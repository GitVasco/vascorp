<?php

/**
 * Asigna la orden de corte (OC) a las líneas activas de programación semanal y prioridad
 * que aún no la tienen (columna ordencorte NULL). Requiere docs/sql/programacion-taller-semana-ordencorte.sql.
 *
 * Regla, procesando las líneas de la más antigua a la más nueva (fecreg):
 * - Se le asigna la OC viva (con saldo en corte u OC) más antigua del mismo modelo/color que ya existía
 *   cuando se creó la línea y que no esté tomada por otra línea.
 * - Si no hay ninguna, su OC ya se cortó y se envió a taller: se le asigna la última OC ya ejecutada anterior
 *   a la línea (queda como "Consumido"; -1 si no se encuentra). Las prioridades en ese caso salen de la bandeja.
 *
 * Uso:
 *   php scripts/migrar_programacion_ordencorte.php            vista previa, no escribe nada
 *   php scripts/migrar_programacion_ordencorte.php --aplicar  guarda los cambios
 */

require_once __DIR__ . '/../modelos/conexion.php';
require_once __DIR__ . '/../modelos/programacion-taller-semana.modelo.php';

$aplicar = in_array('--aplicar', $argv, true);
$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$tieneColumna = (bool) $pdo->query("SHOW COLUMNS FROM programacion_taller_semanajf LIKE 'ordencorte'")->fetch()
	&& (bool) $pdo->query("SHOW COLUMNS FROM programacion_taller_prioridadjf LIKE 'ordencorte'")->fetch();
if (!$tieneColumna && $aplicar) {
	fwrite(STDERR, "Falta la columna ordencorte: ejecuta antes docs/sql/programacion-taller-semana-ordencorte.sql\n");
	exit(1);
}

$filas = array();
foreach (array('programacion_taller_semanajf' => 'P', 'programacion_taller_prioridadjf' => 'R') as $tabla => $tipo) {
	$sql = "SELECT '{$tipo}' AS tipo, id, TRIM(modelo) AS modelo, TRIM(IFNULL(cod_color, '')) AS cod_color, color,
			" . ($tipo === 'P' ? "anio, semana" : "NULL AS anio, NULL AS semana") . ",
			cod_sector, cantidad, fecreg, " . ($tieneColumna ? "ordencorte" : "NULL AS ordencorte") . "
		FROM {$tabla} WHERE estado = 1";
	foreach ($pdo->query($sql) as $r) {
		$filas[] = $r;
	}
}
usort($filas, function ($a, $b) {
	return strcmp($a['fecreg'] . $a['tipo'] . sprintf('%09d', $a['id']), $b['fecreg'] . $b['tipo'] . sprintf('%09d', $b['id']));
});

$mapa = ModeloProgramacionTallerSemana::mdlSaldosPorOc();

// OC ya tomadas por líneas que tienen OC (re-ejecución del script)
$usadas = array();
foreach ($filas as $f) {
	if ($f['ordencorte'] !== null) {
		$usadas[$f['modelo'] . '|' . $f['cod_color']][(int) $f['ordencorte']] = true;
	}
}

// Historial de OC por modelo/color (incluidas las ya ejecutadas) para las líneas consumidas
$artClave = array();
foreach ($pdo->query("SELECT articulo, CONCAT(TRIM(modelo), '|', TRIM(IFNULL(cod_color, ''))) AS clave FROM articulojf") as $r) {
	$artClave[$r['articulo']] = $r['clave'];
}
$historial = array();
foreach ($pdo->query(
	"SELECT DISTINCT d.articulo, d.ordencorte, DATE(o.fecha) AS fecha
	 FROM detalles_ordencortejf d
	 INNER JOIN ordencortejf o ON o.codigo = d.ordencorte"
) as $r) {
	if (isset($artClave[$r['articulo']])) {
		$historial[$artClave[$r['articulo']]][(int) $r['ordencorte']] = $r['fecha'];
	}
}
foreach ($historial as $k => $ocs) {
	krsort($historial[$k]);
}

$stP = $pdo->prepare("UPDATE programacion_taller_semanajf SET ordencorte = :oc WHERE id = :id AND ordencorte IS NULL");
$stR = $pdo->prepare("UPDATE programacion_taller_prioridadjf SET ordencorte = :oc WHERE id = :id AND ordencorte IS NULL");
$stRBaja = $pdo->prepare(
	"UPDATE programacion_taller_prioridadjf
	 SET ordencorte = :oc, estado = 0, usumod = 'migracion_oc', fecmod = NOW()
	 WHERE id = :id AND ordencorte IS NULL"
);

$resumen = array('P_viva' => 0, 'P_consumida' => 0, 'R_viva' => 0, 'R_baja' => 0);
$lineas = array();

if ($aplicar) {
	$pdo->beginTransaction();
}
try {
	foreach ($filas as $f) {
		if ($f['ordencorte'] !== null) {
			continue;
		}
		$clave = $f['modelo'] . '|' . $f['cod_color'];
		$fechaFila = substr($f['fecreg'], 0, 10);
		$vivas = isset($mapa[$clave]) ? $mapa[$clave] : array();

		$asignada = null;
		foreach ($vivas as $oc => $s) { // ascendente: la OC más antigua primero
			if ((int) $s['saldo'] < 1 || isset($usadas[$clave][$oc])) {
				continue;
			}
			if ($oc > 0 && $s['fecha_oc'] !== null && $s['fecha_oc'] > $fechaFila) {
				continue; // OC creada después de la línea: no es la que se programó
			}
			$asignada = $oc;
			break;
		}

		if ($asignada !== null) {
			$usadas[$clave][$asignada] = true;
			$estado = $f['tipo'] === 'P' ? 'P_viva' : 'R_viva';
			$accion = 'OC viva ' . $asignada;
			if ($aplicar) {
				$st = $f['tipo'] === 'P' ? $stP : $stR;
				$st->execute(array(':oc' => $asignada, ':id' => $f['id']));
			}
		} else {
			$asignada = -1;
			$hist = isset($historial[$clave]) ? $historial[$clave] : array();
			foreach ($hist as $oc => $fechaOc) { // de la más nueva a la más antigua
				if ($fechaOc <= $fechaFila && !isset($vivas[$oc])) {
					$asignada = $oc;
					break;
				}
			}
			if ($f['tipo'] === 'P') {
				$estado = 'P_consumida';
				$accion = 'consumida (OC ' . $asignada . ')';
				if ($aplicar) {
					$stP->execute(array(':oc' => $asignada, ':id' => $f['id']));
				}
			} else {
				$estado = 'R_baja';
				$accion = 'sale de prioridad (OC ' . $asignada . ' ya ejecutada)';
				if ($aplicar) {
					$stRBaja->execute(array(':oc' => $asignada, ':id' => $f['id']));
				}
			}
		}
		$resumen[$estado]++;

		$ocsTxt = array();
		foreach ($vivas as $oc => $s) {
			$ocsTxt[] = 'OC' . $oc . ($s['fecha_oc'] ? '(' . $s['fecha_oc'] . ')' : '') . '=' . $s['saldo'];
		}
		$lineas[] = implode("\t", array(
			$f['tipo'] === 'P' ? 'Programado' : 'Prioridad',
			$f['id'],
			$f['modelo'],
			$f['cod_color'],
			$f['color'],
			$f['tipo'] === 'P' ? $f['anio'] . '-S' . $f['semana'] : '',
			$f['cod_sector'],
			$f['cantidad'],
			$fechaFila,
			$accion,
			implode(' | ', $ocsTxt)
		));
	}
	if ($aplicar) {
		$pdo->commit();
	}
} catch (Exception $e) {
	if ($aplicar && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, "Error, no se guardó nada: " . $e->getMessage() . "\n");
	exit(1);
}

$salida = sys_get_temp_dir() . '/migracion_programacion_ordencorte_' . date('Ymd_His') . '.tsv';
file_put_contents(
	$salida,
	"tipo\tid\tmodelo\tcod_color\tcolor\tsemana\ttaller\tcantidad\tfecreg\taccion\tocs_vivas\n" . implode("\n", $lineas) . "\n"
);

echo ($aplicar ? "APLICADO" : "VISTA PREVIA (sin cambios; usa --aplicar para guardar)") . "\n";
echo "Programados con OC viva:        {$resumen['P_viva']}\n";
echo "Programados ya consumidos:      {$resumen['P_consumida']}\n";
echo "Prioridades con OC viva:        {$resumen['R_viva']}\n";
echo "Prioridades que salen (OC ejecutada): {$resumen['R_baja']}\n";
echo "Detalle: {$salida}\n";
