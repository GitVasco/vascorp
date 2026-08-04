<div class="box box-primary dg-bloque dg-bloque--compacto dg-bloque-ventas-vs-anio">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-exchange"></i>
            Ventas vs año pasado
            <small id="dgVentasVsAnioLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-chart-wrap dg-chart-wrap--compact">
            <canvas id="dgGraficoVentasVsAnio" height="220"></canvas>
            <p class="dg-chart-empty text-muted is-hidden" id="dgVentasVsAnioEmpty">Sin ventas para comparar</p>
        </div>
        <div class="table-responsive">
            <table class="table table-condensed table-hover dg-tabla" id="dgTablaVentasVsAnio">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-right" id="dgThVentaN">N</th>
                        <th class="text-right" id="dgThVentaN1">N-1</th>
                        <th class="text-right">Δ</th>
                        <th class="text-right">Δ%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-muted">Cargando…</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="dg-fila-total">
                        <th>Total</th>
                        <th class="text-right" id="dgVentasVsTotalN">—</th>
                        <th class="text-right" id="dgVentasVsTotalN1">—</th>
                        <th class="text-right" id="dgVentasVsTotalDelta">—</th>
                        <th class="text-right" id="dgVentasVsTotalDeltaPct">—</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
