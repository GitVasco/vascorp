<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/cuadre-ventas.modelo.php";
require_once "../controladores/cuadre-ventas.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function cvJson($respuesta)
{
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    cvJson(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!usuarioPuedeVerModulo("gestion_comercial", "cuadre_ventas")) {
    http_response_code(403);
    cvJson(array("ok" => false, "msg" => "Sin permiso para este módulo."));
    exit;
}

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";
if ($accion === "" && isset($_POST["accion"])) {
    $accion = trim((string) $_POST["accion"]);
}

if ($accion === "permisos") {
    cvJson(array(
        "ok" => true,
        "permisos" => ControladorCuadreVentas::ctrPermisos(),
        "usuario_ventas" => ControladorCuadreVentas::ctrUsuarioVentasFiltro(),
    ));
    exit;
}

if ($accion === "estado-tablas") {
    cvJson(array(
        "ok" => true,
        "tablas" => ModeloCuadreVentas::mdlTablasListas(),
    ));
    exit;
}

if ($accion === "listar-ventas") {
    $fecha = isset($_GET["fecha"]) ? $_GET["fecha"] : (isset($_POST["fecha"]) ? $_POST["fecha"] : "");
    $respuesta = ControladorCuadreVentas::ctrListarVentasDia($fecha);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "guardar-borrador") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "registrar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para registrar."));
        exit;
    }
    $fecha = isset($_POST["fecha"]) ? $_POST["fecha"] : "";
    $docsRaw = isset($_POST["docs"]) ? $_POST["docs"] : "[]";
    $docsInput = is_array($docsRaw) ? $docsRaw : json_decode($docsRaw, true);
    if (!is_array($docsInput)) {
        $docsInput = array();
    }
    $respuesta = ControladorCuadreVentas::ctrGuardarBorrador($fecha, $docsInput);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "buscar-op") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "registrar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para registrar."));
        exit;
    }
    $ope = isset($_GET["ope"]) ? $_GET["ope"] : (isset($_POST["ope"]) ? $_POST["ope"] : "");
    $respuesta = ControladorCuadreVentas::ctrBuscarOp($ope);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "registrar-pagos") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "registrar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para registrar."));
        exit;
    }
    $fecha = isset($_POST["fecha"]) ? $_POST["fecha"] : "";
    $docsRaw = isset($_POST["docs"]) ? $_POST["docs"] : "[]";
    $pagosRaw = isset($_POST["pagos"]) ? $_POST["pagos"] : "[]";
    $docsInput = is_array($docsRaw) ? $docsRaw : json_decode($docsRaw, true);
    $pagosInput = is_array($pagosRaw) ? $pagosRaw : json_decode($pagosRaw, true);
    if (!is_array($docsInput)) {
        $docsInput = array();
    }
    if (!is_array($pagosInput)) {
        $pagosInput = array();
    }
    $respuesta = ControladorCuadreVentas::ctrRegistrarPagos($fecha, $docsInput, $pagosInput);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "validar-cuadre") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "validar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para validar."));
        exit;
    }
    $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
    $respuesta = ControladorCuadreVentas::ctrValidarCuadre($id);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "rechazar-cuadre") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "validar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para validar."));
        exit;
    }
    $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
    $motivo = isset($_POST["motivo"]) ? $_POST["motivo"] : "";
    $respuesta = ControladorCuadreVentas::ctrRechazarCuadre($id, $motivo);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "anular-cuadre") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "registrar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para cancelar."));
        exit;
    }
    $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
    $respuesta = ControladorCuadreVentas::ctrAnularCuadre($id);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

if ($accion === "procesar-cuadre") {
    if (!usuarioPuedeModulo("gestion_comercial", "cuadre_ventas", "procesar")) {
        http_response_code(403);
        cvJson(array("ok" => false, "msg" => "Sin permiso para procesar."));
        exit;
    }
    $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
    $respuesta = ControladorCuadreVentas::ctrProcesarCuadre($id);
    if (empty($respuesta["ok"])) {
        http_response_code(400);
    }
    cvJson($respuesta);
    exit;
}

http_response_code(400);
cvJson(array("ok" => false, "msg" => "Acción no válida."));
