<?php

require_once "../../controladores/vendedor.controlador.php";
require_once "../../modelos/vendedor.modelo.php";

class TablaVendedores{

    /*=============================================
    MOSTRAR LA TABLA DE VENDEDORES
    =============================================*/ 

    public function mostrarTablaVendedores(){

        $item = null;     
        $valor = null;

        $vendedor = ControladorVendedores::ctrMostrarVendedores($item, $valor);	
        if(count($vendedor)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($vendedor); $i++){  

        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/         
        
        $botones =  "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarVendedor' idVendedor='".$vendedor[$i]["id"]."' data-toggle='modal' data-target='#modalEditarVendedor'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarVendedor' idVendedor='".$vendedor[$i]["id"]."'><i class='fa fa-times'></i></button></div>";

            $estadoDecisiones = isset($vendedor[$i]["estado_decisiones"]) && (int) $vendedor[$i]["estado_decisiones"] === 1
                ? "<span class='label label-success'>Incluido</span>"
                : "<span class='label label-default'>No</span>";

            $datosJson .= '[
            "'.$vendedor[$i]["codigo"].'",
            "'.$vendedor[$i]["descripcion"].'",
            "'.$estadoDecisiones.'",
            "'.$botones.'"
            ],';        
            }

            $datosJson=substr($datosJson, 0, -1);

            $datosJson .= '] 

            }';

        echo $datosJson;
        }else{

            echo '{
                "data":[]
            }';
            return;

        }
    }

    

}

/*=============================================
ACTIVAR TABLA DE VENDEDORES
=============================================*/ 
$activarVendedores = new TablaVendedores();
$activarVendedores -> mostrarTablaVendedores();

