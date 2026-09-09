<?php
$stockMensual = ControladorDashboardStockMensual::ctrDatos();
$stockMensualJson = json_encode($stockMensual, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$stockMensualFecha = "";
if (!empty($stockMensual["fechas"])) {
    foreach (array_reverse($stockMensual["fechas"]) as $fechaSnap) {
        if ($fechaSnap !== "") {
            $stockMensualFecha = $fechaSnap;
            break;
        }
    }
}
$stockMensualProy = isset($stockMensual["proyeccion"]) ? $stockMensual["proyeccion"] : array("activa" => false);
$stockMensualFechaCorta = $stockMensualFecha;
if ($stockMensualFecha !== "" && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $stockMensualFecha, $p)) {
    $stockMensualFechaCorta = (int) $p[3] . "/" . (int) $p[2];
}
$stockMensualAnioEspejo = !empty($stockMensualProy["anio"]) ? (int) $stockMensualProy["anio"] : ((int) $stockMensual["anio"] - 1);
?>

<style>
    #box-stock-mensual > .box-header { padding: 8px 12px; }
    #box-stock-mensual > .box-body { padding: 8px 12px 10px; }
    #box-stock-mensual .stock-marcas {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #box-stock-mensual .stock-chip {
        color: #fff;
        border-radius: 3px;
        padding: 7px 10px;
        line-height: 1.2;
    }
    #box-stock-mensual .stock-chip .stock-lbl {
        display: block;
        font-size: 11px;
        opacity: 0.92;
    }
    #box-stock-mensual .stock-chip strong {
        font-size: 18px;
        font-weight: 700;
    }
    #box-stock-mensual .stock-chip .stock-sub {
        display: block;
        font-size: 10px;
        opacity: 0.9;
        margin-top: 1px;
    }
    #box-stock-mensual .stock-metrica {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px solid rgba(255,255,255,0.22);
        font-size: 10px;
        line-height: 1.25;
    }
    #box-stock-mensual .stock-metrica span {
        opacity: 0.92;
    }
    #box-stock-mensual .stock-metrica b {
        white-space: nowrap;
        font-weight: 700;
    }
    #box-stock-mensual .chart {
        height: 420px;
        position: relative;
    }
    #box-stock-mensual .stock-chart-title {
        font-weight: 600;
        font-size: 12px;
        margin: 0 0 4px;
    }
</style>

<div class="box box-primary" id="box-stock-mensual">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-cubes"></i>
            Stock final por mes
            <small>— Activos · Almacén 01 · <?php echo (int) $stockMensual["anio"]; ?></small>
        </h3>
    </div>
    <div class="box-body">
        <?php if (count($stockMensual["meses"]) === 0) { ?>
            <p class="text-muted" style="margin: 12px 0;">No hay snapshots de stock para este año.</p>
        <?php } else { ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="stock-marcas">
                        <?php foreach ($stockMensual["series"] as $serie) { ?>
                            <div class="stock-chip" style="background: <?php echo htmlspecialchars($serie["hex"]); ?>;">
                                <span class="stock-lbl"><?php echo htmlspecialchars($serie["label"]); ?></span>
                                <strong><?php echo ControladorDashboardStockMensual::ctrFmt($serie["actual"]); ?></strong>
                                <span class="stock-sub">und en almacén<?php echo $stockMensualFechaCorta !== "" ? " · " . htmlspecialchars($stockMensualFechaCorta) : ""; ?></span>
                                <?php if ($serie["proyectado"] !== null) { ?>
                                    <div class="stock-metrica">
                                        <span>Dic si producimos y vendemos como <?php echo $stockMensualAnioEspejo; ?></span>
                                        <b><?php echo ControladorDashboardStockMensual::ctrFmt($serie["proyectado"]); ?></b>
                                    </div>
                                <?php } ?>
                                <?php if ($serie["factible"] !== null) { ?>
                                    <div class="stock-metrica">
                                        <span>Dic si solo entra lo ya cortado / en taller / en servicios</span>
                                        <b><?php echo ControladorDashboardStockMensual::ctrFmt($serie["factible"]); ?></b>
                                    </div>
                                <?php } ?>
                                <?php if (!empty($serie["prodProy"])) { ?>
                                    <div class="stock-metrica">
                                        <span>Ya en proceso hoy (corte + taller + servicios)</span>
                                        <b><?php echo ControladorDashboardStockMensual::ctrFmt($serie["proceso"]); ?></b>
                                    </div>
                                    <div class="stock-metrica">
                                        <span>Se produjo en <?php echo $stockMensualAnioEspejo; ?> en este mismo tramo</span>
                                        <b><?php echo ControladorDashboardStockMensual::ctrFmt($serie["prodProy"]); ?></b>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <p class="stock-chart-title">Tendencia de stock final</p>
                    <?php if (!empty($stockMensualProy["activa"])) { ?>
                        <p class="stock-sub" style="color:#666; margin: 0 0 8px; font-size: 11px; line-height: 1.35;">
                            Línea continua: stock real.
                            Punteada: stock a fin de cada mes si se produce y vende como <?php echo $stockMensualAnioEspejo; ?>.
                            Puntitos: el mismo cálculo, pero solo entra lo que hoy está en corte, taller o servicios (sin cortar de más).
                        </p>
                    <?php } ?>
                    <div class="chart">
                        <canvas id="stockMensualChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    var stockMensualChart = null;
    var stockMensualDatos = <?php echo $stockMensualJson; ?>;

    function stockMensualFmt(n) {
        n = parseFloat(n) || 0;
        return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function crearGraficoStockMensual(datos) {
        if (typeof Chart === "undefined" || !datos || !datos.meses || !datos.meses.length || !datos.series) {
            return;
        }

        var canvas = document.getElementById("stockMensualChart");
        if (!canvas) {
            return;
        }

        if (stockMensualChart && typeof stockMensualChart.destroy === "function") {
            stockMensualChart.destroy();
            stockMensualChart = null;
        }

        var datasets = [];
        datos.series.forEach(function(serie) {
            datasets.push({
                label: serie.label,
                data: serie.data,
                borderColor: serie.border,
                backgroundColor: serie.bg,
                pointBackgroundColor: serie.border,
                pointBorderColor: serie.border,
                pointHoverBackgroundColor: "#fff",
                pointHoverBorderColor: serie.border,
                borderWidth: 2,
                fill: false,
                lineTension: 0.3,
                spanGaps: false
            });
            if (serie.dataProy) {
                datasets.push({
                    label: serie.label + " · proy.",
                    data: serie.dataProy,
                    borderColor: serie.border,
                    backgroundColor: "transparent",
                    pointBackgroundColor: serie.border,
                    pointBorderColor: serie.border,
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: serie.border,
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 2,
                    fill: false,
                    lineTension: 0.3,
                    spanGaps: false
                });
            }
            if (serie.dataCorte) {
                datasets.push({
                    label: serie.label + " · proceso",
                    data: serie.dataCorte,
                    borderColor: serie.border,
                    backgroundColor: "transparent",
                    pointBackgroundColor: serie.border,
                    pointBorderColor: serie.border,
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: serie.border,
                    borderWidth: 1.5,
                    borderDash: [2, 3],
                    pointRadius: 1,
                    fill: false,
                    lineTension: 0.3,
                    spanGaps: false
                });
            }
        });

        stockMensualChart = new Chart(canvas.getContext("2d"), {
            type: "line",
            data: {
                labels: datos.meses,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: "top",
                    labels: {
                        boxWidth: 10,
                        fontSize: 10,
                        padding: 6,
                        filter: function(item) {
                            return item.text.indexOf("proy.") === -1 && item.text.indexOf("proceso") === -1;
                        }
                    }
                },
                tooltips: {
                    mode: "index",
                    intersect: false,
                    callbacks: {
                        title: function(items) {
                            if (!items.length) {
                                return "";
                            }
                            var i = items[0].index;
                            var mes = datos.meses[i] || "";
                            var dia = datos.fechas && datos.fechas[i] ? datos.fechas[i] : "";
                            if (dia) {
                                return mes + " (" + dia + ")";
                            }
                            return mes + " (proyección)";
                        },
                        label: function(tooltipItem, chartData) {
                            if (tooltipItem.yLabel === null || tooltipItem.yLabel === undefined) {
                                return null;
                            }
                            var nombre = chartData.datasets[tooltipItem.datasetIndex].label;
                            return nombre + ": " + stockMensualFmt(tooltipItem.yLabel);
                        }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            fontSize: 10,
                            callback: function(value) {
                                return stockMensualFmt(value);
                            }
                        }
                    }]
                }
            }
        });
    }

    $(document).ready(function() {
        crearGraficoStockMensual(stockMensualDatos);
    });
</script>
