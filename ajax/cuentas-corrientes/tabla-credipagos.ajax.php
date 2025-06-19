<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

class TablaCredipagos
{

    /*=============================================
    MOSTRAR LA TABLA DE ABONOS
    =============================================*/

    public function mostrarTablaCredipagos()
    {

        $credipagos = ControladorCuentas::ctrVerCredipagos();
        if (count($credipagos) > 0) {

            $datosJson = '{
                "data": [';

            for ($i = 0; $i < count($credipagos); $i++) {

                $cp = $credipagos[$i];
                if ($cp["tipo_doc"] == "01") {
                    $tipo_doc = "Factura";
                } else if ($cp["tipo_doc"] == "03") {
                    $tipo_doc = "Boleta";
                } else if ($cp["tipo_doc"] == "07") {
                    $tipo_doc = "Nota de Crédito";
                } else if ($cp["tipo_doc"] == "08") {
                    $tipo_doc = "Nota de Débito";
                } else if ($cp["tipo_doc"] == "09") {
                    $tipo_doc = "Proforma";
                } else if ($cp["tipo_doc"] == "85") {
                    $tipo_doc = "Letras";
                } else {
                    $tipo_doc = "Otro";
                }

                $monto = "<div style='text-align:right;'>" . number_format($cp["monto"], 2) . "</div>";

                // boton para eliminar
                $botones = "<div class='form-inline'><div class='checkbox' style='margin-right:10px; display:inline-block;'><label><input type='checkbox' class='credipagoCheck' data-id-credipago='" . $credipagos[$i]["id"] . "'> Seleccionar</label></div></div>";

                $datosJson .= '[
                    "' . $tipo_doc . '",
                    "' . $credipagos[$i]["num_cta"] . '",
                    "' . $credipagos[$i]["fecha"] . '",
                    "' . $credipagos[$i]["cliente"] . '",
                    "' . $credipagos[$i]["nombre"] . '",
                    "' . $monto . '",
                    "' . $credipagos[$i]["vendedor"] . '",
                    "' . $credipagos[$i]["notas"] . '",
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
$activarCredipagos = new TablaCredipagos();
$activarCredipagos->mostrarTablaCredipagos();
