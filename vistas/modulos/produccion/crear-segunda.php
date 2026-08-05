<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear Segunda

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear segunda</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
      EL FORMULARIO
      ======================================-->

            <div class="col-lg-5 col-xs-12">

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
                ENTRADA DEL ARTICULO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                                        <select class="form-control  input-sm selectpicker" name="nuevoTalleres" id="nuevoTalleres" data-live-search="true">

                                            <?php

                                            $sectoresInternos = ControladorSectores::ctrSectoresPorTipo(0);
                                            $sectoresExternos = ControladorSectores::ctrSectoresPorTipo(1);

                                            echo '<optgroup label="Taller (interno)">';
                                            foreach ($sectoresInternos as $value) {
                                                echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '">'
                                                    . htmlspecialchars($value["cod_sector"] . "-" . $value["nom_sector"])
                                                    . '</option>';
                                            }
                                            echo '</optgroup>';

                                            echo '<optgroup label="Servicio (externo)">';
                                            foreach ($sectoresExternos as $value) {
                                                echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '">'
                                                    . htmlspecialchars($value["cod_sector"] . "-" . $value["nom_sector"])
                                                    . '</option>';
                                            }
                                            echo '</optgroup>';

                                            ?>
                                        </select>

                                    </div>
                                    <input type="hidden" id="nuevoTipoSector" name="nuevoTipoSector">
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

                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <select class="form-control  input-sm selectpicker" name="nuevoTrabajadores" id="nuevoTrabajadores" data-live-search="true">
                                            <option value="">Seleccionar un trabajador</option>
                                            <?php

                                            $sector = ControladorTrabajador::ctrMostrarTrabajador(null, null);
                                            foreach ($sector as $key => $value) {

                                                echo '<option value="' . $value["cod_tra"] . '">' . $value["nom_tra"] . " " . $value["ape_pat_tra"] . " " . $value["ape_mat_tra"] . '</option>';
                                            }



                                            ?>
                                        </select>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-exclamation-triangle text-red"></i></span>
                                        <select type="text" class="form-control input-sm selectpicker" name="tipoMovimiento" id="tipoMovimiento" data-live-search="true" required>
                                            <option value="">Seleccionar tipo de Movimiento</option>

                                            <?php

                                            $item = "tipo_dato";
                                            $valor = "TTOP";

                                            $codigos = array("S16", "S25", "S26", "S27", "S32");

                                            $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                            foreach ($documentos as $key => $value) {

                                                if (in_array($value["codigo"], $codigos)) {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                }
                                            }

                                            ?>

                                        </select>

                                    </div>

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

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Segunda</button>

                            <a href="ingresos" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $guardarSegunda = new ControladorIngresos();
                    $guardarSegunda->ctrCrearSegunda();

                    ?>


                </div>

            </div>

            <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

            <div class="col-lg-7 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped table-condensed tablaArticulosTalleres" width="100%">

                            <thead>

                                <tr>
                                    <th class="text-center">Guia</th>
                                    <th class="text-center">Modelo</th>
                                    <th class="text-center">Color</th>
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
    window.document.title = "Crear segunda";
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
        var datos2 = new FormData();
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
        })
    })
</script>