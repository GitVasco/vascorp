<?php

require_once "../../controladores/ingresos.controlador.php";
require_once "../../modelos/ingresos.modelo.php";

class TablaEditarOrdenCorte
{

    /* 
    * MOSTRAR TABLA DE ORDENES DE CORTE
    */
    public function mostrarTablaEditarOrdenCorte()
    {

        $valor = $_GET["codigo"];
        $produccion = ModeloIngresos::editarDetalleIngreso($valor);

        if (count($produccion) > 0) {

            $datosJson = '{
            "data": [';

            for ($i = 0; $i < count($produccion); $i++) {


                /* 
            todo: Traemos las acciones
            */

                $sector = ($produccion[$i]["idcierre"] == 0) ? "interno" : "externo";

                $almacen = ($produccion[$i]["almacen"] == "01") ? "stock01" : "stock05";

                $botones =  "<div class='btn-group'><button type='button' class='btn btn-xs btn-warning btnEditarDetalleIngreso' data-toggle='modal' data-target='#modalEditarDetalleIngreso' codigo= '" . $_GET["codigo"] . "' articulo='" . $produccion[$i]["articulo"] . "' modelo='" . $produccion[$i]["modelo"] . "' nombre='" . $produccion[$i]["nombre"] . "' color='" . $produccion[$i]["color"] . "' talla='" . $produccion[$i]["talla"] . "' cantidad='" . number_format($produccion[$i]["cantidad"], 0) . "' saldo='" . $produccion[$i]["saldo"] . "' sector='" . $sector . "' idcierre='" . $produccion[$i]["idcierre"] . "' almacen='" . $almacen . "'><i class='fa fa-pencil'></i></button><button  type='button' class='btn btn-xs btn-danger btnEliminarDetalleIngreso' codigo= '" . $_GET["codigo"] . "' idDetalle='" . $produccion[$i]["articulo"] . "' cantidad='" . $produccion[$i]["cantidad"] . "'><i class='fa fa-times'></i></button></div> ";

                $datosJson .= '[
                "' . ($i + 1) . '",
                "' . $produccion[$i]["articulo"] . '",
                "' . $produccion[$i]["modelo"] . '",
                "' . $produccion[$i]["nombre"] . '",
                "' . $produccion[$i]["color"] . '",
                "' . $produccion[$i]["talla"] . '",
                "' . $produccion[$i]["marca"] . '",
                "' . number_format($produccion[$i]["cantidad"], 0) . '",
                "' . $produccion[$i]["saldo"] . '",
                "' . $botones . '"
                ],';
            }

            $datosJson = substr($datosJson, 0, -1);

            $datosJson .= '] 
    
                }';

            echo $datosJson;
        } else {

            echo '{
                    "data":[]
                }';
            return;
        }
    }
}

/*=============================================
ACTIVAR TABLA DE EDITAR DETALLE ORDEN CORTE
=============================================*/
$activarEditarOrdenCorte = new TablaEditarOrdenCorte();
$activarEditarOrdenCorte->mostrarTablaEditarOrdenCorte();
