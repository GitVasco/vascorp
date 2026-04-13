<?php

require_once "../../controladores/produccion.controlador.php";
require_once "../../modelos/produccion.modelo.php";

class TablaQuincena{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/ 

    public function mostrarTablaQuincena(){

        $valor = null;

        $quincena = ControladorProduccion::ctrMostrarQuincenas($valor);	

        if(count($quincena)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($quincena); $i++){

            /* 
            * BOTONES            
            */
            $botones =  "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarQuincena' title='Editar Fechas' id='".$quincena[$i]["id"]."' data-toggle='modal' data-target='#modalEditarQuincena'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-success btnEficiencia' title='Eficiencia' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' nquincena='".$quincena[$i]["nquincena"]."' id='".$quincena[$i]["id"]."'><i class='fa fa-percent'></i></button><button class='btn btn-xs btn-primary btnPagos' title='Pagos' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' nquincena='".$quincena[$i]["nquincena"]."' id='".$quincena[$i]["id"]."'><i class='fa fa-money'></i></button><button class='btn btn-xs btn-danger btnEliminarQuincena' title='Eliminar' id='".$quincena[$i]["id"]."'><i class='fa fa-times'></i></button></div>";
            
            /* 
            *trusas
            */

            $trusas = "<div class='btn-group'><button class='btn btn-xs btn-outline-success btnReportePagosTrusas' title='Pagos trusas (esquema sueldo / clásico)' id='".$quincena[$i]["id"]."' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' style='border:green 1px solid'><img src='vistas/img/plantilla/excel.png' width='17px'></button><button class='btn btn-xs btn-outline-primary btnReportePagosTrusasProduccion' title='Pagos trusas por monto producido S/. (bonos por rango)' id='".$quincena[$i]["id"]."' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' style='border:#337ab7 1px solid'><img src='vistas/img/plantilla/excel.png' width='17px'></button></div>";

            /* 
            *brasier
            */
            $brasier = "<button class='btn btn-xs  btn-outline-success  btnReportePagosBrasier' title='Reporte de Pagos Brasier' id='".$quincena[$i]["id"]."' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' style='border:green 1px solid'><img src='vistas/img/plantilla/excel.png' width='17px'></button>";

            /* 
            *produccion
            */
            $produccion = "<button class='btn btn-xs btn-info btnImprimirAvance' title='Avance de produccion'  inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' ><i class='fa fa-print'></i></button>";

            $actualizar = "<button class='btn btn-success btn-xs btnActualizarPrecioServicio' title='Actualizar precio tiempo'  inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' ><i class='fa fa-refresh'></i> Actualizar</button>";
     
            $datosJson .= '[
            "'.($i+1).'",
            "'.$quincena[$i]["ano"].'",
            "'.$quincena[$i]["mes"].'",
            "'.$quincena[$i]["quincena"].'",
            "'.$quincena[$i]["inicio"].'",
            "'.$quincena[$i]["fin"].'",
            "'.$quincena[$i]["nombre"].'",
            "'.$quincena[$i]["fecha_creacion"].'",
            "'.$botones.'",
            "<center>'.$actualizar.'</center>",
            "<center>'.$trusas.'</center>",
            "<center>'.$brasier.'</center>",
            "<center>'.$produccion.'</center>"
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
ACTIVAR TABLA DE ARTICULOS
=============================================*/ 
$activarArticulos = new TablaQuincena();
$activarArticulos -> mostrarTablaQuincena();