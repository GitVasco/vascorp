<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";
class TablaCuentas
{

    /*=============================================
    MOSTRAR LA TABLA DE UNIDADES DE MEDIDA
    =============================================*/

    public function mostrarTablaCuentas()
    {

        $item = null;
        $valor = null;

        $cuenta = ControladorCuentas::ctrRangoFechasCuentas($_GET["ano"]);
        if (count($cuenta) > 0) {

            $datosJson = '{
        "data": [';


            foreach ($cuenta as $item) {
                $estado = $item["estado"] === 'PENDIENTE'
                    ? "<button class='btn btn-danger btn-xs btnCancelacionDirecta' idCta='{$item['id']}' tipo_doc='{$item['tipo_doc']}' num_cta='{$item['num_cta']}' cliente='{$item['cliente']}' vendedor='{$item['vendedor']}' monto='{$item['monto']}' saldo='{$item['saldo']}' fecha='{$item['fecha']}' fecha_ven='{$item['fecha_ven']}' doc_origen='{$item['doc_origen']}'>PENDIENTE</button>"
                    : "<button class='btn btn-success btn-xs'>CANCELADO</button>";

                $botones = "<button class='btn btn-xs btn-primary btnVisualizarCuenta' style='margin-right: 5px;' numCta='{$item["num_cta"]}' codCta='{$item["tipo_doc"]}' title='Visualizar cuenta'><i class='fa fa-eye'></i></button>";

                if ($item["saldo"] == 0) {
                    $botones .= "<button class='btn btn-xs btn-warning btnEditarCuenta' style='margin-right: 5px;' idCuenta='{$item["id"]}' data-toggle='modal' data-target='#modalEditarCuenta' title='Editar cuenta'><i class='fa fa-pencil'></i></button>";
                    $botones .= "<button class='btn btn-xs btn-danger btnEliminarCuenta' idCuenta='{$item["id"]}' title='Eliminar cuenta'><i class='fa fa-times'></i></button>";
                } else {
                    if (in_array($item["tipo_doc"], ["01", "03"])) {
                        if ($item["monto"] == $item["saldo"]) {
                            $botones .= "<button class='btn btn-xs btn-info btnAgregarLetra' style='margin-right: 5px;' idCuenta='{$item["id"]}' cliente='{$item["nombre"]}' data-toggle='modal' data-target='#modalAgregarLetras' title='Agregar letra'><i style='color:white' class='fa fa-usd'></i></button>";
                        }
                    } elseif ($item["tipo_doc"] == "85" && $item["estado"] === "PENDIENTE") {
                        $botones .= "<button class='btn btn-xs btn-info btnDividirLetra' style='margin-right: 5px;' idCuenta='{$item["id"]}' cliente='{$item["nombre"]}' data-toggle='modal' data-target='#modalDividirLetra' title='Dividir letra'><i class='fa fa-random'></i></button>";
                        $botones .= "<button class='btn btn-xs btn-success btnImprimirLetra' style='margin-right: 5px;' numCuenta='{$item["num_cta"]}'><i class='fa fa-print'></i></button>";
                        if ($item["protesta"] == "1") {
                            $botones .= "<button class='btn btn-xs btn-basic btnCargoProtesto' style='margin-right: 5px;' num_cta='{$item["num_cta"]}' cliente='{$item["cliente"]}' title='Cargo de Protesto'><i class='fa fa-file'></i></button>";
                        }
                    }
                    $botones .= "<button class='btn btn-xs btn-warning btnEditarCuenta' style='margin-right: 5px;' idCuenta='{$item["id"]}' data-toggle='modal' data-target='#modalEditarCuenta' title='Editar cuenta'><i class='fa fa-pencil'></i></button>";
                    $botones .= "<button class='btn btn-xs btn-danger btnEliminarCuenta' idCuenta='{$item["id"]}' title='Eliminar cuenta'><i class='fa fa-times'></i></button>";
                }

                $protesta = $item["protesta"] == "1" ? "<button class='btn btn-danger btn-xs'>SI</button>" : "";

                // Limitar el texto de cliente - nombre a 100 caracteres
                $textoCliente = $item["cliente"] . " - " . $item["nombre"];
                if (strlen($textoCliente) > 80) {
                    $textoCliente = substr($textoCliente, 0, 77) . "...";
                }

                $datosJson .= '[
                    "C' . $item["tipo_doc"] . '",
                    "' . $item["num_cta"] . '",
                    "' . $textoCliente . '",
                    "' . $item["vendedor"] . '",
                    "' . $item["fecha"] . '",
                    "' . $item["fecha_ven"] . '",
                    "' . number_format($item["monto"], 2) . '",
                    "' . number_format($item["saldo"], 2) . '",
                    "' . $estado . '",
                    "' . $item["num_unico"] . '",
                    "<center>' . $protesta . '</center>",
                    "' . $item["doc_origen"] . '",
                    "' . $botones . '"
                ],';
            }

            $datosJson = substr($datosJson, 0, -1);
            $datosJson .= ']}';

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
ACTIVAR TABLA DE TIPO DE PAGO
=============================================*/
$activarCuentas = new TablaCuentas();
$activarCuentas->mostrarTablaCuentas();
