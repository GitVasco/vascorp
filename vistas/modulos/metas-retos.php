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
                            <span class="text-muted" style="margin-left:8px;">Solo vendedores activos</span>
                        </p>
                        <p class="text-muted" style="margin:0;font-size:12px;line-height:1.45;">
                            Cada columna resume <strong>meta → real → %</strong>.
                            Modelo especial: docenas = cantidad÷12.
                            El estimado suma comisiones según avance (no es liquidación).
                        </p>
                    </div>
                    <div class="col-sm-4">
                        <div class="mr-total-box">
                            <div class="mr-total-label">Total estimado a pagar</div>
                            <div class="mr-total-value" id="mrTotalPagarPeriodo">S/ 0.00</div>
                        </div>
                    </div>
                </div>

                <p class="text-muted" style="margin:0 0 8px;font-size:12px;">
                    En cada reto: <strong>meta / real</strong> y abajo el <strong>+ aporte</strong> al estimado.
                    Colores de columna = tipo de reto.
                </p>
                <table class="table table-bordered table-striped table-condensed tablaMetasRetos mr-table" width="100%"
                       data-anio="<?php echo $anioFiltro; ?>" data-mes="<?php echo $mesFiltro; ?>">
                    <thead>
                        <tr>
                            <th class="mr-th-vend">Vendedor</th>
                            <th class="mr-th-ventas">Ventas S/ <small>(meta/real)</small></th>
                            <th class="mr-th-cli">Clientes <small>(meta/real)</small></th>
                            <th class="mr-th-mod">Modelos <small>(meta/real)</small></th>
                            <th class="mr-th-esp">Especial <small>(modelo · meta/docenas)</small></th>
                            <th class="mr-th-pay">Estimado S/</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
.mr-th-ventas { background: #d9edf7; color: #31708f; border-bottom-color: #3c8dbc !important; }
.mr-th-cli { background: #dff0d8; color: #3c763d; border-bottom-color: #00a65a !important; }
.mr-th-mod { background: #fcf8e3; color: #8a6d3b; border-bottom-color: #f39c12 !important; }
.mr-th-esp { background: #f2dede; color: #a94442; border-bottom-color: #dd4b39 !important; }
.mr-th-pay { background: #e8f5e9; color: #2e7d32; border-bottom-color: #00a65a !important; }

/* Línea lateral de color por columna de reto */
.mr-table > tbody > tr > td:nth-child(2) { border-left: 3px solid #3c8dbc; }
.mr-table > tbody > tr > td:nth-child(3) { border-left: 3px solid #00a65a; }
.mr-table > tbody > tr > td:nth-child(4) { border-left: 3px solid #f39c12; }
.mr-table > tbody > tr > td:nth-child(5) { border-left: 3px solid #dd4b39; }
.mr-table > tbody > tr > td:nth-child(6) { border-left: 3px solid #00a65a; background: #f7fbf7; }

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

                    <div class="box box-solid box-info">
                        <div class="box-header with-border"><h3 class="box-title">1) Monto de ventas</h3></div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;" id="mrAyudaMonto">
                                <strong>Prorrata:</strong> paga % sobre el monto vendido.
                                <strong>Todo o nada:</strong> paga comisión fija solo si se llega a la meta.
                            </p>
                            <div class="row">
                                <div class="col-sm-3">
                                    <label>Meta S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="meta_monto" id="mrMetaMonto">
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_monto" id="mrCumplimientoMonto">
                                        <option value="prorrata">Prorrata (% sobre venta)</option>
                                        <option value="todo_nada">Todo o nada (fijo al lograr)</option>
                                    </select>
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionMontoPct">
                                    <label>Comisión %</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_pct" id="mrComisionMontoPct" placeholder="ej. 1.5">
                                </div>
                                <div class="col-sm-3" id="mrWrapComisionMontoFijo">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_fijo" id="mrComisionMontoFijo" placeholder="ej. 500">
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
                            <p class="help-block" style="margin-top:0;">Modelos distintos vendidos en el mes.</p>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label>Meta (#)</label>
                                    <input type="number" step="1" min="0" class="form-control" name="meta_modelos" id="mrMetaModelos">
                                </div>
                                <div class="col-sm-4">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_modelos_fijo" id="mrComisionModelos">
                                </div>
                                <div class="col-sm-4">
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
                        <div class="box-header with-border"><h3 class="box-title">4) Modelo especial (beneficio)</h3></div>
                        <div class="box-body">
                            <p class="help-block" style="margin-top:0;">
                                Un modelo por vendedor y mes. Avance en <strong>docenas</strong> (pares÷12).
                                La comisión % se aplica sobre la venta de ese modelo (para el reporte de comisiones).
                            </p>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label>Modelo</label>
                                    <select class="form-control selectpicker" name="modelo_especial" id="mrModeloEspecial"
                                            data-live-search="true" title="Sin modelo especial" data-width="100%">
                                        <option value="">— Sin modelo —</option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label>Meta docenas</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="meta_docenas_especial" id="mrMetaDocenasEsp" placeholder="ej. 10">
                                </div>
                                <div class="col-sm-2">
                                    <label>Comisión %</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_modelo_esp_pct" id="mrComisionModeloEspPct" placeholder="ej. 2">
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_modelo_esp" id="mrCumplimientoModeloEsp">
                                        <option value="todo_nada">Todo o nada</option>
                                        <option value="prorrata">Prorrata</option>
                                    </select>
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
