<?php
/* 
    todo: sacamos los totales del 1er año
    */

$ano1 = ControladorMovimientos::ctrTotalesSolesVenta();

/* var_dump("ano1", $ano1); */

$arrayAno1 = array();

foreach ($ano1 as $key => $value) {

    $a1 = $value["ano1"];

    array_push($arrayAno1, $a1);
}

/* var_dump("arrayAno1", $arrayAno1); */

/* 
    todo: sacamos los totales del 2do año
    */

$ano2 = ControladorMovimientos::ctrTotalesSolesVenta();

/* var_dump("ano2", $ano2); */

$arrayAno2 = array();

foreach ($ano2 as $key => $value) {

    $a2 = $value["ano2"];

    array_push($arrayAno2, $a2);
}

/* var_dump("arrayAno2", $arrayAno2); */

/* 
    todo: sacamos los totales del 2do año
    */

$ano3 = ControladorMovimientos::ctrTotalesSolesVenta();

/* var_dump("ano3", $ano3); */

$arrayAno3 = array();

foreach ($ano3 as $key => $value) {

    $a2 = $value["ano3"];

    array_push($arrayAno3, $a2);
}

/* var_dump("arrayAno3", $arrayAno3);  */


?>

<div class="box box-primary" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">

    <div class="box-header with-border" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0; padding: 15px 20px;">

        <h3 class="box-title" style="font-weight: 600; font-size: 18px; margin: 0;">
            <i class="fa fa-line-chart" style="margin-right: 8px;"></i>Ventas por Año
        </h3>

    </div>

    <div class="box-body" style="padding: 20px; background: #f8f9fa;">

        <div class="chart" style="position: relative; height: 450px;">
            <canvas id="lineChartVenta"></canvas>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Preparar datos
    var ventasData2024 = [
        <?php
        $conteo1 = count($arrayAno1);
        foreach ($arrayAno1 as $nro1 => $key) {
            echo ($nro1 != $conteo1 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    var ventasData2025 = [
        <?php
        $conteo2 = count($arrayAno2);
        foreach ($arrayAno2 as $nro2 => $key) {
            echo ($nro2 != $conteo2 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    var ventasData2026 = [
        <?php
        $conteo3 = count($arrayAno3);
        foreach ($arrayAno3 as $nro3 => $key) {
            echo ($nro3 != $conteo3 - 1) ? "$key," : "$key";
        }
        ?>
    ];

    // Crear gradientes
    var ctx = document.getElementById('lineChartVenta').getContext('2d');
    
    var gradient2024 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient2024.addColorStop(0, 'rgba(220, 53, 69, 0.3)');
    gradient2024.addColorStop(1, 'rgba(220, 53, 69, 0.05)');

    var gradient2025 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient2025.addColorStop(0, 'rgba(40, 167, 69, 0.3)');
    gradient2025.addColorStop(1, 'rgba(40, 167, 69, 0.05)');

    var gradient2026 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient2026.addColorStop(0, 'rgba(0, 123, 255, 0.3)');
    gradient2026.addColorStop(1, 'rgba(0, 123, 255, 0.05)');

    // Configuración del gráfico
    var chartVentas = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'],
            datasets: [{
                label: '2024',
                data: ventasData2024,
                borderColor: '#dc3545',
                backgroundColor: gradient2024,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#dc3545',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#dc3545',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(220, 53, 69, 0.3)'
            }, {
                label: '2025',
                data: ventasData2025,
                borderColor: '#28a745',
                backgroundColor: gradient2025,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#28a745',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#28a745',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(40, 167, 69, 0.3)'
            }, {
                label: '2026',
                data: ventasData2026,
                borderColor: '#007bff',
                backgroundColor: gradient2026,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#007bff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#007bff',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(0, 123, 255, 0.3)'
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