(function () {
    var lcContext = { cliente: "", nombre: "" };

    function fmtMoney(v) {
        if (v === null || v === undefined || v === "") return "—";
        return "S/ " + Number(v).toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(s) {
        return String(s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function postLc(accion, data, opts) {
        return $.ajax({
            url: "ajax/linea-credito/linea-credito.ajax.php",
            method: "POST",
            dataType: "json",
            timeout: (opts && opts.timeout) || 0,
            data: $.extend({ accion: accion }, data || {}),
        });
    }

    function msgAjaxError(xhr, fallback) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
            return xhr.responseJSON.msg;
        }
        if (xhr && xhr.responseText) {
            try {
                var parsed = JSON.parse(xhr.responseText);
                if (parsed && parsed.msg) {
                    return parsed.msg;
                }
            } catch (e) { /* respuesta no JSON */ }
        }
        return fallback || "Error de comunicación.";
    }

    function renderHistorial(items) {
        if (!items || !items.length) {
            return '<p class="text-muted">Sin historial registrado.</p>';
        }
        var html = '<ul class="lc-timeline">';
        items.forEach(function (h) {
            html +=
                "<li><strong>" + esc(h.tipo_evento) + "</strong> " +
                esc(h.anio) + "/" + esc(h.mes) +
                " · Op. " + fmtMoney(h.linea_operativa) +
                " · Rec. " + fmtMoney(h.linea_recomendada) +
                (h.linea_aprobada ? " · Apr. " + fmtMoney(h.linea_aprobada) : "") +
                "<br><small class=\"text-muted\">" +
                esc(h.usuario_nombre || "") + " · " + esc(h.fecha || "") +
                "</small></li>";
        });
        return html + "</ul>";
    }

    function renderSolicitudes(items) {
        var html = "";
        if (!items || !items.length) {
            html += '<p class="text-muted">Sin solicitudes.</p>';
        } else {
            html += '<div class="lc-solicitudes">';
            items.forEach(function (s) {
                var cls = s.estado === "APROBADA" ? "success" : s.estado === "RECHAZADA" ? "danger" : "warning";
                html +=
                    '<div class="lc-sol-item">' +
                    '<span class="label label-' + cls + '">' + esc(s.estado) + "</span> " +
                    "Solicitó " + fmtMoney(s.linea_solicitada) + " (actual " + fmtMoney(s.linea_actual) + ")" +
                    "<p>" + esc(s.justificacion) + "</p>";
                if (s.estado === "PENDIENTE") {
                    html +=
                        '<form class="lc-form-resolver" data-id="' + s.id + '">' +
                        '<div class="row">' +
                        '<div class="col-sm-4"><input type="number" step="1000" min="1000" class="form-control input-sm" name="linea_resuelta" placeholder="Línea aprobada (miles)"></div>' +
                        '<div class="col-sm-5"><input type="text" class="form-control input-sm" name="comentario_resolucion" placeholder="Comentario"></div>' +
                        '<div class="col-sm-3">' +
                        '<button type="submit" class="btn btn-success btn-xs" data-estado="APROBADA">Aprobar</button> ' +
                        '<button type="submit" class="btn btn-danger btn-xs" data-estado="RECHAZADA">Rechazar</button>' +
                        "</div></div></form>";
                } else if (s.linea_resuelta) {
                    html += "<small>Aprobado: " + fmtMoney(s.linea_resuelta) + "</small>";
                }
                html += "</div>";
            });
            html += "</div>";
        }

        html +=
            '<div class="lc-nueva-sol">' +
            "<h5>Nueva solicitud de incremento</h5>" +
            '<form id="lcFormSolicitud">' +
            '<input type="hidden" name="codigo_cliente" value="' + esc(lcContext.cliente) + '">' +
            '<div class="row">' +
            '<div class="col-sm-4"><input type="number" step="1000" min="1000" class="form-control input-sm" name="linea_solicitada" placeholder="Monto solicitado (miles)" required></div>' +
            '<div class="col-sm-8"><input type="text" class="form-control input-sm" name="justificacion" placeholder="Justificación" required></div>' +
            "</div>" +
            '<button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px"><i class="fa fa-send"></i> Enviar</button>' +
            "</form></div>";

        return html;
    }

    function renderDetalle(data) {
        var c = data.cliente || {};
        return (
            '<div class="lc-detalle">' +
            '<div class="row lc-kpis">' +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Operativa</span><strong>' + fmtMoney(c.linea_operativa) + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Recomendada</span><strong>' + fmtMoney(c.linea_recomendada) + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi lc-kpi--highlight"><span>Aprobada</span><strong>' + fmtMoney(c.linea_aprobada) + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Cupo disp.</span><strong>' + fmtMoney(c.cupo_disponible) + "</strong></div></div>" +
            "</div>" +
            '<div class="row lc-kpis" style="margin-top:8px">' +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Deuda</span><strong>' + fmtMoney(c.deuda_actual) + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Utilización</span><strong>' + (c.utilizacion_pct != null ? c.utilizacion_pct + "%" : "—") + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Riesgo</span><strong>' + (c.score_riesgo != null ? c.score_riesgo : "—") + "</strong></div></div>" +
            '<div class="col-sm-3"><div class="lc-kpi"><span>Acción IC</span><strong>' + esc(c.accion_linea || "—") + "</strong></div></div>" +
            "</div>" +
            '<p style="margin-top:10px"><button type="button" class="btn btn-default btn-sm" id="btnLcActualizarCliente"><i class="fa fa-refresh"></i> Actualizar desde IC</button></p>' +
            "<h5><i class=\"fa fa-history\"></i> Historial</h5>" + renderHistorial(data.historial) +
            "<h5><i class=\"fa fa-paper-plane\"></i> Solicitudes</h5>" + renderSolicitudes(data.solicitudes) +
            "</div>"
        );
    }

    function cargarDetalle() {
        $("#lcDetalleBody").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
        return postLc("detalle", { codigo_cliente: lcContext.cliente });
    }

    function mostrarDetalle(resp) {
        if (!resp || !resp.ok) {
            $("#lcDetalleBody").html('<div class="alert alert-warning">' + esc((resp && resp.msg) || "Error") + "</div>");
            return;
        }
        $("#lcDetalleBody").html(renderDetalle(resp));
        $("#lcLinkIc").attr("href", resp.url_ic || "#");
    }

    $(document).ready(function () {
        if ($.fn.DataTable && $("#tablaLineaCredito").length) {
            $("#tablaLineaCredito").DataTable({
                language: { url: "vistas/js/spanish.json" },
                pageLength: 25,
                order: [[0, "asc"]],
            });
        }
    });

    $("#lcBusqueda").on("keyup", function () {
        var q = $(this).val().toLowerCase();
        $("#tablaLineaCredito tbody tr").each(function () {
            var txt = $(this).text().toLowerCase();
            $(this).toggle(txt.indexOf(q) >= 0);
        });
    });

    $(document).on("click", ".btnLcDetalle", function () {
        lcContext.cliente = $(this).data("cliente");
        lcContext.nombre = $(this).data("nombre");
        $("#lcDetalleTitulo").html('<i class="fa fa-user"></i> ' + esc(lcContext.nombre));
        $("#modalLcDetalle").modal("show");
        cargarDetalle().done(mostrarDetalle);
    });

    $(document).on("click", "#btnLcActualizarCliente", function () {
        var $btn = $(this);
        $btn.prop("disabled", true);
        postLc("actualizar_cliente", { codigo_cliente: lcContext.cliente })
            .done(function (resp) {
                mostrarDetalle(resp);
                swal("Actualizado", "Datos recalculados desde Inteligencia Comercial.", "success");
            })
            .fail(function () { swal("Error", "No se pudo actualizar.", "error"); })
            .always(function () { $btn.prop("disabled", false); });
    });

    $("#btnLcCierreMensual").on("click", function () {
        swal({
            title: "¿Ejecutar cierre mensual?",
            text: "Se procesarán solo clientes con vendedor activo y compra/pedido en los últimos 24 meses.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Ejecutar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            var $btn = $("#btnLcCierreMensual");
            var labelOriginal = $btn.html();
            var totales = { procesados: 0, errores: 0 };

            function ejecutarLote() {
                return postLc("cierre_mensual_lote", {}, { timeout: 300000 }).then(function (resp) {
                    if (!resp || !resp.ok) {
                        return $.Deferred().reject(resp).promise();
                    }

                    totales.procesados += resp.procesados_lote || 0;
                    totales.errores += resp.errores_lote || 0;

                    if (!resp.terminado && (resp.restantes || 0) > 0) {
                        if ((resp.procesados_lote || 0) + (resp.errores_lote || 0) === 0) {
                            return $.Deferred().reject({
                                msg: "El cierre no avanzó. Verifique que las tablas SQL estén creadas.",
                            }).promise();
                        }
                        $btn.html(
                            '<i class="fa fa-spinner fa-spin"></i> Procesando… (' +
                            resp.restantes + " pendientes)"
                        );
                        return ejecutarLote();
                    }

                    return resp;
                });
            }

            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Iniciando cierre…');

            ejecutarLote()
                .done(function () {
                    swal(
                        "Cierre completado",
                        "Procesados: " + totales.procesados + " · Errores: " + totales.errores,
                        "success"
                    );
                    setTimeout(function () { location.reload(); }, 1200);
                })
                .fail(function (xhr) {
                    var msg = (xhr && xhr.msg) || msgAjaxError(xhr, "No se pudo ejecutar el cierre.");
                    swal("Error", msg, "error");
                })
                .always(function () {
                    $btn.prop("disabled", false).html(labelOriginal);
                });
        });
    });

    $(document).on("submit", "#lcFormSolicitud", function (e) {
        e.preventDefault();
        postLc("solicitar", $(this).serialize())
            .done(function (resp) {
                if (!resp || !resp.ok) { swal("Atención", (resp && resp.msg) || "Error", "warning"); return; }
                mostrarDetalle(resp);
                swal("Enviado", "Solicitud registrada.", "success");
            });
    });

    $(document).on("submit", ".lc-form-resolver", function (e) {
        e.preventDefault();
        var $form = $(this);
        var estado = $(document.activeElement).data("estado");
        if (!estado) return;
        postLc("resolver_solicitud", {
            id_solicitud: $form.data("id"),
            estado: estado,
            linea_resuelta: $form.find('[name="linea_resuelta"]').val(),
            comentario_resolucion: $form.find('[name="comentario_resolucion"]').val(),
        }).done(function (resp) {
            if (!resp || !resp.ok) { swal("Atención", (resp && resp.msg) || "Error", "warning"); return; }
            mostrarDetalle(resp);
            swal("Resuelto", "Solicitud actualizada.", "success");
        });
    });
})();
