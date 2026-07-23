<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/vasco-online.config.php";
require_once "../../controladores/vasco-sync.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!isset($_SESSION["cuenta"]) || (int) $_SESSION["cuenta"] !== 1) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para cuentas corrientes"));
    exit;
}

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";

function vascoCobranzasJson($respuesta)
{
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
}

if ($accion === "listar-pendientes") {
    $filtros = array(
        "status" => isset($_GET["status"]) ? trim($_GET["status"]) : "pending_delivery",
        "seller_username" => isset($_GET["seller_username"]) ? trim($_GET["seller_username"]) : "",
        "since" => isset($_GET["since"]) ? trim($_GET["since"]) : "",
        "limit" => isset($_GET["limit"]) ? (int) $_GET["limit"] : 100,
        "trace_id" => isset($_GET["trace_id"]) ? trim($_GET["trace_id"]) : "",
    );

    vascoCobranzasJson(ControladorVascoSync::ctrListarCobranzasPendientes($filtros));
    exit;
}

if ($accion === "entregar") {
    $raw = file_get_contents("php://input");
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        vascoCobranzasJson(array("ok" => false, "msg" => "Body JSON inválido"));
        exit;
    }

    $items = isset($payload["items"]) && is_array($payload["items"]) ? $payload["items"] : array();
    $traceId = isset($payload["trace_id"]) ? trim((string) $payload["trace_id"]) : "";
    $deliveredBy = isset($payload["delivered_by"]) ? trim((string) $payload["delivered_by"]) : "";

    if ($deliveredBy === "" && isset($_SESSION["nombre"])) {
        $deliveredBy = trim((string) $_SESSION["nombre"]);
    }

    vascoCobranzasJson(ControladorVascoSync::ctrEntregarCobranzas($items, $deliveredBy, $traceId));
    exit;
}

if ($accion === "anular") {
    $raw = file_get_contents("php://input");
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        vascoCobranzasJson(array("ok" => false, "msg" => "Body JSON inválido"));
        exit;
    }

    $items = isset($payload["items"]) && is_array($payload["items"]) ? $payload["items"] : array();
    $traceId = isset($payload["trace_id"]) ? trim((string) $payload["trace_id"]) : "";
    $cancelledBy = isset($payload["cancelled_by"]) ? trim((string) $payload["cancelled_by"]) : "";

    if ($cancelledBy === "" && isset($_SESSION["nombre"])) {
        $cancelledBy = trim((string) $_SESSION["nombre"]);
    }

    vascoCobranzasJson(ControladorVascoSync::ctrAnularCobranzas($items, $cancelledBy, $traceId));
    exit;
}

vascoCobranzasJson(array("ok" => false, "msg" => "Acción no reconocida"));
