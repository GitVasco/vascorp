(function ($) {
    "use strict";

    var URL = "ajax/categorias-modelos.ajax.php";
    var puedeEditar = !!window.CAT_MODELOS_PUEDE_EDITAR;
    var modeloInicial = String(window.CAT_MODELOS_MODELO_INICIAL || "").trim();
    var catalogo = [];
    var codigoASub = {};
    var destino = null;
    var filasDisp = [];
    var seleccion = {};
    var seleccionDestino = {};
    var filasDestino = [];
    var debounceTimer = null;
    var cargandoDisp = false;

    function post(accion, data) {
        data = data || {};
        data.accion = accion;
        return $.ajax({ url: URL, method: "POST", dataType: "json", data: data });
    }

    function alerta(tipo, mensaje) {
        if (typeof swal === "function") {
            swal({
                type: tipo === "danger" ? "error" : tipo,
                title: mensaje,
                confirmButtonText: "Cerrar"
            });
            return;
        }
        window.alert(mensaje);
    }

    function confirmar(mensaje, onYes, opts) {
        opts = opts || {};
        if (typeof swal === "function") {
            // SweetAlert2 (v7): promesa; el 2.º arg callback de SweetAlert1 no aplica
            swal({
                title: opts.titulo || "Confirmar",
                text: mensaje,
                type: opts.tipo || "warning",
                showCancelButton: true,
                confirmButtonColor: opts.confirmColor || "#DD6B55",
                cancelButtonColor: "#aaa",
                confirmButtonText: opts.confirmText || "Sí",
                cancelButtonText: opts.cancelText || "Cancelar"
            }).then(function (result) {
                if (result && result.value && typeof onYes === "function") {
                    onYes();
                }
            });
            return;
        }
        if (window.confirm(mensaje) && typeof onYes === "function") {
            onYes();
        }
    }

    function escapar(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function etiquetaSubCodigo(codigo) {
        var s = codigoASub[codigo];
        return s ? (s.cat + " › " + s.nombre) : codigo;
    }

    function modelosSel() {
        return Object.keys(seleccion).filter(function (k) { return seleccion[k]; });
    }

    function modelosSelDest() {
        return Object.keys(seleccionDestino).filter(function (k) { return seleccionDestino[k]; });
    }

    function actualizarSelDestBarra() {
        var n = modelosSelDest().length;
        $("#catMatchSelDestInfo").text(n ? (n + " sel.") : "0");
        $("#catMatchQuitar").prop("disabled", n < 1);
        $("#catMatchMover").prop("disabled", n < 1 || !destino);
    }

    function actualizarSelBarra() {
        var n = modelosSel().length;
        $("#catMatchSelInfo").text(n + " seleccionado" + (n === 1 ? "" : "s"));
        var $btn = $("#catMatchAgregar");
        if (!$btn.length) {
            return;
        }
        if (!destino) {
            $btn.prop("disabled", true).html('<i class="fa fa-arrow-right"></i> Elige subcategoría');
        } else if (n < 1) {
            $btn.prop("disabled", true).html('<i class="fa fa-arrow-right"></i> Agregar al destino');
        } else {
            $btn.prop("disabled", false).html(
                '<i class="fa fa-arrow-right"></i> Agregar ' + n + " → " + escapar(destino.nombre)
            );
        }
    }

    function actualizarConteos(c) {
        if (!c) {
            return;
        }
        $("#catMatchPendientes").text(c.pendientes != null ? c.pendientes : "—");
        $("#catMatchClasificados").text(c.clasificados != null ? c.clasificados : "—");
    }

    function renderArbol() {
        var $arbol = $("#catMatchArbol").empty();
        if (!catalogo.length) {
            $arbol.html('<p class="text-muted text-center" style="padding:20px;">Sin catálogo</p>');
            return;
        }
        catalogo.forEach(function (cat) {
            var totalCat = 0;
            (cat.subcategorias || []).forEach(function (s) {
                totalCat += Number(s.modelos_activos) || 0;
            });
            var $bloque = $('<div class="cat-match-cat"></div>');
            $bloque.append(
                '<div class="cat-match-cat-head">' + escapar(cat.nombre) +
                ' <span class="badge">' + totalCat + "</span></div>"
            );
            (cat.subcategorias || []).forEach(function (sub) {
                var active = destino && String(destino.id) === String(sub.id) ? " active" : "";
                var $btn = $(
                    '<button type="button" class="cat-match-sub' + active + '" data-id="' + sub.id + '">' +
                    escapar(sub.nombre) +
                    ' <span class="badge bg-green">' + (sub.modelos_activos || 0) + "</span>" +
                    "</button>"
                );
                $btn.data("sub", {
                    id: sub.id,
                    nombre: sub.nombre,
                    codigo: sub.codigo,
                    catId: cat.id,
                    catNombre: cat.nombre
                });
                $bloque.append($btn);
            });
            $arbol.append($bloque);
        });
    }

    function seleccionarDestino(sub) {
        destino = sub;
        $("#catMatchDestinoLabel").text(sub.catNombre + " › " + sub.nombre);
        $("#catMatchDispTitulo").text("(" + sub.catNombre + " › " + sub.nombre + ")");
        $(".cat-match-sub").removeClass("active");
        $('.cat-match-sub[data-id="' + sub.id + '"]').addClass("active");
        seleccion = {};
        actualizarSelBarra();
        actualizarSelDestBarra();
        cargarDisponibles();
        cargarEnDestino();
    }

    function cargarCatalogo() {
        return post("catalogo").done(function (resp) {
            if (!resp || !resp.ok) {
                return;
            }
            catalogo = resp.categorias || [];
            codigoASub = {};
            catalogo.forEach(function (cat) {
                (cat.subcategorias || []).forEach(function (sub) {
                    codigoASub[sub.codigo] = { id: sub.id, nombre: sub.nombre, cat: cat.nombre };
                });
            });
            actualizarConteos(resp.conteos);
            var $marca = $("#catMatchMarca").empty().append('<option value="">Marca</option>');
            (resp.marcas || []).forEach(function (m) {
                $marca.append('<option value="' + m.id + '">' + escapar(m.marca) + "</option>");
            });
            renderArbol();
        });
    }

    function refrescarCatalogoConteos() {
        post("catalogo").done(function (resp) {
            if (!resp || !resp.ok) {
                return;
            }
            catalogo = resp.categorias || [];
            actualizarConteos(resp.conteos);
            renderArbol();
            if (destino) {
                $('.cat-match-sub[data-id="' + destino.id + '"]').addClass("active");
            }
        });
    }

    function cargarDisponibles() {
        if (!destino || cargandoDisp) {
            if (!destino) {
                $("#catMatchListaDisp").html(
                    '<p class="text-muted text-center" style="padding:24px;">Selecciona una subcategoría a la izquierda</p>'
                );
            }
            return;
        }
        cargandoDisp = true;
        $("#catMatchListaDisp").html('<p class="text-muted text-center" style="padding:24px;">Cargando…</p>');
        post("listar", {
            q: $("#catMatchBuscar").val() || modeloInicial,
            id_marca: $("#catMatchMarca").val() || 0,
            id_categoria: 0,
            id_subcategoria: 0,
            estado_lista: "pendientes",
            pagina: 1,
            limite: 1000
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                $("#catMatchListaDisp").html(
                    '<p class="text-center text-danger">' + escapar((resp && resp.mensaje) || "Error") + "</p>"
                );
                return;
            }
            actualizarConteos(resp.conteos);
            filasDisp = resp.filas || [];
            renderDisponibles(filasDisp);
            actualizarSelBarra();
        }).fail(function () {
            $("#catMatchListaDisp").html('<p class="text-center text-danger">Error de red</p>');
        }).always(function () {
            cargandoDisp = false;
        });
    }

    function renderDisponibles(filas) {
        var $lista = $("#catMatchListaDisp").empty();
        if (!filas.length) {
            $lista.html('<p class="text-muted text-center" style="padding:24px;">No hay modelos pendientes</p>');
            return;
        }
        filas.forEach(function (f) {
            var checked = seleccion[f.modelo] ? " checked" : "";
            var selCls = seleccion[f.modelo] ? " selected" : "";
            var sug = "";
            if (f.sugerencia_id) {
                sug = '<span class="cat-match-sug">sugerida: ' +
                    escapar(etiquetaSubCodigo(f.sugerencia_codigo)) + "</span>";
            }
            var checkHtml = puedeEditar
                ? '<input type="checkbox" class="cat-match-check" data-modelo="' + escapar(f.modelo) + '"' + checked + ">"
                : "";
            $lista.append(
                '<div class="cat-match-item' + selCls + '" data-modelo="' + escapar(f.modelo) +
                '" data-sugerencia="' + (f.sugerencia_id || "") + '">' +
                checkHtml +
                '<img src="' + escapar(f.imagen || "vistas/img/modelos/default/anonymous.png") + '" alt="">' +
                '<div class="cat-match-item-body">' +
                "<strong>" + escapar(f.modelo) + "</strong>" + escapar(f.nombre) +
                '<span class="cat-match-item-meta">' + escapar(f.marca || "") +
                (f.tipo ? " · " + escapar(f.tipo) : "") +
                (f.linea ? " · " + escapar(f.linea) : "") +
                "</span>" + sug +
                "</div></div>"
            );
        });
        syncCheckAll();
    }

    function syncCheckAll() {
        var $c = $(".cat-match-check");
        if (!$c.length) {
            $("#catMatchCheckAllDisp").prop("checked", false).prop("indeterminate", false);
            return;
        }
        var n = $c.filter(":checked").length;
        $("#catMatchCheckAllDisp")
            .prop("checked", n === $c.length)
            .prop("indeterminate", n > 0 && n < $c.length);
    }

    function setSel(modelo, on) {
        if (on) {
            seleccion[modelo] = true;
        } else {
            delete seleccion[modelo];
        }
        var $item = $('.cat-match-item[data-modelo="' + modelo + '"]');
        $item.toggleClass("selected", !!on);
        $item.find(".cat-match-check").prop("checked", !!on);
    }

    function setSelDest(modelo, on) {
        if (on) {
            seleccionDestino[modelo] = true;
        } else {
            delete seleccionDestino[modelo];
        }
        var $item = $('#catMatchListaDestino .cat-match-item[data-modelo="' + modelo + '"]');
        $item.toggleClass("selected", !!on);
        $item.find(".cat-match-check-dest").prop("checked", !!on);
    }

    function syncCheckAllDest() {
        var $c = $(".cat-match-check-dest");
        if (!$c.length) {
            $("#catMatchCheckAllDest").prop("checked", false).prop("indeterminate", false);
            return;
        }
        var n = $c.filter(":checked").length;
        $("#catMatchCheckAllDest")
            .prop("checked", n === $c.length)
            .prop("indeterminate", n > 0 && n < $c.length);
    }

    function cargarEnDestino() {
        if (!destino) {
            $("#catMatchListaDestino").html('<p class="text-muted text-center">—</p>');
            $("#catMatchEnDestinoCount").text("0");
            seleccionDestino = {};
            actualizarSelDestBarra();
            return;
        }
        $("#catMatchListaDestino").html('<p class="text-muted text-center">Cargando…</p>');
        post("listarModelosSubcategoria", { id_subcategoria: destino.id }).done(function (resp) {
            if (!resp || !resp.ok) {
                $("#catMatchListaDestino").html('<p class="text-danger text-center">Error</p>');
                return;
            }
            filasDestino = (resp.modelos || []).filter(function (m) {
                return m.estado === "ACTIVO";
            });
            $("#catMatchEnDestinoCount").text(filasDestino.length);
            var $lista = $("#catMatchListaDestino").empty();
            if (!filasDestino.length) {
                $lista.html('<p class="text-muted text-center">Aún vacía</p>');
                syncCheckAllDest();
                actualizarSelDestBarra();
                return;
            }
            filasDestino.forEach(function (m) {
                var checked = seleccionDestino[m.modelo] ? " checked" : "";
                var selCls = seleccionDestino[m.modelo] ? " selected" : "";
                var checkHtml = puedeEditar
                    ? '<input type="checkbox" class="cat-match-check-dest" data-modelo="' + escapar(m.modelo) + '"' + checked + ">"
                    : "";
                $lista.append(
                    '<div class="cat-match-item' + selCls + '" data-modelo="' + escapar(m.modelo) + '">' +
                    checkHtml +
                    '<img src="' + escapar(m.imagen || "vistas/img/modelos/default/anonymous.png") + '" alt="">' +
                    '<div class="cat-match-item-body">' +
                    "<strong>" + escapar(m.modelo) + "</strong>" + escapar(m.nombre) +
                    '<span class="cat-match-item-meta">' + escapar(m.marca || "") +
                    (m.fecha ? " · " + escapar(m.fecha) : "") +
                    "</span></div></div>"
                );
            });
            // Mantener selección de modelos de otra sub (para "Mover aquí")
            var nFuera = 0;
            Object.keys(seleccionDestino).forEach(function (cod) {
                var enLista = filasDestino.some(function (m) { return m.modelo === cod; });
                if (!enLista) {
                    nFuera++;
                }
            });
            syncCheckAllDest();
            actualizarSelDestBarra();
            if (nFuera > 0) {
                $("#catMatchSelDestInfo").text(
                    modelosSelDest().length + " sel. (" + nFuera + " de otra sub)"
                );
            }
        });
    }

    function quitarDelDestino() {
        var modelos = modelosSelDest();
        if (!modelos.length || !puedeEditar) {
            return;
        }
        confirmar(
            "¿Quitar " + modelos.length + " modelo(s) de esta subcategoría?\nVolverán a Disponibles.",
            function () {
                $("#catMatchQuitar").prop("disabled", true);
                post("quitarLote", { modelos: JSON.stringify(modelos) }).done(function (resp) {
                    if (!resp || !resp.ok) {
                        alerta("error", (resp && resp.mensaje) || "No se pudo quitar");
                        actualizarSelDestBarra();
                        return;
                    }
                    seleccionDestino = {};
                    actualizarConteos(resp.conteos);
                    cargarDisponibles();
                    cargarEnDestino();
                    refrescarCatalogoConteos();
                    alerta("success", resp.mensaje || "Quitado correctamente");
                }).fail(function () {
                    alerta("error", "Error de red");
                }).always(function () {
                    actualizarSelDestBarra();
                });
            },
            { confirmText: "Quitar", confirmColor: "#d73925" }
        );
    }

    function moverAlDestinoActual() {
        var modelos = modelosSelDest();
        if (!modelos.length || !destino || !puedeEditar) {
            return;
        }
        var label = destino.catNombre + " › " + destino.nombre;
        confirmar(
            "¿Mover " + modelos.length + " modelo(s) a «" + label + "»?",
            function () {
                $("#catMatchMover").prop("disabled", true);
                post("asignarLote", {
                    id_subcategoria: destino.id,
                    modelos: JSON.stringify(modelos)
                }).done(function (resp) {
                    if (!resp || !resp.ok) {
                        alerta("error", (resp && resp.mensaje) || "No se pudo mover");
                        actualizarSelDestBarra();
                        return;
                    }
                    seleccionDestino = {};
                    actualizarConteos(resp.conteos);
                    cargarDisponibles();
                    cargarEnDestino();
                    refrescarCatalogoConteos();
                    alerta("success", resp.mensaje || "Movido correctamente");
                }).fail(function () {
                    alerta("error", "Error de red");
                }).always(function () {
                    actualizarSelDestBarra();
                });
            },
            { confirmText: "Mover", confirmColor: "#f39c12" }
        );
    }

    function agregarAlDestino() {
        if (!destino || !puedeEditar) {
            return;
        }
        var modelos = modelosSel();
        if (!modelos.length) {
            return;
        }
        var label = destino.catNombre + " › " + destino.nombre;
        confirmar(
            "¿Agregar " + modelos.length + " modelo(s) a «" + label + "»?",
            function () {
                $("#catMatchAgregar").prop("disabled", true);
                post("asignarLote", {
                    id_subcategoria: destino.id,
                    modelos: JSON.stringify(modelos)
                }).done(function (resp) {
                    if (!resp || !resp.ok) {
                        alerta("error", (resp && resp.mensaje) || "No se pudo asignar");
                        actualizarSelBarra();
                        return;
                    }
                    seleccion = {};
                    actualizarConteos(resp.conteos);
                    cargarDisponibles();
                    cargarEnDestino();
                    refrescarCatalogoConteos();
                    alerta("success", resp.mensaje || "Asignado correctamente");
                }).fail(function () {
                    alerta("error", "Error de red");
                }).always(function () {
                    actualizarSelBarra();
                });
            },
            { confirmText: "Agregar", confirmColor: "#00a65a" }
        );
    }

    $(function () {
        cargarCatalogo().then(function () {
            if (modeloInicial) {
                $("#catMatchBuscar").val(modeloInicial);
            }
            if (catalogo.length && catalogo[0].subcategorias && catalogo[0].subcategorias.length) {
                var first = catalogo[0].subcategorias[0];
                seleccionarDestino({
                    id: first.id,
                    nombre: first.nombre,
                    codigo: first.codigo,
                    catId: catalogo[0].id,
                    catNombre: catalogo[0].nombre
                });
            }
        });

        $(document).on("click", ".cat-match-sub", function () {
            seleccionarDestino($(this).data("sub"));
        });

        $("#catMatchBuscar").on("input", function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                cargarDisponibles();
            }, 280);
        });
        $("#catMatchMarca").on("change", function () {
            cargarDisponibles();
        });

        $("#catMatchCheckAllDisp").on("change", function () {
            var on = $(this).prop("checked");
            filasDisp.forEach(function (f) { setSel(f.modelo, on); });
            syncCheckAll();
            actualizarSelBarra();
        });
        $(document).on("change", ".cat-match-check", function () {
            setSel($(this).data("modelo"), $(this).prop("checked"));
            syncCheckAll();
            actualizarSelBarra();
        });
        $(document).on("click", ".cat-match-item", function (e) {
            if (!puedeEditar || $(e.target).is("input")) {
                return;
            }
            var modelo = $(this).data("modelo");
            if (!modelo) {
                return;
            }
            if ($(this).closest("#catMatchListaDisp").length) {
                setSel(modelo, !seleccion[modelo]);
                syncCheckAll();
                actualizarSelBarra();
            } else if ($(this).closest("#catMatchListaDestino").length) {
                setSelDest(modelo, !seleccionDestino[modelo]);
                syncCheckAllDest();
                actualizarSelDestBarra();
            }
        });

        $(document).on("change", ".cat-match-check-dest", function () {
            setSelDest($(this).data("modelo"), $(this).prop("checked"));
            syncCheckAllDest();
            actualizarSelDestBarra();
        });

        $("#catMatchCheckAllDest").on("change", function () {
            var on = $(this).prop("checked");
            filasDestino.forEach(function (m) { setSelDest(m.modelo, on); });
            syncCheckAllDest();
            actualizarSelDestBarra();
        });

        $("#catMatchQuitar").on("click", quitarDelDestino);
        $("#catMatchMover").on("click", moverAlDestinoActual);

        $("#catMatchSelSugeridos").on("click", function () {
            filasDisp.forEach(function (f) {
                if (f.sugerencia_id) {
                    setSel(f.modelo, true);
                }
            });
            syncCheckAll();
            actualizarSelBarra();
        });
        $("#catMatchLimpiar").on("click", function () {
            seleccion = {};
            $(".cat-match-check").prop("checked", false);
            $(".cat-match-item.selected").removeClass("selected");
            syncCheckAll();
            actualizarSelBarra();
        });
        $("#catMatchAgregar").on("click", agregarAlDestino);
    });
})(jQuery);
