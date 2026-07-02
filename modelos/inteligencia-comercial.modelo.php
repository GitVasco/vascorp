<?php

class ModeloInteligenciaComercial
{
    /**
     * Clientes activos con movimientos en cuenta corriente.
     */
    public static function mdlClientesAnalisis()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT
                c.codigo,
                c.nombre
            FROM clientesjf c
            INNER JOIN cuenta_ctejf ct ON ct.cliente = c.codigo
            WHERE c.estado = 1
              AND c.fecha IS NOT NULL
            ORDER BY c.nombre ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Métricas crudas del cliente para el Motor 1.
     */
    public static function mdlMetricasMotorRiesgo($codigoCliente)
    {
        $cfg = icConfigMotor1();
        $tolerancia = (int) $cfg["tolerancia_dias_pago"];
        $mesesTendencia = (int) $cfg["tendencia_meses_periodo"];
        $mesesTendenciaTotal = $mesesTendencia * 2;
        $tiposSql = icVentasTiposValidosSql();

        $stmt = Conexion::conectar()->prepare("
            SELECT
                cli.codigo,
                cli.nombre,
                cli.fecreg,
                vta.fecha_primera_venta,
                IFNULL(
                    TIMESTAMPDIFF(
                        MONTH,
                        COALESCE(vta.fecha_primera_venta, cli.fecreg),
                        NOW()
                    ),
                    0
                ) AS meses_antiguedad,
                IFNULL(doc.total_docs, 0) AS total_docs,
                IFNULL(doc.docs_a_tiempo, 0) AS docs_a_tiempo,
                IFNULL(doc.docs_cerrados, 0) AS docs_cerrados,
                IFNULL(doc.pendientes_vencidos, 0) AS pendientes_vencidos,
                IFNULL(doc.pendientes_fuera_tolerancia, 0) AS pendientes_fuera_tolerancia,
                IFNULL(doc.atraso_promedio, 0) AS atraso_promedio,
                IFNULL(doc.atraso_reciente, 0) AS atraso_reciente,
                IFNULL(doc.atraso_anterior, 0) AS atraso_anterior,
                IFNULL(deuda.total_deuda, 0) AS total_deuda,
                IFNULL(credito.total_credito, 0) AS total_credito,
                IFNULL(inc.incidencias, 0) AS incidencias
            FROM clientesjf cli
            LEFT JOIN (
                SELECT
                    c.cliente,
                    SUM(CASE WHEN UPPER(c.estado) = 'CANCELADO' THEN 1 ELSE 0 END) AS docs_cerrados,
                    SUM(
                        CASE
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND c.fecha_ven < CURDATE()
                                THEN 1
                            ELSE 0
                        END
                    ) AS pendientes_vencidos,
                    SUM(
                        CASE
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND c.fecha_ven < CURDATE()
                                AND DATEDIFF(CURDATE(), c.fecha_ven) > :tolerancia_pend
                                THEN 1
                            ELSE 0
                        END
                    ) AS pendientes_fuera_tolerancia,
                    COUNT(*) AS total_docs,
                    SUM(
                        CASE
                            WHEN UPPER(c.estado) = 'CANCELADO'
                                AND c.ult_pago IS NOT NULL
                                AND GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven), 0) <= :tolerancia_docs
                                THEN 1
                            WHEN UPPER(c.estado) = 'CANCELADO'
                                AND c.ult_pago IS NULL
                                AND IFNULL(c.saldo, 0) = 0
                                THEN 1
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND (
                                    c.fecha_ven >= CURDATE()
                                    OR DATEDIFF(CURDATE(), c.fecha_ven) <= :tolerancia_pend_hist
                                )
                                THEN 1
                            ELSE 0
                        END
                    ) AS docs_a_tiempo,
                    AVG(
                        CASE
                            WHEN UPPER(c.estado) = 'CANCELADO' AND c.ult_pago IS NOT NULL THEN
                                GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_atraso, 0)
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND c.fecha_ven < CURDATE()
                                THEN GREATEST(DATEDIFF(CURDATE(), c.fecha_ven) - :tolerancia_atraso_pend, 0)
                            ELSE NULL
                        END
                    ) AS atraso_promedio,
                    AVG(
                        CASE
                            WHEN UPPER(c.estado) = 'CANCELADO'
                                AND c.ult_pago IS NOT NULL
                                AND c.ult_pago >= DATE_SUB(NOW(), INTERVAL {$mesesTendencia} MONTH)
                                THEN GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_reciente, 0)
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND c.fecha_ven < CURDATE()
                                AND c.fecha_ven >= DATE_SUB(NOW(), INTERVAL {$mesesTendencia} MONTH)
                                THEN GREATEST(DATEDIFF(CURDATE(), c.fecha_ven) - :tolerancia_reciente_pend, 0)
                            ELSE NULL
                        END
                    ) AS atraso_reciente,
                    AVG(
                        CASE
                            WHEN UPPER(c.estado) = 'CANCELADO'
                                AND c.ult_pago IS NOT NULL
                                AND c.ult_pago >= DATE_SUB(NOW(), INTERVAL {$mesesTendenciaTotal} MONTH)
                                AND c.ult_pago < DATE_SUB(NOW(), INTERVAL {$mesesTendencia} MONTH)
                                THEN GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_anterior, 0)
                            WHEN UPPER(c.estado) = 'PENDIENTE'
                                AND IFNULL(c.saldo, 0) > 0
                                AND c.fecha_ven < CURDATE()
                                AND c.fecha_ven >= DATE_SUB(NOW(), INTERVAL {$mesesTendenciaTotal} MONTH)
                                AND c.fecha_ven < DATE_SUB(NOW(), INTERVAL {$mesesTendencia} MONTH)
                                THEN GREATEST(DATEDIFF(CURDATE(), c.fecha_ven) - :tolerancia_anterior_pend, 0)
                            ELSE NULL
                        END
                    ) AS atraso_anterior
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '+'
                  AND (
                    UPPER(c.estado) = 'CANCELADO'
                    OR (
                        UPPER(c.estado) = 'PENDIENTE'
                        AND IFNULL(c.saldo, 0) > 0
                    )
                  )
                  AND c.cliente = :cliente_docs
                GROUP BY c.cliente
            ) doc ON doc.cliente = cli.codigo
            LEFT JOIN (
                SELECT cliente, SUM(saldo) AS total_deuda
                FROM cuenta_ctejf
                WHERE tip_mov = '+'
                  AND UPPER(estado) = 'PENDIENTE'
                  AND cliente = :cliente_deuda
                GROUP BY cliente
            ) deuda ON deuda.cliente = cli.codigo
            LEFT JOIN (
                SELECT cliente, SUM(monto) AS total_credito
                FROM cuenta_ctejf
                WHERE tip_mov = '+'
                  AND cliente = :cliente_credito
                GROUP BY cliente
            ) credito ON credito.cliente = cli.codigo
            LEFT JOIN (
                SELECT cliente, COUNT(*) AS incidencias
                FROM cuenta_ctejf
                WHERE tip_mov = '+'
                  AND cliente = :cliente_inc
                  AND (IFNULL(protesta, 0) > 0 OR IFNULL(renovacion, 0) > 0)
                GROUP BY cliente
            ) inc ON inc.cliente = cli.codigo
            LEFT JOIN (
                SELECT cliente, MIN(fecha) AS fecha_primera_venta
                FROM ventajf
                WHERE cliente = :cliente_vta
                  AND fecha IS NOT NULL
                  AND UPPER(IFNULL(estado, '')) <> 'ANULADO'
                  AND UPPER(tipo) IN ({$tiposSql})
                GROUP BY cliente
            ) vta ON vta.cliente = cli.codigo
            WHERE cli.codigo = :cliente
            LIMIT 1
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_docs", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_deuda", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_credito", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_inc", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_vta", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":tolerancia_docs", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_pend", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_pend_hist", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_atraso", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_atraso_pend", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_reciente", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_reciente_pend", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_anterior", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_anterior_pend", $tolerancia, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Convierte métricas en sub-scores (0-100) y score ponderado final.
     */
    public static function mdlCalcularMotorRiesgoCredito($codigoCliente, $lineaRecomendadaMotor3 = null)
    {
        $cfg = icConfigMotor1();
        $pesos = icPesosDecimalesMotor1();
        $pesosEfectivos = $cfg["pesos_efectivos"];
        $equifaxActivo = $cfg["equifax_activo"];
        $tolerancia = (int) $cfg["tolerancia_dias_pago"];
        $mesesTendencia = (int) $cfg["tendencia_meses_periodo"];
        $mesesTendenciaTotal = $mesesTendencia * 2;
        $multAtraso = (float) $cfg["atraso_multiplicador"];
        $penalIncidencia = (int) $cfg["incidencia_penalizacion"];
        $scoreNeutro = (int) $cfg["score_neutro"];

        $metricas = self::mdlMetricasMotorRiesgo($codigoCliente);

        if (!$metricas) {
            return null;
        }

        $totalDocs = (int) $metricas["total_docs"];
        $docsATiempo = (int) $metricas["docs_a_tiempo"];
        $docsCerrados = (int) $metricas["docs_cerrados"];
        $pendientesVencidos = (int) $metricas["pendientes_vencidos"];
        $pendientesFueraTolerancia = (int) $metricas["pendientes_fuera_tolerancia"];
        $atrasoPromedio = (float) $metricas["atraso_promedio"];
        $atrasoReciente = $metricas["atraso_reciente"] !== null ? (float) $metricas["atraso_reciente"] : null;
        $atrasoAnterior = $metricas["atraso_anterior"] !== null ? (float) $metricas["atraso_anterior"] : null;
        $totalDeuda = (float) $metricas["total_deuda"];
        $totalCredito = (float) $metricas["total_credito"];
        $mesesAntiguedad = (int) $metricas["meses_antiguedad"];
        $incidencias = (int) $metricas["incidencias"];
        $utilizacion = 0;

        $pesoHistorial = (int) $pesosEfectivos["historial_pagos"];
        $pesoAtraso = (int) $pesosEfectivos["dias_atraso"];
        $pesoUtilizacion = (int) $pesosEfectivos["utilizacion_linea"];
        $pesoAntiguedad = (int) $pesosEfectivos["antiguedad"];
        $pesoTendencia = (int) $pesosEfectivos["tendencia_pago"];
        $pesoIncidencias = (int) $pesosEfectivos["incidencias"];

        // Historial — cerrados + pendientes con saldo (vencidos fuera de tolerancia cuentan en contra)
        if ($totalDocs > 0) {
            $scoreHistorial = round(($docsATiempo / $totalDocs) * 100, 2);
            $detalleHistorial = round(($docsATiempo / $totalDocs) * 100, 1)
                . "% cumplimiento ($docsATiempo de $totalDocs documentos).";
            if ($pendientesFueraTolerancia > 0) {
                $detalleHistorial .= " Incluye $pendientesFueraTolerancia pendiente(s) vencido(s) fuera de tolerancia.";
            }
        } else {
            $scoreHistorial = $scoreNeutro;
            $detalleHistorial = "Sin documentos evaluables; score neutro ($scoreNeutro).";
        }

        // Días promedio de atraso (cerrados + pendientes vencidos hoy)
        $scoreAtraso = round(max(0, min(100, 100 - ($atrasoPromedio * $multAtraso))), 2);
        $detalleAtraso = "Promedio de " . round($atrasoPromedio, 1) . " días penalizables (cerrados pagados + pendientes vencidos hoy).";

        // Utilización: deuda vs línea recomendada (Motor 3) o línea operativa como respaldo
        $lineaCredito = self::mdlLineaCreditoOperativa($codigoCliente);
        $lineaOperativa = (float) $lineaCredito["linea_operativa"];
        $picoHistorico = (float) $lineaCredito["pico_historico"];
        $lineaRecomendada = $lineaRecomendadaMotor3 !== null ? (float) $lineaRecomendadaMotor3 : 0.0;
        $usaLineaRecomendada = $lineaRecomendada > 0;
        $lineaReferencia = $usaLineaRecomendada
            ? $lineaRecomendada
            : max($lineaOperativa, $totalDeuda);
        $etiquetaLinea = $usaLineaRecomendada ? "línea recomendada (Motor 3)" : "línea operativa";

        if ($lineaReferencia <= 0) {
            $utilizacion = 0;
            $scoreUtilizacion = $totalDeuda > 0 ? $scoreNeutro : 100;
            $detalleUtilizacion = $totalDeuda > 0
                ? "Deuda S/ " . number_format($totalDeuda, 2) . " sin línea de referencia definida."
                : "Sin deuda ni línea de referencia; utilización 0%.";
        } else {
            $utilizacion = ($totalDeuda / $lineaReferencia) * 100;
            $scoreUtilizacion = icScorePorTramos($utilizacion, $cfg["utilizacion_tramos"]);
            $detalleUtilizacion = "Deuda S/ " . number_format($totalDeuda, 2)
                . " sobre " . $etiquetaLinea . " S/ " . number_format($lineaReferencia, 2)
                . " (" . round($utilizacion, 1) . "%).";
        }

        // Antigüedad — prioridad: primera venta (ventajf), respaldo: fecreg
        $fechaPrimeraVenta = $metricas["fecha_primera_venta"];
        $fechaReferenciaAntiguedad = $fechaPrimeraVenta ?: $metricas["fecreg"];

        $scoreAntiguedad = icScorePorTramos($mesesAntiguedad, $cfg["antiguedad_tramos"]);

        if ($fechaReferenciaAntiguedad) {
            $detalleAntiguedad = $mesesAntiguedad . " meses desde "
                . ($fechaPrimeraVenta ? "su primera venta" : "su registro en el sistema")
                . " (" . date("d/m/Y", strtotime($fechaReferenciaAntiguedad)) . ").";
        } else {
            $detalleAntiguedad = "Sin primera venta ni registro; se aplica el tramo mínimo.";
        }

        // Tendencia de pago
        $tendCfg = $cfg["tendencia"];
        $moraLeveMax = (float) $cfg["tendencia_mora_leve_max"];
        $antiguedadMinTendencia = (int) $cfg["tendencia_antiguedad_comparativa"];
        $resultadoTendencia = icClasificarTendenciaPago($cfg, $atrasoReciente, $atrasoAnterior, $mesesAntiguedad);
        $scoreTendencia = (int) $resultadoTendencia["score"];
        $clasificacionTendencia = $resultadoTendencia["clasificacion"];
        $detalleTendencia = $resultadoTendencia["detalle"];

        // Equifax (solo si está activo en config)
        $scoreEquifax = $scoreNeutro;
        $detalleEquifax = "Sin registro Equifax en el sistema; score neutro ($scoreNeutro).";

        if (!$equifaxActivo) {
            $detalleEquifax = "Factor Equifax desactivado; se aplican los pesos_sin_equifax de configuración.";
        }

        // Incidencias
        $scoreIncidencias = max(0, 100 - ($incidencias * $penalIncidencia));
        $detalleIncidencias = $incidencias === 0
            ? "Sin protestas ni renovaciones registradas."
            : "$incidencias incidencia(s) comercial(es) detectada(s).";

        $reglaUtilizacion = implode(" | ", array_map(function ($t) {
            return "≤" . $t["hasta"] . "% → " . $t["score"];
        }, $cfg["utilizacion_tramos"]));

        $reglaAntiguedad = implode(" | ", array_map(function ($t) {
            return "≤" . $t["hasta"] . "m → " . $t["score"];
        }, $cfg["antiguedad_tramos"]));

        $factores = array(
            "historial_pagos" => array(
                "clave" => "historial_pagos",
                "nombre" => "Historial de pagos",
                "icono" => "fa-check-circle",
                "peso" => $pesoHistorial,
                "score" => $scoreHistorial,
                "detalle" => $detalleHistorial,
                "formula" => "Score = (documentos al día ÷ total evaluados) × 100",
                "regla" => "Cerrados: a tiempo si atraso ≤ $tolerancia días. Pendientes: vigentes o en gracia cuentan bien; vencidos fuera de tolerancia cuentan en contra.",
                "valores" => array(
                    array("etiqueta" => "Total evaluados", "valor" => (string) $totalDocs),
                    array("etiqueta" => "Al día / a tiempo", "valor" => (string) $docsATiempo),
                    array("etiqueta" => "Cerrados", "valor" => (string) $docsCerrados),
                    array("etiqueta" => "Pendientes vencidos", "valor" => (string) $pendientesVencidos),
                    array("etiqueta" => "Pend. fuera tolerancia", "valor" => (string) $pendientesFueraTolerancia),
                    array("etiqueta" => "Tolerancia", "valor" => $tolerancia . " días"),
                ),
                "tabla_logica" => icTablaLogicaHistorial($tolerancia, $docsATiempo, $totalDocs, $scoreHistorial),
            ),
            "dias_atraso" => array(
                "clave" => "dias_atraso",
                "nombre" => "Días promedio de atraso",
                "icono" => "fa-clock-o",
                "peso" => $pesoAtraso,
                "score" => $scoreAtraso,
                "detalle" => $detalleAtraso,
                "formula" => "Atraso penalizable = máx(0, días − $tolerancia). Promedio de cerrados + pendientes vencidos hoy.",
                "regla" => "Incluye mora actual de facturas pendientes ya vencidas (aunque no estén pagadas).",
                "valores" => array(
                    array("etiqueta" => "Atraso penalizable promedio", "valor" => round($atrasoPromedio, 1) . " días"),
                    array("etiqueta" => "Pendientes vencidos hoy", "valor" => (string) $pendientesVencidos),
                    array("etiqueta" => "Tolerancia", "valor" => $tolerancia . " días"),
                    array("etiqueta" => "Multiplicador", "valor" => (string) $multAtraso),
                ),
                "tabla_logica" => icTablaLogicaDiasAtraso($multAtraso, $tolerancia, $atrasoPromedio, $scoreAtraso),
            ),
            "utilizacion_linea" => array(
                "clave" => "utilizacion_linea",
                "nombre" => "Utilización de línea de crédito",
                "icono" => "fa-pie-chart",
                "peso" => $pesoUtilizacion,
                "score" => $scoreUtilizacion,
                "detalle" => $detalleUtilizacion,
                "formula" => "Utilización = deuda pendiente ÷ línea de referencia × 100",
                "regla" => $reglaUtilizacion . ". Prioridad: línea recomendada del Motor 3; si no hay, línea operativa (pico histórico).",
                "valores" => array(
                    array("etiqueta" => "Deuda pendiente", "valor" => "S/ " . number_format($totalDeuda, 2)),
                    array("etiqueta" => "Línea de referencia", "valor" => "S/ " . number_format($lineaReferencia, 2)),
                    array("etiqueta" => "Tipo de línea", "valor" => $usaLineaRecomendada ? "Recomendada (Motor 3)" : "Operativa (pico histórico)"),
                    array("etiqueta" => "Pico histórico", "valor" => "S/ " . number_format($picoHistorico, 2)),
                    array("etiqueta" => "Utilización", "valor" => round($utilizacion, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Tramos de utilización",
                    "Utilización = deuda pendiente ÷ " . ($usaLineaRecomendada ? "línea recomendada del Motor 3" : "línea operativa (máxima deuda tolerada)") . ".",
                    "%",
                    round($utilizacion, 1),
                    $cfg["utilizacion_tramos"],
                    $scoreUtilizacion
                ),
            ),
            "antiguedad" => array(
                "clave" => "antiguedad",
                "nombre" => "Antigüedad",
                "icono" => "fa-calendar",
                "peso" => $pesoAntiguedad,
                "score" => $scoreAntiguedad,
                "detalle" => $detalleAntiguedad,
                "formula" => "Meses = desde primera venta (ventajf) hasta hoy; score por tramos",
                "regla" => $reglaAntiguedad . ". Fuente: primera venta (tipos "
                    . icVentasTiposValidosTexto() . "); si no existe, fecreg del ERP.",
                "valores" => array(
                    array("etiqueta" => "Meses de antigüedad", "valor" => (string) $mesesAntiguedad),
                    array("etiqueta" => "Primera venta", "valor" => $fechaPrimeraVenta ? date("d/m/Y", strtotime($fechaPrimeraVenta)) : "Sin ventas"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Tramos de antigüedad",
                    "Meses desde la primera venta registrada (ventajf) hasta hoy.",
                    " meses",
                    $mesesAntiguedad,
                    $cfg["antiguedad_tramos"],
                    $scoreAntiguedad
                ),
            ),
            "tendencia_pago" => array(
                "clave" => "tendencia_pago",
                "nombre" => "Tendencia de pago",
                "icono" => "fa-line-chart",
                "peso" => $pesoTendencia,
                "score" => $scoreTendencia,
                "detalle" => $detalleTendencia,
                "formula" => "Compara atraso penalizable últimos {$mesesTendencia}m vs anteriores (pagos cerrados + pendientes vencidos por fecha de vencimiento)",
                "regla" => "Atraso penalizable = máx(0, días − $tolerancia). Mora leve inicial (≤ {$moraLeveMax} días sin historial previo) → estable. "
                    . "Cliente nuevo (< {$antiguedadMinTendencia} meses) con mora mayor → neutro. Mejorando (<" . ($tendCfg["mejorando"]["factor"] * 100) . "% del anterior) → " . $tendCfg["mejorando"]["score"]
                    . " | Estable → " . $tendCfg["estable"]["score"]
                    . " | Empeorando → " . $tendCfg["empeorando"]["score"],
                "valores" => array(
                    array("etiqueta" => "Periodo configurado", "valor" => $mesesTendencia . " meses"),
                    array("etiqueta" => "Antigüedad del cliente", "valor" => $mesesAntiguedad . " meses"),
                    array("etiqueta" => "Atraso penalizable reciente ({$mesesTendencia}m)", "valor" => $atrasoReciente !== null ? round($atrasoReciente, 1) . " días" : "Sin datos"),
                    array("etiqueta" => "Atraso penalizable anterior ({$mesesTendencia}m)", "valor" => $atrasoAnterior !== null ? round($atrasoAnterior, 1) . " días" : "Sin datos"),
                    array("etiqueta" => "Umbral mora leve", "valor" => $moraLeveMax . " días"),
                    array("etiqueta" => "Tolerancia descontada", "valor" => $tolerancia . " días"),
                ),
                "tabla_logica" => icTablaLogicaTendencia($cfg, $mesesTendencia, $clasificacionTendencia),
            ),
        );

        if ($equifaxActivo) {
            $pesoEquifax = (int) $pesosEfectivos["equifax"];
            $factores["equifax"] = array(
                "clave" => "equifax",
                "nombre" => "Equifax",
                "icono" => "fa-university",
                "peso" => $pesoEquifax,
                "score" => $scoreEquifax,
                "detalle" => $detalleEquifax,
                "formula" => "Pendiente integración — score neutro $scoreNeutro",
                "regla" => "Cuando exista fuente Equifax, este factor usará el score externo del buró.",
                "valores" => array(
                    array("etiqueta" => "Estado", "valor" => "Sin datos en sistema"),
                    array("etiqueta" => "Score asignado", "valor" => "$scoreNeutro (neutro)"),
                ),
                "tabla_logica" => icTablaLogicaEquifax($scoreNeutro),
            );
        }

        $factores["incidencias"] = array(
                "clave" => "incidencias",
                "nombre" => "Incidencias comerciales",
                "icono" => "fa-exclamation-triangle",
                "peso" => $pesoIncidencias,
                "score" => $scoreIncidencias,
                "detalle" => $detalleIncidencias,
                "formula" => "Score = máx(0, 100 − (incidencias × $penalIncidencia))",
                "regla" => "Cada protesta o renovación registrada resta $penalIncidencia puntos.",
                "valores" => array(
                    array("etiqueta" => "Incidencias detectadas", "valor" => (string) $incidencias),
                    array("etiqueta" => "Penalización", "valor" => ($incidencias * $penalIncidencia) . " pts"),
                ),
                "tabla_logica" => icTablaLogicaIncidencias($penalIncidencia, $incidencias, $scoreIncidencias),
        );

        foreach ($factores as $clave => &$factor) {
            $factor["score"] = round($factor["score"], 1);
            $factor["aportacion"] = round($factor["score"] * $pesos[$clave], 2);
        }
        unset($factor);

        $scoreFinal = 0;
        foreach ($factores as $clave => $factor) {
            $scoreFinal += $factor["score"] * $pesos[$clave];
        }
        $scoreFinal = round($scoreFinal, 2);

        return array(
            "cliente" => array(
                "codigo" => $metricas["codigo"],
                "nombre" => $metricas["nombre"],
            ),
            "motor" => 1,
            "nombre_motor" => "Score de Riesgo Crediticio",
            "score" => $scoreFinal,
            "clasificacion" => icClasificarScore($scoreFinal),
            "factores" => $factores,
            "metricas" => array(
                "total_docs" => $totalDocs,
                "docs_a_tiempo" => $docsATiempo,
                "tolerancia_dias" => $tolerancia,
                "atraso_promedio" => round($atrasoPromedio, 2),
                "utilizacion_pct" => round($utilizacion, 2),
                "total_deuda" => $totalDeuda,
                "linea_operativa" => round($lineaOperativa, 2),
                "linea_recomendada_m3" => $usaLineaRecomendada ? round($lineaRecomendada, 2) : null,
                "linea_referencia_utilizacion" => round($lineaReferencia, 2),
                "total_credito" => $totalCredito,
                "meses_antiguedad" => $mesesAntiguedad,
                "incidencias" => $incidencias,
            ),
        );
    }

    /**
     * Lista SQL segura para cláusulas IN (valores validados desde config).
     */
    private static function mdlSqlIn($valores)
    {
        $items = array();

        foreach ($valores as $valor) {
            $items[] = "'" . addslashes((string) $valor) . "'";
        }

        return implode(", ", $items);
    }

    /**
     * Línea de crédito operativa: pico de deuda simultánea reconstruido desde cuenta corriente.
     * No existe cupo maestro en clientesjf; este es el máximo crédito que el negocio toleró en la práctica.
     */
    public static function mdlLineaCreditoOperativa($codigoCliente)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT tip_mov, monto
            FROM cuenta_ctejf
            WHERE cliente = :cliente
            ORDER BY COALESCE(fecha_creacion, STR_TO_DATE(fecha, '%Y-%m-%d'), fecha) ASC, id ASC
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();
        $movimientos = $stmt->fetchAll();

        $deuda = 0.0;
        $pico = 0.0;

        foreach ($movimientos as $mov) {
            $monto = abs((float) $mov["monto"]);

            if (trim((string) $mov["tip_mov"]) === "-") {
                $deuda -= $monto;
            } else {
                $deuda += $monto;
            }

            if ($deuda < 0) {
                $deuda = 0;
            }

            if ($deuda > $pico) {
                $pico = $deuda;
            }
        }

        $stmtDeuda = Conexion::conectar()->prepare("
            SELECT IFNULL(SUM(saldo), 0) AS total_deuda
            FROM cuenta_ctejf
            WHERE cliente = :cliente
              AND tip_mov = '+'
              AND UPPER(estado) = 'PENDIENTE'
              AND IFNULL(saldo, 0) > 0
        ");
        $stmtDeuda->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmtDeuda->execute();
        $deudaActual = (float) $stmtDeuda->fetchColumn();

        $lineaOperativa = max($pico, $deudaActual);

        return array(
            "pico_historico"   => round($pico, 2),
            "deuda_actual"     => round($deudaActual, 2),
            "linea_operativa"  => round($lineaOperativa, 2),
            "movimientos"      => count($movimientos),
        );
    }

    /**
     * Métricas crudas del cliente para el Motor 3 (línea de crédito).
     */
    public static function mdlMetricasMotorLineaCredito($codigoCliente)
    {
        $cfg = icConfigMotor3();
        $meses = (int) $cfg["meses_periodo"];
        $mesesLargo = isset($cfg["meses_memoria_larga"]) ? (int) $cfg["meses_memoria_larga"] : 12;
        $tiposSql = icVentasTiposValidosSql();
        $vendExclSql = self::mdlSqlIn(icConfigMotor2()["ventas_excluir_vendedores"]);
        $linea = self::mdlLineaCreditoOperativa($codigoCliente);

        $stmt = Conexion::conectar()->prepare("
            SELECT
                cli.codigo,
                cli.nombre,
                IFNULL(
                    TIMESTAMPDIFF(
                        MONTH,
                        COALESCE(vta.fecha_primera_venta, cli.fecreg),
                        NOW()
                    ),
                    0
                ) AS meses_antiguedad,
                IFNULL(vta.monto_reciente, 0) AS monto_reciente,
                IFNULL(vta.monto_anterior, 0) AS monto_anterior,
                IFNULL(vta.compra_maxima, 0) AS compra_maxima,
                IFNULL(vta.compra_maxima_12m, 0) AS compra_maxima_12m,
                IFNULL(vta.monto_12m, 0) AS monto_12m,
                vta.fecha_primera_venta
            FROM clientesjf cli
            LEFT JOIN (
                SELECT
                    v.cliente,
                    SUM(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH) THEN v.total ELSE 0 END) AS monto_reciente,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL " . ($meses * 2) . " MONTH)
                         AND v.fecha < DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                        THEN v.total ELSE 0 END) AS monto_anterior,
                    MAX(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH) THEN v.total ELSE 0 END) AS compra_maxima,
                    MAX(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$mesesLargo} MONTH) THEN v.total ELSE 0 END) AS compra_maxima_12m,
                    SUM(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$mesesLargo} MONTH) THEN v.total ELSE 0 END) AS monto_12m,
                    MIN(v.fecha) AS fecha_primera_venta
                FROM ventajf v
                WHERE UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND UPPER(v.tipo) IN ({$tiposSql})
                  AND v.vendedor NOT IN ({$vendExclSql})
                  AND v.cliente = :cliente_vta
                GROUP BY v.cliente
            ) vta ON vta.cliente = cli.codigo
            WHERE cli.codigo = :cliente
            LIMIT 1
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_vta", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();
        $metricas = $stmt->fetch();

        if (!$metricas) {
            return null;
        }

        $metricas["deuda_actual"] = $linea["deuda_actual"];
        $metricas["pico_historico"] = $linea["pico_historico"];
        $metricas["linea_operativa"] = $linea["linea_operativa"];
        $metricas["movimientos_cta"] = $linea["movimientos"];

        return $metricas;
    }

    /**
     * Motor 3: recomendación de línea de crédito (reutiliza scores de Motores 1, 2 y 4 fidelidad).
     */
    public static function mdlCalcularMotorLineaCredito(
        $codigoCliente,
        $resultadoMotor1 = null,
        $resultadoMotor2 = null,
        $resultadoMotor4 = null
    ) {
        $cfg = icConfigMotor3();
        $pesos = icPesosDecimalesMotor3();
        $pesosEfectivos = $cfg["pesos_efectivos"];
        $meses = (int) $cfg["meses_periodo"];
        $scoreNeutro = (int) $cfg["score_neutro"];
        $equifaxActivo = $cfg["equifax_activo"];

        if ($resultadoMotor1 === null) {
            $resultadoMotor1 = self::mdlCalcularMotorRiesgoCredito($codigoCliente);
        }

        if ($resultadoMotor2 === null) {
            $resultadoMotor2 = self::mdlCalcularMotorComercial($codigoCliente);
        }

        if ($resultadoMotor4 === null) {
            $resultadoMotor4 = self::mdlCalcularMotorFidelidad($codigoCliente);
        }

        $metricas = self::mdlMetricasMotorLineaCredito($codigoCliente);

        if (!$metricas || !$resultadoMotor1) {
            return null;
        }

        $scoreRiesgo = (float) $resultadoMotor1["score"];
        $scoreComercial = $resultadoMotor2 ? (float) $resultadoMotor2["score"] : $scoreNeutro;
        $scoreFidelidad = $resultadoMotor4 ? (float) $resultadoMotor4["score"] : $scoreNeutro;
        $deudaActual = (float) $metricas["deuda_actual"];
        $lineaOperativa = (float) $metricas["linea_operativa"];
        $picoHistorico = (float) $metricas["pico_historico"];
        $montoReciente = (float) $metricas["monto_reciente"];
        $montoAnterior = (float) $metricas["monto_anterior"];
        $compraMaxima = (float) $metricas["compra_maxima"];
        $compraMaxima12m = (float) $metricas["compra_maxima_12m"];
        $mesesAntiguedad = (int) $metricas["meses_antiguedad"];
        $promedioMensual = $montoReciente / max(1, $meses);
        $pctCrecimiento = icPctCrecimiento($montoReciente, $montoAnterior);
        $lineaReferencia = max($lineaOperativa, $deudaActual, 1.0);
        $periodos = icMotor2PeriodosFechas($meses);
        $rangoReciente = icMotor2FormatearRangoFechas($periodos["reciente"]["desde"], $periodos["reciente"]["hasta"]);
        $rangoAnterior = icMotor2FormatearRangoFechas($periodos["anterior"]["desde"], $periodos["anterior"]["hasta"]);
        $periodosBoxMotor3 = icMotor2PeriodosBox(array(
            array("etiqueta" => "Periodo reciente ({$meses} meses)", "rango" => $rangoReciente),
            array("etiqueta" => "Periodo anterior ({$meses} meses)", "rango" => $rangoAnterior),
        ));

        if ($deudaActual <= 0 && $lineaOperativa <= 0) {
            $utilizacion = 0;
            $scoreUtilizacion = 100;
            $detalleUtilizacion = "Sin deuda ni línea operativa previa.";
        } else {
            $utilizacion = ($deudaActual / $lineaReferencia) * 100;
            $scoreUtilizacion = icScorePorTramos($utilizacion, $cfg["utilizacion_tramos"]);
            $detalleUtilizacion = "Deuda S/ " . number_format($deudaActual, 2)
                . " sobre línea operativa S/ " . number_format($lineaReferencia, 2)
                . " (" . round($utilizacion, 1) . "%).";
        }

        if ($promedioMensual <= 0) {
            $scorePromedio = $scoreNeutro;
            $detallePromedio = "Sin compras en los últimos {$meses} meses.";
        } else {
            $scorePromedio = icScorePorTramos($promedioMensual, $cfg["promedio_tramos"]);
            $detallePromedio = "Promedio mensual S/ " . number_format($promedioMensual, 2)
                . " en los últimos {$meses} meses.";
        }

        if ($compraMaxima <= 0) {
            $scoreCompraMax = $scoreNeutro;
            $detalleCompraMax = "Sin compras registradas en el periodo.";
        } else {
            $scoreCompraMax = icScorePorTramos($compraMaxima, $cfg["compra_max_tramos"]);
            $detalleCompraMax = "Mayor compra S/ " . number_format($compraMaxima, 2) . " en {$meses} meses.";
        }

        if ($montoReciente <= 0 && $montoAnterior <= 0) {
            $scoreCrecimiento = $scoreNeutro;
            $detalleCrecimiento = "Sin compras en los periodos evaluados.";
            $clasificacionCrecimiento = "sin_datos";
        } elseif ($montoAnterior <= 0 && $montoReciente > 0) {
            $scoreCrecimiento = 90;
            $detalleCrecimiento = "Compras nuevas en el periodo reciente (sin base en el periodo anterior).";
            $clasificacionCrecimiento = "compras_nuevas";
        } else {
            $scoreCrecimiento = icScorePorUmbralesMinimos($pctCrecimiento, $cfg["crecimiento_umbrales"]);
            $detalleCrecimiento = "Crecimiento " . round($pctCrecimiento, 1) . "% entre periodos de {$meses} meses.";
            $clasificacionCrecimiento = "calculado";
        }

        $tablaCrecimiento = $clasificacionCrecimiento === "compras_nuevas"
            ? array(
                "titulo"   => "Crecimiento de compras",
                "intro"    => "No había compras en el periodo anterior; se asigna score 90 por reactivación o cliente nuevo en la ventana.",
                "columnas" => array("Situación", "Condición", "Score"),
                "filas"    => array(
                    array(
                        "situacion" => "Compras nuevas",
                        "condicion" => "Monto reciente > 0 y periodo anterior = 0",
                        "score"     => 90,
                        "aplica"    => true,
                        "es_resultado" => true,
                    ),
                ),
            )
            : icTablaLogicaPorUmbrales(
                "Crecimiento de compras",
                "Comparación de montos entre periodos consecutivos de {$meses} meses.",
                array("Crecimiento", "Condición", "Score"),
                $cfg["crecimiento_umbrales"],
                $pctCrecimiento,
                $scoreCrecimiento
            );

        $scoreAntiguedad = icScorePorTramos($mesesAntiguedad, $cfg["antiguedad_tramos"]);
        $detalleAntiguedad = $mesesAntiguedad . " meses como cliente activo.";

        $scoreEquifax = $scoreNeutro;
        $detalleEquifax = $equifaxActivo
            ? "Sin registro Equifax; score neutro ({$scoreNeutro})."
            : "Factor Equifax desactivado en configuración.";

        $detalleRiesgo = "Score del Motor 1: " . round($scoreRiesgo, 1) . " (" . $resultadoMotor1["clasificacion"]["etiqueta"] . ").";
        $detalleComercial = $resultadoMotor2
            ? "Score del Motor 2: " . round($scoreComercial, 1) . " (" . $resultadoMotor2["clasificacion"]["etiqueta"] . ")."
            : "Sin datos comerciales; score neutro ({$scoreNeutro}).";
        $detalleFidelidad = $resultadoMotor4
            ? "Score del Motor 3 (Fidelidad): " . round($scoreFidelidad, 1) . " (" . $resultadoMotor4["clasificacion"]["etiqueta"] . ")."
            : "Sin datos de fidelidad; score neutro ({$scoreNeutro}).";

        $factores = array(
            "score_riesgo" => array(
                "clave" => "score_riesgo",
                "nombre" => "Score de Riesgo",
                "icono" => "fa-shield",
                "peso" => (int) $pesosEfectivos["score_riesgo"],
                "score" => round($scoreRiesgo, 1),
                "detalle" => $detalleRiesgo,
                "formula" => "Score heredado = total ponderado del Motor 1",
                "regla" => "Capacidad de pago: historial de pagos, mora, tendencia e incidencias del Motor 1. Peso "
                    . (int) $pesosEfectivos["score_riesgo"] . "% en este motor.",
                "valores" => array(
                    array("etiqueta" => "Score riesgo", "valor" => round($scoreRiesgo, 1)),
                    array("etiqueta" => "Clasificación", "valor" => $resultadoMotor1["clasificacion"]["etiqueta"]),
                    array("etiqueta" => "Deuda actual", "valor" => "S/ " . number_format($deudaActual, 2)),
                ),
                "tabla_logica" => icTablaLogicaScoreReferencia(
                    "Motor 1 — Riesgo crediticio",
                    $scoreRiesgo,
                    $resultadoMotor1["factores"]
                ),
            ),
            "score_comercial" => array(
                "clave" => "score_comercial",
                "nombre" => "Score Comercial",
                "icono" => "fa-line-chart",
                "peso" => (int) $pesosEfectivos["score_comercial"],
                "score" => round($scoreComercial, 1),
                "detalle" => $detalleComercial,
                "formula" => "Score heredado = total ponderado del Motor 2",
                "regla" => "Potencial de crecimiento comercial; se hereda el score final del Motor 2.",
                "valores" => array(
                    array("etiqueta" => "Score comercial", "valor" => round($scoreComercial, 1)),
                    array("etiqueta" => "Clasificación", "valor" => $resultadoMotor2 ? $resultadoMotor2["clasificacion"]["etiqueta"] : "Sin datos"),
                ),
                "tabla_logica" => $resultadoMotor2
                    ? icTablaLogicaScoreReferencia(
                        "Motor 2 — Comercial",
                        $scoreComercial,
                        $resultadoMotor2["factores"]
                    )
                    : array(
                        "titulo"   => "Motor 2 — Comercial",
                        "intro"    => "Sin datos comerciales; se aplica score neutro.",
                        "columnas" => array("Situación", "Condición", "Score"),
                        "filas"    => array(
                            array(
                                "situacion"    => "→ Sin datos",
                                "condicion"    => "Score neutro " . $scoreNeutro,
                                "score"        => $scoreNeutro,
                                "aplica"       => true,
                                "es_resultado" => true,
                            ),
                        ),
                    ),
            ),
            "score_fidelidad" => array(
                "clave" => "score_fidelidad",
                "nombre" => "Score de Fidelidad",
                "icono" => "fa-heart",
                "peso" => (int) $pesosEfectivos["score_fidelidad"],
                "score" => round($scoreFidelidad, 1),
                "detalle" => $detalleFidelidad,
                "formula" => "Score heredado = total ponderado del Motor 3 (Fidelidad)",
                "regla" => "Probabilidad de que el cliente siga comprando; se hereda el score del Motor 3 (Fidelidad). Peso "
                    . (int) $pesosEfectivos["score_fidelidad"] . "% en este motor.",
                "valores" => array(
                    array("etiqueta" => "Score fidelidad", "valor" => round($scoreFidelidad, 1)),
                    array("etiqueta" => "Clasificación", "valor" => $resultadoMotor4 ? $resultadoMotor4["clasificacion"]["etiqueta"] : "Sin datos"),
                ),
                "tabla_logica" => $resultadoMotor4
                    ? icTablaLogicaScoreReferencia(
                        "Motor 3 — Fidelidad",
                        $scoreFidelidad,
                        $resultadoMotor4["factores"]
                    )
                    : array(
                        "titulo"   => "Motor 3 — Fidelidad",
                        "intro"    => "Sin datos de fidelidad; se aplica score neutro.",
                        "columnas" => array("Situación", "Condición", "Score"),
                        "filas"    => array(
                            array(
                                "situacion"    => "→ Sin datos",
                                "condicion"    => "Score neutro " . $scoreNeutro,
                                "score"        => $scoreNeutro,
                                "aplica"       => true,
                                "es_resultado" => true,
                            ),
                        ),
                    ),
            ),
            "promedio_compras" => array(
                "clave" => "promedio_compras",
                "nombre" => "Promedio de compras",
                "icono" => "fa-shopping-cart",
                "peso" => (int) $pesosEfectivos["promedio_compras"],
                "score" => $scorePromedio,
                "detalle" => $detallePromedio,
                "formula" => "Promedio = monto últimos {$meses}m ÷ {$meses}",
                "regla" => "A mayor promedio mensual, mayor capacidad para sostener una línea de crédito.",
                "valores" => array(
                    array("etiqueta" => "Monto periodo", "valor" => "S/ " . number_format($montoReciente, 2)),
                    array("etiqueta" => "Promedio mensual", "valor" => "S/ " . number_format($promedioMensual, 2)),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Promedio mensual de compras",
                    "Se ubica el promedio mensual en un tramo; ese tramo define el score del factor.",
                    " S/",
                    round($promedioMensual, 2),
                    $cfg["promedio_tramos"],
                    $scorePromedio
                ),
                "periodos_box" => $periodosBoxMotor3,
            ),
            "compra_maxima" => array(
                "clave" => "compra_maxima",
                "nombre" => "Compra máxima",
                "icono" => "fa-arrow-up",
                "peso" => (int) $pesosEfectivos["compra_maxima"],
                "score" => $scoreCompraMax,
                "detalle" => $detalleCompraMax,
                "formula" => "Mayor documento de venta en los últimos {$meses} meses",
                "regla" => "Tope observado de operación puntual.",
                "valores" => array(
                    array("etiqueta" => "Compra máxima", "valor" => "S/ " . number_format($compraMaxima, 2)),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Compra máxima del periodo",
                    "El mayor monto de una sola compra determina el tramo y el score.",
                    " S/",
                    round($compraMaxima, 2),
                    $cfg["compra_max_tramos"],
                    $scoreCompraMax
                ),
                "periodos_box" => $periodosBoxMotor3,
            ),
            "crecimiento" => array(
                "clave" => "crecimiento",
                "nombre" => "Crecimiento",
                "icono" => "fa-line-chart",
                "peso" => (int) $pesosEfectivos["crecimiento"],
                "score" => $scoreCrecimiento,
                "detalle" => $detalleCrecimiento,
                "formula" => "Crecimiento % = (reciente − anterior) ÷ anterior × 100",
                "regla" => "Mismo criterio del Motor 2: últimos {$meses}m vs {$meses}m previos.",
                "valores" => array(
                    array("etiqueta" => "Periodo reciente", "valor" => "S/ " . number_format($montoReciente, 2)),
                    array("etiqueta" => "Periodo anterior", "valor" => "S/ " . number_format($montoAnterior, 2)),
                    array("etiqueta" => "Variación", "valor" => round($pctCrecimiento, 1) . "%"),
                ),
                "tabla_logica" => $tablaCrecimiento,
                "periodos_box" => $periodosBoxMotor3,
            ),
            "utilizacion_linea" => array(
                "clave" => "utilizacion_linea",
                "nombre" => "Utilización de línea",
                "icono" => "fa-pie-chart",
                "peso" => (int) $pesosEfectivos["utilizacion_linea"],
                "score" => $scoreUtilizacion,
                "detalle" => $detalleUtilizacion,
                "formula" => "Utilización = deuda actual ÷ línea operativa × 100",
                "regla" => "Mide cuánto del crédito ya tolerado está en uso. Distinto al Motor 1, que usa la línea recomendada cuando existe.",
                "valores" => array(
                    array("etiqueta" => "Deuda actual", "valor" => "S/ " . number_format($deudaActual, 2)),
                    array("etiqueta" => "Línea operativa", "valor" => "S/ " . number_format($lineaOperativa, 2)),
                    array("etiqueta" => "Pico histórico", "valor" => "S/ " . number_format($picoHistorico, 2)),
                    array("etiqueta" => "Utilización", "valor" => round($utilizacion, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Utilización de la línea operativa",
                    "Porcentaje de la línea real observada (pico en cuenta corriente) que está en uso hoy.",
                    "%",
                    round($utilizacion, 1),
                    $cfg["utilizacion_tramos"],
                    $scoreUtilizacion
                ),
            ),
            "antiguedad" => array(
                "clave" => "antiguedad",
                "nombre" => "Antigüedad",
                "icono" => "fa-calendar",
                "peso" => (int) $pesosEfectivos["antiguedad"],
                "score" => $scoreAntiguedad,
                "detalle" => $detalleAntiguedad,
                "formula" => "Meses desde la primera venta válida hasta hoy",
                "regla" => "Clientes con más historial soportan líneas más amplias.",
                "valores" => array(
                    array("etiqueta" => "Meses", "valor" => (string) $mesesAntiguedad),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Antigüedad del cliente",
                    "Meses desde la primera compra; el tramo alcanzado define el score.",
                    " meses",
                    $mesesAntiguedad,
                    $cfg["antiguedad_tramos"],
                    $scoreAntiguedad
                ),
            ),
        );

        if ($equifaxActivo) {
            $factores["equifax"] = array(
                "clave" => "equifax",
                "nombre" => "Equifax",
                "icono" => "fa-university",
                "peso" => (int) $pesosEfectivos["equifax"],
                "score" => $scoreEquifax,
                "detalle" => $detalleEquifax,
                "formula" => "Score externo del buró (cuando exista integración)",
                "regla" => "Complementa la evaluación interna.",
                "valores" => array(),
                "tabla_logica" => icTablaLogicaEquifax($scoreNeutro),
            );
        }

        foreach ($factores as $clave => &$factor) {
            $factor["score"] = round($factor["score"], 1);
            $factor["aportacion"] = round($factor["score"] * $pesos[$clave], 2);
        }
        unset($factor);

        $scoreFinal = 0;
        foreach ($factores as $clave => $factor) {
            $scoreFinal += $factor["score"] * $pesos[$clave];
        }
        $scoreFinal = round($scoreFinal, 2);

        $calculoLinea = icMotor3CalcularLineaRecomendada(
            $cfg,
            $promedioMensual,
            $compraMaxima,
            $compraMaxima12m,
            $scoreFinal,
            $scoreRiesgo,
            $deudaActual,
            $lineaOperativa
        );
        $lineaRecomendada = (float) $calculoLinea["monto"];

        $accion = icMotor3DeterminarAccion(
            $cfg,
            $scoreFinal,
            $scoreRiesgo,
            $utilizacion,
            $deudaActual,
            $lineaOperativa,
            $lineaRecomendada
        );

        $capacidadPago = icMotor3ConstruirCapacidadPago(
            $resultadoMotor1,
            $pesosEfectivos,
            $scoreUtilizacion,
            $utilizacion,
            $equifaxActivo
        );

        $capacidadCompra = icMotor3ConstruirCapacidadCompra(
            $resultadoMotor2,
            $pesosEfectivos,
            $promedioMensual,
            $compraMaxima,
            $pctCrecimiento,
            $meses,
            $montoReciente
        );

        $explicacionLinea = icMotor3ConstruirExplicacionLinea(
            $cfg,
            $calculoLinea,
            $scoreFinal,
            $scoreRiesgo,
            $lineaOperativa,
            $deudaActual,
            $accion,
            $promedioMensual,
            $compraMaxima,
            $meses,
            $capacidadPago,
            $capacidadCompra
        );

        return array(
            "cliente" => array(
                "codigo" => $metricas["codigo"],
                "nombre" => $metricas["nombre"],
            ),
            "motor" => 3,
            "nombre_motor" => "Recomendación de Línea de Crédito",
            "score" => $scoreFinal,
            "clasificacion" => icClasificarScore($scoreFinal),
            "factores" => $factores,
            "accion" => $accion,
            "capacidad_pago" => $capacidadPago,
            "capacidad_compra" => $capacidadCompra,
            "explicacion_linea" => $explicacionLinea,
            "linea" => array(
                "deuda_actual"      => round($deudaActual, 2),
                "pico_historico"    => round($picoHistorico, 2),
                "linea_operativa"   => round($lineaOperativa, 2),
                "linea_recomendada" => $lineaRecomendada,
                "utilizacion_pct"   => round($utilizacion, 2),
                "movimientos_cta"   => (int) $metricas["movimientos_cta"],
                "calculo"           => $calculoLinea,
            ),
            "metricas" => array(
                "promedio_mensual"  => round($promedioMensual, 2),
                "compra_maxima"     => round($compraMaxima, 2),
                "compra_maxima_12m" => round($compraMaxima12m, 2),
                "pct_crecimiento"   => round($pctCrecimiento, 2),
                "score_riesgo"      => round($scoreRiesgo, 2),
                "score_comercial"   => round($scoreComercial, 2),
                "score_fidelidad"   => round($scoreFidelidad, 2),
            ),
        );
    }

    /**
     * Métricas crudas del cliente para el Motor 2 (comercial).
     */
    public static function mdlMetricasMotorComercial($codigoCliente)
    {
        $cfg = icConfigMotor2();
        $meses = (int) $cfg["meses_periodo"];
        $mitadTendencia = icMotor2MesesMitadTendencia($meses);
        $tiposSql = icMotor2TiposVentasSql();
        $vendExclSql = self::mdlSqlIn($cfg["ventas_excluir_vendedores"]);
        $zonaVendExclSql = icMotor2SqlExcluirVendedorPrefijosZona("c2.vendedor");
        $tablaMov = icMotor2TablaMovimientos($cfg["tabla_movimientos"]);

        $stmt = Conexion::conectar()->prepare("
            SELECT
                cli.codigo,
                cli.nombre,
                cli.ubigeo,
                cli.vendedor,
                CONCAT(
                    IFNULL(ub.departamento, ''),
                    ' / ',
                    IFNULL(ub.provincia, ''),
                    ' / ',
                    IFNULL(ub.distrito, '')
                ) AS zona_texto,
                IFNULL(vta.monto_reciente, 0) AS monto_reciente,
                IFNULL(vta.monto_anterior, 0) AS monto_anterior,
                IFNULL(vta.monto_yoy, 0) AS monto_yoy,
                IFNULL(vta.monto_tendencia_ini, 0) AS monto_tendencia_ini,
                IFNULL(vta.monto_tendencia_fin, 0) AS monto_tendencia_fin,
                IFNULL(vta.docs_reciente, 0) AS docs_reciente,
                IFNULL(vta.meses_con_compra, 0) AS meses_con_compra,
                vta.ultima_compra,
                IFNULL(zona.promedio_mensual, 0) AS zona_promedio_mensual,
                IFNULL(lineas.lineas_cliente, 0) AS lineas_cliente,
                IFNULL(lineas.lineas_catalogo, 0) AS lineas_catalogo
            FROM clientesjf cli
            LEFT JOIN ubigeo ub ON ub.codigo = cli.ubigeo
            LEFT JOIN (
                SELECT
                    v.cliente,
                    SUM(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH) THEN v.total ELSE 0 END) AS monto_reciente,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL " . ($meses * 2) . " MONTH)
                         AND v.fecha < DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                        THEN v.total ELSE 0 END) AS monto_anterior,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH), INTERVAL 1 YEAR)
                         AND v.fecha < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                        THEN v.total ELSE 0 END) AS monto_yoy,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                         AND v.fecha < DATE_SUB(CURDATE(), INTERVAL {$mitadTendencia} MONTH)
                        THEN v.total ELSE 0 END) AS monto_tendencia_ini,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$mitadTendencia} MONTH)
                        THEN v.total ELSE 0 END) AS monto_tendencia_fin,
                    SUM(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH) THEN 1 ELSE 0 END) AS docs_reciente,
                    COUNT(DISTINCT CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                        THEN DATE_FORMAT(v.fecha, '%Y-%m') END) AS meses_con_compra,
                    MAX(v.fecha) AS ultima_compra
                FROM ventajf v
                WHERE UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND UPPER(v.tipo) IN ({$tiposSql})
                  AND v.vendedor NOT IN ({$vendExclSql})
                  AND v.cliente = :cliente_vta
                GROUP BY v.cliente
            ) vta ON vta.cliente = cli.codigo
            LEFT JOIN (
                SELECT
                    mensual.ubigeo,
                    AVG(mensual.total_mes) AS promedio_mensual
                FROM (
                    SELECT
                        c2.ubigeo,
                        v.cliente,
                        SUM(v.total) / {$meses} AS total_mes
                    FROM ventajf v
                    INNER JOIN clientesjf c2 ON c2.codigo = v.cliente
                    WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                      AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                      AND UPPER(v.tipo) IN ({$tiposSql})
                      AND v.vendedor NOT IN ({$vendExclSql})
                      AND c2.ubigeo IS NOT NULL
                      AND c2.ubigeo <> ''
                      AND {$zonaVendExclSql}
                    GROUP BY c2.ubigeo, v.cliente
                ) mensual
                GROUP BY mensual.ubigeo
            ) zona ON zona.ubigeo = cli.ubigeo
            LEFT JOIN (
                SELECT
                    m.cliente,
                    COUNT(DISTINCT a.modelo) AS lineas_cliente,
                    (
                        SELECT COUNT(*)
                        FROM modelojf modc
                        WHERE modc.estado = 'activo'
                    ) AS lineas_catalogo
                FROM {$tablaMov} m
                INNER JOIN articulojf a ON a.articulo = m.articulo
                INNER JOIN modelojf mjf ON a.modelo = mjf.modelo
                    AND mjf.estado = 'activo'
                INNER JOIN ventajf v ON v.tipo = m.tipo AND v.documento = m.documento
                WHERE m.cliente = :cliente_lineas
                  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND UPPER(v.tipo) IN ({$tiposSql})
                  AND v.vendedor NOT IN ({$vendExclSql})
                GROUP BY m.cliente
            ) lineas ON lineas.cliente = cli.codigo
            WHERE cli.codigo = :cliente
            LIMIT 1
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_vta", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_lineas", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Convierte métricas comerciales en sub-scores y score ponderado final.
     */
    public static function mdlCalcularMotorComercial($codigoCliente)
    {
        $cfg = icConfigMotor2();
        $pesos = icPesosDecimalesMotor2();
        $pesosEfectivos = $cfg["pesos_efectivos"];
        $meses = (int) $cfg["meses_periodo"];
        $scoreNeutro = (int) $cfg["score_neutro"];

        $metricas = self::mdlMetricasMotorComercial($codigoCliente);

        if (!$metricas) {
            return null;
        }

        $montoReciente = (float) $metricas["monto_reciente"];
        $montoAnterior = (float) $metricas["monto_anterior"];
        $montoYoy = (float) $metricas["monto_yoy"];
        $montoTendenciaIni = (float) $metricas["monto_tendencia_ini"];
        $montoTendenciaFin = (float) $metricas["monto_tendencia_fin"];
        $mitadTendencia = icMotor2MesesMitadTendencia($meses);
        $docsReciente = (int) $metricas["docs_reciente"];
        $mesesConCompra = (int) $metricas["meses_con_compra"];
        $zonaPromedio = (float) $metricas["zona_promedio_mensual"];
        $lineasCliente = (int) $metricas["lineas_cliente"];
        $lineasCatalogo = (int) $metricas["lineas_catalogo"];
        $codigoVendedor = trim((string) $metricas["vendedor"]);
        $esperadaVendedor = icMotor2FrecuenciaEsperadaVendedor($codigoVendedor);

        $pctCrecimiento = icPctCrecimiento($montoReciente, $montoAnterior);
        $pctEstacional = icPctCrecimiento($montoReciente, $montoYoy);
        $frecuenciaMensual = $docsReciente / $meses;
        $frecuenciaEsperada = $esperadaVendedor ? (float) $esperadaVendedor["compras_mes"] : 0;
        $ratioFrecuencia = $frecuenciaEsperada > 0 ? ($frecuenciaMensual / $frecuenciaEsperada) : 0;
        $clienteMensual = $montoReciente / $meses;
        $ratioZona = $zonaPromedio > 0 ? ($clienteMensual / $zonaPromedio) : 0;
        $penetracion = $lineasCatalogo > 0 ? ($lineasCliente / $lineasCatalogo) * 100 : 0;
        $periodos = icMotor2PeriodosFechas($meses);
        $rangoReciente = icMotor2FormatearRangoFechas($periodos["reciente"]["desde"], $periodos["reciente"]["hasta"]);
        $rangoAnterior = icMotor2FormatearRangoFechas($periodos["anterior"]["desde"], $periodos["anterior"]["hasta"]);
        $rangoYoy = icMotor2FormatearRangoFechas($periodos["yoy"]["desde"], $periodos["yoy"]["hasta"]);
        $rangoTendenciaIni = icMotor2FormatearRangoFechas($periodos["tendencia_ini"]["desde"], $periodos["tendencia_ini"]["hasta"]);
        $rangoTendenciaFin = icMotor2FormatearRangoFechas($periodos["tendencia_fin"]["desde"], $periodos["tendencia_fin"]["hasta"]);

        // Crecimiento de compras
        if ($montoReciente <= 0 && $montoAnterior <= 0) {
            $scoreCrecimiento = $scoreNeutro;
            $detalleCrecimiento = "Sin compras en los periodos evaluados.";
        } elseif ($montoAnterior <= 0 && $montoReciente > 0) {
            $scoreCrecimiento = 90;
            $detalleCrecimiento = "Compras nuevas en el periodo reciente (S/ " . number_format($montoReciente, 2) . ").";
        } else {
            $scoreCrecimiento = icScorePorUmbralesMinimos($pctCrecimiento, $cfg["crecimiento_umbrales"]);
            $detalleCrecimiento = "Crecimiento de " . round($pctCrecimiento, 1) . "%: S/ "
                . number_format($montoReciente, 2) . " vs S/ " . number_format($montoAnterior, 2) . " anteriores.";
        }

        // Frecuencia de compra
        $frecuenciaModoRuta = (bool) $esperadaVendedor;

        if ($frecuenciaModoRuta) {
            if ($docsReciente <= 0) {
                $scoreFrecuencia = icScorePorTramos(0, $cfg["frecuencia_ratio_tramos"]);
                $detalleFrecuencia = "Sin compras en {$meses} meses; por debajo de lo esperado para vendedor "
                    . $esperadaVendedor["nombre"] . " ({$esperadaVendedor["compras_mes"]} compras/mes).";
            } else {
                $scoreFrecuencia = icScorePorTramos($ratioFrecuencia, $cfg["frecuencia_ratio_tramos"]);
                $detalleFrecuencia = round($frecuenciaMensual, 2) . " compras/mes vs "
                    . $frecuenciaEsperada . " esperadas (vendedor " . $esperadaVendedor["nombre"]
                    . ", ratio " . round($ratioFrecuencia, 2) . "×). "
                    . $docsReciente . " docs en " . $mesesConCompra . " meses con compra.";
            }
        } elseif ($codigoVendedor === "") {
            $scoreFrecuencia = $scoreNeutro;
            $detalleFrecuencia = "Cliente sin vendedor asignado; score neutro ({$scoreNeutro}).";
        } elseif ($docsReciente <= 0) {
            $scoreFrecuencia = icScorePorTramos(0, $cfg["frecuencia_tramos"]);
            $detalleFrecuencia = "Sin compras en el periodo reciente de {$meses} meses.";
        } else {
            $scoreFrecuencia = icScorePorTramos($frecuenciaMensual, $cfg["frecuencia_tramos"]);
            $detalleFrecuencia = round($frecuenciaMensual, 2) . " compras/mes ({$docsReciente} docs en "
                . $mesesConCompra . " meses activos). Evaluación estándar sin ajuste de ruta intermes.";
        }

        if ($frecuenciaModoRuta) {
            $frecuenciaFormula = "Ratio = (documentos ÷ {$meses} meses) ÷ frecuencia esperada del vendedor";
            $frecuenciaRegla = "Vendedor " . $codigoVendedor . " (" . $esperadaVendedor["nombre"] . "): "
                . $esperadaVendedor["compras_mes"] . " compras/mes esperadas. " . $esperadaVendedor["nota"] . ".";
            $frecuenciaValores = array(
                array("etiqueta" => "Vendedor", "valor" => $codigoVendedor . " (" . $esperadaVendedor["nombre"] . ")"),
                array("etiqueta" => "Compras/mes real", "valor" => round($frecuenciaMensual, 2)),
                array("etiqueta" => "Compras/mes esperada", "valor" => (string) $frecuenciaEsperada),
                array("etiqueta" => "Ratio vs esperado", "valor" => round($ratioFrecuencia, 2) . "×"),
                array("etiqueta" => "Documentos / meses activos", "valor" => $docsReciente . " / " . $mesesConCompra),
            );
            $frecuenciaTabla = icTablaLogicaPorTramos(
                "Ratio frecuencia real ÷ esperada (ruta intermes)",
                "Esperado para " . $esperadaVendedor["nombre"] . ": " . $frecuenciaEsperada
                    . " compras/mes (" . $esperadaVendedor["nota"] . ").",
                "× esperado",
                round($ratioFrecuencia, 2),
                $cfg["frecuencia_ratio_tramos"],
                $scoreFrecuencia
            );
        } else {
            $frecuenciaFormula = "Frecuencia = documentos de venta ÷ {$meses} meses";
            $frecuenciaRegla = $codigoVendedor !== ""
                ? "Vendedor {$codigoVendedor}: evaluación por compras/mes sin ajuste de ruta (solo 04 Norte y 05 Sur usan ratio intermes)."
                : "Sin vendedor asignado → score neutro ({$scoreNeutro}).";
            $frecuenciaValores = array(
                array("etiqueta" => "Vendedor", "valor" => $codigoVendedor !== "" ? $codigoVendedor : "Sin asignar"),
                array("etiqueta" => "Compras/mes", "valor" => round($frecuenciaMensual, 2)),
                array("etiqueta" => "Documentos / meses activos", "valor" => $docsReciente . " / " . $mesesConCompra),
            );
            $frecuenciaTabla = icTablaLogicaPorTramos(
                "Tramos de frecuencia (evaluación estándar)",
                "Promedio de documentos de compra por mes en los últimos {$meses} meses.",
                " compras/mes",
                round($frecuenciaMensual, 2),
                $cfg["frecuencia_tramos"],
                $scoreFrecuencia
            );
        }

        // Potencial de productos (menor penetración = mayor potencial)
        if ($lineasCatalogo <= 0) {
            $scorePotencial = $scoreNeutro;
            $detallePotencial = "Sin detalle de artículos en movimientos; score neutro ({$scoreNeutro}).";
        } else {
            $scorePotencial = icScorePorTramos($penetracion, $cfg["penetracion_tramos"]);
            $detallePotencial = "Penetración de modelos: {$lineasCliente} de {$lineasCatalogo} ("
                . round($penetracion, 1) . "% del catálogo activo).";
        }

        // Tendencia de compra (ritmo dentro del periodo reciente: últimos N/2 vs primeros N/2)
        $resultadoTendencia = icClasificarTendenciaCompra($cfg, $montoTendenciaFin, $montoTendenciaIni);
        $scoreTendencia = (int) $resultadoTendencia["score"];
        $clasificacionTendencia = $resultadoTendencia["clasificacion"];
        $detalleTendencia = $resultadoTendencia["detalle"];

        // Zona o mercado
        $esClienteUsuarioFinal = icMotor2EsClienteUsuarioFinalZona($codigoVendedor);
        $prefijosZonaExcl = icMotor2ZonaExcluirVendedorPrefijosTexto();

        if ($esClienteUsuarioFinal) {
            $scoreZona = $scoreNeutro;
            $detalleZona = "Cliente de canal usuario final (vendedor {$codigoVendedor}); "
                . "score neutro ({$scoreNeutro}) — no comparable con el benchmark mayorista.";
        } elseif ($zonaPromedio <= 0 || $montoReciente <= 0) {
            $scoreZona = $scoreNeutro;
            $detalleZona = $metricas["zona_texto"]
                ? "Sin referencia de zona o sin compras recientes; score neutro ({$scoreNeutro})."
                : "Cliente sin ubigeo registrado; score neutro ({$scoreNeutro}).";
        } else {
            $scoreZona = icScorePorTramos($ratioZona, $cfg["zona_tramos"]);
            $detalleZona = "Promedio mensual S/ " . number_format($clienteMensual, 2)
                . " vs S/ " . number_format($zonaPromedio, 2) . " de su distrito ("
                . round($ratioZona * 100, 1) . "% del promedio). Zona: " . $metricas["zona_texto"] . ".";
        }

        // Estacionalidad (YoY mismo periodo)
        if ($montoReciente <= 0 && $montoYoy <= 0) {
            $scoreEstacionalidad = $scoreNeutro;
            $detalleEstacionalidad = "Sin compras para comparar estacionalidad año contra año.";
        } elseif ($montoYoy <= 0 && $montoReciente > 0) {
            $scoreEstacionalidad = 85;
            $detalleEstacionalidad = "Compras en periodo actual sin equivalente el año anterior.";
        } else {
            $scoreEstacionalidad = icScorePorUmbralesMinimos($pctEstacional, $cfg["estacionalidad_umbrales"]);
            $detalleEstacionalidad = "Variación YoY de " . round($pctEstacional, 1) . "%: S/ "
                . number_format($montoReciente, 2) . " vs S/ " . number_format($montoYoy, 2) . " mismo periodo año anterior.";
        }

        $factores = array(
            "crecimiento_compras" => array(
                "clave" => "crecimiento_compras",
                "nombre" => "Crecimiento de compras",
                "icono" => "fa-line-chart",
                "peso" => (int) $pesosEfectivos["crecimiento_compras"],
                "score" => $scoreCrecimiento,
                "detalle" => $detalleCrecimiento,
                "formula" => "Crecimiento % = (monto reciente − anterior) ÷ anterior × 100",
                "regla" => "Compara montos de compra en periodos consecutivos de {$meses} meses cada uno.",
                "valores" => array(
                    array("etiqueta" => "Monto reciente ({$meses}m)", "valor" => "S/ " . number_format($montoReciente, 2)),
                    array("etiqueta" => "Monto anterior ({$meses}m)", "valor" => "S/ " . number_format($montoAnterior, 2)),
                    array("etiqueta" => "Crecimiento", "valor" => round($pctCrecimiento, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorUmbrales(
                    "Tramos de crecimiento",
                    "Comparación de montos de compra entre periodos consecutivos de {$meses} meses.",
                    array("Crecimiento", "Condición", "Score"),
                    $cfg["crecimiento_umbrales"],
                    $pctCrecimiento,
                    $scoreCrecimiento
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo reciente ({$meses} meses)", "rango" => $rangoReciente),
                    array("etiqueta" => "Periodo anterior ({$meses} meses)", "rango" => $rangoAnterior),
                )),
            ),
            "frecuencia_compra" => array(
                "clave" => "frecuencia_compra",
                "nombre" => "Frecuencia de compra",
                "icono" => "fa-refresh",
                "peso" => (int) $pesosEfectivos["frecuencia_compra"],
                "score" => $scoreFrecuencia,
                "detalle" => $detalleFrecuencia,
                "formula" => $frecuenciaFormula,
                "regla" => $frecuenciaRegla,
                "valores" => $frecuenciaValores,
                "tabla_logica" => $frecuenciaTabla,
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo analizado ({$meses} meses)", "rango" => $rangoReciente),
                )),
            ),
            "potencial_productos" => array(
                "clave" => "potencial_productos",
                "nombre" => "Potencial de productos",
                "icono" => "fa-cubes",
                "peso" => (int) $pesosEfectivos["potencial_productos"],
                "score" => $scorePotencial,
                "detalle" => $detallePotencial,
                "formula" => "Penetración = modelos activos del cliente ÷ modelos activos del catálogo × 100",
                "regla" => "Modelo desde articulojf → modelojf con estado activo. Solo compras válidas (tipos "
                    . icVentasTiposValidosTexto() . ").",
                "valores" => array(
                    array("etiqueta" => "Modelos del cliente", "valor" => (string) $lineasCliente),
                    array("etiqueta" => "Modelos en catálogo", "valor" => (string) $lineasCatalogo),
                    array("etiqueta" => "Penetración", "valor" => round($penetracion, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Penetración de modelos (menor = más potencial)",
                    "Menor penetración implica más modelos activos por desarrollar con el cliente.",
                    "%",
                    round($penetracion, 1),
                    $cfg["penetracion_tramos"],
                    $scorePotencial
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Compras del cliente", "rango" => "Histórico (sin límite de fecha)"),
                    array("etiqueta" => "Catálogo de referencia", "rango" => "Todos los modelos activos en modelojf"),
                )),
            ),
            "tendencia_compra" => array(
                "clave" => "tendencia_compra",
                "nombre" => "Tendencia de compra",
                "icono" => "fa-area-chart",
                "peso" => (int) $pesosEfectivos["tendencia_compra"],
                "score" => $scoreTendencia,
                "detalle" => $detalleTendencia,
                "formula" => "Ritmo = monto últimos {$mitadTendencia}m ÷ monto primeros {$mitadTendencia}m del periodo reciente",
                "regla" => "Mide si el cliente acelera o frena dentro de los últimos {$meses} meses. "
                    . "No duplica crecimiento (ese factor compara semestre vs semestre anterior).",
                "valores" => array(
                    array("etiqueta" => "Primeros {$mitadTendencia}m del periodo", "valor" => "S/ " . number_format($montoTendenciaIni, 2)),
                    array("etiqueta" => "Últimos {$mitadTendencia}m del periodo", "valor" => "S/ " . number_format($montoTendenciaFin, 2)),
                    array("etiqueta" => "Última compra", "valor" => $metricas["ultima_compra"] ? date("d/m/Y", strtotime($metricas["ultima_compra"])) : "Sin compras"),
                ),
                "tabla_logica" => icTablaLogicaTendenciaCompra($cfg, $clasificacionTendencia),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Primeros {$mitadTendencia} meses (del periodo reciente)", "rango" => $rangoTendenciaIni),
                    array("etiqueta" => "Últimos {$mitadTendencia} meses (del periodo reciente)", "rango" => $rangoTendenciaFin),
                )),
            ),
            "zona_mercado" => array(
                "clave" => "zona_mercado",
                "nombre" => "Zona o mercado",
                "icono" => "fa-map-marker",
                "peso" => (int) $pesosEfectivos["zona_mercado"],
                "score" => $scoreZona,
                "detalle" => $detalleZona,
                "formula" => "Ratio = promedio mensual cliente ÷ promedio mensual del distrito",
                "regla" => "Compara con clientes del mismo ubigeo en el periodo reciente. "
                    . "Excluye clientes usuario final (vendedor que inicia con "
                    . ($prefijosZonaExcl !== "" ? $prefijosZonaExcl : "—") . ").",
                "valores" => array(
                    array("etiqueta" => "Zona", "valor" => $metricas["zona_texto"] ?: "Sin ubigeo"),
                    array("etiqueta" => "Promedio cliente/mes", "valor" => "S/ " . number_format($clienteMensual, 2)),
                    array("etiqueta" => "Promedio zona/mes", "valor" => "S/ " . number_format($zonaPromedio, 2)),
                    array("etiqueta" => "Ratio vs zona", "valor" => round($ratioZona, 2) . "×"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Desempeño vs promedio del distrito",
                    "Ratio del promedio mensual del cliente frente al promedio de su distrito. "
                        . "Solo entran clientes mayoristas (sin vendedor "
                        . ($prefijosZonaExcl !== "" ? $prefijosZonaExcl : "—") . ").",
                    "× promedio",
                    round($ratioZona, 2),
                    $cfg["zona_tramos"],
                    $scoreZona
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo de referencia ({$meses} meses)", "rango" => $rangoReciente),
                )),
            ),
            "estacionalidad" => array(
                "clave" => "estacionalidad",
                "nombre" => "Estacionalidad",
                "icono" => "fa-calendar-check-o",
                "peso" => (int) $pesosEfectivos["estacionalidad"],
                "score" => $scoreEstacionalidad,
                "detalle" => $detalleEstacionalidad,
                "formula" => "Variación YoY = (periodo actual − mismo periodo año anterior) ÷ año anterior × 100",
                "regla" => "Compara los últimos {$meses} meses con el mismo rango del año anterior.",
                "valores" => array(
                    array("etiqueta" => "Monto periodo actual", "valor" => "S/ " . number_format($montoReciente, 2)),
                    array("etiqueta" => "Monto mismo periodo YoY", "valor" => "S/ " . number_format($montoYoy, 2)),
                    array("etiqueta" => "Variación YoY", "valor" => round($pctEstacional, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorUmbrales(
                    "Variación estacional año contra año",
                    "Mide si el cliente compra más o menos que en el mismo periodo del año anterior.",
                    array("Variación YoY", "Condición", "Score"),
                    $cfg["estacionalidad_umbrales"],
                    $pctEstacional,
                    $scoreEstacionalidad
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo actual ({$meses} meses)", "rango" => $rangoReciente),
                    array("etiqueta" => "Mismo periodo año anterior", "rango" => $rangoYoy),
                )),
            ),
        );

        foreach ($factores as $clave => &$factor) {
            $factor["score"] = round($factor["score"], 1);
            $factor["aportacion"] = round($factor["score"] * $pesos[$clave], 2);
        }
        unset($factor);

        $scoreFinal = 0;
        foreach ($factores as $clave => $factor) {
            $scoreFinal += $factor["score"] * $pesos[$clave];
        }
        $scoreFinal = round($scoreFinal, 2);

        return array(
            "cliente" => array(
                "codigo" => $metricas["codigo"],
                "nombre" => $metricas["nombre"],
            ),
            "motor" => 2,
            "nombre_motor" => "Score Comercial",
            "score" => $scoreFinal,
            "clasificacion" => icClasificarScore($scoreFinal),
            "factores" => $factores,
            "metricas" => array(
                "monto_reciente" => round($montoReciente, 2),
                "monto_anterior" => round($montoAnterior, 2),
                "pct_crecimiento" => round($pctCrecimiento, 2),
                "frecuencia_mensual" => round($frecuenciaMensual, 2),
                "penetracion_lineas" => round($penetracion, 2),
                "ratio_zona" => round($ratioZona, 2),
            ),
        );
    }

    /**
     * Métricas crudas del cliente para el Motor 4 (fidelidad).
     */
    public static function mdlMetricasMotorFidelidad($codigoCliente)
    {
        $cfg = icConfigMotor4();
        $meses = (int) $cfg["meses_periodo"];
        $mitad = icMotor4MesesMitadTendencia($meses);
        $tiposSql = icVentasTiposValidosSql();
        $vendExclSql = self::mdlSqlIn(icConfigMotor2()["ventas_excluir_vendedores"]);

        $stmt = Conexion::conectar()->prepare("
            SELECT
                cli.codigo,
                cli.nombre,
                cli.fecreg,
                IFNULL(
                    TIMESTAMPDIFF(
                        MONTH,
                        COALESCE(vta.fecha_primera_venta, cli.fecreg),
                        NOW()
                    ),
                    0
                ) AS meses_antiguedad,
                IFNULL(vta.docs_periodo, 0) AS docs_periodo,
                IFNULL(vta.meses_con_compra, 0) AS meses_con_compra,
                vta.ultima_compra,
                IFNULL(vta.monto_tendencia_ini, 0) AS monto_tendencia_ini,
                IFNULL(vta.monto_tendencia_fin, 0) AS monto_tendencia_fin,
                vta.fecha_primera_venta
            FROM clientesjf cli
            LEFT JOIN (
                SELECT
                    v.cliente,
                    SUM(CASE WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH) THEN 1 ELSE 0 END) AS docs_periodo,
                    COUNT(DISTINCT CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                        THEN DATE_FORMAT(v.fecha, '%Y-%m') END) AS meses_con_compra,
                    MAX(v.fecha) AS ultima_compra,
                    MIN(v.fecha) AS fecha_primera_venta,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                         AND v.fecha < DATE_SUB(CURDATE(), INTERVAL {$mitad} MONTH)
                        THEN v.total ELSE 0 END) AS monto_tendencia_ini,
                    SUM(CASE
                        WHEN v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$mitad} MONTH)
                        THEN v.total ELSE 0 END) AS monto_tendencia_fin
                FROM ventajf v
                WHERE UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND UPPER(v.tipo) IN ({$tiposSql})
                  AND v.vendedor NOT IN ({$vendExclSql})
                  AND v.cliente = :cliente_vta
                GROUP BY v.cliente
            ) vta ON vta.cliente = cli.codigo
            WHERE cli.codigo = :cliente
            LIMIT 1
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_vta", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Convierte métricas de fidelidad en sub-scores y score ponderado final.
     */
    public static function mdlCalcularMotorFidelidad($codigoCliente)
    {
        $cfg = icConfigMotor4();
        $pesos = icPesosDecimalesMotor4();
        $pesosEfectivos = $cfg["pesos_efectivos"];
        $meses = (int) $cfg["meses_periodo"];
        $mitad = icMotor4MesesMitadTendencia($meses);
        $scoreNeutro = (int) $cfg["score_neutro"];

        $metricas = self::mdlMetricasMotorFidelidad($codigoCliente);

        if (!$metricas) {
            return null;
        }

        $docsPeriodo = (int) $metricas["docs_periodo"];
        $mesesConCompra = (int) $metricas["meses_con_compra"];
        $mesesAntiguedad = (int) $metricas["meses_antiguedad"];
        $montoTendenciaIni = (float) $metricas["monto_tendencia_ini"];
        $montoTendenciaFin = (float) $metricas["monto_tendencia_fin"];
        $ultimaCompra = $metricas["ultima_compra"];

        $frecuenciaMensual = $docsPeriodo / $meses;
        $regularidadPct = ($mesesConCompra / $meses) * 100;
        $diasUltimaCompra = $ultimaCompra ? (int) ((strtotime("today") - strtotime($ultimaCompra)) / 86400) : 99999;

        $periodos = icMotor4PeriodosFechas($meses);
        $rangoPeriodo = icMotor2FormatearRangoFechas($periodos["periodo"]["desde"], $periodos["periodo"]["hasta"]);
        $rangoTendenciaIni = icMotor2FormatearRangoFechas($periodos["tendencia_ini"]["desde"], $periodos["tendencia_ini"]["hasta"]);
        $rangoTendenciaFin = icMotor2FormatearRangoFechas($periodos["tendencia_fin"]["desde"], $periodos["tendencia_fin"]["hasta"]);

        // Frecuencia (12 meses, sin ajuste de vendedor)
        if ($docsPeriodo <= 0) {
            $scoreFrecuencia = icScorePorTramos(0, $cfg["frecuencia_tramos"]);
            $detalleFrecuencia = "Sin compras en los últimos {$meses} meses.";
        } else {
            $scoreFrecuencia = icScorePorTramos($frecuenciaMensual, $cfg["frecuencia_tramos"]);
            $detalleFrecuencia = round($frecuenciaMensual, 2) . " compras/mes ({$docsPeriodo} docs en {$meses} meses).";
        }

        // Antigüedad
        if ($mesesAntiguedad <= 0) {
            $scoreAntiguedad = $scoreNeutro;
            $detalleAntiguedad = "Sin antigüedad registrada; score neutro ({$scoreNeutro}).";
        } else {
            $scoreAntiguedad = icScorePorTramos($mesesAntiguedad, $cfg["antiguedad_tramos"]);
            $detalleAntiguedad = "{$mesesAntiguedad} meses como cliente activo.";
        }

        // Regularidad
        if ($docsPeriodo <= 0) {
            $scoreRegularidad = icScorePorTramos(0, $cfg["regularidad_tramos"]);
            $detalleRegularidad = "Sin compras en el periodo; 0 de {$meses} meses activos.";
        } else {
            $scoreRegularidad = icScorePorTramos($regularidadPct, $cfg["regularidad_tramos"]);
            $detalleRegularidad = "{$mesesConCompra} de {$meses} meses con compra ("
                . round($regularidadPct, 1) . "% de regularidad).";
        }

        // Última compra
        if (!$ultimaCompra) {
            $scoreUltimaCompra = icScorePorTramos(99999, $cfg["ultima_compra_tramos"]);
            $detalleUltimaCompra = "Sin compras registradas.";
        } else {
            $scoreUltimaCompra = icScorePorTramos($diasUltimaCompra, $cfg["ultima_compra_tramos"]);
            $detalleUltimaCompra = "Última compra hace {$diasUltimaCompra} días ("
                . date("d/m/Y", strtotime($ultimaCompra)) . ").";
        }

        // Tendencia (últimos 6m vs primeros 6m de 12)
        $resultadoTendencia = icClasificarTendenciaCompra($cfg, $montoTendenciaFin, $montoTendenciaIni);
        $scoreTendencia = (int) $resultadoTendencia["score"];
        $clasificacionTendencia = $resultadoTendencia["clasificacion"];
        $detalleTendencia = $resultadoTendencia["detalle"];

        $factores = array(
            "frecuencia" => array(
                "clave" => "frecuencia",
                "nombre" => "Frecuencia",
                "icono" => "fa-refresh",
                "peso" => (int) $pesosEfectivos["frecuencia"],
                "score" => $scoreFrecuencia,
                "detalle" => $detalleFrecuencia,
                "formula" => "Frecuencia = documentos ÷ {$meses} meses",
                "regla" => "Historial de {$meses} meses (S02, S03, S70). Sin ajuste de ruta del vendedor.",
                "valores" => array(
                    array("etiqueta" => "Documentos ({$meses}m)", "valor" => (string) $docsPeriodo),
                    array("etiqueta" => "Compras/mes", "valor" => round($frecuenciaMensual, 2)),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Frecuencia de compra (12 meses)",
                    "Promedio de documentos de compra por mes.",
                    " compras/mes",
                    round($frecuenciaMensual, 2),
                    $cfg["frecuencia_tramos"],
                    $scoreFrecuencia
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo analizado ({$meses} meses)", "rango" => $rangoPeriodo),
                )),
            ),
            "antiguedad" => array(
                "clave" => "antiguedad",
                "nombre" => "Antigüedad",
                "icono" => "fa-calendar",
                "peso" => (int) $pesosEfectivos["antiguedad"],
                "score" => $scoreAntiguedad,
                "detalle" => $detalleAntiguedad,
                "formula" => "Meses = desde primera venta hasta hoy",
                "regla" => "Cliente consolidado vs cliente nuevo. Tipos " . icVentasTiposValidosTexto() . ".",
                "valores" => array(
                    array("etiqueta" => "Meses como cliente", "valor" => (string) $mesesAntiguedad),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Tramos de antigüedad",
                    "Meses desde la primera venta válida hasta hoy.",
                    " meses",
                    $mesesAntiguedad,
                    $cfg["antiguedad_tramos"],
                    $scoreAntiguedad
                ),
            ),
            "regularidad" => array(
                "clave" => "regularidad",
                "nombre" => "Regularidad",
                "icono" => "fa-check-circle",
                "peso" => (int) $pesosEfectivos["regularidad"],
                "score" => $scoreRegularidad,
                "detalle" => $detalleRegularidad,
                "formula" => "Regularidad = meses con compra ÷ {$meses} × 100",
                "regla" => "Mide si compra de forma pareja mes a mes, no solo en picos.",
                "valores" => array(
                    array("etiqueta" => "Meses con compra", "valor" => $mesesConCompra . " / " . $meses),
                    array("etiqueta" => "Regularidad", "valor" => round($regularidadPct, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Regularidad de compra",
                    "Porcentaje de meses del periodo con al menos una compra.",
                    "%",
                    round($regularidadPct, 1),
                    $cfg["regularidad_tramos"],
                    $scoreRegularidad
                ),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Periodo analizado ({$meses} meses)", "rango" => $rangoPeriodo),
                )),
            ),
            "ultima_compra" => array(
                "clave" => "ultima_compra",
                "nombre" => "Última compra",
                "icono" => "fa-clock-o",
                "peso" => (int) $pesosEfectivos["ultima_compra"],
                "score" => $scoreUltimaCompra,
                "detalle" => $detalleUltimaCompra,
                "formula" => "Días = hoy − fecha de última compra",
                "regla" => "Menos días sin comprar = mayor probabilidad de seguir activo.",
                "valores" => array(
                    array("etiqueta" => "Días sin comprar", "valor" => $ultimaCompra ? (string) $diasUltimaCompra : "—"),
                    array("etiqueta" => "Fecha última compra", "valor" => $ultimaCompra ? date("d/m/Y", strtotime($ultimaCompra)) : "Sin compras"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Recencia de la última compra",
                    "Cuántos días han pasado desde la última compra válida.",
                    " días",
                    $ultimaCompra ? $diasUltimaCompra : 0,
                    $cfg["ultima_compra_tramos"],
                    $scoreUltimaCompra
                ),
            ),
            "tendencia" => array(
                "clave" => "tendencia",
                "nombre" => "Tendencia",
                "icono" => "fa-area-chart",
                "peso" => (int) $pesosEfectivos["tendencia"],
                "score" => $scoreTendencia,
                "detalle" => $detalleTendencia,
                "formula" => "Compara montos últimos {$mitad}m vs primeros {$mitad}m del periodo de {$meses} meses",
                "regla" => "Distinto al Motor 2: ventana de 12 meses partida en dos mitades.",
                "valores" => array(
                    array("etiqueta" => "Primeros {$mitad}m", "valor" => "S/ " . number_format($montoTendenciaIni, 2)),
                    array("etiqueta" => "Últimos {$mitad}m", "valor" => "S/ " . number_format($montoTendenciaFin, 2)),
                ),
                "tabla_logica" => icTablaLogicaTendenciaFidelidad($cfg, $clasificacionTendencia),
                "periodos_box" => icMotor2PeriodosBox(array(
                    array("etiqueta" => "Primeros {$mitad} meses", "rango" => $rangoTendenciaIni),
                    array("etiqueta" => "Últimos {$mitad} meses", "rango" => $rangoTendenciaFin),
                )),
            ),
        );

        foreach ($factores as $clave => &$factor) {
            $factor["score"] = round($factor["score"], 1);
            $factor["aportacion"] = round($factor["score"] * $pesos[$clave], 2);
        }
        unset($factor);

        $scoreFinal = 0;
        foreach ($factores as $clave => $factor) {
            $scoreFinal += $factor["score"] * $pesos[$clave];
        }
        $scoreFinal = round($scoreFinal, 2);

        return array(
            "cliente" => array(
                "codigo" => $metricas["codigo"],
                "nombre" => $metricas["nombre"],
            ),
            "motor" => 4,
            "nombre_motor" => "Score de Fidelidad",
            "score" => $scoreFinal,
            "clasificacion" => icClasificarScore($scoreFinal),
            "factores" => $factores,
            "metricas" => array(
                "frecuencia_mensual" => round($frecuenciaMensual, 2),
                "regularidad_pct" => round($regularidadPct, 1),
                "dias_ultima_compra" => $ultimaCompra ? $diasUltimaCompra : null,
                "meses_antiguedad" => $mesesAntiguedad,
            ),
        );
    }
}
