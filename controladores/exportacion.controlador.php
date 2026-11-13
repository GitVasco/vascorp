<?php

/**
 * Facturas de exportación (docs/efact/EXPORTACION-diseno.md).
 * Fase 1: configuración (constantes con valor por defecto), ficha de cliente y partidas por línea.
 * No modifica ni reemplaza el flujo actual de facturación (serie FX en ControladorFacturacion).
 */
class ControladorExportacion
{
    /** Acceso: quien tiene el módulo de facturación. */
    public static function puedeUsar()
    {
        if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
            return false;
        }
        return isset($_SESSION["facturacion"]) && (string) $_SESSION["facturacion"] === "1";
    }

    /**
     * Configuración vigente. Se puede fijar en config.php con
     * EXPORTACION_SERIE, EXPORTACION_LISTA y EXPORTACION_ALMACEN.
     * Sin EXPORTACION_SERIE se usa la única serie FX de talonariosjf; si hay varias, queda vacía.
     */
    public static function config()
    {
        $series = ModeloExportacion::mdlSeriesFX();
        if (defined("EXPORTACION_SERIE")) {
            $serie = EXPORTACION_SERIE;
        } else {
            $serie = count($series) === 1 ? $series[0]["serie"] : "";
        }

        $avisos = array();
        if (!count($series)) {
            $avisos[] = "No hay series FX en talonariosjf.";
        } elseif ($serie === "") {
            $avisos[] = "Hay varias series FX: definir EXPORTACION_SERIE en config.php.";
        } else {
            $existe = false;
            foreach ($series as $s) {
                if ($s["serie"] === $serie) {
                    $existe = true;
                }
            }
            if (!$existe) {
                $avisos[] = "La serie " . $serie . " no existe en talonariosjf.";
            }
        }

        return array(
            "serie" => $serie,
            "series_fx" => $series,
            "lista" => defined("EXPORTACION_LISTA") ? EXPORTACION_LISTA : "precio1",
            "almacen" => defined("EXPORTACION_ALMACEN") ? EXPORTACION_ALMACEN : "01",
            "moneda" => "USD",
            "avisos" => $avisos,
        );
    }

    private static function t($v, $max = 0)
    {
        $v = trim(preg_replace('/[\r\n\t,]+/', ' ', (string) $v));
        $v = preg_replace('/\s{2,}/', ' ', $v);
        if ($max > 0 && function_exists("mb_substr")) {
            $v = mb_substr($v, 0, $max, "UTF-8");
        }
        return $v;
    }

    private static function usuario()
    {
        return isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
    }

    /** Países para el selector de Clientes (ISO 3166-1 alfa-2 => nombre). Vacío = Perú. */
    public static function paises()
    {
        return array(
            "AR" => "ARGENTINA", "BO" => "BOLIVIA", "BR" => "BRASIL", "CA" => "CANADÁ", "CL" => "CHILE",
            "CN" => "CHINA", "CO" => "COLOMBIA", "CR" => "COSTA RICA", "CU" => "CUBA", "DE" => "ALEMANIA",
            "DO" => "REPÚBLICA DOMINICANA", "EC" => "ECUADOR", "ES" => "ESPAÑA", "FR" => "FRANCIA",
            "GB" => "REINO UNIDO", "GT" => "GUATEMALA", "HN" => "HONDURAS", "IT" => "ITALIA", "JP" => "JAPÓN",
            "MX" => "MÉXICO", "NI" => "NICARAGUA", "NL" => "PAÍSES BAJOS", "PA" => "PANAMÁ", "PT" => "PORTUGAL",
            "PY" => "PARAGUAY", "SV" => "EL SALVADOR", "US" => "ESTADOS UNIDOS", "UY" => "URUGUAY",
            "VE" => "VENEZUELA",
        );
    }

    public static function nombrePais($iso)
    {
        $p = self::paises();
        return isset($p[$iso]) ? $p[$iso] : (string) $iso;
    }

    /**
     * Guarda el país del cliente desde la pantalla Clientes, después de que su alta o edición salió bien.
     * Solo actúa si el formulario envió el campo y la columna existe; vacío o PE = Perú (NULL).
     */
    public static function ctrGuardarPaisCliente($codigo, $campoPost)
    {
        if (!isset($_POST[$campoPost]) || !ModeloExportacion::mdlTienePais()) {
            return false;
        }
        $iso = strtoupper(trim((string) $_POST[$campoPost]));
        if ($iso === "" || $iso === "PE") {
            $iso = null;
        } elseif (!array_key_exists($iso, self::paises())) {
            return false;
        }
        return ModeloExportacion::mdlGuardarPaisCliente(trim((string) $codigo), $iso);
    }

    /** Editar precio de lista: mismo permiso que Maestros → Modelos (ficha_modelos → precios). */
    public static function puedeEditarPrecios()
    {
        return function_exists("usuarioPuedeModulo")
            && usuarioPuedeModulo("gestion_comercial", "ficha_modelos", "precios");
    }

    public static function ctrGuardarPrecio1($in)
    {
        if (!self::puedeEditarPrecios()) {
            return array("ok" => false, "msg" => "Sin permiso para editar precios de lista.");
        }
        $modelo = self::t(isset($in["modelo"]) ? $in["modelo"] : "", 20);
        $txt = str_replace(",", ".", trim(isset($in["precio"]) ? (string) $in["precio"] : ""));
        if ($modelo === "" || !ModeloExportacion::mdlModeloActivo($modelo)) {
            return array("ok" => false, "msg" => "Modelo no válido o no activo.");
        }
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $txt)) {
            return array("ok" => false, "msg" => "Precio no válido (máximo 2 decimales).");
        }
        $precio = number_format((float) $txt, 2, ".", "");
        if (!ModeloExportacion::mdlTieneFilaPrecio($modelo)) {
            return array("ok" => false, "msg" => "El modelo no tiene registro de precios; créelo en Maestros → Modelos.");
        }
        $anterior = ModeloExportacion::mdlPrecio1Actual($modelo);
        try {
            $ok = ModeloExportacion::mdlGuardarPrecio1($modelo, $precio);
        } catch (Exception $e) {
            $ok = false;
        }
        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo guardar el precio.");
        }
        $guardado = ModeloExportacion::mdlPrecio1Actual($modelo);
        if ($guardado === null || abs($guardado - (float) $precio) > 0.004) {
            if ($anterior !== null) {
                ModeloExportacion::mdlGuardarPrecio1($modelo, number_format($anterior, 4, ".", ""));
            }
            return array("ok" => false, "msg" => "La base no aceptó el precio " . $precio . " (quedó " . ($guardado === null ? "vacío" : $guardado) . "); se restauró el anterior. Revisar el tipo de la columna preciojf.precio1.");
        }
        return array("ok" => true, "precio" => $precio);
    }

    public static function ctrGuardarPartida($in)
    {
        $d = array(
            "linea" => self::t(isset($in["linea"]) ? $in["linea"] : "", 30),
            "partida" => preg_replace('/[^0-9]/', '', isset($in["partida"]) ? (string) $in["partida"] : ""),
            "descripcion" => self::t(isset($in["descripcion"]) ? $in["descripcion"] : "", 150),
            "activo" => !empty($in["activo"]) && (string) $in["activo"] !== "0" ? 1 : 0,
        );

        if ($d["linea"] === "") {
            return array("ok" => false, "msg" => "Falta la línea.");
        }
        if ($d["partida"] !== "" && strlen($d["partida"]) !== 10) {
            return array("ok" => false, "msg" => "La partida arancelaria debe tener 10 dígitos.");
        }
        if ($d["partida"] === "") {
            $d["activo"] = 0;
        }

        $ok = ModeloExportacion::mdlGuardarPartida($d, self::usuario());
        return $ok ? array("ok" => true) : array("ok" => false, "msg" => "No se pudo guardar la partida.");
    }
}
