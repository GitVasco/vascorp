<?php

require_once __DIR__ . '/cuentas.controlador.php';
require_once __DIR__ . '/../modelos/cuentas.modelo.php';

class ReportesGeneralesV2Servicio
{
    const PREVIEW_LIMIT = 500;

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
            default:
                return array('ok' => false, 'error' => 'Reporte sin implementar.');
        }
    }

    public static function exportUrl($formato, $reporteId, $filtros)
    {
        $f = self::normalizeFiltros($filtros);

        if ($formato === 'pdf') {
            $url = self::buildPdfUrl($reporteId, $f);
            if ($url === '') {
                return array('ok' => false, 'error' => 'PDF no disponible para esta combinación de filtros.');
            }
            return array('ok' => true, 'url' => $url);
        }

        if ($formato === 'xlsx' || $formato === 'excel') {
            $url = self::buildExcelUrl($reporteId, $f);
            if ($url === '') {
                return array('ok' => false, 'error' => 'Excel no disponible para este reporte en v2 (paridad v1).');
            }
            return array('ok' => true, 'url' => $url);
        }

        return array('ok' => false, 'error' => 'Formato no válido.');
    }

    private static function normalizeFiltros($filtros)
    {
        $f = $filtros;
        if (!isset($f['cli']) || $f['cli'] === '') {
            $f['cli'] = 'todo';
        }
        if (!isset($f['vend']) || $f['vend'] === '') {
            $f['vend'] = 'todo';
        }
        if (!isset($f['fin']) || $f['fin'] === '') {
            $f['fin'] = date('Y-m-d');
        }
        if (!isset($f['tip_doc'])) {
            $f['tip_doc'] = '';
        }
        if (!isset($f['banco'])) {
            $f['banco'] = '';
        }
        if (!isset($f['orden1']) || $f['orden1'] === '') {
            $f['orden1'] = 'tipo';
        }
        if (!isset($f['orden2']) || $f['orden2'] === '') {
            $f['orden2'] = 'ordNumCuenta';
        }
        return $f;
    }

    private static function previewCobranza($filtros, $tipo, $totalLabel)
    {
        $f = self::normalizeFiltros($filtros);
        $rows = self::fetchCobranzaRows($tipo, $f);
        if (!is_array($rows)) {
            $rows = array();
        }
        $total = self::fetchCobranzaTotal($tipo, $f);
        return self::buildPreviewResponse($rows, $total, $totalLabel);
    }

    private static function fetchCobranzaRows($tipo, $f)
    {
        $o1 = $f['orden1'];
        $o2 = $f['orden2'];
        $tip = $f['tip_doc'];
        $cli = $f['cli'];
        $vend = $f['vend'];
        $banco = $f['banco'];
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

    private static function fetchCobranzaTotal($tipo, $f)
    {
        $o1 = $f['orden1'];
        $o2 = $f['orden2'];
        $tip = $f['tip_doc'];
        $cli = $f['cli'];
        $vend = $f['vend'];
        $banco = $f['banco'];
        $fin = $f['fin'];

        if ($tipo === 'cobrar') {
            if ($o1 === 'fecha_ven' && $o2 === 'ordVencimiento') {
                return ControladorCuentas::ctrMostrarReporteTotalOct($tip, $banco, $fin);
            }
            return ControladorCuentas::ctrMostrarReporteTotalCobrar($o1, $o2, $tip, $cli, $vend, $banco);
        }
        if ($tipo === 'vencidos') {
            return ControladorCuentas::ctrMostrarReporteTotalVencidos($o1, $o2, $tip, $cli, $vend, $banco);
        }
        if ($tipo === 'no_vencidos') {
            return ControladorCuentas::ctrMostrarReporteTotalNoVencidos($o1, $o2, $tip, $cli, $vend, $banco);
        }
        if ($tipo === 'protestados') {
            return ControladorCuentas::ctrMostrarReporteTotalProtestados($o1, $o2, $tip, $cli, $vend, $banco);
        }
        return array();
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
            array('key' => 'saldo', 'label' => 'Saldo'),
            array('key' => 'protesta', 'label' => 'Protesta'),
            array('key' => 'num_unico', 'label' => 'Nro. único'),
            array('key' => 'banco', 'label' => 'Banco'),
        );
    }

    private static function mapRow($row)
    {
        $keys = array('tipo_doc', 'num_cta', 'fecha', 'fecha_ven', 'vendedor', 'cliente', 'nombre', 'saldo', 'protesta', 'num_unico', 'banco', 'doc_origen', 'estado_doc');
        $out = array();
        foreach ($keys as $k) {
            $out[$k] = isset($row[$k]) ? $row[$k] : '';
        }
        return $out;
    }

    private static function buildPreviewResponse($rows, $total, $totalLabel)
    {
        $totalRows = count($rows);
        $truncated = $totalRows > self::PREVIEW_LIMIT;
        if ($truncated) {
            $rows = array_slice($rows, 0, self::PREVIEW_LIMIT);
        }

        $mapped = array();
        foreach ($rows as $row) {
            $mapped[] = self::mapRow($row);
        }

        $kpis = array(
            array(
                'label' => 'Registros',
                'value' => $truncated ? (self::PREVIEW_LIMIT . ' de ' . $totalRows) : (string) $totalRows,
            ),
        );

        if (is_array($total) && isset($total['saldo_total']) && $total['saldo_total'] !== '') {
            $kpis[] = array(
                'label' => $totalLabel,
                'value' => 'S/ ' . $total['saldo_total'],
            );
        }

        return array(
            'ok' => true,
            'columns' => self::columnasCobranza(),
            'rows' => $mapped,
            'totalRows' => $totalRows,
            'truncated' => $truncated,
            'kpis' => $kpis,
        );
    }

    private static function v1Consulta($reporteId)
    {
        $map = array(
            'doc_por_cobrar' => 'pendiente',
            'doc_vencidos' => 'pendienteVencidoMenor',
            'doc_no_vencidos' => 'pendienteVencidoMayor',
            'doc_protestados' => 'protestado',
        );
        return isset($map[$reporteId]) ? $map[$reporteId] : '';
    }

    private static function buildPdfUrl($reporteId, $f)
    {
        $consulta = self::v1Consulta($reporteId);
        if ($consulta === '') {
            return '';
        }

        $params = array(
            'consulta' => $consulta,
            'orden1' => $f['orden1'],
            'orden2' => $f['orden2'],
        );

        if ($f['orden1'] === 'cliente') {
            $base = 'extensiones/tcpdf/pdf/reporte_cliente_cuentas.php';
            $params['cli'] = $f['cli'];
            return $base . '?' . http_build_query($params);
        }

        if ($f['orden1'] === 'vendedor' && $f['vend'] !== '' && $f['vend'] !== 'todo') {
            $base = 'extensiones/tcpdf/pdf/reporte_vendedor_cuentas.php';
            $params['vend'] = $f['vend'];
            return $base . '?' . http_build_query($params);
        }

        $base = 'extensiones/tcpdf/pdf/reporte_general_cuentas.php';

        if ($f['orden1'] === 'fecha_ven') {
            $params['banco'] = $f['banco'];
            $params['tip_doc'] = $f['tip_doc'];
            $params['fin'] = $f['fin'];
        }

        if ($f['orden1'] === 'vendedor') {
            $params['vend'] = ($f['vend'] === 'todo') ? '' : $f['vend'];
        }

        return $base . '?' . http_build_query($params);
    }

    private static function buildExcelUrl($reporteId, $f)
    {
        if ($reporteId === 'doc_por_cobrar') {
            return 'vistas/reportes_excel/rpt_ctas_ctes.php';
        }
        return '';
    }
}
