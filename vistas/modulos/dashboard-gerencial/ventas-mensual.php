<div class="box box-primary dg-bloque dg-bloque--compacto dg-bloque-ventas-mensual">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-bar-chart"></i>
            Ventas mes a mes
            <small id="dgVentasMensualAnioLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-chart-wrap dg-chart-wrap--compact">
            <canvas id="dgGraficoVentasMensual" height="220"></canvas>
            <p class="dg-chart-empty text-muted is-hidden" id="dgVentasMensualEmpty">Sin ventas en el período</p>
        </div>
        <div class="table-responsive">
            <table class="table table-condensed table-hover dg-tabla" id="dgTablaVentasMensual">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-right">Venta</th>
                        <th class="text-right">% año</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="text-muted">Cargando…</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="dg-fila-total">
                        <th>Total</th>
                        <th class="text-right" id="dgVentasMensualTotal">—</th>
                        <th class="text-right">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
