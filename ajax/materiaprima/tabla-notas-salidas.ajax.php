<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../controladores/notas-salidas.controlador.php";
require_once "../../controladores/gre-manual.controlador.php";
require_once "../../modelos/gre-manual.modelo.php";
require_once "../../modelos/notas-salidas.modelo.php";

class TablaNotasSalidas{

    /*=============================================
    MOSTRAR LA TABLA DE NOTAS SALIDAS
    =============================================*/ 

    public function mostrarTablaNotasSalidas(){


        $notasSalidas = ControladorNotasSalidas::ctrRangoFechasNotasSalidas($_GET["fechaInicial"], $_GET["fechaFinal"]);	
        if(count($notasSalidas)>0){

        $datosJson = '{
        "data": [';

        $puedeGuia = ControladorGreManual::puedeUsar();
        $notasGuiadas = $puedeGuia ? ModeloGreManual::mdlNotasGuiadas() : array();

        for($i = 0; $i < count($notasSalidas); $i++){

            if($notasSalidas[$i]["EstNota"] == 0){

                $estado = "<button class='btn btn-danger btn-xs btnActivarNotaSalida' idNotaSalida='".$notasSalidas[$i]["nro"]."' estadoNotaSalida='1'>PENDIENTE</button>";

            }else {
                $estado = "<button class='btn btn-success btn-xs'>APROBADO</button>";
            }
            
        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/    
        $botones =  "<div class='btn-group' ><button class='btn btn-xs btn-primary btnVisualizarNotaSalida' title='Visualizar Nota de Salida' idNotaSalida='".$notasSalidas[$i]["nro"]."' data-toggle='modal' data-target='#modalVizualizarNotaSalida'><i class='fa fa-eye'></i></button><button class='btn btn-xs btn-danger btnAnularNotaSalida' title='Anular Nota de Salida' idNotaSalida='".$notasSalidas[$i]["nro"]."'><i class='fa fa-times'></i></button><button class='btn btn-xs btn-outline-success pull-right btnDetalleReporteNotaSalida' idNotaSalida='".$notasSalidas[$i]["nro"]."' title='Reporte Nota de Salida' style='border:green 1px solid'><img src='vistas/img/plantilla/excel.png' width='15px'></button></div>"; 

            // Guía de remisión: documento si ya existe, botón para emitirla si no
            $claveNota = $notasSalidas[$i]["tip"] . "-" . $notasSalidas[$i]["ser"] . "-" . $notasSalidas[$i]["nro"];
            if (isset($notasGuiadas[$claveNota])) {
                $guiaCol = "<a href='index.php?ruta=gre-manual' title='Ver en guías manuales'><span class='label label-success'>" . $notasGuiadas[$claveNota]["documento"] . "</span></a>";
            } elseif ($puedeGuia) {
                $guiaCol = "<a class='btn btn-xs btn-success' title='Emitir guía de remisión' target='_blank' rel='noopener' href='index.php?ruta=gre-manual-crear&nota=" . rawurlencode($claveNota) . "'><i class='fa fa-truck'></i> Emitir</a>";
            } else {
                $guiaCol = "";
            }

            $datosJson .= '[
            "'.$notasSalidas[$i]["tip"].'",
            "'.$notasSalidas[$i]["ser"].'",
            "'.$notasSalidas[$i]["nro"].'",
            "'.$notasSalidas[$i]["fecemi"].'",
            "'.$notasSalidas[$i]["razcli"].'",
            "'.$notasSalidas[$i]["almacen"].'",
            "'.$notasSalidas[$i]["UsuReg"].'",
            "'.$estado.'",
            "'.$guiaCol.'",
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
ACTIVAR TABLA DE NOTAS SALIDAS
=============================================*/ 
$activarNotasSalidas = new TablaNotasSalidas();
$activarNotasSalidas -> mostrarTablaNotasSalidas();