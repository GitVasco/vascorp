<?php

date_default_timezone_set("America/Lima");

$periodoActual = ControladorMetasVendedor::ctrPeriodoActual();
$anioFiltro = isset($_GET["anio"]) ? (int) $_GET["anio"] : $periodoActual["anio"];
$mesFiltro = isset($_GET["mes"]) ? (int) $_GET["mes"] : $periodoActual["mes"];
$periodoFiltro = ControladorMetasVendedor::ctrNormalizarPeriodo($anioFiltro, $mesFiltro);
$anioFiltro = $periodoFiltro["anio"];
$mesFiltro = $periodoFiltro["mes"];

$meses = ControladorTalleres::ctrMes();
$vendedores = ControladorMetasVendedor::ctrMostrarVendedores();
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
        <h1>Metas mensuales por vendedor</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Metas vendedor</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-primary">
            <div class="box-header with-border">
                <form method="get" class="form-inline mv-filtro-periodo">
                    <input type="hidden" name="ruta" value="metas-vendedor">
                    <div class="form-group">
                        <label for="mvFiltroAnio">Año</label>
                        <select class="form-control input-sm" id="mvFiltroAnio" name="anio">
                            <?php for ($a = $periodoActual["anio"]; $a <= $periodoActual["anio"] + 2; $a++) : ?>
                                <option value="<?php echo $a; ?>" <?php echo $a === $anioFiltro ? "selected" : ""; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-left:10px;">
                        <label for="mvFiltroMes">Mes</label>
                        <select class="form-control input-sm" id="mvFiltroMes" name="mes">
                            <?php foreach ($meses as $mesItem) :
                                $codMes = (int) $mesItem["codigo"];
                                if ($anioFiltro === $periodoActual["anio"] && $codMes < $periodoActual["mes"]) {
                                    continue;
                                }
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

                <div class="pull-right">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarMeta">
                        <i class="fa fa-plus"></i> Registrar meta
                    </button>
                </div>
            </div>

            <div class="box-body">
                <p class="text-muted">
                    Período: <strong><?php echo htmlspecialchars($nombreMes) . " " . $anioFiltro; ?></strong>.
                    Venta real: <code>ventajf</code> solo tipos S02, S03, S70, E05, S05.
                    Cobranza real: ingresos efectivo en <code>cuenta_ctejf</code>.
                </p>

                <table class="table table-bordered table-striped dt-responsive tablaMetasVendedor" width="100%"
                    data-anio="<?php echo $anioFiltro; ?>"
                    data-mes="<?php echo $mesFiltro; ?>">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Meta venta</th>
                            <th>Venta real</th>
                            <th>% venta</th>
                            <th>Meta cobranza</th>
                            <th>Cobranza real</th>
                            <th>% cobranza</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </section>
</div>

<div id="modalAgregarMeta" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Registrar meta mensual</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Vendedor</label>
                            <select class="form-control selectpicker input-lg" name="nuevoCodVendedor" data-live-search="true" required>
                                <option value="">Seleccione vendedor</option>
                                <?php foreach ($vendedores as $vendedor) : ?>
                                    <option value="<?php echo htmlspecialchars($vendedor["codigo"]); ?>">
                                        <?php echo htmlspecialchars($vendedor["codigo"] . " - " . $vendedor["descripcion"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Año</label>
                                    <select class="form-control input-lg" name="nuevoAnio" required>
                                        <?php for ($a = $periodoActual["anio"]; $a <= $periodoActual["anio"] + 2; $a++) : ?>
                                            <option value="<?php echo $a; ?>" <?php echo $a === $anioFiltro ? "selected" : ""; ?>><?php echo $a; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Mes</label>
                                    <select class="form-control input-lg" name="nuevoMes" required>
                                        <?php foreach ($meses as $mesItem) :
                                            $codMes = (int) $mesItem["codigo"];
                                            if ($anioFiltro === $periodoActual["anio"] && $codMes < $periodoActual["mes"]) {
                                                continue;
                                            }
                                        ?>
                                            <option value="<?php echo $codMes; ?>" <?php echo $codMes === $mesFiltro ? "selected" : ""; ?>>
                                                <?php echo htmlspecialchars($mesItem["descripcion"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Meta venta (S/)</label>
                            <div class="input-group">
                                <span class="input-group-addon">S/</span>
                                <input type="number" min="0" step="0.01" class="form-control input-lg" name="nuevaMetaVenta" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Meta cobranza (S/) <small class="text-muted">opcional por ahora</small></label>
                            <div class="input-group">
                                <span class="input-group-addon">S/</span>
                                <input type="number" min="0" step="0.01" class="form-control input-lg" name="nuevaMetaCobranza" placeholder="Dejar vacío si no aplica">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar meta</button>
                </div>
            </form>
            <?php
            $crearMeta = new ControladorMetasVendedor();
            $crearMeta->ctrCrearMeta();
            ?>
        </div>
    </div>
</div>

<div id="modalEditarMeta" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar meta</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <input type="hidden" id="idMeta" name="idMeta">
                        <div class="form-group">
                            <label>Vendedor</label>
                            <input type="text" class="form-control input-lg" id="editarVendedorLabel" readonly>
                        </div>
                        <div class="form-group">
                            <label>Período</label>
                            <input type="text" class="form-control input-lg" id="editarPeriodoLabel" readonly>
                        </div>
                        <div class="form-group">
                            <label>Meta venta (S/)</label>
                            <div class="input-group">
                                <span class="input-group-addon">S/</span>
                                <input type="number" min="0" step="0.01" class="form-control input-lg" id="editarMetaVenta" name="editarMetaVenta" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Meta cobranza (S/) <small class="text-muted">opcional</small></label>
                            <div class="input-group">
                                <span class="input-group-addon">S/</span>
                                <input type="number" min="0" step="0.01" class="form-control input-lg" id="editarMetaCobranza" name="editarMetaCobranza">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
            <?php
            $editarMeta = new ControladorMetasVendedor();
            $editarMeta->ctrEditarMeta();
            ?>
        </div>
    </div>
</div>

<?php
$eliminarMeta = new ControladorMetasVendedor();
$eliminarMeta->ctrEliminarMeta();
?>

<script>
    window.document.title = "Metas vendedor";
</script>
