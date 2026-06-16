$(function () {
    if ($("#panelRendicionVascoCaja").length === 0) {
        return;
    }

    var $cuerpo = $("#cuerpoCobranzasVasco");
    var $log = $("#logRendicionVasco");
    var $boxResumen = $("#boxResumenCobranzasVasco");
    var $btnConfirmar = $("#btnConfirmarSeleccionVasco");
    var $refGlobal = $("#refGlobalVasco");
    var $chkRefGlobal = $("#chkUsarRefGlobalVasco");

    var estado = {
        traceId: null,
        items: [],
        buscando: false,
        confirmando: false,
    };

    function escHtml(valor) {
        return $("<div>").text(valor == null ? "" : String(valor)).html();
    }

    function fmtMoney(valor) {
        var n = parseFloat(valor);
        if (isNaN(n)) {
            return "—";
        }
        return "S/ " + n.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        var partes = [];
        if (c.code) {
            partes.push(String(c.code));
        }
        if (c.name) {
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

    function actualizarSeleccion() {
        var count = $cuerpo.find(".chk-cobranza-vasco:checked").length;
        $("#resumenSeleccionVasco").text(String(count));
        $btnConfirmar.prop("disabled", count === 0 || estado.confirmando);
    }

    function renderTabla(items) {
        estado.items = items || [];

        if (!estado.items.length) {
            $cuerpo.html(
                '<tr class="vasco-caja-empty"><td colspan="10" class="text-center text-muted">' +
                    "No hay cobranzas pendientes con los filtros indicados." +
                    "</td></tr>"
            );
            actualizarSeleccion();
            return;
        }

        var filas = [];
        $.each(estado.items, function (idx, item) {
            var code = item.code ? String(item.code) : "";
            var id = item.id != null ? String(item.id) : "";
            var ticket = item.ticket_code ? String(item.ticket_code) : "—";
            var notas = item.notes ? String(item.notes) : "—";

            filas.push(
                "<tr data-index=\"" +
                    idx +
                    "\" data-code=\"" +
                    escHtml(code) +
                    "\" data-id=\"" +
                    escHtml(id) +
                    "\">" +
                    '<td><input type="checkbox" class="chk-cobranza-vasco"></td>' +
                    "<td><strong>" +
                    escHtml(code || id) +
                    "</strong></td>" +
                    "<td>" +
                    fmtFecha(item.created_at) +
                    "</td>" +
                    "<td>" +
                    nombreCliente(item) +
                    "</td>" +
                    "<td>" +
                    docCliente(item) +
                    "</td>" +
                    "<td>" +
                    nombreVendedor(item) +
                    "</td>" +
                    '<td class="text-right">' +
                    fmtMoney(item.amount) +
                    "</td>" +
                    "<td>" +
                    escHtml(ticket) +
                    "</td>" +
                    "<td>" +
                    escHtml(notas) +
                    "</td>" +
                    '<td><button type="button" class="btn btn-success btn-xs btn-confirmar-una-vasco">' +
                    '<i class="fa fa-check"></i> Confirmar</button></td>' +
                    "</tr>"
            );
        });

        $cuerpo.html(filas.join(""));
        actualizarSeleccion();
    }

    function itemsSeleccionados() {
        var seleccion = [];
        $cuerpo.find(".chk-cobranza-vasco:checked").each(function () {
            var $tr = $(this).closest("tr");
            var idx = parseInt($tr.attr("data-index"), 10);
            var item = estado.items[idx];
            if (!item) {
                return;
            }
            var row = {};
            if (item.code) {
                row.code = String(item.code);
            }
            if (item.id != null) {
                row.id = parseInt(item.id, 10);
            }
            if ($chkRefGlobal.is(":checked")) {
                var ref = $.trim($refGlobal.val());
                if (ref !== "") {
                    row.external_reference = ref;
                }
            }
            seleccion.push(row);
        });
        return seleccion;
    }

    function itemDesdeFila($tr, externalRef) {
        var idx = parseInt($tr.attr("data-index"), 10);
        var item = estado.items[idx];
        if (!item) {
            return null;
        }
        var row = {};
        if (item.code) {
            row.code = String(item.code);
        }
        if (item.id != null) {
            row.id = parseInt(item.id, 10);
        }
        if (externalRef) {
            row.external_reference = externalRef;
        }
        return row;
    }

    function buscarPendientes() {
        if (estado.buscando) {
            return;
        }

        estado.buscando = true;
        $("#btnBuscarCobranzasVasco").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Consultando…');

        var params = {
            accion: "listar-pendientes",
            status: "pending_delivery",
            seller_username: $.trim($("#filtroVendedorVasco").val()),
            since: $.trim($("#filtroDesdeVasco").val()),
            limit: $("#filtroLimiteVasco").val(),
        };

        if (estado.traceId) {
            params.trace_id = estado.traceId;
        }

        $.getJSON("ajax/cuentas-corrientes/vasco-cobranzas.ajax.php", params)
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error consulta: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal("Error", resp && resp.msg ? resp.msg : "No se pudo consultar Vasco", "error");
                    return;
                }

                estado.traceId = resp.trace_id || estado.traceId;
                $("#resumenCountVasco").text(String(resp.count != null ? resp.count : 0));
                $("#resumenMontoVasco").text(fmtMoney(resp.total_amount));
                $("#resumenTraceVasco").text(estado.traceId || "—");
                $boxResumen.show();

                renderTabla(resp.items || []);
                agregarLog(
                    "Consulta OK — " +
                        (resp.count != null ? resp.count : 0) +
                        " pendiente(s), total " +
                        fmtMoney(resp.total_amount)
                );
            })
            .fail(function () {
                agregarLog("Error de red al consultar Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.buscando = false;
                $("#btnBuscarCobranzasVasco").prop("disabled", false).html('<i class="fa fa-refresh"></i> Consultar pendientes');
            });
    }

    function confirmarItems(items) {
        if (!items || !items.length || estado.confirmando) {
            return;
        }

        estado.confirmando = true;
        $btnConfirmar.prop("disabled", true);
        $(".btn-confirmar-una-vasco").prop("disabled", true);

        var payload = {
            trace_id: estado.traceId,
            delivered_by: window.vascoCajaUsuario || "caja.vascorp",
            items: items,
        };

        $.ajax({
            url: "ajax/cuentas-corrientes/vasco-cobranzas.ajax.php?accion=entregar",
            method: "POST",
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(payload),
        })
            .done(function (resp) {
                if (!resp) {
                    agregarLog("Error confirmación: respuesta vacía");
                    swal("Error", "Respuesta inválida del servidor", "error");
                    return;
                }

                if (resp.trace_id) {
                    estado.traceId = resp.trace_id;
                    $("#resumenTraceVasco").text(estado.traceId);
                }

                var entregadas = resp.delivered != null ? resp.delivered : 0;
                var yaEntregadas = resp.already_delivered != null ? resp.already_delivered : 0;
                var fallidos = resp.failed && resp.failed.length ? resp.failed.length : 0;

                agregarLog(
                    (resp.ok ? "Confirmación OK" : "Confirmación parcial") +
                        " — entregadas: " +
                        entregadas +
                        ", ya entregadas: " +
                        yaEntregadas +
                        ", fallidas: " +
                        fallidos
                );

                if (resp.failed && resp.failed.length) {
                    $.each(resp.failed, function (_, f) {
                        var det = (f.code || f.id || "?") + ": " + (f.error || f.message || "error");
                        agregarLog("  Fallo: " + det);
                    });
                }

                if (resp.ok || resp.partial) {
                    swal(
                        resp.ok ? "Confirmado" : "Parcial",
                        resp.msg || "Operación completada",
                        resp.ok ? "success" : "warning"
                    );
                    buscarPendientes();
                } else {
                    swal("Error", resp.msg || "No se pudo confirmar", "error");
                }
            })
            .fail(function () {
                agregarLog("Error de red al confirmar en Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.confirmando = false;
                actualizarSeleccion();
                $(".btn-confirmar-una-vasco").prop("disabled", false);
            });
    }

    $("#btnBuscarCobranzasVasco").on("click", buscarPendientes);

    $("#btnMarcarTodosVasco").on("click", function () {
        $cuerpo.find(".chk-cobranza-vasco").prop("checked", true);
        actualizarSeleccion();
    });

    $("#btnDesmarcarTodosVasco").on("click", function () {
        $cuerpo.find(".chk-cobranza-vasco").prop("checked", false);
        actualizarSeleccion();
    });

    $cuerpo.on("change", ".chk-cobranza-vasco", actualizarSeleccion);

    $chkRefGlobal.on("change", function () {
        var activo = $(this).is(":checked");
        $refGlobal.prop("disabled", !activo);
    });

    $("#btnConfirmarSeleccionVasco").on("click", function () {
        var items = itemsSeleccionados();
        if (!items.length) {
            return;
        }

        swal({
            title: "¿Confirmar rendición?",
            text: "Se marcarán " + items.length + " cobranza(s) como recibidas en Vasco.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, confirmar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                confirmarItems(items);
            }
        });
    });

    $cuerpo.on("click", ".btn-confirmar-una-vasco", function () {
        var $tr = $(this).closest("tr");
        var code = $tr.attr("data-code") || $tr.find("td:nth-child(2)").text();

        swal({
            title: "¿Confirmar cobranza?",
            text: "Marcar " + code + " como recibida en empresa.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Confirmar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (result.value) {
                var ref = $chkRefGlobal.is(":checked") ? $.trim($refGlobal.val()) : "";
                var item = itemDesdeFila($tr, ref);
                if (item) {
                    confirmarItems([item]);
                }
            }
        });
    });
});
