<?php

require_once "../../controladores/abonos.controlador.php";
require_once "../../modelos/abonos.modelo.php";

class TablaCancelarAbonos
{

    /*=============================================
    MOSTRAR LA TABLA DE ABONOS
    =============================================*/

    public function mostrarTablaCancelarAbonos()
    {

        $item = null;
        $valor = null;

        $abono = ControladorAbonos::ctrMostrarAbonos($item, $valor);
        if (count($abono) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($abono); $i++) {

                /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/

                $botones =  "<input class='chkAbono' type='checkbox' id='chkAbono' name='chkAbono' saldo='" . $abono[$i]["monto"] . "' idAbono='" . $abono[$i]["id"] . "' fecAbono='" . $abono[$i]["fecha"] . "' opAbono='" . $abono[$i]["num_ope"] . "'> Buscar";

                $datosJson .= '[
            "' . $abono[$i]["fecha"] . '",
            "' . $abono[$i]["descripcion"] . '",
            "S/.' . $abono[$i]["monto"] . '",
            "' . $abono[$i]["agencia"] . '",
            "' . $abono[$i]["num_ope"] . '",
            "' . $botones . '"
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
ACTIVAR TABLA DE CANCELAR ABONO
=============================================*/
$activarCancelarAbonos = new TablaCancelarAbonos();
$activarCancelarAbonos->mostrarTablaCancelarAbonos();
