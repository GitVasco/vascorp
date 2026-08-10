(function ($) {
    "use strict";

    var URL = "ajax/materiaprima/mp-reprocesos.ajax.php";
    var items = [];
    var procesos = [];
    var debounceTimers = { origen: null, destino: null, filtro: null };
    var mpCache = { origen: null, destino: null };
    var destinosMulti = [];
    var modoEdicion = false;

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

    function confirmar(mensaje, onYes) {
        if (typeof swal === "function") {
            swal({
                title: "Confirmar",
                text: mensaje,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                cancelButtonColor: "#aaa",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
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

    function chipColor(color) {
        if (!color) {
            return "";
        }
        return (
            '<span class="mpr-chip mpr-chip-color"><i class="fa fa-tint"></i> ' +
            escapar(color) +
            "</span>"
        );
    }

    function chipCod(cod) {
        if (!cod) {
            return "";
        }
        return '<span class="mpr-chip mpr-chip-cod">' + escapar(cod) + "</span>";
    }

    function htmlCard(mp) {
        if (!mp) {
            return '<div class="mpr-card-empty">Busca y selecciona la materia prima</div>';
        }
        return (
            '<div class="mpr-card-fab">' +
            escapar(mp.codfab || mp.codpro || "") +
            "</div>" +
            '<div class="mpr-card-des">' +
            escapar(mp.despro || "") +
            "</div>" +
            '<div class="mpr-card-meta">' +
            chipColor(mp.color) +
            chipCod(mp.codpro) +
            (mp.unidad ? '<span class="mpr-chip">' + escapar(mp.unidad) + "</span>" : "") +
            "</div>"
        );
    }

    function actualizarUiModo() {
        if (modoEdicion) {
            $("#mprDestinosMulti").hide();
            $("#mprCardDestino").show();
            $("#mprDestinoHint").text("un solo destino");
            $("#mprBtnGuardarTexto").text("Guardar");
            $("#mprBuscarDestino").attr("placeholder", "Código resultante");
        } else {
            $("#mprCardDestino").hide();
            $("#mprDestinosMulti").show();
            $("#mprDestinoHint").text("puedes agregar varios");
            $("#mprBuscarDestino").attr("placeholder", "Buscar y agregar MP resultante");
            pintarDestinosMulti();
        }
        refrescarSlotDestinoOk();
    }

    function refrescarSlotDestinoOk() {
        var ok = modoEdicion
            ? !!$("#mprCodProDestino").val()
            : destinosMulti.length > 0;
        $("#mprSlotDestino").toggleClass("is-ok", ok);
    }

    function pintarDestinosMulti() {
        var $lista = $("#mprDestinosLista");
        var $empty = $("#mprDestinosEmpty");
        if (!destinosMulti.length) {
            $lista.empty();
            $empty.show();
            $("#mprBtnGuardarTexto").text("Guardar");
            refrescarSlotDestinoOk();
            return;
        }
        $empty.hide();
        var html = "";
        destinosMulti.forEach(function (mp) {
            html +=
                '<li class="mpr-destino-item">' +
                '<div class="mpr-destino-info">' +
                '<span class="mpr-row-fab">' +
                escapar(mp.codfab || mp.codpro) +
                "</span>" +
                '<span class="mpr-row-des">' +
                escapar(mp.despro || "") +
                "</span>" +
                chipColor(mp.color) +
                chipCod(mp.codpro) +
                "</div>" +
                '<button type="button" class="btn btn-default btn-xs mpr-quitar-destino" data-codpro="' +
                escapar(mp.codpro) +
                '" title="Quitar"><i class="fa fa-times"></i></button>' +
                "</li>";
        });
        $lista.html(html);
        var n = destinosMulti.length;
        $("#mprBtnGuardarTexto").text(
            n === 1 ? "Guardar 1 relación" : "Guardar " + n + " relaciones"
        );
        refrescarSlotDestinoOk();
    }

    function agregarDestino(mp) {
        if (!mp || !mp.codpro) {
            return;
        }
        var origen = String($("#mprCodProOrigen").val() || "");
        if (origen && String(mp.codpro) === origen) {
            alerta("warning", "El destino no puede ser igual al origen");
            return;
        }
        for (var i = 0; i < destinosMulti.length; i++) {
            if (String(destinosMulti[i].codpro) === String(mp.codpro)) {
                alerta("info", "Esa MP ya está en la lista");
                return;
            }
        }
        destinosMulti.push({
            codpro: mp.codpro,
            codfab: mp.codfab || "",
            despro: mp.despro || "",
            color: mp.color || "",
            unidad: mp.unidad || ""
        });
        pintarDestinosMulti();
        $("#mprBuscarDestino").val("").focus();
        $("#mprOpcionesDestino").hide().empty();
    }

    function quitarDestino(codpro) {
        destinosMulti = destinosMulti.filter(function (mp) {
            return String(mp.codpro) !== String(codpro);
        });
        pintarDestinosMulti();
    }

    function setMpOrigen(mp) {
        var $ops = $("#mprOpcionesOrigen");
        mpCache.origen = mp || null;
        $ops.hide().empty();

        if (!mp) {
            $("#mprCodProOrigen").val("");
            $("#mprCardOrigen").html(
                '<div class="mpr-card-empty">Busca y selecciona la materia prima base</div>'
            );
            $("#mprSlotOrigen").removeClass("is-ok");
            return;
        }

        $("#mprCodProOrigen").val(mp.codpro);
        $("#mprBuscarOrigen").val(mp.codfab || mp.codpro || "");
        $("#mprCardOrigen").html(htmlCard(mp));
        $("#mprSlotOrigen").addClass("is-ok");

        // Si algún destino coincide con el nuevo origen, sacarlo
        if (!modoEdicion) {
            var antes = destinosMulti.length;
            destinosMulti = destinosMulti.filter(function (d) {
                return String(d.codpro) !== String(mp.codpro);
            });
            if (destinosMulti.length !== antes) {
                pintarDestinosMulti();
            }
        }
    }

    function setMpDestinoEdicion(mp) {
        var $ops = $("#mprOpcionesDestino");
        mpCache.destino = mp || null;
        $ops.hide().empty();

        if (!mp) {
            $("#mprCodProDestino").val("");
            $("#mprCardDestino").html(
                '<div class="mpr-card-empty">Busca y selecciona la MP resultante</div>'
            );
            refrescarSlotDestinoOk();
            return;
        }

        $("#mprCodProDestino").val(mp.codpro);
        $("#mprBuscarDestino").val(mp.codfab || mp.codpro || "");
        $("#mprCardDestino").html(htmlCard(mp));
        refrescarSlotDestinoOk();
    }

    function mostrarOpciones(lado, opciones) {
        var $ops = lado === "origen" ? $("#mprOpcionesOrigen") : $("#mprOpcionesDestino");
        $ops.empty();
        if (!opciones || !opciones.length) {
            $ops.hide();
            return;
        }

        var html = "";
        opciones.forEach(function (mp) {
            html +=
                '<button type="button" class="mpr-opcion mpr-opcion-mp" data-lado="' +
                escapar(lado) +
                '" data-codpro="' +
                escapar(mp.codpro) +
                '" data-codfab="' +
                escapar(mp.codfab) +
                '" data-despro="' +
                escapar(mp.despro) +
                '" data-color="' +
                escapar(mp.color) +
                '" data-unidad="' +
                escapar(mp.unidad || "") +
                '">' +
                '<div class="mpr-opcion-fab">' +
                escapar(mp.codfab || mp.codpro) +
                "</div>" +
                '<div class="mpr-opcion-des">' +
                escapar(mp.despro || "") +
                (mp.color ? " · " + escapar(mp.color) : "") +
                "</div></button>";
        });
        $ops.html(html).show();
    }

    function buscarMp(lado, silencioso) {
        var termino =
            lado === "origen"
                ? String($("#mprBuscarOrigen").val() || "").trim()
                : String($("#mprBuscarDestino").val() || "").trim();

        if (!termino) {
            if (!silencioso) {
                alerta("warning", "Ingrese un término de búsqueda");
            }
            return;
        }

        post("buscarMp", { termino: termino }).done(function (resp) {
            if (!resp || !resp.ok) {
                if (lado === "origen") {
                    setMpOrigen(null);
                } else if (modoEdicion) {
                    setMpDestinoEdicion(null);
                }
                if (!silencioso) {
                    alerta("error", (resp && resp.mensaje) || "Sin resultados");
                }
                return;
            }
            if (resp.mp) {
                if (lado === "origen") {
                    setMpOrigen(resp.mp);
                } else if (modoEdicion) {
                    setMpDestinoEdicion(resp.mp);
                } else {
                    agregarDestino(resp.mp);
                }
                return;
            }
            if (lado === "origen") {
                setMpOrigen(null);
            } else if (modoEdicion) {
                setMpDestinoEdicion(null);
            }
            mostrarOpciones(lado, resp.opciones || []);
        });
    }

    function abrirModalNuevo() {
        limpiarForm();
        modoEdicion = false;
        actualizarUiModo();
        $("#mprTituloForm").text("Nueva relación");
        $("#modalMpReproceso").modal("show");
        setTimeout(function () {
            $("#mprBuscarOrigen").focus();
        }, 350);
    }

    function limpiarForm() {
        $("#mprId").val("");
        $("#mprBuscarOrigen").val("");
        $("#mprBuscarDestino").val("");
        $("#mprProceso").val("");
        refreshSelectPicker($("#mprProceso"));
        $("#mprObservacion").val("");
        destinosMulti = [];
        setMpOrigen(null);
        setMpDestinoEdicion(null);
        pintarDestinosMulti();
    }

    function refreshSelectPicker($el) {
        if (!$el || !$el.length || !$.fn.selectpicker) {
            return;
        }
        if ($el.parent().hasClass("bootstrap-select")) {
            $el.selectpicker("refresh");
        } else {
            $el.selectpicker();
        }
    }

    function cargarProcesos() {
        return post("procesos").done(function (resp) {
            procesos = resp && resp.ok && resp.procesos ? resp.procesos : [];
            var $sel = $("#mprProceso");
            var $filtro = $("#mprFiltroProceso");
            $sel.find("option:not(:first)").remove();
            $filtro.find("option:not(:first)").remove();
            procesos.forEach(function (p) {
                $sel.append($("<option></option>").val(p.codigo).text(p.etiqueta));
                $filtro.append($("<option></option>").val(p.codigo).text(p.etiqueta));
            });
            refreshSelectPicker($sel);
            refreshSelectPicker($filtro);
        });
    }

    function agruparPorOrigen(lista) {
        var map = {};
        var orden = [];

        lista.forEach(function (it) {
            var key = String(it.cod_pro_origen || "");
            if (!map[key]) {
                map[key] = {
                    cod_pro_origen: it.cod_pro_origen,
                    cod_fab_origen: it.cod_fab_origen,
                    des_origen: it.des_origen,
                    color_origen: it.color_origen,
                    items: []
                };
                orden.push(key);
            }
            map[key].items.push(it);
        });

        orden.sort(function (a, b) {
            var fa = map[a].cod_fab_origen || a;
            var fb = map[b].cod_fab_origen || b;
            return String(fa).localeCompare(String(fb), "es", { sensitivity: "base" });
        });

        return orden.map(function (k) {
            map[k].items.sort(function (a, b) {
                var pa = a.proceso_etiqueta || a.proceso || "";
                var pb = b.proceso_etiqueta || b.proceso || "";
                var cmp = String(pa).localeCompare(String(pb), "es", { sensitivity: "base" });
                if (cmp !== 0) {
                    return cmp;
                }
                return String(a.cod_fab_destino || "").localeCompare(
                    String(b.cod_fab_destino || ""),
                    "es",
                    { sensitivity: "base" }
                );
            });
            return map[k];
        });
    }

    function htmlGrupo(grupo) {
        var n = grupo.items.length;
        var rows = "";

        grupo.items.forEach(function (it) {
            var obs = String(it.observacion || "").trim();
            rows +=
                '<li class="mpr-row">' +
                '<div class="mpr-row-proceso">' +
                '<span class="mpr-proceso-badge">' +
                escapar(it.proceso_etiqueta || it.proceso || "") +
                "</span></div>" +
                '<div class="mpr-row-destino">' +
                '<span class="mpr-row-fab">' +
                escapar(it.cod_fab_destino || "—") +
                "</span>" +
                '<span class="mpr-row-des">' +
                escapar(it.des_destino || "") +
                "</span>" +
                chipColor(it.color_destino) +
                chipCod(it.cod_pro_destino) +
                "</div>" +
                '<div class="mpr-row-side">' +
                (obs
                    ? '<span class="mpr-row-obs" title="' +
                      escapar(obs) +
                      '"><i class="fa fa-comment-o"></i> ' +
                      escapar(obs) +
                      "</span>"
                    : "") +
                '<div class="btn-group">' +
                '<button type="button" class="btn btn-warning btn-xs mpr-editar" data-id="' +
                escapar(it.id) +
                '" title="Editar"><i class="fa fa-pencil"></i></button> ' +
                '<button type="button" class="btn btn-danger btn-xs mpr-eliminar" data-id="' +
                escapar(it.id) +
                '" title="Eliminar"><i class="fa fa-trash"></i></button>' +
                "</div></div></li>";
        });

        return (
            '<article class="mpr-grupo">' +
            '<header class="mpr-grupo-head">' +
            '<div class="mpr-grupo-origen">' +
            '<span class="mpr-grupo-fab">' +
            escapar(grupo.cod_fab_origen || "—") +
            "</span>" +
            '<span class="mpr-grupo-des">' +
            escapar(grupo.des_origen || "") +
            "</span>" +
            '<span class="mpr-grupo-meta">' +
            chipColor(grupo.color_origen) +
            chipCod(grupo.cod_pro_origen) +
            "</span></div>" +
            '<span class="mpr-grupo-count">' +
            n +
            " reproceso" +
            (n === 1 ? "" : "s") +
            "</span></header>" +
            '<ul class="mpr-grupo-rows">' +
            rows +
            "</ul></article>"
        );
    }

    function aplicarFiltros() {
        var texto = String($("#mprFiltroTexto").val() || "")
            .trim()
            .toLowerCase();
        var proceso = String($("#mprFiltroProceso").val() || "").trim();

        var filtrados = items.filter(function (it) {
            if (proceso && String(it.proceso) !== proceso) {
                return false;
            }
            if (!texto) {
                return true;
            }
            var blob = [
                it.cod_fab_origen,
                it.des_origen,
                it.color_origen,
                it.cod_pro_origen,
                it.proceso_etiqueta,
                it.proceso,
                it.cod_fab_destino,
                it.des_destino,
                it.color_destino,
                it.cod_pro_destino,
                it.observacion
            ]
                .join(" ")
                .toLowerCase();
            return blob.indexOf(texto) !== -1;
        });

        pintarLista(filtrados);
    }

    function pintarLista(lista) {
        lista = lista || items;
        $("#mprCount").text(items.length);

        if (!items.length) {
            $("#mprEmpty").show();
            $("#mprNoMatch").hide();
            $("#mprLista").hide().empty();
            return;
        }

        $("#mprEmpty").hide();

        if (!lista.length) {
            $("#mprNoMatch").show();
            $("#mprLista").hide().empty();
            return;
        }

        $("#mprNoMatch").hide();
        var grupos = agruparPorOrigen(lista);
        $("#mprLista").html(grupos.map(htmlGrupo).join("")).show();
    }

    function cargarLista() {
        return post("listar").done(function (resp) {
            items = resp && resp.ok && resp.items ? resp.items : [];
            aplicarFiltros();
        });
    }

    function guardar() {
        var $btn = $("#mprBtnGuardar");
        var codOrigen = String($("#mprCodProOrigen").val() || "").trim();
        var proceso = String($("#mprProceso").val() || "").trim();
        var observacion = String($("#mprObservacion").val() || "").trim();
        var id = String($("#mprId").val() || "").trim();

        if (!codOrigen || !proceso) {
            alerta("warning", "Complete origen y proceso");
            return;
        }

        $btn.prop("disabled", true);

        if (modoEdicion || id) {
            var codDestino = String($("#mprCodProDestino").val() || "").trim();
            if (!codDestino) {
                $btn.prop("disabled", false);
                alerta("warning", "Seleccione el MP destino");
                return;
            }
            post("guardar", {
                id: id,
                cod_pro_origen: codOrigen,
                cod_pro_destino: codDestino,
                proceso: proceso,
                observacion: observacion
            })
                .done(function (resp) {
                    if (!resp || !resp.ok) {
                        alerta("error", (resp && resp.mensaje) || "No se pudo guardar");
                        return;
                    }
                    $("#modalMpReproceso").modal("hide");
                    limpiarForm();
                    cargarLista();
                    alerta("success", resp.mensaje || "Guardado");
                })
                .always(function () {
                    $btn.prop("disabled", false);
                });
            return;
        }

        if (!destinosMulti.length) {
            $btn.prop("disabled", false);
            alerta("warning", "Agregue al menos un MP destino");
            return;
        }

        post("guardarLote", {
            cod_pro_origen: codOrigen,
            proceso: proceso,
            observacion: observacion,
            destinos: JSON.stringify(
                destinosMulti.map(function (d) {
                    return { codpro: d.codpro };
                })
            )
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    alerta("error", (resp && resp.mensaje) || "No se pudo guardar");
                    return;
                }
                $("#modalMpReproceso").modal("hide");
                limpiarForm();
                cargarLista();
                alerta("success", resp.mensaje || "Guardado");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    }

    function editar(id) {
        var it = null;
        for (var i = 0; i < items.length; i++) {
            if (String(items[i].id) === String(id)) {
                it = items[i];
                break;
            }
        }
        if (!it) {
            alerta("error", "Registro no encontrado");
            return;
        }

        modoEdicion = true;
        actualizarUiModo();
        $("#mprId").val(it.id);
        $("#mprTituloForm").text("Editar relación");
        $("#mprProceso").val(it.proceso || "");
        refreshSelectPicker($("#mprProceso"));
        $("#mprObservacion").val(it.observacion || "");
        setMpOrigen({
            codpro: it.cod_pro_origen,
            codfab: it.cod_fab_origen,
            despro: it.des_origen,
            color: it.color_origen || ""
        });
        setMpDestinoEdicion({
            codpro: it.cod_pro_destino,
            codfab: it.cod_fab_destino,
            despro: it.des_destino,
            color: it.color_destino || ""
        });
        $("#modalMpReproceso").modal("show");
    }

    function eliminar(id) {
        confirmar("¿Eliminar esta relación del catálogo?", function () {
            post("eliminar", { id: id }).done(function (resp) {
                if (!resp || !resp.ok) {
                    alerta("error", (resp && resp.mensaje) || "No se pudo eliminar");
                    return;
                }
                if (String($("#mprId").val()) === String(id)) {
                    limpiarForm();
                    $("#modalMpReproceso").modal("hide");
                }
                cargarLista();
            });
        });
    }

    function debounceBuscar(lado) {
        clearTimeout(debounceTimers[lado]);
        debounceTimers[lado] = setTimeout(function () {
            var termino =
                lado === "origen"
                    ? String($("#mprBuscarOrigen").val() || "").trim()
                    : String($("#mprBuscarDestino").val() || "").trim();
            if (termino.length < 2) {
                return;
            }
            buscarMp(lado, true);
        }, 450);
    }

    $(document).on("click", "#mprBtnNuevo, #mprBtnNuevoEmpty", abrirModalNuevo);
    $(document).on("click", "#mprBtnBuscarOrigen", function () {
        buscarMp("origen");
    });
    $(document).on("click", "#mprBtnBuscarDestino", function () {
        buscarMp("destino");
    });

    $(document).on("keydown", "#mprBuscarOrigen", function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            buscarMp("origen");
        }
    });
    $(document).on("keydown", "#mprBuscarDestino", function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            buscarMp("destino");
        }
    });

    $(document).on("input", "#mprBuscarOrigen", function () {
        debounceBuscar("origen");
    });
    $(document).on("input", "#mprBuscarDestino", function () {
        debounceBuscar("destino");
    });

    $(document).on("input", "#mprFiltroTexto", function () {
        clearTimeout(debounceTimers.filtro);
        debounceTimers.filtro = setTimeout(aplicarFiltros, 200);
    });
    $(document).on("changed.bs.select", "#mprFiltroProceso", aplicarFiltros);
    $(document).on("change", "#mprFiltroProceso", aplicarFiltros);

    $(document).on("click", ".mpr-opcion-mp", function (e) {
        e.preventDefault();
        var $el = $(this);
        var mp = {
            codpro: $el.data("codpro"),
            codfab: $el.data("codfab"),
            despro: $el.data("despro"),
            color: $el.data("color"),
            unidad: $el.data("unidad")
        };
        if ($el.data("lado") === "origen") {
            setMpOrigen(mp);
            $("#mprOpcionesOrigen").hide().empty();
        } else if (modoEdicion) {
            setMpDestinoEdicion(mp);
            $("#mprOpcionesDestino").hide().empty();
        } else {
            agregarDestino(mp);
        }
    });

    $(document).on("click", ".mpr-quitar-destino", function () {
        quitarDestino($(this).data("codpro"));
    });

    $(document).on("click", "#mprBtnGuardar", guardar);
    $(document).on("click", ".mpr-editar", function () {
        editar($(this).data("id"));
    });
    $(document).on("click", ".mpr-eliminar", function () {
        eliminar($(this).data("id"));
    });

    $(document).on("shown.bs.modal", "#modalMpReproceso", function () {
        if (!$("#mprCodProOrigen").val()) {
            $("#mprBuscarOrigen").focus();
        }
    });

    $(function () {
        if (!$("#mprLista").length) {
            return;
        }
        actualizarUiModo();
        $.when(cargarProcesos(), cargarLista());
    });
})(jQuery);
