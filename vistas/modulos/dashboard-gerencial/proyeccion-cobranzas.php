<div class="box box-success dg-bloque dg-bloque-proyeccion">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-calendar-check-o"></i>
            Cobertura de proyección
            <small>sin IGV</small>
            <small id="dgProyeccionLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-periodos-resumen">
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a-blue">
                    <div class="dg-resumen-label">Proyección del mes</div>
                    <div class="dg-resumen-rango" id="dgProyMesRango">Por vencer</div>
                    <div class="dg-resumen-valor" id="dgProyMesValor">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a">
                    <div class="dg-resumen-label">Real del mes</div>
                    <div class="dg-resumen-rango">Cobranza efectiva</div>
                    <div class="dg-resumen-valor" id="dgProyRealValor">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--delta">
                    <div class="dg-resumen-label">% Cobertura</div>
                    <div class="dg-resumen-rango">Real / proyección</div>
                    <div class="dg-resumen-valor" id="dgProyPctValor">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Vencido pendiente</div>
                    <div class="dg-resumen-rango">Al corte</div>
                    <div class="dg-resumen-valor" id="dgProyVencidoValor">—</div>
                </div>
            </div>
        </div>

        <p class="dg-origen-hint text-muted" id="dgProyeccionNota"></p>

        <div class="row" style="margin-top: 8px;">
            <div class="col-md-6 col-sm-12">
                <div class="dg-chart-wrap">
                    <canvas id="dgGraficoProyeccion" height="280"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgProyeccionEmpty">Sin proyección en el horizonte</p>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaProyeccion">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th class="text-right">Proyección</th>
                                <th class="text-right">Real</th>
                                <th class="text-right">Δ</th>
                                <th class="text-right">Docs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="dg-fila-total">
                                <th>Próx. meses</th>
                                <th class="text-right" id="dgProyFootProy">—</th>
                                <th class="text-right" id="dgProyFootReal">—</th>
                                <th class="text-right" id="dgProyFootDelta">—</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="dg-proy-extra" id="dgProyExtra"></div>
            </div>
        </div>
    </div>
</div>
