<?php

require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../modelos/categorias-clientes.modelo.php";

class TablaCategoriasComerciales
{

	private function formatoMonto($valor)
	{

		if ($valor === null || $valor === "") {
			return "<span class='text-muted'>—</span>";
		}

		return number_format((float) $valor, 2, ".", ",");
	}

	private function formatoPct($valor)
	{

		if ($valor === null || $valor === "") {
			return "<span class='text-muted'>—</span>";
		}

		return number_format((float) $valor, 2, ".", ",") . "%";
	}

	public function mostrarTabla()
	{

		$categorias = ControladorCategoriasClientes::ctrListarCategorias();
		$data = array();

		if (is_array($categorias) && count($categorias) > 0) {

			foreach ($categorias as $cat) {

				$estadoHtml = ((int) $cat["estado"] === 1)
					? "<span class='label label-success'>Activa</span>"
					: "<span class='label label-danger'>Inactiva</span>";

				$id = (int) $cat["id"];
				$estadoActual = (int) $cat["estado"];
				$nuevoEstado = $estadoActual === 1 ? 0 : 1;
				$iconoEstado = $estadoActual === 1 ? "fa-toggle-on" : "fa-toggle-off";
				$claseEstado = $estadoActual === 1 ? "btn-success" : "btn-default";
				$titleEstado = $estadoActual === 1 ? "Desactivar" : "Activar";

				$botones = "<div class='btn-group'>"
					. "<button class='btn btn-xs btn-warning btnEditarCategoriaComercial' idCategoria='" . $id . "' data-toggle='modal' data-target='#modalEditarCategoriaComercial' title='Editar'><i class='fa fa-pencil'></i></button>"
					. "<button class='btn btn-xs " . $claseEstado . " btnToggleEstadoCategoria' idCategoria='" . $id . "' nuevoEstado='" . $nuevoEstado . "' title='" . $titleEstado . "'><i class='fa " . $iconoEstado . "'></i></button>"
					. "</div>";

				$totalClientes = isset($cat["total_clientes"]) ? (int) $cat["total_clientes"] : 0;
				$totalGrupos = isset($cat["total_grupos"]) ? (int) $cat["total_grupos"] : 0;
				$totalPorRevisar = isset($cat["total_por_revisar"]) ? (int) $cat["total_por_revisar"] : 0;

				$porRevisarHtml = $totalPorRevisar > 0
					? "<span class='label label-warning'>" . $totalPorRevisar . "</span>"
					: "0";

				$montoVentas = isset($cat["monto_ventas_anual"]) ? $cat["monto_ventas_anual"] : null;
				$lineaMinima = isset($cat["linea_minima"]) ? $cat["linea_minima"] : null;
				$dtoVenta = isset($cat["descuento_venta_pct"]) ? $cat["descuento_venta_pct"] : null;
				$dtoPronto = isset($cat["descuento_pronto_pago_pct"]) ? $cat["descuento_pronto_pago_pct"] : null;

				$nombreBadge = ControladorCategoriasClientes::ctrHtmlBadgeCategoria(
					$cat["nombre"],
					isset($cat["codigo"]) ? $cat["codigo"] : "",
					isset($cat["color"]) ? $cat["color"] : null
				);

				$data[] = array(
					(string) (int) $cat["orden"],
					$cat["codigo"],
					$nombreBadge,
					isset($cat["descripcion"]) ? $cat["descripcion"] : "",
					$this->formatoMonto($montoVentas),
					$this->formatoMonto($lineaMinima),
					$this->formatoPct($dtoVenta),
					$this->formatoPct($dtoPronto),
					(string) $totalClientes,
					(string) $totalGrupos,
					$porRevisarHtml,
					$estadoHtml,
					$botones
				);
			}
		}

		echo json_encode(array("data" => $data));
	}
}

$tabla = new TablaCategoriasComerciales();
$tabla->mostrarTabla();
