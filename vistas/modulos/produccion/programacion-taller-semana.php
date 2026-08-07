<?php
if (!isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso de producción.</div></section></div>';
    return;
}
if (!class_exists("ModeloProgramacionTallerSemana")) {
    require_once dirname(__FILE__) . "/../../../modelos/programacion-taller-semana.modelo.php";
}
$semanaActual = ModeloProgramacionTallerSemana::mdlSemanaActual();
$anioUrl = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) $semanaActual["anio"];
$semanaUrl = isset($_GET["semana"]) ? (int) $_GET["semana"] : (int) $semanaActual["semana"];
$infoActual = ModeloProgramacionTallerSemana::mdlRangoSemana($anioUrl, $semanaUrl);
if (!$infoActual) {
    $infoActual = ModeloProgramacionTallerSemana::mdlRangoSemana($semanaActual["anio"], $semanaActual["semana"]);
}
if (!$infoActual) {
    $infoActual = array("anio" => (int) date("o"), "semana" => (int) date("W"), "fecha_inicio" => date("Y-m-d"), "fecha_fin" => date("Y-m-d"));
}
$tabUrl = isset($_GET["tab"]) ? trim((string) $_GET["tab"]) : "programar";
if ($tabUrl !== "programado") {
    $tabUrl = "programar";
}
$modeloUrl = isset($_GET["modelo"]) ? trim((string) $_GET["modelo"]) : "";
$tallerUrl = isset($_GET["taller"]) ? trim((string) $_GET["taller"]) : "";
$nivelUrl = isset($_GET["nivel"]) ? trim((string) $_GET["nivel"]) : "";
$ocultarConsumidosUrl = !isset($_GET["ocultar_consumidos"]) || (string) $_GET["ocultar_consumidos"] !== "0";
?>
<div class="content-wrapper pts-full-page"
     data-tab-inicial="<?php echo htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8'); ?>"
     data-modelo-inicial="<?php echo htmlspecialchars($modeloUrl, ENT_QUOTES, 'UTF-8'); ?>"
     data-taller-inicial="<?php echo htmlspecialchars($tallerUrl, ENT_QUOTES, 'UTF-8'); ?>"
     data-nivel-inicial="<?php echo htmlspecialchars($nivelUrl, ENT_QUOTES, 'UTF-8'); ?>"
     data-ocultar-consumidos="<?php echo $ocultarConsumidosUrl ? '1' : '0'; ?>">

    <section class="content-header" style="padding:8px 10px 0;">
        <h1 style="margin:0 0 4px;font-size:22px;">
            Programación semanal por taller
            <small>Por modelo/color · corte + OC</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Producción</li>
            <li class="active">Prog. semana</li>
        </ol>
    </section>

    <section class="content pts-content">

        <div class="box pts-box-filtros" style="margin-bottom:8px;">
            <div class="box-body">
                <div class="pts-filtros">
                    <div class="pts-filtro pts-filtro-anio">
                        <label>Año</label>
                        <input type="number" class="form-control input-sm" id="filtroAnioPts"
                            value="<?php echo (int) $infoActual['anio']; ?>" min="2000" max="2100">
                    </div>
                    <div class="pts-filtro pts-filtro-semana">
                        <label>Semana</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="btnSemanaAntPts" title="Semana anterior">&laquo;</button>
                            </span>
                            <input type="number" class="form-control" id="filtroSemanaPts"
                                value="<?php echo (int) $infoActual['semana']; ?>" min="1" max="53">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="btnSemanaSigPts" title="Semana siguiente">&raquo;</button>
                            </span>
                        </div>
                    </div>
                    <div class="pts-filtro pts-filtro-rango">
                        <label>Rango</label>
                        <p class="pts-rango-txt" id="textoRangoSemanaPts">
                            <?php echo htmlspecialchars($infoActual['fecha_inicio'] . ' → ' . $infoActual['fecha_fin'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                    <div class="pts-filtro pts-filtro-grow">
                        <label>Modelo</label>
                        <select class="form-control selectpicker" id="filtroModeloPts" data-live-search="true" data-size="8" data-width="100%" title="Todos">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="pts-filtro pts-filtro-grow">
                        <label>Taller</label>
                        <select class="form-control selectpicker" id="filtroTallerPts" data-live-search="true" data-size="8" data-width="100%" title="Todos">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="pts-filtro pts-filtro-nivel">
                        <label>Nivel</label>
                        <select class="form-control input-sm" id="filtroNivelPts" title="Solo en programado">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="pts-filtro pts-filtro-acciones">
                        <label>&nbsp;</label>
                        <div class="pts-acciones-btns">
                            <button type="button" class="btn btn-default btn-sm" id="btnActualizarPts" title="Actualizar">
                                <i class="fa fa-refresh"></i>
                            </button>
                            <a href="#" class="btn btn-success btn-sm" id="btnExcelPts" title="Exportar Excel de la semana">
                                <i class="fa fa-file-excel-o"></i> Excel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="pts-meta-bar">
                    <div id="leyendaNivelesPts" class="pts-leyenda">
                        <strong>Niveles</strong>
                        <span class="text-muted">Cargando…</span>
                    </div>
                </div>
                <div id="estadisticasSemanaPts" class="pts-stats-box">
                    <div class="pts-stats-title">Estadísticas de la semana</div>
                    <div class="text-muted">Cargando…</div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-body" style="padding-top:10px;">
                <ul class="nav nav-tabs" id="tabsPts">
                    <li class="<?php echo $tabUrl === 'programar' ? 'active' : ''; ?>">
                        <a href="#tabDisponiblesPts" data-toggle="tab" data-tab-pts="programar"><i class="fa fa-bolt"></i> Programar</a>
                    </li>
                    <li class="<?php echo $tabUrl === 'programado' ? 'active' : ''; ?>">
                        <a href="#tabProgramadoPts" data-toggle="tab" data-tab-pts="programado"><i class="fa fa-list"></i> Ya programado</a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top:12px;">
                    <div class="tab-pane <?php echo $tabUrl === 'programar' ? 'active' : ''; ?>" id="tabDisponiblesPts">
                        <div id="barraRapidaPts" class="well well-sm" style="margin-bottom:10px;padding:10px 12px;">
                            <div class="row" style="display:flex;flex-wrap:wrap;align-items:flex-end;">
                                <div class="col-sm-3">
                                    <label style="margin-bottom:2px;">Nivel para el lote</label>
                                    <select class="form-control input-sm" id="nivelLotePts">
                                        <option value="">— Elegir nivel —</option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label style="margin-bottom:2px;">&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-sm btn-block" id="btnProgramarLotePts" disabled>
                                        Programar seleccionados (<span id="nSelPts">0</span>)
                                    </button>
                                </div>
                                <div class="col-sm-6">
                                    <p class="help-block" style="margin:18px 0 0;font-size:12px;">
                                        <strong>Rápido:</strong> la cantidad es siempre el Total. Haz clic en el nivel de la fila,
                                        o marca varias, elige nivel arriba y programa el lote.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive pts-tabla-scroll">
                            <table class="table table-bordered table-striped table-condensed table-hover" id="tablaDisponiblesPts" width="100%">
                                <thead>
                                    <tr>
                                        <th style="width:32px;">
                                            <input type="checkbox" id="chkTodosDispPts" title="Seleccionar todos">
                                        </th>
                                        <th>Taller</th>
                                        <th>Modelo</th>
                                        <th>Color</th>
                                        <th title="Almacén de corte">Alm. corte</th>
                                        <th title="Órdenes de corte">Ord. corte</th>
                                        <th title="Cantidad a programar = Alm. + Ord.">Total</th>
                                        <th>Cob.</th>
                                        <th style="min-width:220px;">Programar (elige nivel)</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane <?php echo $tabUrl === 'programado' ? 'active' : ''; ?>" id="tabProgramadoPts">
                        <div style="margin-bottom:10px;">
                            <label class="checkbox-inline" style="font-weight:normal;">
                                <input type="checkbox" id="chkOcultarConsumidosPts" <?php echo $ocultarConsumidosUrl ? 'checked' : ''; ?>>
                                Ocultar consumidos
                                <span class="text-muted">(sin saldo en corte ni OC)</span>
                            </label>
                            <span id="conteoConsumidosPts" class="text-muted" style="margin-left:12px;"></span>
                        </div>
                        <div class="table-responsive pts-tabla-scroll">
                            <table class="table table-bordered table-striped table-condensed" id="tablaProgramadoPts" width="100%">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Nivel</th>
                                        <th>Taller</th>
                                        <th>Modelo</th>
                                        <th>Color</th>
                                        <th>Cantidad</th>
                                        <th>Alm. corte</th>
                                        <th>Ord. corte</th>
                                        <th>Cobertura</th>
                                        <th style="width:90px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div id="toastPts" class="pts-toast" style="display:none;" role="status" aria-live="polite"></div>

<div id="modalProgramarPts" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formProgramarPts">
                <div class="modal-header" style="background:#3c8dbc;color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="tituloModalPts">Editar programación</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ptsId" value="">
                    <input type="hidden" id="ptsModelo" value="">
                    <input type="hidden" id="ptsCodColor" value="">
                    <p><strong id="ptsArticuloTexto">—</strong></p>
                    <p class="text-muted" id="ptsSaldosTexto" style="margin-bottom:12px;">—</p>
                    <div class="form-group">
                        <label>Taller</label>
                        <select class="form-control selectpicker" id="ptsTaller" data-live-search="true" data-container="body" data-size="8" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nivel de urgencia</label>
                        <select class="form-control" id="ptsNivel" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" class="form-control" id="ptsCantidad" min="1" step="1" required>
                    </div>
                    <div class="form-group">
                        <label>Observación</label>
                        <input type="text" class="form-control" id="ptsObservacion" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
body .bootstrap-select .dropdown-menu { z-index: 2060 !important; }
#modalProgramarPts .bootstrap-select { width: 100% !important; }
.content-wrapper.pts-full-page {
    min-height: calc(100vh - 50px) !important;
}
.content-wrapper.pts-full-page > .pts-content {
    padding: 8px 10px 12px !important;
}
.content-wrapper.pts-full-page .box {
    margin-bottom: 8px;
}
.content-wrapper.pts-full-page .box-body {
    padding-left: 12px;
    padding-right: 12px;
}
.pts-tabla-scroll {
    width: 100%;
    overflow: auto;
    max-height: calc(100vh - 290px);
    min-height: 320px;
}
.pts-tabla-scroll thead th {
    position: sticky;
    top: 0;
    background: #3c8dbc;
    color: #fff;
    border-color: #367fa9 !important;
    z-index: 2;
    font-weight: 600;
    white-space: nowrap;
}
#tablaDisponiblesPts thead th,
#tablaProgramadoPts thead th {
    background: #3c8dbc;
    color: #fff;
    border-color: #367fa9 !important;
}
.badge-nivel-pts {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    color: #333;
}
#tablaProgramadoPts tr.fila-consumida-pts {
    opacity: 0.55;
    color: #777;
}
.badge-estado-pts {
    display: inline-block;
    padding: 3px 7px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}
.badge-estado-pts.pendiente { background: #d9edf7; color: #31708f; }
.badge-estado-pts.consumido { background: #eee; color: #666; }
.btn-nivel-pts {
    display: inline-block;
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 3px;
    padding: 3px 7px;
    margin: 1px 2px;
    font-size: 11px;
    font-weight: 600;
    color: #333;
    cursor: pointer;
    line-height: 1.3;
}
.btn-nivel-pts:hover { filter: brightness(0.95); box-shadow: inset 0 0 0 1px rgba(0,0,0,.2); }
.btn-nivel-pts:disabled,
.btn-nivel-pts.disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
#tablaDisponiblesPts tr.programando-pts,
#tablaProgramadoPts tr.programando-pts {
    opacity: 0.5;
    pointer-events: none;
}
#tablaDisponiblesPts tr.pts-sep-modelo > td,
#tablaProgramadoPts tr.pts-sep-modelo > td {
    border-top: 3px solid #444 !important;
}
#tablaDisponiblesPts td.pts-td-taller,
#tablaProgramadoPts td.pts-td-taller {
    font-weight: 600;
    white-space: nowrap;
}
#tablaDisponiblesPts .pts-taller-label,
#tablaProgramadoPts .pts-taller-label {
    display: inline-block;
    padding: 1px 0;
}
#tablaDisponiblesPts tr.ok-pts { background: #dff0d8 !important; }
#tablaProgramadoPts tr.ok-pts { background: #f2dede !important; }
.pts-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;
    min-width: 220px;
    max-width: 420px;
    margin: 0;
    padding: 12px 16px;
    border-radius: 4px;
    box-shadow: 0 4px 16px rgba(0,0,0,.18);
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    border: 0;
}
.pts-toast.pts-toast-success { background: #00a65a; }
.pts-toast.pts-toast-danger { background: #dd4b39; }
.pts-toast.pts-toast-warning { background: #f39c12; color: #fff; }
.pts-toast.pts-toast-info { background: #3c8dbc; }

/* —— Filtros compactos —— */
.pts-box-filtros > .box-body { padding: 8px 12px; }
.pts-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 8px 10px;
}
.pts-filtro label {
    display: block;
    margin: 0 0 2px;
    font-size: 11px;
    font-weight: 600;
    color: #666;
}
.pts-filtro-anio { width: 78px; }
.pts-filtro-semana { width: 130px; }
.pts-filtro-rango { min-width: 150px; }
.pts-filtro-nivel { width: 110px; }
.pts-filtro-grow { flex: 1 1 160px; min-width: 140px; max-width: 240px; }
.pts-filtro-acciones { flex: 0 0 auto; }
.pts-rango-txt {
    margin: 0;
    padding: 5px 0 0;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    color: #333;
}
.pts-acciones-btns { display: flex; gap: 6px; }
.pts-meta-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 8px 0 6px;
    padding-top: 6px;
    border-top: 1px solid #eee;
}
.pts-leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
    font-size: 12px;
}
.pts-leyenda strong { margin-right: 2px; color: #555; }

/* —— Estadísticas —— */
.pts-stats-box {
    background: #f4f6f8;
    border: 1px solid #dde3ea;
    border-radius: 4px;
    padding: 8px 10px;
}
.pts-stats-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 6px;
    margin-bottom: 8px;
}
.pts-stats-title {
    font-weight: 700;
    font-size: 13px;
    margin: 0;
    color: #333;
}
.pts-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
@media (max-width: 991px) {
    .pts-stats-grid { grid-template-columns: 1fr; }
}
.pts-stats-panel {
    background: #fff;
    border: 1px solid #e1e6eb;
    border-radius: 4px;
    padding: 8px;
    min-width: 0;
}
.pts-stats-panel.pts-panel-warn {
    border-color: #f0c36d;
    background: #fffdf8;
}
.pts-stats-subtitle {
    font-weight: 700;
    font-size: 11px;
    color: #666;
    margin: 0 0 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.pts-panel-warn .pts-stats-subtitle { color: #9a6700; }
.pts-stats-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    margin-bottom: 6px;
}
.pts-stats-kpis.pts-kpis-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
@media (max-width: 1200px) {
    .pts-stats-kpis.pts-kpis-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.pts-kpi {
    background: #f8fafc;
    border: 1px solid #e8edf2;
    border-radius: 3px;
    padding: 5px 7px;
    min-width: 0;
}
.pts-panel-warn .pts-kpi {
    background: #fff;
    border-color: #f5dfa0;
}
.pts-kpi .n {
    display: block;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.15;
    color: #222;
    font-variant-numeric: tabular-nums;
}
.pts-panel-warn .pts-kpi .n { color: #b86e00; }
.pts-kpi .l {
    display: block;
    font-size: 10px;
    color: #888;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pts-chip-head {
    font-size: 10px;
    font-weight: 600;
    color: #888;
    margin: 2px 0 4px;
    text-transform: uppercase;
}
.pts-chip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
    gap: 4px;
}
.pts-chip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    padding: 4px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    color: #333;
    background: #eee;
    min-width: 0;
    line-height: 1.25;
}
.pts-chip .pts-chip-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.pts-chip .pts-chip-meta {
    flex: 0 0 auto;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.pts-chip .pts-chip-meta small {
    font-weight: 500;
    color: rgba(0,0,0,.55);
    margin-left: 2px;
}
.pts-stats-empty {
    font-size: 11px;
    color: #999;
    margin: 0;
}
</style>
<script>
window.document.title = "Prog. semanal taller";
</script>
