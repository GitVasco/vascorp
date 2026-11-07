<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/ordenes-servicio-prod.modelo.php";
require_once "../modelos/gre-manual.modelo.php";
require_once "../controladores/gre-manual.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function ospJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

function ospFecha($f)
{
    $d = DateTime::createFromFormat("Y-m-d", (string) $f);
    return $d && $d->format("Y-m-d") === $f;
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok"
    || !isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    ospJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$faltan = ModeloOrdenesServicioProd::mdlTablasListas();
if (count($faltan)) {
    ospJson(array("ok" => false, "msg" => "Faltan tablas/columnas: " . implode(", ", $faltan) . ". Ejecute docs/sql/ordenes-servicio-prod.sql (y docs/sql/gre-manual-servicio.sql)."));
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";

switch ($accion) {
    case "talleres":
        ospJson(array("ok" => true, "datos" => ModeloOrdenesServicioProd::mdlTalleres()));

    case "listar":
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-m-01");
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        $taller = isset($_REQUEST["taller"]) ? trim($_REQUEST["taller"]) : "";
        $estado = isset($_REQUEST["estado"]) ? trim($_REQUEST["estado"]) : "";
        if (!ospFecha($desde) || !ospFecha($hasta)) {
            ospJson(array("ok" => false, "msg" => "Fechas inválidas."), 400);
        }
        if ($estado !== "" && !in_array($estado, array("EMITIDA", "ANULADA"), true)) {
            ospJson(array("ok" => false, "msg" => "Estado inválido."), 400);
        }
        ospJson(array("ok" => true, "datos" => ModeloOrdenesServicioProd::mdlListar($desde, $hasta, $taller, $estado)));

    case "vista_previa":
        $taller = isset($_REQUEST["taller"]) ? trim($_REQUEST["taller"]) : "";
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : "";
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : "";
        if ($taller === "" || !ospFecha($desde) || !ospFecha($hasta) || $desde > $hasta) {
            ospJson(array("ok" => false, "msg" => "Seleccione taller y un rango de fechas válido."), 400);
        }
        $regul = isset($_REQUEST["regularizacion"]) && $_REQUEST["regularizacion"] === "1";
        $prev = ModeloOrdenesServicioProd::mdlVistaPrevia($taller, $desde, $hasta, null, false, $regul);
        $prev["taller"] = ModeloOrdenesServicioProd::mdlDatosTaller($taller);
        $prev["ok"] = true;
        ospJson($prev);

    case "crear":
        $taller = isset($_POST["taller"]) ? trim($_POST["taller"]) : "";
        $desde = isset($_POST["desde"]) ? $_POST["desde"] : "";
        $hasta = isset($_POST["hasta"]) ? $_POST["hasta"] : "";
        $obs = isset($_POST["observaciones"]) ? mb_substr(trim($_POST["observaciones"]), 0, 500) : "";
        if ($taller === "" || !ospFecha($desde) || !ospFecha($hasta) || $desde > $hasta) {
            ospJson(array("ok" => false, "msg" => "Seleccione taller y un rango de fechas válido."), 400);
        }
        $datos = ModeloOrdenesServicioProd::mdlDatosTaller($taller);
        if (!$datos || trim((string) $datos["doc"]) === "") {
            ospJson(array("ok" => false, "msg" => "El taller no tiene RUC/razón social registrados (datos fiscales de talleres de la GRE manual). Complételos antes de emitir la orden."));
        }
        $regul = isset($_POST["regularizacion"]) && $_POST["regularizacion"] === "1";
        if ($regul && $obs === "") {
            $obs = "ORDEN DE REGULARIZACION: SERVICIOS ENVIADOS DEL " . date("d/m/Y", strtotime($desde)) . " AL " . date("d/m/Y", strtotime($hasta)) . ".";
        }
        ospJson(ModeloOrdenesServicioProd::mdlCrear($taller, $desde, $hasta, $obs, (int) $_SESSION["id"], $_SESSION["nombre"], $regul));

    case "ver":
        $o = ModeloOrdenesServicioProd::mdlObtener(isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0);
        if (!$o) {
            ospJson(array("ok" => false, "msg" => "Orden no encontrada."), 404);
        }
        ospJson(array("ok" => true, "orden" => $o, "puede_guia" => ControladorGreManual::puedeUsar()));

    case "anular":
        $motivo = isset($_POST["motivo"]) ? mb_substr(trim($_POST["motivo"]), 0, 200) : "";
        if ($motivo === "") {
            ospJson(array("ok" => false, "msg" => "Indique el motivo de anulación."), 400);
        }
        ospJson(ModeloOrdenesServicioProd::mdlAnular(isset($_POST["id"]) ? $_POST["id"] : 0, $motivo, $_SESSION["nombre"]));

    case "eliminar":
        $r = ModeloOrdenesServicioProd::mdlEliminar(isset($_POST["id"]) ? $_POST["id"] : 0);
        if (!empty($r["ok"])) {
            error_log("[OSP] " . $_SESSION["nombre"] . " eliminó la orden " . $r["numero"]);
        }
        ospJson($r);

    default:
        ospJson(array("ok" => false, "msg" => "Acción inválida."), 400);
}
