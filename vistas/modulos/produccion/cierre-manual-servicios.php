<style>
    .tablaCierreManual td.cmColCant { width: 140px; min-width: 140px; vertical-align: middle; padding: 3px 6px; }
    .tablaCierreManual td.cmColCant .input-group { width: 128px; margin: 0 auto; table-layout: fixed; }
    .tablaCierreManual td.cmColCant .cmCant { width: 100%; height: 30px; text-align: center; padding: 4px 6px; }
    .tablaCierreManual td.cmColCant .input-group-btn { width: 34px; }
    .tablaCierreManual td.cmColCant .cmTodo { height: 30px; width: 34px; padding: 0; }
    .tablaCierreManual td, .tablaCierreManual th { vertical-align: middle !important; }
</style>

<div class="content-wrapper">

    <section class="content-header">
        <h1>Cierre manual de servicios</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="servicios">Servicios</a></li>
            <li class="active">Cierre manual</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-solid">
            <div class="box-body form-inline">
                <label>Taller</label>
                <select id="cmFiltroTaller" class="form-control selectpicker" data-live-search="true" data-width="220px" title="Todos">
                    <option value="">Todos</option>
                </select>
                <label style="margin-left:10px">Modelo</label>
                <select id="cmFiltroModelo" class="form-control selectpicker" data-live-search="true" data-width="220px" title="Todos">
                    <option value="">Todos</option>
                </select>
                <button type="button" class="btn btn-sm btn-default" id="cmLimpiarFiltros"><i class="fa fa-eraser"></i> Limpiar</button>
            </div>
        </div>

        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#cmTabHistorial" data-toggle="tab"><i class="fa fa-history"></i> Cerrados a mano</a></li>
                <li><a href="#cmTabCerrar" data-toggle="tab"><i class="fa fa-lock"></i> Cerrar servicios</a></li>
            </ul>

            <div class="tab-content">

                <!-- ===== HISTORIAL ===== -->
                <div class="tab-pane active" id="cmTabHistorial">
                    <div class="form-inline" style="margin-bottom:10px">
                        <label>Desde</label>
                        <input type="date" id="cmDesde" class="form-control input-sm" value="<?= date('Y-m-01'); ?>">
                        <label>Hasta</label>
                        <input type="date" id="cmHasta" class="form-control input-sm" value="<?= date('Y-m-d'); ?>">
                        <button type="button" class="btn btn-sm btn-primary" id="cmBtnFiltrar"><i class="fa fa-search"></i> Filtrar</button>
                    </div>

                    <table class="table table-bordered table-striped tablaCierreManualHistorial" width="100%">
                        <thead>
                            <tr>
                                <th>Fecha cierre</th>
                                <th>Usuario</th>
                                <th>Motivo</th>
                                <th>Observación</th>
                                <th>Servicio</th>
                                <th>Guía</th>
                                <th>Taller</th>
                                <th>Artículo</th>
                                <th>Saldo antes</th>
                                <th>Cant. cerrada</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- ===== CERRAR ===== -->
                <div class="tab-pane" id="cmTabCerrar">

                    <div class="row">
                        <div class="form-group col-lg-3">
                            <label>Motivo del cierre</label>
                            <select id="cmMotivo" class="form-control selectpicker" data-width="100%">
                                <option value="">Seleccione...</option>
                                <option value="PERDIDA">Pérdida / merma</option>
                                <option value="DEFECTO">Prendas con defecto (no retornan)</option>
                                <option value="ERROR_REGISTRO">Error de registro</option>
                                <option value="ACUERDO_TALLER">Acuerdo con el taller</option>
                                <option value="OTRO">Otro (indicar)</option>
                            </select>
                        </div>
                        <div class="form-group col-lg-5">
                            <label>Observación</label>
                            <input type="text" id="cmObs" class="form-control input-sm" maxlength="255" placeholder="Detalle del cierre (obligatorio si el motivo es Otro)">
                        </div>
                        <div class="form-group col-lg-4">
                            <label>&nbsp;</label><br>
                            <span id="cmResumen" class="text-bold" style="margin-right:10px">0 líneas · 0 prendas</span>
                            <button type="button" class="btn btn-warning" id="btnConfirmarCierreManual"><i class="fa fa-lock"></i> Cerrar cantidades</button>
                        </div>
                    </div>

                    <p class="text-muted">Escriba en cada línea la cantidad que se cierra a mano (no puede superar el saldo). Las líneas con cantidad vacía o 0 no se tocan. El botón <i class="fa fa-arrow-down"></i> completa el saldo.</p>

                    <table class="table table-bordered table-striped tablaCierreManual" width="100%">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Guía</th>
                                <th>Taller</th>
                                <th>Fecha</th>
                                <th>Artículo</th>
                                <th>Enviado</th>
                                <th>Saldo</th>
                                <th style="width:140px; text-align:center">Cantidad a cerrar</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>

    </section>

</div>
