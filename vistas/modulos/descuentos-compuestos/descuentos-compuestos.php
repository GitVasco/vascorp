<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$origenFiltro = isset($_GET["origen"]) ? $_GET["origen"] : "";
$clienteFiltro = isset($_GET["cliente"]) ? trim($_GET["cliente"]) : "";

$resumen = ControladorDescuentosCompuestos::ctrResumenDescuentosCompuestos($clienteFiltro);
$clientes = ControladorDescuentosCompuestos::ctrClientesConDescuentos();

$origenQS = $origenFiltro !== "" ? "&origen=" . urlencode($origenFiltro) : "";
$clienteQS = $clienteFiltro !== "" ? "&cliente=" . urlencode($clienteFiltro) : "";

$nombreClienteFiltro = "";
foreach ($clientes as $cli) {
    if ($cli["codigo"] === $clienteFiltro) {
        $nombreClienteFiltro = $cli["codigo"] . " - " . $cli["nombre"];
        break;
    }
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Descuentos Compuestos ESSO
            <small>Estandarización de notas (DSCTO_p1_p2)</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Descuentos Compuestos</li>
        </ol>
    </section>

    <section class="content">

        <!-- FILTRO POR CLIENTE -->
        <div class="box box-default">
            <div class="box-body" style="display:flex; align-items:flex-end; gap:15px; flex-wrap:wrap;">
                <div style="flex:1; min-width:280px;">
                    <label for="clienteDescuento" style="font-weight:bold; font-size:12px; display:block; margin-bottom:5px;">
                        <i class="fa fa-user"></i> Cliente
                    </label>
                    <select class="form-control selectpicker" id="clienteDescuento" data-live-search="true" data-size="10" title="Todos los clientes">
                        <option value="" <?php echo $clienteFiltro === "" ? "selected" : ""; ?>>TODOS los clientes</option>
                        <?php foreach ($clientes as $cli) : ?>
                            <option value="<?php echo htmlspecialchars($cli["codigo"], ENT_QUOTES, "UTF-8"); ?>" <?php echo $clienteFiltro === $cli["codigo"] ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($cli["codigo"] . " - " . $cli["nombre"] . " (" . $cli["total"] . ")", ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($clienteFiltro !== "") : ?>
                <div>
                    <a href="index.php?ruta=descuentos-compuestos<?php echo $origenQS; ?>" class="btn btn-default">
                        <i class="fa fa-times"></i> Quitar filtro
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($clienteFiltro !== "") : ?>
        <div class="callout callout-info" style="margin-bottom:15px;">
            <p style="margin:0;"><b>Trabajando el cliente:</b> <?php echo htmlspecialchars($nombreClienteFiltro !== "" ? $nombreClienteFiltro : $clienteFiltro, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
        <?php endif; ?>

        <!-- CAJAS DE RESUMEN -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3><?php echo number_format($resumen["TOTAL"]["total"]); ?></h3>
                        <p>Total registros</p>
                        <p style="font-size:13px; margin:0; opacity:.9;">
                            <i class="fa fa-plus-circle"></i> Adicional calc.: <b>S/ <?php echo number_format($resumen["CALCULADOS"]["adicional"], 2); ?></b>
                        </p>
                    </div>
                    <div class="icon"><i class="fa fa-list"></i></div>
                    <span class="small-box-footer">Monto total: S/ <?php echo number_format($resumen["TOTAL"]["monto_total"], 2); ?></span>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3><?php echo number_format($resumen["AUTO"]["total"]); ?></h3>
                        <p>Sugeridos (sin confirmar)</p>
                        <p style="font-size:13px; margin:0; opacity:.9;">
                            <i class="fa fa-plus-circle"></i> Adicional calc.: <b>S/ <?php echo number_format($resumen["AUTO"]["monto_pct2"], 2); ?></b>
                        </p>
                    </div>
                    <div class="icon"><i class="fa fa-magic"></i></div>
                    <span class="small-box-footer">Monto total: S/ <?php echo number_format($resumen["AUTO"]["monto_total"], 2); ?></span>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3><?php echo number_format($resumen["REVISAR"]["total"]); ?></h3>
                        <p>Por revisar (manual)</p>
                        <p style="font-size:13px; margin:0; opacity:.9;">
                            <i class="fa fa-ban"></i> Adicional calc.: <b>—</b>
                        </p>
                    </div>
                    <div class="icon"><i class="fa fa-exclamation-triangle"></i></div>
                    <span class="small-box-footer">Monto total: S/ <?php echo number_format($resumen["REVISAR"]["monto_total"], 2); ?></span>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3><?php echo number_format($resumen["MANUAL"]["total"]); ?></h3>
                        <p>Confirmados</p>
                        <p style="font-size:13px; margin:0; opacity:.9;">
                            <i class="fa fa-plus-circle"></i> Adicional calc.: <b>S/ <?php echo number_format($resumen["MANUAL"]["monto_pct2"], 2); ?></b>
                        </p>
                    </div>
                    <div class="icon"><i class="fa fa-check"></i></div>
                    <span class="small-box-footer">Monto total: S/ <?php echo number_format($resumen["MANUAL"]["monto_total"], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Listado de descuentos compuestos</h3>
                <a href="vistas/reportes_excel/rpt_descuentos_compuestos.php" target="_blank" class="btn btn-success btn-sm" style="margin-left:15px;">
                    <i class="fa fa-file-excel-o"></i> Exportar Excel
                </a>
                <div class="box-tools pull-right">
                    <div class="btn-group" role="group" id="filtroOrigenDescuento">
                        <a href="index.php?ruta=descuentos-compuestos<?php echo $clienteQS; ?>" class="btn btn-xs <?php echo $origenFiltro === "" ? "btn-primary" : "btn-default"; ?>">Todos</a>
                        <a href="index.php?ruta=descuentos-compuestos&origen=AUTO<?php echo $clienteQS; ?>" class="btn btn-xs <?php echo $origenFiltro === "AUTO" ? "btn-warning" : "btn-default"; ?>">Sugeridos</a>
                        <a href="index.php?ruta=descuentos-compuestos&origen=REVISAR<?php echo $clienteQS; ?>" class="btn btn-xs <?php echo $origenFiltro === "REVISAR" ? "btn-danger" : "btn-default"; ?>">Por revisar</a>
                        <a href="index.php?ruta=descuentos-compuestos&origen=MANUAL<?php echo $clienteQS; ?>" class="btn btn-xs <?php echo $origenFiltro === "MANUAL" ? "btn-success" : "btn-default"; ?>">Confirmados</a>
                        <a href="index.php?ruta=descuentos-compuestos&origen=DESCARTADO<?php echo $clienteQS; ?>" class="btn btn-xs <?php echo $origenFiltro === "DESCARTADO" ? "btn-info" : "btn-default"; ?>">Descartados<?php echo $resumen["DESCARTADO"]["total"] > 0 ? " (" . number_format($resumen["DESCARTADO"]["total"]) . ")" : ""; ?></a>
                    </div>
                </div>
            </div>

            <div class="box-body">
                <input type="hidden" id="origenFiltroDescuento" value="<?php echo htmlspecialchars($origenFiltro, ENT_QUOTES, "UTF-8"); ?>">
                <input type="hidden" id="clienteFiltroDescuento" value="<?php echo htmlspecialchars($clienteFiltro, ENT_QUOTES, "UTF-8"); ?>">
                <table class="table table-bordered table-striped dt-responsive tablaDescuentosCompuestos" width="100%">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Nota original</th>
                            <th>Nota estándar</th>
                            <th>%</th>
                            <th>Monto %1</th>
                            <th>Monto %2</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </section>
</div>

<!-- MODAL CORRECCIÓN -->
<div class="modal fade" id="modalCorregirDescuento" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formCorregirDescuento">
                <div class="modal-header" style="background:#3c8dbc; color:white;">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-pencil"></i> Corregir descuento</h4>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="correccionId" name="id">

                    <div class="callout callout-info" style="margin-bottom:15px;">
                        <p style="margin:0;"><b>Nota original:</b> <span id="correccionNotaOriginal" style="font-family:monospace;"></span></p>
                        <p style="margin:0;"><b>Monto total:</b> S/ <span id="correccionMonto"></span></p>
                        <p style="margin:0;"><b>Sugerencia automática:</b> <span id="correccionSugerencia" style="font-family:monospace;"></span></p>
                    </div>

                    <div class="form-group">
                        <label>Nota estándar <small class="text-muted">(formato: DSCTO_p1_p2, ej. DSCTO_7_2)</small></label>
                        <input type="text" class="form-control" id="correccionNotaEstandar" name="nota_estandar" placeholder="DSCTO_7_2" style="text-transform:uppercase; font-family:monospace;" required>
                    </div>

                    <div class="form-group">
                        <label>Descomposición estimada</label>
                        <div id="correccionPreview" class="text-muted">Escribe la nota para ver la descomposición…</div>
                    </div>

                    <div class="form-group">
                        <label>Observación <small class="text-muted">(opcional)</small></label>
                        <input type="text" class="form-control" id="correccionObservacion" name="observacion" maxlength="200">
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" id="correccionEstado" name="estado">
                            <option value="CONFIRMADO">CONFIRMADO (cuenta como oficial)</option>
                            <option value="PENDIENTE">PENDIENTE (guardar sin oficializar)</option>
                            <option value="RECHAZADO">RECHAZADO</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
