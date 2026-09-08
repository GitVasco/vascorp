(function () {
    if (!$(".tablaSeguimientoRecetas").length) {
        return;
    }

    var dt = null;
    var restaurando = false;
    var cargaActiva = 0;

    function esc(texto) {
        return String(texto == null ? "" : texto)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function mostrarCarga(msg) {
        cargaActiva++;
        $("#segRecetasOverlayMsg").text(msg || "Cargando…");
        $("#segRecetasOverlay").addClass("is-on").attr("aria-hidden", "false");
    }

    function ocultarCarga() {
        cargaActiva = Math.max(0, cargaActiva - 1);
        if (cargaActiva === 0) {
            $("#segRecetasOverlay").removeClass("is-on").attr("aria-hidden", "true");
            $(".btnBuscarSeguimientoRecetas").prop("disabled", false);
        }
    }

    function leerFiltrosUrl() {
        var params = new URLSearchParams(window.location.search);
        return {
            linea: (params.get("linea") || "").trim(),
            sublinea: (params.get("sublinea") || "").trim().toUpperCase(),
            mp: (params.get("mp") || "").trim(),
        };
    }

    function escribirFiltrosUrl(linea, sublinea, mp) {
        var url = new URL(window.location.href);
        url.searchParams.delete("linea");
        url.searchParams.delete("sublinea");
        url.searchParams.delete("mp");
        if (linea) {
            url.searchParams.set("linea", linea);
        }
        if (sublinea) {
            url.searchParams.set("sublinea", sublinea);
        }
        if (mp) {
            url.searchParams.set("mp", mp);
        }
        history.replaceState(null, "", url.pathname + url.search + url.hash);
    }

    function filtrosActuales() {
        return {
            linea: ($("#selSegRecetaLinea").val() || "").trim(),
            sublinea: ($("#selSegRecetaSublinea").val() || "").trim().toUpperCase(),
            mp: ($("#selSegRecetaMp").val() || "").trim(),
        };
    }

    function hayFiltro(f) {
        f = f || filtrosActuales();
        return !!(f.linea || f.sublinea || f.mp);
    }

    function postFiltro(accion, extra) {
        var datos = $.extend({ accion: accion }, extra || {});
        return $.ajax({
            url: "ajax/seguimiento-recetas.ajax.php",
            method: "POST",
            data: datos,
            dataType: "json",
        });
    }

    function llenarSelect($sel, items, placeholder, valueKey, labelFn, opts) {
        opts = opts || {};
        var seleccion = opts.seleccion != null ? String(opts.seleccion) : "";
        var html = '<option value="">' + placeholder + "</option>";
        (items || []).forEach(function (item) {
            var val = item[valueKey] || "";
            var attrs = "";
            if (opts.dataFn) {
                var data = opts.dataFn(item) || {};
                Object.keys(data).forEach(function (k) {
                    attrs += ' data-' + k + '="' + esc(data[k]) + '"';
                });
            }
            var selected = seleccion && String(val).toUpperCase() === seleccion.toUpperCase() ? " selected" : "";
            html +=
                '<option value="' +
                esc(val) +
                '"' +
                attrs +
                selected +
                ">" +
                esc(labelFn(item)) +
                "</option>";
        });
        $sel.html(html);
        $sel.selectpicker("refresh");
    }

    function limpiarMpSelect(mensaje) {
        llenarSelect($("#selSegRecetaMp"), [], mensaje || "-------- Materia prima -------", "mp_codigo", function () {
            return "";
        });
        return $.Deferred().resolve().promise();
    }

    function cargarLineas(seleccion) {
        return postFiltro("lineas").done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            llenarSelect(
                $("#selSegRecetaLinea"),
                res.data,
                "-------- Línea -------",
                "linea",
                function (item) {
                    return item.linea;
                },
                { seleccion: seleccion }
            );
        });
    }

    function cargarSublineas(seleccion) {
        var linea = $("#selSegRecetaLinea").val() || "";
        return postFiltro("sublineas", { linea: linea }).done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            llenarSelect(
                $("#selSegRecetaSublinea"),
                res.data,
                "-------- Sublínea -------",
                "codigo_sublinea",
                function (item) {
                    var nom = item.nombre ? " — " + item.nombre : "";
                    return (item.codigo_sublinea || "") + nom;
                },
                {
                    seleccion: seleccion,
                    dataFn: function (item) {
                        return { linea: item.linea || "" };
                    },
                }
            );
        });
    }

    function cargarMps(seleccion) {
        var f = filtrosActuales();

        if (!f.sublinea && !f.linea && !seleccion) {
            limpiarMpSelect("-------- Elija línea o sublínea -------");
            return $.Deferred().resolve().promise();
        }

        return postFiltro("mps", { linea: f.linea, sublinea: f.sublinea }).done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            llenarSelect(
                $("#selSegRecetaMp"),
                res.data,
                "-------- Materia prima -------",
                "mp_codigo",
                function (item) {
                    var extra = [];
                    if (item.descripcion) {
                        extra.push(item.descripcion);
                    }
                    if (item.color) {
                        extra.push(item.color);
                    }
                    return item.mp_codigo + (extra.length ? " — " + extra.join(" / ") : "");
                },
                { seleccion: seleccion }
            );
        });
    }

    function sincronizarLineaDesdeSublinea() {
        var $opt = $("#selSegRecetaSublinea option:selected");
        var lineaSub = ($opt.data("linea") || "").toString().trim();
        if (!lineaSub) {
            return $.Deferred().resolve().promise();
        }
        var lineaActual = ($("#selSegRecetaLinea").val() || "").trim();
        if (lineaActual === lineaSub) {
            return $.Deferred().resolve().promise();
        }
        $("#selSegRecetaLinea").val(lineaSub);
        $("#selSegRecetaLinea").selectpicker("refresh");
        return cargarSublineas($("#selSegRecetaSublinea").val());
    }

    function urlExportExcel(f) {
        f = f || filtrosActuales();
        return (
            "vistas/reportes_excel/rpt_seguimiento_recetas.php?" +
            "linea=" +
            encodeURIComponent(f.linea) +
            "&sublinea=" +
            encodeURIComponent(f.sublinea) +
            "&mp=" +
            encodeURIComponent(f.mp)
        );
    }

    function actualizarExportExcel(f) {
        f = f || filtrosActuales();
        if (hayFiltro(f)) {
            $("#btnExportSeguimientoRecetas").attr("href", urlExportExcel(f));
        } else {
            $("#btnExportSeguimientoRecetas").attr("href", "#");
        }
    }

    function urlTabla(f) {
        f = f || filtrosActuales();
        return (
            "ajax/maestros/tabla-seguimiento.ajax.php?perfil=" +
            encodeURIComponent($("#perfilOculto").val() || "") +
            "&filtroReceta=1" +
            "&linea=" +
            encodeURIComponent(f.linea) +
            "&sublinea=" +
            encodeURIComponent(f.sublinea) +
            "&mp=" +
            encodeURIComponent(f.mp)
        );
    }

    function fmtNum(valor) {
        var n = parseFloat(valor);
        if (isNaN(n)) {
            return "0";
        }
        return n.toLocaleString("es-PE", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 6,
        });
    }

    function hayMpFiltro(f) {
        f = f || filtrosActuales();
        return !!(f.mp || "").trim();
    }

    function limpiarExplosionMp() {
        $("#boxExplosionMpOrdCorte").hide();
        $(".tablaExplosionMpOrdCorte tbody").empty();
        $("#lblExplosionMpResumen").text("");
        $("#alertExplosionMpErrores").hide().empty();
    }

    function pintarExplosionMp(res, f) {
        f = f || filtrosActuales();
        if (!hayMpFiltro(f)) {
            limpiarExplosionMp();
            return;
        }

        $("#boxExplosionMpOrdCorte").show();

        if (!res || !res.ok) {
            $("#lblExplosionMpResumen").text("");
            $(".tablaExplosionMpOrdCorte tbody").html(
                '<tr><td colspan="6" class="text-center text-danger">No se pudo calcular la explosión</td></tr>'
            );
            return;
        }

        var resumen = res.resumen || {};
        $("#lblExplosionMpResumen").text(
            "MP " +
                (f.mp || "") +
                " · " +
                (resumen.articulos || 0) +
                " artículos · " +
                fmtNum(resumen.unidades_ord_corte || 0) +
                " uds. ord. corte"
        );

        var filas = res.consolidados || [];
        if (!filas.length) {
            $(".tablaExplosionMpOrdCorte tbody").html(
                '<tr><td colspan="6" class="text-center text-muted">Sin materia prima calculada</td></tr>'
            );
        } else {
            var html = "";
            filas.forEach(function (row) {
                var roles = row.roles && row.roles.length ? row.roles.join(" / ") : "—";
                if (row.es_tela_principal) {
                    roles = (roles !== "—" ? roles + " · " : "") + "Tela principal";
                }
                html +=
                    "<tr>" +
                    "<td><strong>" +
                    esc(row.mp_codigo || "") +
                    "</strong></td>" +
                    "<td>" +
                    esc(row.mp_descripcion || "") +
                    "</td>" +
                    "<td>" +
                    esc(row.mp_color || "") +
                    "</td>" +
                    "<td>" +
                    esc(row.unidad || "") +
                    "</td>" +
                    '<td class="text-right"><strong>' +
                    esc(fmtNum(row.consumo_total)) +
                    "</strong></td>" +
                    "<td>" +
                    esc(roles) +
                    "</td>" +
                    "</tr>";
            });
            $(".tablaExplosionMpOrdCorte tbody").html(html);
        }

        var errores = res.errores || [];
        if (errores.length) {
            var msg =
                "Hay " +
                errores.length +
                " artículo(s) con receta incompleta o sin receta. Se calcularon solo los demás.";
            if (errores.length <= 5) {
                msg +=
                    " " +
                    errores
                        .map(function (e) {
                            return (e.articulo || e.modelo || "") + ": " + (e.mensaje || "");
                        })
                        .join(" · ");
            }
            $("#alertExplosionMpErrores").text(msg).show();
        } else {
            $("#alertExplosionMpErrores").hide().empty();
        }
    }

    function cargarTabla(f) {
        f = f || filtrosActuales();
        if (!hayFiltro(f)) {
            if (dt) {
                dt.destroy();
                dt = null;
            }
            limpiarExplosionMp();
            return;
        }

        if (dt) {
            dt.destroy();
            dt = null;
        }

        if (hayMpFiltro(f)) {
            $("#boxExplosionMpOrdCorte").show();
            $("#lblExplosionMpResumen").text("Calculando materia prima…");
            $(".tablaExplosionMpOrdCorte tbody").html(
                '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Calculando…</td></tr>'
            );
        } else {
            limpiarExplosionMp();
        }

        mostrarCarga("Consultando artículos…");
        $(".btnBuscarSeguimientoRecetas").prop("disabled", true);

        dt = $(".tablaSeguimientoRecetas").DataTable({
            ajax: {
                url: urlTabla(f),
                dataSrc: function (json) {
                    if (json && json.explosion) {
                        pintarExplosionMp(json.explosion, f);
                    } else {
                        limpiarExplosionMp();
                    }
                    ocultarCarga();
                    return json && json.data ? json.data : [];
                },
                error: function () {
                    ocultarCarga();
                    limpiarExplosionMp();
                    Command: toastr["error"]("No se pudo cargar la tabla");
                },
            },
            deferRender: true,
            retrieve: false,
            processing: true,
            pageLength: 20,
            lengthMenu: [
                [20, 40, 60, -1],
                [20, 40, 60, "Todos"],
            ],
            language: {
                sProcessing: "Procesando...",
                sLengthMenu: "Mostrar _MENU_ registros",
                sZeroRecords: "No se encontraron resultados",
                sEmptyTable: "Ningún dato disponible en esta tabla",
                sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
                sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
                sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
                sInfoPostFix: "",
                sSearch: "Buscar:",
                sUrl: "",
                sInfoThousands: ",",
                sLoadingRecords: "Cargando...",
                oPaginate: {
                    sFirst: "Primero",
                    sLast: "Último",
                    sNext: "Siguiente",
                    sPrevious: "Anterior",
                },
                oAria: {
                    sSortAscending: ": Activar para ordenar la columna de manera ascendente",
                    sSortDescending: ": Activar para ordenar la columna de manera descendente",
                },
            },
        });
    }

    function aplicarBusqueda(actualizarHistorial) {
        var f = filtrosActuales();
        if (!hayFiltro(f)) {
            Command: toastr["warning"]("Elige línea, sublínea o materia prima");
            return;
        }
        if (actualizarHistorial !== false) {
            escribirFiltrosUrl(f.linea, f.sublinea, f.mp);
        }
        actualizarExportExcel(f);
        cargarTabla(f);
    }

    function restaurarDesdeUrl() {
        var f = leerFiltrosUrl();
        restaurando = true;
        mostrarCarga("Preparando filtros…");

        return $.when(cargarLineas(f.linea), cargarSublineas(f.sublinea))
            .then(function () {
                if (f.sublinea && !f.linea) {
                    return sincronizarLineaDesdeSublinea();
                }
            })
            .then(function () {
                if (hayFiltro(f)) {
                    return cargarMps(f.mp);
                }
                return limpiarMpSelect();
            })
            .always(function () {
                restaurando = false;
                ocultarCarga();
                actualizarExportExcel(f);
                if (hayFiltro(f)) {
                    cargarTabla(f);
                }
            });
    }

    $("#btnExportSeguimientoRecetas").on("click", function (e) {
        if (!hayFiltro()) {
            e.preventDefault();
            Command: toastr["warning"]("Elige línea, sublínea o materia prima antes de exportar");
        }
    });

    $("#selSegRecetaLinea").on("changed.bs.select", function () {
        if (restaurando) {
            return;
        }
        $("#selSegRecetaSublinea").val("");
        $("#selSegRecetaMp").val("");
        cargarSublineas("").always(function () {
            limpiarMpSelect("-------- Elija sublínea o busque por línea -------");
            var ff = filtrosActuales();
            if (ff.linea) {
                cargarMps("");
            }
        });
    });

    $("#selSegRecetaSublinea").on("changed.bs.select", function () {
        if (restaurando) {
            return;
        }
        $("#selSegRecetaMp").val("");
        $.when(sincronizarLineaDesdeSublinea()).always(function () {
            cargarMps("");
        });
    });

    $(".btnBuscarSeguimientoRecetas").on("click", function () {
        aplicarBusqueda(true);
    });

    $(".btnLimpiarSeguimientoRecetas").on("click", function () {
        restaurando = true;
        $("#selSegRecetaLinea").val("");
        $("#selSegRecetaSublinea").val("");
        $("#selSegRecetaMp").val("");
        escribirFiltrosUrl("", "", "");
        actualizarExportExcel();
        $.when(cargarSublineas(""))
            .then(function () {
                return limpiarMpSelect();
            })
            .always(function () {
                $("#selSegRecetaLinea").selectpicker("refresh");
                restaurando = false;
                cargarTabla();
            });
    });

    restaurarDesdeUrl();
})();
