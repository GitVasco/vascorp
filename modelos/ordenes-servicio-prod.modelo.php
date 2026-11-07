<?php

require_once "conexion.php";

/**
 * Órdenes de servicio de producción: agrupan los servicios (serviciosjf) de un taller
 * en un rango de fechas y guardan una foto congelada para sustento ante SUNAT.
 * Tablas: ordenes_servicio_prodjf, ordenes_servicio_prod_servjf, ordenes_servicio_prod_detjf
 * y la columna serviciosjf.orden_servicio (docs/sql/ordenes-servicio-prod.sql).
 */
class ModeloOrdenesServicioProd
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
        foreach (array("ordenes_servicio_prodjf", "ordenes_servicio_prod_servjf", "ordenes_servicio_prod_detjf", "gre_sector_datosjf") as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM serviciosjf LIKE 'orden_servicio'");
        if (!$stmt || !$stmt->fetch()) {
            $faltan[] = "serviciosjf.orden_servicio";
        }
        foreach (array(array("ordenes_servicio_prodjf", "taller_cta_bcp"), array("sectorjf", "cuenta_bcp")) as $tc) {
            $stmt = $pdo->query("SHOW COLUMNS FROM " . $tc[0] . " LIKE " . $pdo->quote($tc[1]));
            if (!$stmt || !$stmt->fetch()) {
                $faltan[] = $tc[0] . "." . $tc[1];
            }
        }
        return $faltan;
    }

    /** Talleres que tienen al menos un servicio. */
    public static function mdlTalleres()
    {
        $stmt = self::pdo()->query(
            "SELECT DISTINCT s.cod_sector, s.nom_sector
             FROM serviciosjf se
             INNER JOIN sectorjf s ON se.taller = s.cod_sector
             ORDER BY s.nom_sector"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Datos fiscales del taller (gre_sector_datosjf); null si no están cargados. */
    public static function mdlDatosTaller($taller)
    {
        $stmt = self::pdo()->prepare(
            "SELECT s.cod_sector, s.nom_sector, g.razon_social, g.tipo_doc, g.doc, g.direccion, g.dist, g.prov, g.dpto,
                    s.cuenta_detraccion, s.cuenta_bcp
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
     * Servicios del taller en el rango. $soloLibres = true devuelve solo los que aún no tienen orden.
     * $bloquear = true agrega FOR UPDATE (usar dentro de transacción).
     */
    private static function servicios($pdo, $taller, $desde, $hasta, $soloLibres, $bloquear = false)
    {
        $sql = "SELECT se.codigo, se.guia, se.fecha, se.estado, se.orden_servicio,
                    (SELECT COALESCE(SUM(sd.cantidad), 0) FROM servicios_detallejf sd WHERE sd.codigo = se.codigo) AS unidades
                FROM serviciosjf se
                WHERE se.taller = :taller
                  AND DATE(se.fecha) BETWEEN :desde AND :hasta
                  AND COALESCE(se.estado, '') <> 'ANULADO'";
        if ($soloLibres) {
            $sql .= " AND se.orden_servicio IS NULL";
        }
        $sql .= " ORDER BY se.fecha, se.codigo";
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

    /**
     * Completa la guía de los servicios de órdenes EMITIDAS que aún no la tenían.
     * La guía se emite después de la orden: puede venir de serviciosjf.guia o de la GRE manual
     * (gre_manualjf.servicio, sin anuladas). Es lo único que cambia en una orden ya emitida.
     */
    public static function mdlSincronizarGuias()
    {
        $pdo = self::pdo();
        $pdo->exec(
            "UPDATE ordenes_servicio_prod_servjf os
             INNER JOIN ordenes_servicio_prodjf o ON o.id = os.id_orden AND o.estado = 'EMITIDA'
             INNER JOIN serviciosjf sv ON sv.codigo = os.servicio
             SET os.guia = sv.guia
             WHERE (os.guia IS NULL OR os.guia = '') AND sv.guia IS NOT NULL AND sv.guia <> ''"
        );
        try {
            $pdo->exec(
                "UPDATE ordenes_servicio_prod_servjf os
                 INNER JOIN ordenes_servicio_prodjf o ON o.id = os.id_orden AND o.estado = 'EMITIDA'
                 INNER JOIN (SELECT servicio, MAX(documento) AS documento FROM gre_manualjf
                             WHERE estado <> 'ANULADO' AND servicio IS NOT NULL AND servicio <> '' GROUP BY servicio) g
                         ON g.servicio = os.servicio
                 SET os.guia = g.documento
                 WHERE (os.guia IS NULL OR os.guia = '')"
            );
        } catch (Exception $e) {
            // sin tabla de GRE manual: solo aplica serviciosjf.guia
        }
    }

    /** Detalle (modelo/color/tallas/precio) de un servicio. */
    private static function detalleServicio($pdo, $codigoServicio, $taller)
    {
        $cols = "";
        for ($i = 1; $i <= 8; $i++) {
            $cols .= "SUM(CASE WHEN a.cod_talla = '$i' THEN sd.cantidad ELSE 0 END) AS t$i,";
        }
        $stmt = $pdo->prepare(
            "SELECT a.modelo, a.nombre, a.cod_color, a.color, $cols
                SUM(sd.cantidad) AS cantidad,
                (SELECT ps.precio_doc FROM precio_serviciojf ps
                  WHERE ps.taller = :taller AND ps.modelo = a.modelo ORDER BY ps.id DESC LIMIT 1) AS precio_doc
             FROM servicios_detallejf sd
             LEFT JOIN articulojf a ON sd.articulo = a.articulo
             WHERE sd.codigo = :codigo
             GROUP BY a.modelo, a.nombre, a.cod_color, a.color
             HAVING SUM(sd.cantidad) > 0
             ORDER BY a.modelo, a.cod_color"
        );
        $stmt->bindValue(":codigo", $codigoServicio, PDO::PARAM_STR);
        $stmt->bindValue(":taller", $taller, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
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
     * Arma (sin guardar) el contenido de una orden: servicios libres + detalle + advertencias.
     */
    public static function mdlVistaPrevia($taller, $desde, $hasta, $pdo = null, $bloquear = false)
    {
        $pdo = $pdo ? $pdo : self::pdo();
        $servicios = self::servicios($pdo, $taller, $desde, $hasta, true, $bloquear);
        $ocupados = self::servicios($pdo, $taller, $desde, $hasta, false, false);
        $yaOrdenados = 0;
        foreach ($ocupados as $o) {
            if ($o["orden_servicio"] !== null) {
                $yaOrdenados++;
            }
        }

        $totalUni = 0;
        $totalImp = 0;
        $sinGuia = array();
        $sinPrecio = array();
        $detalle = array();
        foreach ($servicios as &$s) {
            $s["unidades"] = (int) $s["unidades"];
            if ($s["unidades"] <= 0) {
                $s["detalle"] = array();
                continue;
            }
            $s["detalle"] = self::detalleServicio($pdo, $s["codigo"], $taller);
            if (trim((string) $s["guia"]) === "") {
                $sinGuia[] = $s["codigo"];
            }
            foreach ($s["detalle"] as $d) {
                $totalUni += $d["cantidad"];
                $totalImp += $d["importe"];
                if ($d["precio_doc"] === null) {
                    $sinPrecio[$d["modelo"]] = true;
                }
            }
        }
        unset($s);

        // Solo entran servicios con prendas
        $servicios = array_values(array_filter($servicios, function ($s) {
            return $s["unidades"] > 0;
        }));

        return array(
            "servicios" => $servicios,
            "total_unidades" => $totalUni,
            "total_importe" => round($totalImp, 2),
            "ya_ordenados" => $yaOrdenados,
            "sin_guia" => $sinGuia,
            "sin_precio" => array_keys($sinPrecio),
        );
    }

    /**
     * Crea la orden dentro de una transacción. Devuelve array(ok, id|msg).
     * Vuelve a leer los servicios con FOR UPDATE para que dos usuarios no tomen los mismos.
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
            if (!count($prev["servicios"])) {
                throw new Exception("No hay servicios libres para ese taller y rango de fechas.");
            }
            if (count($prev["sin_precio"])) {
                throw new Exception("Faltan precios de servicio para los modelos: " . implode(", ", $prev["sin_precio"]) . ". Regístrelos en Precio servicio.");
            }

            $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 5) AS UNSIGNED)), 0) + 1 FROM ordenes_servicio_prodjf FOR UPDATE");
            $correlativo = (int) $stmt->fetchColumn();
            $numero = "OSP-" . str_pad($correlativo, 6, "0", STR_PAD_LEFT);

            $stmt = $pdo->prepare(
                "INSERT INTO ordenes_servicio_prodjf
                 (numero, taller, taller_nombre, taller_razon, taller_ruc, taller_direccion, taller_cta_detraccion, taller_cta_bcp, fecha_desde, fecha_hasta,
                  fecha_emision, usuario, usuario_nombre, observaciones, total_servicios, total_unidades, total_importe, estado)
                 VALUES (:numero, :taller, :tn, :tr, :truc, :tdir, :tcd, :tcb, :desde, :hasta, NOW(), :uid, :unom, :obs, :ts, :tu, :ti, 'EMITIDA')"
            );
            $direccion = trim($datos["direccion"] . " " . ($datos["dist"] ? "- " . $datos["dist"] . " - " . $datos["prov"] . " - " . $datos["dpto"] : ""));
            $stmt->execute(array(
                ":numero" => $numero,
                ":taller" => $taller,
                ":tn" => $datos["nom_sector"],
                ":tr" => $datos["razon_social"],
                ":truc" => $datos["doc"],
                ":tdir" => $direccion,
                ":tcd" => $datos["cuenta_detraccion"],
                ":tcb" => $datos["cuenta_bcp"],
                ":desde" => $desde,
                ":hasta" => $hasta,
                ":uid" => $usuarioId,
                ":unom" => $usuarioNombre,
                ":obs" => $observaciones,
                ":ts" => count($prev["servicios"]),
                ":tu" => $prev["total_unidades"],
                ":ti" => $prev["total_importe"],
            ));
            $idOrden = (int) $pdo->lastInsertId();

            $insServ = $pdo->prepare("INSERT INTO ordenes_servicio_prod_servjf (id_orden, servicio, guia, fecha_servicio, unidades) VALUES (:o, :s, :g, :f, :u)");
            $insDet = $pdo->prepare(
                "INSERT INTO ordenes_servicio_prod_detjf
                 (id_orden, servicio, modelo, nombre, cod_color, color, t1, t2, t3, t4, t5, t6, t7, t8, cantidad, precio_doc, importe)
                 VALUES (:o, :s, :m, :n, :cc, :c, :t1, :t2, :t3, :t4, :t5, :t6, :t7, :t8, :q, :p, :i)"
            );
            $marca = $pdo->prepare("UPDATE serviciosjf SET orden_servicio = :o WHERE codigo = :c AND orden_servicio IS NULL");

            foreach ($prev["servicios"] as $s) {
                $insServ->execute(array(":o" => $idOrden, ":s" => $s["codigo"], ":g" => $s["guia"], ":f" => $s["fecha"], ":u" => $s["unidades"]));
                foreach ($s["detalle"] as $d) {
                    $insDet->execute(array(
                        ":o" => $idOrden, ":s" => $s["codigo"], ":m" => $d["modelo"], ":n" => $d["nombre"],
                        ":cc" => $d["cod_color"], ":c" => $d["color"],
                        ":t1" => $d["t1"], ":t2" => $d["t2"], ":t3" => $d["t3"], ":t4" => $d["t4"],
                        ":t5" => $d["t5"], ":t6" => $d["t6"], ":t7" => $d["t7"], ":t8" => $d["t8"],
                        ":q" => $d["cantidad"], ":p" => $d["precio_doc"], ":i" => $d["importe"],
                    ));
                }
                $marca->execute(array(":o" => $idOrden, ":c" => $s["codigo"]));
                if ($marca->rowCount() !== 1) {
                    throw new Exception("El servicio " . $s["codigo"] . " ya fue tomado por otra orden. Intente de nuevo.");
                }
            }

            $pdo->commit();
            return array("ok" => true, "id" => $idOrden, "numero" => $numero);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    public static function mdlListar($desde, $hasta, $taller, $estado)
    {
        self::mdlSincronizarGuias();
        $sql = "SELECT id, numero, taller, taller_nombre, taller_ruc, fecha_desde, fecha_hasta, fecha_emision,
                    usuario_nombre, total_servicios, total_unidades, total_importe, estado,
                    (SELECT COUNT(*) FROM ordenes_servicio_prod_servjf os
                      WHERE os.id_orden = ordenes_servicio_prodjf.id AND (os.guia IS NULL OR os.guia = '')) AS guias_pendientes
                FROM ordenes_servicio_prodjf
                WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta";
        $par = array(":desde" => $desde, ":hasta" => $hasta);
        if ($taller !== "") {
            $sql .= " AND taller = :taller";
            $par[":taller"] = $taller;
        }
        if ($estado !== "") {
            $sql .= " AND estado = :estado";
            $par[":estado"] = $estado;
        }
        $sql .= " ORDER BY id DESC LIMIT 1000";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Orden completa (cabecera + servicios + detalle) para ver/imprimir. */
    public static function mdlObtener($id)
    {
        self::mdlSincronizarGuias();
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio_prodjf WHERE id = :id");
        $stmt->execute(array(":id" => (int) $id));
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio_prod_servjf WHERE id_orden = :id ORDER BY fecha_servicio, servicio");
        $stmt->execute(array(":id" => (int) $id));
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio_prod_detjf WHERE id_orden = :id ORDER BY servicio, modelo, cod_color");
        $stmt->execute(array(":id" => (int) $id));
        $det = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($servicios as &$s) {
            $s["detalle"] = array();
            foreach ($det as $d) {
                if ($d["servicio"] === $s["servicio"]) {
                    $s["detalle"][] = $d;
                }
            }
        }
        unset($s);
        $cab["servicios"] = $servicios;
        return $cab;
    }

    /** Anula la orden y libera sus servicios para poder incluirlos en otra. */
    public static function mdlAnular($id, $motivo, $usuarioNombre)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT estado FROM ordenes_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $estado = $stmt->fetchColumn();
            if ($estado === false) {
                throw new Exception("Orden no encontrada.");
            }
            if ($estado === "ANULADA") {
                throw new Exception("La orden ya está anulada.");
            }
            $stmt = $pdo->prepare("UPDATE ordenes_servicio_prodjf SET estado = 'ANULADA', anulado_por = :u, anulado_en = NOW(), motivo_anulacion = :m WHERE id = :id");
            $stmt->execute(array(":u" => $usuarioNombre, ":m" => $motivo, ":id" => (int) $id));
            $stmt = $pdo->prepare("UPDATE serviciosjf SET orden_servicio = NULL WHERE orden_servicio = :id");
            $stmt->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    /**
     * Elimina la orden por completo (cabecera, servicios y detalle) y libera sus servicios
     * para armar otra. Si era la última, su correlativo se vuelve a usar.
     */
    public static function mdlEliminar($id)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT numero FROM ordenes_servicio_prodjf WHERE id = :id FOR UPDATE");
            $stmt->execute(array(":id" => (int) $id));
            $numero = $stmt->fetchColumn();
            if ($numero === false) {
                throw new Exception("Orden no encontrada.");
            }
            $pdo->prepare("UPDATE serviciosjf SET orden_servicio = NULL WHERE orden_servicio = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM ordenes_servicio_prod_detjf WHERE id_orden = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM ordenes_servicio_prod_servjf WHERE id_orden = :id")->execute(array(":id" => (int) $id));
            $pdo->prepare("DELETE FROM ordenes_servicio_prodjf WHERE id = :id")->execute(array(":id" => (int) $id));
            $pdo->commit();
            return array("ok" => true, "numero" => $numero);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }
}
