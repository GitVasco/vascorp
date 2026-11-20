<?php
$kpisEnvio = ControladorCuentas::ctrContarKpisEnvioLetras();
$kpiPorMandar = isset($kpisEnvio["por_mandar"]) ? (int) $kpisEnvio["por_mandar"] : 0;
$kpiEnviadas = isset($kpisEnvio["enviadas"]) ? (int) $kpisEnvio["enviadas"] : 0;
$kpiSinUnico = isset($kpisEnvio["sin_num_unico"]) ? (int) $kpisEnvio["sin_num_unico"] : 0;
?>
<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar envios de letras

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar envios de letras</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-inbox"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Por mandar</span>
                        <span class="info-box-number"><?= number_format($kpiPorMandar, 0, '.', ',') ?></span>
                        <span class="progress-description">Listas para armar un envío</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-paper-plane"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Enviadas al banco</span>
                        <span class="info-box-number"><?= number_format($kpiEnviadas, 0, '.', ',') ?></span>
                        <span class="progress-description">Pendientes de cobro en BCP</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="fa fa-hashtag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sin nro. único</span>
                        <span class="info-box-number"><?= number_format($kpiSinUnico, 0, '.', ',') ?></span>
                        <span class="progress-description">Enviadas que aún no retornan único</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header width-border">
                <a class="btn btn-primary" href="envio-letras"><i class="fa fa-paper-plane-o"></i>  Enviar letras</a>
                <button type="button" class="btn btn-default pull-right" id="daterange-btnEnvioCta">
                    <span>
                        <i class="fa fa-calendar"></i>

                        <?php

                        if(isset($_GET["fechaInicial"])){

                            echo $_GET["fechaInicial"]." - ".$_GET["fechaFinal"];

                        }else{
                        
                            echo 'Rango de fecha';

                        }

                        ?>

                    </span>

                    <i class="fa fa-caret-down"></i>

                </button>
                
            </div>
            <div class="box-body">
            <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
            <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">

                <table class="table table-bordered table-striped dt-responsive tablaEnvioCuentas" width="100%">

                    <thead>

                        <tr>

                            <th>Codigo</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Cantidad</th>
                            <th>Formato</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL VISUALIZAR ENVIO LETRAS
======================================-->
<div id="modalVisualizarEnvioCuenta" class="modal fade" role="dialog">
    <div class="modal-dialog" style="width: 90% !important;">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detalle del envío de letras</h4>
            </div>
            <div class="modal-body">
                <div class="box-body">
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-sm-3"><b>Código:</b> <span id="verEnvioCodigo">-</span></div>
                        <div class="col-sm-3"><b>Fecha:</b> <span id="verEnvioFecha">-</span></div>
                        <div class="col-sm-3"><b>Usuario:</b> <span id="verEnvioUsuario">-</span></div>
                        <div class="col-sm-3"><b>Cantidad:</b> <span id="verEnvioCantidad">-</span></div>
                    </div>
                    <div id="verEnvioAvisoDireccion" class="alert alert-warning" style="display:none; margin-bottom:10px;"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="tablaDetalleEnvioLetras" width="100%">
                            <thead>
                                <tr>
                                    <th>Nº letra</th>
                                    <th>Cliente</th>
                                    <th>Documento</th>
                                    <th>Vencimiento</th>
                                    <th>Saldo</th>
                                    <th>Dirección</th>
                                    <th>Ubigeo</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="verEnvioFormatoActual" class="label label-default" style="margin-right:8px;"></span>
                <button type="button" class="btn btn-default btnDescargarEnvioFormato" id="verEnvioDescargarTxt" data-formato="txt" style="display:none;">
                    <i class="fa fa-file-text-o"></i> Descargar TXT
                </button>
                <button type="button" class="btn btn-success btnDescargarEnvioFormato" id="verEnvioDescargarExcel" data-formato="excel" style="display:none;">
                    <i class="fa fa-file-excel-o"></i> Descargar Excel (.xlsx)
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
window.document.title = "Envio de Letras"
</script>