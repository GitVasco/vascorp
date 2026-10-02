/* GRE remitente manual: listado (#greListado) y formulario (#greForm). */
(function ($) {
    var URL = "ajax/gre-manual.ajax.php";
    var cat = null;
    var items = [];
    var docs = [];
    var tipoItem = "modelo";

    function esc(s) {
        return $("<div>").text(s == null ? "" : s).html();
    }
    function err(msg) {
        if (window.toastr) toastr.error(msg);
        else alert(msg);
    }
    function refrescarSP() {
        $("#greForm select.selectpicker").selectpicker("refresh");
    }
    function ok(msg) {
        if (window.toastr) toastr.success(msg);
    }
    function ajaxErr(xhr) {
        var m = "Error de comunicación.";
        try { m = JSON.parse(xhr.responseText).msg || m; } catch (e) {}
        err(m);
    }

    /* ---------- buscador genérico ---------- */
    function buscador($input, $lista, tipo, render, onPick) {
        var t = null;
        $input.on("input", function () {
            clearTimeout(t);
            var q = $.trim($input.val());
            if (q.length < 2) { $lista.hide().empty(); return; }
            t = setTimeout(function () {
                $.getJSON(URL, { accion: "buscar", tipo: typeof tipo === "function" ? tipo() : tipo, q: q }, function (r) {
                    $lista.empty();
                    $.each(r.datos || [], function (i, d) {
                        $("<li class='list-group-item'>").html(render(d)).data("d", d).appendTo($lista);
                    });
                    if (!(r.datos || []).length) $("<li class='list-group-item text-muted'>").text("Sin resultados").appendTo($lista);
                    $lista.show();
                }).fail(ajaxErr);
            }, 250);
        });
        $lista.on("click", "li", function () {
            var d = $(this).data("d");
            if (!d) return;
            onPick(d);
            $lista.hide().empty();
            $input.val("");
        });
        $(document).on("click", function (e) {
            if (!$(e.target).closest($lista).length && e.target !== $input[0]) $lista.hide();
        });
    }

    /* =====================================================
       LISTADO
    ===================================================== */
    function iniciarListado() {
        var tabla = $("#greTabla").DataTable({
            order: [[0, "desc"]],
            language: { emptyTable: "No hay guías en el rango", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ guías", paginate: { next: "Sig.", previous: "Ant." } },
            columns: [
                { data: null, render: function (r) {
                    return "<b>" + esc(r.documento) + "</b>" + (r.doc_interno ? " <small class='text-muted' title='Número interno original'>(int. " + esc(r.doc_interno) + ")</small>" : "");
                } },
                { data: "tipo", render: function (v) {
                    return v === "INTERNA" ? "<span class='label label-default'>INTERNA</span>" : "<span class='label label-info'>ELECTRÓNICA</span>";
                } },
                { data: "fecha_emision" },
                { data: "fecha_traslado" },
                { data: null, render: function (r) { return esc(r.motivo_cod + " - " + r.motivo_desc); } },
                { data: null, render: function (r) { return esc(r.dest_nombre) + " <small>" + esc(r.dest_doc) + "</small>"; } },
                { data: "lle_dist" },
                { data: "items" },
                { data: "peso_kg" },
                { data: "estado", render: function (v) {
                    var c = v === "ENVIADO" ? "primary" : v === "ANULADO" ? "danger" : "success";
                    return "<span class='label label-" + c + "'>" + v + "</span>";
                } },
                { data: "usuario_registro" },
                { data: null, orderable: false, render: function (r) {
                    var imp = "<a class='btn btn-xs btn-success' title='Imprimir' target='_blank' href='vistas/reportes_ticket/gre_manual.php?id=" + r.id + "'><i class='fa fa-print'></i></a>";
                    var del = "<button class='btn btn-xs btn-default greEliminar' title='Eliminar' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-trash'></i></button>";
                    if (r.estado === "ANULADO") return "<div class='btn-group'>" + imp + del + "</div>";
                    if (r.estado !== "GENERADO") return "<div class='btn-group'>" + imp + "</div>";
                    var accion = r.tipo === "INTERNA"
                        ? "<button class='btn btn-xs btn-info greConvertir' title='Convertir a electrónica' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-exchange'></i></button>"
                        : "<button class='btn btn-xs btn-primary greEnviar' title='Enviar a EFACT' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-paper-plane'></i></button>";
                    return "<div class='btn-group'>" + imp +
                        "<a class='btn btn-xs btn-warning' title='Editar' href='gre-manual-crear&id=" + r.id + "'><i class='fa fa-pencil'></i></a>" + accion +
                        "<button class='btn btn-xs btn-danger greAnular' title='Anular' data-id='" + r.id + "' data-doc='" + esc(r.documento) + "'><i class='fa fa-ban'></i></button>" + del + "</div>";
                } },
            ],
        });
        function cargar() {
            $.getJSON(URL, { accion: "listar", desde: $("#greDesde").val(), hasta: $("#greHasta").val(), estado: $("#greEstadoFiltro").val(), tipo: $("#greTipoFiltro").val() }, function (r) {
                tabla.clear().rows.add(r.datos || []).draw();
            }).fail(ajaxErr);
        }
        $("#greBuscar").on("click", cargar);
        $("#greTabla").on("click", ".greEnviar", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            if (!confirm("¿Enviar la guía " + doc + " a EFACT? Después ya no se podrá editar.")) return;
            $.post(URL, { accion: "enviar", id: id }, function (r) { ok("Se generó el CSV de " + r.documento); cargar(); }, "json").fail(ajaxErr);
        });
        $("#greTabla").on("click", ".greAnular", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            if (!confirm("¿Anular la guía " + doc + "?")) return;
            $.post(URL, { accion: "anular", id: id }, function () { ok("Guía anulada"); cargar(); }, "json").fail(ajaxErr);
        });
        // Convertir interna -> electrónica
        var convId = 0;
        $("#greTabla").on("click", ".greConvertir", function () {
            convId = $(this).data("id");
            var doc = $(this).data("doc");
            $.getJSON(URL, { accion: "series" }, function (r) {
                var el = $.grep(r.series || [], function (s) { return s.tipo === "ELECTRONICA"; });
                if (!el.length) { err("No hay series electrónicas activas."); return; }
                var $s = $("#greConvSerie").empty();
                $.each(el, function (i, x) { $s.append($("<option>").val(x.serie).text(x.serie + " (próximo " + ("00000000" + (parseInt(x.correlativo, 10) + 1)).slice(-8) + ")")); });
                $s.selectpicker("refresh");
                $("#greConvTexto").text("La guía interna " + doc + " pasará a ser electrónica.");
                $("#modalGreConvertir").modal("show");
            }).fail(ajaxErr);
        });
        $("#greConvGuardar").on("click", function () {
            $.post(URL, { accion: "convertir", id: convId, serie: $("#greConvSerie").val() }, function (r) {
                ok("Ahora es " + r.documento + " (antes " + r.doc_interno + ")");
                $("#modalGreConvertir").modal("hide");
                cargar();
            }, "json").fail(ajaxErr);
        });
        $("#greTabla").on("click", ".greEliminar", function () {
            var id = $(this).data("id"), doc = $(this).data("doc");
            if (!confirm("¿ELIMINAR la guía " + doc + "? Se borra definitivamente (el correlativo no cambia).")) return;
            $.post(URL, { accion: "eliminar", id: id }, function () { ok("Guía eliminada"); cargar(); }, "json").fail(ajaxErr);
        });

        // Corregir correlativo
        var seriesCor = [];
        function pintarCor() {
            var s = seriesCor[$("#greCorSerie").val()];
            if (!s) return;
            $("#greCorNumero").val(s.correlativo);
            $("#greCorInfo").text("Último usado: " + s.correlativo + " · Mayor emitido: " + s.max_emitido + ". No puede ser menor al mayor emitido.");
            actualizarProximo();
        }
        function actualizarProximo() {
            var s = seriesCor[$("#greCorSerie").val()], n = parseInt($("#greCorNumero").val(), 10);
            if (!s || isNaN(n)) { $("#greCorProximo").text(""); return; }
            $("#greCorProximo").text("La próxima guía será " + s.serie + "-" + ("00000000" + (n + 1)).slice(-8));
        }
        $("#greBtnCorrelativo").on("click", function () {
            $.getJSON(URL, { accion: "correlativo-info" }, function (r) {
                seriesCor = r.series || [];
                var $s = $("#greCorSerie").empty();
                $.each(seriesCor, function (i, x) { $s.append($("<option>").val(i).text(x.serie + (x.tipo === "INTERNA" ? " (interna)" : " (electrónica)"))); });
                $s.selectpicker("refresh");
                pintarCor();
                $("#modalGreCorrelativo").modal("show");
            }).fail(ajaxErr);
        });
        $("#greCorSerie").on("change", pintarCor);
        $("#greCorNumero").on("input", actualizarProximo);
        $("#greCorGuardar").on("click", function () {
            var s = seriesCor[$("#greCorSerie").val()];
            if (!s) return;
            var n = $("#greCorNumero").val();
            if (!confirm("¿Dejar el último usado de " + s.serie + " en " + n + "?")) return;
            $.post(URL, { accion: "correlativo-fijar", serie: s.serie, numero: n }, function (r) {
                ok("Correlativo actualizado. Próxima: " + r.proximo);
                $("#modalGreCorrelativo").modal("hide");
            }, "json").fail(ajaxErr);
        });
        cargar();
    }

    /* =====================================================
       FORMULARIO
    ===================================================== */
    function v(id) { return $.trim($("#" + id).val()); }

    function unidadSelect(sel) {
        var h = "<select class='form-control input-sm it-unidad' data-live-search='true' data-width='140px' data-container='body'>";
        $.each(cat.unidades, function (i, u) {
            h += "<option value='" + esc(u.codigo) + "' data-desc='" + esc(u.descripcion) + "'" + (u.codigo === sel ? " selected" : "") + ">" + esc(u.descripcion) + " (" + esc(u.codigo) + ")</option>";
        });
        return h + "</select>";
    }

    function pintarItems() {
        var $tb = $("#greTablaItems tbody").empty();
        $("#greContador").text(items.length + (items.length === 1 ? " ítem" : " ítems"));
        $.each(items, function (i, it) {
            var tr = $("<tr>").data("i", i);
            tr.append("<td>" + (i + 1) + "</td><td>" + esc(it.origen) + "</td>");
            tr.append("<td><input class='form-control input-sm it-codigo' maxlength='16' value='" + esc(it.codigo) + "'></td>");
            tr.append("<td><input class='form-control input-sm it-desc' maxlength='250' value='" + esc(it.descripcion) + "'></td>");
            tr.append("<td>" + unidadSelect(it.unidad_cod) + "</td>");
            tr.append("<td><input type='number' step='0.001' min='0' class='form-control input-sm it-cant' value='" + esc(it.cantidad) + "'></td>");
            tr.append("<td><button class='btn btn-xs btn-danger it-del'><i class='fa fa-trash'></i></button></td>");
            $tb.append(tr);
        });
        $tb.find(".it-unidad").selectpicker();
    }
    function descUnidad(cod) {
        var d = "";
        $.each(cat.unidades, function (i, u) { if (u.codigo === cod) d = u.descripcion; });
        return d.substring(0, 15);
    }
    // Guarda lo escrito en la tabla antes de repintar
    function sincronizar() {
        $("#greTablaItems tbody tr").each(function () {
            var o = items[$(this).data("i")];
            if (!o) return;
            o.codigo = $(this).find(".it-codigo").val();
            o.descripcion = $(this).find(".it-desc").val();
            o.cantidad = $(this).find(".it-cant").val();
            o.unidad_cod = $(this).find(".it-unidad").val();
            o.unidad_desc = descUnidad(o.unidad_cod);
        });
    }
    function agregarItem(o) {
        sincronizar();
        var cod = o.unidad || "C62";
        items.push({ origen: o.origen, codigo: o.codigo || "", descripcion: o.descripcion || "", unidad_cod: cod, unidad_desc: descUnidad(cod), cantidad: o.cantidad || "" });
        pintarItems();
    }

    function pintarDocs() {
        var $u = $("#greDocsLista").empty();
        $.each(docs, function (i, d) {
            $("<li>").html(esc(d.tipo + " · " + d.numero) + " <a href='#' class='doc-del' data-i='" + i + "'>&times;</a>").appendTo($u);
        });
    }

    function setModalidad() {
        var priv = $("input[name=greModalidad]:checked").val() === "02";
        $("#grePrivado").toggle(priv);
        $("#grePublico").toggle(!priv);
    }

    function setMotivo() {
        var es04 = $("#greMotivo").val() === "04";
        $("#greAvisoMotivo04").toggle(es04);
        $("#greDestBuscar").toggle(!es04);
        $("#greDestNombre,#greDestTipoDoc,#greDestDoc").prop("disabled", es04);
        if (es04) {
            var r = cat.remitente;
            $("#greDestNombre").val(r.nombre);
            $("#greDestTipoDoc").val(r.tipo_doc);
            $("#greDestDoc").val(r.doc);
            // Traslado entre locales: llegada y partida se escriben, pero sugerimos Vasco en partida
            if (!v("gre_par_ubigeo")) usarVasco("par");
        }
        if (!$("#greMotivoDesc").data("tocado")) {
            $("#greMotivoDesc").val(cat.motivos[$("#greMotivo").val()] || "");
        }
        refrescarSP();
    }

    function usarVasco(p) {
        var r = cat.remitente;
        $("#gre_" + p + "_ubigeo").val(r.ubigeo);
        $("#gre_" + p + "_direccion").val(r.direccion);
        $("#gre_" + p + "_dpto").val(r.dpto);
        $("#gre_" + p + "_prov").val(r.prov);
        $("#gre_" + p + "_dist").val(r.dist);
    }

    function recolectar() {
        var m = $("input[name=greModalidad]:checked").val();
        var it = [];
        $("#greTablaItems tbody tr").each(function () {
            var i = $(this).data("i"), o = items[i];
            var $s = $(this).find(".it-unidad option:selected");
            it.push({
                origen: o.origen,
                codigo: $(this).find(".it-codigo").val(),
                descripcion: $(this).find(".it-desc").val(),
                unidad_cod: $s.val(),
                unidad_desc: $s.data("desc") ? String($s.data("desc")).substring(0, 15) : "",
                cantidad: $(this).find(".it-cant").val(),
            });
        });
        var d = {
            accion: "guardar", id: $("#greForm").data("id") || 0, serie: v("greSerie"),
            fecha_emision: v("greFechaEmision"), fecha_traslado: v("greFechaTraslado"),
            motivo_cod: v("greMotivo"), motivo_desc: v("greMotivoDesc"), modalidad: m,
            peso_kg: v("grePeso"), bultos: v("greBultos"), observaciones: v("greObs"),
            dest_origen: $("input[name=greDestOrigen]:checked").val(), dest_codigo: $("#greDestNombre").data("codigo") || "",
            dest_nombre: v("greDestNombre"), dest_tipo_doc: $("#greDestTipoDoc").val(), dest_doc: v("greDestDoc"), dest_email: v("greDestEmail"),
            transp_ruc: v("greTranspRuc"), transp_nombre: v("greTranspNombre"), transp_mtc: v("greTranspMtc"),
            chofer_tipo_doc: $("#greChoferTipoDoc").val(), chofer_doc: v("greChoferDoc"), chofer_nombres: v("greChoferNombres"),
            chofer_apellidos: v("greChoferApellidos"), chofer_licencia: v("greChoferLicencia"), placa: v("grePlaca"),
            docs_rel: JSON.stringify(docs), items: JSON.stringify(it),
        };
        $.each(["par", "lle"], function (i, p) {
            $.each(["ubigeo", "direccion", "dpto", "prov", "dist"], function (j, k) { d[p + "_" + k] = v("gre_" + p + "_" + k); });
        });
        return d;
    }

    function poblar(g) {
        $("#greSerie").html("<option>" + esc(g.serie) + (g.tipo === "INTERNA" ? " · Interna" : " · Electrónica") + "</option>").prop("disabled", true);
        $("#greFechaEmision").val(g.fecha_emision);
        $("#greFechaTraslado").val(g.fecha_traslado);
        $("#greMotivo").val(g.motivo_cod);
        $("#greMotivoDesc").val(g.motivo_desc).data("tocado", true);
        $("#grePeso").val(g.peso_kg);
        $("#greBultos").val(g.bultos);
        $("#greObs").val(g.observaciones);
        $("input[name=greDestOrigen][value=" + g.dest_origen + "]").prop("checked", true);
        $("#greDestNombre").val(g.dest_nombre).data("codigo", g.dest_codigo);
        $("#greDestTipoDoc").val(g.dest_tipo_doc);
        $("#greDestDoc").val(g.dest_doc);
        $("#greDestEmail").val(g.dest_email);
        $.each(["par", "lle"], function (i, p) {
            $.each(["ubigeo", "direccion", "dpto", "prov", "dist"], function (j, k) { $("#gre_" + p + "_" + k).val(g[p + "_" + k]); });
        });
        $("input[name=greModalidad][value=" + g.modalidad + "]").prop("checked", true);
        $("#greTranspRuc").val(g.transp_ruc); $("#greTranspNombre").val(g.transp_nombre); $("#greTranspMtc").val(g.transp_mtc);
        $("#greChoferTipoDoc").val(g.chofer_tipo_doc || "1"); $("#greChoferDoc").val(g.chofer_doc);
        $("#greChoferNombres").val(g.chofer_nombres); $("#greChoferApellidos").val(g.chofer_apellidos);
        $("#greChoferLicencia").val(g.chofer_licencia); $("#grePlaca").val(g.placa);
        docs = g.docs_rel ? JSON.parse(g.docs_rel) : [];
        items = $.map(g.items, function (i) {
            return { origen: i.origen, codigo: i.codigo, descripcion: i.descripcion, unidad_cod: i.unidad_cod, unidad_desc: i.unidad_desc, cantidad: i.cantidad };
        });
        pintarItems(); pintarDocs(); setModalidad(); setMotivo(); refrescarSP();
        if (docs.length) $("#greDocsBox").show();
    }

    function iniciarFormulario() {
        $.getJSON(URL, { accion: "catalogos" }, function (c) {
            cat = c;
            if (c.faltan && c.faltan.length) {
                $("#greFaltan").text("Faltan tablas en la BD (ejecutar docs/sql/gre-manual.sql): " + c.faltan.join(", ")).show();
                return;
            }
            $.each(c.series, function (i, s) { $("#greSerie").append("<option value='" + esc(s.serie) + "'>" + esc(s.serie) + (s.tipo === "INTERNA" ? " · Interna" : " · Electrónica") + "</option>"); });
            $.each(c.motivos, function (k, d) { $("#greMotivo").append("<option value='" + k + "'>" + k + " - " + esc(d) + "</option>"); });
            $.each(c.choferes, function (i, x) { $("#greChoferSel").append($("<option>").val(i).text(x.nombres + " " + x.apellidos + " (" + x.doc + ")")); });
            $.each(c.vehiculos, function (i, x) { $("#greVehiculoSel").append($("<option>").val(x.placa).text(x.placa + (x.descripcion ? " - " + x.descripcion : ""))); });
            $.each(c.agencias, function (i, x) { $("#greAgenciaSel").append($("<option>").val(i).text(x.nombre)); });
            var hoy = new Date().toISOString().slice(0, 10);
            $("#greFechaEmision,#greFechaTraslado").val(hoy);
            $("#greMotivo").val("01");
            setMotivo(); setModalidad(); refrescarSP();
            var id = parseInt($("#greForm").data("id"), 10) || 0;
            if (id) {
                $.getJSON(URL, { accion: "obtener", id: id }, function (r) {
                    if (r.guia.estado !== "GENERADO") { err("Solo se edita una guía GENERADA."); window.location = "gre-manual"; return; }
                    poblar(r.guia);
                }).fail(ajaxErr);
            }
        }).fail(ajaxErr);

        $("#greMotivo").on("change", setMotivo);
        $("#greMotivoDesc").on("input", function () { $(this).data("tocado", true); });
        $("input[name=greModalidad]").on("change", setModalidad);
        $("#greUsarVasco").on("click", function () { usarVasco("par"); });

        // Destinatario
        buscador($("#greDestBuscador"), $("#greDestResultados"),
            function () { var o = $("input[name=greDestOrigen]:checked").val(); return o === "PROVEEDOR" ? "proveedor" : "cliente"; },
            function (d) { return "<b>" + esc(d.nombre) + "</b> <small>" + esc(d.documento) + " · " + esc(d.direccion) + "</small>"; },
            function (d) {
                $("#greDestNombre").val(d.nombre).data("codigo", d.codigo);
                $("#greDestTipoDoc").val(d.tipo_doc && $("#greDestTipoDoc option[value='" + d.tipo_doc + "']").length ? d.tipo_doc : "6");
                $("#greDestDoc").val(d.documento);
                $("#greDestEmail").val(d.email || "");
                refrescarSP();
                if (!v("gre_lle_direccion")) {
                    $("#gre_lle_direccion").val(d.direccion || "");
                    if (d.ubigeo) {
                        $("#gre_lle_ubigeo").val(d.ubigeo); $("#gre_lle_dpto").val(d.departamento || "");
                        $("#gre_lle_prov").val(d.provincia || ""); $("#gre_lle_dist").val(d.distrito || "");
                    }
                }
            });
        $("input[name=greDestOrigen]").on("change", function () {
            $("#greDestBuscador").closest(".gre-buscador").toggle($(this).val() !== "MANUAL");
            if ($(this).val() === "MANUAL") $("#greDestNombre").data("codigo", "");
        });

        // Ubigeos
        $(".gre-ubigeo-buscador").each(function () {
            var $i = $(this), p = $i.data("p");
            buscador($i, $i.siblings(".gre-resultados"), "ubigeo",
                function (d) { return "<b>" + esc(d.codigo) + "</b> " + esc(d.departamento + " / " + d.provincia + " / " + d.distrito); },
                function (d) {
                    $("#gre_" + p + "_ubigeo").val(d.codigo); $("#gre_" + p + "_dpto").val(d.departamento);
                    $("#gre_" + p + "_prov").val(d.provincia); $("#gre_" + p + "_dist").val(d.distrito);
                });
        });

        // Transporte guardado
        $("#greChoferSel").on("change", function () {
            var x = cat.choferes[$(this).val()];
            if (!x) return;
            $("#greChoferDoc").val(x.doc); $("#greChoferNombres").val(x.nombres); $("#greChoferApellidos").val(x.apellidos);
            $("#greChoferLicencia").val(x.licencia || ""); $("#greChoferTipoDoc").val("1");
            refrescarSP();
        });
        $("#greVehiculoSel").on("change", function () { if ($(this).val()) $("#grePlaca").val($(this).val()); });
        $("#greAgenciaSel").on("change", function () {
            var x = cat.agencias[$(this).val()];
            if (!x) return;
            $("#greTranspRuc").val(x.ruc); $("#greTranspNombre").val(x.nombre); $("#greTranspMtc").val(x.mtc || "");
        });

        // Ítems
        $("#greItemTabs a").on("click", function (e) {
            e.preventDefault();
            $("#greItemTabs li").removeClass("active"); $(this).parent().addClass("active");
            tipoItem = $(this).data("tipo");
            $("#greItemBuscarWrap").toggle(tipoItem !== "manual");
            $("#greAgregarManual").toggle(tipoItem === "manual");
        });
        buscador($("#greItemBuscador"), $("#greItemResultados"), function () { return tipoItem; },
            function (d) { return "<b>" + esc(d.codigo) + "</b> " + esc(d.descripcion); },
            function (d) {
                var o = { modelo: "MODELO", articulo: "ARTICULO", mp: "MP" }[tipoItem];
                agregarItem({ origen: o, codigo: d.codigo, descripcion: d.descripcion, unidad: d.unidad, cantidad: "" });
            });
        $("#greAgregarManual").on("click", function () { agregarItem({ origen: "MANUAL" }); });
        $("#greTablaItems").on("click", ".it-del", function () {
            sincronizar();
            items.splice($(this).closest("tr").data("i"), 1);
            pintarItems();
        });
        // Docs relacionados
        $("#greDocsToggle").on("click", function (e) { e.preventDefault(); $("#greDocsBox").slideToggle(120); });
        $("#greDocAgregar").on("click", function () {
            var n = v("greDocNumero");
            if (!n) return;
            docs.push({ tipo: $("#greDocTipo").val(), numero: n });
            $("#greDocNumero").val(""); pintarDocs();
        });
        $("#greDocsLista").on("click", ".doc-del", function (e) { e.preventDefault(); docs.splice($(this).data("i"), 1); pintarDocs(); });

        // Guardar
        $("#greGuardar").on("click", function () {
            var $b = $(this).prop("disabled", true);
            $.post(URL, recolectar(), function (r) {
                ok("Guía " + r.documento + " guardada");
                window.location = "gre-manual";
            }, "json").fail(function (xhr) { ajaxErr(xhr); $b.prop("disabled", false); });
        });
    }

    $(function () {
        if ($("#greListado").length) iniciarListado();
        if ($("#greForm").length) iniciarFormulario();
    });
})(jQuery);
