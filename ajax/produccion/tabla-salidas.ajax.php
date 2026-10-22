<?php

require_once "../../controladores/salidas.controlador.php";
require_once "../../modelos/salidas.modelo.php";

class TablaSalidas
{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/

    public function mostrarTablaSalidas()
    {

        $valor = null;

        $salidas = ControladorSalidas::ctrMostrarSalidasGeneral($valor);

        if (count($salidas) > 0) {

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($salidas); $i++) {

                $botones = "";

                if (substr($salidas[$i]["codigo"], 0, 1) !== "T") {

                    if ($salidas[$i]["estado"] == "GENERADO") {

                        $estado = "<button class='btn btn-basic btn-xs btnAprobarPedido' codigo='" . $salidas[$i]["codigo"] . "' estadoPedido='APROBADO'>GENERADO</button>";

                        $botones =  "<div class='btn-group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarSalidaVarios' codigo='" . $salidas[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button><button title='Imprimir Salida' class='btn btn-xs btn-success btnImprimirSalida' codigo='" . $salidas[$i]["codigo"] . "'><i class='fa fa-print'></i></button><button title='Facturar salida' class='btn btn-xs btn-primary btnFacturarSalida' codigo='" . $salidas[$i]["codigo"] . "' cod_cli='" . $salidas[$i]["cod_cli"] . "'  nom_cli='" . $salidas[$i]["nombre"] . "' tip_doc='" . $salidas[$i]["tipo_documento"] . "' nro_doc='" . $salidas[$i]["documento"] . "' dscto='" . $salidas[$i]["dscto"] . "' cod_ven='" . $salidas[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button></div>";
                    } else if ($salidas[$i]["estado"] == "APROBADO") {

                        $estado = "<button class='btn btn-warning btn-xs btnAptear' codigo='" . $salidas[$i]["codigo"] . "' estadoPedido='APT'>APROBADO</button>";
                    } else if ($salidas[$i]["estado"] == "APT") {

                        $estado = "<button class='btn btn-default btn-xs btn  btnConfirmar' codigo='" . $salidas[$i]["codigo"] . "' estadoPedido='CONFIRMADO'>APT</button>";
                    } else if ($salidas[$i]["estado"] == "CONFIRMADO") {

                        $estado = "<button class='btn btn-info btn-xs btn btnFacturar' codigo='" . $salidas[$i]["codigo"] . "' estadoPedido='FACTURADO'>CONFIRMADO</button>";
                    } else {

                        $estado = "<button class='btn btn-success btn-xs btn' codigo='" . $salidas[$i]["codigo"] . "' estadoPedido='FACTURADO'>FACTURADO</button>";

                        // Ya procesada: no se ofrece "Pasar Movimiento a:" de nuevo.
                        $botones =  "<div class='btn-group'><button title='Imprimir Salida' class='btn btn-xs btn-success btnImprimirSalida' codigo='" . $salidas[$i]["codigo"] . "'><i class='fa fa-print'></i></button></div>";
                    }


                    $datosJson .= '[
                                        "' . ($i + 1) . '",
                                        "<b>' . $salidas[$i]["codigo"] . '</b>",
                                        "' . $salidas[$i]["cod_cli"] . '",
                                        "<b>' . $salidas[$i]["nombre"] . '</b>",
                                        "' . $salidas[$i]["vendedor"] . '",
                                        "<b>S/ ' . $salidas[$i]["total"] . '</b>",
                                        "' . $estado . '",
                                        "' . $salidas[$i]["nom_usu"] . '",
                                        "' . $salidas[$i]["fecha"] . '",
                                        "' . $botones . '"
                                        ],';
                }
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
ACTIVAR TABLA DE articulos
=============================================*/
$activarArticulossalidas = new TablaSalidas();
$activarArticulossalidas->mostrarTablaSalidas();
