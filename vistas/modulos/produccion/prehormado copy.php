<?php
// revisamos si viene $get['id'] para editar
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $prehormado = ModeloProduccion::verPrehormado($id);

    $tipo = $prehormado["tipo"];
    $fecha = $prehormado["fecha_registro"];
    $cantidad = $prehormado["cantidad"];
    $articulo = $prehormado["articulo"];

    $styleNew = "style='display:none;'";
    $styleEdit = "";

    $typeNew = "button";
    $typeEdit = "submit";
} else {
    $id = null;
    $tipo = "";
    $fecha = date("Y-m-d");
    $cantidad = "";
    $articulo = "";

    $styleNew = "";
    $styleEdit = "style='display:none;'";

    $typeNew = "submit";
    $typeEdit = "button";
}

?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>
            Prehormado
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Prehormado</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-lg-7">
                <div class="box">
                    <div class="box-body">
                        <table class="table table-bordered table-striped dt-responsive tablaPrehormado" width="100%">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="box box-warning">
                    <form role="form" method="post" class="formularioRegistrarPrehormado" id="formPrehormado">
                        <?php if (isset($_GET["id"])) : ?>
                            <input type="hidden" id="idPrehormado" name="idPrehormado" value=<?= $id ?>>
                            <input type="hidden" id="articulo" name="articulo" value=<?= $articulo ?>>
                        <?php endif ?>
                        <div class="box-body">
                            <div class="box">

                                <!-- CODIGO DEL CORTE -->
                                <div class="form-group col-lg-4">
                                    <label>Tipo</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                                        <?php
                                        $tipoPrehormado = ControladorPlantilla::obtenerInfoJson("vistas/json/common.json", "tipo_prehormado", null);
                                        ?>
                                        <select class="form-control input-sm selectpicker" name="tipoPrehormado" id="tipoPrehormado" data-live-search="true" data-size="10" required>
                                            <option value="">Tipo</option>
                                            <?php foreach ($tipoPrehormado as $key => $value) : ?>
                                                <option value="<?= $value['id']; ?>" <?= $value["id"] == $tipo ? "selected" : "" ?>>
                                                    <?= $value['id'] . ' - ' . $value['nombre']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Fecha de registro -->
                                <div class="form-group col-lg-4">
                                    <label>Fecha</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control input-sm" name="fechaPrehormado" id="fechaPrehormado" value="<?= $fecha ?>" required>
                                    </div>
                                </div>

                                <!-- Cantidad -->
                                <div class="form-group col-lg-4">
                                    <label>Cantidad</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-sort-numeric-asc"></i></span>
                                        <input type="number" class="form-control input-sm" name="cantidadPrehormado" id="cantidadPrehormado" min="0" value="<?= $cantidad ?>" required>
                                    </div>
                                </div>

                                <!-- articulos del corte -->
                                <div class="form-group col-lg-12">
                                    <label>ARTÍCULOS</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>
                                        <select class="form-control input-sm selectpicker" name="articulosPrehormado" id="articulosPrehormado" data-live-search="true" data-size="10" required>
                                            <option value="">Seleccionar Artículo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="<?= $typeNew ?>" class="btn btn-primary pull-right" id="btnGuardarPrehormado" <?= $styleNew ?>><i class="fa fa-floppy-o"></i> Guardar Registro</button>
                            <button type="<?= $typeEdit ?>" class="btn btn-warning pull-right" id="btnActualizarPrehormado" <?= $styleEdit ?>><i class="fa fa-floppy-o"></i> Actualizar Registro</button>
                            <a href="prehormado" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>
                    <?php

                    if (isset($_GET['id'])) {
                        $editar = new ControladorProduccion();
                        $editar->ctrEditarPrehormado($id);
                    } else {

                        $crear = new ControladorProduccion();
                        $crear->ctrCrearPrehormado();
                    }

                    ?>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
    window.document.title = "Prehormado"
</script>