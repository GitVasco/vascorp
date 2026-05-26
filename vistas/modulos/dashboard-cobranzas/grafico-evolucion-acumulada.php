<div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-warning">
        <div class="box-header with-border" style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <h3 class="box-title" style="margin: 0;">Evolución acumulada del mes</h3>
            <div class="dc-evolucion-leyenda" id="dcGraficoEvolucionLeyenda" aria-label="Leyenda del gráfico">
                <span class="dc-evolucion-leyenda__item">
                    <i class="dc-evolucion-leyenda__swatch" style="background-color: #3d9970;"></i>
                    <b>2025</b> acumulado
                </span>
                <span class="dc-evolucion-leyenda__item">
                    <i class="dc-evolucion-leyenda__swatch" style="background-color: #3c8dbc;"></i>
                    <b>2026</b> acumulado
                </span>
            </div>
        </div>
        <style>
            .dc-evolucion-leyenda {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 14px;
                font-size: 12px;
                color: #475569;
            }

            .dc-evolucion-leyenda__item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .dc-evolucion-leyenda__swatch {
                display: inline-block;
                width: 22px;
                height: 4px;
                border-radius: 2px;
                vertical-align: middle;
            }
        </style>
        <div class="box-body">
            <div id="dcGraficoEvolucionLoading" class="text-center text-muted" style="padding: 80px 0;">
                Cargando gráfico…
            </div>
            <div class="chart-responsive dc-grafico-evolucion-wrap" id="dcGraficoEvolucionWrap" style="display: none; height: 280px; min-height: 280px;">
                <canvas id="dcGraficoEvolucionCanvas"></canvas>
            </div>
        </div>
    </div>
</div>
