<?php

session_start();

require_once '../controladores/ordencorte.controlador.php';
require_once '../modelos/ordencorte.modelo.php';

require_once '../controladores/articulos.controlador.php';
require_once '../modelos/articulos.modelo.php';

class AjaxOrdenCorte
{

    public $codigo;

    public function ajaxEliminarOrdenCorte()
    {

        $codigo = $this->codigo;

        $respuesta = ControladorOrdenCorte::ctrEliminarOrdenCorte($codigo);

        echo $respuesta;
    }

    /* 
	* VISUALIZAR LA CABECERA DE LA ORDEN DE CORTE
	*/
    public $codigoOC;

    public function ajaxVisualizarOrdenCorte()
    {

        $item = "codigo";
        $valor = $this->codigoOC;

        $respuesta = ControladorOrdenCorte::ctrVisualizaOrdenCorte($item, $valor);

        echo json_encode($respuesta);
    }

    /* 
	* VISUALIZAR DETALLE DE LA ORDEN DE CORTE
	*/
    public function ajaxVisualizarOrdenCorteDetalle()
    {

        $item = "ordencorte";
        $valor = $this->codigoDOC;

        $respuestaDetalle = ControladorOrdenCorte::ctrVisualizarOrdenCorteDetalle($item, $valor);

        echo json_encode($respuestaDetalle);
    }

    /* 
	* VISUALIZAR DETALLE DE LA ORDEN DE CORTE
    */
    public $idDetalle;
    public function ajaxMostrarOrdenCorteDetalle()
    {

        $item = "id";
        $valor = $this->idDetalle;

        $respuesta = ControladorOrdenCorte::ctrMostrarDetalleOrdenCorte($item, $valor);

        echo json_encode($respuesta);
    }

    public function ajaxCargarTarjetas()
    {

        $lista = $this->lista;

        $listaArticulos = json_decode($lista, true);

        foreach ($listaArticulos as $key => $value) {
            $articulo = $value["articulo"];
            $cantidad = $value["cantidad"];
            $respuesta = ModeloOrdenCorte::mdlCargarTarjetas($articulo, $cantidad);



            echo json_encode($respuesta);
        }
    }

    public function ajaxCargarArticulo()
    {

        $prueba = ModeloOrdenCorte::mdlEliminarArticulo();

        $articulo = $this->articulo;

        foreach ($articulo as $key => $value) {
            if ($value["cantidad"] < 0) {

                $cantidad = 0;
            } else {
                $cantidad = $value["cantidad"];
            }
            $datos = array(
                "articulo" => $value["articulo"],
                "cantidad" => $cantidad
            );

            ModeloOrdenCorte::mdlCargarArticulo($datos);
        }

        echo '<pre>';
        print_r($prueba);
        echo '</pre>';
    }
}

/* 
* ELIMINAR ORDEN DE CORTE
*/
if (isset($_POST["codigo"])) {

    $eliminarOrdenCorte = new AjaxOrdenCorte();
    $eliminarOrdenCorte->codigo = $_POST["codigo"];
    $eliminarOrdenCorte->ajaxEliminarOrdenCorte();
}

/* 
 * VISUALIZAR LA CABECERA DE LA ORDEN DE CORTE
*/
if (isset($_POST["codigoOC"])) {

    $visualizarOrdenCorte = new AjaxOrdenCorte();
    $visualizarOrdenCorte->codigoOC = $_POST["codigoOC"];
    $visualizarOrdenCorte->ajaxVisualizarOrdenCorte();
}

/* 
 * VISUALIZAR DETALLE DE LA ORDEN DE CORTE
*/
if (isset($_POST["codigoDOC"])) {

    $visualizarOrdenCorteDetalle = new AjaxOrdenCorte();
    $visualizarOrdenCorteDetalle->codigoDOC = $_POST["codigoDOC"];
    $visualizarOrdenCorteDetalle->ajaxVisualizarOrdenCorteDetalle();
}

/* 
 * VISUALIZAR DETALLE DE LA ORDEN DE CORTE
*/
if (isset($_POST["idDetalle"])) {

    $mostrarOrdenCorteDetalle = new AjaxOrdenCorte();
    $mostrarOrdenCorteDetalle->idDetalle = $_POST["idDetalle"];
    $mostrarOrdenCorteDetalle->ajaxMostrarOrdenCorteDetalle();
}

if (isset($_POST["lista"])) {

    $mostrarOrdenCorteDetalle = new AjaxOrdenCorte();
    $mostrarOrdenCorteDetalle->lista = $_POST["lista"];
    $mostrarOrdenCorteDetalle->ajaxCargarTarjetas();
}


if (isset($_POST["articulos"])) {

    $mostrarOrdenCorteDetalle = new AjaxOrdenCorte();
    $mostrarOrdenCorteDetalle->articulo = json_decode($_POST["articulos"], true);
    $mostrarOrdenCorteDetalle->ajaxCargarArticulo();
}

/*
 * CREAR ORDEN DE CORTE DESDE CSV (lista articulo,cantidad en JSON)
 */
if (isset($_POST["crearOrdenCorteCSV"]) && isset($_POST["listaArticulosOC"])) {
    $respuesta = ControladorOrdenCorte::ctrCrearOrdenCorteDesdeCSV();
    echo json_encode(array(
        "status" => ($respuesta === "ok" ? "ok" : "error"),
        "mensaje" => $respuesta
    ));
}
