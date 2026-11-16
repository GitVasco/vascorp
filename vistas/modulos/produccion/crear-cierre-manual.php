<?php
require_once "controladores/cierre-manual.config.php";
?>
<style>
    .tablaCierreManualAbiertos th, .tablaCierreManualAbiertos td { white-space: nowrap; vertical-align: middle !important; }
    .cmLista { height: 460px; overflow-y: auto; overflow-x: hidden; margin-bottom: 10px; }
    .cmVacio { padding: 28px 16px; text-align: center; color: #888; }
    .cmFila {
        border: 1px solid #f3e2c2;
        border-left: 3px solid #f39c12;
        border-radius: 3px;
        background: #fff;
        margin: 0 0 8px;
        padding: 8px 10px 10px;
    }
    .cmFila.cmIncompleto { border-color: #dd4b39; background: #fff6f5; }
    .cmFilaHead { display: flex; gap: 8px; align-items: flex-start; }
    .cmFilaTxt { flex: 1; min-width: 0; }
    .cmTitulo { font-weight: 600; line-height: 1.25; }
    .cmMeta { color: #777; font-size: 12px; margin-top: 2px; display: flex; flex-wrap: wrap; align-items: center; gap: 4px 6px; }
    .cmMetaSep { color: #bbb; }
    .cmFilaCampos { margin-top: 8px; }
    .cmFila .row { margin-left: -4px; margin-right: -4px; }
    .cmFila .row > [class*="col-"] { padding-left: 4px; padding-right: 4px; }
    .cmFilaCampos label { display: block; font-size: 11px; font-weight: 600; color: #555; margin-bottom: 2px; }
    .cmFilaCampos label .cmReq { color: #dd4b39; }
    .cmFila .bootstrap-select { width: 100% !important; }
    .cmFila .bootstrap-select > .dropdown-toggle { padding-right: 28px; }
</style>
<script>window.CM_FALLAS = <?= json_encode(cmFallas(), JSON_UNESCAPED_UNICODE); ?>;</script>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Crear cierre manual</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="cierres?tab=manuales">Cierres</a></li>
            <li class="active">Crear cierre manual</li>
        </ol>
    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
            EL FORMULARIO
            ======================================-->
            <div class="col-lg-6 col-xs-12">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <form role="form" class="formularioCierreManual" onsubmit="return false;">

                        <div class="box-body">

                            <div class="box">

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION["nombre"]); ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="text" class="form-control" value="Código automático al guardar (CM…)" readonly>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="cmFecha" value="<?php date_default_timezone_set('America/Lima'); echo date("Y-m-d"); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                                        <select class="form-control input-sm selectpicker" id="cmTaller" data-live-search="true" title="Seleccionar taller">
                                            <?php
                                            $cmTallerSel = isset($_GET["taller"]) ? (string) $_GET["taller"] : "";
                                            try {
                                                $cmTalleres = ModeloServicios::mdlTalleresCierreManual();
                                            } catch (Exception $e) {
                                                $cmTalleres = array();
                                            }
                                            foreach ($cmTalleres as $t) { ?>
                                                <option value="<?= htmlspecialchars($t["taller"]); ?>" <?= (string) $t["taller"] === $cmTallerSel ? "selected" : ""; ?>><?= htmlspecialchars($t["taller"] . " - " . $t["nom_sector"] . "  (" . $t["saldo"] . " prendas)"); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-comment"></i></span>
                                        <input type="text" class="form-control" id="cmObs" maxlength="255" placeholder="Observación (opcional)">
                                    </div>
                                </div>

                                <div class="form-group buscador" style="padding-bottom:25px">
                                    <label class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cmBuscador">
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-muted" style="margin:0 0 8px">Cada prenda lleva su motivo.</p>
                                <div class="form-group cmLista">
                                    <div class="cmVacio">Agregue prendas desde la lista de la derecha.<br>En cada una indique el motivo.</div>
                                </div>

                                <div class="row">
                                    <div class="col-xs-6 pull-right">
                                        <table class="table">
                                            <thead>
                                                <tr><th>Total a cerrar</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="width: 50%">
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                                                            <input type="text" class="form-control input-lg" id="cmTotal" value="0" readonly>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="box-footer">
                            <button type="button" class="btn btn-warning pull-right" id="cmGuardar"><i class="fa fa-lock"></i> Guardar cierre manual</button>
                            <a href="cierres?tab=manuales" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                </div>

            </div>

            <!--=====================================
            LINEAS ABIERTAS DEL TALLER
            ======================================-->
            <div class="col-lg-6 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body table-responsive">

                        <table class="table table-bordered table-striped table-condensed tablaCierreManualAbiertos" width="100%">
                            <thead>
                                <tr>
                                    <th class="text-center">Servicio</th>
                                    <th class="text-center">Guía</th>
                                    <th class="text-center">Modelo</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Color</th>
                                    <th class="text-center">Talla</th>
                                    <th class="text-center">Saldo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                        </table>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>
