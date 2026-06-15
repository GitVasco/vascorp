<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/vasco-online.config.php";
require_once "../controladores/vasco-sync.controlador.php";
require_once "../modelos/vasco-sync.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!isset($_SESSION["backend"]) || (int) $_SESSION["backend"] !== 1) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para Vasco Online"));
    exit;
}

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";

if ($accion === "auditar-clientes") {
    $respuesta = ControladorVascoSync::ctrAuditarClientes();
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

if ($accion === "probar-conexion") {
    $respuesta = ControladorVascoSync::ctrProbarConexion();
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

if ($accion === "sincronizar-lote") {
    $lote = isset($_GET["lote"]) ? (int) $_GET["lote"] : 0;
    $traceId = isset($_GET["trace_id"]) ? trim($_GET["trace_id"]) : "";
    $respuesta = ControladorVascoSync::ctrSincronizarLoteClientes($lote, $traceId);
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

if ($accion === "auditar-cuentas") {
    $respuesta = ControladorVascoSync::ctrAuditarCuentas();
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

if ($accion === "sincronizar-lote-cuentas") {
    $lote = isset($_GET["lote"]) ? (int) $_GET["lote"] : 0;
    $traceId = isset($_GET["trace_id"]) ? trim($_GET["trace_id"]) : "";
    $respuesta = ControladorVascoSync::ctrSincronizarLoteCuentas($lote, $traceId);
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

if ($accion === "finalizar-cuentas") {
    $lote = isset($_GET["lote"]) ? (int) $_GET["lote"] : 0;
    $traceId = isset($_GET["trace_id"]) ? trim($_GET["trace_id"]) : "";
    $respuesta = ControladorVascoSync::ctrFinalizarSyncCuentas($traceId, $lote);
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
    exit;
}

echo json_encode(array("ok" => false, "msg" => "Acción no reconocida"));
