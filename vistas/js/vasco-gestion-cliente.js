$(function () {
    if ($("#panelGestionVascoClientes").length === 0) {
        return;
    }

    var $cuerpo = $("#cuerpoGestionVasco");
    var $log = $("#logGestionVasco");
    var $boxResumen = $("#boxResumenGestionVasco");
    var $btnAplicar = $("#btnAplicarSeleccionGestionVasco");

    var estado = {
        traceId: null,
        items: [],
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

    function celularVasco(item) {
        if (!item.phone_e164) {
            return '<span class="text-muted">—</span>';
        }
        return "<code>" + escHtml(String(item.phone_e164)) + "</code>";
    }

    function badgeConsent(item) {
        if (item.whatsapp_consent) {
            return '<span class="label label-success">Sí</span>';
        }
        return '<span class="label label-default">No</span>';
    }

    function estadoErp(item) {
        var erp = item.erp_preview || {};
        var clase = "vasco-gestion-estado-warn";
        if (!erp.encontrado || (erp.motivo && erp.motivo.indexOf("duplicado") >= 0)) {
            clase = "vasco-gestion-estado-error";
        } else if (erp.puede_aplicar) {
            clase = "vasco-gestion-estado-ok";
        }
        var texto = erp.motivo || (erp.encontrado ? "OK" : "No encontrado");
        return '<span class="' + clase + '">' + escHtml(texto) + "</span>";
    }

    function contarAplicables(items) {
        var n = 0;
        $.each(items || [], function (_, item) {
            var erp = item.erp_preview || {};
            if (erp.puede_aplicar) {
                n += 1;
            }
        });
        return n;
    }

    function actualizarSeleccion() {
        var count = $cuerpo.find(".chk-gestion-vasco:checked").length;
        $("#resumenSeleccionGestionVasco").text(String(count));
        $btnAplicar.prop("disabled", count === 0 || estado.procesando);
    }

    function initCheckboxesGestion() {
        $cuerpo.find(".chk-gestion-vasco").each(function () {
            var $chk = $(this);
            if ($chk.parent().hasClass("icheckbox_minimal")) {
                $chk.iCheck("destroy");
            }
        });
    }

    function renderTabla(items) {
        estado.items = items || [];

        if (!estado.items.length) {
            $cuerpo.html(
                '<tr class="vasco-gestion-empty"><td colspan="11" class="text-center text-muted">' +
                    "No hay gestiones pendientes con los filtros indicados." +
                    "</td></tr>"
            );
            actualizarSeleccion();
            return;
        }

        var filas = [];
        $.each(estado.items, function (idx, item) {
            var id = item.id != null ? String(item.id) : "";
            var erp = item.erp_preview || {};
            var telActual = erp.telefono_actual ? String(erp.telefono_actual) : "—";
            var telNuevo = erp.telefono_nuevo ? String(erp.telefono_nuevo) : "—";

            filas.push(
                "<tr data-index=\"" +
                    idx +
                    "\" data-id=\"" +
                    escHtml(id) +
                    "\">" +
                    '<td><input type="checkbox" class="chk-gestion-vasco"></td>' +
                    "<td>" +
                    fmtFecha(item.managed_at || item.created_at) +
                    "</td>" +
                    "<td>" +
                    nombreCliente(item) +
                    "</td>" +
                    "<td>" +
                    docCliente(item) +
                    "</td>" +
                    "<td>" +
                    celularVasco(item) +
                    "</td>" +
                    "<td>" +
                    badgeConsent(item) +
                    "</td>" +
                    "<td>" +
                    escHtml(telActual) +
                    "</td>" +
                    "<td><strong>" +
                    escHtml(telNuevo) +
                    "</strong></td>" +
                    "<td>" +
                    nombreVendedor(item) +
                    "</td>" +
                    "<td>" +
                    estadoErp(item) +
                    "</td>" +
                    '<td>' +
                    '<button type="button" class="btn btn-success btn-xs btn-aplicar-una-gestion-vasco">' +
                    '<i class="fa fa-check"></i> Aplicar</button> ' +
                    '<button type="button" class="btn btn-danger btn-xs btn-rechazar-una-gestion-vasco">' +
                    '<i class="fa fa-times"></i></button>' +
                    "</td>" +
                    "</tr>"
            );
        });

        $cuerpo.html(filas.join(""));
        initCheckboxesGestion();
        actualizarSeleccion();
    }

    function itemDesdeIndice(idx) {
        return estado.items[idx] || null;
    }

    function itemsSeleccionados(actionDefault) {
        var seleccion = [];
        $cuerpo.find(".chk-gestion-vasco:checked").each(function () {
            var $tr = $(this).closest("tr");
            var idx = parseInt($tr.attr("data-index"), 10);
            var item = itemDesdeIndice(idx);
            if (!item || item.id == null) {
                return;
            }
            seleccion.push({
                id: parseInt(item.id, 10),
                action: actionDefault || "synced",
                vasco_item: item,
            });
        });
        return seleccion;
    }

    function buscarPendientes() {
        if (estado.buscando) {
            return;
        }

        estado.buscando = true;
        $("#btnBuscarGestionVasco").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Consultando…');

        var params = {
            accion: "listar-pendientes",
            status: "pending",
            since: $.trim($("#filtroDesdeGestionVasco").val()),
            limit: $("#filtroLimiteGestionVasco").val(),
        };

        if (estado.traceId) {
            params.trace_id = estado.traceId;
        }

        $.getJSON("ajax/cuentas-corrientes/vasco-gestion-cliente.ajax.php", params)
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error consulta: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal("Error", resp && resp.msg ? resp.msg : "No se pudo consultar Vasco", "error");
                    return;
                }

                estado.traceId = resp.trace_id || estado.traceId;
                var items = resp.items || [];
                $("#resumenCountGestionVasco").text(String(resp.count != null ? resp.count : items.length));
                $("#resumenAplicablesGestionVasco").text(String(contarAplicables(items)));
                $("#resumenTraceGestionVasco").text(estado.traceId || "—");
                $boxResumen.show();

                renderTabla(items);
                agregarLog("Consulta OK — " + (resp.count != null ? resp.count : items.length) + " pendiente(s)");
            })
            .fail(function () {
                agregarLog("Error de red al consultar Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.buscando = false;
                $("#btnBuscarGestionVasco").prop("disabled", false).html('<i class="fa fa-refresh"></i> Consultar pendientes');
            });
    }

    function procesarItems(items) {
        if (!items || !items.length || estado.procesando) {
            return;
        }

        estado.procesando = true;
        $btnAplicar.prop("disabled", true);
        $(".btn-aplicar-una-gestion-vasco, .btn-rechazar-una-gestion-vasco").prop("disabled", true);

        var payload = {
            trace_id: estado.traceId,
            ack_by: window.vascoGestionUsuario || "vascorp",
            items: items,
        };

        $.ajax({
            url: "ajax/cuentas-corrientes/vasco-gestion-cliente.ajax.php?accion=procesar",
            method: "POST",
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(payload),
        })
            .done(function (resp) {
                if (!resp) {
                    agregarLog("Error procesamiento: respuesta vacía");
                    swal("Error", "Respuesta inválida del servidor", "error");
                    return;
                }

                if (resp.trace_id) {
                    estado.traceId = resp.trace_id;
                    $("#resumenTraceGestionVasco").text(estado.traceId);
                }

                var synced = resp.synced != null ? resp.synced : 0;
                var rejected = resp.rejected != null ? resp.rejected : 0;
                var fallidos = resp.failed && resp.failed.length ? resp.failed.length : 0;

                agregarLog(
                    (resp.ok ? "Procesamiento OK" : "Procesamiento parcial") +
                        " — synced: " +
                        synced +
                        ", rejected: " +
                        rejected +
                        ", fallidos ack: " +
                        fallidos
                );

                if (resp.erp && resp.erp.length) {
                    $.each(resp.erp, function (_, row) {
                        var det = "ID " + (row.id || "?") + " [" + (row.action || "?") + "]: " + (row.msg || "");
                        if (row.telefono_anterior != null && row.telefono_nuevo != null) {
                            det += " (" + row.telefono_anterior + " → " + row.telefono_nuevo + ")";
                        }
                        agregarLog("  ERP " + det);
                    });
                }

                if (resp.failed && resp.failed.length) {
                    $.each(resp.failed, function (_, f) {
                        agregarLog("  Fallo ack: " + (f.id || "?") + " — " + (f.error || f.message || "error"));
                    });
                }

                if (resp.ok || resp.partial) {
                    swal(
                        resp.ok ? "Procesado" : "Parcial",
                        resp.msg || "Operación completada",
                        resp.ok ? "success" : "warning"
                    );
                    buscarPendientes();
                } else {
                    swal("Error", resp.msg || "No se pudo procesar", "error");
                }
            })
            .fail(function () {
                agregarLog("Error de red al procesar gestiones");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.procesando = false;
                actualizarSeleccion();
                $(".btn-aplicar-una-gestion-vasco, .btn-rechazar-una-gestion-vasco").prop("disabled", false);
            });
    }

    function pedirMotivoRechazo(callback) {
        swal({
            title: "Motivo de rechazo",
            input: "text",
            inputPlaceholder: "Ej. Celular duplicado en ERP",
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

    $("#btnBuscarGestionVasco").on("click", buscarPendientes);

    $("#btnMarcarTodosGestionVasco").on("click", function () {
        $cuerpo.find(".chk-gestion-vasco").prop("checked", true);
        actualizarSeleccion();
    });

    $("#btnDesmarcarTodosGestionVasco").on("click", function () {
        $cuerpo.find(".chk-gestion-vasco").prop("checked", false);
        actualizarSeleccion();
    });

    $cuerpo.on("change click", ".chk-gestion-vasco", function () {
        setTimeout(actualizarSeleccion, 0);
    });

    $("#btnAplicarSeleccionGestionVasco").on("click", function () {
        var items = itemsSeleccionados("synced");
        if (!items.length) {
            return;
        }

        swal({
            title: "¿Aplicar gestiones?",
            text: "Se actualizará el ERP y se confirmará a Vasco para " + items.length + " registro(s).",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, aplicar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                procesarItems(items);
            }
        });
    });

    $cuerpo.on("click", ".btn-aplicar-una-gestion-vasco", function () {
        var $tr = $(this).closest("tr");
        var idx = parseInt($tr.attr("data-index"), 10);
        var item = itemDesdeIndice(idx);
        if (!item || item.id == null) {
            return;
        }

        swal({
            title: "¿Aplicar gestión?",
            text: "Actualizar ERP y confirmar a Vasco (ID " + item.id + ").",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                procesarItems([
                    {
                        id: parseInt(item.id, 10),
                        action: "synced",
                        vasco_item: item,
                    },
                ]);
            }
        });
    });

    $cuerpo.on("click", ".btn-rechazar-una-gestion-vasco", function () {
        var $tr = $(this).closest("tr");
        var idx = parseInt($tr.attr("data-index"), 10);
        var item = itemDesdeIndice(idx);
        if (!item || item.id == null) {
            return;
        }

        pedirMotivoRechazo(function (motivo) {
            procesarItems([
                {
                    id: parseInt(item.id, 10),
                    action: "rejected",
                    rejection_reason: motivo,
                    vasco_item: item,
                },
            ]);
        });
    });
});
