<?php

// Usar los nuevos métodos si se proporciona año, sino usar los métodos antiguos para compatibilidad
$añoConsulta = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : date("Y");
$mesConsulta = isset($_GET["mes"]) && $_GET["mes"] != "TODO" && $_GET["mes"] != "" ? $_GET["mes"] : null;

// Si se proporciona año explícitamente o es diferente al año actual, usar métodos nuevos
if (isset($_GET["año"]) && $_GET["año"] != "" || $añoConsulta != date("Y")) {
    $totales = ControladorMovimientos::ctrTotalesSolesGerencia($añoConsulta, $mesConsulta);
} else {
    // Mantener compatibilidad con código existente
    $totales = ControladorMovimientos::ctrTotalesSoles($mesConsulta);
}

$pedidos = ControladorMovimientos::ctrTotalesSolesPedidos($mesConsulta);

$totalesInicio = ModeloMovimientos::mdlTotalesInicio();

?>



<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-blue">

        <div class="inner">

            <h3>S/ <?php echo number_format($totales["vtas_soles"], 0); ?></h3>

            <p>Ventas - Soles</p>

        </div>

        <div class="icon">

            <i class="fa fa-cart-arrow-down"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>

<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-aqua">

        <div class="inner">

            <h3>S/ <?php echo number_format($pedidos["total"], 0); ?></h3>

            <p>Pedidos - Soles</p>

        </div>

        <div class="icon">

            <i class="fa fa-id-card-o"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>

<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-green">

        <div class="inner">

            <h3>S/<?php echo number_format($totales["pagos_soles"], 0); ?></h3>

            <p>Cobranza - Soles</p>

        </div>

        <div class="icon">

            <i class="fa fa-tags"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>


<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-yellow">

        <div class="inner">

            <h3>S/<?php echo number_format($totalesInicio["total_vencidos_cuentas"] - $totalesInicio["total_vencidos_180_cuentas"], 0); ?></h3>

            <p>Documentos Vencidos - Soles</p>

        </div>

        <div class="icon">

            <i class="fa fa-asterisk"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>

<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-orange">

        <div class="inner">

            <h3>S/<?php echo number_format($totalesInicio["total_vencidos_180_cuentas"], 0); ?></h3>

            <p>Documentos Vencidos - 180 días</p>

        </div>

        <div class="icon">

            <i class="fa fa-exclamation-circle"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>

<div class="col-lg-2 col-xs-6">

    <div class="small-box bg-red">

        <div class="inner">

            <h3>S/<?php echo number_format($totalesInicio["incobrable_cuentas"], 0); ?></h3>

            <p>Documentos Incobrables - Soles</p>

        </div>

        <div class="icon">

            <i class="fa fa-times"></i>

        </div>

        <a href="#" class="small-box-footer">

            Más info <i class="fa fa-arrow-circle-right"></i>

        </a>

    </div>

</div>