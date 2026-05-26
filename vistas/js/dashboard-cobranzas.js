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
        bezierCurve: true,
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
                        lineTension: 0.35,
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
                }, 50);
            } else {
                $(".dc-kpi-card__chart-loading").text("—");
            }
        })
        .fail(function () {
            $(".dc-kpi-card__chart-loading").text("—");
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
