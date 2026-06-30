<?php

require_once "../../controladores/descuentos-compuestos.controlador.php";
require_once "../../modelos/descuentos-compuestos.modelo.php";

class TablaDescuentosCompuestos
{
    public function mostrarTabla()
    {
        $origen = isset($_GET["origen"]) ? $_GET["origen"] : "";
        $cliente = isset($_GET["cliente"]) ? $_GET["cliente"] : "";

        $filas = ControladorDescuentosCompuestos::ctrListarDescuentosCompuestos($origen, 5000, $cliente);

        $data = array();

        foreach ($filas as $item) {
            $origenNota = (string) $item["origen_nota"];

            switch ($origenNota) {
                case "MANUAL":
                    $badge = "<span class='label label-success'>CONFIRMADO</span>";
                    break;
                case "AUTO":
                    $badge = "<span class='label label-warning'>SUGERIDO</span>";
                    break;
                case "DESCARTADO":
                    $badge = "<span class='label label-default'>DESCARTADO</span>";
                    break;
                default:
                    $badge = "<span class='label label-danger'>REVISAR</span>";
                    break;
            }

            $notaFinal = $item["nota_estandar_final"] !== null && $item["nota_estandar_final"] !== ""
                ? $item["nota_estandar_final"]
                : "<span class='text-muted'>—</span>";

            $pct1 = $item["pct1_final"] !== null ? rtrim(rtrim(number_format((float) $item["pct1_final"], 2, ".", ""), "0"), ".") : null;
            $pct2 = $item["pct2_final"] !== null ? rtrim(rtrim(number_format((float) $item["pct2_final"], 2, ".", ""), "0"), ".") : null;
            $porcentajes = ($pct1 !== null && $pct2 !== null)
                ? $pct1 . "% + " . $pct2 . "%"
                : "<span class='text-muted'>—</span>";

            $montoP1 = $item["monto_pct1_final"] !== null
                ? number_format((float) $item["monto_pct1_final"], 2)
                : "<span class='text-muted'>—</span>";
            $montoP2 = $item["monto_pct2_final"] !== null
                ? number_format((float) $item["monto_pct2_final"], 2)
                : "<span class='text-muted'>—</span>";

            $cliente = trim((string) $item["cliente"] . " - " . (string) $item["nombre_cliente"]);
            if (strlen($cliente) > 45) {
                $cliente = substr($cliente, 0, 42) . "...";
            }

            $notaOriginal = htmlspecialchars((string) $item["notas_original"], ENT_QUOTES, "UTF-8");

            $tipoDoc = htmlspecialchars((string) $item["tipo_doc"], ENT_QUOTES, "UTF-8");
            $numCta = htmlspecialchars((string) $item["num_cta"], ENT_QUOTES, "UTF-8");
            $documento = "<b>" . $tipoDoc . "</b> " . $numCta;

            $botones = "<div class='btn-group'>";

            if ($origenNota === "DESCARTADO") {
                $botones .= "<button class='btn btn-info btn-xs btnRestaurarDescuento' idDescuento='{$item["id"]}' title='Restaurar a la lista'><i class='fa fa-undo'></i> Restaurar</button>";
            } else {
                if ($origenNota === "AUTO") {
                    $botones .= "<button class='btn btn-success btn-xs btnConfirmarDescuento' idDescuento='{$item["id"]}' notaPropuesta='" . htmlspecialchars((string) $item["nota_estandar_propuesta"], ENT_QUOTES, "UTF-8") . "' title='Confirmar sugerencia'><i class='fa fa-check'></i> Confirmar</button>";
                }

                $botones .= "<button class='btn btn-primary btn-xs btnEditarDescuento' idDescuento='{$item["id"]}' data-toggle='modal' data-target='#modalCorregirDescuento' title='Corregir manualmente'><i class='fa fa-pencil'></i> Corregir</button>";
                $botones .= "<button class='btn btn-default btn-xs btnDescartarDescuento' idDescuento='{$item["id"]}' title='Descartar (no requiere corrección)'><i class='fa fa-ban'></i> Descartar</button>";
            }

            $botones .= "</div>";

            $data[] = array(
                $documento,
                $item["fecha"],
                htmlspecialchars($cliente, ENT_QUOTES, "UTF-8"),
                number_format((float) $item["monto"], 2),
                "<code>{$notaOriginal}</code>",
                $notaFinal,
                $porcentajes,
                $montoP1,
                $montoP2,
                $badge,
                $botones,
            );
        }

        echo json_encode(array("data" => $data));
    }
}

$tabla = new TablaDescuentosCompuestos();
$tabla->mostrarTabla();
