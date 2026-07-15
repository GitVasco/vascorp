/* global $, L, Chart */
(function () {
    "use strict";

    /**
     * Polígonos aproximados de cobertura comercial (no GIS oficial).
     * Pensados para verse como sectores pintados sobre el mapa de Lima.
     * Leaflet: [lat, lng]
     */
    var MZ_GEO = {
        lima: {
            // Fuera del encuadre inicial metro; se ve al alejar o panear al norte
            NORTE_CHICO: {
                short: "Norte Chico",
                center: [-11.20, -77.55],
                ring: [
                    [-10.90, -77.85], [-10.95, -77.20], [-11.50, -77.15],
                    [-11.55, -77.80], [-10.90, -77.85]
                ],
                metro: false
            },
            CALLAO: {
                short: "Callao",
                center: [-12.04, -77.13],
                ring: [
                    [-11.92, -77.19], [-11.93, -77.09], [-12.00, -77.07],
                    [-12.08, -77.08], [-12.12, -77.12], [-12.10, -77.18],
                    [-12.02, -77.20], [-11.92, -77.19]
                ],
                metro: true
            },
            LIM_NORTE: {
                short: "Norte",
                center: [-11.93, -77.05],
                ring: [
                    [-11.78, -77.12], [-11.75, -76.92], [-11.90, -76.88],
                    [-12.00, -76.95], [-12.02, -77.08], [-11.95, -77.14],
                    [-11.85, -77.15], [-11.78, -77.12]
                ],
                metro: true
            },
            LIM_ESTE: {
                short: "Este",
                center: [-12.02, -76.88],
                ring: [
                    [-11.90, -76.95], [-11.88, -76.72], [-12.05, -76.70],
                    [-12.18, -76.78], [-12.15, -76.98], [-12.05, -76.98],
                    [-11.95, -76.98], [-11.90, -76.95]
                ],
                metro: true
            },
            LIM_CENTRO: {
                short: "Centro",
                center: [-12.06, -77.03],
                ring: [
                    [-12.02, -77.07], [-12.01, -76.99], [-12.05, -76.97],
                    [-12.09, -76.98], [-12.10, -77.05], [-12.07, -77.08],
                    [-12.03, -77.08], [-12.02, -77.07]
                ],
                metro: true
            },
            LIM_MODERNA: {
                short: "Moderna",
                center: [-12.12, -76.98],
                ring: [
                    [-12.08, -77.06], [-12.07, -76.90], [-12.10, -76.85],
                    [-12.18, -76.86], [-12.20, -76.95], [-12.18, -77.04],
                    [-12.12, -77.06], [-12.08, -77.06]
                ],
                metro: true
            },
            LIM_SUR: {
                short: "Sur",
                center: [-12.25, -76.94],
                ring: [
                    [-12.17, -77.03], [-12.16, -76.85], [-12.22, -76.80],
                    [-12.35, -76.82], [-12.38, -76.95], [-12.32, -77.05],
                    [-12.22, -77.05], [-12.17, -77.03]
                ],
                metro: true
            },
            // Encima del Centro (Gamarra / La Victoria comercial)
            LIM_ECONOMICA: {
                short: "Gamarra",
                center: [-12.066, -77.014],
                ring: [
                    [-12.060, -77.022], [-12.059, -77.008], [-12.068, -77.006],
                    [-12.074, -77.012], [-12.072, -77.022], [-12.060, -77.022]
                ],
                metro: true
            }
        },
        peru: {
            PERU_NORTE: {
                short: "Norte",
                center: [-6.5, -76.5],
                ring: [
                    [-0.1, -81.5], [-0.1, -70.0], [-10.0, -70.0],
                    [-12.0, -76.0], [-10.5, -79.5], [-3.5, -81.5], [-0.1, -81.5]
                ],
                metro: true
            },
            PERU_SUR: {
                short: "Sur",
                center: [-14.5, -72.5],
                ring: [
                    [-12.0, -76.0], [-10.0, -70.0], [-13.5, -68.5],
                    [-18.4, -70.3], [-18.4, -76.5], [-14.5, -77.5], [-12.0, -76.0]
                ],
                metro: true
            }
        }
    };

    // Orden de dibujo: debajo primero; Económica al final para que se vea sobre Centro
    var MZ_ORDEN_LIMA = [
        "NORTE_CHICO", "LIM_NORTE", "LIM_ESTE", "CALLAO",
        "LIM_SUR", "LIM_MODERNA", "LIM_CENTRO", "LIM_ECONOMICA"
    ];
    var MZ_ORDEN_PERU = ["PERU_NORTE", "PERU_SUR"];

    var mzCache = {};
    var mzPorCodigo = {};
    var mzVista = "lima";
    var mzSeleccion = null;
    var mzMapLima = null;
    var mzMapPeru = null;
    var mzLayerLima = null;
    var mzLayerPeru = null;
    var mzMapReady = { lima: false, peru: false };
    var mzYaAjustado = { lima: false, peru: false };
    var mzHistCharts = [];

    function mzPeriodo() {
        return {
            anio: parseInt($("#mzAnio").val(), 10) || new Date().getFullYear(),
            mes: parseInt($("#mzMes").val(), 10) || (new Date().getMonth() + 1)
        };
    }

    function mzLeerPeriodoUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            var anio = parseInt(params.get("anio"), 10);
            var mes = parseInt(params.get("mes"), 10);
            return {
                anio: !isNaN(anio) && anio >= 2000 && anio <= 2100 ? anio : null,
                mes: !isNaN(mes) && mes >= 1 && mes <= 12 ? mes : null
            };
        } catch (e) {
            return { anio: null, mes: null };
        }
    }

    function mzEscribirPeriodoUrl() {
        var p = mzPeriodo();
        try {
            var url = new URL(window.location.href);
            url.searchParams.set("anio", String(p.anio));
            url.searchParams.set("mes", String(p.mes));
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, "", url.pathname + url.search + url.hash);
            }
        } catch (e) {
            /* navegadores antiguos: sin sync de URL */
        }
    }

    function mzAsegurarOpcionAnio(anio) {
        anio = parseInt(anio, 10);
        if (isNaN(anio)) {
            return;
        }
        var $sel = $("#mzAnio");
        if ($sel.find("option[value='" + anio + "']").length) {
            return;
        }
        var $opts = $sel.find("option");
        var inserted = false;
        $opts.each(function () {
            if (parseInt($(this).val(), 10) > anio) {
                $(this).before($("<option>").val(anio).text(String(anio)));
                inserted = true;
                return false;
            }
        });
        if (!inserted) {
            $sel.append($("<option>").val(anio).text(String(anio)));
        }
    }

    function mzAplicarPeriodoUrl() {
        var u = mzLeerPeriodoUrl();
        if (u.anio != null) {
            mzAsegurarOpcionAnio(u.anio);
            $("#mzAnio").val(String(u.anio));
        }
        if (u.mes != null) {
            $("#mzMes").val(String(u.mes));
        }
        mzEscribirPeriodoUrl();
    }

    function mzCacheKey(vista) {
        var p = mzPeriodo();
        return vista + "|" + p.anio + "|" + p.mes;
    }

    function mzFmt(n) {
        return (Number(n) || 0).toLocaleString("es-PE");
    }

    function mzMoney(n) {
        return "S/ " + (Number(n) || 0).toLocaleString("es-PE", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    function mzEsc(s) {
        return $("<div>").text(s == null ? "" : String(s)).html();
    }

    function mzSetCarga(txt) {
        $("#mzEstadoCarga").text(txt || "");
    }

    function mzMaxVenta(zonas) {
        var max = 0;
        (zonas || []).forEach(function (z) {
            max = Math.max(max, Number(z.venta_real) || 0);
        });
        return max > 0 ? max : 1;
    }

    function mzDestroyHistCharts() {
        mzHistCharts.forEach(function (ch) {
            if (ch && typeof ch.destroy === "function") {
                try {
                    ch.destroy();
                } catch (e) { /* ignore */ }
            }
        });
        mzHistCharts = [];
        $("#mzHistGrid").empty();
    }

    function mzHexToRgba(hex, alpha) {
        var h = String(hex || "#777777").replace("#", "");
        if (h.length === 3) {
            h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        }
        if (h.length !== 6) {
            h = "777777";
        }
        var r = parseInt(h.slice(0, 2), 16);
        var g = parseInt(h.slice(2, 4), 16);
        var b = parseInt(h.slice(4, 6), 16);
        return "rgba(" + r + "," + g + "," + b + "," + alpha + ")";
    }

    function mzPintarMiniLinea(canvas, labels, valores, color) {
        if (!canvas || !canvas.parentNode || typeof Chart === "undefined") {
            return null;
        }
        var stroke = mzHexToRgba(color, 1);
        var fill = mzHexToRgba(color, 0.18);
        var data = (valores || []).map(function (v) {
            return Math.round(Number(v) || 0);
        });
        var ctx = canvas.getContext("2d");
        var chartData = {
            labels: labels,
            datasets: [
                {
                    label: "Venta",
                    fillColor: fill,
                    strokeColor: stroke,
                    pointColor: stroke,
                    pointStrokeColor: stroke,
                    pointHighlightFill: "#fff",
                    pointHighlightStroke: stroke,
                    data: data
                }
            ]
        };
        var optionsV1 = {
            scaleShowGridLines: false,
            scaleShowHorizontalLines: false,
            scaleShowVerticalLines: false,
            scaleShowLabels: false,
            scaleLineColor: "rgba(0,0,0,0)",
            scaleFontSize: 0,
            scaleBeginAtZero: true,
            pointDot: false,
            datasetStrokeWidth: 2,
            bezierCurve: true,
            bezierCurveTension: 0.3,
            datasetFill: true,
            responsive: true,
            maintainAspectRatio: false,
            showTooltips: true,
            tooltipTemplate: "<%if (label){%><%=label%>: <%}%>S/ <%= value %>"
        };

        var chartV1 = new Chart(ctx);
        if (typeof chartV1.Line === "function") {
            return chartV1.Line(chartData, optionsV1);
        }

        return new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data,
                        borderColor: stroke,
                        backgroundColor: fill,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 3,
                        fill: true,
                        lineTension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function (item) {
                            return "S/ " + mzFmt(item.yLabel != null ? item.yLabel : item.value);
                        }
                    }
                },
                scales: {
                    xAxes: [{ display: false }],
                    yAxes: [
                        {
                            display: false,
                            ticks: { beginAtZero: true }
                        }
                    ]
                }
            }
        });
    }

    function mzPintarHistorico(hist, vista) {
        var tituloVista = vista === "peru" ? "Perú sin Lima" : "Lima y alrededores";
        $("#mzHistTitulo").text(
            "(últimos 12 meses · escala propia por zona · " + tituloVista + ")"
        );

        var labels = hist && hist.labels ? hist.labels : [];
        var series = hist && hist.series ? hist.series : [];
        mzDestroyHistCharts();

        if (!labels.length || !series.length) {
            $("#mzHistEstado").text("Sin datos de ventas para el histórico").show();
            return;
        }
        $("#mzHistEstado").hide();

        if (typeof Chart === "undefined") {
            $("#mzHistEstado").text("Chart.js no disponible").show();
            return;
        }

        var $grid = $("#mzHistGrid");
        var ultimoLabel = labels.length ? labels[labels.length - 1] : "";

        series.forEach(function (s, idx) {
            var color = s.color || "#777777";
            var vals = s.valores || [];
            var ultimo = vals.length ? Number(vals[vals.length - 1]) || 0 : 0;
            var prev = vals.length > 1 ? Number(vals[vals.length - 2]) || 0 : 0;
            var deltaPct = null;
            if (prev !== 0) {
                deltaPct = ((ultimo - prev) / Math.abs(prev)) * 100;
            } else if (ultimo !== 0) {
                deltaPct = 100;
            } else {
                deltaPct = 0;
            }
            var deltaCls = "flat";
            var deltaTxt = "0%";
            if (deltaPct > 0.5) {
                deltaCls = "up";
                deltaTxt = "+" + deltaPct.toFixed(0) + "%";
            } else if (deltaPct < -0.5) {
                deltaCls = "down";
                deltaTxt = deltaPct.toFixed(0) + "%";
            }

            var canvasId = "mzHistMini_" + idx;
            var $card = $(
                "<div class='mz-hist-card' role='button' tabindex='0'/>"
            )
                .attr("data-codigo", s.codigo || "")
                .attr("title", "Ver ficha de " + (s.nombre || s.codigo || "zona"));

            $card.append(
                "<div class='mz-hist-card-head'>" +
                    "<div style='min-width:0;flex:1;'>" +
                    "<div class='mz-hist-card-name'>" +
                    mzEsc(s.nombre || s.codigo || "Zona") +
                    "</div>" +
                    "<div class='mz-hist-card-monto' style='color:" +
                    mzEsc(color) +
                    ";'>" +
                    mzEsc(mzMoney(ultimo)) +
                    "<span class='mz-hist-card-delta " +
                    deltaCls +
                    "'>" +
                    mzEsc(deltaTxt) +
                    "</span></div>" +
                    "<div class='mz-hist-card-meta'>vs mes anterior · " +
                    mzEsc(ultimoLabel) +
                    "</div>" +
                    "</div>" +
                    "<i class='mz-hist-card-swatch' style='background:" +
                    mzEsc(color) +
                    ";'></i>" +
                    "</div>" +
                    "<div class='mz-hist-card-canvas-wrap'>" +
                    "<canvas id='" +
                    canvasId +
                    "' height='72'></canvas>" +
                    "</div>"
            );
            $grid.append($card);

            var canvas = document.getElementById(canvasId);
            var chart = mzPintarMiniLinea(canvas, labels, vals, color);
            if (chart) {
                mzHistCharts.push(chart);
            }
        });
    }

    function mzZonasDesdeCache(entry) {
        if (!entry) {
            return [];
        }
        if (Array.isArray(entry)) {
            return entry;
        }
        return entry.zonas || [];
    }

    function mzInitMap(id, center, zoom) {
        if (typeof L === "undefined") {
            return null;
        }
        var map = L.map(id, {
            zoomControl: true,
            scrollWheelZoom: true
        }).setView(center, zoom);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        return map;
    }

    function mzEnsureMaps() {
        if (!mzMapLima && $("#mzMapLima").length) {
            mzMapLima = mzInitMap("mzMapLima", [-12.05, -77.02], 10);
            mzLayerLima = L.layerGroup().addTo(mzMapLima);
            mzMapReady.lima = true;
        }
        if (!mzMapPeru && $("#mzMapPeru").length) {
            mzMapPeru = mzInitMap("mzMapPeru", [-9.5, -75.0], 5);
            mzLayerPeru = L.layerGroup().addTo(mzMapPeru);
            mzMapReady.peru = true;
        }
    }

    function mzOrdenZonas(vista, zonas) {
        var orden = vista === "peru" ? MZ_ORDEN_PERU : MZ_ORDEN_LIMA;
        var mapa = {};
        (zonas || []).forEach(function (z) {
            mapa[z.codigo] = z;
        });
        var lista = [];
        orden.forEach(function (cod) {
            if (mapa[cod]) {
                lista.push(mapa[cod]);
            }
        });
        (zonas || []).forEach(function (z) {
            if (orden.indexOf(z.codigo) === -1) {
                lista.push(z);
            }
        });
        return lista;
    }

    function mzPintarMapa(vista, zonas, ajustarVista) {
        mzEnsureMaps();
        var map = vista === "peru" ? mzMapPeru : mzMapLima;
        var layer = vista === "peru" ? mzLayerPeru : mzLayerLima;
        var geoSet = vista === "peru" ? MZ_GEO.peru : MZ_GEO.lima;

        if (!map || !layer) {
            return;
        }

        layer.clearLayers();
        var boundsMetro = [];
        var boundsTodos = [];

        mzOrdenZonas(vista, zonas).forEach(function (z) {
            var geo = geoSet[z.codigo];
            if (!geo) {
                return;
            }
            var venta = Number(z.venta_real) || 0;
            var seleccionada = mzSeleccion === z.codigo;
            var fillOpacity = seleccionada ? 0.78 : 0.62;

            var poly = L.polygon(geo.ring, {
                color: "#ffffff",
                weight: seleccionada ? 3 : 2,
                opacity: 0.95,
                fillColor: z.color || "#777777",
                fillOpacity: fillOpacity,
                className: "mz-zona-poly"
            }).addTo(layer);

            poly.bindTooltip(
                "<strong>" + mzEsc(geo.short || z.nombre) + "</strong><br>" +
                    "<span style='font-size:15px;font-weight:800;'>" + mzMoney(venta) + "</span><br>" +
                    "<span style='font-size:11px;'>" + mzEsc(z.nombre) + "</span>",
                {
                    sticky: true,
                    opacity: 0.97,
                    className: "mz-tooltip-zona"
                }
            );

            poly.on("mouseover", function () {
                this.setStyle({ fillOpacity: 0.85, weight: 3 });
                this.bringToFront();
            });
            poly.on("mouseout", function () {
                this.setStyle({
                    fillOpacity: mzSeleccion === z.codigo ? 0.78 : 0.62,
                    weight: mzSeleccion === z.codigo ? 3 : 2
                });
            });
            poly.on("click", function () {
                mzMostrarFicha(z.codigo);
            });

            geo.ring.forEach(function (p) {
                boundsTodos.push(p);
                if (geo.metro !== false) {
                    boundsMetro.push(p);
                }
            });
        });

        if (ajustarVista) {
            var usar = boundsMetro.length ? boundsMetro : boundsTodos;
            if (usar.length) {
                try {
                    map.fitBounds(usar, { padding: [28, 28], maxZoom: vista === "peru" ? 6 : 12 });
                } catch (e) { /* ignore */ }
            }
            mzYaAjustado[vista] = true;
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 80);
    }

    function mzPintarTreemap(zonas) {
        var $box = $("#mzTreemap").empty();
        if (!zonas || !zonas.length) {
            $box.html("<p class='text-muted'>Sin zonas.</p>");
            return;
        }

        var max = mzMaxVenta(zonas);
        var orden = zonas.slice().sort(function (a, b) {
            return (Number(b.venta_real) || 0) - (Number(a.venta_real) || 0);
        });

        orden.forEach(function (z) {
            var venta = Number(z.venta_real) || 0;
            var grow = Math.max(venta, max * 0.04);
            var $cell = $("<div class='mz-treemap-cell'/>")
                .attr("data-codigo", z.codigo)
                .css({
                    background: z.color || "#777",
                    flexGrow: grow,
                    flexBasis: Math.max(70, (venta / max) * 220) + "px"
                })
                .toggleClass("mz-active", mzSeleccion === z.codigo)
                .append($("<div class='mz-treemap-title'/>").text(z.nombre || z.codigo))
                .append($("<div class='mz-treemap-venta'/>").text(mzFmt(venta)))
                .append(
                    $("<div class='mz-treemap-meta'/>").text(
                        mzFmt(z.clientes_con_venta != null ? z.clientes_con_venta : 0) + " clientes"
                    )
                );

            $box.append($cell);
        });
    }

    function mzPintarLeyenda(zonas) {
        var $box = $("#mzLeyenda").empty();
        if (!zonas || !zonas.length) {
            $box.html("<p class='text-muted'>Sin zonas para esta vista.</p>");
            return;
        }
        var orden = zonas.slice().sort(function (a, b) {
            return (Number(b.venta_real) || 0) - (Number(a.venta_real) || 0);
        });
        orden.forEach(function (z) {
            var $item = $("<div class='mz-leyenda-item'/>");
            var $main = $("<div class='mz-leyenda-main'/>")
                .attr("data-codigo", z.codigo)
                .css({ display: "flex", alignItems: "center", gap: "8px" })
                .append($("<span class='mz-leyenda-swatch'/>").css("background", z.color || "#777"))
                .append(
                    $("<span style='flex:1;min-width:0;'/>").html(
                        mzEsc(z.nombre) +
                            "<br><span class='mz-leyenda-venta'>" +
                            mzMoney(z.venta_real) +
                            "</span>"
                    )
                );

            var $btn = $(
                "<button type='button' class='btn btn-xs btn-default mz-leyenda-btn btnMzVerClientes' title='Ver clientes con venta'/>"
            )
                .attr("data-id-zona", z.id)
                .attr("data-codigo", z.codigo)
                .html("<i class='fa fa-list'></i>");

            $item.append($main).append($btn);
            $box.append($item);
        });
    }

    function mzAbrirClientesZona(idZona, codigo, modo) {
        var z = codigo ? mzPorCodigo[codigo] : null;
        if (!idZona && z) {
            idZona = z.id;
        }
        if (!idZona) {
            return;
        }
        if (codigo) {
            mzMostrarFicha(codigo);
        }

        var soloNuevos = modo === "nuevos";
        var p = mzPeriodo();
        var nombre = z ? z.nombre : "Zona";
        var colorZona = z ? z.color || "#3c8dbc" : "#3c8dbc";
        $("#mzModalTituloTipo").text(soloNuevos ? "Clientes nuevos" : "Clientes con venta");
        $("#mzModalZonaNombre").text(nombre);
        $("#mzModalHeader").css("background", soloNuevos ? "#00a65a" : colorZona);
        $("#mzModalPeriodo").text(p.mes + "/" + p.anio);
        $("#mzModalTotalVenta").text("…");
        $("#mzModalTotalCli").text("…");
        $("#mzTablaClientes tbody").html(
            "<tr><td colspan='4' class='text-muted'><i class='fa fa-spinner fa-spin'></i> Cargando…</td></tr>"
        );
        $("#modalMzClientesZona").modal("show");

        $.post(
            "ajax/zonas-comerciales.ajax.php",
            {
                accion: soloNuevos ? "clientesNuevosZona" : "clientesVentaZona",
                idZona: idZona,
                anio: p.anio,
                mes: p.mes
            },
            function (resp) {
                if (!resp || !resp.ok) {
                    $("#mzTablaClientes tbody").html(
                        "<tr><td colspan='4' class='text-danger'>" +
                            mzEsc((resp && resp.mensaje) || "No se pudo cargar") +
                            "</td></tr>"
                    );
                    return;
                }
                $("#mzModalTotalVenta").text(mzMoney(resp.total_venta));
                $("#mzModalTotalCli").text(mzFmt(resp.total_clientes));
                if (resp.zona && resp.zona.nombre) {
                    $("#mzModalZonaNombre").text(resp.zona.nombre);
                }
                var tip = soloNuevos
                    ? "primera venta del período · vendedor de esa venta"
                    : "máx. 500, nuevos primero · vendedores con venta";
                $("#mzModalResumen").html(
                    "Período: <strong>" +
                        mzEsc(String(p.mes + "/" + p.anio)) +
                        "</strong>. Total: <strong id='mzModalTotalVenta'>" +
                        mzEsc(mzMoney(resp.total_venta)) +
                        "</strong> · <span id='mzModalTotalCli'>" +
                        mzEsc(mzFmt(resp.total_clientes)) +
                        "</span> clientes (" +
                        tip +
                        ")."
                );
                var rows = resp.clientes || [];
                if (!rows.length) {
                    $("#mzTablaClientes tbody").html(
                        "<tr><td colspan='4' class='text-muted'>" +
                            (soloNuevos
                                ? "Ningún cliente nuevo en este período"
                                : "Ningún cliente con venta en este período") +
                            "</td></tr>"
                    );
                    return;
                }
                var html = "";
                rows.forEach(function (c) {
                    var cat = c.categoria ? String(c.categoria) : "";
                    var colorCat = c.categoria_color ? String(c.categoria_color) : "#777777";
                    var grupo = c.nombre_grupo ? String(c.nombre_grupo) : "";
                    var badgeNuevo =
                        soloNuevos || c.es_nuevo
                            ? "<span class='label label-success' style='font-weight:600;flex:0 0 auto;'>Nuevo</span>"
                            : "";
                    var badgeGrupo = grupo
                        ? "<span class='label label-primary' style='font-weight:600;flex:0 0 auto;' title='Grupo empresarial'>" +
                          mzEsc(grupo) +
                          "</span>"
                        : "";
                    var badgeCat = cat
                        ? "<span class='label' style='font-weight:600;flex:0 0 auto;background-color:" +
                          mzEsc(colorCat) +
                          ";color:#fff;'>" +
                          mzEsc(cat) +
                          "</span>"
                        : "";
                    var badgesRight =
                        badgeNuevo || badgeGrupo || badgeCat
                            ? "<span style='display:flex;align-items:center;gap:4px;flex:0 0 auto;'>" +
                              badgeNuevo +
                              badgeGrupo +
                              badgeCat +
                              "</span>"
                            : "";
                    var vend = soloNuevos
                        ? c.cod_vendedor || "—"
                        : c.codigos_vendedor || c.cod_vendedor || "—";
                    html +=
                        "<tr>" +
                        "<td>" +
                        mzEsc(c.codigo) +
                        "</td>" +
                        "<td>" +
                        "<div style='display:flex;align-items:center;justify-content:space-between;gap:8px;'>" +
                        "<span style='min-width:0;'>" +
                        mzEsc(c.nombre) +
                        "</span>" +
                        badgesRight +
                        "</div></td>" +
                        "<td><code>" +
                        mzEsc(vend) +
                        "</code></td>" +
                        "<td class='text-right'><strong>" +
                        mzMoney(c.venta_real) +
                        "</strong></td>" +
                        "</tr>";
                });
                $("#mzTablaClientes tbody").html(html);
            },
            "json"
        ).fail(function () {
            $("#mzTablaClientes tbody").html(
                "<tr><td colspan='4' class='text-danger'>Error de comunicación</td></tr>"
            );
        });
    }

    function mzAplicarDatos(zonas, forzarAjuste) {
        mzPorCodigo = {};
        (zonas || []).forEach(function (z) {
            mzPorCodigo[z.codigo] = z;
        });
        var ajustar = !!forzarAjuste || !mzYaAjustado[mzVista];
        mzPintarMapa(mzVista, zonas, ajustar);
        mzPintarTreemap(zonas);
        mzPintarLeyenda(zonas);
    }

    function mzLimpiarSeleccion() {
        mzSeleccion = null;
        $(".mz-treemap-cell").removeClass("mz-active");
    }

    function mzMostrarFicha(codigo) {
        var z = mzPorCodigo[codigo];
        if (!z) {
            return;
        }
        mzSeleccion = codigo;
        $(".mz-treemap-cell").removeClass("mz-active");
        $(".mz-treemap-cell[data-codigo='" + codigo + "']").addClass("mz-active");

        var zonas = mzZonasDesdeCache(mzCache[mzCacheKey(mzVista)]);
        mzPintarMapa(mzVista, zonas, false);
        mzPintarTreemap(zonas);

        $("#mzFichaVacio").hide();
        $("#mzFichaDetalle").show();
        $("#mzFichaTitulo").text(z.nombre || codigo);
        $("#mzFichaHeader").css("background", z.color || "#3c8dbc");
        $("#mzFichaDesc").text(z.descripcion || "");
        $("#mzFichaVenta").text(mzMoney(z.venta_real));
        $("#mzFichaClientesVenta").text(
            mzFmt(z.clientes_con_venta != null ? z.clientes_con_venta : 0)
        );
        $("#mzFichaClientesNuevos").text(
            mzFmt(z.clientes_nuevos != null ? z.clientes_nuevos : 0)
        );
        $("#mzFichaClientesSinAtender").text(
            mzFmt(z.clientes_sin_atender != null ? z.clientes_sin_atender : 0)
        );
        $("#mzFichaVendCount").text(mzFmt(z.total_vendedores));
        $("#mzFichaUbigeos").text(mzFmt(z.total_ubigeos));
        $("#mzFichaCodigo").text(z.codigo || "");
        $("#mzBtnFichaClientes").attr("data-id-zona", z.id).attr("data-codigo", z.codigo);
        $("#mzBtnFichaNuevos").attr("data-id-zona", z.id).attr("data-codigo", z.codigo);

        var $ulVenta = $("#mzFichaVentaVendedores").empty();
        var ventasVend = z.venta_por_vendedor || [];
        if (!ventasVend.length) {
            $ulVenta.append("<li class='text-muted'>Sin ventas de vendedores activos en el período</li>");
        } else {
            ventasVend.forEach(function (v) {
                $ulVenta.append(
                    "<li style='margin-bottom:4px;'><i class='fa fa-line-chart text-muted'></i> " +
                        mzEsc(v.codigo) +
                        " — " +
                        mzEsc(v.nombre) +
                        " <span class='pull-right'><strong>" +
                        mzMoney(v.venta) +
                        "</strong></span></li>"
                );
            });
        }

        var $ul = $("#mzFichaVendedores").empty();
        if (!z.vendedores || !z.vendedores.length) {
            $ul.append("<li class='text-muted'>Sin vendedores activos asignados</li>");
        } else {
            z.vendedores.forEach(function (v) {
                $ul.append(
                    "<li><i class='fa fa-user text-muted'></i> " +
                        mzEsc(v.codigo) +
                        " — " +
                        mzEsc(v.nombre) +
                        "</li>"
                );
            });
        }
    }

    function mzCargarVista(vista, forzar) {
        mzVista = vista || "lima";
        var key = mzCacheKey(mzVista);
        if (!forzar && mzCache[key]) {
            var cached = mzCache[key];
            mzAplicarDatos(mzZonasDesdeCache(cached), false);
            mzPintarHistorico(cached.historico || null, mzVista);
            mzSetCarga("");
            if (mzSeleccion && mzPorCodigo[mzSeleccion]) {
                mzMostrarFicha(mzSeleccion);
            }
            return;
        }

        var p = mzPeriodo();
        mzSetCarga("Cargando…");
        $.post(
            "ajax/zonas-comerciales.ajax.php",
            { accion: "resumenMapa", vista: mzVista, anio: p.anio, mes: p.mes },
            function (resp) {
                if (!resp || !resp.ok) {
                    mzSetCarga("Error al cargar");
                    return;
                }
                var zonas = resp.zonas || [];
                mzCache[key] = {
                    zonas: zonas,
                    historico: resp.historico_12m || null
                };
                mzYaAjustado[mzVista] = false;
                mzAplicarDatos(zonas, true);
                mzPintarHistorico(resp.historico_12m || null, mzVista);
                mzSetCarga(zonas.length + " zonas");
                if (mzSeleccion && mzPorCodigo[mzSeleccion]) {
                    mzMostrarFicha(mzSeleccion);
                } else {
                    mzLimpiarSeleccion();
                    $("#mzFichaDetalle").hide();
                    $("#mzFichaVacio").show();
                    $("#mzFichaTitulo").text("Seleccione una zona");
                    $("#mzFichaHeader").css("background", "#3c8dbc");
                }
            },
            "json"
        ).fail(function () {
            mzSetCarga("Error de comunicación");
        });
    }

    function mzCambiarVista(vista) {
        mzVista = vista;
        mzLimpiarSeleccion();
        $("#mzFichaDetalle").hide();
        $("#mzFichaVacio").show();
        $("#mzFichaTitulo").text("Seleccione una zona");
        $("#mzFichaHeader").css("background", "#3c8dbc");

        if (vista === "peru") {
            $("#mzVistaLima").hide();
            $("#mzVistaPeru").show();
            $("#mzToggleVista label").removeClass("active btn-primary").addClass("btn-default");
            $("#mzToggleVista label").has("input[value='peru']").removeClass("btn-default").addClass("active btn-primary");
        } else {
            $("#mzVistaPeru").hide();
            $("#mzVistaLima").show();
            $("#mzToggleVista label").removeClass("active btn-primary").addClass("btn-default");
            $("#mzToggleVista label").has("input[value='lima']").removeClass("btn-default").addClass("active btn-primary");
        }

        mzYaAjustado[vista] = false;
        mzCargarVista(vista, false);
        setTimeout(function () {
            var map = vista === "peru" ? mzMapPeru : mzMapLima;
            if (map) {
                map.invalidateSize();
            }
        }, 120);
    }

    $(document).on("change", "input[name='mzVista']", function () {
        mzCambiarVista($(this).val());
    });

    $(document).on("click", "#mzToggleVista label", function () {
        var v = $(this).find("input").val();
        if (v) {
            setTimeout(function () {
                mzCambiarVista(v);
            }, 0);
        }
    });

    $("#mzFormPeriodo").on("submit", function (e) {
        e.preventDefault();
        mzEscribirPeriodoUrl();
        mzCargarVista(mzVista, true);
    });

    $("#mzAnio, #mzMes").on("change", function () {
        mzEscribirPeriodoUrl();
        mzCargarVista(mzVista, true);
    });

    $(document).on("click", ".mz-treemap-cell, .mz-leyenda-main", function () {
        var codigo = $(this).data("codigo");
        if (codigo) {
            mzMostrarFicha(codigo);
        }
    });

    $(document).on("click", ".mz-hist-card", function () {
        var codigo = $(this).attr("data-codigo") || $(this).data("codigo");
        if (codigo) {
            mzMostrarFicha(codigo);
        }
    });

    $(document).on("click", ".btnMzVerClientes", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var idZona = $(this).attr("data-id-zona") || $(this).data("idZona");
        var codigo = $(this).attr("data-codigo") || $(this).data("codigo");
        mzAbrirClientesZona(idZona, codigo, "venta");
    });

    $(document).on("click", ".btnMzVerNuevos", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var idZona = $(this).attr("data-id-zona") || $(this).data("idZona");
        var codigo = $(this).attr("data-codigo") || $(this).data("codigo");
        mzAbrirClientesZona(idZona, codigo, "nuevos");
    });

    mzAplicarPeriodoUrl();

    if ($("#mzMapLima").length) {
        if (typeof L === "undefined") {
            mzSetCarga("No se pudo cargar Leaflet");
            $("#mzMapLima").html(
                "<div class='alert alert-warning' style='margin:12px;'>No se cargó el mapa (Leaflet). Revisa conexión a internet/CDN.</div>"
            );
        }
        mzCargarVista("lima", true);
    }
})();
