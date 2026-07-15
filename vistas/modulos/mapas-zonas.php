<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
    denegarAccesoModulo();
    return;
}
date_default_timezone_set("America/Lima");
$mzAnio = (int) date("Y");
$mzMes = (int) date("n");
$mesesNombres = array(
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
);
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
                        <form class="form-inline" style="display:inline-block;margin-left:12px;" id="mzFormPeriodo">
                            <select class="form-control input-sm" id="mzAnio">
                                <?php for ($a = $mzAnio - 2; $a <= $mzAnio; $a++) : ?>
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
                            Solo color de zona (sin títulos en el mapa). Pasá el mouse o usá la leyenda para el nombre y la venta.
                            El <strong>comparativo inferior</strong> usa tamaño proporcional a la venta del mes.
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

                        <h4 style="font-size:14px;margin:16px 0 8px;">Comparativo por ventas <small class="text-muted">(tamaño = venta del período)</small></h4>
                        <div id="mzTreemap" class="mz-treemap" aria-label="Treemap ventas por zona"></div>
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
                            <p style="margin:0 0 10px;">
                                <span class="text-muted">Venta del período</span><br>
                                <strong id="mzFichaVenta" style="font-size:28px;line-height:1.1;">S/ 0</strong>
                            </p>
                            <table class="table table-condensed">
                                <tr>
                                    <th style="width:45%;">Clientes</th>
                                    <td id="mzFichaClientes">—</td>
                                </tr>
                                <tr>
                                    <th>Cobertura (asignados)</th>
                                    <td id="mzFichaVendCount">—</td>
                                </tr>
                                <tr>
                                    <th>Ubigeos mapeados</th>
                                    <td id="mzFichaUbigeos">—</td>
                                </tr>
                                <tr>
                                    <th>Código</th>
                                    <td id="mzFichaCodigo">—</td>
                                </tr>
                            </table>
                            <h4 style="font-size:14px;margin-top:12px;">Venta por vendedor activo</h4>
                            <ul class="list-unstyled" id="mzFichaVentaVendedores" style="max-height:180px;overflow:auto;"></ul>
                            <h4 style="font-size:14px;margin-top:12px;">Cobertura asignada</h4>
                            <ul class="list-unstyled" id="mzFichaVendedores" style="max-height:120px;overflow:auto;"></ul>
                            <button type="button" class="btn btn-primary btn-sm btn-block btnMzVerClientes" id="mzBtnFichaClientes" style="margin-top:10px;">
                                <i class="fa fa-list"></i> Ver clientes con venta
                            </button>
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
                    Clientes con venta — <span id="mzModalZonaNombre">zona</span>
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="margin-top:0;">
                    Período: <strong id="mzModalPeriodo">—</strong>.
                    Total: <strong id="mzModalTotalVenta">S/ 0</strong>
                    · <span id="mzModalTotalCli">0</span> clientes (máx. 500, orden por venta).
                </p>
                <div class="table-responsive" style="max-height:420px;overflow:auto;">
                    <table class="table table-bordered table-striped table-condensed" id="mzTablaClientes">
                        <thead>
                            <tr>
                                <th style="width:90px;">Código</th>
                                <th>Cliente</th>
                                <th class="text-right" style="width:120px;">Venta S/</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" class="text-muted">Cargando…</td></tr>
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
.mz-tooltip-zona {
    font-size: 12px;
    line-height: 1.25;
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
