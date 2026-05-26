<?php

if (!isset($kpisCobranzas)) {
    return;
}

$k = $kpisCobranzas;

$mesesCortos = [
    1 => "Ene", 2 => "Feb", 3 => "Mar", 4 => "Abr", 5 => "May", 6 => "Jun",
    7 => "Jul", 8 => "Ago", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dic",
];

$labelMesAnt = isset($mesesCortos[$k["mes_anterior"]]) ? $mesesCortos[$k["mes_anterior"]] : "";
$textoMesAnt = $labelMesAnt . " " . $k["anno_anterior"];

if (!function_exists("dcFormatoVariacion")) {
    function dcFormatoVariacion($valor)
    {
        $signo = $valor > 0 ? "+" : "";
        return $signo . number_format($valor, 1) . "%";
    }

    function dcClaseVariacion($valor, $invertir = false)
    {
        $subio = ((float) $valor) > 0;
        $bajo = ((float) $valor) < 0;

        if ($invertir) {
            if ($subio) {
                return "dc-trend-malo";
            }
            if ($bajo) {
                return "dc-trend-bueno";
            }
            return "dc-trend-neutro";
        }

        if ($subio) {
            return "dc-trend-bueno";
        }
        if ($bajo) {
            return "dc-trend-malo";
        }

        return "dc-trend-neutro";
    }

    function dcIconoVariacion($valor)
    {
        if ($valor > 0) {
            return "fa-arrow-up";
        }
        if ($valor < 0) {
            return "fa-arrow-down";
        }
        return "fa-minus";
    }
}

$mejorDiaTexto = "—";
if (!empty($k["mejor_dia"])) {
    $nomMesCorto = isset($mesesCortos[$mesActual]) ? $mesesCortos[$mesActual] : "";
    $mejorDiaTexto = (int) $k["mejor_dia"] . " " . $nomMesCorto;
}

$cajas = [
    [
        "id" => "sparkCobranzaTotal",
        "clase" => "dc-kpi--green",
        "icono" => "fa-money",
        "label" => "Cobranza total",
        "valor" => "S/ " . number_format($k["cobranza_total"], 0),
        "trend" => true,
        "var" => $k["cobranza_total_var"],
        "invertir" => false,
        "spark_key" => "cobranza_total",
        "color" => "#3d9970",
    ],
    [
        "id" => "sparkPromedioDiario",
        "clase" => "dc-kpi--teal",
        "icono" => "fa-line-chart",
        "label" => "Promedio diario",
        "valor" => "S/ " . number_format($k["promedio_diario"], 0),
        "trend" => true,
        "var" => $k["promedio_diario_var"],
        "invertir" => false,
        "spark_key" => "promedio_diario",
        "color" => "#3aa99a",
    ],
    [
        "id" => "sparkMejorDia",
        "clase" => "dc-kpi--blue",
        "icono" => "fa-calendar-check-o",
        "label" => "Mejor día del mes",
        "valor" => $mejorDiaTexto,
        "sub" => "S/ " . number_format($k["mejor_dia_monto"], 0),
        "trend" => false,
        "spark_key" => "mejor_dia",
        "color" => "#4a90d9",
    ],
    [
        "id" => "sparkOperaciones",
        "clase" => "dc-kpi--indigo",
        "icono" => "fa-list-alt",
        "label" => "Operaciones",
        "valor" => number_format($k["operaciones"], 0),
        "trend" => true,
        "var" => $k["operaciones_var"],
        "invertir" => false,
        "spark_key" => "operaciones",
        "color" => "#6b7ad4",
    ],
    [
        "id" => "sparkMejorVendedor",
        "clase" => "dc-kpi--amber",
        "icono" => "fa-user-circle",
        "label" => "Mejor vendedor",
        "valor" => $k["mejor_vendedor_nombre"],
        "valor_small" => true,
        "sub" => "S/ " . number_format($k["mejor_vendedor_monto"], 0) . " · " . number_format($k["mejor_vendedor_pct"], 1) . "% del total",
        "trend" => false,
        "spark_key" => "mejor_vendedor",
        "color" => "#e0a030",
    ],
    [
        "id" => "sparkDevDescuentos",
        "clase" => "dc-kpi--rose",
        "icono" => "fa-reply",
        "label" => "Dev. y descuentos",
        "valor" => "S/ " . number_format($k["dev_descuentos"], 0),
        "trend" => true,
        "var" => $k["dev_descuentos_var"],
        "invertir" => true,
        "spark_key" => "dev_descuentos",
        "color" => "#d66b8a",
    ],
];

?>

<style>
    .dc-kpi-card {
        position: relative;
        border-radius: 10px;
        padding: 14px 14px 8px;
        margin-bottom: 15px;
        min-height: 148px;
        overflow: hidden;
        background: #fff;
        color: #334155;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-left-width: 4px;
    }

    .dc-kpi-card__icon {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .dc-kpi-card__label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 6px;
        padding-right: 48px;
    }

    .dc-kpi-card__value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 2px;
        padding-right: 4px;
        color: #1e293b;
    }

    .dc-kpi-card__value--sm {
        font-size: 15px;
        line-height: 1.25;
    }

    .dc-kpi-card__sub {
        font-size: 12px;
        color: #475569;
        margin-bottom: 2px;
    }

    .dc-kpi-card__trend {
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .dc-kpi-card__trend.dc-trend-bueno {
        color: #1e7e34;
    }

    .dc-kpi-card__trend.dc-trend-malo {
        color: #c0392b;
    }

    .dc-kpi-card__trend.dc-trend-neutro {
        color: #64748b;
    }

    .dc-kpi-card__chart {
        position: relative;
        height: 44px;
        margin: 4px -4px 0;
    }

    .dc-kpi-card__chart canvas {
        display: block;
        width: 100% !important;
        height: 44px !important;
    }

    .dc-kpi-card__chart-loading {
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #94a3b8;
    }

    .dc-kpi--green {
        background: #f3fbf6;
        border-left-color: #3d9970;
    }
    .dc-kpi--green .dc-kpi-card__icon {
        background: #e3f5eb;
        color: #2d8659;
    }

    .dc-kpi--teal {
        background: #f2faf9;
        border-left-color: #3aa99a;
    }
    .dc-kpi--teal .dc-kpi-card__icon {
        background: #dff5f1;
        color: #2a8f82;
    }

    .dc-kpi--blue {
        background: #f3f8fd;
        border-left-color: #4a90d9;
    }
    .dc-kpi--blue .dc-kpi-card__icon {
        background: #e3effa;
        color: #357abd;
    }

    .dc-kpi--indigo {
        background: #f5f6fc;
        border-left-color: #6b7ad4;
    }
    .dc-kpi--indigo .dc-kpi-card__icon {
        background: #e8ebf8;
        color: #5a6ac4;
    }

    .dc-kpi--amber {
        background: #fffcf5;
        border-left-color: #e0a030;
    }
    .dc-kpi--amber .dc-kpi-card__icon {
        background: #faf0dc;
        color: #c98a1f;
    }

    .dc-kpi--rose {
        background: #fdf5f7;
        border-left-color: #d66b8a;
    }
    .dc-kpi--rose .dc-kpi-card__icon {
        background: #fae8ee;
        color: #c44569;
    }
</style>

<div class="row">
    <?php foreach ($cajas as $caja) : ?>
        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
            <div class="dc-kpi-card <?php echo $caja["clase"]; ?>"
                data-spark-id="<?php echo $caja["id"]; ?>"
                data-spark-key="<?php echo $caja["spark_key"]; ?>"
                data-spark-color="<?php echo $caja["color"]; ?>">
                <div class="dc-kpi-card__icon">
                    <i class="fa <?php echo $caja["icono"]; ?>"></i>
                </div>
                <div class="dc-kpi-card__label"><?php echo $caja["label"]; ?></div>
                <div class="dc-kpi-card__value<?php echo !empty($caja["valor_small"]) ? " dc-kpi-card__value--sm" : ""; ?>">
                    <?php echo htmlspecialchars($caja["valor"]); ?>
                </div>
                <?php if (!empty($caja["sub"])) : ?>
                    <div class="dc-kpi-card__sub"><?php echo $caja["sub"]; ?></div>
                <?php endif; ?>
                <?php if (!empty($caja["trend"])) : ?>
                    <div class="dc-kpi-card__trend <?php echo dcClaseVariacion($caja["var"], $caja["invertir"]); ?>">
                        <i class="fa <?php echo dcIconoVariacion($caja["var"]); ?>"></i>
                        <?php echo dcFormatoVariacion($caja["var"]); ?> vs. <?php echo $textoMesAnt; ?>
                    </div>
                <?php endif; ?>
                <div class="dc-kpi-card__chart">
                    <div class="dc-kpi-card__chart-loading">Cargando…</div>
                    <canvas id="<?php echo $caja["id"]; ?>" height="44" style="display:none;"></canvas>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
