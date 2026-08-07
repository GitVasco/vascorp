<div class="content-wrapper" id="panelHelpdesk">

    <section class="content-header">
        <h1>
            Portal de Soporte
            <small>Helpdesk TI</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>TI</li>
            <li class="active">Helpdesk</li>
        </ol>
    </section>

    <section class="content">

        <ul class="nav nav-tabs hd-tabs" id="hdTabs">
            <li class="active" id="hdTabNuevoLi">
                <a href="#hdVistaNuevo" data-toggle="tab" id="hdTabNuevo">
                    <i class="fa fa-plus"></i> Nuevo ticket
                </a>
            </li>
            <li id="hdTabListaLi">
                <a href="#hdVistaLista" data-toggle="tab" id="hdTabLista">
                    <i class="fa fa-list"></i> <span id="hdTabListaLabel">Mis tickets</span>
                </a>
            </li>
        </ul>

        <div class="tab-content hd-tab-content">

            <!-- ========== NUEVO TICKET ========== -->
            <div class="tab-pane active" id="hdVistaNuevo">
                <div class="row">
                    <div class="col-md-8">
                        <div class="box box-primary" id="hdBoxAlta">
                            <div class="box-header with-border">
                                <h3 class="box-title">Nuevo Ticket</h3>
                                <p class="hd-subtitle">Completa la información para registrar tu solicitud o incidente.</p>
                            </div>
                            <div class="box-body">
                                <form id="hdFormAlta" autocomplete="off" enctype="multipart/form-data">
                                    <input type="hidden" id="hdTipo" name="tipo" value="INCIDENCIA">

                                    <div class="hd-section">
                                        <h4 class="hd-section-title"><span class="hd-step">1</span> Información de la Solicitud</h4>

                                        <label>Tipo de Solicitud <span class="text-danger">*</span></label>
                                        <div class="hd-tipo-grid hd-tipo-fila" id="hdTipoCards">
                                            <button type="button" class="hd-tipo-card active" data-tipo="INCIDENCIA"
                                                    data-ayuda="Problemas o errores que impiden realizar una tarea.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-exclamation-triangle text-orange"></i>
                                                <strong>Incidencia</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="REQUERIMIENTO"
                                                    data-ayuda="Solicitudes de acceso, configuración o recursos.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-cog text-green"></i>
                                                <strong>Requerimiento</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="CONSULTA"
                                                    data-ayuda="Dudas sobre el uso del sistema o procesos.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-question-circle text-blue"></i>
                                                <strong>Consulta</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="OTRO"
                                                    data-ayuda="Otras solicitudes que no encajan en las categorías anteriores.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-ellipsis-h text-muted"></i>
                                                <strong>Otro</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="DESARROLLO"
                                                    data-ayuda="Nuevas funciones o mejoras del sistema.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-code text-aqua"></i>
                                                <strong>Desarrollo</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="CORRECCION"
                                                    data-ayuda="Bugs o comportamientos incorrectos que hay que corregir.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-wrench text-red"></i>
                                                <strong>Corrección</strong>
                                            </button>
                                        </div>

                                        <label style="margin-top:12px;">Sistema <span class="text-danger">*</span></label>
                                        <input type="hidden" id="hdSistema" name="sistema" value="VASCORP">
                                        <div class="hd-tipo-grid hd-tipo-fila" id="hdSistemaCards">
                                            <button type="button" class="hd-tipo-card hd-sis-card active" data-sistema="VASCORP"
                                                    data-ayuda="ERP actual de la empresa. Sigue en uso y creciendo con nuevos módulos.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-desktop text-blue"></i>
                                                <strong>Vascorp</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card hd-sis-card" data-sistema="SISTEMA_VASCO"
                                                    data-ayuda="Nuestra conexión hacia internet: visita, portal, cobranzas y campañas en la nube.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-cloud text-aqua"></i>
                                                <strong>Sistema Vasco</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card hd-sis-card" data-sistema="VASCOPRO"
                                                    data-ayuda="Será la nueva versión del ERP. Está en proceso de migración (hoy RRHH).">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-rocket text-green"></i>
                                                <strong>VascoPro</strong>
                                            </button>
                                            <button type="button" class="hd-tipo-card hd-sis-card" data-sistema="TI_EMPRESA"
                                                    data-ayuda="PCs, correos, programas, red, impresoras y soporte interno de la empresa.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-life-ring text-orange"></i>
                                                <strong>TI / Soporte</strong>
                                            </button>
                                        </div>

                                        <div class="row" style="margin-top:14px;">
                                            <div class="col-sm-3">
                                                <div class="form-group">
                                                    <label for="hdArea">Área <span class="text-danger">*</span></label>
                                                    <select class="form-control selectpicker" id="hdArea" required
                                                            data-live-search="true" data-width="100%"
                                                            title="Seleccionar área">
                                                        <option value="">Seleccionar área</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-3">
                                                <div class="form-group">
                                                    <label for="hdModulo">Módulo / tema</label>
                                                    <select class="form-control selectpicker" id="hdModulo"
                                                            data-live-search="true" data-width="100%"
                                                            title="Seleccionar módulo">
                                                        <option value="">Seleccionar módulo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="hdTitulo">Asunto <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="hdTitulo" maxlength="200" required
                                                           placeholder="Ej: No puedo emitir factura - Error al guardar">
                                                </div>
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label for="hdPrioridad">Prioridad <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="hdPrioridad">
                                                        <option value="BAJA">Baja</option>
                                                        <option value="MEDIA" selected>Media</option>
                                                        <option value="ALTA">Alta</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row hd-solo-gestionar" style="display:none;">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="hdSolicitante">Solicitante</label>
                                                    <select class="form-control selectpicker" id="hdSolicitante"
                                                            data-live-search="true" data-width="100%"
                                                            title="Yo (sesión actual)">
                                                        <option value="">Yo (sesión actual)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="hdAsignadoAlta">Asignar a</label>
                                                    <select class="form-control selectpicker" id="hdAsignadoAlta"
                                                            data-live-search="true" data-width="100%"
                                                            title="Sin asignar">
                                                        <option value="">Sin asignar</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hd-section">
                                        <h4 class="hd-section-title"><span class="hd-step">2</span> Descripción del Problema</h4>
                                        <div class="hd-pulir-bar">
                                            <button type="button" class="btn btn-default btn-sm" id="hdBtnPulir"
                                                    title="Corrige ortografía y redacción con IA">
                                                <i class="fa fa-magic"></i> Pulir texto (IA)
                                            </button>
                                            <small class="text-muted">Corrige sintaxis y da sentido profesional al asunto, descripción y pasos.</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="hdDescripcion">Descripción detallada <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="hdDescripcion" rows="5" required
                                                      placeholder="Describe el problema o solicitud con el mayor detalle posible…"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="hdPasos">Pasos para reproducir <span class="text-muted">(opcional)</span></label>
                                                    <textarea class="form-control" id="hdPasos" rows="5"
                                                              placeholder="1. Ir a…&#10;2. Hacer clic en…&#10;3. Observar que…"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Adjuntar archivos</label>
                                                <div class="hd-dropzone" id="hdDropzone">
                                                    <i class="fa fa-cloud-upload"></i>
                                                    <p><strong>Arrastra archivos aquí</strong><br>o haz clic para seleccionar</p>
                                                    <small>JPG, PNG, PDF, DOC, XLS · Máx. 10 MB · Hasta 5 archivos</small>
                                                    <input type="file" id="hdAdjuntos" name="adjuntos[]" multiple
                                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                                                </div>
                                                <ul class="hd-file-list" id="hdFileList"></ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hd-form-footer">
                                        <span class="text-muted"><span class="text-danger">*</span> Campos obligatorios</span>
                                        <div>
                                            <button type="button" class="btn btn-default" id="hdBtnCancelar">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" id="hdBtnCrear">
                                                <i class="fa fa-paper-plane"></i> Enviar Ticket
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="box box-solid hd-side-box">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-info-circle text-blue"></i> Información importante</h3>
                            </div>
                            <div class="box-body">
                                <p>Nuestro equipo revisará tu solicitud y te contactará según la prioridad indicada.</p>
                                <ul class="hd-side-list">
                                    <li><strong>Horario de atención:</strong><br>Lun–Vie, 8:00 am – 5:30 pm<br>Sábados, 8:00 am – 12:15 pm</li>
                                    <li><strong>Tiempo de respuesta:</strong><br>Según prioridad del ticket</li>
                                    <li><strong>Notificaciones:</strong><br>Actualizaciones por el canal elegido (próximamente)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="box box-solid hd-side-box hd-side-tips">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-lightbulb-o text-yellow"></i> Consejos para un mejor soporte</h3>
                            </div>
                            <div class="box-body">
                                <ul class="hd-tips">
                                    <li>Sé específico: indica módulo, pantalla y qué esperabas</li>
                                    <li>Incluye pasos para reproducir el problema</li>
                                    <li>Adjunta capturas de pantalla si es posible</li>
                                    <li>Menciona si el error es de todos o solo tuyo</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== LISTA / BANDEJA ========== -->
            <div class="tab-pane" id="hdVistaLista">
                <div class="row">
                    <div class="col-md-7">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-list"></i> <span id="hdListaTitulo">Mis tickets</span></h3>
                                <div class="box-tools pull-right hd-filtros">
                                    <input type="text" class="form-control input-sm" id="hdFiltroQ"
                                           placeholder="Buscar…" style="width:110px;display:inline-block;">
                                    <select class="form-control input-sm" id="hdFiltroEstado" style="width:auto;display:inline-block;">
                                        <option value="__ACTIVOS__" selected>Activos</option>
                                        <option value="">Todos</option>
                                        <option value="ABIERTO">Abierto</option>
                                        <option value="EN_PROGRESO">En progreso</option>
                                        <option value="ESPERANDO_USUARIO">Esperando usuario</option>
                                        <option value="CERRADO">Cerrado</option>
                                    </select>
                                    <select class="form-control input-sm" id="hdFiltroTipo" style="width:auto;display:inline-block;">
                                        <option value="">Tipo</option>
                                        <option value="INCIDENCIA">Incidencia</option>
                                        <option value="REQUERIMIENTO">Requerimiento</option>
                                        <option value="CONSULTA">Consulta</option>
                                        <option value="OTRO">Otro</option>
                                        <option value="DESARROLLO">Desarrollo</option>
                                        <option value="CORRECCION">Corrección</option>
                                    </select>
                                    <button type="button" class="btn btn-default btn-sm" id="hdBtnRefrescar" title="Actualizar">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="box-body table-responsive hd-tabla-wrap">
                                <table class="table table-striped table-condensed" id="hdTablaLista">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Asunto</th>
                                            <th>Tipo</th>
                                            <th>Pri.</th>
                                            <th>Estado</th>
                                            <th>Asignado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hd-vacio">
                                            <td colspan="7" class="text-muted text-center">Cargando…</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="box box-info" id="hdBoxDetalle" style="display:none;">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-ticket"></i> Detalle <span id="hdDetalleTitulo"></span></h3>
                            </div>
                            <div class="box-body" id="hdDetalleCuerpo"></div>
                        </div>
                        <div class="box box-default" id="hdBoxDetalleVacio">
                            <div class="box-body text-muted text-center" style="padding:40px 20px;">
                                Selecciona un ticket para ver el detalle.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
