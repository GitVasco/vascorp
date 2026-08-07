<?php

require_once dirname(__FILE__) . '/dashboard-gerencial.config.php';

class ControladorDashboardGerencial
{
    public static function ctrAnnosPermitidos()
    {
        return dashboardGerencialAnnosPermitidos();
    }

    public static function ctrMesesEtiqueta()
    {
        return array(
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        );
    }

    public static function ctrParseFiltros(array $entrada)
    {
        $annos = self::ctrAnnosPermitidos();
        $anio = isset($entrada['anio']) ? (int) $entrada['anio'] : (int) date('Y');
        $mes = isset($entrada['mes']) ? (int) $entrada['mes'] : (int) date('n');

        if (!in_array($anio, $annos, true)) {
            $anio = in_array((int) date('Y'), $annos, true)
                ? (int) date('Y')
                : $annos[count($annos) - 1];
        }

        if ($mes !== 0 && ($mes < 1 || $mes > 12)) {
            $mes = (int) date('n');
        }

        $vendedor = isset($entrada['vendedor']) ? trim((string) $entrada['vendedor']) : '';

        $modo = isset($entrada['modo']) ? trim((string) $entrada['modo']) : 'vs_anio_ant';
        if (!in_array($modo, array('vs_anio_ant', 'periodos'), true)) {
            $modo = 'vs_anio_ant';
        }

        $periodoA = self::parseRangoFechas(
            isset($entrada['periodo_a_desde']) ? $entrada['periodo_a_desde'] : null,
            isset($entrada['periodo_a_hasta']) ? $entrada['periodo_a_hasta'] : null,
            $anio,
            $mes,
            'a'
        );
        $periodoB = self::parseRangoFechas(
            isset($entrada['periodo_b_desde']) ? $entrada['periodo_b_desde'] : null,
            isset($entrada['periodo_b_hasta']) ? $entrada['periodo_b_hasta'] : null,
            $anio - 1,
            $mes,
            'b'
        );

        return array(
            'anio' => $anio,
            'mes' => $mes,
            'periodo_anual' => ($mes === 0),
            'vendedor' => $vendedor,
            'modo' => $modo,
            'periodo_a_desde' => $periodoA['desde'],
            'periodo_a_hasta' => $periodoA['hasta'],
            'periodo_b_desde' => $periodoB['desde'],
            'periodo_b_hasta' => $periodoB['hasta'],
        );
    }

    public static function ctrVendedoresFiltro($anio)
    {
        return ModeloDashboardGerencial::mdlVendedoresFiltro($anio);
    }

    /**
     * KPIs cabecera (contrato Bloque 0/1). Recuperación y proyección = null hasta bloques 6–8.
     */
    public static function ctrKpis(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $mes = (int) $filtros['mes'];
        $periodoAnual = !empty($filtros['periodo_anual']) || $mes === 0;
        $vendedor = trim((string) $filtros['vendedor']);

        // Variaciones siempre vs mismo período del año anterior (N-1).
        $anioAnt = $anio - 1;
        $mesAnt = $mes;

        $ventaMes = self::obtenerVentaPeriodo($anio, $mes, $vendedor);
        $cobranzaMes = self::obtenerCobranzaPeriodo($anio, $mes, $vendedor);
        $ventaMesAnt = self::obtenerVentaPeriodo($anioAnt, $mesAnt, $vendedor);
        $cobranzaMesAnt = self::obtenerCobranzaPeriodo($anioAnt, $mesAnt, $vendedor);

        if ($periodoAnual) {
            $ventaYtd = $ventaMes;
            $cobranzaYtd = $cobranzaMes;
            $ventaYtdAnt = $ventaMesAnt;
            $cobranzaYtdAnt = $cobranzaMesAnt;
        } else {
            $ventaYtd = self::obtenerVentaYtd($anio, $mes, $vendedor);
            $cobranzaYtd = self::obtenerCobranzaYtd($anio, $mes, $vendedor);
            $ventaYtdAnt = self::obtenerVentaYtd($anioAnt, $mes, $vendedor);
            $cobranzaYtdAnt = self::obtenerCobranzaYtd($anioAnt, $mes, $vendedor);
        }

        return array(
            'periodo_anual' => $periodoAnual,
            'anio_anterior' => $anioAnt,
            'mes_anterior' => $mesAnt,
            'venta_mes' => $ventaMes,
            'venta_mes_var' => self::variacionPorcentaje($ventaMes, $ventaMesAnt),
            'venta_ytd' => $ventaYtd,
            'venta_ytd_var' => self::variacionPorcentaje($ventaYtd, $ventaYtdAnt),
            'cobranza_mes' => $cobranzaMes,
            'cobranza_mes_var' => self::variacionPorcentaje($cobranzaMes, $cobranzaMesAnt),
            'cobranza_ytd' => $cobranzaYtd,
            'cobranza_ytd_var' => self::variacionPorcentaje($cobranzaYtd, $cobranzaYtdAnt),
            'pct_recuperacion' => null,
            'proyeccion_mes' => null,
            'proyeccion_vs_real' => null,
            'fuentes' => array(
                'venta' => 'ventajf.neto (tipos venta real)',
                'cobranza' => 'cuenta_ctejf efectivo sin IGV (÷1.18)',
            ),
        );
    }

    public static function ctrDatosBase(array $filtros)
    {
        $origen = self::ctrOrigenCobranza($filtros);
        $puntualidad = self::ctrPuntualidadVencimientos($filtros);
        $proyeccion = self::ctrProyeccionCobranzas($filtros);
        $kpis = self::ctrKpis($filtros);
        $kpis['pct_recuperacion'] = isset($origen['pct_mismo_mes']) ? $origen['pct_mismo_mes'] : null;
        $kpis['proyeccion_mes'] = isset($proyeccion['proyeccion_mes']) ? $proyeccion['proyeccion_mes'] : null;
        $kpis['real_mes'] = isset($proyeccion['real_mes']) ? $proyeccion['real_mes'] : null;
        // KPI cabecera: % a tiempo (fecha_ven vs ult_pago), no cobertura proyección.
        $kpis['proyeccion_vs_real'] = isset($puntualidad['pct_a_tiempo']) ? $puntualidad['pct_a_tiempo'] : null;

        return array(
            'filtros' => $filtros,
            'kpis' => $kpis,
            'ventas_mensual' => self::ctrVentasMensual($filtros),
            'ventas_vs_anio' => self::ctrVentasVsAnioPasado($filtros),
            'ventas_periodos' => self::ctrVentasPeriodos($filtros),
            'cobranzas_mensual' => self::ctrCobranzasMensual($filtros),
            'cobranzas_vs_anio' => self::ctrCobranzasVsAnioPasado($filtros),
            'cobranzas_periodos' => self::ctrCobranzasPeriodos($filtros),
            'origen_cobranza' => $origen,
            'puntualidad_vencimientos' => $puntualidad,
            'proyeccion_cobranzas' => $proyeccion,
        );
    }

    /**
     * Bloque 2 — ventas mes a mes del año del filtro.
     */
    public static function ctrVentasMensual(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $vendedor = trim((string) $filtros['vendedor']);
        $mesHasta = self::mesHastaSerie($anio);
        $serie = self::serieVentasAnio($anio, $vendedor, $mesHasta);

        return array(
            'anio' => $anio,
            'vendedor' => $vendedor,
            'mes_hasta' => $mesHasta,
            'labels' => $serie['labels'],
            'montos' => $serie['montos'],
            'filas' => $serie['filas'],
            'total' => $serie['total'],
            'fuente' => 'ventajf.neto (tipos venta real)',
        );
    }

    /**
     * Bloque 3 — ventas año N vs N-1 (mismos meses).
     */
    public static function ctrVentasVsAnioPasado(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $anioAnt = $anio - 1;
        $vendedor = trim((string) $filtros['vendedor']);
        $mesHasta = self::mesHastaSerie($anio);

        $act = self::serieVentasAnio($anio, $vendedor, $mesHasta);
        $ant = self::serieVentasAnio($anioAnt, $vendedor, $mesHasta);

        $filas = array();
        $totalDelta = $act['total'] - $ant['total'];

        for ($i = 0; $i < $mesHasta; $i++) {
            $ventaN = isset($act['montos'][$i]) ? (float) $act['montos'][$i] : 0.0;
            $ventaN1 = isset($ant['montos'][$i]) ? (float) $ant['montos'][$i] : 0.0;
            $delta = $ventaN - $ventaN1;

            $filas[] = array(
                'mes' => $i + 1,
                'label' => $act['filas'][$i]['label'],
                'label_corto' => $act['filas'][$i]['label_corto'],
                'venta_n' => $ventaN,
                'venta_n1' => $ventaN1,
                'delta_abs' => $delta,
                'delta_pct' => self::variacionPorcentaje($ventaN, $ventaN1),
            );
        }

        return array(
            'anio' => $anio,
            'anio_anterior' => $anioAnt,
            'vendedor' => $vendedor,
            'mes_hasta' => $mesHasta,
            'labels' => $act['labels'],
            'montos_n' => $act['montos'],
            'montos_n1' => $ant['montos'],
            'filas' => $filas,
            'total_n' => $act['total'],
            'total_n1' => $ant['total'],
            'delta_abs' => $totalDelta,
            'delta_pct' => self::variacionPorcentaje($act['total'], $ant['total']),
            'fuente' => 'ventajf.neto (tipos venta real)',
        );
    }

    /**
     * Bloque 4 — ventas período A vs período B (fechas desde–hasta).
     */
    public static function ctrVentasPeriodos(array $filtros)
    {
        return self::armarComparativoPeriodos(
            $filtros,
            'mdlVentasRango',
            'ventajf.neto (tipos venta real)',
            'venta_a',
            'venta_b'
        );
    }

    /** Bloque 5 — cobranzas mes a mes. */
    public static function ctrCobranzasMensual(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $vendedor = trim((string) $filtros['vendedor']);
        $mesHasta = self::mesHastaSerie($anio);
        $serie = self::serieCobranzasAnio($anio, $vendedor, $mesHasta);

        return array(
            'anio' => $anio,
            'vendedor' => $vendedor,
            'mes_hasta' => $mesHasta,
            'labels' => $serie['labels'],
            'montos' => $serie['montos'],
            'filas' => $serie['filas'],
            'total' => $serie['total'],
            'fuente' => 'cuenta_ctejf efectivo sin IGV (÷1.18)',
        );
    }

    /** Bloque 5 — cobranzas N vs N-1. */
    public static function ctrCobranzasVsAnioPasado(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $anioAnt = $anio - 1;
        $vendedor = trim((string) $filtros['vendedor']);
        $mesHasta = self::mesHastaSerie($anio);

        $act = self::serieCobranzasAnio($anio, $vendedor, $mesHasta);
        $ant = self::serieCobranzasAnio($anioAnt, $vendedor, $mesHasta);

        $filas = array();
        for ($i = 0; $i < $mesHasta; $i++) {
            $n = isset($act['montos'][$i]) ? (float) $act['montos'][$i] : 0.0;
            $n1 = isset($ant['montos'][$i]) ? (float) $ant['montos'][$i] : 0.0;
            $filas[] = array(
                'mes' => $i + 1,
                'label' => $act['filas'][$i]['label'],
                'label_corto' => $act['filas'][$i]['label_corto'],
                'venta_n' => $n,
                'venta_n1' => $n1,
                'delta_abs' => $n - $n1,
                'delta_pct' => self::variacionPorcentaje($n, $n1),
            );
        }

        return array(
            'anio' => $anio,
            'anio_anterior' => $anioAnt,
            'vendedor' => $vendedor,
            'mes_hasta' => $mesHasta,
            'labels' => $act['labels'],
            'montos_n' => $act['montos'],
            'montos_n1' => $ant['montos'],
            'filas' => $filas,
            'total_n' => $act['total'],
            'total_n1' => $ant['total'],
            'delta_abs' => $act['total'] - $ant['total'],
            'delta_pct' => self::variacionPorcentaje($act['total'], $ant['total']),
            'fuente' => 'cuenta_ctejf efectivo sin IGV (÷1.18)',
        );
    }

    /** Bloque 5 — cobranzas período A vs B. */
    public static function ctrCobranzasPeriodos(array $filtros)
    {
        return self::armarComparativoPeriodos(
            $filtros,
            'mdlCobranzasRango',
            'cuenta_ctejf efectivo sin IGV (÷1.18)',
            'venta_a',
            'venta_b'
        );
    }

    /**
     * Bloques 6–7 — origen de la cobranza del período de cobro.
     *
     * Fórmula KPI % recuperación (v1):
     *   % del cobro del período cuya fecha de origen del documento
     *   cae en el mismo mes calendario que la fecha de pago.
     *   (mismo_mes / total_cobrado) * 100
     */
    public static function ctrOrigenCobranza(array $filtros)
    {
        $rango = self::rangoPeriodoCobro($filtros);
        $vendedor = trim((string) $filtros['vendedor']);
        $anio = (int) $filtros['anio'];
        $raw = ModeloDashboardGerencial::mdlOrigenCobranza($rango['desde'], $rango['hasta'], $vendedor);

        $labelsCortos = array(
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        );

        $total = (float) $raw['total'];
        $sinOrigen = (float) $raw['sin_origen'];
        $mismoMes = (float) $raw['mismo_mes'];
        $filas = array();
        $labels = array();
        $montos = array();
        $pcts = array();

        foreach ($raw['filas'] as $fila) {
            $anioOri = (int) $fila['anio'];
            $mes = (int) $fila['mes'];
            $monto = (float) $fila['monto'];
            $label = (isset($labelsCortos[$mes]) ? $labelsCortos[$mes] : (string) $mes) . ' ' . $anioOri;
            $pct = $total > 0 ? round(($monto / $total) * 100, 1) : 0.0;

            $filas[] = array(
                'anio' => $anioOri,
                'mes' => $mes,
                'label' => $label,
                'monto' => $monto,
                'pct' => $pct,
            );
            $labels[] = $label;
            $montos[] = $monto;
            $pcts[] = $pct;
        }

        if ($sinOrigen > 0) {
            $pctSin = $total > 0 ? round(($sinOrigen / $total) * 100, 1) : 0.0;
            $filas[] = array(
                'anio' => null,
                'mes' => null,
                'label' => 'Sin origen',
                'monto' => $sinOrigen,
                'pct' => $pctSin,
            );
            $labels[] = 'Sin origen';
            $montos[] = $sinOrigen;
            $pcts[] = $pctSin;
        }

        $porVendedor = array();
        if ($vendedor === '') {
            $porVendedor = ModeloDashboardGerencial::mdlOrigenCobranzaPorVendedor(
                $rango['desde'],
                $rango['hasta'],
                15
            );
        }

        // Ventas del período seleccionado vs recuperado hasta hoy (de esas ventas).
        $ventasPeriodo = ModeloDashboardGerencial::mdlVentasRango(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $ventaPeriodo = (float) $ventasPeriodo['total'];
        $recuperadoPeriodo = ModeloDashboardGerencial::mdlRecuperadoDeOrigenHasta(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $pctRecupPeriodo = $ventaPeriodo > 0
            ? round(($recuperadoPeriodo / $ventaPeriodo) * 100, 1)
            : 0.0;
        $pendientePeriodo = max(0.0, $ventaPeriodo - $recuperadoPeriodo);

        // Reverso: de las ventas del período, en qué mes de pago se recuperaron.
        $recupRaw = ModeloDashboardGerencial::mdlRecuperacionPorMesPago(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $recupTotal = (float) $recupRaw['total'];
        $filasRecup = array();
        foreach ($recupRaw['filas'] as $fila) {
            $anioPago = (int) $fila['anio'];
            $mesPago = (int) $fila['mes'];
            $monto = (float) $fila['monto'];
            $label = (isset($labelsCortos[$mesPago]) ? $labelsCortos[$mesPago] : (string) $mesPago)
                . ' ' . $anioPago;
            $filasRecup[] = array(
                'anio' => $anioPago,
                'mes' => $mesPago,
                'label' => $label,
                'monto' => $monto,
                'pct' => $recupTotal > 0 ? round(($monto / $recupTotal) * 100, 1) : 0.0,
                'pct_venta' => $ventaPeriodo > 0 ? round(($monto / $ventaPeriodo) * 100, 1) : 0.0,
            );
        }

        // Mes a mes del año: venta/recuperado/pendiente + % cobro mismo mes (= KPI).
        $mensualRaw = ModeloDashboardGerencial::mdlVentasRecuperacionMensualAnio($anio, $vendedor);
        $mensual = array();
        $labelsMes = array();
        $pctMismoMesAnio = array();
        $ventasMes = array();
        $recupMes = array();
        $pendienteMes = array();

        foreach ($mensualRaw as $fila) {
            $mes = (int) $fila['mes'];
            $venta = (float) $fila['venta'];
            $recuperado = (float) $fila['recuperado'];
            $cobrado = (float) $fila['cobrado'];
            $mismo = (float) $fila['mismo_mes'];
            $label = isset($labelsCortos[$mes]) ? $labelsCortos[$mes] : (string) $mes;
            $pctMismo = $cobrado > 0 ? round(($mismo / $cobrado) * 100, 1) : 0.0;
            // Para barra apilada: recuperado acotado a la venta + lo que falta.
            $recupBarra = $venta > 0 ? min($recuperado, $venta) : 0.0;
            $pendiente = max(0.0, $venta - $recuperado);
            $pctRecup = $venta > 0 ? round(($recuperado / $venta) * 100, 1) : 0.0;
            $pctFalta = $venta > 0 ? round(($pendiente / $venta) * 100, 1) : 0.0;

            $mensual[] = array(
                'mes' => $mes,
                'label' => $label,
                'venta' => $venta,
                'recuperado' => $recuperado,
                'pendiente' => $pendiente,
                'cobrado' => $cobrado,
                'mismo_mes' => $mismo,
                'pct_recuperado' => $pctRecup,
                'pct_falta' => $pctFalta,
                'pct_mismo_mes' => $pctMismo,
            );
            $labelsMes[] = $label;
            $pctMismoMesAnio[] = $pctMismo;
            $ventasMes[] = $venta;
            $recupMes[] = $recupBarra;
            $pendienteMes[] = $pendiente;
        }

        // Aging del cobro del período (días origen → pago).
        $agingRaw = ModeloDashboardGerencial::mdlOrigenCobranzaAging(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $agingLabelsMap = array(
            '0-30' => '0–30 días',
            '31-60' => '31–60 días',
            '61-90' => '61–90 días',
            '91-180' => '91–180 días',
            '180+' => '+180 días',
            'sin_origen' => 'Sin origen',
        );
        $agingLabels = array();
        $agingMontos = array();
        $agingFilas = array();
        $agingTotal = array_sum($agingRaw);

        foreach ($agingLabelsMap as $id => $label) {
            if ($id === 'sin_origen' && (float) $agingRaw[$id] <= 0) {
                continue;
            }
            $monto = (float) $agingRaw[$id];
            $agingLabels[] = $label;
            $agingMontos[] = $monto;
            $agingFilas[] = array(
                'id' => $id,
                'label' => $label,
                'monto' => $monto,
                'pct' => $agingTotal > 0 ? round(($monto / $agingTotal) * 100, 1) : 0.0,
            );
        }

        return array(
            'periodo_cobro' => $rango,
            'vendedor' => $vendedor,
            'total' => $total,
            'sin_origen' => $sinOrigen,
            'mismo_mes' => $mismoMes,
            'pct_mismo_mes' => $total > 0 ? round(($mismoMes / $total) * 100, 1) : 0.0,
            'pct_con_origen' => $total > 0 ? round((($total - $sinOrigen) / $total) * 100, 1) : 0.0,
            'labels' => $labels,
            'montos' => $montos,
            'pcts' => $pcts,
            'filas' => $filas,
            'por_vendedor' => $porVendedor,
            'venta_periodo' => $ventaPeriodo,
            'recuperado_periodo' => $recuperadoPeriodo,
            'pendiente_periodo' => $pendientePeriodo,
            'pct_recup_periodo' => $pctRecupPeriodo,
            'filas_recuperacion' => $filasRecup,
            'total_recuperacion' => $recupTotal,
            'mensual' => $mensual,
            'mensual_labels' => $labelsMes,
            'mensual_pct_mismo_mes' => $pctMismoMesAnio,
            'mensual_ventas' => $ventasMes,
            'mensual_recuperado' => $recupMes,
            'mensual_pendiente' => $pendienteMes,
            'aging' => array(
                'labels' => $agingLabels,
                'montos' => $agingMontos,
                'filas' => $agingFilas,
                'total' => $agingTotal,
            ),
            'formula' => '% cobro mismo mes = cobro con origen en el mes del pago / total cobrado. % recup. ventas = cobrado hasta hoy de docs del período / ventas del período.',
            'fuente' => 'cuenta_ctejf efectivo sin IGV (÷1.18) + ventajf; origen = fecha_ori o fecha cargo (+)',
        );
    }

    /**
     * Listado CxC pendiente de recuperación de las ventas del período (modal).
     */
    public static function ctrPendienteRecuperacionDocs(array $filtros, $pagina = 1)
    {
        $rango = self::rangoPeriodoCobro($filtros);
        $vendedor = trim((string) $filtros['vendedor']);
        $pagina = max(1, (int) $pagina);

        $ventasPeriodo = ModeloDashboardGerencial::mdlVentasRango(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $ventaPeriodo = (float) $ventasPeriodo['total'];
        $recuperadoPeriodo = ModeloDashboardGerencial::mdlRecuperadoDeOrigenHasta(
            $rango['desde'],
            $rango['hasta'],
            $vendedor
        );
        $pendienteKpi = max(0.0, $ventaPeriodo - $recuperadoPeriodo);

        $raw = ModeloDashboardGerencial::mdlDocsPendienteRecuperacion(
            $rango['desde'],
            $rango['hasta'],
            $vendedor,
            $pagina,
            50
        );

        $saldoCartera = (float) $raw['total_saldo'];
        $diferencia = round($pendienteKpi - $saldoCartera, 2);

        return array(
            'periodo' => $rango,
            'vendedor' => $vendedor,
            'filas' => $raw['filas'],
            'venta_periodo' => $ventaPeriodo,
            'recuperado_periodo' => $recuperadoPeriodo,
            'pendiente_kpi' => $pendienteKpi,
            'total_saldo' => $saldoCartera,
            'diferencia' => $diferencia,
            'total_docs' => $raw['total_docs'],
            'pagina' => $raw['pagina'],
            'por_pagina' => $raw['por_pagina'],
            'paginas' => $raw['paginas'],
            'por_vendedor' => isset($raw['por_vendedor']) ? $raw['por_vendedor'] : array(),
            'nota' => 'El pendiente del cuadro = ventas − recuperado. El listado es cartera abierta (saldo > 0, sin vendedores 06*/08*). Por eso los montos pueden diferir.',
        );
    }

    /**
     * Cumplimiento de vencimientos (recomendación B):
     * documentos con fecha_ven en el período → a tiempo / atrasado / pendiente
     * según ult_pago (o último abono) vs fecha_ven.
     */
    public static function ctrPuntualidadVencimientos(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $mes = (int) $filtros['mes'];
        $vendedor = trim((string) $filtros['vendedor']);
        $labelsCortos = array(
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        );
        $mesesEtiqueta = self::ctrMesesEtiqueta();

        if ($mes === 0) {
            $desde = sprintf('%04d-01-01', $anio);
            $hasta = sprintf('%04d-12-31', $anio);
            $label = 'Año ' . $anio;
        } else {
            $desde = sprintf('%04d-%02d-01', $anio, $mes);
            $ultimo = (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
            $hasta = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimo);
            $label = (isset($mesesEtiqueta[$mes]) ? $mesesEtiqueta[$mes] : (string) $mes) . ' ' . $anio;
        }

        $raw = ModeloDashboardGerencial::mdlPuntualidadVencimiento($desde, $hasta, $vendedor);
        $total = (float) $raw['total'];
        $aTiempo = (float) $raw['a_tiempo'];
        $atrasado = (float) $raw['atrasado'];
        $pendiente = (float) $raw['pendiente'];

        $pct = function ($parte) use ($total) {
            return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
        };

        $mensualRaw = ModeloDashboardGerencial::mdlPuntualidadVencimientoMensualAnio($anio, $vendedor);
        $mensual = array();
        $labels = array();
        $montosATiempo = array();
        $montosAtrasado = array();
        $montosPendiente = array();
        $pctsATiempo = array();

        foreach ($mensualRaw as $fila) {
            $m = (int) $fila['mes'];
            $tot = (float) $fila['total'];
            $at = (float) $fila['a_tiempo'];
            $ad = (float) $fila['atrasado'];
            $pe = (float) $fila['pendiente'];
            $lab = isset($labelsCortos[$m]) ? $labelsCortos[$m] : (string) $m;
            $pctAt = $tot > 0 ? round(($at / $tot) * 100, 1) : 0.0;

            $mensual[] = array(
                'mes' => $m,
                'label' => $lab,
                'total' => $tot,
                'a_tiempo' => $at,
                'atrasado' => $ad,
                'pendiente' => $pe,
                'pct_a_tiempo' => $pctAt,
                'pct_atrasado' => $tot > 0 ? round(($ad / $tot) * 100, 1) : 0.0,
                'pct_pendiente' => $tot > 0 ? round(($pe / $tot) * 100, 1) : 0.0,
            );
            $labels[] = $lab;
            $montosATiempo[] = $at;
            $montosAtrasado[] = $ad;
            $montosPendiente[] = $pe;
            $pctsATiempo[] = $pctAt;
        }

        return array(
            'periodo' => array(
                'desde' => $desde,
                'hasta' => $hasta,
                'label' => $label,
                'anio' => $anio,
                'mes' => $mes,
            ),
            'vendedor' => $vendedor,
            'total' => $total,
            'a_tiempo' => $aTiempo,
            'atrasado' => $atrasado,
            'pendiente' => $pendiente,
            'pct_a_tiempo' => $pct($aTiempo),
            'pct_atrasado' => $pct($atrasado),
            'pct_pendiente' => $pct($pendiente),
            'docs_total' => (int) $raw['docs_total'],
            'docs_a_tiempo' => (int) $raw['docs_a_tiempo'],
            'docs_atrasado' => (int) $raw['docs_atrasado'],
            'docs_pendiente' => (int) $raw['docs_pendiente'],
            'mensual' => $mensual,
            'mensual_labels' => $labels,
            'mensual_a_tiempo' => $montosATiempo,
            'mensual_atrasado' => $montosAtrasado,
            'mensual_pendiente' => $montosPendiente,
            'mensual_pct_a_tiempo' => $pctsATiempo,
            'formula' => '% a tiempo = docs con fecha_ven en el período cuyo último pago ≤ fecha_ven / total docs vencidos del período (monto sin IGV).',
            'fuente' => 'cuenta_ctejf cargos (+); ult_pago o MAX(fecha) abonos (−) vs fecha_ven',
        );
    }

    /**
     * Bloque 8 — cobertura de proyección (cartera por fecha_ven vs cobranza del mes).
     * Distinto de puntualidad (ult_pago vs fecha_ven).
     *
     * KPI % cobertura = real_mes / proyeccion_mes × 100
     */
    public static function ctrProyeccionCobranzas(array $filtros, $limiteMeses = 6)
    {
        $anio = (int) $filtros['anio'];
        $mes = (int) $filtros['mes'];
        $vendedor = trim((string) $filtros['vendedor']);

        if ($mes === 0) {
            $mes = ($anio === (int) date('Y')) ? (int) date('n') : 12;
        }

        $filtrosCxc = ControladorDashboardCxc::ctrParseFiltros(array(
            'anio' => $anio,
            'mes' => $mes,
            'vendedor' => $vendedor,
            'todos_vendedores' => '1',
        ));

        $cxc = ControladorDashboardCxc::ctrProyeccionPagos($filtrosCxc, $limiteMeses);
        $realMes = self::obtenerCobranzaPeriodo($anio, $mes, $vendedor);

        $proyeccionMes = 0.0;
        $mesesChart = array();
        $labels = array();
        $montosProy = array();
        $montosReal = array();

        foreach ($cxc['meses'] as $fila) {
            $anioFila = (int) $fila['anio'];
            $mesFila = (int) $fila['mes'];
            // Cartera CxC viene con IGV; se muestra sin IGV (igual que cobranza real).
            $totalFila = ModeloDashboardGerencial::sinIgv($fila['total']);

            if ($anioFila === $anio && $mesFila === $mes) {
                $proyeccionMes = $totalFila;
            }

            $realFila = 0.0;
            if ($anioFila < (int) date('Y')
                || ($anioFila === (int) date('Y') && $mesFila <= (int) date('n'))
            ) {
                $realFila = self::obtenerCobranzaPeriodo($anioFila, $mesFila, $vendedor);
            }

            $labels[] = isset($fila['label']) ? $fila['label'] : ($mesFila . '/' . $anioFila);
            $montosProy[] = $totalFila;
            $montosReal[] = $realFila;

            $mesesChart[] = array(
                'anio' => $anioFila,
                'mes' => $mesFila,
                'label' => isset($fila['label']) ? $fila['label'] : '',
                'proyeccion' => $totalFila,
                'real' => $realFila,
                'facturas_guias' => ModeloDashboardGerencial::sinIgv($fila['facturas_guias']),
                'letras' => ModeloDashboardGerencial::sinIgv($fila['letras']),
                'otros' => ModeloDashboardGerencial::sinIgv($fila['otros']),
                'documentos' => (int) $fila['documentos'],
                'clientes' => (int) $fila['clientes'],
                'pct_cartera' => isset($fila['pct']) ? (float) $fila['pct'] : 0.0,
            );
        }

        $pctCumplimiento = $proyeccionMes > 0
            ? round(($realMes / $proyeccionMes) * 100, 1)
            : ($realMes > 0 ? 100.0 : 0.0);

        $mesesEtiqueta = self::ctrMesesEtiqueta();
        $totalesSinIgv = array();
        foreach ((array) $cxc['totales'] as $k => $v) {
            $totalesSinIgv[$k] = is_numeric($v) ? ModeloDashboardGerencial::sinIgv($v) : $v;
        }

        return array(
            'anio' => $anio,
            'mes' => $mes,
            'label_mes' => (isset($mesesEtiqueta[$mes]) ? $mesesEtiqueta[$mes] : (string) $mes) . ' ' . $anio,
            'vendedor' => $vendedor,
            'fecha_corte' => $filtrosCxc['fecha_corte'],
            'vencido' => self::bloqueCarteraSinIgv($cxc['vencido']),
            'incobrable' => self::bloqueCarteraSinIgv($cxc['incobrable']),
            'posterior' => self::bloqueCarteraSinIgv($cxc['posterior']),
            'meses' => $mesesChart,
            'totales' => $totalesSinIgv,
            'limite_meses' => $cxc['limite_meses'],
            'labels' => $labels,
            'montos_proyeccion' => $montosProy,
            'montos_real' => $montosReal,
            'proyeccion_mes' => $proyeccionMes,
            'real_mes' => $realMes,
            'delta_abs' => $realMes - $proyeccionMes,
            'pct_cumplimiento' => $pctCumplimiento,
            'pct_cobertura' => $pctCumplimiento,
            'nota' => 'Cobertura (no puntualidad): proyección = saldo por vencer (fecha_ven); real = cobranza efectiva del mes; % = real / proyección. Montos sin IGV.',
            'fuente' => 'dashboard-cxc (cartera) + cuenta_ctejf (real), sin IGV 18%',
        );
    }

    /** Ajusta montos de un bloque de cartera CxC a sin IGV (deja conteos intactos). */
    private static function bloqueCarteraSinIgv($bloque)
    {
        if (!is_array($bloque)) {
            return $bloque;
        }
        $out = $bloque;
        foreach (array('total', 'facturas_guias', 'letras', 'otros') as $campo) {
            if (isset($out[$campo]) && is_numeric($out[$campo])) {
                $out[$campo] = ModeloDashboardGerencial::sinIgv($out[$campo]);
            }
        }
        return $out;
    }

    private static function rangoPeriodoCobro(array $filtros)
    {
        if (isset($filtros['modo']) && $filtros['modo'] === 'periodos') {
            return array(
                'desde' => (string) $filtros['periodo_a_desde'],
                'hasta' => (string) $filtros['periodo_a_hasta'],
                'label' => $filtros['periodo_a_desde'] . ' → ' . $filtros['periodo_a_hasta'],
                'tipo' => 'periodos_a',
            );
        }

        $anio = (int) $filtros['anio'];
        $mes = (int) $filtros['mes'];

        if ($mes === 0) {
            return array(
                'desde' => sprintf('%04d-01-01', $anio),
                'hasta' => sprintf('%04d-12-31', $anio),
                'label' => 'Año ' . $anio,
                'tipo' => 'anio',
            );
        }

        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimo = (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
        $hasta = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimo);
        $meses = self::ctrMesesEtiqueta();

        return array(
            'desde' => $desde,
            'hasta' => $hasta,
            'label' => (isset($meses[$mes]) ? $meses[$mes] : (string) $mes) . ' ' . $anio,
            'tipo' => 'mes',
            'anio' => $anio,
            'mes' => $mes,
        );
    }

    private static function armarComparativoPeriodos(array $filtros, $metodoModelo, $fuente, $keyA, $keyB)
    {
        $vendedor = trim((string) $filtros['vendedor']);
        $desdeA = (string) $filtros['periodo_a_desde'];
        $hastaA = (string) $filtros['periodo_a_hasta'];
        $desdeB = (string) $filtros['periodo_b_desde'];
        $hastaB = (string) $filtros['periodo_b_hasta'];

        $serieA = call_user_func(array('ModeloDashboardGerencial', $metodoModelo), $desdeA, $hastaA, $vendedor);
        $serieB = call_user_func(array('ModeloDashboardGerencial', $metodoModelo), $desdeB, $hastaB, $vendedor);

        $labelsCortos = array(
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        );

        $porMesNumA = self::agruparVentasPorNumeroMes($serieA['por_mes']);
        $porMesNumB = self::agruparVentasPorNumeroMes($serieB['por_mes']);
        $meses = array_unique(array_merge(array_keys($porMesNumA), array_keys($porMesNumB)));
        sort($meses, SORT_NUMERIC);

        $filas = array();
        $montosA = array();
        $montosB = array();
        $labelsMes = array();

        foreach ($meses as $mes) {
            $mes = (int) $mes;
            $montoA = isset($porMesNumA[$mes]) ? (float) $porMesNumA[$mes] : 0.0;
            $montoB = isset($porMesNumB[$mes]) ? (float) $porMesNumB[$mes] : 0.0;
            $label = isset($labelsCortos[$mes]) ? $labelsCortos[$mes] : (string) $mes;

            $labelsMes[] = $label;
            $montosA[] = $montoA;
            $montosB[] = $montoB;

            $filas[] = array(
                'mes' => $mes,
                'label' => $label,
                $keyA => $montoA,
                $keyB => $montoB,
                'delta_abs' => $montoA - $montoB,
                'delta_pct' => self::variacionPorcentaje($montoA, $montoB),
            );
        }

        $totalA = (float) $serieA['total'];
        $totalB = (float) $serieB['total'];

        return array(
            'vendedor' => $vendedor,
            'periodo_a' => array(
                'desde' => $desdeA,
                'hasta' => $hastaA,
                'label' => $desdeA . ' → ' . $hastaA,
                'total' => $totalA,
            ),
            'periodo_b' => array(
                'desde' => $desdeB,
                'hasta' => $hastaB,
                'label' => $desdeB . ' → ' . $hastaB,
                'total' => $totalB,
            ),
            'labels_totales' => array('Período A', 'Período B'),
            'montos_totales' => array($totalA, $totalB),
            'labels_mes' => $labelsMes,
            'montos_a' => $montosA,
            'montos_b' => $montosB,
            'filas' => $filas,
            'delta_abs' => $totalA - $totalB,
            'delta_pct' => self::variacionPorcentaje($totalA, $totalB),
            'fuente' => $fuente,
        );
    }

    /**
     * @param array<string,float> $porMesClave claves Y-m
     * @return array<int,float> mes (1-12) => monto
     */
    private static function agruparVentasPorNumeroMes(array $porMesClave)
    {
        $out = array();
        foreach ($porMesClave as $clave => $venta) {
            $partes = explode('-', (string) $clave);
            if (count($partes) < 2) {
                continue;
            }
            $mes = (int) $partes[1];
            if ($mes < 1 || $mes > 12) {
                continue;
            }
            if (!isset($out[$mes])) {
                $out[$mes] = 0.0;
            }
            $out[$mes] += (float) $venta;
        }

        return $out;
    }

    private static function mesHastaSerie($anio)
    {
        $anio = (int) $anio;
        if ($anio === (int) date('Y')) {
            return (int) date('n');
        }

        return 12;
    }

    private static function serieVentasAnio($anio, $vendedor, $mesHasta)
    {
        $vendedor = trim((string) $vendedor);
        if ($vendedor === '') {
            $porMes = ModeloDashboardGerencial::mdlVentasMensualGlobal($anio);
        } else {
            $porMes = ModeloDashboardGerencial::mdlVentasMensualVendedor($anio, $vendedor);
        }

        return self::armarSerieMensual($porMes, $mesHasta, 'venta');
    }

    private static function serieCobranzasAnio($anio, $vendedor, $mesHasta)
    {
        $vendedor = trim((string) $vendedor);
        if ($vendedor === '') {
            $porMes = ModeloDashboardGerencial::mdlCobranzasMensualGlobal($anio);
        } else {
            $porMes = ModeloDashboardGerencial::mdlCobranzasMensualVendedor($anio, $vendedor);
        }

        return self::armarSerieMensual($porMes, $mesHasta, 'venta');
    }

    private static function armarSerieMensual(array $porMes, $mesHasta, $campoMonto)
    {
        $mesHasta = max(1, min(12, (int) $mesHasta));
        $labelsCortos = array(
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        );
        $labelsLargos = self::ctrMesesEtiqueta();

        $labels = array();
        $montos = array();
        $filas = array();
        $total = 0.0;

        for ($mes = 1; $mes <= $mesHasta; $mes++) {
            $monto = isset($porMes[$mes]) ? (float) $porMes[$mes] : 0.0;
            $labels[] = $labelsCortos[$mes];
            $montos[] = $monto;
            $total += $monto;
            $filas[] = array(
                'mes' => $mes,
                'label' => $labelsLargos[$mes],
                'label_corto' => $labelsCortos[$mes],
                $campoMonto => $monto,
                'pct' => 0.0,
            );
        }

        foreach ($filas as &$fila) {
            $fila['pct'] = $total > 0 ? round(($fila[$campoMonto] / $total) * 100, 1) : 0.0;
        }
        unset($fila);

        return array(
            'labels' => $labels,
            'montos' => $montos,
            'filas' => $filas,
            'total' => $total,
        );
    }

    private static function parseRangoFechas($desdeRaw, $hastaRaw, $anioDefault, $mesDefault, $etiqueta)
    {
        $desde = self::normalizarFecha($desdeRaw);
        $hasta = self::normalizarFecha($hastaRaw);

        if ($desde === null || $hasta === null) {
            $mesDefault = (int) $mesDefault;
            $anioDefault = (int) $anioDefault;

            if ($mesDefault === 0) {
                $desde = sprintf('%04d-01-01', $anioDefault);
                $hasta = sprintf('%04d-12-31', $anioDefault);
            } else {
                $desde = sprintf('%04d-%02d-01', $anioDefault, $mesDefault);
                $ultimo = (int) date('t', mktime(0, 0, 0, $mesDefault, 1, $anioDefault));
                $hasta = sprintf('%04d-%02d-%02d', $anioDefault, $mesDefault, $ultimo);
            }
        }

        if ($desde > $hasta) {
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        return array(
            'desde' => $desde,
            'hasta' => $hasta,
            'etiqueta' => $etiqueta,
        );
    }

    private static function normalizarFecha($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = trim((string) $valor);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        $partes = explode('-', $valor);
        if (!checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
            return null;
        }

        return $valor;
    }

    private static function variacionPorcentaje($actual, $anterior)
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;

        if ($anterior == 0) {
            return $actual == 0 ? 0.0 : 100.0;
        }

        return (($actual - $anterior) / abs($anterior)) * 100;
    }

    private static function mesHastaYtd($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        $hoyAnio = (int) date('Y');
        $hoyMes = (int) date('n');

        if ($mes === 0) {
            return ($anio === $hoyAnio) ? $hoyMes : 12;
        }

        if ($anio === $hoyAnio) {
            return min($mes, $hoyMes);
        }

        return $mes;
    }

    private static function obtenerVentaPeriodo($anio, $mes, $vendedor)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        $vendedor = trim((string) $vendedor);

        if ($mes === 0) {
            return self::obtenerVentaYtd($anio, 12, $vendedor);
        }

        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $ventas = ModeloDashboardGerencial::mdlVentasRango($desde, $hasta, $vendedor);

        return (float) $ventas['total'];
    }

    private static function obtenerCobranzaPeriodo($anio, $mes, $vendedor)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        $vendedor = trim((string) $vendedor);

        if ($mes === 0) {
            return self::obtenerCobranzaYtd($anio, 12, $vendedor);
        }

        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $cobranzas = ModeloDashboardGerencial::mdlCobranzasRango($desde, $hasta, $vendedor);

        return (float) $cobranzas['total'];
    }

    private static function obtenerVentaYtd($anio, $mesHasta, $vendedor)
    {
        $anio = (int) $anio;
        $hasta = self::mesHastaYtd($anio, (int) $mesHasta);
        $total = 0.0;

        for ($m = 1; $m <= $hasta; $m++) {
            $total += self::obtenerVentaPeriodo($anio, $m, $vendedor);
        }

        return $total;
    }

    private static function obtenerCobranzaYtd($anio, $mesHasta, $vendedor)
    {
        $anio = (int) $anio;
        $hasta = self::mesHastaYtd($anio, (int) $mesHasta);
        $total = 0.0;

        for ($m = 1; $m <= $hasta; $m++) {
            $total += self::obtenerCobranzaPeriodo($anio, $m, $vendedor);
        }

        return $total;
    }
}
