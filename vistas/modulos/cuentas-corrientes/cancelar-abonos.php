<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Cancelar Abonos

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Cancelar Abonos</li>

    </ol>

  </section>
  <div class="row" style="margin:0;">
    <div class="col-md-7" style="padding-right:5px;">
      <section class="content" style="padding:0;">
        <div class="box" style="margin-bottom:10px;">
          <div class="box-header">
            <h3 class="text-center text-danger">Cuentas</h3>
            <button class="btn btn-success btnCancelarAbono" data-toggle='modal' data-target='#modalCancelarAbono' idCuenta><i class="fa fa-save"></i> Cancelar cuenta</button>
          </div>
          <div class="box-body" style="padding:5px;">
            <div style="overflow-x:auto;">
              <table class="table table-bordered table-striped dt-responsive tablaCuentasCancelar" style="width:100%; font-size:13px;">
                <thead>
                  <tr>
                    <th>T. Doc.</th>
                    <th>Nro Doc.</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Saldo</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>
    <div class="col-md-5" style="padding-left:5px;">
      <section class="content" style="padding:0;">
        <div class="box" style="margin-bottom:10px;">
          <h3 class="text-center text-green">Abonos</h3>
          <div class="box-header">
            <button class="btn btn-success" id="btnRecargar"><i class="fa fa-refresh"></i> Actualizar</button>
          </div>
          <div class="box-body" style="padding:5px;">
            <div style="overflow-x:auto;">
              <table class="table table-bordered table-striped dt-responsive tablaAbonosCancelar" style="width:100%; font-size:13px;">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Agencia</th>
                    <th>Operación</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

</div>
<!--=====================================
MODAL CANCELAR ABONO
======================================-->

<div id="modalCancelarAbono" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Cancelar Abono</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">


            <!-- ENTRADA PARA EL CODIGO  -->

            <div class="form-group col-lg-6">
              <label for="">Tipo Documento</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                <input type="text" class="form-control input-lg" name="editarTipo" id="editarTipo" readonly required>

              </div>

            </div>

            <!-- ENTRADA PARA EL DOCUMENTO -->

            <div class="form-group col-lg-6">
              <label for="">N° cuenta</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                <input type="text" class="form-control input-lg" name="editarCuenta" id="editarCuenta" readonly required>
                <input type="hidden" id="idCuenta4" name="idCuenta4">
                <input type="hidden" id="fechaVen" name="fechaVen">
              </div>

            </div>


            <!-- ENTRADA PARA EL CLIENTE -->

            <div class="form-group col-lg-6">
              <label for="">Cliente</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                <input type="text" class="form-control input-lg" name="editarCliente" id="editarCliente" readonly required>
              </div>

            </div>


            <!-- ENTRADA PARA EL VENDEDOR -->

            <div class="form-group col-lg-6">
              <label for="">Vendedor</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                <input type="text" class="form-control input-lg" name="editarVendedor" id="editarVendedor" readonly required>
              </div>

            </div>

            <!-- ENTRADA PARA EL MONTO -->

            <div class="form-group col-lg-6">
              <label for="">Monto</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                <input type="text" class="form-control input-lg" name="editarMonto" id="editarMonto" readonly required>
              </div>

            </div>

            <!-- ENTRADA PARA EL SALDO -->

            <div class="form-group col-lg-6">
              <label for="">Saldo</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                <input type="text" class="form-control input-lg" name="editarSaldo" id="editarSaldo" readonly required>
              </div>

            </div>

            <!-- ENTRADA PARA EL SALDO -->
            <?php
            date_default_timezone_set("America/Lima");
            $fecha = new DateTime();
            ?>
            <div class="form-group col-lg-6">
              <label for="">Monto Abono</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                <input type="text" class="form-control input-lg" name="editarAbono" id="editarAbono" required>
                <input type="hidden" name="idAbono" id="idAbono">
                <input type="hidden" name="opAbono" id="opAbono">
                <input type="hidden" name="editarUsuario" value="<?php echo $_SESSION["id"]; ?>">

              </div>

            </div>

            <!-- ENTRADA PARA LA FECHA -->

            <div class="form-group col-lg-6">
              <label for="">Fecha</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                <input type="date" class="form-control input-lg" name="editarFecha" id="editarFecha" value="<?php echo $fecha->format("Y-m-d") ?>" required>
              </div>

            </div>

            <!-- ENTRADA PARA EL TIPO DE OPERACION -->
            <div class="form-group col-lg-6">
              <label for="">Operación</label>
              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-cogs"></i></span>

                <select class="form-control input-lg" name="editarOperacion" id="editarOperacion" required>
                  <option value="05">DEP. CTACTE</option>
                  <option value="15">YAPE-BCP</option>
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

          <button type="submit" class="btn btn-primary">Confirmar cancelación</button>

        </div>

      </form>

      <?php

      $cancelarAbono = new ControladorAbonos();
      $cancelarAbono->ctrCancelarAbono();

      ?>


    </div>

  </div>

</div>

<script>
  window.document.title = "Cancelar Abonos"
</script>