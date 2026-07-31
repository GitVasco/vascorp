<hr class="dd-aprobar-control-sep">
<div class="form-group dd-aprobar-control-toggle-wrap">
    <label class="checkbox-inline dd-aprobar-control-toggle">
        <input type="checkbox" id="ddAprobarPedidoRequiereControl" value="1">
        <strong>Aprobación condicionada</strong>
        <span class="text-muted"> — requiere control antes de despachar</span>
    </label>
</div>
<div id="ddAprobarPedidoControlFields" class="dd-aprobar-control-fields" style="display:none;">
    <div class="form-group">
        <label for="ddAprobarPedidoControlCondicion">Condición pendiente <span class="text-danger">*</span></label>
        <select
            id="ddAprobarPedidoControlCondicion"
            class="form-control selectpicker dd-control-condicion-select"
            data-live-search="true"
            title="Selecciona condición…"
        >
            <option value="">Selecciona condición…</option>
        </select>
        <p class="help-block dd-control-condicion-help text-muted" id="ddAprobarPedidoControlHelp"></p>
    </div>
    <div class="form-group">
        <label for="ddAprobarPedidoControlArea">Autorizado por (área)</label>
        <select
            id="ddAprobarPedidoControlArea"
            class="form-control selectpicker dd-control-area-select"
            data-live-search="true"
            title="Sin área específica…"
        >
            <option value="">Sin área específica…</option>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:0;">
        <label for="ddAprobarPedidoControlObs">Detalle del control</label>
        <textarea
            id="ddAprobarPedidoControlObs"
            class="form-control"
            rows="2"
            placeholder="Ej.: cliente debe pagar deuda vencida antes de despachar; avisar a APT…"
        ></textarea>
    </div>
</div>
