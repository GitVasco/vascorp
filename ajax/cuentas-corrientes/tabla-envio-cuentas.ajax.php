<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

class TablaEnvioCuentas{

    /*=============================================
    MOSTRAR LA TABLA DE CuentaS
    =============================================*/ 

    public function mostrarTablaEnvioCuentas(){

        $item = null;     
        $valor = null;

        $Cuenta = ControladorCuentas::ctrRangoFechaEnvioCuentas($_GET["fechaInicial"], $_GET["fechaFinal"]);	
        if(count($Cuenta)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($Cuenta); $i++){  

        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/         
        
        $archivo = isset($Cuenta[$i]["archivo"]) ? $Cuenta[$i]["archivo"] : "";
        $extArchivo = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        $formatoActual = ($extArchivo === "xls" || $extArchivo === "xlsx") ? "excel" : "txt";
        $badgeFormato = $archivo !== ""
            ? ($formatoActual === "excel"
                ? "<span class='label label-info' title='Archivo actual'>Excel</span>"
                : "<span class='label label-default' title='Archivo actual'>TXT</span>")
            : "<span class='label label-warning' title='Sin archivo'>Sin archivo</span>";

        $idEnvio = (int) $Cuenta[$i]["id"];
        $btnDescarga = "<div class='btn-group'>"
            ."<button type='button' class='btn btn-xs btn-success dropdown-toggle' data-toggle='dropdown' title='Descargar planilla'>"
            ."<i class='fa fa-download'></i> <span class='caret'></span>"
            ."</button>"
            ."<ul class='dropdown-menu dropdown-menu-right'>"
            ."<li><a href='#' class='btnDescargarEnvioFormato' idEnvio='".$idEnvio."' data-formato='txt'><i class='fa fa-file-text-o'></i> TXT (vigente)</a></li>"
            ."<li><a href='#' class='btnDescargarEnvioFormato' idEnvio='".$idEnvio."' data-formato='excel'><i class='fa fa-file-excel-o'></i> Excel (nuevo)</a></li>"
            ."</ul>"
            ."</div>";

        $botones =  "<div class='btn-group'>"
            ."<button type='button' class='btn btn-xs btn-info btnVisualizarEnvioCuenta' idEnvio='".$idEnvio."' title='Ver detalle'><i class='fa fa-eye'></i></button>"
            ."<button type='button' class='btn btn-xs btn-warning btnEditarEnvioLetra' idEnvio='".$idEnvio."' title='Editar envío'><i class='fa fa-pencil'></i></button>"
            .$btnDescarga
            ."<button type='button' class='btn btn-xs btn-danger btnAnularEnvioCuenta' idEnvio='".$idEnvio."' title='Anular envío'><i class='fa fa-times'></i></button>"
            ."</div>"; 

            $datosJson .= '[
            "'.$Cuenta[$i]["codigo"].'",
            "'.$Cuenta[$i]["fecha"].'",
            "'.$Cuenta[$i]["nombre"].'",
            "'.$Cuenta[$i]["cantidad"].'",
            "'.$badgeFormato.'",
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
ACTIVAR TABLA DE Cuenta
=============================================*/ 
$activarEnvioCuentas = new TablaEnvioCuentas();
$activarEnvioCuentas -> mostrarTablaEnvioCuentas();

