<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$clienteFiltro = isset($_GET["cliente"]) ? trim($_GET["cliente"]) : "";
$clientes = ControladorInteligenciaComercial::ctrClientesAnalisis();
$resultadoMotor1 = null;

if ($clienteFiltro !== "") {
    $resultadoMotor1 = ControladorInteligenciaComercial::ctrCalcularMotorRiesgoCredito($clienteFiltro);
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
?>

<style>
.ic-wrap .ic-motor-box {
    border-radius: 6px;
    margin-bottom: 20px;
}
.ic-wrap .ic-motor-score {
    font-size: 32px;
    font-weight: 700;
    margin-right: 10px;
    vertical-align: middle;
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
        <?php else :
            $m = $resultadoMotor1;
            $cls = $m["clasificacion"];
            $colorScore = icColorScore($m["score"]);
        ?>

            <?php if ($nombreClienteFiltro !== "") : ?>
            <p class="text-muted" style="margin:0 0 15px;">
                <i class="fa fa-building-o"></i> <?php echo htmlspecialchars($nombreClienteFiltro, ENT_QUOTES, "UTF-8"); ?>
            </p>
            <?php endif; ?>

            <!-- MOTOR 1 -->
            <div class="box box-<?php echo htmlspecialchars($cls["color"], ENT_QUOTES, "UTF-8"); ?> ic-motor-box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-shield"></i> Motor 1 — Riesgo Crediticio
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
                    <div class="row" style="display:flex; align-items:center;">
                        <div class="col-sm-3">
                            <p class="ic-chart-title"><i class="fa fa-shield"></i> Score de riesgo</p>
                            <div class="ic-gauge-wrap">
                                <canvas id="icChartRiesgo" width="180" height="180"></canvas>
                                <div class="ic-gauge-center">
                                    <span class="ic-gauge-num" style="color:<?php echo $colorScore; ?>;">
                                        <?php echo number_format($m["score"], 1, ".", ""); ?>
                                    </span>
                                    <span class="ic-gauge-label"><?php echo htmlspecialchars($cls["etiqueta"], ENT_QUOTES, "UTF-8"); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <p class="ic-chart-title"><i class="fa fa-pie-chart"></i> Aportación por factor</p>
                            <div class="ic-aportacion-chart">
                                <canvas id="icChartAportacion" width="180" height="180"></canvas>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <ul class="ic-chart-legend" id="icChartAportacionLegend">
                                <?php
                                $coloresPastel = icColoresPastel();
                                $idxPastel = 0;
                                foreach ($m["factores"] as $factor) :
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

            <!-- MOTORES PRÓXIMOS -->
            <?php
            $motoresProximos = array("Motor 2 — Comercial", "Motor 3 — Rentabilidad", "Motor 4 — Fidelidad", "Motor 5 — Línea de crédito");
            foreach ($motoresProximos as $titulo) :
            ?>
                <div class="ic-motor-placeholder">
                    <i class="fa fa-lock"></i> <?php echo htmlspecialchars($titulo, ENT_QUOTES, "UTF-8"); ?> — Próximamente
                </div>
            <?php endforeach; ?>

            <script type="application/json" id="icMotor1Data"><?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?></script>

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
