/* Órdenes de servicio de producción: listado, previsualización, emisión, anulación e impresión (PDF). */
(function ($) {
    if (!$("#ospTabla").length) return;

    var URL = "ajax/ordenes-servicio-prod.ajax.php";
    var tabla = null;
    var previaValida = false;

    function esc(s) {
        return $("<div>").text(s == null ? "" : s).html();
    }
    function num(n, d) {
        return Number(n || 0).toLocaleString("es-PE", { minimumFractionDigits: d || 0, maximumFractionDigits: d || 0 });
    }
    function fecha(f) {
        if (!f) return "";
        var p = String(f).substr(0, 10).split("-");
        return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : f;
    }
    /* Neto / IGV / Total según si los precios del taller ya incluyen IGV */
    function desglose(importe, conIgv) {
        importe = Number(importe || 0);
        var neto, igv, total;
        if (conIgv) { total = importe; neto = Math.round(total / 1.18 * 100) / 100; igv = Math.round((total - neto) * 100) / 100; }
        else { neto = importe; igv = Math.round(neto * 0.18 * 100) / 100; total = Math.round((neto + igv) * 100) / 100; }
        return { neto: neto, igv: igv, total: total };
    }
    function aviso(tipo, html) {
        $("#ospAvisos").append('<div class="alert alert-' + tipo + '" style="margin-bottom:6px">' + html + "</div>");
    }
    function errorAjax(xhr) {
        var m = "Error de comunicación.";
        try { m = JSON.parse(xhr.responseText).msg || m; } catch (e) {}
        swal({ type: "error", title: "Error", text: m });
    }

    /* ---------- Talleres ---------- */
    function cargarTalleres() {
        $.getJSON(URL, { accion: "talleres" }, function (r) {
            if (!r.ok) { swal({ type: "warning", title: "Módulo no disponible", text: r.msg }); return; }
            var opts = "";
            $.each(r.datos, function (i, t) {
                opts += '<option value="' + esc(t.cod_sector) + '">' + esc(t.nom_sector) + "</option>";
            });
            $("#ospTaller").html('<option value="">Seleccione...</option>' + opts);
            $("#ospFiltroTaller").html('<option value="">Todos los talleres</option>' + opts);
            $("#ospTaller, #ospFiltroTaller").selectpicker("refresh");
        }).fail(errorAjax);
    }

    /* ---------- Listado ---------- */
    function listar() {
        $.getJSON(URL, {
            accion: "listar",
            desde: $("#ospFiltroDesde").val(),
            hasta: $("#ospFiltroHasta").val(),
            taller: $("#ospFiltroTaller").val(),
            estado: $("#ospFiltroEstado").val()
        }, function (r) {
            if (!r.ok) { swal({ type: "warning", title: "Aviso", text: r.msg }); return; }
            if (tabla) { tabla.destroy(); }
            var html = "";
            $.each(r.datos, function (i, o) {
                var anulada = o.estado === "ANULADA";
                html += "<tr>" +
                    "<td><b>" + esc(o.numero) + "</b></td>" +
                    "<td>" + esc(o.taller_nombre) + "</td>" +
                    "<td>" + esc(o.taller_ruc) + "</td>" +
                    "<td>" + fecha(o.fecha_desde) + " - " + fecha(o.fecha_hasta) + "</td>" +
                    "<td>" + fecha(o.fecha_emision) + "</td>" +
                    '<td class="text-right">' + num(o.total_servicios) + "</td>" +
                    '<td class="text-right">' + num(o.total_unidades) + "</td>" +
                    '<td class="text-right">' + (+o.regularizacion === 1 ? '<span class="text-muted">—</span>' : num(o.total_importe, 2)) + "</td>" +
                    '<td><span class="label label-' + (anulada ? "danger" : "success") + '">' + esc(o.estado) + "</span>" +
                    (+o.regularizacion === 1 ? ' <span class="label label-info" title="Servicios ya enviados: sin importes ni guías">REGULARIZACIÓN</span>' : "") +
                    (!anulada && +o.guias_pendientes > 0 ? ' <a href="#" class="label label-warning ospGuias" data-id="' + o.id + '" data-num="' + esc(o.numero) + '" title="Emitir guías">' + o.guias_pendientes + " guía(s) pendiente(s)</a>" : "") + "</td>" +
                    '<td>' + (anulada || +o.regularizacion === 1 ? "" : '<button class="btn btn-success btn-sm ospGuias" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-truck"></i> Guías</button> ') +
                    '<a class="btn btn-default btn-sm" target="_blank" href="ajax/ordenes-servicio-prod.pdf.php?id=' + o.id + '"><i class="fa fa-print"></i> Imprimir</a> ' +
                    (anulada ? "" : '<button class="btn btn-warning btn-sm ospAnular" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-ban"></i> Anular</button> ') +
                    '<button class="btn btn-danger btn-sm ospEliminar" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-trash"></i> Eliminar</button>' +
                    "</td></tr>";
            });
            $("#ospTabla tbody").html(html);
            tabla = $("#ospTabla").DataTable({
                order: [],
                language: {
                    sZeroRecords: "No se encontraron resultados",
                    sEmptyTable: "Ningún dato disponible en esta tabla",
                    sInfo: "Mostrando _START_ al _END_ de _TOTAL_",
                    sInfoEmpty: "Mostrando 0 de 0",
                    sInfoFiltered: "(filtrado de _MAX_)",
                    sLengthMenu: "Mostrar _MENU_ registros",
                    sSearch: "Buscar:",
                    oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" }
                }
            });
        }).fail(errorAjax);
    }

    /* ---------- Previsualización ---------- */
    function resetPrevia() {
        previaValida = false;
        $("#ospAvisos, #ospPrevia").empty();
        $("#ospPie").hide();
        $("#ospBtnEmitir").prop("disabled", true);
    }

    function previsualizar() {
        resetPrevia();
        var taller = $("#ospTaller").val(), desde = $("#ospDesde").val(), hasta = $("#ospHasta").val();
        if (!taller || !desde || !hasta) { aviso("warning", "Seleccione taller y rango de fechas."); return; }
        if (desde > hasta) { aviso("warning", "La fecha inicial no puede ser mayor a la final."); return; }

        var regul = $("#ospRegularizacion").is(":checked");
        $.getJSON(URL, { accion: "vista_previa", taller: taller, desde: desde, hasta: hasta, regularizacion: regul ? "1" : "0" }, function (r) {
            if (!r.ok) { aviso("danger", esc(r.msg)); return; }
            var bloquea = false;
            var t = r.taller || {};
            if (!t.doc) {
                aviso("danger", "El taller no tiene RUC / razón social cargados (datos fiscales de talleres de la GRE manual). No se puede emitir la orden.");
                bloquea = true;
            }
            if (r.sin_precio.length) {
                aviso("danger", "Sin precio de servicio para los modelos: <b>" + esc(r.sin_precio.join(", ")) + "</b>. Regístrelos en Precio servicio.");
                bloquea = true;
            }
            if (r.sin_guia.length) {
                aviso("info", r.sin_guia.length + " servicio(s) aún sin guía de remisión (<b>" + esc(r.sin_guia.join(", ")) + "</b>). La orden se emite con la guía «PENDIENTE»; al emitir la guía se completa sola en la orden.");
            }
            if (!t.cuenta_detraccion && !t.cuenta_bcp) {
                aviso("warning", "El taller no tiene cuentas (detracción / BCP) registradas en Sectores; saldrán en blanco en la orden.");
            }
            if (r.ya_ordenados > 0) {
                aviso("info", r.ya_ordenados + " servicio(s) del rango ya pertenecen a otra orden y no se incluyen.");
            }
            if (!r.servicios.length) {
                aviso("warning", "No hay servicios libres para ese taller y rango.");
                return;
            }

            if (regul) {
                aviso("info", "<b>Orden de regularización:</b> registra los servicios ya enviados. No lleva importes ni exige precios, y no cuenta guías pendientes.");
            }
            var h = '<table class="table table-bordered table-condensed"><thead><tr>' +
                "<th>Servicio</th><th>Guía</th><th>Fecha</th><th>Modelos / colores</th><th class='text-right'>Unidades</th>" + (regul ? "" : "<th class='text-right'>Importe</th>") + "</tr></thead><tbody>";
            $.each(r.servicios, function (i, s) {
                var imp = 0;
                $.each(s.detalle, function (j, d) { imp += d.importe; });
                h += "<tr><td>" + esc(s.codigo) + "</td><td>" + (s.guia ? esc(s.guia) : (regul ? "—" : '<span class="text-warning">PENDIENTE</span>')) + "</td>" +
                    "<td>" + fecha(s.fecha) + "</td><td>" + s.detalle.length + " líneas</td>" +
                    '<td class="text-right">' + num(s.unidades) + "</td>" + (regul ? "" : '<td class="text-right">' + num(imp, 2) + "</td>") + "</tr>";
            });
            var dg = desglose(r.total_importe, +((r.taller || {}).precios_igv) === 1);
            if (regul) {
                h += '</tbody><tfoot><tr><th colspan="4" class="text-right">TOTAL (' + r.servicios.length + ' servicios)</th><th class="text-right">' + num(r.total_unidades) + "</th></tr></tfoot></table>";
            } else h += '</tbody><tfoot>' +
                '<tr><td colspan="4" class="text-right">Subtotal neto (sin IGV)</td><td></td><td class="text-right">S/ ' + num(dg.neto, 2) + "</td></tr>" +
                '<tr><td colspan="4" class="text-right">IGV (18%)</td><td></td><td class="text-right">S/ ' + num(dg.igv, 2) + "</td></tr>" +
                '<tr><th colspan="4" class="text-right">TOTAL</th><th class="text-right">' + num(r.total_unidades) +
                '</th><th class="text-right">S/ ' + num(dg.total, 2) + "</th></tr></tfoot></table>";
            $("#ospPrevia").html(h);
            $("#ospPie").show();
            previaValida = !bloquea;
            $("#ospBtnEmitir").prop("disabled", bloquea);
        }).fail(errorAjax);
    }

    /* ---------- Emitir ---------- */
    function emitir() {
        if (!previaValida) return;
        var $b = $("#ospBtnEmitir").prop("disabled", true);
        $.post(URL, {
            accion: "crear",
            taller: $("#ospTaller").val(),
            desde: $("#ospDesde").val(),
            hasta: $("#ospHasta").val(),
            observaciones: $("#ospObs").val(),
            regularizacion: $("#ospRegularizacion").is(":checked") ? "1" : "0"
        }, function (r) {
            if (!r.ok) {
                $b.prop("disabled", false);
                swal({ type: "error", title: "No se pudo emitir", text: r.msg });
                return;
            }
            $("#ospModalNueva").modal("hide");
            swal({ type: "success", title: "Orden " + r.numero + " emitida", timer: 1800, showConfirmButton: false });
            window.open("ajax/ordenes-servicio-prod.pdf.php?id=" + r.id, "_blank");
            listar();
            setTimeout(function () { abrirGuias(r.id, r.numero); }, 500);
        }, "json").fail(function (xhr) { $b.prop("disabled", false); errorAjax(xhr); });
    }

    /* ---------- Guías de remisión de la orden ---------- */
    var guiasOrden = { id: 0, num: "" };

    function cargarGuias() {
        $("#ospGuiasTabla").html('<p class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        $.getJSON(URL, { accion: "ver", id: guiasOrden.id }, function (r) {
            if (!r.ok) { $("#ospGuiasTabla").html('<div class="alert alert-danger">' + esc(r.msg) + "</div>"); return; }
            var o = r.orden, pend = 0;
            if (+o.regularizacion === 1) {
                $("#ospGuiasAviso").html('<div class="alert alert-info">Orden de regularización: los servicios ya fueron enviados, por lo que no requiere guías de remisión.</div>');
                $("#ospGuiasTabla").empty();
                return;
            }
            var h = '<table class="table table-bordered table-condensed"><thead><tr>' +
                "<th>Servicio</th><th>Fecha</th><th class='text-right'>Unidades</th><th>Guía de remisión</th><th>Independiente</th></tr></thead><tbody>";
            $.each(o.servicios, function (i, s) {
                var tiene = s.guia !== null && s.guia !== "";
                if (!tiene) pend++;
                var url = "index.php?ruta=gre-manual-crear&servicio=" + encodeURIComponent(s.servicio);
                h += "<tr><td>" + esc(s.servicio) + "</td><td>" + fecha(s.fecha_servicio) + "</td>" +
                    '<td class="text-right">' + num(s.unidades) + "</td><td>" +
                    (tiene ? '<span class="label label-success">' + esc(s.guia) + "</span>" : '<span class="label label-warning">PENDIENTE</span>') + "</td><td>" +
                    (tiene || !r.puede_guia || o.estado === "ANULADA" ? "" :
                        '<a class="btn btn-success btn-xs" target="_blank" rel="noopener" href="' + url + '"><i class="fa fa-truck"></i> Emitir guía</a>') +
                    "</td></tr>";
            });
            h += "</tbody></table>";
            $("#ospGuiasTabla").html(h);
            if (pend > 0 && r.puede_guia && o.estado !== "ANULADA") {
                $("#ospGuiasTabla").append('<div class="well well-sm" style="margin-top:10px">' +
                    '<a class="btn btn-primary btn-sm" target="_blank" rel="noopener" href="index.php?ruta=gre-manual-crear&orden=' + o.id + '">' +
                    '<i class="fa fa-truck"></i> Emitir una guía global (' + pend + ' servicio' + (pend > 1 ? "s" : "") + ')</a> ' +
                    '<span class="text-muted">Una sola guía que ampara todos los servicios pendientes de esta orden.</span></div>');
            }
            var av;
            if (!r.puede_guia && pend) {
                av = '<div class="alert alert-warning">No tiene permiso para emitir guías de remisión; pida a quien las emite que las genere desde Servicios.</div>';
            } else if (pend) {
                av = '<div class="alert alert-info"><b>' + pend + " de " + o.servicios.length + "</b> servicio(s) aún sin guía.</div>";
            } else {
                av = '<div class="alert alert-success">Todos los servicios de esta orden tienen guía de remisión.</div>';
            }
            $("#ospGuiasAviso").html(av);
        }).fail(function (xhr) { $("#ospGuiasTabla").empty(); errorAjax(xhr); });
    }

    function abrirGuias(id, numOrden) {
        guiasOrden = { id: id, num: numOrden };
        $("#ospGuiasNum").text(numOrden);
        $("#ospModalGuias").modal("show");
        cargarGuias();
    }

    $("#ospTabla").on("click", ".ospGuias", function (e) {
        e.preventDefault();
        abrirGuias($(this).data("id"), $(this).data("num"));
    });
    $("#ospGuiasActualizar").on("click", cargarGuias);
    // Al volver de la pestaña de la guía, refresca solo
    $(window).on("focus", function () {
        if ($("#ospModalGuias").hasClass("in")) cargarGuias();
    });

    /* ---------- Anular ---------- */
    $("#ospTabla").on("click", ".ospAnular", function () {
        var id = $(this).data("id"), num = $(this).data("num");
        swal({
            title: "Anular " + num,
            text: "Los servicios quedarán libres para otra orden. Indique el motivo:",
            input: "text",
            showCancelButton: true,
            confirmButtonText: "Anular",
            cancelButtonText: "Cancelar",
            inputValidator: function (v) {
                return new Promise(function (resolve, reject) {
                    if ($.trim(v)) resolve();
                    else reject("El motivo es obligatorio");
                });
            }
        }).then(function (motivo) {
            $.post(URL, { accion: "anular", id: id, motivo: motivo }, function (r) {
                if (r.ok) { swal({ type: "success", title: "Orden anulada", timer: 1500, showConfirmButton: false }); listar(); }
                else swal({ type: "error", title: "Error", text: r.msg });
            }, "json").fail(errorAjax);
        }, function () {});
    });

    /* ---------- Eliminar ---------- */
    $("#ospTabla").on("click", ".ospEliminar", function () {
        var id = $(this).data("id"), numOrden = $(this).data("num");
        swal({
            title: "¿Eliminar " + numOrden + "?",
            text: "Se borra la orden definitivamente y sus servicios quedan libres para armar otra. Si ya la entregó o la usa como sustento, mejor use Anular.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then(function () {
            $.post(URL, { accion: "eliminar", id: id }, function (r) {
                if (r.ok) { swal({ type: "success", title: "Orden eliminada", timer: 1500, showConfirmButton: false }); listar(); }
                else swal({ type: "error", title: "Error", text: r.msg });
            }, "json").fail(errorAjax);
        }, function () {});
    });

    /* ---------- Eventos ---------- */
    $("#ospBtnFiltrar").on("click", listar);
    $("#ospBtnPrevia").on("click", previsualizar);
    $("#ospBtnEmitir").on("click", emitir);
    $("#ospTaller, #ospDesde, #ospHasta").on("change", resetPrevia);
    $("#ospModalNueva").on("show.bs.modal", function () {
        resetPrevia();
        $("#ospTaller").selectpicker("refresh");
        $("#ospObs").val("");
        $("#ospRegularizacion").prop("checked", false);
    });
    $("#ospRegularizacion").on("change", resetPrevia);

    cargarTalleres();
    listar();
})(jQuery);
