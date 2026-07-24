<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["cuenta"]) || (int) $_SESSION["cuenta"] !== 1) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para cuentas corrientes"));
    exit;
}

require_once "../../controladores/config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/conexion.php";
require_once "../../controladores/estado-cuenta.controlador.php";
require_once "../../modelos/estado-cuenta.modelo.php";
require_once "../../controladores/grupos-empresariales.controlador.php";
require_once "../../modelos/grupos-empresariales.modelo.php";
require_once "../../modelos/inteligencia-comercial.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../controladores/inteligencia-comercial.controlador.php";
require_once "../../controladores/linea-credito.controlador.php";

$accion = "";
if (isset($_POST["accion"])) {
    $accion = trim((string) $_POST["accion"]);
} elseif (isset($_GET["accion"])) {
    $accion = trim((string) $_GET["accion"]);
}

switch ($accion) {
    case "buscarClientes":
        $q = isset($_POST["q"]) ? $_POST["q"] : (isset($_GET["q"]) ? $_GET["q"] : "");
        echo json_encode(ControladorEstadoCuenta::ctrBuscarClientes($q));
        break;

    case "resumenCliente":
        $codigo = isset($_POST["cliente"]) ? $_POST["cliente"] : "";
        echo json_encode(ControladorEstadoCuenta::ctrResumenCliente($codigo));
        break;

    case "resumenGrupo":
        $codigo = isset($_POST["grupo"]) ? $_POST["grupo"] : "";
        echo json_encode(ControladorEstadoCuenta::ctrResumenGrupo($codigo));
        break;

    case "desgloseGrupo":
        $codigo = isset($_POST["grupo"]) ? $_POST["grupo"] : "";
        $estado = isset($_POST["estado"]) ? $_POST["estado"] : "";
        $soloVencidos = !empty($_POST["solo_vencidos"]);
        echo json_encode(ControladorEstadoCuenta::ctrDesgloseGrupo($codigo, $estado, $soloVencidos));
        break;

    case "documentos":
        $codigo = isset($_POST["cliente"]) ? $_POST["cliente"] : "";
        $estado = isset($_POST["estado"]) ? $_POST["estado"] : "";
        $soloVencidos = !empty($_POST["solo_vencidos"]);
        echo json_encode(ControladorEstadoCuenta::ctrDocumentos($codigo, $estado, $soloVencidos));
        break;

    case "cancelaciones":
        $tipoDoc = isset($_POST["tipo_doc"]) ? $_POST["tipo_doc"] : "";
        $numCta = isset($_POST["num_cta"]) ? $_POST["num_cta"] : "";
        echo json_encode(ControladorEstadoCuenta::ctrCancelaciones($tipoDoc, $numCta));
        break;

    case "pagos":
        $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : "cliente";
        $codigo = isset($_POST["codigo"]) ? $_POST["codigo"] : "";
        echo json_encode(ControladorEstadoCuenta::ctrPagos($tipo, $codigo));
        break;

    default:
        echo json_encode(array("ok" => false, "msg" => "Acción no válida"));
}
