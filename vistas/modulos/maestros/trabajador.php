<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
      
      Administrar trabajadores
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Administrar trabajadores</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">
  
        <button class="btn btn-primary AgregarCodigo"  idTrabajador=100 data-toggle="modal" data-target="#modalAgregarTrabajador">
          
          Agregar trabajadores

        </button>

        <div class="pull-right">
          <button class="btn btn-outline-success btnReporteTra" style="border:green 1px solid">
          <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Trabajadores  </button>
        </div>
      </div>

      <div class="box-body">
        
       <table class="table table-bordered table-striped dt-responsive tablaTrabajador" width="100%">
         
        <thead>
         
         <tr>
           
           <th>Codigo</th>
           <th>Tipo Documento</th>
           <th>Nro Documento</th>
           <th>Nombre</th>
           <th>Apellido Paterno</th>
           <th>Apellido Materno</th>
           <th>Tipo Trabajador</th>
           <th>Estado</th>
           <th>Sueldo</th>
           <th>Sector</th>
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
MODAL AGREGAR TRABAJADOR
======================================-->

<div id="modalAgregarTrabajador" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar Trabajador</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">


            <!-- ENTRADA PARA EL CODIGO DEL TRABAJADOR -->
            
            <!-- <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="text" min="0" class="form-control input-lg" name="codigoTrabajador" id="codigoTrabajador"  readonly required>
                

              </div>

            </div>          -->

            <!-- ENTRADA PARA SELECCIONAR TIPO DE DOCUMENTO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-id-card-o" aria-hidden="true"></i></span>

                <select class="form-control input-lg selectpicker" id="tipoDocumento" name="tipoDocumento" data-live-search="true" required>

                  <option value="">Seleccionar tipo de documento</option>

                  <?php

                  $valor = null;

                  $tipodocumento = ControladorTipoDocumento::ctrMostrarTipoDocumento($valor);

                  foreach ($tipodocumento as $key => $value) {

                    echo '<option value="' . $value["cod_doc"] . '">' . $value["tipo_doc"] . '</option>';
                  }

                  ?>

                </select>

              </div>

            </div> 

            <!-- ENTRADA PARA EL NUMERO DE DOCUMENTO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-check-square" aria-hidden="true"></i></span> 

                <input type="text" class="form-control input-lg" name="nroDocumento" placeholder="Ingresar nro de documento" required>

              </div>

            </div>

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span> 

                <input type="text" class="form-control input-lg" name="nombreTrabajador" placeholder="Ingresar nombre del trabajador" required>

              </div>

            </div>


            <!-- ENTRADA PARA EL APELLIDO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span> 

                <input type="text" class="form-control input-lg" name="apellidoPaterno" placeholder="Ingresar apellido paterno" required>

              </div>

            </div>


            <!-- ENTRADA PARA EL APELLIDO MATERNO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span> 

                <input type="text" class="form-control input-lg" name="apellidoMaterno" placeholder="Ingresar apellido materno" required>

              </div>

            </div>
            <!-- ENTRADA PARA SELECCIONAR TIPO DE TRABAJADOR -->

          <div class="form-group">

              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-briefcase" aria-hidden="true"></i></span>

                    <select class="form-control input-lg selectpicker" id="tipoTrabajador" name="tipoTrabajador" data-live-search="true" required>

                      <option value="">Seleccionar tipo de trabajador</option>

                        <?php

                        $valor = null;

                        $tipotrabajador = ControladorTipoTrabajador::ctrMostrarTipoTrabajador(null, null);

                        if (is_array($tipotrabajador)) {
                        foreach ($tipotrabajador as $key => $value) {

                          echo '<option value="' . $value["cod_tip_tra"] . '">' . $value["nom_tip_trabajador"] . '</option>';
                        }
                        }

                        ?>

                    </select>

              </div>

            </div>      
                        

            <!-- ENTRADA PARA EL SUELDO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-money" aria-hidden="true"></i></span>

                <input type="text" class="form-control input-lg" id="sueldoMes" name="sueldoMes" placeholder="Ingresar sueldo" required>

              </div>

            </div>

            <!-- ENTRADA PARA EL SECTOR -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <select class="form-control input-lg selectpicker" name="nuevoSectorTrabajador" id="nuevoSectorTrabajador" data-live-search="true"  placeholder="Ingresar sector" >
                  <option value="">Seleccionar sector</option>
                  <?php
                  $item = null;
                  $valor=null;

                  $sectores=ControladorSectores::ctrMostrarSectores($item,$valor);
                  foreach ($sectores as $key => $value) {
                    echo"<option value='".$value['cod_sector']."'>".$value["cod_sector"]." - ".$value["nom_sector"]."</option>";
                  }
                  ?>
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

          <button type="submit" class="btn btn-primary">Guardar trabajador</button>

        </div>

      </form>


      <?php

          $crearTrabajador = new ControladorTrabajador();
          $crearTrabajador -> ctrCrearTrabajador();

      ?>


    </div>

  </div>

</div>
<!--=====================================
MODAL EDITAR TRABAJADOR
======================================-->

<div id="modalEditarTrabajador" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar trabajador</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA EL CÓDIGO DE TRABAJADOR -->
            
              <div class="form-group">
                
                <div class="input-group">
                
                    <span class="input-group-addon"><i class="fa fa-code"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarCodigoTrabajador" name="editarCodigoTrabajador" readonly required>
                    

                </div>

              </div>


            <!-- ENTRADA PARA SELECCIONAR TIPO DOCUMENTO -->

                <div class="form-group">
                  
                  <div class="input-group">
                  
                    <span class="input-group-addon"><i class="fa fa-th"></i></span> 

                    <select class="form-control input-lg selectpicker"  name="editarTipoDocumento" data-live-search ="true" readonly required>
                      
                      <!-- <option id="editarTipoDocumento"></option> -->
                    <?php
                    
                    $tipodocumento = ControladorTipoDocumento::ctrMostrarTipoDocumento();
                    //var_dump("marcas", $marcas);

                    foreach ($tipodocumento as $key => $value) {

                      echo '<option value="' . $value["cod_doc"] . '">' . $value["tipo_doc"] . '</option>';

                    }

                    
                    ?>


                    </select>

                  </div>

                </div>

            <!-- ENTRADA PARA EL TIPO DE TRABAJADOR -->
            
              <!-- <div class="form-group">
                
                <div class="input-group">
                
                    <span class="input-group-addon"><i class="fa fa-id-card-o" aria-hidden="true"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarTipoDocumento" name="editarTipoDocumento" readonly required>
                    

                </div>

              </div> -->

            <!-- ENTRADA PARA NRO DE DOCUMENTO -->

              <div class="form-group">
                
                <div class="input-group">
                
                    <span class="input-group-addon"><i class="fa fa-check-square" aria-hidden="true"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarNroDocumento" name="editarNroDocumento" required>

                </div>

              </div>

             <!-- ENTRADA PARA NOMBRE -->

              <div class="form-group">
                
                <div class="input-group">
                
                    <span class="input-group-addon"><i class="fa fa-user-circle" aria-hidden="true"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarNombreTrabajador" name="editarNombreTrabajador"  required>

                </div>

              </div>
             <!-- ENTRADA PARA APELLIDO PATERNO -->

              <div class="form-group">
                
                <div class="input-group">
                
                    <span class="input-group-addon"><i class="fa fa-user-circle" aria-hidden="true"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarApellidoPaterno" name="editarApellidoPaterno"  required>

                </div>

              </div>
              <!-- ENTRADA PARA APELLIDO MATERNO -->

                <div class="form-group">
              
                  <div class="input-group">
              
                      <span class="input-group-addon"><i class="fa fa-user-circle" aria-hidden="true"></i></span> 

                      <input type="text" class="form-control input-lg" id="editarApellidoMaterno" name="editarApellidoMaterno"  required>

                  </div>

                </div>

            <!-- ENTRADA PARA SELECCIONAR TIPO TRABAJADOR -->

            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-briefcase"></i></span> 

                <select class="form-control input-lg selectpicker"  name="editarTipoTrabajador" data-live-search="true"  required>
                  
                  <!-- <option id="editarTipoTrabajador"></option> -->
                  <?php
                    
                    $tipotrabajador = ControladorTipoTrabajador::ctrMostrarTipoTrabajador(null, null);

                    if (is_array($tipotrabajador)) {
                    foreach ($tipotrabajador as $key => $value) {

                      echo '<option value="' . $value["cod_tip_tra"] . '">' . $value["nom_tip_trabajador"] . '</option>';

                    }
                    }

                    
                    ?>
                </select>

              </div>

            </div>
            <!-- ENTRADA PARA EDITAR TIPO TRABAJADOR -->

              <!-- <div class="form-group">
                
                <div class="input-group">
            
                    <span class="input-group-addon"><i class="fa fa-briefcase" aria-hidden="true"></i></span> 

                    <input type="text" class="form-control input-lg" id="editarTipoTrabajador" name="editarTipoTrabajador" readonly  required>

                </div>

              </div> -->


             <!-- ENTRADA PARA EDITAR SUELDO x MES -->

              <div class="form-group">
                    
                  <div class="input-group">
                    
                      <span class="input-group-addon"><i class="fa fa-money" aria-hidden="true"></i></span> 

                      <input type="text" class="form-control input-lg" id="editarSueldoMes" name="editarSueldoMes"  required>
                      <input type="hidden" id="idTrabajador" name="idTrabajador">

                  </div>

              </div>

              <!-- ENTRADA PARA EL SECTOR -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-user"></i></span> 

                <select type="text" class="form-control input-lg selectpicker" name="editarSectorTrabajador" id="editarSectorTrabajador" data-live-search="true">
                  <option value="">Seleccionar sector</option>
                  <?php
                  $item = null;
                  $valor=null;
                  $sectores=ControladorSectores::ctrMostrarSectores($item,$valor);
                  foreach ($sectores as $key => $value) {
                    echo"<option value='".$value['cod_sector']."'>".$value["cod_sector"]." - ".$value["nom_sector"]."</option>";
                  }
                  ?>
                </select>
                <input type="hidden" id="idTipoTrabajador" name="idTipoTrabajador">
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

           $editarTrabajador = new ControladorTrabajador();
            $editarTrabajador -> ctrEditarTrabajador();

        ?>    

    </div>

  </div>

</div>

<?php

  $eliminarTrabajador = new ControladorTrabajador();
  $eliminarTrabajador -> ctrEliminarTrabajador();

?>

<script>
window.document.title = "Trabajador"
</script>