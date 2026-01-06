<?php

// Obtener año actual por defecto
$añoActual = date('Y');
$mesActual = date('n');

// Obtener datos del año actual inicialmente
$nombre_mes = ControladorMovimientos::ctrMesesMovPorAño($añoActual);
$venta_mes = ControladorMovimientos::ctrTotalMesVentPorAño($añoActual);
$produccion_mes = ControladorMovimientos::ctrTotalMesProdPorAño($añoActual);

$arrayMeses = array();
$arrayVentas = array();
$arrayProduccion = array();

foreach ($nombre_mes as $key => $value) {
    $mes = $value["nom_mes"];
    array_push($arrayMeses, $mes);
}

foreach ($venta_mes as $key => $value) {
    $vta = $value["total_mesV"];
    array_push($arrayVentas, $vta);
}

foreach ($produccion_mes as $key => $value) {
    $prod = $value["total_mesP"];
    array_push($arrayProduccion, $prod);
}

if (count($produccion_mes) != 0) {

?>


    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">Ventas vs Producción</h3>

        </div>

        <div class="box-body">

            <div class="chart">
                <canvas id="areaChart" style="height:350px"></canvas>
            </div>

        </div>

    </div>
<?php } ?>
<script>
    // Variable global para el gráfico de ventas vs producción (nombre único)
    var vtasProdChart = null;

    // Función para crear/actualizar el gráfico
    function crearGraficoVtasProd(datos) {
        // Verificar que el canvas exista
        if ($('#areaChart').length === 0) {
            console.error("Canvas #areaChart no encontrado");
            return;
        }

        // Verificar que los datos sean válidos
        if (!datos || !datos.meses || datos.meses.length === 0) {
            console.error("Datos inválidos para el gráfico:", datos);
            return;
        }

        // Destruir gráfico anterior si existe
        if (vtasProdChart !== null) {
            vtasProdChart.destroy();
            vtasProdChart = null;
        }

        // Get context with jQuery
        var areaChartCanvas = $('#areaChart').get(0).getContext('2d');

        var areaChartData = {
            labels: datos.meses,
            datasets: [{
                    label: 'Producción',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    borderColor: 'rgba(60,141,188,1)',
                    pointBackgroundColor: '#3b8bba',
                    pointBorderColor: 'rgba(60,141,188,1)',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(60,141,188,1)',
                    data: datos.produccion,
                    tension: 0.3
                },
                {
                    label: 'Ventas',
                    backgroundColor: 'rgba(210, 214, 222, 0.2)',
                    borderColor: 'rgba(210, 214, 222, 1)',
                    pointBackgroundColor: 'rgba(210, 214, 222, 1)',
                    pointBorderColor: '#c1c7d1',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(220,220,220,1)',
                    data: datos.ventas,
                    tension: 0.3
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

        // Create the line chart usando sintaxis moderna de Chart.js
        vtasProdChart = new Chart(areaChartCanvas, {
            type: 'line',
            data: areaChartData,
            options: areaChartOptions
        });
    }

    // Función para actualizar el gráfico según el período seleccionado
    function actualizarGraficoVtasProd(mes, año) {
        // Verificar que el canvas exista
        if ($('#areaChart').length === 0) {
            return;
        }

        // Determinar el año a usar
        var añoConsulta;
        if (mes === 'null' || mes === null || mes === '' || mes === '0') {
            añoConsulta = new Date().getFullYear();
        } else {
            añoConsulta = (año && año !== '') ? parseInt(año) : new Date().getFullYear();
        }

        $.ajax({
            url: "ajax/inicio.ajax.php",
            method: "POST",
            data: {
                mes: mes,
                año: añoConsulta,
                accion: "graficoVtasProd"
            },
            dataType: "json",
            success: function(respuesta) {
                // Verificar que la respuesta sea válida
                if (respuesta && respuesta.meses && respuesta.meses.length > 0) {
                    crearGraficoVtasProd(respuesta);
                } else {
                    console.error("Datos inválidos recibidos:", respuesta);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al actualizar el gráfico:", error);
                console.error("Respuesta del servidor:", xhr.responseText);
            }
        });
    }

    // Crear gráfico inicial con datos PHP
    $(document).ready(function() {
        // Verificar que el canvas exista antes de crear el gráfico
        if ($('#areaChart').length > 0) {
            var datosIniciales = {
                meses: <?php echo json_encode($arrayMeses, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>,
                ventas: <?php echo json_encode($arrayVentas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>,
                produccion: <?php echo json_encode($arrayProduccion, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>
            };
            
            // Verificar que los datos sean válidos
            if (datosIniciales.meses && datosIniciales.meses.length > 0) {
                crearGraficoVtasProd(datosIniciales);
            }
        }

        // Escuchar cambios en el select de mes
        $(document).on('change', '#selectMes', function() {
            var mesSeleccionado = $(this).val();
            var añoSeleccionado = $(this).find("option:selected").data("año");
            
            // Determinar el año a usar para el gráfico
            var añoParaGrafico;
            if (mesSeleccionado === 'null' || mesSeleccionado === null || mesSeleccionado === '' || mesSeleccionado === '0') {
                añoParaGrafico = new Date().getFullYear();
            } else {
                añoParaGrafico = (añoSeleccionado && añoSeleccionado !== '') ? parseInt(añoSeleccionado) : new Date().getFullYear();
            }
            
            actualizarGraficoVtasProd(mesSeleccionado, añoParaGrafico);
        });
    });
</script>