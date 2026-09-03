<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear Orden de Corte

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear orden de corte</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
      EL FORMULARIO
      ======================================-->

            <div class="col-lg-4 col-xs-12">

                <div class="box box-success">

                    <div class="box-header with-border"></div>

                    <form role="form" method="post" class="formularioOrdenCorte" id="formOrdenCorte">

                        <div class="box-body">

                            <div class="box">

                                <!--=====================================
                ENTRADA DEL VENDEDOR
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                        <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo $_SESSION["nombre"]; ?>" readonly>

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


                                        $codigo = ControladorOrdenCorte::ctrSiguienteCodigoOC();

                                        echo '<input type="text" class="form-control" id="nuevaOrdenCorte" name="nuevaOrdenCorte" value="' . $codigo . '" readonly title="Se confirma al guardar (máximo + 1)">';

                                        ?>

                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL ARTICULO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-percent"></i></span>

                                        <?php

                                        $configuracion = controladorArticulos::ctrConfiguracion();

                                        $urgencia = $configuracion["urgencia"];

                                        /* var_dump("urgencia", $urgencia); */

                                        echo '<input type="number" class="form-control" id="configuracion" name="configuracion" value="' . $urgencia . '" step="any" readonly>';

                                        ?>

                                        <span class="input-group-addon">

                                            <button type="button" class="btn btn-primary btn-xs" style="margin-left: 5 !important;" data-toggle="modal" data-target="#modalConfigurarUrgencia" data-dismiss="modal">Configurar Porcentaje</button><button type="button" class="btn btn-info btn-xs tablaMP" id="tablaMP">Actualizar Tabla</button>

                                        </span>

                                    </div>

                                </div>

                                <!--=====================================
                  ENTRADA BUSCADOR
                  ======================================-->

                                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">

                                            <input type="text" class="form-control " id="buscadorOc" name="buscadorOc" />
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>

                                </div>

                                <!--=====================================
                  TITULOS
                  ======================================-->

                                <div class="box box-primary">

                                    <div class="row">

                                        <div class="col-xs-8">

                                            <label>Articulo</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Ord. Corte</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Mes</label>

                                        </div>

                                    </div>

                                </div>


                                <!--=====================================
                ENTRADA PARA AGREGAR MATERIAPRIMA
                ======================================-->

                                <div class="form-group row nuevoArticuloOC" style="height:400px;overflow: scroll; overflow-x:hidden">

                                </div>

                                <input type="hidden" id="listaArticulosOC" name="listaArticulosOC">

                                <div class="row">

                                    <!--=====================================
                  ENTRADA IMPUESTOS Y TOTAL
                  ======================================-->

                                    <div class="col-xs-6 pull-right">

                                        <table class="table">

                                            <thead>

                                                <tr>
                                                    <th>Total</th>
                                                </tr>

                                            </thead>

                                            <tbody>

                                                <tr>

                                                    <td style="width: 50%">

                                                        <div class="input-group">

                                                            <span class="input-group-addon"><i class="fa fa-scissors"></i></span>

                                                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalOrdenCorte" name="nuevoTotalOrdenCorte" total="" placeholder="0" readonly required>

                                                            <input type="hidden" name="totalOrdenCorte" id="totalOrdenCorte">


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

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Orden Corte</button>

                            <a href="ordencorte" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $guardarOrdenCorte = new ControladorOrdenCorte();
                    $guardarOrdenCorte->ctrCrearOrdenCorte();

                    ?>


                </div>

            </div>

            <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

            <div class="col-lg-8 hidden-md hidden-sm hidden-xs">

                <div class="col-lg-12 hidden-md hidden-sm hidden-xs">

                    <div class="box box-warning">

                        <div class="box-header with-border"></div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped table-condensed tablaArticulosOrdenCorte" width="100%">

                                <thead>

                                    <tr>

                                        <th style="width:10px">+</th>
                                        <th>Modelo</th>
                                        <th>Color</th>
                                        <th>Talla</th>
                                        <th>Proy</th>
                                        <th>Prod</th>
                                        <th>Avance</th>
                                        <th>Stock</th>
                                        <th>Ped.</th>
                                        <th>Taller</th>
                                        <th>Alm. Cor.</th>
                                        <th>Ord. Cor.</th>
                                        <th>Vtas 30d</th>
                                        <th>Mes Dura</th>
                                        <th>Faltante</th>

                                    </tr>

                                </thead>



                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-lg-12 hidden-md hidden-sm hidden-xs">

                    <div class="box box-warning">

                        <div class="box-header with-border"></div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped table-condensed tablaMPOrdenCorte" width="100%">

                                <thead>

                                    <tr>

                                        <th style="width:20px">CodPro</th>
                                        <th style="width:25px">CodFab</th>
                                        <th style="width:400px">Descripcion</th>
                                        <th>Color</th>
                                        <th>Talla</th>
                                        <th>Consumo</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                    </tr>

                                </thead>



                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL CONFIGURAR % DE URGENCIAS
======================================-->

<div id="modalConfigurarUrgencia" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Configurar Porcentaje</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA PORCENTAJE -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-tag"></i></span>

                                <input type="text" class="form-control input-md" name="urgencia" id="urgencia" required>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Confirmar Porcentaje</button>

                </div>

            </form>

            <?php

            $configurarUrgencia = new controladorArticulos();
            $configurarUrgencia->ctrConfigurarUrgencia();

            ?>


        </div>

    </div>

</div>


<script>
    window.document.title = "Crear orden de corte"
</script>

<script>
    $('.nuevoArticuloOC').ready(function() {
        $('#buscadorOc').keyup(function() {

            console.log("hola mundo");

            var nombres = $('.nuevaDescripcionProducto');
            //console.log(nombres.val())
            //console.log(nombres.length())

            var buscando = $(this).val();
            //console.log(buscando.length);

            var item = '';

            for (var i = 0; i < nombres.length; i++) {

                item = $(nombres[i]).val();
                item2 = $(nombres[i]).val().toLowerCase();
                // console.log(item);

                for (var x = 0; x < item.length; x++) {

                    if (buscando.length == 0 || item.indexOf(buscando) > -1 || item2.indexOf(buscando) > -1) {

                        $(nombres[i]).parents('.munditoOC').show();

                    } else {

                        $(nombres[i]).parents('.munditoOC').hide();

                    }
                }
            }
        });
    });

    // COPILOT: evitar que haga submit al presionar enter el formulario de crear orden de corte
    $(document).ready(function() {
        $(window).keydown(function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    });
</script>