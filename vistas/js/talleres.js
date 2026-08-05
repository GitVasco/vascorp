/*
 * CARGAR TABLA TALLERES EN GENERAL
 */
// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("capturarRango5") != null) {
    $("#daterange-btnTaller span").html(localStorage.getItem("capturarRango5"));
    cargarTablaTalleres(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnTaller span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaTalleres(null, null);
}

function cargarTablaTalleres(fechaInicial, fechaFinal) {
    $(".tablaTalleresG").DataTable({
        ajax:
            "ajax/produccion/tabla-talleresGeneral.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
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
        createdRow: function (row, data, index) {
            if (
                data[10] ==
                "<button class='btn btn-info btn-xs btnCerrarTaller' codigoTallerG='" +
                    data[1] +
                    "' estadoTaller='4'>Generado</button>"
            ) {
                $("td", row).css({
                    "background-color": "#E4F7F7",
                    color: "black",
                });
            } else if (
                data[10] ==
                "<span style='font-size:85%' class='label label-success'>Terminado</span>"
            ) {
                $("td", row).css({
                    "background-color": "#E4F7E5",
                    color: "black",
                });
            } else {
                $("td", row).css({
                    "background-color": "#F7E4E9",
                    color: "black",
                });
            }
        },
    });
}

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("capturarRango8") != null) {
    $("#daterange-btnTallerT span").html(
        localStorage.getItem("capturarRango8")
    );
    cargarTablaTalleresTerminados(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnTallerT span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaTalleresTerminados(null, null);
}

/*
 * CARGAR TABLA TALLERES EN TERMINADO
 */
function cargarTablaTalleresTerminados(fechaInicial, fechaFinal) {
    $(".tablaTalleresT").DataTable({
        ajax:
            "ajax/produccion/tabla-talleresTerminado.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
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

/* 
! TABLA PARA LOS PRODUCTOS EN PROCESO
*/
$(".tablaTallerP").DataTable({
    ajax: "ajax/produccion/tabla-talleresP.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    searching: false,
    paging: false,
    ordering: false,
    info: false,
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
! TABLA PARA LOS PRODUCTOS EN TERMINADO
*/
$(".tablaTallerT").DataTable({
    ajax: "ajax/produccion/tabla-talleresT.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    searching: false,
    paging: false,
    ordering: false,
    info: false,
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
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaTalleresG").on("click", ".btnImprimirTicket", function () {
    var ultimo = $(this).attr("ultimo");

    var modelo = $(this).attr("modelo");

    var nombre = $(this).attr("nombre");

    var color = $(this).attr("color");

    var talla = $(this).attr("talla");

    var cant_taller = $(this).attr("cant_taller");

    var cod_operacion = $(this).attr("cod_operacion");

    var nom_operacion = $(this).attr("nom_operacion");

    window.open(
        "vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=" +
            ultimo +
            "&modelo=" +
            modelo +
            "&nombre=" +
            nombre +
            "&color=" +
            color +
            "&talla=" +
            talla +
            "&cant_taller=" +
            cant_taller +
            "&cod_operacion=" +
            cod_operacion +
            "&nom_operacion=" +
            nom_operacion,
        "_blank"
    );
});

$(".tablaTalleresGenerado").on("click", ".btnImprimirTicket", function () {
    var ultimo = $(this).attr("ultimo");

    var modelo = $(this).attr("modelo");

    var nombre = $(this).attr("nombre");

    var color = $(this).attr("color");

    var talla = $(this).attr("talla");

    var cant_taller = $(this).attr("cant_taller");

    var cod_operacion = $(this).attr("cod_operacion");

    var nom_operacion = $(this).attr("nom_operacion");

    window.open(
        "vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=" +
            ultimo +
            "&modelo=" +
            modelo +
            "&nombre=" +
            nombre +
            "&color=" +
            color +
            "&talla=" +
            talla +
            "&cant_taller=" +
            cant_taller +
            "&cod_operacion=" +
            cod_operacion +
            "&nom_operacion=" +
            nom_operacion,
        "_blank"
    );
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaTalleresG").on("click", ".btnEditarTallerG", function () {
    var idTaller = $(this).attr("idTaller");
    var datos = new FormData();
    datos.append("idTaller", idTaller);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#editarCodigo").val(respuesta["id_cabecera"]);
            $("#editarArticulo").val(respuesta["articulo"]);
            $("#cantidad").val(respuesta["cantidad"]);
            $("#editarCodOperacion").val(respuesta["cod_operacion"]);
            $("#editarCOT").val(respuesta["cod_operacion"]);
            $("#editarOT").val(respuesta["nom_operacion"]);
            $("#editarCantidad2").val(respuesta["cantidad"]);
            $("#editarTaller").val(respuesta["id"]);
            $("#editarBarra").val(respuesta["codigo"]);
            $("#editarTalla").val(respuesta["talla"]);
            $("#editarMarca").val(respuesta["marca"]);
            $("#editarColor").val(respuesta["color"]);
            $("#editarNombre").val(respuesta["nombre"]);
            $("#editarModelo").val(respuesta["modelo"]);
        },
    });
});

/*=============================================
RANGO DE FECHAS
=============================================*/
moment.locale("es");
$("#daterange-btnTaller").daterangepicker(
    {
        cancelClass: "CancelarTaller",
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
        $("#daterange-btnTaller span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango5 = $("#daterange-btnTaller span").html();

        localStorage.setItem("capturarRango5", capturarRango5);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaTalleresG").DataTable().destroy();
        cargarTablaTalleres(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarTaller").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango5");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "en-taller";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "en-taller") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango5", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaTalleresG").DataTable().destroy();
            cargarTablaTalleres(fechaInicial, fechaFinal);
        }
    }
});

//Reporte de tALLERES
$(".box").on("click", ".btnReporteTalleres", function () {
    window.location = "vistas/reportes_excel/rpt_talleres.php";
});
/* 
! PRODUCCION DE TRUSAS


/* 
* VEMOS SI LOCAL STORAGE TRAE ALGO
*/
if (localStorage.getItem("capturarRango13") != null) {
    $("#daterange-btnTrusas span").html(
        localStorage.getItem("capturarRango13")
    );
    cargarTablaProduccionTrusas(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
    $(".btnReporteProduccionTrusas").attr(
        "fechaInicial",
        localStorage.getItem("fechaInicial")
    );
    $(".btnReporteProduccionTrusas").attr(
        "fechaFinal",
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnTrusas span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaProduccionTrusas(null, null);
}

/*
 * TABLA PARA PRODUCCION TRUSAS
 */
function cargarTablaProduccionTrusas(fechaInicial, fechaFinal) {
    $(".tablaProduccionTrusas").DataTable({
        ajax:
            "ajax/produccion/tabla-producciontrusas.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
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
        drawCallback: function () {
            var api = this.api();
            $(api.column(18).footer()).html(
                api.column(18, { page: "current" }).data().sum().toFixed(2)
            );
            $(api.column(19).footer()).html(
                api.column(19, { page: "current" }).data().sum().toFixed(2)
            );
        },
    });
}

/*=============================================
RANGO DE FECHAS
=============================================*/

$("#daterange-btnTrusas").daterangepicker(
    {
        cancelClass: "CancelarTrusas",
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
        $("#daterange-btnTrusas span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango13 = $("#daterange-btnTrusas span").html();

        localStorage.setItem("capturarRango13", capturarRango13);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        $(".btnReporteProduccionTrusas").attr("fechaInicial", fechaInicial);
        $(".btnReporteProduccionTrusas").attr("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaProduccionTrusas").DataTable().destroy();
        cargarTablaProduccionTrusas(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarTrusas").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango13");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "produccion-trusas";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "produccion-trusas") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango13", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            $(".btnReporteProduccionTrusas").attr("fechaInicial", fechaInicial);
            $(".btnReporteProduccionTrusas").attr("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaProduccionTrusas").DataTable().destroy();
            cargarTablaProduccionTrusas(fechaInicial, fechaFinal);
        }
    }
});

/* 
! PRODUCCION DE BRASIER
*/
/*
 * BOTON ACEPTAR
 */

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */

if (localStorage.getItem("capturarRango14") != null) {
    $("#daterange-btnBrasieres span").html(
        localStorage.getItem("capturarRango14")
    );
    cargarTablaProduccionBrasier(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
    $(".btnReporteProduccionBrasier").attr(
        "fechaInicial",
        localStorage.getItem("fechaInicial")
    );
    $(".btnReporteProduccionBrasier").attr(
        "fechaFinal",
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnBrasieres span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaProduccionBrasier(null, null);
}

/*
 * TABLA PARA PRODUCCION Brasier
 */
function cargarTablaProduccionBrasier(fechaInicial, fechaFinal) {
    $(".tablaProduccionBrasier").DataTable({
        ajax:
            "ajax/produccion/tabla-produccionbrasier.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
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
        drawCallback: function () {
            var api = this.api();
            $(api.column(18).footer()).html(
                api.column(18, { page: "current" }).data().sum().toFixed(2)
            );
            $(api.column(19).footer()).html(
                api.column(19, { page: "current" }).data().sum().toFixed(2)
            );
        },
    });
}

/*=============================================
RANGO DE FECHAS
=============================================*/

$("#daterange-btnBrasieres").daterangepicker(
    {
        cancelClass: "CancelarBrasieres",
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
        $("#daterange-btnBrasieres span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango14 = $("#daterange-btnBrasieres span").html();

        localStorage.setItem("capturarRango14", capturarRango14);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        $(".btnReporteProduccionBrasier").attr("fechaInicial", fechaInicial);
        $(".btnReporteProduccionBrasier").attr("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaProduccionBrasier").DataTable().destroy();
        cargarTablaProduccionBrasier(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarBrasieres").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango14");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "produccion-brasier";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "produccion-brasier") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango14", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".btnReporteProduccionBrasier").attr(
                "fechaInicial",
                fechaInicial
            );
            $(".btnReporteProduccionBrasier").attr("fechaFinal", fechaFinal);
            $(".tablaProduccionBrasier").DataTable().destroy();
            cargarTablaProduccionBrasier(fechaInicial, fechaFinal);
        }
    }
});

/* 
! PRODUCCION DE VASCO
*/
/*
 * BOTON ACEPTAR
 */
$(".box").on("click", ".btnCargarVasco", function () {
    $(".tablaProduccionVasco").DataTable().destroy();

    var mesV = document.getElementById("mesV").value;
    //console.log(mesV);
    //$(".btnReporteSalida").attr("linea",mesV);
    localStorage.setItem("mesV", mesV);

    cargarTablaProduccionVasco(localStorage.getItem("mesV"));
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("mesV") != null) {
    cargarTablaProduccionVasco(localStorage.getItem("mesV"));
    //console.log("lleno");
} else {
    cargarTablaProduccionVasco(null);
    //console.log("vacio");
}

/*
 * TABLA PARA PRODUCCION Vasco
 */
function cargarTablaProduccionVasco(mesV) {
    $(".tablaProduccionVasco").DataTable({
        ajax:
            "ajax/produccion/tabla-produccionvasco.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&mesV=" +
            mesV,
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
}

$("#nuevoTalleres").change(function () {
    var ingreso = $(this).val();
    if ($(".tablaArticulosTalleres").length) {
        $(".tablaArticulosTalleres").DataTable().destroy();
        localStorage.setItem("sectorIngreso", ingreso);
        cargarTablaArticuloTalleres(localStorage.getItem("sectorIngreso"));
    } else {
        localStorage.setItem("sectorIngreso", ingreso);
    }
    var datos = new FormData();
    datos.append("idSector", ingreso);

    $.ajax({
        url: "ajax/sectores.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log("respuesta", respuesta);

            $("#nuevoTipoSector").val(respuesta["tipo"]);
        },
    });
    var datos2 = new FormData();
    datos2.append("ingreso", ingreso);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta2) {
            //   console.log(respuesta);
            if ($("#nuevoCodigo").length) {
                $("#nuevoCodigo").val(
                    ingreso + ("000" + respuesta2["ultimo_codigo"]).slice(-4)
                );
            }
        },
    });
});

$("#editarTalleres").change(function () {
    var ingreso = $(this).val();
    var datos2 = new FormData();
    datos2.append("ingreso", ingreso);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //   console.log(respuesta);
            $("#nuevoCodigo").val(
                ingreso + ("000" + respuesta["ultimo_codigo"]).slice(-4)
            );
        },
    });
});

if ($(".tablaArticulosTalleres").length) {
    if (localStorage.getItem("sectorIngreso") != null) {
        cargarTablaArticuloTalleres(localStorage.getItem("sectorIngreso"));
    } else {
        cargarTablaArticuloTalleres(null);
    }
}

function cargarTablaArticuloTalleres(sectorIngreso) {
    if (!$(".tablaArticulosTalleres").length) {
        return;
    }
    $(".tablaArticulosTalleres").DataTable({
        ajax:
            "ajax/produccion/tabla-articulostaller.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&sectorIngreso=" +
            sectorIngreso,
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
            sInfo: "Mostrando del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Mostrando del 0 al 0 de un total de 0",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sInfoPostFix: "",
            sSearch: "Buscar:",
            sUrl: "",
            sInfoThousands: ",",
            sLoadingRecords: "Cargando...",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: ">>>",
                sPrevious: "<<<",
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

$("#daterange-btnTallerT").daterangepicker(
    {
        cancelClass: "CancelarTallerT",
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
        $("#daterange-btnTallerT span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango8 = $("#daterange-btnTallerT span").html();

        localStorage.setItem("capturarRango8", capturarRango8);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaTalleresT").DataTable().destroy();
        cargarTablaTalleresTerminados(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarTallerT").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango8");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "en-tallert";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "en-tallert") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango8", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaTalleresT").DataTable().destroy();
            cargarTablaTalleresTerminados(fechaInicial, fechaFinal);
        }
    }
});

$(".tablaArticulosTalleres tbody").on(
    "click",
    "button.agregarArtiTaller",
    function () {
        var articuloIngreso = $(this).attr("articulo");

        var talleres = $(this).attr("taller");
        var idCierre = $(this).attr("idCierre");
        $(this).removeClass("btn-primary agregarArtiTaller");

        $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("articuloT", articuloIngreso);

        $.ajax({
            url: "ajax/articulos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                // console.log("respuesta", respuesta);

                var articulo = respuesta["articulo"];
                var packing = respuesta["packingB"];
                var taller = respuesta["taller"];

                // todo: AGREGAR LOS CAMPOS

                if (idCierre == "") {
                    $(".nuevoArticuloIngreso").append(`
                    <div class="row munditoIngreso" style="padding:5px 15px">
                        <!-- Descripción del Articulo -->
                        <div class="col-xs-6" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${articuloIngreso}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${articuloIngreso}" name="agregarT" value="${packing}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <!-- Cantidad de la Orden de Corte -->
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" id="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${talleres}" articulo="${articulo}" nuevoTaller="${talleres}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <!-- saldo de la Orden de Corte -->
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" id="nuevoSaldoIngreso" value="${taller}" readonly>
                        </div>
                        <!-- corte -->
                        <div class="col-xs-2 divCorte">
                            <input type="text" class="form-control nuevoCorteIngreso input-sm" name="nuevoCorteIngreso" id="nuevoCorteIngreso" value="">
                        </div>
                    </div>`);
                } else {
                    $(".nuevoArticuloIngreso").append(`
                    <div class="row munditoIngreso" style="padding:5px 15px">
                        <!-- Descripción del Articulo -->
                        <div class="col-xs-6" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${idCierre}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${idCierre}" name="agregarT" value="${packing}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <!-- Cantidad de la Orden de Corte -->
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" id="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${talleres}" articulo="${articulo}" nuevoTaller="${talleres}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <!-- saldo de la Orden de Corte -->
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" id="nuevoSaldoIngreso" value="${talleres}" readonly>
                        </div>
                        <!-- corte -->
                        <div class="col-xs-2 divCorte">
                            <input type="text" class="form-control nuevoCorteIngreso input-sm" name="nuevoCorteIngreso" id="nuevoCorteIngreso" value="">
                        </div>
                    </div>`);
                }

                // Después de agregar los inputs al DOM, agregamos el manejador de eventos para el foco
                $(".nuevoArticuloIngreso")
                    .find(".nuevaCantidadArticuloIngreso")
                    .last()
                    .focus(function () {
                        $(this).select();
                    });

                // SUMAR TOTAL DE UNIDADES

                sumarTotalIngreso();

                // AGREGAR IMPUESTO

                // AGRUPAR PRODUCTOS EN FORMATO JSON

                listarArticulosIngreso();

                // PONER FORMATO AL PRECIO DE LOS PRODUCTOS
            },
        });
    }
);

// Además, para manejar el foco en elementos agregados dinámicamente después del primer clic,
// puedes agregar un manejador de evento focus global para todos los elementos actuales y futuros.
$(document).on("focus", ".nuevaCantidadArticuloIngreso", function () {
    $(this).select();
});

/*
 * CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
 */
$(".tablaArticulosTalleres").on("draw.dt", function () {
    if (localStorage.getItem("quitarTaller") != null) {
        var listaIdArticuloT = JSON.parse(localStorage.getItem("quitarTaller"));

        for (var i = 0; i < listaIdArticuloT.length; i++) {
            $(
                "button.recuperarBoton[articuloIngreso='" +
                    listaIdArticuloT[i]["articuloIngreso"] +
                    "']"
            ).removeClass("btn-default");

            $(
                "button.recuperarBoton[articuloIngreso='" +
                    listaIdArticuloT[i]["articuloIngreso"] +
                    "']"
            ).addClass("btn-primary agregarArtiTaller");
        }
    }
});

/*
 * QUITAR ARTICULO DE LA ORDEN DE CORTE Y RECUPERAR BOTÓN
 */
var idQuitarArticuloT = [];
var idQuitarCierreT = [];

localStorage.removeItem("quitarTaller");
localStorage.removeItem("quitarTallerCierre");

$(".formularioIngreso, .formularioIngresoMulti, .formularioSegundaMulti").on(
    "click",
    "button.quitarTaller",
    function () {
        /* Vista multi: lo maneja crear-ingresos-multi.js */
        if ($(this).attr("data-quitar-fila-key")) {
            return;
        }
        /* console.log("boton"); */

        $(this).parent().parent().parent().parent().remove();
        var articuloIngreso = $(this).attr("articuloIngreso");
        // console.log(articuloIngreso);
        /*=============================================
    ALMACENAR EN EL LOCALSTORAGE EL ID DEL MATERIA PRIMA A QUITAR
    =============================================*/

        if (localStorage.getItem("quitarTaller") == null) {
            idQuitarArticuloT = [];
        } else {
            idQuitarArticuloT.concat(localStorage.getItem("quitarTaller"));
        }

        idQuitarArticuloT.push({
            articuloIngreso: articuloIngreso,
        });

        localStorage.setItem("quitarTaller", JSON.stringify(idQuitarArticuloT));
        // console.log(articuloIngreso);
        $(
            "button.recuperarBoton[articuloIngreso='" + articuloIngreso + "']"
        ).removeClass("btn-default");

        $(
            "button.recuperarBoton[articuloIngreso='" + articuloIngreso + "']"
        ).addClass("btn-primary agregarArtiTaller");

        if ($(".nuevoArticuloIngreso").children().length == 0) {
            $("#nuevoTotalTaller").val(0);
            $("#totalTaller").val(0);
            $("#nuevoTotalTaller").attr("total", 0);
        } else {
            // SUMAR TOTAL DE UNIDADES

            sumarTotalIngreso();

            // AGREGAR IMPUESTO

            // AGRUPAR PRODUCTOS EN FORMATO JSON

            listarArticulosIngreso();
        }
    }
);

/*
 * MODIFICAR LA CANTIDAD
 */
$(".formularioIngreso, .formularioIngresoMulti, .formularioSegundaMulti").on(
    "change",
    "input.nuevaCantidadArticuloIngreso",
    function () {
        var nuevoTaller =
            Number($(this).attr("taller")) - Number($(this).val());

        if ($(this).attr("nuevaCantidad") == "0") {
            if (Number($(this).val()) > Number($(this).attr("taller")) + 10) {
                /*=============================================
			SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
			=============================================*/

                $(this).val(0);
                $(this).attr("cantidad", "");
                sumarTotalIngreso();
                $(this).attr("nuevoTaller", $(this).attr("taller"));
                var inputTaller = $(this)
                    .parent()
                    .parent()
                    .children(".divSaldoIngreso")
                    .children(".nuevoSaldoIngreso");
                // console.log(inputSer);
                inputTaller.val($(this).attr("taller"));
                swal({
                    title: "La cantidad supera el Taller",
                    text: "¡Sólo hay " + $(this).attr("taller") + " unidades!",
                    type: "error",
                    confirmButtonText: "¡Cerrar!",
                });

                return;
            } else {
                $(this).attr("cantidad", Number($(this).val()));
                $(this).attr("nuevoTaller", Number(nuevoTaller));
                var inputTaller = $(this)
                    .parent()
                    .parent()
                    .children(".divSaldoIngreso")
                    .children(".nuevoSaldoIngreso");
                // console.log(inputSer);
                inputTaller.val(nuevoTaller);
                //console.log(articulo);
            }
        } else {
            if (Number($(this).val()) > Number($(this).attr("taller"))) {
                /*=============================================
			SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
			=============================================*/

                $(this).val($(this).attr("nuevaCantidad"));
                $(this).attr(
                    "nuevoTaller",
                    $(this).attr("taller") - $(this).attr("nuevaCantidad")
                );
                $(this).attr("cantidad", Number($(this).val()));
                var inputTaller = $(this)
                    .parent()
                    .parent()
                    .children(".divSaldoIngreso")
                    .children(".nuevoSaldoIngreso");
                // console.log(inputSer);
                inputTaller.val(
                    $(this).attr("taller") - $(this).attr("nuevaCantidad")
                );
                sumarTotalIngreso();

                swal({
                    title: "La cantidad supera el Taller",
                    text: "¡Sólo hay " + $(this).attr("taller") + " unidades!",
                    type: "error",
                    confirmButtonText: "¡Cerrar!",
                });

                return;
            } else {
                var nuevaCantidad =
                    Number($(this).val()) -
                    Number($(this).attr("nuevaCantidad"));
                $(this).attr("cantidad", Number(nuevaCantidad));
                $(this).attr("nuevoTaller", Number(nuevoTaller));
                var inputTaller = $(this)
                    .parent()
                    .parent()
                    .children(".divSaldoIngreso")
                    .children(".nuevoSaldoIngreso");
                // console.log(inputSer);
                inputTaller.val(nuevoTaller);
                //console.log(articulo);
            }
        }

        // SUMAR TOTAL DE UNIDADES

        sumarTotalIngreso();

        // AGREGAR IMPUESTO

        // AGRUPAR PRODUCTOS EN FORMATO JSON

        listarArticulosIngreso();
    }
);

//modificar el corte
$(".formularioIngreso, .formularioIngresoMulti, .formularioSegundaMulti").on(
    "change",
    "input.nuevoCorteIngreso",
    function () {
        sumarTotalIngreso();
        listarArticulosIngreso();
    }
);

$(".formularioIngreso").on("submit", function () {
    if (typeof listarArticulosIngreso === "function") {
        listarArticulosIngreso();
    }
});

/*
 * SUMAR EL TOTAL DE LAS ORDENES DE CORTE
 */

function sumarTotalIngreso() {
    var cantidadOc = $(".nuevaCantidadArticuloIngreso");

    //console.log("cantidadOc", cantidadOc);

    var arraySumarCantidades = [];

    for (var i = 0; i < cantidadOc.length; i++) {
        arraySumarCantidades.push(Number($(cantidadOc[i]).val()));
    }
    /* console.log("arraySumarCantidades", arraySumarCantidades); */

    function sunaArrayCantidades(total, numero) {
        return total + numero;
    }

    var sumarTotal = arraySumarCantidades.reduce(sunaArrayCantidades);

    /* console.log("sumarTotal", sumarTotal); */

    $("#nuevoTotalTaller").val(sumarTotal);
    $("#totalTaller").val(sumarTotal);
    $("#nuevoTotalTaller").attr("total", sumarTotal);
}

/*
 * FORMATO DE MILES AL TOTAL
 */
$("#nuevoTotalTaller").number(true, 0);

/*
 * LISTAR TODOS LOS ARTICULOS
 */
function listarArticulosIngreso() {
    var listaArticulos = [];

    var descripcion = $(".nuevaDescripcionProducto");

    var cantidad = $(".nuevaCantidadArticuloIngreso");

    var corte = $(".nuevoCorteIngreso");

    for (var i = 0; i < descripcion.length; i++) {
        listaArticulos.push({
            id: $(descripcion[i]).attr("articuloIngreso"),
            articulo: $(descripcion[i]).attr("codigoAC"),
            cantidad: $(cantidad[i]).attr("cantidad"),
            nuevaCant: $(cantidad[i]).val(),
            taller: $(cantidad[i]).attr("nuevoTaller"),
            idCierre: $(descripcion[i]).attr("idCierre"),
            corte: $(corte[i]).val(),
            codSector: $(cantidad[i]).attr("data-cod-sector") || "",
            sectorConsulta:
                $(cantidad[i]).attr("data-sector-consulta") || "",
            proceso: $(cantidad[i]).attr("data-proceso") || "",
        });
    }

    console.log(
        "🚀 ~ listarArticulosIngreso ~ listaArticulos:",
        listaArticulos
    );
    // console.log("listaArticulos", JSON.stringify(listaArticulos));

    $("#listaArticulosIngreso").val(JSON.stringify(listaArticulos));
}

/*
 * BOTON EDITAR ORDEN DE CORTE
 */
$(".tablaIngresoM").on("click", ".btnEditarIngStock", function () {
    var idIngreso = $(this).attr("idIngreso");
    var sectorIngreso = $(this).attr("sectorIngreso");
    // console.log(sectorIngreso);
    localStorage.setItem("sectorIngreso", sectorIngreso);

    window.location =
        "index.php?ruta=editar-ingreso&idIngreso=" +
        idIngreso +
        "&sector=" +
        sectorIngreso;
});

/*
 *FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL ARTICULO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
 */
function quitarAgregarArticuloT() {
    //Capturamos todos los id de productos que fueron elegidos en la venta
    var articuloIngreso = $(".quitarTaller");
    //console.log("articuloOC", articuloOC);

    //Capturamos todos los botones de agregar que aparecen en la tabla
    var botonesTablaIngreso = $(
        ".tablaArticulosTalleres tbody button.agregarArtiTaller"
    );
    //console.log("botonesTablaOC", botonesTablaOC);

    //Recorremos en un ciclo para obtener los diferentes articuloOC que fueron agregados a la venta
    for (var i = 0; i < articuloIngreso.length; i++) {
        //Capturamos los Id de los productos agregados a la venta
        var boton = $(articuloIngreso[i]).attr("articuloIngreso");

        //Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
        for (var j = 0; j < botonesTablaIngreso.length; j++) {
            if ($(botonesTablaIngreso[j]).attr("articuloIngreso") == boton) {
                $(botonesTablaIngreso[j]).removeClass(
                    "btn-primary agregarArtiTaller"
                );
                $(botonesTablaIngreso[j]).addClass("btn-default");
            }
        }
    }
}

/*
 * CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIÓN:
 */
$(".tablaArticulosTalleres").on("draw.dt", function () {
    quitarAgregarArticuloT();
});

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("capturarRango9") != null) {
    $("#daterange-btnIngresoM span").html(
        localStorage.getItem("capturarRango9")
    );
    cargarTablaIngresosM(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnIngresoM span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaIngresosM(null, null);
}

/*
 * CARGAR TABLA TALLERES EN TERMINADO
 */
function cargarTablaIngresosM(fechaInicial, fechaFinal) {
    $(".tablaIngresoM").DataTable({
        ajax:
            "ajax/produccion/tabla-ingresos.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[7, "desc"]],
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

$("#daterange-btnIngresoM").daterangepicker(
    {
        cancelClass: "CancelarIngresoStock",
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
        $("#daterange-btnIngresoM span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango9 = $("#daterange-btnIngresoM span").html();

        localStorage.setItem("capturarRango9", capturarRango9);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaIngresoM").DataTable().destroy();
        cargarTablaIngresosM(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarIngresoStock").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango9");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "ingresos";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "ingresos") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango9", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaIngresoM").DataTable().destroy();
            cargarTablaIngresosM(fechaInicial, fechaFinal);
        }
    }
});

/*=============================================
ELIMINAR INGRESOS
=============================================*/
$(".tablaIngresoM").on("click", ".btnEliminarIngStock", function () {
    var idIngreso = $(this).attr("idIngreso");
    var documento = $(this).attr("documento");

    swal({
        title: "¿Está seguro de borrar el ingreso de stock?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar ingreso de stock!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=ingresos&idIngreso=" +
                idIngreso +
                "&documento=" +
                documento;
        }
    });
});

/*=============================================
ELIMINAR SEGUNDA
=============================================*/
$(".tablaIngresoM").on("click", ".btnEliminarSegunda", function () {
    var idSegunda = $(this).attr("idSegunda");
    var documento = $(this).attr("documento");

    swal({
        title: "¿Está seguro de borrar la segunda?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar segunda!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=ingresos&idSegunda=" +
                idSegunda +
                "&documento=" +
                documento;
        }
    });
});

/*
 * BOTON REPORTE DE ORDEN DE CORTE
 */
$(".tablaIngresoM").on("click", ".btnReporteIngresoStock", function () {
    var documento = $(this).attr("documento");
    //console.log("codigo", codigo);

    window.location =
        "vistas/reportes_excel/rpt_ingreso_detalle.php?documento=" + documento;
});

//Reporte de Salidas
$(".box").on("click", ".btnReporteIngresoM", function () {
    window.location = "vistas/reportes_excel/rpt_ingreso_stock.php";
});

//Reporte de Salidas
$(".box").on("click", ".btnReporteTallerTerminado", function () {
    fechaI = localStorage.getItem("fechaInicial");
    fechaF = localStorage.getItem("fechaFinal");
    window.location =
        "vistas/reportes_excel/rpt_taller_terminado.php?fechaInicial=" +
        fechaI +
        "&fechaFinal=" +
        fechaF;
});

$(".box").on("click", ".btnReporteTallerGenerado", function () {
    articuloTallerP = localStorage.getItem("articuloTallerP");
    window.location =
        "vistas/reportes_excel/rpt_taller_generado.php?articuloTallerP=" +
        articuloTallerP;
});

$(".box").on("click", ".btnReporteTallerOperacion", function () {
    modeloTallerP = localStorage.getItem("modeloTallerOp");
    window.location =
        "vistas/reportes_excel/rpt_taller_operacion.php?modeloTallerP=" +
        modeloTallerP;
});

/*=============================================
EDITAR TALLER T
=============================================*/
$(".tablaTalleresT").on("click", ".btnEditarTallerTerminado", function () {
    var idTallerT = $(this).attr("idTallerT");
    var datos = new FormData();
    datos.append("idTallerT", idTallerT);

    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#editarModelo").val(respuesta["modelo"]);
            $("#editarColor").val(respuesta["color"]);
            $("#editarTalla").val(respuesta["talla"]);
            $("#editarCodOperacion").val(respuesta["cod_operacion"]);
            $("#editarOperacion").val(respuesta["nom_operacion"]);
            $("#editar_cod_tra").val(respuesta["cod_trabajador"]);
            $("#editar_cod_tra").selectpicker("refresh");
            $("#editar_codigoBarra").val(respuesta["codigo"]);
            $("#editarFechaProceso").val(respuesta["fecha_proceso"]);
            $("#editarFechaTerminado").val(respuesta["fecha_terminado"]);
        },
    });
});

/*=============================================
EDITAR TALLER T
=============================================*/
$(".tablaTalleresT").on("click", ".btnDividirTallerTerminado", function () {
    var idTaller = $(this).attr("idTaller");
    var datos = new FormData();
    datos.append("idTaller", idTaller);

    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#editarCodigo").val(respuesta["id_cabecera"]);
            $("#editarArticulo").val(respuesta["articulo"]);
            $("#editarNombre").val(respuesta["nombre"]);
            $("#editarModelos").val(respuesta["modelo"]);
            $("#editarColores").val(respuesta["color"]);
            $("#editarTallas").val(respuesta["talla"]);
            $("#cantidades").val(respuesta["cantidad"]);
            $("#editarCantidades").val(respuesta["cantidad"]);
            $("#editarCodOperaciones").val(respuesta["cod_operacion"]);
            $("#editarCOTT").val(respuesta["cod_operacion"]);
            $("#editarOTT").val(respuesta["nom_operacion"]);
            $("#editarBarra").val(respuesta["codigo"]);
            $("#editarTaller").val(respuesta["id"]);
            $("#trabajador").val(respuesta["trabajador"]);
            $("#fecha_proceso").val(respuesta["fecha_proceso"]);
            $("#fecha_terminado").val(respuesta["fecha_terminado"]);
        },
    });
});

/*
 * BOTON EDITAR SEGUNDA
 */
$(".tablaIngresoM").on("click", ".btnEditarSegunda", function () {
    var idIngreso = $(this).attr("idIngreso");

    window.location = "index.php?ruta=editar-segunda&idIngreso=" + idIngreso;
});

$("#fechaCabecera").change(function () {
    var fecha = $(this).val();
    var datos = new FormData();
    datos.append("fecha", fecha);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoCodigo").find("option").remove();
            $("#nuevoCodigo").append(
                '<option value="">Seleccionar articulo</option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevoCodigo").append(
                    "<option value='" +
                        respuesta[i]["id"] +
                        "'>" +
                        respuesta[i]["id"] +
                        " - " +
                        respuesta[i]["articulo"] +
                        " - " +
                        respuesta[i]["color"] +
                        " - " +
                        respuesta[i]["talla"] +
                        "</option>"
                );
            }
            $("#nuevoCodigo").selectpicker("refresh");
        },
    });
});
$("#fechaCabecera2").change(function () {
    var fecha = $(this).val();
    var datos = new FormData();
    datos.append("fecha", fecha);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoCodigo2").find("option").remove();
            $("#nuevoCodigo2").append(
                '<option value="">Seleccionar articulo</option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevoCodigo2").append(
                    "<option value='" +
                        respuesta[i]["id"] +
                        "'>" +
                        respuesta[i]["id"] +
                        " - " +
                        respuesta[i]["articulo"] +
                        " - " +
                        respuesta[i]["color"] +
                        " - " +
                        respuesta[i]["talla"] +
                        "</option>"
                );
            }
            $("#nuevoCodigo2").selectpicker("refresh");
        },
    });
});

/*
 * CARGAR TABLA TALLER GENERADO
 */
if (localStorage.getItem("articuloTallerP") != null) {
    $("#selectArticuloTallerP").val(localStorage.getItem("articuloTallerP"));
    $("#selectArticuloTallerP").selectpicker("refresh");

    cargarTablaTalleresGenerados(localStorage.getItem("articuloTallerP"));
    // console.log("lleno");
} else {
    cargarTablaTalleresGenerados(null);
    // console.log("vacio");
}

function cargarTablaTalleresGenerados(articuloTallerP) {
    $(".tablaTalleresGenerado").DataTable({
        ajax:
            "ajax/produccion/tabla-talleresGenerado.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&articuloTallerP=" +
            articuloTallerP,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[1, "asc"]],
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
        createdRow: function (row, data, index) {
            var cantidad = data[1].length;
            if (cantidad == 11) {
                if (data[1].substr(-1) == "A") {
                    $("td", row).css({
                        "background-color": "#FDEC8A",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "B") {
                    $("td", row).css({
                        "background-color": "#DCF6C8",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "C") {
                    $("td", row).css({
                        "background-color": "#FFCE6B",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "D") {
                    $("td", row).css({
                        "background-color": "#FF746B",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "E") {
                    $("td", row).css({
                        "background-color": "#F98EF1",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "F") {
                    $("td", row).css({
                        "background-color": "#A6FA52",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "1") {
                    $("td", row).css({
                        "background-color": "#FDEC8A",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "2") {
                    $("td", row).css({
                        "background-color": "#DCF6C8",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "3") {
                    $("td", row).css({
                        "background-color": "#FFCE6B",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "4") {
                    $("td", row).css({
                        "background-color": "#FF746B",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "5") {
                    $("td", row).css({
                        "background-color": "#F98EF1",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "6") {
                    $("td", row).css({
                        "background-color": "#A6FA52",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "7") {
                    $("td", row).css({
                        "background-color": "#35FAE2",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "8") {
                    $("td", row).css({
                        "background-color": "#276BE1",
                        color: "black",
                    });
                }
                if (data[1].substr(-1) == "9") {
                    $("td", row).css({
                        "background-color": "#A9F3EA",
                        color: "black",
                    });
                }
            }
        },
    });
}

$("#selectArticuloTallerP").change(function () {
    $(".tablaTalleresGenerado").DataTable().destroy();
    var articuloTallerP = $(this).val();
    localStorage.setItem("articuloTallerP", articuloTallerP);
    cargarTablaTalleresGenerados(localStorage.getItem("articuloTallerP"));
});

/*
 * BOTON LIMPIAR MODELO CORTE
 */
$(".box").on("click", ".btnLimpiarArticuloTallerP", function () {
    localStorage.removeItem("articuloTallerP");
    localStorage.clear();
    window.location = "en-tallerp";
});

/*=============================================
EDITAR TALLER T
=============================================*/
$(".tablaTalleresGenerado").on(
    "click",
    ".btnDividirTallerGenerado",
    function () {
        var idTaller = $(this).attr("idTaller");
        var datos = new FormData();
        datos.append("idTaller", idTaller);

        $.ajax({
            url: "ajax/talleres.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#editarCodigo").val(respuesta["id_cabecera"]);
                $("#editarArticulo").val(respuesta["articulo"]);
                $("#editarNombre").val(respuesta["nombre"]);
                $("#editarModelos").val(respuesta["modelo"]);
                $("#editarColores").val(respuesta["color"]);
                $("#editarTallas").val(respuesta["talla"]);
                $("#cantidades").val(respuesta["cantidad"]);
                $("#editarCantidades").val(respuesta["cantidad"]);
                $("#editarCodOperaciones").val(respuesta["cod_operacion"]);
                $("#editarCOTP").val(respuesta["cod_operacion"]);
                $("#editarOTP").val(respuesta["nom_operacion"]);
                $("#editarBarra").val(respuesta["codigo"]);
                $("#editarTaller").val(respuesta["id"]);
                $("#trabajador").val(respuesta["trabajador"]);
                $("#fecha_proceso").val(respuesta["fecha_proceso"]);
                $("#fecha_terminado").val(respuesta["fecha_terminado"]);
            },
        });
    }
);

$(".tablaTalleresGenerado").on(
    "click",
    ".btnRegresarTallerGenerado",
    function () {
        var idTaller = $(this).attr("idTaller");
        var datos = new FormData();
        datos.append("idTaller", idTaller);

        $.ajax({
            url: "ajax/talleres.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#regresarCodigo").val(respuesta["id_cabecera"]);
                $("#regresarArticulo").val(respuesta["articulo"]);
                $("#regresarNombre").val(respuesta["nombre"]);
                $("#regresarModelos").val(respuesta["modelo"]);
                $("#regresarColores").val(respuesta["color"]);
                $("#regresarTallas").val(respuesta["talla"]);
                $("#regresarCantidades").val(respuesta["cantidad"]);
                $("#regresarCantidades").val(respuesta["cantidad"]);
                $("#regresarCodOperaciones").val(respuesta["cod_operacion"]);
                $("#regresarCOTP").val(respuesta["cod_operacion"]);
                $("#regresarOTP").val(respuesta["nom_operacion"]);
                var barra = respuesta["codigo"];
                var ultBarra = barra.substr(-1);
                if (barra.length == 11) {
                    if (ultBarra == "A") {
                        $("#regresarBarra").val(barra.substr(0, 10));
                    } else if (ultBarra == "B") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "A");
                    } else if (ultBarra == "C") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "B");
                    } else if (ultBarra == "D") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "C");
                    } else if (ultBarra == "E") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "D");
                    } else if (ultBarra == "F") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "E");
                    } else if (ultBarra == "1") {
                        $("#regresarBarra").val(barra.substr(0, 10));
                    } else if (ultBarra == "2") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "1");
                    } else if (ultBarra == "3") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "2");
                    } else if (ultBarra == "4") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "3");
                    } else if (ultBarra == "5") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "4");
                    } else if (ultBarra == "6") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "5");
                    } else if (ultBarra == "7") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "6");
                    } else if (ultBarra == "8") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "7");
                    } else if (ultBarra == "9") {
                        $("#regresarBarra").val(barra.substr(0, 10) + "8");
                    }
                }
                $("#regresarBarraAntigua").val(barra);
                $("#regresarTaller").val(respuesta["id"]);
                $("#trabajador").val(respuesta["trabajador"]);
                $("#regresarFecha_proceso").val(respuesta["fecha_proceso"]);
                $("#regresarFecha_terminado").val(respuesta["fecha_terminado"]);
            },
        });
    }
);

//Reporte de Para
$(".box").on("click", ".btnReporteProduccionBrasier", function () {
    var inicio = $(this).attr("fechaInicial");
    var fin = $(this).attr("fechaFinal");
    window.location =
        "vistas/reportes_excel/rpt_produccion_brasieres.php?inicio=" +
        inicio +
        "&fin=" +
        fin;
});

//Reporte de Para
$(".box").on("click", ".btnReporteProduccionTrusas", function () {
    var inicio = $(this).attr("fechaInicial");
    var fin = $(this).attr("fechaFinal");
    window.location =
        "vistas/reportes_excel/rpt_produccion_trusas.php?inicio=" +
        inicio +
        "&fin=" +
        fin;
});

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("modeloTallerOp") != null) {
    cargarTablaTalleresOperaciones(localStorage.getItem("modeloTallerOp"));
} else {
    cargarTablaTalleresOperaciones(null, null);
}

function cargarTablaTalleresOperaciones(modeloTallerOp) {
    $(".tablaTalleresOperaciones").DataTable({
        ajax:
            "ajax/produccion/tabla-talleresOperaciones.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&modeloTallerOp=" +
            modeloTallerOp,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
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

$("#selectModeloTallerOp").change(function () {
    $(".tablaTalleresOperaciones").DataTable().destroy();
    var modeloTallerOp = $(this).val();
    localStorage.setItem("modeloTallerOp", modeloTallerOp);
    cargarTablaTalleresOperaciones(localStorage.getItem("modeloTallerOp"));
});

/*
 * BOTON LIMPIAR MODELO CORTE
 */
$(".box").on("click", ".btnLimpiarModeloTallerOp", function () {
    localStorage.removeItem("modeloTallerOp");
    localStorage.clear();
    window.location = "operacion-taller";
});

// EDITAR CANTIDAD OPERACION
$(".tablaTalleresOperaciones").on(
    "click",
    ".btnEditarTallerOperacion",
    function () {
        var idTaller = $(this).attr("idTaller");
        var datos = new FormData();
        datos.append("idTaller", idTaller);
        $.ajax({
            url: "ajax/talleres.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#editarCodigo").val(respuesta["id_cabecera"]);
                $("#editarArticulo").val(respuesta["articulo"]);
                $("#cantidad").val(respuesta["cantidad"]);
                $("#editarCodOperacion").val(respuesta["cod_operacion"]);
                $("#editarCOTO").val(respuesta["cod_operacion"]);
                $("#editarOTO").val(respuesta["nom_operacion"]);
                $("#editarCantidad2").val(respuesta["cantidad"]);
                $("#editarTaller").val(respuesta["id"]);
                $("#editarBarra").val(respuesta["codigo"]);
                $("#editarTalla").val(respuesta["talla"]);
                $("#editarMarca").val(respuesta["marca"]);
                $("#editarColor").val(respuesta["color"]);
                $("#editarNombre").val(respuesta["nombre"]);
                $("#editarModelo").val(respuesta["modelo"]);
            },
        });
    }
);

$(".box").on("click", ".btnCrearTicket", function () {
    var idTaller = $(this).attr("idTaller");
    var datos = new FormData();
    datos.append("idTaller", idTaller);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //  console.log(respuesta);
            $("#verCab").val(respuesta["id_cabecera"]);
            $("#verArti").val(respuesta["articulo"]);
            $("#verCodOP").val(respuesta["cod_operacion"]);
            $("#verBar").val(respuesta["codigo"]);
            $("#verCol").val(respuesta["color"]);
            $("#verOP").val(respuesta["nombre"]);
            $("#verMod").val(respuesta["modelo"]);
            $("#verPrec").val(respuesta["total_precio"]);
            $("#verTmp").val(respuesta["total_tiempo"]);
        },
    });
});

$(".tablaTalleresOperaciones").on("click", ".btnImprimirTicket", function () {
    var ultimo = $(this).attr("ultimo");

    var modelo = $(this).attr("modelo");

    var nombre = $(this).attr("nombre");

    var color = $(this).attr("color");

    var talla = $(this).attr("talla");

    var cant_taller = $(this).attr("cant_taller");

    var cod_operacion = $(this).attr("cod_operacion");

    var nom_operacion = $(this).attr("nom_operacion");

    window.open(
        "vistas/reportes_ticket/produccion_ticket_detalle.php?ultimo=" +
            ultimo +
            "&modelo=" +
            modelo +
            "&nombre=" +
            nombre +
            "&color=" +
            color +
            "&talla=" +
            talla +
            "&cant_taller=" +
            cant_taller +
            "&cod_operacion=" +
            cod_operacion +
            "&nom_operacion=" +
            nom_operacion,
        "_blank"
    );
});

$("#ticketArticulo").change(function () {
    var articulo = $(this).val();
    //console.log(articulo.length);
    if (articulo.length == 8) {
        var modelo = articulo.substring(0, 5);
    } else {
        var modelo = articulo.substring(0, 4);
    }
    var datos = new FormData();
    datos.append("modelo", modelo);
    $.ajax({
        url: "ajax/talleres.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);

            $("#ticketOperacion").find("option").remove();
            $("#ticketOperacion").append(
                '<option value="">Seleccionar operacion</option>'
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#ticketOperacion").append(
                    "<option value='" +
                        respuesta[i]["cod_operacion"] +
                        "'>" +
                        respuesta[i]["cod_operacion"] +
                        " - " +
                        respuesta[i]["nombre"] +
                        "</option>"
                );
            }
            $("#ticketOperacion").selectpicker("refresh");
        },
    });
});

$(".tablaTalleresT").on("click", ".btnReiniciarTallerT", function () {
    var codigo = $(this).attr("codigoTallerT");
    var estadoTaller = $(this).attr("estadoTaller");
    // console.log("estadoTaller", estadoTaller);
    // Capturamos el id del usuario y el estado
    swal({
        title: "¿Está seguro de reiniciar el taller del codigo " + codigo + "?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, reiniciar el taller!",
    }).then(function (result) {
        if (result.value) {
            // console.log("codigo", codigo);

            //Realizamos la activación-desactivación por una petición AJAX
            var datos = new FormData();
            datos.append("activarId", codigo);
            datos.append("activarEstado", estadoTaller);
            $.ajax({
                url: "ajax/talleres.ajax.php",
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
                        text: "¡El taller fue reiniciado con éxito!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false,
                    }).then((result) => {
                        if (result.value) {
                            window.location = "en-tallert";
                        }
                    });
                },
            });
        }
    });
});

$(".btnIngresoDeta").click(function () {
    //tabla dinamica de ver Ingresos General
    if (localStorage.getItem("capturarRango10") != null) {
        $("#daterange-btnVerIngresos span").html(
            localStorage.getItem("capturarRango10")
        );
        cargarTablaDetalleIngresos(
            localStorage.getItem("fechaInicial"),
            localStorage.getItem("fechaFinal")
        );
    } else {
        $("#daterange-btnVerIngresos span").html(
            '<i class="fa fa-calendar"></i> Rango de Fecha '
        );
        cargarTablaDetalleIngresos(null, null);
    }
});

function cargarTablaDetalleIngresos(fechaInicial, fechaFinal) {
    $(".tablaDetalleIngresoTotal").DataTable({
        ajax:
            "ajax/produccion/tabla-ver-ingresos.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[2, "desc"]],
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

$("#daterange-btnVerIngresos").daterangepicker(
    {
        cancelClass: "CancelarVerIngresos",
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
        $("#daterange-btnVerIngresos span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango10 = $("#daterange-btnVerIngresos span").html();

        localStorage.setItem("capturarRango10", capturarRango10);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaDetalleIngresoTotal").DataTable().destroy();
        cargarTablaDetalleIngresos(fechaInicial, fechaFinal);
    }
);

/*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarVerIngresos").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango10");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "ingresos";
    }
);

/*=============================================
  CAPTURAR HOY
  =============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if (ruta == "ingresos") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango10", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaDetalleIngresoTotal").DataTable().destroy();
            cargarTablaDetalleIngresos(fechaInicial, fechaFinal);
        }
    }
});

//ver tabla de cada ingreso registrado

$(".tablaIngresoM").on("click", ".btnVisualizarIngreso", function () {
    var codigoIngreso = $(this).attr("codigoIngreso");
    //console.log("codigoAC", codigoAC);

    var datos = new FormData();
    datos.append("codigoIngreso", codigoIngreso);

    $.ajax({
        url: "ajax/ingresos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log("respuesta", respuesta);

            $("#cierre").val(respuesta["documento"]);
            $("#tipoIng").val(respuesta["tipo"]);
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

    var codigoDIngreso = $(this).attr("codigoIngreso");
    //console.log("codigoDAC", codigoDAC);

    var datosDOC = new FormData();
    datosDOC.append("codigoDIngreso", codigoDIngreso);

    $.ajax({
        url: "ajax/ingresos.ajax.php",
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

                $(".tablaDetalleIngreso").append(
                    '<tr class="detalleMP">' +
                        "<td>" +
                        id.cod_sector +
                        " - " +
                        id.nom_sector +
                        " </td>" +
                        "<td>" +
                        id.fechas +
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

$(".tablaTalleresGenerado , .tablaTalleresG").on(
    "click",
    ".btnCerrarTaller",
    function () {
        var codigo = $(this).attr("codigoTallerG");
        var estadoTaller = $(this).attr("estadoTaller");
        //Realizamos la activación-desactivación por una petición AJAX
        var datos = new FormData();
        datos.append("activarId", codigo);
        datos.append("activarEstado", estadoTaller);
        $.ajax({
            url: "ajax/talleres.ajax.php",
            type: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (respuesta) {
                if (estadoTaller == "4") {
                    Command: toastr["error"]("Ticket cerrado correctamente!");
                } else {
                    Command: toastr["info"]("Ticket generado correctamente!");
                }
            },
        });

        if (estadoTaller == "4") {
            $(this).removeClass("btn-info");
            $(this).addClass("btn-danger");
            $(this).html("Cerrado");
            $(this).attr("estadoTaller", "1");
        } else {
            $(this).removeClass("btn-danger");
            $(this).addClass("btn-info");
            $(this).html("Generado");
            $(this).attr("estadoTaller", "4");
        }
    }
);

$(".box").on("click", ".btnActualizarFecha", function () {
    // console.log("estadoTaller", estadoTaller);
    // Capturamos el id del usuario y el estado
    var fecha = "probando";
    swal({
        title: "¿Está seguro de actualizar el taller con fecha de ayer",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, actualizar el taller!",
    }).then(function (result) {
        if (result.value) {
            var datos = new FormData();
            datos.append("actualizarFecha", fecha);
            $.ajax({
                url: "ajax/talleres.ajax.php",
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
                        text: "¡El taller fue actualizado ayer con éxito!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar",
                        closeOnConfirm: false,
                    }).then((result) => {
                        if (result.value) {
                            window.location = "en-tallert";
                        }
                    });
                },
            });
        }
    });
});

$("#editarCantidades").keyup(function () {
    var nuevaCantidad = $(this).val();
    var cantidad = $("#cantidades").val();

    if (Number(nuevaCantidad) > Number(cantidad)) {
        /*=============================================
		SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
		=============================================*/

        $(this).val(cantidad);

        Command: toastr["error"](
            "La cantidad supera al origen solo hay " + cantidad + " UND!"
        );
    }
});

var tablaEditarDetalleIngreso = null;

if ($(".tablaEditarDetalleIngreso").length && $("#codigoIngreso").length) {
    tablaEditarDetalleIngreso = $(".tablaEditarDetalleIngreso").DataTable({
        ajax:
            "ajax/produccion/tabla-editar-ingreso.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&codigo=" +
            $("#codigoIngreso").val(),
        deferRender: true,
        retrieve: true,
        processing: true,
        pageLength: 20,
        dom: '<"row"<"col-sm-5"l><"col-sm-7 ingreso-dt-toolbar"f>>rtip',
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "Mostrando del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Mostrando del 0 al 0 de un total de 0",
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
        initComplete: function () {
            var $toolbar = $(this.api().table().container()).find(
                ".ingreso-dt-toolbar"
            );
            var $filtro = $("#filtroModeloIngresoWrap");
            var $buscar = $toolbar.find(".dataTables_filter");

            if ($toolbar.length && $filtro.length && $buscar.length) {
                // Buscar primero (posición original), modelo a la derecha
                $filtro.insertAfter($buscar).show();
                if ($.fn.selectpicker) {
                    $("#filtroModeloIngreso").selectpicker("refresh");
                }
            }
        },
    });

    $(document).on("changed.bs.select change", "#filtroModeloIngreso", function () {
        if (!tablaEditarDetalleIngreso) {
            return;
        }

        var modelo = $(this).val() || "";
        if (modelo === "") {
            tablaEditarDetalleIngreso.column(2).search("").draw();
            return;
        }

        var modeloEscapado = $.fn.dataTable.util.escapeRegex(modelo);
        tablaEditarDetalleIngreso
            .column(2)
            .search("^" + modeloEscapado + "$", true, false)
            .draw();
    });
}

//
$("#selectEnTalleres").change(function () {
    $(".tablaEnTalleres").DataTable().destroy();
    let taller = $(this).val();
    console.log("taller", taller);
    cargarTablaEnTaller(taller);
});

function cargarTablaEnTaller(taller) {
    $(".tablaEnTalleres").DataTable({
        ajax: "ajax/produccion/tabla-en-talleres.php?taller=" + taller,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
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

cargarTablaEnTaller(null);
