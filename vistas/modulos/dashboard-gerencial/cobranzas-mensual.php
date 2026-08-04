<div class="box box-info dg-bloque dg-bloque--compacto dg-bloque-cobranzas-mensual">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-bar-chart"></i>
            Cobranzas mes a mes
            <small>sin IGV</small>
            <small id="dgCobranzasMensualAnioLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-chart-wrap dg-chart-wrap--compact">
            <canvas id="dgGraficoCobranzasMensual" height="220"></canvas>
            <p class="dg-chart-empty text-muted is-hidden" id="dgCobranzasMensualEmpty">Sin cobranzas en el período</p>
        </div>
        <div class="table-responsive">
            <table class="table table-condensed table-hover dg-tabla" id="dgTablaCobranzasMensual">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-right">Cobranza</th>
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
                        <th class="text-right" id="dgCobranzasMensualTotal">—</th>
                        <th class="text-right">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
