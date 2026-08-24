<?php

require_once dirname(__FILE__) . "/dashboard-gerencial.config.php";
require_once dirname(__FILE__) . "/dashboard-cxc.config.php";

class ControladorInformeSemanalVendedor
{
    public static function ctrParseFiltros($entrada)
    {
        $vendedor = isset($entrada["vendedor"]) ? trim((string) $entrada["vendedor"]) : "";
        $semanaRaw = isset($entrada["semana"]) ? trim((string) $entrada["semana"]) : "";

        $lunes = self::lunesDesdeSemanaIso($semanaRaw);
        if ($lunes === null) {
            $ref = new DateTime("yesterday");
            $lunes = self::lunesDeFecha($ref);
        }

        $domingo = clone $lunes;
        $domingo->modify("+6 days");
        $sabado = clone $lunes;
        $sabado->modify("+5 days");

        $lunesAnt = clone $lunes;
        $lunesAnt->modify("-7 days");
        $domingoAnt = clone $lunesAnt;
        $domingoAnt->modify("+6 days");

        return array(
            "vendedor" => $vendedor,
            "lunes" => $lunes->format("Y-m-d"),
            "sabado" => $sabado->format("Y-m-d"),
            "domingo" => $domingo->format("Y-m-d"),
            "lunes_ant" => $lunesAnt->format("Y-m-d"),
            "domingo_ant" => $domingoAnt->format("Y-m-d"),
            "semana_iso" => $lunes->format("o") . "-W" . $lunes->format("W"),
        );
    }

    /**
     * @return DateTime|null
     */
    private static function lunesDesdeSemanaIso($semana)
    {
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $semana, $m)) {
            return null;
        }
        $dto = new DateTime();
        $dto->setISODate((int) $m[1], (int) $m[2]);
        $dto->setTime(0, 0, 0);
        return $dto;
    }

    /**
     * @return DateTime
     */
    private static function lunesDeFecha(DateTime $fecha)
    {
        $d = clone $fecha;
        $d->setTime(0, 0, 0);
        $n = (int) $d->format("N");
        if ($n > 1) {
            $d->modify("-" . ($n - 1) . " days");
        }
        return $d;
    }

    public static function ctrVendedoresFiltro()
    {
        return ModeloInformeSemanalVendedor::mdlVendedoresFiltro();
    }

    public static function ctrInforme(array $filtros)
    {
        $vendedor = $filtros["vendedor"];
        if ($vendedor === "") {
            return array("ok" => false, "msg" => "Elija un vendedor.");
        }

        $permitidos = array();
        foreach (self::ctrVendedoresFiltro() as $v) {
            $permitidos[trim((string) $v["codigo"])] = $v;
        }
        if (!isset($permitidos[$vendedor])) {
            return array("ok" => false, "msg" => "Vendedor no válido para el informe.");
        }

        $datos = ModeloInformeSemanalVendedor::mdlInforme($filtros);
        $datos["vendedor"] = array(
            "codigo" => $vendedor,
            "nombre" => $permitidos[$vendedor]["descripcion"],
            "zona" => ModeloInformeSemanalVendedor::mdlZonaVendedor($vendedor),
        );
        $datos["periodo"] = array(
            "semana_iso" => $filtros["semana_iso"],
            "desde" => $filtros["lunes"],
            "hasta_chart" => $filtros["sabado"],
            "hasta" => $filtros["domingo"],
            "etiqueta" => self::etiquetaRango($filtros["lunes"], $filtros["sabado"]),
            "fecha_emision" => date("d/m/Y"),
        );
        $datos["comparativo"] = self::armarComparativo($datos);
        $datos["lectura"] = self::armarLectura($datos);
        $datos["plan"] = self::armarPlan($datos);
        $datos["observaciones"] = self::armarObservaciones($datos);

        return array("ok" => true, "informe" => $datos);
    }

    private static function etiquetaRango($desde, $hasta)
    {
        $meses = array(1 => "enero", 2 => "febrero", 3 => "marzo", 4 => "abril", 5 => "mayo", 6 => "junio", 7 => "julio", 8 => "agosto", 9 => "septiembre", 10 => "octubre", 11 => "noviembre", 12 => "diciembre");
        $d = new DateTime($desde);
        $h = new DateTime($hasta);
        $md = $meses[(int) $d->format("n")];
        $mh = $meses[(int) $h->format("n")];
        if ($d->format("Y-m") === $h->format("Y-m")) {
            return (int) $d->format("j") . " al " . (int) $h->format("j") . " de " . $md . " de " . $d->format("Y");
        }
        return (int) $d->format("j") . " de " . $md . " al " . (int) $h->format("j") . " de " . $mh . " de " . $h->format("Y");
    }

    private static function variacion($actual, $anterior)
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;
        if (abs($anterior) < 0.0001) {
            return $actual > 0 ? 100.0 : 0.0;
        }
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    private static function armarComparativo(array $datos)
    {
        $k = $datos["kpis"];
        $a = $datos["kpis_ant"];
        $filas = array(
            array("indicador" => "Ventas", "actual" => $k["venta"], "anterior" => $a["venta"], "tipo" => "moneda"),
            array("indicador" => "Pedidos", "actual" => $k["pedidos"], "anterior" => $a["pedidos"], "tipo" => "entero"),
            array("indicador" => "Clientes con compra", "actual" => $k["clientes_compra"], "anterior" => $a["clientes_compra"], "tipo" => "entero"),
            array("indicador" => "Nuevos clientes", "actual" => $k["nuevos"], "anterior" => $a["nuevos"], "tipo" => "entero"),
            array("indicador" => "Cobranza realizada", "actual" => $k["cobranza"], "anterior" => $a["cobranza"], "tipo" => "moneda"),
        );
        foreach ($filas as &$f) {
            $f["variacion"] = self::variacion($f["actual"], $f["anterior"]);
        }
        unset($f);
        return $filas;
    }

    private static function armarLectura(array $datos)
    {
        $k = $datos["kpis"];
        $items = array();
        $varProm = $k["variacion_promedio"];
        if ($varProm >= 0) {
            $items[] = "Las ventas superaron el promedio de las últimas 4 semanas en " . number_format(abs($varProm), 1) . "%.";
        } else {
            $items[] = "Las ventas quedaron " . number_format(abs($varProm), 1) . "% por debajo del promedio de las últimas 4 semanas.";
        }

        $items[] = $k["clientes_compra"] . " de " . $k["clientes_cartera"] . " clientes de cartera compraron en la semana.";

        $pf = isset($datos["por_facturar"]) ? $datos["por_facturar"] : array();
        $nPf = isset($pf["total_pedidos"]) ? (int) $pf["total_pedidos"] : 0;
        if ($nPf > 0) {
            $items[] = "Tiene " . $nPf . " pedido(s) aprobado(s) por facturar (S/ "
                . number_format((float) $pf["total_soles"], 0, ".", ",")
                . " op. gravada). De esta semana: " . (int) $pf["semana_pedidos"] . ".";
        } else {
            $items[] = "No tiene pedidos aprobados pendientes de facturar.";
        }

        if ((int) $k["nuevos"] > 0) {
            $items[] = "Se incorporaron " . (int) $k["nuevos"] . " cliente(s) nuevo(s) (primera compra).";
        } else {
            $items[] = "No hubo clientes con primera compra en la semana.";
        }

        $varCob = self::variacion($k["cobranza"], $datos["kpis_ant"]["cobranza"]);
        if ($varCob >= 0) {
            $items[] = "La cobranza mejoró " . number_format($varCob, 1) . "% respecto de la semana anterior.";
        } else {
            $items[] = "La cobranza bajó " . number_format(abs($varCob), 1) . "% respecto de la semana anterior.";
        }

        $car = $datos["cartera"];
        $pct30 = (float) $car["pct_0_30"];
        if ($pct30 >= 25) {
            $items[] = "El tramo vencido 0–30 días representa " . number_format($pct30, 1) . "% de la cartera: conviene priorizar esa cobranza.";
        } else {
            $items[] = "La cartera por vencer es el tramo más grande (" . number_format((float) $car["pct_por_vencer"], 1) . "%).";
        }

        return array_slice($items, 0, 6);
    }

    private static function armarPlan(array $datos)
    {
        $k = $datos["kpis"];
        $car = $datos["cartera"];
        $plan = array();

        if ((float) $car["monto_0_30"] > 0) {
            $plan[] = "Visitar y cobrar clientes con vencido 0–30 días (S/ " . number_format((float) $car["monto_0_30"], 0, ".", ",") . ").";
        }
        if ((float) $car["monto_mas_30"] > 0) {
            $plan[] = "Plan de recupero para deuda de más de 30 días (S/ " . number_format((float) $car["monto_mas_30"], 0, ".", ",") . ").";
        }
        $pf = isset($datos["por_facturar"]) ? $datos["por_facturar"] : array();
        if (!empty($pf["total_pedidos"])) {
            $plan[] = "Avanzar a facturación los " . (int) $pf["total_pedidos"]
                . " pedidos en Aprobado / APT / Confirmado (S/ "
                . number_format((float) $pf["total_soles"], 0, ".", ",") . ").";
        }
        if ((int) $k["nuevos"] < 2) {
            $plan[] = "Agendar visitas a prospectos para incorporar al menos 2 clientes nuevos.";
        } else {
            $plan[] = "Dar seguimiento a los clientes nuevos de esta semana para una segunda compra.";
        }
        $plan[] = "Cubrir clientes de cartera que no compraron esta semana (" . max(0, (int) $k["clientes_cartera"] - (int) $k["clientes_compra"]) . " sin compra).";

        return array_slice($plan, 0, 4);
    }

    private static function armarObservaciones(array $datos)
    {
        $k = $datos["kpis"];
        $var = $k["variacion_promedio"];
        $tono = $var >= 0 ? "por encima" : "por debajo";
        return "Semana "
            . ($var >= 0 ? "sólida" : "ajustada")
            . ": la venta quedó " . $tono . " del promedio reciente. "
            . "Cobranza S/ " . number_format((float) $k["cobranza"], 0, ".", ",")
            . " y cartera total S/ " . number_format((float) $datos["cartera"]["total"], 0, ".", ",")
            . ". Pedidos por facturar: " . (int) $datos["por_facturar"]["total_pedidos"]
            . " (S/ " . number_format((float) $datos["por_facturar"]["total_soles"], 0, ".", ",") . ")."
            . " El plan debe equilibrar venta nueva, facturación pendiente y recupero de vencido corto.";
    }
}
