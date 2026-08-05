<?php

require_once "../../controladores/sectores.controlador.php";
require_once "../../modelos/sectores.modelo.php";

header("Content-Type: application/json; charset=utf-8");

class TablaSectores
{

	public function mostrarTablaSectores()
	{
		$sector = ControladorSectores::ctrMostrarSectores(null);
		$data = array();

		if (!is_array($sector) || count($sector) < 1) {
			echo json_encode(array("data" => array()));
			return;
		}

		foreach ($sector as $fila) {
			$cod = isset($fila["cod_sector"]) ? (string) $fila["cod_sector"] : "";
			$nom = isset($fila["nom_sector"]) ? (string) $fila["nom_sector"] : "";

			$tipo = ControladorSectores::ctrEsInterno($cod) ? "TALLER" : "SERVICIO";
			$tipoValor = (isset($fila["tipo"]) && ((int) $fila["tipo"] === 0 || $fila["tipo"] === "0")) ? "0" : "1";
			$estadoValor = (isset($fila["estado"]) && (int) $fila["estado"] === 0) ? 0 : 1;
			$estadoHtml = $estadoValor === 1
				? "<span class='label label-success'>Activo</span>"
				: "<span class='label label-default'>Inactivo</span>";

			$color = isset($fila["color"]) ? trim((string) $fila["color"]) : "";
			if ($color === "" || !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
				$color = ModeloSectores::mdlColorPastelPorDefecto($cod);
			}
			$colorEsc = htmlspecialchars($color, ENT_QUOTES, "UTF-8");
			$colorHtml = "<span style='display:inline-block;width:22px;height:22px;border-radius:4px;background:"
				. $colorEsc . ";border:1px solid #ccc;vertical-align:middle;margin-right:6px;'></span>"
				. "<small>" . $colorEsc . "</small>";

			$codAttr = htmlspecialchars($cod, ENT_QUOTES, "UTF-8");
			$botones = "<div class='btn-group'>"
				. "<button class='btn btn-xs btn-warning btnEditarSector' idSector='" . $codAttr
				. "' tipoSector='" . $tipoValor
				. "' estadoSector='" . $estadoValor
				. "' colorSector='" . $colorEsc
				. "' data-toggle='modal' data-target='#modalEditarSector'><i class='fa fa-pencil'></i></button>"
				. "<button class='btn btn-xs btn-danger btnEliminarSector' idSector='" . $codAttr
				. "'><i class='fa fa-times'></i></button></div>";

			$data[] = array(
				$cod,
				$nom,
				$tipo,
				$colorHtml,
				$estadoHtml,
				$botones
			);
		}

		echo json_encode(array("data" => $data), JSON_UNESCAPED_UNICODE);
	}
}

$activarSectores = new TablaSectores();
$activarSectores->mostrarTablaSectores();
