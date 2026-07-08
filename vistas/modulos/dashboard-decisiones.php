<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$vendedorSeleccionado = ControladorDashboardDecisiones::ctrVendedorSeleccionado();
$vendedoresPermitidos = ControladorDashboardDecisiones::ctrVendedoresPermitidos();
$datos = ControladorDashboardDecisiones::ctrDatosDashboard();
$pedidos = $datos["pedidos"];
$cartera = $datos["cartera"];
$alertas = $datos["alertas"];
$topGenerados = $datos["top_generados"];
$generados = $datos["generados"];
$estancados = $datos["estancados"];
$atraso = $datos["atraso"];
$avanceVentas = $datos["avance_ventas"];

function ddAvancePctClase($pct)
{
    $pct = (float) $pct;

    if ($pct >= 100) {
        return "ok";
    }
    if ($pct >= 80) {
        return "warn";
    }

    return "bad";
}

function ddAvanceSegmentos($avance, $meta)
{
    $meta = (float) $meta;

    if ($meta <= 0) {
        return array();
    }

    return array(
        array("clase" => "real", "monto" => (float) $avance["venta_real"], "titulo" => "Venta facturada"),
        array("clase" => "generado", "monto" => (float) $avance["soles_generados"], "titulo" => "Generados"),
        array("clase" => "aprobado", "monto" => (float) $avance["soles_aprobados"], "titulo" => "Aprobados"),
        array("clase" => "apt", "monto" => (float) $avance["soles_apt"], "titulo" => "APT"),
        array("clase" => "confirmado", "monto" => (float) $avance["soles_confirmados"], "titulo" => "Confirmados"),
    );
}

function ddEstadoBadge($estado)
{
    $map = array(
        "GENERADO" => "label-default",
        "APROBADO" => "label-warning",
        "APT" => "label-primary",
        "CONFIRMADO" => "label-info",
        "ANULADO" => "label-danger",
    );

    $clase = isset($map[$estado]) ? $map[$estado] : "label-default";

    return '<span class="label ' . $clase . ' dd-estado-badge">' . htmlspecialchars($estado) . '</span>';
}

function ddFormatoMonto($lista, $monto)
{
    $simbolo = ($lista === "precio1") ? "$ " : "S/ ";
    return $simbolo . number_format((float) $monto, 2);
}

function ddNivelAtraso($dias)
{
    $dias = (int) $dias;

    if ($dias >= 60) {
        return array("critico", "Crítico");
    }
    if ($dias >= 30) {
        return array("alto", "Alto");
    }
    if ($dias >= 15) {
        return array("medio", "Medio");
    }

    return array("bajo", "Bajo");
}

function ddClienteLinea($codigo, $nombre)
{
    $codigo = htmlspecialchars(trim((string) $codigo));
    $nombre = htmlspecialchars(trim((string) $nombre));

    if ($codigo === "" && $nombre === "") {
        return '<span class="text-muted">—</span>';
    }

    $html = '<span class="dd-cli-linea">';

    if ($codigo !== "") {
        $html .= '<span class="dd-cod-cli dd-cod-cli--inline">' . $codigo . "</span>";
    }

    if ($nombre !== "") {
        $html .= '<span class="dd-cli-nombre">' . $nombre . "</span>";
    }

    return $html . "</span>";
}

$estadosPipeline = array(
    array(
        "GENERADO",
        "Generados",
        "Pendientes de aprobación de crédito",
        (int) $pedidos["generados"],
        (float) $pedidos["soles_generados"],
        "#95a5a6",
    ),
    array(
        "APROBADO",
        "Aprobados",
        "Ya aprobados por créditos",
        (int) $pedidos["aprobados"],
        (float) $pedidos["soles_aprobados"],
        "#f39c12",
    ),
    array(
        "APT",
        "APT",
        "En almacén siendo preparados",
        (int) $pedidos["apt"],
        (float) $pedidos["soles_apt"],
        "#3c8dbc",
    ),
    array(
        "CONFIRMADO",
        "Confirmados",
        "Listos para ser facturados",
        (int) $pedidos["confirmados"],
        (float) $pedidos["soles_confirmados"],
        "#00c0ef",
    ),
);
?>

<div class="content-wrapper dd-dashboard">
    <section class="content-header dd-content-header">
        <div class="dd-header-row">
            <div class="dd-header-title">
                <h1>
                    Centro de Decisiones
                    <small>Mayoristas y distribuidores</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
                    <li class="active">Centro de Decisiones</li>
                </ol>
            </div>
            <form method="get" action="index.php" class="dd-filtro-form">
                <input type="hidden" name="ruta" value="dashboard-decisiones">
                <div class="dd-filtro-group">
                    <label for="ddFiltroVendedor" class="dd-filtro-label">
                        <i class="fa fa-user"></i> Vendedor
                    </label>
                    <select id="ddFiltroVendedor"
                            name="vendedor"
                            class="form-control selectpicker dd-filtro-select"
                            data-live-search="true"
                            data-size="8"
                            title="Todos los vendedores">
                        <option value="">Todos los vendedores</option>
                        <?php foreach ($vendedoresPermitidos as $vend) : ?>
                            <option value="<?php echo htmlspecialchars($vend["codigo"]); ?>"
                                <?php echo ($vendedorSeleccionado === (string) $vend["codigo"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($vend["codigo"] . " - " . $vend["descripcion"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm dd-filtro-btn">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>
                    <a href="index.php?ruta=dashboard-decisiones"
                       class="btn btn-default btn-sm dd-filtro-btn dd-filtro-btn--limpiar"
                       title="Quitar filtro de vendedor">
                        <i class="fa fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        <?php if ($vendedorSeleccionado !== "") : ?>
            <?php
            $nombreVendedorFiltro = $vendedorSeleccionado;
            foreach ($vendedoresPermitidos as $vend) {
                if ((string) $vend["codigo"] === $vendedorSeleccionado) {
                    $nombreVendedorFiltro = $vend["codigo"] . " · " . $vend["descripcion"];
                    break;
                }
            }
            ?>
            <div class="dd-filtro-activo">
                <i class="fa fa-filter"></i> Mostrando datos de:
                <strong><?php echo htmlspecialchars($nombreVendedorFiltro); ?></strong>
                <a href="index.php?ruta=dashboard-decisiones" class="dd-filtro-limpiar">Ver todos</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="content dd-content-compact">
        <div class="dd-decision-bar">
            <a href="#ddSeccionGenerados" class="dd-decision-card dd-decision-card--primary">
                <div class="dd-decision-card-label"><i class="fa fa-gavel"></i> Por aprobar hoy</div>
                <div class="dd-decision-card-value">S/ <?php echo number_format((float) $alertas["generados_soles"], 0); ?></div>
                <div class="dd-decision-card-meta"><?php echo (int) $alertas["generados_total"]; ?> pedidos esperando crédito</div>
            </a>
            <div class="dd-decision-card dd-decision-card--danger <?php echo ((int) $alertas["generados_mora"] > 0) ? "dd-decision-card--pulse" : ""; ?>">
                <div class="dd-decision-card-label"><i class="fa fa-exclamation-circle"></i> Cliente con deuda vencida</div>
                <div class="dd-decision-card-value">S/ <?php echo number_format((float) $alertas["generados_mora_soles"], 0); ?></div>
                <div class="dd-decision-card-meta"><?php echo (int) $alertas["generados_mora"]; ?> ped. en riesgo</div>
            </div>
            <div class="dd-decision-card dd-decision-card--warn">
                <div class="dd-decision-card-label"><i class="fa fa-clock-o"></i> Esperando +2 días</div>
                <div class="dd-decision-card-value">S/ <?php echo number_format((float) $alertas["generados_antiguos_soles"], 0); ?></div>
                <div class="dd-decision-card-meta"><?php echo (int) $alertas["generados_antiguos"]; ?> ped.</div>
            </div>
            <div class="dd-decision-card dd-decision-card--neutral">
                <div class="dd-decision-card-label"><i class="fa fa-hourglass-half"></i> Estancados +3 días</div>
                <div class="dd-decision-card-value">S/ <?php echo number_format((float) $pedidos["soles_estancados"], 0); ?></div>
                <div class="dd-decision-card-meta"><?php echo (int) $pedidos["estancados_3d"]; ?> ped. · post-aprobación</div>
            </div>
            <div class="dd-decision-card dd-decision-card--cartera">
                <div class="dd-decision-card-label"><i class="fa fa-money"></i> Cartera vencida</div>
                <div class="dd-decision-card-value">S/ <?php echo number_format((float) $cartera["deuda_vencida"], 0); ?></div>
                <div class="dd-decision-card-meta"><?php echo (int) $cartera["clientes_vencidos"]; ?> clientes</div>
            </div>
        </div>

        <?php
        $mesesDd = ControladorTalleres::ctrMes();
        $nombreMesAvance = (string) $avanceVentas["mes"];
        foreach ($mesesDd as $mesItem) {
            if ((int) $mesItem["codigo"] === (int) $avanceVentas["mes"]) {
                $nombreMesAvance = $mesItem["descripcion"];
                break;
            }
        }
        ?>
        <div class="box box-solid dd-box dd-avance-ventas-box">
            <div class="box-header with-border dd-box-header-compact">
                <h3 class="box-title">
                    <i class="fa fa-bullseye"></i>
                    Avance de ventas — <?php echo htmlspecialchars($nombreMesAvance . " " . $avanceVentas["anio"]); ?>
                </h3>
                <div class="dd-header-tools pull-right">
                    <span class="dd-resumen-chip">
                        Real S/ <?php echo number_format((float) $avanceVentas["total_venta"], 0); ?>
                        (<?php echo number_format((float) $avanceVentas["pct_global"], 1); ?>%)
                    </span>
                    <span class="dd-resumen-chip dd-resumen-chip--total">
                        Proy. S/ <?php echo number_format((float) $avanceVentas["total_proyectado"], 0); ?>
                        / <?php echo number_format((float) $avanceVentas["total_meta"], 0); ?>
                        (<?php echo number_format((float) $avanceVentas["pct_proyectado"], 1); ?>%)
                    </span>
                    <a href="index.php?ruta=metas-vendedor&anio=<?php echo (int) $avanceVentas["anio"]; ?>&mes=<?php echo (int) $avanceVentas["mes"]; ?>"
                       class="btn btn-xs btn-default"
                       title="Gestionar metas">
                        <i class="fa fa-pencil"></i> Metas
                    </a>
                </div>
            </div>
            <div class="box-body dd-box-body-compact">
                <?php if (empty($avanceVentas["filas"])) : ?>
                    <p class="text-muted text-center dd-avance-empty">
                        No hay metas de venta registradas para este mes.
                        <a href="index.php?ruta=metas-vendedor">Registrar metas</a>
                    </p>
                <?php else : ?>
                    <div class="table-responsive dd-table-wrap dd-table-wrap--avance">
                        <table class="table table-hover table-condensed dd-table dd-table-compact dd-avance-table">
                            <thead>
                                <tr>
                                    <th class="dd-avance-col-vend">Vendedor</th>
                                    <th class="dd-avance-col-bar">Avance / proyección</th>
                                    <th class="text-right dd-avance-col-monto">Facturado</th>
                                    <th class="text-right dd-avance-col-monto">Pipeline</th>
                                    <th class="text-right dd-avance-col-monto">Meta</th>
                                    <th class="text-right dd-avance-col-faltante">Faltante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avanceVentas["filas"] as $avance) :
                                    $meta = (float) $avance["meta_venta"];
                                    $real = (float) $avance["venta_real"];
                                    $pipeline = (float) $avance["soles_generados"]
                                        + (float) $avance["soles_aprobados"]
                                        + (float) $avance["soles_apt"]
                                        + (float) $avance["soles_confirmados"];
                                    $proyectado = $real + $pipeline;
                                    $pctProy = ($meta > 0) ? round(($proyectado / $meta) * 100, 1) : 0;
                                    $clase = ddAvancePctClase($pctProy);
                                    $faltante = max(0, $meta - $proyectado);
                                    $segmentos = ddAvanceSegmentos($avance, $meta);
                                    $totalPctBar = 0.0;
                                    foreach ($segmentos as $seg) {
                                        if ($seg["monto"] > 0) {
                                            $totalPctBar += ($seg["monto"] / $meta) * 100;
                                        }
                                    }
                                    $escalaBar = ($totalPctBar > 100) ? (100 / $totalPctBar) : 1;
                                    ?>
                                    <tr>
                                        <td class="dd-avance-col-vend">
                                            <span class="dd-cod-cli"><?php echo htmlspecialchars($avance["cod_vendedor"]); ?></span>
                                            <?php echo htmlspecialchars($avance["nombre_vendedor"]); ?>
                                        </td>
                                        <td class="dd-avance-col-bar">
                                            <div class="dd-avance-bar-row">
                                                <div class="dd-avance-bar<?php echo ($totalPctBar > 100) ? " dd-avance-bar--overflow" : ""; ?>">
                                                    <?php foreach ($segmentos as $seg) :
                                                        if ($seg["monto"] <= 0) {
                                                            continue;
                                                        }
                                                        $anchoSeg = round((($seg["monto"] / $meta) * 100) * $escalaBar, 2);
                                                        ?>
                                                        <div class="dd-avance-seg dd-avance-seg--<?php echo $seg["clase"]; ?>"
                                                             style="width:<?php echo $anchoSeg; ?>%"
                                                             title="<?php echo htmlspecialchars($seg["titulo"] . ": S/ " . number_format($seg["monto"], 0)); ?>"></div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <span class="dd-avance-pct dd-avance-pct--<?php echo $clase; ?>" title="Proyección sobre meta">
                                                    <?php echo number_format($pctProy, 1); ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-right dd-avance-col-monto">
                                            S/ <?php echo number_format($real, 0); ?>
                                        </td>
                                        <td class="text-right dd-avance-col-monto">
                                            S/ <?php echo number_format($pipeline, 0); ?>
                                        </td>
                                        <td class="text-right dd-avance-col-monto text-muted">
                                            S/ <?php echo number_format($meta, 0); ?>
                                        </td>
                                        <td class="text-right dd-avance-col-faltante">
                                            <?php if ($faltante > 0) : ?>
                                                <span class="dd-avance-faltante">S/ <?php echo number_format($faltante, 0); ?></span>
                                            <?php else : ?>
                                                <span class="dd-avance-faltante dd-avance-faltante--ok"><i class="fa fa-check"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="dd-avance-leyenda">
                        <span class="dd-avance-leyenda-item"><i class="dd-avance-leyenda-dot dd-avance-seg--real"></i> Facturado</span>
                        <span class="dd-avance-leyenda-item"><i class="dd-avance-leyenda-dot dd-avance-seg--generado"></i> Generado</span>
                        <span class="dd-avance-leyenda-item"><i class="dd-avance-leyenda-dot dd-avance-seg--aprobado"></i> Aprobado</span>
                        <span class="dd-avance-leyenda-item"><i class="dd-avance-leyenda-dot dd-avance-seg--apt"></i> APT</span>
                        <span class="dd-avance-leyenda-item"><i class="dd-avance-leyenda-dot dd-avance-seg--confirmado"></i> Confirmado</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row dd-main-split">
            <div class="col-md-8 dd-main-left">
                <div class="box box-solid dd-box">
                    <div class="box-header with-border dd-box-header-compact">
                        <h3 class="box-title"><i class="fa fa-tasks"></i> Pipeline pre-factura (S/)</h3>
                        <div class="dd-header-tools pull-right">
                            <span class="dd-resumen-chip dd-resumen-chip--total">Total S/ <?php echo number_format((float) $pedidos["soles_pipeline"], 0); ?></span>
                            <a href="pedidoscv-vendedores" class="btn btn-xs btn-default" title="Ver pedidos"><i class="fa fa-list"></i></a>
                            <a href="index.php?ruta=inteligencia-comercial" class="btn btn-xs btn-primary" title="Inteligencia Comercial"><i class="fa fa-line-chart"></i></a>
                        </div>
                    </div>
                    <div class="box-body dd-box-body-compact">
                        <div class="row dd-estados-soles-grid">
                            <?php foreach ($estadosPipeline as $estadoItem) : ?>
                                <div class="col-sm-3 col-xs-6">
                                    <div class="dd-estado-soles-item">
                                        <div class="dd-estado-soles-top">
                                            <span class="dd-pipeline-dot" style="background:<?php echo $estadoItem[5]; ?>"></span>
                                            <div class="dd-estado-soles-text">
                                                <strong><?php echo $estadoItem[1]; ?></strong>
                                                <small><?php echo $estadoItem[3]; ?> ped.</small>
                                            </div>
                                        </div>
                                        <div class="dd-estado-soles-monto">S/ <?php echo number_format($estadoItem[4], 0); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="box box-solid dd-box dd-generados-box" id="ddSeccionGenerados">
                    <div class="box-header with-border dd-box-header-compact">
                        <h3 class="box-title">
                            <i class="fa fa-flag"></i> Pendientes de aprobación de crédito
                        </h3>
                        <span class="label label-warning pull-right"><?php echo count($generados); ?></span>
                    </div>
                    <div class="box-body table-responsive dd-table-wrap dd-table-wrap--generados dd-box-body-compact">
                        <table class="table table-hover table-condensed dd-table dd-table-compact">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th class="dd-col-cliente">Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Condición</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                    <th>Días</th>
                                    <th class="text-center" width="120px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($generados)) : ?>
                                    <tr><td colspan="8" class="text-center text-muted">No hay pedidos GENERADOS pendientes de aprobación.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($generados as $row) : ?>
                                        <tr class="<?php echo ((int) $row["cliente_en_mora"] === 1) ? "dd-row-mora" : ""; ?>">
                                            <td>
                                                <?php if ((int) $row["cliente_en_mora"] === 1) : ?>
                                                    <i class="fa fa-warning text-danger" title="Cliente con deuda vencida"></i>
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($row["codigo"]); ?></strong>
                                                <?php if (!empty($row["decision_credito"])) : ?>
                                                    <span class="label label-danger dd-motivo-badge"
                                                          title="<?php echo htmlspecialchars($row["decision_credito"]["motivo_etiqueta"]); ?>">
                                                        <i class="fa fa-ban"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="dd-col-cliente">
                                                <div class="dd-cell-main dd-cell-cliente" title="<?php echo htmlspecialchars($row["cod_cli"] . " · " . $row["cliente"]); ?>">
                                                    <?php echo ddClienteLinea($row["cod_cli"], $row["cliente"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dd-cell-main">
                                                    <span class="dd-cod-cli"><?php echo htmlspecialchars($row["vendedor"]); ?></span>
                                                    <?php echo htmlspecialchars($row["nom_vendedor"]); ?>
                                                </div>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($row["condicion"]); ?></small></td>
                                            <td><?php echo ddFormatoMonto($row["lista"], $row["total"]); ?></td>
                                            <td><?php echo htmlspecialchars($row["fecha"]); ?></td>
                                            <td>
                                                <span class="dd-dias dd-dias--<?php echo ((int) $row["dias_pendiente"] >= 2) ? "alto" : "medio"; ?>">
                                                    <?php echo (int) $row["dias_pendiente"]; ?>d
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($row["cod_cli"])) : ?>
                                                    <div class="dd-acciones-iconos">
                                                        <button type="button"
                                                            class="btn btn-xs btn-default btnDdMiniIc"
                                                            title="Ver inteligencia del cliente"
                                                            data-cliente="<?php echo htmlspecialchars($row["cod_cli"]); ?>"
                                                            data-pedido="<?php echo htmlspecialchars($row["codigo"]); ?>"
                                                            data-nombre="<?php echo htmlspecialchars($row["cliente"]); ?>">
                                                            <i class="fa fa-user-circle"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-xs <?php echo !empty($row["decision_credito"]) ? "btn-danger" : "btn-warning"; ?> btnDdDecisionCredito"
                                                            title="Decisión de crédito"
                                                            data-cliente="<?php echo htmlspecialchars($row["cod_cli"]); ?>"
                                                            data-pedido="<?php echo htmlspecialchars($row["codigo"]); ?>"
                                                            data-nombre="<?php echo htmlspecialchars($row["cliente"]); ?>">
                                                            <i class="fa fa-gavel"></i>
                                                        </button>
                                                        <a href="index.php?ruta=inteligencia-comercial&cliente=<?php echo urlencode($row["cod_cli"]); ?>"
                                                           class="btn btn-xs btn-default"
                                                           title="Abrir análisis completo"
                                                           target="_blank">
                                                            <i class="fa fa-line-chart"></i>
                                                        </a>
                                                    </div>
                                                <?php else : ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4 dd-main-right">
                <div class="box box-solid dd-box dd-box-prioridad dd-box-prioridad--tall">
                    <div class="box-header with-border dd-box-header-compact">
                        <h3 class="box-title"><i class="fa fa-bolt"></i> Prioridad de revisión</h3>
                        <small class="dd-prioridad-orden pull-right">Antiguo → nuevo</small>
                    </div>
                    <div class="box-body dd-box-body-compact dd-prioridad-list dd-prioridad-list--tall">
                        <?php if (empty($topGenerados)) : ?>
                            <p class="text-muted text-center" style="margin:0;">Sin pedidos pendientes.</p>
                        <?php else : ?>
                            <table class="table table-hover table-condensed dd-prioridad-table">
                                <thead>
                                    <tr>
                                        <th class="dd-prio-col-dias">Días</th>
                                        <th class="dd-col-cliente">Pedido / Cliente</th>
                                        <th class="text-right dd-prio-col-total">Pedido</th>
                                        <th class="text-right dd-prio-col-deuda">Deuda</th>
                                        <th class="dd-prio-col-acc"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topGenerados as $top) :
                                        $enMora = (float) $top["deuda_vencida_cliente"] > 0;
                                        $diasAlto = (int) $top["dias_pendiente"] >= 2;
                                        ?>
                                        <tr class="<?php echo $enMora ? "dd-row-mora" : ""; ?>">
                                            <td class="dd-prio-col-dias">
                                                <span class="dd-dias dd-dias--<?php echo $diasAlto ? "alto" : "medio"; ?>">
                                                    <?php echo (int) $top["dias_pendiente"]; ?>d
                                                </span>
                                            </td>
                                            <td class="dd-col-cliente">
                                                <div class="dd-prio-pedido">
                                                    <?php echo htmlspecialchars($top["codigo"]); ?>
                                                </div>
                                                <div class="dd-prio-cliente" title="<?php echo htmlspecialchars($top["cod_cli"] . " · " . $top["cliente"]); ?>">
                                                    <?php echo ddClienteLinea($top["cod_cli"], $top["cliente"]); ?>
                                                </div>
                                            </td>
                                            <td class="text-right dd-prio-col-total">
                                                <span class="dd-prio-monto"><?php echo ddFormatoMonto($top["lista"], $top["total"]); ?></span>
                                            </td>
                                            <td class="text-right dd-prio-col-deuda">
                                                <?php if ($enMora) : ?>
                                                    <span class="dd-prio-deuda dd-prio-deuda--vencida" title="Deuda vencida del cliente">
                                                        <i class="fa fa-warning"></i>
                                                        S/ <?php echo number_format((float) $top["deuda_vencida_cliente"], 0); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="dd-prio-deuda dd-prio-deuda--ok">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="dd-prio-col-acc text-center">
                                                <?php if (!empty($top["cod_cli"])) : ?>
                                                    <button type="button"
                                                        class="btn btn-xs btn-default btnDdMiniIc"
                                                        title="Ver inteligencia del cliente"
                                                        data-cliente="<?php echo htmlspecialchars($top["cod_cli"]); ?>"
                                                        data-pedido="<?php echo htmlspecialchars($top["codigo"]); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($top["cliente"]); ?>">
                                                        <i class="fa fa-user-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row dd-row-compact">
            <div class="col-md-6">
                <div class="box box-warning dd-box">
                    <div class="box-header with-border dd-box-header-compact">
                        <h3 class="box-title"><i class="fa fa-hourglass-half"></i> Estancados +3 días</h3>
                    </div>
                    <div class="box-body table-responsive dd-table-wrap dd-table-wrap--estancados">
                        <table class="table table-hover table-condensed dd-table dd-table-compact">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th class="dd-col-cliente">Cliente</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($estancados)) : ?>
                                    <tr><td colspan="5" class="text-center text-muted">Sin pedidos estancados en estos estados.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($estancados as $row) : ?>
                                        <tr>
                                            <td>
                                                <a href="#"
                                                   class="dd-link-pedido"
                                                   data-codigo="<?php echo htmlspecialchars($row["codigo"]); ?>"
                                                   title="Imprimir pedido">
                                                    <strong><?php echo htmlspecialchars($row["codigo"]); ?></strong>
                                                </a>
                                            </td>
                                            <td class="dd-col-cliente">
                                                <div class="dd-cell-main dd-cell-cliente" title="<?php echo htmlspecialchars($row["cod_cli"] . " · " . $row["cliente"]); ?>">
                                                    <?php echo ddClienteLinea($row["cod_cli"], $row["cliente"]); ?>
                                                </div>
                                            </td>
                                            <td><?php echo ddEstadoBadge($row["estado"]); ?></td>
                                            <td><?php echo ddFormatoMonto($row["lista"], $row["total"]); ?></td>
                                            <td><span class="dd-dias dd-dias--<?php echo ((int) $row["dias_sin_avance"] >= 7) ? "alto" : "medio"; ?>"><?php echo (int) $row["dias_sin_avance"]; ?>d</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-danger dd-box">
                    <div class="box-header with-border dd-box-header-compact">
                        <h3 class="box-title"><i class="fa fa-users"></i> Clientes con pedido activo</h3>
                    </div>
                    <div class="box-body table-responsive dd-table-wrap dd-table-wrap--short">
                        <table class="table table-hover table-condensed dd-table dd-table-compact">
                            <thead>
                                <tr>
                                    <th class="dd-col-cliente">Cliente</th>
                                    <th>Pedidos</th>
                                    <th class="text-right">Pipeline</th>
                                    <th class="text-right">Deuda</th>
                                    <th>Días</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($atraso)) : ?>
                                    <tr><td colspan="6" class="text-center text-muted">Sin clientes con pedidos en pipeline.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($atraso as $row) :
                                        $enMora = (float) $row["deuda_vencida"] > 0;
                                        $diasPedido = (int) $row["dias_pedido"];
                                        $pedidosGenerados = (int) $row["pedidos_generados"];
                                        ?>
                                        <tr class="<?php echo $enMora ? "dd-row-mora" : ""; ?>">
                                            <td class="dd-col-cliente">
                                                <div class="dd-cell-main dd-cell-cliente" title="<?php echo htmlspecialchars($row["codigo"] . " · " . $row["nombre"]); ?>">
                                                    <?php echo ddClienteLinea($row["codigo"], $row["nombre"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo (int) $row["pedidos_activos"]; ?></strong>
                                                <?php if ($pedidosGenerados > 0) : ?>
                                                    <small class="text-warning"> · <?php echo $pedidosGenerados; ?> gen.</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">S/ <?php echo number_format((float) $row["soles_pipeline"], 0); ?></td>
                                            <td class="text-right">
                                                <?php if ($enMora) : ?>
                                                    <span class="dd-prio-deuda dd-prio-deuda--vencida">
                                                        S/ <?php echo number_format((float) $row["deuda_vencida"], 0); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="dd-prio-deuda dd-prio-deuda--ok">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="dd-dias dd-dias--<?php echo ($diasPedido >= 3) ? "alto" : "medio"; ?>">
                                                    <?php echo $diasPedido; ?>d
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <a href="index.php?ruta=inteligencia-comercial&cliente=<?php echo urlencode($row["codigo"]); ?>"
                                                   class="btn btn-xs btn-default" title="Analizar cliente">
                                                    <i class="fa fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalDdMiniIc" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-mini-ic-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddMiniIcTitulo">
                    <i class="fa fa-user-circle"></i> Análisis para decisión
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddMiniIcSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddMiniIcBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando análisis del cliente…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddMiniIcLinkCompleto" target="_blank">
                    <i class="fa fa-external-link"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDdDecisionCredito" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content dd-decision-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="ddDecisionCreditoTitulo">
                    <i class="fa fa-gavel"></i> Decisión de crédito
                </h4>
                <p class="dd-mini-ic-subtitulo" id="ddDecisionCreditoSubtitulo"></p>
            </div>
            <div class="modal-body" id="ddDecisionCreditoBody">
                <div class="dd-mini-ic-loading text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando decisión…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="ddDecisionCreditoLinkIc" target="_blank">
                    <i class="fa fa-line-chart"></i> Ver análisis completo
                </a>
            </div>
        </div>
    </div>
</div>

<script>window.document.title = "Centro de Decisiones | Vasco System";</script>
