/* Helpdesk — dashboard indicadores (compacto + pasteles) */
(function ($) {
    "use strict";

    if ($("#panelHelpdesk").length === 0 || $("#hdVistaIndicadores").length === 0) {
        return;
    }

    var API = "ajax/helpdesk.ajax.php";
    var charts = {};
    var cargado = false;
    var PASTEL = [
        "#A8D5E5", "#F9C5A1", "#B8E0D2", "#F5B7B1", "#D2B4DE",
        "#FAD7A0", "#AED6F1", "#D5F5E3", "#F5CBA7", "#D7BDE2"
    ];
    var PASTEL_LINE = "#7FB3D5";
    var PASTEL_FILL = "rgba(168,213,229,0.35)";
    var PASTEL_BAR = "#C5B3E0";
    var PASTEL_SLA = ["#A9DFBF", "#F5B7B1"];

    function esc(v) {
        return $("<div>").text(v == null ? "" : String(v)).html();
    }

    function toast(tipo, msg) {
        if (window.toastr) {
            toastr[tipo](msg);
            return;
        }
        alert(msg);
    }

    function destruirChart(key) {
        if (charts[key]) {
            charts[key].destroy();
            charts[key] = null;
        }
    }

    function chartDisponible() {
        return typeof window.Chart !== "undefined";
    }

    function optsBase(extra) {
        var o = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { boxWidth: 10, font: { size: 10 }, padding: 8 }
                }
            }
        };
        return $.extend(true, o, extra || {});
    }

    function crearChart(key, canvasId, config) {
        destruirChart(key);
        var el = document.getElementById(canvasId);
        if (!el || !chartDisponible()) {
            return;
        }
        charts[key] = new Chart(el.getContext("2d"), config);
    }

    function fmtFechaInput(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1);
        var day = String(d.getDate());
        if (m.length < 2) {
            m = "0" + m;
        }
        if (day.length < 2) {
            day = "0" + day;
        }
        return y + "-" + m + "-" + day;
    }

    function setRangoMes() {
        var now = new Date();
        var desde = new Date(now.getFullYear(), now.getMonth(), 1);
        $("#hdIndDesde").val(fmtFechaInput(desde));
        $("#hdIndHasta").val(fmtFechaInput(now));
    }

    function setRangoDias(n) {
        var hasta = new Date();
        var desde = new Date();
        desde.setDate(hasta.getDate() - (n - 1));
        $("#hdIndDesde").val(fmtFechaInput(desde));
        $("#hdIndHasta").val(fmtFechaInput(hasta));
    }

    function badgeSlaMini(sla) {
        if (!sla || !sla.label) {
            return "—";
        }
        return '<span class="hd-sla ' + esc(sla.cls || "hd-sla-na") + '">' + esc(sla.label) + "</span>";
    }

    function deltaHtml(val, invertGood) {
        if (val === null || val === undefined || val === "") {
            return "";
        }
        var n = Number(val);
        if (isNaN(n)) {
            return "";
        }
        var up = n > 0;
        var good = invertGood ? !up : up;
        var cls = n === 0 ? "hd-delta-flat" : (good ? "hd-delta-up" : "hd-delta-down");
        var arrow = n > 0 ? "↑" : (n < 0 ? "↓" : "·");
        return '<span class="' + cls + '">' + arrow + " " + Math.abs(n) + "% vs ant.</span>";
    }

    function deltaPts(val, invertGood) {
        if (val === null || val === undefined || val === "") {
            return "";
        }
        var n = Number(val);
        if (isNaN(n)) {
            return "";
        }
        var up = n > 0;
        var good = invertGood ? !up : up;
        var cls = n === 0 ? "hd-delta-flat" : (good ? "hd-delta-up" : "hd-delta-down");
        var arrow = n > 0 ? "↑" : (n < 0 ? "↓" : "·");
        return '<span class="' + cls + '">' + arrow + " " + Math.abs(n) + " pts</span>";
    }

    function renderKpis(k) {
        k = k || {};
        var d = k.delta || {};
        $("#hdIndCreado").text(k.creados || 0);
        $("#hdIndCerrado").text(k.cerrados || 0);
        $("#hdIndAbierto").text(k.abiertos || 0);
        $("#hdIndVencido").text(k.vencidos || 0);
        $("#hdIndSlaPct").text(k.sla_pct == null ? "—" : (k.sla_pct + "%"));
        $("#hdIndPromedio").text(k.promedio_horas == null ? "—" : (k.promedio_horas + "h"));
        $("#hdIndDeltaCreado").html(deltaHtml(d.creados, false));
        $("#hdIndDeltaCerrado").html(deltaHtml(d.cerrados, false));
        $("#hdIndDeltaSla").html(deltaPts(d.sla_pct, false));
        // bajar horas es bueno
        if (d.promedio_horas != null) {
            var n = Number(d.promedio_horas);
            var cls = n < 0 ? "hd-delta-up" : (n > 0 ? "hd-delta-down" : "hd-delta-flat");
            var arrow = n > 0 ? "↑" : (n < 0 ? "↓" : "·");
            $("#hdIndDeltaPromedio").html(
                '<span class="' + cls + '">' + arrow + " " + Math.abs(n) + "h</span>"
            );
        } else {
            $("#hdIndDeltaPromedio").empty();
        }
    }

    function renderResolucion(rows) {
        var $box = $("#hdIndResolucionPri").empty();
        (rows || []).forEach(function (r) {
            var txt = r.horas == null ? "Sin datos" : (r.horas + " h · " + r.n + " tickets");
            $box.append(
                '<div class="hd-ind-res-row">' +
                    '<span class="label label-prioridad-' + esc(r.prioridad) + '">' + esc(r.prioridad) + "</span>" +
                    "<strong>" + esc(txt) + "</strong>" +
                "</div>"
            );
        });
    }

    function renderActividad(items) {
        var $box = $("#hdIndActividad").empty();
        if (!items || !items.length) {
            $box.append('<p class="text-muted">Sin actividad reciente.</p>');
            return;
        }
        items.forEach(function (a) {
            var tipo = a.tipo_evento || "EVENTO";
            $box.append(
                '<div class="hd-ind-act-item">' +
                    '<div class="hd-ind-act-dot"></div>' +
                    '<div>' +
                        '<a href="#ticket/' + esc(a.ticket_id) + '">#' + esc(a.ticket_id) + "</a> " +
                        "<strong>" + esc(tipo) + "</strong>" +
                        '<div class="text-muted">' + esc(a.usuario_nombre || "") +
                        " · " + esc(a.creado_en) + "</div>" +
                        '<div class="hd-ind-act-msg">' + esc(a.mensaje || a.ticket_titulo || "") + "</div>" +
                    "</div>" +
                "</div>"
            );
        });
    }

    function renderTablas(tablas) {
        tablas = tablas || {};
        var $asig = $("#hdIndTablaAsignados tbody").empty();
        var asig = tablas.asignados || [];
        if (!asig.length) {
            $asig.append('<tr><td colspan="6" class="text-muted text-center">Sin datos</td></tr>');
        } else {
            asig.forEach(function (a) {
                var slaBadge = a.sla_pct == null
                    ? "—"
                    : '<span class="hd-sla-pill">' + esc(a.sla_pct) + "%</span>";
                $asig.append(
                    "<tr>" +
                        "<td>" + esc(a.nombre) + "</td>" +
                        "<td>" + esc(a.creados) + "</td>" +
                        "<td>" + esc(a.abiertos) + "</td>" +
                        "<td>" + esc(a.cerrados) + "</td>" +
                        "<td>" + esc(a.vencidos) + "</td>" +
                        "<td>" + slaBadge + "</td>" +
                    "</tr>"
                );
            });
        }

        var $mod = $("#hdIndTablaModulos tbody").empty();
        var mods = tablas.top_modulos || [];
        if (!mods.length) {
            $mod.append('<tr><td colspan="3" class="text-muted text-center">Sin datos</td></tr>');
        } else {
            mods.forEach(function (m, i) {
                $mod.append(
                    "<tr><td>" + (i + 1) + "</td><td>" + esc(m.nombre) +
                    "</td><td><strong>" + esc(m.total) + "</strong></td></tr>"
                );
            });
        }

        var $ven = $("#hdIndTablaVencidos tbody").empty();
        var ven = tablas.vencidos || [];
        if (!ven.length) {
            $ven.append('<tr><td colspan="4" class="text-muted text-center">Sin vencidos</td></tr>');
        } else {
            ven.forEach(function (t) {
                $ven.append(
                    "<tr>" +
                        '<td><a href="#ticket/' + esc(t.id) + '">#' + esc(t.id) + "</a></td>" +
                        "<td>" + esc(t.titulo) + "</td>" +
                        "<td>" + esc(t.prioridad) + "</td>" +
                        "<td>" + badgeSlaMini(t.sla) + "</td>" +
                    "</tr>"
                );
            });
        }

        renderResolucion(tablas.resolucion_prioridad);
        renderActividad(tablas.actividad);
    }

    function renderCharts(ch) {
        ch = ch || {};
        if (!chartDisponible()) {
            toast("warning", "Chart.js no está disponible; se muestran solo tablas.");
            return;
        }

        crearChart("dia", "hdChartDia", {
            type: "line",
            data: {
                labels: (ch.por_dia && ch.por_dia.labels) || [],
                datasets: [{
                    label: "Creados",
                    data: (ch.por_dia && ch.por_dia.data) || [],
                    borderColor: PASTEL_LINE,
                    backgroundColor: PASTEL_FILL,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    borderWidth: 2
                }]
            },
            options: optsBase({
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 }, maxRotation: 0 } }
                }
            })
        });

        crearChart("sla", "hdChartSla", {
            type: "doughnut",
            data: {
                labels: (ch.sla && ch.sla.labels) || [],
                datasets: [{
                    data: (ch.sla && ch.sla.data) || [0, 0],
                    backgroundColor: PASTEL_SLA,
                    borderWidth: 0
                }]
            },
            options: optsBase({ cutout: "58%" })
        });

        crearChart("backlog", "hdChartBacklog", {
            type: "bar",
            data: {
                labels: (ch.backlog_edad && ch.backlog_edad.labels) || [],
                datasets: [{
                    data: (ch.backlog_edad && ch.backlog_edad.data) || [],
                    backgroundColor: PASTEL_BAR,
                    borderRadius: 4
                }]
            },
            options: optsBase({
                indexAxis: "y",
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                    y: { ticks: { font: { size: 10 } } }
                }
            })
        });

        function donut(key, id, serie) {
            crearChart(key, id, {
                type: "doughnut",
                data: {
                    labels: (serie && serie.labels) || [],
                    datasets: [{
                        data: (serie && serie.data) || [],
                        backgroundColor: PASTEL,
                        borderWidth: 0
                    }]
                },
                options: optsBase({
                    cutout: "55%",
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: { boxWidth: 8, font: { size: 9 }, padding: 6 }
                        }
                    }
                })
            });
        }

        donut("tipo", "hdChartTipo", ch.por_tipo);
        donut("prioridad", "hdChartPrioridad", ch.por_prioridad);
        donut("sistema", "hdChartSistema", ch.por_sistema);

        crearChart("area", "hdChartArea", {
            type: "bar",
            data: {
                labels: (ch.por_area && ch.por_area.labels) || [],
                datasets: [{
                    data: (ch.por_area && ch.por_area.data) || [],
                    backgroundColor: "#AED6F1",
                    borderRadius: 4
                }]
            },
            options: optsBase({
                indexAxis: "y",
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, font: { size: 9 } } },
                    y: { ticks: { font: { size: 9 } } }
                }
            })
        });
    }

    function cargarIndicadores() {
        var desde = $("#hdIndDesde").val();
        var hasta = $("#hdIndHasta").val();
        if (!desde || !hasta) {
            setRangoMes();
            desde = $("#hdIndDesde").val();
            hasta = $("#hdIndHasta").val();
        }
        return $.ajax({
            url: API + "?accion=indicadores",
            method: "POST",
            dataType: "json",
            data: { accion: "indicadores", desde: desde, hasta: hasta }
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudieron cargar indicadores.");
                    return;
                }
                cargado = true;
                if (res.sla_horas) {
                    $("#hdIndSlaHint").text(
                        "SLA laboral · Alta " + res.sla_horas.ALTA + "h · Media " +
                        res.sla_horas.MEDIA + "h · Baja " + res.sla_horas.BAJA + "h" +
                        " (lun–vie 8:00–17:30 · sáb 8:00–12:15). " +
                        "El % solo cuenta cumplido/fuera; Sin SLA y cancelados no entran."
                    );
                }
                renderKpis(res.kpis);
                renderCharts(res.charts);
                renderTablas(res.tablas);
            })
            .fail(function () {
                toast("error", "Error de red al cargar indicadores.");
            });
    }

    window.hdCargarIndicadores = function (force) {
        if (!cargado || force) {
            return cargarIndicadores();
        }
        return $.Deferred().resolve().promise();
    };

    $("#hdIndAplicar").on("click", function () {
        cargarIndicadores();
    });
    $("#hdIndMes").on("click", function () {
        setRangoMes();
        cargarIndicadores();
    });
    $("#hdInd30").on("click", function () {
        setRangoDias(30);
        cargarIndicadores();
    });
    $("#hdInd7").on("click", function () {
        setRangoDias(7);
        cargarIndicadores();
    });

    $("#hdVistaIndicadores").on("click", "a[href^='#ticket/']", function (e) {
        e.preventDefault();
        var href = $(this).attr("href") || "";
        var m = href.match(/#ticket\/(\d+)/);
        if (m && typeof window.hdAbrirTicket === "function") {
            window.hdAbrirTicket(Number(m[1]));
        } else if (m) {
            location.hash = "ticket/" + m[1];
        }
    });

    setRangoMes();
})(jQuery);
