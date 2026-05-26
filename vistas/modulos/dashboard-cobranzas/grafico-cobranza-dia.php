<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Cobranza por día del mes</h3>
            <span class="label label-default" id="dcGraficoDiaPromedio" style="margin-left: 8px; font-weight: normal;">
                Promedio diario: —
            </span>
        </div>
        <div class="box-body">
            <div id="dcGraficoCobranzaDiaLoading" class="text-center text-muted" style="padding: 80px 0;">
                Cargando gráfico…
            </div>
            <div class="chart-responsive dc-grafico-cobranza-dia-wrap" id="dcGraficoCobranzaDiaWrap" style="display: none;">
                <div class="chart" id="dc-grafico-cobranza-dia" style="height: 300px;"></div>
            </div>
            <style>
                .dc-grafico-cobranza-dia-wrap .morris-chart {
                    width: 100%;
                }

                .dc-grafico-cobranza-dia-wrap svg {
                    width: 100% !important;
                    max-height: 300px;
                }
            </style>
        </div>
    </div>
</div>
