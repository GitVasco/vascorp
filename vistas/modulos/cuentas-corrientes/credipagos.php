<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            CrediPagos
            <small>Panel de CrediPagos</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">CrediPagos</li>
        </ol>
    </section>

    <section class="content">

        <div class="row" id="kpisCredipagos" style="margin-bottom:8px;">
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-box bg-aqua" style="min-height:74px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:74px; width:58px; line-height:74px; font-size:26px;"><i class="fa fa-list"></i></span>
                    <div class="info-box-content" style="margin-left:58px;">
                        <span class="info-box-text">Cantidad</span>
                        <span class="info-box-number" id="kpiCredipagosCantidad" style="font-size:22px;">0</span>
                        <span class="progress-description" id="kpiCredipagosHintCantidad" style="font-size:11px;">Registros del listado</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-box bg-green" style="min-height:74px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:74px; width:58px; line-height:74px; font-size:26px;"><i class="fa fa-money"></i></span>
                    <div class="info-box-content" style="margin-left:58px;">
                        <span class="info-box-text">Monto total</span>
                        <span class="info-box-number" id="kpiCredipagosMonto" style="font-size:20px; white-space:nowrap;">S/ 0.00</span>
                        <span class="progress-description" id="kpiCredipagosHintMonto" style="font-size:11px;">Suma del listado</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-box bg-yellow" style="min-height:74px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:74px; width:58px; line-height:74px; font-size:26px;"><i class="fa fa-check-square-o"></i></span>
                    <div class="info-box-content" style="margin-left:58px;">
                        <span class="info-box-text">Seleccionados</span>
                        <span class="info-box-number" id="kpiCredipagosSeleccionados" style="font-size:22px;">0</span>
                        <span class="progress-description" id="kpiCredipagosPctCantidad" style="font-size:11px;">Nada marcado</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-box bg-teal" style="min-height:74px; margin-bottom:8px;">
                    <span class="info-box-icon" style="height:74px; width:58px; line-height:74px; font-size:26px;"><i class="fa fa-calculator"></i></span>
                    <div class="info-box-content" style="margin-left:58px;">
                        <span class="info-box-text">Monto seleccionado</span>
                        <span class="info-box-number" id="kpiCredipagosMontoSel" style="font-size:20px; white-space:nowrap;">S/ 0.00</span>
                        <span class="progress-description" id="kpiCredipagosPctMonto" style="font-size:11px;">Se actualiza al marcar</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">

            <div class="box-header with-border">
                <button class="btn btn-default" id="marcarTodos">
                    <i class="fa fa-check-square-o"></i> Seleccionar todos
                </button>
                <button class="btn btn-default" id="desmarcarTodos">
                    <i class="fa fa-square-o"></i> Deseleccionar todos
                </button>
                <button class="btn btn-danger" id="eliminarSeleccionados" disabled>
                    <i class="fa fa-trash"></i> Eliminar seleccionados
                </button>
                <span id="contadorSeleccionados" style="margin-left: 15px;">
                    Seleccionados: <strong>0</strong>
                </span>
            </div>

            <div class="box box-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped dt-responsive tablaCredipagos" width="100%">
                        <thead>
                            <tr>
                                <th>Tip. Doc.</th>
                                <th>Num. Cta</th>
                                <th>Fec. Canc.</th>
                                <th>Cod. Cli.</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Vendedor</th>
                                <th>Notas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                    </table>
                </div>
            </div>
        </div>

    </section>


</div>

<script>
    window.document.title = "Credipagos"
</script>