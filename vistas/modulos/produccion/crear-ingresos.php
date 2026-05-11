<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear ingresos

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear ingreso</li>

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

                    <form role="form" method="post" class="formularioIngreso">

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
                ENTRADA DE GUIA
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-file-text"></i></span>
                                        <input type="text" class="form-control" id="nuevaGuiaIng" name="nuevaGuiaIng" placeholder="Ingresar guia" required>


                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="text" class="form-control" id="nuevoCodigo" name="nuevoCodigo" readonly>


                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="nuevaFecha" name="nuevaFecha" value="<?php date_default_timezone_set('America/Lima');
                                                                                                                            echo date("Y-m-d"); ?>" required>


                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL ARTICULO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                                        <select class="form-control  input-sm selectpicker" name="nuevoTalleres" id="nuevoTalleres" data-live-search="true">

                                            <?php

                                            // creamos un array para configurar los talleres
                                            $taller = ["T0", "T1", "T2", "T3", "T4", "T5", "T6", "T8", "T9", "TA", "TB", "TC", "TD", "T11", "TE", 'T12', 'T13'];

                                            $sector = ControladorSectores::ctrMostrarSectores(null);
                                            foreach ($sector as $key => $value) {

                                                // validamos que el sector sea un taller 
                                                if (in_array($value["cod_sector"], $taller)) {
                                                    echo '<option value="' . $value["cod_sector"] . '">' . $value["cod_sector"] . "-" . $value["nom_sector"] . '</option>';
                                                }
                                            }



                                            ?>
                                        </select>

                                    </div>
                                    <input type="hidden" id="nuevoTipoSector" name="nuevoTipoSector">
                                </div>

                                <!--=====================================
                ENTRADA BUSCADOR
                ======================================-->

                                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">

                                            <input type="text" class="form-control " id="buscadorIngreso" name="buscadorIngreso" />
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>

                                </div>


                                <!--=====================================
                TITULOS
                ======================================-->

                                <div class="box box-primary">

                                    <div class="row">

                                        <div class="col-xs-6">

                                            <label>Articulo</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">En Taller</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Saldo</label>

                                        </div>


                                        <div class="col-xs-2">

                                            <label for="">Corte</label>

                                        </div>

                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA PARA AGREGAR MATERIAPRIMA
                ======================================-->

                                <div class="form-group row nuevoArticuloIngreso" style="height:400px;overflow: scroll; overflow-x:hidden">


                                </div>

                                <input type="hidden" id="listaArticulosIngreso" name="listaArticulosIngreso">

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

                                                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalTaller" name="nuevoTotalTaller" total="" placeholder="0" readonly required>

                                                            <input type="hidden" name="totalTaller" id="totalTaller">


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

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Ingreso</button>

                            <a href="ingresos" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $guardarIngreso = new ControladorIngresos();
                    $guardarIngreso->ctrCrearIngresoC();

                    ?>


                </div>

            </div>

            <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

            <div class="col-lg-6 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped table-condensed tablaArticulosTalleres" width="100%">

                            <thead>

                                <tr>
                                    <th class="text-center">Guia</th>
                                    <th class="text-center">Modelo</th>
                                    <th class="text-center" style="width:150px">Color</th>
                                    <th class="text-center">Talla</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">En Taller</th>
                                    <th class="text-center">Alm. Corte</th>
                                    <th class="text-center">Ord. Corte</th>
                                    <th class="text-center">Acciones</th>
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
    window.document.title = "Crear ingreso";
    localStorage.setItem("sectorIngreso", null);
</script>

<script>
    $('.nuevoArticuloIngreso').ready(function() {
        $('#buscadorIngreso').keyup(function() {


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

                        $(nombres[i]).parents('.munditoIngreso').show();

                    } else {

                        $(nombres[i]).parents('.munditoIngreso').hide();

                    }
                }


            }


        });
    });


    $(document).ready(function() {

        var ingreso = $("#nuevoTalleres").val();
        $(".tablaArticulosTalleres").DataTable().destroy();
        localStorage.setItem("sectorIngreso", ingreso);
        cargarTablaArticuloTalleres(localStorage.getItem("sectorIngreso"));
        /* var datos2 = new FormData();
        datos2.append("ingreso", ingreso);
        $.ajax({
            url: "ajax/talleres.ajax.php",
            method: "POST",
            data: datos2,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(respuesta2) {
                //   console.log(respuesta);
                $("#nuevoCodigo").val(ingreso + ("000" + respuesta2["ultimo_codigo"]).slice(-4));
            }
        }) */


        var random = Math.floor(Math.random() * 90000) + 10000;;

        $("#nuevoCodigo").val(ingreso + random);


    })
</script>