<?php
/* Bloque de datos fiscales para guías de remisión (solo sectores externos) */
function bloqueDatosGreSector($p)
{
?>
        <div class="gre-sector-datos" id="<?= $p; ?>GreBloque" style="background:#f7f9fb;border:1px solid #dde3ea;border-radius:4px;padding:12px 14px">
          <h4 style="margin:0 0 10px;font-size:15px"><i class="fa fa-truck text-blue"></i> Datos para guía de remisión
            <small class="text-muted">opcional</small></h4>

          <div class="form-group">
            <label>Documento</label>
            <div class="input-group">
              <span class="input-group-btn" style="width:130px">
                <select class="form-control" name="<?= $p; ?>GreTipoDoc" id="<?= $p; ?>GreTipoDoc" style="border-right:0">
                  <option value="6">RUC</option><option value="1">DNI</option><option value="4">Carnet extr.</option>
                  <option value="7">Pasaporte</option><option value="0">Sin RUC</option><option value="A">Céd. diplom.</option>
                </select>
              </span>
              <input type="text" class="form-control" name="<?= $p; ?>GreDoc" id="<?= $p; ?>GreDoc" maxlength="15" placeholder="Número">
              <span class="input-group-btn">
                <button type="button" class="btn btn-info btnBuscarDocGre" data-p="<?= $p; ?>" title="Buscar en SUNAT / RENIEC (RUC o DNI)">
                  <i class="fa fa-search"></i> Buscar
                </button>
              </span>
            </div>
            <span class="help-block" id="<?= $p; ?>GreMsg" style="margin:4px 0 0;font-size:12px"></span>
          </div>

          <div class="form-group">
            <label>Razón social / nombre</label>
            <input type="text" class="form-control" name="<?= $p; ?>GreRazon" id="<?= $p; ?>GreRazon" maxlength="100">
          </div>

          <div class="form-group">
            <label>Correo <small class="text-muted">(opcional)</small></label>
            <input type="text" class="form-control" name="<?= $p; ?>GreEmail" id="<?= $p; ?>GreEmail" maxlength="100">
          </div>

          <div class="form-group" style="position:relative">
            <label>Ubigeo</label>
            <input type="text" class="form-control gre-sector-ubigeo" data-p="<?= $p; ?>" placeholder="Buscar distrito o código..." autocomplete="off">
            <ul class="list-group" style="display:none;position:absolute;z-index:2000;left:0;right:0;max-height:220px;overflow-y:auto;margin:0;box-shadow:0 4px 10px rgba(0,0,0,.15)"></ul>
          </div>

          <div class="row">
            <div class="form-group col-sm-4"><label>Cód. ubigeo</label><input type="text" class="form-control" name="<?= $p; ?>GreUbigeo" id="<?= $p; ?>GreUbigeo" maxlength="6"></div>
            <div class="form-group col-sm-8"><label>Distrito</label><input type="text" class="form-control" name="<?= $p; ?>GreDist" id="<?= $p; ?>GreDist" maxlength="30"></div>
          </div>
          <div class="row">
            <div class="form-group col-sm-6"><label>Provincia</label><input type="text" class="form-control" name="<?= $p; ?>GreProv" id="<?= $p; ?>GreProv" maxlength="30"></div>
            <div class="form-group col-sm-6"><label>Departamento</label><input type="text" class="form-control" name="<?= $p; ?>GreDpto" id="<?= $p; ?>GreDpto" maxlength="30"></div>
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label>Dirección</label>
            <input type="text" class="form-control" name="<?= $p; ?>GreDireccion" id="<?= $p; ?>GreDireccion" maxlength="100">
          </div>
        </div>
<?php
}
?>

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
           <th>Documento</th>
           <th>Razón social</th>
           <th>Dirección (guía)</th>
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
          <div class="row">
          <div class="col-md-12" id="nuevoColGen">

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
          <div class="col-md-6" id="nuevoColGre" style="display:none">
<?php bloqueDatosGreSector('nuevo'); ?>
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
          <div class="row">
          <div class="col-md-12" id="editarColGen">

          
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
          <div class="col-md-6" id="editarColGre" style="display:none">
<?php bloqueDatosGreSector('editar'); ?>
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