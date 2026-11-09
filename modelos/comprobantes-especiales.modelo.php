<?php

require_once "conexion.php";

/**
 * Comprobantes especiales (boleta/factura): hoy el tipo GRATUITA (operaciones gratuitas).
 * Tablas propias (comp_especialjf, comp_especial_detjf, comp_especial_seriejf). No toca
 * ventajf / movimientosjf / articulojf: no mueve stock ni genera cobranza.
 */
class ModeloComprobantesEspeciales
{
    public static function mdlTablasListas()
    {
        $pdo = Conexion::conectar();
        $faltan = array();
        foreach (array("comp_especialjf", "comp_especial_detjf", "comp_especial_seriejf") as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }
        if (!count($faltan)) {
            $stmt = $pdo->query("SHOW COLUMNS FROM comp_especialjf LIKE 'tipo_operacion'");
            if (!$stmt || !$stmt->fetch()) {
                $faltan[] = "comp_especialjf.tipo_operacion (ejecutar docs/sql/comprobantes-especiales-migracion.sql)";
            }
        }
        return $faltan;
    }

    public static function mdlSeries()
    {
        $stmt = Conexion::conectar()->query("SELECT tipo_doc, serie, correlativo FROM comp_especial_seriejf WHERE activo = 1 ORDER BY tipo_doc, serie");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListar($desde, $hasta, $estado, $lote = "", $tipoOp = "")
    {
        $sql = "SELECT o.id, o.documento, o.tipo_doc, o.tipo_operacion, o.fecha_emision, o.lote, o.categoria, o.afectacion, o.motivo,
                    o.receptor_tipo_doc, o.receptor_doc, o.receptor_nombre, o.total_gratuito, o.igv_informativo,
                    o.estado, o.archivo_csv, o.usuario_registro, o.aprobado_por, o.fecha_envio,
                    (SELECT COUNT(*) FROM comp_especial_detjf d WHERE d.id_comprobante = o.id) AS items
                FROM comp_especialjf o
                WHERE o.fecha_emision BETWEEN :desde AND :hasta";
        if ($estado !== "") {
            $sql .= " AND o.estado = :estado";
        }
        if ($lote !== "") {
            $sql .= " AND o.lote = :lote";
        }
        if ($tipoOp !== "") {
            $sql .= " AND o.tipo_operacion = :tipo_op";
        }
        $sql .= " ORDER BY o.id DESC LIMIT 2000";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        if ($estado !== "") {
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        }
        if ($lote !== "") {
            $stmt->bindParam(":lote", $lote, PDO::PARAM_STR);
        }
        if ($tipoOp !== "") {
            $stmt->bindParam(":tipo_op", $tipoOp, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtener($id)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM comp_especialjf WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT codigo, descripcion, unidad_cod, cantidad, valor_ref_unit, valor_venta, igv_informativo
            FROM comp_especial_detjf WHERE id_comprobante = :id ORDER BY item");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $cab;
    }

    /**
     * Crea varios comprobantes (uno por receptor) en UNA transacción.
     * $docs: array de array("cab" => array(...), "items" => array(...)). Todos o ninguno.
     * El correlativo se toma con bloqueo (FOR UPDATE) de la serie activa del tipo.
     */
    public static function mdlCrearLote($docs, $usuario)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $cols = array(
                "tipo_operacion", "fecha_emision", "moneda", "lote", "categoria", "afectacion", "motivo", "observaciones",
                "receptor_tipo_doc", "receptor_doc", "receptor_nombre", "receptor_direccion", "receptor_ubigeo",
                "receptor_dpto", "receptor_prov", "receptor_dist", "receptor_email",
                "total_gratuito", "igv_informativo", "aprobado_por",
            );
            $selSerie = $pdo->prepare("SELECT id, serie, correlativo FROM comp_especial_seriejf WHERE tipo_doc = :t AND activo = 1 ORDER BY id LIMIT 1 FOR UPDATE");
            $insCab = $pdo->prepare("INSERT INTO comp_especialjf (tipo_doc, serie, numero, documento, usuario_registro, " . implode(", ", $cols) . ")
                VALUES (:tipo_doc, :serie, :numero, :documento, :usuario, :" . implode(", :", $cols) . ")");
            $insDet = $pdo->prepare("INSERT INTO comp_especial_detjf (id_comprobante, item, codigo, descripcion, unidad_cod, cantidad, valor_ref_unit, valor_venta, igv_informativo)
                VALUES (:id, :item, :codigo, :descripcion, :unidad, :cantidad, :ref, :vv, :igv)");
            $updSerie = $pdo->prepare("UPDATE comp_especial_seriejf SET correlativo = :n WHERE id = :id");

            $creados = array();
            foreach ($docs as $d) {
                $c = $d["cab"];
                $selSerie->execute(array(":t" => $c["tipo_doc"]));
                $s = $selSerie->fetch(PDO::FETCH_ASSOC);
                if (!$s) {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "No hay una serie activa para " . ($c["tipo_doc"] === "01" ? "facturas" : "boletas") . " de comprobantes especiales.");
                }
                $selSerie->closeCursor();
                $numero = (int) $s["correlativo"] + 1;
                $documento = $s["serie"] . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);

                foreach ($cols as $col) {
                    $insCab->bindValue(":" . $col, $c[$col]);
                }
                $insCab->bindValue(":tipo_doc", $c["tipo_doc"]);
                $insCab->bindValue(":serie", $s["serie"]);
                $insCab->bindValue(":numero", $numero, PDO::PARAM_INT);
                $insCab->bindValue(":documento", $documento);
                $insCab->bindValue(":usuario", $usuario);
                $insCab->execute();
                $id = (int) $pdo->lastInsertId();

                $n = 0;
                foreach ($d["items"] as $it) {
                    $n++;
                    $insDet->execute(array(
                        ":id" => $id, ":item" => $n, ":codigo" => $it["codigo"], ":descripcion" => $it["descripcion"],
                        ":unidad" => $it["unidad_cod"], ":cantidad" => $it["cantidad"], ":ref" => $it["valor_ref_unit"],
                        ":vv" => $it["valor_venta"], ":igv" => $it["igv_informativo"],
                    ));
                }
                $updSerie->execute(array(":n" => $numero, ":id" => $s["id"]));
                $creados[] = array("id" => $id, "documento" => $documento);
            }
            $pdo->commit();
            return array("ok" => true, "creados" => $creados);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[COMP_ESPECIALES] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudieron guardar los comprobantes.");
        }
    }

    public static function mdlMarcarEnviado($id, $archivo)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE comp_especialjf SET estado = 'ENVIADO', archivo_csv = :a, fecha_envio = NOW()
            WHERE id = :id AND estado = 'GENERADO'");
        $stmt->bindParam(":a", $archivo, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    /** Registro manual: el comprobante ENVIADO se dio de baja en el portal de eFact. */
    public static function mdlMarcarBaja($id)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE comp_especialjf SET estado = 'BAJA', fecha_baja = NOW() WHERE id = :id AND estado = 'ENVIADO'");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    public static function mdlAnular($id)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE comp_especialjf SET estado = 'ANULADO' WHERE id = :id AND estado = 'GENERADO'");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    public static function mdlEliminar($id)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT estado FROM comp_especialjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $estado = $stmt->fetchColumn();
            if ($estado !== "GENERADO" && $estado !== "ANULADO") {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare("DELETE FROM comp_especial_detjf WHERE id_comprobante = " . (int) $id)->execute();
            $pdo->prepare("DELETE FROM comp_especialjf WHERE id = " . (int) $id)->execute();
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}
