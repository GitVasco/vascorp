/*=============================================
CIERRE MANUAL DE SERVICIOS
- crear-cierre-manual: taller + tipo de falla + artículos (flujo tipo crear ingreso)
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

        function limpiarLista() {
            agregados = {};
            $(".cmLista").empty();
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

            var desc = [r.modelo, r.nombre, r.color, r.talla ? "T" + r.talla : ""].join(" ").replace(/\s+/g, " ").trim();
            $(".cmLista").append(
                "<div class='row cmFila' style='padding:5px 15px' data-id='" + r.id + "'>" +
                    "<div class='col-xs-6' style='padding-right:0px'>" +
                        "<div class='input-group'>" +
                            "<span class='input-group-addon'><button type='button' class='btn btn-danger btn-xs cmQuitar'><i class='fa fa-times'></i></button></span>" +
                            "<input type='text' class='form-control input-sm cmDesc' value='" + esc(desc) + "' readonly>" +
                        "</div>" +
                    "</div>" +
                    "<div class='col-xs-2'><input type='text' class='form-control input-sm' value='" + esc(r.codigo) + "' readonly></div>" +
                    "<div class='col-xs-2'><input type='number' class='form-control input-sm' value='" + r.saldo + "' readonly></div>" +
                    "<div class='col-xs-2'><input type='number' class='form-control input-sm cmCantidad' min='1' max='" + r.saldo + "' value='0'></div>" +
                "</div>"
            );
            $(".cmLista .cmCantidad").last().focus();
            sumarTotal();
        });

        $(".cmLista").on("focus", ".cmCantidad", function () {
            $(this).select();
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
            sumarTotal();
        });

        $(".cmLista").on("click", ".cmQuitar", function () {
            var $fila = $(this).closest(".cmFila");
            var id = $fila.data("id");
            delete agregados[id];
            $fila.remove();
            $(tabla.rows().nodes()).find("button[data-id='" + id + "']").removeClass("btn-default").addClass("btn-primary cmAgregar");
            sumarTotal();
        });

        $("#cmBuscador").on("keyup", function () {
            var q = $(this).val().toLowerCase();
            $(".cmLista .cmFila").each(function () {
                var txt = ($(this).find(".cmDesc").val() || "").toLowerCase();
                $(this).toggle(!q || txt.indexOf(q) > -1);
            });
        });

        $("#cmGuardar").on("click", function () {
            var falla = $("#cmFalla").val();
            var items = [];
            var total = 0;
            var sinCantidad = false;
            $(".cmLista .cmFila").each(function () {
                var c = parseInt($(this).find(".cmCantidad").val(), 10) || 0;
                if (c <= 0) {
                    sinCantidad = true;
                    return;
                }
                items.push({ id: parseInt($(this).data("id"), 10), cantidad: c });
                total += c;
            });

            if (!tallerActual) {
                swal({ type: "warning", title: "Seleccione el taller", confirmButtonText: "Cerrar" });
                return;
            }
            if (!falla) {
                swal({ type: "warning", title: "Seleccione el tipo de falla", confirmButtonText: "Cerrar" });
                return;
            }
            if (!$(".cmLista .cmFila").length) {
                swal({ type: "warning", title: "Agregue al menos un artículo", confirmButtonText: "Cerrar" });
                return;
            }
            if (sinCantidad) {
                swal({ type: "warning", title: "Hay artículos con cantidad 0", text: "Indique la cantidad a cerrar o quítelos de la lista.", confirmButtonText: "Cerrar" });
                return;
            }

            swal({
                title: "¿Cerrar " + total + " prenda(s) en " + items.length + " línea(s)?",
                text: "Se descontarán del saldo del servicio y del stock en servicio. Esta acción no se puede deshacer.",
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
                    { accion: "cerrar", taller: tallerActual, falla: falla, obs: $("#cmObs").val(), items: JSON.stringify(items) },
                    function (resp) {
                        if (resp && resp.ok) {
                            swal({
                                type: "success",
                                title: "Cierre manual " + resp.codigo + " guardado",
                                text: "Se cerraron " + resp.cantidad + " prenda(s) en " + resp.cerrados + " línea(s).",
                                confirmButtonText: "Cerrar",
                            }).then(function () {
                                window.location = "cierres";
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
                { data: "lineas", className: "text-right" },
                { data: "cantidad", className: "text-right" },
                {
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function () {
                        return "<button type='button' class='btn btn-xs btn-info cmVerDetalle' title='Ver detalle'><i class='fa fa-eye'></i></button>";
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
            $("#cmDetCabecera").text(r.fecha + " · " + r.usuario + " · " + r.taller + " · " + r.falla + (r.obs ? " · " + r.obs : ""));
            var $tb = $("#cmDetTabla tbody").html("<tr><td colspan='7' class='text-center'>Cargando...</td></tr>");
            $("#modalDetalleCierreManual").modal("show");

            $.post(URL, { accion: "detalle", clave: r.clave }, function (resp) {
                if (!resp || !resp.ok) {
                    $tb.html("<tr><td colspan='7' class='text-danger'>" + esc((resp && resp.error) || "No se pudo cargar el detalle") + "</td></tr>");
                    return;
                }
                var html = "";
                var total = 0;
                $.each(resp.data, function (i, d) {
                    total += parseInt(d.cantidad, 10) || 0;
                    html +=
                        "<tr><td>" + esc(d.codigo) + "</td><td>" + esc(d.guia) + "</td>" +
                        "<td>" + esc(d.modelo) + " " + esc(d.nombre) + "</td><td>" + esc(d.color) + "</td>" +
                        "<td>" + esc(d.talla) + "</td><td class='text-right'>" + esc(d.saldo_antes) + "</td>" +
                        "<td class='text-right'>" + esc(d.cantidad) + "</td></tr>";
                });
                html += "<tr><th colspan='6' class='text-right'>Total</th><th class='text-right'>" + total + "</th></tr>";
                $tb.html(html);
            }, "json");
        });
    }
});
