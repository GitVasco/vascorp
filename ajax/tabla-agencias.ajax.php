<?php

require_once "../controladores/agencia.controlador.php";
require_once "../modelos/agencia.modelo.php";

class TablaAgencias{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/ 

    public function mostrarTablaAgencias(){

        $item = null;     
        $valor = null;

        $agencias = ControladorAgencias::ctrMostrarAgencias($item, $valor);	
        if(count($agencias)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($agencias); $i++){  

        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/         
        
        $botones =  "<div class='btn-group'><button class='btn btn-warning btnEditarAgencia' idAgencia='".$agencias[$i]["id"]."' data-toggle='modal' data-target='#modalEditarAgencia'><i class='fa fa-pencil'></i></button><button class='btn btn-danger btnEliminarAgencia' idAgencia='".$agencias[$i]["id"]."'><i class='fa fa-times'></i></button></div>"; 

            $datosJson .= '[
            "'.($i+1).'",
            "'.$agencias[$i]["nombre"].'",
            "'.$agencias[$i]["ruc"].'",
            "'.$agencias[$i]["direccion"].'",
            "'.$agencias[$i]["ubigeo"].'",
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
ACTIVAR TABLA DE AGENCIAS
=============================================*/ 
$activarAgencias = new TablaAgencias();
$activarAgencias -> mostrarTablaAgencias();
