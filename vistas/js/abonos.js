function periodoAbonosSeleccionado() {
    return {
        anio: $("#anioPeriodoAbonos").val() || "",
        mes: $("#mesPeriodoAbonos").val() || "",
    };
}

function esPeriodoAnioCompletoAbonos(mes) {
    return !mes || mes === "todos" || mes === "0";
}

function leerParamsUrlAbonos() {
    var params = {};
    var query = window.location.search.replace(/^\?/, "");
    if (!query) {
        return params;
    }
    var partes = query.split("&");
    for (var i = 0; i < partes.length; i++) {
        if (!partes[i]) {
            continue;
        }
        var par = partes[i].split("=");
        var clave = decodeURIComponent(par[0] || "");
        var valor = decodeURIComponent((par[1] || "").replace(/\+/g, " "));
        params[clave] = valor;
    }
    return params;
}

function sincronizarUrlFiltrosAbonos() {
    if (!$("#anioPeriodoAbonos").length) {
        return;
    }

    var periodo = periodoAbonosSeleccionado();
    var motivo = $("#filtroMotivoAbono").val() || "";
    var actual = leerParamsUrlAbonos();
    var partes = [];

    for (var clave in actual) {
        if (!Object.prototype.hasOwnProperty.call(actual, clave)) {
            continue;
        }
        if (clave === "anio" || clave === "mes" || clave === "motivo" || clave === "idAbono") {
            continue;
        }
        partes.push(
            encodeURIComponent(clave) + "=" + encodeURIComponent(actual[clave])
        );
    }

    if (periodo.anio) {
        partes.push("anio=" + encodeURIComponent(periodo.anio));
    }
    if (periodo.mes) {
        partes.push("mes=" + encodeURIComponent(periodo.mes));
    }
    if (motivo) {
        partes.push("motivo=" + encodeURIComponent(motivo));
    }

    var path = window.location.pathname;
    var nueva =
        path + (partes.length ? "?" + partes.join("&") : "") + window.location.hash;

    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, "", nueva);
    }
}

function aplicarFiltrosDesdeUrlAbonos() {
    var params = leerParamsUrlAbonos();

    if (params.anio && $("#anioPeriodoAbonos option[value='" + params.anio + "']").length) {
        $("#anioPeriodoAbonos").val(params.anio);
    }
    if (params.mes && $("#mesPeriodoAbonos option[value='" + params.mes + "']").length) {
        $("#mesPeriodoAbonos").val(params.mes);
    }
    if (Object.prototype.hasOwnProperty.call(params, "motivo")) {
        var motivo = params.motivo || "";
        if (motivo === "" || $("#filtroMotivoAbono option[value='" + motivo + "']").length) {
            $("#filtroMotivoAbono").val(motivo);
        }
    }

    if ($.fn.selectpicker) {
        $("#anioPeriodoAbonos, #mesPeriodoAbonos").selectpicker("refresh");
    }
}

function urlTablaAbonos(motivo) {
    var periodo = periodoAbonosSeleccionado();
    var url =
        "ajax/cuentas-corrientes/tabla-abonos.ajax.php?perfil=" +
        $("#perfilOculto").val();
    if (motivo) {
        url += "&motivo=" + encodeURIComponent(motivo);
    }
    if (periodo.anio) {
        url += "&anio=" + encodeURIComponent(periodo.anio);
    }
    if (periodo.mes && !esPeriodoAnioCompletoAbonos(periodo.mes)) {
        url += "&mes=" + encodeURIComponent(periodo.mes);
    } else if (esPeriodoAnioCompletoAbonos(periodo.mes)) {
        url += "&mes=todos";
    }
    return url;
}

function formatoMontoAbono(n) {
    var num = Number(n) || 0;
    return (
        "S/." +
        num.toLocaleString("es-PE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

function pctTextoAbono(valor) {
    if (valor === null || valor === undefined) {
        return "—";
    }
    return valor + "%";
}

function pintarStatsCabeceraAbonos(respuesta) {
    var mes = respuesta.del_mes || {};
    var anio = respuesta.acumulado_anio || {};
    var mesNombre = respuesta.mes_nombre || "";
    var anioNum = respuesta.anio || "";
    var anioCompleto = !!respuesta.periodo_anio_completo;
    var etiquetaPeriodo = anioCompleto ? "Año" : "Mes";

    $("#subtituloPeriodoAbonos").text(
        anioCompleto ? "Todo " + anioNum : mesNombre + " " + anioNum
    );
    $("#lblPeriodoPendientes").text(etiquetaPeriodo + " · pendientes");
    $("#lblPeriodoAplicados").text(etiquetaPeriodo + " · aplicados");
    $("#lblPeriodoPct").text(etiquetaPeriodo + " · % pend.");

    $("#statMesPendientes").text(
        mes.pendientes_cant !== undefined ? mes.pendientes_cant : "—"
    );
    $("#statMesPendientesMonto").text(formatoMontoAbono(mes.pendientes_monto));
    $("#statMesAplicados").text(
        mes.aplicados_cant !== undefined ? mes.aplicados_cant : "—"
    );
    $("#statMesAplicadosMonto").text(formatoMontoAbono(mes.aplicados_monto));
    $("#statMesPct").text(pctTextoAbono(mes.pct_pendiente));
    $("#statMesTotal").text(
        "Total: " + (mes.total_cant !== undefined ? mes.total_cant : "—")
    );

    $("#statAnioPendientes").text(
        anio.pendientes_cant !== undefined ? anio.pendientes_cant : "—"
    );
    $("#statAnioPendientesMonto").text(formatoMontoAbono(anio.pendientes_monto));
    $("#statAnioAplicados").text(
        anio.aplicados_cant !== undefined ? anio.aplicados_cant : "—"
    );
    $("#statAnioAplicadosMonto").text(formatoMontoAbono(anio.aplicados_monto));
    $("#statAnioPct").text(pctTextoAbono(anio.pct_pendiente));
    $("#statAnioTotal").text(
        "Total: " + (anio.total_cant !== undefined ? anio.total_cant : "—")
    );
}

function cargarEstadisticasAbonos() {
    if (!$("#statMesPendientes").length) {
        return;
    }

    var periodo = periodoAbonosSeleccionado();
    var datos = new FormData();
    datos.append("estadisticasMensualesAbonos", "1");
    datos.append("anioEstadistica", periodo.anio);
    datos.append("mesEstadistica", periodo.mes);

    $("#statMesPendientes, #statMesAplicados, #statMesPct").text("…");
    $("#statAnioPendientes, #statAnioAplicados, #statAnioPct").text("…");

    $.ajax({
        url: "ajax/abonos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            pintarStatsCabeceraAbonos(respuesta || {});
        },
        error: function () {
            $("#statMesPendientes, #statMesAplicados, #statMesPct").text("—");
            $("#statAnioPendientes, #statAnioAplicados, #statAnioPct").text("—");
            $("#subtituloPeriodoAbonos").text("No se pudo cargar");
        },
    });
}

function cargarTablaAbonos(motivo) {
    if ($.fn.DataTable.isDataTable(".tablaAbonos")) {
        $(".tablaAbonos").DataTable().destroy();
    }

    $(".tablaAbonos").DataTable({
        ajax: urlTablaAbonos(motivo),
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "DESC"]],
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

function recargarPeriodoAbonos() {
    sincronizarUrlFiltrosAbonos();
    cargarEstadisticasAbonos();
    if ($(".tablaAbonos").length) {
        cargarTablaAbonos($("#filtroMotivoAbono").val() || "");
    }
}

if ($(".tablaAbonos").length) {
    aplicarFiltrosDesdeUrlAbonos();
    recargarPeriodoAbonos();
}

$("#anioPeriodoAbonos, #mesPeriodoAbonos").on("change", function () {
    recargarPeriodoAbonos();
});

$("#filtroMotivoAbono").on("change", function () {
    sincronizarUrlFiltrosAbonos();
    cargarTablaAbonos($(this).val() || "");
});

$(document).on("click", ".btnReporteAbonos", function () {
    var periodo = periodoAbonosSeleccionado();
    var motivo = $("#filtroMotivoAbono").val() || "";
    var url = "vistas/reportes_excel/rpt_abonos.php?anio=" + encodeURIComponent(periodo.anio || "");
    if (periodo.mes) {
        url += "&mes=" + encodeURIComponent(periodo.mes);
    }
    if (motivo) {
        url += "&motivo=" + encodeURIComponent(motivo);
    }
    window.location = url;
});

$(window).on("popstate", function () {
    if (!$(".tablaAbonos").length) {
        return;
    }
    aplicarFiltrosDesdeUrlAbonos();
    cargarEstadisticasAbonos();
    cargarTablaAbonos($("#filtroMotivoAbono").val() || "");
});

/*=============================================
EDITAR ABONO
=============================================*/
$(".tablaAbonos").on("click", ".btnEditarAbono", function () {
    var idAbono = $(this).attr("idAbono");
    var datos = new FormData();
    datos.append("idAbono", idAbono);

    $.ajax({
        url: "ajax/abonos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idAbono").val(respuesta["id"]);
            $("#editarFecha").val(respuesta["fecha"]);
            $("#editarDescripcion").val(respuesta["descripcion"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarAgencia").val(respuesta["agencia"]);
            $("#editarOpe").val(respuesta["num_ope"]);
        },
    });
});

/*=============================================
MOTIVO / OBSERVACIÓN PENDIENTE
=============================================*/
$(".tablaAbonos").on("click", ".btnMotivoAbono", function () {
    var idAbono = $(this).attr("idAbono");
    var datos = new FormData();
    datos.append("idAbono", idAbono);

    $("#idAbonoMotivo").val(idAbono);
    $("#motivoPendiente").val("");
    $("#observacionPendiente").val("");
    $("#motivoMetaAbono").hide().text("");

    $.ajax({
        url: "ajax/abonos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idAbonoMotivo").val(respuesta["id"]);
            $("#motivoPendiente").val(
                respuesta["motivo_pendiente"] ? respuesta["motivo_pendiente"] : ""
            );
            $("#observacionPendiente").val(
                respuesta["observacion_pendiente"]
                    ? respuesta["observacion_pendiente"]
                    : ""
            );

            if (respuesta["motivo_usuario"] || respuesta["motivo_fecha"]) {
                var meta = "Último cambio";
                if (respuesta["motivo_usuario"]) {
                    meta += ": " + respuesta["motivo_usuario"];
                }
                if (respuesta["motivo_fecha"]) {
                    meta += " — " + respuesta["motivo_fecha"];
                }
                $("#motivoMetaAbono").text(meta).show();
            }
        },
    });
});

$("#btnGuardarMotivoAbono").on("click", function () {
    var idAbono = $("#idAbonoMotivo").val();
    if (!idAbono) {
        if (typeof toastr !== "undefined") {
            Command: toastr["error"]("No hay abono seleccionado");
        }
        return;
    }

    var datos = new FormData();
    datos.append("guardarMotivoPendiente", "1");
    datos.append("idAbonoMotivo", idAbono);
    datos.append("motivoPendiente", $("#motivoPendiente").val() || "");
    datos.append("observacionPendiente", $("#observacionPendiente").val() || "");

    $.ajax({
        url: "ajax/abonos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta && respuesta.ok) {
                $("#modalMotivoAbono").modal("hide");
                if (typeof toastr !== "undefined") {
                    Command: toastr["success"](
                        respuesta.mensaje || "Motivo guardado"
                    );
                }
                cargarTablaAbonos($("#filtroMotivoAbono").val() || "");
            } else {
                var msg =
                    respuesta && respuesta.mensaje
                        ? respuesta.mensaje
                        : "No se pudo guardar";
                if (typeof toastr !== "undefined") {
                    Command: toastr["error"](msg);
                } else {
                    alert(msg);
                }
            }
        },
        error: function () {
            if (typeof toastr !== "undefined") {
                Command: toastr["error"]("Error al guardar el motivo");
            }
        },
    });
});

/*=============================================
ELIMINAR ABONO
=============================================*/
$(".tablaAbonos").on("click", ".btnEliminarAbono", function () {
    var idAbono = $(this).attr("idAbono");

    swal({
        title: "¿Está seguro de borrar el abono?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar abono!",
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=abonos&idAbono=" + idAbono;
        }
    });
});
//Reporte de Colores
// $(".box").on("click", ".btnReporteColor", function () {
//     window.location = "vistas/reportes_excel/rpt_color.php";

// })
/*
 * Remover localstorage con btn Actualizar
 */
$("#btnRecargar").click(function () {
    localStorage.removeItem("saldo");
    localStorage.clear();
    $(".chkAbono").prop("checked", false);
});
/*
 * validar el checkbox
 */
$(".tablaAbonosCancelar").on("click", ".chkAbono", function () {
    $(".tablaCuentasCancelar").DataTable().destroy();
    var saldo = $(this).attr("saldo");
    var idAbono = $(this).attr("idAbono");
    var fecAbono = $(this).attr("fecAbono");
    var opAbono = $(this).attr("opAbono");
    $(".btnCancelarAbono").attr("idAbono", idAbono);
    $(".btnCancelarAbono").attr("opAbono", opAbono);
    localStorage.setItem("saldo", saldo);
    localStorage.setItem("fecAbono", fecAbono);
    localStorage.setItem("opAbono", opAbono);
    cargarTablaCuentasCancelar(localStorage.getItem("saldo"));
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("saldo") != null) {
    cargarTablaCuentasCancelar(localStorage.getItem("saldo"));
    // console.log("lleno");
} else {
    cargarTablaCuentasCancelar(null);
    // console.log("vacio");
}

function urlTablaAbonosCancelar(motivo) {
    var url =
        "ajax/cuentas-corrientes/tabla-abonos-cancelar.ajax.php?perfil=" +
        $("#perfilOculto").val();
    if (motivo) {
        url += "&motivo=" + encodeURIComponent(motivo);
    }
    return url;
}

function cargarTablaAbonosCancelar(motivo) {
    if (!$(".tablaAbonosCancelar").length) {
        return;
    }
    if ($.fn.DataTable.isDataTable(".tablaAbonosCancelar")) {
        $(".tablaAbonosCancelar").DataTable().destroy();
    }

    $(".tablaAbonosCancelar").DataTable({
        ajax: urlTablaAbonosCancelar(motivo),
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

if ($(".tablaAbonosCancelar").length) {
    cargarTablaAbonosCancelar($("#filtroMotivoCancelar").val() || "");
}

$("#filtroMotivoCancelar").on("change", function () {
    cargarTablaAbonosCancelar($(this).val() || "");
});

function cargarTablaCuentasCancelar(saldo) {
    $(".tablaCuentasCancelar").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas-cancelar.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&saldo=" +
            saldo,
        deferRender: true,
        retrieve: true,
        processing: true,
        ordering: false,
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
 * validar el checkbox
 */
$(".tablaCuentasCancelar").on("click", ".chkCancelar", function () {
    var idCuenta = $(this).attr("idCuenta");
    $(".btnCancelarAbono").attr("idCuenta", idCuenta);
});

/*
 * CONFIRMAR CANCELACIÓN DE ABONO
 */
$(".btnCancelarAbono").click(function () {
    console.log("click");
    var idCuenta = $(this).attr("idCuenta");

    if (!idCuenta) {
        //toastr.error("No hay idCuenta para cancelar el abono");
        Command: toastr["error"]("No hay selección para cancelar el abono");
        return;
    }

    var idAbono = $(this).attr("idAbono");
    var opAbono = $(this).attr("opAbono");
    console.log("🚀 ~ opAbono:", opAbono);

    if (!idAbono) {
        console.log("🚀 ~ idAbono:", idAbono);
        //toastr.error("No hay idAbono para cancelar el abono");
        Command: toastr["error"]("No hay selección para cancelar el abono");
        return;
    }

    // Get the account information
    var datos = new FormData();
    datos.append("idCuenta", idCuenta);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // Display the account information in the edit form
            $("#idCuenta4").val(respuesta["id"]);
            $("#editarTipo").val(respuesta["tipo_doc"]);
            $("#editarCuenta").val(respuesta["num_cta"]);
            $("#editarVendedor").val(respuesta["vendedor"]);
            $("#editarCliente").val(respuesta["cliente"]);
            $("#editarSaldo").val(respuesta["saldo"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarFecha").val(localStorage.getItem("fecAbono"));
            $("#fechaVen").val(respuesta["fecha_ven"]);
            $("#editarAbono").val(localStorage.getItem("saldo"));
            $("#idAbono").val(idAbono);
            $("#opAbono").val(opAbono);
        },
    });
});
