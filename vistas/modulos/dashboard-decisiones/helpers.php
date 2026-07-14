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

if (!function_exists("ddAvanceSegmentos")) {
    function ddAvanceSegmentos($avance, $meta)
    {
        $meta = (float) $meta;

        if ($meta <= 0) {
            return array();
        }

        return array(
            array("clase" => "real", "monto" => (float) $avance["venta_real"], "titulo" => "Venta facturada"),
            array("clase" => "generado", "monto" => (float) $avance["soles_generados"], "titulo" => "Generados"),
            array("clase" => "aprobado", "monto" => (float) $avance["soles_aprobados"], "titulo" => "Aprobados"),
            array("clase" => "apt", "monto" => (float) $avance["soles_apt"], "titulo" => "APT"),
            array("clase" => "confirmado", "monto" => (float) $avance["soles_confirmados"], "titulo" => "Confirmados"),
        );
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

if (!function_exists("ddClienteLinea")) {
    function ddClienteLinea($codigo, $nombre)
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

        return $html . "</span>";
    }
}
