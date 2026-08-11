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

    if (function_exists("dcObtenerControlPostAprobacion") && dcObtenerControlPostAprobacion($codigo)) {
        return dcEtiquetaControlPostAprobacion($codigo);
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

function dcUsuarioPuedeLiberarControlPostAprobacion()
{
    return function_exists("usuarioPuedeModulo")
        && (
            usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "resolver")
            || usuarioPuedeModulo("gestion_comercial", "centro_decisiones", "aprobar")
        );
}

function dcUsuarioPuedeRegistrarControlPostAprobacion()
{
    return dcUsuarioPuedeLiberarControlPostAprobacion();
}

/**
 * Registra control + fila CONTROL_REGISTRADO en bitácora.
 */
function dcAplicarControlPostAprobacion(array $datos)
{
    $codigoPedido = isset($datos["codigo_pedido"]) ? (int) $datos["codigo_pedido"] : 0;
    $codigoCliente = isset($datos["codigo_cliente"]) ? trim((string) $datos["codigo_cliente"]) : "";
    $condicionCodigo = isset($datos["condicion_codigo"])
        ? strtoupper(trim((string) $datos["condicion_codigo"]))
        : "";
    $areaCodigo = isset($datos["area_autoriza_codigo"])
        ? strtoupper(trim((string) $datos["area_autoriza_codigo"]))
        : "";
    $comentario = isset($datos["comentario"]) ? trim((string) $datos["comentario"]) : "";
    $usuarioId = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : 0;
    $idAccionAprobacion = isset($datos["id_accion_aprobacion"]) ? (int) $datos["id_accion_aprobacion"] : 0;
    $pedidoTotal = isset($datos["pedido_total"]) ? $datos["pedido_total"] : null;
    $pedidoLista = isset($datos["pedido_lista"]) ? $datos["pedido_lista"] : null;
    $pedidoEstado = isset($datos["pedido_estado_resultado"]) ? $datos["pedido_estado_resultado"] : null;

    if ($codigoPedido <= 0 || $codigoCliente === "" || $usuarioId <= 0) {
        return array("ok" => false, "msg" => "Datos incompletos.");
    }

    $validacion = dcValidarDatosControlPostAprobacion($condicionCodigo, $comentario, $areaCodigo);
    if (empty($validacion["ok"])) {
        return $validacion;
    }

    $resultado = dcRegistrarControlPostAprobacion(array(
        "codigo_pedido" => $codigoPedido,
        "codigo_cliente" => $codigoCliente,
        "id_accion_aprobacion" => $idAccionAprobacion > 0 ? $idAccionAprobacion : null,
        "condicion_codigo" => $condicionCodigo,
        "area_autoriza_codigo" => $areaCodigo !== "" ? $areaCodigo : null,
        "comentario" => $comentario !== "" ? $comentario : null,
        "bloquea_apt" => isset($validacion["bloquea_apt"]) ? (int) $validacion["bloquea_apt"] : 1,
        "usuario_id" => $usuarioId,
    ));

    if (empty($resultado["ok"])) {
        return $resultado;
    }

    $detalleControl = "Control: " . dcEtiquetaControlPostAprobacion($condicionCodigo);
    if ($areaCodigo !== "") {
        $detalleControl .= " · Área: " . dcEtiquetaAreaAutorizacion($areaCodigo);
    }

    if (function_exists("dcRegistrarAccionCredito")) {
        dcRegistrarAccionCredito(array(
            "codigo_pedido" => $codigoPedido,
            "codigo_cliente" => $codigoCliente,
            "tipo_accion" => "CONTROL_REGISTRADO",
            "origen" => "centro_decisiones",
            "pedido_total" => $pedidoTotal,
            "pedido_lista" => $pedidoLista,
            "pedido_estado_resultado" => $pedidoEstado,
            "motivo_codigo" => $condicionCodigo,
            "comentario" => $comentario !== "" ? $comentario : null,
            "usuario_id" => $usuarioId,
            "detalle" => $detalleControl,
        ));
    }

    return array(
        "ok" => true,
        "msg" => "Control registrado correctamente.",
        "id" => isset($resultado["id"]) ? (int) $resultado["id"] : 0,
    );
}

function dcListarAreasAutorizacion()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["areas_autorizacion"]) && is_array($catalogo["areas_autorizacion"])
        ? $catalogo["areas_autorizacion"]
        : array();
}

function dcObtenerAreaAutorizacion($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    if ($codigo === "") {
        return null;
    }

    foreach (dcListarAreasAutorizacion() as $area) {
        if (isset($area["codigo"]) && strtoupper($area["codigo"]) === $codigo) {
            return $area;
        }
    }

    return null;
}

function dcEtiquetaAreaAutorizacion($codigo)
{
    $area = dcObtenerAreaAutorizacion($codigo);

    return $area ? $area["etiqueta"] : $codigo;
}

function dcListarControlesPostAprobacion()
{
    $catalogo = dcCargarCatalogoMotivos();

    return isset($catalogo["controles_post_aprobacion"]) && is_array($catalogo["controles_post_aprobacion"])
        ? $catalogo["controles_post_aprobacion"]
        : array();
}

function dcObtenerControlPostAprobacion($codigo)
{
    $codigo = strtoupper(trim((string) $codigo));

    if ($codigo === "") {
        return null;
    }

    foreach (dcListarControlesPostAprobacion() as $control) {
        if (isset($control["codigo"]) && strtoupper($control["codigo"]) === $codigo) {
            return $control;
        }
    }

    return null;
}

function dcEtiquetaControlPostAprobacion($codigo)
{
    $control = dcObtenerControlPostAprobacion($codigo);

    return $control ? $control["etiqueta"] : $codigo;
}

function dcControlPostAprobacionBloqueaApt($codigo)
{
    $control = dcObtenerControlPostAprobacion($codigo);

    if (!$control) {
        return true;
    }

    return !isset($control["bloquea_apt"]) || (int) $control["bloquea_apt"] === 1;
}

function dcControlPostAprobacionRequiereObservacion($codigo)
{
    $control = dcObtenerControlPostAprobacion($codigo);

    return $control && !empty($control["requiere_observacion"]);
}

function dcValidarDatosControlPostAprobacion($condicionCodigo, $comentario, $areaCodigo = "")
{
    $condicionCodigo = strtoupper(trim((string) $condicionCodigo));
    $comentario = trim((string) $comentario);
    $areaCodigo = strtoupper(trim((string) $areaCodigo));

    if ($condicionCodigo === "") {
        return array("ok" => false, "msg" => "Indica la condición del control post-aprobación.");
    }

    $control = dcObtenerControlPostAprobacion($condicionCodigo);
    if (!$control) {
        return array("ok" => false, "msg" => "Condición de control no válida.");
    }

    if (dcControlPostAprobacionRequiereObservacion($condicionCodigo) && $comentario === "") {
        return array("ok" => false, "msg" => "Esta condición requiere detalle en la observación.");
    }

    if ($areaCodigo !== "" && !dcObtenerAreaAutorizacion($areaCodigo)) {
        return array("ok" => false, "msg" => "Área de autorización no válida.");
    }

    return array(
        "ok" => true,
        "control" => $control,
        "bloquea_apt" => dcControlPostAprobacionBloqueaApt($condicionCodigo) ? 1 : 0,
    );
}

function dcRegistrarControlPostAprobacion(array $datos)
{
    if (!class_exists("ModeloDecisionesCredito")) {
        return array("ok" => false, "msg" => "Modelo no disponible.");
    }

    try {
        return ModeloDecisionesCredito::mdlRegistrarControlPostAprobacion($datos);
    } catch (Exception $e) {
        return array("ok" => false, "msg" => "No se pudo registrar el control.");
    }
}

/**
 * Texto del aviso en celda ubigeo (impresión de pedido).
 */
function dcTextoSelloControlCreditoImpresion()
{
    return "AVISAR ANTES DE FACTURAR";
}

/**
 * ¿Mostrar aviso en lugar del ubigeo? (solo si hay control post-aprobación pendiente)
 */
function dcPedidoMostrarSelloControlCreditoImpresion($codigoPedido)
{
    // Preview temporal del sello en todos los pedidos (impresión).
    // Volver a false cuando se valide el diseño.
    static $previewEnTodosLosPedidos = false;

    if ($previewEnTodosLosPedidos) {
        return true;
    }

    $codigoPedido = (int) $codigoPedido;
    if ($codigoPedido <= 0 || !class_exists("ModeloDecisionesCredito")) {
        return false;
    }

    try {
        return ModeloDecisionesCredito::mdlControlPendientePorPedido($codigoPedido) !== null;
    } catch (Exception $e) {
        return false;
    }
}

function dcHtmlSelloControlCreditoImpresionCelda($texto = null)
{
    $texto = ($texto !== null && $texto !== "") ? $texto : dcTextoSelloControlCreditoImpresion();
    $texto = function_exists("mb_strtoupper") ? mb_strtoupper($texto, "UTF-8") : strtoupper($texto);

    return '<span class="hc-print-ubigeo-sello-wrap">'
        . '<span class="hc-print-sello-ubigeo">'
        . htmlspecialchars($texto, ENT_QUOTES, "UTF-8")
        . "</span></span>";
}

function dcHtmlSelloControlCreditoImpresionDireccion($texto = null)
{
    $texto = ($texto !== null && $texto !== "") ? $texto : dcTextoSelloControlCreditoImpresion();
    $texto = function_exists("mb_strtoupper") ? mb_strtoupper($texto, "UTF-8") : strtoupper($texto);

    return '<div class="hc-print-sello-direccion">'
        . '<span class="hc-print-sello-direccion-texto">'
        . htmlspecialchars($texto, ENT_QUOTES, "UTF-8")
        . "</span></div>";
}

/**
 * Filas DIRECCIÓN + ubigeo. Con control pendiente, el aviso ocupa 2 filas a la derecha.
 */
function dcHtmlFilasDireccionImpresionPedido($codigoPedido, array $respuesta)
{
    $direccion = htmlspecialchars(isset($respuesta["direccion"]) ? (string) $respuesta["direccion"] : "", ENT_QUOTES, "UTF-8");
    $nomUbi = htmlspecialchars(isset($respuesta["nom_ubi"]) ? (string) $respuesta["nom_ubi"] : "", ENT_QUOTES, "UTF-8");
    $ubigeo = isset($respuesta["ubigeo"]) ? (string) $respuesta["ubigeo"] : "";

    if (dcPedidoMostrarSelloControlCreditoImpresion($codigoPedido)) {
        $sello = dcHtmlSelloControlCreditoImpresionDireccion();

        return '
                            <tr>
                                <th style="width:10%;text-align:left;">DIRECCIÓN:</th>
                                <td colspan="8">' . $direccion . '</td>
                                <td rowspan="2" class="hc-print-celda-sello-direccion" colspan="2">' . $sello . '</td>
                            </tr>
                            <tr>
                                <th style="width:10%"></th>
                                <td colspan="6">' . $nomUbi . '</td>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                            </tr>';
    }

    $celdaUbigeo = dcCeldaUbigeoImpresionPedido($codigoPedido, $ubigeo);

    return '
                            <tr>
                                <th style="width:10%;text-align:left;">DIRECCIÓN:</th>
                                <td colspan="10">' . $direccion . '</td>
                            </tr>
                            <tr>
                                <th style="width:10%"></th>
                                <td colspan="6">' . $nomUbi . '</td>
                                <td class="hc-print-celda-ubigeo" style="width:10%;text-align:left;" colspan="2">' . $celdaUbigeo . '</td>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                            </tr>';
}

/**
 * Contenido de la celda ubigeo: aviso con borde o ubigeo normal.
 */
function dcCeldaUbigeoImpresionPedido($codigoPedido, $ubigeo = "")
{
    if (dcPedidoMostrarSelloControlCreditoImpresion($codigoPedido)) {
        return dcHtmlSelloControlCreditoImpresionCelda();
    }

    return htmlspecialchars(trim((string) $ubigeo), ENT_QUOTES, "UTF-8");
}

/** @deprecated Usar dcCeldaUbigeoImpresionPedido */
function dcHtmlControlCreditoImpresionPedido($codigoPedido)
{
    if (!dcPedidoMostrarSelloControlCreditoImpresion($codigoPedido)) {
        return "";
    }

    return dcHtmlSelloControlCreditoImpresionCelda();
}

/** @deprecated */
function dcHtmlSelloControlCreditoImpresion($texto = null)
{
    return dcHtmlSelloControlCreditoImpresionCelda($texto);
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
        "CONTROL_REGISTRADO" => "Control registrado",
        "DESPACHO_AUTORIZADO" => "Despacho autorizado",
    );

    $tipo = strtoupper(trim((string) $tipo));

    return isset($map[$tipo]) ? $map[$tipo] : $tipo;
}

function dcClaseTipoAccion($tipo)
{
    $map = array(
        "APROBADO" => "success",
        "OBJECION" => "warning",
        "OBJECION_CERRADA" => "info",
        "ANULADO" => "danger",
        "CATEGORIA_ASIGNADA" => "info",
        "CONTROL_REGISTRADO" => "warning",
        "DESPACHO_AUTORIZADO" => "success",
    );

    $tipo = strtoupper(trim((string) $tipo));

    return isset($map[$tipo]) ? $map[$tipo] : "default";
}
