<div class="content-wrapper">

  <section class="content-header">

    <h1>

    <?php
    
    $inicio = $_GET["inicio"];
    $fin = $_GET["fin"];

    echo 'Eficiencia desde '.$inicio.' hasta '.$fin;
    
    ?>

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Eficiencia</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">
      <div class="box-header with-border">
      <div class="col-lg-2">
            
            <select type="text" class="form-control input-lg " name="selectSectorEfi" id="selectSectorEfi" >
                <option value="">Seleccionar sector</option>
                <?php
                $sectores = ControladorSectores::ctrSectoresPorTipo(0);
                foreach ($sectores as $key => $value) {
                    echo "<option value='" . htmlspecialchars($value["cod_sector"]) . "'>"
                        . htmlspecialchars($value["cod_sector"] . " - " . $value["nom_sector"])
                        . "</option>";
                }
                ?>
            </select>
          </div>
          <div class="col-lg-2">
            <button class="btn btn-primary btnLimpiarSectorEfi"  name="btnLimpiarSectorEfi" inicio="<?php echo $_GET["inicio"]?>" fin="<?php echo $_GET["fin"]?>" nquincena="<?php echo $_GET["nquincena"]?>" id="<?php echo $_GET["id"]?>"><i class="fa fa-refresh"></i> Limpiar</button>
          </div> 
          <div class="pull-right">
            <button class="btn btn-outline-success btnReporteEficiencia" modelo="" style="border:green 1px solid"  inicio=<?php echo $_GET["inicio"]?> fin=<?php echo $_GET["fin"]?> quincena=<?php echo $_GET["nquincena"]?> id=<?php echo $_GET["id"]?>>
                    <img src="vistas/img/plantilla/excel.png" width="20px" > Reporte Eficiencia  </button>
          </div>
        
      </div>
      <div class="box-body">

        <input type="hidden" value="<?=$_SESSION["perfil"];?>" id="perfilOculto">
        
       <table class="table table-bordered table-striped dt-responsive tablaEficiencia" width="100%">
         
        <thead>
         
         <tr>
         <?php

         $nquincena = $_GET["nquincena"];
         //var_dump($nquincena);

         if($nquincena == "1"){

          echo '<th>Cod. Trab.</th>
                <th>Trabajador</th>
                <th>27</th>
                <th>28</th>
                <th>29</th>
                <th>30</th>
                <th>31</th>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
                <th>9</th>
                <th>10</th>
                <th>11</th>
                <th>12</th>
                <th>13</th>
                <th>14</th>
                <th>15</th>
                <th>16</th>';

         }else{

          echo '<th>Cod. Trab.</th>
                <th>Trabajador</th>
                <th>12</th>
                <th>13</th>
                <th>14</th>
                <th>15</th>
                <th>16</th>
                <th>17</th>
                <th>18</th>
                <th>19</th>
                <th>20</th>
                <th>21</th>
                <th>22</th>
                <th>23</th>
                <th>24</th>
                <th>25</th>
                <th>26</th>
                <th>27</th>
                <th>28</th>
                <th>29</th>
                <th>30</th>
                <th>31</th>
                <th>1</th>';

         }
         
         ?>
          
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
window.document.title = "Eficiencias"
</script>