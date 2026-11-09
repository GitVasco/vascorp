/* Comprobantes especiales: listado (#ceListado) y formulario (#ceForm). */
(function ($) {
    var URL = "ajax/comprobantes-especiales.ajax.php";
    var cat = null;
    var receptores = [];

    function esc(s) { return $("<div>").text(s == null ? "" : s).html(); }
    function err(msg) { swal({ type: "warning", title: "Atención", text: msg }); }
    function ok(msg) { swal({ type: "success", title: msg, timer: 1800, showConfirmButton: false }); }
    function confirmar(titulo, texto, boton, alAceptar, peligro) {
        swal({
            title: titulo, text: texto, type: peligro ? "warning" : "question", showCancelButton: true,
            confirmButtonColor: peligro ? "#d33" : "#3085d6", confirmButtonText: boton, cancelButtonText: "Cancelar"
        }).then(function () { alAceptar(); }, function () {});
    }
    function ajaxErr(xhr) {
        var m = "Error de comunicación.";
        try { m = JSON.parse(xhr.responseText).msg || m; } catch (e) {}
        err(m);
    }
    function refrescarSP() {
        $("#ceForm select.selectpicker, #ceListado select.selectpicker").selectpicker("refresh");
    }
    function num(v) { var n = parseFloat(String(v).replace(",", ".")); return isNaN(n) ? 0 : n; }
    function m2(n) { return (Math.round(n * 100) / 100).toFixed(2); }

    function cargarCatalogos(cb) {
        $.getJSON(URL, { accion: "catalogos" }, function (r) {
            if (r.faltan && r.faltan.length) {
                $("#ceFaltan").html("Faltan tablas: <b>" + esc(r.faltan.join(", ")) + "</b>. Ejecutar <code>docs/sql/comprobantes-especiales.sql</code>.").show();
                return;
            }
            cat = r;
            cb();
        }).fail(ajaxErr);
    }

    /* =====================================================
       LISTADO
    ===================================================== */
    function iniciarListado() {
        var tabla = $("#ceTabla").DataTable({
            order: [[0, "desc"]],
            language: { emptyTable: "No hay comprobantes en el rango", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ comprobante(s)", paginate: { next: "Sig.", previous: "Ant." } },
            autoWidth: false,
            columns: [
                { data: null, className: "ce-nowrap", render: function (r) { return "<b>" + esc(r.documento) + "</b><br><small class='text-muted'>" + (r.tipo_doc === "01" ? "Factura" : "Boleta") + "</small>"; } },
                { data: "fecha_emision", className: "text-center ce-nowrap", render: function (v) { var p = String(v || "").split("-"); return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : esc(v); } },
                { data: null, render: function (r) { return "<div style='min-width:170px'>" + esc(r.receptor_nombre) + "<br><small class='text-muted'>" + (r.receptor_tipo_doc === "6" ? "RUC " : "DNI ") + esc(r.receptor_doc) + "</small></div>"; } },
                { data: "tipo_operacion", className: "ce-nowrap", render: function (v) {
                    var p = cat && cat.perfiles ? cat.perfiles[v] : null;
                    return esc(p && p.corto ? p.corto : (cat && cat.tipos && cat.tipos[v]) || v);
                } },
                { data: "categoria", className: "ce-nowrap", render: function (v, t, r) {
                    var p = cat && cat.perfiles ? cat.perfiles[r.tipo_operacion] : null;
                    return esc(p && p.categorias && p.categorias[v] ? p.categorias[v] : v);
                } },
                { data: "afectacion", render: function (v) {
                    var n = cat && cat.afectacion_nombres ? cat.afectacion_nombres[v] : "";
                    return "<b>" + esc(v) + "</b>" + (n ? "<br><small class='text-muted'>" + esc(n) + "</small>" : "");
                } },
                { data: "total_gratuito", className: "text-right ce-nowrap", render: function (v) { return Number(v).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } },
                { data: "igv_informativo", className: "text-right ce-nowrap", render: function (v) { return Number(v).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } },
                { data: "lote", className: "ce-nowrap", render: function (v) { return "<small class='text-muted'>" + esc(v) + "</small>"; } },
                { data: "estado", className: "text-center ce-nowrap", render: function (v) {
                    var c = v === "ENVIADO" ? "primary" : v === "ANULADO" ? "danger" : v === "BAJA" ? "warning" : "success";
                    return "<span class='label label-" + c + "'>" + v + "</span>";
                } },
                { data: null, orderable: false, className: "text-center ce-nowrap", render: function (r) {
                    var imp = "<a class='btn btn-xs btn-success' title='Imprimir' target='_blank' href='vistas/reportes_ticket/impresion_comprobante_especial.php?id=" + r.id + "'><i class='fa fa-print'></i></a>";
                    var csv = "<a class='btn btn-xs btn-default' title='Ver CSV (no envía nada)' target='_blank' href='ajax/comprobantes-especiales.ajax.php?accion=ver-csv&id=" + r.id + "'><i class='fa fa-file-text-o'></i></a>";
                    var del = "<button class='btn btn-xs btn-default ceEliminar' title='Eliminar' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-trash'></i></button>";
                    var g;
                    if (r.estado === "ANULADO") g = imp + del;
                    else if (r.estado === "ENVIADO") g = imp + csv +
                        "<button class='btn btn-xs btn-warning ceBaja' title='Marcar baja (ya se dio de baja en eFact)' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-arrow-down'></i></button>";
                    else if (r.estado === "BAJA") g = imp + csv;
                    else g = imp + csv +
                        "<button class='btn btn-xs btn-primary ceEnviar' title='Enviar a eFact' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-paper-plane'></i></button>" +
                        "<button class='btn btn-xs btn-danger ceAnular' title='Anular' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-ban'></i></button>" + del;
                    return "<div class='btn-group' style='white-space:nowrap'>" + g + "</div>";
                } }
            ]
        });
        function cargar() {
            $.getJSON(URL, { accion: "listar", desde: $("#ceDesde").val(), hasta: $("#ceHasta").val(), estado: $("#ceEstadoFiltro").val(), tipo_operacion: $("#ceTipoFiltro").val(), lote: $.trim($("#ceLoteFiltro").val()) }, function (r) {
                tabla.clear().rows.add(r.datos || []).draw();
            }).fail(ajaxErr);
        }
        $("#ceBuscar").on("click", cargar);
        // Limpiar filtros: vuelve al mes en curso, sin estado, tipo ni lote
        var desdeInicial = $("#ceDesde").val(), hastaInicial = $("#ceHasta").val();
        $("#ceLimpiar").on("click", function () {
            $("#ceDesde").val(desdeInicial);
            $("#ceHasta").val(hastaInicial);
            $("#ceLoteFiltro").val("");
            $("#ceEstadoFiltro, #ceTipoFiltro").val("").selectpicker("refresh");
            tabla.search("");   // también el cuadro "Buscar" de la tabla
            cargar();
        });
        $("#ceTabla").on("click", ".ceEnviar", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            confirmar("¿Enviar " + doc + " a eFact?", "Se deja el CSV en la carpeta que vigila eFact. Ya no se podrá editar ni anular desde aquí.", "Sí, enviar", function () {
                $.post(URL, { accion: "enviar", id: id }, function (r) { ok("CSV generado: " + r.archivo); cargar(); }, "json").fail(ajaxErr);
            });
        });
        $("#ceTabla").on("click", ".ceBaja", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            confirmar("¿" + doc + " ya se dio de baja en eFact?", "Solo deja el registro en el sistema; la baja se hace en el portal de eFact.", "Sí, ya está de baja", function () {
                $.post(URL, { accion: "baja", id: id }, function () { ok("Marcado como BAJA"); cargar(); }, "json").fail(ajaxErr);
            }, true);
        });
        $("#ceTabla").on("click", ".ceAnular", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            confirmar("¿Anular " + doc + "?", "Aún no se envió a eFact. El correlativo ya quedó usado.", "Sí, anular", function () {
                $.post(URL, { accion: "anular", id: id }, function () { ok("Anulado"); cargar(); }, "json").fail(ajaxErr);
            }, true);
        });
        $("#ceTabla").on("click", ".ceEliminar", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            confirmar("¿Eliminar " + doc + "?", "Se borra definitivamente (el correlativo no cambia).", "Sí, eliminar", function () {
                $.post(URL, { accion: "eliminar", id: id }, function () { ok("Eliminado"); cargar(); }, "json").fail(ajaxErr);
            }, true);
        });
        cargarCatalogos(function () {
            $("#ceTipoFiltro").append(opciones(cat.tipos)).selectpicker("refresh");
            cargar();
        });
    }

    /* =====================================================
       FORMULARIO
    ===================================================== */
    function perfilActual() {
        return cat.perfiles[$("#ceTipo").val()] || cat.perfiles[Object.keys(cat.perfiles)[0]];
    }

    function opciones(obj) {
        var h = "";
        $.each(obj, function (k, v) { h += "<option value='" + esc(k) + "'>" + esc(v) + "</option>"; });
        return h;
    }

    function pintarAyuda() {
        var a = perfilActual().ayuda;
        $("#ceAyuda").toggle(!!a);
        if (!a) return;
        $("#ceAyudaTitulo").html("<i class='fa fa-lightbulb-o'></i> " + esc(a.titulo || "Guía rápida"));
        var $p = $("#ceAyudaPasos").empty();
        $.each(a.pasos || [], function (i, t) { $p.append($("<li>").text(t)); });
        $("#ceAyudaCodTitulo").text(a.codigos_titulo || "");
        var $c = $("#ceAyudaCodigos").empty();
        $.each(a.codigos || [], function (i, c) { $c.append($("<tr>").append($("<td>").text(c[0]), $("<td class='text-center'>").html("<b>" + esc(c[1]) + "</b>"))); });
        $("#ceAyudaNota").text(a.nota || "");
    }

    function filaItem() {
        return "<tr class='ceItem'>" +
            "<td><input type='text' class='form-control input-sm i-cod' maxlength='30'></td>" +
            "<td><input type='text' class='form-control input-sm i-desc' maxlength='250' placeholder='Ej. CANASTA NAVIDEÑA - ENTREGA A TRABAJADORES'></td>" +
            "<td><select class='form-control input-sm selectpicker i-und' data-width='100%' data-container='body'>" + opciones(cat.unidades) + "</select></td>" +
            "<td><input type='number' class='form-control input-sm i-cant' min='0' step='0.001' value='1'></td>" +
            "<td><input type='number' class='form-control input-sm i-ref' min='0' step='0.01'></td>" +
            "<td class='text-right i-vv'>0.00</td>" +
            "<td><button type='button' class='btn btn-xs btn-danger i-del'><i class='fa fa-times'></i></button></td></tr>";
    }

    function agregarFilaItem() {
        var $f = $(filaItem()).appendTo("#ceItems tbody");
        $f.find("select.selectpicker").selectpicker();
    }

    function leerItems() {
        var it = [];
        $("#ceItems tbody tr").each(function () {
            var $r = $(this);
            it.push({ codigo: $.trim($r.find(".i-cod").val()), descripcion: $.trim($r.find(".i-desc").val()), unidad: $r.find("select.i-und").val(),
                cantidad: $r.find(".i-cant").val(), valor_ref_unit: $r.find(".i-ref").val() });
        });
        return it;
    }

    function recalcular() {
        var gravado = parseInt($("#ceAfectacion").val(), 10) >= 11 && parseInt($("#ceAfectacion").val(), 10) <= 16;
        var tot = 0, igv = 0;
        $("#ceItems tbody tr").each(function () {
            var $r = $(this);
            var vv = Math.round(num($r.find(".i-ref").val()) * 100) / 100 * num($r.find(".i-cant").val());
            vv = Math.round(vv * 100) / 100;
            $r.find(".i-vv").text(m2(vv));
            tot += vv;
            if (gravado) igv += Math.round(vv * 0.18 * 100) / 100;
        });
        $("#ceTotGrat").text(m2(tot));
        $("#ceTotIgv").text(m2(igv));
    }

    function pintarReceptores() {
        var $t = $("#ceReceptores tbody").empty();
        var b = 0, f = 0;
        $.each(receptores, function (i, r) {
            var tipo = r.doc.length === 11 ? "Factura" : r.doc.length === 8 ? "Boleta" : "<span class='text-red'>inválido</span>";
            if (r.doc.length === 11) f++; else if (r.doc.length === 8) b++;
            $t.append("<tr data-i='" + i + "'><td>" + esc(r.doc) + "</td>" +
                "<td><input type='text' class='form-control input-sm r-nombre" + (r.nombre ? "" : " ceVacio") + "' maxlength='100' placeholder='Escribe el nombre' value='" + esc(r.nombre) + "'" + (r.nombre ? "" : " style='border-color:#dd4b39'") + "></td>" +
                "<td><input type='text' class='form-control input-sm r-dir' maxlength='100' value='" + esc(r.direccion) + "'></td>" +
                "<td>" + tipo + "</td><td><button type='button' class='btn btn-xs btn-danger r-del'><i class='fa fa-times'></i></button></td></tr>");
        });
        $("#ceResumenRec").text(receptores.length ? receptores.length + " comprobantes: " + b + " boleta(s), " + f + " factura(s)." : "Sin receptores.");
    }

    function estadoDoc(html, clase) {
        $("#ceDocEstado").attr("class", clase || "").html(html);
    }

    // Un DNI/RUC: busca en la API; si no lo encuentra lo agrega vacío para completarlo a mano.
    var buscandoDoc = false;
    function buscarDoc() {
        var doc = $("#ceDocBuscar").val().replace(/\D/g, "");
        if (doc.length !== 8 && doc.length !== 11) { estadoDoc("Escribe un DNI (8) o RUC (11).", "text-red"); return; }
        if (buscandoDoc) return;
        for (var k = 0; k < receptores.length; k++) {
            if (receptores[k].doc === doc) { estadoDoc("Ya está en la lista.", "text-orange"); $("#ceDocBuscar").select(); return; }
        }
        buscandoDoc = true;
        estadoDoc("<i class='fa fa-spinner fa-spin'></i> Buscando " + esc(doc) + "…", "text-muted");
        function terminar() {
            buscandoDoc = false;
            $("#ceDocBuscar").val("");
            pintarReceptores();
        }
        $.getJSON(URL, { accion: "consultar-doc", tipo_doc: doc.length === 11 ? "6" : "1", numero: doc }, function (d) {
            receptores.push({ doc: doc, nombre: d.razon_social, direccion: d.direccion, ubigeo: d.ubigeo, dpto: d.dpto, prov: d.prov, dist: d.dist, email: "" });
            terminar();
            estadoDoc("<i class='fa fa-check'></i> " + esc(d.razon_social), "text-green");
            $("#ceDocBuscar").focus();
        }).fail(function (xhr) {
            var m = "No se encontró.";
            try { m = JSON.parse(xhr.responseText).msg || m; } catch (e) {}
            receptores.push({ doc: doc, nombre: "", direccion: "", ubigeo: "", dpto: "", prov: "", dist: "", email: "" });
            terminar();
            estadoDoc("<i class='fa fa-exclamation-triangle'></i> " + esc(m) + " Escribe el nombre a mano en la lista.", "text-red");
            $("#ceReceptores tbody tr:last .r-nombre").focus();
        });
    }

    function agregarLista() {
        var lineas = $("#cePegar").val().split(/\r?\n/), malos = [];
        $.each(lineas, function (i, l) {
            l = $.trim(l);
            if (!l) return;
            // DNI (8) o RUC (11) al inicio; lo que siga (tab, espacio, ; o ,) es el nombre
            var m = l.match(/^(\d{8}|\d{11})(?:[\s;,]+(.*))?$/);
            if (!m) { malos.push(l); return; }
            var doc = m[1];
            var nombre = $.trim(m[2] || "");
            for (var k = 0; k < receptores.length; k++) { if (receptores[k].doc === doc) return; }
            receptores.push({ doc: doc, nombre: nombre, direccion: "", ubigeo: "", dpto: "", prov: "", dist: "", email: "" });
        });
        $("#cePegar").val("");
        pintarReceptores();
        if (malos.length) err("Líneas ignoradas (documento inválido):\n" + malos.join("\n"));
        consultarTodos();   // los que vienen solo con DNI/RUC se buscan solos
    }

    // Busca en la API los receptores sin nombre (uno por uno). Los que no se encuentran quedan en rojo para escribirlos a mano.
    var consultando = false;
    function consultarTodos() {
        if (consultando) return;
        var pend = [];
        $.each(receptores, function (i, r) { if (!r.nombre) pend.push(i); });
        if (!pend.length) { estadoDoc("No hay nombres pendientes.", "text-muted"); return; }
        consultando = true;
        var total = pend.length, hechos = 0, fallidos = [];
        (function sig() {
            if (!pend.length) {
                consultando = false;
                pintarReceptores();
                if (fallidos.length) {
                    estadoDoc("<i class='fa fa-exclamation-triangle'></i> No se encontró: " + esc(fallidos.join(", ")) + ". Escribe el nombre a mano (en rojo).", "text-red");
                    $("#ceReceptores tbody .ceVacio:first").focus();
                } else {
                    estadoDoc("<i class='fa fa-check'></i> Datos completados (" + hechos + ").", "text-green");
                }
                return;
            }
            var i = pend.shift(), r = receptores[i];
            estadoDoc("<i class='fa fa-spinner fa-spin'></i> Buscando " + (total - pend.length) + " de " + total + "…", "text-muted");
            $.getJSON(URL, { accion: "consultar-doc", tipo_doc: r.doc.length === 11 ? "6" : "1", numero: r.doc }, function (d) {
                r.nombre = d.razon_social;
                if (!r.direccion) r.direccion = d.direccion;
                r.ubigeo = d.ubigeo; r.dpto = d.dpto; r.prov = d.prov; r.dist = d.dist;
                hechos++;
            }).fail(function () { fallidos.push(r.doc); }).always(function () { pintarReceptores(); sig(); });
        })();
    }

    function iniciarFormulario() {
        cargarCatalogos(function () {
            $("#ceTipo").html(opciones(cat.tipos)).selectpicker("refresh");
            function cargarPerfil() {
                var p = perfilActual();
                $("#ceCategoria").html(opciones(p.categorias));
                $("#ceAfectacion").html(opciones(p.afectaciones));
                refrescarSP();
                pintarAyuda();
                sugerir();
            }
            function sugerir() {
                var p = perfilActual();
                var s = (p.sugerido || {})[$("#ceCategoria").val()] || { afe: "", motivo: "" };
                if (s.afe && p.afectaciones[s.afe]) $("#ceAfectacion").val(s.afe).selectpicker("refresh");
                if (!$.trim($("#ceMotivo").val()) || $("#ceMotivo").data("auto")) { $("#ceMotivo").val(s.motivo).data("auto", true); }
                recalcular();
            }
            $("#ceMotivo").on("input", function () { $(this).data("auto", false); });
            $("#ceTipo").on("change", cargarPerfil);
            $("#ceCategoria").on("change", sugerir);
            $("#ceAfectacion").on("change", recalcular);
            agregarFilaItem();
            cargarPerfil();
        });
        $("#ceAddItem").on("click", function () { if (cat) agregarFilaItem(); });
        $("#ceItems").on("input change", "input,select", recalcular);
        $("#ceItems").on("click", ".i-del", function () {
            if ($("#ceItems tbody tr").length > 1) { $(this).closest("tr").remove(); recalcular(); }
        });
        var tDoc = null;
        $("#ceDocBuscar").on("input", function () {
            var v = $(this).val().replace(/\D/g, "");
            $(this).val(v);
            clearTimeout(tDoc);
            if (v.length === 11) buscarDoc();
            else if (v.length === 8) tDoc = setTimeout(buscarDoc, 700);   // espera: puede ser el inicio de un RUC
            else estadoDoc("", "");
        }).on("keydown", function (e) { if (e.which === 13) { e.preventDefault(); buscarDoc(); } });
        $("#ceDocBtn").on("click", buscarDoc);
        $("#ceAgregarLista").on("click", agregarLista);
        $("#ceConsultarTodos").on("click", consultarTodos);
        $("#ceReceptores").on("input", ".r-nombre", function () { receptores[$(this).closest("tr").data("i")].nombre = $(this).val(); });
        $("#ceReceptores").on("input", ".r-dir", function () { receptores[$(this).closest("tr").data("i")].direccion = $(this).val(); });
        $("#ceReceptores").on("click", ".r-del", function () { receptores.splice($(this).closest("tr").data("i"), 1); pintarReceptores(); });

        $("#ceGuardar").on("click", function () {
            if (!cat) return;
            var $b = $(this);
            confirmar("¿Crear " + receptores.length + " comprobante(s)?", "Quedan en estado GENERADO. Se envían a eFact desde el listado.", "Sí, crear", function () {
                $b.prop("disabled", true);
                $.post(URL, {
                    accion: "crear-lote", tipo_operacion: $("#ceTipo").val(),
                    fecha: $("#ceFecha").val(), categoria: $("#ceCategoria").val(), afectacion: $("#ceAfectacion").val(),
                    motivo: $("#ceMotivo").val(), observaciones: $("#ceObs").val(),
                    items: JSON.stringify(leerItems()), receptores: JSON.stringify(receptores)
                }, function (r) {
                    swal({ type: "success", title: "Creados " + r.creados.length + " comprobante(s)", text: "Lote " + r.lote, timer: 1500, showConfirmButton: false })
                        .then(function () { window.location = "index.php?ruta=comprobantes-especiales&lote=" + encodeURIComponent(r.lote); }, function () { window.location = "index.php?ruta=comprobantes-especiales&lote=" + encodeURIComponent(r.lote); });
                }, "json").fail(function (xhr) { $b.prop("disabled", false); ajaxErr(xhr); });
            });
        });
    }

    $(function () {
        if ($("#ceListado").length) iniciarListado();
        if ($("#ceForm").length) iniciarFormulario();
    });
})(jQuery);
