(function () {
    var ddDecisionContext = {
        cliente: "",
        pedido: "",
        nombre: "",
        motivos: [],
        motivosAprobacion: [],
    };
    var ddIncluirGeneradosAvance = false;
    var ddMotivosAprobacionCache = null;

    function fmtSolesEntero(value) {
        return (
            "S/ " +
            Number(value || 0).toLocaleString("es-PE", {
                maximumFractionDigits: 0,
            })
        );
    }

    function ddAvancePctClase(pct) {
        pct = Number(pct) || 0;
        if (pct >= 100) {
            return "ok";
        }
        if (pct >= 80) {
            return "warn";
        }
        return "bad";
    }

    function aplicarAvanceIncluirGenerados(incluir) {
        ddIncluirGeneradosAvance = !!incluir;
        var $check = $("#ddAvanceIncluirGenerados");
        if ($check.length) {
            $check.prop("checked", ddIncluirGeneradosAvance);
        }

        $(".dd-avance-leyenda-generado").toggleClass("is-off", !ddIncluirGeneradosAvance);

        var $chip = $("#ddAvanceProyChip");
        if ($chip.length) {
            var proy = ddIncluirGeneradosAvance
                ? Number($chip.data("proy-gen") || 0)
                : Number($chip.data("proy") || 0);
            var meta = Number($chip.data("meta") || 0);
            var pct = ddIncluirGeneradosAvance
                ? Number($chip.data("pct-gen") || 0)
                : Number($chip.data("pct") || 0);
            $chip.html(
                "Proy. " +
                    fmtSolesEntero(proy) +
                    " / " +
                    fmtSolesEntero(meta) +
                    " (" +
                    pct.toLocaleString("es-PE", {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                    }) +
                    "%)"
            );
        }

        $(".dd-avance-row").each(function () {
            var $row = $(this);
            var real = Number($row.data("real") || 0);
            var gen = Number($row.data("generado") || 0);
            var apr = Number($row.data("aprobado") || 0);
            var apt = Number($row.data("apt") || 0);
            var conf = Number($row.data("confirmado") || 0);
            var meta = Number($row.data("meta") || 0);

            var segmentos = [
                { clase: "real", monto: real, titulo: "Venta facturada" },
            ];
            if (ddIncluirGeneradosAvance) {
                segmentos.push({
                    clase: "generado",
                    monto: gen,
                    titulo: "Generados",
                });
            }
            segmentos.push(
                { clase: "aprobado", monto: apr, titulo: "Aprobados" },
                { clase: "apt", monto: apt, titulo: "APT" },
                { clase: "confirmado", monto: conf, titulo: "Confirmados" }
            );

            var pipeline = apr + apt + conf + (ddIncluirGeneradosAvance ? gen : 0);
            var proyectado = real + pipeline;
            var pctProy = meta > 0 ? Math.round((proyectado / meta) * 1000) / 10 : 0;
            var faltante = Math.max(0, meta - proyectado);
            var clase = ddAvancePctClase(pctProy);

            var totalPctBar = 0;
            segmentos.forEach(function (seg) {
                if (seg.monto > 0 && meta > 0) {
                    totalPctBar += (seg.monto / meta) * 100;
                }
            });
            var escala = totalPctBar > 100 ? 100 / totalPctBar : 1;

            var $bar = $row.find(".dd-avance-bar-el");
            $bar.toggleClass("dd-avance-bar--overflow", totalPctBar > 100);
            $bar.empty();
            if (meta > 0) {
                segmentos.forEach(function (seg) {
                    if (seg.monto <= 0) {
                        return;
                    }
                    var ancho = Math.round((seg.monto / meta) * 100 * escala * 100) / 100;
                    $bar.append(
                        '<div class="dd-avance-seg dd-avance-seg--' +
                            seg.clase +
                            '" style="width:' +
                            ancho +
                            '%" title="' +
                            escapeHtml(seg.titulo) +
                            ": " +
                            fmtSolesEntero(seg.monto) +
                            '"></div>'
                    );
                });
            }

            var $pct = $row.find(".dd-avance-pct-el");
            $pct
                .removeClass("dd-avance-pct--ok dd-avance-pct--warn dd-avance-pct--bad")
                .addClass("dd-avance-pct--" + clase)
                .text(
                    pctProy.toLocaleString("es-PE", {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                    }) + "%"
                );

            $row.find(".dd-avance-pipeline").text(fmtSolesEntero(pipeline));

            var $falt = $row.find(".dd-avance-faltante-cell");
            if (faltante > 0) {
                $falt.html(
                    '<span class="dd-avance-faltante">' +
                        fmtSolesEntero(faltante) +
                        "</span>"
                );
            } else {
                $falt.html(
                    '<span class="dd-avance-faltante dd-avance-faltante--ok"><i class="fa fa-check"></i></span>'
                );
            }
        });
    }

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

    function initMotivoSelectpicker($motivo, motivos, placeholder) {
        if (!$motivo || !$motivo.length) {
            return;
        }

        placeholder = placeholder || "Seleccione motivo…";

        if ($motivo.parent().hasClass("bootstrap-select") || $motivo.data("selectpicker")) {
            $motivo.selectpicker("destroy");
        }

        $motivo.empty();
        $motivo.append('<option value="">' + placeholder + "</option>");

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
            noneSelectedText: placeholder,
            liveSearchPlaceholder: "Buscar motivo…",
            width: "100%",
            container: "body",
            dropupAuto: false,
        });
    }

    function poblarMotivoSelectpicker(motivos) {
        initMotivoSelectpicker(
            $("#modalDdDecisionCredito").find(".dd-motivo-select"),
            motivos,
            "Seleccione motivo…"
        );
    }

    function poblarMotivoAprobacionSelectpicker($scope, motivos) {
        var $motivos = ($scope && $scope.length ? $scope : $(document)).find(
            ".dd-motivo-aprobacion-select"
        );
        if (!$motivos.length) {
            return;
        }

        $motivos.each(function () {
            initMotivoSelectpicker($(this), motivos, "Sin motivo…");
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

    function renderMotivoAprobacionOptions(motivos, selected) {
        var html = '<option value="">Sin motivo…</option>';

        (motivos || []).forEach(function (motivo) {
            if (!motivo || !motivo.codigo) {
                return;
            }
            var sel = selected === motivo.codigo ? " selected" : "";
            html +=
                '<option value="' +
                escapeHtml(motivo.codigo) +
                '"' +
                sel +
                ">" +
                escapeHtml(motivo.etiqueta || motivo.codigo) +
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
                        '<label>Motivo <small class="text-muted">(opcional)</small></label>' +
                        '<select class="form-control selectpicker dd-motivo-aprobacion-select" name="motivo_observacion_codigo" title="Sin motivo…" data-live-search="true">' +
                        renderMotivoAprobacionOptions(catalogo.motivos_aprobacion) +
                        "</select>" +
                        "</div>" +
                        '<div class="form-group">' +
                        '<textarea class="form-control input-sm" name="comentario_resolucion" rows="2" placeholder="Observación para ventas (opcional)"></textarea>' +
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
                        (sol.motivo_observacion_etiqueta
                            ? " — " + escapeHtml(sol.motivo_observacion_etiqueta)
                            : "") +
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

        var motivosAprob =
            (resp.catalogo && resp.catalogo.motivos_aprobacion) || [];
        ddDecisionContext.motivosAprobacion = motivosAprob;
        if (motivosAprob.length) {
            ddMotivosAprobacionCache = motivosAprob;
        }
        poblarMotivoAprobacionSelectpicker(
            $("#modalDdDecisionCredito"),
            motivosAprob
        );
    }

    function postDecisionCredito(accion, data) {
        // serialize() devuelve string; $.extend(string) lo parte en caracteres
        // y nunca envía codigo_cliente / motivo. Objetos sí se mergean bien.
        var payload;
        if (typeof data === "string") {
            payload = "accion=" + encodeURIComponent(accion);
            if (data) {
                payload += "&" + data;
            }
        } else {
            payload = $.extend({ accion: accion }, data || {});
        }

        return $.ajax({
            url: "ajax/dashboard-decisiones/decisiones-credito.ajax.php",
            method: "POST",
            dataType: "json",
            data: payload,
        });
    }

    var ddRefreshXhr = null;
    var ddRefreshSeq = 0;
    var ddIgnorarCambioFiltro = false;

    function ddUrlDashboard(vendedor) {
        var url = "index.php?ruta=dashboard-decisiones";
        if (vendedor) {
            url += "&vendedor=" + encodeURIComponent(vendedor);
        }
        return url;
    }

    function ddSetFiltroControlesDisabled(disabled) {
        var $select = $("#ddFiltroVendedor");
        var $btns = $("#btnDdActualizar, #btnDdLimpiarFiltro, .btnDdLimpiarFiltro");

        $select.prop("disabled", disabled);
        if ($select.data("selectpicker")) {
            $select.selectpicker("refresh");
        }
        $btns.prop("disabled", disabled);
    }

    function ddAplicarValorSelectVendedor(vendedor) {
        ddIgnorarCambioFiltro = true;
        var $select = $("#ddFiltroVendedor");
        if ($select.data("selectpicker")) {
            $select.selectpicker("val", vendedor);
        } else {
            $select.val(vendedor);
        }
        ddIgnorarCambioFiltro = false;
    }

    function refrescarDashboard(vendedor, opciones) {
        opciones = opciones || {};
        vendedor = vendedor == null ? "" : String(vendedor);

        var seq = ++ddRefreshSeq;

        if (ddRefreshXhr && ddRefreshXhr.readyState !== 4) {
            ddRefreshXhr.abort();
        }

        $("#ddContenidoDashboard").addClass("dd-dashboard--loading");
        ddSetFiltroControlesDisabled(true);

        ddRefreshXhr = $.ajax({
            url: "ajax/dashboard-decisiones/refrescar-dashboard.ajax.php",
            method: "POST",
            dataType: "json",
            data: { vendedor: vendedor },
        })
            .done(function (resp) {
                if (seq !== ddRefreshSeq) {
                    return;
                }

                if (!resp || !resp.ok) {
                    swal(
                        "Atención",
                        (resp && resp.msg) || "No se pudo actualizar el dashboard.",
                        "warning"
                    );
                    return;
                }

                $("#ddContenidoDashboard").html(resp.html);
                aplicarAvanceIncluirGenerados(ddIncluirGeneradosAvance);

                if (opciones.actualizarUrl) {
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(
                            { vendedor: resp.vendedor || "" },
                            "",
                            ddUrlDashboard(resp.vendedor || "")
                        );
                    }
                }

                if (opciones.mensajeExito) {
                    swal("Listo", opciones.mensajeExito, "success");
                }
            })
            .fail(function (xhr, status) {
                if (status === "abort" || seq !== ddRefreshSeq) {
                    return;
                }
                swal("Error", "No se pudo actualizar el dashboard.", "error");
            })
            .always(function () {
                if (seq !== ddRefreshSeq) {
                    return;
                }
                $("#ddContenidoDashboard").removeClass("dd-dashboard--loading");
                ddSetFiltroControlesDisabled(false);
                ddRefreshXhr = null;
            });

        return ddRefreshXhr;
    }

    function ddLimpiarFiltroVendedor() {
        ddAplicarValorSelectVendedor("");
        refrescarDashboard("", { actualizarUrl: true });
    }

    $(document).ready(function () {
        if ($("#ddFiltroVendedor").length) {
            $("#ddFiltroVendedor").selectpicker("refresh");
        }

        $("#ddFiltroVendedor").on("change", function () {
            if (ddIgnorarCambioFiltro) {
                return;
            }
            refrescarDashboard($(this).val(), { actualizarUrl: true });
        });

        $(document).on("change", "#ddAvanceIncluirGenerados", function () {
            aplicarAvanceIncluirGenerados($(this).is(":checked"));
        });

        aplicarAvanceIncluirGenerados(false);
        $("#btnDdActualizar").on("click", function () {
            refrescarDashboard($("#ddFiltroVendedor").val(), { actualizarUrl: false });
        });

        $(document).on("click", ".btnDdLimpiarFiltro", function (e) {
            e.preventDefault();
            ddLimpiarFiltroVendedor();
        });

        $(window).on("popstate", function (e) {
            if (!$("#ddContenidoDashboard").length) {
                return;
            }

            var state = e.originalEvent && e.originalEvent.state;
            var vendedor = "";

            if (state && state.vendedor != null) {
                vendedor = String(state.vendedor);
            } else {
                try {
                    var params = new URLSearchParams(window.location.search);
                    vendedor = params.get("vendedor") || "";
                } catch (err) {
                    vendedor = "";
                }
            }

            ddAplicarValorSelectVendedor(vendedor);
            refrescarDashboard(vendedor, { actualizarUrl: false });
        });
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
            motivo_observacion_codigo: $form
                .find('[name="motivo_observacion_codigo"]')
                .val(),
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

    $(document).on("click", ".btnDdAprobarPedido", function () {
        var $btn = $(this);
        var pedido = $btn.data("pedido");
        var cliente = $btn.data("cliente") || "";
        var codCli = $btn.data("cod-cli") || "";
        var tieneCategoria = String($btn.data("tiene-categoria") || "0") === "1";

        if (!pedido) {
            return;
        }

        if (!tieneCategoria) {
            abrirModalCategoriaParaAprobar({
                $btn: $btn,
                pedido: pedido,
                cliente: cliente,
                codCli: codCli,
            });
            return;
        }

        abrirModalAprobarPedido({
            $btn: $btn,
            pedido: pedido,
            cliente: cliente,
            idCategoria: 0,
        });
    });

    var ddAprobarCatCtx = null;
    var ddAprobarPedidoCtx = null;
    var ddCategoriasCache = null;

    function colorCategoriaFallback(codigo, color) {
        var mapa = {
            DIST: "#dd4b39",
            MAYO: "#00a65a",
            MINO: "#f39c12",
            CATA: "#00c0ef",
            UFIN: "#605ca8",
        };
        var hex = String(color || "").trim();
        if (/^#[0-9A-Fa-f]{3,8}$/.test(hex)) {
            return hex;
        }
        return mapa[String(codigo || "").toUpperCase()] || "#777777";
    }

    function cargarCategoriasActivas() {
        if (ddCategoriasCache) {
            return $.Deferred().resolve(ddCategoriasCache).promise();
        }

        return $.ajax({
            url: "ajax/categorias-clientes.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "listarActivas" },
        }).then(function (resp) {
            ddCategoriasCache = (resp && resp.ok && resp.data) ? resp.data : [];
            return ddCategoriasCache;
        });
    }

    function llenarSelectCategorias($select, categorias) {
        var html = '<option value="">Seleccione una categoría…</option>';
        (categorias || []).forEach(function (cat) {
            var id = cat.id;
            var codigo = cat.codigo || "";
            var nombre = cat.nombre || codigo;
            var color = colorCategoriaFallback(codigo, cat.color);
            html +=
                '<option value="' +
                escapeHtml(id) +
                '" data-codigo="' +
                escapeHtml(codigo) +
                '" data-nombre="' +
                escapeHtml(nombre) +
                '" data-color="' +
                escapeHtml(color) +
                '">' +
                escapeHtml(codigo + " — " + nombre) +
                "</option>";
        });
        $select.html(html);
    }

    function actualizarPreviewCategoria() {
        var $opt = $("#ddAprobarCatSelect option:selected");
        var $preview = $("#ddAprobarCatPreview");
        var id = $("#ddAprobarCatSelect").val();

        if (!id) {
            $preview.hide().empty();
            return;
        }

        var codigo = $opt.data("codigo") || "";
        var nombre = $opt.data("nombre") || "";
        var color = $opt.data("color") || "#777777";

        $preview
            .html(
                '<span class="dd-cat-sigla" style="background-color:' +
                    escapeHtml(color) +
                    ';">' +
                    escapeHtml(String(codigo).toUpperCase()) +
                    "</span> " +
                    '<span class="dd-aprobar-cat-preview-nombre">' +
                    escapeHtml(nombre) +
                    "</span>"
            )
            .show();
    }

    function abrirModalCategoriaParaAprobar(ctx) {
        ddAprobarCatCtx = ctx;

        $("#ddAprobarCatCliente").text(
            "Pedido " +
                ctx.pedido +
                (ctx.cliente ? " · " + ctx.cliente : "") +
                (ctx.codCli ? " (" + ctx.codCli + ")" : "")
        );
        $("#ddAprobarCatHint").text(
            "Este cliente aún no tiene categoría comercial. Elígela para asignarla y aprobar el pedido."
        );
        $("#ddAprobarCatSelect").html('<option value="">Cargando categorías…</option>');
        $("#ddAprobarCatPreview").hide().empty();
        $("#ddAprobarCatConfirm").prop("disabled", true);

        $("#modalDdAprobarCategoria").modal("show");

        cargarCategoriasActivas()
            .done(function (categorias) {
                llenarSelectCategorias($("#ddAprobarCatSelect"), categorias);
                $("#ddAprobarCatConfirm").prop("disabled", false);

                if (!ctx.codCli) {
                    return;
                }

                $.ajax({
                    url: "ajax/categorias-clientes.ajax.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        accion: "efectivaCliente",
                        codigoCliente: ctx.codCli,
                    },
                }).done(function (efectiva) {
                    if (efectiva && efectiva.ok && efectiva.codigo_grupo) {
                        $("#ddAprobarCatHint").html(
                            "El cliente pertenece al grupo <strong>" +
                                escapeHtml(
                                    efectiva.nombre_grupo || efectiva.codigo_grupo
                                ) +
                                "</strong>. " +
                                "La categoría se asignará al <strong>grupo</strong> y servirá para todos sus clientes."
                        );
                    }
                });
            })
            .fail(function () {
                $("#ddAprobarCatSelect").html(
                    '<option value="">No se pudieron cargar las categorías</option>'
                );
                swal(
                    "Error",
                    "No se pudieron cargar las categorías comerciales.",
                    "error"
                );
            });
    }

    $(document).on("change", "#ddAprobarCatSelect", actualizarPreviewCategoria);

    $(document).on("click", "#ddAprobarCatConfirm", function () {
        if (!ddAprobarCatCtx || !ddAprobarCatCtx.pedido) {
            return;
        }

        var idCategoria = parseInt($("#ddAprobarCatSelect").val(), 10) || 0;
        if (idCategoria <= 0) {
            swal("Atención", "Selecciona una categoría comercial.", "warning");
            return;
        }

        var ctx = ddAprobarCatCtx;
        $("#modalDdAprobarCategoria").modal("hide");

        abrirModalAprobarPedido({
            $btn: ctx.$btn,
            pedido: ctx.pedido,
            cliente: ctx.cliente,
            idCategoria: idCategoria,
        });
    });

    function cargarMotivosAprobacion() {
        if (ddMotivosAprobacionCache && ddMotivosAprobacionCache.length) {
            return $.Deferred().resolve(ddMotivosAprobacionCache).promise();
        }

        if (
            ddDecisionContext.motivosAprobacion &&
            ddDecisionContext.motivosAprobacion.length
        ) {
            ddMotivosAprobacionCache = ddDecisionContext.motivosAprobacion;
            return $.Deferred().resolve(ddMotivosAprobacionCache).promise();
        }

        return postDecisionCredito("catalogo", {}).then(function (resp) {
            var lista =
                (resp && resp.motivos_aprobacion) ||
                (resp && resp.catalogo && resp.catalogo.motivos_aprobacion) ||
                [];
            ddMotivosAprobacionCache = lista;
            ddDecisionContext.motivosAprobacion = lista;
            return lista;
        });
    }

    function abrirModalAprobarPedido(ctx) {
        ddAprobarPedidoCtx = ctx || null;

        if (!ddAprobarPedidoCtx || !ddAprobarPedidoCtx.pedido) {
            return;
        }

        var detalle =
            "Pedido " +
            ddAprobarPedidoCtx.pedido +
            (ddAprobarPedidoCtx.cliente
                ? " · " + ddAprobarPedidoCtx.cliente
                : "");
        $("#ddAprobarPedidoInfo").text(detalle);

        var conCategoria = (ddAprobarPedidoCtx.idCategoria || 0) > 0;
        $("#ddAprobarPedidoHint").text(
            conCategoria
                ? "Se asignará la categoría seleccionada. Motivo y observación son opcionales."
                : "El pedido pasará a APROBADO. Motivo y observación son opcionales."
        );
        $("#ddAprobarPedidoConfirm").html(
            conCategoria
                ? '<i class="fa fa-check"></i> Asignar y aprobar'
                : '<i class="fa fa-check"></i> Aprobar'
        );

        $("#ddAprobarPedidoObs").val("");
        initMotivoSelectpicker(
            $("#ddAprobarPedidoMotivo"),
            [],
            "Cargando motivos…"
        );
        $("#modalDdAprobarPedido").modal("show");

        cargarMotivosAprobacion()
            .done(function (motivos) {
                initMotivoSelectpicker(
                    $("#ddAprobarPedidoMotivo"),
                    motivos,
                    "Sin motivo…"
                );
            })
            .fail(function () {
                initMotivoSelectpicker(
                    $("#ddAprobarPedidoMotivo"),
                    [],
                    "Sin catálogo de motivos"
                );
            });
    }

    $(document).on("click", "#ddAprobarPedidoConfirm", function () {
        if (!ddAprobarPedidoCtx || !ddAprobarPedidoCtx.pedido) {
            return;
        }

        var ctx = ddAprobarPedidoCtx;
        var motivo = $("#ddAprobarPedidoMotivo").val() || "";
        var comentario = $("#ddAprobarPedidoObs").val() || "";

        $("#modalDdAprobarPedido").modal("hide");
        ejecutarAprobarPedido(
            ctx.$btn,
            ctx.pedido,
            ctx.idCategoria || 0,
            motivo,
            comentario
        );
    });

    function ejecutarAprobarPedido($btn, pedido, idCategoria, motivoCodigo, comentario) {
        if ($btn && $btn.length) {
            $btn.prop("disabled", true);
        }

        var payload = { codigo_pedido: pedido };
        if (idCategoria > 0) {
            payload.id_categoria = idCategoria;
        }
        if (motivoCodigo) {
            payload.motivo_codigo = motivoCodigo;
        }
        if (comentario) {
            payload.comentario = comentario;
        }

        $.ajax({
            url: "ajax/dashboard-decisiones/aprobar-pedido.ajax.php",
            method: "POST",
            dataType: "json",
            data: payload,
        })
            .done(function (resp) {
                if (resp && resp.requiere_categoria) {
                    if ($btn && $btn.length) {
                        $btn.prop("disabled", false);
                    }
                    abrirModalCategoriaParaAprobar({
                        $btn: $btn,
                        pedido: pedido,
                        cliente: resp.nombre_cliente || "",
                        codCli: resp.codigo_cliente || "",
                    });
                    return;
                }

                if (!resp || !resp.ok) {
                    swal(
                        "Atención",
                        (resp && resp.msg) || "No se pudo aprobar el pedido.",
                        "warning"
                    );
                    if ($btn && $btn.length) {
                        $btn.prop("disabled", false);
                    }
                    return;
                }

                ddAprobarCatCtx = null;
                ddAprobarPedidoCtx = null;

                var mensaje = resp.categoria_asignada
                    ? "Categoría asignada y pedido aprobado. El dashboard se actualizó."
                    : "El pedido fue aprobado y el dashboard fue actualizado.";

                refrescarDashboard($("#ddFiltroVendedor").val(), {
                    actualizarUrl: false,
                    mensajeExito: mensaje,
                }).fail(function (xhr, status) {
                    if (status !== "abort" && $btn && $btn.length) {
                        $btn.prop("disabled", false);
                    }
                });
            })
            .fail(function () {
                swal("Error", "No se pudo aprobar el pedido.", "error");
                if ($btn && $btn.length) {
                    $btn.prop("disabled", false);
                }
            });
    }

    $(document).on("click", ".btnDdAnularPedido", function () {
        var $btn = $(this);
        var pedido = $btn.data("pedido");
        var cliente = $btn.data("cliente") || "";

        if (!pedido) {
            return;
        }

        var detalle = "Pedido " + pedido + (cliente ? " · " + cliente : "");

        swal({
            title: "¿Anular pedido?",
            text:
                detalle +
                "\n\nEsta acción es definitiva y no se puede revertir.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd4b39",
            cancelButtonColor: "#95a5a6",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Sí, anular definitivamente",
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            $btn.prop("disabled", true);

            $.ajax({
                url: "ajax/dashboard-decisiones/anular-pedido.ajax.php",
                method: "POST",
                dataType: "json",
                data: { codigo_pedido: pedido },
            })
                .done(function (resp) {
                    if (!resp || !resp.ok) {
                        swal(
                            "Atención",
                            (resp && resp.msg) || "No se pudo anular el pedido.",
                            "warning"
                        );
                        $btn.prop("disabled", false);
                        return;
                    }

                    refrescarDashboard($("#ddFiltroVendedor").val(), {
                        actualizarUrl: false,
                        mensajeExito:
                            "El pedido fue anulado y el dashboard fue actualizado.",
                    }).fail(function (xhr, status) {
                        if (status !== "abort") {
                            $btn.prop("disabled", false);
                        }
                    });
                })
                .fail(function () {
                    swal("Error", "No se pudo anular el pedido.", "error");
                    $btn.prop("disabled", false);
                });
        });
    });
})();
