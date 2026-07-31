<?php
if (!isset($generados) || !is_array($generados)) {
    $generados = array();
}
if (!isset($aprobados) || !is_array($aprobados)) {
    $aprobados = array();
}
if (!function_exists("ddFormatoMonto")) {
    require_once __DIR__ . "/../dashboard-decisiones/helpers.php";
}

$puedeAprobar = function_exists("dcUsuarioPuedeAprobarPedido") && dcUsuarioPuedeAprobarPedido();
$puedeAnular = function_exists("dcUsuarioPuedeAnularPedido") && dcUsuarioPuedeAnularPedido();
$puedeLiberarControl = function_exists("dcUsuarioPuedeLiberarControlPostAprobacion")
    && dcUsuarioPuedeLiberarControlPostAprobacion();
$puedeRegistrarControl = function_exists("dcUsuarioPuedeRegistrarControlPostAprobacion")
    && dcUsuarioPuedeRegistrarControlPostAprobacion();

if (!function_exists("hcRenderAccionesPedido")) {
    /**
     * @param array $row
     * @param bool  $esGenerado
     * @param bool  $puedeAprobar
     * @param bool  $puedeAnular
     * @param bool  $puedeLiberarControl
     * @param bool  $puedeRegistrarControl
     */
    function hcRenderAccionesPedido(
        $row,
        $esGenerado,
        $puedeAprobar,
        $puedeAnular,
        $puedeLiberarControl = false,
        $puedeRegistrarControl = false
    )
    {
        $html = '<div class="dd-acciones-iconos">';

        if (!empty($row["cod_cli"])) {
            $html .= '<button type="button" class="btn btn-xs btn-default btnDdMiniIc"'
                . ' title="Ver inteligencia del cliente"'
                . ' data-cliente="' . htmlspecialchars($row["cod_cli"]) . '"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-nombre="' . htmlspecialchars($row["cliente"]) . '">'
                . '<i class="fa fa-user-circle"></i></button>';

            $html .= '<button type="button" class="btn btn-xs btn-warning btnDdDecisionCredito"'
                . ' title="'
                . (!empty($row["decision_credito"])
                    ? "Objeción vigente (pedido en GENERADO)"
                    : "Decisión de crédito")
                . '"'
                . ' data-cliente="' . htmlspecialchars($row["cod_cli"]) . '"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-nombre="' . htmlspecialchars($row["cliente"]) . '">'
                . '<i class="fa fa-gavel"></i></button>';
        }

        if ($esGenerado && $puedeAprobar && empty($row["decision_credito"])) {
            $html .= '<button type="button" class="btn btn-xs btn-success btnDdAprobarPedido"'
                . ' title="Aprobar pedido"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-cliente="' . htmlspecialchars($row["cliente"]) . '"'
                . ' data-cod-cli="' . htmlspecialchars($row["cod_cli"]) . '"'
                . ' data-tiene-categoria="' . (!empty($row["categoria_codigo"]) ? "1" : "0") . '"'
                . ' data-es-contado="' . (!empty($row["es_contado"]) ? "1" : "0") . '"'
                . ' data-condicion="' . htmlspecialchars(isset($row["condicion"]) ? $row["condicion"] : "") . '">'
                . '<i class="fa fa-check"></i></button>';
        }

        if ($esGenerado && $puedeAnular) {
            $html .= '<button type="button" class="btn btn-xs btn-danger btnDdAnularPedido"'
                . ' title="Anular pedido (sin retorno)"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-cliente="' . htmlspecialchars($row["cliente"]) . '">'
                . '<i class="fa fa-times"></i></button>';
        }

        if (!$esGenerado && !empty($row["control_post_aprobacion"]) && $puedeLiberarControl) {
            $ctrl = $row["control_post_aprobacion"];
            $html .= '<button type="button" class="btn btn-xs btn-success btnHcLiberarControl"'
                . ' title="Liberar despacho (control pendiente)"'
                . ' data-id="' . (int) $ctrl["id"] . '"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-condicion="' . htmlspecialchars(isset($ctrl["condicion_etiqueta"]) ? $ctrl["condicion_etiqueta"] : "") . '"'
                . ' data-cliente="' . htmlspecialchars($row["cliente"]) . '"'
                . ' data-area="' . htmlspecialchars(!empty($ctrl["area_autoriza_codigo"]) ? $ctrl["area_autoriza_codigo"] : "") . '">'
                . '<i class="fa fa-unlock"></i></button>';
        }

        if (
            !$esGenerado
            && empty($row["control_post_aprobacion"])
            && $puedeRegistrarControl
        ) {
            $html .= '<button type="button" class="btn btn-xs btn-warning btnHcRegistrarControl"'
                . ' title="Registrar control post-aprobación"'
                . ' data-pedido="' . htmlspecialchars($row["codigo"]) . '"'
                . ' data-cliente="' . htmlspecialchars($row["cliente"]) . '"'
                . ' data-cod-cli="' . htmlspecialchars($row["cod_cli"]) . '">'
                . '<i class="fa fa-lock"></i></button>';
        }

        $html .= "</div>";

        return $html;
    }
}

if (!function_exists("hcRenderFilasCola")) {
    /**
     * @param array $lista
     * @param bool  $esGenerado
     * @param bool  $puedeAprobar
     * @param bool  $puedeRegistrarControl
     */
    function hcRenderFilasCola(
        $lista,
        $esGenerado,
        $puedeAprobar,
        $puedeAnular,
        $puedeLiberarControl = false,
        $puedeRegistrarControl = false
    )
    {
        if (empty($lista)) {
            $msg = $esGenerado
                ? "No hay pedidos GENERADOS pendientes de aprobación."
                : "No hay pedidos APROBADOS en cola.";
            return '<tr><td colspan="8" class="text-center text-muted">' . $msg . "</td></tr>";
        }

        $html = "";
        foreach ($lista as $row) {
            $html .= '<tr class="' . (((int) $row["cliente_en_mora"] === 1) ? "dd-row-mora" : "") . '">';
            $html .= "<td>";
            if ((int) $row["cliente_en_mora"] === 1) {
                $html .= '<i class="fa fa-warning text-danger" title="Cliente con deuda vencida"></i> ';
            }
            $html .= "<strong>" . htmlspecialchars($row["codigo"]) . "</strong>";
            if (!empty($row["decision_credito"])) {
                $motivoEtiqueta = isset($row["decision_credito"]["motivo_etiqueta"])
                    ? $row["decision_credito"]["motivo_etiqueta"]
                    : "Objeción de crédito";
                $html .= ' <span class="label label-warning dd-motivo-badge" title="'
                    . htmlspecialchars("Objeción — pedido sigue en GENERADO · " . $motivoEtiqueta) . '">'
                    . '<i class="fa fa-exclamation-triangle"></i></span>';
            }
            if (!$esGenerado && !empty($row["control_post_aprobacion"])) {
                $ctrl = $row["control_post_aprobacion"];
                $tituloCtrl = "Control pendiente: "
                    . (isset($ctrl["condicion_etiqueta"]) ? $ctrl["condicion_etiqueta"] : $ctrl["condicion_codigo"]);
                if (!empty($ctrl["area_etiqueta"])) {
                    $tituloCtrl .= " · " . $ctrl["area_etiqueta"];
                }
                $html .= ' <span class="label label-danger hc-control-badge" title="'
                    . htmlspecialchars($tituloCtrl) . '"><i class="fa fa-lock"></i></span>';
            }
            $html .= "</td>";
            $html .= '<td class="dd-col-cliente"><div class="dd-cell-main dd-cell-cliente" title="'
                . htmlspecialchars(
                    $row["cod_cli"] . " · " . $row["cliente"]
                    . (!empty($row["nombre_grupo"]) ? (" · " . $row["nombre_grupo"]) : "")
                ) . '">'
                . ddClienteLinea($row["cod_cli"], $row["cliente"], $row)
                . "</div></td>";
            $html .= '<td><div class="dd-cell-main"><span class="dd-cod-cli">'
                . htmlspecialchars($row["vendedor"]) . "</span> "
                . htmlspecialchars($row["nom_vendedor"]) . "</div></td>";
            $html .= "<td><small>" . htmlspecialchars($row["condicion"]) . "</small></td>";
            $html .= '<td class="text-right hc-monto"><strong>' . ddFormatoMonto($row["lista"], $row["total"]) . "</strong></td>";
            $html .= '<td class="text-center hc-fecha-cell">' . htmlspecialchars($row["fecha"]) . "</td>";
            $html .= '<td><span class="dd-dias dd-dias--'
                . (((int) $row["dias_pendiente"] >= 2) ? "alto" : "medio") . '">'
                . (int) $row["dias_pendiente"] . "d</span></td>";
            $html .= '<td class="text-center">'
                . hcRenderAccionesPedido(
                    $row,
                    $esGenerado,
                    $puedeAprobar,
                    $puedeAnular,
                    $puedeLiberarControl,
                    $puedeRegistrarControl
                )
                . "</td>";
            $html .= "</tr>";
        }

        return $html;
    }
}
?>

<div class="row hc-cola-kpis">
    <div class="col-sm-6 col-xs-12">
        <div class="hc-kpi hc-kpi--objecion">
            <span class="hc-kpi-lbl">Por aprobar (GENERADO)</span>
            <strong id="hcKpiColaGen"><?php echo count($generados); ?></strong>
            <span class="hc-kpi-sub" id="hcKpiColaGenSoles">
                S/ <?php echo number_format(isset($resumenCola["soles_generados"]) ? (float) $resumenCola["soles_generados"] : 0, 0); ?>
            </span>
        </div>
    </div>
    <div class="col-sm-6 col-xs-12">
        <div class="hc-kpi hc-kpi--aprobado">
            <span class="hc-kpi-lbl">Aprobados en pipeline</span>
            <strong id="hcKpiColaApr"><?php echo count($aprobados); ?></strong>
            <span class="hc-kpi-sub" id="hcKpiColaAprSoles">
                S/ <?php echo number_format(isset($resumenCola["soles_aprobados"]) ? (float) $resumenCola["soles_aprobados"] : 0, 0); ?>
            </span>
        </div>
    </div>
</div>

<div class="box box-solid dd-box hc-cola-box">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-flag"></i> Pendientes de aprobación
        </h3>
        <span class="label label-warning pull-right" id="hcBadgeGen"><?php echo count($generados); ?></span>
    </div>
    <div class="box-body table-responsive dd-table-wrap">
        <table class="table table-hover table-condensed dd-table" id="hcTablaGenerados">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th class="dd-col-cliente">Cliente</th>
                    <th>Vendedor</th>
                    <th>Condición</th>
                    <th class="text-right">Total c/IGV</th>
                    <th class="text-center">Fecha</th>
                    <th>Días</th>
                    <th class="text-center" width="150px"></th>
                </tr>
            </thead>
            <tbody id="hcBodyGenerados">
                <?php echo hcRenderFilasCola($generados, true, $puedeAprobar, $puedeAnular, $puedeLiberarControl, $puedeRegistrarControl); ?>
            </tbody>
        </table>
    </div>
</div>

<div class="box box-solid dd-box hc-cola-box">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-check-circle"></i> Aprobados (control)
        </h3>
        <span class="label label-success pull-right" id="hcBadgeApr"><?php echo count($aprobados); ?></span>
    </div>
    <div class="box-body" style="padding-bottom:0;">
        <p class="text-muted hc-controles-intro">
            <i class="fa fa-lock text-warning"></i> Registra un control si el pedido ya está aprobado y surgió una condición u observación.
        </p>
    </div>
    <div class="box-body table-responsive dd-table-wrap">
        <table class="table table-hover table-condensed dd-table" id="hcTablaAprobados">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th class="dd-col-cliente">Cliente</th>
                    <th>Vendedor</th>
                    <th>Condición</th>
                    <th class="text-right">Total c/IGV</th>
                    <th class="text-center">Fecha</th>
                    <th>Días</th>
                    <th class="text-center" width="150px"></th>
                </tr>
            </thead>
            <tbody id="hcBodyAprobados">
                <?php echo hcRenderFilasCola($aprobados, false, $puedeAprobar, $puedeAnular, $puedeLiberarControl, $puedeRegistrarControl); ?>
            </tbody>
        </table>
    </div>
</div>
