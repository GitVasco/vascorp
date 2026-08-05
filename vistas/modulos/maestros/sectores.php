<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
      
      Administrar sectores
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Administrar sectores</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">
        
      <div class="box-header with-border">
  
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarSector">
          
          Agregar sector

        </button>
        <div class="pull-right">
          <button class="btn btn-outline-success btnReporteSector" style="border:green 1px solid">
          <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Sectores  </button>
        </div>
        <p class="help-block" style="margin-top:10px;margin-bottom:0;">
          Puedes activar o desactivar un sector y asignarle un color. Por ahora esto solo se guarda aquí; aún no cambia otros módulos.
        </p>
      </div>

      <div class="box-body">
        
       <table class="table table-bordered table-striped dt-responsive tablaSectores" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Codigo</th>
           <th>Sector</th>
           <th>Tipo</th>
           <th>Color</th>
           <th>Estado</th>
           <th>Acciones</th>

         </tr> 

        </thead>

       </table>

      </div>

    </div>

  </section>

</div>

<!--=====================================
MODAL AGREGAR SECTOR
======================================-->

<div id="modalAgregarSector" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Agregar Sector</h4>

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

                <input type="text" class="form-control input-lg" name="nuevoSector" placeholder="Ingresar sector" required>

              </div>

            </div>

            <!-- TIPO: 0 = Taller (interno), 1 = Servicio (externo) -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-wrench"></i></span>

                <select class="form-control input-lg" name="nuevoTipo" id="nuevoTipo" required>
                  <option value="0">Taller (interno)</option>
                  <option value="1" selected>Servicio (externo)</option>
                </select>

              </div>

            </div>

            <div class="form-group">
              <label>Estado</label>
              <select class="form-control input-lg" name="nuevoEstado" id="nuevoEstado" required>
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="form-group">
              <label>Color del taller</label>
              <div class="input-group">
                <span class="input-group-addon" id="previewNuevoColorSector" style="width:42px;background:#A8D5E5;"></span>
                <select class="form-control input-lg selectColorSector" name="nuevoColor" id="nuevoColor" required>
                  <?php foreach (ModeloSectores::mdlPaletaPasteles() as $hex => $etiqueta) { ?>
                  <option value="<?php echo htmlspecialchars($hex, ENT_QUOTES, 'UTF-8'); ?>"
                    data-color="<?php echo htmlspecialchars($hex, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php echo $hex === '#A8D5E5' ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                  <?php } ?>
                </select>
              </div>
            </div>
 
          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar sector</button>

        </div>

      </form>


      <?php

        $crearSector = new ControladorSectores();
        $crearSector -> ctrCrearSector();

      ?>


    </div>

  </div>

</div>


<!--=====================================
MODAL EDITAR SECTOR
======================================-->

<div id="modalEditarSector" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar sector</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

          
            <!-- ENTRADA PARA EL DOCUMENTO ID -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text" class="form-control input-lg" name="editarCodigo" id="editarCodigo" required>

              </div>

            </div>

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <input type="text" class="form-control input-lg" name="editarSector" id="editarSector" required>
                <input type="hidden" id="idSector" name="idSector">
              </div>

            </div>

            <!-- TIPO: 0 = Taller (interno), 1 = Servicio (externo) -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-wrench"></i></span>

                <select class="form-control input-lg" name="editarTipo" id="editarTipo" required>
                  <option value="0">Taller (interno)</option>
                  <option value="1">Servicio (externo)</option>
                </select>

              </div>

            </div>

            <div class="form-group">
              <label>Estado</label>
              <select class="form-control input-lg" name="editarEstado" id="editarEstado" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="form-group">
              <label>Color del taller</label>
              <div class="input-group">
                <span class="input-group-addon" id="previewEditarColorSector" style="width:42px;background:#A8D5E5;"></span>
                <select class="form-control input-lg selectColorSector" name="editarColor" id="editarColor" required>
                  <?php foreach (ModeloSectores::mdlPaletaPasteles() as $hex => $etiqueta) { ?>
                  <option value="<?php echo htmlspecialchars($hex, ENT_QUOTES, 'UTF-8'); ?>"
                    data-color="<?php echo htmlspecialchars($hex, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                  <?php } ?>
                </select>
              </div>
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

        $editarSector = new ControladorSectores();
        $editarSector -> ctrEditarSector();

      ?>   


    </div>

  </div>

</div>


<?php

  $eliminarSector = new ControladorSectores();
  $eliminarSector -> ctrEliminarSector();

?>

<script>
window.document.title = "Sectores"
</script>