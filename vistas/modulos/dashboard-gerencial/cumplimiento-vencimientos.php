<div class="box box-warning dg-bloque dg-bloque-puntualidad">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-check-circle"></i>
            Cumplimiento de vencimientos
            <small>sin IGV</small>
            <small id="dgPuntualidadLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-periodos-resumen">
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--delta">
                    <div class="dg-resumen-label">Venció en período</div>
                    <div class="dg-resumen-rango" id="dgPuntPeriodoRango">—</div>
                    <div class="dg-resumen-valor" id="dgPuntTotal">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a">
                    <div class="dg-resumen-label">% A tiempo</div>
                    <div class="dg-resumen-rango">Pago ≤ vencimiento</div>
                    <div class="dg-resumen-valor" id="dgPuntPctATiempo">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">A tiempo</div>
                    <div class="dg-resumen-rango" id="dgPuntDocsATiempo">—</div>
                    <div class="dg-resumen-valor" id="dgPuntATiempo">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Atrasado</div>
                    <div class="dg-resumen-rango" id="dgPuntDocsAtrasado">—</div>
                    <div class="dg-resumen-valor" id="dgPuntAtrasado">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Pendiente</div>
                    <div class="dg-resumen-rango" id="dgPuntDocsPendiente">—</div>
                    <div class="dg-resumen-valor" id="dgPuntPendiente">—</div>
                </div>
            </div>
        </div>

        <p class="dg-origen-hint text-muted" id="dgPuntualidadFormula"></p>

        <div class="row dg-fila-doble" style="margin-top: 8px;">
            <div class="col-md-6 col-sm-12">
                <div class="dg-chart-wrap dg-chart-wrap--periodos">
                    <canvas id="dgGraficoPuntualidad"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgPuntualidadEmpty">Sin vencimientos en el año</p>
                </div>
                <p class="dg-origen-hint text-muted">Barra = monto que vencía · verde a tiempo · naranja atrasado · gris pendiente</p>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaPuntualidad">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="text-right">Venció</th>
                                <th class="text-right">A tiempo</th>
                                <th class="text-right">Atrasado</th>
                                <th class="text-right">Pendiente</th>
                                <th class="text-right">% a tiempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
