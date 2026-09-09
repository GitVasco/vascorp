<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "mp-recetas.lib.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["materiaprima"]) || (int) $_SESSION["materiaprima"] !== 1) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso", "data" => array()));
	return;
}

$accion = isset($_POST["accion"]) ? trim((string) $_POST["accion"]) : "";

if ($accion === "listar") {
	$data = mpRecetasListarData();
	if ($data === false) {
		echo json_encode(array("ok" => false, "mensaje" => "No se pudo consultar", "data" => array()));
		return;
	}
	echo json_encode(array(
		"ok" => true,
		"total" => count($data),
		"data" => $data
	));
	return;
}

if ($accion === "lineas") {
	$out = array();
	$vistos = array();
	foreach (mpRecetasPrefijosLinea() as $lin) {
		if ($lin["codigo"] === "" || isset($vistos[$lin["codigo"]])) {
			continue;
		}
		$vistos[$lin["codigo"]] = true;
		$out[] = $lin;
	}
	usort($out, function ($a, $b) {
		return strnatcasecmp($a["codigo"], $b["codigo"]);
	});
	echo json_encode(array("ok" => true, "data" => $out));
	return;
}

if ($accion === "sublineas") {
	$linea = strtoupper(trim(isset($_POST["linea"]) ? (string) $_POST["linea"] : ""));
	if ($linea === "") {
		echo json_encode(array("ok" => true, "data" => array()));
		return;
	}
	$filas = ModeloMateriaPrima::mdlMostrarSubLineas($linea);
	$out = array();
	$vistos = array();
	if ($filas) {
		foreach ($filas as $row) {
			$pref = strtoupper(mpRecetasCampo($row, array("Des_Corta", "des_corta")));
			$sub = trim(mpRecetasCampo($row, array("Valor_3", "valor_3")));
			$codigo = strtoupper($pref . $sub);
			if ($codigo === "" || isset($vistos[$codigo])) {
				continue;
			}
			$estado = mpRecetasCampo($row, array("Estado", "estado"));
			if ($estado !== "" && $estado !== "1") {
				continue;
			}
			$vistos[$codigo] = true;
			$out[] = array(
				"codigo" => $codigo,
				"nombre" => mpRecetasCampo($row, array("Des_Larga", "des_larga"))
			);
		}
	}
	usort($out, function ($a, $b) {
		return strnatcasecmp($a["codigo"], $b["codigo"]);
	});
	echo json_encode(array("ok" => true, "data" => $out));
	return;
}

if ($accion === "recetas") {
	$codpro = isset($_POST["codpro"]) ? trim((string) $_POST["codpro"]) : "";
	$filas = ModeloRecetasModelo::mdlRecetasQueUsanMp($codpro);
	$data = array();
	foreach ($filas as $f) {
		$data[] = array(
			"id" => isset($f["id"]) ? (int) $f["id"] : 0,
			"modelo" => (string) $f["modelo"],
			"nombre_modelo" => (string) $f["nombre_modelo"],
			"version" => isset($f["version"]) ? (int) $f["version"] : 0,
			"estado" => (string) $f["estado"]
		);
	}
	echo json_encode(array("ok" => true, "data" => $data));
	return;
}

if ($accion === "guardarCosto") {
	$codpro = isset($_POST["codpro"]) ? trim((string) $_POST["codpro"]) : "";
	$costoRaw = isset($_POST["costo"]) ? $_POST["costo"] : "";
	$codpro = mpRecetasNormalizarCodigo($codpro);
	$costo = mpRecetasParseCosto($costoRaw);
	if ($codpro === "") {
		echo json_encode(array("ok" => false, "mensaje" => "Falta el código"));
		return;
	}
	if ($costo === null || $costo === false || $costo <= 0) {
		echo json_encode(array("ok" => false, "mensaje" => "Ingresá un costo mayor a 0"));
		return;
	}
	$existe = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
	if (!$existe) {
		echo json_encode(array("ok" => false, "mensaje" => "La materia prima no existe o está inactiva"));
		return;
	}
	$ok = ModeloMateriaPrima::mdlActualizarCostoFicha(
		$codpro,
		number_format($costo, 6, ".", "")
	);
	if ($ok !== "ok") {
		echo json_encode(array("ok" => false, "mensaje" => "No se pudo guardar el costo"));
		return;
	}
	echo json_encode(array(
		"ok" => true,
		"mensaje" => "Costo actualizado",
		"codpro" => $codpro,
		"costo" => $costo
	));
	return;
}

if ($accion === "importarCostos") {
	if (!isset($_FILES["archivo"]) || !is_array($_FILES["archivo"])) {
		echo json_encode(array("ok" => false, "mensaje" => "Adjuntá el Excel"));
		return;
	}
	$archivo = $_FILES["archivo"];
	if (!isset($archivo["error"]) || (int) $archivo["error"] !== UPLOAD_ERR_OK) {
		echo json_encode(array("ok" => false, "mensaje" => "No se pudo subir el archivo"));
		return;
	}
	$nombre = isset($archivo["name"]) ? (string) $archivo["name"] : "";
	$tmp = isset($archivo["tmp_name"]) ? (string) $archivo["tmp_name"] : "";
	$leido = mpRecetasLeerArchivoCostos($tmp, $nombre);
	if (empty($leido["ok"])) {
		echo json_encode($leido);
		return;
	}
	echo json_encode(mpRecetasImportarCostos($leido["filas"]));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida", "data" => array()));
