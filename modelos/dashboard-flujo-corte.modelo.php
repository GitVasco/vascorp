<?php

require_once "conexion.php";

class ModeloDashboardFlujoCorte
{
    /**
     * Stock vivo en artículos: OC pendiente, almacén de corte, taller y servicio.
     * $filtro = null | array("modo" => "nuevo"|"antiguo", "modelos" => array(...))
     */
    public static function mdlStocksActuales($filtro = null)
    {
        $params = array();
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);

        $stmt = Conexion::conectar()->prepare(
            "SELECT
                COALESCE(SUM(GREATEST(a.ord_corte, 0)), 0) AS en_oc,
                COALESCE(SUM(GREATEST(a.alm_corte, 0)), 0) AS en_corte,
                COALESCE(SUM(GREATEST(a.taller, 0)), 0) AS en_taller,
                COALESCE(SUM(GREATEST(a.servicio, 0)), 0) AS en_servicio
             FROM articulojf a
             WHERE 1 = 1 {$cond}"
        );
        $stmt->execute($params);
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
     * Modelos en primera producción: presentes en el flujo de corte (stock vivo o
     * movimiento desde $desde hasta $hasta) y sin producción terminada (E20) antes de $desde.
     */
    public static function mdlModelosPrimeraProduccion($desde, $hasta)
    {
        $candidatos = self::mdlModelosEnFlujo($desde, $hasta);
        if (!$candidatos) {
            return array();
        }

        $conProduccion = array();
        $anioDesde = (int) substr((string) $desde, 0, 4);
        foreach (self::mdlTablasMovimientos() as $anio => $tabla) {
            if ($anio > $anioDesde) {
                continue;
            }
            $pendientes = array_values(array_diff($candidatos, array_keys($conProduccion)));
            if (!$pendientes) {
                break;
            }

            $params = array();
            $cond = self::mdlCondModelo("a.modelo", array("modo" => "nuevo", "modelos" => $pendientes), $params);
            $condFecha = "";
            if ($anio === $anioDesde) {
                $condFecha = " AND m.fecha < :desde";
                $params[":desde"] = $desde;
            }

            $stmt = Conexion::conectar()->prepare(
                "SELECT DISTINCT a.modelo
                 FROM {$tabla} m
                 INNER JOIN articulojf a ON a.articulo = m.articulo
                 WHERE m.tipo = 'E20' {$condFecha} {$cond}"
            );
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $modelo) {
                $conProduccion[(string) $modelo] = true;
            }
            $stmt->closeCursor();
        }

        return array_values(array_diff($candidatos, array_keys($conProduccion)));
    }

    private static function mdlModelosEnFlujo($desde, $hasta)
    {
        $sql = "SELECT modelo FROM articulojf
                WHERE COALESCE(modelo, '') <> ''
                  AND (ord_corte > 0 OR alm_corte > 0 OR taller > 0 OR servicio > 0)
            UNION
            SELECT a.modelo
                FROM detalles_ordencortejf d
                INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
                INNER JOIN articulojf a ON a.articulo = d.articulo
                WHERE DATE(o.fecha) BETWEEN :d1 AND :h1
            UNION
            SELECT a.modelo
                FROM almacencorte_detallejf acd
                INNER JOIN articulojf a ON a.articulo = acd.articulo
                WHERE DATE(acd.fecha) BETWEEN :d2 AND :h2
            UNION
            SELECT a.modelo
                FROM entaller_cabjf e
                INNER JOIN articulojf a ON a.articulo = e.articulo
                WHERE DATE(e.fecha) BETWEEN :d3 AND :h3";
        $params = array(
            ":d1" => $desde, ":h1" => $hasta,
            ":d2" => $desde, ":h2" => $hasta,
            ":d3" => $desde, ":h3" => $hasta,
        );

        $tabla = self::mdlTablaMovimientos($desde);
        if (self::mdlExisteTabla($tabla)) {
            $sql .= "
            UNION
            SELECT a.modelo
                FROM {$tabla} m
                INNER JOIN articulojf a ON a.articulo = m.articulo
                WHERE m.tipo = 'E20' AND DATE(m.fecha) BETWEEN :d4 AND :h4";
            $params[":d4"] = $desde;
            $params[":h4"] = $hasta;
        }

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($params);
        $modelos = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $modelo) {
            $modelo = (string) $modelo;
            if ($modelo !== "") {
                $modelos[$modelo] = true;
            }
        }
        $stmt->closeCursor();

        return array_keys($modelos);
    }

    /**
     * Tablas movimientosjf_AAAA existentes, ordenadas de la más reciente a la más antigua.
     */
    private static function mdlTablasMovimientos()
    {
        $stmt = Conexion::conectar()->query("SHOW TABLES LIKE 'movimientosjf\\_%'");
        $tablas = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
            if (preg_match('/^movimientosjf_([0-9]{4})$/', $tabla, $m)) {
                $tablas[(int) $m[1]] = $tabla;
            }
        }
        $stmt->closeCursor();
        krsort($tablas);
        return $tablas;
    }

    /**
     * Movimiento del período: programado (OC nuevas), cortado y envíos.
     */
    public static function mdlMovimientoPeriodo($desde, $hasta, $filtro = null)
    {
        $pdo = Conexion::conectar();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        if ($filtro) {
            $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(d.cantidad), 0) AS programado
                 FROM detalles_ordencortejf d
                 INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
                 LEFT JOIN articulojf a ON a.articulo = d.articulo
                 WHERE DATE(o.fecha) BETWEEN :desde AND :hasta {$cond}"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(total), 0) AS programado
                 FROM ordencortejf
                 WHERE DATE(fecha) BETWEEN :desde AND :hasta"
            );
        }
        $stmt->execute($params);
        $programado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(acd.cantidad), 0) AS cortado
             FROM almacencorte_detallejf acd
             LEFT JOIN articulojf a ON a.articulo = acd.articulo
             WHERE DATE(acd.fecha) BETWEEN :desde AND :hasta {$cond}"
        );
        $stmt->execute($params);
        $cortado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
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
             LEFT JOIN articulojf a ON a.articulo = e.articulo
             WHERE DATE(e.fecha) BETWEEN :desde AND :hasta {$cond}"
        );
        $stmt->execute($params);
        $envio = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $prod = self::mdlProduccionPeriodo($desde, $hasta, $filtro);

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
    public static function mdlDiarioPeriodo($desde, $hasta, $filtro = null)
    {
        $pdo = Conexion::conectar();
        $porDia = array();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        if ($filtro) {
            $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
            $stmt = $pdo->prepare(
                "SELECT DATE(o.fecha) AS dia, COALESCE(SUM(d.cantidad), 0) AS programado
                 FROM detalles_ordencortejf d
                 INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
                 LEFT JOIN articulojf a ON a.articulo = d.articulo
                 WHERE DATE(o.fecha) BETWEEN :desde AND :hasta {$cond}
                 GROUP BY DATE(o.fecha)"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT DATE(fecha) AS dia, COALESCE(SUM(total), 0) AS programado
                 FROM ordencortejf
                 WHERE DATE(fecha) BETWEEN :desde AND :hasta
                 GROUP BY DATE(fecha)"
            );
        }
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["programado"] = (float) $fila["programado"];
        }
        $stmt->closeCursor();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
        $stmt = $pdo->prepare(
            "SELECT DATE(acd.fecha) AS dia, COALESCE(SUM(acd.cantidad), 0) AS cortado
             FROM almacencorte_detallejf acd
             LEFT JOIN articulojf a ON a.articulo = acd.articulo
             WHERE DATE(acd.fecha) BETWEEN :desde AND :hasta {$cond}
             GROUP BY DATE(acd.fecha)"
        );
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["cortado"] = (float) $fila["cortado"];
        }
        $stmt->closeCursor();

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
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
             LEFT JOIN articulojf a ON a.articulo = e.articulo
             WHERE DATE(e.fecha) BETWEEN :desde AND :hasta {$cond}
             GROUP BY DATE(e.fecha)"
        );
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $dia = $fila["dia"];
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["enviado_taller"] = (float) $fila["enviado_taller"];
            $porDia[$dia]["enviado_servicio"] = (float) $fila["enviado_servicio"];
        }
        $stmt->closeCursor();

        foreach (self::mdlDiarioProduccion($desde, $hasta, $filtro) as $dia => $prod) {
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = self::mdlFilaDiariaVacia();
            }
            $porDia[$dia]["prod_taller"] = $prod["prod_taller"];
            $porDia[$dia]["prod_servicio"] = $prod["prod_servicio"];
        }

        return $porDia;
    }

    /**
     * Condición SQL para limitar a (modo "nuevo") o excluir (modo "antiguo") la lista de modelos.
     */
    private static function mdlCondModelo($columna, $filtro, &$params)
    {
        if (!$filtro) {
            return "";
        }
        $modelos = array_values(isset($filtro["modelos"]) ? $filtro["modelos"] : array());
        $esNuevo = isset($filtro["modo"]) && $filtro["modo"] === "nuevo";
        if (!$modelos) {
            return $esNuevo ? " AND 1 = 0" : "";
        }

        $marcas = array();
        foreach ($modelos as $i => $modelo) {
            $clave = ":fmod" . $i;
            $marcas[] = $clave;
            $params[$clave] = (string) $modelo;
        }
        return " AND " . $columna . ($esNuevo ? " IN (" : " NOT IN (") . implode(", ", $marcas) . ")";
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

    private static function mdlProduccionPeriodo($desde, $hasta, $filtro = null)
    {
        $vacio = array("prod_taller" => 0, "prod_servicio" => 0);
        $tabla = self::mdlTablaMovimientos($desde);
        if (!self::mdlExisteTabla($tabla)) {
            return $vacio;
        }

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
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
             LEFT JOIN articulojf a ON a.articulo = m.articulo
             WHERE m.tipo = 'E20'
               AND DATE(m.fecha) BETWEEN :desde AND :hasta {$cond}"
        );
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return array(
            "prod_taller" => $fila ? (float) $fila["prod_taller"] : 0,
            "prod_servicio" => $fila ? (float) $fila["prod_servicio"] : 0,
        );
    }

    private static function mdlDiarioProduccion($desde, $hasta, $filtro = null)
    {
        $tabla = self::mdlTablaMovimientos($desde);
        if (!self::mdlExisteTabla($tabla)) {
            return array();
        }

        $params = array(":desde" => $desde, ":hasta" => $hasta);
        $cond = self::mdlCondModelo("COALESCE(a.modelo, '')", $filtro, $params);
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
             LEFT JOIN articulojf a ON a.articulo = m.articulo
             WHERE m.tipo = 'E20'
               AND DATE(m.fecha) BETWEEN :desde AND :hasta {$cond}
             GROUP BY DATE(m.fecha)"
        );
        $stmt->execute($params);
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
