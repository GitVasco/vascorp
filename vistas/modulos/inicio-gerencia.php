<div class="content-wrapper">
    <title>Analisis</title>
    <section class="content-header">
        <h1>
            Dashboard

            <small>Página de control</small>

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Dashboard</li>

        </ol>

    </section>


    <section class="content">

        <div class="col-lg-10">

            <?php

            setlocale(LC_ALL, "es_ES");

            if (isset($_GET["mes"]) && $_GET["mes"] != "TODO") {

                $mesN = $_GET["mes"];

                $nomMes = ControladorTalleres::ctrMesB($mesN);
                $nomMesA = $nomMes["descripcion"];
            } else {

                $nomMesA = "TODOS";
            }

            #var_dump($nomMesA);

            echo '<div class="box box-success">

                    <div class="box-header">

                        <h1>Bienvenid@ ' . $_SESSION["nombre"] . ' - MES - <b>' . $nomMesA . '</b></h1>

                    </div>

                 </div>';


            ?>

        </div>

        <div class="col-lg-2">

            <select class="form-control input-lg selectpicker" id="mesGerencia" name="mesGerencia" data-live-search="true">

                <option value="">Seleccionar Mes</option>
                <option value="TODO">TODO</option>
                <option value="1">ENERO</option>
                <option value="2">FEBRERO</option>
                <option value="3">MARZO</option>
                <option value="4">ABRIL</option>
                <option value="5">MAYO</option>
                <option value="6">JUNIO</option>
                <option value="7">JULIO</option>
                <option value="8">AGOSTO</option>
                <option value="9">SEPTIEMBRE</option>
                <option value="10">OCTUBRE</option>
                <option value="11">NOVIEMBRE</option>
                <option value="12">DICIEMBRE</option>

            </select>

        </div>

        <div class="col-lg-12"></div>

        <div class="row">

            <?php

            include "inicio/cajas-superiores-cia.php";

            ?>

        </div>

        <!-- Ventas por documento -->
        <div class="row">

            <div class="col-lg-3">

                <div class="box box-danger">
                    <div class="box-header with-border"></div>
                    <center><b>Ventas por Documento <button class='btn btn-primary btn-xs btnRptResVtas' title='Pedidos' mes=<?php echo isset($_GET["mes"]) ? $_GET["mes"] : "TODO" ?>>Resumen Anual</button> <button class='btn btn-info btn-xs btnRptResVtaMes' title='Pedidos' mes=<?php echo isset($_GET["mes"]) ? $_GET["mes"] : "TODO" ?>>Resumen Mes</button></b></center>

                    <div class="box-body no-padding">
                        <table class="table table-bordered table-striped dt-responsive tablaVtasGerencia" width="100%">
                            <thead>
                                <tr>
                                    <th>CT</th>
                                    <th>Tipo</th>
                                    <th>Neto</th>
                                    <th>Igv</th>
                                    <th>Dscto</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                </div>

                <?php

                $mes = isset($_GET["mes"]) && $_GET["mes"] != "TODO" ? $_GET["mes"] : null;

                $totales = ControladorMovimientos::ctrTotalesSoles($mes);
                $facturas = ControladorMovimientos::ctrFacturas($mes);
                $proformas = ControladorMovimientos::ctrProformas($mes);

                $totalFacturas = $totales["vtas_soles"] != 0 ? ($facturas["neto"] / $totales["vtas_soles"]) * 100 : 0;
                $totalProformas = $totales["vtas_soles"] != 0 ? ($proformas["neto"] / $totales["vtas_soles"]) * 100 : 0;

                ?>

                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="ion ion-ios-pricetag-outline"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">Facturas</span>
                        <span class="info-box-number">S/ <?php echo number_format($facturas["neto"], 2) ?></span>

                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo number_format($totalFacturas, 0) ?>%"></div>
                        </div>
                        <span class="progress-description">
                            <?php echo number_format($totalFacturas, 0) ?>% Del Mes Actual
                        </span>
                    </div>

                </div>

                <?php

                if ($totalProformas > 20) {

                    echo '<div class="info-box bg-red">';
                } else {

                    echo '<div class="info-box bg-green">';
                }

                ?>

                <!-- <div class="info-box bg-red"> -->
                <span class="info-box-icon"><i class="ion ion-ios-cloud-download-outline"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">Guias</span>
                    <span class="info-box-number">S/ <?php echo number_format($proformas["neto"], 2) ?></span>

                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo number_format($totalProformas, 0) ?>%"></div>
                    </div>
                    <span class="progress-description">
                        <?php echo number_format($totalProformas, 0) ?>% Del mes actual
                    </span>
                </div>

            </div>

        </div>

        <!-- Ventas por pedido y vendedor -->
        <div class="col-lg-4">

            <div class="box box-danger">
                <div class="box-header with-border"></div>
                <center><b>Ventas / Pedidos por Vendedor <button class='btn btn-primary btn-xs btnRptPeds' title='Pedidos' vendedor=''>Pedidos</button></b></center>


                <div class="box-body no-padding">
                    <table class="table table-bordered table-striped dt-responsive tablaVtasGerenciaVdor" width="100%">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Ventas</th>
                                <th>Pedidos</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th style="text-align:right !important;"></th>
                                <th style="text-align:right !important;"></th>
                                <th style="text-align:right !important;"></th>
                            </tr>
                        </tfoot>

                    </table>
                </div>

            </div>

        </div>

        <!-- Cuentas por vendedor -->
        <div class="col-lg-5">

            <div class="box box-danger">
                <div class="box-header with-border"></div>
                <center><b>Cuentas por cobrar - Vendedor</b></center>

                <div class="box-body no-padding">
                    <table class="table table-bordered table-striped dt-responsive tablaCtasVdor" width="100%">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Facturas</th>
                                <th>Guias</th>
                                <th>Letras</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

        <!-- Resumen de gestion -->
        <div class="col-lg-7">

            <div class="box box-primary">
                <div class="box-header with-border"></div>
                <center><b>Resumen de gestión<button class='btn btn-primary btn-xs btnMontoAno' title='Pedidos' vendedor=''>Monto Por Año</button> <button class='btn btn-info btn-xs ml-2 btnRptResCobMes' title='Cobranza' mes=<?php echo isset($_GET["mes"]) ? $_GET["mes"] : "TODO" ?>>Resumen de Cobranza</button></b></center>

                <div class="box-body no-padding">
                    <table class="table table-bordered table-striped dt-responsive tablaRangos" width="100%">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Ventas</th>
                                <th>Cobranza</th>
                                <th>Vencidos</th>
                                <th>2014-2018</th>
                                <th>2019</th>
                                <th>2020</th>
                                <th>2021</th>
                                <th>2022</th>
                                <th>2023</th>
                                <th>2024</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <th></th>
                            <th></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                        </tfoot>
                    </table>
                </div>

            </div>

        </div>

        <!-- Rango dias -->
        <div class="col-lg-5">

            <div class="box box-primary">
                <div class="box-header with-border"></div>
                <center><b>Resumen Rangos
                        <button class='btn btn-danger btn-xs btn180dias' title='Pedidos' vendedor=''>181 Días</button></b></center>

                <div class="box-body no-padding">

                    <table class="table table-bordered table-striped dt-responsive tablaRangosDias" width="100%">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>1 a 30</th>
                                <th>31 a 60</th>
                                <th>61 a 90</th>
                                <th>91 a 120</th>
                                <th>121 a 150</th>
                                <th>151 a 180</th>
                                <th>181 a mas</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <th></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                            <th style="text-align:right !important;"></th>
                        </tfoot>
                    </table>
                </div>

            </div>



        </div>

        <!-- Graficos -->
        <div class="row">

            <div class="col-lg-6">

                <?php

                include "reportes/vtas-ano.php";

                ?>

            </div>




            <div class="col-lg-6">

                <?php

                include "reportes/pagos-ano.php";

                ?>

            </div>


        </div>

    </section>

</div>

<script>
    window.document.title = "Analisis"
</script>