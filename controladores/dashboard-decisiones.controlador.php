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
        $vendedor = self::ctrVendedorSeleccionado();
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

    public static function ctrDatosDashboard()
    {
        ModeloDashboardDecisiones::setVendedorFiltro(self::ctrVendedorSeleccionado());

        return array(
            "pedidos" => self::ctrResumenPedidos(),
            "cartera" => self::ctrResumenCartera(),
            "alertas" => self::ctrAlertasDecision(),
            "top_generados" => self::ctrTopGeneradosPendientes(),
            "generados" => self::ctrPedidosGenerados(),
            "estancados" => self::ctrPedidosEstancados(),
            "atraso" => self::ctrClientesConAtraso(),
            "avance_ventas" => self::ctrAvanceVentasMes(),
        );
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
        $lineaRecomendada = isset($linea["linea_recomendada"]) ? (float) $linea["linea_recomendada"] : 0;
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
                "recomendada" => isset($linea["linea_recomendada"]) ? round((float) $linea["linea_recomendada"], 2) : 0,
                "deuda_actual" => isset($linea["deuda_actual"]) ? round((float) $linea["deuda_actual"], 2) : 0,
                "utilizacion" => isset($linea["utilizacion_pct"]) ? round((float) $linea["utilizacion_pct"], 1) : 0,
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
            "url_completo" => "index.php?ruta=inteligencia-comercial&cliente=" . urlencode($codigoCliente),
        );
    }
}
