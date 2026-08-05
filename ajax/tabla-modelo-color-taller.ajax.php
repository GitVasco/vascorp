<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/modelo-color-taller.controlador.php";
require_once "../modelos/modelo-color-taller.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!ControladorModeloColorTaller::ctrPuedeProduccion()) {
	echo json_encode(array("data" => array()));
	return;
}

$filtros = array(
	"modelo" => isset($_POST["modelo"]) ? $_POST["modelo"] : "",
	"cod_color" => isset($_POST["cod_color"]) ? $_POST["cod_color"] : "",
	"cod_sector" => isset($_POST["cod_sector"]) ? $_POST["cod_sector"] : "",
	"estado" => isset($_POST["estado"]) ? $_POST["estado"] : ""
);

$lista = ControladorModeloColorTaller::ctrListar($filtros);
$data = array();

if (is_array($lista)) {
	foreach ($lista as $a) {
		$id = (int) $a["id"];
		$modeloTxt = htmlspecialchars($a["modelo"], ENT_QUOTES, "UTF-8");
		if (!empty($a["nombre_modelo"])) {
			$modeloTxt .= " — " . htmlspecialchars($a["nombre_modelo"], ENT_QUOTES, "UTF-8");
		}

		if ($a["cod_color"] === "" || $a["cod_color"] === null) {
			$colorTxt = "<span class='label label-info'>Todo el modelo</span>";
		} else {
			$colorTxt = htmlspecialchars($a["cod_color"], ENT_QUOTES, "UTF-8");
			if (!empty($a["nom_color"])) {
				$colorTxt .= " — " . htmlspecialchars($a["nom_color"], ENT_QUOTES, "UTF-8");
			}
		}

		$tallerTxt = htmlspecialchars($a["cod_sector"], ENT_QUOTES, "UTF-8");
		if (!empty($a["nom_sector"])) {
			$tallerTxt .= " — " . htmlspecialchars($a["nom_sector"], ENT_QUOTES, "UTF-8");
		}

		$estadoHtml = ((int) $a["estado"] === 1)
			? "<span class='label label-success'>Activo</span>"
			: "<span class='label label-default'>Inactivo</span>";

		$obs = isset($a["observacion"]) && $a["observacion"] !== "" && $a["observacion"] !== null
			? htmlspecialchars($a["observacion"], ENT_QUOTES, "UTF-8")
			: "—";

		$botones = "<div class='btn-group'>"
			. "<button class='btn btn-xs btn-warning btnEditarModeloColorTaller' data-id='" . $id
			. "' title='Editar'><i class='fa fa-pencil'></i></button>"
			. "<button class='btn btn-xs btn-danger btnEliminarModeloColorTaller' data-id='" . $id
			. "' data-modelo='" . htmlspecialchars($a["modelo"], ENT_QUOTES, "UTF-8")
			. "' data-color='" . htmlspecialchars((string) $a["cod_color"], ENT_QUOTES, "UTF-8")
			. "' title='Eliminar'><i class='fa fa-trash'></i></button>"
			. "</div>";

		$data[] = array(
			$modeloTxt,
			$colorTxt,
			$tallerTxt,
			$estadoHtml,
			$obs,
			$botones
		);
	}
}

echo json_encode(array("data" => $data));
