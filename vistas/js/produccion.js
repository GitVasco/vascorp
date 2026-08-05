/*
 * tabla paraa cargar la lista de quincenas
 */
$(".tablaQuincena").DataTable({
    ajax:
        "ajax/produccion/tabla-quincenas.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[5, "desc"]],
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
 * EDITAR QUINCENA
 */
$(".tablaQuincena").on("click", ".btnEditarQuincena", function () {
    var id = $(this).attr("id");
    var datos = new FormData();

    datos.append("id", id);
    $.ajax({
        url: "ajax/quincena.ajax.php",
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
            $("#editarQuincena").val(respuesta["nquincena"]);
            $("#editarQuincena").selectpicker("refresh");
            $("#editarInicio").val(respuesta["inicio"]);
            $("#editarFin").val(respuesta["fin"]);
        },
    });
});

/*
 * BOTON VER EFICIENCIA
 */
$(".tablaQuincena").on("click", ".btnEficiencia", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var nquincena = $(this).attr("nquincena");
    var id = $(this).attr("id");
    console.log(inicio, fin, nquincena, id);

    localStorage.setItem("inicio", inicio);
    localStorage.setItem("fin", fin);
    localStorage.setItem("nquincena", nquincena);
    localStorage.setItem("id", id);

    window.location =
        "index.php?ruta=eficiencia&inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&nquincena=" +
        nquincena +
        "&id=" +
        id;
});

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("sectorEfi") != null) {
    cargarEficiencia(
        localStorage.getItem("inicio"),
        localStorage.getItem("fin"),
        localStorage.getItem("nquincena"),
        localStorage.getItem("id"),
        localStorage.getItem("sectorEfi")
    );
} else {
    cargarEficiencia(
        localStorage.getItem("inicio"),
        localStorage.getItem("fin"),
        localStorage.getItem("nquincena"),
        localStorage.getItem("id"),
        null
    );
}

function cargarEficiencia(inicio, fin, nquincena, id, sectorEfi) {
    $(".tablaEficiencia").DataTable({
        ajax:
            "ajax/produccion/tabla-eficiencia.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&inicio=" +
            inicio +
            "&fin=" +
            fin +
            "&nquincena=" +
            nquincena +
            "&id=" +
            id +
            "&sectorEfi=" +
            sectorEfi,
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

//Reporte de Eficiencias
$(".box").on("click", ".btnReporteEficiencia", function () {
    inicio = $(this).attr("inicio");
    fin = $(this).attr("fin");
    quincena = $(this).attr("quincena");
    id = $(this).attr("id");
    window.location =
        "vistas/reportes_excel/rpt_eficiencia.php?inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&quincena=" +
        quincena +
        "&id=" +
        id;
});

/*
 * BOTON VER PAGOS
 */
$(".tablaQuincena").on("click", ".btnPagos", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var nquincena = $(this).attr("nquincena");
    var id = $(this).attr("id");
    //console.log(inicio, fin, nquincena, id);

    localStorage.setItem("inicio", inicio);
    localStorage.setItem("fin", fin);
    localStorage.setItem("nquincena", nquincena);
    localStorage.setItem("id", id);

    window.location =
        "index.php?ruta=pagos&inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&nquincena=" +
        nquincena +
        "&id=" +
        id;
});
// Cargar tabla de pagos (vista /pagos)
$(function () {
    if (window.location.href.indexOf("ruta=pagos") === -1) {
        return;
    }

    var paramsPagos = new URLSearchParams(window.location.search);
    var inicioPagos =
        paramsPagos.get("inicio") || localStorage.getItem("inicio");
    var finPagos = paramsPagos.get("fin") || localStorage.getItem("fin");
    var nquincenaPagos =
        paramsPagos.get("nquincena") || localStorage.getItem("nquincena");
    var idPagos = paramsPagos.get("id") || localStorage.getItem("id");
    var sectorPagos = localStorage.getItem("sectorTra");

    cargarPagos(inicioPagos, finPagos, nquincenaPagos, idPagos, sectorPagos);
    refrescarSelectTrabajadorDetalle();
});

function refrescarSelectTrabajadorDetalle() {
    var $sel = $("#selectTrabajadorDetalle");
    if (!$sel.length || typeof $sel.selectpicker !== "function") {
        return;
    }
    if ($sel.data("selectpicker")) {
        $sel.selectpicker("refresh");
    } else {
        $sel.selectpicker({ liveSearch: true, size: 10 });
    }
}

function cargarPagos(inicio, fin, nquincena, id, sectorTra) {
    if (!inicio || !fin || !nquincena || !id) {
        return;
    }

    if (sectorTra === null || sectorTra === undefined) {
        sectorTra = "null";
    }

    if ($.fn.DataTable.isDataTable(".tablaPagos")) {
        $(".tablaPagos").DataTable().clear().destroy();
    }

    var tablaPagos = $(".tablaPagos").DataTable({
        ajax: {
            url:
                "ajax/produccion/tabla-pagos.ajax.php?perfil=" +
                encodeURIComponent($("#perfilOculto").val() || "") +
                "&inicio=" +
                encodeURIComponent(inicio) +
                "&fin=" +
                encodeURIComponent(fin) +
                "&nquincena=" +
                encodeURIComponent(nquincena) +
                "&id=" +
                encodeURIComponent(id) +
                "&sectorTra=" +
                encodeURIComponent(sectorTra),
            error: function (xhr, status) {
                console.error("Error cargando pagos:", status, xhr.responseText);
                alert(
                    "No se pudo cargar la tabla de pagos. Revise la consola o intente de nuevo."
                );
            },
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[25, "desc"]],
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
            if (api.rows({ page: "all" }).count() === 0) {
                return;
            }
            var colCount = api.columns().count();
            if (colCount < 26) {
                return;
            }
            $(api.column(23).footer()).html(
                api.column(23, { page: "all" }).data().sum().toFixed(2)
            );
            $(api.column(24).footer()).html(
                api.column(24, { page: "all" }).data().sum().toFixed(2)
            );
            $(api.column(25).footer()).html(
                api.column(25, { page: "all" }).data().sum().toFixed(2)
            );
        },
    });

    tablaPagos.on("xhr.dt", function (e, settings, json) {
        var $sel = $("#selectTrabajadorDetalle");
        $sel.val("");
        $sel.find("option:not(:first)").remove();
        if (!json || !json.data) {
            refrescarSelectTrabajadorDetalle();
            return;
        }
        json.data.forEach(function (row) {
            var cod = row[0];
            var nom = String(row[1]).replace(/<[^>]*>/g, "").trim();
            $sel.append(
                $("<option></option>").val(cod).text(cod + " - " + nom)
            );
        });
        refrescarSelectTrabajadorDetalle();
    });

    return tablaPagos;
}

function abrirDetalleProduccionTrabajador(inicio, fin, trabajador, sectorTra) {
    if (!trabajador) {
        alert("Seleccione un trabajador.");
        return;
    }
    var url =
        "vistas/reportes_excel/rpt_detalle_produccion_trabajador.php?inicio=" +
        encodeURIComponent(inicio) +
        "&fin=" +
        encodeURIComponent(fin) +
        "&trabajador=" +
        encodeURIComponent(trabajador);
    if (sectorTra && sectorTra !== "null") {
        url += "&sector=" + encodeURIComponent(sectorTra);
    }
    window.location = url;
}

function abrirDetalleProduccionTodos(inicio, fin, sectorTra) {
    var url =
        "vistas/reportes_excel/rpt_detalle_produccion_todos.php?inicio=" +
        encodeURIComponent(inicio) +
        "&fin=" +
        encodeURIComponent(fin);
    if (sectorTra && sectorTra !== "null") {
        url += "&sector=" + encodeURIComponent(sectorTra);
    }
    window.location = url;
}

$(".box").on("click", ".btnDetalleProduccionTrabajador", function () {
    abrirDetalleProduccionTrabajador(
        $(this).attr("inicio"),
        $(this).attr("fin"),
        $("#selectTrabajadorDetalle").val(),
        localStorage.getItem("sectorTra")
    );
});

$(".box").on("click", ".btnDetalleProduccionTodos", function () {
    abrirDetalleProduccionTodos(
        $(this).attr("inicio"),
        $(this).attr("fin"),
        localStorage.getItem("sectorTra")
    );
});
/*
 * BOTON REPORTE DE PAGOS DE TRUSAS
 */
$(".tablaQuincena").on("click", ".btnReportePagosTrusas", function () {
    inicio = $(this).attr("inicio");
    fin = $(this).attr("fin");
    id = $(this).attr("id");

    window.location =
        "vistas/reportes_excel/rpt_pagos_trusas.php?inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&id=" +
        id;
});

$(".tablaQuincena, .box").on("click", ".btnReportePagosTrusasProduccion", function () {
    inicio = $(this).attr("inicio");
    fin = $(this).attr("fin");
    id = $(this).attr("id");

    window.location =
        "vistas/reportes_excel/rpt_pagos_trusas_produccion.php?inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&id=" +
        id;
});

/*
 * BOTON REPORTE DE PAGOS DE BRASIERES
 */
$(".tablaQuincena").on("click", ".btnReportePagosBrasier", function () {
    inicio = $(this).attr("inicio");
    fin = $(this).attr("fin");
    id = $(this).attr("id");

    window.location =
        "vistas/reportes_excel/rpt_pagos_brasier.php?inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&id=" +
        id;
});

//Reporte de Eficiencias
$(".box").on("click", ".btnReporteEficiencia", function () {
    inicio = $(this).attr("inicio");
    fin = $(this).attr("fin");
    quincena = $(this).attr("quincena");
    id = $(this).attr("id");
    window.location =
        "vistas/reportes_excel/rpt_eficiencia.php?inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&quincena=" +
        quincena +
        "&id=" +
        id;
});

/*=============================================
ELIMINAR QUICENA
=============================================*/

$(".tablaQuincena tbody").on(
    "click",
    "button.btnEliminarQuincena",
    function () {
        var idQuincena = $(this).attr("id");
        //console.log("idQuincena", idQuincena);

        swal({
            title: "¿Está seguro de borrar la quincena?",
            text: "¡Si no lo está puede cancelar la accíón!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, borrar quincena!",
        }).then(function (result) {
            if (result.value) {
                window.location =
                    "index.php?ruta=quincena&idQuincena=" + idQuincena;
            }
        });
    }
);

$("#selectSectorTra").change(function () {
    $(".tablaPagos").DataTable().destroy();
    var sectorTra = $(this).val();
    localStorage.setItem("sectorTra", sectorTra);
    cargarPagos(
        localStorage.getItem("inicio"),
        localStorage.getItem("fin"),
        localStorage.getItem("nquincena"),
        localStorage.getItem("id"),
        localStorage.getItem("sectorTra")
    );
});
/*
 * BOTON LIMPIAR SECTOR TRABAJADOR
 */
$(".box").on("click", ".btnLimpiarSectorTra", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var quincena = $(this).attr("quincena");
    var id = $(this).attr("id");
    localStorage.removeItem("sectorTra");
    window.location =
        "index.php?ruta=pagos&inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&quincena=" +
        quincena +
        "&id=" +
        id;
});

$("#selectSectorEfi").change(function () {
    $(".tablaEficiencia").DataTable().destroy();
    var sectorEfi = $(this).val();
    localStorage.setItem("sectorEfi", sectorEfi);
    cargarEficiencia(
        localStorage.getItem("inicio"),
        localStorage.getItem("fin"),
        localStorage.getItem("nquincena"),
        localStorage.getItem("id"),
        localStorage.getItem("sectorEfi")
    );
});
/*
 * BOTON LIMPIAR SECTOR TRABAJADOR
 */
$(".box").on("click", ".btnLimpiarSectorEfi", function () {
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var nquincena = $(this).attr("nquincena");
    var id = $(this).attr("id");
    localStorage.removeItem("sectorEfi");
    window.location =
        "index.php?ruta=eficiencia&inicio=" +
        inicio +
        "&fin=" +
        fin +
        "&nquincena=" +
        nquincena +
        "&id=" +
        id;
});

/*=============================================
Actualizar Precio Servicio
=============================================*/

$(".tablaQuincena tbody").on(
    "click",
    "button.btnActualizarPrecioServicio",
    function () {
        var inicioPrecioTiempo = $(this).attr("inicio");
        var finPrecioTiempo = $(this).attr("fin");
        var datos = new FormData();
        datos.append("inicioPrecioTiempo", inicioPrecioTiempo);
        datos.append("finPrecioTiempo", finPrecioTiempo);
        $.ajax({
            url: "ajax/quincena.ajax.php",
            type: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (respuesta) {
                Command: toastr["success"]("Actualizado exitosamente!");
            },
        });
    }
);

/*=============================================
ELIMINAR QUICENA
=============================================*/

$(".tablaQuincena tbody").on("click", "button.btnImprimirAvance", function () {
    var inicioQuincena = $(this).attr("inicio");
    var finQuincena = $(this).attr("fin");
    //console.log("idQuincena", idQuincena);

    window.location =
        "index.php?ruta=quincena&inicioQuincena=" +
        inicioQuincena +
        "&finQuincena=" +
        finQuincena;
});

function marcarBotonTallerEG(tallerEG) {
    $(".btnTallerEG, .btnTotT").removeClass("btn-info").addClass("btn-default");
    if (tallerEG == null || tallerEG === "null") {
        $(".btnTotT").removeClass("btn-default").addClass("btn-info");
    } else {
        $(".btnTallerEG[value='" + tallerEG + "']")
            .removeClass("btn-default")
            .addClass("btn-info");
    }
}

if (localStorage.getItem("tallerEG") != null) {
    cargarTablaEficienciaGlobal(localStorage.getItem("tallerEG"));
    marcarBotonTallerEG(localStorage.getItem("tallerEG"));
} else {
    var tallerEG = "null";
    marcarBotonTallerEG(tallerEG);
    cargarTablaEficienciaGlobal(tallerEG);
}

function cargarTablaEficienciaGlobal(tallerEG) {
    $(".tablaEficienciaGlobal").DataTable({
        ajax:
            "ajax/produccion/tabla-eficiencia-global.ajax.php?tallerEG=" +
            tallerEG,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[15, "desc"]],
        pageLength: 40,
        lengthMenu: [
            [40, 80, 100, -1],
            [40, 80, 100, "Todos"],
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
            if (data[3].substr(29, 1) == "f") {
                $("td", row).eq(3).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[3].substr(29, 1) == "h") {
                $("td", row).eq(3).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[3].substr(29, 1) == "t") {
                $("td", row).eq(3).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[3].substr(29, 1) == "q") {
                $("td", row).eq(3).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[4].substr(29, 1) == "f") {
                $("td", row).eq(4).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[4].substr(29, 1) == "h") {
                $("td", row).eq(4).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[4].substr(29, 1) == "t") {
                $("td", row).eq(4).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[4].substr(29, 1) == "q") {
                $("td", row).eq(4).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[5].substr(29, 1) == "f") {
                $("td", row).eq(5).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[5].substr(29, 1) == "h") {
                $("td", row).eq(5).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[5].substr(29, 1) == "t") {
                $("td", row).eq(5).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[5].substr(29, 1) == "q") {
                $("td", row).eq(5).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[6].substr(29, 1) == "f") {
                $("td", row).eq(6).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[6].substr(29, 1) == "h") {
                $("td", row).eq(6).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[6].substr(29, 1) == "t") {
                $("td", row).eq(6).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[6].substr(29, 1) == "q") {
                $("td", row).eq(6).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[7].substr(29, 1) == "f") {
                $("td", row).eq(7).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[7].substr(29, 1) == "h") {
                $("td", row).eq(7).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[7].substr(29, 1) == "t") {
                $("td", row).eq(7).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[7].substr(29, 1) == "q") {
                $("td", row).eq(7).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[8].substr(29, 1) == "f") {
                $("td", row).eq(8).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[8].substr(29, 1) == "h") {
                $("td", row).eq(8).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[8].substr(29, 1) == "t") {
                $("td", row).eq(8).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[8].substr(29, 1) == "q") {
                $("td", row).eq(8).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[9].substr(29, 1) == "f") {
                $("td", row).eq(9).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[9].substr(29, 1) == "h") {
                $("td", row).eq(9).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[9].substr(29, 1) == "t") {
                $("td", row).eq(9).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[9].substr(29, 1) == "q") {
                $("td", row).eq(9).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[10].substr(29, 1) == "f") {
                $("td", row).eq(10).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[10].substr(29, 1) == "h") {
                $("td", row).eq(10).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[10].substr(29, 1) == "t") {
                $("td", row).eq(10).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[10].substr(29, 1) == "q") {
                $("td", row).eq(10).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[11].substr(29, 1) == "f") {
                $("td", row).eq(11).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[11].substr(29, 1) == "h") {
                $("td", row).eq(11).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[11].substr(29, 1) == "t") {
                $("td", row).eq(11).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[11].substr(29, 1) == "q") {
                $("td", row).eq(11).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[12].substr(29, 1) == "f") {
                $("td", row).eq(12).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[12].substr(29, 1) == "h") {
                $("td", row).eq(12).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[12].substr(29, 1) == "t") {
                $("td", row).eq(12).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[12].substr(29, 1) == "q") {
                $("td", row).eq(12).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[13].substr(29, 1) == "f") {
                $("td", row).eq(13).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[13].substr(29, 1) == "h") {
                $("td", row).eq(13).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[13].substr(29, 1) == "t") {
                $("td", row).eq(13).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[13].substr(29, 1) == "q") {
                $("td", row).eq(13).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[14].substr(29, 1) == "f") {
                $("td", row).eq(14).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[14].substr(29, 1) == "h") {
                $("td", row).eq(14).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[14].substr(29, 1) == "t") {
                $("td", row).eq(14).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[14].substr(29, 1) == "q") {
                $("td", row).eq(14).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }

            if (data[15].substr(29, 1) == "f") {
                $("td", row).eq(15).css({
                    "background-color": "#DDFFDF",
                    color: "black",
                });
            } else if (data[15].substr(29, 1) == "h") {
                $("td", row).eq(15).css({
                    "background-color": "#FFE7D8",
                    color: "black",
                });
            } else if (data[15].substr(29, 1) == "t") {
                $("td", row).eq(15).css({
                    "background-color": "#FFFFC7",
                    color: "black",
                });
            } else if (data[15].substr(29, 1) == "q") {
                $("td", row).eq(15).css({
                    "background-color": "#FFCECE",
                    color: "black",
                });
            }
        },
    });
}

$(document).on("click", ".btnTallerEG", function () {
    var tallerEG = $(this).val();
    localStorage.setItem("tallerEG", tallerEG);
    $(".tablaEficienciaGlobal").DataTable().destroy();
    cargarTablaEficienciaGlobal(tallerEG);
    marcarBotonTallerEG(tallerEG);
});

$(document).on("click", ".btnTotT", function () {
    var tallerEG = $(this).val();
    localStorage.setItem("tallerEG", tallerEG);
    $(".tablaEficienciaGlobal").DataTable().destroy();
    cargarTablaEficienciaGlobal(tallerEG);
    marcarBotonTallerEG(tallerEG);
});

// GUARDAR N° GUÍA DEL INGRESO
$(document).on("click", "#btnGuardarGuiaIngreso", function () {
    var documento = $("#codigoIngreso").val();
    var guia = $.trim($("#editarGuiaIngreso").val());

    if (!documento) {
        return;
    }

    var $btn = $(this);
    $btn.prop("disabled", true);

    var datos = new FormData();
    datos.append("documentoGuia", documento);
    datos.append("nuevaGuia", guia);

    $.ajax({
        url: "ajax/ingresos.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            $btn.prop("disabled", false);
            if ($.trim(respuesta) === "ok") {
                if (typeof toastr !== "undefined") {
                    Command: toastr["success"]("Guía actualizada");
                } else {
                    swal({
                        type: "success",
                        title: "Listo",
                        text: "La guía se actualizó correctamente.",
                        confirmButtonText: "Cerrar",
                    });
                }
            } else {
                swal({
                    type: "error",
                    title: "Error",
                    text: "No se pudo actualizar la guía.",
                    confirmButtonText: "Cerrar",
                });
            }
        },
        error: function () {
            $btn.prop("disabled", false);
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo actualizar la guía.",
                confirmButtonText: "Cerrar",
            });
        },
    });
});

$(document).on("keydown", "#editarGuiaIngreso", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        $("#btnGuardarGuiaIngreso").click();
    }
});

// EDITAR OPERACIÓN
$(".tablaEditarDetalleIngreso ").on(
    "click",
    "button.btnEditarDetalleIngreso",
    function () {
        var codigo = $(this).attr("codigo");
        var articulo = $(this).attr("articulo");
        var modelo = $(this).attr("modelo");
        var nombre = $(this).attr("nombre");
        var color = $(this).attr("color");
        var talla = $(this).attr("talla");
        var cantidad = $(this).attr("cantidad");
        var saldo = $(this).attr("saldo");
        var idcierre = $(this).attr("idcierre");
        var sector = $(this).attr("sector");
        var almacen = $(this).attr("almacen");

        var maximo = Number(cantidad) + Number(saldo);
        document.getElementById("cantidad").setAttribute("max", maximo);

        $("#articulo").val(articulo);
        $("#modelo").val(modelo);
        $("#nombre").val(nombre);
        $("#color").val(color);
        $("#talla").val("T - " + talla);
        $("#cantidad").val(cantidad);
        $("#saldo").val(saldo);
        $("#cantidadO").val(cantidad);
        $("#saldoO").val(saldo);
        $("#codigo").val(codigo);
        $("#idcierre").val(idcierre);
        $("#sector").val(sector);
        $("#almacen").val(almacen);
    }
);

$("#cantidad").change(function () {
    const cantidadO = document.getElementById("cantidadO").value;

    const cantidad = document.getElementById("cantidad").value;
    const saldo = document.getElementById("saldoO").value;

    const cantTotal = Number(cantidadO) + Number(saldo);

    let nuevoSaldo = cantTotal - Number(cantidad);

    $("#saldo").val(nuevoSaldo);
});

/*
 * BORRAR LA TARJETA
 */
$(".tablaEditarDetalleIngreso").on(
    "click",
    ".btnEliminarDetalleIngreso",
    function () {
        var documento = $(this).attr("codigo");
        var articulo = $(this).attr("iddetalle");
        var cantidad = $(this).attr("cantidad");

        var datos = new FormData();
        datos.append("documento", documento);
        datos.append("articulo", articulo);
        datos.append("cantidad", cantidad);

        $.ajax({
            url: "ajax/ingresos.ajax.php",
            type: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (respuesta) {
                var params = new URLSearchParams(window.location.search);
                var idIngreso = params.get("idIngreso") || documento;
                var sector = params.get("sector");
                var urlEditar =
                    "index.php?ruta=editar-ingreso&idIngreso=" +
                    encodeURIComponent(idIngreso);
                if (sector) {
                    urlEditar += "&sector=" + encodeURIComponent(sector);
                }

                swal({
                    type: "success",
                    title: "¡Ok!",
                    text: "¡La información fue Eliminada con éxito!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar",
                }).then((result) => {
                    if (result.value) {
                        window.location = urlEditar;
                    }
                });
            },
        });
    }
);

/*
 * tabla paraa cargar la lista de quincenas
 */
$(".tablaAjusteTaller").DataTable({
    ajax: "ajax/produccion/tabla-ajuste-taller.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[5, "desc"]],
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
