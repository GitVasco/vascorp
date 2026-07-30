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
          <i class="fa fa-plus-square"></i>
            Agregar modelo

        </button>
        <div class="pull-right">
          <button class="btn btn-outline-success btnReporteModelos" style="border:green 1px solid">
          <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Modelos  </button>
        </div>
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
              <th>Und.</th>
              <th>linea</th>
              <th>Operaciones</th>
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
MODAL VER MODELO
======================================-->

<div id="modalVerModelo" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">


        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Visualizar modelo</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

            <!-- ENTRADA PARA VISUALIZAR FOTO -->

            <div class="form-group">
              <div class="panel text-center"><h3 class="titulo"></h3></div>
              <div align="center" style="border:3px solid black">
              <img  class="img-thumbnail previsualizar" width="300px">
              </div>
              

            </div>
            <div id="scroll">
              <table class="table tablaDetalleModelo" width="100%">
                <thead>
                
                  <th class="text-center">Modelo</th>
                  <th class="text-center">Nombre</th>
                  <th class="text-center">Color</th>
                  <th class="text-center">Talla</th>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>


        </div>



    </div>

  </div>

</div>

<!--=====================================
MODAL AGREGAR MODELO
======================================-->

<div id="modalAgregarModelo" class="modal fade" role="dialog">

  <div class="modal-dialog">

    <div class="modal-content">

      <form role="form"  method="post" enctype="multipart/form-data">

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
                    if($value["venta"]== 1 && $value["marca"]!="MEDIAS"){
                    echo '<option value="' . $value["id"] . '">' . $value["marca"] . '</option>';
                    }
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

                  <option value="">Seleccionar tipo</option>

                </select>

              </div>

            </div>

            <!-- UNIDAD DE MEDIDA (FE) -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-balance-scale"></i></span>

                <select class="form-control input-lg" id="nuevaUnidadMedida" name="nuevaUnidadMedida" required>

                  <?php
                  $unidades = ControladorUnidadMedidas::ctrMostrarUnidadMedidas(null, null);
                  foreach ($unidades as $unidad) {
                    $sel = ($unidad["codigo"] === "C62") ? " selected" : "";
                    echo '<option value="' . htmlspecialchars($unidad["codigo"]) . '"' . $sel . '>'
                      . htmlspecialchars($unidad["codigo"] . " — " . $unidad["descripcion"])
                      . '</option>';
                  }
                  ?>

                </select>

              </div>
              <p class="help-block" style="margin:6px 0 0">Facturación electrónica. Por defecto: C62 — PIEZAS.</p>

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
MODAL EDITAR MODELO
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
                $item=null;
                $valor=null;
                    
                    $marcas = ControladorMarcas::ctrMostrarMarcas($item,$valor);
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

                <select class="form-control input-lg" id="editarTipo" name="editarTipo" required readonly>

                  <option value=""></option>

                </select>

              </div>

            </div>

            <!-- UNIDAD DE MEDIDA (FE) -->

            <div class="form-group">

              <div class="input-group">

                <span class="input-group-addon"><i class="fa fa-balance-scale"></i></span>

                <select class="form-control input-lg" id="editarUnidadMedida" name="editarUnidadMedida" required>

                  <?php
                  $unidadesEdit = ControladorUnidadMedidas::ctrMostrarUnidadMedidas(null, null);
                  foreach ($unidadesEdit as $unidad) {
                    echo '<option value="' . htmlspecialchars($unidad["codigo"]) . '">'
                      . htmlspecialchars($unidad["codigo"] . " — " . $unidad["descripcion"])
                      . '</option>';
                  }
                  ?>

                </select>

              </div>
              <p class="help-block" style="margin:6px 0 0">Facturación electrónica (SUNAT).</p>

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
          
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
  
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

<!--=====================================
MODAL AGREGAR MODELO
======================================-->

<div id="modalVerPrecio" class="modal fade" role="dialog">

  <div class="modal-dialog modal-sm" >

    <div class="modal-content" >

      <form role="form"  method="post" enctype="multipart/form-data">

        <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

        <div class="modal-header" style="background:#3c8dbc; color:white">

          <button type="button" class="close" data-dismiss="modal">&times;</button>

          <h4 class="modal-title">Lista de Precios</h4>

        </div>

        <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

        <div class="modal-body">

          <div class="box-body">

          <div class="form-group">
            <label for="">Modelo</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>
                <input type="text" class="form-control input-md" name="modelo" id="modelo" readonly>
              </div>
            </div>

            <div class="form-group">
            <label for="">Descripcion</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>
                <input type="text" class="form-control input-md" name="descModelo" id="descModelo" readonly>
              </div>
            </div>
            <!-- ENTRADA PARA LISTAR PRECIOS -->
              <table class="tablaDetallePrecio" width="100%">
                <thead>
                <tr>
                  <th style="width:40px;text-align:center">N°</th>
                  <th style="width:100px">S/</th>
                  <th style="width:120px"></th>
                </tr>
                </thead>
                <tbody>
             
                </tbody>
              </table>

          </div>

        </div>

        <!--=====================================
        PIE DEL MODAL
        ======================================-->

        <div class="modal-footer">

          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

          <button type="submit" class="btn btn-primary">Guardar Precio</button>

        </div>

      </form>
      
      <?php
      
        $editarPrecio= new ControladorModelos();
        $editarPrecio->ctrEditarPrecio();
     
      ?>


    </div>

  </div>

</div>
<script>
window.document.title = "Modelos"
</script>