<?php
$filasPagos = ControladorMovimientos::ctrTotalesSolesPagos();

$arrayAno1 = array_fill(0, 12, 0);
$arrayAno2 = array_fill(0, 12, 0);
$arrayAno3 = array_fill(0, 12, 0);

foreach ($filasPagos as $value) {

    $idx = ((int) $value["codigo"]) - 1;

    if ($idx < 0 || $idx > 11) {
        continue;
    }

    $arrayAno1[$idx] = $value["ano1"] !== null ? $value["ano1"] : 0;
    $arrayAno2[$idx] = $value["ano2"] !== null ? $value["ano2"] : 0;
    $arrayAno3[$idx] = $value["ano3"] !== null ? $value["ano3"] : 0;
}
?>

<div class="box box-primary" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">

    <div class="box-header with-border" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 8px 8px 0 0; padding: 15px 20px;">

        <h3 class="box-title" style="font-weight: 600; font-size: 18px; margin: 0;">
            <i class="fa fa-money" style="margin-right: 8px;"></i>Pagos por Año
        </h3>

    </div>

    <div class="box-body" style="padding: 20px; background: #f8f9fa;">

        <div class="chart" style="position: relative; height: 450px;">
            <canvas id="lineChart"></canvas>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Preparar datos
    var pagosData2024 = [
        <?php
        $conteo1 = count($arrayAno1);
        foreach ($arrayAno1 as $nro1 => $key) {
            echo ($nro1 != $conteo1 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    var pagosData2025 = [
        <?php
        $conteo2 = count($arrayAno2);
        foreach ($arrayAno2 as $nro2 => $key) {
            echo ($nro2 != $conteo2 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    var pagosData2026 = [
        <?php
        $conteo3 = count($arrayAno3);
        foreach ($arrayAno3 as $nro3 => $key) {
            echo ($nro3 != $conteo3 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    // Crear gradientes
    var ctxPagos = document.getElementById('lineChart').getContext('2d');
    
    var gradient2024Pagos = ctxPagos.createLinearGradient(0, 0, 0, 400);
    gradient2024Pagos.addColorStop(0, 'rgba(255, 87, 108, 0.3)');
    gradient2024Pagos.addColorStop(1, 'rgba(255, 87, 108, 0.05)');

    var gradient2025Pagos = ctxPagos.createLinearGradient(0, 0, 0, 400);
    gradient2025Pagos.addColorStop(0, 'rgba(255, 193, 7, 0.3)');
    gradient2025Pagos.addColorStop(1, 'rgba(255, 193, 7, 0.05)');

    var gradient2026Pagos = ctxPagos.createLinearGradient(0, 0, 0, 400);
    gradient2026Pagos.addColorStop(0, 'rgba(108, 117, 125, 0.3)');
    gradient2026Pagos.addColorStop(1, 'rgba(108, 117, 125, 0.05)');

    // Configuración del gráfico
    var chartPagos = new Chart(ctxPagos, {
        type: 'line',
        data: {
            labels: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'],
            datasets: [{
                label: '2024',
                data: pagosData2024,
                borderColor: '#ff576c',
                backgroundColor: gradient2024Pagos,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#ff576c',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#ff576c',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(255, 87, 108, 0.3)'
            }, {
                label: '2025',
                data: pagosData2025,
                borderColor: '#ffc107',
                backgroundColor: gradient2025Pagos,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#ffc107',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#ffc107',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(255, 193, 7, 0.3)'
            }, {
                label: '2026',
                data: pagosData2026,
                borderColor: '#6c757d',
                backgroundColor: gradient2026Pagos,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6c757d',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#6c757d',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(108, 117, 125, 0.3)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 13,
                            weight: '600',
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                        },
                        color: '#495057'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'S/ ' + new Intl.NumberFormat('es-PE', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(context.parsed.y) + ' Mil';
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                        },
                        color: '#6c757d',
                        callback: function(value) {
                            return 'S/ ' + new Intl.NumberFormat('es-PE', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value) + 'K';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '600',
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                        },
                        color: '#495057'
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
</script>