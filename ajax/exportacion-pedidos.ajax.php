<?php

/**
 * Exportación en pedidos (docs/efact/EXPORTACION-diseno.md, Fase 3A). Solo lectura.
 * Marca en listados, aviso en crear pedido y filtro de series en el modal Facturar.
 * El control que bloquea está en el servidor (ControladorExportacion::ctrValidarFacturarPedido).
 */

session_start();

require_once "../modelos/conexion.php";
require_once "../modelos/exportacion.modelo.php";
require_once "../controladores/exportacion.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function expPedJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    expPedJson(array("ok" => false, "msg" => "Sesión expirada."), 401);
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";

try {
    switch ($accion) {
        case "clientes-exterior":
            expPedJson(array("ok" => true, "clientes" => ModeloExportacion::mdlMapaClientesExterior()));

        case "cliente":
            $codigo = isset($_REQUEST["codigo"]) ? trim($_REQUEST["codigo"]) : "";
            expPedJson($codigo === "" ? array("ok" => true, "exterior" => false) : ControladorExportacion::ctrInfoCliente($codigo));

        case "pedido":
            $codigo = isset($_REQUEST["codigo"]) ? trim($_REQUEST["codigo"]) : "";
            expPedJson(ControladorExportacion::ctrInfoPedido($codigo));

        default:
            expPedJson(array("ok" => false, "msg" => "Acción no válida."), 400);
    }
} catch (Exception $e) {
    expPedJson(array("ok" => false, "exterior" => false, "msg" => "No disponible."), 500);
}
