var ptsNiveles = [];
var ptsMapaNiveles = {};
var ptsSectores = [];
var ptsProgramadosCache = [];
var ptsDisponiblesCache = [];
var ptsStatsCache = null;
var ptsToastTimer = null;
var ptsUrlSyncLock = false;
var ptsInicializado = false;
var ptsStatsTimer = null;

function toastPts(mensaje, tipo) {
    var $t = $("#toastPts");
    if (!$t.length) {
        $t = $('<div id="toastPts" class="pts-toast" role="status" aria-live="polite"></div>').appendTo("body");
    }
    tipo = tipo || "success";
    if (tipo === "error") tipo = "danger";
    $t.stop(true, true)
        .removeClass("pts-toast-success pts-toast-danger pts-toast-warning pts-toast-info")
        .addClass("pts-toast pts-toast-" + tipo)
        .html(escaparPts(mensaje))
        .fadeIn(120);
    if (ptsToastTimer) clearTimeout(ptsToastTimer);
    ptsToastTimer = setTimeout(function () {
        $t.fadeOut(220);
    }, 2800);
}

function escaparPts(t) {
    return $("<div>").text(t == null ? "" : String(t)).html();
}

function badgeNivelPts(nivelId, nombre, color) {
    var bg = color || "#ddd";
    var txt = nombre || nivelId || "—";
    return '<span class="badge-nivel-pts" style="background:' + escaparPts(bg) + '">' + escaparPts(txt) + "</span>";
}

function anioPts() {
    return parseInt($("#filtroAnioPts").val(), 10) || 0;
}

function semanaPts() {
    return parseInt($("#filtroSemanaPts").val(), 10) || 0;
}

function tabActivaPts() {
    var tab = $("#tabsPts li.active a").attr("data-tab-pts");
    return tab === "programado" ? "programado" : "programar";
}

function leerParamPts(nombre) {
    try {
        return String(new URLSearchParams(window.location.search || "").get(nombre) || "").trim();
    } catch (e) {
        return "";
    }
}

function sincronizarUrlPts() {
    if (ptsUrlSyncLock || !window.history || !window.history.replaceState) {
        return;
    }
    try {
        var params = new URLSearchParams(window.location.search || "");
        // Mantener ruta si el sitio la usa en query
        var anio = anioPts();
        var semana = semanaPts();
        var modelo = $("#filtroModeloPts").val() || "";
        var taller = $("#filtroTallerPts").val() || "";
        var nivel = $("#filtroNivelPts").val() || "";
        var tab = tabActivaPts();
        var ocultar = $("#chkOcultarConsumidosPts").is(":checked") ? "1" : "0";

        if (anio) params.set("anio", String(anio)); else params.delete("anio");
        if (semana) params.set("semana", String(semana)); else params.delete("semana");
        if (modelo) params.set("modelo", modelo); else params.delete("modelo");
        if (taller) params.set("taller", taller); else params.delete("taller");
        if (nivel) params.set("nivel", nivel); else params.delete("nivel");
        params.set("tab", tab);
        params.set("ocultar_consumidos", ocultar);

        var qs = params.toString();
        var url = window.location.pathname + (qs ? "?" + qs : "") + (window.location.hash || "");
        window.history.replaceState(null, "", url);
    } catch (e) {}
}

function aplicarEstadoInicialPts() {
    var $page = $(".pts-full-page");
    if (!$page.length) return;

    ptsUrlSyncLock = true;
    var modelo = $page.attr("data-modelo-inicial") || leerParamPts("modelo");
    var taller = $page.attr("data-taller-inicial") || leerParamPts("taller");
    var nivel = $page.attr("data-nivel-inicial") || leerParamPts("nivel");
    var ocultar = $page.attr("data-ocultar-consumidos");
    if (ocultar === "0") {
        $("#chkOcultarConsumidosPts").prop("checked", false);
    } else if (ocultar === "1") {
        $("#chkOcultarConsumidosPts").prop("checked", true);
    }

    if (modelo) {
        $("#filtroModeloPts").val(modelo);
        refrescarSelectPts($("#filtroModeloPts"));
    }
    if (taller) {
        $("#filtroTallerPts").val(taller);
        refrescarSelectPts($("#filtroTallerPts"));
    }
    if (nivel) {
        $("#filtroNivelPts").val(nivel);
    }
    ptsUrlSyncLock = false;
}

function refrescarSelectPts($select) {
    if (!$select.length || typeof $select.selectpicker !== "function") {
        return;
    }
    try {
        if ($select.data("selectpicker")) {
            $select.selectpicker("refresh");
        } else {
            $select.selectpicker({ liveSearch: true, size: 8 });
        }
    } catch (e) {}
}

function opcionesNivelHtmlPts(incluirVacio, vacioTexto) {
    var html = "";
    if (incluirVacio) {
        html += '<option value="">' + escaparPts(vacioTexto || "— Seleccionar —") + "</option>";
    }
    ptsNiveles.forEach(function (n) {
        html += '<option value="' + escaparPts(n.id) + '">' + escaparPts(n.nombre) + "</option>";
    });
    return html;
}

function botonesNivelHtmlPts() {
    if (!ptsNiveles.length) {
        return '<span class="text-muted">Sin niveles</span>';
    }
    return ptsNiveles.map(function (n) {
        var bg = n.color || "#ddd";
        return '<button type="button" class="btn-nivel-pts btnNivelRapidoPts" data-nivel="'
            + escaparPts(n.id) + '" style="background:' + escaparPts(bg) + '" title="'
            + escaparPts(n.nombre) + '">' + escaparPts(n.nombre) + "</button>";
    }).join("");
}

function cargarNivelesPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "niveles" }, function (resp) {
        ptsNiveles = (resp && resp.ok && resp.data && resp.data.niveles) ? resp.data.niveles : [];
        ptsMapaNiveles = {};
        var $leyenda = $("#leyendaNivelesPts");
        var $filtro = $("#filtroNivelPts");
        var $modalNivel = $("#ptsNivel");
        var $lote = $("#nivelLotePts");
        $filtro.find("option:not(:first)").remove();
        $modalNivel.find("option:not(:first)").remove();
        $lote.html(opcionesNivelHtmlPts(true, "— Elegir nivel —"));
        var chips = ['<strong style="margin-right:4px;">Niveles:</strong>'];
        ptsNiveles.forEach(function (n) {
            ptsMapaNiveles[n.id] = n;
            chips.push(badgeNivelPts(n.id, n.nombre, n.color));
            $filtro.append($("<option>").val(n.id).text(n.nombre));
            $modalNivel.append($("<option>").val(n.id).text(n.nombre));
        });
        $leyenda.html(chips.join(" "));
    }, "json");
}

function cargarModelosPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "listarModelos" }, function (resp) {
        var $filtro = $("#filtroModeloPts");
        $filtro.find("option:not(:first)").remove();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (m) {
                $filtro.append(
                    $("<option>").val(m.modelo).text(m.etiqueta || m.modelo)
                );
            });
        }
        refrescarSelectPts($filtro);
    }, "json");
}

function cargarSectoresPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "listarSectores" }, function (resp) {
        ptsSectores = (resp && resp.ok && resp.data) ? resp.data : [];
        var $filtro = $("#filtroTallerPts");
        var $modal = $("#ptsTaller");
        $filtro.find("option:not(:first)").remove();
        $modal.find("option:not(:first)").remove();
        ptsSectores.forEach(function (s) {
            var txt = s.cod_sector + " — " + (s.nom_sector || "");
            $filtro.append($("<option>").val(s.cod_sector).text(txt));
            $modal.append($("<option>").val(s.cod_sector).text(txt));
        });
        refrescarSelectPts($filtro);
        refrescarSelectPts($modal);
    }, "json");
}

function actualizarRangoPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anioPts(),
        semana: semanaPts()
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#filtroAnioPts").val(resp.data.anio);
            $("#filtroSemanaPts").val(resp.data.semana);
            $("#textoRangoSemanaPts").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
        }
    }, "json");
}

function moverSemanaPts(delta) {
    var anio = anioPts();
    var semana = semanaPts() + delta;
    if (semana < 1) {
        anio -= 1;
        semana = 52;
    } else if (semana > 53) {
        anio += 1;
        semana = 1;
    }
    $("#filtroAnioPts").val(anio);
    $("#filtroSemanaPts").val(semana);
    actualizarRangoPts().always(function () {
        recargarPts();
    });
}

function numPts(v) {
    var n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
}

function formatNumPts(v) {
    var n = numPts(v);
    try {
        return n.toLocaleString("es-PE");
    } catch (e) {
        return String(n);
    }
}

function chipTallerPts(r, opts) {
    opts = opts || {};
    var bg = r.color_taller || opts.bgDefault || "#eee";
    var cod = r.cod_sector || "—";
    var nom = r.nom_sector || "";
    var name = nom ? (cod + " · " + nom) : String(cod);
    var uds = formatNumPts(r.total_cantidad);
    var cols = numPts(opts.coloresKey ? r[opts.coloresKey] : (r.total_colores != null ? r.total_colores : r.total_lineas));
    return '<div class="pts-chip" style="background:' + escaparPts(bg) + '" title="' + escaparPts(name) + '">'
        + '<span class="pts-chip-name">' + escaparPts(name) + '</span>'
        + '<span class="pts-chip-meta">' + escaparPts(uds)
        + ' <small>(' + escaparPts(cols) + ')</small></span>'
        + '</div>';
}

function pintarEstadisticasPts(stats) {
    var $box = $("#estadisticasSemanaPts");
    if (!$box.length) return;

    var t = (stats && stats.totales) ? stats.totales : null;
    var totalLineas = t ? numPts(t.total_lineas) : 0;
    var pp = (stats && stats.por_programar) ? stats.por_programar : null;
    var ppt = (pp && pp.totales) ? pp.totales : null;
    var ppColores = ppt ? numPts(ppt.total_colores) : 0;
    var ppModelos = ppt ? numPts(ppt.total_modelos) : 0;
    var ppCant = ppt ? numPts(ppt.total_cantidad) : 0;
    var ppTaller = (pp && pp.por_taller) ? pp.por_taller : [];

    var html = "";
    html += '<div class="pts-stats-head">'
        + '<h4 class="pts-stats-title">Semana ' + escaparPts(semanaPts()) + ' · ' + escaparPts(anioPts()) + '</h4>'
        + '</div>';
    html += '<div class="pts-stats-grid">';

    // Panel pendiente de programar
    html += '<div class="pts-stats-panel pts-panel-warn">';
    html += '<div class="pts-stats-subtitle">Pendiente de programar</div>';
    html += '<div class="pts-stats-kpis">'
        + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(ppCant)) + '</span><span class="l">Unidades</span></div>'
        + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(ppColores)) + '</span><span class="l">Colores</span></div>'
        + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(ppModelos)) + '</span><span class="l">Modelos</span></div>'
        + '</div>';
    if (ppTaller.length) {
        html += '<div class="pts-chip-head">Por taller sugerido</div><div class="pts-chip-grid">';
        ppTaller.forEach(function (r) {
            html += chipTallerPts(r, { bgDefault: "#f0ad4e", coloresKey: "total_colores" });
        });
        html += '</div>';
    } else {
        html += '<p class="pts-stats-empty">Nada pendiente con saldo en corte/OC.</p>';
    }
    html += '</div>';

    // Panel ya programado
    html += '<div class="pts-stats-panel">';
    html += '<div class="pts-stats-subtitle">Ya programado</div>';
    if (!t || totalLineas < 1) {
        html += '<p class="pts-stats-empty">Sin programación en esta semana.</p>';
    } else {
        html += '<div class="pts-stats-kpis pts-kpis-4">'
            + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(t.total_cantidad)) + '</span><span class="l">Unidades</span></div>'
            + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(totalLineas)) + '</span><span class="l">Colores</span></div>'
            + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(t.pendientes_lineas)) + '</span><span class="l">Con saldo</span></div>'
            + '<div class="pts-kpi"><span class="n">' + escaparPts(formatNumPts(t.consumidos_lineas)) + '</span><span class="l">Consumidos</span></div>'
            + '</div>';

        var porNivel = stats.por_nivel || [];
        if (porNivel.length) {
            html += '<div class="pts-chip-head">Por nivel</div><div class="pts-chip-grid">';
            porNivel.forEach(function (r) {
                var meta = ptsMapaNiveles[r.nivel] || {};
                html += chipTallerPts({
                    cod_sector: meta.nombre || r.nivel,
                    nom_sector: "",
                    color_taller: meta.color || "#ddd",
                    total_cantidad: r.total_cantidad,
                    total_lineas: r.total_lineas
                }, { coloresKey: "total_lineas" });
            });
            html += '</div>';
        }

        var porTaller = stats.por_taller || [];
        if (porTaller.length) {
            html += '<div class="pts-chip-head">Por taller</div><div class="pts-chip-grid">';
            porTaller.forEach(function (r) {
                html += chipTallerPts(r, { bgDefault: "#A8D5E5", coloresKey: "total_lineas" });
            });
            html += '</div>';
        }
    }
    html += '</div>';

    html += '</div>'; // grid
    $box.html(html);
}

function etiquetaColorPts(r) {
    return (r.cod_color || "") + (r.color ? " — " + r.color : "");
}

function colorTallerPts(r) {
    if (r && r.color_taller) {
        return String(r.color_taller);
    }
    var cod = r ? (r.cod_sector || r.cod_sector_resuelto || "") : "";
    if (!cod || !ptsSectores || !ptsSectores.length) {
        return "";
    }
    for (var i = 0; i < ptsSectores.length; i++) {
        if (String(ptsSectores[i].cod_sector) === String(cod) && ptsSectores[i].color) {
            return String(ptsSectores[i].color);
        }
    }
    return "";
}

function celdaTallerHtmlPts(r, texto, extraHtml) {
    var bg = colorTallerPts(r) || "#e8e8e8";
    return '<td class="pts-td-taller" style="background:' + escaparPts(bg) + '">'
        + '<span class="pts-taller-label">' + escaparPts(texto) + "</span>"
        + (extraHtml || "")
        + "</td>";
}

function estiloFilaTallerPts(r) {
    var bg = colorTallerPts(r);
    return bg ? ("border-left:4px solid " + bg) : "";
}

function esConsumidoPts(r) {
    return r && (parseInt(r.consumido, 10) === 1 || parseInt(r.saldo_vivo, 10) <= 0);
}

function badgeEstadoPts(r) {
    if (esConsumidoPts(r)) {
        return '<span class="badge-estado-pts consumido">Consumido</span>';
    }
    return '<span class="badge-estado-pts pendiente">Pendiente</span>';
}

function actualizarContadorSelPts() {
    var n = $("#tablaDisponiblesPts tbody .chkDispPts:checked").length;
    $("#nSelPts").text(n);
    $("#btnProgramarLotePts").prop("disabled", n < 1 || !$("#nivelLotePts").val());
}

function pintarTablaProgramadosPts() {
    var $tb = $("#tablaProgramadoPts tbody").empty();
    var ocultar = $("#chkOcultarConsumidosPts").is(":checked");
    var data = ptsProgramadosCache || [];
    var totalConsumidos = 0;
    data.forEach(function (r) {
        if (esConsumidoPts(r)) totalConsumidos += 1;
    });
    var visibles = ocultar
        ? data.filter(function (r) { return !esConsumidoPts(r); })
        : data;

    $("#conteoConsumidosPts").text(
        data.length
            ? (totalConsumidos + " consumido(s) de " + data.length)
            : ""
    );

    if (!data.length) {
        $tb.html('<tr><td colspan="10" class="text-center text-muted">Nada programado en esta semana</td></tr>');
        return;
    }
    if (!visibles.length) {
        $tb.html('<tr><td colspan="10" class="text-center text-muted">Solo hay líneas consumidas (quita el filtro para verlas)</td></tr>');
        return;
    }

    var prevModelo = null;
    visibles.forEach(function (r) {
        var consumido = esConsumidoPts(r);
        var taller = (r.cod_sector || "") + (r.nom_sector ? " — " + r.nom_sector : "");
        var alm = r.alm_corte_vivo != null ? r.alm_corte_vivo : r.saldo_alm_corte;
        var ord = r.ord_corte_vivo != null ? r.ord_corte_vivo : r.saldo_ord_corte;
        var titleSnap = "Al programar: alm " + (r.saldo_alm_corte || 0) + " / OC " + (r.saldo_ord_corte || 0);
        var acciones = '<button type="button" class="btn btn-xs btn-warning btnEditarPts" data-id="' + r.id + '" title="Editar"><i class="fa fa-pencil"></i></button> '
            + '<button type="button" class="btn btn-xs btn-danger btnEliminarPts" data-id="' + r.id + '" title="Quitar"><i class="fa fa-trash"></i></button>';
        var clases = [];
        if (consumido) clases.push("fila-consumida-pts");
        if (prevModelo !== null && String(r.modelo || "") !== String(prevModelo)) {
            clases.push("pts-sep-modelo");
        }
        prevModelo = r.modelo || "";
        var estiloFila = estiloFilaTallerPts(r);
        var $tr = $(
            "<tr class=\"" + clases.join(" ") + "\""
            + (estiloFila ? ' style="' + estiloFila + '"' : "") + ">"
            + "<td>" + badgeEstadoPts(r) + "</td>"
            + "<td>" + badgeNivelPts(r.nivel, r.nivel_nombre, r.nivel_color) + "</td>"
            + celdaTallerHtmlPts(r, taller)
            + "<td>" + escaparPts(r.modelo) + "</td>"
            + "<td>" + escaparPts(etiquetaColorPts(r)) + "</td>"
            + "<td><strong>" + escaparPts(r.cantidad) + "</strong></td>"
            + "<td title=\"" + escaparPts(titleSnap) + "\">" + escaparPts(alm) + "</td>"
            + "<td title=\"" + escaparPts(titleSnap) + "\">" + escaparPts(ord) + "</td>"
            + "<td>" + escaparPts(r.urg_plan == null ? "—" : r.urg_plan) + "</td>"
            + "<td>" + acciones + "</td>"
            + "</tr>"
        );
        $tr.find(".btnEditarPts").data("row", r);
        $tb.append($tr);
    });
}

function ordenarProgramadosCachePts() {
    ptsProgramadosCache.sort(function (a, b) {
        var sa = String(a.cod_sector || "");
        var sb = String(b.cod_sector || "");
        if (sa.charAt(0) === "T" || sa.charAt(0) === "t") sa = sa.slice(1);
        if (sb.charAt(0) === "T" || sb.charAt(0) === "t") sb = sb.slice(1);
        var cmp = sa.localeCompare(sb, undefined, { numeric: true, sensitivity: "base" });
        if (cmp !== 0) return cmp;
        cmp = String(a.modelo || "").localeCompare(String(b.modelo || ""));
        if (cmp !== 0) return cmp;
        return String(a.cod_color || "").localeCompare(String(b.cod_color || ""));
    });
}

function upsertProgramadoCachePts(row) {
    if (!row || !row.id) return;
    var id = String(row.id);
    var idx = -1;
    for (var i = 0; i < ptsProgramadosCache.length; i++) {
        if (String(ptsProgramadosCache[i].id) === id) {
            idx = i;
            break;
        }
    }
    if (idx >= 0) {
        ptsProgramadosCache[idx] = row;
    } else {
        ptsProgramadosCache.push(row);
    }
    ordenarProgramadosCachePts();
    pintarTablaProgramadosPts();
}

function quitarProgramadoCachePts(id) {
    id = String(id);
    ptsProgramadosCache = ptsProgramadosCache.filter(function (r) {
        return String(r.id) !== id;
    });
    pintarTablaProgramadosPts();
}

function upsertDisponibleCachePts(row) {
    if (!row) return;
    var clave = claveDispPts(row);
    ptsDisponiblesCache = ptsDisponiblesCache.filter(function (x) {
        return claveDispPts(x) !== clave;
    });
    if ((parseInt(row.saldo_disponible, 10) || 0) > 0) {
        ptsDisponiblesCache.push(row);
        ptsDisponiblesCache.sort(function (a, b) {
            var cmp = String(a.modelo || "").localeCompare(String(b.modelo || ""));
            if (cmp !== 0) return cmp;
            return String(a.cod_color || "").localeCompare(String(b.cod_color || ""));
        });
    }
    pintarTablaDisponiblesPts();
}

function cargarEstadisticasPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "estadisticas",
        anio: anioPts(),
        semana: semanaPts()
    }, function (resp) {
        if (resp && resp.ok) {
            ptsStatsCache = resp.estadisticas || null;
            pintarEstadisticasPts(ptsStatsCache || { totales: null, por_nivel: [], por_taller: [], por_programar: null });
        }
    }, "json");
}

/** Recalcula stats pesadas en segundo plano (tras programar/eliminar). */
function programarRefreshStatsPts() {
    if (ptsStatsTimer) clearTimeout(ptsStatsTimer);
    ptsStatsTimer = setTimeout(function () {
        cargarEstadisticasPts();
    }, 400);
}

function cargarProgramadosPts(opts) {
    opts = opts || {};
    var incluirStats = opts.stats !== false;
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "programados",
        anio: anioPts(),
        semana: semanaPts(),
        cod_sector: $("#filtroTallerPts").val() || "",
        nivel: $("#filtroNivelPts").val() || "",
        modelo: $("#filtroModeloPts").val() || "",
        incluir_stats: incluirStats ? "1" : "0"
    }, function (resp) {
        if (!resp || !resp.ok) {
            ptsProgramadosCache = [];
            $("#tablaProgramadoPts tbody").html(
                '<tr><td colspan="10" class="text-center text-muted">No se pudo cargar</td></tr>'
            );
            $("#conteoConsumidosPts").text("");
            return;
        }
        if (incluirStats && resp.estadisticas) {
            ptsStatsCache = resp.estadisticas;
            pintarEstadisticasPts(resp.estadisticas);
        }
        ptsProgramadosCache = resp.data || [];
        pintarTablaProgramadosPts();
    }, "json");
}

function claveDispPts(r) {
    return String(r.modelo || "") + "|" + String(r.cod_color || "");
}

function pintarTablaDisponiblesPts() {
    var $tb = $("#tablaDisponiblesPts tbody").empty();
    $("#chkTodosDispPts").prop("checked", false);
    var data = ptsDisponiblesCache || [];
    if (!data.length) {
        $tb.html('<tr><td colspan="9" class="text-center text-muted">No hay modelo/color con saldo en corte u OC</td></tr>');
        actualizarContadorSelPts();
        return;
    }

    var botones = botonesNivelHtmlPts();
    var prevModelo = null;
    data.forEach(function (r, idx) {
        var tallerTxt = (r.cod_sector_resuelto || "—") + (r.nom_sector ? " — " + r.nom_sector : "");
        var sinTaller = !r.cod_sector_resuelto;
        var alm = parseInt(r.alm_corte, 10) || 0;
        var ord = parseInt(r.ord_corte, 10) || 0;
        var disp = parseInt(r.saldo_disponible, 10) || (alm + ord);
        var $tr = $("<tr>");
        if (sinTaller) {
            $tr.addClass("warning");
        }
        if (prevModelo !== null && String(r.modelo || "") !== String(prevModelo)) {
            $tr.addClass("pts-sep-modelo");
        }
        prevModelo = r.modelo || "";
        var estiloFila = estiloFilaTallerPts(r);
        if (estiloFila) {
            $tr.attr("style", estiloFila);
        }
        $tr.append(
            '<td><input type="checkbox" class="chkDispPts"' + (sinTaller ? " disabled" : "") + "></td>"
            + celdaTallerHtmlPts(
                r,
                tallerTxt,
                sinTaller ? ' <i class="fa fa-warning text-yellow" title="Sin taller"></i>' : ""
            )
            + "<td>" + escaparPts(r.modelo) + "</td>"
            + "<td>" + escaparPts(etiquetaColorPts(r)) + "</td>"
            + "<td>" + escaparPts(alm) + "</td>"
            + "<td>" + escaparPts(ord) + "</td>"
            + "<td><strong>" + escaparPts(disp) + "</strong></td>"
            + "<td>" + escaparPts(r.urg_plan == null ? "—" : r.urg_plan) + "</td>"
            + '<td class="celda-niveles-pts">' + (sinTaller
                ? '<span class="text-muted">Configura taller primero</span>'
                : botones) + "</td>"
        );
        $tr.data("row", r);
        $tr.data("idx", idx);
        $tb.append($tr);
    });
    actualizarContadorSelPts();
}

function cargarDisponiblesPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "candidatos",
        cod_sector: $("#filtroTallerPts").val() || "",
        modelo: $("#filtroModeloPts").val() || ""
    }, function (resp) {
        if (!resp || !resp.ok) {
            ptsDisponiblesCache = [];
            $("#tablaDisponiblesPts tbody").html(
                '<tr><td colspan="9" class="text-center text-muted">No se pudo cargar</td></tr>'
            );
            actualizarContadorSelPts();
            return;
        }
        ptsDisponiblesCache = resp.data || [];
        pintarTablaDisponiblesPts();
    }, "json");
}

function recargarPts(opts) {
    opts = opts || {};
    // Carga listados sin stats pesadas; las stats van aparte (o se omiten si opts.stats === false)
    var pProg = cargarProgramadosPts({ stats: false });
    var pDisp = cargarDisponiblesPts();
    var pStats = opts.stats === false
        ? $.Deferred().resolve().promise()
        : cargarEstadisticasPts();
    return $.when(pProg, pDisp, pStats).always(function () {
        if (ptsInicializado) {
            sincronizarUrlPts();
        }
    });
}

function payloadProgramarPts(row, nivel, cantidad) {
    return {
        accion: "programar",
        anio: anioPts(),
        semana: semanaPts(),
        modelo: row.modelo || "",
        cod_color: row.cod_color || "",
        cod_sector: row.cod_sector_resuelto || row.cod_sector || "",
        nivel: nivel,
        cantidad: cantidad,
        observacion: ""
    };
}

function programarFilaPts($tr, nivel) {
    var row = $tr.data("row");
    if (!row || !nivel) return;
    if (!row.cod_sector_resuelto) {
        toastPts("Sin taller configurado para este modelo/color", "warning");
        return;
    }
    var cant = parseInt(row.saldo_disponible, 10) || 0;
    if (cant < 1) {
        toastPts("Sin total disponible para programar", "warning");
        return;
    }

    $tr.addClass("programando-pts");
    $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", true);

    $.post("ajax/programacion-taller-semana.ajax.php", payloadProgramarPts(row, nivel, cant), function (resp) {
        if (resp && resp.ok) {
            $tr.removeClass("programando-pts").addClass("ok-pts");
            toastPts((row.modelo || "") + " / " + (row.cod_color || "") + " → programado", "success");
            var clave = claveDispPts(row);
            ptsDisponiblesCache = ptsDisponiblesCache.filter(function (x) {
                return claveDispPts(x) !== clave;
            });
            $tr.fadeOut(120, function () {
                $tr.remove();
                actualizarContadorSelPts();
                if (!$("#tablaDisponiblesPts tbody tr").length) {
                    pintarTablaDisponiblesPts();
                }
            });
            if (resp.row) {
                upsertProgramadoCachePts(resp.row);
            } else {
                cargarProgramadosPts({ stats: false });
            }
            programarRefreshStatsPts();
        } else {
            $tr.removeClass("programando-pts");
            $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", false);
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo programar", "danger");
        }
    }, "json").fail(function () {
        $tr.removeClass("programando-pts");
        $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", false);
        toastPts("Error de comunicación", "danger");
    });
}

function programarLotePts() {
    var nivel = $("#nivelLotePts").val();
    if (!nivel) {
        toastPts("Elige un nivel para el lote", "warning");
        return;
    }
    var items = [];
    var $filas = [];
    $("#tablaDisponiblesPts tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find(".chkDispPts").is(":checked")) return;
        var row = $tr.data("row");
        if (!row || !row.cod_sector_resuelto) return;
        var cant = parseInt(row.saldo_disponible, 10) || 0;
        if (cant < 1) return;
        items.push({
            modelo: row.modelo,
            cod_color: row.cod_color,
            cod_sector: row.cod_sector_resuelto,
            cantidad: cant,
            nivel: nivel
        });
        $filas.push($tr);
    });
    if (!items.length) {
        toastPts("No hay filas válidas seleccionadas", "warning");
        return;
    }

    $("#btnProgramarLotePts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Programando…');
    $filas.forEach(function ($tr) { $tr.addClass("programando-pts"); });

    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "programarLote",
        anio: anioPts(),
        semana: semanaPts(),
        items: JSON.stringify(items)
    }, function (resp) {
        $("#btnProgramarLotePts").html('Programar seleccionados (<span id="nSelPts">0</span>)');
        if (resp && resp.ok) {
            toastPts(resp.mensaje || "Lote programado", "success");
            if (resp.errores && resp.errores.length) {
                console.warn("Errores lote PTS", resp.errores);
            }
            // Listados sin esperar stats; stats en background
            $.when(
                cargarDisponiblesPts(),
                cargarProgramadosPts({ stats: false })
            ).always(function () {
                actualizarContadorSelPts();
                programarRefreshStatsPts();
            });
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo programar el lote", "danger");
            $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
            actualizarContadorSelPts();
        }
    }, "json").fail(function () {
        $("#btnProgramarLotePts").html('Programar seleccionados (<span id="nSelPts">0</span>)');
        toastPts("Error de comunicación", "danger");
        $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
        actualizarContadorSelPts();
    });
}

function abrirModalProgramarPts(row, esEdicion) {
    $("#ptsId").val(esEdicion ? row.id : "");
    $("#ptsModelo").val(row.modelo || "");
    $("#ptsCodColor").val(row.cod_color || "");
    $("#tituloModalPts").text(esEdicion ? "Editar programación" : "Programar modelo/color");
    $("#ptsArticuloTexto").text(
        (row.modelo || "") + " / " + etiquetaColorPts(row) + (row.nombre ? " — " + row.nombre : "")
    );
    var alm = row.alm_corte_vivo != null
        ? row.alm_corte_vivo
        : (row.alm_corte != null ? row.alm_corte : row.saldo_alm_corte);
    var ord = row.ord_corte_vivo != null
        ? row.ord_corte_vivo
        : (row.ord_corte != null ? row.ord_corte : row.saldo_ord_corte);
    var disp = row.saldo_vivo != null
        ? row.saldo_vivo
        : (row.saldo_disponible != null
            ? row.saldo_disponible
            : ((parseInt(alm, 10) || 0) + (parseInt(ord, 10) || 0)));
    var textoSaldo = "Alm. corte: " + (alm || 0) + " · Ord. corte: " + (ord || 0) + " · Disponible: " + disp;
    if (esEdicion && esConsumidoPts(row)) {
        textoSaldo += " · Consumido (sin saldo vivo en corte/OC)";
    }
    $("#ptsSaldosTexto").text(textoSaldo);
    var taller = row.cod_sector_resuelto || row.cod_sector || "";
    $("#ptsTaller").val(taller);
    refrescarSelectPts($("#ptsTaller"));
    $("#ptsNivel").val(esEdicion && row.nivel ? row.nivel : "");
    $("#ptsCantidad").val(esEdicion ? row.cantidad : (disp > 0 ? disp : 1));
    if (esEdicion && esConsumidoPts(row)) {
        $("#ptsCantidad").attr("max", Math.max(parseInt(row.cantidad, 10) || 0, 0));
    } else {
        $("#ptsCantidad").attr("max", disp > 0 ? disp : "");
    }
    $("#ptsObservacion").val(row.observacion || "");
    $("#modalProgramarPts").modal("show");
}

if ($("#filtroAnioPts").length) {
    $.when(cargarNivelesPts(), cargarSectoresPts(), cargarModelosPts()).always(function () {
        aplicarEstadoInicialPts();
        actualizarRangoPts().always(function () {
            recargarPts().always(function () {
                ptsInicializado = true;
                sincronizarUrlPts();
            });
        });
    });
}

function urlExcelPts() {
    var params = new URLSearchParams();
    params.set("anio", String(anioPts()));
    params.set("semana", String(semanaPts()));
    var modelo = $("#filtroModeloPts").val() || "";
    var taller = $("#filtroTallerPts").val() || "";
    var nivel = $("#filtroNivelPts").val() || "";
    if (modelo) params.set("modelo", modelo);
    if (taller) params.set("taller", taller);
    if (nivel) params.set("nivel", nivel);
    return "vistas/reportes_excel/rpt_programacion_taller_semana.php?" + params.toString();
}

$("#btnSemanaAntPts").on("click", function () { moverSemanaPts(-1); });
$("#btnSemanaSigPts").on("click", function () { moverSemanaPts(1); });
$("#btnActualizarPts").on("click", function () {
    actualizarRangoPts().always(function () { recargarPts(); });
});
$("#btnExcelPts").on("click", function (e) {
    e.preventDefault();
    window.location.href = urlExcelPts();
});
$("#filtroAnioPts, #filtroSemanaPts").on("change", function () {
    actualizarRangoPts().always(function () { recargarPts(); });
});
$("#filtroTallerPts, #filtroModeloPts, #filtroNivelPts").on("changed.bs.select change", function () {
    if (ptsUrlSyncLock) return;
    recargarPts();
});
$("#chkOcultarConsumidosPts").on("change", function () {
    pintarTablaProgramadosPts();
    sincronizarUrlPts();
});

$("#tabsPts a[data-toggle='tab']").on("shown.bs.tab", function () {
    sincronizarUrlPts();
    // Al abrir "Ya programado", refrescar listado (stats en background)
    if (tabActivaPts() === "programado") {
        cargarProgramadosPts({ stats: false });
        programarRefreshStatsPts();
    }
});

$("#chkTodosDispPts").on("change", function () {
    var on = $(this).is(":checked");
    $("#tablaDisponiblesPts tbody .chkDispPts:not(:disabled)").prop("checked", on);
    actualizarContadorSelPts();
});
$(document).on("change", ".chkDispPts", actualizarContadorSelPts);
$("#nivelLotePts").on("change", actualizarContadorSelPts);
$("#btnProgramarLotePts").on("click", programarLotePts);

$(document).on("click", ".btnNivelRapidoPts", function () {
    var nivel = $(this).attr("data-nivel");
    var $tr = $(this).closest("tr");
    programarFilaPts($tr, nivel);
});

$(document).on("click", ".btnEditarPts", function () {
    var row = $(this).data("row");
    if (!row) return;
    abrirModalProgramarPts(row, true);
});

$(document).on("click", ".btnEliminarPts", function () {
    var $btn = $(this);
    var $tr = $btn.closest("tr");
    var id = $btn.attr("data-id");
    if ($tr.hasClass("programando-pts")) return;

    var go = function () {
        $tr.addClass("programando-pts");
        $tr.find(".btnEditarPts, .btnEliminarPts").prop("disabled", true);
        var $icon = $btn.find("i");
        var iconPrev = $icon.attr("class") || "fa fa-trash";
        $icon.attr("class", "fa fa-spinner fa-spin");

        $.post("ajax/programacion-taller-semana.ajax.php", { accion: "eliminar", id: id }, function (resp) {
            if (resp && resp.ok) {
                $tr.removeClass("programando-pts").addClass("ok-pts");
                toastPts(resp.mensaje || "Eliminado", "success");
                $tr.fadeOut(160, function () {
                    quitarProgramadoCachePts(id);
                    if (resp.candidato) {
                        upsertDisponibleCachePts(resp.candidato);
                    }
                    programarRefreshStatsPts();
                });
            } else {
                $tr.removeClass("programando-pts");
                $tr.find(".btnEditarPts, .btnEliminarPts").prop("disabled", false);
                $icon.attr("class", iconPrev);
                toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo eliminar", "danger");
            }
        }, "json").fail(function () {
            $tr.removeClass("programando-pts");
            $tr.find(".btnEditarPts, .btnEliminarPts").prop("disabled", false);
            $icon.attr("class", iconPrev);
            toastPts("Error de comunicación", "danger");
        });
    };
    if (typeof swal === "function") {
        swal({
            title: "¿Quitar de la programación?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd4b39",
            confirmButtonText: "Sí, quitar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result && result.value) go();
        });
        return;
    }
    if (window.confirm("¿Quitar de la programación?")) go();
});

$("#formProgramarPts").on("submit", function (e) {
    e.preventDefault();
    var id = $("#ptsId").val();
    var payload = {
        accion: id ? "editar" : "programar",
        id: id || "",
        anio: anioPts(),
        semana: semanaPts(),
        modelo: $("#ptsModelo").val(),
        cod_color: $("#ptsCodColor").val(),
        cod_sector: $("#ptsTaller").val(),
        nivel: $("#ptsNivel").val(),
        cantidad: $("#ptsCantidad").val(),
        observacion: $("#ptsObservacion").val()
    };
    $.post("ajax/programacion-taller-semana.ajax.php", payload, function (resp) {
        if (resp && resp.ok) {
            $("#modalProgramarPts").modal("hide");
            if (resp.row) {
                upsertProgramadoCachePts(resp.row);
                var clave = claveDispPts(resp.row);
                ptsDisponiblesCache = ptsDisponiblesCache.filter(function (x) {
                    return claveDispPts(x) !== clave;
                });
                pintarTablaDisponiblesPts();
            } else {
                cargarProgramadosPts({ stats: false });
                cargarDisponiblesPts();
            }
            programarRefreshStatsPts();
            toastPts(resp.mensaje || "Guardado", "success");
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo guardar", "danger");
        }
    }, "json").fail(function () {
        toastPts("Error de comunicación", "danger");
    });
});

$("#modalProgramarPts").on("shown.bs.modal", function () {
    refrescarSelectPts($("#ptsTaller"));
});
