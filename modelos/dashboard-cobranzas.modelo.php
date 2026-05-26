<?php

require_once "conexion.php";

class ModeloDashboardCobranzas
{
    public static function annosDashboard()
    {
        return array(2025, 2026);
    }

    private static function rangoAnnosDashboard()
    {
        return array(
            "inicio" => "2025-01-01",
            "fin" => "2027-01-01",
        );
    }

    private static function sqlIngreso($alias = "cc")
    {
        return "CASE
            WHEN {$alias}.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82') THEN 'EFECTIVO'
            WHEN {$alias}.cod_pago IN ('13', '96') THEN 'DEVOLUCION'
            WHEN {$alias}.cod_pago IN ('97', '10') THEN 'DESCUENTOS'
            ELSE 'OTROS'
        END";
    }

    private static function rangoMes($anno, $mes)
    {
        $anno = (int) $anno;
        $mes = (int) $mes;
        $inicio = sprintf("%04d-%02d-01", $anno, $mes);

        if ($mes === 12) {
            $fin = sprintf("%04d-01-01", $anno + 1);
        } else {
            $fin = sprintf("%04d-%02d-01", $anno, $mes + 1);
        }

        return array("inicio" => $inicio, "fin" => $fin);
    }

    private static function bindFiltroPeriodo($stmt, $anno, $mes, $vendedor)
    {
        $rango = self::rangoMes($anno, $mes);

        $stmt->bindParam(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
    }

    /** Solo cod_pago clasificados como EFECTIVO (gráficos y totales de cobranza del dashboard). */
    private static function filtroSoloEfectivo($alias = "cc")
    {
        $sqlIngreso = self::sqlIngreso($alias);

        return " AND {$sqlIngreso} = 'EFECTIVO'";
    }

    private static function wherePeriodo($alias = "cc")
    {
        return "{$alias}.tip_mov = '-'
            AND {$alias}.fecha >= :fecha_ini
            AND {$alias}.fecha < :fecha_fin
            AND (:vendedor = '' OR {$alias}.vendedor = :vendedor)";
    }

    private static function wherePeriodoGraficos($alias = "cc")
    {
        return self::wherePeriodo($alias) . self::filtroSoloEfectivo($alias);
    }

    /**
     * Semana del mes por día calendario (no CEIL(día/7), que desalinea la semana 5).
     * 1: 01-07, 2: 08-14, 3: 15-21, 4: 22-28, 5: 29-fin de mes
     */
    private static function sqlSemanaMes($alias = "cc")
    {
        return "CASE
            WHEN DAY({$alias}.fecha) <= 7 THEN 1
            WHEN DAY({$alias}.fecha) <= 14 THEN 2
            WHEN DAY({$alias}.fecha) <= 21 THEN 3
            WHEN DAY({$alias}.fecha) <= 28 THEN 4
            ELSE 5
        END";
    }

    private static function diasDelMes($anno, $mes)
    {
        $mes = (int) $mes;
        $anno = (int) $anno;

        if (function_exists("cal_days_in_month")) {
            return (int) cal_days_in_month(CAL_GREGORIAN, $mes, $anno);
        }

        $diasPorMes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        if ($mes === 2) {
            $bisiesto = ($anno % 400 === 0) || ($anno % 4 === 0 && $anno % 100 !== 0);
            return $bisiesto ? 29 : 28;
        }

        return isset($diasPorMes[$mes - 1]) ? $diasPorMes[$mes - 1] : 31;
    }

    private static function diasCalendarioSemana($semana, $ultimoDia)
    {
        $semana = (int) $semana;
        $ultimoDia = (int) $ultimoDia;
        $inicio = (($semana - 1) * 7) + 1;
        $fin = min($semana * 7, $ultimoDia);

        if ($inicio > $ultimoDia) {
            return 0;
        }

        return $fin - $inicio + 1;
    }

    /**
     * Cuántas veces cae cada día en el mes (DAYOFWEEK MySQL: 1=Dom … 7=Sáb).
     */
    private static function contarDiasSemanaEnMes($anno, $mes)
    {
        $ultimo = self::diasDelMes($anno, $mes);
        $conteo = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0);

        for ($dia = 1; $dia <= $ultimo; $dia++) {
            $ts = mktime(0, 0, 0, (int) $mes, $dia, (int) $anno);
            $numDia = (int) date("w", $ts) + 1;
            $conteo[$numDia]++;
        }

        return $conteo;
    }

    private static function etiquetaRangoSemana($semana, $ultimoDia)
    {
        $inicio = (($semana - 1) * 7) + 1;
        $fin = min($semana * 7, $ultimoDia);

        if ($inicio > $ultimoDia) {
            return "";
        }

        return sprintf("%02d - %02d", $inicio, $fin);
    }

    static public function mdlCobranzaPromedioSemana($anno, $mes, $vendedor = "")
    {
        $sqlSemana = self::sqlSemanaMes("cc");

        $sql = "SELECT
            {$sqlSemana} AS semana_mes,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY {$sqlSemana}
        ORDER BY semana_mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $porSemana = array();

        foreach ($filas as $fila) {
            $porSemana[(int) $fila["semana_mes"]] = (float) $fila["total"];
        }

        $ultimoDia = self::diasDelMes($anno, $mes);
        $labels = array();
        $rangos = array();
        $promedios = array();
        $totales = array();

        for ($semana = 1; $semana <= 5; $semana++) {
            $diasCal = self::diasCalendarioSemana($semana, $ultimoDia);
            $total = isset($porSemana[$semana]) ? $porSemana[$semana] : 0;
            $promedio = $diasCal > 0 ? round($total / $diasCal, 2) : 0;
            $rango = self::etiquetaRangoSemana($semana, $ultimoDia);
            $rangoEtiqueta = str_replace(" - ", " al ", $rango);

            $labels[] = "Sem " . $semana . " (" . $rango . ")";
            $rangos[] = $rangoEtiqueta;
            $promedios[] = $promedio;
            $totales[] = $total;
        }

        return array(
            "labels" => $labels,
            "rangos" => $rangos,
            "promedios" => $promedios,
            "totales" => $totales,
        );
    }

    static public function mdlCobranzaPorDiaSemana($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT
            DAYOFWEEK(cc.fecha) AS num_dia_semana,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY DAYOFWEEK(cc.fecha)";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $porDia = array();

        foreach ($filas as $fila) {
            $porDia[(int) $fila["num_dia_semana"]] = (float) $fila["total"];
        }

        $ordenDias = array(
            2 => array("corto" => "Lun", "nombre" => "Lunes"),
            3 => array("corto" => "Mar", "nombre" => "Martes"),
            4 => array("corto" => "Mie", "nombre" => "Miércoles"),
            5 => array("corto" => "Jue", "nombre" => "Jueves"),
            6 => array("corto" => "Vie", "nombre" => "Viernes"),
            7 => array("corto" => "Sab", "nombre" => "Sábado"),
            1 => array("corto" => "Dom", "nombre" => "Domingo"),
        );

        $ocurrenciasMes = self::contarDiasSemanaEnMes($anno, $mes);
        $labels = array();
        $montos = array();
        $mejorMonto = 0;
        $mejorNombre = "";

        foreach ($ordenDias as $num => $info) {
            $total = isset($porDia[$num]) ? $porDia[$num] : 0;
            $vecesEnMes = isset($ocurrenciasMes[$num]) ? (int) $ocurrenciasMes[$num] : 0;
            $promedio = $vecesEnMes > 0 ? round($total / $vecesEnMes, 2) : 0;

            $labels[] = $info["corto"];
            $montos[] = $promedio;

            if ($promedio > $mejorMonto) {
                $mejorMonto = $promedio;
                $mejorNombre = $info["nombre"];
            }
        }

        return array(
            "labels" => $labels,
            "montos" => $montos,
            "mejor_dia" => $mejorNombre,
            "mejor_monto" => $mejorMonto,
        );
    }

    static public function mdlComparativoDosPeriodos($anno, $mes, $annoAnt, $mesAnt, $vendedor = "")
    {
        $sqlIngreso = self::sqlIngreso("cc");
        $rangoAct = self::rangoMes($anno, $mes);
        $rangoAnt = self::rangoMes($annoAnt, $mesAnt);

        $filtroEfectivo = self::filtroSoloEfectivo("cc");

        $sql = "SELECT
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act {$filtroEfectivo}
                    THEN cc.monto ELSE 0
                END
            ), 0) AS cobranza_total,
            SUM(
                CASE
                    WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act {$filtroEfectivo}
                    THEN 1 ELSE 0
                END
            ) AS operaciones,
            COUNT(DISTINCT
                CASE
                    WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act {$filtroEfectivo}
                    THEN DATE(cc.fecha) END
            ) AS dias_con_movimiento,
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act
                        AND " . $sqlIngreso . " IN ('DEVOLUCION', 'DESCUENTOS')
                    THEN cc.monto ELSE 0
                END
            ), 0) AS dev_descuentos,
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant {$filtroEfectivo}
                    THEN cc.monto ELSE 0
                END
            ), 0) AS cobranza_total_ant,
            SUM(
                CASE
                    WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant {$filtroEfectivo}
                    THEN 1 ELSE 0
                END
            ) AS operaciones_ant,
            COUNT(DISTINCT
                CASE
                    WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant {$filtroEfectivo}
                    THEN DATE(cc.fecha) END
            ) AS dias_con_movimiento_ant,
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant
                        AND " . $sqlIngreso . " IN ('DEVOLUCION', 'DESCUENTOS')
                    THEN cc.monto ELSE 0
                END
            ), 0) AS dev_descuentos_ant
        FROM cuenta_ctejf cc
        WHERE cc.tip_mov = '-'
            AND (
                (cc.fecha >= :ini_act AND cc.fecha < :fin_act)
                OR (cc.fecha >= :ini_ant AND cc.fecha < :fin_ant)
            )
            AND (:vendedor = '' OR cc.vendedor = :vendedor)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":ini_act", $rangoAct["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fin_act", $rangoAct["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":ini_ant", $rangoAnt["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fin_ant", $rangoAnt["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlMejorDia($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY monto DESC
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array("dia" => null, "monto" => 0);
        }

        return $fila;
    }

    static public function mdlMejorVendedor($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT
            cc.vendedor,
            COALESCE(SUM(cc.monto), 0) AS monto,
            COALESCE(MAX(m.descripcion), cc.vendedor) AS nombre_vendedor
        FROM cuenta_ctejf cc
        LEFT JOIN maestrajf m
            ON cc.vendedor = m.codigo
            AND m.tipo_dato = 'TVEND'
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY cc.vendedor
        ORDER BY monto DESC
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array("vendedor" => null, "monto" => 0, "nombre_vendedor" => "—");
        }

        return $fila;
    }

    static public function mdlSerieDiariaMes($anno, $mes, $vendedor = "")
    {
        $sqlIngreso = self::sqlIngreso("cc");

        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(
                CASE WHEN {$sqlIngreso} = 'EFECTIVO' THEN cc.monto ELSE 0 END
            ), 0) AS monto,
            SUM(
                CASE WHEN {$sqlIngreso} = 'EFECTIVO' THEN 1 ELSE 0 END
            ) AS operaciones,
            COALESCE(SUM(
                CASE
                    WHEN {$sqlIngreso} IN ('DEVOLUCION', 'DESCUENTOS') THEN cc.monto
                    ELSE 0
                END
            ), 0) AS dev_descuentos
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSerieDiariaVendedor($anno, $mes, $codigoVendedor)
    {
        if ($codigoVendedor === null || $codigoVendedor === "") {
            return array();
        }

        $vendedor = $codigoVendedor;

        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlVendedoresConCobranza($anno)
    {
        $anno = (int) $anno;
        $inicio = $anno . "-01-01";
        $fin = ($anno + 1) . "-01-01";

        $sql = "SELECT DISTINCT
            cc.vendedor AS codigo,
            COALESCE(m.descripcion, cc.vendedor) AS descripcion
        FROM cuenta_ctejf cc
        LEFT JOIN maestrajf m
            ON cc.vendedor = m.codigo
            AND m.tipo_dato = 'TVEND'
        WHERE cc.tip_mov = '-'
            AND cc.fecha >= :fecha_ini
            AND cc.fecha < :fecha_fin
            AND cc.vendedor IS NOT NULL
            AND cc.vendedor != ''"
            . self::filtroSoloEfectivo("cc") . "
        ORDER BY descripcion ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_ini", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $fin, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cobranza diaria del mes para varios años (comparativo evolución acumulada).
     */
    static public function mdlCobranzaDiariaAnnosMes($mes, $vendedor, $annos)
    {
        $mes = (int) $mes;
        $vendedor = trim((string) $vendedor);
        $annos = array_map("intval", (array) $annos);
        $annos = array_values(array_unique($annos));

        if ($mes < 1 || $mes > 12 || count($annos) === 0) {
            return array();
        }

        $placeholders = array();
        $params = array();

        foreach ($annos as $i => $anno) {
            $keyAnno = ":anno_" . $i;
            $keyIni = ":ini_" . $i;
            $keyFin = ":fin_" . $i;
            $rango = self::rangoMes($anno, $mes);

            $placeholders[] = "(cc.fecha >= {$keyIni} AND cc.fecha < {$keyFin})";
            $params[$keyIni] = $rango["inicio"];
            $params[$keyFin] = $rango["fin"];
        }

        $sql = "SELECT
            YEAR(cc.fecha) AS anno,
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto
        FROM cuenta_ctejf cc
        WHERE cc.tip_mov = '-'
            AND (" . implode(" OR ", $placeholders) . ")
            AND (:vendedor = '' OR cc.vendedor = :vendedor)"
            . self::filtroSoloEfectivo("cc") . "
        GROUP BY YEAR(cc.fecha), DAY(cc.fecha)
        ORDER BY anno ASC, dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);

        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total cobrado por mes y año (2025 vs 2026). Ignora filtro de mes del dashboard.
     */
    static public function mdlCobranzaComparativoMensual($vendedor = "")
    {
        $vendedor = trim((string) $vendedor);
        $rango = self::rangoAnnosDashboard();

        $sql = "SELECT
            YEAR(cc.fecha) AS anno,
            MONTH(cc.fecha) AS mes,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE cc.tip_mov = '-'
            AND cc.fecha >= :fecha_ini
            AND cc.fecha < :fecha_fin
            AND (:vendedor = '' OR cc.vendedor = :vendedor)"
            . self::filtroSoloEfectivo("cc") . "
        GROUP BY YEAR(cc.fecha), MONTH(cc.fecha)
        ORDER BY mes ASC, anno ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total cobranza efectivo del período (base para % en rankings).
     */
    static public function mdlSumaCobranzaEfectivoPeriodo($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc");

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? (float) $fila["total"] : 0;
    }

    /**
     * Top clientes por cobranza efectivo en el período (respeta filtro vendedor).
     */
    static public function mdlTopClientes($anno, $mes, $vendedor = "", $limite = 10)
    {
        $limite = (int) $limite;

        if ($limite < 1) {
            $limite = 10;
        }

        $sql = "SELECT
            cc.cliente AS codigo,
            COALESCE(MAX(c.nombre), cc.cliente) AS nombre,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        LEFT JOIN clientesjf c
            ON cc.cliente = c.codigo
        WHERE " . self::wherePeriodoGraficos("cc") . "
            AND cc.cliente IS NOT NULL
            AND cc.cliente != ''
        GROUP BY cc.cliente
        ORDER BY total DESC
        LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top vendedores por cobranza en el período (año + mes). Sin filtro por vendedor.
     */
    static public function mdlTopVendedores($anno, $mes, $limite = 10)
    {
        $anno = (int) $anno;
        $mes = (int) $mes;
        $limite = (int) $limite;

        if ($limite < 1) {
            $limite = 10;
        }

        $sql = "SELECT
            cc.vendedor AS codigo,
            COALESCE(MAX(m.descripcion), cc.vendedor) AS nombre,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        LEFT JOIN maestrajf m
            ON cc.vendedor = m.codigo
            AND m.tipo_dato = 'TVEND'
        WHERE " . self::wherePeriodoGraficos("cc") . "
            AND cc.vendedor IS NOT NULL
            AND cc.vendedor != ''
        GROUP BY cc.vendedor
        ORDER BY total DESC
        LIMIT " . $limite;

        $vendedorTodos = "";
        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedorTodos);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Matriz semana del mes (1–5) × día de la semana (Lun–Dom), suma de cobranza efectivo.
     */
    static public function mdlHeatmapCobranza($anno, $mes, $vendedor = "")
    {
        $sqlSemana = self::sqlSemanaMes("cc");

        $sql = "SELECT
            {$sqlSemana} AS semana_mes,
            DAYOFWEEK(cc.fecha) AS num_dia_semana,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodoGraficos("cc") . "
        GROUP BY {$sqlSemana}, DAYOFWEEK(cc.fecha)";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapa = array();
        $maxMonto = 0;

        foreach ($filas as $fila) {
            $semana = (int) $fila["semana_mes"];
            $dia = (int) $fila["num_dia_semana"];
            $monto = (float) $fila["total"];

            if (!isset($mapa[$semana])) {
                $mapa[$semana] = array();
            }

            $mapa[$semana][$dia] = $monto;

            if ($monto > $maxMonto) {
                $maxMonto = $monto;
            }
        }

        $ordenDias = array(
            2 => array("corto" => "Lun", "nombre" => "Lunes"),
            3 => array("corto" => "Mar", "nombre" => "Martes"),
            4 => array("corto" => "Mie", "nombre" => "Miércoles"),
            5 => array("corto" => "Jue", "nombre" => "Jueves"),
            6 => array("corto" => "Vie", "nombre" => "Viernes"),
            7 => array("corto" => "Sab", "nombre" => "Sábado"),
            1 => array("corto" => "Dom", "nombre" => "Domingo"),
        );

        $columnas = array();

        foreach ($ordenDias as $num => $meta) {
            $columnas[] = array(
                "num_dia_semana" => $num,
                "nom_dia_corto" => $meta["corto"],
                "nom_dia" => $meta["nombre"],
            );
        }

        $ultimoDia = self::diasDelMes($anno, $mes);
        $filasMatriz = array();

        for ($semana = 1; $semana <= 5; $semana++) {
            $rango = self::etiquetaRangoSemana($semana, $ultimoDia);

            if ($rango === "") {
                continue;
            }

            $celdas = array();

            foreach ($ordenDias as $num => $meta) {
                $monto = 0;

                if (isset($mapa[$semana]) && isset($mapa[$semana][$num])) {
                    $monto = $mapa[$semana][$num];
                }

                $celdas[] = array(
                    "num_dia_semana" => $num,
                    "nom_dia" => $meta["nombre"],
                    "nom_dia_corto" => $meta["corto"],
                    "monto" => $monto,
                );
            }

            $filasMatriz[] = array(
                "semana_mes" => $semana,
                "rango" => $rango,
                "etiqueta" => "Sem " . $semana . " (" . $rango . ")",
                "celdas" => $celdas,
            );
        }

        return array(
            "columnas" => $columnas,
            "filas" => $filasMatriz,
            "max_monto" => $maxMonto,
            "anno" => (int) $anno,
            "mes" => (int) $mes,
        );
    }

    /**
     * Días del mes sin cobranza efectivo (sin movimiento con monto > 0).
     */
    static public function mdlDiasSinCobranza($anno, $mes, $vendedor = "")
    {
        $diasMes = self::diasDelMes($anno, $mes);

        $sql = "SELECT COUNT(*) AS dias_con
        FROM (
            SELECT DAY(cc.fecha) AS dia
            FROM cuenta_ctejf cc
            WHERE " . self::wherePeriodoGraficos("cc") . "
            GROUP BY DAY(cc.fecha)
            HAVING SUM(cc.monto) > 0
        ) AS dias_cobranza";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $diasCon = $fila ? (int) $fila["dias_con"] : 0;

        if ($diasCon < 0) {
            $diasCon = 0;
        }

        if ($diasCon > $diasMes) {
            $diasCon = $diasMes;
        }

        $diasSin = $diasMes - $diasCon;
        $porcentajeSin = 0;
        $porcentajeCon = 0;
        $escalaMax = $diasMes > 10 ? $diasMes : 10;

        if ($diasMes > 0) {
            $porcentajeSin = round(($diasSin / $diasMes) * 100, 1);
            $porcentajeCon = round(($diasCon / $diasMes) * 100, 1);
        }

        return array(
            "dias_mes" => $diasMes,
            "dias_con_cobranza" => $diasCon,
            "dias_sin" => $diasSin,
            "porcentaje_sin" => $porcentajeSin,
            "porcentaje_con" => $porcentajeCon,
            "escala_max" => $escalaMax,
            "anno" => (int) $anno,
            "mes" => (int) $mes,
        );
    }

    /**
     * Distribución por tipo de ingreso (EFECTIVO, DEVOLUCION, DESCUENTOS, OTROS).
     * Incluye todos los tipos; no aplica filtro solo efectivo.
     */
    static public function mdlDistribucionTipoIngreso($anno, $mes, $vendedor = "")
    {
        $sqlIngreso = self::sqlIngreso("cc");

        $sql = "SELECT
            {$sqlIngreso} AS tipo_ingreso,
            COALESCE(SUM(cc.monto), 0) AS total
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY {$sqlIngreso}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $porTipo = array();

        foreach ($filas as $fila) {
            $porTipo[(string) $fila["tipo_ingreso"]] = (float) $fila["total"];
        }

        $catalogo = array(
            array("clave" => "EFECTIVO", "label" => "Efectivo", "color" => "#3d9970"),
            array("clave" => "DEVOLUCION", "label" => "Devolución", "color" => "#dd4b39"),
            array("clave" => "DESCUENTOS", "label" => "Descuentos", "color" => "#f39c12"),
            array("clave" => "OTROS", "label" => "Otros", "color" => "#95a5a6"),
        );

        $segmentos = array();
        $totalGeneral = 0;

        foreach ($catalogo as $item) {
            $monto = isset($porTipo[$item["clave"]]) ? $porTipo[$item["clave"]] : 0;
            $totalGeneral += $monto;
        }

        foreach ($catalogo as $item) {
            $monto = isset($porTipo[$item["clave"]]) ? $porTipo[$item["clave"]] : 0;
            $porcentaje = 0;

            if ($totalGeneral > 0) {
                $porcentaje = round(($monto / $totalGeneral) * 100, 1);
            }

            $segmentos[] = array(
                "clave" => $item["clave"],
                "label" => $item["label"],
                "color" => $item["color"],
                "monto" => $monto,
                "porcentaje" => $porcentaje,
            );
        }

        return array(
            "segmentos" => $segmentos,
            "total" => $totalGeneral,
            "anno" => (int) $anno,
            "mes" => (int) $mes,
        );
    }
}
