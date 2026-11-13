/*
 * Comprobantes especiales → Nuevo comprobante: tipo «Exportación» (docs/efact/EXPORTACION-diseno.md, Fase 3B).
 * Al elegirlo se ocultan los bloques de la gratuita y aparece el panel #expCrear: se jala el pedido,
 * se carga su información y se emite con las funciones de facturación existentes.
 * Acceso directo: comprobantes-especiales-crear#exportacion o #exportacion-<pedido>.
 */
$(function () {
    "use strict";

    var $panel = $("#expCrear");
    if (!$panel.length) return;

    var URL = "ajax/exportacion-emitir.ajax.php";
    var TIPO = "EXPORTACION";
    var soloExportacion = $("#expSoloExportacion").val() === "1";
    var paises = {}, pedidoPedido = "", cargado = false;
    var subtituloOriginal = $("#ceForm .content-header small").first().text();

    var mHash = String(window.location.hash || "").match(/^#exportacion(?:-(\d+))?$/);
    if (mHash && mHash[1]) pedidoPedido = mHash[1];

    function esc(s) {
        return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }
    function usd(v) { return Number(v || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function bandera(iso) {
        iso = String(iso || "").toLowerCase();
        return /^[a-z]{2}$/.test(iso) ? "<img class='exp-bandera' src='https://flagcdn.com/w20/" + iso + ".png' alt='' onerror=\"this.style.display='none'\">" : "";
    }
    function ajaxErr(x) {
        var r = x && x.responseJSON;
        if (r && r.faltan) {
            $("#expCFaltan").html("<b>Faltan tablas:</b> " + $.map(r.faltan, esc).join(", ") + ". Ejecutar <code>docs/sql/exportacion-fase3.sql</code>.").show();
            return;
        }
        swal("Error", (r && r.msg) || "No se pudo completar la operación.", "error");
    }
    function urlImpresion(doc) {
        var base = window.URL_BASE_IMPRESION_BOLFACT || "vistas/reportes_ticket/impresion_bolfact.php";
        return base + "?tipo=S03&documento=" + encodeURIComponent(doc);
    }

    /* ---------- Tipo en el selector ---------- */
    var $tipo = $("#ceTipo");
    function asegurarOpcion() {
        if (!$tipo.find("option[value='" + TIPO + "']").length) {
            $tipo.append("<option value='" + TIPO + "'>Exportación (factura desde pedido)</option>");
        }
        if (soloExportacion || pedidoPedido || mHash) $tipo.val(TIPO);
        $tipo.selectpicker("refresh");
        aplicarModo();
    }

    // La gratuita (comprobantes-especiales.js) rellena el selector por ajax: volver a poner la opción al terminar
    $(document).ajaxComplete(function (e, xhr, s) {
        if (s && /comprobantes-especiales\.ajax\.php/.test(s.url || "") && /accion=catalogos/.test(s.url || "")) {
            setTimeout(asegurarOpcion, 0);
        }
    });
    if (soloExportacion) {
        $("#ceForm .content-header small").first().text("");
        asegurarOpcion();
    }

    /* ---------- Mostrar / ocultar bloques ---------- */
    function bloquesGratuita() {
        return $("#ceTipoWrap").siblings()
            .add($("#ceItems").closest(".box"))
            .add($("#ceReceptores").closest(".box"));
    }

    function pintarAyuda() {
        $("#ceAyudaTitulo").html("<i class='fa fa-lightbulb-o'></i> Cómo emitir una exportación");
        $("#ceAyudaPasos").html($.map([
            "Elija el pedido: solo salen clientes del exterior (con país en Clientes) aprobados, APT o confirmados.",
            "Revise cliente, país y detalle. Si hay un error en rojo, corríjalo en el pedido o en Clientes.",
            "Tipo de cambio: se carga solo (totales del día o API SUNAT). Puede corregirlo aquí o después en Listado y envío → Exportación.",
            "Emitir: se registra la venta en USD (exportación), la cuenta corriente y la salida del almacén 01, como el Facturar normal.",
            "Luego envíe el archivo a eFact desde Listado y envío → pestaña Exportación."
        ], function (t) { return "<li>" + esc(t) + "</li>"; }).join(""));
        $("#ceAyudaCodTitulo").text("");
        $("#ceAyudaCodigos").html("");
        $("#ceAyudaNota").text("Lista de precios 1 en USD, sin IGV (operación de exportación).");
    }

    function aplicarModo() {
        var esExp = $tipo.val() === TIPO;
        bloquesGratuita().toggle(!esExp);
        $panel.toggle(esExp);
        $("#ceForm .content-header small").first().text(esExp ? "Exportación · factura desde pedido · descuenta stock del almacén 01" : subtituloOriginal);
        if (esExp) {
            setTimeout(pintarAyuda, 0);
            if (!cargado) { cargarPedidos(); cargarTipoCambio(); }
        }
    }

    /* ---------- Tipo de cambio: totales del día → API → último registrado ---------- */
    function cargarTipoCambio() {
        var $i = $("#expCTc"), $f = $("#expCTcFuente"), $b = $("#expCTcRefrescar");
        $f.text("Buscando…");
        $b.prop("disabled", true).find("i").addClass("fa-spin");
        $.getJSON(URL, { accion: "tipo-cambio-dia" })
            .done(function (r) {
                $i.val(Number(r.venta).toFixed(4));
                $f.text(r.texto || "");
            })
            .fail(function (x) {
                var r = x && x.responseJSON;
                $f.html("<span class='text-danger'>" + esc((r && r.msg) || "No se pudo obtener el tipo de cambio.") + "</span> Puede escribirlo a mano.");
            })
            .always(function () { $b.prop("disabled", false).find("i").removeClass("fa-spin"); });
    }
    $("#expCTcRefrescar").on("click", cargarTipoCambio);
    $("#expCTc").on("input", function () { $("#expCTcFuente").text("Escrito a mano"); });
    $tipo.on("change", aplicarModo);

    /* ---------- Pedidos ---------- */
    var $sel = $("#expCPedido");

    function cargarPedidos() {
        cargado = true;
        $.getJSON(URL, { accion: "bandeja" }, function (r) {
            paises = r.paises || {};
            $("#expCSerie").text(r.serie || "—");
            var ops = "<option value=''>Seleccionar pedido</option>" + $.map(r.datos || [], function (p) {
                var iso = String(p.pais_iso || "").toUpperCase();
                return "<option value='" + esc(p.codigo) + "' data-subtext='" + esc(iso + " · $ " + usd(p.total) + " · " + p.estado) + "'>" +
                    esc(p.codigo + " · " + p.nombre) + "</option>";
            }).join("");
            $sel.html(ops);
            if (!(r.datos || []).length) $sel.append("<option value='' disabled>No hay pedidos de exportación pendientes</option>");
            if (pedidoPedido) { $sel.val(pedidoPedido); pedidoPedido = ""; }
            $sel.selectpicker("refresh");
            if ($sel.val()) revisar($sel.val());
        }).fail(ajaxErr);
    }

    function limpiar() {
        $("#expCInfo").hide();
        $("#expCEmitir").prop("disabled", true);
    }

    function revisar(pedido) {
        limpiar();
        if (!pedido) return;
        $.getJSON(URL, { accion: "revisar", pedido: pedido }, function (r) {
            if (String($sel.val()) !== String(pedido)) return;
            var p = r.pedido, iso = String(p.pais_iso || "").toUpperCase();
            $("#expCCliente").html("<b>" + esc(p.nombre) + "</b> <small class='text-muted'>" + esc(p.cliente) + "</small>");
            $("#expCPais").html(bandera(iso) + "<b>" + esc(iso) + "</b> " + esc(p.pais || paises[iso] || ""));
            $("#expCDoc").text(p.documento || "—");
            $("#expCDir").text(p.direccion || "—");
            $("#expCCond").text(p.condicion || "—");
            $("#expCEstado").text(p.estado);
            $("#expCSerie").text(r.serie || "—");
            $("#expCErrores").html($.map(r.errores || [], esc).join("<br>")).toggle((r.errores || []).length > 0);
            $("#expCAvisos").html($.map(r.avisos || [], esc).join("<br>")).toggle((r.avisos || []).length > 0);
            $("#expCDetalle tbody").html($.map(r.detalle || [], function (d) {
                var stock = Number(d.sin_stock) > 0 ? " <i class='fa fa-exclamation-triangle text-warning' title='Stock 01 menor a lo pedido'></i>" : "";
                return "<tr><td><b>" + esc(d.modelo) + "</b>" + stock + "</td><td>" + esc(d.nombre) + "</td><td>" + esc(d.linea) +
                    "</td><td class='text-center'>" + esc(Number(d.cantidad)) + "</td><td class='text-right'>" + usd(d.precio) +
                    "</td><td class='text-right'>" + usd(d.total) + "</td></tr>";
            }).join("") || "<tr><td colspan='6' class='text-center text-muted'>Sin detalle</td></tr>");
            $("#expCDscto").text(Number(p.descuento_total) ? "- " + usd(p.descuento_total) : "0.00");
            $("#expCTotal").text("$ " + usd(p.total));
            $("#expCInfo").show();
            $("#expCEmitir").prop("disabled", !r.puede_emitir);
        }).fail(ajaxErr);
    }
    $sel.on("change", function () {
        var v = $(this).val();
        if (window.history && history.replaceState) {
            history.replaceState(null, "", window.location.pathname + window.location.search + "#exportacion" + (v ? "-" + v : ""));
        }
        revisar(v);
    });

    $("#expCEmitir").on("click", function () {
        var $b = $(this), pedido = $sel.val();
        if (!pedido) return;
        swal({
            type: "question",
            title: "¿Emitir la factura de exportación?",
            text: "Pedido " + pedido + ": se asigna el número de la serie " + $("#expCSerie").text() +
                ", se descuenta el stock del almacén 01 y se registra la venta en USD y la cuenta corriente.",
            showCancelButton: true,
            confirmButtonText: "Sí, emitir",
            cancelButtonText: "Cancelar"
        }).then(function (res) {
            if (!res || !res.value) return;
            $b.prop("disabled", true);
            $.post(URL, { accion: "emitir", pedido: pedido, tipo_cambio: $("#expCTc").val() }, null, "json")
                .done(function (r) {
                    limpiar();
                    if (window.history && history.replaceState) {
                        history.replaceState(null, "", window.location.pathname + window.location.search + "#exportacion");
                    }
                    cargarPedidos();
                    swal({
                        type: "success",
                        title: "Factura " + r.documento + " emitida" + (r.tipo_cambio ? " · T.C. " + r.tipo_cambio : ""),
                        text: "Para enviarla a eFact vaya a Listado y envío → pestaña Exportación.",
                        showCancelButton: true,
                        confirmButtonText: "Imprimir",
                        cancelButtonText: "Ir al listado"
                    }).then(function (x) {
                        if (x && x.value) {
                            window.open(urlImpresion(r.documento), "_blank");
                        } else if (x && x.dismiss === "cancel") {
                            window.location = "comprobantes-especiales#exportacion";
                        }
                    });
                })
                .fail(function (x) { $b.prop("disabled", false); ajaxErr(x); });
        });
    });

    if (!soloExportacion && mHash) asegurarOpcion();
});
