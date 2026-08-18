<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";
require_once "../../controladores/config.php";

date_default_timezone_set('America/Lima');

class TablaVerCuentas2
{

    /*=============================================
    MOSTRAR LA TABLA DE CuentaS
    =============================================*/

    public function mostrarTablaVerCuentas2()
    {

        $Cuenta = ControladorCuentas::ctrMostrarCancelacionesV2($_GET["numCta"], $_GET["codCta"]);
        if (count($Cuenta) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($Cuenta); $i++) {

                $date = $Cuenta[$i]["fecha"];
                $cierre_periodo = CIERRE_PERIODO;

                // si la fecha es mayor al cierre de periodo, no se puede editar, mostrar el label sino los botones
                if ($date < $cierre_periodo) {

                    $botones = "<label class='label label-danger'>No editable</label>";
                } else {

                    $botones = "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarCancelacion' idCancelacion='" . $Cuenta[$i]["id"] . "' data-toggle='modal' data-target='#modalEditarCancelacion'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarCancelacion' idCancelacion='" . $Cuenta[$i]["id"] . "' ><i class='fa fa-times'></i></button></div>";
                }



                /*=============================================
                TRAEMOS LAS ACCIONES
                =============================================*/
                // $botones = "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarCancelacion' idCancelacion='" . $Cuenta[$i]["id"] . "' data-toggle='modal' data-target='#modalEditarCancelacion'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarCancelacion' idCancelacion='" . $Cuenta[$i]["id"] . "' ><i class='fa fa-times'></i></button></div>";

                $montoFmt = number_format((float) $Cuenta[$i]["monto"], 2, ".", ",");

                $datosJson .= '[
            "' . $Cuenta[$i]["cod_pago"] . '",
            "' . $Cuenta[$i]["doc_origen"] . '",
            "' . $Cuenta[$i]["fecha"] . '",
            "' . $Cuenta[$i]["notas"] . '",
            "' . $montoFmt . '",
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
ACTIVAR TABLA DE Cuenta
=============================================*/
$activarVerCuentas2 = new TablaVerCuentas2();
$activarVerCuentas2->mostrarTablaVerCuentas2();
