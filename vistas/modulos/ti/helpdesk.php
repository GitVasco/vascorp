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
            <li id="hdTabIndicadoresLi">
                <a href="#hdVistaIndicadores" data-toggle="tab" id="hdTabIndicadores">
                    <i class="fa fa-bar-chart"></i> Indicadores
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
                                        <p class="help-block" style="margin-top:0;">
                                            Haz clic en un tipo para ver qué significa (aparece arriba).
                                        </p>
                                        <div class="hd-tipo-grid hd-tipo-fila" id="hdTipoCards">
                                            <button type="button" class="hd-tipo-card active" data-tipo="INCIDENCIA"
                                                    data-ayuda="Algo se rompió y no puedes trabajar: sale error, no carga, se cae o se bloquea. Ej.: no guarda la factura, pantalla en blanco. Si el sistema sí abre pero calcula o muestra mal → usa Corrección.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-exclamation-triangle text-orange"></i>
                                                <strong>Incidencia</strong>
                                                <small>Se rompió / no avanzo</small>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="REQUERIMIENTO"
                                                    data-ayuda="Pides que TI haga un ajuste sobre datos o configuración que ya existen (tú o el proceso se equivocaron, o necesitas un permiso). Ej.: mover tickets a otra fecha, dar acceso a un módulo, activar un usuario. No es programar algo nuevo.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-cog text-green"></i>
                                                <strong>Requerimiento</strong>
                                                <small>Ajuste / permiso / dato</small>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="CONSULTA"
                                                    data-ayuda="Solo quieres que te expliquen cómo hacer algo. No pides que cambien datos ni arreglen el sistema. Ej.: ¿dónde veo el estado de cuenta? ¿cómo anulo una factura?">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-question-circle text-blue"></i>
                                                <strong>Consulta</strong>
                                                <small>Solo una pregunta</small>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="DESARROLLO"
                                                    data-ayuda="Quieres que construyan o mejoren el sistema: pantalla nueva, función nueva o automatización. Suele llevar días o semanas. Ej.: pedidos por tablet, nuevo reporte automático.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-code text-aqua"></i>
                                                <strong>Desarrollo</strong>
                                                <small>Algo nuevo en el sistema</small>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="CORRECCION"
                                                    data-ayuda="El sistema sí abre y trabaja, pero se equivoca: calcula mal, muestra un dato incorrecto o un botón hace otra cosa. Ej.: el IGV sale mal. Si no carga o sale error y no puedes avanzar → usa Incidencia.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-wrench text-red"></i>
                                                <strong>Corrección</strong>
                                                <small>Abre, pero se equivoca</small>
                                            </button>
                                            <button type="button" class="hd-tipo-card" data-tipo="OTRO"
                                                    data-ayuda="Úsalo solo si revisaste los demás y ninguno encaja. Casi siempre sí hay un tipo correcto.">
                                                <span class="hd-tipo-help" title="¿Qué es?" aria-label="Ayuda">?</span>
                                                <i class="fa fa-ellipsis-h text-muted"></i>
                                                <strong>Otro</strong>
                                                <small>Ninguno encaja</small>
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
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="hdArea">Área <span class="text-danger">*</span></label>
                                                    <select class="form-control selectpicker" id="hdArea" required
                                                            data-live-search="true" data-width="100%"
                                                            title="Seleccionar área">
                                                        <option value="">Seleccionar área</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="form-group">
                                                    <label for="hdModulo">Módulo / tema</label>
                                                    <select class="form-control selectpicker" id="hdModulo"
                                                            data-live-search="true" data-width="100%"
                                                            title="Seleccionar módulo">
                                                        <option value="">Seleccionar módulo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-3">
                                                <div class="form-group">
                                                    <label for="hdPrioridad">Prioridad <span class="text-danger">*</span></label>
                                                    <select class="form-control selectpicker" id="hdPrioridad"
                                                            data-width="100%" data-show-content="true">
                                                        <option value="BAJA"
                                                            data-content="<span class='label label-prioridad-BAJA'><i class='fa fa-flag'></i> Baja</span>">Baja</option>
                                                        <option value="MEDIA" selected
                                                            data-content="<span class='label label-prioridad-MEDIA'><i class='fa fa-flag'></i> Media</span>">Media</option>
                                                        <option value="ALTA"
                                                            data-content="<span class='label label-prioridad-ALTA'><i class='fa fa-flag'></i> Alta</span>">Alta</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="hdTitulo">Asunto <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="hdTitulo" maxlength="200" required
                                                           placeholder="Ej: No puedo emitir factura - Error al guardar">
                                                </div>
                                            </div>
                                        </div>

                                        <?php
                                        $hdPuedePulirIa = isset($_SESSION["id"]) && (int) $_SESSION["id"] === 6;
                                        ?>
                                        <div class="hd-pulir-bar<?= $hdPuedePulirIa ? "" : " hd-pulir-hidden"; ?>" id="hdPulirBar">
                                            <button type="button" class="btn btn-info btn-sm" id="hdBtnPulir"
                                                    title="Mejora asunto, descripción y pasos con IA">
                                                <i class="fa fa-magic"></i> Pulir asunto y texto (IA)
                                            </button>
                                            <small class="text-muted">Mejora asunto, descripción y pasos.</small>
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
                                                    <label for="hdAsignadoAlta"><i class="fa fa-user"></i> Asignar a</label>
                                                    <select class="form-control selectpicker" id="hdAsignadoAlta"
                                                            data-live-search="true" data-width="100%"
                                                            title="Elegir responsable…">
                                                        <option value="">Sin asignar</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hd-section">
                                        <h4 class="hd-section-title"><span class="hd-step">2</span> Descripción del Problema</h4>
                                        <div class="form-group">
                                            <label for="hdDescripcion">Descripción detallada <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="hdDescripcion" rows="5" required
                                                      placeholder="Describe el problema o solicitud con el mayor detalle posible…"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="hdPasos" id="hdPasosLabel">Pasos para reproducir <span class="text-muted">(opcional)</span></label>
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
                            <div class="box-body" id="hdInfoImportante">
                                <div class="hd-ejemplo-box" id="hdEjemploBox">
                                    <strong id="hdEjemploTipoTitulo"><i class="fa fa-tag"></i> Tipo seleccionado</strong>
                                    <p class="hd-tipo-explicacion" id="hdInfoIntro">Elige un tipo para ver la explicación y un ejemplo.</p>
                                    <hr class="hd-ejemplo-sep">
                                    <strong><i class="fa fa-file-text-o"></i> Ejemplo</strong>
                                    <p class="hd-ejemplo-asunto" id="hdEjemploAsunto"></p>
                                    <p class="hd-ejemplo-desc" id="hdEjemploDesc"></p>
                                    <button type="button" class="btn btn-default btn-xs" id="hdBtnUsarEjemplo"
                                            title="Rellena asunto, descripción y pasos si están vacíos">
                                        <i class="fa fa-clipboard"></i> Usar este ejemplo
                                    </button>
                                </div>
                                <ul class="hd-side-list" id="hdInfoLista" style="margin-top:12px;">
                                    <li id="hdInfoPrioridad"><strong>Prioridad elegida:</strong><br>Según prioridad del ticket</li>
                                </ul>
                            </div>
                        </div>
                        <div class="box box-solid hd-side-box hd-side-tips">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-lightbulb-o text-yellow"></i> Consejos para un mejor soporte</h3>
                            </div>
                            <div class="box-body">
                                <p class="hd-tips-titulo">¿Qué tipo elijo?</p>
                                <ul class="hd-tips hd-tips-tipos" id="hdTipsTipos">
                                    <li><strong>Incidencia:</strong> no puedes avanzar (error, no carga)</li>
                                    <li><strong>Corrección:</strong> sí entra, pero hace mal (dato/cálculo)</li>
                                    <li><strong>Requerimiento:</strong> ajustar algo existente (fecha, acceso, permiso)</li>
                                    <li><strong>Consulta:</strong> solo una duda de uso</li>
                                    <li><strong>Desarrollo:</strong> función o pantalla nueva</li>
                                    <li><strong>Otro:</strong> solo si no encaja arriba</li>
                                </ul>
                                <p class="hd-tips-sla">
                                    <strong>SLA:</strong> tiempo objetivo en <em>horario laboral</em>
                                    (lun–vie 8:00–17:30 · sáb 8:00–12:15; noches y domingos no cuentan).
                                    Alta ~4 h, Media ~24 h, Baja ~72 h. En Desarrollo no aplica:
                                    TI define una fecha estimada. Un agente puede
                                    <strong>cancelar el SLA</strong> de un ticket con motivo (queda en historial).
                                </p>
                                <p class="hd-tips-dep">
                                    <strong>Si depende de otra área:</strong> gestiona lo de TI, deja comentario
                                    (qué se pidió y a quién), pasa a <em>Esperando</em> y baja la prioridad
                                    si no bloquea la operación. El reloj SLA sigue en horario laboral;
                                    si el caso deja de aplicar, <strong>cancela el SLA</strong> con motivo.
                                </p>
                                <p class="hd-tips-titulo">Para este tipo</p>
                                <ul class="hd-tips" id="hdConsejosLista">
                                    <li>Sé específico: indica módulo, pantalla y qué esperabas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== LISTA / BANDEJA ========== -->
            <div class="tab-pane" id="hdVistaLista">
                <div class="hd-kpis" id="hdKpis">
                    <button type="button" class="hd-kpi hd-kpi-total active" data-filtro="">
                        <span class="hd-kpi-n" id="hdKpiTotal">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-inbox"></i> Todos</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-activos" data-filtro="__ACTIVOS__">
                        <span class="hd-kpi-n" id="hdKpiActivos">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-bolt"></i> Activos</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-abierto" data-filtro="ABIERTO">
                        <span class="hd-kpi-n" id="hdKpiAbierto">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-folder-open"></i> Abiertos</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-progreso" data-filtro="EN_PROGRESO">
                        <span class="hd-kpi-n" id="hdKpiProgreso">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-spinner"></i> En progreso</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-espera" data-filtro="ESPERANDO_USUARIO">
                        <span class="hd-kpi-n" id="hdKpiEspera">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-hourglass-half"></i> Esperando</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-vencido" data-filtro="__VENCIDOS__">
                        <span class="hd-kpi-n" id="hdKpiVencidos">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-exclamation-triangle"></i> Vencidos SLA</span>
                    </button>
                    <button type="button" class="hd-kpi hd-kpi-cerrado" data-filtro="CERRADO">
                        <span class="hd-kpi-n" id="hdKpiCerrado">0</span>
                        <span class="hd-kpi-lbl"><i class="fa fa-check"></i> Cerrados</span>
                    </button>
                </div>
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> <span id="hdListaTitulo">Mis tickets</span></h3>
                        <div class="box-tools pull-right hd-filtros">
                            <input type="text" class="form-control input-sm" id="hdFiltroQ"
                                   placeholder="Buscar…" style="width:140px;display:inline-block;">
                            <select class="form-control input-sm selectpicker" id="hdFiltroEstado"
                                    title="Estado" data-width="170px" data-container="body">
                                <option value="">Todos</option>
                                <option value="__ACTIVOS__">Activos</option>
                                <option value="ABIERTO">Abierto</option>
                                <option value="EN_PROGRESO">En progreso</option>
                                <option value="ESPERANDO_USUARIO">Esperando (usuario/área)</option>
                                <option value="CERRADO">Cerrado</option>
                                <option value="__VENCIDOS__">Vencidos SLA</option>
                            </select>
                            <select class="form-control input-sm selectpicker" id="hdFiltroTipo"
                                    title="Tipo" data-width="150px" data-container="body">
                                <option value="">Tipo</option>
                                <option value="INCIDENCIA">Incidencia</option>
                                <option value="REQUERIMIENTO">Requerimiento</option>
                                <option value="CONSULTA">Consulta</option>
                                <option value="DESARROLLO">Desarrollo</option>
                                <option value="CORRECCION">Corrección</option>
                                <option value="OTRO">Otro</option>
                            </select>
                            <span id="hdFiltrosPersonas" class="hd-filtros-personas" style="display:none;">
                                <select class="form-control input-sm" id="hdFiltroSolicitante"
                                        title="Solicitante" data-live-search="true" data-width="170px"
                                        style="width:170px;display:inline-block;">
                                    <option value="">Solicitante</option>
                                </select>
                                <select class="form-control input-sm" id="hdFiltroAsignado"
                                        title="Asignado" data-width="140px"
                                        style="width:140px;display:inline-block;">
                                    <option value="">Asignado</option>
                                    <option value="__SIN__">Sin asignar</option>
                                </select>
                            </span>
                            <button type="button" class="btn btn-default btn-sm" id="hdBtnRefrescar" title="Actualizar">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body table-responsive hd-tabla-wrap">
                        <table class="table table-hover table-condensed" id="hdTablaLista">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Asunto</th>
                                    <th>Solicitante</th>
                                    <th>Tipo</th>
                                    <th>Pri.</th>
                                    <th>Estado</th>
                                    <th>Asignado</th>
                                    <th>SLA</th>
                                    <th>Antigüedad</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="hd-vacio">
                                    <td colspan="10" class="text-muted text-center">Cargando…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========== INDICADORES / DASHBOARD ========== -->
            <div class="tab-pane" id="hdVistaIndicadores">
                <div class="box box-solid hd-ind-filtros">
                    <div class="box-body" style="padding:10px 15px;">
                        <div class="form-inline hd-ind-filtros-row">
                            <label>Desde</label>
                            <input type="date" class="form-control input-sm" id="hdIndDesde">
                            <label>Hasta</label>
                            <input type="date" class="form-control input-sm" id="hdIndHasta">
                            <button type="button" class="btn btn-primary btn-sm" id="hdIndAplicar">
                                <i class="fa fa-filter"></i> Aplicar
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="hdIndMes">Mes</button>
                            <button type="button" class="btn btn-default btn-sm" id="hdInd30">30 días</button>
                            <button type="button" class="btn btn-default btn-sm" id="hdInd7">7 días</button>
                            <small class="text-muted" id="hdIndSlaHint"></small>
                        </div>
                    </div>
                </div>

                <div class="hd-ind-kpis" id="hdIndKpis">
                    <div class="hd-ind-kpi hd-ind-kpi-blue">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-inbox"></i></div>
                        <div>
                            <span class="n" id="hdIndCreado">0</span>
                            <span class="l">Recibidos</span>
                            <span class="d" id="hdIndDeltaCreado"></span>
                        </div>
                    </div>
                    <div class="hd-ind-kpi hd-ind-kpi-green">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-check-circle"></i></div>
                        <div>
                            <span class="n" id="hdIndCerrado">0</span>
                            <span class="l">Resueltos</span>
                            <span class="d" id="hdIndDeltaCerrado"></span>
                        </div>
                    </div>
                    <div class="hd-ind-kpi hd-ind-kpi-amber">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-folder-open"></i></div>
                        <div>
                            <span class="n" id="hdIndAbierto">0</span>
                            <span class="l">Pendientes</span>
                        </div>
                    </div>
                    <div class="hd-ind-kpi hd-ind-kpi-rose">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-exclamation-triangle"></i></div>
                        <div>
                            <span class="n" id="hdIndVencido">0</span>
                            <span class="l">Vencidos SLA</span>
                        </div>
                    </div>
                    <div class="hd-ind-kpi hd-ind-kpi-teal">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-clock-o"></i></div>
                        <div>
                            <span class="n" id="hdIndPromedio">—</span>
                            <span class="l">Tiempo prom.</span>
                            <span class="d" id="hdIndDeltaPromedio"></span>
                        </div>
                    </div>
                    <div class="hd-ind-kpi hd-ind-kpi-lilac">
                        <div class="hd-ind-kpi-icon"><i class="fa fa-crosshairs"></i></div>
                        <div>
                            <span class="n" id="hdIndSlaPct">—</span>
                            <span class="l">SLA cumplimiento</span>
                            <span class="d" id="hdIndDeltaSla"></span>
                        </div>
                    </div>
                </div>

                <div class="row hd-ind-charts-row">
                    <div class="col-md-5">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Creados por día</h3></div>
                            <div class="box-body hd-chart-sm"><canvas id="hdChartDia"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">SLA cerrados</h3></div>
                            <div class="box-body hd-chart-sm"><canvas id="hdChartSla"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Backlog por antigüedad</h3></div>
                            <div class="box-body hd-chart-sm"><canvas id="hdChartBacklog"></canvas></div>
                        </div>
                    </div>
                </div>

                <div class="row hd-ind-charts-row">
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Por tipo</h3></div>
                            <div class="box-body hd-chart-xs"><canvas id="hdChartTipo"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Por prioridad</h3></div>
                            <div class="box-body hd-chart-xs"><canvas id="hdChartPrioridad"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Por sistema</h3></div>
                            <div class="box-body hd-chart-xs"><canvas id="hdChartSistema"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Por área</h3></div>
                            <div class="box-body hd-chart-xs"><canvas id="hdChartArea"></canvas></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Resolución por prioridad</h3></div>
                            <div class="box-body" id="hdIndResolucionPri"></div>
                        </div>
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Temas recurrentes</h3></div>
                            <div class="box-body table-responsive" style="max-height:220px;overflow:auto;">
                                <table class="table table-condensed" id="hdIndTablaModulos">
                                    <thead><tr><th>#</th><th>Módulo / tema</th><th>N°</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Técnicos — resumen</h3></div>
                            <div class="box-body table-responsive">
                                <table class="table table-condensed table-hover" id="hdIndTablaAsignados">
                                    <thead>
                                        <tr>
                                            <th>Asignado</th>
                                            <th>Asig.</th>
                                            <th>Pend.</th>
                                            <th>Resueltos</th>
                                            <th>Venc.</th>
                                            <th>SLA</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Vencidos SLA</h3></div>
                            <div class="box-body table-responsive" style="max-height:200px;overflow:auto;">
                                <table class="table table-condensed" id="hdIndTablaVencidos">
                                    <thead>
                                        <tr><th>#</th><th>Asunto</th><th>Pri.</th><th>SLA</th></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid hd-ind-box">
                            <div class="box-header with-border"><h3 class="box-title">Actividad reciente</h3></div>
                            <div class="box-body" id="hdIndActividad" style="max-height:420px;overflow:auto;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== CONVERSACIÓN / DETALLE ========== -->
            <div class="tab-pane" id="hdVistaConversacion">
                <div class="row">
                    <div class="col-md-8">
                        <div class="box box-primary hd-conv-main">
                            <div class="box-header with-border" id="hdConvCabecera"></div>
                            <div class="box-body" style="padding-top:0;">
                                <ul class="nav nav-tabs hd-conv-tabs">
                                    <li class="active"><a href="#hdTabConv" data-toggle="tab">Conversación</a></li>
                                    <li><a href="#hdTabDetalles" data-toggle="tab">Detalles</a></li>
                                    <li><a href="#hdTabArchivos" data-toggle="tab">Archivos <span class="badge" id="hdBadgeArchivos">0</span></a></li>
                                    <li><a href="#hdTabHistorial" data-toggle="tab">Historial</a></li>
                                </ul>
                                <div class="tab-content hd-conv-tab-content">
                                    <div class="tab-pane active" id="hdTabConv">
                                        <div class="hd-hilo" id="hdHilo"></div>
                                        <div class="hd-responder" id="hdResponderBox">
                                            <form id="hdFormComentar">
                                                <div class="hd-pulir-bar">
                                                    <label style="margin:0;">Responder</label>
                                                    <button type="button" class="btn btn-default btn-sm" id="hdBtnPulirResp"
                                                            style="display:none;"
                                                            title="Corregir ortografía y redacción con IA">
                                                        <i class="fa fa-magic"></i> Corregir con IA
                                                    </button>
                                                </div>
                                                <textarea class="form-control" id="hdComentario" rows="3"
                                                          placeholder="Escribe tu respuesta…"></textarea>
                                                <div class="hd-dropzone hd-dropzone-resp" id="hdDropzoneResp">
                                                    <i class="fa fa-paperclip"></i>
                                                    <p><strong>Adjuntar archivos</strong> · arrastra o haz clic</p>
                                                    <small>JPG, PNG, PDF, DOC, XLS · Máx. 10 MB · Hasta 5</small>
                                                    <input type="file" id="hdAdjuntosResp" name="adjuntos[]" multiple
                                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                                                </div>
                                                <ul class="hd-file-list" id="hdFileListResp"></ul>
                                                <div class="hd-responder-actions">
                                                    <div class="hd-responder-opts hd-solo-gestionar" style="display:none;">
                                                        <label class="text-muted">Al enviar, cambiar estado a</label>
                                                        <select class="form-control selectpicker" id="hdRespEstado"
                                                                data-width="180px" data-container="body">
                                                            <option value="">(sin cambio)</option>
                                                            <option value="EN_PROGRESO"
                                                                    data-content="<span class='label label-warning'>En progreso</span>">En progreso</option>
                                                            <option value="ESPERANDO_USUARIO"
                                                                    data-content="<span class='label label-info'>Esperando</span>">Esperando (usuario/área)</option>
                                                            <option value="CERRADO"
                                                                    data-content="<span class='label label-default'>Cerrado</span>">Cerrado</option>
                                                            <option value="ABIERTO"
                                                                    data-content="<span class='label label-primary'>Abierto</span>">Abierto</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary" id="hdBtnEnviarResp">
                                                        <i class="fa fa-paper-plane"></i> Enviar respuesta
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="hdTabDetalles"></div>
                                    <div class="tab-pane" id="hdTabArchivos"></div>
                                    <div class="tab-pane" id="hdTabHistorial"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="box box-solid" id="hdConvSidebarInfo">
                            <div class="box-header with-border">
                                <h3 class="box-title">Información del ticket</h3>
                            </div>
                            <div class="box-body" id="hdConvSidebarBody"></div>
                        </div>
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h3 class="box-title">Solicitante</h3>
                            </div>
                            <div class="box-body" id="hdConvSolicitante"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
