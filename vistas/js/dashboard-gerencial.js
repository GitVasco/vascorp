(function () {
    "use strict";

    var charts = {
        ventasMensual: null,
        ventasVsAnio: null,
        ventasPeriodosMes: null,
        cobranzasMensual: null,
        cobranzasVsAnio: null,
        cobranzasPeriodosMes: null,
        pctMismoMesAnio: null,
        ventasVsRecup: null,
        agingCobranza: null,
        puntualidad: null,
        proyeccion: null
    };

    var PALETTE_ORIGEN = [
        "rgba(234, 88, 12, 0.8)",
        "rgba(37, 99, 235, 0.75)",
        "rgba(22, 163, 74, 0.75)",
        "rgba(79, 70, 229, 0.75)",
        "rgba(13, 148, 136, 0.75)",
        "rgba(100, 116, 139, 0.65)",
        "rgba(220, 38, 38, 0.65)",
        "rgba(202, 138, 4, 0.75)"
    ];

    var COLOR_VENTA = {
        bg: "rgba(22, 163, 74, 0.75)",
        border: "#15803d",
        bg2: "rgba(100, 116, 139, 0.55)",
        border2: "#475569"
    };

    var COLOR_COBRANZA = {
        bg: "rgba(37, 99, 235, 0.75)",
        border: "#1d4ed8",
        bg2: "rgba(100, 116, 139, 0.55)",
        border2: "#475569"
    };

    function elData() {
        return document.getElementById("dashboardGerencialData");
    }

    function leerEstado() {
        var nodo = elData();
        if (!nodo) {
            return {
                anio: "",
                mes: "",
                vendedor: "",
                modo: "vs_anio_ant",
                periodoADesde: "",
                periodoAHasta: "",
                periodoBDesde: "",
                periodoBHasta: ""
            };
        }

        return {
            anio: nodo.getAttribute("data-anio") || "",
            mes: nodo.getAttribute("data-mes") || "",
            vendedor: nodo.getAttribute("data-vendedor") || "",
            modo: nodo.getAttribute("data-modo") || "vs_anio_ant",
            periodoADesde: nodo.getAttribute("data-periodo-a-desde") || "",
            periodoAHasta: nodo.getAttribute("data-periodo-a-hasta") || "",
            periodoBDesde: nodo.getAttribute("data-periodo-b-desde") || "",
            periodoBHasta: nodo.getAttribute("data-periodo-b-hasta") || ""
        };
    }

    function escribirEstado(estado) {
        var nodo = elData();
        if (!nodo) {
            return;
        }

        nodo.setAttribute("data-anio", estado.anio || "");
        nodo.setAttribute("data-mes", estado.mes || "");
        nodo.setAttribute("data-vendedor", estado.vendedor || "");
        nodo.setAttribute("data-modo", estado.modo || "vs_anio_ant");
        nodo.setAttribute("data-periodo-a-desde", estado.periodoADesde || "");
        nodo.setAttribute("data-periodo-a-hasta", estado.periodoAHasta || "");
        nodo.setAttribute("data-periodo-b-desde", estado.periodoBDesde || "");
        nodo.setAttribute("data-periodo-b-hasta", estado.periodoBHasta || "");
    }

    function estadoDesdeFormulario() {
        return {
            anio: $("#anioDg").val() || "",
            mes: $("#mesDg").val() || "",
            vendedor: $("#vendedorDg").val() || "",
            modo: $("#modoDg").val() || "vs_anio_ant",
            periodoADesde: $("#periodoADesdeDg").val() || "",
            periodoAHasta: $("#periodoAHastaDg").val() || "",
            periodoBDesde: $("#periodoBDesdeDg").val() || "",
            periodoBHasta: $("#periodoBHastaDg").val() || ""
        };
    }

    function construirUrl(estado) {
        var params = [
            "ruta=dashboard-gerencial",
            "año=" + encodeURIComponent(estado.anio),
            "mes=" + encodeURIComponent(estado.mes)
        ];

        if (estado.vendedor) {
            params.push("vendedor=" + encodeURIComponent(estado.vendedor));
        }
        if (estado.modo && estado.modo !== "vs_anio_ant") {
            params.push("modo=" + encodeURIComponent(estado.modo));
        }
        if (estado.modo === "periodos") {
            if (estado.periodoADesde) {
                params.push("periodo_a_desde=" + encodeURIComponent(estado.periodoADesde));
            }
            if (estado.periodoAHasta) {
                params.push("periodo_a_hasta=" + encodeURIComponent(estado.periodoAHasta));
            }
            if (estado.periodoBDesde) {
                params.push("periodo_b_desde=" + encodeURIComponent(estado.periodoBDesde));
            }
            if (estado.periodoBHasta) {
                params.push("periodo_b_hasta=" + encodeURIComponent(estado.periodoBHasta));
            }
        }

        return "index.php?" + params.join("&");
    }

    function irConEstado(estado) {
        escribirEstado(estado);
        window.location.href = construirUrl(estado);
    }

    function paramsAjax(estado, extra) {
        var base = {
            anio: estado.anio,
            mes: estado.mes,
            vendedor: estado.vendedor,
            modo: estado.modo,
            periodo_a_desde: estado.periodoADesde,
            periodo_a_hasta: estado.periodoAHasta,
            periodo_b_desde: estado.periodoBDesde,
            periodo_b_hasta: estado.periodoBHasta
        };

        if (extra) {
            for (var k in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, k)) {
                    base[k] = extra[k];
                }
            }
        }

        return base;
    }

    function togglePeriodos() {
        var modo = $("#modoDg").val();
        var box = $("#dgPeriodosBox");
        if (modo === "periodos") {
            box.removeClass("is-hidden");
        } else {
            box.addClass("is-hidden");
        }
    }

    function formatoMonto(valor) {
        if (valor === null || valor === undefined) {
            return "—";
        }
        var n = Number(valor);
        if (isNaN(n)) {
            return "—";
        }
        return "S/ " + n.toLocaleString("es-PE", { maximumFractionDigits: 0 });
    }

    function formatoVar(valor) {
        if (valor === null || valor === undefined) {
            return "—";
        }
        var n = Number(valor);
        if (isNaN(n)) {
            return "—";
        }
        var signo = n > 0 ? "+" : "";
        return signo + n.toFixed(1) + "%";
    }

    function claseVar(valor) {
        var n = Number(valor);
        if (isNaN(n) || n === 0) {
            return "dg-trend-neutro";
        }
        return n > 0 ? "dg-trend-bueno" : "dg-trend-malo";
    }

    function iconoVar(valor) {
        var n = Number(valor);
        if (isNaN(n) || n === 0) {
            return "fa-minus";
        }
        return n > 0 ? "fa-arrow-up" : "fa-arrow-down";
    }

    function pintarKpi(id, valor, variacion) {
        var card = document.getElementById(id);
        if (!card) {
            return;
        }
        var elValor = card.querySelector('[data-role="valor"]');
        var elTrend = card.querySelector('[data-role="trend"]');
        if (elValor) {
            elValor.textContent = formatoMonto(valor);
        }
        if (elTrend && variacion !== undefined) {
            elTrend.className = "dg-kpi-trend " + claseVar(variacion);
            elTrend.innerHTML = '<i class="fa ' + iconoVar(variacion) + '"></i><span>' + formatoVar(variacion) + "</span>";
        }
    }

    function destruirChart(chart) {
        if (chart && typeof chart.destroy === "function") {
            chart.destroy();
        }
    }

    function claseDelta(valor) {
        var n = Number(valor);
        if (isNaN(n) || n === 0) {
            return "dg-delta-neu";
        }
        return n > 0 ? "dg-delta-pos" : "dg-delta-neg";
    }

    function formatoDeltaAbs(valor) {
        var n = Number(valor);
        if (isNaN(n)) {
            return "—";
        }
        return (n > 0 ? "+" : "") + formatoMonto(n);
    }

    function setText(id, text, cls) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        el.textContent = text;
        if (cls !== undefined && cls !== null) {
            el.className = cls;
        }
    }

    function opcionesEjeY() {
        return {
            beginAtZero: true,
            ticks: {
                callback: function (value) {
                    return Number(value).toLocaleString("es-PE", { maximumFractionDigits: 0 });
                }
            }
        };
    }

    function toggleEmpty(el, hayDatos) {
        if (!el) {
            return;
        }
        if (hayDatos) {
            el.classList.add("is-hidden");
        } else {
            el.classList.remove("is-hidden");
        }
    }

    function renderSerieMensual(data, cfg) {
        if (!data) {
            return;
        }

        var canvas = document.getElementById(cfg.canvas);
        var empty = document.getElementById(cfg.empty);
        var tbody = document.querySelector("#" + cfg.tabla + " tbody");
        var totalEl = document.getElementById(cfg.total);
        var anioLabel = document.getElementById(cfg.anioLabel);
        var color = cfg.color;
        var vacioMsg = cfg.vacioMsg || "Sin datos";

        if (anioLabel) {
            anioLabel.textContent = data.anio ? String(data.anio) : "";
        }

        var filas = data.filas || [];
        var montos = data.montos || [];
        var labels = data.labels || [];
        var total = Number(data.total) || 0;
        var hayDatos = montos.some(function (m) { return Number(m) > 0; });

        if (tbody) {
            if (!filas.length) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-muted">' + vacioMsg + "</td></tr>";
            } else {
                tbody.innerHTML = filas.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label_corto || fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct || 0).toFixed(1) + "%</td>" +
                        "</tr>";
                }).join("");
            }
        }

        if (totalEl) {
            totalEl.textContent = formatoMonto(total);
        }

        toggleEmpty(empty, hayDatos);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        destruirChart(charts[cfg.chartKey]);
        charts[cfg.chartKey] = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: (cfg.serieLabel || "Serie") + " " + (data.anio || ""),
                    data: montos,
                    backgroundColor: color.bg,
                    borderColor: color.border,
                    borderWidth: 1,
                    borderRadius: 3,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return formatoMonto(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: { y: opcionesEjeY() }
            }
        });
    }

    function renderVsAnio(data, cfg) {
        if (!data) {
            return;
        }

        var canvas = document.getElementById(cfg.canvas);
        var empty = document.getElementById(cfg.empty);
        var tbody = document.querySelector("#" + cfg.tabla + " tbody");
        var labelEl = document.getElementById(cfg.label);
        var thN = document.getElementById(cfg.thN);
        var thN1 = document.getElementById(cfg.thN1);
        var color = cfg.color;
        var vacioMsg = cfg.vacioMsg || "Sin datos para comparar";

        if (labelEl) {
            labelEl.textContent = (data.anio || "") + " vs " + (data.anio_anterior || "");
        }
        if (thN) {
            thN.textContent = String(data.anio || "N");
        }
        if (thN1) {
            thN1.textContent = String(data.anio_anterior || "N-1");
        }

        var filas = data.filas || [];
        var montosN = data.montos_n || [];
        var montosN1 = data.montos_n1 || [];
        var labels = data.labels || [];
        var hayDatos = montosN.concat(montosN1).some(function (m) { return Number(m) > 0; });

        if (tbody) {
            if (!filas.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">' + vacioMsg + "</td></tr>";
            } else {
                tbody.innerHTML = filas.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label_corto || fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta_n) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta_n1) + "</td>" +
                        '<td class="text-right ' + claseDelta(fila.delta_abs) + '">' + formatoDeltaAbs(fila.delta_abs) + "</td>" +
                        '<td class="text-right ' + claseDelta(fila.delta_pct) + '">' + formatoVar(fila.delta_pct) + "</td>" +
                        "</tr>";
                }).join("");
            }
        }

        setText(cfg.totalN, formatoMonto(data.total_n), "text-right");
        setText(cfg.totalN1, formatoMonto(data.total_n1), "text-right");
        setText(cfg.totalDelta, formatoDeltaAbs(data.delta_abs), "text-right " + claseDelta(data.delta_abs));
        setText(cfg.totalDeltaPct, formatoVar(data.delta_pct), "text-right " + claseDelta(data.delta_pct));
        toggleEmpty(empty, hayDatos);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        destruirChart(charts[cfg.chartKey]);
        charts[cfg.chartKey] = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: String(data.anio || "N"),
                        data: montosN,
                        backgroundColor: color.bg,
                        borderColor: color.border,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 28
                    },
                    {
                        label: String(data.anio_anterior || "N-1"),
                        data: montosN1,
                        backgroundColor: color.bg2,
                        borderColor: color.border2,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 28
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: "top" },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return (ctx.dataset.label || "") + ": " + formatoMonto(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: { y: opcionesEjeY() }
            }
        });
    }

    function renderPeriodos(data, cfg) {
        if (!data) {
            return;
        }

        var periodoA = data.periodo_a || {};
        var periodoB = data.periodo_b || {};
        var filas = data.filas || [];
        var empty = document.getElementById(cfg.empty);
        var tbody = document.querySelector("#" + cfg.tabla + " tbody");
        var labelEl = document.getElementById(cfg.label);
        var color = cfg.color;
        var vacioMsg = cfg.vacioMsg || "Sin datos en los períodos";

        if (labelEl) {
            labelEl.textContent = (periodoA.label || "") + "  ·  " + (periodoB.label || "");
        }

        setText(cfg.rangoA, periodoA.label || "—");
        setText(cfg.totalA, formatoMonto(periodoA.total));
        setText(cfg.rangoB, periodoB.label || "—");
        setText(cfg.totalB, formatoMonto(periodoB.total));
        setText(cfg.delta, formatoDeltaAbs(data.delta_abs), "dg-resumen-valor " + claseDelta(data.delta_abs));
        setText(cfg.deltaPct, formatoVar(data.delta_pct), "dg-resumen-sub " + claseDelta(data.delta_pct));
        setText(cfg.footA, formatoMonto(periodoA.total), "text-right");
        setText(cfg.footB, formatoMonto(periodoB.total), "text-right");
        setText(cfg.footDelta, formatoDeltaAbs(data.delta_abs), "text-right " + claseDelta(data.delta_abs));
        setText(cfg.footDeltaPct, formatoVar(data.delta_pct), "text-right " + claseDelta(data.delta_pct));

        var hayDatos = (Number(periodoA.total) > 0) || (Number(periodoB.total) > 0);

        if (tbody) {
            if (!filas.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">' + vacioMsg + "</td></tr>";
            } else {
                tbody.innerHTML = filas.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta_a) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta_b) + "</td>" +
                        '<td class="text-right ' + claseDelta(fila.delta_abs) + '">' + formatoDeltaAbs(fila.delta_abs) + "</td>" +
                        '<td class="text-right ' + claseDelta(fila.delta_pct) + '">' + formatoVar(fila.delta_pct) + "</td>" +
                        "</tr>";
                }).join("");
            }
        }

        toggleEmpty(empty, hayDatos);

        if (typeof Chart === "undefined") {
            return;
        }

        var canvasMes = document.getElementById(cfg.canvasMes);
        if (!canvasMes) {
            return;
        }

        destruirChart(charts[cfg.chartMesKey]);
        charts[cfg.chartMesKey] = new Chart(canvasMes.getContext("2d"), {
            type: "bar",
            data: {
                labels: data.labels_mes || [],
                datasets: [
                    {
                        label: "Período A",
                        data: data.montos_a || [],
                        backgroundColor: color.bg,
                        borderColor: color.border,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 36
                    },
                    {
                        label: "Período B",
                        data: data.montos_b || [],
                        backgroundColor: color.bg2,
                        borderColor: color.border2,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 36
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: "top" },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return (ctx.dataset.label || "") + ": " + formatoMonto(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: { y: opcionesEjeY() }
            }
        });
    }

    function cargarBase(estado) {
        return $.ajax({
            url: "ajax/dashboard-gerencial.ajax.php",
            method: "GET",
            dataType: "json",
            data: paramsAjax(estado, { accion: "base" })
        }).done(function (resp) {
            if (!resp || !resp.ok || !resp.data) {
                return;
            }

            var k = resp.data.kpis;
            if (k) {
                pintarKpi("dgKpiVentaMes", k.venta_mes, k.venta_mes_var);
                pintarKpi("dgKpiVentaYtd", k.venta_ytd, k.venta_ytd_var);
                pintarKpi("dgKpiCobranzaMes", k.cobranza_mes, k.cobranza_mes_var);
                pintarKpi("dgKpiCobranzaYtd", k.cobranza_ytd, k.cobranza_ytd_var);
            }

            if (resp.data.ventas_mensual) {
                renderSerieMensual(resp.data.ventas_mensual, {
                    canvas: "dgGraficoVentasMensual",
                    empty: "dgVentasMensualEmpty",
                    tabla: "dgTablaVentasMensual",
                    total: "dgVentasMensualTotal",
                    anioLabel: "dgVentasMensualAnioLabel",
                    chartKey: "ventasMensual",
                    color: COLOR_VENTA,
                    serieLabel: "Venta",
                    vacioMsg: "Sin ventas en el período"
                });
            }

            if (resp.data.ventas_vs_anio) {
                renderVsAnio(resp.data.ventas_vs_anio, {
                    canvas: "dgGraficoVentasVsAnio",
                    empty: "dgVentasVsAnioEmpty",
                    tabla: "dgTablaVentasVsAnio",
                    label: "dgVentasVsAnioLabel",
                    thN: "dgThVentaN",
                    thN1: "dgThVentaN1",
                    totalN: "dgVentasVsTotalN",
                    totalN1: "dgVentasVsTotalN1",
                    totalDelta: "dgVentasVsTotalDelta",
                    totalDeltaPct: "dgVentasVsTotalDeltaPct",
                    chartKey: "ventasVsAnio",
                    color: COLOR_VENTA,
                    vacioMsg: "Sin ventas para comparar"
                });
            }

            if (resp.data.ventas_periodos) {
                renderPeriodos(resp.data.ventas_periodos, {
                    empty: "dgVentasPeriodosEmpty",
                    tabla: "dgTablaVentasPeriodos",
                    label: "dgVentasPeriodosLabel",
                    rangoA: "dgPeriodoARango",
                    totalA: "dgPeriodoATotal",
                    rangoB: "dgPeriodoBRango",
                    totalB: "dgPeriodoBTotal",
                    delta: "dgPeriodosDelta",
                    deltaPct: "dgPeriodosDeltaPct",
                    footA: "dgPeriodosFootA",
                    footB: "dgPeriodosFootB",
                    footDelta: "dgPeriodosFootDelta",
                    footDeltaPct: "dgPeriodosFootDeltaPct",
                    canvasMes: "dgGraficoVentasPeriodosMes",
                    chartMesKey: "ventasPeriodosMes",
                    color: COLOR_VENTA,
                    vacioMsg: "Sin ventas en los períodos"
                });
            }

            if (resp.data.cobranzas_mensual) {
                renderSerieMensual(resp.data.cobranzas_mensual, {
                    canvas: "dgGraficoCobranzasMensual",
                    empty: "dgCobranzasMensualEmpty",
                    tabla: "dgTablaCobranzasMensual",
                    total: "dgCobranzasMensualTotal",
                    anioLabel: "dgCobranzasMensualAnioLabel",
                    chartKey: "cobranzasMensual",
                    color: COLOR_COBRANZA,
                    serieLabel: "Cobranza",
                    vacioMsg: "Sin cobranzas en el período"
                });
            }

            if (resp.data.cobranzas_vs_anio) {
                renderVsAnio(resp.data.cobranzas_vs_anio, {
                    canvas: "dgGraficoCobranzasVsAnio",
                    empty: "dgCobranzasVsAnioEmpty",
                    tabla: "dgTablaCobranzasVsAnio",
                    label: "dgCobranzasVsAnioLabel",
                    thN: "dgThCobranzaN",
                    thN1: "dgThCobranzaN1",
                    totalN: "dgCobranzasVsTotalN",
                    totalN1: "dgCobranzasVsTotalN1",
                    totalDelta: "dgCobranzasVsTotalDelta",
                    totalDeltaPct: "dgCobranzasVsTotalDeltaPct",
                    chartKey: "cobranzasVsAnio",
                    color: COLOR_COBRANZA,
                    vacioMsg: "Sin cobranzas para comparar"
                });
            }

            if (resp.data.cobranzas_periodos) {
                renderPeriodos(resp.data.cobranzas_periodos, {
                    empty: "dgCobranzasPeriodosEmpty",
                    tabla: "dgTablaCobranzasPeriodos",
                    label: "dgCobranzasPeriodosLabel",
                    rangoA: "dgCobPeriodoARango",
                    totalA: "dgCobPeriodoATotal",
                    rangoB: "dgCobPeriodoBRango",
                    totalB: "dgCobPeriodoBTotal",
                    delta: "dgCobPeriodosDelta",
                    deltaPct: "dgCobPeriodosDeltaPct",
                    footA: "dgCobPeriodosFootA",
                    footB: "dgCobPeriodosFootB",
                    footDelta: "dgCobPeriodosFootDelta",
                    footDeltaPct: "dgCobPeriodosFootDeltaPct",
                    canvasMes: "dgGraficoCobranzasPeriodosMes",
                    chartMesKey: "cobranzasPeriodosMes",
                    color: COLOR_COBRANZA,
                    vacioMsg: "Sin cobranzas en los períodos"
                });
            }

            if (resp.data.origen_cobranza) {
                renderOrigenCobranza(resp.data.origen_cobranza);
            }

            if (resp.data.puntualidad_vencimientos) {
                renderPuntualidadVencimientos(resp.data.puntualidad_vencimientos);
            }
            if (resp.data.proyeccion_cobranzas) {
                renderProyeccionCobranzas(resp.data.proyeccion_cobranzas);
            }

            if (k && k.pct_recuperacion !== null && k.pct_recuperacion !== undefined) {
                var kpiRec = document.querySelector('#dgKpiRecuperacion [data-role="valor"]');
                if (kpiRec) {
                    kpiRec.textContent = Number(k.pct_recuperacion).toFixed(1) + "%";
                }
            }

            if (k && k.proyeccion_vs_real !== null && k.proyeccion_vs_real !== undefined) {
                var kpiProy = document.querySelector('#dgKpiProyeccion [data-role="valor"]');
                if (kpiProy) {
                    kpiProy.textContent = Number(k.proyeccion_vs_real).toFixed(1) + "%";
                }
            }
        });
    }

    function renderPuntualidadVencimientos(data) {
        if (!data) {
            return;
        }

        var periodo = data.periodo || {};
        var mensual = data.mensual || [];
        var tbody = document.querySelector("#dgTablaPuntualidad tbody");
        var canvas = document.getElementById("dgGraficoPuntualidad");
        var empty = document.getElementById("dgPuntualidadEmpty");

        setText("dgPuntualidadLabel", periodo.label || "");
        setText("dgPuntPeriodoRango", periodo.label || "—");
        setText("dgPuntTotal", formatoMonto(data.total));
        setText("dgPuntPctATiempo", (data.pct_a_tiempo !== null && data.pct_a_tiempo !== undefined)
            ? Number(data.pct_a_tiempo).toFixed(1) + "%"
            : "—");
        setText("dgPuntATiempo", formatoMonto(data.a_tiempo));
        setText("dgPuntAtrasado", formatoMonto(data.atrasado));
        setText("dgPuntPendiente", formatoMonto(data.pendiente));
        setText("dgPuntDocsATiempo", (data.docs_a_tiempo || 0) + " docs · " + Number(data.pct_a_tiempo || 0).toFixed(1) + "%");
        setText("dgPuntDocsAtrasado", (data.docs_atrasado || 0) + " docs · " + Number(data.pct_atrasado || 0).toFixed(1) + "%");
        setText("dgPuntDocsPendiente", (data.docs_pendiente || 0) + " docs · " + Number(data.pct_pendiente || 0).toFixed(1) + "%");
        setText("dgPuntualidadFormula", data.formula || "");

        if (tbody) {
            if (!mensual.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Sin vencimientos en el año</td></tr>';
            } else {
                tbody.innerHTML = mensual.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.total) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.a_tiempo) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.atrasado) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.pendiente) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct_a_tiempo || 0).toFixed(1) + "%</td>" +
                        "</tr>";
                }).join("");
            }
        }

        var hayDatos = Number(data.total) > 0 || mensual.some(function (f) { return Number(f.total) > 0; });
        toggleEmpty(empty, hayDatos);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        destruirChart(charts.puntualidad);
        charts.puntualidad = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: data.mensual_labels || [],
                datasets: [
                    {
                        label: "A tiempo",
                        data: data.mensual_a_tiempo || [],
                        backgroundColor: COLOR_VENTA.bg,
                        borderWidth: 0,
                        maxBarThickness: 36,
                        stack: "punt"
                    },
                    {
                        label: "Atrasado",
                        data: data.mensual_atrasado || [],
                        backgroundColor: "rgba(234, 88, 12, 0.75)",
                        borderWidth: 0,
                        maxBarThickness: 36,
                        stack: "punt"
                    },
                    {
                        label: "Pendiente",
                        data: data.mensual_pendiente || [],
                        backgroundColor: "rgba(148, 163, 184, 0.55)",
                        borderWidth: 0,
                        maxBarThickness: 36,
                        stack: "punt"
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "top" },
                    tooltip: {
                        callbacks: {
                            afterTitle: function (items) {
                                if (!items.length) {
                                    return "";
                                }
                                var i = items[0].dataIndex;
                                var fila = mensual[i] || {};
                                return "% a tiempo: " + Number(fila.pct_a_tiempo || 0).toFixed(1) + "%";
                            },
                            label: function (ctx) {
                                return (ctx.dataset.label || "") + ": " + formatoMonto(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true },
                    y: Object.assign({ stacked: true }, opcionesEjeY())
                }
            }
        });
    }

    function renderProyeccionCobranzas(data) {
        if (!data) {
            return;
        }

        var meses = data.meses || [];
        var totales = data.totales || {};
        var vencido = data.vencido || {};
        var empty = document.getElementById("dgProyeccionEmpty");
        var tbody = document.querySelector("#dgTablaProyeccion tbody");
        var canvas = document.getElementById("dgGraficoProyeccion");
        var extra = document.getElementById("dgProyExtra");

        setText("dgProyeccionLabel", (data.label_mes || "") + (data.fecha_corte ? " · corte " + data.fecha_corte : ""));
        setText("dgProyMesRango", data.label_mes || "Por vencer");
        setText("dgProyMesValor", formatoMonto(data.proyeccion_mes));
        setText("dgProyRealValor", formatoMonto(data.real_mes));
        setText("dgProyPctValor", (data.pct_cobertura !== null && data.pct_cobertura !== undefined)
            ? Number(data.pct_cobertura).toFixed(1) + "%"
            : ((data.pct_cumplimiento !== null && data.pct_cumplimiento !== undefined)
                ? Number(data.pct_cumplimiento).toFixed(1) + "%"
                : "—"));
        setText("dgProyVencidoValor", formatoMonto(vencido.total));
        setText("dgProyeccionNota", data.nota || "");

        var totalProy = Number(totales.proyeccion) || 0;
        var totalReal = meses.reduce(function (acc, f) { return acc + (Number(f.real) || 0); }, 0);

        setText("dgProyFootProy", formatoMonto(totalProy), "text-right");
        setText("dgProyFootReal", formatoMonto(totalReal), "text-right");
        setText("dgProyFootDelta", formatoDeltaAbs(totalReal - totalProy), "text-right " + claseDelta(totalReal - totalProy));

        if (extra) {
            var inc = data.incobrable || {};
            var post = data.posterior || {};
            extra.innerHTML =
                "<span><strong>Horizonte:</strong> S/ " + Number(totalProy).toLocaleString("es-PE", { maximumFractionDigits: 0 }) + "</span>" +
                "<span><strong>Incobrables:</strong> " + formatoMonto(inc.total) + "</span>" +
                (post.total ? "<span><strong>Posterior:</strong> " + formatoMonto(post.total) + "</span>" : "") +
                "<span><strong>Cartera total:</strong> " + formatoMonto(totales.general) + "</span>";
        }

        var hayDatos = totalProy > 0 || Number(vencido.total) > 0 || meses.length > 0;

        if (tbody) {
            if (!meses.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Sin vencimientos proyectados</td></tr>';
            } else {
                tbody.innerHTML = meses.map(function (fila) {
                    var delta = (Number(fila.real) || 0) - (Number(fila.proyeccion) || 0);
                    return "<tr>" +
                        "<td>" + (fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.proyeccion) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.real) + "</td>" +
                        '<td class="text-right ' + claseDelta(delta) + '">' + formatoDeltaAbs(delta) + "</td>" +
                        '<td class="text-right">' + (fila.documentos || 0) + "</td>" +
                        "</tr>";
                }).join("");
            }
        }

        toggleEmpty(empty, hayDatos);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        destruirChart(charts.proyeccion);
        charts.proyeccion = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: "Proyección",
                        data: data.montos_proyeccion || [],
                        backgroundColor: "rgba(37, 99, 235, 0.7)",
                        borderColor: "#1d4ed8",
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 28
                    },
                    {
                        label: "Real",
                        data: data.montos_real || [],
                        backgroundColor: "rgba(22, 163, 74, 0.7)",
                        borderColor: "#15803d",
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 28
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: "top" },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return (ctx.dataset.label || "") + ": " + formatoMonto(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: { y: opcionesEjeY() }
            }
        });
    }

    /** Agrupa filas &lt; umbral% en "Otros"; conserva "Sin origen" al final. */
    function agruparFilasOtros(filas, total, umbralPct) {
        var vista = [];
        var otrosMonto = 0;
        var nOtros = 0;
        var especial = null;
        var totalN = Number(total) || 0;

        (filas || []).forEach(function (f) {
            var label = f.label || "";
            if (label === "Sin origen") {
                especial = f;
                return;
            }
            if (Number(f.pct) >= umbralPct) {
                vista.push(f);
            } else {
                otrosMonto += Number(f.monto) || 0;
                nOtros += 1;
            }
        });

        if (nOtros > 0 && otrosMonto > 0) {
            vista.push({
                label: "Otros (" + nOtros + ")",
                monto: otrosMonto,
                pct: totalN > 0 ? Math.round((otrosMonto / totalN) * 1000) / 10 : 0,
                esOtros: true
            });
        }
        if (especial) {
            vista.push(especial);
        }
        return vista;
    }

    function renderTablaBarrasMes(tbody, filasVista, emptyMsg) {
        if (!tbody) {
            return;
        }
        if (!filasVista.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">' + (emptyMsg || "Sin datos") + "</td></tr>";
            return;
        }
        var maxMonto = 0;
        filasVista.forEach(function (f) {
            var m = Number(f.monto) || 0;
            if (m > maxMonto) {
                maxMonto = m;
            }
        });
        tbody.innerHTML = filasVista.map(function (fila, i) {
            var monto = Number(fila.monto) || 0;
            var pctBar = maxMonto > 0 ? Math.round((monto / maxMonto) * 1000) / 10 : 0;
            var color = fila.esOtros
                ? "rgba(100, 116, 139, 0.7)"
                : PALETTE_ORIGEN[i % PALETTE_ORIGEN.length];
            var trCls = fila.esOtros ? ' class="dg-fila-otros"' : "";
            return "<tr" + trCls + ">" +
                "<td>" + (fila.label || "") + "</td>" +
                '<td class="dg-col-barra">' +
                    '<div class="dg-barra-track" title="' + Number(fila.pct || 0).toFixed(1) + '%">' +
                        '<div class="dg-barra-fill" style="width:' + pctBar + "%;background:" + color + '"></div>' +
                    "</div>" +
                "</td>" +
                '<td class="text-right">' + formatoMonto(fila.monto) + "</td>" +
                '<td class="text-right">' + Number(fila.pct || 0).toFixed(1) + "%</td>" +
                "</tr>";
        }).join("");
    }

    function renderOrigenCobranza(data) {
        if (!data) {
            return;
        }

        var periodo = data.periodo_cobro || {};
        var filas = data.filas || [];
        var filasRecup = data.filas_recuperacion || [];
        var mensual = data.mensual || [];
        var aging = data.aging || {};
        var agingFilas = aging.filas || [];
        var porVendedor = data.por_vendedor || [];
        var tbody = document.querySelector("#dgTablaOrigenCobranza tbody");
        var tbodyRecup = document.querySelector("#dgTablaRecuperacionVenta tbody");
        var tbodyMensual = document.querySelector("#dgTablaRecupMensual tbody");
        var tbodyAging = document.querySelector("#dgTablaAging tbody");
        var tbodyVend = document.querySelector("#dgTablaOrigenVendedor tbody");
        var boxVend = document.getElementById("dgOrigenVendedoresBox");
        var canvasPct = document.getElementById("dgGraficoPctMismoMesAnio");
        var canvasVs = document.getElementById("dgGraficoVentasVsRecup");
        var canvasAging = document.getElementById("dgGraficoAgingCobranza");

        setText("dgOrigenCobranzaLabel", periodo.label || "");
        setText("dgOrigenPeriodoRango", periodo.label || "—");
        setText("dgOrigenVentaRango", periodo.label || "—");
        setText("dgOrigenTotal", formatoMonto(data.total));
        setText("dgOrigenPctRecup", (data.pct_mismo_mes !== null && data.pct_mismo_mes !== undefined)
            ? Number(data.pct_mismo_mes).toFixed(1) + "%"
            : "—");
        setText("dgOrigenMismoMes", formatoMonto(data.mismo_mes));
        setText("dgOrigenPctConOrigen", (data.pct_con_origen !== null && data.pct_con_origen !== undefined)
            ? Number(data.pct_con_origen).toFixed(1) + "%"
            : "—");
        setText("dgOrigenVentaPeriodo", formatoMonto(data.venta_periodo));
        setText("dgOrigenRecupPeriodo", formatoMonto(data.recuperado_periodo));
        setText("dgOrigenPctRecupPeriodo", (data.pct_recup_periodo !== null && data.pct_recup_periodo !== undefined)
            ? Number(data.pct_recup_periodo).toFixed(1) + "%"
            : "—");
        setText("dgOrigenFootTotal", formatoMonto(data.total), "text-right");
        setText("dgRecupFootTotal", formatoMonto(data.total_recuperacion || data.recuperado_periodo), "text-right");
        setText("dgRecupFootPct", (data.pct_recup_periodo !== null && data.pct_recup_periodo !== undefined)
            ? Number(data.pct_recup_periodo).toFixed(1) + "%"
            : "—", "text-right");
        setText("dgRecupFootPendiente", formatoMonto(data.pendiente_periodo), "text-right");
        var pctPend = Number(data.venta_periodo) > 0
            ? ((Number(data.pendiente_periodo) || 0) / Number(data.venta_periodo) * 100).toFixed(1) + "%"
            : "—";
        setText("dgRecupFootPendPct", pctPend, "text-right");
        setText("dgAgingFootTotal", formatoMonto(aging.total || 0), "text-right");
        setText("dgOrigenFormula", data.formula || "");
        setText(
            "dgRecupHint",
            "Ventas " + (periodo.label || "") + ": " + formatoMonto(data.venta_periodo) +
                " · recuperado hasta hoy por mes de pago"
        );

        var UMBRAL_PCT = 2;
        renderTablaBarrasMes(
            tbody,
            agruparFilasOtros(filas, data.total, UMBRAL_PCT),
            "Sin cobranzas en el período"
        );
        renderTablaBarrasMes(
            tbodyRecup,
            agruparFilasOtros(filasRecup, data.total_recuperacion || data.recuperado_periodo, UMBRAL_PCT),
            "Sin recuperación de esas ventas"
        );

        if (tbodyMensual) {
            if (!mensual.length) {
                tbodyMensual.innerHTML = '<tr><td colspan="8" class="text-muted">Sin datos del año</td></tr>';
            } else {
                tbodyMensual.innerHTML = mensual.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.venta) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.recuperado) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.pendiente) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct_recuperado || 0).toFixed(1) + "%</td>" +
                        '<td class="text-right">' + formatoMonto(fila.cobrado) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.mismo_mes) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct_mismo_mes || 0).toFixed(1) + "%</td>" +
                        "</tr>";
                }).join("");
            }
        }

        if (tbodyAging) {
            if (!agingFilas.length) {
                tbodyAging.innerHTML = '<tr><td colspan="3" class="text-muted">Sin cobranzas en el período</td></tr>';
            } else {
                tbodyAging.innerHTML = agingFilas.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.label || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.monto) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct || 0).toFixed(1) + "%</td>" +
                        "</tr>";
                }).join("");
            }
        }

        if (boxVend && tbodyVend) {
            if (porVendedor.length) {
                boxVend.classList.remove("is-hidden");
                tbodyVend.innerHTML = porVendedor.map(function (fila) {
                    return "<tr>" +
                        "<td>" + (fila.vendedor || "") + " — " + (fila.nombre || "") + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.total) + "</td>" +
                        '<td class="text-right">' + formatoMonto(fila.mismo_mes) + "</td>" +
                        '<td class="text-right">' + Number(fila.pct_mismo_mes || 0).toFixed(1) + "%</td>" +
                        "</tr>";
                }).join("");
            } else {
                boxVend.classList.add("is-hidden");
            }
        }

        toggleEmpty(document.getElementById("dgPctMismoMesEmpty"), mensual.length > 0);
        toggleEmpty(document.getElementById("dgVentasVsRecupEmpty"), mensual.length > 0);
        toggleEmpty(document.getElementById("dgAgingEmpty"), Number(aging.total) > 0);

        if (typeof Chart === "undefined") {
            return;
        }

        destruirChart(charts.pctMismoMesAnio);
        if (canvasPct) {
            charts.pctMismoMesAnio = new Chart(canvasPct.getContext("2d"), {
                type: "bar",
                data: {
                    labels: data.mensual_labels || [],
                    datasets: [{
                        label: "% cobro mismo mes",
                        data: data.mensual_pct_mismo_mes || [],
                        backgroundColor: "rgba(37, 99, 235, 0.75)",
                        borderColor: "#1d4ed8",
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 36
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return "% cobro mismo mes: " + Number(ctx.parsed.y).toFixed(1) + "%";
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            ticks: {
                                callback: function (value) {
                                    return value + "%";
                                }
                            }
                        }
                    }
                }
            });
        }

        destruirChart(charts.ventasVsRecup);
        if (canvasVs) {
            var ventasStack = data.mensual_ventas || [];
            var recupStack = data.mensual_recuperado || [];
            var pendStack = data.mensual_pendiente || [];
            charts.ventasVsRecup = new Chart(canvasVs.getContext("2d"), {
                type: "bar",
                data: {
                    labels: data.mensual_labels || [],
                    datasets: [
                        {
                            label: "Recuperado",
                            data: recupStack,
                            backgroundColor: COLOR_VENTA.bg,
                            borderColor: COLOR_VENTA.border,
                            borderWidth: 0,
                            maxBarThickness: 36,
                            stack: "venta"
                        },
                        {
                            label: "Pendiente",
                            data: pendStack,
                            backgroundColor: "rgba(148, 163, 184, 0.55)",
                            borderColor: "#64748b",
                            borderWidth: 0,
                            maxBarThickness: 36,
                            stack: "venta"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: "top" },
                        tooltip: {
                            callbacks: {
                                afterTitle: function (items) {
                                    if (!items.length) {
                                        return "";
                                    }
                                    var i = items[0].dataIndex;
                                    var venta = Number(ventasStack[i]) || 0;
                                    return "Venta: " + formatoMonto(venta);
                                },
                                label: function (ctx) {
                                    var i = ctx.dataIndex;
                                    var venta = Number(ventasStack[i]) || 0;
                                    var monto = Number(ctx.parsed.y) || 0;
                                    var pct = venta > 0 ? ((monto / venta) * 100).toFixed(1) : "0.0";
                                    return (ctx.dataset.label || "") + ": " + formatoMonto(monto) + " (" + pct + "%)";
                                }
                            }
                        }
                    },
                    scales: {
                        x: { stacked: true },
                        y: Object.assign({ stacked: true }, opcionesEjeY())
                    }
                }
            });
        }

        destruirChart(charts.agingCobranza);
        if (canvasAging) {
            var agingLabels = aging.labels || [];
            var agingMontos = aging.montos || [];
            var bgAging = agingLabels.map(function (_, i) {
                return PALETTE_ORIGEN[i % PALETTE_ORIGEN.length];
            });
            charts.agingCobranza = new Chart(canvasAging.getContext("2d"), {
                type: "bar",
                data: {
                    labels: agingLabels,
                    datasets: [{
                        label: "Cobranza por antigüedad",
                        data: agingMontos,
                        backgroundColor: bgAging,
                        borderWidth: 0,
                        borderRadius: 3,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var fila = agingFilas[ctx.dataIndex] || {};
                                    return formatoMonto(ctx.parsed.y) +
                                        (fila.pct !== undefined ? " (" + Number(fila.pct).toFixed(1) + "%)" : "");
                                }
                            }
                        }
                    },
                    scales: { y: opcionesEjeY() }
                }
            });
        }
    }

    $(function () {
        if (!elData()) {
            return;
        }

        if ($.fn.selectpicker) {
            $(".dg-dashboard .selectpicker").selectpicker();
        }

        togglePeriodos();

        $("#modoDg").on("changed.bs.select change", function () {
            togglePeriodos();
        });

        $("#btnAplicarFiltrosDg").on("click", function () {
            irConEstado(estadoDesdeFormulario());
        });

        $("#anioDg, #mesDg, #vendedorDg").on("changed.bs.select", function () {
            irConEstado(estadoDesdeFormulario());
        });

        cargarBase(leerEstado());
    });
})();
