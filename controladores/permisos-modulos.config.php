<?php

/**
 * Control de acceso por módulo (Gestión comercial / Vasco Online).
 * Fuente única de IDs: controladores/permisos-modulos.json
 */

function pmRutaPermisosModulosJson()
{
    return __DIR__ . "/permisos-modulos.json";
}

function pmCargarPermisosModulos()
{
    static $config = null;
    static $intentoFallido = false;

    if ($config !== null) {
        return $config;
    }

    if ($intentoFallido) {
        return null;
    }

    $ruta = pmRutaPermisosModulosJson();

    if (!is_readable($ruta)) {
        $intentoFallido = true;
        return null;
    }

    $json = file_get_contents($ruta);
    if ($json === false || $json === "") {
        $intentoFallido = true;
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        $intentoFallido = true;
        return null;
    }

    $config = $data;
    return $config;
}

function usuarioPuedeModulo($sector, $modulo, $accion = "ver")
{
    if (!isset($_SESSION["id"])) {
        return false;
    }

    $sector = trim((string) $sector);
    $modulo = trim((string) $modulo);
    $accion = trim((string) $accion);

    if ($sector === "" || $modulo === "" || $accion === "") {
        return false;
    }

    $config = pmCargarPermisosModulos();
    if ($config === null) {
        return false;
    }

    if (
        !isset($config[$sector]) ||
        !is_array($config[$sector]) ||
        !isset($config[$sector][$modulo]) ||
        !is_array($config[$sector][$modulo]) ||
        !isset($config[$sector][$modulo][$accion]) ||
        !is_array($config[$sector][$modulo][$accion])
    ) {
        return false;
    }

    $idUsuario = (int) $_SESSION["id"];
    if ($idUsuario < 1) {
        return false;
    }

    $permitidos = array_map("intval", $config[$sector][$modulo][$accion]);

    return in_array($idUsuario, $permitidos, true);
}

function usuarioPuedeVerModulo($sector, $modulo)
{
    return usuarioPuedeModulo($sector, $modulo, "ver");
}

function usuarioPuedeAlgunaOpcionSector($sector)
{
    $sector = trim((string) $sector);
    if ($sector === "") {
        return false;
    }

    $config = pmCargarPermisosModulos();
    if ($config === null || !isset($config[$sector]) || !is_array($config[$sector])) {
        return false;
    }

    foreach ($config[$sector] as $modulo => $acciones) {
        if (!is_array($acciones)) {
            continue;
        }
        if (usuarioPuedeVerModulo($sector, $modulo)) {
            return true;
        }
    }

    return false;
}

function denegarAccesoModulo()
{
    if (!headers_sent()) {
        http_response_code(403);
    }

    echo '<div class="content-wrapper">'
        . '<section class="content">'
        . '<div class="alert alert-warning" style="margin:20px;">'
        . '<h4><i class="icon fa fa-lock"></i> Acceso denegado</h4>'
        . 'No tienes permiso para acceder a este módulo.'
        . '</div>'
        . '</section>'
        . '</div>';
}

/**
 * Compatibilidad temporal con el resto del sistema.
 * Nuevos módulos de Gestión comercial deben usar usuarioPuedeVerModulo().
 */
if (!function_exists("usuarioPuedeDashboardCobranzas")) {
    function usuarioPuedeDashboardCobranzas()
    {
        return usuarioPuedeAlgunaOpcionSector("gestion_comercial");
    }
}
