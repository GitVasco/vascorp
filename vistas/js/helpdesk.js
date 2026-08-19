$(function () {
    if ($("#panelHelpdesk").length === 0) {
        return;
    }

    var API = "ajax/helpdesk.ajax.php";
    var LS_FILTRO_ESTADO = "helpdesk_filtro_estado";
    var ESTADOS_FILTRO_OK = {
        "": true,
        "__ACTIVOS__": true,
        "ABIERTO": true,
        "EN_PROGRESO": true,
        "ESPERANDO_USUARIO": true,
        "CERRADO": true,
        "__VENCIDOS__": true
    };
    var permisos = {
        ver: true,
        registrar: false,
        gestionar: false,
        control_total: false,
        agente_bandeja: false,
        pulir_ia: false,
        reabrir: false,
        eliminar: false
    };
    var ticketActual = null;
    var agentes = [];
    var usuarios = [];
    var archivosSeleccionados = [];
    var archivosRespuesta = [];

    var LABELS_ESTADO = {
        ABIERTO: { cls: "label-primary", txt: "Abierto" },
        EN_PROGRESO: { cls: "label-warning", txt: "En progreso" },
        ESPERANDO_USUARIO: { cls: "label-info", txt: "Esperando" },
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

    /** Guías / ejemplos al crear ticket según tipo */
    var GUIA_ALTA = {
        INCIDENCIA: {
            tituloPh: "Ej: No puedo emitir factura — Error al guardar",
            descPh: "Qué intentabas hacer, qué pasó, mensaje de error (si hubo) y desde cuándo ocurre…",
            pasosPh: "1. Ir a Ventas → Facturas\n2. Completar datos y guardar\n3. Aparece el error “…”",
            pasosLabel: 'Pasos para reproducir <span class="text-muted">(opcional)</span>',
            intro: "No puedes avanzar: error, no carga o se bloquea. Tiene SLA. Si sí abre pero calcula/muestra mal → Corrección.",
            prioridadNota: {
                ALTA: "Alta · ~4 h laborales",
                MEDIA: "Media · ~24 h laborales",
                BAJA: "Baja · ~72 h laborales"
            },
            tips: [
                "Indica módulo, pantalla y el mensaje exacto del error",
                "¿Le pasa a todos o solo a ti?",
                "Adjunta captura del error",
                "Si el sistema abre pero calcula mal → usa Corrección"
            ],
            ejemploAsunto: "No puedo emitir factura — Error al guardar",
            ejemploDesc: "Al guardar una factura en Vascorp aparece “Error de conexión”. Pasa desde hoy en la mañana, con distintos clientes. Antes funcionaba normal.",
            ejemploPasos: "1. Ir a Ventas → Facturas\n2. Elegir cliente y agregar ítems\n3. Clic en Guardar\n4. Sale el mensaje de error"
        },
        REQUERIMIENTO: {
            tituloPh: "Ej: Mover tickets creados por error a otra fecha",
            descPh: "Qué hay que ajustar (dato, acceso, permiso, fecha) y para quién…",
            pasosPh: "1. Situación actual: …\n2. Lo que necesitas: …\n3. Códigos / usuarios afectados: …",
            pasosLabel: 'Detalle del pedido <span class="text-muted">(opcional)</span>',
            intro: "Pedido operativo sobre algo que ya existe: mover fechas, accesos, permisos o ajustes de datos. Sí tiene SLA. No es programar una función nueva.",
            prioridadNota: {
                ALTA: "Alta · ~4 h laborales",
                MEDIA: "Media · ~24 h laborales",
                BAJA: "Baja · ~72 h laborales"
            },
            tips: [
                "Di qué hay que cambiar y a qué valor (ej. fecha origen → fecha destino)",
                "Indica códigos, usuarios o documentos afectados",
                "Si es permiso: qué puede hacer hoy y qué debería poder",
                "Si pides una función nueva → usa Desarrollo"
            ],
            ejemploAsunto: "Mover tickets creados por error a otra fecha",
            ejemploDesc: "Por error registré varios tickets con fecha 05/08. Necesito que se muevan a la fecha 07/08. Lista de IDs o rango: …",
            ejemploPasos: "1. Fecha actual incorrecta: 05/08\n2. Fecha correcta: 07/08\n3. Tickets afectados: #… / rango …"
        },
        CONSULTA: {
            tituloPh: "Ej: ¿Dónde veo el estado de cuenta del cliente?",
            descPh: "Qué quieres saber o lograr, y qué ya intentaste…",
            pasosPh: "1. Lo que busco: …\n2. Dónde ya busqué: …\n3. Duda concreta: …",
            pasosLabel: 'Contexto de la duda <span class="text-muted">(opcional)</span>',
            intro: "Solo una duda de uso o proceso. Tiene SLA. No pidas aquí cambios de datos ni arreglos.",
            prioridadNota: {
                ALTA: "Alta · bloquea una tarea urgente",
                MEDIA: "Media · consulta normal de trabajo",
                BAJA: "Baja · curiosidad o mejora de conocimiento"
            },
            tips: [
                "Formula la pregunta en una frase clara",
                "Indica el sistema y la pantalla donde estás",
                "Cuenta qué ya revisaste",
                "Si descubres un error, cambia el tipo a Incidencia o Corrección"
            ],
            ejemploAsunto: "¿Dónde veo el estado de cuenta del cliente?",
            ejemploDesc: "Necesito saber en qué menú de Vascorp puedo ver la deuda y los abonos de un cliente. Ya revisé Ventas pero no lo encuentro.",
            ejemploPasos: "1. Objetivo: ver deuda del cliente\n2. Revisado: menú Ventas\n3. Duda: ¿está en Cobranzas u otro módulo?"
        },
        OTRO: {
            tituloPh: "Ej: Solicitud que no encaja en las otras categorías",
            descPh: "Explica con claridad qué necesitas y por qué no es incidencia, requerimiento o desarrollo…",
            pasosPh: "1. Contexto: …\n2. Pedido: …\n3. Resultado esperado: …",
            pasosLabel: 'Detalle adicional <span class="text-muted">(opcional)</span>',
            intro: "Úsalo solo si no encaja en incidencia, requerimiento, consulta, desarrollo o corrección.",
            prioridadNota: {
                ALTA: "Alta · urgente",
                MEDIA: "Media · normal",
                BAJA: "Baja · sin apuro"
            },
            tips: [
                "Primero revisa si encaja en otro tipo (casi siempre sí)",
                "Sé concreto: qué pedís y para cuándo",
                "Adjunta archivos si ayudan a entender",
                "Si es función nueva, mejor elige Desarrollo"
            ],
            ejemploAsunto: "Coordinar revisión de equipo / tema especial",
            ejemploDesc: "Necesito coordinar con TI un tema que no es un error ni un desarrollo: …",
            ejemploPasos: "1. Contexto: …\n2. Pedido: …\n3. Fecha tentativa: …"
        },
        DESARROLLO: {
            tituloPh: "Ej: Pedidos por tablet en locales del cliente",
            descPh: "Qué función o mejora necesitas, para quién es, y qué problema resuelve…",
            pasosPh: "1. Situación actual: …\n2. Lo que debería poder hacer: …\n3. Usuarios / áreas: …\n4. Fecha deseada (si hay): …",
            pasosLabel: 'Alcance / criterios <span class="text-muted">(opcional)</span>',
            intro: "Construir o mejorar el sistema (pantallas, funciones, automatizaciones). No usa SLA de horas; TI puede poner una fecha estimada al atenderlo.",
            prioridadNota: {
                ALTA: "Alta · priorizar en la cola de desarrollos (sin SLA de horas)",
                MEDIA: "Media · trabajo planificado normal (sin SLA de horas)",
                BAJA: "Baja · backlog / cuando haya cupo (sin SLA de horas)"
            },
            tips: [
                "Describe el resultado de negocio, no solo “hacer un módulo”",
                "Indica usuarios o área que lo usarán",
                "Si tienes una fecha deseada, menciónala en la descripción",
                "Adjunta boceto, Excel o captura de referencia si tienes"
            ],
            ejemploAsunto: "Pedidos por tablet en locales del cliente",
            ejemploDesc: "Se necesita registrar pedidos desde tablets en los locales, para agilizar revisión, aprobación y preparación. Hoy se hace a mano / por otro canal. Fecha deseada aproximada: 3 semanas.",
            ejemploPasos: "1. Hoy: el pedido se anota fuera del sistema\n2. Debe: crear pedido desde tablet en Sistema Vasco\n3. Usuarios: vendedores de local\n4. Deseado: ~3 semanas"
        },
        CORRECCION: {
            tituloPh: "Ej: El total de la factura calcula mal el IGV",
            descPh: "Qué hace mal el sistema, qué debería hacer, y un ejemplo con datos…",
            pasosPh: "1. Ir a …\n2. Usar este ejemplo: …\n3. Resultado actual (incorrecto): …\n4. Resultado esperado: …",
            pasosLabel: 'Pasos para reproducir <span class="text-muted">(opcional)</span>',
            intro: "Sí abre, pero hace mal (cálculo, dato o botón). Tiene SLA. Si no puedes entrar o sale error/bloqueo → Incidencia.",
            prioridadNota: {
                ALTA: "Alta · afecta operaciones o cifras críticas (~4 h laborales)",
                MEDIA: "Media · molesta pero hay alternativa (~24 h laborales)",
                BAJA: "Baja · detalle menor (~72 h laborales)"
            },
            tips: [
                "Di resultado actual vs esperado (con un ejemplo)",
                "Indica si siempre falla o solo a veces",
                "Adjunta captura del valor incorrecto",
                "Si no carga / error / se bloquea → usa Incidencia"
            ],
            ejemploAsunto: "El total de la factura calcula mal el IGV",
            ejemploDesc: "En facturas con descuento, el IGV queda distinto al Excel. Ejemplo: subtotal 100, descuento 10 → el sistema muestra IGV X y debería ser Y.",
            ejemploPasos: "1. Crear factura con ítem S/ 100\n2. Aplicar descuento 10%\n3. Ver IGV calculado\n4. Comparar con el valor esperado"
        }
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

    function escPre(v) {
        return esc(String(v == null ? "" : v).replace(/\r\n|\r/g, "\n"));
    }

    function escBr(v) {
        return escPre(v).replace(/\n/g, "<br>");
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
        var map = { BAJA: "Baja", MEDIA: "Media", ALTA: "Alta" };
        var txt = map[p] || p;
        return '<span class="label label-prioridad-' + esc(p) + '">' + esc(txt) + "</span>";
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

    function colorTipo(tipo) {
        var map = {
            INCIDENCIA: "text-orange",
            REQUERIMIENTO: "text-green",
            CONSULTA: "text-blue",
            OTRO: "text-muted",
            DESARROLLO: "text-aqua",
            CORRECCION: "text-red",
            SOPORTE: "text-orange"
        };
        return map[tipo] || "text-muted";
    }

    function celdaTipo(tipo) {
        var label = LABELS_TIPO[tipo] || tipo || "—";
        return (
            '<span class="hd-lista-tipo" title="' + esc(label) + '">' +
                '<i class="fa ' + iconoTipo(tipo) + " " + colorTipo(tipo) + '"></i> ' +
                esc(label) +
            "</span>"
        );
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
        $($root).find("select").each(function () {
            var $s = $(this);
            if ($s.data("selectpicker")) {
                $s.selectpicker("destroy");
            }
        });
    }

    function cerrarPickers() {
        $(".bootstrap-select.open").removeClass("open");
    }

    function initPicker($sel) {
        var $sel = $($sel);
        if (!$sel.length || !$.fn.selectpicker) {
            return;
        }
        var showContent = $sel.attr("data-show-content") === "true" ||
            $sel.find("option[data-content]").length > 0;
        var opts = {
            container: "body",
            sanitize: false
        };
        if (showContent) {
            opts.showContent = true;
        }
        if ($sel.data("selectpicker")) {
            $sel.selectpicker("refresh");
            return;
        }
        $sel.selectpicker(opts);
    }

    function valPicker($sel, value) {
        var $sel = $($sel);
        $sel.val(value == null ? "" : value);
        if ($.fn.selectpicker && $sel.data("selectpicker")) {
            $sel.selectpicker("val", $sel.val());
        }
    }

    function refrescarPickersAlta() {
        ["#hdArea", "#hdModulo", "#hdPrioridad", "#hdSolicitante", "#hdAsignadoAlta"].forEach(initPicker);
    }

    function refrescarPickersLista() {
        initPicker("#hdFiltroEstado");
        initPicker("#hdFiltroTipo");
        if (permisos.gestionar) {
            initPicker("#hdFiltroSolicitante");
            initPicker("#hdFiltroAsignado");
        }
    }

    function refrescarPickersGestion() {
        ["#hdGestTipo", "#hdGestEstado", "#hdGestPrioridad", "#hdGestSolicitante", "#hdGestAsignado", "#hdRespEstado", "#hdRespRapida"].forEach(initPicker);
    }

    function leerFiltroEstadoGuardado() {
        try {
            var v = localStorage.getItem(LS_FILTRO_ESTADO);
            if (v === null || v === undefined) {
                return null;
            }
            return ESTADOS_FILTRO_OK.hasOwnProperty(v) ? v : "";
        } catch (e) {
            return null;
        }
    }

    function guardarFiltroEstado(val) {
        try {
            localStorage.setItem(LS_FILTRO_ESTADO, val == null ? "" : String(val));
        } catch (e) { /* ignore */ }
    }

    function setFiltroEstado(val) {
        if (!ESTADOS_FILTRO_OK.hasOwnProperty(val == null ? "" : String(val))) {
            val = "";
        }
        var $sel = $("#hdFiltroEstado");
        ignorarCambioFiltroEstado = true;
        $sel.val(val == null ? "" : String(val));
        if ($.fn.selectpicker && $sel.data("selectpicker")) {
            $sel.selectpicker("val", $sel.val());
        }
        guardarFiltroEstado($sel.val() || "");
        setTimeout(function () {
            ignorarCambioFiltroEstado = false;
        }, 50);
        return $sel.val() || "";
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
            $("#hdFiltrosPersonas").css("display", "inline");
            $("#hdTabIndicadoresLi").show();
            if (permisos.control_total) {
                $("#hdTabListaLabel, #hdListaTitulo").text("Bandeja");
            } else {
                $("#hdTabListaLabel, #hdListaTitulo").text("Mi bandeja");
            }
        } else {
            $(".hd-solo-gestionar").hide();
            $("#hdFiltrosPersonas").hide();
            $("#hdTabIndicadoresLi").hide();
            $("#hdTabListaLabel, #hdListaTitulo").text("Mis tickets");
        }
        if (permisos.pulir_ia) {
            $("#hdPulirBar").removeClass("hd-pulir-hidden");
            $("#hdBtnPulir, #hdBtnPulirResp").show();
        } else {
            $("#hdPulirBar").addClass("hd-pulir-hidden");
            $("#hdBtnPulir, #hdBtnPulirResp").hide();
        }
    }

    function fillSelect($sel, items, placeholder, valueKey, labelFn) {
        var $sel = $($sel);
        var current = $sel.val();
        $sel.empty();
        if (placeholder !== null) {
            $sel.append('<option value="" data-hidden="true">' + esc(placeholder) + "</option>");
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

    function renderFileListResp() {
        var $ul = $("#hdFileListResp").empty();
        archivosRespuesta.forEach(function (f, idx) {
            $ul.append(
                "<li>" +
                    "<span>" + esc(f.name) + " <small class=\"text-muted\">(" + Math.round(f.size / 1024) + " KB)</small></span>" +
                    '<button type="button" class="hd-file-remove" data-idx="' + idx + '">&times;</button>' +
                "</li>"
            );
        });
    }

    function agregarArchivosA(lista, fileList, onDone) {
        var files = Array.prototype.slice.call(fileList || []);
        files.forEach(function (f) {
            if (lista.length >= MAX_FILES) {
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
            var dup = lista.some(function (x) {
                return x.name === f.name && x.size === f.size;
            });
            if (!dup) {
                lista.push(f);
            }
        });
        if (typeof onDone === "function") {
            onDone();
        }
    }

    function agregarArchivos(fileList) {
        agregarArchivosA(archivosSeleccionados, fileList, renderFileList);
    }

    function agregarArchivosResp(fileList) {
        agregarArchivosA(archivosRespuesta, fileList, renderFileListResp);
    }

    function limpiarArchivosResp() {
        archivosRespuesta = [];
        $("#hdAdjuntosResp").val("");
        renderFileListResp();
    }

    function refreshPicker($sel) {
        initPicker($sel);
    }

    function fillOptions($sel, items, placeholder) {
        var $sel = $($sel);
        var current = $sel.val();
        $sel.empty();
        $sel.append('<option value="" data-hidden="true">' + esc(placeholder) + "</option>");
        (items || []).forEach(function (v) {
            if (v == null || String(v).trim() === "") {
                return;
            }
            $sel.append('<option value="' + esc(v) + '">' + esc(v) + "</option>");
        });
        if (current) {
            $sel.val(current);
        }
        refreshPicker($sel);
    }

    function fillModulosGrouped($sel, grupos, placeholder) {
        var $sel = $($sel);
        var current = $sel.val();
        $sel.empty();
        $sel.append('<option value="" data-hidden="true">' + esc(placeholder) + "</option>");
        (grupos || []).forEach(function (g) {
            var seccion = (g && g.seccion) ? String(g.seccion) : "Otros";
            var $og = $('<optgroup>').attr("label", seccion);
            (g.items || []).forEach(function (item) {
                if (item == null || String(item).trim() === "") {
                    return;
                }
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

    function tipoUsaFechaEstimada(tipo) {
        return tipo === "DESARROLLO";
    }

    function fechaEstimadaSoloDia(val) {
        if (!val) {
            return "";
        }
        return String(val).substring(0, 10);
    }

    /** Compromiso suave: aviso visual si se pasó la fecha (no es SLA). */
    function infoFechaEstimada(ticket) {
        var f = fechaEstimadaSoloDia(ticket && ticket.fecha_estimada);
        if (!f) {
            return null;
        }
        var cerrado = ticket.estado === "CERRADO";
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        var d = new Date(f + "T00:00:00");
        var atrasado = !cerrado && !isNaN(d.getTime()) && d < hoy;
        return {
            fecha: f,
            atrasado: atrasado,
            label: atrasado ? ("Atrasado · " + f) : ("Est. " + f),
            cls: atrasado ? "hd-est-atrasado" : "hd-est-ok"
        };
    }

    function badgeFechaEstimada(ticket) {
        var info = infoFechaEstimada(ticket);
        if (!info) {
            return "";
        }
        var icon = info.atrasado ? "fa-exclamation-circle" : "fa-calendar";
        var title = info.atrasado
            ? "Se pasó la fecha estimada · no afecta SLA"
            : "Fecha estimada de entrega · no es SLA";
        return '<span class="hd-est ' + info.cls + '" title="' + esc(title) + '">' +
            '<i class="fa ' + icon + '"></i> ' + esc(info.label) + "</span>";
    }

    function toggleFechaEstimadaAlta() {
        // Fecha estimada solo la define TI al gestionar el ticket, no al crear.
        $("#hdFechaEstimadaWrap").hide();
        $("#hdFechaEstimada").val("");
    }

    function guiaAltaActual() {
        var tipo = $("#hdTipo").val() || "INCIDENCIA";
        return GUIA_ALTA[tipo] || GUIA_ALTA.INCIDENCIA;
    }

    function aplicarGuiaAlta() {
        var g = guiaAltaActual();
        var pri = $("#hdPrioridad").val() || "MEDIA";
        var notaPri = (g.prioridadNota && g.prioridadNota[pri]) || g.prioridadNota.MEDIA;
        var tipoLabel = LABELS_TIPO[$("#hdTipo").val()] || "Tipo";

        $("#hdTitulo").attr("placeholder", g.tituloPh);
        $("#hdDescripcion").attr("placeholder", g.descPh);
        $("#hdPasos").attr("placeholder", g.pasosPh);
        $("#hdPasosLabel").html(g.pasosLabel);
        $("#hdEjemploTipoTitulo").html('<i class="fa fa-tag"></i> ' + esc(tipoLabel));
        $("#hdInfoIntro").text(g.intro);
        $("#hdInfoPrioridad").html(
            "<strong>Prioridad elegida:</strong><br>" +
            badgePrioridad(pri) + " " + esc(notaPri)
        );
        $("#hdEjemploAsunto").text(g.ejemploAsunto);
        $("#hdEjemploDesc").text(g.ejemploDesc);

        var $tips = $("#hdConsejosLista").empty();
        (g.tips || []).forEach(function (t) {
            $tips.append("<li>" + esc(t) + "</li>");
        });
    }

    function usarEjemploAlta() {
        var g = guiaAltaActual();
        var llenados = 0;
        if (!$.trim($("#hdTitulo").val())) {
            $("#hdTitulo").val(g.ejemploAsunto);
            llenados++;
        }
        if (!$.trim($("#hdDescripcion").val())) {
            $("#hdDescripcion").val(g.ejemploDesc);
            llenados++;
        }
        if (!$.trim($("#hdPasos").val())) {
            $("#hdPasos").val(g.ejemploPasos);
            llenados++;
        }
        if (llenados === 0) {
            toast("info", "Los campos ya tienen texto. Bórralos si quieres pegar el ejemplo.");
            return;
        }
        toast("success", "Ejemplo cargado en los campos vacíos. Ajústalo a tu caso.");
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
        valPicker("#hdPrioridad", "MEDIA");
        valPicker("#hdSolicitante", "");
        valPicker("#hdAsignadoAlta", "");
        valPicker("#hdArea", "");
        valPicker("#hdModulo", "");
        $("#hdFechaEstimada").val("");
        toggleFechaEstimadaAlta();
        aplicarGuiaAlta();
        archivosSeleccionados = [];
        renderFileList();
        $("#hdAdjuntos").val("");
        fillOptions($("#hdArea"), catalogos.areas, "Seleccionar área");
        aplicarSistema("VASCORP");
        refrescarPickersAlta();
    }

    function cargarBase() {
        return post("catalogos").done(function (res) {
            if (!res || !res.ok) {
                return;
            }
            if (res.permisos) {
                permisos = $.extend({}, permisos, res.permisos);
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
            fillFiltrosPersonas();
            var guardado = leerFiltroEstadoGuardado();
            if (guardado !== null) {
                $("#hdFiltroEstado").val(guardado);
            }
            refrescarPickersAlta();
        });
    }

    function fillFiltrosPersonas() {
        if (!permisos.gestionar) {
            return;
        }
        fillSelect($("#hdFiltroSolicitante"), usuarios, "Solicitante", "id", function (u) {
            return (u.nombre || "") + (u.usuario ? " (" + u.usuario + ")" : "");
        });
        var $asig = $("#hdFiltroAsignado");
        var currentAsig = $asig.val();
        $asig.empty();
        $asig.append('<option value="">Asignado</option>');
        $asig.append('<option value="__SIN__">Sin asignar</option>');
        (agentes || []).forEach(function (u) {
            $asig.append(
                '<option value="' + esc(u.id) + '">' + esc(u.nombre || ("#" + u.id)) + "</option>"
            );
        });
        if (currentAsig) {
            $asig.val(currentAsig);
        }
        refreshPicker("#hdFiltroSolicitante");
        refreshPicker("#hdFiltroAsignado");
    }

    function fmtFechaCorta(fecha) {
        if (!fecha) {
            return "—";
        }
        var s = String(fecha);
        var m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:\s+|T)(\d{2}):(\d{2})/);
        if (m) {
            return m[3] + "/" + m[2] + "/" + m[1] + " " + m[4] + ":" + m[5];
        }
        m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) {
            return m[3] + "/" + m[2] + "/" + m[1];
        }
        return s;
    }

    function filaMetaCreado(t) {
        return '<div><i class="fa fa-clock-o"></i> <span>Creado</span> <strong>' +
            esc(fmtFechaCorta(t && t.creado_en)) + "</strong></div>";
    }

    function antiguedadTxt(fecha) {
        if (!fecha) {
            return "—";
        }
        var d = new Date(String(fecha).replace(" ", "T"));
        if (isNaN(d.getTime())) {
            return "—";
        }
        var days = Math.floor((Date.now() - d.getTime()) / 86400000);
        if (days <= 0) {
            return "Hoy";
        }
        if (days === 1) {
            return "1 día";
        }
        if (days < 30) {
            return days + " días";
        }
        var meses = Math.floor(days / 30);
        return meses === 1 ? "1 mes" : meses + " meses";
    }

    function badgeSla(sla) {
        if (!sla || !sla.label) {
            return '<span class="hd-sla hd-sla-na">—</span>';
        }
        var icon = "fa-clock-o";
        if (sla.codigo === "CUMPLIDO") {
            icon = "fa-check-circle";
        } else if (sla.codigo === "VENCIDO" || sla.codigo === "FUERA") {
            icon = "fa-exclamation-triangle";
        } else if (sla.codigo === "N/A") {
            icon = "fa-minus";
        } else if (sla.codigo === "EXENTO") {
            icon = "fa-ban";
        }
        var title = "";
        if (sla.codigo === "N/A") {
            title = "Trabajo planificado · sin reloj SLA de cierre";
        } else if (sla.codigo === "EXENTO") {
            title = sla.motivo
                ? ("SLA cancelado · " + sla.motivo)
                : "SLA cancelado por un agente";
        } else if (sla.horas_limite) {
            title = "Límite: " + sla.horas_limite + "h laborales" +
                (sla.deadline ? " · vence " + sla.deadline : "") +
                " (lun–vie 8:00–17:30 · sáb 8:00–12:15)";
        }
        return '<span class="hd-sla ' + esc(sla.cls || "hd-sla-na") + '" title="' + esc(title) + '">' +
            '<i class="fa ' + icon + '"></i> ' + esc(sla.label) + "</span>";
    }

    function renderKpis(resumen) {
        var r = resumen || {};
        $("#hdKpiTotal").text(r.total || 0);
        $("#hdKpiActivos").text(r.activos || 0);
        $("#hdKpiAbierto").text(r.ABIERTO || 0);
        $("#hdKpiProgreso").text(r.EN_PROGRESO || 0);
        $("#hdKpiEspera").text(r.ESPERANDO_USUARIO || 0);
        $("#hdKpiVencidos").text(r.vencidos || 0);
        $("#hdKpiCerrado").text(r.CERRADO || 0);
        var filtro = $("#hdFiltroEstado").val();
        $("#hdKpis .hd-kpi").removeClass("active");
        $('#hdKpis .hd-kpi[data-filtro="' + filtro + '"]').addClass("active");
    }

    function renderLista(items) {
        var $tb = $("#hdTablaLista tbody").empty();
        if (!items || !items.length) {
            $tb.append('<tr class="hd-vacio"><td colspan="10" class="text-muted text-center">Sin tickets.</td></tr>');
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
            var antCls = "";
            if (t.creado_en && t.estado !== "CERRADO") {
                var d = new Date(String(t.creado_en).replace(" ", "T"));
                if (!isNaN(d.getTime())) {
                    var days = Math.floor((Date.now() - d.getTime()) / 86400000);
                    if (days >= 7) {
                        antCls = " hd-ant-alerta";
                    } else if (days >= 3) {
                        antCls = " hd-ant-aviso";
                    }
                }
            }
            var rowSla = (t.sla && t.sla.codigo === "VENCIDO") ? " hd-row-vencido" : "";
            $tb.append(
                '<tr class="hd-row' + activa + (t.estado === "CERRADO" ? " hd-row-cerrado" : "") + rowSla + '" data-id="' + esc(t.id) + '">' +
                    "<td><strong>#" + esc(t.id) + "</strong></td>" +
                    "<td>" + esc(t.titulo) +
                        (sub.length ? "<br><small class=\"text-muted\">" + sub.join(" · ") + "</small>" : "") +
                    "</td>" +
                    "<td>" + esc(t.solicitante_nombre || ("#" + t.solicitante_id)) + "</td>" +
                    "<td>" + celdaTipo(t.tipo) + "</td>" +
                    "<td>" + badgePrioridad(t.prioridad) + "</td>" +
                    "<td>" + badgeEstado(t.estado) + "</td>" +
                    "<td>" + esc(t.asignado_nombre || "—") + "</td>" +
                                                    "<td>" + badgeSla(t.sla) +
                        (function () {
                            var fe = badgeFechaEstimada(t);
                            return fe ? "<br>" + fe : "";
                        })() +
                    "</td>" +
                    '<td class="hd-antiguedad' + antCls + '">' + esc(antiguedadTxt(t.creado_en)) +
                        "<br><small class=\"text-muted\">" + esc(fmtFechaCorta(t.creado_en)) + "</small></td>" +
                    '<td><button type="button" class="btn btn-xs btn-info hd-btn-ver"><i class="fa fa-eye"></i></button></td>' +
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
        if (permisos.gestionar) {
            var sol = $("#hdFiltroSolicitante").val();
            var asig = $("#hdFiltroAsignado").val();
            if (sol) {
                data.solicitante_id = sol;
            }
            if (asig) {
                data.asignado_id = asig;
            }
        }
        if (estado === "__ACTIVOS__") {
            data.solo_abiertos = 1;
        } else if (estado === "__VENCIDOS__") {
            data.solo_vencidos = 1;
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
                    permisos = $.extend({}, permisos, res.permisos);
                    aplicarPermisosUi();
                }
                renderKpis(res.resumen || {});
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
        if (h === "indicadores") {
            return { vista: "indicadores" };
        }
        return { vista: "lista" };
    }

    function setHash(vista, ticketId) {
        var h = "#lista";
        if (vista === "nuevo") {
            h = "#nuevo";
        } else if (vista === "indicadores" && permisos.gestionar) {
            h = "#indicadores";
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
        cerrarPickers();
        $("#hdVistaNuevo, #hdVistaLista, #hdVistaIndicadores").removeClass("active");
        $("#hdTabNuevoLi, #hdTabListaLi, #hdTabIndicadoresLi").removeClass("active");
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
        cerrarPickers();
        $("#hdVistaConversacion, #hdVistaIndicadores").removeClass("active");
        $("#hdVistaNuevo").removeClass("active");
        $("#hdTabNuevoLi, #hdTabIndicadoresLi").removeClass("active");
        $("#hdTabListaLi").addClass("active");
        $("#hdVistaLista").addClass("active");
        ticketActual = null;
        if (actualizarHash !== false) {
            setHash("lista");
        }
        refrescarPickersLista();
        cargarLista();
    }

    function mostrarVistaNuevo(actualizarHash) {
        if (!permisos.registrar) {
            mostrarVistaLista(actualizarHash);
            return;
        }
        $("#hdVistaConversacion, #hdVistaIndicadores, #hdVistaLista").removeClass("active");
        $("#hdTabListaLi, #hdTabIndicadoresLi").removeClass("active");
        $("#hdTabNuevoLi").addClass("active");
        $("#hdVistaNuevo").addClass("active");
        ticketActual = null;
        if (actualizarHash !== false) {
            setHash("nuevo");
        }
        refrescarPickersAlta();
        cerrarPickers();
    }

    function mostrarVistaIndicadores(actualizarHash) {
        if (!permisos.gestionar) {
            mostrarVistaLista(actualizarHash);
            return;
        }
        $("#hdVistaConversacion, #hdVistaNuevo, #hdVistaLista").removeClass("active");
        $("#hdTabNuevoLi, #hdTabListaLi").removeClass("active");
        $("#hdTabIndicadoresLi").addClass("active");
        $("#hdVistaIndicadores").addClass("active");
        ticketActual = null;
        if (actualizarHash !== false) {
            setHash("indicadores");
        }
        cerrarPickers();
        if (typeof window.hdCargarIndicadores === "function") {
            window.hdCargarIndicadores(true);
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
        if (estado.vista === "indicadores") {
            mostrarVistaIndicadores(false);
            return;
        }
        mostrarVistaLista(false);
    }

    function metaEvento(tipo) {
        var map = {
            ALTA: { txt: "Creación", icon: "fa-plus-circle", cls: "hd-hist-alta" },
            COMENTARIO: { txt: "Comentario", icon: "fa-comment", cls: "hd-hist-comentario" },
            CAMBIO_ESTADO: { txt: "Cambio de estado", icon: "fa-exchange", cls: "hd-hist-estado" },
            ASIGNACION: { txt: "Asignación", icon: "fa-user", cls: "hd-hist-asignacion" },
            REAPERTURA_USUARIO: { txt: "Reabierto por usuario", icon: "fa-undo", cls: "hd-hist-reapertura" }
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
        if (c.tipo_evento === "REAPERTURA_USUARIO") {
            return "El solicitante indicó que no quedó resuelto:<br>" + escBr(msg);
        }
        return escBr(msg);
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
                                c.tipo_evento === "REAPERTURA_USUARIO" ? "label-warning" :
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
                    '<div class="hd-msg-text">' + escPre(t.descripcion) + "</div>" +
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
            if (c.tipo_evento === "REAPERTURA_USUARIO") {
                html +=
                    '<div class="hd-msg-sistema hd-msg-reapertura">' +
                        '<span class="label label-warning">Reabierto por el usuario</span> ' +
                        '<div class="hd-msg-text" style="margin-top:6px;">' +
                            escPre(c.mensaje) +
                        "</div>" +
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
                        '<div class="hd-msg-text">' + escPre(c.mensaje) + "</div>" +
                    "</div>" +
                "</div>";
        });
        return html;
    }

    function renderConversacion(res) {
        var t = res.ticket;
        ticketActual = t;
        agentes = res.agentes || agentes;
        var sla = res.sla || (t && t.sla) || null;

        $("#hdConvCabecera").html(
            '<div class="hd-conv-head">' +
                '<div>' +
                    '<span class="hd-ticket-id">#' + esc(t.id) + "</span> " +
                    badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) + " " +
                    badgeSla(sla) +
                    '<h3 class="hd-conv-asunto">' + esc(t.titulo) + "</h3>" +
                    '<div class="text-muted hd-conv-meta">' +
                        "Creado por <strong>" + esc(t.solicitante_nombre || ("#" + t.solicitante_id)) + "</strong>" +
                        (t.area ? " · " + esc(t.area) : "") +
                        " · " + esc(fmtFechaCorta(t.creado_en)) +
                        (sla && sla.horas_limite
                            ? " · SLA " + esc(String(sla.horas_limite)) + "h"
                            : "") +
                        (infoFechaEstimada(t)
                            ? " · " + esc(infoFechaEstimada(t).label)
                            : "") +
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
                    '<div class="hd-det-chip hd-det-chip-sla">' +
                        '<i class="fa fa-clock-o"></i>' +
                        '<div><span class="hd-det-chip-lbl">SLA</span>' +
                        badgeSla(sla) +
                        (sla && sla.codigo === "EXENTO"
                            ? '<br><small class="text-muted">' +
                              esc(sla.motivo || "Exento de medición") + "</small>"
                            : (sla && sla.codigo === "N/A"
                                ? '<br><small class="text-muted">Planificado</small>'
                                : (sla && sla.horas_limite
                                    ? '<br><small class="text-muted">Límite ' + esc(String(sla.horas_limite)) + "h" +
                                      (sla.deadline ? " · " + esc(sla.deadline) : "") + "</small>"
                                    : ""))) +
                        "</div>" +
                    "</div>" +
                    (tipoUsaFechaEstimada(t.tipo) || t.fecha_estimada
                        ? '<div class="hd-det-chip">' +
                            '<i class="fa fa-calendar"></i>' +
                            '<div><span class="hd-det-chip-lbl">Fecha estimada</span>' +
                            (badgeFechaEstimada(t) ||
                                "<strong>Sin definir</strong>") +
                            "</div></div>"
                        : "") +
                "</div>" +
                '<div class="hd-det-info-row">' +
                    '<div class="hd-det-info-item"><i class="fa fa-desktop text-orange"></i> ' +
                        '<span>Sistema</span><strong>' + esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-building text-aqua"></i> ' +
                        '<span>Área</span><strong>' + esc(t.area || "—") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-cubes text-green"></i> ' +
                        '<span>Módulo</span><strong>' + esc(t.modulo || "—") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-user text-blue"></i> ' +
                        '<span>Asignado</span><strong>' + esc(t.asignado_nombre || "Sin asignar") + "</strong></div>" +
                    '<div class="hd-det-info-item"><i class="fa fa-clock-o text-muted"></i> ' +
                        '<span>Creado</span><strong>' + esc(fmtFechaCorta(t.creado_en)) + "</strong></div>" +
                "</div>" +
                '<div class="hd-det-block">' +
                    '<h4><i class="fa fa-align-left"></i> Descripción</h4>' +
                    '<div class="hd-det-text">' + escPre(t.descripcion) + "</div>" +
                "</div>" +
                (t.pasos_reproducir
                    ? '<div class="hd-det-block hd-det-block-pasos">' +
                        '<h4><i class="fa fa-list-ol"></i> Pasos para reproducir</h4>' +
                        '<div class="hd-det-text">' + escPre(t.pasos_reproducir) + "</div>" +
                      "</div>"
                    : "") +
            "</div>"
        );
        $("#hdTabArchivos").html(renderAdjuntosTab(res.adjuntos || []));
        $("#hdTabHistorial").html(renderHistorial(res.comentarios || []));

        // Sidebar gestión
        var cerrado = t.estado === "CERRADO";
        var reapInfo = res.reapertura_solicitante || {};
        var estadoSel = cerrado ? "EN_PROGRESO" : t.estado;
        var optsEstado = "";
        ["ABIERTO", "EN_PROGRESO", "ESPERANDO_USUARIO", "CERRADO"].forEach(function (e) {
            if (cerrado && e === "CERRADO") {
                return;
            }
            var info = LABELS_ESTADO[e] || { cls: "label-default", txt: e };
            var txtOpt = e === "ESPERANDO_USUARIO" ? "Esperando (usuario/área)" : info.txt;
            optsEstado +=
                '<option value="' + e + '"' + (e === estadoSel ? " selected" : "") +
                " data-content=\"<span class='label " + info.cls + "'>" + esc(info.txt) + "</span>\">" +
                esc(txtOpt) + "</option>";
        });
        var optsAsig = '<option value="">Sin asignar</option>';
        agentes.forEach(function (u) {
            optsAsig +=
                '<option value="' + esc(u.id) + '"' +
                (String(u.id) === String(t.asignado_id || "") ? " selected" : "") +
                ">" + esc(u.nombre || ("#" + u.id)) + "</option>";
        });
        var optsSol = "";
        var solIds = {};
        (usuarios || []).forEach(function (u) {
            solIds[String(u.id)] = true;
            optsSol +=
                '<option value="' + esc(u.id) + '"' +
                (String(u.id) === String(t.solicitante_id || "") ? " selected" : "") +
                ">" + esc(u.nombre || ("#" + u.id)) + "</option>";
        });
        if (t.solicitante_id && !solIds[String(t.solicitante_id)]) {
            optsSol =
                '<option value="' + esc(t.solicitante_id) + '" selected>' +
                esc(t.solicitante_nombre || ("#" + t.solicitante_id)) +
                "</option>" + optsSol;
        }
        var optsPri = "";
        ["BAJA", "MEDIA", "ALTA"].forEach(function (p) {
            optsPri +=
                '<option value="' + p + '"' + (p === t.prioridad ? " selected" : "") +
                " data-content=\"<span class='label label-prioridad-" + p + "'><i class='fa fa-flag'></i> " +
                esc(p) + "</span>\">" +
                esc(p) + "</option>";
        });
        var optsTipo = "";
        ["INCIDENCIA", "REQUERIMIENTO", "CONSULTA", "DESARROLLO", "CORRECCION", "OTRO"].forEach(function (tp) {
            optsTipo +=
                '<option value="' + tp + '"' + (tp === t.tipo ? " selected" : "") + ">" +
                esc(LABELS_TIPO[tp] || tp) + "</option>";
        });

        destroyPickers("#hdConvSidebarBody");

        if (permisos.gestionar && !cerrado) {
            var fechaEst = fechaEstimadaSoloDia(t.fecha_estimada);
            var showFecha = tipoUsaFechaEstimada(t.tipo);
            var exento = String(t.sla_exento) === "1" || t.sla_exento === 1 || t.sla_exento === true;
            var motivoEx = t.sla_exento_motivo || "";
            var tipoSinSla = t.tipo === "DESARROLLO";
            $("#hdConvSidebarBody").html(
                '<form id="hdFormGestionar" class="hd-side-form">' +
                    '<div class="hd-side-badges">' +
                        badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) +
                    "</div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-tag text-blue"></i> Tipo</label>' +
                        '<select class="form-control selectpicker" id="hdGestTipo" data-width="100%">' +
                        optsTipo + "</select></div>" +
                    '<div class="form-group" id="hdGestFechaWrap"' + (showFecha ? "" : ' style="display:none;"') + ">" +
                        '<label><i class="fa fa-calendar text-green"></i> Fecha estimada</label>' +
                        '<input type="date" class="form-control" id="hdGestFechaEstimada" value="' +
                            esc(fechaEst) + '">' +
                        '<p class="help-block" style="margin-bottom:0;">Compromiso · no es SLA</p></div>' +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-exchange text-aqua"></i> Estado</label>' +
                        '<select class="form-control selectpicker" id="hdGestEstado" data-width="100%">' +
                        optsEstado + "</select>" +
                        '<p class="help-block hd-hint-espera" id="hdHintEspera"' +
                        (t.estado === "ESPERANDO_USUARIO" ? "" : ' style="display:none;"') + ">" +
                        "Deja un comentario: qué autorización falta y a quién se pidió. " +
                        "Si no bloquea operación, considera bajar prioridad.</p></div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-flag text-red"></i> Prioridad</label>' +
                        '<select class="form-control selectpicker" id="hdGestPrioridad" data-width="100%">' +
                        optsPri + "</select></div>" +
                    '<div class="form-group">' +
                        '<label><i class="fa fa-user-circle text-blue"></i> Solicitante</label>' +
                        '<p class="help-block">Quién pidió el soporte (si lo creaste vos por error)</p>' +
                        '<select class="form-control selectpicker" id="hdGestSolicitante" data-width="100%" ' +
                        'data-live-search="true" title="Elegir solicitante">' +
                        optsSol + "</select></div>" +
                    '<div class="form-group hd-side-asignar">' +
                        '<label><i class="fa fa-user text-aqua"></i> Asignar a</label>' +
                        '<p class="help-block">Responsable de atender el ticket</p>' +
                        '<select class="form-control selectpicker" id="hdGestAsignado" data-width="100%" ' +
                        'data-live-search="true" title="Sin asignar">' +
                        optsAsig + "</select></div>" +
                    (tipoSinSla
                        ? ""
                        : '<div class="form-group hd-sla-exento-box">' +
                            '<label class="hd-check-exento">' +
                              '<input type="checkbox" id="hdGestSlaExento"' + (exento ? " checked" : "") + "> " +
                              "<strong>Cancelar SLA</strong></label>" +
                            '<p class="help-block" style="margin:4px 0 6px;">Exime este ticket de la medición (cualquier motivo válido).</p>' +
                            '<div id="hdGestSlaMotivoWrap"' + (exento ? "" : ' style="display:none;"') + ">" +
                              '<label for="hdGestSlaMotivo">Motivo <span class="text-danger">*</span></label>' +
                              '<textarea class="form-control" id="hdGestSlaMotivo" rows="2" maxlength="255" ' +
                              'placeholder="Ej.: pendiente autorización Gerencia / Seguridad">' +
                              esc(motivoEx) + "</textarea></div></div>") +
                    '<div class="hd-side-meta">' +
                        filaMetaCreado(t) +
                        '<div><i class="fa fa-building"></i> <span>Área</span> <strong>' + esc(t.area || "—") + "</strong></div>" +
                        '<div><i class="fa fa-desktop"></i> <span>Sistema</span> <strong>' +
                            esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                        '<div><i class="fa fa-cubes"></i> <span>Módulo</span> <strong>' + esc(t.modulo || "—") + "</strong></div>" +
                    "</div>" +
                    '<button type="submit" class="btn btn-primary btn-block">' +
                        '<i class="fa fa-save"></i> Guardar cambios</button>' +
                "</form>"
            );
            $("#hdGestTipo").on("changed.bs.select change", function () {
                var show = tipoUsaFechaEstimada($(this).val());
                $("#hdGestFechaWrap").toggle(!!show);
                if (!show) {
                    $("#hdGestFechaEstimada").val("");
                }
                var sinSlaTipo = $(this).val() === "DESARROLLO";
                $(".hd-sla-exento-box").toggle(!sinSlaTipo);
            });
            $("#hdGestEstado").on("changed.bs.select change", function () {
                $("#hdHintEspera").toggle($(this).val() === "ESPERANDO_USUARIO");
            });
            $("#hdGestSlaExento").on("change", function () {
                $("#hdGestSlaMotivoWrap").toggle(!!$(this).is(":checked"));
            });
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
                        filaMetaCreado(t) +
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
        } else {
            $("#hdConvSidebarBody").html(
                (cerrado
                    ? '<div class="callout callout-default" style="margin-bottom:12px;padding:8px 12px;">' +
                      '<p style="margin:0;"><i class="fa fa-lock"></i> Ticket cerrado. Solo lectura.</p></div>'
                    : "") +
                '<div class="hd-side-badges">' + badgeEstado(t.estado) + " " + badgePrioridad(t.prioridad) + "</div>" +
                '<div class="hd-side-meta">' +
                    filaMetaCreado(t) +
                    '<div><i class="fa fa-user"></i> <span>Asignado</span> <strong>' +
                        esc(t.asignado_nombre || "Sin asignar") + "</strong></div>" +
                    (tipoUsaFechaEstimada(t.tipo) || t.fecha_estimada
                        ? '<div><i class="fa fa-calendar"></i> <span>Est.</span> <strong>' +
                          esc(fechaEstimadaSoloDia(t.fecha_estimada) || "Sin definir") + "</strong></div>"
                        : "") +
                    '<div><i class="fa fa-building"></i> <span>Área</span> <strong>' + esc(t.area || "—") + "</strong></div>" +
                    '<div><i class="fa fa-desktop"></i> <span>Sistema</span> <strong>' +
                        esc(LABELS_SISTEMA[t.sistema] || t.sistema || "—") + "</strong></div>" +
                    '<div><i class="fa fa-cubes"></i> <span>Módulo</span> <strong>' + esc(t.modulo || "—") + "</strong></div>" +
                "</div>"
            );
        }

        if (permisos.eliminar) {
            $("#hdConvSidebarBody").append(
                '<div class="hd-eliminar-box">' +
                    '<hr style="margin:14px 0 10px;border-top-color:#ddd;">' +
                    '<p class="help-block" style="margin-bottom:8px;">' +
                    '<i class="fa fa-exclamation-triangle text-red"></i> ' +
                    "Borrado definitivo (solo vos). No se puede deshacer.</p>" +
                    '<button type="button" class="btn btn-danger btn-block hd-btn-eliminar-ticket">' +
                    '<i class="fa fa-trash"></i> Eliminar ticket</button>' +
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
        valPicker("#hdRespRapida", "");
        valPicker("#hdRespEstado", "");
        limpiarArchivosResp();
        $("#hdReabrirSolicitanteBox").remove();
        if (cerrado) {
            $("#hdResponderBox").hide();
            var reap = reapInfo;
            var msgCerrado;
            if (permisos.reabrir) {
                msgCerrado = " Para responder, reabrilo desde el panel derecho.";
            } else if (reap.puede_reabrir) {
                msgCerrado = " Si no quedó resuelto, completá el formulario de abajo" +
                    " (hasta el " + esc(fmtFechaCorta(reap.hasta)) + ").";
            } else if (reap.es_solicitante && reap.vencida) {
                msgCerrado = " El plazo de " + esc(String(reap.dias || 7)) +
                    " días para reabrir ya venció. Si necesitás ayuda, abrí un ticket nuevo.";
            } else {
                msgCerrado = " No se pueden agregar respuestas.";
            }
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
            if (reap.puede_reabrir && !permisos.gestionar) {
                $("#hdCerradoBanner").after(
                    '<div id="hdReabrirSolicitanteBox" class="hd-reabrir-solicitante">' +
                        "<p><strong>¿No quedó resuelto?</strong> Explicá qué falta y reabriremos el ticket " +
                        "para que TI lo retome. Tenés tiempo hasta el <strong>" +
                        esc(fmtFechaCorta(reap.hasta)) + "</strong>.</p>" +
                        '<textarea class="form-control hd-reabrir-motivo" rows="3" ' +
                        'placeholder="Ej: Pedí mover las fechas y solo corrigieron una parte…"></textarea>' +
                        '<button type="button" class="btn btn-warning hd-btn-reabrir-solicitante">' +
                            '<i class="fa fa-undo"></i> No está resuelto — reabrir ticket' +
                        "</button>" +
                    "</div>"
                );
            }
        } else {
            $("#hdCerradoBanner").remove();
            $("#hdResponderBox").show();
        }
        mostrarVistaConversacion(t.id);
        refrescarPickersGestion();
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
                    permisos = $.extend({}, permisos, res.permisos);
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
            toastr.options = $.extend({}, toastr.options, {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 9000,
                extendedTimeOut: 4000,
                newestOnTop: true
            });
            toastr.info(ayuda, titulo);
            return;
        }
        if (typeof toast === "function") {
            toast("info", titulo + ": " + ayuda);
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
        toggleFechaEstimadaAlta();
        aplicarGuiaAlta();
        mostrarAyudaCard(this, "Tipo");
    });

    $("#hdPrioridad").on("change changed.bs.select", function () {
        aplicarGuiaAlta();
    });

    $("#hdBtnUsarEjemplo").on("click", function () {
        usarEjemploAlta();
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

    var $dzResp = $("#hdDropzoneResp");
    $dzResp.on("dragover dragenter", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dzResp.addClass("hd-dragover");
    });
    $dzResp.on("dragleave drop", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dzResp.removeClass("hd-dragover");
    });
    $dzResp.on("drop", function (e) {
        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files) {
            agregarArchivosResp(dt.files);
        }
    });
    $("#hdAdjuntosResp").on("change", function () {
        agregarArchivosResp(this.files);
        $(this).val("");
    });
    $("#hdFileListResp").on("click", ".hd-file-remove", function () {
        var idx = Number($(this).data("idx"));
        archivosRespuesta.splice(idx, 1);
        renderFileListResp();
    });

    $("#hdBtnCancelar").on("click", function () {
        resetFormAlta();
    });

    $("#hdBtnRefrescar").on("click", function () {
        cargarLista();
    });

    $("#hdFiltroTipo, #hdFiltroSolicitante, #hdFiltroAsignado").on("change", function () {
        cargarLista();
    });
    $("#hdFiltroTipo").on("changed.bs.select", function () {
        cargarLista();
    });
    var filtroEstadoTimer = null;
    var ignorarCambioFiltroEstado = false;
    function onCambioFiltroEstado() {
        if (ignorarCambioFiltroEstado) {
            return;
        }
        clearTimeout(filtroEstadoTimer);
        filtroEstadoTimer = setTimeout(function () {
            guardarFiltroEstado($("#hdFiltroEstado").val() || "");
            cargarLista();
        }, 30);
    }
    $("#hdFiltroEstado").on("changed.bs.select change", onCambioFiltroEstado);
    $("#hdFiltroSolicitante, #hdFiltroAsignado").on("changed.bs.select", function () {
        cargarLista();
    });

    $("#hdKpis").on("click", ".hd-kpi", function () {
        var filtro = $(this).attr("data-filtro");
        if (filtro === undefined) {
            filtro = "";
        }
        setFiltroEstado(filtro);
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
            refrescarPickersLista();
            cerrarPickers();
            cargarLista();
        } else if (href === "#hdVistaNuevo") {
            ticketActual = null;
            $("#hdVistaConversacion").removeClass("active");
            setHash("nuevo");
            refrescarPickersAlta();
            cerrarPickers();
        } else if (href === "#hdVistaIndicadores") {
            ticketActual = null;
            $("#hdVistaConversacion").removeClass("active");
            setHash("indicadores");
            cerrarPickers();
            if (typeof window.hdCargarIndicadores === "function") {
                window.hdCargarIndicadores(true);
            }
        }
    });

    window.hdAbrirTicket = verTicket;

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
                toast("success", res.msg || "Asunto, descripción y pasos pulidos.");
            })
            .fail(function () {
                toast("error", "Error de red al pulir con IA.");
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-magic"></i> Pulir asunto y texto (IA)');
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
        if (!mensaje && archivosRespuesta.length === 0) {
            toast("warning", "Escriba una respuesta o adjunte un archivo.");
            return;
        }
        var fd = new FormData();
        fd.append("accion", "comentar");
        fd.append("id", ticketActual.id);
        fd.append("mensaje", mensaje);
        if (permisos.gestionar && $("#hdRespEstado").val()) {
            fd.append("cambiar_estado", $("#hdRespEstado").val());
        }
        archivosRespuesta.forEach(function (f) {
            fd.append("adjuntos[]", f);
        });
        $("#hdBtnEnviarResp").prop("disabled", true);
        $.ajax({
            url: API + "?accion=comentar",
            method: "POST",
            dataType: "json",
            data: fd,
            processData: false,
            contentType: false
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo enviar.");
                    return;
                }
                toast("success", res.msg || "Enviado.");
                limpiarArchivosResp();
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al responder.");
            })
            .always(function () {
                $("#hdBtnEnviarResp").prop("disabled", false);
            });
    });

    $("#hdRespRapida").on("changed.bs.select change", function () {
        var txt = $.trim($(this).val());
        if (!txt) {
            return;
        }
        $("#hdComentario").val(txt).focus();
        valPicker("#hdRespRapida", "");
    });

    $(document).on("click", ".hd-btn-reabrir-solicitante", function () {
        if (!ticketActual) {
            return;
        }
        var $box = $(this).closest(".hd-reabrir-solicitante");
        var mensaje = $.trim($box.find(".hd-reabrir-motivo").val());
        if (mensaje.length < 5) {
            toast("warning", "Explicá qué falta o qué no quedó resuelto.");
            return;
        }
        var $btn = $(this);
        $btn.prop("disabled", true);
        post("reabrir_solicitante", {
            id: ticketActual.id,
            mensaje: mensaje
        })
            .done(function (res) {
                if (!res || !res.ok) {
                    toast("error", (res && res.msg) || "No se pudo reabrir.");
                    return;
                }
                toast("success", res.msg || "Ticket reabierto.");
                verTicket(ticketActual.id);
            })
            .fail(function () {
                toast("error", "Error de red al reabrir.");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    });

    $(document).on("click", ".hd-btn-eliminar-ticket", function () {
        if (!permisos.eliminar || !ticketActual) {
            return;
        }
        var id = ticketActual.id;
        var titulo = ticketActual.titulo || "";
        var confirmar = function () {
            var $btn = $(".hd-btn-eliminar-ticket");
            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Eliminando…');
            post("eliminar", { id: id })
                .done(function (res) {
                    if (!res || !res.ok) {
                        toast("error", (res && res.msg) || "No se pudo eliminar.");
                        $btn.prop("disabled", false)
                            .html('<i class="fa fa-trash"></i> Eliminar ticket');
                        return;
                    }
                    toast("success", res.msg || ("Ticket #" + id + " eliminado."));
                    ticketActual = null;
                    mostrarVistaLista();
                })
                .fail(function () {
                    toast("error", "Error de red al eliminar.");
                    $btn.prop("disabled", false)
                        .html('<i class="fa fa-trash"></i> Eliminar ticket');
                });
        };

        if (typeof swal === "function") {
            swal({
                title: "¿Eliminar ticket #" + id + "?",
                text: titulo
                    ? ('Se borrará "' + titulo + '" con comentarios y adjuntos. No se puede deshacer.')
                    : "Se borrará el ticket con comentarios y adjuntos. No se puede deshacer.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dd4b39",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (result && result.value) {
                    confirmar();
                }
            });
            return;
        }

        if (window.confirm("¿Eliminar ticket #" + id + "? No se puede deshacer.")) {
            confirmar();
        }
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
            data.solicitante_id = $("#hdGestSolicitante").val();
            data.prioridad = $("#hdGestPrioridad").val();
            data.tipo = $("#hdGestTipo").val();
            data.fecha_estimada = tipoUsaFechaEstimada(data.tipo)
                ? ($("#hdGestFechaEstimada").val() || "")
                : "";
            if ($("#hdGestSlaExento").length) {
                var eximir = $("#hdGestSlaExento").is(":checked");
                data.sla_exento = eximir ? 1 : 0;
                data.sla_exento_motivo = eximir
                    ? $.trim($("#hdGestSlaMotivo").val() || "")
                    : "";
                if (eximir && data.sla_exento_motivo.length < 5) {
                    toast("error", "Indique el motivo para cancelar el SLA (mín. 5 caracteres).");
                    return;
                }
            }
            if (!data.solicitante_id) {
                toast("error", "Elegí un solicitante.");
                return;
            }
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
        aplicarGuiaAlta();
        aplicarHashActual();
        hdBooting = false;
    });
});
