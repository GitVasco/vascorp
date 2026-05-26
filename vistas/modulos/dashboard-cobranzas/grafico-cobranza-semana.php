<div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">Cobranza promedio por semana</h3>
        </div>
        <div class="box-body">
            <div id="dcGraficoCobranzaSemanaLoading" class="text-center text-muted" style="padding: 80px 0;">
                Cargando gráfico…
            </div>
            <div class="chart-responsive dc-grafico-cobranza-semana-wrap" id="dcGraficoCobranzaSemanaWrap" style="display: none; height: 300px;">
                <canvas id="dcGraficoCobranzaSemanaCanvas"></canvas>
            </div>
            <style>
                .dc-grafico-cobranza-semana-wrap canvas {
                    max-height: 300px;
                }
            </style>
        </div>
    </div>
</div>
