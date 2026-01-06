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

        <div class="col-lg-12">

            <?php

            setlocale(LC_ALL, "es_ES");

            // Si no hay parámetros GET, redirigir con año y mes actual
            if (!isset($_GET["año"]) && !isset($_GET["mes"])) {
                $añoActual = date("Y");
                $mesActual = date("n");
                $url = "index.php?ruta=inicio-gerencia&año=" . $añoActual . "&mes=" . $mesActual;
                echo "<script>window.location.href = '" . $url . "';</script>";
                exit;
            }

            // Obtener año y mes de los parámetros GET, por defecto año y mes actual
            $añoActual = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : date("Y");
            // Si no hay mes en GET o es vacío, usar mes actual. Si es "TODO", mantenerlo
            if (isset($_GET["mes"]) && $_GET["mes"] != "") {
                $mesActual = $_GET["mes"] == "TODO" ? "TODO" : intval($_GET["mes"]);
            } else {
                $mesActual = date("n"); // Mes actual (1-12)
            }

            if (isset($_GET["mes"]) && $_GET["mes"] != "TODO" && $_GET["mes"] != "") {

                $mesN = $_GET["mes"];

                $nomMes = ControladorTalleres::ctrMesB($mesN);
                $nomMesA = $nomMes["descripcion"];
            } else {

                $nomMesA = "TODOS";
            }

            #var_dump($nomMesA);

            echo '<div class="box box-success">

                    <div class="box-header" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px;">

                        <div style="flex: 1;">
                            <h1 style="margin: 0; line-height: 1.2;">Bienvenid@ ' . $_SESSION["nombre"] . ' - AÑO: <b>' . $añoActual . '</b> - MES: <b>' . $nomMesA . '</b></h1>
                        </div>

                        <div style="display: flex; gap: 15px; align-items: flex-start; margin-left: 20px;">
                            <div style="min-width: 150px;">
                                <label for="añoGerencia" style="font-weight: bold; margin-bottom: 5px; display: block; font-size: 12px;">Año:</label>
                                <select class="form-control selectpicker" id="añoGerencia" name="añoGerencia" data-live-search="true" style="height: 38px;">

                                    <option value="">Seleccionar Año</option>
                                    <option value="2024" ' . (($añoActual == 2024) ? 'selected' : '') . '>2024</option>
                                    <option value="2025" ' . (($añoActual == 2025) ? 'selected' : '') . '>2025</option>
                                    <option value="2026" ' . (($añoActual == 2026) ? 'selected' : '') . '>2026</option>

                                </select>
                            </div>

                            <div style="min-width: 150px;">
                                <label for="mesGerencia" style="font-weight: bold; margin-bottom: 5px; display: block; font-size: 12px;">Mes:</label>
                                <select class="form-control selectpicker" id="mesGerencia" name="mesGerencia" data-live-search="true" style="height: 38px;">

                                    <option value="">Seleccionar Mes</option>
                                    <option value="TODO" ' . (($mesActual == null || $mesActual == "TODO") ? 'selected' : '') . '>TODO</option>
                                    <option value="1" ' . (($mesActual == 1) ? 'selected' : '') . '>ENERO</option>
                                    <option value="2" ' . (($mesActual == 2) ? 'selected' : '') . '>FEBRERO</option>
                                    <option value="3" ' . (($mesActual == 3) ? 'selected' : '') . '>MARZO</option>
                                    <option value="4" ' . (($mesActual == 4) ? 'selected' : '') . '>ABRIL</option>
                                    <option value="5" ' . (($mesActual == 5) ? 'selected' : '') . '>MAYO</option>
                                    <option value="6" ' . (($mesActual == 6) ? 'selected' : '') . '>JUNIO</option>
                                    <option value="7" ' . (($mesActual == 7) ? 'selected' : '') . '>JULIO</option>
                                    <option value="8" ' . (($mesActual == 8) ? 'selected' : '') . '>AGOSTO</option>
                                    <option value="9" ' . (($mesActual == 9) ? 'selected' : '') . '>SEPTIEMBRE</option>
                                    <option value="10" ' . (($mesActual == 10) ? 'selected' : '') . '>OCTUBRE</option>
                                    <option value="11" ' . (($mesActual == 11) ? 'selected' : '') . '>NOVIEMBRE</option>
                                    <option value="12" ' . (($mesActual == 12) ? 'selected' : '') . '>DICIEMBRE</option>

                                </select>
                            </div>
                        </div>

                    </div>

                 </div>';


            ?>

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

                // Usar los nuevos métodos si se proporciona año, sino usar los métodos antiguos para compatibilidad
                $añoConsulta = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : date("Y");
                $mesConsulta = isset($_GET["mes"]) && $_GET["mes"] != "TODO" && $_GET["mes"] != "" ? $_GET["mes"] : null;

                // Si se proporciona año explícitamente o es diferente al año actual, usar métodos nuevos
                if (isset($_GET["año"]) && $_GET["año"] != "" || $añoConsulta != date("Y")) {
                    $totales = ControladorMovimientos::ctrTotalesSolesGerencia($añoConsulta, $mesConsulta);
                    $facturas = ControladorMovimientos::ctrFacturasGerencia($añoConsulta, $mesConsulta);
                    $proformas = ControladorMovimientos::ctrProformasGerencia($añoConsulta, $mesConsulta);
                } else {
                    // Mantener compatibilidad con código existente
                    $totales = ControladorMovimientos::ctrTotalesSoles($mesConsulta);
                    $facturas = ControladorMovimientos::ctrFacturas($mesConsulta);
                    $proformas = ControladorMovimientos::ctrProformas($mesConsulta);
                }

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