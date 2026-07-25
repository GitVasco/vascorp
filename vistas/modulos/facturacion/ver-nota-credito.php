<div class="content-wrapper">

  <section class="content-header">
    
    <h1>
      
      Notas de crédito / débito
    
    </h1>

    <ol class="breadcrumb">
      
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      
      <li class="active">Notas de crédito / débito</li>
    
    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">
  
        <a class="btn btn-primary" href="notas-credito">
          Agregar notas de credito/debito
        </a>

        <div class="btn-group" style="margin-left:10px" id="filtroTipoNotaCD" role="group" aria-label="Filtrar tipo de nota">
          <button type="button" class="btn btn-default btnFiltroNotaCD active" data-tipo="">Todas</button>
          <button type="button" class="btn btn-default btnFiltroNotaCD" data-tipo="E05">Nota crédito</button>
          <button type="button" class="btn btn-default btnFiltroNotaCD" data-tipo="S05">Nota débito</button>
        </div>

        <button type="button" class="btn btn-default pull-right" id="daterange-btnNotasCD">
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
        
       <table class="table table-bordered table-striped dt-responsive tablaNotaCredito" width="100%">
         
        <thead>
         
         <tr>
           <th>Tipo doc.</th>
           <th>NT</th>
           <th>documento</th>
           <th>Total</th>
           <th>Cliente</th>
           <th>Fecha</th>
           <th>Doc Origen</th>
           <th>Fec Origen</th>
           <th>Usuario</th>
           <th>Estado</th>
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
MODAL CCUENTA
======================================-->

<div id="modalCuenta" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Asignar cuenta</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

            <div class="box-body">
                <!-- ENTRADA PARA SUBIR FOTO CARGO -->

                <div class="form-group">

          <div class="box box-primary col-lg-12 ">

            <div class="box-header">

              <b>Datos Principales</b>

            </div>

              <!-- ENTRADA PARA EL CODIGO DEL PEDIDO-->

              <div class="form-group col-lg-4">

                  <label>Cod. Cliente</label>

                  <div class="input-group">

                      <span class="input-group-addon"><i class="fa fa-key"></i></span>

                      <input type="text" class="form-control input-sm" id="codCliCta" name="codCliCta" readonly>

                  </div>

              </div>

              <!-- ENTRADA PARA EL NOMBRE DEL CLIENTE-->

              <div class="form-group col-lg-8">

                  <label>Cliente</label>

                  <div class="input-group">

                      <span class="input-group-addon"><i class="fa fa-key"></i></span>

                      <input type="text" class="form-control input-sm" id="nomCliCta" name="nomCliCta" readonly>

                  </div>

              </div>


              <!-- ENTRADA PARA EL TIPO DOCUMENTO DEL CLIENTE-->

              <div class="form-group col-lg-4">

                  <label>Tipo Documento</label>

                  <div class="input-group">

                      <span class="input-group-addon"><i class="fa fa-key"></i></span>

                      <input type="text" class="form-control input-sm" id="tipDocCta" name="tipDocCta" readonly>

                  </div>

              </div>

              <!-- ENTRADA PARA EL NUMERO DOCUMENTO DEL CLIENTE-->

              <div class="form-group col-lg-4">

                  <label>Nro. Documento</label>

                  <div class="input-group">

                      <span class="input-group-addon"><i class="fa fa-key"></i></span>

                      <input type="text" class="form-control input-sm" id="nroDocCta" name="nroDocCta" readonly>

                  </div>

              </div>

              <!-- ENTRADA PARA EL NUMERO DOCUMENTO DEL CLIENTE-->

              <div class="form-group col-lg-4">

                  <label>Zona</label>

                  <div class="input-group">

                      <span class="input-group-addon"><i class="fa fa-key"></i></span>

                      <input type="text" class="form-control input-sm" id="zonaCta" name="zonaCta" readonly>

                  </div>

              </div>              

          </div>                

                    <div class="panel">Cuenta</div>

                        <select type="text" class="form-control input-sm selectpicker" name="formaPagoCta" id="formaPagoCta" data-live-search="true"  required>
                        <option value="">Seleccionar Cuenta</option>

                            <?php

                            $valor = "07";

                            $documentos = ControladorPedidos::ctrTraerCuentas($valor);
                            foreach ($documentos as $key => $value) {
                                echo '<option value="' . $value["codigo"] . '">' . $value["cuenta"] . '</option>';
                            }

                            ?>

                        </select>


                </div>

            </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

        <button type="submit" class="btn btn-primary">Guardar cambioss</button>

        </div>

      </form>
      <?php

      $cuenta = new ControladorFacturacion();
      $cuenta -> ctrAsignarCuenta();

      ?>
    </div>

  </div>

</div>

<script>
window.document.title = "NC / ND | Vasco System";
</script>