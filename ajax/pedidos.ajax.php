<?php

require_once '../controladores/articulos.controlador.php';
require_once '../modelos/articulos.modelo.php';
require_once '../controladores/clientes.controlador.php';
require_once '../modelos/clientes.modelo.php';

require_once '../controladores/pedidos.controlador.php';
require_once '../modelos/pedidos.modelo.php';

class AjaxPedidos{

    /* 
	* VISUALIZAR COLORES
	*/
	public function ajaxVerColores(){

        $valor = $this->modelo;

        $respuesta = controladorArticulos::ctrVerColores($valor);
       
        echo json_encode($respuesta);
    }

    /* 
	* VISUALIZAR COLORES
	*/
	public function ajaxVerColoresCantidades(){

        $pedido = $this->pedido;
        $modelo = $this->modeloA;

        $respuesta = controladorArticulos::ctrVerColoresCantidades($pedido, $modelo);
       
        echo json_encode($respuesta);
    }    

    /* 
	* VISUALIZAR COLORES
	*/
	public function ajaxVerDatos(){

        $modelo = $this->mod;
        $lista = $this->modLista;

        $respuestaLista = controladorArticulos::ctrVerPrecios($modelo, $lista);
       
        echo json_encode($respuestaLista);
    }    

    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
	public function ajaxVeLista(){

        $valor = $this->cliList;

        $respuestaDet = ControladorClientes::ctrVerLista($valor);
       
        echo json_encode($respuestaDet);
    }   
    
    /* 
	* SACAR LA LISTA DE PRECIOS ASIGNADA
	*/
	public function ajaxBorrarModelo(){

        $modelo = $this->modelo;
        $pedido = $this->pedido;

        $respuesta = ModeloPedidos::mdlBorrarModelo($modelo, $pedido);

        echo json_encode($respuesta);

    }     


}

/* 
 * VISUALIZAR COLORES
*/
if(isset($_POST["modelo"])){

    $visualizarMateriaPrimaDetalle = new AjaxPedidos();
    $visualizarMateriaPrimaDetalle -> modelo = $_POST["modelo"];
    $visualizarMateriaPrimaDetalle -> ajaxVerColores();

}

/* 
 * VISUALIZAR COLORES Y MODIFICAR
*/
if(isset($_POST["pedido"])){

    $verColoresyCantidades = new AjaxPedidos();
    $verColoresyCantidades -> pedido = $_POST["pedido"];
    $verColoresyCantidades -> modeloA = $_POST["modeloA"];
    $verColoresyCantidades -> ajaxVerColoresCantidades();

}

/* 
 * VISUALIZAR precios y otros
*/
if(isset($_POST["mod"])){

    $visualizarPrecios = new AjaxPedidos();
    $visualizarPrecios -> mod = $_POST["mod"];
    $visualizarPrecios -> modLista = $_POST["modLista"];
    $visualizarPrecios -> ajaxVerDatos();

}

/* 
 * SACAR LA LISTA DE PRECIOS ASIGNADA
*/
if(isset($_POST["cliList"])){

    $visualizarListaPrecios = new AjaxPedidos();
    $visualizarListaPrecios -> cliList = $_POST["cliList"];
    $visualizarListaPrecios -> ajaxVeLista();

}

/* 
 * PARA BORRAR POR MODELO
*/
if(isset($_POST["modeloB"])){

    $borrarModelo = new AjaxPedidos();
    $borrarModelo -> modelo = $_POST["modeloB"];
    $borrarModelo -> pedido = $_POST["pedidoB"];
    $borrarModelo -> ajaxBorrarModelo();

}