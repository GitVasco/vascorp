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
                <p class="text-muted" style="margin-top:0;">
                    Período: <strong><?php echo htmlspecialchars($nombreMes) . " " . $anioFiltro; ?></strong>.
                    Solo vendedores activos. Tipos: monto ventas (ventajf), clientes nuevos (1ª compra sin grupo), modelos distintos (movimientos + articulojf).
                    Comisiones se configuran aquí; el reporte de liquidación es fase posterior.
                </p>
                <table class="table table-bordered table-striped dt-responsive tablaMetasRetos" width="100%"
                       data-anio="<?php echo $anioFiltro; ?>" data-mes="<?php echo $mesFiltro; ?>">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Meta S/</th>
                            <th>Venta real</th>
                            <th>% monto</th>
                            <th>Meta cli.</th>
                            <th>Cli. nuevos</th>
                            <th>% cli.</th>
                            <th>Meta mod.</th>
                            <th>Modelos</th>
                            <th>% mod.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
</div>

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
                            <div class="row">
                                <div class="col-sm-3">
                                    <label>Meta S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="meta_monto" id="mrMetaMonto">
                                </div>
                                <div class="col-sm-3">
                                    <label>Comisión %</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_pct" id="mrComisionMontoPct" placeholder="ej. 1.5">
                                </div>
                                <div class="col-sm-3">
                                    <label>Comisión fija S/</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="comision_monto_fijo" id="mrComisionMontoFijo">
                                </div>
                                <div class="col-sm-3">
                                    <label>Cumplimiento</label>
                                    <select class="form-control" name="cumplimiento_monto" id="mrCumplimientoMonto">
                                        <option value="todo_nada">Todo o nada</option>
                                        <option value="prorrata">Prorrata</option>
                                    </select>
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
