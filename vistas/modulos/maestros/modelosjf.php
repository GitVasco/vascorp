<div class="content-wrapper">

  <section class="content-header">

    <h1>

      Administrar modelos

    </h1>

    <ol class="breadcrumb">

      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

      <li class="active">Administrar modelos</li>

    </ol>

  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarModelo">

          Agregar modelo

        </button>

      </div>

      <div class="box-body">
        <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
        <table class="table table-bordered table-striped dt-responsive tablaModelos" width="100%"> 

          <thead>

            <tr>

              <th style="width:10px">#</th>
              <th>Imagen</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Nombre</th>
              <th>Estado</th>
              <th>Tipo</th>
              <th>linea</th>
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
MODAL AGREGAR ARTICULO
======================================-->

<div id="modalAgregarModelo" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Agregar modelo</h4>

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

            <!-- ENTRADA PARA TIPO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>

                <select class="form-control input-lg" id="nuevoTipo" name="nuevoTipo">

                  <option value="">Selecionar tipo</option>

                  <option value="BRASIER">BRASIER</option>

                  <option value="TRUSA">TRUSA</option>

                  <option value="TOP">TOP</option>

                  <option value="BODY">BODY</option>

                  <option value="BOXER V">BOXER V</option>

                  <option value="BVD NIÑOS">BVD NIÑOS</option>

                  <option value="GUAPITAS">GUAPITAS</option>

                  <option value="SK">SK</option>

                </select>

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

          <button type="submit" class="btn btn-primary">Guardar modelo</button>

        </div>

      </form>
      
      <?php

      $crearModelo = new ControladorModelos();
      $crearModelo->ctrCrearModelo();

      ?>


    </div>

  </div>

</div>


<!--=====================================
MODAL EDITAR ARTICULO
======================================-->

<div id="modalEditarModelo" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form" method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Editar modelo</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">


            <!-- ENTRADA PARA SELECCIONAR MARCA -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-th"></i></span>

                <select class="form-control input-lg selectpicker" id="editarMarca" name="editarMarca" data-live-search="true" required readonly>

                <?php
                    
                    $marcas = ControladorMarcas::ctrMostrarMarcas();
                    //var_dump("marcas", $marcas);

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

                <input type="text" class="form-control input-lg" id="editarModelo" name="editarModelo" placeholder="Ingresar modelo" required >

              </div>

            </div>

            <!-- ENTRADA PARA LA DESCRIPCIÓN -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-product-hunt"></i></span>

                <input type="text" class="form-control input-lg" id="editarDescripcion" name="editarDescripcion" placeholder="Ingresar nombre" required>

              </div>

            </div>


            <!-- ENTRADA PARA TIPO -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>

                <select class="form-control input-lg" name="editarTipo" required readonly>

                  <option id="editarTipo"></option>

                </select>

              </div>

            </div>

            <!-- ENTRADA PARA SUBIR FOTO -->

            <div class="form-group">

              <div class="panel">SUBIR IMAGEN</div>

              <input type="file" class="nuevaImagen" name="editarImagen">

              <p class="help-block">Peso máximo de la imagen 2MB</p>

              <img src="vistas/img/articulos/default/anonymous.png" class="img-thumbnail previsualizar" width="100px">

              <input type="hidden" name="imagenActual" id="imagenActual">

            </div>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->


        <?php

        if ($_SESSION["perfil"] == "Logistica") {

          echo '<div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          
                  </div>';
        } else {

          echo '<div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          
                    <button type="submit" class="btn btn-primary">Guardar artículo</button>
  
                  </div>';
        }
        ?>

      </form>
      <?php

      $editarModelo = new ControladorModelos();
      $editarModelo->ctrEditarModelo();

      ?>
    </div>

  </div>

</div>


<?php

$eliminarModelo = new ControladorModelos();
$eliminarModelo->ctrEliminarModelo();

?>