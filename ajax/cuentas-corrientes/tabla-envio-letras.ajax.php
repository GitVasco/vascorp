<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

class TablaEnvioLetras
{

    /*=============================================
    MOSTRAR LA TABLA DE cuentas
    =============================================*/

    public function mostrarTablaEnvioLetras()
    {

        $item = null;
        $valor = null;
        $codigoEnvio = isset($_GET["codigoEnvio"]) ? trim($_GET["codigoEnvio"]) : null;
        if ($codigoEnvio === "") {
            $codigoEnvio = null;
        }

        $cuenta = ControladorCuentas::ctrMostrarCuentasUnicos($item, $valor, $codigoEnvio);
        if (count($cuenta) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($cuenta); $i++) {

                if ($cuenta[$i]["estado"] == 'PENDIENTE') {
                    $estado =  "<button class='btn btn-danger btn-xs'>PENDIENTE</button>";
                } else {
                    $estado =  "<button class='btn btn-success btn-xs'>CANCELADO</button>";
                }

                /*=============================================
            TRAEMOS LAS ACCIONES
            =============================================*/

                $botones =  "<button class='btn btn-primary btn-xs agregarEnvioCuenta recuperarEnvioCuenta' idcuenta='" . $cuenta[$i]["id"] . "'><i class='fa fa-plus-square'></i> Agregar</button>";

                $datosJson .= '[
            "' . $cuenta[$i]["tipo_doc"] . '",
            "' . $cuenta[$i]["num_cta"] . '",
            "' . $cuenta[$i]["cliente"] . " - " . $cuenta[$i]["nombre"] . '",
            "' . $cuenta[$i]["fecha"] . '",
            "' . $cuenta[$i]["fecha_ven"] . '",
            "S/.' . number_format($cuenta[$i]["saldo"], 2) . '",
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
ACTIVAR TABLA DE CANCELAR cuenta
=============================================*/
$activarEnvioLetras = new TablaEnvioLetras();
$activarEnvioLetras->mostrarTablaEnvioLetras();
