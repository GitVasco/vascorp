<?php

$valor = 1;

$modelos = ControladorMovimientos::ctrMovMes($valor);

#var_dump("modelos",$modelos);

$colores = array("red", "green", "yellow", "aqua", "purple", "blue", "cyan", "magenta", "orange", "gold");

$sumaUnd = ControladorMovimientos::ctrSumaUnd($valor);

#var_dump("sumaUnd", $sumaUnd["sumaUnd"]);

if (count($modelos) != 0) {

?>

  <!--=====================================
MODELOS MÁS VENDIDOS
======================================-->

  <div class="box box-default">

    <div class="box-header with-border">

      <h3 class="box-title">Modelos más Vendidos del Mes Pasado</h3>

    </div>

    <div class="box-body">

      <div class="row">

        <div class="col-md-7">

          <div class="chart-responsive">

            <canvas id="pieChartP" height="150"></canvas>

          </div>

        </div>

        <div class="col-md-5">

          <ul class="chart-legend clearfix">

            <?php

            $totalModelos = count($modelos);
            $maxItems = min(10, $totalModelos); // Solo usar los elementos que existen, máximo 10

            for ($i = 0; $i < $maxItems; $i++) {
              if (isset($modelos[$i]) && isset($modelos[$i]["modelo"])) {
                echo ' <li><i class="fa fa-circle-o text-' . $colores[$i] . '"></i> ' . htmlspecialchars($modelos[$i]["modelo"]) . '</li>';
              }
            }

            ?>


          </ul>

        </div>

      </div>

    </div>

    <div class="box-footer no-padding">

      <ul class="nav nav-pills nav-stacked">

        <?php

        $totalModelos = count($modelos);
        $maxItems = min(5, $totalModelos); // Solo usar los elementos que existen, máximo 5

        for ($i = 0; $i < $maxItems; $i++) {
          if (isset($modelos[$i]) && isset($modelos[$i]["modelo"]) && isset($modelos[$i]["ventas"]) && isset($sumaUnd["sumaUnd"]) && $sumaUnd["sumaUnd"] > 0) {
            $porcentaje = ceil($modelos[$i]["ventas"] * 100 / $sumaUnd["sumaUnd"]);
            echo '<li>
						 
                      <a>

                      ' . htmlspecialchars($modelos[$i]["modelo"]) . '

                      <span class="pull-right text-' . $colores[$i] . '">   
                      ' . $porcentaje . '%
                      </span>
                        
                  </a>

                    </li>';
          }
        }

        ?>


      </ul>

    </div>

  </div>
<?php } ?>
<script>
  // -------------
  // - PIE CHART -
  // -------------
  // Verificar que el canvas exista y que haya datos
  if ($('#pieChartP').length > 0 && <?php echo count($modelos); ?> > 0) {
    // Get context with jQuery - using jQuery's .get() method.
    var pieChartCanvas = $('#pieChartP').get(0).getContext('2d');
    var pieChart = new Chart(pieChartCanvas);
    var PieData = [

      <?php

      $totalModelos = count($modelos);
      $maxItems = min(10, $totalModelos); // Solo usar los elementos que existen, máximo 10

      for ($i = 0; $i < $maxItems; $i++) {
        if (isset($modelos[$i]) && isset($modelos[$i]["ventas"]) && isset($modelos[$i]["modelo"])) {
          $ventas = floatval($modelos[$i]["ventas"]);
          $modelo = addslashes($modelos[$i]["modelo"]);
          $color = $colores[$i];
          
          echo "{
          value    : " . $ventas . ",
          color    : '" . $color . "',
          highlight: '" . $color . "',
          label    : '" . $modelo . "'
        }";
          
          // Agregar coma solo si no es el último elemento
          if ($i < $maxItems - 1) {
            echo ",";
          }
        }
      }

      ?>
    ];
    var pieOptions = {

      segmentShowStroke: true,
      segmentStrokeColor: '#fff',
      segmentStrokeWidth: 1,
      percentageInnerCutout: 50, // This is 0 for Pie charts
      animationSteps: 100,
      animationEasing: 'easeOutBounce',
      animateRotate: true,
      animateScale: false,
      responsive: true,
      maintainAspectRatio: false,
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
      tooltipTemplate: '<%=label %> - <%=value%>'
    };
    /* pieChart.Doughnut(PieData, pieOptions); */
  }
</script>