<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/liquidaciones-servicio-prod.modelo.php";

header("Content-Type: application/json; charset=utf-8");

function lspJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

function lspFecha($f)
{
    $d = DateTime::createFromFormat("Y-m-d", (string) $f);
    return $d && $d->format("Y-m-d") === $f;
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok"
    || !isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    lspJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$faltan = ModeloLiquidacionesServicioProd::mdlTablasListas();
if (count($faltan)) {
    lspJson(array("ok" => false, "msg" => "Faltan tablas/columnas: " . implode(", ", $faltan) . ". Ejecute docs/sql/liquidaciones-servicio-prod.sql (y docs/sql/ordenes-servicio-prod.sql)."));
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";

switch ($accion) {
    case "talleres":
        lspJson(array("ok" => true, "datos" => ModeloLiquidacionesServicioProd::mdlTalleres()));

    case "listar":
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-m-01");
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        $taller = isset($_REQUEST["taller"]) ? trim($_REQUEST["taller"]) : "";
        $estado = isset($_REQUEST["estado"]) ? trim($_REQUEST["estado"]) : "";
        if (!lspFecha($desde) || !lspFecha($hasta)) {
            lspJson(array("ok" => false, "msg" => "Fechas inválidas."), 400);
        }
        if ($estado !== "" && !in_array($estado, array("LIQUIDADA", "PAGADA", "ANULADA"), true)) {
            lspJson(array("ok" => false, "msg" => "Estado inválido."), 400);
        }
        lspJson(array("ok" => true, "datos" => ModeloLiquidacionesServicioProd::mdlListar($desde, $hasta, $taller, $estado)));

    case "vista_previa":
        $taller = isset($_REQUEST["taller"]) ? trim($_REQUEST["taller"]) : "";
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : "";
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : "";
        if ($taller === "" || !lspFecha($desde) || !lspFecha($hasta) || $desde > $hasta) {
            lspJson(array("ok" => false, "msg" => "Seleccione taller y un rango de fechas válido."), 400);
        }
        $prev = ModeloLiquidacionesServicioProd::mdlVistaPrevia($taller, $desde, $hasta);
        $prev["taller"] = ModeloLiquidacionesServicioProd::mdlDatosTaller($taller);
        $prev["ok"] = true;
        lspJson($prev);

    case "crear":
        $taller = isset($_POST["taller"]) ? trim($_POST["taller"]) : "";
        $desde = isset($_POST["desde"]) ? $_POST["desde"] : "";
        $hasta = isset($_POST["hasta"]) ? $_POST["hasta"] : "";
        $obs = isset($_POST["observaciones"]) ? mb_substr(trim($_POST["observaciones"]), 0, 500) : "";
        if ($taller === "" || !lspFecha($desde) || !lspFecha($hasta) || $desde > $hasta) {
            lspJson(array("ok" => false, "msg" => "Seleccione taller y un rango de fechas válido."), 400);
        }
        $datos = ModeloLiquidacionesServicioProd::mdlDatosTaller($taller);
        if (!$datos || trim((string) $datos["doc"]) === "") {
            lspJson(array("ok" => false, "msg" => "El taller no tiene RUC/razón social registrados (datos fiscales de talleres de la GRE manual). Complételos antes de liquidar."));
        }
        lspJson(ModeloLiquidacionesServicioProd::mdlCrear($taller, $desde, $hasta, $obs, (int) $_SESSION["id"], $_SESSION["nombre"]));

    case "ver":
        $l = ModeloLiquidacionesServicioProd::mdlObtener(isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0);
        if (!$l) {
            lspJson(array("ok" => false, "msg" => "Liquidación no encontrada."), 404);
        }
        lspJson(array("ok" => true, "liquidacion" => $l));

    case "pagar":
        $fecha = isset($_POST["fecha_pago"]) ? $_POST["fecha_pago"] : "";
        $ref = isset($_POST["referencia"]) ? mb_substr(trim($_POST["referencia"]), 0, 100) : "";
        if (!lspFecha($fecha)) {
            lspJson(array("ok" => false, "msg" => "Indique la fecha de pago."), 400);
        }
        $r = ModeloLiquidacionesServicioProd::mdlPagar(isset($_POST["id"]) ? $_POST["id"] : 0, $fecha, $ref, $_SESSION["nombre"]);
        if (!empty($r["ok"])) {
            error_log("[LSP] " . $_SESSION["nombre"] . " marcó pagada la liquidación id " . (int) $_POST["id"]);
        }
        lspJson($r);

    case "revertir_pago":
        $r = ModeloLiquidacionesServicioProd::mdlRevertirPago(isset($_POST["id"]) ? $_POST["id"] : 0);
        if (!empty($r["ok"])) {
            error_log("[LSP] " . $_SESSION["nombre"] . " revirtió el pago de la liquidación id " . (int) $_POST["id"]);
        }
        lspJson($r);

    case "anular":
        $motivo = isset($_POST["motivo"]) ? mb_substr(trim($_POST["motivo"]), 0, 200) : "";
        if ($motivo === "") {
            lspJson(array("ok" => false, "msg" => "Indique el motivo de anulación."), 400);
        }
        lspJson(ModeloLiquidacionesServicioProd::mdlAnular(isset($_POST["id"]) ? $_POST["id"] : 0, $motivo, $_SESSION["nombre"]));

    case "eliminar":
        $r = ModeloLiquidacionesServicioProd::mdlEliminar(isset($_POST["id"]) ? $_POST["id"] : 0);
        if (!empty($r["ok"])) {
            error_log("[LSP] " . $_SESSION["nombre"] . " eliminó la liquidación " . $r["numero"]);
        }
        lspJson($r);

    default:
        lspJson(array("ok" => false, "msg" => "Acción inválida."), 400);
}
