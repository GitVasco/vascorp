/* Exportación: configuración (#expConfig) — clientes del exterior (solo consulta) y partidas por línea. */
(function ($) {
    var URL = "ajax/exportacion.ajax.php";
    var partidas = [];

    function esc(s) { return $("<div>").text(s == null ? "" : s).html().replace(/"/g, "&quot;").replace(/'/g, "&#39;"); }
    function err(msg) { swal({ type: "warning", title: "Atención", text: msg }); }
    function ok(msg) { swal({ type: "success", title: msg, timer: 1500, showConfirmButton: false }); }
    function ajaxErr(xhr) {
        var m = "Error de comunicación.";
        try {
            var r = JSON.parse(xhr.responseText);
            m = r.msg || m;
            if (r.faltan && r.faltan.length) {
                $("#expFaltan").html("Falta: <b>" + esc(r.faltan.join(", ")) + "</b>. Ejecutar <code>docs/sql/exportacion-fase1.sql</code>.").show();
                return;
            }
        } catch (e) {}
        err(m);
    }
    function estado(activo) {
        return String(activo) === "1" ? "<span class='label label-success'>Activa</span>" : "<span class='label label-default'>Inactiva</span>";
    }
    var TIPOS_DOC = { "0": "Sin documento", "1": "DNI", "4": "C. Extra.", "6": "RUC", "7": "Pasaporte", "A": "C. Diplom.", "B": "Doc. país residencia", "C": "Tax ID", "D": "Identification Number", "E": "Tarjeta Andina" };

    /* =====================================================
       CONFIGURACIÓN
    ===================================================== */
    function cargarConfig() {
        $.getJSON(URL, { accion: "config" }, function (r) {
            var c = r.config;
            var series = $.map(c.series_fx || [], function (s) { return s.serie + " (últ. " + s.correlativo + ")"; }).join(", ");
            $("#expCfgSerie").html(c.serie ? "<b>" + esc(c.serie) + "</b>" + (series ? " <small class='text-muted'>· FX registradas: " + esc(series) + "</small>" : "") : "<span class='text-danger'>Sin definir</span>");
            $("#expCfgLista").text(c.lista);
            listaExp = c.lista;
            cargarClientes();
            $("#expCfgAlmacen").text(c.almacen);
            $("#expCfgMoneda").text(c.moneda);
            if (c.avisos && c.avisos.length) {
                $("#expCfgAvisos").html(esc(c.avisos.join(" "))).show();
            }
        }).fail(ajaxErr);
    }

    /* =====================================================
       CLIENTES DEL EXTERIOR (solo consulta)
    ===================================================== */
    var tablaClientes, paises = {}, listaExp = "precio1";
    function iniciarClientes() {
        tablaClientes = $("#expTablaClientes").DataTable({
            order: [[1, "asc"]],
            autoWidth: false,
            language: { emptyTable: "Aún no hay clientes con país del exterior", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ cliente(s)", paginate: { next: "Sig.", previous: "Ant." } },
            columns: [
                { data: "codigo", className: "exp-nowrap", render: function (v) { return esc(v); } },
                { data: "nombre", render: function (v) { return "<b>" + esc(v) + "</b>"; } },
                { data: "pais_iso", className: "exp-nowrap", render: function (v, t) {
                    if (t !== "display") return v + " " + (paises[v] || "");
                    var iso = String(v || "").toLowerCase();
                    var flag = /^[a-z]{2}$/.test(iso) ? "<img class='exp-bandera' src='https://flagcdn.com/w20/" + iso + ".png' alt='' onerror=\"this.style.display='none'\">" : "";
                    return flag + "<b>" + esc(v) + "</b> " + esc(paises[v] || "");
                } },
                { data: null, render: function (r) { return esc(TIPOS_DOC[r.tipo_documento] || r.tipo_documento) + " " + esc(r.documento); } },
                { data: "direccion", render: function (v) { return esc(v); } },
                { data: "email", render: function (v) { return esc(v); } },
                { data: "lista_precios", className: "text-center exp-nowrap", render: function (v) {
                    return v === listaExp ? esc(v) : "<span class='text-danger' title='Para exportación debe ser " + esc(listaExp) + "'>" + esc(v || "—") + " <i class='fa fa-exclamation-triangle'></i></span>";
                } }
            ]
        });
    }

    function cargarClientes() {
        $.getJSON(URL, { accion: "clientes" }, function (r) {
            paises = r.paises || {};
            tablaClientes.clear().rows.add(r.exterior || []).draw();
            var d = r.sin_pais || [];
            if (!d.length) { $("#expSinPais").hide(); return; }
            $("#expSinPais").html("<b>" + d.length + " cliente(s) ya tienen facturas de exportación y no tienen país asignado:</b> " +
                $.map(d, function (c) { return esc(c.nombre || c.codigo) + " <small>(" + esc(c.codigo) + ", " + c.facturas + " fact.)</small>"; }).join(", ") +
                ". Asignarlo en <a href='clientes'>Clientes</a>.").show();
        }).fail(ajaxErr);
    }

    /* =====================================================
       MODELOS ACTIVOS Y PRECIO LISTA 1
    ===================================================== */
    var tablaModelos, puedeEditarPrecios = false;
    function m2(v) { var n = parseFloat(v); return isNaN(n) ? "" : n.toFixed(2); }

    function iniciarModelos() {
        tablaModelos = $("#expTablaModelos").DataTable({
            order: [[0, "asc"]],
            autoWidth: false,
            pageLength: 25,
            language: { emptyTable: "No hay modelos activos", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ modelo(s)", paginate: { next: "Sig.", previous: "Ant." } },
            columns: [
                { data: "modelo", className: "exp-nowrap", render: function (v) { return "<b>" + esc(v) + "</b>"; } },
                { data: "nombre", render: function (v) { return esc(v); } },
                { data: "marca", render: function (v) { return esc(v); } },
                { data: "linea", render: function (v) { return esc(v); } },
                { data: "tipo", render: function (v) { return esc(v); } },
                { data: "cod_unidad", className: "text-center", render: function (v) { return esc(v); } },
                { data: "precio1", className: "text-right exp-nowrap", render: function (v, t, r) {
                    if (t !== "display") return parseFloat(v) || 0;
                    if (String(r.tiene_precio) !== "1") return "<span class='text-danger' title='Crear el registro de precios en Maestros → Modelos'>Sin registro</span>";
                    if (!puedeEditarPrecios) return esc(m2(v));
                    return "<input type='text' inputmode='decimal' class='form-control input-sm exp-precio' data-modelo='" + esc(r.modelo) + "' data-original='" + esc(m2(v)) + "' value='" + esc(m2(v)) + "'>";
                } }
            ]
        });

        $("#expTablaModelos").on("keydown", ".exp-precio", function (e) {
            if (e.which === 13) { e.preventDefault(); $(this).blur(); }
        });
        $("#expTablaModelos").on("focus", ".exp-precio", function () { $(this).select(); });
        $("#expTablaModelos").on("change", ".exp-precio", function () {
            var $in = $(this);
            var valor = $.trim($in.val()).replace(",", ".");
            if (valor === $in.attr("data-original")) return;
            $in.removeClass("exp-ok exp-error").prop("disabled", true);
            $.post(URL, { accion: "guardar-precio1", modelo: $in.attr("data-modelo"), precio: valor }, function (r) {
                var fila = tablaModelos.row($in.closest("tr"));
                var d = fila.data();
                d.precio1 = r.precio;
                $in.val(r.precio).attr("data-original", r.precio).prop("disabled", false).addClass("exp-ok");
                setTimeout(function () { $in.removeClass("exp-ok"); }, 1500);
            }, "json").fail(function (xhr) {
                $in.prop("disabled", false).addClass("exp-error").val($in.attr("data-original"));
                ajaxErr(xhr);
            });
        });
    }

    function cargarModelos() {
        $.getJSON(URL, { accion: "modelos" }, function (r) {
            puedeEditarPrecios = !!r.puede_editar;
            if (!puedeEditarPrecios) {
                $("#expModelosAyuda").text("Solo modelos activos. Precio de la lista 1 en dólares (USD), sin IGV. Solo lectura: para editar precios se necesita el permiso de precios de modelos.");
            }
            tablaModelos.clear().rows.add(r.datos || []).draw();
        }).fail(ajaxErr);
    }

    /* =====================================================
       PARTIDAS POR LÍNEA
    ===================================================== */
    var tablaPartidas;
    function iniciarPartidas() {
        tablaPartidas = $("#expTablaPartidas").DataTable({
            order: [[0, "asc"]],
            autoWidth: false,
            pageLength: 25,
            language: { emptyTable: "No hay líneas", search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "_TOTAL_ línea(s)", paginate: { next: "Sig.", previous: "Ant." } },
            columns: [
                { data: "linea", render: function (v) { return "<b>" + esc(v) + "</b>"; } },
                { data: "partida", className: "exp-nowrap", render: function (v) { return v ? esc(v) : "<span class='text-muted'>Sin partida</span>"; } },
                { data: "descripcion", render: function (v) { return esc(v); } },
                { data: null, className: "text-center", render: function (r) { return r.partida ? estado(r.activo) : ""; } },
                { data: null, orderable: false, className: "text-center", render: function (r) {
                    return "<button class='btn btn-xs btn-warning expEditarPartida' data-linea='" + esc(r.linea) + "' title='Editar'><i class='fa fa-pencil'></i></button>";
                } }
            ]
        });
        $("#expTablaPartidas").on("click", ".expEditarPartida", function () {
            var l = String($(this).attr("data-linea"));
            var p = $.grep(partidas, function (x) { return String(x.linea) === l; })[0] || { linea: l };
            $("#expPLinea").val(p.linea);
            $("#expPLineaTxt").text(p.linea);
            $("#expPPartida").val(p.partida || "");
            $("#expPDescripcion").val(p.descripcion || "");
            $("#expPActivo").prop("checked", !p.partida || String(p.activo) === "1");
            $("#expModalPartida").modal("show");
        });
    }

    function cargarPartidas() {
        $.getJSON(URL, { accion: "partidas" }, function (r) {
            partidas = r.datos || [];
            tablaPartidas.clear().rows.add(partidas).draw();
        }).fail(ajaxErr);
    }

    $(function () {
        if (!$("#expConfig").length) return;
        iniciarClientes();
        iniciarModelos();
        iniciarPartidas();
        cargarConfig();
        cargarModelos();
        cargarPartidas();
        $("a[href='#expTabModelos'], a[href='#expTabPartidas']").on("shown.bs.tab", function () {
            tablaModelos.columns.adjust();
            tablaPartidas.columns.adjust();
        });

        // Pestaña activa en la URL (#clientes, #modelos, #partidas)
        var TABS = { clientes: "#expTabClientes", modelos: "#expTabModelos", partidas: "#expTabPartidas" };
        $("#expConfig .nav-tabs a[data-toggle='tab']").on("shown.bs.tab", function () {
            var destino = $(this).attr("href");
            for (var k in TABS) {
                if (TABS[k] === destino && window.history && history.replaceState) {
                    history.replaceState(null, "", window.location.pathname + window.location.search + "#" + k);
                }
            }
        });
        var hashInicial = String(window.location.hash || "").replace("#", "");
        if (TABS[hashInicial]) {
            $("#expConfig .nav-tabs a[href='" + TABS[hashInicial] + "']").tab("show");
        }

        $("#expGuardarPartida").on("click", function () {
            $.post(URL, {
                accion: "guardar-partida",
                linea: $("#expPLinea").val(),
                partida: $.trim($("#expPPartida").val()),
                descripcion: $.trim($("#expPDescripcion").val()),
                activo: $("#expPActivo").is(":checked") ? 1 : 0
            }, function () {
                $("#expModalPartida").modal("hide");
                ok("Partida guardada");
                cargarPartidas();
            }, "json").fail(ajaxErr);
        });
    });
})(jQuery);
