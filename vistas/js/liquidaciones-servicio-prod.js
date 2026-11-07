/* Liquidaciones de servicios de producción: listado, previsualización, emisión, pago, anulación e impresión (PDF). */
(function ($) {
    if (!$("#lspTabla").length) return;

    var URL = "ajax/liquidaciones-servicio-prod.ajax.php";
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
    function aviso(tipo, html) {
        $("#lspAvisos").append('<div class="alert alert-' + tipo + '" style="margin-bottom:6px">' + html + "</div>");
    }
    function errorAjax(xhr) {
        var m = "Error de comunicación.";
        try { m = JSON.parse(xhr.responseText).msg || m; } catch (e) {}
        swal({ type: "error", title: "Error", text: m });
    }
    function etiquetaEstado(e) {
        if (e === "PAGADA") return '<span class="label label-success">PAGADA</span>';
        if (e === "ANULADA") return '<span class="label label-danger">ANULADA</span>';
        return '<span class="label label-primary">POR PAGAR</span>';
    }

    /* ---------- Talleres ---------- */
    function cargarTalleres() {
        $.getJSON(URL, { accion: "talleres" }, function (r) {
            if (!r.ok) { swal({ type: "warning", title: "Módulo no disponible", text: r.msg }); return; }
            var opts = "";
            $.each(r.datos, function (i, t) {
                opts += '<option value="' + esc(t.cod_sector) + '">' + esc(t.nom_sector) + "</option>";
            });
            $("#lspTaller").html('<option value="">Seleccione...</option>' + opts);
            $("#lspFiltroTaller").html('<option value="">Todos los talleres</option>' + opts);
            $("#lspTaller, #lspFiltroTaller").selectpicker("refresh");
        }).fail(errorAjax);
    }

    /* ---------- Listado ---------- */
    function listar() {
        $.getJSON(URL, {
            accion: "listar",
            desde: $("#lspFiltroDesde").val(),
            hasta: $("#lspFiltroHasta").val(),
            taller: $("#lspFiltroTaller").val(),
            estado: $("#lspFiltroEstado").val()
        }, function (r) {
            if (!r.ok) { swal({ type: "warning", title: "Aviso", text: r.msg }); return; }
            if (tabla) { tabla.destroy(); }
            var html = "";
            $.each(r.datos, function (i, o) {
                var anulada = o.estado === "ANULADA", pagada = o.estado === "PAGADA";
                var btns = '<a class="btn btn-default btn-sm" target="_blank" href="ajax/liquidaciones-servicio-prod.pdf.php?id=' + o.id + '"><i class="fa fa-print"></i> Imprimir</a> ';
                if (o.estado === "LIQUIDADA") {
                    btns += '<button class="btn btn-success btn-sm lspPagar" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-money"></i> Pagar</button> ' +
                        '<button class="btn btn-warning btn-sm lspAnular" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-ban"></i> Anular</button> ';
                }
                if (pagada) {
                    btns += '<button class="btn btn-default btn-sm lspRevertir" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-undo"></i> Revertir pago</button> ';
                } else {
                    btns += '<button class="btn btn-danger btn-sm lspEliminar" data-id="' + o.id + '" data-num="' + esc(o.numero) + '"><i class="fa fa-trash"></i></button>';
                }
                html += "<tr>" +
                    "<td><b>" + esc(o.numero) + "</b></td>" +
                    "<td>" + esc(o.taller_nombre) + "</td>" +
                    "<td>" + esc(o.taller_ruc) + "</td>" +
                    "<td>" + fecha(o.fecha_desde) + " - " + fecha(o.fecha_hasta) + "</td>" +
                    '<td class="text-right">' + num(o.total_cierres) + "</td>" +
                    '<td class="text-right">' + num(o.total_unidades) + "</td>" +
                    '<td class="text-right">' + num(o.total_importe, 2) + "</td>" +
                    "<td>" + etiquetaEstado(o.estado) +
                    (pagada && o.fecha_pago ? " <small>" + fecha(o.fecha_pago) + "</small>" : "") +
                    (!anulada && +o.lineas_sin_osp > 0 ? ' <span class="label label-warning">' + o.lineas_sin_osp + " línea(s) sin OSP</span>" : "") + "</td>" +
                    "<td>" + btns + "</td></tr>";
            });
            $("#lspTabla tbody").html(html);
            tabla = $("#lspTabla").DataTable({
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
        $("#lspAvisos, #lspPrevia").empty();
        $("#lspPie").hide();
        $("#lspBtnEmitir").prop("disabled", true);
    }

    function previsualizar() {
        resetPrevia();
        var taller = $("#lspTaller").val(), desde = $("#lspDesde").val(), hasta = $("#lspHasta").val();
        if (!taller || !desde || !hasta) { aviso("warning", "Seleccione taller y rango de fechas."); return; }
        if (desde > hasta) { aviso("warning", "La fecha inicial no puede ser mayor a la final."); return; }

        $.getJSON(URL, { accion: "vista_previa", taller: taller, desde: desde, hasta: hasta }, function (r) {
            if (!r.ok) { aviso("danger", esc(r.msg)); return; }
            var bloquea = false;
            var t = r.taller || {};
            if (!t.doc) {
                aviso("danger", "El taller no tiene RUC / razón social cargados (datos fiscales de talleres de la GRE manual). No se puede liquidar.");
                bloquea = true;
            }
            if (r.sin_precio.length) {
                aviso("danger", "Sin precio de servicio para los modelos: <b>" + esc(r.sin_precio.join(", ")) + "</b>. Regístrelos en Precio servicio.");
                bloquea = true;
            }
            if (r.lineas_sin_osp > 0) {
                aviso("warning", "<b>" + r.lineas_sin_osp + " línea(s) sin OSP</b> (" + num(r.unidades_sin_osp) + " un. / S/ " + num(r.importe_sin_osp, 2) +
                    "). Se puede liquidar igual; la OSP se completa sola cuando se emita.");
            }
            if (!t.cuenta_detraccion && !t.cuenta_bcp) {
                aviso("warning", "El taller no tiene cuentas (detracción / BCP) registradas en Sectores; saldrán en blanco.");
            }
            if (r.ya_liquidados > 0) {
                aviso("info", r.ya_liquidados + " cierre(s) del rango ya pertenecen a otra liquidación y no se incluyen.");
            }
            if (r.ya_pagados > 0) {
                aviso("info", r.ya_pagados + " cierre(s) del rango ya figuran como PAGADO (módulo Pagos) y no se incluyen.");
            }
            if (!r.cierres.length) {
                aviso("warning", "No hay cierres por liquidar para ese taller y rango.");
                return;
            }

            var h = '<table class="table table-bordered table-condensed"><thead><tr>' +
                "<th>Cierre</th><th>Guía</th><th>Fecha</th><th>Servicio(s) de origen</th><th>OSP</th><th class='text-right'>Unidades</th><th class='text-right'>Importe</th></tr></thead><tbody>";
            $.each(r.cierres, function (i, c) {
                var osp = $.map(c.osp, esc).join(", ");
                if (c.lineas_sin_osp > 0) {
                    osp += (osp ? " " : "") + '<span class="label label-warning">' + c.lineas_sin_osp + " línea(s) sin OSP</span>";
                }
                h += "<tr><td>" + esc(c.codigo) + "</td><td>" + esc(c.guia) + "</td><td>" + fecha(c.fecha) + "</td><td>" + (c.servicios.length ? $.map(c.servicios, esc).join(", ") : '<span class="text-muted">sin servicio</span>') + "</td><td>" + osp + "</td>" +
                    '<td class="text-right">' + num(c.unidades) + '</td><td class="text-right">' + num(c.importe, 2) + "</td></tr>";
            });
            h += '</tbody><tfoot><tr><th colspan="5" class="text-right">TOTAL</th><th class="text-right">' + num(r.total_unidades) +
                '</th><th class="text-right">S/ ' + num(r.total_importe, 2) + "</th></tr></tfoot></table>";
            $("#lspPrevia").html(h);
            $("#lspPie").show();
            previaValida = !bloquea;
            $("#lspBtnEmitir").prop("disabled", bloquea);
        }).fail(errorAjax);
    }

    /* ---------- Emitir ---------- */
    function emitir() {
        if (!previaValida) return;
        var $b = $("#lspBtnEmitir").prop("disabled", true);
        $.post(URL, {
            accion: "crear",
            taller: $("#lspTaller").val(),
            desde: $("#lspDesde").val(),
            hasta: $("#lspHasta").val(),
            observaciones: $("#lspObs").val()
        }, function (r) {
            if (!r.ok) {
                $b.prop("disabled", false);
                swal({ type: "error", title: "No se pudo liquidar", text: r.msg });
                return;
            }
            $("#lspModalNueva").modal("hide");
            swal({ type: "success", title: "Liquidación " + r.numero + " emitida", timer: 1800, showConfirmButton: false });
            window.open("ajax/liquidaciones-servicio-prod.pdf.php?id=" + r.id, "_blank");
            listar();
        }, "json").fail(function (xhr) { $b.prop("disabled", false); errorAjax(xhr); });
    }

    /* ---------- Pagar ---------- */
    $("#lspTabla").on("click", ".lspPagar", function () {
        $("#lspPagarId").val($(this).data("id"));
        $("#lspPagarNum").text($(this).data("num"));
        $("#lspPagarFecha").val(new Date().toISOString().substr(0, 10));
        $("#lspPagarRef").val("");
        $("#lspModalPagar").modal("show");
    });
    $("#lspBtnPagar").on("click", function () {
        var $b = $(this).prop("disabled", true);
        $.post(URL, {
            accion: "pagar",
            id: $("#lspPagarId").val(),
            fecha_pago: $("#lspPagarFecha").val(),
            referencia: $("#lspPagarRef").val()
        }, function (r) {
            $b.prop("disabled", false);
            if (r.ok) {
                $("#lspModalPagar").modal("hide");
                swal({ type: "success", title: "Pago registrado", timer: 1500, showConfirmButton: false });
                listar();
            } else swal({ type: "error", title: "Error", text: r.msg });
        }, "json").fail(function (xhr) { $b.prop("disabled", false); errorAjax(xhr); });
    });

    $("#lspTabla").on("click", ".lspRevertir", function () {
        var id = $(this).data("id"), numL = $(this).data("num");
        swal({
            title: "¿Revertir el pago de " + numL + "?",
            text: "La liquidación vuelve a «Por pagar» y sus cierres a POR PAGAR.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, revertir",
            cancelButtonText: "Cancelar"
        }).then(function () {
            $.post(URL, { accion: "revertir_pago", id: id }, function (r) {
                if (r.ok) { swal({ type: "success", title: "Pago revertido", timer: 1500, showConfirmButton: false }); listar(); }
                else swal({ type: "error", title: "Error", text: r.msg });
            }, "json").fail(errorAjax);
        }, function () {});
    });

    /* ---------- Anular ---------- */
    $("#lspTabla").on("click", ".lspAnular", function () {
        var id = $(this).data("id"), numL = $(this).data("num");
        swal({
            title: "Anular " + numL,
            text: "Los cierres quedarán libres para otra liquidación. Indique el motivo:",
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
                if (r.ok) { swal({ type: "success", title: "Liquidación anulada", timer: 1500, showConfirmButton: false }); listar(); }
                else swal({ type: "error", title: "Error", text: r.msg });
            }, "json").fail(errorAjax);
        }, function () {});
    });

    /* ---------- Eliminar ---------- */
    $("#lspTabla").on("click", ".lspEliminar", function () {
        var id = $(this).data("id"), numL = $(this).data("num");
        swal({
            title: "¿Eliminar " + numL + "?",
            text: "Se borra definitivamente y sus cierres quedan libres para armar otra. Si ya la entregó, mejor use Anular.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then(function () {
            $.post(URL, { accion: "eliminar", id: id }, function (r) {
                if (r.ok) { swal({ type: "success", title: "Liquidación eliminada", timer: 1500, showConfirmButton: false }); listar(); }
                else swal({ type: "error", title: "Error", text: r.msg });
            }, "json").fail(errorAjax);
        }, function () {});
    });

    /* ---------- Eventos ---------- */
    $("#lspBtnFiltrar").on("click", listar);
    $("#lspBtnPrevia").on("click", previsualizar);
    $("#lspBtnEmitir").on("click", emitir);
    $("#lspTaller, #lspDesde, #lspHasta").on("change", resetPrevia);
    $("#lspModalNueva").on("show.bs.modal", function () {
        resetPrevia();
        $("#lspTaller").selectpicker("refresh");
        $("#lspObs").val("");
    });

    cargarTalleres();
    listar();
})(jQuery);
