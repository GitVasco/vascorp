<?php

require_once "../../controladores/materiaprima.controlador.php";
require_once "../../modelos/materiaprima.modelo.php";

class TablaMateriaPrima
{

    /*=============================================
    MOSTRAR LA TABLA DE MATERIA PRIMA
    =============================================*/

    public function mostrarTablaMateriaPrima()
    {

        $valor = null;
        $sublinea = isset($_GET["sublinea"]) ? $_GET["sublinea"] : "";

        $materiaprima = ControladorMateriaPrima::ctrMostrarMateriaPrima2($valor, $sublinea);
        if (count($materiaprima) > 0) {

            $datosJson = '{
            "data": [';

            for ($i = 0; $i < count($materiaprima); $i++) {

                $descripcion = str_replace('"', '', $materiaprima[$i]["DesPro"]);

                $proveedorFull = trim(str_replace('"', '', $materiaprima[$i]["Proveedores"]));
                $proveedorCorto = $proveedorFull;
                if (mb_strlen($proveedorCorto) > 22) {
                    $proveedorCorto = mb_substr($proveedorCorto, 0, 22) . '...';
                }
                $proveedorHtml = "<span class='mp-texto-corto' title='" . htmlspecialchars($proveedorFull, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($proveedorCorto, ENT_QUOTES, 'UTF-8') . "</span>";

                //estado de la materia prima
                if ($materiaprima[$i]["estpro"] == "Inactivo") {

                    $estado = "<span style='font-size:85%' class='label label-danger'>" . $materiaprima[$i]["estpro"] . "</span>";


                    $botones = "<div class='mp-acciones'><button class='btn btn-xs btn-info btnVisualizarArticulos' title='Visualizar Articulos' data-toggle='modal' data-target='#modalVisualizarArticulos' articuloMP='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-eye'></i></button><button class='btn btn-xs btn-default btnOrdenesMp' title='Ver OC / OS' data-toggle='modal' data-target='#modalOrdenesMp' codpro='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-file-text-o'></i></button></div>";
                } else {

                    $estado = "<span style='font-size:85%' class='label label-success'>" . $materiaprima[$i]["estpro"] . "</span>";

                    $botones = "<div class='mp-acciones'>"
                        . "<button class='btn btn-xs btn-info btnVisualizarArticulos' title='Visualizar Articulos' data-toggle='modal' data-target='#modalVisualizarArticulos' articuloMP='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-eye'></i></button>"
                        . "<button class='btn btn-xs btn-default btnOrdenesMp' title='Ver OC / OS' data-toggle='modal' data-target='#modalOrdenesMp' codpro='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-file-text-o'></i></button>"
                        . "<button class='btn btn-xs btn-warning btnEditarMateriaPrima' idMateriaPrima='" . $materiaprima[$i]["CodPro"] . "' data-toggle='modal' data-target='#modalEditarMateriaPrima' title='Editar Materia Prima'><i class='fa fa-pencil'></i></button>"
                        . "<button class='btn btn-xs btn-success btnDuplicarMateriaPrima' idMateriaPrima='" . $materiaprima[$i]["CodPro"] . "' data-toggle='modal' data-target='#modalDuplicarMateriaPrima' title='Nuevo Color'><i class='fa fa-clone'></i></button>"
                        . "<button class='btn btn-xs btn-primary btnEditarCosto' title='Visualizar Costo' data-toggle='modal' data-target='#modalEditarCostos' materiaPrima='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-money'></i></button>"
                        . "<button class='btn btn-xs btn-danger btnAnularMateriaPrima' title='Anular Materia Prima' idMateriaPrima='" . $materiaprima[$i]["CodPro"] . "'><i class='fa fa-times'></i></button>"
                        . "</div>";
                }


                $datosJson .= '[
                "' . $materiaprima[$i]["CodPro"] . '",
                "' . $materiaprima[$i]["CodFab"] . '",
                "' . $descripcion . '",
                "' . $materiaprima[$i]["Color"] . '",
                "' . $materiaprima[$i]["Talla"] . '",
                "' . $materiaprima[$i]["Unidad"] . '",
                "' . $proveedorHtml . '",
                "' . $materiaprima[$i]["CodAlm01"] . '",
                "' . $estado . '",
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
ACTIVAR TABLA DE MATERIA PRIMA
=============================================*/
$activarMateriaPrima = new TablaMateriaPrima();
$activarMateriaPrima->mostrarTablaMateriaPrima();
