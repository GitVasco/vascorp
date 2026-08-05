<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Eficiencia Global

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Eficiencia</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

        <div class="box-header with-border">

            <div class="col-lg-12">

                <?php
                $internosEG = ControladorSectores::ctrSectoresPorTipo(0);
                foreach ($internosEG as $sec) {
                    echo '<button type="button" class="btn btn-default btnTallerEG" value="'
                        . htmlspecialchars($sec["cod_sector"]) . '">'
                        . htmlspecialchars($sec["cod_sector"] . "-" . $sec["nom_sector"])
                        . '</button> ';
                }
                ?>
                <button type="button" class="btn btn-default btnTotT" id="btnTotT" name="btnTotT" value="null">
                    Todo
                </button>

            </div>

        </div>    

        <div class="box-body">
        
        <table class="table table-bordered table-striped dt-responsive tablaEficienciaGlobal" width="100%">
            
        <thead>
            
            <tr>

                <th>S.</th>
                <th>CT</th>
                <th>Trabajador</th>
                <th>01-Ene</th>
                <th>02-Ene</th>
                <th>01-Feb</th>
                <th>02-Feb</th>
                <th>01-Mar</th>
                <th>02-Mar</th>
                <th>01-Abr</th>
                <th>02-Abr</th>
                <th>01-May</th>
                <th>02-May</th>
                <th>01-Jun</th>
                <th>02-Jun</th>
                <th>01-Jul</th>
                <th>02-Jul</th>
                <th>01-Ago</th>
                <th>02-Ago</th>
                <th>01-Sep</th>
                <th>02-Sep</th>
                <th>01-Oct</th>
                <th>02-Oct</th>
                <th>01-Nov</th>
                <th>02-Nov</th>
                <th>01-Dic</th>
                <th>02-Dic</th>
                <th>Promedio</th>
            
            </tr> 

        </thead>

        <tbody> 

        </tbody>

        </table>

        </div>

    </div>

  </section>

</div>

<script>
window.document.title = "Eficiencia Global"
</script>