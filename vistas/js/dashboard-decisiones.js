(function () {
    var ddDecisionContext = {
        cliente: "",
        pedido: "",
        nombre: "",
    };

    function formatMoney(value, simbolo) {
        var prefix = simbolo || "S/ ";
        return prefix + Number(value || 0).toLocaleString("es-PE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
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

    function severidadClase(severidad) {
        var map = {
            critica: "danger",
            alta: "danger",
            media: "warning",
            baja: "info",
        };
        return map[String(severidad || "").toLowerCase()] || "default";
    }

    function renderGrupoCupoBanner(linea, decision) {
        var modo = (decision && decision.modo_cupo) || (linea && linea.modo);
        var grupo = (decision && decision.grupo) || (linea && linea.grupo);

        if (modo !== "grupo" || !grupo || !grupo.nombre) {
            return "";
        }

        return (
            '<div class="dd-dc-grupo-banner">' +
            '<i class="fa fa-sitemap"></i> ' +
            "Cupo validado por grupo empresarial: <strong>" + escapeHtml(grupo.nombre) + "</strong>" +
            (linea && linea.etiqueta_referencia
                ? " · " + escapeHtml(linea.etiqueta_referencia) + " " + formatMoney(linea.referencia || linea.aprobada || linea.recomendada)
                : "") +
            "</div>"
        );
    }

    function renderPedidoHero(pedido, decision) {
        if (!pedido || !pedido.codigo) {
            return "";
        }

        var simbolo = pedido.simbolo || (pedido.lista === "precio1" ? "$ " : "S/ ");
        var estadoClase = estadoPedidoClass(pedido.estado || "");
        var dias = parseInt(pedido.dias_pendiente, 10) || 0;
        var alertaCupo = "";

        var esGrupo = decision && decision.modo_cupo === "grupo";
        var prefijoGrupo = esGrupo ? " (cupo del grupo)" : "";

        if (decision && decision.cupo_suficiente === false) {
            alertaCupo =
                '<div class="dd-mini-alerta dd-mini-alerta--danger">' +
                '<i class="fa fa-exclamation-triangle"></i> El pedido (' +
                formatMoney(decision.pedido_total, simbolo) +
                ") supera el cupo disponible" + prefijoGrupo + " (" +
                formatMoney(decision.cupo_disponible) +
                ")</div>";
        } else if (decision && decision.cupo_suficiente === true) {
            alertaCupo =
                '<div class="dd-mini-alerta dd-mini-alerta--ok">' +
                '<i class="fa fa-check-circle"></i> Cupo disponible' + prefijoGrupo + " cubre el pedido</div>";
        }

        return (
            '<div class="dd-mini-pedido-hero">' +
            '<div class="dd-mini-pedido-hero__top">' +
            '<span class="dd-mini-pedido-hero__eyebrow"><i class="fa fa-shopping-cart"></i> Pedido en análisis</span>' +
            '<span class="label ' + estadoClase + ' dd-mini-pedido-estado">' + escapeHtml(pedido.estado || "—") + "</span>" +
            "</div>" +
            '<div class="dd-mini-pedido-hero__main">' +
            '<div class="dd-mini-pedido-hero__codigo">' + escapeHtml(pedido.codigo) + "</div>" +
            '<div class="dd-mini-pedido-hero__monto">' + formatMoney(pedido.total, simbolo) + "</div>" +
            "</div>" +
            '<div class="dd-mini-pedido-hero__meta">' +
            (pedido.fecha ? '<span><i class="fa fa-calendar"></i> ' + escapeHtml(pedido.fecha) + "</span>" : "") +
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
            renderGrupoCupoBanner(linea, decision) +
            renderPedidoHero(pedido, decision) +
            '<div class="dd-mini-ic-cliente">' +
            '<div class="dd-mini-ic-cliente__avatar"><i class="fa fa-user"></i></div>' +
            '<div class="dd-mini-ic-cliente__info">' +
            "<strong>" + escapeHtml(cliente.nombre || "") + "</strong>" +
            '<span class="dd-mini-ic-cliente__cod">' + escapeHtml(cliente.codigo || "") + "</span>" +
            "</div>" +
            "</div>" +
            '<div class="row dd-mini-ic-scores">' +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score ' + scoreClass(riesgo.color) + '">' +
            '<span class="dd-mini-score-num">' + (riesgo.score !== null ? riesgo.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Riesgo</span><small>' + escapeHtml(riesgo.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--default">' +
            '<span class="dd-mini-score-num">' + (comercial.score !== null ? comercial.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Comercial</span><small>' + escapeHtml(comercial.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--default">' +
            '<span class="dd-mini-score-num">' + (fidelidad.score !== null ? fidelidad.score : "—") + "</span>" +
            '<span class="dd-mini-score-lbl">Fidelidad</span><small>' + escapeHtml(fidelidad.etiqueta || "") + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="dd-mini-score dd-mini-score--' + (linea.accion_color || "default") + '">' +
            '<span class="dd-mini-score-lbl dd-mini-score-lbl--action">Línea crédito</span><strong>' + escapeHtml(linea.accion || "—") + "</strong></div></div>" +
            "</div>" +
            '<div class="dd-mini-ic-section-title"><i class="fa fa-bar-chart"></i> Situación financiera</div>' +
            '<div class="row dd-mini-ic-metrics">' +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Deuda actual</span><strong>' + formatMoney(linea.deuda_actual) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric dd-mini-metric--highlight"><span>Cupo disponible</span><strong>' + formatMoney(linea.disponible) + "</strong>" +
            (linea.modo === "grupo" ? "<small>Consolidado grupo</small>" : "") + "</div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>' + escapeHtml(linea.etiqueta_referencia || "Línea recomendada") + "</span><strong>" + formatMoney(linea.referencia || linea.recomendada) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Utilización</span><strong>' + (linea.utilizacion || 0) + "%</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Atraso promedio</span><strong>' + (riesgo.atraso_promedio || 0) + " días</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Docs vencidos</span><strong>' + (riesgo.docs_vencidos || 0) + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Historial de pago</span><strong>' + historial + "</strong></div></div>" +
            '<div class="col-sm-4 col-xs-6"><div class="dd-mini-metric"><span>Última compra</span><strong>' + escapeHtml(comercial.ultima_compra || "—") + "</strong></div></div>" +
            "</div>" +
            (linea.explicacion
                ? '<div class="callout callout-' + (linea.accion_color || "info") + ' dd-mini-ic-callout"><p><i class="fa fa-lightbulb-o"></i> ' + escapeHtml(linea.explicacion) + "</p></div>"
                : "") +
            "</div>"
        );
    }

    function scoreChipColor(score) {
        var n = Number(score);
        if (isNaN(n)) {
            return "default";
        }
        if (n >= 90) {
            return "success";
        }
        if (n >= 80) {
            return "primary";
        }
        if (n >= 70) {
            return "info";
        }
        if (n >= 60) {
            return "warning";
        }
        return "danger";
    }

    function formatFechaCorta(value) {
        if (!value) {
            return "";
        }
        var str = String(value);
        if (/^\d{2}\/\d{2}\/\d{4}$/.test(str)) {
            return str;
        }
        var d = new Date(str.replace(" ", "T"));
        if (isNaN(d.getTime())) {
            return str;
        }
        return d.toLocaleDateString("es-PE");
    }

    function poblarMotivoSelectpicker(motivos) {
        var $motivo = $("#modalDdDecisionCredito").find(".dd-motivo-select");
        if (!$motivo.length) {
            return;
        }

        if ($motivo.parent().hasClass("bootstrap-select")) {
            $motivo.selectpicker("destroy");
        }

        $motivo.empty();
        $motivo.append('<option value="">Seleccione motivo…</option>');

        (motivos || []).forEach(function (motivo) {
            if (!motivo || !motivo.codigo) {
                return;
            }
            $motivo.append(
                $("<option></option>")
                    .attr("value", motivo.codigo)
                    .text(motivo.etiqueta || motivo.codigo)
            );
        });

        $motivo.addClass("selectpicker");
        $motivo.selectpicker({
            liveSearch: true,
            size: 8,
            noneSelectedText: "Seleccione motivo…",
            liveSearchPlaceholder: "Buscar motivo…",
            width: "100%",
            container: "body",
            dropupAuto: false,
        });
    }

    function renderMotivoOptions(motivos, selected) {
        var html = '<option value="">Seleccione motivo…</option>';

        (motivos || []).forEach(function (motivo) {
            var sel = selected === motivo.codigo ? " selected" : "";
            html +=
                '<option value="' +
                escapeHtml(motivo.codigo) +
                '"' +
                sel +
                ">" +
                escapeHtml(motivo.etiqueta) +
                "</option>";
        });

        return html;
    }

    function renderTipoSolicitudOptions(catalogo, permitidas) {
        var html = '<option value="">Seleccione solicitud…</option>';
        var mapa = {};

        (permitidas || []).forEach(function (codigo) {
            mapa[codigo] = true;
        });

        (catalogo.tipos_solicitud || []).forEach(function (tipo) {
            if (!mapa[tipo.codigo]) {
                return;
            }
            html +=
                '<option value="' +
                escapeHtml(tipo.codigo) +
                '">' +
                escapeHtml(tipo.etiqueta) +
                "</option>";
        });

        return html;
    }

    function renderResolucionOptions(resoluciones, selected) {
        var html = '<option value="">Seleccione resolución…</option>';

        (resoluciones || []).forEach(function (item) {
            var sel = selected === item.codigo ? " selected" : "";
            html +=
                '<option value="' +
                escapeHtml(item.codigo) +
                '"' +
                sel +
                ">" +
                escapeHtml(item.etiqueta) +
                "</option>";
        });

        return html;
    }

    function renderDecisionActual(decision) {
        if (!decision) {
            return "";
        }

        return (
            '<div class="dd-dc-alert dd-dc-alert--' +
            severidadClase(decision.motivo_severidad) +
            '">' +
            '<div class="dd-dc-alert__icon"><i class="fa fa-ban"></i></div>' +
            '<div class="dd-dc-alert__body">' +
            "<strong>No aprobado:</strong> " +
            escapeHtml(decision.motivo_etiqueta) +
            (decision.comentario
                ? '<p class="dd-dc-alert__text">' + escapeHtml(decision.comentario) + "</p>"
                : "") +
            '<span class="dd-dc-alert__meta">' +
            escapeHtml(decision.usuario_registro_nombre || "—") +
            " · " +
            escapeHtml(decision.fecha_registro || "") +
            "</span>" +
            "</div></div>"
        );
    }

    function renderCupoComparacion(ic, pedido, simbolo) {
        if (!ic || !ic.decision) {
            return "";
        }

        var decision = ic.decision;
        var pedidoTotal = Number(decision.pedido_total || pedido.total || 0);
        var cupo = Number(decision.cupo_disponible || 0);
        var max = Math.max(pedidoTotal, cupo, 1);
        var pctPedido = Math.min(100, (pedidoTotal / max) * 100);
        var pctCupo = Math.min(100, (cupo / max) * 100);
        var suficiente = decision.cupo_suficiente === true;
        var insuficiente = decision.cupo_suficiente === false;
        var estadoClase = suficiente ? "ok" : insuficiente ? "bad" : "neutral";
        var delta = formatMoney(Math.max(0, pedidoTotal - cupo), simbolo);
        var mensaje = suficiente
            ? "Cupo" + (decision.modo_cupo === "grupo" ? " del grupo" : "") + " cubre el pedido"
            : insuficiente
            ? "Faltan " + delta + (decision.modo_cupo === "grupo" ? " (cupo grupo)" : "")
            : "Revise cupo vs pedido";

        return (
            '<div class="dd-dc-cupo dd-dc-cupo--' +
            estadoClase +
            '">' +
            '<div class="dd-dc-cupo__head">' +
            '<strong><i class="fa fa-balance-scale"></i> ' +
            escapeHtml(mensaje) +
            "</strong></div>" +
            '<div class="dd-dc-cupo__row">' +
            '<span class="dd-dc-cupo__label">Ped.</span>' +
            '<div class="dd-dc-cupo__track"><div class="dd-dc-cupo__fill dd-dc-cupo__fill--pedido" style="width:' +
            pctPedido +
            '%"></div></div>' +
            "<strong>" +
            formatMoney(pedidoTotal, simbolo) +
            "</strong></div>" +
            '<div class="dd-dc-cupo__row">' +
            '<span class="dd-dc-cupo__label">Cupo</span>' +
            '<div class="dd-dc-cupo__track"><div class="dd-dc-cupo__fill dd-dc-cupo__fill--cupo" style="width:' +
            pctCupo +
            '%"></div></div>' +
            "<strong>" +
            formatMoney(cupo, simbolo) +
            "</strong></div></div>"
        );
    }

    function renderMetricasCompactas(riesgo, linea, comercial, pedido, historial) {
        var docsVenc = pedido.docs_vencidos != null
            ? Number(pedido.docs_vencidos)
            : Number(riesgo.docs_vencidos || 0);
        var ultimaCompra =
            pedido.ultima_compra_fmt ||
            formatFechaCorta(comercial.ultima_compra) ||
            "—";

        var items = [
            ["Deuda" + (linea.modo === "grupo" ? " grupo" : ""), formatMoney(linea.deuda_actual)],
            [linea.etiqueta_referencia || "Línea rec.", formatMoney(linea.referencia || linea.recomendada)],
            ["Utilización", (linea.utilizacion || 0) + "%"],
            ["Docs venc.", String(docsVenc), docsVenc > 0 ? "danger" : null],
            ["Atraso", (riesgo.atraso_promedio || 0) + " d"],
            ["Historial", historial],
            ["Últ. compra", ultimaCompra],
        ];

        if (Number(pedido.deuda_vencida) > 0) {
            items.push(["Deuda venc.", formatMoney(pedido.deuda_vencida), "danger"]);
        }

        var html = '<dl class="dd-dc-kv">';
        items.forEach(function (item) {
            var clase = item[2] === "danger" ? " dd-dc-kv__item--danger" : "";
            html +=
                '<div class="dd-dc-kv__item' +
                clase +
                '"><dt>' +
                escapeHtml(item[0]) +
                "</dt><dd>" +
                escapeHtml(item[1]) +
                "</dd></div>";
        });
        html += "</dl>";
        return html;
    }

    function renderScoresInline(riesgo, comercial, fidelidad, linea) {
        var lineaColor = linea.accion_color && linea.accion_color !== "default"
            ? linea.accion_color
            : "default";

        return (
            '<div class="dd-dc-chips">' +
            '<span class="dd-dc-chip dd-dc-chip--' +
            scoreChipColor(riesgo.score) +
            '">Riesgo <b>' +
            (riesgo.score !== null ? riesgo.score : "—") +
            "</b> " +
            escapeHtml(riesgo.etiqueta || "") +
            "</span>" +
            '<span class="dd-dc-chip dd-dc-chip--' +
            scoreChipColor(comercial.score) +
            '">Com. <b>' +
            (comercial.score !== null ? comercial.score : "—") +
            "</b> " +
            escapeHtml(comercial.etiqueta || "") +
            "</span>" +
            '<span class="dd-dc-chip dd-dc-chip--' +
            scoreChipColor(fidelidad.score) +
            '">Fid. <b>' +
            (fidelidad.score !== null ? fidelidad.score : "—") +
            "</b> " +
            escapeHtml(fidelidad.etiqueta || "") +
            "</span>" +
            '<span class="dd-dc-chip dd-dc-chip--' +
            lineaColor +
            '">Línea: <b>' +
            escapeHtml(linea.accion || "—") +
            "</b></span>" +
            "</div>"
        );
    }

    function renderDecisionInteligencia(data) {
        var ic = data.inteligencia;
        if (!ic || !ic.ok) {
            return (
                '<div class="dd-dc-section dd-dc-section--muted">' +
                '<p class="text-muted"><i class="fa fa-info-circle"></i> Sin inteligencia comercial.</p>' +
                "</div>"
            );
        }

        var riesgo = ic.riesgo || {};
        var linea = ic.linea || {};
        var comercial = ic.comercial || {};
        var fidelidad = ic.fidelidad || {};
        var cliente = ic.cliente || {};
        var pedido = data.pedido || {};
        var simbolo = pedido.lista === "precio1" ? "$ " : "S/ ";
        var historial =
            riesgo.historial_pct !== null && riesgo.historial_pct !== undefined
                ? riesgo.historial_pct + "% al día"
                : "Sin historial";

        return (
            '<div class="dd-dc-intel">' +
            renderGrupoCupoBanner(linea, ic.decision) +
            '<div class="dd-dc-context">' +
            '<div class="dd-dc-context__cliente">' +
            '<div class="dd-dc-context__avatar"><i class="fa fa-building"></i></div>' +
            '<div class="dd-dc-context__text">' +
            "<strong>" +
            escapeHtml(cliente.nombre || ddDecisionContext.nombre || "Cliente") +
            "</strong>" +
            '<span class="dd-dc-context__meta">' +
            escapeHtml(cliente.codigo || ddDecisionContext.cliente || "") +
            (comercial.vendedor ? " · " + escapeHtml(comercial.vendedor) : "") +
            "</span></div></div>" +
            '<div class="dd-dc-context__pedido">' +
            "<strong>" +
            escapeHtml(pedido.codigo) +
            "</strong>" +
            "<span>" +
            formatMoney(pedido.total, simbolo) +
            '</span><span class="label ' +
            estadoPedidoClass(pedido.estado) +
            '">' +
            escapeHtml(pedido.estado || "") +
            "</span>" +
            (pedido.dias_pendiente
                ? '<span class="dd-dc-context__dias">' +
                  parseInt(pedido.dias_pendiente, 10) +
                  "d</span>"
                : "") +
            "</div></div>" +
            renderScoresInline(riesgo, comercial, fidelidad, linea) +
            '<div class="dd-dc-main-grid">' +
            renderCupoComparacion(ic, pedido, simbolo) +
            renderMetricasCompactas(riesgo, linea, comercial, pedido, historial) +
            "</div>" +
            (linea.explicacion
                ? '<div class="dd-dc-tip" title="' +
                  escapeHtml(linea.explicacion) +
                  '"><i class="fa fa-lightbulb-o"></i> ' +
                  escapeHtml(linea.explicacion) +
                  "</div>"
                : "") +
            "</div>"
        );
    }

    function renderFormRegistrar(pedido, catalogo) {
        return (
            '<div class="dd-dc-card dd-dc-card--form">' +
            '<div class="dd-dc-card__head dd-dc-card__head--compact">' +
            '<h5><i class="fa fa-pencil-square-o"></i> Registrar motivo <small>Créditos y Cobranzas</small></h5>' +
            "</div>" +
            '<form id="ddFormRegistrarDecision" class="dd-decision-form dd-decision-form--compact">' +
            '<input type="hidden" name="codigo_pedido" value="' +
            escapeHtml(pedido.codigo) +
            '">' +
            '<input type="hidden" name="codigo_cliente" value="' +
            escapeHtml(ddDecisionContext.cliente) +
            '">' +
            '<div class="row">' +
            '<div class="col-sm-5">' +
            '<div class="form-group">' +
            "<label>Motivo</label>" +
            '<select class="form-control dd-motivo-select" name="motivo_codigo" required>' +
            "</select>" +
            "</div></div>" +
            '<div class="col-sm-7">' +
            '<div class="form-group">' +
            "<label>Detalle</label>" +
            '<textarea class="form-control input-sm" name="comentario" rows="2" placeholder="Contexto para ventas…"></textarea>' +
            "</div></div></div>" +
            '<button type="submit" class="btn btn-danger btn-sm dd-dc-btn-submit">' +
            '<i class="fa fa-save"></i> Guardar motivo' +
            "</button></form></div>"
        );
    }

    function renderSolicitudes(solicitudes, catalogo, permisos, decision) {
        var html =
            '<div class="dd-dc-card dd-dc-card--compact"><div class="dd-dc-card__head dd-dc-card__head--compact">' +
            '<h5><i class="fa fa-paper-plane"></i> Solicitudes</h5>' +
            "</div>";

        if (!solicitudes || !solicitudes.length) {
            html += '<p class="dd-dc-empty"><i class="fa fa-inbox"></i> Aún no hay solicitudes.</p>';
        } else {
            html += '<div class="dd-decision-solicitudes">';
            solicitudes.forEach(function (sol) {
                var estadoClase =
                    sol.estado === "APROBADA"
                        ? "success"
                        : sol.estado === "RECHAZADA"
                        ? "danger"
                        : "warning";

                html +=
                    '<div class="dd-decision-solicitud">' +
                    '<div class="dd-decision-solicitud__top">' +
                    "<strong>" +
                    escapeHtml(sol.tipo_etiqueta || sol.tipo_solicitud) +
                    '</strong> <span class="label label-' +
                    estadoClase +
                    '">' +
                    escapeHtml(sol.estado) +
                    "</span>" +
                    "</div>" +
                    "<p>" +
                    escapeHtml(sol.justificacion) +
                    "</p>" +
                    '<p class="dd-decision-meta"><small>' +
                    escapeHtml(sol.usuario_solicita_nombre || "") +
                    " · " +
                    escapeHtml(sol.fecha_solicitud || "") +
                    "</small></p>";

                if (sol.estado === "PENDIENTE" && permisos.resolver) {
                    html +=
                        '<form class="dd-form-resolver-solicitud dd-dc-resolve-form" data-id="' +
                        sol.id +
                        '">' +
                        '<div class="form-group">' +
                        '<label>Resolución de créditos</label>' +
                        '<select class="form-control input-sm dd-resolucion-select" name="resolucion_codigo" required>' +
                        renderResolucionOptions(catalogo.resoluciones) +
                        "</select>" +
                        "</div>" +
                        '<div class="form-group">' +
                        '<textarea class="form-control input-sm" name="comentario_resolucion" rows="2" placeholder="Comentario para ventas (opcional)"></textarea>' +
                        "</div>" +
                        '<div class="btn-group btn-group-sm btn-group-justified">' +
                        '<div class="btn-group"><button type="submit" class="btn btn-success" data-estado="APROBADA"><i class="fa fa-check"></i> Aprobar</button></div>' +
                        '<div class="btn-group"><button type="submit" class="btn btn-danger" data-estado="RECHAZADA"><i class="fa fa-times"></i> Rechazar</button></div>' +
                        "</div>" +
                        "</form>";
                } else if (sol.resolucion_etiqueta) {
                    html +=
                        '<p class="dd-dc-resolve-done"><i class="fa fa-check-circle"></i> ' +
                        escapeHtml(sol.resolucion_etiqueta) +
                        (sol.comentario_resolucion
                            ? " — " + escapeHtml(sol.comentario_resolucion)
                            : "") +
                        "</p>";
                }

                html += "</div>";
            });
            html += "</div>";
        }

        if (decision && permisos.solicitar) {
            html +=
                '<div class="dd-dc-nueva-solicitud">' +
                '<form id="ddFormNuevaSolicitud" class="dd-decision-form">' +
                '<input type="hidden" name="id_decision" value="' +
                decision.id +
                '">' +
                '<div class="form-group">' +
                "<label>Nueva solicitud</label>" +
                '<select class="form-control input-sm" name="tipo_solicitud" required>' +
                renderTipoSolicitudOptions(catalogo, decision.solicitudes_permitidas) +
                "</select>" +
                "</div>" +
                '<div class="form-group">' +
                '<textarea class="form-control input-sm" name="justificacion" rows="3" placeholder="Explique por qué debería revisarse esta decisión…" required></textarea>' +
                "</div>" +
                '<button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-send"></i> Enviar solicitud</button>' +
                "</form></div>";
        }

        html += "</div>";
        return html;
    }

    function renderBitacora(eventos) {
        if (!eventos || !eventos.length) {
            return "";
        }

        var html =
            '<div class="dd-dc-card dd-dc-card--compact dd-dc-card--timeline"><div class="dd-dc-card__head dd-dc-card__head--compact">' +
            '<h5><i class="fa fa-history"></i> Historial</h5>' +
            "</div>" +
            '<ul class="dd-decision-timeline">';
            eventos.forEach(function (ev) {
                var icono = "fa-circle";
                if (ev.tipo_evento === "DECISION_REGISTRADA") icono = "fa-ban";
                if (ev.tipo_evento === "SOLICITUD_CREADA") icono = "fa-paper-plane";
                if (ev.tipo_evento === "SOLICITUD_RESUELTA") icono = "fa-gavel";
                if (ev.tipo_evento === "DECISION_CERRADA") icono = "fa-check";

                html +=
                    "<li>" +
                    '<span class="dd-decision-timeline__icon"><i class="fa ' +
                    icono +
                    '"></i></span>' +
                    '<div class="dd-decision-timeline__content">' +
                    '<span class="dd-decision-timeline__tipo">' +
                    escapeHtml(ev.tipo_evento.replace(/_/g, " ")) +
                    "</span>" +
                    '<span class="dd-decision-timeline__detalle">' +
                    escapeHtml(ev.detalle || "") +
                    "</span>" +
                    '<span class="dd-decision-timeline__meta">' +
                    escapeHtml(ev.usuario_nombre || "") +
                    " · " +
                    escapeHtml(ev.fecha || "") +
                    "</span></div></li>";
            });
            html += "</ul></div>";
        return html;
    }

    function renderDecisionCredito(data) {
        var pedido = data.pedido || {};
        var decision = data.decision || null;
        var catalogo = data.catalogo || {};
        var permisos = catalogo.permisos || {};

        var html =
            '<div class="dd-decision-panel">' +
            renderDecisionInteligencia(data);

        if (decision) {
            html += renderDecisionActual(decision);
            html += renderSolicitudes(data.solicitudes, catalogo, permisos, decision);

            if (permisos.resolver) {
                html +=
                    '<div class="dd-dc-card dd-dc-card--compact dd-dc-card--form">' +
                    '<div class="dd-dc-card__head dd-dc-card__head--compact">' +
                    '<h5><i class="fa fa-check-square-o"></i> Cerrar caso</h5>' +
                    "</div>" +
                    '<form id="ddFormCerrarDecision" class="dd-decision-form">' +
                    '<input type="hidden" name="id_decision" value="' +
                    decision.id +
                    '">' +
                    '<div class="form-group">' +
                    '<label>Resolución final</label>' +
                    '<select class="form-control input-sm" name="resolucion_codigo" required>' +
                    renderResolucionOptions(catalogo.resoluciones) +
                    "</select>" +
                    "</div>" +
                    '<div class="form-group">' +
                    '<textarea class="form-control input-sm" name="resolucion_comentario" rows="2" placeholder="Comentario (opcional)"></textarea>' +
                    "</div>" +
                    '<button type="submit" class="btn btn-default btn-sm btn-block">Cerrar decisión</button>' +
                    "</form></div>";
            }
        } else if (permisos.registrar && String(pedido.estado || "").toUpperCase() === "GENERADO") {
            html += renderFormRegistrar(pedido, catalogo);
        } else if (!decision) {
            html +=
                '<div class="dd-dc-card dd-dc-card--muted">' +
                '<p class="text-muted"><i class="fa fa-info-circle"></i> No hay motivo registrado para este pedido.</p>' +
                "</div>";
        }

        html += renderBitacora(data.eventos);
        html += "</div>";

        return html;
    }

    function cargarDecisionCredito() {
        $("#ddDecisionCreditoBody").html(
            '<div class="dd-mini-ic-loading text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando decisión…</p></div>'
        );

        return $.ajax({
            url: "ajax/dashboard-decisiones/decisiones-credito.ajax.php",
            method: "POST",
            dataType: "json",
            data: {
                accion: "estado",
                codigo_pedido: ddDecisionContext.pedido,
                codigo_cliente: ddDecisionContext.cliente,
            },
        });
    }

    function mostrarDecisionCredito(resp) {
        if (!resp || !resp.ok) {
            $("#ddDecisionCreditoBody").html(
                '<div class="alert alert-warning">' +
                    escapeHtml((resp && resp.msg) || "No se pudo cargar la decisión.") +
                    "</div>"
            );
            $("#ddDecisionCreditoLinkIc").attr("href", "#").addClass("disabled");
            return;
        }

        $("#ddDecisionCreditoBody").html(renderDecisionCredito(resp));
        $("#ddDecisionCreditoLinkIc")
            .attr("href", resp.url_completo || "#")
            .removeClass("disabled");

        var motivos =
            (resp.catalogo && resp.catalogo.motivos) ||
            resp.motivos ||
            [];
        ddDecisionContext.motivos = motivos;
        poblarMotivoSelectpicker(motivos);
    }

    function postDecisionCredito(accion, data) {
        return $.ajax({
            url: "ajax/dashboard-decisiones/decisiones-credito.ajax.php",
            method: "POST",
            dataType: "json",
            data: $.extend({ accion: accion }, data),
        });
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
                            escapeHtml((resp && resp.msg) || "No se pudo cargar el análisis.") +
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

    $(document).on("click", ".btnDdDecisionCredito", function () {
        var $btn = $(this);

        ddDecisionContext.cliente = $btn.data("cliente");
        ddDecisionContext.pedido = $btn.data("pedido");
        ddDecisionContext.nombre = $btn.data("nombre");

        $("#ddDecisionCreditoTitulo").html('<i class="fa fa-gavel"></i> Decisión de crédito');
        $("#ddDecisionCreditoSubtitulo").text(
            (ddDecisionContext.nombre ? ddDecisionContext.nombre + " · " : "") +
                "Pedido " +
                ddDecisionContext.pedido
        );
        $("#ddDecisionCreditoLinkIc").attr("href", "#").addClass("disabled");
        $("#modalDdDecisionCredito").modal("show");

        cargarDecisionCredito().done(mostrarDecisionCredito).fail(function () {
            $("#ddDecisionCreditoBody").html(
                '<div class="alert alert-danger">Error al cargar la decisión de crédito.</div>'
            );
        });
    });

    $("#modalDdDecisionCredito").on("shown.bs.modal", function () {
        if (ddDecisionContext.motivos && ddDecisionContext.motivos.length) {
            poblarMotivoSelectpicker(ddDecisionContext.motivos);
        }
    });

    $(document).on("submit", "#ddFormRegistrarDecision", function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        $btn.prop("disabled", true);

        postDecisionCredito("registrar", $form.serialize())
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    swal("Atención", (resp && resp.msg) || "No se pudo registrar.", "warning");
                    return;
                }
                mostrarDecisionCredito(resp);
                swal("Registrado", "Motivo de no aprobación guardado.", "success");
            })
            .fail(function () {
                swal("Error", "No se pudo registrar la decisión.", "error");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    });

    $(document).on("submit", "#ddFormNuevaSolicitud", function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        $btn.prop("disabled", true);

        postDecisionCredito("solicitar", $form.serialize())
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    swal("Atención", (resp && resp.msg) || "No se pudo crear la solicitud.", "warning");
                    return;
                }
                mostrarDecisionCredito(resp);
                swal("Enviado", "Solicitud registrada.", "success");
            })
            .fail(function () {
                swal("Error", "No se pudo enviar la solicitud.", "error");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    });

    $(document).on("submit", "#ddFormCerrarDecision", function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        $btn.prop("disabled", true);

        postDecisionCredito("cerrar_decision", $form.serialize())
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    swal("Atención", (resp && resp.msg) || "No se pudo cerrar.", "warning");
                    return;
                }
                mostrarDecisionCredito(resp);
                swal("Cerrado", "Decisión cerrada.", "success");
            })
            .fail(function () {
                swal("Error", "No se pudo cerrar la decisión.", "error");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    });

    $(document).on("submit", ".dd-form-resolver-solicitud", function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $(document.activeElement);
        var estado = $btn.data("estado");

        if (!estado) {
            return;
        }

        var $buttons = $form.find("button[type='submit']");
        $buttons.prop("disabled", true);

        postDecisionCredito("resolver_solicitud", {
            id_solicitud: $form.data("id"),
            estado: estado,
            resolucion_codigo: $form.find('[name="resolucion_codigo"]').val(),
            comentario_resolucion: $form.find('[name="comentario_resolucion"]').val(),
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    swal("Atención", (resp && resp.msg) || "No se pudo resolver.", "warning");
                    return;
                }
                mostrarDecisionCredito(resp);
                swal("Resuelto", "Solicitud actualizada.", "success");
            })
            .fail(function () {
                swal("Error", "No se pudo resolver la solicitud.", "error");
            })
            .always(function () {
                $buttons.prop("disabled", false);
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
