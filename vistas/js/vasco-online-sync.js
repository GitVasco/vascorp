$(function () {
    if ($("#panelVascoOnlineSync").length === 0) {
        return;
    }

    var $btnAnalizar = $("#btnAnalizarClientesVasco");
    var $btnProbar = $("#btnProbarConexionVasco");
    var $btnSincronizar = $("#btnSincronizarClientesVasco");
    var $btnReintentar = $("#btnReintentarFallidosVasco");
    var $btnDescargarRechazados = $("#btnDescargarRechazadosVasco");
    var $badgeConexion = $("#badgeConexionVasco");
    var $badgeEstadoSync = $("#badgeEstadoSync");
    var $barraProgreso = $("#barraProgresoSync");
    var $log = $("#vascoSyncLog");

    var estadoSync = {
        listos: 0,
        lotesEstimados: 0,
        traceId: null,
        lotesFallidos: [],
        rechazados: [],
        sincronizando: false,
    };

    var $btnAnalizarCuentas = $("#btnAnalizarCuentasVasco");
    var $btnSincronizarCuentas = $("#btnSincronizarCuentasVasco");
    var $btnReintentarCuentas = $("#btnReintentarFallidosCuentasVasco");
    var $btnDescargarRechazadosCuentas = $("#btnDescargarRechazadosCuentasVasco");
    var $badgeEstadoSyncCuentas = $("#badgeEstadoSyncCuentas");
    var $barraProgresoCuentas = $("#barraProgresoSyncCuentas");

    var estadoSyncCuentas = {
        listos: 0,
        lotesEstimados: 0,
        traceId: null,
        lotesFallidos: [],
        rechazados: [],
        sincronizando: false,
    };

    function escHtml(valor) {
        return $("<div>").text(valor == null ? "" : String(valor)).html();
    }

    function fmtNum(valor) {
        var n = parseInt(valor, 10);
        if (isNaN(n)) {
            return "—";
        }
        return n.toLocaleString("es-PE");
    }

    function fmtMoney(valor) {
        var n = parseFloat(valor);
        if (isNaN(n)) {
            return "—";
        }
        return n.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function agregarLog(linea) {
        var hora = new Date().toLocaleTimeString("es-PE", { hour12: false });
        var texto = "[" + hora + "] " + linea;
        var actual = $log.text();
        $log.text((actual ? actual + "\n" : "") + texto);
        $log.scrollTop($log[0].scrollHeight);
    }

    function estadoLabel(activo) {
        return activo
            ? '<span class="label label-success">Activo</span>'
            : '<span class="label label-default">Inactivo</span>';
    }

    function actualizarProgreso(lotesHechos, lotesTotal, docsEnviados, docsTotal) {
        var pct = lotesTotal > 0 ? Math.round((lotesHechos / lotesTotal) * 100) : 0;
        $barraProgreso.css("width", pct + "%");
        $("#textoProgresoSync").text(
            fmtNum(lotesHechos) +
                " de " +
                fmtNum(lotesTotal) +
                " lotes · " +
                fmtNum(docsEnviados) +
                " de " +
                fmtNum(docsTotal) +
                " documentos"
        );
    }

    function renderResumen(resumen) {
        $("#statTotalClientes").text(fmtNum(resumen.total));
        $("#statMaxLote").text(fmtNum(resumen.max_por_lote));
        $("#statLotesEstimados").text(fmtNum(resumen.lotes_estimados));
        $("#statListosSync").text(fmtNum(resumen.listos_sync));
        $("#statActivos").text(fmtNum(resumen.activos));
        $("#statGruposDuplicados").text(fmtNum(resumen.grupos_duplicados));
        $("#statRegDuplicados").text(
            fmtNum(resumen.codigos_extra_mismo_doc != null ? resumen.codigos_extra_mismo_doc : resumen.registros_en_duplicados)
        );
        $("#statSinDocumento").text(fmtNum(resumen.sin_documento));
        $("#statTipoInvalido").text(fmtNum(resumen.tipo_documento_invalido));

        $("#badgeCountDuplicados").text(fmtNum(resumen.grupos_duplicados));
        $("#badgeCountAdvertencias").text(
            fmtNum((parseInt(resumen.sin_documento, 10) || 0) + (parseInt(resumen.tipo_documento_invalido, 10) || 0))
        );

        estadoSync.listos = parseInt(resumen.listos_sync, 10) || 0;
        estadoSync.lotesEstimados = parseInt(resumen.lotes_estimados, 10) || 0;
        estadoSync.traceId = null;
        estadoSync.lotesFallidos = [];

        var puedeSync = estadoSync.listos > 0 && !estadoSync.sincronizando;
        $btnSincronizar.prop("disabled", !puedeSync);
        $btnReintentar.prop("disabled", true);

        $badgeEstadoSync
            .removeClass("label-warning label-success label-danger label-default")
            .addClass(puedeSync ? "label-success" : "label-warning")
            .html(
                puedeSync
                    ? '<i class="fa fa-check"></i> Listo'
                    : '<i class="fa fa-exclamation-triangle"></i> Revisar'
            );

        actualizarProgreso(0, estadoSync.lotesEstimados, 0, estadoSync.listos);
    }

    function renderDuplicados(duplicados) {
        var $tbody = $("#tablaDuplicadosVasco tbody");
        $tbody.empty();

        if (!duplicados || duplicados.length === 0) {
            $tbody.append(
                '<tr><td colspan="5">' +
                    '<div class="vasco-empty-state"><i class="fa fa-check-circle"></i>' +
                    "No hay documentos con varios códigos en vascorp.</div></td></tr>"
            );
            return;
        }

        duplicados.forEach(function (grupo, idx) {
            var sugeridoId = grupo.sugerido_id;
            var filasClientes = (grupo.clientes || [])
                .map(function (c) {
                    var sugerido = parseInt(c.id, 10) === parseInt(sugeridoId, 10);
                    return (
                        "<tr" +
                        (sugerido ? ' class="success"' : "") +
                        ">" +
                        "<td>" +
                        escHtml(c.id) +
                        (sugerido ? ' <span class="label label-success">Sugerido</span>' : "") +
                        "</td>" +
                        "<td>" +
                        escHtml(c.codigo) +
                        "</td>" +
                        "<td>" +
                        escHtml(c.nombre) +
                        "</td>" +
                        "<td>" +
                        estadoLabel(c.activo) +
                        "</td>" +
                        "<td>" +
                        escHtml(c.vendedor) +
                        "</td>" +
                        "<td>" +
                        escHtml(c.fecreg) +
                        "</td>" +
                        "</tr>"
                    );
                })
                .join("");

            $tbody.append(
                '<tr class="warning">' +
                    '<td><button type="button" class="btn btn-xs btn-default btnVerGrupoDup" data-target="#grupoDup' +
                    idx +
                    '">' +
                    '<i class="fa fa-plus"></i></button></td>' +
                    "<td>" +
                    escHtml(grupo.tipo_documento) +
                    "</td>" +
                    "<td><code>" +
                    escHtml(grupo.documento) +
                    "</code></td>" +
                    '<td><span class="badge bg-red">' +
                    escHtml(grupo.cantidad) +
                    "</span></td>" +
                    "<td>ID " +
                    escHtml(sugeridoId) +
                    "</td>" +
                    "</tr>" +
                    '<tr id="grupoDup' +
                    idx +
                    '" class="grupo-dup-detalle" style="display:none;">' +
                    '<td colspan="5" class="no-padding">' +
                    '<table class="table table-condensed table-bordered" style="margin:0;background:#fff;">' +
                    "<thead><tr>" +
                    "<th>ID</th><th>Código</th><th>Nombre</th><th>Estado</th><th>Vendedor</th><th>Fec. reg.</th>" +
                    "</tr></thead><tbody>" +
                    filasClientes +
                    "</tbody></table></td></tr>"
            );
        });
    }

    function renderAdvertencias(advertencias) {
        var $contenido = $("#contenidoAdvertenciasVasco");
        var sinDoc = advertencias.sin_documento || [];
        var tipoInv = advertencias.tipo_documento_invalido || [];

        if (sinDoc.length === 0 && tipoInv.length === 0) {
            $contenido.html(
                '<div class="vasco-empty-state"><i class="fa fa-check-circle"></i>' +
                    "Sin bloqueos por datos incompletos o tipo de documento inválido.</div>"
            );
            return;
        }

        var html = '<div style="padding:12px 15px;">';

        if (sinDoc.length > 0) {
            html += '<h5 style="margin-top:0;">Sin documento <span class="badge">' + sinDoc.length + "</span></h5>";
            html += "<ul class='list-unstyled' style='margin-bottom:15px;'>";
            sinDoc.slice(0, 20).forEach(function (c) {
                html += "<li style='padding:3px 0;'><code>" + escHtml(c.codigo) + "</code> — " + escHtml(c.nombre) + "</li>";
            });
            if (sinDoc.length > 20) {
                html += "<li class='text-muted'><em>… y " + (sinDoc.length - 20) + " más</em></li>";
            }
            html += "</ul>";
        }

        if (tipoInv.length > 0) {
            html += '<h5>Tipo documento inválido <span class="badge">' + tipoInv.length + "</span></h5>";
            html += "<ul class='list-unstyled'>";
            tipoInv.slice(0, 20).forEach(function (c) {
                html +=
                    "<li style='padding:3px 0;'><code>" +
                    escHtml(c.codigo) +
                    "</code> — tipo <strong>" +
                    escHtml(c.tipo_documento) +
                    "</strong> — " +
                    escHtml(c.nombre) +
                    "</li>";
            });
            if (tipoInv.length > 20) {
                html += "<li class='text-muted'><em>… y " + (tipoInv.length - 20) + " más</em></li>";
            }
            html += "</ul>";
        }

        html += "</div>";
        $contenido.html(html);

        if (sinDoc.length + tipoInv.length > 0) {
            $('a[href="#res-advertencias"]').tab("show");
        }
    }

    function enviarLote(numeroLote) {
        var data = {
            accion: "sincronizar-lote",
            lote: numeroLote,
        };

        if (estadoSync.traceId) {
            data.trace_id = estadoSync.traceId;
        }

        return $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: data,
            timeout: 130000,
        });
    }

    function ejecutarLotes(numerosLote, esReintento) {
        if (!numerosLote || numerosLote.length === 0) {
            return;
        }

        estadoSync.sincronizando = true;
        $btnSincronizar.prop("disabled", true);
        $btnReintentar.prop("disabled", true);
        $btnAnalizar.prop("disabled", true);

        var indice = 0;
        var totalLotes = estadoSync.lotesEstimados;
        var docsEnviados = 0;
        var totalInsert = 0;
        var totalUpdate = 0;
        var nuevosFallidos = [];
        var lotesParciales = [];
        var totalRechazadas = 0;

        if (!esReintento) {
            estadoSync.rechazados = [];
        }

        agregarLog(
            (esReintento ? "Reintentando " : "Iniciando sync de ") +
                fmtNum(estadoSync.listos) +
                " documentos en " +
                fmtNum(numerosLote.length) +
                " lote(s)…"
        );

        function siguiente() {
            if (indice >= numerosLote.length) {
                finalizar();
                return;
            }

            var numeroLote = numerosLote[indice];
            agregarLog("POST lote " + numeroLote + "…");

            enviarLote(numeroLote)
                .done(function (resp) {
                    if (resp && resp.trace_id) {
                        estadoSync.traceId = resp.trace_id;
                    }

                    if (resp && resp.skipped) {
                        agregarLog("Lote " + numeroLote + " vacío — fin anticipado.");
                        indice = numerosLote.length;
                        siguiente();
                        return;
                    }

                    if (!resp || !resp.ok) {
                        nuevosFallidos.push(numeroLote);
                        agregarLog("Lote " + numeroLote + " ERROR — " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    } else {
                        docsEnviados += parseInt(resp.customers_sent, 10) || 0;
                        totalInsert += parseInt(resp.inserted, 10) || 0;
                        totalUpdate += parseInt(resp.updated, 10) || 0;

                        var rechazadasLote = resp.failed && resp.failed.length ? resp.failed.length : 0;
                        var detalle =
                            "Lote " +
                            numeroLote +
                            (rechazadasLote > 0 ? " PARCIAL" : " OK") +
                            " — insertados " +
                            (resp.inserted || 0) +
                            ", actualizados " +
                            (resp.updated || 0);

                        if (rechazadasLote > 0) {
                            detalle += " (" + rechazadasLote + " fila(s) rechazadas)";
                            lotesParciales.push(numeroLote);
                            totalRechazadas += rechazadasLote;
                        }

                        agregarLog(detalle);

                        if (rechazadasLote > 0) {
                            logFailedClientes(resp, "  Lote " + numeroLote);
                            resp.failed.forEach(function (f) {
                                estadoSync.rechazados.push({
                                    lote: numeroLote,
                                    code: f.code || "",
                                    doc_type: f.doc_type || "",
                                    doc_number: f.doc_number || "",
                                    legal_name: f.legal_name || "",
                                    message: f.message || "error",
                                });
                            });
                        }
                    }

                    indice++;
                    actualizarProgreso(indice, totalLotes, docsEnviados, estadoSync.listos);
                    siguiente();
                })
                .fail(function (xhr) {
                    nuevosFallidos.push(numeroLote);
                    agregarLog("Lote " + numeroLote + " falló — red/servidor (" + xhr.status + ")");
                    indice++;
                    actualizarProgreso(indice, totalLotes, docsEnviados, estadoSync.listos);
                    siguiente();
                });
        }

        function finalizar() {
            estadoSync.sincronizando = false;
            estadoSync.lotesFallidos = nuevosFallidos;

            $btnAnalizar.prop("disabled", false);
            $btnSincronizar.prop("disabled", estadoSync.listos <= 0);
            $btnReintentar.prop("disabled", nuevosFallidos.length === 0);
            if ($btnDescargarRechazados.length) {
                $btnDescargarRechazados.prop("disabled", estadoSync.rechazados.length === 0);
            }

            if (nuevosFallidos.length > 0) {
                agregarLog(
                    "Sync terminada con " +
                        nuevosFallidos.length +
                        " lote(s) con error: " +
                        nuevosFallidos.join(", ") +
                        (lotesParciales.length > 0
                            ? " · " + lotesParciales.length + " lote(s) parcial(es), " + fmtNum(totalRechazadas) + " fila(s) rechazadas"
                            : "")
                );
                swal({
                    type: "warning",
                    title: "Sync con errores",
                    text:
                        nuevosFallidos.length +
                        " lote(s) fallaron (no se enviaron). Usa Reintentar fallidos." +
                        (lotesParciales.length > 0
                            ? " Además " + fmtNum(totalRechazadas) + " fila(s) fueron rechazadas por Vasco."
                            : ""),
                });
            } else if (lotesParciales.length > 0) {
                agregarLog(
                    "Sync parcial — insertados " +
                        fmtNum(totalInsert) +
                        ", actualizados " +
                        fmtNum(totalUpdate) +
                        ". " +
                        fmtNum(totalRechazadas) +
                        " fila(s) rechazadas en " +
                        lotesParciales.length +
                        " lote(s)."
                );
                swal({
                    type: "warning",
                    title: "Sync parcial",
                    text:
                        "Todos los lotes se enviaron. Insertados " +
                        fmtNum(totalInsert) +
                        ", actualizados " +
                        fmtNum(totalUpdate) +
                        ", pero Vasco rechazó " +
                        fmtNum(totalRechazadas) +
                        " fila(s) por datos inválidos (revisa el detalle).",
                });
            } else {
                agregarLog(
                    "Sync completa — insertados " + totalInsert + ", actualizados " + totalUpdate + "."
                );
                swal({
                    type: "success",
                    title: "Sync completa",
                    text:
                        "Enviados " +
                        fmtNum(docsEnviados) +
                        " documentos. Insertados: " +
                        fmtNum(totalInsert) +
                        ", actualizados: " +
                        fmtNum(totalUpdate) +
                        ".",
                });
            }
        }

        siguiente();
    }

    function actualizarProgresoCuentas(lotesHechos, lotesTotal, cuentasEnviadas, cuentasTotal) {
        var pct = lotesTotal > 0 ? Math.round((lotesHechos / lotesTotal) * 100) : 0;
        $barraProgresoCuentas.css("width", pct + "%");
        $("#textoProgresoSyncCuentas").text(
            fmtNum(lotesHechos) +
                " de " +
                fmtNum(lotesTotal) +
                " lotes · " +
                fmtNum(cuentasEnviadas) +
                " de " +
                fmtNum(cuentasTotal) +
                " clientes con deuda"
        );
    }

    function renderResumenCuentas(resumen) {
        $("#statCuentasConDeuda").text(fmtNum(resumen.clientes_con_deuda));
        $("#statCuentasDocsPendientes").text(fmtNum(resumen.docs_pendientes));
        $("#statCuentasDeudaTotal").text(fmtMoney(resumen.deuda_total));
        $("#statCuentasVencidoTotal").text(fmtMoney(resumen.vencido_total));
        $("#statCuentasLotes").text(fmtNum(resumen.lotes_estimados));
        $("#statCuentasMaxLote").text(fmtNum(resumen.max_por_lote));
        $("#statCuentasSinDoc").text(fmtNum(resumen.sin_documento));
        $("#statCuentasConsolidados").text(fmtNum(resumen.consolidados));

        estadoSyncCuentas.listos = parseInt(resumen.listos_sync, 10) || 0;
        estadoSyncCuentas.lotesEstimados = parseInt(resumen.lotes_estimados, 10) || 0;
        estadoSyncCuentas.traceId = null;
        estadoSyncCuentas.lotesFallidos = [];

        var puedeSync = estadoSyncCuentas.listos > 0 && !estadoSyncCuentas.sincronizando;
        $btnSincronizarCuentas.prop("disabled", !puedeSync);
        $btnReintentarCuentas.prop("disabled", true);

        $badgeEstadoSyncCuentas
            .removeClass("label-warning label-success label-danger label-default")
            .addClass(puedeSync ? "label-success" : "label-warning")
            .html(
                puedeSync
                    ? '<i class="fa fa-check"></i> Listo'
                    : '<i class="fa fa-exclamation-triangle"></i> Revisar'
            );

        $("#badgeCountCuentasMuestra").text(fmtNum(resumen.clientes_con_deuda));
        $("#badgeCountCuentasBloqueos").text(
            fmtNum((parseInt(resumen.sin_documento, 10) || 0) + (parseInt(resumen.exceso_documentos, 10) || 0))
        );

        actualizarProgresoCuentas(0, estadoSyncCuentas.lotesEstimados, 0, estadoSyncCuentas.listos);
    }

    function renderMuestraCuentas(muestra) {
        var $tbody = $("#tablaMuestraCuentasVasco tbody");
        $tbody.empty();

        if (!muestra || muestra.length === 0) {
            $tbody.append(
                '<tr><td colspan="5">' +
                    '<div class="vasco-empty-state"><i class="fa fa-check-circle"></i>' +
                    "No hay clientes con deuda pendiente para enviar.</div></td></tr>"
            );
            return;
        }

        muestra.forEach(function (c) {
            var docLabel = escHtml(c.tipo_documento) + " <code>" + escHtml(c.documento) + "</code>";
            if (parseInt(c.cant_codigos, 10) > 1) {
                docLabel += ' <span class="label label-info">' + escHtml(c.cant_codigos) + " cód.</span>";
            }
            $tbody.append(
                "<tr>" +
                    "<td>" + docLabel + "</td>" +
                    "<td>" + escHtml(c.nombre) + "</td>" +
                    '<td class="text-right">' + fmtMoney(c.deuda_total) + "</td>" +
                    '<td class="text-right">' + fmtMoney(c.vencido_total) + "</td>" +
                    '<td class="text-center"><span class="badge bg-blue">' + escHtml(c.cant_docs) + "</span></td>" +
                    "</tr>"
            );
        });
    }

    function renderBloqueosCuentas(bloqueos) {
        var $contenido = $("#contenidoBloqueosCuentasVasco");
        var sinDoc = (bloqueos && bloqueos.sin_documento) || [];
        var exceso = (bloqueos && bloqueos.exceso_documentos) || [];

        if (sinDoc.length === 0 && exceso.length === 0) {
            $contenido.html(
                '<div class="vasco-empty-state"><i class="fa fa-check-circle"></i>' +
                    "Sin bloqueos detectados.</div>"
            );
            return;
        }

        var html = '<div style="padding:12px 15px;">';

        if (sinDoc.length > 0) {
            html += '<h5 style="margin-top:0;">Sin documento válido <span class="badge">' + sinDoc.length + "</span></h5>";
            html += "<ul class='list-unstyled' style='margin-bottom:15px;'>";
            sinDoc.slice(0, 20).forEach(function (c) {
                html +=
                    "<li style='padding:3px 0;'><code>" +
                    escHtml(c.codigo) +
                    "</code> — " +
                    escHtml(c.nombre) +
                    " — S/ " +
                    fmtMoney(c.deuda) +
                    " (" +
                    escHtml(c.cant_docs) +
                    " docs)</li>";
            });
            if (sinDoc.length > 20) {
                html += "<li class='text-muted'><em>… y " + (sinDoc.length - 20) + " más</em></li>";
            }
            html += "</ul>";
        }

        if (exceso.length > 0) {
            html += '<h5>Exceso de documentos (&gt;500) <span class="badge">' + exceso.length + "</span></h5>";
            html += "<ul class='list-unstyled'>";
            exceso.slice(0, 20).forEach(function (c) {
                html +=
                    "<li style='padding:3px 0;'><code>" +
                    escHtml(c.documento) +
                    "</code> — " +
                    escHtml(c.nombre) +
                    " — " +
                    escHtml(c.cant_docs) +
                    " docs</li>";
            });
            if (exceso.length > 20) {
                html += "<li class='text-muted'><em>… y " + (exceso.length - 20) + " más</em></li>";
            }
            html += "</ul>";
        }

        html += "</div>";
        $contenido.html(html);
    }

    function descClienteFailed(f) {
        var partes = [];
        if (f.code) {
            partes.push(String(f.code));
        }
        var doc = (f.doc_type || "") + " " + (f.doc_number || "");
        doc = doc.replace(/\s+/g, " ").trim();
        if (doc) {
            partes.push(doc);
        }
        if (f.legal_name) {
            partes.push(String(f.legal_name));
        }
        return partes.length ? partes.join(" · ") : "cliente sin identificar";
    }

    function logFailedClientes(resp, prefijo) {
        var failed = resp && resp.failed ? resp.failed : [];
        var i;

        if (!failed.length) {
            return;
        }

        for (i = 0; i < failed.length && i < 8; i++) {
            agregarLog(prefijo + " [" + descClienteFailed(failed[i]) + "] " + (failed[i].message || "error"));
        }

        if (failed.length > 8) {
            agregarLog(prefijo + " … y " + (failed.length - 8) + " fila(s) rechazada(s) más");
        }
    }

    function descargarCsv(nombreArchivo, encabezados, filas) {
        var lineas = [encabezados.join(",")];
        filas.forEach(function (fila) {
            var cols = fila.map(function (valor) {
                var texto = valor == null ? "" : String(valor);
                if (/[",\n]/.test(texto)) {
                    texto = '"' + texto.replace(/"/g, '""') + '"';
                }
                return texto;
            });
            lineas.push(cols.join(","));
        });

        var contenido = "\ufeff" + lineas.join("\r\n");
        var blob = new Blob([contenido], { type: "text/csv;charset=utf-8;" });
        var url = URL.createObjectURL(blob);
        var a = document.createElement("a");
        a.href = url;
        a.download = nombreArchivo;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function acumularRechazadosCuentas(resp, numeroLote) {
        var failed = resp && resp.failed ? resp.failed : [];
        failed.forEach(function (f) {
            estadoSyncCuentas.rechazados.push({
                lote: numeroLote,
                doc_type: f.doc_type || "",
                doc_number: f.doc_number || "",
                message: f.message || "error",
            });
        });
    }

    function logFailedCuentas(resp, prefijo) {
        var failed = resp && resp.failed ? resp.failed : [];
        var i;

        if (!failed.length) {
            return;
        }

        for (i = 0; i < failed.length && i < 8; i++) {
            agregarLog(
                prefijo +
                    " [" +
                    (failed[i].doc_type || "?") +
                    " " +
                    (failed[i].doc_number || "?") +
                    "] " +
                    (failed[i].message || "error")
            );
        }

        if (failed.length > 8) {
            agregarLog(prefijo + " … y " + (failed.length - 8) + " fallo(s) más");
        }
    }

    function enviarLoteCuentas(numeroLote) {
        var data = {
            accion: "sincronizar-lote-cuentas",
            lote: numeroLote,
        };

        if (estadoSyncCuentas.traceId) {
            data.trace_id = estadoSyncCuentas.traceId;
        }

        return $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: data,
            timeout: 180000,
        });
    }

    function enviarFinalizeCuentas(numeroLote) {
        return $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: {
                accion: "finalizar-cuentas",
                lote: numeroLote,
                trace_id: estadoSyncCuentas.traceId,
            },
            timeout: 180000,
        });
    }

    function ejecutarLotesCuentas(numerosLote, esReintento) {
        if (!numerosLote || numerosLote.length === 0) {
            return;
        }

        if (!esReintento) {
            estadoSyncCuentas.traceId = null;
        }

        estadoSyncCuentas.sincronizando = true;
        $btnSincronizarCuentas.prop("disabled", true);
        $btnReintentarCuentas.prop("disabled", true);
        $btnAnalizarCuentas.prop("disabled", true);

        var indice = 0;
        var totalLotes = estadoSyncCuentas.lotesEstimados;
        var cuentasEnviadas = 0;
        var cuentasGuardadas = 0;
        var totalDocs = 0;
        var nuevosFallidos = [];

        if (!esReintento) {
            estadoSyncCuentas.rechazados = [];
        }

        agregarLog(
            (esReintento ? "Reintentando cuentas " : "Iniciando sync cuentas ") +
                fmtNum(estadoSyncCuentas.listos) +
                " clientes en " +
                fmtNum(numerosLote.length) +
                " lote(s)…"
        );

        function siguiente() {
            if (indice >= numerosLote.length) {
                finalizarCuentas();
                return;
            }

            var numeroLote = numerosLote[indice];
            agregarLog("POST cuentas lote " + numeroLote + "…");

            enviarLoteCuentas(numeroLote)
                .done(function (resp) {
                    if (resp && resp.trace_id) {
                        estadoSyncCuentas.traceId = resp.trace_id;
                    }

                    if (resp && resp.skipped) {
                        agregarLog("Lote cuentas " + numeroLote + " vacío — fin.");
                        indice = numerosLote.length;
                        siguiente();
                        return;
                    }

                    if (!resp || !resp.ok) {
                        nuevosFallidos.push(numeroLote);
                        agregarLog(
                            "Lote cuentas " + numeroLote + " ERROR — " + (resp && resp.msg ? resp.msg : "respuesta inválida")
                        );
                        logFailedCuentas(resp, "  ↳");
                        acumularRechazadosCuentas(resp, numeroLote);
                    } else {
                        var enviados = parseInt(resp.accounts_sent, 10) || 0;
                        var guardados = parseInt(resp.processed, 10) || 0;
                        var docsVasco = parseInt(resp.documents, 10) || parseInt(resp.documents_sent, 10) || 0;
                        var loteConError = false;

                        cuentasEnviadas += enviados;
                        cuentasGuardadas += guardados;
                        totalDocs += docsVasco;

                        agregarLog(
                            "Lote cuentas " +
                                numeroLote +
                                " — enviados " +
                                enviados +
                                ", guardados en Vasco " +
                                guardados +
                                ", docs " +
                                docsVasco
                        );

                        if (guardados === 0 && enviados > 0) {
                            loteConError = true;
                            agregarLog("  ↳ Ningún cliente se guardó (¿maestro de clientes sync?)");
                            logFailedCuentas(resp, "  ↳");
                            acumularRechazadosCuentas(resp, numeroLote);
                        } else if (resp.failed && resp.failed.length > 0) {
                            logFailedCuentas(resp, "  ↳ parcial");
                            acumularRechazadosCuentas(resp, numeroLote);
                            if (guardados < enviados) {
                                loteConError = true;
                            }
                        }

                        if (loteConError) {
                            nuevosFallidos.push(numeroLote);
                        }
                    }

                    indice++;
                    actualizarProgresoCuentas(indice, totalLotes, cuentasEnviadas, estadoSyncCuentas.listos);
                    siguiente();
                })
                .fail(function (xhr) {
                    nuevosFallidos.push(numeroLote);
                    agregarLog("Lote cuentas " + numeroLote + " falló — red/servidor (" + xhr.status + ")");
                    indice++;
                    actualizarProgresoCuentas(indice, totalLotes, cuentasEnviadas, estadoSyncCuentas.listos);
                    siguiente();
                });
        }

        function terminarUiCuentas(purged, finalizeOk) {
            estadoSyncCuentas.sincronizando = false;
            estadoSyncCuentas.lotesFallidos = nuevosFallidos;

            $btnAnalizarCuentas.prop("disabled", false);
            $btnSincronizarCuentas.prop("disabled", estadoSyncCuentas.listos <= 0);
            $btnReintentarCuentas.prop("disabled", nuevosFallidos.length === 0);
            if ($btnDescargarRechazadosCuentas.length) {
                $btnDescargarRechazadosCuentas.prop("disabled", estadoSyncCuentas.rechazados.length === 0);
            }

            if (nuevosFallidos.length > 0) {
                agregarLog("Sync cuentas terminada con " + nuevosFallidos.length + " lote(s) con error.");
                swal({
                    type: "warning",
                    title: "Sync cuentas con errores",
                    text: "Algunos lotes fallaron. Usa Reintentar fallidos. No se ejecutó finalize (purga).",
                });
            } else if (cuentasGuardadas > 0) {
                var resumen =
                    "Guardados en Vasco: " +
                    fmtNum(cuentasGuardadas) +
                    " clientes (" +
                    fmtNum(totalDocs) +
                    " documentos)";

                if (finalizeOk) {
                    resumen += ". Purgados (sin deuda): " + fmtNum(purged);
                } else if (purged === null) {
                    resumen += ". Finalize no ejecutado.";
                } else {
                    resumen += ". Finalize falló — revisa el log.";
                }

                agregarLog("Sync cuentas completa — " + resumen + ".");
                swal({
                    type: finalizeOk ? "success" : "warning",
                    title: finalizeOk ? "Sync cuentas completa" : "Sync cuentas con advertencia",
                    text: resumen + " Revisa Operación → Estados de cuenta.",
                });
            } else {
                agregarLog("Sync cuentas terminada sin registros guardados en Vasco.");
                swal({
                    type: "warning",
                    title: "Nada guardado en Vasco",
                    text: "Se enviaron lotes pero Vasco no guardó cuentas. Revisa el log: suele ser cliente no encontrado (sync Clientes primero) o errores de validación.",
                });
            }
        }

        function finalizarCuentas() {
            var debeFinalize =
                nuevosFallidos.length === 0 &&
                cuentasGuardadas > 0 &&
                estadoSyncCuentas.traceId &&
                estadoSyncCuentas.lotesEstimados > 0;

            if (!debeFinalize) {
                terminarUiCuentas(null, false);
                return;
            }

            var batchFinalize = estadoSyncCuentas.lotesEstimados + 1;

            agregarLog(
                "POST finalize (batch " +
                    batchFinalize +
                    ") — purga clientes que ya no deben en Vasco…"
            );

            enviarFinalizeCuentas(batchFinalize)
                .done(function (resp) {
                    var purged = 0;
                    var finalizeOk = false;

                    if (resp && resp.ok) {
                        purged = parseInt(resp.purged, 10) || 0;
                        finalizeOk = true;
                        agregarLog("Finalize OK — purgados en Vasco: " + fmtNum(purged));
                    } else {
                        agregarLog(
                            "Finalize ERROR — " + (resp && resp.msg ? resp.msg : "respuesta inválida")
                        );
                        logFailedCuentas(resp, "  ↳");
                    }

                    terminarUiCuentas(purged, finalizeOk);
                })
                .fail(function (xhr) {
                    agregarLog("Finalize falló — red/servidor (" + xhr.status + ")");
                    terminarUiCuentas(0, false);
                });
        }

        siguiente();
    }

    $btnAnalizarCuentas.on("click", function () {
        var $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Analizando…');
        agregarLog("Auditoría de cuentas por cobrar…");

        $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: { accion: "auditar-cuentas" },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error cuentas: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal({
                        type: "error",
                        title: "No se pudo analizar",
                        text: resp && resp.msg ? resp.msg : "Error desconocido",
                    });
                    return;
                }

                renderResumenCuentas(resp.resumen);
                renderMuestraCuentas(resp.muestra);
                renderBloqueosCuentas(resp.bloqueos);

                agregarLog(
                    "Cuentas OK — con deuda: " +
                        resp.resumen.clientes_con_deuda +
                        ", docs: " +
                        resp.resumen.docs_pendientes +
                        ", bloqueados: " +
                        resp.resumen.bloqueados_envio
                );
            })
            .fail(function (xhr) {
                agregarLog("Fallo auditoría cuentas (" + xhr.status + ")");
                swal({
                    type: "error",
                    title: "Error de conexión",
                    text: "No se pudo consultar cuenta_ctejf.",
                });
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-search"></i> Analizar cuentas');
            });
    });

    $btnSincronizarCuentas.on("click", function () {
        if (estadoSyncCuentas.lotesEstimados <= 0) {
            swal({
                type: "warning",
                title: "Sin datos",
                text: "Analiza primero para calcular los lotes.",
            });
            return;
        }

        var lotes = [];
        var i;
        for (i = 1; i <= estadoSyncCuentas.lotesEstimados; i++) {
            lotes.push(i);
        }

        ejecutarLotesCuentas(lotes, false);
    });

    $btnReintentarCuentas.on("click", function () {
        if (estadoSyncCuentas.lotesFallidos.length === 0) {
            return;
        }
        ejecutarLotesCuentas(estadoSyncCuentas.lotesFallidos.slice(), true);
    });

    $btnDescargarRechazadosCuentas.on("click", function () {
        if (!estadoSyncCuentas.rechazados.length) {
            swal("Sin datos", "No hay cuentas rechazadas para exportar.", "info");
            return;
        }

        var filas = estadoSyncCuentas.rechazados.map(function (r) {
            return [r.lote, r.doc_type, r.doc_number, r.message];
        });

        descargarCsv(
            "cuentas-rechazadas-" + (estadoSyncCuentas.traceId || "vasco") + ".csv",
            ["lote", "tipo_doc", "documento", "motivo"],
            filas
        );
    });

    $btnAnalizar.on("click", function () {
        var $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Analizando…');
        agregarLog("Iniciando auditoría de clientes…");

        $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: { accion: "auditar-clientes" },
        })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    agregarLog("Error: " + (resp && resp.msg ? resp.msg : "respuesta inválida"));
                    swal({
                        type: "error",
                        title: "No se pudo analizar",
                        text: resp && resp.msg ? resp.msg : "Error desconocido",
                    });
                    return;
                }

                renderResumen(resp.resumen);
                renderDuplicados(resp.duplicados);
                renderAdvertencias(resp.advertencias);

                agregarLog(
                    "OK — documentos a enviar: " +
                        resp.resumen.listos_sync +
                        ", duplicados: " +
                        resp.resumen.grupos_duplicados +
                        ", bloqueados: " +
                        resp.resumen.bloqueados_envio
                );

                if (resp.resumen.grupos_duplicados > 0) {
                    agregarLog(
                        resp.resumen.grupos_duplicados +
                            " documentos con varios códigos — se enviará 1 registro por documento."
                    );
                }
            })
            .fail(function (xhr) {
                agregarLog("Fallo de red o servidor (" + xhr.status + ")");
                swal({
                    type: "error",
                    title: "Error de conexión",
                    text: "No se pudo consultar la base de datos.",
                });
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-search"></i> Analizar clientes');
            });
    });

    $btnSincronizar.on("click", function () {
        if (estadoSync.lotesEstimados <= 0) {
            swal({
                type: "warning",
                title: "Sin datos",
                text: "Analiza primero para calcular los lotes.",
            });
            return;
        }

        var lotes = [];
        var i;
        for (i = 1; i <= estadoSync.lotesEstimados; i++) {
            lotes.push(i);
        }

        ejecutarLotes(lotes, false);
    });

    $btnReintentar.on("click", function () {
        if (estadoSync.lotesFallidos.length === 0) {
            return;
        }

        ejecutarLotes(estadoSync.lotesFallidos.slice(), true);
    });

    $btnDescargarRechazados.on("click", function () {
        if (!estadoSync.rechazados.length) {
            swal("Sin datos", "No hay clientes rechazados para exportar.", "info");
            return;
        }

        var filas = estadoSync.rechazados.map(function (r) {
            return [r.lote, r.code, r.doc_type, r.doc_number, r.legal_name, r.message];
        });

        descargarCsv(
            "clientes-rechazados-" + (estadoSync.traceId || "vasco") + ".csv",
            ["lote", "codigo", "tipo_doc", "documento", "nombre", "motivo"],
            filas
        );
    });

    $(document).on("click", ".btnVerGrupoDup", function () {
        var target = $(this).data("target");
        var $fila = $(target);
        var visible = $fila.is(":visible");
        $fila.toggle(!visible);
        $(this).find("i").toggleClass("fa-plus fa-minus", !visible);
    });

    $btnProbar.on("click", function () {
        var $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');

        $badgeConexion
            .removeClass("label-default label-success label-danger")
            .addClass("label-warning")
            .html('<i class="fa fa-spinner fa-spin"></i> Probando…');

        agregarLog("GET /health…");

        $.ajax({
            url: "ajax/vasco-sync.ajax.php",
            method: "GET",
            dataType: "json",
            data: { accion: "probar-conexion" },
            timeout: 35000,
        })
            .done(function (resp) {
                if (resp && resp.ok) {
                    $badgeConexion
                        .removeClass("label-warning label-danger label-default")
                        .addClass("label-success")
                        .html('<i class="fa fa-check"></i> Conectado');
                    agregarLog("Health OK — " + (resp.msg || "disponible") + (resp.url ? " (" + resp.url + ")" : ""));
                } else {
                    $badgeConexion
                        .removeClass("label-warning label-success label-default")
                        .addClass("label-danger")
                        .html('<i class="fa fa-times"></i> Error');
                    agregarLog("Health falló — " + (resp && resp.msg ? resp.msg : "error desconocido"));
                }
            })
            .fail(function (xhr) {
                $badgeConexion
                    .removeClass("label-warning label-success label-default")
                    .addClass("label-danger")
                    .html('<i class="fa fa-times"></i> Error');
                agregarLog("Health falló — error de red (" + xhr.status + ")");
            })
            .always(function () {
                $btn.prop("disabled", false).html('<i class="fa fa-refresh"></i> Probar');
            });
    });

    $('a[href="#tab-cuentas"]').on("shown.bs.tab", function () {
        agregarLog("Pestaña Cuentas — estados de cuenta (cuenta_ctejf).");
    });

    $('a[href="#tab-clientes"]').on("shown.bs.tab", function () {
        agregarLog("Pestaña Clientes.");
    });
});
