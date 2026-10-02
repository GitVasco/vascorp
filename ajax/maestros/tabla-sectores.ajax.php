<?php

require_once "../../controladores/sectores.controlador.php";
require_once "../../modelos/sectores.modelo.php";
require_once "../../modelos/gre-manual.modelo.php";

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

		$datosGre = ModeloGreManual::mdlTodosDatosTaller();
		$tiposDoc = array("6" => "RUC", "1" => "DNI", "4" => "C.E.", "7" => "PAS.", "0" => "S/RUC", "A" => "C.D.");

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

			// Datos para guía de remisión: solo aplica a externos
			if ($tipo === "SERVICIO" && isset($datosGre[$cod])) {
				$g = $datosGre[$cod];
				$docHtml = htmlspecialchars((isset($tiposDoc[$g["tipo_doc"]]) ? $tiposDoc[$g["tipo_doc"]] : "") . " " . $g["doc"], ENT_QUOTES, "UTF-8");
				$razonHtml = htmlspecialchars($g["razon_social"], ENT_QUOTES, "UTF-8");
				$dirHtml = htmlspecialchars($g["direccion"] . ($g["dist"] !== "" ? " - " . $g["dist"] : ""), ENT_QUOTES, "UTF-8");
			} elseif ($tipo === "SERVICIO") {
				$docHtml = "<span class='label label-warning'>Sin datos</span>";
				$razonHtml = "";
				$dirHtml = "";
			} else {
				$docHtml = "<span class='text-muted'>—</span>";
				$razonHtml = "";
				$dirHtml = "";
			}

			$data[] = array(
				$cod,
				$nom,
				$tipo,
				$docHtml,
				$razonHtml,
				$dirHtml,
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
