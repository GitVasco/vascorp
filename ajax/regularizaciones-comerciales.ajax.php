<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/regularizaciones-comerciales.modelo.php";
require_once "../controladores/regularizaciones-comerciales.servicio.php";
require_once "../controladores/regularizaciones-comerciales.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function rcJson($respuesta)
{
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    rcJson(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!usuarioPuedeVerModulo("vasco_online", "regularizaciones_comerciales")) {
    http_response_code(403);
    rcJson(array("ok" => false, "msg" => "Sin permiso para este módulo."));
    exit;
}

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";
if ($accion === "" && isset($_POST["accion"])) {
    $accion = trim((string) $_POST["accion"]);
}

$accionesVer = array("buscar-cargos", "listar", "ver", "permisos");
$accionesRegistrar = array("crear");
$accionesAnular = array("anular");
$accionesResolver = array("reconciliar");

if (in_array($accion, $accionesRegistrar, true)
    && !usuarioPuedeModulo("vasco_online", "regularizaciones_comerciales", "registrar")
) {
    http_response_code(403);
    rcJson(array("ok" => false, "msg" => "Sin permiso para registrar."));
    exit;
}

if (in_array($accion, $accionesAnular, true)
    && !usuarioPuedeModulo("vasco_online", "regularizaciones_comerciales", "anular")
) {
    http_response_code(403);
    rcJson(array("ok" => false, "msg" => "Sin permiso para anular."));
    exit;
}

if (in_array($accion, $accionesResolver, true)
    && !usuarioPuedeModulo("vasco_online", "regularizaciones_comerciales", "resolver")
) {
    http_response_code(403);
    rcJson(array("ok" => false, "msg" => "Sin permiso para resolver."));
    exit;
}

switch ($accion) {
    case "permisos":
        rcJson(array(
            "ok" => true,
            "permisos" => ControladorRegularizacionesComerciales::ctrPermisos(),
        ));
        break;

    case "buscar-cargos":
        rcJson(ControladorRegularizacionesComerciales::ctrBuscarCargos());
        break;

    case "listar":
        rcJson(ControladorRegularizacionesComerciales::ctrListar());
        break;

    case "ver":
        rcJson(ControladorRegularizacionesComerciales::ctrVer());
        break;

    case "crear":
        rcJson(ControladorRegularizacionesComerciales::ctrCrear());
        break;

    case "anular":
        rcJson(ControladorRegularizacionesComerciales::ctrAnular());
        break;

    case "reconciliar":
        rcJson(ControladorRegularizacionesComerciales::ctrReconciliar());
        break;

    default:
        rcJson(array("ok" => false, "msg" => "Acción no reconocida."));
        break;
}
