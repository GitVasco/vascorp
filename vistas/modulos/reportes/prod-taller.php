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

<style>
    #box-prod-taller > .box-header { padding: 5px 10px; }
    #box-prod-taller > .box-body { padding: 6px 10px 8px; }
    #box-prod-taller .box-title { font-size: 15px; }
    #box-prod-taller .box-title small { font-size: 10px; }
    #box-prod-taller .pt-filtros {
        margin-bottom: 6px;
        padding: 5px 8px;
        background: #f9fafb;
        border: 1px solid #e8ecf0;
        border-radius: 3px;
    }
    #box-prod-taller .pt-filtros-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px 6px;
        margin-bottom: 4px;
    }
    #box-prod-taller .pt-filtros-label {
        font-weight: 600;
        font-size: 10px;
        color: #555;
    }
    #box-prod-taller .pt-filtros-count {
        font-size: 10px;
        color: #888;
        margin-left: auto;
    }
    #box-prod-taller .pt-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 3px 4px;
    }
    #box-prod-taller .pt-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        padding: 1px 6px 1px 5px;
        font-size: 10px;
        font-weight: 600;
        color: #555;
        background: #fff;
        border: 1px solid #d2d6de;
        border-radius: 10px;
        cursor: pointer;
        user-select: none;
        transition: border-color 0.15s, box-shadow 0.15s, color 0.15s;
    }
    #box-prod-taller .pt-chip:hover {
        border-color: #aaa;
    }
    #box-prod-taller .pt-chip input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    #box-prod-taller .pt-chip-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
        background: #ccc;
    }
    #box-prod-taller .pt-chip.pt-chip--on {
        color: #222;
        border-color: #3c8dbc;
        box-shadow: 0 0 0 1px rgba(60, 141, 188, 0.25);
    }
    #box-prod-taller .pt-chip.pt-chip--off {
        opacity: 0.55;
    }
    #box-prod-taller .pt-chip.pt-chip--off .pt-chip-dot {
        background: #bbb !important;
    }
    #box-prod-taller .pt-grid {
        margin-left: -6px;
        margin-right: -6px;
    }
    #box-prod-taller .pt-grid > [class*="col-"] {
        padding-left: 6px;
        padding-right: 6px;
    }
    #box-prod-taller .pt-chart-wrap {
        position: relative;
        height: 240px;
        padding: 2px 0 0;
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 3px;
    }
    #box-prod-taller .pt-table-wrap {
        height: 240px;
        max-height: 240px;
        overflow: auto;
        border: 1px solid #e8ecf0;
        border-radius: 3px;
    }
    @media (max-width: 991px) {
        #box-prod-taller .pt-chart-wrap {
            height: 220px;
            margin-bottom: 6px;
        }
        #box-prod-taller .pt-table-wrap {
            height: 200px;
            max-height: 200px;
        }
    }
    #box-prod-taller .pt-table {
        margin: 0;
        font-size: 10px;
        white-space: nowrap;
    }
    #box-prod-taller .pt-table thead th {
        background: #3c8dbc;
        color: #fff;
        font-weight: 600;
        border-color: #367fa9 !important;
        position: sticky;
        top: 0;
        z-index: 2;
        text-align: center;
        padding: 3px 4px;
        font-size: 10px;
    }
    #box-prod-taller .pt-table thead th.pt-col-taller {
        left: 0;
        z-index: 3;
        text-align: left;
        min-width: 72px;
    }
    #box-prod-taller .pt-table tbody td {
        text-align: right;
        padding: 2px 4px;
        vertical-align: middle;
    }
    #box-prod-taller .pt-table tbody td.pt-col-taller {
        text-align: left;
        font-weight: 600;
        color: #333;
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
        box-shadow: 2px 0 4px rgba(0,0,0,0.04);
    }
    #box-prod-taller .pt-table tbody tr:nth-child(even) td.pt-col-taller {
        background: #f9f9f9;
    }
    #box-prod-taller .pt-table tbody tr:nth-child(even) td:not(.pt-col-taller) {
        background: #fafafa;
    }
    #box-prod-taller .pt-table .pt-num-zero {
        color: #bbb;
    }
</style>

<div class="box box-primary" id="box-prod-taller">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-industry"></i>
            Producción por taller
            <small>— Unidades por mes · <?php echo (int) $añoActual; ?></small>
        </h3>
    </div>
    <div class="box-body">

        <div class="pt-filtros">
            <div class="pt-filtros-bar">
                <span class="pt-filtros-label">Comparar</span>
                <button type="button" class="btn btn-xs btn-primary" id="ptSelectAll">Todos</button>
                <button type="button" class="btn btn-xs btn-default" id="ptSelectNone">Ninguno</button>
                <span class="pt-filtros-count" id="ptFiltrosCount"></span>
            </div>
            <div class="pt-chips" id="ptChipsRow">
                <?php
                foreach ($arrayTalleres as $taller => $sector) {
                    $tallerEsc = htmlspecialchars((string) $taller, ENT_QUOTES, 'UTF-8');
                    $sectorEsc = htmlspecialchars($sector, ENT_QUOTES, 'UTF-8');
                    echo '<label class="pt-chip pt-chip--on" for="sector-' . $tallerEsc . '" data-taller="' . $tallerEsc . '">';
                    echo '<input type="checkbox" class="sector-checkbox" id="sector-' . $tallerEsc . '" value="' . $tallerEsc . '" checked>';
                    echo '<span class="pt-chip-dot"></span>';
                    echo '<span class="pt-chip-text">' . $sectorEsc . '</span>';
                    echo '</label>';
                }
                ?>
            </div>
        </div>

        <div class="row pt-grid">
            <div class="col-md-7 col-sm-12">
                <div class="pt-chart-wrap">
                    <canvas id="prodtallerChart"></canvas>
                </div>
            </div>
            <div class="col-md-5 col-sm-12">
                <div class="pt-table-wrap">
            <table class="table table-bordered table-condensed pt-table" id="produccionTable">
                <thead>
                    <tr>
                        <th class="pt-col-taller">Taller</th>
                        <?php
                        foreach ($arrayMeses as $mes) {
                            $mesEsc = htmlspecialchars($mes, ENT_QUOTES, 'UTF-8');
                            $mesCorto = htmlspecialchars(mb_substr($mes, 0, 3, 'UTF-8'), ENT_QUOTES, 'UTF-8');
                            echo "<th title=\"$mesEsc\">$mesCorto</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody id="produccionTableBody">
                    <?php
                    foreach ($arrayProduccion as $taller => $producciones) {
                        $tallerEsc = htmlspecialchars((string) $taller, ENT_QUOTES, 'UTF-8');
                        $nombreEsc = htmlspecialchars($arrayTalleres[$taller], ENT_QUOTES, 'UTF-8');
                        echo "<tr class='produccion-row' data-taller='$tallerEsc'>";
                        echo "<td class='pt-col-taller'>$nombreEsc</td>";
                        foreach ($producciones as $produccion) {
                            $n = (float) $produccion;
                            $cls = $n == 0 ? " class='pt-num-zero'" : "";
                            echo "<td$cls>" . number_format($n, 0) . "</td>";
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    // Variable global para el gráfico
    var areaChart = null;
    var arrayProduccionGlobal = <?php echo json_encode($arrayProduccion); ?>;
    var arrayTalleresGlobal = <?php echo json_encode($arrayTalleres); ?>;
    var arrayMesesGlobal = <?php echo json_encode($arrayMeses); ?>;

    var ptPalette = [
        '#3c8dbc', '#00a65a', '#f39c12', '#dd4b39', '#605ca8', '#00c0ef',
        '#d81b60', '#39cccc', '#001f3f', '#ff851b', '#2ecc71', '#8e44ad',
        '#795548', '#607d8b'
    ];
    var ptColorPorTaller = {};

    function ptOrdenTalleres(talleres) {
        return Object.keys(talleres || {}).sort(function(a, b) {
            var na = (talleres[a] || '').toString();
            var nb = (talleres[b] || '').toString();
            return na.localeCompare(nb, 'es', { sensitivity: 'base' });
        });
    }

    function ptReconstruirMapaColores(talleres) {
        ptColorPorTaller = {};
        ptOrdenTalleres(talleres).forEach(function(taller, i) {
            ptColorPorTaller[taller] = ptPalette[i % ptPalette.length];
        });
    }

    function ptColorTaller(taller) {
        return ptColorPorTaller[taller] || ptPalette[0];
    }

    function ptDatasetLinea(taller, label, datosTaller) {
        var borderColor = ptColorTaller(taller);
        var nActivos = $('.sector-checkbox:checked').length;
        return {
            _tallerKey: taller,
            label: label,
            backgroundColor: 'transparent',
            borderColor: borderColor,
            pointBackgroundColor: borderColor,
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: borderColor,
            data: datosTaller,
            lineTension: 0.3,
            fill: false,
            borderWidth: nActivos > 8 ? 1.5 : 2,
            pointRadius: nActivos > 8 ? 2 : 3,
            pointHoverRadius: 4
        };
    }

    function ptOpcionesChart() {
        var nActivos = $('.sector-checkbox:checked').length;
        return {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'k';
                            }
                            return value;
                        }
                    },
                    gridLines: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            },
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var ds = data.datasets[tooltipItem.datasetIndex];
                        var val = tooltipItem.yLabel;
                        return ds.label + ': ' + number_format(val, 0);
                    },
                }
            },
            layout: {
                padding: { top: 4, right: 4, bottom: 0, left: 0 }
            },
            animation: {
                duration: nActivos > 10 ? 0 : 400
            }
        };
    }

    function ptSincronizarChipConDataset(taller, visible) {
        if (!taller) {
            return;
        }
        var $chip = $('.pt-chip[data-taller="' + taller + '"]');
        var $cb = $chip.find('.sector-checkbox');
        $cb.prop('checked', visible);
        $chip.toggleClass('pt-chip--on', visible).toggleClass('pt-chip--off', !visible);
        var color = ptColorTaller(taller);
        $chip.find('.pt-chip-dot').css('background', visible ? color : '');
    }

    function ptActualizarEstadoChips() {
        $('.pt-chip').each(function() {
            var $chip = $(this);
            var on = $chip.find('.sector-checkbox').is(':checked');
            var taller = $chip.data('taller');
            $chip.toggleClass('pt-chip--on', on).toggleClass('pt-chip--off', !on);
            $chip.find('.pt-chip-dot').css('background', on ? ptColorTaller(String(taller)) : '');
        });
        ptActualizarContador();
    }

    function ptMesCorto(nombreMes) {
        return (nombreMes || '').substring(0, 3);
    }

    function ptActualizarContador() {
        var total = $('.sector-checkbox').length;
        var n = $('.sector-checkbox:checked').length;
        $('#ptFiltrosCount').text(n + '/' + total);
    }

    function ptEnlazarEventosFiltros() {
        $('.sector-checkbox').off('change.pt').on('change.pt', function() {
            ptActualizarEstadoChips();
            updateChart();
            updateTable();
        });

        $('#ptSelectAll').off('click.pt').on('click.pt', function() {
            $('.sector-checkbox').prop('checked', true);
            ptActualizarEstadoChips();
            updateChart();
            updateTable();
        });

        $('#ptSelectNone').off('click.pt').on('click.pt', function() {
            $('.sector-checkbox').prop('checked', false);
            ptActualizarEstadoChips();
            updateChart();
            updateTable();
        });
    }

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
        ptReconstruirMapaColores(datos.talleres);

        // Preparar datasets iniciales con todos los talleres seleccionados
        var datasetsIniciales = [];
        
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

            datasetsIniciales.push(ptDatasetLinea(
                taller,
                datos.talleres[taller] || 'Taller ' + taller,
                datosTaller
            ));
        }

        var areaChartData = {
            labels: datos.meses,
            datasets: datasetsIniciales
        };

        var areaChartOptions = ptOpcionesChart();

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
            updateChart();
            updateTable();
        } catch(e) {
            console.error("Error al crear el gráfico:", e);
            return;
        }

        // Actualizar tabla
        actualizarTablaCompleta(datos);

        // Actualizar checkboxes de talleres (esto también reasignará los eventos)
        actualizarCheckboxes(datos.talleres);
        ptActualizarEstadoChips();
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

        for (var taller in arrayProduccionGlobal) {
            if (selectedSectors.includes(taller)) {
                // Verificar que los datos existan y sean válidos
                if (arrayProduccionGlobal[taller] && Array.isArray(arrayProduccionGlobal[taller])) {
                    var datosTaller = arrayProduccionGlobal[taller].map(function(val) {
                        return parseFloat(val) || 0;
                    });
                    
                    while (datosTaller.length < 12) {
                        datosTaller.push(0);
                    }
                    
                    areaChart.data.datasets.push(ptDatasetLinea(
                        taller,
                        arrayTalleresGlobal[taller] || 'Taller ' + taller,
                        datosTaller
                    ));
                }
            }
        }

        areaChart.options = ptOpcionesChart();
        areaChart.update('active');
    }

    // Función para actualizar la tabla completa
    function actualizarTablaCompleta(datos) {
        // Actualizar encabezados de meses
        var thead = $('#produccionTable thead tr');
        thead.html('<th class="pt-col-taller">Taller</th>');
        datos.meses.forEach(function(mes) {
            thead.append('<th title="' + mes + '">' + ptMesCorto(mes) + '</th>');
        });

        var tbody = $('#produccionTableBody');
        tbody.html('');
        
        for (var taller in datos.produccion) {
            var row = $('<tr class="produccion-row" data-taller="' + taller + '"></tr>');
            row.append('<td class="pt-col-taller">' + datos.talleres[taller] + '</td>');
            
            datos.produccion[taller].forEach(function(prod) {
                var n = parseFloat(prod) || 0;
                var cls = n === 0 ? ' class="pt-num-zero"' : '';
                row.append('<td' + cls + '>' + number_format(n, 0) + '</td>');
            });
            
            tbody.append(row);
        }
    }

    // Función para actualizar checkboxes de talleres
    function actualizarCheckboxes(talleres) {
        var container = $('#ptChipsRow');
        container.html('');
        ptReconstruirMapaColores(talleres);

        ptOrdenTalleres(talleres).forEach(function(taller) {
            var nombre = talleres[taller];
            var color = ptColorTaller(taller);
            var chip = $('<label class="pt-chip pt-chip--on" for="sector-' + taller + '" data-taller="' + taller + '"></label>');
            chip.append('<input type="checkbox" class="sector-checkbox" id="sector-' + taller + '" value="' + taller + '" checked>');
            chip.append('<span class="pt-chip-dot" style="background:' + color + '"></span>');
            chip.append($('<span class="pt-chip-text"></span>').text(nombre));
            container.append(chip);
        });

        ptEnlazarEventosFiltros();
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

    // Crear gráfico inicial con datos PHP
    $(document).ready(function() {
        ptReconstruirMapaColores(arrayTalleresGlobal);
        ptEnlazarEventosFiltros();
        ptActualizarEstadoChips();

        var datosIniciales = {
            meses: arrayMesesGlobal,
            produccion: arrayProduccionGlobal,
            talleres: arrayTalleresGlobal
        };
        
        if (datosIniciales.meses && datosIniciales.produccion && datosIniciales.talleres) {
            crearGrafico(datosIniciales);
        } else {
            console.error("Datos iniciales inválidos:", datosIniciales);
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
            
            actualizarGraficoProdTaller(mesSeleccionado, añoParaGrafico);
        });

    });
</script>