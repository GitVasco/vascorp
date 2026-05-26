<?php

class ControladorDashboardCobranzas
{
    private static function diasDelMes($anno, $mes)
    {
        $mes = (int) $mes;
        $anno = (int) $anno;

        if (function_exists("cal_days_in_month")) {
            return (int) cal_days_in_month(CAL_GREGORIAN, $mes, $anno);
        }

        $diasPorMes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        if ($mes === 2) {
            $bisiesto = ($anno % 400 === 0) || ($anno % 4 === 0 && $anno % 100 !== 0);
            return $bisiesto ? 29 : 28;
        }

        return isset($diasPorMes[$mes - 1]) ? $diasPorMes[$mes - 1] : 31;
    }

    private static function periodoAnterior($anno, $mes)
    {
        $mesAnt = (int) $mes - 1;
        $annoAnt = (int) $anno;

        if ($mesAnt < 1) {
            $mesAnt = 12;
            $annoAnt--;
        }

        return ["anno" => $annoAnt, "mes" => $mesAnt];
    }

    private static function variacionPorcentaje($actual, $anterior)
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;

        if ($anterior == 0) {
            if ($actual == 0) {
                return 0;
            }
            return 100;
        }

        return (($actual - $anterior) / abs($anterior)) * 100;
    }

    private static function promedioDiario($total, $diasConMovimiento)
    {
        $dias = (int) $diasConMovimiento;
        if ($dias <= 0) {
            return 0;
        }
        return (float) $total / $dias;
    }

    private static function construirSparklines($anno, $mes, $vendedor, $codigoMejorVendedor)
    {
        $ultimoDia = self::diasDelMes($anno, $mes);
        $filasSerie = ModeloDashboardCobranzas::mdlSerieDiariaMes($anno, $mes, $vendedor);

        $filasVendedor = array();
        if ($codigoMejorVendedor !== null && $codigoMejorVendedor !== "") {
            if ($vendedor !== "" && $vendedor === $codigoMejorVendedor) {
                $filasVendedor = $filasSerie;
            } else {
                $filasVendedor = ModeloDashboardCobranzas::mdlSerieDiariaVendedor(
                    $anno,
                    $mes,
                    $codigoMejorVendedor
                );
            }
        }

        $porDia = [];
        foreach ($filasSerie as $fila) {
            $porDia[(int) $fila["dia"]] = $fila;
        }

        $porDiaVendedor = [];
        foreach ($filasVendedor as $fila) {
            $porDiaVendedor[(int) $fila["dia"]] = (float) $fila["monto"];
        }

        $labels = [];
        $montos = [];
        $operaciones = [];
        $devoluciones = [];
        $promedioAcum = [];
        $vendedorTop = [];
        $acumulado = 0;
        $diasConDato = 0;

        for ($dia = 1; $dia <= $ultimoDia; $dia++) {
            $labels[] = (string) $dia;
            $montoDia = isset($porDia[$dia]) ? (float) $porDia[$dia]["monto"] : 0;
            $opsDia = isset($porDia[$dia]) ? (int) $porDia[$dia]["operaciones"] : 0;
            $devDia = isset($porDia[$dia]) ? (float) $porDia[$dia]["dev_descuentos"] : 0;

            $montos[] = $montoDia;
            $operaciones[] = $opsDia;
            $devoluciones[] = $devDia;
            $vendedorTop[] = isset($porDiaVendedor[$dia]) ? $porDiaVendedor[$dia] : 0;

            if ($montoDia != 0) {
                $diasConDato++;
                $acumulado += $montoDia;
            }

            $promedioAcum[] = $diasConDato > 0 ? round($acumulado / $diasConDato, 2) : 0;
        }

        $totalMes = array_sum($montos);
        $diasConMovimiento = 0;
        foreach ($montos as $montoDia) {
            if ($montoDia > 0) {
                $diasConMovimiento++;
            }
        }
        $promedioLinea = $diasConMovimiento > 0
            ? round($totalMes / $diasConMovimiento, 2)
            : 0;

        return array(
            "labels" => $labels,
            "cobranza_total" => $montos,
            "promedio_diario" => $promedioAcum,
            "promedio_diario_linea" => $promedioLinea,
            "mejor_dia" => $montos,
            "operaciones" => $operaciones,
            "mejor_vendedor" => $vendedorTop,
            "dev_descuentos" => $devoluciones,
        );
    }

    static public function ctrKpisSuperiores($anno, $mes, $vendedor = "")
    {
        $vendedor = trim((string) $vendedor);
        $ant = self::periodoAnterior($anno, $mes);

        $comparativo = ModeloDashboardCobranzas::mdlComparativoDosPeriodos(
            $anno,
            $mes,
            $ant["anno"],
            $ant["mes"],
            $vendedor
        );

        $mejorVendedor = ModeloDashboardCobranzas::mdlMejorVendedor($anno, $mes, $vendedor);
        $mejorDia = ModeloDashboardCobranzas::mdlMejorDia($anno, $mes, $vendedor);

        $total = (float) $comparativo["cobranza_total"];
        $totalAnt = (float) $comparativo["cobranza_total_ant"];
        $promedio = self::promedioDiario($total, $comparativo["dias_con_movimiento"]);
        $promedioAnt = self::promedioDiario($totalAnt, $comparativo["dias_con_movimiento_ant"]);
        $montoVendedor = (float) $mejorVendedor["monto"];
        $pctVendedor = $total > 0 ? ($montoVendedor / $total) * 100 : 0;

        return [
            "cobranza_total" => $total,
            "cobranza_total_var" => self::variacionPorcentaje($total, $totalAnt),
            "promedio_diario" => $promedio,
            "promedio_diario_var" => self::variacionPorcentaje($promedio, $promedioAnt),
            "operaciones" => (int) $comparativo["operaciones"],
            "operaciones_var" => self::variacionPorcentaje(
                $comparativo["operaciones"],
                $comparativo["operaciones_ant"]
            ),
            "dev_descuentos" => (float) $comparativo["dev_descuentos"],
            "dev_descuentos_var" => self::variacionPorcentaje(
                $comparativo["dev_descuentos"],
                $comparativo["dev_descuentos_ant"]
            ),
            "mejor_dia" => !empty($mejorDia["dia"]) ? (int) $mejorDia["dia"] : null,
            "mejor_dia_monto" => (float) $mejorDia["monto"],
            "mejor_vendedor_codigo" => $mejorVendedor["vendedor"],
            "mejor_vendedor_nombre" => $mejorVendedor["nombre_vendedor"],
            "mejor_vendedor_monto" => $montoVendedor,
            "mejor_vendedor_pct" => $pctVendedor,
            "mes_anterior" => $ant["mes"],
            "anno_anterior" => $ant["anno"],
        ];
    }

    static public function ctrSparklines($anno, $mes, $vendedor = "", $codigoMejorVendedor = "")
    {
        return self::construirSparklines(
            (int) $anno,
            (int) $mes,
            trim((string) $vendedor),
            trim((string) $codigoMejorVendedor)
        );
    }

    static public function ctrDatosGraficos($anno, $mes, $vendedor = "", $codigoMejorVendedor = "")
    {
        $anno = (int) $anno;
        $mes = (int) $mes;
        $vendedor = trim((string) $vendedor);

        return array(
            "sparklines" => self::ctrSparklines($anno, $mes, $vendedor, $codigoMejorVendedor),
            "cobranza_semana" => ModeloDashboardCobranzas::mdlCobranzaPromedioSemana(
                $anno,
                $mes,
                $vendedor
            ),
        );
    }

    static public function ctrVendedoresFiltro($anno)
    {
        return ModeloDashboardCobranzas::mdlVendedoresConCobranza($anno);
    }
}
