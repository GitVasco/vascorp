<div class="content-wrapper">
    <section class="content-header">
        <h1>Crear prehormado</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Crear prehormado</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <!-- FORMULARIO (IZQUIERDA) -->
            <div class="col-lg-6 col-xs-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Datos del prehormado</h3>
                    </div>

                    <form role="form" method="post" id="formPrehormado" class="formularioIngreso">
                        <div class="box-body">

                            <!-- Panel: Tipo prehormado -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-tags"></i> <strong>Tipo de prehormado</strong>
                                </div>
                                <div class="panel-body">

                                    <!-- Producto / Servicio -->
                                    <div class="form-group">
                                        <label for="tipoPrehormadoPS" class="control-label">¿Qué vas a prehormar?</label>
                                        <select class="form-control" name="tipoPrehormadoPS" id="tipoPrehormadoPS" required>
                                            <option value="producto" selected>Producto</option>
                                            <option value="servicio">Servicio</option>
                                        </select>
                                        <p class="help-block">Marcador informativo para tu backend (no modifica el select).</p>
                                    </div>

                                    <!-- Fecha -->
                                    <div class="form-group">
                                        <label for="nuevaFecha" class="control-label">Fecha</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            <input type="date" class="form-control" id="nuevaFecha" name="nuevaFecha"
                                                value="<?php date_default_timezone_set('America/Lima');
                                                        echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Panel: Lista por agregar -->
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <i class="fa fa-list-ul"></i> <strong>Lista por agregar</strong>
                                </div>
                                <div class="panel-body">

                                    <!-- Etiquetas de columnas -->
                                    <div class="well well-sm">
                                        <div class="row">
                                            <div class="col-xs-10"><strong>Artículo</strong></div>
                                            <div class="col-xs-2 text-center"><strong>Cantidad</strong></div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="listaArticulosPrehormado" name="listaArticulosPrehormado">

                                    <!-- Resumen / Totales -->
                                    <div class="panel panel-info">
                                        <div class="panel-heading">
                                            <i class="fa fa-check-square-o"></i> <strong>Resumen</strong>
                                        </div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label for="nuevoTotalTaller" class="control-label">Total de unidades</label>
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-calculator"></i></span>
                                                        <input type="text" class="form-control input-lg" id="nuevoTotalTaller"
                                                            name="nuevoTotalTaller" placeholder="0" readonly required>
                                                        <input type="hidden" name="totalTaller" id="totalTaller">
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                    <span class="label label-default">Se calculará al agregar cantidades</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div> <!-- /box-body -->

                        <div class="box-footer">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p class="text-muted">Revisa cantidades antes de guardar.</p>
                                </div>
                                <div class="col-sm-6 text-right">
                                    <a href="prehormado" id="cancel" name="cancel" class="btn btn-default">
                                        <i class="fa fa-times-circle"></i> Cancelar
                                    </a>

                                    <?php
                                    $crear = new ControladorProduccion();
                                    $crear->ctrCrearPrehormado();
                                    ?>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-floppy-o"></i> Guardar prehormado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <!-- TABLA DE ARTÍCULOS (DERECHA) -->
            <div class="col-lg-6 hidden-md hidden-sm hidden-xs">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Artículos disponibles</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">
                            Selecciona artículos aquí (cuando añadas la lógica) para agregarlos en la “Lista por agregar”.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-condensed tablaArticulosPrehormado" width="100%">

                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Color</th>
                                        <th>Talla</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se llena por tu DataTable / servidor -->
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div> <!-- /row -->

        <!-- TABLA DE PREHORMADOS CREADOS -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Prehormados ya creados</h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped dt-responsive tablaPrehormado" width="100%">
                                <caption class="hidden-xs">Histórico de registros</caption>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Color</th>
                                        <th>Talla</th>
                                        <th>Cantidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se llena por servidor -->
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted">Usa esta tabla para revisar o editar registros existentes.</p>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<script>
    window.document.title = "Crear prehormado";
    localStorage.setItem("sectorIngreso", null);
</script>