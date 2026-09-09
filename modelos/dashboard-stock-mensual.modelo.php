<?php

require_once "conexion.php";

class ModeloDashboardStockMensual
{
    /**
     * Stock de producto terminado (cantidad) del último snapshot de cada mes,
     * solo prendas activas de las marcas del dashboard.
     */
    public static function mdlStockFinalPorMes($anio)
    {
        $anio = (int) $anio;
        $fechas = self::mdlFechasCierreMes($anio);
        if (!$fechas) {
            return array();
        }

        $cacheClave = "stock_mes_" . $anio . "_" . md5(implode("|", $fechas));
        $cache = self::mdlCacheLeer($cacheClave);
        if (is_array($cache)) {
            return $cache;
        }

        $placeholders = array();
        $params = array();
        foreach ($fechas as $i => $fecha) {
            $nombre = ":f" . $i;
            $placeholders[] = $nombre;
            $params[$nombre] = $fecha;
        }

        $sql = "SELECT MONTH(s.fecha) AS mes,
                       DATE(s.fecha) AS dia,
                       CASE
                           WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                           ELSE UPPER(TRIM(a.marca))
                       END AS marca,
                       SUM(s.cantidad) AS stock
                FROM stock_diario s
                INNER JOIN articulojf a
                  ON a.articulo = s.articulo
                WHERE s.fecha IN (" . implode(", ", $placeholders) . ")
                  AND UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO'
                  AND a.marca IN ('JACKYFORM', 'GUAPITAS', 'ROSALINDA')
                GROUP BY MONTH(s.fecha), DATE(s.fecha),
                         CASE
                             WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                             ELSE UPPER(TRIM(a.marca))
                         END
                ORDER BY mes, marca";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $filas = $filas ? $filas : array();

        self::mdlCacheGuardar($cacheClave, $filas);

        return $filas;
    }

    /**
     * Último snapshot de cada mes del año (último día, o el más reciente del mes en curso).
     */
    private static function mdlFechasCierreMes($anio)
    {
        $anio = (int) $anio;
        $desde = sprintf("%04d-01-01 00:00:00", $anio);
        $hasta = sprintf("%04d-01-01 00:00:00", $anio + 1);

        $stmt = Conexion::conectar()->prepare(
            "SELECT MAX(fecha) AS fecha
             FROM stock_diario
             WHERE fecha >= :desde AND fecha < :hasta"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$fila || empty($fila["fecha"])) {
            return array();
        }

        $ultimoSnap = $fila["fecha"];
        $mesUltimo = (int) date("n", strtotime($ultimoSnap));
        $fechas = array();
        for ($mes = 1; $mes < $mesUltimo; $mes++) {
            $fechas[] = date("Y-m-t", strtotime(sprintf("%04d-%02d-01", $anio, $mes))) . " 23:50:00";
        }
        $fechas[] = $ultimoSnap;

        return $fechas;
    }

    /**
     * Producción (E20) y ventas netas (S02/S03/S70/E05) de almacén 01
     * del año espejo, solo prendas activas de las marcas del dashboard.
     */
    public static function mdlMovimientosEspejo($anioPasado, $desde, $hasta)
    {
        $anioPasado = (int) $anioPasado;
        $tabla = "movimientosjf_" . $anioPasado;
        if (!preg_match('/^movimientosjf_\d{4}$/', $tabla)) {
            return array();
        }

        $cacheClave = "mov_espejo_" . $anioPasado . "_" . md5($desde . "|" . $hasta);
        $cache = self::mdlCacheLeer($cacheClave);
        if (is_array($cache)) {
            return $cache;
        }

        $pdo = Conexion::conectar();
        $existe = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla))->fetch(PDO::FETCH_NUM);
        if (!$existe) {
            return array();
        }

        $stmt = $pdo->prepare(
            "SELECT MONTH(m.fecha) AS mes,
                    CASE
                        WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                        ELSE UPPER(TRIM(a.marca))
                    END AS marca,
                    SUM(CASE WHEN m.tipo = 'E20' THEN m.cantidad ELSE 0 END) AS produccion,
                    SUM(CASE WHEN m.tipo IN ('S02', 'S03', 'S70', 'E05') THEN m.cantidad ELSE 0 END) AS ventas
             FROM {$tabla} m
             INNER JOIN articulojf a
               ON a.articulo = m.articulo
             WHERE m.fecha >= :desde
               AND m.fecha <= :hasta
               AND m.almacen = '01'
               AND m.tipo IN ('E20', 'S02', 'S03', 'S70', 'E05')
               AND UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO'
               AND a.marca IN ('JACKYFORM', 'GUAPITAS', 'ROSALINDA')
             GROUP BY MONTH(m.fecha),
                      CASE
                          WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                          ELSE UPPER(TRIM(a.marca))
                      END
             ORDER BY mes, marca"
        );
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $filas = $filas ? $filas : array();

        self::mdlCacheGuardar($cacheClave, $filas);

        return $filas;
    }

    /**
     * En proceso hoy: corte + taller + servicios (sin órdenes de corte).
     */
    public static function mdlProcesoActualPorMarca()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT CASE
                        WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                        ELSE UPPER(TRIM(a.marca))
                    END AS marca,
                    COALESCE(SUM(GREATEST(IFNULL(a.alm_corte, 0), 0)), 0)
                    + COALESCE(SUM(GREATEST(IFNULL(a.taller, 0), 0)), 0)
                    + COALESCE(SUM(GREATEST(IFNULL(a.servicio, 0), 0)), 0) AS proceso
             FROM articulojf a
             WHERE UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO'
               AND a.marca IN ('JACKYFORM', 'GUAPITAS', 'ROSALINDA')
             GROUP BY CASE
                          WHEN UPPER(TRIM(a.marca)) = 'GUAPITAS' THEN 'JACKYFORM'
                          ELSE UPPER(TRIM(a.marca))
                      END"
        );
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $porMarca = array();
        foreach ($filas as $fila) {
            $porMarca[strtoupper(trim($fila["marca"]))] = (int) round((float) $fila["proceso"]);
        }

        return $porMarca;
    }

    private static function mdlCacheRuta($clave)
    {
        $nombre = preg_replace("/[^a-zA-Z0-9_]/", "", $clave);
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "vascorp_" . $nombre . ".json";
    }

    private static function mdlCacheLeer($clave)
    {
        $ruta = self::mdlCacheRuta($clave);
        if (!is_file($ruta)) {
            return null;
        }
        $raw = file_get_contents($ruta);
        if ($raw === false || $raw === "") {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function mdlCacheGuardar($clave, $data)
    {
        @file_put_contents(self::mdlCacheRuta($clave), json_encode($data), LOCK_EX);
    }
}
