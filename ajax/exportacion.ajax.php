<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/exportacion.modelo.php";
require_once "../controladores/exportacion.controlador.php";
// Solo lectura: consultas del CSV de exportación y calcularComas (no se llama a ctrGenerarFEFacBolB)
require_once "../modelos/facturacion.modelo.php";
require_once "../controladores/facturacion.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function expJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ControladorExportacion::puedeUsar()) {
    expJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$faltan = ModeloExportacion::mdlTablasListas();
if (count($faltan)) {
    expJson(array("ok" => false, "faltan" => $faltan, "msg" => "Faltan tablas: " . implode(", ", $faltan) . ". Ejecutar docs/sql/exportacion-fase1.sql."), 400);
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";

switch ($accion) {
    case "config":
        expJson(array("ok" => true, "config" => ControladorExportacion::config()));

    case "clientes":
        expJson(array(
            "ok" => true,
            "paises" => ControladorExportacion::paises(),
            "exterior" => ModeloExportacion::mdlClientesExterior(),
            "sin_pais" => ModeloExportacion::mdlClientesFXSinPais(),
        ));

    case "modelos":
        expJson(array(
            "ok" => true,
            "puede_editar" => ControladorExportacion::puedeEditarPrecios(),
            "datos" => ModeloExportacion::mdlModelosPrecio1(),
        ));

    case "guardar-precio1":
        $r = ControladorExportacion::ctrGuardarPrecio1($_POST);
        expJson($r, !empty($r["ok"]) ? 200 : 400);

    case "facturas-fx":
        $desde = isset($_REQUEST["desde"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-01-01");
        $hasta = isset($_REQUEST["hasta"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        expJson(array("ok" => true, "datos" => ModeloExportacion::mdlFacturasFX($desde, $hasta)));

    case "csv-comparar":
        $r = ControladorExportacion::ctrCompararCsv(isset($_REQUEST["documento"]) ? $_REQUEST["documento"] : "");
        expJson($r, !empty($r["ok"]) ? 200 : 400);

    case "csv-descargar":
        // Descarga para revisar o mandar a eFact; no deja nada en la carpeta que vigila eFact
        $modo = isset($_REQUEST["modo"]) && $_REQUEST["modo"] === "actual" ? "actual" : "nuevo";
        $r = ControladorExportacion::ctrArmarCsv(isset($_REQUEST["documento"]) ? $_REQUEST["documento"] : "", $modo);
        if (empty($r["ok"])) {
            expJson($r, 400);
        }
        $nombre = ($modo === "actual" ? "ACTUAL-" : "NUEVO-") . $r["nombre"];
        header("Content-Type: text/plain; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"" . $nombre . "\"");
        echo $r["contenido"];
        exit;

    case "partidas":
        expJson(array("ok" => true, "datos" => ModeloExportacion::mdlListarPartidas()));

    case "guardar-partida":
        $r = ControladorExportacion::ctrGuardarPartida($_POST);
        expJson($r, !empty($r["ok"]) ? 200 : 400);

    default:
        expJson(array("ok" => false, "msg" => "Acción no válida."), 400);
}
