/*
 * Exportación en pedidos (docs/efact/EXPORTACION-diseno.md, Fase 3A).
 * Un pedido es de exportación si el cliente tiene país distinto de Perú (Clientes → «Cliente del exterior»).
 * - Listados de pedidos: marca EXP + bandera junto al cliente.
 * - Crear/editar pedido: aviso con país y lista de precios.
 * - Modal Facturar: en factura/boleta solo deja series FX para el exterior y las quita para clientes de Perú.
 * Es solo ayuda en pantalla: el bloqueo real está en el servidor al generar el documento.
 */
(function ($) {
    "use strict";

    var URL = "ajax/exportacion-pedidos.ajax.php";
    var TABLAS = ".tablaPedidosCV, .tablaPedidosGenerados, .tablaPedidosAprobados, .tablaPedidosAPT, .tablaPedidosConfirmados, .tablaPedidosFacturados";

    function esc(s) {
        return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function bandera(iso) {
        iso = String(iso || "").toLowerCase();
        return /^[a-z]{2}$/.test(iso)
            ? "<img src='https://flagcdn.com/w20/" + iso + ".png' alt='' style='height:11px;margin-right:3px;vertical-align:baseline' onerror=\"this.style.display='none'\">"
            : "";
    }

    function etiqueta(iso) {
        return "<span class='label bg-navy expPedBadge' style='margin-left:6px;font-size:10px' title='Pedido de exportación'>" +
            bandera(iso) + "EXP " + esc(iso) + "</span>";
    }

    /* ---------- Listados: marca junto al cliente ---------- */
    var mapaExterior = null, pidiendoMapa = null;

    function conMapa(cb) {
        if (mapaExterior) return cb(mapaExterior);
        if (!pidiendoMapa) {
            pidiendoMapa = $.getJSON(URL, { accion: "clientes-exterior" }).then(function (r) {
                mapaExterior = (r && r.clientes) || {};
                return mapaExterior;
            }, function () {
                mapaExterior = {};
                return mapaExterior;
            });
        }
        pidiendoMapa.then(cb);
    }

    function marcarTabla(tabla) {
        if (!$.fn.dataTable || !$.fn.dataTable.isDataTable(tabla)) return;
        conMapa(function (mapa) {
            if ($.isEmptyObject(mapa)) return;
            $(tabla).DataTable().rows({ page: "current" }).every(function () {
                var d = this.data(), nodo = this.node();
                if (!nodo || !d) return;
                var cod = $("<div>").html(String(d[1] == null ? "" : d[1])).text().trim();
                var iso = mapa[cod];
                var $nom = $(nodo).find(".pedidosCvClienteNombre");
                if (iso && $nom.length && !$nom.find(".expPedBadge").length) {
                    $nom.append(etiqueta(iso));
                }
            });
        });
    }

    /* ---------- Crear / editar pedido: aviso del cliente ---------- */
    function avisoCliente(codigo) {
        var $sel = $("#seleccionarCliente");
        if (!$sel.length) return;
        var $grupo = $sel.closest(".form-group");
        var $aviso = $("#expAvisoCliente");
        if (!$aviso.length) {
            $aviso = $("<p id='expAvisoCliente' class='help-block small' style='display:none;margin:4px 0 0'></p>");
            $grupo.append($aviso);
        }
        codigo = String(codigo || "").trim();
        if (!codigo) { $aviso.hide(); return; }
        $.getJSON(URL, { accion: "cliente", codigo: codigo }, function (r) {
            if (!r || !r.exterior) { $aviso.hide(); return; }
            var h = etiqueta(r.pais_iso) + " <b>Exportación</b> · " + esc(r.pais) + " · se factura con serie FX en dólares.";
            if (!r.lista_ok) {
                h += " <span class='text-danger'><b>El cliente no tiene la lista de precios 1</b> (tiene " + esc(r.lista || "—") +
                    "): cámbiela en Clientes o en el pedido, si no, no se podrá facturar.</span>";
            }
            $aviso.html(h).show();
        });
    }

    $(document).on("change", "#seleccionarCliente", function () { avisoCliente($(this).val()); });
    $(function () {
        if ($("#seleccionarCliente").length) {
            avisoCliente($("#seleccionarCliente").val() || $("#codCliente").val());
        }
    });

    /* ---------- Modal Facturar: series FX ---------- */
    var infoPedido = null, pedidoActual = "";

    function avisoFacturar(html) {
        var $a = $("#expAvisoFacturar");
        if (!$a.length) {
            $a = $("<p id='expAvisoFacturar' class='small' style='display:none;margin:4px 0 0'></p>");
            $("#serie").closest(".mf-field").append($a);
        }
        if (html) { $a.html(html).show(); } else { $a.hide().empty(); }
    }

    function filtrarSeries() {
        var $serie = $("#serie");
        if (!$serie.length || !infoPedido || !infoPedido.ok) return;
        var tdoc = String($("#tdoc").val() || "");
        var $ops = $serie.find("option").filter(function () { return this.value !== ""; });
        $ops.prop("disabled", false);

        if (tdoc !== "01" && tdoc !== "03") {
            avisoFacturar(infoPedido.exterior ? etiqueta(infoPedido.pais_iso) + " Cliente del exterior." : "");
            refrescar($serie);
            return;
        }

        var fx = $ops.filter(function () { return /^FX/i.test(this.value); });
        if (infoPedido.exterior) {
            // La exportación se emite solo en Comprobantes especiales → Exportación → Emitir desde pedido
            $ops.prop("disabled", true);
            if ($serie.val()) $serie.val("").trigger("change");
            avisoFacturar(etiqueta(infoPedido.pais_iso) + " <span class='text-danger'>Cliente del exterior (" + esc(infoPedido.pais) +
                "): la factura de exportación se emite en <a href='comprobantes-especiales-crear#exportacion-" + encodeURIComponent(pedidoActual) +
                "'><b>Comprobantes especiales → Nuevo comprobante → Exportación</b></a>.</span>");
        } else {
            fx.prop("disabled", true);
            if (/^FX/i.test($serie.val() || "")) $serie.val("").trigger("change");
            avisoFacturar("");
        }
        refrescar($serie);
    }

    function refrescar($s) {
        if ($.fn.selectpicker && $s.hasClass("selectpicker")) $s.selectpicker("refresh");
    }

    // En las tablas (no en document): pedidoscv.js corta la propagación del clic en Facturar
    function alAbrirFacturar() {
        var codigo = String($(this).attr("codigo") || "");
        pedidoActual = codigo;
        infoPedido = null;
        avisoFacturar("");
        if (!codigo) return;
        $.getJSON(URL, { accion: "pedido", codigo: codigo }, function (r) {
            if (codigo !== pedidoActual) return;
            infoPedido = r;
            filtrarSeries();
        });
    }

    $(function () {
        $(TABLAS).on("click", "button.btnFacturar", alAbrirFacturar);
        $(TABLAS).on("draw.dt", function () { marcarTabla(this); });
        $(TABLAS).each(function () { marcarTabla(this); });
    });

    // Las series llegan por ajax al elegir el tipo de documento (pedidoscv.js): filtrar al terminar
    $(document).ajaxComplete(function (e, xhr, settings) {
        if (settings && /talonarios\.ajax\.php/.test(settings.url || "")) {
            setTimeout(filtrarSeries, 0);
        }
    });
})(jQuery);
