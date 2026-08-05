<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear arreglos

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear arreglo</li>

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
                                        <select class="form-control  input-sm selectpicker" name="nuevoTalleresCrearArreglos" id="nuevoTalleresCrearArreglos" data-live-search="true">

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

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Arreglos</button>

                            <a href="arreglos" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $guardarArreglo = new ControladorArreglos();
                    $guardarArreglo->ctrCrearArreglos();

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
    window.document.title = "Crear arreglo";
    localStorage.setItem("sectorIngreso", null);
</script>

<script>
    $(document).ready(function() {

        var ingreso = $("#nuevoTalleresCrearArreglos").val();
        $(".tablaArticulosTalleres").DataTable().destroy();
        localStorage.setItem("sectorIngreso", ingreso);
        cargarTablaArticuloTalleres(localStorage.getItem("sectorIngreso"));


        var random = Math.floor(Math.random() * 90000) + 10000;;

        $("#nuevoCodigo").val("A" + ingreso + random);


    })
</script>