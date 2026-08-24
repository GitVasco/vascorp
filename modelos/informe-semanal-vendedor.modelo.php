<?php

require_once "conexion.php";
require_once dirname(__FILE__) . "/../controladores/metas-retos.config.php";
require_once dirname(__FILE__) . "/../controladores/dashboard-cxc.config.php";

class ModeloInformeSemanalVendedor
{
    private static function sqlTiposVenta($alias = "v")
    {
        return "{$alias}.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')";
    }

    private static function sqlVentaValida($alias = "v")
    {
        return "UPPER(IFNULL({$alias}.estado, '')) <> 'ANULADO' AND " . self::sqlTiposVenta($alias);
    }

    private static function sqlCobranzaEfectivo($alias = "cc")
    {
        return mrSqlInCodigosCobranzaEfectiva($alias);
    }

    /**
     * Cobranzas históricas que se suman al vendedor actual (solo este informe).
     * Ventas no se remapean.
     */
    private static function predecesoresCobranza()
    {
        return array(
            "31" => array("00"),
            "33" => array("24", "32"),
            "30" => array("26", "26A"),
        );
    }

    private static function codigosCobranza($vendedor)
    {
        $vendedor = trim((string) $vendedor);
        $codigos = array($vendedor);
        $mapa = self::predecesoresCobranza();
        if (isset($mapa[$vendedor])) {
            foreach ($mapa[$vendedor] as $antiguo) {
                $codigos[] = trim((string) $antiguo);
            }
        }
        return array_values(array_unique($codigos));
    }

    private static function sqlInVendedoresCobranza($alias, array $codigos)
    {
        $partes = array();
        foreach ($codigos as $codigo) {
            $codigo = trim((string) $codigo);
            if ($codigo === "") {
                continue;
            }
            $partes[] = "'" . str_replace("'", "''", $codigo) . "'";
        }
        if (count($partes) === 0) {
            return "1=0";
        }
        return "TRIM(IFNULL({$alias}.vendedor, '')) IN (" . implode(", ", $partes) . ")";
    }

    private static function finExclusivo($hastaInclusive)
    {
        return date("Y-m-d", strtotime($hastaInclusive . " +1 day"));
    }

    public static function mdlVendedoresFiltro()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT codigo, descripcion
             FROM maestrajf
             WHERE UPPER(tipo_dato) = 'TVEND'
               AND estado_decisiones = 1
             ORDER BY codigo ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlZonaVendedor($codigo)
    {
        $codigo = trim((string) $codigo);
        if ($codigo === "" || !class_exists("ModeloZonasComerciales")) {
            return "—";
        }
        $zonas = ModeloZonasComerciales::mdlZonasPorVendedor($codigo);
        if (!is_array($zonas) || count($zonas) === 0) {
            return "—";
        }
        $nombres = array();
        foreach ($zonas as $z) {
            if (!empty($z["nombre"])) {
                $nombres[] = $z["nombre"];
            }
        }
        return count($nombres) ? implode(" / ", $nombres) : "—";
    }

    public static function mdlInforme(array $filtros)
    {
        $vendedor = $filtros["vendedor"];
        $lunes = $filtros["lunes"];
        $domingo = $filtros["domingo"];
        $lunesAnt = $filtros["lunes_ant"];
        $domingoAnt = $filtros["domingo_ant"];

        $kpis = self::mdlKpisPeriodo($vendedor, $lunes, $domingo);
        $kpisAnt = self::mdlKpisPeriodo($vendedor, $lunesAnt, $domingoAnt);
        $kpis["clientes_cartera"] = self::mdlClientesCartera($vendedor);

        $promedio4 = self::mdlPromedio4Semanas($vendedor, $lunes);
        $kpis["promedio_4"] = $promedio4;
        $kpis["variacion_promedio"] = $promedio4 > 0
            ? round((($kpis["venta"] - $promedio4) / $promedio4) * 100, 1)
            : ($kpis["venta"] > 0 ? 100.0 : 0.0);

        return array(
            "kpis" => $kpis,
            "kpis_ant" => $kpisAnt,
            "diario" => self::mdlVentasDiariasComparativo($vendedor, $lunes, $lunesAnt),
            "diario_cobranza" => self::mdlCobranzasDiariasComparativo($vendedor, $lunes, $lunesAnt),
            "top_clientes" => self::mdlTopClientes($vendedor, $lunes, $domingo, 5),
            "cartera" => self::mdlCartera($vendedor, $domingo),
            "por_facturar" => self::mdlPorFacturar($vendedor, $lunes, $domingo),
        );
    }

    private static function mdlKpisPeriodo($vendedor, $desde, $hastaInclusive)
    {
        $fin = self::finExclusivo($hastaInclusive);
        $sql = "SELECT
                COALESCE(SUM(v.neto), 0) AS venta,
                COUNT(DISTINCT v.documento) AS pedidos,
                COUNT(DISTINCT v.cliente) AS clientes_compra
            FROM ventajf v
            WHERE " . self::sqlVentaValida("v") . "
              AND TRIM(v.vendedor) = :vendedor
              AND v.fecha >= :ini AND v.fecha < :fin";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->bindValue(":ini", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return array(
            "venta" => (float) $fila["venta"],
            "pedidos" => (int) $fila["pedidos"],
            "clientes_compra" => (int) $fila["clientes_compra"],
            "nuevos" => self::mdlNuevosClientes($vendedor, $desde, $hastaInclusive),
            "cobranza" => self::mdlCobranza($vendedor, $desde, $hastaInclusive),
        );
    }

    private static function mdlNuevosClientes($vendedor, $desde, $hastaInclusive)
    {
        $fin = self::finExclusivo($hastaInclusive);
        $sql = "SELECT COUNT(DISTINCT v.cliente) AS n
            FROM ventajf v
            WHERE " . self::sqlVentaValida("v") . "
              AND TRIM(v.vendedor) = :vendedor
              AND v.fecha >= :ini AND v.fecha < :fin
              AND NOT EXISTS (
                  SELECT 1 FROM ventajf v2
                  WHERE " . self::sqlVentaValida("v2") . "
                    AND v2.cliente = v.cliente
                    AND v2.fecha < :ini2
              )";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->bindValue(":ini", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindValue(":ini2", $desde, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $fila["n"];
    }

    private static function mdlCobranza($vendedor, $desde, $hastaInclusive)
    {
        $fin = self::finExclusivo($hastaInclusive);
        $sql = "SELECT COALESCE(SUM(cc.monto), 0) AS cobranza
            FROM cuenta_ctejf cc
            WHERE cc.tip_mov = '-'
              AND cc.fecha >= :fecha_ini
              AND cc.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo("cc") . "
              AND " . self::sqlInVendedoresCobranza("cc", self::codigosCobranza($vendedor));
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha_ini", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fecha_fin", $fin, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $bruto = $fila ? (float) $fila["cobranza"] : 0.0;
        return ModeloDashboardGerencial::sinIgv($bruto);
    }

    private static function mdlClientesCartera($vendedor)
    {
        $sql = "SELECT COUNT(*) AS n
            FROM clientesjf
            WHERE TRIM(IFNULL(vendedor, '')) = :vendedor
              AND (estado = 1 OR estado = '1')";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $fila["n"];
    }

    private static function mdlPromedio4Semanas($vendedor, $lunesActual)
    {
        $sum = 0.0;
        $lunes = new DateTime($lunesActual);
        for ($i = 0; $i < 4; $i++) {
            $ini = clone $lunes;
            $ini->modify("-" . ($i * 7) . " days");
            $finInc = clone $ini;
            $finInc->modify("+6 days");
            $k = self::mdlKpisPeriodo($vendedor, $ini->format("Y-m-d"), $finInc->format("Y-m-d"));
            $sum += $k["venta"];
        }
        return round($sum / 4, 2);
    }

    private static function mdlVentasDiariasComparativo($vendedor, $lunes, $lunesAnt)
    {
        $labels = array("Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom");
        $act = self::mdlVentasPorDia($vendedor, $lunes, 7);
        $ant = self::mdlVentasPorDia($vendedor, $lunesAnt, 7);
        return array(
            "labels" => $labels,
            "actual" => $act,
            "anterior" => $ant,
        );
    }

    private static function mdlVentasPorDia($vendedor, $lunes, $dias)
    {
        $ini = $lunes;
        $fin = date("Y-m-d", strtotime($lunes . " +" . $dias . " days"));
        $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m-%d') AS dia, COALESCE(SUM(v.neto), 0) AS venta
            FROM ventajf v
            WHERE " . self::sqlVentaValida("v") . "
              AND TRIM(v.vendedor) = :vendedor
              AND v.fecha >= :ini AND v.fecha < :fin
            GROUP BY DATE_FORMAT(v.fecha, '%Y-%m-%d')";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->bindValue(":ini", $ini, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
        $stmt->execute();
        $map = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $clave = substr(trim((string) $fila["dia"]), 0, 10);
            $map[$clave] = (float) $fila["venta"];
        }
        $out = array();
        $cursor = new DateTime($lunes);
        for ($i = 0; $i < $dias; $i++) {
            $clave = $cursor->format("Y-m-d");
            $out[] = isset($map[$clave]) ? $map[$clave] : 0.0;
            $cursor->modify("+1 day");
        }
        return $out;
    }

    private static function mdlCobranzasDiariasComparativo($vendedor, $lunes, $lunesAnt)
    {
        $labels = array("Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom");
        return array(
            "labels" => $labels,
            "actual" => self::mdlCobranzasPorDia($vendedor, $lunes, 7),
            "anterior" => self::mdlCobranzasPorDia($vendedor, $lunesAnt, 7),
        );
    }

    private static function mdlCobranzasPorDia($vendedor, $lunes, $dias)
    {
        $ini = $lunes;
        $fin = date("Y-m-d", strtotime($lunes . " +" . $dias . " days"));
        $sql = "SELECT DATE_FORMAT(cc.fecha, '%Y-%m-%d') AS dia, COALESCE(SUM(cc.monto), 0) AS cobranza
            FROM cuenta_ctejf cc
            WHERE cc.tip_mov = '-'
              AND cc.fecha >= :ini AND cc.fecha < :fin
              AND " . self::sqlCobranzaEfectivo("cc") . "
              AND " . self::sqlInVendedoresCobranza("cc", self::codigosCobranza($vendedor)) . "
            GROUP BY DATE_FORMAT(cc.fecha, '%Y-%m-%d')";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":ini", $ini, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
        $stmt->execute();
        $map = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $clave = substr(trim((string) $fila["dia"]), 0, 10);
            $map[$clave] = ModeloDashboardGerencial::sinIgv($fila["cobranza"]);
        }
        $out = array();
        $cursor = new DateTime($lunes);
        for ($i = 0; $i < $dias; $i++) {
            $clave = $cursor->format("Y-m-d");
            $out[] = isset($map[$clave]) ? $map[$clave] : 0.0;
            $cursor->modify("+1 day");
        }
        return $out;
    }

    private static function mdlTopClientes($vendedor, $desde, $hastaInclusive, $limite)
    {
        $limite = max(1, min(10, (int) $limite));
        $fin = self::finExclusivo($hastaInclusive);
        $sql = "SELECT
                t.codigo,
                COALESCE(
                    (
                        SELECT NULLIF(TRIM(c.nombre), '')
                        FROM clientesjf c
                        WHERE TRIM(c.codigo) = t.codigo
                        LIMIT 1
                    ),
                    t.codigo
                ) AS nombre,
                t.venta
            FROM (
                SELECT
                    TRIM(v.cliente) AS codigo,
                    COALESCE(SUM(v.neto), 0) AS venta
                FROM ventajf v
                WHERE " . self::sqlVentaValida("v") . "
                  AND TRIM(v.vendedor) = :vendedor
                  AND v.fecha >= :ini AND v.fecha < :fin
                GROUP BY TRIM(v.cliente)
                ORDER BY venta DESC
                LIMIT {$limite}
            ) t";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->bindValue(":ini", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
        $stmt->execute();
        $filas = array();
        $n = 1;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $filas[] = array(
                "puesto" => $n,
                "codigo" => $fila["codigo"],
                "nombre" => $fila["nombre"],
                "venta" => (float) $fila["venta"],
            );
            $n++;
        }
        return $filas;
    }

    private static function mdlCartera($vendedor, $fechaCorte)
    {
        $filtros = array(
            "anio" => (int) substr($fechaCorte, 0, 4),
            "mes" => (int) substr($fechaCorte, 5, 2),
            "fecha_corte" => $fechaCorte,
            "vendedor" => $vendedor,
            "cliente" => "",
            "zona" => 0,
            "todos_vendedores" => true,
        );
        $fila = ModeloDashboardCxc::mdlAntiguedad($filtros, false);
        if (!$fila) {
            $fila = array();
        }

        $porVencer = (float) (isset($fila["por_vencer"]) ? $fila["por_vencer"] : 0);
        $r0 = (float) (isset($fila["rango_0_30"]) ? $fila["rango_0_30"] : 0);
        $r31 = (float) (isset($fila["rango_31_60"]) ? $fila["rango_31_60"] : 0);
        $r61 = (float) (isset($fila["rango_61_90"]) ? $fila["rango_61_90"] : 0);
        $r91 = (float) (isset($fila["rango_91_180"]) ? $fila["rango_91_180"] : 0);
        $r180 = (float) (isset($fila["rango_180_mas"]) ? $fila["rango_180_mas"] : 0);
        $mas90 = $r91 + $r180;
        $mas30 = $r31 + $r61 + $mas90;
        $total = $porVencer + $r0 + $r31 + $r61 + $mas90;

        $pct = function ($m) use ($total) {
            return $total > 0 ? round(($m / $total) * 100, 1) : 0.0;
        };

        $tramos = array(
            array("id" => "por_vencer", "label" => "Por vencer", "monto" => $porVencer, "pct" => $pct($porVencer), "color" => "#A8D5BA"),
            array("id" => "0_30", "label" => "Vencido 0-30 días", "monto" => $r0, "pct" => $pct($r0), "color" => "#F5E6A8"),
            array("id" => "31_60", "label" => "Vencido 31-60 días", "monto" => $r31, "pct" => $pct($r31), "color" => "#F5C4A0"),
            array("id" => "61_90", "label" => "Vencido 61-90 días", "monto" => $r61, "pct" => $pct($r61), "color" => "#F0B0B0"),
            array("id" => "mas_90", "label" => "Vencido +90 días", "monto" => $mas90, "pct" => $pct($mas90), "color" => "#D8B4C8"),
        );

        return array(
            "total" => $total,
            "tramos" => $tramos,
            "monto_por_vencer" => $porVencer,
            "monto_0_30" => $r0,
            "monto_mas_30" => $mas30,
            "pct_por_vencer" => $pct($porVencer),
            "pct_0_30" => $pct($r0),
        );
    }

    /**
     * Pedidos aprobados / APT / confirmados aún no facturados (temporaljf).
     * Soles = op. gravada, misma regla que el centro de decisiones (excluye lista precio1).
     */
    private static function mdlPorFacturar($vendedor, $desde, $hastaInclusive)
    {
        $sqlBase = "FROM temporaljf t
            WHERE TRIM(IFNULL(t.vendedor, '')) = :vendedor
              AND t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')";
        $soles = "CASE WHEN COALESCE(t.lista, '') <> 'precio1' THEN IFNULL(t.op_gravada, 0) ELSE 0 END";

        $sql = "SELECT
                t.estado,
                COUNT(*) AS pedidos,
                COALESCE(SUM({$soles}), 0) AS soles,
                COALESCE(SUM(CASE
                    WHEN DATE(t.fecha) >= :ini AND DATE(t.fecha) <= :fin THEN 1 ELSE 0 END), 0) AS pedidos_semana,
                COALESCE(SUM(CASE
                    WHEN DATE(t.fecha) >= :ini2 AND DATE(t.fecha) <= :fin2 THEN {$soles} ELSE 0 END), 0) AS soles_semana
            {$sqlBase}
            GROUP BY t.estado";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->bindValue(":ini", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fin", $hastaInclusive, PDO::PARAM_STR);
        $stmt->bindValue(":ini2", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":fin2", $hastaInclusive, PDO::PARAM_STR);
        $stmt->execute();

        $etiquetas = array(
            "APROBADO" => "Aprobado",
            "APT" => "En APT",
            "CONFIRMADO" => "Confirmado",
        );
        $porEstado = array();
        foreach ($etiquetas as $est => $label) {
            $porEstado[$est] = array(
                "estado" => $est,
                "label" => $label,
                "pedidos" => 0,
                "soles" => 0.0,
                "pedidos_semana" => 0,
                "soles_semana" => 0.0,
            );
        }
        $totalPed = 0;
        $totalSoles = 0.0;
        $semPed = 0;
        $semSoles = 0.0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $est = strtoupper(trim((string) $fila["estado"]));
            if (!isset($porEstado[$est])) {
                continue;
            }
            $porEstado[$est]["pedidos"] = (int) $fila["pedidos"];
            $porEstado[$est]["soles"] = (float) $fila["soles"];
            $porEstado[$est]["pedidos_semana"] = (int) $fila["pedidos_semana"];
            $porEstado[$est]["soles_semana"] = (float) $fila["soles_semana"];
            $totalPed += (int) $fila["pedidos"];
            $totalSoles += (float) $fila["soles"];
            $semPed += (int) $fila["pedidos_semana"];
            $semSoles += (float) $fila["soles_semana"];
        }

        return array(
            "total_pedidos" => $totalPed,
            "total_soles" => $totalSoles,
            "semana_pedidos" => $semPed,
            "semana_soles" => $semSoles,
            "por_estado" => array_values($porEstado),
        );
    }
}
