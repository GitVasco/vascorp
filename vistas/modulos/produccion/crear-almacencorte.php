<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear Corte

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear corte</li>

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

                    <form role="form" method="post" class="formularioAlmacenCorte">

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

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-file-text"></i></span>

                                        <input type="number" class="form-control" id="nuevaGuia" name="nuevaGuia" placeholder="Guia de Corte.." required>

                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                        <?php

                                        $codigo = ControladorAlmacenCorte::ctrSiguienteCodigoAC();

                                        echo '<input type="text" class="form-control" id="nuevaAlmacenCorte" name="nuevaAlmacenCorte" value="' . $codigo . '" readonly title="Se confirma al guardar">';


                                        ?>

                                    </div>



                                </div>

                                <!--=====================================
                  ENTRADA BUSCADOR
                  ======================================-->

                                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">

                                            <input type="text" class="form-control " id="buscadorAc" name="buscadorAc" />
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>

                                </div>

                                <!--=====================================
                TITULOS
                ======================================-->

                                <div class="box box-primary">

                                    <div class="row">

                                        <div class="col-xs-2">

                                            <label>OC</label>

                                        </div>

                                        <div class="col-xs-5">

                                            <label>Artículo</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label>Cantidad</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label>Saldo</label>

                                        </div>

                                        <div class="col-xs-1">

                                            <label>Cerrar</label>

                                        </div>

                                    </div>

                                </div>


                                <!--=====================================
                ENTRADA PARA AGREGAR MATERIAPRIMA
                ======================================-->

                                <div class="form-group row nuevoArticuloAC" style="height:400px;overflow: scroll; overflow-x:hidden">


                                </div>

                                <input type="hidden" id="listaArticulosAC" name="listaArticulosAC">
                                <input type="hidden" id="listArticulo" name="listArticulo">
                                <input type="hidden" id="listCantidad" name="listCantidad">

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

                                                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalAlmacenCorte" name="nuevoTotalAlmacenCorte" total="" placeholder="0" readonly required>

                                                            <input type="hidden" name="totalAlmacenCorte" id="totalAlmacenCorte">


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

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Corte</button>

                            <a href="almacencorte" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $crearAlmacenCorte = new ControladorAlmacenCorte();
                    $crearAlmacenCorte->ctrCrearAlmacenCorte();

                    ?>


                </div>

            </div>

            <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

            <div class="col-lg-5 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped table-condensed tablaArticulosAlmacenCorte" width="100%">

                            <thead>

                                <tr>
                                    <th style="width:10px">Acciones</th>
                                    <th>OC</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Cantidad</th>
                                    <th>Saldo</th>
                                    <th>Alm. Corte</th>

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
    window.document.title = "Crear cortes"
</script>

<script>
    $('.nuevoArticuloAC').ready(function() {
        $('#buscadorAc').keyup(function() {


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

                        $(nombres[i]).parents('.munditoAC').show();

                    } else {

                        $(nombres[i]).parents('.munditoAC').hide();

                    }
                }


            }


        });
    });
</script>