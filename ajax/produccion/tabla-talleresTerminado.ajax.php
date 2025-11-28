<?php

require_once "../../controladores/talleres.controlador.php";
require_once "../../modelos/talleres.modelo.php";


date_default_timezone_set('America/Lima');

class TablaTalleresT
{

    /*
    * MOSTRAR TABLA DE ORDENES DE CORTE
    */
    public function mostrarTablaTalleresT()
    {

        $valor = null;

        $talleres = ControladorTalleres::ctrRangoFechasTalleresTerminados($_GET["fechaInicial"], $_GET["fechaFinal"]);
        if (count($talleres) > 0) {

            #var_dump("almacencorte", $talleres);

            $datosJson = '{
            "data": [';

            for ($i = 0; $i < count($talleres); $i++) {

                /*
            todo: ESTADO
            */
                if ($talleres[$i]["estado"] == "1") {


                    $estado = "<span style='font-size:85%' class='label label-info'>Generado</span>";
                } elseif ($talleres[$i]["estado"] == "2") {

                    $estado = "<span style='font-size:85%' class='label label-primary'>En Proceso</span>";
                } else {

                    $estado = "<button class='btn btn-success btn-xs btnReiniciarTallerT' codigoTallerT='" . $talleres[$i]["codigo"] . "' estadoTaller='1'>Terminado</button>";
                }
                $botones = "<div class='btn-group'><button class='btn btn-xs btn-warning btnEditarTallerTerminado' title='Editar Taller terminado' idTallerT='" . $talleres[$i]["id"] . "' data-toggle='modal' data-target='#editarTallerT' ><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-success btnDividirTallerTerminado' title='Dividir cantidad'  idTaller='" . $talleres[$i]["codigo"] . "' data-toggle='modal' data-target='#dividirTallerT' ><i class='fa fa-random'></i></button></div>";

                $datosJson .= '[
                "' . $talleres[$i]["id"] . '",
                "' . $talleres[$i]["codigo"] . '",
                "' . $talleres[$i]["modelo"] . '",
                "' . $talleres[$i]["color"] . '",
                "' . $talleres[$i]["talla"] . '",
                "' . $talleres[$i]["nom_operacion"] . '",
                "' . $talleres[$i]["trabajador"] . '",
                "' . $talleres[$i]["cantidad"] . '",
                "' . $talleres[$i]["fecha_proceso"] . '",
                "' . $talleres[$i]["fecha_terminado"] . '",
                "' . $estado . '",
                "' . $talleres[$i]["tiempo_real"] . " min." . '",
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
ACTIVAR TABLA DE orden$ordencorte
=============================================*/
$activarTalleresT = new TablaTalleresT();
$activarTalleresT->mostrarTablaTalleresT();
