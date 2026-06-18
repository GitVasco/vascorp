$(function () {
    if ($("#panelSolicitudesAtencionVasco").length === 0) {
        return;
    }

    var $cuerpo = $("#cuerpoAtencionVasco");
    var $log = $("#logAtencionVasco");
    var $boxResumen = $("#boxResumenAtencionVasco");
    var $btnTomar = $("#btnTomarSeleccionAtencionVasco");
    var $btnCompletar = $("#btnCompletarSeleccionAtencionVasco");
    var $filtroEstado = $("#filtroEstadoAtencionVasco");

    var estado = {
        traceId: null,
        items: [],
        statusFiltro: "pending",
        buscando: false,
        procesando: false,
    };

    function escHtml(valor) {
        return $("<div>").text(valor == null ? "" : String(valor)).html();
    }

    function fmtFecha(valor) {
        if (!valor) {
            return "—";
        }
        var d = new Date(valor);
        if (isNaN(d.getTime())) {
            return escHtml(valor);
        }
        return d.toLocaleString("es-PE", { hour12: false });
    }

    function agregarLog(linea) {
        var hora = new Date().toLocaleTimeString("es-PE", { hour12: false });
        var texto = "[" + hora + "] " + linea;
        var actual = $log.text();
        if (actual === "Sin operaciones aún.") {
            actual = "";
        }
        $log.text((actual ? actual + "\n" : "") + texto);
        $log.scrollTop($log[0].scrollHeight);
    }

    function docCliente(item) {
        var c = item.customer || {};
        var tipo = c.doc_type ? String(c.doc_type) : "";
        var num = c.doc_number ? String(c.doc_number) : "";
        if (tipo === "" && num === "") {
            return "—";
        }
        return escHtml(tipo + " " + num);
    }

    function nombreCliente(item) {
        var c = item.customer || {};
        var erp = item.erp_preview || {};
        var partes = [];
        if (erp.codigo) {
            partes.push(String(erp.codigo));
        } else if (c.code) {
            partes.push(String(c.code));
        }
        if (erp.nombre) {
            partes.push(String(erp.nombre));
        } else if (c.name) {
            partes.push(String(c.name));
        }
        if (c.external_id) {
            partes.push("(ERP " + String(c.external_id) + ")");
        }
        return partes.length ? escHtml(partes.join(" — ")) : "—";
    }

    function nombreVendedor(item) {
        var s = item.seller || {};
        var partes = [];
        if (s.name) {
            partes.push(String(s.name));
        }
        if (s.username) {
            partes.push(String(s.username));
        }
        return partes.length ? escHtml(partes.join(" · ")) : "—";
    }

    function estadoErp(item) {
        var erp = item.erp_preview || {};
        var clase = "vasco-atencion-estado-warn";
        if (!erp.encontrado) {
            clase = "vasco-atencion-estado-error";
        } else if (erp.puede_tomar) {
            clase = "vasco-atencion-estado-ok";
        }
        var texto = erp.motivo || (erp.encontrado ? "OK" : "No encontrado");
        return '<span class="' + clase + '">' + escHtml(texto) + "</span>";
    }

    function contarTomables(items) {
        var n = 0;
        $.each(items || [], function (_, item) {
            var erp = item.erp_preview || {};
            if (erp.puede_tomar) {
                n += 1;
            }
        });
        return n;
    }

    function actualizarBotonesMasivos() {
        var status = estado.statusFiltro;
        if (status === "pending") {
            $btnTomar.show();
            $btnCompletar.hide();
        } else if (status === "acknowledged") {
            $btnTomar.hide();
            $btnCompletar.show();
        } else {
            $btnTomar.hide();
            $btnCompletar.hide();
        }
    }

    function actualizarSeleccion() {
        var count = $cuerpo.find(".chk-atencion-vasco:checked").length;
        $("#resumenSeleccionAtencionVasco").text(String(count));
        var disabled = count === 0 || estado.procesando;
        $btnTomar.prop("disabled", disabled);
        $btnCompletar.prop("disabled", disabled);
    }

    function botonesFila(item) {
        var status = estado.statusFiltro;
        var html = "";

        if (status === "pending") {
            html +=
                '<button type="button" class="btn btn-success btn-xs btn-tomar-una-atencion-vasco">' +
                '<i class="fa fa-hand-paper-o"></i> Tomar</button> ' +
                '<button type="button" class="btn btn-danger btn-xs btn-rechazar-una-atencion-vasco">' +
                '<i class="fa fa-times"></i></button>';
        } else if (status === "acknowledged") {
            html +=
                '<button type="button" class="btn btn-primary btn-xs btn-completar-una-atencion-vasco">' +
                '<i class="fa fa-check"></i> Completar</button>';
        } else {
            html = '<span class="text-muted">—</span>';
        }

        return html;
    }

    function renderTabla(items) {
        estado.items = items || [];

        if (!estado.items.length) {
            $cuerpo.html(
                '<tr class="vasco-atencion-empty"><td colspan="9" class="text-center text-muted">' +
                    "No hay solicitudes con los filtros indicados." +
                    "</td></tr>"
            );
            actualizarSeleccion();
            return;
        }

        var filas = [];
        $.each(estado.items, function (idx, item) {
            var id = item.id != null ? String(item.id) : "";
            var erp = item.erp_preview || {};
            var mensaje = item.message ? String(item.message) : "—";
            var vendErp = erp.vendedor_erp ? String(erp.vendedor_erp) : "—";
            var mostrarCheck = estado.statusFiltro === "pending" || estado.statusFiltro === "acknowledged";

            filas.push(
                "<tr data-index=\"" +
                    idx +
                    "\" data-id=\"" +
                    escHtml(id) +
                    "\">" +
                    "<td>" +
                    (mostrarCheck
                        ? '<input type="checkbox" class="chk-atencion-vasco">'
                        : "") +
                    "</td>" +
                    "<td>" +
                    fmtFecha(item.created_at || item.requested_at) +
                    "</td>" +
                    "<td>" +
                    nombreCliente(item) +
                    "</td>" +
                    "<td>" +
                    docCliente(item) +
                    "</td>" +
                    '<td class="vasco-atencion-mensaje">' +
                    escHtml(mensaje) +
                    "</td>" +
                    "<td>" +
                    nombreVendedor(item) +
                    "</td>" +
                    "<td>" +
                    escHtml(vendErp) +
                    "</td>" +
                    "<td>" +
                    estadoErp(item) +
                    "</td>" +
                    "<td>" +
                    botonesFila(item) +
                    "</td>" +
                    "</tr>"
            );
        });

        $cuerpo.html(filas.join(""));
        actualizarSeleccion();
    }

    function itemDesdeIndice(idx) {
        return estado.items[idx] || null;
    }

    function itemsSeleccionados(actionDefault) {
        var seleccion = [];
        $cuerpo.find(".chk-atencion-vasco:checked").each(function () {
            var $tr = $(this).closest("tr");
            var idx = parseInt($tr.attr("data-index"), 10);
            var item = itemDesdeIndice(idx);
            if (!item || item.id == null) {
                return;
            }
            seleccion.push({
                id: parseInt(item.id, 10),
                action: actionDefault,
                vasco_item: item,
            });
        });
        return seleccion;
    }

    function buscarSolicitudes() {
        if (estado.buscando) {
            return;
        }

        estado.buscando = true;
        estado.statusFiltro = $filtroEstado.val() || "pending";
        actualizarBotonesMasivos();

        $("#btnBuscarAtencionVasco").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Consultando…');

        var params = {
            accion: "listar",
            status: estado.statusFiltro,
            since: $.trim($("#filtroDesdeAtencionVasco").val()),
            limit: $("#filtroLimiteAtencionVasco").val(),
        };

        if (estado.traceId) {
            params.trace_id = estado.traceId;
        }

        $.getJSON("ajax/cuentas-corrientes/vasco-solicitud-atencion.ajax.php", params)
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error consulta: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal("Error", resp && resp.msg ? resp.msg : "No se pudo consultar Vasco", "error");
                    return;
                }

                estado.traceId = resp.trace_id || estado.traceId;
                var items = resp.items || [];
                $("#resumenCountAtencionVasco").text(String(resp.count != null ? resp.count : items.length));
                $("#resumenTomablesAtencionVasco").text(String(contarTomables(items)));
                $("#resumenTraceAtencionVasco").text(estado.traceId || "—");
                $boxResumen.show();

                renderTabla(items);
                agregarLog(
                    "Consulta OK — " +
                        (resp.count != null ? resp.count : items.length) +
                        " solicitud(es) [" +
                        estado.statusFiltro +
                        "]"
                );
            })
            .fail(function () {
                agregarLog("Error de red al consultar Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.buscando = false;
                $("#btnBuscarAtencionVasco").prop("disabled", false).html('<i class="fa fa-refresh"></i> Consultar');
            });
    }

    function procesarItems(items) {
        if (!items || !items.length || estado.procesando) {
            return;
        }

        estado.procesando = true;
        $btnTomar.prop("disabled", true);
        $btnCompletar.prop("disabled", true);
        $(".btn-tomar-una-atencion-vasco, .btn-rechazar-una-atencion-vasco, .btn-completar-una-atencion-vasco").prop(
            "disabled",
            true
        );

        var payload = {
            trace_id: estado.traceId,
            ack_by: window.vascoAtencionUsuario || "vascorp",
            items: items,
        };

        $.ajax({
            url: "ajax/cuentas-corrientes/vasco-solicitud-atencion.ajax.php?accion=procesar",
            method: "POST",
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(payload),
        })
            .done(function (resp) {
                if (!resp) {
                    agregarLog("Error: respuesta vacía");
                    swal("Error", "Respuesta inválida del servidor", "error");
                    return;
                }

                var erp = resp.erp || [];
                $.each(erp, function (_, row) {
                    var linea = "#" + row.id + " " + (row.action || "") + ": " + (row.msg || "");
                    if (row.ok === false) {
                        linea += " [falló]";
                    }
                    agregarLog(linea);
                });

                if (resp.ok) {
                    swal("Listo", resp.msg || "Operación completada", "success");
                } else if (resp.partial) {
                    swal("Parcial", resp.msg || "Algunos ítems fallaron", "warning");
                } else {
                    swal("Error", resp.msg || "No se pudo procesar", "error");
                }

                buscarSolicitudes();
            })
            .fail(function () {
                agregarLog("Error de red al procesar");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.procesando = false;
                actualizarSeleccion();
                $(".btn-tomar-una-atencion-vasco, .btn-rechazar-una-atencion-vasco, .btn-completar-una-atencion-vasco").prop(
                    "disabled",
                    false
                );
            });
    }

    function procesarUna($tr, action, rejectionReason) {
        var idx = parseInt($tr.attr("data-index"), 10);
        var item = itemDesdeIndice(idx);
        if (!item || item.id == null) {
            return;
        }

        var entry = {
            id: parseInt(item.id, 10),
            action: action,
            vasco_item: item,
        };

        if (action === "rejected" && rejectionReason) {
            entry.rejection_reason = rejectionReason;
        }

        procesarItems([entry]);
    }

    function pedirMotivoRechazo(callback) {
        swal({
            title: "Motivo de rechazo",
            input: "text",
            inputPlaceholder: "Ej. Cliente no encontrado en ERP",
            showCancelButton: true,
            confirmButtonText: "Rechazar",
            cancelButtonText: "Cancelar",
            inputValidator: function (value) {
                if (!value || !$.trim(value)) {
                    return "Indique un motivo";
                }
                return null;
            },
        }).then(function (result) {
            if (result.value) {
                callback($.trim(result.value));
            }
        });
    }

    $("#btnBuscarAtencionVasco").on("click", buscarSolicitudes);

    $filtroEstado.on("change", function () {
        estado.statusFiltro = $filtroEstado.val() || "pending";
        actualizarBotonesMasivos();
    });

    $("#btnMarcarTodosAtencionVasco").on("click", function () {
        $cuerpo.find(".chk-atencion-vasco").prop("checked", true);
        actualizarSeleccion();
    });

    $("#btnDesmarcarTodosAtencionVasco").on("click", function () {
        $cuerpo.find(".chk-atencion-vasco").prop("checked", false);
        actualizarSeleccion();
    });

    $cuerpo.on("change click", ".chk-atencion-vasco", function () {
        actualizarSeleccion();
    });

    $("#btnTomarSeleccionAtencionVasco").on("click", function () {
        var items = itemsSeleccionados("acknowledged");
        if (!items.length) {
            swal("Atención", "Seleccione al menos una solicitud", "info");
            return;
        }
        procesarItems(items);
    });

    $("#btnCompletarSeleccionAtencionVasco").on("click", function () {
        var items = itemsSeleccionados("completed");
        if (!items.length) {
            swal("Atención", "Seleccione al menos una solicitud", "info");
            return;
        }
        procesarItems(items);
    });

    $cuerpo.on("click", ".btn-tomar-una-atencion-vasco", function () {
        procesarUna($(this).closest("tr"), "acknowledged");
    });

    $cuerpo.on("click", ".btn-completar-una-atencion-vasco", function () {
        procesarUna($(this).closest("tr"), "completed");
    });

    $cuerpo.on("click", ".btn-rechazar-una-atencion-vasco", function () {
        var $tr = $(this).closest("tr");
        pedirMotivoRechazo(function (motivo) {
            procesarUna($tr, "rejected", motivo);
        });
    });

    actualizarBotonesMasivos();
});
