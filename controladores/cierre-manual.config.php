<?php

/**
 * Catálogo de tipos de falla del cierre manual de servicios.
 * Fuente: controladores/cierre-manual-fallas.json
 */

if (!function_exists("cmCargarCatalogo")) {
    function cmCargarCatalogo()
    {
        static $catalogo = null;
        if ($catalogo !== null) {
            return $catalogo;
        }
        $json = @file_get_contents(__DIR__ . "/cierre-manual-fallas.json");
        $datos = $json !== false ? json_decode($json, true) : null;
        $catalogo = is_array($datos) ? $datos : array("fallas" => array(), "motivos_anteriores" => array());
        return $catalogo;
    }

    function cmFallas()
    {
        $c = cmCargarCatalogo();
        return isset($c["fallas"]) && is_array($c["fallas"]) ? $c["fallas"] : array();
    }

    function cmFallaValida($codigo)
    {
        foreach (cmFallas() as $f) {
            if ((string) $f["codigo"] === (string) $codigo) {
                return true;
            }
        }
        return false;
    }

    // Registros antiguos guardaban un motivo (PERDIDA, DEFECTO...) en vez del código de falla
    function cmNombreFalla($codigo)
    {
        foreach (cmFallas() as $f) {
            if ((string) $f["codigo"] === (string) $codigo) {
                return $f["codigo"] . " - " . $f["nombre"];
            }
        }
        $c = cmCargarCatalogo();
        if (isset($c["motivos_anteriores"][$codigo])) {
            return $c["motivos_anteriores"][$codigo];
        }
        return (string) $codigo;
    }
}
