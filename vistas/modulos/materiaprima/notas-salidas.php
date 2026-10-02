<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Notas de Salidas

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Notas de Salidas</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">

        <a href="crear-nota-salida">

          <button class="btn btn-primary">

            Agregar Nota de Salida

          </button>

        </a>

        <button class="btn btn-info btnUnirNotaSalida" data-toggle='modal' data-target='#modalUnirNotaSalida'><i class="fa fa-random"></i>

            Unir Saldo

          </button>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnNotasSalidas">
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
        <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">
       <table class="table table-bordered table-striped dt-responsive tablaNotasSalidas" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Tipo</th>
           <th>Serie</th>
           <th>Numero</th>
           <th>Fec. Emisión</th> 
           <th>Razon Social</th>
           <th>Almacen de Salida</th>
           <th>Responsable</th>
           <th>Estado</th>
           <th>Guía</th>
           <th style="width:130px">Acciones</th>

         </tr> 

        </thead>

       </table>

      </div>

    </div>

  </section>

</div>



<!--=====================================
MODAL VIZUALIZAR NOTA DE SALIDA
======================================-->

<div id="modalVizualizarNotaSalida" class="modal fade" role="dialog">

  <div class="modal-dialog" style="width: 70% !important;">

    <div class="modal-content">


        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">VISUALIZAR NOTA DE SALIDA</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <div class="form-group col-lg-3">
              
              <label>CODIGO</label>

              <div class="input-group">

              <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text" class="form-control input-sm" name="codigo" id="codigo"  readonly>

              </div>

            </div>

            <div class="form-group col-lg-3">
              
              <label>FECHA</label>

              <div class="input-group">

              <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 

                <input type="text" class="form-control input-sm" name="fecha" id="fecha"  readonly>

              </div>

            </div>

            <!-- ENTRADA PARA LA GUIA-->
            
            <div class="form-group col-lg-3">

              <label>ALMACEN</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-truck"></i></span> 

                <select type="text" class="form-control input-sm" name="almacen" id="almacen"  disabled>
                <option value="">SELECCIONAR TIPO ALMACEN</option>
                <?php

                  $almacen = ControladorNotasSalidas::ctrMostrarTipoAlmacen();

                  foreach ($almacen as $key => $value) {

                    echo '<option value="'.$value["id_almacen"].'">'.$value["id_almacen"]." - ".$value["almacen"].'</option>';

                  }

                  ?>
                </select>
              </div>

            </div> 

            <!-- ENTRADA PARA LA FECHA-->
            
            <div class="form-group col-lg-3">

              <label>RUC</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span> 

                <input type="number" class="form-control input-sm" name="ruc" id="ruc"   readonly >

              </div>

            </div>   
 
            <!-- ENTRADA PARA LA RESPONSABLE-->
            
            <div class="form-group col-lg-3">

              <label>CLIENTE</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-users"></i></span> 

                  <select  class="form-control input-sm" name="cliente" id="cliente" disabled>
                      <option value="">SELECCIONAR CLIENTE</option>
                        <?php

                        $cliente = ControladorNotasSalidas::ctrMostrarClientesNotas();

                        foreach ($cliente as $key => $value) {

                          echo '<option value="'.$value["Ruc"].'">'.$value["CodCli"]." - ".$value["RazCli"].'</option>';

                        }

                        ?>
                    </select>

              </div>

            </div>            
   
            
            <!-- ENTRADA PARA LA CANTIDAD-->
            
            <div class="form-group col-lg-3">

              <label>MOTIVO</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <select  class="form-control input-sm " name="motivo" id="motivo" data-live-search="true" disabled>
                    <option value="">SELECCIONAR MOTIVO</option>
                      <?php

                      $motivo = ControladorNotasSalidas::ctrMostrarMotivoNota();

                      foreach ($motivo as $key => $value) {

                        echo '<option value="'.$value["Cod_Argumento"].'">'.$value["Des_Larga"].'</option>';

                      }

                      ?>
                  </select>

              </div>

            </div>
            
            <!-- ENTRADA PARA EL ESTADO-->
            
            <div class="form-group col-lg-6">

              <label for="">OBSERVACION</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <input type="text" class="form-control input-sm" name="observacion" id="observacion"  readonly>

              </div>

            </div>
            <div class="form-group col-lg-12">
              <table class="table table-hover table-striped tablaDetalleNotaSalida" width="100%">
                <thead>
                
                  <th class="text-center">Item</th>
                  <th class="text-center">Cod.Producto</th>
                  <th class="text-center">Cod.Fabrica</th>
                  <th class="text-center">Descripcion</th>
                  <th class="text-center">Color</th>
                  <th class="text-center">Costo</th>
                  <th class="text-center">Destino</th>
                  <th class="text-center">Cantidad</th>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
            
          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>


        </div>



    </div>

  </div>

</div>

<!--=====================================
MODAL UNIR NOTA DE SALIDA
======================================-->

<div id="modalUnirNotaSalida" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" class="formularioUnirNotaSalida">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Unir nota de salida</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA LA NOTA DE SALIDA -->
            
            <div class="form-group">
              <label for="">NOTA DE SALIDA</label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <select  class="form-control selectpicker" name="selectNotaSalida" id="selectNotaSalida" data-live-search="true" required>
                  <option value="">SELECCIONAR NOTA DE SALIDA</option>

                  <?php 
                    $item="EstNota";
                    $valor="1";

                    $notaSalida= ControladorNotasSalidas::ctrSelectNotaSalida($item,$valor);

                    foreach ($notaSalida as $key => $value) {

                      echo '<option value="'.$value["Nro"].'">'.$value["Nro"]." / ".$value["fecha"].'</option>';

                    }

                  ?>

                </select>

              </div>

            </div>          

            <!-- ENTRADA PARA LA MATERIA PRIMA -->
            
            <div class="form-group">
              <label for="">MATERIA PRIMA</label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <select  class="form-control selectpicker" name="selectCodPro" id="selectCodPro" data-live-search="true" required>
                <option value="">SELECCIONAR MATERIA PRIMA</option>
                </select>

              </div>

            </div>

            <!-- ENTRADA PARA LA NOTA DE SALIDA A SALDAR -->
            
            <div class="form-group">
              <label for="">NOTA DE SALIDA A SALDAR </label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <select  class="form-control selectpicker" name="selectDependienteNotaSalida" id="selectDependienteNotaSalida" data-live-search="true" required>
                <option value="">SELECCIONAR NOTA DE SALIDA</option>
                </select>

              </div>

            </div>

             <!-- ENTRADA PARA LA CANTIDAD -->
            
             <div class="form-group">
              <label for="">CANTIDAD </label>
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <input type="number" step="any" min="0" class="form-control input-md" name="nuevaCantidadSaldar" id="nuevaCantidadSaldar"  saldo = "" required>


              </div>

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Unir saldo</button>

        </div>

      </form>


      <?php

        $unirNotaSalida = new ControladorNotasSalidas();
        $unirNotaSalida -> ctrUnirNotaSalida();

      ?>


    </div>

  </div>

</div>

<?php 
  $anularNotaSalida = new ControladorNotasSalidas();
  $anularNotaSalida -> ctrAnularNotaSalida();
?>

<script>
window.document.title = "Notas de Salidas"
</script>