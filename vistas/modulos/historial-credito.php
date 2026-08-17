<?php
if (!function_exists("dcUsuarioPuedeVerHistorialCredito") || !dcUsuarioPuedeVerHistorialCredito()) {
    denegarAccesoModulo();
    return;
}

require_once __DIR__ . "/dashboard-decisiones/helpers.php";

date_default_timezone_set("America/Lima");

$fechaDesde = isset($_GET["desde"]) ? trim((string) $_GET["desde"]) : date("Y-m-d", strtotime("-30 days"));
$fechaHasta = isset($_GET["hasta"]) ? trim((string) $_GET["hasta"]) : date("Y-m-d");
$tipoAccion = isset($_GET["tipo"]) ? strtoupper(trim((string) $_GET["tipo"])) : "";
$q = isset($_GET["q"]) ? trim((string) $_GET["q"]) : "";
$tabInicial = isset($_GET["tab"]) ? strtolower(trim((string) $_GET["tab"])) : "cola";
if ($tabInicial !== "cola" && $tabInicial !== "movimientos" && $tabInicial !== "dashboard" && $tabInicial !== "controles") {
    $tabInicial = "cola";
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = date("Y-m-d", strtotime("-30 days"));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = date("Y-m-d");
}

$vendedorSeleccionado = ControladorDashboardDecisiones::ctrVendedorSeleccionado();
$vendedoresPermitidos = ControladorDashboardDecisiones::ctrVendedoresPermitidos();
$cola = ControladorDashboardDecisiones::ctrColaPedidosCredito(80, $vendedorSeleccionado);
$generados = $cola["generados"];
$aprobados = $cola["aprobados"];
$resumenCola = $cola["resumen"];

$controlesInicial = ControladorDecisionesCredito::ctrListarControlesPostAprobacion(array(
    "limite" => 100,
));
$filasControles = (!empty($controlesInicial["ok"]) && isset($controlesInicial["filas"]))
    ? $controlesInicial["filas"]
    : array();
$puedeLiberarControl = !empty($controlesInicial["puede_liberar"]);
$controlesInicialError = !empty($controlesInicial["ok"])
    ? ""
    : (isset($controlesInicial["msg"]) ? (string) $controlesInicial["msg"] : "");

$datos = ControladorDecisionesCredito::ctrListarHistorialAcciones(array(
    "fecha_desde" => $fechaDesde,
    "fecha_hasta" => $fechaHasta,
    "tipo_accion" => $tipoAccion,
    "q" => $q,
    "limite" => 200,
));

$filasMovimientos = (!empty($datos["ok"]) && isset($datos["filas"])) ? $datos["filas"] : array();
$resumen = (!empty($datos["ok"]) && isset($datos["resumen"])) ? $datos["resumen"] : array(
    "APROBADO" => 0,
    "OBJECION" => 0,
    "OBJECION_CERRADA" => 0,
    "ANULADO" => 0,
    "total" => 0,
);

function hcFmtMonto($lista, $monto)
{
    if ($monto === null || $monto === "") {
        return "—";
    }
    $simbolo = ($lista === "precio1") ? "$ " : "S/ ";
    return $simbolo . number_format((float) $monto, 2);
}

function hcFmtFecha($fecha)
{
    if ($fecha === null || $fecha === "") {
        return "—";
    }
    $ts = strtotime((string) $fecha);
    if ($ts === false || $ts <= 0 || (int) date("Y", $ts) < 2000) {
        return "—";
    }

    return date("d/m/Y H:i", $ts);
}

function hcDashAyuda($texto)
{
    return '<button type="button" class="hc-dash-ayuda" aria-label="Qué significa este indicador">'
        . '<i class="fa fa-question"></i>'
        . '<span class="hc-dash-ayuda-tip" role="tooltip">'
        . htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8")
        . '</span></button>';
}
?>

<div class="content-wrapper hc-page">
    <section class="content-header">
        <div class="hc-header-row">
            <div>
                <h1>
                    Historial de crédito
                    <small>Aprobar pedidos y controlar lo que pasó</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
                    <li><a href="index.php?ruta=dashboard-decisiones">Centro de Decisiones</a></li>
                    <li class="active">Historial de crédito</li>
                </ol>
            </div>
            <a href="index.php?ruta=dashboard-decisiones" class="btn btn-default btn-sm">
                <i class="fa fa-gavel"></i> Ir al Centro de Decisiones
            </a>
        </div>
    </section>

    <section class="content">
        <ul class="nav nav-tabs hc-tabs" id="hcTabs">
            <li class="<?php echo $tabInicial === "cola" ? "active" : ""; ?>">
                <a href="#hcTabCola" data-toggle="tab">
                    <i class="fa fa-inbox"></i> Cola de pedidos
                    <span class="badge" id="hcTabBadgeCola"><?php echo (int) $resumenCola["generados"]; ?></span>
                </a>
            </li>
            <li class="<?php echo $tabInicial === "controles" ? "active" : ""; ?>">
                <a href="#hcTabControles" data-toggle="tab">
                    <i class="fa fa-lock"></i> Controles pendientes
                    <span class="badge bg-red" id="hcTabBadgeControles"><?php echo count($filasControles); ?></span>
                </a>
            </li>
            <li class="<?php echo $tabInicial === "dashboard" ? "active" : ""; ?>">
                <a href="#hcTabDashboard" data-toggle="tab">
                    <i class="fa fa-bar-chart"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo $tabInicial === "movimientos" ? "active" : ""; ?>">
                <a href="#hcTabMovimientos" data-toggle="tab">
                    <i class="fa fa-history"></i> Movimientos
                </a>
            </li>
        </ul>

        <div class="tab-content hc-tab-content">
            <div class="tab-pane <?php echo $tabInicial === "cola" ? "active" : ""; ?>" id="hcTabCola">
                <form class="hc-filtro-cola" id="hcFiltroColaForm" onsubmit="return false;">
                    <div class="hc-filtro-cola-group">
                        <label for="hcFiltroVendedor" class="hc-filtro-cola-label">
                            <i class="fa fa-user"></i> Vendedor
                        </label>
                        <select id="hcFiltroVendedor"
                                name="vendedor"
                                class="form-control selectpicker hc-filtro-select"
                                data-live-search="true"
                                data-size="8"
                                title="Todos los vendedores">
                            <option value="">Todos los vendedores</option>
                            <?php foreach ($vendedoresPermitidos as $vend) : ?>
                                <option value="<?php echo htmlspecialchars($vend["codigo"]); ?>"
                                    <?php echo ($vendedorSeleccionado === (string) $vend["codigo"]) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($vend["codigo"] . " - " . $vend["descripcion"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="btnHcActualizarCola" class="btn btn-primary btn-sm">
                            <i class="fa fa-refresh"></i> Actualizar
                        </button>
                        <button type="button" id="btnHcLimpiarVendedor" class="btn btn-default btn-sm" title="Quitar filtro de vendedor">
                            <i class="fa fa-times"></i> Limpiar
                        </button>
                    </div>
                </form>
                <div id="hcColaWrap">
                    <?php include __DIR__ . "/historial-credito/tabla-cola.php"; ?>
                </div>
            </div>

            <div class="tab-pane <?php echo $tabInicial === "controles" ? "active" : ""; ?>" id="hcTabControles">
                <div class="box box-solid dd-box hc-cola-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-lock"></i> Controles post-aprobación
                        </h3>
                        <span class="label label-danger pull-right" id="hcBadgeControles"><?php echo count($filasControles); ?></span>
                    </div>
                    <div class="box-body">
                        <?php if ($controlesInicialError !== "") : ?>
                            <div class="alert alert-danger hc-controles-alert">
                                <?php echo htmlspecialchars($controlesInicialError); ?>
                            </div>
                        <?php endif; ?>
                        <p class="text-muted hc-controles-intro">
                            Pedidos con condición pendiente antes de facturar (aprobado, APT o confirmado).
                            Los marcados con <span class="label label-danger">FAC</span> bloquean la facturación hasta liberarlos.
                            Si un pedido ya aprobado requiere seguimiento, usa el botón <i class="fa fa-lock"></i> en la cola de aprobados.
                        </p>
                    </div>
                    <div class="box-body table-responsive dd-table-wrap" style="border-top:1px solid #f4f4f4;">
                        <table class="table table-hover table-condensed dd-table" id="hcTablaControles">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Estado</th>
                                    <th class="dd-col-cliente">Cliente</th>
                                    <th>Condición</th>
                                    <th>Área</th>
                                    <th>Registró</th>
                                    <th class="text-right">Total c/IGV</th>
                                    <th class="text-center">Desde</th>
                                    <th>Días</th>
                                    <th class="text-center" width="100px"></th>
                                </tr>
                            </thead>
                            <tbody id="hcBodyControles">
                                <?php
                                $filasTablaControles = $filasControles;
                                include __DIR__ . "/historial-credito/tabla-controles.php";
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane <?php echo $tabInicial === "dashboard" ? "active" : ""; ?>" id="hcTabDashboard">
                <form class="hc-dash-filtros" id="hcDashFiltrosForm" onsubmit="return false;">
                    <div class="hc-dash-filtros-grid">
                        <div class="form-group">
                            <label for="hcDashDesde">Desde</label>
                            <input type="date" class="form-control input-sm" id="hcDashDesde" value="<?php echo htmlspecialchars($fechaDesde); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hcDashHasta">Hasta</label>
                            <input type="date" class="form-control input-sm" id="hcDashHasta" value="<?php echo htmlspecialchars($fechaHasta); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hcDashVendedor"><i class="fa fa-user"></i> Vendedor</label>
                            <select id="hcDashVendedor"
                                    name="vendedor"
                                    class="form-control selectpicker hc-filtro-select"
                                    data-live-search="true"
                                    data-size="8"
                                    title="Todos los vendedores">
                                <option value="">Todos los vendedores</option>
                                <?php foreach ($vendedoresPermitidos as $vend) : ?>
                                    <option value="<?php echo htmlspecialchars($vend["codigo"]); ?>"
                                        <?php echo ($vendedorSeleccionado === (string) $vend["codigo"]) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($vend["codigo"] . " - " . $vend["descripcion"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="hcDashDiasAbiertos">Obj. abiertas ≥ días</label>
                            <input type="number" min="1" max="90" class="form-control input-sm" id="hcDashDiasAbiertos" value="3">
                        </div>
                        <div class="form-group hc-dash-filtro-acciones">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnHcDashActualizar">
                                    <i class="fa fa-refresh"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="hcDashLoading" class="hc-dash-loading text-center text-muted" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando dashboard…</p>
                </div>

                <div id="hcDashContenido">
                    <div class="hc-dash-pulso" id="hcDashPulso">
                        <div class="hc-dash-pulso-estado" id="hcDashPulsoEstado">
                            <span class="hc-dash-pulso-dot" id="hcDashPulsoDot"></span>
                            <span id="hcDashPulsoTexto">Cargando actividad…</span>
                        </div>
                        <div class="row hc-dash-pulso-kpis">
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--hoy">
                                    <?php echo hcDashAyuda("Cuántas acciones de crédito se registraron hoy: aprobar, objetar, anular o cerrar objeciones."); ?>
                                    <span class="hc-dash-pulso-lbl">Hoy</span>
                                    <strong id="hcDashPulsoHoy">—</strong>
                                    <span class="hc-kpi-sub">acciones</span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--sem">
                                    <?php echo hcDashAyuda("Total de acciones de crédito desde el lunes de esta semana hasta hoy."); ?>
                                    <span class="hc-dash-pulso-lbl">Esta semana</span>
                                    <strong id="hcDashPulsoSem">—</strong>
                                    <span class="hc-kpi-sub">acciones</span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--ultima">
                                    <?php echo hcDashAyuda("Hace cuánto fue la última acción registrada en el historial de crédito. Sirve para ver si el equipo está gestionando ahora."); ?>
                                    <span class="hc-dash-pulso-lbl">Última gestión</span>
                                    <strong id="hcDashPulsoUltima">—</strong>
                                    <span class="hc-kpi-sub" id="hcDashPulsoUltimaSub">—</span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--team">
                                    <?php echo hcDashAyuda("Cantidad de usuarios distintos que registraron al menos una acción de crédito hoy."); ?>
                                    <span class="hc-dash-pulso-lbl">Analistas hoy</span>
                                    <strong id="hcDashPulsoTeam">—</strong>
                                    <span class="hc-kpi-sub">activos</span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--through">
                                    <?php echo hcDashAyuda("Promedio de acciones por día en el rango de fechas seleccionado arriba."); ?>
                                    <span class="hc-dash-pulso-lbl">Ritmo</span>
                                    <strong id="hcDashPulsoRitmo">—</strong>
                                    <span class="hc-kpi-sub">acciones/día</span>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 col-md-2">
                                <div class="hc-dash-pulso-item hc-dash-pulso-item--sla">
                                    <?php echo hcDashAyuda("Porcentaje de objeciones cerradas en 48 horas o menos, dentro del período filtrado."); ?>
                                    <span class="hc-dash-pulso-lbl">SLA 48h</span>
                                    <strong id="hcDashPulsoSla">—</strong>
                                    <span class="hc-kpi-sub">obj. cerradas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hc-dash-metricas" id="hcDashKpis">
                        <div class="hc-dash-kpi hc-dash-kpi--cola">
                            <?php echo hcDashAyuda("Pedidos en estado GENERADO que aún esperan una decisión de crédito (aprobar, objetar o anular)."); ?>
                            <span class="hc-kpi-lbl">Cola actual</span>
                            <strong id="hcDashKpiCola"><?php echo (int) $resumenCola["generados"]; ?></strong>
                            <span class="hc-kpi-sub">pedidos GENERADO</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--cola24">
                            <?php echo hcDashAyuda("De la cola actual, cuántos pedidos llevan más de 24 horas sin gestión. El monto es la suma en soles de esos pedidos."); ?>
                            <span class="hc-kpi-lbl">Cola sin atender 24h+</span>
                            <strong id="hcDashSaludCola24">—</strong>
                            <span class="hc-kpi-sub" id="hcDashSaludColaMonto">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--edad">
                            <?php echo hcDashAyuda("Promedio de días que los pedidos GENERADO llevan esperando. El máximo muestra el pedido más antiguo en cola."); ?>
                            <span class="hc-kpi-lbl">Edad promedio cola</span>
                            <strong id="hcDashSaludEdad">—</strong>
                            <span class="hc-kpi-sub" id="hcDashSaludEdadMax">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--aprobado">
                            <?php echo hcDashAyuda("Aprobados ÷ total de decisiones (aprobados + objeciones + anulados) en el período. Abajo: monto en soles de pedidos aprobados."); ?>
                            <span class="hc-kpi-lbl">Tasa aprobación</span>
                            <strong id="hcDashKpiTasaAprob">—</strong>
                            <span class="hc-kpi-sub" id="hcDashKpiMontoAprob">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--objecion">
                            <?php echo hcDashAyuda("Objeciones ÷ total de decisiones en el período. Abajo: monto en soles de esos pedidos objetados."); ?>
                            <span class="hc-kpi-lbl">Tasa objeción</span>
                            <strong id="hcDashKpiTasaObj">—</strong>
                            <span class="hc-kpi-sub" id="hcDashKpiMontoObj">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--anulado">
                            <?php echo hcDashAyuda("Anulados ÷ total de decisiones en el período. Junto con aprobación y objeción, las tres tasas suman 100%."); ?>
                            <span class="hc-kpi-lbl">Tasa anulación</span>
                            <strong id="hcDashKpiTasaAnul">—</strong>
                            <span class="hc-kpi-sub" id="hcDashKpiAnulN">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--total">
                            <?php echo hcDashAyuda("Total de decisiones en el período: cada aprobación, objeción o anulación cuenta como una. Abajo: desglose por cantidad."); ?>
                            <span class="hc-kpi-lbl">Decisiones</span>
                            <strong id="hcDashKpiDecisiones">—</strong>
                            <span class="hc-kpi-sub" id="hcDashKpiDecisionesDelta">apr + obj + anul</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--riesgo">
                            <?php echo hcDashAyuda("Suma en soles de pedidos con objeción vigente (aún no cerrada ni revertida). Refleja exposición comercial pendiente de resolver."); ?>
                            <span class="hc-kpi-lbl">Monto en riesgo</span>
                            <strong id="hcDashKpiRiesgo">—</strong>
                            <span class="hc-kpi-sub" id="hcDashKpiRiesgoN">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--tiempo">
                            <?php echo hcDashAyuda("Tiempo promedio entre registrar una objeción y cerrarla, para las objeciones resueltas en el período filtrado."); ?>
                            <span class="hc-kpi-lbl">Tiempo resolución</span>
                            <strong id="hcDashKpiTiempo">—</strong>
                            <span class="hc-kpi-sub">promedio objeciones</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--delta">
                            <?php echo hcDashAyuda("Cambio porcentual en la cantidad de decisiones vs el período anterior de igual duración (justo antes del rango de fechas)."); ?>
                            <span class="hc-kpi-lbl">Δ vs período anterior</span>
                            <strong id="hcDashSaludDelta">—</strong>
                            <span class="hc-kpi-sub" id="hcDashSaludDeltaSub">—</span>
                        </div>
                        <div class="hc-dash-kpi hc-dash-kpi--delta-aprob">
                            <?php echo hcDashAyuda("Cambio porcentual en cantidad de aprobaciones vs el período anterior. Abajo: variación del monto aprobado en soles."); ?>
                            <span class="hc-kpi-lbl">Δ aprobados</span>
                            <strong id="hcDashSaludDeltaAprob">—</strong>
                            <span class="hc-kpi-sub" id="hcDashSaludDeltaAprobSub">—</span>
                        </div>
                    </div>

                    <div class="row hc-dash-graficos">
                        <div class="col-lg-6">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Actividad por hora</h3>
                                </div>
                                <div class="box-body">
                                    <div class="hc-dash-chart-wrap hc-dash-chart-wrap--sm">
                                        <canvas id="hcChartHora"></canvas>
                                    </div>
                                    <p class="text-muted hc-dash-empty" id="hcChartHoraEmpty" style="display:none;">Sin actividad en el rango.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-calendar"></i> Actividad por día</h3>
                                </div>
                                <div class="box-body">
                                    <div class="hc-dash-chart-wrap hc-dash-chart-wrap--sm">
                                        <canvas id="hcChartDow"></canvas>
                                    </div>
                                    <p class="text-muted hc-dash-empty" id="hcChartDowEmpty" style="display:none;">Sin actividad en el rango.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row hc-dash-graficos">
                        <div class="col-lg-8">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-line-chart"></i> Evolución diaria</h3>
                                </div>
                                <div class="box-body">
                                    <div class="hc-dash-chart-wrap">
                                        <canvas id="hcChartSerie"></canvas>
                                    </div>
                                    <p class="text-muted hc-dash-empty" id="hcChartSerieEmpty" style="display:none;">Sin datos en el rango.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-filter"></i> Embudo de gestión</h3>
                                </div>
                                <div class="box-body">
                                    <div class="hc-dash-chart-wrap hc-dash-chart-wrap--sm">
                                        <canvas id="hcChartEmbudo"></canvas>
                                    </div>
                                    <p class="text-muted hc-dash-empty" id="hcChartEmbudoEmpty" style="display:none;">Sin decisiones en el rango.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row hc-dash-graficos">
                        <div class="col-lg-12">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Pareto de motivos de objeción</h3>
                                </div>
                                <div class="box-body">
                                    <div class="hc-dash-chart-wrap hc-dash-chart-wrap--md">
                                        <canvas id="hcChartMotivos"></canvas>
                                    </div>
                                    <p class="text-muted hc-dash-empty" id="hcChartMotivosEmpty" style="display:none;">Sin objeciones en el rango.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row hc-dash-tablas">
                        <div class="col-lg-12">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-bolt"></i> Últimas gestiones en tiempo real</h3>
                                </div>
                                <div class="box-body table-responsive">
                                    <table class="table table-hover table-condensed hc-dash-tabla" id="hcTablaUltimas">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Hace</th>
                                                <th>Tipo</th>
                                                <th>Pedido</th>
                                                <th>Cliente</th>
                                                <th class="text-right">Monto</th>
                                                <th>Detalle</th>
                                                <th>Analista</th>
                                            </tr>
                                        </thead>
                                        <tbody id="hcTablaUltimasBody">
                                            <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row hc-dash-tablas">
                        <div class="col-lg-6">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-users"></i> Ranking analistas</h3>
                                </div>
                                <div class="box-body table-responsive">
                                    <table class="table table-hover table-condensed hc-dash-tabla" id="hcTablaAnalistas">
                                        <thead>
                                            <tr>
                                                <th>Analista</th>
                                                <th class="text-center">Aprob.</th>
                                                <th class="text-center">Objec.</th>
                                                <th class="text-center">% Aprob.</th>
                                                <th class="text-right">Monto aprob. S/</th>
                                            </tr>
                                        </thead>
                                        <tbody id="hcTablaAnalistasBody">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="box box-solid hc-dash-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-user-times"></i> Clientes más objetados</h3>
                                </div>
                                <div class="box-body table-responsive">
                                    <table class="table table-hover table-condensed hc-dash-tabla" id="hcTablaClientes">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th class="text-center"># Obj.</th>
                                                <th class="text-right">Monto S/</th>
                                                <th>Último motivo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="hcTablaClientesBody">
                                            <tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row hc-dash-tablas">
                        <div class="col-lg-12">
                            <div class="box box-solid hc-dash-box hc-dash-box--alert">
                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        Objeciones abiertas
                                        <small id="hcDashAbiertasSub">≥ 3 días</small>
                                    </h3>
                                </div>
                                <div class="box-body table-responsive">
                                    <table class="table table-hover table-condensed hc-dash-tabla" id="hcTablaAbiertas">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Días</th>
                                                <th>Pedido</th>
                                                <th>Cliente</th>
                                                <th>Motivo</th>
                                                <th class="text-right">Monto</th>
                                                <th>Analista</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody id="hcTablaAbiertasBody">
                                            <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane <?php echo $tabInicial === "movimientos" ? "active" : ""; ?>" id="hcTabMovimientos">
                <div class="row hc-resumen-row">
                    <div class="col-sm-3 col-xs-6">
                        <div class="hc-kpi hc-kpi--aprobado">
                            <span class="hc-kpi-lbl">Aprobados</span>
                            <strong id="hcKpiAprobado"><?php echo (int) $resumen["APROBADO"]; ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6">
                        <div class="hc-kpi hc-kpi--objecion">
                            <span class="hc-kpi-lbl">Objeciones</span>
                            <strong id="hcKpiObjecion"><?php echo (int) $resumen["OBJECION"]; ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6">
                        <div class="hc-kpi hc-kpi--cerrada">
                            <span class="hc-kpi-lbl">Obj. cerradas</span>
                            <strong id="hcKpiCerrada"><?php echo (int) $resumen["OBJECION_CERRADA"]; ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6">
                        <div class="hc-kpi hc-kpi--anulado">
                            <span class="hc-kpi-lbl">Anulados</span>
                            <strong id="hcKpiAnulado"><?php echo (int) $resumen["ANULADO"]; ?></strong>
                        </div>
                    </div>
                </div>

                <div class="box box-primary hc-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-history"></i> Movimientos</h3>
                    </div>
                    <div class="box-body">
                        <form class="hc-filtros" id="hcFiltrosForm" onsubmit="return false;">
                            <div class="hc-filtros-grid">
                                <div class="form-group">
                                    <label for="hcFechaDesde">Desde</label>
                                    <input type="date" class="form-control input-sm" id="hcFechaDesde" value="<?php echo htmlspecialchars($fechaDesde); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="hcFechaHasta">Hasta</label>
                                    <input type="date" class="form-control input-sm" id="hcFechaHasta" value="<?php echo htmlspecialchars($fechaHasta); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="hcTipo">Tipo</label>
                                    <select class="form-control input-sm" id="hcTipo">
                                        <option value="">Todos</option>
                                        <option value="APROBADO" <?php echo $tipoAccion === "APROBADO" ? "selected" : ""; ?>>Aprobados</option>
                                        <option value="OBJECION" <?php echo $tipoAccion === "OBJECION" ? "selected" : ""; ?>>Objeciones</option>
                                        <option value="OBJECION_CERRADA" <?php echo $tipoAccion === "OBJECION_CERRADA" ? "selected" : ""; ?>>Obj. cerradas</option>
                                        <option value="ANULADO" <?php echo $tipoAccion === "ANULADO" ? "selected" : ""; ?>>Anulados</option>
                                        <option value="CONTROL_REGISTRADO" <?php echo $tipoAccion === "CONTROL_REGISTRADO" ? "selected" : ""; ?>>Controles registrados</option>
                                        <option value="DESPACHO_AUTORIZADO" <?php echo $tipoAccion === "DESPACHO_AUTORIZADO" ? "selected" : ""; ?>>Despachos autorizados</option>
                                    </select>
                                </div>
                                <div class="form-group hc-filtro-buscar">
                                    <label for="hcBuscar">Buscar</label>
                                    <input type="text" class="form-control input-sm" id="hcBuscar"
                                           value="<?php echo htmlspecialchars($q); ?>"
                                           placeholder="Pedido, cliente, motivo…">
                                </div>
                                <div class="form-group hc-filtro-acciones">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-sm" id="btnHcBuscar">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm" id="btnHcLimpiar">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-condensed hc-tabla" id="hcTabla">
                                <thead>
                                    <tr>
                                        <th class="text-center">Fecha</th>
                                        <th>Tipo</th>
                                        <th>Pedido</th>
                                        <th>Cliente</th>
                                        <th class="text-right">Monto c/IGV</th>
                                        <th>Detalle</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody id="hcTablaBody">
                                    <?php if (empty($filasMovimientos)) : ?>
                                        <tr class="hc-empty">
                                            <td colspan="7" class="text-center text-muted">
                                                No hay movimientos en el rango seleccionado.
                                                Las acciones nuevas (aprobar / objeción / anular) aparecerán aquí.
                                            </td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($filasMovimientos as $row) : ?>
                                            <tr>
                                                <td class="text-center hc-fecha-cell">
                                                    <span class="hc-fecha"><?php echo htmlspecialchars(hcFmtFecha(isset($row["fecha"]) ? $row["fecha"] : "")); ?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-<?php echo htmlspecialchars($row["tipo_clase"]); ?>">
                                                        <?php echo htmlspecialchars($row["tipo_etiqueta"]); ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($row["codigo_pedido"]); ?></strong></td>
                                                <td>
                                                    <span class="hc-cli-cod"><?php echo htmlspecialchars($row["codigo_cliente"]); ?></span>
                                                    <?php echo htmlspecialchars($row["cliente_nombre"]); ?>
                                                </td>
                                                <td class="text-right hc-monto">
                                                    <strong><?php echo hcFmtMonto(
                                                        isset($row["pedido_lista"]) ? $row["pedido_lista"] : "",
                                                        isset($row["pedido_total"]) ? $row["pedido_total"] : null
                                                    ); ?></strong>
                                                </td>
                                                <td class="hc-detalle">
                                                    <?php
                                                    $tieneMotivo = !empty($row["motivo_etiqueta"]);
                                                    $tieneComentario = !empty($row["comentario"]);
                                                    $tieneDetalle = !empty($row["detalle"]);
                                                    $esAprobado = ($row["tipo_accion"] === "APROBADO");
                                                    ?>
                                                    <?php if ($tieneMotivo) : ?>
                                                        <div class="hc-detalle-texto">
                                                            <strong><?php echo htmlspecialchars($row["motivo_etiqueta"]); ?></strong>
                                                            <?php if ($tieneComentario) : ?>
                                                                <div class="text-muted"><?php echo htmlspecialchars($row["comentario"]); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php elseif ($tieneComentario) : ?>
                                                        <div class="hc-detalle-texto"><?php echo htmlspecialchars($row["comentario"]); ?></div>
                                                    <?php elseif ($tieneDetalle) : ?>
                                                        <div class="hc-detalle-texto"><?php echo htmlspecialchars($row["detalle"]); ?></div>
                                                    <?php elseif (!$esAprobado) : ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>

                                                    <?php if ($esAprobado || $row["tipo_accion"] === "ANULADO") : ?>
                                                        <div class="hc-snap">
                                                            <?php if (!empty($row["categoria_codigo"])) :
                                                                $hexCat = !empty($row["categoria_color"])
                                                                    ? $row["categoria_color"]
                                                                    : (class_exists("ControladorCategoriasClientes")
                                                                        ? ControladorCategoriasClientes::ctrResolverColorCategoria("", $row["categoria_codigo"])
                                                                        : "#777777");
                                                                ?>
                                                                <span class="hc-snap-item">
                                                                    <span class="hc-cat-sigla" style="background-color:<?php echo htmlspecialchars($hexCat, ENT_QUOTES, "UTF-8"); ?>;">
                                                                        <?php echo htmlspecialchars(strtoupper($row["categoria_codigo"])); ?>
                                                                    </span>
                                                                    <?php if (!empty($row["categoria_nombre"])) : ?>
                                                                        <span class="hc-snap-nombre"><?php echo htmlspecialchars($row["categoria_nombre"]); ?></span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($row["nombre_grupo"]) || (!empty($row["cupo_modo"]) && $row["cupo_modo"] === "grupo")) : ?>
                                                                <span class="hc-snap-item">
                                                                    <i class="fa fa-sitemap"></i>
                                                                    <span class="hc-snap-nombre"><?php echo htmlspecialchars(!empty($row["nombre_grupo"]) ? $row["nombre_grupo"] : $row["codigo_grupo"]); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (isset($row["linea_referencia"]) && $row["linea_referencia"] !== null && $row["linea_referencia"] !== "") : ?>
                                                                <span class="hc-snap-item" title="<?php echo htmlspecialchars(isset($row["etiqueta_linea"]) ? $row["etiqueta_linea"] : "Línea"); ?>">
                                                                    <span class="hc-snap-nombre">Línea S/ <?php echo number_format((float) $row["linea_referencia"], 0); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (isset($row["cupo_disponible"]) && $row["cupo_disponible"] !== null && $row["cupo_disponible"] !== "") : ?>
                                                                <span class="hc-snap-item">
                                                                    <span class="hc-snap-nombre">Disp. S/ <?php echo number_format((float) $row["cupo_disponible"], 0); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (isset($row["deuda_actual"]) && $row["deuda_actual"] !== null && $row["deuda_actual"] !== "") : ?>
                                                                <span class="hc-snap-item">
                                                                    <span class="hc-snap-nombre">Deuda S/ <?php echo number_format((float) $row["deuda_actual"], 0); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (isset($row["utilizacion_pct"]) && $row["utilizacion_pct"] !== null && $row["utilizacion_pct"] !== "") : ?>
                                                                <span class="hc-snap-item">
                                                                    <span class="hc-snap-nombre">Util. <?php echo number_format((float) $row["utilizacion_pct"], 0); ?>%</span>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (isset($row["score_riesgo"]) && $row["score_riesgo"] !== null && $row["score_riesgo"] !== "") :
                                                                $sr = (float) $row["score_riesgo"];
                                                                if ($sr >= 90) {
                                                                    $srCls = "success";
                                                                } elseif ($sr >= 80) {
                                                                    $srCls = "primary";
                                                                } elseif ($sr >= 70) {
                                                                    $srCls = "info";
                                                                } elseif ($sr >= 60) {
                                                                    $srCls = "warning";
                                                                } else {
                                                                    $srCls = "danger";
                                                                }
                                                                ?>
                                                                <span class="hc-snap-item hc-riesgo hc-riesgo--<?php echo $srCls; ?>">
                                                                    <span class="hc-snap-nombre">Riesgo <?php echo number_format($sr, 0); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row["usuario_nombre"]); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Stub fuera de pantalla: el JS de acciones necesita #ddFiltroVendedor / #ddContenidoDashboard -->
<div id="hcDdStub" aria-hidden="true">
    <select id="ddFiltroVendedor"><option value=""></option></select>
    <div id="ddContenidoDashboard"></div>
</div>

<div class="modal fade" id="modalDdMiniIc" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-mini-ic-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddMiniIcTitulo">
                    <i class="fa fa-user-circle"></i> Análisis para decisión
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddMiniIcSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddMiniIcBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando análisis del cliente…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddMiniIcLinkCompleto" target="_blank">
                    <i class="fa fa-external-link"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDdDecisionCredito" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-decision-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddDecisionCreditoTitulo">
                    <i class="fa fa-gavel"></i> Decisión de crédito
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddDecisionCreditoSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddDecisionCreditoBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando decisión…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddDecisionCreditoLinkIc" target="_blank">
                    <i class="fa fa-line-chart"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDdAprobarCategoria" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content dd-aprobar-cat-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-tags"></i> Categoría requerida para aprobar
                </h4>
            </div>
            <div class="modal-body">
                <p class="dd-aprobar-cat-cliente" id="ddAprobarCatCliente"></p>
                <p class="text-muted dd-aprobar-cat-hint" id="ddAprobarCatHint">
                    Antes de aprobar el pedido debes asignar una categoría comercial.
                </p>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="ddAprobarCatSelect">Categoría comercial</label>
                    <select id="ddAprobarCatSelect" class="form-control">
                        <option value="">Cargando categorías…</option>
                    </select>
                </div>
                <div id="ddAprobarCatPreview" class="dd-aprobar-cat-preview" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="ddAprobarCatConfirm">
                    <i class="fa fa-arrow-right"></i> Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDdAprobarPedido" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-check-circle"></i> Aprobar pedido
                </h4>
            </div>
            <div class="modal-body">
                <p class="dd-aprobar-cat-cliente" id="ddAprobarPedidoInfo"></p>
                <p class="text-muted" id="ddAprobarPedidoHint">
                    Puedes indicar un motivo y una observación (opcionales).
                </p>
                <div class="form-group">
                    <label for="ddAprobarPedidoMotivo">Motivo</label>
                    <select
                        id="ddAprobarPedidoMotivo"
                        class="form-control selectpicker dd-motivo-aprobacion-select"
                        data-live-search="true"
                        title="Sin motivo…"
                    >
                        <option value="">Sin motivo…</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="ddAprobarPedidoObs">Observación</label>
                    <textarea
                        id="ddAprobarPedidoObs"
                        class="form-control"
                        rows="3"
                        placeholder="Observación para la bitácora (opcional)"
                    ></textarea>
                </div>
                <?php include __DIR__ . "/dashboard-decisiones/aprobar-pedido-controles.php"; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="ddAprobarPedidoConfirm">
                    <i class="fa fa-check"></i> Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHcRegistrarControl" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-lock"></i> Registrar control
                </h4>
            </div>
            <div class="modal-body">
                <p class="dd-aprobar-cat-cliente" id="hcRegistrarControlInfo"></p>
                <p class="text-muted" id="hcRegistrarControlHint">
                    Para pedidos ya aprobados: registra una condición u observación operativa antes de despachar.
                </p>
                <?php include __DIR__ . "/historial-credito/registrar-control-fields.php"; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="hcRegistrarControlConfirm">
                    <i class="fa fa-lock"></i> Registrar control
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHcLiberarControl" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-unlock"></i> Liberar despacho
                </h4>
            </div>
            <div class="modal-body">
                <p class="dd-aprobar-cat-cliente" id="hcLiberarControlInfo"></p>
                <p class="text-muted" id="hcLiberarControlHint">
                    Confirma que se cumplió la condición. Si al aprobar no quedó claro quién autorizó, indícalo aquí.
                    El pedido podrá facturarse tras liberar.
                </p>
                <div class="form-group">
                    <label for="hcLiberarControlArea">Autorizado por (área)</label>
                    <select
                        id="hcLiberarControlArea"
                        class="form-control selectpicker hc-control-area-select"
                        data-live-search="true"
                        title="Selecciona área…"
                    >
                        <option value="">Sin área específica…</option>
                    </select>
                    <p class="help-block text-muted">
                        Ej.: Gerencia General, Créditos y Cobranzas…
                    </p>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="hcLiberarControlObs">Observación de cierre</label>
                    <textarea
                        id="hcLiberarControlObs"
                        class="form-control"
                        rows="3"
                        placeholder="Ej.: cliente pagó deuda; APT fue avisado…"
                    ></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="hcLiberarControlConfirm">
                    <i class="fa fa-unlock"></i> Liberar despacho
                </button>
            </div>
        </div>
    </div>
</div>

<script>window.document.title = "Historial de crédito | Vasco System";</script>
