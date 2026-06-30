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
?>

<style>
.ic-wrap .ic-score-panel {
    background: linear-gradient(135deg, #1a2a4a 0%, #2c3e6b 100%);
    color: #fff;
    border-radius: 8px;
    padding: 20px;
    min-height: 280px;
}
.ic-wrap .ic-score-panel .ic-score-num {
    font-size: 72px;
    font-weight: 700;
    line-height: 1;
    margin: 0;
}
.ic-wrap .ic-gauge-wrap {
    position: relative;
    height: 200px;
    max-width: 200px;
    margin: 0 auto;
}
.ic-wrap .ic-factor-card {
    border-radius: 6px;
    border: 1px solid #e8e8e8;
    background: #fff;
    padding: 14px 16px;
    margin-bottom: 15px;
    transition: box-shadow .2s, border-color .2s;
    cursor: pointer;
}
.ic-wrap .ic-factor-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
    border-color: #3c8dbc;
}
.ic-wrap .ic-factor-card .ic-factor-score {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}
.ic-wrap .ic-progress {
    height: 8px;
    margin: 8px 0 10px;
    background: #ecf0f5;
    border-radius: 4px;
    overflow: hidden;
}
.ic-wrap .ic-progress-bar {
    height: 100%;
    border-radius: 4px;
    transition: width .4s ease;
}
.ic-wrap .ic-kpi-box {
    border-radius: 6px;
    padding: 12px 15px;
    background: #f9f9f9;
    border-left: 4px solid #3c8dbc;
    margin-bottom: 15px;
}
.ic-wrap .ic-kpi-box h4 {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 700;
}
.ic-wrap .ic-kpi-box p {
    margin: 0;
    color: #777;
    font-size: 12px;
}
.ic-wrap .ic-chart-box {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
}
.ic-wrap .ic-chart-box h4 {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 600;
    color: #444;
}
.ic-wrap .ic-motor-placeholder {
    opacity: .55;
    border: 2px dashed #ddd;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    color: #999;
    min-height: 100px;
}
.ic-wrap .ic-modal-valor {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.ic-wrap .ic-modal-valor:last-child { border-bottom: none; }
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
                <p style="margin:0;"><i class="fa fa-info-circle"></i> Seleccione un cliente para ver el análisis de riesgo crediticio.</p>
            </div>
        <?php elseif (!$resultadoMotor1) : ?>
            <div class="callout callout-warning">
                <p style="margin:0;">No se encontraron datos para el cliente seleccionado.</p>
            </div>
        <?php else :
            $m = $resultadoMotor1;
            $cls = $m["clasificacion"];
        ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="ic-score-panel text-center">
                        <p style="opacity:.8; margin:0 0 5px; font-size:13px;">
                            <i class="fa fa-shield"></i> Motor 1 — Riesgo Crediticio
                        </p>
                        <p class="ic-score-num" style="color:<?php echo icColorScore($m["score"]); ?>;">
                            <?php echo number_format($m["score"], 1); ?>
                        </p>
                        <span class="label label-<?php echo htmlspecialchars($cls["color"], ENT_QUOTES, "UTF-8"); ?>" style="font-size:14px; padding:6px 14px;">
                            <?php echo htmlspecialchars($cls["etiqueta"], ENT_QUOTES, "UTF-8"); ?>
                        </span>
                        <p style="margin:12px 0 0; opacity:.75; font-size:12px;">
                            <?php echo htmlspecialchars($nombreClienteFiltro, ENT_QUOTES, "UTF-8"); ?>
                        </p>
                        <div class="ic-gauge-wrap" style="margin-top:15px;">
                            <canvas id="icGaugeScore"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="ic-chart-box" style="height:280px;">
                        <h4><i class="fa fa-bar-chart"></i> Score por factor</h4>
                        <canvas id="icChartFactores" height="200"></canvas>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="ic-chart-box" style="height:280px;">
                        <h4><i class="fa fa-pie-chart"></i> Aportación al score final</h4>
                        <canvas id="icChartAportacion" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top:5px;">
                <div class="col-md-3">
                    <div class="ic-kpi-box" style="border-color:#00a65a;">
                        <h4><?php echo (int) $m["metricas"]["docs_a_tiempo"]; ?> / <?php echo (int) $m["metricas"]["total_docs"]; ?></h4>
                        <p>Cerrados a tiempo (≤<?php echo (int) $m["metricas"]["tolerancia_dias"]; ?> días)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ic-kpi-box" style="border-color:#f39c12;">
                        <h4><?php echo number_format($m["metricas"]["atraso_promedio"], 1); ?> días</h4>
                        <p>Atraso promedio</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ic-kpi-box" style="border-color:#3c8dbc;">
                        <h4><?php echo number_format($m["metricas"]["utilizacion_pct"], 1); ?>%</h4>
                        <p>Utilización de crédito</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ic-kpi-box" style="border-color:#dd4b39;">
                        <h4><?php echo (int) $m["metricas"]["incidencias"]; ?></h4>
                        <p>Incidencias comerciales</p>
                    </div>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-list-alt"></i> Factores de riesgo — clic para ver detalle</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <?php foreach ($m["factores"] as $factor) :
                            $colorFactor = icColorScore($factor["score"]);
                        ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="ic-factor-card btnDetalleFactor"
                                     data-factor="<?php echo htmlspecialchars($factor["clave"], ENT_QUOTES, "UTF-8"); ?>">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                        <div>
                                            <i class="fa <?php echo htmlspecialchars($factor["icono"], ENT_QUOTES, "UTF-8"); ?>" style="color:<?php echo $colorFactor; ?>;"></i>
                                            <strong style="font-size:13px; margin-left:4px;"><?php echo htmlspecialchars($factor["nombre"], ENT_QUOTES, "UTF-8"); ?></strong>
                                            <br><small class="text-muted">Peso <?php echo (int) $factor["peso"]; ?>% · Aporta <?php echo number_format($factor["aportacion"], 1); ?> pts</small>
                                        </div>
                                        <span class="ic-factor-score" style="color:<?php echo $colorFactor; ?>;">
                                            <?php echo number_format($factor["score"], 0); ?>
                                        </span>
                                    </div>
                                    <div class="ic-progress">
                                        <div class="ic-progress-bar" style="width:<?php echo min(100, $factor["score"]); ?>%; background:<?php echo $colorFactor; ?>;"></div>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars($factor["detalle"], ENT_QUOTES, "UTF-8"); ?></small>
                                    <div style="margin-top:8px;">
                                        <span class="text-primary" style="font-size:12px;"><i class="fa fa-search-plus"></i> Ver detalle</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php
                $motoresProximos = array("Motor 2 — Comercial", "Motor 3 — Rentabilidad", "Motor 4 — Fidelidad", "Motor 5 — Línea de crédito");
                foreach ($motoresProximos as $titulo) :
                ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="ic-motor-placeholder">
                            <i class="fa fa-lock" style="font-size:20px; display:block; margin-bottom:8px;"></i>
                            <?php echo htmlspecialchars($titulo, ENT_QUOTES, "UTF-8"); ?>
                            <br><small>Próximamente</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <script type="application/json" id="icMotor1Data"><?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?></script>

        <?php endif; ?>

    </section>
</div>

<!-- MODAL DETALLE FACTOR -->
<div id="modalDetalleFactor" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title" style="color:#fff;">
                    <i id="icModalIcon" class="fa fa-info-circle"></i>
                    <span id="icModalTitulo">Detalle del factor</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4 text-center">
                        <p style="font-size:56px; font-weight:700; margin:10px 0;" id="icModalScore">—</p>
                        <p class="text-muted">Score del factor (0 – 100)</p>
                        <p><span class="label label-default" id="icModalPeso">Peso —%</span></p>
                        <p><strong>Aportación al total:</strong> <span id="icModalAportacion">—</span> pts</p>
                    </div>
                    <div class="col-sm-8">
                        <div class="callout callout-info" style="margin-bottom:12px;">
                            <p style="margin:0;" id="icModalDetalle">—</p>
                        </div>
                        <h5 style="font-weight:600;"><i class="fa fa-calculator"></i> Fórmula aplicada</h5>
                        <p id="icModalFormula" class="text-muted" style="font-family:monospace; background:#f9f9f9; padding:10px; border-radius:4px;">—</p>
                        <h5 style="font-weight:600;"><i class="fa fa-book"></i> Regla de negocio</h5>
                        <p id="icModalRegla" class="text-muted">—</p>
                        <h5 style="font-weight:600;"><i class="fa fa-database"></i> Datos utilizados</h5>
                        <div id="icModalValores"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
