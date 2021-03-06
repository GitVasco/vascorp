<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";
require_once "../../controladores/clientes.controlador.php";
require_once "../../modelos/clientes.modelo.php";
class TablaCancelarCuentas{

    /*=============================================
    MOSTRAR LA TABLA DE UNIDADES DE MEDIDA
    =============================================*/ 

    public function mostrarTablaCancelarCuentas(){

        $saldo = $_GET["saldo"]; 

        $cuenta = ControladorCuentas::ctrMostrarCuentasPendientes($saldo);	
        // var_dump($cuenta);
        if(count($cuenta)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($cuenta); $i++){  
        
            
            $botones =  "<input class='chkCancelar' type='checkbox' id='chkCancelar' name='chkCancelar' idCuenta='".$cuenta[$i]["id"]."'> Cancelar"; 
                
            $datosJson .= '[
            "'.$cuenta[$i]["tipo_doc"].'",
            "'.$cuenta[$i]["num_cta"].'",
            "'.$cuenta[$i]["cliente"]." - ".$cuenta[$i]["nombre"].'",
            "'.$cuenta[$i]["fecha"].'",
            "'.$cuenta[$i]["fecha_ven"].'",
            "'.$cuenta[$i]["monto"].'",
            "'.$cuenta[$i]["saldo"].'",
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
ACTIVAR TABLA CANCELAR CUENTAS
=============================================*/ 
$activarCancelarCuentas = new TablaCancelarCuentas();
$activarCancelarCuentas -> mostrarTablaCancelarCuentas();

