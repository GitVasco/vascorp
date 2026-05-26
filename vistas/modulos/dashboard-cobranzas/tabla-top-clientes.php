<div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
    <div class="box box-success">
        <div class="box-header with-border dc-top-clientes-header">
            <h3 class="box-title">Top 10 clientes</h3>
            <span class="label label-default dc-top-clientes-periodo" id="dcTopClientesPeriodo">
                —
            </span>
        </div>
        <div class="box-body dc-top-clientes-body" style="padding-top: 8px; min-height: 220px;">
            <div id="dcTopClientesLoading" class="text-center text-muted" style="padding: 48px 0;">
                Cargando ranking…
            </div>
            <div id="dcTopClientesWrap" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dc-top-clientes-table">
                        <thead>
                            <tr>
                                <th class="dc-top-clientes-table__rank">#</th>
                                <th>Cliente</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody id="dc-top-clientes-tbody"></tbody>
                    </table>
                </div>
                <p class="dc-top-clientes-foot text-muted" id="dcTopClientesFoot"></p>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-top-clientes-header .box-title {
        font-size: 14px;
        display: block;
        margin-bottom: 4px;
    }

    .dc-top-clientes-periodo {
        font-weight: normal;
        font-size: 10px;
        display: inline-block;
    }

    .dc-top-clientes-table {
        margin-bottom: 0;
        font-size: 10px;
    }

    .dc-top-clientes-table thead th {
        border-bottom: 2px solid #e2e8f0;
        color: #64748b;
        font-weight: 600;
        padding: 4px 2px;
        font-size: 9px;
    }

    .dc-top-clientes-table__rank {
        width: 22px;
    }

    .dc-top-clientes-table tbody td {
        vertical-align: middle;
        padding: 5px 2px;
        border-top: 1px solid #f1f5f9;
    }

    .dc-top-clientes-table__num {
        display: inline-flex;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ecf0f5;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        align-items: center;
        justify-content: center;
    }

    .dc-top-clientes-table__num--1 {
        background: #3d9970;
        color: #fff;
    }

    .dc-top-clientes-table__num--2,
    .dc-top-clientes-table__num--3 {
        background: #3c8dbc;
        color: #fff;
    }

    .dc-top-clientes-table__codigo {
        font-size: 9px;
        font-weight: 700;
        color: #64748b;
    }

    .dc-top-clientes-table__nombre {
        font-weight: 600;
        color: #1e293b;
        font-size: 9px;
    }

    .dc-top-clientes-table tbody td:nth-child(2) {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dc-top-clientes-table__monto,
    .dc-top-clientes-table__pct {
        font-size: 9px;
    }

    .dc-top-clientes-table__monto {
        min-width: 44px;
    }

    .dc-top-clientes-table__pct {
        min-width: 32px;
    }

    .dc-top-clientes-table__monto {
        font-weight: 700;
        color: #334155;
        white-space: nowrap;
    }

    .dc-top-clientes-table__pct {
        font-weight: 700;
        color: #3d9970;
        white-space: nowrap;
    }

    .dc-top-clientes-foot {
        margin: 10px 0 0;
        font-size: 11px;
    }
</style>
