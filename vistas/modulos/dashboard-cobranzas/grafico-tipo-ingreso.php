<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Distribución por tipo de ingreso</h3>
            <span class="label label-default" id="dcTipoIngresoPeriodo" style="margin-left: 8px; font-weight: normal;">
                —
            </span>
        </div>
        <div class="box-body dc-tipo-ingreso-body" style="padding-top: 8px; min-height: 240px;">
            <div id="dcTipoIngresoLoading" class="text-center text-muted" style="padding: 72px 0;">
                Cargando gráfico…
            </div>
            <div id="dcTipoIngresoWrap" style="display: none;">
                <div class="dc-tipo-ingreso-chart-wrap" id="dcTipoIngresoChartWrap">
                    <canvas id="dcGraficoTipoIngresoCanvas" width="220" height="200"></canvas>
                </div>
                <ul class="dc-tipo-ingreso-leyenda" id="dcTipoIngresoLeyenda"></ul>
                <p class="dc-tipo-ingreso-total text-muted" id="dcTipoIngresoTotal"></p>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-tipo-ingreso-chart-wrap {
        position: relative;
        width: 220px;
        height: 200px;
        margin: 0 auto 4px;
    }

    .dc-tipo-ingreso-chart-wrap canvas {
        display: block !important;
        width: 220px !important;
        height: 200px !important;
    }

    .dc-tipo-ingreso-leyenda {
        list-style: none;
        margin: 14px 0 0;
        padding: 0;
        font-size: 12px;
    }

    .dc-tipo-ingreso-leyenda li {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .dc-tipo-ingreso-leyenda li:last-child {
        border-bottom: none;
    }

    .dc-tipo-ingreso-leyenda__swatch {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
    }

    .dc-tipo-ingreso-leyenda__label {
        flex: 1;
        font-weight: 600;
        color: #334155;
    }

    .dc-tipo-ingreso-leyenda__pct {
        font-weight: 700;
        color: #64748b;
        min-width: 42px;
        text-align: right;
    }

    .dc-tipo-ingreso-leyenda__monto {
        font-weight: 600;
        color: #1e293b;
        min-width: 72px;
        text-align: right;
    }

    .dc-tipo-ingreso-total {
        margin: 10px 0 0;
        font-size: 11px;
        text-align: center;
    }
</style>
