<?php

require_once "../../controladores/movimientos.controlador.php";
require_once "../../modelos/movimientos.modelo.php";

//declaracion la zona horaria
date_default_timezone_set('America/Lima');

class TablaMovimientos{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/ 

    public function mostrarTablaVtasGerencia(){

        $mes = isset($_GET["mes"]) ? $_GET["mes"] : null;
        $añoActual = date("Y");
        $año = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : $añoActual;

        // Si se proporciona año explícitamente o es diferente al año actual, usar métodos nuevos
        if (isset($_GET["año"]) && $_GET["año"] != "" || $año != $añoActual) {
            $movimientos = ControladorMovimientos::ctrMostrarResumenVdorGerencia($año, $mes);
        } else {
            // Mantener compatibilidad con código existente
            $movimientos = ControladorMovimientos::ctrMostrarResumenVdor($mes);
        }	

        if(count($movimientos)>0){

        $datosJson = '{
        "data": [';

        for($i = 0; $i < count($movimientos); $i++){

            if($movimientos[$i]["codigo"] == "ZZ"){

                $pedidos = "<div style='text-align:right !important'><b>".number_format($movimientos[$i]["pedidos"],2)."</b></div>";
                $ventas = "<div style='text-align:right !important'><b>".number_format($movimientos[$i]["ventas"],2)."</div>";
                $total = "<div style='text-align:right !important'><b>".number_format($movimientos[$i]["total"],2)."</b></div>";

            }else{

                $pedidos = "<div type='button' style='text-align:right !important'>".number_format($movimientos[$i]["pedidos"],2)."</div>";

                
            }

            if($movimientos[$i]["ventas"] < 0){

                $ventas = "<div style='text-align:right !important'>0.00</div>";

            }else{

                $ventas = "<div style='text-align:right !important'>".number_format($movimientos[$i]["ventas"],2)."</div>";

            }

            if($movimientos[$i]["total"] < 0){

                $total = "<div style='text-align:right !important'>0.00</div>";

            }else{

                $total = "<div style='text-align:right !important'>".number_format($movimientos[$i]["total"],2)."</div>";

            }            

            #$ventas = "<div style='text-align:right !important'>".number_format($movimientos[$i]["ventas"],2)."</div>";
            #$total = "<div style='text-align:right !important'>".number_format($movimientos[$i]["total"],2)."</div>";

            $codigo = "<button class='btn btn-link btn-xs btnRptPeds' title='Pedidos' vendedor='".$movimientos[$i]["codigo"]."' >".$movimientos[$i]["codigo"]."</button>";
            
                $datosJson .= '[
                "'.$codigo.'",
                "'.$movimientos[$i]["descripcion"].'",
                "'.$ventas.'",
                "'.$pedidos.'",                
                "'.$total.'"
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
ACTIVAR TABLA DE PRODUCTOS
=============================================*/ 
$activarMovimientos = new TablaMovimientos();
$activarMovimientos -> mostrarTablaVtasGerencia();