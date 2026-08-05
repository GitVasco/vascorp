<?php

require_once "../../controladores/sectores.controlador.php";
require_once "../../modelos/sectores.modelo.php";

class TablaSectores{

    /*=============================================
    MOSTRAR LA TABLA DE SECTORES
    =============================================*/ 

    public function mostrarTablaSectores(){

        $item = null;     
        $valor = null;

        $sector = ControladorSectores::ctrMostrarSectores($item, $valor);	
        if(count($sector)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($sector); $i++){  

            // Misma etiqueta vía helper (Fase 0); comportamiento igual a tipo == 0
            if (ControladorSectores::ctrEsInterno($sector[$i]["cod_sector"])) {
                $tipo = "TALLER";
            } else {
                $tipo = "SERVICIO";
            }
    
        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/         
        
        $tipoValor = ($sector[$i]["tipo"] == 0 || $sector[$i]["tipo"] === "0") ? "0" : "1";
        $botones =  "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarSector' idSector='".$sector[$i]["cod_sector"]."' tipoSector='".$tipoValor."' data-toggle='modal' data-target='#modalEditarSector'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarSector' idSector='".$sector[$i]["cod_sector"]."'><i class='fa fa-times'></i></button></div>"; 

            $datosJson .= '[
            "'.$sector[$i]["cod_sector"].'",
            "'.$sector[$i]["nom_sector"].'",
            "'.$tipo.'",
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
ACTIVAR TABLA DE SECTORES
=============================================*/ 
$activarSectores = new TablaSectores();
$activarSectores -> mostrarTablaSectores();

