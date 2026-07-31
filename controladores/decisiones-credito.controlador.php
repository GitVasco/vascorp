<?php

class ControladorDecisionesCredito
{
    public static function ctrCatalogo()
    {
        return array(
            "motivos" => dcListarMotivos(),
            "motivos_aprobacion" => dcListarMotivosAprobacion(),
            "areas_autorizacion" => dcListarAreasAutorizacion(),
            "controles_post_aprobacion" => dcListarControlesPostAprobacion(),
            "tipos_solicitud" => dcListarTiposSolicitud(),
            "resoluciones" => dcListarResoluciones(),
            "permisos" => array(
                "registrar" => dcUsuarioPuedeRegistrarDecision(),
                "solicitar" => dcUsuarioPuedeSolicitar(),
                "resolver" => dcUsuarioPuedeResolver(),
                "anular" => dcUsuarioPuedeAnularPedido(),
                "aprobar" => dcUsuarioPuedeAprobarPedido(),
                "liberar_control" => dcUsuarioPuedeLiberarControlPostAprobacion(),
                "registrar_control" => dcUsuarioPuedeRegistrarControlPostAprobacion(),
            ),
        );
    }

    public static function ctrEstadoPedido($codigoPedido, $codigoCliente = "")
    {
        $codigoPedido = (int) $codigoPedido;
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoPedido <= 0) {
            return array("ok" => false, "msg" => "Pedido no indicado.");
        }

        $pedido = ModeloDecisionesCredito::mdlPedidoExiste($codigoPedido, $codigoCliente);

        if (!$pedido) {
            return array("ok" => false, "msg" => "Pedido no encontrado.");
        }

        if (!empty($pedido["ultima_compra"])) {
            $tsUltima = strtotime($pedido["ultima_compra"]);
            $pedido["ultima_compra_fmt"] = $tsUltima ? date("d/m/Y", $tsUltima) : null;
        } else {
            $pedido["ultima_compra_fmt"] = null;
        }

        $pedido["docs_vencidos"] = isset($pedido["docs_vencidos"]) ? (int) $pedido["docs_vencidos"] : 0;

        $decision = ModeloDecisionesCredito::mdlDecisionVigentePorPedido($codigoPedido);
        $decision = $decision ? ModeloDecisionesCredito::mdlEnriquecerDecision($decision) : null;

        $solicitudes = array();
        if ($decision) {
            $raw = ModeloDecisionesCredito::mdlSolicitudesPorDecision((int) $decision["id"]);
            foreach ($raw as $item) {
                $solicitudes[] = ModeloDecisionesCredito::mdlEnriquecerSolicitud($item);
            }
        }

        $eventos = ModeloDecisionesCredito::mdlEnriquecerEventos(
            ModeloDecisionesCredito::mdlEventosPorPedido($codigoPedido)
        );

        $clienteCodigo = $codigoCliente !== "" ? $codigoCliente : (string) $pedido["cliente"];

        return self::ctrAdjuntarInteligencia(array(
            "ok" => true,
            "pedido" => $pedido,
            "decision" => $decision,
            "solicitudes" => $solicitudes,
            "eventos" => $eventos,
            "catalogo" => self::ctrCatalogo(),
            "motivos" => dcListarMotivos(),
        ), $clienteCodigo, (string) $codigoPedido);
    }

    private static function ctrAdjuntarInteligencia(array $respuesta, $codigoCliente, $codigoPedido)
    {
        $inteligencia = null;

        if ($codigoCliente !== "" && class_exists("ControladorDashboardDecisiones")) {
            $ic = ControladorDashboardDecisiones::ctrMiniInteligenciaCliente($codigoCliente, $codigoPedido);
            if (!empty($ic["ok"])) {
                $inteligencia = $ic;
            }
        }

        if ($inteligencia && !empty($respuesta["pedido"])) {
            $pedido = $respuesta["pedido"];

            if (isset($inteligencia["riesgo"])) {
                $docsIc = isset($inteligencia["riesgo"]["docs_vencidos"])
                    ? (int) $inteligencia["riesgo"]["docs_vencidos"]
                    : 0;
                $docsCartera = (int) $pedido["docs_vencidos"];
                $inteligencia["riesgo"]["docs_vencidos"] = max($docsIc, $docsCartera);
            }

            if (isset($inteligencia["comercial"])) {
                if (!empty($pedido["ultima_compra_fmt"])) {
                    $inteligencia["comercial"]["ultima_compra"] = $pedido["ultima_compra_fmt"];
                } elseif (!empty($inteligencia["comercial"]["ultima_compra"])) {
                    $tsIc = strtotime($inteligencia["comercial"]["ultima_compra"]);
                    $inteligencia["comercial"]["ultima_compra"] = $tsIc
                        ? date("d/m/Y", $tsIc)
                        : $inteligencia["comercial"]["ultima_compra"];
                }
            }
        }

        $respuesta["inteligencia"] = $inteligencia;
        $respuesta["url_completo"] = $inteligencia && !empty($inteligencia["url_completo"])
            ? $inteligencia["url_completo"]
            : ("index.php?ruta=inteligencia-comercial&cliente=" . urlencode($codigoCliente));

        return $respuesta;
    }

    public static function ctrRegistrarNoAprobacion()
    {
        if (!dcUsuarioPuedeRegistrarDecision()) {
            return array("ok" => false, "msg" => "Sin permiso para registrar decisiones de crédito.");
        }

        $codigoPedido = isset($_POST["codigo_pedido"]) ? (int) $_POST["codigo_pedido"] : 0;
        $codigoCliente = isset($_POST["codigo_cliente"]) ? trim((string) $_POST["codigo_cliente"]) : "";
        $motivoCodigo = isset($_POST["motivo_codigo"]) ? strtoupper(trim((string) $_POST["motivo_codigo"])) : "";
        $comentario = isset($_POST["comentario"]) ? trim((string) $_POST["comentario"]) : "";

        if ($codigoPedido <= 0 || $codigoCliente === "" || $motivoCodigo === "") {
            return array("ok" => false, "msg" => "Complete pedido, cliente y motivo.");
        }

        $motivo = dcObtenerMotivo($motivoCodigo);

        if (!$motivo) {
            return array("ok" => false, "msg" => "Motivo no válido.");
        }

        if (!empty($motivo["requiere_comentario"]) && $comentario === "") {
            return array("ok" => false, "msg" => "Este motivo requiere comentario.");
        }

        $pedido = ModeloDecisionesCredito::mdlPedidoExiste($codigoPedido, $codigoCliente);

        if (!$pedido) {
            return array("ok" => false, "msg" => "Pedido no encontrado para ese cliente.");
        }

        if (strtoupper((string) $pedido["estado"]) !== "GENERADO") {
            return array("ok" => false, "msg" => "Solo se registran motivos en pedidos GENERADO.");
        }

        $resultado = ModeloDecisionesCredito::mdlRegistrarNoAprobacion(array(
            "codigo_pedido" => $codigoPedido,
            "codigo_cliente" => $codigoCliente,
            "motivo_codigo" => $motivoCodigo,
            "comentario" => $comentario,
            "usuario_registro" => (int) $_SESSION["id"],
        ));

        if (empty($resultado["ok"])) {
            return array("ok" => false, "msg" => isset($resultado["msg"]) ? $resultado["msg"] : "Error al registrar.");
        }

        if (function_exists("dcRegistrarAccionCredito")) {
            dcRegistrarAccionCredito(array(
                "codigo_pedido" => $codigoPedido,
                "codigo_cliente" => $codigoCliente,
                "tipo_accion" => "OBJECION",
                "origen" => "centro_decisiones",
                "pedido_total" => isset($pedido["total"]) ? $pedido["total"] : null,
                "pedido_lista" => isset($pedido["lista"]) ? $pedido["lista"] : null,
                "pedido_estado_resultado" => "GENERADO",
                "id_decision" => isset($resultado["id"]) ? (int) $resultado["id"] : 0,
                "motivo_codigo" => $motivoCodigo,
                "comentario" => $comentario,
                "usuario_id" => (int) $_SESSION["id"],
                "detalle" => function_exists("dcEtiquetaMotivo")
                    ? dcEtiquetaMotivo($motivoCodigo)
                    : $motivoCodigo,
            ));
        }

        return self::ctrEstadoPedido($codigoPedido, $codigoCliente);
    }

    public static function ctrCrearSolicitud()
    {
        if (!dcUsuarioPuedeSolicitar()) {
            return array("ok" => false, "msg" => "Sin permiso para crear solicitudes.");
        }

        $idDecision = isset($_POST["id_decision"]) ? (int) $_POST["id_decision"] : 0;
        $tipoSolicitud = isset($_POST["tipo_solicitud"]) ? strtoupper(trim((string) $_POST["tipo_solicitud"])) : "";
        $justificacion = isset($_POST["justificacion"]) ? trim((string) $_POST["justificacion"]) : "";

        if ($idDecision <= 0 || $tipoSolicitud === "" || $justificacion === "") {
            return array("ok" => false, "msg" => "Complete tipo de solicitud y justificación.");
        }

        $tipo = dcObtenerTipoSolicitud($tipoSolicitud);

        if (!$tipo) {
            return array("ok" => false, "msg" => "Tipo de solicitud no válido.");
        }

        if (!empty($tipo["requiere_justificacion"]) && strlen($justificacion) < 10) {
            return array("ok" => false, "msg" => "La justificación debe tener al menos 10 caracteres.");
        }

        $decision = ModeloDecisionesCredito::mdlDecisionPorId($idDecision);

        if (!$decision || $decision["estado"] !== "VIGENTE") {
            return array("ok" => false, "msg" => "No hay una decisión vigente para solicitar.");
        }

        $permitidas = dcSolicitudesPermitidasPorMotivo($decision["motivo_codigo"]);

        if (!in_array($tipoSolicitud, $permitidas, true)) {
            return array("ok" => false, "msg" => "Ese tipo de solicitud no aplica al motivo registrado.");
        }

        $resultado = ModeloDecisionesCredito::mdlCrearSolicitud(array(
            "id_decision" => $idDecision,
            "codigo_pedido" => (int) $decision["codigo_pedido"],
            "codigo_cliente" => $decision["codigo_cliente"],
            "tipo_solicitud" => $tipoSolicitud,
            "justificacion" => $justificacion,
            "usuario_solicita" => (int) $_SESSION["id"],
        ));

        if (empty($resultado["ok"])) {
            return array("ok" => false, "msg" => isset($resultado["msg"]) ? $resultado["msg"] : "Error al crear solicitud.");
        }

        return self::ctrEstadoPedido((int) $decision["codigo_pedido"], $decision["codigo_cliente"]);
    }

    public static function ctrResolverSolicitud()
    {
        if (!dcUsuarioPuedeResolver()) {
            return array("ok" => false, "msg" => "Sin permiso para resolver solicitudes.");
        }

        $idSolicitud = isset($_POST["id_solicitud"]) ? (int) $_POST["id_solicitud"] : 0;
        $estado = isset($_POST["estado"]) ? strtoupper(trim((string) $_POST["estado"])) : "";
        $resolucionCodigo = isset($_POST["resolucion_codigo"]) ? strtoupper(trim((string) $_POST["resolucion_codigo"])) : "";
        $comentario = isset($_POST["comentario_resolucion"]) ? trim((string) $_POST["comentario_resolucion"]) : "";
        $motivoObservacion = isset($_POST["motivo_observacion_codigo"])
            ? strtoupper(trim((string) $_POST["motivo_observacion_codigo"]))
            : "";

        if ($idSolicitud <= 0 || !in_array($estado, array("APROBADA", "RECHAZADA"), true) || $resolucionCodigo === "") {
            return array("ok" => false, "msg" => "Datos de resolución incompletos.");
        }

        if (!dcObtenerResolucion($resolucionCodigo)) {
            return array("ok" => false, "msg" => "Resolución no válida.");
        }

        if ($motivoObservacion !== "" && !dcObtenerMotivoAprobacion($motivoObservacion)) {
            return array("ok" => false, "msg" => "Motivo de observación no válido.");
        }

        $solicitud = ModeloDecisionesCredito::mdlSolicitudPorId($idSolicitud);

        if (!$solicitud) {
            return array("ok" => false, "msg" => "Solicitud no encontrada.");
        }

        $resultado = ModeloDecisionesCredito::mdlResolverSolicitud(array(
            "id" => $idSolicitud,
            "estado" => $estado,
            "resolucion_codigo" => $resolucionCodigo,
            "comentario_resolucion" => $comentario,
            "motivo_observacion_codigo" => $motivoObservacion !== "" ? $motivoObservacion : null,
            "codigo_pedido" => (int) $solicitud["codigo_pedido"],
            "codigo_cliente" => $solicitud["codigo_cliente"],
            "usuario_resuelve" => (int) $_SESSION["id"],
        ));

        if (empty($resultado["ok"])) {
            return array("ok" => false, "msg" => isset($resultado["msg"]) ? $resultado["msg"] : "Error al resolver.");
        }

        if ($estado === "APROBADA" && in_array($resolucionCodigo, array("DECISION_REVERTIDA", "APROBADO_EXCEPCION", "APROBADO_REEVALUACION", "LINEA_AJUSTADA"), true)) {
            ModeloDecisionesCredito::mdlCerrarDecision(array(
                "id" => (int) $solicitud["id_decision"],
                "resolucion_codigo" => $resolucionCodigo,
                "resolucion_comentario" => $comentario,
                "codigo_pedido" => (int) $solicitud["codigo_pedido"],
                "codigo_cliente" => $solicitud["codigo_cliente"],
                "usuario_resolucion" => (int) $_SESSION["id"],
            ));

            if (function_exists("dcRegistrarAccionCredito")) {
                $detalleCierre = "Solicitud resuelta: " . $resolucionCodigo;
                if ($motivoObservacion !== "") {
                    $detalleCierre .= " · Motivo: " . dcEtiquetaMotivoAprobacion($motivoObservacion);
                }

                dcRegistrarAccionCredito(array(
                    "codigo_pedido" => (int) $solicitud["codigo_pedido"],
                    "codigo_cliente" => $solicitud["codigo_cliente"],
                    "tipo_accion" => "OBJECION_CERRADA",
                    "origen" => "centro_decisiones",
                    "pedido_estado_resultado" => "GENERADO",
                    "id_decision" => (int) $solicitud["id_decision"],
                    "motivo_codigo" => $motivoObservacion !== "" ? $motivoObservacion : null,
                    "comentario" => $comentario,
                    "usuario_id" => (int) $_SESSION["id"],
                    "detalle" => $detalleCierre,
                ));
            }
        }

        return self::ctrEstadoPedido((int) $solicitud["codigo_pedido"], $solicitud["codigo_cliente"]);
    }

    public static function ctrCerrarDecision()
    {
        if (!dcUsuarioPuedeResolver()) {
            return array("ok" => false, "msg" => "Sin permiso para cerrar decisiones.");
        }

        $idDecision = isset($_POST["id_decision"]) ? (int) $_POST["id_decision"] : 0;
        $resolucionCodigo = isset($_POST["resolucion_codigo"]) ? strtoupper(trim((string) $_POST["resolucion_codigo"])) : "";
        $comentario = isset($_POST["resolucion_comentario"]) ? trim((string) $_POST["resolucion_comentario"]) : "";

        if ($idDecision <= 0 || $resolucionCodigo === "") {
            return array("ok" => false, "msg" => "Indique la resolución.");
        }

        if (!dcObtenerResolucion($resolucionCodigo)) {
            return array("ok" => false, "msg" => "Resolución no válida.");
        }

        $decision = ModeloDecisionesCredito::mdlDecisionPorId($idDecision);

        if (!$decision || $decision["estado"] !== "VIGENTE") {
            return array("ok" => false, "msg" => "Decisión no encontrada o ya cerrada.");
        }

        $resultado = ModeloDecisionesCredito::mdlCerrarDecision(array(
            "id" => $idDecision,
            "resolucion_codigo" => $resolucionCodigo,
            "resolucion_comentario" => $comentario,
            "codigo_pedido" => (int) $decision["codigo_pedido"],
            "codigo_cliente" => $decision["codigo_cliente"],
            "usuario_resolucion" => (int) $_SESSION["id"],
        ));

        if (empty($resultado["ok"])) {
            return array("ok" => false, "msg" => isset($resultado["msg"]) ? $resultado["msg"] : "Error al cerrar.");
        }

        if (function_exists("dcRegistrarAccionCredito")) {
            dcRegistrarAccionCredito(array(
                "codigo_pedido" => (int) $decision["codigo_pedido"],
                "codigo_cliente" => $decision["codigo_cliente"],
                "tipo_accion" => "OBJECION_CERRADA",
                "origen" => "centro_decisiones",
                "pedido_estado_resultado" => "GENERADO",
                "id_decision" => $idDecision,
                "motivo_codigo" => isset($decision["motivo_codigo"]) ? $decision["motivo_codigo"] : null,
                "comentario" => $comentario,
                "usuario_id" => (int) $_SESSION["id"],
                "detalle" => "Resolución: " . $resolucionCodigo,
            ));
        }

        return self::ctrEstadoPedido((int) $decision["codigo_pedido"], $decision["codigo_cliente"]);
    }

    public static function ctrListarHistorialAcciones(array $filtros = array())
    {
        if (!dcUsuarioPuedeVerHistorialCredito()) {
            return array("ok" => false, "msg" => "Sin permiso para ver el historial.");
        }

        if (empty($filtros["fecha_desde"])) {
            $filtros["fecha_desde"] = date("Y-m-d", strtotime("-30 days"));
        }

        if (empty($filtros["fecha_hasta"])) {
            $filtros["fecha_hasta"] = date("Y-m-d");
        }

        $filas = ModeloDecisionesCredito::mdlListarAcciones($filtros);
        $resumen = ModeloDecisionesCredito::mdlResumenAcciones(array(
            "fecha_desde" => $filtros["fecha_desde"],
            "fecha_hasta" => $filtros["fecha_hasta"],
            "usuario_id" => isset($filtros["usuario_id"]) ? $filtros["usuario_id"] : 0,
        ));

        return array(
            "ok" => true,
            "filtros" => $filtros,
            "resumen" => $resumen,
            "filas" => $filas,
        );
    }

    public static function ctrDashboardGestionCredito(array $filtros = array())
    {
        if (!dcUsuarioPuedeVerHistorialCredito()) {
            return array("ok" => false, "msg" => "Sin permiso para ver el historial.");
        }

        if (empty($filtros["fecha_desde"])) {
            $filtros["fecha_desde"] = date("Y-m-d", strtotime("-30 days"));
        }

        if (empty($filtros["fecha_hasta"])) {
            $filtros["fecha_hasta"] = date("Y-m-d");
        }

        $vendedor = isset($filtros["vendedor"]) ? trim((string) $filtros["vendedor"]) : "";
        if ($vendedor !== "" && class_exists("ModeloDashboardDecisiones")) {
            $vendedor = ModeloDashboardDecisiones::normalizarVendedorFiltro($vendedor);
        }
        $filtros["vendedor"] = $vendedor;

        $datos = ModeloDecisionesCredito::mdlDashboardGestionCredito($filtros);

        $colaGenerados = 0;
        if (class_exists("ControladorDashboardDecisiones")) {
            $cola = ControladorDashboardDecisiones::ctrColaPedidosCredito(80, $vendedor !== "" ? $vendedor : null);
            if (!empty($cola["ok"]) && isset($cola["resumen"]["generados"])) {
                $colaGenerados = (int) $cola["resumen"]["generados"];
            }
        }
        $datos["kpis"]["cola_generados"] = $colaGenerados;

        return array(
            "ok" => true,
            "filtros" => $filtros,
            "kpis" => $datos["kpis"],
            "pulso" => $datos["pulso"],
            "comparacion" => $datos["comparacion"],
            "cola_salud" => $datos["cola_salud"],
            "embudo" => $datos["embudo"],
            "serie_diaria" => $datos["serie_diaria"],
            "motivos" => $datos["motivos"],
            "analistas" => $datos["analistas"],
            "clientes_objetados" => $datos["clientes_objetados"],
            "objeciones_abiertas" => $datos["objeciones_abiertas"],
            "actividad_hora" => $datos["actividad_hora"],
            "actividad_dow" => $datos["actividad_dow"],
            "ultimas_gestiones" => $datos["ultimas_gestiones"],
            "dias_abiertos_min" => $datos["dias_abiertos_min"],
        );
    }

    public static function ctrListarControlesPostAprobacion(array $filtros = array())
    {
        if (!dcUsuarioPuedeVerHistorialCredito()) {
            return array("ok" => false, "msg" => "Sin permiso.", "filas" => array());
        }

        $filas = ModeloDecisionesCredito::mdlListarControlesPostAprobacion($filtros);

        return array(
            "ok" => true,
            "filas" => $filas,
            "total" => count($filas),
            "puede_liberar" => dcUsuarioPuedeLiberarControlPostAprobacion(),
            "puede_registrar" => dcUsuarioPuedeRegistrarControlPostAprobacion(),
        );
    }

    public static function ctrRegistrarControlPostAprobacion()
    {
        if (!dcUsuarioPuedeRegistrarControlPostAprobacion()) {
            return array("ok" => false, "msg" => "Sin permiso para registrar controles.");
        }

        if (!isset($_SESSION["id"]) || !(int) $_SESSION["id"]) {
            return array("ok" => false, "msg" => "Sesión no válida.");
        }

        $codigoPedido = isset($_POST["codigo_pedido"]) ? (int) $_POST["codigo_pedido"] : 0;
        $condicionCodigo = isset($_POST["control_condicion_codigo"])
            ? strtoupper(trim((string) $_POST["control_condicion_codigo"]))
            : "";
        $areaCodigo = isset($_POST["control_area_codigo"])
            ? strtoupper(trim((string) $_POST["control_area_codigo"]))
            : "";
        $comentario = isset($_POST["control_comentario"])
            ? trim((string) $_POST["control_comentario"])
            : "";

        if ($codigoPedido <= 0) {
            return array("ok" => false, "msg" => "Pedido no indicado.");
        }

        if ($condicionCodigo === "") {
            return array("ok" => false, "msg" => "Indica la condición del control.");
        }

        $pedido = ModeloDecisionesCredito::mdlPedidoExiste($codigoPedido);
        if (!$pedido) {
            return array("ok" => false, "msg" => "Pedido no encontrado.");
        }

        $estado = strtoupper(trim((string) $pedido["estado"]));
        if (!in_array($estado, array("APROBADO", "APT"), true)) {
            return array(
                "ok" => false,
                "msg" => "Solo se puede registrar control en pedidos APROBADOS o en APT. Estado actual: "
                    . $estado,
            );
        }

        $codigoCliente = isset($pedido["cliente"]) ? trim((string) $pedido["cliente"]) : "";
        if ($codigoCliente === "") {
            return array("ok" => false, "msg" => "El pedido no tiene cliente asociado.");
        }

        return dcAplicarControlPostAprobacion(array(
            "codigo_pedido" => $codigoPedido,
            "codigo_cliente" => $codigoCliente,
            "condicion_codigo" => $condicionCodigo,
            "area_autoriza_codigo" => $areaCodigo !== "" ? $areaCodigo : null,
            "comentario" => $comentario,
            "usuario_id" => (int) $_SESSION["id"],
            "pedido_total" => isset($pedido["total"]) ? $pedido["total"] : null,
            "pedido_lista" => isset($pedido["lista"]) ? $pedido["lista"] : null,
            "pedido_estado_resultado" => $estado,
        ));
    }

    public static function ctrLiberarControlPostAprobacion()
    {
        if (!dcUsuarioPuedeLiberarControlPostAprobacion()) {
            return array("ok" => false, "msg" => "Sin permiso para liberar controles.");
        }

        if (!isset($_SESSION["id"]) || !(int) $_SESSION["id"]) {
            return array("ok" => false, "msg" => "Sesión no válida.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $comentario = isset($_POST["comentario_liberacion"])
            ? trim((string) $_POST["comentario_liberacion"])
            : "";

        $resultado = ModeloDecisionesCredito::mdlLiberarControlPostAprobacion(array(
            "id" => $id,
            "usuario_id" => (int) $_SESSION["id"],
            "comentario_liberacion" => $comentario,
        ));

        if (empty($resultado["ok"])) {
            return $resultado;
        }

        $control = isset($resultado["control"]) ? $resultado["control"] : array();
        $codigoPedido = isset($control["codigo_pedido"]) ? (int) $control["codigo_pedido"] : 0;
        $codigoCliente = isset($control["codigo_cliente"]) ? $control["codigo_cliente"] : "";

        if ($codigoPedido > 0 && function_exists("dcRegistrarAccionCredito")) {
            $detalle = "Control liberado: "
                . (isset($control["condicion_etiqueta"]) ? $control["condicion_etiqueta"] : $control["condicion_codigo"]);
            if (!empty($control["area_etiqueta"])) {
                $detalle .= " · Área: " . $control["area_etiqueta"];
            }

            dcRegistrarAccionCredito(array(
                "codigo_pedido" => $codigoPedido,
                "codigo_cliente" => $codigoCliente,
                "tipo_accion" => "DESPACHO_AUTORIZADO",
                "origen" => "centro_decisiones",
                "motivo_codigo" => isset($control["condicion_codigo"]) ? $control["condicion_codigo"] : null,
                "comentario" => $comentario !== "" ? $comentario : null,
                "usuario_id" => (int) $_SESSION["id"],
                "detalle" => $detalle,
            ));
        }

        return array(
            "ok" => true,
            "msg" => "Despacho autorizado. El pedido ya puede pasar a APT.",
            "control" => $control,
        );
    }
}
