<?php

if (!function_exists("ddAvancePctClase")) {
    function ddAvancePctClase($pct)
    {
        $pct = (float) $pct;

        if ($pct >= 100) {
            return "ok";
        }
        if ($pct >= 80) {
            return "warn";
        }

        return "bad";
    }
}

/**
 * Color continuo 0→rojo … 100→verde para % completo de pedidos.
 */
if (!function_exists("ddPctCompletoEstilo")) {
    function ddPctCompletoEstilo($pct)
    {
        $pct = max(0, min(100, (float) $pct));
        $hue = (int) round($pct * 1.2);

        return "color: hsl(" . $hue . ", 72%, 32%);";
    }
}

if (!function_exists("ddAvanceSegmentos")) {
    function ddAvanceSegmentos($avance, $meta, $incluirGenerados = false)
    {
        $meta = (float) $meta;

        if ($meta <= 0) {
            return array();
        }

        $segmentos = array(
            array("clase" => "real", "monto" => (float) $avance["venta_real"], "titulo" => "Venta permitida"),
        );

        if ($incluirGenerados) {
            $segmentos[] = array(
                "clase" => "generado",
                "monto" => (float) $avance["soles_generados"],
                "titulo" => "Generados",
            );
        }

        $segmentos[] = array("clase" => "aprobado", "monto" => (float) $avance["soles_aprobados"], "titulo" => "Aprobados");
        $segmentos[] = array("clase" => "apt", "monto" => (float) $avance["soles_apt"], "titulo" => "APT");
        $segmentos[] = array("clase" => "confirmado", "monto" => (float) $avance["soles_confirmados"], "titulo" => "Confirmados");

        return $segmentos;
    }
}

if (!function_exists("ddAvancePipeline")) {
    function ddAvancePipeline($avance, $incluirGenerados = false)
    {
        $pipeline = (float) $avance["soles_aprobados"]
            + (float) $avance["soles_apt"]
            + (float) $avance["soles_confirmados"];

        if ($incluirGenerados) {
            $pipeline += (float) $avance["soles_generados"];
        }

        return $pipeline;
    }
}

if (!function_exists("ddEstadoBadge")) {
    function ddEstadoBadge($estado)
    {
        $map = array(
            "GENERADO" => "label-default",
            "APROBADO" => "label-warning",
            "APT" => "label-primary",
            "CONFIRMADO" => "label-info",
            "ANULADO" => "label-danger",
        );

        $clase = isset($map[$estado]) ? $map[$estado] : "label-default";

        return '<span class="label ' . $clase . ' dd-estado-badge">' . htmlspecialchars($estado) . '</span>';
    }
}

if (!function_exists("ddFormatoMonto")) {
    function ddFormatoMonto($lista, $monto)
    {
        $simbolo = ($lista === "precio1") ? "$ " : "S/ ";
        return $simbolo . number_format((float) $monto, 2);
    }
}

if (!function_exists("ddFormatoDocumento")) {
    function ddFormatoDocumento($documento)
    {
        $documento = trim((string) $documento);

        if ($documento === "") {
            return "—";
        }

        if (strlen($documento) > 4) {
            return substr($documento, 0, 4) . "-" . substr($documento, 4);
        }

        return $documento;
    }
}

if (!function_exists("ddNivelAtraso")) {
    function ddNivelAtraso($dias)
    {
        $dias = (int) $dias;

        if ($dias >= 60) {
            return array("critico", "Crítico");
        }
        if ($dias >= 30) {
            return array("alto", "Alto");
        }
        if ($dias >= 15) {
            return array("medio", "Medio");
        }

        return array("bajo", "Bajo");
    }
}

if (!function_exists("ddBadgeCategoriaCorta")) {
    function ddBadgeCategoriaCorta($categoria = null)
    {
        if (!is_array($categoria)) {
            return "";
        }

        $codigo = isset($categoria["categoria_codigo"])
            ? trim((string) $categoria["categoria_codigo"])
            : "";
        $nombre = isset($categoria["categoria_nombre"])
            ? trim((string) $categoria["categoria_nombre"])
            : "";
        $color = isset($categoria["categoria_color"])
            ? trim((string) $categoria["categoria_color"])
            : "";

        // Mapa normalizado: {codigo, nombre, color} — no confundir con filas de pedido
        if (
            $codigo === ""
            && !empty($categoria["codigo"])
            && array_key_exists("color", $categoria)
            && !array_key_exists("categoria_codigo", $categoria)
            && !isset($categoria["cod_cli"])
            && !isset($categoria["cliente"])
        ) {
            $codigo = trim((string) $categoria["codigo"]);
            $nombre = isset($categoria["nombre"]) ? trim((string) $categoria["nombre"]) : "";
            $color = isset($categoria["color"]) ? trim((string) $categoria["color"]) : "";
        }

        if ($codigo === "") {
            return "";
        }

        if (class_exists("ControladorCategoriasClientes")) {
            $hex = ControladorCategoriasClientes::ctrResolverColorCategoria($color, $codigo);
        } else {
            $hex = ($color !== "" && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $color))
                ? $color
                : "#777777";
        }

        $titulo = $nombre !== "" ? $nombre : $codigo;

        return '<span class="dd-cat-sigla" style="background-color:'
            . htmlspecialchars($hex, ENT_QUOTES, "UTF-8")
            . ';" title="'
            . htmlspecialchars($titulo, ENT_QUOTES, "UTF-8")
            . '">'
            . htmlspecialchars(strtoupper($codigo), ENT_QUOTES, "UTF-8")
            . "</span>";
    }
}

if (!function_exists("ddClienteLinea")) {
    function ddClienteLinea($codigo, $nombre, $categoria = null)
    {
        $codigo = htmlspecialchars(trim((string) $codigo));
        $nombre = htmlspecialchars(trim((string) $nombre));

        if ($codigo === "" && $nombre === "") {
            return '<span class="text-muted">—</span>';
        }

        $html = '<span class="dd-cli-linea">';

        if ($codigo !== "") {
            $html .= '<span class="dd-cod-cli dd-cod-cli--inline">' . $codigo . "</span>";
        }

        if ($nombre !== "") {
            $html .= '<span class="dd-cli-nombre">' . $nombre . "</span>";
        }

        $badge = ddBadgeCategoriaCorta($categoria);
        if ($badge !== "") {
            $html .= $badge;
        }

        return $html . "</span>";
    }
}

if (!function_exists("ddAlertaArticuloBadge")) {
    function ddAlertaArticuloBadge($alerta)
    {
        $alerta = strtolower(trim((string) $alerta));
        $map = array(
            "ambos" => array("dd-art-alerta dd-art-alerta--ambos", "Sin stock + Descont."),
            "descontinuado" => array("dd-art-alerta dd-art-alerta--descont", "Descontinuado"),
            "sin_stock" => array("dd-art-alerta dd-art-alerta--stock", "Sin stock"),
        );

        if (!isset($map[$alerta])) {
            return '<span class="dd-art-alerta">' . htmlspecialchars($alerta) . "</span>";
        }

        return '<span class="' . $map[$alerta][0] . '">' . $map[$alerta][1] . "</span>";
    }
}

if (!function_exists("ddDescripcionArticulo")) {
    function ddDescripcionArticulo($row)
    {
        $nombre = trim((string) (isset($row["nombre"]) ? $row["nombre"] : ""));

        return $nombre !== "" ? htmlspecialchars($nombre) : "—";
    }
}
