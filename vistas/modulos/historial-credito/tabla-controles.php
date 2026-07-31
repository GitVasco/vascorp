<?php
if (!isset($filas) || !is_array($filas)) {
    $filas = array();
}
if (!isset($puedeLiberar)) {
    $puedeLiberar = function_exists("dcUsuarioPuedeLiberarControlPostAprobacion")
        && dcUsuarioPuedeLiberarControlPostAprobacion();
}
if (!function_exists("ddFormatoMonto")) {
    require_once __DIR__ . "/../dashboard-decisiones/helpers.php";
}

if (!function_exists("hcEtiquetaEstadoPedidoControl")) {
    function hcEtiquetaEstadoPedidoControl($estado)
    {
        $estado = strtoupper(trim((string) $estado));
        $mapa = array(
            "GENERADO" => "Generado",
            "APROBADO" => "Aprobado",
            "APT" => "APT",
            "CONFIRMADO" => "Confirmado",
        );

        return isset($mapa[$estado]) ? $mapa[$estado] : ($estado !== "" ? $estado : "—");
    }
}

if (empty($filas)) {
    echo '<tr><td colspan="10" class="text-center text-muted">'
        . 'No hay controles post-aprobación pendientes.'
        . '</td></tr>';
    return;
}

foreach ($filas as $row) {
    $bloquea = (int) (isset($row["bloquea_apt"]) ? $row["bloquea_apt"] : 1) === 1;
    echo '<tr class="hc-row-control' . ($bloquea ? " hc-row-control--bloquea" : "") . '">';
    echo '<td><strong>' . htmlspecialchars($row["codigo_pedido"]) . '</strong>';
    if ($bloquea) {
        echo ' <span class="label label-danger" title="Bloquea facturación">FAC</span>';
    }
    echo '</td>';
    echo '<td><small>' . htmlspecialchars(hcEtiquetaEstadoPedidoControl(isset($row["pedido_estado"]) ? $row["pedido_estado"] : "")) . '</small></td>';
    echo '<td class="dd-col-cliente"><div class="dd-cell-main dd-cell-cliente" title="'
        . htmlspecialchars($row["codigo_cliente"] . " · " . $row["cliente_nombre"]) . '">'
        . ddClienteLinea($row["codigo_cliente"], $row["cliente_nombre"], $row)
        . '</div></td>';
    echo '<td><small>' . htmlspecialchars(isset($row["condicion_etiqueta"]) ? $row["condicion_etiqueta"] : $row["condicion_codigo"]) . '</small>';
    if (!empty($row["comentario"])) {
        echo '<div class="text-muted hc-control-detalle">' . htmlspecialchars($row["comentario"]) . '</div>';
    }
    echo '</td>';
    echo '<td><small>' . htmlspecialchars(!empty($row["area_etiqueta"]) ? $row["area_etiqueta"] : "—") . '</small></td>';
    echo '<td><small>' . htmlspecialchars(isset($row["usuario_registra_nombre"]) ? $row["usuario_registra_nombre"] : "—") . '</small></td>';
    echo '<td class="text-right hc-monto"><strong>' . ddFormatoMonto($row["pedido_lista"], $row["pedido_total"]) . '</strong></td>';
    echo '<td class="text-center hc-fecha-cell">' . htmlspecialchars(isset($row["fecha_registro"]) ? $row["fecha_registro"] : "") . '</td>';
    echo '<td class="text-center"><span class="dd-dias dd-dias--'
        . ((int) (isset($row["dias_pendiente"]) ? $row["dias_pendiente"] : 0) >= 2 ? "alto" : "medio") . '">'
        . (int) (isset($row["dias_pendiente"]) ? $row["dias_pendiente"] : 0) . 'd</span></td>';
    echo '<td class="text-center">';
    if ($puedeLiberar) {
        echo '<button type="button" class="btn btn-xs btn-success btnHcLiberarControl"'
            . ' title="Liberar despacho"'
            . ' data-id="' . (int) $row["id"] . '"'
            . ' data-pedido="' . htmlspecialchars($row["codigo_pedido"]) . '"'
            . ' data-condicion="' . htmlspecialchars(isset($row["condicion_etiqueta"]) ? $row["condicion_etiqueta"] : "") . '"'
            . ' data-cliente="' . htmlspecialchars($row["cliente_nombre"]) . '"'
            . ' data-area="' . htmlspecialchars(!empty($row["area_autoriza_codigo"]) ? $row["area_autoriza_codigo"] : "") . '">'
            . '<i class="fa fa-unlock"></i> Liberar</button>';
    } else {
        echo '<span class="text-muted">—</span>';
    }
    echo '</td>';
    echo '</tr>';
}
