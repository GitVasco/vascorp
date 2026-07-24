(function ($) {
    "use strict";

    if (!$("#ecPage").length) {
        return;
    }

    var API = "ajax/cuentas-corrientes/estado-cuenta.ajax.php";
    var state = {
        modo: null, // cliente | grupo
        cliente: null,
        grupo: null,
        filtroDocs: "pendiente",
        tablaDocs: null,
        clientesCargados: false,
        conColumnaLocal: false,
        // Si llegó por un cliente con grupo:
        origenCliente: null, // codigo
        origenNombre: null,
        soloLocal: false,
        _syncingSelects: false,
    };

    function fmtMoney(n) {
        var v = Number(n) || 0;
        return (
            "S/ " +
            v.toLocaleString("es-PE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    }

    function esc(s) {
        return $("<div>").text(s == null ? "" : String(s)).html();
    }

    /** Celda fecha con data-order ISO para que DataTables ordene cronológicamente. */
    function celdaFecha(texto, sortKey) {
        var shown = texto ? esc(texto) : "—";
        var order = sortKey ? esc(sortKey) : "";
        return '<td data-order="' + order + '">' + shown + "</td>";
    }

    function post(data) {
        return $.ajax({
            url: API,
            method: "POST",
            data: data,
            dataType: "json",
        });
    }

    function filtroPayload() {
        var data = { estado: "", solo_vencidos: 0 };
        if (state.filtroDocs === "vencido") {
            data.solo_vencidos = 1;
            data.estado = "pendiente";
        } else if (state.filtroDocs) {
            data.estado = state.filtroDocs;
        }
        return data;
    }

    function setSelects(cliente, grupo) {
        state._syncingSelects = true;
        if (typeof cliente !== "undefined") {
            $("#ecFiltroCliente").val(cliente || "").selectpicker("refresh");
        }
        if (typeof grupo !== "undefined") {
            $("#ecFiltroGrupo").val(grupo || "").selectpicker("refresh");
        }
        state._syncingSelects = false;
    }

    function showEmpty() {
        $("#ecEmpty").removeClass("hidden");
        $("#ecContenido").addClass("hidden");
        $("#ecPanelDocs").addClass("hidden");
                $("#btnEcPagos").prop("disabled", true);
        $("#btnEcSoloLocal, #btnEcTodoGrupo").addClass("hidden");
        destruirTablaDocs();
        state.modo = null;
        state.cliente = null;
        state.grupo = null;
        state.conColumnaLocal = false;
        state.origenCliente = null;
        state.origenNombre = null;
        state.soloLocal = false;
        $("#ecPage").removeClass("ec-page--grupo ec-page--cliente");
    }

    function showContent() {
        $("#ecEmpty").addClass("hidden");
        $("#ecContenido").removeClass("hidden");
        $("#btnEcPagos").prop("disabled", false);
    }

    var RIESGO_LABEL = {
        critico: "Crítico",
        alto: "Alto",
        medio: "Medio",
        bajo: "Bajo",
        ok: "OK",
        sin_dato: "Sin línea",
    };

    function setCredito(c) {
        c = c || {};
        var linea = Number(c.linea_referencia) || 0;
        var aprobada = c.linea_aprobada;
        var mostrarLinea = aprobada !== null && aprobada !== undefined && Number(aprobada) > 0
            ? Number(aprobada)
            : linea;
        $("#ecKpiLinea").text(mostrarLinea > 0 ? fmtMoney(mostrarLinea) : "—");
        $("#ecKpiLineaEtiqueta").text(c.etiqueta_linea || "");
        $("#ecKpiCupo").text(fmtMoney(c.cupo_disponible));
        var uso = Number(c.utilizacion_pct) || 0;
        $("#ecKpiUso").text(uso.toFixed(1) + "%");
        $("#ecKpiUsoBar").css("width", Math.min(100, Math.max(0, uso)) + "%");
        var $uso = $("#ecKpis .ec-chip--uso");
        $uso.removeClass("is-ok is-medio is-alto is-critico");
        var usoClass = "is-ok";
        if (uso >= 100 || c.cupo_agotado) usoClass = "is-critico";
        else if (uso >= 80) usoClass = "is-alto";
        else if (uso >= 50) usoClass = "is-medio";
        $uso.addClass(usoClass);

        var riesgo = c.riesgo || "sin_dato";
        var $badge = $("#ecRiesgoBadge");
        $badge
            .removeClass(
                "ec-riesgo-badge--critico ec-riesgo-badge--alto ec-riesgo-badge--medio ec-riesgo-badge--bajo ec-riesgo-badge--ok ec-riesgo-badge--sin_dato"
            )
            .addClass("ec-riesgo-badge--" + riesgo)
            .text(RIESGO_LABEL[riesgo] || riesgo);
    }

    function setKpis(r, credito) {
        r = r || {};
        $("#ecKpiVenta").text(fmtMoney(r.total_venta));
        $("#ecKpiDeuda").text(fmtMoney(r.total_deuda));
        $("#ecKpiVencido").text(fmtMoney(r.total_vencido));
        var mora = Number(r.pct_mora) || 0;
        $("#ecKpiMoraPct").text(mora > 0 ? mora.toFixed(1) + "%" : "");
        $("#ecKpiDocs").text(String(r.docs_pendientes || 0));
        var sub = "";
        if (r.docs_vencidos) {
            sub = r.docs_vencidos + " venc.";
        }
        if (r.total_locales) {
            sub += (sub ? " · " : "") + r.total_locales + " loc.";
        }
        $("#ecKpiDocsSub").text(sub);
        $("#ecKpiProtesta").text(String(r.docs_protestados || 0));
        $("#ecKpis .ec-chip--protesta").toggleClass("is-alert", (r.docs_protestados || 0) > 0);
        setCredito(credito);
    }

    function actualizarContexto() {
        $("#btnEcSoloLocal, #btnEcTodoGrupo").addClass("hidden");

        if (state.modo === "grupo" && state.origenCliente && !state.soloLocal) {
            $("#btnEcSoloLocal").removeClass("hidden");
            return;
        }

        if (state.modo === "cliente" && state.grupo && state.origenCliente) {
            $("#btnEcTodoGrupo").removeClass("hidden");
        }
    }

    function cargarClientes(force) {
        if (state.clientesCargados && !force) {
            return $.Deferred().resolve().promise();
        }
        var $sel = $("#ecFiltroCliente");
        $sel.prop("disabled", true).selectpicker("refresh");
        return post({ accion: "buscarClientes", q: "" })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    console.error("estado-cuenta: buscarClientes", resp);
                    return;
                }
                var actual = $sel.val();
                $sel.find("option").remove();
                $sel.append('<option value="">Seleccionar cliente</option>');
                (resp.clientes || []).forEach(function (c) {
                    var label =
                        c.codigo +
                        " — " +
                        c.nombre +
                        (c.documento ? " — " + c.documento : "");
                    if (c.grupo_nombre) {
                        label += " · " + c.grupo_nombre;
                    }
                    var $opt = $("<option>")
                        .val(c.codigo)
                        .text(label)
                        .attr("data-grupo", c.grupo || "")
                        .attr("data-grupo-nombre", c.grupo_nombre || "")
                        .attr("data-nombre", c.nombre || "");
                    $sel.append($opt);
                });
                if (actual) {
                    $sel.val(actual);
                }
                state.clientesCargados = true;
            })
            .fail(function (xhr) {
                console.error("estado-cuenta: error AJAX clientes", xhr && xhr.status, xhr && xhr.responseText);
            })
            .always(function () {
                $sel.prop("disabled", false).selectpicker("refresh");
            });
    }

    function destruirTablaDocs() {
        if ($.fn.DataTable.isDataTable("#ecTablaDocs")) {
            try {
                $("#ecTablaDocs").DataTable().clear().destroy();
            } catch (e) {}
        }
        state.tablaDocs = null;
        $("#ecTablaDocs thead").empty();
        $("#ecTablaDocs tbody").empty();
    }

    function theadHtml(conLocal) {
        var localTh = conLocal ? "<th>Local</th>" : "";
        return (
            "<tr>" +
            localTh +
            "<th>Tipo</th><th>Nro</th><th>Origen</th>" +
            "<th>Emisión</th><th>Vencimiento</th>" +
            '<th class="ec-monto">Monto</th><th class="ec-monto">Saldo</th>' +
            '<th>Últ. pago</th><th class="text-center">Dif</th>' +
            '<th class="text-center">Prot.</th><th class="text-center">Renov.</th>' +
            "<th>Nro. único</th><th>Vend.</th><th>Estado</th><th></th>" +
            "</tr>"
        );
    }

    function celdaLocal(d) {
        var esOrigen =
            state.origenCliente &&
            String(d.local_codigo) === String(state.origenCliente);
        var cod = d.local_codigo ? esc(d.local_codigo) : "";
        var nom = esc(d.local_nombre || "");
        return (
            '<td class="ec-col-local">' +
            (cod ? '<span class="ec-local-cod">' + cod + "</span>" : "") +
            (cod && nom ? " " : "") +
            '<span class="ec-local-nombre">' +
            (nom || cod) +
            "</span>" +
            (esOrigen ? ' <span class="ec-local-pill">Consultado</span>' : "") +
            "</td>"
        );
    }

    function filaDocumento(d, conLocal) {
        var estadoHtml =
            d.estado === "PENDIENTE"
                ? '<span class="ec-badge-pendiente">PENDIENTE</span>'
                : '<span class="ec-badge-cancelado">CANCELADO</span>';
        if (d.es_vencido) {
            estadoHtml += ' <span class="ec-badge-vencido">VENCIDO</span>';
        }
        var dif = Number(d.diferencia) || 0;
        var difClass = dif > 0 ? "ec-dif-pos" : dif < 0 ? "ec-dif-neg" : "";
        var rowClass = d.es_vencido ? " ec-row-vencido" : "";
        if (
            conLocal &&
            state.origenCliente &&
            String(d.local_codigo) === String(state.origenCliente)
        ) {
            rowClass += " ec-row-origen";
        }
        return (
            '<tr class="' +
            rowClass +
            '">' +
            (conLocal ? celdaLocal(d) : "") +
            "<td>" +
            esc(d.tipo_doc) +
            "</td>" +
            "<td>" +
            esc(d.num_cta) +
            "</td>" +
            "<td>" +
            esc(d.doc_origen) +
            "</td>" +
            celdaFecha(d.fecha, d.fecha_sort) +
            celdaFecha(d.fecha_ven, d.fecha_ven_sort) +
            '<td class="ec-monto">' +
            fmtMoney(d.monto) +
            "</td>" +
            '<td class="ec-monto"><strong>' +
            fmtMoney(d.saldo) +
            "</strong></td>" +
            celdaFecha(d.ult_pago, d.ult_pago_sort) +
            '<td class="text-center ' +
            difClass +
            '">' +
            dif +
            "</td>" +
            '<td class="text-center">' +
            (d.protesta ? '<span class="ec-si">SI</span>' : '<span class="ec-no">NO</span>') +
            "</td>" +
            '<td class="text-center">' +
            (d.renovacion ? '<span class="ec-si">SI</span>' : '<span class="ec-no">NO</span>') +
            "</td>" +
            "<td>" +
            esc(d.num_unico || "") +
            "</td>" +
            "<td>" +
            esc(d.vendedor || "") +
            "</td>" +
            "<td>" +
            estadoHtml +
            "</td>" +
            "<td>" +
            '<button type="button" class="btn btn-xs btn-primary btnEcVerCancel" ' +
            'data-tipo="' +
            esc(d.tipo_doc) +
            '" data-num="' +
            esc(d.num_cta) +
            '" title="Ver cancelaciones"><i class="fa fa-eye"></i></button>' +
            "</td>" +
            "</tr>"
        );
    }

    function pintarTabla(docs, conLocal) {
        destruirTablaDocs();
        state.conColumnaLocal = !!conLocal;
        $("#ecTablaDocs thead").html(theadHtml(conLocal));
        var $tb = $("#ecTablaDocs tbody");
        $tb.empty();

        var lista = docs || [];
        $("#ecDocsContador").text(
            lista.length + " documento" + (lista.length === 1 ? "" : "s")
        );

        lista.forEach(function (d) {
            $tb.append(filaDocumento(d, conLocal));
        });

        // Columna Vencimiento: con Local=5, sin Local=4
        var idxVen = conLocal ? 5 : 4;

        state.tablaDocs = $("#ecTablaDocs").DataTable({
            order: [[idxVen, "desc"]],
            pageLength: 25,
            lengthMenu: [
                [25, 50, 100, -1],
                [25, 50, 100, "Todos"],
            ],
            language: {
                sProcessing: "Procesando...",
                sLengthMenu: "Mostrar _MENU_ registros",
                sZeroRecords: "No se encontraron documentos",
                sEmptyTable: "Sin documentos para este filtro",
                sInfo: "Mostrando _START_ a _END_ de _TOTAL_",
                sInfoEmpty: "Mostrando 0 a 0 de 0",
                sInfoFiltered: "(filtrado de _MAX_)",
                sSearch: "Buscar:",
                oPaginate: {
                    sFirst: "Primero",
                    sLast: "Último",
                    sNext: "Siguiente",
                    sPrevious: "Anterior",
                },
            },
            columnDefs: [{ orderable: false, targets: -1 }],
            createdRow: function (row) {
                if ($(row).hasClass("ec-row-origen")) {
                    // mantener clase post-datatable
                }
            },
        });
    }

    function aplanarLocales(locales) {
        var docs = [];
        (locales || []).forEach(function (loc) {
            (loc.documentos || []).forEach(function (d) {
                var row = $.extend({}, d);
                row.local_codigo = loc.codigo;
                row.local_nombre = loc.nombre;
                docs.push(row);
            });
        });
        return docs;
    }

    function cargarDocumentosCliente(codigo) {
        var data = $.extend({ accion: "documentos", cliente: codigo }, filtroPayload());
        $("#ecPanelDocs").removeClass("hidden");
        post(data).done(function (resp) {
            pintarTabla((resp && resp.ok && resp.documentos) || [], false);
            actualizarContexto();
        });
    }

    function cargarDocumentosGrupo(codigoGrupo, opts) {
        opts = opts || {};
        var data = $.extend({ accion: "desgloseGrupo", grupo: codigoGrupo }, filtroPayload());
        $("#ecPanelDocs").removeClass("hidden");

        post(data).done(function (resp) {
            if (!resp || !resp.ok) {
                alert((resp && resp.msg) || "No se pudo cargar el grupo");
                return;
            }

            state.modo = "grupo";
            state.grupo = resp.grupo.codigo;
            state.soloLocal = false;
            state.cliente = state.origenCliente || null;

            showContent();
            $("#ecPage").removeClass("ec-page--cliente").addClass("ec-page--grupo");

            $("#ecSnapModo").html('<i class="fa fa-sitemap"></i> Grupo empresarial');
            $("#ecSnapTitulo").text(resp.grupo.nombre);

            var meta =
                "Código: " +
                resp.grupo.codigo +
                " · " +
                (resp.resumen.total_locales || 0) +
                " locales · documentos unificados";
            if (state.origenCliente) {
                meta +=
                    ' · desde <span class="ec-snap-origen">' +
                    esc(state.origenNombre || state.origenCliente) +
                    "</span>";
            }
            $("#ecSnapMeta").html(meta);

            setKpis(resp.resumen, resp.credito);
            pintarTabla(aplanarLocales(resp.locales || []), true);
            actualizarContexto();

            // Sync selects: mantener cliente si vino de ahí
            if (opts.fromCliente && state.origenCliente) {
                setSelects(state.origenCliente, codigoGrupo);
            } else if (!opts.keepCliente) {
                setSelects("", codigoGrupo);
                // si abrió grupo directo, limpiar origen
                if (!opts.fromCliente) {
                    state.origenCliente = null;
                    state.origenNombre = null;
                    actualizarContexto();
                }
            } else {
                setSelects(undefined, codigoGrupo);
            }
        });
    }

    function mostrarSoloLocal() {
        if (!state.origenCliente) return;
        state.soloLocal = true;
        state.modo = "cliente";
        state.cliente = state.origenCliente;

        post({ accion: "resumenCliente", cliente: state.origenCliente }).done(function (resp) {
            if (!resp || !resp.ok) return;

            showContent();
            $("#ecPage").removeClass("ec-page--grupo").addClass("ec-page--cliente");
            $("#ecSnapModo").html('<i class="fa fa-user"></i> Local del grupo');
            $("#ecSnapTitulo").text(resp.cliente.nombre);
            var meta = [resp.cliente.codigo];
            if (resp.cliente.documento) meta.push("Doc: " + resp.cliente.documento);
            var metaHtml = esc(meta.join(" · "));
            if (resp.cliente.grupo) {
                metaHtml +=
                    ' <span class="ec-grupo-tag"><i class="fa fa-sitemap"></i> ' +
                    esc(resp.cliente.grupo_nombre || resp.cliente.grupo) +
                    "</span>";
                state.grupo = resp.cliente.grupo;
            }
            $("#ecSnapMeta").html(metaHtml);
            setKpis(resp.resumen, resp.credito);
            cargarDocumentosCliente(resp.cliente.codigo);
            setSelects(state.origenCliente, state.grupo || "");
            actualizarContexto();
        });
    }

    function cargarCliente(codigo) {
        if (!codigo) return;

        post({ accion: "resumenCliente", cliente: codigo }).done(function (resp) {
            if (!resp || !resp.ok) {
                alert((resp && resp.msg) || "No se pudo cargar el cliente");
                return;
            }

            // Si pertenece a grupo → abrir consolidado automático
            if (resp.cliente.grupo) {
                state.origenCliente = resp.cliente.codigo;
                state.origenNombre = resp.cliente.nombre;
                state.grupo = resp.cliente.grupo;
                state.cliente = resp.cliente.codigo;
                state.soloLocal = false;
                cargarDocumentosGrupo(resp.cliente.grupo, { fromCliente: true });
                return;
            }

            // Cliente sin grupo
            state.modo = "cliente";
            state.cliente = resp.cliente.codigo;
            state.grupo = null;
            state.origenCliente = null;
            state.origenNombre = null;
            state.soloLocal = false;

            showContent();
            $("#ecPage").removeClass("ec-page--grupo").addClass("ec-page--cliente");
            $("#ecSnapModo").html('<i class="fa fa-user"></i> Cliente');
            $("#ecSnapTitulo").text(resp.cliente.nombre);

            var meta = [];
            meta.push(resp.cliente.codigo);
            if (resp.cliente.documento) meta.push("Doc: " + resp.cliente.documento);
            if (resp.cliente.telefono) meta.push("Tel: " + resp.cliente.telefono);
            $("#ecSnapMeta").html(esc(meta.join(" · ")) || "—");

            setKpis(resp.resumen, resp.credito);
            cargarDocumentosCliente(resp.cliente.codigo);
            setSelects(codigo, "");
            actualizarContexto();
        });
    }

    function cargarGrupo(codigo) {
        if (!codigo) return;
        // Apertura directa de grupo: sin origen de cliente
        state.origenCliente = null;
        state.origenNombre = null;
        state.soloLocal = false;
        cargarDocumentosGrupo(codigo, { fromCliente: false });
    }

    function refrescarDocs() {
        if (state.modo === "grupo" && state.grupo && !state.soloLocal) {
            cargarDocumentosGrupo(state.grupo, {
                fromCliente: !!state.origenCliente,
                keepCliente: !!state.origenCliente,
            });
        } else if (state.cliente || state.origenCliente) {
            cargarDocumentosCliente(state.cliente || state.origenCliente);
        }
    }

    function limpiar() {
        setSelects("", "");
        $("#ecDocsFiltros .btn").removeClass("active");
        $('#ecDocsFiltros .btn[data-filtro="pendiente"]').addClass("active");
        state.filtroDocs = "pendiente";
        showEmpty();
    }

    $("#ecFiltroCliente").on("changed.bs.select", function () {
        if (state._syncingSelects) return;
        var codigo = $(this).val();
        if (!codigo) {
            if (state.modo === "grupo" && state.grupo && !state.origenCliente) {
                return;
            }
            if (!state.grupo) {
                showEmpty();
            }
            return;
        }
        cargarCliente(codigo);
    });

    $("#ecFiltroGrupo").on("changed.bs.select", function () {
        if (state._syncingSelects) return;
        var codigo = $(this).val();
        if (!codigo) {
            if (!state.cliente && !state.origenCliente) {
                showEmpty();
            }
            return;
        }
        cargarGrupo(codigo);
    });

    $("#btnEcLimpiar").on("click", limpiar);

    $("#btnEcSoloLocal").on("click", function () {
        mostrarSoloLocal();
    });

    $("#btnEcTodoGrupo").on("click", function () {
        if (!state.grupo) return;
        state.soloLocal = false;
        cargarDocumentosGrupo(state.grupo, {
            fromCliente: !!state.origenCliente,
            keepCliente: !!state.origenCliente,
        });
    });

    $("#ecDocsFiltros").on("click", ".btn", function () {
        $("#ecDocsFiltros .btn").removeClass("active");
        $(this).addClass("active");
        state.filtroDocs = $(this).data("filtro") || "";
        refrescarDocs();
    });

    $("#ecTablaDocs").on("click", ".btnEcVerCancel", function () {
        var tipo = $(this).data("tipo");
        var num = $(this).data("num");
        $("#modalEcCancelDoc").text(tipo + " / " + num);
        $("#ecTablaCancel tbody").html(
            '<tr><td colspan="5" class="text-center text-muted">Cargando…</td></tr>'
        );
        $("#modalEcCancelaciones").modal("show");
        post({ accion: "cancelaciones", tipo_doc: tipo, num_cta: num }).done(function (resp) {
            var $tb = $("#ecTablaCancel tbody");
            $tb.empty();
            var rows = (resp && resp.cancelaciones) || [];
            if (!rows.length) {
                $tb.append(
                    '<tr><td colspan="5" class="text-center text-muted">Sin cancelaciones</td></tr>'
                );
                return;
            }
            rows.forEach(function (c) {
                $tb.append(
                    "<tr>" +
                        "<td>" +
                        esc(c.cod_pago) +
                        "</td>" +
                        "<td>" +
                        esc(c.doc_origen) +
                        "</td>" +
                        "<td>" +
                        esc(c.fecha) +
                        "</td>" +
                        "<td>" +
                        esc(c.notas || "") +
                        "</td>" +
                        '<td class="ec-monto">' +
                        fmtMoney(c.monto) +
                        "</td>" +
                        "</tr>"
                );
            });
        });
    });

    $("#btnEcPagos").on("click", function () {
        var tipo = "cliente";
        var codigo = state.cliente || state.origenCliente;
        if (state.modo === "grupo" && state.grupo && !state.soloLocal) {
            tipo = "grupo";
            codigo = state.grupo;
        }
        if (!codigo) return;

        $("#modalEcPagosSub").text(
            tipo === "grupo"
                ? "Pagos consolidados del grupo"
                : "Pagos del cliente " + codigo
        );
        $("#ecTablaPagos tbody").html(
            '<tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr>'
        );
        $("#modalEcPagos").modal("show");

        post({ accion: "pagos", tipo: tipo, codigo: codigo }).done(function (resp) {
            var $tb = $("#ecTablaPagos tbody");
            $tb.empty();
            var rows = (resp && resp.pagos) || [];
            if (!rows.length) {
                $tb.append(
                    '<tr><td colspan="4" class="text-center text-muted">Sin pagos en el período</td></tr>'
                );
                return;
            }
            rows.forEach(function (p) {
                $tb.append(
                    "<tr>" +
                        "<td>" +
                        esc(p.mes) +
                        " " +
                        esc(p.anno) +
                        "</td>" +
                        '<td class="ec-monto"><strong>' +
                        fmtMoney(p.monto) +
                        "</strong></td>" +
                        '<td class="ec-monto">' +
                        fmtMoney(p.monto_jackyform) +
                        "</td>" +
                        '<td class="ec-monto">' +
                        fmtMoney(p.monto_rosalinda) +
                        "</td>" +
                        "</tr>"
                );
            });
        });
    });

    showEmpty();
    cargarClientes(false);
})(jQuery);
