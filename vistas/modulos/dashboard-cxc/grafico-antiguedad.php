<?php

if (!isset($antiguedadCxc)) {
    return;
}
?>

<div class="box box-default cxc-panel">
    <div class="box-header with-border">
        <h3 class="box-title">Antigüedad de saldos</h3>
    </div>
    <div class="box-body">
        <div class="cxc-grafico-wrap">
            <canvas id="graficoAntiguedadCxc" height="260"></canvas>
        </div>
        <div id="graficoAntiguedadCxcEmpty" class="cxc-empty-state" style="display:none;">
            No hay saldos pendientes para el corte seleccionado.
        </div>
    </div>
</div>

<script type="application/json" id="cxcAntiguedadInitialData"><?php echo json_encode($antiguedadCxc, JSON_UNESCAPED_UNICODE); ?></script>
