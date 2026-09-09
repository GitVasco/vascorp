<?php

class ControladorDashboardStockMensual
{
    private static $mesesCortos = array(
        1 => "Ene",
        2 => "Feb",
        3 => "Mar",
        4 => "Abr",
        5 => "May",
        6 => "Jun",
        7 => "Jul",
        8 => "Ago",
        9 => "Sep",
        10 => "Oct",
        11 => "Nov",
        12 => "Dic",
    );

    private static $marcas = array(
        "JACKYFORM" => "Jackyform + Guapitas",
        "ROSALINDA" => "Rosalinda",
    );

    private static $colores = array(
        "JACKYFORM" => array("hex" => "#3c8dbc", "border" => "rgba(60,141,188,1)", "bg" => "rgba(60,141,188,0.15)"),
        "ROSALINDA" => array("hex" => "#d81b60", "border" => "rgba(216,27,96,1)", "bg" => "rgba(216,27,96,0.15)"),
    );

    public static function ctrDatos($anio = null)
    {
        $anio = $anio === null ? (int) date("Y") : (int) $anio;
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) date("Y");
        }

        $filas = ModeloDashboardStockMensual::mdlStockFinalPorMes($anio);

        $stockPorMarca = array();
        foreach (array_keys(self::$marcas) as $clave) {
            $stockPorMarca[$clave] = array();
        }

        $fechasPorMes = array();
        $ultimoMesActual = 0;
        $ultimaFecha = "";

        foreach ($filas as $fila) {
            $mes = (int) $fila["mes"];
            $marca = strtoupper(trim($fila["marca"]));
            if (!isset(self::$mesesCortos[$mes]) || !isset(self::$marcas[$marca])) {
                continue;
            }
            $fechasPorMes[$mes] = $fila["dia"];
            $stockPorMarca[$marca][$mes] = (int) round((float) $fila["stock"]);
            if ($mes > $ultimoMesActual) {
                $ultimoMesActual = $mes;
                $ultimaFecha = $fila["dia"];
            }
        }

        $meses = array();
        $fechas = array();
        for ($mes = 1; $mes <= 12; $mes++) {
            $meses[] = self::$mesesCortos[$mes];
            $fechas[] = isset($fechasPorMes[$mes]) ? $fechasPorMes[$mes] : "";
        }

        $proyeccion = self::ctrArmarProyeccion($anio, $ultimaFecha, $ultimoMesActual, $stockPorMarca);

        $series = array();
        foreach (self::$marcas as $clave => $label) {
            $data = array();
            $dataProy = array();
            $dataCorte = array();
            for ($mes = 1; $mes <= 12; $mes++) {
                $tieneActual = isset($stockPorMarca[$clave][$mes]);
                $data[] = $tieneActual ? $stockPorMarca[$clave][$mes] : null;
                $dataProy[] = isset($proyeccion["series"][$clave][$mes])
                    ? $proyeccion["series"][$clave][$mes]
                    : null;
                $dataCorte[] = isset($proyeccion["factible"][$clave][$mes])
                    ? $proyeccion["factible"][$clave][$mes]
                    : null;
            }

            $actual = 0;
            if ($ultimoMesActual > 0 && isset($stockPorMarca[$clave][$ultimoMesActual])) {
                $actual = $stockPorMarca[$clave][$ultimoMesActual];
            }

            $proyectado = self::ctrUltimoPunto($dataProy);
            $factible = self::ctrUltimoPunto($dataCorte);
            $resumen = isset($proyeccion["resumen"][$clave]) ? $proyeccion["resumen"][$clave] : array(
                "proceso" => 0,
                "prod" => 0,
            );

            $color = self::$colores[$clave];
            $series[] = array(
                "clave" => $clave,
                "label" => $label,
                "hex" => $color["hex"],
                "border" => $color["border"],
                "bg" => $color["bg"],
                "actual" => $actual,
                "proyectado" => $proyectado,
                "factible" => $factible,
                "proceso" => $resumen["proceso"],
                "prodProy" => $resumen["prod"],
                "data" => $data,
                "dataProy" => $dataProy,
                "dataCorte" => $dataCorte,
            );
        }

        return array(
            "anio" => $anio,
            "meses" => $meses,
            "fechas" => $fechas,
            "series" => $series,
            "proyeccion" => array(
                "activa" => $proyeccion["activa"],
                "desde" => $proyeccion["desde"],
                "hasta" => $proyeccion["hasta"],
                "anio" => $proyeccion["anio"],
            ),
        );
    }

    private static function ctrArmarProyeccion($anio, $ultimaFecha, $ultimoMesActual, $stockPorMarca)
    {
        $vacio = array(
            "activa" => false,
            "desde" => "",
            "hasta" => "",
            "anio" => $anio - 1,
            "series" => array(),
            "factible" => array(),
            "resumen" => array(),
        );

        if ($ultimaFecha === "" || $ultimoMesActual < 1) {
            return $vacio;
        }

        $espejoDesde = new DateTime($ultimaFecha);
        $espejoDesde->modify("-1 year");
        $espejoDesde->modify("+1 day");
        $anioPasado = $anio - 1;
        $desde = $espejoDesde->format("Y-m-d");
        $hasta = sprintf("%04d-12-31", $anioPasado);

        if ($desde > $hasta) {
            return $vacio;
        }

        $filas = ModeloDashboardStockMensual::mdlMovimientosEspejo($anioPasado, $desde, $hasta);
        $mov = array();
        foreach (array_keys(self::$marcas) as $clave) {
            $mov[$clave] = array();
        }
        foreach ($filas as $fila) {
            $mes = (int) $fila["mes"];
            $marca = strtoupper(trim($fila["marca"]));
            if (!isset(self::$marcas[$marca]) || $mes < 1 || $mes > 12) {
                continue;
            }
            $mov[$marca][$mes] = array(
                "produccion" => (float) $fila["produccion"],
                "ventas" => (float) $fila["ventas"],
            );
        }

        $procesoPorMarca = ModeloDashboardStockMensual::mdlProcesoActualPorMarca();

        $series = array();
        $factible = array();
        $resumen = array();
        foreach (array_keys(self::$marcas) as $clave) {
            $stockProy = isset($stockPorMarca[$clave][$ultimoMesActual])
                ? (int) $stockPorMarca[$clave][$ultimoMesActual]
                : 0;
            $stockFact = $stockProy;
            $procesoRestante = isset($procesoPorMarca[$clave]) ? (int) $procesoPorMarca[$clave] : 0;
            $procesoInicial = $procesoRestante;
            $prodTotal = 0;
            $puntosProy = array();
            $puntosFact = array();
            for ($mes = $ultimoMesActual; $mes <= 12; $mes++) {
                $prod = isset($mov[$clave][$mes]) ? $mov[$clave][$mes]["produccion"] : 0;
                $ven = isset($mov[$clave][$mes]) ? $mov[$clave][$mes]["ventas"] : 0;
                $prodTotal += $prod;
                $stockProy = (int) round($stockProy + $prod - $ven);
                $puntosProy[$mes] = $stockProy;

                $prodProceso = min($prod, $procesoRestante);
                $procesoRestante -= $prodProceso;
                $stockFact = (int) round($stockFact + $prodProceso - $ven);
                $puntosFact[$mes] = $stockFact;
            }
            $series[$clave] = $puntosProy;
            $factible[$clave] = $puntosFact;
            $resumen[$clave] = array(
                "proceso" => $procesoInicial,
                "prod" => (int) round($prodTotal),
            );
        }

        return array(
            "activa" => true,
            "desde" => $desde,
            "hasta" => $hasta,
            "anio" => $anioPasado,
            "series" => $series,
            "factible" => $factible,
            "resumen" => $resumen,
        );
    }

    private static function ctrUltimoPunto($serie)
    {
        for ($i = count($serie) - 1; $i >= 0; $i--) {
            if ($serie[$i] !== null) {
                return $serie[$i];
            }
        }
        return null;
    }

    public static function ctrFmt($numero)
    {
        return number_format((float) $numero, 0, ".", ",");
    }
}
