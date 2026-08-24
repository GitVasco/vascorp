<div class="content-wrapper" id="panelCuadreVentas">

    <section class="content-header">
        <h1>
            Cuadre de ventas del día
            <small>Pendientes · vendedores 08 · pagos que cuadran · aún no entra a cuentas</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Cuentas corrientes</li>
            <li class="active">Cuadre de ventas</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Ventas del día</h3>
            </div>
            <div class="box-body">
                <div class="cv-toolbar">
                    <div class="form-inline cv-filtros">
                        <div class="form-group">
                            <label for="cvFecha">Fecha</label>
                            <input type="date" class="form-control" id="cvFecha" name="cvFecha">
                        </div>
                        <button type="button" class="btn btn-primary" id="cvBtnBuscar">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                    <div class="cv-totales">
                        <span>Docs: <strong id="cvTotCantidad">0</strong></span>
                        <span>Monto: <strong id="cvTotMonto">0.00</strong></span>
                        <span>Saldo: <strong id="cvTotSaldo">0.00</strong></span>
                    </div>
                    <p class="text-muted cv-ayuda" id="cvAyuda">
                        Solo pendientes de vendedores <strong>08</strong>. Un lote = un cliente.
                    </p>
                </div>

                <ul class="nav nav-tabs cv-nav-tabs" id="cvNavTabs" style="display:none;">
                    <li class="active" id="cvLiDocs">
                        <a href="#cvTabDocs" data-toggle="tab">Documentos</a>
                    </li>
                    <li id="cvLiValidar">
                        <a href="#cvTabValidar" data-toggle="tab">
                            Por validar <span class="badge" id="cvBadgeValidar">0</span>
                        </a>
                    </li>
                    <li id="cvLiProcesar" style="display:none;">
                        <a href="#cvTabProcesar" data-toggle="tab">
                            Por procesar <span class="badge" id="cvBadgeProcesar">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content cv-tab-content">
                    <div class="tab-pane active" id="cvTabDocs">
                        <div class="cv-lote" id="cvLoteBar">
                            <span>Cliente: <strong id="cvLoteCliente">—</strong></span>
                            <span>Marcados: <strong id="cvLoteN">0</strong></span>
                            <span>A aplicar: <strong id="cvLoteTotal">0.00</strong></span>
                            <button type="button" class="btn btn-success btn-sm" id="cvBtnGuardar" disabled>
                                <i class="fa fa-save"></i> Guardar borrador
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="cvBtnLimpiar">Limpiar</button>
                            <input type="text" class="form-control input-sm" id="cvBuscaCliente" placeholder="Buscar cliente…" autocomplete="off">
                        </div>
                        <div class="cv-borradores" id="cvBorradores"></div>
                        <div class="cv-registrados" id="cvRegistrados"></div>
                        <div class="cv-cuerpo">
                            <div class="table-responsive cv-tabla-wrap">
                                <table class="table table-bordered table-striped table-condensed" id="cvTablaVentas">
                                    <thead>
                                        <tr>
                                            <th class="cv-col-check"></th>
                                            <th class="cv-col-usuario" style="display:none;">Usuario</th>
                                            <th class="cv-col-vendedor" style="display:none;">Vendedor</th>
                                            <th class="cv-col-tipo">Tipo</th>
                                            <th class="cv-col-doc">Documento</th>
                                            <th class="cv-col-cliente">Cliente</th>
                                            <th class="cv-col-monto text-right">Monto</th>
                                            <th class="cv-col-saldo text-right">Saldo</th>
                                            <th class="cv-col-aplicar text-right">A aplicar</th>
                                            <th class="cv-col-estado">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cv-vacio">
                                            <td colspan="6" class="text-muted text-center">Elige una fecha y busca.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <aside class="cv-pagos" id="cvPagosBox">
                                <h4>Pagos</h4>
                                <p class="text-muted cv-pagos-ayuda">
                                    Elige el medio y la OP. Si está en Abonos, entra por su monto completo.
                                    La suma de los abonos tiene que ser igual al total de las boletas.
                                </p>
                                <div class="cv-pago-form">
                                    <label for="cvMedio">Medio</label>
                                    <select class="form-control input-sm selectpicker" id="cvMedio"
                                            data-style="btn-default btn-sm"
                                            data-width="100%"
                                            data-container="body"
                                            data-size="8">
                                        <option value="80">Efectivo</option>
                                        <option value="15">Yape</option>
                                        <option value="05">Depósito</option>
                                        <option value="17">Tarjeta</option>
                                        <option value="16">Link de pago</option>
                                        <option value="14">Culqi</option>
                                    </select>
                                    <div class="cv-pago-fila cv-sin-op" id="cvPagoFila">
                                        <div id="cvPagoOpWrap" class="cv-pago-op-col" style="display:none;">
                                            <label for="cvOpe">Nº de OP <span class="text-muted">si hay</span></label>
                                            <input type="text" class="form-control input-sm" id="cvOpe" placeholder="Operación" maxlength="50" autocomplete="off">
                                        </div>
                                        <div class="cv-pago-monto-col">
                                            <label for="cvPagoMontoNuevo">Monto</label>
                                            <input type="number" min="0.01" step="0.01" class="form-control input-sm" id="cvPagoMontoNuevo" value="0.01">
                                        </div>
                                    </div>
                                    <p class="cv-ope-estado" id="cvOpeEstado"></p>
                                    <div class="cv-ope-lista" id="cvOpeLista"></div>
                                    <div class="cv-pago-form-acciones">
                                        <button type="button" class="btn btn-primary btn-sm" id="cvBtnAgregarPago">
                                            <i class="fa fa-plus"></i> Agregar
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm" id="cvBtnOrganizar" title="Busca documentos que sumen cada pago">
                                            Organizar
                                        </button>
                                    </div>
                                </div>
                                <div class="cv-grupos" id="cvGrupos"></div>
                                <table class="table table-condensed" id="cvTablaPagos">
                                    <thead>
                                        <tr>
                                            <th>Medio</th>
                                            <th class="text-right">Monto</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cv-pagos-vacio">
                                            <td colspan="3" class="text-muted text-center">Sin pagos</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="cv-pagos-resumen">
                                    <div>Docs: <strong id="cvPagoDocs">0.00</strong></div>
                                    <div>Pagos: <strong id="cvPagoSum">0.00</strong></div>
                                    <div>Diferencia: <strong id="cvPagoDif">0.00</strong></div>
                                </div>
                                <button type="button" class="btn btn-warning btn-block" id="cvBtnRegistrar" disabled>
                                    <i class="fa fa-check"></i> Registrar
                                </button>
                            </aside>
                        </div>
                    </div>

                    <div class="tab-pane" id="cvTabValidar">
                        <div class="cv-validar" id="cvBoxValidar">
                            <p class="text-muted cv-pagos-ayuda">
                                Pulsa <strong>+</strong> para ver el detalle.
                                <strong>Confirmar</strong> deja el cuadre listo (aún no entra a cuentas).
                                Quien lo armó, en producción, no confirma el suyo.
                                Abajo queda el historial del día con su estado.
                            </p>
                            <div class="cv-validar-acciones-top">
                                <button type="button" class="btn btn-success btn-sm" id="cvBtnExcelValidar" title="Bajar Excel del día">
                                    <i class="fa fa-file-excel-o"></i> Excel
                                </button>
                            </div>
                            <div class="cv-validar-cuerpo">
                            <div class="cv-tabla-wrap cv-validar-tabla-wrap">
                                <table class="table table-bordered table-condensed" id="cvTablaValidar" style="width:100%">
                                    <colgroup>
                                        <col style="width:40px">
                                        <col style="width:22%">
                                        <col style="width:10%">
                                        <col style="width:6%">
                                        <col style="width:9%">
                                        <col style="width:20%">
                                        <col style="width:12%">
                                        <col style="width:14%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="cv-col-toggle"></th>
                                            <th>Cliente</th>
                                            <th>Registró</th>
                                            <th class="text-right">Docs</th>
                                            <th class="text-right">Total</th>
                                            <th>Pagos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cv-vacio">
                                            <td colspan="8" class="text-muted text-center">No hay cuadres por validar.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <aside class="cv-validar-sumas" id="cvValidarSumas">
                                <h4>Suma del día</h4>
                                <p class="text-muted cv-pagos-ayuda">Lo que hay por validar, por medio.</p>
                                <div id="cvSumasMedios"></div>
                                <div class="cv-suma-fila cv-suma-total">
                                    <span>Total</span>
                                    <strong id="cvSumTotal">0.00</strong>
                                </div>
                            </aside>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="cvTabProcesar">
                        <div class="cv-validar" id="cvBoxProcesar">
                            <p class="text-muted cv-pagos-ayuda">
                                Cuadres ya confirmados. <strong>Procesar a cte</strong> baja el saldo en cuentas y consume la OP.
                                El historial del día queda con estado Procesado.
                            </p>
                            <div class="cv-validar-cuerpo">
                            <div class="cv-tabla-wrap cv-validar-tabla-wrap">
                                <table class="table table-bordered table-condensed" id="cvTablaProcesar" style="width:100%">
                                    <colgroup>
                                        <col style="width:40px">
                                        <col style="width:22%">
                                        <col style="width:10%">
                                        <col style="width:6%">
                                        <col style="width:9%">
                                        <col style="width:20%">
                                        <col style="width:12%">
                                        <col style="width:14%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="cv-col-toggle"></th>
                                            <th>Cliente</th>
                                            <th>Registró</th>
                                            <th class="text-right">Docs</th>
                                            <th class="text-right">Total</th>
                                            <th>Pagos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cv-vacio">
                                            <td colspan="8" class="text-muted text-center">No hay cuadres por procesar.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <aside class="cv-validar-sumas" id="cvProcesarSumas">
                                <h4>Listos para cte</h4>
                                <p class="text-muted cv-pagos-ayuda">Confirmados, listos para entrar a cuentas.</p>
                                <div id="cvSumasProcesar"></div>
                                <div class="cv-suma-fila cv-suma-total">
                                    <span>Total</span>
                                    <strong id="cvSumProcesarTotal">0.00</strong>
                                </div>
                            </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
