<?php

require_once "../../controladores/servicio.controlador.php";
require_once "../../modelos/servicio.modelo.php";

class TablaPagoServicio{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/ 

    public function mostrarTablaPagoServicio(){

        $valor = null;

        $quincena = ControladorServicios::ctrMostrarPagoServicios($valor);	

        if(count($quincena)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($quincena); $i++){

            /* 
            * BOTONES   
            
            
            */

            if($quincena[$i]["estado_pago"] == "POR PAGAR"){

                /* $estado = "<button class='btn btn-danger btn-xs btnActivar'>".$articulos[$i]["id"]."</button>"; */
                $estado_pago = "<button class='btn btn-warning btn-xs btnPagarCierreServicio'  inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' idPago='".$quincena[$i]["id"]."' estadoPago='PAGADO'>POR PAGAR</button>";
    
            }else{
    
                /* $estado = "<button class='btn btn-success btn-xs btnActivarArt'>".$articulos[$i]["id"]."</button>"; */
                $estado_pago = "<button class='btn btn-primary btn-xs btnPagarCierreServicio'  inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' idPago='".$quincena[$i]["id"]."' estadoPago='POR PAGAR'>PAGADO</button>";
    
            }
         
            $botones =  "<div class='btn-group'><button class='btn btn-xs btn-info btnVerPagoSer' title='Ver pagos'  inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' data-toggle='modal' data-target='#modalVerPagoServicio'><i class='fa fa-eye'></i></button><button class='btn btn-xs btn-warning btnEditarPagoServicio' title='Editar Fechas' id='".$quincena[$i]["id"]."' data-toggle='modal' data-target='#modalEditarPagoServicio'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarPagoServicio' title='Eliminar' id='".$quincena[$i]["id"]."'><i class='fa fa-times'></i></button><button class='btn btn-xs btn-outline-success btnReportePagoServicios2' inicio='".$quincena[$i]["inicio"]."' fin='".$quincena[$i]["fin"]."' id= '".$quincena[$i]["id"]."'style='border:green 1px solid' ><img src='vistas/img/plantilla/excel.png' width='20px'></button><button class='btn btn-xs btnVerEtiqueta' id='".$quincena[$i]["id"]."' title='Ver etiquetas' style='background:gray' data-toggle='modal' data-target='#modalVerEtiqueta'><i class='fa fa-money'></i></button></div>";
            
            $datosJson .= '[
            "'.($i+1).'",
            "'.$quincena[$i]["ano"].'",
            "'.$quincena[$i]["mes"].'",
            "'.$quincena[$i]["inicio"].'",
            "'.$quincena[$i]["fin"].'",
            "'.$quincena[$i]["nombre"].'",
            "'.$quincena[$i]["fecha_creacion"].'",
            "'.$estado_pago.'",
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
ACTIVAR TABLA DE ARTICULOS
=============================================*/ 
$activarPagoServicio = new TablaPagoServicio();
$activarPagoServicio -> mostrarTablaPagoServicio();