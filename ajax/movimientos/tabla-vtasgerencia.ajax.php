<?php

require_once "../../controladores/movimientos.controlador.php";
require_once "../../modelos/movimientos.modelo.php";

//declaracion la zona horaria
date_default_timezone_set('America/Lima');

class TablaMovimientos
{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    public function mostrarTablaVtasGerencia()
    {

        $mes = isset($_GET["mes"]) ? $_GET["mes"] : null;
        $añoActual = date("Y");
        $año = isset($_GET["año"]) && $_GET["año"] != "" ? intval($_GET["año"]) : $añoActual;

        // Si se proporciona año explícitamente o es diferente al año actual, usar métodos nuevos
        if (isset($_GET["año"]) && $_GET["año"] != "" || $año != $añoActual) {
            $movimientos = ControladorMovimientos::ctrMostrarResumenVtasGerencia($año, $mes);
        } else {
            // Mantener compatibilidad con código existente
            $movimientos = ControladorMovimientos::ctrMostrarResumenVtas($mes);
        }

        if (count($movimientos) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($movimientos); $i++) {

                // valimos el tipo de documento sea igual al año actual
                if ($movimientos[$i]["tipo"] == date("Y")) {

                    $neto = "<div style='text-align:right !important'><b>" . number_format($movimientos[$i]["neto"], 2) . "</b></div>";
                    $igv = "<div style='text-align:right !important'><b>" . number_format($movimientos[$i]["igv"], 2) . "</div>";
                    $dscto = "<div style='text-align:right !important'><b>" . number_format($movimientos[$i]["dscto"], 2) . "</b></div>";
                    $total = "<div style='text-align:right !important'><b>" . number_format($movimientos[$i]["total"], 2) . "</b></div>";
                } else {

                    $neto = "<div style='text-align:right !important'>" . number_format($movimientos[$i]["neto"], 2) . "</div>";
                    $igv = "<div style='text-align:right !important'>" . number_format($movimientos[$i]["igv"], 2) . "</div>";
                    $dscto = "<div style='text-align:right !important'>" . number_format($movimientos[$i]["dscto"], 2) . "</div>";
                    $total = "<div style='text-align:right !important'>" . number_format($movimientos[$i]["total"], 2) . "</div>";
                }

                $datosJson .= '[
                "' . $movimientos[$i]["tipo"] . '",
                "' . $movimientos[$i]["tipo_documento"] . '",
                "' . $neto . '",
                "' . $igv . '",
                "' . $dscto . '",
                "' . $total . '"
                ],';
            }

            $datosJson = substr($datosJson, 0, -1);

            $datosJson .= '] 

                }';

            echo $datosJson;
        } else {

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
$activarMovimientos->mostrarTablaVtasGerencia();
