<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/helpdesk.modelo.php";
require_once "../controladores/helpdesk.controlador.php";

function hdJson($respuesta)
{
    header("Content-Type: application/json; charset=utf-8");
    if (defined("JSON_UNESCAPED_UNICODE")) {
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($respuesta);
    }
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    hdJson(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!usuarioPuedeVerModulo("ti", "helpdesk")) {
    http_response_code(403);
    hdJson(array("ok" => false, "msg" => "Sin permiso para este módulo."));
    exit;
}

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";
if ($accion === "" && isset($_POST["accion"])) {
    $accion = trim((string) $_POST["accion"]);
}

$accionesRegistrar = array("crear");
$accionesPulirIa = array("pulir");
$accionesGestionar = array("actualizar");

if (in_array($accion, $accionesRegistrar, true)
    && !usuarioPuedeModulo("ti", "helpdesk", "registrar")
    && !usuarioPuedeModulo("ti", "helpdesk", "gestionar")
) {
    http_response_code(403);
    hdJson(array("ok" => false, "msg" => "Sin permiso para registrar."));
    exit;
}

if (in_array($accion, $accionesPulirIa, true)
    && (int) (isset($_SESSION["id"]) ? $_SESSION["id"] : 0) !== ControladorHelpdesk::USUARIO_PULIR_IA
) {
    http_response_code(403);
    hdJson(array("ok" => false, "msg" => "Sin permiso para corregir con IA."));
    exit;
}

if (in_array($accion, $accionesGestionar, true)
    && !usuarioPuedeModulo("ti", "helpdesk", "gestionar")
) {
    http_response_code(403);
    hdJson(array("ok" => false, "msg" => "Sin permiso para gestionar."));
    exit;
}

switch ($accion) {
    case "permisos":
    case "catalogos":
        hdJson(ControladorHelpdesk::ctrAgentes());
        break;

    case "listar":
        hdJson(ControladorHelpdesk::ctrListar());
        break;

    case "ver":
        hdJson(ControladorHelpdesk::ctrVer());
        break;

    case "crear":
        hdJson(ControladorHelpdesk::ctrCrear());
        break;

    case "pulir":
        hdJson(ControladorHelpdesk::ctrPulirTexto());
        break;

    case "comentar":
        hdJson(ControladorHelpdesk::ctrComentar());
        break;

    case "actualizar":
        hdJson(ControladorHelpdesk::ctrActualizar());
        break;

    case "adjunto":
        $res = ControladorHelpdesk::ctrDescargarAdjunto();
        if (empty($res["ok"])) {
            http_response_code(404);
            hdJson($res);
            exit;
        }
        $mime = $res["mime"];
        $nombre = $res["nombre"];
        $path = $res["path"];
        header("Content-Type: " . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', "", $nombre) . '"');
        header("Content-Length: " . filesize($path));
        readfile($path);
        exit;

    default:
        hdJson(array("ok" => false, "msg" => "Acción no reconocida."));
        break;
}
