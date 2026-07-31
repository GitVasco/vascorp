/*
 * cargamos la tabla para articulos en pedidos
 */
$(".tablaArticulosPedidos").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    pageLength: 25,
    lengthMenu: [
        [25, 50, 75, -1],
        [25, 50, 75, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * VISUALIZAR DETALLEs QUE SE JALAN DEL PEDIDO
 */

/*
 * BOTON CREAR PEDIDO
 */
$(".btnCrearPedido").click(function () {
    var pedido = $(this).attr("pedido");
    //console.log("pedido", pedido);

    window.location = "index.php?ruta=crear-pedidocv&pedido=" + pedido;
});

$("#seleccionarCliente").change(function () {
    var cliList = document.getElementById("seleccionarCliente").value;

    var nuevoCodigo = document.getElementById("nuevoCodigo").value;
    var datos = new FormData();
    datos.append("cliList", cliList);

    // Obtener la fecha actual
    var fechaActual = new Date();

    // Verificar si es el 8 de marzo de 2023
    if (
        fechaActual.getDate() === 9 &&
        fechaActual.getMonth() === 2 &&
        fechaActual.getFullYear() === 2023
    ) {
        var precio = "ok";
        //console.log("Hoy es el 8 de marzo de 2023");
    } else {
        var precio = "no";
        //console.log("Hoy no es el 8 de marzo de 2023");
    }

    let pedidos = [];

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDet) {
            if (pedidos.includes(nuevoCodigo)) {
                $("#lista").val(respuestaDet["lista_precios"]);
                console.log("precio normal");
            } else if (
                (respuestaDet["vendedor"] == "08" ||
                    respuestaDet["vendedor"] == "08R") &&
                precio == "ok"
            ) {
                $("#lista").val("precio2");
                console.log("precio especial");
            } else {
                $("#lista").val(respuestaDet["lista_precios"]);
                console.log("precio normal");
            }
        },
    });
});

const vendedoresEspeciales = new Set(["08L", "08O"]);

$("#seleccionarVendedor").change(function () {
    const cliList = $("#seleccionarCliente").val();
    const vendedor = $("#seleccionarVendedor").val();

    const datos = new FormData();
    datos.append("cliList", cliList);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDet) {
            const listaPrecio = vendedoresEspeciales.has(vendedor)
                ? "precio6"
                : respuestaDet["lista_precios"];
            $("#lista").val(listaPrecio);
            console.log("🚀 ~ listaPrecio:", listaPrecio);
        },
    });
});

/*
 * quitar productos con el boton
 */

$(".formularioPedidoCV").on("click", "button.quitarArtPed", function () {
    //console.log("boton");

    $(this).parent().parent().parent().parent().remove();

    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosPed();
});

/*
 * activar cuando cambien el descuento
 */

$("#descPer").change(function () {
    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosPed();
});

function cambioDescuento() {
    var bruto = document.getElementById("nuevoSubTotal").value;
    var descuento = document.getElementById("descPer").value;

    var descN = bruto * (descuento / 100);

    var subTotal = bruto - descN;

    var impNuevo = subTotal * 0.18;

    //var impNuevo = 0;

    var total = subTotal + impNuevo;

    $("#descTotal").val(descN.toFixed(2));
    $("#subTotal").val(subTotal.toFixed(2));
    $("#impTotal").val(impNuevo.toFixed(2));
    $("#nuevoTotal").val(total.toFixed(2));

    //console.log(descN);
}

/*
 * nuevos  totales al cambiar la cantidad
 */

$(".formularioPedidoCV").on("change", "input.nuevaCantidadArtPed", function () {
    var precio = $(this)
        .parent()
        .parent()
        .children(".ingresoPrecio")
        .children()
        .children(".nuevoPrecioArticulo");

    //console.log("precio", precio.val());

    var precioFinal = $(this).val() * precio.attr("precioReal");

    precio.val(precioFinal.toFixed(4));

    /* var nuevoArtPed = Number($(this).attr("artPed")) + Number($(this).val());
    console.log(nuevoArtPed);

    $(this).attr("nuevoArtPed", nuevoArtPed); */

    //console.log(precioFinal);

    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosPed();
});

/*
 * SUMAR TODOS LOS TOTALES
 */

function sumarTotalesPreciosA() {
    var precioItem = $(".nuevoPrecioArticulo");

    var arraySumaPrecio = [];

    for (var i = 0; i < precioItem.length; i++) {
        arraySumaPrecio.push(Number($(precioItem[i]).val()));
    }

    //console.log("arraySumaPrecio", arraySumaPrecio);

    function sumaArrayPrecios(total, numero) {
        return total + numero;
    }

    var sumaTotalPrecio = arraySumaPrecio.reduce(sumaArrayPrecios);

    //console.log("sumaTotalPrecio", sumaTotalPrecio);

    $("#nuevoSubTotalA").val(sumaTotalPrecio.toFixed(2));
    $("#nuevoSubTotal").val(sumaTotalPrecio.toFixed(2));
}

/*
 * ARRAY CON TODOS LOS ARTICULOS
 */

function listarArticulosPed() {
    var listaArticulos = [];

    var descripcion = $(".nuevaDescripcionArticulo");
    var cantidad = $(".nuevaCantidadArtPed");
    var precio = $(".nuevoPrecioArticulo");

    for (var i = 0; i < descripcion.length; i++) {
        listaArticulos.push({
            articulo: $(descripcion[i]).attr("articulo"),
            descripcion: $(descripcion[i]).val(),
            cantidad: $(cantidad[i]).val(),
            precio: $(precio[i]).attr("precioReal"),
            total: $(precio[i]).val(),
        });
    }

    //console.log("listaArticulos", JSON.stringify(listaArticulos));
    //$("#listaProductosPedidos").val(JSON.stringify(listaArticulos));
}

/*
 * AL CAMBIAR LA CONDICION DE VENTA
 */

$("#condicionVenta").change(function () {
    //console.log("si llego")

    sumarTotalesPreciosA();
    //cambioDescuento();
    listarArticulosPed();

    $("#modalito").removeAttr("disabled");
    $("#modalito").removeClass("btn-default");
    $("#modalito").addClass("btn-primary");
});

$("#seleccionarCliente").change(function () {
    //console.log("si llego al cliente")

    //sumarTotalesPreciosA();
    //cambioDescuento();
    //listarArticulosPed();

    var cliente = document.getElementById("seleccionarCliente").value;
    //console.log(cliente);
    $("#codClienteM").val(cliente);

    var datos = new FormData();
    datos.append("codigo", cliente);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDet) {
            //console.log(respuestaDet);

            $("#nomClienteM").val(respuestaDet["nombre"]);
        },
    });

    /* var nomCliente = document.getElementById("nomCliente").value;
    console.log(nomCliente);
    $("#nomClienteM").val(nomCliente); */

    var vendedor = document.getElementById("seleccionarVendedor").value;
    //console.log(vendedor)
    $("#vendedorM").val(vendedor);
});

$(".crearPedido").click(function () {
    sumarTotalesPreciosA();
    //cambioDescuento();
    listarArticulosPed();

    var codigo = document.getElementById("nuevoCodigo").value;
    $("#codigoM").val(codigo);

    var cliente = document.getElementById("seleccionarCliente").value;
    //console.log(cliente);
    $("#codClienteM").val(cliente);

    var datos = new FormData();
    datos.append("codigo", cliente);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDet) {
            //console.log(respuestaDet);

            $("#nomClienteM").val(respuestaDet["nombre"]);
        },
    });

    var vendedor = document.getElementById("seleccionarVendedor").value;
    $("#vendedorM").val(vendedor);

    var lista = document.getElementById("seleccionarLista").value;

    var opGravada = document.getElementById("nuevoSubTotalA").value;
    $("#opGravadaM").val(opGravada);

    var descuento = document.getElementById("descTotal").value;
    $("#descuentoM").val(descuento);

    var subTotal = document.getElementById("subTotal").value;
    $("#subTotalM").val(subTotal);

    var impuesto = document.getElementById("impTotal").value;
    //console.log(impuesto);
    $("#igvM").val(impuesto);

    var nuevoTotal = Number(opGravada) + Number(impuesto);

    var total = document.getElementById("nuevoTotal").value;

    if (nuevoTotal == total) {
        $("#totalM").val(nuevoTotal);
    } else {
        $("#totalM").val(total);
    }

    var articulos = document.getElementById("listaProductosPedidos").value;
    $("#articulosM").val(articulos);

    var condicionVenta = document.getElementById("condicionVenta").value;
    //console.log(condicionVenta);
    $("#condicionVentaM").val(condicionVenta);

    var agencia = document.getElementById("agencia").value;
    $("#agenciaM").val(agencia);

    var usuario = document.getElementById("idUsuario").value;
    $("#usuarioM").val(usuario);

    //console.log(usuario);
});

/*
 * cargamos la tabla de pedidos
 */
$(".tablaPedidosCV").DataTable({
    ajax: "ajax/facturacion/tabla-pedidosCV.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * BOTON REVISAR PEDIDO
 */
$(".box").on("click", ".btnEditarPedidoCV", function () {
    var pedido = $(this).attr("codigo");
    //console.log("pedido", pedido);

    window.location = "index.php?ruta=crear-pedidocv&pedido=" + pedido;
});

/*
 * Escaneo código de barras (misma tabla que imprimir/Facturar; evita depender sólo de .box con DT)
 */
$(
    ".tablaPedidosCV, .tablaPedidosGenerados, .tablaPedidosAprobados, .tablaPedidosAPT, .tablaPedidosConfirmados, .tablaPedidosFacturados"
).on("click", ".btnEscaneoBarcodePedCv", function (e) {
    e.preventDefault();
    var pedido = $(this).attr("codigo");
    if (!pedido) {
        return;
    }
    window.location =
        "index.php?ruta=escaneo-barcode-pedidocv&pedido=" +
        encodeURIComponent(pedido);
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(
    ".tablaPedidosCV, .tablaPedidosGenerados, .tablaPedidosAprobados, .tablaPedidosAPT, .tablaPedidosConfirmados, .tablaPedidosFacturados"
).on("click", ".btnImprimirPedido", function () {
    var codigo = $(this).attr("codigo");
    //console.log(codigo);

    var datos = new FormData();
    datos.append("codPedido", codigo);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {},
    });

    window.open(
        "vistas/reportes_ticket/impresion_pedido.php?codigo=" + codigo,
        "_blank"
    );
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaPedidosConfirmados, .tablaPedidosGenerados, .tablaPedidosCV").on(
    "click",
    ".btnCotizarPedido",
    function () {
        var codigo = $(this).attr("codigo");
        //console.log(codigo);

        window.open(
            "vistas/reportes_ticket/pedido_cotizar.php?codigo=" + codigo,
            "_blank"
        );
    }
);

/*
 * UX modal facturar
 */
function mfFormatoOpcionSerie(numero) {
    if (!numero) {
        return "";
    }
    var parts = String(numero).split("-");
    if (parts.length < 2) {
        return numero;
    }
    return parts[0] + " → " + parts[1];
}

function mfActualizarPreviewSerie($select, previewId) {
    var $preview = $("#" + previewId);
    if (!$preview.length) {
        return;
    }
    var val = $select.val();
    if (!val) {
        $preview.text(
            previewId === "seriePreview"
                ? "El número final se asigna al generar."
                : ""
        );
        return;
    }
    var parts = String(val).split("-");
    var texto =
        parts.length >= 2 ? parts[0] + " → " + parts[1] : val;
    $preview.html(
        'Estimado: <strong>' +
            texto +
            '</strong> <span class="mf-preview-note">(se confirma al generar)</span>'
    );
}

function mfMarcarTipDoc(estado) {
    var $tip = $("#tipDoc");
    if (!$tip.length) {
        return;
    }
    $tip.removeClass("mf-tipdoc-ok mf-tipdoc-warn");
    $tip.css({ background: "", color: "", fontWeight: "" });
    if (estado === "ok") {
        $tip.addClass("mf-tipdoc-ok");
    } else if (estado === "warn") {
        $tip.addClass("mf-tipdoc-warn");
    }
}

function mfEvaluarTipDocCliente(tipoDocGen) {
    var tipoCli = ($("#tipDoc").val() || "").toUpperCase();
    if (!tipoDocGen || !tipoCli) {
        mfMarcarTipDoc(null);
        return;
    }
    if (tipoDocGen === "01") {
        mfMarcarTipDoc(tipoCli === "RUC" ? "ok" : "warn");
    } else if (tipoDocGen === "03") {
        mfMarcarTipDoc(tipoCli === "DNI" ? "ok" : "warn");
    } else {
        mfMarcarTipDoc(null);
    }
}

function mfRefreshSelectpicker($select) {
    if (!$select || !$select.length || typeof $select.selectpicker !== "function") {
        return;
    }
    if (!$select.data("selectpicker")) {
        $select.selectpicker({
            liveSearch: true,
            container: "body",
            size: 8,
        });
    } else {
        $select.selectpicker("refresh");
    }
}

function mfLlenarSelectSeries($select, respuesta) {
    $select.find("option").remove();
    $select.append('<option value="">Seleccionar serie</option>');
    if (respuesta && respuesta.length) {
        for (var i = 0; i < respuesta.length; i++) {
            var id = respuesta[i];
            $select.append(
                '<option value="' +
                    id.numero +
                    '">' +
                    mfFormatoOpcionSerie(id.numero) +
                    "</option>"
            );
        }
    }
    $select.val("");
    mfRefreshSelectpicker($select);
}

function mfMostrarSerieSeparado(mostrar) {
    var $wrap = $("#wrapSerieSeparado");
    var $sel = $("#serieSeparado");
    if (!$sel.length) {
        return;
    }
    if (mostrar) {
        $wrap.removeClass("hidden");
        $sel.prop("disabled", false).prop("required", true);
    } else {
        $wrap.addClass("hidden");
        $sel.prop("disabled", true).prop("required", false).val("");
        $sel.find("option").remove();
        $sel.append('<option value="">Seleccionar serie</option>');
        mfActualizarPreviewSerie($sel, "serieSeparadoPreview");
    }
    mfRefreshSelectpicker($sel);
}

function mfResetSepararUi() {
    var chkF = document.getElementById("chkFactura");
    var chkB = document.getElementById("chkBoleta");
    if (chkF) {
        chkF.checked = false;
    }
    if (chkB) {
        chkB.checked = false;
    }
    mfMostrarSerieSeparado(false);
}

/*
 * Nota de crédito desde pedido: validar F/B y cargar fecha de emisión
 */
function mfTipoDocOrigenEsperado() {
    var tipoDoc = ($("#tdocorigen").val() || "").toString();
    if (tipoDoc === "01") {
        return { prefijo: "F", tipoVenta: "S03", etiqueta: "factura" };
    }
    if (tipoDoc === "03") {
        return { prefijo: "B", tipoVenta: "S02", etiqueta: "boleta" };
    }
    return null;
}

function mfNormalizarDocOrigen(doc) {
    return (doc || "").toString().toUpperCase().replace(/[\s\-]+/g, "");
}

function mfSetHelpOrigen(msg, ok) {
    var $help = $("#serieOrigenHelp");
    if (!$help.length) {
        return;
    }
    $help.text(msg || "");
    $help.toggleClass("is-ok", !!ok);
}

function mfValidarPrefijoOrigen(doc, esperado, silent) {
    if (!esperado) {
        if (!silent) {
            mfSetHelpOrigen("Seleccione tipo doc. origen (Factura o Boleta).", false);
        }
        return false;
    }
    if (!doc) {
        if (!silent) {
            mfSetHelpOrigen("Ingrese el documento origen.", false);
        }
        return false;
    }
    if (doc.charAt(0) !== esperado.prefijo) {
        if (!silent) {
            mfSetHelpOrigen(
                "Para " +
                    esperado.etiqueta +
                    " el número debe empezar con " +
                    esperado.prefijo +
                    ".",
                false
            );
        }
        return false;
    }
    return true;
}

function mfCargarFechaOrigenNc() {
    if (!$("#serieOrigen").length) {
        return;
    }

    var esperado = mfTipoDocOrigenEsperado();
    var doc = mfNormalizarDocOrigen($("#serieOrigen").val());
    $("#serieOrigen").val(doc);

    if (!mfValidarPrefijoOrigen(doc, esperado, false)) {
        $("#fechaOrigen").val("");
        return;
    }

    var datos = new FormData();
    datos.append("buscarDocRelGuia", doc);

    $.ajax({
        url: "ajax/facturacion.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (!respuesta || !respuesta.ok) {
                $("#fechaOrigen").val("");
                mfSetHelpOrigen(
                    "No se encontró la " + esperado.etiqueta + " " + doc + ".",
                    false
                );
                return;
            }

            if (respuesta.tipo !== esperado.tipoVenta) {
                $("#fechaOrigen").val("");
                mfSetHelpOrigen(
                    "El documento no corresponde a una " +
                        esperado.etiqueta +
                        ".",
                    false
                );
                return;
            }

            var fecha = (respuesta.fecha || "").toString().substring(0, 10);
            $("#fechaOrigen").val(fecha);
            mfSetHelpOrigen("Documento válido. Fecha de emisión cargada.", true);
        },
        error: function () {
            $("#fechaOrigen").val("");
            mfSetHelpOrigen("No se pudo validar el documento.", false);
        },
    });
}

function mfValidarNotaCreditoPedido() {
    if (($("#tdoc").val() || "") !== "07") {
        return true;
    }

    var esperado = mfTipoDocOrigenEsperado();
    var doc = mfNormalizarDocOrigen($("#serieOrigen").val());
    $("#serieOrigen").val(doc);

    if (!mfValidarPrefijoOrigen(doc, esperado, false)) {
        if (typeof toastr !== "undefined") {
            toastr["error"](
                $("#serieOrigenHelp").text() ||
                    "Revise tipo y documento origen de la nota de crédito"
            );
        }
        return false;
    }

    if (!$("#fechaOrigen").val()) {
        if (typeof toastr !== "undefined") {
            toastr["error"](
                "No hay fecha de emisión del documento origen. Verifique el N° Fact/Bol."
            );
        }
        mfSetHelpOrigen(
            "No hay fecha de emisión. Verifique el documento origen.",
            false
        );
        return false;
    }

    if (!$("#notaMotivo").val()) {
        if (typeof toastr !== "undefined") {
            toastr["error"]("Seleccione el motivo de la nota de crédito.");
        }
        return false;
    }

    return true;
}

window.mfValidarNotaCreditoPedido = mfValidarNotaCreditoPedido;

$(document).on("changed.bs.select", "#tdocorigen", function () {
    if (!$("#wrapNotaCredito").length || $("#wrapNotaCredito").hasClass("hidden")) {
        return;
    }
    $("#fechaOrigen").val("");
    mfSetHelpOrigen("", false);
    var esperado = mfTipoDocOrigenEsperado();
    if (esperado) {
        $("#serieOrigen").attr(
            "placeholder",
            esperado.prefijo === "F"
                ? "Ej. F00100012345"
                : "Ej. B00100012345"
        );
    }
    if ($("#serieOrigen").val()) {
        mfCargarFechaOrigenNc();
    }
});

$(document).on("blur", "#serieOrigen", function () {
    if (!$("#wrapNotaCredito").length || $("#wrapNotaCredito").hasClass("hidden")) {
        return;
    }
    mfCargarFechaOrigenNc();
});

$(document).on("input", "#serieOrigen", function () {
    $("#fechaOrigen").val("");
    mfSetHelpOrigen("", false);
});

/*
 * AL CAMBIAR EL SELECT DE DOCUMENTO
 */
$("#tdoc").change(function () {
    var documento = document.getElementById("tdoc").value;

    if (documento == "00") {
        $("#GuiasDiv").removeClass("hidden disable-div");
        $("#wrapSepararDoc").removeClass("hidden");
    } else {
        $("#GuiasDiv").addClass("hidden");
        $("#wrapSepararDoc").addClass("hidden");
    }

    mfEvaluarTipDocCliente(documento);

    mfResetSepararUi();

    if (documento == "00") {
        document.getElementById("chkFactura").disabled = false;
        document.getElementById("chkBoleta").disabled = false;
    } else {
        document.getElementById("chkFactura").disabled = true;
        document.getElementById("chkBoleta").disabled = true;
    }

    if (documento == "07") {
        $("#wrapNotaCredito").removeClass("hidden");
        $(".campoTipOrigen").removeClass("hidden");
        $(".campoDocOrigen").removeClass("hidden");
        $(".campoFecOrigen").removeClass("hidden");
        $(".campoMotOrigen").removeClass("hidden");
        $("#tdocorigen, #notaMotivo").prop("required", true);
        $("#serieOrigen, #fechaOrigen").prop("required", true);
        mfRefreshSelectpicker($("#tdocorigen"));
        mfRefreshSelectpicker($("#notaMotivo"));
        mfSetHelpOrigen("", false);
    } else {
        $("#wrapNotaCredito").addClass("hidden");
        $(".campoTipOrigen").addClass("hidden");
        $(".campoDocOrigen").addClass("hidden");
        $(".campoFecOrigen").addClass("hidden");
        $(".campoMotOrigen").addClass("hidden");
        $("#tdocorigen, #notaMotivo").prop("required", false).val("");
        $("#serieOrigen, #fechaOrigen").prop("required", false).val("");
        mfSetHelpOrigen("", false);
        mfRefreshSelectpicker($("#tdocorigen"));
        mfRefreshSelectpicker($("#notaMotivo"));
    }

    var serie = $("#serie");
    var $btn = $("#btnGenerarDoc");
    $btn.prop("disabled", true);

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({
        url: "ajax/talonarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            mfLlenarSelectSeries(serie, respuesta);
            mfActualizarPreviewSerie(serie, "seriePreview");
            $btn.prop("disabled", false);
        },
        error: function () {
            mfLlenarSelectSeries(serie, []);
            mfActualizarPreviewSerie(serie, "seriePreview");
            $btn.prop("disabled", false);
        },
    });

    //*INICIO DE FORMA DE PAGO

    if (documento == "01" || documento == "03") {
        //console.log("aqui", documento);
        //document.getElementById("formaPago").disabled = false;

        var formaPago = $("#formaPago");

        var datos = new FormData();
        datos.append("documento", documento);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                //console.log(respuesta);

                formaPago.find("option").remove();

                formaPago.append(
                    '<option value="">Seleccionar Forma Pago</option>'
                );

                for (var id of respuesta) {
                    formaPago.append(
                        '<option value="' +
                            id.codigo +
                            '">' +
                            id.codigo +
                            " - " +
                            id.cuenta +
                            "</option>"
                    );
                    //console.log(formaPago);
                }
            },
        });
    } else if (documento == "07") {
        //console.log("aqui", documento);
        //document.getElementById("formaPago").disabled = false;

        var formaPago = $("#formaPago");

        var datos = new FormData();
        datos.append("documento", documento);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                //console.log(respuesta);

                formaPago.find("option").remove();

                formaPago.append(
                    '<option value="">Seleccionar Forma Pago</option>'
                );

                for (var id of respuesta) {
                    formaPago.append(
                        '<option value="' +
                            id.codigo +
                            '">' +
                            id.codigo +
                            " - " +
                            id.cuenta +
                            "</option>"
                    );
                    //console.log(formaPago);
                }
            },
        });
    } else {
        //document.getElementById("formaPago").disabled = true;

        var formaPago = $("#formaPago");
        formaPago.find("option").remove();
        formaPago.append('<option value="">Seleccionar Forma Pago</option>');
    }

    //*FIN DE FORMA DE PAGO
});

/*
 * validar el checkbox
 */
$(".chkFactura").change(function () {
    var chkBox = document.getElementById("chkFactura");

    var documento = "01";
    var serieSeparado = $("#serieSeparado");

    mfEvaluarTipDocCliente(documento);

    if (chkBox.checked == true) {
        document.getElementById("chkBoleta").disabled = true;
        document.getElementById("chkBoleta").checked = false;
        mfMostrarSerieSeparado(true);
    } else {
        document.getElementById("chkBoleta").disabled = false;
        mfMostrarSerieSeparado(false);
        mfEvaluarTipDocCliente($("#tdoc").val());
        return;
    }

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({
        url: "ajax/talonarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            mfLlenarSelectSeries(serieSeparado, respuesta);
            mfActualizarPreviewSerie(serieSeparado, "serieSeparadoPreview");
        },
    });
});

$(".chkBoleta").change(function () {
    var chkBox = document.getElementById("chkBoleta");
    var serieSeparado = $("#serieSeparado");
    var documento = "03";

    mfEvaluarTipDocCliente(documento);

    if (chkBox.checked == true) {
        document.getElementById("chkFactura").disabled = true;
        document.getElementById("chkFactura").checked = false;
        mfMostrarSerieSeparado(true);
    } else {
        document.getElementById("chkFactura").disabled = false;
        mfMostrarSerieSeparado(false);
        mfEvaluarTipDocCliente($("#tdoc").val());
        return;
    }

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({
        url: "ajax/talonarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            mfLlenarSelectSeries(serieSeparado, respuesta);
            mfActualizarPreviewSerie(serieSeparado, "serieSeparadoPreview");
        },
    });
});

$(document).on("change", "#serie", function () {
    mfActualizarPreviewSerie($(this), "seriePreview");
});

$(document).on("change", "#serieSeparado", function () {
    mfActualizarPreviewSerie($(this), "serieSeparadoPreview");
});

$("#modalFacturar").on("show.bs.modal", function () {
    var $btn = $("#btnGenerarDoc");
    $btn.prop("disabled", false).html("Generar documento");

    $("#GuiasDiv").addClass("hidden");
    $("#wrapSepararDoc").addClass("hidden");
    $("#wrapNotaCredito").addClass("hidden");
    mfResetSepararUi();
    $("#orden_compra").val("");
    $("#tdocorigen, #notaMotivo").prop("required", false).val("");
    $("#serieOrigen, #fechaOrigen").prop("required", false).val("");
    mfSetHelpOrigen("", false);
    mfRefreshSelectpicker($("#tdocorigen"));
    mfRefreshSelectpicker($("#notaMotivo"));

    if (document.getElementById("tdoc")) {
        $("#tdoc").val("").selectpicker("refresh");
    }

    var serie = $("#serie");
    serie.find("option").remove();
    serie.append('<option value="">Seleccionar serie</option>');
    serie.val("");
    mfRefreshSelectpicker(serie);
    mfActualizarPreviewSerie(serie, "seriePreview");

    if (document.getElementById("tipDoc")) {
        mfMarcarTipDoc(null);
    }
});

/*
 * ACTIVAR MODAL
 */

$(
    ".tablaPedidosCV, .tablaPedidosGenerados, .tablaPedidosAprobados, .tablaPedidosAPT, .tablaPedidosConfirmados, .tablaPedidosFacturados"
).on("click", "button.btnFacturar", function () {
    var codigo = $(this).attr("codigo");
    var cod_cli = $(this).attr("cod_cli");
    var nom_cli = $(this).attr("nom_cli");
    var tip_doc = $(this).attr("tip_doc");
    var nro_doc = $(this).attr("nro_doc");
    var dscto = $(this).attr("dscto");
    var cod_ven = $(this).attr("cod_ven");
    //console.log(nro_doc);

    $("#codPedido").val(codigo);
    $("#codCli").val(cod_cli);
    $("#nomCli").val(nom_cli);
    $("#tipDoc").val(tip_doc);
    $("#nroDoc").val(nro_doc);
    $("#dscto").val(dscto);
    $("#codVen").val(cod_ven);

    var datos = new FormData();
    datos.append("codPedido", codigo);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {},
    });
});

/*
 * BOTON REVISAR FACTURA
 */
$(".box").on("click", ".btnEditarFacturaCV", function () {
    var pedido = $(this).attr("codigo");
    //console.log("factura", pedido);

    window.location = "index.php?ruta=crear-facturascv&pedido=" + pedido;
});

/*
 * BOTON IR A PEDIDOS GENERADOS
 */
$(".btnGenerados").click(function () {
    window.location = "pedidos-generados";
});

/*
 * BOTON IR A PEDIDOS APROBADOS
 */
$(".btnAprobados").click(function () {
    window.location = "pedidos-aprobados";
});

/*
 * BOTON IR A PEDIDOS EN APT
 */
$(".btnAPT").click(function () {
    window.location = "pedidos-apt";
});

/*
 * BOTON IR A PEDIDOS CONFIRMADOS
 */
$(".btnConfirmados").click(function () {
    window.location = "pedidos-confirmados";
});

/*
 * BOTON IR A PEDIDOS FACTURADOS
 */
$(".btnFacturados").click(function () {
    window.location = "pedidos-facturados";
});

/*
 * BOTON IR A PEDIDOS INICIO
 */
$(".btnInicioPed").click(function () {
    window.location = "pedidoscv";
});

/*
 * CARGADOS TABLA GENERADOS
 */
$(".tablaPedidosGenerados").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos-generados.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * CARGADOS TABLA APROBADOS
 */
$(".tablaPedidosAprobados").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos-aprobados.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * CARGADOS TABLA APT
 */
$(".tablaPedidosAPT").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos-apt.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * CARGADOS TABLA CONFIRMADOS
 */
$(".tablaPedidosConfirmados").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos-confirmados.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

/*
 * CARGADOS TABLA FACTURADOS
 */
$(".tablaPedidosFacturados").DataTable({
    ajax: "ajax/facturacion/tabla-pedidos-facturados.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[9, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

$(".tablaPedidosGenerados, .tablaPedidosCV").on(
    "click",
    ".btnAprobarPedido",
    function () {
        var codigo = $(this).attr("codigo");
        var estadoPedido = $(this).attr("estadoPedido");
        //Realizamos la activación-desactivación por una petición AJAX
        var datos = new FormData();
        datos.append("activarId", codigo);
        datos.append("activarEstado", estadoPedido);

        $.ajax({
            url: "ajax/facturacion.ajax.php",
            type: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (respuesta) {
                // console.log(respuesta);
                swal({
                    type: "success",
                    title: "¡Ok!",
                    text: "¡El pedido fue aprobado con éxito!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                    closeOnConfirm: false,
                }).then((result) => {
                    if (result.value) {
                        window.location = "pedidos-generados";
                    }
                });
            },
        });
    }
);

$(".tablaPedidosAprobados").on("click", ".btnAptear", function () {
    var codigo = $(this).attr("codigo");
    var estadoPedido = $(this).attr("estadoPedido");
    //Realizamos la activación-desactivación por una petición AJAX
    var datos = new FormData();
    datos.append("activarId", codigo);
    datos.append("activarEstado", estadoPedido);

    $.ajax({
        url: "ajax/facturacion.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            if (
                typeof respuesta === "string" &&
                respuesta.indexOf("BLOQUEO_CONTROL|") === 0
            ) {
                swal(
                    "Control pendiente",
                    respuesta.replace("BLOQUEO_CONTROL|", ""),
                    "warning"
                );
                return;
            }
            if (respuesta !== "ok") {
                swal(
                    "Atención",
                    "No se pudo cambiar el estado del pedido.",
                    "warning"
                );
                return;
            }
            // console.log(respuesta);
            swal({
                type: "success",
                title: "¡Ok!",
                text: "¡El pedido fue dado de apta con éxito!",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false,
            }).then((result) => {
                if (result.value) {
                    window.location = "pedidos-aprobados";
                }
            });
        },
    });
});

$(".tablaPedidosAPT").on("click", ".btnConfirmar", function () {
    var codigo = $(this).attr("codigo");
    var estadoPedido = $(this).attr("estadoPedido");
    //Realizamos la activación-desactivación por una petición AJAX
    var datos = new FormData();
    datos.append("activarId", codigo);
    datos.append("activarEstado", estadoPedido);

    $.ajax({
        url: "ajax/facturacion.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            // console.log(respuesta);
            swal({
                type: "success",
                title: "¡Ok!",
                text: "¡El pedido fue confirmado con éxito!",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false,
            }).then((result) => {
                if (result.value) {
                    window.location = "pedidos-apt";
                }
            });
        },
    });
});

$(document).ready(function () {
    var pedidoCvClientesAjaxPending = false;
    var pedidoCvClientesCatalogoListo = false;

    const codClienteElement = document.getElementById("codCliente");
    const codAgenciaElement = document.getElementById("agencia");

    $("select#seleccionarCliente[data-carga-clientes-al-abrir]").on(
        "show.bs.select",
        function () {
            var $sel = $(this);

            if ($sel.prop("disabled")) {
                return;
            }

            var nOptsIni = $sel.find("option").length;

            if (
                pedidoCvClientesAjaxPending ||
                pedidoCvClientesCatalogoListo ||
                nOptsIni > 1
            ) {
                return;
            }

            cargarClientes("1");
        }
    );

    if (codClienteElement) {
        const clientSelected = codClienteElement.value;

        if (clientSelected !== "") {
            // Cargar clientes automáticamente si codCliente tiene un valor (editar pedido)
            cargarClientes(clientSelected);
        }
    }

    function cargarClientes(clienteCuenta) {
        var $sel = $("select#seleccionarCliente");
        if (!$sel.length) {
            return;
        }

        var datos = new FormData();
        datos.append("clienteCuenta", clienteCuenta);

        $.ajax({
            url: "ajax/clientes.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function () {
                if ($sel.is("[data-carga-clientes-al-abrir]")) {
                    pedidoCvClientesAjaxPending = true;
                }
            },
            success: function (respuesta2) {
                $sel.find("option").remove();
                $sel.append(
                    "<option value='' >Seleccionar cliente</option>"
                );
                for (let i = 0; i < respuesta2.length; i++) {
                    $sel.append(
                        "<option value='" +
                            respuesta2[i]["codigo"] +
                            "'>" +
                            respuesta2[i]["codigo"] +
                            " - " +
                            respuesta2[i]["nombre"] +
                            "</option>"
                    );
                }

                // Seleccionar el cliente que estaba previamente seleccionado si se está editando
                if (clienteCuenta !== "1") {
                    $sel.val(clienteCuenta);
                }
                $sel.selectpicker("refresh");

                if ($sel.is("[data-carga-clientes-al-abrir]")) {
                    pedidoCvClientesCatalogoListo = true;
                }
            },
            complete: function () {
                if ($sel.is("[data-carga-clientes-al-abrir]")) {
                    pedidoCvClientesAjaxPending = false;
                }
            },
            error: function () {
                Command: toastr["error"]("No se pudieron cargar los clientes.");
            },
        });
    }

    /*******************************
     * Funcion para cargar agencia
     *******************************/
    function cargarAgencia(codigo) {
        var datos = new FormData();
        datos.append("codigo", codigo);

        $.ajax({
            url: "ajax/clientes.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta["agencia"] != "")
                    $("#agencia").val(respuesta["agencia"]);
                $("#agencia").selectpicker("refresh");
            },
        });
    }
});

$(".tablaArticulosPedidos").on("click", ".modificarArtPed", function () {
    //console.log("hola mundo");

    var cliente = document.getElementById("seleccionarCliente").value;
    var vendedor = document.getElementById("seleccionarVendedor").value;
    var pedido = document.getElementById("nuevoCodigo").value;
    var modLista = document.getElementById("lista").value;

    console.log(pedido);

    if (modLista == "") {
        var modLista1 = document.getElementById("seleccionarLista").value;
        $("#nLista").val(modLista1);
        var datos = new FormData();
        datos.append("modLista", modLista1);
        //console.log('lista',modLista1);
    } else {
        $("#nLista").val(modLista);
        var datos = new FormData();
        datos.append("modLista", modLista);
        //console.log('lista',modLista);
    }

    //ver para q sirve
    $("#clienteA").val(cliente);
    $("#vendedorA").val(vendedor);

    $("#modeloModalA").val($(this).attr("modelo"));

    /*
     *datos para la cabecera
     */
    var mod = $(this).attr("modelo");
    //console.log(mod);

    //var datos = new FormData();
    datos.append("mod", mod);
    //datos.append("modLista", modLista);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaLista) {
            $("#precioA").val(respuestaLista["precio"]);
        },
    });

    /*
     * datos para la tabla
     */

    var modelo = $(this).attr("modelo");
    //console.log(modelo);

    var datosPedido = new FormData();
    datosPedido.append("modeloA", modelo);
    datosPedido.append("pedido", pedido);
    //console.log(datosPedido);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datosPedido,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaA) {
            // console.log("respuestaA", respuestaA);

            $(".detalleCT").remove();

            for (var id of respuestaA) {
                /* TALLA 1 */
                if (id.t1 == 1) {
                    var talla1 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" value="' +
                        id.v1 +
                        '" min="0"></td>';
                } else {
                    var talla1 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" readonly></td>';
                }

                /* TALLA 2 */
                if (id.t2 == 1) {
                    var talla2 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" value="' +
                        id.v2 +
                        '" min="0"></td>';
                } else {
                    var talla2 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" readonly></td>';
                }

                /* TALLA 3 */
                if (id.t3 == 1) {
                    var talla3 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" value="' +
                        id.v3 +
                        '" min="0"></td>';
                } else {
                    var talla3 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" readonly></td>';
                }

                /* TALLA 4 */
                if (id.t4 == 1) {
                    var talla4 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" value="' +
                        id.v4 +
                        '" min="0"></td>';
                } else {
                    var talla4 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" readonly></td>';
                }

                /* TALLA 5 */
                if (id.t5 == 1) {
                    var talla5 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" value="' +
                        id.v5 +
                        '" min="0"></td>';
                } else {
                    var talla5 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" readonly></td>';
                }

                /* TALLA 6 */
                if (id.t6 == 1) {
                    var talla6 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" value="' +
                        id.v6 +
                        '" min="0"></td>';
                } else {
                    var talla6 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" readonly></td>';
                }

                /* TALLA 7*/
                if (id.t7 == 1) {
                    var talla7 =
                        '<td><input style="width:100%" class="pruebaA" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" value="' +
                        id.v7 +
                        '" min="0"></td>';
                } else {
                    var talla7 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" readonly></td>';
                }

                /* TALLA 8 */
                if (id.t8 == 1) {
                    var talla8 =
                        '<td><input style="width:100%" class="cantidad" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        8 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        8 +
                        '"value="' +
                        id.v8 +
                        '" min="0"></td>';
                } else {
                    var talla8 =
                        '<td><input style="width:100%" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        8 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        8 +
                        '" readonly></td>';
                }

                var fila =
                    '<tr class="detalleCT">' +
                    "<td>" +
                    id.modelo +
                    " </td>" +
                    "<td>" +
                    id.color +
                    " </td>" +
                    talla1 +
                    talla2 +
                    talla3 +
                    talla4 +
                    talla5 +
                    talla6 +
                    talla7 +
                    talla8 +
                    "</tr>";

                $(".tablaColTal").append(fila);
            }
        },
    });
});

//*OPCION B GENERAR PEDIDO
$(".modificarArtPedB").click(function () {
    var modelo = document.getElementById("modelo").value;
    if (modelo != "") {
        var cliente = document.getElementById("seleccionarCliente").value;
        var vendedor = document.getElementById("seleccionarVendedor").value;
        var pedido = document.getElementById("nuevoCodigo").value;
        var modLista = document.getElementById("lista").value;
        var agencia = document.getElementById("agencia").value;

        if (modLista == "") {
            var modLista1 = document.getElementById("seleccionarLista").value;
            $("#nLista").val(modLista1);
            var datos = new FormData();
            datos.append("modLista", modLista1);
            //console.log('lista',modLista1);
        } else {
            $("#nLista").val(modLista);
            var datos = new FormData();
            datos.append("modLista", modLista);
            //console.log('lista',modLista);
        }

        //ver para q sirve
        $("#clienteA").val(cliente);
        $("#vendedorA").val(vendedor);
        $("#agenciaA").val(agencia);

        //*datos para la cabecera
        //var mod = document.getElementById("modelo").value;
        //var mod = $(this).attr("modelo");
        //console.log(mod);

        $("#modeloModalA").val(modelo);

        //var datos = new FormData();
        datos.append("mod", mod);
        //datos.append("modLista", modLista);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuestaLista) {
                if (respuestaLista["precio"] <= 0) {
                    Command: toastr["error"]("El modelo no tiene precio");
                }

                $("#modeloModalA").val(respuestaLista["modelo"]);

                $("#precioA").val(respuestaLista["precio"]);
            },
        });

        /*
         * datos para la tabla
         */
        //var modelo = respuestaLista["modelo"];
        var modelo = document.getElementById("modelo").value;
        console.log(modelo);

        var datosPedido = new FormData();
        datosPedido.append("modeloA", modelo);
        datosPedido.append("pedido", pedido);
        // console.log(datosPedido);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datosPedido,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuestaA) {
                //console.log("respuestaA", respuestaA);

                $(".detalleCT").remove();

                for (var id of respuestaA) {
                    /* TALLA 1 */
                    if (id.t1 == 1) {
                        var talla1 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            1 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            1 +
                            '" value="' +
                            id.v1 +
                            '" min="0"></td>';
                    } else {
                        var talla1 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            1 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            1 +
                            '" readonly></td>';
                    }

                    /* TALLA 2 */
                    if (id.t2 == 1) {
                        var talla2 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            2 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            2 +
                            '" value="' +
                            id.v2 +
                            '" min="0"></td>';
                    } else {
                        var talla2 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            2 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            2 +
                            '" readonly></td>';
                    }

                    /* TALLA 3 */
                    if (id.t3 == 1) {
                        var talla3 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            3 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            3 +
                            '" value="' +
                            id.v3 +
                            '" min="0"></td>';
                    } else {
                        var talla3 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            3 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            3 +
                            '" readonly></td>';
                    }

                    /* TALLA 4 */
                    if (id.t4 == 1) {
                        var talla4 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            4 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            4 +
                            '" value="' +
                            id.v4 +
                            '" min="0" ></td>';
                    } else {
                        var talla4 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            4 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            4 +
                            '" readonly></td>';
                    }

                    /* TALLA 5 */
                    if (id.t5 == 1) {
                        var talla5 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            5 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            5 +
                            '" value="' +
                            id.v5 +
                            '" min="0" ></td>';
                    } else {
                        var talla5 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            5 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            5 +
                            '" readonly></td>';
                    }

                    /* TALLA 6 */
                    if (id.t6 == 1) {
                        var talla6 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            6 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            6 +
                            '" value="' +
                            id.v6 +
                            '" min="0" ></td>';
                    } else {
                        var talla6 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            6 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            6 +
                            '" readonly></td>';
                    }

                    /* TALLA 7*/
                    if (id.t7 == 1) {
                        var talla7 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            7 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            7 +
                            '" value="' +
                            id.v7 +
                            '" min="0" ></td>';
                    } else {
                        var talla7 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            7 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            7 +
                            '" readonly></td>';
                    }

                    /* TALLA 8 */
                    if (id.t8 == 1) {
                        var talla8 =
                            '<td><input style="width:100%" class="pruebaA" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            8 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            8 +
                            '"value="' +
                            id.v8 +
                            '" min="0" ></td>';
                    } else {
                        var talla8 =
                            '<td><input style="width:100%" type="text" name="' +
                            id.modelo +
                            id.cod_color +
                            8 +
                            '" id="' +
                            id.modelo +
                            id.cod_color +
                            8 +
                            '" readonly></td>';
                    }

                    var fila =
                        '<tr class="detalleCT">' +
                        "<td>" +
                        id.modelo +
                        " </td>" +
                        "<td>" +
                        id.color +
                        " </td>" +
                        talla1 +
                        talla2 +
                        talla3 +
                        talla4 +
                        talla5 +
                        talla6 +
                        talla7 +
                        talla8 +
                        "</tr>";

                    $(".tablaColTal").append(fila);
                }

                var inputs = $("form :text"),
                    length = inputs.length,
                    i = 25;
                //console.log(inputs);
                //console.log(length);

                inputs.on("keypress", function (event) {
                    var code = event.keyCode || event.which;
                    if (code == 13) {
                        event.preventDefault();
                        i = i == length - 12 ? 26 : ++i;
                        console.log(i);
                        inputs[i].focus();
                        inputs[i].select();
                    }
                });
            },
        });
    }
});

$(".btnCalCantA").click(function () {
    var totalCantidadA = 0;
    $(".pruebaA").each(function () {
        totalCantidadA += parseInt($(this).val()) || 0;
    });

    var precio = document.getElementById("precioA").value;

    var totalSolesA = totalCantidadA * precio;

    $("#totalCantidadA").val(totalCantidadA);

    $("#totalSolesA").val(totalSolesA);
    $("#totalSolesA").number(true, 2);

    console.log(totalSolesA);
    console.log(totalCantidadA);
});

/*
 * Dividir Pedido
 */

$(".tablaPedidosCV").on("click", "button.btnDividirPed", function () {
    var codigo = $(this).attr("codigo");
    var cod_cli = $(this).attr("cod_cli");
    var nom_cli = $(this).attr("nom_cli");
    var total = $(this).attr("total");

    $("#codPedidoD").val(codigo);
    $("#codCliD").val(cod_cli);
    $("#nomCliD").val(nom_cli);
    $("#totalD").val(total);
});

/*
 *ANULAR PEDIDOS
 */
$(".tablaPedidosAprobados, .tablaPedidosCV, .tablaPedidosGenerados").on(
    "click",
    ".btnAnularPedidoCV",
    function () {
        var codigo = $(this).attr("codigo");
        var estado = $(this).attr("estado");
        //console.log(codigo,estado);

        // Capturamos el id de la orden de compra
        swal({
            title: "¿Está seguro de anular el pedido?",
            text: "¡Si no lo está puede cancelar la acción!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, anular pedido!",
        }).then(function (result) {
            if (result.value) {
                window.location = "index.php?ruta=pedidoscv&codigoP=" + codigo;
            }
        });
    }
);

$(".btnBorrarModelo").click(function () {
    var modeloB = $(this).attr("modelo");
    var pedidoB = $(this).attr("pedido");
    //console.log("modelo", modeloB, "pedido", pedidoB);

    var datos = new FormData();
    datos.append("modeloB", modeloB);
    datos.append("pedidoB", pedidoB);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            console.log("respuesta", respuesta);

            if (respuesta == "ok") {
                Command: toastr["error"]("El modelo fue eliminado");
                $("#updDiv").load(" #updDiv"); //actualizas el div
                //$("#updDivB").load(" #updDivB");//actualizas el div
                $("#updDivC").load(" #updDivC"); //actualizas el div
            }
        },
    });
});

$(".refreshDetalle").click(function () {
    var pedido = $(this).attr("pedido");

    Command: toastr["success"]("Se actualizo los detalles");
    window.location = "index.php?ruta=crear-pedidocv&pedido=" + pedido;
});

/*
 *ANULAR PEDIDOS
 */
$(".tablaPedidosConfirmados, .tablaPedidosGenerados").on(
    "click",
    ".btnDuplicarPedido",
    function () {
        var codDup = $(this).attr("codigo");
        console.log(codDup);

        // Capturamos el id de la orden de compra
        swal({
            title: "¿Está seguro de duplicar el pedido?",
            text: "¡Si no lo está puede cancelar la acción!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, duplicar pedido!",
        }).then(function (result) {
            if (result.value) {
                var datos = new FormData();
                datos.append("codDup", codDup);

                $.ajax({
                    url: "ajax/pedidos.ajax.php",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (respuesta) {
                        if (respuesta == "ok") {
                            swal({
                                type: "success",
                                title: "Se duplico el pedido",
                                showConfirmButton: true,
                                confirmButtonText: "Cerrar",
                            }).then(function (result) {
                                if (result.value) {
                                    window.location = "pedidoscv";
                                }
                            });
                        }
                    },
                });
            }
        });
    }
);

// BUSCAR AGENCIA DE TRANSPORTES
$("#seleccionarCliente").change(function () {
    var codigo = document.getElementById("seleccionarCliente").value;

    var datos = new FormData();
    datos.append("codigo", codigo);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta["agencia"] != "") {
                $("#agencia").val(respuesta["agencia"]);
                $("#agencia").selectpicker("refresh");
            }
        },
    });
});

// El correlativo se asigna atómicamente en el servidor al generar el documento.
// No reservar / tocar talonario al elegir serie ni al cerrar el modal.

//VALIDA SI ES RUC O DNI
function ValidarRuc() {
    documento = $("#validarRuc").attr("documento");
    //console.log(documento);

    var datos = new FormData();
    datos.append("nuevoRuc", documento);
    $.ajax({
        type: "POST",
        url: "ajax/proveedor.ajax.php",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (jsonx) {
            var data = jsonx["data"];

            if (data["condicion"] == "HABIDO") {
                Command: toastr["success"]("HABIDO");
            } else {
                Command: toastr["error"]("NO HABIDO");
            }

            if (data["estado"] == "ACTIVO") {
                Command: toastr["success"]("ACTIVO");
            } else {
                Command: toastr["error"]("NO ACTIVO");
            }
            //console.log(data["condicion"]);
        },
    });
}

$("#BGBGG").change(function () {});

//************************************************** */

function updateModLista(modLista = "") {
    return modLista === "" ? $("#seleccionarLista").val() : modLista;
}

function updateFormData(datos, key, value) {
    datos.append(key, value);
}

function getTallaHtml(tallaIndex, id, isEnabled, value) {
    return isEnabled
        ? `<td><input style="width:100%" class="pruebaA" type="text" name="${id.modelo}${id.cod_color}${tallaIndex}" id="${id.modelo}${id.cod_color}${tallaIndex}" value="${value}" min="0" autocomplete="off"></td>`
        : `<td><input style="width:100%" type="text" name="${id.modelo}${id.cod_color}${tallaIndex}" id="${id.modelo}${id.cod_color}${tallaIndex}" readonly autocomplete="off"></td>`;
}

function createFila(id) {
    return (
        '<tr class="detalleCT">' +
        `<td>${id.modelo}</td>` +
        `<td>${id.color}</td>` +
        getTallaHtml(1, id, id.t1 == 1, id.v1) +
        getTallaHtml(2, id, id.t2 == 1, id.v2) +
        getTallaHtml(3, id, id.t3 == 1, id.v3) +
        getTallaHtml(4, id, id.t4 == 1, id.v4) +
        getTallaHtml(5, id, id.t5 == 1, id.v5) +
        getTallaHtml(6, id, id.t6 == 1, id.v6) +
        getTallaHtml(7, id, id.t7 == 1, id.v7) +
        getTallaHtml(8, id, id.t8 == 1, id.v8) +
        "</tr>"
    );
}

$(".modificarArtPedC").click(function () {
    const modelo = $("#modelo").val();

    if (modelo !== "") {
        const cliente = $("#seleccionarCliente").val();
        const vendedor = $("#seleccionarVendedor").val();
        const pedido = $("#nuevoCodigo").val();
        const modLista = $("#lista").val();
        const agencia = $("#agencia").val();
        const listaValue = updateModLista(modLista);

        $("#nLista").val(listaValue);
        $("#clienteA").val(cliente);
        $("#vendedorA").val(vendedor);
        $("#agenciaA").val(agencia);

        const datos = new FormData();
        updateFormData(datos, "modLista", listaValue);
        updateFormData(datos, "mod", modelo);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuestaLista) {
                if (respuestaLista["precio"] <= 0) {
                    Command: toastr["error"]("El modelo no tiene precio");
                }

                $("#modeloModalA").val(respuestaLista["modelo"]);
                $("#precioA").val(respuestaLista["precio"]);
            },
        });

        const datosPedido = new FormData();
        updateFormData(datosPedido, "modeloA", modelo);
        updateFormData(datosPedido, "pedido", pedido);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datosPedido,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuestaA) {
                $(".detalleCT").remove();

                for (var id of respuestaA) {
                    const fila = createFila(id);
                    $(".tablaColTal").append(fila);
                }

                const inputs = $("form :text");
                let i = 25;

                inputs.on("keypress", function (event) {
                    const code = event.keyCode || event.which;
                    if (code === 13) {
                        event.preventDefault();
                        i = i === inputs.length - 12 ? 26 : ++i;
                        inputs[i].focus();
                        inputs[i].select();
                    }
                });
            },
        });
    }
});

/* Igual que clic en «Agregar»: abre modal (data-toggle del botón) + carga detalle modelo */
$("#modelo").on("keydown.pedidoCvModeloModal", function (event) {
    if (event.key !== "Enter" && event.which !== 13) {
        return;
    }
    var $btn = $(".modificarArtPedC").first();
    if (!$btn.length) {
        return;
    }
    event.preventDefault();
    $btn.trigger("click");
});

//*nuevo modelo de guardar modelos por ajax

$("#guardarModelo").click(function () {
    const tableInputsJson = JSON.stringify(getTableInputsData());
    let pedidoN = document.getElementById("pedido").value;
    const nuevoPedidoN = document.getElementById("nuevoCodigo").value;
    const clienteN = document.getElementById("clienteA").value;
    const vendedorN = document.getElementById("vendedorA").value;
    const listaN = document.getElementById("nLista").value;
    const agenciaN = document.getElementById("agenciaA").value;
    const modeloN = document.getElementById("modeloModalA").value;
    const precioN = document.getElementById("precioA").value;

    var datos = new FormData();
    datos.append("pedidoN", pedidoN);
    datos.append("nuevoPedidoN", nuevoPedidoN);
    datos.append("clienteN", clienteN);
    datos.append("vendedorN", vendedorN);
    datos.append("listaN", listaN);
    datos.append("agenciaN", agenciaN);
    datos.append("modeloN", modeloN);
    datos.append("precioN", precioN);
    datos.append("articulosN", tableInputsJson);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDet) {
            var ped = respuestaDet;
            if (respuestaDet && typeof respuestaDet === "object") {
                ped = respuestaDet.pedido;
            }

            if (ped == "toast" || respuestaDet == "toast") {
                $("#modelo").val("");
                $("#totalCantidadA").val("");
                $("#totalSolesA").val("");

                $(".detalleCT").remove();
                $("#modalModificarClienteP").modal("hide");

                Command: toastr["success"]("El modelo fue registrado");
                $("#updDivB").load(" #updDivB"); //actualizas el div
                $("#updDivC").load(" #updDivC"); //actualizas el div
                $("#updDiv").load(" #updDiv"); //actualizas el div
            } else {
                window.location.href =
                    "index.php?ruta=crear-pedidocv&pedido=" + ped;
            }
        },
    });
});

// Aquí puedes agregar el código para enviar o guardar el JSON en el lugar que necesites
function getTableInputsData() {
    const tableInputsData = [];
    const tableInputs = $(".tablaColTal input");

    tableInputs.each(function () {
        const inputName = $(this).attr("name");
        const inputValue = $(this).val();

        tableInputsData.push({
            name: inputName,
            value: inputValue,
        });
    });

    return tableInputsData;
}

//* boton cambiar precio
$(".tablaPedidosCV, .tablaPedidosAprobados").on(
    "click",
    ".btnPrecio",
    function () {
        var pedido = $(this).attr("codigo");
        var numero = window.prompt(
            "Digite el número de la lista de precios para cambiar el pedido " +
                pedido
        );

        var parsedNumero = parseInt(numero);
        if (!isNaN(parsedNumero)) {
            const datosPrecio = new FormData();
            updateFormData(datosPrecio, "pedidoL", pedido);
            updateFormData(datosPrecio, "listaL", parsedNumero);
            $.ajax({
                url: "ajax/precios.ajax.php",
                method: "POST",
                data: datosPrecio,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuestaA) {
                    if (respuestaA == "ok") {
                        Command: toastr["success"]("Se actualizo los precios");
                    }
                },
            });
        }
    }
);
