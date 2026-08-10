<div class="content-wrapper">

    <section class="content-header">

        <h1>
            Administrar Abonos
            <small id="subtituloPeriodoAbonos">Periodo</small>
        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar Abonos</li>

        </ol>

        <div class="form-inline" style="margin-top:12px;">
            <label for="anioPeriodoAbonos" style="font-weight:normal; margin-right:4px;">Año</label>
            <select class="form-control input-sm selectpicker" id="anioPeriodoAbonos" data-width="100px" data-size="8" title="Año" style="margin-right:10px;">
                <?php
                $anioMinAbonos = ControladorAbonos::ctrAnioMinimoAbonos();
                $anioActualStats = (int) date("Y");
                if ($anioActualStats < $anioMinAbonos) {
                    $anioActualStats = $anioMinAbonos;
                }
                $mesActualStats = (int) date("n");
                $anioSelUrl = isset($_GET["anio"]) ? (int) $_GET["anio"] : $anioActualStats;
                if ($anioSelUrl < $anioMinAbonos || $anioSelUrl > $anioActualStats) {
                    $anioSelUrl = $anioActualStats;
                }
                for ($y = $anioActualStats; $y >= $anioMinAbonos; $y--) {
                    $sel = $y === $anioSelUrl ? " selected" : "";
                    echo '<option value="' . $y . '"' . $sel . '>' . $y . '</option>';
                }
                ?>
            </select>
            <label for="mesPeriodoAbonos" style="font-weight:normal; margin-right:4px; margin-left:8px;">Mes</label>
            <select class="form-control input-sm selectpicker" id="mesPeriodoAbonos" data-width="150px" data-size="10" title="Mes">
                <?php
                $mesesPeriodo = array(
                    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
                    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
                    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre",
                );
                $mesRawUrl = isset($_GET["mes"]) ? (string) $_GET["mes"] : (string) $mesActualStats;
                $mesTodosSel = ($mesRawUrl === "todos" || $mesRawUrl === "0");
                if ($mesTodosSel) {
                    $mesSelUrl = 0;
                } else {
                    $mesSelUrl = (int) $mesRawUrl;
                    if ($mesSelUrl < 1 || $mesSelUrl > 12) {
                        $mesSelUrl = $mesActualStats;
                    }
                }
                echo '<option value="todos"' . ($mesSelUrl === 0 ? " selected" : "") . '>Todo el año</option>';
                foreach ($mesesPeriodo as $numMes => $nomMes) {
                    $sel = $numMes === $mesSelUrl ? " selected" : "";
                    echo '<option value="' . $numMes . '"' . $sel . '>' . $nomMes . '</option>';
                }
                ?>
            </select>
        </div>

    </section>

    <section class="content">

        <div class="row" id="statsCabeceraAbonos" style="margin-bottom:8px;">
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-yellow" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-hourglass-half"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text" id="lblPeriodoPendientes">Periodo · pendientes</span>
                        <span class="info-box-number" id="statMesPendientes" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statMesPendientesMonto" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-green" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-check"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text" id="lblPeriodoAplicados">Periodo · aplicados</span>
                        <span class="info-box-number" id="statMesAplicados" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statMesAplicadosMonto" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-aqua" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-percent"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text" id="lblPeriodoPct">Periodo · % pend.</span>
                        <span class="info-box-number" id="statMesPct" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statMesTotal" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-orange" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-calendar"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text">Año · pendientes</span>
                        <span class="info-box-number" id="statAnioPendientes" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statAnioPendientesMonto" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-teal" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-calendar-check-o"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text">Año · aplicados</span>
                        <span class="info-box-number" id="statAnioAplicados" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statAnioAplicadosMonto" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                <div class="info-box bg-navy" style="min-height:70px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:70px; width:55px; line-height:70px; font-size:28px;"><i class="fa fa-line-chart"></i></span>
                    <div class="info-box-content" style="margin-left:55px;">
                        <span class="info-box-text">Año · % pend.</span>
                        <span class="info-box-number" id="statAnioPct" style="font-size:20px;">—</span>
                        <span class="progress-description" id="statAnioTotal" style="font-size:11px;">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header width-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarAbono">Agregar Abono</button>
                <button class="btn btn-success" data-toggle="modal" data-target="#modalImportarAbono"><i class="fa fa-upload"></i> Importar Abono</button>
                <button type="button" class="btn btn-outline-success btnReporteAbonos" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte
                </button>
                <div class="form-inline pull-right" style="margin-top:5px;">
                    <label for="filtroMotivoAbono" style="margin-right:6px;">Motivo</label>
                    <select class="form-control input-sm" id="filtroMotivoAbono">
                        <option value="">Todos</option>
                        <option value="sin"<?php echo (isset($_GET["motivo"]) && $_GET["motivo"] === "sin") ? " selected" : ""; ?>>Sin motivo</option>
                        <?php
                        $motivosAbono = ControladorAbonos::ctrMotivosPendiente();
                        $motivoSelUrl = isset($_GET["motivo"]) ? (string) $_GET["motivo"] : "";
                        foreach ($motivosAbono as $codigo => $etiqueta) {
                            $sel = ($motivoSelUrl === (string) $codigo) ? " selected" : "";
                            echo '<option value="' . htmlspecialchars($codigo, ENT_QUOTES, "UTF-8") . '"' . $sel . '>'
                                . htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8")
                                . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaAbonos" width="100%">

                    <thead>

                        <tr>

                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th style="text-align:right">Monto</th>
                            <th>Agencia</th>
                            <th>Operación</th>
                            <th>Motivo</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>


<!--=====================================
MODAL MOTIVO / OBSERVACIÓN PENDIENTE
======================================-->

<div id="modalMotivoAbono" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Motivo de no aplicación</h4>

        </div>

        <div class="modal-body">

          <div class="box-body">

            <input type="hidden" id="idAbonoMotivo">

            <div class="form-group">
              <label>Motivo</label>
              <select class="form-control input-lg" id="motivoPendiente">
                <option value="">Sin motivo</option>
                <?php
                foreach ($motivosAbono as $codigo => $etiqueta) {
                    echo '<option value="' . htmlspecialchars($codigo, ENT_QUOTES, "UTF-8") . '">'
                        . htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8")
                        . '</option>';
                }
                ?>
              </select>
            </div>

            <div class="form-group">
              <label>Observación <small class="text-muted">(opcional)</small></label>
              <textarea class="form-control" id="observacionPendiente" rows="3" maxlength="500" placeholder="Detalle adicional si hace falta"></textarea>
            </div>

            <p class="help-block" id="motivoMetaAbono" style="display:none;"></p>

          </div>

        </div>

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="button" class="btn btn-primary" id="btnGuardarMotivoAbono">Guardar</button>

        </div>

    </div>

  </div>

</div>


<!--=====================================
MODAL AGREGAR ABONO
======================================-->

<div id="modalAgregarAbono" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Agregar abono</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA FECHA -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="date"  class="form-control input-lg" name="nuevaFecha"  required>

              </div>

            </div>          

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-text-width"></i></span> 

                <input type="text" class="form-control input-lg" name="nuevaDescripcion" placeholder="Ingresar descripción" required>

              </div>

            </div>

            <!-- ENTRADA PARA MONTO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-usd"></i></span> 

                <input type="number" min="0" step="any" class="form-control input-lg" name="nuevoMonto" placeholder="Ingresar monto" required>

              </div>

            </div>  

            <!-- ENTRADA PARA AGENCIA -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-university"></i></span> 

                <input type="text" class="form-control input-lg" name="nuevaAgencia" placeholder="Ingresar agencia" required>

              </div>

            </div>  

            <!-- ENTRADA PARA OPERACION -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-bolt"></i></span> 

                <input type="text" class="form-control input-lg" name="nuevoOpe" placeholder="Ingresar codigo operación" required>

              </div>

            </div>  

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar abono</button>

        </div>

      </form>


      <?php

        $crearAbono = new ControladorAbonos();
        $crearAbono -> ctrCrearAbono();

      ?>


    </div>

  </div>

</div>


<!--=====================================
MODAL EDITAR ABONO
======================================-->

<div id="modalEditarAbono" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar abono</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

          
            <!-- ENTRADA PARA FECHA  -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 

                <input type="date"  class="form-control input-lg" name="editarFecha" id="editarFecha" required>

              </div>

            </div>

            <!-- ENTRADA PARA EL NOMBRE -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-text-width"></i></span> 

                <input type="text" class="form-control input-lg" name="editarDescripcion" id="editarDescripcion" required>
                <input type="hidden" id="idAbono" name="idAbono">
              </div>

            </div>
  
            <!-- ENTRADA PARA MONTO -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-usd"></i></span> 

                <input type="number" min="0" step="any" class="form-control input-lg" name="editarMonto" id="editarMonto" required>
              </div>

            </div>

            <!-- ENTRADA PARA AGENCIA -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-university"></i></span> 

                <input type="text" class="form-control input-lg" name="editarAgencia" id="editarAgencia" required>
              </div>

            </div>

            <!-- ENTRADA PARA OPERACION -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-bolt"></i></span> 

                <input type="text" class="form-control input-lg" name="editarOpe" id="editarOpe" required>
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

        $editarAbono = new ControladorAbonos();
        $editarAbono -> ctrEditarAbono();

      ?>   


    </div>

  </div>

</div>


<!--=====================================
MODAL IMPORTAR CUENTAS DE BANCO
======================================-->

<div id="modalImportarAbono" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Importar abonos</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA EL CODIGO -->
            
            <div class="form-group">
            <label for=""><h3>Archivo de banco para abonos</h3></label>
              <div class="input-group">
                
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <input type="file"  class="form-control input-lg" name="nuevoAbono" id="nuevoAbono"  required>

              </div>

            </div>        

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary" name="importAbono">Importar abonos</button>

        </div>

      </form>


      <?php

        $importarAbono = new ControladorAbonos();
        $importarAbono -> ctrImportarAbono();

      ?>


    </div>

  </div>

</div>

<?php

  $eliminarAbono = new ControladorAbonos();
  $eliminarAbono -> ctrEliminarAbono();

?>


<script>
    window.document.title = "Abonos"
</script>