<div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-primary">
        <div class="box-header with-border" style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <h3 class="box-title" style="margin: 0;">Cobranza por mes (comparativo)</h3>
            <div class="dc-evolucion-leyenda">
                <span class="dc-evolucion-leyenda__item">
                    <i class="dc-evolucion-leyenda__swatch" style="background-color: #3d9970;"></i>
                    <b>2025</b>
                </span>
                <span class="dc-evolucion-leyenda__item">
                    <i class="dc-evolucion-leyenda__swatch" style="background-color: #3c8dbc;"></i>
                    <b>2026</b>
                </span>
            </div>
            <span class="label label-default" id="dcGraficoComparativoTotales" style="font-weight: normal;">
                —
            </span>
        </div>
        <div class="box-body">
            <div id="dcGraficoComparativoLoading" class="text-center text-muted" style="padding: 80px 0;">
                Cargando gráfico…
            </div>
            <div class="chart-responsive dc-grafico-comparativo-wrap" id="dcGraficoComparativoWrap" style="display: none;">
                <div class="chart" id="dc-grafico-comparativo-mes" style="height: 280px; min-height: 280px;"></div>
            </div>
        </div>
    </div>
</div>
