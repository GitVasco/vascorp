<?php

require_once "../../controladores/grupos-empresariales.controlador.php";
require_once "../../modelos/grupos-empresariales.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../modelos/categorias-clientes.modelo.php";

class TablaGruposEmpresariales
{

	private function formatoMonto($valor)
	{

		if ($valor === null || $valor === "") {
			return "<span class='text-muted'>—</span>";
		}

		return "<span class='text-right' style='display:block;'>" .
			number_format((float) $valor, 2, ".", ",") .
			"</span>";
	}

	public function mostrarTablaGrupos()
	{

		$grupos = ControladorGruposEmpresariales::ctrMostrarGrupos(null, null);
		$data = array();

		if (is_array($grupos) && count($grupos) > 0) {

			$codigosGrupo = array();
			$idsCategoria = array();

			foreach ($grupos as $grupo) {
				if (!empty($grupo["codigo"])) {
					$codigosGrupo[] = $grupo["codigo"];
				}
				if (!empty($grupo["categoria_id"])) {
					$idsCategoria[] = (int) $grupo["categoria_id"];
				}
			}

			$montosMes = ModeloCategoriasClientes::mdlMontoFacturadoMesGrupos(array_values(array_unique($codigosGrupo)));
			$montos12m = ModeloCategoriasClientes::mdlMontoFacturado12mGrupos(array_values(array_unique($codigosGrupo)));
			$requisitos = ModeloCategoriasClientes::mdlRequisitosMontoPorCategorias(array_values(array_unique($idsCategoria)));

			foreach ($grupos as $grupo) {

				$estado = $grupo["estado"] == 1
					? "<span class='label label-success'>Activo</span>"
					: "<span class='label label-danger'>Inactivo</span>";

				$nombreEsc = htmlspecialchars($grupo["nombre"], ENT_QUOTES, "UTF-8");
				$totalClientes = isset($grupo["total_clientes"]) ? (int) $grupo["total_clientes"] : 0;
				$codigoGrupo = $grupo["codigo"];

				$categoriaHtml = ControladorCategoriasClientes::ctrHtmlBadgeCategoria(
					isset($grupo["categoria_comercial"]) ? $grupo["categoria_comercial"] : "",
					isset($grupo["categoria_codigo"]) ? $grupo["categoria_codigo"] : "",
					isset($grupo["categoria_color"]) ? $grupo["categoria_color"] : null
				);

				$zonaNombre = isset($grupo["zona_nombre"]) ? trim((string) $grupo["zona_nombre"]) : "";
				if ($zonaNombre !== "") {
					$zonaColor = !empty($grupo["zona_color"]) ? $grupo["zona_color"] : "#777777";
					$zonaHtml = "<span class='label' style='background-color:" .
						htmlspecialchars($zonaColor, ENT_QUOTES, "UTF-8") .
						";'>" .
						htmlspecialchars($zonaNombre, ENT_QUOTES, "UTF-8") .
						"</span>";
				} else {
					$zonaHtml = "<span class='text-muted'>—</span>";
				}

				$ventasMes = isset($montosMes[$codigoGrupo]) ? $montosMes[$codigoGrupo] : 0;
				$ventas12m = isset($montos12m[$codigoGrupo]) ? $montos12m[$codigoGrupo] : 0;

				$idCat = isset($grupo["categoria_id"]) ? (int) $grupo["categoria_id"] : 0;
				$requisito = ($idCat > 0 && array_key_exists($idCat, $requisitos))
					? $requisitos[$idCat]
					: null;

				$botones = "<div class='btn-group'>"
					. "<button class='btn btn-xs btn-info btnVerClientesGrupo' codigoGrupo='" . $codigoGrupo . "' nombreGrupo='" . $nombreEsc . "' data-toggle='modal' data-target='#modalClientesGrupo'><i class='fa fa-users'></i></button>"
					. "<button class='btn btn-xs btn-warning btnEditarGrupo' idGrupo='" . $grupo["id"] . "' data-toggle='modal' data-target='#modalEditarGrupo'><i class='fa fa-pencil'></i></button>"
					. "<button class='btn btn-xs btn-danger btnEliminarGrupo' idGrupo='" . $grupo["id"] . "'><i class='fa fa-times'></i></button>"
					. "</div>";

				$data[] = array(
					$codigoGrupo,
					$grupo["nombre"],
					(string) $totalClientes,
					$categoriaHtml,
					$zonaHtml,
					$this->formatoMonto($ventasMes),
					$this->formatoMonto($ventas12m),
					$this->formatoMonto($requisito),
					$estado,
					$botones
				);
			}
		}

		echo json_encode(array("data" => $data));
	}
}

$activarGrupos = new TablaGruposEmpresariales();
$activarGrupos->mostrarTablaGrupos();
