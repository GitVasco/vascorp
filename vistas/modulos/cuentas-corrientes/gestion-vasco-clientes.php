<div class="content-wrapper" id="panelGestionVascoClientes">

    <section class="content-header">
        <h1>
            Gestión Vasco
            <small>Contacto WhatsApp capturado en visita</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Cuentas corrientes</li>
            <li class="active">Gestión Vasco</li>
        </ol>
    </section>

    <section class="content">

        <p class="vasco-gestion-intro text-muted">
            Datos capturados por vendedores en <strong>Vasco</strong> con el cliente presente
            (<span class="label label-warning">Pendiente sync</span>).
            Revise el celular y el consentimiento, aplique en el ERP y confirme a Vasco.
            El teléfono actualizado se usará en las <strong>notificaciones WhatsApp</strong> de letras.
        </p>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-search"></i> Buscar pendientes</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroDesdeGestionVasco">Desde</label>
                            <input type="date" class="form-control" id="filtroDesdeGestionVasco">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroLimiteGestionVasco">Límite</label>
                            <select class="form-control" id="filtroLimiteGestionVasco">
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
                                <button type="button" class="btn btn-primary" id="btnBuscarGestionVasco">
                                    <i class="fa fa-refresh"></i> Consultar pendientes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-default" id="boxResumenGestionVasco" style="display:none;">
            <div class="box-body">
                <div class="row text-center">
                    <div class="col-sm-3">
                        <small class="text-muted">Pendientes</small>
                        <div><strong id="resumenCountGestionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Listos para aplicar</small>
                        <div><strong id="resumenAplicablesGestionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Seleccionados</small>
                        <div><strong id="resumenSeleccionGestionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Trace ID</small>
                        <div><code id="resumenTraceGestionVasco" class="vasco-gestion-trace">—</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <button type="button" class="btn btn-default btn-sm" id="btnMarcarTodosGestionVasco">
                    <i class="fa fa-check-square-o"></i> Seleccionar todos
                </button>
                <button type="button" class="btn btn-default btn-sm" id="btnDesmarcarTodosGestionVasco">
                    <i class="fa fa-square-o"></i> Ninguno
                </button>
                <button type="button" class="btn btn-success" id="btnAplicarSeleccionGestionVasco" disabled>
                    <i class="fa fa-check"></i> Aplicar seleccionadas
                </button>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tablaGestionVasco" width="100%">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Fecha gestión</th>
                                <th>Cliente</th>
                                <th>Doc.</th>
                                <th>Celular Vasco</th>
                                <th>Consent.</th>
                                <th>Tel. ERP actual</th>
                                <th>Tel. ERP nuevo</th>
                                <th>Vendedor</th>
                                <th>Estado ERP</th>
                                <th style="width:150px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoGestionVasco">
                            <tr class="vasco-gestion-empty">
                                <td colspan="11" class="text-center text-muted">
                                    Use <strong>Consultar pendientes</strong> para cargar gestiones desde Vasco.
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
                <pre id="logGestionVasco" class="vasco-gestion-log">Sin operaciones aún.</pre>
            </div>
        </div>

    </section>
</div>

<script>
    window.document.title = "Gestión Vasco — Clientes";
    window.vascoGestionUsuario = <?php echo json_encode(isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "vascorp"); ?>;
</script>
