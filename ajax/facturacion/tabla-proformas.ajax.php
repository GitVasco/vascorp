<?php

require_once "../../controladores/facturacion.controlador.php";
require_once "../../modelos/facturacion.modelo.php";

class TablaProformas{

    /*=============================================
    MOSTRAR LA TABLA DE PROFORMAS
    =============================================*/

    public function mostrarTablaProformas(){


        $proformas = ControladorFacturacion::ctrRangoFechasProformas($_GET["fechaInicial"],$_GET["fechaFinal"]);

        if(count($proformas)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($proformas); $i++){

            /*estado
            */
            if($proformas[$i]["facturacion"] == "0"){

                $estado = "<span style='font-size:85%' class='label label-success'>GENERADO</span>";
                
            }else if($proformas[$i]["facturacion"] == "1"){

                $estado = "<span style='font-size:85%' class='label label-warning'>ERROR</span>";

            }else if($proformas[$i]["facturacion"] == "2"){

                $estado = "<span style='font-size:85%' class='label label-primary'>ENVIADO</span>";

            }else if($proformas[$i]["facturacion"] == "4"){

                $estado = "<span class='btn btn-danger btn-xs btn btnEliminarDocumento' documento='".$proformas[$i]["documento"]."' tipo='".$proformas[$i]["tipo"]."' pagina='facturas'>ANULADO</span>";

            }

            $total = "<div style='text-align:right !important'>".number_format($proformas[$i]["total"],2)."</div>";

            //* CARGO
            $rutaCar  = "../../".$proformas[$i]["cargo"];
            

            if(file_exists($rutaCar) && $proformas[$i]["cargo"] != "../imagenes_vasco/default/anonymous.png"){

                $cargo = "<a class='btn btn-xs btn-info' href='".$rutaCar."' download title='Descargar CARGO' >C</a>";

            }else{

                $cargo = "";

            }

            //*RECEPCION
            $rutaRep = "../../".$proformas[$i]["recepcion"];

            if(file_exists($rutaRep) && $proformas[$i]["recepcion"] != "../imagenes_vasco/default/anonymous.png"){

                $recepcion = "<a class='btn btn-xs btn-info' href='".$rutaRep."' download title='Descargar RECEPCION' >R</a>";

            }else{

                $recepcion = "";

            }            

            if($proformas[$i]["facturacion"] == "0"){

                $botones =  "<div class='btn-group'><button title='Imprimir Proforma' class='btn btn-xs btn-success btnImprimirProforma' tipo='".$proformas[$i]["tipo"]."' documento='".$proformas[$i]["documento"]."'><i class='fa fa-print'></i></button><button title='Anular Documento' class='btn btn-xs  btn-danger btnAnularDocumento' documento='".$proformas[$i]["documento"]."' tipo='".$proformas[$i]["tipo"]."' pagina='proformas'><i class='fa fa-close'></i></button><button title='Cargar Fotos' class='btn btn-xs btn-info btnCargarFotosFact' tipo='".$proformas[$i]["tipo"]."' documento='".$proformas[$i]["documento"]."' data-toggle='modal' data-target='#modalCargarFotos'><i class='fa fa-camera'></i></button></div>";

            }else{

                $botones =  "<div class='btn-group'><button title='Imprimir Factura' class='btn btn-xs btn-success btnImprimirBoleta' tipo='".$proformas[$i]["tipo"]."' documento='".$proformas[$i]["documento"]."'><i class='fa fa-print'></i></button><button class='btn btn-xs btn-primary btnImprimirTicketFacBol' tipo='".$proformas[$i]["tipo"]."' documento='".$proformas[$i]["documento"]."'><i class='fa fa-file-word-o'></i></button><button title='Cargar Fotos' class='btn btn-xs btn-info btnCargarFotosFact' tipo='".$proformas[$i]["tipo"]."' documento='".$proformas[$i]["documento"]."' data-toggle='modal' data-target='#modalCargarFotos'><i class='fa fa-camera'></i></button></div>";

            }            



            $datosJson .= '[
            "'.$proformas[$i]["tipo_documento"].'",
            "<b>'.$proformas[$i]["documento"].'</b>",
            "'.$total.'",
            "'.$proformas[$i]["cliente"].'",
            "<b>'.$proformas[$i]["nombre"].'</b>",
            "'.$proformas[$i]["vendedor"].'",
            "'.$proformas[$i]["fecha"].'",
            "'.$proformas[$i]["doc_destino"].'",
            "'.$proformas[$i]["estado"].'",
            "'.$proformas[$i]["ubigeo"].'",
            "'.$cargo." ".$recepcion.'",
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
ACTIVAR TABLA DE PROFORMAS
=============================================*/
$activarTablaProformas = new TablaProformas();
$activarTablaProformas -> mostrarTablaProformas();