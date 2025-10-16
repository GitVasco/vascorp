<?php

require_once "../../controladores/produccion.controlador.php";
require_once "../../modelos/produccion.modelo.php";

require_once "../../controladores/materiaprima.controlador.php";
require_once "../../modelos/materiaprima.modelo.php";

class TablaPrehormado
{

    /*=============================================
    MOSTRAR LA TABLA DE UNIDADES DE MEDIDA
    =============================================*/

    public function mostrarTablaPrehormado()
    {

        $tipo = $_GET["tipoPrehormado"];

        if ($tipo == "producto") {
            $respuesta = ModeloProduccion::mdlMostrarArticulosBrasier();
        } else {
            $respuesta = ModeloMateriaPrima::mdlMostrarAlmacen01('COP');
        }


        if (count($respuesta) > 0) {

            $datosJson = '{
            "data": [';

            for ($i = 0; $i < count($respuesta); $i++) {

                $codigo = $tipo == "producto"
                    ? $respuesta[$i]["articulo"]
                    : $respuesta[$i]["codfab"];

                $nombre = $tipo == "producto"
                    ? $respuesta[$i]["nombre"]
                    : $respuesta[$i]["despro"];

                $botones = "<div class='btn-group'><button class='btn btn-primary btn-xs btnAgregarArticuloPrehormado recuperarArticuloPrehormado' idArticulo='" . $codigo . "' nombreArticulo='" . $nombre . "' colorArticulo='" . $respuesta[$i]["color"] . "' tallaArticulo='" . $respuesta[$i]["talla"] . "'><i class='fa fa-plus'></i></button></div>";

                $datosJson .= '[
                    "' . $codigo . '",
                    "' . $nombre . '",
                    "' . $respuesta[$i]["color"] . '",
                    "' . $respuesta[$i]["talla"] . '",
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
ACTIVAR TABLA DE prehormadoS
=============================================*/
$activarprehormados = new TablaPrehormado();
$activarprehormados->mostrarTablaPrehormado();
