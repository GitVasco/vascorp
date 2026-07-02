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

/** Tramos de utilización de línea (% deuda / crédito histórico) → score. */
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
