(function ($) {
    "use strict";

    var URL = "ajax/categorias-modelos.ajax.php";
    var puedeEditar = !!window.CAT_SUB_MODELOS_PUEDE_EDITAR;
    var categorias = [];
    var subcategorias = [];
    var conteos = { activos: 0, pendientes: 0, clasificados: 0 };
    var chartCat = null;
    var chartSub = null;

    var PALETA = [
        "#3c8dbc", "#00a65a", "#f39c12", "#dd4b39", "#605ca8",
        "#00c0ef", "#d81b60", "#39cccc", "#ff851b", "#001f3f",
        "#7d26cd", "#2ecc71", "#e74c3c", "#3498db", "#9b59b6"
    ];

    function post(accion, data) {
        if (typeof data === "string") {
            data = (data ? data + "&" : "") + "accion=" + encodeURIComponent(accion);
        } else {
            data = $.extend({}, data || {});
            data.accion = accion;
        }
        return $.ajax({ url: URL, method: "POST", dataType: "json", data: data });
    }

    function alerta(tipo, msg) {
        if (typeof swal === "function") {
            swal({ type: tipo === "danger" ? "error" : tipo, title: msg, confirmButtonText: "Cerrar" });
            return;
        }
        alert(msg);
    }

    function escapar(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function badgeEstado(estado) {
        return Number(estado) === 1
            ? '<span class="label label-success">Activo</span>'
            : '<span class="label label-danger">Inactivo</span>';
    }

    function slugPreview(texto) {
        var map = {
            "á": "A", "à": "A", "ä": "A", "â": "A", "ã": "A",
            "é": "E", "è": "E", "ë": "E", "ê": "E",
            "í": "I", "ì": "I", "ï": "I", "î": "I",
            "ó": "O", "ò": "O", "ö": "O", "ô": "O", "õ": "O",
            "ú": "U", "ù": "U", "ü": "U", "û": "U",
            "ñ": "N", "ç": "C",
            "Á": "A", "É": "E", "Í": "I", "Ó": "O", "Ú": "U", "Ñ": "N"
        };
        var t = String(texto || "");
        Object.keys(map).forEach(function (k) {
            t = t.split(k).join(map[k]);
        });
        t = t.toUpperCase().replace(/[^A-Z0-9]+/g, "_").replace(/^_|_$/g, "").replace(/_+/g, "_");
        return t || "ITEM";
    }

    function actualizarPreviewCat() {
        if (Number($("#catModeloId").val()) > 0) {
            return;
        }
        $("#catModeloCodigo").val(slugPreview($("#catModeloNombre").val()).substring(0, 40));
    }

    function actualizarPreviewSub() {
        if (Number($("#subModeloId").val()) > 0) {
            return;
        }
        var idCat = $("#subModeloCategoria").val();
        var cat = categorias.filter(function (c) { return String(c.id) === String(idCat); })[0];
        var pref = cat ? slugPreview(cat.codigo).substring(0, 20) : "SUB";
        var slug = slugPreview($("#subModeloNombre").val()).substring(0, 40);
        $("#subModeloCodigo").val((pref + "_" + slug).substring(0, 70));
    }

    function destruirChart(ch) {
        if (ch && typeof ch.destroy === "function") {
            try { ch.destroy(); } catch (e) { /* ignore */ }
        }
        return null;
    }

    function esChartV1() {
        try {
            var c = document.createElement("canvas");
            var probe = new Chart(c.getContext("2d"));
            return typeof probe.Doughnut === "function";
        } catch (e) {
            return false;
        }
    }

    function renderGraficos() {
        if (typeof Chart === "undefined") {
            return;
        }

        var labelsCat = [];
        var dataCat = [];
        var colorsCat = [];
        categorias.forEach(function (c, i) {
            var n = Number(c.modelos_activos) || 0;
            labelsCat.push(c.nombre);
            dataCat.push(n);
            colorsCat.push(PALETA[i % PALETA.length]);
        });
        var pendientes = Number(conteos.pendientes) || 0;
        if (pendientes > 0) {
            labelsCat.push("Sin clasificar");
            dataCat.push(pendientes);
            colorsCat.push("#bdbdbd");
        }

        var tieneCat = dataCat.some(function (n) { return n > 0; });
        $("#chartCatModelos").toggle(tieneCat);
        $("#chartCatModelosVacio").toggle(!tieneCat);
        chartCat = destruirChart(chartCat);

        if (tieneCat) {
            var ctxCat = document.getElementById("chartCatModelos");
            if (ctxCat) {
                if (esChartV1()) {
                    var segmentos = [];
                    labelsCat.forEach(function (lab, i) {
                        if (dataCat[i] <= 0) {
                            return;
                        }
                        segmentos.push({
                            value: dataCat[i],
                            color: colorsCat[i],
                            highlight: colorsCat[i],
                            label: lab
                        });
                    });
                    chartCat = new Chart(ctxCat.getContext("2d")).Doughnut(segmentos, {
                        responsive: true,
                        maintainAspectRatio: false,
                        percentageInnerCutout: 55,
                        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>"
                    });
                } else {
                    chartCat = new Chart(ctxCat.getContext("2d"), {
                        type: "doughnut",
                        data: {
                            labels: labelsCat,
                            datasets: [{ data: dataCat, backgroundColor: colorsCat }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: "bottom" }
                        }
                    });
                }
            }
        }

        var idFiltro = $("#filtroCatSubAdmin").val();
        var labelsSub = [];
        var labelsSubFull = [];
        var dataSub = [];
        var colorsSub = [];
        subcategorias.forEach(function (s, i) {
            if (idFiltro && String(s.id_categoria) !== String(idFiltro)) {
                return;
            }
            var n = Number(s.modelos_activos) || 0;
            if (n <= 0) {
                return;
            }
            var full = (s.nombre_categoria ? s.nombre_categoria + " › " : "") + s.nombre;
            labelsSubFull.push(full);
            labelsSub.push(String(labelsSub.length + 1));
            dataSub.push(n);
            colorsSub.push(PALETA[i % PALETA.length]);
        });

        var tieneSub = dataSub.length > 0;
        $("#chartSubModelos").toggle(tieneSub);
        $("#chartSubModelosVacio").toggle(!tieneSub);
        var $leyenda = $("#leyendaSubModelos").empty().toggle(tieneSub);
        chartSub = destruirChart(chartSub);

        if (tieneSub) {
            labelsSubFull.forEach(function (lab, i) {
                $leyenda.append(
                    '<li style="margin-bottom:4px;line-height:1.3;">' +
                    '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' +
                    colorsSub[i] + ';margin-right:6px;"></span>' +
                    "<strong>" + (i + 1) + ".</strong> " + escapar(lab) +
                    ' <span class="text-muted">(' + dataSub[i] + ")</span></li>"
                );
            });

            var ctxSub = document.getElementById("chartSubModelos");
            if (ctxSub) {
                if (esChartV1()) {
                    chartSub = new Chart(ctxSub.getContext("2d")).Bar({
                        labels: labelsSub,
                        datasets: [{
                            fillColor: "rgba(0,166,90,0.75)",
                            strokeColor: "rgba(0,166,90,1)",
                            highlightFill: "rgba(0,166,90,0.95)",
                            highlightStroke: "rgba(0,166,90,1)",
                            data: dataSub
                        }]
                    }, {
                        responsive: true,
                        maintainAspectRatio: false,
                        scaleBeginAtZero: true,
                        scaleIntegersOnly: true,
                        scaleFontSize: 10,
                        barShowStroke: false,
                        tooltipTemplate: "<%=label%>: <%= value %> modelos"
                    });
                } else {
                    chartSub = new Chart(ctxSub.getContext("2d"), {
                        type: "bar",
                        data: {
                            labels: labelsSub,
                            datasets: [{
                                label: "Modelos activos",
                                data: dataSub,
                                backgroundColor: colorsSub
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            scales: {
                                yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                            },
                            tooltips: {
                                callbacks: {
                                    title: function (items) {
                                        var idx = items[0] ? items[0].index : 0;
                                        return labelsSubFull[idx] || "";
                                    },
                                    label: function (item) {
                                        return item.yLabel + " modelos";
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        $("#catSubResumenConteos").text(
            "Activos: " + (conteos.activos || 0) +
            " · Clasificados: " + (conteos.clasificados || 0) +
            " · Pendientes: " + (conteos.pendientes || 0)
        );
    }

    function cargar() {
        return post("listarAdmin").done(function (resp) {
            if (!resp || !resp.ok) {
                alerta("danger", (resp && resp.mensaje) || "No se pudo cargar");
                return;
            }
            categorias = resp.categorias || [];
            subcategorias = resp.subcategorias || [];
            conteos = resp.conteos || { activos: 0, pendientes: 0, clasificados: 0 };
            renderCategorias();
            poblarFiltros();
            renderSubcategorias();
            renderGraficos();
        });
    }

    function poblarFiltros() {
        var $filtro = $("#filtroCatSubAdmin");
        var actual = $filtro.val() || "";
        $filtro.empty().append('<option value="">Todas</option>');
        categorias.forEach(function (c) {
            $filtro.append('<option value="' + c.id + '">' + escapar(c.nombre) + "</option>");
        });
        $filtro.val(actual);

        var $sel = $("#subModeloCategoria");
        if ($sel.length) {
            $sel.empty();
            categorias.forEach(function (c) {
                if (Number(c.estado) !== 1) {
                    return;
                }
                $sel.append('<option value="' + c.id + '">' + escapar(c.nombre) + "</option>");
            });
            if (!$sel.children().length) {
                categorias.forEach(function (c) {
                    $sel.append('<option value="' + c.id + '">' + escapar(c.nombre) + "</option>");
                });
            }
        }
    }

    function renderCategorias() {
        var $tb = $("#tablaCategoriasModelo tbody").empty();
        if (!categorias.length) {
            $tb.append('<tr><td colspan="7" class="text-center text-muted">Sin categorías</td></tr>');
            return;
        }
        categorias.forEach(function (c) {
            var acciones = puedeEditar
                ? '<button type="button" class="btn btn-xs btn-warning btnEditarCatModelo" data-id="' + c.id + '"><i class="fa fa-pencil"></i></button>'
                : "—";
            $tb.append(
                "<tr>" +
                "<td><code>" + escapar(c.codigo) + "</code></td>" +
                "<td>" + escapar(c.nombre) + "</td>" +
                "<td>" + escapar(c.orden) + "</td>" +
                "<td>" + escapar(c.total_subcategorias) + "</td>" +
                "<td>" + escapar(c.modelos_activos) + "</td>" +
                "<td>" + badgeEstado(c.estado) + "</td>" +
                "<td>" + acciones + "</td>" +
                "</tr>"
            );
        });
    }

    function renderSubcategorias() {
        var idCat = $("#filtroCatSubAdmin").val();
        var filas = subcategorias.filter(function (s) {
            return !idCat || String(s.id_categoria) === String(idCat);
        });
        var $tb = $("#tablaSubcategoriasModelo tbody").empty();
        if (!filas.length) {
            $tb.append('<tr><td colspan="7" class="text-center text-muted">Sin subcategorías</td></tr>');
            return;
        }
        filas.forEach(function (s) {
            var nModelos = Number(s.modelos_activos) || 0;
            var celdaModelos = nModelos > 0
                ? '<a href="#" class="btnVerModelosSub" data-id="' + s.id + '" title="Ver modelos">' + nModelos + '</a>'
                : '<span class="text-muted">0</span>';
            var acciones = '<button type="button" class="btn btn-xs btn-info btnVerModelosSub" data-id="' + s.id + '" title="Ver modelos"><i class="fa fa-eye"></i></button>';
            if (puedeEditar) {
                acciones = '<div class="btn-group" style="display:inline-flex;flex-wrap:nowrap;white-space:nowrap;">' +
                    acciones +
                    '<button type="button" class="btn btn-xs btn-warning btnEditarSubModelo" data-id="' + s.id + '" title="Editar"><i class="fa fa-pencil"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-danger btnEliminarSubModelo" data-id="' + s.id + '" data-nombre="' + escapar(s.nombre) + '" title="Eliminar"><i class="fa fa-trash"></i></button>' +
                    "</div>";
            }
            $tb.append(
                "<tr>" +
                "<td><code>" + escapar(s.codigo) + "</code></td>" +
                "<td>" + escapar(s.nombre) + "</td>" +
                "<td>" + escapar(s.nombre_categoria) + "</td>" +
                "<td>" + escapar(s.orden) + "</td>" +
                "<td>" + celdaModelos + "</td>" +
                "<td>" + badgeEstado(s.estado) + "</td>" +
                "<td style='white-space:nowrap;'>" + acciones + "</td>" +
                "</tr>"
            );
        });
    }

    function abrirModelosSubcategoria(idSub) {
        idSub = Number(idSub);
        if (!idSub) {
            return;
        }
        $("#tituloModelosSubcategoria").text("…");
        $("#resumenModelosSubcategoria").text("Cargando…");
        $("#tablaModelosSubcategoria tbody").html(
            '<tr><td colspan="8" class="text-center text-muted">Cargando…</td></tr>'
        );
        $("#linkClasificarDesdeSub").attr("href", "index.php?ruta=categorias-modelos");
        $("#modalModelosSubcategoria").modal("show");

        post("listarModelosSubcategoria", { id_subcategoria: idSub }).done(function (resp) {
            if (!resp || !resp.ok) {
                $("#tablaModelosSubcategoria tbody").html(
                    '<tr><td colspan="8" class="text-center text-danger">' +
                    escapar((resp && resp.mensaje) || "Error") + "</td></tr>"
                );
                return;
            }
            var sub = resp.subcategoria || {};
            var titulo = (sub.categoria ? sub.categoria + " › " : "") + (sub.nombre || "");
            $("#tituloModelosSubcategoria").text(titulo);
            $("#resumenModelosSubcategoria").text(
                (resp.activos || 0) + " activos · " + (resp.total || 0) + " en total (incluye inactivos asignados)"
            );
            $("#linkClasificarDesdeSub").attr(
                "href",
                "index.php?ruta=categorias-modelos&estado_lista=clasificados"
            );

            var modelos = resp.modelos || [];
            var $tb = $("#tablaModelosSubcategoria tbody").empty();
            if (!modelos.length) {
                $tb.append('<tr><td colspan="8" class="text-center text-muted">Ningún modelo asignado</td></tr>');
                return;
            }
            modelos.forEach(function (m) {
                var img = m.imagen || "vistas/img/modelos/default/anonymous.png";
                var estadoLabel = m.estado === "ACTIVO"
                    ? '<span class="label label-success">Activo</span>'
                    : '<span class="label label-default">' + escapar(m.estado || "—") + "</span>";
                var asignado = escapar(m.usuario_nombre || "") +
                    (m.fecha ? "<br><small>" + escapar(m.fecha) + "</small>" : "");
                var linkFicha = "index.php?ruta=ficha-gerencial-modelos&modelo=" + encodeURIComponent(m.modelo);
                $tb.append(
                    "<tr>" +
                    '<td><img src="' + escapar(img) + '" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:3px;"></td>' +
                    '<td><a href="' + linkFicha + '"><strong>' + escapar(m.modelo) + "</strong></a></td>" +
                    "<td>" + escapar(m.nombre) + "</td>" +
                    "<td>" + escapar(m.marca) + "</td>" +
                    "<td>" + escapar(m.tipo || "—") + "</td>" +
                    "<td>" + escapar(m.linea || "—") + "</td>" +
                    "<td>" + estadoLabel + "</td>" +
                    "<td style='font-size:12px;'>" + asignado + "</td>" +
                    "</tr>"
                );
            });
        }).fail(function () {
            $("#tablaModelosSubcategoria tbody").html(
                '<tr><td colspan="8" class="text-center text-danger">Error de red</td></tr>'
            );
        });
    }

    function abrirCategoria(item) {
        var esNuevo = !item;
        $("#tituloModalCategoriaModelo").text(esNuevo ? "Nueva categoría" : "Editar categoría");
        $("#catModeloId").val(esNuevo ? 0 : item.id);
        $("#catModeloNombre").val(esNuevo ? "" : item.nombre);
        $("#catModeloOrden").val(esNuevo ? 0 : item.orden);
        $("#catModeloEstado").val(esNuevo ? "1" : String(item.estado));
        if (esNuevo) {
            $("#catModeloCodigo").val("");
            $("#ayudaCatModeloCodigo").text("Vista previa; el código definitivo se genera al guardar.");
            actualizarPreviewCat();
        } else {
            $("#catModeloCodigo").val(item.codigo);
            $("#ayudaCatModeloCodigo").text("Código fijo (no se modifica).");
        }
        $("#modalCategoriaModelo").modal("show");
    }

    function abrirSubcategoria(item) {
        var esNuevo = !item;
        poblarFiltros();
        $("#tituloModalSubcategoriaModelo").text(esNuevo ? "Nueva subcategoría" : "Editar subcategoría");
        $("#subModeloId").val(esNuevo ? 0 : item.id);
        $("#subModeloNombre").val(esNuevo ? "" : item.nombre);
        $("#subModeloOrden").val(esNuevo ? 0 : item.orden);
        $("#subModeloEstado").val(esNuevo ? "1" : String(item.estado));
        $("#subModeloCategoria").val(esNuevo ? ($("#filtroCatSubAdmin").val() || "") : String(item.id_categoria));
        if (!$("#subModeloCategoria").val() && categorias.length) {
            $("#subModeloCategoria").val(String(categorias[0].id));
        }
        if (esNuevo) {
            $("#subModeloCodigo").val("");
            $("#ayudaSubModeloCodigo").text("Vista previa; el código definitivo se genera al guardar.");
            actualizarPreviewSub();
        } else {
            $("#subModeloCodigo").val(item.codigo);
            $("#ayudaSubModeloCodigo").text("Código fijo (no se modifica).");
        }
        $("#modalSubcategoriaModelo").modal("show");
    }

    $(function () {
        if (!$("#tablaCategoriasModelo").length) {
            return;
        }
        cargar();

        $("#filtroCatSubAdmin").on("change", function () {
            renderSubcategorias();
            renderGraficos();
        });

        $("#btnNuevaCategoriaModelo").on("click", function () {
            abrirCategoria(null);
        });
        $("#btnNuevaSubcategoriaModelo").on("click", function () {
            abrirSubcategoria(null);
        });

        $("#catModeloNombre").on("input", actualizarPreviewCat);
        $("#subModeloNombre").on("input", actualizarPreviewSub);
        $("#subModeloCategoria").on("change", actualizarPreviewSub);

        $(document).on("click", ".btnEditarCatModelo", function () {
            var id = Number($(this).data("id"));
            var item = categorias.filter(function (c) { return Number(c.id) === id; })[0];
            if (item) {
                abrirCategoria(item);
            }
        });
        $(document).on("click", ".btnVerModelosSub", function (e) {
            e.preventDefault();
            abrirModelosSubcategoria($(this).data("id"));
        });

        $(document).on("click", ".btnEditarSubModelo", function () {
            var id = Number($(this).data("id"));
            var item = subcategorias.filter(function (s) { return Number(s.id) === id; })[0];
            if (item) {
                abrirSubcategoria(item);
            }
        });

        $(document).on("click", ".btnEliminarSubModelo", function () {
            var id = Number($(this).data("id"));
            var nombre = $(this).data("nombre") || "";
            if (!id) {
                return;
            }
            if (!window.confirm("¿Eliminar la subcategoría \"" + nombre + "\"?\nSolo es posible si no tiene modelos ni historial.")) {
                return;
            }
            post("eliminarSubcategoria", { id: id }).done(function (resp) {
                if (!resp || !resp.ok) {
                    alerta("danger", (resp && resp.mensaje) || "No se pudo eliminar");
                    return;
                }
                alerta("success", resp.mensaje || "Eliminada");
                cargar();
            }).fail(function () {
                alerta("danger", "Error de red");
            });
        });

        $("#formCategoriaModelo").on("submit", function (e) {
            e.preventDefault();
            post("guardarCategoria", $(this).serialize()).done(function (resp) {
                if (!resp || !resp.ok) {
                    alerta("danger", (resp && resp.mensaje) || "Error al guardar");
                    return;
                }
                $("#modalCategoriaModelo").modal("hide");
                var msg = resp.mensaje || "Guardado";
                if (resp.codigo) {
                    msg += " · código " + resp.codigo;
                }
                alerta("success", msg);
                cargar();
            }).fail(function () {
                alerta("danger", "Error de red");
            });
        });

        $("#formSubcategoriaModelo").on("submit", function (e) {
            e.preventDefault();
            post("guardarSubcategoria", $(this).serialize()).done(function (resp) {
                if (!resp || !resp.ok) {
                    alerta("danger", (resp && resp.mensaje) || "Error al guardar");
                    return;
                }
                $("#modalSubcategoriaModelo").modal("hide");
                var msg = resp.mensaje || "Guardado";
                if (resp.codigo) {
                    msg += " · código " + resp.codigo;
                }
                alerta("success", msg);
                cargar();
            }).fail(function () {
                alerta("danger", "Error de red");
            });
        });
    });
})(jQuery);
