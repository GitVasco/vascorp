<?php

require_once "../../controladores/ingresos.controlador.php";
require_once "../../modelos/ingresos.modelo.php";
// declaramos la zona horaria
date_default_timezone_set('America/Lima');

class TablaVerIngresos
{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    public function mostrarTablaVerIngresos()
    {

        $item = null;
        $valor = null;

        $ingresos = ControladorIngresos::ctrRangoFechasVerIngresos($_GET["fechaInicial"], $_GET["fechaFinal"]);

        if (count($ingresos) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($ingresos); $i++) {
                for ($j = 1; $j <= 8; $j++) {
                    ${"t$j"} = ($ingresos[$i]["t$j"] == '0') ? '' : $ingresos[$i]["t$j"];
                }


                $datosJson .= '[
                "' . $ingresos[$i]["tipo"] . '",
                "' . $ingresos[$i]["cod_sector"] . " - " . $ingresos[$i]["nom_sector"] . '",
                "' . $ingresos[$i]["guia"] . '",
                "' . $ingresos[$i]["fechas"] . '",
                "' . $ingresos[$i]["documento"] . '",
                "' . $ingresos[$i]["modelo"] . '",
                "' . $ingresos[$i]["nombre"] . '",
                "' . $ingresos[$i]["color"] . '",
                "' . $t1 . '",
                "' . $t2 . '",
                "' . $t3 . '",
                "' . $t4 . '",
                "' . $t5 . '",
                "' . $t6 . '",
                "' . $t7 . '",
                "' . $t8 . '",
                "' . $ingresos[$i]["total"] . '"
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
ACTIVAR TABLA DE VER INGRESOS
=============================================*/
$activarVerIngresos = new TablaVerIngresos();
$activarVerIngresos->mostrarTablaVerIngresos();
