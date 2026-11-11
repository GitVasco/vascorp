<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../modelos/conexion.php";
require_once "../modelos/servicio.modelo.php";
require_once "../controladores/cierre-manual.config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["nombre"])) {
    echo json_encode(array("ok" => false, "error" => "Sesión no válida"));
    exit;
}

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
    $taller = isset($_POST["taller"]) ? trim($_POST["taller"]) : "";
    if ($taller === "") {
        salidaJson(array("data" => array()));
        exit;
    }
    try {
        salidaJson(array("data" => ModeloServicios::mdlListarAbiertosCierreManual($taller)));
    } catch (Exception $e) {
        salidaJson(array("data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "historial") {
    $desde = (isset($_POST["desde"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["desde"])) ? $_POST["desde"] : date("Y-m-01");
    $hasta = (isset($_POST["hasta"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["hasta"])) ? $_POST["hasta"] : date("Y-m-d");
    try {
        $filas = ModeloServicios::mdlHistorialCierreManual($desde, $hasta);
        foreach ($filas as &$f) {
            $f["falla"] = cmNombreFalla($f["motivo"]);
        }
        unset($f);
        salidaJson(array("data" => $filas));
    } catch (Exception $e) {
        salidaJson(array("data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "detalle") {
    $clave = isset($_POST["clave"]) ? $_POST["clave"] : "";
    try {
        salidaJson(array("ok" => true, "data" => ModeloServicios::mdlDetalleCierreManual($clave)));
    } catch (Exception $e) {
        salidaJson(array("ok" => false, "data" => array(), "error" => $e->getMessage()));
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
    $taller = isset($_POST["taller"]) ? trim($_POST["taller"]) : "";
    $falla = isset($_POST["falla"]) ? $_POST["falla"] : "";
    $obs = trim(isset($_POST["obs"]) ? $_POST["obs"] : "");

    if ($taller === "") {
        echo json_encode(array("ok" => false, "error" => "Seleccione el taller"));
    } elseif (!cmFallaValida($falla)) {
        echo json_encode(array("ok" => false, "error" => "Seleccione un tipo de falla válido"));
    } elseif (!$items) {
        echo json_encode(array("ok" => false, "error" => "Agregue al menos un artículo con cantidad a cerrar"));
    } else {
        echo json_encode(ModeloServicios::mdlCierreManualServicios($items, $falla, mb_substr($obs, 0, 255), $_SESSION["nombre"], $taller));
    }
} else {
    echo json_encode(array("ok" => false, "error" => "Acción no válida"));
}
