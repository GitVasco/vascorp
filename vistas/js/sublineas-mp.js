(function ($) {
    "use strict";

    var URL = "ajax/materiaprima/sublineas-mp.ajax.php";
    var grupos = [];
    var lineas = [];
    var debounceFiltro = null;
    var previewActual = null;
    var sublineaSel = null;
    var urlLock = false;
    var catalogos = { colores: [], tallas: [], unidades: [] };
    var catalogosListos = false;
    var descMpManual = false;
    var fabTimer = null;
    var fabExiste = false;

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

    function formatoStock(n) {
        var num = Number(n);
        if (!isFinite(num)) {
            num = 0;
        }
        return num.toLocaleString("en-US", {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4
        });
    }

    function escapar(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function lineaActual() {
        return String($("#smpSelLinea").val() || "").trim();
    }

    function textoFiltro() {
        return String($("#smpFiltroTexto").val() || "")
            .trim()
            .toUpperCase();
    }

    function refreshSelect($el) {
        if ($el.hasClass("selectpicker") && $.fn.selectpicker) {
            $el.selectpicker("refresh");
        }
    }

    function itemsDeLinea(linea) {
        var found = [];
        var needle = String(linea || "").toUpperCase();
        grupos.forEach(function (g) {
            if (String(g.linea || "").toUpperCase() !== needle) {
                return;
            }
            found = g.items || [];
        });
        return found;
    }

    function buscarItem(arg) {
        var found = null;
        grupos.forEach(function (g) {
            (g.items || []).forEach(function (item) {
                if (String(item.cod_argumento) === String(arg)) {
                    found = item;
                }
            });
        });
        return found;
    }

    function buscarPorCodigo(codigo) {
        var found = null;
        var needle = String(codigo || "").toUpperCase();
        grupos.forEach(function (g) {
            (g.items || []).forEach(function (item) {
                if (String(item.codigo_sublinea || "").toUpperCase() === needle) {
                    found = item;
                }
            });
        });
        return found;
    }

    function itemCoincide(item, q) {
        if (!q) {
            return true;
        }
        var hay = [item.codigo_sublinea, item.nombre, item.subcodigo, item.cod_argumento]
            .join(" ")
            .toUpperCase();
        return hay.indexOf(q) !== -1;
    }

    function leerUrl() {
        try {
            var params = new URLSearchParams(window.location.search || "");
            return {
                linea: String(params.get("linea") || "").trim().toUpperCase(),
                sub: String(params.get("sub") || "").trim().toUpperCase()
            };
        } catch (e) {
            return { linea: "", sub: "" };
        }
    }

    function actualizarUrl() {
        if (urlLock || !window.history || !window.history.replaceState) {
            return;
        }
        var params;
        try {
            params = new URLSearchParams(window.location.search || "");
        } catch (e) {
            return;
        }
        var linea = lineaActual();
        var sub = sublineaSel && sublineaSel.codigo_sublinea
            ? String(sublineaSel.codigo_sublinea).trim().toUpperCase()
            : "";
        if (linea) {
            params.set("linea", linea);
        } else {
            params.delete("linea");
        }
        if (sub) {
            params.set("sub", sub);
        } else {
            params.delete("sub");
        }
        var qs = params.toString();
        var url = window.location.pathname + (qs ? "?" + qs : "") + (window.location.hash || "");
        window.history.replaceState({}, "", url);
    }

    function aplicarSelectLinea(valor) {
        if ($.fn.selectpicker) {
            $("#smpSelLinea").selectpicker("val", valor || "");
            $("#smpSelLinea").selectpicker("refresh");
        } else {
            $("#smpSelLinea").val(valor || "");
        }
    }

    function restaurarDesdeUrl() {
        var st = leerUrl();
        var lineaOk = false;
        lineas.forEach(function (lin) {
            if (String(lin.codigo || "").toUpperCase() === st.linea) {
                lineaOk = true;
            }
        });
        if (!st.linea || !lineaOk) {
            pintarLista();
            return;
        }
        urlLock = true;
        aplicarSelectLinea(st.linea);
        cargarPreview(st.linea).always(function () {
            if (st.sub) {
                var item = buscarPorCodigo(st.sub);
                if (item) {
                    cargarMps(item);
                }
            }
            urlLock = false;
            actualizarUrl();
        });
    }

    function pintarLineas() {
        var html = '<option value="">Seleccionar línea…</option>';
        lineas.forEach(function (lin) {
            html +=
                '<option value="' +
                escapar(lin.codigo) +
                '">' +
                escapar(lin.codigo) +
                " — " +
                escapar(lin.nombre || lin.argumento || "") +
                "</option>";
        });
        $("#smpSelLinea").html(html);
        refreshSelect($("#smpSelLinea"));
    }

    function pintarStats(items, preview) {
        var total = items.length;
        var conMp = 0;
        var mp = 0;
        items.forEach(function (item) {
            var n = Number(item.mp_activas || 0);
            mp += n;
            if (n > 0) {
                conMp += 1;
            }
        });
        $("#smpStatTotal").text(total);
        $("#smpStatConMp").text(conMp);
        $("#smpStatSinUso").text(total - conMp);
        $("#smpStatMp").text(mp);
        $("#smpStatProximo").text((preview && preview.codigo_sublinea) || "—");
    }

    function pintarLista() {
        var linea = lineaActual();
        if (!linea) {
            $("#smpPlaceholder").show();
            $("#smpContenido").hide();
            $("#smpBtnNuevo").prop("disabled", true);
            $("#smpFiltroWrap").hide();
            $(".smp-page").removeClass("is-ready");
            limpiarMp();
            return;
        }

        $("#smpPlaceholder").hide();
        $("#smpContenido").show();
        $("#smpBtnNuevo").prop("disabled", false);
        $(".smp-page").addClass("is-ready");

        var items = itemsDeLinea(linea);
        var q = textoFiltro();
        var filas = "";
        var visibles = 0;

        items.forEach(function (item) {
            if (!itemCoincide(item, q)) {
                return;
            }
            visibles += 1;
            var n = Number(item.mp_activas || 0);
            var chip =
                n > 0
                    ? '<span class="smp-chip smp-chip-mp">' + n + "</span>"
                    : '<span class="smp-chip">0</span>';
            var sel =
                sublineaSel && sublineaSel.cod_argumento === item.cod_argumento
                    ? " smp-row-activa"
                    : "";
            filas +=
                '<tr class="smp-row-sub' +
                sel +
                '" data-arg="' +
                escapar(item.cod_argumento) +
                '">' +
                '<td class="smp-cod">' +
                escapar(item.codigo_sublinea) +
                "</td>" +
                '<td class="smp-nom" title="' +
                escapar(item.nombre || "") +
                '">' +
                escapar(item.nombre || "") +
                "</td>" +
                '<td class="text-center">' +
                chip +
                "</td>" +
                '<td class="smp-col-acc">' +
                '<button type="button" class="btn btn-warning btn-xs smp-btn-editar" data-arg="' +
                escapar(item.cod_argumento) +
                '" title="Editar"><i class="fa fa-pencil"></i></button>' +
                "</td>" +
                "</tr>";
        });

        $("#smpTablaBody").html(filas);
        $("#smpTablaBody").closest(".smp-tabla-wrap").toggle(items.length > 0 && visibles > 0);
        $("#smpEmpty").toggle(items.length === 0);
        $("#smpNoMatch").toggle(items.length > 0 && visibles === 0);
        $("#smpFiltroWrap").toggle(items.length > 0);

        pintarStats(items, previewActual);

        var $activa = $("#smpTablaBody tr.smp-row-activa");
        if ($activa.length && $activa[0].scrollIntoView) {
            $activa[0].scrollIntoView({ block: "nearest" });
        }
    }

    function limpiarMp() {
        sublineaSel = null;
        $("#smpMpPlaceholder").show();
        $("#smpMpContenido").hide();
        $("#smpMpBody").empty();
        $("#smpMpTitulo").text("Materias primas");
        $("#smpMpCount").hide().text("0");
        $("#smpTablaBody tr").removeClass("smp-row-activa");
        $("#smpBtnNuevaMp").prop("disabled", true);
    }

    function pintarMps(res, item) {
        var lista = (res && res.items) || [];
        var titulo = item
            ? item.codigo_sublinea + (item.nombre ? " — " + item.nombre : "")
            : "Materias primas";
        $("#smpMpTitulo").text(titulo);
        $("#smpMpCount").text(lista.length).show();
        $("#smpMpPlaceholder").hide();
        $("#smpMpContenido").show();

        var filas = "";
        lista.forEach(function (mp) {
            filas +=
                "<tr>" +
                '<td class="smp-cod">' +
                escapar(mp.codfab || mp.codpro) +
                "</td>" +
                '<td class="smp-nom" title="' +
                escapar(mp.despro || "") +
                '">' +
                escapar(mp.despro || "") +
                "</td>" +
                "<td>" +
                escapar(mp.color || "") +
                "</td>" +
                '<td class="smp-num">' +
                escapar(formatoStock(mp.stock)) +
                "</td>" +
                '<td class="smp-ocos-cell">' +
                '<button type="button" class="btn btn-link btn-xs smp-btn-ordenes" data-codpro="' +
                escapar(mp.codpro || "") +
                '" data-codfab="' +
                escapar(mp.codfab || "") +
                '" data-despro="' +
                escapar(mp.despro || "") +
                '" data-color="' +
                escapar(mp.color || "") +
                '" data-stock="' +
                escapar(formatoStock(mp.stock)) +
                '" title="Ver órdenes de compra y servicio">' +
                badgeOcos("oc", mp.n_oc) +
                badgeOcos("os", mp.n_os) +
                "</button>" +
                "</td>" +
                '<td class="smp-col-acc">' +
                '<button type="button" class="btn btn-default btn-xs smp-btn-duplicar" data-codpro="' +
                escapar(mp.codpro || "") +
                '" title="Duplicar"><i class="fa fa-files-o"></i></button>' +
                "</td>" +
                "</tr>";
        });
        $("#smpMpBody").html(filas);
        $("#smpMpBody").closest(".smp-tabla-wrap").toggle(lista.length > 0);
        $("#smpMpEmpty").toggle(lista.length === 0);
    }

    function cargarMps(item) {
        if (!item || !item.codigo_sublinea) {
            limpiarMp();
            return;
        }
        sublineaSel = item;
        $("#smpBtnNuevaMp").prop("disabled", false);
        $("#smpTablaBody tr").removeClass("smp-row-activa");
        $("#smpTablaBody tr[data-arg='" + item.cod_argumento + "']").addClass("smp-row-activa");
        actualizarUrl();
        $("#smpMpTitulo").text("Cargando…");
        post("mps", { codigo_sublinea: item.codigo_sublinea })
            .done(function (res) {
                if (!res || !res.ok) {
                    alerta("warning", (res && res.mensaje) || "No se pudieron cargar las MP");
                    return;
                }
                pintarMps(res, item);
            })
            .fail(function () {
                alerta("error", "Error de conexión");
            });
    }

    function cargarPreview(linea) {
        previewActual = null;
        if (!linea) {
            pintarLista();
            return $.Deferred().resolve().promise();
        }
        return post("preview", { linea: linea }).done(function (res) {
            previewActual = res && res.ok ? res : null;
            pintarLista();
        }).fail(function () {
            pintarLista();
        });
    }

    function resetForm() {
        $("#smpModo").val("crear");
        $("#smpCodArgumento").val("");
        $("#smpNombre").val("");
        $("#smpValor1").val("");
        $("#smpValor2").val("");
        $("#smpValor4").val("");
        $("#smpValor5").val("");
        $("#smpCodigoPreview").text("—");
        $("#smpSubcodigoPreview").text("—");
        $("#smpArgPreview").text("—");
        $("#smpCamposExtra").collapse("hide");
    }

    function abrirCrear() {
        var linea = lineaActual();
        if (!linea) {
            alerta("warning", "Selecciona una línea");
            return;
        }
        resetForm();
        $("#smpTituloForm").text("Nueva sublínea");
        $("#smpHelp").text("El código se arma con la línea más el correlativo (ej. " + linea + "001).");
        $("#smpBtnGuardar").prop("disabled", false);
        var prev = previewActual;
        if (prev) {
            $("#smpCodigoPreview").text(prev.codigo_sublinea || "—");
            $("#smpSubcodigoPreview").text(prev.subcodigo || "—");
            $("#smpArgPreview").text(prev.cod_argumento || "—");
        } else {
            post("preview", { linea: linea }).done(function (res) {
                if (res && res.ok) {
                    $("#smpCodigoPreview").text(res.codigo_sublinea || "—");
                    $("#smpSubcodigoPreview").text(res.subcodigo || "—");
                    $("#smpArgPreview").text(res.cod_argumento || "—");
                }
            });
        }
        $("#modalSublineaMp").modal("show");
        setTimeout(function () {
            $("#smpNombre").focus();
        }, 350);
    }

    function abrirEditar(item) {
        resetForm();
        $("#smpModo").val("editar");
        $("#smpTituloForm").text("Editar sublínea");
        $("#smpHelp").text("La línea y el correlativo no se cambian, para no romper el código de las materias primas.");
        $("#smpCodArgumento").val(item.cod_argumento);
        $("#smpNombre").val(item.nombre || "");
        $("#smpValor1").val(item.valor_1 || "");
        $("#smpValor2").val(item.valor_2 || "");
        $("#smpValor4").val(item.valor_4 || "");
        $("#smpValor5").val(item.valor_5 || "");
        $("#smpCodigoPreview").text(item.codigo_sublinea || "—");
        $("#smpSubcodigoPreview").text(item.subcodigo || "—");
        $("#smpArgPreview").text(item.cod_argumento || "—");
        $("#smpBtnGuardar").prop("disabled", false);
        $("#modalSublineaMp").modal("show");
        setTimeout(function () {
            $("#smpNombre").focus();
        }, 350);
    }

    function guardar() {
        var modo = $("#smpModo").val();
        var nombre = String($("#smpNombre").val() || "").trim();
        if (!nombre) {
            alerta("warning", "Ingresa el nombre de la sublínea");
            return;
        }

        var data = {
            nombre: nombre,
            valor_1: String($("#smpValor1").val() || "").trim(),
            valor_2: String($("#smpValor2").val() || "").trim(),
            valor_4: String($("#smpValor4").val() || "").trim(),
            valor_5: String($("#smpValor5").val() || "").trim()
        };

        var accion = "crear";
        var linea = lineaActual();
        if (modo === "editar") {
            accion = "editar";
            data.cod_argumento = $("#smpCodArgumento").val();
        } else {
            data.linea = linea;
            if (!data.linea) {
                alerta("warning", "Selecciona una línea");
                return;
            }
        }

        $("#smpBtnGuardar").prop("disabled", true);
        post(accion, data)
            .done(function (res) {
                if (!res || !res.ok) {
                    alerta("warning", (res && res.mensaje) || "No se pudo guardar");
                    return;
                }
                $("#modalSublineaMp").modal("hide");
                alerta("success", res.mensaje || "Guardado");
                cargarLista().done(function () {
                    cargarPreview(linea);
                });
            })
            .fail(function () {
                alerta("error", "Error de conexión");
            })
            .always(function () {
                $("#smpBtnGuardar").prop("disabled", false);
            });
    }

    function cargarLineas() {
        return post("lineas").done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            lineas = res.lineas || [];
            pintarLineas();
        });
    }

    function cargarLista() {
        return post("listar").done(function (res) {
            if (!res || !res.ok) {
                alerta("warning", (res && res.mensaje) || "No se pudo cargar");
                return;
            }
            grupos = res.grupos || [];
        });
    }

    function badgeOcos(tipo, n) {
        n = Number(n) || 0;
        var cls =
            "smp-badge-ocos " +
            (n > 0 ? (tipo === "oc" ? "smp-badge-oc" : "smp-badge-os") : "smp-badge-off");
        return '<span class="' + cls + '">' + tipo.toUpperCase() + " " + n + "</span>";
    }

    function filaVacia(cols, texto) {
        return (
            '<tr><td colspan="' +
            cols +
            '" class="text-center text-muted">' +
            escapar(texto) +
            "</td></tr>"
        );
    }

    function labelEstado(est) {
        est = String(est || "").toUpperCase();
        if (est === "ABI") {
            return "<span class='label label-success'>ABI</span>";
        }
        if (est === "PAR") {
            return "<span class='label label-warning'>PAR</span>";
        }
        return est ? "<span class='label label-default'>" + escapar(est) + "</span>" : "";
    }

    function setSelectVal($el, val) {
        val = val == null ? "" : String(val);
        if ($.fn.selectpicker && $el.hasClass("selectpicker")) {
            $el.selectpicker("val", val);
            $el.selectpicker("refresh");
        } else {
            $el.val(val);
        }
    }

    function numCampo(v) {
        if (v == null || v === "") {
            return "0";
        }
        return String(v);
    }

    function fillSelect($el, items, placeholder) {
        var html = '<option value="">' + escapar(placeholder || "Seleccionar…") + "</option>";
        (items || []).forEach(function (it) {
            var extra = it.corta ? " — " + it.corta : "";
            html +=
                '<option value="' +
                escapar(it.codigo) +
                '">' +
                escapar(it.codigo) +
                " — " +
                escapar(it.nombre || "") +
                extra +
                "</option>";
        });
        $el.html(html);
        refreshSelect($el);
    }

    function cargarCatalogos() {
        if (catalogosListos) {
            return $.Deferred().resolve().promise();
        }
        return post("catalogos").done(function (res) {
            if (!res || !res.ok) {
                alerta("warning", (res && res.mensaje) || "No se pudieron cargar los catálogos");
                return;
            }
            catalogos.colores = res.colores || [];
            catalogos.tallas = res.tallas || [];
            catalogos.unidades = res.unidades || [];
            fillSelect($("#smpMpColor"), catalogos.colores, "Seleccionar color…");
            fillSelect($("#smpMpTalla"), catalogos.tallas, "Seleccionar talla…");
            fillSelect($("#smpMpUnidad"), catalogos.unidades, "Seleccionar unidad…");
            catalogosListos = true;
        });
    }

    function nombreCatalogo(lista, codigo) {
        var found = "";
        (lista || []).forEach(function (it) {
            if (String(it.codigo) === String(codigo)) {
                found = it.nombre || "";
            }
        });
        return found;
    }

    function armarCodFab() {
        if (!sublineaSel) {
            return "";
        }
        var color = String($("#smpMpColor").val() || "");
        var talla = String($("#smpMpTalla").val() || "");
        return String(sublineaSel.linea || "") + String(sublineaSel.subcodigo || "") + color + talla;
    }

    function pintarCodFab() {
        var cod = armarCodFab();
        $("#smpMpCodFab").text(cod || "—");
        return cod;
    }

    function validarCodFab() {
        var cod = pintarCodFab();
        var color = String($("#smpMpColor").val() || "");
        var talla = String($("#smpMpTalla").val() || "");
        fabExiste = false;
        $("#smpMpFabError").hide();
        $("#smpBtnGuardarMp").prop("disabled", false);
        if (!color || !talla || !cod) {
            return;
        }
        post("validarCodFab", { codfab: cod }).done(function (res) {
            if (res && res.ok && res.existe) {
                fabExiste = true;
                $("#smpMpFabError").show();
                $("#smpBtnGuardarMp").prop("disabled", true);
            }
        });
    }

    function sugerirDescripcionMp() {
        if (descMpManual || !sublineaSel) {
            return;
        }
        var nom = String(sublineaSel.nombre || "").trim();
        var colorNom = nombreCatalogo(catalogos.colores, $("#smpMpColor").val());
        $("#smpMpNombre").val(colorNom ? nom + " " + colorNom : nom);
    }

    function prepararFormMp() {
        $("#smpMpLineaTxt").text(sublineaSel.linea || "—");
        $("#smpMpSubTxt").text(
            (sublineaSel.codigo_sublinea || "") +
                (sublineaSel.nombre ? " — " + sublineaSel.nombre : "")
        );
        $("#smpMpFabError").hide();
        fabExiste = false;
        $("#smpBtnGuardarMp").prop("disabled", false);
    }

    function abrirCrearMp() {
        if (!sublineaSel) {
            alerta("warning", "Elige una sublínea");
            return;
        }
        descMpManual = false;
        $("#smpMpOrigenCodpro").val("");
        $("#smpMpTituloForm").text("Nueva materia prima");
        $("#smpMpFabHint").text("El código se arma con línea + sublínea + color + talla.");
        $("#smpBtnGuardarMp").html('<i class="fa fa-save"></i> Guardar MP');
        $("#smpMpCodAlt").val("");
        $("#smpMpPeso").val("0");
        $("#smpMpAdval").val("0");
        $("#smpMpSeguro").val("0");
        $("#smpMpStkMin").val("0");
        $("#smpMpStkMax").val("0");
        $("#smpMpExtra").collapse("hide");
        prepararFormMp();
        cargarCatalogos().always(function () {
            setSelectVal($("#smpMpColor"), "");
            setSelectVal($("#smpMpTalla"), "");
            setSelectVal($("#smpMpUnidad"), "");
            sugerirDescripcionMp();
            pintarCodFab();
            $("#modalMpNueva").modal("show");
        });
    }

    function abrirDuplicarMp(codpro) {
        if (!sublineaSel) {
            alerta("warning", "Elige una sublínea");
            return;
        }
        if (!codpro) {
            return;
        }
        post("detalleMp", { codpro: codpro })
            .done(function (res) {
                if (!res || !res.ok || !res.mp) {
                    alerta("warning", (res && res.mensaje) || "No se pudo cargar la materia prima");
                    return;
                }
                var mp = res.mp;
                descMpManual = true;
                $("#smpMpOrigenCodpro").val(mp.codpro || "");
                $("#smpMpTituloForm").text("Duplicar materia prima");
                $("#smpMpFabHint").text(
                    "Cambia color o talla para generar un código nuevo. Se copian unidad, peso, stocks y precios."
                );
                $("#smpBtnGuardarMp").html('<i class="fa fa-files-o"></i> Duplicar MP');
                $("#smpMpCodAlt").val(mp.codalt || "");
                $("#smpMpNombre").val(mp.despro || "");
                $("#smpMpPeso").val(numCampo(mp.peso));
                $("#smpMpAdval").val(numCampo(mp.adval));
                $("#smpMpSeguro").val(numCampo(mp.seguro));
                $("#smpMpStkMin").val(numCampo(mp.stk_min));
                $("#smpMpStkMax").val(numCampo(mp.stk_max));
                var hayExtra =
                    Number(mp.peso) ||
                    Number(mp.adval) ||
                    Number(mp.seguro) ||
                    Number(mp.stk_min) ||
                    Number(mp.stk_max);
                $("#smpMpExtra").collapse(hayExtra ? "show" : "hide");
                prepararFormMp();
                cargarCatalogos().always(function () {
                    setSelectVal($("#smpMpColor"), mp.color || "");
                    setSelectVal($("#smpMpTalla"), mp.talla || "");
                    setSelectVal($("#smpMpUnidad"), mp.unidad || "");
                    pintarCodFab();
                    validarCodFab();
                    $("#modalMpNueva").modal("show");
                });
            })
            .fail(function () {
                alerta("error", "Error de conexión");
            });
    }

    function abrirOrdenes($btn) {
        var codpro = String($btn.attr("data-codpro") || "");
        $("#smpOcCodFab").text($btn.attr("data-codfab") || "—");
        $("#smpOcDespro").text($btn.attr("data-despro") || "—");
        $("#smpOcColor").text($btn.attr("data-color") || "—");
        $("#smpOcStock").text($btn.attr("data-stock") || "—");
        $("#smpOcCount").text("0");
        $("#smpOsCount").text("0");
        $("#smpOcBody").html(filaVacia(8, "Cargando…"));
        $("#smpOsBody").html(filaVacia(9, "Cargando…"));
        $("#modalMpOrdenes").modal("show");
        if (!codpro) {
            $("#smpOcBody").html(filaVacia(8, "Falta la materia prima"));
            $("#smpOsBody").html(filaVacia(9, "Falta la materia prima"));
            return;
        }
        post("ordenes", { codpro: codpro })
            .done(function (res) {
                if (!res || !res.ok) {
                    $("#smpOcBody").html(filaVacia(8, (res && res.mensaje) || "No se pudieron cargar"));
                    $("#smpOsBody").html(filaVacia(9, (res && res.mensaje) || "No se pudieron cargar"));
                    return;
                }
                if (res.mp) {
                    if (res.mp.codfab) {
                        $("#smpOcCodFab").text(res.mp.codfab);
                    }
                    if (res.mp.despro) {
                        $("#smpOcDespro").text(res.mp.despro);
                    }
                    if (res.mp.color) {
                        $("#smpOcColor").text(res.mp.color);
                    }
                    if (res.mp.stock !== "" && res.mp.stock != null) {
                        $("#smpOcStock").text(formatoStock(res.mp.stock));
                    }
                }
                pintarTablaOc(res.oc || []);
                pintarTablaOs(res.os || []);
            })
            .fail(function () {
                $("#smpOcBody").html(filaVacia(8, "Error de conexión"));
                $("#smpOsBody").html(filaVacia(9, "Error de conexión"));
            });
    }

    function pintarTablaOc(lista) {
        $("#smpOcCount").text(lista.length);
        if (!lista.length) {
            $("#smpOcBody").html(filaVacia(8, "Sin órdenes de compra pendientes"));
            return;
        }
        var html = "";
        lista.forEach(function (row) {
            var nro = String(row.nro || "");
            var nroHtml = nro
                ? '<a href="index.php?ruta=editar-orden-compra&idOrdenCompra=' +
                  encodeURIComponent(nro) +
                  '" target="_blank">' +
                  escapar(nro) +
                  "</a>"
                : "";
            html +=
                "<tr>" +
                "<td>" +
                nroHtml +
                "</td>" +
                "<td>" +
                escapar(row.fecemi || "") +
                "</td>" +
                "<td>" +
                escapar(row.fecllegada || "") +
                "</td>" +
                "<td>" +
                escapar(row.proveedor || "") +
                "</td>" +
                '<td class="smp-num">' +
                escapar(row.cantidad || "") +
                "</td>" +
                '<td class="smp-num">' +
                escapar(row.saldo || "") +
                "</td>" +
                "<td>" +
                labelEstado(row.estado) +
                "</td>" +
                '<td class="smp-num">' +
                escapar(row.precio || "") +
                "</td>" +
                "</tr>";
        });
        $("#smpOcBody").html(html);
    }

    function pintarTablaOs(lista) {
        $("#smpOsCount").text(lista.length);
        if (!lista.length) {
            $("#smpOsBody").html(filaVacia(9, "Sin órdenes de servicio pendientes"));
            return;
        }
        var html = "";
        lista.forEach(function (row) {
            var rol = String(row.rol || "").toUpperCase();
            var rolHtml =
                rol === "ORIGEN"
                    ? "<span class='label label-info'>ORIGEN</span>"
                    : rol === "DESTINO"
                      ? "<span class='label label-primary'>DESTINO</span>"
                      : escapar(rol);
            html +=
                "<tr>" +
                "<td>" +
                escapar(row.nro || "") +
                "</td>" +
                "<td>" +
                escapar(row.fecemi || "") +
                "</td>" +
                "<td>" +
                escapar(row.fecent || "") +
                "</td>" +
                "<td>" +
                rolHtml +
                "</td>" +
                "<td>" +
                escapar((row.codpro_origen || "") + (row.des_origen ? " — " + row.des_origen : "")) +
                "</td>" +
                "<td>" +
                escapar(
                    (row.codpro_destino || "") + (row.des_destino ? " — " + row.des_destino : "")
                ) +
                "</td>" +
                '<td class="smp-num">' +
                escapar(row.cantidad || "") +
                "</td>" +
                '<td class="smp-num">' +
                escapar(row.saldo || "") +
                "</td>" +
                "<td>" +
                labelEstado(row.estado) +
                "</td>" +
                "</tr>";
        });
        $("#smpOsBody").html(html);
    }

    function guardarMp() {
        if (!sublineaSel) {
            alerta("warning", "Elige una sublínea");
            return;
        }
        if (fabExiste) {
            alerta("warning", "Ese código de fábrica ya existe");
            return;
        }
        var color = String($("#smpMpColor").val() || "").trim();
        var talla = String($("#smpMpTalla").val() || "").trim();
        var unidad = String($("#smpMpUnidad").val() || "").trim();
        var nombre = String($("#smpMpNombre").val() || "").trim();
        if (!color || !talla || !unidad) {
            alerta("warning", "Completa color, talla y unidad");
            return;
        }
        if (!nombre) {
            alerta("warning", "Ingresa la descripción");
            return;
        }
        var data = {
            linea: sublineaSel.linea,
            subcodigo: sublineaSel.subcodigo,
            color: color,
            talla: talla,
            unidad: unidad,
            nombre: nombre,
            codalt: String($("#smpMpCodAlt").val() || "").trim(),
            peso: String($("#smpMpPeso").val() || "0"),
            adval: String($("#smpMpAdval").val() || "0"),
            seguro: String($("#smpMpSeguro").val() || "0"),
            stk_min: String($("#smpMpStkMin").val() || "0"),
            stk_max: String($("#smpMpStkMax").val() || "0"),
            origen_codpro: String($("#smpMpOrigenCodpro").val() || "").trim()
        };
        $("#smpBtnGuardarMp").prop("disabled", true);
        post("crearMp", data)
            .done(function (res) {
                if (!res || !res.ok) {
                    alerta("warning", (res && res.mensaje) || "No se pudo crear");
                    return;
                }
                $("#modalMpNueva").modal("hide");
                alerta("success", res.mensaje || "Materia prima creada");
                var codigo = sublineaSel.codigo_sublinea;
                cargarLista().done(function () {
                    pintarLista();
                    var item = buscarPorCodigo(codigo);
                    if (item) {
                        cargarMps(item);
                    }
                });
            })
            .fail(function () {
                alerta("error", "Error de conexión");
            })
            .always(function () {
                $("#smpBtnGuardarMp").prop("disabled", false);
            });
    }

    $(function () {
        if (!$(".smp-page").length) {
            return;
        }

        cargarLineas().always(function () {
            cargarLista().always(function () {
                restaurarDesdeUrl();
            });
        });

        $("#smpSelLinea").on("changed.bs.select change", function () {
            if (urlLock) {
                return;
            }
            $("#smpFiltroTexto").val("");
            limpiarMp();
            cargarPreview(lineaActual());
            actualizarUrl();
        });

        $("#smpFiltroTexto").on("keyup", function () {
            clearTimeout(debounceFiltro);
            debounceFiltro = setTimeout(pintarLista, 160);
        });

        $("#smpBtnNuevo").on("click", abrirCrear);

        $("#smpTablaBody").on("click", ".smp-btn-editar", function (e) {
            e.stopPropagation();
            var item = buscarItem($(this).attr("data-arg"));
            if (item) {
                abrirEditar(item);
            }
        });

        $("#smpTablaBody").on("click", "tr.smp-row-sub", function () {
            var item = buscarItem($(this).attr("data-arg"));
            if (item) {
                cargarMps(item);
            }
        });

        $("#smpBtnGuardar").on("click", guardar);

        $("#smpBtnNuevaMp").on("click", abrirCrearMp);
        $("#smpBtnGuardarMp").on("click", guardarMp);

        $("#smpMpBody").on("click", ".smp-btn-ordenes", function (e) {
            e.preventDefault();
            e.stopPropagation();
            abrirOrdenes($(this));
        });

        $("#smpMpBody").on("click", ".smp-btn-duplicar", function (e) {
            e.preventDefault();
            e.stopPropagation();
            abrirDuplicarMp($(this).attr("data-codpro"));
        });

        $("#smpMpColor, #smpMpTalla").on("changed.bs.select change", function () {
            sugerirDescripcionMp();
            clearTimeout(fabTimer);
            fabTimer = setTimeout(validarCodFab, 180);
        });

        $("#smpMpNombre").on("input", function () {
            descMpManual = true;
        });

        $("#smpNombre").on("keydown", function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                guardar();
            }
        });
    });
})(jQuery);
