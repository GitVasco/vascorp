<div class="content-wrapper" id="ceListado">
<style>
    #ceTabla td, #ceTabla th { vertical-align: middle !important; }
    #ceTabla .ce-nowrap { white-space: nowrap; }
    #ceTabla td.text-right, #ceTabla th.text-right { padding-right: 14px; }
</style>

    <section class="content-header">
        <h1>
            Comprobantes especiales
            <small><?= class_exists("ControladorExportacion") && ControladorExportacion::puedeUsar() ? "Listado y envío a eFact · gratuitas y exportación" : "Boletas y facturas fuera del flujo de ventas · no mueve stock"; ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Comprobantes especiales</li>
        </ol>
    </section>

    <?php
    // Pestañas: Gratuitas (permiso propio) y Exportación (módulo de facturación, docs/efact/EXPORTACION-diseno.md)
    $ceVerGratuitas = ControladorComprobantesEspeciales::puedeUsar();
    $ceVerExportacion = class_exists("ControladorExportacion") && ControladorExportacion::puedeUsar();
    ?>
    <section class="content">
        <div class="nav-tabs-custom" id="ceTabsListado">
            <ul class="nav nav-tabs">
                <?php if ($ceVerGratuitas) { ?>
                <li class="active"><a href="#ceTabGratuitas" data-toggle="tab"><i class="fa fa-gift"></i> Gratuitas</a></li>
                <?php } ?>
                <?php if ($ceVerExportacion) { ?>
                <li class="<?= $ceVerGratuitas ? "" : "active"; ?>"><a href="#ceTabExportacion" data-toggle="tab"><i class="fa fa-globe"></i> Exportación</a></li>
                <?php } ?>
            </ul>
            <div class="tab-content">
        <?php if ($ceVerExportacion) { ?>
        <div class="tab-pane <?= $ceVerGratuitas ? "" : "active"; ?>" id="ceTabExportacion">
            <?php include __DIR__ . "/exportacion-listado-panel.php"; ?>
        </div>
        <?php } ?>
        <?php if ($ceVerGratuitas) { ?>
        <div class="tab-pane active" id="ceTabGratuitas">
        <div class="alert alert-danger" id="ceFaltan" style="display:none"></div>
        <div class="box">
            <div class="box-header with-border">
                <div class="form-inline">
                    <label>Desde</label> <input type="date" class="form-control input-sm" id="ceDesde" value="<?= date("Y-m-01"); ?>">
                    <label style="margin-left:8px">Hasta</label> <input type="date" class="form-control input-sm" id="ceHasta" value="<?= date("Y-m-d"); ?>">
                    <select class="form-control input-sm selectpicker" id="ceEstadoFiltro" data-width="150px" style="margin-left:8px">
                        <option value="">Todos los estados</option>
                        <option value="GENERADO">GENERADO</option>
                        <option value="ENVIADO">ENVIADO</option>
                        <option value="BAJA">BAJA</option>
                        <option value="ANULADO">ANULADO</option>
                    </select>
                    <select class="form-control input-sm selectpicker" id="ceTipoFiltro" data-width="200px" style="margin-left:8px"><option value="">Todos los tipos</option></select>
                    <input type="text" class="form-control input-sm" id="ceLoteFiltro" placeholder="Lote (opcional)" style="margin-left:8px;width:140px" value="<?= isset($_GET["lote"]) ? htmlspecialchars(preg_replace('/[^A-Za-z0-9-]/', '', $_GET["lote"]), ENT_QUOTES, "UTF-8") : ""; ?>">
                    <button type="button" class="btn btn-default btn-sm" id="ceBuscar"><i class="fa fa-search"></i> Buscar</button>
                    <button type="button" class="btn btn-default btn-sm" id="ceLimpiar" title="Restablece fechas, estado, tipo y lote"><i class="fa fa-eraser"></i> Limpiar filtros</button>
                    <a href="comprobantes-especiales-crear" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Nuevo comprobante</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive" id="ceTabla" width="100%">
                    <thead>
                        <tr>
                            <th>Documento</th><th class="text-center">Emisión</th><th>Receptor</th><th>Tipo</th><th>Categoría</th>
                            <th>Afectación IGV</th><th class="text-right">Op. gratuita</th><th class="text-right">IGV inf.</th>
                            <th>Lote</th><th class="text-center">Estado</th><th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <p class="text-muted" style="font-size:12px">
            <b>Enviar</b> deja el CSV en la carpeta de eFact. Si es una prueba, dar de baja el comprobante en el portal de eFact y pulsar
            <i class="fa fa-arrow-down"></i> <b>Marcar baja</b> para dejar el registro.
        </p>
        </div>
        <?php } ?>
            </div>
        </div>
    </section>
</div>
