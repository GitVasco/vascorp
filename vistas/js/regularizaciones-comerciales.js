$(function () {
    if ($("#panelRegularizacionesComerciales").length === 0) {
        return;
    }

    var API = "ajax/regularizaciones-comerciales.ajax.php";
    var permisos = { ver: true, registrar: false, anular: false, resolver: false };
    var cargoSel = null;

    function esc(v) {
        return $("<div>").text(v == null ? "" : String(v)).html();
    }

    function money(n) {
        var x = Number(n);
        if (isNaN(x)) {
            return "—";
        }
        return x.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function toast(tipo, msg) {
        if (window.toastr) {
            toastr[tipo](msg);
            return;
        }
        alert(msg);
    }

    function badgeEstado(estado) {
        var map = {
            ACTIVA: { cls: "label-success", txt: "Activa" },
            REQUIERE_REVISION: { cls: "label-warning", txt: "Por revisar" },
            RESUELTA_AUTOMATICA: { cls: "label-info", txt: "Resuelta" },
            ANULADA: { cls: "label-default", txt: "Anulada" }
        };
        var info = map[estado] || { cls: "label-default", txt: estado };
        return '<span class="label ' + info.cls + '">' + esc(info.txt) + "</span>";
    }

    function post(accion, data) {
        data = data || {};
        data.accion = accion;
        return $.ajax({
            url: API + "?accion=" + encodeURIComponent(accion),
            method: "POST",
            dataType: "json",
            data: data
        });
    }

    function aplicarPermisosUi() {
        if (!permisos.registrar) {
            $("#rcBoxAlta").hide();
            $("#rcTablaCargos").find(".rc-btn-elegir").prop("disabled", true);
        }
    }

    function cargarPermisos() {
        return post("permisos").done(function (res) {
            if (res && res.ok && res.permisos) {
                permisos = res.permisos;
            }
            aplicarPermisosUi();
        });
    }

    function renderCargos(cargos) {
        var $tb = $("#rcTablaCargos tbody").empty();
        if (!cargos || !cargos.length) {
            $tb.append('<tr class="rc-vacio"><td colspan="5" class="text-muted text-center">Sin resultados.</td></tr>');
            return;
        }
        cargos.forEach(function (c) {
            var doc = esc(c.tipo_doc) + " " + esc(c.num_cta);
            var cli = esc(c.cliente) + (c.cliente_nombre ? "<br><small>" + esc(c.cliente_nombre) + "</small>" : "");
            var btn = permisos.registrar
                ? '<button type="button" class="btn btn-xs btn-primary rc-btn-elegir" data-id="' + esc(c.id) + '">Elegir</button>'
                : "";
            $tb.append(
                "<tr>" +
                    "<td>" + doc + "<br><small>#" + esc(c.id) + "</small></td>" +
                    "<td>" + cli + "</td>" +
                    '<td class="text-right">' + money(c.saldo_oficial != null ? c.saldo_oficial : c.saldo) + "</td>" +
                    '<td class="text-right"><strong>' + money(c.saldo_comercial) + "</strong></td>" +
                    "<td>" + btn + "</td>" +
                "</tr>"
            );
            $tb.find("tr:last .rc-btn-elegir").data("cargo", c);
        });
    }

    function buscarCargos() {
        var q = $.trim($("#rcBuscarQ").val());
        if (q === "") {
            toast("warning", "Escriba un criterio de búsqueda.");
            return;
        }
        $("#rcBtnBuscarCargos").prop("disabled", true);
        post("buscar-cargos", { q: q })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo buscar.");
                    return;
                }
                if (res.permisos) {
                    permisos = res.permisos;
                }
                renderCargos(res.cargos || []);
            })
            .fail(function () {
                toast("error", "Error de red al buscar cargos.");
            })
            .always(function () {
                $("#rcBtnBuscarCargos").prop("disabled", false);
            });
    }

    function seleccionarCargo(c) {
        cargoSel = c;
        $("#rcCuentaCteId").val(c.id);
        $("#rcMonto").val("");
        $("#rcSustento").val("");
        $("#rcMotivo").val("Pago no ingresado — regularización comercial excepcional");
        $("#rcObservacion").val("");
        var hoy = new Date();
        var ymd = hoy.toISOString().slice(0, 10);
        $("#rcFechaPago").val(ymd);

        var html =
            '<div class="rc-sel-line"><strong>En Vascorp:</strong> ' + money(c.saldo_oficial != null ? c.saldo_oficial : c.saldo) + "</div>" +
            '<div class="rc-sel-line"><strong>Ya regularizado:</strong> ' + money(c.regularizacion_activa || 0) + "</div>" +
            '<div class="rc-sel-line"><strong>En VascoPro:</strong> ' + money(c.saldo_comercial) + "</div>" +
            '<div class="rc-sel-line text-muted">' + esc(c.tipo_doc) + " " + esc(c.num_cta) +
            " · " + esc(c.cliente) +
            (c.cliente_nombre ? " — " + esc(c.cliente_nombre) : "") +
            "</div>";
        $("#rcCargoSeleccionado").html(html);
        $("#rcBoxAlta").show();
        $("#rcMonto").attr("max", c.saldo_comercial).focus();
    }

    function renderLista(items) {
        var $tb = $("#rcTablaLista tbody").empty();
        if (!items || !items.length) {
            $tb.append('<tr class="rc-vacio"><td colspan="7" class="text-muted text-center">Sin regularizaciones.</td></tr>');
            return;
        }
        items.forEach(function (r) {
            var acciones = [];
            acciones.push('<button type="button" class="btn btn-xs btn-default rc-btn-ver" data-id="' + esc(r.id) + '">Ver</button>');
            if (permisos.anular && (r.estado === "ACTIVA" || r.estado === "REQUIERE_REVISION")) {
                acciones.push('<button type="button" class="btn btn-xs btn-danger rc-btn-anular" data-id="' + esc(r.id) + '">Anular</button>');
            }
            if (permisos.resolver && (r.estado === "ACTIVA" || r.estado === "REQUIERE_REVISION")) {
                acciones.push('<button type="button" class="btn btn-xs btn-warning rc-btn-reconciliar" data-id="' + esc(r.id) + '">Conciliar</button>');
            }
            $tb.append(
                "<tr>" +
                    "<td>" + esc(r.id) + "</td>" +
                    "<td>" + esc(r.tipo_doc) + " " + esc(r.num_cta) + "</td>" +
                    "<td>" + esc(r.cliente_codigo) + "</td>" +
                    '<td class="text-right">' + money(r.monto_aplicable) +
                    "<br><small class='text-muted'>orig. " + money(r.monto_original) + "</small></td>" +
                    "<td>" + badgeEstado(r.estado) + "</td>" +
                    "<td>" + esc(r.sustento_referencia) + "</td>" +
                    '<td class="rc-acciones">' + acciones.join(" ") + "</td>" +
                "</tr>"
            );
        });
    }

    function listar() {
        var estado = $("#rcFiltroEstado").val();
        post("listar", { estado: estado })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo listar.");
                    return;
                }
                if (res.permisos) {
                    permisos = res.permisos;
                }
                renderLista(res.items || []);
            })
            .fail(function () {
                toast("error", "Error de red al listar.");
            });
    }

    function verDetalle(id) {
        post("ver", { id: id })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo cargar el detalle.");
                    return;
                }
                var r = res.regularizacion || {};
                var c = res.cargo || {};
                var ev = res.eventos || [];
                var html = "";
                html += '<div class="row rc-detalle-saldos">';
                html += '<div class="col-sm-4"><div class="rc-kpi"><span>En Vascorp</span><strong>' + money(res.saldo_oficial) + "</strong></div></div>";
                html += '<div class="col-sm-4"><div class="rc-kpi"><span>Regularizado</span><strong>' + money(r.monto_aplicable) + "</strong></div></div>";
                html += '<div class="col-sm-4"><div class="rc-kpi"><span>En VascoPro</span><strong>' + money(res.saldo_comercial) + "</strong></div></div>";
                html += "</div>";
                html += "<p>" + badgeEstado(r.estado) +
                    " · " + esc(r.tipo_doc) + " " + esc(r.num_cta) +
                    " · " + esc(r.cliente_codigo) +
                    (c.cliente_nombre ? " — " + esc(c.cliente_nombre) : "") +
                    "</p>";
                html += "<p><strong>Recibo / OP:</strong> " + esc(r.sustento_referencia) +
                    "<br><strong>Motivo:</strong> " + esc(r.motivo) +
                    (r.observacion ? "<br><strong>Nota:</strong> " + esc(r.observacion) : "") +
                    "</p>";
                html += "<p class='text-muted'>Pago: " + esc(r.fecha_pago_cliente) +
                    " · Registró: " + esc(r.usuario_registro_nombre || r.usuario_registro_id) +
                    " · " + esc(r.fecha_registro) + "</p>";

                html += '<h4>Historial</h4><div class="table-responsive"><table class="table table-condensed"><thead><tr>' +
                    "<th>Fecha</th><th>Evento</th><th>Estado</th><th>Δ</th><th>Quién</th></tr></thead><tbody>";
                if (!ev.length) {
                    html += '<tr><td colspan="5" class="text-muted">Sin eventos.</td></tr>';
                } else {
                    ev.forEach(function (e) {
                        html += "<tr>" +
                            "<td>" + esc(e.fecha) + "</td>" +
                            "<td>" + esc(e.tipo_evento) + "</td>" +
                            "<td>" + esc(e.estado_anterior || "—") + " → " + esc(e.estado_nuevo || "—") + "</td>" +
                            '<td class="text-right">' + (e.monto_delta != null ? money(e.monto_delta) : "—") + "</td>" +
                            "<td>" + esc(e.origen) + (e.usuario_nombre ? " · " + esc(e.usuario_nombre) : "") + "</td>" +
                            "</tr>";
                    });
                }
                html += "</tbody></table></div>";

                $("#rcDetalleCuerpo").html(html);
                $("#rcBoxDetalle").show();
            })
            .fail(function () {
                toast("error", "Error de red al ver detalle.");
            });
    }

    $("#rcBtnBuscarCargos").on("click", buscarCargos);
    $("#rcBuscarQ").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            buscarCargos();
        }
    });

    $("#rcTablaCargos").on("click", ".rc-btn-elegir", function () {
        var c = $(this).data("cargo");
        if (c) {
            seleccionarCargo(c);
        }
    });

    $("#rcBtnCancelarAlta").on("click", function () {
        cargoSel = null;
        $("#rcBoxAlta").hide();
        $("#rcFormAlta")[0].reset();
    });

    $("#rcFormAlta").on("submit", function (e) {
        e.preventDefault();
        if (!permisos.registrar) {
            toast("error", "Sin permiso para registrar.");
            return;
        }
        var data = {
            cuenta_cte_id: $("#rcCuentaCteId").val(),
            monto: $("#rcMonto").val(),
            fecha_pago_cliente: $("#rcFechaPago").val(),
            sustento_referencia: $.trim($("#rcSustento").val()),
            motivo: $.trim($("#rcMotivo").val()),
            observacion: $.trim($("#rcObservacion").val())
        };
        if (!data.cuenta_cte_id || !data.monto || !data.fecha_pago_cliente || !data.sustento_referencia || !data.motivo) {
            toast("warning", "Complete los campos obligatorios.");
            return;
        }
        if (!window.confirm(
            "¿Guardar esta regularización?\n\n" +
            "Monto: " + data.monto + "\n" +
            "Recibo/OP: " + data.sustento_referencia + "\n\n" +
            "Esto solo cambia lo que ve el vendedor en VascoPro.\n" +
            "No registra cobro ni cambia la contabilidad."
        )) {
            return;
        }
        $("#rcBtnCrear").prop("disabled", true);
        post("crear", data)
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo registrar.");
                    return;
                }
                toast("success", "Regularización #" + res.id + " registrada.");
                $("#rcBoxAlta").hide();
                $("#rcFormAlta")[0].reset();
                cargoSel = null;
                listar();
                if (data.cuenta_cte_id) {
                    verDetalle(res.id);
                }
            })
            .fail(function (xhr) {
                var msg = "Error al registrar.";
                if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toast("error", msg);
            })
            .always(function () {
                $("#rcBtnCrear").prop("disabled", false);
            });
    });

    $("#rcBtnRefrescarLista, #rcFiltroEstado").on("click change", function (e) {
        if (e.type === "change" || $(e.currentTarget).is("#rcBtnRefrescarLista")) {
            listar();
        }
    });

    $("#rcTablaLista").on("click", ".rc-btn-ver", function () {
        verDetalle($(this).data("id"));
    });

    $("#rcTablaLista").on("click", ".rc-btn-anular", function () {
        $("#rcAnularId").val($(this).data("id"));
        $("#rcAnularMotivo").val("");
        $("#rcModalAnular").modal("show");
    });

    $("#rcBtnConfirmarAnular").on("click", function () {
        var id = $("#rcAnularId").val();
        var motivo = $.trim($("#rcAnularMotivo").val());
        if (!motivo) {
            toast("warning", "Indique el motivo de anulación.");
            return;
        }
        $("#rcBtnConfirmarAnular").prop("disabled", true);
        post("anular", { id: id, motivo_anulacion: motivo })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo anular.");
                    return;
                }
                toast("success", "Regularización anulada.");
                $("#rcModalAnular").modal("hide");
                listar();
                verDetalle(id);
            })
            .fail(function () {
                toast("error", "Error al anular.");
            })
            .always(function () {
                $("#rcBtnConfirmarAnular").prop("disabled", false);
            });
    });

    $("#rcTablaLista").on("click", ".rc-btn-reconciliar", function () {
        var id = $(this).data("id");
        if (!window.confirm("¿Reintentar conciliación con abonos oficiales vinculados?")) {
            return;
        }
        post("reconciliar", { id: id, accion_reconciliar: "reintentar" })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo conciliar.");
                    return;
                }
                toast("success", "Conciliación: " + (res.cambios || 0) + " cambio(s).");
                listar();
                verDetalle(id);
            })
            .fail(function () {
                toast("error", "Error al conciliar.");
            });
    });

    cargarPermisos().always(function () {
        listar();
    });
});
