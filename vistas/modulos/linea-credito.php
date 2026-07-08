<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

date_default_timezone_set("America/Lima");
$periodo = ControladorLineaCredito::ctrPeriodoCierre();
$filas = ControladorLineaCredito::ctrListar();
$totalCartera = ModeloLineaCredito::mdlContarCarteraActiva();
$totalCierre = ModeloLineaCredito::mdlResumenCierre($periodo["anio"], $periodo["mes"]);
$meses = ControladorTalleres::ctrMes();
$nombreMes = (string) $periodo["mes"];
foreach ($meses as $mesItem) {
    if ((int) $mesItem["codigo"] === $periodo["mes"]) {
        $nombreMes = $mesItem["descripcion"];
        break;
    }
}

function lcFmt($valor)
{
    if ($valor === null || $valor === "") {
        return '<span class="text-muted">—</span>';
    }

    return "S/ " . number_format((float) $valor, 2);
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Línea de crédito por cliente</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Línea de crédito</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-credit-card"></i> Cartera crediticia</h3>
                <div class="box-tools pull-right lc-toolbar">
                    <span class="label label-info" id="lcResumenCierre">
                        Cierre <?php echo (int) $periodo["mes"] . "/" . (int) $periodo["anio"]; ?>:
                        <?php echo (int) $totalCierre; ?> clientes
                    </span>
                    <button type="button" class="btn btn-warning btn-sm" id="btnLcCierreMensual">
                        <i class="fa fa-refresh"></i> Cierre mensual
                    </button>
                </div>
            </div>
            <div class="box-body">
                <p class="lc-leyenda text-muted">
                    <i class="fa fa-info-circle"></i>
                    Cartera activa: <strong><?php echo (int) $totalCartera; ?></strong> clientes
                    (vendedor con Centro de Decisiones + compra o pedido en los últimos 24 meses).
                    Líneas recomendadas y aprobadas se redondean hacia abajo al múltiplo de S/ 1.000.
                </p>
                <div class="row lc-filtros">
                    <div class="col-sm-4">
                        <input type="text" class="form-control input-sm" id="lcBusqueda" placeholder="Buscar cliente o código…">
                    </div>
                </div>
                <div class="table-responsive lc-table-wrap">
                    <table class="table table-hover table-condensed" id="tablaLineaCredito">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Línea oper.</th>
                                <th>Recomendada</th>
                                <th>Aprobada</th>
                                <th>Deuda</th>
                                <th>Cupo</th>
                                <th>Riesgo</th>
                                <th>Últ. actualización</th>
                                <th width="90"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $row) : ?>
                                <tr data-cliente="<?php echo htmlspecialchars($row["codigo"]); ?>">
                                    <td>
                                        <strong class="lc-cod"><?php echo htmlspecialchars($row["codigo"]); ?></strong>
                                        <div class="lc-nombre"><?php echo htmlspecialchars($row["nombre"]); ?></div>
                                    </td>
                                    <td><?php echo lcFmt($row["linea_operativa"]); ?></td>
                                    <td><?php echo lcFmt($row["linea_recomendada"]); ?></td>
                                    <td><?php echo lcFmt($row["linea_aprobada"]); ?></td>
                                    <td><?php echo lcFmt($row["deuda_actual"]); ?></td>
                                    <td><?php echo lcFmt($row["cupo_disponible"]); ?></td>
                                    <td>
                                        <?php if ($row["score_riesgo"] !== null) : ?>
                                            <span class="label label-default"><?php echo number_format((float) $row["score_riesgo"], 1); ?></span>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo $row["fecha_actualizacion"] ? htmlspecialchars($row["fecha_actualizacion"]) : "—"; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-default btnLcDetalle" data-cliente="<?php echo htmlspecialchars($row["codigo"]); ?>" data-nombre="<?php echo htmlspecialchars($row["nombre"]); ?>">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalLcDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="lcDetalleTitulo"><i class="fa fa-user"></i> Cliente</h4>
            </div>
            <div class="modal-body" id="lcDetalleBody">
                <div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="lcLinkIc" target="_blank"><i class="fa fa-line-chart"></i> Inteligencia comercial</a>
            </div>
        </div>
    </div>
</div>

<script>window.document.title = "Línea de crédito | Vasco System";</script>
