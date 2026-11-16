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
            if ((int) $f["motivos_distintos"] > 1) {
                $f["falla"] = "Varios";
            } else {
                $f["falla"] = cmNombreFalla($f["motivo"]);
            }
        }
        unset($f);
        salidaJson(array("data" => $filas));
    } catch (Exception $e) {
        salidaJson(array("data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "detalle") {
    $clave = isset($_POST["clave"]) ? $_POST["clave"] : "";
    try {
        $filas = ModeloServicios::mdlDetalleCierreManual($clave);
        foreach ($filas as &$f) {
            $f["motivo_nombre"] = cmNombreFalla(isset($f["motivo"]) ? $f["motivo"] : "");
        }
        unset($f);
        salidaJson(array("ok" => true, "data" => $filas));
    } catch (Exception $e) {
        salidaJson(array("ok" => false, "data" => array(), "error" => $e->getMessage()));
    }
} elseif ($accion === "cerrar") {
    $items = array();
    $errorItems = "";
    $raw = isset($_POST["items"]) ? json_decode($_POST["items"], true) : null;
    if (is_array($raw)) {
        foreach ($raw as $it) {
            $id = isset($it["id"]) ? (int) $it["id"] : 0;
            $cant = isset($it["cantidad"]) ? (int) $it["cantidad"] : 0;
            $falla = isset($it["falla"]) ? trim((string) $it["falla"]) : "";
            if ($id <= 0 || $cant <= 0) {
                continue;
            }
            if (!cmFallaValida($falla)) {
                $errorItems = "Cada prenda debe tener un motivo válido";
                break;
            }
            $items[] = array("id" => $id, "cantidad" => $cant, "falla" => $falla);
        }
    }
    $taller = isset($_POST["taller"]) ? trim($_POST["taller"]) : "";
    $fecha = isset($_POST["fecha"]) ? trim($_POST["fecha"]) : "";
    $obs = trim(isset($_POST["obs"]) ? $_POST["obs"] : "");

    if ($taller === "") {
        echo json_encode(array("ok" => false, "error" => "Seleccione el taller"));
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        echo json_encode(array("ok" => false, "error" => "Indique una fecha válida"));
    } elseif ($errorItems !== "") {
        echo json_encode(array("ok" => false, "error" => $errorItems));
    } elseif (!$items) {
        echo json_encode(array("ok" => false, "error" => "Agregue al menos una prenda con cantidad y motivo"));
    } else {
        echo json_encode(ModeloServicios::mdlCierreManualServicios($items, mb_substr($obs, 0, 255), $_SESSION["nombre"], $taller, $fecha));
    }
} elseif ($accion === "anular") {
    $codigo = isset($_POST["codigo"]) ? trim($_POST["codigo"]) : "";
    $motivo = isset($_POST["motivo"]) ? trim($_POST["motivo"]) : "";
    echo json_encode(ModeloServicios::mdlAnularCierreManual($codigo, $motivo, $_SESSION["nombre"]));
} else {
    echo json_encode(array("ok" => false, "error" => "Acción no válida"));
}
