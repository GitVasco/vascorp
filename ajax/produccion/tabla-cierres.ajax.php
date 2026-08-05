<?php

require_once "../../controladores/cierres.controlador.php";
require_once "../../modelos/cierres.modelo.php";

// declaramos zona horaria
date_default_timezone_set('America/Lima');
class TablaCierres
{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    public function mostrarTablaCierres()
    {


        $cierres = ControladorCierres::ctrRangoFechasCierres($_GET["fechaInicial"], $_GET["fechaFinal"]);
        if (count($cierres) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($cierres); $i++) {

                /*=============================================
        ESTADO
        =============================================*/

                if ($cierres[$i]["estado"] == "INACTIVO") {

                    /* $estado = "<button class='btn btn-danger btn-xs btnActivar'>".$articulos[$i]["id"]."</button>"; */
                    $estado = "<button class='btn btn-danger btn-xs btnActivarCierre' >Inactivo</button>";
                } else {

                    /* $estado = "<button class='btn btn-success btn-xs btnActivarArt'>".$articulos[$i]["id"]."</button>"; */
                    $estado = "<button class='btn btn-success btn-xs btnActivarCierre' >Activo</button>";
                }

                if ($cierres[$i]["estado_pago"] == "POR PAGAR") {

                    /* $estado = "<button class='btn btn-danger btn-xs btnActivar'>".$articulos[$i]["id"]."</button>"; */
                    $estado_pago = "<button class='btn btn-warning btn-xs btnPagarCierre' guiaCierre='" . $cierres[$i]["guia"] . "' estadoPago='PAGADO'>POR PAGAR</button>";
                } else {

                    /* $estado = "<button class='btn btn-success btn-xs btnActivarArt'>".$articulos[$i]["id"]."</button>"; */
                    $estado_pago = "<button class='btn btn-primary btn-xs btnPagarCierre' guiaCierre='" . $cierres[$i]["guia"] . "' estadoPago='POR PAGAR'>PAGADO</button>";
                }

                /*=============================================
        TRAEMOS LAS ACCIONES
        =============================================*/
                $fecha = substr($cierres[$i]["fecha"], 0, 10);

                if ($cierres[$i]["estado_pago"] == "PAGADO") {
                    $btnEditar = "<button class='btn btn-xs btn-warning' disabled title='No se puede editar: cierre pagado'><i class='fa fa-pencil'></i></button>";
                } else {
                    $btnEditar = "<button class='btn btn-xs btn-warning btnEditarCierre' title='Editar cierre' idCierre='" . $cierres[$i]["codigo"] . "'><i class='fa fa-pencil'></i></button>";
                }

                $botones =  "<div class='btn-group'>"
                    . "<button class='btn btn-xs btn-info btnVisualizarCierre' title='Visualizar Cierre' data-toggle='modal' data-target='#modalVisualizarCierre' codigoCierre='" . $cierres[$i]["codigo"] . "'><i class='fa fa-eye'></i></button>"
                    . $btnEditar
                    . "<button class='btn btn-xs btn-danger btnEliminarCierre' title='Eliminar Cierre' idCierre='" . $cierres[$i]["codigo"] . "'><i class='fa fa-times'></i></button>"
                    . "<button class='btn btn-xs btn-outline-success pull-right btnDetalleCierre' idCierre='" . $cierres[$i]["codigo"] . "' style='border:green 1px solid'><img src='vistas/img/plantilla/excel.png' width='18px'></button>"
                    . "</div>";

                $datosJson .= '[
            "' . ($i + 1) . '",
            "' . $cierres[$i]["codigo"] . '",
            "' . $cierres[$i]["guia"] . '",
            "' . $cierres[$i]["nombre"] . '",
            "' . $cierres[$i]["taller"] . " - " . $cierres[$i]["nom_sector"] . '",
            "' . $cierres[$i]["total"] . '",
            "' . $fecha . '",
            "' . $estado . '",
            "' . $estado_pago . '",
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
$activarCierres = new TablaCierres();
$activarCierres->mostrarTablaCierres();
