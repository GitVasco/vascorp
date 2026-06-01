<?php

require_once "../controladores/proveedor.controlador.php";
require_once "../modelos/proveedor.modelo.php";
require_once "../helpers/jsonpe.api.php";

class AjaxProveedores
{

	/*=============================================
	EDITAR PROVEEDOR
	=============================================*/

	public $CodRuc;

	public function ajaxEditarProveedor()
	{

		$item = "CodRuc";
		$valor = $this->CodRuc;

		$respuesta = ControladorProveedores::ctrMostrarProveedores($item, $valor);

		echo json_encode($respuesta);
	}

	/*=============================================
	VALIDAR RUC PROVEEDOR
	=============================================*/
	public $RucPro;
	public function ajaxValidarRuc()
	{

		$item = "RucPro";
		$valor = $this->RucPro;
		$respuesta = ControladorProveedores::ctrMostrarProveedores($item, $valor);

		echo json_encode($respuesta);
	}

	/*=============================================
	CONSULTAR RUC PROVEEDOR O CLIENTE
	=============================================*/
	public $nuevoRuc;
	public function ajaxConsultarRUC()
	{
		$valor = $this->nuevoRuc;
		$respuesta = JsonPeApi::consultarRuc($valor);
		echo json_encode($respuesta);
	}
}

/*=============================================
EDITAR PROVEEDOR
=============================================*/

if (isset($_POST["CodRuc"])) {

	$cliente = new AjaxProveedores();
	$cliente->CodRuc = $_POST["CodRuc"];
	$cliente->ajaxEditarProveedor();
}

/*=============================================
VALIDAR RUC PROVEEDOR
=============================================*/
if (isset($_POST["RucPro"])) {
	$validarDocumento = new AjaxProveedores();
	$validarDocumento->RucPro = $_POST["RucPro"];
	$validarDocumento->ajaxValidarRuc();
}


/*=============================================
CONSULTAR RUC PROVEEDOR O CLIENTE
=============================================*/

if (isset($_POST["nuevoRuc"])) {

	$consultarRuc = new AjaxProveedores();
	$consultarRuc->nuevoRuc = $_POST["nuevoRuc"];
	$consultarRuc->ajaxConsultarRUC();
}
