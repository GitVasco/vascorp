<?php

require_once "../../controladores/pedidos.controlador.php";
require_once "../../modelos/pedidos.modelo.php";

class TablaPedidosCV
{

    /*=============================================
    MOSTRAR LA TABLA DE ARTICULOS
    =============================================*/

    public function mostrarTablaPedidosCV()
    {

        $valor = null;

        $pedidos = ControladorPedidos::ctrMostraPedidosGeneral($valor);

        if (count($pedidos) > 0) {

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
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button>" . $btnBarcodeEscaneo . "<button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button><button title='Anular Pedido' class='btn btn-xs  btn-danger btnAnularPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "' estado='" . $pedidos[$i]["estado"] . "'><i class='fa fa-close'></i></button><button title='Dividir Pedido' class='btn btn-xs btn-info btnDividirPed'  codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "'  total='" . $pedidos[$i]["total"] . "' data-toggle='modal' data-target='#modalDividir'><i class='fa fa-percent'></i></button><button title='Cambiar Lista de Precio' class='btn btn-xs bg-maroon btnPrecio' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-recycle'></i></button><button title='Cotizar Pedido' class='btn btn-xs btn-info btnCotizarPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-calculator'></i></button></div>";
                } else if ($pedidos[$i]["estado"] == "APROBADO") {

                    $estado = "<button class='btn btn-warning btn-xs btnAptear' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='APT'>APROBADO</button>";
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button>" . $btnBarcodeEscaneo . "<button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button><button title='Facturar Pedido' class='btn btn-xs btn-primary btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "' tip_doc='" . $pedidos[$i]["tipo_doc"] . "' nro_doc='" . $pedidos[$i]["documento"] . "' dscto='" . $pedidos[$i]["dscto"] . "' cod_ven='" . $pedidos[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button><button title='Anular Pedido' class='btn btn-xs  btn-danger btnAnularPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "' estado='" . $pedidos[$i]["estado"] . "'><i class='fa fa-close'></i></button></div>";
                } else if ($pedidos[$i]["estado"] == "APT") {

                    $estado = "<button class='btn btn-default btn-xs btn  btnConfirmar' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='CONFIRMADO'>APT</button>";
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button>" . $btnBarcodeEscaneo . "<button title='Facturar Pedido' class='btn btn-xs btn-primary btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "' tip_doc='" . $pedidos[$i]["tipo_doc"] . "' nro_doc='" . $pedidos[$i]["documento"] . "' dscto='" . $pedidos[$i]["dscto"] . "' cod_ven='" . $pedidos[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button><button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button></div>";
                } else if ($pedidos[$i]["estado"] == "CONFIRMADO") {

                    $estado = "<button class='btn btn-info btn-xs btn btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='FACTURADO'>CONFIRMADO</button>";
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Editar Pedido' class='btn btn-xs btn-warning btnEditarPedidoCV' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-pencil-square-o'></i></button>" . $btnBarcodeEscaneo . "<button title='Facturar Pedido' class='btn btn-xs btn-primary btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "' tip_doc='" . $pedidos[$i]["tipo_doc"] . "' nro_doc='" . $pedidos[$i]["documento"] . "' dscto='" . $pedidos[$i]["dscto"] . "' cod_ven='" . $pedidos[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button><button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button></div>";
                } else if ($pedidos[$i]["estado"] == "ANULADO") {

                    $estado = "<button class='btn btn-danger btn-xs btn' codigo='" . $pedidos[$i]["codigo"] . "'>ANULADO</button>";
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button></div>";
                } else {

                    $estado = "<button class='btn btn-success btn-xs btn' codigo='" . $pedidos[$i]["codigo"] . "' estadoPedido='FACTURADO'>FACTURADO</button>";
                    $botones =  "<div class='btn-group pedidosCvAcciones' role='group'><button title='Imprimir Pedido' class='btn btn-xs btn-success btnImprimirPedido' codigo='" . $pedidos[$i]["codigo"] . "'><i class='fa fa-print'></i></button><button title='Facturar Pedido' class='btn btn-xs btn-primary btnFacturar' codigo='" . $pedidos[$i]["codigo"] . "' cod_cli='" . $pedidos[$i]["cod_cli"] . "'  nom_cli='" . $pedidos[$i]["nombre"] . "' tip_doc='" . $pedidos[$i]["tipo_doc"] . "' nro_doc='" . $pedidos[$i]["documento"] . "' dscto='" . $pedidos[$i]["dscto"] . "' cod_ven='" . $pedidos[$i]["vendedor"] . "' data-toggle='modal' data-target='#modalFacturar'><i class='fa fa-paper-plane'></i></button></div>";
                }

                /*=============================================
            TRAEMOS LAS ACCIONES
            =============================================*/

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
