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
            "motivos_aprobacion" => array(),
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
            "motivos_aprobacion" => array(),
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

function dcListarMotivosAprobacion()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["motivos_aprobacion"]) && is_array($catalogo["motivos_aprobacion"])
        ? $catalogo["motivos_aprobacion"]
        : array();
}

function dcObtenerMotivoAprobacion($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    if ($codigo === "") {
        return null;
    }

    foreach (dcListarMotivosAprobacion() as $motivo) {
        if (isset($motivo["codigo"]) && strtoupper($motivo["codigo"]) === $codigo) {
            return $motivo;
        }
    }

    return null;
}

function dcEtiquetaMotivoAprobacion($codigo)
{
    $motivo = dcObtenerMotivoAprobacion($codigo);

    return $motivo ? $motivo["etiqueta"] : $codigo;
}

/**
 * Condiciones de venta tratadas como pago inmediato (contado / contra entrega).
 */
function dcCodigosCondicionContado()
{
    return array("01", "02");
}

/**
 * ¿La condición de venta es al contado / contra entrega?
 * Acepta código ("01") o descripción ("CONTADO").
 */
function dcEsCondicionContado($codigoODescripcion)
{
    $valor = strtoupper(trim((string) $codigoODescripcion));

    if ($valor === "") {
        return false;
    }

    if (in_array($valor, dcCodigosCondicionContado(), true)) {
        return true;
    }

    if (strpos($valor, "CONTADO") !== false || strpos($valor, "CONTRA ENTREGA") !== false) {
        return true;
    }

    return false;
}

/**
 * Filtra motivos de aprobación según contexto: credito | contado.
 * Sin campo "aplica" → válido en ambos.
 */
function dcListarMotivosAprobacionPorContexto($contexto = "credito")
{
    $contexto = strtolower(trim((string) $contexto));
    if ($contexto !== "contado") {
        $contexto = "credito";
    }

    $salida = array();
    foreach (dcListarMotivosAprobacion() as $motivo) {
        if (!is_array($motivo) || empty($motivo["codigo"])) {
            continue;
        }

        if (empty($motivo["aplica"]) || !is_array($motivo["aplica"])) {
            $salida[] = $motivo;
            continue;
        }

        $aplica = array();
        foreach ($motivo["aplica"] as $item) {
            $aplica[] = strtolower(trim((string) $item));
        }

        if (in_array($contexto, $aplica, true)) {
            $salida[] = $motivo;
        }
    }

    return $salida;
}

/**
 * Etiqueta para bitácora: prueba motivos de no aprobación y de aprobación.
 */
function dcEtiquetaMotivoAccion($codigo)
{
    $codigo = trim((string) $codigo);

    if ($codigo === "") {
        return "";
    }

    if (dcObtenerMotivo($codigo)) {
        return dcEtiquetaMotivo($codigo);
    }

    if (dcObtenerMotivoAprobacion($codigo)) {
        return dcEtiquetaMotivoAprobacion($codigo);
    }

    return $codigo;
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

function dcUsuarioPuedeVerHistorialCredito()
{
    return function_exists("usuarioPuedeModulo")
        && usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "historial");
}

/**
 * Registra una acción en decision_credito_accionjf.
 * No interrumpe el flujo principal si falla el insert.
 */
function dcRegistrarAccionCredito(array $datos)
{
    if (!class_exists("ModeloDecisionesCredito")) {
        return false;
    }

    try {
        return ModeloDecisionesCredito::mdlRegistrarAccion($datos);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Snapshot de categoría + línea/cupo al momento de la acción.
 * Usa cupo de línea de crédito (cliente o grupo) y scores del último estado guardado.
 */
function dcArmarSnapshotAccionCredito($codigoCliente)
{
    $codigoCliente = trim((string) $codigoCliente);
    $vacio = array();

    if ($codigoCliente === "") {
        return $vacio;
    }

    $out = array();

    // Categoría efectiva (cliente o grupo)
    if (class_exists("ControladorCategoriasClientes")) {
        try {
            $efectiva = ControladorCategoriasClientes::ctrObtenerCategoriaEfectivaCliente($codigoCliente);
            if (!empty($efectiva["tiene_categoria"]) && !empty($efectiva["categoria"])) {
                $cat = $efectiva["categoria"];
                $out["id_categoria"] = isset($cat["id"]) ? (int) $cat["id"] : null;
                $out["categoria_codigo"] = isset($cat["codigo"]) ? $cat["codigo"] : null;
                $out["categoria_nombre"] = isset($cat["nombre"]) ? $cat["nombre"] : null;
                $out["categoria_entidad"] = isset($efectiva["origen_asignacion"])
                    ? $efectiva["origen_asignacion"]
                    : null;
                if (!empty($efectiva["codigo_grupo"])) {
                    $out["categoria_codigo_entidad"] = $efectiva["codigo_grupo"];
                    $out["codigo_grupo"] = $efectiva["codigo_grupo"];
                    $out["nombre_grupo"] = isset($efectiva["nombre_grupo"])
                        ? $efectiva["nombre_grupo"]
                        : null;
                } else {
                    $out["categoria_codigo_entidad"] = $codigoCliente;
                }
            } elseif (!empty($efectiva["codigo_grupo"])) {
                $out["codigo_grupo"] = $efectiva["codigo_grupo"];
                $out["nombre_grupo"] = isset($efectiva["nombre_grupo"])
                    ? $efectiva["nombre_grupo"]
                    : null;
                $out["categoria_entidad"] = "grupo";
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    // Línea / cupo (cliente o grupo)
    if (class_exists("ControladorLineaCredito")) {
        try {
            $ref = ControladorLineaCredito::ctrReferenciaCupoPedido($codigoCliente);
            if (is_array($ref)) {
                $out["cupo_modo"] = isset($ref["modo"]) ? $ref["modo"] : null;
                $out["linea_aprobada"] = isset($ref["linea_aprobada"]) ? $ref["linea_aprobada"] : null;
                $out["linea_recomendada"] = isset($ref["linea_recomendada"]) ? $ref["linea_recomendada"] : null;
                $out["linea_referencia"] = isset($ref["linea_referencia"]) ? $ref["linea_referencia"] : null;
                $out["deuda_actual"] = isset($ref["deuda_actual"]) ? $ref["deuda_actual"] : null;
                $out["cupo_disponible"] = isset($ref["cupo_disponible"]) ? $ref["cupo_disponible"] : null;
                $out["utilizacion_pct"] = isset($ref["utilizacion_pct"]) ? $ref["utilizacion_pct"] : null;
                $out["etiqueta_linea"] = isset($ref["etiqueta_linea"]) ? $ref["etiqueta_linea"] : null;

                if (!empty($ref["grupo"]) && is_array($ref["grupo"])) {
                    $out["codigo_grupo"] = isset($ref["grupo"]["codigo"])
                        ? $ref["grupo"]["codigo"]
                        : (isset($out["codigo_grupo"]) ? $out["codigo_grupo"] : null);
                    $out["nombre_grupo"] = isset($ref["grupo"]["nombre"])
                        ? $ref["grupo"]["nombre"]
                        : (isset($out["nombre_grupo"]) ? $out["nombre_grupo"] : null);
                }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    // Scores del último estado en línea de crédito (sin recalcular IC completo)
    if (class_exists("ModeloLineaCredito")) {
        try {
            $modo = isset($out["cupo_modo"]) ? $out["cupo_modo"] : "cliente";
            $filaScore = null;

            if ($modo === "grupo" && !empty($out["codigo_grupo"])) {
                $filaScore = ModeloLineaCredito::mdlGrupoLinea($out["codigo_grupo"]);
            } else {
                $filaScore = ModeloLineaCredito::mdlClienteLinea($codigoCliente);
            }

            if (is_array($filaScore)) {
                if (isset($filaScore["score_riesgo"]) && $filaScore["score_riesgo"] !== null && $filaScore["score_riesgo"] !== "") {
                    $out["score_riesgo"] = round((float) $filaScore["score_riesgo"], 2);
                }
                if (isset($filaScore["score_comercial"]) && $filaScore["score_comercial"] !== null && $filaScore["score_comercial"] !== "") {
                    $out["score_comercial"] = round((float) $filaScore["score_comercial"], 2);
                }
                if (isset($filaScore["score_fidelidad"]) && $filaScore["score_fidelidad"] !== null && $filaScore["score_fidelidad"] !== "") {
                    $out["score_fidelidad"] = round((float) $filaScore["score_fidelidad"], 2);
                }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    // Resumen legible
    $partes = array();
    if (!empty($out["categoria_codigo"])) {
        $partes[] = "Cat. " . $out["categoria_codigo"]
            . (!empty($out["categoria_nombre"]) ? " (" . $out["categoria_nombre"] . ")" : "");
    }
    if (!empty($out["cupo_modo"]) && $out["cupo_modo"] === "grupo" && !empty($out["nombre_grupo"])) {
        $partes[] = "Grupo " . $out["nombre_grupo"];
    }
    if (isset($out["linea_referencia"]) && $out["linea_referencia"] !== null) {
        $etiq = !empty($out["etiqueta_linea"]) ? $out["etiqueta_linea"] : "Línea";
        $partes[] = $etiq . " S/ " . number_format((float) $out["linea_referencia"], 0);
    }
    if (isset($out["cupo_disponible"]) && $out["cupo_disponible"] !== null) {
        $partes[] = "Disp. S/ " . number_format((float) $out["cupo_disponible"], 0);
    }
    if (isset($out["deuda_actual"]) && $out["deuda_actual"] !== null) {
        $partes[] = "Deuda S/ " . number_format((float) $out["deuda_actual"], 0);
    }
    if (!empty($partes)) {
        $out["detalle"] = implode(" · ", $partes);
    }

    return $out;
}

function dcEtiquetaTipoAccion($tipo)
{
    $map = array(
        "APROBADO" => "Aprobado",
        "OBJECION" => "Objeción",
        "OBJECION_CERRADA" => "Objeción cerrada",
        "ANULADO" => "Anulado",
        "CATEGORIA_ASIGNADA" => "Categoría asignada",
    );

    $tipo = strtoupper(trim((string) $tipo));

    return isset($map[$tipo]) ? $map[$tipo] : $tipo;
}

function dcClaseTipoAccion($tipo)
{
    $map = array(
        "APROBADO" => "success",
        "OBJECION" => "danger",
        "OBJECION_CERRADA" => "warning",
        "ANULADO" => "default",
        "CATEGORIA_ASIGNADA" => "info",
    );

    $tipo = strtoupper(trim((string) $tipo));

    return isset($map[$tipo]) ? $map[$tipo] : "default";
}
