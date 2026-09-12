<?php
$stockCobertura = ControladorDashboardStockCobertura::ctrDatos();
$stockCoberturaJson = json_encode($stockCobertura, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$stockCoberturaDesde = ControladorDashboardStockCobertura::ctrFmtFechaCorta($stockCobertura["desde"]);
$stockCoberturaHasta = ControladorDashboardStockCobertura::ctrFmtFechaCorta($stockCobertura["hasta"]);
?>

<style>
    #box-stock-cobertura > .box-header { padding: 8px 12px; }
    #box-stock-cobertura > .box-body { padding: 8px 12px 10px; }
    #box-stock-cobertura .stock-marcas {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #box-stock-cobertura .stock-chip {
        color: #fff;
        border-radius: 3px;
        padding: 7px 10px;
        line-height: 1.2;
    }
    #box-stock-cobertura .stock-chip .stock-lbl {
        display: block;
        font-size: 11px;
        opacity: 0.92;
    }
    #box-stock-cobertura .stock-chip strong {
        font-size: 18px;
        font-weight: 700;
    }
    #box-stock-cobertura .stock-chip .stock-sub {
        display: block;
        font-size: 10px;
        opacity: 0.9;
        margin-top: 1px;
    }
    #box-stock-cobertura .stock-metrica {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px solid rgba(255,255,255,0.22);
        font-size: 10px;
        line-height: 1.25;
    }
    #box-stock-cobertura .stock-metrica span { opacity: 0.92; }
    #box-stock-cobertura .stock-metrica b { white-space: nowrap; font-weight: 700; }
    #box-stock-cobertura .chart {
        height: 360px;
        position: relative;
    }
    #box-stock-cobertura .stock-chart-title {
        font-weight: 600;
        font-size: 12px;
        margin: 0 0 4px;
    }
    #box-stock-cobertura .stock-chip.alerta-warn { box-shadow: inset 0 0 0 2px rgba(255,235,59,0.85); }
    #box-stock-cobertura .stock-chip.alerta-danger { box-shadow: inset 0 0 0 2px rgba(255,205,210,0.95); }
</style>

<div class="box box-info" id="box-stock-cobertura">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-balance-scale"></i>
            Artículos en peligro vs los que alcanzan
            <small>— Si se vende igual que <?php echo htmlspecialchars($stockCoberturaDesde . " – " . $stockCoberturaHasta); ?> · Almacén + proceso − pedido</small>
        </h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <div class="stock-marcas">
                    <?php foreach ($stockCobertura["series"] as $serie) { ?>
                        <div class="stock-chip alerta-<?php echo htmlspecialchars($serie["alerta"]); ?>"
                             style="background: <?php echo htmlspecialchars($serie["hex"]); ?>;">
                            <span class="stock-lbl"><?php echo htmlspecialchars($serie["label"]); ?></span>
                            <strong><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["peligro_30"]); ?> en peligro</strong>
                            <span class="stock-sub">
                                de <?php echo ControladorDashboardStockCobertura::ctrFmt($serie["articulos"]); ?> con venta en 30 días
                                (<?php echo (int) $serie["pct_peligro"]; ?>%)
                            </span>
                            <div class="stock-metrica">
                                <span>Alcanzan los 30 días</span>
                                <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["alcanzan_30"]); ?></b>
                            </div>
                            <div class="stock-metrica">
                                <span>Ya en peligro hoy</span>
                                <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["peligro_hoy"]); ?></b>
                            </div>
                            <div class="stock-metrica">
                                <span>Ya pedido (comprometido)</span>
                                <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["comprometido"]); ?></b>
                            </div>
                            <div class="stock-metrica">
                                <span>Disponible (alm + proceso − pedido)</span>
                                <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["disponible"]); ?></b>
                            </div>
                            <div class="stock-metrica">
                                <span>Facturado 30 días</span>
                                <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["facturado"]); ?></b>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-md-9">
                <p class="stock-chart-title">Artículos en peligro si se vende igual que los últimos 30 días</p>
                <p class="stock-sub" style="color:#666; margin: 0 0 8px; font-size: 11px; line-height: 1.35;">
                    Solo el quiebre, para que se vea. Lo que sí alcanza está a la izquierda.
                    El stock disponible ya descuenta lo pedido. Si la barra crece, se van quedando SKU sin cubrirse.
                </p>
                <div class="chart">
                    <canvas id="stockCoberturaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    var stockCoberturaChart = null;
    var stockCoberturaDatos = <?php echo $stockCoberturaJson; ?>;

    function stockCoberturaFmt(n) {
        n = parseFloat(n) || 0;
        return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function crearGraficoStockCobertura(datos) {
        if (typeof Chart === "undefined" || !datos || !datos.eje || !datos.series || !datos.series.length) {
            return;
        }

        var canvas = document.getElementById("stockCoberturaChart");
        if (!canvas) {
            return;
        }

        if (stockCoberturaChart && typeof stockCoberturaChart.destroy === "function") {
            stockCoberturaChart.destroy();
            stockCoberturaChart = null;
        }

        var cortes = [0, 10, 20, 30];
        var labels = cortes.map(function(d) {
            return d === 0 ? "Hoy" : "En " + d + " días";
        });
        var datasets = datos.series.map(function(serie) {
            return {
                label: serie.label,
                data: cortes.map(function(d) {
                    return serie.dataPeligro && serie.dataPeligro[d] != null ? serie.dataPeligro[d] : 0;
                }),
                backgroundColor: serie.hex,
                borderColor: serie.border,
                borderWidth: 1
            };
        });

        stockCoberturaChart = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: datasets
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    var ctx = chart.chart.ctx;
                    ctx.save();
                    ctx.font = "11px sans-serif";
                    ctx.fillStyle = "#333";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "bottom";
                    chart.data.datasets.forEach(function(ds, i) {
                        var meta = chart.getDatasetMeta(i);
                        if (!meta || meta.hidden) {
                            return;
                        }
                        meta.data.forEach(function(bar, index) {
                            var val = ds.data[index];
                            if (val === null || val === undefined) {
                                return;
                            }
                            ctx.fillText(stockCoberturaFmt(val), bar._model.x, bar._model.y - 3);
                        });
                    });
                    ctx.restore();
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 16 }
                },
                legend: {
                    display: true,
                    position: "top",
                    labels: {
                        boxWidth: 10,
                        fontSize: 10,
                        padding: 6
                    }
                },
                tooltips: {
                    mode: "index",
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, chartData) {
                            var nombre = chartData.datasets[tooltipItem.datasetIndex].label;
                            return nombre + ": " + stockCoberturaFmt(tooltipItem.yLabel) + " en peligro";
                        }
                    }
                },
                scales: {
                    xAxes: [{
                        barPercentage: 0.7,
                        categoryPercentage: 0.55,
                        ticks: { fontSize: 11 }
                    }],
                    yAxes: [{
                        ticks: {
                            fontSize: 10,
                            beginAtZero: true,
                            callback: function(value) {
                                return stockCoberturaFmt(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            fontSize: 10,
                            labelString: "Artículos en peligro"
                        }
                    }]
                }
            }
        });
    }

    $(document).ready(function() {
        crearGraficoStockCobertura(stockCoberturaDatos);
    });
</script>
