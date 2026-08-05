/*=============================================
CARGAR LA TABLA DINÁMICA DE CIERRES
=============================================*/

if (localStorage.getItem("capturarRango15") != null) {
    $("#daterange-btnCierres span").html(
        localStorage.getItem("capturarRango15")
    );
    cargarTablaCierres(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnCierres span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaCierres(null, null);
}

/*
 * TABLA PARA PRODUCCION Brasier
 */
function cargarTablaCierres(fechaInicial, fechaFinal) {
    $(".tablaCierres").DataTable({
        ajax:
            "ajax/produccion/tabla-cierres.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[6, "desc"]],
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
}

/*=============================================
RANGO DE FECHAS
=============================================*/

$("#daterange-btnCierres").daterangepicker(
    {
        cancelClass: "CancelarCierres",
        locale: {
            daysOfWeek: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
            monthNames: [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ],
        },
        ranges: {
            Hoy: [moment(), moment()],
            Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "Últimos 7 días": [moment().subtract(6, "days"), moment()],
            "Últimos 30 días": [moment().subtract(29, "days"), moment()],
            "Este mes": [moment().startOf("month"), moment().endOf("month")],
            "Último mes": [
                moment().subtract(1, "month").startOf("month"),
                moment().subtract(1, "month").endOf("month"),
            ],
        },

        startDate: moment(),
        endDate: moment(),
    },
    function (start, end) {
        $("#daterange-btnCierres span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango15 = $("#daterange-btnCierres span").html();

        localStorage.setItem("capturarRango15", capturarRango15);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaCierres").DataTable().destroy();
        cargarTablaCierres(fechaInicial, fechaFinal);
    }
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarCierres").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango15");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "cierres";
    }
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "cierres") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango15", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaCierres").DataTable().destroy();
            cargarTablaCierres(fechaInicial, fechaFinal);
        }
    }
});

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("sectorCierre") != null) {
    cargarTablaArticuloCierres(localStorage.getItem("sectorCierre"));
} else {
    cargarTablaArticuloCierres(null);
}

function cargarTablaArticuloCierres(sectorCierre) {
    $(".tablaArticuloCierre").DataTable({
        ajax:
            "ajax/produccion/tabla-articulocierres.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&sectorCierre=" +
            sectorCierre,
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
}

/*=============================================
  AGREGANDO PRODUCTOS A LA VENTA DESDE LA TABLA
  =============================================*/

$(".tablaArticuloCierre tbody").on(
    "click",
    "button.agregarServicio",
    function () {
        var articuloCierre = $(this).attr("articuloCierre");
        var codServicio = $(this).attr("codServicio");
        var saldoServicio = $(this).attr("saldoServicio");
        var codDetalle = $(this).attr("codDetalle");
        // console.log(codServicio);
        /* console.log("idProducto", idProducto); */

        $(this).removeClass("btn-primary agregarServicio");

        $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("articuloServicio", articuloCierre);

        $.ajax({
            url: "ajax/articulos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                var packing = respuesta["packing"];
                var servicio = respuesta["servicio"];
                /*=============================================
        EVITAR AGREGAR PRODUTO CUANDO EL STOCK ESTÁ EN CERO
        =============================================*/

                if (saldoServicio == 0) {
                    swal({
                        title: "No hay en saldo disponible",
                        type: "error",
                        confirmButtonText: "¡Cerrar!",
                    });

                    $("button[codigoServicio='" + codServicio + "']").addClass(
                        "btn-primary agregarServicio"
                    );

                    return;
                }

                $(".nuevoCierres").append(
                    '<div class="row munditoCierre" style="padding:5px 15px">' +
                        "<!-- Descripción del producto -->" +
                        '<div class="col-xs-3" style="padding-right:0px">' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarServicio" codigoServicio ="' +
                        codServicio +
                        '"><i class="fa fa-times"></i></button></span>' +
                        '<input type="text" class="form-control nuevoCodServicio2" name="agregarProducto" value="' +
                        codDetalle +
                        '"  readonly required>' +
                        '<input type="hidden" class="form-control nuevoCodServicio" value="' +
                        codServicio +
                        '"   >' +
                        "</div>" +
                        "</div>" +
                        "<!-- Descripcion del producto -->" +
                        '<div class="col-xs-5">' +
                        '<input type="text" class="form-control nuevaDescripcionProducto" name="agregarProducto" value="' +
                        packing +
                        '" articuloCierre="' +
                        articuloCierre +
                        '" saldo = "' +
                        Number(saldoServicio) +
                        '" readonly required>' +
                        "</div>" +
                        "<!-- Cantidad del producto -->" +
                        '<div class="col-xs-2">' +
                        '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="0" servicio="' +
                        saldoServicio +
                        '" nuevoServicio="' +
                        saldoServicio +
                        '"  required>' +
                        "</div>" +
                        "<!-- Taller del producto -->" +
                        '<div class="col-xs-2 ingresoServicio">' +
                        '<input type="number" class="form-control nuevoServicioProducto" name="nuevoServicioProducto" id="nuevoServicioProducto" value="' +
                        saldoServicio +
                        '" readonly>' +
                        "</div>" +
                        "</div>"
                );

                // SUMAR TOTAL DE CANTIDADES
                sumarTotalCierre();

                // AGRUPAR PRODUCTOS EN FORMATO JSON

                listarCierres();
            },
        });
    }
);

/*=============================================
  CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
  =============================================*/

$(".tablaArticuloCierre").on("draw.dt", function () {
    // console.log("tabla");

    if (localStorage.getItem("quitarServicio") != null) {
        var listaIdServicios = JSON.parse(
            localStorage.getItem("quitarServicio")
        );
        // console.log(listaIdProductos);

        for (var i = 0; i < listaIdServicios.length; i++) {
            $(
                "button.recuperarBoton[codServicio='" +
                    listaIdServicios[i]["codigoServicio"] +
                    "']"
            ).removeClass("btn-default");
            $(
                "button.recuperarBoton[codServicio='" +
                    listaIdServicios[i]["codigoServicio"] +
                    "']"
            ).addClass("btn-primary agregarServicio");
        }
    }
});

/*=============================================
  QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
  =============================================*/

var idQuitarProducto = [];

localStorage.removeItem("quitarServicio");

$(".formularioCierre").on("click", "button.quitarServicio", function () {
    var fila = $(this).closest(".munditoCierre");
    var codigoServicio = $(this).attr("codigoServicio");
    var cantidadDevuelta = Number(
        fila.find("input.nuevaCantidadProducto").val()
    );

    if (isNaN(cantidadDevuelta) || cantidadDevuelta < 0) {
        cantidadDevuelta = 0;
    }

    fila.remove();

    /*=============================================
    ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
    =============================================*/

    if (localStorage.getItem("quitarServicio") == null) {
        idQuitarProducto = [];
    } else {
        idQuitarProducto.concat(localStorage.getItem("quitarServicio"));
    }

    idQuitarProducto.push({
        codigoServicio: codigoServicio,
    });
    localStorage.setItem("quitarServicio", JSON.stringify(idQuitarProducto));

    var botonRecuperar = $(
        "button.recuperarBoton[codServicio='" + codigoServicio + "']"
    );
    var saldoBoton = Number(botonRecuperar.attr("saldoServicio"));
    if (isNaN(saldoBoton)) {
        saldoBoton = 0;
    }
    botonRecuperar.attr("saldoServicio", saldoBoton + cantidadDevuelta);
    botonRecuperar.removeClass("btn-default");
    botonRecuperar.addClass("btn-primary agregarServicio");

    // Actualizar celda de saldo en la tabla si está visible
    var celdaSaldo = botonRecuperar.closest("tr").find("td").eq(6);
    if (celdaSaldo.length) {
        celdaSaldo.text(saldoBoton + cantidadDevuelta);
    }

    if ($(".nuevoCierres .munditoCierre").length == 0) {
        $("#nuevoTotalVenta").val(0);
        $("#totalVenta").val(0);
        $("#nuevoTotalVenta").attr("total", 0);
        $("#listaProductos").val("[]");
    } else {
        sumarTotalCierre();
        listarCierres();
    }
});

/*=============================================
  MODIFICAR LA CANTIDAD
  =============================================*/

$(".formularioCierre").on("change", "input.nuevaCantidadProducto", function () {
    var nuevoServicio = Number($(this).attr("servicio")) - $(this).val();
    var inputSer = $(this)
        .parent()
        .parent()
        .children(".ingresoServicio")
        .children(".nuevoServicioProducto");
    // console.log(inputSer);
    inputSer.val(nuevoServicio);

    $(this).attr("nuevoServicio", nuevoServicio);

    if (Number($(this).val()) > Number($(this).attr("servicio")) + 10) {
        /*=============================================
      SI LA CANTIDAD ES SUPERIOR AL SERVICIO REGRESAR VALORES INICIALES
      =============================================*/

        $(this).val(0);

        swal({
            title: "La cantidad supera mas de 10 unidades la cantidad de servicio",
            text: "¡Sólo hay " + $(this).attr("servicio") + " unidades!",
            type: "error",
            confirmButtonText: "¡Cerrar!",
        });

        return;
    }

    sumarTotalCierre();

    // AGRUPAR PRODUCTOS EN FORMATO JSON

    listarCierres();
});

$("#nuevoTotalVenta").number(true, 2);

/*=============================================
  LISTAR TODOS LOS PRODUCTOS
  =============================================*/

function listarCierres() {
    var listaProductos = [];

    var descripcion = $(".nuevaDescripcionProducto");

    var cantidad = $(".nuevaCantidadProducto");

    var codigoSer = $(".nuevoCodServicio");

    for (var i = 0; i < descripcion.length; i++) {
        listaProductos.push({
            id: $(descripcion[i]).attr("articuloCierre"),
            articulo: $(descripcion[i]).attr("articuloCierre"),
            cantidad: Number($(cantidad[i]).val()),
            servicio: Number($(cantidad[i]).attr("servicio")),
            codServicio: $(codigoSer[i]).val(),
            saldo: Number($(descripcion[i]).attr("saldo")),
        });
    }

    $("#listaProductos").val(JSON.stringify(listaProductos));
}

/*
 * SUMAR EL TOTAL DE LAS VENTAS
 */

function sumarTotalCierre() {
    var cantidadSer = $(".nuevaCantidadProducto");

    //console.log("cantidadOc", cantidadOc);

    var arraySumarCantidades = [];

    for (var i = 0; i < cantidadSer.length; i++) {
        arraySumarCantidades.push(Number($(cantidadSer[i]).val()));
    }
    /* console.log("arraySumarCantidades", arraySumarCantidades); */

    function sunaArrayCantidades(total, numero) {
        return total + numero;
    }

    var sumarTotal = arraySumarCantidades.length
        ? arraySumarCantidades.reduce(sunaArrayCantidades)
        : 0;

    $("#nuevoTotalVenta").val(sumarTotal);
    $("#totalVenta").val(sumarTotal);
    $("#nuevoTotalVenta").attr("total", sumarTotal);
    if ($("#cierreSummaryTotal").length) {
        $("#cierreSummaryTotal").text(sumarTotal);
    }
}

/*=============================================
  BOTON EDITAR SERVICIO
  =============================================*/
$(".tablaCierres").on("click", ".btnEditarCierre", function () {
    var idCierre = $(this).attr("idCierre");

    window.location = "index.php?ruta=editar-cierre&idCierre=" + idCierre;
});

// Formato para los números en las cajas
$("#totalVenta").number(true, 0);

/*=============================================
  FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
  =============================================*/

function quitarAgregarProducto() {
    // Capturamos todos los servicios ya elegidos en el cierre
    var idProductos = $(".quitarServicio");
    var botonesTabla = $(".tablaArticuloCierre tbody button.recuperarBoton");

    for (var i = 0; i < idProductos.length; i++) {
        var boton = $(idProductos[i]).attr("codigoServicio");

        for (var j = 0; j < botonesTabla.length; j++) {
            if ($(botonesTabla[j]).attr("codServicio") == boton) {
                $(botonesTabla[j]).removeClass("btn-primary agregarServicio");
                $(botonesTabla[j]).addClass("btn-default");
            }
        }
    }
}

/*=============================================
  CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIÓN:
  =============================================*/

$(".tablaArticuloCierre").on("draw.dt", function () {
    quitarAgregarProducto();
});

/*=============================================
  BORRAR VENTA
  =============================================*/
$(".tablaCierres").on("click", ".btnEliminarCierre", function () {
    var idCierre = $(this).attr("idCierre");
    swal({
        type: "warning",
        title: "Advertencia",
        text: "¿Está seguro de eliminar el cierre? ¡Si no está seguro, puede cancelar la acción!",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "¡Si, eliminar cierre!",
        cancelButtonText: "Cancelar",
    }).then(function (result) {
        if (result.value) {
            var datos = new FormData();
            datos.append("idCierre", idCierre);
            $.ajax({
                url: "ajax/cierres.ajax.php",
                type: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                success: function (respuesta) {
                    if (respuesta == "ok") {
                        swal({
                            type: "success",
                            title: "¡Ok!",
                            text: "¡La información fue Eliminada con éxito!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar",
                        }).then((result) => {
                            if (result.value) {
                                window.location = "cierres";
                            }
                        });
                    }
                },
            });
        }
    });
});

/*=============================================
  BOTON REPORTE SERVICIO CON MATERIA PRIMA
  =============================================*/
$(".tablaCierres").on("click", ".btnDetalleCierre", function () {
    var idCierre = $(this).attr("idCierre");

    window.location =
        "vistas/reportes_excel/rpt_detalle_cierre.php?idCierre=" + idCierre;
});

$("#seleccionarSector").change(function () {
    var cierre = $(this).val();
    $(".tablaArticuloCierre").DataTable().destroy();
    localStorage.setItem("sectorCierre", cierre);
    cargarTablaArticuloCierres(localStorage.getItem("sectorCierre"));
    var datos2 = new FormData();
    datos2.append("cierre", cierre);
    $.ajax({
        url: "ajax/cierres.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoCierre").val(
                cierre + "R" + ("000" + respuesta["ultimo_codigo"]).slice(-4)
            );
        },
    });
});

/*
 * VISUALIZAR DETALLE DEL CORTE
 */
$(".tablaCierres").on("click", ".btnVisualizarCierre", function () {
    var codigoCierre = $(this).attr("codigoCierre");
    //console.log("codigoAC", codigoAC);

    var datos = new FormData();
    datos.append("codigoCierre", codigoCierre);

    $.ajax({
        url: "ajax/cierres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log("respuesta", respuesta);
            $("#idCierre").val(respuesta["id"]);
            $("#cierre").val(respuesta["codigo"]);
            $("#guia").val(respuesta["guia"]);
            $("#fecha").val(respuesta["fecha"]);
            $("#nombre").val(
                respuesta["taller"] + " - " + respuesta["nom_sector"]
            );
            $("#cantidad").val(respuesta["total"]);
            $("#estado").val(respuesta["estado"]);

            $("#cantidad").number(true, 0);
        },
    });

    var codigoDCierre = $(this).attr("codigoCierre");
    //console.log("codigoDAC", codigoDAC);

    var datosDOC = new FormData();
    datosDOC.append("codigoDCierre", codigoDCierre);

    $.ajax({
        url: "ajax/cierres.ajax.php",
        method: "POST",
        data: datosDOC,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            // console.log("respuestaDetalle", respuestaDetalle);

            $(".detalleMP").remove();

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

                $(".tablaDetalleOC").append(
                    '<tr class="detalleMP">' +
                        "<td>" +
                        id.cod_sector +
                        " - " +
                        id.nom_sector +
                        " </td>" +
                        "<td>" +
                        id.guia +
                        " </td>" +
                        "<td>" +
                        id.fechas +
                        " </td>" +
                        "<td>" +
                        id.codigo +
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

if (localStorage.getItem("capturarRango16") != null) {
    $("#daterange-btnVerCierres span").html(
        localStorage.getItem("capturarRango16")
    );
    cargarTablaDetalleCierres(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnVerCierres span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaDetalleCierres(null, null);
}

function cargarTablaDetalleCierres(fechaInicial, fechaFinal) {
    $(".tablaDetalleCierrreTotal").DataTable({
        ajax:
            "ajax/produccion/tabla-ver-cierres.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
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
        createdRow: function (row, data, index) {
            if (data[0] == "T4 - ADELA") {
                $("td", row).css({
                    "background-color": "#D6C4D5",
                    color: "black",
                });
            } else if (data[0] == "T6 - PABLO") {
                $("td", row).css({
                    "background-color": "#C7C1D8",
                    color: "black",
                });
            } else if (data[0] == "T9 - FRANCISCO") {
                $("td", row).css({
                    "background-color": "#DADEBE",
                    color: "black",
                });
            } else if (data[0] == "TA - ELVIRA") {
                $("td", row).css({
                    "background-color": "#F7E4E9",
                    color: "black",
                });
            } else if (data[0] == "T7 - GUSTAVO") {
                $("td", row).css({
                    "background-color": "#D4F8F7",
                    color: "black",
                });
            } else if (data[0] == "T6 - PABLO") {
                $("td", row).css({
                    "background-color": "#D4F8E2",
                    color: "black",
                });
            } else if (data[0] == "T8 - MIGUEL") {
                $("td", row).css({
                    "background-color": "#F4F8D4",
                    color: "black",
                });
            }
        },
    });
}
$(".box").on("click", ".btnRegistroCierre", function () {
    localStorage.removeItem("sectorCierre");
});
$(".box").on("click", ".btnLimpiarSectorCierre", function () {
    localStorage.removeItem("sectorCierre");
    window.location = "crear-cierre";
});

/*=============================================
RANGO DE FECHAS
=============================================*/

$("#daterange-btnVerCierres").daterangepicker(
    {
        cancelClass: "CancelarVerCierres",
        locale: {
            daysOfWeek: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
            monthNames: [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ],
        },
        ranges: {
            Hoy: [moment(), moment()],
            Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "Últimos 7 días": [moment().subtract(6, "days"), moment()],
            "Últimos 30 días": [moment().subtract(29, "days"), moment()],
            "Este mes": [moment().startOf("month"), moment().endOf("month")],
            "Último mes": [
                moment().subtract(1, "month").startOf("month"),
                moment().subtract(1, "month").endOf("month"),
            ],
        },

        startDate: moment(),
        endDate: moment(),
    },
    function (start, end) {
        $("#daterange-btnVerCierres span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango16 = $("#daterange-btnVerCierres span").html();

        localStorage.setItem("capturarRango16", capturarRango16);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaDetalleCierrreTotal").DataTable().destroy();
        cargarTablaDetalleCierres(fechaInicial, fechaFinal);
    }
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarVerCierres").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango16");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "cierres";
    }
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if (ruta == "cierres") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango16", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaDetalleCierrreTotal").DataTable().destroy();
            cargarTablaDetalleCierres(fechaInicial, fechaFinal);
        }
    }
});

$(".tablaCierres").on("click", ".btnPagarCierre", function () {
    // Capturamos la guia de cierre y el estado de pago

    var guiaCierre = $(this).attr("guiaCierre");
    var estadoPago = $(this).attr("estadoPago");
    //Realizamos el pago por una petición AJAX
    var datos = new FormData();
    datos.append("activarGuia", guiaCierre);
    datos.append("activarPago", estadoPago);
    $.ajax({
        url: "ajax/cierres.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            if (estadoPago == "POR PAGAR") {
                Command: toastr["warning"]("Por pagar !");
            } else {
                Command: toastr["info"]("Pagado exitosamente!");
            }
        },
    });
    //Cambiamos el estado del botón físicamente
    if (estadoPago == "PAGADO") {
        $(this).removeClass("btn-warning");
        $(this).addClass("btn-primary");
        $(this).html("PAGADO");
        $(this).attr("estadoPago", "POR PAGAR");
    } else {
        $(this).removeClass("btn-primary");
        $(this).addClass("btn-warning");
        $(this).html("POR PAGAR");
        $(this).attr("estadoPago", "PAGADO");
    }
});

$(".tablaArticuloCierre tbody").on(
    "click",
    "button.btnCerrarServicio",
    function () {
        var articuloSer = $(this).attr("articulo");
        var saldo = $(this).attr("saldo");
        var id = $(this).attr("idServ");

        //console.log(articuloSer, saldo, id)

        $(this).removeClass("btn-danger btnCerrarServicio");
        $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("articuloSer", articuloSer);
        datos.append("saldo", saldo);
        datos.append("id", id);

        $.ajax({
            url: "ajax/cierres.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                console.log(respuesta);
            },
        });
    }
);

$(".box").on("click", ".btnReporteCierre", function () {
    window.location = "vistas/reportes_excel/rpt_cierres.php";
});
