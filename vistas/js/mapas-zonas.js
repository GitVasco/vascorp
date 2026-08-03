/* global $, L, Chart */
(function () {
    "use strict";

    /**
     * Polígonos aproximados solo donde no hay GeoJSON de distrito
     * (Norte Chico fuera de Lima Metropolitana; Gamarra no es distrito; Perú).
     * Leaflet: [lat, lng]
     */
    var MZ_GEO = {
        lima: {
            // Overlay comercial sobre La Victoria (no es ubigeo propio)
            LIM_ECONOMICA: {
                short: "Gamarra",
                center: [-12.066, -77.014],
                ring: [
                    [-12.060, -77.022], [-12.059, -77.008], [-12.068, -77.006],
                    [-12.074, -77.012], [-12.072, -77.022], [-12.060, -77.022]
                ],
                metro: true,
                overlay: true
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

    /** Departamento (sin tildes) → zona comercial Perú (excluye Lima/Callao del mapa). */
    var MZ_DEPTO_A_ZONA = {
        TUMBES: "PERU_NORTE",
        PIURA: "PERU_NORTE",
        LAMBAYEQUE: "PERU_NORTE",
        "LA LIBERTAD": "PERU_NORTE",
        CAJAMARCA: "PERU_NORTE",
        AMAZONAS: "PERU_NORTE",
        "SAN MARTIN": "PERU_NORTE",
        LORETO: "PERU_NORTE",
        UCAYALI: "PERU_NORTE",
        ANCASH: "PERU_NORTE",

        ICA: "PERU_SUR",
        AREQUIPA: "PERU_SUR",
        MOQUEGUA: "PERU_SUR",
        TACNA: "PERU_SUR",
        PUNO: "PERU_SUR",
        CUSCO: "PERU_SUR",
        APURIMAC: "PERU_SUR",
        "MADRE DE DIOS": "PERU_SUR",
        AYACUCHO: "PERU_SUR",
        HUANCAVELICA: "PERU_SUR",
        JUNIN: "PERU_SUR",
        HUANUCO: "PERU_SUR",
        PASCO: "PERU_SUR"
    };

    // Orden de dibujo overlays; Económica al final
    var MZ_ORDEN_LIMA = [
        "NORTE_CHICO",
        "LIM_ZONA_1", "LIM_ZONA_2", "LIM_ZONA_3",
        "LIM_VICTORIA", "LIM_CERCADO",
        "LIM_ECONOMICA"
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
    var mzCargaSeq = 0;
    var mzLimaDistritos = null;
    var mzLimaDistritosPromise = null;
    var mzNorteChicoGeo = null;
    var mzNorteChicoPromise = null;
    var mzPeruDepartamentos = null;
    var mzPeruDepartamentosPromise = null;
    var mzLayerByZona = {};
    var mzBoundsVista = { lima: null, peru: null };
    var mzGeoAsignacion = {
        departamentos: {},
        distritos: {},
        provincias: {}
    };
    var mzVentasGeo = {
        departamentos: {},
        distritos: {},
        provincias: {}
    };

    function mzNormNombre(s) {
        return String(s || "")
            .toUpperCase()
            .replace(/Á/g, "A")
            .replace(/É/g, "E")
            .replace(/Í/g, "I")
            .replace(/Ó/g, "O")
            .replace(/Ú/g, "U")
            .replace(/Ü/g, "U")
            .replace(/Ñ/g, "N")
            .replace(/\s+/g, " ")
            .trim();
    }

    function mzAplicarGeoAsignacion(geo) {
        mzGeoAsignacion = {
            departamentos: (geo && geo.departamentos) || {},
            distritos: (geo && geo.distritos) || {},
            provincias: (geo && geo.provincias) || {}
        };
    }

    function mzAplicarVentasGeo(ventas) {
        mzVentasGeo = {
            departamentos: (ventas && ventas.departamentos) || {},
            distritos: (ventas && ventas.distritos) || {},
            provincias: (ventas && ventas.provincias) || {}
        };
    }

    function mzVentaGeo(tipo, nombre) {
        var key = tipo === "distritos" ? mzClaveDistritoGeo(nombre) : mzNormNombre(nombre);
        var mapa = mzVentasGeo[tipo] || {};
        if (mapa[key] == null) {
            return 0;
        }
        return Number(mapa[key]) || 0;
    }

    function mzTooltipGeoHtml(titulo, nombreZona, ventaLocal, ventaZona) {
        var html =
            "<strong>" +
            mzEsc(titulo) +
            "</strong><br>" +
            "<span style='font-size:12px;'>" +
            mzEsc(nombreZona || "") +
            "</span><br>" +
            "<span style='font-size:15px;font-weight:800;'>" +
            mzMoney(ventaLocal) +
            "</span>";
        if (ventaZona != null && Number(ventaZona) > 0 && Math.round(Number(ventaZona)) !== Math.round(Number(ventaLocal))) {
            html +=
                "<br><span style='font-size:11px;opacity:.85;'>Zona: " +
                mzMoney(ventaZona) +
                "</span>";
        }
        return html;
    }

    /** GeoJSON IGN → clave en geo_asignacion (ubigeo) */
    var MZ_ALIAS_DISTRITO_GEO = {
        LIMA: "LIMA (CERCADO)"
    };

    function mzClaveDistritoGeo(nombreDistrito) {
        var key = mzNormNombre(nombreDistrito);
        return MZ_ALIAS_DISTRITO_GEO[key] || key;
    }

    function mzCodigoZonaActiva(codigo) {
        if (!codigo || !mzPorCodigo[codigo]) {
            return null;
        }
        return codigo;
    }

    function mzZonaDeDistrito(nombreDistrito) {
        var key = mzClaveDistritoGeo(nombreDistrito);
        return mzCodigoZonaActiva(mzGeoAsignacion.distritos[key] || null);
    }

    function mzZonaDeDepartamento(nombreDep) {
        var key = mzNormNombre(nombreDep);
        if (key === "LIMA" || key === "CALLAO") {
            return null;
        }
        var codigo = mzGeoAsignacion.departamentos[key] || MZ_DEPTO_A_ZONA[key] || null;
        return mzCodigoZonaActiva(codigo);
    }

    function mzZonaDeProvincia(nombreProv) {
        var key = mzNormNombre(nombreProv);
        var codigo = mzGeoAsignacion.provincias[key] || null;
        if (!codigo && (key === "BARRANCA" || key === "HUARAL" || key === "HUAURA")) {
            codigo = "NORTE_CHICO";
        }
        return mzCodigoZonaActiva(codigo);
    }

    function mzCargarDistritosLima() {
        if (mzLimaDistritos) {
            return $.Deferred().resolve(mzLimaDistritos).promise();
        }
        if (mzLimaDistritosPromise) {
            return mzLimaDistritosPromise;
        }
        mzLimaDistritosPromise = $.getJSON("vistas/js/data/lima-callao-distritos.geojson").then(
            function (data) {
                mzLimaDistritos = data;
                return data;
            },
            function () {
                mzLimaDistritosPromise = null;
                return $.Deferred().reject().promise();
            }
        );
        return mzLimaDistritosPromise;
    }

    function mzCargarNorteChico() {
        if (mzNorteChicoGeo) {
            return $.Deferred().resolve(mzNorteChicoGeo).promise();
        }
        if (mzNorteChicoPromise) {
            return mzNorteChicoPromise;
        }
        mzNorteChicoPromise = $.getJSON("vistas/js/data/norte-chico-provincias.geojson").then(
            function (data) {
                mzNorteChicoGeo = data;
                return data;
            },
            function () {
                mzNorteChicoPromise = null;
                return $.Deferred().resolve(null).promise();
            }
        );
        return mzNorteChicoPromise;
    }

    function mzCargarGeoLimaCompleto() {
        var dfd = $.Deferred();
        mzCargarDistritosLima()
            .done(function (lima) {
                mzCargarNorteChico().always(function (norte) {
                    dfd.resolve({
                        lima: lima,
                        norte: norte && norte.type ? norte : mzNorteChicoGeo
                    });
                });
            })
            .fail(function () {
                dfd.reject();
            });
        return dfd.promise();
    }

    function mzCargarDepartamentosPeru() {
        if (mzPeruDepartamentos) {
            return $.Deferred().resolve(mzPeruDepartamentos).promise();
        }
        if (mzPeruDepartamentosPromise) {
            return mzPeruDepartamentosPromise;
        }
        mzPeruDepartamentosPromise = $.getJSON("vistas/js/data/peru-departamentos.geojson").then(
            function (data) {
                mzPeruDepartamentos = data;
                return data;
            },
            function () {
                mzPeruDepartamentosPromise = null;
                return $.Deferred().reject().promise();
            }
        );
        return mzPeruDepartamentosPromise;
    }

    function mzPeriodo() {
        return {
            anio: parseInt($("#mzAnio").val(), 10) || new Date().getFullYear(),
            mes: parseInt($("#mzMes").val(), 10) || (new Date().getMonth() + 1)
        };
    }

    function mzGrupoMarca() {
        var v = parseInt($("input[name='mzGrupoMarca']:checked").val(), 10);
        return !isNaN(v) && v > 0 ? v : 0;
    }

    function mzFiltroDistribuidor() {
        var v = String($("#mzFiltroDistribuidor").val() || "con").toLowerCase();
        return v === "solo" || v === "sin" ? v : "con";
    }

    function mzLeerPeriodoUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            var anio = parseInt(params.get("anio"), 10);
            var mes = parseInt(params.get("mes"), 10);
            var grupoMarca = parseInt(params.get("grupo_marca"), 10);
            var distribuidores = String(params.get("distribuidores") || "con").toLowerCase();
            var vista = String(params.get("vista") || "lima").toLowerCase();
            var zona = String(params.get("zona") || "").trim();
            return {
                anio: !isNaN(anio) && anio >= 2000 && anio <= 2100 ? anio : null,
                mes: !isNaN(mes) && mes >= 1 && mes <= 12 ? mes : null,
                grupoMarca: !isNaN(grupoMarca) && grupoMarca > 0 ? grupoMarca : 0,
                distribuidores: distribuidores === "solo" || distribuidores === "sin" ? distribuidores : "con",
                vista: vista === "peru" ? "peru" : "lima",
                zona: /^[A-Za-z0-9_-]+$/.test(zona) ? zona : ""
            };
        } catch (e) {
            return { anio: null, mes: null, grupoMarca: 0, distribuidores: "con", vista: "lima", zona: "" };
        }
    }

    function mzEscribirPeriodoUrl() {
        var p = mzPeriodo();
        try {
            var url = new URL(window.location.href);
            url.searchParams.set("anio", String(p.anio));
            url.searchParams.set("mes", String(p.mes));
            url.searchParams.set("distribuidores", mzFiltroDistribuidor());
            url.searchParams.set("grupo_marca", String(mzGrupoMarca()));
            url.searchParams.set("vista", mzVista);
            if (mzSeleccion) {
                url.searchParams.set("zona", mzSeleccion);
            } else {
                url.searchParams.delete("zona");
            }
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
        $("#mzFiltroDistribuidor").val(u.distribuidores);
        var $grupo = $("input[name='mzGrupoMarca'][value='" + u.grupoMarca + "']");
        if (!$grupo.length) {
            $grupo = $("input[name='mzGrupoMarca'][value='0']");
        }
        $("input[name='mzGrupoMarca']").prop("checked", false);
        $grupo.prop("checked", true);
        mzVista = u.vista;
        mzSeleccion = u.zona || null;
        mzAplicarEstiloToggleGrupo();
        mzEscribirPeriodoUrl();
    }

    function mzCacheKey(vista) {
        var p = mzPeriodo();
        return vista + "|" + p.anio + "|" + p.mes + "|g" + mzGrupoMarca() + "|d" + mzFiltroDistribuidor();
    }

    function mzFmt(n) {
        return Math.round(Number(n) || 0).toLocaleString("es-PE");
    }

    function mzMoney(n) {
        return "S/ " + Math.round(Number(n) || 0).toLocaleString("es-PE");
    }

    function mzFechaCorta(fecha) {
        var p = String(fecha || "").split("-");
        return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : String(fecha || "");
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
            mzMapLima.on("mouseout", function (e) {
                if (!e.relatedTarget || !mzMapLima.getContainer().contains(e.relatedTarget)) {
                    mzCerrarTodosTooltips();
                }
            });
            $("#mzMapLima").on("mouseleave", function () {
                mzCerrarTodosTooltips();
            });
        }
        if (!mzMapPeru && $("#mzMapPeru").length) {
            mzMapPeru = mzInitMap("mzMapPeru", [-9.5, -75.0], 5);
            mzLayerPeru = L.layerGroup().addTo(mzMapPeru);
            mzMapReady.peru = true;
            mzMapPeru.on("mouseout", function (e) {
                if (!e.relatedTarget || !mzMapPeru.getContainer().contains(e.relatedTarget)) {
                    mzCerrarTodosTooltips();
                }
            });
            $("#mzMapPeru").on("mouseleave", function () {
                mzCerrarTodosTooltips();
            });
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

    function mzEstiloPoligonoZona(codigoZona, ventaLocal) {
        var z = mzPorCodigo[codigoZona] || {};
        var haySel = !!mzSeleccion;
        var seleccionada = haySel && mzSeleccion === codigoZona;
        var atenuada = haySel && !seleccionada;
        var sinVenta =
            seleccionada &&
            ventaLocal != null &&
            !isNaN(Number(ventaLocal)) &&
            Number(ventaLocal) <= 0;

        if (atenuada) {
            return {
                color: "#f2f2f2",
                weight: 1,
                opacity: 0.9,
                fillColor: "#c8c8c8",
                fillOpacity: 0.28,
                className: "mz-zona-poly mz-zona-dim"
            };
        }

        // Zona enfocada: piezas sin venta en gris
        if (sinVenta) {
            return {
                color: "#ffffff",
                weight: 2,
                opacity: 1,
                fillColor: "#9e9e9e",
                fillOpacity: 0.42,
                className: "mz-zona-poly mz-zona-sin-venta"
            };
        }

        return {
            color: "#ffffff",
            weight: seleccionada ? 3 : 1.4,
            opacity: 1,
            fillColor: z.color || "#bbbbbb",
            fillOpacity: seleccionada ? 0.82 : 0.68,
            className: "mz-zona-poly"
        };
    }

    function mzMetaCapa(codigoZona, geoTipo, geoNombre) {
        return {
            codigoZona: codigoZona || "",
            geoTipo: geoTipo || "",
            geoNombre: geoNombre || ""
        };
    }

    function mzEstiloDesdeCapa(ly) {
        var meta = (ly && ly._mzMeta) || {};
        var venta = null;
        if (meta.geoTipo && meta.geoNombre) {
            venta = mzVentaGeo(meta.geoTipo, meta.geoNombre);
        }
        return mzEstiloPoligonoZona(meta.codigoZona, venta);
    }

    function mzRegistrarCapaZona(ly, codigoZona, geoTipo, geoNombre) {
        if (!ly || !codigoZona) {
            return;
        }
        ly._mzMeta = mzMetaCapa(codigoZona, geoTipo, geoNombre);
        if (!mzLayerByZona[codigoZona]) {
            mzLayerByZona[codigoZona] = [];
        }
        mzLayerByZona[codigoZona].push(ly);
    }

    function mzMapaActivo() {
        return mzVista === "peru" ? mzMapPeru : mzMapLima;
    }

    function mzBoundsDeZona(codigoZona) {
        var layers = mzLayerByZona[codigoZona] || [];
        var bounds = null;
        layers.forEach(function (ly) {
            if (!ly || typeof ly.getBounds !== "function") {
                return;
            }
            try {
                var b = ly.getBounds();
                if (b && b.isValid()) {
                    bounds = bounds ? bounds.extend(b) : L.latLngBounds(b.getSouthWest(), b.getNorthEast());
                }
            } catch (e) { /* ignore */ }
        });
        return bounds;
    }

    function mzEnfocarZonaEnMapa(codigoZona) {
        var map = mzMapaActivo();
        if (!map || !codigoZona) {
            return;
        }
        var bounds = mzBoundsDeZona(codigoZona);
        if (!bounds || !bounds.isValid()) {
            return;
        }
        try {
            map.fitBounds(bounds, {
                padding: [40, 40],
                maxZoom: mzVista === "peru" ? 7 : 13,
                animate: true
            });
        } catch (e) { /* ignore */ }

        (mzLayerByZona[codigoZona] || []).forEach(function (ly) {
            if (ly && typeof ly.bringToFront === "function") {
                ly.bringToFront();
            }
        });
    }

    function mzRestaurarVistaMapa() {
        var map = mzMapaActivo();
        var bounds = mzBoundsVista[mzVista];
        if (!map || !bounds || !bounds.isValid) {
            return;
        }
        try {
            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: mzVista === "peru" ? [28, 28] : [24, 24],
                    maxZoom: mzVista === "peru" ? 6 : 12,
                    animate: true
                });
            }
        } catch (e) { /* ignore */ }
    }

    function mzGuardarBoundsVista(vista, puntos) {
        if (!puntos || !puntos.length || typeof L === "undefined") {
            return;
        }
        try {
            mzBoundsVista[vista] = L.latLngBounds(puntos);
        } catch (e) {
            mzBoundsVista[vista] = null;
        }
    }

    function mzCerrarTooltipsZona(codigoZona) {
        (mzLayerByZona[codigoZona] || []).forEach(function (ly) {
            if (ly && typeof ly.closeTooltip === "function") {
                try {
                    ly.closeTooltip();
                } catch (e) { /* ignore */ }
            }
        });
    }

    function mzCerrarTodosTooltips() {
        Object.keys(mzLayerByZona).forEach(function (cod) {
            mzCerrarTooltipsZona(cod);
        });
        // Por si quedó algún tooltip suelto en el DOM
        $(".leaflet-tooltip.mz-tooltip-zona").remove();
    }

    function mzBindZonaEvents(layer, codigoZona) {
        layer.on("mouseover", function () {
            if (mzSeleccion && mzSeleccion !== codigoZona) {
                return;
            }
            var lista = mzLayerByZona[codigoZona] || [];
            lista.forEach(function (ly) {
                if (ly.setStyle) {
                    ly.setStyle({ fillOpacity: 0.88, weight: 2.5 });
                }
                if (ly.bringToFront) {
                    ly.bringToFront();
                }
            });
        });
        layer.on("mouseout", function () {
            mzCerrarTooltipsZona(codigoZona);
            var lista = mzLayerByZona[codigoZona] || [];
            lista.forEach(function (ly) {
                if (ly.setStyle) {
                    ly.setStyle(mzEstiloDesdeCapa(ly));
                }
            });
        });
        layer.on("click", function () {
            mzCerrarTodosTooltips();
            mzMostrarFicha(codigoZona);
        });
    }

    function mzOpcionesTooltipZona() {
        return {
            sticky: true,
            opacity: 0.97,
            className: "mz-tooltip-zona",
            direction: "top",
            offset: [0, -8]
        };
    }

    function mzPintarOverlayRing(layer, z, geo, boundsMetro, boundsTodos) {
        var estilo = mzEstiloPoligonoZona(z.codigo, Number(z.venta_real) || 0);
        var poly = L.polygon(geo.ring, estilo).addTo(layer);

        poly.bindTooltip(
            "<strong>" + mzEsc(geo.short || z.nombre) + "</strong><br>" +
                "<span style='font-size:15px;font-weight:800;'>" + mzMoney(z.venta_real) + "</span><br>" +
                "<span style='font-size:11px;'>" + mzEsc(z.nombre) + "</span>",
            mzOpcionesTooltipZona()
        );
        mzRegistrarCapaZona(poly, z.codigo, "", "");
        mzBindZonaEvents(poly, z.codigo);

        geo.ring.forEach(function (p) {
            boundsTodos.push(p);
            if (geo.metro !== false) {
                boundsMetro.push(p);
            }
        });
    }

    function mzPintarMapaLimaDistritos(map, layer, zonas, ajustarVista, geojson, geoNorteChico) {
        mzLayerByZona = {};
        var porCodigo = {};
        (zonas || []).forEach(function (z) {
            porCodigo[z.codigo] = z;
        });

        var boundsMetro = [];
        var boundsTodos = [];
        var boundsNorteChico = [];

        var gj = L.geoJSON(geojson, {
            style: function (feature) {
                var props = feature.properties || {};
                var codigo = mzZonaDeDistrito(props.distrito, props.provincia);
                var nombreDist = props.distrito2 || props.distrito || "";
                if (!codigo || !porCodigo[codigo]) {
                    return {
                        color: "#dddddd",
                        weight: 1,
                        opacity: 0.8,
                        fillColor: "#eeeeee",
                        fillOpacity: 0.25
                    };
                }
                return mzEstiloPoligonoZona(codigo, mzVentaGeo("distritos", nombreDist));
            },
            onEachFeature: function (feature, lyr) {
                var props = feature.properties || {};
                var codigo = mzZonaDeDistrito(props.distrito, props.provincia);
                var z = codigo ? porCodigo[codigo] : null;
                var nombreDist = props.distrito2 || props.distrito || "";

                if (z) {
                    mzRegistrarCapaZona(lyr, codigo, "distritos", nombreDist);
                    lyr.bindTooltip(
                        mzTooltipGeoHtml(
                            nombreDist,
                            z.nombre,
                            mzVentaGeo("distritos", nombreDist),
                            z.venta_real
                        ),
                        mzOpcionesTooltipZona()
                    );
                    mzBindZonaEvents(lyr, codigo);
                }

                try {
                    var b = lyr.getBounds();
                    if (b && b.isValid()) {
                        boundsMetro.push(b.getSouthWest());
                        boundsMetro.push(b.getNorthEast());
                        boundsTodos.push(b.getSouthWest());
                        boundsTodos.push(b.getNorthEast());
                    }
                } catch (e) { /* ignore */ }
            }
        });
        gj.addTo(layer);

        // Norte Chico / provincias Lima fuera de metro (Barranca, Huaral, Huaura…)
        if (geoNorteChico) {
            var gjNc = L.geoJSON(geoNorteChico, {
                filter: function (feature) {
                    var props = feature.properties || {};
                    var codigo = mzZonaDeProvincia(props.provincia || props.NOMBPROV);
                    return !!(codigo && porCodigo[codigo]);
                },
                style: function (feature) {
                    var props = feature.properties || {};
                    var nombreProv = props.provincia || props.NOMBPROV || "";
                    var codigo = mzZonaDeProvincia(nombreProv);
                    return mzEstiloPoligonoZona(codigo, mzVentaGeo("provincias", nombreProv));
                },
                onEachFeature: function (feature, lyr) {
                    var props = feature.properties || {};
                    var nombreProv = props.provincia || props.NOMBPROV || "Provincia";
                    var codigo = mzZonaDeProvincia(nombreProv);
                    var z = codigo ? porCodigo[codigo] : null;
                    if (!z) {
                        return;
                    }
                    mzRegistrarCapaZona(lyr, codigo, "provincias", nombreProv);
                    lyr.bindTooltip(
                        mzTooltipGeoHtml(
                            nombreProv,
                            z.nombre,
                            mzVentaGeo("provincias", nombreProv),
                            z.venta_real
                        ),
                        mzOpcionesTooltipZona()
                    );
                    mzBindZonaEvents(lyr, codigo);
                    try {
                        var bn = lyr.getBounds();
                        if (bn && bn.isValid()) {
                            boundsNorteChico.push(bn.getSouthWest());
                            boundsNorteChico.push(bn.getNorthEast());
                            boundsTodos.push(bn.getSouthWest());
                            boundsTodos.push(bn.getNorthEast());
                        }
                    } catch (e2) { /* ignore */ }
                }
            });
            gjNc.addTo(layer);
        }

        // Gamarra (overlay aproximado sobre La Victoria)
        ["LIM_ECONOMICA"].forEach(function (cod) {
            var z = porCodigo[cod];
            var geo = MZ_GEO.lima[cod];
            if (z && geo && geo.ring) {
                mzPintarOverlayRing(layer, z, geo, boundsMetro, boundsTodos);
            }
        });

        // Vista inicial: metro + Norte Chico para que se vea pintado
        var boundsInicial = boundsMetro.concat(boundsNorteChico);
        if (!boundsInicial.length) {
            boundsInicial = boundsTodos;
        }

        if (ajustarVista) {
            mzGuardarBoundsVista("lima", boundsInicial);
            if (boundsInicial.length) {
                try {
                    map.fitBounds(boundsInicial, { padding: [24, 24], maxZoom: 11 });
                } catch (e3) { /* ignore */ }
            }
            mzYaAjustado.lima = true;
        } else {
            mzGuardarBoundsVista("lima", boundsInicial);
        }

        if (mzSeleccion) {
            mzResaltarSeleccionMapa();
            mzEnfocarZonaEnMapa(mzSeleccion);
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 80);
    }

    function mzPintarMapaPeruDepartamentos(map, layer, zonas, ajustarVista, geojson) {
        mzLayerByZona = {};
        var porCodigo = {};
        (zonas || []).forEach(function (z) {
            porCodigo[z.codigo] = z;
        });

        var boundsTodos = [];

        var gj = L.geoJSON(geojson, {
            filter: function (feature) {
                var props = feature.properties || {};
                var dep = mzNormNombre(props.NOMBDEP || props.nombdep || "");
                return dep !== "LIMA" && dep !== "CALLAO";
            },
            style: function (feature) {
                var props = feature.properties || {};
                var nombreDep = props.NOMBDEP || props.nombdep || "";
                var codigo = mzZonaDeDepartamento(nombreDep);
                if (!codigo || !porCodigo[codigo]) {
                    return {
                        color: "#dddddd",
                        weight: 1,
                        opacity: 0.8,
                        fillColor: "#eeeeee",
                        fillOpacity: 0.2
                    };
                }
                return mzEstiloPoligonoZona(codigo, mzVentaGeo("departamentos", nombreDep));
            },
            onEachFeature: function (feature, lyr) {
                var props = feature.properties || {};
                var nombreDep = props.NOMBDEP || props.nombdep || "";
                var codigo = mzZonaDeDepartamento(nombreDep);
                var z = codigo ? porCodigo[codigo] : null;

                if (z) {
                    mzRegistrarCapaZona(lyr, codigo, "departamentos", nombreDep);
                    lyr.bindTooltip(
                        mzTooltipGeoHtml(
                            nombreDep,
                            z.nombre,
                            mzVentaGeo("departamentos", nombreDep),
                            z.venta_real
                        ),
                        mzOpcionesTooltipZona()
                    );
                    mzBindZonaEvents(lyr, codigo);
                }

                try {
                    var b = lyr.getBounds();
                    if (b && b.isValid()) {
                        boundsTodos.push(b.getSouthWest());
                        boundsTodos.push(b.getNorthEast());
                    }
                } catch (e) { /* ignore */ }
            }
        });
        gj.addTo(layer);

        if (ajustarVista) {
            mzGuardarBoundsVista("peru", boundsTodos);
            if (boundsTodos.length) {
                try {
                    map.fitBounds(boundsTodos, { padding: [28, 28], maxZoom: 6 });
                } catch (e2) { /* ignore */ }
            }
            mzYaAjustado.peru = true;
        } else {
            mzGuardarBoundsVista("peru", boundsTodos);
        }

        if (mzSeleccion) {
            mzResaltarSeleccionMapa();
            mzEnfocarZonaEnMapa(mzSeleccion);
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 80);
    }

    function mzPintarMapa(vista, zonas, ajustarVista) {
        mzEnsureMaps();
        var map = vista === "peru" ? mzMapPeru : mzMapLima;
        var layer = vista === "peru" ? mzLayerPeru : mzLayerLima;

        if (!map || !layer) {
            return;
        }

        layer.clearLayers();
        mzLayerByZona = {};

        if (vista === "lima") {
            mzCargarGeoLimaCompleto()
                .done(function (pack) {
                    if (mzVista !== "lima") {
                        return;
                    }
                    layer.clearLayers();
                    mzPintarMapaLimaDistritos(
                        map,
                        layer,
                        zonas,
                        ajustarVista,
                        pack.lima,
                        pack.norte
                    );
                })
                .fail(function () {
                    mzPintarMapaRings(vista, zonas, ajustarVista, map, layer);
                });
            return;
        }

        if (vista === "peru") {
            mzCargarDepartamentosPeru()
                .done(function (geojson) {
                    if (mzVista !== "peru") {
                        return;
                    }
                    layer.clearLayers();
                    mzPintarMapaPeruDepartamentos(map, layer, zonas, ajustarVista, geojson);
                })
                .fail(function () {
                    mzPintarMapaRings(vista, zonas, ajustarVista, map, layer);
                });
            return;
        }

        mzPintarMapaRings(vista, zonas, ajustarVista, map, layer);
    }

    function mzPintarMapaRings(vista, zonas, ajustarVista, map, layer) {
        var geoSet = vista === "peru" ? MZ_GEO.peru : MZ_GEO.lima;
        var boundsMetro = [];
        var boundsTodos = [];

        mzOrdenZonas(vista, zonas).forEach(function (z) {
            var geo = geoSet[z.codigo];
            if (!geo || !geo.ring) {
                return;
            }
            mzPintarOverlayRing(layer, z, geo, boundsMetro, boundsTodos);
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
        var sinAtender = modo === "sin_atender";
        var p = mzPeriodo();
        var nombre = z ? z.nombre : "Zona";
        var colorZona = z ? z.color || "#3c8dbc" : "#3c8dbc";
        $("#mzModalTituloTipo").text(soloNuevos ? "Clientes nuevos" : (sinAtender ? "Clientes sin atender" : "Clientes con venta"));
        $("#mzModalZonaNombre").text(nombre);
        $("#mzModalHeader").css("background", soloNuevos ? "#00a65a" : (sinAtender ? "#f39c12" : colorZona));
        $("#mzModalColVendedor").text(sinAtender ? "Vendedor asignado" : "Vendedor");
        $("#mzModalColValor").text(sinAtender ? "Última compra" : "Venta S/");
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
                accion: soloNuevos ? "clientesNuevosZona" : (sinAtender ? "clientesSinAtenderZona" : "clientesVentaZona"),
                idZona: idZona,
                anio: p.anio,
                mes: p.mes,
                id_grupo_marca: mzGrupoMarca(),
                filtro_distribuidor: mzFiltroDistribuidor()
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
                $("#mzModalTotalVenta").text(sinAtender ? "—" : mzMoney(resp.total_venta));
                $("#mzModalTotalCli").text(mzFmt(resp.total_clientes));
                if (resp.zona && resp.zona.nombre) {
                    $("#mzModalZonaNombre").text(resp.zona.nombre);
                }
                var tip = soloNuevos
                    ? "primera venta del período · vendedor de esa venta"
                    : (sinAtender ? "con compra en los 2 años previos y sin venta válida en el período" : "máx. 500, nuevos primero · vendedores con venta");
                $("#mzModalResumen").html(sinAtender
                    ? "Período: <strong>" + mzEsc(String(p.mes + "/" + p.anio))
                        + "</strong>. <span id='mzModalTotalCli'>" + mzEsc(mzFmt(resp.total_clientes))
                        + "</span> clientes (" + tip + ")."
                    : "Período: <strong>" +
                        mzEsc(String(p.mes + "/" + p.anio)) +
                        "</strong>. Total: <strong id='mzModalTotalVenta'>" +
                        mzEsc(mzMoney(resp.total_venta)) +
                        "</strong> · <span id='mzModalTotalCli'>" +
                        mzEsc(mzFmt(resp.total_clientes)) +
                        "</span> clientes (" +
                        tip +
                        ").");
                var rows = resp.clientes || [];
                if (!rows.length) {
                    $("#mzTablaClientes tbody").html(
                        "<tr><td colspan='4' class='text-muted'>" +
                            (soloNuevos
                                ? "Ningún cliente nuevo en este período"
                                : (sinAtender ? "Todos los clientes de la cartera tuvieron venta en este período" : "Ningún cliente con venta en este período")) +
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
                    var vend = sinAtender
                        ? (c.cod_vendedor || "—") + (c.nombre_vendedor ? " — " + c.nombre_vendedor : "")
                        : soloNuevos
                        ? c.cod_vendedor || "—"
                        : c.codigos_vendedor || c.cod_vendedor || "—";
                    var valorFinal = sinAtender
                        ? (c.ultima_venta || "Sin venta previa")
                        : mzMoney(c.venta_real);
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
                        "<td" + (sinAtender ? " class='mz-vendedor-asignado'" : "") + "><code>" +
                        mzEsc(vend) +
                        "</code></td>" +
                        "<td class='text-right'><strong>" +
                        mzEsc(valorFinal) +
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

    function mzResaltarSeleccionMapa() {
        Object.keys(mzLayerByZona).forEach(function (cod) {
            (mzLayerByZona[cod] || []).forEach(function (ly) {
                if (ly && typeof ly.setStyle === "function") {
                    ly.setStyle(mzEstiloDesdeCapa(ly));
                }
            });
        });
        if (mzSeleccion && mzLayerByZona[mzSeleccion]) {
            (mzLayerByZona[mzSeleccion] || []).forEach(function (ly) {
                if (ly && typeof ly.bringToFront === "function") {
                    ly.bringToFront();
                }
            });
        }
    }

    function mzLimpiarSeleccion() {
        mzSeleccion = null;
        mzEscribirPeriodoUrl();
        $(".mz-treemap-cell").removeClass("mz-active");
        mzResaltarSeleccionMapa();
        mzRestaurarVistaMapa();
    }

    function mzMostrarFicha(codigo) {
        var z = mzPorCodigo[codigo];
        if (!z) {
            return;
        }
        var misma = mzSeleccion === codigo;
        mzSeleccion = codigo;
        mzEscribirPeriodoUrl();
        $(".mz-treemap-cell").removeClass("mz-active");
        $(".mz-treemap-cell[data-codigo='" + codigo + "']").addClass("mz-active");

        var zonas = mzZonasDesdeCache(mzCache[mzCacheKey(mzVista)]);
        if (mzLayerByZona[codigo] && mzLayerByZona[codigo].length) {
            mzResaltarSeleccionMapa();
            if (!misma) {
                mzEnfocarZonaEnMapa(codigo);
            }
            mzPintarTreemap(zonas);
        } else {
            mzPintarMapa(mzVista, zonas, false);
            mzPintarTreemap(zonas);
        }

        $("#mzFichaVacio").hide();
        $("#mzFichaDetalle").show();
        $("#mzFichaTitulo").text(z.nombre || codigo);
        $("#mzFichaHeader").css("background", z.color || "#3c8dbc");
        $("#mzFichaDesc").text(z.descripcion || "");
        $("#mzFichaVenta").text(mzMoney(z.venta_real));
        $("#mzFichaPromedioVenta")
            .attr(
                "title",
                "Promedio de los 12 meses completos anteriores al período seleccionado"
            )
            .html(
                "<strong>" + mzEsc(mzMoney(z.promedio_venta_mensual_12m)) + "</strong>"
                + "<br><small class='text-muted'>"
                + mzEsc(mzFechaCorta(z.promedio_venta_desde))
                + " – "
                + mzEsc(mzFechaCorta(z.promedio_venta_hasta))
                + "</small>"
            );
        $("#mzFichaVentaTotal12m")
            .attr(
                "title",
                "Venta acumulada del mismo período usado para calcular el promedio mensual"
            )
            .html("<strong>" + mzEsc(mzMoney(z.venta_total_12m)) + "</strong>");
        $("#mzFichaClientesVenta").text(
            mzFmt(z.clientes_con_venta != null ? z.clientes_con_venta : 0)
        );
        var modelosVenta = z.modelos_con_venta != null ? z.modelos_con_venta : 0;
        var coberturaModelos = z.cobertura_modelos_pct;
        $("#mzFichaModelosVenta")
            .attr("title", "Cartera activa: " + mzFmt(z.total_modelos_cartera != null ? z.total_modelos_cartera : 0) + " modelos")
            .html(
                mzEsc(mzFmt(modelosVenta))
                + (coberturaModelos !== null && coberturaModelos !== undefined
                    ? " <span class='label label-info'>" + mzEsc(Number(coberturaModelos).toLocaleString("es-PE", { minimumFractionDigits: 1, maximumFractionDigits: 1 })) + "% cartera</span>"
                    : "")
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
        $("#mzBtnFichaSinAtender").attr("data-id-zona", z.id).attr("data-codigo", z.codigo);

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
            mzAplicarGeoAsignacion(cached.geo_asignacion || null);
            mzAplicarVentasGeo(cached.ventas_geo || null);
            mzAplicarDatos(mzZonasDesdeCache(cached), false);
            mzPintarHistorico(cached.historico || null, mzVista);
            mzSetCarga("");
            if (mzSeleccion && mzPorCodigo[mzSeleccion]) {
                mzMostrarFicha(mzSeleccion);
            }
            return;
        }

        var p = mzPeriodo();
        var seq = ++mzCargaSeq;
        var grupo = mzGrupoMarca();
        mzSetCarga("Cargando…");
        $.post(
            "ajax/zonas-comerciales.ajax.php",
            {
                accion: "resumenMapa",
                vista: mzVista,
                anio: p.anio,
                mes: p.mes,
                id_grupo_marca: grupo,
                filtro_distribuidor: mzFiltroDistribuidor()
            },
            function (resp) {
                if (seq !== mzCargaSeq) {
                    return;
                }
                if (!resp || !resp.ok) {
                    mzSetCarga("Error al cargar");
                    return;
                }
                var zonas = resp.zonas || [];
                mzAplicarGeoAsignacion(resp.geo_asignacion || null);
                mzAplicarVentasGeo(resp.ventas_geo || null);
                mzCache[key] = {
                    zonas: zonas,
                    historico: resp.historico_12m || null,
                    geo_asignacion: resp.geo_asignacion || null,
                    ventas_geo: resp.ventas_geo || null
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
            if (seq !== mzCargaSeq) {
                return;
            }
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

    function mzAplicarEstiloToggleGrupo() {
        $("#mzToggleGrupo label").each(function () {
            var $lab = $(this);
            var checked = $lab.find("input").is(":checked");
            $lab.toggleClass("active btn-primary", checked);
            $lab.toggleClass("btn-default", !checked);
        });
    }

    function mzCambiarGrupo() {
        mzAplicarEstiloToggleGrupo();
        mzEscribirPeriodoUrl();
        mzCargarVista(mzVista, true);
    }

    $(document).on("change", "input[name='mzGrupoMarca']", function () {
        mzCambiarGrupo();
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

    $("#mzAnio, #mzMes, #mzFiltroDistribuidor").on("change", function () {
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

    $(document).on("click", ".btnMzVerSinAtender", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var idZona = $(this).attr("data-id-zona") || $(this).data("idZona");
        var codigo = $(this).attr("data-codigo") || $(this).data("codigo");
        mzAbrirClientesZona(idZona, codigo, "sin_atender");
    });

    mzAplicarPeriodoUrl();

    if ($("#mzMapLima").length) {
        if (typeof L === "undefined") {
            mzSetCarga("No se pudo cargar Leaflet");
            $("#mzMapLima").html(
                "<div class='alert alert-warning' style='margin:12px;'>No se cargó el mapa (Leaflet). Revisa conexión a internet/CDN.</div>"
            );
        }
        if (mzVista === "peru") {
            $("#mzVistaLima").hide();
            $("#mzVistaPeru").show();
            $("#mzToggleVista label").removeClass("active btn-primary").addClass("btn-default");
            $("#mzToggleVista label").has("input[value='peru']").removeClass("btn-default").addClass("active btn-primary");
            $("input[name='mzVista'][value='peru']").prop("checked", true);
        }
        mzCargarVista(mzVista, true);
    }
})();
