<?php

class ControladorDashboardStockCobertura
{
    const DIAS_VENTANA = 30;

    private static $marcas = array(
        "JACKYFORM" => "Jackyform + Guapitas",
        "ROSALINDA" => "Rosalinda",
    );

    private static $colores = array(
        "JACKYFORM" => array("hex" => "#3c8dbc", "border" => "rgba(60,141,188,1)", "bg" => "rgba(60,141,188,0.18)"),
        "ROSALINDA" => array("hex" => "#d81b60", "border" => "rgba(216,27,96,1)", "bg" => "rgba(216,27,96,0.18)"),
    );

    public static function ctrDatos()
    {
        $hasta = date("Y-m-d");
        $desde = date("Y-m-d", strtotime("-" . (self::DIAS_VENTANA - 1) . " days"));
        $filas = ModeloDashboardStockCobertura::mdlArticulosConVenta($desde, $hasta);

        $base = array();
        foreach (array_keys(self::$marcas) as $clave) {
            $base[$clave] = self::ctrMarcaVacia();
        }

        foreach ($filas as $fila) {
            $marca = strtoupper(trim($fila["marca"]));
            if (!isset($base[$marca])) {
                continue;
            }
            $stock = (int) round((float) $fila["stock"]);
            $proceso = (int) round((float) $fila["proceso"]);
            $comprometido = (int) round((float) $fila["comprometido"]);
            $ventas = (int) round((float) $fila["ventas"]);
            if ($ventas < 0) {
                $ventas = 0;
            }
            if ($ventas <= 0) {
                continue;
            }

            $disponible = $stock + $proceso - $comprometido;
            $base[$marca]["articulos"]++;
            $base[$marca]["almacen"] += $stock;
            $base[$marca]["proceso"] += $proceso;
            $base[$marca]["comprometido"] += $comprometido;
            $base[$marca]["facturado"] += $ventas;
            $base[$marca]["ritmos"][] = array(
                "disponible" => $disponible,
                "ritmo" => $ventas / self::DIAS_VENTANA,
            );
        }

        $eje = array("Hoy");
        for ($dia = 1; $dia <= self::DIAS_VENTANA; $dia++) {
            $eje[] = "+" . $dia;
        }

        $series = array();
        foreach (self::$marcas as $clave => $label) {
            $marca = $base[$clave];
            $ok = array();
            $peligro = array();
            for ($dia = 0; $dia <= self::DIAS_VENTANA; $dia++) {
                $nOk = 0;
                $nPeligro = 0;
                foreach ($marca["ritmos"] as $art) {
                    $saldo = $art["disponible"] - ($art["ritmo"] * $dia);
                    if ($saldo > 0) {
                        $nOk++;
                    } else {
                        $nPeligro++;
                    }
                }
                $ok[] = $nOk;
                $peligro[] = $nPeligro;
            }

            $peligroHoy = $peligro[0];
            $peligro30 = $peligro[self::DIAS_VENTANA];
            $alcanzan30 = $ok[self::DIAS_VENTANA];
            $total = $marca["articulos"];
            $pct = $total > 0 ? round($peligro30 * 100 / $total) : 0;
            $color = self::$colores[$clave];
            $series[] = array(
                "clave" => $clave,
                "label" => $label,
                "hex" => $color["hex"],
                "border" => $color["border"],
                "bg" => $color["bg"],
                "almacen" => $marca["almacen"],
                "proceso" => $marca["proceso"],
                "comprometido" => $marca["comprometido"],
                "disponible" => $marca["almacen"] + $marca["proceso"] - $marca["comprometido"],
                "facturado" => $marca["facturado"],
                "articulos" => $total,
                "alcanzan_30" => $alcanzan30,
                "peligro_hoy" => $peligroHoy,
                "peligro_30" => $peligro30,
                "pct_peligro" => $pct,
                "dataOk" => $ok,
                "dataPeligro" => $peligro,
                "alerta" => self::ctrAlerta($pct, $total),
            );
        }

        return array(
            "desde" => $desde,
            "hasta" => $hasta,
            "dias" => self::DIAS_VENTANA,
            "eje" => $eje,
            "series" => $series,
        );
    }

    public static function ctrFmt($numero)
    {
        return number_format((float) $numero, 0, ".", ",");
    }

    public static function ctrFmtFechaCorta($fecha)
    {
        if ($fecha === "" || !preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fecha, $p)) {
            return $fecha;
        }
        return (int) $p[3] . "/" . (int) $p[2];
    }

    private static function ctrMarcaVacia()
    {
        return array(
            "articulos" => 0,
            "almacen" => 0,
            "proceso" => 0,
            "comprometido" => 0,
            "facturado" => 0,
            "ritmos" => array(),
        );
    }

    private static function ctrAlerta($pct, $total)
    {
        if ($total <= 0) {
            return "ok";
        }
        if ($pct >= 40) {
            return "danger";
        }
        if ($pct >= 20) {
            return "warn";
        }
        return "ok";
    }
}
