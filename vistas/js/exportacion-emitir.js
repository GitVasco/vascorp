/*
 * Exportación — pestaña del listado de Comprobantes especiales: facturas emitidas y envío a eFact
 * (docs/efact/EXPORTACION-diseno.md, Fase 3B). La emisión: Nuevo comprobante → tipo Exportación (exportacion-crear.js).
 */
$(function () {
    "use strict";

    if (!$("#expEmitir").length) return;

    var URL = "ajax/exportacion-emitir.ajax.php";
    var paises = {};

    function esc(s) {
        return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }
    function usd(v) { return Number(v || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function fecha(v) {
        var p = String(v || "").substr(0, 10).split("-");
        return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : esc(v);
    }
    function bandera(iso) {
        iso = String(iso || "").toLowerCase();
        return /^[a-z]{2}$/.test(iso) ? "<img class='exp-bandera' src='https://flagcdn.com/w20/" + iso + ".png' alt='' onerror=\"this.style.display='none'\">" : "";
    }
    function pais(iso) { return bandera(iso) + "<b>" + esc(iso) + "</b> " + esc(paises[iso] || ""); }
    function ajaxErr(x) {
        var r = x && x.responseJSON;
        if (r && r.faltan) {
            $("#expFaltan").html("<b>Faltan tablas:</b> " + $.map(r.faltan, esc).join(", ") + ". Ejecutar <code>docs/sql/exportacion-fase3.sql</code>.").show();
            return;
        }
        swal("Error", (r && r.msg) || "No se pudo completar la operación.", "error");
    }
    function urlImpresion(doc) {
        var base = window.URL_BASE_IMPRESION_BOLFACT || "vistas/reportes_ticket/impresion_bolfact.php";
        return base + "?tipo=S03&documento=" + encodeURIComponent(doc);
    }

    var tabla = $("#expTablaEmitidas").DataTable({
        order: [[1, "desc"]], autoWidth: false, pageLength: 25,
        language: { emptyTable: "Sin facturas en el rango", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ factura(s)", infoEmpty: "", paginate: { next: "Sig.", previous: "Ant." } },
        columns: [
            { data: "documento", className: "exp-nowrap", render: function (v) { return "<b>" + esc(v) + "</b>"; } },
            { data: "creado_en", className: "text-center exp-nowrap", render: function (v, t) {
                return t === "display" ? fecha(v) + " <small class='text-muted'>" + esc(String(v).substr(11, 5)) + "</small>" : v;
            } },
            { data: "pedido" },
            { data: null, render: function (r) { return esc(r.nombre) + " <small class='text-muted'>" + esc(r.cliente) + "</small>"; } },
            { data: "pais_iso", className: "exp-nowrap", render: function (v, t) { return t === "display" ? pais(v) : v; } },
            { data: "total", className: "text-right exp-nowrap", render: function (v, t) { return t === "display" ? "$ " + usd(v) : v; } },
            { data: "tipo_cambio", className: "text-right", orderable: false, render: function (v, t, r) {
                if (t !== "display") return v;
                var val = v === null || v === undefined ? "" : String(Number(v));
                return "<span class='exp-nowrap'><input type='text' class='form-control exp-tc expTc' data-id='" + esc(r.id) + "' data-orig='" + esc(val) + "' value='" + esc(val) + "' placeholder='—'>" +
                    " <button class='btn btn-xs btn-default expTcAuto' data-id='" + esc(r.id) + "' title='Traer el del día de emisión (totales o API)'><i class='fa fa-refresh'></i></button></span>";
            } },
            { data: "estado", className: "text-center exp-nowrap", render: function (v, t, r) {
                if (v === "ENVIADO") return "<span class='label label-success' title='" + esc(r.archivo || "") + "'>ENVIADO</span>";
                var antiguo = r.estado_venta === "ENVIADO" ? " <i class='fa fa-exclamation-triangle text-danger' title='La venta figura como enviada por el camino antiguo'></i>" : "";
                return "<span class='label label-warning'>POR ENVIAR</span>" + antiguo;
            } },
            { data: null, orderable: false, className: "text-center exp-nowrap", render: function (r) {
                var h = "<div class='btn-group'>" +
                    "<button class='btn btn-xs btn-default expVer' data-id='" + esc(r.id) + "' title='Ver archivo para eFact'><i class='fa fa-file-text-o'></i></button>" +
                    "<a class='btn btn-xs btn-default' target='_blank' href='" + esc(urlImpresion(r.documento)) + "' title='Imprimir'><i class='fa fa-print'></i></a>";
                if (r.estado !== "ENVIADO") {
                    h += "<button class='btn btn-xs btn-success expEnviar' data-id='" + esc(r.id) + "' data-doc='" + esc(r.documento) + "' title='Enviar a eFact'><i class='fa fa-paper-plane'></i> Enviar</button>";
                }
                return h + "</div>";
            } }
        ]
    });

    function cargar() {
        $.getJSON(URL, { accion: "emitidas", desde: $("#expEmDesde").val(), hasta: $("#expEmHasta").val() }, function (r) {
            paises = r.paises || {};
            tabla.clear().rows.add(r.datos || []).draw();
        }).fail(ajaxErr);
    }
    $("#expEmBuscar").on("click", cargar);

    $("#expTablaEmitidas").on("keydown", ".expTc", function (e) { if (e.which === 13) $(this).blur(); });
    $("#expTablaEmitidas").on("change", ".expTc", function () {
        var $i = $(this);
        $i.removeClass("exp-ok exp-error");
        $.post(URL, { accion: "tipo-cambio", id: $i.attr("data-id"), tipo_cambio: $i.val() }, null, "json")
            .done(function (r) {
                var val = r.tipo_cambio === null ? "" : String(Number(r.tipo_cambio));
                $i.val(val).attr("data-orig", val).addClass("exp-ok");
                var d = tabla.row($i.closest("tr")).data();
                if (d) d.tipo_cambio = r.tipo_cambio;
            })
            .fail(function (x) { $i.val($i.attr("data-orig")).addClass("exp-error"); ajaxErr(x); });
    });

    $("#expTablaEmitidas").on("click", ".expTcAuto", function () {
        var $b = $(this), $i = $b.closest("td").find(".expTc");
        $b.prop("disabled", true).find("i").addClass("fa-spin");
        $i.removeClass("exp-ok exp-error");
        $.post(URL, { accion: "tipo-cambio-auto", id: $b.attr("data-id") }, null, "json")
            .done(function (r) {
                var val = String(Number(r.tipo_cambio));
                $i.val(val).attr("data-orig", val).addClass("exp-ok").attr("title", r.texto || "");
                var d = tabla.row($b.closest("tr")).data();
                if (d) d.tipo_cambio = r.tipo_cambio;
            })
            .fail(function (x) { $i.addClass("exp-error"); ajaxErr(x); })
            .always(function () { $b.prop("disabled", false).find("i").removeClass("fa-spin"); });
    });

    $("#expTablaEmitidas").on("click", ".expVer", function () {
        $.getJSON(URL, { accion: "ver-archivo", id: $(this).attr("data-id") }, function (r) {
            $("#expArchNombre").text(r.nombre);
            $("#expArchContenido").text(r.contenido);
            $("#expArchAvisos").html($.map(r.avisos || [], esc).join("<br>")).toggle((r.avisos || []).length > 0);
            $("#expModalArchivo").modal("show");
        }).fail(ajaxErr);
    });

    $("#expTablaEmitidas").on("click", ".expEnviar", function () {
        var $b = $(this), id = $b.attr("data-id"), doc = $b.attr("data-doc");
        swal({
            type: "warning",
            title: "¿Enviar " + doc + " a eFact?",
            text: "Se deja el archivo nuevo en la carpeta de eFact. No se puede deshacer desde aquí.",
            showCancelButton: true,
            confirmButtonText: "Sí, enviar",
            cancelButtonText: "Cancelar"
        }).then(function (res) {
            if (!res || !res.value) return;
            $b.prop("disabled", true);
            $.post(URL, { accion: "enviar", id: id }, null, "json")
                .done(function (r) {
                    var av = r.avisos && r.avisos.length ? " Avisos: " + r.avisos.join(" ") : "";
                    swal("Enviado", "Archivo " + r.archivo + " en la carpeta de eFact." + av, "success");
                    cargar();
                })
                .fail(function (x) { $b.prop("disabled", false); ajaxErr(x); });
        });
    });

    // Pestaña activa del listado en la URL (#gratuitas, #exportacion)
    var TABS = { gratuitas: "#ceTabGratuitas", exportacion: "#ceTabExportacion" };
    $("#ceTabsListado a[data-toggle='tab']").on("shown.bs.tab", function () {
        var href = $(this).attr("href");
        $.each(TABS, function (k, v) { if (v === href && window.history.replaceState) window.history.replaceState(null, "", "#" + k); });
        tabla.columns.adjust();
        if ($.fn.dataTable && $.fn.dataTable.isDataTable("#ceTabla")) $("#ceTabla").DataTable().columns.adjust();
    });
    var h = String(window.location.hash || "").replace("#", "");
    if (TABS[h]) $("#ceTabsListado a[href='" + TABS[h] + "']").tab("show");

    cargar();
});
