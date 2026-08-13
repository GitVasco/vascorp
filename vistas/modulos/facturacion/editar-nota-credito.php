<?php
$tipo = $_GET["tipo"];
$documento = $_GET["documento"];

$venta = ModeloFacturacion::mdlMostrarVentaImpresion($documento, $tipo);

if ($tipo == "E05") {
    $venta["neto"] = $venta["neto"] * -1;
    $venta["dscto"] = $venta["dscto"] * -1;
    $venta["igv"] = $venta["igv"] * -1;
    $venta["total"] = $venta["total"] * -1;
    $esCredito = true;
    $tipoLabel = "Nota de crédito";
} else {
    $venta["neto"] = $venta["neto"];
    $venta["dscto"] = $venta["dscto"];
    $venta["igv"] = $venta["igv"];
    $venta["total"] = $venta["total"];
    $esCredito = false;
    $tipoLabel = "Nota de débito";
}

date_default_timezone_set("America/Lima");
$today = date("Y-m-d");
$oneYearAgo = date("Y-m-d", strtotime("-1 year"));
$estadoDoc = isset($venta["estado"]) ? $venta["estado"] : "";

// Detalle de devolución (misma fuente que el PDF de impresión)
$lineasDev = array();
$unidadesDev = 0;
$esDevolucion = false;
$annoMov = date("Y", strtotime($venta["fecha_emision"]));
$tablaMov = "movimientosjf_" . $annoMov;
try {
    $modelosImp = ControladorFacturacion::ctrMostrarModeloImpresionV2($tablaMov, $documento, $tipo, 0, 200);
    if (is_array($modelosImp)) {
        foreach ($modelosImp as $linea) {
            if (abs((float) $linea["cantidad"]) > 0) {
                $lineasDev[] = $linea;
            }
        }
    }
    $esDevolucion = count($lineasDev) > 0;
    if ($esDevolucion) {
        $unidadImp = ControladorFacturacion::ctrMostrarUnidadesImpresion($documento, $tipo, $tablaMov);
        if (is_array($unidadImp) && isset($unidadImp["cantidad"])) {
            $unidadesDev = abs((float) $unidadImp["cantidad"]);
        }
    }
} catch (Exception $e) {
    $lineasDev = array();
    $esDevolucion = false;
}

$montoLetra = "";
$rutaLetras = "extensiones/cantidad_en_letras.php";
if (file_exists($rutaLetras)) {
    require_once $rutaLetras;
    if (function_exists("CantidadEnLetra")) {
        $montoLetra = CantidadEnLetra(abs((float) $venta["total"]));
    }
}
?>

<div class="content-wrapper enc-page enc-page--con-dev">

    <section class="content-header">
        <h1>Editar <?php echo htmlspecialchars($tipoLabel); ?></h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="ver-nota-credito">NC / ND</a></li>
            <li class="active"><?php echo htmlspecialchars($venta["documento"]); ?></li>
        </ol>
    </section>

    <section class="enc-shell">

        <header class="enc-hero">
            <div>
                <p class="enc-hero__crumb">
                    <a href="inicio">Inicio</a> · <a href="ver-nota-credito">NC / ND</a> · Editar
                </p>
                <h1 class="enc-hero__title">
                    Editar <?php echo htmlspecialchars($tipoLabel); ?>
                    <span class="enc-hero__doc"><?php echo htmlspecialchars($venta["documento"]); ?></span>
                </h1>
            </div>
            <div class="enc-hero__chips">
                <span class="enc-chip <?php echo $esCredito ? 'enc-chip--nc' : 'enc-chip--nd'; ?>">
                    <?php echo $esCredito ? 'Nota crédito' : 'Nota débito'; ?>
                </span>
                <span class="enc-chip enc-chip--doc"><?php echo htmlspecialchars($venta["documento"]); ?></span>
                <?php if ($estadoDoc !== "") : ?>
                    <span class="enc-chip enc-chip--estado"><?php echo htmlspecialchars($estadoDoc); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="enc-layout">
            <div class="enc-main">
                <div class="enc-panel">
                    <div class="enc-panel__body">

                        <section class="enc-block">
                            <div class="enc-block__head">
                                <h2 class="enc-block__title">Datos generales</h2>
                                <p class="enc-block__hint">Identificación de la nota, cliente y vendedor</p>
                            </div>

                            <div class="enc-sub">
                                <p class="enc-sub__label">Identificación de la nota</p>
                                <div class="enc-grid enc-grid--id">
                                    <div class="enc-field enc-field--tipo">
                                        <label>Tipo</label>
                                        <form method="post" class="enc-seg" aria-label="Tipo de nota">
                                            <?php if ($esCredito) : ?>
                                                <label for="radio1">
                                                    <input type="radio" class="form-check-input optNotas1" id="radio1" name="optNotas1" value="credito" checked>
                                                    Crédito
                                                </label>
                                                <label for="radio2">
                                                    <input type="radio" class="form-check-input optNotas1" id="radio2" name="optNotas1" value="debito" disabled>
                                                    Débito
                                                </label>
                                            <?php else : ?>
                                                <label for="radio1">
                                                    <input type="radio" class="form-check-input optNotas1" id="radio1" name="optNotas1" value="credito" disabled>
                                                    Crédito
                                                </label>
                                                <label for="radio2">
                                                    <input type="radio" class="form-check-input optNotas1" id="radio2" name="optNotas1" value="debito" checked>
                                                    Débito
                                                </label>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    <div class="enc-field">
                                        <label for="tipoNotaSerie">N° Serie</label>
                                        <input type="text" class="form-control input-md" id="tipoNotaSerie" name="tipoNotaSerie" value="<?php echo substr($venta["documento"], 0, 4); ?>" readonly>
                                    </div>
                                    <div class="enc-field">
                                        <label for="tipoNotaDocumento">N° Documento</label>
                                        <input type="text" name="tipoNotaDocumento" id="tipoNotaDocumento" class="form-control input-md" value="<?php echo $venta["documento"]; ?>" readonly>
                                    </div>
                                    <div class="enc-field">
                                        <label for="notaFecha">Fecha</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            <input type="date" class="form-control input-lg" name="notaFecha" id="notaFecha" value="<?php echo $venta["fecha_emision"] ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="enc-grid enc-grid--id-motivos">
                                    <div class="enc-field">
                                        <label for="notaMotivo">Motivo</label>
                                        <select class="form-control input-md selectpicker" name="notaMotivo" id="notaMotivo" data-live-search="true" required>
                                            <option value="">Seleccionar motivo</option>
                                            <?php
                                            $valor = ($_GET["tipo"] == "S05") ? "TMOTD" : "TMOT";
                                            $item = "tipo_dato";
                                            $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                            foreach ($documentos as $key => $value) {
                                                if ($value["codigo"] == $venta["motivo"]) {
                                                    echo '<option value="' . $value["codigo"] . '" selected>' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                } else {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="enc-field">
                                        <label for="notaTipoCont">Tipo cont.</label>
                                        <select class="form-control input-md selectpicker" name="notaTipoCont" id="notaTipoCont" data-live-search="true" required>
                                            <option value="">Seleccionar tipo contable</option>
                                            <?php
                                            $item = "tipo_dato";
                                            $valor = "TCON";
                                            $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                            foreach ($documentos as $key => $value) {
                                                if ($value["codigo"] == $venta["tip_cont"]) {
                                                    echo '<option value="' . $value["codigo"] . '" selected>' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                } else {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="enc-check enc-check--cta <?php echo $esCredito ? 'is-hidden' : ''; ?>" id="wrapGeneraCtaCte">
                                        <label class="form-check-label" for="radioCtaCte">
                                            <input type="checkbox" class="form-check-input generaCtaCte" id="radioCtaCte" name="generaCtaCte" value="generaCta" <?php echo $esCredito ? 'disabled' : ''; ?>>
                                            Genera cta. cte.
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="enc-sub" style="margin-top:14px">
                                <p class="enc-sub__label">Cliente y vendedor</p>
                                <div class="enc-grid enc-grid--2">
                                    <div class="enc-field">
                                        <label for="selectNotaCliente">Cliente</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                            <select class="form-control input-lg selectpicker" data-live-search="true" name="selectNotaCliente" id="selectNotaCliente">
                                                <?php
                                                $valor = $venta["cliente"];
                                                $client2 = ControladorClientes::ctrMostrarClientesP(null, null);
                                                foreach ($client2 as $key => $value) {
                                                    if ($value["codigo"] == $valor) {
                                                        echo '<option value="' . $value["codigo"] . '" selected>' . $value["codigo"] . " - " . $value["nombre"] . '</option>';
                                                    } else {
                                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["nombre"] . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="enc-field">
                                        <label for="selectNotaVendedor">Vendedor</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-briefcase"></i></span>
                                            <select class="form-control input-md selectpicker" data-live-search="true" name="selectNotaVendedor" id="selectNotaVendedor">
                                                <?php
                                                $valor = $venta["vendedor"];
                                                $vendedores2 = ControladorVendedores::ctrMostrarVendedores(null, null);
                                                foreach ($vendedores2 as $key => $value) {
                                                    if ($value["codigo"] == $valor) {
                                                        echo '<option value="' . $value["codigo"] . '" selected>' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                    } else {
                                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="enc-block">
                            <div class="enc-block__head">
                                <h2 class="enc-block__title">Documento de origen</h2>
                                <p class="enc-block__hint">Factura / boleta que motiva la nota</p>
                            </div>

                            <div class="enc-grid enc-grid--origen">
                                <div class="enc-field">
                                    <label for="selectNotaDocumento">Tipo Doc</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>
                                        <select class="form-control input-md selectpicker" data-live-search="true" name="selectNotaDocumento" id="selectNotaDocumento">
                                            <option value="">Seleccionar documento</option>
                                            <?php
                                            $item = "tipo_dato";
                                            $valor = "tdoc";
                                            $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                            foreach ($documentos as $key => $value) {
                                                if ($value["codigo"] == $venta["tipo_doc"]) {
                                                    echo '<option value="' . $value["codigo"] . '" selected>' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                } else {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="enc-field">
                                    <label for="notaNroFactura">N° Fact/Bol</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                        <input type="text" class="form-control input-lg" name="notaNroFactura" id="notaNroFactura" value="<?php echo $venta["doc_origen"] ?>" required>
                                    </div>
                                    <small class="enc-field-help" id="notaNroFacturaHelp"></small>
                                </div>

                                <div class="enc-field">
                                    <label for="notaFechaFactura">Fecha fact.</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control input-md" name="notaFechaFactura" id="notaFechaFactura" min="<?php echo $oneYearAgo; ?>" max="<?php echo $today; ?>" value="<?php echo $venta["fecha_origen"] ?>" required readonly>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>
                </div>
            </div>

            <section class="enc-items">
                <div class="enc-panel enc-detail-panel">
                    <div class="enc-panel__body">
                        <div class="enc-block__head">
                            <h2 class="enc-block__title">Detalle</h2>
                            <p class="enc-block__hint">Glosa de la nota</p>
                        </div>
                        <div class="enc-field">
                            <label for="notaTexto">Observación / glosa</label>
                            <textarea class="form-control" rows="3" name="notaTexto" id="notaTexto"><?php echo $venta["observacion"] ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="enc-panel enc-dev">
                    <div class="enc-dev__bar">
                        <div>
                            <span class="enc-dev__tag"><?php echo $esDevolucion ? 'Devolución' : 'Ítems'; ?></span>
                            <h2 class="enc-dev__title">Ítems <?php echo $esDevolucion ? 'devueltos' : 'de la nota'; ?></h2>
                        </div>
                        <?php if ($esDevolucion) : ?>
                            <span class="enc-dev__meta"><?php echo count($lineasDev); ?> modelo(s)</span>
                        <?php else : ?>
                            <span class="enc-dev__meta">Sin ítems</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($esDevolucion) : ?>
                    <div class="enc-dev__table-wrap">
                        <table class="enc-dev__table">
                            <thead>
                                <tr>
                                    <th>Modelo</th>
                                    <th>Descripción</th>
                                    <th class="is-num">Cant.</th>
                                    <th class="is-num">P. unit.</th>
                                    <th class="is-num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lineasDev as $linea) : ?>
                                    <tr>
                                        <td class="is-mono"><?php echo htmlspecialchars($linea["modelo"]); ?></td>
                                        <td><?php echo htmlspecialchars($linea["nombre"]); ?></td>
                                        <td class="is-num"><?php echo number_format(abs((float) $linea["cantidad"]), 2); ?></td>
                                        <td class="is-num"><?php echo number_format((float) $linea["precio"], 2); ?></td>
                                        <td class="is-num"><?php echo number_format(abs((float) $linea["total"]), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else : ?>
                    <div class="enc-dev__empty">
                        <strong>Sin devolución de mercadería</strong>
                        <span>Esta nota no tiene ítems en movimientos. El espacio queda reservado para cuando sí haya devolución.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="enc-aside">
                <div class="enc-panel enc-totals">
                    <div class="enc-panel__body">
                        <h2 class="enc-totals__title">Resumen de importes</h2>
                        <p class="enc-totals__hint">Montos en soles (S/)</p>

                        <div class="enc-tot-row">
                            <label for="notaSubTotal">Subtotal <small>op. gravada</small></label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaSubTotal" id="notaSubTotal" step="any" min="0" value="<?php echo $venta["neto"]; ?>">
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaDsctos">Descuentos</label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaDsctos" id="notaDsctos" step="any" min="0" value="<?php echo $venta["dscto"]; ?>">
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaFlete">Flete</label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaFlete" id="notaFlete" step="any" min="0" value="0.00">
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaOtros">Otros</label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaOtros" id="notaOtros" step="any" min="0" value="0.00">
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaIGV">
                                IGV
                                <span class="enc-tot-pct">18%</span>
                            </label>
                            <input type="hidden" name="IGV" id="IGV" value="18.00">
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaIGV" id="notaIGV" step="any" min="0" value="<?php echo number_format(($venta["igv"]), 2, '.', ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaNoAfecto">No afecto</label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaNoAfecto" id="notaNoAfecto" step="any" min="0" value="0.00">
                            </div>
                        </div>

                        <div class="enc-tot-total">
                            <div class="enc-tot-total__top">
                                <label for="notaTotal">Total nota</label>
                                <span class="enc-tot-total__cur">S/</span>
                            </div>
                            <input type="number" class="form-control input-sm text-right" name="notaTotal" id="notaTotal" step="any" min="0" value="<?php echo $venta["total"]; ?>" readonly>
                            <input type="hidden" name="notaUsuario" id="notaUsuario" value="<?php echo $_SESSION["id"] ?>">
                        </div>

                        <div class="enc-sonvarios enc-sonvarios--totales">
                            <span class="enc-sonvarios__lbl">Son</span>
                            <div class="enc-sonvarios__txt"><?php echo $montoLetra !== "" ? htmlspecialchars($montoLetra) : "—"; ?></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="enc-actions">
            <button type="button" class="btn btnGuardarNotaCredito" data-modo="editar"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btnImprimirNotaCredito" tipo="<?php echo $venta["tipo"] ?>" documento="<?php echo $venta["documento"] ?>"><i class="fa fa-print"></i> Imprimir</button>
            <button type="button" class="btn btnAnularNotaCredito"><i class="fa fa-window-close-o"></i> Anular</button>
            <button type="button" class="btn btnEliminarNotaCredito"><i class="fa fa-times"></i> Eliminar</button>
            <button type="button" class="btn btnTerminarNotaCredito"><i class="fa fa-check"></i> Terminar</button>
        </div>

    </section>

</div>

<script>
    window.document.title = <?php echo json_encode(
        "Editar " . ($esCredito ? "NC" : "ND") . " " . $venta["documento"] . " | Vasco System",
        JSON_UNESCAPED_UNICODE
    ); ?>;
</script>

<script>
    $(".notaTexto").on("input", function() {
        var origen = $(this).val();
        console.log("🚀 ~ file: editar-nota-credito.php:431 ~ $ ~ origen:", origen)

        // Reemplaza las comas con un espacio vacío
        var destino = origen.replace(/,/g, "");

        // Elimina los saltos de línea
        destino = destino.replace(/(\r\n|\n|\r)/gm, "");

        // Muestra el mensaje de error si se eliminaron las comas
        if (origen !== destino) {
            Command: toastr["error"]("Se eliminaron las comas(,)");
        }

        // Actualiza el contenido del textarea
        $("#notaTexto").val(destino);
    });
</script>
