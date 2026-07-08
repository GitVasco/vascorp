<?php

require_once "../../controladores/metas-vendedor.controlador.php";
require_once "../../modelos/metas-vendedor.modelo.php";

class TablaMetasVendedor
{
    private static function formatoMonto($valor)
    {
        return "S/ " . number_format((float) $valor, 2, ".", ",");
    }

    private static function formatoPorcentaje($real, $meta)
    {
        $meta = (float) $meta;
        $real = (float) $real;

        if ($meta <= 0) {
            return "<span class='text-muted'>—</span>";
        }

        $pct = round(($real / $meta) * 100, 1);
        $clase = "label-default";

        if ($pct >= 100) {
            $clase = "label-success";
        } elseif ($pct >= 80) {
            $clase = "label-warning";
        } else {
            $clase = "label-danger";
        }

        return "<span class='label {$clase}'>{$pct}%</span>";
    }

    public function mostrarTabla()
    {
        $anio = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y");
        $mes = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n");
        $periodo = ControladorMetasVendedor::ctrNormalizarPeriodo($anio, $mes);

        $metas = ControladorMetasVendedor::ctrListarMetasPeriodo($periodo["anio"], $periodo["mes"]);

        if (count($metas) === 0) {
            echo '{"data":[]}';
            return;
        }

        $filas = array();

        foreach ($metas as $meta) {
            $vendedor = $meta["cod_vendedor"] . " - " . ($meta["nombre_vendedor"] ?: $meta["cod_vendedor"]);
            $metaCobranzaTxt = $meta["meta_cobranza"] === null
                ? "<span class='text-muted'>—</span>"
                : self::formatoMonto($meta["meta_cobranza"]);
            $pctCobranza = $meta["meta_cobranza"] === null
                ? "<span class='text-muted'>—</span>"
                : self::formatoPorcentaje($meta["cobranza_real"], $meta["meta_cobranza"]);

            $botones = "<div class='btn-group'>"
                . "<button class='btn btn-xs btn-warning btnEditarMeta' idMeta='" . (int) $meta["id"] . "' data-toggle='modal' data-target='#modalEditarMeta'><i class='fa fa-pencil'></i></button>"
                . "<button class='btn btn-xs btn-danger btnEliminarMeta' idMeta='" . (int) $meta["id"] . "'><i class='fa fa-times'></i></button>"
                . "</div>";

            $filas[] = array(
                $vendedor,
                self::formatoMonto($meta["meta_venta"]),
                self::formatoMonto($meta["venta_real"]),
                self::formatoPorcentaje($meta["venta_real"], $meta["meta_venta"]),
                $metaCobranzaTxt,
                self::formatoMonto($meta["cobranza_real"]),
                $pctCobranza,
                $botones,
            );
        }

        echo json_encode(array("data" => $filas), JSON_UNESCAPED_UNICODE);
    }
}

$activar = new TablaMetasVendedor();
$activar->mostrarTabla();
