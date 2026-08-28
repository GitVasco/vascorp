<?php

class ControladorDashboardFlujoCorte
{
    private static $meses = array(
        1 => "Enero",
        2 => "Febrero",
        3 => "Marzo",
        4 => "Abril",
        5 => "Mayo",
        6 => "Junio",
        7 => "Julio",
        8 => "Agosto",
        9 => "Septiembre",
        10 => "Octubre",
        11 => "Noviembre",
        12 => "Diciembre",
    );

    public static function ctrDatos($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) date("Y");
        }
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date("n");
        }

        $hoy = date("Y-m-d");
        $desde = sprintf("%04d-%02d-01", $anio, $mes);
        $ultimoDiaMes = (int) date("t", strtotime($desde));
        $hastaMes = sprintf("%04d-%02d-%02d", $anio, $mes, $ultimoDiaMes);

        $esMesActual = ($anio === (int) date("Y") && $mes === (int) date("n"));
        $hastaSerie = $esMesActual ? $hoy : $hastaMes;
        if ($hastaSerie > $hoy) {
            $hastaSerie = $hoy;
        }
        if ($hastaSerie < $desde) {
            $hastaSerie = $desde;
        }

        $stocks = ModeloDashboardFlujoCorte::mdlStocksActuales();
        $ocResumen = ModeloDashboardFlujoCorte::mdlResumenOcAbiertas();
        $movimiento = ModeloDashboardFlujoCorte::mdlMovimientoPeriodo($desde, $hastaSerie);
        $movHastaHoy = ModeloDashboardFlujoCorte::mdlMovimientoPeriodo($desde, $hoy);

        $prev = self::ctrPeriodoAnterior($anio, $mes);
        $movAnterior = ModeloDashboardFlujoCorte::mdlMovimientoPeriodo($prev["desde"], $prev["hasta"]);

        $porDia = ModeloDashboardFlujoCorte::mdlDiarioPeriodo($desde, $hastaSerie);
        $diario = self::ctrArmarSeriesDiarias(
            $desde,
            $hastaSerie,
            $porDia,
            $stocks,
            $movHastaHoy
        );

        $enviadoTotal = $movimiento["enviado_taller"] + $movimiento["enviado_servicio"];
        $enviadoAnt = $movAnterior["enviado_taller"] + $movAnterior["enviado_servicio"];
        $diasSerie = max(1, self::ctrDiasEntre($desde, $hastaSerie));

        $ritmoCorte = $movimiento["cortado"] / $diasSerie;
        $ritmoEnvio = $enviadoTotal / $diasSerie;
        $enOc = (float) $stocks["en_oc"];
        $enCorte = (float) $stocks["en_corte"];

        $coberturaOc = ($ritmoCorte > 0) ? round($enOc / $ritmoCorte, 1) : null;
        $coberturaCorte = ($ritmoEnvio > 0) ? round($enCorte / $ritmoEnvio, 1) : null;

        return array(
            "periodo" => self::$meses[$mes] . " " . $anio,
            "anio" => $anio,
            "mes" => $mes,
            "es_mes_actual" => $esMesActual,
            "stocks" => array(
                "oc" => (float) $stocks["en_oc"],
                "corte" => (float) $stocks["en_corte"],
                "taller" => (float) $stocks["en_taller"],
                "servicio" => (float) $stocks["en_servicio"],
            ),
            "oc_resumen" => array(
                "ordenes" => (int) $ocResumen["ordenes"],
                "pendientes" => (int) $ocResumen["pendientes"],
                "parciales" => (int) $ocResumen["parciales"],
                "saldo" => (float) $ocResumen["saldo_unidades"],
            ),
            "movimiento" => array(
                "programado" => $movimiento["programado"],
                "cortado" => $movimiento["cortado"],
                "enviado_taller" => $movimiento["enviado_taller"],
                "enviado_servicio" => $movimiento["enviado_servicio"],
                "enviado_total" => $enviadoTotal,
                "programado_ant" => $movAnterior["programado"],
                "cortado_ant" => $movAnterior["cortado"],
                "enviado_ant" => $enviadoAnt,
                "periodo_anterior" => $prev["etiqueta"],
            ),
            "cobertura" => array(
                "dias_oc" => $coberturaOc,
                "dias_corte" => $coberturaCorte,
                "ritmo_corte" => round($ritmoCorte, 0),
                "ritmo_envio" => round($ritmoEnvio, 0),
            ),
            "alerta" => self::ctrArmarAlerta($movimiento, $enviadoTotal, $coberturaOc),
            "diario" => $diario,
        );
    }

    private static function ctrPeriodoAnterior($anio, $mes)
    {
        $mesAnt = $mes - 1;
        $anioAnt = $anio;
        if ($mesAnt < 1) {
            $mesAnt = 12;
            $anioAnt--;
        }
        $desde = sprintf("%04d-%02d-01", $anioAnt, $mesAnt);
        $ultimo = (int) date("t", strtotime($desde));
        $hasta = sprintf("%04d-%02d-%02d", $anioAnt, $mesAnt, $ultimo);

        return array(
            "desde" => $desde,
            "hasta" => $hasta,
            "etiqueta" => self::$meses[$mesAnt] . " " . $anioAnt,
        );
    }

    private static function ctrDiasEntre($desde, $hasta)
    {
        $a = new DateTime($desde);
        $b = new DateTime($hasta);
        return $a->diff($b)->days + 1;
    }

    private static function ctrArmarSeriesDiarias($desde, $hasta, $porDia, $stocks, $movHastaHoy)
    {
        $inicioOc = (float) $stocks["en_oc"] - $movHastaHoy["programado"] + $movHastaHoy["cortado"];
        $inicioCorte = (float) $stocks["en_corte"] - $movHastaHoy["cortado"]
            + $movHastaHoy["enviado_taller"] + $movHastaHoy["enviado_servicio"];
        $prodTallerHasta = isset($movHastaHoy["prod_taller"]) ? (float) $movHastaHoy["prod_taller"] : 0;
        $prodServHasta = isset($movHastaHoy["prod_servicio"]) ? (float) $movHastaHoy["prod_servicio"] : 0;
        $inicioTaller = (float) $stocks["en_taller"] - $movHastaHoy["enviado_taller"] + $prodTallerHasta;
        $inicioServicio = (float) $stocks["en_servicio"] - $movHastaHoy["enviado_servicio"] + $prodServHasta;

        $saldoOc = $inicioOc;
        $saldoCorte = $inicioCorte;
        $saldoTaller = $inicioTaller;
        $saldoServicio = $inicioServicio;

        $labels = array();
        $serieOc = array();
        $serieCorte = array();
        $serieTaller = array();
        $serieServicio = array();

        $cursor = new DateTime($desde);
        $fin = new DateTime($hasta);
        $vacio = array(
            "programado" => 0,
            "cortado" => 0,
            "enviado_taller" => 0,
            "enviado_servicio" => 0,
            "prod_taller" => 0,
            "prod_servicio" => 0,
        );

        while ($cursor <= $fin) {
            $dia = $cursor->format("Y-m-d");
            $fila = isset($porDia[$dia]) ? array_merge($vacio, $porDia[$dia]) : $vacio;

            $saldoOc += $fila["programado"] - $fila["cortado"];
            $saldoCorte += $fila["cortado"] - $fila["enviado_taller"] - $fila["enviado_servicio"];
            $saldoTaller += $fila["enviado_taller"] - $fila["prod_taller"];
            $saldoServicio += $fila["enviado_servicio"] - $fila["prod_servicio"];

            $labels[] = (int) $cursor->format("j");
            $serieOc[] = round($saldoOc, 0);
            $serieCorte[] = round($saldoCorte, 0);
            $serieTaller[] = round($saldoTaller, 0);
            $serieServicio[] = round($saldoServicio, 0);

            $cursor->modify("+1 day");
        }

        return array(
            "labels" => $labels,
            "saldo_oc" => $serieOc,
            "saldo_corte" => $serieCorte,
            "saldo_taller" => $serieTaller,
            "saldo_servicio" => $serieServicio,
        );
    }

    private static function ctrArmarAlerta($movimiento, $enviadoTotal, $coberturaOc)
    {
        $programado = $movimiento["programado"];
        $cortado = $movimiento["cortado"];

        if ($cortado > 0 && $programado < ($cortado * 0.5)) {
            $texto = "Se está cortando bastante más de lo que se programa. El saldo de órdenes de corte se está agotando.";
            if ($coberturaOc !== null) {
                $texto .= " Al ritmo actual alcanzaría para unos " . $coberturaOc . " días.";
            }
            return array("tipo" => "danger", "texto" => $texto);
        }

        if ($cortado > 0 && $programado < $cortado) {
            $texto = "El corte consume más unidades de las que se programan en el período. Conviene adelantar órdenes de corte.";
            if ($coberturaOc !== null) {
                $texto .= " Cobertura estimada: " . $coberturaOc . " días.";
            }
            return array("tipo" => "warning", "texto" => $texto);
        }

        if ($enviadoTotal > 0 && $cortado > 0 && $enviadoTotal < ($cortado * 0.5)) {
            return array(
                "tipo" => "warning",
                "texto" => "Se corta más de lo que se envía a taller o servicios. El almacén de corte se está acumulando.",
            );
        }

        return array("tipo" => "info", "texto" => "");
    }

    public static function ctrFmt($numero)
    {
        return number_format((float) $numero, 0, ".", ",");
    }

    public static function ctrDeltaPct($actual, $anterior)
    {
        if ($anterior == 0) {
            return null;
        }
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
}
