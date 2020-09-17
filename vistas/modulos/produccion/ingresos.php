<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Ingresos

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Ingresos</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">

        <a href="crear-ingresos">

          <button class="btn btn-primary">

            Agregar Ingresos

          </button>

        </a>
        <a href="crear-segunda">

          <button class="btn btn-danger">

            Crear segunda

          </button>

        </a>
        <button class="btn btn-outline-success btnReporteIngresoM"  style="border:green 1px solid">
                      <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Ingresos de stock  </button> 
        <button type="button" class="btn btn-default pull-right" id="daterange-btnIngresoM">
          <span>
            <i class="fa fa-calendar"></i>

            <?php

              if(isset($_GET["fechaInicial"])){

                echo $_GET["fechaInicial"]." - ".$_GET["fechaFinal"];

              }else{
              
                echo 'Rango de fecha';

              }

            ?>

          </span>

          <i class="fa fa-caret-down"></i>

        </button>
        
      </div>

      


      <div class="box-body">

        <input type="hidden" value="<?=$_SESSION["perfil"];?>" id="perfilOculto">
        
       <table class="table table-bordered table-striped dt-responsive tablaIngresoM">
         
        <thead>
         
         <tr>
           
           <th>N°</th>
           <th>Tipo</th>
           <th>Responsable</th>
           <th>Taller</th>
           <th>Documento</th> 
           <th>Total</th>
           <th>Fecha</th>
           <th style="width:150px">Acciones</th>

         </tr> 

        </thead>

       </table>

      </div>

    </div>

  </section>

</div>

<?php 
  $eliminarIngreso = new ControladorIngresos();
  $eliminarIngreso -> ctrEliminarIngreso();
?>