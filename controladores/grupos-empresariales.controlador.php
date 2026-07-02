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

		$datos = array(
			"id" => $id,
			"nombre" => $nombre,
			"descripcion" => trim(isset($_POST["editarDescripcionGrupo"]) ? $_POST["editarDescripcionGrupo"] : ""),
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
			return;
		}

		$respuesta = ModeloGruposEmpresariales::mdlAsignarClienteAGrupo(
			trim($_POST["codigoClienteGrupo"]),
			trim($_POST["codigoGrupoAsignar"])
		);

		echo json_encode(array("status" => $respuesta));
	}

	static public function ctrQuitarCliente()
	{

		if (!isset($_POST["codigoClienteQuitar"])) {
			return;
		}

		$respuesta = ModeloGruposEmpresariales::mdlQuitarClienteDeGrupo(trim($_POST["codigoClienteQuitar"]));

		echo json_encode(array("status" => $respuesta));
	}
}
