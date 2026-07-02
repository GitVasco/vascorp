<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$clienteFiltro = isset($_GET["cliente"]) ? trim($_GET["cliente"]) : "";
$clientes = ControladorInteligenciaComercial::ctrClientesAnalisis();
$resultadoMotor1 = null;
$resultadoMotor2 = null;
$resultadoMotor3 = null;
$resultadoMotor4 = null;

if ($clienteFiltro !== "") {
    $analisis = ControladorInteligenciaComercial::ctrCalcularAnalisisCompleto($clienteFiltro);
    $resultadoMotor1 = $analisis["motor1"];
    $resultadoMotor2 = $analisis["motor2"];
    $resultadoMotor3 = $analisis["motor3"];
    $resultadoMotor4 = $analisis["motor4"];
}

$nombreClienteFiltro = "";
foreach ($clientes as $cli) {
    if ($cli["codigo"] === $clienteFiltro) {
        $nombreClienteFiltro = $cli["codigo"] . " - " . $cli["nombre"];
        break;
    }
}

function icColorScore($score)
{
    if ($score >= 80) {
        return "#00a65a";
    }
    if ($score >= 70) {
        return "#3c8dbc";
    }
    if ($score >= 60) {
        return "#f39c12";
    }

    return "#dd4b39";
}

function icColoresPastel()
{
    return array(
        "#F4A6A6",
        "#FFD4A3",
        "#A8CCE8",
        "#C5B4E3",
        "#B5E0C8",
        "#F7C5D8",
        "#D5E8A4",
        "#E8D4B8",
    );
}

function icRenderMotorPanel($m, $opciones)
{
    $cls = $m["clasificacion"];
    $colorScore = icColorScore($m["score"]);
    $coloresPastel = icColoresPastel();
    $idxPastel = 0;
    ?>
    <div class="box box-<?php echo htmlspecialchars($cls["color"], ENT_QUOTES, "UTF-8"); ?> ic-motor-box">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa <?php echo htmlspecialchars($opciones["icono"], ENT_QUOTES, "UTF-8"); ?>"></i>
                <?php echo htmlspecialchars($opciones["titulo"], ENT_QUOTES, "UTF-8"); ?>
                <?php if (!empty($opciones["proposito"])) : ?>
                    <small class="ic-motor-proposito"><?php echo htmlspecialchars($opciones["proposito"], ENT_QUOTES, "UTF-8"); ?></small>
                <?php endif; ?>
            </h3>
            <div class="box-tools pull-right" style="display:flex; align-items:center; gap:10px;">
                <span class="ic-motor-score" style="color:<?php echo $colorScore; ?>;">
                    <?php echo number_format($m["score"], 1, ".", ""); ?>
                </span>
                <span class="label label-<?php echo htmlspecialchars($cls["color"], ENT_QUOTES, "UTF-8"); ?>" style="font-size:13px;">
                    <?php echo htmlspecialchars($cls["etiqueta"], ENT_QUOTES, "UTF-8"); ?>
                </span>
            </div>
        </div>
        <div class="box-body">
            <div class="row ic-motor-panel-inner" style="display:flex; align-items:center; flex-wrap:wrap;">
                <div class="col-sm-3 col-xs-6">
                    <p class="ic-chart-title"><i class="fa <?php echo htmlspecialchars($opciones["icono"], ENT_QUOTES, "UTF-8"); ?>"></i> <?php echo htmlspecialchars($opciones["titulo_gauge"], ENT_QUOTES, "UTF-8"); ?></p>
                    <div class="ic-gauge-wrap">
                        <canvas id="<?php echo htmlspecialchars($opciones["chart_score"], ENT_QUOTES, "UTF-8"); ?>" width="180" height="180"></canvas>
                        <div class="ic-gauge-center">
                            <span class="ic-gauge-num" style="color:<?php echo $colorScore; ?>;">
                                <?php echo number_format($m["score"], 1, ".", ""); ?>
                            </span>
                            <span class="ic-gauge-label"><?php echo htmlspecialchars($cls["etiqueta"], ENT_QUOTES, "UTF-8"); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <p class="ic-chart-title"><i class="fa fa-pie-chart"></i> Aportación por factor</p>
                    <div class="ic-aportacion-chart">
                        <canvas id="<?php echo htmlspecialchars($opciones["chart_aport"], ENT_QUOTES, "UTF-8"); ?>" width="180" height="180"></canvas>
                    </div>
                </div>
                <div class="col-sm-6 col-xs-12">
                    <ul class="ic-chart-legend" id="<?php echo htmlspecialchars($opciones["legend_id"], ENT_QUOTES, "UTF-8"); ?>" data-motor="<?php echo (int) $m["motor"]; ?>">
                        <?php foreach ($m["factores"] as $factor) :
                            $colorPastel = $coloresPastel[$idxPastel % count($coloresPastel)];
                            $idxPastel++;
                        ?>
                            <li class="ic-legend-item"
                                data-factor="<?php echo htmlspecialchars($factor["clave"], ENT_QUOTES, "UTF-8"); ?>"
                                data-color="<?php echo htmlspecialchars($colorPastel, ENT_QUOTES, "UTF-8"); ?>">
                                <span class="ic-legend-dot" style="background:<?php echo $colorPastel; ?>;"></span>
                                <span class="ic-legend-label"><?php echo htmlspecialchars($factor["nombre"], ENT_QUOTES, "UTF-8"); ?></span>
                                <span class="ic-legend-score" title="Score del factor"><?php echo number_format($factor["score"], 1, ".", ""); ?></span>
                                <span class="ic-legend-val" title="Aportación al total">+<?php echo number_format($factor["aportacion"], 2, ".", ""); ?></span>
                                <span class="ic-legend-peso"><?php echo (int) $factor["peso"]; ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted" style="margin:10px 0 0; font-size:12px;">
                        <i class="fa fa-hand-pointer-o"></i> Clic en un factor para ver el detalle del cálculo.
                        <span style="margin-left:8px;">Score factor · Aportación pts · Peso</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script type="application/json" id="<?php echo htmlspecialchars($opciones["data_id"], ENT_QUOTES, "UTF-8"); ?>"><?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?></script>
    <?php
}

function icRenderMotorLineaCreditoPanel($m, $opciones)
{
    $cls = $m["clasificacion"];
    $colorScore = icColorScore($m["score"]);
    $accion = $m["accion"];
    $linea = $m["linea"];
    $coloresPastel = icColoresPastel();
    $idxPastel = 0;
    ?>
    <div class="box box-<?php echo htmlspecialchars($cls["color"], ENT_QUOTES, "UTF-8"); ?> ic-motor-box">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa <?php echo htmlspecialchars($opciones["icono"], ENT_QUOTES, "UTF-8"); ?>"></i>
                <?php echo htmlspecialchars($opciones["titulo"], ENT_QUOTES, "UTF-8"); ?>
                <?php if (!empty($opciones["proposito"])) : ?>
                    <small class="ic-motor-proposito"><?php echo htmlspecialchars($opciones["proposito"], ENT_QUOTES, "UTF-8"); ?></small>
                <?php endif; ?>
            </h3>
            <div class="box-tools pull-right" style="display:flex; align-items:center; gap:10px;">
                <span class="label label-<?php echo htmlspecialchars($accion["color"], ENT_QUOTES, "UTF-8"); ?>" style="font-size:12px;">
                    <i class="fa <?php echo htmlspecialchars($accion["icono"], ENT_QUOTES, "UTF-8"); ?>"></i>
                    <?php echo htmlspecialchars($accion["etiqueta"], ENT_QUOTES, "UTF-8"); ?>
                </span>
                <span class="ic-motor-score" style="color:<?php echo $colorScore; ?>;">
                    <?php echo number_format($m["score"], 1, ".", ""); ?>
                </span>
            </div>
        </div>
        <div class="box-body">
            <div class="ic-linea-resumen">
                <div class="ic-linea-monto">
                    <span class="ic-linea-etq">Deuda actual</span>
                    <strong>S/ <?php echo number_format($linea["deuda_actual"], 2); ?></strong>
                </div>
                <div class="ic-linea-monto">
                    <span class="ic-linea-etq">Línea operativa</span>
                    <strong>S/ <?php echo number_format($linea["linea_operativa"], 2); ?></strong>
                    <small>Pico histórico S/ <?php echo number_format($linea["pico_historico"], 2); ?></small>
                </div>
                <div class="ic-linea-monto ic-linea-recomendada">
                    <span class="ic-linea-etq">Línea recomendada</span>
                    <strong>S/ <?php echo number_format($linea["linea_recomendada"], 2); ?></strong>
                </div>
            </div>
            <p class="ic-linea-explicacion text-muted">
                <i class="fa fa-info-circle"></i>
                <?php echo htmlspecialchars($accion["explicacion"], ENT_QUOTES, "UTF-8"); ?>
            </p>
            <p style="margin:0 0 12px;">
                <button type="button" class="btn btn-sm btn-default btn-ic-linea-detalle" id="btnDetalleLineaCredito">
                    <i class="fa fa-question-circle"></i> ¿Por qué esta línea de crédito?
                </button>
            </p>
            <div class="row ic-motor-panel-inner" style="display:flex; align-items:center; flex-wrap:wrap;">
                <div class="col-sm-3 col-xs-6">
                    <p class="ic-chart-title"><i class="fa <?php echo htmlspecialchars($opciones["icono"], ENT_QUOTES, "UTF-8"); ?>"></i> <?php echo htmlspecialchars($opciones["titulo_gauge"], ENT_QUOTES, "UTF-8"); ?></p>
                    <div class="ic-gauge-wrap">
                        <canvas id="<?php echo htmlspecialchars($opciones["chart_score"], ENT_QUOTES, "UTF-8"); ?>" width="180" height="180"></canvas>
                        <div class="ic-gauge-center">
                            <span class="ic-gauge-num" style="color:<?php echo $colorScore; ?>;">
                                <?php echo number_format($m["score"], 1, ".", ""); ?>
                            </span>
                            <span class="ic-gauge-label"><?php echo htmlspecialchars($cls["etiqueta"], ENT_QUOTES, "UTF-8"); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <p class="ic-chart-title"><i class="fa fa-pie-chart"></i> Aportación por factor</p>
                    <div class="ic-aportacion-chart">
                        <canvas id="<?php echo htmlspecialchars($opciones["chart_aport"], ENT_QUOTES, "UTF-8"); ?>" width="180" height="180"></canvas>
                    </div>
                </div>
                <div class="col-sm-6 col-xs-12">
                    <ul class="ic-chart-legend" id="<?php echo htmlspecialchars($opciones["legend_id"], ENT_QUOTES, "UTF-8"); ?>" data-motor="<?php echo (int) $m["motor"]; ?>">
                        <?php foreach ($m["factores"] as $factor) :
                            $colorPastel = $coloresPastel[$idxPastel % count($coloresPastel)];
                            $idxPastel++;
                        ?>
                            <li class="ic-legend-item"
                                data-factor="<?php echo htmlspecialchars($factor["clave"], ENT_QUOTES, "UTF-8"); ?>"
                                data-color="<?php echo htmlspecialchars($colorPastel, ENT_QUOTES, "UTF-8"); ?>">
                                <span class="ic-legend-dot" style="background:<?php echo $colorPastel; ?>;"></span>
                                <span class="ic-legend-label"><?php echo htmlspecialchars($factor["nombre"], ENT_QUOTES, "UTF-8"); ?></span>
                                <span class="ic-legend-score" title="Score del factor"><?php echo number_format($factor["score"], 1, ".", ""); ?></span>
                                <span class="ic-legend-val" title="Aportación al total">+<?php echo number_format($factor["aportacion"], 2, ".", ""); ?></span>
                                <span class="ic-legend-peso"><?php echo (int) $factor["peso"]; ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted" style="margin:10px 0 0; font-size:12px;">
                        <i class="fa fa-hand-pointer-o"></i> Clic en un factor para ver el detalle del cálculo.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script type="application/json" id="<?php echo htmlspecialchars($opciones["data_id"], ENT_QUOTES, "UTF-8"); ?>"><?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?></script>
    <?php if (!empty($m["explicacion_linea"])) : ?>
    <script type="application/json" id="icMotor3LineaExplicacion"><?php echo json_encode($m["explicacion_linea"], JSON_UNESCAPED_UNICODE); ?></script>
    <?php endif; ?>
    <?php
}
?>

<style>
.ic-wrap .ic-motor-box {
    border-radius: 6px;
    margin-bottom: 20px;
    height: 100%;
}
.ic-wrap .ic-motores-grid {
    margin-bottom: 5px;
}
.ic-wrap .ic-motores-grid > [class*="col-"] {
    margin-bottom: 20px;
}
.ic-motores-grid .ic-motor-score {
    font-size: 24px;
}
.ic-motores-grid .ic-gauge-wrap,
.ic-motores-grid .ic-aportacion-chart {
    width: 150px;
    height: 150px;
}
.ic-motores-grid .ic-gauge-wrap canvas,
.ic-motores-grid .ic-aportacion-chart canvas {
    width: 150px !important;
    height: 150px !important;
}
.ic-motores-grid .ic-gauge-center .ic-gauge-num {
    font-size: 22px;
}
.ic-motores-grid .ic-chart-legend li {
    font-size: 12px;
    padding: 7px 8px;
    gap: 6px;
}
.ic-motores-grid .ic-legend-score {
    min-width: 44px;
}
.ic-motores-grid .ic-legend-val {
    min-width: 58px;
}
.ic-motores-grid .ic-motor-panel-inner {
    display: block !important;
}
.ic-motores-grid .ic-motor-panel-inner > .col-sm-3 {
    width: 50%;
    float: left;
}
.ic-motores-grid .ic-motor-panel-inner > .col-sm-6 {
    width: 100%;
    clear: both;
    float: none;
}
.ic-motores-grid .ic-motor-placeholder {
    margin-bottom: 0;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.ic-motores-grid .ic-motor-placeholder-proposito {
    font-size: 11px;
    color: #bbb;
}
.ic-linea-resumen {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}
.ic-linea-monto {
    flex: 1;
    min-width: 140px;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px 12px;
    border-left: 3px solid #3c8dbc;
}
.ic-linea-monto.ic-linea-recomendada {
    border-left-color: #00a65a;
    background: #f4faf6;
}
.ic-linea-etq {
    display: block;
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.ic-linea-monto strong {
    display: block;
    font-size: 18px;
    color: #333;
    margin-top: 2px;
}
.ic-linea-monto small {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 2px;
}
.ic-linea-explicacion {
    font-size: 12px;
    margin: 0 0 15px;
    padding: 8px 10px;
    background: #fffdf5;
    border-radius: 4px;
    border-left: 3px solid #f39c12;
}
.ic-capacidad-dual {
    margin-bottom: 14px;
}
.ic-capacidad-box {
    border-radius: 6px;
    padding: 10px 12px;
    height: 100%;
    border: 1px solid #e8e8e8;
}
.ic-capacidad-pago {
    background: #f7fbff;
    border-left: 3px solid #3c8dbc;
}
.ic-capacidad-compra {
    background: #f8fdf9;
    border-left: 3px solid #00a65a;
}
.ic-capacidad-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
}
.ic-capacidad-head strong {
    font-size: 18px;
}
.ic-capacidad-intro {
    font-size: 11px;
    color: #777;
    margin: 0 0 8px;
    line-height: 1.4;
}
.ic-capacidad-lista {
    list-style: none;
    margin: 0;
    padding: 0;
    font-size: 12px;
}
.ic-capacidad-lista li {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 4px 0;
    border-bottom: 1px dashed #e0e0e0;
}
.ic-capacidad-lista li:last-child {
    border-bottom: none;
}
.ic-capacidad-lista span {
    color: #888;
}
.ic-capacidad-indicadores {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ic-capacidad-indicadores li {
    font-size: 10px;
    background: #fff;
    border: 1px solid #dde;
    border-radius: 4px;
    padding: 3px 6px;
    cursor: help;
}
.ic-capacidad-indicadores li span {
    font-weight: 700;
    margin-left: 4px;
    color: #3c8dbc;
}
.ic-capacidad-nota {
    font-size: 11px;
    color: #666;
    margin: 8px 0 0;
}
.ic-modal-linea-body {
    padding-top: 8px;
}
.ic-linea-seccion {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}
.ic-linea-seccion:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.ic-linea-seccion-hero {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 16px;
}
.ic-linea-seccion-hero .alert {
    margin: 0;
}
.ic-linea-seccion-hero #icModalLineaAccionTxt {
    display: block;
    margin-top: 6px;
    font-weight: normal;
    font-size: 13px;
}
.ic-linea-seccion-titulo {
    font-size: 14px;
    font-weight: 600;
    color: #444;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ic-linea-seccion-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #3c8dbc;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
.ic-linea-seccion-intro {
    font-size: 12px;
    color: #777;
    margin: 0 0 10px;
    line-height: 1.45;
}
.ic-linea-calculo-card {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 12px 14px;
    border: 1px solid #e8e8e8;
}
.ic-linea-base-def {
    font-size: 12px;
    color: #555;
    margin: 0 0 10px;
    padding: 8px 10px;
    background: #fff;
    border-left: 3px solid #00a65a;
    border-radius: 0 4px 4px 0;
}
.ic-linea-resultado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #ddd;
    font-size: 13px;
}
.ic-linea-resultado strong {
    font-size: 18px;
    color: #00a65a;
}
.ic-linea-ref-grid .ic-modal-dato {
    margin-bottom: 8px;
}
.ic-linea-details {
    font-size: 13px;
    color: #555;
}
.ic-linea-details summary {
    cursor: pointer;
    padding: 8px 0;
    font-weight: 600;
}
.ic-linea-details summary .fa {
    margin-right: 6px;
    color: #3c8dbc;
}
.ic-cap-modal-ind-wrap {
    margin-top: 8px;
}
.ic-cap-ind-titulo {
    font-size: 10px;
    color: #999;
    text-transform: uppercase;
    margin: 0 0 4px;
    letter-spacing: 0.3px;
}
.ic-linea-pasos-table td:first-child {
    font-weight: 600;
    white-space: nowrap;
}
.btn-ic-linea-detalle {
    font-weight: 600;
}
.ic-linea-pasos {
    list-style: none;
    margin: 0;
    padding: 0;
}
.ic-linea-pasos li {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    align-items: flex-start;
}
.ic-linea-pasos li:last-child {
    border-bottom: none;
}
.ic-linea-paso-num {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #3c8dbc;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ic-linea-paso-body strong {
    display: block;
    font-size: 15px;
    color: #333;
}
.ic-linea-paso-body span {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}
.ic-linea-paso-etq {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
}
.ic-wrap .ic-motor-score {
    font-size: 32px;
    font-weight: 700;
    margin-right: 10px;
    vertical-align: middle;
}
.ic-wrap .ic-motor-proposito {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    font-weight: 400;
    color: #888;
    line-height: 1.3;
}
.ic-motores-grid .ic-motor-proposito {
    font-size: 11px;
}
.ic-wrap .ic-chart-legend {
    list-style: none;
    margin: 0;
    padding: 0;
}
.ic-wrap .ic-chart-legend li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
    cursor: pointer;
    border-radius: 4px;
    transition: background .15s;
}
.ic-wrap .ic-chart-legend li:hover {
    background: #f5f8fc;
}
.ic-wrap .ic-chart-legend li:last-child { border-bottom: none; }
.ic-wrap .ic-legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.ic-wrap .ic-legend-label {
    flex: 1;
    color: #333;
    font-weight: 600;
}
.ic-wrap .ic-legend-score {
    color: #666;
    min-width: 52px;
    text-align: right;
}
.ic-wrap .ic-legend-val {
    font-weight: 700;
    color: #333;
    min-width: 72px;
    text-align: right;
}
.ic-wrap .ic-legend-peso {
    color: #999;
    font-size: 11px;
    min-width: 44px;
    text-align: right;
}
.ic-wrap .ic-gauge-wrap {
    position: relative;
    width: 180px;
    height: 180px;
    margin: 0 auto;
}
.ic-wrap .ic-gauge-wrap canvas {
    display: block;
    width: 180px !important;
    height: 180px !important;
}
.ic-wrap .ic-gauge-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    line-height: 1.15;
}
.ic-wrap .ic-gauge-center .ic-gauge-num {
    display: block;
    font-size: 26px;
    font-weight: 700;
}
.ic-wrap .ic-gauge-center .ic-gauge-label {
    display: block;
    font-size: 10px;
    color: #888;
    margin-top: 3px;
    max-width: 90px;
    text-align: center;
}
.ic-wrap .ic-aportacion-chart {
    width: 180px;
    height: 180px;
    margin: 0 auto;
}
.ic-wrap .ic-aportacion-chart canvas {
    display: block;
    width: 180px !important;
    height: 180px !important;
}
.ic-wrap .ic-chart-title {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    margin: 0 0 8px;
}
.ic-wrap .ic-motor-placeholder {
    opacity: .55;
    border: 2px dashed #ddd;
    border-radius: 6px;
    padding: 16px;
    text-align: center;
    color: #999;
    margin-bottom: 15px;
}
.ic-wrap .ic-modal-valor {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.ic-wrap .ic-modal-valor:last-child { border-bottom: none; }

/* Modal detalle factor */
.ic-modal-metrics {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
}
.ic-modal-metrics > [class*="col-"] {
    display: flex;
    margin-bottom: 10px;
}
.ic-modal-metric {
    flex: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16px 12px 14px;
    background: #f8f9fb;
    border-radius: 8px;
    border: 1px solid #e8ecf0;
    min-height: 130px;
}
.ic-modal-metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    margin-bottom: 10px;
    flex-shrink: 0;
}
.ic-modal-icon-score  { background: #fdecea; color: #dd4b39; }
.ic-modal-icon-peso   { background: #e8f4fc; color: #3c8dbc; }
.ic-modal-icon-aporte { background: #e8f8f0; color: #00a65a; }
.ic-modal-metric-val {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.1;
}
.ic-modal-metric-lbl {
    display: block;
    font-size: 10px;
    color: #888;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: .4px;
    line-height: 1.3;
}
.ic-modal-metric-footer {
    width: 100%;
    margin-top: auto;
    padding-top: 10px;
    min-height: 18px;
}
.ic-modal-score-bar {
    height: 6px;
    background: #e8ecf0;
    border-radius: 3px;
    overflow: hidden;
}
.ic-modal-score-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width .3s;
}
.ic-modal-spacer {
    display: block;
    height: 6px;
}
.ic-modal-resumen {
    background: #e8f4fc;
    border-left: 4px solid #3c8dbc;
    padding: 14px 16px;
    border-radius: 0 6px 6px 0;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.5;
    color: #333;
}
.ic-modal-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #444;
    margin: 0 0 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid #f0f0f0;
}
.ic-modal-dato {
    text-align: center;
    padding: 12px 8px;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    margin-bottom: 12px;
    height: calc(100% - 12px);
}
.ic-modal-dato-val {
    display: block;
    font-size: 22px;
    font-weight: 700;
    color: #333;
    line-height: 1.2;
}
.ic-modal-dato-lbl {
    display: block;
    font-size: 11px;
    color: #888;
    margin-top: 5px;
    line-height: 1.3;
}
.ic-modal-calculo {
    background: #fafbfc;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}
.ic-modal-formula {
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 13px;
    background: #fff;
    border: 1px dashed #ccc;
    padding: 12px 14px;
    border-radius: 6px;
    margin-bottom: 12px;
    color: #444;
}
.ic-modal-aportacion-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #e8ecf0;
    font-size: 13px;
}
.ic-modal-aportacion-line strong {
    font-size: 18px;
    color: #333;
}
.ic-modal-regla-wrap {
    margin-top: 4px;
}
.ic-modal-regla-wrap summary {
    cursor: pointer;
    font-size: 12px;
    color: #3c8dbc;
    padding: 8px 0;
    user-select: none;
}
.ic-modal-regla-wrap summary:hover { text-decoration: underline; }
.ic-modal-regla-wrap p {
    font-size: 12px;
    color: #777;
    margin: 0 0 8px;
    padding: 10px 12px;
    background: #f9f9f9;
    border-radius: 4px;
    line-height: 1.5;
}
.ic-modal-logica {
    margin-bottom: 20px;
}
.ic-modal-logica-intro {
    font-size: 12px;
    color: #666;
    margin: 0 0 10px;
    line-height: 1.45;
}
.ic-modal-logica-table-wrap {
    overflow-x: auto;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    background: #fff;
}
.ic-modal-logica-table {
    width: 100%;
    margin: 0;
    font-size: 12px;
    border-collapse: collapse;
}
.ic-modal-logica-table th {
    background: #f4f6f9;
    color: #555;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .3px;
    padding: 10px 12px;
    border-bottom: 2px solid #e8ecf0;
    white-space: nowrap;
}
.ic-modal-logica-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f2f5;
    color: #444;
    vertical-align: top;
    line-height: 1.4;
}
.ic-modal-logica-table tr:last-child td {
    border-bottom: none;
}
.ic-modal-logica-table tr.ic-logica-aplica td {
    background: #e8f4fc;
    font-weight: 600;
}
.ic-modal-logica-table tr.ic-logica-aplica td:last-child {
    color: #3c8dbc;
    font-size: 14px;
}
.ic-modal-logica-table tr.ic-logica-resultado td {
    background: #e8f8f0;
    border-top: 2px solid #00a65a;
}
.ic-modal-logica-table tr.ic-logica-resultado td:last-child {
    color: #00a65a;
    font-size: 15px;
}
.ic-modal-logica-badge {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 7px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    border-radius: 10px;
    background: #3c8dbc;
    color: #fff;
    vertical-align: middle;
}
.ic-modal-logica-table tr.ic-logica-resultado .ic-modal-logica-badge {
    background: #00a65a;
}
.ic-modal-periodos {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-left: 4px solid #3c8dbc;
    border-radius: 0 8px 8px 0;
    padding: 14px 16px;
    margin-bottom: 20px;
}
.ic-modal-periodos-title {
    font-size: 12px;
    font-weight: 700;
    color: #555;
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.ic-modal-periodos-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.ic-modal-periodos-list li {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 7px 0;
    border-bottom: 1px solid #f0f2f5;
    font-size: 13px;
    line-height: 1.4;
}
.ic-modal-periodos-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.ic-modal-periodos-list .ic-periodo-etq {
    color: #666;
    flex-shrink: 0;
}
.ic-modal-periodos-list .ic-periodo-rango {
    color: #333;
    font-weight: 600;
    text-align: right;
}
</style>

<div class="content-wrapper ic-wrap">
    <section class="content-header">
        <h1>
            Inteligencia Comercial
            <small>Análisis por cliente</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Inteligencia Comercial</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-default">
            <div class="box-body" style="display:flex; align-items:flex-end; gap:15px; flex-wrap:wrap;">
                <div style="flex:1; min-width:280px;">
                    <label for="clienteInteligencia" style="font-weight:bold; font-size:12px; display:block; margin-bottom:5px;">
                        <i class="fa fa-user"></i> Cliente
                    </label>
                    <select class="form-control selectpicker" id="clienteInteligencia" data-live-search="true" data-size="10" title="Seleccione un cliente">
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($clientes as $cli) : ?>
                            <option value="<?php echo htmlspecialchars($cli["codigo"], ENT_QUOTES, "UTF-8"); ?>" <?php echo $clienteFiltro === $cli["codigo"] ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($cli["codigo"] . " - " . $cli["nombre"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if ($clienteFiltro === "") : ?>
            <div class="callout callout-info">
                <p style="margin:0;"><i class="fa fa-info-circle"></i> Seleccione un cliente para ver el análisis.</p>
            </div>
        <?php elseif (!$resultadoMotor1) : ?>
            <div class="callout callout-warning">
                <p style="margin:0;">No se encontraron datos para el cliente seleccionado.</p>
            </div>
        <?php else : ?>

            <?php if ($nombreClienteFiltro !== "") : ?>
            <p class="text-muted" style="margin:0 0 15px;">
                <i class="fa fa-building-o"></i> <?php echo htmlspecialchars($nombreClienteFiltro, ENT_QUOTES, "UTF-8"); ?>
            </p>
            <?php endif; ?>

            <div class="row ic-motores-grid">
                <div class="col-md-6">
                    <?php
                    icRenderMotorPanel($resultadoMotor1, array(
                        "titulo"       => "Motor 1 — Riesgo Crediticio",
                        "proposito"    => "¿Le puedo fiar o mantener crédito?",
                        "icono"        => "fa-shield",
                        "titulo_gauge" => "Score de riesgo",
                        "chart_score"  => "icChartRiesgo",
                        "chart_aport"  => "icChartAportacion",
                        "legend_id"    => "icMotor1Legend",
                        "data_id"      => "icMotor1Data",
                    ));
                    ?>
                </div>
                <?php if ($resultadoMotor2) : ?>
                <div class="col-md-6">
                    <?php
                    icRenderMotorPanel($resultadoMotor2, array(
                        "titulo"       => "Motor 2 — Comercial",
                        "proposito"    => "¿Tiene potencial para venderle más?",
                        "icono"        => "fa-line-chart",
                        "titulo_gauge" => "Score comercial",
                        "chart_score"  => "icChartComercial",
                        "chart_aport"  => "icChartAportacion2",
                        "legend_id"    => "icMotor2Legend",
                        "data_id"      => "icMotor2Data",
                    ));
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="row ic-motores-grid">
                <?php if ($resultadoMotor4) : ?>
                <div class="col-md-6">
                    <?php
                    icRenderMotorPanel($resultadoMotor4, array(
                        "titulo"       => "Motor 4 — Fidelidad",
                        "proposito"    => "¿Seguirá comprando con nosotros?",
                        "icono"        => "fa-heart",
                        "titulo_gauge" => "Score de fidelidad",
                        "chart_score"  => "icChartFidelidad",
                        "chart_aport"  => "icChartAportacion4",
                        "legend_id"    => "icMotor4Legend",
                        "data_id"      => "icMotor4Data",
                    ));
                    ?>
                </div>
                <?php endif; ?>
                <?php if ($resultadoMotor3) : ?>
                <div class="col-md-6">
                    <?php
                    icRenderMotorLineaCreditoPanel($resultadoMotor3, array(
                        "titulo"       => "Motor 3 — Línea de crédito",
                        "proposito"    => "¿Qué línea de crédito recomendar?",
                        "icono"        => "fa-credit-card",
                        "titulo_gauge" => "Score de línea",
                        "chart_score"  => "icChartLinea",
                        "chart_aport"  => "icChartAportacion3",
                        "legend_id"    => "icMotor3Legend",
                        "data_id"      => "icMotor3Data",
                    ));
                    ?>
                </div>
                <?php else : ?>
                <div class="col-md-6">
                    <div class="ic-motor-placeholder">
                        <div><i class="fa fa-lock"></i> Motor 3 — Línea de crédito — Sin datos</div>
                        <div class="ic-motor-placeholder-proposito">¿Qué línea de crédito recomendar?</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="row ic-motores-grid">
                <div class="col-md-6">
                    <div class="ic-motor-placeholder">
                        <div><i class="fa fa-lock"></i> Motor 5 — Rentabilidad — Próximamente</div>
                        <div class="ic-motor-placeholder-proposito">¿Cuánto beneficio genera?</div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </section>
</div>

<!-- MODAL DETALLE FACTOR -->
<div id="modalDetalleFactor" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content ic-wrap">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;">
                    <i id="icModalIcon" class="fa fa-info-circle"></i>
                    <span id="icModalTitulo">Detalle del factor</span>
                </h4>
            </div>
            <div class="modal-body">

                <div class="row ic-modal-metrics">
                    <div class="col-xs-4">
                        <div class="ic-modal-metric">
                            <div class="ic-modal-metric-icon ic-modal-icon-score" id="icModalScoreIcon">
                                <i class="fa fa-tachometer"></i>
                            </div>
                            <span class="ic-modal-metric-val" id="icModalScore">—</span>
                            <span class="ic-modal-metric-lbl">Score del factor</span>
                            <div class="ic-modal-metric-footer">
                                <div class="ic-modal-score-bar">
                                    <div class="ic-modal-score-bar-fill" id="icModalScoreBar" style="width:0;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="ic-modal-metric">
                            <div class="ic-modal-metric-icon ic-modal-icon-peso">
                                <i class="fa fa-balance-scale"></i>
                            </div>
                            <span class="ic-modal-metric-val" id="icModalPeso">—</span>
                            <span class="ic-modal-metric-lbl">Peso en el motor</span>
                            <div class="ic-modal-metric-footer">
                                <span class="ic-modal-spacer"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="ic-modal-metric">
                            <div class="ic-modal-metric-icon ic-modal-icon-aporte">
                                <i class="fa fa-puzzle-piece"></i>
                            </div>
                            <span class="ic-modal-metric-val" id="icModalAportacion">—</span>
                            <span class="ic-modal-metric-lbl">Pts al score total</span>
                            <div class="ic-modal-metric-footer">
                                <span class="ic-modal-spacer"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ic-modal-resumen" id="icModalDetalle">—</div>

                <div class="ic-modal-periodos" id="icModalPeriodosWrap" style="display:none;">
                    <p class="ic-modal-periodos-title" id="icModalPeriodosTitulo">Periodos de comparación</p>
                    <ul class="ic-modal-periodos-list" id="icModalPeriodosList"></ul>
                </div>

                <div class="ic-modal-logica" id="icModalLogicaWrap" style="display:none;">
                    <h5 class="ic-modal-section-title"><i class="fa fa-table"></i> <span id="icModalLogicaTitulo">Reglas de puntuación</span></h5>
                    <p class="ic-modal-logica-intro" id="icModalLogicaIntro"></p>
                    <div class="ic-modal-logica-table-wrap">
                        <table class="ic-modal-logica-table">
                            <thead>
                                <tr id="icModalLogicaHead"></tr>
                            </thead>
                            <tbody id="icModalLogicaBody"></tbody>
                        </table>
                    </div>
                </div>

                <h5 class="ic-modal-section-title"><i class="fa fa-database"></i> Datos del cliente</h5>
                <div class="row" id="icModalValores"></div>

                <h5 class="ic-modal-section-title" style="margin-top:8px;"><i class="fa fa-calculator"></i> Cómo se calculó</h5>
                <div class="ic-modal-calculo">
                    <div class="ic-modal-formula" id="icModalFormula">—</div>
                    <div class="ic-modal-aportacion-line">
                        <span>Aportación = score × peso</span>
                        <span><strong id="icModalAportacionCalc">—</strong> pts</span>
                    </div>
                </div>

                <details class="ic-modal-regla-wrap">
                    <summary><i class="fa fa-book"></i> Ver regla de negocio completa</summary>
                    <p id="icModalRegla">—</p>
                </details>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LÍNEA DE CRÉDITO RECOMENDADA -->
<div id="modalDetalleLinea" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content ic-wrap">
            <div class="modal-header bg-green">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;">
                    <i class="fa fa-credit-card"></i>
                    <span id="icModalLineaTitulo">¿Por qué esta línea de crédito?</span>
                </h4>
            </div>
            <div class="modal-body ic-modal-linea-body">

                <section class="ic-linea-seccion ic-linea-seccion-hero">
                    <div class="alert" id="icModalLineaAccion">
                        <strong id="icModalLineaAccionEtq">—</strong>
                        <span id="icModalLineaAccionTxt"></span>
                    </div>
                </section>

                <section class="ic-linea-seccion" id="icModalLineaCapacidadWrap" style="display:none;">
                    <h5 class="ic-linea-seccion-titulo"><span class="ic-linea-seccion-num">1</span> Análisis: ¿puede comprar y puede pagar?</h5>
                    <p class="ic-linea-seccion-intro" id="icModalLineaBalance"></p>
                    <div class="row ic-capacidad-dual">
                        <div class="col-sm-6" id="icModalCapPago">
                            <div class="ic-capacidad-box ic-capacidad-pago">
                                <div class="ic-capacidad-head">
                                    <span class="ic-cap-modal-titulo">Capacidad de pago</span>
                                    <strong class="ic-cap-modal-score">—</strong>
                                </div>
                                <p class="ic-capacidad-intro ic-cap-modal-intro"></p>
                                <ul class="ic-capacidad-lista ic-cap-modal-lista"></ul>
                                <div class="ic-cap-modal-ind-wrap">
                                    <p class="ic-cap-ind-titulo">Indicadores (Motor 1)</p>
                                    <ul class="ic-capacidad-indicadores ic-cap-modal-indicadores"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6" id="icModalCapCompra">
                            <div class="ic-capacidad-box ic-capacidad-compra">
                                <div class="ic-capacidad-head">
                                    <span class="ic-cap-modal-titulo">Capacidad de compra</span>
                                    <strong class="ic-cap-modal-score">—</strong>
                                </div>
                                <p class="ic-capacidad-intro ic-cap-modal-intro"></p>
                                <ul class="ic-capacidad-lista ic-cap-modal-lista"></ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ic-linea-seccion">
                    <h5 class="ic-linea-seccion-titulo"><span class="ic-linea-seccion-num">2</span> Cálculo del monto recomendado</h5>
                    <div class="ic-linea-calculo-card">
                        <p class="ic-linea-base-def" id="icModalLineaBaseDef"></p>
                        <div class="ic-modal-formula" id="icModalLineaFormula">—</div>
                        <div class="ic-linea-resultado">
                            <span>Resultado</span>
                            <strong id="icModalLineaCalculo">—</strong>
                        </div>
                    </div>
                    <div class="ic-modal-logica-table-wrap" style="margin-top:10px;">
                        <table class="ic-modal-logica-table ic-linea-pasos-table">
                            <thead>
                                <tr>
                                    <th>Paso</th>
                                    <th>Valor</th>
                                    <th>Qué significa</th>
                                </tr>
                            </thead>
                            <tbody id="icModalLineaPasos"></tbody>
                        </table>
                    </div>
                </section>

                <section class="ic-linea-seccion">
                    <h5 class="ic-linea-seccion-titulo"><span class="ic-linea-seccion-num">3</span> Referencia de líneas</h5>
                    <div class="row ic-linea-ref-grid" id="icModalLineaComparacion"></div>
                </section>

                <section class="ic-linea-seccion ic-linea-seccion-reglas">
                    <details class="ic-linea-details">
                        <summary><i class="fa fa-table"></i> Ver reglas de la acción recomendada</summary>
                        <div class="ic-modal-logica" id="icModalLineaAccionWrap" style="display:none; margin-top:10px;">
                            <p class="ic-modal-logica-intro" id="icModalLineaAccionIntro"></p>
                            <div class="ic-modal-logica-table-wrap">
                                <table class="ic-modal-logica-table">
                                    <thead>
                                        <tr id="icModalLineaAccionHead"></tr>
                                    </thead>
                                    <tbody id="icModalLineaAccionBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                </section>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
