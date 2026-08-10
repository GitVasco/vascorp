<div class="content-wrapper mpr-page">

    <section class="content-header">
        <h1>
            Reprocesos MP
            <small>Catálogo de transformaciones</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Materia Prima</li>
            <li class="active">Reprocesos MP</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-solid mpr-box">
            <div class="box-header with-border mpr-toolbar">
                <div class="mpr-toolbar-left">
                    <h3 class="box-title">
                        Relaciones
                        <span class="badge bg-aqua" id="mprCount">0</span>
                    </h3>
                    <div class="mpr-filters">
                        <div class="input-group input-group-sm mpr-filter-search">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="mprFiltroTexto" placeholder="Filtrar por código, descripción o color…">
                        </div>
                        <select class="form-control input-sm selectpicker mpr-filter-proceso" id="mprFiltroProceso" data-width="180px" data-style="btn-default btn-sm" title="Todos los procesos">
                            <option value="">Todos los procesos</option>
                        </select>
                    </div>
                </div>
                <div class="mpr-toolbar-right">
                    <button type="button" class="btn btn-primary" id="mprBtnNuevo">
                        <i class="fa fa-plus"></i> Nueva relación
                    </button>
                </div>
            </div>

            <div class="box-body mpr-list-wrap">
                <div id="mprLista" class="mpr-lista"></div>
                <div class="mpr-empty" id="mprEmpty" style="display:none;">
                    <i class="fa fa-retweet"></i>
                    <p>Aún no hay relaciones en el catálogo.</p>
                    <button type="button" class="btn btn-primary btn-sm" id="mprBtnNuevoEmpty">
                        <i class="fa fa-plus"></i> Crear la primera
                    </button>
                </div>
                <div class="mpr-no-match" id="mprNoMatch" style="display:none;">
                    <p class="text-muted">Sin coincidencias con el filtro.</p>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Modal alta / edición -->
<div class="modal fade" id="modalMpReproceso" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content mpr-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="mprTituloForm">Nueva relación</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mprId" value="">

                <div class="mpr-flow">
                    <div class="mpr-flow-col">
                        <div class="mpr-slot-label">
                            <span class="mpr-step">1</span> MP origen
                        </div>
                        <div class="mpr-slot" id="mprSlotOrigen" data-lado="origen">
                            <div class="mpr-slot-search">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="mprBuscarOrigen" placeholder="Código, fábrica o descripción" autocomplete="off">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" id="mprBtnBuscarOrigen" title="Buscar">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </span>
                                </div>
                                <div class="mpr-opciones" id="mprOpcionesOrigen" style="display:none;"></div>
                            </div>
                            <input type="hidden" id="mprCodProOrigen">
                            <div class="mpr-card" id="mprCardOrigen">
                                <div class="mpr-card-empty">Busca y selecciona la materia prima base</div>
                            </div>
                        </div>
                    </div>

                    <div class="mpr-flow-mid">
                        <div class="mpr-slot-label">
                            <span class="mpr-step">2</span> Proceso
                        </div>
                        <div class="mpr-proceso-box">
                            <select class="form-control selectpicker" id="mprProceso" data-width="100%" data-live-search="true" title="Seleccionar proceso…">
                                <option value="">Seleccionar…</option>
                            </select>
                            <div class="mpr-arrow" aria-hidden="true">
                                <i class="fa fa-long-arrow-right"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mpr-flow-col mpr-flow-destino">
                        <div class="mpr-slot-label">
                            <span class="mpr-step">3</span> MP destino
                            <small class="text-muted" id="mprDestinoHint">puedes agregar varios</small>
                        </div>
                        <div class="mpr-slot mpr-slot-destino" id="mprSlotDestino" data-lado="destino">
                            <div class="mpr-slot-search">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="mprBuscarDestino" placeholder="Buscar y agregar MP resultante" autocomplete="off">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" id="mprBtnBuscarDestino" title="Buscar">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </span>
                                </div>
                                <div class="mpr-opciones" id="mprOpcionesDestino" style="display:none;"></div>
                            </div>
                            <input type="hidden" id="mprCodProDestino">
                            <!-- Modo edición: una sola tarjeta -->
                            <div class="mpr-card" id="mprCardDestino" style="display:none;">
                                <div class="mpr-card-empty">Busca y selecciona la MP resultante</div>
                            </div>
                            <!-- Modo alta: lista múltiple -->
                            <div id="mprDestinosMulti">
                                <ul class="mpr-destinos-lista" id="mprDestinosLista"></ul>
                                <div class="mpr-card-empty" id="mprDestinosEmpty">Busca y agrega una o más MP destino</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mpr-obs" style="margin-top:16px;margin-bottom:0;">
                    <label for="mprObservacion">Observación <span class="text-muted">(opcional)</span></label>
                    <input type="text" class="form-control" id="mprObservacion" maxlength="200" placeholder="Ej. solo para ciertos modelos / proveedor…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="mprBtnGuardar">
                    <i class="fa fa-save"></i> <span id="mprBtnGuardarTexto">Guardar</span>
                </button>
            </div>
        </div>
    </div>
</div>
