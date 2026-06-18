<div class="content-wrapper" id="panelSolicitudesAtencionVasco">

    <section class="content-header">
        <h1>
            Solicitudes de atención
            <small>Portal cliente — pedidos de visita o contacto</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Vasco Online</li>
            <li class="active">Solicitudes atención</li>
        </ol>
    </section>

    <section class="content">

        <p class="vasco-atencion-intro text-muted">
            Los clientes pueden solicitar atención desde el <strong>portal Vasco</strong>
            (<span class="label label-warning">pending</span>).
            Revise el mensaje, tome la solicitud para avisar al vendedor y ciérrela cuando se haya atendido al cliente.
        </p>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-search"></i> Buscar solicitudes</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroEstadoAtencionVasco">Estado</label>
                            <select class="form-control" id="filtroEstadoAtencionVasco">
                                <option value="pending" selected>Pendientes</option>
                                <option value="acknowledged">Tomadas</option>
                                <option value="completed">Completadas</option>
                                <option value="cancelled">Canceladas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroDesdeAtencionVasco">Desde</label>
                            <input type="date" class="form-control" id="filtroDesdeAtencionVasco">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroLimiteAtencionVasco">Límite</label>
                            <select class="form-control" id="filtroLimiteAtencionVasco">
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary" id="btnBuscarAtencionVasco">
                                    <i class="fa fa-refresh"></i> Consultar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-default" id="boxResumenAtencionVasco" style="display:none;">
            <div class="box-body">
                <div class="row text-center">
                    <div class="col-sm-3">
                        <small class="text-muted">Resultados</small>
                        <div><strong id="resumenCountAtencionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Listas para tomar</small>
                        <div><strong id="resumenTomablesAtencionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Seleccionadas</small>
                        <div><strong id="resumenSeleccionAtencionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Trace ID</small>
                        <div><code id="resumenTraceAtencionVasco" class="vasco-atencion-trace">—</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <button type="button" class="btn btn-default btn-sm" id="btnMarcarTodosAtencionVasco">
                    <i class="fa fa-check-square-o"></i> Seleccionar todos
                </button>
                <button type="button" class="btn btn-default btn-sm" id="btnDesmarcarTodosAtencionVasco">
                    <i class="fa fa-square-o"></i> Ninguno
                </button>
                <button type="button" class="btn btn-success" id="btnTomarSeleccionAtencionVasco" disabled>
                    <i class="fa fa-hand-paper-o"></i> Tomar seleccionadas
                </button>
                <button type="button" class="btn btn-primary" id="btnCompletarSeleccionAtencionVasco" disabled style="display:none;">
                    <i class="fa fa-check"></i> Completar seleccionadas
                </button>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tablaAtencionVasco" width="100%">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Doc.</th>
                                <th>Mensaje</th>
                                <th>Vendedor Vasco</th>
                                <th>Vend. ERP</th>
                                <th>Estado ERP</th>
                                <th style="width:170px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoAtencionVasco">
                            <tr class="vasco-atencion-empty">
                                <td colspan="9" class="text-center text-muted">
                                    Use <strong>Consultar</strong> para cargar solicitudes desde Vasco.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list-alt"></i> Registro de operación</h3>
            </div>
            <div class="box-body">
                <pre id="logAtencionVasco" class="vasco-atencion-log">Sin operaciones aún.</pre>
            </div>
        </div>

    </section>
</div>

<script>
    window.document.title = "Solicitudes atención — Vasco Online";
    window.vascoAtencionUsuario = <?php echo json_encode(isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "vascorp"); ?>;
</script>
