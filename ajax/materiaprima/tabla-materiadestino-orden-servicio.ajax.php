<?php

require_once "../../controladores/orden-servicio.controlador.php";
require_once "../../modelos/orden-servicio.modelo.php";

class TablaMateriaPrima{

    /*=============================================
    MOSTRAR LA TABLA DE MATERIA PRIMA
    =============================================*/ 

    public function mostrarTablaMateriaPrima(){


        $materiaprima = ControladorOrdenServicio::ctrMostrarMateriasDestino();	
        if(count($materiaprima)>0){

        $datosJson = '{
            "data": [';
    
            for($i = 0; $i < count($materiaprima); $i++){

            $descripcion = str_replace('"','',$materiaprima[$i]["DesPro"]);
         
       
            /*=============================================
            TRAEMOS LAS ACCIONES
            =============================================*/         

                
        $botones =  "<div class='btn-group'><button class='btn btn-primary btn-xs agregarMateriaDestinoServicio ' codigo='".$materiaprima[$i]["CodPro"]."' descripcion = '".$descripcion."' color='".$materiaprima[$i]["Color"]."'><i class='fa fa-plus-square'></i> Agregar</button></div>"; 
    
                $datosJson .= '[
                "'.$materiaprima[$i]["CodPro"].'",
                "'.$materiaprima[$i]["CodFab"].'",
                "'.$descripcion.'",
                "'.$materiaprima[$i]["Color"].'",
                "'.$materiaprima[$i]["Unidad"].'",
                "'.$materiaprima[$i]["precio"].'",
                "'.$materiaprima[$i]["CodAlm01"].'",
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
ACTIVAR TABLA DE MATERIA PRIMA
=============================================*/ 
$activarMateriaPrima = new TablaMateriaPrima();
$activarMateriaPrima -> mostrarTablaMateriaPrima();