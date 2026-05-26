function actualizarFiltrosCobranzas() {
    var año = $("#añoCobranzas").val() || "";
    var mes = $("#mesCobranzas").val() || "";
    var vendedor = $("#vendedorCobranzas").val() || "";

    var url = "index.php?ruta=dashboard-cobranzas";

    if (año !== "") {
        url += "&año=" + encodeURIComponent(año);
    }
    if (mes !== "") {
        url += "&mes=" + encodeURIComponent(mes);
    }
    if (vendedor !== "") {
        url += "&vendedor=" + encodeURIComponent(vendedor);
    }

    window.location = url;
}

function dcColorConAlpha(hex, alpha) {
    var limpio = (hex || "#3d9970").replace("#", "");
    if (limpio.length !== 6) {
        return "rgba(61, 153, 112, " + alpha + ")";
    }
    var r = parseInt(limpio.substring(0, 2), 16);
    var g = parseInt(limpio.substring(2, 4), 16);
    var b = parseInt(limpio.substring(4, 6), 16);
    return "rgba(" + r + ", " + g + ", " + b + ", " + alpha + ")";
}

function dcCrearSparkline(canvas, valores, labels, esOperaciones, colorAccent) {
    if (!canvas || !valores || !valores.length) {
        return;
    }

    var stroke = colorAccent || "#3d9970";
    var fill = dcColorConAlpha(stroke, 0.18);

    var ctx = canvas.getContext("2d");
    var chartData = {
        labels: labels,
        datasets: [
            {
                fillColor: fill,
                strokeColor: stroke,
                pointColor: "rgba(0, 0, 0, 0)",
                pointStrokeColor: "rgba(0, 0, 0, 0)",
                pointHighlightFill: stroke,
                pointHighlightStroke: stroke,
                data: valores,
            },
        ],
    };

    var options = {
        scaleShowGridLines: false,
        scaleShowHorizontalLines: false,
        scaleShowVerticalLines: false,
        scaleLineColor: "rgba(0,0,0,0)",
        scaleFontSize: 0,
        pointDot: false,
        pointDotRadius: 0,
        datasetStrokeWidth: 2,
        bezierCurve: false,
        datasetFill: true,
        responsive: true,
        maintainAspectRatio: false,
        showTooltips: true,
        tooltipTemplate: esOperaciones
            ? "<%if (label){%><%=label%>: <%}%><%= value %> op."
            : "<%if (label){%><%=label%>: <%}%>S/ <%= value %>",
    };

    if (typeof Chart === "undefined") {
        return;
    }

    var chartV1 = new Chart(ctx);
    if (typeof chartV1.Line === "function") {
        chartV1.Line(chartData, options);
        return;
    }

    if (typeof Chart === "function") {
        new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        data: valores,
                        borderColor: stroke,
                        backgroundColor: fill,
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: true,
                        lineTension: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { enabled: true },
                scales: {
                    xAxes: [{ display: false }],
                    yAxes: [{ display: false, ticks: { beginAtZero: true } }],
                },
            },
        });
    }
}

function inicializarSparklinesCobranzas(data) {
    if (!data) {
        return;
    }

    var labels = data.labels || [];

    $(".dc-kpi-card[data-spark-id]").each(function () {
        var $card = $(this);
        var canvasId = $card.data("spark-id");
        var key = $card.data("spark-key");
        var canvas = document.getElementById(canvasId);
        var $wrap = $card.find(".dc-kpi-card__chart");
        var $loading = $wrap.find(".dc-kpi-card__chart-loading");

        if (!canvas || !data[key]) {
            $loading.text("—");
            return;
        }

        $loading.hide();
        $(canvas).show();
        dcCrearSparkline(
            canvas,
            data[key],
            labels,
            key === "operaciones",
            $card.data("spark-color")
        );
    });
}

var dcChartCobranzaDia = null;
var dcChartCobranzaSemana = null;
var dcChartCobranzaDiaSemana = null;

function dcFormatearSoles(valor) {
    var n = Math.round(parseFloat(valor) || 0);
    return "S/ " + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/** Etiquetas sobre puntos/barras: 10k, 15k, 13,4k */
function dcFormatearMontoCompacto(valor) {
    var n = parseFloat(valor) || 0;
    var abs = Math.abs(n);

    if (abs < 1000) {
        return String(Math.round(n));
    }

    var k = n / 1000;
    var texto = k.toFixed(1).replace(".", ",");
    texto = texto.replace(/,0$/, "");

    return texto + "k";
}

/** Tope del eje Y en Morris: evita mucho espacio vacío arriba (p. ej. 200k con pico 110k). */
function dcCalcularYMaxMorris(montos, lineaReferencia) {
    var maxValor = 0;
    var i;

    for (i = 0; i < montos.length; i++) {
        if (montos[i] > maxValor) {
            maxValor = montos[i];
        }
    }

    if (lineaReferencia > maxValor) {
        maxValor = lineaReferencia;
    }

    if (maxValor <= 0) {
        return null;
    }

    var techo = maxValor * 1.12;

    if (techo >= 10000) {
        return Math.ceil(techo / 5000) * 5000;
    }

    if (techo >= 1000) {
        return Math.ceil(techo / 500) * 500;
    }

    return Math.ceil(techo / 50) * 50;
}

function dcEtiquetasRangoSemana(semanaData) {
    if (semanaData.rangos && semanaData.rangos.length) {
        return semanaData.rangos;
    }

    var labelsFull = semanaData.labels || [];
    var out = [];
    var i;
    var m;

    for (i = 0; i < labelsFull.length; i++) {
        m = String(labelsFull[i]).match(/\((\d{2}\s*-\s*\d{2})\)/);
        if (m) {
            out.push(m[1].replace(/\s/g, "").replace("-", " al "));
        } else {
            out.push(labelsFull[i]);
        }
    }

    return out;
}

/** Escala fija en Chart.js 1: tope con margen para etiquetas sobre el pico (S3, etc.). */
function dcOpcionesEscalaChartLinea(valores) {
    var ymax = dcCalcularYMaxMorris(valores, 0);

    if (!ymax) {
        return {};
    }

    ymax = Math.ceil((ymax * 1.15) / 5000) * 5000;
    var steps = 4;
    var stepWidth = ymax / steps;

    return {
        scaleOverride: true,
        scaleSteps: steps,
        scaleStepWidth: stepWidth,
        scaleStartValue: 0,
    };
}

/** Chart.js 1.x redibuja al hover y borra texto pintado en canvas; re-pintar tras cada draw */
function dcEnlazarEtiquetasFijas(chartInstance, pintarFn) {
    if (!chartInstance || typeof chartInstance.draw !== "function" || chartInstance._dcMontosEtiquetasOk) {
        return;
    }

    chartInstance._dcMontosEtiquetasOk = true;
    var drawOriginal = chartInstance.draw;

    chartInstance.draw = function (ease) {
        var ret = drawOriginal.apply(this, arguments);
        pintarFn(this);
        return ret;
    };
}

function inicializarGraficoCobranzaDia(data) {
    var $loading = $("#dcGraficoCobranzaDiaLoading");
    var $wrap = $("#dcGraficoCobranzaDiaWrap");
    var $el = $("#dc-grafico-cobranza-dia");

    if (!$el.length || !data || !data.cobranza_total) {
        $loading.text("Sin datos para el período");
        return;
    }

    var labels = data.labels || [];
    var montos = data.cobranza_total || [];
    var morrisData = [];
    var i;

    for (i = 0; i < labels.length; i++) {
        morrisData.push({
            dia: labels[i],
            cobranza: parseFloat(montos[i]) || 0,
        });
    }

    var promedio = parseFloat(data.promedio_diario_linea) || 0;
    var goals = promedio > 0 ? [promedio] : [];
    var ymaxMorris = dcCalcularYMaxMorris(montos, promedio);

    $("#dcGraficoDiaPromedio").text(
        "Promedio diario: " + (promedio > 0 ? dcFormatearSoles(promedio) : "—")
    );

    $el.empty();
    if (dcChartCobranzaDia !== null) {
        dcChartCobranzaDia = null;
    }

    $loading.hide();
    $wrap.show();

    dcChartCobranzaDia = Morris.Bar({
        element: "dc-grafico-cobranza-dia",
        resize: true,
        data: morrisData,
        xkey: "dia",
        ykeys: ["cobranza"],
        labels: ["Cobranza"],
        barColors: ["#3d9970"],
        goals: goals,
        goalLineColors: ["#95a5a6"],
        goalStrokeWidth: 2,
        hideHover: "auto",
        gridTextColor: "#666",
        ymax: ymaxMorris !== null ? ymaxMorris : "auto",
        ymin: 0,
        barSizeRatio: 0.88,
        barGap: 1,
        padding: 10,
        numLines: 4,
        gridTextSize: 10,
        xLabelAngle: 0,
        yLabelFormat: function (y) {
            return dcFormatearMontoCompacto(y);
        },
    });
}

function dcPintarEjeYCompacto(chartInstance) {
    var scale = chartInstance.scale;
    var chart = chartInstance.chart;

    if (!scale || !chart || !chart.ctx || !scale.yLabels || !scale.yLabels.length) {
        return;
    }

    var ctx = chart.ctx;
    var yLabelGap = (scale.endPoint - scale.startPoint) / scale.steps;
    var x = Math.round(scale.xScalePaddingLeft) - 10;
    var padIzq = Math.max(0, x - 52);
    var i;
    var yLabelCenter;
    var valorNumerico;
    var texto;

    ctx.save();
    ctx.font = scale.font || '9px "Helvetica Neue", Helvetica, Arial, sans-serif';
    ctx.textAlign = "right";
    ctx.textBaseline = "middle";

    for (i = 0; i < scale.yLabels.length; i++) {
        yLabelCenter = scale.endPoint - yLabelGap * i;
        valorNumerico = scale.min + i * scale.stepValue;
        texto = dcFormatearMontoCompacto(valorNumerico);

        ctx.fillStyle = "#ffffff";
        ctx.fillRect(padIzq, yLabelCenter - 8, x - padIzq + 10, 16);
        ctx.fillStyle = scale.textColor || "#64748b";
        ctx.fillText(texto, x, yLabelCenter);
    }

    ctx.restore();
}

function dcPintarLineaSemanaConEje(chartInstance) {
    dcPintarEjeYCompacto(chartInstance);
    dcPintarMontosEnLinea(chartInstance);
}

function dcPintarMontosEnLinea(chartInstance) {
    if (!chartInstance || !chartInstance.chart || !chartInstance.datasets) {
        return;
    }

    var ctx = chartInstance.chart.ctx;
    ctx.save();
    ctx.font = '600 11px "Helvetica Neue", Helvetica, Arial, sans-serif';
    ctx.fillStyle = "#334155";
    ctx.textAlign = "center";
    ctx.textBaseline = "bottom";

    chartInstance.datasets.forEach(function (dataset) {
        dataset.points.forEach(function (point) {
            if (!point.hasValue()) {
                return;
            }
            var texto = dcFormatearMontoCompacto(point.value);
            var offsetY = point.y < 28 ? 14 : 12;
            ctx.fillText(texto, point.x, point.y - offsetY);
        });
    });

    ctx.restore();
}

function inicializarGraficoCobranzaSemana(semanaData) {
    var $loading = $("#dcGraficoCobranzaSemanaLoading");
    var $wrap = $("#dcGraficoCobranzaSemanaWrap");
    var canvas = document.getElementById("dcGraficoCobranzaSemanaCanvas");

    if (!canvas || !semanaData || !semanaData.promedios) {
        $loading.text("Sin datos para el período");
        return;
    }

    var labelsFull = semanaData.labels || [];
    var promedios = semanaData.promedios || [];
    var etiquetasEje = dcEtiquetasRangoSemana(semanaData);
    var escalaLinea = dcOpcionesEscalaChartLinea(promedios);

    if (typeof Chart === "undefined") {
        $loading.text("Gráfico no disponible");
        return;
    }

    dcChartCobranzaSemana = null;

    $loading.hide();
    $wrap.show();

    var ctx = canvas.getContext("2d");
    var chartData = {
        labels: etiquetasEje,
        datasets: [
            {
                fillColor: "rgba(60, 141, 188, 0.12)",
                strokeColor: "#3c8dbc",
                pointColor: "#3c8dbc",
                pointStrokeColor: "#ffffff",
                pointHighlightFill: "#3c8dbc",
                pointHighlightStroke: "#ffffff",
                data: promedios,
            },
        ],
    };

    var options = {
        bezierCurve: false,
        datasetFill: true,
        pointDot: true,
        pointDotRadius: 4,
        pointDotStrokeWidth: 2,
        datasetStrokeWidth: 2,
        scaleBeginAtZero: true,
        scaleShowGridLines: true,
        scaleGridLineColor: "rgba(0,0,0,0.06)",
        scaleFontSize: 9,
        scaleFontColor: "#64748b",
        responsive: true,
        maintainAspectRatio: false,
        showTooltips: true,
        tooltipTemplate:
            "<%if (label){%>Días <%=label%>: <%}%><%= value %> (prom. diario)",
        onAnimationComplete: function () {
            dcPintarMontosEnLinea(this);
        },
    };

    if (escalaLinea.scaleOverride) {
        options.scaleOverride = escalaLinea.scaleOverride;
        options.scaleSteps = escalaLinea.scaleSteps;
        options.scaleStepWidth = escalaLinea.scaleStepWidth;
        options.scaleStartValue = escalaLinea.scaleStartValue;
    }

    var chartV1 = new Chart(ctx);
    if (typeof chartV1.Line === "function") {
        dcChartCobranzaSemana = chartV1.Line(chartData, options);
        dcEnlazarEtiquetasFijas(dcChartCobranzaSemana, dcPintarLineaSemanaConEje);
        return;
    }

    if (typeof Chart === "function") {
        dcChartCobranzaSemana = new Chart(ctx, {
            type: "line",
            data: {
                labels: etiquetasEje,
                datasets: [
                    {
                        data: promedios,
                        borderColor: "#3c8dbc",
                        backgroundColor: "rgba(60, 141, 188, 0.12)",
                        borderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        lineTension: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { enabled: true },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }],
                },
            },
        });
    }
}

function dcPintarMontosEnBarras(chartInstance) {
    if (!chartInstance || !chartInstance.chart || !chartInstance.datasets) {
        return;
    }

    var ctx = chartInstance.chart.ctx;
    ctx.save();
    ctx.font = '600 10px "Helvetica Neue", Helvetica, Arial, sans-serif';
    ctx.fillStyle = "#334155";
    ctx.textAlign = "center";
    ctx.textBaseline = "bottom";

    chartInstance.datasets.forEach(function (dataset) {
        if (!dataset.bars) {
            return;
        }
        dataset.bars.forEach(function (bar) {
            if (!bar.value && bar.value !== 0) {
                return;
            }
            ctx.fillText(dcFormatearMontoCompacto(bar.value), bar.x, bar.y - 8);
        });
    });

    ctx.restore();
}

function inicializarGraficoCobranzaDiaSemana(diaSemanaData) {
    var $loading = $("#dcGraficoDiaSemanaLoading");
    var $wrap = $("#dcGraficoDiaSemanaWrap");
    var $badge = $("#dcGraficoDiaSemanaMejor");
    var canvas = document.getElementById("dcGraficoDiaSemanaCanvas");

    if (!canvas || !diaSemanaData || !diaSemanaData.montos) {
        $loading.text("Sin datos para el período");
        return;
    }

    var labels = diaSemanaData.labels || [];
    var montos = diaSemanaData.montos || [];

    if (typeof Chart === "undefined") {
        $loading.text("Gráfico no disponible");
        return;
    }

    dcChartCobranzaDiaSemana = null;

    if (diaSemanaData.mejor_dia && parseFloat(diaSemanaData.mejor_monto) > 0) {
        $badge
            .text(
                "Mayor prom.: " +
                    diaSemanaData.mejor_dia +
                    " · " +
                    dcFormatearSoles(diaSemanaData.mejor_monto)
            )
            .show();
    } else {
        $badge.hide();
    }

    $loading.hide();
    $wrap.show();

    var ctx = canvas.getContext("2d");
    var chartData = {
        labels: labels,
        datasets: [
            {
                fillColor: "rgba(60, 141, 188, 0.85)",
                strokeColor: "rgba(60, 141, 188, 1)",
                highlightFill: "rgba(26, 122, 76, 0.9)",
                highlightStroke: "#1a7a4c",
                data: montos,
            },
        ],
    };

    var options = {
        scaleBeginAtZero: true,
        scaleShowGridLines: true,
        scaleGridLineColor: "rgba(0,0,0,0.06)",
        scaleFontSize: 11,
        scaleFontColor: "#64748b",
        responsive: true,
        maintainAspectRatio: false,
        showTooltips: true,
        tooltipTemplate: "<%if (label){%><%=label%>: <%}%>S/ <%= value %> (prom.)",
        onAnimationComplete: function () {
            dcPintarMontosEnBarras(this);
        },
    };

    var chartV1 = new Chart(ctx);
    if (typeof chartV1.Bar === "function") {
        dcChartCobranzaDiaSemana = chartV1.Bar(chartData, options);
        dcEnlazarEtiquetasFijas(dcChartCobranzaDiaSemana, dcPintarMontosEnBarras);
    }
}

function cargarSparklinesCobranzas() {
    var $el = $("#dashboardCobranzasData");
    if (!$el.length) {
        return;
    }

    var params = {
        anno: $el.data("anno"),
        mes: $el.data("mes"),
        vendedor: $el.data("vendedor") || "",
        vendedor_top: $el.data("vendedor-top") || "",
    };

    $.ajax({
        url: "ajax/dashboard-cobranzas.sparklines.ajax.php",
        data: params,
        dataType: "text",
        cache: false,
    })
        .done(function (texto) {
            var resp = null;

            try {
                resp = JSON.parse(texto);
            } catch (e1) {
                var inicio = texto.indexOf("{");
                if (inicio >= 0) {
                    try {
                        resp = JSON.parse(texto.substring(inicio));
                    } catch (e2) {
                        resp = null;
                    }
                }
            }

            if (resp && resp.ok && resp.sparklines) {
                setTimeout(function () {
                    inicializarSparklinesCobranzas(resp.sparklines);
                    inicializarGraficoCobranzaDia(resp.sparklines);
                    if (resp.cobranza_semana) {
                        inicializarGraficoCobranzaSemana(resp.cobranza_semana);
                    } else {
                        $("#dcGraficoCobranzaSemanaLoading").text("Sin datos");
                    }
                    if (resp.cobranza_dia_semana) {
                        inicializarGraficoCobranzaDiaSemana(resp.cobranza_dia_semana);
                    } else {
                        $("#dcGraficoDiaSemanaLoading").text("Sin datos");
                    }
                }, 50);
            } else {
                $(".dc-kpi-card__chart-loading").text("—");
                $("#dcGraficoCobranzaDiaLoading").text("Sin datos");
                $("#dcGraficoCobranzaSemanaLoading").text("Sin datos");
                $("#dcGraficoDiaSemanaLoading").text("Sin datos");
            }
        })
        .fail(function () {
            $(".dc-kpi-card__chart-loading").text("—");
            $("#dcGraficoCobranzaDiaLoading").text("Error al cargar");
            $("#dcGraficoCobranzaSemanaLoading").text("Error al cargar");
            $("#dcGraficoDiaSemanaLoading").text("Error al cargar");
        });
}

$(document).ready(function () {
    if ($("#añoCobranzas").length) {
        $("#añoCobranzas, #mesCobranzas, #vendedorCobranzas").selectpicker("refresh");
    }

    cargarSparklinesCobranzas();
});

$("#añoCobranzas").change(function () {
    actualizarFiltrosCobranzas();
});

$("#mesCobranzas").change(function () {
    actualizarFiltrosCobranzas();
});

$("#vendedorCobranzas").change(function () {
    actualizarFiltrosCobranzas();
});
