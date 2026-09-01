<?php

class ControladorReportesGeneralesV2
{
    public static function ctrCatalogoJson()
    {
        return array(
            'groups' => ReportesGeneralesV2Config::grupos(),
            'templates' => ReportesGeneralesV2Config::plantillas(),
            'filterDefs' => ReportesGeneralesV2Config::filterDefs(),
        );
    }

    public static function ctrParseFiltros($input)
    {
        $keys = array('reporte', 'orden1', 'orden2', 'tip_doc', 'canc', 'cli', 'vend', 'banco', 'inicio', 'fin');
        $out = array();
        foreach ($keys as $key) {
            $out[$key] = isset($input[$key]) ? trim((string) $input[$key]) : '';
        }
        if ($out['orden1'] === '') {
            $out['orden1'] = 'tipo';
        }
        if ($out['orden2'] === '') {
            $out['orden2'] = 'ordNumCuenta';
        }
        return $out;
    }

    public static function ctrPreview($filtros)
    {
        $tpl = ReportesGeneralesV2Config::find($filtros['reporte']);
        if ($tpl === null) {
            return array('ok' => false, 'error' => 'Plantilla de reporte no encontrada.');
        }
        if ($tpl['estado'] === 'fuera_alcance') {
            return array('ok' => false, 'error' => 'Este reporte aún no está disponible.');
        }
        if ($tpl['estado'] !== 'listo') {
            return array(
                'ok' => false,
                'error' => 'Reporte en construcción (Fase ' . (int) $tpl['fase'] . '). Próximamente: ' . $tpl['title'] . '.',
                'fase' => (int) $tpl['fase'],
            );
        }

        require_once __DIR__ . '/reportes-generales-v2.servicio.php';
        return ReportesGeneralesV2Servicio::preview($filtros['reporte'], $filtros);
    }

    public static function ctrExport($formato, $filtros)
    {
        $tpl = ReportesGeneralesV2Config::find($filtros['reporte']);
        if ($tpl === null) {
            return array('ok' => false, 'error' => 'Plantilla de reporte no encontrada.');
        }
        if ($tpl['estado'] !== 'listo') {
            return array('ok' => false, 'error' => 'Reporte aún no disponible para exportar.');
        }

        require_once __DIR__ . '/reportes-generales-v2.servicio.php';
        return ReportesGeneralesV2Servicio::exportUrl($formato, $filtros['reporte'], $filtros);
    }
}
