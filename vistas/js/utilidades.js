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
        cancelButtonText: "Cancelar",
        closeOnConfirm: true
    }, function (confirmado) {
        if (!confirmado) {
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
        cancelButtonText: "Cancelar",
        closeOnConfirm: true
    }, function (confirmado) {
        if (!confirmado) {
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
