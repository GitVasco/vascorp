<?php
date_default_timezone_set("America/Lima");
$fechaActual = date("Y-m-d");
$today = $fechaActual;
$oneYearAgo = date("Y-m-d", strtotime("-1 year"));
?>

<div class="content-wrapper enc-page enc-page--con-dev enc-page--crear">

    <section class="content-header">
        <h1 id="encPageTitle">Nueva nota de crédito / débito</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="ver-nota-credito">NC / ND</a></li>
            <li class="active" id="encPageCrumb">Crear</li>
        </ol>
    </section>

    <section class="enc-shell">

        <header class="enc-hero">
            <div>
                <p class="enc-hero__crumb">
                    <a href="inicio">Inicio</a> · <a href="ver-nota-credito">NC / ND</a> · <span id="encHeroCrumb">Crear</span>
                </p>
                <h1 class="enc-hero__title" id="encHeroTitle">Nueva nota de crédito / débito</h1>
            </div>
            <div class="enc-hero__chips">
                <span class="enc-chip enc-chip--nc" id="encHeroChip">NC / ND</span>
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
                                            <label for="radio1">
                                                <input type="radio" class="form-check-input optNotas1" id="radio1" name="optNotas1" value="credito">
                                                Crédito
                                            </label>
                                            <label for="radio2">
                                                <input type="radio" class="form-check-input optNotas1" id="radio2" name="optNotas1" value="debido">
                                                Débito
                                            </label>
                                        </form>
                                    </div>
                                    <div class="enc-field">
                                        <label for="tipoNotaSerie">N° Serie</label>
                                        <select class="form-control input-md selectpicker" id="tipoNotaSerie" name="tipoNotaSerie" data-live-search="true">
                                            <option value="">Seleccionar serie</option>
                                        </select>
                                    </div>
                                    <div class="enc-field">
                                        <label for="tipoNotaDocumento">N° Documento</label>
                                        <input type="text" name="tipoNotaDocumento" id="tipoNotaDocumento" class="form-control input-md" value="0" readonly>
                                    </div>
                                    <div class="enc-field">
                                        <label for="notaFecha">Fecha</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            <input type="date" class="form-control input-lg" name="notaFecha" id="notaFecha" value="<?php echo $fechaActual; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="enc-grid enc-grid--id-motivos">
                                    <div class="enc-field">
                                        <label for="notaMotivo">Motivo</label>
                                        <select class="form-control input-md selectpicker" name="notaMotivo" id="notaMotivo" data-live-search="true" required>
                                            <option value="">Seleccionar motivo</option>
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
                                                echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="enc-check enc-check--cta is-hidden" id="wrapGeneraCtaCte">
                                        <label class="form-check-label" for="radioCtaCte">
                                            <input type="checkbox" class="form-check-input generaCtaCte" id="radioCtaCte" name="generaCtaCte" value="generaCta" disabled>
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
                                                <option value="">Seleccionar cliente</option>
                                                <?php
                                                $clientes = ControladorClientes::ctrMostrarClientes(null, null);
                                                foreach ($clientes as $key => $value) {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["nombre"] . '</option>';
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
                                                <option value="">Seleccionar vendedor</option>
                                                <?php
                                                $vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);
                                                foreach ($vendedores as $key => $value) {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
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
                                                echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="enc-field">
                                    <label for="notaNroFactura">N° Fact/Bol</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                        <input type="text" class="form-control input-lg" name="notaNroFactura" id="notaNroFactura" required>
                                    </div>
                                    <small class="enc-field-help" id="notaNroFacturaHelp"></small>
                                </div>

                                <div class="enc-field">
                                    <label for="notaFechaFactura">Fecha fact.</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control input-md" name="notaFechaFactura" id="notaFechaFactura" min="<?php echo $oneYearAgo; ?>" max="<?php echo $today; ?>" required readonly>
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
                            <textarea class="form-control notaTexto" rows="3" name="notaTexto" id="notaTexto" placeholder="Ingresar detalle sin dar enter ni usar comas."></textarea>
                        </div>
                    </div>
                </div>

                <div class="enc-panel enc-dev">
                    <div class="enc-dev__bar">
                        <div>
                            <span class="enc-dev__tag">Ítems</span>
                            <h2 class="enc-dev__title">Ítems de la nota</h2>
                        </div>
                        <span class="enc-dev__meta">Sin ítems</span>
                    </div>
                    <div class="enc-dev__empty">
                        <strong>Sin ítems de devolución</strong>
                        <span>Si la nota es por devolución de mercadería, los ítems se verán aquí al editarla después de generada.</span>
                    </div>
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
                                <input type="number" class="form-control input-sm text-right" name="notaSubTotal" id="notaSubTotal" step="any" min="0">
                            </div>
                        </div>

                        <div class="enc-tot-row">
                            <label for="notaDsctos">Descuentos</label>
                            <div class="enc-tot-input">
                                <span class="enc-tot-prefix">S/</span>
                                <input type="number" class="form-control input-sm text-right" name="notaDsctos" id="notaDsctos" step="any" min="0" value="0.00">
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
                                <input type="number" class="form-control input-sm text-right" name="notaIGV" id="notaIGV" step="any" min="0" value="0.00" readonly>
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
                            <input type="number" class="form-control input-sm text-right" name="notaTotal" id="notaTotal" step="any" min="0" value="0.00" readonly>
                            <input type="hidden" name="notaUsuario" id="notaUsuario" value="<?php echo $_SESSION["id"] ?>">
                        </div>

                        <div class="enc-sonvarios enc-sonvarios--totales">
                            <span class="enc-sonvarios__lbl">Son</span>
                            <div class="enc-sonvarios__txt" id="notaSonLetras">—</div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="enc-actions">
            <button type="button" class="btn btnGuardarNotaCredito" id="btnBlocNCD"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btnImprimirNotaCredito" tipo="" documento="" disabled><i class="fa fa-print"></i> Imprimir</button>
            <button type="button" class="btn btnAnularNotaCredito"><i class="fa fa-window-close-o"></i> Anular</button>
            <button type="button" class="btn btnEliminarNotaCredito"><i class="fa fa-times"></i> Eliminar</button>
            <button type="button" class="btn btnTerminarNotaCredito"><i class="fa fa-check"></i> Terminar</button>
        </div>

    </section>

</div>

<script>
    window.document.title = "Nueva NC / ND | Vasco System";
</script>

<script>
    $("#notaTexto").on("input", function() {
        var origen = $(this).val();
        var destino = origen.replace(/,/g, "");
        destino = destino.replace(/(\r\n|\n|\r)/gm, "");
        if (origen !== destino) {
            Command: toastr["error"]("Se eliminaron las comas(,)");
        }
        $("#notaTexto").val(destino);
    });
</script>
