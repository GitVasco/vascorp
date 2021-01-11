<?php

require_once "../controladores/cuentas.controlador.php";
require_once "../modelos/cuentas.modelo.php";
require_once "../controladores/clientes.controlador.php";
require_once "../modelos/clientes.modelo.php";
class TablaCuentasPendientes{

    /*=============================================
    MOSTRAR LA TABLA DE UNIDADES DE MEDIDA
    =============================================*/ 

    public function mostrarTablaCuentasPendientes(){


        $cuenta = ControladorCuentas::ctrRangoFechasCuentasPendientes($_GET["fechaInicial"], $_GET["fechaFinal"]);	
        if(count($cuenta)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($cuenta); $i++){  
        $clientes=ControladorClientes::ctrMostrarClientes("codigo",$cuenta[$i]["cliente"]);
        /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/      
            if($cuenta[$i]["estado"]=='PENDIENTE'){
                $estado =  "<button class='btn btn-danger btn-xs'>PENDIENTE</button>";
            }else{
                $estado =  "<button class='btn btn-success btn-xs'>CANCELADO</button>";
            }
            if($cuenta[$i]["saldo"]==0){
                
                $botones =  "<div class='btn-group'><button class='btn btn-primary btnVisualizarCuenta' numCta='".$cuenta[$i]["num_cta"]."'  title='Visualizar cuenta'><i class='fa fa-eye'></i></button><button class='btn btn-warning btnEditarCuenta' idCuenta='".$cuenta[$i]["id"]."' data-toggle='modal' data-target='#modalEditarCuenta' title='Editar cuenta'><i class='fa fa-pencil'></i></button><button class='btn btn-danger btnEliminarCuenta' idCuenta='".$cuenta[$i]["id"]."' title='Eliminar cuenta'><i class='fa fa-times'></i></button></div>";
                
            }else{
                if($cuenta[$i]["tipo_doc"]=="01" || $cuenta[$i]["tipo_doc"]=="03" ){
                    $botones =  "<div class='btn-group'><button class='btn btn-success btnCancelarCuenta' idCuenta='".$cuenta[$i]["id"]."' data-toggle='modal' data-target='#modalCancelarCuenta' title='Cancelar cuenta'><i class='fa fa-money'></i></button><button class='btn btn-primary btnVisualizarCuenta' numCta='".$cuenta[$i]["num_cta"]."'  title='Visualizar cuenta'><i class='fa fa-eye'></i></button><button class='btn btn-info btnAgregarLetra' idCuenta='".$cuenta[$i]["id"]."' cliente='".$clientes["nombre"]."' data-toggle='modal' data-target='#modalAgregarLetras' title='Agregar letra'><i style='color:white'  class='fa fa-usd'></i></button><button class='btn btn-warning btnEditarCuenta' idCuenta='".$cuenta[$i]["id"]."' data-toggle='modal' data-target='#modalEditarCuenta' title='Editar cuenta'><i class='fa fa-pencil'></i></button><button class='btn btn-danger btnEliminarCuenta' idCuenta='".$cuenta[$i]["id"]."' title='Eliminar cuenta'><i class='fa fa-times'></i></button></div>"; 
                }else{
                    $botones =  "<div class='btn-group'><button class='btn btn-success btnCancelarCuenta' idCuenta='".$cuenta[$i]["id"]."' data-toggle='modal' data-target='#modalCancelarCuenta' title='Cancelar cuenta'><i class='fa fa-money'></i></button><button class='btn btn-primary btnVisualizarCuenta' numCta='".$cuenta[$i]["num_cta"]."'  title='Visualizar cuenta'><i class='fa fa-eye'></i></button><button class='btn btn-warning btnEditarCuenta' idCuenta='".$cuenta[$i]["id"]."' data-toggle='modal' data-target='#modalEditarCuenta' title='Editar cuenta'><i class='fa fa-pencil'></i></button><button class='btn btn-danger btnEliminarCuenta' idCuenta='".$cuenta[$i]["id"]."' title='Eliminar cuenta'><i class='fa fa-times'></i></button></div>"; 
                }
            }
            
        
      

        

            $datosJson .= '[
            "'.$cuenta[$i]["tipo_doc"].'",
            "'.$cuenta[$i]["num_cta"].'",
            "'.$clientes["codigo"]." - ".$clientes["nombre"].'",
            "'.$cuenta[$i]["vendedor"].'",
            "'.$cuenta[$i]["fecha"].'",
            "'.$cuenta[$i]["fecha_ven"].'",
            "'.$cuenta[$i]["monto"].'",
            "'.$cuenta[$i]["saldo"].'",
            "'.$estado.'",
            "'.$cuenta[$i]["num_unico"].'",
            "'.$cuenta[$i]["doc_origen"].'",
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
ACTIVAR TABLA DE TIPO DE PAGO
=============================================*/ 
$activarCuentasPendientes = new TablaCuentasPendientes();
$activarCuentasPendientes -> mostrarTablaCuentasPendientes();
