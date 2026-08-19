/* Utilidades */

var utStock01Items = [];
var utServicioItems = [];

$(function () {
    $(".ut-info[data-toggle='popover']").popover({
        container: "body"
    });
});

function utFmtNum(n) {
    var x = Number(n);
    if (isNaN(x)) {
        return "0";
    }
    return x.toLocaleString("es-PE", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4
    });
}

function utEscape(s) {
    return String(s == null ? "" : s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function utMostrarCarga(msg) {
    $("#utOverlayMsg").text(msg || "Procesando…");
    $("#utOverlay").addClass("is-on").attr("aria-hidden", "false");
}

function utOcultarCarga() {
    $("#utOverlay").removeClass("is-on").attr("aria-hidden", "true");
}

function utSetBtnLoading($btn, loading, htmlIdle) {
    if (!$btn || !$btn.length) {
        return;
    }
    if (loading) {
        if (!$btn.data("ut-html")) {
            $btn.data("ut-html", $btn.html());
        }
        $btn.prop("disabled", true)
            .html('<i class="fa fa-spinner fa-spin"></i> Calculando…');
    } else {
        $btn.prop("disabled", false)
            .html(htmlIdle || $btn.data("ut-html") || $btn.html());
    }
}

/* ---- Stock almacén 01 ---- */

function utActualizarBotonStock01() {
    var n = $("#utStock01Table tbody input.ut-stock01-chk:checked").length;
    $("#btnUtActualizarStock01").prop("disabled", n < 1);
    $("#utStock01Count").text(n + " seleccionado(s)");
}

function utRenderStock01(rows) {
    utStock01Items = rows || [];
    var $tb = $("#utStock01Table tbody");
    $tb.empty();

    if (!utStock01Items.length) {
        $("#utStock01TableWrap").hide();
        $("#utStock01Empty").show();
        $("#btnUtActualizarStock01").prop("disabled", true);
        return;
    }

    $("#utStock01Empty").hide();
    $("#utStock01TableWrap").show();

    var html = "";
    for (var i = 0; i < utStock01Items.length; i++) {
        var r = utStock01Items[i];
        var diff = Number(r.diferencia) || 0;
        var cls = diff < 0 ? "ut-diff-neg" : (diff > 0 ? "ut-diff-pos" : "");
        html += "<tr data-idx=\"" + i + "\">" +
            "<td><input type=\"checkbox\" class=\"ut-stock01-chk\" checked></td>" +
            "<td>" + utEscape(r.articulo) + "</td>" +
            "<td>" + utEscape(r.modelo) + "</td>" +
            "<td>" + utEscape(r.color) + "</td>" +
            "<td>" + utEscape(r.talla) + "</td>" +
            "<td class=\"ut-nombre\">" + utEscape(r.nombre) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.ingresos) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.salidas) + "</td>" +
            "<td class=\"text-right\"><strong>" + utFmtNum(r.stock_calculado) + "</strong></td>" +
            "<td class=\"text-right\">" + utFmtNum(r.stock_actual) + "</td>" +
            "<td class=\"text-right " + cls + "\">" + utFmtNum(diff) + "</td>" +
            "</tr>";
    }
    $tb.html(html);
    $("#utStock01CheckAll").prop("checked", true);
    utActualizarBotonStock01();
}

function utCargarDescuadresStock01(opts) {
    opts = opts || {};
    var $btn = $("#btnUtCuadrarStock01");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true);
        utMostrarCarga("Calculando descuadres de stock…");
    } else {
        $("#utStock01Loading").show();
        $("#utStock01Empty").hide();
        $("#utStock01TableWrap").hide();
        $("#btnUtActualizarStock01").prop("disabled", true);
    }

    $("#utStock01Meta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "descuadresStock01" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false);
        } else {
            $("#utStock01Loading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo calcular",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utStock01Meta").text("· " + (resp.anio || "") + " · " + (resp.total || 0) + " descuadre(s)");
        utRenderStock01(resp.data || []);
        $("#modalUtStock01").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false);
        } else {
            $("#utStock01Loading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosStock01() {
    var out = [];
    $("#utStock01Table tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-stock01-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utStock01Items[idx]) {
            return;
        }
        out.push({
            articulo: utStock01Items[idx].articulo,
            stock_calculado: utStock01Items[idx].stock_calculado
        });
    });
    return out;
}

$(document).on("click", "#btnUtCuadrarStock01", function () {
    utCargarDescuadresStock01();
});

$(document).on("change", "#utStock01CheckAll", function () {
    var on = $(this).is(":checked");
    $("#utStock01Table tbody input.ut-stock01-chk").prop("checked", on);
    utActualizarBotonStock01();
});

$(document).on("change", "#utStock01Table tbody input.ut-stock01-chk", function () {
    var total = $("#utStock01Table tbody input.ut-stock01-chk").length;
    var checked = $("#utStock01Table tbody input.ut-stock01-chk:checked").length;
    $("#utStock01CheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonStock01();
});

$(document).on("click", "#btnUtActualizarStock01", function () {
    var items = utItemsSeleccionadosStock01();
    if (!items.length) {
        return;
    }

    var $btn = $("#btnUtActualizarStock01");

    swal({
        title: "¿Actualizar stock?",
        text: "Se actualizarán " + items.length + " artículo(s) con el saldo calculado.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Actualizar seleccionados');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Actualizando…');
        utMostrarCarga("Actualizando stock…");
        $.post("ajax/utilidades.ajax.php", {
            accion: "actualizarStock01",
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                utActualizarBotonStock01();
                return;
            }
            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarDescuadresStock01({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            utActualizarBotonStock01();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Servicio / cierre ---- */

function utActualizarBotonServicio() {
    var n = $("#utServicioTable tbody input.ut-servicio-chk:checked").length;
    $("#btnUtActualizarServicio").prop("disabled", n < 1);
    $("#utServicioCount").text(n + " seleccionado(s)");
}

function utRenderServicio(rows) {
    utServicioItems = rows || [];
    var $tb = $("#utServicioTable tbody");
    $tb.empty();

    if (!utServicioItems.length) {
        $("#utServicioTableWrap").hide();
        $("#utServicioEmpty").show();
        $("#btnUtActualizarServicio").prop("disabled", true);
        return;
    }

    $("#utServicioEmpty").hide();
    $("#utServicioTableWrap").show();

    var html = "";
    for (var i = 0; i < utServicioItems.length; i++) {
        var r = utServicioItems[i];
        var diff = Number(r.diferencia) || 0;
        var cls = diff < 0 ? "ut-diff-neg" : (diff > 0 ? "ut-diff-pos" : "");
        html += "<tr data-idx=\"" + i + "\">" +
            "<td><input type=\"checkbox\" class=\"ut-servicio-chk\" checked></td>" +
            "<td>" + utEscape(r.articulo) + "</td>" +
            "<td>" + utEscape(r.modelo) + "</td>" +
            "<td>" + utEscape(r.color) + "</td>" +
            "<td>" + utEscape(r.talla) + "</td>" +
            "<td class=\"ut-nombre\">" + utEscape(r.nombre) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.servicio_total) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.servicio) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.cierre) + "</td>" +
            "<td class=\"text-right\"><strong>" + utFmtNum(r.servicio_calculado) + "</strong></td>" +
            "<td class=\"text-right " + cls + "\">" + utFmtNum(diff) + "</td>" +
            "</tr>";
    }
    $tb.html(html);
    $("#utServicioCheckAll").prop("checked", true);
    utActualizarBotonServicio();
}

function utCargarDescuadresServicio(opts) {
    opts = opts || {};
    var $btn = $("#btnUtCuadrarServicio");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true);
        utMostrarCarga("Calculando descuadres de servicio…");
    } else {
        $("#utServicioLoading").show();
        $("#utServicioEmpty").hide();
        $("#utServicioTableWrap").hide();
        $("#btnUtActualizarServicio").prop("disabled", true);
    }

    $("#utServicioMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "descuadresServicio" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false);
        } else {
            $("#utServicioLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo calcular",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utServicioMeta").text("· " + (resp.total || 0) + " descuadre(s)");
        utRenderServicio(resp.data || []);
        $("#modalUtServicio").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false);
        } else {
            $("#utServicioLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosServicio() {
    var out = [];
    $("#utServicioTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-servicio-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utServicioItems[idx]) {
            return;
        }
        out.push({
            articulo: utServicioItems[idx].articulo,
            servicio_calculado: utServicioItems[idx].servicio_calculado
        });
    });
    return out;
}

$(document).on("click", "#btnUtCuadrarServicio", function () {
    utCargarDescuadresServicio();
});

$(document).on("change", "#utServicioCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utServicioTable tbody input.ut-servicio-chk").prop("checked", on);
    utActualizarBotonServicio();
});

$(document).on("change", "#utServicioTable tbody input.ut-servicio-chk", function () {
    var total = $("#utServicioTable tbody input.ut-servicio-chk").length;
    var checked = $("#utServicioTable tbody input.ut-servicio-chk:checked").length;
    $("#utServicioCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonServicio();
});

$(document).on("click", "#btnUtActualizarServicio", function () {
    var items = utItemsSeleccionadosServicio();
    if (!items.length) {
        return;
    }

    var $btn = $("#btnUtActualizarServicio");

    swal({
        title: "¿Actualizar servicio?",
        text: "Se actualizarán " + items.length + " artículo(s) con servicio abierto + cierre.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Actualizar seleccionados');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Actualizando…');
        utMostrarCarga("Actualizando servicio…");
        $.post("ajax/utilidades.ajax.php", {
            accion: "actualizarServicio",
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                utActualizarBotonServicio();
                return;
            }
            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarDescuadresServicio({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            utActualizarBotonServicio();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Completar fecha vencimiento cte ---- */

var utFechaVenItems = [];

function utFmtFechaCorta(val) {
    if (!val) {
        return "";
    }
    var s = String(val);
    if (s.length >= 10) {
        return s.substring(0, 10);
    }
    return s;
}

function utActualizarBotonFechaVen() {
    var n = $("#utFechaVenTable tbody input.ut-fechaven-chk:checked").length;
    $("#btnUtCompletarFechaVen").prop("disabled", n < 1);
    $("#utFechaVenCount").text(n + " seleccionado(s)");
}

function utRenderFechaVen(rows) {
    utFechaVenItems = rows || [];
    var $tb = $("#utFechaVenTable tbody");
    $tb.empty();

    if (!utFechaVenItems.length) {
        $("#utFechaVenEmpty").show();
        $("#utFechaVenTableWrap").hide();
        $("#btnUtCompletarFechaVen").prop("disabled", true);
        $("#utFechaVenCount").text("");
        return;
    }

    $("#utFechaVenEmpty").hide();
    $("#utFechaVenTableWrap").show();

    var html = "";
    for (var i = 0; i < utFechaVenItems.length; i++) {
        var r = utFechaVenItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-fechaven-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo_doc || "") + "</td>";
        html += "<td>" + utEscape(r.num_cta || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha_ven_propuesta)) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.monto) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.saldo) + "</td>";
        html += "<td>" + utEscape(r.estado || "") + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utFechaVenCheckAll").prop("checked", true);
    utActualizarBotonFechaVen();
}

function utCargarCteSinFechaVen(opts) {
    opts = opts || {};
    var $btn = $("#btnUtCteSinFechaVen");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-calendar-check-o"></i> Revisar');
        utMostrarCarga("Buscando cargos sin fecha de vencimiento…");
    } else {
        $("#utFechaVenLoading").show();
        $("#utFechaVenEmpty").hide();
        $("#utFechaVenTableWrap").hide();
        $("#btnUtCompletarFechaVen").prop("disabled", true);
    }

    $("#utFechaVenMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "cteSinFechaVen" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-calendar-check-o"></i> Revisar');
        } else {
            $("#utFechaVenLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utFechaVenMeta").text("· " + (resp.total || 0) + " registro(s)");
        utRenderFechaVen(resp.data || []);
        $("#modalUtFechaVen").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-calendar-check-o"></i> Revisar');
        } else {
            $("#utFechaVenLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utIdsSeleccionadosFechaVen() {
    var out = [];
    $("#utFechaVenTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-fechaven-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utFechaVenItems[idx]) {
            return;
        }
        out.push(utFechaVenItems[idx].id);
    });
    return out;
}

$(document).on("click", "#btnUtCteSinFechaVen", function () {
    utCargarCteSinFechaVen();
});

$(document).on("change", "#utFechaVenCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utFechaVenTable tbody input.ut-fechaven-chk").prop("checked", on);
    utActualizarBotonFechaVen();
});

$(document).on("change", "#utFechaVenTable tbody input.ut-fechaven-chk", function () {
    var total = $("#utFechaVenTable tbody input.ut-fechaven-chk").length;
    var checked = $("#utFechaVenTable tbody input.ut-fechaven-chk:checked").length;
    $("#utFechaVenCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonFechaVen();
});

$(document).on("click", "#btnUtCompletarFechaVen", function () {
    var ids = utIdsSeleccionadosFechaVen();
    if (!ids.length) {
        return;
    }

    var $btn = $("#btnUtCompletarFechaVen");
    swal({
        title: "¿Completar fecha de vencimiento?",
        text: "Se pondrá fecha_ven = fecha del documento en " + ids.length + " registro(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Completando fechas…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarFechaVenCte",
            ids: JSON.stringify(ids)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonFechaVen();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarCteSinFechaVen({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonFechaVen();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Completar fecha origen cte (abonos) ---- */

var utFechaOriItems = [];

function utActualizarBotonFechaOri() {
    var n = $("#utFechaOriTable tbody input.ut-fechaori-chk:checked").length;
    $("#btnUtCompletarFechaOri").prop("disabled", n < 1);
    $("#utFechaOriCount").text(n + " seleccionado(s)");
}

function utRenderFechaOri(rows) {
    utFechaOriItems = rows || [];
    var $tb = $("#utFechaOriTable tbody");
    $tb.empty();

    if (!utFechaOriItems.length) {
        $("#utFechaOriEmpty").show();
        $("#utFechaOriTableWrap").hide();
        $("#btnUtCompletarFechaOri").prop("disabled", true);
        $("#utFechaOriCount").text("");
        return;
    }

    $("#utFechaOriEmpty").hide();
    $("#utFechaOriTableWrap").show();

    var html = "";
    for (var i = 0; i < utFechaOriItems.length; i++) {
        var r = utFechaOriItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-fechaori-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo_doc || "") + "</td>";
        html += "<td>" + utEscape(r.num_cta || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha_ori_prop)) + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha_ori_ven_prop)) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.monto) + "</td>";
        html += "<td>" + utEscape(r.estado || "") + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utFechaOriCheckAll").prop("checked", true);
    utActualizarBotonFechaOri();
}

function utCargarCteSinFechaOri(opts) {
    opts = opts || {};
    var $btn = $("#btnUtCteSinFechaOri");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-link"></i> Revisar');
        utMostrarCarga("Buscando abonos sin fecha de origen…");
    } else {
        $("#utFechaOriLoading").show();
        $("#utFechaOriEmpty").hide();
        $("#utFechaOriTableWrap").hide();
        $("#btnUtCompletarFechaOri").prop("disabled", true);
    }

    $("#utFechaOriMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "cteSinFechaOri" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-link"></i> Revisar');
        } else {
            $("#utFechaOriLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        var dias = resp.dias || 60;
        $("#utFechaOriMeta").text("· últimos " + dias + " días · " + (resp.total || 0) + " registro(s)");
        utRenderFechaOri(resp.data || []);
        $("#modalUtFechaOri").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-link"></i> Revisar');
        } else {
            $("#utFechaOriLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utIdsSeleccionadosFechaOri() {
    var out = [];
    $("#utFechaOriTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-fechaori-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utFechaOriItems[idx]) {
            return;
        }
        out.push(utFechaOriItems[idx].id);
    });
    return out;
}

$(document).on("click", "#btnUtCteSinFechaOri", function () {
    utCargarCteSinFechaOri();
});

$(document).on("change", "#utFechaOriCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utFechaOriTable tbody input.ut-fechaori-chk").prop("checked", on);
    utActualizarBotonFechaOri();
});

$(document).on("change", "#utFechaOriTable tbody input.ut-fechaori-chk", function () {
    var total = $("#utFechaOriTable tbody input.ut-fechaori-chk").length;
    var checked = $("#utFechaOriTable tbody input.ut-fechaori-chk:checked").length;
    $("#utFechaOriCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonFechaOri();
});

$(document).on("click", "#btnUtCompletarFechaOri", function () {
    var ids = utIdsSeleccionadosFechaOri();
    if (!ids.length) {
        return;
    }

    var $btn = $("#btnUtCompletarFechaOri");
    swal({
        title: "¿Completar fecha de origen?",
        text: "Se copiará fecha y vencimiento del cargo al abono en " + ids.length + " registro(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Completando fechas de origen…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarFechaOriCte",
            ids: JSON.stringify(ids)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonFechaOri();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarCteSinFechaOri({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonFechaOri();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Completar tipo de cambio cte ---- */

var utTipCambioItems = [];

function utActualizarBotonTipCambio() {
    var n = $("#utTipCambioTable tbody input.ut-tipcambio-chk:checked").length;
    $("#btnUtCompletarTipCambio").prop("disabled", n < 1);
    $("#utTipCambioCount").text(n + " seleccionado(s)");
}

function utRenderTipCambio(rows) {
    utTipCambioItems = rows || [];
    var $tb = $("#utTipCambioTable tbody");
    $tb.empty();

    if (!utTipCambioItems.length) {
        $("#utTipCambioEmpty").show();
        $("#utTipCambioTableWrap").hide();
        $("#btnUtCompletarTipCambio").prop("disabled", true);
        $("#utTipCambioCount").text("");
        return;
    }

    $("#utTipCambioEmpty").hide();
    $("#utTipCambioTableWrap").show();

    var html = "";
    for (var i = 0; i < utTipCambioItems.length; i++) {
        var r = utTipCambioItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-tipcambio-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo_doc || "") + "</td>";
        html += "<td>" + utEscape(r.num_cta || "") + "</td>";
        html += "<td>" + utEscape(r.tip_mov || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.tip_cambio) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.tip_cambio_prop) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.monto) + "</td>";
        html += "<td>" + utEscape(r.estado || "") + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utTipCambioCheckAll").prop("checked", true);
    utActualizarBotonTipCambio();
}

function utCargarCteSinTipCambio(opts) {
    opts = opts || {};
    var $btn = $("#btnUtCteSinTipCambio");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-exchange"></i> Revisar');
        utMostrarCarga("Buscando cuentas sin tipo de cambio…");
    } else {
        $("#utTipCambioLoading").show();
        $("#utTipCambioEmpty").hide();
        $("#utTipCambioTableWrap").hide();
        $("#btnUtCompletarTipCambio").prop("disabled", true);
    }

    $("#utTipCambioMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "cteSinTipCambio" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exchange"></i> Revisar');
        } else {
            $("#utTipCambioLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utTipCambioMeta").text("· " + (resp.anio || "") + " · " + (resp.total || 0) + " registro(s)");
        utRenderTipCambio(resp.data || []);
        $("#modalUtTipCambio").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exchange"></i> Revisar');
        } else {
            $("#utTipCambioLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utIdsSeleccionadosTipCambio() {
    var out = [];
    $("#utTipCambioTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-tipcambio-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utTipCambioItems[idx]) {
            return;
        }
        out.push(utTipCambioItems[idx].id);
    });
    return out;
}

$(document).on("click", "#btnUtCteSinTipCambio", function () {
    utCargarCteSinTipCambio();
});

$(document).on("change", "#utTipCambioCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utTipCambioTable tbody input.ut-tipcambio-chk").prop("checked", on);
    utActualizarBotonTipCambio();
});

$(document).on("change", "#utTipCambioTable tbody input.ut-tipcambio-chk", function () {
    var total = $("#utTipCambioTable tbody input.ut-tipcambio-chk").length;
    var checked = $("#utTipCambioTable tbody input.ut-tipcambio-chk:checked").length;
    $("#utTipCambioCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonTipCambio();
});

$(document).on("click", "#btnUtCompletarTipCambio", function () {
    var ids = utIdsSeleccionadosTipCambio();
    if (!ids.length) {
        return;
    }

    var $btn = $("#btnUtCompletarTipCambio");
    swal({
        title: "¿Actualizar tipo de cambio?",
        text: "Se tomará el cambio de venta del día en " + ids.length + " registro(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Actualizando tipo de cambio…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarTipCambioCte",
            ids: JSON.stringify(ids)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonTipCambio();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarCteSinTipCambio({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonTipCambio();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Pipeline cte (secuencia) ---- */

var utPipelineBusy = false;

function utPipelineResetSteps() {
    $("#utPipelineSteps .ut-pipeline-step").each(function () {
        var $li = $(this);
        $li.removeClass("is-active is-done is-error is-skip");
        $li.find(".ut-pipeline-step__icon").html('<i class="fa fa-circle-o"></i>');
        $li.find(".ut-pipeline-step__msg").text("En espera");
    });
    $("#utPipelineIntro").text("Ejecutando correcciones en orden…");
    $("#btnUtPipelineCerrar").prop("disabled", true);
}

function utPipelineSetStep(step, state, msg) {
    var $li = $('#utPipelineSteps .ut-pipeline-step[data-step="' + step + '"]');
    if (!$li.length) {
        return;
    }
    $li.removeClass("is-active is-done is-error is-skip");
    var icon = '<i class="fa fa-circle-o"></i>';
    if (state === "active") {
        $li.addClass("is-active");
        icon = '<i class="fa fa-spinner fa-spin"></i>';
    } else if (state === "done") {
        $li.addClass("is-done");
        icon = '<i class="fa fa-check-circle"></i>';
    } else if (state === "skip") {
        $li.addClass("is-done is-skip");
        icon = '<i class="fa fa-check-circle"></i>';
    } else if (state === "error") {
        $li.addClass("is-error");
        icon = '<i class="fa fa-times-circle"></i>';
    }
    $li.find(".ut-pipeline-step__icon").html(icon);
    if (msg) {
        $li.find(".ut-pipeline-step__msg").text(msg);
    }
}

function utPostJson(data) {
    return $.ajax({
        url: "ajax/utilidades.ajax.php",
        method: "POST",
        data: data,
        dataType: "json",
        timeout: 300000
    });
}

/**
 * Completa T/C faltantes en totalesjf (API / día previo).
 * setStepFn(step, state, msg) — utPipelineSetStep o utVentaPipelineSetStep
 */
function utPipelinePasoTotalesTipCambio(setStepFn) {
    var setStep = setStepFn || utPipelineSetStep;
    setStep("totalesTc", "active", "Buscando días sin T/C en totales…");
    return utPostJson({ accion: "totalesSinTipCambio" }).then(function (resp) {
        if (!resp || !resp.ok) {
            throw new Error((resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar totales");
        }
        var data = resp.data || [];
        if (!data.length) {
            setStep("totalesTc", "skip", "Sin pendientes");
            return { actualizados: 0, total: 0, omitidos: 0 };
        }
        var fechas = data.map(function (r) { return r.fecha; });
        setStep("totalesTc", "active", "Consultando API para " + fechas.length + " día(s)…");
        return utPostJson({
            accion: "completarTipCambioTotales",
            fechas: JSON.stringify(fechas)
        }).then(function (upd) {
            if (!upd || !upd.ok) {
                throw new Error((upd && upd.mensaje) ? upd.mensaje : "No se pudo completar T/C en totales");
            }
            var n = Number(upd.actualizados) || 0;
            var om = Number(upd.omitidos) || 0;
            var msg = n + " actualizado(s)";
            if (om > 0) {
                msg += ", " + om + " omitido(s)";
            }
            setStep("totalesTc", "done", msg);
            return { actualizados: n, total: fechas.length, omitidos: om };
        });
    });
}

function utPipelinePasoFechaVen() {
    utPipelineSetStep("fechaVen", "active", "Buscando cargos sin vencimiento…");
    return utPostJson({ accion: "cteSinFechaVen" }).then(function (resp) {
        if (!resp || !resp.ok) {
            throw new Error((resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar vencimientos");
        }
        var data = resp.data || [];
        if (!data.length) {
            utPipelineSetStep("fechaVen", "skip", "Sin pendientes");
            return { actualizados: 0, total: 0 };
        }
        var ids = data.map(function (r) { return r.id; });
        utPipelineSetStep("fechaVen", "active", "Completando " + ids.length + " registro(s)…");
        return utPostJson({
            accion: "completarFechaVenCte",
            ids: JSON.stringify(ids)
        }).then(function (upd) {
            if (!upd || !upd.ok) {
                throw new Error((upd && upd.mensaje) ? upd.mensaje : "No se pudo completar vencimientos");
            }
            var n = Number(upd.actualizados) || 0;
            utPipelineSetStep("fechaVen", "done", n + " actualizado(s)");
            return { actualizados: n, total: ids.length };
        });
    });
}

function utPipelinePasoFechaOri() {
    utPipelineSetStep("fechaOri", "active", "Buscando abonos sin origen…");
    return utPostJson({ accion: "cteSinFechaOri" }).then(function (resp) {
        if (!resp || !resp.ok) {
            throw new Error((resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar orígenes");
        }
        var data = resp.data || [];
        if (!data.length) {
            utPipelineSetStep("fechaOri", "skip", "Sin pendientes");
            return { actualizados: 0, total: 0 };
        }
        var ids = data.map(function (r) { return r.id; });
        utPipelineSetStep("fechaOri", "active", "Completando " + ids.length + " registro(s)…");
        return utPostJson({
            accion: "completarFechaOriCte",
            ids: JSON.stringify(ids)
        }).then(function (upd) {
            if (!upd || !upd.ok) {
                throw new Error((upd && upd.mensaje) ? upd.mensaje : "No se pudo completar orígenes");
            }
            var n = Number(upd.actualizados) || 0;
            utPipelineSetStep("fechaOri", "done", n + " actualizado(s)");
            return { actualizados: n, total: ids.length };
        });
    });
}

function utPipelinePasoTipCambio() {
    utPipelineSetStep("tipCambio", "active", "Buscando cuentas sin tipo de cambio…");
    return utPostJson({ accion: "cteSinTipCambio" }).then(function (resp) {
        if (!resp || !resp.ok) {
            throw new Error((resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar tipo de cambio");
        }
        var data = resp.data || [];
        if (!data.length) {
            utPipelineSetStep("tipCambio", "skip", "Sin pendientes");
            return { actualizados: 0, total: 0 };
        }
        var ids = data.map(function (r) { return r.id; });
        utPipelineSetStep("tipCambio", "active", "Actualizando " + ids.length + " registro(s)…");
        return utPostJson({
            accion: "completarTipCambioCte",
            ids: JSON.stringify(ids)
        }).then(function (upd) {
            if (!upd || !upd.ok) {
                throw new Error((upd && upd.mensaje) ? upd.mensaje : "No se pudo actualizar tipo de cambio");
            }
            var n = Number(upd.actualizados) || 0;
            utPipelineSetStep("tipCambio", "done", n + " actualizado(s)");
            return { actualizados: n, total: ids.length };
        });
    });
}

function utEjecutarPipelineCte() {
    if (utPipelineBusy) {
        return;
    }
    utPipelineBusy = true;
    var $btn = $("#btnUtCtePipeline");
    utSetBtnLoading($btn, true, '<i class="fa fa-play"></i> Ejecutar secuencia');
    utPipelineResetSteps();
    $("#modalUtCtePipeline").modal("show");

    var resumen = [];

    utPipelinePasoTotalesTipCambio(utPipelineSetStep)
        .then(function (r0) {
            resumen.push("Totales: " + (r0.actualizados || 0));
            return utPipelinePasoFechaVen();
        })
        .then(function (r1) {
            resumen.push("Vencimiento: " + (r1.actualizados || 0));
            return utPipelinePasoFechaOri();
        })
        .then(function (r2) {
            resumen.push("Origen: " + (r2.actualizados || 0));
            return utPipelinePasoTipCambio();
        })
        .then(function (r3) {
            resumen.push("T/C cte: " + (r3.actualizados || 0));
            $("#utPipelineIntro").text("Secuencia terminada. " + resumen.join(" · "));
            $("#btnUtPipelineCerrar").prop("disabled", false);
            utPipelineBusy = false;
            utSetBtnLoading($btn, false, '<i class="fa fa-play"></i> Ejecutar secuencia');
        })
        .fail(function (err) {
            var msg = "Error en la secuencia";
            if (err && err.message) {
                msg = err.message;
            } else if (err && err.statusText) {
                msg = "No se pudo comunicar con el servidor";
            }
            var $active = $("#utPipelineSteps .ut-pipeline-step.is-active");
            if ($active.length) {
                var step = $active.attr("data-step");
                utPipelineSetStep(step, "error", msg);
            }
            $("#utPipelineIntro").text("Se detuvo la secuencia por un error.");
            $("#btnUtPipelineCerrar").prop("disabled", false);
            utPipelineBusy = false;
            utSetBtnLoading($btn, false, '<i class="fa fa-play"></i> Ejecutar secuencia');
        });
}

$(document).on("click", "#btnUtCtePipeline", function () {
    if (utPipelineBusy) {
        return;
    }
    swal({
        title: "¿Ejecutar secuencia?",
        text: "Completará en orden: T/C totales → fecha vencimiento → fecha origen → tipo de cambio cte. (todos los pendientes de cada paso).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, ejecutar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utEjecutarPipelineCte();
    });
});

/* ---- Pipeline ventas (secuencia) ---- */

function utPeriodoVentas() {
    var p = $.trim($("#utVentaPipelinePeriodo").val() || "");
    if (/^\d{4}-\d{2}$/.test(p)) {
        return p;
    }
    return utPeriodoMesActual();
}

function utSyncPeriodosVenta() {
    var p = utPeriodoVentas();
    $("#utVentaCuentaPeriodo, #utVentaPosPeriodo, #utVentaCulqiPeriodo, #utVentaNcDevPeriodo, #utVentaNcDsctoPeriodo, #utVentaNdFletePeriodo, #utVentaNdProtestoPeriodo").val(p);
    return p;
}

function utVentaPipelineResetSteps() {
    $("#utVentaPipelineSteps .ut-pipeline-step").each(function () {
        var $li = $(this);
        $li.removeClass("is-active is-done is-error is-skip");
        $li.find(".ut-pipeline-step__icon").html('<i class="fa fa-circle-o"></i>');
        $li.find(".ut-pipeline-step__msg").text("En espera");
    });
    $("#utVentaPipelineIntro").text("Ejecutando correcciones en orden…");
    $("#btnUtVentaPipelineCerrar").prop("disabled", true);
}

function utVentaPipelineSetStep(step, state, msg) {
    var $li = $('#utVentaPipelineSteps .ut-pipeline-step[data-step="' + step + '"]');
    if (!$li.length) {
        return;
    }
    $li.removeClass("is-active is-done is-error is-skip");
    var icon = '<i class="fa fa-circle-o"></i>';
    if (state === "active") {
        $li.addClass("is-active");
        icon = '<i class="fa fa-spinner fa-spin"></i>';
    } else if (state === "done") {
        $li.addClass("is-done");
        icon = '<i class="fa fa-check-circle"></i>';
    } else if (state === "skip") {
        $li.addClass("is-done is-skip");
        icon = '<i class="fa fa-check-circle"></i>';
    } else if (state === "error") {
        $li.addClass("is-error");
        icon = '<i class="fa fa-times-circle"></i>';
    }
    $li.find(".ut-pipeline-step__icon").html(icon);
    if (msg) {
        $li.find(".ut-pipeline-step__msg").text(msg);
    }
}

function utVentaPipelineMapItems(rows) {
    return (rows || []).map(function (r) {
        return { tipo: r.tipo, documento: r.documento };
    }).filter(function (it) {
        return it.tipo && it.documento;
    });
}

function utVentaPipelinePaso(opts) {
    var step = opts.step;
    var listAccion = opts.listAccion;
    var updateAccion = opts.updateAccion;
    var listLabel = opts.listLabel || "Buscando pendientes…";
    var updateLabel = opts.updateLabel || "Completando…";
    var listData = opts.listData || {};
    var updateExtra = opts.updateExtra || {};
    var errorList = opts.errorList || "No se pudo consultar";
    var errorUpdate = opts.errorUpdate || "No se pudo completar";

    utVentaPipelineSetStep(step, "active", listLabel);
    var payload = $.extend({ accion: listAccion }, listData);
    return utPostJson(payload).then(function (resp) {
        if (!resp || !resp.ok) {
            throw new Error((resp && resp.mensaje) ? resp.mensaje : errorList);
        }
        var data = resp.data || [];
        if (!data.length) {
            utVentaPipelineSetStep(step, "skip", "Sin pendientes");
            return { actualizados: 0, total: 0 };
        }
        var items = utVentaPipelineMapItems(data);
        if (!items.length) {
            utVentaPipelineSetStep(step, "skip", "Sin pendientes");
            return { actualizados: 0, total: 0 };
        }
        utVentaPipelineSetStep(step, "active", updateLabel.replace("{n}", String(items.length)));
        var updPayload = $.extend({
            accion: updateAccion,
            items: JSON.stringify(items)
        }, updateExtra);
        return utPostJson(updPayload).then(function (upd) {
            if (!upd || !upd.ok) {
                throw new Error((upd && upd.mensaje) ? upd.mensaje : errorUpdate);
            }
            var n = Number(upd.actualizados) || 0;
            utVentaPipelineSetStep(step, "done", n + " actualizado(s)");
            return { actualizados: n, total: items.length };
        });
    });
}

function utEjecutarPipelineVentas() {
    if (utPipelineBusy) {
        return;
    }
    var periodo = utSyncPeriodosVenta();
    utPipelineBusy = true;
    var $btn = $("#btnUtVentaPipeline");
    utSetBtnLoading($btn, true, '<i class="fa fa-play"></i> Ejecutar secuencia');
    utVentaPipelineResetSteps();
    $("#utVentaPipelineIntro").text("Periodo " + periodo + " · ejecutando correcciones…");
    $("#modalUtVentaPipeline").modal("show");

    var resumen = [];
    var periodoExtra = { periodo: periodo };

    utPipelinePasoTotalesTipCambio(utVentaPipelineSetStep)
        .then(function (r0) {
            resumen.push("Totales: " + (r0.actualizados || 0));
            return utVentaPipelinePaso({
                step: "tipCambio",
                listAccion: "ventasSinTipCambio",
                updateAccion: "completarTipCambioVentas",
                listLabel: "Buscando ventas sin tipo de cambio…",
                updateLabel: "Actualizando {n} venta(s)…"
            });
        })
        .then(function (r) {
            resumen.push("T/C ventas: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "cuenta",
                listAccion: "ventasSinCuenta",
                updateAccion: "completarCuentaVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando facturas/boletas sin cuenta…",
                updateLabel: "Completando cuenta en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("S02/S03: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "pos",
                listAccion: "ventasCuentaPos",
                updateAccion: "completarCuentaPosVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando POS showroom…",
                updateLabel: "Asignando 702213 en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("POS: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "culqi",
                listAccion: "ventasCuentaCulqi",
                updateAccion: "completarCuentaCulqiVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando Culqi…",
                updateLabel: "Asignando Culqi en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("Culqi: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "ncDev",
                listAccion: "ventasCuentaNcDev",
                updateAccion: "completarCuentaNcDevVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando NC devolución…",
                updateLabel: "Asignando NC devolución en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("NC dev: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "ncDscto",
                listAccion: "ventasCuentaNcDscto",
                updateAccion: "completarCuentaNcDsctoVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando NC descuento…",
                updateLabel: "Asignando NC descuento en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("NC dscto: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "ndFlete",
                listAccion: "ventasCuentaNdFlete",
                updateAccion: "completarCuentaNdFleteVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando ND flete…",
                updateLabel: "Asignando ND flete en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("ND flete: " + (r.actualizados || 0));
            return utVentaPipelinePaso({
                step: "ndProtesto",
                listAccion: "ventasCuentaNdProtesto",
                updateAccion: "completarCuentaNdProtestoVentas",
                listData: periodoExtra,
                updateExtra: periodoExtra,
                listLabel: "Buscando ND protesto…",
                updateLabel: "Asignando ND protesto en {n}…"
            });
        })
        .then(function (r) {
            resumen.push("ND protesto: " + (r.actualizados || 0));
            $("#utVentaPipelineIntro").text("Secuencia terminada (" + periodo + "). " + resumen.join(" · "));
            $("#btnUtVentaPipelineCerrar").prop("disabled", false);
            utPipelineBusy = false;
            utSetBtnLoading($btn, false, '<i class="fa fa-play"></i> Ejecutar secuencia');
        })
        .fail(function (err) {
            var msg = "Error en la secuencia";
            if (err && err.message) {
                msg = err.message;
            } else if (err && err.statusText) {
                msg = "No se pudo comunicar con el servidor";
            }
            var $active = $("#utVentaPipelineSteps .ut-pipeline-step.is-active");
            if ($active.length) {
                utVentaPipelineSetStep($active.attr("data-step"), "error", msg);
            }
            $("#utVentaPipelineIntro").text("Se detuvo la secuencia por un error.");
            $("#btnUtVentaPipelineCerrar").prop("disabled", false);
            utPipelineBusy = false;
            utSetBtnLoading($btn, false, '<i class="fa fa-play"></i> Ejecutar secuencia');
        });
}

$(document).on("change", "#utVentaPipelinePeriodo", function () {
    utSyncPeriodosVenta();
});

$(document).on("click", "#btnUtVentaPipeline", function () {
    if (utPipelineBusy) {
        return;
    }
    var periodo = utSyncPeriodosVenta();
    swal({
        title: "¿Ejecutar secuencia de ventas?",
        text: "Periodo " + periodo + ": T/C totales → T/C ventas → cuenta S02/S03 → POS → Culqi → NC devolución → NC descuento → ND flete → ND protesto (todos los pendientes de cada paso).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, ejecutar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utEjecutarPipelineVentas();
    });
});

$(function () {
    if ($("#utVentaPipelinePeriodo").length) {
        utSyncPeriodosVenta();
    }
});

/* ---- Completar T/C en totales (datos-día) ---- */

var utTotalesTipCambioItems = [];

function utActualizarBotonTotalesTipCambio() {
    var n = $("#utTotalesTipCambioTable tbody input.ut-totalestc-chk:checked").length;
    $("#btnUtCompletarTotalesTipCambio").prop("disabled", n < 1);
    $("#utTotalesTipCambioCount").text(n + " seleccionado(s)");
}

function utRenderTotalesTipCambio(rows) {
    utTotalesTipCambioItems = rows || [];
    var $tb = $("#utTotalesTipCambioTable tbody");
    $tb.empty();

    if (!utTotalesTipCambioItems.length) {
        $("#utTotalesTipCambioEmpty").show();
        $("#utTotalesTipCambioTableWrap").hide();
        $("#btnUtCompletarTotalesTipCambio").prop("disabled", true);
        $("#utTotalesTipCambioCount").text("");
        return;
    }

    $("#utTotalesTipCambioEmpty").hide();
    $("#utTotalesTipCambioTableWrap").show();

    var html = "";
    for (var i = 0; i < utTotalesTipCambioItems.length; i++) {
        var r = utTotalesTipCambioItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-totalestc-chk\" checked></td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.dia_semana || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.cambio_compra) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.cambio_venta) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utTotalesTipCambioCheckAll").prop("checked", true);
    utActualizarBotonTotalesTipCambio();
}

function utCargarTotalesSinTipCambio(opts) {
    opts = opts || {};
    var $btn = $("#btnUtTotalesSinTipCambio");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-calendar"></i> Revisar');
        utMostrarCarga("Buscando días sin T/C en totales…");
    } else {
        $("#utTotalesTipCambioLoading").show();
        $("#utTotalesTipCambioEmpty").hide();
        $("#utTotalesTipCambioTableWrap").hide();
        $("#btnUtCompletarTotalesTipCambio").prop("disabled", true);
    }

    $("#utTotalesTipCambioMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "totalesSinTipCambio" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-calendar"></i> Revisar');
        } else {
            $("#utTotalesTipCambioLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utTotalesTipCambioMeta").text("· " + (resp.anio || "") + " · " + (resp.total || 0) + " día(s)");
        utRenderTotalesTipCambio(resp.data || []);
        $("#modalUtTotalesTipCambio").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-calendar"></i> Revisar');
        } else {
            $("#utTotalesTipCambioLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utFechasSeleccionadasTotalesTipCambio() {
    var out = [];
    $("#utTotalesTipCambioTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-totalestc-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utTotalesTipCambioItems[idx]) {
            return;
        }
        out.push(utTotalesTipCambioItems[idx].fecha);
    });
    return out;
}

$(document).on("click", "#btnUtTotalesSinTipCambio", function () {
    utCargarTotalesSinTipCambio();
});

$(document).on("change", "#utTotalesTipCambioCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utTotalesTipCambioTable tbody input.ut-totalestc-chk").prop("checked", on);
    utActualizarBotonTotalesTipCambio();
});

$(document).on("change", "#utTotalesTipCambioTable tbody input.ut-totalestc-chk", function () {
    var total = $("#utTotalesTipCambioTable tbody input.ut-totalestc-chk").length;
    var checked = $("#utTotalesTipCambioTable tbody input.ut-totalestc-chk:checked").length;
    $("#utTotalesTipCambioCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonTotalesTipCambio();
});

$(document).on("click", "#btnUtCompletarTotalesTipCambio", function () {
    var fechas = utFechasSeleccionadasTotalesTipCambio();
    if (!fechas.length) {
        return;
    }

    var $btn = $("#btnUtCompletarTotalesTipCambio");
    swal({
        title: "¿Completar T/C en totales?",
        text: "Se consultará la API (misma que Datos diarios) para " + fechas.length + " día(s). Si no hay TC, se reusa el día previo.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Consultando tipos de cambio… puede tardar unos segundos");

        $.ajax({
            url: "ajax/utilidades.ajax.php",
            method: "POST",
            data: {
                accion: "completarTipCambioTotales",
                fechas: JSON.stringify(fechas)
            },
            dataType: "json",
            timeout: 300000
        }).done(function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo completar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }
            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || ("Actualizados: " + (resp.actualizados || 0)),
                confirmButtonText: "Cerrar"
            }).then(function () {
                utCargarTotalesSinTipCambio({ silencioso: true });
            });
        }).fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor (o se agotó el tiempo)",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Completar tipo de cambio ventas ---- */

var utVentaTipCambioItems = [];

function utActualizarBotonVentaTipCambio() {
    var n = $("#utVentaTipCambioTable tbody input.ut-ventatc-chk:checked").length;
    $("#btnUtCompletarVentaTipCambio").prop("disabled", n < 1);
    $("#utVentaTipCambioCount").text(n + " seleccionado(s)");
}

function utRenderVentaTipCambio(rows) {
    utVentaTipCambioItems = rows || [];
    var $tb = $("#utVentaTipCambioTable tbody");
    $tb.empty();

    if (!utVentaTipCambioItems.length) {
        $("#utVentaTipCambioEmpty").show();
        $("#utVentaTipCambioTableWrap").hide();
        $("#btnUtCompletarVentaTipCambio").prop("disabled", true);
        $("#utVentaTipCambioCount").text("");
        return;
    }

    $("#utVentaTipCambioEmpty").hide();
    $("#utVentaTipCambioTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaTipCambioItems.length; i++) {
        var r = utVentaTipCambioItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventatc-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.tipo_cambio) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.tipo_cambio_prop) + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaTipCambioCheckAll").prop("checked", true);
    utActualizarBotonVentaTipCambio();
}

function utCargarVentasSinTipCambio(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasSinTipCambio");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-exchange"></i> Revisar');
        utMostrarCarga("Buscando ventas sin tipo de cambio…");
    } else {
        $("#utVentaTipCambioLoading").show();
        $("#utVentaTipCambioEmpty").hide();
        $("#utVentaTipCambioTableWrap").hide();
        $("#btnUtCompletarVentaTipCambio").prop("disabled", true);
    }

    $("#utVentaTipCambioMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "ventasSinTipCambio" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exchange"></i> Revisar');
        } else {
            $("#utVentaTipCambioLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utVentaTipCambioMeta").text("· " + (resp.anio || "") + " · " + (resp.total || 0) + " registro(s)");
        utRenderVentaTipCambio(resp.data || []);
        $("#modalUtVentaTipCambio").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exchange"></i> Revisar');
        } else {
            $("#utVentaTipCambioLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utIdsSeleccionadosVentaTipCambio() {
    var out = [];
    $("#utVentaTipCambioTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventatc-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaTipCambioItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaTipCambioItems[idx].tipo,
            documento: utVentaTipCambioItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasSinTipCambio", function () {
    utCargarVentasSinTipCambio();
});

$(document).on("change", "#utVentaTipCambioCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaTipCambioTable tbody input.ut-ventatc-chk").prop("checked", on);
    utActualizarBotonVentaTipCambio();
});

$(document).on("change", "#utVentaTipCambioTable tbody input.ut-ventatc-chk", function () {
    var total = $("#utVentaTipCambioTable tbody input.ut-ventatc-chk").length;
    var checked = $("#utVentaTipCambioTable tbody input.ut-ventatc-chk:checked").length;
    $("#utVentaTipCambioCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaTipCambio();
});

$(document).on("click", "#btnUtCompletarVentaTipCambio", function () {
    var items = utIdsSeleccionadosVentaTipCambio();
    if (!items.length) {
        return;
    }

    var $btn = $("#btnUtCompletarVentaTipCambio");
    swal({
        title: "¿Actualizar tipo de cambio?",
        text: "Se tomará el cambio de venta del día en " + items.length + " venta(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Actualizando tipo de cambio de ventas…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarTipCambioVentas",
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaTipCambio();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasSinTipCambio({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaTipCambio();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Completar cuenta contable ventas (S02/S03) ---- */

var utVentaCuentaItems = [];
var utVentaCuentaPeriodoActual = "";

function utPeriodoMesActual() {
    var d = new Date();
    var m = d.getMonth() + 1;
    return d.getFullYear() + "-" + (m < 10 ? "0" : "") + m;
}


function utActualizarBotonVentaCuenta() {
    var n = $("#utVentaCuentaTable tbody input.ut-ventacta-chk:checked").length;
    $("#btnUtCompletarVentaCuenta").prop("disabled", n < 1);
    $("#utVentaCuentaCount").text(n + " seleccionado(s)");
}

function utRenderVentaCuenta(rows) {
    utVentaCuentaItems = rows || [];
    var $tb = $("#utVentaCuentaTable tbody");
    $tb.empty();

    if (!utVentaCuentaItems.length) {
        $("#utVentaCuentaEmpty").show();
        $("#utVentaCuentaTableWrap").hide();
        $("#btnUtCompletarVentaCuenta").prop("disabled", true);
        $("#utVentaCuentaCount").text("");
        return;
    }

    $("#utVentaCuentaEmpty").hide();
    $("#utVentaCuentaTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaCuentaItems.length; i++) {
        var r = utVentaCuentaItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventacta-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaCuentaCheckAll").prop("checked", true);
    utActualizarBotonVentaCuenta();
}

function utCargarVentasSinCuenta(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasSinCuenta");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaCuentaPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-book"></i> Revisar');
        utMostrarCarga("Buscando ventas sin cuenta contable…");
    } else {
        $("#utVentaCuentaLoading").show();
        $("#utVentaCuentaEmpty").hide();
        $("#utVentaCuentaTableWrap").hide();
        $("#btnUtCompletarVentaCuenta").prop("disabled", true);
    }

    $("#utVentaCuentaMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasSinCuenta",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-book"></i> Revisar');
        } else {
            $("#utVentaCuentaLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaCuentaPeriodoActual = resp.periodo;
            $("#utVentaCuentaPeriodo").val(resp.periodo);
        }

        $("#utVentaCuentaMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.inicio || "") + " → " + (resp.fin || "") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaCuenta(resp.data || []);
        $("#modalUtVentaCuenta").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-book"></i> Revisar');
        } else {
            $("#utVentaCuentaLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaCuenta() {
    var out = [];
    $("#utVentaCuentaTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventacta-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaCuentaItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaCuentaItems[idx].tipo,
            documento: utVentaCuentaItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasSinCuenta", function () {
    utCargarVentasSinCuenta();
});

$(document).on("change", "#utVentaCuentaCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaCuentaTable tbody input.ut-ventacta-chk").prop("checked", on);
    utActualizarBotonVentaCuenta();
});

$(document).on("change", "#utVentaCuentaTable tbody input.ut-ventacta-chk", function () {
    var total = $("#utVentaCuentaTable tbody input.ut-ventacta-chk").length;
    var checked = $("#utVentaCuentaTable tbody input.ut-ventacta-chk:checked").length;
    $("#utVentaCuentaCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaCuenta();
});

$(document).on("click", "#btnUtCompletarVentaCuenta", function () {
    var items = utItemsSeleccionadosVentaCuenta();
    if (!items.length) {
        return;
    }

    var periodo = utVentaCuentaPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaCuenta");
    swal({
        title: "¿Completar cuenta contable?",
        text: "Periodo " + periodo + ": se asignará 702211 (Lima) o 702212 (provincia) en " + items.length + " venta(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Completando cuentas contables…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaCuenta();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasSinCuenta({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaCuenta();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta POS showroom (702213) ---- */

var utVentaPosItems = [];
var utVentaPosPeriodoActual = "";

function utActualizarBotonVentaPos() {
    var n = $("#utVentaPosTable tbody input.ut-ventapos-chk:checked").length;
    $("#btnUtCompletarVentaPos").prop("disabled", n < 1);
    $("#utVentaPosCount").text(n + " seleccionado(s)");
}

function utRenderVentaPos(rows) {
    utVentaPosItems = rows || [];
    var $tb = $("#utVentaPosTable tbody");
    $tb.empty();

    if (!utVentaPosItems.length) {
        $("#utVentaPosEmpty").show();
        $("#utVentaPosTableWrap").hide();
        $("#btnUtCompletarVentaPos").prop("disabled", true);
        $("#utVentaPosCount").text("");
        return;
    }

    $("#utVentaPosEmpty").hide();
    $("#utVentaPosTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaPosItems.length; i++) {
        var r = utVentaPosItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventapos-chk\" checked></td>";
        html += "<td>" + utEscape(r.num_cta || "") + "</td>";
        html += "<td>" + utEscape(r.tipo_doc || "") + "</td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cod_pago || "") + "</td>";
        html += "<td>" + utEscape(r.vendedor || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha_pago)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaPosCheckAll").prop("checked", true);
    utActualizarBotonVentaPos();
}

function utCargarVentasCuentaPos(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaPos");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaPosPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-credit-card"></i> Revisar');
        utMostrarCarga("Buscando ventas POS showroom…");
    } else {
        $("#utVentaPosLoading").show();
        $("#utVentaPosEmpty").hide();
        $("#utVentaPosTableWrap").hide();
        $("#btnUtCompletarVentaPos").prop("disabled", true);
    }

    $("#utVentaPosMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaPos",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-credit-card"></i> Revisar');
        } else {
            $("#utVentaPosLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaPosPeriodoActual = resp.periodo;
            $("#utVentaPosPeriodo").val(resp.periodo);
        }

        $("#utVentaPosMeta").text(
            "· " + (resp.periodo || periodo) +
            " · cuenta " + (resp.cuenta || "702213") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaPos(resp.data || []);
        $("#modalUtVentaPos").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-credit-card"></i> Revisar');
        } else {
            $("#utVentaPosLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaPos() {
    var out = [];
    $("#utVentaPosTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventapos-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaPosItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaPosItems[idx].tipo,
            documento: utVentaPosItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaPos", function () {
    utCargarVentasCuentaPos();
});

$(document).on("change", "#utVentaPosCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaPosTable tbody input.ut-ventapos-chk").prop("checked", on);
    utActualizarBotonVentaPos();
});

$(document).on("change", "#utVentaPosTable tbody input.ut-ventapos-chk", function () {
    var total = $("#utVentaPosTable tbody input.ut-ventapos-chk").length;
    var checked = $("#utVentaPosTable tbody input.ut-ventapos-chk:checked").length;
    $("#utVentaPosCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaPos();
});

$(document).on("click", "#btnUtCompletarVentaPos", function () {
    var items = utItemsSeleccionadosVentaPos();
    if (!items.length) {
        return;
    }

    var periodo = utVentaPosPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaPos");
    swal({
        title: "¿Asignar cuenta 702213?",
        text: "Periodo " + periodo + ": se pondrá cuenta POS showroom en " + items.length + " venta(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta POS showroom…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaPosVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaPos();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaPos({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaPos();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta Culqi (702215 / 702216) ---- */

var utVentaCulqiItems = [];
var utVentaCulqiPeriodoActual = "";

function utActualizarBotonVentaCulqi() {
    var n = $("#utVentaCulqiTable tbody input.ut-ventaculqi-chk:checked").length;
    $("#btnUtCompletarVentaCulqi").prop("disabled", n < 1);
    $("#utVentaCulqiCount").text(n + " seleccionado(s)");
}

function utRenderVentaCulqi(rows) {
    utVentaCulqiItems = rows || [];
    var $tb = $("#utVentaCulqiTable tbody");
    $tb.empty();

    if (!utVentaCulqiItems.length) {
        $("#utVentaCulqiEmpty").show();
        $("#utVentaCulqiTableWrap").hide();
        $("#btnUtCompletarVentaCulqi").prop("disabled", true);
        $("#utVentaCulqiCount").text("");
        return;
    }

    $("#utVentaCulqiEmpty").hide();
    $("#utVentaCulqiTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaCulqiItems.length; i++) {
        var r = utVentaCulqiItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventaculqi-chk\" checked></td>";
        html += "<td>" + utEscape(r.num_cta || "") + "</td>";
        html += "<td>" + utEscape(r.tipo_doc || "") + "</td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha_pago)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaCulqiCheckAll").prop("checked", true);
    utActualizarBotonVentaCulqi();
}

function utCargarVentasCuentaCulqi(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaCulqi");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaCulqiPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-globe"></i> Revisar');
        utMostrarCarga("Buscando ventas Culqi…");
    } else {
        $("#utVentaCulqiLoading").show();
        $("#utVentaCulqiEmpty").hide();
        $("#utVentaCulqiTableWrap").hide();
        $("#btnUtCompletarVentaCulqi").prop("disabled", true);
    }

    $("#utVentaCulqiMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaCulqi",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-globe"></i> Revisar');
        } else {
            $("#utVentaCulqiLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaCulqiPeriodoActual = resp.periodo;
            $("#utVentaCulqiPeriodo").val(resp.periodo);
        }

        $("#utVentaCulqiMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.cuenta_lima || "702215") + "/" + (resp.cuenta_prov || "702216") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaCulqi(resp.data || []);
        $("#modalUtVentaCulqi").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-globe"></i> Revisar');
        } else {
            $("#utVentaCulqiLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaCulqi() {
    var out = [];
    $("#utVentaCulqiTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventaculqi-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaCulqiItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaCulqiItems[idx].tipo,
            documento: utVentaCulqiItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaCulqi", function () {
    utCargarVentasCuentaCulqi();
});

$(document).on("change", "#utVentaCulqiCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaCulqiTable tbody input.ut-ventaculqi-chk").prop("checked", on);
    utActualizarBotonVentaCulqi();
});

$(document).on("change", "#utVentaCulqiTable tbody input.ut-ventaculqi-chk", function () {
    var total = $("#utVentaCulqiTable tbody input.ut-ventaculqi-chk").length;
    var checked = $("#utVentaCulqiTable tbody input.ut-ventaculqi-chk:checked").length;
    $("#utVentaCulqiCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaCulqi();
});

$(document).on("click", "#btnUtCompletarVentaCulqi", function () {
    var items = utItemsSeleccionadosVentaCulqi();
    if (!items.length) {
        return;
    }

    var periodo = utVentaCulqiPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaCulqi");
    swal({
        title: "¿Asignar cuenta Culqi?",
        text: "Periodo " + periodo + ": Lima 702215 / provincia 702216 en " + items.length + " venta(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta Culqi…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaCulqiVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaCulqi();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaCulqi({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaCulqi();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta NC devolución (709411 / 709412) ---- */

var utVentaNcDevItems = [];
var utVentaNcDevPeriodoActual = "";

function utActualizarBotonVentaNcDev() {
    var n = $("#utVentaNcDevTable tbody input.ut-ventancdev-chk:checked").length;
    $("#btnUtCompletarVentaNcDev").prop("disabled", n < 1);
    $("#utVentaNcDevCount").text(n + " seleccionado(s)");
}

function utRenderVentaNcDev(rows) {
    utVentaNcDevItems = rows || [];
    var $tb = $("#utVentaNcDevTable tbody");
    $tb.empty();

    if (!utVentaNcDevItems.length) {
        $("#utVentaNcDevEmpty").show();
        $("#utVentaNcDevTableWrap").hide();
        $("#btnUtCompletarVentaNcDev").prop("disabled", true);
        $("#utVentaNcDevCount").text("");
        return;
    }

    $("#utVentaNcDevEmpty").hide();
    $("#utVentaNcDevTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaNcDevItems.length; i++) {
        var r = utVentaNcDevItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventancdev-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.motivo || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaNcDevCheckAll").prop("checked", true);
    utActualizarBotonVentaNcDev();
}

function utCargarVentasCuentaNcDev(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaNcDev");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaNcDevPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-undo"></i> Revisar');
        utMostrarCarga("Buscando NC devolución…");
    } else {
        $("#utVentaNcDevLoading").show();
        $("#utVentaNcDevEmpty").hide();
        $("#utVentaNcDevTableWrap").hide();
        $("#btnUtCompletarVentaNcDev").prop("disabled", true);
    }

    $("#utVentaNcDevMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaNcDev",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-undo"></i> Revisar');
        } else {
            $("#utVentaNcDevLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaNcDevPeriodoActual = resp.periodo;
            $("#utVentaNcDevPeriodo").val(resp.periodo);
        }

        $("#utVentaNcDevMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.cuenta_lima || "709411") + "/" + (resp.cuenta_prov || "709412") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaNcDev(resp.data || []);
        $("#modalUtVentaNcDev").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-undo"></i> Revisar');
        } else {
            $("#utVentaNcDevLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaNcDev() {
    var out = [];
    $("#utVentaNcDevTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventancdev-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaNcDevItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaNcDevItems[idx].tipo,
            documento: utVentaNcDevItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaNcDev", function () {
    utCargarVentasCuentaNcDev();
});

$(document).on("change", "#utVentaNcDevCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaNcDevTable tbody input.ut-ventancdev-chk").prop("checked", on);
    utActualizarBotonVentaNcDev();
});

$(document).on("change", "#utVentaNcDevTable tbody input.ut-ventancdev-chk", function () {
    var total = $("#utVentaNcDevTable tbody input.ut-ventancdev-chk").length;
    var checked = $("#utVentaNcDevTable tbody input.ut-ventancdev-chk:checked").length;
    $("#utVentaNcDevCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaNcDev();
});

$(document).on("click", "#btnUtCompletarVentaNcDev", function () {
    var items = utItemsSeleccionadosVentaNcDev();
    if (!items.length) {
        return;
    }

    var periodo = utVentaNcDevPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaNcDev");
    swal({
        title: "¿Asignar cuenta NC devolución?",
        text: "Periodo " + periodo + ": Lima 709411 / provincia 709412 en " + items.length + " documento(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta NC devolución…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaNcDevVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNcDev();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaNcDev({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNcDev();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta NC descuento (741101 / 741102) ---- */

var utVentaNcDsctoItems = [];
var utVentaNcDsctoPeriodoActual = "";

function utActualizarBotonVentaNcDscto() {
    var n = $("#utVentaNcDsctoTable tbody input.ut-ventancdscto-chk:checked").length;
    $("#btnUtCompletarVentaNcDscto").prop("disabled", n < 1);
    $("#utVentaNcDsctoCount").text(n + " seleccionado(s)");
}

function utRenderVentaNcDscto(rows) {
    utVentaNcDsctoItems = rows || [];
    var $tb = $("#utVentaNcDsctoTable tbody");
    $tb.empty();

    if (!utVentaNcDsctoItems.length) {
        $("#utVentaNcDsctoEmpty").show();
        $("#utVentaNcDsctoTableWrap").hide();
        $("#btnUtCompletarVentaNcDscto").prop("disabled", true);
        $("#utVentaNcDsctoCount").text("");
        return;
    }

    $("#utVentaNcDsctoEmpty").hide();
    $("#utVentaNcDsctoTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaNcDsctoItems.length; i++) {
        var r = utVentaNcDsctoItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventancdscto-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.motivo || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaNcDsctoCheckAll").prop("checked", true);
    utActualizarBotonVentaNcDscto();
}

function utCargarVentasCuentaNcDscto(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaNcDscto");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaNcDsctoPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-percent"></i> Revisar');
        utMostrarCarga("Buscando NC descuento…");
    } else {
        $("#utVentaNcDsctoLoading").show();
        $("#utVentaNcDsctoEmpty").hide();
        $("#utVentaNcDsctoTableWrap").hide();
        $("#btnUtCompletarVentaNcDscto").prop("disabled", true);
    }

    $("#utVentaNcDsctoMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaNcDscto",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-percent"></i> Revisar');
        } else {
            $("#utVentaNcDsctoLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaNcDsctoPeriodoActual = resp.periodo;
            $("#utVentaNcDsctoPeriodo").val(resp.periodo);
        }

        $("#utVentaNcDsctoMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.cuenta_lima || "741101") + "/" + (resp.cuenta_prov || "741102") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaNcDscto(resp.data || []);
        $("#modalUtVentaNcDscto").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-percent"></i> Revisar');
        } else {
            $("#utVentaNcDsctoLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaNcDscto() {
    var out = [];
    $("#utVentaNcDsctoTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventancdscto-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaNcDsctoItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaNcDsctoItems[idx].tipo,
            documento: utVentaNcDsctoItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaNcDscto", function () {
    utCargarVentasCuentaNcDscto();
});

$(document).on("change", "#utVentaNcDsctoCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaNcDsctoTable tbody input.ut-ventancdscto-chk").prop("checked", on);
    utActualizarBotonVentaNcDscto();
});

$(document).on("change", "#utVentaNcDsctoTable tbody input.ut-ventancdscto-chk", function () {
    var total = $("#utVentaNcDsctoTable tbody input.ut-ventancdscto-chk").length;
    var checked = $("#utVentaNcDsctoTable tbody input.ut-ventancdscto-chk:checked").length;
    $("#utVentaNcDsctoCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaNcDscto();
});

$(document).on("click", "#btnUtCompletarVentaNcDscto", function () {
    var items = utItemsSeleccionadosVentaNcDscto();
    if (!items.length) {
        return;
    }

    var periodo = utVentaNcDsctoPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaNcDscto");
    swal({
        title: "¿Asignar cuenta NC descuento?",
        text: "Periodo " + periodo + ": Lima 741101 / provincia 741102 en " + items.length + " documento(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta NC descuento…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaNcDsctoVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNcDscto();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaNcDscto({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNcDscto();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta ND flete (75995 / 75996) ---- */

var utVentaNdFleteItems = [];
var utVentaNdFletePeriodoActual = "";

function utActualizarBotonVentaNdFlete() {
    var n = $("#utVentaNdFleteTable tbody input.ut-ventandflete-chk:checked").length;
    $("#btnUtCompletarVentaNdFlete").prop("disabled", n < 1);
    $("#utVentaNdFleteCount").text(n + " seleccionado(s)");
}

function utRenderVentaNdFlete(rows) {
    utVentaNdFleteItems = rows || [];
    var $tb = $("#utVentaNdFleteTable tbody");
    $tb.empty();

    if (!utVentaNdFleteItems.length) {
        $("#utVentaNdFleteEmpty").show();
        $("#utVentaNdFleteTableWrap").hide();
        $("#btnUtCompletarVentaNdFlete").prop("disabled", true);
        $("#utVentaNdFleteCount").text("");
        return;
    }

    $("#utVentaNdFleteEmpty").hide();
    $("#utVentaNdFleteTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaNdFleteItems.length; i++) {
        var r = utVentaNdFleteItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventandflete-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.vendedor || "") + "</td>";
        html += "<td>" + utEscape(r.motivo || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaNdFleteCheckAll").prop("checked", true);
    utActualizarBotonVentaNdFlete();
}

function utCargarVentasCuentaNdFlete(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaNdFlete");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaNdFletePeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-truck"></i> Revisar');
        utMostrarCarga("Buscando ND flete…");
    } else {
        $("#utVentaNdFleteLoading").show();
        $("#utVentaNdFleteEmpty").hide();
        $("#utVentaNdFleteTableWrap").hide();
        $("#btnUtCompletarVentaNdFlete").prop("disabled", true);
    }

    $("#utVentaNdFleteMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaNdFlete",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-truck"></i> Revisar');
        } else {
            $("#utVentaNdFleteLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaNdFletePeriodoActual = resp.periodo;
            $("#utVentaNdFletePeriodo").val(resp.periodo);
        }

        $("#utVentaNdFleteMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.cuenta_lima || "75995") + "/" + (resp.cuenta_prov || "75996") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaNdFlete(resp.data || []);
        $("#modalUtVentaNdFlete").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-truck"></i> Revisar');
        } else {
            $("#utVentaNdFleteLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaNdFlete() {
    var out = [];
    $("#utVentaNdFleteTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventandflete-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaNdFleteItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaNdFleteItems[idx].tipo,
            documento: utVentaNdFleteItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaNdFlete", function () {
    utCargarVentasCuentaNdFlete();
});

$(document).on("change", "#utVentaNdFleteCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaNdFleteTable tbody input.ut-ventandflete-chk").prop("checked", on);
    utActualizarBotonVentaNdFlete();
});

$(document).on("change", "#utVentaNdFleteTable tbody input.ut-ventandflete-chk", function () {
    var total = $("#utVentaNdFleteTable tbody input.ut-ventandflete-chk").length;
    var checked = $("#utVentaNdFleteTable tbody input.ut-ventandflete-chk:checked").length;
    $("#utVentaNdFleteCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaNdFlete();
});

$(document).on("click", "#btnUtCompletarVentaNdFlete", function () {
    var items = utItemsSeleccionadosVentaNdFlete();
    if (!items.length) {
        return;
    }

    var periodo = utVentaNdFletePeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaNdFlete");
    swal({
        title: "¿Asignar cuenta ND flete?",
        text: "Periodo " + periodo + ": Lima 75995 / provincia 75996 en " + items.length + " documento(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta ND flete…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaNdFleteVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNdFlete();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaNdFlete({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNdFlete();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Cuenta ND protesto (75991 / 75992) ---- */

var utVentaNdProtestoItems = [];
var utVentaNdProtestoPeriodoActual = "";

function utActualizarBotonVentaNdProtesto() {
    var n = $("#utVentaNdProtestoTable tbody input.ut-ventandprot-chk:checked").length;
    $("#btnUtCompletarVentaNdProtesto").prop("disabled", n < 1);
    $("#utVentaNdProtestoCount").text(n + " seleccionado(s)");
}

function utRenderVentaNdProtesto(rows) {
    utVentaNdProtestoItems = rows || [];
    var $tb = $("#utVentaNdProtestoTable tbody");
    $tb.empty();

    if (!utVentaNdProtestoItems.length) {
        $("#utVentaNdProtestoEmpty").show();
        $("#utVentaNdProtestoTableWrap").hide();
        $("#btnUtCompletarVentaNdProtesto").prop("disabled", true);
        $("#utVentaNdProtestoCount").text("");
        return;
    }

    $("#utVentaNdProtestoEmpty").hide();
    $("#utVentaNdProtestoTableWrap").show();

    var html = "";
    for (var i = 0; i < utVentaNdProtestoItems.length; i++) {
        var r = utVentaNdProtestoItems[i];
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-ventandprot-chk\" checked></td>";
        html += "<td>" + utEscape(r.tipo || "") + "</td>";
        html += "<td>" + utEscape(r.documento || "") + "</td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(r.vendedor || "") + "</td>";
        html += "<td>" + utEscape(r.motivo || "") + "</td>";
        html += "<td>" + utEscape(r.ubigeo || "") + "</td>";
        html += "<td>" + utEscape(r.zona || "") + "</td>";
        html += "<td>" + utEscape(utFmtFechaCorta(r.fecha)) + "</td>";
        html += "<td>" + utEscape(r.cuenta || "") + "</td>";
        html += "<td>" + utEscape(r.cuenta_prop || "") + "</td>";
        html += "<td class=\"text-right\">" + utFmtNum(r.total) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVentaNdProtestoCheckAll").prop("checked", true);
    utActualizarBotonVentaNdProtesto();
}

function utCargarVentasCuentaNdProtesto(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVentasCuentaNdProtesto");
    var silencioso = !!opts.silencioso;
    var periodo = opts.periodo || utPeriodoVentas();
    utVentaNdProtestoPeriodoActual = periodo;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-exclamation-triangle"></i> Revisar');
        utMostrarCarga("Buscando ND protesto…");
    } else {
        $("#utVentaNdProtestoLoading").show();
        $("#utVentaNdProtestoEmpty").hide();
        $("#utVentaNdProtestoTableWrap").hide();
        $("#btnUtCompletarVentaNdProtesto").prop("disabled", true);
    }

    $("#utVentaNdProtestoMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "ventasCuentaNdProtesto",
        periodo: periodo
    }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exclamation-triangle"></i> Revisar');
        } else {
            $("#utVentaNdProtestoLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        if (resp.periodo) {
            utVentaNdProtestoPeriodoActual = resp.periodo;
            $("#utVentaNdProtestoPeriodo").val(resp.periodo);
        }

        $("#utVentaNdProtestoMeta").text(
            "· " + (resp.periodo || periodo) +
            " · " + (resp.cuenta_lima || "75991") + "/" + (resp.cuenta_prov || "75992") +
            " · " + (resp.total || 0) + " registro(s)"
        );
        utRenderVentaNdProtesto(resp.data || []);
        $("#modalUtVentaNdProtesto").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-exclamation-triangle"></i> Revisar');
        } else {
            $("#utVentaNdProtestoLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVentaNdProtesto() {
    var out = [];
    $("#utVentaNdProtestoTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-ventandprot-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVentaNdProtestoItems[idx]) {
            return;
        }
        out.push({
            tipo: utVentaNdProtestoItems[idx].tipo,
            documento: utVentaNdProtestoItems[idx].documento
        });
    });
    return out;
}

$(document).on("click", "#btnUtVentasCuentaNdProtesto", function () {
    utCargarVentasCuentaNdProtesto();
});

$(document).on("change", "#utVentaNdProtestoCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVentaNdProtestoTable tbody input.ut-ventandprot-chk").prop("checked", on);
    utActualizarBotonVentaNdProtesto();
});

$(document).on("change", "#utVentaNdProtestoTable tbody input.ut-ventandprot-chk", function () {
    var total = $("#utVentaNdProtestoTable tbody input.ut-ventandprot-chk").length;
    var checked = $("#utVentaNdProtestoTable tbody input.ut-ventandprot-chk:checked").length;
    $("#utVentaNdProtestoCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVentaNdProtesto();
});

$(document).on("click", "#btnUtCompletarVentaNdProtesto", function () {
    var items = utItemsSeleccionadosVentaNdProtesto();
    if (!items.length) {
        return;
    }

    var periodo = utVentaNdProtestoPeriodoActual || utPeriodoVentas();
    var $btn = $("#btnUtCompletarVentaNdProtesto");
    swal({
        title: "¿Asignar cuenta ND protesto?",
        text: "Periodo " + periodo + ": Lima 75991 / provincia 75992 en " + items.length + " documento(s).",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Completar seleccionados');
        utMostrarCarga("Asignando cuenta ND protesto…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "completarCuentaNdProtestoVentas",
            periodo: periodo,
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNdProtesto();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVentasCuentaNdProtesto({ silencioso: true, periodo: periodo });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Completar seleccionados');
            utActualizarBotonVentaNdProtesto();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Limpiar cte. VTAOFIC21 ---- */

$(document).on("click", "#btnUtLimpiarVtaOfic", function () {
    var $btn = $("#btnUtLimpiarVtaOfic");

    utSetBtnLoading($btn, true, '<i class="fa fa-trash"></i> Eliminar');
    utMostrarCarga("Consultando movimientos…");

    $.post("ajax/utilidades.ajax.php", {
        accion: "contarCuentaVtaOficina"
    }, function (prev) {
        utOcultarCarga();
        utSetBtnLoading($btn, false, '<i class="fa fa-trash"></i> Eliminar');

        if (!prev || !prev.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (prev && prev.mensaje) ? prev.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        var total = Number(prev.total) || 0;
        if (total < 1) {
            swal({
                type: "info",
                title: "Sin movimientos",
                text: "VTAOFIC21 no tiene registros en cuenta corriente.",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        var textoCantidad = total === 1
            ? "Se eliminará 1 movimiento de VTAOFIC21. No se puede deshacer."
            : ("Se eliminarán " + total + " movimientos de VTAOFIC21. No se puede deshacer.");

        swal({
            title: "¿Borrar cte. de VTAOFIC21?",
            text: textoCantidad,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#c0392b",
            confirmButtonText: "Sí, eliminar " + total,
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.value) {
                return;
            }
            utSetBtnLoading($btn, true, '<i class="fa fa-trash"></i> Eliminar');
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Eliminando…');
            utMostrarCarga("Eliminando " + total + " movimiento(s)…");
            $.post("ajax/utilidades.ajax.php", {
                accion: "eliminarCuentaVtaOficina"
            }, function (resp) {
                utOcultarCarga();
                utSetBtnLoading($btn, false, '<i class="fa fa-trash"></i> Eliminar');
                if (!resp || !resp.ok) {
                    swal({
                        type: "error",
                        title: "Error",
                        text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo eliminar",
                        confirmButtonText: "Cerrar"
                    });
                    return;
                }
                swal({
                    type: "success",
                    title: "Listo",
                    text: resp.mensaje || "Cuenta corriente eliminada",
                    confirmButtonText: "Cerrar"
                });
            }, "json").fail(function () {
                utOcultarCarga();
                utSetBtnLoading($btn, false, '<i class="fa fa-trash"></i> Eliminar');
                swal({
                    type: "error",
                    title: "Error",
                    text: "No se pudo comunicar con el servidor",
                    confirmButtonText: "Cerrar"
                });
            });
        });
    }, "json").fail(function () {
        utOcultarCarga();
        utSetBtnLoading($btn, false, '<i class="fa fa-trash"></i> Eliminar');
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
});

/* ---- Tracking modelo ---- */

var utTrackingModeloActual = "";

function utStat(label, value, extraClass) {
    var cls = extraClass ? (" ut-stat " + extraClass) : " ut-stat";
    return "<div class=\"" + cls.trim() + "\">" +
        "<span class=\"ut-stat__label\">" + utEscape(label) + "</span>" +
        "<span class=\"ut-stat__value\">" + utEscape(String(value)) + "</span>" +
        "</div>";
}

function utNumEq(a, b) {
    return Math.round((Number(a) || 0) * 10000) === Math.round((Number(b) || 0) * 10000);
}

function utCeldaCambio(actual, calculado) {
    var a = Number(actual) || 0;
    var c = Number(calculado) || 0;
    if (utNumEq(a, c)) {
        return "<td class=\"text-right\">" + utFmtNum(a) + "</td>";
    }
    return "<td class=\"text-right ut-cell-fix\" title=\"Se corregirá a " + utFmtNum(c) + "\">" +
        "<span class=\"ut-cell-fix__from\">" + utFmtNum(a) + "</span>" +
        "<span class=\"ut-cell-fix__arrow\">→</span>" +
        "<span class=\"ut-cell-fix__to\">" + utFmtNum(c) + "</span>" +
        "</td>";
}

function utRenderTracking(resp) {
    var resumen = resp.resumen || {};
    var docs = resp.documentos || {};
    var oc = docs.oc || {};
    var corte = docs.corte || {};
    var envio = docs.envio || {};
    var servAb = docs.servicio_abierto || {};
    var cierre = docs.cierre || {};
    var ing = docs.ingresos_e20 || {};
    var brecha = resumen.brecha;
    var brechaCls = "";
    if (brecha !== null && brecha !== undefined && Number(brecha) !== 0) {
        brechaCls = "ut-stat--warn";
    }

    var htmlRes = "";
    htmlRes += utStat("Artículos", resumen.articulos || 0);
    htmlRes += utStat("ord_corte", utFmtNum(resumen.ord_corte));
    htmlRes += utStat("alm_corte", utFmtNum(resumen.alm_corte));
    htmlRes += utStat("taller", utFmtNum(resumen.taller));
    htmlRes += utStat("servicio", utFmtNum(resumen.servicio));
    htmlRes += utStat("stock", utFmtNum(resumen.stock));
    htmlRes += utStat("Inicio corte", utFmtNum(resumen.inicio_corte));
    htmlRes += utStat("En proceso", utFmtNum(resumen.en_proceso));
    if (ing.disponible === false || !Number(resumen.ingresos_disponible)) {
        htmlRes += utStat("Ingresos E20 " + (resp.anio || ""), "N/D");
        htmlRes += utStat("Brecha", "N/D");
    } else {
        htmlRes += utStat(
            "Ingresos E20 " + (resp.anio || ""),
            (ing.filas || 0) + " / " + utFmtNum(ing.cantidad)
        );
        htmlRes += utStat("Brecha", utFmtNum(brecha), brechaCls);
    }
    htmlRes += utStat("OC (filas/cant)", (oc.filas || 0) + " / " + utFmtNum(oc.cantidad));
    htmlRes += utStat("Corte (filas/cant)", (corte.filas || 0) + " / " + utFmtNum(corte.cantidad));
    htmlRes += utStat("Envíos (filas/saldo int.)", (envio.filas || 0) + " / " + utFmtNum(envio.saldo));
    htmlRes += utStat("Serv. abierto", (servAb.filas || 0) + " / " + utFmtNum(servAb.saldo));
    htmlRes += utStat("Cierres", (cierre.filas || 0) + " / " + utFmtNum(cierre.cantidad));
    var entExt = docs.entaller_ext || {};
    var entExtCls = "";
    if (!utNumEq(entExt.saldo, entExt.calculado)) {
        entExtCls = "ut-stat--warn";
    }
    htmlRes += utStat(
        "Ent.ext (saldo/calc)",
        utFmtNum(entExt.saldo) + " / " + utFmtNum(entExt.calculado),
        entExtCls
    );
    var dServCierre = resumen.brecha_serv_cierre;
    var dCierreIng = resumen.brecha_cierre_ing;
    var dCadena = resumen.brecha_cadena;
    htmlRes += utStat("Serv.orig", utFmtNum(resumen.servicio_origen));
    htmlRes += utStat("Cierre ini", utFmtNum(resumen.cierre_inicio));
    htmlRes += utStat("E20 desde cierre", utFmtNum(resumen.e20_cierre));
    htmlRes += utStat(
        "Δ Serv→Cierre",
        utFmtNum(dServCierre),
        !utNumEq(dServCierre, 0) ? "ut-stat--warn" : ""
    );
    if (dCierreIng === null || dCierreIng === undefined) {
        htmlRes += utStat("Δ Cierre→Ing", "N/D");
        htmlRes += utStat("Δ Cadena", "N/D");
    } else {
        htmlRes += utStat(
            "Δ Cierre→Ing",
            utFmtNum(dCierreIng),
            !utNumEq(dCierreIng, 0) ? "ut-stat--warn" : ""
        );
        htmlRes += utStat(
            "Δ Cadena",
            utFmtNum(dCadena),
            !utNumEq(dCadena, 0) ? "ut-stat--warn" : ""
        );
    }
    $("#utTrackingResumen").html(htmlRes).show();
    $("#utTrackingLeyenda").show();

    var arts = resp.articulos || [];
    var $tbD = $("#utTrackingDetalleTable tbody");
    $tbD.empty();
    var htmlD = "";
    var aCorregir = 0;
    var aCadenaMal = 0;
    var aCorregirCadena = 0;
    for (var j = 0; j < arts.length; j++) {
        var r = arts[j];
        var chOc = !utNumEq(r.ord_corte, r.ord_corte_calc);
        var chAlm = !utNumEq(r.alm_corte, r.alm_corte_calc);
        var chTal = !utNumEq(r.taller, r.taller_calc);
        var chSer = !utNumEq(r.servicio, r.servicio_calc);
        var chEnt = !utNumEq(r.entaller_ext_saldo, r.entaller_ext_calc);
        var seCorrige = chOc || chAlm || chTal || chSer || chEnt;
        if (seCorrige) {
            aCorregir++;
        }

        var badge = seCorrige
            ? "<span class=\"ut-badge-fix\">corrige</span>"
            : "<span class=\"ut-badge-ok\">ok</span>";
        var rowCls = seCorrige ? " class=\"ut-row-fix\"" : "";

        var brechaVal = r.brecha;
        var brechaTxt = (r.ingresos_disponible && brechaVal !== null && brechaVal !== undefined)
            ? utFmtNum(brechaVal)
            : "N/D";
        var brechaClsTd = "";
        if (r.ingresos_disponible && brechaVal !== null && brechaVal !== undefined && Number(brechaVal) !== 0) {
            brechaClsTd = Number(brechaVal) < 0 ? "ut-diff-neg" : "ut-diff-pos";
        }
        var ingTxt = r.ingresos_disponible ? utFmtNum(r.ingresos_e20) : "N/D";

        htmlD += "<tr" + rowCls + ">" +
            "<td>" + badge + "</td>" +
            "<td>" + utEscape(r.articulo) + "</td>" +
            "<td>" + utEscape(r.color) + "</td>" +
            "<td>" + utEscape(r.talla) + "</td>" +
            utCeldaCambio(r.ord_corte, r.ord_corte_calc) +
            "<td class=\"text-right\">" + utFmtNum(r.ord_corte_calc) + "</td>" +
            utCeldaCambio(r.alm_corte, r.alm_corte_calc) +
            "<td class=\"text-right\">" + utFmtNum(r.alm_corte_calc) + "</td>" +
            utCeldaCambio(r.taller, r.taller_calc) +
            "<td class=\"text-right\">" + utFmtNum(r.taller_calc) + "</td>" +
            utCeldaCambio(r.servicio, r.servicio_calc) +
            "<td class=\"text-right\">" + utFmtNum(r.servicio_calc) + "</td>" +
            utCeldaCambio(r.entaller_ext_saldo, r.entaller_ext_calc) +
            "<td class=\"text-right\">" + utFmtNum(r.entaller_ext_calc) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.inicio_corte) + "</td>" +
            "<td class=\"text-right\">" + ingTxt + "</td>" +
            "<td class=\"text-right " + brechaClsTd + "\">" + brechaTxt + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(r.stock) + "</td>" +
            "</tr>";
    }
    $tbD.html(htmlD || "<tr><td colspan=\"18\" class=\"text-center text-muted\">Sin artículos</td></tr>");

    var $tbC = $("#utTrackingCadenaTable tbody");
    $tbC.empty();
    var htmlC = "";
    for (var k = 0; k < arts.length; k++) {
        var c = arts[k];
        var d1 = Number(c.brecha_serv_cierre) || 0;
        var d2 = c.brecha_cierre_ing;
        var d3 = c.brecha_cadena;
        var hayAct = !utNumEq(c.servicio_origen, 0)
            || !utNumEq(c.cierre_inicio, 0)
            || !utNumEq(c.servicio_abierto, 0)
            || !utNumEq(c.cierre, 0)
            || !utNumEq(c.e20_cierre, 0);

        var cierreIniCalc = (c.cierre_inicio_calc !== null && c.cierre_inicio_calc !== undefined)
            ? c.cierre_inicio_calc
            : null;
        var servAbCalc = (c.servicio_abierto_calc !== null && c.servicio_abierto_calc !== undefined)
            ? c.servicio_abierto_calc
            : null;
        var corrigeCierre = cierreIniCalc !== null && !utNumEq(c.cierre_inicio, cierreIniCalc);
        var corrigeServAb = servAbCalc !== null && !utNumEq(c.servicio_abierto, servAbCalc);
        var seCorrigeCadena = corrigeCierre || corrigeServAb;

        var mal1 = !utNumEq(d1, 0);
        var mal2 = (d2 !== null && d2 !== undefined && !utNumEq(d2, 0));
        var mal3 = (d3 !== null && d3 !== undefined && !utNumEq(d3, 0));
        var malCadena = hayAct && (mal1 || mal2 || mal3);
        if (malCadena) {
            aCadenaMal++;
        }
        if (seCorrigeCadena) {
            aCorregirCadena++;
        }

        var badgeC = !hayAct
            ? "<span class=\"ut-badge-ok\">—</span>"
            : (seCorrigeCadena
                ? "<span class=\"ut-badge-fix\">corrige</span>"
                : (malCadena
                    ? "<span class=\"ut-badge-warn\">descuadra</span>"
                    : "<span class=\"ut-badge-ok\">ok</span>"));
        var rowC = seCorrigeCadena ? " class=\"ut-row-fix\"" : (malCadena ? " class=\"ut-row-warn\"" : "");
        var cls1 = mal1 ? (d1 < 0 ? "ut-diff-neg" : "ut-diff-pos") : "";
        var cls2 = "";
        var txt2 = "N/D";
        if (d2 !== null && d2 !== undefined) {
            txt2 = utFmtNum(d2);
            if (mal2) {
                cls2 = Number(d2) < 0 ? "ut-diff-neg" : "ut-diff-pos";
            }
        }
        var cls3 = "";
        var txt3 = "N/D";
        if (d3 !== null && d3 !== undefined) {
            txt3 = utFmtNum(d3);
            if (mal3) {
                cls3 = Number(d3) < 0 ? "ut-diff-neg" : "ut-diff-pos";
            }
        }

        var tdServOrig = "<td class=\"text-right\">" + utFmtNum(c.servicio_origen) + "</td>";
        var tdServAb = (servAbCalc !== null)
            ? utCeldaCambio(c.servicio_abierto, servAbCalc)
            : "<td class=\"text-right\">" + utFmtNum(c.servicio_abierto) + "</td>";
        var tdCierreIni = (cierreIniCalc !== null)
            ? utCeldaCambio(c.cierre_inicio, cierreIniCalc)
            : "<td class=\"text-right\">" + utFmtNum(c.cierre_inicio) + "</td>";

        htmlC += "<tr" + rowC + ">" +
            "<td>" + badgeC + "</td>" +
            "<td>" + utEscape(c.articulo) + "</td>" +
            "<td>" + utEscape(c.color) + "</td>" +
            "<td>" + utEscape(c.talla) + "</td>" +
            tdServOrig +
            tdServAb +
            tdCierreIni +
            "<td class=\"text-right " + cls1 + "\">" + utFmtNum(d1) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(c.cierre) + "</td>" +
            "<td class=\"text-right\">" + utFmtNum(c.e20_cierre) + "</td>" +
            "<td class=\"text-right " + cls2 + "\">" + txt2 + "</td>" +
            "<td class=\"text-right " + cls3 + "\">" + txt3 + "</td>" +
            "</tr>";
    }
    $tbC.html(htmlC || "<tr><td colspan=\"12\" class=\"text-center text-muted\">Sin artículos</td></tr>");

    $("#utTrackingMeta").text(
        "· " + (resp.modelo || "") +
        " · " + aCorregir + " espejo" +
        " · " + aCorregirCadena + " cadena"
    );
}

function utCargarTrackingModelo(opts) {
    opts = opts || {};
    var modelo = $.trim($("#utTrackingModelo").val() || "");
    if (!modelo) {
        swal({
            type: "warning",
            title: "Modelo requerido",
            text: "Ingresa el código del modelo a analizar.",
            confirmButtonText: "Cerrar"
        });
        return;
    }

    var $btn = $("#btnUtTrackingModelo");
    var silencioso = !!opts.silencioso;
    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-search"></i> Analizar');
        utMostrarCarga("Analizando modelo " + modelo + "…");
    } else {
        utMostrarCarga("Actualizando análisis…");
    }
    $("#utTrackingMeta").text("");

    $.post("ajax/utilidades.ajax.php", {
        accion: "trackingModelo",
        modelo: modelo
    }, function (resp) {
        utOcultarCarga();
        if (!silencioso) {
            utSetBtnLoading($btn, false, '<i class="fa fa-search"></i> Analizar');
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo analizar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        utTrackingModeloActual = resp.modelo || modelo;
        utRenderTracking(resp);
        $("#modalUtTracking").modal("show");
    }, "json").fail(function () {
        utOcultarCarga();
        if (!silencioso) {
            utSetBtnLoading($btn, false, '<i class="fa fa-search"></i> Analizar');
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

$(document).on("click", "#btnUtTrackingModelo", function () {
    utCargarTrackingModelo();
});

$(document).on("keydown", "#utTrackingModelo", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        utCargarTrackingModelo();
    }
});

$(document).on("click", "#btnUtCorregirSaldosModelo", function () {
    var modelo = utTrackingModeloActual || $.trim($("#utTrackingModelo").val() || "");
    if (!modelo) {
        return;
    }

    var $btn = $("#btnUtCorregirSaldosModelo");

    swal({
        title: "¿Corregir saldos del modelo " + modelo + "?",
        text: "También alinea la cadena: Cierre ini = pend + E20, y baja Serv.ab fantasma (no infla Serv.orig). No crea/borra E20 ni cambia stock.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, corregir",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Corregir saldos artículo');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Corrigiendo…');
        utMostrarCarga("Corrigiendo saldos de " + modelo + "…");
        $.post("ajax/utilidades.ajax.php", {
            accion: "corregirSaldosModelo",
            modelo: modelo
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Corregir saldos artículo');
            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo corregir",
                    confirmButtonText: "Cerrar"
                });
                return;
            }
            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Saldos corregidos",
                confirmButtonText: "Cerrar"
            });
            utCargarTrackingModelo({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Corregir saldos artículo');
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});

/* ---- Vendedor de última venta ---- */

var utVendedorUltimaItems = [];

function utFmtVendedor(cod, nombre) {
    var c = String(cod || "").trim();
    var n = String(nombre || "").trim();
    if (!c && !n) {
        return "—";
    }
    if (!n) {
        return c;
    }
    if (!c) {
        return n;
    }
    return c + " — " + n;
}

function utFmtGrupo(cod, nombre) {
    var c = String(cod || "").trim();
    var n = String(nombre || "").trim();
    if (!c && !n) {
        return "—";
    }
    if (!n) {
        return c;
    }
    if (!c) {
        return n;
    }
    return c + " — " + n;
}

function utActualizarBotonVendedorUltima() {
    var n = $("#utVendedorUltimaTable tbody input.ut-vendult-chk:checked").length;
    $("#btnUtActualizarVendedorUltima").prop("disabled", n < 1);
    $("#utVendedorUltimaCount").text(n + " seleccionado(s)");
}

function utRenderVendedorUltima(rows) {
    utVendedorUltimaItems = rows || [];
    var $tb = $("#utVendedorUltimaTable tbody");
    $tb.empty();

    if (!utVendedorUltimaItems.length) {
        $("#utVendedorUltimaEmpty").show();
        $("#utVendedorUltimaTableWrap").hide();
        $("#btnUtActualizarVendedorUltima").prop("disabled", true);
        $("#utVendedorUltimaCount").text("");
        return;
    }

    $("#utVendedorUltimaEmpty").hide();
    $("#utVendedorUltimaTableWrap").show();

    var html = "";
    for (var i = 0; i < utVendedorUltimaItems.length; i++) {
        var r = utVendedorUltimaItems[i];
        var doc = [r.tipo, r.documento].filter(function (x) {
            return String(x || "").trim() !== "";
        }).join(" ");
        var ultima = utFmtFechaCorta(r.fecha_ultima);
        if (doc) {
            ultima = ultima ? (ultima + " · " + doc) : doc;
        }
        var alcance = r.alcance === "grupo" ? "Grupo" : "Cliente";
        html += "<tr data-idx=\"" + i + "\">";
        html += "<td><input type=\"checkbox\" class=\"ut-vendult-chk\" checked></td>";
        html += "<td>" + utEscape(r.cliente || "") + "</td>";
        html += "<td class=\"ut-nombre\">" + utEscape(r.cliente_nombre || "") + "</td>";
        html += "<td>" + utEscape(utFmtGrupo(r.grupo, r.grupo_nombre)) + "</td>";
        html += "<td>" + utEscape(utFmtVendedor(r.vendedor_actual, r.vendedor_actual_nombre)) + "</td>";
        html += "<td><strong>" + utEscape(utFmtVendedor(r.vendedor_propuesto, r.vendedor_propuesto_nombre)) + "</strong></td>";
        html += "<td>" + utEscape(ultima) + "</td>";
        html += "<td>" + utEscape(alcance) + "</td>";
        html += "</tr>";
    }
    $tb.html(html);
    $("#utVendedorUltimaCheckAll").prop("checked", true);
    utActualizarBotonVendedorUltima();
}

function utCargarVendedorUltima(opts) {
    opts = opts || {};
    var $btn = $("#btnUtVendedorUltima");
    var silencioso = !!opts.silencioso;

    if (!silencioso) {
        utSetBtnLoading($btn, true, '<i class="fa fa-user"></i> Revisar');
        utMostrarCarga("Buscando clientes con vendedor distinto a la última venta (2 años)…");
    } else {
        $("#utVendedorUltimaLoading").show();
        $("#utVendedorUltimaEmpty").hide();
        $("#utVendedorUltimaTableWrap").hide();
        $("#btnUtActualizarVendedorUltima").prop("disabled", true);
    }

    $("#utVendedorUltimaMeta").text("");

    $.post("ajax/utilidades.ajax.php", { accion: "clientesVendedorUltimaVenta" }, function (resp) {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-user"></i> Revisar');
        } else {
            $("#utVendedorUltimaLoading").hide();
        }

        if (!resp || !resp.ok) {
            swal({
                type: "error",
                title: "Error",
                text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo consultar",
                confirmButtonText: "Cerrar"
            });
            return;
        }

        $("#utVendedorUltimaMeta").text("· " + (resp.total || 0) + " cliente(s)");
        utRenderVendedorUltima(resp.data || []);
        $("#modalUtVendedorUltima").modal("show");
    }, "json").fail(function () {
        if (!silencioso) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-user"></i> Revisar');
        } else {
            $("#utVendedorUltimaLoading").hide();
        }
        swal({
            type: "error",
            title: "Error",
            text: "No se pudo comunicar con el servidor",
            confirmButtonText: "Cerrar"
        });
    });
}

function utItemsSeleccionadosVendedorUltima() {
    var out = [];
    $("#utVendedorUltimaTable tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find("input.ut-vendult-chk").is(":checked")) {
            return;
        }
        var idx = parseInt($tr.attr("data-idx"), 10);
        if (isNaN(idx) || !utVendedorUltimaItems[idx]) {
            return;
        }
        var r = utVendedorUltimaItems[idx];
        out.push({
            cliente: r.cliente,
            vendedor_propuesto: r.vendedor_propuesto
        });
    });
    return out;
}

$(document).on("click", "#btnUtVendedorUltima", function () {
    utCargarVendedorUltima();
});

$(document).on("change", "#utVendedorUltimaCheckAll", function () {
    var on = $(this).is(":checked");
    $("#utVendedorUltimaTable tbody input.ut-vendult-chk").prop("checked", on);
    utActualizarBotonVendedorUltima();
});

$(document).on("change", "#utVendedorUltimaTable tbody input.ut-vendult-chk", function () {
    var total = $("#utVendedorUltimaTable tbody input.ut-vendult-chk").length;
    var checked = $("#utVendedorUltimaTable tbody input.ut-vendult-chk:checked").length;
    $("#utVendedorUltimaCheckAll").prop("checked", total > 0 && total === checked);
    utActualizarBotonVendedorUltima();
});

$(document).on("click", "#btnUtActualizarVendedorUltima", function () {
    var items = utItemsSeleccionadosVendedorUltima();
    if (!items.length) {
        return;
    }

    var $btn = $("#btnUtActualizarVendedorUltima");
    swal({
        title: "¿Actualizar vendedor en el maestro?",
        text: "Se asignará el vendedor de la última venta (últimos 2 años) en " + items.length + " cliente(s). No se tocan 06 ni 08. 30, 33, 18, 18a, 22 y 26 no entran.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#1e8449",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }
        utSetBtnLoading($btn, true, '<i class="fa fa-check"></i> Actualizar seleccionados');
        utMostrarCarga("Actualizando vendedor en el maestro…");

        $.post("ajax/utilidades.ajax.php", {
            accion: "actualizarVendedorUltimaVenta",
            items: JSON.stringify(items)
        }, function (resp) {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            utActualizarBotonVendedorUltima();

            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (resp && resp.mensaje) ? resp.mensaje : "No se pudo actualizar",
                    confirmButtonText: "Cerrar"
                });
                return;
            }

            swal({
                type: "success",
                title: "Listo",
                text: resp.mensaje || "Actualizado",
                confirmButtonText: "Cerrar"
            });
            utCargarVendedorUltima({ silencioso: true });
        }, "json").fail(function () {
            utOcultarCarga();
            utSetBtnLoading($btn, false, '<i class="fa fa-check"></i> Actualizar seleccionados');
            utActualizarBotonVendedorUltima();
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });
});
