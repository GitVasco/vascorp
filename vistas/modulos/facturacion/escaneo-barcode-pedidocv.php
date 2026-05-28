<?php

/**
 * Escaneo código de barras + cierre (condición / guardar) — pedido CV temporal.
 * Layout 2 columnas; guardado con el mismo POST que crear-pedidocv (ctrCrearPedidoTotales).
 */

$pedidoParam = isset($_GET["pedido"]) ? trim((string) $_GET["pedido"]) : "";

require_once "controladores/pedidos.controlador.php";
require_once "controladores/vendedor.controlador.php";
require_once "controladores/agencia.controlador.php";
require_once "controladores/condicionventa.controlador.php";

$pedidoCab = null;

if ($pedidoParam !== "") {

    $pedidoCab = ControladorPedidos::ctrMostrarTemporal($pedidoParam);
}

$tienePedidoActivo = ($pedidoCab && !empty($pedidoCab["codigo"]) && isset($pedidoCab["codigo"]));
$tienePedidoGetInvalido = ($pedidoParam !== "" && !$tienePedidoActivo);

$vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);

usort($vendedores, function ($a, $b) {

    return strcmp($a["codigo"], $b["codigo"]);
});


/** Pantalla escaneo: sólo vendedores cuyo código empieza con 08 */
$vendedores = array_values(array_filter($vendedores, function ($item) {

    $codigo = isset($item["codigo"]) ? trim((string) $item["codigo"]) : "";

    return (strlen($codigo) >= 2 && substr($codigo, 0, 2) === "08");

}));

$itemAg = null;
$valorAg = null;
$agencias = ControladorAgencias::ctrMostrarAgencias($itemAg, $valorAg);

$brutoIni = 0;
$impIni = 0;
$totalIni = 0;
$listaIni = "";

if ($tienePedidoActivo) {

    $listaIni = isset($pedidoCab["lista"]) ? (string) $pedidoCab["lista"] : "";

    $totRow = ControladorPedidos::ctrMostrarTemporalTotal($pedidoParam);

    if ($totRow && isset($totRow["totalArt"])) {

        $brutoIni = floatval($totRow["totalArt"]);
    }

    $subIni = $brutoIni;

    $impIni = ($listaIni === "precio1") ? 0 : round($subIni * 0.18, 2);

    $totalIni = ($listaIni === "precio1") ? round($subIni, 2) : round($subIni + $impIni, 2);

}

$cliIni = "";
$venIni = "";
$agIni = "";

if ($tienePedidoActivo) {

    $cliIni = isset($pedidoCab["cliente"]) ? (string) $pedidoCab["cliente"] : "";

    $venIni = isset($pedidoCab["vendedor"]) ? (string) $pedidoCab["vendedor"] : "";

    $agIni = isset($pedidoCab["agencia"]) ? (string) $pedidoCab["agencia"] : "";

}

/* Líneas iniciales del detalle (lista bajo Pedido activo) */
$listaArticulosEscaneoIni = array();

if ($tienePedidoActivo) {

    $listaArticulosEscaneoIni = ControladorPedidos::ctrMostrarDetallesTemporalB($pedidoParam);

}

$idUsuarioSes = isset($_SESSION["id"]) ? $_SESSION["id"] : "";

?>

<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear pedido

            <small>Código de barras (CV)</small>

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li><a href="pedidoscv">Pedidos</a></li>

            <li class="active">Crear pedido · código de barras</li>

        </ol>

    </section>

    <section class="content">

        <?php if ($tienePedidoGetInvalido) : ?>

            <div class="alert alert-warning">

                No existe cabecera temporal para <strong><?php echo htmlspecialchars($pedidoParam, ENT_QUOTES, "UTF-8"); ?></strong>.


                Cree la cabecera en la columna izquierda o <a href="escaneo-barcode-pedidocv">vuelva a ingresar sin código</a>.
            </div>

        <?php endif; ?>

        <form role="form" method="post" id="formularioEscaneoPedidoCv" autocomplete="off">

            <input type="hidden" name="idUsuario" id="idUsuario" value="<?php echo htmlspecialchars((string) $idUsuarioSes, ENT_QUOTES, "UTF-8"); ?>">

            <input type="hidden" name="nuevoCodigo" id="formEscaneoNuevoCodigo" value="<?php echo $tienePedidoActivo ? htmlspecialchars($pedidoParam, ENT_QUOTES, "UTF-8") : ""; ?>">

            <input type="hidden" name="seleccionarCliente" id="escaneoPostCliente" value="<?php echo htmlspecialchars($cliIni, ENT_QUOTES, "UTF-8"); ?>">

            <input type="hidden" name="seleccionarVendedor" id="escaneoPostVendedor" value="<?php echo htmlspecialchars($venIni, ENT_QUOTES, "UTF-8"); ?>">

            <input type="hidden" name="descPer" id="escaneoDescPer" value="0">

            <?php /* Totales que espera ctrCrearPedidoTotales (valores en_US para evitar coma) */ ?>

            <input type="hidden" name="nuevoSubTotalA" id="escaneoHSubA" value="<?php echo number_format($brutoIni, 2, ".", ""); ?>">

            <input type="hidden" name="descTotal" id="escaneoHDesc" value="0.00">

            <input type="hidden" name="subTotal" id="escaneoHSubNet" value="<?php echo number_format($brutoIni, 2, ".", ""); ?>">

            <input type="hidden" name="impTotal" id="escaneoHImp" value="<?php echo number_format($impIni, 2, ".", ""); ?>">

            <input type="hidden" name="nuevoTotal" id="escaneoHTotal" value="<?php echo number_format($totalIni, 2, ".", ""); ?>">

            <div id="vistaEscaneoCvCompleta">

                <div class="callout callout-info" style="margin-bottom:18px;">

                    <p style="margin:0;"><strong>Izquierda:</strong> cabecera del temporal. <strong>Derecha:</strong> lector, totales, <em>condición de venta</em> y <strong>Guardar pedido</strong>.</p>

                </div>

                <div class="row">

                    <div class="col-xs-12 col-md-6">

                        <div id="escaneoResumenCabPhp" class="box box-solid box-default" <?php echo $tienePedidoActivo ? "" : 'style="display:none;"'; ?>>

                            <div class="box-header with-border">

                                <h3 class="box-title">Pedido activo</h3>

                                <div class="box-tools pull-right">

                                    <button type="button" class="btn btn-default btn-sm" id="btnEscaneoNuevoPedidoPhp" title="Nuevo temporal">

                                        <i class="fa fa-refresh"></i> Nuevo

                                    </button>

                                </div>

                            </div>

                            <div class="box-body">

                                <p class="lead" style="margin-top:0;"><code id="escaneoLblCodPhp"><?php echo $tienePedidoActivo ? htmlspecialchars($pedidoParam, ENT_QUOTES, "UTF-8") : ""; ?></code></p>

                                <p>Cliente: <strong id="escaneoLblCliPhp"><?php echo $tienePedidoActivo ? htmlspecialchars($cliIni, ENT_QUOTES, "UTF-8") : ""; ?></strong></p>

                                <?php if ($tienePedidoActivo && $listaIni !== "") : ?>

                                    <p class="text-muted">Lista precios: <?php echo htmlspecialchars($listaIni, ENT_QUOTES, "UTF-8"); ?></p>

                                <?php endif; ?>

                                <a href="index.php?ruta=crear-pedidocv&amp;pedido=<?php echo $tienePedidoActivo ? urlencode($pedidoParam) : ""; ?>" id="lnkEscaneoIrCrearPhp" class="btn btn-sm btn-default" <?php echo $tienePedidoActivo ? "" : 'style="display:none;"'; ?>>

                                    <i class="fa fa-external-link"></i> Ver en Crear pedido

                                </a>

                            </div>

                        </div>

                        <div id="escaneoResumenCabJs" class="box box-solid box-info" style="display:none;">

                            <div class="box-header with-border">

                                <h3 class="box-title">Pedido activo</h3>

                                <div class="box-tools pull-right">

                                    <button type="button" class="btn btn-default btn-sm" id="btnEscaneoNuevoPedidoJs"><i class="fa fa-refresh"></i> Nuevo</button>

                                </div>

                            </div>

                            <div class="box-body">

                                <p id="textoEscaneoResumenJs"></p>

                                <a href="#" id="lnkEscaneoIrCrearJs" class="btn btn-sm btn-default"><i class="fa fa-external-link"></i> Ver en Crear pedido</a>

                            </div>

                        </div>

                        <div id="escaneoPanelListadoArticulos" class="box box-solid box-success" <?php echo $tienePedidoActivo ? "" : 'style="display:none;"'; ?>>

                            <div class="box-header with-border">

                                <h3 class="box-title"><i class="fa fa-list"></i> Artículos agregados</h3>



                            </div>



                            <div class="box-body" style="padding-top:10px;padding-bottom:10px;">



                                <p class="help-block small" style="margin-top:0;">




                                    Cambie <strong>Cantidad</strong> o <strong>P. unitario</strong> y salga del campo (o pulse Enter)



                                    para guardar. El botón rojo elimina la línea del temporal.


                                </p>



                                <div class="table-responsive">
                                    <table class="table table-striped table-condensed table-bordered" style="margin-bottom:0;">

                                        <thead>

                                            <tr>

                                                <th>SKU</th>

                                                <th>Descripción</th>

                                                <th class="text-right" style="min-width:6em;">Cant.</th>

                                                <th class="text-right" style="min-width:7em;">P. unit.</th>

                                                <th class="text-right">Total</th>

                                                <th class="text-center" style="width:3.5em;">&nbsp;</th>

                                            </tr>

                                        </thead>

                                        <tbody id="escaneoListaArticulosBody" <?php echo $tienePedidoActivo ? 'data-esc-skip-ini-listado="1"' : ""; ?>>

                                            <?php if (empty($listaArticulosEscaneoIni)) : ?>

                                                <tr class="escaneoListaArticulosVacío">




                                                    <td colspan="6" class="text-muted">Sin artículos aún. Escanee códigos de barras para agregar líneas.</td>


                                                </tr>

                                            <?php else : ?>

                                                <?php foreach ($listaArticulosEscaneoIni as $filaLst) :

                                                    $skuLst = isset($filaLst["articulo"]) ? (string) $filaLst["articulo"] : "";

                                                    $packLst = "";

                                                    if (isset($filaLst["packing"])) {

                                                        $packLst = trim((string) $filaLst["packing"]);

                                                    }

                                                    $descLst = ($packLst !== "") ? $packLst : $skuLst;

                                                    $cantLst = isset($filaLst["cantidad"]) ? $filaLst["cantidad"] : "";

                                                    $preLst = isset($filaLst["precio"]) ? floatval($filaLst["precio"]) : 0;

                                                    $totLst = isset($filaLst["total"]) ? floatval($filaLst["total"]) : 0;

                                                    ?>

                                                    <?php
                                                    $cantInt = intval($cantLst);
                                                    $preFmt = number_format($preLst, 2, ".", "");
                                                    $totFmt = number_format($totLst, 2, ".", "");
                                                    $skuAttrOut = htmlspecialchars($skuLst, ENT_QUOTES, "UTF-8");
                                                    $descOut = htmlspecialchars($descLst, ENT_QUOTES, "UTF-8");
                                                    $cantAttrOut = htmlspecialchars((string) $cantInt, ENT_QUOTES, "UTF-8");
                                                    $precAttrOut = htmlspecialchars($preFmt, ENT_QUOTES, "UTF-8");
                                                    ?>

                                                    <tr class="escaneoCvFilaLinea" data-articulo="<?php echo $skuAttrOut; ?>" data-sync-cant="<?php echo $cantAttrOut; ?>" data-sync-prec="<?php echo $precAttrOut; ?>">

                                                        <td><?php echo $skuAttrOut; ?></td>

                                                        <td><?php echo $descOut; ?></td>

                                                        <td class="text-right" style="max-width:5.5rem;">

                                                            <input type="number" class="form-control input-sm escaneoCvInputCant text-right" min="1" step="1" value="<?php echo $cantAttrOut; ?>" autocomplete="off" />

                                                        </td>

                                                        <td class="text-right" style="max-width:6.5rem;">

                                                            <input type="number" class="form-control input-sm escaneoCvInputPrecio text-right" min="0" step="0.01" value="<?php echo $precAttrOut; ?>" autocomplete="off" />

                                                        </td>

                                                        <td class="text-right escaneoCvColTotal"><?php echo htmlspecialchars($totFmt, ENT_QUOTES, "UTF-8"); ?></td>

                                                        <td class="text-center">

                                                            <button type="button" class="btn btn-danger btn-xs escaneoCvBtnEliminarLinea" title="Eliminar línea">

                                                                <i class="fa fa-times"></i>

                                                            </button>

                                                        </td>

                                                    </tr>

                                                <?php endforeach; ?>

                                            <?php endif; ?>

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                        <div id="escaneoBloqueFormularioCab" <?php echo $tienePedidoActivo ? 'style="display:none;"' : ""; ?>>

                            <div class="box box-primary">

                                <div class="box-header with-border">

                                    <h3 class="box-title"><i class="fa fa-user"></i> Cabecera del pedido temporal</h3>

                                </div>

                                <div class="box-body">

                                    <div id="panelEscaneoCvNuevoPedido">

                                        <input type="hidden" id="escaneoListaPreciosCab" value="">

                                        <p class="help-block small">Abra el desplegable de <strong>Cliente</strong> para cargar el listado (la primera vez puede demorar unos segundos). Luego elija cliente, vendedor y agencia.</p>

                                        <div class="form-group">

                                            <label>Cliente</label>

                                            <select class="form-control selectpicker" id="escaneoSeleccionarCliente" data-live-search="true" data-width="100%" data-size="10" title="Seleccionar cliente" <?php echo $tienePedidoActivo ? "disabled" : ""; ?>>

                                            </select>

                                        </div>

                                        <div class="form-group">

                                            <label>Vendedor</label>

                                            <select class="form-control selectpicker" id="escaneoSeleccionarVendedor" data-live-search="true" data-width="100%" data-size="10" title="Seleccionar vendedor" <?php echo $tienePedidoActivo ? "disabled" : ""; ?>>

                                                <option value="">Seleccionar vendedor</option>

                                                <?php foreach ($vendedores as $value) : ?>

                                                    <option value="<?php echo htmlspecialchars((string) $value["codigo"], ENT_QUOTES, "UTF-8"); ?>">

                                                        <?php echo htmlspecialchars((string) $value["codigo"] . " — " . (string) $value["descripcion"], ENT_QUOTES, "UTF-8"); ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="form-group">

                                            <label>Agencia <small class="text-muted">(cabecera)</small></label>

                                            <select class="form-control selectpicker" id="escaneoAgenciaCab" data-live-search="true" data-width="100%" data-size="10" <?php echo $tienePedidoActivo ? "disabled" : ""; ?>>

                                                <option value="">Seleccionar agencia</option>

                                                <?php foreach ($agencias as $value) : ?>

                                                    <option value="<?php echo htmlspecialchars($value["id"], ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($value["id"] . " — " . $value["nombre"], ENT_QUOTES, "UTF-8"); ?></option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <button type="button" id="btnEscaneoCrearCabTemporal" class="btn btn-success btn-lg btn-block" <?php echo $tienePedidoActivo ? "disabled" : ""; ?>>

                                            <i class="fa fa-check"></i> Crear pedido temporal

                                        </button>

                                    </div>

                                </div>

                            </div>

                            <div class="box box-default">

                                <div class="box-header with-border">

                                    <h3 class="box-title"><i class="fa fa-folder-open-o"></i> ¿Ya tienes un pedido temporal abierto?</h3>

                                    <div class="box-tools pull-right">

                                        <button type="button" class="btn btn-box-tool" data-widget="collapse" title="Contraer o expandir"><i class="fa fa-minus"></i></button>

                                    </div>

                                </div>

                                <div class="box-body">

                                    <div class="callout callout-info" style="margin-top:0;">

                                        <p style="margin:0 0 10px 0;"><strong>Use esto sólo si</strong> ya generó la cabecera del temporal en otra pantalla (por ejemplo desde <strong>Lista de pedidos</strong>, <strong>Crear pedido</strong> o el ícono de código de barras en la tabla).</p>

                                        <ol style="margin-bottom:0;padding-left:1.25em;">

                                            <li>Escriba el <strong>número de pedido temporal</strong> (el mismo que aparece en la columna «Código» en la lista de pedidos).</li>

                                            <li>Pulse <strong>«Abrir pedido»</strong> y podrá escanear o cerrar ese pedido en esta vista.</li>

                                        </ol>

                                    </div>

                                    <div class="form-group">

                                        <label for="pedidoEscaneoCvExistente">Número del pedido temporal</label>

                                        <div class="input-group input-group-lg">

                                            <span class="input-group-addon">N.º</span>

                                            <input type="text" class="form-control" id="pedidoEscaneoCvExistente" placeholder="Mismo valor que columna «Código» en la lista de pedidos" autocomplete="off" value="<?php echo $tienePedidoActivo ? htmlspecialchars($pedidoParam, ENT_QUOTES, "UTF-8") : ""; ?>" title="Igual que en la tabla de pedidos, columna Código">

                                            <span class="input-group-btn">

                                                <button type="button" class="btn btn-primary" id="btnEscaneoIrPedidoManual">

                                                    <i class="fa fa-arrow-right"></i> Abrir pedido

                                                </button>

                                            </span>

                                        </div>

                                        <span class="help-block small" style="margin-bottom:0;">También puede pulsar <strong>Enter</strong> después de escribir.</span>

                                    </div>

                                    <p class="help-block small" style="margin-bottom:0;margin-top:14px;"><i class="fa fa-lightbulb-o"></i> Si <strong>aún no</strong> tiene pedido temporal, ignore este bloque y use arriba <strong>Crear pedido temporal</strong> con cliente, vendedor y agencia.</p>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-xs-12 col-md-6">

                        <input type="hidden" id="escaneoPedidoCodigoActivo" value="<?php echo $tienePedidoActivo ? htmlspecialchars($pedidoParam, ENT_QUOTES, "UTF-8") : ""; ?>">

                        <div id="panelEscaneoCvLector" class="box box-warning" <?php echo $tienePedidoActivo ? "" : 'style="display:none;"'; ?>>

                            <div class="box-header with-border">

                                <h3 class="box-title"><i class="fa fa-barcode"></i> Lector</h3>

                                <div class="box-tools">

                                    <button type="button" class="btn btn-default btn-xs" id="btnEscaneoRefresTotales"><i class="fa fa-calculator"></i> Recalcular</button>

                                </div>

                            </div>

                            <div class="box-body">

                                <label for="inputBarcodePedCv">Campo para el código de barras</label>

                                <input type="text" class="form-control input-lg" id="inputBarcodePedCv" autocomplete="off" placeholder="Enter al finalizar cada lectura" <?php echo $tienePedidoActivo ? "" : "disabled"; ?>>

                                <p class="help-block small" style="margin-top:10px;margin-bottom:0;">Ej.: <strong>0057032</strong> → <strong>10057032</strong> (1 + dígitos completos); <strong>RL271013</strong> tal cual. Si no hay precio en lista igual suma cantidad (<strong>S/ 0</strong>) y muestra alerta.</p>

                            </div>

                        </div>

                        <div id="panelEscaneoCvCierre" class="box box-success" style="<?php echo $tienePedidoActivo ? "" : 'display:none;'; ?>">

                            <div class="box-header with-border">

                                <h3 class="box-title"><i class="fa fa-save"></i> Totales y condición</h3>

                            </div>

                            <div class="box-body">

                                <table class="table table-condensed">

                                    <tr><td><b>Op. gravadas</b></td><td class="text-right"><span id="escaneoMuestraSub"><?php echo number_format($brutoIni, 2); ?></span></td></tr>

                                    <tr><td>Descuento</td><td class="text-right"><span id="escaneoMuestraDesc">0.00</span></td></tr>

                                    <tr><td>Subtotal</td><td class="text-right"><span id="escaneoMuestraSubNet"><?php echo number_format($brutoIni, 2); ?></span></td></tr>

                                    <tr><td>IGV</td><td class="text-right"><span id="escaneoMuestraImp"><?php echo number_format($impIni, 2); ?></span></td></tr>

                                    <tr class="success"><td><b>Total</b></td><td class="text-right"><strong id="escaneoMuestraTot"><?php echo number_format($totalIni, 2); ?></strong></td></tr>

                                </table>

                                <div class="form-group">

                                    <label>Condición de venta</label>

                                    <select class="form-control selectpicker" name="condicionVenta" id="escaneoCondicionVenta" data-live-search="true" data-width="100%" required>

                                        <?php

                                        $cvSel = "";

                                        if ($tienePedidoActivo && isset($pedidoCab["condicion_venta"]) && floatval($pedidoCab["condicion_venta"]) > 0) {

                                            $cvSel = (string) $pedidoCab["condicion_venta"];

                                        }


                                        /** Sin condición guardada — condición de venta id 1 por defecto */


                                        if ($cvSel === "") {

                                            $cvSel = "1";

                                        }


                                        if ($cvSel !== "") {

                                            $cUn = ControladorCondicionVentas::ctrMostrarCondicionVentas("id", $cvSel);

                                            echo '<option value="' . htmlspecialchars($cUn["id"], ENT_QUOTES, "UTF-8") . '">' .

                                                htmlspecialchars($cUn["codigo"] . " — " . $cUn["descripcion"], ENT_QUOTES, "UTF-8") . '</option>';
                                        }


                                        $condTodos = ControladorCondicionVentas::ctrMostrarCondicionVentas(null, null);

                                        foreach ($condTodos as $cRow) {

                                            if ($cvSel !== "" && (string) $cRow["id"] === $cvSel) {

                                                continue;
                                            }

                                            echo '<option value="' . htmlspecialchars((string) $cRow["id"], ENT_QUOTES, "UTF-8") . '">' .

                                                htmlspecialchars((string) $cRow["codigo"] . " — " . (string) $cRow["descripcion"], ENT_QUOTES, "UTF-8") . '</option>';
                                        }

                                        ?>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>Agencia transporte <small>(puede revisarse antes de guardar)</small></label>

                                    <select class="form-control selectpicker" name="agencia" id="escaneoAgenciaPost" data-live-search="true" data-width="100%" data-size="10" required>

                                        <option value="">Seleccionar agencia…</option>

                                        <?php foreach ($agencias as $value) : ?>

                                            <?php

                                            $sel = ($tienePedidoActivo && (string) $value["id"] === (string) $agIni) ? ' selected="selected"' : '';

                                            ?>

                                            <option value="<?php echo htmlspecialchars($value["id"], ENT_QUOTES, "UTF-8"); ?>"<?php echo $sel; ?>>

                                                <?php echo htmlspecialchars($value["id"] . " — " . $value["nombre"], ENT_QUOTES, "UTF-8"); ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <button type="submit" name="guardarPedidoEscaneoCvBtn" id="btnEscaneoGuardarPedido" class="btn btn-primary btn-lg btn-block" <?php echo $tienePedidoActivo ? "" : "disabled"; ?>>

                                    <i class="fa fa-check-circle"></i> Guardar pedido

                                </button>

                                <p class="help-block small" style="margin-top:12px;margin-bottom:0;">Equivale al botón <em>Crear pedido</em> del formulario habitual: lleva la lista pedidos tras guardar.</p>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="clearfix" style="margin-top:14px;"></div>

                <a href="pedidoscv" class="btn btn-default"><i class="fa fa-list"></i> Lista de pedidos</a>

            </div>

        </form>

        <?php

        $totalesPedEscaneo = new ControladorPedidos();
        $totalesPedEscaneo->ctrCrearPedidoTotales();

        ?>

    </section>

</div>
