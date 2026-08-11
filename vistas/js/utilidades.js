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
