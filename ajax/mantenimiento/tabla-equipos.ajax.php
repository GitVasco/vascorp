<?php

require_once "../../controladores/mantenimiento.controlador.php";
require_once "../../modelos/mantenimiento.modelo.php";

class TablaEquipos{

    /*=============================================
    MOSTRAR LA TABLA DE AGENCIAS
    =============================================*/ 

    public function mostrarTablaEquipos(){

        $valor = null;

        $equipos = ControladorMantenimiento::ctrMostrarEquipos($valor);	
        if(count($equipos)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($equipos); $i++){  

            //*ESTADO
            if($equipos[$i]["estado"] == "Operativo"){

                $estado = "<button class='btn btn-success btn-xs btnBajaEquipo' idEquipo='".$equipos[$i]["id"]."' estadoEquipo='Inoperativo'>Operativo</button>";

            }else if($equipos[$i]["estado"] == "Inoperativo"){

                $estado = "<button class='btn btn-danger btn-xs btnAltaEquipo' idEquipo='".$equipos[$i]["id"]."' estadoEquipo='Operativo'>Inoperativo</button>";

            }else{

                $estado = "<button class='btn btn-warning btn-xs btnAltaEquipo' idEquipo='".$equipos[$i]["id"]."' estadoEquipo='Operativo'>Sin Uso</button>";

            }

            //*TRAEMOS LAS ACCIONES  
            $botones =  "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarEquipo' idEquipo='".$equipos[$i]["id"]."' data-toggle='modal' data-target='#modalEditarEquipos'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarAgencia' idEquipo='".$equipos[$i]["id"]."'><i class='fa fa-times'></i></button></div>"; 

            $datosJson .= '[
            "'.$equipos[$i]["cod_tipo"].'",
            "'.$equipos[$i]["nombre_tipo_maquina"].'",
            "'.$equipos[$i]["descripcion"].'",
            "'.$equipos[$i]["ubicacion_maquina"].'",
            "'.$equipos[$i]["marca_equipo"].'",
            "'.$equipos[$i]["modelo_equipo"].'",
            "'.$equipos[$i]["serie_equipo"].'",
            "'.$estado.'",
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
$activarAgencias = new TablaEquipos();
$activarAgencias -> mostrarTablaEquipos();
