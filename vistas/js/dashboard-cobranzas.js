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

function dcFormatearSoles(valor) {
    var n = Math.round(parseFloat(valor) || 0);
    return "S/ " + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
        ymax: "auto",
        preUnits: "S/ ",
        xLabelAngle: 45,
    });
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
            var texto = dcFormatearSoles(point.value);
            ctx.fillText(texto, point.x, point.y - 10);
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
    var labelsCortos = [];
    var i;

    for (i = 0; i < labelsFull.length; i++) {
        labelsCortos.push("S" + (i + 1));
    }

    if (typeof Chart === "undefined") {
        $loading.text("Gráfico no disponible");
        return;
    }

    dcChartCobranzaSemana = null;

    $loading.hide();
    $wrap.show();

    var ctx = canvas.getContext("2d");
    var chartData = {
        labels: labelsCortos,
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
        scaleFontSize: 10,
        scaleFontColor: "#64748b",
        responsive: true,
        maintainAspectRatio: false,
        showTooltips: true,
        tooltipTemplate:
            "<%if (label){%><%=label%>: <%}%><%= value %> (prom. diario)",
        onAnimationComplete: function () {
            dcPintarMontosEnLinea(this);
        },
    };

    var chartV1 = new Chart(ctx);
    if (typeof chartV1.Line === "function") {
        dcChartCobranzaSemana = chartV1.Line(chartData, options);
        return;
    }

    if (typeof Chart === "function") {
        dcChartCobranzaSemana = new Chart(ctx, {
            type: "line",
            data: {
                labels: labelsCortos,
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
                }, 50);
            } else {
                $(".dc-kpi-card__chart-loading").text("—");
                $("#dcGraficoCobranzaDiaLoading").text("Sin datos");
                $("#dcGraficoCobranzaSemanaLoading").text("Sin datos");
            }
        })
        .fail(function () {
            $(".dc-kpi-card__chart-loading").text("—");
            $("#dcGraficoCobranzaDiaLoading").text("Error al cargar");
            $("#dcGraficoCobranzaSemanaLoading").text("Error al cargar");
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
