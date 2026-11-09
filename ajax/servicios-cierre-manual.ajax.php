<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../modelos/conexion.php";
require_once "../modelos/servicio.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["nombre"])) {
    echo json_encode(array("ok" => false, "error" => "Sesión no válida"));
    exit;
}

$motivosValidos = array("PERDIDA", "DEFECTO", "ERROR_REGISTRO", "ACUERDO_TALLER", "OTRO");

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

function salidaJson($arr)
{
    $j = json_encode($arr);
    if ($j === false) {
        $j = json_encode(array("data" => array(), "error" => "No se pudo codificar la respuesta (código " . json_last_error() . "): revise caracteres especiales en los datos"));
    }
    echo $j;
}

if ($accion === "listar") {
    try {
        $codigo = !empty($_POST["codigo"]) ? $_POST["codigo"] : null;
        salidaJson(array("data" => ModeloServicios::mdlListarAbiertosCierreManual($codigo)));
    } catch (Exception $e) {
        salidaJson(array("data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "historial") {
    $desde = (isset($_POST["desde"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["desde"])) ? $_POST["desde"] : date("Y-m-01");
    $hasta = (isset($_POST["hasta"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["hasta"])) ? $_POST["hasta"] : date("Y-m-d");
    try {
        salidaJson(array("data" => ModeloServicios::mdlHistorialCierreManual($desde, $hasta)));
    } catch (Exception $e) {
        salidaJson(array("data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "cerrar") {
    $items = array();
    $raw = isset($_POST["items"]) ? json_decode($_POST["items"], true) : null;
    if (is_array($raw)) {
        foreach ($raw as $it) {
            if (isset($it["id"]) && isset($it["cantidad"]) && (int) $it["id"] > 0 && (int) $it["cantidad"] > 0) {
                $items[] = array("id" => (int) $it["id"], "cantidad" => (int) $it["cantidad"]);
            }
        }
    }
    $motivo = isset($_POST["motivo"]) ? $_POST["motivo"] : "";
    $obs = trim(isset($_POST["obs"]) ? $_POST["obs"] : "");

    if (!$items) {
        echo json_encode(array("ok" => false, "error" => "Indique la cantidad a cerrar en al menos una línea"));
    } elseif (!in_array($motivo, $motivosValidos, true)) {
        echo json_encode(array("ok" => false, "error" => "Seleccione un motivo válido"));
    } elseif ($motivo === "OTRO" && $obs === "") {
        echo json_encode(array("ok" => false, "error" => "Indique la observación para el motivo 'Otro'"));
    } else {
        echo json_encode(ModeloServicios::mdlCierreManualServicios($items, $motivo, mb_substr($obs, 0, 255), $_SESSION["nombre"]));
    }
} else {
    echo json_encode(array("ok" => false, "error" => "Acción no válida"));
}
