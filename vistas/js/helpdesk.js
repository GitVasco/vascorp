$(function () {
    if ($("#panelHelpdesk").length === 0) {
        return;
    }

    var API = "ajax/helpdesk.ajax.php";
    var permisos = { ver: true, registrar: false, gestionar: false, pulir_ia: false, reabrir: false };
    var ticketActual = null;
    var agentes = [];
    var usuarios = [];
    var archivosSeleccionados = [];

    var LABELS_ESTADO = {
        ABIERTO: { cls: "label-primary", txt: "Abierto" },
        EN_PROGRESO: { cls: "label-warning", txt: "En progreso" },
        ESPERANDO_USUARIO: { cls: "label-info", txt: "Esperando usuario" },
        CERRADO: { cls: "label-default", txt: "Cerrado" }
    };

    var LABELS_TIPO = {
        INCIDENCIA: "Incidencia",
        REQUERIMIENTO: "Requerimiento",
        CONSULTA: "Consulta",
        OTRO: "Otro",
        DESARROLLO: "Desarrollo",
        CORRECCION: "Corrección",
        SOPORTE: "Soporte"
    };

    var catalogos = { areas: [], sistemas: [] };
    var sistemaActual = "VASCORP";

    var LABELS_SISTEMA = {
        VASCORP: "Vascorp",
        SISTEMA_VASCO: "Sistema Vasco",
        VASCOPRO: "VascoPro",
        TI_EMPRESA: "TI / Soporte"
    };

    /** Etiqueta en el hilo según rol del agente asignable */
    var LABELS_AGENTE = {
        6: { txt: "Desarrollo", cls: "label-primary" },
        10: { txt: "Soporte", cls: "label-warning" }
    };

    var EXT_OK = ["jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx"];
    var MAX_BYTES = 10 * 1024 * 1024;
    var MAX_FILES = 5;

    function esc(v) {
        return $("<div>").text(v == null ? "" : String(v)).html();
    }

    function toast(tipo, msg) {
        if (window.toastr) {
            toastr[tipo](msg);
            return;
        }
        alert(msg);
    }

    function badgeEstado(estado) {
        var info = LABELS_ESTADO[estado] || { cls: "label-default", txt: estado };
        return '<span class="label ' + info.cls + '">' + esc(info.txt) + "</span>";
    }

    function badgePrioridad(p) {
        return '<span class="label label-prioridad-' + esc(p) + '">' + esc(p) + "</span>";
    }

    function iconoTipo(tipo) {
        var map = {
            INCIDENCIA: "fa-exclamation-triangle",
            REQUERIMIENTO: "fa-cogs",
            CONSULTA: "fa-question-circle",
            OTRO: "fa-ellipsis-h",
            DESARROLLO: "fa-code",
            CORRECCION: "fa-wrench",
            SOPORTE: "fa-life-ring"
        };
        return map[tipo] || "fa-ticket";
    }

    function iconoSistema(sis) {
        var map = {
            VASCORP: "fa-desktop",
            SISTEMA_VASCO: "fa-cloud",
            VASCOPRO: "fa-rocket",
            TI_EMPRESA: "fa-headphones"
        };
        return map[sis] || "fa-cube";
    }

    function destroyPickers($root) {
        $($root).find("select.selectpicker").each(function () {
            var $s = $(this);
            if ($s.data("selectpicker")) {
                $s.selectpicker("destroy");
            }
        });
    }

    function initPicker($sel) {
        var $sel = $($sel);
        if (!$.fn.selectpicker) {
            return;
        }
        if ($sel.data("selectpicker")) {
            $sel.selectpicker("refresh");
        } else {
            $sel.selectpicker({
                container: "body",
                sanitize: false
            });
        }
    }

    function post(accion, data) {
        data = data || {};
        data.accion = accion;
        return $.ajax({
            url: API + "?accion=" + encodeURIComponent(accion),
            method: "POST",
            dataType: "json",
            data: data
        });
    }

    function aplicarPermisosUi() {
        if (!permisos.registrar) {
            $("#hdTabNuevoLi").hide();
            $("#hdVistaNuevo").removeClass("active");
            $("#hdTabListaLi").addClass("active");
            $("#hdVistaLista").addClass("active");
            $("#hdTabs a[href='#hdVistaLista']").tab("show");
        } else {
            $("#hdTabNuevoLi").show();
        }
        if (permisos.gestionar) {
            $(".hd-solo-gestionar").show();
            $("#hdTabListaLabel, #hdListaTitulo").text("Bandeja");
        } else {
            $(".hd-solo-gestionar").hide();
            $("#hdTabListaLabel, #hdListaTitulo").text("Mis tickets");
        }
        if (permisos.pulir_ia) {
            $("#hdBtnPulir, #hdBtnPulirResp").show();
            $("#hdVistaNuevo .hd-pulir-bar").show();
        } else {
            $("#hdBtnPulir, #hdBtnPulirResp").hide();
            $("#hdVistaNuevo .hd-pulir-bar").hide();
        }
    }

    function fillSelect($sel, items, placeholder, valueKey, labelFn) {
        var $sel = $($sel);
        var current = $sel.val();
        $sel.empty();
        if (placeholder !== null) {
            $sel.append('<option value="">' + esc(placeholder) + "</option>");
        }
        (items || []).forEach(function (it) {
            var val = it[valueKey];
            $sel.append('<option value="' + esc(val) + '">' + esc(labelFn(it)) + "</option>");
        });
        if (current) {
            $sel.val(current);
        }
        refreshPicker($sel);
    }

    function renderFileList() {
        var $ul = $("#hdFileList").empty();
        archivosSeleccionados.forEach(function (f, idx) {
            $ul.append(
                "<li>" +
                    "<span>" + esc(f.name) + " <small class=\"text-muted\">(" + Math.round(f.size / 1024) + " KB)</small></span>" +
                    '<button type="button" class="hd-file-remove" data-idx="' + idx + '">&times;</button>' +
                "</li>"
            );
        });
    }

    function agregarArchivos(fileList) {
        var files = Array.prototype.slice.call(fileList || []);
        files.forEach(function (f) {
            if (archivosSeleccionados.length >= MAX_FILES) {
                toast("warning", "Máximo " + MAX_FILES + " archivos.");
                return;
            }
            var ext = (f.name.split(".").pop() || "").toLowerCase();
            if (EXT_OK.indexOf(ext) < 0) {
                toast("error", "Formato no permitido: " + ext);
                return;
            }
            if (f.size > MAX_BYTES) {
                toast("error", "Máximo 10 MB por archivo: " + f.name);
                return;
            }
            var dup = archivosSeleccionados.some(function (x) {
                return x.name === f.name && x.size === f.size;
            });
            if (!dup) {
                archivosSeleccionados.push(f);
            }
        });
        renderFileList();
    }

    function refreshPicker($sel) {
        var $sel = $($sel);
        if ($.fn.selectpicker && $sel.hasClass("selectpicker")) {
            $sel.selectpicker("refresh");
        }
    }

    function fillOptions($sel, items, placeholder) {
        var current = $sel.val();
        $sel.empty();
        $sel.append('<option value="">' + esc(placeholder) + "</option>");
        (items || []).forEach(function (v) {
            $sel.append('<option value="' + esc(v) + '">' + esc(v) + "</option>");
        });
        if (current) {
            $sel.val(current);
        }
        refreshPicker($sel);
    }

    function fillModulosGrouped($sel, grupos, placeholder) {
        var current = $sel.val();
        $sel.empty();
        $sel.append('<option value="">' + esc(placeholder) + "</option>");
        (grupos || []).forEach(function (g) {
            var seccion = (g && g.seccion) ? String(g.seccion) : "Otros";
            var $og = $('<optgroup>').attr("label", seccion);
            (g.items || []).forEach(function (item) {
                $og.append('<option value="' + esc(item) + '">' + esc(item) + "</option>");
            });
            $sel.append($og);
        });
        if (current) {
            $sel.val(current);
        }
        refreshPicker($sel);
    }

    function modulosDelSistema(sistemaId) {
        var lista = catalogos.sistemas || [];
        for (var i = 0; i < lista.length; i++) {
            if (lista[i].id === sistemaId) {
                return lista[i].modulos || [];
            }
        }
        return [];
    }

    function aplicarSistema(sistemaId) {
        sistemaActual = sistemaId || "VASCORP";
        $("#hdSistema").val(sistemaActual);
        $("#hdSistemaCards .hd-sis-card").removeClass("active");
        $('#hdSistemaCards .hd-sis-card[data-sistema="' + sistemaActual + '"]').addClass("active");
        fillModulosGrouped($("#hdModulo"), modulosDelSistema(sistemaActual), "Seleccionar módulo");
    }

    function resetFormAlta() {
        $("#hdFormAlta")[0].reset();
        $("#hdTipo").val("INCIDENCIA");
        $("#hdTipoCards .hd-tipo-card").removeClass("active");
        $('#hdTipoCards .hd-tipo-card[data-tipo="INCIDENCIA"]').addClass("active");
        $("#hdPrioridad").val("MEDIA");
        archivosSeleccionados = [];
        renderFileList();
        $("#hdAdjuntos").val("");
        fillOptions($("#hdArea"), catalogos.areas, "Seleccionar área");
        aplicarSistema("VASCORP");
    }

    function cargarBase() {
        return post("catalogos").done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            if (res.permisos) {
                permisos = res.permisos;
            }
            agentes = res.agentes || [];
            usuarios = res.usuarios || [];
            if (res.catalogos) {
                catalogos.areas = res.catalogos.areas || [];
                catalogos.sistemas = res.catalogos.sistemas || [];
            }
            aplicarPermisosUi();
            fillOptions($("#hdArea"), catalogos.areas, "Seleccionar área");
            aplicarSistema(sistemaActual);
            fillSelect($("#hdAsignadoAlta"), agentes, "Sin asignar", "id", function (u) {
                return u.nombre || ("#" + u.id);
            });
            fillSelect($("#hdSolicitante"), usuarios, "Yo (sesión actual)", "id", function (u) {
                return (u.nombre || "") + (u.usuario ? " (" + u.usuario + ")" : "");
            });
        });
    }

    function renderLista(items) {
        var $tb = $("#hdTablaLista tbody").empty();
        if (!items || !items.length) {
            $tb.append('<tr class="hd-vacio"><td colspan="7" class="text-muted text-center">Sin tickets.</td></tr>');
            return;
        }
        items.forEach(function (t) {
            var activa = ticketActual && Number(ticketActual.id) === Number(t.id) ? " hd-row-activa" : "";
            var sub = [];
            if (t.sistema) {
                sub.push(esc(LABELS_SISTEMA[t.sistema] || t.sistema));
            }
            if (t.area) {
                sub.push(esc(t.area));
            }
            if (t.modulo) {
                sub.push(esc(t.modulo));
            }
            $tb.append(
                '<tr class="hd-row' + activa + '" data-id="' + esc(t.id) + '">' +
                    "<td>#" + esc(t.id) + "</td>" +
                    "<td>" + esc(t.titulo) +
                        (sub.length ? "<br><small class=\"text-muted\">" + sub.join(" · ") + "</small>" : "") +
                    "</td>" +
                    "<td>" + esc(LABELS_TIPO[t.tipo] || t.tipo) + "</td>" +
                    "<td>" + badgePrioridad(t.prioridad) + "</td>" +
                    "<td>" + badgeEstado(t.estado) + "</td>" +
                    "<td>" + esc(t.asignado_nombre || "—") + "</td>" +
                    '<td><button type="button" class="btn btn-xs btn-info hd-btn-ver">Ver</button></td>' +
                "</tr>"
            );
        });
    }

    function cargarLista() {
        var estado = $("#hdFiltroEstado").val();
        var data = {
            tipo: $("#hdFiltroTipo").val(),
            q: $.trim($("#hdFiltroQ").val())
        };
        if (estado === "__ACTIVOS__") {
            data.solo_abiertos = 1;
        } else {
            data.estado = estado;
        }
        return post("listar", data)
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo listar.");
                    return;
                }
                if (res.permisos) {
                    permisos = res.permisos;
                    aplicarPermisosUi();
                }
                renderLista(res.items || []);
            })
            .fail(function () {
                toast("error", "Error de red al listar.");
            });
    }

    function iniciales(nombre) {
        var p = String(nombre || "?").trim().split(/\s+/);
        if (p.length >= 2) {
            return (p[0].charAt(0) + p[1].charAt(0)).toUpperCase();
        }
        return String(nombre || "?").substring(0, 2).toUpperCase();
    }

    var ignoreHashChange = false;
    var hdBooting = true;

    function parseHash() {
        var h = String(location.hash || "").replace(/^#/, "");
        var m = h.match(/^ticket\/(\d+)$/i);
        if (m) {
            return { vista: "ticket", id: Number(m[1]) };
        }
        if (h === "nuevo") {
            return { vista: "nuevo" };
        }
        return { vista: "lista" };
    }

    function setHash(vista, ticketId) {
        var h = "#lista";
        if (vista === "nuevo") {
            h = "#nuevo";
        } else if (vista === "ticket" && ticketId) {
            h = "#ticket/" + ticketId;
        }
        if (location.hash === h) {
            return;
        }
        ignoreHashChange = true;
        if (window.history && history.replaceState) {
            history.replaceState(null, "", location.pathname + location.search + h);
        } else {
            location.hash = h.slice(1);
        }
        setTimeout(function () {
            ignoreHashChange = false;
        }, 80);
    }

    function mostrarVistaConversacion(ticketId) {
        $("#hdVistaNuevo, #hdVistaLista").removeClass("active");
        $("#hdTabNuevoLi, #hdTabListaLi").removeClass("active");
        $("#hdVistaConversacion").addClass("active");
        if (permisos.gestionar) {
            $(".hd-solo-gestionar").show();
        } else {
            $(".hd-solo-gestionar").hide();
        }
        if (ticketId) {
            setHash("ticket", ticketId);
        }
    }

    function mostrarVistaLista(actualizarHash) {
        $("#hdVistaConversacion").removeClass("active");
        $("#hdVistaNuevo").removeClass("active");
        $("#hdTabNuevoLi").removeClass("active");
        $("#hdTabListaLi").addClass("active");
        $("#hdVistaLista").addClass("active");
        ticketActual = null;
        if (actualizarHash !== false) {
            setHash("lista");
        }
        cargarLista();
    }

    function mostrarVistaNuevo(actualizarHash) {
        if (!permisos.registrar) {
            mostrarVistaLista(actualizarHash);
            return;
        }
        $("#hdVistaConversacion").removeClass("active");
        $("#hdVistaLista").removeClass("active");
        $("#hdTabListaLi").removeClass("active");
        $("#hdTabNuevoLi").addClass("active");
        $("#hdVistaNuevo").addClass("active");
        ticketActual = null;
        if (actualizarHash !== false) {
            setHash("nuevo");
        }
    }

    function aplicarHashActual() {
        var estado = parseHash();
        if (estado.vista === "ticket" && estado.id > 0) {
            if (ticketActual && Number(ticketActual.id) === estado.id
                && $("#hdVistaConversacion").hasClass("active")) {
                return;
            }
            verTicket(estado.id);
            return;
        }
        if (estado.vista === "nuevo") {
            mostrarVistaNuevo(false);
            return;
        }
        mostrarVistaLista(false);
    }

    function metaEvento(tipo) {
        var map = {
            ALTA: { txt: "Creación", icon: "fa-plus-circle", cls: "hd-hist-alta" },
            COMENTARIO: { txt: "Comentario", icon: "fa-comment", cls: "hd-hist-comentario" },
            CAMBIO_ESTADO: { txt: "Cambio de estado", icon: "fa-exchange", cls: "hd-hist-estado" },
            ASIGNACION: { txt: "Asignación", icon: "fa-user", cls: "hd-hist-asignacion" }
        };
        return map[tipo] || { txt: tipo || "Evento", icon: "fa-circle", cls: "hd-hist-otro" };
    }

    function mensajeHistorialAmigable(c) {
        var msg = String(c.mensaje || "");
        if (c.tipo_evento === "CAMBIO_ESTADO") {
            var m = msg.match(/Estado:\s*([A-Z_]+)\s*(?:→|->)\s*([A-Z_]+)/i);
            if (m) {
                var de = (LABELS_ESTADO[m[1]] && LABELS_ESTADO[m[1]].txt) || m[1];
                var a = (LABELS_ESTADO[m[2]] && LABELS_ESTADO[m[2]].txt) || m[2];
                return "Pasó de <strong>" + esc(de) + "</strong> a <strong>" + esc(a) + "</strong>";
            }
        }
        return esc(msg).replace(/\n/g, "<br>");
    }

    function renderHistorial(comentarios) {
        if (!comentarios || !comentarios.length) {
            return '<div class="hd-hist-vacio"><i class="fa fa-history"></i><p>Sin historial aún.</p></div>';
        }
        var html = '<div class="hd-timeline">';
        comentarios.forEach(function (c) {
            var meta = metaEvento(c.tipo_evento);
            var rol = LABELS_AGENTE[c.usuario_id] || LABELS_AGENTE[String(c.usuario_id)];
            html +=
                '<div class="hd-timeline-item ' + meta.cls + '">' +
                    '<div class="hd-timeline-dot"><i class="fa ' + meta.icon + '"></i></div>' +
                    '<div class="hd-timeline-card">' +
                        '<div class="hd-timeline-head">' +
                            '<span class="label ' + (
                                c.tipo_evento === "ALTA" ? "label-success" :
                                c.tipo_evento === "CAMBIO_ESTADO" ? "label-info" :
                                c.tipo_evento === "ASIGNACION" ? "label-primary" :
                                "label-default"
                            ) + '">' + esc(meta.txt) + "</span>" +
                            '<span class="hd-timeline-fecha"><i class="fa fa-clock-o"></i> ' +
                                esc(c.creado_en) + "</span>" +
                        "</div>" +
                        '<div class="hd-timeline-who">' +
                            '<span class="hd-msg-avatar hd-hist-avatar">' +
                                esc(iniciales(c.usuario_nombre)) + "</span>" +
                            "<strong>" + esc(c.usuario_nombre || ("#" + c.usuario_id)) + "</strong>" +
                            (rol ? ' <span class="label ' + rol.cls + '">' + esc(rol.txt) + "</span>" : "") +
                        "</div>" +
                        '<div class="hd-timeline-msg">' + mensajeHistorialAmigable(c) + "</div>" +
                    "</div>" +
                "</div>";
        });
        html += "</div>";
        return html;
    }

    function renderAdjuntosTab(adjuntos) {
        if (!adjuntos || !adjuntos.length) {
            return '<p class="text-muted">Sin archivos adjuntos.</p>';
        }
        var html = '<ul class="hd-adjuntos-lista">';
        adjuntos.forEach(function (a) {
            var url = API + "?accion=adjunto&id=" + encodeURIComponent(a.id);
            html +=
                "<li><a href=\"" + esc(url) + "\" target=\"_blank\">" +
                '<i class="fa fa-paperclip"></i> ' + esc(a.nombre_original) +
                "</a> <small class=\"text-muted\">(" + Math.round((a.tamanio || 0) / 1024) + " KB)</small></li>";
        });
        html += "</ul>";
        return html;
    }

    function renderHilo(t, comentarios, agentesLista) {
        var html = "";
        // Mensaje inicial = descripción del ticket
        html +=
            '<div class="hd-msg hd-msg-solicitante">' +
                '<div class="hd-msg-avatar">' + esc(iniciales(t.solicitante_nombre)) + "</div>" +
                '<div class="hd-msg-body">' +
                    '<div class="hd-msg-meta"><strong>' + esc(t.solicitante_nombre || "Solicitante") +
                    '</strong> <span class="text-muted">' + esc(t.creado_en) + "</span></div>" +
                    '<div class="hd-msg-text">' + esc(t.descripcion).replace(/\n/g, "<br>") + "</div>" +
                "</div>" +
            "</div>";

        (comentarios || []).forEach(function (c) {
            if (c.tipo_evento === "ALTA") {
                return;
            }
            if (c.tipo_evento === "CAMBIO_ESTADO" || c.tipo_evento === "ASIGNACION") {
                html +=
                    '<div class="hd-msg-sistema">' +
                        '<span class="label label-default">' + esc(c.tipo_evento) + "</span> " +
                        esc(c.mensaje) +
                        ' <small class="text-muted">' + esc(c.creado_en) + "</small>" +
                    "</div>";
                return;
            }
            var esDelEquipo = false;
            (agentesLista || []).forEach(function (a) {
                if (String(a.id) === String(c.usuario_id)) {
                    esDelEquipo = true;
                }
            });
            if (!esDelEquipo && String(c.usuario_id) !== String(t.solicitante_id)) {
                esDelEquipo = !!LABELS_AGENTE[c.usuario_id] || permisos.gestionar;
            }
            var cls = esDelEquipo || String(c.usuario_id) !== String(t.solicitante_id)
                ? "hd-msg-agente"
                : "hd-msg-solicitante";
            var etiqueta = "";
            if (esDelEquipo || String(c.usuario_id) !== String(t.solicitante_id)) {
                var rol = LABELS_AGENTE[c.usuario_id] || LABELS_AGENTE[String(c.usuario_id)]
                    || { txt: "TI", cls: "label-default" };
                etiqueta = ' <span class="label ' + rol.cls + '">' + esc(rol.txt) + "</span>";
            }
            html +=
                '<div class="hd-msg ' + cls + '">' +
                    '<div class="hd-msg-avatar">' + esc(iniciales(c.usuario_nombre)) + "</div>" +
                    '<div class="hd-msg-body">' +
                        '<div class="hd-msg-meta"><strong>' + esc(c.usuario_nombre || ("#" + c.usuario_id)) +
                        "</strong> <span class=\"text-muted\">" + esc(c.creado_en) + "</span>" +
                        etiqueta +
                        "</div>" +
                        '<div class="hd-msg-text">' + esc(c.mensaje).replace(/\n/g, "<br>") + "</div>" +
                    "</div>" +
                "</div>";
        });
        return html;
    }

    function renderConversacion(res) {
        var t = res.ticket;
        ticketActual = t;
        agentes = res.agentes || agentes;

        $("#hdConvCabecera").html(
            '<div class="hd-conv-head">' +
                '<div>' +
                    '<span class="hd-ticket-id">#' + esc(t.id) + "</span> " +
                    badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) +
                    '<h3 class="hd-conv-asunto">' + esc(t.titulo) + "</h3>" +
                    '<div class="text-muted hd-conv-meta">' +
                        "Creado por <strong>" + esc(t.solicitante_nombre || ("#" + t.solicitante_id)) + "</strong>" +
                        (t.area ? " · " + esc(t.area) : "") +
                        " · " + esc(t.creado_en) +
                    "</div>" +
                "</div>" +
            "</div>"
        );

        $("#hdHilo").html(renderHilo(t, res.comentarios || [], agentes));
        $("#hdBadgeArchivos").text((res.adjuntos || []).length);

        $("#hdTabDetalles").html(
            '<div class="hd-detalles-view">' +
                '<div class="hd-det-chips">' +
                    '<div class="hd-det-chip hd-det-chip-tipo">' +
                        '<i class="fa ' + iconoTipo(t.tipo) + '"></i>' +
                        '<div><span class="hd-det-chip-lbl">Tipo</span>' +
                        '<strong>' + esc(LABELS_TIPO[t.tipo] || t.tipo) + "</strong></div>" +
                    "</div>" +
                    '<div class="hd-det-chip hd-det-chip-prioridad">' +
                        '<i class="fa fa-flag"></i>' +
                        '<div><span class="hd-det-chip-lbl">Prioridad</span>' +
                        badgePrioridad(t.prioridad) + "</div>" +
                    "</div>" +
                    '<div class="hd-det-chip hd-det-chip-estado">' +
                        '<i class="fa fa-info-circle"></i>' +
                        '<div><span class="hd-det-chip-lbl">Estado</span>' +
                        badgeEstado(t.estado) + "</div>" +
                    "</div>" +
                    '<div class="hd-det-chip hd-det-chip-sistema">' +
                        '<i class="fa ' + iconoSistema(t.sistema) + '"></i>' +
                        '<div><span class="hd-det-chip-lbl">Sistema</span>' +
                        '<strong>' + esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                    "</div>" +
                "</div>" +
                '<div class="hd-det-info-row">' +
                    '<div class="hd-det-info-item"><i class="fa fa-building text-aqua"></i> ' +
                        '<span>Área</span><strong>' + esc(t.area || "—") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-cubes text-green"></i> ' +
                        '<span>Módulo</span><strong>' + esc(t.modulo || "—") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-user text-blue"></i> ' +
                        '<span>Asignado</span><strong>' + esc(t.asignado_nombre || "Sin asignar") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-clock-o text-muted"></i> ' +
                        '<span>Creado</span><strong>' + esc(t.creado_en || "—") + "</strong></div>" +
                "</div>" +
                '<div class="hd-det-block">' +
                    '<h4><i class="fa fa-align-left"></i> Descripción</h4>' +
                    '<div class="hd-det-text">' + esc(t.descripcion).replace(/\n/g, "<br>") + "</div>" +
                "</div>" +
                (t.pasos_reproducir
                    ? '<div class="hd-det-block hd-det-block-pasos">' +
                        '<h4><i class="fa fa-list-ol"></i> Pasos para reproducir</h4>' +
                        '<div class="hd-det-text">' + esc(t.pasos_reproducir).replace(/\n/g, "<br>") + "</div>" +
                      "</div>"
                    : "") +
            "</div>"
        );
        $("#hdTabArchivos").html(renderAdjuntosTab(res.adjuntos || []));
        $("#hdTabHistorial").html(renderHistorial(res.comentarios || []));

        // Sidebar gestión
        var cerrado = t.estado === "CERRADO";
        var estadoSel = cerrado ? "EN_PROGRESO" : t.estado;
        var optsEstado = "";
        ["ABIERTO", "EN_PROGRESO", "ESPERANDO_USUARIO", "CERRADO"].forEach(function (e) {
            if (cerrado && e === "CERRADO") {
                return;
            }
            var info = LABELS_ESTADO[e] || { cls: "label-default", txt: e };
            optsEstado +=
                '<option value="' + e + '"' + (e === estadoSel ? " selected" : "") +
                " data-content=\"<span class='label " + info.cls + "'>" + esc(info.txt) + "</span>\">" +
                esc(info.txt) + "</option>";
        });
        var optsAsig = '<option value="">Sin asignar</option>';
        agentes.forEach(function (u) {
            optsAsig +=
                '<option value="' + esc(u.id) + '"' +
                (String(u.id) === String(t.asignado_id || "") ? " selected" : "") +
                ">" + esc(u.nombre || ("#" + u.id)) + "</option>";
        });
        var optsPri = "";
        ["BAJA", "MEDIA", "ALTA"].forEach(function (p) {
            optsPri +=
                '<option value="' + p + '"' + (p === t.prioridad ? " selected" : "") +
                " data-content=\"<span class='label label-prioridad-" + p + "'>" + p + "</span>\">" +
                esc(p) + "</option>";
        });

        destroyPickers("#hdConvSidebarBody");

        if (permisos.gestionar && !cerrado) {
            $("#hdConvSidebarBody").html(
                '<form id="hdFormGestionar" class="hd-side-form">' +
                    '<div class="hd-side-badges">' +
                        badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) +
                    "</div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-exchange text-aqua"></i> Estado</label>' +
                        '<select class="form-control selectpicker" id="hdGestEstado" data-width="100%">' +
                        optsEstado + "</select></div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-flag text-red"></i> Prioridad</label>' +
                        '<select class="form-control selectpicker" id="hdGestPrioridad" data-width="100%">' +
                        optsPri + "</select></div>" +
                    '<div class="form-group hd-side-asignar">' +
                        '<label><i class="fa fa-user text-aqua"></i> Asignar a</label>' +
                        '<p class="help-block">Responsable de atender el ticket</p>' +
                        '<select class="form-control selectpicker" id="hdGestAsignado" data-width="100%" ' +
                        'data-live-search="true" title="Sin asignar">' +
                        optsAsig + "</select></div>" +
                    '<div class="hd-side-meta">' +
                        '<div><i class="fa fa-building"></i> <span>Área</span> <strong>' + esc(t.area || "—") + "</strong></div>" +
                        '<div><i class="fa fa-desktop"></i> <span>Sistema</span> <strong>' +
                            esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                        '<div><i class="fa fa-cubes"></i> <span>Módulo</span> <strong>' + esc(t.modulo || "—") + "</strong></div>" +
                    "</div>" +
                    '<button type="submit" class="btn btn-primary btn-block">' +
                        '<i class="fa fa-save"></i> Guardar cambios</button>' +
                "</form>"
            );
            initPicker("#hdGestEstado");
            initPicker("#hdGestPrioridad");
            initPicker("#hdGestAsignado");
        } else if (permisos.reabrir && cerrado) {
            $("#hdConvSidebarBody").html(
                '<form id="hdFormGestionar" class="hd-side-form">' +
                    '<div class="hd-side-badges">' +
                        badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) +
                    "</div>" +
                    '<div class="callout callout-warning" style="margin-bottom:12px;padding:8px 12px;">' +
                        '<p style="margin:0;"><i class="fa fa-lock"></i> Ticket cerrado. ' +
                        "Cambiá el estado para reabrirlo.</p></div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-exchange text-aqua"></i> Reabrir como</label>' +
                        '<select class="form-control selectpicker" id="hdGestEstado" data-width="100%">' +
                        optsEstado + "</select></div>" +
                    '<div class="hd-side-meta">' +
                        '<div><i class="fa fa-user"></i> <span>Asignado</span> <strong>' +
                            esc(t.asignado_nombre || "Sin asignar") + "</strong></div>" +
                        '<div><i class="fa fa-flag"></i> <span>Prioridad</span> ' + badgePrioridad(t.prioridad) + "</div>" +
                        '<div><i class="fa fa-building"></i> <span>Área</span> <strong>' + esc(t.area || "—") + "</strong></div>" +
                        '<div><i class="fa fa-desktop"></i> <span>Sistema</span> <strong>' +
                            esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                        '<div><i class="fa fa-cubes"></i> <span>Módulo</span> <strong>' + esc(t.modulo || "—") + "</strong></div>" +
                    "</div>" +
                    '<button type="submit" class="btn btn-warning btn-block">' +
                        '<i class="fa fa-unlock"></i> Reabrir ticket</button>' +
                "</form>"
            );
            initPicker("#hdGestEstado");
        } else {
            $("#hdConvSidebarBody").html(
                (cerrado
                    ? '<div class="callout callout-default" style="margin-bottom:12px;padding:8px 12px;">' +
                      '<p style="margin:0;"><i class="fa fa-lock"></i> Ticket cerrado. Solo lectura.</p></div>'
                    : "") +
                '<div class="hd-side-badges">' + badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) + "</div>" +
                '<div class="hd-side-meta">' +
                    '<div><i class="fa fa-user"></i> <span>Asignado</span> <strong>' +
                        esc(t.asignado_nombre || "Sin asignar") + "</strong></div>" +
                    '<div><i class="fa fa-building"></i> <span>Área</span> <strong>' + esc(t.area || "—") + "</strong></div>" +
                    '<div><i class="fa fa-desktop"></i> <span>Sistema</span> <strong>' +
                        esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                    '<div><i class="fa fa-cubes"></i> <span>Módulo</span> <strong>' + esc(t.modulo || "—") + "</strong></div>" +
                "</div>"
            );
        }

        function correoVisible(c) {
            c = String(c || "").trim();
            return c && c !== "0" && c.indexOf("@") !== -1 ? c : "";
        }
        var mailSol = correoVisible(t.solicitante_correo);
        var mailContacto = correoVisible(t.correo_contacto);
        $("#hdConvSolicitante").html(
            '<div class="hd-solicitante-card">' +
                '<div class="hd-msg-avatar">' + esc(iniciales(t.solicitante_nombre)) + "</div>" +
                '<div><strong>' + esc(t.solicitante_nombre || ("#" + t.solicitante_id)) + "</strong>" +
                (t.solicitante_usuario
                    ? '<br><span class="text-muted">@' + esc(t.solicitante_usuario) + "</span>"
                    : "") +
                (t.area ? '<br><span class="text-muted">' + esc(t.area) + "</span>" : "") +
                (mailSol ? "<br><small>" + esc(mailSol) + "</small>" : "") +
                (mailContacto && mailContacto !== mailSol
                    ? "<br><small>" + esc(mailContacto) + "</small>"
                    : "") +
                "</div></div>"
        );

        $("#hdComentario").val("");
        $("#hdRespEstado").val("");
        if (cerrado) {
            $("#hdResponderBox").hide();
            var msgCerrado = permisos.reabrir
                ? " Para responder, reabrilo desde el panel derecho."
                : " No se pueden agregar respuestas.";
            if ($("#hdCerradoBanner").length === 0) {
                $("#hdHilo").after(
                    '<div id="hdCerradoBanner" class="hd-cerrado-banner">' +
                        '<i class="fa fa-lock"></i> Este ticket está cerrado.' + msgCerrado +
                    "</div>"
                );
            } else {
                $("#hdCerradoBanner").html(
                    '<i class="fa fa-lock"></i> Este ticket está cerrado.' + msgCerrado
                ).show();
            }
        } else {
            $("#hdCerradoBanner").remove();
            $("#hdResponderBox").show();
            initPicker("#hdRespEstado");
            refreshPicker("#hdRespEstado");
        }
        mostrarVistaConversacion(t.id);
        var $hilo = $("#hdHilo");
        $hilo.scrollTop($hilo[0].scrollHeight);
    }

    function verTicket(id) {
        post("ver", { id: id })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo cargar.");
                    return;
                }
                if (res.permisos) {
                    permisos = res.permisos;
                }
                if (res.agentes) {
                    agentes = res.agentes;
                }
                renderConversacion(res);
            })
            .fail(function () {
                toast("error", "Error de red al ver ticket.");
            });
    }

    function mostrarAyudaCard($card, fallbackTitulo) {
        var $card = $($card);
        var titulo = $.trim($card.find("strong").first().text()) || fallbackTitulo;
        var ayuda = $card.attr("data-ayuda") || "Sin descripción.";
        if (window.toastr) {
            toastr.clear();
            toastr.info(ayuda, titulo, { timeOut: 7000, closeButton: true, progressBar: true });
            return;
        }
        alert(titulo + "\n\n" + ayuda);
    }

    // --- Tipo cards ---
    $("#hdTipoCards").on("click", ".hd-tipo-help", function (e) {
        e.preventDefault();
        e.stopPropagation();
        mostrarAyudaCard($(this).closest(".hd-tipo-card"), "Tipo");
    });

    $("#hdTipoCards").on("click", ".hd-tipo-card", function () {
        $("#hdTipoCards .hd-tipo-card").removeClass("active");
        $(this).addClass("active");
        $("#hdTipo").val($(this).data("tipo"));
        mostrarAyudaCard(this, "Tipo");
    });

    // --- Sistema cards ---
    $("#hdSistemaCards").on("click", ".hd-tipo-help", function (e) {
        e.preventDefault();
        e.stopPropagation();
        mostrarAyudaCard($(this).closest(".hd-sis-card"), "Sistema");
    });

    $("#hdSistemaCards").on("click", ".hd-sis-card", function () {
        aplicarSistema($(this).data("sistema"));
        mostrarAyudaCard(this, "Sistema");
    });

    // --- Dropzone ---
    var $dz = $("#hdDropzone");
    $dz.on("dragover dragenter", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dz.addClass("hd-dragover");
    });
    $dz.on("dragleave drop", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dz.removeClass("hd-dragover");
    });
    $dz.on("drop", function (e) {
        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files) {
            agregarArchivos(dt.files);
        }
    });
    $("#hdAdjuntos").on("change", function () {
        agregarArchivos(this.files);
        $(this).val("");
    });
    $("#hdFileList").on("click", ".hd-file-remove", function () {
        var idx = Number($(this).data("idx"));
        archivosSeleccionados.splice(idx, 1);
        renderFileList();
    });

    $("#hdBtnCancelar").on("click", function () {
        resetFormAlta();
    });

    $("#hdBtnRefrescar").on("click", function () {
        cargarLista();
    });

    $("#hdFiltroEstado, #hdFiltroTipo").on("change", function () {
        cargarLista();
    });

    var qTimer = null;
    $("#hdFiltroQ").on("keyup", function () {
        clearTimeout(qTimer);
        qTimer = setTimeout(cargarLista, 350);
    });

    $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
        if (hdBooting) {
            return;
        }
        var href = $(e.target).attr("href");
        if (href === "#hdVistaLista") {
            ticketActual = null;
            $("#hdVistaConversacion").removeClass("active");
            setHash("lista");
            cargarLista();
        } else if (href === "#hdVistaNuevo") {
            ticketActual = null;
            $("#hdVistaConversacion").removeClass("active");
            setHash("nuevo");
        }
    });

    $("#hdTablaLista").on("click", ".hd-btn-ver, tr.hd-row td:not(:last-child)", function () {
        var id = $(this).closest("tr").data("id");
        if (id) {
            verTicket(id);
        }
    });

    $("#hdBtnPulir").on("click", function () {
        if (!permisos.pulir_ia) {
            toast("error", "Sin permiso para corregir con IA.");
            return;
        }
        var titulo = $.trim($("#hdTitulo").val());
        var descripcion = $.trim($("#hdDescripcion").val());
        var pasos = $.trim($("#hdPasos").val());
        if (!titulo && !descripcion && !pasos) {
            toast("warning", "Escriba al menos el asunto o la descripción.");
            return;
        }
        var $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Pulido…');
        post("pulir", {
            titulo: titulo,
            descripcion: descripcion,
            pasos_reproducir: pasos
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo pulir el texto.");
                    return;
                }
                if (res.titulo != null) {
                    $("#hdTitulo").val(res.titulo);
                }
                if (res.descripcion != null) {
                    $("#hdDescripcion").val(res.descripcion);
                }
                if (res.pasos_reproducir != null) {
                    $("#hdPasos").val(res.pasos_reproducir);
                }
                toast("success", res.msg || "Texto pulido.");
            })
            .fail(function () {
                toast("error", "Error de red al pulir con IA.");
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-magic"></i> Pulir texto (IA)');
            });
    });

    $("#hdBtnPulirResp").on("click", function () {
        if (!permisos.pulir_ia) {
            toast("error", "Sin permiso para corregir con IA.");
            return;
        }
        var mensaje = $.trim($("#hdComentario").val());
        if (!mensaje) {
            toast("warning", "Escriba la respuesta a corregir.");
            return;
        }
        var $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Corrigiendo…');
        post("pulir", { modo: "respuesta", mensaje: mensaje })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo corregir.");
                    return;
                }
                if (res.mensaje != null) {
                    $("#hdComentario").val(res.mensaje);
                }
                toast("success", res.msg || "Respuesta corregida.");
            })
            .fail(function () {
                toast("error", "Error de red al corregir con IA.");
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-magic"></i> Corregir con IA');
            });
    });

    $("#hdFormAlta").on("submit", function (e) {
        e.preventDefault();
        if (!permisos.registrar) {
            return;
        }

        var fd = new FormData();
        fd.append("accion", "crear");
        fd.append("titulo", $.trim($("#hdTitulo").val()));
        fd.append("descripcion", $.trim($("#hdDescripcion").val()));
        fd.append("pasos_reproducir", $.trim($("#hdPasos").val()));
        fd.append("tipo", $("#hdTipo").val());
        fd.append("prioridad", $("#hdPrioridad").val());
        fd.append("area", $("#hdArea").val());
        fd.append("modulo", $("#hdModulo").val());
        fd.append("sistema", $("#hdSistema").val() || sistemaActual);

        if (permisos.gestionar) {
            if ($("#hdSolicitante").val()) {
                fd.append("solicitante_id", $("#hdSolicitante").val());
            }
            fd.append("asignado_id", $("#hdAsignadoAlta").val());
        }

        archivosSeleccionados.forEach(function (f) {
            fd.append("adjuntos[]", f);
        });

        $("#hdBtnCrear").prop("disabled", true);
        $.ajax({
            url: API + "?accion=crear",
            method: "POST",
            dataType: "json",
            data: fd,
            processData: false,
            contentType: false
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo crear.");
                    return;
                }
                toast("success", res.msg || "Creado.");
                resetFormAlta();
                if (res.id) {
                    verTicket(res.id);
                } else {
                    mostrarVistaLista();
                }
            })
            .fail(function () {
                toast("error", "Error de red al crear.");
            })
            .always(function () {
                $("#hdBtnCrear").prop("disabled", false);
            });
    });

    $("#hdFormComentar").on("submit", function (e) {
        e.preventDefault();
        if (!ticketActual) {
            return;
        }
        var mensaje = $.trim($("#hdComentario").val());
        if (!mensaje) {
            toast("warning", "Escriba una respuesta.");
            return;
        }
        var data = { id: ticketActual.id, mensaje: mensaje };
        if (permisos.gestionar && $("#hdRespEstado").val()) {
            data.cambiar_estado = $("#hdRespEstado").val();
        }
        $("#hdBtnEnviarResp").prop("disabled", true);
        post("comentar", data)
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo enviar.");
                    return;
                }
                toast("success", res.msg || "Enviado.");
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al responder.");
            })
            .always(function () {
                $("#hdBtnEnviarResp").prop("disabled", false);
            });
    });

    $("#hdConvSidebarBody").on("submit", "#hdFormGestionar", function (e) {
        e.preventDefault();
        if (!ticketActual) {
            return;
        }
        if (ticketActual.estado === "CERRADO") {
            if (!permisos.reabrir) {
                toast("error", "Solo el responsable autorizado puede reabrir el ticket.");
                return;
            }
        } else if (!permisos.gestionar) {
            return;
        }
        var data = {
            id: ticketActual.id,
            estado: $("#hdGestEstado").val()
        };
        if (ticketActual.estado !== "CERRADO") {
            data.asignado_id = $("#hdGestAsignado").val();
            data.prioridad = $("#hdGestPrioridad").val();
        }
        post("actualizar", data)
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo actualizar.");
                    return;
                }
                toast("success", res.msg || "Actualizado.");
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al actualizar.");
            });
    });

    $(window).on("hashchange", function () {
        if (ignoreHashChange || hdBooting) {
            return;
        }
        aplicarHashActual();
    });

    cargarBase().always(function () {
        aplicarHashActual();
        hdBooting = false;
    });
});
