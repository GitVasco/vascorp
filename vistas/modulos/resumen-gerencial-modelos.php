<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
    denegarAccesoModulo();
    return;
}

$anioResumenModelos = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y");
$mesResumenModelos = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n");
$grupoResumenModelos = isset($_GET["id_grupo"]) ? (int) $_GET["id_grupo"] : 0;
$categoriaResumenModelos = isset($_GET["id_categoria"]) ? (int) $_GET["id_categoria"] : 0;
$subcategoriaResumenModelos = isset($_GET["id_subcategoria"]) ? (int) $_GET["id_subcategoria"] : 0;
$ordenResumenModelos = isset($_GET["orden"]) ? trim($_GET["orden"]) : "ventas";
if ($anioResumenModelos < 2021 || $anioResumenModelos > (int) date("Y")) {
    $anioResumenModelos = (int) date("Y");
}
if ($mesResumenModelos < 1 || $mesResumenModelos > 12) {
    $mesResumenModelos = (int) date("n");
}
if ($grupoResumenModelos < 0) {
    $grupoResumenModelos = 0;
}
if ($categoriaResumenModelos < 0) {
    $categoriaResumenModelos = 0;
}
if ($subcategoriaResumenModelos < 0) {
    $subcategoriaResumenModelos = 0;
}
if (!in_array($ordenResumenModelos, array("ventas", "utilidad"), true)) {
    $ordenResumenModelos = "ventas";
}
$mesesResumenModelos = array(
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
);
?>

<div class="content-wrapper resumen-modelos-page">
    <section class="content-header">
        <h1>Resumen gerencial de modelos <small>Comparación de indicadores principales</small></h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="ficha-gerencial-modelos">Análisis de modelos</a></li>
            <li class="active">Resumen de modelos</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary resumen-modelos-filtros">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2 col-sm-6">
                        <label>Grupo comercial</label>
                        <select class="form-control" id="resumenModelosGrupo" data-inicial="<?php echo $grupoResumenModelos; ?>">
                            <option value="">Todos los grupos</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label>Categoría</label>
                        <select class="form-control" id="resumenModelosCategoria" data-inicial="<?php echo $categoriaResumenModelos; ?>">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label>Subcategoría</label>
                        <select class="form-control" id="resumenModelosSubcategoria" data-inicial="<?php echo $subcategoriaResumenModelos; ?>">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-1 col-sm-3">
                        <label>Año</label>
                        <select class="form-control" id="resumenModelosAnio">
                            <?php for ($anio = (int) date("Y"); $anio >= 2021; $anio--) { ?>
                            <option value="<?php echo $anio; ?>" <?php echo $anio === $anioResumenModelos ? "selected" : ""; ?>>
                                <?php echo $anio; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-1 col-sm-3">
                        <label>Mes</label>
                        <select class="form-control" id="resumenModelosMes">
                            <?php foreach ($mesesResumenModelos as $numero => $nombre) { ?>
                            <option value="<?php echo $numero; ?>" <?php echo $numero === $mesResumenModelos ? "selected" : ""; ?>>
                                <?php echo $nombre; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label>Ordenar por</label>
                        <div class="resumen-modelos-orden">
                            <label><input type="radio" name="resumenModelosOrden" value="ventas" <?php echo $ordenResumenModelos === "ventas" ? "checked" : ""; ?>> Top ventas</label>
                            <label><input type="radio" name="resumenModelosOrden" value="utilidad" <?php echo $ordenResumenModelos === "utilidad" ? "checked" : ""; ?>> Top utilidad</label>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 resumen-modelos-acciones">
                        <label>&nbsp;</label>
                        <div class="btn-group btn-group-justified">
                            <a href="#" class="btn btn-primary" id="btnCargarResumenModelos"><i class="fa fa-search"></i> Consultar</a>
                            <a href="#" class="btn btn-default" id="btnLimpiarResumenModelos"><i class="fa fa-eraser"></i> Limpiar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-danger" id="resumenModelosError" style="display:none;"></div>

        <div class="box box-info" id="zonaResumenModelos">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-table"></i> Comparativo de modelos</h3>
                <div class="box-tools pull-right resumen-modelos-seleccion-tools">
                    <span id="resumenModelosSeleccionadosTexto">0 de 4 seleccionados</span>
                    <button type="button" class="btn btn-success btn-sm" id="btnCompararModelosSeleccionados" disabled>
                        <i class="fa fa-columns"></i> Comparar seleccionados
                    </button>
                    <span class="resumen-modelos-cargando"></span>
                </div>
            </div>
            <div class="box-body">
                <p class="text-muted resumen-modelos-descripcion">
                    Ranking de ventas por unidades del mes (general, categoría y subcategoría). La utilidad se rankea dentro del grupo comercial. Stock y lista 9 son valores actuales.
                </p>
                <div class="resumen-modelos-toolbar">
                    <div class="resumen-modelos-buscador">
                        <i class="fa fa-search"></i>
                        <input type="text" class="form-control" id="buscarResumenModelos" placeholder="Buscar por código, nombre, marca, grupo, categoría o subcategoría">
                        <span id="resumenModelosCoincidencias"></span>
                    </div>
                    <div class="resumen-ranking-leyenda">
                        <span><i class="fa fa-check alto"></i> Top 20%</span>
                        <span><i class="fa fa-circle medio"></i> Entre 21% y 50%</span>
                        <span><i class="fa fa-times bajo"></i> Debajo del 50%</span>
                    </div>
                </div>
                <div class="table-responsive resumen-modelos-tabla-wrap">
                    <table class="table table-condensed table-hover resumen-modelos-tabla">
                        <thead>
                            <tr>
                                <th class="text-center">Elegir</th>
                                <th>Modelo</th>
                                <th>Nombre</th>
                                <th>Marca / grupo</th>
                                <th>Categoría / sub</th>
                                <th>Rk. general</th>
                                <th>Rk. categoría</th>
                                <th>Rk. subcategoría</th>
                                <th>Rk. utilidad</th>
                                <th>Ventas acumuladas</th>
                                <th>Unidades</th>
                                <th>Utilidad</th>
                                <th>Margen</th>
                                <th>Stock disponible</th>
                                <th>Rotación</th>
                                <th>Días inventario</th>
                                <th>Variación interanual</th>
                                <th>Costo utilizado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaResumenModelos">
                            <tr><td colspan="18" class="text-muted text-center">Cargando información...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
