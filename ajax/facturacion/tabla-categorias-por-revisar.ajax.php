<?php

require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../modelos/categorias-clientes.modelo.php";

class TablaCategoriasPorRevisar
{

	private function formatoMonto($valor)
	{

		if ($valor === null || $valor === "") {
			return "-";
		}

		return number_format((float) $valor, 2, ".", ",");
	}

	public function mostrarTabla()
	{

		$filas = ControladorCategoriasClientes::ctrListarBandejaRevision();
		$data = array();

		if (is_array($filas) && count($filas) > 0) {

			foreach ($filas as $fila) {

				$tipoHtml = $fila["tipo_entidad"] === "grupo"
					? "<span class='label label-primary'>Grupo</span>"
					: "<span class='label label-default'>Cliente</span>";

				$categoria = ControladorCategoriasClientes::ctrHtmlBadgeCategoria(
					isset($fila["categoria_nombre"]) ? $fila["categoria_nombre"] : "",
					isset($fila["categoria_codigo"]) ? $fila["categoria_codigo"] : "",
					isset($fila["categoria_color"]) ? $fila["categoria_color"] : null
				);

				$monto12 = isset($fila["monto_12m"]) ? (float) $fila["monto_12m"] : 0;
				$montoHtml = $this->formatoMonto($monto12);

				$requisito = isset($fila["requisito_monto"]) ? $fila["requisito_monto"] : null;
				$requisitoHtml = ($requisito === null || $requisito === "")
					? "<span class='text-muted'>Sin umbral</span>"
					: $this->formatoMonto($requisito);

				$indicativo = isset($fila["indicativo_requisito"]) ? $fila["indicativo_requisito"] : "sin_umbral";
				if ($indicativo === "alcanza") {
					$vsHtml = "<span class='label label-success'>Alcanza</span>";
				} elseif ($indicativo === "no_alcanza") {
					$vsHtml = "<span class='label label-danger'>No alcanza</span>";
				} else {
					$vsHtml = "<span class='label label-default'>Sin umbral</span>";
				}

				$cumplimiento = isset($fila["cumplimiento"]) ? $fila["cumplimiento"] : "sin_categoria";
				switch ($cumplimiento) {
					case "cumple":
						$cumplHtml = "<span class='label label-success'>Cumple</span>";
						break;
					case "no_cumple":
						$cumplHtml = "<span class='label label-danger'>No cumple</span>";
						break;
					case "por_revisar":
						$cumplHtml = "<span class='label label-warning'>Por revisar</span>";
						break;
					case "sin_categoria":
						$cumplHtml = "<span class='label label-default'>Sin categoría</span>";
						break;
					default:
						$cumplHtml = "<span class='label label-info'>Pendiente</span>";
						break;
				}

				$origen = isset($fila["origen"]) && $fila["origen"]
					? htmlspecialchars($fila["origen"], ENT_QUOTES, "UTF-8")
					: "-";

				if (!empty($fila["es_excepcion"])) {
					$origen = "<span class='label label-warning'>excepción</span>";
				}

				$vence = "-";
				if (!empty($fila["vigencia_hasta"])) {
					$vence = substr($fila["vigencia_hasta"], 0, 10);
				}

				$motivoBandeja = isset($fila["motivo_bandeja"])
					? htmlspecialchars($fila["motivo_bandeja"], ENT_QUOTES, "UTF-8")
					: "";

				$idAsig = isset($fila["id_asignacion"]) ? (int) $fila["id_asignacion"] : 0;
				$idCat = isset($fila["id_categoria"]) ? (int) $fila["id_categoria"] : 0;
				$esExc = !empty($fila["es_excepcion"]) ? 1 : 0;
				$motivo = isset($fila["motivo"]) ? htmlspecialchars($fila["motivo"], ENT_QUOTES, "UTF-8") : "";
				$nombreEsc = htmlspecialchars($fila["nombre_entidad"], ENT_QUOTES, "UTF-8");

				$botones = "<button type='button' class='btn btn-xs btn-warning btnRevisarCategoriaBandeja'"
					. " tipoEntidad='" . htmlspecialchars($fila["tipo_entidad"], ENT_QUOTES, "UTF-8") . "'"
					. " codigoEntidad='" . htmlspecialchars($fila["codigo_entidad"], ENT_QUOTES, "UTF-8") . "'"
					. " nombreEntidad='" . $nombreEsc . "'"
					. " idAsignacion='" . $idAsig . "'"
					. " idCategoria='" . $idCat . "'"
					. " cumplimiento='" . htmlspecialchars($cumplimiento, ENT_QUOTES, "UTF-8") . "'"
					. " esExcepcion='" . $esExc . "'"
					. " motivo='" . $motivo . "'"
					. " vigenciaHasta='" . ($vence !== "-" ? $vence : "") . "'"
					. " data-toggle='modal' data-target='#modalRevisarCategoriaBandeja'>"
					. "<i class='fa fa-search'></i> Revisar</button>";

				$data[] = array(
					$tipoHtml,
					htmlspecialchars($fila["codigo_entidad"], ENT_QUOTES, "UTF-8"),
					$nombreEsc,
					$categoria,
					$montoHtml,
					$requisitoHtml,
					$vsHtml,
					$motivoBandeja,
					$cumplHtml,
					$origen,
					$vence,
					$botones
				);
			}
		}

		echo json_encode(array("data" => $data));
	}
}

$tabla = new TablaCategoriasPorRevisar();
$tabla->mostrarTabla();
