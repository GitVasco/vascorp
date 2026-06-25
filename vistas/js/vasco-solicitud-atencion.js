$(function () {
    if ($("#panelSolicitudesAtencionVasco").length === 0) {
        return;
    }

    var $cuerpo = $("#cuerpoAtencionVasco");
    var $log = $("#logAtencionVasco");
    var $boxResumen = $("#boxResumenAtencionVasco");
    var $btnTomar = $("#btnTomarSeleccionAtencionVasco");
    var $btnAtender = $("#btnAtenderSeleccionAtencionVasco");
    var $tabs = $("#tabsBandejaAtencionVasco");
    var $historialFiltro = $(".vasco-atencion-historial-filtro");
    var $thFechaSec = $("#thFechaSecundariaAtencionVasco");

    var estado = {
        traceId: null,
        items: [],
        tabActiva: "pending",
        statusFiltro: "pending",
        buscando: false,
        procesando: false,
        conteos: { pending: 0, acknowledged: 0 },
        ultimaAccion: null,
    };

    var textosAyuda = {
        pending: "Nuevas solicitudes del portal — confírmelas con Tomar o rechácelas.",
        acknowledged: "Solicitudes en curso — marque atendida cuando el vendedor contactó al cliente.",
        historial: "Consulta de solicitudes ya cerradas en Vasco.",
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

    function statusApiDesdeTab(tab) {
        if (tab === "historial") {
            return $("#filtroHistorialAtencionVasco").val() || "completed";
        }
        return tab;
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

    function badgeEstadoVasco(item) {
        var status = item.status ? String(item.status) : estado.statusFiltro;
        if (status === "pending") {
            return '<span class="label label-warning">Pendiente</span>';
        }
        if (status === "acknowledged") {
            return '<span class="label label-info">En curso</span>';
        }
        if (status === "completed") {
            return '<span class="label label-success">Atendida</span>';
        }
        if (status === "cancelled") {
            return '<span class="label label-default">Cancelada</span>';
        }
        return '<span class="label label-default">' + escHtml(status) + "</span>";
    }

    function estadoErp(item) {
        var erp = item.erp_preview || {};
        var status = item.status ? String(item.status) : estado.statusFiltro;

        if (status === "acknowledged" || status === "completed") {
            if (erp.encontrado) {
                return '<span class="vasco-atencion-estado-ok">Cliente en ERP</span>';
            }
            return '<span class="vasco-atencion-estado-warn">Sin match ERP</span>';
        }

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

    function actualizarAyudaTab() {
        var texto = textosAyuda[estado.tabActiva] || "";
        $("#ayudaTabAtencionVasco").text(texto);

        if (estado.tabActiva === "pending") {
            $("#resumenLabelSecundarioAtencionVasco").text("Listas para tomar");
            $thFechaSec.text("—");
        } else if (estado.tabActiva === "acknowledged") {
            $("#resumenLabelSecundarioAtencionVasco").text("Pendientes de cierre");
            $thFechaSec.text("Tomada");
        } else {
            $("#resumenLabelSecundarioAtencionVasco").text("Registros");
            $thFechaSec.text("Cierre");
        }
    }

    function actualizarBotonesMasivos() {
        var tab = estado.tabActiva;
        var esHistorial = tab === "historial";

        $(".vasco-atencion-accion-masiva").toggle(!esHistorial);

        if (tab === "pending") {
            $btnTomar.show();
            $btnAtender.hide();
        } else if (tab === "acknowledged") {
            $btnTomar.hide();
            $btnAtender.show();
        } else {
            $btnTomar.hide();
            $btnAtender.hide();
        }
    }

    function actualizarSeleccion() {
        var count = $cuerpo.find(".chk-atencion-vasco:checked").length;
        $("#resumenSeleccionAtencionVasco").text(String(count));
        var disabled = count === 0 || estado.procesando;
        $btnTomar.prop("disabled", disabled);
        $btnAtender.prop("disabled", disabled);
    }

    function actualizarBadgesConteo() {
        $("#badgeCountPendientes").text(String(estado.conteos.pending));
        $("#badgeCountEnCurso").text(String(estado.conteos.acknowledged));

        if (estado.conteos.acknowledged > 0) {
            $("#tabAtencionEnCurso").addClass("vasco-atencion-tab-alerta");
        } else {
            $("#tabAtencionEnCurso").removeClass("vasco-atencion-tab-alerta");
        }
    }

    function botonesFila(item) {
        var tab = estado.tabActiva;
        var html = "";

        if (tab === "pending") {
            html +=
                '<button type="button" class="btn btn-success btn-xs btn-tomar-una-atencion-vasco" title="Confirmar y pasar a En curso">' +
                '<i class="fa fa-hand-paper-o"></i> Tomar</button> ' +
                '<button type="button" class="btn btn-primary btn-xs btn-atender-una-atencion-vasco" title="Cerrar directo (sin pasar por En curso)">' +
                '<i class="fa fa-check-circle"></i> Atendida</button> ' +
                '<button type="button" class="btn btn-danger btn-xs btn-rechazar-una-atencion-vasco" title="Rechazar">' +
                '<i class="fa fa-times"></i></button>';
        } else if (tab === "acknowledged") {
            html +=
                '<button type="button" class="btn btn-primary btn-xs btn-atender-una-atencion-vasco">' +
                '<i class="fa fa-check-circle"></i> Marcar atendida</button>';
        } else {
            html = '<span class="text-muted">—</span>';
        }

        return html;
    }

    function fechaSecundaria(item) {
        var tab = estado.tabActiva;
        if (tab === "acknowledged") {
            return fmtFecha(item.acknowledged_at || item.updated_at);
        }
        if (tab === "historial") {
            return fmtFecha(item.completed_at || item.cancelled_at || item.updated_at);
        }
        return "—";
    }

    function renderTabla(items) {
        estado.items = items || [];
        var colspan = 11;

        if (!estado.items.length) {
            var vacio = "No hay solicitudes en esta bandeja.";
            if (estado.tabActiva === "acknowledged") {
                vacio = "No hay solicitudes en curso. Revise la pestaña Pendientes.";
            }
            $cuerpo.html(
                '<tr class="vasco-atencion-empty"><td colspan="' +
                    colspan +
                    '" class="text-center text-muted">' +
                    vacio +
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
            var mostrarCheck = estado.tabActiva === "pending" || estado.tabActiva === "acknowledged";

            filas.push(
                "<tr data-index=\"" +
                    idx +
                    "\" data-id=\"" +
                    escHtml(id) +
                    "\">" +
                    "<td>" +
                    (mostrarCheck ? '<input type="checkbox" class="chk-atencion-vasco">' : "") +
                    "</td>" +
                    "<td>" +
                    badgeEstadoVasco(item) +
                    "</td>" +
                    "<td>" +
                    fmtFecha(item.created_at || item.requested_at) +
                    "</td>" +
                    "<td>" +
                    fechaSecundaria(item) +
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

    function consultarApi(status, traceIdOpcional) {
        var params = {
            accion: "listar",
            status: status,
            since: $.trim($("#filtroDesdeAtencionVasco").val()),
            limit: $("#filtroLimiteAtencionVasco").val(),
        };

        if (traceIdOpcional) {
            params.trace_id = traceIdOpcional;
        }

        return $.getJSON("ajax/cuentas-corrientes/vasco-solicitud-atencion.ajax.php", params);
    }

    function refrescarConteosTabs() {
        var reqs = [
            consultarApi("pending"),
            consultarApi("acknowledged"),
        ];

        return $.when(reqs[0], reqs[1]).done(function (respPending, respAck) {
            var p = respPending[0] || respPending;
            var a = respAck[0] || respAck;

            if (p && p.ok) {
                estado.conteos.pending = p.count != null ? p.count : (p.items || []).length;
            }
            if (a && a.ok) {
                estado.conteos.acknowledged = a.count != null ? a.count : (a.items || []).length;
            }
            actualizarBadgesConteo();
        });
    }

    function buscarSolicitudes(opciones) {
        opciones = opciones || {};

        if (estado.buscando) {
            return;
        }

        estado.buscando = true;
        estado.statusFiltro = statusApiDesdeTab(estado.tabActiva);
        actualizarBotonesMasivos();
        actualizarAyudaTab();

        $("#btnBuscarAtencionVasco").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Consultando…');

        consultarApi(estado.statusFiltro, estado.traceId)
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error consulta: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal("Error", resp && resp.msg ? resp.msg : "No se pudo consultar Vasco", "error");
                    return;
                }

                estado.traceId = resp.trace_id || estado.traceId;
                var items = resp.items || [];
                var count = resp.count != null ? resp.count : items.length;

                $("#resumenCountAtencionVasco").text(String(count));
                if (estado.tabActiva === "pending") {
                    $("#resumenSecundarioAtencionVasco").text(String(contarTomables(items)));
                    estado.conteos.pending = count;
                } else if (estado.tabActiva === "acknowledged") {
                    $("#resumenSecundarioAtencionVasco").text(String(count));
                    estado.conteos.acknowledged = count;
                } else {
                    $("#resumenSecundarioAtencionVasco").text(String(count));
                }

                $("#resumenTraceAtencionVasco").text(estado.traceId || "—");
                $boxResumen.show();
                actualizarBadgesConteo();

                renderTabla(items);
                agregarLog("Consulta OK — " + count + " solicitud(es) [" + estado.statusFiltro + "]");

                if (!opciones.omitirConteos) {
                    refrescarConteosTabs();
                }
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

    function procesarItems(items, opciones) {
        opciones = opciones || {};

        if (!items || !items.length || estado.procesando) {
            return;
        }

        estado.procesando = true;
        $btnTomar.prop("disabled", true);
        $btnAtender.prop("disabled", true);
        $(".btn-tomar-una-atencion-vasco, .btn-rechazar-una-atencion-vasco, .btn-atender-una-atencion-vasco").prop(
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
                var huboTomadas = false;
                var huboAtendidas = false;

                $.each(erp, function (_, row) {
                    var linea = "#" + row.id + " " + (row.action || "") + ": " + (row.msg || "");
                    if (row.ok === false) {
                        linea += " [falló]";
                    }
                    agregarLog(linea);
                    if (row.action === "acknowledged" && row.ok !== false) {
                        huboTomadas = true;
                    }
                    if (row.action === "completed" && row.ok !== false) {
                        huboAtendidas = true;
                    }
                });

                if (resp.ok) {
                    swal("Listo", resp.msg || "Operación completada", "success");
                } else if (resp.partial) {
                    swal("Parcial", resp.msg || "Algunos ítems fallaron", "warning");
                } else {
                    swal("Error", resp.msg || "No se pudo procesar", "error");
                }

                if (huboTomadas && estado.tabActiva === "pending") {
                    refrescarConteosTabs();
                    cambiarTab("acknowledged", { autoBuscar: true });
                    return;
                }

                if (huboAtendidas) {
                    refrescarConteosTabs();
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
                $(".btn-tomar-una-atencion-vasco, .btn-rechazar-una-atencion-vasco, .btn-atender-una-atencion-vasco").prop(
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

    function confirmarAtendidas(cantidad, callback) {
        swal({
            title: "¿Marcar como atendida?",
            text:
                "Se cerrará en Vasco y el cliente podrá enviar una nueva solicitud desde el portal (" +
                cantidad +
                " registro(s)).",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, marcar atendida",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                callback();
            }
        });
    }

    function cambiarTab(tab, opciones) {
        opciones = opciones || {};
        estado.tabActiva = tab;
        estado.statusFiltro = statusApiDesdeTab(tab);

        $tabs.find("li").removeClass("active");
        $tabs.find('a[data-status="' + tab + '"]').parent().addClass("active");

        $historialFiltro.toggle(tab === "historial");
        actualizarBotonesMasivos();
        actualizarAyudaTab();

        if (opciones.autoBuscar) {
            buscarSolicitudes({ omitirConteos: opciones.silencioso });
        } else {
            $cuerpo.html(
                '<tr class="vasco-atencion-empty"><td colspan="11" class="text-center text-muted">' +
                    "Pulse <strong>Consultar</strong> para cargar esta bandeja." +
                    "</td></tr>"
            );
            actualizarSeleccion();
        }
    }

    $tabs.on("click", "a[data-status]", function (e) {
        e.preventDefault();
        var tab = $(this).data("status");
        if (tab && tab !== estado.tabActiva) {
            cambiarTab(tab);
        }
    });

    $("#filtroHistorialAtencionVasco").on("change", function () {
        if (estado.tabActiva === "historial") {
            buscarSolicitudes();
        }
    });

    $("#btnBuscarAtencionVasco").on("click", function () {
        buscarSolicitudes();
    });

    $("#btnRefrescarConteosAtencionVasco").on("click", function () {
        refrescarConteosTabs().always(function () {
            agregarLog("Contadores de pestañas actualizados");
        });
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
        swal({
            title: "¿Tomar solicitudes?",
            text: "Pasarán a En curso para que el vendedor atienda al cliente (" + items.length + ").",
            type: "info",
            showCancelButton: true,
            confirmButtonText: "Tomar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                procesarItems(items);
            }
        });
    });

    $("#btnAtenderSeleccionAtencionVasco").on("click", function () {
        var items = itemsSeleccionados("completed");
        if (!items.length) {
            swal("Atención", "Seleccione al menos una solicitud", "info");
            return;
        }
        confirmarAtendidas(items.length, function () {
            procesarItems(items);
        });
    });

    $cuerpo.on("click", ".btn-tomar-una-atencion-vasco", function () {
        var $tr = $(this).closest("tr");
        var item = itemDesdeIndice(parseInt($tr.attr("data-index"), 10));
        swal({
            title: "¿Tomar solicitud?",
            text: "La solicitud #" + (item ? item.id : "") + " pasará a En curso.",
            type: "info",
            showCancelButton: true,
            confirmButtonText: "Tomar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                procesarUna($tr, "acknowledged");
            }
        });
    });

    $cuerpo.on("click", ".btn-atender-una-atencion-vasco", function () {
        var $tr = $(this).closest("tr");
        var item = itemDesdeIndice(parseInt($tr.attr("data-index"), 10));
        confirmarAtendidas(1, function () {
            procesarUna($tr, "completed");
        });
    });

    $cuerpo.on("click", ".btn-rechazar-una-atencion-vasco", function () {
        var $tr = $(this).closest("tr");
        pedirMotivoRechazo(function (motivo) {
            procesarUna($tr, "rejected", motivo);
        });
    });

    actualizarBotonesMasivos();
    actualizarAyudaTab();
    refrescarConteosTabs();
    buscarSolicitudes();
});
