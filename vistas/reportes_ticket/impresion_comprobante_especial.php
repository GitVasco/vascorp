<?php
session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/comprobantes-especiales.modelo.php";
require_once "../../controladores/comprobantes-especiales.controlador.php";

if (!ControladorComprobantesEspeciales::puedeUsar()) {
    http_response_code(403);
    exit("Sin permiso.");
}

$g = ModeloComprobantesEspeciales::mdlObtener(isset($_GET["id"]) ? (int) $_GET["id"] : 0);
if (!$g) {
    http_response_code(404);
    exit("Comprobante no encontrado.");
}

$g["afectacion_texto"] = ControladorComprobantesEspeciales::textoAfectacion($g["afectacion"]);

require_once "impresion_comprobante_especial_html.php";

header("Content-Type: text/html; charset=utf-8");
echo ceImpresionHtml($g);
