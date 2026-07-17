<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
    denegarAccesoModulo();
    return;
}

$modelosComparacion = array();
$modelosEntrada = isset($_GET["modelos"]) ? explode(",", $_GET["modelos"]) : array();
foreach ($modelosEntrada as $modeloEntrada) {
    $modeloEntrada = trim($modeloEntrada);
    if ($modeloEntrada !== "" && strlen($modeloEntrada) <= 10 && preg_match('/^[A-Za-z0-9._-]+$/', $modeloEntrada)
        && !in_array($modeloEntrada, $modelosComparacion, true)) {
        $modelosComparacion[] = $modeloEntrada;
    }
}
if (count($modelosComparacion) > 4) {
    $modelosComparacion = array_slice($modelosComparacion, 0, 4);
}
$anioComparacion = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y");
$mesComparacion = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n");
$grupoComparacion = isset($_GET["id_grupo"]) ? (int) $_GET["id_grupo"] : 0;
if ($anioComparacion < 2021 || $anioComparacion > (int) date("Y")) {
    $anioComparacion = (int) date("Y");
}
if ($mesComparacion < 1 || $mesComparacion > 12) {
    $mesComparacion = (int) date("n");
}
if ($grupoComparacion < 0) {
    $grupoComparacion = 0;
}
?>

<div class="content-wrapper comparacion-modelos-page">
    <section class="content-header">
        <h1>Comparación gerencial de modelos <small>Panel dinámico de 2 a 4 modelos</small></h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="index.php?ruta=resumen-gerencial-modelos&anio=<?php echo $anioComparacion; ?>&mes=<?php echo $mesComparacion; ?>&id_grupo=<?php echo $grupoComparacion; ?>">Resumen de modelos</a></li>
            <li class="active">Comparación</li>
        </ol>
    </section>

    <section class="content">
        <div class="comparacion-modelos-barra">
            <a class="btn btn-default" href="index.php?ruta=resumen-gerencial-modelos&anio=<?php echo $anioComparacion; ?>&mes=<?php echo $mesComparacion; ?>&id_grupo=<?php echo $grupoComparacion; ?>">
                <i class="fa fa-arrow-left"></i> Cambiar modelos
            </a>
            <span id="comparacionModelosPeriodo"></span>
        </div>

        <div class="alert alert-info" id="comparacionModelosEstado">
            <i class="fa fa-spinner fa-spin"></i> Construyendo comparación...
        </div>

        <div id="comparacionModelosContenido" style="display:none;">
            <div class="comparacion-modelos-grid" id="comparacionModelosCabeceras"></div>

            <div class="box box-primary comparacion-modelos-box">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-dashboard"></i> Indicadores principales</h3></div>
                <div class="box-body">
                    <div class="comparacion-modelos-grid" id="comparacionModelosIndicadores"></div>
                </div>
            </div>

            <div class="comparacion-graficos-grid">
                <div class="box box-primary comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-crosshairs"></i> Perfil comparativo</h3></div>
                    <div class="box-body">
                        <p class="text-muted comparacion-radar-descripcion" id="comparacionRadarDescripcion">Preparando la escala comparativa.</p>
                        <div class="comparacion-grafico-leyenda" id="comparacionRadarLeyenda"></div>
                        <div class="comparacion-grafico radar"><canvas id="graficoComparacionRadar"></canvas></div>
                    </div>
                </div>
                <div class="box box-info comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-line-chart"></i> Evolución mensual</h3></div>
                    <div class="box-body">
                        <div class="comparacion-grafico-leyenda" id="comparacionModelosLeyenda"></div>
                        <div class="comparacion-grafico"><canvas id="graficoComparacionEvolucion"></canvas></div>
                    </div>
                </div>
                <div class="box box-success comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title">Ventas y utilidad</h3></div>
                    <div class="box-body">
                        <div class="comparacion-grafico-leyenda"><span><i style="background:#3c8dbc"></i> Ventas</span><span><i style="background:#00a65a"></i> Utilidad</span></div>
                        <div class="comparacion-grafico"><canvas id="graficoComparacionRentabilidad"></canvas></div>
                    </div>
                </div>
                <div class="box box-warning comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title">Unidades vendidas y stock</h3></div>
                    <div class="box-body">
                        <div class="comparacion-grafico-leyenda"><span><i style="background:#f39c12"></i> Unidades</span><span><i style="background:#605ca8"></i> Stock</span></div>
                        <div class="comparacion-grafico"><canvas id="graficoComparacionInventario"></canvas></div>
                    </div>
                </div>
                <div class="box box-info comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title">Margen</h3></div>
                    <div class="box-body"><div class="comparacion-grafico"><canvas id="graficoComparacionMargen"></canvas></div></div>
                </div>
                <div class="box box-info comparacion-modelos-box">
                    <div class="box-header with-border"><h3 class="box-title">Rotación</h3></div>
                    <div class="box-body"><div class="comparacion-grafico"><canvas id="graficoComparacionRotacion"></canvas></div></div>
                </div>
            </div>

            <div class="box box-default comparacion-modelos-box">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-table"></i> Resumen comparativo</h3></div>
                <div class="box-body table-responsive">
                    <h4 class="comparacion-ganadores-titulo"><i class="fa fa-trophy"></i> Ganadores por indicador</h4>
                    <div class="comparacion-modelos-grid comparacion-ganadores-grid" id="comparacionModelosGanadores"></div>
                    <table class="table table-bordered table-condensed comparacion-modelos-tabla" id="tablaComparacionModelos"></table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
window.comparacionModelosConfig = <?php echo json_encode(array(
    "modelos" => $modelosComparacion,
    "anio" => $anioComparacion,
    "mes" => $mesComparacion,
    "id_grupo" => $grupoComparacion
), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
