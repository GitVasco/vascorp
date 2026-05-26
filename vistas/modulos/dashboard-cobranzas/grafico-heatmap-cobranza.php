<div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Heatmap de cobranzas</h3>
            <span class="label label-default" id="dcHeatmapPeriodo" style="margin-left: 8px; font-weight: normal;">
                —
            </span>
            <span class="label label-success" style="margin-left: 4px; font-weight: normal; font-size: 10px;">
                Solo efectivo
            </span>
        </div>
        <div class="box-body dc-heatmap-body" style="padding-top: 8px; min-height: 220px;">
            <div id="dcHeatmapLoading" class="text-center text-muted" style="padding: 48px 0;">
                Cargando matriz…
            </div>
            <div id="dcHeatmapWrap" style="display: none;">
                <div class="dc-heatmap-scroll">
                    <table class="dc-heatmap-table" id="dc-heatmap-table">
                        <thead>
                            <tr>
                                <th class="dc-heatmap-table__corner"></th>
                                <th>Lun</th>
                                <th>Mar</th>
                                <th>Mie</th>
                                <th>Jue</th>
                                <th>Vie</th>
                                <th>Sab</th>
                                <th>Dom</th>
                            </tr>
                        </thead>
                        <tbody id="dc-heatmap-tbody"></tbody>
                    </table>
                </div>
                <div class="dc-heatmap-leyenda">
                    <span class="dc-heatmap-leyenda__label">Menor</span>
                    <div class="dc-heatmap-leyenda__bar"></div>
                    <span class="dc-heatmap-leyenda__label">Mayor</span>
                    <span class="dc-heatmap-leyenda__max" id="dcHeatmapMaxLabel"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-heatmap-scroll {
        overflow-x: auto;
    }

    .dc-heatmap-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 4px;
        table-layout: fixed;
        font-size: 12px;
    }

    .dc-heatmap-table thead th {
        text-align: center;
        font-weight: 600;
        color: #64748b;
        padding: 6px 4px;
        border: none;
        background: transparent;
    }

    .dc-heatmap-table__corner {
        width: 120px;
    }

    .dc-heatmap-table tbody th {
        text-align: left;
        font-weight: 600;
        color: #475569;
        padding: 8px 10px 8px 0;
        white-space: nowrap;
        border: none;
        background: transparent;
        vertical-align: middle;
    }

    .dc-heatmap-table td {
        text-align: center;
        font-weight: 700;
        padding: 10px 6px;
        border-radius: 6px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        vertical-align: middle;
        min-height: 40px;
        cursor: default;
        transition: box-shadow 0.15s ease;
    }

    .dc-heatmap-table td:hover {
        box-shadow: 0 0 0 2px rgba(61, 153, 112, 0.35);
        z-index: 1;
        position: relative;
    }

    .dc-heatmap-table td.dc-heatmap-celda--vacia {
        color: #94a3b8;
        font-weight: 500;
    }

    .dc-heatmap-leyenda {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
        padding-top: 4px;
        font-size: 11px;
        color: #64748b;
    }

    .dc-heatmap-leyenda__bar {
        flex: 1;
        max-width: 200px;
        height: 10px;
        border-radius: 5px;
        background: linear-gradient(
            90deg,
            #f1f5f9 0%,
            rgba(61, 153, 112, 0.25) 35%,
            rgba(61, 153, 112, 0.65) 70%,
            #2f8f62 100%
        );
    }

    .dc-heatmap-leyenda__max {
        margin-left: auto;
        font-weight: 600;
        color: #334155;
    }
</style>
