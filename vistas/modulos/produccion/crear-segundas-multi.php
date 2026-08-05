<div class="content-wrapper">

    <section class="content-header">

        <h1>
            Crear segunda <small class="label label-danger">Multi-taller</small>
        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear segunda multi-taller</li>

        </ol>

    </section>

    <section class="content">

        <div class="alert alert-warning">
            <strong>Multi-taller:</strong> al guardar se genera <strong>un documento por cada taller</strong> distinto en las líneas; la <strong>guía</strong>, el <strong>trabajador</strong> y el <strong>tipo de movimiento</strong> son los mismos en todas las cabeceras.
        </div>

        <?php
        $codigosInternos = ControladorSectores::ctrCodigosPorTipo(0);
        $codigosExternos = ControladorSectores::ctrCodigosPorTipo(1);
        $tallerRepresentanteInterno = count($codigosInternos) > 0 ? $codigosInternos[0] : "T1";
        $primerCodigoExterno = count($codigosExternos) > 0 ? $codigosExternos[0] : "";
        ?>

        <div class="row">

            <div class="col-lg-6 col-xs-12">

                <div class="box box-danger">

                    <div class="box-header with-border"></div>

                    <form role="form" method="post" class="formularioSegundaMulti">

                        <div class="box-body">

                            <div class="box">

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
                                        <input type="text" class="form-control" id="nuevaGuiaIng" name="nuevaGuiaIng" placeholder="Ingresar guía (compartida por todos los documentos)" required>


                                    </div>

                                </div>

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="nuevaFecha" name="nuevaFecha" value="<?php date_default_timezone_set('America/Lima');
                                                                                                                            echo date("Y-m-d"); ?>" required>


                                    </div>

                                </div>

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <select class="form-control input-sm selectpicker" name="nuevoTrabajadores" id="nuevoTrabajadores" data-live-search="true" required>
                                            <option value="">Seleccionar un trabajador</option>
                                            <?php
                                            $trabajadores = ControladorTrabajador::ctrMostrarTrabajador(null, null);
                                            foreach ($trabajadores as $value) {
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
                                            foreach ($documentos as $value) {
                                                if (in_array($value["codigo"], $codigos)) {
                                                    echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>

                                    </div>

                                </div>

                                <div class="form-group">

                                    <div id="segundasMultiRep" data-taller-interno="<?php echo htmlspecialchars($tallerRepresentanteInterno); ?>" data-taller-externo="<?php echo htmlspecialchars($primerCodigoExterno); ?>" style="display:none"></div>

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                                        <select class="form-control input-sm selectpicker" name="alcanceProcesoCabecera" id="alcanceProcesoCabeceraSegundaMulti" data-live-search="true" title="Elija proceso">
                                            <option value="externos" selected>Externos</option>
                                            <option value="internos">Internos</option>
                                        </select>

                                    </div>
                                    <input type="hidden" name="nuevoTalleres" id="nuevoTalleres" value="<?php echo htmlspecialchars($primerCodigoExterno); ?>">
                                    <input type="hidden" id="nuevoTipoSector" name="nuevoTipoSector">
                                    <input type="hidden" name="segundaMulti" value="1">
                                    <p class="help-block text-muted" style="margin-top:8px">La tabla lista artículos según Internos o Externos. El <strong>código de documento</strong> se generará en el servidor al guardar.</p>
                                </div>

                                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">

                                            <input type="text" class="form-control " id="buscadorIngreso" name="buscadorIngreso" />
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>

                                </div>

                                <div class="box box-primary">

                                    <div class="row">

                                        <div class="col-xs-5">

                                            <label>Articulo</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Taller</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">En Taller</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Saldo</label>

                                        </div>

                                        <div class="col-xs-1">

                                            <label for="">Corte</label>

                                        </div>

                                    </div>

                                </div>

                                <div class="form-group row nuevoArticuloIngreso" style="height:400px;overflow: scroll; overflow-x:hidden">


                                </div>

                                <input type="hidden" id="listaArticulosIngreso" name="listaArticulosIngreso">

                                <div class="row">

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

                                <br>

                            </div>

                        </div>

                        <div class="box-footer">

                            <button type="submit" class="btn btn-danger pull-right"><i class="fa fa-floppy-o"></i> Guardar Segunda</button>

                            <a href="ingresos" id="cancel" name="cancel" class="btn btn-default"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php
                    $guardarSegundaMulti = new ControladorIngresosSegundaMulti();
                    $guardarSegundaMulti->ctrCrearSegundaMulti();
                    ?>

                </div>

            </div>

            <div class="col-lg-6 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border">
                        <span class="text-muted" id="segundasMultiTablaEstado"></span>
                    </div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped table-condensed tablaArticulosTalleresSegundaMulti" width="100%">

                            <thead>

                                <tr>
                                    <th class="text-center">Guia</th>
                                    <th class="text-center">Taller</th>
                                    <th class="text-center">Proceso</th>
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
    window.document.title = "Crear segunda (multi-taller)";
    window.vistaCrearSegundasMulti = true;
</script>

<script>
    $('.nuevoArticuloIngreso').ready(function() {
        $('#buscadorIngreso').keyup(function() {


            var nombres = $('.nuevaDescripcionProducto');

            var buscando = $(this).val();

            var item = '';

            for (var i = 0; i < nombres.length; i++) {

                item = $(nombres[i]).val();
                item2 = $(nombres[i]).val().toLowerCase();

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


</script>
