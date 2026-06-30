<?php

/**
 * Configuración del Motor de Inteligencia Comercial.
 * Ajustar aquí pesos, tolerancias y reglas sin tocar la lógica del modelo.
 */

// ─── Motor 1: Score de Riesgo Crediticio ───────────────────────────────────

/** Pesos de cada factor (deben sumar 100). */
$ic_motor1_pesos = array(
    "historial_pagos"    => 35,
    "dias_atraso"          => 20,
    "utilizacion_linea"  => 10,
    "antiguedad"           => 10,
    "tendencia_pago"       => 10,
    "equifax"              => 10,
    "incidencias"          => 5,
);

/** Días de tolerancia para considerar un pago "a tiempo" (solo documentos CANCELADOS). */
$ic_motor1_tolerancia_dias_pago = 30;

/** Multiplicador para convertir días de atraso promedio en score (score = 100 − atraso × mult). */
$ic_motor1_atraso_multiplicador = 1.2;

/** Puntos que resta cada incidencia comercial (protesta / renovación). */
$ic_motor1_incidencia_penalizacion = 15;

/** Score neutro cuando no hay datos suficientes. */
$ic_motor1_score_neutro = 50;

/** Tramos de utilización de línea (% deuda / crédito histórico) → score. */
$ic_motor1_utilizacion_tramos = array(
    array("hasta" => 30,  "score" => 100),
    array("hasta" => 50,  "score" => 85),
    array("hasta" => 70,  "score" => 70),
    array("hasta" => 90,  "score" => 50),
    array("hasta" => 999, "score" => 20),
);

/** Tramos de antigüedad (meses como cliente) → score. */
$ic_motor1_antiguedad_tramos = array(
    array("hasta" => 6,   "score" => 40),
    array("hasta" => 12,  "score" => 55),
    array("hasta" => 24,  "score" => 70),
    array("hasta" => 60,  "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Umbrales de tendencia de pago (comparación 6m reciente vs 6m anterior). */
$ic_motor1_tendencia = array(
    "mejorando"  => array("factor" => 0.9,  "score" => 90),
    "estable"    => array("factor" => 1.1,  "score" => 70),
    "empeorando" => array("score" => 40),
);

// ─── Clasificación general de scores (todos los motores) ─────────────────────

$ic_clasificacion_scores = array(
    array("min" => 90, "etiqueta" => "Excelente",    "color" => "success"),
    array("min" => 80, "etiqueta" => "Bueno",        "color" => "primary"),
    array("min" => 70, "etiqueta" => "Aceptable",    "color" => "info"),
    array("min" => 60, "etiqueta" => "Riesgo Medio", "color" => "warning"),
    array("min" => 0,  "etiqueta" => "Riesgo Alto",  "color" => "danger"),
);

/**
 * Devuelve la configuración del Motor 1.
 */
function icConfigMotor1()
{
    global $ic_motor1_pesos,
           $ic_motor1_tolerancia_dias_pago,
           $ic_motor1_atraso_multiplicador,
           $ic_motor1_incidencia_penalizacion,
           $ic_motor1_score_neutro,
           $ic_motor1_utilizacion_tramos,
           $ic_motor1_antiguedad_tramos,
           $ic_motor1_tendencia;

    return array(
        "pesos"                  => $ic_motor1_pesos,
        "tolerancia_dias_pago"   => (int) $ic_motor1_tolerancia_dias_pago,
        "atraso_multiplicador"   => (float) $ic_motor1_atraso_multiplicador,
        "incidencia_penalizacion"=> (int) $ic_motor1_incidencia_penalizacion,
        "score_neutro"           => (int) $ic_motor1_score_neutro,
        "utilizacion_tramos"     => $ic_motor1_utilizacion_tramos,
        "antiguedad_tramos"      => $ic_motor1_antiguedad_tramos,
        "tendencia"              => $ic_motor1_tendencia,
    );
}

/**
 * Pesos del Motor 1 como fracción decimal (0.35, 0.20, …).
 */
function icPesosDecimalesMotor1()
{
    $cfg = icConfigMotor1();
    $decimales = array();

    foreach ($cfg["pesos"] as $clave => $porcentaje) {
        $decimales[$clave] = $porcentaje / 100;
    }

    return $decimales;
}

/**
 * Clasificación textual según score.
 */
function icClasificarScore($score)
{
    global $ic_clasificacion_scores;

    foreach ($ic_clasificacion_scores as $tramo) {
        if ($score >= $tramo["min"]) {
            return array(
                "etiqueta" => $tramo["etiqueta"],
                "color"    => $tramo["color"],
            );
        }
    }

    return array("etiqueta" => "Riesgo Alto", "color" => "danger");
}

/**
 * Score según tramos [{hasta, score}, …].
 */
function icScorePorTramos($valor, $tramos)
{
    foreach ($tramos as $tramo) {
        if ($valor <= $tramo["hasta"]) {
            return (int) $tramo["score"];
        }
    }

    return (int) end($tramos)["score"];
}
