<?php

/**
 * Catálogo de plantillas — Reportes generales v2.
 * Fuente única: no duplicar ids en JS ni en ajax.
 */
class ReportesGeneralesV2Config
{
    public static function grupos()
    {
        return array(
            array('id' => 'cobranza', 'label' => 'Cobranza'),
            array('id' => 'letras', 'label' => 'Letras'),
            array('id' => 'movimientos_saldos', 'label' => 'Movimientos y saldos'),
            array('id' => 'pagos', 'label' => 'Pagos'),
        );
    }

    /**
     * estado: pendiente | listo | fuera_alcance
     * modo_ux: preview_tabla | preview_informe | preview_tabla_pesada | fuera_alcance
     */
    public static function plantillas()
    {
        return array(
            array(
                'id' => 'doc_por_cobrar',
                'v1' => 'pendiente',
                'group' => 'cobranza',
                'title' => 'Doc. por cobrar',
                'hint' => 'Documentos pendientes de cobro.',
                'icon' => 'fa-file-text-o',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'tip_doc', 'cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_vencidos',
                'v1' => 'pendienteVencidoMenor',
                'group' => 'cobranza',
                'title' => 'Doc. vencidos',
                'hint' => 'Documentos con fecha de vencimiento anterior a hoy.',
                'icon' => 'fa-exclamation-triangle',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'tip_doc', 'cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_no_vencidos',
                'v1' => 'pendienteVencidoMayor',
                'group' => 'cobranza',
                'title' => 'Doc. no vencidos',
                'hint' => 'Documentos aún no vencidos.',
                'icon' => 'fa-check-circle',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'tip_doc', 'cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_protestados',
                'v1' => 'protestado',
                'group' => 'cobranza',
                'title' => 'Doc. protestados',
                'hint' => 'Documentos marcados como protestados.',
                'icon' => 'fa-ban',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'tip_doc', 'cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'letras_por_imprimir',
                'v1' => 'option5',
                'group' => 'letras',
                'title' => 'Letras por imprimir',
                'hint' => 'Letras pendientes de impresión.',
                'icon' => 'fa-print',
                'fase' => 2,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'letras_por_aceptar',
                'v1' => 'estadoEnvioVacio',
                'group' => 'letras',
                'title' => 'Letras por aceptar',
                'hint' => 'Letras sin número único / por aceptar en banco.',
                'icon' => 'fa-envelope-o',
                'fase' => 2,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_informe',
                'filters' => array('vend', 'inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'letras_en_cartera',
                'v1' => 'unicoCartera',
                'group' => 'letras',
                'title' => 'Letras en cartera',
                'hint' => 'Letras con número único en cartera.',
                'icon' => 'fa-briefcase',
                'fase' => 2,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('cli', 'vend', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_por_banco_estado',
                'v1' => 'option8',
                'group' => 'cobranza',
                'title' => 'Doc. por banco/estado',
                'hint' => 'Agrupado por banco y estado del documento.',
                'icon' => 'fa-university',
                'fase' => 3,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_informe',
                'filters' => array('tip_doc', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_por_estado_banco',
                'v1' => 'option9',
                'group' => 'cobranza',
                'title' => 'Doc. por estado/banco',
                'hint' => 'Agrupado por estado y banco.',
                'icon' => 'fa-list-alt',
                'fase' => 3,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_informe',
                'filters' => array('tip_doc', 'banco'),
                'columns' => array(),
            ),
            array(
                'id' => 'doc_cancelados',
                'v1' => 'cancelado',
                'group' => 'cobranza',
                'title' => 'Doc. cancelados',
                'hint' => 'Documentos cancelados.',
                'icon' => 'fa-times-circle',
                'fase' => 2,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'cli', 'vend', 'inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'movimientos_ctacte',
                'v1' => 'option11',
                'group' => 'movimientos_saldos',
                'title' => 'Movimientos en Ctas.ctes.',
                'hint' => 'Cargos y abonos en un rango de fechas.',
                'icon' => 'fa-exchange',
                'fase' => 3,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla_pesada',
                'filters' => array('cli', 'vend', 'inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'saldos_fecha',
                'v1' => 'fechaSaldo',
                'group' => 'movimientos_saldos',
                'title' => 'Saldos a una fecha',
                'hint' => 'Detalle de saldos al cierre indicado.',
                'icon' => 'fa-calendar',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('cli', 'inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'pagos',
                'v1' => 'pagos',
                'group' => 'pagos',
                'title' => 'Pagos',
                'hint' => 'Pagos efectuados en el periodo.',
                'icon' => 'fa-money',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('orden1', 'orden2', 'canc', 'cli', 'vend', 'inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'estado_cuenta',
                'v1' => 'fechaActualSaldo',
                'group' => 'movimientos_saldos',
                'title' => 'Estado de cuenta',
                'hint' => 'Extracto por cliente con cargos, abonos y saldo.',
                'icon' => 'fa-book',
                'fase' => 1,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_tabla',
                'filters' => array('cli', 'vend', 'inicio', 'fin'),
                'required' => array('cli'),
                'columns' => array(),
            ),
            array(
                'id' => 'resumen_saldos_fecha',
                'v1' => 'option15',
                'group' => 'movimientos_saldos',
                'title' => 'Rsm saldos a una fecha (S/)',
                'hint' => 'Resumen de saldos en soles a la fecha.',
                'icon' => 'fa-calculator',
                'fase' => 3,
                'estado' => 'pendiente',
                'modo_ux' => 'preview_informe',
                'filters' => array('inicio', 'fin'),
                'columns' => array(),
            ),
            array(
                'id' => 'pagos_comisiones',
                'v1' => 'option16',
                'group' => 'pagos',
                'title' => 'Pagos-comisiones',
                'hint' => 'Fuera de alcance hasta definición de negocio.',
                'icon' => 'fa-percent',
                'fase' => 0,
                'estado' => 'fuera_alcance',
                'modo_ux' => 'fuera_alcance',
                'filters' => array(),
                'columns' => array(),
            ),
        );
    }

    public static function find($id)
    {
        foreach (self::plantillas() as $tpl) {
            if ($tpl['id'] === $id) {
                return $tpl;
            }
        }
        return null;
    }

    public static function filterDefs()
    {
        return array(
            'orden1' => array('label' => 'Orden primario', 'type' => 'select', 'options' => array(
                array('value' => 'tipo', 'label' => 'Por Tipo/Número'),
                array('value' => 'cliente', 'label' => 'Por Cliente'),
                array('value' => 'vendedor', 'label' => 'Por Vendedor'),
                array('value' => 'fecha_ven', 'label' => 'Por Fch. vencimiento'),
                array('value' => 'fecha_pag', 'label' => 'Por Fch. Pago'),
            )),
            'orden2' => array('label' => 'Orden secundario', 'type' => 'select', 'options' => array(
                array('value' => 'ordNumCuenta', 'label' => 'Por Tipo/Número'),
                array('value' => 'ordVencimiento', 'label' => 'Por fecha de vencimiento'),
                array('value' => 'ordCliente', 'label' => 'Por cliente'),
            )),
            'tip_doc' => array('label' => 'Tipo documento', 'type' => 'tip_doc'),
            'canc' => array('label' => 'Tipo cancelación', 'type' => 'canc'),
            'cli' => array('label' => 'Cliente', 'type' => 'cli'),
            'vend' => array('label' => 'Vendedor', 'type' => 'vend'),
            'banco' => array('label' => 'Banco', 'type' => 'banco'),
            'inicio' => array('label' => 'Fecha inicio', 'type' => 'date'),
            'fin' => array('label' => 'Fecha fin', 'type' => 'date'),
        );
    }

    /**
     * Acceso beta v2 — ampliar esta lista al abrir a más usuarios.
     * @return int[]
     */
    public static function idsAccesoBeta()
    {
        return array(6);
    }

    public static function puedeAcceder()
    {
        if (!isset($_SESSION['id'])) {
            return false;
        }
        $id = (int) $_SESSION['id'];
        foreach (self::idsAccesoBeta() as $uid) {
            if ((int) $uid === $id) {
                return true;
            }
        }
        return false;
    }
}
