<?php

require_once "../controladores/clientes.controlador.php";
require_once "../modelos/clientes.modelo.php";
require_once "../helpers/jsonpe.api.php";

class AjaxClientes
{

	/*=============================================
	EDITAR CLIENTE
	=============================================*/

	public $codigo;

	public function ajaxEditarCliente()
	{

		$item = "codigo";
		$valor = $this->codigo;

		$respuesta = ControladorClientes::ctrMostrarClientes($item, $valor);

		echo json_encode($respuesta);
	}
	/*=============================================
	VALIDAR DOCUMENTO CLIENTE
	=============================================*/
	public $documento;
	public function ajaxValidarDocumento()
	{
		$item = "documento";
		$valor = $this->documento;
		$respuesta = ControladorClientes::ctrMostrarClientes($item, $valor);
		echo json_encode($respuesta);
	}

	public $clienteCuenta;

	public function ajaxMostrarClienteCuenta()
	{

		$respuesta = ControladorClientes::ctrMostrarClientesCuentas(null, null);

		echo json_encode($respuesta);
	}

	/*=============================================
	CONSULTAR DNI CLIENTE
	=============================================*/
	public $nuevoDni;
	public function ajaxConsultarDNI()
	{
		$valor = $this->nuevoDni;
		$respuesta = JsonPeApi::consultarDni($valor);
		echo json_encode($respuesta);
	}
}

/*=============================================
EDITAR CLIENTE
=============================================*/

if (isset($_POST["codigo"])) {

	$cliente = new AjaxClientes();
	$cliente->codigo = $_POST["codigo"];
	$cliente->ajaxEditarCliente();
}

/*=============================================
VALIDAR DOCUMENTO EXISTENTE
=============================================*/
if (isset($_POST["documento"])) {
	$validarDocumento = new AjaxClientes();
	$validarDocumento->documento = $_POST["documento"];
	$validarDocumento->ajaxValidarDocumento();
}

/*=============================================
CARGAR CLIENTES CON BOTON 
=============================================*/
if (isset($_POST["clienteCuenta"])) {
	$clienteCuenta = new AjaxClientes();
	$clienteCuenta->clienteCuenta = $_POST["clienteCuenta"];
	$clienteCuenta->ajaxMostrarClienteCuenta();
}

/*=============================================
CONSULTAR DNI CLIENTE
=============================================*/

if (isset($_POST["nuevoDni"])) {

	$consultarDni = new AjaxClientes();
	$consultarDni->nuevoDni = $_POST["nuevoDni"];
	$consultarDni->ajaxConsultarDNI();
}
