/*=============================================
CIERRE MANUAL DE SERVICIOS
- crear-cierre-manual: taller + fecha + artículos; cada prenda lleva su motivo
- cierres (pestaña "Cierres manuales"): historial agrupado por cierre
=============================================*/
$(function () {
    var URL = "ajax/servicios-cierre-manual.ajax.php";

    function esc(t) {
        return $("<div>").text(t == null ? "" : t).html();
    }

    function avisarError(json) {
        if (json && json.error) {
            swal({ type: "error", title: "Error al cargar los datos", text: json.error, confirmButtonText: "Cerrar" });
        }
    }

    /*=============================================
    CREAR CIERRE MANUAL
    =============================================*/
    if ($(".tablaCierreManualAbiertos").length) {
        // el select viene armado desde PHP con el taller de la URL ya seleccionado
        var tallerActual = $("#cmTaller").val() || "";
        // líneas agregadas: { idLinea: saldo }
        var agregados = {};
        var fallas = window.CM_FALLAS || [];

        var tabla = $(".tablaCierreManualAbiertos").DataTable({
            ajax: {
                url: URL,
                type: "POST",
                data: function (d) {
                    d.accion = "listar";
                    d.taller = tallerActual;
                },
            },
            pageLength: 20,
            lengthMenu: [
                [20, 40, 60, -1],
                [20, 40, 60, "Todos"],
            ],
            order: [[0, "desc"]],
            columns: [
                { data: "codigo" },
                { data: "guia" },
                { data: "modelo", render: esc },
                { data: "nombre", render: esc },
                { data: "color", render: esc },
                { data: "talla", className: "text-center", render: esc },
                { data: "saldo", className: "text-center" },
                {
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function (d, t, r) {
                        var usado = agregados[r.id] !== undefined;
                        return "<button type='button' class='btn btn-xs " + (usado ? "btn-default" : "btn-primary cmAgregar") + "' data-id='" + r.id + "'>Agregar</button>";
                    },
                },
            ],
            language: $.fn.dataTable.defaults.language,
        }).on("xhr.dt", function (e, settings, json) {
            avisarError(json);
        });

        function guardarTallerEnUrl() {
            var p = new URLSearchParams(window.location.search);
            tallerActual ? p.set("taller", tallerActual) : p.delete("taller");
            var qs = p.toString();
            window.history.replaceState(null, "", window.location.pathname + (qs ? "?" + qs : ""));
        }

        guardarTallerEnUrl();

        function hayAgregados() {
            return Object.keys(agregados).length > 0;
        }

        function opcionesMotivo() {
            var html = "";
            $.each(fallas, function (_, f) {
                html += "<option value='" + esc(f.codigo) + "'>" + esc(f.codigo + " - " + f.nombre) + "</option>";
            });
            return html;
        }

        // el selectpicker a veces deja .val() vacío aunque el botón muestre el motivo
        function leerMotivo($fila) {
            var $sel = $fila.find("select.cmMotivo").first();
            var v = null;
            if ($sel.length && $sel.data("selectpicker")) {
                v = $sel.selectpicker("val");
            }
            if (v == null || v === "") {
                v = $sel.val();
            }
            if ($.isArray(v)) {
                v = v.length ? v[0] : "";
            }
            if ((v == null || v === "") && $sel.length) {
                v = $sel.find("option:selected").attr("value");
            }
            return $.trim(v == null ? "" : String(v));
        }

        function pintarVacio() {
            if ($(".cmLista .cmFila").length) {
                $(".cmLista .cmVacio").remove();
                return;
            }
            if (!$(".cmLista .cmVacio").length) {
                $(".cmLista").html(
                    "<div class='cmVacio'>Agregue prendas desde la lista de la derecha.<br>En cada una indique el motivo.</div>"
                );
            }
        }

        function limpiarLista() {
            agregados = {};
            $(".cmLista .cmMotivo").each(function () {
                if ($(this).data("selectpicker")) {
                    $(this).selectpicker("destroy");
                }
            });
            $(".cmLista").empty();
            pintarVacio();
            sumarTotal();
        }

        $("#cmTaller").on("change", function () {
            var nuevo = $(this).val() || "";
            if (nuevo === tallerActual) {
                return;
            }
            var aplicar = function () {
                tallerActual = nuevo;
                guardarTallerEnUrl();
                limpiarLista();
                tabla.ajax.reload();
            };
            if (!hayAgregados()) {
                aplicar();
                return;
            }
            swal({
                title: "¿Cambiar de taller?",
                text: "Se quitarán los artículos ya agregados.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, cambiar",
                cancelButtonText: "Cancelar",
            }).then(function (r) {
                if (r.value) {
                    aplicar();
                } else {
                    $("#cmTaller").selectpicker("val", tallerActual);
                }
            });
        });

        function sumarTotal() {
            var total = 0;
            $(".cmLista .cmCantidad").each(function () {
                total += parseInt($(this).val(), 10) || 0;
            });
            $("#cmTotal").val(total);
        }

        $(".tablaCierreManualAbiertos tbody").on("click", "button.cmAgregar", function () {
            var r = tabla.row($(this).closest("tr")).data();
            if (!r || agregados[r.id] !== undefined) {
                return;
            }
            agregados[r.id] = parseInt(r.saldo, 10) || 0;
            $(this).removeClass("btn-primary cmAgregar").addClass("btn-default");

            var titulo = [r.modelo, r.nombre].join(" ").replace(/\s+/g, " ").trim();
            var meta = [
                r.color || "",
                r.talla ? "T" + r.talla : "",
                r.codigo ? "Serv. " + r.codigo : "",
                r.guia ? "Guía " + r.guia : "",
                "Saldo " + r.saldo,
            ].filter(function (x) { return x; });

            $(".cmLista .cmVacio").remove();
            $(".cmLista").append(
                "<div class='cmFila' data-id='" + r.id + "'>" +
                    "<div class='cmFilaHead'>" +
                        "<button type='button' class='btn btn-danger btn-xs cmQuitar' title='Quitar'><i class='fa fa-times'></i></button>" +
                        "<div class='cmFilaTxt'>" +
                            "<div class='cmTitulo'>" + esc(titulo || r.codigo) + "</div>" +
                            "<div class='cmMeta'>" +
                                $.map(meta, function (t, i) {
                                    return (i ? "<span class='cmMetaSep'>·</span>" : "") + "<span>" + esc(t) + "</span>";
                                }).join("") +
                            "</div>" +
                        "</div>" +
                    "</div>" +
                    "<div class='row cmFilaCampos'>" +
                        "<div class='col-xs-3'>" +
                            "<label>Cerrar</label>" +
                            "<input type='number' class='form-control input-sm cmCantidad' min='1' max='" + r.saldo + "' value='0'>" +
                        "</div>" +
                        "<div class='col-xs-9'>" +
                            "<label>Motivo <span class='cmReq'>*</span></label>" +
                            "<select class='form-control input-sm selectpicker cmMotivo' data-live-search='true' data-container='body' data-width='100%' title='Seleccionar motivo'>" +
                                opcionesMotivo() +
                            "</select>" +
                        "</div>" +
                    "</div>" +
                "</div>"
            );
            var $fila = $(".cmLista .cmFila").last();
            $fila.find(".cmMotivo").selectpicker({
                liveSearch: true,
                container: "body",
                width: "100%",
                noneSelectedText: "Seleccionar motivo",
            });
            $fila.find(".cmCantidad").focus();
            sumarTotal();
        });

        $(".cmLista").on("focus", ".cmCantidad", function () {
            $(this).select();
        });

        $(".cmLista").on("changed.bs.select change", ".cmMotivo", function () {
            var $fila = $(this).closest(".cmFila");
            $fila.attr("data-falla", leerMotivo($fila));
            $fila.removeClass("cmIncompleto");
        });

        $(".cmLista").on("change", ".cmCantidad", function () {
            var id = $(this).closest(".cmFila").data("id");
            var saldo = agregados[id] || 0;
            var v = parseInt($(this).val(), 10);
            if (isNaN(v) || v < 0) {
                v = 0;
            }
            if (v > saldo) {
                v = saldo;
                swal({ type: "error", title: "La cantidad supera el saldo", text: "¡Solo hay " + saldo + " unidades por cerrar!", confirmButtonText: "Cerrar" });
            }
            $(this).val(v);
            $(this).closest(".cmFila").removeClass("cmIncompleto");
            sumarTotal();
        });

        $(".cmLista").on("click", ".cmQuitar", function () {
            var $fila = $(this).closest(".cmFila");
            var id = $fila.data("id");
            delete agregados[id];
            var $motivo = $fila.find(".cmMotivo");
            if ($motivo.data("selectpicker")) {
                $motivo.selectpicker("destroy");
            }
            $fila.remove();
            $(tabla.rows().nodes()).find("button[data-id='" + id + "']").removeClass("btn-default").addClass("btn-primary cmAgregar");
            pintarVacio();
            sumarTotal();
        });

        $("#cmBuscador").on("keyup", function () {
            var q = $(this).val().toLowerCase();
            $(".cmLista .cmFila").each(function () {
                var txt = (
                    $(this).find(".cmTitulo").text() + " " +
                    $(this).find(".cmMeta").text() + " " +
                    $(this).find(".cmMotivo option:selected").text()
                ).toLowerCase();
                $(this).toggle(!q || txt.indexOf(q) > -1);
            });
        });

        $("#cmGuardar").on("click", function () {
            var items = [];
            var total = 0;
            var incompleto = false;
            $(".cmLista .cmFila").removeClass("cmIncompleto");
            $(".cmLista .cmFila").each(function () {
                var $fila = $(this);
                var c = parseInt($fila.find(".cmCantidad").val(), 10) || 0;
                var falla = leerMotivo($fila) || ($fila.attr("data-falla") || "");
                if (c <= 0 || !falla) {
                    incompleto = true;
                    $fila.addClass("cmIncompleto");
                    return;
                }
                items.push({
                    id: parseInt($fila.data("id"), 10),
                    cantidad: c,
                    falla: falla,
                });
                total += c;
            });

            var fecha = $("#cmFecha").val() || "";
            if (!tallerActual) {
                swal({ type: "warning", title: "Seleccione el taller", confirmButtonText: "Cerrar" });
                return;
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
                swal({ type: "warning", title: "Indique la fecha del cierre", confirmButtonText: "Cerrar" });
                return;
            }
            if (!$(".cmLista .cmFila").length) {
                swal({ type: "warning", title: "Agregue al menos una prenda", confirmButtonText: "Cerrar" });
                return;
            }
            if (incompleto) {
                swal({
                    type: "warning",
                    title: "Faltan datos en alguna prenda",
                    text: "Cada prenda necesita motivo y una cantidad mayor a 0. Las incompletas están marcadas.",
                    confirmButtonText: "Cerrar",
                });
                var $mala = $(".cmLista .cmIncompleto").first();
                if ($mala.length) {
                    $mala[0].scrollIntoView({ block: "nearest" });
                }
                return;
            }

            swal({
                title: "¿Cerrar " + total + " prenda(s) en " + items.length + " línea(s)?",
                text: "Cada prenda se cierra con su motivo. Se descontarán del saldo del servicio. Esta acción no se puede deshacer.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, cerrar",
                cancelButtonText: "Cancelar",
            }).then(function (r) {
                if (!r.value) {
                    return;
                }
                $("#cmGuardar").prop("disabled", true);
                $.post(
                    URL,
                    { accion: "cerrar", taller: tallerActual, fecha: fecha, obs: $("#cmObs").val(), items: JSON.stringify(items) },
                    function (resp) {
                        if (resp && resp.ok) {
                            swal({
                                type: "success",
                                title: "Cierre manual " + resp.codigo + " guardado",
                                text: "Se cerraron " + resp.cantidad + " prenda(s) en " + resp.cerrados + " línea(s).",
                                confirmButtonText: "Cerrar",
                            }).then(function () {
                                window.location = "cierres?tab=manuales";
                            });
                        } else {
                            $("#cmGuardar").prop("disabled", false);
                            swal({ type: "error", title: (resp && resp.error) || "No se pudo cerrar", confirmButtonText: "Cerrar" });
                        }
                    },
                    "json"
                ).fail(function () {
                    $("#cmGuardar").prop("disabled", false);
                    swal({ type: "error", title: "No se pudo conectar con el servidor", confirmButtonText: "Cerrar" });
                });
            });
        });
    }

    /*=============================================
    PESTAÑA EN LA URL (cierres / manuales)
    =============================================*/
    if ($('a[data-cm-tab]').length) {
        function cmGuardarTabEnUrl(tab) {
            var p = new URLSearchParams(window.location.search);
            if (tab === "manuales") {
                p.set("tab", "manuales");
            } else {
                p.delete("tab");
            }
            var qs = p.toString();
            window.history.replaceState(null, "", window.location.pathname + (qs ? "?" + qs : ""));
        }

        $('a[data-cm-tab]').on("shown.bs.tab", function () {
            cmGuardarTabEnUrl($(this).data("cm-tab") || "cierres");
        });
    }

    /*=============================================
    HISTORIAL EN LA LISTA DE CIERRES
    =============================================*/
    if ($(".tablaCierresManuales").length) {
        var tablaHist = $(".tablaCierresManuales").DataTable({
            ajax: {
                url: URL,
                type: "POST",
                data: function (d) {
                    d.accion = "historial";
                    d.desde = $("#cmDesde").val();
                    d.hasta = $("#cmHasta").val();
                },
            },
            pageLength: 20,
            order: [[1, "desc"]],
            columns: [
                {
                    data: "cierre_codigo",
                    render: function (v) {
                        return v ? esc(v) : "<span class='text-muted'>(anterior)</span>";
                    },
                },
                { data: "fecha" },
                { data: "usuario", render: esc },
                { data: "taller", render: esc },
                { data: "falla", render: esc },
                { data: "obs", render: esc },
                {
                    data: "anulado",
                    className: "text-center",
                    render: function (v, t, r) {
                        if (parseInt(v, 10) === 1) {
                            var tip = r.motivo_anulacion ? esc(r.motivo_anulacion) : "Anulado";
                            return "<span class='label label-danger' title='" + tip + "'>Anulado</span>";
                        }
                        return "<span class='label label-success'>Vigente</span>";
                    },
                },
                { data: "lineas", className: "text-right" },
                { data: "cantidad", className: "text-right" },
                {
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function (d, t, r) {
                        var btns = "<button type='button' class='btn btn-xs btn-info cmVerDetalle' title='Ver detalle'><i class='fa fa-eye'></i></button>";
                        if (r.cierre_codigo && parseInt(r.anulado, 10) !== 1) {
                            btns += " <button type='button' class='btn btn-xs btn-danger cmAnular' title='Anular'><i class='fa fa-ban'></i></button>";
                        }
                        return btns;
                    },
                },
            ],
            language: $.fn.dataTable.defaults.language,
        }).on("xhr.dt", function (e, settings, json) {
            avisarError(json);
        });

        $("#cmBtnFiltrar").on("click", function () {
            tablaHist.ajax.reload();
        });

        $('a[href="#tabCierresManuales"]').on("shown.bs.tab", function () {
            tablaHist.columns.adjust();
        });

        $(".tablaCierresManuales tbody").on("click", ".cmVerDetalle", function () {
            var r = tablaHist.row($(this).closest("tr")).data();
            if (!r) {
                return;
            }
            $("#cmDetTitulo").text(r.cierre_codigo || "");
            var cab = r.fecha + " · " + r.usuario + " · " + r.taller + (r.falla ? " · " + r.falla : "") + (r.obs ? " · " + r.obs : "");
            if (parseInt(r.anulado, 10) === 1) {
                cab += " · ANULADO" + (r.motivo_anulacion ? " (" + r.motivo_anulacion + ")" : "");
            }
            $("#cmDetCabecera").text(cab);
            var $tb = $("#cmDetTabla tbody").html("<tr><td colspan='8' class='text-center'>Cargando...</td></tr>");
            $("#modalDetalleCierreManual").modal("show");

            $.post(URL, { accion: "detalle", clave: r.clave }, function (resp) {
                if (!resp || !resp.ok) {
                    $tb.html("<tr><td colspan='8' class='text-danger'>" + esc((resp && resp.error) || "No se pudo cargar el detalle") + "</td></tr>");
                    return;
                }
                var html = "";
                var total = 0;
                $.each(resp.data, function (i, d) {
                    total += parseInt(d.cantidad, 10) || 0;
                    var talla = d.talla_cierre || d.talla_articulo || d.talla || "";
                    html +=
                        "<tr><td>" + esc(d.codigo) + "</td><td>" + esc(d.guia) + "</td>" +
                        "<td>" + esc(d.modelo) + " " + esc(d.nombre) + "</td><td>" + esc(d.color) + "</td>" +
                        "<td>" + esc(talla) + "</td><td>" + esc(d.motivo_nombre || d.motivo || "") + "</td>" +
                        "<td class='text-right'>" + esc(d.saldo_antes) + "</td>" +
                        "<td class='text-right'>" + esc(d.cantidad) + "</td></tr>";
                });
                html += "<tr><th colspan='7' class='text-right'>Total</th><th class='text-right'>" + total + "</th></tr>";
                $tb.html(html);
            }, "json");
        });

        $(".tablaCierresManuales tbody").on("click", ".cmAnular", function () {
            var r = tablaHist.row($(this).closest("tr")).data();
            if (!r || !r.cierre_codigo || parseInt(r.anulado, 10) === 1) {
                return;
            }
            swal({
                title: "¿Anular " + r.cierre_codigo + "?",
                text: "Se devolverá el saldo a las líneas del servicio y al stock en servicio. Indique el motivo:",
                input: "text",
                inputPlaceholder: "Motivo de anulación",
                showCancelButton: true,
                confirmButtonText: "Sí, anular",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#dd4b39",
            }).then(function (res) {
                if (!res || res.dismiss || res.value === undefined) {
                    return;
                }
                var motivo = $.trim(res.value || "");
                if (!motivo) {
                    swal({ type: "warning", title: "Indique el motivo de anulación", confirmButtonText: "Cerrar" });
                    return;
                }
                $.post(
                    URL,
                    { accion: "anular", codigo: r.cierre_codigo, motivo: motivo },
                    function (resp) {
                        if (resp && resp.ok) {
                            swal({
                                type: "success",
                                title: "Cierre " + resp.codigo + " anulado",
                                text: "Se devolvieron " + resp.cantidad + " prenda(s) en " + resp.lineas + " línea(s).",
                                confirmButtonText: "Cerrar",
                            });
                            tablaHist.ajax.reload(null, false);
                        } else {
                            swal({ type: "error", title: (resp && resp.error) || "No se pudo anular", confirmButtonText: "Cerrar" });
                        }
                    },
                    "json"
                ).fail(function () {
                    swal({ type: "error", title: "No se pudo conectar con el servidor", confirmButtonText: "Cerrar" });
                });
            });
        });
    }
});
