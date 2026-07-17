<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
    denegarAccesoModulo();
    return;
}
date_default_timezone_set("America/Lima");
$mzAnioActual = (int) date("Y");
$mzAnio = isset($_GET["anio"]) ? (int) $_GET["anio"] : $mzAnioActual;
$mzMes = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n");
if ($mzAnio < 2000 || $mzAnio > 2100) {
    $mzAnio = $mzAnioActual;
}
if ($mzMes < 1 || $mzMes > 12) {
    $mzMes = (int) date("n");
}
$mzAnioMin = $mzAnioActual - 2;
$mzAnioMax = $mzAnioActual;
if ($mzAnio < $mzAnioMin) {
    $mzAnioMin = $mzAnio;
}
if ($mzAnio > $mzAnioMax) {
    $mzAnioMax = $mzAnio;
}
$mesesNombres = array(
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
);
$mzGruposMarca = ControladorGruposMarcasComercial::ctrListarGrupos(true);
if (!is_array($mzGruposMarca)) {
    $mzGruposMarca = array();
}
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Mapas de zonas
            <small>Rendimiento por zona (venta neta)</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="index.php?ruta=zonas-comerciales">Zonas comerciales</a></li>
            <li class="active">Mapas</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="btn-group" id="mzToggleVista" data-toggle="buttons">
                            <label class="btn btn-primary active">
                                <input type="radio" name="mzVista" value="lima" autocomplete="off" checked> Lima y alrededores
                            </label>
                            <label class="btn btn-default">
                                <input type="radio" name="mzVista" value="peru" autocomplete="off"> Perú sin Lima
                            </label>
                        </div>
                        <div class="btn-group" id="mzToggleGrupo" data-toggle="buttons" style="margin-left:12px;" title="Filtrar por vendedores del grupo de marcas">
                            <label class="btn btn-primary active">
                                <input type="radio" name="mzGrupoMarca" value="0" autocomplete="off" checked> Ambos
                            </label>
                            <?php foreach ($mzGruposMarca as $g) :
                                $gId = (int) $g["id"];
                                $gLabel = trim((string) $g["nombre"]);
                                if ($gLabel === "") {
                                    $gLabel = trim((string) $g["codigo"]);
                                }
                                ?>
                            <label class="btn btn-default">
                                <input type="radio" name="mzGrupoMarca" value="<?php echo $gId; ?>" autocomplete="off">
                                <?php echo htmlspecialchars($gLabel, ENT_QUOTES, "UTF-8"); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <form class="form-inline" style="display:inline-block;margin-left:12px;" id="mzFormPeriodo">
                            <select class="form-control input-sm" id="mzFiltroDistribuidor" title="Filtrar la cartera por categoría comercial">
                                <option value="con">Con distribuidores</option>
                                <option value="solo">Solo distribuidores</option>
                                <option value="sin">Sin distribuidores</option>
                            </select>
                            <select class="form-control input-sm" id="mzAnio">
                                <?php for ($a = $mzAnioMin; $a <= $mzAnioMax; $a++) : ?>
                                <option value="<?php echo $a; ?>" <?php echo $a === $mzAnio ? "selected" : ""; ?>><?php echo $a; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select class="form-control input-sm" id="mzMes">
                                <?php foreach ($mesesNombres as $num => $nom) : ?>
                                <option value="<?php echo $num; ?>" <?php echo $num === $mzMes ? "selected" : ""; ?>>
                                    <?php echo $num . " - " . $nom; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></button>
                        </form>
                        <span class="pull-right text-muted" id="mzEstadoCarga" style="margin-top:8px;">Cargando…</span>
                    </div>
                    <div class="box-body">
                        <p class="help-block" style="margin-top:0;">
                            Lima se pinta por <strong>distrito</strong> (y Norte Chico por provincias Barranca/Huaral/Huaura)
                            y Perú sin Lima por <strong>departamento</strong> (sin Lima/Callao), con el color de tu zona comercial.
                            Los colores siguen las reglas ubigeo→zona del módulo (si movés Huánuco al norte, el mapa lo refleja).
                            Al hacer clic en una zona, el mapa se acerca y el resto se atenúa en gris.
                            Pasá el mouse para ver el nombre, zona y venta.
                            El filtro de grupo muestra ventas de vendedores asignados a ese grupo (Ambos = sin filtro).
                            Los filtros de grupo y distribuidores usan la segmentación comercial vigente actualmente,
                            incluso al consultar ventas de meses anteriores.
                        </p>

                        <div id="mzVistaLima" class="mz-panel">
                            <div id="mzMapLima" class="mz-leaflet"></div>
                        </div>

                        <div id="mzVistaPeru" class="mz-panel" style="display:none;">
                            <div id="mzMapPeru" class="mz-leaflet"></div>
                            <p class="text-muted text-center" style="margin-top:8px;margin-bottom:0;">
                                Lima / Callao / Norte Chico se ven en el mapa Lima.
                            </p>
                        </div>

                        <h4 style="font-size:14px;margin:16px 0 8px;">Comparativo por ventas <small class="text-muted">(tamaño = venta del período · montos en soles)</small></h4>
                        <div id="mzTreemap" class="mz-treemap" aria-label="Treemap ventas por zona"></div>

                        <h4 style="font-size:14px;margin:16px 0 8px;">
                            Evolución de ventas
                            <small class="text-muted" id="mzHistTitulo">(últimos 12 meses · escala propia por zona)</small>
                        </h4>
                        <div id="mzHistGrid" class="mz-hist-grid" aria-label="Evolución por zona"></div>
                        <p class="text-muted text-center" id="mzHistEstado" style="margin:6px 0 0;display:none;"></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="box box-solid" id="mzFichaBox">
                    <div class="box-header with-border" id="mzFichaHeader" style="background:#3c8dbc;color:#fff;">
                        <h3 class="box-title" id="mzFichaTitulo">Seleccione una zona</h3>
                    </div>
                    <div class="box-body" id="mzFichaCuerpo">
                        <p class="text-muted" id="mzFichaVacio">
                            Haga clic en una zona del mapa o del comparativo.
                        </p>
                        <div id="mzFichaDetalle" style="display:none;">
                            <p id="mzFichaDesc" class="help-block" style="margin-top:0;"></p>
                            <div class="mz-ficha-venta-actual">
                                <span>Venta del período</span>
                                <strong id="mzFichaVenta">S/ 0</strong>
                            </div>

                            <div class="mz-ficha-ventas-12m">
                                <div class="mz-ficha-resumen-card mz-ficha-resumen-total">
                                    <span>Venta total</span>
                                    <div class="mz-ficha-resumen-valor" id="mzFichaVentaTotal12m">—</div>
                                    <small>Últimos 12 meses completos</small>
                                </div>
                                <div class="mz-ficha-resumen-card">
                                    <span>Promedio mensual</span>
                                    <div class="mz-ficha-resumen-valor" id="mzFichaPromedioVenta">—</div>
                                </div>
                            </div>

                            <h4 class="mz-ficha-seccion"><i class="fa fa-line-chart"></i> Desempeño comercial</h4>
                            <div class="mz-ficha-kpis">
                                <div class="mz-ficha-kpi">
                                    <span><i class="fa fa-users"></i> Clientes con venta</span>
                                    <strong id="mzFichaClientesVenta">—</strong>
                                    <button type="button" class="mz-ficha-kpi-action btnMzVerClientes" id="mzBtnFichaClientes">
                                        <i class="fa fa-list"></i> Ver clientes
                                    </button>
                                </div>
                                <div class="mz-ficha-kpi">
                                    <span><i class="fa fa-cubes"></i> Modelos con venta</span>
                                    <strong id="mzFichaModelosVenta">—</strong>
                                </div>
                                <div class="mz-ficha-kpi mz-ficha-kpi-success">
                                    <span><i class="fa fa-user-plus"></i> Clientes nuevos</span>
                                    <strong id="mzFichaClientesNuevos">—</strong>
                                    <button type="button" class="mz-ficha-kpi-action btnMzVerNuevos" id="mzBtnFichaNuevos">
                                        <i class="fa fa-user-plus"></i> Ver nuevos
                                    </button>
                                </div>
                                <div class="mz-ficha-kpi mz-ficha-kpi-warning">
                                    <span><i class="fa fa-user-times"></i> Sin atender (2 años)</span>
                                    <strong id="mzFichaClientesSinAtender">—</strong>
                                    <button type="button" class="mz-ficha-kpi-action btnMzVerSinAtender" id="mzBtnFichaSinAtender">
                                        <i class="fa fa-user-times"></i> Ver clientes
                                    </button>
                                </div>
                            </div>

                            <div class="mz-ficha-meta">
                                <span title="Vendedores asignados"><i class="fa fa-user"></i> <strong id="mzFichaVendCount">—</strong> vendedores</span>
                                <span title="Ubigeos mapeados"><i class="fa fa-map-marker"></i> <strong id="mzFichaUbigeos">—</strong> ubigeos</span>
                                <span title="Código de zona"><i class="fa fa-tag"></i> <strong id="mzFichaCodigo">—</strong></span>
                            </div>

                            <h4 class="mz-ficha-seccion"><i class="fa fa-bar-chart"></i> Venta por vendedor activo</h4>
                            <ul class="list-unstyled" id="mzFichaVentaVendedores" style="max-height:180px;overflow:auto;"></ul>
                            <h4 class="mz-ficha-seccion"><i class="fa fa-street-view"></i> Cobertura asignada</h4>
                            <ul class="list-unstyled" id="mzFichaVendedores" style="max-height:120px;overflow:auto;"></ul>
                            <a href="index.php?ruta=zonas-comerciales" class="btn btn-default btn-sm btn-block" style="margin-top:6px;">
                                <i class="fa fa-map"></i> Ir a zonas comerciales
                            </a>
                        </div>
                    </div>
                </div>

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Leyenda</h3>
                    </div>
                    <div class="box-body" id="mzLeyenda">
                        <p class="text-muted">Se carga con el mapa…</p>
                    </div>
                    <div class="box-footer" style="padding:8px 10px;">
                        <small class="text-muted">Clic en zona → detalle. Botón <i class="fa fa-list"></i> → clientes con venta.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalMzClientesZona" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" id="mzModalHeader" style="background:#3c8dbc;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.9;">&times;</button>
                <h4 class="modal-title">
                    <span id="mzModalTituloTipo">Clientes con venta</span> — <span id="mzModalZonaNombre">zona</span>
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="margin-top:0;" id="mzModalResumen">
                    Período: <strong id="mzModalPeriodo">—</strong>.
                    Total: <strong id="mzModalTotalVenta">S/ 0</strong>
                    · <span id="mzModalTotalCli">0</span> clientes.
                </p>
                <div class="table-responsive" style="max-height:420px;overflow:auto;">
                    <table class="table table-bordered table-striped table-condensed" id="mzTablaClientes">
                        <thead>
                            <tr>
                                <th style="width:90px;">Código</th>
                                <th>Cliente</th>
                                <th style="width:180px;" id="mzModalColVendedor">Vendedor</th>
                                <th class="text-right" style="width:110px;" id="mzModalColValor">Venta S/</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-muted">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.mz-leaflet {
    height: 480px;
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    z-index: 1;
}
#mzTablaClientes .mz-vendedor-asignado,
#mzTablaClientes .mz-vendedor-asignado code {
    font-size: 11px;
    white-space: nowrap;
}
.mz-ficha-venta-actual {
    padding: 10px 12px;
    margin: 4px 0 10px;
    border-left: 4px solid #3c8dbc;
    border-radius: 5px;
    background: linear-gradient(135deg, #f7fafc, #fff);
}
.mz-ficha-venta-actual span,
.mz-ficha-resumen-card > span {
    display: block;
    color: #6c757d;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .35px;
}
.mz-ficha-venta-actual strong {
    display: block;
    margin-top: 2px;
    color: #263238;
    font-size: 27px;
    line-height: 1.15;
}
.mz-ficha-ventas-12m {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 14px;
}
.mz-ficha-resumen-card {
    min-width: 0;
    padding: 10px;
    border: 1px solid #e4e8eb;
    border-radius: 7px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.mz-ficha-resumen-total {
    background: #f5faff;
    border-color: #cfe5f5;
}
.mz-ficha-resumen-valor {
    margin-top: 4px;
    color: #263238;
    font-size: 17px;
    font-weight: 800;
    line-height: 1.2;
}
.mz-ficha-resumen-card small {
    display: block;
    margin-top: 3px;
    color: #8a9499;
    font-size: 9px;
    line-height: 1.25;
}
.mz-ficha-seccion {
    margin: 14px 0 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #edf0f2;
    color: #455a64;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .25px;
}
.mz-ficha-seccion i {
    width: 17px;
    color: #8a9ba5;
}
.mz-ficha-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px;
}
.mz-ficha-kpi {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 105px;
    padding: 8px 9px;
    border: 1px solid #e4e8eb;
    border-radius: 6px;
    background: #fafbfc;
}
.mz-ficha-kpi > span {
    display: block;
    min-height: 28px;
    color: #66757d;
    font-size: 10px;
    font-weight: 600;
    line-height: 1.25;
}
.mz-ficha-kpi > span i {
    width: 14px;
    color: #8a9ba5;
}
.mz-ficha-kpi > strong {
    display: block;
    margin-top: 3px;
    color: #263238;
    font-size: 18px;
    line-height: 1.2;
}
.mz-ficha-kpi-action {
    width: 100%;
    margin-top: auto;
    padding: 6px 0 0;
    border: 0;
    border-top: 1px solid #e2e8ec;
    outline: 0;
    background: transparent;
    color: #3c8dbc;
    font-size: 10px;
    font-weight: 700;
    text-align: left;
}
.mz-ficha-kpi-action:hover,
.mz-ficha-kpi-action:focus {
    color: #246a91;
    text-decoration: underline;
}
.mz-ficha-kpi-success .mz-ficha-kpi-action {
    border-top-color: #d5ecdf;
    color: #008d4c;
}
.mz-ficha-kpi-warning .mz-ficha-kpi-action {
    border-top-color: #f4dfba;
    color: #d58512;
}
.mz-ficha-kpi-success {
    border-color: #ccebd9;
    background: #f4fbf7;
}
.mz-ficha-kpi-warning {
    border-color: #f7dfb3;
    background: #fff9ef;
}
.mz-ficha-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.mz-ficha-meta > span {
    padding: 5px 8px;
    border-radius: 12px;
    background: #f0f2f4;
    color: #66757d;
    font-size: 10px;
    white-space: nowrap;
}
.mz-ficha-meta i {
    margin-right: 2px;
    color: #87969e;
}
#mzFichaVentaVendedores li,
#mzFichaVendedores li {
    padding: 5px 2px;
    border-bottom: 1px dashed #edf0f2;
    font-size: 11px;
}
#mzFichaVentaVendedores li:last-child,
#mzFichaVendedores li:last-child {
    border-bottom: 0;
}
#mzFichaDetalle .btn-block {
    border-radius: 5px;
    font-weight: 600;
}
.mz-hist-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
@media (min-width: 1200px) {
    .mz-hist-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
.mz-hist-card {
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    background: #fff;
    padding: 10px 10px 6px;
    cursor: pointer;
    transition: box-shadow .12s ease, border-color .12s ease;
    min-width: 0;
}
.mz-hist-card:hover {
    border-color: #bbb;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.mz-hist-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}
.mz-hist-card-name {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
    color: #333;
    min-width: 0;
}
.mz-hist-card-swatch {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    border: 1px solid rgba(0,0,0,.12);
    flex: 0 0 auto;
    margin-top: 2px;
}
.mz-hist-card-monto {
    font-size: 16px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.2px;
}
.mz-hist-card-delta {
    font-size: 11px;
    font-weight: 600;
    margin-left: 4px;
}
.mz-hist-card-delta.up { color: #00a65a; }
.mz-hist-card-delta.down { color: #dd4b39; }
.mz-hist-card-delta.flat { color: #999; }
.mz-hist-card-meta {
    font-size: 10px;
    color: #888;
    margin-top: 2px;
}
.mz-hist-card-canvas-wrap {
    position: relative;
    height: 72px;
    margin-top: 6px;
}
.mz-hist-card-canvas-wrap canvas {
    width: 100% !important;
    height: 72px !important;
}
.mz-treemap {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 130px;
    align-content: stretch;
}
.mz-treemap-cell {
    flex: 1 1 80px;
    min-height: 110px;
    border-radius: 6px;
    color: #fff;
    padding: 10px 12px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border: 2px solid transparent;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    text-shadow: 0 1px 2px rgba(0,0,0,.35);
    transition: transform .12s ease, box-shadow .12s ease;
}
.mz-treemap-cell:hover,
.mz-treemap-cell.mz-active {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
    border-color: #222;
}
.mz-treemap-title {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
}
.mz-treemap-venta {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.3px;
    line-height: 1.1;
    margin-top: 6px;
    white-space: nowrap;
}
.mz-treemap-meta {
    font-size: 11px;
    opacity: .95;
    margin-top: 4px;
}
.mz-map-label {
    background: transparent !important;
    border: none !important;
}
.mz-map-label .mz-label-box {
    background: rgba(255,255,255,0.9);
    border: 2px solid #999;
    border-radius: 4px;
    padding: 2px 6px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,.25);
    pointer-events: none;
}
.mz-map-label .mz-label-compact {
    min-width: 0;
}
.mz-map-label .mz-label-name {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #222;
    line-height: 1.1;
    text-transform: uppercase;
    letter-spacing: 0.2px;
}
.mz-dist-label {
    background: transparent !important;
    border: none !important;
}
.mz-dist-label span {
    display: inline-block;
    color: #111;
    font-size: 9px;
    font-weight: 700;
    line-height: 1.1;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.15px;
    white-space: nowrap;
    pointer-events: none;
    text-shadow:
        0 0 2px #fff,
        0 0 3px #fff,
        1px 0 0 #fff,
        -1px 0 0 #fff,
        0 1px 0 #fff,
        0 -1px 0 #fff;
    transform: translate(-50%, -50%);
}
.mz-tooltip-zona {
    font-size: 12px;
    line-height: 1.25;
    pointer-events: none;
}
.leaflet-interactive {
    cursor: pointer;
}
.mz-leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.mz-leyenda-main {
    flex: 1;
    min-width: 0;
    cursor: pointer;
}
.mz-leyenda-swatch {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,.15);
    flex: 0 0 auto;
}
.mz-leyenda-venta {
    font-weight: 700;
    white-space: nowrap;
}
.mz-leyenda-btn {
    flex: 0 0 auto;
}
</style>
