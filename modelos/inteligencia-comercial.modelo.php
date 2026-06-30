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

        $stmt = Conexion::conectar()->prepare("
            SELECT
                cli.codigo,
                cli.nombre,
                cli.fecreg,
                TIMESTAMPDIFF(MONTH, cli.fecreg, NOW()) AS meses_antiguedad,
                IFNULL(doc.total_docs, 0) AS total_docs,
                IFNULL(doc.docs_a_tiempo, 0) AS docs_a_tiempo,
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
                    COUNT(*) AS total_docs,
                    SUM(
                        CASE
                            WHEN c.ult_pago IS NOT NULL
                                AND GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven), 0) <= :tolerancia_docs
                                THEN 1
                            WHEN c.ult_pago IS NULL AND IFNULL(c.saldo, 0) = 0
                                THEN 1
                            ELSE 0
                        END
                    ) AS docs_a_tiempo,
                    AVG(
                        CASE
                            WHEN c.ult_pago IS NOT NULL
                                THEN GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_atraso, 0)
                            ELSE NULL
                        END
                    ) AS atraso_promedio,
                    AVG(
                        CASE
                            WHEN c.ult_pago IS NOT NULL
                                AND c.ult_pago >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                THEN GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_reciente, 0)
                            ELSE NULL
                        END
                    ) AS atraso_reciente,
                    AVG(
                        CASE
                            WHEN c.ult_pago IS NOT NULL
                                AND c.ult_pago >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                                AND c.ult_pago < DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                THEN GREATEST(DATEDIFF(c.ult_pago, c.fecha_ven) - :tolerancia_anterior, 0)
                            ELSE NULL
                        END
                    ) AS atraso_anterior
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '+'
                  AND UPPER(c.estado) = 'CANCELADO'
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
            WHERE cli.codigo = :cliente
            LIMIT 1
        ");

        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_docs", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_deuda", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_credito", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":cliente_inc", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":tolerancia_docs", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_atraso", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_reciente", $tolerancia, PDO::PARAM_INT);
        $stmt->bindParam(":tolerancia_anterior", $tolerancia, PDO::PARAM_INT);

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
        $tolerancia = (int) $cfg["tolerancia_dias_pago"];
        $multAtraso = (float) $cfg["atraso_multiplicador"];
        $penalIncidencia = (int) $cfg["incidencia_penalizacion"];
        $scoreNeutro = (int) $cfg["score_neutro"];

        $metricas = self::mdlMetricasMotorRiesgo($codigoCliente);

        if (!$metricas) {
            return null;
        }

        $totalDocs = (int) $metricas["total_docs"];
        $docsATiempo = (int) $metricas["docs_a_tiempo"];
        $atrasoPromedio = (float) $metricas["atraso_promedio"];
        $atrasoReciente = $metricas["atraso_reciente"] !== null ? (float) $metricas["atraso_reciente"] : null;
        $atrasoAnterior = $metricas["atraso_anterior"] !== null ? (float) $metricas["atraso_anterior"] : null;
        $totalDeuda = (float) $metricas["total_deuda"];
        $totalCredito = (float) $metricas["total_credito"];
        $mesesAntiguedad = (int) $metricas["meses_antiguedad"];
        $incidencias = (int) $metricas["incidencias"];
        $utilizacion = 0;

        $pesoHistorial = (int) $cfg["pesos"]["historial_pagos"];
        $pesoAtraso = (int) $cfg["pesos"]["dias_atraso"];
        $pesoUtilizacion = (int) $cfg["pesos"]["utilizacion_linea"];
        $pesoAntiguedad = (int) $cfg["pesos"]["antiguedad"];
        $pesoTendencia = (int) $cfg["pesos"]["tendencia_pago"];
        $pesoEquifax = (int) $cfg["pesos"]["equifax"];
        $pesoIncidencias = (int) $cfg["pesos"]["incidencias"];

        // Historial de pagos — solo documentos CANCELADOS
        if ($totalDocs > 0) {
            $scoreHistorial = round(($docsATiempo / $totalDocs) * 100, 2);
            $detalleHistorial = round(($docsATiempo / $totalDocs) * 100, 1)
                . "% de documentos cerrados pagados a tiempo ($docsATiempo de $totalDocs cerrados).";
        } else {
            $scoreHistorial = $scoreNeutro;
            $detalleHistorial = "Sin documentos cerrados; score neutro ($scoreNeutro).";
        }

        // Días promedio de atraso (penalizable = días sobre vencimiento menos tolerancia)
        $scoreAtraso = round(max(0, min(100, 100 - ($atrasoPromedio * $multAtraso))), 2);
        $detalleAtraso = "Promedio de " . round($atrasoPromedio, 1) . " días penalizables (después de descontar $tolerancia días de tolerancia).";

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

        // Antigüedad
        $scoreAntiguedad = icScorePorTramos($mesesAntiguedad, $cfg["antiguedad_tramos"]);
        $detalleAntiguedad = $mesesAntiguedad . " meses como cliente registrado.";

        // Tendencia de pago
        $tendCfg = $cfg["tendencia"];
        if ($atrasoReciente !== null && $atrasoAnterior !== null && $atrasoAnterior > 0) {
            if ($atrasoReciente < $atrasoAnterior * $tendCfg["mejorando"]["factor"]) {
                $scoreTendencia = (int) $tendCfg["mejorando"]["score"];
                $detalleTendencia = "Mejorando: atraso penalizable reciente " . round($atrasoReciente, 1) . " días vs " . round($atrasoAnterior, 1) . " días anteriores.";
            } elseif ($atrasoReciente <= $atrasoAnterior * $tendCfg["estable"]["factor"]) {
                $scoreTendencia = (int) $tendCfg["estable"]["score"];
                $detalleTendencia = "Estable: atraso penalizable reciente " . round($atrasoReciente, 1) . " días vs " . round($atrasoAnterior, 1) . " días anteriores.";
            } else {
                $scoreTendencia = (int) $tendCfg["empeorando"]["score"];
                $detalleTendencia = "Empeorando: atraso penalizable reciente " . round($atrasoReciente, 1) . " días vs " . round($atrasoAnterior, 1) . " días anteriores.";
            }
        } else {
            $scoreTendencia = $scoreNeutro;
            $detalleTendencia = "Datos insuficientes para comparar tendencia (últimos 12 meses).";
        }

        // Equifax
        $scoreEquifax = $scoreNeutro;
        $detalleEquifax = "Sin registro Equifax en el sistema; score neutro ($scoreNeutro).";

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
                "formula" => "Score = (cerrados a tiempo ÷ total cerrados) × 100",
                "regla" => "Solo documentos CANCELADOS. A tiempo = atraso ≤ $tolerancia días (config: tolerancia_dias_pago).",
                "valores" => array(
                    array("etiqueta" => "Documentos cerrados", "valor" => (string) $totalDocs),
                    array("etiqueta" => "Cerrados a tiempo", "valor" => (string) $docsATiempo),
                    array("etiqueta" => "Tolerancia configurada", "valor" => $tolerancia . " días"),
                    array("etiqueta" => "Porcentaje", "valor" => $totalDocs > 0 ? round(($docsATiempo / $totalDocs) * 100, 1) . "%" : "N/A"),
                ),
            ),
            "dias_atraso" => array(
                "clave" => "dias_atraso",
                "nombre" => "Días promedio de atraso",
                "icono" => "fa-clock-o",
                "peso" => $pesoAtraso,
                "score" => $scoreAtraso,
                "detalle" => $detalleAtraso,
                "formula" => "Atraso penalizable = máx(0, días desde vencimiento − $tolerancia). Score = máx(0, 100 − (promedio × $multAtraso))",
                "regla" => "Solo documentos cerrados. Los primeros $tolerancia días de atraso no penalizan (misma tolerancia que historial de pagos).",
                "valores" => array(
                    array("etiqueta" => "Atraso penalizable promedio", "valor" => round($atrasoPromedio, 1) . " días"),
                    array("etiqueta" => "Tolerancia descontada", "valor" => $tolerancia . " días"),
                    array("etiqueta" => "Multiplicador", "valor" => (string) $multAtraso),
                    array("etiqueta" => "Score calculado", "valor" => number_format($scoreAtraso, 1)),
                ),
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
            ),
            "antiguedad" => array(
                "clave" => "antiguedad",
                "nombre" => "Antigüedad",
                "icono" => "fa-calendar",
                "peso" => $pesoAntiguedad,
                "score" => $scoreAntiguedad,
                "detalle" => $detalleAntiguedad,
                "formula" => "Score por tramos según meses registrado como cliente",
                "regla" => $reglaAntiguedad,
                "valores" => array(
                    array("etiqueta" => "Meses como cliente", "valor" => (string) $mesesAntiguedad),
                    array("etiqueta" => "Fecha registro", "valor" => $metricas["fecreg"] ? date("d/m/Y", strtotime($metricas["fecreg"])) : "N/A"),
                ),
            ),
            "tendencia_pago" => array(
                "clave" => "tendencia_pago",
                "nombre" => "Tendencia de pago",
                "icono" => "fa-line-chart",
                "peso" => $pesoTendencia,
                "score" => $scoreTendencia,
                "detalle" => $detalleTendencia,
                "formula" => "Compara atraso penalizable (después de tolerancia) últimos 6 meses vs 6 meses anteriores",
                "regla" => "Atraso penalizable = máx(0, días − $tolerancia días). Mejorando (<" . ($tendCfg["mejorando"]["factor"] * 100) . "% del anterior) → " . $tendCfg["mejorando"]["score"]
                    . " | Estable (±" . (($tendCfg["estable"]["factor"] - 1) * 100) . "%) → " . $tendCfg["estable"]["score"]
                    . " | Empeorando → " . $tendCfg["empeorando"]["score"],
                "valores" => array(
                    array("etiqueta" => "Atraso penalizable reciente (6m)", "valor" => $atrasoReciente !== null ? round($atrasoReciente, 1) . " días" : "Sin datos"),
                    array("etiqueta" => "Atraso penalizable anterior (6m)", "valor" => $atrasoAnterior !== null ? round($atrasoAnterior, 1) . " días" : "Sin datos"),
                    array("etiqueta" => "Tolerancia descontada", "valor" => $tolerancia . " días"),
                ),
            ),
            "equifax" => array(
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
            ),
            "incidencias" => array(
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
