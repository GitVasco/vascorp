$(document).ready(function () {

    $("#clienteInteligencia").on("changed.bs.select", function () {
        var cliente = $(this).val();

        if (cliente === "" || cliente === null) {
            window.location = "index.php?ruta=inteligencia-comercial";
            return;
        }

        window.location = "index.php?ruta=inteligencia-comercial&cliente=" + encodeURIComponent(cliente);
    });

    var dataEl = document.getElementById("icMotor1Data");
    if (!dataEl || typeof Chart === "undefined") {
        return;
    }

    var motorData = JSON.parse(dataEl.textContent);
    var factores = motorData.factores;
    var scoreFinal = motorData.score;

    icInitGauges(scoreFinal);
    icInitCharts(factores);
    icInitModales(factores);

});

function icColorScore(score) {
    if (score >= 80) return "#00a65a";
    if (score >= 70) return "#3c8dbc";
    if (score >= 60) return "#f39c12";
    return "#dd4b39";
}

function icInitGauges(score) {
    var ctx = document.getElementById("icGaugeScore");
    if (!ctx) return;

    var color = icColorScore(score);
    var restante = Math.max(0, 100 - score);

    new Chart(ctx.getContext("2d")).Doughnut(
        [
            { value: score, color: color, highlight: color, label: "Score" },
            { value: restante, color: "rgba(255,255,255,.15)", highlight: "rgba(255,255,255,.2)", label: "Restante" }
        ],
        {
            segmentShowStroke: false,
            percentageInnerCutout: 75,
            animation: true,
            animationSteps: 60,
            showTooltips: false
        }
    );
}

function icInitCharts(factores) {
    var labels = [];
    var scores = [];
    var colores = [];
    var aportaciones = [];
    var aportColores = [];

    $.each(factores, function (clave, f) {
        var nombreCorto = f.nombre.length > 22 ? f.nombre.substring(0, 20) + "…" : f.nombre;
        labels.push(nombreCorto);
        scores.push(f.score);
        colores.push(icColorScore(f.score));
        aportaciones.push(f.aportacion);
        aportColores.push(icColorScore(f.score));
    });

    var ctxFactores = document.getElementById("icChartFactores");
    if (ctxFactores) {
        new Chart(ctxFactores.getContext("2d")).Bar(
            {
                labels: labels,
                datasets: [{
                    label: "Score",
                    fillColor: colores,
                    strokeColor: colores,
                    highlightFill: colores,
                    highlightStroke: colores,
                    data: scores
                }]
            },
            {
                responsive: true,
                maintainAspectRatio: false,
                scaleBeginAtZero: true,
                scaleOverride: true,
                scaleSteps: 5,
                scaleStepWidth: 20,
                scaleStartValue: 0,
                barShowStroke: false,
                barDatasetSpacing: 2
            }
        );
    }

    var ctxAport = document.getElementById("icChartAportacion");
    if (ctxAport) {
        new Chart(ctxAport.getContext("2d")).Doughnut(
            aportaciones.map(function (val, i) {
                return {
                    value: val,
                    color: aportColores[i],
                    highlight: aportColores[i],
                    label: labels[i]
                };
            }),
            {
                segmentShowStroke: true,
                segmentStrokeColor: "#fff",
                segmentStrokeWidth: 2,
                percentageInnerCutout: 40,
                animation: true
            }
        );
    }
}

function icInitModales(factores) {
    $(".btnDetalleFactor").on("click", function () {
        var clave = $(this).data("factor");
        var f = factores[clave];
        if (!f) return;

        var color = icColorScore(f.score);

        $("#icModalIcon").attr("class", "fa " + f.icono);
        $("#icModalTitulo").text(f.nombre);
        $("#icModalScore").text(parseFloat(f.score).toFixed(1)).css("color", color);
        $("#icModalPeso").text("Peso " + f.peso + "%");
        $("#icModalAportacion").text(parseFloat(f.aportacion).toFixed(2));
        $("#icModalDetalle").text(f.detalle);
        $("#icModalFormula").text(f.formula);
        $("#icModalRegla").text(f.regla);

        var htmlValores = "";
        if (f.valores && f.valores.length) {
            $.each(f.valores, function (i, v) {
                htmlValores += '<div class="ic-modal-valor">';
                htmlValores += '<span class="text-muted">' + v.etiqueta + '</span>';
                htmlValores += '<strong>' + v.valor + '</strong>';
                htmlValores += '</div>';
            });
        }
        $("#icModalValores").html(htmlValores);

        $("#modalDetalleFactor").modal("show");
    });
}
