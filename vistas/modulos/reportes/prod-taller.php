<?php

// Obtener año actual por defecto
$añoActual = date('Y');
$mesActual = date('n');

// Obtener datos del año actual inicialmente
$nombre_mes = ControladorMovimientos::ctrMesesMovPorAño($añoActual);
$produccion_taller = ControladorMovimientos::ctrTotalMesProdTallerPorAño($añoActual, null);

$arrayMeses = [];
$arrayProduccion = [];
$arrayTalleres = [];

foreach ($nombre_mes as $key => $value) {
    $mes = $value["nom_mes"];
    $arrayMeses[] = $mes;
}

foreach ($produccion_taller as $value) {
    $mes = intval($value["mes"]) - 1; // restamos 1 porque los meses en PHP van de 1 a 12 y en JavaScript de 0 a 11
    $taller = $value["taller"];
    $produccion = floatval($value["produccion"]);

    if (!isset($arrayProduccion[$taller])) {
        // Crear array indexado numéricamente (0-11) para los 12 meses
        $arrayProduccion[$taller] = array();
        for ($i = 0; $i < 12; $i++) {
            $arrayProduccion[$taller][$i] = 0;
        }
    }
    
    // Asegurar que el índice del mes esté en el rango válido (0-11)
    if ($mes >= 0 && $mes < 12) {
        $arrayProduccion[$taller][$mes] = $produccion;
    }
    
    $arrayTalleres[$taller] = $value["nom_sector"];
}

// Asegurar que los arrays estén indexados numéricamente para JSON
$arrayProduccionFormateado = array();
foreach ($arrayProduccion as $taller => $datos) {
    $arrayProduccionFormateado[$taller] = array_values($datos);
}
$arrayProduccion = $arrayProduccionFormateado;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Producción Por Taller</h3>
    </div>
    <div class="box-body">

        <div class="form-inline" style="margin-bottom: 15px;">
            <label for="selectAll">Seleccionar Todos</label>
            <input type="checkbox" id="selectAll" checked>
            <div class="row">
                <?php
                foreach ($arrayTalleres as $taller => $sector) {
                    echo "<div class='col-xs-6 col-sm-4 col-md-2'><label for='sector-$taller'>$sector</label><input type='checkbox' class='sector-checkbox' id='sector-$taller' value='$taller' checked></div>";
                }
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart" style="height: 100%;">
                    <canvas id="prodtallerChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <table class="table table-bordered" id="produccionTable">
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
                    <tbody id="produccionTableBody">
                        <?php
                        foreach ($arrayProduccion as $taller => $producciones) {
                            echo "<tr class='produccion-row' data-taller='$taller'>";
                            echo "<td>{$arrayTalleres[$taller]}</td>";
                            foreach ($producciones as $produccion) {
                                $produccion = number_format($produccion, 0);
                                echo "<td>$produccion</td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    // Variable global para el gráfico
    var areaChart = null;
    var arrayProduccionGlobal = <?php echo json_encode($arrayProduccion); ?>;
    var arrayTalleresGlobal = <?php echo json_encode($arrayTalleres); ?>;
    var arrayMesesGlobal = <?php echo json_encode($arrayMeses); ?>;

    var colors = [
        'rgba(75,192,192,0.2)',
        'rgba(255,159,64,0.2)',
        'rgba(153,102,255,0.2)',
        'rgba(255,205,86,0.2)',
        'rgba(54,162,235,0.2)',
        'rgba(255,99,132,0.2)',
        'rgba(201,203,207,0.2)'
    ];
    var borderColors = [
        'rgba(75,192,192,1)',
        'rgba(255,159,64,1)',
        'rgba(153,102,255,1)',
        'rgba(255,205,86,1)',
        'rgba(54,162,235,1)',
        'rgba(255,99,132,1)',
        'rgba(201,203,207,1)'
    ];

    // Función para crear/actualizar el gráfico
    function crearGrafico(datos) {
        // Destruir gráfico anterior si existe y es válido
        if (areaChart !== null && typeof areaChart === 'object' && typeof areaChart.destroy === 'function') {
            try {
                areaChart.destroy();
            } catch(e) {
                console.warn("Error al destruir gráfico anterior:", e);
            }
            areaChart = null;
        }

        // Verificar que el canvas exista
        if ($('#prodtallerChart').length === 0) {
            console.error("Canvas #prodtallerChart no encontrado");
            return;
        }

        // Validar datos
        if (!datos || !datos.meses || !datos.produccion || !datos.talleres) {
            console.error("Datos inválidos para el gráfico:", datos);
            return;
        }

        var areaChartCanvas = $('#prodtallerChart').get(0).getContext('2d');

        // Actualizar datos globales primero
        arrayProduccionGlobal = datos.produccion;
        arrayTalleresGlobal = datos.talleres;
        arrayMesesGlobal = datos.meses;

        // Preparar datasets iniciales con todos los talleres seleccionados
        var datasetsIniciales = [];
        var index = 0;
        
        for (var taller in datos.produccion) {
            // Verificar que el taller tenga datos válidos
            if (!datos.produccion[taller] || !Array.isArray(datos.produccion[taller])) {
                console.warn("Datos inválidos para el taller:", taller, datos.produccion[taller]);
                continue;
            }

            // Convertir a array numérico si es necesario y asegurar que tenga 12 elementos
            var datosTaller = datos.produccion[taller];
            if (datosTaller.length < 12) {
                // Rellenar con ceros si faltan meses
                while (datosTaller.length < 12) {
                    datosTaller.push(0);
                }
            }
            
            // Asegurar que todos los valores sean números
            datosTaller = datosTaller.map(function(val) {
                return parseFloat(val) || 0;
            });

            var color = colors[index % colors.length];
            var borderColor = borderColors[index % borderColors.length];
            
            datasetsIniciales.push({
                label: datos.talleres[taller] || 'Taller ' + taller,
                backgroundColor: color,
                borderColor: borderColor,
                pointBackgroundColor: borderColor,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: borderColor,
                data: datosTaller,
                tension: 0.1,
                fill: false,
                borderWidth: 2
            });
            index++;
        }

        console.log("Datasets creados:", datasetsIniciales.length, "talleres");
        console.log("Meses:", datos.meses.length);

        var areaChartData = {
            labels: datos.meses,
            datasets: datasetsIniciales
        };

        var areaChartOptions = {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'top'
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return data.datasets[tooltipItem.datasetIndex].label + ' - ' + tooltipItem.yLabel;
                    }
                }
            }
        };

        // Verificar que Chart.js esté disponible
        if (typeof Chart === 'undefined') {
            console.error("Chart.js no está disponible");
            return;
        }

        // Crear el gráfico con los datasets iniciales
        try {
            areaChart = new Chart(areaChartCanvas, {
                type: 'line',
                data: areaChartData,
                options: areaChartOptions
            });
            console.log("Gráfico Producción por Taller creado exitosamente con", datasetsIniciales.length, "datasets");
            console.log("Primer dataset:", datasetsIniciales[0]);
        } catch(e) {
            console.error("Error al crear el gráfico:", e);
            return;
        }

        // Actualizar tabla
        actualizarTablaCompleta(datos);

        // Actualizar checkboxes de talleres (esto también reasignará los eventos)
        actualizarCheckboxes(datos.talleres);
        
        // Ajustar altura del gráfico a la altura de la tabla
        setTimeout(function() {
            ajustarAlturaGrafico();
        }, 100);
    }

    // Función para actualizar el gráfico según talleres seleccionados
    function updateChart() {
        if (areaChart === null) {
            console.warn("Gráfico no inicializado");
            return;
        }

        var selectedSectors = $('.sector-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (selectedSectors.length === 0) {
            console.warn("No hay talleres seleccionados");
            // Mostrar mensaje o mantener gráfico vacío
            areaChart.data.datasets = [];
            areaChart.update();
            return;
        }

        // Actualizar labels
        areaChart.data.labels = arrayMesesGlobal;
        
        // Limpiar y recrear datasets
        areaChart.data.datasets = [];

        var index = 0;
        for (var taller in arrayProduccionGlobal) {
            if (selectedSectors.includes(taller)) {
                var color = colors[index % colors.length];
                var borderColor = borderColors[index % borderColors.length];
                
                // Verificar que los datos existan y sean válidos
                if (arrayProduccionGlobal[taller] && Array.isArray(arrayProduccionGlobal[taller])) {
                    // Asegurar que todos los valores sean números y que haya 12 elementos
                    var datosTaller = arrayProduccionGlobal[taller].map(function(val) {
                        return parseFloat(val) || 0;
                    });
                    
                    // Rellenar con ceros si faltan meses
                    while (datosTaller.length < 12) {
                        datosTaller.push(0);
                    }
                    
                    areaChart.data.datasets.push({
                        label: arrayTalleresGlobal[taller] || 'Taller ' + taller,
                        backgroundColor: color,
                        borderColor: borderColor,
                        pointBackgroundColor: borderColor,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: borderColor,
                        data: datosTaller,
                        lineTension: 0.1,
                        fill: false,
                        borderWidth: 2
                    });
                    index++;
                } else {
                    console.warn("Datos inválidos para el taller:", taller, arrayProduccionGlobal[taller]);
                }
            }
        }

        console.log("Actualizando gráfico con", areaChart.data.datasets.length, "datasets");

        // Actualizar el gráfico con animación suave
        areaChart.update('active');
    }

    // Función para actualizar la tabla completa
    function actualizarTablaCompleta(datos) {
        // Actualizar encabezados de meses
        var thead = $('.table thead tr');
        thead.html('<th>Mes</th>');
        datos.meses.forEach(function(mes) {
            thead.append('<th>' + mes + '</th>');
        });

        // Actualizar cuerpo de la tabla
        var tbody = $('#produccionTableBody');
        tbody.html('');
        
        for (var taller in datos.produccion) {
            var row = $('<tr class="produccion-row" data-taller="' + taller + '"></tr>');
            row.append('<td>' + datos.talleres[taller] + '</td>');
            
            datos.produccion[taller].forEach(function(prod) {
                row.append('<td>' + number_format(prod, 0) + '</td>');
            });
            
            tbody.append(row);
        }
        
        // Ajustar altura del gráfico después de actualizar la tabla
        setTimeout(function() {
            ajustarAlturaGrafico();
        }, 100);
    }

    // Función para actualizar checkboxes de talleres
    function actualizarCheckboxes(talleres) {
        var container = $('.form-inline .row');
        container.html('');
        
        var talleresArray = Object.keys(talleres);
        talleresArray.forEach(function(taller) {
            var col = $('<div class="col-xs-6 col-sm-4 col-md-2"></div>');
            var label = $('<label for="sector-' + taller + '">' + talleres[taller] + '</label>');
            var checkbox = $('<input type="checkbox" class="sector-checkbox" id="sector-' + taller + '" value="' + taller + '" checked>');
            
            col.append(label);
            col.append(checkbox);
            container.append(col);
        });

        // Reasignar eventos
        $('.sector-checkbox').off('change').on('change', function() {
            updateChart();
            updateTable();
        });

        // Reasignar evento selectAll
        $('#selectAll').off('change').on('change', function() {
            var checked = this.checked;
            $('.sector-checkbox').prop('checked', checked);
            updateChart();
            updateTable();
        });
    }

    function updateTable() {
        var selectedSectors = $('.sector-checkbox:checked').map(function() {
            return this.value;
        }).get();

        $('.produccion-row').each(function() {
            var taller = $(this).data('taller');
            if (selectedSectors.includes(taller)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Ajustar altura del gráfico después de actualizar la tabla
        setTimeout(function() {
            ajustarAlturaGrafico();
        }, 100);
    }

    // Función helper para formatear números
    function number_format(number, decimals) {
        number = parseFloat(number);
        return number.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Función para actualizar el gráfico según el período seleccionado
    function actualizarGraficoProdTaller(mes, año) {
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
                accion: "graficoProdTaller"
            },
            dataType: "json",
            success: function(respuesta) {
                console.log("Datos recibidos del servidor:", respuesta);
                if (respuesta && respuesta.meses && respuesta.produccion && respuesta.talleres) {
                    console.log("Talleres encontrados:", Object.keys(respuesta.talleres).length);
                    console.log("Meses:", respuesta.meses.length);
                    crearGrafico(respuesta);
                } else {
                    console.error("Respuesta inválida del servidor:", respuesta);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al actualizar el gráfico:", error);
                console.error("Respuesta del servidor:", xhr.responseText);
            }
        });
    }

    // Función para ajustar la altura del gráfico a la altura de la tabla
    function ajustarAlturaGrafico() {
        var tabla = $('#produccionTable');
        var chartContainer = $('.chart');
        if (tabla.length > 0 && chartContainer.length > 0) {
            var alturaTabla = tabla.outerHeight();
            chartContainer.css('height', alturaTabla + 'px');
            $('#prodtallerChart').css('height', alturaTabla + 'px');
            // Actualizar el gráfico si existe
            if (areaChart !== null) {
                areaChart.resize();
            }
        }
    }

    // Crear gráfico inicial con datos PHP
    $(document).ready(function() {
        console.log("Inicializando gráfico con datos PHP");
        console.log("Talleres disponibles:", Object.keys(arrayTalleresGlobal).length);
        console.log("Meses:", arrayMesesGlobal.length);
        
        var datosIniciales = {
            meses: arrayMesesGlobal,
            produccion: arrayProduccionGlobal,
            talleres: arrayTalleresGlobal
        };
        
        // Verificar que los datos estén correctos
        if (datosIniciales.meses && datosIniciales.produccion && datosIniciales.talleres) {
            crearGrafico(datosIniciales);
            // Ajustar altura después de crear el gráfico
            setTimeout(function() {
                ajustarAlturaGrafico();
            }, 100);
        } else {
            console.error("Datos iniciales inválidos:", datosIniciales);
        }
        
        // Ajustar altura cuando se actualice la tabla
        $(document).on('DOMSubtreeModified', '#produccionTableBody', function() {
            setTimeout(function() {
                ajustarAlturaGrafico();
            }, 100);
        });

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
            
            actualizarGraficoProdTaller(mesSeleccionado, añoParaGrafico);
        });

        $('#selectAll').on('change', function() {
            var checked = this.checked;
            $('.sector-checkbox').prop('checked', checked);
            updateChart();
            updateTable();
        });
    });
</script>