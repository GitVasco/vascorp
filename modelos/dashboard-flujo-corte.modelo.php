<?php

require_once "conexion.php";

class ModeloDashboardFlujoCorte
{
    /**
     * Stock vivo en artículos: OC pendiente, almacén de corte, taller y servicio.
     */
    public static function mdlStocksActuales()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                COALESCE(SUM(GREATEST(ord_corte, 0)), 0) AS en_oc,
                COALESCE(SUM(GREATEST(alm_corte, 0)), 0) AS en_corte,
                COALESCE(SUM(GREATEST(taller, 0)), 0) AS en_taller,
                COALESCE(SUM(GREATEST(servicio, 0)), 0) AS en_servicio
             FROM articulojf"
        );
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $fila ? $fila : array(
            "en_oc" => 0,
            "en_corte" => 0,
            "en_taller" => 0,
            "en_servicio" => 0,
        );
    }

    /**
     * Cabeceras de OC aún abiertas (Pendiente / Parcial).
     */
    public static function mdlResumenOcAbiertas()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                COUNT(*) AS ordenes,
                COALESCE(SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END), 0) AS pendientes,
                COALESCE(SUM(CASE WHEN estado = 'Parcial' THEN 1 ELSE 0 END), 0) AS parciales,
                COALESCE(SUM(saldo), 0) AS saldo_unidades
             FROM ordencortejf
             WHERE estado <> 'Cerrado'"
        );
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $fila ? $fila : array(
            "ordenes" => 0,
            "pendientes" => 0,
            "parciales" => 0,
            "saldo_unidades" => 0,
        );
    }

    /**
     * Movimiento del período: programado (OC nuevas), cortado y envíos.
     */
    public static function mdlMovimientoPeriodo($desde, $hasta)
    {
        $pdo = Conexion::conectar();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) AS programado
             FROM ordencortejf
             WHERE DATE(fecha) BETWEEN :desde AND :hasta"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $programado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(cantidad), 0) AS cortado
             FROM almacencorte_detallejf
             WHERE DATE(fecha) BETWEEN :desde AND :hasta"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $cortado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE
                    WHEN e.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL)
                    THEN e.cantidad ELSE 0 END), 0) AS enviado_taller,
                COALESCE(SUM(CASE
                    WHEN NOT (e.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL))
                    THEN e.cantidad ELSE 0 END), 0) AS enviado_servicio
             FROM entaller_cabjf e
             LEFT JOIN sectorjf s ON e.taller = s.cod_sector
             WHERE DATE(e.fecha) BETWEEN :desde AND :hasta"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $envio = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $prod = self::mdlProduccionPeriodo($desde, $hasta);

        return array(
            "programado" => $programado ? (float) $programado["programado"] : 0,
            "cortado" => $cortado ? (float) $cortado["cortado"] : 0,
            "enviado_taller" => $envio ? (float) $envio["enviado_taller"] : 0,
            "enviado_servicio" => $envio ? (float) $envio["enviado_servicio"] : 0,
            "prod_taller" => $prod["prod_taller"],
            "prod_servicio" => $prod["prod_servicio"],
        );
    }

    /**
     * Series diarias del período (programado, cortado, enviado, producción).
     */
    public static function mdlDiarioPeriodo($desde, $hasta)
    {
        $pdo = Conexion::conectar();
        $porDia = array();

        $stmt = $pdo->prepare(
            "SELECT DATE(fecha) AS dia, COALESCE(SUM(total), 0) AS programado
             FROM ordencortejf
             WHERE DATE(fecha) BETWEEN :desde AND :hasta
             GROUP BY DATE(fecha)"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["programado"] = (float) $fila["programado"];
        }
        $stmt->closeCursor();

        $stmt = $pdo->prepare(
            "SELECT DATE(fecha) AS dia, COALESCE(SUM(cantidad), 0) AS cortado
             FROM almacencorte_detallejf
             WHERE DATE(fecha) BETWEEN :desde AND :hasta
             GROUP BY DATE(fecha)"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["cortado"] = (float) $fila["cortado"];
        }
        $stmt->closeCursor();

        $stmt = $pdo->prepare(
            "SELECT DATE(e.fecha) AS dia,
                COALESCE(SUM(CASE
                    WHEN e.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL)
                    THEN e.cantidad ELSE 0 END), 0) AS enviado_taller,
                COALESCE(SUM(CASE
                    WHEN NOT (e.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL))
                    THEN e.cantidad ELSE 0 END), 0) AS enviado_servicio
             FROM entaller_cabjf e
             LEFT JOIN sectorjf s ON e.taller = s.cod_sector
             WHERE DATE(e.fecha) BETWEEN :desde AND :hasta
             GROUP BY DATE(e.fecha)"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["enviado_taller"] = (float) $fila["enviado_taller"];
            $porDia[$dia]["enviado_servicio"] = (float) $fila["enviado_servicio"];
        }
        $stmt->closeCursor();

        foreach (self::mdlDiarioProduccion($desde, $hasta) as $dia => $prod) {
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["prod_taller"] = $prod["prod_taller"];
            $porDia[$dia]["prod_servicio"] = $prod["prod_servicio"];
        }

        return $porDia;
    }

    private static function mdlTablaMovimientos($desde)
    {
        $anio = (int) substr((string) $desde, 0, 4);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) date("Y");
        }
        return "movimientosjf_" . $anio;
    }

    private static function mdlExisteTabla($tabla)
    {
        if (!preg_match('/^movimientosjf_[0-9]{4}$/', $tabla)) {
            return false;
        }
        $stmt = Conexion::conectar()->query("SHOW TABLES LIKE " . Conexion::conectar()->quote($tabla));
        $ok = $stmt && $stmt->fetch();
        if ($stmt) {
            $stmt->closeCursor();
        }
        return (bool) $ok;
    }

    private static function mdlProduccionPeriodo($desde, $hasta)
    {
        $vacio = array("prod_taller" => 0, "prod_servicio" => 0);
        $tabla = self::mdlTablaMovimientos($desde);
        if (!self::mdlExisteTabla($tabla)) {
            return $vacio;
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT
                COALESCE(SUM(CASE
                    WHEN m.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL)
                    THEN m.cantidad ELSE 0 END), 0) AS prod_taller,
                COALESCE(SUM(CASE
                    WHEN NOT (m.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL))
                    THEN m.cantidad ELSE 0 END), 0) AS prod_servicio
             FROM {$tabla} m
             LEFT JOIN sectorjf s ON m.taller = s.cod_sector
             WHERE m.tipo = 'E20'
               AND DATE(m.fecha) BETWEEN :desde AND :hasta"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return array(
            "prod_taller" => $fila ? (float) $fila["prod_taller"] : 0,
            "prod_servicio" => $fila ? (float) $fila["prod_servicio"] : 0,
        );
    }

    private static function mdlDiarioProduccion($desde, $hasta)
    {
        $tabla = self::mdlTablaMovimientos($desde);
        if (!self::mdlExisteTabla($tabla)) {
            return array();
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT DATE(m.fecha) AS dia,
                COALESCE(SUM(CASE
                    WHEN m.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL)
                    THEN m.cantidad ELSE 0 END), 0) AS prod_taller,
                COALESCE(SUM(CASE
                    WHEN NOT (m.taller = 'VC' OR s.tipo = 0 OR (s.cod_sector IS NOT NULL AND s.tipo IS NULL))
                    THEN m.cantidad ELSE 0 END), 0) AS prod_servicio
             FROM {$tabla} m
             LEFT JOIN sectorjf s ON m.taller = s.cod_sector
             WHERE m.tipo = 'E20'
               AND DATE(m.fecha) BETWEEN :desde AND :hasta
             GROUP BY DATE(m.fecha)"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $porDia = array();
        foreach ($filas as $fila) {
            $porDia[$fila["dia"]] = array(
                "prod_taller" => (float) $fila["prod_taller"],
                "prod_servicio" => (float) $fila["prod_servicio"],
            );
        }
        return $porDia;
    }

    private static function mdlFilaDiariaVacia()
    {
        return array(
            "programado" => 0,
            "cortado" => 0,
            "enviado_taller" => 0,
            "enviado_servicio" => 0,
            "prod_taller" => 0,
            "prod_servicio" => 0,
        );
    }
}
