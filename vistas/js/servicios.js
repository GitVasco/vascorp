/*=============================================
CARGAR LA TABLA DINÁMICA DE VENTAS
=============================================*/

/*  $.ajax({

 	url: "ajax/tabla-ventas.ajax.php",
 	success:function(respuesta){
		
 		console.log("respuesta", respuesta);

 	}

 })  */

if (localStorage.getItem("capturarRango19") != null) {
    $("#daterange-btnServicios span").html(
        localStorage.getItem("capturarRango19")
    );
    cargarTablaServicios(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnServicios span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaServicios(null, null);
}

/*
 * TABLA PARA PRODUCCION Brasier
 */
function cargarTablaServicios(fechaInicial, fechaFinal) {
    $(".tablaServicios").DataTable({
        ajax:
            "ajax/produccion/tabla-servicios.ajax.php?perfil=" +
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

$("#daterange-btnServicios").daterangepicker(
    {
        cancelClass: "CancelarServicios",
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
        $("#daterange-btnServicios span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango19 = $("#daterange-btnServicios span").html();

        localStorage.setItem("capturarRango19", capturarRango19);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaServicios").DataTable().destroy();
        cargarTablaServicios(fechaInicial, fechaFinal);
    }
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarServicios").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango19");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "servicios";
    }
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li ").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "servicios") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango19", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaServicios").DataTable().destroy();
            cargarTablaServicios(fechaInicial, fechaFinal);
        }
    }
});

$(".tablaArticuloServicio").DataTable({
    ajax: "ajax/produccion/tabla-articuloservicios.ajax.php",
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

$(".tablaPrecioServicios").DataTable({
    ajax: "ajax/produccion/tabla-precio-servicio.ajax.php",
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

/*=============================================
  AGREGANDO PRODUCTOS A LA VENTA DESDE LA TABLA
  =============================================*/

$(".tablaArticuloServicio tbody").on(
    "click",
    "button.agregarProducto",
    function () {
        var articuloServicio = $(this).attr("articuloServicio");

        /* console.log("idProducto", idProducto); */

        $(this).removeClass("btn-primary agregarProducto");

        $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("articuloServicio", articuloServicio);

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
                var taller = respuesta["taller"];
                var servicio = respuesta["servicio"];
                /*=============================================
        EVITAR AGREGAR PRODUTO CUANDO EL STOCK ESTÁ EN CERO
        =============================================*/

                if (taller == 0) {
                    swal({
                        title: "No hay en taller disponible",
                        type: "error",
                        confirmButtonText: "¡Cerrar!",
                    });

                    $(
                        "button[articuloServicio='" + articuloServicio + "']"
                    ).addClass("btn-primary agregarProducto");

                    return;
                }

                $(".nuevoProducto").append(
                    '<div class="row munditoServicio" style="padding:5px 15px">' +
                        "<!-- Descripción del producto -->" +
                        '<div class="col-xs-6" style="padding-right:0px">' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" articuloServicio="' +
                        articuloServicio +
                        '"><i class="fa fa-times"></i></button></span>' +
                        '<input type="text" class="form-control nuevaDescripcionProducto" name="agregarProducto" value="' +
                        packing +
                        '" articuloServicio="' +
                        articuloServicio +
                        '" readonly required>' +
                        "</div>" +
                        "</div>" +
                        "<!-- Cantidad del producto -->" +
                        '<div class="col-xs-3">' +
                        '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="0" taller="' +
                        taller +
                        '" nuevoTaller="' +
                        taller +
                        '"  servicio= "' +
                        servicio +
                        '"required>' +
                        "</div>" +
                        "<!-- Taller del producto -->" +
                        '<div class="col-xs-3 ingresoTaller">' +
                        '<input type="number" class="form-control nuevoTallerProducto" name="nuevoTallerProducto" id="nuevoTallerProducto" value="' +
                        taller +
                        '" readonly>' +
                        "</div>" +
                        "</div>"
                );

                // SUMAR TOTAL DE CANTIDADES
                sumarTotalServicio();

                // AGRUPAR PRODUCTOS EN FORMATO JSON

                listarServicios();
            },
        });
    }
);

/*=============================================
  CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
  =============================================*/

$(".tablaArticuloServicio").on("draw.dt", function () {
    /* console.log("tabla"); */

    if (localStorage.getItem("quitarProducto") != null) {
        var listaIdProductos = JSON.parse(
            localStorage.getItem("quitarProducto")
        );
        // console.log(listaIdProductos);
        for (var i = 0; i < listaIdProductos.length; i++) {
            $(
                "button.recuperarBoton[articuloServicio='" +
                    listaIdProductos[i]["articuloServicio"] +
                    "']"
            ).removeClass("btn-default");
            $(
                "button.recuperarBoton[articuloServicio='" +
                    listaIdProductos[i]["articuloServicio"] +
                    "']"
            ).addClass("btn-primary agregarProducto");
        }
    }
});

/*=============================================
  QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
  =============================================*/

var idQuitarProducto = [];

localStorage.removeItem("quitarProducto");

$(".formularioServicio").on("click", "button.quitarProducto", function () {
    /* console.log("boton"); */

    $(this).parent().parent().parent().parent().remove();

    var articuloServicio = $(this).attr("articuloServicio");

    /*=============================================
    ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
    =============================================*/

    if (localStorage.getItem("quitarProducto") == null) {
        idQuitarProducto = [];
    } else {
        idQuitarProducto.concat(localStorage.getItem("quitarProducto"));
    }

    idQuitarProducto.push({
        articuloServicio: articuloServicio,
    });

    localStorage.setItem("quitarProducto", JSON.stringify(idQuitarProducto));

    $(
        "button.recuperarBoton[articuloServicio='" + articuloServicio + "']"
    ).removeClass("btn-default");

    $(
        "button.recuperarBoton[articuloServicio='" + articuloServicio + "']"
    ).addClass("btn-primary agregarProducto");

    if ($(".nuevoProducto").children().length == 0) {
        $("#nuevoTotalVenta").val(0);
        $("#totalVenta").val(0);
        $("#nuevoTotalVenta").attr("total", 0);
    } else {
        // SUMAR TOTAL DE PRECIOS

        // AGRUPAR PRODUCTOS EN FORMATO JSON
        sumarTotalServicio();

        listarServicios();
    }
});

/*=============================================
  MODIFICAR LA CANTIDAD
  =============================================*/

$(".formularioServicio").on(
    "change",
    "input.nuevaCantidadProducto",
    function () {
        var nuevoTaller = Number($(this).attr("taller")) - $(this).val();
        var inputTaller = $(this)
            .parent()
            .parent()
            .children(".ingresoTaller")
            .children(".nuevoTallerProducto");
        inputTaller.val(nuevoTaller);

        $(this).attr("nuevoTaller", nuevoTaller);

        if (Number($(this).val()) > Number($(this).attr("taller"))) {
            /*=============================================
      SI LA CANTIDAD ES SUPERIOR AL TALLER REGRESAR VALORES INICIALES
      =============================================*/

            $(this).val(0);

            swal({
                title: "La cantidad supera la cantidad de taller",
                text: "¡Sólo hay " + $(this).attr("taller") + " unidades!",
                type: "error",
                confirmButtonText: "¡Cerrar!",
            });

            return;
        }

        sumarTotalServicio();

        // AGRUPAR PRODUCTOS EN FORMATO JSON

        listarServicios();
    }
);

$("#nuevoTotalVenta").number(true, 2);

/*=============================================
  LISTAR TODOS LOS PRODUCTOS
  =============================================*/

function listarServicios() {
    var listaProductos = [];

    var descripcion = $(".nuevaDescripcionProducto");

    var cantidad = $(".nuevaCantidadProducto");

    for (var i = 0; i < descripcion.length; i++) {
        listaProductos.push({
            id: $(descripcion[i]).attr("articuloServicio"),
            articulo: $(descripcion[i]).attr("articuloServicio"),
            cantidad: $(cantidad[i]).val(),
            taller: $(cantidad[i]).attr("taller"),
            servicio: $(cantidad[i]).attr("servicio"),
        });
    }

    //  console.log("listaProductos", JSON.stringify(listaProductos));

    $("#listaProductos").val(JSON.stringify(listaProductos));
    // console.log(JSON.stringify(listaProductos));
}

/*
 * SUMAR EL TOTAL DE LAS VENTAS
 */

function sumarTotalServicio() {
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

    var sumarTotal = arraySumarCantidades.reduce(sunaArrayCantidades);

    /* console.log("sumarTotal", sumarTotal); */

    $("#nuevoTotalVenta").val(sumarTotal);
    $("#totalVenta").val(sumarTotal);
    $("#nuevoTotalVenta").attr("total", sumarTotal);
}

/*=============================================
  BOTON EDITAR SERVICIO
  =============================================*/
$(".tablaServicios").on("click", ".btnEditarServicio", function () {
    var idServicio = $(this).attr("idServicio");

    window.location = "index.php?ruta=editar-servicio&idServicio=" + idServicio;
});

// Formato para los números en las cajas
$("#totalVenta").number(true, 0);

/*=============================================
  FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
  =============================================*/

function quitarAgregarProducto() {
    //Capturamos todos los id de productos que fueron elegidos en la venta
    var idProductos = $(".quitarProducto");
    //console.log("idProductos", idProductos);

    //Capturamos todos los botones de agregar que aparecen en la tabla
    var botonesTabla = $(".tablaArticuloServicio tbody button.agregarProducto");

    /* console.log("botonesTabla", botonesTabla); */

    //Recorremos en un ciclo para obtener los diferentes idProductos que fueron agregados a la venta
    for (var i = 0; i < idProductos.length; i++) {
        //Capturamos los Id de los productos agregados a la venta
        var boton = $(idProductos[i]).attr("articuloServicio");

        //Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
        for (var j = 0; j < botonesTabla.length; j++) {
            if ($(botonesTabla[j]).attr("articuloServicio") == boton) {
                $(botonesTabla[j]).removeClass("btn-primary agregarProducto");
                $(botonesTabla[j]).addClass("btn-default");
            }
        }
    }
}

/*=============================================
  CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIÓN:
  =============================================*/

$(".tablaArticuloServicio").on("draw.dt", function () {
    quitarAgregarProducto();
});

/*=============================================
  BORRAR VENTA
  =============================================*/
$(".tablaServicios").on("click", ".btnEliminarServicio", function () {
    var idServicio = $(this).attr("idServicio");
    swal({
        type: "warning",
        title: "Advertencia",
        text: "¿Está seguro de eliminar el servicio? ¡Si no está seguro, puede cancelar la acción!",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "¡Si, eliminar servicio!",
        cancelButtonText: "Cancelar",
    }).then(function (result) {
        if (result.value) {
            var datos = new FormData();
            datos.append("idServicio", idServicio);
            $.ajax({
                url: "ajax/servicios.ajax.php",
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
                                window.location = "servicios";
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
$(".tablaServicios").on("click", ".btnDetalleServicio", function () {
    var idServicio = $(this).attr("idServicio");

    window.location =
        "vistas/reportes_excel/rpt_detalle_servicio.php?idServicio=" +
        idServicio;
});

$("#seleccionarSector").change(function () {
    var servicio = $(this).val();
    var datos2 = new FormData();
    datos2.append("servicio", servicio);
    $.ajax({
        url: "ajax/servicios.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoServicio").val(
                servicio + ("000" + respuesta["ultimo_codigo"]).slice(-4)
            );
        },
    });
});

/*=============================================
EDITAR PRECIO SERVICIO
=============================================*/
$(".tablaPrecioServicios").on("click", ".btnEditarPrecioServicio", function () {
    var idPrecioServicio = $(this).attr("idPrecioServicio");

    var datos = new FormData();
    datos.append("idPrecioServicio", idPrecioServicio);

    $.ajax({
        url: "ajax/servicios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idPrecioServicio").val(respuesta["id"]);
            $("#editarTallerPrecio").val(respuesta["taller"]);
            $("#editarTallerPrecio").selectpicker("refresh");

            $("#editarModeloPrecio").val(respuesta["modelo"]);

            $("#editarModeloPrecio").selectpicker("refresh");
            $("#editarPrecioDocenaServicio").val(respuesta["precio_doc"]);
            console.log("🚀 ~ file: servicios.js:700 ~ ", respuesta["modelo"]);
        },
    });
});

/*=============================================
ELIMINAR PRECIO DE SERVICIO
=============================================*/
$(".tablaPrecioServicios").on(
    "click",
    ".btnEliminarPrecioServicio",
    function () {
        var idPrecioServicio = $(this).attr("idPrecioServicio");

        swal({
            title: "¿Está seguro de borrar el precio servicio?",
            text: "¡Si no lo está puede cancelar la acción!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, borrar precio servicio!",
        }).then(function (result) {
            if (result.value) {
                window.location =
                    "index.php?ruta=precio-servicio&idPrecioServicio=" +
                    idPrecioServicio;
            }
        });
    }
);

/*
 * VISUALIZAR DETALLE DEL CORTE
 */
$(".tablaServicios").on("click", ".btnVisualizarServicio", function () {
    var codigoServicio = $(this).attr("codigoServicio");
    //console.log("codigoAC", codigoAC);

    var datos = new FormData();
    datos.append("codigoServicio", codigoServicio);

    $.ajax({
        url: "ajax/servicios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log("respuesta", respuesta);

            $("#servicio").val(respuesta["codigo"]);
            $("#fecha").val(respuesta["fecha"]);
            $("#nombre").val(
                respuesta["taller"] + " - " + respuesta["nom_sector"]
            );
            $("#cantidad").val(respuesta["total"]);
            $("#estado").val(respuesta["estado"]);

            $("#cantidad").number(true, 0);
        },
    });

    var codigoDServicio = $(this).attr("codigoServicio");
    //console.log("codigoDAC", codigoDAC);

    var datosDOC = new FormData();
    datosDOC.append("codigoDServicio", codigoDServicio);

    $.ajax({
        url: "ajax/servicios.ajax.php",
        method: "POST",
        data: datosDOC,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            // console.log("respuestaDetalle", respuestaDetalle);

            $(".detalleMP").remove();

            var totalEnviado = 0;

            for (var id of respuestaDetalle) {
                totalEnviado += Number(id.total) || 0;

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

            // Cabecera: total enviado según detalle (cantidad), no saldo ni total desactualizado
            $("#cantidad").val(totalEnviado);
            $("#cantidad").number(true, 0);
        },
    });
});

/*
 * VISUALIZAR DETALLE DEL SERVICIO GENERAL
 */

if (localStorage.getItem("capturarRango20") != null) {
    $("#daterange-btnServicios span").html(
        localStorage.getItem("capturarRango20")
    );
    cargarTablaServiciosGeneral(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnServicios span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaServiciosGeneral(null, null);
}

/*
 * TABLA PARA PRODUCCION Brasier
 */
function cargarTablaServiciosGeneral(fechaInicial, fechaFinal) {
    $(".tablaDetalleSerTotal").DataTable({
        ajax:
            "ajax/produccion/tabla-ver-servicios.ajax.php?perfil=" +
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

/*=============================================
RANGO DE FECHAS
=============================================*/

$("#daterange-btnVerServicios").daterangepicker(
    {
        cancelClass: "CancelarVerServicios",
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
        $("#daterange-btnVerServicios span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango20 = $("#daterange-btnVerServicios span").html();

        localStorage.setItem("capturarRango20", capturarRango20);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaDetalleSerTotal").DataTable().destroy();
        cargarTablaServiciosGeneral(fechaInicial, fechaFinal);
    }
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarVerServicios").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango20");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "servicios";
    }
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li ").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "servicios") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango20", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaDetalleSerTotal").DataTable().destroy();
            cargarTablaServiciosGeneral(fechaInicial, fechaFinal);
        }
    }
});

$(".tablaPagoServicios").on("click", ".btnVerPagoSer", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    $("#btnReportePagoServicios").attr("inicio", inicio);
    $("#btnReportePagoServicios").attr("fin", fin);
    $(".tablaVerPagoSer").DataTable().destroy();
    $(".tablaVerPagoSer").DataTable({
        ajax:
            "ajax/produccion/tabla-ver-pagoservicios.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&inicio=" +
            inicio +
            "&fin=" +
            fin,
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
});
$(".tablaPagoServicios").DataTable({
    ajax:
        "ajax/produccion/tabla-pago-servicio.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[3, "desc"]],
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
 * EDITAR PAGO SERVICIO
 */
$(".tablaPagoServicios").on("click", ".btnEditarPagoServicio", function () {
    var idPagoServicio = $(this).attr("id");
    var datos = new FormData();
    // console.log(idPagoServicio);
    datos.append("idPagoServicio", idPagoServicio);
    $.ajax({
        url: "ajax/servicios.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta)
            $("#id").val(respuesta["id"]);
            $("#editarMes").val(respuesta["nmes"]);
            $("#editarMes").selectpicker("refresh");
            $("#editarSemana").selectpicker("refresh");
            $("#editarInicio").val(respuesta["inicio"]);
            $("#editarFin").val(respuesta["fin"]);
        },
    });
});

$(".tablaPagoServicios tbody").on(
    "click",
    "button.btnEliminarPagoServicio",
    function () {
        var idPagoServicio = $(this).attr("id");
        //console.log("idQuincena", idQuincena);

        swal({
            title: "¿Está seguro de borrar el pago de servicio?",
            text: "¡Si no lo está puede cancelar la accíón!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, borrar pago de servicio!",
        }).then(function (result) {
            if (result.value) {
                window.location =
                    "index.php?ruta=pago-servicio&idPagoServicio=" +
                    idPagoServicio;
            }
        });
    }
);

$(".btnReportePagoServicios").click(function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    window.location =
        "vistas/reportes_excel/rpt_pago_servicio.php?inicio=" +
        inicio +
        "&fin=" +
        fin;
});

$(".tablaPagoServicios tbody").on(
    "click",
    "button.btnReportePagoServicios2",
    function () {
        var inicio = $(this).attr("inicio");
        var fin = $(this).attr("fin");
        var id = $(this).attr("id");
        window.location =
            "vistas/reportes_excel/rpt_pago_servicio.php?inicio=" +
            inicio +
            "&fin=" +
            fin +
            "&id=" +
            id;
    }
);

$(".tablaPagoServicios").on("click", ".btnPagarCierreServicio", function () {
    // Capturamos la guia de cierre y el estado de pago

    var idPago = $(this).attr("idPago");
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");

    var estadoPago = $(this).attr("estadoPago");
    //Realizamos el pago por una petición AJAX
    var datos = new FormData();
    datos.append("idPago", idPago);
    datos.append("estadoPago", estadoPago);
    $.ajax({
        url: "ajax/servicios.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            // console.log("se pago en la quincena");
            var datos2 = new FormData();
            datos2.append("inicio", inicio);
            datos2.append("fin", fin);
            datos2.append("estadoPagoServicio", estadoPago);
            $.ajax({
                url: "ajax/cierres.ajax.php",
                type: "POST",
                data: datos2,
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

/*
 * EDITAR PAGO SERVICIO
 */
$(".tablaPagoServicios").on("click", ".btnVerEtiqueta", function () {
    var idPagoServicio = $(this).attr("id");
    var datos = new FormData();
    // console.log(idPagoServicio);
    datos.append("idPagoServicio", idPagoServicio);
    $.ajax({
        url: "ajax/servicios.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#id2").val(respuesta["id"]);
            $("#editarInicio2").val(respuesta["inicio"]);
            $("#editarFin2").val(respuesta["fin"]);

            $(".detalleEtq").remove();

            var nombre = "";

            for (var i = 1; i <= 15; i++) {
                if (i == 1) {
                    nombre = "T0-IRMA";
                } else if (i == 2) {
                    nombre = "T1-BRASIER";
                } else if (i == 3) {
                    nombre = "T2-MEDRANO";
                } else if (i == 4) {
                    nombre = "T3-TRUSAS";
                } else if (i == 5) {
                    nombre = "T4-ADELA";
                } else if (i == 6) {
                    nombre = "T5-VASCO";
                } else if (i == 7) {
                    nombre = "T6-PABLO";
                } else if (i == 8) {
                    nombre = "T7-GUSTAVO";
                } else if (i == 9) {
                    nombre = "T8-MIGUEL";
                } else if (i == 10) {
                    nombre = "T9-FRANCISCO";
                } else if (i == 11) {
                    nombre = "T10-ANTIGUOS";
                } else if (i == 12) {
                    nombre = "TA-ELVIRA";
                } else if (i == 13) {
                    nombre = "TB-IREANA";
                } else if (i == 14) {
                    nombre = "NN";
                } else {
                    nombre = "NN";
                }

                if (i == 14 || i == 15) {
                    $(".tablaDetalleEtiqueta").append(
                        '<tr class="detalleEtq" >' +
                            '<td style="background-color:#3c8dbc;border-radius:50px;width:80px;text-align:center;color:white">' +
                            nombre +
                            " </td>" +
                            '<th style="width:20px"></th>' +
                            '<td style="width:10px" > <input type="number" min="0" class="form-control input-sm" name="taller' +
                            i +
                            '" id="etiqueta' +
                            i +
                            '" value="' +
                            respuesta["taller" + i + ""] +
                            '" disabled > </td>' +
                            "</tr>"
                    );
                } else {
                    $(".tablaDetalleEtiqueta").append(
                        '<tr class="detalleEtq"> ' +
                            '<td style="background-color:#3c8dbc;border-radius:50px;width:80px;text-align:center;color:white">' +
                            nombre +
                            " </td>" +
                            '<th style="width:20px"></th>' +
                            '<td style="width:10px" > <input type="number" min="0" class="form-control input-sm" name="taller' +
                            i +
                            '" id="etiqueta' +
                            i +
                            '" value="' +
                            respuesta["taller" + i + ""] +
                            '"  > </td>' +
                            "</tr>"
                    );
                }
            }
        },
    });
});

$(".box").on("click", ".btnReporteServicios", function () {
    window.location = "vistas/reportes_excel/rpt_servicios.php";
});

$(".box").on("click", ".btnReporteHistorialServicios", function () {
    var anio = $("#anioHistorialServicios").val();
    if (!anio) {
        anio = new Date().getFullYear();
    }
    window.location =
        "vistas/reportes_excel/rpt_historial_servicios.php?anio=" + anio;
});

$(".box").on("click", ".btnReportePendienteRetorno", function () {
    window.location = "vistas/reportes_excel/rpt_pendiente_retorno_servicios.php";
});
