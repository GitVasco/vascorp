/*
 * cargamos la tabla para SUBLIMADOS
 */
if (localStorage.getItem("capturarRango27") != null) {
    $("#daterange-btnFactura span").html(
        localStorage.getItem("capturarRango27")
    );
    cargarTablaSublimado(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
    $(".btnReporteSublimados").attr(
        "fechaInicial",
        localStorage.getItem("fechaInicial")
    );
    $(".btnReporteSublimados").attr(
        "fechaFinal",
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnFactura span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaSublimado(null, null);
}

function cargarTablaSublimado(fechaInicial, fechaFinal) {
    $(".tablaSublimados").DataTable({
        ajax:
            "ajax/produccion/tabla-sublimados.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
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
}
/*=============================================
EDITAR SUBLIMADO
=============================================*/
$(".tablaSublimados").on("click", ".btnEditarSublimado", function () {
    var idSublimado = $(this).attr("idSublimado");

    var datos = new FormData();
    datos.append("idSublimado", idSublimado);

    $.ajax({
        url: "ajax/procedimientos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idSublimado").val(respuesta["id"]);
            $("#editarModeloSublimado").val(respuesta["modelo"]);
            $("#editarModeloSublimado2").val(
                respuesta["modelo"] + " - " + respuesta["nombre"]
            );
            $("#editarColorModelo").val(respuesta["color_modelo"]);
            $("#editarColorModelo2").val(
                respuesta["color_modelo"] + " - " + respuesta["nom_color"]
            );
            $("#editarMateriaSublimado").val(respuesta["materia_prima"]);
            $("#editarMateriaSublimado2").val(
                respuesta["materia_prima"] + " - " + respuesta["descripcion"]
            );
            $("#editarCorteSublimado").val(respuesta["cod_corte"]);
            $("#editarFechaInicioSub").val(respuesta["fecha_inicio"]);
            $("#editarFechaFinSub").val(respuesta["fecha_fin"]);
            $("#editarCantidadSub").val(respuesta["cantidad"]);
        },
    });
});

/*=============================================
ELIMINAR SUBLIMADO
=============================================*/
$(".tablaSublimados").on("click", ".btnEliminarSublimado", function () {
    var idSublimado = $(this).attr("idSublimado");

    swal({
        title: "¿Está seguro de borrar el sublimado?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar sublimado!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=sublimados&idSublimado=" + idSublimado;
        }
    });
});

/*=============================================
RANGO DE FECHAS SUBLIMADOS
=============================================*/

$("#daterange-btnSublimado").daterangepicker(
    {
        cancelClass: "CancelarSublimado",
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
        $("#daterange-btnSublimado span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango27 = $("#daterange-btnSublimado span").html();

        localStorage.setItem("capturarRango27", capturarRango27);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        $(".btnReporteSublimados").attr("fechaInicial", fechaInicial);
        $(".btnReporteSublimados").attr("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaSublimados").DataTable().destroy();
        cargarTablaSublimado(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarSublimado").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango27");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "sublimados";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "sublimados") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;
            localStorage.setItem("capturarRango27", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            $(".btnReporteSublimados").attr("fechaInicial", fechaInicial);
            $(".btnReporteSublimados").attr("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaSublimados").DataTable().destroy();
            cargarTablaSublimado(fechaInicial, fechaFinal);
        }
    }
});

$("#nuevoModeloSublimado").change(function () {
    var modelo = $(this).val();

    var datos = new FormData();
    datos.append("modelo3", modelo);

    $.ajax({
        url: "ajax/modelos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoColorModelo").find("option").remove();
            $("#nuevoColorModelo").append(
                '<option value="">Seleccionar color modelo</option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevoColorModelo").append(
                    "<option value='" +
                        respuesta[i]["cod_color"] +
                        "'>" +
                        respuesta[i]["cod_color"] +
                        " - " +
                        respuesta[i]["color"] +
                        "</option>"
                );
            }
            $("#nuevoColorModelo").selectpicker("refresh");
        },
    });
});

$("#nuevoColorModelo").change(function () {
    var color = $(this).val();
    var modelo = $("#nuevoModeloSublimado").val();
    var articulo = modelo + color;
    var datos = new FormData();
    datos.append("articuloSublimado", articulo);

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
            $("#nuevaMateriaSublimado").find("option").remove();
            $("#nuevaMateriaSublimado").append(
                '<option value="">Seleccionar materia prima</option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevaMateriaSublimado").append(
                    "<option value='" +
                        respuesta[i]["mat_pri"] +
                        "'>" +
                        respuesta[i]["mat_pri"] +
                        " - " +
                        respuesta[i]["descripcion"] +
                        "</option>"
                );
            }
            $("#nuevaMateriaSublimado").selectpicker("refresh");
        },
    });

    var datos2 = new FormData();
    datos2.append("modeloSublimado", modelo);
    datos2.append("colorSublimado", color);

    $.ajax({
        url: "ajax/cortes.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            console.log(respuesta);
            $("#nuevoCorteSublimado").find("option").remove();
            $("#nuevoCorteSublimado").append(
                '<option value="">Seleccionar corte </option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevoCorteSublimado").append(
                    "<option value='" +
                        respuesta[i]["guia"] +
                        "'>" +
                        respuesta[i]["guia"] +
                        " - " +
                        respuesta[i]["fecha"] +
                        "</option>"
                );
            }
            $("#nuevoCorteSublimado").selectpicker("refresh");
        },
    });
});

$("#nuevaFechaInicioSub").keyup(function () {
    $("#nuevaFechaFinSub").prop("readonly", false);
});

$("#nuevaFechaFinSub").keyup(function () {
    var fin = $(this).val();
    var inicio = $("#nuevaFechaInicioSub").val();

    var fechaInicio = new Date(inicio);
    var fechaFin = new Date(fin);
    if (fechaInicio > fechaFin) {
        if ($(".msgError").length == 0) {
            $("#nuevaFechaFinSub")
                .parent()
                .after(
                    '<div class="alert alert-danger alert-dismissable msgError" id="mensajeError">' +
                        '<a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>' +
                        "<strong>Error!</strong> La fecha final tiene que ser mayor a la de inicio." +
                        "</div>"
                );
        } else {
            $(".msgError").remove();
        }
    }
});

$("#editarFechaInicioSub").keyup(function () {
    $("#editarFechaFinSub").prop("readonly", false);
});

$("#editarFechaFinSub").keyup(function () {
    var fin = $(this).val();
    var inicio = $("#editarFechaInicioSub").val();

    var fechaInicio = new Date(inicio);
    var fechaFin = new Date(fin);
    if (fechaInicio > fechaFin) {
        if ($(".msgError").length == 0) {
            $("#editarFechaFinSub")
                .parent()
                .after(
                    '<div class="alert alert-danger alert-dismissable msgError" id="mensajeError">' +
                        '<a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>' +
                        "<strong>Error!</strong> La fecha final tiene que ser mayor a la de inicio." +
                        "</div>"
                );
        } else {
            $(".msgError").remove();
        }
    }
});

//Reporte de Para
$(".box").on("click", ".btnReporteSublimados", function () {
    var inicio = $(this).attr("fechaInicial");
    var fin = $(this).attr("fechaFinal");
    window.location =
        "vistas/reportes_excel/rpt_sublimados.php?inicio=" +
        inicio +
        "&fin=" +
        fin;
});

// Prehormado
// capturamos el evento change del select con id tipoPrehormado
// Función para cargar los artículos basada en el tipo de prehormado seleccionado
function cargarArticulos(tipoPrehormado, select) {
    var datos = new FormData();
    datos.append("tipoPrehormado", tipoPrehormado);

    $.ajax({
        url: "ajax/prehormado.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#articulosPrehormado").find("option").remove();
            $("#articulosPrehormado").append(
                '<option value="">Seleccionar articulo</option>'
            );

            let familias = [
                "COP009",
                "COP010",
                "COP011",
                "COP012",
                "COP013",
                "COP014",
                "COP015",
                "COP016",
                "COP017",
                "COP018",
                "COP019",
                "COP020",
                "COP021",
                "COP022",
                "COP023",
                "COP024",
                "COP025",
            ];

            for (let i = 0; i < respuesta.length; i++) {
                const item = respuesta[i];
                if (tipoPrehormado != "02" || familias.includes(item.fampro)) {
                    const codigo =
                        tipoPrehormado == "01" ? item.articulo : item.codfab;
                    const articulo =
                        tipoPrehormado == "01"
                            ? `${item.modelo} - ${item.nombre} - ${item.color} - ${item.talla}`
                            : `${item.codpro} - ${item.codfab} - ${item.despro} - ${item.color} - ${item.talla}`;

                    // Verificar si el artículo actual debe ser marcado como seleccionado
                    const isSelected = codigo === select ? "selected" : "";

                    // Añadir el artículo al select, marcando como seleccionado si es necesario
                    $("#articulosPrehormado").append(
                        `<option value='${codigo}' ${isSelected}>${articulo}</option>`
                    );
                }
            }

            $("#articulosPrehormado").selectpicker("refresh");
        },
    });
}

// // Evento change para el select de tipo de prehormado
// $("#tipoPrehormado").change(function () {
//     var articulo = $("#articulo").val();
//     var select = articulo ? articulo : "";

//     cargarArticulos($(this).val(), select);
// });

// // Cargar artículos al cargar la página, si ya hay un tipo de prehormado seleccionado
// $(document).ready(function () {
//     var tipoPrehormadoInicial = $("#tipoPrehormado").val();
//     var articulo = $("#articulo").val();
//     var select = articulo ? articulo : "";
//     if (tipoPrehormadoInicial) {
//         cargarArticulos(tipoPrehormadoInicial, select);
//     }
// });

// // tabla prehormado
// function cargarTablaPrehormado() {
//     $(".tablaPrehormado").DataTable({
//         ajax: "ajax/produccion/tabla-prehormado.ajax.php",
//         deferRender: true,
//         retrieve: true,
//         processing: true,
//         order: [[0, "desc"]],
//         pageLength: 20,
//         lengthMenu: [
//             [20, 40, 60, -1],
//             [20, 40, 60, "Todos"],
//         ],
//         language: {
//             sProcessing: "Procesando...",
//             sLengthMenu: "Mostrar _MENU_ registros",
//             sZeroRecords: "No se encontraron resultados",
//             sEmptyTable: "Ningún dato disponible en esta tabla",
//             sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
//             sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
//             sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
//             sInfoPostFix: "",
//             sSearch: "Buscar:",
//             sUrl: "",
//             sInfoThousands: ",",
//             sLoadingRecords: "Cargando...",
//             oPaginate: {
//                 sFirst: "Primero",
//                 sLast: "Último",
//                 sNext: "Siguiente",
//                 sPrevious: "Anterior",
//             },
//             oAria: {
//                 sSortAscending:
//                     ": Activar para ordenar la columna de manera ascendente",
//                 sSortDescending:
//                     ": Activar para ordenar la columna de manera descendente",
//             },
//         },
//     });
// }

// // cargar tabla prehormado
// cargarTablaPrehormado();

$(".tablaPrehormado tbody").on(
    "click",
    "button.btnEliminarPrehormado",
    function () {
        const prehormado = $(this).attr("idPrehormado");
        swal({
            title: "¿Está seguro de borrar el registro?",
            text: "¡Si no lo está puede cancelar la acción!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, borrar!",
        }).then(function (result) {
            if (result.value) {
                const datos = new FormData();
                datos.append("eliminarPrehormado", prehormado);
                $.ajax({
                    url: "ajax/prehormado.ajax.php",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (respuesta) {
                        if (respuesta == "ok") {
                            Command: toastr["success"](
                                "El registro ha sido eliminado correctamente"
                            );
                            $(".tablaPrehormado").DataTable().ajax.reload();
                        }
                    },
                });
            }
        });
    }
);
