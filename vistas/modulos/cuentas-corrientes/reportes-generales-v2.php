<?php

require_once 'controladores/reportes-generales-v2.config.php';

if (!ReportesGeneralesV2Config::puedeAcceder()) {
    if (function_exists('denegarAccesoModulo')) {
        denegarAccesoModulo();
    } else {
        echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso</div></section></div>';
    }
    return;
}

require_once 'controladores/reportes-generales-v2.controlador.php';

$catalogo = ControladorReportesGeneralesV2::ctrCatalogoJson();

$item = null;
$valor = 'tdoc';
$documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

$itemCanc = 'tipo_dato';
$valorCanc = 'TCAN';
$tiposCancelacion = ControladorCuentas::ctrMostrarPagos($itemCanc, $valorCanc);

$clientes = ControladorClientes::ctrMostrarClientes(null, null);
$vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);
$bancos = ControladorBancos::ctrMostrarBancos(null, null);

?>
<div class="content-wrapper rgv2-page" id="rgv2Page">

    <section class="content-header">
        <h1>
            Reportes generales
            <small>v2 (beta)</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="reportes-generales">Reportes generales (legacy)</a></li>
            <li class="active">Reportes v2</li>
        </ol>
    </section>

    <section class="content">
        <div class="callout callout-info">
            <p class="mb-0">
                Versión nueva en desarrollo. El menú <strong>Reportes Generales</strong> anterior sigue igual.
                Elija una plantilla, aplique filtros y use <strong>Vista previa</strong>; Excel y PDF se habilitan cuando el reporte esté listo.
            </p>
        </div>

        <div class="row rgv2-layout">
            <div class="col-md-4 col-lg-3">
                <div class="box box-solid rgv2-catalog-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-th-list"></i> Plantillas</h3>
                    </div>
                    <div class="box-body">
                        <div class="rgv2-group-nav" id="rgv2GroupToggle" role="tablist" aria-label="Grupo de plantillas"></div>
                        <div class="form-group rgv2-search-wrap">
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="search" class="form-control" id="rgv2Search" placeholder="Buscar plantilla…" autocomplete="off">
                            </div>
                        </div>
                        <div id="rgv2CatalogList" class="rgv2-catalog-list"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-8 col-lg-9">
                <div class="box box-default">
                    <div class="box-body">

                        <div id="rgv2Empty" class="rgv2-empty text-center">
                            <div class="rgv2-empty__icon"><i class="fa fa-file-text-o"></i></div>
                            <p class="rgv2-empty__title">Sin plantilla seleccionada</p>
                            <p class="text-muted rgv2-empty__hint">Elija un reporte del catálogo de la izquierda para ver filtros y vista previa.</p>
                        </div>

                        <div id="rgv2Panel" class="hidden">
                            <div class="rgv2-toolbar clearfix">
                                <div class="pull-left">
                                    <h4 class="rgv2-title" id="rgv2Title">—</h4>
                                    <span class="label label-default" id="rgv2GroupBadge">—</span>
                                    <span class="label label-warning" id="rgv2EstadoBadge">Pendiente</span>
                                </div>
                                <div class="pull-right rgv2-actions">
                                    <button type="submit" form="rgv2Filters" class="btn btn-primary btn-sm" id="rgv2PreviewBtn">
                                        <i class="fa fa-eye"></i> Vista previa
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" id="rgv2ExcelBtn" disabled>
                                        <i class="fa fa-file-excel-o"></i> Excel
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" id="rgv2PdfBtn" disabled>
                                        <i class="fa fa-file-pdf-o"></i> PDF
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted small" id="rgv2Hint"></p>

                            <form id="rgv2Filters" class="rgv2-filters">
                                <div class="row" id="rgv2FilterFields"></div>
                            </form>

                            <div class="rgv2-kpis" id="rgv2Kpis"></div>

                            <div class="table-responsive rgv2-table-wrap">
                                <table class="table table-bordered table-striped table-condensed rgv2-table">
                                    <thead id="rgv2Thead"></thead>
                                    <tbody id="rgv2Tbody"></tbody>
                                    <tfoot id="rgv2Tfoot"></tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    window.Rgv2Boot = <?php echo json_encode($catalogo, JSON_UNESCAPED_UNICODE); ?>;
    window.Rgv2Lookups = {
        tip_doc: <?php echo json_encode($documentos, JSON_UNESCAPED_UNICODE); ?>,
        canc: <?php echo json_encode($tiposCancelacion, JSON_UNESCAPED_UNICODE); ?>,
        cli: <?php echo json_encode($clientes, JSON_UNESCAPED_UNICODE); ?>,
        vend: <?php echo json_encode($vendedores, JSON_UNESCAPED_UNICODE); ?>,
        banco: <?php echo json_encode($bancos, JSON_UNESCAPED_UNICODE); ?>
    };
    window.document.title = "Reportes generales v2";
</script>
