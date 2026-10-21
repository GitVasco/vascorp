<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Orden de servicio

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Orden de servicio</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">

        <a href="crear-orden-servicio">

          <button class="btn btn-primary">

            Agregar Orden de Servicio

          </button>

        </a>

        <button class="btn btn-outline-success btnReporteOServicioGeneral" style="border:green 1px solid" inicio="" fin="">
          <img src="vistas/img/plantilla/excel.png" width="20px"> OServicio General Detallado
        </button>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnOrdenServicio">
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
       <table class="table table-bordered table-striped dt-responsive tablaOrdenesServicios" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Est</th>
           <th>Serie</th>
           <th>Numero</th>
           <th>Proveedor</th>
           <th>Fec. Emisión</th> 
           <th>Responsable</th> 
           <th>Estado</th>
           <th>Cerrar</th>
           <th style="width:120px">Acciones</th>

         </tr> 

        </thead>

       </table>

      </div>

    </div>

  </section>

</div>


<!--=====================================
MODAL VIZUALIZAR ORDEN DE SERVICIO
======================================-->

<div id="modalVizualizarOrdenServicio" class="modal fade" role="dialog">

  <div class="modal-dialog" style="width: 75% !important;">

    <div class="modal-content">


        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">VISUALIZAR ORDEN DE SERVICIO</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <div class="form-group col-lg-2">
              
              <label>CODIGO</label>

              <div class="input-group">

              <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text" class="form-control input-sm" name="codigo" id="codigo"  readonly>

              </div>

            </div>

            <div class="form-group col-lg-2">
              
              <label>PROVEEDOR</label>

              <div class="input-group">

              <span class="input-group-addon"><i class="fa fa-truck"></i></span> 

                <input type="text" class="form-control input-sm" name="proveedor" id="proveedor"  readonly>

              </div>

            </div>

            <!-- ENTRADA PARA LA GUIA-->
            
            <div class="form-group col-lg-4">

              <label>RAZON SOCIAL</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-truck"></i></span> 
                <input type="text" class="form-control input-sm" name="razonsocial" id="razonsocial"  readonly>
              </div>

            </div> 

            <!-- ENTRADA PARA LA FECHA-->
            
            <div class="form-group col-lg-2">

              <label>RUC</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span> 

                <input type="number" class="form-control input-sm" name="ruc" id="ruc"   readonly >

              </div>

            </div>   

            <!-- ENTRADA PARA LA FECHA-->
            
            <div class="form-group col-lg-2">

              <label>ESTADO</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <input type="text" class="form-control input-sm" name="estado" id="estado"   readonly >

              </div>

            </div>   
 
 
            <!-- ENTRADA PARA LA RESPONSABLE-->
            
            <div class="form-group col-lg-2">

              <label>FECHA EMISION</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 

                <input type="text" class="form-control input-sm" name="emision" id="emision"  readonly>
              </div>

            </div>            
   
            
            <!-- ENTRADA PARA LA CANTIDAD-->
            
            <div class="form-group col-lg-2">

              <label>FECHA ENTREGA</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 

                <input type="text" class="form-control input-sm" name="entrega" id="entrega"  readonly>

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

            <!-- ENTRADA PARA LA CANTIDAD-->
            
            <div class="form-group col-lg-2">

              <label>DESCONTADO</label>
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span> 

                <input type="text" class="form-control input-sm" name="descontado" id="descontado"  readonly>

              </div>

            </div>
            
            
            <div class="form-group col-lg-12">
              <table class="table table-hover table-striped tablaDetalleOrdenServicio" width="100%">
                <thead>
                
                  <th >Item</th>
                  <th >Cod.Pro</th>
                  <th style="min-width:240px">Descripcion</th>
                  <th >Cod.Dest</th>
                  <th style="min-width:240px">Descripcion</th>
                  <th >Color</th>
                  <th >Und</th>
                  <th style="min-width:80px">Cantidad</th>
                  <th >Despacho</th>
                  <th >Saldo</th>
                  <th >Cod.Est</th>
                  <th >Estado</th>
                  <th >Cerrar</th>
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


<?php

  $anularOrdenCompra = new ControladorOrdenCompra();
  $anularOrdenCompra -> ctrAnularOrdenCompra();

?>

<script>
window.document.title = "Orden de servicio"
</script>