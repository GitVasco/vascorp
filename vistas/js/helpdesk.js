$(function () {
    if ($("#panelHelpdesk").length === 0) {
        return;
    }

    var API = "ajax/helpdesk.ajax.php";
    var permisos = { ver: true, registrar: false, gestionar: false };
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

    function renderHistorial(comentarios) {
        if (!comentarios || !comentarios.length) {
            return '<p class="text-muted">Sin historial.</p>';
        }
        var html = '<ul class="hd-historial">';
        comentarios.forEach(function (c) {
            html +=
                "<li>" +
                    '<div class="hd-hist-meta">' +
                        esc(c.usuario_nombre || ("#" + c.usuario_id)) +
                        " · " + esc(c.creado_en) +
                        " · " + esc(c.tipo_evento) +
                    "</div>" +
                    "<div>" + esc(c.mensaje).replace(/\n/g, "<br>") + "</div>" +
                "</li>";
        });
        html += "</ul>";
        return html;
    }

    function renderAdjuntos(adjuntos) {
        if (!adjuntos || !adjuntos.length) {
            return "";
        }
        var html = "<h4>Adjuntos</h4><ul class=\"hd-adjuntos-lista\">";
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

    function renderDetalle(res) {
        var t = res.ticket;
        ticketActual = t;
        $("#hdDetalleTitulo").text("#" + t.id);
        $("#hdBoxDetalle").show();
        $("#hdBoxDetalleVacio").hide();

        var meta =
            '<div class="hd-detalle-meta">' +
                '<div class="hd-meta-line"><strong>' + esc(t.titulo) + "</strong></div>" +
                '<div class="hd-meta-line">' + badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) +
                    " · " + esc(LABELS_TIPO[t.tipo] || t.tipo) +
                    (t.sistema ? " · " + esc(LABELS_SISTEMA[t.sistema] || t.sistema) : "") +
                    (t.area ? " · " + esc(t.area) : "") +
                "</div>" +
                '<div class="hd-meta-line text-muted">Solicitante: ' + esc(t.solicitante_nombre || ("#" + t.solicitante_id)) +
                    " · Asignado: " + esc(t.asignado_nombre || "—") + "</div>" +
                '<div class="hd-meta-line text-muted">Creado: ' + esc(t.creado_en) +
                    (t.cerrado_en ? " · Cerrado: " + esc(t.cerrado_en) : "") + "</div>" +
                (t.modulo ? '<div class="hd-meta-line">Módulo: ' + esc(t.modulo) + "</div>" : "") +
                '<div class="hd-meta-line" style="margin-top:8px;white-space:pre-wrap;">' + esc(t.descripcion) + "</div>" +
                (t.pasos_reproducir
                    ? '<div class="hd-meta-line" style="margin-top:8px;"><strong>Pasos:</strong><br><span style="white-space:pre-wrap;">' +
                      esc(t.pasos_reproducir) + "</span></div>"
                    : "") +
            "</div>";

        var adj = renderAdjuntos(res.adjuntos || []);
        var hist = "<h4>Historial</h4>" + renderHistorial(res.comentarios || []);

        var formComentario =
            '<form id="hdFormComentar" style="margin-bottom:12px;">' +
                '<div class="form-group">' +
                    '<textarea class="form-control" id="hdComentario" rows="2" ' +
                    'placeholder="Agregar comentario…"></textarea>' +
                "</div>" +
                '<button type="submit" class="btn btn-default btn-sm">' +
                    '<i class="fa fa-comment"></i> Comentar</button>' +
            "</form>";

        var gestionar = "";
        if (permisos.gestionar) {
            var optsEstado = "";
            ["ABIERTO", "EN_PROGRESO", "ESPERANDO_USUARIO", "CERRADO"].forEach(function (e) {
                optsEstado +=
                    '<option value="' + e + '"' + (e === t.estado ? " selected" : "") + ">" +
                    esc((LABELS_ESTADO[e] && LABELS_ESTADO[e].txt) || e) +
                    "</option>";
            });
            var optsAsig = '<option value="">Sin asignar</option>';
            (res.agentes || agentes).forEach(function (u) {
                optsAsig +=
                    '<option value="' + esc(u.id) + '"' +
                    (String(u.id) === String(t.asignado_id || "") ? " selected" : "") +
                    ">" + esc(u.nombre || ("#" + u.id)) + "</option>";
            });
            var optsPri = "";
            ["BAJA", "MEDIA", "ALTA"].forEach(function (p) {
                optsPri +=
                    '<option value="' + p + '"' + (p === t.prioridad ? " selected" : "") + ">" +
                    esc(p) + "</option>";
            });

            gestionar =
                '<div class="hd-gestionar-bar">' +
                    "<h4>Gestionar</h4>" +
                    '<form id="hdFormGestionar" class="form-inline">' +
                        '<div class="form-group"><label>Estado</label> ' +
                            '<select class="form-control input-sm" id="hdGestEstado">' + optsEstado + "</select></div>" +
                        '<div class="form-group"><label>Asignado</label> ' +
                            '<select class="form-control input-sm" id="hdGestAsignado">' + optsAsig + "</select></div>" +
                        '<div class="form-group"><label>Prioridad</label> ' +
                            '<select class="form-control input-sm" id="hdGestPrioridad">' + optsPri + "</select></div>" +
                        '<button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save"></i> Guardar</button>' +
                    "</form>" +
                "</div>";
        }

        $("#hdDetalleCuerpo").html(meta + adj + hist + formComentario + gestionar);
        $("#hdTablaLista tr.hd-row").removeClass("hd-row-activa");
        $('#hdTablaLista tr.hd-row[data-id="' + t.id + '"]').addClass("hd-row-activa");
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
                renderDetalle(res);
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
        if ($(e.target).attr("href") === "#hdVistaLista") {
            cargarLista();
        }
    });

    $("#hdTablaLista").on("click", ".hd-btn-ver, tr.hd-row td:not(:last-child)", function () {
        var id = $(this).closest("tr").data("id");
        if (id) {
            verTicket(id);
        }
    });

    $("#hdBtnPulir").on("click", function () {
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
                $("#hdTabs a[href='#hdVistaLista']").tab("show");
                cargarLista().done(function () {
                    if (res.id) {
                        verTicket(res.id);
                    }
                });
            })
            .fail(function () {
                toast("error", "Error de red al crear.");
            })
            .always(function () {
                $("#hdBtnCrear").prop("disabled", false);
            });
    });

    $("#hdDetalleCuerpo").on("submit", "#hdFormComentar", function (e) {
        e.preventDefault();
        if (!ticketActual) {
            return;
        }
        var mensaje = $.trim($("#hdComentario").val());
        if (!mensaje) {
            toast("warning", "Escriba un comentario.");
            return;
        }
        post("comentar", { id: ticketActual.id, mensaje: mensaje })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo comentar.");
                    return;
                }
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al comentar.");
            });
    });

    $("#hdDetalleCuerpo").on("submit", "#hdFormGestionar", function (e) {
        e.preventDefault();
        if (!ticketActual || !permisos.gestionar) {
            return;
        }
        post("actualizar", {
            id: ticketActual.id,
            estado: $("#hdGestEstado").val(),
            asignado_id: $("#hdGestAsignado").val(),
            prioridad: $("#hdGestPrioridad").val()
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo actualizar.");
                    return;
                }
                toast("success", res.msg || "Actualizado.");
                cargarLista();
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al actualizar.");
            });
    });

    cargarBase().always(function () {
        if (!permisos.registrar) {
            cargarLista();
        }
    });
});
