<?php

require_once "conexion.php";

/**
 * Liquidaciones de servicios de producción: agrupan los cierres (cierresjf) de un taller en un
 * rango de fechas, congelan precios y datos del taller, y sirven para pagar al taller.
 * Cada línea trae la OSP de origen (NULL = "Sin OSP", se completa sola al emitir la OSP).
 * Tablas: liquidaciones_servicio_prodjf, liquidaciones_servicio_prod_cierrejf,
 * liquidaciones_servicio_prod_detjf y la columna cierresjf.liquidacion
 * (docs/sql/liquidaciones-servicio-prod.sql).
 */
class ModeloLiquidacionesServicioProd
{
    const TALLAS = array("S", "M", "L", "XL", "XXL", "XS", "T7", "T8");

    /** Conexión compartida con errores como excepciones (en PHP 5.6 PDO calla por defecto y las transacciones no harían rollback). */
    private static function pdo()
    {
        $pdo = Conexion::conectar();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public static function mdlTablasListas()
    {
        $pdo = self::pdo();
        $faltan = array();
        foreach (array("liquidaciones_servicio_prodjf", "liquidaciones_servicio_prod_cierrejf", "liquidaciones_servicio_prod_detjf", "ordenes_servicio_prodjf", "gre_sector_datosjf") as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }
        foreach (array(array("cierresjf", "liquidacion"), array("serviciosjf", "orden_servicio"), array("sectorjf", "cuenta_bcp"), array("sectorjf", "precios_igv"), array("liquidaciones_servicio_prodjf", "precios_igv")) as $tc) {
            $stmt = $pdo->query("SHOW COLUMNS FROM " . $tc[0] . " LIKE " . $pdo->quote($tc[1]));
            if (!$stmt || !$stmt->fetch()) {
                $faltan[] = $tc[0] . "." . $tc[1];
            }
        }
        return $faltan;
    }

    /** Talleres que tienen al menos un cierre. */
    public static function mdlTalleres()
    {
        $stmt = self::pdo()->query(
            "SELECT DISTINCT s.cod_sector, s.nom_sector
             FROM cierresjf c
             INNER JOIN sectorjf s ON c.taller = s.cod_sector
             ORDER BY s.nom_sector"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Datos fiscales del taller (gre_sector_datosjf); null si no están cargados. */
    public static function mdlDatosTaller($taller)
    {
        $stmt = self::pdo()->prepare(
            "SELECT s.cod_sector, s.nom_sector, g.razon_social, g.tipo_doc, g.doc, g.direccion, g.dist, g.prov, g.dpto,
                    s.cuenta_detraccion, s.cuenta_bcp, s.precios_igv
             FROM sectorjf s
             LEFT JOIN gre_sector_datosjf g ON g.cod_sector = s.cod_sector
             WHERE s.cod_sector = :t"
        );
        $stmt->bindValue(":t", $taller, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? $r : null;
    }

    /**
     * Cierres del taller en el rango que aún no están liquidados ni pagados.
     * $bloquear = true agrega FOR UPDATE (usar dentro de transacción).
     */
    private static function cierres($pdo, $taller, $desde, $hasta, $bloquear = false)
    {
        $sql = "SELECT c.codigo, c.guia, c.fecha,
                    (SELECT COALESCE(SUM(cd.inicio), 0) FROM cierres_detallejf cd WHERE cd.codigo = c.codigo) AS unidades
                FROM cierresjf c
                WHERE c.taller = :taller
                  AND DATE(c.fecha) BETWEEN :desde AND :hasta
                  AND COALESCE(c.estado, '') <> 'ANULADO'
                  AND c.liquidacion IS NULL
                  AND COALESCE(c.estado_pago, '') <> 'PAGADO'
                ORDER BY c.fecha, c.codigo";
        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":taller", $taller, PDO::PARAM_STR);
        $stmt->bindValue(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cuántos cierres del rango no entran: ya liquidados, o pagados por el módulo Pagos. */
    private static function cierresExcluidos($pdo, $taller, $desde, $hasta)
    {
        $stmt = $pdo->prepare(
            "SELECT
                SUM(c.liquidacion IS NOT NULL) AS liquidados,
                SUM(c.liquidacion IS NULL AND COALESCE(c.estado_pago, '') = 'PAGADO') AS pagados
             FROM cierresjf c
             WHERE c.taller = :taller AND DATE(c.fecha) BETWEEN :desde AND :hasta AND COALESCE(c.estado, '') <> 'ANULADO'"
        );
        $stmt->execute(array(":taller" => $taller, ":desde" => $desde, ":hasta" => $hasta));
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return array("liquidados" => (int) $r["liquidados"], "pagados" => (int) $r["pagados"]);
    }

    /**
     * Líneas (modelo/color/tallas/precio) de los cierres dados, con la OSP de origen.
     * Se agrupa por cierre + servicio de origen + modelo + color: así cada línea tiene una sola OSP.
     */
    private static function detalleCierres($pdo, $taller, array $codigos)
    {
        if (!count($codigos)) {
            return array();
        }
        $cols = "";
        for ($i = 1; $i <= 8; $i++) {
            $cols .= "SUM(CASE WHEN a.cod_talla = '$i' THEN cd.inicio ELSE 0 END) AS t$i,";
        }
        $ph = implode(",", array_fill(0, count($codigos), "?"));
        $stmt = $pdo->prepare(
            "SELECT cd.codigo AS cierre, svc.codigo AS servicio, svc.orden_servicio AS id_osp, o.numero AS osp_numero,
                    a.modelo, a.nombre, a.cod_color, a.color, $cols
                    SUM(cd.inicio) AS cantidad,
                    (SELECT ps.precio_doc FROM precio_serviciojf ps
                      WHERE ps.taller = ? AND ps.modelo = a.modelo ORDER BY ps.id DESC LIMIT 1) AS precio_doc
             FROM cierres_detallejf cd
             LEFT JOIN articulojf a ON cd.articulo = a.articulo
             LEFT JOIN servicios_detallejf sdt ON sdt.id = cd.cod_servicio
             LEFT JOIN serviciosjf svc ON svc.codigo = sdt.codigo
             LEFT JOIN ordenes_servicio_prodjf o ON o.id = svc.orden_servicio AND o.estado = 'EMITIDA'
             WHERE cd.codigo IN ($ph)
             GROUP BY cd.codigo, svc.codigo, svc.orden_servicio, o.numero, a.modelo, a.nombre, a.cod_color, a.color
             HAVING SUM(cd.inicio) > 0
             ORDER BY cd.codigo, a.modelo, a.cod_color, svc.codigo"
        );
        $stmt->execute(array_merge(array($taller), array_values($codigos)));
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f["id_osp"] = $f["id_osp"] === null || $f["osp_numero"] === null ? null : (int) $f["id_osp"];
            $f["precio_doc"] = $f["precio_doc"] === null ? null : (float) $f["precio_doc"];
            $f["cantidad"] = (int) $f["cantidad"];
            for ($i = 1; $i <= 8; $i++) {
                $f["t$i"] = (int) $f["t$i"];
            }
            $f["importe"] = $f["precio_doc"] === null ? 0 : round($f["cantidad"] / 12 * $f["precio_doc"], 2);
        }
        unset($f);
        return $filas;
    }

    /**
     * Completa la OSP de las líneas de liquidaciones vigentes que aún no la tenían.
     * La OSP se emite después del cierre: se toma de serviciosjf.orden_servicio (solo EMITIDAS).
     * Es lo único que cambia en una liquidación ya emitida.
     */
    public static function mdlSincronizarOsp()
    {
        self::pdo()->exec(
            "UPDATE liquidaciones_servicio_prod_detjf d
             INNER JOIN liquidaciones_servicio_prodjf l ON l.id = d.id_liquidacion AND l.estado <> 'ANULADA'
             INNER JOIN serviciosjf sv ON sv.codigo = d.servicio AND sv.orden_servicio IS NOT NULL
             INNER JOIN ordenes_servicio_prodjf o ON o.id = sv.orden_servicio AND o.estado = 'EMITIDA'
             SET d.id_osp = o.id, d.osp_numero = o.numero
             WHERE d.id_osp IS NULL"
        );
    }

    /**
     * Arma (sin guardar) el contenido de una liquidación: cierres libres + detalle + advertencias.
     */
    public static function mdlVistaPrevia($taller, $desde, $hasta, $pdo = null, $bloquear = false)
    {
        $pdo = $pdo ? $pdo : self::pdo();
        $cierres = self::cierres($pdo, $taller, $desde, $hasta, $bloquear);
        $excl = self::cierresExcluidos($pdo, $taller, $desde, $hasta);

        $codigos = array();
        foreach ($cierres as $c) {
            $codigos[] = $c["codigo"];
        }
        $det = self::detalleCierres($pdo, $taller, $codigos);

        $porCierre = array();
        foreach ($det as $d) {
            $porCierre[$d["cierre"]][] = $d;
        }

        $totalUni = 0;
        $totalImp = 0;
        $sinPrecio = array();
        $sinOsp = 0;
        $uniSinOsp = 0;
        $impSinOsp = 0;
        $lista = array();
        foreach ($cierres as $c) {
            $c["detalle"] = isset($porCierre[$c["codigo"]]) ? $porCierre[$c["codigo"]] : array();
            $c["unidades"] = 0;
            $c["importe"] = 0;
            $osp = array();
            $serv = array();
            $c["lineas_sin_osp"] = 0;
            foreach ($c["detalle"] as $d) {
                $c["unidades"] += $d["cantidad"];
                $c["importe"] += $d["importe"];
                if ($d["servicio"] !== null && $d["servicio"] !== "") {
                    $serv[$d["servicio"]] = true;
                }
                if ($d["precio_doc"] === null) {
                    $sinPrecio[$d["modelo"]] = true;
                }
                if ($d["id_osp"] === null) {
                    $c["lineas_sin_osp"]++;
                    $sinOsp++;
                    $uniSinOsp += $d["cantidad"];
                    $impSinOsp += $d["importe"];
                } else {
                    $osp[$d["osp_numero"]] = true;
                }
            }
            if ($c["unidades"] <= 0) {
                continue; // solo entran cierres con prendas
            }
            $c["osp"] = array_keys($osp);
            $c["servicios"] = array_keys($serv);
            $totalUni += $c["unidades"];
            $totalImp += $c["importe"];
            $lista[] = $c;
        }

        return array(
            "cierres" => $lista,
            "total_unidades" => $totalUni,
            "total_importe" => round($totalImp, 2),
            "lineas_sin_osp" => $sinOsp,
            "unidades_sin_osp" => $uniSinOsp,
            "importe_sin_osp" => round($impSinOsp, 2),
            "ya_liquidados" => $excl["liquidados"],
            "ya_pagados" => $excl["pagados"],
            "sin_precio" => array_keys($sinPrecio),
        );
    }

    /**
     * Crea la liquidación dentro de una transacción. Devuelve array(ok, id|msg).
     * Vuelve a leer los cierres con FOR UPDATE para que dos usuarios no tomen los mismos.
     */
    public static function mdlCrear($taller, $desde, $hasta, $observaciones, $usuarioId, $usuarioNombre)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();

            $datos = self::mdlDatosTaller($taller);
            if (!$datos) {
                throw new Exception("Taller no encontrado.");
            }
            $prev = self::mdlVistaPrevia($taller, $desde, $hasta, $pdo, true);
            if (!count($prev["cierres"])) {
                throw new Exception("No hay cierres por liquidar para ese taller y rango de fechas.");
            }
            if (count($prev["sin_precio"])) {
                throw new Exception("Faltan precios de servicio para los modelos: " . implode(", ", $prev["sin_precio"]) . ". Regístrelos en Precio servicio.");
            }

            $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 5) AS UNSIGNED)), 0) + 1 FROM liquidaciones_servicio_prodjf FOR UPDATE");
            $correlativo = (int) $stmt->fetchColumn();
            $numero = "LSP-" . str_pad($correlativo, 6, "0", STR_PAD_LEFT);

            $direccion = trim($datos["direccion"] . " " . ($datos["dist"] ? "- " . $datos["dist"] . " - " . $datos["prov"] . " - " . $datos["dpto"] : ""));
            $stmt = $pdo->prepare(
                "INSERT INTO liquidaciones_servicio_prodjf
                 (numero, taller, taller_nombre, taller_razon, taller_ruc, taller_direccion, taller_cta_detraccion, taller_cta_bcp, precios_igv, fecha_desde, fecha_hasta,
                  fecha_emision, usuario, usuario_nombre, observaciones, total_cierres, total_unidades, total_importe, estado)
                 VALUES (:numero, :taller, :tn, :tr, :truc, :tdir, :tcd, :tcb, :pigv, :desde, :hasta, NOW(), :uid, :unom, :obs, :tc, :tu, :ti, 'LIQUIDADA')"
            );
            $stmt->execute(array(
                ":numero" => $numero,
                ":taller" => $taller,
                ":tn" => $datos["nom_sector"],
                ":tr" => $datos["razon_social"],
                ":truc" => $datos["doc"],
                ":tdir" => $direccion,
                ":tcd" => $datos["cuenta_detraccion"],
                ":tcb" => $datos["cuenta_bcp"],
                ":pigv" => !empty($datos["precios_igv"]) ? 1 : 0,
                ":desde" => $desde,
                ":hasta" => $hasta,
                ":uid" => $usuarioId,
                ":unom" => $usuarioNombre,
                ":obs" => $observaciones,
                ":tc" => count($prev["cierres"]),
                ":tu" => $prev["total_unidades"],
                ":ti" => $prev["total_importe"],
            ));
            $id = (int) $pdo->lastInsertId();

            $insCierre = $pdo->prepare("INSERT INTO liquidaciones_servicio_prod_cierrejf (id_liquidacion, cierre, guia, fecha_cierre, unidades) VALUES (:l, :c, :g, :f, :u)");
            $insDet = $pdo->prepare(
                "INSERT INTO liquidaciones_servicio_prod_detjf
                 (id_liquidacion, cierre, servicio, id_osp, osp_numero, modelo, nombre, cod_color, color, t1, t2, t3, t4, t5, t6, t7, t8, cantidad, precio_doc, importe)
                 VALUES (:l, :c, :s, :io, :on, :m, :n, :cc, :co, :t1, :t2, :t3, :t4, :t5, :t6, :t7, :t8, :q, :p, :i)"
            );
            $marca = $pdo->prepare("UPDATE cierresjf SET liquidacion = :l WHERE codigo = :c AND liquidacion IS NULL");

            foreach ($prev["cierres"] as $c) {
                $insCierre->execute(array(":l" => $id, ":c" => $c["codigo"], ":g" => $c["guia"], ":f" => $c["fecha"], ":u" => $c["unidades"]));
                foreach ($c["detalle"] as $d) {
                    $insDet->execute(array(
                        ":l" => $id, ":c" => $c["codigo"], ":s" => $d["servicio"], ":io" => $d["id_osp"], ":on" => $d["osp_numero"],
                        ":m" => $d["modelo"], ":n" => $d["nombre"], ":cc" => $d["cod_color"], ":co" => $d["color"],
                        ":t1" => $d["t1"], ":t2" => $d["t2"], ":t3" => $d["t3"], ":t4" => $d["t4"],
                        ":t5" => $d["t5"], ":t6" => $d["t6"], ":t7" => $d["t7"], ":t8" => $d["t8"],
                        ":q" => $d["cantidad"], ":p" => $d["precio_doc"], ":i" => $d["importe"],
                    ));
                }
                $marca->execute(array(":l" => $id, ":c" => $c["codigo"]));
                if ($marca->rowCount() < 1) {
                    throw new Exception("El cierre " . $c["codigo"] . " ya fue tomado por otra liquidación. Intente de nuevo.");
                }
            }

            $pdo->commit();
            return array("ok" => true, "id" => $id, "numero" => $numero);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    public static function mdlListar($desde, $hasta, $taller, $estado)
    {
        self::mdlSincronizarOsp();
        $sql = "SELECT l.id, l.numero, l.taller, l.taller_nombre, l.taller_ruc, l.fecha_desde, l.fecha_hasta, l.fecha_emision,
                    l.usuario_nombre, l.total_cierres, l.total_unidades, l.total_importe, l.estado, l.fecha_pago,
                    (SELECT COUNT(*) FROM liquidaciones_servicio_prod_detjf d WHERE d.id_liquidacion = l.id AND d.id_osp IS NULL) AS lineas_sin_osp
                FROM liquidaciones_servicio_prodjf l
                WHERE l.fecha_desde <= :hasta AND l.fecha_hasta >= :desde";
        $par = array(":desde" => $desde, ":hasta" => $hasta);
        if ($taller !== "") {
            $sql .= " AND l.taller = :taller";
            $par[":taller"] = $taller;
        }
        if ($estado !== "") {
            $sql .= " AND l.estado = :estado";
            $par[":estado"] = $estado;
        }
        $sql .= " ORDER BY l.id DESC LIMIT 1000";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Liquidación completa (cabecera + cierres + detalle) para ver/imprimir. */
    public static function mdlObtener($id)
    {
        self::mdlSincronizarOsp();
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM liquidaciones_servicio_prodjf WHERE id = :id");
        $stmt->execute(array(":id" => (int) $id));
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT * FROM liquidaciones_servicio_prod_cierrejf WHERE id_liquidacion = :id ORDER BY fecha_cierre, cierre");
        $stmt->execute(array(":id" => (int) $id));
        $cierres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT * FROM liquidaciones_servicio_prod_detjf WHERE id_liquidacion = :id ORDER BY cierre, modelo, cod_color, servicio");
        $stmt->execute(array(":id" => (int) $id));
        $det = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cierres as &$c) {
            $c["detalle"] = array();
            foreach ($det as $d) {
                if ($d["cierre"] === $c["cierre"]) {
                    $c["detalle"][] = $d;
                }
            }
        }
        unset($c);
        $cab["cierres"] = $cierres;
        return $cab;
    }

    /** Marca la liquidación como pagada y sus cierres como PAGADO (igual que el módulo Pagos). */
    public static function mdlPagar($id, $fechaPago, $referencia, $usuarioNombre)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT estado FROM liquidaciones_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $estado = $stmt->fetchColumn();
            if ($estado === false) {
                throw new Exception("Liquidación no encontrada.");
            }
            if ($estado !== "LIQUIDADA") {
                throw new Exception("Solo se puede pagar una liquidación en estado LIQUIDADA.");
            }
            $pdo->prepare("UPDATE liquidaciones_servicio_prodjf SET estado = 'PAGADA', fecha_pago = :f, referencia_pago = :r, pagado_por = :u WHERE id = :id")
                ->execute(array(":f" => $fechaPago, ":r" => $referencia, ":u" => $usuarioNombre, ":id" => (int) $id));
            $pdo->prepare("UPDATE cierresjf SET estado_pago = 'PAGADO' WHERE liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    /** Revierte el pago: la liquidación vuelve a LIQUIDADA y sus cierres a POR PAGAR. */
    public static function mdlRevertirPago($id)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT estado FROM liquidaciones_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $estado = $stmt->fetchColumn();
            if ($estado !== "PAGADA") {
                throw new Exception("La liquidación no está pagada.");
            }
            $pdo->prepare("UPDATE liquidaciones_servicio_prodjf SET estado = 'LIQUIDADA', fecha_pago = NULL, referencia_pago = NULL, pagado_por = NULL WHERE id = :id")
                ->execute(array(":id" => (int) $id));
            $pdo->prepare("UPDATE cierresjf SET estado_pago = 'POR PAGAR' WHERE liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    /** Anula la liquidación (no pagada) y libera sus cierres para poder incluirlos en otra. */
    public static function mdlAnular($id, $motivo, $usuarioNombre)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT estado FROM liquidaciones_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $estado = $stmt->fetchColumn();
            if ($estado === false) {
                throw new Exception("Liquidación no encontrada.");
            }
            if ($estado === "ANULADA") {
                throw new Exception("La liquidación ya está anulada.");
            }
            if ($estado === "PAGADA") {
                throw new Exception("La liquidación está pagada. Revierta el pago antes de anularla.");
            }
            $pdo->prepare("UPDATE liquidaciones_servicio_prodjf SET estado = 'ANULADA', anulado_por = :u, anulado_en = NOW(), motivo_anulacion = :m WHERE id = :id")
                ->execute(array(":u" => $usuarioNombre, ":m" => $motivo, ":id" => (int) $id));
            $pdo->prepare("UPDATE cierresjf SET liquidacion = NULL WHERE liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    /** Elimina la liquidación (no pagada) por completo y libera sus cierres. */
    public static function mdlEliminar($id)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT numero, estado FROM liquidaciones_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $l = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$l) {
                throw new Exception("Liquidación no encontrada.");
            }
            if ($l["estado"] === "PAGADA") {
                throw new Exception("La liquidación está pagada. Revierta el pago antes de eliminarla.");
            }
            $pdo->prepare("UPDATE cierresjf SET liquidacion = NULL WHERE liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM liquidaciones_servicio_prod_detjf WHERE id_liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM liquidaciones_servicio_prod_cierrejf WHERE id_liquidacion = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM liquidaciones_servicio_prodjf WHERE id = :id")->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true, "numero" => $l["numero"]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }
}
