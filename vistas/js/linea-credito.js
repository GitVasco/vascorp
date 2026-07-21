(function () {
    var lcContext = { cliente: "", nombre: "", pisoCategoria: null };
    var lcGrupoPiso = null;
    var lcGrupoFiltro = "";
    var lcGrupoDeudaTotal = 0;
    var tablaLc = null;
    var lcUrlSyncLock = false;

    function fmtMoney(v) {
        if (v === null || v === undefined || v === "") return "—";
        return "S/ " + Number(v).toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /** Solo dígitos → entero (p. ej. "200.000" → 200000). */
    function parseMiles(str) {
        var digits = String(str == null ? "" : str).replace(/\D/g, "");
        if (!digits) {
            return 0;
        }
        return parseInt(digits, 10) || 0;
    }

    /** Entero → miles con punto (es-PE): 200000 → "200.000". */
    function fmtMilesInput(n) {
        var num = Math.floor(Math.abs(Number(n) || 0));
        if (!num) {
            return "";
        }
        return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function sincronizarInputMiles($input) {
        var raw = parseMiles($input.val());
        var formatted = fmtMilesInput(raw);
        var $form = $input.closest("form");
        var $raw = $form.find(".lc-miles-raw");
        var $preview = $form.find(".lc-miles-preview");

        if ($input.val() !== formatted) {
            $input.val(formatted);
        }
        if ($raw.length) {
            $raw.val(raw > 0 ? String(raw) : "");
        }
        if ($preview.length) {
            if (raw > 0) {
                $preview.html("Se registrará: <strong>" + fmtMoney(raw) + "</strong>");
            } else {
                $preview.text("Escriba el monto; se formatea en miles automáticamente.");
            }
        }
        return raw;
    }

    function esc(s) {
        return String(s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function lcLeerParamsUrl() {
        try {
            var params = new URLSearchParams(window.location.search || "");
            return {
                grupo: String(params.get("grupo") || "").trim(),
                cliente: String(params.get("cliente") || "").trim(),
            };
        } catch (e) {
            return { grupo: "", cliente: "" };
        }
    }

    /**
     * Mantiene ?ruta=linea-credito&grupo=…&cliente=… para poder refrescar/compartir.
     * opts.grupo / opts.cliente: string o "" para borrar; undefined = dejar el estado actual.
     */
    function lcActualizarUrl(opts) {
        if (lcUrlSyncLock || !window.history || !window.history.replaceState) {
            return;
        }

        opts = opts || {};
        var params;

        try {
            params = new URLSearchParams(window.location.search || "");
        } catch (e) {
            return;
        }

        if (!params.get("ruta")) {
            params.set("ruta", "linea-credito");
        }

        var grupo = opts.grupo !== undefined ? String(opts.grupo || "").trim() : String(lcGrupoFiltro || "").trim();
        var cliente = opts.cliente !== undefined
            ? String(opts.cliente || "").trim()
            : String(lcContext.cliente || "").trim();

        if (grupo) {
            params.set("grupo", grupo);
        } else {
            params.delete("grupo");
        }

        if (cliente) {
            params.set("cliente", cliente);
        } else {
            params.delete("cliente");
        }

        var qs = params.toString();
        var url = window.location.pathname + (qs ? "?" + qs : "") + (window.location.hash || "");
        window.history.replaceState(null, "", url);
    }

    function postLc(accion, data, opts) {
        var payload;

        if (typeof data === "string") {
            payload = "accion=" + encodeURIComponent(accion) + (data ? "&" + data : "");
        } else {
            payload = $.extend({ accion: accion }, data || {});
        }

        return $.ajax({
            url: "ajax/linea-credito/linea-credito.ajax.php",
            method: "POST",
            dataType: "json",
            timeout: (opts && opts.timeout) || 0,
            data: payload,
        });
    }

    function msgAjaxError(xhr, fallback) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
            return xhr.responseJSON.msg;
        }
        if (xhr && xhr.responseText) {
            try {
                var parsed = JSON.parse(xhr.responseText);
                if (parsed && parsed.msg) {
                    return parsed.msg;
                }
            } catch (e) { /* respuesta no JSON */ }
        }
        return fallback || "Error de comunicación.";
    }

    function scoreColor(score) {
        var n = Number(score);
        if (isNaN(n)) return "default";
        if (n >= 90) return "success";
        if (n >= 80) return "primary";
        if (n >= 70) return "info";
        if (n >= 60) return "warning";
        return "danger";
    }

    function scoreEtiqueta(score) {
        var n = Number(score);
        if (isNaN(n)) return "Sin dato";
        if (n >= 90) return "Excelente";
        if (n >= 80) return "Bueno";
        if (n >= 70) return "Aceptable";
        if (n >= 60) return "Riesgo medio";
        return "Riesgo alto";
    }

    function accionColor(texto) {
        var t = String(texto || "").toLowerCase();
        if (t.indexOf("incrementar") >= 0 || t.indexOf("aprobar") >= 0) return "success";
        if (t.indexOf("mantener") >= 0) return "primary";
        if (t.indexOf("reducir") >= 0) return "warning";
        if (t.indexOf("suspender") >= 0) return "danger";
        if (t.indexOf("manual") >= 0) return "warning";
        return "default";
    }

    function utilColor(pct) {
        var n = Number(pct);
        if (isNaN(n)) return "default";
        if (n < 50) return "success";
        if (n < 80) return "warning";
        return "danger";
    }

    function renderUtilBar(utilizacionPct, opts) {
        opts = opts || {};
        var utilClr = utilColor(utilizacionPct);
        var utilPct = utilizacionPct != null && utilizacionPct !== "" ? Math.min(100, Number(utilizacionPct)) : 0;
        var label = opts.label || "Utilización de línea";
        var wrapCls = "lc-util-bar-wrap" + (opts.compact ? " lc-util-bar-wrap--compact" : "");

        return (
            '<div class="' + wrapCls + '">' +
            '<div class="lc-util-bar-head"><span>' + esc(label) + "</span><strong class=\"lc-util-val--" + utilClr + '">' +
            (utilizacionPct != null && utilizacionPct !== "" ? utilPct.toFixed(1) + "%" : "—") + "</strong></div>" +
            '<div class="lc-util-bar"><div class="lc-util-bar-fill lc-util-bar-fill--' + utilClr + '" style="width:' + utilPct + '%"></div></div>' +
            (opts.leyenda !== false
                ? '<div class="lc-util-leyenda"><span>0%</span><span>50%</span><span>80%</span><span>100%</span></div>'
                : "") +
            (opts.nota ? '<p class="lc-util-nota">' + esc(opts.nota) + "</p>" : "") +
            "</div>"
        );
    }

    function cupoColor(cupo, deuda, recomendada) {
        var c = Number(cupo);
        var d = Number(deuda);
        var r = Number(recomendada);
        if (r > 0 && d > r) return "danger";
        if (c <= 0) return "danger";
        if (c < r * 0.2) return "warning";
        return "success";
    }

    function buildVeredicto(c) {
        var riesgo = Number(c.score_riesgo);
        var util = Number(c.utilizacion_pct);
        var cupo = Number(c.cupo_disponible);
        var deuda = Number(c.deuda_actual);
        var rec = Number(c.linea_recomendada);
        var apr = Number(c.linea_aprobada);

        if (!isNaN(riesgo) && riesgo < 60) {
            return {
                cls: "danger",
                icon: "fa-exclamation-triangle",
                titulo: "Riesgo alto — revisar con cautela",
                detalle: "Score de riesgo " + riesgo.toFixed(1) + ". Evaluar historial de pago antes de ampliar cupo.",
            };
        }
        if (rec > 0 && deuda > rec) {
            return {
                cls: "danger",
                icon: "fa-ban",
                titulo: "Cliente excedido sobre línea recomendada",
                detalle: "Deuda " + fmtMoney(deuda) + " supera recomendada " + fmtMoney(rec) + ". Regularizar antes de nuevo crédito.",
            };
        }
        if (!isNaN(util) && util >= 80) {
            return {
                cls: "warning",
                icon: "fa-warning",
                titulo: "Línea muy utilizada (" + util.toFixed(1) + "%)",
                detalle: "Poco margen disponible. Cupo restante: " + fmtMoney(cupo) + ".",
            };
        }
        if (cupo > 0 && (!isNaN(riesgo) ? riesgo >= 70 : true)) {
            return {
                cls: "success",
                icon: "fa-check-circle",
                titulo: "Margen de crédito disponible",
                detalle: "Cupo " + fmtMoney(cupo) + " · Utilización " + (isNaN(util) ? "—" : util.toFixed(1) + "%") +
                    (apr > 0 ? " · Línea aprobada " + fmtMoney(apr) : " · Sin línea aprobada manual"),
            };
        }
        if (String(c.accion_linea || "").toLowerCase().indexOf("mantener") >= 0) {
            return {
                cls: "primary",
                icon: "fa-check",
                titulo: "Mantener línea actual",
                detalle: "La IC sugiere conservar el cupo vigente. Cupo disponible: " + fmtMoney(cupo) + ".",
            };
        }
        return {
            cls: "info",
            icon: "fa-info-circle",
            titulo: "Revisar situación crediticia",
            detalle: "Deuda " + fmtMoney(deuda) + " · Recomendada " + fmtMoney(rec) + " · Cupo " + fmtMoney(cupo) + ".",
        };
    }

    function parseDetalleHistorial(detalle) {
        if (!detalle) return null;
        try {
            return typeof detalle === "string" ? JSON.parse(detalle) : detalle;
        } catch (e) {
            return null;
        }
    }

    function lineaCreditoRef(c) {
        var tieneApr = c.linea_aprobada !== null && c.linea_aprobada !== "" && Number(c.linea_aprobada) > 0;
        return {
            valor: tieneApr ? Number(c.linea_aprobada) : Number(c.linea_recomendada || 0),
            etiqueta: tieneApr ? "Aprobada por créditos" : "Recomendada por IC (sin aprobar)",
            esAprobada: tieneApr,
        };
    }

    function lineaCreditoRefGrupo(c) {
        var tieneApr = c.linea_aprobada !== null && c.linea_aprobada !== "" && Number(c.linea_aprobada) > 0;
        return {
            valor: tieneApr ? Number(c.linea_aprobada) : Number(c.linea_recomendada || 0),
            etiqueta: tieneApr ? "Aprobada por créditos (grupo)" : "Recomendada por IC (sin aprobar)",
            esAprobada: tieneApr,
        };
    }

    function buildVeredictoGrupo(data) {
        var c = data.consolidado || {};
        var tot = data.totales_grupo || data.totales_cartera || {};
        var riesgo = Number(c.score_riesgo);
        var util = Number(c.utilizacion_pct);
        var cupo = Number(c.cupo_disponible);
        var deuda = Number(c.deuda_actual);
        var rec = Number(c.linea_recomendada);
        var apr = Number(c.linea_aprobada);
        var refLinea = apr > 0 ? apr : rec;

        if (!isNaN(riesgo) && riesgo < 60) {
            return {
                cls: "danger",
                icon: "fa-exclamation-triangle",
                titulo: "Grupo con riesgo alto — revisar con cautela",
                detalle: "Score consolidado " + riesgo.toFixed(1) + ". Evalúe el historial de pago del grupo antes de ampliar cupo.",
            };
        }
        if (refLinea > 0 && deuda > refLinea) {
            return {
                cls: "danger",
                icon: "fa-ban",
                titulo: "Grupo excedido sobre línea " + (apr > 0 ? "aprobada" : "recomendada"),
                detalle: "Deuda consolidada " + fmtMoney(deuda) + " supera " + fmtMoney(refLinea) + ".",
            };
        }
        if (!isNaN(util) && util >= 80) {
            return {
                cls: "warning",
                icon: "fa-warning",
                titulo: "Línea del grupo muy utilizada (" + util.toFixed(1) + "%)",
                detalle: "Poco margen disponible: " + fmtMoney(cupo) + ".",
            };
        }
        if (cupo > 0) {
            return {
                cls: "success",
                icon: "fa-check-circle",
                titulo: "Margen de crédito disponible a nivel grupo",
                detalle: tot.clientes + " local(es) · Cupo " + fmtMoney(cupo) +
                    (apr > 0 ? " · Línea aprobada " + fmtMoney(apr) : " · Referencia IC " + fmtMoney(rec)),
            };
        }
        return {
            cls: "info",
            icon: "fa-info-circle",
            titulo: "Revisar situación crediticia del grupo",
            detalle: "Deuda " + fmtMoney(deuda) + " · Referencia " + fmtMoney(refLinea) + " · Cupo " + fmtMoney(cupo) + ".",
        };
    }

    function lineaGrupoDisplay(c, refGrupo) {
        if (refGrupo.esAprobada) {
            return {
                titulo: "Línea aprobada",
                valor: fmtMoney(c.linea_aprobada),
                nota: "Registrada por créditos",
                cls: "lc-hero-box--linea-aprobada",
            };
        }

        var rec = Number(c.linea_recomendada || 0);
        if (rec > 0) {
            return {
                titulo: "Línea vigente (IC)",
                valor: fmtMoney(rec),
                nota: "Referencia IC · Sin aprobación manual",
                cls: "lc-hero-box--linea-ref",
            };
        }

        return {
            titulo: "Línea vigente",
            valor: '<span class="text-muted">—</span>',
            nota: "Sin dato de IC",
            cls: "lc-hero-box--linea",
        };
    }

    function textoPisoCategoria(piso) {
        if (!piso || !piso.activo || !(Number(piso.monto) > 0)) {
            return "";
        }
        var cat = piso.categoria_nombre ? " (" + piso.categoria_nombre + ")" : "";
        return "Piso categoría" + cat + ": " + fmtMoney(piso.monto);
    }

    function renderCategoriaBadge(piso) {
        if (!piso || !piso.tiene_categoria || !piso.categoria_nombre) {
            return (
                '<div class="lc-cat-chip lc-cat-chip--empty">' +
                '<i class="fa fa-tag"></i> Sin categoría comercial</div>'
            );
        }

        var color = piso.categoria_color || "#777777";
        var meta = [];
        if (piso.monto_compras_anual != null && Number(piso.monto_compras_anual) > 0) {
            meta.push("Compra anual mín. " + fmtMoney(piso.monto_compras_anual));
        }
        if (piso.activo && Number(piso.monto) > 0) {
            meta.push("Piso línea " + fmtMoney(piso.monto));
        } else {
            meta.push("Sin piso de línea definido");
        }

        return (
            '<div class="lc-cat-chip">' +
            '<span class="lc-cat-chip__badge" style="background:' + esc(color) + '">' +
            esc(piso.categoria_nombre) + "</span>" +
            '<span class="lc-cat-chip__meta">' + esc(meta.join(" · ")) + "</span>" +
            '<small class="lc-cat-chip__hint">El piso no reduce tu línea: solo alerta si apruebas por debajo.</small>' +
            "</div>"
        );
    }

    function confirmarSiBajoPiso(lineaNueva, piso, onOk) {
        var montoPiso = piso && piso.activo ? Number(piso.monto) : 0;
        if (!(montoPiso > 0) || !(Number(lineaNueva) + 0.01 < montoPiso)) {
            onOk();
            return;
        }
        var cat = piso.categoria_nombre ? piso.categoria_nombre : "la categoría";
        swal({
            title: "Línea bajo el piso de categoría",
            text: "La línea (" + fmtMoney(lineaNueva) + ") es menor al piso de " + cat +
                " (" + fmtMoney(montoPiso) + "). ¿Desea guardar igual?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, guardar",
            cancelButtonText: "Cancelar",
        }, function (ok) {
            if (ok) {
                onOk();
            }
        });
    }

    function swalRegistroOk(resp, fallbackMsg) {
        if (resp && resp.alerta_piso && resp.alerta_piso.bajo_piso && resp.alerta_piso.mensaje) {
            swal("Registrado con alerta", resp.alerta_piso.mensaje, "warning");
            return;
        }
        swal("Registrado", fallbackMsg, "success");
    }

    function renderRegistroLineaGrupo(data) {
        var g = data.grupo || {};
        var c = data.consolidado || {};
        var ref = lineaCreditoRefGrupo(c);
        var linea = lineaGrupoDisplay(c, ref);
        var piso = data.piso_categoria || null;
        var pisoTxt = textoPisoCategoria(piso);

        return (
            '<div class="lc-registro-linea lc-registro-linea--grupo lc-registro-linea--compact">' +
            '<form id="lcFormRegistroLineaGrupo" class="lc-form-inline-grupo">' +
            '<input type="hidden" name="codigo_grupo" value="' + esc(g.codigo) + '">' +
            '<div class="lc-form-inline-grupo__meta">' +
            '<i class="fa fa-pencil-square-o"></i>' +
            "<span>Vigente: <strong>" + linea.valor + "</strong> · " + esc(linea.nota) +
            (pisoTxt ? " · " + esc(pisoTxt) : "") +
            "</span></div>" +
            '<div class="lc-form-inline-grupo__fields">' +
            '<div class="lc-form-inline-grupo__field">' +
            '<label class="lc-field-lbl">Nueva línea</label>' +
            '<input type="text" inputmode="numeric" autocomplete="off" class="form-control input-sm lc-input-miles" ' +
            'placeholder="Ej. 200.000" required>' +
            '<input type="hidden" name="linea_aprobada" class="lc-miles-raw" value="">' +
            '<small class="lc-miles-preview">Escriba el monto; se formatea en miles automáticamente.</small></div>' +
            '<div class="lc-form-inline-grupo__field lc-form-inline-grupo__field--wide">' +
            '<label class="lc-field-lbl">Motivo</label>' +
            '<input type="text" class="form-control input-sm" name="motivo" placeholder="Motivo del cambio…" required></div>' +
            '<button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Guardar</button>' +
            "</div></form></div>"
        );
    }

    function renderMetricPill(label, value, cls) {
        return (
            '<div class="lc-grupo-metric' + (cls ? " lc-grupo-metric--" + cls : "") + '">' +
            "<span>" + esc(label) + "</span><strong>" + value + "</strong></div>"
        );
    }

    function renderGrupoHeroFin(c, refGrupo) {
        var deudaV = Number(c.deuda_vencida || 0);
        var deudaT = Number(c.deuda_actual || 0);
        var cupo = Number(c.cupo_disponible || 0);
        var refLinea = refGrupo.esAprobada ? Number(c.linea_aprobada || 0) : Number(c.linea_recomendada || 0);
        var vencCls = deudaV > 0 ? "lc-hero-box--danger" : "lc-hero-box--ok";
        var deudaCls = refLinea > 0 && deudaT > refLinea ? "lc-hero-box--warn" : "";
        var cupoCls = cupo <= 0 ? "lc-hero-box--danger" : "lc-hero-box--accent";
        var linea = lineaGrupoDisplay(c, refGrupo);

        return (
            '<div class="lc-grupo-hero">' +
            '<div class="lc-hero-box ' + vencCls + '">' +
            "<span><i class=\"fa fa-exclamation-circle\"></i> Deuda vencida</span>" +
            "<strong>" + fmtMoney(deudaV) + "</strong>" +
            "<small>" + (deudaV > 0 ? "Facturas vencidas pendientes" : "Sin vencidos al día de hoy") + "</small></div>" +
            '<div class="lc-hero-box lc-hero-box--deuda ' + deudaCls + '">' +
            "<span>Deuda total</span><strong>" + fmtMoney(deudaT) + "</strong></div>" +
            '<div class="lc-hero-box lc-hero-box--linea ' + linea.cls + '">' +
            "<span>" + esc(linea.titulo) + "</span><strong>" + linea.valor + "</strong>" +
            "<small>" + esc(linea.nota) + "</small></div>" +
            '<div class="lc-hero-box ' + cupoCls + '">' +
            "<span>Cupo disponible</span><strong>" + fmtMoney(cupo) + "</strong>" +
            "<small>" + (cupo > 0 ? "Disponible para pedidos" : "Sin margen de crédito") + "</small></div></div>"
        );
    }

    function renderUtilGrupo(c, refGrupo) {
        var nota = refGrupo.esAprobada
            ? "Deuda consolidada / línea aprobada del grupo"
            : "Deuda consolidada / referencia IC del grupo";

        return renderUtilBar(c.utilizacion_pct, {
            compact: true,
            label: "Utilización de línea del grupo",
            nota: nota,
        });
    }

    function renderPanelGrupo(data) {
        var g = data.grupo || {};
        var c = data.consolidado || {};
        var tot = data.totales_grupo || data.totales_cartera || {};
        var veredicto = buildVeredictoGrupo(data);
        var accClr = accionColor(c.accion_linea);
        var refGrupo = lineaCreditoRefGrupo(c);
        var vacioHtml = "";
        var histCount = (data.historial_grupo && data.historial_grupo.length) || 0;
        var fueraCartera = Number(tot.fuera_cartera || 0);

        if (!tot.clientes) {
            vacioHtml = '<div class="lc-grupo-empty lc-grupo-empty--compact">Este grupo no tiene locales activos.</div>';
        } else if (fueraCartera > 0) {
            vacioHtml =
                '<div class="lc-grupo-empty lc-grupo-empty--compact lc-grupo-empty--warn">' +
                fueraCartera + " local(es) fuera de cartera activa (incluidos en la deuda del grupo)." +
                "</div>";
        }

        var peorHtml = "";
        if (data.peor_ruc && data.peor_ruc.codigo) {
            var urlPeor = "index.php?ruta=inteligencia-comercial&cliente=" + encodeURIComponent(data.peor_ruc.codigo);
            peorHtml =
                '<div class="lc-grupo-alert lc-grupo-alert--compact">' +
                '<i class="fa fa-flag"></i>' +
                "<span><strong>Vigilar:</strong> " + esc(data.peor_ruc.codigo) +
                " · " + esc(data.peor_ruc.nombre || "") +
                ' <a href="' + urlPeor + '" target="_blank" rel="noopener noreferrer">IC <i class="fa fa-external-link"></i></a></span></div>';
        }

        return (
            '<div class="lc-grupo-card lc-grupo-card--compact">' +
            '<div class="lc-grupo-card__head lc-grupo-card__head--compact">' +
            "<div class=\"lc-grupo-card__title\">" +
            "<h4><i class=\"fa fa-sitemap\"></i> " + esc(g.nombre) + "</h4>" +
            "<small>" + esc(g.codigo) + " · " + (tot.clientes || 0) + " local(es)</small></div>" +
            '<div class="lc-grupo-card__actions">' +
            '<button type="button" class="btn btn-default btn-xs" id="btnLcActualizarGrupo" data-grupo="' + esc(g.codigo) + '" title="Recalcular deuda, scores y snapshot del grupo">' +
            '<i class="fa fa-refresh"></i> Actualizar</button>' +
            '<a href="' + esc(data.url_ic || "#") + '" class="btn btn-default btn-xs" target="_blank" rel="noopener noreferrer" title="Análisis en IC">' +
            '<i class="fa fa-line-chart"></i> IC</a>' +
            "</div></div>" +
            '<div class="lc-grupo-card__body lc-grupo-card__body--compact">' +
            '<div class="lc-veredicto lc-veredicto--inline lc-veredicto--' + veredicto.cls + '">' +
            '<i class="fa ' + veredicto.icon + '"></i>' +
            "<span><strong>" + esc(veredicto.titulo) + "</strong> — " + esc(veredicto.detalle) + "</span></div>" +
            renderCategoriaBadge(data.piso_categoria) +
            peorHtml +
            renderGrupoHeroFin(c, refGrupo) +
            renderUtilGrupo(c, refGrupo) +
            '<div class="lc-grupo-scores-row">' +
            renderMetricPill("Riesgo", c.score_riesgo != null ? Number(c.score_riesgo).toFixed(1) : "—", scoreColor(c.score_riesgo)) +
            renderMetricPill("Comercial", c.score_comercial != null ? Number(c.score_comercial).toFixed(1) : "—", scoreColor(c.score_comercial)) +
            renderMetricPill("Fidelidad", c.score_fidelidad != null ? Number(c.score_fidelidad).toFixed(1) : "—", scoreColor(c.score_fidelidad)) +
            renderMetricPill("Acción IC", esc(c.accion_linea || "—"), accClr) +
            renderMetricPill("Operativa", fmtMoney(c.linea_operativa)) +
            renderMetricPill("Locales", String(tot.clientes || 0)) +
            "</div>" +
            renderRegistroLineaGrupo(data) +
            '<div class="lc-grupo-historial-wrap">' +
            '<button type="button" class="lc-grupo-historial-toggle lc-grupo-historial-toggle--compact" data-toggle="collapse" data-target="#lcGrupoHistorial">' +
            '<span><i class="fa fa-history"></i> Historial' +
            (histCount ? ' <em>(' + histCount + ")</em>" : "") +
            "</span><span class=\"lc-grupo-historial-caret\"><i class=\"fa fa-chevron-down\"></i></span></button>" +
            '<div id="lcGrupoHistorial" class="collapse">' +
            renderHistorial(data.historial_grupo) +
            "</div></div>" +
            vacioHtml +
            "</div></div>"
        );
    }

    function fmtDeudaCell(deuda) {
        var n = Number(deuda || 0);
        return '<strong class="lc-deuda-val">' + fmtMoney(n) + "</strong>";
    }

    function fmtVencidaCell(vencida) {
        var n = Number(vencida || 0);
        if (n > 0) {
            return '<strong class="lc-vencida-val lc-vencida-val--alert">' + fmtMoney(n) + "</strong>";
        }
        return '<span class="text-muted">—</span>';
    }

    function badgeFueraCartera() {
        return '<span class="lc-fuera-cartera-tag" title="No está en cartera activa; su deuda sí cuenta en el grupo">' +
            '<i class="fa fa-eye-slash"></i> Fuera de cartera</span>';
    }

    function scoreBadgeHtml(score) {
        if (score === null || score === undefined || score === "") {
            return '<span class="text-muted">—</span>';
        }
        var sr = Number(score);
        var srCls = "default";
        if (sr >= 90) srCls = "success";
        else if (sr >= 80) srCls = "primary";
        else if (sr >= 70) srCls = "info";
        else if (sr >= 60) srCls = "warning";
        else srCls = "danger";
        return '<span class="lc-riesgo-badge lc-riesgo-badge--' + srCls + '">' + sr.toFixed(1) + "</span>";
    }

    function aplicarDeudaFila($row, miembro) {
        var deuda = Number(miembro.deuda_actual || 0);
        var vencida = Number(miembro.deuda_vencida || 0);
        $row.find("td.lc-col-deuda").attr("data-deuda", deuda).html(fmtDeudaCell(deuda));
        $row.find("td.lc-col-vencida").attr("data-vencida", vencida).html(fmtVencidaCell(vencida));

        var $nombre = $row.find("td:first .lc-nombre");
        $row.find(".lc-fuera-cartera-tag").remove();
        if (Number(miembro.fuera_cartera) === 1) {
            $row.addClass("lc-row--fuera-cartera");
            if ($nombre.length) {
                $nombre.after(badgeFueraCartera());
            } else {
                $row.find("td:first").append(badgeFueraCartera());
            }
        } else {
            $row.removeClass("lc-row--fuera-cartera");
        }
    }

    function filaLocalGrupoHtml(miembro, codigoGrupo, nombreGrupo) {
        var deuda = Number(miembro.deuda_actual || 0);
        var vencida = Number(miembro.deuda_vencida || 0);
        var fuera = Number(miembro.fuera_cartera) === 1;
        var badges = '<span class="lc-grupo-tag" title="Pertenece a grupo empresarial">' +
            '<i class="fa fa-sitemap"></i> ' + esc(nombreGrupo) + "</span>";
        if (fuera) {
            badges += badgeFueraCartera();
        }

        return (
            '<tr class="lc-row--grupo' + (fuera ? " lc-row--fuera-cartera" : "") + ' lc-row--inyectada" ' +
            'data-cliente="' + esc(miembro.codigo) + '" ' +
            'data-grupo="' + esc(codigoGrupo) + '" ' +
            'data-grupo-nombre="' + esc(nombreGrupo) + '" ' +
            'data-inyectada="1">' +
            "<td><strong class=\"lc-cod\">" + esc(miembro.codigo) + "</strong>" +
            '<div class="lc-nombre">' + esc(miembro.nombre) + "</div>" + badges + "</td>" +
            '<td class="lc-col-linea">' + fmtMoney(miembro.linea_operativa) + "</td>" +
            '<td class="lc-col-linea">' + fmtMoney(miembro.linea_recomendada) + "</td>" +
            '<td class="lc-col-aprobada"><button type="button" class="btn btn-xs btn-primary btnLcIrGrupo lc-btn-grupo-inline" ' +
            'data-grupo="' + esc(codigoGrupo) + '" data-nombre="' + esc(nombreGrupo) + '" title="La línea aprobada se gestiona a nivel de grupo">' +
            '<i class="fa fa-sitemap"></i> Grupo</button></td>' +
            '<td class="lc-col-deuda" data-deuda="' + deuda + '">' + fmtDeudaCell(deuda) + "</td>" +
            '<td class="lc-col-vencida" data-vencida="' + vencida + '">' + fmtVencidaCell(vencida) + "</td>" +
            '<td class="lc-col-pct"><span class="text-muted">—</span></td>' +
            '<td class="lc-col-cupo"><span class="text-muted lc-cupo-grupo-hint" title="El cupo se valida contra la línea del grupo">' +
            '<i class="fa fa-level-up"></i> Consolidado</span></td>' +
            '<td class="lc-col-riesgo">' + scoreBadgeHtml(miembro.score_riesgo) + "</td>" +
            '<td class="lc-col-fecha"><small>' + esc(miembro.fecha_actualizacion || "—") + "</small></td>" +
            '<td class="text-center lc-col-acciones">' +
            '<button type="button" class="btn btn-xs btn-default btnLcDetalle" data-cliente="' + esc(miembro.codigo) +
            '" data-nombre="' + esc(miembro.nombre) + '" title="Ver detalle del local">' +
            '<i class="fa fa-search"></i></button></td></tr>'
        );
    }

    function limpiarLocalesInyectados() {
        if (!tablaLc) {
            $("#tablaLineaCredito tbody tr.lc-row--inyectada").remove();
            return;
        }
        tablaLc.rows(function (idx, data, node) {
            return $(node).attr("data-inyectada") === "1";
        }).remove().draw(false);
    }

    function sincronizarLocalesGrupo(data) {
        var miembros = data.miembros || [];
        var g = data.grupo || {};
        var codigoGrupo = g.codigo || lcGrupoFiltro;
        var nombreGrupo = g.nombre || codigoGrupo;
        var presentes = {};

        $("#tablaLineaCredito tbody tr").each(function () {
            var $row = $(this);
            var codigo = String($row.data("cliente") || "");
            if (!codigo) {
                return;
            }
            presentes[codigo] = $row;
        });

        miembros.forEach(function (m) {
            var codigo = String(m.codigo || "");
            if (!codigo) {
                return;
            }
            if (presentes[codigo]) {
                aplicarDeudaFila(presentes[codigo], m);
            } else if (tablaLc) {
                tablaLc.row.add($(filaLocalGrupoHtml(m, codigoGrupo, nombreGrupo))).draw(false);
            } else {
                $("#tablaLineaCredito tbody").append(filaLocalGrupoHtml(m, codigoGrupo, nombreGrupo));
            }
        });
    }

    function deudaTotalDesdeDetalleGrupo(resp) {
        if (resp.consolidado && resp.consolidado.deuda_actual != null) {
            return parseFloat(resp.consolidado.deuda_actual) || 0;
        }
        var tot = resp.totales_grupo || resp.totales_cartera || {};
        return tot.deuda ? parseFloat(tot.deuda) : 0;
    }

    function limpiarFiltroGrupo() {
        seleccionarGrupo("", { scroll: false });
    }

    function seleccionarGrupo(codigoGrupo, opts) {
        var $sel = $("#lcFiltroGrupo");
        if (!$sel.length) {
            return;
        }

        $sel.val(codigoGrupo || "");
        if ($.fn.selectpicker) {
            $sel.selectpicker("refresh");
        }

        aplicarFiltroGrupo(opts);

        if (opts && opts.scroll !== false) {
            var $dest = $("#lcMainGrid");
            if ($dest.length && $dest.hasClass("lc-main-grid--split")) {
                $("html, body").animate({ scrollTop: $dest.offset().top - 70 }, 350);
            }
        }
    }

    function actualizarPctDeudaLocales() {
        var enGrupo = lcGrupoFiltro && lcGrupoFiltro !== "__sin_grupo__";
        $("#tablaLineaCredito tbody tr").each(function () {
            var $row = $(this);
            var $pct = $row.find("td.lc-col-pct");

            if (!$pct.length) {
                return;
            }

            if (!enGrupo || !lcGrupoDeudaTotal) {
                $pct.html('<span class="text-muted">—</span>');
                return;
            }

            if ($row.css("display") === "none") {
                return;
            }

            var deuda = parseFloat($row.find("td.lc-col-deuda").data("deuda") || 0);
            var pctVal = lcGrupoDeudaTotal > 0 ? (deuda / lcGrupoDeudaTotal) * 100 : 0;
            var pctCls = pctVal >= 40 ? "lc-pct-alto" : pctVal >= 20 ? "lc-pct-medio" : "";

            $pct.html('<span class="lc-pct-val ' + pctCls + '">' + pctVal.toFixed(1) + "%</span>");
        });
    }

    function actualizarColumnasTabla(modo) {
        if (!tablaLc) {
            return;
        }

        var visibles = {
            todos: [true, true, true, true, true, true, false, true, true, true, true],
            grupo: [true, false, true, false, true, true, true, false, true, false, true],
            "sin-grupo": [true, true, true, true, true, true, false, true, true, true, true],
        };
        var cols = visibles[modo] || visibles.todos;

        cols.forEach(function (visible, idx) {
            tablaLc.column(idx).visible(visible, false);
        });

        tablaLc.columns.adjust().draw(false);
        actualizarPctDeudaLocales();
    }

    function actualizarLayoutGrupo() {
        var split = lcGrupoFiltro && lcGrupoFiltro !== "__sin_grupo__";
        var $grid = $("#lcMainGrid");
        var $colPanel = $("#lcColPanel");
        var $colTabla = $("#lcColTabla");

        $grid.toggleClass("lc-main-grid--split", split);

        if (split) {
            $colPanel.removeClass("hidden").addClass("col-md-6");
            $colTabla.removeClass("col-md-12").addClass("col-md-6");
        } else {
            $colPanel.removeClass("col-md-6").addClass("hidden");
            $colTabla.removeClass("col-md-6").addClass("col-md-12");
        }

        if (tablaLc) {
            window.setTimeout(function () {
                tablaLc.columns.adjust().draw(false);
            }, 80);
        }
    }

    function actualizarModoVista() {
        var val = lcGrupoFiltro;
        var $page = $("#lcPage");
        var $badge = $("#lcModoBadge");
        var $ayuda = $("#lcFiltroAyuda");
        var $seccion = $("#lcTablaSeccion");
        var modo = "todos";

        $page.removeClass("lc-page--modo-todos lc-page--modo-grupo lc-page--modo-sin-grupo");

        if (val && val !== "__sin_grupo__") {
            modo = "grupo";
            $page.addClass("lc-page--modo-grupo");
            $badge.html('<i class="fa fa-sitemap"></i> Vista por grupo');
            $ayuda.html(
                "Panel <strong>izquierdo</strong>: línea del grupo · Tabla <strong>derecha</strong>: locales con deuda y riesgo."
            );
            $seccion.addClass("lc-tabla-seccion--secundaria lc-tabla--grupo");
        } else if (val === "__sin_grupo__") {
            modo = "sin-grupo";
            $page.addClass("lc-page--modo-sin-grupo");
            $badge.html('<i class="fa fa-user-o"></i> Sin grupo');
            $ayuda.html("Clientes independientes: la línea aprobada se registra por local.");
            $seccion.removeClass("lc-tabla-seccion--secundaria lc-tabla--grupo");
            lcGrupoDeudaTotal = 0;
        } else {
            $page.addClass("lc-page--modo-todos");
            $badge.html('<i class="fa fa-globe"></i> Vista general');
            $ayuda.html(
                "Elija un grupo para gestionar la línea aprobada consolidada. " +
                "Los locales en grupo muestran cupo consolidado."
            );
            $seccion.removeClass("lc-tabla-seccion--secundaria lc-tabla--grupo");
            lcGrupoDeudaTotal = 0;
        }

        actualizarLayoutGrupo();
        actualizarColumnasTabla(modo);
        $("#btnLcLimpiarFiltro").toggleClass("hidden", val === "");
    }

    function actualizarContadorTabla() {
        if (!tablaLc) {
            return;
        }
        var info = tablaLc.page.info();
        $("#lcTablaContador").text(info.recordsDisplay + " registro(s)");
    }

    function actualizarTituloTabla() {
        var $sel = $("#lcFiltroGrupo");
        var val = $sel.val() || "";
        var titulo = '<i class="fa fa-list"></i> Clientes en cartera';

        if (val && val !== "__sin_grupo__") {
            var nombre = $sel.find("option:selected").data("nombre") || $sel.find("option:selected").text();
            titulo = '<i class="fa fa-building-o"></i> Locales del grupo — ' + esc(nombre);
        } else if (val === "__sin_grupo__") {
            titulo = '<i class="fa fa-user-o"></i> Clientes sin grupo empresarial';
        }

        $("#lcTablaTitulo").html(titulo);
    }

    function ocultarPanelGrupo() {
        limpiarLocalesInyectados();
        $("#lcPanelGrupo").hide().empty();
        lcGrupoDeudaTotal = 0;
        actualizarPctDeudaLocales();
    }

    function cargarPanelGrupo(codigoGrupo) {
        if (!codigoGrupo || codigoGrupo === "__sin_grupo__") {
            ocultarPanelGrupo();
            return;
        }

        $("#lcPanelGrupo").show().html(
            '<div class="lc-grupo-loading"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Calculando análisis consolidado…</p></div>'
        );

        limpiarLocalesInyectados();

        postLc("detalle_grupo", { codigo_grupo: codigoGrupo }, { timeout: 120000 })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    $("#lcPanelGrupo").html(
                        '<div class="alert alert-warning" style="margin:0">' + esc((resp && resp.msg) || "No se pudo cargar el grupo.") + "</div>"
                    );
                    lcGrupoDeudaTotal = 0;
                    actualizarPctDeudaLocales();
                    return;
                }
                sincronizarLocalesGrupo(resp);
                lcGrupoDeudaTotal = deudaTotalDesdeDetalleGrupo(resp);
                lcGrupoPiso = resp.piso_categoria || null;
                $("#lcPanelGrupo").html(renderPanelGrupo(resp));
                actualizarLayoutGrupo();
                actualizarPctDeudaLocales();
                if (tablaLc) {
                    tablaLc.columns.adjust().draw(false);
                }
            })
            .fail(function (xhr) {
                $("#lcPanelGrupo").html(
                    '<div class="alert alert-danger" style="margin:0">' + esc(msgAjaxError(xhr, "Error al cargar análisis del grupo.")) + "</div>"
                );
                actualizarLayoutGrupo();
            });
    }

    function aplicarFiltroGrupo(opts) {
        var val = $("#lcFiltroGrupo").val() || "";
        lcGrupoFiltro = val;
        actualizarTituloTabla();
        actualizarModoVista();

        if (val && val !== "__sin_grupo__") {
            cargarPanelGrupo(val);
        } else {
            ocultarPanelGrupo();
        }

        if (tablaLc) {
            tablaLc.draw();
            actualizarContadorTabla();
        }

        if (!opts || !opts.fromUrl) {
            // Al cambiar filtro se limpia el cliente de la URL (salvo restauración inicial).
            if (!opts || opts.clearCliente !== false) {
                if (!$("#modalLcDetalle").hasClass("in") && !$("#modalLcDetalle").hasClass("show")) {
                    lcContext.cliente = "";
                    lcContext.nombre = "";
                }
            }
            lcActualizarUrl({
                grupo: val,
                cliente: (opts && opts.keepCliente) ? lcContext.cliente : (
                    ($("#modalLcDetalle").hasClass("in") || $("#modalLcDetalle").hasClass("show"))
                        ? lcContext.cliente
                        : ""
                ),
            });
        }
    }

    function abrirDetalleCliente(codigo, nombre) {
        codigo = String(codigo || "").trim();
        if (!codigo) {
            return;
        }

        if (!nombre) {
            var $row = $("#tablaLineaCredito tbody tr").filter(function () {
                return String($(this).data("cliente") || "") === codigo;
            }).first();
            if ($row.length) {
                var $btn = $row.find(".btnLcDetalle");
                nombre = ($btn.data("nombre") || $row.find(".lc-nombre").first().text() || "").trim();
            }
        }

        lcContext.cliente = codigo;
        lcContext.nombre = nombre || codigo;
        $("#lcDetalleTitulo").html(
            '<i class="fa fa-user"></i> ' + esc(lcContext.nombre) +
            '<span class="lc-modal-cod">' + esc(lcContext.cliente) + "</span>"
        );
        lcActualizarUrl({ cliente: codigo });
        $("#modalLcDetalle").modal("show");
        cargarDetalle().done(function (resp) {
            if (resp && resp.ok && resp.cliente) {
                if (resp.cliente.nombre) {
                    lcContext.nombre = resp.cliente.nombre;
                    $("#lcDetalleTitulo").html(
                        '<i class="fa fa-user"></i> ' + esc(lcContext.nombre) +
                        '<span class="lc-modal-cod">' + esc(lcContext.cliente) + "</span>"
                    );
                }
            }
            mostrarDetalle(resp);
        });
    }

    function restaurarDesdeUrl() {
        var params = lcLeerParamsUrl();
        lcUrlSyncLock = true;

        if (params.grupo) {
            var $sel = $("#lcFiltroGrupo");
            var grupoOk = false;
            if ($sel.length) {
                $sel.find("option").each(function () {
                    if (String($(this).val()) === params.grupo) {
                        grupoOk = true;
                        return false;
                    }
                });
            }
            if (grupoOk) {
                $sel.val(params.grupo);
                if ($.fn.selectpicker) {
                    $sel.selectpicker("refresh");
                }
                aplicarFiltroGrupo({ fromUrl: true, clearCliente: false, keepCliente: true });
            }
        }

        lcUrlSyncLock = false;
        lcActualizarUrl({
            grupo: lcGrupoFiltro,
            cliente: params.cliente || "",
        });

        if (params.cliente) {
            window.setTimeout(function () {
                abrirDetalleCliente(params.cliente);
            }, 250);
        }
    }

    $(window).on("resize.lcGrid", function () {
        if (tablaLc && lcGrupoFiltro && lcGrupoFiltro !== "__sin_grupo__") {
            tablaLc.columns.adjust();
        }
    });

    function renderHistorial(items) {
        if (!items || !items.length) {
            return '<p class="text-muted" style="margin:0">Sin historial registrado.</p>';
        }
        var html = '<ul class="lc-timeline">';
        items.forEach(function (h) {
            var tipo = String(h.tipo_evento || "");
            var tlCls = "lc-tl--default";
            var tipoLabel = tipo.replace(/_/g, " ");
            var det = parseDetalleHistorial(h.detalle);
            var motivoHtml = "";

            if (tipo === "CIERRE_MENSUAL") tlCls = "lc-tl--cierre";
            else if (tipo === "LINEA_APROBADA" || tipo === "LINEA_ACTUALIZADA") tlCls = "lc-tl--aprobada";
            else if (tipo === "LINEA_RECHAZADA") tlCls = "lc-tl--rechazada";
            else if (tipo === "ACTUALIZACION_INDIVIDUAL" || tipo === "ACTUALIZACION_GRUPO") tlCls = "lc-tl--actualizacion";

            if (det && det.motivo) {
                motivoHtml = '<br><em class="lc-tl-motivo">Motivo: ' + esc(det.motivo) + "</em>";
                if (det.linea_anterior != null && det.linea_nueva != null) {
                    motivoHtml += " · " + fmtMoney(det.linea_anterior) + " → " + fmtMoney(det.linea_nueva);
                }
            }

            html +=
                '<li class="' + tlCls + '">' +
                '<span class="lc-tl-tipo">' + esc(tipoLabel) + "</span> " +
                esc(h.anio) + "/" + esc(h.mes) +
                " · Línea " + fmtMoney(h.linea_aprobada || h.linea_recomendada) +
                " · Cupo " + fmtMoney(h.cupo_disponible) +
                motivoHtml +
                "<br><small class=\"text-muted\">" +
                esc(h.usuario_nombre || "") + " · " + esc(h.fecha || "") +
                "</small></li>";
        });
        return html + "</ul>";
    }

    function renderRegistroLinea(c, piso) {
        var ref = lineaCreditoRef(c);
        var lineaVigente = ref.esAprobada ? fmtMoney(c.linea_aprobada) : "Sin línea aprobada";
        var pisoTxt = textoPisoCategoria(piso);

        return (
            '<div class="lc-registro-linea">' +
            "<h5><i class=\"fa fa-pencil-square-o\"></i> Registrar línea aprobada</h5>" +
            '<p class="lc-registro-ayuda">Línea vigente: <strong>' + lineaVigente + "</strong>" +
            (ref.esAprobada ? "" : " · Referencia IC: " + fmtMoney(c.linea_recomendada)) +
            (pisoTxt ? "<br>" + esc(pisoTxt) : "") +
            "</p>" +
            '<form id="lcFormRegistroLinea">' +
            '<input type="hidden" name="codigo_cliente" value="' + esc(lcContext.cliente) + '">' +
            '<div class="row">' +
            '<div class="col-sm-4"><label class="lc-field-lbl">Nueva línea</label>' +
            '<input type="text" inputmode="numeric" autocomplete="off" class="form-control input-sm lc-input-miles" ' +
            'placeholder="Ej. 20.000" required>' +
            '<input type="hidden" name="linea_aprobada" class="lc-miles-raw" value="">' +
            '<small class="lc-miles-preview">Escriba el monto; se formatea en miles automáticamente.</small></div>' +
            '<div class="col-sm-8"><label class="lc-field-lbl">Motivo del cambio</label>' +
            '<input type="text" class="form-control input-sm" name="motivo" placeholder="Ej. Buen historial de pago, incremento por volumen…" required></div>' +
            "</div>" +
            '<button type="submit" class="btn btn-success btn-sm" style="margin-top:10px"><i class="fa fa-check"></i> Guardar línea aprobada</button>' +
            "</form></div>"
        );
    }

    function renderBannerGrupo(data) {
        var g = data.grupo || {};
        var gr = data.grupo_resumen || {};
        var codigoGrupo = g.codigo || (data.cliente && data.cliente.grupo) || "";
        var nombreGrupo = g.nombre || codigoGrupo;
        var cupoGrupo = gr.cupo_disponible != null ? fmtMoney(gr.cupo_disponible) : "—";
        var lineaGrupo = (gr.linea_aprobada != null && Number(gr.linea_aprobada) > 0)
            ? fmtMoney(gr.linea_aprobada)
            : (gr.linea_recomendada > 0 ? fmtMoney(gr.linea_recomendada) + " (IC)" : "Pendiente");

        return (
            '<div class="lc-grupo-banner">' +
            '<div class="lc-grupo-banner__icon"><i class="fa fa-sitemap"></i></div>' +
            '<div class="lc-grupo-banner__body">' +
            "<strong>Local del grupo " + esc(nombreGrupo) + "</strong>" +
            "<p>La línea aprobada y el cupo para pedidos se validan a nivel de grupo, no por local.</p>" +
            '<div class="lc-grupo-banner__kpis">' +
            '<span><em>Línea ref.</em> ' + lineaGrupo + "</span>" +
            '<span><em>Cupo grupo</em> ' + cupoGrupo + "</span>" +
            "</div></div>" +
            '<button type="button" class="btn btn-primary btn-sm btnLcIrGrupoModal" data-grupo="' + esc(codigoGrupo) + '" data-nombre="' + esc(nombreGrupo) + '">' +
            '<i class="fa fa-arrow-up"></i> Gestionar grupo</button>' +
            "</div>"
        );
    }

    function renderDetalleLocalGrupo(data) {
        var c = data.cliente || {};
        var riesgoClr = scoreColor(c.score_riesgo);
        var comClr = scoreColor(c.score_comercial);
        var fidClr = scoreColor(c.score_fidelidad);

        return (
            '<div class="lc-detalle lc-detalle--local-grupo">' +
            renderBannerGrupo(data) +
            renderCategoriaBadge(data.piso_categoria) +
            '<div class="lc-section-title"><i class="fa fa-map-marker"></i> Situación de este local</div>' +
            '<div class="row lc-scores lc-scores--compact">' +
            '<div class="col-sm-4 col-xs-6"><div class="lc-score-card lc-score-card--' + riesgoClr + '">' +
            '<span class="lc-score-num">' + (c.score_riesgo != null ? Number(c.score_riesgo).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Riesgo local</span></div></div>' +
            '<div class="col-sm-4 col-xs-6"><div class="lc-score-card lc-score-card--' + comClr + '">' +
            '<span class="lc-score-num">' + (c.score_comercial != null ? Number(c.score_comercial).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Comercial</span></div></div>' +
            '<div class="col-sm-4 col-xs-6"><div class="lc-score-card lc-score-card--' + fidClr + '">' +
            '<span class="lc-score-num">' + (c.score_fidelidad != null ? Number(c.score_fidelidad).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Fidelidad</span></div></div>' +
            "</div>" +
            '<div class="lc-local-deuda-box">' +
            "<span>Deuda de este local</span><strong>" + fmtMoney(c.deuda_actual) + "</strong>" +
            "<small>Contribuye al total consolidado del grupo</small></div>" +
            '<div class="lc-toolbar-actions">' +
            '<button type="button" class="btn btn-default btn-sm" id="btnLcActualizarCliente"><i class="fa fa-refresh"></i> Actualizar desde IC</button>' +
            "</div>" +
            '<div class="lc-section-title lc-section-title--muted"><i class="fa fa-history"></i> Historial del local <small>(solo consulta)</small></div>' +
            renderHistorial(data.historial) +
            "</div>"
        );
    }

    function renderDetalle(data) {
        if (data.pertenece_grupo) {
            return renderDetalleLocalGrupo(data);
        }

        var c = data.cliente || {};
        var veredicto = buildVeredicto(c);
        var riesgoClr = scoreColor(c.score_riesgo);
        var comClr = scoreColor(c.score_comercial);
        var fidClr = scoreColor(c.score_fidelidad);
        var accClr = accionColor(c.accion_linea);
        var ref = lineaCreditoRef(c);
        var lineaClr = ref.esAprobada ? "success" : "highlight";
        var cupoClr = cupoColor(c.cupo_disponible, c.deuda_actual, ref.valor);

        return (
            '<div class="lc-detalle">' +
            '<div class="lc-veredicto lc-veredicto--' + veredicto.cls + '">' +
            '<i class="fa ' + veredicto.icon + '"></i>' +
            "<div><strong>" + esc(veredicto.titulo) + "</strong><span>" + esc(veredicto.detalle) + "</span></div>" +
            "</div>" +
            renderCategoriaBadge(data.piso_categoria) +
            '<div class="row lc-scores">' +
            '<div class="col-sm-3 col-xs-6"><div class="lc-score-card lc-score-card--' + riesgoClr + '">' +
            '<span class="lc-score-num">' + (c.score_riesgo != null ? Number(c.score_riesgo).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Riesgo</span><small>' + esc(scoreEtiqueta(c.score_riesgo)) + "</small></div></div>" +
            '<div class="col-sm-3 col-xs-6"><div class="lc-score-card lc-score-card--' + comClr + '">' +
            '<span class="lc-score-num">' + (c.score_comercial != null ? Number(c.score_comercial).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Comercial</span><small>Actividad de compra</small></div></div>' +
            '<div class="col-sm-3 col-xs-6"><div class="lc-score-card lc-score-card--' + fidClr + '">' +
            '<span class="lc-score-num">' + (c.score_fidelidad != null ? Number(c.score_fidelidad).toFixed(1) : "—") + "</span>" +
            '<span class="lc-score-lbl">Fidelidad</span><small>Relación comercial</small></div></div>' +
            '<div class="col-sm-3 col-xs-6"><div class="lc-score-card lc-score-card--' + accClr + ' lc-score-card--action">' +
            '<span class="lc-score-lbl">Acción IC</span>' +
            '<span class="lc-score-action-text">' + esc(c.accion_linea || "—") + "</span></div></div>" +
            "</div>" +
            (c.accion_linea
                ? '<div class="lc-callout-accion lc-callout-accion--' + accClr + '"><p><i class="fa fa-lightbulb-o"></i> ' +
                esc(c.accion_linea) + " — use la línea recomendada como referencia para la decisión.</p></div>"
                : "") +
            '<div class="lc-section-title"><i class="fa fa-exchange"></i> Referencia operativa</div>' +
            '<div class="lc-lineas-compare">' +
            '<div class="lc-linea-chip"><span>Operativa</span><strong>' + fmtMoney(c.linea_operativa) + "</strong></div>" +
            '<div class="lc-linea-chip lc-linea-chip--rec"><span>Recomendada IC</span><strong>' + fmtMoney(c.linea_recomendada) + "</strong></div>" +
            (ref.esAprobada
                ? '<div class="lc-linea-chip lc-linea-chip--apr"><span>Aprobada</span><strong>' + fmtMoney(c.linea_aprobada) + "</strong></div>"
                : '<div class="lc-linea-chip lc-linea-chip--vac"><span>Aprobada</span><strong>—</strong><small>Pendiente de registro</small></div>') +
            "</div>" +
            '<div class="lc-section-title"><i class="fa fa-calculator"></i> Cupo de crédito</div>' +
            '<div class="lc-fin-trio">' +
            '<div class="lc-fin-box lc-fin-box--deuda lc-metric--neutral">' +
            "<span>Deuda actual</span><strong>" + fmtMoney(c.deuda_actual) + "</strong></div>" +
            '<div class="lc-fin-op" title="Menos deuda">−</div>' +
            '<div class="lc-fin-box lc-fin-box--linea lc-metric--' + lineaClr + '">' +
            "<span>Línea de crédito</span><strong>" + fmtMoney(ref.valor) + "</strong>" +
            "<small>" + esc(ref.etiqueta) + "</small></div>" +
            '<div class="lc-fin-op" title="Igual a cupo">=</div>' +
            '<div class="lc-fin-box lc-fin-box--cupo lc-metric--' + cupoClr + '">' +
            "<span>Cupo disponible</span><strong>" + fmtMoney(c.cupo_disponible) + "</strong>" +
            (Number(c.cupo_disponible) > 0
                ? "<small>Disponible para nuevo pedido</small>"
                : "<small>Sin margen de crédito</small>") +
            "</div></div>" +
            renderUtilBar(c.utilizacion_pct) +
            '<div class="lc-toolbar-actions">' +
            '<button type="button" class="btn btn-default btn-sm" id="btnLcActualizarCliente"><i class="fa fa-refresh"></i> Actualizar desde IC</button>' +
            "</div>" +
            '<div class="lc-section-title"><i class="fa fa-history"></i> Historial</div>' + renderHistorial(data.historial) +
            renderRegistroLinea(c, data.piso_categoria) +
            "</div>"
        );
    }

    function cargarDetalle() {
        $("#lcDetalleBody").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
        return postLc("detalle", { codigo_cliente: lcContext.cliente });
    }

    function mostrarDetalle(resp) {
        if (!resp || !resp.ok) {
            $("#lcDetalleBody").html('<div class="alert alert-warning">' + esc((resp && resp.msg) || "Error") + "</div>");
            return;
        }
        lcContext.pisoCategoria = resp.piso_categoria || null;
        $("#lcDetalleBody").html(renderDetalle(resp));
        $("#lcLinkIc").attr("href", resp.url_ic || "#");
    }

    $.fn.dataTable.ext.search.push(function (settings, _data, dataIndex) {
        if (!settings.nTable || settings.nTable.id !== "tablaLineaCredito") {
            return true;
        }
        if (!lcGrupoFiltro) {
            return true;
        }

        var row = settings.aoData[dataIndex].nTr;
        var grupo = $(row).attr("data-grupo") || "";

        if (lcGrupoFiltro === "__sin_grupo__") {
            return grupo === "";
        }

        return grupo === lcGrupoFiltro;
    });

    $(document).ready(function () {
        if ($.fn.DataTable && $("#tablaLineaCredito").length) {
            tablaLc = $("#tablaLineaCredito").DataTable({
                language: { url: "vistas/js/spanish.json" },
                pageLength: 25,
                order: [[0, "asc"]],
                autoWidth: false,
                drawCallback: function () {
                    actualizarContadorTabla();
                    actualizarPctDeudaLocales();
                },
            });
            actualizarModoVista();
            actualizarContadorTabla();
        }

        if ($.fn.selectpicker && $("#lcFiltroGrupo").length) {
            $("#lcFiltroGrupo").selectpicker();
        }

        restaurarDesdeUrl();
    });

    $(document).on("click", ".btnLcIrGrupo, .btnLcIrGrupoModal", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var codigo = $(this).data("grupo");
        if (!codigo) {
            return;
        }
        lcContext.cliente = "";
        lcContext.nombre = "";
        $("#modalLcDetalle").modal("hide");
        seleccionarGrupo(codigo, { scroll: true });
    });

    $(document).on("changed.bs.select", "#lcFiltroGrupo", aplicarFiltroGrupo);

    $("#btnLcLimpiarFiltro").on("click", function () {
        limpiarFiltroGrupo();
    });

    $(document).on("click", ".btnLcDetalle", function () {
        abrirDetalleCliente($(this).data("cliente"), $(this).data("nombre"));
    });

    $("#modalLcDetalle").on("hidden.bs.modal", function () {
        lcContext.cliente = "";
        lcContext.nombre = "";
        lcContext.pisoCategoria = null;
        lcActualizarUrl({ cliente: "" });
    });

    $(document).on("click", "#btnLcActualizarCliente", function () {
        var $btn = $(this);
        $btn.prop("disabled", true);
        postLc("actualizar_cliente", { codigo_cliente: lcContext.cliente })
            .done(function (resp) {
                mostrarDetalle(resp);
                swal("Actualizado", "Datos recalculados desde Inteligencia Comercial.", "success");
            })
            .fail(function () { swal("Error", "No se pudo actualizar.", "error"); })
            .always(function () { $btn.prop("disabled", false); });
    });

    $(document).on("click", "#btnLcActualizarGrupo", function () {
        var $btn = $(this);
        var codigoGrupo = $btn.data("grupo") || lcGrupoFiltro;

        if (!codigoGrupo || codigoGrupo === "__sin_grupo__") {
            return;
        }

        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Actualizando…');
        postLc("actualizar_grupo", { codigo_grupo: codigoGrupo }, { timeout: 120000 })
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    swal("Atención", (resp && resp.msg) || "No se pudo actualizar el grupo.", "warning");
                    return;
                }
                sincronizarLocalesGrupo(resp);
                lcGrupoDeudaTotal = deudaTotalDesdeDetalleGrupo(resp);
                lcGrupoPiso = resp.piso_categoria || lcGrupoPiso;
                $("#lcPanelGrupo").html(renderPanelGrupo(resp));
                actualizarLayoutGrupo();
                actualizarPctDeudaLocales();
                if (tablaLc) {
                    tablaLc.columns.adjust().draw(false);
                }
                swal("Actualizado", "Grupo recalculado desde Inteligencia Comercial.", "success");
            })
            .fail(function (xhr) {
                swal("Error", msgAjaxError(xhr, "No se pudo actualizar el grupo."), "error");
            })
            .always(function () {
                var $nuevo = $("#btnLcActualizarGrupo");
                if ($nuevo.length) {
                    $nuevo.prop("disabled", false).html('<i class="fa fa-refresh"></i> Actualizar');
                }
            });
    });

    $("#btnLcCierreMensual").on("click", function () {
        swal({
            title: "¿Ejecutar cierre mensual?",
            text: "Se procesarán clientes con compra/pedido en los últimos 24 meses, excluyendo canales de contado (08, showroom, digital, etc.).",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Ejecutar",
            cancelButtonText: "Cancelar",
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            var $btn = $("#btnLcCierreMensual");
            var labelOriginal = $btn.html();
            var totales = { procesados: 0, errores: 0 };

            function ejecutarLote() {
                return postLc("cierre_mensual_lote", {}, { timeout: 300000 }).then(function (resp) {
                    if (!resp || !resp.ok) {
                        return $.Deferred().reject(resp).promise();
                    }

                    totales.procesados += resp.procesados_lote || 0;
                    totales.errores += resp.errores_lote || 0;

                    if (!resp.terminado && (resp.restantes || 0) > 0) {
                        if ((resp.procesados_lote || 0) + (resp.errores_lote || 0) === 0) {
                            return $.Deferred().reject({
                                msg: "El cierre no avanzó. Verifique que las tablas SQL estén creadas.",
                            }).promise();
                        }
                        $btn.html(
                            '<i class="fa fa-spinner fa-spin"></i> Procesando… (' +
                            resp.restantes + " pendientes)"
                        );
                        return ejecutarLote();
                    }

                    return resp;
                });
            }

            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Iniciando cierre…');

            ejecutarLote()
                .done(function () {
                    swal(
                        "Cierre completado",
                        "Procesados: " + totales.procesados + " · Errores: " + totales.errores,
                        "success"
                    );
                    setTimeout(function () { location.reload(); }, 1200);
                })
                .fail(function (xhr) {
                    var msg = (xhr && xhr.msg) || msgAjaxError(xhr, "No se pudo ejecutar el cierre.");
                    swal("Error", msg, "error");
                })
                .always(function () {
                    $btn.prop("disabled", false).html(labelOriginal);
                });
        });
    });

    $(document).on("input blur", ".lc-input-miles", function () {
        sincronizarInputMiles($(this));
    });

    $(document).on("submit", "#lcFormRegistroLineaGrupo", function (e) {
        e.preventDefault();
        var $form = $(this);
        var $miles = $form.find(".lc-input-miles");
        var lineaNueva = sincronizarInputMiles($miles);

        if (lineaNueva < 1000) {
            swal("Atención", "Ingrese una línea de al menos S/ 1.000.", "warning");
            $miles.focus();
            return;
        }

        confirmarSiBajoPiso(lineaNueva, lcGrupoPiso, function () {
            postLc("registrar_linea_grupo", $form.serialize())
                .done(function (resp) {
                    if (!resp || !resp.ok) {
                        swal("Atención", (resp && resp.msg) || "Error", "warning");
                        return;
                    }
                    lcGrupoPiso = resp.piso_categoria || lcGrupoPiso;
                    limpiarLocalesInyectados();
                    sincronizarLocalesGrupo(resp);
                    $("#lcPanelGrupo").html(renderPanelGrupo(resp));
                    lcGrupoDeudaTotal = deudaTotalDesdeDetalleGrupo(resp);
                    actualizarLayoutGrupo();
                    actualizarPctDeudaLocales();
                    swalRegistroOk(resp, "Línea aprobada del grupo guardada en historial.");
                })
                .fail(function (xhr) {
                    swal("Error", msgAjaxError(xhr, "No se pudo registrar la línea del grupo."), "error");
                });
        });
    });

    $(document).on("submit", "#lcFormRegistroLinea", function (e) {
        e.preventDefault();
        var $form = $(this);
        var $miles = $form.find(".lc-input-miles");
        var lineaNueva = sincronizarInputMiles($miles);

        if (lineaNueva < 1000) {
            swal("Atención", "Ingrese una línea de al menos S/ 1.000.", "warning");
            $miles.focus();
            return;
        }

        confirmarSiBajoPiso(lineaNueva, lcContext.pisoCategoria, function () {
            postLc("registrar_linea", $form.serialize())
                .done(function (resp) {
                    if (!resp || !resp.ok) {
                        swal("Atención", (resp && resp.msg) || "Error", "warning");
                        return;
                    }
                    mostrarDetalle(resp);
                    swalRegistroOk(resp, "Línea aprobada guardada en historial.");
                })
                .fail(function (xhr) {
                    swal("Error", msgAjaxError(xhr, "No se pudo registrar la línea."), "error");
                });
        });
    });
})();
