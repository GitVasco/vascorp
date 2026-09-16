<?php
$stockCobertura = ControladorDashboardStockCobertura::ctrDatos();
$stockCoberturaJson = json_encode($stockCobertura, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$stockCoberturaDesde = ControladorDashboardStockCobertura::ctrFmtFechaCorta($stockCobertura["desde"]);
$stockCoberturaHasta = ControladorDashboardStockCobertura::ctrFmtFechaCorta($stockCobertura["hasta"]);
$stockCoberturaUrg = ControladorDashboardStockCobertura::ctrFmtPorc(isset($stockCobertura["urgencia"]) ? $stockCobertura["urgencia"] : 100);
?>

<style>
    #box-stock-cobertura > .box-header { padding: 6px 10px; }
    #box-stock-cobertura > .box-header .box-title { font-size: 15px; }
    #box-stock-cobertura > .box-body { padding: 6px 10px 8px; }
    #box-stock-cobertura .stock-marca {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }
    #box-stock-cobertura .stock-dona-wrap {
        height: 118px;
        width: 118px;
        flex: 0 0 118px;
        position: relative;
    }
    #box-stock-cobertura .stock-chip {
        flex: 1;
        min-width: 0;
        color: #fff;
        border-radius: 3px;
        padding: 5px 8px;
        line-height: 1.15;
    }
    #box-stock-cobertura .stock-chip .stock-lbl {
        font-size: 10px;
        opacity: 0.92;
        margin-right: 6px;
    }
    #box-stock-cobertura .stock-chip strong {
        font-size: 14px;
        font-weight: 700;
    }
    #box-stock-cobertura .stock-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px 10px;
        margin-top: 4px;
        font-size: 10px;
    }
    #box-stock-cobertura .stock-grid > div {
        display: flex;
        justify-content: space-between;
        gap: 6px;
    }
    #box-stock-cobertura .stock-grid span { opacity: 0.9; }
    #box-stock-cobertura .stock-grid b { font-weight: 700; white-space: nowrap; }
    #box-stock-cobertura .stock-chip.alerta-warn { box-shadow: inset 0 0 0 2px rgba(255,235,59,0.85); }
    #box-stock-cobertura .stock-chip.alerta-danger { box-shadow: inset 0 0 0 2px rgba(255,205,210,0.95); }
    @media (max-width: 991px) {
        #box-stock-cobertura .stock-marca { margin-bottom: 6px; }
    }
</style>

<div class="box box-info" id="box-stock-cobertura">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-balance-scale"></i>
            Artículos en peligro vs los que alcanzan
            <small>— Venta <?php echo htmlspecialchars($stockCoberturaDesde . " – " . $stockCoberturaHasta); ?> × <?php echo htmlspecialchars($stockCoberturaUrg); ?>% (urgencia OC) · Almacén + proceso − pedido</small>
        </h3>
    </div>
    <div class="box-body">
        <div class="row">
            <?php foreach ($stockCobertura["series"] as $i => $serie) { ?>
                <div class="col-md-6">
                    <div class="stock-marca">
                        <div class="stock-dona-wrap">
                            <canvas id="stockCoberturaDona-<?php echo (int) $i; ?>"></canvas>
                        </div>
                        <div class="stock-chip alerta-<?php echo htmlspecialchars($serie["alerta"]); ?>"
                             style="background: <?php echo htmlspecialchars($serie["hex"]); ?>;">
                            <span class="stock-lbl"><?php echo htmlspecialchars($serie["label"]); ?></span>
                            <strong><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["peligro_30"]); ?> en peligro</strong>
                            <span style="font-size:10px; opacity:0.9;">
                                / <?php echo ControladorDashboardStockCobertura::ctrFmt($serie["articulos"]); ?>
                                (<?php echo (int) $serie["pct_peligro"]; ?>%)
                            </span>
                            <div class="stock-grid">
                                <div><span>Alcanzan</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["alcanzan_30"]); ?></b></div>
                                <div><span>Peligro hoy</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["peligro_hoy"]); ?></b></div>
                                <div><span>Pedido</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["comprometido"]); ?></b></div>
                                <div><span>Disponible</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["disponible"]); ?></b></div>
                                <div><span>Fact. 30d</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["facturado"]); ?></b></div>
                                <div><span>Proy. <?php echo htmlspecialchars($stockCoberturaUrg); ?>%</span> <b><?php echo ControladorDashboardStockCobertura::ctrFmt($serie["proyectado"]); ?></b></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    var stockCoberturaDonas = [];
    var stockCoberturaDatos = <?php echo $stockCoberturaJson; ?>;

    function stockCoberturaFmt(n) {
        n = parseFloat(n) || 0;
        return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function stockCoberturaPluginCentro(chart) {
        var cfg = chart.config.centro;
        if (!cfg) {
            return;
        }
        var area = chart.chartArea;
        if (!area) {
            return;
        }
        var ctx = chart.chart.ctx;
        var cx = (area.left + area.right) / 2;
        var cy = (area.top + area.bottom) / 2;
        ctx.save();
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillStyle = cfg.pct >= 20 ? "#e67e22" : "#333";
        ctx.font = "bold 16px sans-serif";
        ctx.fillText(cfg.pct + "%", cx, cy - 6);
        ctx.fillStyle = "#666";
        ctx.font = "9px sans-serif";
        ctx.fillText("peligro", cx, cy + 9);
        ctx.restore();
    }

    function crearDonasStockCobertura(datos) {
        if (typeof Chart === "undefined" || !datos || !datos.series || !datos.series.length) {
            return;
        }

        stockCoberturaDonas.forEach(function(ch) {
            if (ch && typeof ch.destroy === "function") {
                ch.destroy();
            }
        });
        stockCoberturaDonas = [];

        datos.series.forEach(function(serie, i) {
            var canvas = document.getElementById("stockCoberturaDona-" + i);
            if (!canvas) {
                return;
            }
            var peligro = parseInt(serie.peligro_30, 10) || 0;
            var alcanzan = parseInt(serie.alcanzan_30, 10) || 0;
            var pct = parseInt(serie.pct_peligro, 10) || 0;
            var dataPeligro = peligro;
            var dataAlcanzan = alcanzan;
            var colorAlcanzan = serie.hex;
            var borderAlcanzan = serie.border;
            if (peligro === 0 && alcanzan === 0) {
                dataAlcanzan = 1;
                colorAlcanzan = "#d2d6de";
                borderAlcanzan = "#d2d6de";
            }

            var chart = new Chart(canvas.getContext("2d"), {
                type: "doughnut",
                data: {
                    labels: ["En peligro", "Alcanzan"],
                    datasets: [{
                        data: [dataPeligro, dataAlcanzan],
                        backgroundColor: ["#f39c12", colorAlcanzan],
                        borderColor: ["#d68910", borderAlcanzan],
                        borderWidth: 1
                    }]
                },
                centro: {
                    pct: pct
                },
                plugins: [{
                    afterDraw: stockCoberturaPluginCentro
                }],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 70,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, chartData) {
                                var idx = tooltipItem.index;
                                var nombre = chartData.labels[idx];
                                var val = idx === 0 ? peligro : alcanzan;
                                var tot = peligro + alcanzan;
                                var p = tot > 0 ? Math.round(val * 100 / tot) : 0;
                                return nombre + ": " + stockCoberturaFmt(val) + " (" + p + "%)";
                            }
                        }
                    }
                }
            });
            stockCoberturaDonas.push(chart);
        });
    }

    $(document).ready(function() {
        crearDonasStockCobertura(stockCoberturaDatos);
    });
</script>
