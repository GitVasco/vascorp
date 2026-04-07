<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Crear Tarjeta

    </h1>

    <ol class="breadcrumb">

      <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Crear tarjeta</li>

    </ol>

  </section>

  <section class="content">

    <div class="row">

      <!--=====================================
      EL FORMULARIO
      ======================================-->

      <div class="col-lg-7 col-xs-12">

        <div class="box box-success">

          <div class="box-header with-border"></div>

          <form role="form" method="post" class="formularioMateriaPrima">

            <div class="box-body">

              <div class="box">

                <!--=====================================
                ENTRADA DEL VENDEDOR
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-user"></i></span>

                    <input type="text" class="form-control" id="nuevoUsuario" name="nuevoUsuario" value="<?php echo $_SESSION["nombre"]; ?>" readonly>

                    <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">

                  </div>

                </div>

                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                    <?php


                    $ult_codigo = ControladorTarjetas::ctrUltimoCodigoTarjeta();

                    /* var_dump("ult_codigo", $ult_codigo); */

                    if (!$ult_codigo) {

                      echo '<input type="text" class="form-control" id="nuevaTarjeta" name="nuevaTarjeta" value="10001" readonly>';
                    } else {

                      foreach ($ult_codigo as $key => $value) { }

                      $codigo = $value["ultimo_codigo"] + 1;

                      /* 
                        ! como esta sumando el codigo
                        */

                      /* var_dump("ultimo_codigo", $ult_codigo);
                        var_dump("ultimo_codigo", $codigo); */



                      echo '<input type="text" class="form-control" id="nuevaTarjeta" name="nuevaTarjeta" value="' . $codigo . '" readonly>';
                    }

                    ?>

                  </div>

                </div>

                <!--=====================================
                ENTRADA DEL ARTICULO
                ======================================-->

                <div class="form-group">

                  <div class="input-group">

                    <span class="input-group-addon"><i class="fa fa-users"></i></span>

                    <select class="form-control selectpicker" id="seleccionarArticulo" name="seleccionarArticulo" data-live-search="true" data-size="10" required>

                      <option value="">Seleccionar articulo</option>

                      <?php

                      $articulos = controladorArticulos::ctrMostrarSinTarjeta();

                      foreach ($articulos as $key => $value) {

                        echo '<option value="' . $value["articulo"] . '">' . $value["packing"] . '</option>';
                      }

                      ?>

                    </select>

                    <span class="input-group-addon"><button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#modalAgregarArticulo" data-dismiss="modal">Agregar
                        Articulo</button></span>

                  </div>

                </div>

                <!--=====================================
                TITULOS
                ======================================-->

                <div class="box box-primary">

                  <div class="row">

                    <div class="col-xs-6">

                      <label>Materia Prima</label>

                    </div>

                    <div class="col-xs-2">

                      <label for="">Tej. Principal</label>

                    </div>

                    <div class="col-xs-2">

                      <label for="">Consumo</label>

                    </div>

                    <div class="col-xs-2">

                      <label for="">Costo</label>

                    </div>

                  </div>

                </div>

                <!--=====================================
                ENTRADA PARA AGREGAR MATERIAPRIMA
                ======================================-->

                <div class="form-group row nuevaMateriaPrima">


                </div>

                <input type="hidden" id="listaMP" name="listaMP">

                <div class="row">

                  <!--=====================================
                  ENTRADA IMPUESTOS Y TOTAL
                  ======================================-->

                  <div class="col-xs-8 pull-right">

                    <table class="table">

                      <thead>

                        <tr>
                          <th>Impuesto</th>
                          <th>Total</th>
                        </tr>

                      </thead>

                      <tbody>

                        <tr>

                          <td style="width: 50%">

                            <div class="input-group">

                              <input type="number" class="form-control input-lg" min="0" value="18" id="nuevoImpuestoTarjeta" name="nuevoImpuestoTarjeta" placeholder="0" required>

                              <input type="hidden" name="nuevoPrecioImpuesto" id="nuevoPrecioImpuesto" required>

                              <input type="hidden" name="nuevoPrecioNeto" id="nuevoPrecioNeto" required>


                              <span class="input-group-addon"><i class="fa fa-percent"></i></span>

                            </div>

                          </td>

                          <td style="width: 50%">

                            <div class="input-group">

                              <span class="input-group-addon"><i class="fa fa-money"></i></span>

                              <input type="text" min="1" class="form-control input-lg" id="nuevoTotalTarjeta" name="nuevoTotalTarjeta" total="" placeholder="00000" readonly required>

                              <input type="hidden" name="totalTarjeta" id="totalTarjeta">


                            </div>

                          </td>

                        </tr>

                      </tbody>

                    </table>

                  </div>

                </div>

                <hr>

                <!--=====================================
                BOTON GUARDAR
                ======================================-->

                <br>

              </div>

            </div>

            <div class="box-footer">

              <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar tarjeta</button>

              <a href="tarjetas" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
            </div>

          </form>

          <?php

          $guardarTarjeta = new ControladorTarjetas();
          $guardarTarjeta->ctrCrearTarjeta();

          ?>

        </div>

      </div>

      <!--=====================================
      LA TABLA DE PRODUCTOS
      ======================================-->

      <div class="col-lg-5 hidden-md hidden-sm hidden-xs">

        <div class="box box-warning">

          <div class="box-header with-border"></div>

          <div class="box-body">

            <table class="table table-bordered table-striped table-condensed tablaMateriaPrimaTarjetas" width="100%">

              <thead>

                <tr>
                  <th>Línea</th>
                  <th>Cód</th>
                  <th>Descripción</th>
                  <th>Color</th>
                  <th>Unidad</th>
                  <th style="width:10px">Acciones</th>
                </tr>

              </thead>



            </table>

          </div>

        </div>


      </div>

    </div>

  </section>

</div>

<!--=====================================
MODAL AGREGAR ARTICULO
======================================-->

<div id="modalAgregarArticulo" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Agregar articulo</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">


            <!-- ENTRADA PARA SELECCIONAR CATEGORÍA -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-th"></i></span>

                <select class="form-control input-lg" id="nuevaMarca" name="nuevaMarca" required>

                  <option value="">Seleccionar marca</option>

                  <?php

                  $valor = null;

                  $marcas = ControladorMarcas::ctrMostrarMarcas($valor);

                  foreach ($marcas as $key => $value) {

                    echo '<option value="' . $value["id"] . '">' . $value["marca"] . '</option>';
                  }

                  ?>

                </select>

              </div>

            </div>

            <!-- ENTRADA PARA EL MODELO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-paper-plane"></i></span>

                <input type="text" class="form-control input-lg" id="nuevoModelo" name="nuevoModelo" placeholder="Ingresar modelo" required>

              </div>

            </div>

            <!-- ENTRADA PARA LA DESCRIPCIÓN -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-product-hunt"></i></span>

                <input type="text" class="form-control input-lg" id="nuevaDescripcion" name="nuevaDescripcion" placeholder="Ingresar nombre" required>

              </div>

            </div>

            <!-- ENTRADA PARA SELECCIONAR COLOR -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-dashboard"></i></span>

                <select class="form-control input-lg" id="nuevoColor" name="nuevoColor" required>

                  <option value="">Seleccionar color</option>

                  <?php

                  $item = null;
                  $valor = null;

                  $colores = ControladorColores::ctrMostrarColores($item, $valor);

                  foreach ($colores as $key => $value) {

                    echo '<option value="' . $value["cod_color"] . '">' . $value["cod_color"] . ' - ' . $value["nom_color"] . '</option>';
                  }

                  ?>

                </select>

              </div>

            </div>

            <input type="hidden" name="color" id="color">

            <!-- ENTRADA PARA TALLAS -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-tag"></i></span>

                <select class="form-control input-lg" id="nuevaTalla" name="nuevaTalla" required>

                  <option value="">Selecionar talla</option>

                  <option value="1">S</option>

                  <option value="2">M</option>

                  <option value="3">L</option>

                  <option value="4">XL</option>

                  <option value="5">XXL</option>

                  <option value="6">XS</option>

                  <option value="2">4</option>

                  <option value="3">6</option>

                  <option value="4">8</option>

                  <option value="5">10</option>

                  <option value="6">12</option>

                  <option value="7">14</option>

                  <option value="8">16</option>

                  <option value="1">28</option>

                  <option value="2">30</option>

                  <option value="3">32</option>

                  <option value="4">34</option>

                  <option value="5">36</option>

                  <option value="6">38</option>

                  <option value="7">40</option>

                  <option value="8">42</option>

                </select>

              </div>

            </div>

            <input type="hidden" name="talla" id="talla">

            <!-- ENTRADA PARA TIPO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>

                <select class="form-control input-lg" id="nuevoTipo" name="nuevoTipo">

                  <option value="">Selecionar tipo</option>

                  <option value="BRASIER">BRASIER</option>

                  <option value="TRUSA">TRUSA</option>

                  <option value="TOP">TOP</option>

                  <option value="SEAMLESS">SEAMLESS</option>

                  <option value="BODY">BODY</option>

                  <option value="BOXER V">BOXER V</option>

                  <option value="BVD NIÑOS">BVD NIÑOS</option>

                  <option value="GUAPITAS">GUAPITAS</option>

                  <option value="SK">SK</option>

                </select>

              </div>

            </div>

            <!-- ENTRADA PARA EL CÓDIGO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-code"></i></span>

                <input type="text" class="form-control input-lg" id="nuevoCodigo" name="nuevoCodigo" placeholder="Ingresar código" readonly required>

              </div>

            </div>

            <!-- ENTRADA PARA SUBIR FOTO -->

            <div class="form-group">

              <div class="panel">SUBIR IMAGEN</div>

              <input type="file" class="nuevaImagen" name="nuevaImagen">

              <p class="help-block">Peso máximo de la imagen 2MB</p>

              <img src="vistas/img/articulos/default/anonymous.png" class="img-thumbnail previsualizar" width="100px">

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar artículo</button>

        </div>

      </form>
      <?php

      $crearArticulo = new ControladorArticulos();
      $crearArticulo->ctrCrearArticulo();

      ?>


    </div>

  </div>

</div>
<script>
window.document.title = "Crear tarjetas"
</script>