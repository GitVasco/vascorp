$(function () {
    if ($("#panelCuadreVentas").length === 0) {
        return;
    }

    var API = "ajax/cuadre-ventas.ajax.php";
    var verTodas = false;
    var puedeRegistrar = false;
    var puedeValidar = false;
    var puedeProcesar = false;
    var clienteSel = "";
    var usuarioSel = "";
    var borradores = [];
    var pendientesValidar = [];
    var lotesProcesar = [];
    var pagos = [];
    var gruposOrganizar = [];

    function esc(v) {
        return $("<div>").text(v == null ? "" : String(v)).html();
    }

    function money(n) {
        var x = Number(n);
        if (isNaN(x)) {
            return "0.00";
        }
        return x.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function round2(n) {
        return Math.round((Number(n) || 0) * 100) / 100;
    }

    var CATALOGO_MEDIOS = [
        { cod: "80", label: "Efectivo", pide_op: false },
        { cod: "15", label: "Yape", pide_op: true },
        { cod: "05", label: "Depósito", pide_op: true },
        { cod: "17", label: "Tarjeta", pide_op: true },
        { cod: "16", label: "Link de pago", pide_op: true },
        { cod: "14", label: "Culqi", pide_op: true }
    ];

    function medioPorCod(cod) {
        var i;
        var t = String(cod || "");
        if (t === "EFECTIVO") {
            t = "80";
        } else if (t === "YAPE") {
            t = "15";
        } else if (t === "ABONO_OP" || t === "DEPOSITO") {
            t = "05";
        } else if (t === "TARJETA") {
            t = "17";
        } else if (t === "LINK") {
            t = "16";
        } else if (t === "CULQI" || t === "CULQUI") {
            t = "14";
        }
        for (i = 0; i < CATALOGO_MEDIOS.length; i++) {
            if (CATALOGO_MEDIOS[i].cod === t) {
                return CATALOGO_MEDIOS[i];
            }
        }
        return null;
    }

    function etiquetaMedio(tipo) {
        var m = medioPorCod(tipo);
        if (m) {
            return m.label;
        }
        return tipo || "—";
    }

    function etiquetaTipo(tipo) {
        if (tipo === "01") {
            return "Factura";
        }
        if (tipo === "03") {
            return "Boleta";
        }
        return tipo || "—";
    }

    function hoyLocal() {
        var d = new Date();
        var m = ("0" + (d.getMonth() + 1)).slice(-2);
        var dia = ("0" + d.getDate()).slice(-2);
        return d.getFullYear() + "-" + m + "-" + dia;
    }

    function fechaValida(valor) {
        return /^\d{4}-\d{2}-\d{2}$/.test(valor || "");
    }

    function leerParamUrl(clave) {
        var query = window.location.search.replace(/^\?/, "");
        if (!query) {
            return "";
        }
        var partes = query.split("&");
        for (var i = 0; i < partes.length; i++) {
            if (!partes[i]) {
                continue;
            }
            var par = partes[i].split("=");
            var k = decodeURIComponent(par[0] || "");
            if (k === clave) {
                return decodeURIComponent((par[1] || "").replace(/\+/g, " "));
            }
        }
        return "";
    }

    function leerFechaUrl() {
        return leerParamUrl("fecha");
    }

    function pestanaValida(valor) {
        return valor === "documentos" || valor === "validar" || valor === "procesar";
    }

    function escribirUrl(valores) {
        var query = window.location.search.replace(/^\?/, "");
        var partes = [];
        if (query) {
            var crudos = query.split("&");
            for (var i = 0; i < crudos.length; i++) {
                if (!crudos[i]) {
                    continue;
                }
                var par = crudos[i].split("=");
                var clave = decodeURIComponent(par[0] || "");
                if (clave === "fecha" || clave === "pestana") {
                    continue;
                }
                partes.push(crudos[i]);
            }
        }
        var fecha = valores && valores.hasOwnProperty("fecha") ? valores.fecha : leerParamUrl("fecha");
        var pestana = valores && valores.hasOwnProperty("pestana") ? valores.pestana : leerParamUrl("pestana");
        if (fechaValida(fecha)) {
            partes.push("fecha=" + encodeURIComponent(fecha));
        }
        if (pestanaValida(pestana)) {
            partes.push("pestana=" + encodeURIComponent(pestana));
        }
        var path = window.location.pathname;
        var nueva = path + (partes.length ? "?" + partes.join("&") : "") + window.location.hash;
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, "", nueva);
        }
    }

    function escribirFechaUrl(fecha) {
        escribirUrl({ fecha: fecha });
    }

    function pestanaDesdeHref(href) {
        if (href === "#cvTabValidar") {
            return "validar";
        }
        if (href === "#cvTabProcesar") {
            return "procesar";
        }
        return "documentos";
    }

    function aplicarPestanaUrl() {
        var p = leerParamUrl("pestana");
        if (p === "validar" && puedeValidar) {
            irAPestana("cvTabValidar");
            return;
        }
        if (p === "procesar" && puedeProcesar) {
            irAPestana("cvTabProcesar");
            return;
        }
        irAPestana("cvTabDocs");
    }

    function aviso(tipo, titulo, msg) {
        if (window.swal) {
            swal({ type: tipo || "warning", title: titulo || "Cuadre", text: msg });
            return;
        }
        alert(msg);
    }

    function cols() {
        return verTodas ? 10 : 8;
    }

    function filaVacia(texto) {
        return '<tr class="cv-vacio"><td colspan="' + cols() + '" class="text-muted text-center">'
            + esc(texto) + "</td></tr>";
    }

    function aplicarModo(todas) {
        verTodas = !!todas;
        $(".cv-col-usuario, .cv-col-vendedor").toggle(verTodas);
        $("#cvTablaVentas").toggleClass("cv-con-usuario", verTodas);
        if (verTodas) {
            $("#cvAyuda").html(
                "Pendientes de vendedores <strong>08</strong>. Marca un solo cliente. Puedes aplicar menos que el saldo."
            );
        } else {
            $("#cvAyuda").html(
                "Tus pendientes (vendedores 08). Marca un solo cliente. Puedes aplicar menos que el saldo."
            );
        }
    }

    function pintarTotales(totales) {
        var t = totales || {};
        $("#cvTotCantidad").text(t.cantidad != null ? t.cantidad : 0);
        $("#cvTotMonto").text(money(t.monto));
        $("#cvTotSaldo").text(money(t.saldo));
    }

    function filasMarcadas() {
        return $("#cvTablaVentas tbody tr").has(".cv-check:checked");
    }

    function recalcularLote() {
        var $filas = filasMarcadas();
        var n = $filas.length;
        var total = 0;
        var nombre = "—";
        $filas.each(function () {
            var $tr = $(this);
            var aplicar = parseFloat($tr.find(".cv-aplicar").val());
            if (!isNaN(aplicar)) {
                total += aplicar;
            }
            if (nombre === "—") {
                nombre = $tr.attr("data-cliente-label") || $tr.attr("data-cliente") || "—";
            }
        });
        clienteSel = n ? ($filas.first().attr("data-cliente") || "") : "";
        usuarioSel = n ? ($filas.first().attr("data-usuario") || "") : "";
        $("#cvLoteCliente").text(n ? nombre : "—");
        $("#cvLoteN").text(n);
        $("#cvLoteTotal").text(money(total));
        $("#cvBtnGuardar").prop("disabled", !puedeRegistrar || n < 1);
        recalcularPagos();
    }

    function limpiarSeleccion() {
        $("#cvTablaVentas .cv-check").prop("checked", false);
        $("#cvTablaVentas .cv-aplicar").val("").prop("disabled", true);
        $("#cvTablaVentas tr").removeClass("cv-fila-sel");
        clienteSel = "";
        usuarioSel = "";
        gruposOrganizar = [];
        $("#cvGrupos").empty();
        pagos = [];
        pintarPagos();
        recalcularLote();
    }

    function aplicarDocs(docs) {
        limpiarSeleccion();
        if (!docs || !docs.length) {
            return;
        }
        var mapa = {};
        for (var i = 0; i < docs.length; i++) {
            mapa[String(docs[i].id)] = docs[i].monto_aplicar;
        }
        $("#cvTablaVentas tbody tr").each(function () {
            var $tr = $(this);
            var id = $tr.attr("data-id");
            if (!mapa.hasOwnProperty(id) || $tr.hasClass("cv-fila-bloqueada")) {
                return;
            }
            var saldo = parseFloat($tr.attr("data-saldo")) || 0;
            var aplicar = mapa[id];
            if (aplicar == null || isNaN(aplicar)) {
                aplicar = saldo;
            }
            $tr.find(".cv-check").prop("checked", true);
            $tr.find(".cv-aplicar").prop("disabled", false).val(Number(aplicar).toFixed(2));
            $tr.addClass("cv-fila-sel");
        });
        recalcularLote();
    }

    function pintarBorradores(lista) {
        borradores = lista || [];
        var $box = $("#cvBorradores");
        $box.empty();
        if (!borradores.length) {
            return;
        }
        $box.append('<span class="text-muted">Borradores: </span>');
        for (var i = 0; i < borradores.length; i++) {
            var b = borradores[i];
            var etiqueta = b.cliente + (b.cliente_nombre ? " — " + b.cliente_nombre : "");
            $box.append(
                '<button type="button" class="btn btn-xs btn-info cv-chip-borrador" data-idx="' + i + '">'
                + esc(etiqueta) + " (" + (b.n_docs || 0) + ")"
                + "</button> "
            );
        }
    }

    function pintarRegistrados(lista) {
        var $box = $("#cvRegistrados");
        $box.empty();
        if (puedeValidar || !lista || !lista.length) {
            return;
        }
        $box.append('<span class="text-muted">Registrados (pendientes de validar): </span>');
        for (var i = 0; i < lista.length; i++) {
            var r = lista[i];
            var etiqueta = r.cliente + (r.cliente_nombre ? " — " + r.cliente_nombre : "");
            $box.append(
                '<button type="button" class="btn btn-xs btn-warning cv-chip-anular" data-id="'
                + esc(r.id) + '" title="Cancelar este cuadre">'
                + esc(etiqueta) + " · " + money(r.total_docs)
                + ' ×</button> '
            );
        }
    }

    function resumenPagos(medios) {
        if (!medios || !medios.length) {
            return "—";
        }
        var partes = [];
        for (var i = 0; i < medios.length; i++) {
            var m = medios[i];
            var t = etiquetaMedio(m.tipo_medio);
            if (m.num_ope) {
                t += " " + m.num_ope;
            }
            partes.push(t + " " + money(m.monto));
        }
        return partes.join(" + ");
    }

    function etiquetaPago(m) {
        if (!m) {
            return "Sin medio";
        }
        var t = etiquetaMedio(m.tipo_medio);
        if (m.num_ope) {
            t += " " + m.num_ope;
        }
        return t;
    }

    function asignarDocsAMedios(docs, medios) {
        var items = [];
        var pagosList = [];
        var i;
        var t;
        for (i = 0; i < (docs || []).length; i++) {
            items.push({
                idx: i,
                doc: docs[i],
                cents: toCents(docs[i].monto_aplicar),
                restante: toCents(docs[i].monto_aplicar)
            });
        }
        for (i = 0; i < (medios || []).length; i++) {
            pagosList.push({
                medio: medios[i],
                cents: toCents(medios[i].monto),
                restante: toCents(medios[i].monto)
            });
        }
        pagosList.sort(function (a, b) {
            var aOp = (a.medio && (a.medio.num_ope || a.medio.id_abono)) ? 1 : 0;
            var bOp = (b.medio && (b.medio.num_ope || b.medio.id_abono)) ? 1 : 0;
            if (aOp !== bOp) {
                return bOp - aOp;
            }
            return b.cents - a.cents;
        });
        var filas = [];
        var usados = {};
        var disponibles;
        var sub;
        var j;
        var it;
        var use;
        for (t = 0; t < pagosList.length; t++) {
            if (pagosList[t].restante <= 0) {
                continue;
            }
            disponibles = [];
            for (i = 0; i < items.length; i++) {
                if (!usados[items[i].idx] && items[i].restante === items[i].cents) {
                    disponibles.push(items[i]);
                }
            }
            sub = hallarSubconjunto(disponibles, pagosList[t].restante);
            if (!sub) {
                continue;
            }
            for (j = 0; j < sub.length; j++) {
                it = disponibles[sub[j]];
                usados[it.idx] = true;
                it.restante = 0;
                filas.push({
                    orden: it.idx,
                    doc: it.doc,
                    medio: pagosList[t].medio,
                    monto: round2(it.cents / 100)
                });
            }
            pagosList[t].restante = 0;
        }
        for (i = 0; i < items.length; i++) {
            if (items[i].restante <= 0) {
                continue;
            }
            for (t = 0; t < pagosList.length && items[i].restante > 0; t++) {
                if (pagosList[t].restante <= 0) {
                    continue;
                }
                use = items[i].restante < pagosList[t].restante ? items[i].restante : pagosList[t].restante;
                filas.push({
                    orden: items[i].idx,
                    doc: items[i].doc,
                    medio: pagosList[t].medio,
                    monto: round2(use / 100)
                });
                items[i].restante -= use;
                pagosList[t].restante -= use;
            }
            if (items[i].restante > 0) {
                filas.push({
                    orden: items[i].idx,
                    doc: items[i].doc,
                    medio: null,
                    monto: round2(items[i].restante / 100)
                });
            }
        }
        filas.sort(function (a, b) {
            if (a.orden !== b.orden) {
                return a.orden - b.orden;
            }
            return 0;
        });
        return filas;
    }

    function htmlDetalleLote(lote) {
        var docs = lote.docs || [];
        var medios = lote.medios || [];
        var filas = asignarDocsAMedios(docs, medios);
        var html = '<div class="cv-val-detalle-inner">';
        html += '<p class="cv-val-detalle-titulo">Documento a documento</p>';
        html += '<table class="table table-bordered table-condensed cv-val-cruce">';
        html += "<thead><tr><th>Documento</th><th class=\"text-right\">Monto doc.</th>"
            + "<th>Pagado con</th><th class=\"text-right\">Monto</th></tr></thead><tbody>";
        if (!filas.length) {
            html += '<tr><td colspan="4" class="text-muted">Sin detalle de documentos.</td></tr>';
        }
        var i;
        var f;
        var docTxt;
        var medioTxt;
        var docAnterior = null;
        for (i = 0; i < filas.length; i++) {
            f = filas[i];
            docTxt = etiquetaTipo(f.doc.tipo_doc) + " " + (f.doc.num_cta || "");
            medioTxt = etiquetaPago(f.medio);
            html += "<tr>";
            if (f.doc !== docAnterior) {
                html += "<td>" + esc(docTxt) + '</td><td class="text-right">' + money(f.doc.monto_aplicar) + "</td>";
                docAnterior = f.doc;
            } else {
                html += '<td></td><td></td>';
            }
            html += "<td>" + esc(medioTxt) + '</td><td class="text-right">' + money(f.monto) + "</td></tr>";
        }
        html += "</tbody></table></div>";
        return html;
    }

    function irAPestana(id) {
        var $link = $('#cvNavTabs a[href="#' + id + '"]');
        if ($link.length && $link.is(":visible")) {
            $link.tab("show");
        }
    }

    function estadoLote(l) {
        return String((l && l.estado) || "").toUpperCase();
    }

    function lotesDeEstado(lista, estado) {
        var out = [];
        var i;
        for (i = 0; i < (lista || []).length; i++) {
            if (estadoLote(lista[i]) === estado) {
                out.push(lista[i]);
            }
        }
        return out;
    }

    function htmlEstado(l) {
        var e = estadoLote(l);
        var map = {
            REGISTRADO: { txt: "Por validar", cls: "label-warning" },
            VALIDADO: { txt: "Confirmado", cls: "label-info" },
            PROCESADO: { txt: "Procesado", cls: "label-success" },
            RECHAZADO: { txt: "Rechazado", cls: "label-danger" },
            ANULADO: { txt: "Anulado", cls: "label-default" }
        };
        var m = map[e] || { txt: e || "—", cls: "label-default" };
        var title = "";
        if (l && l.observacion && (e === "RECHAZADO" || e === "ANULADO")) {
            title = ' title="' + esc(l.observacion) + '"';
        }
        return '<span class="label ' + m.cls + ' cv-estado"' + title + ">" + esc(m.txt) + "</span>";
    }

    function htmlFilaLote(l, i, acciones) {
        var nombre = l.cliente + (l.cliente_nombre ? " — " + l.cliente_nombre : "");
        var quien = l.usuario_registro_nombre || ("ID " + l.usuario_registro);
        var hist = !acciones;
        return '<tr class="cv-val-fila' + (hist ? " cv-val-hist" : "") + '" data-idx="' + i + '">'
            + '<td class="cv-col-toggle"><button type="button" class="btn btn-default btn-xs cv-val-toggle" title="Ver documentos">'
            + '<i class="fa fa-plus"></i></button></td>'
            + "<td>" + esc(nombre) + "</td>"
            + "<td>" + esc(quien) + "</td>"
            + '<td class="text-right">' + (l.n_docs || 0) + "</td>"
            + '<td class="text-right">' + money(l.total_docs) + "</td>"
            + "<td>" + esc(resumenPagos(l.medios)) + "</td>"
            + "<td>" + htmlEstado(l) + "</td>"
            + '<td class="cv-val-acciones">' + (acciones || "—") + "</td>"
            + "</tr>"
            + '<tr class="cv-val-detalle" data-idx="' + i + '" style="display:none;"><td colspan="8">'
            + htmlDetalleLote(l) + "</td></tr>";
    }

    function pintarPendientes(lista) {
        pendientesValidar = lista || [];
        var $tb = $("#cvTablaValidar tbody");
        $tb.empty();
        syncPestanas();
        var pendientes = lotesDeEstado(pendientesValidar, "REGISTRADO");
        $("#cvBadgeValidar").text(pendientes.length);
        if (!puedeValidar) {
            pintarSumasEn("#cvSumasMedios", "#cvSumTotal", []);
            return;
        }
        if (!pendientesValidar.length) {
            $tb.append('<tr class="cv-vacio"><td colspan="8" class="text-muted text-center">No hay cuadres en esa fecha.</td></tr>');
            pintarSumasEn("#cvSumasMedios", "#cvSumTotal", []);
            return;
        }
        var i;
        var l;
        var acciones;
        for (i = 0; i < pendientesValidar.length; i++) {
            l = pendientesValidar[i];
            acciones = "";
            if (estadoLote(l) === "REGISTRADO") {
                if (l.es_propio) {
                    acciones = '<button type="button" class="btn btn-warning btn-xs cv-ico cv-val-anular" title="Cancelar">'
                        + '<i class="fa fa-undo"></i></button>';
                }
                if (!l.es_propio || verTodas) {
                    acciones += (acciones ? " " : "")
                        + '<button type="button" class="btn btn-success btn-xs cv-ico cv-val-ok" title="Confirmar">'
                        + '<i class="fa fa-check-circle"></i></button> '
                        + '<button type="button" class="btn btn-danger btn-xs cv-ico cv-val-no" title="Rechazar">'
                        + '<i class="fa fa-times-circle"></i></button>';
                }
            }
            $tb.append(htmlFilaLote(l, i, acciones));
        }
        pintarSumasEn("#cvSumasMedios", "#cvSumTotal", pendientes);
    }

    function syncPestanas() {
        var hayTabs = puedeValidar || puedeProcesar;
        $("#cvNavTabs").toggle(hayTabs);
        $("#cvLiValidar").toggle(!!puedeValidar);
        $("#cvLiProcesar").toggle(!!puedeProcesar);
    }

    function pintarProcesar(lista) {
        lotesProcesar = lista || [];
        var $tb = $("#cvTablaProcesar tbody");
        $tb.empty();
        syncPestanas();
        var pendientes = lotesDeEstado(lotesProcesar, "VALIDADO");
        $("#cvBadgeProcesar").text(pendientes.length);
        if (!puedeProcesar) {
            pintarSumasEn("#cvSumasProcesar", "#cvSumProcesarTotal", []);
            return;
        }
        if (!lotesProcesar.length) {
            $tb.append('<tr class="cv-vacio"><td colspan="8" class="text-muted text-center">No hay cuadres confirmados ni procesados en esa fecha.</td></tr>');
            pintarSumasEn("#cvSumasProcesar", "#cvSumProcesarTotal", []);
            return;
        }
        var i;
        var l;
        var acciones;
        for (i = 0; i < lotesProcesar.length; i++) {
            l = lotesProcesar[i];
            acciones = "";
            if (estadoLote(l) === "VALIDADO") {
                acciones = '<button type="button" class="btn btn-success btn-xs cv-ico cv-proc-cte" title="Procesar a cuentas">'
                    + '<i class="fa fa-arrow-circle-right"></i></button>';
            }
            $tb.append(htmlFilaLote(l, i, acciones));
        }
        pintarSumasEn("#cvSumasProcesar", "#cvSumProcesarTotal", pendientes);
    }

    function pintarSumasEn(selFilas, selTotal, lotes) {
        var sumas = {};
        var i;
        var j;
        var k;
        var medios;
        var tipo;
        var monto;
        var total = 0;
        var html = "";
        for (k = 0; k < CATALOGO_MEDIOS.length; k++) {
            sumas[CATALOGO_MEDIOS[k].cod] = 0;
        }
        for (i = 0; i < (lotes || []).length; i++) {
            medios = lotes[i].medios || [];
            for (j = 0; j < medios.length; j++) {
                tipo = medioPorCod(medios[j].tipo_medio);
                monto = Number(medios[j].monto) || 0;
                if (tipo) {
                    sumas[tipo.cod] += monto;
                }
                total += monto;
            }
        }
        for (k = 0; k < CATALOGO_MEDIOS.length; k++) {
            html += '<div class="cv-suma-fila"><span>'
                + esc(CATALOGO_MEDIOS[k].label)
                + "</span><strong>"
                + money(sumas[CATALOGO_MEDIOS[k].cod])
                + "</strong></div>";
        }
        $(selFilas).html(html);
        $(selTotal).text(money(total));
    }

    function totalDocsLote() {
        var total = 0;
        filasMarcadas().each(function () {
            var aplicar = parseFloat($(this).find(".cv-aplicar").val());
            if (!isNaN(aplicar)) {
                total += aplicar;
            }
        });
        return round2(total);
    }

    function totalPagosLote() {
        var total = 0;
        for (var i = 0; i < pagos.length; i++) {
            total += round2(pagos[i].monto);
        }
        return round2(total);
    }

    function restantePagos() {
        return round2(totalDocsLote() - totalPagosLote());
    }

    function pintarPagos() {
        var $tb = $("#cvTablaPagos tbody");
        $tb.empty();
        if (!pagos.length) {
            $tb.append('<tr class="cv-pagos-vacio"><td colspan="3" class="text-muted text-center">Sin pagos</td></tr>');
            recalcularPagos();
            return;
        }
        for (var i = 0; i < pagos.length; i++) {
            var p = pagos[i];
            var detalle = etiquetaMedio(p.tipo_medio);
            if (p.num_ope) {
                detalle += " " + esc(p.num_ope);
                if (p.id_abono) {
                    detalle += ' <span class="text-muted">(Abonos · ' + money(p.disponible || p.monto) + ")</span>";
                } else {
                    detalle += ' <span class="text-muted">(sin abono)</span>';
                }
            }
            $tb.append(
                '<tr data-idx="' + i + '">'
                + "<td>" + detalle + "</td>"
                + '<td class="text-right"><input type="number" min="0.01" step="0.01" class="form-control input-sm cv-pago-monto"'
                + (p.id_abono ? " readonly" : "")
                + ' value="'
                + round2(p.monto).toFixed(2) + '"></td>'
                + '<td><button type="button" class="btn btn-xs btn-default cv-pago-quitar" title="Quitar">&times;</button></td>'
                + "</tr>"
            );
        }
        recalcularPagos();
    }

    function recalcularPagos() {
        var docs = totalDocsLote();
        var sum = totalPagosLote();
        var dif = round2(docs - sum);
        $("#cvPagoDocs").text(money(docs));
        $("#cvPagoSum").text(money(sum));
        var $dif = $("#cvPagoDif");
        $dif.text(money(dif));
        $dif.toggleClass("cv-dif-ok", Math.abs(dif) < 0.01 && docs > 0 && pagos.length > 0);
        $dif.toggleClass("cv-dif-mal", Math.abs(dif) >= 0.01 || docs < 0.01 || pagos.length < 1);
        $("#cvBtnRegistrar").prop(
            "disabled",
            !puedeRegistrar || docs < 0.01 || pagos.length < 1 || Math.abs(dif) >= 0.01
        );
    }

    function agregarPago(pago) {
        pagos.push(pago);
        pintarPagos();
    }

    function toCents(n) {
        return Math.round(round2(n) * 100);
    }

    function clonarPago(p) {
        return {
            tipo_medio: p.tipo_medio,
            id_abono: p.id_abono,
            num_ope: p.num_ope,
            disponible: p.disponible,
            monto: p.monto
        };
    }

    function hallarSubconjunto(items, targetCents) {
        var best = {};
        best[0] = [];
        var k;
        var key;
        var snapshot;
        var s;
        var prev;
        var next;
        var cand;
        var c;
        for (k = 0; k < items.length; k++) {
            c = items[k].cents;
            snapshot = [];
            for (key in best) {
                if (best.hasOwnProperty(key)) {
                    snapshot.push(parseInt(key, 10));
                }
            }
            for (s = 0; s < snapshot.length; s++) {
                prev = snapshot[s];
                next = prev + c;
                if (next > targetCents) {
                    continue;
                }
                cand = best[prev].concat([k]);
                if (!best[next] || cand.length < best[next].length) {
                    best[next] = cand;
                }
            }
        }
        return best[targetCents] || null;
    }

    function docsDeFilasMarcadas() {
        var out = [];
        filasMarcadas().each(function () {
            var $tr = $(this);
            out.push({
                id: parseInt($tr.attr("data-id"), 10),
                num_cta: $tr.attr("data-num-cta") || $.trim($tr.find(".cv-col-doc").text()),
                tipo_doc: $tr.attr("data-tipo-doc") || "",
                monto_aplicar: parseFloat($tr.find(".cv-aplicar").val()) || 0
            });
        });
        return out;
    }

    function pintarGrupos(grupos, sobra) {
        var $box = $("#cvGrupos");
        $box.empty();
        if (!grupos || !grupos.length) {
            return;
        }
        $box.append("<strong>Grupos que cuadran</strong>");
        var i;
        var j;
        var g;
        var nums;
        for (i = 0; i < grupos.length; i++) {
            g = grupos[i];
            nums = [];
            for (j = 0; j < g.docs.length; j++) {
                nums.push(g.docs[j].num_cta + " (" + money(g.docs[j].monto) + ")");
            }
            $box.append(
                '<div class="cv-grupo">'
                + "<div><strong>" + esc(etiquetaMedio(g.pago.tipo_medio))
                + (g.pago.num_ope ? " " + esc(g.pago.num_ope) : "")
                + "</strong> · " + money(g.pago.monto) + "</div>"
                + '<div class="text-muted">' + esc(nums.join(" + ")) + "</div>"
                + '<button type="button" class="btn btn-xs btn-info cv-grupo-usar" data-idx="' + i + '">Usar este grupo</button>'
                + "</div>"
            );
        }
        if (sobra && sobra.length) {
            nums = [];
            for (i = 0; i < sobra.length; i++) {
                nums.push(sobra[i].num_cta);
            }
            $box.append('<p class="text-muted cv-grupos-sobra">Quedan sin grupo: ' + esc(nums.join(", ")) + "</p>");
        }
    }

    function organizarPorMontos() {
        var docs = docsDeFilasMarcadas();
        if (docs.length < 1) {
            aviso("warning", "Organizar", "Marca los documentos del cliente.");
            return;
        }
        if (pagos.length < 1) {
            aviso("warning", "Organizar", "Agrega los pagos con su monto.");
            return;
        }
        var items = [];
        var i;
        for (i = 0; i < docs.length; i++) {
            items.push({
                id: docs[i].id,
                num_cta: docs[i].num_cta,
                tipo_doc: docs[i].tipo_doc,
                monto: round2(docs[i].monto_aplicar),
                cents: toCents(docs[i].monto_aplicar)
            });
        }
        var targets = [];
        for (i = 0; i < pagos.length; i++) {
            targets.push({
                pago: clonarPago(pagos[i]),
                cents: toCents(pagos[i].monto)
            });
        }
        targets.sort(function (a, b) {
            return b.cents - a.cents;
        });
        var usados = {};
        var grupos = [];
        var t;
        var disponibles;
        var sub;
        var gDocs;
        var j;
        var it;
        for (t = 0; t < targets.length; t++) {
            disponibles = [];
            for (i = 0; i < items.length; i++) {
                if (!usados[items[i].id]) {
                    disponibles.push(items[i]);
                }
            }
            sub = hallarSubconjunto(disponibles, targets[t].cents);
            if (!sub) {
                gruposOrganizar = [];
                $("#cvGrupos").empty();
                aviso(
                    "warning",
                    "Organizar",
                    "No encontré boletas que sumen exacto a "
                        + money(targets[t].pago.monto)
                        + " (" + etiquetaMedio(targets[t].pago.tipo_medio) + ")."
                );
                return;
            }
            gDocs = [];
            for (j = 0; j < sub.length; j++) {
                it = disponibles[sub[j]];
                usados[it.id] = true;
                gDocs.push(it);
            }
            grupos.push({ pago: targets[t].pago, docs: gDocs });
        }
        var sobra = [];
        for (i = 0; i < items.length; i++) {
            if (!usados[items[i].id]) {
                sobra.push(items[i]);
            }
        }
        gruposOrganizar = grupos;
        pintarGrupos(grupos, sobra);
    }

    function usarGrupo(idx) {
        var g = gruposOrganizar[idx];
        if (!g) {
            return;
        }
        var mapa = {};
        var i;
        for (i = 0; i < g.docs.length; i++) {
            mapa[String(g.docs[i].id)] = g.docs[i].monto;
        }
        $("#cvTablaVentas tbody tr").each(function () {
            var $tr = $(this);
            var id = $tr.attr("data-id");
            if ($tr.hasClass("cv-fila-bloqueada")) {
                return;
            }
            if (mapa.hasOwnProperty(id)) {
                $tr.find(".cv-check").prop("checked", true);
                $tr.find(".cv-aplicar").prop("disabled", false).val(Number(mapa[id]).toFixed(2));
                $tr.addClass("cv-fila-sel");
            } else {
                $tr.find(".cv-check").prop("checked", false);
                $tr.find(".cv-aplicar").val("").prop("disabled", true);
                $tr.removeClass("cv-fila-sel");
            }
        });
        pagos = [clonarPago(g.pago)];
        pintarPagos();
        recalcularLote();
    }

    function ordenarDocs(docs) {
        var copia = [];
        var i;
        for (i = 0; i < (docs || []).length; i++) {
            copia.push({ d: docs[i], i: i });
        }
        copia.sort(function (a, b) {
            var ba = a.d.bloqueado ? 1 : 0;
            var bb = b.d.bloqueado ? 1 : 0;
            if (ba !== bb) {
                return ba - bb;
            }
            return a.i - b.i;
        });
        var out = [];
        for (i = 0; i < copia.length; i++) {
            out.push(copia[i].d);
        }
        return out;
    }

    function filtrarClientes() {
        var q = $.trim($("#cvBuscaCliente").val() || "").toLowerCase();
        $("#cvTablaVentas tbody tr").each(function () {
            var $tr = $(this);
            if ($tr.hasClass("cv-vacio")) {
                return;
            }
            if (!q) {
                $tr.show();
                return;
            }
            var txt = (($tr.attr("data-cliente-label") || "") + " " + ($tr.attr("data-cliente") || "")).toLowerCase();
            $tr.toggle(txt.indexOf(q) !== -1);
        });
    }

    function pintarDocs(docs) {
        var $tb = $("#cvTablaVentas tbody");
        $tb.empty();
        clienteSel = "";
        usuarioSel = "";
        if (!docs || !docs.length) {
            $tb.append(filaVacia(
                verTodas
                    ? "No hay pendientes de vendedores 08 en esa fecha."
                    : "No hay pendientes tuyos de vendedores 08 en esa fecha."
            ));
            recalcularLote();
            return;
        }
        docs = ordenarDocs(docs);
        for (var i = 0; i < docs.length; i++) {
            var d = docs[i];
            var labelCliente = d.cliente_nombre
                ? d.cliente + " — " + d.cliente_nombre
                : (d.cliente || "—");
            var clienteHtml = esc(labelCliente);
            var etiquetaUsuario = d.usuario_nombre || d.usuario || "—";
            var colExtra = verTodas
                ? '<td class="cv-col-usuario">' + esc(etiquetaUsuario) + "</td>"
                    + '<td class="cv-col-vendedor">' + esc(d.vendedor || "—") + "</td>"
                : "";
            var saldo = Number(d.saldo) || 0;
            var bloqueado = !!d.bloqueado;
            var checkHtml = bloqueado
                ? '<input type="checkbox" class="cv-check" disabled title="Ya está en otro cuadre">'
                : '<input type="checkbox" class="cv-check">';
            var estadoHtml = bloqueado ? "En cuadre" : esc(d.estado || "—");
            $tb.append(
                '<tr class="' + (bloqueado ? "cv-fila-bloqueada" : "") + '" data-id="' + esc(d.id)
                + '" data-cliente="' + esc(d.cliente)
                + '" data-usuario="' + esc(d.usuario)
                + '" data-saldo="' + saldo
                + '" data-num-cta="' + esc(d.num_cta || "")
                + '" data-tipo-doc="' + esc(d.tipo_doc || "")
                + '" data-cliente-label="' + esc(labelCliente) + '">'
                + '<td class="cv-col-check">' + checkHtml + "</td>"
                + colExtra
                + '<td class="cv-col-tipo">' + esc(etiquetaTipo(d.tipo_doc)) + "</td>"
                + '<td class="cv-col-doc">' + esc(d.num_cta) + "</td>"
                + '<td class="cv-col-cliente">' + clienteHtml + "</td>"
                + '<td class="cv-col-monto text-right">' + money(d.monto) + "</td>"
                + '<td class="cv-col-saldo text-right">' + money(d.saldo) + "</td>"
                + '<td class="cv-col-aplicar"><input type="number" min="0.01" step="0.01" class="form-control input-sm cv-aplicar" disabled></td>'
                + '<td class="cv-col-estado">' + estadoHtml + "</td>"
                + "</tr>"
            );
        }
        recalcularLote();
        filtrarClientes();
    }

    function docsSeleccionados() {
        var out = [];
        filasMarcadas().each(function () {
            var $tr = $(this);
            out.push({
                id: parseInt($tr.attr("data-id"), 10),
                monto_aplicar: parseFloat($tr.find(".cv-aplicar").val())
            });
        });
        return out;
    }

    function buscar() {
        var fecha = $("#cvFecha").val();
        if (!fecha) {
            $("#cvTablaVentas tbody").html(filaVacia("Elige una fecha."));
            pintarTotales({ cantidad: 0, monto: 0, saldo: 0 });
            return;
        }
        escribirFechaUrl(fecha);
        pagos = [];
        gruposOrganizar = [];
        $("#cvGrupos").empty();
        pintarPagos();
        $("#cvTablaVentas tbody").html(filaVacia("Buscando…"));
        $.getJSON(API, { accion: "listar-ventas", fecha: fecha })
            .done(function (r) {
                if (!r || !r.ok) {
                    $("#cvTablaVentas tbody").html(filaVacia((r && r.msg) ? r.msg : "No se pudo listar."));
                    pintarTotales({ cantidad: 0, monto: 0, saldo: 0 });
                    return;
                }
                puedeRegistrar = !!r.puede_registrar;
                puedeValidar = !!r.puede_validar;
                puedeProcesar = !!r.puede_procesar;
                aplicarModo(r.ver_todas);
                pintarDocs(r.docs || []);
                pintarTotales(r.totales);
                pintarBorradores(r.borradores || []);
                pintarRegistrados(r.registrados || []);
                pintarPendientes(r.pendientes_validar || []);
                pintarProcesar(r.validados || []);
                if (r.borrador && r.borrador.docs) {
                    aplicarDocs(r.borrador.docs);
                }
                aplicarPestanaUrl();
            })
            .fail(function () {
                $("#cvTablaVentas tbody").html(filaVacia("Sin permiso o error al listar."));
                pintarTotales({ cantidad: 0, monto: 0, saldo: 0 });
            });
    }

    $("#cvTablaVentas").on("change", ".cv-check", function () {
        var $chk = $(this);
        if ($chk.prop("disabled")) {
            return;
        }
        var $tr = $chk.closest("tr");
        var cli = $tr.attr("data-cliente") || "";
        var usr = $tr.attr("data-usuario") || "";
        if ($chk.prop("checked")) {
            if (clienteSel && cli !== clienteSel) {
                $chk.prop("checked", false);
                aviso("warning", "Un solo cliente", "El lote es de un solo cliente. Limpia o guarda antes de cambiar.");
                return;
            }
            if (usuarioSel && usr !== usuarioSel) {
                $chk.prop("checked", false);
                aviso("warning", "Un solo usuario", "No mezcles ventas registradas por otra persona en el mismo lote.");
                return;
            }
            var saldo = parseFloat($tr.attr("data-saldo")) || 0;
            $tr.find(".cv-aplicar").prop("disabled", false).val(saldo.toFixed(2));
            $tr.addClass("cv-fila-sel");
        } else {
            $tr.find(".cv-aplicar").val("").prop("disabled", true);
            $tr.removeClass("cv-fila-sel");
        }
        recalcularLote();
    });

    $("#cvTablaVentas").on("input change", ".cv-aplicar", function () {
        var $inp = $(this);
        var $tr = $inp.closest("tr");
        var saldo = parseFloat($tr.attr("data-saldo")) || 0;
        var val = parseFloat($inp.val());
        if (isNaN(val) || val <= 0) {
            $inp.val("0.01");
        } else if (val > saldo) {
            $inp.val(saldo.toFixed(2));
        }
        recalcularLote();
    });

    $("#cvBtnLimpiar").on("click", limpiarSeleccion);
    $("#cvBuscaCliente").on("input", filtrarClientes);

    $("#cvBorradores").on("click", ".cv-chip-borrador", function () {
        var idx = parseInt($(this).attr("data-idx"), 10);
        if (!borradores[idx] || !borradores[idx].docs) {
            return;
        }
        aplicarDocs(borradores[idx].docs);
    });

    $("#cvBtnGuardar").on("click", function () {
        var docs = docsSeleccionados();
        if (!docs.length) {
            return;
        }
        var fecha = $("#cvFecha").val();
        $("#cvBtnGuardar").prop("disabled", true);
        $.ajax({
            url: API,
            method: "POST",
            dataType: "json",
            data: {
                accion: "guardar-borrador",
                fecha: fecha,
                docs: JSON.stringify(docs)
            }
        }).done(function (r) {
            if (!r || !r.ok) {
                aviso("error", "No se guardó", (r && r.msg) ? r.msg : "Error al guardar.");
                recalcularLote();
                return;
            }
            aviso("success", "Borrador", r.msg || "Guardado.");
            recalcularLote();
        }).fail(function (xhr) {
            var r = xhr.responseJSON;
            aviso("error", "No se guardó", (r && r.msg) ? r.msg : "Error al guardar.");
            recalcularLote();
        });
    });

    var opConsulta = null;
    var opTimer = null;
    var opBusquedaN = 0;

    function opYaEnLote(ope, idAbono) {
        var i;
        var p;
        for (i = 0; i < pagos.length; i++) {
            p = pagos[i];
            if (ope && p.num_ope && String(p.num_ope) === String(ope)) {
                return true;
            }
            if (idAbono && Number(p.id_abono) === Number(idAbono)) {
                return true;
            }
        }
        return false;
    }

    function montoDesdeAbono(abono) {
        return round2(Number(abono.monto) || 0);
    }

    function bloquearMontoSiAbono() {
        $("#cvPagoMontoNuevo").prop("readonly", !!(opConsulta && opConsulta.abono));
    }

    function consultarOp(ope) {
        return $.getJSON(API, { accion: "buscar-op", ope: ope });
    }

    function medioForm() {
        return medioPorCod($("#cvMedio").val());
    }

    function montoForm() {
        var val = parseFloat($("#cvPagoMontoNuevo").val());
        if (isNaN(val) || val <= 0) {
            return 0.01;
        }
        return round2(val);
    }

    function pintarEstadoOpe(texto, cls) {
        var $el = $("#cvOpeEstado");
        $el.removeClass("ok warn err");
        if (cls) {
            $el.addClass(cls);
        }
        $el.text(texto || "");
    }

    function pintarListaOpe(abonos) {
        var $box = $("#cvOpeLista");
        var i;
        var a;
        var fecha;
        $box.empty();
        if (!abonos || abonos.length < 2) {
            return;
        }
        for (i = 0; i < abonos.length; i++) {
            a = abonos[i];
            fecha = a.fecha ? String(a.fecha).substring(0, 10) : "";
            $box.append(
                '<button type="button" class="cv-ope-item" data-idx="' + i + '">'
                + esc(a.num_ope) + " · " + money(a.monto)
                + (fecha ? ' <span class="text-muted">' + esc(fecha) + "</span>" : "")
                + "</button>"
            );
        }
    }

    function elegirAbono(abono, opeDigitada) {
        opConsulta = {
            ope: opeDigitada || $.trim($("#cvOpe").val()),
            encontrado: true,
            abono: abono,
            abonos: opConsulta && opConsulta.abonos ? opConsulta.abonos : [abono]
        };
        pintarEstadoOpe("Elegida · " + abono.num_ope + " · " + money(abono.monto), "ok");
        $("#cvPagoMontoNuevo").val(montoDesdeAbono(abono).toFixed(2));
        bloquearMontoSiAbono();
        $("#cvOpeLista .cv-ope-item").removeClass("active");
        $("#cvOpeLista .cv-ope-item[data-idx]").each(function () {
            var idx = parseInt($(this).attr("data-idx"), 10);
            var lista = opConsulta.abonos || [];
            if (lista[idx] && Number(lista[idx].id) === Number(abono.id)) {
                $(this).addClass("active");
            }
        });
    }

    function sugerirMontoForm() {
        var sug = restantePagos();
        if (sug < 0.01) {
            sug = 0.01;
        }
        if (opConsulta && opConsulta.abono) {
            sug = montoDesdeAbono(opConsulta.abono);
        }
        $("#cvPagoMontoNuevo").val(round2(sug).toFixed(2));
        bloquearMontoSiAbono();
    }

    function resetFormaPago() {
        $("#cvOpe").val("");
        opConsulta = null;
        pintarEstadoOpe("");
        pintarListaOpe([]);
        sugerirMontoForm();
    }

    function syncFormaPago(opts) {
        var meta = medioForm();
        var pide = !!(meta && meta.pide_op);
        $("#cvPagoOpWrap").toggle(pide);
        $("#cvPagoFila").toggleClass("cv-sin-op", !pide);
        if (!pide) {
            $("#cvOpe").val("");
            opConsulta = null;
            pintarEstadoOpe("");
            pintarListaOpe([]);
        }
        sugerirMontoForm();
        if (opts && opts.silent) {
            return;
        }
        if (pide) {
            $("#cvOpe").focus();
        } else {
            $("#cvPagoMontoNuevo").focus();
        }
    }

    function aplicarConsultaOp(r, ope) {
        pintarListaOpe([]);
        if (!r || !r.ok) {
            opConsulta = null;
            pintarEstadoOpe((r && r.msg) ? r.msg : "No se pudo buscar la OP.", "err");
            bloquearMontoSiAbono();
            return;
        }
        if (r.multiple && r.abonos && r.abonos.length > 1) {
            opConsulta = { ope: ope, encontrado: true, abono: null, abonos: r.abonos };
            pintarEstadoOpe("Varias OP con esos dígitos. Elige una.", "warn");
            pintarListaOpe(r.abonos);
            bloquearMontoSiAbono();
            return;
        }
        if (r.encontrado && r.abono) {
            opConsulta = { ope: ope, encontrado: true, abono: r.abono };
            pintarEstadoOpe("En Abonos · " + r.abono.num_ope + " · " + money(r.abono.monto), "ok");
            $("#cvPagoMontoNuevo").val(montoDesdeAbono(r.abono).toFixed(2));
            bloquearMontoSiAbono();
            return;
        }
        opConsulta = { ope: ope, encontrado: false, abono: null };
        pintarEstadoOpe("No está en Abonos. Se registra igual.", "warn");
        sugerirMontoForm();
    }

    function buscarOpSilencio() {
        var ope = $.trim($("#cvOpe").val());
        var n;
        if (!ope) {
            opConsulta = null;
            pintarEstadoOpe("");
            pintarListaOpe([]);
            sugerirMontoForm();
            return;
        }
        n = ++opBusquedaN;
        pintarEstadoOpe("Buscando…");
        consultarOp(ope)
            .done(function (r) {
                if (n !== opBusquedaN) {
                    return;
                }
                aplicarConsultaOp(r, ope);
            })
            .fail(function (xhr) {
                if (n !== opBusquedaN) {
                    return;
                }
                aplicarConsultaOp(xhr.responseJSON, ope);
            });
    }

    function programarBuscarOp() {
        if (opTimer) {
            clearTimeout(opTimer);
        }
        opTimer = setTimeout(buscarOpSilencio, 350);
    }

    function agregarCaja(cod, extra) {
        var pago = {
            tipo_medio: cod,
            monto: montoForm()
        };
        if (extra) {
            if (extra.num_ope) {
                pago.num_ope = extra.num_ope;
            }
            if (extra.id_abono) {
                pago.id_abono = extra.id_abono;
            }
            if (extra.disponible) {
                pago.disponible = extra.disponible;
                pago.monto = round2(extra.disponible);
            }
            if (extra.monto && !extra.id_abono) {
                pago.monto = round2(extra.monto);
            }
        }
        if (pago.monto <= 0) {
            pago.monto = 0.01;
        }
        agregarPago(pago);
        resetFormaPago();
    }

    function agregarPagoActual() {
        var meta = medioForm();
        var ope;
        var extra;
        if (!meta) {
            return;
        }
        if (!meta.pide_op) {
            agregarCaja(meta.cod);
            return;
        }
        ope = $.trim($("#cvOpe").val());
        if (!ope) {
            agregarCaja(meta.cod);
            return;
        }
        if (opYaEnLote(ope, 0)) {
            aviso("warning", "OP", "Esa OP ya está en este lote.");
            return;
        }
        extra = { num_ope: ope };
        if (opConsulta && opConsulta.ope === ope && opConsulta.abono) {
            if (opYaEnLote(opConsulta.abono.num_ope, opConsulta.abono.id)) {
                aviso("warning", "OP", "Esa OP ya está en este lote.");
                return;
            }
            extra.id_abono = opConsulta.abono.id;
            extra.num_ope = opConsulta.abono.num_ope;
            extra.disponible = Number(opConsulta.abono.monto) || 0;
        }
        if (extra.id_abono) {
            agregarCaja(meta.cod, extra);
            return;
        }
        if (opConsulta && opConsulta.ope === ope && opConsulta.abonos && opConsulta.abonos.length > 1 && !opConsulta.abono) {
            aviso("warning", "OP", "Hay varias coincidencias. Elige una de la lista.");
            return;
        }
        $("#cvBtnAgregarPago").prop("disabled", true);
        consultarOp(ope)
            .done(function (r) {
                $("#cvBtnAgregarPago").prop("disabled", false);
                aplicarConsultaOp(r, ope);
                if (!r || !r.ok) {
                    aviso("error", "OP", (r && r.msg) ? r.msg : "No se pudo buscar la OP.");
                    return;
                }
                if (r.multiple && !r.abono) {
                    aviso("warning", "OP", "Hay varias coincidencias. Elige una de la lista.");
                    return;
                }
                extra = { num_ope: ope };
                if (r.encontrado && r.abono) {
                    if (opYaEnLote(r.abono.num_ope, r.abono.id)) {
                        aviso("warning", "OP", "Esa OP ya está en este lote.");
                        return;
                    }
                    extra.id_abono = r.abono.id;
                    extra.num_ope = r.abono.num_ope;
                    extra.disponible = Number(r.abono.monto) || 0;
                }
                agregarCaja(meta.cod, extra);
            })
            .fail(function (xhr) {
                $("#cvBtnAgregarPago").prop("disabled", false);
                var r = xhr.responseJSON;
                aviso("error", "OP", (r && r.msg) ? r.msg : "No se pudo buscar la OP.");
            });
    }

    $("#cvOpeLista").on("click", ".cv-ope-item", function () {
        var idx = parseInt($(this).attr("data-idx"), 10);
        var lista = opConsulta && opConsulta.abonos ? opConsulta.abonos : [];
        if (!lista[idx]) {
            return;
        }
        elegirAbono(lista[idx], opConsulta.ope);
    });
    $("#cvMedio").on("changed.bs.select change", syncFormaPago);
    $("#cvOpe").on("input", programarBuscarOp);
    $("#cvOpe").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            if (opTimer) {
                clearTimeout(opTimer);
            }
            buscarOpSilencio();
        }
    });
    $("#cvPagoMontoNuevo").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            agregarPagoActual();
        }
    });
    $("#cvBtnAgregarPago").on("click", agregarPagoActual);
    $("#cvBtnOrganizar").on("click", organizarPorMontos);
    $("#cvGrupos").on("click", ".cv-grupo-usar", function () {
        usarGrupo(parseInt($(this).attr("data-idx"), 10));
    });

    $("#cvTablaPagos").on("input change", ".cv-pago-monto", function () {
        var $inp = $(this);
        var idx = parseInt($inp.closest("tr").attr("data-idx"), 10);
        var val = parseFloat($inp.val());
        if (isNaN(val) || val <= 0) {
            val = 0.01;
            $inp.val("0.01");
        }
        if (pagos[idx]) {
            if (pagos[idx].id_abono) {
                val = round2(pagos[idx].disponible || pagos[idx].monto);
                $inp.val(val.toFixed(2));
            } else if (pagos[idx].disponible && val > pagos[idx].disponible) {
                val = pagos[idx].disponible;
                $inp.val(round2(val).toFixed(2));
            }
            pagos[idx].monto = round2(val);
        }
        recalcularPagos();
    });

    $("#cvTablaPagos").on("click", ".cv-pago-quitar", function () {
        var idx = parseInt($(this).closest("tr").attr("data-idx"), 10);
        if (idx >= 0) {
            pagos.splice(idx, 1);
            pintarPagos();
        }
    });

    $("#cvBtnRegistrar").on("click", function () {
        var docs = docsSeleccionados();
        if (!docs.length || !pagos.length) {
            return;
        }
        if (Math.abs(restantePagos()) >= 0.01) {
            aviso(
                "warning",
                "No cuadra",
                "El total de los abonos/pagos tiene que ser igual al total de las boletas."
            );
            return;
        }
        var i;
        for (i = 0; i < pagos.length; i++) {
            if (pagos[i].id_abono && pagos[i].disponible
                    && Math.abs(round2(pagos[i].monto) - round2(pagos[i].disponible)) >= 0.01) {
                aviso(
                    "warning",
                    "Abono incompleto",
                    "La OP " + (pagos[i].num_ope || "") + " es de "
                        + money(pagos[i].disponible)
                        + ". Usala completa para que cuadre con las boletas."
                );
                return;
            }
        }
        var payload = [];
        for (var i = 0; i < pagos.length; i++) {
            payload.push({
                tipo_medio: pagos[i].tipo_medio,
                id_abono: pagos[i].id_abono || 0,
                num_ope: pagos[i].num_ope || "",
                monto: round2(pagos[i].monto)
            });
        }
        $("#cvBtnRegistrar").prop("disabled", true);
        $.ajax({
            url: API,
            method: "POST",
            dataType: "json",
            data: {
                accion: "registrar-pagos",
                fecha: $("#cvFecha").val(),
                docs: JSON.stringify(docs),
                pagos: JSON.stringify(payload)
            }
        }).done(function (r) {
            if (!r || !r.ok) {
                aviso("error", "No se registró", (r && r.msg) ? r.msg : "Error al registrar.");
                recalcularPagos();
                return;
            }
            aviso("success", "Registrado", r.msg || "Cuadre registrado.");
            pagos = [];
            escribirUrl({ pestana: "validar" });
            buscar();
        }).fail(function (xhr) {
            var r = xhr.responseJSON;
            aviso("error", "No se registró", (r && r.msg) ? r.msg : "Error al registrar.");
            recalcularPagos();
        });
    });

    function lotePorFila($el) {
        var $tr = $el.closest("tr");
        var idx = parseInt($tr.attr("data-idx"), 10);
        if ($tr.closest("table").attr("id") === "cvTablaProcesar") {
            return lotesProcesar[idx] || null;
        }
        return pendientesValidar[idx] || null;
    }

    function setDetalleAbierto($fila, abierto) {
        var idx = $fila.attr("data-idx");
        var $tabla = $fila.closest("table");
        var $det = $tabla.find(".cv-val-detalle[data-idx='" + idx + "']");
        var $icono = $fila.find(".cv-val-toggle i");
        var $btn = $fila.find(".cv-val-toggle");
        if (abierto) {
            $det.show();
            $fila.addClass("cv-val-abierta");
            $icono.removeClass("fa-plus").addClass("fa-minus");
            $btn.attr("title", "Ocultar documentos");
        } else {
            $det.hide();
            $fila.removeClass("cv-val-abierta");
            $icono.removeClass("fa-minus").addClass("fa-plus");
            $btn.attr("title", "Ver documentos");
        }
    }

    $("#cvTablaValidar, #cvTablaProcesar").on("click", ".cv-val-toggle", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $fila = $(this).closest("tr");
        setDetalleAbierto($fila, !$fila.hasClass("cv-val-abierta"));
    });

    $("#cvTablaValidar, #cvTablaProcesar").on("click", ".cv-val-fila", function (e) {
        if ($(e.target).closest("button, a, input").length) {
            return;
        }
        var $fila = $(this);
        setDetalleAbierto($fila, !$fila.hasClass("cv-val-abierta"));
    });

    function pedirAnular(id) {
        id = parseInt(id, 10);
        if (!id) {
            return;
        }
        var hacer = function () {
            $.ajax({
                url: API,
                method: "POST",
                dataType: "json",
                data: { accion: "anular-cuadre", id: id }
            }).done(function (r) {
                if (!r || !r.ok) {
                    aviso("error", "No se canceló", (r && r.msg) ? r.msg : "Error al cancelar.");
                    return;
                }
                aviso("success", "Cancelado", r.msg || "Cuadre cancelado.");
                buscar();
            }).fail(function (xhr) {
                var r = xhr.responseJSON;
                aviso("error", "No se canceló", (r && r.msg) ? r.msg : "Error al cancelar.");
            });
        };
        if (window.swal) {
            swal({
                title: "¿Cancelar este cuadre?",
                text: "La OP queda libre y puedes armarlo de nuevo. Aún no entra a cuentas.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, cancelar",
                cancelButtonText: "No"
            }).then(function (result) {
                if (result && result.value) {
                    hacer();
                }
            });
            return;
        }
        if (window.confirm("¿Cancelar este cuadre? La OP queda libre.")) {
            hacer();
        }
    }

    $("#cvTablaValidar").on("click", ".cv-val-anular", function () {
        var lote = lotePorFila($(this));
        if (!lote || !lote.es_propio) {
            return;
        }
        pedirAnular(lote.id);
    });

    $("#cvRegistrados").on("click", ".cv-chip-anular", function () {
        pedirAnular($(this).attr("data-id"));
    });

    $("#cvTablaValidar").on("click", ".cv-val-ok", function () {
        var lote = lotePorFila($(this));
        if (!lote || (lote.es_propio && !verTodas)) {
            return;
        }
        var hacer = function () {
            $.ajax({
                url: API,
                method: "POST",
                dataType: "json",
                data: { accion: "validar-cuadre", id: lote.id }
            }).done(function (r) {
                if (!r || !r.ok) {
                    aviso("error", "No se confirmó", (r && r.msg) ? r.msg : "Error al confirmar.");
                    return;
                }
                aviso("success", "Confirmado", r.msg || "Cuadre confirmado.");
                if (puedeProcesar) {
                    escribirUrl({ pestana: "procesar" });
                }
                buscar();
            }).fail(function (xhr) {
                var r = xhr.responseJSON;
                aviso("error", "No se confirmó", (r && r.msg) ? r.msg : "Error al confirmar.");
            });
        };
        if (window.swal) {
            swal({
                title: "¿Confirmar este cuadre?",
                text: "Queda listo. Aún no entra a cuentas.",
                type: "question",
                showCancelButton: true,
                confirmButtonText: "Confirmar",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (result && result.value) {
                    hacer();
                }
            });
            return;
        }
        if (window.confirm("¿Confirmar este cuadre? Aún no entra a cuentas.")) {
            hacer();
        }
    });

    $("#cvTablaProcesar").on("click", ".cv-proc-cte", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var lote = lotePorFila($(this));
        if (!lote) {
            return;
        }
        var $btn = $(this);
        var hacer = function () {
            $btn.prop("disabled", true);
            $.ajax({
                url: API,
                method: "POST",
                dataType: "json",
                data: { accion: "procesar-cuadre", id: lote.id }
            }).done(function (r) {
                $btn.prop("disabled", false);
                if (!r || !r.ok) {
                    aviso("error", "No se procesó", (r && r.msg) ? r.msg : "Error al procesar.");
                    return;
                }
                aviso("success", "Procesado", r.msg || "Ya entra a cuentas.");
                buscar();
            }).fail(function (xhr) {
                $btn.prop("disabled", false);
                var r = xhr.responseJSON;
                aviso("error", "No se procesó", (r && r.msg) ? r.msg : "Error al procesar.");
            });
        };
        if (window.swal) {
            swal({
                title: "¿Procesar a cuenta corriente?",
                text: "Baja el saldo de los documentos y consume la OP. No se puede deshacer desde aquí.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Procesar",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (result && result.value) {
                    hacer();
                }
            });
            return;
        }
        if (window.confirm("¿Procesar a cuenta corriente? Baja el saldo y consume la OP.")) {
            hacer();
        }
    });

    $("#cvBtnExcelValidar").on("click", function () {
        var fecha = $("#cvFecha").val();
        if (!fechaValida(fecha)) {
            aviso("warning", "Excel", "Elige una fecha.");
            return;
        }
        window.location = "vistas/reportes_excel/rpt_cuadre_ventas.php?fecha=" + encodeURIComponent(fecha);
    });

    $("#cvTablaValidar").on("click", ".cv-val-no", function () {
        var lote = lotePorFila($(this));
        if (!lote || (lote.es_propio && !verTodas)) {
            return;
        }
        var rechazar = function (motivo) {
            $.ajax({
                url: API,
                method: "POST",
                dataType: "json",
                data: { accion: "rechazar-cuadre", id: lote.id, motivo: motivo }
            }).done(function (r) {
                if (!r || !r.ok) {
                    aviso("error", "No se rechazó", (r && r.msg) ? r.msg : "Error al rechazar.");
                    return;
                }
                aviso("success", "Rechazado", r.msg || "Cuadre rechazado.");
                buscar();
            }).fail(function (xhr) {
                var r = xhr.responseJSON;
                aviso("error", "No se rechazó", (r && r.msg) ? r.msg : "Error al rechazar.");
            });
        };
        if (window.swal) {
            swal({
                title: "Motivo del rechazo",
                input: "textarea",
                inputPlaceholder: "¿Por qué se rechaza?",
                showCancelButton: true,
                confirmButtonText: "Rechazar",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    if (!value || !$.trim(value)) {
                        return "Indica un motivo";
                    }
                    return null;
                }
            }).then(function (result) {
                if (result && result.value) {
                    rechazar($.trim(result.value));
                }
            });
            return;
        }
        var motivo = window.prompt("Motivo del rechazo:");
        if (motivo && $.trim(motivo)) {
            rechazar($.trim(motivo));
        }
    });

    var fechaUrl = leerFechaUrl();
    $("#cvFecha").val(fechaValida(fechaUrl) ? fechaUrl : hoyLocal());
    $("#cvNavTabs").on("shown.bs.tab", "a[data-toggle='tab']", function (e) {
        escribirUrl({ pestana: pestanaDesdeHref($(e.target).attr("href")) });
    });
    $("#cvBtnBuscar").on("click", buscar);
    $("#cvFecha").on("change", buscar);
    if ($("#cvMedio").length && typeof $("#cvMedio").selectpicker === "function") {
        $("#cvMedio").selectpicker({
            style: "btn-default btn-sm",
            width: "100%",
            container: "body",
            size: 8
        });
    }
    pintarSumasEn("#cvSumasMedios", "#cvSumTotal", []);
    pintarSumasEn("#cvSumasProcesar", "#cvSumProcesarTotal", []);
    syncFormaPago({ silent: true });
    buscar();
});
