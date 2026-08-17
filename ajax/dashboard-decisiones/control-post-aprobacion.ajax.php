<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../controladores/decisiones-credito.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(array("ok" => false, "msg" => "Método no permitido"));
    exit;
}

$accion = isset($_POST["accion"]) ? strtolower(trim((string) $_POST["accion"])) : "listar";

if ($accion === "catalogo") {
    if (!dcUsuarioPuedeVerHistorialCredito()) {
        echo json_encode(array("ok" => false, "msg" => "Sin permiso."));
        exit;
    }

    echo json_encode(array(
        "ok" => true,
        "controles_post_aprobacion" => dcListarControlesPostAprobacion(),
        "areas_autorizacion" => dcListarAreasAutorizacion(),
        "puede_liberar" => dcUsuarioPuedeLiberarControlPostAprobacion(),
        "puede_registrar" => dcUsuarioPuedeRegistrarControlPostAprobacion(),
    ));
    exit;
}

if ($accion === "registrar") {
    echo json_encode(ControladorDecisionesCredito::ctrRegistrarControlPostAprobacion());
    exit;
}

if ($accion === "liberar") {
    echo json_encode(ControladorDecisionesCredito::ctrLiberarControlPostAprobacion());
    exit;
}

if ($accion === "listar") {
    $vendedor = isset($_POST["vendedor"]) ? trim((string) $_POST["vendedor"]) : "";
    $limite = isset($_POST["limite"]) ? (int) $_POST["limite"] : 100;

    $datos = ControladorDecisionesCredito::ctrListarControlesPostAprobacion(array(
        "vendedor" => $vendedor,
        "limite" => $limite,
    ));

    ob_start();
    $filasTablaControles = (!empty($datos["ok"]) && isset($datos["filas"])) ? $datos["filas"] : array();
    $puedeLiberar = !empty($datos["puede_liberar"]);
    include __DIR__ . "/../../vistas/modulos/historial-credito/tabla-controles.php";
    $html = ob_get_clean();

    $datos["html"] = $html;
    echo json_encode($datos);
    exit;
}

echo json_encode(array("ok" => false, "msg" => "Acción no válida."));
