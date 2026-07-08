(function () {
    function formatMoney(value, simbolo) {
        var prefix = simbolo || "S/ ";
        return prefix + Number(value || 0).toLocaleString("es-PE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function scoreClass(color) {
        var map = {
            success: "dd-mini-score--success",
            warning: "dd-mini-score--warning",
            danger: "dd-mini-score--danger",
            info: "dd-mini-score--info",
            primary: "dd-mini-score--primary",
            default: "dd-mini-score--default",
        };
        return map[color] || "dd-mini-score--default";
    }

    function estadoPedidoClass(estado) {
        var map = {
            GENERADO: "label-default",
            APROBADO: "label-warning",
            APT: "label-primary",
            CONFIRMADO: "label-info",
        };
        return map[estado] || "label-default";
    }

    function renderPedidoHero(pedido, decision) {
        if (!pedido || !pedido.codigo) {
            return "";
        }

        var simbolo = pedido.simbolo || "S/ ";
        var estadoClase = estadoPedidoClass(pedido.estado || "");
        var dias = parseInt(pedido.dias_pendiente, 10) || 0;
        var alertaCupo = "";

        if (decision && decision.cupo_suficiente === false) {
            alertaCupo =
                '<div class="dd-mini-alerta dd-mini-alerta--danger">' +
                '<i class="fa fa-exclamation-triangle"></i> El pedido (' +
                formatMoney(decision.pedido_total, simbolo) +
                ") supera el cupo disponible (" +
                formatMoney(decision.cupo_disponible) +
                ")</div>";
        } else if (decision && decision.cupo_suficiente === true) {
            alertaCupo =
                '<div class="dd-mini-alerta dd-mini-alerta--ok">' +
                '<i class="fa fa-check-circle"></i> Cupo disponible cubre el pedido</div>';
        }

        return (
            '<div class="dd-mini-pedido-hero">' +
            '<div class="dd-mini-pedido-hero__top">' +
            '<span class="dd-mini-pedido-hero__eyebrow"><i class="fa fa-shopping-cart"></i> Pedido en análisis</span>' +
            '<span class="label ' + estadoClase + ' dd-mini-pedido-estado">' + (pedido.estado || "—") + "</span>" +
            "</div>" +
            '<div class="dd-mini-pedido-hero__main">' +
            '<div class="dd-mini-pedido-hero__codigo">' + pedido.codigo + "</div>" +
            '<div class="dd-mini-pedido-hero__monto">' + formatMoney(pedido.total, simbolo) + "</div>" +
            "</div>" +
            '<div class="dd-mini-pedido-hero__meta">' +
            (pedido.fecha ? '<span><i class="fa fa-calendar"></i> ' + pedido.fecha + "</span>" : "") +
            '<span><i class="fa fa-clock-o"></i> ' + dias + "d en pipeline</span>" +
            "</div>" +
            alertaCupo +
            "</div>"
        );
    }

    function renderMiniIc(data) {
        var riesgo = data.riesgo || {};
        var linea = data.linea || {};
        var comercial = data.comercial || {};
        var fidelidad = data.fidelidad || {};
        var cliente = data.cliente || {};
        var pedido = data.pedido_detalle || null;
        var decision = data.decision || {};

        var historial =
            riesgo.historial_pct !== null && riesgo.historial_pct !== undefined
                ? riesgo.historial_pct + "% al día"
                : "Sin historial";

        return (
            '<div class="dd-mini-ic">' +
            renderPedidoHero(pedido, decision) +
            '<div class="dd-mini-ic-cliente">' +
            '<div class="dd-mini-ic-cliente__avatar"><i class="fa fa-user"></i></div>' +
            '<div class="dd-mini-ic-cliente__info">' +
            "<strong>" + (cliente.nombre || "") + "</strong>" +
            '<span class="dd-mini-ic-cliente__cod">' + (cliente.codigo || "") + "</span>" +
            "</div>" +
            "</div>" +
            '<div class="row dd-mini-ic-scores">' +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score ' + scoreClass(riesgo.color) + '">' +
            '<span class="dd-mini-score-num">' + (riesgo.score !== null ? riesgo.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Riesgo</span><small>' + (riesgo.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--default">' +
            '<span class="dd-mini-score-num">' + (comercial.score !== null ? comercial.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Comercial</span><small>' + (comercial.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--default">' +
            '<span class="dd-mini-score-num">' + (fidelidad.score !== null ? fidelidad.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Fidelidad</span><small>' + (fidelidad.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--' + (linea.accion_color || "default") + '">' +
            '<span class="dd-mini-score-lbl dd-mini-score-lbl--action">Línea crédito</span><strong>' + (linea.accion || "—") + "</strong></div></div>" +
            "</div>" +
            '<div class="dd-mini-ic-section-title"><i class="fa fa-bar-chart"></i> Situación financiera</div>' +
            '<div class="row dd-mini-ic-metrics">' +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Deuda actual</span><strong>' + formatMoney(linea.deuda_actual) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric dd-mini-metric--highlight"><span>Cupo disponible</span><strong>' + formatMoney(linea.disponible) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Línea recomendada</span><strong>' + formatMoney(linea.recomendada) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Utilización</span><strong>' + (linea.utilizacion || 0) + "%</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Atraso promedio</span><strong>' + (riesgo.atraso_promedio || 0) + " días</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Docs vencidos</span><strong>' + (riesgo.docs_vencidos || 0) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Historial de pago</span><strong>' + historial + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Última compra</span><strong>' + (comercial.ultima_compra || "—") + "</strong></div></div>" +
            "</div>" +
            (linea.explicacion
                ? '<div class="callout callout-' + (linea.accion_color || "info") + ' dd-mini-ic-callout"><p><i class="fa fa-lightbulb-o"></i> ' + linea.explicacion + "</p></div>"
                : "") +
            "</div>"
        );
    }

    $(document).ready(function () {
        if ($("#ddFiltroVendedor").length) {
            $("#ddFiltroVendedor").selectpicker("refresh");
        }
    });

    $(document).on("click", ".btnDdMiniIc", function () {
        var $btn = $(this);
        var cliente = $btn.data("cliente");
        var pedido = $btn.data("pedido");
        var nombre = $btn.data("nombre");

        $("#ddMiniIcTitulo").html('<i class="fa fa-user-circle"></i> Análisis para decisión');
        $("#ddMiniIcSubtitulo").text(nombre || cliente || "");
        $("#ddMiniIcBody").html(
            '<div class="dd-mini-ic-loading text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando análisis del cliente…</p></div>'
        );
        $("#ddMiniIcLinkCompleto").attr("href", "#").addClass("disabled");
        $("#modalDdMiniIc").modal("show");

        $.ajax({
            url: "ajax/dashboard-decisiones/mini-inteligencia.ajax.php",
            method: "POST",
            dataType: "json",
            data: { cliente: cliente, pedido: pedido },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    $("#ddMiniIcBody").html(
                        '<div class="alert alert-warning">' +
                            (resp && resp.msg ? resp.msg : "No se pudo cargar el análisis.") +
                            "</div>"
                    );
                    return;
                }

                $("#ddMiniIcBody").html(renderMiniIc(resp));
                $("#ddMiniIcLinkCompleto")
                    .attr("href", resp.url_completo || "#")
                    .removeClass("disabled");
            })
            .fail(function () {
                $("#ddMiniIcBody").html(
                    '<div class="alert alert-danger">Error al consultar Inteligencia Comercial.</div>'
                );
            });
    });

    $(document).on("click", ".dd-link-pedido", function (e) {
        e.preventDefault();

        var codigo = $(this).data("codigo");
        if (!codigo) {
            return;
        }

        var datos = new FormData();
        datos.append("codPedido", codigo);

        $.ajax({
            url: "ajax/pedidos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
        });

        window.open(
            "vistas/reportes_ticket/impresion_pedido.php?codigo=" + codigo,
            "_blank"
        );
    });
})();
