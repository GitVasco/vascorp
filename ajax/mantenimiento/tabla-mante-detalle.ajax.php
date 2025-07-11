<?php

require_once "../../controladores/mantenimiento.controlador.php";
require_once "../../modelos/mantenimiento.modelo.php";

class TablaMantenimientoDetalle
{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    public function mostrarTablaMantenimientoDetalle()
    {

        $valor = $_GET["manteCab"];
        $mantenimiento = ControladorMantenimiento::ctrMostrarMantenimientoDetalle($valor);

        if (count($mantenimiento) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($mantenimiento); $i++) {

                $des_larga = str_replace('"', '', $mantenimiento[$i]["item"]);


                /*=============================================
                TRAEMOS LAS ACCIONES
                =============================================*/
                // $botones =  "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarRepuesto' idDetMante='" . $mantenimiento[$i]["id"] . "' data-toggle='modal' data-target='#modalEditarRepuestos'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnAnularRepuestos' idDetMante='" . $mantenimiento[$i]["id"] . "'><i class='fa fa-times'></i></button></div>";

                $botones = "";

                $datosJson .= '[
            "' . $mantenimiento[$i]["cod_interno"] . '",
            "' . $des_larga . '",
            "' . $mantenimiento[$i]["cantidad"] . '",
            "' . $mantenimiento[$i]["precio"] . '",
            "' . $mantenimiento[$i]["total"] . '",
            "' . $mantenimiento[$i]["observacion"] . '",
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
ACTIVAR TABLA DE AGENCIAS
=============================================*/
$activarTabla = new TablaMantenimientoDetalle();
$activarTabla->mostrarTablaMantenimientoDetalle();
