<?php
if (!function_exists('usuarioPuedeVerModulo') || !usuarioPuedeVerModulo('gestion_comercial', 'dashboard_gerencial')) {
    denegarAccesoModulo();
    return;
}
?>

<div class="content-wrapper dg-dashboard">
    <section class="content-header">
        <h1>
            Dashboard Gerencial
            <small>Ventas y cobranzas</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Dashboard Gerencial</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">En construcción</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted" style="margin-bottom: 12px;">
                            Vista nueva según
                            <code>docs/comercial/dashboard-gerencial/PLAN_DASHBOARD_GERENCIAL.md</code>.
                        </p>
                        <ol class="dg-plan-lista">
                            <li>Ventas mes a mes</li>
                            <li>Ventas vs año pasado</li>
                            <li>Ventas períodos específicos</li>
                            <li>Cobranzas (mes a mes / vs N-1 / períodos)</li>
                            <li>Origen de cobranza / tasa de recuperación</li>
                            <li>Origen global y por vendedor</li>
                            <li>Proyección de cobranzas</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
