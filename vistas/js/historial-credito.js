(function () {
    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function fmtMonto(lista, monto) {
        if (monto === null || monto === undefined || monto === "") {
            return "—";
        }
        var simbolo = lista === "precio1" ? "$ " : "S/ ";
        return (
            simbolo +
            Number(monto || 0).toLocaleString("es-PE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    }

    function fmtFecha(fecha) {
        if (!fecha) {
            return "—";
        }
        var d = new Date(String(fecha).replace(" ", "T"));
        if (isNaN(d.getTime())) {
            return escapeHtml(fecha);
        }
        var pad = function (n) {
            return n < 10 ? "0" + n : String(n);
        };
        return (
            pad(d.getDate()) +
            "/" +
            pad(d.getMonth() + 1) +
            "/" +
            d.getFullYear() +
            " " +
            pad(d.getHours()) +
            ":" +
            pad(d.getMinutes())
        );
    }

    function renderSnap(row) {
        var tipo = String(row.tipo_accion || "").toUpperCase();
        if (tipo !== "APROBADO" && tipo !== "ANULADO") {
            return "";
        }

        var parts = [];
        if (row.categoria_codigo) {
            var colorCat = row.categoria_color || "#777777";
            parts.push(
                '<span class="hc-snap-item"><span class="hc-cat-sigla" style="background-color:' +
                    escapeHtml(colorCat) +
                    ';">' +
                    escapeHtml(String(row.categoria_codigo).toUpperCase()) +
                    '</span> <span class="hc-snap-nombre">' +
                    escapeHtml(row.categoria_nombre || "") +
                    "</span></span>"
            );
        }
        if (row.nombre_grupo || (row.cupo_modo === "grupo" && row.codigo_grupo)) {
            parts.push(
                '<span class="hc-snap-item"><i class="fa fa-sitemap"></i> <span class="hc-snap-nombre">' +
                    escapeHtml(row.nombre_grupo || row.codigo_grupo) +
                    "</span></span>"
            );
        }
        if (row.linea_referencia !== null && row.linea_referencia !== undefined && row.linea_referencia !== "") {
            parts.push(
                '<span class="hc-snap-item" title="' +
                    escapeHtml(row.etiqueta_linea || "Línea") +
                    '"><span class="hc-snap-nombre">Línea S/ ' +
                    Number(row.linea_referencia).toLocaleString("es-PE", { maximumFractionDigits: 0 }) +
                    "</span></span>"
            );
        }
        if (row.cupo_disponible !== null && row.cupo_disponible !== undefined && row.cupo_disponible !== "") {
            parts.push(
                '<span class="hc-snap-item"><span class="hc-snap-nombre">Disp. S/ ' +
                    Number(row.cupo_disponible).toLocaleString("es-PE", { maximumFractionDigits: 0 }) +
                    "</span></span>"
            );
        }
        if (row.deuda_actual !== null && row.deuda_actual !== undefined && row.deuda_actual !== "") {
            parts.push(
                '<span class="hc-snap-item"><span class="hc-snap-nombre">Deuda S/ ' +
                    Number(row.deuda_actual).toLocaleString("es-PE", { maximumFractionDigits: 0 }) +
                    "</span></span>"
            );
        }
        if (row.utilizacion_pct !== null && row.utilizacion_pct !== undefined && row.utilizacion_pct !== "") {
            parts.push(
                '<span class="hc-snap-item"><span class="hc-snap-nombre">Util. ' +
                    Number(row.utilizacion_pct).toLocaleString("es-PE", { maximumFractionDigits: 0 }) +
                    "%</span></span>"
            );
        }
        if (row.score_riesgo !== null && row.score_riesgo !== undefined && row.score_riesgo !== "") {
            var sr = Number(row.score_riesgo);
            var srCls = "danger";
            if (sr >= 90) {
                srCls = "success";
            } else if (sr >= 80) {
                srCls = "primary";
            } else if (sr >= 70) {
                srCls = "info";
            } else if (sr >= 60) {
                srCls = "warning";
            }
            parts.push(
                '<span class="hc-snap-item hc-riesgo hc-riesgo--' +
                    srCls +
                    '"><span class="hc-snap-nombre">Riesgo ' +
                    sr.toLocaleString("es-PE", { maximumFractionDigits: 0 }) +
                    "</span></span>"
            );
        }

        if (!parts.length) {
            return "";
        }

        return '<div class="hc-snap">' + parts.join("") + "</div>";
    }

    function renderFila(row) {
        var detalle = "";
        var esAprobado = String(row.tipo_accion || "").toUpperCase() === "APROBADO";

        if (row.motivo_etiqueta) {
            detalle =
                '<div class="hc-detalle-texto"><strong>' +
                escapeHtml(row.motivo_etiqueta) +
                "</strong>" +
                (row.comentario
                    ? '<div class="text-muted">' + escapeHtml(row.comentario) + "</div>"
                    : "") +
                "</div>";
        } else if (row.comentario) {
            detalle = '<div class="hc-detalle-texto">' + escapeHtml(row.comentario) + "</div>";
        } else if (row.detalle) {
            detalle = '<div class="hc-detalle-texto">' + escapeHtml(row.detalle) + "</div>";
        } else if (!esAprobado) {
            detalle = '<span class="text-muted">—</span>';
        }

        detalle += renderSnap(row);

        return (
            "<tr>" +
            '<td class="text-center hc-fecha-cell"><span class="hc-fecha">' +
            fmtFecha(row.fecha) +
            "</span></td>" +
            '<td><span class="label label-' +
            escapeHtml(row.tipo_clase || "default") +
            '">' +
            escapeHtml(row.tipo_etiqueta || row.tipo_accion) +
            "</span></td>" +
            "<td><strong>" +
            escapeHtml(row.codigo_pedido) +
            "</strong></td>" +
            '<td><span class="hc-cli-cod">' +
            escapeHtml(row.codigo_cliente) +
            "</span> " +
            escapeHtml(row.cliente_nombre || "") +
            "</td>" +
            '<td class="text-right hc-monto"><strong>' +
            fmtMonto(row.pedido_lista, row.pedido_total) +
            "</strong></td>" +
            '<td class="hc-detalle">' +
            detalle +
            "</td>" +
            "<td>" +
            escapeHtml(row.usuario_nombre || "") +
            "</td>" +
            "</tr>"
        );
    }

    function actualizarKpis(resumen) {
        resumen = resumen || {};
        $("#hcKpiAprobado").text(resumen.APROBADO || 0);
        $("#hcKpiObjecion").text(resumen.OBJECION || 0);
        $("#hcKpiCerrada").text(resumen.OBJECION_CERRADA || 0);
        $("#hcKpiAnulado").text(resumen.ANULADO || 0);
    }

    var hcColaXhr = null;
    var hcIgnorarCambioVendedor = false;

    function hcVendedorActual() {
        var $sel = $("#hcFiltroVendedor");
        if (!$sel.length) {
            return "";
        }
        return String($sel.val() || "");
    }

    function hcSincronizarStubVendedor(vendedor) {
        var $stub = $("#hcDdStub #ddFiltroVendedor");
        if ($stub.length) {
            $stub.val(vendedor || "");
        }
    }

    function hcActualizarUrlVendedor(vendedor) {
        if (!(window.history && window.history.replaceState)) {
            return;
        }
        var params = ["ruta=historial-credito"];
        if (vendedor) {
            params.push("vendedor=" + encodeURIComponent(vendedor));
        }
        if ($("#hcTabDashboard").hasClass("active")) {
            params.push("tab=dashboard");
        } else if ($("#hcTabMovimientos").hasClass("active")) {
            params.push("tab=movimientos");
        }
        window.history.replaceState(
            { vendedor: vendedor || "" },
            "",
            "index.php?" + params.join("&")
        );
    }

    function recargarCola() {
        if (!$("#hcColaWrap").length) {
            return;
        }

        if (hcColaXhr && hcColaXhr.readyState !== 4) {
            hcColaXhr.abort();
        }

        var vendedor = hcVendedorActual();
        hcSincronizarStubVendedor(vendedor);

        $("#hcColaWrap").addClass("hc-cola--loading");
        $("#btnHcActualizarCola, #btnHcLimpiarVendedor").prop("disabled", true);
        var $sel = $("#hcFiltroVendedor");
        $sel.prop("disabled", true);
        if ($sel.data("selectpicker")) {
            $sel.selectpicker("refresh");
        }

        hcColaXhr = $.ajax({
            url: "ajax/dashboard-decisiones/cola-credito.ajax.php",
            method: "POST",
            dataType: "json",
            data: { limite: 80, vendedor: vendedor },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    return;
                }
                $("#hcColaWrap").html(resp.html);
                var r = resp.resumen || {};
                $("#hcTabBadgeCola").text(r.generados || 0);
                hcActualizarUrlVendedor(resp.vendedor || vendedor);
            })
            .always(function () {
                $("#hcColaWrap").removeClass("hc-cola--loading");
                $("#btnHcActualizarCola, #btnHcLimpiarVendedor").prop("disabled", false);
                $sel.prop("disabled", false);
                if ($sel.data("selectpicker")) {
                    $sel.selectpicker("refresh");
                }
                hcColaXhr = null;
            });
    }

    function cargarHistorial() {
        var $body = $("#hcTablaBody");
        $body.html(
            '<tr><td colspan="7" class="text-center text-muted">' +
                '<i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>'
        );

        $.ajax({
            url: "ajax/dashboard-decisiones/historial-credito.ajax.php",
            method: "POST",
            dataType: "json",
            data: {
                fecha_desde: $("#hcFechaDesde").val(),
                fecha_hasta: $("#hcFechaHasta").val(),
                tipo_accion: $("#hcTipo").val(),
                q: $("#hcBuscar").val(),
            },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    $body.html(
                        '<tr><td colspan="7" class="text-center text-danger">' +
                            escapeHtml((resp && resp.msg) || "No se pudo cargar el historial.") +
                            "</td></tr>"
                    );
                    return;
                }

                actualizarKpis(resp.resumen);

                var filas = resp.filas || [];
                if (!filas.length) {
                    $body.html(
                        '<tr class="hc-empty"><td colspan="7" class="text-center text-muted">' +
                            "No hay movimientos en el rango seleccionado.</td></tr>"
                    );
                    return;
                }

                var html = "";
                filas.forEach(function (row) {
                    html += renderFila(row);
                });
                $body.html(html);
            })
            .fail(function () {
                $body.html(
                    '<tr><td colspan="7" class="text-center text-danger">' +
                        "Error de comunicación con el servidor.</td></tr>"
                );
            });
    }

    function urlAccion(settings) {
        return String((settings && settings.url) || "");
    }

    function dataAccion(settings) {
        var data = (settings && settings.data) || "";
        if (typeof data === "object" && data !== null) {
            return data.accion || "";
        }
        var m = String(data).match(/(?:^|&)accion=([^&]*)/);
        return m ? decodeURIComponent(m[1]) : "";
    }

    $("#btnHcBuscar").on("click", cargarHistorial);

    $("#btnHcLimpiar").on("click", function () {
        var hoy = new Date();
        var desde = new Date();
        desde.setDate(hoy.getDate() - 30);
        var pad = function (n) {
            return n < 10 ? "0" + n : String(n);
        };
        var toYmd = function (d) {
            return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
        };
        $("#hcFechaDesde").val(toYmd(desde));
        $("#hcFechaHasta").val(toYmd(hoy));
        $("#hcTipo").val("");
        $("#hcBuscar").val("");
        cargarHistorial();
    });

    $("#hcBuscar").on("keydown", function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            cargarHistorial();
        }
    });

    $(document).on("click", "#btnHcActualizarCola", function () {
        recargarCola();
    });

    $(document).on("click", "#btnHcLimpiarVendedor", function () {
        hcIgnorarCambioVendedor = true;
        var $sel = $("#hcFiltroVendedor");
        if ($sel.data("selectpicker")) {
            $sel.selectpicker("val", "");
        } else {
            $sel.val("");
        }
        hcIgnorarCambioVendedor = false;
        recargarCola();
    });

    $(function () {
        var $sel = $("#hcFiltroVendedor");
        if ($sel.length) {
            $sel.selectpicker("refresh");
            hcSincronizarStubVendedor(hcVendedorActual());
            $sel.on("changed.bs.select change", function () {
                if (hcIgnorarCambioVendedor) {
                    return;
                }
                recargarCola();
            });
        }
    });

    $(document).ajaxComplete(function (_e, xhr, settings) {
        var url = urlAccion(settings);

        if (
            url.indexOf("aprobar-pedido.ajax.php") !== -1 ||
            url.indexOf("anular-pedido.ajax.php") !== -1
        ) {
            var respAccion = null;
            try {
                respAccion = xhr.responseJSON || JSON.parse(xhr.responseText || "{}");
            } catch (err) {
                respAccion = null;
            }
            if (respAccion && respAccion.ok) {
                recargarCola();
                if ($("#hcTabMovimientos").hasClass("active")) {
                    cargarHistorial();
                }
            }
            return;
        }

        if (url.indexOf("refrescar-dashboard.ajax.php") !== -1) {
            recargarCola();
            if ($("#hcTabMovimientos").hasClass("active")) {
                cargarHistorial();
            }
            return;
        }

        if (url.indexOf("decisiones-credito.ajax.php") === -1) {
            return;
        }

        var accion = String(dataAccion(settings) || "").toLowerCase();
        if (
            accion === "registrar" ||
            accion === "solicitar" ||
            accion === "cerrar_decision" ||
            accion === "resolver_solicitud"
        ) {
            recargarCola();
            if ($("#hcTabMovimientos").hasClass("active")) {
                cargarHistorial();
            }
        }
    });

    var hcSwalOrig = window.swal;
    if (typeof hcSwalOrig === "function") {
        window.swal = function () {
            var args = Array.prototype.slice.call(arguments);
            if (args.length >= 2 && typeof args[1] === "string") {
                args[1] = args[1]
                    .replace(/El dashboard se actualizó\./gi, "La cola se actualizó.")
                    .replace(/el dashboard fue actualizado\./gi, "la cola se actualizó.")
                    .replace(/y el dashboard fue actualizado\./gi, "y la cola se actualizó.");
            }
            return hcSwalOrig.apply(this, args);
        };
    }

    // Evitar dropdown fantasma de selectpicker sobre el stub de vendedor
    $(function () {
        function limpiarStubFiltro() {
            var $sel = $("#hcDdStub #ddFiltroVendedor");
            if (!$sel.length) {
                return;
            }
            try {
                if ($sel.data("selectpicker")) {
                    $sel.selectpicker("destroy");
                }
            } catch (err) {}
            $("#hcDdStub .bootstrap-select").remove();
            $sel.next(".bootstrap-select").remove();
        }

        limpiarStubFiltro();
        setTimeout(limpiarStubFiltro, 0);
        setTimeout(limpiarStubFiltro, 200);
    });

    /* ========== Dashboard gestión ========== */
    var hcDashCharts = { serie: null, embudo: null, motivos: null, hora: null, dow: null };
    var hcDashCargado = false;

    var HC_PASTEL = {
        aprobado: "#7dcea0",
        aprobadoFill: "rgba(125,206,160,0.35)",
        objecion: "#f5b041",
        objecionFill: "rgba(245,176,65,0.35)",
        anulado: "#ec7063",
        anuladoFill: "rgba(236,112,99,0.25)",
        azul: "#85c1e9",
        azulFill: "rgba(133,193,233,0.35)",
        morado: "#bb8fce",
        moradoFill: "rgba(187,143,206,0.35)",
        verdeAgua: "#76d7c4",
        embudo: ["#a9dfbf", "#fad7a0", "#aed6f1", "#f5b7b1"],
        severidad: {
            critica: "#f5b7b1",
            alta: "#fad7a0",
            media: "#aed6f1",
            baja: "#a9dfbf",
        },
    };

    function fmtSoles(n) {
        if (n === null || n === undefined || n === "") {
            return "—";
        }
        return (
            "S/ " +
            Number(n || 0).toLocaleString("es-PE", {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            })
        );
    }

    function fmtPct(n) {
        if (n === null || n === undefined || n === "") {
            return "—";
        }
        return Number(n).toLocaleString("es-PE", { maximumFractionDigits: 1 }) + "%";
    }

    function fmtRelativo(fecha) {
        if (!fecha) {
            return "—";
        }
        var d = new Date(String(fecha).replace(" ", "T"));
        if (isNaN(d.getTime())) {
            return "—";
        }
        var mins = Math.max(0, Math.round((Date.now() - d.getTime()) / 60000));
        if (mins < 1) {
            return "ahora";
        }
        if (mins < 60) {
            return mins + " min";
        }
        if (mins < 1440) {
            return Math.floor(mins / 60) + " h";
        }
        return Math.floor(mins / 1440) + " d";
    }

    function fmtDeltaHtml(val) {
        if (val === null || val === undefined || val === "") {
            return "";
        }
        var n = Number(val);
        var cls = "hc-dash-delta--flat";
        var icon = "→";
        if (n > 0) {
            cls = "hc-dash-delta--up";
            icon = "▲";
        } else if (n < 0) {
            cls = "hc-dash-delta--down";
            icon = "▼";
        }
        return (
            '<span class="hc-dash-delta ' +
            cls +
            '">' +
            icon +
            " " +
            Math.abs(n).toLocaleString("es-PE", { maximumFractionDigits: 1 }) +
            "%</span>"
        );
    }

    function fmtHorasResolucion(h) {
        if (h === null || h === undefined || h === "") {
            return "—";
        }
        h = Number(h);
        if (h >= 24) {
            return (h / 24).toLocaleString("es-PE", { maximumFractionDigits: 1 }) + " d";
        }
        return h.toLocaleString("es-PE", { maximumFractionDigits: 1 }) + " h";
    }

    function destruirDashChart(key) {
        if (hcDashCharts[key] && typeof hcDashCharts[key].destroy === "function") {
            hcDashCharts[key].destroy();
        }
        hcDashCharts[key] = null;
    }

    function hcDashVendedor() {
        var $sel = $("#hcDashVendedor");
        return $sel.length ? String($sel.val() || "") : hcVendedorActual();
    }

    function hcActualizarUrlTab() {
        if (!(window.history && window.history.replaceState)) {
            return;
        }
        var params = ["ruta=historial-credito"];
        var vend = hcVendedorActual() || hcDashVendedor();
        if (vend) {
            params.push("vendedor=" + encodeURIComponent(vend));
        }
        if ($("#hcTabDashboard").hasClass("active")) {
            params.push("tab=dashboard");
        } else if ($("#hcTabMovimientos").hasClass("active")) {
            params.push("tab=movimientos");
        }
        window.history.replaceState({}, "", "index.php?" + params.join("&"));
    }

    function renderDashKpis(kpis, comparacion) {
        kpis = kpis || {};
        comparacion = comparacion || {};
        $("#hcDashKpiTasaAprob").text(fmtPct(kpis.tasa_aprobacion_pct));
        $("#hcDashKpiTasaObj").text(fmtPct(kpis.tasa_objecion_pct));
        $("#hcDashKpiTasaAnul").text(fmtPct(kpis.tasa_anulacion_pct));
        $("#hcDashKpiMontoAprob").text(fmtSoles(kpis.monto_aprobado_soles));
        $("#hcDashKpiMontoObj").text(fmtSoles(kpis.monto_objetado_soles));
        $("#hcDashKpiAnulN").text((kpis.conteo_anulado || 0) + " anulado(s)");
        $("#hcDashKpiRiesgo").text(fmtSoles(kpis.monto_riesgo_soles));
        $("#hcDashKpiRiesgoN").text(
            (kpis.objeciones_vigentes || 0) + " objeción(es) vigente(s)"
        );
        $("#hcDashKpiTiempo").text(fmtHorasResolucion(kpis.tiempo_resolucion_horas));
        $("#hcDashKpiCola").text(kpis.cola_generados || 0);
        $("#hcDashKpiDecisiones").text(kpis.decisiones_total || 0);
        var deltaDec = comparacion.decisiones_delta_pct;
        var breakdown =
            (kpis.conteo_aprobado || 0) +
            " apr · " +
            (kpis.conteo_objecion || 0) +
            " obj · " +
            (kpis.conteo_anulado || 0) +
            " anul";
        if (deltaDec !== null && deltaDec !== undefined) {
            $("#hcDashKpiDecisionesDelta").html(
                breakdown + " · vs ant. " + fmtDeltaHtml(deltaDec)
            );
        } else {
            $("#hcDashKpiDecisionesDelta").text(breakdown);
        }
    }

    function renderPulso(pulso, kpis) {
        pulso = pulso || {};
        kpis = kpis || {};
        $("#hcDashPulsoHoy").text(pulso.acciones_hoy || 0);
        $("#hcDashPulsoSem").text(pulso.acciones_semana || 0);
        $("#hcDashPulsoTeam").text(pulso.analistas_activos_hoy || 0);
        $("#hcDashPulsoRitmo").text(
            kpis.throughput_dia !== null && kpis.throughput_dia !== undefined
                ? Number(kpis.throughput_dia).toLocaleString("es-PE", { maximumFractionDigits: 1 })
                : "—"
        );
        $("#hcDashPulsoSla").text(
            kpis.pct_sla_48h !== null && kpis.pct_sla_48h !== undefined
                ? fmtPct(kpis.pct_sla_48h)
                : "—"
        );

        var $dot = $("#hcDashPulsoDot");
        var activo = !!pulso.equipo_activo;
        $dot.removeClass("hc-dash-pulso-dot--activo hc-dash-pulso-dot--inactivo");
        $dot.addClass(activo ? "hc-dash-pulso-dot--activo" : "hc-dash-pulso-dot--inactivo");

        if (activo) {
            $("#hcDashPulsoTexto").text("Equipo trabajando — hay gestión reciente de crédito");
        } else if ((pulso.acciones_hoy || 0) === 0) {
            $("#hcDashPulsoTexto").text("Sin acciones hoy — revisar cola pendiente");
        } else {
            $("#hcDashPulsoTexto").text("Actividad registrada — última gestión hace un rato");
        }

        if (pulso.minutos_desde_ultima !== null && pulso.minutos_desde_ultima !== undefined) {
            var rel = fmtRelativo(pulso.ultima_accion);
            $("#hcDashPulsoUltima").text(rel);
            $("#hcDashPulsoUltimaSub").text(fmtFecha(pulso.ultima_accion));
        } else {
            $("#hcDashPulsoUltima").text("—");
            $("#hcDashPulsoUltimaSub").text("sin registros");
        }
    }

    function renderSalud(colaSalud, comparacion) {
        colaSalud = colaSalud || {};
        comparacion = comparacion || {};
        $("#hcDashSaludCola24").text(colaSalud.sin_atender_24h || 0);
        $("#hcDashSaludColaMonto").text(
            "monto cola " + fmtSoles(colaSalud.monto_soles || 0)
        );
        $("#hcDashSaludEdad").text(
            (colaSalud.dias_promedio || 0).toLocaleString("es-PE", { maximumFractionDigits: 1 }) + " d"
        );
        $("#hcDashSaludEdadMax").text("máx. " + (colaSalud.dias_max || 0) + " días en cola");

        var dDec = comparacion.decisiones_delta_pct;
        $("#hcDashSaludDelta").html(
            dDec !== null && dDec !== undefined ? fmtDeltaHtml(dDec) : "—"
        );
        if (comparacion.periodo_anterior) {
            $("#hcDashSaludDeltaSub").text(
                "decisiones vs " +
                    comparacion.periodo_anterior.desde +
                    " → " +
                    comparacion.periodo_anterior.hasta
            );
        } else {
            $("#hcDashSaludDeltaSub").text("sin período anterior");
        }

        var dAp = comparacion.aprobados_delta_pct;
        $("#hcDashSaludDeltaAprob").html(
            dAp !== null && dAp !== undefined ? fmtDeltaHtml(dAp) : "—"
        );
        var dMonto = comparacion.monto_aprobado_delta_pct;
        if (dMonto !== null && dMonto !== undefined) {
            $("#hcDashSaludDeltaAprobSub").html("monto aprob. " + fmtDeltaHtml(dMonto));
        } else {
            $("#hcDashSaludDeltaAprobSub").text("aprobados");
        }
    }

    function renderChartSerie(serie) {
        var canvas = document.getElementById("hcChartSerie");
        var empty = document.getElementById("hcChartSerieEmpty");
        destruirDashChart("serie");

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        serie = serie || [];
        if (!serie.length) {
            canvas.style.display = "none";
            if (empty) {
                empty.style.display = "block";
            }
            return;
        }

        canvas.style.display = "block";
        if (empty) {
            empty.style.display = "none";
        }

        var labels = serie.map(function (d) {
            var p = String(d.fecha || "").split("-");
            return p.length === 3 ? p[2] + "/" + p[1] : d.fecha;
        });

        hcDashCharts.serie = new Chart(canvas.getContext("2d"), {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Aprobados",
                        data: serie.map(function (d) {
                            return d.APROBADO || 0;
                        }),
                        borderColor: HC_PASTEL.aprobado,
                        backgroundColor: HC_PASTEL.aprobadoFill,
                        tension: 0.3,
                        fill: true,
                        yAxisID: "y",
                    },
                    {
                        label: "Objeciones",
                        data: serie.map(function (d) {
                            return d.OBJECION || 0;
                        }),
                        borderColor: HC_PASTEL.objecion,
                        backgroundColor: HC_PASTEL.objecionFill,
                        tension: 0.3,
                        fill: true,
                        yAxisID: "y",
                    },
                    {
                        label: "Anulados",
                        data: serie.map(function (d) {
                            return d.ANULADO || 0;
                        }),
                        borderColor: HC_PASTEL.anulado,
                        backgroundColor: HC_PASTEL.anuladoFill,
                        tension: 0.3,
                        fill: false,
                        yAxisID: "y",
                    },
                    {
                        label: "Monto aprob. S/",
                        data: serie.map(function (d) {
                            return d.monto_aprobado || 0;
                        }),
                        borderColor: HC_PASTEL.azul,
                        backgroundColor: "transparent",
                        borderDash: [4, 4],
                        tension: 0.2,
                        fill: false,
                        yAxisID: "y1",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: { position: "bottom" },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: "Cantidad" },
                        ticks: { precision: 0 },
                    },
                    y1: {
                        beginAtZero: true,
                        position: "right",
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: "S/" },
                    },
                },
            },
        });
    }

    function renderChartEmbudo(embudo) {
        var canvas = document.getElementById("hcChartEmbudo");
        var empty = document.getElementById("hcChartEmbudoEmpty");
        destruirDashChart("embudo");

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        embudo = embudo || {};
        var labels = ["Aprobados", "Objeciones", "Obj. cerradas", "Anulados"];
        var data = [
            embudo.APROBADO || 0,
            embudo.OBJECION || 0,
            embudo.OBJECION_CERRADA || 0,
            embudo.ANULADO || 0,
        ];
        var total = data.reduce(function (a, b) {
            return a + b;
        }, 0);

        if (total <= 0) {
            canvas.style.display = "none";
            if (empty) {
                empty.style.display = "block";
            }
            return;
        }

        canvas.style.display = "block";
        if (empty) {
            empty.style.display = "none";
        }

        hcDashCharts.embudo = new Chart(canvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data,
                        backgroundColor: HC_PASTEL.embudo,
                        borderColor: "#fff",
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom" },
                },
            },
        });
    }

    function renderChartMotivos(motivos) {
        var canvas = document.getElementById("hcChartMotivos");
        var empty = document.getElementById("hcChartMotivosEmpty");
        destruirDashChart("motivos");

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        motivos = motivos || [];
        if (!motivos.length) {
            canvas.style.display = "none";
            if (empty) {
                empty.style.display = "block";
            }
            return;
        }

        canvas.style.display = "block";
        if (empty) {
            empty.style.display = "none";
        }

        var colores = HC_PASTEL.severidad;

        hcDashCharts.motivos = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: motivos.map(function (m) {
                    return m.etiqueta || m.codigo;
                }),
                datasets: [
                    {
                        label: "Objeciones",
                        data: motivos.map(function (m) {
                            return m.total || 0;
                        }),
                        backgroundColor: motivos.map(function (m) {
                            return colores[m.severidad] || "#95a5a6";
                        }),
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (ctx) {
                                var m = motivos[ctx.dataIndex];
                                if (!m) {
                                    return "";
                                }
                                return (
                                    m.pct +
                                    "% · " +
                                    fmtSoles(m.monto_soles)
                                );
                            },
                        },
                    },
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }

    function renderTablaAnalistas(filas) {
        var $body = $("#hcTablaAnalistasBody");
        filas = filas || [];
        if (!filas.length) {
            $body.html(
                '<tr><td colspan="5" class="text-center text-muted">Sin acciones en el período.</td></tr>'
            );
            return;
        }
        var html = "";
        filas.forEach(function (r) {
            html +=
                "<tr>" +
                "<td><strong>" +
                escapeHtml(r.usuario_nombre) +
                "</strong></td>" +
                '<td class="text-center">' +
                (r.aprobados || 0) +
                "</td>" +
                '<td class="text-center">' +
                (r.objeciones || 0) +
                "</td>" +
                '<td class="text-center">' +
                fmtPct(r.pct_aprobacion) +
                "</td>" +
                '<td class="text-right">' +
                fmtSoles(r.monto_aprobado_soles) +
                "</td>" +
                "</tr>";
        });
        $body.html(html);
    }

    function renderTablaClientes(filas) {
        var $body = $("#hcTablaClientesBody");
        filas = filas || [];
        if (!filas.length) {
            $body.html(
                '<tr><td colspan="4" class="text-center text-muted">Sin clientes objetados en el período.</td></tr>'
            );
            return;
        }
        var html = "";
        filas.forEach(function (r) {
            html +=
                "<tr>" +
                "<td><span class=\"hc-cli-cod\">" +
                escapeHtml(r.codigo_cliente) +
                "</span> " +
                escapeHtml(r.cliente_nombre || "") +
                "</td>" +
                '<td class="text-center"><strong>' +
                (r.objeciones || 0) +
                "</strong></td>" +
                '<td class="text-right">' +
                fmtSoles(r.monto_soles) +
                "</td>" +
                "<td>" +
                escapeHtml(r.ultimo_motivo || "—") +
                "</td>" +
                "</tr>";
        });
        $body.html(html);
    }

    function clsDias(n) {
        if (n >= 7) {
            return "hc-dash-dias hc-dash-dias--alta";
        }
        if (n >= 4) {
            return "hc-dash-dias hc-dash-dias--media";
        }
        return "hc-dash-dias hc-dash-dias--baja";
    }

    function renderTablaAbiertas(filas, diasMin) {
        var $body = $("#hcTablaAbiertasBody");
        $("#hcDashAbiertasSub").text("≥ " + (diasMin || 3) + " días");
        filas = filas || [];
        if (!filas.length) {
            $body.html(
                '<tr><td colspan="7" class="text-center text-muted">No hay objeciones abiertas con esa antigüedad.</td></tr>'
            );
            return;
        }
        var html = "";
        filas.forEach(function (r) {
            var monto =
                r.pedido_lista === "precio1"
                    ? "$ " +
                      Number(r.pedido_total || 0).toLocaleString("es-PE", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      })
                    : fmtSoles(r.monto_soles);
            html +=
                "<tr>" +
                '<td class="text-center"><span class="' +
                clsDias(r.dias_abierta || 0) +
                '">' +
                (r.dias_abierta || 0) +
                "</span></td>" +
                "<td><strong>" +
                escapeHtml(r.codigo_pedido) +
                "</strong></td>" +
                "<td><span class=\"hc-cli-cod\">" +
                escapeHtml(r.codigo_cliente) +
                "</span> " +
                escapeHtml(r.cliente_nombre || "") +
                "</td>" +
                "<td>" +
                escapeHtml(r.motivo_etiqueta || "—") +
                "</td>" +
                '<td class="text-right">' +
                monto +
                "</td>" +
                "<td>" +
                escapeHtml(r.usuario_nombre || "") +
                "</td>" +
                '<td class="text-center hc-fecha-cell"><span class="hc-fecha">' +
                fmtFecha(r.fecha_registro) +
                "</span></td>" +
                "</tr>";
        });
        $body.html(html);
    }

    function renderChartHora(filas) {
        var canvas = document.getElementById("hcChartHora");
        var empty = document.getElementById("hcChartHoraEmpty");
        destruirDashChart("hora");

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        filas = filas || [];
        var mapa = {};
        var h;
        for (h = 7; h <= 20; h++) {
            mapa[h] = 0;
        }
        filas.forEach(function (r) {
            var hora = Number(r.hora);
            if (hora >= 0 && hora <= 23) {
                mapa[hora] = (mapa[hora] || 0) + (r.total || 0);
            }
        });

        var labels = [];
        var data = [];
        Object.keys(mapa)
            .sort(function (a, b) {
                return Number(a) - Number(b);
            })
            .forEach(function (k) {
                if (Number(k) >= 7 && Number(k) <= 20) {
                    labels.push(k + ":00");
                    data.push(mapa[k]);
                }
            });

        var total = data.reduce(function (a, b) {
            return a + b;
        }, 0);
        if (total <= 0) {
            canvas.style.display = "none";
            if (empty) {
                empty.style.display = "block";
            }
            return;
        }

        canvas.style.display = "block";
        if (empty) {
            empty.style.display = "none";
        }

        hcDashCharts.hora = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Acciones",
                        data: data,
                        backgroundColor: HC_PASTEL.azulFill,
                        borderColor: HC_PASTEL.azul,
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }

    function renderChartDow(filas) {
        var canvas = document.getElementById("hcChartDow");
        var empty = document.getElementById("hcChartDowEmpty");
        destruirDashChart("dow");

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        filas = filas || [];
        var orden = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
        var mapa = { Lun: 0, Mar: 0, Mié: 0, Jue: 0, Vie: 0, Sáb: 0, Dom: 0 };
        filas.forEach(function (r) {
            if (r.label && mapa.hasOwnProperty(r.label)) {
                mapa[r.label] = r.total || 0;
            }
        });

        var data = orden.map(function (d) {
            return mapa[d];
        });
        var total = data.reduce(function (a, b) {
            return a + b;
        }, 0);

        if (total <= 0) {
            canvas.style.display = "none";
            if (empty) {
                empty.style.display = "block";
            }
            return;
        }

        canvas.style.display = "block";
        if (empty) {
            empty.style.display = "none";
        }

        hcDashCharts.dow = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: orden,
                datasets: [
                    {
                        label: "Acciones",
                        data: data,
                        backgroundColor: HC_PASTEL.moradoFill,
                        borderColor: HC_PASTEL.morado,
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }

    function renderTablaUltimas(filas) {
        var $body = $("#hcTablaUltimasBody");
        filas = filas || [];
        if (!filas.length) {
            $body.html(
                '<tr><td colspan="7" class="text-center text-muted">Sin gestiones en el período.</td></tr>'
            );
            return;
        }
        var html = "";
        filas.forEach(function (r) {
            var tipo = String(r.tipo_accion || "").toUpperCase();
            var hace = fmtRelativo(r.fecha);
            var haceCls =
                hace === "ahora" || (hace.indexOf("min") !== -1 && parseInt(hace, 10) < 60)
                    ? "hc-dash-hace hc-dash-hace--reciente"
                    : "hc-dash-hace";
            var detalle = r.motivo_etiqueta || "";
            html +=
                "<tr>" +
                '<td class="text-center"><span class="' +
                haceCls +
                '">' +
                escapeHtml(hace) +
                "</span></td>" +
                '<td><span class="hc-dash-tipo hc-dash-tipo--' +
                escapeHtml(tipo || "default") +
                '">' +
                escapeHtml(r.tipo_etiqueta || tipo) +
                "</span></td>" +
                "<td><strong>" +
                escapeHtml(r.codigo_pedido) +
                "</strong></td>" +
                '<td><span class="hc-cli-cod">' +
                escapeHtml(r.codigo_cliente) +
                "</span> " +
                escapeHtml(r.cliente_nombre || "") +
                "</td>" +
                '<td class="text-right">' +
                fmtMonto(r.pedido_lista, r.pedido_total) +
                "</td>" +
                "<td>" +
                escapeHtml(detalle || "—") +
                "</td>" +
                "<td>" +
                escapeHtml(r.usuario_nombre || "") +
                "</td>" +
                "</tr>";
        });
        $body.html(html);
    }

    function renderDashboard(resp) {
        renderPulso(resp.pulso, resp.kpis);
        renderSalud(resp.cola_salud, resp.comparacion);
        renderDashKpis(resp.kpis, resp.comparacion);
        renderChartSerie(resp.serie_diaria);
        renderChartEmbudo(resp.embudo);
        renderChartMotivos(resp.motivos);
        renderChartHora(resp.actividad_hora);
        renderChartDow(resp.actividad_dow);
        renderTablaUltimas(resp.ultimas_gestiones);
        renderTablaAnalistas(resp.analistas);
        renderTablaClientes(resp.clientes_objetados);
        renderTablaAbiertas(resp.objeciones_abiertas, resp.dias_abiertos_min);
    }

    function cargarDashboard() {
        if (!$("#hcTabDashboard").length) {
            return;
        }

        $("#hcDashLoading").show();
        $("#hcDashContenido").css("opacity", "0.45");

        $.ajax({
            url: "ajax/dashboard-decisiones/historial-credito-dashboard.ajax.php",
            method: "POST",
            dataType: "json",
            data: {
                fecha_desde: $("#hcDashDesde").val(),
                fecha_hasta: $("#hcDashHasta").val(),
                vendedor: hcDashVendedor(),
                dias_abiertos: $("#hcDashDiasAbiertos").val() || 3,
            },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    return;
                }
                hcDashCargado = true;
                renderDashboard(resp);
                if (resp.kpis && resp.kpis.cola_generados !== undefined) {
                    $("#hcTabBadgeCola").text(resp.kpis.cola_generados);
                    $("#hcDashKpiCola").text(resp.kpis.cola_generados);
                }
            })
            .always(function () {
                $("#hcDashLoading").hide();
                $("#hcDashContenido").css("opacity", "1");
            });
    }

    $("#btnHcDashActualizar").on("click", cargarDashboard);

    $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
        var target = $(e.target).attr("href");
        if (target === "#hcTabDashboard") {
            hcActualizarUrlTab();
            if (!hcDashCargado) {
                cargarDashboard();
            } else {
                Object.keys(hcDashCharts).forEach(function (key) {
                    if (hcDashCharts[key] && typeof hcDashCharts[key].resize === "function") {
                        hcDashCharts[key].resize();
                    }
                });
            }
        } else if (target === "#hcTabCola" || target === "#hcTabMovimientos") {
            hcActualizarUrlTab();
        }
    });

    $(function () {
        var $dashSel = $("#hcDashVendedor");
        if ($dashSel.length && $dashSel.data("selectpicker")) {
            $dashSel.selectpicker("refresh");
        }
        if ($("#hcTabDashboard").hasClass("active")) {
            cargarDashboard();
        }
    });

    $(document).ajaxComplete(function (_e, xhr, settings) {
        var url = urlAccion(settings);
        if (
            url.indexOf("aprobar-pedido.ajax.php") !== -1 ||
            url.indexOf("anular-pedido.ajax.php") !== -1 ||
            url.indexOf("refrescar-dashboard.ajax.php") !== -1 ||
            (url.indexOf("decisiones-credito.ajax.php") !== -1 &&
                ["registrar", "solicitar", "cerrar_decision", "resolver_solicitud"].indexOf(
                    String(dataAccion(settings) || "").toLowerCase()
                ) !== -1)
        ) {
            if ($("#hcTabDashboard").hasClass("active")) {
                cargarDashboard();
            }
        }
    });
})();
