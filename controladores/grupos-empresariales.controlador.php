<?php

class ControladorGruposEmpresariales
{

	static public function ctrCrearGrupo()
	{

		if (!isset($_POST["nuevoNombreGrupo"])) {
			return;
		}

		$nombre = trim($_POST["nuevoNombreGrupo"]);

		if ($nombre === "") {
			return;
		}

		$codigo = ModeloGruposEmpresariales::mdlSiguienteCodigoGrupo();

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();

		$datos = array(
			"codigo" => $codigo,
			"nombre" => $nombre,
			"descripcion" => trim(isset($_POST["nuevaDescripcionGrupo"]) ? $_POST["nuevaDescripcionGrupo"] : ""),
			"id_zona" => ControladorZonasComerciales::ctrIdZonaDesdePost("nuevaIdZonaGrupo"),
			"estado" => 1,
			"usureg" => isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "",
			"fecreg" => $fecha->format("Y-m-d H:i:s")
		);

		$respuesta = ModeloGruposEmpresariales::mdlIngresarGrupo($datos);

		if ($respuesta == "ok") {
			echo '<script>
				swal({
					type: "success",
					title: "El grupo empresarial ha sido guardado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function(result) {
					if (result.value) { window.location = "grupos-empresariales"; }
				});
			</script>';
		}
	}

	static public function ctrMostrarGrupos($item, $valor)
	{

		return ModeloGruposEmpresariales::mdlMostrarGrupos($item, $valor);
	}

	static public function ctrMostrarGruposActivos()
	{

		return ModeloGruposEmpresariales::mdlMostrarGruposActivos();
	}

	static public function ctrSiguienteCodigoGrupo()
	{

		return ModeloGruposEmpresariales::mdlSiguienteCodigoGrupo();
	}

	static public function ctrEditarGrupo()
	{

		if (!isset($_POST["editarNombreGrupo"]) || !isset($_POST["idGrupo"])) {
			return;
		}

		$id = (int) $_POST["idGrupo"];
		$nombre = trim($_POST["editarNombreGrupo"]);

		if ($nombre === "") {
			return;
		}

		$grupoActual = ModeloGruposEmpresariales::mdlMostrarGrupos("id", $id);
		if (!$grupoActual) {
			return;
		}

		$idZonaActual = isset($grupoActual["id_zona"]) ? $grupoActual["id_zona"] : null;

		$datos = array(
			"id" => $id,
			"nombre" => $nombre,
			"descripcion" => trim(isset($_POST["editarDescripcionGrupo"]) ? $_POST["editarDescripcionGrupo"] : ""),
			"id_zona" => ControladorZonasComerciales::ctrIdZonaDesdePost("editarIdZonaGrupo", $idZonaActual),
			"estado" => (int) (isset($_POST["editarEstadoGrupo"]) ? $_POST["editarEstadoGrupo"] : 1)
		);

		$respuesta = ModeloGruposEmpresariales::mdlEditarGrupo($datos);

		if ($respuesta == "ok") {
			echo '<script>
				swal({
					type: "success",
					title: "El grupo empresarial ha sido actualizado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function(result) {
					if (result.value) { window.location = "grupos-empresariales"; }
				});
			</script>';
		}
	}

	static public function ctrEliminarGrupo()
	{

		if (!isset($_GET["idGrupo"])) {
			return;
		}

		$id = (int) $_GET["idGrupo"];
		$grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("id", $id);

		if (!$grupo) {
			return;
		}

		$totalClientes = ModeloGruposEmpresariales::mdlContarClientesPorGrupo($grupo["codigo"]);
		if ($totalClientes > 0) {
			echo '<script>
				swal({
					type: "error",
					title: "No se puede eliminar el grupo",
					text: "Tiene ' . $totalClientes . ' cliente(s) asignado(s). Quítelos primero o desactívelo.",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function() { window.location = "grupos-empresariales"; });
			</script>';
			return;
		}

		$respuesta = ModeloGruposEmpresariales::mdlEliminarGrupo($id);

		if ($respuesta == "ok") {
			echo '<script>
				swal({
					type: "success",
					title: "El grupo empresarial ha sido eliminado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function(result) {
					if (result.value) { window.location = "grupos-empresariales"; }
				});
			</script>';
		}
	}

	static public function ctrAsignarCliente()
	{

		if (!isset($_POST["codigoClienteGrupo"]) || !isset($_POST["codigoGrupoAsignar"])) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "Faltan datos para asignar el cliente."
			));
			return;
		}

		$codigoCliente = trim($_POST["codigoClienteGrupo"]);
		$codigoGrupo = trim($_POST["codigoGrupoAsignar"]);

		if ($codigoCliente === "" || $codigoGrupo === "") {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "Seleccione un cliente y un grupo válidos."
			));
			return;
		}

		$grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $codigoGrupo);

		if (!$grupo) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El grupo no existe."
			));
			return;
		}

		if ((int) $grupo["estado"] !== 1) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El grupo está inactivo y no admite nuevos clientes."
			));
			return;
		}

		$cliente = ModeloGruposEmpresariales::mdlMostrarClientePorCodigo($codigoCliente);

		if (!$cliente || (int) $cliente["estado"] !== 1) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente no existe o está inactivo."
			));
			return;
		}

		$grupoActual = isset($cliente["grupo"]) ? trim($cliente["grupo"]) : "";
		if ($grupoActual !== "") {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente ya pertenece a otro grupo."
			));
			return;
		}

		$respuesta = ModeloGruposEmpresariales::mdlAsignarClienteAGrupo($codigoCliente, $codigoGrupo);

		if ($respuesta === "ok") {
			// Al entrar al grupo deja de aplicar categoría individual.
			ControladorCategoriasClientes::ctrCerrarAsignacionEntidad("cliente", $codigoCliente);

			$clienteAsignado = ModeloGruposEmpresariales::mdlMostrarClientePorCodigo($codigoCliente);
			$total = ModeloGruposEmpresariales::mdlContarClientesPorGrupo($codigoGrupo, true);
			$categoriaGrupo = ControladorCategoriasClientes::ctrCategoriaVigenteGrupo($codigoGrupo);
			$nombreCat = ($categoriaGrupo && !empty($categoriaGrupo["tiene_categoria"]))
				? $categoriaGrupo["etiqueta"]
				: "Sin categoría / pendiente";

			echo json_encode(array(
				"status" => "ok",
				"mensaje" => "Cliente asignado. Hereda la categoría del grupo: " . $nombreCat,
				"cliente" => array(
					"codigo" => $clienteAsignado["codigo"],
					"nombre" => $clienteAsignado["nombre"],
					"documento" => isset($clienteAsignado["documento"]) ? $clienteAsignado["documento"] : "",
					"telefono" => isset($clienteAsignado["telefono"]) ? $clienteAsignado["telefono"] : ""
				),
				"total_miembros" => $total,
				"categoria_grupo" => $nombreCat
			));
			return;
		}

		if ($respuesta === "no_disponible") {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente ya no está disponible para asignar."
			));
			return;
		}

		echo json_encode(array(
			"status" => "error",
			"mensaje" => "No se pudo asignar el cliente."
		));
	}

	static public function ctrQuitarCliente()
	{

		if (!isset($_POST["codigoClienteQuitar"])) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "Faltan datos para quitar el cliente."
			));
			return;
		}

		$codigoCliente = trim($_POST["codigoClienteQuitar"]);
		$codigoGrupo = isset($_POST["codigoGrupoQuitar"]) ? trim($_POST["codigoGrupoQuitar"]) : "";

		if ($codigoCliente === "") {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "Cliente no válido."
			));
			return;
		}

		$cliente = ModeloGruposEmpresariales::mdlMostrarClientePorCodigo($codigoCliente);

		if (!$cliente) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente no existe."
			));
			return;
		}

		$grupoCliente = isset($cliente["grupo"]) ? trim($cliente["grupo"]) : "";

		if ($codigoGrupo !== "" && $grupoCliente !== $codigoGrupo) {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente no pertenece a este grupo."
			));
			return;
		}

		$grupoReferencia = $codigoGrupo !== "" ? $codigoGrupo : $grupoCliente;
		$respuesta = ModeloGruposEmpresariales::mdlQuitarClienteDeGrupo(
			$codigoCliente,
			$codigoGrupo !== "" ? $codigoGrupo : null
		);

		if ($respuesta === "ok") {
			// Al salir del grupo queda sin categoría hasta asignación individual.
			ControladorCategoriasClientes::ctrCerrarAsignacionEntidad("cliente", $codigoCliente);

			$total = $grupoReferencia !== ""
				? ModeloGruposEmpresariales::mdlContarClientesPorGrupo($grupoReferencia, true)
				: 0;

			echo json_encode(array(
				"status" => "ok",
				"mensaje" => "Cliente quitado del grupo. Queda sin categoría comercial hasta asignarle una individual.",
				"cliente" => array(
					"codigo" => $cliente["codigo"],
					"nombre" => $cliente["nombre"],
					"documento" => isset($cliente["documento"]) ? $cliente["documento"] : ""
				),
				"total_miembros" => $total
			));
			return;
		}

		if ($respuesta === "no_encontrado") {
			echo json_encode(array(
				"status" => "error",
				"mensaje" => "El cliente ya no pertenecía al grupo."
			));
			return;
		}

		echo json_encode(array(
			"status" => "error",
			"mensaje" => "No se pudo quitar el cliente del grupo."
		));
	}
}
