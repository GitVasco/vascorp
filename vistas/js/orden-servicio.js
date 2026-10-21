/*=============================================
CARGAR LA TABLA DINÁMICA DE NOTAS DE SALIDAS
=============================================*/

if (localStorage.getItem("capturarRango33") != null) {
    $("#daterange-btnOrdenServicio span").html(
        localStorage.getItem("capturarRango33")
    );
    cargarTablaOrdenServicio(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnOrdenServicio span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaOrdenServicio(null, null);
}

/*
 * TABLA PARA ORDEN DE SERVICIO
 */
function cargarTablaOrdenServicio(fechaInicial, fechaFinal) {
    $(".tablaOrdenesServicios").DataTable({
        ajax:
            "ajax/materiaprima/tabla-orden-servicio.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[2, "desc"]],
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
RANGO DE FECHAS PARA ORDEN DE SERVICIO
=============================================*/

$("#daterange-btnOrdenServicio").daterangepicker(
    {
        cancelClass: "CancelarOrdenServicio",
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
        $("#daterange-btnOrdenServicio span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango33 = $("#daterange-btnOrdenServicio span").html();

        localStorage.setItem("capturarRango33", capturarRango33);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        $(".btnReporteOServicioGeneral").attr("inicio", fechaInicial);
        $(".btnReporteOServicioGeneral").attr("fin", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaOrdenesServicios").DataTable().destroy();
        cargarTablaOrdenServicio(fechaInicial, fechaFinal);
    }
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarOrdenServicio").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango33");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "orden-servicio";
    }
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "orden-servicio") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango33", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            $(".btnReporteOServicioGeneral").attr("inicio", fechaInicial);
            $(".btnReporteOServicioGeneral").attr("fin", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaOrdenesServicios").DataTable().destroy();
            cargarTablaOrdenServicio(fechaInicial, fechaFinal);
        }
    }
});

// TABLA MATERIA ORDENES SERVICIOS

$(".tablaMateriaOrdenesServicios").DataTable({
    ajax:
        "ajax/materiaprima/tabla-materia-orden-servicio.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
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

// TABLA MATERIA ORDENES SERVICIOS DESTINO

$(".tablaMateriaServicioDestino").DataTable({
    ajax:
        "ajax/materiaprima/tabla-materiadestino-orden-servicio.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
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
var c = 0;
$(".tablaMateriaOrdenesServicios tbody").on(
    "click",
    "button.agregarMateriaServicio",
    function () {
        var idMateriaServicio = $(this).attr("idMateriaServicio");

        /* console.log("idProducto", idProducto); */

        // $(this).removeClass("btn-primary agregarMateriaServicio");

        // $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("idMateriaPrima2", idMateriaServicio);

        $.ajax({
            url: "ajax/materiaprima.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                // console.log(respuesta);
                c = c + 1;
                var codpro = respuesta["codpro"];
                var color = respuesta["color"];
                var unidad = respuesta["unidad"];
                var descripcion = respuesta["descripcion"];
                var stock = respuesta["stock"];

                /*=============================================
      EVITAR AGREGAR PRODUTO CUANDO EL STOCK ESTÁ EN CERO
      =============================================*/

                if (stock == 0) {
                    swal({
                        title: "No hay stock disponible",
                        type: "error",
                        confirmButtonText: "¡Cerrar!",
                    });

                    $(
                        "button[idMateriaServicio='" + idMateriaServicio + "']"
                    ).addClass("btn-primary agregarMateriaServicio");

                    return;
                }

                $(".nuevaMateriaServicio").append(
                    '<div class="row" style="padding:1px 15px">' +
                        "<!-- Descripción del producto -->" +
                        '<div class="col-xs-1" style="padding-right:0px">' +
                        '<input type="text" class="form-control input-sm nuevoCodigoPro" idMateriaNota="' +
                        codpro +
                        '" name="agregarProducto" value="' +
                        codpro +
                        '"  readonly>' +
                        "</div>" +
                        '<div class="col-xs-3" >' +
                        '<input type="text" class="form-control input-sm nuevaDescripcionMateria"  name="nuevaDescripcionMateria" value="' +
                        descripcion +
                        '"  readonly>' +
                        "</div>" +
                        '<div class="col-xs-1" >' +
                        '<input type="text" class="form-control input-sm nuevoColor"  name="nuevoColor" value="' +
                        color +
                        '"  readonly>' +
                        "</div>" +
                        '<div class="col-xs-1 destinoCodigo">' +
                        '<input type="text" class="form-control input-sm nuevoCodigoPro2 modmpOSDestino"  name="nuevoCodigoPro2"  id="codigo' +
                        codpro +
                        c +
                        '" origen = "' +
                        codpro +
                        c +
                        '" autocomplete="off">' +
                        "</div>" +
                        '<div class="col-xs-3 destinoDescripcion" >' +
                        '<input type="text" class="form-control input-sm nuevaDescripcionMateria2"  name="nuevaDescripcionMateria2"  id="descripcion' +
                        codpro +
                        c +
                        '" readonly>' +
                        "</div>" +
                        '<div class="col-xs-1 destinoColor" >' +
                        '<input type="text" class="form-control input-sm nuevoColor2"  name="nuevoColor2"  id="color' +
                        codpro +
                        c +
                        '"readonly>' +
                        "</div>" +
                        '<div class="col-xs-1" >' +
                        '<input type="text" class="form-control input-sm nuevaUnidad"  name="nuevaUnidad" value="' +
                        unidad +
                        '" readonly>' +
                        "</div>" +
                        "<!-- Cantidad del producto -->" +
                        '<div class="col-xs-1">' +
                        '<div class="input-group">' +
                        '<input type="number" step="any" class="form-control input-sm nuevaCantidadMateria" name="nuevaCantidadMateria" min="1" value="1" stock="' +
                        stock +
                        '" nuevoStock="' +
                        Number(stock - 1) +
                        '" required>' +
                        '<span class="input-group-addon"  style="padding: 3px 6px"><button type="button" class="btn btn-danger btn-xs quitarMateriaServicio" idMateriaServicio="' +
                        idMateriaServicio +
                        '"><i class="fa fa-times"></i></button></span>' +
                        "</div>" +
                        "</div>" +
                        "</div>"
                );

                // AGRUPAR MATERIAS EN FORMATO JSON

                listarMateriaServicios();
            },
        });
    }
);

/*=============================================
QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
=============================================*/

var idQuitarMateriaServicio = [];

localStorage.removeItem("quitarMateriaServicio");

$(".formularioOrdenServicio").on(
    "click",
    "button.quitarMateriaServicio",
    function () {
        /* console.log("boton"); */

        $(this).parent().parent().parent().parent().remove();

        var idMateriaServicio = $(this).attr("idMateriaServicio");

        /*=============================================
  ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
  =============================================*/

        if (localStorage.getItem("quitarMateriaServicio") == null) {
            idQuitarMateriaServicio = [];
        } else {
            idQuitarMateriaServicio.concat(
                localStorage.getItem("quitarMateriaServicio")
            );
        }

        idQuitarMateriaServicio.push({
            idMateriaServicio: idMateriaServicio,
        });

        localStorage.setItem(
            "quitarMateriaServicio",
            JSON.stringify(idQuitarMateriaServicio)
        );

        $(
            "button.recuperarBoton[idMateriaServicio='" +
                idMateriaServicio +
                "']"
        ).removeClass("btn-default");

        $(
            "button.recuperarBoton[idMateriaServicio='" +
                idMateriaServicio +
                "']"
        ).addClass("btn-primary agregarMateriaServicio");

        // AGRUPAR MATERIAS EN FORMATO JSON

        listarMateriaServicios();
    }
);

/*=============================================
MODIFICAR LA CANTIDAD
=============================================*/

$(".formularioOrdenServicio").on(
    "keyup",
    "input.nuevaCantidadMateria",
    function () {
        var nuevoStock = Number($(this).attr("stock")) - $(this).val();

        $(this).attr("nuevoStock", nuevoStock);

        if (Number($(this).val()) > Number($(this).attr("stock"))) {
            /*=============================================
    SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
    =============================================*/

            $(this).val(1);

            swal({
                title: "La cantidad supera el Stock",
                text: "¡Sólo hay " + $(this).attr("stock") + " unidades!",
                type: "error",
                confirmButtonText: "¡Cerrar!",
            });

            return;
        }

        // AGRUPAR MATERIAS EN FORMATO JSON

        listarMateriaServicios();
    }
);

/*=============================================
MODIFICAR MATERIA DESTINO
=============================================*/
$(".formularioOrdenServicio").on("click", "input.nuevoCodigoPro2", function () {
    var origen = $(this).attr("origen");
    $("#codigoOrigen").val(origen);
    $("#ModalMPOrdenServicioDestino").modal("show");
    // $("#ModalMPOrdenServicioDestino").modal('hide');
});

/*=============================================
MODIFICAR MATERIA DESTINO
=============================================*/
$(".tablaMateriaServicioDestino").on(
    "click",
    "button.agregarMateriaDestinoServicio",
    function () {
        var origen = $("#codigoOrigen").val();

        // console.log(origen);
        var codigo = $(this).attr("codigo");
        var descripcion = $(this).attr("descripcion");
        var color = $(this).attr("color");

        $("#codigo" + origen).val(codigo);
        $("#descripcion" + origen).val(descripcion);
        $("#color" + origen).val(color);
        $("#ModalMPOrdenServicioDestino").modal("hide");

        listarMateriaServicios();
    }
);

/*=============================================
LISTAR TODOS LOS PRODUCTOS
=============================================*/

function listarMateriaServicios() {
    var listarMateriaServicios = [];

    var descripcion = $(".nuevaDescripcionMateria");

    var cantidad = $(".nuevaCantidadMateria");

    var codpro = $(".nuevoCodigoPro");

    var color = $(".nuevoColor");

    var descripcion_des = $(".nuevaDescripcionMateria2");

    var codpro_des = $(".nuevoCodigoPro2");

    var color_des = $(".nuevoColor2");

    for (var i = 0; i < descripcion.length; i++) {
        listarMateriaServicios.push({
            id: $(codpro[i]).val(),
            descripcion: $(descripcion[i]).val(),
            cantidad: $(cantidad[i]).val(),
            color: $(color[i]).val(),
            stock: $(cantidad[i]).attr("nuevoStock"),
            id_des: $(codpro_des[i]).val(),
            descripcion_des: $(descripcion_des[i]).val(),
            color_des: $(color_des[i]).val(),
        });
    }

    // console.log("listarMateriaServicios", JSON.stringify(listarMateriaServicios));

    $("#listarMateriaServicios").val(JSON.stringify(listarMateriaServicios));
}

/*
 * VISUALIZAR DETALLE DEL CORTE
 */
$(".tablaOrdenesServicios").on(
    "click",
    ".btnVisualizarOrdenServicio",
    function () {
        var idOrdenServicio = $(this).attr("idOrdenServicio");
        // console.log(idOrdenServicio);

        var datos = new FormData();
        datos.append("idOrdenServicio", idOrdenServicio);

        $.ajax({
            url: "ajax/orden-servicio.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                // console.log(respuesta);
                $("#codigo").val(idOrdenServicio);
                $("#emision").val(respuesta["FecEmi"]);
                $("#entrega").val(respuesta["FecEnt"]);
                $("#proveedor").val("000097");
                $("#razonsocial").val("ELASTICOS VASCO");
                $("#ruc").val("20551240356");
                $("#estado").val(respuesta["EstOS"]);
                $("#descontado").val(respuesta["DesStk"]);
                $("#observacion").val(respuesta["ObsOs"]);
            },
        });

        cargarDetalleOrdenServicio(idOrdenServicio);
    }
);

function cargarDetalleOrdenServicio(idOrdenServicioDetalle) {
    var datosDOC = new FormData();
    datosDOC.append("idOrdenServicioDetalle", idOrdenServicioDetalle);

    $.ajax({
        url: "ajax/orden-servicio.ajax.php",
        method: "POST",
        data: datosDOC,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            $(".detalleOS").remove();

            for (var id of respuestaDetalle) {
                var cerrado = String(id.EstOS || "").toUpperCase() === "CER";
                var botonCerrar = cerrado
                    ? "<button type='button' class='btn btn-xs' disabled title='Ítem cerrado'><i class='fa fa-times-circle'></i> Cerrar</button>"
                    : "<button type='button' class='btn btn-xs btnCerrarDetalleOS' nro='" +
                      id.Nro +
                      "' item='" +
                      id.Item +
                      "' title='Cerrar ítem'><i class='fa fa-times-circle'></i> Cerrar</button>";

                $(".tablaDetalleOrdenServicio").append(
                    '<tr class="detalleOS">' +
                        "<td >" +
                        id.Item +
                        " </td>" +
                        "<td >" +
                        id.CodProOrigen +
                        " </td>" +
                        "<td >" +
                        id.DesProOrigen +
                        " </td>" +
                        "<td >" +
                        id.CodProDestino +
                        " </td>" +
                        "<td >" +
                        id.Descripcion +
                        " </td>" +
                        "<td >" +
                        id.Color2 +
                        " </td>" +
                        "<td >" +
                        id.Unidad +
                        " </td>" +
                        "<td >" +
                        id.CantidadIni +
                        "</td>" +
                        "<td >" +
                        id.Despacho +
                        "</td>" +
                        "<td >" +
                        id.Saldo +
                        "</td>" +
                        "<td >" +
                        id.EstOS +
                        "</td>" +
                        "<td >" +
                        id.EstadoDet +
                        "</td>" +
                        "<td >" +
                        botonCerrar +
                        "</td>" +
                        "</tr>"
                );
            }
        },
    });
}

$(".tablaDetalleOrdenServicio").on("click", ".btnCerrarDetalleOS", function () {
    var nro = $(this).attr("nro");
    var item = $(this).attr("item");

    swal({
        title: "¿Cerrar el ítem " + item + " de la orden " + nro + "?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, cerrar ítem!",
    }).then(function (result) {
        if (result.value) {
            var datos = new FormData();
            datos.append("cerrarItemNro", nro);
            datos.append("cerrarItem", item);

            $.ajax({
                url: "ajax/orden-servicio.ajax.php",
                type: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                success: function (respuesta) {
                    respuesta = $.trim(respuesta);

                    if (respuesta !== "ok" && respuesta !== "ok-orden") {
                        swal({
                            type: "error",
                            title: "Error",
                            text: "No se pudo cerrar el ítem.",
                            confirmButtonText: "Cerrar",
                        });
                        return;
                    }

                    cargarDetalleOrdenServicio(nro);

                    if (respuesta === "ok-orden") {
                        $("#estado").val("CERRADA");
                        if (
                            $.fn.DataTable.isDataTable(".tablaOrdenesServicios")
                        ) {
                            $(".tablaOrdenesServicios")
                                .DataTable()
                                .ajax.reload(null, false);
                        }
                    }

                    swal({
                        type: "success",
                        title: "¡Ok!",
                        text:
                            respuesta === "ok-orden"
                                ? "El ítem se cerró y la orden quedó cerrada porque ya no tiene ítems abiertos."
                                : "El ítem fue cerrado con éxito.",
                        confirmButtonText: "Cerrar",
                    });
                },
            });
        }
    });
});

$(".tablaOrdenesServicios").on("click", ".btnCerrarOServicio", function () {
    var idCerrar = $(this).attr("idOrdenServicio");

    // Capturamos el id de la orden de servicio
    swal({
        title: "¿Está seguro de cerrar la orden de servicio " + idCerrar + "?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, cerrar orden de servicio!",
    }).then(function (result) {
        if (result.value) {
            var datos = new FormData();
            datos.append("cerrarId", idCerrar);
            $.ajax({
                url: "ajax/orden-servicio.ajax.php",
                type: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                success: function (respuesta) {
                    console.log(respuesta);
                    swal({
                        type: "success",
                        title: "¡Ok!",
                        text: "¡La orden de servicio fue cerrada con éxito!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false,
                    }).then((result) => {
                        if (result.value) {
                            window.location = "orden-servicio";
                        }
                    });
                },
            });
        }
    });
});

//REPORTE DE ORDEN DE SERVICIO DE CADA UNO
$(".tablaOrdenesServicios").on(
    "click",
    ".btnDetalleReporteOrdenServicio",
    function () {
        var idOrdenServicio = $(this).attr("idOrdenServicio");
        // console.log(idOrdenCompra);
        window.location =
            "vistas/reportes_excel/rpt_orden_servicio.php?idOrdenServicio=" +
            idOrdenServicio;
    }
);

$(".box").on("click", ".btnReporteOServicioGeneral", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");

    // Obtener la fecha actual
    var fechaActual = new Date();
    var anoActual = fechaActual.getFullYear();

    // Formatear el primer día del año
    var primerDiaAno = anoActual + "-01-01";

    // Formatear la fecha actual en formato YYYY-MM-DD
    var mes = ("0" + (fechaActual.getMonth() + 1)).slice(-2);
    var dia = ("0" + fechaActual.getDate()).slice(-2);
    var fechaActualFormateada = anoActual + "-" + mes + "-" + dia;

    // Asignar valores por defecto si no están definidos
    inicio = inicio ? inicio : primerDiaAno;
    fin = fin ? fin : fechaActualFormateada;

    window.location =
        "vistas/reportes_excel/rpt_orden_servicio_general.php?inicio=" +
        inicio +
        "&fin=" +
        fin;
});

/*
 * MATERIA PRIMA EN OC PENDIENTES DE ENTREGA
 */
$(".tablaMpPendienteOS").DataTable({
    ajax:
        "ajax/materiaprima/tabla-mp-os-pendiente.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[1, "desc"]],
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

$(".box").on("click", ".btnReporteOServicioPendiente", function () {
    window.location = "vistas/reportes_excel/rpt_orden_servicio_pendiente.php";
});
