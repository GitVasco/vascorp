<?php

// Obtener año actual por defecto
$añoActual = date('Y');
$mesActual = date('n');

// Obtener datos del año actual inicialmente
$nombre_mes = ControladorMovimientos::ctrMesesMovPorAño($añoActual);
$produccion_mes = ControladorMovimientos::ctrTotalMesProdPorAño($añoActual);
$corte_mes = ControladorMovimientos::ctrTotalMesCortePorAño($añoActual);

$arrayMeses = [];
$arrayProduccion = [];
$arrayCorte = [];

foreach ($nombre_mes as $key => $value) {
    $mes = $value["nom_mes"];
    $arrayMeses[] = $mes;
}

foreach ($produccion_mes as $value) {
    $arrayProduccion[] = $value["total_mesP"];
}

foreach ($corte_mes as $value) {
    $arrayCorte[] = $value["total_mesC"];
}

?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Corte vs Producción</h3>
    </div>
    <div class="box-body">
        <div class="chart">
            <canvas id="corprodChart" style="height:400px"></canvas>
        </div>

        <table class="table table-bordered" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Mes</th>
                    <?php
                    foreach ($arrayMeses as $mes) {
                        echo "<th>$mes</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="background-color: rgba(75,192,192,0.2);">Producción</td>
                    <?php
                    foreach ($arrayProduccion as $produccion) {
                        $produccion = number_format($produccion, 0);
                        echo "<td>$produccion</td>";
                    }
                    ?>
                </tr>
                <tr>
                    <td style="background-color: rgba(255,159,64,0.2);">Corte</td>
                    <?php
                    foreach ($arrayCorte as $corte) {
                        $corte = number_format($corte, 0);
                        echo "<td>$corte</td>";
                    }
                    ?>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<script>
    // Variable global para el gráfico
    var corprodChart = null;

    // Función para crear/actualizar el gráfico
    function actualizarGraficoCorteProd(mes, año) {
        // Determinar el año a usar
        var añoConsulta;
        if (mes === 'null' || mes === null || mes === '' || mes === '0') {
            // Si es mes actual, usar año actual
            añoConsulta = new Date().getFullYear();
        } else {
            // Si hay año seleccionado, usarlo; si no, usar año actual
            añoConsulta = (año && año !== '') ? parseInt(año) : new Date().getFullYear();
        }

        $.ajax({
            url: "ajax/inicio.ajax.php",
            method: "POST",
            data: {
                mes: mes,
                año: añoConsulta,
                accion: "graficoCorteProd"
            },
            dataType: "json",
            success: function(respuesta) {
                // Destruir gráfico anterior si existe
                if (corprodChart !== null) {
                    corprodChart.destroy();
                }

                // Get context with jQuery
                var areaChartCanvas = $('#corprodChart').get(0).getContext('2d');

                var areaChartData = {
                    labels: respuesta.meses,
                    datasets: [{
                            label: 'Producción',
                            backgroundColor: 'rgba(75,192,192,0.2)',
                            borderColor: 'rgba(75,192,192,1)',
                            pointBackgroundColor: 'rgba(75,192,192,1)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(75,192,192,1)',
                            data: respuesta.produccion
                        },
                        {
                            label: 'Corte',
                            backgroundColor: 'rgba(255,159,64,0.2)',
                            borderColor: 'rgba(255,159,64,1)',
                            pointBackgroundColor: 'rgba(255,159,64,1)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(255,159,64,1)',
                            data: respuesta.corte
                        }
                    ]
                };

                var areaChartOptions = {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ' - ' + context.raw;
                                }
                            }
                        }
                    }
                };

                // Crear el gráfico
                corprodChart = new Chart(areaChartCanvas, {
                    type: 'line',
                    data: areaChartData,
                    options: areaChartOptions
                });

                // Actualizar la tabla
                actualizarTablaCorteProd(respuesta);
            },
            error: function(xhr, status, error) {
                console.error("Error al actualizar el gráfico:", error);
            }
        });
    }

    // Función para actualizar la tabla
    function actualizarTablaCorteProd(datos) {
        // Actualizar encabezados de meses
        var thead = $('.table thead tr');
        thead.html('<th>Mes</th>');
        datos.meses.forEach(function(mes) {
            thead.append('<th>' + mes + '</th>');
        });

        // Actualizar fila de producción
        var filaProd = $('.table tbody tr').eq(0);
        filaProd.html('<td style="background-color: rgba(75,192,192,0.2);">Producción</td>');
        datos.produccion.forEach(function(prod) {
            filaProd.append('<td>' + number_format(prod, 0) + '</td>');
        });

        // Actualizar fila de corte
        var filaCorte = $('.table tbody tr').eq(1);
        filaCorte.html('<td style="background-color: rgba(255,159,64,0.2);">Corte</td>');
        datos.corte.forEach(function(corte) {
            filaCorte.append('<td>' + number_format(corte, 0) + '</td>');
        });
    }

    // Función helper para formatear números
    function number_format(number, decimals) {
        number = parseFloat(number);
        return number.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Crear gráfico inicial con datos PHP
    $(document).ready(function() {
        var areaChartCanvas = $('#corprodChart').get(0).getContext('2d');

        var areaChartData = {
            labels: [
                <?php
                $conteoM = count($arrayMeses);
                foreach ($arrayMeses as $numeroM => $key) {
                    if ($numeroM != $conteoM - 1) {
                        echo "'$key',";
                    } else {
                        echo "'$key'";
                    }
                }
                ?>
            ],
            datasets: [{
                    label: 'Producción',
                    backgroundColor: 'rgba(75,192,192,0.2)',
                    borderColor: 'rgba(75,192,192,1)',
                    pointBackgroundColor: 'rgba(75,192,192,1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(75,192,192,1)',
                    data: [
                        <?php
                        $conteoP = count($arrayProduccion);
                        foreach ($arrayProduccion as $numeroP => $key) {
                            if ($numeroP != $conteoP - 1) {
                                echo "$key,";
                            } else {
                                echo "$key";
                            }
                        }
                        ?>
                    ]
                },
                {
                    label: 'Corte',
                    backgroundColor: 'rgba(255,159,64,0.2)',
                    borderColor: 'rgba(255,159,64,1)',
                    pointBackgroundColor: 'rgba(255,159,64,1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(255,159,64,1)',
                    data: [
                        <?php
                        $conteoV = count($arrayCorte);
                        foreach ($arrayCorte as $numeroV => $key) {
                            if ($numeroV != $conteoV - 1) {
                                echo "$key,";
                            } else {
                                echo "$key";
                            }
                        }
                        ?>
                    ]
                }
            ]
        };

        var areaChartOptions = {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ' - ' + context.raw;
                        }
                    }
                }
            }
        };

        // Crear el gráfico inicial
        corprodChart = new Chart(areaChartCanvas, {
            type: 'line',
            data: areaChartData,
            options: areaChartOptions
        });

        // Escuchar cambios en el select de mes
        $(document).on('change', '#selectMes', function() {
            var mesSeleccionado = $(this).val();
            var añoSeleccionado = $(this).find("option:selected").data("año");
            
            // Determinar el año a usar para el gráfico
            var añoParaGrafico;
            if (mesSeleccionado === 'null' || mesSeleccionado === null || mesSeleccionado === '' || mesSeleccionado === '0') {
                // Si es mes actual, usar año actual
                añoParaGrafico = new Date().getFullYear();
            } else {
                // Si hay año seleccionado, usarlo; si no, usar año actual
                añoParaGrafico = (añoSeleccionado && añoSeleccionado !== '') ? parseInt(añoSeleccionado) : new Date().getFullYear();
            }
            
            actualizarGraficoCorteProd(mesSeleccionado, añoParaGrafico);
        });
    });
</script>