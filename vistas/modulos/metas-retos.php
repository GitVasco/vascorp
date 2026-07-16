<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
    denegarAccesoModulo();
    return;
}

date_default_timezone_set("America/Lima");
$periodoActual = ControladorMetasRetos::ctrPeriodoActual();
$anioFiltro = isset($_GET["anio"]) ? (int) $_GET["anio"] : $periodoActual["anio"];
$mesFiltro = isset($_GET["mes"]) ? (int) $_GET["mes"] : $periodoActual["mes"];
$periodoFiltro = ControladorMetasRetos::ctrNormalizarPeriodo($anioFiltro, $mesFiltro);
$anioFiltro = $periodoFiltro["anio"];
$mesFiltro = $periodoFiltro["mes"];
$meses = ControladorTalleres::ctrMes();
$puedeEditar = ControladorMetasRetos::ctrPuedeEditar();
$comisionVentasOn = function_exists("mrComisionVentasHabilitada") && mrComisionVentasHabilitada();
$codigosCobranzaTxt = function_exists("mrTextoCodigosCobranzaEfectiva")
	? mrTextoCodigosCobranzaEfectiva()
	: "00, TR, 05, 06, 14, 15, 16, 17, 18, 80, 82";
$nombreMes = (string) $mesFiltro;
foreach ($meses as $mesItem) {
    if ((int) $mesItem["codigo"] === $mesFiltro) {
        $nombreMes = $mesItem["descripcion"];
        break;
    }
}
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Metas / retos por vendedor</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Metas / retos</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form method="get" class="form-inline">
                    <input type="hidden" name="ruta" value="metas-retos">
                    <div class="form-group">
                        <label>Año</label>
                        <select class="form-control input-sm" name="anio" id="mrFiltroAnio">
                            <?php for ($a = $periodoActual["anio"] - 1; $a <= $periodoActual["anio"] + 1; $a++) : ?>
                                <option value="<?php echo $a; ?>" <?php echo $a === $anioFiltro ? "selected" : ""; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-left:10px;">
                        <label>Mes</label>
                        <select class="form-control input-sm" name="mes" id="mrFiltroMes">
                            <?php foreach ($meses as $mesItem) :
                                $codMes = (int) $mesItem["codigo"];
                            ?>
                                <option value="<?php echo $codMes; ?>" <?php echo $codMes === $mesFiltro ? "selected" : ""; ?>>
                                    <?php echo $mesItem["codigo"] . " - " . $mesItem["descripcion"]; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-default btn-sm" style="margin-left:10px;">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>
                </form>
            </div>
            <div class="box-body">
                <div class="row mr-summary">
                    <div class="col-sm-8">
                        <p class="mr-period" style="margin:0 0 6px;">
                            <span class="label label-primary" style="font-size:13px;">
                                <?php echo htmlspecialchars($nombreMes . " " . $anioFiltro); ?>
                            </span>
                            <span class="text-muted" style="margin-left:8px;">Solo vendedores activos · avance por marcas permitidas</span>
                        </p>
                        <p class="text-muted" style="margin:0;font-size:12px;line-height:1.45;">
                            Cada columna resume <strong>meta → real → %</strong>.
                            La <strong>cobranza</strong> usa la misma fuente que Resumen de gestión (sin IGV; códigos:
                            <?php echo htmlspecialchars($codigosCobranzaTxt); ?>).
                            Ventas, modelos y clientes nuevos consideran marcas autorizadas;
                            las NC descuento (E05 sin unidades) restan al monto.
                            Incentivos de producto siguen pagando por venta del objetivo.
                            El estimado es orientativo (sujeto a validación/liquidación).
                            <?php if (!$comisionVentasOn) : ?>
                                <span class="label label-default" style="margin-left:4px;">Comisión por ventas: desactivada</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-sm-4">
                        <div class="mr-total-box">
                            <div class="mr-total-label">Total estimado a pagar <small style="opacity:.85;">(sujeto a liquidación)</small></div>
                            <div class="mr-total-value" id="mrTotalPagarPeriodo">S/ 0.00</div>
                        </div>
                    </div>
                </div>

                <p class="text-muted" style="margin:0 0 8px;font-size:12px;">
                    En cada reto: <strong>meta / real</strong> y abajo el <strong>+ aporte</strong> al estimado.
                    Colores de columna = tipo de reto.
                </p>
                <table class="table table-bordered table-striped table-condensed tablaMetasRetos mr-table" width="100%"
                       data-anio="<?php echo $anioFiltro; ?>" data-mes="<?php echo $mesFiltro; ?>"
                       data-comision-ventas="<?php echo $comisionVentasOn ? "1" : "0"; ?>">
                    <thead>
                        <tr>
                            <th class="mr-th-vend">Vendedor</th>
                            <th class="mr-th-cob">Cobranza S/ <small>(meta/real)</small></th>
                            <th class="mr-th-ventas">Ventas S/ <small>(referencia)</small></th>
                            <th class="mr-th-cli">Clientes <small>(meta/real)</small></th>
                            <th class="mr-th-mod">Modelos <small>(meta/real)</small></th>
                            <th class="mr-th-esp">Incentivos <small>(producto)</small></th>
                            <th class="mr-th-pay">Estimado S/</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="box box-default collapsed-box" id="mrBoxCoberturaMarcas">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-balance-scale"></i> Conciliación por marcas
                    <small class="text-muted">(cabecera vs KPI Fase 3)</small>
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                <p class="text-muted" style="margin-top:0;font-size:12px;line-height:1.45;">
                    <strong>Fase 3 activa:</strong> el avance oficial de metas/retos usa
                    <strong>venta permitida</strong> (líneas con marca autorizada)
                    + <strong>NC descuento E05</strong> (sin unidades, resta al vendedor sin marca).
                    Las devoluciones E05 con unidades se imputan por marca.
                    Esta tabla sigue mostrando la cabecera anterior para conciliar.
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed table-striped" id="tablaConciliacionMarcas" width="100%">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th class="text-right">Cabecera S/</th>
                                <th class="text-right">KPI permitido S/</th>
                                <th class="text-right">Líneas perm. S/</th>
                                <th class="text-right">NC desc. S/</th>
                                <th class="text-right">Fuera cobertura S/</th>
                                <th class="text-right">Dif. cab−KPI</th>
                                <th class="text-center">Cli. cab.</th>
                                <th class="text-center">Cli. perm.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="10" class="text-muted text-center">Expande el panel para cargar datos del periodo.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.mr-summary { margin-bottom: 12px; }
.mr-total-box {
    background: #3c8dbc;
    color: #fff;
    border-radius: 4px;
    padding: 10px 14px;
    text-align: right;
}
.mr-total-label { font-size: 11px; opacity: .9; }
.mr-total-value { font-size: 22px; font-weight: 700; line-height: 1.2; }

/* Encabezados por color de reto */
.mr-table > thead > tr > th {
    vertical-align: middle;
    white-space: nowrap;
    font-size: 12px;
    border-bottom-width: 3px !important;
}
.mr-th-vend { background: #f4f4f4; border-bottom-color: #bbb !important; }
.mr-th-cob { background: #e8eaf6; color: #3949ab; border-bottom-color: #3f51b5 !important; }
.mr-th-ventas { background: #d9edf7; color: #31708f; border-bottom-color: #3c8dbc !important; }
.mr-th-cli { background: #dff0d8; color: #3c763d; border-bottom-color: #00a65a !important; }
.mr-th-mod { background: #fcf8e3; color: #8a6d3b; border-bottom-color: #f39c12 !important; }
.mr-th-esp { background: #f2dede; color: #a94442; border-bottom-color: #dd4b39 !important; }
.mr-th-pay { background: #e8f5e9; color: #2e7d32; border-bottom-color: #00a65a !important; }

/* Línea lateral de color por columna de reto */
.mr-table > tbody > tr > td:nth-child(2) { border-left: 3px solid #3f51b5; }
.mr-table > tbody > tr > td:nth-child(3) { border-left: 3px solid #3c8dbc; }
.mr-table > tbody > tr > td:nth-child(4) { border-left: 3px solid #00a65a; }
.mr-table > tbody > tr > td:nth-child(5) { border-left: 3px solid #f39c12; }
.mr-table > tbody > tr > td:nth-child(6) { border-left: 3px solid #dd4b39; }
.mr-table > tbody > tr > td:nth-child(7) { border-left: 3px solid #00a65a; background: #f7fbf7; }

.mr-ventas-ref { opacity: .92; }
.mr-ventas-ref .box-header { background: #ecf0f5 !important; color: #555 !important; }

.mr-table > tbody > tr > td {
    vertical-align: middle !important;
    padding: 6px 8px !important;
}
.mr-cell { line-height: 1.25; }
.mr-line { font-size: 12px; white-space: nowrap; }
.mr-meta { color: #888; }
.mr-sep { color: #bbb; margin: 0 3px; }
.mr-real { font-weight: 700; color: #333; }
.mr-mod { font-size: 11px; font-weight: 700; color: #a94442; margin-bottom: 1px; }
.mr-inc-summary { font-size: 12px; font-weight: 700; color: #a94442; }
.mr-inc-detalle { font-size: 11px; color: #666; margin-top: 2px; line-height: 1.3; }
.mr-inc-form label { font-size: 12px; margin-bottom: 2px; font-weight: 600; }
.mr-inc-form .row + .row { clear: both; }
.mr-empty { color: #ccc; }
.mr-bar {
    height: 12px;
    margin: 3px 0 0;
    background: #eee;
    border-radius: 2px;
}
.mr-bar .progress-bar {
    font-size: 9px;
    line-height: 12px;
    font-weight: 600;
}
.mr-aporte {
    margin-top: 2px;
    font-size: 11px;
    font-weight: 700;
    color: #2e7d32;
    white-space: nowrap;
}
.mr-aporte--zero {
    color: #bbb;
    font-weight: 600;
}
.mr-pay {
    font-weight: 700;
    color: #2e7d32;
    font-size: 13px;
    white-space: nowrap;
}
@media (max-width: 767px) {
    .mr-total-box { text-align: left; margin-top: 8px; }
}
#tablaConciliacionMarcas .mr-cob-dif-pos { color: #dd4b39; font-weight: 700; }
#tablaConciliacionMarcas .mr-cob-dif-zero { color: #888; }
#tablaConciliacionMarcas .mr-cob-permitida { color: #00a65a; font-weight: 700; }
#tablaConciliacionMarcas .mr-cob-fuera { color: #f39c12; }
.mr-detalle-marca-table { font-size: 11px; margin: 6px 0 0; }
</style>

<?php if ($puedeEditar) { ?>
<div id="modalMetasRetos" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formMetasRetos">
                <div class="modal-header" style="background:#3c8dbc;color:#fff;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Configurar metas / retos — <span id="mrTituloVendedor"></span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="cod_vendedor" id="mrCodVendedor">
                    <input type="hidden" name="anio" id="mrAnio" value="<?php echo $anioFiltro; ?>">
                    <input type="hidden" name="mes" id="mrMes" value="<?php echo $mesFiltro; ?>">

                    <div class="box box-solid" style="border-top-color:#3f51b5;">
                        <div class="box-header with-border" style="background:#e8eaf6;">
                            <h3 class="box-title" style="color:#3949ab;">1) Cobranza efectiva</h3>
                        </div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;" id="mrAyudaCobranza">
                                Misma fuente que Resumen de gestión, <strong>sin IGV</strong>
                                (monto ÷ <?php echo htmlspecialchars((string) (function_exists("mrIgvFactor") ? mrIgvFactor() : 1.18)); ?>).
                                Códigos: <?php echo htmlspecialchars($codigosCobranzaTxt); ?>.
                            </p>
                            <div class="row">
                                <div class="col-sm-3">
                                    <label>Meta S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="meta_cobranza" id="mrMetaCobranza">
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_cobranza" id="mrCumplimientoCobranza">
                                        <option value="prorrata">Prorrata (% sobre cobranza)</option>
                                        <option value="todo_nada">Todo o nada (fijo al lograr)</option>
                                    </select>
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionCobranzaPct">
                                    <label>Comisión %</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_cobranza_pct" id="mrComisionCobranzaPct" placeholder="ej. 1.5">
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionCobranzaFijo">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_cobranza_fijo" id="mrComisionCobranzaFijo" placeholder="ej. 500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-solid box-info mr-ventas-ref" id="mrBoxVentasRef">
                        <div class="box-header with-border">
                            <h3 class="box-title">
                                Ventas (referencia / futura comisión)
                                <?php if (!$comisionVentasOn) : ?>
                                    <small class="label label-default">Comisión por ventas: desactivada</small>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;" id="mrAyudaMonto">
                                <?php if ($comisionVentasOn) : ?>
                                    <strong>Prorrata:</strong> paga % sobre el monto vendido.
                                    <strong>Todo o nada:</strong> paga comisión fija solo si se llega a la meta.
                                <?php else : ?>
                                    Conservado como indicador operativo. No genera comisión mientras la política de cobranza esté activa.
                                <?php endif; ?>
                            </p>
                            <div class="row">
                                <div class="col-sm-3">
                                    <label>Meta S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="meta_monto" id="mrMetaMonto"
                                        <?php echo $comisionVentasOn ? "" : "readonly"; ?>>
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_monto" id="mrCumplimientoMonto"
                                        <?php echo $comisionVentasOn ? "" : "disabled"; ?>>
                                        <option value="prorrata">Prorrata (% sobre venta)</option>
                                        <option value="todo_nada">Todo o nada (fijo al lograr)</option>
                                    </select>
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionMontoPct">
                                    <label>Comisión %</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_pct" id="mrComisionMontoPct" placeholder="ej. 1.5"
                                        <?php echo $comisionVentasOn ? "" : "readonly"; ?>>
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionMontoFijo">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_fijo" id="mrComisionMontoFijo" placeholder="ej. 500"
                                        <?php echo $comisionVentasOn ? "" : "readonly"; ?>>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-solid box-success">
                        <div class="box-header with-border"><h3 class="box-title">2) Clientes nuevos</h3></div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;">1ª compra en la vida, sin grupo empresarial.</p>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label>Meta (#)</label>
                                    <input type="number" step="1" min="0" class="form-control" name="meta_clientes" id="mrMetaClientes">
                                </div>
                                <div class="col-sm-4">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_clientes_fijo" id="mrComisionClientes">
                                </div>
                                <div class="col-sm-4">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_clientes" id="mrCumplimientoClientes">
                                        <option value="todo_nada">Todo o nada</option>
                                        <option value="prorrata">Prorrata</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-solid box-warning">
                        <div class="box-header with-border"><h3 class="box-title">3) Modelos activos</h3></div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;">
                                Universo del vendedor (grupos vigentes):
                                <strong id="mrUniversoModelosTxt">—</strong> modelos activos.
                                El avance cuenta modelos distintos vendidos de marcas permitidas.
                            </p>
                            <div class="row">
                                <div class="col-sm-3">
                                    <label>Definir meta como</label>
                                    <select class="form-control" name="meta_modelos_modo" id="mrMetaModelosModo">
                                        <option value="cantidad">Cantidad</option>
                                        <option value="porcentaje">Porcentaje del universo</option>
                                    </select>
                                </div>
                                <div class="col-sm-3" id="mrWrapMetaModelosCantidad">
                                    <label>Meta (#)</label>
                                    <input type="number" step="1" min="0" class="form-control" name="meta_modelos" id="mrMetaModelos">
                                </div>
                                <div class="col-sm-3" id="mrWrapMetaModelosPct" style="display:none;">
                                    <label>Meta (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="meta_modelos_pct" id="mrMetaModelosPct" placeholder="ej. 80">
                                    <p class="help-block" style="margin-bottom:0;" id="mrMetaModelosPctPreview">Equivale a — modelos</p>
                                </div>
                                <div class="col-sm-3">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_modelos_fijo" id="mrComisionModelos">
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_modelos" id="mrCumplimientoModelos">
                                        <option value="todo_nada">Todo o nada</option>
                                        <option value="prorrata">Prorrata</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-solid box-danger">
                        <div class="box-header with-border">
                            <h3 class="box-title">4) Incentivos por producto</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-xs btn-danger" id="mrBtnAgregarIncentivo">
                                    <i class="fa fa-plus"></i> Agregar incentivo
                                </button>
                            </div>
                        </div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;">
                                Varios incentivos por mes. Cada uno se mide aparte y la comisión % es sobre la venta del objetivo.
                                <strong>Modelo + color</strong> suma todas las tallas de ese color; para una talla exacta usá <strong>Artículo</strong>.
                            </p>
                            <input type="hidden" name="incentivos_json" id="mrIncentivosJson" value="[]">
                            <input type="hidden" name="forzar_superpuestos" id="mrForzarSuperpuestos" value="0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed" id="mrTablaIncentivos">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Objetivo</th>
                                            <th>Unidad</th>
                                            <th>Meta</th>
                                            <th>Comisión %</th>
                                            <th>Cumplimiento</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mrIncentivosBody">
                                        <tr class="mr-inc-empty"><td colspan="7" class="text-muted text-center">Sin incentivos</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="mrIncForm" class="well well-sm mr-inc-form" style="display:none;margin-bottom:0;">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <label>Tipo</label>
                                        <select class="form-control input-sm" id="mrIncTipo">
                                            <option value="modelo">Modelo</option>
                                            <option value="modelo_color">Modelo + color</option>
                                            <option value="articulo">Artículo</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-5" id="mrIncWrapModelo">
                                        <label>Modelo</label>
                                        <select class="form-control input-sm selectpicker" id="mrIncModelo"
                                                data-live-search="true" title="Elegir modelo" data-width="100%">
                                        </select>
                                    </div>
                                    <div class="col-sm-4" id="mrIncWrapColor" style="display:none;">
                                        <label>Color <small class="text-muted">(todas las tallas)</small></label>
                                        <select class="form-control input-sm" id="mrIncColor">
                                            <option value="">— Color —</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4" id="mrIncWrapArticulo" style="display:none;">
                                        <label>Artículo / SKU</label>
                                        <input type="text" class="form-control input-sm" id="mrIncArticulo" list="mrIncArticuloList" placeholder="Buscar código…" autocomplete="off">
                                        <datalist id="mrIncArticuloList"></datalist>
                                        <p class="help-block" style="margin:2px 0 0;font-size:11px;" id="mrIncArticuloInfo"></p>
                                    </div>
                                </div>
                                <div class="row" style="margin-top:8px;">
                                    <div class="col-sm-3">
                                        <label>Unidad</label>
                                        <select class="form-control input-sm" id="mrIncUnidad">
                                            <option value="docenas">Docenas</option>
                                            <option value="unidades">Unidades</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Meta</label>
                                        <input type="number" step="0.01" min="0.01" class="form-control input-sm" id="mrIncMeta" placeholder="ej. 100">
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Comisión %</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control input-sm" id="mrIncPct" placeholder="ej. 5">
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Cumplimiento</label>
                                        <select class="form-control input-sm" id="mrIncCumplimiento">
                                            <option value="todo_nada">Todo o nada</option>
                                            <option value="prorrata">Prorrata</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-top:10px;">
                                    <button type="button" class="btn btn-sm btn-primary" id="mrIncConfirmar">
                                        <i class="fa fa-check"></i> Agregar a la lista
                                    </button>
                                    <button type="button" class="btn btn-sm btn-default" id="mrIncCancelar">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>
