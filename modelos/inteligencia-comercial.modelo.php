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
    public static function mdlCalcularMotorRiesgoCredito($codigoCliente)
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

        // Utilización de línea
        if ($totalCredito <= 0) {
            $utilizacion = 0;
            $scoreUtilizacion = 100;
            $detalleUtilizacion = "Sin crédito histórico; utilización 0%.";
        } else {
            $utilizacion = ($totalDeuda / $totalCredito) * 100;
            $scoreUtilizacion = icScorePorTramos($utilizacion, $cfg["utilizacion_tramos"]);
            $detalleUtilizacion = "Deuda S/ " . number_format($totalDeuda, 2)
                . " sobre crédito histórico S/ " . number_format($totalCredito, 2)
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
                "formula" => "Utilización = deuda pendiente ÷ crédito histórico × 100",
                "regla" => $reglaUtilizacion . ". Proxy hasta tener cupo oficial.",
                "valores" => array(
                    array("etiqueta" => "Deuda pendiente", "valor" => "S/ " . number_format($totalDeuda, 2)),
                    array("etiqueta" => "Crédito histórico", "valor" => "S/ " . number_format($totalCredito, 2)),
                    array("etiqueta" => "Utilización", "valor" => round($utilizacion, 1) . "%"),
                ),
                "tabla_logica" => icTablaLogicaPorTramos(
                    "Tramos de utilización",
                    "Utilización = deuda pendiente ÷ crédito histórico. Proxy hasta tener cupo oficial (Motor 5).",
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
                "regla" => $reglaAntiguedad . ". Fuente: primera venta; si no existe, fecreg del ERP.",
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
                "total_credito" => $totalCredito,
                "meses_antiguedad" => $mesesAntiguedad,
                "incidencias" => $incidencias,
            ),
        );
    }
}
