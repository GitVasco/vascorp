<?php

require_once "../../controladores/maestras.controlador.php";
require_once "../../modelos/maestras.modelo.php";

class TablaMaestraCabecera{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/ 

    public function mostrarTablaMaestraCabecera(){

        $tipPro = null;
        $docPro = null;

        $maestras = ControladorMaestras::ctrMostrarProdCabecera($tipPro, $docPro);	
        if(count($maestras)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($maestras); $i++){  

            /* 
            * boton en el codigo de tabla
            */

            $documento = "<button class='btn btn-link btn-xs ActivarDetalle' documento='".$maestras[$i]["documento"]."' tipo='".$maestras[$i]["tipo"]."'>".$maestras[$i]["documento"]."</button>";


        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/         
        
        $botones =  "<div class='btn-group'><button class='btn btn-primary btn-xs btnAgregarProd' tipo='".$maestras[$i]["tipo"]."' documento='".$maestras[$i]["documento"]."' data-toggle='modal' data-target='#modalAgregarProd'><i class='fa fa-plus'></i></button></div>"; 

            $datosJson .= '[
            "'.$maestras[$i]["tipo"].'",
            "'.$documento.'",
            "'.$maestras[$i]["total"].'",
            "'.$maestras[$i]["fecha"].'",
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
$activarTabla = new TablaMaestraCabecera();
$activarTabla -> mostrarTablaMaestraCabecera();

