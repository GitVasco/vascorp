/*
 * TABLA CON LAS VENTAS TOTALES POR MES
 */
$(".tablaMovimientos").DataTable({
    ajax: "ajax/movimientos/tabla-movimientos.ajax.php",
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

/*
 * TABLA DIAS
 */
$(".tablaDatosDia").DataTable({
    ajax: "ajax/movimientos/tabla-datos-dia.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "desc"]],
    pageLength: 31,
    lengthMenu: [
        [31, 62, 93, -1],
        [31, 62, 93, "Todos"],
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
 * ACTUALIZAR TOTALES DEL MES
 */
$(".tablaMovimientos").on("click", ".btnActualizarMes", function () {
    var año = $(this).attr("año");
    var mes = $(this).attr("mes");

    /* console.log(año, mes); */

    var datos = new FormData();
    datos.append("año", año);
    datos.append("mes", mes);

    $.ajax({
        url: "ajax/movimientos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            /* console.log("respuesta",respuesta); */

            if (respuesta == "ok") {
                swal({
                    type: "success",
                    title: "¡Ok!",
                    text: "¡La información fue Actualizada con éxito!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                }).then((result) => {
                    if (result.value) {
                        window.location = "movimientos";
                    }
                });
            }
        },
    });
});

/* 
! PRODUCCION
*/

/*
 * BOTON ACEPTAR
 */
$("#modeloP").change(function () {
    $(".tablaMProd").DataTable().destroy();

    var modeloP = $(this).val();
    //console.log(modeloP);

    localStorage.setItem("modeloP", modeloP);
    $(".btnReporteProduccion").attr("modelo", modeloP);
    cargarTablaMProd(localStorage.getItem("modeloP"));
});

/*
 * BOTON LIMPIAR
 */
$(".box").on("click", ".btnLimpiarModP", function () {
    localStorage.removeItem("modeloP");
    $(".btnReporteProduccion").attr("modelo", "");
    localStorage.clear();

    window.location = "m-produccion";
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("modeloP") != null) {
    $("#modeloP").val(localStorage.getItem("modeloP"));
    $("#modeloP").selectpicker("refresh");
    cargarTablaMProd(localStorage.getItem("modeloP"));
    //console.log("lleno");
} else {
    cargarTablaMProd(null);
    //console.log("vacio");
}

/*
 * TABLA MOVIMIENTOS
 */
function cargarTablaMProd(modeloP) {
    $(".tablaMProd").DataTable({
        ajax:
            "ajax/movimientos/tabla-mProd.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&modeloP=" +
            modeloP,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 30,
        lengthMenu: [
            [30, 60, 90, -1],
            [30, 60, 90, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "No hay datos disponibles en esta tabla",
            sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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
! VENTAS
*/

/*
 * BOTON ACEPTAR
 */
$("#modeloV").change(function () {
    $(".tablaMVta").DataTable().destroy();

    var modeloV = $(this).val();
    //console.log(modeloV);

    localStorage.setItem("modeloV", modeloV);
    $(".btnReporteVentas").attr("modelo", modeloV);
    cargarTablaMVta(localStorage.getItem("modeloV"));
});

/*
 * BOTON LIMPIAR
 */
$(".box").on("click", ".btnLimpiarModV", function () {
    localStorage.removeItem("modeloV");
    $(".btnReporteVentas").attr("modelo", "");
    localStorage.clear();

    window.location = "m-ventas";
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("modeloV") != null) {
    $("#modeloV").val(localStorage.getItem("modeloV"));
    $("#modeloV").selectpicker("refresh");
    cargarTablaMVta(localStorage.getItem("modeloV"));
    //console.log("lleno");
} else {
    cargarTablaMVta(null);
    //console.log("vacio");
}

/*
 * TABLA MOVIMIENTOS
 */
function cargarTablaMVta(modeloV) {
    $(".tablaMVta").DataTable({
        ajax:
            "ajax/movimientos/tabla-mVta.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&modeloV=" +
            modeloV,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 30,
        lengthMenu: [
            [30, 60, 90, -1],
            [30, 60, 90, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "No hay datos disponibles en esta tabla",
            sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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
! INGRESOS MATERIA PRIMA
*/

/*
 * BOTON ACEPTAR
 */
$("#lineaMPIng").change(function () {
    $(".tablaMpIng").DataTable().destroy();

    var lineaMpIng = $(this).val();
    //console.log(lineaMp);

    localStorage.setItem("lineaMpIng", lineaMpIng);
    $(".btnReporteIngreso").attr("linea", lineaMpIng);
    cargarTablaMpIng(localStorage.getItem("lineaMpIng"));
});

/*
 * BOTON LIMPIAR
 */
$(".box").on("click", ".btnLimpiarMpIng", function () {
    localStorage.removeItem("lineaMpIng");
    $(".btnReporteIngreso").attr("linea", "");
    localStorage.clear();

    window.location = "mp-ingresos";
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("lineaMpIng") != null) {
    $("#lineaMPIng").val(localStorage.getItem("lineaMpIng"));
    $("#lineaMPIng").selectpicker("refresh");
    cargarTablaMpIng(localStorage.getItem("lineaMpIng"));
    //console.log("lleno");
} else {
    cargarTablaMpIng(null);
    //console.log("vacio");
}

/*
 * TABLA MOVIMIENTOS
 */
function cargarTablaMpIng(lineaMpIng) {
    $(".tablaMpIng").DataTable({
        ajax:
            "ajax/movimientos/tabla-mping.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&lineaMpIng=" +
            lineaMpIng,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 30,
        lengthMenu: [
            [30, 60, 90, -1],
            [30, 60, 90, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "No hay datos disponibles en esta tabla",
            sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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
! SALIDAS MATERIA PRIMA
*/

/*
 * BOTON ACEPTAR
 */
$("#lineaMPSal").change(function () {
    $(".tablaMpSal").DataTable().destroy();

    var lineaMpSal = $(this).val();
    console.log(lineaMpSal);
    //console.log(lineaMp);
    $(".btnReporteSalida").attr("linea", lineaMpSal);
    localStorage.setItem("lineaMpSal", lineaMpSal);

    cargarTablaMpSal(localStorage.getItem("lineaMpSal"));
});

/*
 * BOTON LIMPIAR
 */
$(".box").on("click", ".btnLimpiarMpSal", function () {
    localStorage.removeItem("lineaMpSal");
    localStorage.clear();
    $(".btnReporteSalida").attr("linea", "");
    window.location = "mp-salidas";
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("lineaMpSal") != null) {
    $("#lineaMPSal").val(localStorage.getItem("lineaMpSal"));
    $("#lineaMPSal").selectpicker("refresh");
    cargarTablaMpSal(localStorage.getItem("lineaMpSal"));
    //console.log("lleno");
} else {
    cargarTablaMpSal(null);
    //console.log("vacio");
}

/*
 * TABLA MOVIMIENTOS
 */
function cargarTablaMpSal(lineaMpSal) {
    $(".tablaMpSal").DataTable({
        ajax:
            "ajax/movimientos/tabla-mpsal.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&lineaMpSal=" +
            lineaMpSal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 30,
        lengthMenu: [
            [30, 60, 90, -1],
            [30, 60, 90, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "No hay datos disponibles en esta tabla",
            sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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

//Reporte de Produccion
$(".box").on("click", ".btnReporteProduccion", function () {
    var modelo = $(this).attr("modelo");
    window.location =
        "vistas/reportes_excel/rpt_movimiento_produccion.php?modelo=" + modelo;
});

//Reporte de Ventas
$(".box").on("click", ".btnReporteVentas", function () {
    var modelo = $(this).attr("modelo");
    window.location =
        "vistas/reportes_excel/rpt_movimiento_venta.php?modelo=" + modelo;
});

//Reporte de Ingresos
$(".box").on("click", ".btnReporteIngreso", function () {
    var linea = $(this).attr("linea");
    window.location =
        "vistas/reportes_excel/rpt_movimiento_ingreso.php?linea=" + linea;
});

//Reporte de Salidas
$(".box").on("click", ".btnReporteSalida", function () {
    var linea = $(this).attr("linea");
    window.location =
        "vistas/reportes_excel/rpt_movimiento_salida.php?linea=" + linea;
});

/*
 * MANEJO DE FILTROS AÑO Y MES PARA INICIO-GERENCIA
 */
function actualizarFiltrosGerencia() {
    var año = $("#añoGerencia").val() || "";
    var mes = $("#mesGerencia").val() || "";

    localStorage.setItem("añoGerencia", año);
    localStorage.setItem("mesGerencia", mes);

    // Construir URL con parámetros
    var url = "index.php?ruta=inicio-gerencia";
    if (año != "") {
        url += "&año=" + año;
    }
    if (mes != "") {
        url += "&mes=" + mes;
    }

    window.location = url;
}

// Cargar valores de la URL o localStorage al cargar la página
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const añoURL = urlParams.get("año");
    const mesURL = urlParams.get("mes");
    
    // Si hay parámetros en la URL, usarlos (el PHP ya los estableció como selected)
    // Si no hay parámetros en la URL, usar localStorage o valores por defecto
    if (!añoURL && !mesURL) {
        var añoGuardado = localStorage.getItem("añoGerencia");
        var mesGuardado = localStorage.getItem("mesGerencia");
        
        // Solo usar localStorage si no hay parámetros en la URL
        if (añoGuardado) {
            $("#añoGerencia").val(añoGuardado);
            $("#añoGerencia").selectpicker('refresh');
        } else {
            // Si no hay localStorage ni URL, establecer año actual
            var añoActual = new Date().getFullYear();
            $("#añoGerencia").val(añoActual);
            $("#añoGerencia").selectpicker('refresh');
        }
        
        if (mesGuardado) {
            $("#mesGerencia").val(mesGuardado);
            $("#mesGerencia").selectpicker('refresh');
        } else {
            // Si no hay localStorage ni URL, establecer mes actual
            var mesActual = new Date().getMonth() + 1; // getMonth() devuelve 0-11
            $("#mesGerencia").val(mesActual);
            $("#mesGerencia").selectpicker('refresh');
        }
    }
    // Si hay parámetros en la URL, el PHP ya estableció los valores correctos
    // Solo necesitamos refrescar el selectpicker para que se muestren correctamente
    $("#añoGerencia").selectpicker('refresh');
    $("#mesGerencia").selectpicker('refresh');
});

$("#añoGerencia").change(function () {
    actualizarFiltrosGerencia();
});

$("#mesGerencia").change(function () {
    actualizarFiltrosGerencia();
});

const urlParams = new URLSearchParams(window.location.search);
const myParam = urlParams.get("mes");
const myAñoParam = urlParams.get("año") || "";

// Construir parámetros para las URLs de AJAX
var ajaxParams = "mes=" + (myParam || "");
if (myAñoParam != "") {
    ajaxParams += "&año=" + myAñoParam;
}

$(".tablaVtasGerencia").DataTable({
    ajax: "ajax/movimientos/tabla-vtasgerencia.ajax.php?" + ajaxParams,
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "desc"]],
    searching: false,
    paging: false,
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "No hay datos disponibles en esta tabla",
        sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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

$(".tablaVtasGerenciaVdor").DataTable({
    ajax: "ajax/movimientos/tabla-vtasgerenciavdor.ajax.php?" + ajaxParams,
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
    searching: false,
    paging: false,
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "No hay datos disponibles en esta tabla",
        sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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
        $(api.column(2).footer()).html(
            Intl.NumberFormat().format(
                api.column(2, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(3).footer()).html(
            Intl.NumberFormat().format(
                api.column(3, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(4).footer()).html(
            Intl.NumberFormat().format(
                api.column(4, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );
    },
});

$(".tablaCtasVdor").DataTable({
    ajax: "ajax/movimientos/tabla-ctasgerenciavdor.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
    searching: false,
    paging: false,
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "No hay datos disponibles en esta tabla",
        sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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

$(".tablaRangos").DataTable({
    ajax: "ajax/movimientos/tabla-rangos.ajax.php?" + ajaxParams,
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
    searching: false,
    paging: false,
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "No hay datos disponibles en esta tabla",
        sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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
        $(api.column(2).footer()).html(
            Intl.NumberFormat().format(
                api.column(2, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(3).footer()).html(
            Intl.NumberFormat().format(
                api.column(3, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(4).footer()).html(
            Intl.NumberFormat().format(
                api.column(4, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        // Columnas: 0=Codigo, 1=Nombre, 2=Ventas, 3=Cobranza, 4=Vencidos, 5=2014-2020, 6=2021, 7=2022, 8=2023, 9=2024, 10=2025
        $(api.column(5).footer()).html(
            Intl.NumberFormat().format(
                api.column(5, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(6).footer()).html(
            Intl.NumberFormat().format(
                api.column(6, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(7).footer()).html(
            Intl.NumberFormat().format(
                api.column(7, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(8).footer()).html(
            Intl.NumberFormat().format(
                api.column(8, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(9).footer()).html(
            Intl.NumberFormat().format(
                api.column(9, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(10).footer()).html(
            Intl.NumberFormat().format(
                api.column(10, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );
    },
});

/*
 * ACTUALIZAR TIPO DE CAMBIO
 */
$(".tablaDatosDia").on("click", ".btnActualizarMes", function () {
    var fecha = $(this).attr("fecha");
    //console.log(fecha);

    var datos = new FormData();
    datos.append("fecha", fecha);

    $.ajax({
        url: "ajax/movimientos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            if (respuesta == "ok") {
                //toast de confirmacion
                Command: toastr["success"]("Se actualizo el TC: " + fecha);

                // swal({
                //     type: "success",
                //     title: "¡Ok!",
                //     text: "¡La información fue Actualizada con éxito!",
                //     showConfirmButton: true,
                //     confirmButtonText: "Cerrar",
                // }).then((result) => {
                //     if (result.value) {
                //         window.location = "datos-dia";
                //     }
                // });
            } else {
                Command: toastr["error"]("No se encontro el tipo de cambio");
            }
        },
    });
});

$(".tablaRangosDias").DataTable({
    ajax: "ajax/movimientos/tabla-rangos-dias.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
    searching: false,
    paging: false,
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "No hay datos disponibles en esta tabla",
        sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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

        $(api.column(1).footer()).html(
            Intl.NumberFormat().format(
                api.column(1, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(2).footer()).html(
            Intl.NumberFormat().format(
                api.column(2, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(3).footer()).html(
            Intl.NumberFormat().format(
                api.column(3, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(4).footer()).html(
            Intl.NumberFormat().format(
                api.column(4, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(5).footer()).html(
            Intl.NumberFormat().format(
                api.column(5, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(6).footer()).html(
            Intl.NumberFormat().format(
                api.column(6, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(7).footer()).html(
            Intl.NumberFormat().format(
                api.column(7, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );

        $(api.column(8).footer()).html(
            Intl.NumberFormat().format(
                api.column(8, { page: "current" }).data().sum().toFixed(2) * -1
            )
        );
    },
});

//Reporte de Salidas
$(".box").on("click", ".btn180dias", function () {
    //console.log('hola mundo');

    window.location = "vistas/reportes_excel/rpt_rango7.php";
});

//Reporte de Salidas
$(".box").on("click", ".btnMontoAno", function () {
    //console.log('hola mundo');

    window.location = "vistas/reportes_excel/rpt_incobanno.php";
});

function actualizarTC() {
    let fecha = $("#btnActTC").attr("fecha");
    //console.log(fecha);

    var datos = new FormData();
    datos.append("fecha", fecha);

    $.ajax({
        url: "ajax/movimientos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            if (respuesta == "ok") {
                Command: toastr["success"](
                    "Se actualizo el TC de hoy: " + fecha
                );
            } else {
                Command: toastr["error"]("No se encontro el tipo de cambio");
            }
        },
    });
}

$("#ModelMov").change(function () {
    var model = $(this).val();

    localStorage.setItem("ModelMov", model);

    window.location = "index.php?ruta=inicio&modelo=" + model;
});
