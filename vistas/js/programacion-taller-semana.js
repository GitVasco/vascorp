var ptsNiveles = [];
var ptsMapaNiveles = {};
var ptsSectores = [];
var ptsProgramadosCache = [];
var ptsDisponiblesCache = [];
var ptsPriorizadosCache = [];
var ptsNoEjecCache = [];
var ptsEnviarCache = [];
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

/** Semana elegida para destinar (independiente del filtro de cabecera). */
function anioDestPts() {
    return parseInt($("#destAnioPts").val(), 10) || 0;
}

function semanaDestPts() {
    return parseInt($("#destSemanaPts").val(), 10) || 0;
}

/** Semana a consultar en Ya programado. */
function anioProgPts() {
    return parseInt($("#progAnioPts").val(), 10) || anioPts();
}

function semanaProgPts() {
    return parseInt($("#progSemanaPts").val(), 10) || semanaPts();
}

function anioEnvDesdePts() {
    return parseInt($("#envAnioDesdePts").val(), 10) || anioPts();
}

function semanaEnvDesdePts() {
    return parseInt($("#envSemanaDesdePts").val(), 10) || semanaPts();
}

function anioEnvHastaPts() {
    return parseInt($("#envAnioHastaPts").val(), 10) || anioPts();
}

function semanaEnvHastaPts() {
    return parseInt($("#envSemanaHastaPts").val(), 10) || semanaPts();
}

function moverIsoPts(anio, semana, delta) {
    semana = (parseInt(semana, 10) || 1) + (parseInt(delta, 10) || 0);
    anio = parseInt(anio, 10) || 0;
    if (semana < 1) {
        anio -= 1;
        semana = 52;
    } else if (semana > 53) {
        anio += 1;
        semana = 1;
    }
    return { anio: anio, semana: semana };
}

function sincronizarCabeceraConProgPts() {
    var anio = anioProgPts();
    var semana = semanaProgPts();
    if (!anio || !semana) return;
    $("#filtroAnioPts").val(anio);
    $("#filtroSemanaPts").val(semana);
}

function tabActivaPts() {
    var tab = $("#tabsPts li.active a").attr("data-tab-pts");
    if (tab === "programado" || tab === "destinar" || tab === "priorizar" || tab === "no_ejecutado" || tab === "enviar") {
        return tab;
    }
    if (tab === "programar") {
        return "priorizar";
    }
    return "priorizar";
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

function botonesNivelHtmlPts() {
    var niveles = nivelesOrdenadosPts();
    if (!niveles.length) {
        return '<span class="text-muted">Sin niveles</span>';
    }
    return niveles.map(function (n) {
        var bg = n.color || "#ddd";
        return '<button type="button" class="btn-nivel-pts btnNivelRapidoPts" data-nivel="'
            + escaparPts(n.id) + '" style="background:' + escaparPts(bg) + '" title="'
            + escaparPts(n.nombre) + '">' + escaparPts(n.nombre) + "</button>";
    }).join("");
}

function nivelesOrdenadosPts() {
    return (ptsNiveles || []).slice().sort(function (a, b) {
        return (parseInt(a.orden, 10) || 99) - (parseInt(b.orden, 10) || 99);
    });
}

function cargarNivelesPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "niveles" }, function (resp) {
        ptsNiveles = (resp && resp.ok && resp.data && resp.data.niveles) ? resp.data.niveles : [];
        ptsMapaNiveles = {};
        var $leyenda = $("#leyendaNivelesPts");
        var $filtro = $("#filtroNivelPts");
        var $destNivel = $("#destFiltroNivelPts");
        var $progNivel = $("#progFiltroNivelPts");
        var $envNivel = $("#envFiltroNivelPts");
        var $modalNivel = $("#ptsNivel");
        $filtro.find("option:not(:first)").remove();
        $destNivel.find("option:not(:first)").remove();
        $progNivel.find("option:not(:first)").remove();
        $envNivel.find("option:not(:first)").remove();
        $modalNivel.find("option:not(:first)").remove();
        var chips = ['<strong style="margin-right:4px;">Niveles:</strong>'];
        nivelesOrdenadosPts().forEach(function (n) {
            ptsMapaNiveles[n.id] = n;
            chips.push(badgeNivelPts(n.id, n.nombre, n.color));
            $filtro.append($("<option>").val(n.id).text(n.nombre));
            $destNivel.append($("<option>").val(n.id).text(n.nombre));
            $progNivel.append($("<option>").val(n.id).text(n.nombre));
            $envNivel.append($("<option>").val(n.id).text(n.nombre));
            $modalNivel.append($("<option>").val(n.id).text(n.nombre));
        });
        $leyenda.html(chips.join(" "));
        refrescarSelectPts($destNivel);
        refrescarSelectPts($progNivel);
        refrescarSelectPts($envNivel);
        pintarChkNivelesLotePts();
    }, "json");
}

function pintarChkNivelesLotePts() {
    var $box = $("#chkNivelesLotePts");
    if (!$box.length) return;
    var niveles = nivelesOrdenadosPts();
    if (!niveles.length) {
        $box.html('<span class="text-muted">Sin niveles</span>');
        return;
    }
    var actual = nivelLoteSeleccionadoPts();
    var html = [];
    niveles.forEach(function (n) {
        var bg = n.color || "#ddd";
        var checked = String(actual) === String(n.id) ? " checked" : "";
        var activo = checked ? " activo" : "";
        html.push(
            '<label class="pts-chk-nivel-item' + activo + '" style="--pts-nivel-color:'
            + escaparPts(bg) + ';border-color:' + escaparPts(bg) + '">'
            + '<input type="checkbox" class="chkNivelLotePts" name="chkNivelLotePts" value="'
            + escaparPts(n.id) + '"' + checked + '>'
            + '<span class="pts-chk-nivel-badge" style="background:' + escaparPts(bg) + '">'
            + escaparPts(n.nombre) + "</span>"
            + "</label>"
        );
    });
    $box.html(html.join(""));
}

/** Marca un solo nivel en los checkboxes del lote. */
function setNivelLotePts(nivelId) {
    nivelId = nivelId || "";
    $("#chkNivelesLotePts .chkNivelLotePts").each(function () {
        var on = String($(this).val()) === String(nivelId) && nivelId !== "";
        $(this).prop("checked", on);
        $(this).closest(".pts-chk-nivel-item").toggleClass("activo", on);
    });
    actualizarContadorSelPts();
}

function nivelLoteSeleccionadoPts() {
    var desdeChk = $("#chkNivelesLotePts .chkNivelLotePts:checked").first().val();
    return desdeChk ? String(desdeChk) : "";
}

function cargarModelosPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "listarModelos" }, function (resp) {
        var $filtro = $("#filtroModeloPts");
        var $destModelo = $("#destFiltroModeloPts");
        var $progModelo = $("#progFiltroModeloPts");
        var $envModelo = $("#envFiltroModeloPts");
        $filtro.find("option:not(:first)").remove();
        $destModelo.find("option:not(:first)").remove();
        $progModelo.find("option:not(:first)").remove();
        $envModelo.find("option:not(:first)").remove();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (m) {
                var txt = m.etiqueta || m.modelo;
                $filtro.append($("<option>").val(m.modelo).text(txt));
                $destModelo.append($("<option>").val(m.modelo).text(txt));
                $progModelo.append($("<option>").val(m.modelo).text(txt));
                $envModelo.append($("<option>").val(m.modelo).text(txt));
            });
        }
        refrescarSelectPts($filtro);
        refrescarSelectPts($destModelo);
        refrescarSelectPts($progModelo);
        refrescarSelectPts($envModelo);
    }, "json");
}

function cargarSectoresPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "listarSectores" }, function (resp) {
        ptsSectores = (resp && resp.ok && resp.data) ? resp.data : [];
        var $filtro = $("#filtroTallerPts");
        var $destTaller = $("#destFiltroTallerPts");
        var $progTaller = $("#progFiltroTallerPts");
        var $envTaller = $("#envFiltroTallerPts");
        var $modal = $("#ptsTaller");
        $filtro.find("option:not(:first)").remove();
        $destTaller.find("option:not(:first)").remove();
        $progTaller.find("option:not(:first)").remove();
        $envTaller.find("option:not(:first)").remove();
        $modal.find("option:not(:first)").remove();
        ptsSectores.forEach(function (s) {
            var txt = s.cod_sector + " — " + (s.nom_sector || "");
            $filtro.append($("<option>").val(s.cod_sector).text(txt));
            $destTaller.append($("<option>").val(s.cod_sector).text(txt));
            $progTaller.append($("<option>").val(s.cod_sector).text(txt));
            $envTaller.append($("<option>").val(s.cod_sector).text(txt));
            $modal.append($("<option>").val(s.cod_sector).text(txt));
        });
        refrescarSelectPts($filtro);
        refrescarSelectPts($destTaller);
        refrescarSelectPts($progTaller);
        refrescarSelectPts($envTaller);
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

function actualizarRangoDestPts() {
    var anio = anioDestPts();
    var semana = semanaDestPts();
    if (!anio || !semana) {
        $("#textoRangoDestPts").text("—");
        $("#avisoDestPasadaPts").hide();
        actualizarContadorSelDestPts();
        return $.Deferred().resolve().promise();
    }
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#destAnioPts").val(resp.data.anio);
            $("#destSemanaPts").val(resp.data.semana);
            $("#textoRangoDestPts").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            var pasada = parseInt(resp.data.pasada, 10) === 1;
            $("#avisoDestPasadaPts").toggle(pasada);
            $("#destAnioPts, #destSemanaPts").data("pasada", pasada ? 1 : 0);
        } else {
            $("#textoRangoDestPts").text("Semana no válida");
            $("#avisoDestPasadaPts").show();
            $("#destAnioPts, #destSemanaPts").data("pasada", 1);
        }
        actualizarContadorSelDestPts();
    }, "json");
}

function actualizarRangoProgPts() {
    var anio = anioProgPts();
    var semana = semanaProgPts();
    if (!anio || !semana) {
        $("#textoRangoProgPts").text("—");
        return $.Deferred().resolve().promise();
    }
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#progAnioPts").val(resp.data.anio);
            $("#progSemanaPts").val(resp.data.semana);
            $("#textoRangoProgPts").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            sincronizarCabeceraConProgPts();
            $("#textoRangoSemanaPts").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
        } else {
            $("#textoRangoProgPts").text("Semana no válida");
        }
    }, "json");
}

function actualizarRangoEnvPts() {
    var dAnio = anioEnvDesdePts();
    var dSem = semanaEnvDesdePts();
    var hAnio = anioEnvHastaPts();
    var hSem = semanaEnvHastaPts();
    if (!dAnio || !dSem || !hAnio || !hSem) {
        $("#textoRangoEnvPts").text("—");
        return $.Deferred().resolve().promise();
    }
    var pDesde = $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana", anio: dAnio, semana: dSem
    }, null, "json");
    var pHasta = $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana", anio: hAnio, semana: hSem
    }, null, "json");
    return $.when(pDesde, pHasta).done(function (a, b) {
        var respD = a && a[0] ? a[0] : a;
        var respH = b && b[0] ? b[0] : b;
        var dIni = (respD && respD.ok && respD.data) ? respD.data.fecha_inicio : "—";
        var dFin = (respD && respD.ok && respD.data) ? respD.data.fecha_fin : "—";
        var hIni = (respH && respH.ok && respH.data) ? respH.data.fecha_inicio : "—";
        var hFin = (respH && respH.ok && respH.data) ? respH.data.fecha_fin : "—";
        if (respD && respD.ok && respD.data) {
            $("#envAnioDesdePts").val(respD.data.anio);
            $("#envSemanaDesdePts").val(respD.data.semana);
        }
        if (respH && respH.ok && respH.data) {
            $("#envAnioHastaPts").val(respH.data.anio);
            $("#envSemanaHastaPts").val(respH.data.semana);
        }
        if (dIni !== "—" && hIni !== "—" && String(dIni) > String(hIni)) {
            var tmpA = $("#envAnioDesdePts").val();
            var tmpS = $("#envSemanaDesdePts").val();
            $("#envAnioDesdePts").val($("#envAnioHastaPts").val());
            $("#envSemanaDesdePts").val($("#envSemanaHastaPts").val());
            $("#envAnioHastaPts").val(tmpA);
            $("#envSemanaHastaPts").val(tmpS);
            $("#textoRangoEnvPts").text(hIni + " → " + dFin);
        } else {
            $("#textoRangoEnvPts").text(dIni + " → " + hFin);
        }
    });
}

function moverSemanaEnvDesdePts(delta) {
    var n = moverIsoPts(anioEnvDesdePts(), semanaEnvDesdePts(), delta);
    $("#envAnioDesdePts").val(n.anio);
    $("#envSemanaDesdePts").val(n.semana);
    actualizarRangoEnvPts().always(function () {
        cargarArticulosEnviarPts();
    });
}

function moverSemanaEnvHastaPts(delta) {
    var n = moverIsoPts(anioEnvHastaPts(), semanaEnvHastaPts(), delta);
    $("#envAnioHastaPts").val(n.anio);
    $("#envSemanaHastaPts").val(n.semana);
    actualizarRangoEnvPts().always(function () {
        cargarArticulosEnviarPts();
    });
}

function aplicarPresetEnvPts(semanasAtras) {
    semanasAtras = parseInt(semanasAtras, 10) || 0;
    $(".btnPresetEnvPts").removeClass("btn-primary").addClass("btn-default");
    $(".btnPresetEnvPts[data-semanas='" + semanasAtras + "']").removeClass("btn-default").addClass("btn-primary");
    var hasta = { anio: anioPts(), semana: semanaPts() };
    var desde = moverIsoPts(hasta.anio, hasta.semana, -semanasAtras);
    $("#envAnioHastaPts").val(hasta.anio);
    $("#envSemanaHastaPts").val(hasta.semana);
    $("#envAnioDesdePts").val(desde.anio);
    $("#envSemanaDesdePts").val(desde.semana);
    actualizarRangoEnvPts().always(function () {
        cargarArticulosEnviarPts();
    });
}

function moverSemanaProgPts(delta) {
    var anio = anioProgPts();
    var semana = semanaProgPts() + delta;
    if (semana < 1) {
        anio -= 1;
        semana = 52;
    } else if (semana > 53) {
        anio += 1;
        semana = 1;
    }
    $("#progAnioPts").val(anio);
    $("#progSemanaPts").val(semana);
    mostrarCargandoProgramadosPts();
    actualizarRangoProgPts().always(function () {
        sincronizarCabeceraConProgPts();
        actualizarRangoPts();
        cargarProgramadosPts({ stats: false });
        programarRefreshStatsPts();
        sincronizarUrlPts();
    });
}

function semanaDestPasadaPts() {
    return parseInt($("#destAnioPts").data("pasada"), 10) === 1;
}

function moverSemanaDestPts(delta) {
    var anio = anioDestPts();
    var semana = semanaDestPts() + delta;
    if (semana < 1) {
        anio -= 1;
        semana = 52;
    } else if (semana > 53) {
        anio += 1;
        semana = 1;
    }
    $("#destAnioPts").val(anio);
    $("#destSemanaPts").val(semana);
    actualizarRangoDestPts();
}

function moverSemanaNePts(delta) {
    var anio = anioNePts();
    var semana = semanaNePts() + delta;
    if (semana < 1) {
        anio -= 1;
        semana = 52;
    } else if (semana > 53) {
        anio += 1;
        semana = 1;
    }
    $("#neAnioPts").val(anio);
    $("#neSemanaPts").val(semana);
    actualizarRangoNePts();
}

function sincronizarProgConCabeceraPts() {
    var anio = anioPts();
    var semana = semanaPts();
    if (!anio || !semana) return;
    $("#progAnioPts").val(anio);
    $("#progSemanaPts").val(semana);
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
        sincronizarProgConCabeceraPts();
        actualizarRangoProgPts();
        cargarEstadisticasPts();
        if (tabActivaPts() === "programado") {
            mostrarCargandoProgramadosPts();
            cargarProgramadosPts({ stats: false });
        }
        sincronizarUrlPts();
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
    var $box = $("#estadisticasSemanaBodyPts");
    if (!$box.length) {
        $box = $("#estadisticasSemanaPts");
    }
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

    // Panel ya programado (resumen de la semana elegida arriba)
    html += '<div class="pts-stats-panel">';
    html += '<div class="pts-stats-subtitle">Ya programado <small class="text-muted">(sem. '
        + escaparPts(semanaPts()) + ' · ' + escaparPts(anioPts()) + ')</small></div>';
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
    var nivel = nivelLoteSeleccionadoPts();
    $("#btnProgramarLotePts").prop("disabled", n < 1 || !nivel);
}

function actualizarContadorSelDestPts() {
    var n = $("#tablaPriorizadosPts tbody .chkPriPts:checked").length;
    $("#nSelDestPts").text("(" + n + ")");
    var okDest = anioDestPts() > 0 && semanaDestPts() > 0 && !semanaDestPasadaPts();
    $("#btnDestinarLotePts").prop("disabled", n < 1 || !okDest);
}

function filtrosProgramadoPts() {
    return {
        nivel: $("#progFiltroNivelPts").val() || "",
        taller: $("#progFiltroTallerPts").val() || "",
        modelo: $("#progFiltroModeloPts").val() || ""
    };
}

function filasProgramadoFiltradasPts() {
    var f = filtrosProgramadoPts();
    return (ptsProgramadosCache || []).filter(function (r) {
        if (f.nivel && String(r.nivel || "") !== String(f.nivel)) return false;
        if (f.taller && String(r.cod_sector || "") !== String(f.taller)) return false;
        if (f.modelo && String(r.modelo || "") !== String(f.modelo)) return false;
        return true;
    });
}

function pintarTablaProgramadosPts() {
    var $tb = $("#tablaProgramadoPts tbody").empty();
    var ocultar = $("#chkOcultarConsumidosPts").is(":checked");
    var todos = ptsProgramadosCache || [];
    var data = filasProgramadoFiltradasPts();
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

    if (!todos.length) {
        $tb.html('<tr><td colspan="10" class="text-center text-muted">Nada programado en esta semana</td></tr>');
        return;
    }
    if (!data.length) {
        $tb.html('<tr><td colspan="10" class="text-center text-muted">No hay coincidencias con los filtros de esta pestaña.</td></tr>');
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

function anioNePts() {
    return parseInt($("#neAnioPts").val(), 10) || 0;
}

function semanaNePts() {
    return parseInt($("#neSemanaPts").val(), 10) || 0;
}

function semanaNePasadaPts() {
    return parseInt($("#neAnioPts").data("pasada"), 10) === 1;
}

function actualizarRangoNePts() {
    var anio = anioNePts();
    var semana = semanaNePts();
    if (!anio || !semana) {
        $("#textoRangoNePts").text("—");
        $("#avisoNePasadaPts").hide();
        actualizarContadorSelNePts();
        return $.Deferred().resolve().promise();
    }
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#neAnioPts").val(resp.data.anio);
            $("#neSemanaPts").val(resp.data.semana);
            $("#textoRangoNePts").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            var pasada = parseInt(resp.data.pasada, 10) === 1;
            $("#avisoNePasadaPts").toggle(pasada);
            $("#neAnioPts, #neSemanaPts").data("pasada", pasada ? 1 : 0);
        } else {
            $("#textoRangoNePts").text("Semana no válida");
            $("#avisoNePasadaPts").show();
            $("#neAnioPts, #neSemanaPts").data("pasada", 1);
        }
        actualizarContadorSelNePts();
    }, "json");
}

function actualizarContadorSelNePts() {
    var n = $("#tablaNoEjecPts tbody .chkNePts:checked").length;
    $("#nSelNePts").text("(" + n + ")");
    var okDest = anioNePts() > 0 && semanaNePts() > 0 && !semanaNePasadaPts();
    $("#btnMoverNeLotePts").prop("disabled", n < 1 || !okDest);
    $("#btnDevolverNeLotePts").prop("disabled", n < 1);
}

function actualizarBadgeNoEjecPts(total) {
    var $b = $("#badgeNoEjecPts");
    if (!$b.length) return;
    total = parseInt(total, 10) || 0;
    if (total > 0) {
        $b.text(total > 99 ? "99+" : String(total)).show();
    } else {
        $b.hide().text("0");
    }
}

function mostrarCargandoNoEjecPts() {
    $("#tablaNoEjecPts tbody").html(
        '<tr><td colspan="11" class="text-center" style="padding:28px 12px;">'
        + '<i class="fa fa-spinner fa-spin fa-lg" style="margin-right:8px;"></i>'
        + '<span class="text-muted">Cargando no ejecutados…</span></td></tr>'
    );
}

function pintarTablaNoEjecPts() {
    var $tb = $("#tablaNoEjecPts tbody").empty();
    $("#chkTodosNePts").prop("checked", false);
    var data = ptsNoEjecCache || [];
    actualizarBadgeNoEjecPts(data.length);
    if (!data.length) {
        $tb.html('<tr><td colspan="11" class="text-center text-muted">No hay programados pendientes en semanas pasadas</td></tr>');
        actualizarContadorSelNePts();
        return;
    }
    data.forEach(function (r) {
        var taller = (r.cod_sector || "") + (r.nom_sector ? " — " + r.nom_sector : "");
        var alm = r.alm_corte_vivo != null ? r.alm_corte_vivo : r.saldo_alm_corte;
        var ord = r.ord_corte_vivo != null ? r.ord_corte_vivo : r.saldo_ord_corte;
        var saldo = r.saldo_vivo != null ? r.saldo_vivo : ((parseInt(alm, 10) || 0) + (parseInt(ord, 10) || 0));
        var origen = "Sem. " + (r.semana || "?") + " · " + (r.anio || "");
        if (r.fecha_inicio && r.fecha_fin) {
            origen += "<br><small class=\"text-muted\">" + escaparPts(r.fecha_inicio) + " → " + escaparPts(r.fecha_fin) + "</small>";
        }
        var $tr = $("<tr>");
        var estiloFila = estiloFilaTallerPts(r);
        if (estiloFila) $tr.attr("style", estiloFila);
        $tr.append(
            '<td><input type="checkbox" class="chkNePts"></td>'
            + "<td>" + origen + "</td>"
            + "<td>" + badgeNivelPts(r.nivel, r.nivel_nombre, r.nivel_color) + "</td>"
            + celdaTallerHtmlPts(r, taller)
            + "<td>" + escaparPts(r.modelo) + "</td>"
            + "<td>" + escaparPts(etiquetaColorPts(r)) + "</td>"
            + "<td><strong>" + escaparPts(r.cantidad) + "</strong></td>"
            + "<td>" + escaparPts(alm) + "</td>"
            + "<td>" + escaparPts(ord) + "</td>"
            + "<td><strong>" + escaparPts(saldo) + "</strong></td>"
            + '<td>'
            + '<button type="button" class="btn btn-xs btn-success btnMoverNePts" title="Mover a semana"><i class="fa fa-calendar"></i></button> '
            + '<button type="button" class="btn btn-xs btn-warning btnDevolverNePts" title="Devolver a prioridad"><i class="fa fa-flag"></i></button>'
            + "</td>"
        );
        $tr.data("row", r);
        $tb.append($tr);
    });
    actualizarContadorSelNePts();
}

function cargarNoEjecutadosPts() {
    mostrarCargandoNoEjecPts();
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "noEjecutados",
        cod_sector: $("#filtroTallerPts").val() || "",
        nivel: $("#filtroNivelPts").val() || "",
        modelo: $("#filtroModeloPts").val() || ""
    }, function (resp) {
        if (!resp || !resp.ok) {
            ptsNoEjecCache = [];
            $("#tablaNoEjecPts tbody").html(
                '<tr><td colspan="11" class="text-center text-muted">No se pudo cargar</td></tr>'
            );
            actualizarBadgeNoEjecPts(0);
            actualizarContadorSelNePts();
            return;
        }
        ptsNoEjecCache = resp.data || [];
        pintarTablaNoEjecPts();
    }, "json").fail(function () {
        ptsNoEjecCache = [];
        $("#tablaNoEjecPts tbody").html(
            '<tr><td colspan="11" class="text-center text-muted">Error de comunicación</td></tr>'
        );
        actualizarBadgeNoEjecPts(0);
        actualizarContadorSelNePts();
    });
}

function refrescarBadgeNoEjecPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", { accion: "contarNoEjecutados" }, function (resp) {
        if (resp && resp.ok) {
            actualizarBadgeNoEjecPts(resp.total);
        }
    }, "json");
}

function abrirModalMoverNePts(row) {
    if (!row || !row.id) return;
    $("#neModalId").val(row.id);
    $("#neModalArticulo").text((row.modelo || "") + " / " + etiquetaColorPts(row));
    $("#neModalOrigen").html(
        "Origen: sem. " + escaparPts(row.semana) + " · " + escaparPts(row.anio)
        + " · " + badgeNivelPts(row.nivel, row.nivel_nombre, row.nivel_color)
    );
    $("#neModalAnio").val(anioNePts() || anioPts());
    $("#neModalSemana").val(semanaNePts() || semanaPts());
    actualizarRangoModalNePts();
    $("#modalMoverNePts").modal("show");
}

function actualizarRangoModalNePts() {
    var anio = parseInt($("#neModalAnio").val(), 10) || 0;
    var semana = parseInt($("#neModalSemana").val(), 10) || 0;
    if (!anio || !semana) {
        $("#neModalRango").text("—");
        $("#neModalAvisoPasada").hide();
        return;
    }
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#neModalRango").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            var pasada = parseInt(resp.data.pasada, 10) === 1;
            $("#neModalAvisoPasada").toggle(pasada);
            $("#formMoverNePts").data("pasada", pasada ? 1 : 0);
        } else {
            $("#neModalRango").text("Semana no válida");
            $("#neModalAvisoPasada").show();
            $("#formMoverNePts").data("pasada", 1);
        }
    }, "json");
}

function moverNoEjecutadoPts(id, anio, semana, $tr) {
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "moverNoEjecutado",
        id: id,
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok) {
            $("#modalMoverNePts").modal("hide");
            toastPts(resp.mensaje || "Movido", "success");
            ptsNoEjecCache = ptsNoEjecCache.filter(function (r) { return String(r.id) !== String(id); });
            pintarTablaNoEjecPts();
            if (anio === anioProgPts() && semana === semanaProgPts() && resp.row) {
                upsertProgramadoCachePts(resp.row);
                programarRefreshStatsPts();
            }
        } else {
            if ($tr) $tr.removeClass("programando-pts");
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo mover", "danger");
        }
    }, "json").fail(function () {
        if ($tr) $tr.removeClass("programando-pts");
        toastPts("Error de comunicación", "danger");
    });
}

function devolverNoEjecutadoPts(id, $tr) {
    if ($tr) $tr.addClass("programando-pts");
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "devolverPrioridad",
        id: id
    }, function (resp) {
        if (resp && resp.ok) {
            toastPts(resp.mensaje || "Devuelto", "success");
            ptsNoEjecCache = ptsNoEjecCache.filter(function (r) { return String(r.id) !== String(id); });
            pintarTablaNoEjecPts();
            if (resp.prioridad) {
                upsertPriorizadoCachePts(resp.prioridad);
            } else {
                cargarPriorizadosPts();
            }
        } else {
            if ($tr) $tr.removeClass("programando-pts");
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo devolver", "danger");
        }
    }, "json").fail(function () {
        if ($tr) $tr.removeClass("programando-pts");
        toastPts("Error de comunicación", "danger");
    });
}

function idsSeleccionadosNePts() {
    var ids = [];
    $("#tablaNoEjecPts tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find(".chkNePts").is(":checked")) return;
        var row = $tr.data("row");
        if (row && row.id) ids.push(row.id);
    });
    return ids;
}

function moverNoEjecutadoLotePts() {
    var anio = anioNePts();
    var semana = semanaNePts();
    if (!anio || !semana || semanaNePasadaPts()) {
        toastPts("Elige una semana destino válida", "warning");
        return;
    }
    var ids = idsSeleccionadosNePts();
    if (!ids.length) {
        toastPts("Selecciona al menos uno", "warning");
        return;
    }
    $("#btnMoverNeLotePts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Moviendo…');
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "moverNoEjecutadoLote",
        anio: anio,
        semana: semana,
        ids: JSON.stringify(ids)
    }, function (resp) {
        $("#btnMoverNeLotePts").html('Mover a sem. <span id="nSelNePts">(0)</span>');
        if (resp && resp.ok) {
            toastPts(resp.mensaje || "Movidos", "success");
            $.when(cargarNoEjecutadosPts()).always(function () {
                if (anio === anioProgPts() && semana === semanaProgPts()) {
                    cargarProgramadosPts({ stats: false });
                    programarRefreshStatsPts();
                }
            });
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo mover el lote", "danger");
            actualizarContadorSelNePts();
        }
    }, "json").fail(function () {
        $("#btnMoverNeLotePts").html('Mover a sem. <span id="nSelNePts">(0)</span>');
        toastPts("Error de comunicación", "danger");
        actualizarContadorSelNePts();
    });
}

function devolverNoEjecutadoLotePts() {
    var ids = idsSeleccionadosNePts();
    if (!ids.length) {
        toastPts("Selecciona al menos uno", "warning");
        return;
    }
    var go = function () {
        $("#btnDevolverNeLotePts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>…');
        $.post("ajax/programacion-taller-semana.ajax.php", {
            accion: "devolverPrioridadLote",
            ids: JSON.stringify(ids)
        }, function (resp) {
            $("#btnDevolverNeLotePts").html("Devolver a prioridad");
            if (resp && resp.ok) {
                toastPts(resp.mensaje || "Devueltos", "success");
                $.when(cargarNoEjecutadosPts(), cargarPriorizadosPts()).always(actualizarContadorSelNePts);
            } else {
                toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo devolver el lote", "danger");
                actualizarContadorSelNePts();
            }
        }, "json").fail(function () {
            $("#btnDevolverNeLotePts").html("Devolver a prioridad");
            toastPts("Error de comunicación", "danger");
            actualizarContadorSelNePts();
        });
    };
    if (typeof swal === "function") {
        swal({
            title: "¿Devolver a prioridad?",
            text: ids.length + " ítem(s) saldrán de la semana pasada y volverán a la bandeja.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#f39c12",
            confirmButtonText: "Sí, devolver",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result && result.value) go();
        });
        return;
    }
    if (window.confirm("¿Devolver a prioridad?")) go();
}

function mostrarCargandoProgramadosPts() {
    $("#conteoConsumidosPts").html('<i class="fa fa-spinner fa-spin"></i> Cargando…');
    $("#tablaProgramadoPts tbody").html(
        '<tr class="pts-row-cargando">'
        + '<td colspan="10" class="text-center" style="padding:28px 12px;">'
        + '<i class="fa fa-spinner fa-spin fa-lg" style="margin-right:8px;"></i>'
        + '<span class="text-muted">Cargando programación de la semana…</span>'
        + "</td></tr>"
    );
    $("#barraProgramadoPts").addClass("pts-cargando");
    $("#progAnioPts, #progSemanaPts, #btnProgSemAntPts, #btnProgSemSigPts, #progFiltroNivelPts, #progFiltroTallerPts, #progFiltroModeloPts").prop("disabled", true);
    refrescarSelectPts($("#progFiltroNivelPts"));
    refrescarSelectPts($("#progFiltroTallerPts"));
    refrescarSelectPts($("#progFiltroModeloPts"));
}

function ocultarCargandoProgramadosPts() {
    $("#barraProgramadoPts").removeClass("pts-cargando");
    $("#progAnioPts, #progSemanaPts, #btnProgSemAntPts, #btnProgSemSigPts, #progFiltroNivelPts, #progFiltroTallerPts, #progFiltroModeloPts").prop("disabled", false);
    refrescarSelectPts($("#progFiltroNivelPts"));
    refrescarSelectPts($("#progFiltroTallerPts"));
    refrescarSelectPts($("#progFiltroModeloPts"));
}

var ptsReqProgramados = 0;

function cargarProgramadosPts(opts) {
    opts = opts || {};
    var incluirStats = opts.stats !== false;
    var anio = anioProgPts();
    var semana = semanaProgPts();
    sincronizarCabeceraConProgPts();
    var reqId = ++ptsReqProgramados;
    mostrarCargandoProgramadosPts();
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "programados",
        anio: anio,
        semana: semana,
        incluir_stats: incluirStats ? "1" : "0"
    }, function (resp) {
        if (reqId !== ptsReqProgramados) return;
        ocultarCargandoProgramadosPts();
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
    }, "json").fail(function () {
        if (reqId !== ptsReqProgramados) return;
        ocultarCargandoProgramadosPts();
        ptsProgramadosCache = [];
        $("#tablaProgramadoPts tbody").html(
            '<tr><td colspan="10" class="text-center text-muted">Error de comunicación</td></tr>'
        );
        $("#conteoConsumidosPts").text("");
    });
}

function claveDispPts(r) {
    return String(r.modelo || "") + "|" + String(r.cod_color || "");
}

function filtrosDestinarPts() {
    return {
        nivel: $("#destFiltroNivelPts").val() || "",
        taller: $("#destFiltroTallerPts").val() || "",
        modelo: $("#destFiltroModeloPts").val() || ""
    };
}

function filasDestinarVisiblesPts() {
    var f = filtrosDestinarPts();
    return (ptsPriorizadosCache || []).filter(function (r) {
        if (f.nivel && String(r.nivel || "") !== String(f.nivel)) return false;
        if (f.taller && String(r.cod_sector || "") !== String(f.taller)) return false;
        if (f.modelo && String(r.modelo || "") !== String(f.modelo)) return false;
        return true;
    });
}

function pintarTablaPriorizadosPts() {
    var $tb = $("#tablaPriorizadosPts tbody").empty();
    $("#chkTodosPriPts").prop("checked", false);
    var todos = ptsPriorizadosCache || [];
    var data = filasDestinarVisiblesPts();
    if (!todos.length) {
        $tb.html('<tr><td colspan="12" class="text-center text-muted">Nada priorizado. Ve a la pestaña Priorizar.</td></tr>');
        actualizarContadorSelDestPts();
        return;
    }
    if (!data.length) {
        $tb.html('<tr><td colspan="12" class="text-center text-muted">No hay coincidencias con los filtros de esta pestaña.</td></tr>');
        actualizarContadorSelDestPts();
        return;
    }
    var botones = botonesNivelHtmlPts();
    var prevModelo = null;
    data.forEach(function (r) {
        var tallerTxt = (r.cod_sector || "—") + (r.nom_sector ? " — " + r.nom_sector : "");
        var alm = r.alm_corte_vivo != null ? r.alm_corte_vivo : r.saldo_alm_corte;
        var ord = r.ord_corte_vivo != null ? r.ord_corte_vivo : r.saldo_ord_corte;
        var cant = r.cantidad != null ? r.cantidad : ((parseInt(alm, 10) || 0) + (parseInt(ord, 10) || 0));
        var sinSaldo = (parseInt(r.saldo_vivo, 10) || 0) < 1;
        var $tr = $("<tr>");
        if (sinSaldo) {
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
            '<td><input type="checkbox" class="chkPriPts"' + (sinSaldo ? " disabled" : "") + "></td>"
            + "<td>" + badgeNivelPts(r.nivel, r.nivel_nombre, r.nivel_color) + "</td>"
            + celdaTallerHtmlPts(r, tallerTxt)
            + "<td>" + escaparPts(r.modelo) + "</td>"
            + "<td>" + escaparPts(etiquetaColorPts(r)) + "</td>"
            + "<td><strong>" + escaparPts(cant) + "</strong></td>"
            + "<td>" + escaparPts(alm) + "</td>"
            + "<td>" + escaparPts(ord) + "</td>"
            + "<td>" + escaparPts(r.urg_plan == null ? "—" : r.urg_plan) + "</td>"
            + '<td class="celda-niveles-pts">'
            + (sinSaldo ? '<span class="text-muted">Sin saldo</span>' : botones)
            + "</td>"
            + "<td>"
            + (sinSaldo
                ? ""
                : '<button type="button" class="btn btn-xs btn-success btnDestinarFilaPts" title="Elegir semana y destinar">'
                    + '<i class="fa fa-calendar"></i> Destinar…</button>')
            + "</td>"
            + '<td><button type="button" class="btn btn-xs btn-danger btnQuitarPriPts" title="Quitar de prioridad"><i class="fa fa-trash"></i></button></td>'
        );
        $tr.data("row", r);
        $tb.append($tr);
    });
    actualizarContadorSelDestPts();
}

function ordenarPriorizadosCachePts() {
    ptsPriorizadosCache.sort(function (a, b) {
        var oa = a.nivel_orden != null ? parseInt(a.nivel_orden, 10) : 99;
        var ob = b.nivel_orden != null ? parseInt(b.nivel_orden, 10) : 99;
        if (oa !== ob) return oa - ob;
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

function upsertPriorizadoCachePts(row) {
    if (!row || !row.id) return;
    var id = String(row.id);
    var idx = -1;
    for (var i = 0; i < ptsPriorizadosCache.length; i++) {
        if (String(ptsPriorizadosCache[i].id) === id) {
            idx = i;
            break;
        }
    }
    if (idx >= 0) {
        ptsPriorizadosCache[idx] = row;
    } else {
        ptsPriorizadosCache.push(row);
    }
    ordenarPriorizadosCachePts();
    pintarTablaPriorizadosPts();
}

function quitarPriorizadoCachePts(id) {
    id = String(id);
    ptsPriorizadosCache = ptsPriorizadosCache.filter(function (r) {
        return String(r.id) !== id;
    });
    pintarTablaPriorizadosPts();
}

function cargarPriorizadosPts() {
    return $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "priorizados"
    }, function (resp) {
        if (!resp || !resp.ok) {
            ptsPriorizadosCache = [];
            $("#tablaPriorizadosPts tbody").html(
                '<tr><td colspan="12" class="text-center text-muted">No se pudo cargar</td></tr>'
            );
            actualizarContadorSelDestPts();
            return;
        }
        ptsPriorizadosCache = resp.data || [];
        pintarTablaPriorizadosPts();
    }, "json");
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

function claveSemanaEnvPts(r) {
    return String(r.anio || "") + "-" + String(r.semana || "");
}

function claveGrupoEnvPts(r) {
    return String(r.id_programacion || "") + "|" + String(r.modelo || "") + "|" + String(r.cod_color || "");
}

function actualizarContadorSelEnvPts() {
    var n = 0;
    $("#tablaEnviarPts tbody .chkEnvPts:checked").each(function () {
        var qty = parseInt($(this).closest("tr").find(".qtyEnvPts").val(), 10) || 0;
        if (qty > 0) n += 1;
    });
    $("#nSelEnvPts").text("(" + n + ")");
    $("#btnMandarEnvPts").prop("disabled", n < 1);
    $("#tablaEnviarPts tbody tr.pts-grupo-env").each(function () {
        var grupo = String($(this).attr("data-grupo") || "");
        var $chks = $("#tablaEnviarPts tbody .chkEnvPts").filter(function () {
            return String($(this).attr("data-grupo") || "") === grupo;
        });
        var total = $chks.length;
        var marcados = $chks.filter(":checked").length;
        $(this).find(".chkGrupoEnvPts").prop("checked", total > 0 && marcados === total);
    });
    $("#tablaEnviarPts tbody tr.pts-semana-env").each(function () {
        var sem = String($(this).attr("data-semana") || "");
        var $chks = $("#tablaEnviarPts tbody .chkEnvPts").filter(function () {
            return String($(this).attr("data-semana") || "") === sem;
        });
        var total = $chks.length;
        var marcados = $chks.filter(":checked").length;
        $(this).find(".chkSemanaEnvPts").prop("checked", total > 0 && marcados === total);
    });
}

function pintarTablaEnviarPts() {
    var $tb = $("#tablaEnviarPts tbody").empty();
    $("#chkTodosEnvPts").prop("checked", false);
    var data = ptsEnviarCache || [];
    if (!data.length) {
        $tb.html('<tr><td colspan="9" class="text-center text-muted">Nada programado con saldo en almacén de corte en ese rango de semanas.</td></tr>');
        $("#resumenEnvPts").text("");
        actualizarContadorSelEnvPts();
        return;
    }

    var semanas = [];
    var mapaSem = {};
    data.forEach(function (r) {
        var sKey = claveSemanaEnvPts(r);
        if (!mapaSem[sKey]) {
            mapaSem[sKey] = { key: sKey, row: r, grupos: [], mapaG: {} };
            semanas.push(mapaSem[sKey]);
        }
        var sem = mapaSem[sKey];
        var gKey = String(r.id_programacion || claveGrupoEnvPts(r));
        if (!sem.mapaG[gKey]) {
            sem.mapaG[gKey] = { key: gKey, row: r, items: [] };
            sem.grupos.push(sem.mapaG[gKey]);
        }
        sem.mapaG[gKey].items.push(r);
    });

    var nColores = 0;
    var nTallas = data.length;
    semanas.forEach(function (sem) {
        nColores += sem.grupos.length;
        var totalAlmSem = 0;
        var nTallasSem = 0;
        sem.grupos.forEach(function (g) {
            g.items.forEach(function (it) {
                totalAlmSem += parseInt(it.alm_corte, 10) || 0;
                nTallasSem += 1;
            });
        });
        var r0 = sem.row;
        var $sem = $('<tr class="pts-semana-env" data-semana="' + escaparPts(sem.key) + '">');
        $sem.append(
            '<td><input type="checkbox" class="chkSemanaEnvPts" data-semana="'
            + escaparPts(sem.key) + '" title="Toda esta semana"></td>'
            + '<td colspan="8">Semana ' + escaparPts(r0.semana)
            + " · " + escaparPts(r0.anio)
            + (r0.fecha_inicio ? " · " + escaparPts(r0.fecha_inicio) + " → " + escaparPts(r0.fecha_fin || "") : "")
            + " · " + sem.grupos.length + " color(es) · " + nTallasSem + " talla(s) · " + totalAlmSem + " uds</td>"
        );
        $tb.append($sem);

        sem.grupos.forEach(function (g) {
            var r = g.row;
            var totalAlm = 0;
            g.items.forEach(function (it) {
                totalAlm += parseInt(it.alm_corte, 10) || 0;
            });
            var taller = (r.cod_sector || "") + (r.nom_sector ? " — " + r.nom_sector : "");
            var estiloFila = estiloFilaTallerPts(r);
            var $h = $('<tr class="pts-grupo-env" data-grupo="' + escaparPts(g.key)
                + '" data-semana="' + escaparPts(sem.key) + '"'
                + (estiloFila ? ' style="' + estiloFila + '"' : "") + ">");
            $h.append(
                '<td><input type="checkbox" class="chkGrupoEnvPts" data-grupo="'
                + escaparPts(g.key) + '" data-semana="' + escaparPts(sem.key)
                + '" title="Todas las tallas de este color"></td>'
                + "<td>" + badgeNivelPts(r.nivel, r.nivel_nombre, r.nivel_color) + "</td>"
                + celdaTallerHtmlPts(r, taller)
                + "<td>" + escaparPts(r.modelo) + "</td>"
                + "<td>" + escaparPts(etiquetaColorPts(r)) + "</td>"
                + '<td colspan="4"><span class="text-muted">'
                + g.items.length + " talla(s) · " + totalAlm + " uds en corte</span>"
                + (r.nombre_modelo ? ' <span class="text-muted">· ' + escaparPts(r.nombre_modelo) + "</span>" : "")
                + "</td>"
            );
            $tb.append($h);

            g.items.forEach(function (it) {
                var alm = parseInt(it.alm_corte, 10) || 0;
                var $tr = $('<tr data-grupo="' + escaparPts(g.key) + '" data-semana="' + escaparPts(sem.key) + '">');
                $tr.append(
                    '<td><input type="checkbox" class="chkEnvPts" data-grupo="' + escaparPts(g.key)
                    + '" data-semana="' + escaparPts(sem.key) + '"></td>'
                    + "<td></td><td></td><td></td><td></td>"
                    + "<td>" + escaparPts(it.talla || it.cod_talla || "—") + "</td>"
                    + "<td>" + escaparPts(it.articulo) + "</td>"
                    + "<td>" + escaparPts(alm) + "</td>"
                    + '<td><input type="number" class="form-control input-sm qtyEnvPts" min="1" max="'
                    + alm + '" value="' + alm + '"></td>'
                );
                $tr.data("row", it);
                $tb.append($tr);
            });
        });
    });

    $("#resumenEnvPts").text(
        semanas.length + " semana(s) · " + nColores + " color(es) · " + nTallas + " talla(s) con saldo"
    );
    actualizarContadorSelEnvPts();
}

function payloadRangoEnvPts() {
    return {
        anio_desde: anioEnvDesdePts(),
        semana_desde: semanaEnvDesdePts(),
        anio_hasta: anioEnvHastaPts(),
        semana_hasta: semanaEnvHastaPts()
    };
}

function cargarArticulosEnviarPts() {
    var $tb = $("#tablaEnviarPts tbody");
    $tb.html(
        '<tr><td colspan="9" class="text-center text-muted">'
        + '<i class="fa fa-spinner fa-spin"></i> Cargando artículos…</td></tr>'
    );
    var payload = payloadRangoEnvPts();
    payload.accion = "articulosEnviar";
    payload.cod_sector = $("#envFiltroTallerPts").val() || "";
    payload.nivel = $("#envFiltroNivelPts").val() || "";
    payload.modelo = $("#envFiltroModeloPts").val() || "";
    return $.post("ajax/programacion-taller-semana.ajax.php", payload, function (resp) {
        if (!resp || !resp.ok) {
            ptsEnviarCache = [];
            $tb.html('<tr><td colspan="9" class="text-center text-muted">No se pudo cargar</td></tr>');
            $("#resumenEnvPts").text("");
            actualizarContadorSelEnvPts();
            return;
        }
        ptsEnviarCache = resp.data || [];
        pintarTablaEnviarPts();
    }, "json").fail(function () {
        ptsEnviarCache = [];
        $tb.html('<tr><td colspan="9" class="text-center text-muted">Error de comunicación</td></tr>');
        $("#resumenEnvPts").text("");
        actualizarContadorSelEnvPts();
    });
}

function itemsEnviarSeleccionadosPts() {
    var items = [];
    $("#tablaEnviarPts tbody .chkEnvPts:checked").each(function () {
        var $tr = $(this).closest("tr");
        var row = $tr.data("row");
        if (!row || !row.articulo) return;
        var $qty = $tr.find(".qtyEnvPts");
        var qty = parseInt($qty.val(), 10) || 0;
        var max = parseInt($qty.attr("max"), 10) || 0;
        if (qty < 1) return;
        if (max > 0 && qty > max) qty = max;
        items.push({
            articulo: row.articulo,
            cantidad: qty,
            id_programacion: row.id_programacion || 0
        });
    });
    return items;
}

function mandarLoteEnvPts() {
    var items = itemsEnviarSeleccionadosPts();
    if (!items.length) {
        toastPts("Marca al menos una talla", "warning");
        return;
    }
    var necesitaGuia = false;
    var semanas = {};
    $("#tablaEnviarPts tbody .chkEnvPts:checked").each(function () {
        var row = $(this).closest("tr").data("row");
        if (row && parseInt(row.es_interno, 10) === 1) {
            necesitaGuia = true;
        }
        if (row && row.semana) {
            semanas[String(row.anio) + "-" + String(row.semana)] = true;
        }
    });
    var guia = $.trim($("#envGuiaPts").val() || "");
    if (necesitaGuia && !guia) {
        toastPts("Indica la guía (taller interno)", "warning");
        $("#envGuiaPts").focus();
        return;
    }

    var nSem = 0;
    for (var k in semanas) {
        if (semanas.hasOwnProperty(k)) nSem += 1;
    }
    var textoConfirm = items.length + " talla(s) de " + nSem + " semana(s) saldrán del almacén de corte al taller programado.";

    var go = function () {
        $("#btnMandarEnvPts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Mandando…');
        $("#tablaEnviarPts tbody .chkEnvPts:checked").closest("tr").addClass("programando-pts");
        var payload = payloadRangoEnvPts();
        payload.accion = "mandarTallerLote";
        payload.guia = guia;
        payload.items = JSON.stringify(items);
        $.post("ajax/programacion-taller-semana.ajax.php", payload, function (resp) {
            $("#btnMandarEnvPts").html('Mandar seleccionados <span id="nSelEnvPts">(0)</span>');
            if (resp && resp.ok) {
                toastPts(resp.mensaje || "Enviado a taller", "success");
                if (resp.errores && resp.errores.length) {
                    console.warn("Avisos envío PTS", resp.errores);
                }
                cargarArticulosEnviarPts();
                cargarProgramadosPts({ stats: false });
                cargarEstadisticasPts();
            } else {
                toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo mandar a taller", "danger");
                $("#tablaEnviarPts tbody tr").removeClass("programando-pts");
                actualizarContadorSelEnvPts();
            }
        }, "json").fail(function () {
            $("#btnMandarEnvPts").html('Mandar seleccionados <span id="nSelEnvPts">(0)</span>');
            toastPts("Error de comunicación", "danger");
            $("#tablaEnviarPts tbody tr").removeClass("programando-pts");
            actualizarContadorSelEnvPts();
        });
    };

    if (typeof swal === "function") {
        swal({
            title: "¿Mandar a taller?",
            text: textoConfirm,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00a65a",
            confirmButtonText: "Sí, mandar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result && result.value) go();
        });
        return;
    }
    if (window.confirm(textoConfirm)) go();
}

function recargarPts(opts) {
    opts = opts || {};
    var pProg = cargarProgramadosPts({ stats: false });
    var pDisp = cargarDisponiblesPts();
    var pPri = cargarPriorizadosPts();
    var pNe = (tabActivaPts() === "no_ejecutado")
        ? cargarNoEjecutadosPts()
        : refrescarBadgeNoEjecPts();
    var pEnv = (tabActivaPts() === "enviar")
        ? cargarArticulosEnviarPts()
        : $.Deferred().resolve().promise();
    var pStats = opts.stats === false
        ? $.Deferred().resolve().promise()
        : cargarEstadisticasPts();
    return $.when(pProg, pDisp, pPri, pNe, pEnv, pStats).always(function () {
        if (ptsInicializado) {
            sincronizarUrlPts();
        }
        actualizarContadorSelDestPts();
    });
}

function payloadPriorizarPts(row, nivel, cantidad) {
    return {
        accion: "priorizar",
        modelo: row.modelo || "",
        cod_color: row.cod_color || "",
        cod_sector: row.cod_sector_resuelto || row.cod_sector || "",
        nivel: nivel,
        cantidad: cantidad,
        observacion: ""
    };
}

function priorizarFilaPts($tr, nivel) {
    var row = $tr.data("row");
    if (!row || !nivel) return;
    if (!row.cod_sector_resuelto) {
        toastPts("Sin taller configurado para este modelo/color", "warning");
        return;
    }
    var cant = parseInt(row.saldo_disponible, 10) || 0;
    if (cant < 1) {
        toastPts("Sin total disponible para priorizar", "warning");
        return;
    }

    $tr.addClass("programando-pts");
    $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", true);

    $.post("ajax/programacion-taller-semana.ajax.php", payloadPriorizarPts(row, nivel, cant), function (resp) {
        if (resp && resp.ok) {
            $tr.removeClass("programando-pts").addClass("ok-pts");
            toastPts((row.modelo || "") + " / " + (row.cod_color || "") + " → priorizado", "success");
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
                upsertPriorizadoCachePts(resp.row);
            } else {
                cargarPriorizadosPts();
            }
        } else {
            $tr.removeClass("programando-pts");
            $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", false);
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo priorizar", "danger");
        }
    }, "json").fail(function () {
        $tr.removeClass("programando-pts");
        $tr.find(".btnNivelRapidoPts, .chkDispPts").prop("disabled", false);
        toastPts("Error de comunicación", "danger");
    });
}

function priorizarLotePts() {
    var nivel = nivelLoteSeleccionadoPts();
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

    $("#btnProgramarLotePts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Priorizando…');
    $filas.forEach(function ($tr) { $tr.addClass("programando-pts"); });

    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "priorizarLote",
        items: JSON.stringify(items)
    }, function (resp) {
        $("#btnProgramarLotePts").html('Priorizar seleccionados (<span id="nSelPts">0</span>)');
        if (resp && resp.ok) {
            toastPts(resp.mensaje || "Lote priorizado", "success");
            if (resp.errores && resp.errores.length) {
                console.warn("Errores lote prioridad PTS", resp.errores);
            }
            $.when(cargarDisponiblesPts(), cargarPriorizadosPts()).always(function () {
                actualizarContadorSelPts();
            });
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo priorizar el lote", "danger");
            $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
            actualizarContadorSelPts();
        }
    }, "json").fail(function () {
        $("#btnProgramarLotePts").html('Priorizar seleccionados (<span id="nSelPts">0</span>)');
        toastPts("Error de comunicación", "danger");
        $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
        actualizarContadorSelPts();
    });
}

function cambiarNivelPrioridadPts($tr, nivel) {
    var row = $tr.data("row");
    if (!row || !row.id || !nivel) return;
    if (String(row.nivel) === String(nivel)) return;
    $tr.addClass("programando-pts");
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "editarPrioridad",
        id: row.id,
        nivel: nivel
    }, function (resp) {
        $tr.removeClass("programando-pts");
        if (resp && resp.ok && resp.row) {
            toastPts("Nivel actualizado", "success");
            upsertPriorizadoCachePts(resp.row);
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo cambiar nivel", "danger");
        }
    }, "json").fail(function () {
        $tr.removeClass("programando-pts");
        toastPts("Error de comunicación", "danger");
    });
}

function destinarConSemanaPts(idPri, anio, semana, $tr) {
    anio = parseInt(anio, 10) || 0;
    semana = parseInt(semana, 10) || 0;
    if (!idPri || !anio || !semana) {
        toastPts("Elige año y semana destino", "warning");
        return;
    }
    if ($tr) {
        $tr.addClass("programando-pts");
        $tr.find("button, .chkPriPts").prop("disabled", true);
    }
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "destinarSemana",
        id_prioridad: idPri,
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok) {
            $("#modalDestinarPts").modal("hide");
            toastPts(resp.mensaje || "Destinado", "success");
            quitarPriorizadoCachePts(idPri);
            // Si Ya programado está en esa semana, refrescar listado/stats
            if (anio === anioProgPts() && semana === semanaProgPts()) {
                if (resp.row) {
                    upsertProgramadoCachePts(resp.row);
                } else {
                    cargarProgramadosPts({ stats: false });
                }
                programarRefreshStatsPts();
            }
        } else {
            if ($tr) {
                $tr.removeClass("programando-pts");
                $tr.find("button, .chkPriPts").prop("disabled", false);
            }
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo destinar", "danger");
        }
    }, "json").fail(function () {
        if ($tr) {
            $tr.removeClass("programando-pts");
            $tr.find("button, .chkPriPts").prop("disabled", false);
        }
        toastPts("Error de comunicación", "danger");
    });
}

function abrirModalDestinarPts(row) {
    if (!row || !row.id) return;
    $("#destModalIdPri").val(row.id);
    $("#destModalArticulo").text(
        (row.modelo || "") + " / " + etiquetaColorPts(row)
    );
    $("#destModalNivel").html(
        "Nivel: " + badgeNivelPts(row.nivel, row.nivel_nombre, row.nivel_color)
    );
    $("#destModalAnio").val(anioDestPts() || anioPts());
    $("#destModalSemana").val(semanaDestPts() || semanaPts());
    actualizarRangoModalDestPts();
    $("#modalDestinarPts").modal("show");
}

function actualizarRangoModalDestPts() {
    var anio = parseInt($("#destModalAnio").val(), 10) || 0;
    var semana = parseInt($("#destModalSemana").val(), 10) || 0;
    if (!anio || !semana) {
        $("#destModalRango").text("—");
        $("#destModalAvisoPasada").hide();
        return;
    }
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#destModalRango").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            var pasada = parseInt(resp.data.pasada, 10) === 1;
            $("#destModalAvisoPasada").toggle(pasada);
            $("#formDestinarPts").data("pasada", pasada ? 1 : 0);
        } else {
            $("#destModalRango").text("Semana no válida");
            $("#destModalAvisoPasada").show();
            $("#formDestinarPts").data("pasada", 1);
        }
    }, "json");
}

function destinarLotePts() {
    var anio = anioDestPts();
    var semana = semanaDestPts();
    if (!anio || !semana) {
        toastPts("Elige año y semana destino en la barra", "warning");
        return;
    }
    if (semanaDestPasadaPts()) {
        toastPts("No se puede destinar a una semana que ya pasó", "warning");
        return;
    }
    var ids = [];
    var $filas = [];
    $("#tablaPriorizadosPts tbody tr").each(function () {
        var $tr = $(this);
        if (!$tr.find(".chkPriPts").is(":checked")) return;
        var row = $tr.data("row");
        if (!row || !row.id) return;
        ids.push(row.id);
        $filas.push($tr);
    });
    if (!ids.length) {
        toastPts("Selecciona al menos uno", "warning");
        return;
    }
    var htmlBtn = 'Destinar seleccionados <span id="nSelDestPts">(0)</span>';
    $("#btnDestinarLotePts").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Destinando…');
    $filas.forEach(function ($tr) { $tr.addClass("programando-pts"); });
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "destinarLote",
        anio: anio,
        semana: semana,
        ids: JSON.stringify(ids)
    }, function (resp) {
        $("#btnDestinarLotePts").html(htmlBtn);
        if (resp && resp.ok) {
            toastPts(resp.mensaje || "Lote destinado", "success");
            $.when(cargarPriorizadosPts()).always(function () {
                actualizarContadorSelDestPts();
                if (anio === anioProgPts() && semana === semanaProgPts()) {
                    cargarProgramadosPts({ stats: false });
                    programarRefreshStatsPts();
                }
            });
        } else {
            toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo destinar el lote", "danger");
            $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
            actualizarContadorSelDestPts();
        }
    }, "json").fail(function () {
        $("#btnDestinarLotePts").html(htmlBtn);
        toastPts("Error de comunicación", "danger");
        $filas.forEach(function ($tr) { $tr.removeClass("programando-pts"); });
        actualizarContadorSelDestPts();
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
    var $taller = $("#ptsTaller");
    $taller.val(taller);
    try {
        if ($taller.data("selectpicker")) {
            $taller.selectpicker("val", taller);
        } else {
            refrescarSelectPts($taller);
        }
    } catch (e) {
        refrescarSelectPts($taller);
    }
    $("#ptsNivel").val(esEdicion && row.nivel ? row.nivel : "");
    var anioEdit = esEdicion && row.anio ? row.anio : anioProgPts();
    var semEdit = esEdicion && row.semana ? row.semana : semanaProgPts();
    $("#ptsAnio").val(anioEdit).data("anio-orig", anioEdit);
    $("#ptsSemana").val(semEdit).data("semana-orig", semEdit);
    $("#ptsObservacion").val(row.observacion || "");
    actualizarRangoModalEditPts();
    $("#modalProgramarPts").modal("show");
}

function actualizarRangoModalEditPts() {
    var anio = parseInt($("#ptsAnio").val(), 10) || 0;
    var semana = parseInt($("#ptsSemana").val(), 10) || 0;
    if (!anio || !semana) {
        $("#ptsRangoSemana").text("—");
        $("#ptsAvisoPasada").hide();
        $("#formProgramarPts").data("pasada", 0);
        return;
    }
    $.post("ajax/programacion-taller-semana.ajax.php", {
        accion: "infoSemana",
        anio: anio,
        semana: semana
    }, function (resp) {
        if (resp && resp.ok && resp.data) {
            $("#ptsRangoSemana").text(resp.data.fecha_inicio + " → " + resp.data.fecha_fin);
            var pasada = parseInt(resp.data.pasada, 10) === 1;
            var anioOrig = parseInt($("#ptsAnio").data("anio-orig"), 10) || 0;
            var semOrig = parseInt($("#ptsSemana").data("semana-orig"), 10) || 0;
            var misma = anio === anioOrig && semana === semOrig;
            var bloquear = pasada && !misma;
            $("#ptsAvisoPasada").toggle(bloquear);
            $("#formProgramarPts").data("pasada", bloquear ? 1 : 0);
        } else {
            $("#ptsRangoSemana").text("Semana no válida");
            $("#ptsAvisoPasada").show();
            $("#formProgramarPts").data("pasada", 1);
        }
    }, "json");
}

if ($("#filtroAnioPts").length) {
    $.when(cargarNivelesPts(), cargarSectoresPts(), cargarModelosPts()).always(function () {
        aplicarEstadoInicialPts();
        // Alinear selector de Ya programado con cabecera al iniciar
        $("#progAnioPts").val(anioPts());
        $("#progSemanaPts").val(semanaPts());
        $.when(
            actualizarRangoPts(),
            actualizarRangoDestPts(),
            actualizarRangoProgPts(),
            actualizarRangoEnvPts(),
            actualizarRangoNePts(),
            refrescarBadgeNoEjecPts()
        ).always(function () {
            recargarPts().always(function () {
                ptsInicializado = true;
                sincronizarUrlPts();
            });
        });
    });
}

function limpiarFiltrosProgramadoPts(silencio) {
    var $nivel = $("#progFiltroNivelPts");
    var $taller = $("#progFiltroTallerPts");
    var $modelo = $("#progFiltroModeloPts");
    $nivel.val("");
    $taller.val("");
    $modelo.val("");
    try {
        if ($nivel.data("selectpicker")) $nivel.selectpicker("val", "");
        if ($taller.data("selectpicker")) $taller.selectpicker("val", "");
        if ($modelo.data("selectpicker")) $modelo.selectpicker("val", "");
    } catch (e) {
        refrescarSelectPts($nivel);
        refrescarSelectPts($taller);
        refrescarSelectPts($modelo);
    }
    pintarTablaProgramadosPts();
    if (!silencio) {
        toastPts("Filtros de Ya programado limpiados", "success");
    }
}

function limpiarFiltrosEnviarPts(silencio) {
    var $nivel = $("#envFiltroNivelPts");
    var $taller = $("#envFiltroTallerPts");
    var $modelo = $("#envFiltroModeloPts");
    $nivel.val("");
    $taller.val("");
    $modelo.val("");
    try {
        if ($nivel.data("selectpicker")) $nivel.selectpicker("val", "");
        if ($taller.data("selectpicker")) $taller.selectpicker("val", "");
        if ($modelo.data("selectpicker")) $modelo.selectpicker("val", "");
    } catch (e) {
        refrescarSelectPts($nivel);
        refrescarSelectPts($taller);
        refrescarSelectPts($modelo);
    }
    if (tabActivaPts() === "enviar") {
        cargarArticulosEnviarPts();
    }
    if (!silencio) {
        toastPts("Filtros de Enviar a taller limpiados", "success");
    }
}

function limpiarFiltrosDestinarPts(silencio) {
    var $nivel = $("#destFiltroNivelPts");
    var $taller = $("#destFiltroTallerPts");
    var $modelo = $("#destFiltroModeloPts");
    $nivel.val("");
    $taller.val("");
    $modelo.val("");
    try {
        if ($nivel.data("selectpicker")) $nivel.selectpicker("val", "");
        if ($taller.data("selectpicker")) $taller.selectpicker("val", "");
        if ($modelo.data("selectpicker")) $modelo.selectpicker("val", "");
    } catch (e) {
        refrescarSelectPts($nivel);
        refrescarSelectPts($taller);
        refrescarSelectPts($modelo);
    }
    pintarTablaPriorizadosPts();
    if (!silencio) {
        toastPts("Filtros de destinar limpiados", "success");
    }
}

function limpiarFiltrosPts() {
    ptsUrlSyncLock = true;
    var $modelo = $("#filtroModeloPts");
    var $taller = $("#filtroTallerPts");
    var $nivel = $("#filtroNivelPts");
    $modelo.val("");
    $taller.val("");
    $nivel.val("");
    try {
        if ($modelo.data("selectpicker")) {
            $modelo.selectpicker("val", "");
        } else {
            refrescarSelectPts($modelo);
        }
        if ($taller.data("selectpicker")) {
            $taller.selectpicker("val", "");
        } else {
            refrescarSelectPts($taller);
        }
    } catch (e) {
        refrescarSelectPts($modelo);
        refrescarSelectPts($taller);
    }
    ptsUrlSyncLock = false;
    limpiarFiltrosDestinarPts(true);
    limpiarFiltrosProgramadoPts(true);
    limpiarFiltrosEnviarPts(true);
    recargarPts();
    toastPts("Filtros limpiados", "success");
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
$("#btnLimpiarFiltrosPts").on("click", limpiarFiltrosPts);
$("#btnExcelPts").on("click", function (e) {
    e.preventDefault();
    window.location.href = urlExcelPts();
});
$("#filtroAnioPts, #filtroSemanaPts").on("change", function () {
    // Semana del resumen: stats + Excel; alinea Ya programado
    sincronizarProgConCabeceraPts();
    $.when(actualizarRangoPts(), actualizarRangoProgPts()).always(function () {
        cargarEstadisticasPts();
        if (tabActivaPts() === "programado") {
            mostrarCargandoProgramadosPts();
            cargarProgramadosPts({ stats: false });
        }
        sincronizarUrlPts();
    });
});
$("#destAnioPts, #destSemanaPts").on("change", function () {
    actualizarRangoDestPts();
});
$("#btnDestSemAntPts").on("click", function () { moverSemanaDestPts(-1); });
$("#btnDestSemSigPts").on("click", function () { moverSemanaDestPts(1); });
$("#neAnioPts, #neSemanaPts").on("change", function () {
    actualizarRangoNePts();
});
$("#btnNeSemAntPts").on("click", function () { moverSemanaNePts(-1); });
$("#btnNeSemSigPts").on("click", function () { moverSemanaNePts(1); });
$("#progAnioPts, #progSemanaPts").on("change", function () {
    mostrarCargandoProgramadosPts();
    actualizarRangoProgPts().always(function () {
        sincronizarCabeceraConProgPts();
        actualizarRangoPts();
        cargarProgramadosPts({ stats: false });
        programarRefreshStatsPts();
        sincronizarUrlPts();
    });
});
$("#btnProgSemAntPts").on("click", function () { moverSemanaProgPts(-1); });
$("#btnProgSemSigPts").on("click", function () { moverSemanaProgPts(1); });
$("#envAnioDesdePts, #envSemanaDesdePts, #envAnioHastaPts, #envSemanaHastaPts").on("change", function () {
    $(".btnPresetEnvPts").removeClass("btn-primary").addClass("btn-default");
    actualizarRangoEnvPts().always(function () {
        cargarArticulosEnviarPts();
    });
});
$("#btnEnvDesdeAntPts").on("click", function () { moverSemanaEnvDesdePts(-1); });
$("#btnEnvDesdeSigPts").on("click", function () { moverSemanaEnvDesdePts(1); });
$("#btnEnvHastaAntPts").on("click", function () { moverSemanaEnvHastaPts(-1); });
$("#btnEnvHastaSigPts").on("click", function () { moverSemanaEnvHastaPts(1); });
$(document).on("click", ".btnPresetEnvPts", function () {
    aplicarPresetEnvPts($(this).attr("data-semanas"));
});
$("#envFiltroNivelPts, #envFiltroTallerPts, #envFiltroModeloPts").on("changed.bs.select change", function () {
    if (ptsUrlSyncLock) return;
    cargarArticulosEnviarPts();
});
$("#filtroTallerPts, #filtroModeloPts, #filtroNivelPts").on("changed.bs.select change", function () {
    if (ptsUrlSyncLock) return;
    recargarPts();
});
$("#destFiltroNivelPts, #destFiltroTallerPts, #destFiltroModeloPts").on("changed.bs.select change", function () {
    pintarTablaPriorizadosPts();
});
$("#progFiltroNivelPts, #progFiltroTallerPts, #progFiltroModeloPts").on("changed.bs.select change", function () {
    pintarTablaProgramadosPts();
});
$("#chkOcultarConsumidosPts").on("change", function () {
    pintarTablaProgramadosPts();
    sincronizarUrlPts();
});

$("#tabsPts a[data-toggle='tab']").on("shown.bs.tab", function () {
    sincronizarUrlPts();
    var tab = tabActivaPts();
    if (tab === "programado") {
        // Feedback inmediato; rango y listado en paralelo
        mostrarCargandoProgramadosPts();
        actualizarRangoProgPts();
        cargarProgramadosPts({ stats: false });
        programarRefreshStatsPts();
        refrescarSelectPts($("#progFiltroNivelPts"));
        refrescarSelectPts($("#progFiltroTallerPts"));
        refrescarSelectPts($("#progFiltroModeloPts"));
    } else if (tab === "enviar") {
        actualizarRangoEnvPts();
        cargarArticulosEnviarPts();
        refrescarSelectPts($("#envFiltroNivelPts"));
        refrescarSelectPts($("#envFiltroTallerPts"));
        refrescarSelectPts($("#envFiltroModeloPts"));
    } else if (tab === "no_ejecutado") {
        actualizarRangoNePts();
        cargarNoEjecutadosPts();
    } else if (tab === "destinar") {
        cargarPriorizadosPts();
        actualizarRangoDestPts();
        actualizarContadorSelDestPts();
        refrescarSelectPts($("#destFiltroNivelPts"));
        refrescarSelectPts($("#destFiltroTallerPts"));
        refrescarSelectPts($("#destFiltroModeloPts"));
    } else if (tab === "priorizar") {
        cargarDisponiblesPts();
    }
});

$("#chkTodosDispPts").on("change", function () {
    var on = $(this).is(":checked");
    $("#tablaDisponiblesPts tbody .chkDispPts:not(:disabled)").prop("checked", on);
    actualizarContadorSelPts();
});
$(document).on("change", ".chkDispPts", actualizarContadorSelPts);
$(document).on("change", ".chkNivelLotePts", function () {
    var $chk = $(this);
    if ($chk.is(":checked")) {
        $("#chkNivelesLotePts .chkNivelLotePts").not($chk).prop("checked", false);
        setNivelLotePts($chk.val());
    } else {
        setNivelLotePts("");
    }
});
$("#btnProgramarLotePts").on("click", priorizarLotePts);

$("#chkTodosPriPts").on("change", function () {
    var on = $(this).is(":checked");
    $("#tablaPriorizadosPts tbody .chkPriPts:not(:disabled)").prop("checked", on);
    actualizarContadorSelDestPts();
});
$(document).on("change", ".chkPriPts", actualizarContadorSelDestPts);
$("#btnDestinarLotePts").on("click", destinarLotePts);

$(document).on("click", "#tablaDisponiblesPts .btnNivelRapidoPts", function () {
    var nivel = $(this).attr("data-nivel");
    var $tr = $(this).closest("tr");
    priorizarFilaPts($tr, nivel);
});

$(document).on("click", "#tablaPriorizadosPts .btnNivelRapidoPts", function () {
    var nivel = $(this).attr("data-nivel");
    var $tr = $(this).closest("tr");
    cambiarNivelPrioridadPts($tr, nivel);
});

$(document).on("click", ".btnDestinarFilaPts", function () {
    var row = $(this).closest("tr").data("row");
    abrirModalDestinarPts(row);
});

$("#destModalAnio, #destModalSemana").on("change", actualizarRangoModalDestPts);

$("#formDestinarPts").on("submit", function (e) {
    e.preventDefault();
    if (parseInt($("#formDestinarPts").data("pasada"), 10) === 1) {
        toastPts("No se puede destinar a una semana que ya pasó", "warning");
        return;
    }
    var idPri = parseInt($("#destModalIdPri").val(), 10) || 0;
    var anio = parseInt($("#destModalAnio").val(), 10) || 0;
    var semana = parseInt($("#destModalSemana").val(), 10) || 0;
    var $tr = null;
    $("#tablaPriorizadosPts tbody tr").each(function () {
        var row = $(this).data("row");
        if (row && String(row.id) === String(idPri)) {
            $tr = $(this);
            return false;
        }
    });
    destinarConSemanaPts(idPri, anio, semana, $tr);
});

$(document).on("click", ".btnQuitarPriPts", function () {
    var $btn = $(this);
    var $tr = $btn.closest("tr");
    var row = $tr.data("row");
    if (!row || !row.id || $tr.hasClass("programando-pts")) return;
    var go = function () {
        $tr.addClass("programando-pts");
        $.post("ajax/programacion-taller-semana.ajax.php", {
            accion: "eliminarPrioridad",
            id: row.id
        }, function (resp) {
            if (resp && resp.ok) {
                toastPts(resp.mensaje || "Quitado", "success");
                quitarPriorizadoCachePts(row.id);
                if (resp.candidato) {
                    upsertDisponibleCachePts(resp.candidato);
                }
            } else {
                $tr.removeClass("programando-pts");
                toastPts((resp && resp.mensaje) ? resp.mensaje : "No se pudo quitar", "danger");
            }
        }, "json").fail(function () {
            $tr.removeClass("programando-pts");
            toastPts("Error de comunicación", "danger");
        });
    };
    if (typeof swal === "function") {
        swal({
            title: "¿Quitar de la prioridad?",
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
    if (window.confirm("¿Quitar de la prioridad?")) go();
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
    if (parseInt($("#formProgramarPts").data("pasada"), 10) === 1) {
        toastPts("No se puede mover a una semana que ya pasó", "warning");
        return;
    }
    var payload = {
        accion: id ? "editar" : "programar",
        id: id || "",
        anio: $("#ptsAnio").val(),
        semana: $("#ptsSemana").val(),
        modelo: $("#ptsModelo").val(),
        cod_color: $("#ptsCodColor").val(),
        cod_sector: $("#ptsTaller").val(),
        nivel: $("#ptsNivel").val(),
        observacion: $("#ptsObservacion").val()
    };
    $.post("ajax/programacion-taller-semana.ajax.php", payload, function (resp) {
        if (resp && resp.ok) {
            $("#modalProgramarPts").modal("hide");
            if (resp.row) {
                var mismaSemana = parseInt(resp.row.anio, 10) === anioProgPts()
                    && parseInt(resp.row.semana, 10) === semanaProgPts();
                if (mismaSemana) {
                    upsertProgramadoCachePts(resp.row);
                } else {
                    quitarProgramadoCachePts(resp.row.id);
                }
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

$("#ptsAnio, #ptsSemana").on("change", actualizarRangoModalEditPts);

$("#modalProgramarPts").on("shown.bs.modal", function () {
    var $taller = $("#ptsTaller");
    refrescarSelectPts($taller);
    try {
        if ($taller.data("selectpicker")) {
            $taller.selectpicker("val", $taller.val());
        }
    } catch (e) {}
});

$("#chkTodosNePts").on("change", function () {
    var on = $(this).is(":checked");
    $("#tablaNoEjecPts tbody .chkNePts").prop("checked", on);
    actualizarContadorSelNePts();
});
$(document).on("change", ".chkNePts", actualizarContadorSelNePts);
$("#btnMoverNeLotePts").on("click", moverNoEjecutadoLotePts);
$("#btnDevolverNeLotePts").on("click", devolverNoEjecutadoLotePts);

$(document).on("click", ".btnMoverNePts", function () {
    var row = $(this).closest("tr").data("row");
    abrirModalMoverNePts(row);
});
$(document).on("click", ".btnDevolverNePts", function () {
    var $tr = $(this).closest("tr");
    var row = $tr.data("row");
    if (!row || !row.id) return;
    var go = function () { devolverNoEjecutadoPts(row.id, $tr); };
    if (typeof swal === "function") {
        swal({
            title: "¿Devolver a prioridad?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#f39c12",
            confirmButtonText: "Sí, devolver",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result && result.value) go();
        });
        return;
    }
    if (window.confirm("¿Devolver a prioridad?")) go();
});

$("#neModalAnio, #neModalSemana").on("change", actualizarRangoModalNePts);
$("#formMoverNePts").on("submit", function (e) {
    e.preventDefault();
    if (parseInt($("#formMoverNePts").data("pasada"), 10) === 1) {
        toastPts("No se puede mover a una semana que ya pasó", "warning");
        return;
    }
    var id = parseInt($("#neModalId").val(), 10) || 0;
    var anio = parseInt($("#neModalAnio").val(), 10) || 0;
    var semana = parseInt($("#neModalSemana").val(), 10) || 0;
    var $tr = null;
    $("#tablaNoEjecPts tbody tr").each(function () {
        var row = $(this).data("row");
        if (row && String(row.id) === String(id)) {
            $tr = $(this);
            return false;
        }
    });
    if ($tr) $tr.addClass("programando-pts");
    moverNoEjecutadoPts(id, anio, semana, $tr);
});

$("#chkTodosEnvPts").on("change", function () {
    var on = $(this).is(":checked");
    $("#tablaEnviarPts tbody .chkEnvPts, #tablaEnviarPts tbody .chkGrupoEnvPts, #tablaEnviarPts tbody .chkSemanaEnvPts").prop("checked", on);
    actualizarContadorSelEnvPts();
});
$(document).on("change", "#tablaEnviarPts .chkSemanaEnvPts", function () {
    var on = $(this).is(":checked");
    var sem = String($(this).attr("data-semana") || "");
    $("#tablaEnviarPts tbody .chkEnvPts, #tablaEnviarPts tbody .chkGrupoEnvPts").filter(function () {
        return String($(this).attr("data-semana") || "") === sem;
    }).prop("checked", on);
    actualizarContadorSelEnvPts();
});
$(document).on("change", "#tablaEnviarPts .chkGrupoEnvPts", function () {
    var on = $(this).is(":checked");
    var grupo = String($(this).attr("data-grupo") || "");
    $("#tablaEnviarPts tbody .chkEnvPts").filter(function () {
        return String($(this).attr("data-grupo") || "") === grupo;
    }).prop("checked", on);
    actualizarContadorSelEnvPts();
});
$(document).on("change", "#tablaEnviarPts .chkEnvPts", actualizarContadorSelEnvPts);
$(document).on("change", "#tablaEnviarPts .qtyEnvPts", actualizarContadorSelEnvPts);
$("#btnMandarEnvPts").on("click", mandarLoteEnvPts);
