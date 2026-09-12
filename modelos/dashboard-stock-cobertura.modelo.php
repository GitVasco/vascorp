<?php

require_once "conexion.php";

class ModeloDashboardStockCobertura
{
    /**
     * Artículos activos con venta neta en la ventana, más stock, proceso y pedidos.
     */
    public static function mdlArticulosConVenta($desde, $hasta)
    {
        $fuente = self::mdlSqlMovimientos($desde, $hasta);
        if ($fuente === null) {
            return array();
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT CASE
                        WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                        ELSE UPPER(TRIM(a.marca))
                    END AS marca,
                    a.articulo,
                    GREATEST(IFNULL(a.stock, 0), 0) AS stock,
                    GREATEST(IFNULL(a.alm_corte, 0), 0)
                        + GREATEST(IFNULL(a.taller, 0), 0)
                        + GREATEST(IFNULL(a.servicio, 0), 0) AS proceso,
                    GREATEST(IFNULL(a.pedidos, 0), 0) AS comprometido,
                    COALESCE(v.ventas, 0) AS ventas
             FROM articulojf a
             INNER JOIN (
                    SELECT m.articulo,
                           SUM(IFNULL(m.cantidad, 0)) AS ventas
                    FROM {$fuente} m
                    WHERE DATE(m.fecha) >= :desde
                      AND DATE(m.fecha) <= :hasta
                      AND m.almacen = '01'
                      AND m.tipo IN ('S02', 'S03', 'S70', 'E05')
                    GROUP BY m.articulo
                    HAVING SUM(IFNULL(m.cantidad, 0)) > 0
             ) v ON v.articulo = a.articulo
             WHERE UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO'
               AND a.marca IN ('JACKYFORM', 'GUAPITAS', 'ROSALINDA')"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $filas ? $filas : array();
    }

    private static function mdlSqlMovimientos($desde, $hasta)
    {
        $anioIni = (int) substr((string) $desde, 0, 4);
        $anioFin = (int) substr((string) $hasta, 0, 4);
        if ($anioIni < 2000 || $anioFin > 2100 || $anioIni > $anioFin) {
            return null;
        }

        $partes = array();
        for ($anio = $anioIni; $anio <= $anioFin; $anio++) {
            $tabla = "movimientosjf_" . $anio;
            if (!self::mdlExisteTabla($tabla)) {
                continue;
            }
            $partes[] = "SELECT articulo, cantidad, fecha, tipo, almacen FROM {$tabla}";
        }

        if (!$partes) {
            return null;
        }

        return "(" . implode(" UNION ALL ", $partes) . ")";
    }

    private static function mdlExisteTabla($tabla)
    {
        if (!preg_match('/^movimientosjf_[0-9]{4}$/', $tabla)) {
            return false;
        }
        $pdo = Conexion::conectar();
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
        $ok = $stmt && $stmt->fetch();
        if ($stmt) {
            $stmt->closeCursor();
        }
        return (bool) $ok;
    }
}
