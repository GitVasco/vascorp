<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Crear producción de Cuadros

    </h1>

    <ol class="breadcrumb">

      <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Crear producción de Cuadros</li>

    </ol>

  </section>

  <section class="content">

    <div class="row">

      <!--=====================================
      EL FORMULARIO
      ======================================-->

      <div class="col-lg-6 col-xs-12">

        <div class="box box-success">

          <div class="box-header with-border"></div>

          <form role="form" method="post" class="formularioCuadros">

            <div class="box-body">

              <div class="box">

                <?php 
                    date_default_timezone_set('America/Lima');
                    $fecha = new DateTime();
                ?>

                <!--=====================================
                ENTRADA DEL RESPONSABLE
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-user"></i></span>

                    <input type="text" class="form-control" id="responsable" name="responsable"
                      value="<?php echo $_SESSION["nombre"]; ?>" readonly>

                  </div>

                </div>

                <!-- TIPO DE MOVIMIENTO -->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>

                    <input type="text" class="form-control" id="nomMov" name="nomMov"
                      value="Producción de Cuadros" readonly>

                  </div>

                </div>                

                <!--=====================================
                ENTRADA DEL CODIGO
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                    <?php

                        $tipo = "PCUA";

                        $correlativo = ControladorMateriaPrima::ctrCorrelativoNuevo($tipo);

                        echo '<input type="text" class="form-control" id="correlativo" name="correlativo" value="'.$correlativo["correlativo"].'" readonly>';


                    ?>

                  </div>

                </div>

                <!-- FECHA -->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                    <input type="date" class="form-control" id="fecha" name="fecha"
                      value="<?php echo $fecha->format("Y-m-d"); ?>">

                  </div>

                </div> 

                <!--=====================================
                        TITULOS
                ======================================-->

                <div class="box box-primary">

                  <div class="row">

                    <div class="col-xs-2">

                      <label>CodPro</label>

                    </div>

                    <div class="col-xs-4">

                      <label>Descripcion</label>

                    </div>

                    <div class="col-xs-4">

                      <label>Color</label>

                    </div>

                    <div class="col-xs-2">

                      <label>Cantidad</label>

                    </div>

                  </div>

                </div>                 

                <!--=====================================
                ENTRADA PARA AGREGAR PRODUCTO
                ======================================-->

                <div class="form-group row nuevoCuadro">


                </div>

                <input type="hidden" id="listaCuaMp" name="listaCuaMp">


                <div class="row">

                  <!--=====================================
                  ENTRADA IMPUESTOS Y TOTAL
                  ======================================-->

                  <div class="col-xs-4 pull-right">

                    <table class="table">

                      <thead>

                        <tr>
                          <th>Total</th>
                        </tr>

                      </thead>

                      <tbody>

                        <tr>

                          <td style="width: 10%">

                            <div class="input-group">

                                <input type="number" class="form-control" min="0" id="nuevoTotal" name="nuevoTotal" placeholder="0" required readonly>
                                <span class="input-group-addon"><i class="fa fa-rocket"></i></span>

                                <input type="hidden" name="totalCua" id="totalCua">                                                            

                            </div>

                          </td>

                        </tr>

                      </tbody>

                    </table>

                  </div>

                </div>

                <hr>

                <br>

              </div>

            </div>

            <div class="box-footer">

              <button type="submit" class="btn btn-primary pull-right">Guardar cuadro</button>

              <a href="tabla-produccion"  class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>

            </div>

          </form>

            <?php

            $crearCuadros = new ControladorMateriaPrima();
            $crearCuadros -> ctrCrearCuadrosProd();

            ?>        

        </div>

      </div>

      <!--=====================================
      LA TABLA DE PRODUCTOS
      ======================================-->

      <div class="col-lg-6 hidden-md hidden-sm hidden-xs">

        <div class="box box-warning">

          <div class="box-header with-border"></div>

          <div class="box-body">

            <table class="table table-bordered table-striped dt-responsive tablaAlmacen01CUA" width="100%">

              <thead>

                <tr>
                  <th style="width: 10px">CodPro</th>
                  <th>Descripcion</th>
                  <th>Color</th>
                  <th>Stock</th>
                  <th>#</th>
                </tr>

              </thead>


            </table>

          </div>

        </div>


      </div>

    </div>

  </section>

</div>

<script>
window.document.title = "Prod. Cuadros"
</script>