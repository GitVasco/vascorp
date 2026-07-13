<?php

require_once "../controladores/grupos-empresariales.controlador.php";
require_once "../modelos/grupos-empresariales.modelo.php";
require_once "../controladores/categorias-clientes.controlador.php";
require_once "../modelos/categorias-clientes.modelo.php";

class AjaxGruposEmpresariales
{

	public $idGrupo;
	public $codigoGrupo;

	public function ajaxEditarGrupo()
	{

		$respuesta = ControladorGruposEmpresariales::ctrMostrarGrupos("id", $this->idGrupo);
		echo json_encode($respuesta);
	}

	public function ajaxClientesGrupo()
	{

		$clientes = ModeloGruposEmpresariales::mdlMostrarClientesPorGrupo($this->codigoGrupo);
		$disponibles = ModeloGruposEmpresariales::mdlMostrarClientesSinGrupo();
		$total = ModeloGruposEmpresariales::mdlContarClientesPorGrupo($this->codigoGrupo, true);
		$categoria = ControladorCategoriasClientes::ctrCategoriaVigenteGrupo($this->codigoGrupo);

		echo json_encode(array(
			"clientes" => $clientes,
			"disponibles" => $disponibles,
			"total_miembros" => $total,
			"categoria" => $categoria
		));
	}
}
if (isset($_POST["idGrupo"])) {

	$ajax = new AjaxGruposEmpresariales();
	$ajax->idGrupo = $_POST["idGrupo"];
	$ajax->ajaxEditarGrupo();
}

if (isset($_POST["codigoGrupo"])) {

	$ajax = new AjaxGruposEmpresariales();
	$ajax->codigoGrupo = $_POST["codigoGrupo"];
	$ajax->ajaxClientesGrupo();
}

if (isset($_POST["accion"]) && $_POST["accion"] === "asignar") {
	$ctrl = new ControladorGruposEmpresariales();
	$ctrl->ctrAsignarCliente();
}

if (isset($_POST["accion"]) && $_POST["accion"] === "quitar") {
	$ctrl = new ControladorGruposEmpresariales();
	$ctrl->ctrQuitarCliente();
}
