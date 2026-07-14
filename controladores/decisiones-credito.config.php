<?php

function dcRutaCatalogoMotivos()
{
    return __DIR__ . "/creditos-motivos.config.json";
}

function dcCargarCatalogoMotivos()
{
    static $catalogo = null;

    if ($catalogo !== null) {
        return $catalogo;
    }

    $ruta = dcRutaCatalogoMotivos();

    if (!is_readable($ruta)) {
        return array(
            "version" => "1.0",
            "motivos" => array(),
            "tipos_solicitud" => array(),
            "resoluciones_posibles" => array(),
        );
    }

    $json = file_get_contents($ruta);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return array(
            "version" => "1.0",
            "motivos" => array(),
            "tipos_solicitud" => array(),
            "resoluciones_posibles" => array(),
        );
    }

    $catalogo = $data;

    return $catalogo;
}

function dcListarMotivos()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["motivos"]) && is_array($catalogo["motivos"])
        ? $catalogo["motivos"]
        : array();
}

function dcObtenerMotivo($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    foreach (dcListarMotivos() as $motivo) {
        if (isset($motivo["codigo"]) && strtoupper($motivo["codigo"]) === $codigo) {
            return $motivo;
        }
    }

    return null;
}

function dcListarTiposSolicitud()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["tipos_solicitud"]) && is_array($catalogo["tipos_solicitud"])
        ? $catalogo["tipos_solicitud"]
        : array();
}

function dcObtenerTipoSolicitud($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    foreach (dcListarTiposSolicitud() as $tipo) {
        if (isset($tipo["codigo"]) && strtoupper($tipo["codigo"]) === $codigo) {
            return $tipo;
        }
    }

    return null;
}

function dcSolicitudesPermitidasPorMotivo($motivoCodigo)
{
    $motivo = dcObtenerMotivo($motivoCodigo);

    if (!$motivo || empty($motivo["solicitudes_permitidas"]) || !is_array($motivo["solicitudes_permitidas"])) {
        return array();
    }

    return $motivo["solicitudes_permitidas"];
}

function dcListarResoluciones()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["resoluciones_posibles"]) && is_array($catalogo["resoluciones_posibles"])
        ? $catalogo["resoluciones_posibles"]
        : array();
}

function dcObtenerResolucion($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    foreach (dcListarResoluciones() as $resolucion) {
        if (isset($resolucion["codigo"]) && strtoupper($resolucion["codigo"]) === $codigo) {
            return $resolucion;
        }
    }

    return null;
}

function dcEtiquetaMotivo($codigo)
{
    $motivo = dcObtenerMotivo($codigo);

    return $motivo ? $motivo["etiqueta"] : $codigo;
}

function dcEtiquetaSolicitud($codigo)
{
    $tipo = dcObtenerTipoSolicitud($codigo);

    return $tipo ? $tipo["etiqueta"] : $codigo;
}

function dcEtiquetaResolucion($codigo)
{
    $resolucion = dcObtenerResolucion($codigo);

    return $resolucion ? $resolucion["etiqueta"] : $codigo;
}

function dcSeveridadClase($severidad)
{
    $map = array(
        "critica" => "danger",
        "alta" => "danger",
        "media" => "warning",
        "baja" => "info",
    );

    $key = strtolower(trim((string) $severidad));

    return isset($map[$key]) ? $map[$key] : "default";
}

function dcUsuarioPuedeRegistrarDecision()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "registrar");
}

function dcUsuarioPuedeSolicitar()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "solicitar");
}

function dcUsuarioPuedeResolver()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "resolver");
}

function dcUsuarioPuedeAnularPedido()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "anular");
}

function dcUsuarioPuedeAprobarPedido()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "aprobar");
}
