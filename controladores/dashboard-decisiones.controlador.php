<?php

class ControladorDashboardDecisiones
{
    public static function ctrVendedorSeleccionado()
    {
        $codigo = isset($_GET["vendedor"]) ? trim((string) $_GET["vendedor"]) : "";

        return ModeloDashboardDecisiones::normalizarVendedorFiltro($codigo);
    }

    public static function ctrVendedoresPermitidos()
    {
        return ModeloDashboardDecisiones::mdlVendedoresPermitidos();
    }

    public static function ctrResumenPedidos()
    {
        return ModeloDashboardDecisiones::mdlResumenPedidos();
    }

    public static function ctrResumenCartera()
    {
        return ModeloDashboardDecisiones::mdlResumenCartera();
    }

    public static function ctrPedidosEstancados()
    {
        return ModeloDashboardDecisiones::mdlPedidosEstancados();
    }

    public static function ctrAlertasDecision()
    {
        return ModeloDashboardDecisiones::mdlAlertasDecision();
    }

    public static function ctrTopGeneradosPendientes($limite = 20)
    {
        return ModeloDashboardDecisiones::mdlTopGeneradosPendientes($limite);
    }

    public static function ctrPedidosGenerados($limite = 30)
    {
        return ModeloDashboardDecisiones::mdlPedidosGenerados($limite);
    }

    public static function ctrPedidosEnProceso($limite = 12)
    {
        return ModeloDashboardDecisiones::mdlPedidosEnProceso($limite);
    }

    public static function ctrClientesConAtraso($limite = 12)
    {
        return ModeloDashboardDecisiones::mdlClientesConAtraso($limite);
    }

    public static function ctrAvanceVentasMes()
    {
        date_default_timezone_set("America/Lima");

        $anio = (int) date("Y");
        $mes = (int) date("n");
        $vendedor = ModeloDashboardDecisiones::getVendedorFiltro();
        if ($vendedor === "") {
            $vendedor = self::ctrVendedorSeleccionado();
        }
        $filas = ModeloMetasVendedor::mdlAvanceVentasDashboard($anio, $mes, $vendedor);

        $totalMeta = 0.0;
        $totalVenta = 0.0;
        $totalPipeline = 0.0;
        $totalProyectado = 0.0;

        foreach ($filas as $fila) {
            $pipeline = (float) $fila["soles_generados"]
                + (float) $fila["soles_aprobados"]
                + (float) $fila["soles_apt"]
                + (float) $fila["soles_confirmados"];

            $totalMeta += (float) $fila["meta_venta"];
            $totalVenta += (float) $fila["venta_real"];
            $totalPipeline += $pipeline;
            $totalProyectado += (float) $fila["venta_real"] + $pipeline;
        }

        $pctGlobal = ($totalMeta > 0) ? round(($totalVenta / $totalMeta) * 100, 1) : 0.0;
        $pctProyectado = ($totalMeta > 0) ? round(($totalProyectado / $totalMeta) * 100, 1) : 0.0;

        return array(
            "anio" => $anio,
            "mes" => $mes,
            "filas" => $filas,
            "total_meta" => round($totalMeta, 2),
            "total_venta" => round($totalVenta, 2),
            "total_pipeline" => round($totalPipeline, 2),
            "total_proyectado" => round($totalProyectado, 2),
            "pct_global" => $pctGlobal,
            "pct_proyectado" => $pctProyectado,
        );
    }

    public static function ctrPedidosRecientes($limite = 8)
    {
        return ModeloDashboardDecisiones::mdlPedidosRecientes($limite);
    }

    public static function ctrResumenFacturadoMes()
    {
        return ModeloDashboardDecisiones::mdlResumenFacturadoMes();
    }

    public static function ctrFacturadoMes($limite = 40)
    {
        return ModeloDashboardDecisiones::mdlFacturadoMes($limite);
    }

    public static function ctrAnularPedidoGenerado($codigoPedido)
    {
        $codigoPedido = trim((string) $codigoPedido);

        if ($codigoPedido === "") {
            return array("ok" => false, "msg" => "Pedido no indicado.");
        }

        if (!isset($_SESSION["id"]) || !(int) $_SESSION["id"]) {
            return array("ok" => false, "msg" => "Sesión no válida.");
        }

        $pedido = ModeloDashboardDecisiones::mdlPedidoParaAnular($codigoPedido);

        if (!$pedido) {
            return array("ok" => false, "msg" => "Pedido no encontrado.");
        }

        if (strtoupper(trim((string) $pedido["estado"])) !== "GENERADO") {
            return array(
                "ok" => false,
                "msg" => "Solo se pueden anular pedidos en estado GENERADO. Estado actual: "
                    . $pedido["estado"],
            );
        }

        $ok = ModeloDashboardDecisiones::mdlAnularPedidoGenerado(
            $codigoPedido,
            (int) $_SESSION["id"]
        );

        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo anular el pedido.");
        }

        return array(
            "ok" => true,
            "msg" => "Pedido anulado correctamente.",
            "codigo" => $pedido["codigo"],
        );
    }

    public static function ctrDatosDashboard()
    {
        $tTotal = microtime(true);
        ModeloDashboardDecisiones::setVendedorFiltro(self::ctrVendedorSeleccionado());

        $timings = array();

        $t = microtime(true);
        $generados = self::ctrPedidosGenerados();
        $timings["generados"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $pedidos = self::ctrResumenPedidos();
        $timings["pedidos"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $cartera = self::ctrResumenCartera();
        $timings["cartera"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $alertas = self::ctrAlertasDecision();
        $timings["alertas"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $topGenerados = self::ctrTopGeneradosPendientes();
        $timings["top_generados"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $generadosEnriquecidos = self::ctrEnriquecerGeneradosConDecision($generados);
        $timings["enriquecer"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $estancados = self::ctrPedidosEstancados();
        $timings["estancados"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $atraso = self::ctrClientesConAtraso();
        $timings["atraso"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $avanceVentas = self::ctrAvanceVentasMes();
        $timings["avance_ventas"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $facturadoResumen = self::ctrResumenFacturadoMes();
        $timings["facturado_resumen"] = round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $facturado = self::ctrFacturadoMes();
        $timings["facturado"] = round((microtime(true) - $t) * 1000);

        $timings["total_datos"] = round((microtime(true) - $tTotal) * 1000);

        if (defined("DD_PERF_LOG") && DD_PERF_LOG) {
            error_log(
                "[DD_PERF] vendedor=" . ModeloDashboardDecisiones::getVendedorFiltro()
                . " " . json_encode($timings)
            );
        }

        return array(
            "pedidos" => $pedidos,
            "cartera" => $cartera,
            "alertas" => $alertas,
            "top_generados" => $topGenerados,
            "generados" => $generadosEnriquecidos,
            "estancados" => $estancados,
            "atraso" => $atraso,
            "avance_ventas" => $avanceVentas,
            "facturado_resumen" => $facturadoResumen,
            "facturado" => $facturado,
        );
    }

    private static function ctrEnriquecerGeneradosConDecision(array $generados)
    {
        if (empty($generados)) {
            return $generados;
        }

        $codigos = array();

        foreach ($generados as $row) {
            $codigos[] = (int) $row["codigo"];
        }

        try {
            $decisiones = ModeloDecisionesCredito::mdlDecisionesVigentesPorPedidos($codigos);
        } catch (Exception $e) {
            $decisiones = array();
        }

        foreach ($generados as $idx => $row) {
            $codigoPedido = (int) $row["codigo"];
            $decision = isset($decisiones[$codigoPedido]) ? $decisiones[$codigoPedido] : null;

            if ($decision) {
                $decision = ModeloDecisionesCredito::mdlEnriquecerDecision($decision);
            }

            $generados[$idx]["decision_credito"] = $decision;
        }

        return $generados;
    }

    /**
     * Resumen compacto de Inteligencia Comercial para el modal del dashboard.
     */
    public static function ctrMiniInteligenciaCliente($codigoCliente, $codigoPedido = "")
    {
        $codigoCliente = trim((string) $codigoCliente);
        $codigoPedido = trim((string) $codigoPedido);

        if ($codigoCliente === "") {
            return array("ok" => false, "msg" => "Cliente no indicado.");
        }

        $analisis = ControladorInteligenciaComercial::ctrCalcularAnalisisCompleto($codigoCliente);

        if (empty($analisis["motor1"])) {
            return array("ok" => false, "msg" => "No se encontró información del cliente.");
        }

        $m1 = $analisis["motor1"];
        $m2 = $analisis["motor2"];
        $m3 = $analisis["motor3"];
        $m4 = $analisis["motor4"];
        $metricas1 = isset($m1["metricas"]) ? $m1["metricas"] : array();
        $linea = isset($m3["linea"]) ? $m3["linea"] : array();
        $accion = isset($m3["accion"]) ? $m3["accion"] : array();
        $metricas2 = ($m2 && isset($m2["metricas"])) ? $m2["metricas"] : array();

        $totalDocs = isset($metricas1["total_docs"]) ? (int) $metricas1["total_docs"] : 0;
        $docsTiempo = isset($metricas1["docs_a_tiempo"]) ? (int) $metricas1["docs_a_tiempo"] : 0;
        $historialPct = $totalDocs > 0 ? round(($docsTiempo / $totalDocs) * 100, 1) : null;
        $pedidoDetalle = null;

        if ($codigoPedido !== "") {
            $pedidoDetalle = ModeloDashboardDecisiones::mdlPedidoMini($codigoPedido, $codigoCliente);
        }

        $deudaActual = isset($linea["deuda_actual"]) ? (float) $linea["deuda_actual"] : 0;
        $lineaRecomendada = isset($linea["linea_recomendada"])
            ? (function_exists("icRedondearLineaCredito")
                ? icRedondearLineaCredito((float) $linea["linea_recomendada"])
                : (float) $linea["linea_recomendada"])
            : 0;

        $refCupoPedido = class_exists("ControladorLineaCredito")
            ? ControladorLineaCredito::ctrReferenciaCupoPedido($codigoCliente, $lineaRecomendada, $deudaActual)
            : null;

        if ($refCupoPedido) {
            $deudaActual = (float) $refCupoPedido["deuda_actual"];
            $lineaRecomendada = (float) $refCupoPedido["linea_recomendada"];
            $disponible = (float) $refCupoPedido["cupo_disponible"];
            $refCupo = array(
                "disponible_nuevo_credito" => $disponible,
                "excedido_sobre_recomendada" => (float) $refCupoPedido["excedido_sobre_recomendada"],
                "cupo_agotado" => !empty($refCupoPedido["cupo_agotado"]),
                "tiene_excedido" => (float) $refCupoPedido["excedido_sobre_recomendada"] > 0,
            );
        } else {
            $refCupo = function_exists("icCalcularReferenciaCupoLinea")
                ? icCalcularReferenciaCupoLinea($deudaActual, $lineaRecomendada)
                : array(
                    "disponible_nuevo_credito" => max(0, $lineaRecomendada - $deudaActual),
                    "excedido_sobre_recomendada" => max(0, $deudaActual - $lineaRecomendada),
                    "cupo_agotado" => false,
                    "tiene_excedido" => false,
                );

            $disponible = isset($refCupo["disponible_nuevo_credito"])
                ? round((float) $refCupo["disponible_nuevo_credito"], 2)
                : 0;
        }

        $utilizacionLinea = $refCupoPedido
            ? (float) $refCupoPedido["utilizacion_pct"]
            : (isset($linea["utilizacion_pct"]) ? round((float) $linea["utilizacion_pct"], 1) : 0);

        $urlCompleto = "index.php?ruta=inteligencia-comercial&cliente=" . urlencode($codigoCliente);

        if ($refCupoPedido && $refCupoPedido["modo"] === "grupo" && !empty($refCupoPedido["grupo"]["codigo"])) {
            $urlCompleto = "index.php?ruta=inteligencia-comercial&modo=grupo&grupo="
                . urlencode($refCupoPedido["grupo"]["codigo"]);
        }

        $pedidoTotal = ($pedidoDetalle && isset($pedidoDetalle["total"])) ? (float) $pedidoDetalle["total"] : 0;
        $cupoSuficiente = null;

        if ($pedidoDetalle) {
            $cupoSuficiente = $disponible >= $pedidoTotal;
        }

        return array(
            "ok" => true,
            "pedido" => $codigoPedido,
            "pedido_detalle" => $pedidoDetalle,
            "decision" => array(
                "cupo_suficiente" => $cupoSuficiente,
                "pedido_total" => $pedidoTotal,
                "cupo_disponible" => $disponible,
                "excedido_sobre_recomendada" => isset($refCupo["excedido_sobre_recomendada"])
                    ? round((float) $refCupo["excedido_sobre_recomendada"], 2)
                    : 0,
                "cupo_agotado" => !empty($refCupo["cupo_agotado"]),
                "modo_cupo" => $refCupoPedido ? $refCupoPedido["modo"] : "cliente",
                "grupo" => ($refCupoPedido && !empty($refCupoPedido["grupo"])) ? $refCupoPedido["grupo"] : null,
                "linea_referencia" => $refCupoPedido ? (float) $refCupoPedido["linea_referencia"] : $lineaRecomendada,
                "etiqueta_linea" => $refCupoPedido ? $refCupoPedido["etiqueta_linea"] : "Recomendada IC",
            ),
            "cliente" => array(
                "codigo" => $m1["cliente"]["codigo"],
                "nombre" => $m1["cliente"]["nombre"],
            ),
            "riesgo" => array(
                "score" => round((float) $m1["score"], 1),
                "etiqueta" => $m1["clasificacion"]["etiqueta"],
                "color" => $m1["clasificacion"]["color"],
                "atraso_promedio" => isset($metricas1["atraso_promedio"]) ? round((float) $metricas1["atraso_promedio"], 1) : 0,
                "deuda" => isset($metricas1["total_deuda"]) ? round((float) $metricas1["total_deuda"], 2) : 0,
                "docs_vencidos" => isset($metricas1["pendientes_vencidos"]) ? (int) $metricas1["pendientes_vencidos"] : 0,
                "historial_pct" => $historialPct,
                "incidencias" => isset($metricas1["incidencias"]) ? (int) $metricas1["incidencias"] : 0,
            ),
            "linea" => array(
                "recomendada" => $lineaRecomendada,
                "aprobada" => ($refCupoPedido && !empty($refCupoPedido["linea_aprobada"]))
                    ? round((float) $refCupoPedido["linea_aprobada"], 2)
                    : null,
                "referencia" => $refCupoPedido ? round((float) $refCupoPedido["linea_referencia"], 2) : $lineaRecomendada,
                "etiqueta_referencia" => $refCupoPedido ? $refCupoPedido["etiqueta_linea"] : "Recomendada IC",
                "modo" => $refCupoPedido ? $refCupoPedido["modo"] : "cliente",
                "grupo" => ($refCupoPedido && !empty($refCupoPedido["grupo"])) ? $refCupoPedido["grupo"] : null,
                "deuda_actual" => round($deudaActual, 2),
                "utilizacion" => $utilizacionLinea,
                "accion" => isset($accion["etiqueta"]) ? $accion["etiqueta"] : "—",
                "accion_color" => isset($accion["color"]) ? $accion["color"] : "default",
                "explicacion" => isset($accion["explicacion"]) ? $accion["explicacion"] : "",
                "disponible" => $disponible,
                "excedido" => isset($refCupo["excedido_sobre_recomendada"])
                    ? round((float) $refCupo["excedido_sobre_recomendada"], 2)
                    : 0,
            ),
            "comercial" => array(
                "score" => $m2 ? round((float) $m2["score"], 1) : null,
                "etiqueta" => $m2 ? $m2["clasificacion"]["etiqueta"] : "Sin datos",
                "ultima_compra" => isset($metricas2["ultima_compra"]) ? $metricas2["ultima_compra"] : null,
                "vendedor" => isset($metricas2["vendedor"]) ? $metricas2["vendedor"] : null,
            ),
            "fidelidad" => array(
                "score" => $m4 ? round((float) $m4["score"], 1) : null,
                "etiqueta" => $m4 ? $m4["clasificacion"]["etiqueta"] : "Sin datos",
            ),
            "url_completo" => $urlCompleto,
        );
    }
}
