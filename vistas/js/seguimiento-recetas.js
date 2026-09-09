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
            sublinea: (params.get("sublinea") || "").trim().toUpperCase(),
            mp: (params.get("mp") || "").trim(),
        };
    }

    function escribirFiltrosUrl(sublinea, mp) {
        var url = new URL(window.location.href);
        url.searchParams.delete("linea");
        url.searchParams.delete("sublinea");
        url.searchParams.delete("mp");
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
            sublinea: ($("#selSegRecetaSublinea").val() || "").trim().toUpperCase(),
            mp: ($("#selSegRecetaMp").val() || "").trim(),
        };
    }

    function hayFiltro(f) {
        f = f || filtrosActuales();
        return !!(f.sublinea || f.mp);
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
            var selected = seleccion && String(val).toUpperCase() === seleccion.toUpperCase() ? " selected" : "";
            html +=
                '<option value="' +
                esc(val) +
                '"' +
                selected +
                ">" +
                esc(labelFn(item)) +
                "</option>";
        });
        $sel.html(html);
        $sel.selectpicker("refresh");
    }

    function limpiarMpSelect(mensaje) {
        llenarSelect($("#selSegRecetaMp"), [], mensaje || "-------- Materia prima (tela) -------", "mp_codigo", function () {
            return "";
        });
        return $.Deferred().resolve().promise();
    }

    function cargarSublineas(seleccion) {
        return postFiltro("sublineas").done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            llenarSelect(
                $("#selSegRecetaSublinea"),
                res.data,
                "-------- Sublínea tela -------",
                "codigo_sublinea",
                function (item) {
                    var nom = item.nombre ? " — " + item.nombre : "";
                    return (item.codigo_sublinea || "") + nom;
                },
                { seleccion: seleccion }
            );
        });
    }

    function cargarMps(seleccion) {
        var f = filtrosActuales();
        return postFiltro("mps", { sublinea: f.sublinea }).done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            llenarSelect(
                $("#selSegRecetaMp"),
                res.data,
                "-------- Materia prima (tela) -------",
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

    function urlExportExcel(f) {
        f = f || filtrosActuales();
        return (
            "vistas/reportes_excel/rpt_seguimiento_recetas.php?" +
            "sublinea=" +
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

    function fmtNum4(valor) {
        var n = parseFloat(valor);
        if (isNaN(n)) {
            return "0.0000";
        }
        return n.toFixed(4);
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

    function limpiarTotalesArticulos() {
        $("#segRecetasTotalesFoot").hide();
    }

    function pintarTotalesArticulos(totales) {
        if (!totales) {
            limpiarTotalesArticulos();
            return;
        }
        $("#segRecTotStock").html("<strong>" + esc(fmtNum(totales.stock)) + "</strong>");
        $("#segRecTotPedidos").html("<strong>" + esc(fmtNum(totales.pedidos)) + "</strong>");
        $("#segRecTotTaller").html("<strong>" + esc(fmtNum(totales.taller)) + "</strong>");
        $("#segRecTotServicio").html("<strong>" + esc(fmtNum(totales.servicio)) + "</strong>");
        $("#segRecTotArreglos").html("<strong>" + esc(fmtNum(totales.arreglos)) + "</strong>");
        $("#segRecTotAlmCorte").html("<strong>" + esc(fmtNum(totales.alm_corte)) + "</strong>");
        $("#segRecTotOrdCorte").html("<strong>" + esc(fmtNum(totales.ord_corte)) + "</strong>");
        $("#segRecTotMpOrdCorte").html("<strong>" + esc(fmtNum4(totales.consumo_ord_corte)) + "</strong>");
        $("#segRecetasTotalesFoot").show();
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
                '<tr><td colspan="8" class="text-center text-danger">No se pudo calcular la explosión</td></tr>'
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
                '<tr><td colspan="8" class="text-center text-muted">Sin materia prima calculada</td></tr>'
            );
        } else {
            var html = "";
            filas.forEach(function (row) {
                var roles = row.roles && row.roles.length ? row.roles.join(" / ") : "—";
                if (row.es_tela_principal) {
                    roles = (roles !== "—" ? roles + " · " : "") + "Tela principal";
                }
                var alcanza = Number(row.mp_alcanza) === 1;
                var alcanzaHtml = alcanza
                    ? '<span class="label label-success">Sí</span>'
                    : '<span class="label label-danger">No</span>';
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
                    esc(fmtNum4(row.consumo_total)) +
                    "</strong></td>" +
                    '<td class="text-right">' +
                    esc(fmtNum(row.mp_stock)) +
                    "</td>" +
                    '<td class="text-center">' +
                    alcanzaHtml +
                    "</td>" +
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
            limpiarTotalesArticulos();
            return;
        }

        if (dt) {
            dt.destroy();
            dt = null;
        }

        limpiarTotalesArticulos();

        if (hayMpFiltro(f)) {
            $("#boxExplosionMpOrdCorte").show();
            $("#lblExplosionMpResumen").text("Calculando materia prima…");
            $(".tablaExplosionMpOrdCorte tbody").html(
                '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Calculando…</td></tr>'
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
                    if (json && json.totales) {
                        pintarTotalesArticulos(json.totales);
                    } else {
                        limpiarTotalesArticulos();
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
            Command: toastr["warning"]("Elige sublínea o materia prima de tela");
            return;
        }
        if (actualizarHistorial !== false) {
            escribirFiltrosUrl(f.sublinea, f.mp);
        }
        actualizarExportExcel(f);
        cargarTabla(f);
    }

    function restaurarDesdeUrl() {
        var f = leerFiltrosUrl();
        restaurando = true;
        mostrarCarga("Preparando filtros…");

        return cargarSublineas(f.sublinea)
            .then(function () {
                return cargarMps(f.mp);
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
            Command: toastr["warning"]("Elige sublínea o materia prima antes de exportar");
        }
    });

    $("#selSegRecetaSublinea").on("changed.bs.select", function () {
        if (restaurando) {
            return;
        }
        $("#selSegRecetaMp").val("");
        cargarMps("");
    });

    $(".btnBuscarSeguimientoRecetas").on("click", function () {
        aplicarBusqueda(true);
    });

    $(".btnLimpiarSeguimientoRecetas").on("click", function () {
        restaurando = true;
        $("#selSegRecetaSublinea").val("");
        $("#selSegRecetaMp").val("");
        escribirFiltrosUrl("", "");
        actualizarExportExcel();
        $.when(cargarSublineas(""))
            .then(function () {
                return limpiarMpSelect();
            })
            .always(function () {
                restaurando = false;
                cargarTabla();
            });
    });

    restaurarDesdeUrl();
})();
