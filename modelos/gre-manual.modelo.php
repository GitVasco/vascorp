<?php

require_once "conexion.php";

/**
 * GRE remitente manual. Tablas propias (gre_manualjf, gre_manual_detjf,
 * gre_manual_seriejf). No toca ventajf ni movimientos: no mueve stock.
 */
class ModeloGreManual
{
    public static function mdlTablasListas()
    {
        $pdo = Conexion::conectar();
        $faltan = array();
        foreach (array("gre_manualjf", "gre_manual_detjf", "gre_manual_seriejf") as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }
        if (!count($faltan)) {
            $columnas = array(array("gre_manual_seriejf", "tipo"), array("gre_manualjf", "tipo"), array("gre_manualjf", "doc_interno"));
            foreach ($columnas as $tc) {
                $stmt = $pdo->query("SHOW COLUMNS FROM " . $tc[0] . " LIKE " . $pdo->quote($tc[1]));
                if (!$stmt || !$stmt->fetch()) {
                    $faltan[] = $tc[0] . "." . $tc[1] . " (ejecutar docs/sql/gre-manual-tipo.sql)";
                }
            }
        }
        return $faltan;
    }

    public static function mdlSeries()
    {
        $stmt = Conexion::conectar()->query("SELECT serie, correlativo, tipo FROM gre_manual_seriejf WHERE activo = 1 ORDER BY tipo, serie");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*=============================================
    BÚSQUEDAS
    =============================================*/

    public static function mdlBuscarClientes($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT c.codigo, c.nombre, c.tipo_documento AS tipo_doc, c.documento,
                c.direccion, c.email, IF(LENGTH(c.ubigeo) = 6, c.ubigeo, '') AS ubigeo,
                u.departamento, u.provincia, u.distrito
            FROM clientesjf c
            LEFT JOIN ubigeo u ON c.ubigeo = u.codigo
            WHERE c.nombre LIKE :q OR c.documento LIKE :q OR c.codigo LIKE :q
            ORDER BY c.nombre LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarProveedores($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT CodRuc AS codigo, RazPro AS nombre, '6' AS tipo_doc,
                RucPro AS documento, DirPro AS direccion, EmaPro AS email
            FROM proveedor
            WHERE EstPro = '1' AND (RazPro LIKE :q OR RucPro LIKE :q)
            ORDER BY RazPro LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarUbigeos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT codigo, departamento, provincia, distrito
            FROM ubigeo
            WHERE codigo <> '000000' AND (codigo LIKE :q OR distrito LIKE :q OR provincia LIKE :q)
            ORDER BY departamento, provincia, distrito LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarModelos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT modelo AS codigo, nombre AS descripcion,
                COALESCE(NULLIF(TRIM(cod_unidad), ''), 'C62') AS unidad
            FROM modelojf
            WHERE modelo LIKE :q OR nombre LIKE :q
            ORDER BY modelo LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarArticulos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT a.articulo AS codigo, a.nombre AS descripcion,
                COALESCE(NULLIF(TRIM(m.cod_unidad), ''), 'C62') AS unidad
            FROM articulojf a
            LEFT JOIN modelojf m ON a.modelo = m.modelo
            WHERE a.articulo LIKE :q OR a.nombre LIKE :q
            ORDER BY a.articulo LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarMateriaPrima($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT p.CodPro AS codigo,
                CONCAT_WS(' - ', p.DesPro, tbcol.des_larga) AS descripcion
            FROM producto p
            LEFT JOIN tabla_m_detalle tbcol ON tbcol.cod_tabla = 'TCOL' AND tbcol.cod_argumento = p.ColPro
            WHERE p.CodPro LIKE :q OR p.DesPro LIKE :q
            ORDER BY p.CodPro LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUnidades()
    {
        $stmt = Conexion::conectar()->query("SELECT codigo, descripcion FROM unidades_medidajf ORDER BY descripcion");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mismas tablas que usa la guía desde pedidos (tabla_m_detalle). */
    public static function mdlChoferes()
    {
        $stmt = Conexion::conectar()->query("SELECT cod_argumento AS codigo, valor_3 AS doc, des_larga AS nombres,
                des_corta AS apellidos, valor_4 AS licencia
            FROM tabla_m_detalle WHERE cod_tabla = 'tcho' ORDER BY des_larga");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlVehiculos()
    {
        $stmt = Conexion::conectar()->query("SELECT cod_argumento AS codigo, valor_3 AS placa, des_larga AS descripcion
            FROM tabla_m_detalle WHERE cod_tabla = 'TCAR' ORDER BY valor_3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlAgencias()
    {
        $stmt = Conexion::conectar()->query("SELECT nombre, ruc, mtc FROM agenciasjf ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*=============================================
    CRUD
    =============================================*/

    public static function mdlListar($desde, $hasta, $estado, $tipo = "")
    {
        $sql = "SELECT g.id, g.documento, g.tipo, g.doc_interno, g.fecha_emision, g.fecha_traslado, g.motivo_cod, g.motivo_desc,
                    g.modalidad, g.dest_nombre, g.dest_doc, g.lle_dist, g.peso_kg, g.estado,
                    g.usuario_registro, g.fecha_envio,
                    (SELECT COUNT(*) FROM gre_manual_detjf d WHERE d.id_gre = g.id) AS items
                FROM gre_manualjf g
                WHERE g.fecha_emision BETWEEN :desde AND :hasta";
        if ($estado !== "") {
            $sql .= " AND g.estado = :estado";
        }
        if ($tipo !== "") {
            $sql .= " AND g.tipo = :tipo";
        }
        $sql .= " ORDER BY g.id DESC LIMIT 1000";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        if ($estado !== "") {
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        }
        if ($tipo !== "") {
            $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtener($id)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM gre_manualjf WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT origen, codigo, descripcion, unidad_cod, unidad_desc, cantidad
            FROM gre_manual_detjf WHERE id_gre = :id ORDER BY item");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $cab;
    }

    /**
     * Inserta (id = 0) o actualiza (solo GENERADO). El correlativo se toma
     * con bloqueo dentro de la misma transacción.
     */
    public static function mdlGuardar($id, $serie, $c, $items, $usuario)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $cols = array(
                "fecha_emision", "fecha_traslado", "motivo_cod", "motivo_desc", "modalidad", "peso_kg", "bultos",
                "observaciones", "dest_origen", "dest_codigo", "dest_nombre", "dest_tipo_doc", "dest_doc",
                "dest_email", "par_ubigeo", "par_direccion", "par_dpto", "par_prov", "par_dist", "lle_ubigeo",
                "lle_direccion", "lle_dpto", "lle_prov", "lle_dist", "transp_ruc", "transp_nombre", "transp_mtc",
                "chofer_tipo_doc", "chofer_doc", "chofer_nombres", "chofer_apellidos", "chofer_licencia",
                "placa", "docs_rel",
            );

            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
                $estado = $stmt->fetchColumn();
                if ($estado !== "GENERADO") {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "Solo se puede editar una guía en estado GENERADO.");
                }
                $set = array();
                foreach ($cols as $col) {
                    $set[] = "$col = :$col";
                }
                $stmt = $pdo->prepare("UPDATE gre_manualjf SET " . implode(", ", $set) . " WHERE id = :id");
                foreach ($cols as $col) {
                    $stmt->bindValue(":" . $col, $c[$col]);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
                $pdo->prepare("DELETE FROM gre_manual_detjf WHERE id_gre = " . (int) $id)->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, correlativo, tipo FROM gre_manual_seriejf WHERE serie = :serie AND activo = 1 FOR UPDATE");
                $stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
                $stmt->execute();
                $s = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$s) {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "La serie no existe o está inactiva.");
                }
                $numero = (int) $s["correlativo"] + 1;
                $documento = $serie . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO gre_manualjf (serie, numero, documento, tipo, usuario_registro, " . implode(", ", $cols) . ")
                    VALUES (:serie, :numero, :documento, :tipo, :usuario, :" . implode(", :", $cols) . ")");
                foreach ($cols as $col) {
                    $stmt->bindValue(":" . $col, $c[$col]);
                }
                $stmt->bindValue(":serie", $serie);
                $stmt->bindValue(":numero", $numero, PDO::PARAM_INT);
                $stmt->bindValue(":documento", $documento);
                $stmt->bindValue(":tipo", $s["tipo"]);
                $stmt->bindValue(":usuario", $usuario);
                $stmt->execute();
                $id = (int) $pdo->lastInsertId();

                $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                    ->execute(array(":n" => $numero, ":id" => $s["id"]));
            }

            $stmt = $pdo->prepare("INSERT INTO gre_manual_detjf (id_gre, item, origen, codigo, descripcion, unidad_cod, unidad_desc, cantidad)
                VALUES (:id, :item, :origen, :codigo, :descripcion, :unidad_cod, :unidad_desc, :cantidad)");
            $n = 0;
            foreach ($items as $it) {
                $n++;
                $stmt->execute(array(
                    ":id" => $id,
                    ":item" => $n,
                    ":origen" => $it["origen"],
                    ":codigo" => $it["codigo"],
                    ":descripcion" => $it["descripcion"],
                    ":unidad_cod" => $it["unidad_cod"],
                    ":unidad_desc" => $it["unidad_desc"],
                    ":cantidad" => $it["cantidad"],
                ));
            }

            $pdo->commit();
            $stmt = $pdo->prepare("SELECT documento FROM gre_manualjf WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return array("ok" => true, "id" => $id, "documento" => $stmt->fetchColumn());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo guardar la guía.");
        }
    }

    public static function mdlMarcarEnviado($id, $archivo)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE gre_manualjf SET estado = 'ENVIADO', archivo_csv = :a, fecha_envio = NOW()
            WHERE id = :id AND estado = 'GENERADO' AND tipo = 'ELECTRONICA'");
        $stmt->bindParam(":a", $archivo, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    public static function mdlAnular($id)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE gre_manualjf SET estado = 'ANULADO' WHERE id = :id AND estado = 'GENERADO'");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }


    /**
     * Convierte una guía INTERNA (GENERADO) en ELECTRONICA: toma el siguiente
     * número de la serie electrónica y guarda el número interno en doc_interno.
     */
    public static function mdlConvertirAElectronica($id, $serieDestino)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT documento, tipo, estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $g = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$g || $g["tipo"] !== "INTERNA" || $g["estado"] !== "GENERADO") {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "Solo se convierte una guía INTERNA en estado GENERADO.");
            }
            $stmt = $pdo->prepare("SELECT id, correlativo FROM gre_manual_seriejf WHERE serie = :s AND activo = 1 AND tipo = 'ELECTRONICA' FOR UPDATE");
            $stmt->bindParam(":s", $serieDestino, PDO::PARAM_STR);
            $stmt->execute();
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$s) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "La serie electrónica no existe o está inactiva.");
            }
            $numero = (int) $s["correlativo"] + 1;
            $documento = $serieDestino . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);
            $pdo->prepare("UPDATE gre_manualjf SET serie = :serie, numero = :numero, documento = :doc, tipo = 'ELECTRONICA', doc_interno = :int WHERE id = :id")
                ->execute(array(":serie" => $serieDestino, ":numero" => $numero, ":doc" => $documento, ":int" => $g["documento"], ":id" => $id));
            $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                ->execute(array(":n" => $numero, ":id" => $s["id"]));
            $pdo->commit();
            return array("ok" => true, "documento" => $documento, "doc_interno" => $g["documento"]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo convertir la guía.");
        }
    }

    /** Elimina una guía no enviada (GENERADO o ANULADO). No toca el correlativo. */
    public static function mdlEliminar($id)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $estado = $stmt->fetchColumn();
            if ($estado !== "GENERADO" && $estado !== "ANULADO") {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare("DELETE FROM gre_manual_detjf WHERE id_gre = " . (int) $id)->execute();
            $pdo->prepare("DELETE FROM gre_manualjf WHERE id = " . (int) $id)->execute();
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return false;
        }
    }

    /** Series con su último número usado y el mayor número realmente emitido. */
    public static function mdlSeriesCorrelativo()
    {
        $stmt = Conexion::conectar()->query("SELECT s.serie, s.correlativo, s.tipo,
                IFNULL((SELECT MAX(g.numero) FROM gre_manualjf g WHERE g.serie = s.serie), 0) AS max_emitido
            FROM gre_manual_seriejf s WHERE s.activo = 1 ORDER BY s.tipo, s.serie");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fija el último número usado de la serie (el próximo será n + 1).
     * No puede ser menor al mayor número ya emitido: chocaría con una guía existente.
     */
    public static function mdlFijarCorrelativo($serie, $n)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM gre_manual_seriejf WHERE serie = :s AND activo = 1 FOR UPDATE");
            $stmt->bindParam(":s", $serie, PDO::PARAM_STR);
            $stmt->execute();
            $idSerie = $stmt->fetchColumn();
            if (!$idSerie) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "La serie no existe.");
            }
            $stmt = $pdo->prepare("SELECT IFNULL(MAX(numero), 0) FROM gre_manualjf WHERE serie = :s");
            $stmt->bindParam(":s", $serie, PDO::PARAM_STR);
            $stmt->execute();
            $max = (int) $stmt->fetchColumn();
            if ($n < $max) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "No puede ser menor a $max (ya existe esa guía en la serie $serie).");
            }
            $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                ->execute(array(":n" => $n, ":id" => $idSerie));
            $pdo->commit();
            return array("ok" => true, "proximo" => $serie . "-" . str_pad($n + 1, 8, "0", STR_PAD_LEFT));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo actualizar el correlativo.");
        }
    }
}
