<?php

require_once "../../controladores/grupos-empresariales.controlador.php";
require_once "../../modelos/grupos-empresariales.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../modelos/categorias-clientes.modelo.php";

class TablaGruposEmpresariales
{

	public function mostrarTablaGrupos()
	{

		$grupos = ControladorGruposEmpresariales::ctrMostrarGrupos(null, null);
		$data = array();

		if (is_array($grupos) && count($grupos) > 0) {

			foreach ($grupos as $grupo) {

				$estado = $grupo["estado"] == 1
					? "<span class='label label-success'>Activo</span>"
					: "<span class='label label-danger'>Inactivo</span>";

				$nombreEsc = htmlspecialchars($grupo["nombre"], ENT_QUOTES, "UTF-8");
				$descripcion = isset($grupo["descripcion"]) ? $grupo["descripcion"] : "";
				$totalClientes = isset($grupo["total_clientes"]) ? (int) $grupo["total_clientes"] : 0;

				$categoriaHtml = ControladorCategoriasClientes::ctrHtmlBadgeCategoria(
					isset($grupo["categoria_comercial"]) ? $grupo["categoria_comercial"] : "",
					isset($grupo["categoria_codigo"]) ? $grupo["categoria_codigo"] : "",
					isset($grupo["categoria_color"]) ? $grupo["categoria_color"] : null
				);

				$botones = "<div class='btn-group'>"
					. "<button class='btn btn-xs btn-info btnVerClientesGrupo' codigoGrupo='" . $grupo["codigo"] . "' nombreGrupo='" . $nombreEsc . "' data-toggle='modal' data-target='#modalClientesGrupo'><i class='fa fa-users'></i></button>"
					. "<button class='btn btn-xs btn-warning btnEditarGrupo' idGrupo='" . $grupo["id"] . "' data-toggle='modal' data-target='#modalEditarGrupo'><i class='fa fa-pencil'></i></button>"
					. "<button class='btn btn-xs btn-danger btnEliminarGrupo' idGrupo='" . $grupo["id"] . "'><i class='fa fa-times'></i></button>"
					. "</div>";

				$data[] = array(
					$grupo["codigo"],
					$grupo["nombre"],
					$descripcion,
					(string) $totalClientes,
					$categoriaHtml,
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
