<?php

require_once "../../controladores/pedidos.controlador.php";
require_once "../../modelos/pedidos.modelo.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/decisiones-credito.modelo.php";

class TablaPedidosCV
{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/

    public function mostrarTablaPedidosCV()
    {

        $valor = 'confirmado';

        $pedidos = ControladorPedidos::ctrMostraPedidosTablas($valor);

        if (count($pedidos) > 0) {

            $codigosPedido = array();
            for ($j = 0; $j < count($pedidos); $j++) {
                $codigosPedido[] = (int) $pedidos[$j]["codigo"];
            }
            $mapaControles = class_exists("ModeloDecisionesCredito")
                ? ModeloDecisionesCredito::mdlMapaControlesPendientesPorPedidos($codigosPedido)
                : array();

            $datosJson = '{
        "data": [';

            for ($i = 0; $i < count($pedidos); $i++) {

                //* moneda
                $moneda = $pedidos[$i]["lista"] == "precio1" ? "$ " : "S/ ";

                $codVenRow = isset($pedidos[$i]["vendedor"]) ? trim((string) $pedidos[$i]["vendedor"]) : "";
                $btnBarcodeEscaneo = (strlen($codVenRow) >= 2 && substr($codVenRow, 0, 2) === "08")
                    ? "<button type='button' title='Escaneo código de barras' class='btn btn-xs btn-default btnEscaneoBarcodePedCv' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-barcode'></i></button>"
                    : "";

                /*
            * ESTADOS
            */
                if ($pedidos[$i]["estado"] == "GENERADO") {

                    $estado = "<button class='btn btn-basic btn-xs btnAprobarPedido' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='APROBADO'>GENERADO</button>";
                } else if ($pedidos[$i]["estado"] == "APROBADO") {

                    $estado = "<button class='btn btn-warning btn-xs btnAptear' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='APT'>APROBADO</button>";
                } else if ($pedidos[$i]["estado"] == "APT") {

                    $estado = "<button class='btn btn-default btn-xs btn  btnConfirmar' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='CONFIRMADO'>APT</button>";
                } else if ($pedidos[$i]["estado"] == "CONFIRMADO") {

                    $codigoPedido = (int) $pedidos[$i]["codigo"];
                    $badgeControl = "";
                    if (isset($mapaControles[$codigoPedido])) {
                        $ctrl = $mapaControles[$codigoPedido];
                        $tituloCtrl = isset($ctrl["condicion_etiqueta"])
                            ? $ctrl["condicion_etiqueta"]
                            : $ctrl["condicion_codigo"];
                        if ((int) $ctrl["bloquea_apt"] === 1) {
                            $badgeControl = " <span class='label label-danger' title='"
                                . htmlspecialchars("Control pendiente: " . $tituloCtrl, ENT_QUOTES, "UTF-8")
                                . "'><i class='fa fa-lock'></i></span>";
                        }
                    }

                    $estado = "<button class='btn btn-info btn-xs btn btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='FACTURADOS'>CONFIRMADO</button>" . $badgeControl;
                } else {

                    $estado = "<button class='btn btn-success btn-xs btn' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='FACTURADOS'>FACTURADO</button>";
                }

                /*=============================================
            TRAEMOS LAS ACCIONES
            =============================================*/

                $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button>" . $btnBarcodeEscaneo . "<button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button><button title='Facturar Pedido' class='btn btn-xs btn-primary btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "' tip_doc='" . $pedidos[$i]["tipo_doc"] . "' nro_doc='" . $pedidos[$i]["documento"] . "' dscto='" . $pedidos[$i]["dscto"] . "' cod_ven='" . $pedidos[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button><button title='Cotizar Pedido' class='btn btn-xs btn-info btnCotizarPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-calculator'></i></button><button title='Duplicar Pedido' class='btn btn-xs btn-default btnDuplicarPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-copy'></i></button></div>";

                //*Validar estado del ruc
                if ($pedidos[$i]["tipo_documento"] == 6) {

                    $codigo = "<button class='btn btn-warning btn-xs btn btnValidarRuc' documento='" . $pedidos[$i]["documento"] . "' onclick='ValidarRuc()' name='validarRuc' id='validarRuc'>" . $pedidos[$i]["cod_cli"] . "</button>";
                } else {

                    $codigo = $pedidos[$i]["cod_cli"];
                }


                $datosJson .= '[
            "' . ($i + 1) . '",
            "<b>' . $pedidos[$i]["codigo"] . '</b>",
            "' . $codigo . '",
            "<b>' . $pedidos[$i]["nombre"] . '</b>",
            "' . $pedidos[$i]["vendedor"] . '",
            "<b>' . $moneda . $pedidos[$i]["total"] . '</b>",
            "' . $pedidos[$i]["descripcion"] . '",
            "' . $estado . '",
            "' . $pedidos[$i]["nom_usu"] . '",
            "' . $pedidos[$i]["fecha"] . '",
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
ACTIVAR TABLA DE articulos
=============================================*/
$activarArticulosPedidos = new TablaPedidosCV();
$activarArticulosPedidos->mostrarTablaPedidosCV();
