$(function () {
    if ($("#panelRendicionVascoCaja").length === 0) {
        return;
    }

    var $cuerpo = $("#cuerpoCobranzasVasco");
    var $log = $("#logRendicionVasco");
    var $boxResumen = $("#boxResumenCobranzasVasco");
    var $btnConfirmar = $("#btnConfirmarSeleccionVasco");
    var $btnAnular = $("#btnAnularSeleccionVasco");
    var $refGlobal = $("#refGlobalVasco");
    var $chkRefGlobal = $("#chkUsarRefGlobalVasco");
    var $filtroVendedor = $("#filtroVendedorVasco");
    var $boxTotalesVendedor = $("#resumenPorVendedorVasco");
    var $gridTotalesVendedor = $("#cuerpoTotalesVendedorVasco");
    var $resumenFiltro = $("#resumenFiltroVasco");

    var estado = {
        traceId: null,
        itemsTodos: [],
        items: [],
        buscando: false,
        confirmando: false,
        anulando: false,
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

    function sellerInfo(item) {
        var s = (item && item.seller) || {};
        var username = s.username ? String(s.username).trim() : "";
        var name = s.name ? String(s.name).trim() : "";
        var id = s.id != null ? String(s.id) : "";
        var key = username || (id ? "id:" + id : "") || (name ? "name:" + name : "sin-vendedor");
        var label = name || username || (id ? "ID " + id : "Sin vendedor");
        if (name && username) {
            label = name + " · " + username;
        } else if (!name && username) {
            label = username;
        }
        return {
            key: key,
            username: username,
            name: name,
            label: label,
            displayName: name || username || (id ? "ID " + id : "Sin vendedor"),
            displayDoc: username && name ? username : "",
            sort: (name || username || "").toLocaleLowerCase("es"),
        };
    }

    function celdaVendedor(item) {
        var info = sellerInfo(item);
        var html = '<div class="vasco-caja-vendedor-celda"><span>' + escHtml(info.displayName) + "</span>";
        if (info.displayDoc) {
            html += "<small>" + escHtml(info.displayDoc) + "</small>";
        }
        html += "</div>";
        return html;
    }

    function compararPorVendedor(a, b) {
        var sa = sellerInfo(a);
        var sb = sellerInfo(b);
        if (sa.sort < sb.sort) {
            return -1;
        }
        if (sa.sort > sb.sort) {
            return 1;
        }
        var fa = a.created_at ? String(a.created_at) : "";
        var fb = b.created_at ? String(b.created_at) : "";
        if (fa < fb) {
            return -1;
        }
        if (fa > fb) {
            return 1;
        }
        return 0;
    }

    function ordenarPorVendedor(items) {
        return (items || []).slice().sort(compararPorVendedor);
    }

    function resumirPorVendedor(items) {
        var mapa = {};
        var lista = [];

        $.each(items || [], function (_, item) {
            var info = sellerInfo(item);
            if (!mapa[info.key]) {
                mapa[info.key] = {
                    key: info.key,
                    username: info.username,
                    label: info.label,
                    displayName: info.displayName,
                    displayDoc: info.displayDoc,
                    sort: info.sort,
                    count: 0,
                    total: 0,
                };
                lista.push(mapa[info.key]);
            }
            mapa[info.key].count += 1;
            var monto = parseFloat(item.amount);
            if (!isNaN(monto)) {
                mapa[info.key].total += monto;
            }
        });

        lista.sort(function (a, b) {
            if (a.sort < b.sort) {
                return -1;
            }
            if (a.sort > b.sort) {
                return 1;
            }
            return 0;
        });

        return lista;
    }

    function etiquetaFiltroActivo(sellerKey) {
        if (!sellerKey) {
            return "Todos";
        }
        var resumen = resumirPorVendedor(estado.itemsTodos);
        for (var i = 0; i < resumen.length; i++) {
            if (resumen[i].key === sellerKey) {
                return resumen[i].displayName;
            }
        }
        return "Filtrado";
    }

    function actualizarSelectVendedores(items, selectedKey) {
        var resumen = resumirPorVendedor(items);
        var valorActual = selectedKey != null ? selectedKey : $filtroVendedor.val();
        var opciones = ['<option value="">Todos los vendedores (' + resumen.length + ")</option>"];

        $.each(resumen, function (_, row) {
            opciones.push(
                '<option value="' +
                    escHtml(row.key) +
                    '">' +
                    escHtml(row.label) +
                    " — " +
                    fmtMoney(row.total) +
                    " (" +
                    row.count +
                    ")</option>"
            );
        });

        if (!resumen.length) {
            $filtroVendedor.prop("disabled", true).html(
                '<option value="">Sin vendedores con cobranza</option>'
            );
            return;
        }

        $filtroVendedor.prop("disabled", false).html(opciones.join(""));
        var existe = false;
        if (valorActual) {
            $filtroVendedor.find("option").each(function () {
                if ($(this).val() === valorActual) {
                    existe = true;
                    return false;
                }
            });
        }
        $filtroVendedor.val(existe ? valorActual : "");
    }

    function renderTotalesPorVendedor(items, activeKey) {
        var resumen = resumirPorVendedor(items);

        if (!resumen.length) {
            $boxTotalesVendedor.hide();
            $gridTotalesVendedor.empty();
            return;
        }

        var cards = [];
        $.each(resumen, function (_, row) {
            var active = activeKey && row.key === activeKey ? " is-active" : "";
            cards.push(
                '<button type="button" class="vasco-caja-seller-card' +
                    active +
                    '" data-seller-key="' +
                    escHtml(row.key) +
                    '" title="Filtrar por este vendedor">' +
                    '<span class="vasco-caja-seller-name">' +
                    escHtml(row.displayName) +
                    "</span>" +
                    (row.displayDoc
                        ? '<span class="vasco-caja-seller-doc">' + escHtml(row.displayDoc) + "</span>"
                        : "") +
                    '<span class="vasco-caja-seller-meta">' +
                    '<span class="vasco-caja-seller-count">' +
                    row.count +
                    (row.count === 1 ? " cobranza" : " cobranzas") +
                    "</span>" +
                    '<span class="vasco-caja-seller-amount">' +
                    fmtMoney(row.total) +
                    "</span>" +
                    "</span>" +
                    "</button>"
            );
        });

        $gridTotalesVendedor.html(cards.join(""));
        $boxTotalesVendedor.show();
    }

    function actualizarResumenVista(items, sellerKey) {
        var total = 0;
        $.each(items || [], function (_, item) {
            var monto = parseFloat(item.amount);
            if (!isNaN(monto)) {
                total += monto;
            }
        });
        $("#resumenCountVasco").text(String((items || []).length));
        $("#resumenMontoVasco").text(fmtMoney(total));
        $resumenFiltro.text(etiquetaFiltroActivo(sellerKey));
    }

    function actualizarSeleccion() {
        var count = $cuerpo.find(".chk-cobranza-vasco:checked").length;
        var ocupado = estado.confirmando || estado.anulando;
        $("#resumenSeleccionVasco").text(String(count));
        $btnConfirmar.prop("disabled", count === 0 || ocupado);
        $btnAnular.prop("disabled", count === 0 || ocupado);

        $cuerpo.find("tr[data-index]").each(function () {
            var checked = $(this).find(".chk-cobranza-vasco").is(":checked");
            $(this).toggleClass("vasco-caja-row-checked", checked);
        });
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
                '<tr data-index="' +
                    idx +
                    '" data-code="' +
                    escHtml(code) +
                    '" data-id="' +
                    escHtml(id) +
                    '">' +
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
                    celdaVendedor(item) +
                    "</td>" +
                    '<td class="text-right vasco-caja-monto">' +
                    fmtMoney(item.amount) +
                    "</td>" +
                    "<td>" +
                    escHtml(ticket) +
                    "</td>" +
                    "<td>" +
                    escHtml(notas) +
                    "</td>" +
                    '<td class="vasco-caja-acciones">' +
                    '<button type="button" class="btn btn-success btn-xs btn-confirmar-una-vasco">' +
                    '<i class="fa fa-check"></i> Confirmar</button> ' +
                    '<button type="button" class="btn btn-danger btn-xs btn-anular-una-vasco">' +
                    '<i class="fa fa-ban"></i> Anular</button>' +
                    "</td>" +
                    "</tr>"
            );
        });

        $cuerpo.html(filas.join(""));
        actualizarSeleccion();
    }

    function itemsFiltradosPorVendedor(items, sellerKey) {
        if (!sellerKey) {
            return ordenarPorVendedor(items);
        }
        var filtrados = [];
        $.each(items || [], function (_, item) {
            if (sellerInfo(item).key === sellerKey) {
                filtrados.push(item);
            }
        });
        return ordenarPorVendedor(filtrados);
    }

    function aplicarFiltroVendedor() {
        var sellerKey = $filtroVendedor.val() || "";
        var filtrados = itemsFiltradosPorVendedor(estado.itemsTodos, sellerKey);
        actualizarResumenVista(filtrados, sellerKey);
        renderTotalesPorVendedor(estado.itemsTodos, sellerKey);
        renderTabla(filtrados);
    }

    function seleccionarVendedor(sellerKey) {
        var actual = $filtroVendedor.val() || "";
        var siguiente = actual === sellerKey ? "" : sellerKey || "";
        $filtroVendedor.val(siguiente);
        aplicarFiltroVendedor();
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

    function itemDesdeFila($tr, extra) {
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
        if (extra && typeof extra === "object") {
            if (extra.external_reference) {
                row.external_reference = extra.external_reference;
            }
            if (extra.reason) {
                row.reason = String(extra.reason).substring(0, 255);
            }
        } else if (extra) {
            row.external_reference = extra;
        }
        return row;
    }

    function pedirMotivoAnulacion(callback) {
        swal({
            title: "Motivo de anulación",
            text: "Obligatorio. Quedará en la auditoría de Vasco.",
            input: "text",
            inputPlaceholder: "Ej. Monto incorrecto registrado por vendedor",
            showCancelButton: true,
            confirmButtonText: "Anular",
            confirmButtonColor: "#dd4b39",
            cancelButtonText: "Volver",
            inputValidator: function (value) {
                if (!value || !$.trim(value)) {
                    return "Indique un motivo";
                }
                if ($.trim(value).length > 255) {
                    return "Máximo 255 caracteres";
                }
                return null;
            },
        }).then(function (result) {
            if (result.value) {
                callback($.trim(result.value).substring(0, 255));
            }
        });
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
                estado.itemsTodos = ordenarPorVendedor(resp.items || []);
                $("#resumenTraceVasco").text(estado.traceId || "—").attr("title", estado.traceId || "");
                $boxResumen.show();

                actualizarSelectVendedores(estado.itemsTodos, "");
                aplicarFiltroVendedor();

                agregarLog(
                    "Consulta OK — " +
                        estado.itemsTodos.length +
                        " pendiente(s), total " +
                        fmtMoney(resp.total_amount) +
                        ", " +
                        resumirPorVendedor(estado.itemsTodos).length +
                        " vendedor(es)"
                );
            })
            .fail(function () {
                agregarLog("Error de red al consultar Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.buscando = false;
                $("#btnBuscarCobranzasVasco").prop("disabled", false).html('<i class="fa fa-refresh"></i> Consultar');
            });
    }

    function confirmarItems(items) {
        if (!items || !items.length || estado.confirmando || estado.anulando) {
            return;
        }

        estado.confirmando = true;
        $btnConfirmar.prop("disabled", true);
        $btnAnular.prop("disabled", true);
        $(".btn-confirmar-una-vasco, .btn-anular-una-vasco").prop("disabled", true);

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
                    $("#resumenTraceVasco").text(estado.traceId).attr("title", estado.traceId);
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
                $(".btn-confirmar-una-vasco, .btn-anular-una-vasco").prop("disabled", false);
            });
    }

    function anularItems(items) {
        if (!items || !items.length || estado.confirmando || estado.anulando) {
            return;
        }

        estado.anulando = true;
        $btnConfirmar.prop("disabled", true);
        $btnAnular.prop("disabled", true);
        $(".btn-confirmar-una-vasco, .btn-anular-una-vasco").prop("disabled", true);

        var payload = {
            cancelled_by: window.vascoCajaUsuario || "caja.vascorp",
            items: items,
        };

        $.ajax({
            url: "ajax/cuentas-corrientes/vasco-cobranzas.ajax.php?accion=anular",
            method: "POST",
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(payload),
        })
            .done(function (resp) {
                if (!resp) {
                    agregarLog("Error anulación: respuesta vacía");
                    swal("Error", "Respuesta inválida del servidor", "error");
                    return;
                }

                if (resp.trace_id) {
                    estado.traceId = resp.trace_id;
                    $("#resumenTraceVasco").text(estado.traceId).attr("title", estado.traceId);
                }

                var anuladas = resp.cancelled != null ? resp.cancelled : 0;
                var yaAnuladas = resp.already_cancelled != null ? resp.already_cancelled : 0;
                var fallidos = resp.failed && resp.failed.length ? resp.failed.length : 0;

                agregarLog(
                    (resp.ok ? "Anulación OK" : "Anulación parcial") +
                        " — anuladas: " +
                        anuladas +
                        ", ya anuladas: " +
                        yaAnuladas +
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
                        resp.ok ? "Anulado" : "Parcial",
                        resp.msg || "Operación completada",
                        resp.ok ? "success" : "warning"
                    );
                    buscarPendientes();
                } else {
                    swal("Error", resp.msg || "No se pudo anular", "error");
                }
            })
            .fail(function () {
                agregarLog("Error de red al anular en Vasco");
                swal("Error", "No se pudo conectar con el servidor", "error");
            })
            .always(function () {
                estado.anulando = false;
                actualizarSeleccion();
                $(".btn-confirmar-una-vasco, .btn-anular-una-vasco").prop("disabled", false);
            });
    }

    $("#btnBuscarCobranzasVasco").on("click", buscarPendientes);

    $filtroVendedor.on("change", function () {
        if (!estado.itemsTodos.length) {
            return;
        }
        aplicarFiltroVendedor();
    });

    $gridTotalesVendedor.on("click", ".vasco-caja-seller-card", function () {
        seleccionarVendedor($(this).attr("data-seller-key") || "");
    });

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
        if (activo) {
            $refGlobal.focus();
        }
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

    $("#btnAnularSeleccionVasco").on("click", function () {
        var items = itemsSeleccionados();
        if (!items.length) {
            return;
        }

        pedirMotivoAnulacion(function (motivo) {
            var conMotivo = [];
            $.each(items, function (_, item) {
                var row = $.extend({}, item);
                row.reason = motivo;
                delete row.external_reference;
                conMotivo.push(row);
            });
            anularItems(conMotivo);
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
                var item = itemDesdeFila($tr, ref ? { external_reference: ref } : null);
                if (item) {
                    confirmarItems([item]);
                }
            }
        });
    });

    $cuerpo.on("click", ".btn-anular-una-vasco", function () {
        var $tr = $(this).closest("tr");
        var code = $tr.attr("data-code") || $tr.find("td:nth-child(2)").text();

        pedirMotivoAnulacion(function (motivo) {
            var item = itemDesdeFila($tr, { reason: motivo });
            if (item) {
                anularItems([item]);
            }
        });
    });
});
