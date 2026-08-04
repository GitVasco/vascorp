<div class="box box-info dg-bloque dg-bloque--compacto dg-bloque-cobranzas-vs-anio">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-exchange"></i>
            Cobranzas vs año pasado
            <small>sin IGV</small>
            <small id="dgCobranzasVsAnioLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-chart-wrap dg-chart-wrap--compact">
            <canvas id="dgGraficoCobranzasVsAnio" height="220"></canvas>
            <p class="dg-chart-empty text-muted is-hidden" id="dgCobranzasVsAnioEmpty">Sin cobranzas para comparar</p>
        </div>
        <div class="table-responsive">
            <table class="table table-condensed table-hover dg-tabla" id="dgTablaCobranzasVsAnio">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-right" id="dgThCobranzaN">N</th>
                        <th class="text-right" id="dgThCobranzaN1">N-1</th>
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
                        <th class="text-right" id="dgCobranzasVsTotalN">—</th>
                        <th class="text-right" id="dgCobranzasVsTotalN1">—</th>
                        <th class="text-right" id="dgCobranzasVsTotalDelta">—</th>
                        <th class="text-right" id="dgCobranzasVsTotalDeltaPct">—</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
