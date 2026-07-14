<?php
if (!function_exists("dcUsuarioPuedeVerHistorialCredito") || !dcUsuarioPuedeVerHistorialCredito()) {
    denegarAccesoModulo();
    return;
}

date_default_timezone_set("America/Lima");

$fechaDesde = isset($_GET["desde"]) ? trim((string) $_GET["desde"]) : date("Y-m-d", strtotime("-30 days"));
$fechaHasta = isset($_GET["hasta"]) ? trim((string) $_GET["hasta"]) : date("Y-m-d");
$tipoAccion = isset($_GET["tipo"]) ? strtoupper(trim((string) $_GET["tipo"])) : "";
$q = isset($_GET["q"]) ? trim((string) $_GET["q"]) : "";

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
    $fechaDesde = date("Y-m-d", strtotime("-30 days"));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
    $fechaHasta = date("Y-m-d");
}

$datos = ControladorDecisionesCredito::ctrListarHistorialAcciones(array(
    "fecha_desde" => $fechaDesde,
    "fecha_hasta" => $fechaHasta,
    "tipo_accion" => $tipoAccion,
    "q" => $q,
    "limite" => 200,
));

$filas = (!empty($datos["ok"]) && isset($datos["filas"])) ? $datos["filas"] : array();
$resumen = (!empty($datos["ok"]) && isset($datos["resumen"])) ? $datos["resumen"] : array(
    "APROBADO" => 0,
    "OBJECION" => 0,
    "OBJECION_CERRADA" => 0,
    "ANULADO" => 0,
    "total" => 0,
);

function hcFmtMonto($lista, $monto)
{
    if ($monto === null || $monto === "") {
        return "—";
    }
    $simbolo = ($lista === "precio1") ? "$ " : "S/ ";
    return $simbolo . number_format((float) $monto, 2);
}
?>

<div class="content-wrapper hc-page">
    <section class="content-header">
        <div class="hc-header-row">
            <div>
                <h1>
                    Historial de crédito
                    <small>Aprobaciones, objeciones y anulaciones</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
                    <li><a href="index.php?ruta=dashboard-decisiones">Centro de Decisiones</a></li>
                    <li class="active">Historial de crédito</li>
                </ol>
            </div>
            <a href="index.php?ruta=dashboard-decisiones" class="btn btn-default btn-sm">
                <i class="fa fa-gavel"></i> Ir al Centro de Decisiones
            </a>
        </div>
    </section>

    <section class="content">
        <div class="row hc-resumen-row">
            <div class="col-sm-3 col-xs-6">
                <div class="hc-kpi hc-kpi--aprobado">
                    <span class="hc-kpi-lbl">Aprobados</span>
                    <strong id="hcKpiAprobado"><?php echo (int) $resumen["APROBADO"]; ?></strong>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="hc-kpi hc-kpi--objecion">
                    <span class="hc-kpi-lbl">Objeciones</span>
                    <strong id="hcKpiObjecion"><?php echo (int) $resumen["OBJECION"]; ?></strong>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="hc-kpi hc-kpi--cerrada">
                    <span class="hc-kpi-lbl">Obj. cerradas</span>
                    <strong id="hcKpiCerrada"><?php echo (int) $resumen["OBJECION_CERRADA"]; ?></strong>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="hc-kpi hc-kpi--anulado">
                    <span class="hc-kpi-lbl">Anulados</span>
                    <strong id="hcKpiAnulado"><?php echo (int) $resumen["ANULADO"]; ?></strong>
                </div>
            </div>
        </div>

        <div class="box box-primary hc-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-history"></i> Movimientos</h3>
            </div>
            <div class="box-body">
                <form class="hc-filtros" id="hcFiltrosForm" onsubmit="return false;">
                    <div class="hc-filtros-grid">
                        <div class="form-group">
                            <label for="hcFechaDesde">Desde</label>
                            <input type="date" class="form-control input-sm" id="hcFechaDesde" value="<?php echo htmlspecialchars($fechaDesde); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hcFechaHasta">Hasta</label>
                            <input type="date" class="form-control input-sm" id="hcFechaHasta" value="<?php echo htmlspecialchars($fechaHasta); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hcTipo">Tipo</label>
                            <select class="form-control input-sm" id="hcTipo">
                                <option value="">Todos</option>
                                <option value="APROBADO" <?php echo $tipoAccion === "APROBADO" ? "selected" : ""; ?>>Aprobados</option>
                                <option value="OBJECION" <?php echo $tipoAccion === "OBJECION" ? "selected" : ""; ?>>Objeciones</option>
                                <option value="OBJECION_CERRADA" <?php echo $tipoAccion === "OBJECION_CERRADA" ? "selected" : ""; ?>>Obj. cerradas</option>
                                <option value="ANULADO" <?php echo $tipoAccion === "ANULADO" ? "selected" : ""; ?>>Anulados</option>
                            </select>
                        </div>
                        <div class="form-group hc-filtro-buscar">
                            <label for="hcBuscar">Buscar</label>
                            <input type="text" class="form-control input-sm" id="hcBuscar"
                                   value="<?php echo htmlspecialchars($q); ?>"
                                   placeholder="Pedido, cliente, motivo…">
                        </div>
                        <div class="form-group hc-filtro-acciones">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnHcBuscar">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn btn-default btn-sm" id="btnHcLimpiar">
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-condensed hc-tabla" id="hcTabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th class="text-right">Monto</th>
                                <th>Detalle</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="hcTablaBody">
                            <?php if (empty($filas)) : ?>
                                <tr class="hc-empty">
                                    <td colspan="7" class="text-center text-muted">
                                        No hay movimientos en el rango seleccionado.
                                        Las acciones nuevas (aprobar / objeción / anular) aparecerán aquí.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($filas as $row) : ?>
                                    <tr>
                                        <td>
                                            <span class="hc-fecha"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($row["fecha"]))); ?></span>
                                        </td>
                                        <td>
                                            <span class="label label-<?php echo htmlspecialchars($row["tipo_clase"]); ?>">
                                                <?php echo htmlspecialchars($row["tipo_etiqueta"]); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row["codigo_pedido"]); ?></strong></td>
                                        <td>
                                            <span class="hc-cli-cod"><?php echo htmlspecialchars($row["codigo_cliente"]); ?></span>
                                            <?php echo htmlspecialchars($row["cliente_nombre"]); ?>
                                        </td>
                                        <td class="text-right">
                                            <?php echo hcFmtMonto(
                                                isset($row["pedido_lista"]) ? $row["pedido_lista"] : "",
                                                isset($row["pedido_total"]) ? $row["pedido_total"] : null
                                            ); ?>
                                        </td>
                                        <td class="hc-detalle">
                                            <?php
                                            $tieneMotivo = !empty($row["motivo_etiqueta"]);
                                            $tieneComentario = !empty($row["comentario"]);
                                            $tieneDetalle = !empty($row["detalle"]);
                                            $esAprobado = ($row["tipo_accion"] === "APROBADO");
                                            ?>
                                            <?php if ($tieneMotivo) : ?>
                                                <div class="hc-detalle-texto">
                                                    <strong><?php echo htmlspecialchars($row["motivo_etiqueta"]); ?></strong>
                                                    <?php if ($tieneComentario) : ?>
                                                        <div class="text-muted"><?php echo htmlspecialchars($row["comentario"]); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($tieneComentario) : ?>
                                                <div class="hc-detalle-texto"><?php echo htmlspecialchars($row["comentario"]); ?></div>
                                            <?php elseif ($tieneDetalle) : ?>
                                                <div class="hc-detalle-texto"><?php echo htmlspecialchars($row["detalle"]); ?></div>
                                            <?php elseif (!$esAprobado) : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>

                                            <?php if ($esAprobado || $row["tipo_accion"] === "ANULADO") : ?>
                                                <div class="hc-snap">
                                                    <?php if (!empty($row["categoria_codigo"])) :
                                                        $hexCat = !empty($row["categoria_color"])
                                                            ? $row["categoria_color"]
                                                            : (class_exists("ControladorCategoriasClientes")
                                                                ? ControladorCategoriasClientes::ctrResolverColorCategoria("", $row["categoria_codigo"])
                                                                : "#777777");
                                                        ?>
                                                        <span class="hc-snap-item">
                                                            <span class="hc-cat-sigla" style="background-color:<?php echo htmlspecialchars($hexCat, ENT_QUOTES, "UTF-8"); ?>;">
                                                                <?php echo htmlspecialchars(strtoupper($row["categoria_codigo"])); ?>
                                                            </span>
                                                            <?php if (!empty($row["categoria_nombre"])) : ?>
                                                                <span class="hc-snap-nombre"><?php echo htmlspecialchars($row["categoria_nombre"]); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row["nombre_grupo"]) || (!empty($row["cupo_modo"]) && $row["cupo_modo"] === "grupo")) : ?>
                                                        <span class="hc-snap-item">
                                                            <i class="fa fa-sitemap"></i>
                                                            <span class="hc-snap-nombre"><?php echo htmlspecialchars(!empty($row["nombre_grupo"]) ? $row["nombre_grupo"] : $row["codigo_grupo"]); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($row["linea_referencia"]) && $row["linea_referencia"] !== null && $row["linea_referencia"] !== "") : ?>
                                                        <span class="hc-snap-item" title="<?php echo htmlspecialchars(isset($row["etiqueta_linea"]) ? $row["etiqueta_linea"] : "Línea"); ?>">
                                                            <span class="hc-snap-nombre">Línea S/ <?php echo number_format((float) $row["linea_referencia"], 0); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($row["cupo_disponible"]) && $row["cupo_disponible"] !== null && $row["cupo_disponible"] !== "") : ?>
                                                        <span class="hc-snap-item">
                                                            <span class="hc-snap-nombre">Disp. S/ <?php echo number_format((float) $row["cupo_disponible"], 0); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($row["deuda_actual"]) && $row["deuda_actual"] !== null && $row["deuda_actual"] !== "") : ?>
                                                        <span class="hc-snap-item">
                                                            <span class="hc-snap-nombre">Deuda S/ <?php echo number_format((float) $row["deuda_actual"], 0); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($row["utilizacion_pct"]) && $row["utilizacion_pct"] !== null && $row["utilizacion_pct"] !== "") : ?>
                                                        <span class="hc-snap-item">
                                                            <span class="hc-snap-nombre">Util. <?php echo number_format((float) $row["utilizacion_pct"], 0); ?>%</span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($row["score_riesgo"]) && $row["score_riesgo"] !== null && $row["score_riesgo"] !== "") :
                                                        $sr = (float) $row["score_riesgo"];
                                                        if ($sr >= 90) {
                                                            $srCls = "success";
                                                        } elseif ($sr >= 80) {
                                                            $srCls = "primary";
                                                        } elseif ($sr >= 70) {
                                                            $srCls = "info";
                                                        } elseif ($sr >= 60) {
                                                            $srCls = "warning";
                                                        } else {
                                                            $srCls = "danger";
                                                        }
                                                        ?>
                                                        <span class="hc-snap-item hc-riesgo hc-riesgo--<?php echo $srCls; ?>">
                                                            <span class="hc-snap-nombre">Riesgo <?php echo number_format($sr, 0); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row["usuario_nombre"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>window.document.title = "Historial de crédito | Vasco System";</script>
