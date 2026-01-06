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
            $movimientos = ControladorMovimientos::ctrMostrarRangosGerencia($año, $mes);
        } else {
            // Mantener compatibilidad con código existente
            $movimientos = ControladorMovimientos::ctrMostrarRangos($mes);
        }

        if (count($movimientos) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($movimientos); $i++) {

                $ventas = "<div style='text-align:right !important'>" . number_format($movimientos[$i]["ventas"], 2) . "</div>";

                $cobranza = "<div style='text-align:right !important; color:green'>" . number_format($movimientos[$i]["cobranza"], 2) . "</div>";

                $saldo = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["saldo"], 2) . "</div>";

                if ($movimientos[$i]["p14"] > 0) {

                    $p14 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p14"], 2) . "</div>";
                } else {

                    $p14 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p15"] > 0) {

                    $p15 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p15"], 2) . "</div>";
                } else {

                    $p15 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p16"] > 0) {

                    $p16 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p16"], 2) . "</div>";
                } else {

                    $p16 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p17"] > 0) {

                    $p17 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p17"], 2) . "</div>";
                } else {

                    $p17 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p18"] > 0) {

                    $p18 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p18"], 2) . "</div>";
                } else {

                    $p18 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p19"] > 0) {

                    $p19 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p19"], 2) . "</div>";
                } else {

                    $p19 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p20"] > 0) {

                    $p20 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p20"], 2) . "</div>";
                } else {

                    $p20 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p21"] > 0) {

                    $p21 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p21"], 2) . "</div>";
                } else {

                    $p21 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p22"] > 0) {

                    $p22 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p22"], 2) . "</div>";
                } else {

                    $p22 = "<div style='text-align:right !important; color:red'>0</div>";
                }

                if ($movimientos[$i]["p23"] > 0) {

                    $p23 = "<div style='text-align:right !important; color:blue'>" . number_format($movimientos[$i]["p23"], 2) . "</div>";
                } else {

                    $p23 = "<div style='text-align:right !important; color:blue'>0</div>";
                }

                if ($movimientos[$i]["p24"] > 0) {

                    $p24 = "<div style='text-align:right !important; color:blue'>" . number_format($movimientos[$i]["p24"], 2) . "</div>";
                } else {

                    $p24 = "<div style='text-align:right !important; color:blue'>0</div>";
                }


                // p14 ahora contiene la suma acumulada de 2014-2020
                $p1418 = "<div style='text-align:right !important; color:red'>" . number_format($movimientos[$i]["p14"], 2) . "</div>";

                $datosJson .= '[
                "' . $movimientos[$i]["codigo"] . '",
                "' . $movimientos[$i]["descripcion"] . '",
                "<b>' . $ventas . '</b>",
                "<b>' . $cobranza . '</b>",
                "<b>' . $saldo . '</b>",
                "' . $p1418 . '",
                "' . $p19 . '",
                "' . $p20 . '",
                "' . $p21 . '",
                "' . $p22 . '",
                "' . $p23 . '",
                "' . $p24 . '"
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
