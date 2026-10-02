<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../controladores/servicio.controlador.php";
require_once "../../controladores/gre-manual.controlador.php";
require_once "../../modelos/servicio.modelo.php";

class TablaServicios
{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    public function mostrarTablaServicios()
    {

        $item = null;
        $valor = null;

        $servicios = ControladorServicios::ctrRangoFechasServicios($_GET["fechaInicial"], $_GET["fechaFinal"]);
        if (count($servicios) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($servicios); $i++) {

                /*=============================================
        ESTADO
        =============================================*/

                if ($servicios[$i]["estado"] == "INACTIVO") {

                    /* $estado = "<button class='btn btn-danger btn-xs btnActivar'>".$articulos[$i]["id"]."</button>"; */
                    $estado = "<button class='btn btn-danger btn-xs btnActivarSer' >Inactivo</button>";
                } else {

                    /* $estado = "<button class='btn btn-success btn-xs btnActivarArt'>".$articulos[$i]["id"]."</button>"; */
                    $estado = "<button class='btn btn-success btn-xs btnActivarSer' >Activo</button>";
                }


                /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/

                $botones =  "<div class='btn-group'><button class='btn btn-xs btn-info btnVisualizarServicio' title='Visualizar Servicio' data-toggle='modal' data-target='#modalVisualizarServicio' codigoServicio='" . $servicios[$i]["codigo"] . "'><i class='fa fa-eye'></i></button><button class='btn btn-xs btn-warning btnEditarServicio' title='Editar Servicio' idServicio='" . $servicios[$i]["codigo"] . "' data-toggle='modal' data-target='#modalEditarServicio'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-danger btnEliminarServicio' title='Eliminar Servicio' idServicio='" . $servicios[$i]["codigo"] . "'><i class='fa fa-times'></i></button><button class='btn btn-xs btn-outline-success pull-right btnDetalleServicio' idServicio='" . $servicios[$i]["codigo"] . "' style='border:green 1px solid'><img src='vistas/img/plantilla/excel.png' width='17px'></button></div>";
                // Guía de remisión (motivo otros / servicio de producción) para quien puede emitir guías manuales
                if (ControladorGreManual::puedeUsar() && $servicios[$i]["estado"] != "INACTIVO") {
                    $botones = substr($botones, 0, -strlen("</div>")) . "<a class='btn btn-xs btn-success' title='Emitir guía de remisión' target='_blank' rel='noopener' href='index.php?ruta=gre-manual-crear&servicio=" . rawurlencode($servicios[$i]["codigo"]) . "'><i class='fa fa-truck'></i></a></div>";
                }
                $fecha = substr($servicios[$i]["fecha"], 0, 10);
                $datosJson .= '[
            "' . ($i + 1) . '",
            "' . $servicios[$i]["codigo"] . '",
            "' . $servicios[$i]["guia"] . '",
            "' . $servicios[$i]["nombre"] . '",
            "' . $servicios[$i]["taller"] . " - " . $servicios[$i]["nom_sector"] . '",
            "' . $servicios[$i]["total"] . '",
            "' . $fecha . '",
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
ACTIVAR TABLA DE SERVICIOS
=============================================*/
$activarServicios = new TablaServicios();
$activarServicios->mostrarTablaServicios();
