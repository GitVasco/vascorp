<?php
require_once "controladores/cierre-manual.config.php";
?>
<style>
    .tablaCierreManualAbiertos th, .tablaCierreManualAbiertos td { white-space: nowrap; vertical-align: middle !important; }
</style>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Crear cierre manual</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="cierres">Cierres</a></li>
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
                                        <input type="date" class="form-control" value="<?php date_default_timezone_set('America/Lima'); echo date("Y-m-d"); ?>" readonly>
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
                                        <span class="input-group-addon"><i class="fa fa-exclamation-triangle"></i></span>
                                        <select class="form-control input-sm selectpicker" id="cmFalla" data-live-search="true" title="Seleccionar tipo de falla">
                                            <?php foreach (cmFallas() as $f) { ?>
                                                <option value="<?= htmlspecialchars($f["codigo"]); ?>"><?= htmlspecialchars($f["codigo"] . " - " . $f["nombre"]); ?></option>
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

                                <div class="box box-primary">
                                    <div class="row">
                                        <div class="col-xs-6"><label>Artículo</label></div>
                                        <div class="col-xs-2"><label>Servicio</label></div>
                                        <div class="col-xs-2"><label>Saldo</label></div>
                                        <div class="col-xs-2"><label>Cerrar</label></div>
                                    </div>
                                </div>

                                <div class="form-group row cmLista" style="height:400px;overflow: scroll; overflow-x:hidden"></div>

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
                            <a href="cierres" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
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
