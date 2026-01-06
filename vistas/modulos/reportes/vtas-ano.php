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

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">Ventas por Año</h3>

    </div>

    <div class="box-body">

        <div class="chart">
            <canvas id="lineChartVenta" style="height: 400px;"></canvas>
        </div>

    </div>

</div>

<script>
    var lineChartCanvas = $('#lineChartVenta').get(0).getContext('2d')
    var lineChart = new Chart(lineChartCanvas)

    var areaChartData = {
        labels: ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'],
        datasets: [{
                label: '2024',
                fillColor: 'rgba(200, 56, 56, 0.7)',
                strokeColor: 'rgba(200, 56, 56, 0.7)',
                pointColor: 'rgba(200, 56, 56, 0.7)',
                pointStrokeColor: '#C83838',
                pointHighlightFill: '#FFFFFF',
                pointHighlightStroke: 'rgba(200, 56, 56, 0.7)',
                data: [

                    <?php

                    $conteo1 = count($arrayAno1);

                    foreach ($arrayAno1 as $nro1 => $key) {

                        if ($nro1 != $conteo1 - 1) {

                            echo "$key,";
                        } else {

                            echo "$key";
                        }
                    }


                    ?>

                ]
            },
            {
                label: '2025',
                fillColor: 'rgba(95, 214, 167, 0.8)',
                strokeColor: 'rgba(95, 214, 167, 0.8)',
                pointColor: '#5FD6A7',
                pointStrokeColor: 'rgba(95, 214, 167, 0.8)',
                pointHighlightFill: '#FFFFFF',
                pointHighlightStroke: 'rgba(95, 214, 167, 0.8)',
                data: [

                    <?php

                    $conteo2 = count($arrayAno2);

                    foreach ($arrayAno2 as $nro2 => $key) {

                        if ($nro2 != $conteo2 - 1) {

                            echo "$key,";
                        } else {

                            echo "$key";
                        }
                    }


                    ?>

                ]
            },
            {
                label: '2026',
                fillColor: 'rgba(21, 117, 146, 1)',
                strokeColor: 'rgba(21, 117, 146, 1)',
                pointColor: 'rgba(21, 117, 146, 1)',
                pointStrokeColor: '#157592',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(21, 117, 146,1)',
                data: [

                    <?php

                    $conteo3 = count($arrayAno3);

                    foreach ($arrayAno3 as $nro3 => $key) {

                        if ($nro3 != $conteo3 - 1) {

                            echo "$key,";
                        } else {

                            echo "$key";
                        }
                    }


                    ?>


                ]
            }
        ]
    }

    var areaChartOptions = {

        showScale: true,
        scaleShowGridLines: true,
        scaleGridLineColor: 'rgba(0,0,0,.05)',
        scaleGridLineWidth: 1,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        bezierCurve: true,
        bezierCurveTension: 0.3,
        pointDot: true,
        pointDotRadius: 4,
        pointDotStrokeWidth: 1,
        pointHitDetectionRadius: 20,
        datasetStroke: true,
        datasetStrokeWidth: 2,
        datasetFill: true,
        legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
        maintainAspectRatio: true,
        responsive: true,
        multiTooltipTemplate: '<%= datasetLabel %> - <%= value %> Mil',
        scaleOverride: true,
        scaleSteps: 31,
        scaleStepWidth: 100,
        scaleStartValue: 0

    }

    var lineChartOptions = areaChartOptions
    lineChartOptions.datasetFill = false
    lineChart.Line(areaChartData, lineChartOptions)
</script>