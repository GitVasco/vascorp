<?php

require_once dirname(__FILE__) . "/../../modelos/recetas-modelo.modelo.php";
require_once dirname(__FILE__) . "/../../modelos/materiaprima.modelo.php";

function mpRecetasCampo($row, $claves)
{
	foreach ($claves as $k) {
		if (isset($row[$k]) && trim((string) $row[$k]) !== "") {
			return trim((string) $row[$k]);
		}
	}
	return "";
}

function mpRecetasPrefijosLinea()
{
	static $prefs = null;
	if ($prefs !== null) {
		return $prefs;
	}
	$prefs = array();
	$filas = ModeloMateriaPrima::mdlMostrarLineas();
	if (!$filas) {
		return $prefs;
	}
	foreach ($filas as $row) {
		$codigo = strtoupper(mpRecetasCampo($row, array("Des_Corta", "des_corta")));
		if ($codigo === "") {
			continue;
		}
		$prefs[] = array(
			"codigo" => $codigo,
			"nombre" => mpRecetasCampo($row, array("Des_Larga", "des_larga"))
		);
	}
	usort($prefs, function ($a, $b) {
		return strlen($b["codigo"]) - strlen($a["codigo"]);
	});
	return $prefs;
}

function mpRecetasLineaDeCodigo($codigoSub)
{
	$codigoSub = strtoupper(trim((string) $codigoSub));
	foreach (mpRecetasPrefijosLinea() as $lin) {
		if ($lin["codigo"] !== "" && strpos($codigoSub, $lin["codigo"]) === 0) {
			return $lin;
		}
	}
	return array("codigo" => "", "nombre" => "");
}

function mpRecetasListarData()
{
	$filas = ModeloRecetasModelo::mdlMpUsadasEnRecetas(false);
	if ($filas === false) {
		return false;
	}
	$data = array();
	foreach ($filas as $f) {
		$sub = strtoupper(trim((string) $f["codigo_sublinea"]));
		$lin = mpRecetasLineaDeCodigo($sub);
		$data[] = array(
			"codpro" => (string) $f["codpro"],
			"codfab" => (string) $f["codfab"],
			"despro" => (string) $f["despro"],
			"linea" => $lin["codigo"],
			"linea_nombre" => $lin["nombre"],
			"codigo_sublinea" => $sub,
			"color" => (string) $f["color"],
			"unidad" => (string) $f["unidad"],
			"stock" => (float) $f["stock"],
			"costo" => (float) $f["costo"],
			"estpro" => (string) $f["estpro"]
		);
	}
	return $data;
}

function mpRecetasFiltrarData($data, $linea, $sublinea, $mp, $costo)
{
	$linea = strtoupper(trim((string) $linea));
	$sublinea = strtoupper(trim((string) $sublinea));
	$mp = trim((string) $mp);
	$costo = trim((string) $costo);
	$out = array();
	foreach ($data as $r) {
		if ($linea !== "" && strtoupper((string) $r["linea"]) !== $linea) {
			continue;
		}
		if ($sublinea !== "" && strtoupper((string) $r["codigo_sublinea"]) !== $sublinea) {
			continue;
		}
		if ($mp !== "" && (string) $r["codpro"] !== $mp) {
			continue;
		}
		$tiene = ((float) $r["costo"]) > 0;
		if ($costo === "con" && !$tiene) {
			continue;
		}
		if ($costo === "sin" && $tiene) {
			continue;
		}
		$out[] = $r;
	}
	usort($out, function ($a, $b) {
		return strnatcasecmp((string) $a["codfab"], (string) $b["codfab"]);
	});
	return $out;
}

function mpRecetasNormalizarEncabezado($valor)
{
	$valor = strtolower(trim((string) $valor));
	$valor = str_replace(array("á", "é", "í", "ó", "ú", "ñ"), array("a", "e", "i", "o", "u", "n"), $valor);
	$valor = preg_replace('/[^a-z0-9]+/', "_", $valor);
	$valor = trim($valor, "_");
	$alias = array(
		"codigo" => "codigo",
		"codpro" => "codigo",
		"cod_pro" => "codigo",
		"mp_codigo" => "codigo",
		"mp" => "codigo",
		"costo" => "costo",
		"cospro" => "costo",
		"costo_unitario" => "costo",
		"precio" => "costo"
	);
	return isset($alias[$valor]) ? $alias[$valor] : $valor;
}

function mpRecetasParseCosto($raw)
{
	$raw = trim((string) $raw);
	if ($raw === "") {
		return null;
	}
	$raw = str_replace(array(" ", "S/", "s/"), "", $raw);
	if (strpos($raw, ",") !== false && strpos($raw, ".") !== false) {
		if (strrpos($raw, ",") > strrpos($raw, ".")) {
			$raw = str_replace(".", "", $raw);
			$raw = str_replace(",", ".", $raw);
		} else {
			$raw = str_replace(",", "", $raw);
		}
	} elseif (strpos($raw, ",") !== false) {
		$raw = str_replace(",", ".", $raw);
	}
	if (!is_numeric($raw)) {
		return false;
	}
	return (float) $raw;
}

function mpRecetasNormalizarCodigo($raw)
{
	$raw = trim((string) $raw);
	$raw = preg_replace('/^=\"?|\="$/', "", $raw);
	$raw = trim($raw, " \t\"'");
	if ($raw === "") {
		return "";
	}
	if (ctype_digit($raw) && strlen($raw) < 5) {
		return str_pad($raw, 5, "0", STR_PAD_LEFT);
	}
	return $raw;
}

function mpRecetasLeerArchivoCostos($archivoTmp, $nombreOriginal)
{
	$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
	if (!in_array($ext, array("csv", "xls", "xlsx"), true)) {
		return array("ok" => false, "mensaje" => "El archivo debe ser CSV, XLS o XLSX");
	}

	$filas = array();
	if ($ext === "csv") {
		$manejador = fopen($archivoTmp, "rb");
		if ($manejador === false) {
			return array("ok" => false, "mensaje" => "No se pudo leer el archivo");
		}
		$primera = fgets($manejador);
		if ($primera === false) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "El archivo está vacío");
		}
		$delimitador = (substr_count($primera, ";") > substr_count($primera, ",")) ? ";" : ",";
		rewind($manejador);
		$encabezadosRaw = fgetcsv($manejador, 0, $delimitador);
		if (!is_array($encabezadosRaw)) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "No se pudo leer el encabezado");
		}
		$pos = array();
		foreach ($encabezadosRaw as $i => $enc) {
			$norm = mpRecetasNormalizarEncabezado($enc);
			if ($norm === "codigo" || $norm === "costo") {
				$pos[$norm] = $i;
			}
		}
		if (!isset($pos["codigo"]) || !isset($pos["costo"])) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "Faltan las columnas codigo y costo");
		}
		$n = 1;
		while (($valores = fgetcsv($manejador, 0, $delimitador)) !== false) {
			$n++;
			$codigo = mpRecetasNormalizarCodigo(isset($valores[$pos["codigo"]]) ? $valores[$pos["codigo"]] : "");
			$costoRaw = isset($valores[$pos["costo"]]) ? $valores[$pos["costo"]] : "";
			if ($codigo === "" && trim((string) $costoRaw) === "") {
				continue;
			}
			$filas[] = array("fila" => $n, "codigo" => $codigo, "costo_raw" => $costoRaw);
		}
		fclose($manejador);
		return array("ok" => true, "filas" => $filas);
	}

	$phpExcelPath = dirname(__FILE__) . "/../../vistas/reportes_excel/Classes/PHPExcel.php";
	if (!file_exists($phpExcelPath)) {
		return array("ok" => false, "mensaje" => "No está disponible el lector de Excel");
	}
	require_once $phpExcelPath;
	try {
		$excel = PHPExcel_IOFactory::load($archivoTmp);
		$sheet = $excel->getActiveSheet();
		$highestRow = (int) $sheet->getHighestDataRow();
		$highestCol = $sheet->getHighestDataColumn();
		$encabezadosRaw = $sheet->rangeToArray("A1:" . $highestCol . "1", null, true, false);
		$encabezadosRaw = isset($encabezadosRaw[0]) ? $encabezadosRaw[0] : array();
		$pos = array();
		foreach ($encabezadosRaw as $i => $enc) {
			$norm = mpRecetasNormalizarEncabezado($enc);
			if ($norm === "codigo" || $norm === "costo") {
				$pos[$norm] = $i;
			}
		}
		if (!isset($pos["codigo"]) || !isset($pos["costo"])) {
			return array("ok" => false, "mensaje" => "Faltan las columnas codigo y costo");
		}
		for ($n = 2; $n <= $highestRow; $n++) {
			$valores = $sheet->rangeToArray("A{$n}:" . $highestCol . $n, null, true, false);
			$valores = isset($valores[0]) ? $valores[0] : array();
			$codigo = mpRecetasNormalizarCodigo(isset($valores[$pos["codigo"]]) ? $valores[$pos["codigo"]] : "");
			$costoRaw = isset($valores[$pos["costo"]]) ? $valores[$pos["costo"]] : "";
			if ($codigo === "" && trim((string) $costoRaw) === "") {
				continue;
			}
			$filas[] = array("fila" => $n, "codigo" => $codigo, "costo_raw" => $costoRaw);
		}
		return array("ok" => true, "filas" => $filas);
	} catch (Exception $e) {
		return array("ok" => false, "mensaje" => "No se pudo leer el Excel");
	}
}

function mpRecetasImportarCostos($filas)
{
	$actualizados = 0;
	$omitidos = 0;
	$errores = array();
	$vistos = array();

	foreach ($filas as $f) {
		$fila = isset($f["fila"]) ? (int) $f["fila"] : 0;
		$codigo = mpRecetasNormalizarCodigo(isset($f["codigo"]) ? $f["codigo"] : "");
		$costo = mpRecetasParseCosto(isset($f["costo_raw"]) ? $f["costo_raw"] : "");
		if ($codigo === "") {
			$errores[] = "Fila {$fila}: falta el código";
			continue;
		}
		if ($costo === null || $costo === 0.0) {
			$omitidos++;
			continue;
		}
		if ($costo === false || $costo < 0) {
			$errores[] = "Fila {$fila} ({$codigo}): costo inválido";
			continue;
		}
		if (isset($vistos[$codigo])) {
			$omitidos++;
			continue;
		}
		$vistos[$codigo] = true;
		$existe = ModeloMateriaPrima::mdlMostrarMateriaPrima($codigo);
		if (!$existe) {
			$errores[] = "Fila {$fila} ({$codigo}): no existe o está inactiva";
			continue;
		}
		$ok = ModeloMateriaPrima::mdlActualizarCostoFicha(
			$codigo,
			number_format($costo, 6, ".", "")
		);
		if ($ok === "ok") {
			$actualizados++;
		} else {
			$errores[] = "Fila {$fila} ({$codigo}): no se pudo actualizar";
		}
	}

	$mensaje = "Se actualizaron {$actualizados} costo(s)";
	if ($omitidos > 0) {
		$mensaje .= ". {$omitidos} fila(s) omitida(s)";
	}
	if (count($errores) > 0) {
		$mensaje .= ". " . count($errores) . " con error";
	}

	return array(
		"ok" => true,
		"actualizados" => $actualizados,
		"omitidos" => $omitidos,
		"errores" => $errores,
		"mensaje" => $mensaje
	);
}
