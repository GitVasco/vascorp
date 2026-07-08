<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
      
      Administrar vendedores
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Administrar vendedores</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">
  
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarVendedor">
          
          Agregar vendedores

        </button>

        <div class="pull-right">
          <button class="btn btn-outline-success" style="border:green 1px solid">
          <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte vendedores  </button>
        </div>
      </div>
        
      <div class="box-body">
        
       <table class="table table-bordered table-striped dt-responsive tablaVendedores" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Codigo</th>
           <th>Nombres</th>
           <th>Centro Decisiones</th>
           <th>Acciones</th>

         </tr> 

        </thead>

        <tbody>

        </tbody>

       </table>

      </div>

    </div>

  </section>

</div>

<!--=====================================
MODAL AGREGAR VENDEDOR
======================================-->

<div id="modalAgregarVendedor" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Agregar vendedor</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA EL CODIGO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text" min="0" class="form-control input-lg" name="nuevoCodigo" placeholder="Ingresar codigo" required>

              </div>

            </div>          

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <input type="text" class="form-control input-lg" name="nuevaDescripcion" placeholder="Ingresar nombres de vendedor" required>

              </div>

            </div>

            <!-- CENTRO DE DECISIONES -->
            <div class="form-group">
              <label>Centro de Decisiones</label>
              <select class="form-control input-lg" name="nuevoEstadoDecisiones">
                <option value="0">No incluido</option>
                <option value="1">Incluido</option>
              </select>
              <p class="help-block" style="margin-bottom:0;">Solo afecta el filtro del módulo Centro de Decisiones.</p>
            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar vendedor</button>

        </div>

      </form>


      <?php

        $crearVendedor = new ControladorVendedores();
        $crearVendedor -> ctrCrearVendedor();

      ?>


    </div>

  </div>

</div>


<!--=====================================
MODAL EDITAR VENDEDOR
======================================-->

<div id="modalEditarVendedor" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar vendedor</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

          
            <!-- ENTRADA PARA EL CODIGO  -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text"  class="form-control input-lg" name="editarCodigo" id="editarCodigo" required>

              </div>

            </div>

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <input type="text" class="form-control input-lg" name="editarDescripcion" id="editarDescripcion" required>
                <input type="hidden" id="idVendedor" name="idVendedor">
              </div>

            </div>

            <!-- CENTRO DE DECISIONES -->
            <div class="form-group">
              <label>Centro de Decisiones</label>
              <select class="form-control input-lg" name="editarEstadoDecisiones" id="editarEstadoDecisiones">
                <option value="0">No incluido</option>
                <option value="1">Incluido</option>
              </select>
              <p class="help-block" style="margin-bottom:0;">Solo afecta el filtro del módulo Centro de Decisiones.</p>
            </div>
  
          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar cambios</button>

        </div>

      </form>

      <?php

        $editarVendedor = new ControladorVendedores();
        $editarVendedor -> ctrEditarVendedor();

      ?>   


    </div>

  </div>

</div>


<?php

  $eliminarVendedor = new ControladorVendedores();
  $eliminarVendedor -> ctrEliminarVendedor();

?>

<script>
window.document.title = "Vendedores"
</script>