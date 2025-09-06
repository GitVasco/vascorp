<?php

require_once "../../controladores/produccion.controlador.php";
require_once "../../modelos/produccion.modelo.php";
require_once "../../controladores/plantilla.controlador.php";

class TablaPrehormado
{

    /* 
    * MOSTRAR TABLA DE ORDENES DE CORTE
    */
    public function mostrarTablaPrehormado()
    {

        $prehormado = ModeloProduccion::mdlMostrarPrehormados();

        if (count($prehormado) > 0) {

            $datosJson = '{
            "data": [';

            for ($i = 0; $i < count($prehormado); $i++) {

                // tipo de prehormado
                //$prehormado[$i]["tipo"] = ($prehormado[$i]["tipo"] == "01") ? "Servicio" : "Produccion";

                $id = $prehormado[$i]["id"];
                $editAction = "<a href='index.php?ruta=prehormado&id={$id}' class='btn btn-xs btn-warning disabled' role='button' aria-disabled='true' tabindex='-1'>
                    <i class='fa fa-pencil'></i>
                </a>";

                $deleteAction = "<button  type='button' class='btn btn-xs btn-danger btnEliminarPrehormado' idPrehormado='{$prehormado[$i]["id"]}'>
                    <i class='fa fa-times'></i>
                </button>";

                $botones =  "<div class='btn-group'>
                    {$editAction}
                    {$deleteAction}
                </div> ";

                $botones = ControladorPlantilla::htmlClean($botones);

                $datosJson .= '[
                "' . $prehormado[$i]["fecha_registro"] . '",
                "' . $prehormado[$i]["tipo"] . '",
                "' . $prehormado[$i]["articulo"] . '",
                "' . $prehormado[$i]["producto"] . '",
                "' . $prehormado[$i]["color"] . '",
                "' . $prehormado[$i]["talla"] . '",
                "' . $prehormado[$i]["cantidad"] . '",
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
ACTIVAR TABLA DE EDITAR DETALLE ORDEN CORTE
=============================================*/
$activarEditarOrdenCorte = new TablaPrehormado();
$activarEditarOrdenCorte->mostrarTablaPrehormado();
