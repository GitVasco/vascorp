<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar Artículos

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar artículos</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

            </div>
            <div class="box-header">
                <div class="pull-right">
                    <button class="btn btn-outline-success btnReporteArt" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Articulos </button>

                    <button type="button" class="btn btn-success" id="saldosArticulos" name="saldosArticulos" data-toggle="modal" data-target="#modalSaldosMes">
                        Saldos a una Fecha
                    </button>
                </div>

            </div>
            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">

                <table class="table table-bordered table-striped dt-responsive tablaArticulos" width="100%">

                    <thead>

                        <tr>

                            <th style="width:10px">#</th>
                            <th>Imagen</th>
                            <th>Artículo</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Stock</th>
                            <th>Stock 01</th>
                            <th>Stock 05</th>
                            <th>Promedio</th>
                            <th>Tarjeta</th>
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

                                    $valor = null;

                                    $colores = ControladorColores::ctrMostrarColores($valor);

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


<!--=====================================
MODAL EDITAR ARTICULO
======================================-->

<div id="modalEditarArticulo" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar articulo</h4>

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
                                    $valor = null;
                                    $marcas = ControladorMarcas::ctrMostrarMarcas($valor);
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

                                <input type="text" class="form-control input-lg" id="editarModelo" name="editarModelo" placeholder="Ingresar modelo" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA DESCRIPCIÓN -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-product-hunt"></i></span>

                                <input type="text" class="form-control input-lg" id="editarDescripcion" name="editarDescripcion" placeholder="Ingresar nombre" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA SELECCIONAR COLOR -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-dashboard"></i></span>

                                <select class="form-control input-lg" name="editarColor" required readonly>

                                    <option id="editarColor"></option>


                                </select>

                            </div>

                        </div>



                        <!-- ENTRADA PARA TALLAS -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-tag"></i></span>

                                <select class="form-control input-lg" name="editarTalla" required readonly>

                                    <option id="editarTalla"></option>

                                </select>

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

                        <!-- ENTRADA PARA EL CÓDIGO -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-code"></i></span>

                                <input type="text" class="form-control input-lg" id="editarCodigo" name="editarCodigo" placeholder="Ingresar código" readonly required>

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

            $editarArticulo = new ControladorArticulos();
            $editarArticulo->ctrEditarArticulo();

            ?>
        </div>

    </div>

</div>

<!--------------------------------
* Modal para saldos
--------------------------------->
<div id="modalSaldosMes" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" id="formularioSaldosArticulos">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Saldos a una fecha</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <div class="form-group col-lg-6">

                            <label>Fecha Fin</label>

                            <input type="date" class="form-control" id="fFin" name="fFin" min="2021-01-01" value="<?= date('Y-m-d') ?>">

                        </div>

                        <div class="form-group col-lg-6">
                            <label for="conGuias">Incluir</label>
                            <div class="form-control form-check">
                                <input class="form-check-input" type="checkbox" id="conGuias" name="conGuias" checked>
                                <label class="form-check-label" for="conGuias">
                                    Guias
                                </label>
                            </div>
                        </div>

                    </div>

                </div>

                <!--=====================================
                PIE DEL MODAL
                ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="button" id="generarSaldoArt" name="generarSaldoArt" class="btn btn-primary btnGenerarSaldoArt">Exportar</button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php

$eliminarArticulo = new ControladorArticulos();
$eliminarArticulo->ctrEliminarArticulo();

?>




<script>
    window.document.title = "Articulos"
</script>