<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/vasco-online.config.php";
require_once "../../modelos/vasco-sync.modelo.php";
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

function vascoSolicitudAtencionJson($respuesta)
{
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
}

if ($accion === "listar") {
    $status = isset($_GET["status"]) ? trim($_GET["status"]) : "pending";
    if (!in_array($status, array("pending", "acknowledged", "completed", "cancelled"), true)) {
        $status = "pending";
    }

    $filtros = array(
        "status" => $status,
        "since" => isset($_GET["since"]) ? trim($_GET["since"]) : "",
        "limit" => isset($_GET["limit"]) ? (int) $_GET["limit"] : 100,
        "trace_id" => isset($_GET["trace_id"]) ? trim($_GET["trace_id"]) : "",
    );

    vascoSolicitudAtencionJson(ControladorVascoSync::ctrListarSolicitudesAtencion($filtros));
    exit;
}

if ($accion === "procesar") {
    $raw = file_get_contents("php://input");
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        vascoSolicitudAtencionJson(array("ok" => false, "msg" => "Body JSON inválido"));
        exit;
    }

    $items = isset($payload["items"]) && is_array($payload["items"]) ? $payload["items"] : array();
    $traceId = isset($payload["trace_id"]) ? trim((string) $payload["trace_id"]) : "";
    $ackBy = isset($payload["ack_by"]) ? trim((string) $payload["ack_by"]) : "";

    if ($ackBy === "" && isset($_SESSION["nombre"])) {
        $ackBy = trim((string) $_SESSION["nombre"]);
    }

    vascoSolicitudAtencionJson(ControladorVascoSync::ctrProcesarSolicitudesAtencion($items, $ackBy, $traceId));
    exit;
}

vascoSolicitudAtencionJson(array("ok" => false, "msg" => "Acción no reconocida"));
