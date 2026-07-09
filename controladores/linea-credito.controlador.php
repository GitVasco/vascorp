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

    private static function ctrPersistirSnapshotGrupo(array $snapshot, $tipoEvento, $anio, $mes, $usuarioId)
    {
        $snapshot["ultimo_cierre_anio"] = $anio;
        $snapshot["ultimo_cierre_mes"] = $mes;
        $snapshot["usuario_actualiza"] = $usuarioId;

        ModeloLineaCredito::mdlGuardarEstadoGrupo($snapshot);

        ModeloLineaCredito::mdlRegistrarHistorialGrupo(array(
            "codigo_grupo" => $snapshot["codigo_grupo"],
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
            "usuario_id" => $usuarioId,
        ));
    }

    private static function ctrSnapshotGrupoDesdeIc($codigoGrupo, $lineaAprobadaActual = null)
    {
        $motores = self::ctrMotoresGrupoIc($codigoGrupo);
        $motor1 = $motores["motor1"];
        $motor2 = $motores["motor2"];
        $motor3 = $motores["motor3"];
        $motor4 = $motores["motor4"];

        if (!$motor3 || empty($motor3["linea"])) {
            return null;
        }

        $linea = $motor3["linea"];
        $accion = isset($motor3["accion"]["etiqueta"]) ? $motor3["accion"]["etiqueta"] : "";
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
            "codigo_grupo" => $codigoGrupo,
            "linea_operativa" => round($lineaOperativa, 2),
            "linea_recomendada" => $lineaRecomendada,
            "linea_aprobada" => $lineaAprobada,
            "deuda_actual" => round($deuda, 2),
            "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
            "utilizacion_pct" => $utilizacionPct,
            "score_riesgo" => $motor1 ? round((float) $motor1["score"], 2) : null,
            "score_comercial" => $motor2 ? round((float) $motor2["score"], 2) : null,
            "score_fidelidad" => $motor4 ? round((float) $motor4["score"], 2) : null,
            "accion_linea" => $accion,
            "detalle" => json_encode(array(
                "riesgo_etiqueta" => $motor1 ? $motor1["clasificacion"]["etiqueta"] : null,
                "comercial_etiqueta" => $motor2 ? $motor2["clasificacion"]["etiqueta"] : null,
                "fidelidad_etiqueta" => $motor4 ? $motor4["clasificacion"]["etiqueta"] : null,
                "accion_explicacion" => isset($motor3["accion"]["explicacion"]) ? $motor3["accion"]["explicacion"] : "",
            ), JSON_UNESCAPED_UNICODE),
        );
    }

    private static function ctrLineaAnteriorGrupo($fila)
    {
        if (!$fila || empty($fila["linea_aprobada"])) {
            return null;
        }

        return (float) $fila["linea_aprobada"];
    }

    private static function ctrClientePerteneceGrupo($fila)
    {
        return $fila && !empty($fila["grupo"]) && trim((string) $fila["grupo"]) !== "";
    }

    public static function ctrListar($busqueda = "")
    {
        return ModeloLineaCredito::mdlListarClientesConLinea($busqueda);
    }

    /**
     * Referencia de cupo para validar pedidos (Centro de Decisiones).
     * Clientes con grupo usan línea/deuda consolidada del grupo.
     */
    public static function ctrReferenciaCupoPedido($codigoCliente, $lineaRecomendadaIc = null, $deudaIc = null)
    {
        $codigoCliente = trim((string) $codigoCliente);
        $cliente = ModeloLineaCredito::mdlClienteLinea($codigoCliente);

        if (!$cliente) {
            return null;
        }

        if (empty($cliente["grupo"]) || trim((string) $cliente["grupo"]) === "") {
            $lineaAprobada = (!empty($cliente["linea_aprobada"]) && (float) $cliente["linea_aprobada"] > 0)
                ? (float) $cliente["linea_aprobada"]
                : null;
            $deuda = $deudaIc !== null
                ? (float) $deudaIc
                : (float) (isset($cliente["deuda_actual"]) ? $cliente["deuda_actual"] : 0);
            $recomendada = $lineaRecomendadaIc !== null
                ? (float) $lineaRecomendadaIc
                : (float) (isset($cliente["linea_recomendada"]) ? $cliente["linea_recomendada"] : 0);

            if ($recomendada > 0 && function_exists("icRedondearLineaCredito")) {
                $recomendada = icRedondearLineaCredito($recomendada);
            } elseif ($recomendada > 0) {
                $recomendada = round($recomendada, 2);
            }

            $refLinea = ($lineaAprobada !== null && $lineaAprobada > 0) ? $lineaAprobada : $recomendada;
            $refCupo = function_exists("icCalcularReferenciaCupoLinea")
                ? icCalcularReferenciaCupoLinea($deuda, $refLinea)
                : array(
                    "disponible_nuevo_credito" => max(0, $refLinea - $deuda),
                    "excedido_sobre_recomendada" => max(0, $deuda - $refLinea),
                    "cupo_agotado" => false,
                );

            $utilizacion = $refLinea > 0 ? round(($deuda / $refLinea) * 100, 1) : 0;

            return array(
                "modo" => "cliente",
                "grupo" => null,
                "linea_aprobada" => $lineaAprobada,
                "linea_recomendada" => $recomendada,
                "linea_referencia" => $refLinea,
                "deuda_actual" => round($deuda, 2),
                "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
                "excedido_sobre_recomendada" => round((float) $refCupo["excedido_sobre_recomendada"], 2),
                "cupo_agotado" => !empty($refCupo["cupo_agotado"]),
                "utilizacion_pct" => $utilizacion,
                "etiqueta_linea" => ($lineaAprobada !== null && $lineaAprobada > 0)
                    ? "Aprobada por créditos"
                    : "Recomendada IC",
            );
        }

        $codigoGrupo = trim((string) $cliente["grupo"]);
        $grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $codigoGrupo);
        $lineaGrupo = ModeloLineaCredito::mdlGrupoLinea($codigoGrupo);
        $lineaOperativa = ModeloInteligenciaComercial::mdlLineaCreditoOperativaGrupo($codigoGrupo);
        $deuda = isset($lineaOperativa["deuda_actual"]) ? (float) $lineaOperativa["deuda_actual"] : 0;

        $lineaAprobada = ($lineaGrupo && !empty($lineaGrupo["linea_aprobada"]) && (float) $lineaGrupo["linea_aprobada"] > 0)
            ? (float) $lineaGrupo["linea_aprobada"]
            : null;

        $recomendada = null;

        if ($lineaGrupo && !empty($lineaGrupo["linea_recomendada"]) && (float) $lineaGrupo["linea_recomendada"] > 0) {
            $recomendada = (float) $lineaGrupo["linea_recomendada"];
        } elseif ($lineaRecomendadaIc !== null && (float) $lineaRecomendadaIc > 0) {
            $recomendada = (float) $lineaRecomendadaIc;
        } else {
            $motores = self::ctrMotoresGrupoIc($codigoGrupo);
            $motor3 = $motores["motor3"];

            if ($motor3 && !empty($motor3["linea"]["linea_recomendada"])) {
                $recomendada = (float) $motor3["linea"]["linea_recomendada"];
            }
        }

        if ($recomendada !== null && $recomendada > 0 && function_exists("icRedondearLineaCredito")) {
            $recomendada = icRedondearLineaCredito($recomendada);
        } elseif ($recomendada !== null && $recomendada > 0) {
            $recomendada = round($recomendada, 2);
        } else {
            $recomendada = 0.0;
        }

        $refLinea = ($lineaAprobada !== null && $lineaAprobada > 0) ? $lineaAprobada : $recomendada;
        $refCupo = function_exists("icCalcularReferenciaCupoLinea")
            ? icCalcularReferenciaCupoLinea($deuda, $refLinea)
            : array(
                "disponible_nuevo_credito" => max(0, $refLinea - $deuda),
                "excedido_sobre_recomendada" => max(0, $deuda - $refLinea),
                "cupo_agotado" => false,
            );

        $utilizacion = $refLinea > 0 ? round(($deuda / $refLinea) * 100, 1) : 0;

        return array(
            "modo" => "grupo",
            "grupo" => array(
                "codigo" => $codigoGrupo,
                "nombre" => $grupo ? $grupo["nombre"] : $codigoGrupo,
            ),
            "linea_aprobada" => $lineaAprobada,
            "linea_recomendada" => $recomendada,
            "linea_referencia" => $refLinea,
            "deuda_actual" => round($deuda, 2),
            "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
            "excedido_sobre_recomendada" => round((float) $refCupo["excedido_sobre_recomendada"], 2),
            "cupo_agotado" => !empty($refCupo["cupo_agotado"]),
            "utilizacion_pct" => $utilizacion,
            "etiqueta_linea" => ($lineaAprobada !== null && $lineaAprobada > 0)
                ? "Aprobada del grupo"
                : "Recomendada IC (grupo)",
        );
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

        $perteneceGrupo = self::ctrClientePerteneceGrupo($cliente);
        $grupoCliente = null;
        $grupoResumen = null;

        if ($perteneceGrupo) {
            $grupoCliente = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $cliente["grupo"]);
            $grupoResumen = self::ctrReferenciaCupoPedido($codigoCliente);
        }

        return array(
            "ok" => true,
            "cliente" => $cliente,
            "historial" => ModeloLineaCredito::mdlHistorialCliente($codigoCliente),
            "url_ic" => "index.php?ruta=inteligencia-comercial&cliente=" . urlencode($codigoCliente),
            "pertenece_grupo" => $perteneceGrupo,
            "grupo" => $grupoCliente,
            "grupo_resumen" => $grupoResumen,
        );
    }

    private static function ctrTotalesCarteraGrupo(array $miembros)
    {
        $totales = array(
            "clientes" => count($miembros),
            "deuda" => 0.0,
            "deuda_vencida" => 0.0,
            "cupo" => 0.0,
            "linea_aprobada" => 0.0,
            "linea_recomendada" => 0.0,
            "linea_vigente" => 0.0,
            "sin_aprobada" => 0,
        );

        foreach ($miembros as $fila) {
            $deuda = isset($fila["deuda_actual"]) ? (float) $fila["deuda_actual"] : 0.0;
            $deudaVencida = isset($fila["deuda_vencida"]) ? (float) $fila["deuda_vencida"] : 0.0;
            $cupo = isset($fila["cupo_disponible"]) ? (float) $fila["cupo_disponible"] : 0.0;
            $recomendada = isset($fila["linea_recomendada"]) ? (float) $fila["linea_recomendada"] : 0.0;
            $aprobada = isset($fila["linea_aprobada"]) ? (float) $fila["linea_aprobada"] : 0.0;

            $totales["deuda"] += $deuda;
            $totales["deuda_vencida"] += $deudaVencida;
            $totales["cupo"] += $cupo;
            $totales["linea_recomendada"] += $recomendada;

            if ($aprobada > 0) {
                $totales["linea_aprobada"] += $aprobada;
                $totales["linea_vigente"] += $aprobada;
            } else {
                $totales["sin_aprobada"]++;
                $totales["linea_vigente"] += $recomendada;
            }
        }

        foreach (array("deuda", "deuda_vencida", "cupo", "linea_aprobada", "linea_recomendada", "linea_vigente") as $campo) {
            $totales[$campo] = round($totales[$campo], 2);
        }

        return $totales;
    }

    private static function ctrMotoresGrupoIc($codigoGrupo)
    {
        $motor1 = ControladorInteligenciaComercial::ctrCalcularMotorRiesgoCreditoGrupo($codigoGrupo);
        $motor2 = ControladorInteligenciaComercial::ctrCalcularMotorComercialGrupo($codigoGrupo);
        $motor4 = ControladorInteligenciaComercial::ctrCalcularMotorFidelidadGrupo($codigoGrupo);
        $motor3 = ControladorInteligenciaComercial::ctrCalcularMotorLineaCreditoGrupo($codigoGrupo, $motor1, $motor2, $motor4);

        if ($motor3 && !empty($motor3["linea"]["linea_recomendada"])) {
            $lineaRecomendada = (float) $motor3["linea"]["linea_recomendada"];

            if ($lineaRecomendada > 0) {
                $motor1 = ControladorInteligenciaComercial::ctrCalcularMotorRiesgoCreditoGrupo($codigoGrupo, $lineaRecomendada);
                $motor3 = ControladorInteligenciaComercial::ctrCalcularMotorLineaCreditoGrupo($codigoGrupo, $motor1, $motor2, $motor4);
            }
        }

        return array(
            "motor1" => $motor1,
            "motor2" => $motor2,
            "motor3" => $motor3,
            "motor4" => $motor4,
        );
    }

    public static function ctrDetalleGrupo($codigoGrupo)
    {
        $codigoGrupo = trim((string) $codigoGrupo);

        if ($codigoGrupo === "" || $codigoGrupo === "__sin_grupo__") {
            return array("ok" => false, "msg" => "Grupo no indicado.");
        }

        $grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $codigoGrupo);

        if (!$grupo) {
            return array("ok" => false, "msg" => "Grupo no encontrado.");
        }

        $miembros = ModeloLineaCredito::mdlListarClientesConLineaPorGrupo($codigoGrupo);
        $lineaGrupo = ModeloLineaCredito::mdlGrupoLinea($codigoGrupo);
        $motores = self::ctrMotoresGrupoIc($codigoGrupo);
        $motor1 = $motores["motor1"];
        $motor2 = $motores["motor2"];
        $motor3 = $motores["motor3"];
        $motor4 = $motores["motor4"];
        $lineaIc = ($motor3 && isset($motor3["linea"])) ? $motor3["linea"] : array();

        $lineaRecomendada = isset($lineaIc["linea_recomendada"]) ? (float) $lineaIc["linea_recomendada"] : null;

        if ($lineaRecomendada !== null && function_exists("icRedondearLineaCredito")) {
            $lineaRecomendada = icRedondearLineaCredito($lineaRecomendada);
        } elseif ($lineaRecomendada !== null) {
            $lineaRecomendada = round($lineaRecomendada, 2);
        }

        $deudaIc = isset($lineaIc["deuda_actual"]) ? round((float) $lineaIc["deuda_actual"], 2) : null;
        $lineaOperGrupo = ModeloInteligenciaComercial::mdlLineaCreditoOperativaGrupo($codigoGrupo);
        $deudaVencida = isset($lineaOperGrupo["deuda_vencida"])
            ? round((float) $lineaOperGrupo["deuda_vencida"], 2)
            : null;
        $lineaAprobada = ($lineaGrupo && !empty($lineaGrupo["linea_aprobada"]))
            ? round((float) $lineaGrupo["linea_aprobada"], 2)
            : null;
        $refLinea = ($lineaAprobada !== null && $lineaAprobada > 0) ? $lineaAprobada : $lineaRecomendada;
        $cupoRegistrado = null;
        $utilRegistrado = null;

        if ($refLinea !== null && $refLinea > 0 && $deudaIc !== null) {
            $refCupo = function_exists("icCalcularReferenciaCupoLinea")
                ? icCalcularReferenciaCupoLinea($deudaIc, $refLinea)
                : array("disponible_nuevo_credito" => max(0, $refLinea - $deudaIc));
            $cupoRegistrado = round((float) $refCupo["disponible_nuevo_credito"], 2);
            $utilRegistrado = round(($deudaIc / $refLinea) * 100, 1);
        }

        $cupoIc = null;
        $utilIc = isset($lineaIc["utilizacion_pct"]) ? round((float) $lineaIc["utilizacion_pct"], 1) : null;

        if ($lineaRecomendada !== null && $lineaRecomendada > 0 && $deudaIc !== null && function_exists("icCalcularReferenciaCupoLinea")) {
            $refCupoIc = icCalcularReferenciaCupoLinea($deudaIc, $lineaRecomendada);
            $cupoIc = round((float) $refCupoIc["disponible_nuevo_credito"], 2);
        } elseif ($lineaRecomendada !== null && $deudaIc !== null) {
            $cupoIc = round(max(0, $lineaRecomendada - $deudaIc), 2);
        }

        return array(
            "ok" => true,
            "grupo" => array(
                "codigo" => $grupo["codigo"],
                "nombre" => $grupo["nombre"],
            ),
            "linea_grupo" => $lineaGrupo,
            "historial_grupo" => ModeloLineaCredito::mdlHistorialGrupo($codigoGrupo),
            "consolidado" => array(
                "score_riesgo" => $motor1 ? round((float) $motor1["score"], 1) : null,
                "score_comercial" => $motor2 ? round((float) $motor2["score"], 1) : null,
                "score_fidelidad" => $motor4 ? round((float) $motor4["score"], 1) : null,
                "riesgo_etiqueta" => ($motor1 && isset($motor1["clasificacion"]["etiqueta"])) ? $motor1["clasificacion"]["etiqueta"] : null,
                "comercial_etiqueta" => ($motor2 && isset($motor2["clasificacion"]["etiqueta"])) ? $motor2["clasificacion"]["etiqueta"] : null,
                "fidelidad_etiqueta" => ($motor4 && isset($motor4["clasificacion"]["etiqueta"])) ? $motor4["clasificacion"]["etiqueta"] : null,
                "linea_operativa" => isset($lineaIc["linea_operativa"]) ? round((float) $lineaIc["linea_operativa"], 2) : null,
                "linea_recomendada" => $lineaRecomendada,
                "linea_aprobada" => $lineaAprobada,
                "deuda_actual" => $deudaIc,
                "deuda_vencida" => $deudaVencida,
                "cupo_disponible" => $cupoRegistrado !== null ? $cupoRegistrado : $cupoIc,
                "utilizacion_pct" => $utilRegistrado !== null ? $utilRegistrado : $utilIc,
                "accion_linea" => ($motor3 && isset($motor3["accion"]["etiqueta"])) ? $motor3["accion"]["etiqueta"] : null,
            ),
            "peor_ruc" => ($motor1 && !empty($motor1["peor_ruc"]["codigo"])) ? $motor1["peor_ruc"] : null,
            "totales_cartera" => self::ctrTotalesCarteraGrupo($miembros),
            "miembros" => $miembros,
            "url_ic" => "index.php?ruta=inteligencia-comercial&modo=grupo&grupo=" . urlencode($codigoGrupo),
        );
    }

    public static function ctrRegistrarLineaGrupo()
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $codigoGrupo = isset($_POST["codigo_grupo"]) ? trim((string) $_POST["codigo_grupo"]) : "";
        $lineaNueva = isset($_POST["linea_aprobada"]) ? (float) $_POST["linea_aprobada"] : 0;
        $motivo = isset($_POST["motivo"]) ? trim((string) $_POST["motivo"]) : "";

        if ($codigoGrupo === "" || $lineaNueva <= 0 || $motivo === "") {
            return array("ok" => false, "msg" => "Complete grupo, línea aprobada y motivo.");
        }

        if (function_exists("icRedondearLineaCredito")) {
            $lineaNueva = icRedondearLineaCredito($lineaNueva);
        }

        if ($lineaNueva <= 0) {
            return array("ok" => false, "msg" => "La línea aprobada debe ser al menos S/ 1.000.");
        }

        $grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $codigoGrupo);

        if (!$grupo) {
            return array("ok" => false, "msg" => "Grupo no encontrado.");
        }

        $fila = ModeloLineaCredito::mdlGrupoLinea($codigoGrupo);
        $lineaAnterior = self::ctrLineaAnteriorGrupo($fila);

        if ($lineaAnterior !== null && abs($lineaAnterior - $lineaNueva) < 0.01) {
            return array("ok" => false, "msg" => "La línea indicada es igual a la vigente.");
        }

        $snapshot = self::ctrSnapshotGrupoDesdeIc($codigoGrupo, $lineaNueva);

        if (!$snapshot) {
            $miembros = ModeloLineaCredito::mdlListarClientesConLineaPorGrupo($codigoGrupo);
            $totales = self::ctrTotalesCarteraGrupo($miembros);
            $deuda = (float) $totales["deuda"];
            $refCupo = function_exists("icCalcularReferenciaCupoLinea")
                ? icCalcularReferenciaCupoLinea($deuda, $lineaNueva)
                : array("disponible_nuevo_credito" => max(0, $lineaNueva - $deuda));

            $snapshot = array(
                "codigo_grupo" => $codigoGrupo,
                "linea_operativa" => round((float) (isset($fila["linea_operativa"]) ? $fila["linea_operativa"] : 0), 2),
                "linea_recomendada" => round((float) (isset($fila["linea_recomendada"]) ? $fila["linea_recomendada"] : 0), 2),
                "linea_aprobada" => $lineaNueva,
                "deuda_actual" => round($deuda, 2),
                "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
                "utilizacion_pct" => $lineaNueva > 0 ? round(($deuda / $lineaNueva) * 100, 2) : 0,
                "score_riesgo" => isset($fila["score_riesgo"]) ? $fila["score_riesgo"] : null,
                "score_comercial" => isset($fila["score_comercial"]) ? $fila["score_comercial"] : null,
                "score_fidelidad" => isset($fila["score_fidelidad"]) ? $fila["score_fidelidad"] : null,
                "accion_linea" => isset($fila["accion_linea"]) ? $fila["accion_linea"] : null,
                "detalle" => null,
            );
        } else {
            $snapshot["linea_aprobada"] = $lineaNueva;
        }

        $detalleRegistro = array(
            "origen" => "REGISTRO_MANUAL_GRUPO",
            "motivo" => $motivo,
            "linea_anterior" => $lineaAnterior,
            "linea_nueva" => $lineaNueva,
        );

        if (!empty($snapshot["detalle"])) {
            $icDetalle = json_decode($snapshot["detalle"], true);
            if (is_array($icDetalle)) {
                $detalleRegistro["ic"] = $icDetalle;
            }
        }

        $snapshot["detalle"] = json_encode($detalleRegistro, JSON_UNESCAPED_UNICODE);

        $periodo = self::ctrPeriodoCierre();
        $tipoEvento = ($lineaAnterior !== null && $lineaAnterior > 0) ? "LINEA_ACTUALIZADA" : "LINEA_APROBADA";

        self::ctrPersistirSnapshotGrupo(
            $snapshot,
            $tipoEvento,
            $periodo["anio"],
            $periodo["mes"],
            (int) $_SESSION["id"]
        );

        ModeloLineaCredito::mdlLimpiarLineaAprobadaMiembrosGrupo($codigoGrupo);

        return self::ctrDetalleGrupo($codigoGrupo);
    }

    private static function ctrLineaAnteriorCliente($fila)
    {
        if (!$fila || empty($fila["linea_aprobada"])) {
            return null;
        }

        return (float) $fila["linea_aprobada"];
    }

    public static function ctrRegistrarLinea()
    {
        if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
            return array("ok" => false, "msg" => "Sin permiso.");
        }

        $codigoCliente = isset($_POST["codigo_cliente"]) ? trim((string) $_POST["codigo_cliente"]) : "";
        $lineaNueva = isset($_POST["linea_aprobada"]) ? (float) $_POST["linea_aprobada"] : 0;
        $motivo = isset($_POST["motivo"]) ? trim((string) $_POST["motivo"]) : "";

        if ($codigoCliente === "" || $lineaNueva <= 0 || $motivo === "") {
            return array("ok" => false, "msg" => "Complete cliente, línea aprobada y motivo.");
        }

        if (function_exists("icRedondearLineaCredito")) {
            $lineaNueva = icRedondearLineaCredito($lineaNueva);
        }

        if ($lineaNueva <= 0) {
            return array("ok" => false, "msg" => "La línea aprobada debe ser al menos S/ 1.000.");
        }

        $fila = ModeloLineaCredito::mdlClienteLinea($codigoCliente);

        if (!$fila) {
            return array("ok" => false, "msg" => "Cliente no encontrado.");
        }

        if (self::ctrClientePerteneceGrupo($fila)) {
            $grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $fila["grupo"]);
            $nombreGrupo = $grupo ? $grupo["nombre"] : $fila["grupo"];

            return array(
                "ok" => false,
                "msg" => "Este cliente pertenece al grupo " . $nombreGrupo . ". Registre la línea aprobada a nivel de grupo.",
            );
        }

        $lineaAnterior = self::ctrLineaAnteriorCliente($fila);

        if ($lineaAnterior !== null && abs($lineaAnterior - $lineaNueva) < 0.01) {
            return array("ok" => false, "msg" => "La línea indicada es igual a la vigente.");
        }

        $snapshot = self::ctrSnapshotDesdeIc($codigoCliente, $lineaNueva);

        if (!$snapshot) {
            $deuda = isset($fila["deuda_actual"]) ? (float) $fila["deuda_actual"] : 0.0;
            $refCupo = function_exists("icCalcularReferenciaCupoLinea")
                ? icCalcularReferenciaCupoLinea($deuda, $lineaNueva)
                : array("disponible_nuevo_credito" => max(0, $lineaNueva - $deuda));

            $snapshot = array(
                "codigo_cliente" => $codigoCliente,
                "linea_operativa" => round((float) (isset($fila["linea_operativa"]) ? $fila["linea_operativa"] : 0), 2),
                "linea_recomendada" => round((float) (isset($fila["linea_recomendada"]) ? $fila["linea_recomendada"] : 0), 2),
                "linea_aprobada" => $lineaNueva,
                "deuda_actual" => round($deuda, 2),
                "cupo_disponible" => round((float) $refCupo["disponible_nuevo_credito"], 2),
                "utilizacion_pct" => $lineaNueva > 0 ? round(($deuda / $lineaNueva) * 100, 2) : 0,
                "score_riesgo" => isset($fila["score_riesgo"]) ? $fila["score_riesgo"] : null,
                "score_comercial" => isset($fila["score_comercial"]) ? $fila["score_comercial"] : null,
                "score_fidelidad" => isset($fila["score_fidelidad"]) ? $fila["score_fidelidad"] : null,
                "accion_linea" => isset($fila["accion_linea"]) ? $fila["accion_linea"] : null,
                "detalle" => null,
            );
        } else {
            $snapshot["linea_aprobada"] = $lineaNueva;
        }

        $detalleRegistro = array(
            "origen" => "REGISTRO_MANUAL",
            "motivo" => $motivo,
            "linea_anterior" => $lineaAnterior,
            "linea_nueva" => $lineaNueva,
        );

        if (!empty($snapshot["detalle"])) {
            $icDetalle = json_decode($snapshot["detalle"], true);
            if (is_array($icDetalle)) {
                $detalleRegistro["ic"] = $icDetalle;
            }
        }

        $snapshot["detalle"] = json_encode($detalleRegistro, JSON_UNESCAPED_UNICODE);

        $periodo = self::ctrPeriodoCierre();
        $tipoEvento = ($lineaAnterior !== null && $lineaAnterior > 0) ? "LINEA_ACTUALIZADA" : "LINEA_APROBADA";

        self::ctrPersistirSnapshot(
            $snapshot,
            $tipoEvento,
            $periodo["anio"],
            $periodo["mes"],
            (int) $_SESSION["id"]
        );

        return self::ctrDetalleCliente($codigoCliente);
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

    private static function ctrProcesarGrupoCierre($codigoGrupo, $periodo, $idUsuario)
    {
        $codigoGrupo = trim((string) $codigoGrupo);

        if ($codigoGrupo === "") {
            return "omitido";
        }

        if (ModeloLineaCredito::mdlExisteCierreMensualGrupo($codigoGrupo, $periodo["anio"], $periodo["mes"])) {
            return "omitido";
        }

        $fila = ModeloLineaCredito::mdlGrupoLinea($codigoGrupo);
        $lineaAprobada = ($fila && $fila["linea_aprobada"] !== null) ? $fila["linea_aprobada"] : null;
        $snapshot = self::ctrSnapshotGrupoDesdeIc($codigoGrupo, $lineaAprobada);

        if (!$snapshot) {
            ModeloLineaCredito::mdlRegistrarHistorialGrupo(array(
                "codigo_grupo" => $codigoGrupo,
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
                "usuario_id" => (int) $idUsuario,
            ));

            return "error";
        }

        self::ctrPersistirSnapshotGrupo(
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

        $grupos = ModeloLineaCredito::mdlGruposCarteraActivaPendientesCierre(
            $periodo["anio"],
            $periodo["mes"],
            $limite
        );

        foreach ($grupos as $grupo) {
            $resultadoGrupo = self::ctrProcesarGrupoCierre($grupo["codigo"], $periodo, $idUsuario);

            if ($resultadoGrupo === "procesado") {
                $procesados++;
            } elseif ($resultadoGrupo === "error") {
                $errores++;
            }
        }

        $restantes = ModeloLineaCredito::mdlContarPendientesCierre($periodo["anio"], $periodo["mes"]);
        $restantesGrupos = ModeloLineaCredito::mdlContarGruposPendientesCierre($periodo["anio"], $periodo["mes"]);

        return array(
            "ok" => true,
            "anio" => $periodo["anio"],
            "mes" => $periodo["mes"],
            "procesados_lote" => $procesados,
            "errores_lote" => $errores,
            "restantes" => $restantes + $restantesGrupos,
            "terminado" => ($restantes + $restantesGrupos) === 0,
            "total_cierre" => ($restantes + $restantesGrupos) === 0
                ? ModeloLineaCredito::mdlResumenCierre($periodo["anio"], $periodo["mes"])
                : null,
            "total_cierre_grupos" => ($restantes + $restantesGrupos) === 0
                ? ModeloLineaCredito::mdlResumenCierreGrupo($periodo["anio"], $periodo["mes"])
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
}
