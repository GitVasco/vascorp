<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Talleres - General

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Talleres - General</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">
      <div class="box-header with-border">
        <button class="btn btn-info" data-toggle="modal" data-target="#modalExportarArticulo"> 
        <i class="fa fa-upload"></i> Imprimir ticket
        </button>
        <button class="btn btn-danger" data-toggle="modal" data-target="#modalEliminarArticulo"> 
        <i class="fa fa-trash"></i> Eliminar bloque
        </button>
        <button class="btn btn-warning btnCrearTicket" data-toggle="modal" data-target="#modalCrearTicket" idTaller="2021493731"> 
        <i class="fa fa-print"></i> Reimprimir ticket
        </button>

        <button class="btn btn-primary btnCrearCompensacion" data-toggle="modal" data-target="#modalCrearCompensacion" idTaller="2021493731"> 
        <i class="fa fa-plus-square"></i> Crear compensacion
        </button>

        <button class="btn btn-success btnCrearTicketOriginal" data-toggle="modal" data-target="#modalCrearTicketOriginal" > 
        <i class="fa fa-plus-square"></i> Crear ticket
        </button>

        <button type="button" class="btn btn-default btnReporteTalleres" style="border:green 1px solid">
          <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Talleres  </button>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnTaller">
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

        <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
        <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">

        <table class="table table-bordered table-striped dt-responsive tablaTalleresG" width="100%">

          <thead>

            <tr>

              <th>Id</th>
              <th>Cob. Barra</th>
              <th>Modelo</th>
              <th>Color</th>
              <th>Talla</th>
              <th>Operación</th>
              <th>Trabajador</th>
              <th>Cantidad</th>
              <th>Fecha</th>
              <th>F. termino</th>
              <th>Estado</th>
              <th width="150px">Acciones</th>

            </tr>

          </thead>

        </table>

      </div>

    </div>

  </section>

</div>

<!--=====================================
MODAL EDITAR CANTIDAD
======================================-->

<div id="modalEditarCantidad" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar Cantidad</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <input type="hidden" name="editarTaller" id="editarTaller">
            <input type="hidden" name="usuario" value="<?php echo $_SESSION["id"]; ?>">
            <input type="hidden" name="editarCodigo" id="editarCodigo">
            <input type="hidden" name="editarCodOperacion" id="editarCodOperacion">
            <input type="hidden" name="editarBarra" id="editarBarra">
            <!-- ENTRADA PARA EL NOMBRE -->
            <div class="form-group col-lg-6">

              <label>Articulo</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarArticulo" name="editarArticulo" required readonly>

              </div>

            </div>

            <div class="form-group col-lg-6">

              <label>Nombre</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarNombre" name="editarNombre" required readonly>

              </div>

            </div>

            <!-- ENTRADA PARA LA OPERACION -->
            <div class="form-group col-lg-4">

              <label>Cod. Op</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarCOT" name="editarCOT"  readonly>

              </div>

            </div>

            <div class="form-group col-lg-8">

              <label>Operacion</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarOT" name="editarOT"  readonly>

              </div>

            </div>

            <div class="form-group col-lg-4">

              <label>Modelo</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarModelo" name="editarModelo" required readonly>

              </div>

            </div>

            <div class="form-group col-lg-4">

              <label>Color</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarColor" name="editarColor" required readonly>

              </div>

            </div>

            <div class="form-group col-lg-4">

              <label>Talla</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                <input type="text" class="form-control" id="editarTalla" name="editarTalla" required readonly>

              </div>

            </div>
            <div class="form-group col-lg-6">

              <label>Cantidad</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-arrow-up"></i></span>

                <input type="number" class="form-control" id="cantidad" name="cantidad" required readonly>

              </div>

            </div>

            <div class="form-group col-lg-6">

              <label>Dividir Cantidad</label>

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-arrow-down"></i></span>

                <input type="number" class="form-control" id="editarCantidad2" name="editarCantidad2" required >

              </div>

            </div>

          </div>

        </div>

          <!--=====================================
        PIE DEL MODAL
        ======================================-->

          <div class="modal-footer">

            <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

            <button type="submit" class="btn btn-success">Editar Cantidad</button>

          </div>

      </form>

      <?php

      $editarCantidad = new ControladorTalleres();
      $editarCantidad->ctrEditarCantidad();

      ?>

    </div>

  </div>

</div>

<!--=====================================
MODAL EXPORTAR ARTICULO
======================================-->

<div id="modalExportarArticulo" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Exportar árticulo</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA LA FECHA -->
            
            <div class="form-group">
              
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 
                <input type="date" class="form-control input-lg" name="fechaCabecera" id="fechaCabecera">
              </div>

            </div>
            <!-- ENTRADA PARA EL CODIGO UNICO DE ARTICULO TALLER -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <select type="text" class="form-control input-lg selectpicker" name="nuevoCodigo" id="nuevoCodigo" data-live-search="true" data-size="10" required>
                  <option value="">Seleccionar articulo</option>
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

          <button type="submit" class="btn btn-primary">Exportar articulo</button>

        </div>

      </form>


      <?php

        $exportarArticulo = new ControladorTalleres();
        $exportarArticulo -> ctrExportarArticulo();

      ?>


    </div>

  </div>

</div>

<!--=====================================
MODAL ELIMINAR ARTICULO
======================================-->

<div id="modalEliminarArticulo" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Eliminar árticulo</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA LA FECHA -->
            
            <div class="form-group">
              
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span> 
                <input type="date" class="form-control input-lg" name="fechaCabecera2" id="fechaCabecera2">
              </div>

            </div>

            <!-- ENTRADA PARA EL CODIGO UNICO DE ARTICULO TALLER -->
            
            <div class="form-group">
              
              <div class="input-group">
              
                <span class="input-group-addon"><i class="fa fa-key"></i></span> 

                <select type="text" class="form-control input-lg selectpicker" name="nuevoCodigo2" id="nuevoCodigo2" data-live-search="true" data-size="10" required>
                  <option value="">Seleccionar articulo</option>

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

          <button type="submit" class="btn btn-danger">Confirmar Eliminado</button>

        </div>

      </form>


      <?php

        $eliminarTaller = new ControladorTalleres();
        $eliminarTaller -> ctrEliminarArticulo();

      ?>


    </div>

  </div>

</div>


<div id="modalCrearTicket" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#f39c12; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title"><i class="fa fa-print"></i> Reimprimir Ticket (Sin registrar)</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            <strong>Nota:</strong> Este formulario solo reimprime el ticket, NO registra nuevos envíos a taller.
          </div>

          <div class="box-body">

            <div class="form-group col-lg-6">
              <label for="">Modelo</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verMod" name="reimprimirMod" readonly>
                  
              </div>
            </div>  

            <div class="form-group col-lg-6">
              <label for="">Color</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verCol" name="reimprimirCol" readonly>
                  
              </div>
            </div>  

            <div class="form-group col-lg-6">
              <label for="">Talla</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verTal" name="reimprimirTal" value="SURTIDO" readonly>
                  <input type="hidden" id="verArti" name="reimprimirArti">
                  <input type="hidden" id="verCab" name="reimprimirCab">
                  <input type="hidden" id="verPrec" name="reimprimirPrec">
                  <input type="hidden" id="verTmp" name="reimprimirTmp">
                  <input type="hidden" id="verBar" name="reimprimirBar">
                  <input type="hidden" name="reimprimirUser" value="<?php echo $_SESSION["id"]?>">
                  
              </div>
            </div>  


            <div class="form-group col-lg-6">
            <label for="">Cod. Operacion</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" id="verCodOP" name="reimprimirCodOP" readonly>
              </div>

            </div>

            <div class="form-group col-lg-6">
            <label for="">Operacion</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" id="verOP" name="reimprimirOP" readonly>
              </div>

            </div>

            <div class="form-group col-lg-6">
            <label for="">Cantidad</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" name="reimprimirCantidad" id="verCantidad" required>
              </div>

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-warning"><i class="fa fa-print"></i> Reimprimir Ticket</button>

        </div>

      </form>

        <?php

          $reimprimirTicket = new ControladorTalleres();
          $reimprimirTicket -> ctrReimprimirTicket();

        ?>  


    </div>

  </div>

</div>

<div id="modalCrearTicketOriginal" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Crear Ticket</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <div class="form-group col-lg-12">
              <label for="">Articulo</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <select class="form-control input-lg selectpicker" id="ticketArticulo" name="ticketArticulo" data-live-search="true" data-size="10" required>
                    <option value="">Seleccionar Articulo</option>
                    <?php 

                      $articulo =controladorArticulos::ctrMostrarArticulosTicket();
                      foreach ($articulo as $key => $value) {
                        echo '<option value="'.$value["articulo"].'">'.$value["modelo"]." - ". $value["color"]." - ".$value["talla"].'</option>';
                      }
                    ?>
                  </select>
                  
                  <input type="hidden"  name="ticketUser" value="<?php echo $_SESSION["id"]?>">
                  
              </div>
            </div>  


            <div class="form-group col-lg-12">
            <label for="">Operacion</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <select type="text" class="form-control input-lg selectpicker" id="ticketOperacion" name="ticketOperacion" data-live-search="true" data-size="10" required>
                  <option value="">Seleccionar Operación</option>
                  
                  </select>
              </div>

            </div>

            <div class="form-group col-lg-12">
            <label for="">Cantidad</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" name="ticketCantidad" id="ticketCantidad" required>
              </div>

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Crear Ticket</button>

        </div>

      </form>

        <?php

          $crearTicketOriginal = new ControladorTalleres();
          $crearTicketOriginal -> ctrCrearTicketOriginal();

        ?>  


    </div>

  </div>

</div>



<div id="modalCrearCompensacion" class="modal fade" role="dialog">
  
  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Crear Compensación</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <div class="form-group col-lg-6">
              <label for="">Modelo</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verMod2" name="verMod" value="C001" readonly>
                  
              </div>
            </div>  

            <div class="form-group col-lg-6">
              <label for="">Color</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verCol2" name="verCol" value="SURTIDO" readonly>
                  
              </div>
            </div>  

            <div class="form-group col-lg-6">
              <label for="">Talla</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                  <input type="text" class="form-control input-lg" id="verTal2" name="verTal" value="SURTIDO"readonly>
                  <input type="hidden" id="verArti2" name="verArti" value="C001251">
                  <input type="hidden" id="verCab2" name="verCab" value="2021493">
                  <input type="hidden"  name="verUser" value="<?php echo $_SESSION["id"]?>">
                  
              </div>
            </div>  


            <div class="form-group col-lg-6">
            <label for="">Cod. Operacion</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" id="verCodOP2" name="verCodOP" value="828" readonly>
              </div>

            </div>

            <div class="form-group col-lg-6">
            <label for="">Operacion</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" id="verOP2" name="verOP" value="COMPENSACION" readonly>
              </div>

            </div>

            <div class="form-group col-lg-6">
            <label for="">Cantidad</label>
              <div class="input-group">

                  <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                  <input type="text" class="form-control input-lg" name="verCantidad2" id="verCantidad2" required>
              </div>

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Crear Compensacion</button>

        </div>

      </form>

        <?php

          $crearCompensacion = new ControladorTalleres();
          $crearCompensacion -> ctrCrearCompensacion();

        ?>  


    </div>

  </div>

</div>
<script>
window.document.title = "Talleres General"
</script>