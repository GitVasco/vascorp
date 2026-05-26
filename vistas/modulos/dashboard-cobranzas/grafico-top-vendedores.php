<div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Top 10 vendedores</h3>
            <span class="label label-default" id="dcTopVendedoresPeriodo" style="margin-left: 8px; font-weight: normal;">
                —
            </span>
            <span class="label label-info" style="margin-left: 4px; font-weight: normal; font-size: 10px;">
                No filtra por vendedor
            </span>
        </div>
        <div class="box-body dc-top-vendedores-body" style="padding-top: 10px; min-height: 280px;">
            <div id="dcTopVendedoresLoading" class="text-center text-muted" style="padding: 60px 0;">
                Cargando ranking…
            </div>
            <div id="dcTopVendedoresWrap" style="display: none;">
                <div id="dc-top-vendedores-list" class="dc-top-vendedores-list"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-top-vendedores-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .dc-top-vendedor-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
    }

    .dc-top-vendedor-row__rank {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ecf0f5;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dc-top-vendedor-row__rank--1 {
        background: #3d9970;
        color: #fff;
    }

    .dc-top-vendedor-row__rank--2,
    .dc-top-vendedor-row__rank--3 {
        background: #3c8dbc;
        color: #fff;
    }

    .dc-top-vendedor-row__info {
        flex: 0 1 42%;
        min-width: 0;
        max-width: 42%;
    }

    .dc-top-vendedor-row__nombre {
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .dc-top-vendedor-row__codigo {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
    }

    .dc-top-vendedor-row__bar-wrap {
        flex: 2;
        min-width: 90px;
        max-width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .dc-top-vendedor-row__bar {
        height: 100%;
        background: linear-gradient(90deg, #3d9970, #2f8f62);
        border-radius: 4px;
        min-width: 2px;
    }

    .dc-top-vendedor-row__monto {
        width: 64px;
        text-align: right;
        font-weight: 700;
        color: #334155;
        flex-shrink: 0;
    }
</style>
