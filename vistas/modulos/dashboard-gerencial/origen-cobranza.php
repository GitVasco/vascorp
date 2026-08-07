<div class="box box-warning dg-bloque dg-bloque-origen-cobranza">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-sitemap"></i>
            Origen de la cobranza
            <small>sin IGV</small>
            <small id="dgOrigenCobranzaLabel"></small>
        </h3>
    </div>
    <div class="box-body">
        <div class="dg-periodos-resumen dg-origen-kpis">
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--delta">
                    <div class="dg-resumen-label">Cobrado período</div>
                    <div class="dg-resumen-rango" id="dgOrigenPeriodoRango">—</div>
                    <div class="dg-resumen-valor" id="dgOrigenTotal">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a-blue">
                    <div class="dg-resumen-label">% Cobro mismo mes</div>
                    <div class="dg-resumen-rango">Origen = mes pago</div>
                    <div class="dg-resumen-valor" id="dgOrigenPctRecup">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Mismo mes</div>
                    <div class="dg-resumen-rango">Monto</div>
                    <div class="dg-resumen-valor" id="dgOrigenMismoMes">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Con origen</div>
                    <div class="dg-resumen-rango">% trazable</div>
                    <div class="dg-resumen-valor" id="dgOrigenPctConOrigen">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a">
                    <div class="dg-resumen-label">Ventas período</div>
                    <div class="dg-resumen-rango" id="dgOrigenVentaRango">—</div>
                    <div class="dg-resumen-valor" id="dgOrigenVentaPeriodo">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card">
                    <div class="dg-resumen-label">Recuperado</div>
                    <div class="dg-resumen-rango">Hasta hoy</div>
                    <div class="dg-resumen-valor" id="dgOrigenRecupPeriodo">—</div>
                </div>
            </div>
            <div class="dg-periodos-resumen__item">
                <div class="dg-resumen-card dg-resumen-card--a-blue">
                    <div class="dg-resumen-label">% Recuperado</div>
                    <div class="dg-resumen-rango">Recup. / ventas</div>
                    <div class="dg-resumen-valor" id="dgOrigenPctRecupPeriodo">—</div>
                </div>
            </div>
        </div>

        <p class="dg-origen-hint text-muted" id="dgOrigenFormula"></p>

        <div class="row dg-fila-doble dg-origen-fila-espejo">
            <div class="col-md-6 col-sm-12">
                <h4 class="dg-subtitulo">De lo cobrado: mes origen <small>≥2% · resto en Otros</small></h4>
                <p class="dg-origen-hint text-muted">Del cobro del período, ¿de qué mes eran las facturas?</p>
                <div class="table-responsive dg-origen-mes-wrap">
                    <table class="table table-condensed table-hover dg-tabla dg-tabla-barras" id="dgTablaOrigenCobranza">
                        <thead>
                            <tr>
                                <th style="width: 72px;">Mes origen</th>
                                <th class="dg-col-barra">Dist.</th>
                                <th class="text-right" style="width: 84px;">Monto</th>
                                <th class="text-right" style="width: 84px;">Acum.</th>
                                <th class="text-right" style="width: 44px;">%</th>
                                <th class="text-right" style="width: 56px;">% acum.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="dg-fila-total">
                                <th colspan="2">Total cobrado</th>
                                <th class="text-right" id="dgOrigenFootTotal">—</th>
                                <th class="text-right" id="dgOrigenFootAcum">—</th>
                                <th class="text-right">100%</th>
                                <th class="text-right" id="dgOrigenFootPctAcum">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <h4 class="dg-subtitulo">
                    De lo vendido: mes recuperación <small>≥2% · resto en Otros</small>
                    <button type="button" class="btn btn-xs btn-warning dg-btn-pendiente-cxc" id="dgBtnPendienteCxc" title="Ver cuentas por cobrar pendientes">
                        <i class="fa fa-list"></i> CxC pendientes
                    </button>
                </h4>
                <p class="dg-origen-hint text-muted" id="dgRecupHint">De las ventas del período, ¿en qué mes se cobraron?</p>
                <div class="table-responsive dg-origen-mes-wrap">
                    <table class="table table-condensed table-hover dg-tabla dg-tabla-barras" id="dgTablaRecuperacionVenta">
                        <thead>
                            <tr>
                                <th style="width: 72px;">Mes pago</th>
                                <th class="dg-col-barra">Dist.</th>
                                <th class="text-right" style="width: 84px;">Monto</th>
                                <th class="text-right" style="width: 84px;">Acum.</th>
                                <th class="text-right" style="width: 44px;" title="Sobre ventas del período">%</th>
                                <th class="text-right" style="width: 56px;" title="Acumulado sobre ventas del período">% acum.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="dg-fila-total">
                                <th colspan="2">Total recuperado</th>
                                <th class="text-right" id="dgRecupFootTotal">—</th>
                                <th class="text-right" id="dgRecupFootAcum">—</th>
                                <th class="text-right" id="dgRecupFootPct">—</th>
                                <th class="text-right" id="dgRecupFootPctAcum">—</th>
                            </tr>
                            <tr class="dg-fila-pendiente">
                                <th colspan="2">Pendiente</th>
                                <th class="text-right" id="dgRecupFootPendiente">—</th>
                                <th class="text-right">—</th>
                                <th class="text-right" id="dgRecupFootPendPct">—</th>
                                <th class="text-right">—</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row dg-fila-doble dg-origen-fila-3">
            <div class="col-md-6 col-sm-12">
                <h4 class="dg-subtitulo">% Cobro mismo mes</h4>
                <div class="dg-chart-wrap dg-chart-wrap--origen-sm">
                    <canvas id="dgGraficoPctMismoMesAnio"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgPctMismoMesEmpty">Sin datos</p>
                </div>
                <p class="dg-origen-hint text-muted">mismo mes / total cobrado del mes</p>
            </div>
            <div class="col-md-6 col-sm-12">
                <h4 class="dg-subtitulo">Recuperado vs pendiente</h4>
                <div class="dg-chart-wrap dg-chart-wrap--origen-sm">
                    <canvas id="dgGraficoVentasVsRecup"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgVentasVsRecupEmpty">Sin datos</p>
                </div>
                <p class="dg-origen-hint text-muted">verde recuperado · gris pendiente</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4 class="dg-subtitulo">Recuperación del año <small>detalle mes a mes</small></h4>
                <div class="table-responsive dg-origen-tabla-mes">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaRecupMensual">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="text-right">Ventas</th>
                                <th class="text-right">Recuperado</th>
                                <th class="text-right">Pendiente</th>
                                <th class="text-right">% recup.</th>
                                <th class="text-right">Cobrado</th>
                                <th class="text-right">Mismo mes</th>
                                <th class="text-right">% cobro mismo mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        /*
         * Ocultos por ahora — reactivar cuando se defina el momento de mostrarlos.
         * Backend/JS siguen cargando datos (aging + por_vendedor).
         *
        <h4 class="dg-subtitulo">Antigüedad del cobro</h4>
        <div class="row dg-origen-aging">
            <div class="col-md-7 col-sm-12">
                <div class="dg-chart-wrap dg-chart-wrap--origen-sm">
                    <canvas id="dgGraficoAgingCobranza"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgAgingEmpty">Sin cobranzas en el período</p>
                </div>
            </div>
            <div class="col-md-5 col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaAging">
                        <thead>
                            <tr>
                                <th>Rango</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">%</th>
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
                                <th class="text-right" id="dgAgingFootTotal">—</th>
                                <th class="text-right">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="dg-origen-vendedores is-hidden" id="dgOrigenVendedoresBox">
            <h4 class="dg-subtitulo">Por vendedor <small>(top 15 · Todos)</small></h4>
            <div class="table-responsive">
                <table class="table table-condensed table-hover dg-tabla" id="dgTablaOrigenVendedor">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th class="text-right">Cobranza</th>
                            <th class="text-right">Mismo mes</th>
                            <th class="text-right">% cobro mismo mes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-muted">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        */
        ?>
    </div>
</div>
