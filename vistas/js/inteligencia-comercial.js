$(document).ready(function () {

    $("#clienteInteligencia").on("changed.bs.select", function () {
        var cliente = $(this).val();

        if (cliente === "" || cliente === null) {
            window.location = "index.php?ruta=inteligencia-comercial";
            return;
        }

        window.location = "index.php?ruta=inteligencia-comercial&cliente=" + encodeURIComponent(cliente);
    });

    icInitResumenIa();

    if (typeof Chart === "undefined") {
        return;
    }

    var factoresPorMotor = {};

    $("[id^='icMotor'][id$='Data']").each(function () {
        var motorData = JSON.parse(this.textContent);
        var motorNum = motorData.motor;
        var factoresPorClave = {};

        $.each(motorData.factores, function (clave, f) {
            factoresPorClave[f.clave || clave] = f;
        });

        factoresPorMotor[motorNum] = factoresPorClave;

        var $legend = $(".ic-chart-legend[data-motor='" + motorNum + "']");
        var chartScoreId = $legend.closest(".box-body").find(".ic-gauge-wrap canvas").attr("id");
        var chartAportId = $legend.closest(".box-body").find(".ic-aportacion-chart canvas").attr("id");

        icInitChartRiesgo(motorData.score, chartScoreId);
        icInitChartAportacion(factoresPorClave, chartAportId, $legend);
    });

    icInitModales(factoresPorMotor);
    icInitModalLineaCredito();
    icInitImpresion();

});

function icInitResumenIa() {
    var $box = $("#icResumenIaBox");
    var $btn = $("#btnIcGenerarResumenIa");

    if (!$box.length || !$btn.length) {
        return;
    }

    var cliente = $box.data("cliente");
    var $estado = $("#icResumenIaEstado");
    var $contenido = $("#icResumenIaContenido");
    var $decision = $("#icResumenIaDecision");
    var $significa = $("#icResumenIaSignifica");
    var $lineaPorQue = $("#icResumenIaLineaPorQue");
    var $lista = $("#icResumenIaRecomendaciones");
    var $meta = $("#icResumenIaMeta");

    function icResumenIaClaseDecision(texto) {
        var t = (texto || "").toLowerCase();
        if (/no otorgar|rechaz|suspender|no fiar|riesgo alto|escalar|aprobación manual|aprobacion manual/.test(t)) {
            return "ic-decision-riesgo";
        }
        if (/visita|esperar|reactivar|seguimiento|revisar/.test(t)) {
            return "ic-decision-alerta";
        }
        return "";
    }

    $btn.on("click", function () {
        if (!cliente) {
            return;
        }

        $btn.prop("disabled", true);
        $estado.removeClass("ic-error").html('<i class="fa fa-spinner fa-spin"></i> Generando resumen…');
        $contenido.hide();

        $.ajax({
            url: "ajax/inteligencia-comercial/resumen-ia.ajax.php",
            type: "POST",
            dataType: "json",
            data: { cliente: cliente }
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                $estado.addClass("ic-error").text((resp && resp.msg) ? resp.msg : "No se pudo generar el resumen.");
                return;
            }

            $estado.hide();

            var decision = resp.decision || "";
            var significa = resp.que_significa || resp.alertas || [];
            var linea = resp.linea_credito || resp.linea_credito_por_que || "";
            var mejorar = resp.como_mejorar || resp.recomendaciones || [];

            $decision.text(decision).removeClass("ic-decision-alerta ic-decision-riesgo");
            if (decision) {
                $decision.addClass(icResumenIaClaseDecision(decision));
            }
            $("#icResumenIaBloqueDecision").toggle(!!decision);

            $significa.empty();
            if (significa.length) {
                $.each(significa, function (_, item) {
                    $("<li></li>").text(item).appendTo($significa);
                });
            }
            $("#icResumenIaBloqueSignifica").toggle(significa.length > 0);

            $lineaPorQue.text(linea);
            $("#icResumenIaBloqueLinea").toggle(!!linea);

            $lista.empty();
            if (mejorar.length) {
                $.each(mejorar, function (_, item) {
                    $("<li></li>").text(item).appendTo($lista);
                });
            }
            $("#icResumenIaBloqueMejorar").toggle(mejorar.length > 0);

            var meta = [];
            if (resp.modelo) {
                meta.push("Modelo: " + resp.modelo + (resp.modelo_respaldo ? " (respaldo)" : ""));
            }
            if (resp.generado_en) {
                meta.push("Generado: " + resp.generado_en);
            }
            $meta.text(meta.join(" · "));
            $contenido.show();
        }).fail(function () {
            $estado.addClass("ic-error").text("Error de comunicación con el servidor.");
        }).always(function () {
            $btn.prop("disabled", false);
        });
    });
}

function icInitImpresion() {
    var $btnV = $("#btnImprimirVertical");
    var $btnH = $("#btnImprimirHorizontal");
    if (!$btnV.length && !$btnH.length) return;

    var ahora = new Date();
    var fechaTxt = "Impreso: " + ahora.toLocaleDateString("es-PE") + " " + ahora.toLocaleTimeString("es-PE", { hour: "2-digit", minute: "2-digit" });
    $(".ic-print-fecha").text(fechaTxt);

    function icImprimir(orientacion) {
        var $body = $("body");
        var esLandscape = orientacion === "landscape";
        var style = document.getElementById("icPrintOrientacion");
        if (!style) {
            style = document.createElement("style");
            style.id = "icPrintOrientacion";
            document.head.appendChild(style);
        }
        style.textContent = "@media print { @page { size: A4 " + (esLandscape ? "landscape" : "portrait") + "; margin: " + (esLandscape ? "6mm 8mm" : "8mm 10mm") + "; } }";

        $body.removeClass("ic-print-portrait ic-print-landscape");
        $body.addClass(esLandscape ? "ic-print-landscape" : "ic-print-portrait");

        var limpiar = function () {
            $body.removeClass("ic-print-portrait ic-print-landscape");
            if (style) {
                style.textContent = "";
            }
            window.removeEventListener("afterprint", limpiar);
        };
        window.addEventListener("afterprint", limpiar);

        window.print();
    }

    $btnV.on("click", function () {
        icImprimir("portrait");
    });
    $btnH.on("click", function () {
        icImprimir("landscape");
    });
}

var IC_COLORES_PASTEL = [
    "#F4A6A6", "#FFD4A3", "#A8CCE8", "#C5B4E3",
    "#B5E0C8", "#F7C5D8", "#D5E8A4", "#E8D4B8"
];

function icColorPastel(index) {
    return IC_COLORES_PASTEL[index % IC_COLORES_PASTEL.length];
}

function icColorScore(score) {
    if (score >= 80) return "#00a65a";
    if (score >= 70) return "#3c8dbc";
    if (score >= 60) return "#f39c12";
    return "#dd4b39";
}

function icFmtScore(val) {
    return parseFloat(val).toFixed(1);
}

function icFmtAportacion(val) {
    return parseFloat(val).toFixed(2);
}

function icInitChartRiesgo(scoreFinal, canvasId) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var score = parseFloat(scoreFinal);
    var color = icColorScore(score);
    var restante = Math.max(0, 100 - score);

    new Chart(ctx.getContext("2d")).Doughnut(
        [
            { value: score, color: color, highlight: color, label: "Score " + icFmtScore(score) },
            { value: restante, color: "#ecf0f5", highlight: "#e0e5ea", label: "" }
        ],
        {
            responsive: false,
            segmentShowStroke: false,
            percentageInnerCutout: 78,
            animation: true,
            animationSteps: 60,
            showTooltips: false
        }
    );
}

function icInitChartAportacion(factoresPorClave, canvasId, $legend) {
    var segmentos = [];
    var claves = [];

    $legend.find(".ic-legend-item").each(function (i) {
        var clave = $(this).data("factor");
        var f = factoresPorClave[clave];
        if (!f) return;

        var color = $(this).data("color") || icColorPastel(i);

        claves.push(clave);
        segmentos.push({
            value: parseFloat(f.aportacion),
            color: color,
            highlight: color,
            label: f.nombre
        });
    });

    var ctxAport = document.getElementById(canvasId);
    if (!ctxAport || !segmentos.length) return;

    var chartAport = new Chart(ctxAport.getContext("2d")).Doughnut(segmentos, {
        responsive: false,
        segmentShowStroke: true,
        segmentStrokeColor: "#fff",
        segmentStrokeWidth: 2,
        percentageInnerCutout: 50,
        animation: true,
        tooltipTemplate: "<%if (label){%><%=label%>: +<%= value.toFixed(2) %> pts<%}%>"
    });

    $(ctxAport).css("cursor", "pointer").on("click", function (evt) {
        var active = chartAport.getSegmentsAtEvent(evt);
        if (!active || !active.length) return;

        var idx = typeof active[0]._index !== "undefined" ? active[0]._index : 0;
        var clave = claves[idx];

        if (clave && factoresPorClave[clave]) {
            icAbrirModalFactor(factoresPorClave[clave]);
        }
    });
}

function icAbrirModalFactor(f) {
    if (!f) return;

    var score = parseFloat(f.score);
    var color = icColorScore(score);
    var aportacion = icFmtAportacion(f.aportacion);

    $("#icModalIcon").attr("class", "fa " + f.icono);
    $("#icModalTitulo").text(f.nombre);
    $("#icModalScore").text(icFmtScore(score)).css("color", color);
    $("#icModalScoreIcon").css({ background: color + "22", color: color });
    $("#icModalScoreBar").css({ width: Math.min(100, score) + "%", background: color });
    $("#icModalPeso").text(f.peso + "%");
    $("#icModalAportacion").text("+" + aportacion);
    $("#icModalAportacionCalc").text(icFmtScore(score) + " × " + f.peso + "% = " + aportacion);
    $("#icModalDetalle").html("<i class=\"fa fa-info-circle\"></i> " + f.detalle);
    $("#icModalFormula").text(f.formula);
    $("#icModalRegla").text(f.regla);

    icRenderTablaLogica(f.tabla_logica);
    icRenderPeriodosBox(f.periodos_box);

    var htmlValores = "";
    if (f.valores && f.valores.length) {
        $.each(f.valores, function (i, v) {
            htmlValores += '<div class="col-sm-6 col-xs-6">';
            htmlValores += '<div class="ic-modal-dato">';
            htmlValores += '<span class="ic-modal-dato-val">' + v.valor + '</span>';
            htmlValores += '<span class="ic-modal-dato-lbl">' + v.etiqueta + '</span>';
            htmlValores += '</div></div>';
        });
    }
    $("#icModalValores").html(htmlValores);

    $("#modalDetalleFactor").modal("show");
}

function icRenderTablaLogica(tabla) {
    var $wrap = $("#icModalLogicaWrap");

    if (!tabla || !tabla.filas || !tabla.filas.length) {
        $wrap.hide();
        return;
    }

    $("#icModalLogicaTitulo").text(tabla.titulo || "Reglas de puntuación");

    if (tabla.intro) {
        $("#icModalLogicaIntro").text(tabla.intro).show();
    } else {
        $("#icModalLogicaIntro").hide();
    }

    var columnas = tabla.columnas || ["Situación", "Condición", "Score"];
    var headHtml = "";
    $.each(columnas, function (i, col) {
        headHtml += "<th>" + col + "</th>";
    });
    $("#icModalLogicaHead").html(headHtml);

    var bodyHtml = "";
    $.each(tabla.filas, function (i, fila) {
        var clases = [];
        if (fila.aplica) clases.push("ic-logica-aplica");
        if (fila.es_resultado) clases.push("ic-logica-resultado");

        var badge = fila.aplica
            ? '<span class="ic-modal-logica-badge">' + (fila.es_resultado ? "Su caso" : "Aplica") + "</span>"
            : "";

        bodyHtml += '<tr class="' + clases.join(" ") + '">';
        bodyHtml += "<td>" + fila.situacion + badge + "</td>";
        bodyHtml += "<td>" + fila.condicion + "</td>";
        bodyHtml += "<td>" + fila.score + "</td>";
        bodyHtml += "</tr>";
    });
    $("#icModalLogicaBody").html(bodyHtml);
    $wrap.show();
}

function icRenderPeriodosBox(periodos) {
    var $wrap = $("#icModalPeriodosWrap");

    if (!periodos || !periodos.items || !periodos.items.length) {
        $wrap.hide();
        return;
    }

    $("#icModalPeriodosTitulo").text(periodos.titulo || "Periodos de comparación");

    var html = "";
    $.each(periodos.items, function (i, item) {
        html += "<li>";
        html += '<span class="ic-periodo-etq">' + item.etiqueta + "</span>";
        html += '<span class="ic-periodo-rango">' + item.rango + "</span>";
        html += "</li>";
    });
    $("#icModalPeriodosList").html(html);
    $wrap.show();
}

function icInitModales(factoresPorMotor) {
    $(".ic-legend-item").on("click", function () {
        var motorNum = $(this).closest(".ic-chart-legend").data("motor");
        var clave = $(this).data("factor");
        var mapa = factoresPorMotor[motorNum];

        if (mapa && mapa[clave]) {
            icAbrirModalFactor(mapa[clave]);
        }
    });
}

function icInitModalLineaCredito() {
    var $json = $("#icMotor3LineaExplicacion");
    if (!$json.length) return;

    $("#btnDetalleLineaCredito").on("click", function () {
        try {
            var data = JSON.parse($json.text());
            icAbrirModalLinea(data);
        } catch (e) {
            console.error("icMotor3LineaExplicacion", e);
        }
    });
}

function icAbrirModalLinea(data) {
    if (!data) return;

    var accion = data.accion || {};
    var colorMap = {
        success: "alert-success",
        primary: "alert-info",
        warning: "alert-warning",
        danger: "alert-danger",
        info: "alert-info"
    };
    var alertClass = colorMap[accion.color] || "alert-info";

    $("#icModalLineaTitulo").text(data.titulo || "¿Por qué esta línea de crédito?");
    $("#icModalLineaAccion")
        .attr("class", "alert " + alertClass);
    $("#icModalLineaAccionEtq")
        .html("<i class=\"fa " + (accion.icono || "fa-info-circle") + "\"></i> " + (accion.etiqueta || "Recomendación"));
    $("#icModalLineaAccionTxt").text(accion.explicacion || data.resumen || "");

    $("#icModalLineaBaseDef").text(data.definicion_base_economica || "");
    if (data.nota_pausa) {
        $("#icModalLineaNotaPausa span").text(data.nota_pausa);
        $("#icModalLineaNotaPausa").show();
    } else {
        $("#icModalLineaNotaPausa").hide();
    }
    $("#icModalLineaFormula").text(data.formula || "—");
    $("#icModalLineaCalculo").text(data.calculo || "—");

    var pasosHtml = "";
    $.each(data.pasos || [], function (i, paso) {
        pasosHtml += "<tr>";
        pasosHtml += "<td>" + paso.etiqueta.replace(/^\d+\.\s*/, "") + "</td>";
        pasosHtml += "<td><strong>" + paso.valor + "</strong></td>";
        pasosHtml += "<td>" + paso.detalle + "</td>";
        pasosHtml += "</tr>";
    });
    $("#icModalLineaPasos").html(pasosHtml);

    var compHtml = "";
    $.each(data.comparacion || [], function (i, item) {
        compHtml += '<div class="col-sm-6 col-xs-6">';
        compHtml += '<div class="ic-modal-dato">';
        compHtml += '<span class="ic-modal-dato-val">' + item.valor + "</span>";
        compHtml += '<span class="ic-modal-dato-lbl">' + item.etiqueta + "</span>";
        compHtml += "</div></div>";
    });
    $("#icModalLineaComparacion").html(compHtml);

    icRenderCapacidadDual(data.capacidad_pago, data.capacidad_compra, data.balance);

    icRenderTablaLogicaEn(
        data.tabla_accion,
        "#icModalLineaAccionWrap",
        null,
        "#icModalLineaAccionIntro",
        "#icModalLineaAccionHead",
        "#icModalLineaAccionBody"
    );

    if (data.tabla_accion && data.tabla_accion.filas && data.tabla_accion.filas.length) {
        $("#icModalLineaAccionWrap").show();
    }

    $("#modalDetalleLinea").modal("show");
}

function icRenderTablaLogicaEn(tabla, wrapSel, tituloSel, introSel, headSel, bodySel) {
    var $wrap = $(wrapSel);

    if (!tabla || !tabla.filas || !tabla.filas.length) {
        $wrap.hide();
        return;
    }

    if (tituloSel) {
        $(tituloSel).text(tabla.titulo || "Reglas");
    }
    if (tabla.intro) {
        $(introSel).text(tabla.intro).show();
    } else {
        $(introSel).hide();
    }

    var columnas = tabla.columnas || ["Situación", "Condición", "Score"];
    var headHtml = "";
    $.each(columnas, function (i, col) {
        headHtml += "<th>" + col + "</th>";
    });
    $(headSel).html(headHtml);

    var bodyHtml = "";
    $.each(tabla.filas, function (i, fila) {
        var clases = [];
        if (fila.aplica) clases.push("ic-logica-aplica");
        if (fila.es_resultado) clases.push("ic-logica-resultado");

        var badge = fila.aplica
            ? '<span class="ic-modal-logica-badge">' + (fila.es_resultado ? "Su caso" : "Aplica") + "</span>"
            : "";

        bodyHtml += '<tr class="' + clases.join(" ") + '">';
        bodyHtml += "<td>" + fila.situacion + badge + "</td>";
        bodyHtml += "<td>" + fila.condicion + "</td>";
        bodyHtml += "<td>" + fila.score + "</td>";
        bodyHtml += "</tr>";
    });
    $(bodySel).html(bodyHtml);
    $wrap.show();
}

function icRenderCapacidadDual(capPago, capCompra, balance) {
    var $wrap = $("#icModalLineaCapacidadWrap");
    if (!capPago && !capCompra) {
        $wrap.hide();
        return;
    }

    if (balance) {
        $("#icModalLineaBalance").text(balance).show();
    } else {
        $("#icModalLineaBalance").hide();
    }

    function renderBox(cap, $el, scoreKey) {
        if (!cap) {
            $el.hide();
            return;
        }
        $el.show();
        $el.find(".ic-cap-modal-titulo").text(cap.titulo);
        $el.find(".ic-cap-modal-intro").text(cap.intro);
        var score = scoreKey === "score_riesgo" ? cap.score_riesgo : cap.score_comercial;
        $el.find(".ic-cap-modal-score")
            .text(score !== null && score !== undefined ? parseFloat(score).toFixed(1) : "—")
            .css("color", icColorScore(score || 50));

        var listHtml = "";
        $.each(cap.resumen || [], function (i, item) {
            listHtml += "<li><span>" + item.etiqueta + "</span><strong>" + item.valor + "</strong></li>";
        });
        $el.find(".ic-cap-modal-lista").html(listHtml);

        if (cap.indicadores && cap.indicadores.length) {
            var indHtml = "";
            $.each(cap.indicadores, function (i, ind) {
                indHtml += '<li title="' + String(ind.detalle).replace(/"/g, "&quot;") + '">'
                    + ind.nombre + ' <span>' + parseFloat(ind.score).toFixed(1) + "</span></li>";
            });
            $el.find(".ic-cap-modal-indicadores").html(indHtml);
            $el.find(".ic-cap-modal-ind-wrap").show();
        } else {
            $el.find(".ic-cap-modal-ind-wrap").hide();
        }
    }

    renderBox(capPago, $("#icModalCapPago"), "score_riesgo");
    renderBox(capCompra, $("#icModalCapCompra"), "score_comercial");
    $wrap.show();
}
