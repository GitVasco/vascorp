<?php

/**
 * Configuración del Motor de Inteligencia Comercial.
 * Ajustar aquí pesos, tolerancias y reglas sin tocar la lógica del modelo.
 */

/** Tipos ventajf válidos en todos los motores (campo `tipo`). Solo estos cuentan como compra/venta. */
$ic_ventas_tipos_validos = array("S02", "S03", "S70");

// ─── Motor 1: Score de Riesgo Crediticio ───────────────────────────────────

/** Pesos con Equifax activo (deben sumar 100). */
$ic_motor1_pesos_con_equifax = array(
    "historial_pagos"       => 35,
    "dias_atraso"           => 20,
    "utilizacion_linea"     => 10,
    "antiguedad"            => 10,
    "tendencia_pago"        => 10,
    "equifax"               => 10,
    "incidencias"           => 5,
);

/** Pesos sin Equifax (deben sumar 100). Sin reparto automático del % de Equifax. */
$ic_motor1_pesos_sin_equifax = array(
    "historial_pagos"       => 40,
    "dias_atraso"           => 20,
    "utilizacion_linea"     => 10,
    "antiguedad"            => 10,
    "tendencia_pago"        => 10,
    "incidencias"           => 10,
);

/** Equifax activo: true = usa pesos_con_equifax; false = usa pesos_sin_equifax. */
$ic_motor1_equifax_activo = false;

/** Días de tolerancia en factores de pago.
 *  Historial: a tiempo si atraso ≤ este valor (cerrados) o pendiente vigente/en gracia.
 *  Atraso y tendencia: penalizable = máx(0, días − tolerancia).
 *  Pendientes vencidos fuera de tolerancia cuentan como mora actual. */
$ic_motor1_tolerancia_dias_pago = 45;

/** Multiplicador para convertir días de atraso promedio en score (score = 100 − atraso × mult).
 *  Menor valor = menos castigo. Ej.: 30 días → 1.2 resta 36 pts | 0.8 resta 24 pts | 0.6 resta 18 pts */
$ic_motor1_atraso_multiplicador = 0.8;

/** Puntos que resta cada incidencia comercial (protesta / renovación). */
$ic_motor1_incidencia_penalizacion = 15;

/** Score neutro cuando no hay datos suficientes. */
$ic_motor1_score_neutro = 50;

/** Tramos de utilización de línea (% deuda / línea operativa) → score. */
$ic_motor1_utilizacion_tramos = array(
    array("hasta" => 30,  "score" => 100),
    array("hasta" => 50,  "score" => 85),
    array("hasta" => 70,  "score" => 70),
    array("hasta" => 90,  "score" => 50),
    array("hasta" => 999, "score" => 20),
);

/** Tramos de antigüedad (meses desde primera venta en ventajf) → score. */
$ic_motor1_antiguedad_tramos = array(
    array("hasta" => 6,   "score" => 50),
    array("hasta" => 12,  "score" => 55),
    array("hasta" => 24,  "score" => 70),
    array("hasta" => 60,  "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Umbrales de tendencia de pago (comparación periodo reciente vs periodo anterior). */
$ic_motor1_tendencia = array(
    "mejorando"  => array("factor" => 0.9,  "score" => 90),
    "estable"    => array("factor" => 1.1,  "score" => 70),
    "empeorando" => array("score" => 40),
);

/** Meses por periodo de tendencia: 3 o 6. Compara últimos N meses vs los N meses anteriores. */
$ic_motor1_tendencia_meses_periodo = 6;

/** Si el periodo anterior no tuvo mora (0 días) y el reciente tiene mora leve ≤ este umbral → Estable, no Empeorando. */
$ic_motor1_tendencia_mora_leve_max = 15;

/** Meses mínimos de antigüedad para castigar 0 → mora como empeorando. Por debajo → score neutro si supera mora_leve_max. */
$ic_motor1_tendencia_antiguedad_comparativa = 12;

// ─── Motor 2: Score Comercial ────────────────────────────────────────────────

/** Pesos de cada factor (deben sumar 100). */
$ic_motor2_pesos = array(
    "crecimiento_compras" => 30,
    "frecuencia_compra"   => 25,
    "potencial_productos" => 15,
    "tendencia_compra"    => 15,
    "zona_mercado"        => 10,
    "estacionalidad"      => 5,
);

/** Meses del periodo de análisis (reciente vs anterior). */
$ic_motor2_meses_periodo = 6;

/** Tabla de movimientos con detalle de artículos (actualizar el año si aplica). */
$ic_motor2_tabla_movimientos = "movimientosjf_2026";

/** Score neutro cuando no hay datos suficientes. */
$ic_motor2_score_neutro = 50;

/** Vendedores excluidos del análisis comercial. */
$ic_motor2_ventas_excluir_vendedores = array("99", "23");

/** Prefijos de vendedor (usuario final) excluidos del benchmark de zona o mercado. */
$ic_motor2_zona_excluir_vendedor_prefijos = array("08");

/** Crecimiento % compras (reciente vs periodo anterior) → score. Umbrales mínimos descendentes. */
$ic_motor2_crecimiento_umbrales = array(
    array("desde" => 20,  "score" => 100),
    array("desde" => 10,  "score" => 85),
    array("desde" => 0,   "score" => 70),
    array("desde" => -10, "score" => 55),
    array("desde" => -999, "score" => 35),
);

/** Solo vendedores 04 (Norte) y 05 (Sur): ruta un mes sí y un mes no. */
$ic_motor2_frecuencia_esperada_vendedor = array(
    "04" => array(
        "nombre"      => "Norte",
        "compras_mes" => 0.5,
        "nota"        => "Ruta un mes sí y un mes no",
    ),
    "05" => array(
        "nombre"      => "Sur",
        "compras_mes" => 0.5,
        "nota"        => "Ruta un mes sí y un mes no",
    ),
);

/** Ratio real ÷ esperada — solo aplica a vendedores 04 y 05. */
$ic_motor2_frecuencia_ratio_tramos = array(
    array("hasta" => 0.5, "score" => 40),
    array("hasta" => 0.8, "score" => 55),
    array("hasta" => 1.0, "score" => 70),
    array("hasta" => 1.2, "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Frecuencia absoluta (compras/mes) para demás vendedores. */
$ic_motor2_frecuencia_tramos = array(
    array("hasta" => 0.5, "score" => 40),
    array("hasta" => 1,   "score" => 55),
    array("hasta" => 2,   "score" => 70),
    array("hasta" => 3,   "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Penetración de modelos activos (%) → score (menor penetración = mayor potencial). */
$ic_motor2_penetracion_tramos = array(
    array("hasta" => 20,  "score" => 100),
    array("hasta" => 40,  "score" => 85),
    array("hasta" => 60,  "score" => 70),
    array("hasta" => 80,  "score" => 55),
    array("hasta" => 999, "score" => 40),
);

/** Tendencia de compra: ritmo dentro del periodo reciente (última mitad vs primera mitad). */
$ic_motor2_tendencia_compra = array(
    "mejorando"  => array("factor" => 1.1, "score" => 90),
    "estable"    => array("factor" => 0.9, "score" => 70),
    "empeorando" => array("score" => 40),
);

/** Ratio venta mensual cliente ÷ promedio zona (mismo distrito) → score. */
$ic_motor2_zona_tramos = array(
    array("hasta" => 0.5, "score" => 40),
    array("hasta" => 0.8, "score" => 55),
    array("hasta" => 1.0, "score" => 70),
    array("hasta" => 1.2, "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Variación YoY del periodo reciente (%) → score. */
$ic_motor2_estacionalidad_umbrales = array(
    array("desde" => 15,  "score" => 100),
    array("desde" => 5,   "score" => 85),
    array("desde" => -5,  "score" => 70),
    array("desde" => -15, "score" => 55),
    array("desde" => -999, "score" => 35),
);

// ─── Motor 3: Recomendación de Línea de Crédito ─────────────────────────────

/** Pesos de cada factor (deben sumar 100). */
$ic_motor3_pesos = array(
    "score_riesgo"        => 40,
    "promedio_compras"    => 10,
    "compra_maxima"       => 10,
    "crecimiento"         => 10,
    "utilizacion_linea"   => 10,
    "antiguedad"          => 5,
    "score_comercial"     => 5,
    "score_fidelidad"     => 5,
    "equifax"             => 5,
);

/** Meses para promedio de compras y crecimiento (alineado con Motor 2). */
$ic_motor3_meses_periodo = 6;

/** Meses de cobertura para estimar monto de línea recomendada (promedio mensual × este valor). */
$ic_motor3_meses_cobertura_linea = 3;

/** Ventana larga para compra máxima histórica en el cálculo de línea (pausas comerciales / letras). */
$ic_motor3_meses_memoria_larga = 12;

/**
 * Piso de línea para buen pagador con baja utilización de la línea operativa.
 * No cambia pesos del motor; solo evita recomendaciones irrealmente bajas.
 */
$ic_motor3_piso_linea = array(
    "riesgo_min"          => 75,
    "utilizacion_max_pct" => 30,
    "ratio_deuda"         => 1.5,
    "ratio_operativa"     => 0.20,
);

$ic_motor3_score_neutro = 50;

/** Promedio mensual de compras (S/) → score. */
$ic_motor3_promedio_tramos = array(
    array("hasta" => 2000,   "score" => 40),
    array("hasta" => 5000,   "score" => 55),
    array("hasta" => 15000,  "score" => 70),
    array("hasta" => 30000,  "score" => 85),
    array("hasta" => 999999, "score" => 100),
);

/** Mayor compra en el periodo (S/) → score. */
$ic_motor3_compra_max_tramos = array(
    array("hasta" => 3000,   "score" => 40),
    array("hasta" => 8000,   "score" => 55),
    array("hasta" => 20000,  "score" => 70),
    array("hasta" => 40000,  "score" => 85),
    array("hasta" => 999999, "score" => 100),
);

/** Utilización deuda ÷ línea operativa (%) → score. Baja utilización favorece ampliar. */
$ic_motor3_utilizacion_tramos = array(
    array("hasta" => 40,  "score" => 100),
    array("hasta" => 60,  "score" => 85),
    array("hasta" => 75,  "score" => 70),
    array("hasta" => 90,  "score" => 50),
    array("hasta" => 999, "score" => 25),
);

/** Variación % compras (mismo criterio Motor 2) → score. */
$ic_motor3_crecimiento_umbrales = array(
    array("desde" => 20,  "score" => 100),
    array("desde" => 10,  "score" => 85),
    array("desde" => 0,   "score" => 70),
    array("desde" => -10, "score" => 55),
    array("desde" => -999, "score" => 35),
);

/** Umbrales para decidir la acción recomendada. */
$ic_motor3_acciones = array(
    "riesgo_suspende"           => 60,
    "riesgo_manual"             => 65,
    "score_aprobar_inicial"     => 70,
    "score_incrementar"         => 75,
    "ratio_incrementar"         => 1.15,
    "ratio_reducir"              => 0.80,
    "utilizacion_alta"           => 90,
    "utilizacion_alta_riesgo"    => 75,
    "reducir_riesgo_exento"      => 75,
    "reducir_utilizacion_exenta" => 30,
);

// ─── Motor 4: Score de Fidelidad ─────────────────────────────────────────────

/** Pesos (suman 100). Reclamos excluido: +5% regularidad, +5% última compra. */
$ic_motor4_pesos = array(
    "frecuencia"    => 25,
    "antiguedad"    => 20,
    "regularidad"   => 25,
    "ultima_compra" => 20,
    "tendencia"     => 10,
);

/** Ventana de análisis (12 meses — distinta al Motor 2). */
$ic_motor4_meses_periodo = 12;

$ic_motor4_score_neutro = 50;

/** Compras/mes en los últimos 12 meses → score. */
$ic_motor4_frecuencia_tramos = array(
    array("hasta" => 0.25, "score" => 40),
    array("hasta" => 0.5,  "score" => 55),
    array("hasta" => 1,    "score" => 70),
    array("hasta" => 2,    "score" => 85),
    array("hasta" => 999,  "score" => 100),
);

/** Reutiliza tramos de antigüedad del Motor 1 (misma escala). */

/** % de meses con al menos una compra en 12 meses → score. */
$ic_motor4_regularidad_tramos = array(
    array("hasta" => 25,  "score" => 40),
    array("hasta" => 50,  "score" => 55),
    array("hasta" => 75,  "score" => 70),
    array("hasta" => 90,  "score" => 85),
    array("hasta" => 999, "score" => 100),
);

/** Días desde la última compra → score (menos días = más fidelidad). */
$ic_motor4_ultima_compra_tramos = array(
    array("hasta" => 30,   "score" => 100),
    array("hasta" => 60,   "score" => 85),
    array("hasta" => 90,   "score" => 70),
    array("hasta" => 180,  "score" => 55),
    array("hasta" => 365,  "score" => 40),
    array("hasta" => 99999, "score" => 20),
);

/** Ritmo en 12 meses: últimos 6m vs primeros 6m del periodo. */
$ic_motor4_tendencia_fidelidad = array(
    "mejorando"  => array("factor" => 1.1, "score" => 90),
    "estable"    => array("factor" => 0.9, "score" => 70),
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
    global $ic_motor1_pesos_con_equifax,
        $ic_motor1_pesos_sin_equifax,
        $ic_motor1_equifax_activo,
        $ic_motor1_tolerancia_dias_pago,
        $ic_motor1_atraso_multiplicador,
        $ic_motor1_incidencia_penalizacion,
        $ic_motor1_score_neutro,
        $ic_motor1_utilizacion_tramos,
        $ic_motor1_antiguedad_tramos,
        $ic_motor1_tendencia,
        $ic_motor1_tendencia_meses_periodo,
        $ic_motor1_tendencia_mora_leve_max,
        $ic_motor1_tendencia_antiguedad_comparativa;

    $mesesPeriodo = icTendenciaMesesPeriodo($ic_motor1_tendencia_meses_periodo);

    return array(
        "pesos"                            => icPesosEfectivosMotor1(),
        "pesos_con_equifax"                => $ic_motor1_pesos_con_equifax,
        "pesos_sin_equifax"                => $ic_motor1_pesos_sin_equifax,
        "pesos_efectivos"                  => icPesosEfectivosMotor1(),
        "equifax_activo"                   => (bool) $ic_motor1_equifax_activo,
        "ventas_tipos"                     => icVentasTiposValidos(),
        "tolerancia_dias_pago"       => (int) $ic_motor1_tolerancia_dias_pago,
        "atraso_multiplicador"       => (float) $ic_motor1_atraso_multiplicador,
        "incidencia_penalizacion"    => (int) $ic_motor1_incidencia_penalizacion,
        "score_neutro"               => (int) $ic_motor1_score_neutro,
        "utilizacion_tramos"         => $ic_motor1_utilizacion_tramos,
        "antiguedad_tramos"          => $ic_motor1_antiguedad_tramos,
        "tendencia"                       => $ic_motor1_tendencia,
        "tendencia_meses_periodo"         => $mesesPeriodo,
        "tendencia_mora_leve_max"         => (float) $ic_motor1_tendencia_mora_leve_max,
        "tendencia_antiguedad_comparativa" => (int) $ic_motor1_tendencia_antiguedad_comparativa,
    );
}

/**
 * Clasifica tendencia de pago y devuelve score, clasificación y detalle.
 */
function icClasificarTendenciaPago($cfg, $atrasoReciente, $atrasoAnterior, $mesesAntiguedad)
{
    $tendCfg = $cfg["tendencia"];
    $mesesPeriodo = (int) $cfg["tendencia_meses_periodo"];
    $scoreNeutro = (int) $cfg["score_neutro"];
    $moraLeveMax = (float) $cfg["tendencia_mora_leve_max"];
    $antiguedadMin = (int) $cfg["tendencia_antiguedad_comparativa"];

    if ($antiguedadMin <= 0) {
        $antiguedadMin = $mesesPeriodo * 2;
    }

    if ($atrasoReciente === null || $atrasoAnterior === null) {
        return array(
            "score"          => $scoreNeutro,
            "clasificacion"  => "sin_datos",
            "detalle"        => "Datos insuficientes para comparar tendencia (se requieren documentos en ambos periodos de {$mesesPeriodo} meses).",
        );
    }

    $rec = round((float) $atrasoReciente, 1);
    $ant = round((float) $atrasoAnterior, 1);

    if ($ant <= 0 && $rec <= 0) {
        return array(
            "score"         => (int) $tendCfg["estable"]["score"],
            "clasificacion" => "estable",
            "detalle"       => "Estable: sin atraso penalizable en ambos periodos de {$mesesPeriodo} meses.",
        );
    }

    if ($ant <= 0 && $rec > 0) {
        if ($rec <= $moraLeveMax) {
            return array(
                "score"         => (int) $tendCfg["estable"]["score"],
                "clasificacion" => "estable",
                "detalle"       => "Estable: mora leve inicial ({$rec} días penalizables sin historial previo; umbral ≤ {$moraLeveMax} días).",
            );
        }

        if ($mesesAntiguedad < $antiguedadMin) {
            return array(
                "score"         => $scoreNeutro,
                "clasificacion" => "neutro_cliente_nuevo",
                "detalle"       => "Score neutro: cliente con {$mesesAntiguedad} meses de antigüedad (< {$antiguedadMin}); insuficiente historial para confirmar deterioro.",
            );
        }

        return array(
            "score"         => (int) $tendCfg["empeorando"]["score"],
            "clasificacion" => "empeorando",
            "detalle"       => "Empeorando: pasó de 0 a {$rec} días penalizables en el periodo reciente.",
        );
    }

    if ($rec < $ant * $tendCfg["mejorando"]["factor"]) {
        return array(
            "score"         => (int) $tendCfg["mejorando"]["score"],
            "clasificacion" => "mejorando",
            "detalle"       => "Mejorando: atraso penalizable reciente {$rec} días vs {$ant} días anteriores.",
        );
    }

    if ($rec <= $ant * $tendCfg["estable"]["factor"]) {
        return array(
            "score"         => (int) $tendCfg["estable"]["score"],
            "clasificacion" => "estable",
            "detalle"       => "Estable: atraso penalizable reciente {$rec} días vs {$ant} días anteriores.",
        );
    }

    return array(
        "score"         => (int) $tendCfg["empeorando"]["score"],
        "clasificacion" => "empeorando",
        "detalle"       => "Empeorando: atraso penalizable reciente {$rec} días vs {$ant} días anteriores.",
    );
}

/**
 * Periodo de tendencia válido: solo 3 o 6 meses.
 */
function icTendenciaMesesPeriodo($meses = null)
{
    global $ic_motor1_tendencia_meses_periodo;

    if ($meses === null) {
        $meses = $ic_motor1_tendencia_meses_periodo;
    }

    $meses = (int) $meses;

    return in_array($meses, array(3, 6), true) ? $meses : 6;
}

/**
 * Pesos activos del Motor 1 según si Equifax está habilitado.
 */
function icPesosEfectivosMotor1()
{
    global $ic_motor1_pesos_con_equifax, $ic_motor1_pesos_sin_equifax, $ic_motor1_equifax_activo;

    return $ic_motor1_equifax_activo ? $ic_motor1_pesos_con_equifax : $ic_motor1_pesos_sin_equifax;
}

/**
 * Pesos del Motor 1 como fracción decimal (0.35, 0.20, …).
 */
function icPesosDecimalesMotor1()
{
    $decimales = array();

    foreach (icPesosEfectivosMotor1() as $clave => $porcentaje) {
        if ($porcentaje > 0) {
            $decimales[$clave] = $porcentaje / 100;
        }
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

/**
 * Tablas explicativas para el modal de cada factor (reglas + fila aplicada al cliente).
 */

function icTablaLogicaHistorial($tolerancia, $docsATiempo, $totalDocs, $scoreHistorial)
{
    $pct = $totalDocs > 0 ? round($docsATiempo / $totalDocs * 100, 1) : 0;

    return array(
        "titulo"   => "¿Qué documentos cuentan como «al día»?",
        "intro"    => "El score es el porcentaje de documentos evaluados que cumplen las reglas siguientes.",
        "columnas" => array("Tipo de documento", "¿Cuenta a favor?", "Criterio"),
        "filas"    => array(
            array("situacion" => "Cerrado y pagado", "condicion" => "Sí", "score" => "Atraso de pago ≤ {$tolerancia} días", "aplica" => false),
            array("situacion" => "Pendiente vigente", "condicion" => "Sí", "score" => "Sin vencer o dentro de la gracia ({$tolerancia} días)", "aplica" => false),
            array("situacion" => "Pendiente vencido", "condicion" => "No", "score" => "Vencido y fuera de tolerancia sin pagar", "aplica" => false),
            array(
                "situacion"    => "→ Este cliente",
                "condicion"    => "{$docsATiempo} de {$totalDocs} al día ({$pct}%)",
                "score"        => round($scoreHistorial, 1),
                "aplica"       => true,
                "es_resultado" => true,
            ),
        ),
    );
}

function icTablaLogicaDiasAtraso($mult, $tolerancia, $atrasoPromedio, $score)
{
    $ejemplos = array(0, 15, 30, 50, 75);
    $filas = array();

    foreach ($ejemplos as $dias) {
        $ejScore = max(0, min(100, 100 - $dias * $mult));
        $filas[] = array(
            "situacion" => "Referencia: {$dias} días penalizables",
            "condicion" => "100 − ({$dias} × {$mult})",
            "score"     => round($ejScore, 1),
            "aplica"    => false,
        );
    }

    $filas[] = array(
        "situacion"    => "→ Este cliente",
        "condicion"    => round($atrasoPromedio, 1) . " días penalizables (después de {$tolerancia} días de tolerancia)",
        "score"        => round($score, 1),
        "aplica"       => true,
        "es_resultado" => true,
    );

    return array(
        "titulo"   => "Conversión de atraso a score",
        "intro"    => "Atraso penalizable = máx(0, días de mora − {$tolerancia}). Score = 100 − (promedio × {$mult}), entre 0 y 100.",
        "columnas" => array("Situación", "Cálculo", "Score"),
        "filas"    => $filas,
    );
}

function icTablaLogicaPorTramos($titulo, $intro, $unidad, $valorActual, $tramos, $scoreObtenido)
{
    $filas = array();
    $desde = 0.0;
    $valorActual = (float) $valorActual;

    foreach ($tramos as $tramo) {
        $hasta = (float) $tramo["hasta"];
        $scoreTramo = (int) $tramo["score"];

        if ($hasta >= 999) {
            $rango = "Más de " . icFormatTramoNum($desde) . $unidad;
        } elseif ($desde <= 0) {
            $rango = "Hasta " . icFormatTramoNum($hasta) . $unidad;
        } else {
            $rango = icFormatTramoNum($desde) . " a " . icFormatTramoNum($hasta) . $unidad;
        }

        $enTramo = $valorActual > $desde && ($hasta >= 999 || $valorActual <= $hasta);
        if ($desde <= 0 && $valorActual <= $hasta) {
            $enTramo = true;
        }

        $filas[] = array(
            "situacion" => $rango,
            "condicion" => "Valor en este rango",
            "score"     => $scoreTramo,
            "aplica"    => $enTramo && (int) round($scoreObtenido) === $scoreTramo,
        );

        $desde = $hasta;
    }

    return array(
        "titulo"   => $titulo,
        "intro"    => $intro,
        "columnas" => array("Rango", "Condición", "Score"),
        "filas"    => $filas,
    );
}

function icFormatTramoNum($numero)
{
    $numero = (float) $numero;

    if ($numero == (int) $numero) {
        return (string) (int) $numero;
    }

    return rtrim(rtrim(number_format($numero, 1, ".", ""), "0"), ".");
}

function icTablaLogicaTendencia($cfg, $meses, $clasificacion)
{
    $t = $cfg["tendencia"];
    $mejPct = round($t["mejorando"]["factor"] * 100);
    $estPct = round($t["estable"]["factor"] * 100);
    $neutro = (int) $cfg["score_neutro"];
    $moraLeveMax = (float) $cfg["tendencia_mora_leve_max"];
    $antiguedadMin = (int) $cfg["tendencia_antiguedad_comparativa"];

    return array(
        "titulo"   => "Clasificación de la tendencia",
        "intro"    => "Compara atraso penalizable de los últimos {$meses} meses vs los {$meses} meses anteriores.",
        "columnas" => array("Tendencia", "Cuándo aplica", "Score"),
        "filas"    => array(
            array(
                "situacion" => "Mejorando",
                "condicion" => "Atraso reciente < {$mejPct}% del periodo anterior",
                "score"     => (int) $t["mejorando"]["score"],
                "aplica"    => $clasificacion === "mejorando",
            ),
            array(
                "situacion" => "Estable",
                "condicion" => "Entre {$mejPct}% y {$estPct}% del anterior, sin atraso en ambos periodos, o mora leve inicial (≤ {$moraLeveMax} días sin historial previo)",
                "score"     => (int) $t["estable"]["score"],
                "aplica"    => $clasificacion === "estable",
            ),
            array(
                "situacion" => "Neutro (cliente nuevo)",
                "condicion" => "Antigüedad < {$antiguedadMin} meses y mora reciente > {$moraLeveMax} días sin baseline previo",
                "score"     => $neutro,
                "aplica"    => $clasificacion === "neutro_cliente_nuevo",
            ),
            array(
                "situacion" => "Empeorando",
                "condicion" => "Atraso reciente > {$estPct}% del anterior, o pasó de 0 a mora relevante (cliente con historial suficiente)",
                "score"     => (int) $t["empeorando"]["score"],
                "aplica"    => $clasificacion === "empeorando",
            ),
            array(
                "situacion" => "Sin datos",
                "condicion" => "No hay documentos suficientes en ambos periodos",
                "score"     => $neutro,
                "aplica"    => $clasificacion === "sin_datos",
            ),
        ),
    );
}

function icTablaLogicaIncidencias($penalizacion, $incidencias, $score)
{
    $filas = array();

    for ($i = 0; $i <= 4; $i++) {
        $s = max(0, 100 - $i * $penalizacion);
        $filas[] = array(
            "situacion" => $i === 0 ? "Sin incidencias" : ($i . " incidencia" . ($i > 1 ? "s" : "")),
            "condicion" => "100 − ({$i} × {$penalizacion})",
            "score"     => $s,
            "aplica"    => $incidencias === $i,
        );
    }

    if ($incidencias > 4) {
        $filas[] = array(
            "situacion"    => "→ Este cliente",
            "condicion"    => "{$incidencias} incidencias (protesta / renovación)",
            "score"        => round($score, 1),
            "aplica"       => true,
            "es_resultado" => true,
        );
    }

    return array(
        "titulo"   => "Penalización por incidencias",
        "intro"    => "Cada protesta o renovación registrada resta {$penalizacion} puntos al score del factor.",
        "columnas" => array("Incidencias", "Cálculo", "Score"),
        "filas"    => $filas,
    );
}

function icTablaLogicaEquifax($scoreNeutro)
{
    return array(
        "titulo"   => "Estado del factor Equifax",
        "intro"    => "Hasta integrar el buró de crédito, se asigna un score neutro.",
        "columnas" => array("Situación", "Condición", "Score"),
        "filas"    => array(
            array(
                "situacion" => "Sin datos de buró",
                "condicion" => "Integración pendiente en el ERP",
                "score"     => $scoreNeutro,
                "aplica"    => true,
            ),
        ),
    );
}

// ─── Motor 2: helpers ────────────────────────────────────────────────────────

function icConfigMotor2()
{
    global $ic_motor2_pesos,
        $ic_motor2_meses_periodo,
        $ic_motor2_tabla_movimientos,
        $ic_motor2_score_neutro,
        $ic_motor2_ventas_excluir_vendedores,
        $ic_motor2_zona_excluir_vendedor_prefijos,
        $ic_motor2_crecimiento_umbrales,
        $ic_motor2_frecuencia_esperada_vendedor,
        $ic_motor2_frecuencia_ratio_tramos,
        $ic_motor2_frecuencia_tramos,
        $ic_motor2_penetracion_tramos,
        $ic_motor2_tendencia_compra,
        $ic_motor2_zona_tramos,
        $ic_motor2_estacionalidad_umbrales;

    return array(
        "pesos"                      => $ic_motor2_pesos,
        "pesos_efectivos"            => $ic_motor2_pesos,
        "meses_periodo"              => icMotor2MesesPeriodo($ic_motor2_meses_periodo),
        "tabla_movimientos"          => icMotor2TablaMovimientos($ic_motor2_tabla_movimientos),
        "score_neutro"               => (int) $ic_motor2_score_neutro,
        "ventas_tipos"               => icVentasTiposValidos(),
        "ventas_excluir_vendedores"  => $ic_motor2_ventas_excluir_vendedores,
        "zona_excluir_vendedor_prefijos" => $ic_motor2_zona_excluir_vendedor_prefijos,
        "crecimiento_umbrales"       => $ic_motor2_crecimiento_umbrales,
        "frecuencia_esperada_vendedor" => $ic_motor2_frecuencia_esperada_vendedor,
        "frecuencia_ratio_tramos"    => $ic_motor2_frecuencia_ratio_tramos,
        "frecuencia_tramos"          => $ic_motor2_frecuencia_tramos,
        "penetracion_tramos"         => $ic_motor2_penetracion_tramos,
        "tendencia_compra"           => $ic_motor2_tendencia_compra,
        "zona_tramos"                => $ic_motor2_zona_tramos,
        "estacionalidad_umbrales"    => $ic_motor2_estacionalidad_umbrales,
    );
}

function icMotor2MesesPeriodo($meses = null)
{
    global $ic_motor2_meses_periodo;

    if ($meses === null) {
        $meses = $ic_motor2_meses_periodo;
    }

    $meses = (int) $meses;

    return in_array($meses, array(3, 6, 12), true) ? $meses : 6;
}

/** Texto legible de los tipos de documento ventajf incluidos. */
function icVentasTiposValidosTexto()
{
    return implode(", ", icVentasTiposValidos());
}

/** Lista de tipos ventajf válidos para Inteligencia Comercial. */
function icVentasTiposValidos()
{
    global $ic_ventas_tipos_validos;

    return $ic_ventas_tipos_validos;
}

/** Regla común de filtrado de ventajf para todos los motores. */
function icReglaVentajfInteligenciaComercial()
{
    $cfg = icConfigMotor2();
    $tipos = icVentasTiposValidosTexto();
    $vendedores = implode(", ", $cfg["ventas_excluir_vendedores"]);

    return "Fuente: ventajf (campo tipo). Documentos: {$tipos}. Excluye estado ANULADO y vendedores {$vendedores}.";
}

/** Lista SQL para UPPER(v.tipo) IN (...). */
function icVentasTiposValidosSql()
{
    global $ic_ventas_tipos_validos;

    $items = array();

    foreach ($ic_ventas_tipos_validos as $tipo) {
        $items[] = "'" . strtoupper(addslashes((string) $tipo)) . "'";
    }

    return implode(", ", $items);
}

/** @deprecated Usar icVentasTiposValidosTexto() */
function icMotor2TiposVentasTexto()
{
    return icVentasTiposValidosTexto();
}

/** @deprecated Usar icReglaVentajfInteligenciaComercial() */
function icMotor2ReglaVentajf()
{
    return icReglaVentajfInteligenciaComercial();
}

/** @deprecated Usar icVentasTiposValidosSql() */
function icMotor2TiposVentasSql()
{
    return icVentasTiposValidosSql();
}

/**
 * Rangos de fechas del Motor 2 (alineados con las consultas SQL).
 */
function icMotor2PeriodosFechas($meses = null)
{
    $meses = icMotor2MesesPeriodo($meses);
    $hoy = new DateTime("today");

    $inicioReciente = clone $hoy;
    $inicioReciente->modify("-{$meses} months");
    $finReciente = clone $hoy;

    $inicioAnterior = clone $hoy;
    $inicioAnterior->modify("-" . ($meses * 2) . " months");
    $finAnterior = clone $inicioReciente;
    $finAnterior->modify("-1 day");

    $inicioYoy = clone $inicioReciente;
    $inicioYoy->modify("-1 year");
    $finYoy = clone $hoy;
    $finYoy->modify("-1 year");

    $inicioLineas = clone $hoy;
    $inicioLineas->modify("-12 months");

    $mitad = (int) floor($meses / 2);
    $inicioTendenciaIni = clone $inicioReciente;
    $finTendenciaIni = clone $hoy;
    $finTendenciaIni->modify("-{$mitad} months");
    $finTendenciaIni->modify("-1 day");
    $inicioTendenciaFin = clone $hoy;
    $inicioTendenciaFin->modify("-{$mitad} months");
    $finTendenciaFin = clone $hoy;

    return array(
        "reciente" => array("desde" => $inicioReciente, "hasta" => $finReciente),
        "anterior" => array("desde" => $inicioAnterior, "hasta" => $finAnterior),
        "yoy"      => array("desde" => $inicioYoy, "hasta" => $finYoy),
        "lineas"   => array("desde" => $inicioLineas, "hasta" => $finReciente),
        "tendencia_ini" => array("desde" => $inicioTendenciaIni, "hasta" => $finTendenciaIni),
        "tendencia_fin" => array("desde" => $inicioTendenciaFin, "hasta" => $finTendenciaFin),
    );
}

function icMotor2FormatearRangoFechas($desde, $hasta)
{
    return $desde->format("d/m/Y") . " – " . $hasta->format("d/m/Y");
}

/** Caja única de periodos para el modal del factor. */
function icMotor2PeriodosBox(array $items)
{
    if (!$items) {
        return null;
    }

    return array(
        "titulo" => "Periodos de comparación",
        "items"  => $items,
    );
}

/** Cliente de canal usuario final (vendedor con prefijo excluido del benchmark de zona). */
function icMotor2EsClienteUsuarioFinalZona($codigoVendedor)
{
    global $ic_motor2_zona_excluir_vendedor_prefijos;

    $codigo = trim((string) $codigoVendedor);

    if ($codigo === "" || !$ic_motor2_zona_excluir_vendedor_prefijos) {
        return false;
    }

    foreach ($ic_motor2_zona_excluir_vendedor_prefijos as $prefijo) {
        $prefijo = trim((string) $prefijo);

        if ($prefijo !== "" && strpos($codigo, $prefijo) === 0) {
            return true;
        }
    }

    return false;
}

/** SQL: excluir clientes cuyo vendedor asignado inicia con prefijos de usuario final. */
function icMotor2SqlExcluirVendedorPrefijosZona($campo = "c2.vendedor")
{
    global $ic_motor2_zona_excluir_vendedor_prefijos;

    if (!$ic_motor2_zona_excluir_vendedor_prefijos) {
        return "1 = 1";
    }

    $partes = array();

    foreach ($ic_motor2_zona_excluir_vendedor_prefijos as $prefijo) {
        $prefijo = trim((string) $prefijo);

        if ($prefijo === "") {
            continue;
        }

        $prefijoSql = str_replace(array("'", "\\", "%", "_"), "", $prefijo);
        $partes[] = "TRIM({$campo}) NOT LIKE '{$prefijoSql}%'";
    }

    if (!$partes) {
        return "1 = 1";
    }

    return implode(" AND ", $partes);
}

/** Texto legible de prefijos excluidos del benchmark de zona. */
function icMotor2ZonaExcluirVendedorPrefijosTexto()
{
    global $ic_motor2_zona_excluir_vendedor_prefijos;

    if (!$ic_motor2_zona_excluir_vendedor_prefijos) {
        return "";
    }

    $items = array();

    foreach ($ic_motor2_zona_excluir_vendedor_prefijos as $prefijo) {
        $prefijo = trim((string) $prefijo);

        if ($prefijo !== "") {
            $items[] = $prefijo;
        }
    }

    return implode(", ", $items);
}

/** Config de frecuencia esperada para un vendedor (04 Norte, 05 Sur). */
function icMotor2FrecuenciaEsperadaVendedor($codigoVendedor)
{
    global $ic_motor2_frecuencia_esperada_vendedor;

    $codigo = trim((string) $codigoVendedor);

    if ($codigo === "") {
        return null;
    }

    if (isset($ic_motor2_frecuencia_esperada_vendedor[$codigo])) {
        return $ic_motor2_frecuencia_esperada_vendedor[$codigo];
    }

    $codigoPadded = str_pad($codigo, 2, "0", STR_PAD_LEFT);

    if (isset($ic_motor2_frecuencia_esperada_vendedor[$codigoPadded])) {
        return $ic_motor2_frecuencia_esperada_vendedor[$codigoPadded];
    }

    return null;
}

function icMotor2TablaMovimientos($tabla = null)
{
    global $ic_motor2_tabla_movimientos;

    if ($tabla === null) {
        $tabla = $ic_motor2_tabla_movimientos;
    }

    if (!preg_match('/^movimientosjf_\d{4}$/', $tabla)) {
        return "movimientosjf_" . date("Y");
    }

    return $tabla;
}

function icPesosDecimalesMotor2()
{
    $decimales = array();

    foreach (icConfigMotor2()["pesos"] as $clave => $porcentaje) {
        if ($porcentaje > 0) {
            $decimales[$clave] = $porcentaje / 100;
        }
    }

    return $decimales;
}

function icConfigMotor3()
{
    global $ic_motor3_pesos,
        $ic_motor3_meses_periodo,
        $ic_motor3_meses_cobertura_linea,
        $ic_motor3_meses_memoria_larga,
        $ic_motor3_piso_linea,
        $ic_motor3_score_neutro,
        $ic_motor3_promedio_tramos,
        $ic_motor3_compra_max_tramos,
        $ic_motor3_utilizacion_tramos,
        $ic_motor3_crecimiento_umbrales,
        $ic_motor3_acciones,
        $ic_motor1_antiguedad_tramos,
        $ic_motor1_equifax_activo;

    return array(
        "pesos"                  => $ic_motor3_pesos,
        "pesos_efectivos"        => $ic_motor3_pesos,
        "meses_periodo"          => (int) $ic_motor3_meses_periodo,
        "meses_cobertura_linea"  => (int) $ic_motor3_meses_cobertura_linea,
        "meses_memoria_larga"    => (int) $ic_motor3_meses_memoria_larga,
        "piso_linea"             => $ic_motor3_piso_linea,
        "score_neutro"           => (int) $ic_motor3_score_neutro,
        "ventas_tipos"           => icVentasTiposValidos(),
        "promedio_tramos"        => $ic_motor3_promedio_tramos,
        "compra_max_tramos"      => $ic_motor3_compra_max_tramos,
        "utilizacion_tramos"     => $ic_motor3_utilizacion_tramos,
        "crecimiento_umbrales"   => $ic_motor3_crecimiento_umbrales,
        "antiguedad_tramos"      => $ic_motor1_antiguedad_tramos,
        "acciones"               => $ic_motor3_acciones,
        "equifax_activo"         => (bool) $ic_motor1_equifax_activo,
    );
}

function icPesosDecimalesMotor3()
{
    $decimales = array();

    foreach (icConfigMotor3()["pesos"] as $clave => $porcentaje) {
        if ($porcentaje > 0) {
            $decimales[$clave] = $porcentaje / 100;
        }
    }

    return $decimales;
}

/**
 * Origen de la base económica (mayor entre candidatos).
 */
function icMotor3OrigenBaseEconomica($basePromedio, $compraMaxima, $compraMaxima12m, $baseEconomica)
{
    $candidatos = array(
        "promedio_mensual"   => (float) $basePromedio,
        "compra_maxima"      => (float) $compraMaxima,
        "compra_maxima_12m"  => (float) $compraMaxima12m,
    );

    foreach ($candidatos as $clave => $valor) {
        if (abs($valor - $baseEconomica) < 0.01 && $valor > 0) {
            return $clave;
        }
    }

    return "mixta";
}

/**
 * Detecta pausa comercial reciente (ej. cliente a letras que deja de pedir temporalmente).
 */
function icMotor3DetectarPausaComercial($basePromedio, $compraMaxima, $compraMaxima12m)
{
    $compraMaxima12m = (float) $compraMaxima12m;
    $compraMaxima = (float) $compraMaxima;
    $basePromedio = (float) $basePromedio;

    if ($compraMaxima12m <= 0) {
        return array("activa" => false, "nota" => "");
    }

    $porCompraReciente = $compraMaxima > 0 && $compraMaxima12m > ($compraMaxima * 1.15);
    $porPromedio = $basePromedio > 0 && $compraMaxima12m > ($basePromedio * 1.25);

    if (!$porCompraReciente && !$porPromedio) {
        return array("activa" => false, "nota" => "");
    }

    return array(
        "activa" => true,
        "nota"   => "Hay compras mayores en los últimos 12 meses que en el periodo reciente. "
            . "Puede deberse a pausa comercial por letras/cobranza sin empeorar el historial de pago.",
    );
}

/**
 * Piso de línea para buen pagador con baja utilización de la línea operativa.
 */
function icMotor3CalcularPisoLinea($cfg, $scoreRiesgo, $deudaActual, $lineaOperativa)
{
    $pisoCfg = isset($cfg["piso_linea"]) ? $cfg["piso_linea"] : array();
    $riesgoMin = isset($pisoCfg["riesgo_min"]) ? (float) $pisoCfg["riesgo_min"] : 75;
    $utilMax = isset($pisoCfg["utilizacion_max_pct"]) ? (float) $pisoCfg["utilizacion_max_pct"] : 30;
    $ratioDeuda = isset($pisoCfg["ratio_deuda"]) ? (float) $pisoCfg["ratio_deuda"] : 1.5;
    $ratioOperativa = isset($pisoCfg["ratio_operativa"]) ? (float) $pisoCfg["ratio_operativa"] : 0.20;

    $deudaActual = (float) $deudaActual;
    $lineaOperativa = (float) $lineaOperativa;

    if ($scoreRiesgo < $riesgoMin || $lineaOperativa <= 0) {
        return array("monto" => 0.0, "activo" => false, "detalle" => "");
    }

    $utilizacionReal = ($deudaActual / max($lineaOperativa, $deudaActual, 1.0)) * 100;

    if ($utilizacionReal >= $utilMax) {
        return array("monto" => 0.0, "activo" => false, "detalle" => "");
    }

    $pisoDeuda = $deudaActual > 0 ? ($deudaActual * $ratioDeuda) : 0;
    $pisoOperativa = $lineaOperativa * $ratioOperativa;
    $piso = max($pisoDeuda, $pisoOperativa);

    $detalle = "Riesgo ≥ {$riesgoMin}, utilización real " . round($utilizacionReal, 1)
        . "% < {$utilMax}%. Piso = max(deuda×{$ratioDeuda}, operativa×{$ratioOperativa}).";

    return array(
        "monto"   => round($piso, 2),
        "activo"  => $piso > 0,
        "detalle" => $detalle,
    );
}

/**
 * Monto de línea recomendada a partir de capacidad de compra y score compuesto.
 * Devuelve monto y desglose para el modal explicativo.
 * $pisoCategoria: array opcional {activo, monto, categoria_nombre, categoria_codigo}
 */
function icMotor3CalcularLineaRecomendada(
    $cfg,
    $promedioMensual,
    $compraMaxima,
    $compraMaxima12m,
    $scoreFinal,
    $scoreRiesgo,
    $deudaActual = 0,
    $lineaOperativa = 0,
    $pisoCategoria = null
) {
    $mesesCobertura = max(1, (int) $cfg["meses_cobertura_linea"]);
    $basePromedio = $promedioMensual * $mesesCobertura;
    $compraMaxima = (float) $compraMaxima;
    $compraMaxima12m = (float) $compraMaxima12m;
    $baseEconomica = max($basePromedio, $compraMaxima, $compraMaxima12m);
    $pausa = icMotor3DetectarPausaComercial($basePromedio, $compraMaxima, $compraMaxima12m);

    $pisoCatActivo = is_array($pisoCategoria) && !empty($pisoCategoria["activo"])
        && isset($pisoCategoria["monto"]) && (float) $pisoCategoria["monto"] > 0;
    $pisoCatMonto = $pisoCatActivo ? round((float) $pisoCategoria["monto"], 2) : 0.0;
    $catNombre = ($pisoCatActivo && !empty($pisoCategoria["categoria_nombre"]))
        ? $pisoCategoria["categoria_nombre"]
        : "";

    if ($baseEconomica <= 0) {
        $montoSinBase = $pisoCatMonto;
        return array(
            "monto"             => $montoSinBase,
            "meses_cobertura"   => $mesesCobertura,
            "base_promedio"     => round($basePromedio, 2),
            "compra_maxima_12m" => round($compraMaxima12m, 2),
            "base_economica"    => 0.0,
            "monto_bruto"       => 0.0,
            "piso_aplicado"     => $pisoCatMonto,
            "piso_activo"       => $pisoCatMonto > 0,
            "piso_detalle"      => $pisoCatMonto > 0
                ? "Sin base económica; se usa piso de categoría" . ($catNombre !== "" ? " ({$catNombre})" : "") . "."
                : "",
            "piso_categoria"    => $pisoCatMonto,
            "piso_categoria_activo" => $pisoCatActivo,
            "piso_categoria_nombre" => $catNombre,
            "factor_score"      => 0.0,
            "factor_riesgo"     => 0.0,
            "origen_base"       => "sin_datos",
            "pausa_comercial"   => $pausa["activa"],
            "nota_pausa"        => $pausa["nota"],
        );
    }

    $factorScore = max(0.35, min(1.0, $scoreFinal / 100));
    $factorRiesgo = max(0.5, min(1.0, $scoreRiesgo / 100));
    $montoBruto = $baseEconomica * $factorScore * $factorRiesgo;
    $piso = icMotor3CalcularPisoLinea($cfg, $scoreRiesgo, $deudaActual, $lineaOperativa);
    $pisoBuenPagador = $piso["activo"] ? (float) $piso["monto"] : 0.0;
    $pisoFinal = max($pisoBuenPagador, $pisoCatMonto);
    $montoFinal = max($montoBruto, $pisoFinal);

    $pisoDetalle = "";
    if ($pisoFinal > 0) {
        $partes = array();
        if ($pisoBuenPagador > 0) {
            $partes[] = "buen pagador S/ " . number_format($pisoBuenPagador, 2);
        }
        if ($pisoCatMonto > 0) {
            $partes[] = "categoría" . ($catNombre !== "" ? " {$catNombre}" : "")
                . " S/ " . number_format($pisoCatMonto, 2);
        }
        $pisoDetalle = "Piso = max(" . implode(", ", $partes) . ").";
        if (!empty($piso["detalle"]) && $pisoBuenPagador > 0) {
            $pisoDetalle .= " " . $piso["detalle"];
        }
    }

    return array(
        "monto"             => round(max(0, $montoFinal), 2),
        "meses_cobertura"   => $mesesCobertura,
        "base_promedio"     => round($basePromedio, 2),
        "compra_maxima_12m" => round($compraMaxima12m, 2),
        "base_economica"    => round($baseEconomica, 2),
        "monto_bruto"       => round($montoBruto, 2),
        "piso_aplicado"     => $pisoFinal,
        "piso_activo"       => $montoFinal > ($montoBruto + 0.01),
        "piso_detalle"      => $pisoDetalle,
        "piso_categoria"    => $pisoCatMonto,
        "piso_categoria_activo" => $pisoCatActivo,
        "piso_categoria_nombre" => $catNombre,
        "factor_score"      => round($factorScore, 4),
        "factor_riesgo"     => round($factorRiesgo, 4),
        "origen_base"       => icMotor3OrigenBaseEconomica($basePromedio, $compraMaxima, $compraMaxima12m, $baseEconomica),
        "pausa_comercial"   => $pausa["activa"],
        "nota_pausa"        => $pausa["nota"],
    );
}

/**
 * Tabla explicativa para factores que heredan el score de otro motor.
 */
function icTablaLogicaScoreReferencia($tituloMotor, $scoreTotal, $factoresOrigen)
{
    $filas = array();

    foreach ($factoresOrigen as $factor) {
        $filas[] = array(
            "situacion" => $factor["nombre"],
            "condicion" => "Score " . number_format($factor["score"], 1)
                . " · Peso " . (int) $factor["peso"] . "%"
                . " → +" . number_format($factor["aportacion"], 2) . " pts al motor origen",
            "score"     => number_format($factor["score"], 1),
            "aplica"    => false,
        );
    }

    $filas[] = array(
        "situacion"    => "→ Score heredado",
        "condicion"    => "Total ponderado de " . $tituloMotor . " (se usa como score de este factor)",
        "score"        => number_format($scoreTotal, 1),
        "aplica"       => true,
        "es_resultado" => true,
    );

    return array(
        "titulo"   => "Desglose del " . $tituloMotor,
        "intro"    => "Este factor no recalcula: toma el score final del motor indicado y lo pondera según el peso del Motor 3.",
        "columnas" => array("Componente origen", "Aportación al motor origen", "Score"),
        "filas"    => $filas,
    );
}

/**
 * Tabla de reglas que determinaron la acción sobre la línea.
 */
function icTablaLogicaAccionLinea($cfg, $accionClave, $scoreFinal, $scoreRiesgo, $utilizacion, $ratio)
{
    $umb = $cfg["acciones"];
    $reglas = array(
        array(
            "situacion" => "Suspender",
            "condicion" => "Riesgo < " . $umb["riesgo_suspende"] . " y hay deuda pendiente",
            "aplica"    => $accionClave === "suspender",
        ),
        array(
            "situacion" => "Aprobación manual (riesgo)",
            "condicion" => "Riesgo < " . $umb["riesgo_manual"],
            "aplica"    => $accionClave === "aprobacion_manual" && $scoreRiesgo < (int) $umb["riesgo_manual"],
        ),
        array(
            "situacion" => "Aprobar línea inicial",
            "condicion" => "Sin línea previa y score compuesto ≥ " . $umb["score_aprobar_inicial"],
            "aplica"    => $accionClave === "aprobar_inicial",
        ),
        array(
            "situacion" => "Incrementar",
            "condicion" => "Línea recomendada ≥ " . round($umb["ratio_incrementar"] * 100) . "% de la operativa, score ≥ " . $umb["score_incrementar"],
            "aplica"    => $accionClave === "incrementar",
        ),
        array(
            "situacion" => "Reducir (utilización)",
            "condicion" => "Utilización ≥ " . $umb["utilizacion_alta"] . "% y riesgo < " . $umb["utilizacion_alta_riesgo"],
            "aplica"    => $accionClave === "reducir" && $utilizacion >= (float) $umb["utilizacion_alta"],
        ),
        array(
            "situacion" => "Reducir (capacidad)",
            "condicion" => "Línea recomendada ≤ " . round($umb["ratio_reducir"] * 100) . "% de la operativa",
            "aplica"    => $accionClave === "reducir" && $ratio > 0 && $ratio <= (float) $umb["ratio_reducir"],
        ),
        array(
            "situacion" => "Mantener (baja utilización)",
            "condicion" => "Capacidad baja vs operativa, pero riesgo ≥ " . $umb["reducir_riesgo_exento"]
                . " y utilización < " . $umb["reducir_utilizacion_exenta"] . "%",
            "aplica"    => $accionClave === "mantener"
                && $ratio > 0
                && $ratio <= (float) $umb["ratio_reducir"]
                && $scoreRiesgo >= (float) $umb["reducir_riesgo_exento"]
                && $utilizacion < (float) $umb["reducir_utilizacion_exenta"],
        ),
        array(
            "situacion" => "Mantener",
            "condicion" => "Perfil y montos dentro de parámetros",
            "aplica"    => $accionClave === "mantener"
                && !($ratio > 0
                    && $ratio <= (float) $umb["ratio_reducir"]
                    && $scoreRiesgo >= (float) $umb["reducir_riesgo_exento"]
                    && $utilizacion < (float) $umb["reducir_utilizacion_exenta"]),
        ),
    );

    $filas = array();
    foreach ($reglas as $regla) {
        $filas[] = array(
            "situacion" => $regla["situacion"],
            "condicion" => $regla["condicion"],
            "score"     => $regla["aplica"] ? "✓" : "—",
            "aplica"    => $regla["aplica"],
        );
    }

    $filas[] = array(
        "situacion"    => "→ Valores actuales",
        "condicion"    => "Score " . round($scoreFinal, 1)
            . " · Riesgo " . round($scoreRiesgo, 1)
            . " · Util. " . round($utilizacion, 1) . "%"
            . ($ratio > 0 ? " · Ratio " . round($ratio, 2) . "×" : ""),
        "score"        => "—",
        "aplica"       => true,
        "es_resultado" => true,
    );

    return array(
        "titulo"   => "Reglas de la acción recomendada",
        "intro"    => "Se evalúan en orden de prioridad; la primera condición que aplica define la recomendación.",
        "columnas" => array("Acción", "Cuándo aplica", "¿Aplica?"),
        "filas"    => $filas,
    );
}

/**
 * Resumen de capacidad de pago para el Motor 3 (datos del Motor 1).
 */
function icMotor3ConstruirCapacidadPago($resultadoMotor1, $pesosMotor3, $scoreUtilizacionM3, $utilizacionOperativa, $equifaxActivo = false)
{
    $factoresPago = array("historial_pagos", "dias_atraso", "tendencia_pago", "incidencias", "utilizacion_linea", "antiguedad");
    $indicadores = array();
    $pesoPagos = (int) $pesosMotor3["score_riesgo"] + (int) $pesosMotor3["utilizacion_linea"];

    if ($equifaxActivo && !empty($pesosMotor3["equifax"])) {
        $pesoPagos += (int) $pesosMotor3["equifax"];
    }

    foreach ($factoresPago as $clave) {
        if (!isset($resultadoMotor1["factores"][$clave])) {
            continue;
        }
        $f = $resultadoMotor1["factores"][$clave];
        $indicadores[] = array(
            "clave"     => $clave,
            "nombre"    => $f["nombre"],
            "score"     => round((float) $f["score"], 1),
            "detalle"   => $f["detalle"],
            "motor"     => 1,
        );
    }

    $metricas = $resultadoMotor1["metricas"];

    return array(
        "titulo"        => "Capacidad de pago",
        "intro"         => "¿Puede y suele pagar a tiempo? Viene del Motor 1 (cuenta corriente) y pesa "
            . $pesoPagos . "% en este motor (score riesgo + utilización"
            . ($equifaxActivo ? " + Equifax" : "") . ").",
        "score_riesgo"  => round((float) $resultadoMotor1["score"], 1),
        "clasificacion" => $resultadoMotor1["clasificacion"]["etiqueta"],
        "peso_motor3"   => $pesoPagos,
        "indicadores"   => $indicadores,
        "resumen"       => array(
            array("etiqueta" => "Cumplimiento de pagos", "valor" => isset($metricas["docs_a_tiempo"], $metricas["total_docs"])
                && $metricas["total_docs"] > 0
                ? round($metricas["docs_a_tiempo"] / $metricas["total_docs"] * 100, 1) . "% al día"
                : "Sin documentos"),
            array("etiqueta" => "Atraso penalizable prom.", "valor" => round((float) $metricas["atraso_promedio"], 1) . " días"),
            array("etiqueta" => "Deuda pendiente", "valor" => "S/ " . number_format((float) $metricas["total_deuda"], 2)),
            array("etiqueta" => "Utilización (línea operativa)", "valor" => round($utilizacionOperativa, 1) . "%"),
        ),
        "factor_riesgo_linea" => "El monto recomendado se multiplica por el score de riesgo ÷ 100 (mín. 0,50).",
    );
}

/**
 * Resumen de capacidad de compra para el Motor 3.
 */
function icMotor3ConstruirCapacidadCompra(
    $resultadoMotor2,
    $pesosMotor3,
    $promedioMensual,
    $compraMaxima,
    $pctCrecimiento,
    $meses,
    $montoReciente
) {
    $pesoVentas = (int) $pesosMotor3["promedio_compras"]
        + (int) $pesosMotor3["compra_maxima"]
        + (int) $pesosMotor3["crecimiento"]
        + (int) $pesosMotor3["score_comercial"];

    return array(
        "titulo"        => "Capacidad de compra",
        "intro"         => "¿Cuánto puede comprar según su volumen? Pesa " . $pesoVentas . "% en este motor "
            . "(promedio, compra máxima, crecimiento y score comercial).",
        "score_comercial" => $resultadoMotor2
            ? round((float) $resultadoMotor2["score"], 1)
            : null,
        "clasificacion" => $resultadoMotor2 ? $resultadoMotor2["clasificacion"]["etiqueta"] : "Sin datos",
        "peso_motor3"   => $pesoVentas,
        "resumen"       => array(
            array("etiqueta" => "Promedio mensual ({$meses}m)", "valor" => "S/ " . number_format($promedioMensual, 2)),
            array("etiqueta" => "Compra máxima ({$meses}m)", "valor" => "S/ " . number_format($compraMaxima, 2)),
            array("etiqueta" => "Monto periodo", "valor" => "S/ " . number_format($montoReciente, 2)),
            array("etiqueta" => "Crecimiento vs periodo ant.", "valor" => round($pctCrecimiento, 1) . "%"),
        ),
        "base_linea"    => "La base del monto recomendado sale de aquí (promedio × meses de cobertura o compra máxima).",
    );
}

/**
 * Bloque explicativo completo para el modal de línea recomendada.
 */
function icMotor3ConstruirExplicacionLinea(
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
    $capacidadPago = null,
    $capacidadCompra = null
) {
    $mesesCobertura = (int) $calculoLinea["meses_cobertura"];
    $baseEconomica = (float) $calculoLinea["base_economica"];
    $monto = (float) $calculoLinea["monto"];
    $montoBruto = isset($calculoLinea["monto_bruto"]) ? (float) $calculoLinea["monto_bruto"] : $monto;
    $compraMaxima12m = isset($calculoLinea["compra_maxima_12m"]) ? (float) $calculoLinea["compra_maxima_12m"] : 0;
    $factorScore = (float) $calculoLinea["factor_score"];
    $factorRiesgo = (float) $calculoLinea["factor_riesgo"];
    $ratio = $lineaOperativa > 0 && $monto > 0 ? ($monto / $lineaOperativa) : 0;
    $utilizacion = $lineaOperativa > 0 ? ($deudaActual / max($lineaOperativa, $deudaActual, 1)) * 100 : 0;
    $mesesLargo = isset($cfg["meses_memoria_larga"]) ? (int) $cfg["meses_memoria_larga"] : 12;

    $origenesBase = array(
        "promedio_mensual"  => "promedio mensual × {$mesesCobertura} meses de cobertura",
        "compra_maxima"     => "compra máxima del periodo reciente ({$meses} meses)",
        "compra_maxima_12m" => "compra máxima de los últimos {$mesesLargo} meses (memoria larga)",
        "mixta"             => "el mayor entre promedio×cobertura, compra máx. reciente y compra máx. 12m",
        "sin_datos"         => "sin compras recientes para estimar base",
    );
    $origenClave = isset($calculoLinea["origen_base"]) ? $calculoLinea["origen_base"] : "mixta";
    $origenBase = isset($origenesBase[$origenClave]) ? $origenesBase[$origenClave] : $origenesBase["mixta"];

    $calculoTexto = $baseEconomica > 0
        ? "S/ " . number_format($baseEconomica, 2)
            . " × " . round($factorScore, 2)
            . " × " . round($factorRiesgo, 2)
            . " = S/ " . number_format($montoBruto, 2)
            . (!empty($calculoLinea["piso_activo"])
                ? " → piso S/ " . number_format((float) $calculoLinea["piso_aplicado"], 2)
                    . " = S/ " . number_format($monto, 2)
                : " = S/ " . number_format($monto, 2))
        : "Sin base económica en los últimos {$meses} meses.";

    $pasos = array(
        array(
            "etiqueta" => "1. Promedio mensual",
            "valor"    => "S/ " . number_format($promedioMensual, 2),
            "detalle"  => "Compras de los últimos {$meses} meses ÷ {$meses}",
        ),
        array(
            "etiqueta" => "2. Compra máxima (periodo)",
            "valor"    => "S/ " . number_format($compraMaxima, 2),
            "detalle"  => "Mayor documento de venta en los últimos {$meses} meses",
        ),
        array(
            "etiqueta" => "3. Compra máxima (12 meses)",
            "valor"    => "S/ " . number_format($compraMaxima12m, 2),
            "detalle"  => "Mayor documento en los últimos {$mesesLargo} meses (evita castigar pausas comerciales)",
        ),
        array(
            "etiqueta" => "4. Base económica",
            "valor"    => "S/ " . number_format($baseEconomica, 2),
            "detalle"  => "Se usa: " . $origenBase,
        ),
        array(
            "etiqueta" => "5. Factor score Motor 3",
            "valor"    => round($factorScore, 2) . "×",
            "detalle"  => "Score compuesto " . round($scoreFinal, 1) . " ÷ 100 (mín. 0,35)",
        ),
        array(
            "etiqueta" => "6. Factor riesgo Motor 1",
            "valor"    => round($factorRiesgo, 2) . "×",
            "detalle"  => "Score riesgo " . round($scoreRiesgo, 1) . " ÷ 100 (mín. 0,50)",
        ),
        array(
            "etiqueta" => "7. Línea bruta",
            "valor"    => "S/ " . number_format($montoBruto, 2),
            "detalle"  => "Base × factores de score y riesgo",
        ),
    );

    if (!empty($calculoLinea["piso_activo"])) {
        $etiquetaPiso = "8. Piso aplicado";
        if (!empty($calculoLinea["piso_categoria_activo"]) && (float) $calculoLinea["piso_categoria"] >= (float) $calculoLinea["piso_aplicado"] - 0.01) {
            $nombreCat = !empty($calculoLinea["piso_categoria_nombre"])
                ? $calculoLinea["piso_categoria_nombre"]
                : "categoría";
            $etiquetaPiso = "8. Piso por categoría ({$nombreCat})";
        } elseif (!empty($calculoLinea["piso_detalle"]) && strpos($calculoLinea["piso_detalle"], "buen pagador") !== false) {
            $etiquetaPiso = "8. Piso por buen pagador / categoría";
        }

        $pasos[] = array(
            "etiqueta" => $etiquetaPiso,
            "valor"    => "S/ " . number_format((float) $calculoLinea["piso_aplicado"], 2),
            "detalle"  => isset($calculoLinea["piso_detalle"]) ? $calculoLinea["piso_detalle"] : "",
        );
    }

    $pasos[] = array(
        "etiqueta" => (!empty($calculoLinea["piso_activo"]) ? "9" : "8") . ". Línea recomendada",
        "valor"    => "S/ " . number_format($monto, 2),
        "detalle"  => "Referencia para el Motor 1 (utilización) cuando es > 0",
    );

    return array(
        "titulo"   => "¿Por qué esta línea de crédito?",
        "resumen"  => $accion["explicacion"],
        "definicion_base_economica" => "Base económica = el mayor entre (promedio mensual × {$mesesCobertura}), "
            . "la compra máxima de {$meses} meses y la compra máxima de {$mesesLargo} meses. "
            . "Así no se penaliza una pausa de pedidos si el cliente sigue pagando bien.",
        "formula"  => "Línea = max(base × factor score × factor riesgo, piso buen pagador, piso categoría)",
        "calculo"  => $calculoTexto,
        "pasos"    => $pasos,
        "nota_pausa" => !empty($calculoLinea["nota_pausa"]) ? $calculoLinea["nota_pausa"] : "",
        "pausa_comercial" => !empty($calculoLinea["pausa_comercial"]),
        "comparacion" => array(
            array("etiqueta" => "Línea operativa (pico histórico)", "valor" => "S/ " . number_format($lineaOperativa, 2)),
            array("etiqueta" => "Línea recomendada", "valor" => "S/ " . number_format($monto, 2)),
            array("etiqueta" => "Deuda actual", "valor" => "S/ " . number_format($deudaActual, 2)),
            array("etiqueta" => "Ratio recomendada ÷ operativa", "valor" => $ratio > 0 ? round($ratio, 2) . "×" : "—"),
        ),
        "accion"       => $accion,
        "tabla_accion" => icTablaLogicaAccionLinea($cfg, $accion["clave"], $scoreFinal, $scoreRiesgo, $utilizacion, $ratio),
        "capacidad_pago"  => $capacidadPago,
        "capacidad_compra" => $capacidadCompra,
        "balance"       => "La línea equilibra capacidad de compra (cuánto vende) y capacidad de pago (cómo paga). "
            . "El monto sale de las compras pero se ajusta por el riesgo de cobranza.",
    );
}

/**
 * Acción de línea de crédito según scores, utilización y montos.
 */
function icMotor3DeterminarAccion($cfg, $scoreFinal, $scoreRiesgo, $utilizacion, $deuda, $lineaOperativa, $lineaRecomendada)
{
    $umb = $cfg["acciones"];
    $lineaBase = max($lineaOperativa, 1.0);
    $ratio = $lineaRecomendada > 0 ? ($lineaRecomendada / $lineaBase) : 0;

    $acciones = array(
        "aprobar_inicial"   => array("etiqueta" => "Aprobar línea inicial", "color" => "success", "icono" => "fa-plus-circle"),
        "mantener"          => array("etiqueta" => "Mantener línea actual", "color" => "primary", "icono" => "fa-check"),
        "incrementar"       => array("etiqueta" => "Incrementar línea", "color" => "success", "icono" => "fa-arrow-up"),
        "reducir"           => array("etiqueta" => "Reducir línea", "color" => "warning", "icono" => "fa-arrow-down"),
        "suspender"         => array("etiqueta" => "Suspender temporalmente", "color" => "danger", "icono" => "fa-ban"),
        "aprobacion_manual" => array("etiqueta" => "Solicitar aprobación manual", "color" => "warning", "icono" => "fa-user-md"),
    );

    $clave = "mantener";
    $motivos = array();

    if ($scoreRiesgo < (int) $umb["riesgo_suspende"] && $deuda > 0) {
        $clave = "suspender";
        $motivos[] = "Riesgo crediticio bajo (" . round($scoreRiesgo, 1) . ") con deuda pendiente.";
    } elseif ($scoreRiesgo < (int) $umb["riesgo_manual"]) {
        $clave = "aprobacion_manual";
        $motivos[] = "Score de riesgo insuficiente (" . round($scoreRiesgo, 1) . ").";
    } elseif ($lineaOperativa <= 0 && $deuda <= 0) {
        if ($scoreFinal >= (int) $umb["score_aprobar_inicial"]) {
            $clave = "aprobar_inicial";
            $motivos[] = "Sin exposición previa; perfil favorable para primera línea.";
        } else {
            $clave = "aprobacion_manual";
            $motivos[] = "Cliente sin historial de crédito; requiere validación gerencial.";
        }
    } elseif ($utilizacion >= (float) $umb["utilizacion_alta"] && $scoreRiesgo < (float) $umb["utilizacion_alta_riesgo"]) {
        $clave = "reducir";
        $motivos[] = "Utilización alta (" . round($utilizacion, 1) . "%) con riesgo medio-bajo.";
    } elseif (
        $lineaRecomendada > 0
        && $ratio >= (float) $umb["ratio_incrementar"]
        && $scoreFinal >= (int) $umb["score_incrementar"]
        && $scoreRiesgo >= (int) $umb["score_aprobar_inicial"]
    ) {
        $clave = "incrementar";
        $motivos[] = "Línea recomendada supera la operativa en " . round(($ratio - 1) * 100, 1) . "% con buen perfil.";
    } elseif ($lineaRecomendada > 0 && $ratio <= (float) $umb["ratio_reducir"]) {
        $utilizacionReal = $lineaOperativa > 0
            ? ($deuda / max($lineaOperativa, $deuda, 1.0)) * 100
            : 0;

        if (
            $scoreRiesgo >= (float) $umb["reducir_riesgo_exento"]
            && $utilizacionReal < (float) $umb["reducir_utilizacion_exenta"]
        ) {
            $clave = "mantener";
            $motivos[] = "La línea calculada es conservadora por compras recientes bajas, "
                . "pero el riesgo es bueno (" . round($scoreRiesgo, 1) . ") "
                . "y la utilización real es baja (" . round($utilizacionReal, 1) . "%). "
                . "Se sugiere mantener la línea operativa.";
        } else {
            $clave = "reducir";
            $motivos[] = "Capacidad observada supera lo que el perfil justifica hoy.";
        }
    } elseif ($lineaRecomendada <= 0 && $lineaOperativa > 0 && $deuda <= 0) {
        $clave = "aprobacion_manual";
        $motivos[] = "Tiene línea operativa histórica pero sin compras recientes para recalcular monto.";
    } elseif ($scoreFinal < (int) $umb["riesgo_suspende"]) {
        $clave = "aprobacion_manual";
        $motivos[] = "Score compuesto bajo (" . round($scoreFinal, 1) . ").";
    } else {
        $motivos[] = "Perfil y utilización dentro de parámetros para conservar la línea operativa.";
    }

    $meta = $acciones[$clave];

    return array(
        "clave"     => $clave,
        "etiqueta"  => $meta["etiqueta"],
        "color"     => $meta["color"],
        "icono"     => $meta["icono"],
        "explicacion" => implode(" ", $motivos),
    );
}

function icConfigMotor4()
{
    global $ic_motor4_pesos,
        $ic_motor4_meses_periodo,
        $ic_motor4_score_neutro,
        $ic_motor4_frecuencia_tramos,
        $ic_motor4_regularidad_tramos,
        $ic_motor4_ultima_compra_tramos,
        $ic_motor4_tendencia_fidelidad,
        $ic_motor1_antiguedad_tramos;

    return array(
        "pesos"                 => $ic_motor4_pesos,
        "pesos_efectivos"       => $ic_motor4_pesos,
        "meses_periodo"         => icMotor4MesesPeriodo($ic_motor4_meses_periodo),
        "score_neutro"          => (int) $ic_motor4_score_neutro,
        "ventas_tipos"          => icVentasTiposValidos(),
        "frecuencia_tramos"     => $ic_motor4_frecuencia_tramos,
        "antiguedad_tramos"     => $ic_motor1_antiguedad_tramos,
        "regularidad_tramos"    => $ic_motor4_regularidad_tramos,
        "ultima_compra_tramos"  => $ic_motor4_ultima_compra_tramos,
        "tendencia_fidelidad"   => $ic_motor4_tendencia_fidelidad,
        "tendencia_compra"      => $ic_motor4_tendencia_fidelidad,
    );
}

function icMotor4MesesPeriodo($meses = null)
{
    global $ic_motor4_meses_periodo;

    if ($meses === null) {
        $meses = $ic_motor4_meses_periodo;
    }

    $meses = (int) $meses;

    return in_array($meses, array(6, 12), true) ? $meses : 12;
}

function icMotor4MesesMitadTendencia($meses = null)
{
    $meses = icMotor4MesesPeriodo($meses);

    return max(1, (int) floor($meses / 2));
}

function icMotor4PeriodosFechas($meses = null)
{
    $meses = icMotor4MesesPeriodo($meses);
    $mitad = icMotor4MesesMitadTendencia($meses);
    $hoy = new DateTime("today");

    $inicioPeriodo = clone $hoy;
    $inicioPeriodo->modify("-{$meses} months");
    $finPeriodo = clone $hoy;

    $inicioTendenciaIni = clone $inicioPeriodo;
    $finTendenciaIni = clone $hoy;
    $finTendenciaIni->modify("-{$mitad} months");
    $finTendenciaIni->modify("-1 day");
    $inicioTendenciaFin = clone $hoy;
    $inicioTendenciaFin->modify("-{$mitad} months");
    $finTendenciaFin = clone $hoy;

    return array(
        "periodo"       => array("desde" => $inicioPeriodo, "hasta" => $finPeriodo),
        "tendencia_ini" => array("desde" => $inicioTendenciaIni, "hasta" => $finTendenciaIni),
        "tendencia_fin" => array("desde" => $inicioTendenciaFin, "hasta" => $finTendenciaFin),
    );
}

function icPesosDecimalesMotor4()
{
    $decimales = array();

    foreach (icConfigMotor4()["pesos"] as $clave => $porcentaje) {
        if ($porcentaje > 0) {
            $decimales[$clave] = $porcentaje / 100;
        }
    }

    return $decimales;
}

function icTablaLogicaTendenciaFidelidad($cfg, $clasificacion)
{
    $t = $cfg["tendencia_fidelidad"];
    $mejPct = round($t["mejorando"]["factor"] * 100);
    $estPct = round($t["estable"]["factor"] * 100);
    $neutro = (int) $cfg["score_neutro"];
    $meses = (int) $cfg["meses_periodo"];
    $mitad = icMotor4MesesMitadTendencia($meses);

    return array(
        "titulo"   => "Ritmo de compra en el periodo de fidelidad",
        "intro"    => "En {$meses} meses: compara los últimos {$mitad} meses con los {$mitad} meses previos del mismo periodo.",
        "columnas" => array("Tendencia", "Cuándo aplica", "Score"),
        "filas"    => array(
            array(
                "situacion" => "Mejorando",
                "condicion" => "Últimos {$mitad}m ≥ {$mejPct}% de los primeros {$mitad}m",
                "score"     => (int) $t["mejorando"]["score"],
                "aplica"    => $clasificacion === "mejorando",
            ),
            array(
                "situacion" => "Estable",
                "condicion" => "Últimos {$mitad}m entre {$estPct}% y {$mejPct}% de los primeros {$mitad}m",
                "score"     => (int) $t["estable"]["score"],
                "aplica"    => $clasificacion === "estable",
            ),
            array(
                "situacion" => "Empeorando",
                "condicion" => "Últimos {$mitad}m < {$estPct}% de los primeros {$mitad}m",
                "score"     => (int) $t["empeorando"]["score"],
                "aplica"    => $clasificacion === "empeorando",
            ),
            array(
                "situacion" => "Sin datos",
                "condicion" => "Sin compras en el periodo de {$meses} meses",
                "score"     => $neutro,
                "aplica"    => $clasificacion === "sin_datos",
            ),
        ),
    );
}

function icScorePorUmbralesMinimos($valor, $umbrales)
{
    foreach ($umbrales as $umbral) {
        if ($valor >= $umbral["desde"]) {
            return (int) $umbral["score"];
        }
    }

    return (int) end($umbrales)["score"];
}

function icMotor2MesesMitadTendencia($meses = null)
{
    $meses = icMotor2MesesPeriodo($meses);

    return max(1, (int) floor($meses / 2));
}

function icClasificarTendenciaCompra($cfg, $montoUltimos, $montoPrimeros)
{
    $tendCfg = $cfg["tendencia_compra"];
    $scoreNeutro = (int) $cfg["score_neutro"];
    $meses = (int) $cfg["meses_periodo"];
    $mitad = icMotor2MesesMitadTendencia($meses);

    if ($montoUltimos <= 0 && $montoPrimeros <= 0) {
        return array(
            "score"         => $scoreNeutro,
            "clasificacion" => "sin_datos",
            "detalle"       => "Sin compras en el periodo reciente de {$meses} meses.",
        );
    }

    if ($montoPrimeros <= 0 && $montoUltimos > 0) {
        return array(
            "score"         => (int) $tendCfg["mejorando"]["score"],
            "clasificacion" => "mejorando",
            "detalle"       => "Mejorando: compras en los últimos {$mitad} meses sin historial en los {$mitad} meses previos del periodo.",
        );
    }

    if ($montoUltimos >= $montoPrimeros * $tendCfg["mejorando"]["factor"]) {
        return array(
            "score"         => (int) $tendCfg["mejorando"]["score"],
            "clasificacion" => "mejorando",
            "detalle"       => "Mejorando: S/ " . number_format($montoUltimos, 2) . " (últimos {$mitad}m) vs S/ "
                . number_format($montoPrimeros, 2) . " (primeros {$mitad}m del periodo).",
        );
    }

    if ($montoUltimos >= $montoPrimeros * $tendCfg["estable"]["factor"]) {
        return array(
            "score"         => (int) $tendCfg["estable"]["score"],
            "clasificacion" => "estable",
            "detalle"       => "Estable: S/ " . number_format($montoUltimos, 2) . " (últimos {$mitad}m) vs S/ "
                . number_format($montoPrimeros, 2) . " (primeros {$mitad}m del periodo).",
        );
    }

    return array(
        "score"         => (int) $tendCfg["empeorando"]["score"],
        "clasificacion" => "empeorando",
        "detalle"       => "Empeorando: S/ " . number_format($montoUltimos, 2) . " (últimos {$mitad}m) vs S/ "
            . number_format($montoPrimeros, 2) . " (primeros {$mitad}m del periodo).",
    );
}

function icPctCrecimiento($reciente, $anterior)
{
    if ($anterior > 0) {
        return (($reciente - $anterior) / $anterior) * 100;
    }

    if ($reciente > 0) {
        return 100;
    }

    return 0;
}

function icTablaLogicaPorUmbrales($titulo, $intro, $columnas, $umbrales, $valorActual, $scoreObtenido, $sufijo = "%")
{
    $filas = array();
    $scoreCalculado = icScorePorUmbralesMinimos($valorActual, $umbrales);

    foreach ($umbrales as $umbral) {
        $desde = $umbral["desde"];
        $etiqueta = $desde >= 0
            ? "≥ " . $desde . $sufijo
            : "< " . abs($desde) . $sufijo;

        $filas[] = array(
            "situacion" => $etiqueta,
            "condicion" => "Valor en este rango",
            "score"     => (int) $umbral["score"],
            "aplica"    => (int) $umbral["score"] === (int) $scoreCalculado
                && (int) round($scoreObtenido) === (int) $scoreCalculado,
        );
    }

    return array(
        "titulo"   => $titulo,
        "intro"    => $intro,
        "columnas" => $columnas,
        "filas"    => $filas,
    );
}

function icTablaLogicaTendenciaCompra($cfg, $clasificacion)
{
    $t = $cfg["tendencia_compra"];
    $mejPct = round($t["mejorando"]["factor"] * 100);
    $estPct = round($t["estable"]["factor"] * 100);
    $neutro = (int) $cfg["score_neutro"];
    $meses = (int) $cfg["meses_periodo"];
    $mitad = icMotor2MesesMitadTendencia($meses);

    return array(
        "titulo"   => "Clasificación del ritmo de compra",
        "intro"    => "Dentro de los últimos {$meses} meses: compara los últimos {$mitad} meses con los {$mitad} meses previos del mismo periodo.",
        "columnas" => array("Tendencia", "Cuándo aplica", "Score"),
        "filas"    => array(
            array(
                "situacion" => "Mejorando",
                "condicion" => "Últimos {$mitad}m ≥ {$mejPct}% de los primeros {$mitad}m, o compras nuevas solo al final del periodo",
                "score"     => (int) $t["mejorando"]["score"],
                "aplica"    => $clasificacion === "mejorando",
            ),
            array(
                "situacion" => "Estable",
                "condicion" => "Últimos {$mitad}m entre {$estPct}% y {$mejPct}% de los primeros {$mitad}m",
                "score"     => (int) $t["estable"]["score"],
                "aplica"    => $clasificacion === "estable",
            ),
            array(
                "situacion" => "Empeorando",
                "condicion" => "Últimos {$mitad}m < {$estPct}% de los primeros {$mitad}m",
                "score"     => (int) $t["empeorando"]["score"],
                "aplica"    => $clasificacion === "empeorando",
            ),
            array(
                "situacion" => "Sin datos",
                "condicion" => "Sin compras en el periodo reciente de {$meses} meses",
                "score"     => $neutro,
                "aplica"    => $clasificacion === "sin_datos",
            ),
        ),
    );
}

// ─── Resumen ejecutivo con OpenAI (narrativa; no calcula scores) ───────────────

require_once __DIR__ . "/inteligencia-comercial.prompt.php";

function icConfigOpenAi()
{
    $modelosRespaldo = array();
    if (defined("OPENAI_IC_MODELOS_RESPALDO")) {
        $partes = explode(",", (string) OPENAI_IC_MODELOS_RESPALDO);
        foreach ($partes as $parte) {
            $modelo = trim($parte);
            if ($modelo !== "") {
                $modelosRespaldo[] = $modelo;
            }
        }
    }

    return array(
        "activo"           => defined("OPENAI_IC_ACTIVO") ? (bool) OPENAI_IC_ACTIVO : false,
        "api_key"          => defined("OPENAI_API_KEY") ? trim((string) OPENAI_API_KEY) : "",
        "modelo"           => defined("OPENAI_IC_MODELO") ? trim((string) OPENAI_IC_MODELO) : "gpt-4o-mini",
        "modelos_respaldo" => $modelosRespaldo,
        "reintentos"       => defined("OPENAI_IC_REINTENTOS") ? max(1, (int) OPENAI_IC_REINTENTOS) : 2,
        "espera_reintento" => defined("OPENAI_IC_ESPERA_REINTENTO_MS") ? max(0, (int) OPENAI_IC_ESPERA_REINTENTO_MS) : 1000,
        "timeout"          => 45,
    );
}

function icOpenAiModelosIntentar($cfg)
{
    $lista = array();
    $principal = !empty($cfg["modelo"]) ? trim((string) $cfg["modelo"]) : "gpt-4o-mini";

    if ($principal !== "") {
        $lista[] = $principal;
    }

    if (!empty($cfg["modelos_respaldo"]) && is_array($cfg["modelos_respaldo"])) {
        foreach ($cfg["modelos_respaldo"] as $modelo) {
            $modelo = trim((string) $modelo);
            if ($modelo !== "" && !in_array($modelo, $lista, true)) {
                $lista[] = $modelo;
            }
        }
    }

    return $lista;
}

function icOpenAiErrorEsModeloNoDisponible($detalle)
{
    $detalle = strtolower((string) $detalle);

    return strpos($detalle, "does not have access to model") !== false
        || strpos($detalle, "model_not_found") !== false
        || strpos($detalle, "no tiene acceso al modelo") !== false;
}

function icOpenAiErrorEsReintentable($httpCode, $detalle)
{
    if ($httpCode === 429 || $httpCode === 500 || $httpCode === 502 || $httpCode === 503 || $httpCode === 504) {
        return true;
    }

    $detalle = strtolower((string) $detalle);

    return strpos($detalle, "timeout") !== false
        || strpos($detalle, "overloaded") !== false
        || strpos($detalle, "rate limit") !== false;
}

/**
 * Un solo intento HTTP a Chat Completions.
 */
function icOpenAiChatCompletionIntento($mensajes, $modelo, $cfg)
{
    $payload = json_encode(array(
        "model"       => $modelo,
        "messages"    => $mensajes,
        "temperature" => 0.35,
        "max_tokens"  => 700,
    ), JSON_UNESCAPED_UNICODE);

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $cfg["api_key"],
        ),
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => (int) $cfg["timeout"],
        CURLOPT_CONNECTTIMEOUT => 15,
    ));

    $respuesta = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return array(
            "ok"        => false,
            "msg"       => "Error de conexión con OpenAI: " . $error,
            "http_code" => 0,
            "modelo"    => $modelo,
            "reintentar" => true,
            "cambiar_modelo" => false,
        );
    }

    $json = json_decode($respuesta, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $detalle = isset($json["error"]["message"]) ? $json["error"]["message"] : ("HTTP " . $httpCode);

        return array(
            "ok"             => false,
            "msg"            => "OpenAI: " . $detalle,
            "http_code"      => $httpCode,
            "modelo"         => $modelo,
            "reintentar"     => icOpenAiErrorEsReintentable($httpCode, $detalle),
            "cambiar_modelo" => icOpenAiErrorEsModeloNoDisponible($detalle),
        );
    }

    if (!isset($json["choices"][0]["message"]["content"])) {
        return array(
            "ok"             => false,
            "msg"            => "Respuesta vacía de OpenAI.",
            "http_code"      => $httpCode,
            "modelo"         => $modelo,
            "reintentar"     => true,
            "cambiar_modelo" => false,
        );
    }

    return array(
        "ok"      => true,
        "content" => (string) $json["choices"][0]["message"]["content"],
        "modelo"  => $modelo,
    );
}

/**
 * Extrae factores más débiles y más fuertes de un motor.
 */
function icResumenIaFactoresDestacados($factores, $limite = 3)
{
    $lista = array();

    if (!is_array($factores)) {
        return array("debiles" => array(), "fuertes" => array());
    }

    foreach ($factores as $factor) {
        if (!is_array($factor) || !isset($factor["nombre"], $factor["score"])) {
            continue;
        }

        $lista[] = array(
            "nombre"   => (string) $factor["nombre"],
            "score"    => round((float) $factor["score"], 1),
            "peso_pct" => isset($factor["peso"]) ? (int) $factor["peso"] : null,
        );
    }

    if (empty($lista)) {
        return array("debiles" => array(), "fuertes" => array());
    }

    $debiles = $lista;
    usort($debiles, function ($a, $b) {
        if ($a["score"] === $b["score"]) {
            return 0;
        }

        return ($a["score"] < $b["score"]) ? -1 : 1;
    });
    $debiles = array_slice($debiles, 0, $limite);

    $fuertes = $lista;
    usort($fuertes, function ($a, $b) {
        if ($a["score"] === $b["score"]) {
            return 0;
        }

        return ($a["score"] > $b["score"]) ? -1 : 1;
    });
    $fuertes = array_slice($fuertes, 0, $limite);

    return array(
        "debiles" => $debiles,
        "fuertes" => $fuertes,
    );
}

function icResumenIaMotorContexto($etiqueta, $resultado)
{
    if (!$resultado || !is_array($resultado)) {
        return null;
    }

    $destacados = icResumenIaFactoresDestacados(isset($resultado["factores"]) ? $resultado["factores"] : array());

    return array(
        "etiqueta"         => $etiqueta,
        "score"            => round((float) $resultado["score"], 1),
        "clasificacion"    => isset($resultado["clasificacion"]["etiqueta"])
            ? (string) $resultado["clasificacion"]["etiqueta"]
            : "",
        "requiere_atencion" => round((float) $resultado["score"], 1) < 70,
        "factores_debiles" => $destacados["debiles"],
        "factores_fuertes" => $destacados["fuertes"],
    );
}

/**
 * Redondeo conservador de líneas de crédito (recomendada / aprobada) al múltiplo inferior.
 * Ej.: 12.750 → 12.000 · 13.001 → 13.000 · montos menores a 1.000 → 0.
 */
function icRedondearLineaCredito($monto, $incremento = 1000)
{
    $monto = (float) $monto;

    if ($monto <= 0) {
        return 0.0;
    }

    $incremento = max(1, (int) $incremento);

    return floor($monto / $incremento) * $incremento;
}

/**
 * Referencia de cupo: disponible, excedido y pago mínimo para regularizar.
 */
function icCalcularReferenciaCupoLinea($deudaActual, $lineaRecomendada)
{
    $deudaActual = max(0, (float) $deudaActual);
    $lineaRecomendada = icRedondearLineaCredito(max(0, (float) $lineaRecomendada));
    $disponible = max(0, $lineaRecomendada - $deudaActual);
    $excedido = max(0, $deudaActual - $lineaRecomendada);
    $tieneExcedido = $lineaRecomendada > 0 && $excedido > 0.01;
    $cupoAgotado = $lineaRecomendada > 0 && $disponible <= 0.01;
    $pagoMinimo = $tieneExcedido ? round($excedido, 2) : 0.0;

    $resultado = array(
        "disponible_nuevo_credito"     => round($disponible, 2),
        "excedido_sobre_recomendada"    => $tieneExcedido ? round($excedido, 2) : 0.0,
        "cupo_agotado"                 => $cupoAgotado,
        "tiene_excedido"               => $tieneExcedido,
        "pago_minimo_regularizar"      => $pagoMinimo,
        "deuda_tras_pago_minimo"       => $tieneExcedido ? round($lineaRecomendada, 2) : round($deudaActual, 2),
        "solucion_pago"                => null,
    );

    if ($tieneExcedido) {
        $resultado["solucion_pago"] = array(
            "monto"    => $pagoMinimo,
            "objetivo" => "regularizar_cupo",
            "mensaje"  => "Abonar al menos S/ " . number_format($pagoMinimo, 2)
                . " para bajar la deuda al cupo recomendado (S/ " . number_format($lineaRecomendada, 2)
                . ") y recuperar margen para un pedido a crédito.",
            "formula"  => "pago_mínimo = deuda_actual − linea_recomendada",
        );
    }

    return $resultado;
}

/**
 * Contexto compacto para el prompt de OpenAI (datos ya calculados por los motores).
 */
function icConstruirContextoResumenIa($analisis, $esGrupo = false)
{
    $m1 = isset($analisis["motor1"]) ? $analisis["motor1"] : null;
    $m2 = isset($analisis["motor2"]) ? $analisis["motor2"] : null;
    $mLinea = isset($analisis["motor3"]) ? $analisis["motor3"] : null;
    $mFidelidad = isset($analisis["motor4"]) ? $analisis["motor4"] : null;
    $cierre = isset($analisis["cierre"]) ? $analisis["cierre"] : null;

    $cliente = array("codigo" => "", "nombre" => "");
    if ($m1 && isset($m1["cliente"])) {
        $cliente["codigo"] = (string) $m1["cliente"]["codigo"];
        $cliente["nombre"] = (string) $m1["cliente"]["nombre"];
    }

    $contexto = array(
        "cliente" => $cliente,
        "consolidado" => (bool) $esGrupo,
        "motores" => array(),
        "guia_para_interpretar" => array(
            "nota" => $esGrupo
                ? "Análisis consolidado de grupo empresarial (varios RUC). El usuario ya ve scores en pantalla; interprétalos sin repetir números."
                : "El usuario ya ve scores y gráficos en pantalla. Interprétalos; no repitas los números.",
            "motores" => array(
                "Motor 1 — Riesgo" => "¿Se le puede fiar? Pagos, atrasos, utilización de línea, antigüedad.",
                "Motor 2 — Comercial" => "¿Vale la pena venderle más? Frecuencia, volumen, crecimiento, tendencia.",
                "Motor 3 — Fidelidad" => "¿Sigue siendo cliente leal? Regularidad, última compra, constancia.",
                "Motor 4 — Línea" => "Recomendación global: acción sobre la línea, cupo recomendado y disponible para un pedido nuevo (recomendada − deuda).",
            ),
        ),
    );

    if ($esGrupo) {
        $contexto["tipo_analisis"] = "grupo_empresarial";
        $contexto["guia_para_interpretar"]["motores"]["Nota grupo"] =
            "Las compras, deudas e historial de pagos están consolidados de todos los RUC del grupo. En la tabla de locales se resalta el RUC con peor historial individual como alerta.";
        $peorRuc = isset($analisis["peor_ruc"]) ? $analisis["peor_ruc"] : null;
        if ($peorRuc && !empty($peorRuc["codigo"])) {
            $contexto["ruc_critico_grupo"] = array(
                "codigo" => (string) $peorRuc["codigo"],
                "nombre" => isset($peorRuc["nombre"]) ? (string) $peorRuc["nombre"] : "",
                "nota"   => "Local con peor historial de pago individual dentro del grupo; no reemplaza el historial consolidado del Motor 1.",
            );
        }
    }

    if ($esGrupo && $cierre) {
        $contexto["cierre_mensual"] = array(
            "mes"                  => isset($cierre["mes_actual_label"]) ? $cierre["mes_actual_label"] : "",
            "compra_mes_actual"    => isset($cierre["compra_mes_actual"]) ? (float) $cierre["compra_mes_actual"] : 0,
            "compra_mes_anterior"  => isset($cierre["compra_mes_anterior"]) ? (float) $cierre["compra_mes_anterior"] : 0,
            "pct_vs_anterior"      => isset($cierre["pct_vs_anterior"]) ? (float) $cierre["pct_vs_anterior"] : 0,
            "promedio_mensual_6m"  => isset($cierre["promedio_mensual"]) ? (float) $cierre["promedio_mensual"] : 0,
            "faltante_meta"        => isset($cierre["faltante_meta"]) ? (float) $cierre["faltante_meta"] : 0,
            "rucs_compraron_mes"   => isset($cierre["rucs_compraron_mes"]) ? (int) $cierre["rucs_compraron_mes"] : 0,
            "total_rucs"           => isset($cierre["total_rucs"]) ? (int) $cierre["total_rucs"] : 0,
        );
    }

    $mapa = array(
        array("Motor 1 — Riesgo crediticio", $m1),
        array("Motor 2 — Comercial", $m2),
        array("Motor 3 — Fidelidad", $mFidelidad),
        array("Motor 4 — Línea de crédito (recomendación global)", $mLinea),
    );

    foreach ($mapa as $item) {
        $bloque = icResumenIaMotorContexto($item[0], $item[1]);
        if ($bloque) {
            $contexto["motores"][] = $bloque;
        }
    }

    if ($mLinea && isset($mLinea["linea"])) {
        $linea = $mLinea["linea"];
        $explicacion = isset($mLinea["explicacion_linea"]) && is_array($mLinea["explicacion_linea"])
            ? $mLinea["explicacion_linea"]
            : array();

        $pasosClave = array();
        if (!empty($explicacion["pasos"]) && is_array($explicacion["pasos"])) {
            $indices = array(3, 4, 5, 6, 7, 8);
            foreach ($indices as $idx) {
                if (!isset($explicacion["pasos"][$idx])) {
                    continue;
                }
                $paso = $explicacion["pasos"][$idx];
                $pasosClave[] = array(
                    "etiqueta" => isset($paso["etiqueta"]) ? (string) $paso["etiqueta"] : "",
                    "valor"    => isset($paso["valor"]) ? (string) $paso["valor"] : "",
                    "detalle"  => isset($paso["detalle"]) ? (string) $paso["detalle"] : "",
                );
            }
        }

        $deudaActual = isset($linea["deuda_actual"]) ? (float) $linea["deuda_actual"] : 0;
        $lineaRecomendada = isset($linea["linea_recomendada"]) ? (float) $linea["linea_recomendada"] : 0;
        $refCupo = icCalcularReferenciaCupoLinea($deudaActual, $lineaRecomendada);
        $disponibleNuevo = $refCupo["disponible_nuevo_credito"];
        $excedidoSobreRecomendada = $refCupo["excedido_sobre_recomendada"];
        $tieneExcedido = $refCupo["tiene_excedido"];

        $contexto["linea_credito"] = array(
            "score_motor"        => round((float) $mLinea["score"], 1),
            "accion"             => isset($mLinea["accion"]["etiqueta"]) ? $mLinea["accion"]["etiqueta"] : "",
            "explicacion_accion" => isset($mLinea["accion"]["explicacion"]) ? $mLinea["accion"]["explicacion"] : "",
            "deuda_actual"       => $deudaActual,
            "linea_operativa"    => isset($linea["linea_operativa"]) ? (float) $linea["linea_operativa"] : 0,
            "linea_recomendada"  => $lineaRecomendada,
            "disponible_nuevo_credito" => $disponibleNuevo,
            "excedido_sobre_recomendada" => $excedidoSobreRecomendada,
            "cupo_agotado"       => $refCupo["cupo_agotado"],
            "pago_minimo_regularizar" => $refCupo["pago_minimo_regularizar"],
            "deuda_tras_pago_minimo"  => $refCupo["deuda_tras_pago_minimo"],
            "solucion_pago"      => $refCupo["solucion_pago"],
            "nota_disponible"    => "Línea recomendada = cupo total sugerido. Disponible = recomendada − deuda. "
                . "Si hay excedido, pago_minimo_regularizar indica cuánto debe abonar para volver al cupo sugerido.",
            "utilizacion_pct"    => isset($linea["utilizacion_pct"]) ? (float) $linea["utilizacion_pct"] : 0,
            "por_que"            => array(
                "calculo"        => isset($explicacion["calculo"]) ? (string) $explicacion["calculo"] : "",
                "resumen_motor"  => isset($explicacion["resumen"]) ? (string) $explicacion["resumen"] : "",
                "origen_base"    => "",
                "pasos_clave"    => $pasosClave,
                "comparacion"    => isset($explicacion["comparacion"]) ? $explicacion["comparacion"] : array(),
            ),
        );

        if (!empty($explicacion["pasos"][3]["detalle"])) {
            $contexto["linea_credito"]["por_que"]["origen_base"] = (string) $explicacion["pasos"][3]["detalle"];
        }

        if (!empty($explicacion["pausa_comercial"])) {
            $contexto["linea_credito"]["pausa_comercial"] = true;
            if (!empty($explicacion["nota_pausa"])) {
                $contexto["linea_credito"]["nota_pausa"] = $explicacion["nota_pausa"];
            }
        }

        if ($lineaRecomendada > 0) {
            if (!isset($contexto["linea_credito"]["por_que"]["comparacion"])
                || !is_array($contexto["linea_credito"]["por_que"]["comparacion"])) {
                $contexto["linea_credito"]["por_que"]["comparacion"] = array();
            }

            if ($tieneExcedido) {
                $contexto["linea_credito"]["por_que"]["comparacion"][] = array(
                    "etiqueta" => "Excedido sobre cupo recomendado",
                    "valor"    => "S/ " . number_format($excedidoSobreRecomendada, 2),
                );
                $contexto["linea_credito"]["por_que"]["comparacion"][] = array(
                    "etiqueta" => "Abono mínimo sugerido para regularizar",
                    "valor"    => "S/ " . number_format($refCupo["pago_minimo_regularizar"], 2),
                );
            } else {
                $contexto["linea_credito"]["por_que"]["comparacion"][] = array(
                    "etiqueta" => "Disponible para nuevo crédito",
                    "valor"    => "S/ " . number_format($disponibleNuevo, 2),
                );
            }
        }
    }

    return $contexto;
}

function icOpenAiMensajesResumenCliente($contexto)
{
    $jsonContexto = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $userContent = icPromptResumenIaUsuario($jsonContexto);

    if (!empty($contexto["consolidado"])) {
        $userContent = "Análisis consolidado de GRUPO EMPRESARIAL (varios RUC del mismo grupo; compras y deudas sumadas). "
            . "Incluye cierre mensual del grupo si está en el JSON. "
            . $userContent;
    }

    return array(
        array(
            "role"    => "system",
            "content" => icPromptResumenIaSistema(),
        ),
        array(
            "role"    => "user",
            "content" => $userContent,
        ),
    );
}

function icOpenAiParsearResumenJson($texto)
{
    $texto = trim((string) $texto);
    $datos = json_decode($texto, true);

    if (!is_array($datos) && preg_match('/\{[\s\S]*\}/', $texto, $coincidencias)) {
        $datos = json_decode($coincidencias[0], true);
    }

    if (!is_array($datos)) {
        return icOpenAiNormalizarResumenParseado(array(
            "decision"      => "",
            "que_significa" => array(),
            "linea_credito" => trim($texto),
            "como_mejorar"  => array(),
        ));
    }

    return icOpenAiNormalizarResumenParseado($datos);
}

function icOpenAiNormalizarResumenParseado($datos)
{
    $decision = isset($datos["decision"]) ? trim((string) $datos["decision"]) : "";

    $queSignifica = array();
    if (isset($datos["que_significa"]) && is_array($datos["que_significa"])) {
        $queSignifica = array_values(array_filter(array_map("trim", $datos["que_significa"])));
    } elseif (isset($datos["alertas"]) && is_array($datos["alertas"])) {
        $queSignifica = array_values(array_filter(array_map("trim", $datos["alertas"])));
    } elseif (!empty($datos["resumen"])) {
        $queSignifica = array(trim((string) $datos["resumen"]));
    }

    $lineaCredito = "";
    if (isset($datos["linea_credito"])) {
        $lineaCredito = trim((string) $datos["linea_credito"]);
    } elseif (isset($datos["linea_credito_por_que"])) {
        $lineaCredito = trim((string) $datos["linea_credito_por_que"]);
    }

    $comoMejorar = array();
    if (isset($datos["como_mejorar"]) && is_array($datos["como_mejorar"])) {
        $comoMejorar = array_values(array_filter(array_map("trim", $datos["como_mejorar"])));
    } elseif (isset($datos["recomendaciones"]) && is_array($datos["recomendaciones"])) {
        $comoMejorar = array_values(array_filter(array_map("trim", $datos["recomendaciones"])));
    }

    $resumenPartes = array();
    if ($decision !== "") {
        $resumenPartes[] = $decision;
    }
    if (!empty($queSignifica)) {
        $resumenPartes[] = implode("\n", $queSignifica);
    }
    if ($lineaCredito !== "") {
        $resumenPartes[] = $lineaCredito;
    }

    return array(
        "decision"              => $decision,
        "que_significa"         => $queSignifica,
        "linea_credito"         => $lineaCredito,
        "como_mejorar"          => $comoMejorar,
        "alertas"               => $queSignifica,
        "linea_credito_por_que" => $lineaCredito,
        "recomendaciones"       => $comoMejorar,
        "resumen"               => implode("\n\n", $resumenPartes),
    );
}

/**
 * Llama a la API de OpenAI Chat Completions (reintentos + modelos de respaldo).
 */
function icOpenAiChatCompletion($mensajes, $cfg = null)
{
    if ($cfg === null) {
        $cfg = icConfigOpenAi();
    }

    if (empty($cfg["activo"])) {
        return array("ok" => false, "msg" => "Resumen con IA desactivado en configuración.");
    }

    if (empty($cfg["api_key"])) {
        return array("ok" => false, "msg" => "Configure OPENAI_API_KEY en controladores/config.php");
    }

    $modelos = icOpenAiModelosIntentar($cfg);
    $maxReintentos = !empty($cfg["reintentos"]) ? (int) $cfg["reintentos"] : 2;
    $esperaMs = isset($cfg["espera_reintento"]) ? (int) $cfg["espera_reintento"] : 1000;
    $modeloSolicitado = !empty($cfg["modelo"]) ? $cfg["modelo"] : "gpt-4o-mini";
    $ultimoError = "No se pudo contactar a OpenAI.";

    foreach ($modelos as $modelo) {
        $intentosModelo = $maxReintentos;

        for ($intento = 1; $intento <= $intentosModelo; $intento++) {
            $resultado = icOpenAiChatCompletionIntento($mensajes, $modelo, $cfg);

            if (!empty($resultado["ok"])) {
                $resultado["modelo_solicitado"] = $modeloSolicitado;
                $resultado["modelo_respaldo"] = ($modelo !== $modeloSolicitado);

                return $resultado;
            }

            $ultimoError = $resultado["msg"];

            if (!empty($resultado["cambiar_modelo"])) {
                break;
            }

            if (empty($resultado["reintentar"]) || $intento >= $intentosModelo) {
                break;
            }

            if ($esperaMs > 0) {
                usleep($esperaMs * 1000);
            }
        }
    }

    return array(
        "ok"  => false,
        "msg" => $ultimoError . " Si persiste, verifique en OpenAI que el proyecto de la API key tenga habilitado el modelo.",
    );
}

function icOpenAiGenerarResumenCliente($contexto)
{
    $mensajes = icOpenAiMensajesResumenCliente($contexto);
    $llamada = icOpenAiChatCompletion($mensajes);

    if (empty($llamada["ok"])) {
        return $llamada;
    }

    $parseado = icOpenAiParsearResumenJson($llamada["content"]);

    return array(
        "ok"                    => true,
        "resumen"               => $parseado["resumen"],
        "decision"              => $parseado["decision"],
        "que_significa"         => $parseado["que_significa"],
        "linea_credito"         => $parseado["linea_credito"],
        "como_mejorar"          => $parseado["como_mejorar"],
        "alertas"               => $parseado["alertas"],
        "linea_credito_por_que" => $parseado["linea_credito_por_que"],
        "recomendaciones"       => $parseado["recomendaciones"],
        "modelo"                => isset($llamada["modelo"]) ? $llamada["modelo"] : "",
        "modelo_respaldo"       => !empty($llamada["modelo_respaldo"]),
    );
}
