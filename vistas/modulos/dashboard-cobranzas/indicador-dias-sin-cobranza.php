<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">Días sin cobranza</h3>
            <span class="label label-default" id="dcDiasSinPeriodo" style="margin-left: 8px; font-weight: normal;">
                —
            </span>
            <span class="label label-success" style="margin-left: 4px; font-weight: normal; font-size: 10px;">
                Solo efectivo
            </span>
        </div>
        <div class="box-body dc-dias-sin-body" style="padding-top: 4px; min-height: 240px;">
            <div id="dcDiasSinLoading" class="text-center text-muted" style="padding: 72px 0;">
                Cargando indicador…
            </div>
            <div id="dcDiasSinWrap" style="display: none;">
                <div class="dc-gauge" aria-hidden="true">
                    <svg
                        class="dc-gauge__svg"
                        id="dcGaugeSvg"
                        viewBox="0 0 220 130"
                        width="180"
                        height="120"
                        role="img"
                        aria-label="Indicador de días sin cobranza">
                        <g id="dcGaugeZonas"></g>
                        <line
                            id="dcGaugeNeedle"
                            x1="110"
                            y1="108"
                            x2="110"
                            y2="48"
                            stroke="#1e293b"
                            stroke-width="2.5"
                            stroke-linecap="round" />
                        <circle cx="110" cy="108" r="5" fill="#1e293b" />
                        <text x="28" y="122" class="dc-gauge__tick">0</text>
                        <text x="188" y="122" class="dc-gauge__tick" id="dcGaugeTickMax">10</text>
                    </svg>
                    <div class="dc-gauge__centro">
                        <div class="dc-gauge__valor" id="dcDiasSinValor">0</div>
                        <div class="dc-gauge__unidad">días</div>
                    </div>
                </div>
                <p class="dc-dias-sin-detalle text-muted" id="dcDiasSinDetalle"></p>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-dias-sin-body {
        position: relative;
    }

    .dc-gauge {
        position: relative;
        max-width: 100%;
        margin: 0 auto;
    }

    .dc-gauge__svg {
        display: block;
        width: 100%;
        height: auto;
    }

    .dc-gauge__tick {
        font-size: 11px;
        fill: #94a3b8;
        font-weight: 600;
    }

    .dc-gauge__centro {
        position: absolute;
        left: 50%;
        bottom: 28px;
        transform: translateX(-50%);
        text-align: center;
        pointer-events: none;
    }

    .dc-gauge__valor {
        font-size: 32px;
        font-weight: 800;
        line-height: 1;
        color: #1e293b;
    }

    .dc-gauge__unidad {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin-top: 2px;
    }

    .dc-gauge__valor--ok {
        color: #2f8f62;
    }

    .dc-gauge__valor--warn {
        color: #d68910;
    }

    .dc-gauge__valor--alert {
        color: #c0392b;
    }

    .dc-dias-sin-detalle {
        margin: 8px 0 0;
        font-size: 9px;
        text-align: center;
        line-height: 1.35;
    }

    .dc-dias-sin-body .box-title {
        font-size: 14px;
    }
</style>
