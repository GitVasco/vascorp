<?php

require_once __DIR__ . '/cuentas.controlador.php';
require_once __DIR__ . '/../modelos/cuentas.modelo.php';
require_once __DIR__ . '/reportes-estado-cuenta.lib.php';
require_once __DIR__ . '/reportes-generales-v2.letras.lib.php';
require_once __DIR__ . '/reportes-generales-v2.fase3.lib.php';

class ReportesGeneralesV2Servicio
{
    const PREVIEW_LIMIT = 500;
    const EXPORT_LIMIT = 50000;

    /** @var int|null null = vista previa; EXPORT_LIMIT = exportación */
    private static $rowLimit = null;

    /** Orden fijo v2 cobranza — no exponer combinaciones legacy en UI */
    const ORDEN1_COBRANZA = 'tipo';
    const ORDEN2_COBRANZA = 'ordNumCuenta';

    public static function preview($reporteId, $filtros)
    {
        switch ($reporteId) {
            case 'doc_por_cobrar':
                return self::previewCobranza($filtros, 'cobrar', 'Total general');
            case 'doc_vencidos':
                return self::previewCobranza($filtros, 'vencidos', 'Total vencidos');
            case 'doc_no_vencidos':
                return self::previewCobranza($filtros, 'no_vencidos', 'Total no vencidos');
            case 'doc_protestados':
                return self::previewCobranza($filtros, 'protestados', 'Total protestados');
            case 'pagos':
                return self::previewPagos($filtros);
            case 'estado_cuenta':
                return self::previewEstadoCuenta($filtros);
            case 'saldos_fecha':
                return self::previewSaldosFecha($filtros);
            case 'letras_por_imprimir':
                return self::previewLetrasPorImprimir($filtros);
            case 'letras_por_aceptar':
                return self::previewLetrasPorAceptar($filtros);
            case 'letras_en_cartera':
                return self::previewLetrasEnCartera($filtros);
            case 'doc_cancelados':
                return self::previewDocCancelados($filtros);
            case 'doc_por_banco_estado':
                return self::previewInformeBancoEstado($filtros);
            case 'doc_por_estado_banco':
                return self::previewInformeEstadoBanco($filtros);
            case 'movimientos_ctacte':
                return self::previewMovimientos($filtros);
            case 'resumen_saldos_fecha':
                return self::previewResumenSaldosFecha($filtros);
            default:
                return array('ok' => false, 'error' => 'Reporte sin implementar.');
        }
    }

    /**
     * Datos completos para exportar (misma lógica que preview, sin tope de 500 filas).
     */
    public static function exportPayload($reporteId, $filtros)
    {
        self::$rowLimit = self::EXPORT_LIMIT;
        try {
            $result = self::preview($reporteId, $filtros);
            if (!is_array($result) || empty($result['ok'])) {
                return $result;
            }
            $tpl = ReportesGeneralesV2Config::find($reporteId);
            $result['reporteId'] = $reporteId;
            $result['title'] = ($tpl && isset($tpl['title'])) ? $tpl['title'] : $reporteId;
            return $result;
        } finally {
            self::$rowLimit = null;
        }
    }

    public static function exportUrl($formato, $reporteId, $filtros)
    {
        $tpl = ReportesGeneralesV2Config::find($reporteId);
        if ($tpl === null) {
            return array('ok' => false, 'error' => 'Plantilla de reporte no encontrada.');
        }
        if ($tpl['estado'] !== 'listo') {
            return array('ok' => false, 'error' => 'Reporte aún no disponible para exportar.');
        }

        $cap = ReportesGeneralesV2Config::exportCapacidades($tpl);
        if ($formato === 'pdf' && empty($cap['pdf'])) {
            return array('ok' => false, 'error' => 'PDF no disponible para este reporte.');
        }
        if (($formato === 'xlsx' || $formato === 'excel') && empty($cap['excel'])) {
            return array('ok' => false, 'error' => 'Excel no disponible para este reporte.');
        }

        $fmt = ($formato === 'pdf') ? 'pdf' : 'xlsx';
        $params = array(
            'formato' => $fmt,
            'reporte' => $reporteId,
        );
        $keys = array('orden1', 'orden2', 'tip_doc', 'canc', 'cli', 'vend', 'banco', 'inicio', 'fin');
        foreach ($keys as $key) {
            if (isset($filtros[$key]) && trim((string) $filtros[$key]) !== '') {
                $params[$key] = trim((string) $filtros[$key]);
            }
        }

        return array(
            'ok' => true,
            'url' => 'vistas/reportes_excel/rgv2_export.php?' . http_build_query($params),
        );
    }

    private static function currentLimit()
    {
        if (self::$rowLimit !== null) {
            return (int) self::$rowLimit;
        }
        return self::PREVIEW_LIMIT;
    }

    private static function normalizeBase($filtros)
    {
        $f = $filtros;
        $keys = array('tip_doc', 'cli', 'vend', 'banco', 'canc', 'inicio', 'fin');
        foreach ($keys as $key) {
            if (!isset($f[$key])) {
                $f[$key] = '';
            } else {
                $f[$key] = trim((string) $f[$key]);
            }
        }
        return $f;
    }

    private static function normalizeForReporte($reporteId, $filtros)
    {
        if ($reporteId === 'pagos') {
            return self::normalizePagosFiltros($filtros);
        }
        if ($reporteId === 'estado_cuenta') {
            return ReportesEstadoCuentaLib::prepararFiltros($filtros);
        }
        if ($reporteId === 'saldos_fecha') {
            return self::normalizeSaldosFechaFiltros($filtros);
        }
        if ($reporteId === 'letras_por_imprimir' || $reporteId === 'letras_en_cartera'
            || $reporteId === 'letras_por_aceptar' || $reporteId === 'doc_cancelados') {
            return self::normalizeBase($filtros);
        }
        if ($reporteId === 'resumen_saldos_fecha') {
            return self::normalizeSaldosFechaFiltros($filtros);
        }
        if ($reporteId === 'movimientos_ctacte' || $reporteId === 'doc_por_banco_estado'
            || $reporteId === 'doc_por_estado_banco') {
            return self::normalizeBase($filtros);
        }
        return self::normalizeFiltrosCobranza($filtros);
    }

    private static function normalizeFiltrosCobranza($filtros)
    {
        $f = self::normalizeBase($filtros);
        $f['orden1'] = self::ORDEN1_COBRANZA;
        $f['orden2'] = self::ORDEN2_COBRANZA;
        if ($f['fin'] === '') {
            $f['fin'] = date('Y-m-d');
        }
        return $f;
    }

    private static function normalizePagosFiltros($filtros)
    {
        $f = self::normalizeBase($filtros);
        if ($f['vend'] !== '') {
            $f['orden1'] = 'vendedor';
        } else {
            $f['orden1'] = 'fecha_pag';
        }
        $f['orden2'] = 'ordNumCuenta';
        $f['canc_param'] = ($f['canc'] === '') ? 'todo' : $f['canc'];
        return $f;
    }

    private static function normalizeSaldosFechaFiltros($filtros)
    {
        $f = self::normalizeBase($filtros);
        if ($f['fin'] === '') {
            $f['fin'] = date('Y-m-d');
        }
        if ($f['inicio'] === '') {
            $f['inicio'] = $f['fin'];
        }
        return $f;
    }

    /** @deprecated use normalizeFiltrosCobranza */
    private static function normalizeFiltros($filtros)
    {
        return self::normalizeFiltrosCobranza($filtros);
    }

    private static function previewCobranza($filtros, $tipo, $totalLabel)
    {
        $f = self::normalizeFiltrosCobranza($filtros);
        $rows = self::fetchCobranzaRows($tipo, $f);
        if (!is_array($rows)) {
            $rows = array();
        }
        $rows = self::aplicarFiltrosCobranza($rows, $f);
        $total = self::calcularTotalFilas($rows);
        return self::buildPreviewResponse($rows, $total, $totalLabel, self::columnasCobranza(), 'mapRowCobranza', array());
    }

    private static function previewPagos($filtros)
    {
        $f = self::normalizePagosFiltros($filtros);
        if ($f['inicio'] === '' || $f['fin'] === '') {
            return array('ok' => false, 'error' => 'Indique fecha inicio y fin.');
        }

        $rows = ControladorCuentas::ctrMostrarReportePagos(
            $f['orden1'],
            $f['orden2'],
            $f['canc_param'],
            $f['vend'],
            $f['inicio'],
            $f['fin'],
            $f['cli']
        );
        if (!is_array($rows)) {
            $rows = array();
        }
        $rows = self::filtrarFilasDetallePagos($rows);
        $rows = self::aplicarFiltrosPagosPostQuery($rows, $f);

        $total = ControladorCuentas::ctrMostrarReporteTotalPagos(
            $f['orden1'],
            $f['orden2'],
            $f['canc_param'],
            $f['vend'],
            $f['inicio'],
            $f['fin'],
            $f['cli']
        );

        $kpisExtra = array();
        if (is_array($total)) {
            if (isset($total['fact']) && $total['fact'] !== '') {
                $kpisExtra[] = array('label' => 'Total facturas', 'value' => 'S/ ' . $total['fact']);
            }
            if (isset($total['letra']) && $total['letra'] !== '') {
                $kpisExtra[] = array('label' => 'Total letras', 'value' => 'S/ ' . $total['letra']);
            }
        }

        return self::buildPreviewResponse($rows, array(), '', self::columnasPagos(), 'mapRowPagos', $kpisExtra);
    }

    private static function previewEstadoCuenta($filtros)
    {
        $f = ReportesEstadoCuentaLib::prepararFiltros($filtros);
        $validacion = ReportesEstadoCuentaLib::validar($f);
        if ($validacion['ok'] !== true) {
            return $validacion;
        }

        $pdo = Conexion::conectar();
        $rows = ReportesEstadoCuentaLib::consultarDetalle($pdo, $f);
        $kpisExtra = self::kpisEstadoCuenta($rows);

        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasEstadoCuenta(),
            'mapRowEstadoCuenta',
            $kpisExtra
        );
    }

    private static function kpisEstadoCuenta($rows)
    {
        $saldoInicial = '';
        $cargos = 0.0;
        $abonos = 0.0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $orden = isset($row['orden']) ? $row['orden'] : 'B';
            if ($orden === 'A') {
                $saldoInicial = isset($row['monto']) ? (string) $row['monto'] : '';
                continue;
            }
            $monto = self::parseSaldo(isset($row['monto']) ? $row['monto'] : 0);
            $tip = isset($row['tip_mov']) ? $row['tip_mov'] : '';
            if ($tip === '+') {
                $cargos += $monto;
            } elseif ($tip === '-') {
                $abonos += $monto;
            }
        }

        $kpis = array();
        if ($saldoInicial !== '') {
            $kpis[] = array('label' => 'Saldo inicial', 'value' => 'S/ ' . $saldoInicial);
        }
        if ($cargos > 0) {
            $kpis[] = array(
                'label' => 'Cargos del periodo',
                'value' => 'S/ ' . number_format($cargos, 2, '.', ','),
            );
        }
        if ($abonos > 0) {
            $kpis[] = array(
                'label' => 'Abonos del periodo',
                'value' => 'S/ ' . number_format($abonos, 2, '.', ','),
            );
        }
        return $kpis;
    }

    private static function previewSaldosFecha($filtros)
    {
        $f = self::normalizeSaldosFechaFiltros($filtros);
        if ($f['fin'] === '') {
            return array('ok' => false, 'error' => 'Indique la fecha de corte.');
        }
        if (strtotime($f['inicio']) > strtotime($f['fin'])) {
            return array('ok' => false, 'error' => 'La fecha de inicio no puede ser mayor que la de corte.');
        }

        $rows = ControladorCuentas::ctrSaldoFecha($f['inicio'], $f['fin']);
        if (!is_array($rows)) {
            $rows = array();
        }
        $rows = self::filtrarFilasDetalleSaldosFecha($rows, $f);
        $kpisExtra = self::kpisSaldosFecha($rows, $f);

        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasSaldosFecha(),
            'mapRowSaldosFecha',
            $kpisExtra
        );
    }

    private static function filtrarFilasDetalleSaldosFecha($rows, $f)
    {
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tipo = isset($row['tipo_doc']) ? (string) $row['tipo_doc'] : '';
            if ($tipo === '00' || $tipo === '99') {
                continue;
            }
            if ($f['cli'] !== '' && isset($row['cliente']) && (string) $row['cliente'] !== (string) $f['cli']) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function kpisSaldosFecha($rows, $f)
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sum += self::parseSaldo(isset($row['saldoFecha']) ? $row['saldoFecha'] : 0);
        }

        $kpis = array(
            array(
                'label' => 'Fecha corte',
                'value' => $f['fin'],
            ),
        );
        $kpis[] = array(
            'label' => 'Total saldo',
            'value' => 'S/ ' . number_format($sum, 2, '.', ','),
        );
        return $kpis;
    }

    private static function previewLetrasPorImprimir($filtros)
    {
        return self::previewLetrasTabla($filtros, 'ReportesGeneralesV2LetrasLib', 'consultarLetrasPorImprimir');
    }

    private static function previewLetrasEnCartera($filtros)
    {
        return self::previewLetrasTabla($filtros, 'ReportesGeneralesV2LetrasLib', 'consultarLetrasEnCartera');
    }

    private static function previewLetrasPorAceptar($filtros)
    {
        $f = self::normalizeBase($filtros);
        if ($f['vend'] === '') {
            return array('ok' => false, 'error' => 'Indique el vendedor.');
        }
        return self::previewLetrasTabla($filtros, 'ReportesGeneralesV2LetrasLib', 'consultarLetrasPorAceptar');
    }

    private static function previewDocCancelados($filtros)
    {
        $f = self::normalizeBase($filtros);
        $pdo = Conexion::conectar();
        $rows = ReportesGeneralesV2LetrasLib::consultarDocCancelados($pdo, $f);
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sum += self::parseSaldo(isset($row['monto']) ? $row['monto'] : 0);
        }
        $kpisExtra = array(
            array(
                'label' => 'Total monto',
                'value' => 'S/ ' . number_format($sum, 2, '.', ','),
            ),
        );
        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasLetras(),
            'mapRowLetras',
            $kpisExtra
        );
    }

    private static function previewLetrasTabla($filtros, $libClass, $method)
    {
        $f = self::normalizeBase($filtros);
        $pdo = Conexion::conectar();
        $rows = call_user_func(array($libClass, $method), $pdo, $f);
        $sum = ReportesGeneralesV2LetrasLib::totalSaldo($rows);
        $kpisExtra = array(
            array(
                'label' => 'Total saldo',
                'value' => 'S/ ' . number_format($sum, 2, '.', ','),
            ),
        );
        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasLetras(),
            'mapRowLetras',
            $kpisExtra
        );
    }

    private static function previewInformeBancoEstado($filtros)
    {
        return self::previewInformeAgrupado($filtros, 'banco_estado');
    }

    private static function previewInformeEstadoBanco($filtros)
    {
        return self::previewInformeAgrupado($filtros, 'estado_banco');
    }

    private static function previewInformeAgrupado($filtros, $modo)
    {
        $f = self::normalizeBase($filtros);
        $pdo = Conexion::conectar();
        $raw = ReportesGeneralesV2Fase3Lib::consultarDocumentosPendientes($pdo, $f);
        $totalRaw = count($raw);

        if ($modo === 'banco_estado') {
            $built = ReportesGeneralesV2Fase3Lib::construirInformeBancoEstado($raw);
        } else {
            $built = ReportesGeneralesV2Fase3Lib::construirInformeEstadoBanco($raw);
        }

        $builtRows = $built['rows'];
        $limit = self::currentLimit();
        $totalBuilt = count($builtRows);
        $truncated = ($limit > 0 && $totalBuilt > $limit);
        if ($truncated) {
            $builtRows = array_slice($builtRows, 0, $limit);
        }

        $kpisExtra = array(
            array(
                'label' => 'Total saldo',
                'value' => 'S/ ' . ReportesGeneralesV2Fase3Lib::fmtNum($built['total_saldo']),
            ),
        );

        return self::buildPreviewResponseInforme(
            $builtRows,
            $kpisExtra,
            $truncated,
            $totalRaw
        );
    }

    private static function previewMovimientos($filtros)
    {
        $f = self::normalizeBase($filtros);
        if ($f['inicio'] === '' || $f['fin'] === '') {
            return array('ok' => false, 'error' => 'Indique fecha inicio y fin.');
        }
        if (strtotime($f['inicio']) > strtotime($f['fin'])) {
            return array('ok' => false, 'error' => 'La fecha de inicio no puede ser mayor que la de fin.');
        }

        $pdo = Conexion::conectar();
        $rows = ReportesGeneralesV2Fase3Lib::consultarMovimientos($pdo, $f);
        $cargos = 0.0;
        $abonos = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $monto = self::parseSaldo(isset($row['monto']) ? $row['monto'] : 0);
            $tip = isset($row['tip_mov']) ? $row['tip_mov'] : '';
            if ($tip === '+') {
                $cargos += $monto;
            } elseif ($tip === '-') {
                $abonos += $monto;
            }
        }
        $kpisExtra = array(
            array('label' => 'Total cargos', 'value' => 'S/ ' . number_format($cargos, 2, '.', ',')),
            array('label' => 'Total abonos', 'value' => 'S/ ' . number_format($abonos, 2, '.', ',')),
        );

        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasMovimientos(),
            'mapRowMovimientos',
            $kpisExtra
        );
    }

    private static function previewResumenSaldosFecha($filtros)
    {
        $f = self::normalizeSaldosFechaFiltros($filtros);
        if ($f['fin'] === '') {
            return array('ok' => false, 'error' => 'Indique la fecha de corte.');
        }
        if (strtotime($f['inicio']) > strtotime($f['fin'])) {
            return array('ok' => false, 'error' => 'La fecha de inicio no puede ser mayor que la de corte.');
        }

        $pdo = Conexion::conectar();
        $rows = ReportesGeneralesV2Fase3Lib::consultarResumenSaldosFecha($pdo, $f);
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sum += self::parseSaldo(isset($row['saldo_fecha']) ? $row['saldo_fecha'] : 0);
        }
        $kpisExtra = array(
            array('label' => 'Fecha corte', 'value' => $f['fin']),
            array('label' => 'Total saldo', 'value' => 'S/ ' . number_format($sum, 2, '.', ',')),
        );

        return self::buildPreviewResponse(
            $rows,
            array(),
            '',
            self::columnasResumenSaldos(),
            'mapRowResumenSaldos',
            $kpisExtra
        );
    }

    private static function buildPreviewResponseInforme($rows, $kpisExtra, $truncated, $totalDetailRows)
    {
        if (!is_array($kpisExtra)) {
            $kpisExtra = array();
        }
        $mapped = array();
        foreach ($rows as $row) {
            $mapped[] = self::mapRowInforme($row);
        }
        $kpis = array(
            array(
                'label' => 'Documentos',
                'value' => $truncated
                    ? (self::currentLimit() . ' de ' . $totalDetailRows)
                    : (string) $totalDetailRows,
            ),
        );
        foreach ($kpisExtra as $kpi) {
            $kpis[] = $kpi;
        }
        return array(
            'ok' => true,
            'previewMode' => 'informe',
            'columns' => self::columnasInformeCobranza(),
            'rows' => $mapped,
            'totalRows' => $totalDetailRows,
            'truncated' => $truncated,
            'kpis' => $kpis,
        );
    }

    private static function filtrarFilasDetallePagos($rows)
    {
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tipo = isset($row['tipo_doc']) ? (string) $row['tipo_doc'] : '';
            if ($tipo === '-1' || $tipo === '999') {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Filtros que la rama vendedor del legacy no aplica en SQL.
     */
    private static function aplicarFiltrosPagosPostQuery($rows, $f)
    {
        if ($f['canc'] === '') {
            return $rows;
        }
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!isset($row['cod_pago']) || (string) $row['cod_pago'] !== (string) $f['canc']) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function fetchCobranzaRows($tipo, $f)
    {
        $o1 = self::ORDEN1_COBRANZA;
        $o2 = self::ORDEN2_COBRANZA;
        $tip = '';
        $cli = 'todo';
        $vend = 'todo';
        $banco = '';
        $fin = $f['fin'];

        if ($tipo === 'cobrar') {
            return ControladorCuentas::ctrMostrarReporteCobrar($o1, $o2, $tip, $cli, $vend, $banco, $fin);
        }
        if ($tipo === 'vencidos') {
            return ControladorCuentas::ctrMostrarReporteVencidos($o1, $o2, $tip, $cli, $vend, $banco);
        }
        if ($tipo === 'no_vencidos') {
            return ControladorCuentas::ctrMostrarReporteNoVencidos($o1, $o2, $tip, $cli, $vend, $banco);
        }
        if ($tipo === 'protestados') {
            return ControladorCuentas::ctrMostrarReporteProtestados($o1, $o2, $tip, $cli, $vend, $banco);
        }
        return array();
    }

    /**
     * Filtros v2 cobranza — solo los visibles en UI; todos aplican sobre el detalle.
     */
    private static function aplicarFiltrosCobranza($rows, $f)
    {
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!isset($row['num_cta']) || $row['num_cta'] === '') {
                continue;
            }
            if ($f['tip_doc'] !== '' && isset($row['tipo_doc']) && (string) $row['tipo_doc'] !== (string) $f['tip_doc']) {
                continue;
            }
            if ($f['cli'] !== '' && isset($row['cliente']) && (string) $row['cliente'] !== (string) $f['cli']) {
                continue;
            }
            if ($f['vend'] !== '' && isset($row['vendedor']) && (string) $row['vendedor'] !== (string) $f['vend']) {
                continue;
            }
            if ($f['banco'] !== '' && !self::filaCoincideBanco($row, $f['banco'])) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function filaCoincideBanco($row, $codigoBanco)
    {
        if (!isset($row['banco'])) {
            return false;
        }
        $valor = trim((string) $row['banco']);
        if ($valor === (string) $codigoBanco) {
            return true;
        }
        if ($codigoBanco === '02' && strtoupper($valor) === 'BCP') {
            return true;
        }
        return false;
    }

    private static function parseSaldo($valor)
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }
        return (float) str_replace(',', '', (string) $valor);
    }

    private static function calcularTotalFilas($rows)
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += self::parseSaldo(isset($row['saldo']) ? $row['saldo'] : 0);
        }
        return array('saldo_total' => number_format($sum, 2, '.', ','));
    }

    private static function columnasCobranza()
    {
        return array(
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha'),
            array('key' => 'fecha_ven', 'label' => 'Vencimiento'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'),
            array('key' => 'protesta', 'label' => 'Protesta'),
            array('key' => 'num_unico', 'label' => 'Nro. único'),
            array('key' => 'banco', 'label' => 'Banco'),
        );
    }

    private static function mapRowCobranza($row)
    {
        $keys = array('tipo_doc', 'num_cta', 'fecha', 'fecha_ven', 'vendedor', 'cliente', 'nombre', 'saldo', 'protesta', 'num_unico', 'banco', 'doc_origen', 'estado_doc');
        $out = array();
        foreach ($keys as $k) {
            $out[$k] = isset($row[$k]) ? $row[$k] : '';
        }
        return $out;
    }

    private static function mapRowPagos($row)
    {
        $keys = array('tipo_doc', 'num_cta', 'fecha', 'cliente', 'nombre', 'vendedor', 'cod_pago', 'notas', 'fact', 'letra', 'doc_origen');
        $out = array();
        foreach ($keys as $k) {
            $out[$k] = isset($row[$k]) ? $row[$k] : '';
        }
        return $out;
    }

    private static function columnasPagos()
    {
        return array(
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha pago'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'cod_pago', 'label' => 'Cob.'),
            array('key' => 'notas', 'label' => 'Notas'),
            array('key' => 'fact', 'label' => 'Fact. S/', 'type' => 'money'),
            array('key' => 'letra', 'label' => 'Letra S/', 'type' => 'money'),
        );
    }

    private static function mapRowEstadoCuenta($row)
    {
        $orden = isset($row['orden']) ? $row['orden'] : 'B';
        $out = array(
            'concepto' => '',
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => ReportesEstadoCuentaLib::formatearFecha(isset($row['fecha']) ? $row['fecha'] : ''),
            'fecha_ven' => ReportesEstadoCuentaLib::formatearFecha(isset($row['fecha_ven']) ? $row['fecha_ven'] : ''),
            'tip_mov' => isset($row['tip_mov']) ? $row['tip_mov'] : '',
            'monto' => isset($row['monto']) ? $row['monto'] : '',
            'saldo' => isset($row['saldo']) ? $row['saldo'] : '',
            'cod_pago' => isset($row['cod_pago']) ? $row['cod_pago'] : '',
            'doc_origen' => isset($row['doc_origen']) ? $row['doc_origen'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'notas' => isset($row['notas']) ? $row['notas'] : '',
        );
        if ($orden === 'A') {
            $out['concepto'] = 'Saldo inicial';
            $out['tip_mov'] = '=';
        }
        return $out;
    }

    private static function columnasEstadoCuenta()
    {
        return array(
            array('key' => 'concepto', 'label' => ''),
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha'),
            array('key' => 'fecha_ven', 'label' => 'Venc.'),
            array('key' => 'tip_mov', 'label' => 'Mov.'),
            array('key' => 'monto', 'label' => 'Monto', 'type' => 'money'),
            array('key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'),
            array('key' => 'cod_pago', 'label' => 'Cob.'),
            array('key' => 'doc_origen', 'label' => 'Origen'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'notas', 'label' => 'Notas'),
        );
    }

    private static function mapRowSaldosFecha($row)
    {
        $out = array(
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => isset($row['fecha']) ? $row['fecha'] : '',
            'fecha_ven' => isset($row['fecha_ven']) ? $row['fecha_ven'] : '',
            'doc_origen' => isset($row['doc_origen']) ? $row['doc_origen'] : '',
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'estado' => isset($row['estado']) ? $row['estado'] : '',
            'saldo_fecha' => isset($row['saldoFecha']) ? $row['saldoFecha'] : '',
        );
        return $out;
    }

    private static function columnasSaldosFecha()
    {
        return array(
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha emi.'),
            array('key' => 'fecha_ven', 'label' => 'Venc.'),
            array('key' => 'doc_origen', 'label' => 'Origen'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'estado', 'label' => 'Estado'),
            array('key' => 'saldo_fecha', 'label' => 'Saldo S/', 'type' => 'money'),
        );
    }

    private static function mapRowLetras($row)
    {
        $out = array(
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => isset($row['fecha']) ? $row['fecha'] : '',
            'fecha_ven' => isset($row['fecha_ven']) ? $row['fecha_ven'] : '',
            'doc_origen' => isset($row['doc_origen']) ? $row['doc_origen'] : '',
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'saldo' => isset($row['saldo']) ? $row['saldo'] : '',
            'num_unico' => isset($row['num_unico']) ? $row['num_unico'] : '',
            'banco' => isset($row['banco']) ? $row['banco'] : '',
        );
        return $out;
    }

    private static function columnasLetras()
    {
        return array(
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha'),
            array('key' => 'fecha_ven', 'label' => 'Venc.'),
            array('key' => 'doc_origen', 'label' => 'Origen'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'),
            array('key' => 'num_unico', 'label' => 'Nro. único'),
            array('key' => 'banco', 'label' => 'Banco'),
        );
    }

    private static function mapRowInforme($row)
    {
        $out = array(
            '_rowType' => isset($row['_rowType']) ? $row['_rowType'] : 'detail',
            'concepto' => isset($row['concepto']) ? $row['concepto'] : '',
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => isset($row['fecha']) ? $row['fecha'] : '',
            'fecha_ven' => isset($row['fecha_ven']) ? $row['fecha_ven'] : '',
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'banco' => isset($row['banco']) ? $row['banco'] : '',
            'estado_doc' => isset($row['estado_doc']) ? $row['estado_doc'] : '',
            'saldo' => isset($row['saldo']) ? $row['saldo'] : '',
        );
        return $out;
    }

    private static function columnasInformeCobranza()
    {
        return array(
            array('key' => 'concepto', 'label' => ''),
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'fecha', 'label' => 'Fecha'),
            array('key' => 'fecha_ven', 'label' => 'Venc.'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'banco', 'label' => 'Banco'),
            array('key' => 'estado_doc', 'label' => 'Estado'),
            array('key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'),
        );
    }

    private static function mapRowMovimientos($row)
    {
        return array(
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => isset($row['fecha']) ? $row['fecha'] : '',
            'tip_mov' => isset($row['tip_mov']) ? $row['tip_mov'] : '',
            'monto' => isset($row['monto']) ? $row['monto'] : '',
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'cod_pago' => isset($row['cod_pago']) ? $row['cod_pago'] : '',
            'doc_origen' => isset($row['doc_origen']) ? $row['doc_origen'] : '',
            'notas' => isset($row['notas']) ? $row['notas'] : '',
        );
    }

    private static function columnasMovimientos()
    {
        return array(
            array('key' => 'fecha', 'label' => 'Fecha'),
            array('key' => 'tip_mov', 'label' => 'Mov.'),
            array('key' => 'tipo_doc', 'label' => 'Tipo'),
            array('key' => 'num_cta', 'label' => 'Número'),
            array('key' => 'monto', 'label' => 'Monto', 'type' => 'money'),
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'vendedor', 'label' => 'Vend.'),
            array('key' => 'cod_pago', 'label' => 'Cob.'),
            array('key' => 'doc_origen', 'label' => 'Origen'),
            array('key' => 'notas', 'label' => 'Notas'),
        );
    }

    private static function mapRowResumenSaldos($row)
    {
        return array(
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'saldo_fecha' => isset($row['saldo_fecha']) ? $row['saldo_fecha'] : '',
        );
    }

    private static function columnasResumenSaldos()
    {
        return array(
            array('key' => 'cliente', 'label' => 'Cliente'),
            array('key' => 'nombre', 'label' => 'Nombre'),
            array('key' => 'saldo_fecha', 'label' => 'Saldo S/', 'type' => 'money'),
        );
    }

    private static function buildPreviewResponse($rows, $total, $totalLabel, $columns, $mapCallback, $kpisExtra)
    {
        if ($columns === null) {
            $columns = self::columnasCobranza();
        }
        if ($mapCallback === null || $mapCallback === '') {
            $mapCallback = 'mapRowCobranza';
        }
        if (!is_array($kpisExtra)) {
            $kpisExtra = array();
        }

        $totalRows = count($rows);
        $limit = self::currentLimit();
        $truncated = ($limit > 0 && $totalRows > $limit);
        if ($truncated) {
            $rows = array_slice($rows, 0, $limit);
        }

        $mapped = array();
        foreach ($rows as $row) {
            if ($mapCallback === 'mapRowPagos') {
                $mapped[] = self::mapRowPagos($row);
            } elseif ($mapCallback === 'mapRowEstadoCuenta') {
                $mapped[] = self::mapRowEstadoCuenta($row);
            } elseif ($mapCallback === 'mapRowSaldosFecha') {
                $mapped[] = self::mapRowSaldosFecha($row);
            } elseif ($mapCallback === 'mapRowLetras') {
                $mapped[] = self::mapRowLetras($row);
            } elseif ($mapCallback === 'mapRowMovimientos') {
                $mapped[] = self::mapRowMovimientos($row);
            } elseif ($mapCallback === 'mapRowResumenSaldos') {
                $mapped[] = self::mapRowResumenSaldos($row);
            } else {
                $mapped[] = self::mapRowCobranza($row);
            }
        }

        $kpis = array(
            array(
                'label' => 'Registros',
                'value' => $truncated ? (self::currentLimit() . ' de ' . $totalRows) : (string) $totalRows,
            ),
        );

        foreach ($kpisExtra as $kpi) {
            $kpis[] = $kpi;
        }

        if ($totalLabel !== '' && is_array($total) && isset($total['saldo_total']) && $total['saldo_total'] !== '') {
            $kpis[] = array(
                'label' => $totalLabel,
                'value' => 'S/ ' . $total['saldo_total'],
            );
        }

        return array(
            'ok' => true,
            'columns' => $columns,
            'rows' => $mapped,
            'totalRows' => $totalRows,
            'truncated' => $truncated,
            'kpis' => $kpis,
        );
    }
}
