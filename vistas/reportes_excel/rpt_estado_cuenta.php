<?php

require_once __DIR__ . '/../../controladores/reportes-estado-cuenta.lib.php';

define('RPT_ESTADO_CUENTA_MAX_DIAS', ReportesEstadoCuentaLib::MAX_DIAS);

function rptEstadoCuentaError($mensaje) {
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte no disponible</title></head><body style="font-family:sans-serif;padding:2rem;">';
  echo '<h2>No se pudo generar el reporte</h2><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
  echo '<p><a href="javascript:history.back()">Volver</a></p></body></html>';
  exit;
}

function rptEstadoCuentaFiltrosPagos($conexion, $f) {
  $filtrosPagos = '';
  if ($f['cli'] !== '') {
    $filtrosPagos .= ' AND cliente = ' . $conexion->quote($f['cli']);
  }
  if ($f['vend'] !== '') {
    $filtrosPagos .= ' AND vendedor = ' . $conexion->quote($f['vend']);
  }
  return $filtrosPagos;
}

require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";

$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);

try {
  $conexion = new PDO(
    "mysql:host=" . $con["ip"] . ";dbname=" . $con["db"],
    $con["user"],
    $con["pwd"],
    array(
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
    )
  );
  $conexion->exec("set names utf8");
} catch (PDOException $e) {
  rptEstadoCuentaError('No se pudo conectar a la base de datos.');
}

date_default_timezone_set('America/Lima');
set_time_limit(0);

$f = ReportesEstadoCuentaLib::prepararFiltros(array(
  'cli' => isset($_GET["cli"]) ? $_GET["cli"] : '',
  'vend' => isset($_GET["vend"]) ? $_GET["vend"] : '',
  'inicio' => isset($_GET["inicio"]) ? $_GET["inicio"] : '',
  'fin' => isset($_GET["fin"]) ? $_GET["fin"] : '',
));

$validacion = ReportesEstadoCuentaLib::validar($f);
if ($validacion['ok'] !== true) {
  rptEstadoCuentaError($validacion['error']);
}

try {
  $stmtDetalle = $conexion->query(
    ReportesEstadoCuentaLib::sqlDetalle(
      $f['inicio'],
      $f['fin'],
      $f['fecha_corte'],
      ReportesEstadoCuentaLib::filtrosSql($conexion, $f['cli'], $f['vend'], 'cc'),
      ReportesEstadoCuentaLib::filtrosSql($conexion, $f['cli'], $f['vend'], 'cc'),
      rptEstadoCuentaFiltrosPagos($conexion, $f)
    )
  );
} catch (PDOException $e) {
  rptEstadoCuentaError('Error al consultar los datos del reporte.');
}

$nombreArchivo = 'Estado Cta - ' . $f['inicio'] . ' a ' . $f['fin'] . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

$salida = fopen('php://output', 'w');
fputcsv($salida, array(
  'Tip. Doc',
  'Num Cta',
  'Cod Pago',
  'Doc Origen',
  'Fec Emi',
  'Fec Ven',
  'Monto',
  'Saldo',
  'TC',
  'Ult. Pago',
  'Tip Mov',
  'Cod. Cli',
  'Doc. Cli',
  'Cliente',
  'Vendedor',
  'Notas'
), ';');

while ($fila = $stmtDetalle->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($salida, array(
    $fila['tipo_doc'],
    $fila['num_cta'],
    $fila['cod_pago'],
    $fila['doc_origen'],
    ReportesEstadoCuentaLib::formatearFecha($fila['fecha']),
    ReportesEstadoCuentaLib::formatearFecha($fila['fecha_ven']),
    $fila['monto'],
    $fila['saldo'],
    $fila['tip_cambio'],
    ReportesEstadoCuentaLib::formatearFecha($fila['ult_pago']),
    $fila['tip_mov'],
    $fila['cliente'],
    $fila['doc_cliente'],
    $fila['nombre'],
    $fila['vendedor'],
    $fila['notas']
  ), ';');
}

fclose($salida);
exit;
