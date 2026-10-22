/*
 * VISUALIZAR DETALLEs QUE SE JALAN DEL PEDIDO
 */
$(".tablaArticulosPedidos").on("click", ".agregarArtPed", function () {
    var cliente = document.getElementById("seleccionarClienteS").value;
    var vendedor = document.getElementById("seleccionarVendedor").value;
    //var usuario = document.getElementById("idUsuario").value;
    var modLista = document.getElementById("lista").value;

    //var agencia = document.getElementById("seleccionarAgencia").value;

    //console.log(usuario);

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
    }

    //console.log(cliente);
    $("#cliente").val(cliente);
    $("#vendedor").val(vendedor);
    //$("#agencia").val(agencia);
    //$("#usuario").val(usuario);

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
            //console.log("respuesta",respuestaLista["precio"]);

            $("#precio").val(respuestaLista["precio"]);
        },
    });

    /*
     * datos para la tabla
     */

    var modelo = $(this).attr("modelo");

    var datosColor = new FormData();
    datosColor.append("modelo", modelo);

    $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datosColor,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log("respuesta", respuesta);

            $("#modeloModal").val(modelo);

            $(".detalleCT").remove();

            for (var id of respuesta) {
                /* TALLA 1 */
                if (id.t1 == 1) {
                    var talla1 =
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        1 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        2 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        3 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        4 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        5 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        6 +
                        '" value=0 min="0"></td>';
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
                        '<td><input style="width:100%" class="prueba" type="number" name="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" id="' +
                        id.modelo +
                        id.cod_color +
                        7 +
                        '" value=0 min="0"></td>';
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
                        '" value=0 min="0"></td>';
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

/*
 * BOTON CREAR PEDIDO
 */
$(".btnCrearSalidaVarios").click(function () {
    var salida = $(this).attr("salida");
    //console.log("pedido", pedido);

    window.location = "index.php?ruta=crear-salidas-varios&salida=" + salida;
});

$(".btnCalCant").click(function () {
    var totalCantidad = 0;
    $(".prueba").each(function () {
        totalCantidad += parseInt($(this).val()) || 0;
    });

    var precio = document.getElementById("precio").value;

    var totalSoles = totalCantidad * precio;

    $("#totalCantidad").val(totalCantidad);

    $("#totalSoles").val(totalSoles);
    $("#totalSoles").number(true, 2);

    //console.log(totalSoles);
    //console.log(totalCantidad);
});

$("#seleccionarClienteS").change(function () {
    var cliList = document.getElementById("seleccionarClienteS").value;
    // console.log(cliList);

    var datos = new FormData();
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
            // console.log(respuestaDet);

            $("#lista").val(respuestaDet["lista_precios"]);
        },
    });
});

/*
 * quitar productos con el boton
 */

$(".formularioSalidaVarios").on("click", "button.quitarArtPed", function () {
    //console.log("boton");

    $(this).parent().parent().parent().parent().remove();

    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosS();
});

/*
 * activar cuando cambien el descuento
 */

$("#descPer").change(function () {
    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosS();
});

function cambioDescuento() {
    var bruto = document.getElementById("nuevoSubTotal").value;
    var descuento = document.getElementById("descPer").value;

    var descN = bruto * (descuento / 100);

    var subTotal = bruto - descN;

    var impNuevo = subTotal * 0.18;

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

$(".formularioSalidaVarios").on(
    "change",
    "input.nuevaCantidadArtPed",
    function () {
        var precio = $(this)
            .parent()
            .parent()
            .children(".ingresoPrecio")
            .children()
            .children(".nuevoPrecioArticulo");

        console.log("precio", precio.val());

        var precioFinal = $(this).val() * precio.attr("precioReal");

        precio.val(precioFinal.toFixed(4));

        sumarTotalesPreciosA();
        cambioDescuento();
        listarArticulosS();
    }
);

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

function listarArticulosS() {
    var listaArticulos = [];

    var descripcion = $(".nuevaDescripcionArticulo");
    console.log(
        "🚀 ~ file: salidas.js:464 ~ listarArticulos ~ descripcion:",
        descripcion
    );
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

    $("#listaProductosPedidos").val(JSON.stringify(listaArticulos));
}

/*
 * AL CAMBIAR LA CONDICION DE VENTA
 */

$("#seleccionarClienteS").change(function () {
    var cliente = document.getElementById("seleccionarClienteS").value;
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

    var vendedor = document.getElementById("seleccionarVendedorS").value;
    //console.log(vendedor)
    $("#vendedorM").val(vendedor);
});

$(".crearSalidaVarios").click(function () {
    sumarTotalesPreciosA();
    cambioDescuento();
    listarArticulosS();

    var codigo = document.getElementById("nuevoCodigo").value;
    $("#codigoM").val(codigo);

    var cliente = document.getElementById("seleccionarClienteS").value;
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

    var vendedor = document.getElementById("seleccionarVendedorS").value;
    $("#vendedorM").val(vendedor);

    var lista = document.getElementById("seleccionarLista").value;

    var opGravada = document.getElementById("nuevoSubTotalA").value;
    $("#opGravadaM").val(opGravada);

    var descuento = document.getElementById("descTotal").value;
    $("#descuentoM").val(descuento);

    var subTotal = document.getElementById("subTotal").value;
    $("#subTotalM").val(subTotal);

    var impuesto = document.getElementById("impTotal").value;
    $("#igvM").val(impuesto);

    var total = document.getElementById("nuevoTotal").value;
    $("#totalM").val(total);

    var articulos = document.getElementById("listaProductosPedidos").value;
    $("#articulosM").val(articulos);

    var usuario = document.getElementById("idUsuario").value;
    $("#usuarioM").val(usuario);

    //console.log(usuario);
});

/*
 * cargamos la tabla de pedidos
 */
$(".tablaSalidaVarios").DataTable({
    ajax: "ajax/produccion/tabla-salidas.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    pageLength: 20,
    order: [[8, "desc"]],
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
$(".tablaSalidaVarios").on("click", ".btnEditarSalidaVarios", function () {
    var salida = $(this).attr("codigo");
    //console.log("pedido", pedido);

    window.location = "index.php?ruta=crear-salidas-varios&salida=" + salida;
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaSalidaVarios").on("click", ".btnImprimirSalida", function () {
    var codigo = $(this).attr("codigo");
    //console.log(codigo);

    window.open(
        "vistas/reportes_ticket/impresion_salida.php?codigo=" + codigo,
        "_blank"
    );
});

/*
 * AL CAMBIAR EL SELECT DE DOCUMENTO
 */
$("#tdoc2").change(function () {
    var documento = document.getElementById("tdoc2").value;
    //console.log(documento);

    var serie = $("#serieSalida");
    //console.log(serie);

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({
        url: "ajax/salidas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);

            var numero = Number(respuesta["argumento"]) + Number(1);
            serie.val(("00000" + numero).slice(-5));
        },
    });

    var datos2 = new FormData();
    datos2.append("documentoSalida", documento);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta2) {
            $("#nomTipo").val(respuesta2[0]["descripcion"]);
        },
    });
});

/*
 * EVITAR DOBLE ENVÍO DE "Pasar Movimiento a:"
 * Al enviar, el botón se deshabilita. La protección definitiva está en el
 * servidor (transacción + estado), esto solo evita clics repetidos.
 */
$("#formFacturarSalida").on("submit", function () {
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop("disabled", true).text("Generando documento...");
});

/*
 * ACTIVAR MODAL
 */

$(".tablaSalidaVarios tbody").on(
    "click",
    "button.btnFacturarSalida",
    function () {
        var codigo = $(this).attr("codigo");
        var cod_cli = $(this).attr("cod_cli");
        var nom_cli = $(this).attr("nom_cli");
        var tip_doc = $(this).attr("tip_doc");
        var nro_doc = $(this).attr("nro_doc");
        var dscto = $(this).attr("dscto");
        var cod_ven = $(this).attr("cod_ven");
        //console.log(nro_doc);

        $("#codSalida").val(codigo);
        $("#codCli").val(cod_cli);
        $("#nomCli").val(nom_cli);
        $("#tipDoc").val(tip_doc);
        $("#nroDoc").val(nro_doc);
        $("#dscto").val(dscto);
        $("#codVen").val(cod_ven);
    }
);

$(".tablaSalidaVarios").on("click", ".btnAprobarPedido", function () {
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
});

$(".tablaSalidaVarios").on("click", ".btnAptear", function () {
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

$(".tablaSalidaVarios").on("click", ".btnConfirmar", function () {
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

$(".tablaSalidaVarios").on("click", ".btnFacturar", function () {
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
                text: "¡El pedido fue facturado con éxito!",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false,
            }).then((result) => {
                if (result.value) {
                    window.location = "pedidos-confirmados";
                }
            });
        },
    });
});

$(".formularioSalidaVarios").on("click", ".btnCargarCliente", function () {
    var clienteCuenta = "1";

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
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();
            xhr.upload.onprogress = function (event) {
                var perc = Math.round((event.loaded / event.total) * 100);
                $("#progressBar1").html("Cargando clientes..");
                $("#progressBar1").css("width", perc + "%");
            };
            return xhr;
        },
        beforeSend: function (xhr) {
            $("#progressBar1").text("0%");
            $("#progressBar1").css("width", "0%");
        },
        success: function (respuesta2) {
            $("#progressBar1").addClass("progress-bar");
            $("#progressBar1").text("100% - Carga realizada");

            $("#seleccionarClienteS").find("option").remove();
            $("#seleccionarClienteS").append(
                "<option value='' > Seleccionar cliente </option>"
            );
            for (let i = 0; i < respuesta2.length; i++) {
                $("#seleccionarClienteS").append(
                    "<option value='" +
                        respuesta2[i]["codigo"] +
                        "'>" +
                        respuesta2[i]["codigo"] +
                        " - " +
                        respuesta2[i]["nombre"] +
                        "</option>"
                );
            }
            $("#seleccionarClienteS").selectpicker("refresh");
        },
    });
});

/*
 * cargamos la tabla de Lista documentos
 */
$(".tablaListarDocumentos").DataTable({
    ajax: "ajax/produccion/tabla-listar-documentos.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[8, "desc"]],
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

$(".tablaArticulosSalidas").DataTable({
    ajax: "ajax/produccion/tabla-articulos-salidas.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
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

$(".tablaArticulosSalidas").on("click", ".modificarArtSal", function () {
    //console.log("hola mundo");

    var cliente = document.getElementById("seleccionarClienteS").value;
    var vendedor = document.getElementById("seleccionarVendedorS").value;
    var salida = document.getElementById("nuevoCodigo").value;
    var modLista = document.getElementById("lista").value;

    // console.log(salida);

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
            //console.log("respuesta",respuestaLista["precio"]);

            $("#precioA").val(respuestaLista["precio"]);
        },
    });

    /*
     * datos para la tabla
     */

    var modelo = $(this).attr("modelo");
    // console.log(modelo);

    var datosPedido = new FormData();
    datosPedido.append("modeloA", modelo);
    datosPedido.append("salida", salida);
    // console.log(datosPedido);

    $.ajax({
        url: "ajax/salidas.ajax.php",
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

/*
 * VISUALIZAR DETALLE DEL LISTAR DOCUMENTO
 */
$(".tablaListarDocumentos").on("click", ".btnVisualizarDocumento", function () {
    var documento2 = $(this).attr("documento");
    // console.log(documento2);

    var datos = new FormData();
    datos.append("documento2", documento2);

    $.ajax({
        url: "ajax/salidas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);

            $("#tipo").val(respuesta["tipo"]);
            $("#origen").val(respuesta["doc_origen"]);
            $("#documento").val(respuesta["documento"]);
            $("#descripcion").val(respuesta["tipo_documento"]);
            $("#total").val(respuesta["total"]);
            $("#fecha").val(respuesta["fecha"]);
            $("#cantidad").val(respuesta["total"]);
            $("#estado").val(respuesta["estado"]);
        },
    });

    var detalleDocumento = $(this).attr("documento");
    //console.log("codigoDAC", codigoDAC);

    var datosDOC = new FormData();
    datosDOC.append("codigoDIngreso", detalleDocumento);

    $.ajax({
        url: "ajax/ingresos.ajax.php",
        method: "POST",
        data: datosDOC,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            $(".detalleDoc").remove();

            for (var id of respuestaDetalle) {
                if (id.t1 > 0) {
                    var t1 = id.t1;
                } else var t1 = "";

                if (id.t2 > 0) {
                    var t2 = id.t2;
                } else var t2 = "";

                if (id.t3 > 0) {
                    var t3 = id.t3;
                } else var t3 = "";

                if (id.t4 > 0) {
                    var t4 = id.t4;
                } else var t4 = "";

                if (id.t5 > 0) {
                    var t5 = id.t5;
                } else var t5 = "";

                if (id.t6 > 0) {
                    var t6 = id.t6;
                } else var t6 = "";

                if (id.t7 > 0) {
                    var t7 = id.t7;
                } else var t7 = "";

                if (id.t8 > 0) {
                    var t8 = id.t8;
                } else var t8 = "";

                $(".tablaDetalleDocumento").append(
                    '<tr class="detalleDoc">' +
                        "<td>" +
                        id.documento +
                        " </td>" +
                        "<td><b>" +
                        id.modelo +
                        " </b></td>" +
                        "<td>" +
                        id.nombre +
                        " </td>" +
                        "<td>" +
                        id.color +
                        " </td>" +
                        "<td><b>" +
                        t1 +
                        " </b></td>" +
                        "<td><b>" +
                        t2 +
                        " </b></td>" +
                        "<td><b>" +
                        t3 +
                        " </b></td>" +
                        "<td><b>" +
                        t4 +
                        " </b></td>" +
                        "<td><b>" +
                        t5 +
                        " </b></td>" +
                        "<td><b>" +
                        t6 +
                        " </b></td>" +
                        "<td><b>" +
                        t7 +
                        " </b></td>" +
                        "<td><b>" +
                        t8 +
                        " </b></td>" +
                        "<td><b>" +
                        id.total +
                        " </b></td>" +
                        "</tr>"
                );
            }
        },
    });
});
