<div class="content-wrapper dc-dashboard-cobranzas">
    <section class="content-header">
        <h1>
            Dashboard de Cobranzas
            <small>Resumen</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Dashboard de Cobranzas</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
            <?php
            if (!isset($_GET["año"]) && !isset($_GET["mes"])) {
                $añoRedirect = date("Y");
                $mesRedirect = date("n");
                $url = "index.php?ruta=dashboard-cobranzas&año=" . $añoRedirect . "&mes=" . $mesRedirect;
                echo "<script>window.location.href = '" . $url . "';</script>";
                exit;
            }

            $añoActual = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : date("Y");
            $mesActual = isset($_GET["mes"]) && $_GET["mes"] != "" ? intval($_GET["mes"]) : date("n");
            $annosPermitidos = array(2025, 2026);

            if (!in_array($añoActual, $annosPermitidos, true)) {
                $añoActual = in_array((int) date("Y"), $annosPermitidos, true)
                    ? (int) date("Y")
                    : 2026;
            }
            $vendedorActual = isset($_GET["vendedor"]) ? trim($_GET["vendedor"]) : "";

            $meses = [
                1 => "ENERO",
                2 => "FEBRERO",
                3 => "MARZO",
                4 => "ABRIL",
                5 => "MAYO",
                6 => "JUNIO",
                7 => "JULIO",
                8 => "AGOSTO",
                9 => "SEPTIEMBRE",
                10 => "OCTUBRE",
                11 => "NOVIEMBRE",
                12 => "DICIEMBRE",
            ];

            $nomMesA = isset($meses[$mesActual]) ? $meses[$mesActual] : "";
            $labelVendedor = "TODOS";

            $vendedores = ControladorDashboardCobranzas::ctrVendedoresFiltro($añoActual);

            foreach ($vendedores as $v) {
                if ($vendedorActual !== "" && $v["codigo"] === $vendedorActual) {
                    $labelVendedor = $v["codigo"] . " - " . $v["descripcion"];
                    break;
                }
            }

            if ($vendedorActual !== "" && $labelVendedor === "TODOS") {
                $labelVendedor = $vendedorActual;
            }

            ?>

            <div class="box box-primary">
                <div class="box-header" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; flex-wrap: wrap; gap: 12px;">
                    <div style="flex: 1; min-width: 220px;">
                        <h3 style="margin: 0; line-height: 1.3;">
                            Período: <b><?php echo $añoActual; ?></b> — <b><?php echo $nomMesA; ?></b>
                            <span style="font-size: 14px; font-weight: normal;"> · Vendedor: <b><?php echo htmlspecialchars($labelVendedor); ?></b></span>
                        </h3>
                    </div>

                    <div style="display: flex; gap: 15px; align-items: flex-start; flex-wrap: wrap;">
                        <div style="min-width: 140px;">
                            <label for="añoCobranzas" style="font-weight: bold; margin-bottom: 5px; display: block; font-size: 12px;">Año</label>
                            <select class="form-control selectpicker" id="añoCobranzas" name="añoCobranzas" data-live-search="true">
                                <option value="">Seleccionar año</option>
                                <?php foreach ([2025, 2026] as $año) : ?>
                                    <option value="<?php echo $año; ?>" <?php echo ($añoActual == $año) ? "selected" : ""; ?>><?php echo $año; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="min-width: 160px;">
                            <label for="mesCobranzas" style="font-weight: bold; margin-bottom: 5px; display: block; font-size: 12px;">Mes</label>
                            <select class="form-control selectpicker" id="mesCobranzas" name="mesCobranzas" data-live-search="true">
                                <option value="">Seleccionar mes</option>
                                <?php foreach ($meses as $num => $nombre) : ?>
                                    <option value="<?php echo $num; ?>" <?php echo ($mesActual == $num) ? "selected" : ""; ?>><?php echo $nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="min-width: 220px;">
                            <label for="vendedorCobranzas" style="font-weight: bold; margin-bottom: 5px; display: block; font-size: 12px;">Vendedor</label>
                            <select class="form-control selectpicker" id="vendedorCobranzas" name="vendedorCobranzas" data-live-search="true" data-size="8">
                                <option value="" <?php echo ($vendedorActual === "") ? "selected" : ""; ?>>TODOS</option>
                                <?php foreach ($vendedores as $v) : ?>
                                    <option value="<?php echo htmlspecialchars($v["codigo"]); ?>" <?php echo ($vendedorActual === $v["codigo"]) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($v["codigo"] . " - " . $v["descripcion"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $kpisCobranzas = ControladorDashboardCobranzas::ctrKpisSuperiores(
                $añoActual,
                $mesActual,
                $vendedorActual
            );
            ?>

            <div id="dashboardCobranzasData"
                data-anno="<?php echo $añoActual; ?>"
                data-mes="<?php echo $mesActual; ?>"
                data-vendedor="<?php echo htmlspecialchars($vendedorActual); ?>"
                data-vendedor-top="<?php echo htmlspecialchars(isset($kpisCobranzas["mejor_vendedor_codigo"]) ? $kpisCobranzas["mejor_vendedor_codigo"] : ""); ?>">
            </div>

            <?php
            include "dashboard-cobranzas/cajas-superiores.php";

            include "dashboard-cobranzas/graficos-fila.php";

            echo '<div class="row dc-graficos-cobranzas dc-graficos-fila-inferior">';
            include __DIR__ . "/dashboard-cobranzas/grafico-evolucion-acumulada.php";
            include __DIR__ . "/dashboard-cobranzas/grafico-comparativo-mes.php";
            include __DIR__ . "/dashboard-cobranzas/grafico-top-vendedores.php";
            echo '</div>';
            ?>

            </div>
        </div>
    </section>
</div>

<style>
    .dc-dashboard-cobranzas .content {
        padding-bottom: 48px;
    }

    .dc-dashboard-cobranzas .dc-graficos-fila-inferior {
        margin-bottom: 8px;
    }

    .dc-dashboard-cobranzas .dc-graficos-fila-inferior .box {
        margin-bottom: 0;
    }

    .dc-dashboard-cobranzas .dc-graficos-fila-inferior .box-body {
        overflow: visible;
        padding-bottom: 12px;
    }

    @media (min-width: 1200px) {
        .dc-dashboard-cobranzas .dc-graficos-fila-inferior .box-body {
            min-height: 340px;
        }
    }
</style>
