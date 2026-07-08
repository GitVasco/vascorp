<?php

class ControladorLineaCredito
{
    public static function ctrPeriodoCierre()
    {
        date_default_timezone_set("America/Lima");

        return array(
            "anio" => (int) date("Y"),
            "mes" => (int) date("n"),
        );
    }

    private static function ctrLineaReferencia(array $snapshot)
    {
        if (!empty($snapshot["linea_aprobada"]) && (float) $snapshot["linea_aprobada"] > 0) {
            return (float) $snapshot["linea_aprobada"];
        }

        if (!empty($snapshot["linea_recomendada"]) && (float) $snapshot["linea_recomendada"] > 0) {
            return (float) $snapshot["linea_recomendada"];
        }

        return (float) $snapshot["linea_operativa"];
    }

    private static function ctrSnapshotDesdeIc($codigoCliente, $lineaAprobadaActual = null)
    {
        $analisis = ControladorInteligenciaComercial::ctrCalcularAnalisisCompleto($codigoCliente);

        if (empty($analisis["motor3"])) {
            return null;
        }

        $m1 = $analisis["motor1"];
        $m2 = $analisis["motor2"];
        $m3 = $analisis["motor3"];
        $m4 = $analisis["motor4"];
        $linea = $m3["linea"];
        $accion = isset($m3["accion"]["etiqueta"]) ? $m3["accion"]["etiqueta"] : "";

        $deuda = (float) $linea["deuda_actual"];
        $lineaOperativa = (float) $linea["linea_operativa"];
        $lineaRecomendada = function_exists("icRedondearLineaCredito")
            ? icRedondearLineaCredito((float) $linea["linea_recomendada"])
            : round((float) $linea["linea_recomendada"], 2);
        $lineaAprobada = $lineaAprobadaActual !== null ? (float) $lineaAprobadaActual : null;

        $refLinea = ($lineaAprobada !== null && $lineaAprobada > 0) ? $lineaAprobada : $lineaRecomendada;
        $refCupo = function_exists("icCalcularReferenciaCupoLinea")
            ? icCalcularReferenciaCupoLinea($deuda, $refLinea)
            : array("disponible_nuevo_credito" => max(0, $refLinea - $deuda));

        $utilizacionPct = $refLinea > 0
            ? round(($deuda / $refLinea) * 100, 2)
            : round((float) $linea["utilizacion_pct"], 2);

        return array(
            "codigo_cliente" => $codigoCliente,
            "linea_operativa" => round($lineaOperativa, 2),
            "linea_recomendada" => $lineaRecomendada,
            "linea_aprobada" => $lineaAprobada,
            "deuda_actual" => round($deuda, 2),
            "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
            "utilizacion_pct" => $utilizacionPct,
            "score_riesgo" => $m1 ? round((float) $m1["score"], 2) : null,
            "score_comercial" => $m2 ? round((float) $m2["score"], 2) : null,
            "score_fidelidad" => $m4 ? round((float) $m4["score"], 2) : null,
            "accion_linea" => $accion,
            "detalle" => json_encode(array(
                "riesgo_etiqueta" => $m1 ? $m1["clasificacion"]["etiqueta"] : null,
                "comercial_etiqueta" => $m2 ? $m2["clasificacion"]["etiqueta"] : null,
                "fidelidad_etiqueta" => $m4 ? $m4["clasificacion"]["etiqueta"] : null,
                "accion_explicacion" => isset($m3["accion"]["explicacion"]) ? $m3["accion"]["explicacion"] : "",
            ), JSON_UNESCAPED_UNICODE),
        );
    }

    private static function ctrPersistirSnapshot(array $snapshot, $tipoEvento, $anio, $mes, $usuarioId, $idSolicitud = null)
    {
        $snapshot["ultimo_cierre_anio"] = $anio;
        $snapshot["ultimo_cierre_mes"] = $mes;
        $snapshot["usuario_actualiza"] = $usuarioId;

        ModeloLineaCredito::mdlGuardarEstadoCliente($snapshot);

        ModeloLineaCredito::mdlRegistrarHistorial(array(
            "codigo_cliente" => $snapshot["codigo_cliente"],
            "anio" => $anio,
            "mes" => $mes,
            "tipo_evento" => $tipoEvento,
            "linea_operativa" => $snapshot["linea_operativa"],
            "linea_recomendada" => $snapshot["linea_recomendada"],
            "linea_aprobada" => $snapshot["linea_aprobada"],
            "deuda_actual" => $snapshot["deuda_actual"],
            "cupo_disponible" => $snapshot["cupo_disponible"],
            "utilizacion_pct" => $snapshot["utilizacion_pct"],
            "score_riesgo" => $snapshot["score_riesgo"],
            "score_comercial" => $snapshot["score_comercial"],
            "score_fidelidad" => $snapshot["score_fidelidad"],
            "accion_linea" => $snapshot["accion_linea"],
            "detalle" => $snapshot["detalle"],
            "id_solicitud" => $idSolicitud,
            "usuario_id" => $usuarioId,
        ));
    }

    public static function ctrListar($busqueda = "")
    {
        return ModeloLineaCredito::mdlListarClientesConLinea($busqueda);
    }

    public static function ctrDetalleCliente($codigoCliente)
    {
        $codigoCliente = trim((string) $codigoCliente);

        if ($codigoCliente === "") {
            return array("ok" => false, "msg" => "Cliente no indicado.");
        }

        $cliente = ModeloLineaCredito::mdlClienteLinea($codigoCliente);

        if (!$cliente) {
            return array("ok" => false, "msg" => "Cliente no encontrado.");
        }

        return array(
            "ok" => true,
            "cliente" => $cliente,
            "historial" => ModeloLineaCredito::mdlHistorialCliente($codigoCliente),
            "solicitudes" => ModeloLineaCredito::mdlSolicitudesCliente($codigoCliente),
            "url_ic" => "index.php?ruta=inteligencia-comercial&cliente=" . urlencode($codigoCliente),
        );
    }

    public static function ctrActualizarCliente($codigoCliente)
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $codigoCliente = trim((string) $codigoCliente);
        $fila = ModeloLineaCredito::mdlClienteLinea($codigoCliente);
        $lineaAprobada = ($fila && $fila["linea_aprobada"] !== null) ? $fila["linea_aprobada"] : null;

        $snapshot = self::ctrSnapshotDesdeIc($codigoCliente, $lineaAprobada);

        if (!$snapshot) {
            return array("ok" => false, "msg" => "No se pudo calcular la línea del cliente.");
        }

        $periodo = self::ctrPeriodoCierre();
        self::ctrPersistirSnapshot(
            $snapshot,
            "ACTUALIZACION_INDIVIDUAL",
            $periodo["anio"],
            $periodo["mes"],
            (int) $_SESSION["id"]
        );

        return self::ctrDetalleCliente($codigoCliente);
    }

    private static function ctrProcesarClienteCierre($codigo, $periodo, $idUsuario)
    {
        $codigo = trim((string) $codigo);

        if ($codigo === "") {
            return "omitido";
        }

        if (ModeloLineaCredito::mdlExisteCierreMensual($codigo, $periodo["anio"], $periodo["mes"])) {
            return "omitido";
        }

        $fila = ModeloLineaCredito::mdlClienteLinea($codigo);
        $lineaAprobada = ($fila && $fila["linea_aprobada"] !== null) ? $fila["linea_aprobada"] : null;
        $snapshot = self::ctrSnapshotDesdeIc($codigo, $lineaAprobada);

        if (!$snapshot) {
            ModeloLineaCredito::mdlRegistrarHistorial(array(
                "codigo_cliente" => $codigo,
                "anio" => $periodo["anio"],
                "mes" => $periodo["mes"],
                "tipo_evento" => "CIERRE_MENSUAL",
                "linea_operativa" => 0,
                "linea_recomendada" => 0,
                "linea_aprobada" => $lineaAprobada,
                "deuda_actual" => 0,
                "cupo_disponible" => 0,
                "utilizacion_pct" => 0,
                "score_riesgo" => null,
                "score_comercial" => null,
                "score_fidelidad" => null,
                "accion_linea" => null,
                "detalle" => json_encode(array("error" => "ic_no_disponible"), JSON_UNESCAPED_UNICODE),
                "id_solicitud" => null,
                "usuario_id" => (int) $idUsuario,
            ));

            return "error";
        }

        self::ctrPersistirSnapshot(
            $snapshot,
            "CIERRE_MENSUAL",
            $periodo["anio"],
            $periodo["mes"],
            (int) $idUsuario
        );

        return "procesado";
    }

    public static function ctrCierreMensualLote($limite = 15)
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso para ejecutar cierre mensual.");
        }

        $limite = max(1, min(30, (int) $limite));
        $periodo = self::ctrPeriodoCierre();
        $clientes = ModeloLineaCredito::mdlClientesCarteraActivaPendientesCierre(
            $periodo["anio"],
            $periodo["mes"],
            $limite
        );
        $procesados = 0;
        $errores = 0;
        $idUsuario = (int) $_SESSION["id"];

        foreach ($clientes as $cliente) {
            $resultado = self::ctrProcesarClienteCierre($cliente["codigo"], $periodo, $idUsuario);

            if ($resultado === "procesado") {
                $procesados++;
            } elseif ($resultado === "error") {
                $errores++;
            }
        }

        $restantes = ModeloLineaCredito::mdlContarPendientesCierre($periodo["anio"], $periodo["mes"]);

        return array(
            "ok" => true,
            "anio" => $periodo["anio"],
            "mes" => $periodo["mes"],
            "procesados_lote" => $procesados,
            "errores_lote" => $errores,
            "restantes" => $restantes,
            "terminado" => $restantes === 0,
            "total_cierre" => $restantes === 0
                ? ModeloLineaCredito::mdlResumenCierre($periodo["anio"], $periodo["mes"])
                : null,
        );
    }

    public static function ctrCierreMensual()
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso para ejecutar cierre mensual.");
        }

        $periodo = self::ctrPeriodoCierre();
        $totalCartera = ModeloLineaCredito::mdlContarCarteraActiva();
        $procesados = 0;
        $omitidos = 0;
        $errores = 0;
        $idUsuario = (int) $_SESSION["id"];

        do {
            $lote = self::ctrCierreMensualLote(30);
            if (empty($lote["ok"])) {
                return $lote;
            }
            $procesados += (int) $lote["procesados_lote"];
            $errores += (int) $lote["errores_lote"];
        } while (empty($lote["terminado"]));

        $omitidos = max(0, $totalCartera - $procesados - $errores);

        return array(
            "ok" => true,
            "anio" => $periodo["anio"],
            "mes" => $periodo["mes"],
            "total_cartera" => $totalCartera,
            "procesados" => $procesados,
            "omitidos" => $omitidos,
            "errores" => $errores,
            "total_cierre" => ModeloLineaCredito::mdlResumenCierre($periodo["anio"], $periodo["mes"]),
        );
    }

    public static function ctrCrearSolicitud()
    {
        $codigoCliente = isset($_POST["codigo_cliente"]) ? trim((string) $_POST["codigo_cliente"]) : "";
        $lineaSolicitada = isset($_POST["linea_solicitada"]) ? (float) $_POST["linea_solicitada"] : 0;
        $justificacion = isset($_POST["justificacion"]) ? trim((string) $_POST["justificacion"]) : "";

        if ($codigoCliente === "" || $lineaSolicitada <= 0 || $justificacion === "") {
            return array("ok" => false, "msg" => "Complete cliente, monto solicitado y justificación.");
        }

        if (function_exists("icRedondearLineaCredito")) {
            $lineaSolicitada = icRedondearLineaCredito($lineaSolicitada);
        }

        if ($lineaSolicitada <= 0) {
            return array("ok" => false, "msg" => "El monto solicitado debe ser al menos S/ 1.000.");
        }

        $fila = ModeloLineaCredito::mdlClienteLinea($codigoCliente);
        $lineaActual = 0.0;

        if ($fila) {
            if (!empty($fila["linea_aprobada"])) {
                $lineaActual = (float) $fila["linea_aprobada"];
            } elseif (!empty($fila["linea_recomendada"])) {
                $lineaActual = (float) $fila["linea_recomendada"];
            } else {
                $lineaActual = (float) $fila["linea_operativa"];
            }
        }

        $id = ModeloLineaCredito::mdlCrearSolicitud(array(
            "codigo_cliente" => $codigoCliente,
            "linea_actual" => round($lineaActual, 2),
            "linea_solicitada" => round($lineaSolicitada, 2),
            "justificacion" => $justificacion,
            "usuario_solicita" => (int) $_SESSION["id"],
        ));

        if ($id <= 0) {
            return array("ok" => false, "msg" => "No se pudo registrar la solicitud.");
        }

        return self::ctrDetalleCliente($codigoCliente);
    }

    public static function ctrResolverSolicitud()
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso para resolver solicitudes.");
        }

        $id = isset($_POST["id_solicitud"]) ? (int) $_POST["id_solicitud"] : 0;
        $estado = isset($_POST["estado"]) ? strtoupper(trim((string) $_POST["estado"])) : "";
        $lineaResuelta = isset($_POST["linea_resuelta"]) ? (float) $_POST["linea_resuelta"] : 0;
        $comentario = isset($_POST["comentario_resolucion"]) ? trim((string) $_POST["comentario_resolucion"]) : "";

        if ($id <= 0 || !in_array($estado, array("APROBADA", "RECHAZADA"), true)) {
            return array("ok" => false, "msg" => "Datos de resolución incompletos.");
        }

        if ($estado === "APROBADA" && $lineaResuelta <= 0) {
            return array("ok" => false, "msg" => "Indique el monto de línea aprobado.");
        }

        if ($estado === "APROBADA" && function_exists("icRedondearLineaCredito")) {
            $lineaResuelta = icRedondearLineaCredito($lineaResuelta);
            if ($lineaResuelta <= 0) {
                return array("ok" => false, "msg" => "El monto aprobado debe ser al menos S/ 1.000.");
            }
        }

        $solicitud = ModeloLineaCredito::mdlSolicitudPorId($id);

        if (!$solicitud || $solicitud["estado"] !== "PENDIENTE") {
            return array("ok" => false, "msg" => "Solicitud no encontrada o ya resuelta.");
        }

        $resultado = ModeloLineaCredito::mdlResolverSolicitud(array(
            "id" => $id,
            "estado" => $estado,
            "linea_resuelta" => $estado === "APROBADA" ? $lineaAprobada : null,
            "comentario_resolucion" => $comentario,
            "usuario_resuelve" => (int) $_SESSION["id"],
        ));

        if ($resultado !== "ok") {
            return array("ok" => false, "msg" => "No se pudo resolver la solicitud.");
        }

        $periodo = self::ctrPeriodoCierre();
        $tipoEvento = $estado === "APROBADA" ? "LINEA_APROBADA" : "LINEA_RECHAZADA";
        $lineaAprobada = $estado === "APROBADA"
            ? (function_exists("icRedondearLineaCredito") ? icRedondearLineaCredito($lineaResuelta) : round($lineaResuelta, 2))
            : null;

        if ($estado === "APROBADA") {
            ModeloLineaCredito::mdlActualizarLineaAprobada(
                $solicitud["codigo_cliente"],
                $lineaAprobada,
                (int) $_SESSION["id"]
            );
        }

        $snapshot = self::ctrSnapshotDesdeIc($solicitud["codigo_cliente"], $lineaAprobada);

        if ($snapshot) {
            if ($estado === "APROBADA") {
                $snapshot["linea_aprobada"] = $lineaAprobada;
            }

            self::ctrPersistirSnapshot(
                $snapshot,
                $tipoEvento,
                $periodo["anio"],
                $periodo["mes"],
                (int) $_SESSION["id"],
                $id
            );
        }

        return self::ctrDetalleCliente($solicitud["codigo_cliente"]);
    }
}
