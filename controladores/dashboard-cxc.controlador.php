<?php

require_once dirname(__FILE__) . '/dashboard-cxc.config.php';

class ControladorDashboardCxc
{
    public static function ctrAnnosPermitidos()
    {
        return dashboardCxcAnnosPermitidos();
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
            $anio = in_array((int) date('Y'), $annos, true) ? (int) date('Y') : $annos[count($annos) - 1];
        }

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        $vendedor = isset($entrada['vendedor']) ? trim((string) $entrada['vendedor']) : '';
        $cliente = isset($entrada['cliente']) ? trim((string) $entrada['cliente']) : '';
        $zona = isset($entrada['zona']) ? (int) $entrada['zona'] : 0;
        $rango = isset($entrada['rango']) ? trim((string) $entrada['rango']) : '';

        $rangosValidos = array('', 'por-vencer', '0-30', '31-60', '61-90', '91-180', '180+', 'incobrable');
        if (!in_array($rango, $rangosValidos, true)) {
            $rango = '';
        }

        $todosVendedores = false;
        if (isset($entrada['todos_vendedores'])) {
            $rawTodos = $entrada['todos_vendedores'];
            $todosVendedores = in_array((string) $rawTodos, array('1', 'true', 'on', 'si', 'sí'), true);
        }

        return array(
            'anio' => $anio,
            'mes' => $mes,
            'fecha_corte' => ModeloDashboardCxc::mdlFechaCorteDesdePeriodo($anio, $mes),
            'vendedor' => $vendedor,
            'cliente' => $cliente,
            'zona' => max(0, $zona),
            'rango' => $rango,
            'todos_vendedores' => $todosVendedores,
        );
    }

    public static function ctrVendedoresFiltro()
    {
        return ModeloDashboardCxc::mdlVendedoresFiltro();
    }

    public static function ctrKpis(array $filtros)
    {
        return ModeloDashboardCxc::mdlKpis($filtros);
    }

    private static function periodoAnterior($anio, $mes)
    {
        $mesAnt = (int) $mes - 1;
        $anioAnt = (int) $anio;

        if ($mesAnt < 1) {
            $mesAnt = 12;
            $anioAnt--;
        }

        return array('anio' => $anioAnt, 'mes' => $mesAnt);
    }

    private static function variacionPorcentaje($actual, $anterior)
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;

        if ($anterior == 0) {
            if ($actual == 0) {
                return 0;
            }
            return 100;
        }

        return (($actual - $anterior) / abs($anterior)) * 100;
    }

    private static function sumarCampoAvance(array $filas, $campo)
    {
        $total = 0;

        foreach ($filas as $fila) {
            $total += (float) $fila[$campo];
        }

        return $total;
    }

    /**
     * KPIs de cabecera alineados al mockup: ventas, meta, cobranza y CxC.
     */
    public static function ctrKpisCabecera(array $filtros)
    {
        $anio = (int) $filtros['anio'];
        $mes = (int) $filtros['mes'];
        $vendedor = trim((string) $filtros['vendedor']);
        $ant = self::periodoAnterior($anio, $mes);

        $cxc = self::ctrKpis($filtros);
        $totalCxc = (float) $cxc['total_por_cobrar'];

        $filasAvance = ModeloMetasVendedor::mdlAvanceVentasDashboard($anio, $mes, $vendedor);
        $filasAvanceAnt = ModeloMetasVendedor::mdlAvanceVentasDashboard($ant['anio'], $ant['mes'], $vendedor);

        $ventaMes = self::sumarCampoAvance($filasAvance, 'venta_real');
        $ventaMesAnt = self::sumarCampoAvance($filasAvanceAnt, 'venta_real');
        $metaMes = self::sumarCampoAvance($filasAvance, 'meta_venta');

        if ($vendedor === '') {
            $totales = ControladorMovimientos::ctrTotalesSolesGerencia($anio, $mes);
            $totalesAnt = ControladorMovimientos::ctrTotalesSolesGerencia($ant['anio'], $ant['mes']);

            if (is_array($totales) && isset($totales['vtas_soles'])) {
                $ventaMes = (float) $totales['vtas_soles'];
            }
            if (is_array($totalesAnt) && isset($totalesAnt['vtas_soles'])) {
                $ventaMesAnt = (float) $totalesAnt['vtas_soles'];
            }
        }

        $pctMeta = $metaMes > 0 ? round(($ventaMes / $metaMes) * 100, 1) : 0;

        $cobranzaMes = 0;
        $cobranzaVar = 0;

        if ($vendedor === '') {
            $totales = ControladorMovimientos::ctrTotalesSolesGerencia($anio, $mes);
            $totalesAnt = ControladorMovimientos::ctrTotalesSolesGerencia($ant['anio'], $ant['mes']);
            $cobranzaMes = is_array($totales) && isset($totales['pagos_soles']) ? (float) $totales['pagos_soles'] : 0;
            $cobranzaAnt = is_array($totalesAnt) && isset($totalesAnt['pagos_soles']) ? (float) $totalesAnt['pagos_soles'] : 0;
            $cobranzaVar = self::variacionPorcentaje($cobranzaMes, $cobranzaAnt);
        } else {
            $cobranzaKpi = ControladorDashboardCobranzas::ctrKpisSuperiores($anio, $mes, $vendedor);
            $cobranzaMes = (float) $cobranzaKpi['cobranza_total'];
            $cobranzaVar = (float) $cobranzaKpi['cobranza_total_var'];
        }

        return array(
            'anio_anterior' => $ant['anio'],
            'mes_anterior' => $ant['mes'],
            'venta_mes' => $ventaMes,
            'venta_mes_var' => self::variacionPorcentaje($ventaMes, $ventaMesAnt),
            'meta_mes' => $metaMes,
            'pct_meta' => $pctMeta,
            'cobranza_mes' => $cobranzaMes,
            'cobranza_mes_var' => $cobranzaVar,
            'total_por_cobrar' => $totalCxc,
            'monto_vencido' => (float) $cxc['monto_vencido'],
            'pct_vencido_cxc' => $totalCxc > 0 ? round(((float) $cxc['monto_vencido'] / $totalCxc) * 100, 1) : 0,
            'monto_incobrable' => (float) $cxc['monto_incobrable'],
            'pct_incobrable_cxc' => $totalCxc > 0 ? round(((float) $cxc['monto_incobrable'] / $totalCxc) * 100, 1) : 0,
        );
    }

    public static function ctrResumenCobranza(array $filtros)
    {
        $kpis = self::ctrKpis($filtros);
        $antiguedad = self::ctrAntiguedad($filtros);
        $total = (float) $kpis['total_por_cobrar'];

        $montoVencido = (float) $kpis['monto_vencido'];

        $rangosDonut = array();
        if (!empty($antiguedad['rangos'])) {
            foreach ($antiguedad['rangos'] as $rango) {
                if ($rango['id'] === 'incobrable' || $rango['id'] === 'por-vencer') {
                    continue;
                }
                $rangosDonut[] = $rango;
            }
        }

        foreach ($rangosDonut as &$rangoDonut) {
            $rangoDonut['porcentaje'] = $montoVencido > 0
                ? round(((float) $rangoDonut['monto'] / $montoVencido) * 100, 1)
                : 0;
        }
        unset($rangoDonut);

        $proy30 = (float) $kpis['cobranza_proyectada_30'];
        $proy30Ant = (float) $kpis['cobranza_proyectada_30_ant'];

        return array(
            'total_por_cobrar' => $total,
            'monto_vencido' => (float) $kpis['monto_vencido'],
            'pct_vencido' => $total > 0 ? round(((float) $kpis['monto_vencido'] / $total) * 100, 1) : 0,
            'monto_por_vencer' => (float) $kpis['monto_por_vencer'],
            'pct_por_vencer' => $total > 0 ? round(((float) $kpis['monto_por_vencer'] / $total) * 100, 1) : 0,
            'monto_incobrable' => (float) $kpis['monto_incobrable'],
            'pct_incobrable' => $total > 0 ? round(((float) $kpis['monto_incobrable'] / $total) * 100, 1) : 0,
            'cobranza_proyectada_30' => $proy30,
            'cobranza_proyectada_30_ant' => $proy30Ant,
            'cobranza_proyectada_30_var' => self::variacionPorcentaje($proy30, $proy30Ant),
            'antiguedad' => array(
                'rangos' => $rangosDonut,
                'total' => $montoVencido,
            ),
        );
    }

    public static function ctrAntiguedad(array $filtros)
    {
        $fila = ModeloDashboardCxc::mdlAntiguedad($filtros);

        if (!$fila) {
            return array(
                'rangos' => array(),
                'total' => 0,
            );
        }

        $totalVencido = (float) $fila['rango_0_30']
            + (float) $fila['rango_31_60']
            + (float) $fila['rango_61_90']
            + (float) $fila['rango_91_180']
            + (float) $fila['rango_180_mas'];

        $total = $totalVencido
            + (float) $fila['por_vencer']
            + (float) $fila['incobrables'];

        $definiciones = array(
            array('id' => '0-30', 'label' => '0–30 días', 'monto' => (float) $fila['rango_0_30'], 'clientes' => (int) $fila['clientes_0_30'], 'color' => '#6BCB9A'),
            array('id' => '31-60', 'label' => '31–60 días', 'monto' => (float) $fila['rango_31_60'], 'clientes' => (int) $fila['clientes_31_60'], 'color' => '#FFD166'),
            array('id' => '61-90', 'label' => '61–90 días', 'monto' => (float) $fila['rango_61_90'], 'clientes' => (int) $fila['clientes_61_90'], 'color' => '#FFAB6B'),
            array('id' => '91-180', 'label' => '91–180 días', 'monto' => (float) $fila['rango_91_180'], 'clientes' => (int) $fila['clientes_91_180'], 'color' => '#FF7B7B'),
            array('id' => '180+', 'label' => '+180 días', 'monto' => (float) $fila['rango_180_mas'], 'clientes' => (int) $fila['clientes_180_mas'], 'color' => '#E56B8A'),
            array('id' => 'incobrable', 'label' => 'Incobrables', 'monto' => (float) $fila['incobrables'], 'clientes' => (int) $fila['clientes_incobrables'], 'color' => '#A78BFA'),
        );

        foreach ($definiciones as &$item) {
            if ($item['id'] === 'incobrable') {
                $item['porcentaje'] = $total > 0 ? round(($item['monto'] / $total) * 100, 1) : 0;
            } else {
                $item['porcentaje'] = $totalVencido > 0 ? round(($item['monto'] / $totalVencido) * 100, 1) : 0;
            }
        }
        unset($item);

        return array(
            'rangos' => $definiciones,
            'total' => $total,
            'total_vencido' => $totalVencido,
            'nota' => 'Los rangos 0–30…+180 solo incluyen documentos vencidos. Incobrables se muestran aparte por regla de vendedor.',
        );
    }

    public static function ctrPorVendedor(array $filtros)
    {
        return ModeloDashboardCxc::mdlPorVendedor($filtros);
    }

    public static function ctrTablasVendedor(array $filtros)
    {
        return array(
            'cartera' => self::ctrPorVendedor($filtros),
            'ventas' => self::ctrVentasPorVendedor($filtros),
        );
    }

    public static function ctrPorRango(array $filtros)
    {
        return ModeloDashboardCxc::mdlPorRango($filtros);
    }

    public static function ctrProyeccionPagos(array $filtros, $limiteMeses = 6)
    {
        $mesesEtiqueta = self::ctrMesesEtiqueta();
        $kpis = ModeloDashboardCxc::mdlKpis($filtros);
        $vencido = ModeloDashboardCxc::mdlProyeccionPagosResumenVencido($filtros);
        $incobrable = ModeloDashboardCxc::mdlProyeccionPagosResumenIncobrable($filtros);
        $filas = ModeloDashboardCxc::mdlProyeccionPagosMensual($filtros, $limiteMeses);

        $totalProyeccion = 0;

        foreach ($filas as &$fila) {
            $mes = (int) $fila['mes'];
            $anio = (int) $fila['anio'];
            $nomMes = isset($mesesEtiqueta[$mes]) ? $mesesEtiqueta[$mes] : (string) $mes;
            $fila['label'] = $nomMes . ' ' . $anio;
            $fila['tipo'] = 'mes';

            $totalProyeccion += (float) $fila['total'];
        }
        unset($fila);

        $totalGeneral = (float) $kpis['total_por_cobrar'];
        $basePct = $totalGeneral > 0 ? $totalGeneral : 0;
        $posterior = max(0, (float) $kpis['monto_por_vencer'] - $totalProyeccion);

        $vencido['label'] = 'Vencido (pendiente)';
        $vencido['tipo'] = 'vencido';
        $vencido['pct'] = $basePct > 0 ? round(((float) $vencido['total'] / $basePct) * 100, 1) : 0;

        $incobrable['label'] = 'Incobrables';
        $incobrable['tipo'] = 'incobrable';
        $incobrable['pct'] = $basePct > 0 ? round(((float) $incobrable['total'] / $basePct) * 100, 1) : 0;

        foreach ($filas as &$fila) {
            $fila['pct'] = $basePct > 0 ? round(((float) $fila['total'] / $basePct) * 100, 1) : 0;
        }
        unset($fila);

        $posteriorFila = null;
        if ($posterior > 0) {
            $posteriorFila = array(
                'label' => 'Por vencer (después de ' . (int) $limiteMeses . ' meses)',
                'tipo' => 'posterior',
                'facturas_guias' => 0,
                'letras' => 0,
                'otros' => $posterior,
                'total' => $posterior,
                'documentos' => 0,
                'clientes' => 0,
                'pct' => $basePct > 0 ? round(($posterior / $basePct) * 100, 1) : 0,
            );
        }

        return array(
            'vencido' => $vencido,
            'incobrable' => $incobrable,
            'posterior' => $posteriorFila,
            'meses' => $filas,
            'totales' => array(
                'vencido' => (float) $vencido['total'],
                'incobrable' => (float) $incobrable['total'],
                'proyeccion' => $totalProyeccion,
                'posterior' => $posterior,
                'por_vencer' => (float) $kpis['monto_por_vencer'],
                'general' => $totalGeneral,
            ),
            'limite_meses' => max(1, min(12, (int) $limiteMeses)),
            'nota' => 'Vencido = saldo vencido al corte (sin incobrables). Total = Por vencer + Vencido + Incobrables. Fact./guías, letras (banco 02) y otros cuadran con el total de cada fila.',
        );
    }

    public static function ctrTopClientes(array $filtros, $limite = 10)
    {
        $filas = ModeloDashboardCxc::mdlTopClientes($filtros, $limite);

        return ControladorLineaCredito::ctrEnriquecerLineasReferenciaTopDeuda($filas);
    }

    public static function ctrDetalleDocumentos(array $filtros, $pagina = 1, $porPagina = 25, $orden = 'vencido_desc')
    {
        $total = ModeloDashboardCxc::mdlConteoDetalle($filtros);
        $filas = ModeloDashboardCxc::mdlDetalleDocumentos($filtros, $pagina, $porPagina, $orden);
        $saldoTotal = ModeloDashboardCxc::mdlTotalesDetalleFiltrado($filtros);
        $porPagina = max(10, min(100, (int) $porPagina));
        $paginas = $porPagina > 0 ? (int) ceil($total / $porPagina) : 0;

        return array(
            'filas' => $filas,
            'paginacion' => array(
                'pagina' => max(1, (int) $pagina),
                'por_pagina' => $porPagina,
                'total_registros' => $total,
                'total_paginas' => $paginas,
            ),
            'saldo_total' => $saldoTotal,
        );
    }

    public static function ctrVentasPorVendedor(array $filtros)
    {
        return ModeloDashboardCxc::mdlVentasPorVendedor($filtros);
    }

    public static function ctrVentasPorTipoDocumento(array $filtros)
    {
        return ModeloDashboardCxc::mdlVentasPorTipoDocumento($filtros);
    }

    public static function ctrVentasPorZona(array $filtros)
    {
        return ModeloDashboardCxc::mdlVentasPorZona($filtros);
    }

    public static function ctrVentasTendencia(array $filtros)
    {
        $tendencia = ModeloDashboardCxc::mdlVentasTendenciaDiaria($filtros);
        $meses = self::ctrMesesEtiqueta();

        $tendencia['mes_actual']['label'] = isset($meses[$tendencia['mes_actual']['mes']])
            ? $meses[$tendencia['mes_actual']['mes']] . ' ' . $tendencia['mes_actual']['anio']
            : '';
        $tendencia['mes_anterior']['label'] = isset($meses[$tendencia['mes_anterior']['mes']])
            ? $meses[$tendencia['mes_anterior']['mes']] . ' ' . $tendencia['mes_anterior']['anio']
            : '';

        return $tendencia;
    }

    public static function ctrDatosVentas(array $filtros)
    {
        return array(
            'por_vendedor' => self::ctrVentasPorVendedor($filtros),
            'por_tipo_documento' => self::ctrVentasPorTipoDocumento($filtros),
            'por_zona' => self::ctrVentasPorZona($filtros),
            'tendencia' => self::ctrVentasTendencia($filtros),
        );
    }

    public static function ctrDatosDashboard(array $filtros, $paginaDetalle = 1, $porPaginaDetalle = 25)
    {
        return array(
            'filtros' => $filtros,
            'kpis' => self::ctrKpis($filtros),
            'antiguedad' => self::ctrAntiguedad($filtros),
            'por_vendedor' => self::ctrPorVendedor($filtros),
            'por_rango' => self::ctrPorRango($filtros),
            'top_clientes' => self::ctrTopClientes($filtros, 10),
            'detalle' => self::ctrDetalleDocumentos($filtros, $paginaDetalle, $porPaginaDetalle),
        );
    }
}
