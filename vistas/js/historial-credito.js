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
            '<td><span class="hc-fecha">' +
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
            '<td class="text-right">' +
            fmtMonto(row.pedido_lista, row.pedido_total) +
            "</td>" +
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
})();
