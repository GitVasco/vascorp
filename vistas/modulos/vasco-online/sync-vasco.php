<div class="content-wrapper" id="panelVascoOnlineSync">

    <section class="content-header">

        <h1>
            Vasco Online
            <small>Sincronización vascorp → internet</small>
        </h1>

        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Vasco Online</li>
        </ol>

    </section>

    <section class="content">

        <?php
        $configVascoOnline = function_exists("obtenerConfigVascoOnline") ? obtenerConfigVascoOnline() : array(
            "base_url" => "",
            "api_key" => "",
            "timeout" => 120,
            "max_por_lote" => 500,
            "endpoint_clientes" => "/v2/sync/customers-bulk",
        );
        $urlSyncClientes = function_exists("obtenerUrlSyncClientesVasco")
            ? obtenerUrlSyncClientesVasco()
            : "";
        $urlSyncCuentas = function_exists("obtenerUrlSyncCuentasVasco")
            ? obtenerUrlSyncCuentasVasco()
            : "";
        $urlHealthVasco = function_exists("obtenerUrlHealthVasco")
            ? obtenerUrlHealthVasco()
            : "";
        $apiKeyEnmascarada = function_exists("vascoOnlineApiKeyEnmascarada")
            ? vascoOnlineApiKeyEnmascarada()
            : "";
        ?>

        <p class="vasco-sync-intro">
            En Vasco Online lo que importa es el <strong>documento</strong> (RUC/DNI): ahí se agrupa la deuda y la cobranza.
            Varios códigos en vascorp con el mismo documento se <strong>consolidan en uno</strong> al enviar.
        </p>

        <!-- Conexión compacta -->
        <div class="box box-default vasco-conn-compact">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <small class="text-muted">API base</small>
                        <div><strong><?php echo htmlspecialchars($configVascoOnline["base_url"], ENT_QUOTES, "UTF-8"); ?></strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">API Key</small>
                        <div><code><?php echo htmlspecialchars($apiKeyEnmascarada, ENT_QUOTES, "UTF-8"); ?></code></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Estado</small>
                        <div><span class="label label-default" id="badgeConexionVasco"><i class="fa fa-circle"></i> No verificado</span></div>
                    </div>
                    <div class="col-sm-2 text-right">
                        <button type="button" class="btn btn-default btn-sm" id="btnProbarConexionVasco" style="margin-top:10px;">
                            <i class="fa fa-refresh"></i> Probar
                        </button>
                    </div>
                </div>
                <p class="vasco-conn-meta">
                    Health: <code><?php echo htmlspecialchars($urlHealthVasco, ENT_QUOTES, "UTF-8"); ?></code>
                    · Sync clientes: <code><?php echo htmlspecialchars($urlSyncClientes, ENT_QUOTES, "UTF-8"); ?></code>
                    <?php if ($urlSyncCuentas !== "") { ?>
                    · Sync cuentas: <code><?php echo htmlspecialchars($urlSyncCuentas, ENT_QUOTES, "UTF-8"); ?></code>
                    <?php } ?>
                    · Timeout <?php echo (int) $configVascoOnline["timeout"]; ?> s
                    · API key en <code>controladores/config.php</code>
                    · URLs en <code>controladores/vasco-online.config.php</code>
                </p>
            </div>
        </div>

        <!-- Módulos -->
        <div class="nav-tabs-custom vasco-module-tabs">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#tab-clientes" data-toggle="tab"><i class="fa fa-users"></i> Clientes</a>
                </li>
                <li>
                    <a href="#tab-cuentas" data-toggle="tab"><i class="fa fa-file-text-o"></i> Cuentas</a>
                </li>
                <li class="disabled">
                    <a href="#" class="vasco-futuro-tab" onclick="return false;">
                        <i class="fa fa-cube"></i> Productos
                    </a>
                </li>
                <li class="disabled">
                    <a href="#" class="vasco-futuro-tab" onclick="return false;">
                        <i class="fa fa-id-badge"></i> Vendedores
                    </a>
                </li>
                <li class="disabled">
                    <a href="#" class="vasco-futuro-tab" onclick="return false;">
                        <i class="fa fa-shopping-cart"></i> Pedidos
                    </a>
                </li>
            </ul>

            <div class="tab-content">

                <div class="tab-pane active" id="tab-clientes">

                    <div class="row">

                        <!-- Columna izquierda: acción + resumen -->
                        <div class="col-md-4">

                            <div class="box box-primary vasco-panel-accion">
                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        <span class="vasco-step-badge">1</span> Analizar
                                    </h3>
                                    <div class="box-tools pull-right">
                                        <span class="label label-default" id="badgeEstadoSync">
                                            <i class="fa fa-circle-o"></i> Sin analizar
                                        </span>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <p class="text-muted" style="margin-top:0;">
                                        Detecta duplicados por documento y datos que bloquean el envío.
                                    </p>
                                    <button type="button" class="btn btn-primary btn-block btn-lg" id="btnAnalizarClientesVasco">
                                        <i class="fa fa-search"></i> Analizar clientes
                                    </button>
                                </div>
                            </div>

                            <div class="box box-default">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Resumen</h3>
                                </div>
                                <div class="box-body" style="padding-top:8px;">
                                    <ul class="vasco-resumen-list">
                                        <li>
                                            <span>Total vascorp</span>
                                            <span class="valor" id="statTotalClientes">—</span>
                                        </li>
                                        <li>
                                            <span>Activos</span>
                                            <span class="valor" id="statActivos">—</span>
                                        </li>
                                        <li>
                                            <span>Documentos a enviar</span>
                                            <span class="valor ok" id="statListosSync">—</span>
                                        </li>
                                        <li>
                                            <span>Lotes estimados</span>
                                            <span class="valor" id="statLotesEstimados">—</span>
                                        </li>
                                        <li>
                                            <span>Máx. por lote</span>
                                            <span class="valor" id="statMaxLote"><?php echo (int) $configVascoOnline["max_por_lote"]; ?></span>
                                        </li>
                                        <li>
                                            <span>Docs. con varios códigos</span>
                                            <span class="valor warn" id="statGruposDuplicados">—</span>
                                        </li>
                                        <li>
                                            <span>Códigos extras (no se envían)</span>
                                            <span class="valor" id="statRegDuplicados">—</span>
                                        </li>
                                        <li>
                                            <span>Sin documento</span>
                                            <span class="valor warn" id="statSinDocumento">—</span>
                                        </li>
                                        <li>
                                            <span>Tipo doc. inválido</span>
                                            <span class="valor warn" id="statTipoInvalido">—</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="box box-success">
                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        <span class="vasco-step-badge muted">2</span> Enviar
                                    </h3>
                                </div>
                                <div class="box-body">
                                    <div class="progress progress-xs active" style="margin-bottom:8px;">
                                        <div class="progress-bar progress-bar-success" id="barraProgresoSync" style="width: 0%"></div>
                                    </div>
                                    <p class="text-muted" id="textoProgresoSync" style="margin-bottom:12px;">
                                        Analiza primero para calcular lotes
                                    </p>
                                    <button type="button" class="btn btn-success btn-block" id="btnSincronizarClientesVasco" disabled>
                                        <i class="fa fa-play"></i> Sincronizar
                                    </button>
                                    <button type="button" class="btn btn-default btn-block" id="btnReintentarFallidosVasco" disabled style="margin-top:6px;">
                                        <i class="fa fa-repeat"></i> Reintentar fallidos
                                    </button>
                                    <button type="button" class="btn btn-warning btn-block" id="btnDescargarRechazadosVasco" disabled style="margin-top:6px;">
                                        <i class="fa fa-download"></i> Descargar rechazados (CSV)
                                    </button>
                                    <p class="text-muted" style="margin:12px 0 0;font-size:12px;">
                                        Envía <strong>activos e inactivos</strong> con documento válido.
                                        Sin vendedor ni grupo por ahora.
                                        <code>external_id</code> = <code>clientesjf.id</code>
                                    </p>
                                </div>
                            </div>

                            <div class="box box-default vasco-guia-sync">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-question-circle"></i> ¿Qué pasa con los bloqueados?</h3>
                                </div>
                                <div class="box-body">

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-copy text-aqua"></i> Varios códigos, mismo documento</h5>
                                        <p>
                                            En vascorp puede haber varios clientes (códigos distintos) con el mismo RUC/DNI.
                                            <strong>No hace falta corregirlos todos</strong> para sincronizar.
                                        </p>
                                        <ul>
                                            <li>Lo que importa en internet es el <strong>documento</strong>, no el código vascorp.</li>
                                            <li>Al enviar, mandamos <strong>1 registro por documento</strong> (el sugerido: activo y más reciente).</li>
                                            <li>Los demás códigos del mismo doc <strong>no se envían por separado</strong>; en vascorp pueden seguir existiendo.</li>
                                            <li>La finalidad es ver en Vasco Online la deuda y datos por <strong>número de documento</strong>.</li>
                                        </ul>
                                    </div>

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-ban text-red"></i> Bloqueados de verdad (no se envían)</h5>
                                        <p>
                                            Clientes sin número de documento o con tipo inválido.
                                            Vasco exige <code>doc_number</code> y <code>doc_type</code> SUNAT-06.
                                        </p>
                                    </div>

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-file-text-o text-yellow"></i> Tipo de documento inválido</h5>
                                        <p>
                                            El tipo no está en el catálogo SUNAT-06 que acepta Vasco:
                                            <code>1</code> DNI, <code>4</code> CE, <code>6</code> RUC, <code>7</code> pasaporte,
                                            <code>0</code>, <code>A</code>, <code>B</code>.
                                            Hay que corregir el tipo en vascorp antes de sincronizar.
                                        </p>
                                    </div>

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-check-circle text-green"></i> Listos para enviar</h5>
                                        <p>
                                            Cuenta de <strong>documentos únicos</strong> con tipo y número válidos
                                            (<strong>activos e inactivos</strong>; inactivos van con <code>state: 2</code>).
                                            Si reenvías el mismo <code>id</code>, Vasco actualiza (no duplica).
                                        </p>
                                    </div>

                                    <div class="vasco-guia-item vasco-guia-resumen">
                                        <p class="text-muted" style="margin:0;">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>Resumen:</strong> mismo doc con varios códigos → se consolida al enviar.
                                            Sin documento o tipo inválido → sí bloquea el envío de ese cliente.
                                        </p>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <!-- Columna derecha: resultados -->
                        <div class="col-md-8">

                            <div class="box box-default vasco-resultados-panel">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-list-alt"></i> Resultados de auditoría</h3>
                                </div>
                                <div class="box-body">

                                    <div class="nav-tabs-custom vasco-resultados-tabs">
                                        <ul class="nav nav-tabs">
                                            <li class="active">
                                                <a href="#res-duplicados" data-toggle="tab">
                                                    Duplicados
                                                    <span class="badge bg-red" id="badgeCountDuplicados">0</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#res-advertencias" data-toggle="tab">
                                                    Bloqueos
                                                    <span class="badge bg-yellow" id="badgeCountAdvertencias">0</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content vasco-resultados-scroll-wrap">
                                            <div class="tab-pane active" id="res-duplicados">
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-condensed" id="tablaDuplicadosVasco" style="margin-bottom:0;">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:36px;"></th>
                                                                <th>Tipo</th>
                                                                <th>Documento</th>
                                                                <th style="width:60px;">Cant.</th>
                                                                <th>Sugerencia</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="5">
                                                                    <div class="vasco-empty-state">
                                                                        <i class="fa fa-search"></i>
                                                                        Pulsa <strong>Analizar clientes</strong> para ver duplicados por documento.
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane" id="res-advertencias">
                                                <div id="contenidoAdvertenciasVasco">
                                                    <div class="vasco-empty-state">
                                                        <i class="fa fa-check-circle"></i>
                                                        Sin bloqueos detectados. Ejecuta el análisis para validar.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- ========== PESTAÑA CUENTAS (estado de cuenta) ========== -->
                <div class="tab-pane" id="tab-cuentas">

                    <div class="row">

                        <div class="col-md-4">

                            <div class="box box-primary vasco-panel-accion">
                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        <span class="vasco-step-badge">1</span> Analizar
                                    </h3>
                                    <div class="box-tools pull-right">
                                        <span class="label label-default" id="badgeEstadoSyncCuentas">
                                            <i class="fa fa-circle-o"></i> Sin analizar
                                        </span>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <p class="text-muted" style="margin-top:0;">
                                        Agrupa por documento del cliente y calcula deuda desde
                                        <code>cuenta_ctejf</code> (solo <strong>PENDIENTE</strong>, saldo &gt; 0).
                                    </p>
                                    <button type="button" class="btn btn-primary btn-block btn-lg" id="btnAnalizarCuentasVasco">
                                        <i class="fa fa-search"></i> Analizar cuentas
                                    </button>
                                    <p class="text-muted vasco-box-footnote">
                                        Requiere maestro de <strong>clientes</strong> sync en Vasco.
                                        Llave: <code>doc_type</code> + <code>doc_number</code>.
                                    </p>
                                </div>
                            </div>

                            <div class="box box-default">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Resumen</h3>
                                </div>
                                <div class="box-body" style="padding-top:8px;">
                                    <ul class="vasco-resumen-list">
                                        <li>
                                            <span>Clientes con deuda</span>
                                            <span class="valor ok" id="statCuentasConDeuda">—</span>
                                        </li>
                                        <li>
                                            <span>Docs. pendientes total</span>
                                            <span class="valor" id="statCuentasDocsPendientes">—</span>
                                        </li>
                                        <li>
                                            <span>Deuda total S/</span>
                                            <span class="valor" id="statCuentasDeudaTotal">—</span>
                                        </li>
                                        <li>
                                            <span>Vencido total S/</span>
                                            <span class="valor warn" id="statCuentasVencidoTotal">—</span>
                                        </li>
                                        <li>
                                            <span>Lotes estimados</span>
                                            <span class="valor" id="statCuentasLotes">—</span>
                                        </li>
                                        <li>
                                            <span>Máx. por lote</span>
                                            <span class="valor" id="statCuentasMaxLote"><?php echo (int) $configVascoOnline["max_por_lote"]; ?></span>
                                        </li>
                                        <li>
                                            <span>Sin documento cliente</span>
                                            <span class="valor warn" id="statCuentasSinDoc">—</span>
                                        </li>
                                        <li>
                                            <span>Varios códigos / mismo doc</span>
                                            <span class="valor" id="statCuentasConsolidados">—</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="box box-success">
                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        <span class="vasco-step-badge muted">2</span> Enviar
                                    </h3>
                                </div>
                                <div class="box-body">
                                    <div class="progress progress-xs active" style="margin-bottom:8px;">
                                        <div class="progress-bar progress-bar-success" id="barraProgresoSyncCuentas" style="width: 0%"></div>
                                    </div>
                                    <p class="text-muted" id="textoProgresoSyncCuentas" style="margin-bottom:12px;">
                                        Analiza primero para calcular lotes
                                    </p>
                                    <button type="button" class="btn btn-success btn-block" id="btnSincronizarCuentasVasco" disabled>
                                        <i class="fa fa-play"></i> Sincronizar cuentas
                                    </button>
                                    <button type="button" class="btn btn-default btn-block" id="btnReintentarFallidosCuentasVasco" disabled style="margin-top:6px;">
                                        <i class="fa fa-repeat"></i> Reintentar fallidos
                                    </button>
                                    <button type="button" class="btn btn-warning btn-block" id="btnDescargarRechazadosCuentasVasco" disabled style="margin-top:6px;">
                                        <i class="fa fa-download"></i> Descargar rechazados (CSV)
                                    </button>
                                    <p class="text-muted vasco-box-footnote">
                                        Solo clientes con deuda · máx. <?php echo (int) $configVascoOnline["max_por_lote"]; ?> clientes/lote
                                        · al terminar, <code>finalize</code> purga quien ya pagó en Vasco.
                                    </p>
                                </div>
                            </div>

                            <div class="box box-default vasco-guia-sync">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-question-circle"></i> Reglas de extracción</h3>
                                </div>
                                <div class="box-body">

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-check text-green"></i> Qué se envía</h5>
                                        <ul>
                                            <li><code>deuda_total</code> y <code>vencido_total</code> (fecha vencida &lt; hoy)</li>
                                            <li><code>pending_documents</code> — <strong>PENDIENTE</strong>, <code>tip_mov = '+'</code>, saldo &gt; 0</li>
                                            <li>1 registro por documento del cliente (consolida varios códigos vascorp)</li>
                                        </ul>
                                    </div>

                                    <div class="vasco-guia-item">
                                        <h5><i class="fa fa-ban text-red"></i> Qué no va en los lotes</h5>
                                        <ul>
                                            <li>Clientes sin deuda (se purgan al <code>finalize</code>)</li>
                                            <li>Cancelados, saldo 0, pagos (<code>tip_mov = '-'</code>)</li>
                                        </ul>
                                    </div>

                                    <div class="vasco-guia-item vasco-guia-resumen">
                                        <p class="text-muted" style="margin:0;">
                                            <i class="fa fa-external-link"></i>
                                            Ver en Vasco: <strong>Operación → Estados de cuenta</strong>.
                                            Reenvío idempotente: Vasco reemplaza el snapshot del cliente.
                                        </p>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="col-md-8">

                            <div class="box box-default vasco-resultados-panel">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-list-alt"></i> Vista previa de auditoría</h3>
                                </div>
                                <div class="box-body">

                                    <div class="nav-tabs-custom vasco-resultados-tabs">
                                        <ul class="nav nav-tabs">
                                            <li class="active">
                                                <a href="#res-cuentas-muestra" data-toggle="tab">
                                                    Muestra
                                                    <span class="badge bg-blue" id="badgeCountCuentasMuestra">0</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#res-cuentas-bloqueos" data-toggle="tab">
                                                    Bloqueos
                                                    <span class="badge bg-yellow" id="badgeCountCuentasBloqueos">0</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#res-cuentas-payload" data-toggle="tab">
                                                    Ejemplo JSON
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content vasco-resultados-scroll-wrap">
                                            <div class="tab-pane active" id="res-cuentas-muestra">
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-condensed" id="tablaMuestraCuentasVasco" style="margin-bottom:0;">
                                                        <thead>
                                                            <tr>
                                                                <th>Doc. cliente</th>
                                                                <th>Nombre</th>
                                                                <th class="text-right">Deuda</th>
                                                                <th class="text-right">Vencido</th>
                                                                <th class="text-center">Docs</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="5">
                                                                    <div class="vasco-empty-state">
                                                                        <i class="fa fa-search"></i>
                                                                        Aquí verás una muestra de clientes con deuda consolidada por documento.
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane" id="res-cuentas-bloqueos">
                                                <div id="contenidoBloqueosCuentasVasco">
                                                    <div class="vasco-empty-state">
                                                        <i class="fa fa-check-circle"></i>
                                                        Clientes con cuenta pero sin documento válido para Vasco.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane" id="res-cuentas-payload">
                                                <div class="vasco-payload-preview">
                                                    <p class="text-muted" style="padding:12px 15px 0;margin:0;font-size:12px;">
                                                        Estructura esperada por el API (referencia):
                                                    </p>
                                                    <pre class="vasco-json-sample">{
  "trace_id": "vascorp-ec-YYYYMMDD-001",
  "batch": 1,
  "accounts": [
    {
      "doc_type": "6",
      "doc_number": "20123456789",
      "deuda_total": 405881.63,
      "vencido_total": 137446.65,
      "pending_documents": [
        {
          "doc_type": "09",
          "doc_number": "0030019723",
          "issue_date": "2026-02-09",
          "due_date": "2026-05-10",
          "amount": 40145.39,
          "balance": 40145.39
        }
      ]
    }
  ]
}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>

        <!-- Log colapsable -->
        <div class="box box-default collapsed-box vasco-log-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-terminal"></i> Log</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <pre id="vascoSyncLog" class="bg-black" style="color:#aaa;padding:12px;border-radius:4px;max-height:140px;overflow-y:auto;">[--:--:--] Panel Vasco Online listo
[--:--:--] Pestañas: Clientes (activo) · Cuentas (UI)</pre>
            </div>
        </div>

    </section>

</div>

<script>
window.document.title = "Vasco Online";
</script>
