<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "linea_credito")) {
    denegarAccesoModulo();
    return;
}

date_default_timezone_set("America/Lima");
$periodo = ControladorLineaCredito::ctrPeriodoCierre();
$filas = ControladorLineaCredito::ctrListar();
$totalCartera = ModeloLineaCredito::mdlContarCarteraActiva();
$totalCierre = ModeloLineaCredito::mdlResumenCierre($periodo["anio"], $periodo["mes"]);
$gruposActivos = ControladorGruposEmpresariales::ctrMostrarGruposActivos();
$gruposPorCodigo = array();

foreach ($gruposActivos as $grupoItem) {
    $gruposPorCodigo[$grupoItem["codigo"]] = $grupoItem["nombre"];
}

$usuariosActivosLc = ModeloLineaCredito::mdlUsuariosActivos();
$idUsuarioSesionLc = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;

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

<div class="content-wrapper lc-page lc-page--modo-todos" id="lcPage">
    <section class="content-header">
        <h1>Línea de crédito</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Línea de crédito</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary lc-box-principal">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-credit-card"></i> Cartera crediticia</h3>
                <div class="box-tools pull-right lc-toolbar">
                    <span class="label label-info" id="lcResumenCierre">
                        Cierre <?php echo (int) $periodo["mes"] . "/" . (int) $periodo["anio"]; ?>:
                        <?php echo (int) $totalCierre; ?> clientes
                    </span>
                    <button type="button" class="btn btn-success btn-sm" id="btnLcExportarExcel" title="Exportar cartera a Excel">
                        <i class="fa fa-file-excel-o"></i> Exportar Excel
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="btnLcCierreMensual">
                        <i class="fa fa-refresh"></i> Cierre mensual
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="lc-intro-bar">
                    <p class="lc-leyenda text-muted">
                        <i class="fa fa-info-circle"></i>
                        Cartera activa: <strong><?php echo (int) $totalCartera; ?></strong> clientes
                        · Líneas redondeadas al múltiplo de S/ 1.000.
                    </p>
                    <span class="lc-modo-badge" id="lcModoBadge"><i class="fa fa-globe"></i> Vista general</span>
                </div>

                <div class="lc-filtros-bar">
                    <div class="lc-filtros-bar__campo">
                        <label class="lc-field-lbl" for="lcFiltroGrupo">Grupo empresarial</label>
                        <select class="form-control input-sm selectpicker" id="lcFiltroGrupo" data-live-search="true" data-size="8" title="Todos los clientes">
                            <option value="">Todos los clientes</option>
                            <option value="__sin_grupo__">Sin grupo empresarial</option>
                            <?php foreach ($gruposActivos as $grupoItem) : ?>
                                <option value="<?php echo htmlspecialchars($grupoItem["codigo"], ENT_QUOTES, "UTF-8"); ?>"
                                    data-nombre="<?php echo htmlspecialchars($grupoItem["nombre"], ENT_QUOTES, "UTF-8"); ?>">
                                    <?php echo htmlspecialchars($grupoItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lc-filtros-bar__acciones">
                        <button type="button" class="btn btn-default btn-sm hidden" id="btnLcLimpiarFiltro" title="Quitar filtro y ver toda la cartera">
                            <i class="fa fa-times"></i> Ver todos los clientes
                        </button>
                    </div>
                    <div class="lc-filtros-bar__ayuda" id="lcFiltroAyuda">
                        Elija un grupo para gestionar la línea aprobada consolidada. Los locales solo muestran deuda y riesgo individual.
                    </div>
                </div>

                <div class="row lc-main-grid" id="lcMainGrid">
                    <div class="lc-main-grid__panel hidden" id="lcColPanel">
                        <div id="lcPanelGrupo" class="lc-panel-grupo" style="display:none"></div>
                    </div>
                    <div class="col-md-12 lc-main-grid__tabla" id="lcColTabla">
                        <div class="lc-tabla-seccion" id="lcTablaSeccion">
                    <div class="lc-tabla-titulo-wrap">
                        <h4 class="lc-tabla-titulo" id="lcTablaTitulo">
                            <i class="fa fa-list"></i> Clientes en cartera
                        </h4>
                        <span class="lc-tabla-contador text-muted" id="lcTablaContador"></span>
                    </div>
                    <div class="table-responsive lc-table-wrap">
                        <table class="table table-hover table-condensed" id="tablaLineaCredito">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="lc-col-linea">Línea oper.</th>
                                    <th class="lc-col-linea">Recomendada</th>
                                    <th class="lc-col-aprobada">Aprobada</th>
                                    <th class="lc-col-deuda">Deuda</th>
                                    <th class="lc-col-vencida">Vencida</th>
                                    <th class="lc-col-pct">% grupo</th>
                                    <th class="lc-col-cupo">Cupo</th>
                                    <th class="lc-col-riesgo">Riesgo</th>
                                    <th class="lc-col-fecha">Últ. actualización</th>
                                    <th class="lc-col-acciones" width="56"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filas as $row) :
                                    $codigoGrupo = isset($row["grupo"]) ? trim((string) $row["grupo"]) : "";
                                    $nombreGrupo = ($codigoGrupo !== "" && isset($gruposPorCodigo[$codigoGrupo]))
                                        ? $gruposPorCodigo[$codigoGrupo]
                                        : $codigoGrupo;
                                    $tieneGrupo = $codigoGrupo !== "";
                                ?>
                                    <tr class="<?php echo $tieneGrupo ? "lc-row--grupo" : "lc-row--individual"; ?>"
                                        data-cliente="<?php echo htmlspecialchars($row["codigo"]); ?>"
                                        data-grupo="<?php echo htmlspecialchars($codigoGrupo, ENT_QUOTES, "UTF-8"); ?>"
                                        data-grupo-nombre="<?php echo htmlspecialchars($nombreGrupo, ENT_QUOTES, "UTF-8"); ?>">
                                        <td>
                                            <strong class="lc-cod"><?php echo htmlspecialchars($row["codigo"]); ?></strong>
                                            <div class="lc-nombre"><?php echo htmlspecialchars($row["nombre"]); ?></div>
                                            <?php if ($tieneGrupo) : ?>
                                                <span class="lc-grupo-tag" title="Pertenece a grupo empresarial">
                                                    <i class="fa fa-sitemap"></i> <?php echo htmlspecialchars($nombreGrupo); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="lc-col-linea"><?php echo lcFmt($row["linea_operativa"]); ?></td>
                                        <td class="lc-col-linea"><?php echo lcFmt($row["linea_recomendada"]); ?></td>
                                        <td class="lc-col-aprobada">
                                            <?php if ($tieneGrupo) : ?>
                                                <button type="button" class="btn btn-xs btn-primary btnLcIrGrupo lc-btn-grupo-inline"
                                                    data-grupo="<?php echo htmlspecialchars($codigoGrupo, ENT_QUOTES, "UTF-8"); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($nombreGrupo, ENT_QUOTES, "UTF-8"); ?>"
                                                    title="La línea aprobada se gestiona a nivel de grupo">
                                                    <i class="fa fa-sitemap"></i> Grupo
                                                </button>
                                            <?php elseif ($row["linea_aprobada"] !== null && (float) $row["linea_aprobada"] > 0) : ?>
                                                <?php echo lcFmt($row["linea_aprobada"]); ?>
                                            <?php else : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="lc-col-deuda" data-deuda="<?php echo isset($row["deuda_actual"]) ? (float) $row["deuda_actual"] : 0; ?>">
                                            <strong class="lc-deuda-val"><?php echo lcFmt($row["deuda_actual"]); ?></strong>
                                        </td>
                                        <td class="lc-col-vencida" data-vencida="<?php echo isset($row["deuda_vencida"]) ? (float) $row["deuda_vencida"] : 0; ?>">
                                            <?php
                                            $deudaVencida = isset($row["deuda_vencida"]) ? (float) $row["deuda_vencida"] : 0;
                                            if ($deudaVencida > 0) : ?>
                                                <strong class="lc-vencida-val lc-vencida-val--alert"><?php echo lcFmt($deudaVencida); ?></strong>
                                            <?php else : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="lc-col-pct"><span class="text-muted">—</span></td>
                                        <td class="lc-col-cupo">
                                            <?php if ($tieneGrupo) : ?>
                                                <span class="text-muted lc-cupo-grupo-hint" title="El cupo se valida contra la línea del grupo">
                                                    <i class="fa fa-level-up"></i> Consolidado
                                                </span>
                                            <?php else : ?>
                                                <?php echo lcFmt($row["cupo_disponible"]); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="lc-col-riesgo">
                                            <?php if ($row["score_riesgo"] !== null) :
                                                $sr = (float) $row["score_riesgo"];
                                                $srCls = "default";
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
                                                <span class="lc-riesgo-badge lc-riesgo-badge--<?php echo $srCls; ?>"><?php echo number_format($sr, 1); ?></span>
                                            <?php else : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="lc-col-fecha">
                                            <small><?php echo $row["fecha_actualizacion"] ? htmlspecialchars($row["fecha_actualizacion"]) : "—"; ?></small>
                                        </td>
                                        <td class="text-center lc-col-acciones">
                                            <button type="button" class="btn btn-xs btn-default btnLcDetalle" data-cliente="<?php echo htmlspecialchars($row["codigo"]); ?>" data-nombre="<?php echo htmlspecialchars($row["nombre"]); ?>" title="Ver detalle del local">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php if ($tieneGrupo) : ?>
                                                <button type="button" class="btn btn-xs btn-primary btnLcIrGrupo" data-grupo="<?php echo htmlspecialchars($codigoGrupo, ENT_QUOTES, "UTF-8"); ?>" data-nombre="<?php echo htmlspecialchars($nombreGrupo, ENT_QUOTES, "UTF-8"); ?>" title="Ir al grupo">
                                                    <i class="fa fa-sitemap"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalLcExportExcel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-file-excel-o"></i> Exportar cartera a Excel</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Los datos van en las hojas <strong>Por cliente</strong> y <strong>Por grupo</strong> (sin bloqueo).
                    La responsabilidad del reporte queda registrada en la hoja <strong>Metadatos</strong> (solo lectura).
                </p>
                <div class="form-group">
                    <label for="lcExportSolicitadoPor">Solicitado por <span class="text-danger">*</span></label>
                    <select class="form-control selectpicker" id="lcExportSolicitadoPor" data-live-search="true" data-size="8" required>
                        <option value="">Seleccione responsable…</option>
                        <?php foreach ($usuariosActivosLc as $usuarioLc) : ?>
                            <option value="<?php echo (int) $usuarioLc["id"]; ?>"
                                <?php echo ((int) $usuarioLc["id"] === $idUsuarioSesionLc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuarioLc["nombre"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <form id="lcFormExportExcel" method="GET" action="vistas/reportes_excel/rpt_linea_credito.php" target="_blank" style="display:inline;">
                    <input type="hidden" name="solicitud_por" id="lcExportSolicitadoPorHidden" value="">
                    <button type="submit" class="btn btn-success" id="btnLcConfirmarExportExcel">
                        <i class="fa fa-download"></i> Descargar Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
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
