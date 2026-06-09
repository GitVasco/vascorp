<?php

define('RPT_ESTADO_CUENTA_MAX_DIAS', 2190);

function rptEstadoCuentaError($mensaje) {
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte no disponible</title></head><body style="font-family:sans-serif;padding:2rem;">';
  echo '<h2>No se pudo generar el reporte</h2><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
  echo '<p><a href="javascript:history.back()">Volver</a></p></body></html>';
  exit;
}

function rptEstadoCuentaValidarFecha($fecha, $etiqueta) {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    rptEstadoCuentaError('La fecha de ' . $etiqueta . ' no es valida.');
  }
  $partes = explode('-', $fecha);
  if (!checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0])) {
    rptEstadoCuentaError('La fecha de ' . $etiqueta . ' no es valida.');
  }
  return $fecha;
}

function rptEstadoCuentaSanitizarCodigo($valor) {
  $valor = trim($valor);
  if ($valor === '') {
    return '';
  }
  if (!preg_match('/^[A-Za-z0-9._-]+$/', $valor)) {
    rptEstadoCuentaError('El codigo de filtro indicado no es valido.');
  }
  return $valor;
}

function rptEstadoCuentaFiltrosSql($cliente, $vendedor, $alias = 'cc') {
  $sql = '';
  if ($cliente !== '') {
    $sql .= " AND {$alias}.cliente = " . $GLOBALS['rptEstadoCuentaConexion']->quote($cliente);
  }
  if ($vendedor !== '') {
    $sql .= " AND {$alias}.vendedor = " . $GLOBALS['rptEstadoCuentaConexion']->quote($vendedor);
  }
  return $sql;
}

function rptEstadoCuentaFormatearFecha($fecha) {
  if ($fecha === '' || $fecha === null) {
    return '';
  }
  $ts = strtotime($fecha);
  return $ts ? date('d/m/Y', $ts) : $fecha;
}

function rptEstadoCuentaSqlDetalle($fechaInicial, $fechaFinal, $fechaCorte, $filtrosA, $filtrosB, $filtrosPagos) {
  return "SELECT 
            'A' AS orden,
            '' AS tipo_doc,
            '' AS num_cta,
            '' AS cod_pago,
            '' AS doc_origen,
            '{$fechaCorte}' AS fecha,
            '{$fechaCorte}' AS fecha_ven,
            ROUND(SUM(cc.monto - IFNULL(c1.monto, 0)), 2) AS monto,
            0 AS saldo,
            '' AS tip_cambio,
            '' AS ult_pago,
            '' AS tip_mov,
            cc.cliente AS cliente,
            '' AS doc_cliente,
            '' AS nombre,
            '' AS vendedor,
            '' AS notas
          FROM cuenta_ctejf cc
          LEFT JOIN (
            SELECT
              tipo_doc,
              num_cta,
              SUM(monto) AS monto
            FROM cuenta_ctejf
            WHERE tip_mov = '-'
              AND fecha <= '{$fechaCorte}'
              {$filtrosPagos}
            GROUP BY tipo_doc, num_cta
          ) AS c1
            ON cc.tipo_doc = c1.tipo_doc
            AND cc.num_cta = c1.num_cta
          LEFT JOIN clientesjf AS c
            ON cc.cliente = c.codigo
          WHERE cc.tip_mov = '+'
            AND cc.fecha <= '{$fechaCorte}'
            {$filtrosA}
          GROUP BY cc.cliente
          UNION ALL
          SELECT
            'B' AS orden,
            cc.tipo_doc,
            cc.num_cta,
            cc.cod_pago,
            cc.doc_origen,
            cc.fecha,
            cc.fecha_ven,
            ROUND(cc.monto, 2) AS monto,
            ROUND(cc.saldo, 2) AS saldo,
            cc.tip_cambio,
            cc.ult_pago,
            cc.tip_mov,
            cc.cliente,
            c.documento AS doc_cliente,
            c.nombre,
            cc.vendedor,
            cc.notas
          FROM cuenta_ctejf cc
          LEFT JOIN clientesjf c
            ON cc.cliente = c.codigo
          WHERE cc.fecha >= '{$fechaCorte}'
            AND cc.fecha <= '{$fechaFinal}'
            {$filtrosB}
          ORDER BY cliente, orden, tipo_doc, num_cta, fecha, tip_mov";
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

$GLOBALS['rptEstadoCuentaConexion'] = $conexion;

date_default_timezone_set('America/Lima');
set_time_limit(0);

$fechaInicial = isset($_GET["inicio"]) ? trim($_GET["inicio"]) : '';
$fechaFinal = isset($_GET["fin"]) ? trim($_GET["fin"]) : '';
$cliente = rptEstadoCuentaSanitizarCodigo(isset($_GET["cli"]) ? $_GET["cli"] : '');
$vendedor = rptEstadoCuentaSanitizarCodigo(isset($_GET["vend"]) ? $_GET["vend"] : '');

if ($fechaInicial === '' && $fechaFinal !== '') {
  $fechaInicial = $fechaFinal;
}
if ($fechaFinal === '') {
  $fechaFinal = date('Y-m-d');
}
if ($fechaInicial === '') {
  rptEstadoCuentaError('Debe indicar la fecha de inicio del reporte.');
}

$fechaInicial = rptEstadoCuentaValidarFecha($fechaInicial, 'inicio');
$fechaFinal = rptEstadoCuentaValidarFecha($fechaFinal, 'fin');

if (strtotime($fechaInicial) > strtotime($fechaFinal)) {
  rptEstadoCuentaError('La fecha de inicio no puede ser mayor que la fecha de fin.');
}

$diasRango = (strtotime($fechaFinal) - strtotime($fechaInicial)) / 86400;
if ($diasRango > RPT_ESTADO_CUENTA_MAX_DIAS) {
  rptEstadoCuentaError(
    'El rango de fechas supera el maximo permitido de 6 anos. Reduzca el periodo o genere el reporte por partes.'
  );
}

$fechaCorte = date('Y-m-d', strtotime($fechaInicial . ' -1 day'));
$filtrosA = rptEstadoCuentaFiltrosSql($cliente, $vendedor, 'cc');
$filtrosB = rptEstadoCuentaFiltrosSql($cliente, $vendedor, 'cc');
$filtrosPagos = '';
if ($cliente !== '') {
  $filtrosPagos .= " AND cliente = " . $conexion->quote($cliente);
}
if ($vendedor !== '') {
  $filtrosPagos .= " AND vendedor = " . $conexion->quote($vendedor);
}

$sqlDetalle = rptEstadoCuentaSqlDetalle(
  $fechaInicial,
  $fechaFinal,
  $fechaCorte,
  $filtrosA,
  $filtrosB,
  $filtrosPagos
);

try {
  $stmtDetalle = $conexion->query($sqlDetalle);
} catch (PDOException $e) {
  rptEstadoCuentaError('Error al consultar los datos del reporte.');
}

$nombreArchivo = 'Estado Cta - ' . $fechaInicial . ' a ' . $fechaFinal . '.csv';

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
    rptEstadoCuentaFormatearFecha($fila['fecha']),
    rptEstadoCuentaFormatearFecha($fila['fecha_ven']),
    $fila['monto'],
    $fila['saldo'],
    $fila['tip_cambio'],
    rptEstadoCuentaFormatearFecha($fila['ult_pago']),
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
