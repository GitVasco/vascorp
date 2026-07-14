<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "centro_decisiones")) {
    denegarAccesoModulo();
    return;
}

require_once __DIR__ . "/dashboard-decisiones/helpers.php";

$vendedorSeleccionado = ControladorDashboardDecisiones::ctrVendedorSeleccionado();
$vendedoresPermitidos = ControladorDashboardDecisiones::ctrVendedoresPermitidos();
$datos = ControladorDashboardDecisiones::ctrDatosDashboard();
$pedidos = $datos["pedidos"];
$cartera = $datos["cartera"];
$alertas = $datos["alertas"];
$topGenerados = $datos["top_generados"];
$generados = $datos["generados"];
$estancados = $datos["estancados"];
$atraso = $datos["atraso"];
$avanceVentas = $datos["avance_ventas"];
$facturadoResumen = $datos["facturado_resumen"];
$facturado = $datos["facturado"];
?>

<div class="content-wrapper dd-dashboard">
    <section class="content-header dd-content-header">
        <div class="dd-header-row">
            <div class="dd-header-title">
                <h1>
                    Centro de Decisiones
                    <small>Mayoristas y distribuidores</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
                    <li class="active">Centro de Decisiones</li>
                </ol>
            </div>
            <form class="dd-filtro-form" id="ddFiltroForm" onsubmit="return false;">
                <div class="dd-filtro-group">
                    <label for="ddFiltroVendedor" class="dd-filtro-label">
                        <i class="fa fa-user"></i> Vendedor
                    </label>
                    <select id="ddFiltroVendedor"
                            name="vendedor"
                            class="form-control selectpicker dd-filtro-select"
                            data-live-search="true"
                            data-size="8"
                            title="Todos los vendedores">
                        <option value="">Todos los vendedores</option>
                        <?php foreach ($vendedoresPermitidos as $vend) : ?>
                            <option value="<?php echo htmlspecialchars($vend["codigo"]); ?>"
                                <?php echo ($vendedorSeleccionado === (string) $vend["codigo"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($vend["codigo"] . " - " . $vend["descripcion"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btnDdActualizar" class="btn btn-primary btn-sm dd-filtro-btn">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>
                    <button type="button"
                       id="btnDdLimpiarFiltro"
                       class="btn btn-default btn-sm dd-filtro-btn dd-filtro-btn--limpiar btnDdLimpiarFiltro"
                       title="Quitar filtro de vendedor">
                        <i class="fa fa-times"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="content dd-content-compact">
        <div id="ddContenidoDashboard">
            <?php include __DIR__ . "/dashboard-decisiones/contenido-dashboard.php"; ?>
        </div>
    </section>
</div>

<div class="modal fade" id="modalDdMiniIc" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-mini-ic-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddMiniIcTitulo">
                    <i class="fa fa-user-circle"></i> Análisis para decisión
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddMiniIcSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddMiniIcBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando análisis del cliente…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddMiniIcLinkCompleto" target="_blank">
                    <i class="fa fa-external-link"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDdDecisionCredito" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-decision-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddDecisionCreditoTitulo">
                    <i class="fa fa-gavel"></i> Decisión de crédito
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddDecisionCreditoSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddDecisionCreditoBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando decisión…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddDecisionCreditoLinkIc" target="_blank">
                    <i class="fa fa-line-chart"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<script>window.document.title = "Centro de Decisiones | Vasco System";</script>
