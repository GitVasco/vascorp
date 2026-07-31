<div class="content-wrapper">

  <section class="content-header">

    <h1>

    <?php
    
    $inicio = $_GET["inicio"];
    $fin = $_GET["fin"];

    echo 'Pagos desde '.$inicio.' hasta '.$fin;
    
    ?>

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Pagos</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">
      <div class="box-header with-border">
          <div class="col-lg-2">
            
            <select type="text" class="form-control input-lg " name="selectSectorTra" id="selectSectorTra" >
                <option value="">Seleccionar sector</option>
                <?php
                $item = null;
                $valor=null;
                $sectores=ControladorSectores::ctrMostrarSectores($item,$valor);
                foreach ($sectores as $key => $value) {
                  if($value["cod_sector"] == 'T1' || $value["cod_sector"] == 'T3'){
                    echo"<option value='".$value['cod_sector']."'>".$value["cod_sector"]." - ".$value["nom_sector"]."</option>";
                  }
                }
                ?>
            </select>
          </div>
          <div class="col-lg-2">
            <button class="btn btn-primary btnLimpiarSectorTra"  name="btnLimpiarSectorTra" inicio="<?php echo $_GET["inicio"]?>" fin="<?php echo $_GET["fin"]?>" quincena="<?php echo $_GET["quincena"]?>" id="<?php echo $_GET["id"]?>"><i class="fa fa-refresh"></i> Limpiar</button>
          </div> 
        <button class="btn btn-outline-success btnReportePago" modelo="" style="border:green 1px solid"  inicio=<?php echo $_GET["inicio"]?> fin=<?php echo $_GET["fin"]?> quincena=<?php echo $_GET["nquincena"]?> id=<?php echo $_GET["id"]?>>
                    <img src="vistas/img/plantilla/excel.png" width="20px" > Reporte Pagos  </button>
      </div>
      <div class="box-body">

        <input type="hidden" value="<?=$_SESSION["perfil"];?>" id="perfilOculto">
        
       <table class="table table-bordered table-striped dt-responsive tablaPagos" id="totalPagos" width="100%">
         
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
                <th>16</th>
                <th>Total S/</th>
                <th>Bono S/</th>
                <th>Total a pagar</th>';

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
                <th>1</th>
                <th>Total S/</th>
                <th>Bono S/</th>
                <th>Total a pagar</th>';

         }
         
         ?>
          
         </tr> 

        </thead>

        <tbody> 
 
        </tbody>
        <tfoot>
        <tr>
         <th></th>
         <th>TOTAL:</th>
         <?php
         // 21 columnas de días + 3 totales = 24; con cod+trab = 26 (igual que thead)
         for ($i = 0; $i < 24; $i++) {
           echo '<th></th>';
         }
         ?>
        </tr>
        </tfoot>
       </table>
      </div>

    </div>

  </section>

</div>

<script>
window.document.title = "Pagos"
</script>