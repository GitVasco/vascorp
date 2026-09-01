(function (w, $) {
    'use strict';

    var boot = w.Rgv2Boot;
    var lookups = w.Rgv2Lookups || {};
    if (!boot || !$('#rgv2Page').length) {
        return;
    }

    var state = {
        group: boot.groups[0] ? boot.groups[0].id : '',
        templateId: '',
        search: '',
        previewOk: false,
        fetchSeq: 0,
    };

    var urlSyncLock = false;
    var pendingUrlFilters = null;
    var ROUTE_SLUG = 'reportes-generales-v2';
    var FILTER_URL_KEYS = ['tip_doc', 'cli', 'vend', 'banco', 'inicio', 'fin', 'canc'];

    var $catalog = $('#rgv2CatalogList');
    var $empty = $('#rgv2Empty');
    var $panel = $('#rgv2Panel');

    function tpl(id) {
        return boot.templates.find(function (t) {
            return t.id === id;
        }) || null;
    }

    function groupLabel(id) {
        var g = boot.groups.find(function (x) {
            return x.id === id;
        });
        return g ? g.label : id;
    }

    function estadoLabel(estado) {
        if (estado === 'listo') return 'Listo';
        if (estado === 'fuera_alcance') return 'Fuera de alcance';
        return 'Pendiente';
    }

    function estadoClass(estado) {
        if (estado === 'listo') return 'label-success';
        if (estado === 'fuera_alcance') return 'label-default';
        return 'label-warning';
    }

    function filteredTemplates() {
        var q = state.search.toLowerCase();
        return boot.templates.filter(function (t) {
            if (t.group !== state.group) return false;
            if (!q) return true;
            return (t.title + ' ' + t.hint + ' ' + t.id).toLowerCase().indexOf(q) !== -1;
        });
    }

    function groupShortLabel(id) {
        var map = {
            movimientos_saldos: 'Movimientos'
        };
        return map[id] || null;
    }

    function renderGroupToggle() {
        var html = '<ul class="nav nav-pills nav-justified rgv2-group-pills">';
        boot.groups.forEach(function (g) {
            var active = g.id === state.group ? ' active' : '';
            var label = groupShortLabel(g.id) || g.label;
            html += '<li class="' + (active ? 'active' : '') + '">' +
                '<a href="#" data-group="' + g.id + '">' + escapeHtml(label) + '</a></li>';
        });
        html += '</ul>';
        $('#rgv2GroupToggle').html(html);
    }

    function renderCatalog() {
        var items = filteredTemplates();
        if (!items.length) {
            $catalog.html('<p class="rgv2-catalog-empty text-muted">Ninguna plantilla coincide con la búsqueda.</p>');
            return;
        }
        var html = items.map(function (t) {
            var active = t.id === state.templateId ? ' rgv2-catalog-item--active' : '';
            var metaClass = 'rgv2-meta--pending';
            var metaText = 'F' + t.fase;
            if (t.estado === 'listo') {
                metaClass = 'rgv2-meta--ready';
                metaText = 'Listo';
            } else if (t.estado === 'fuera_alcance') {
                metaClass = 'rgv2-meta--off';
                metaText = 'N/A';
            }
            return '<div class="rgv2-catalog-item' + active + '" data-id="' + t.id + '" role="button" tabindex="0">' +
                '<table class="rgv2-catalog-item__table"><tbody><tr>' +
                '<td class="rgv2-catalog-item__icon"><i class="fa ' + (t.icon || 'fa-file-o') + '"></i></td>' +
                '<td class="rgv2-catalog-item__title">' + escapeHtml(t.title) + '</td>' +
                '<td class="rgv2-catalog-item__meta"><span class="rgv2-badge ' + metaClass + '">' + escapeHtml(metaText) + '</span></td>' +
                '</tr></tbody></table></div>';
        }).join('');
        $catalog.html(html);
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function parseMonto(val) {
        if (val === null || val === undefined || val === '') {
            return null;
        }
        var n = parseFloat(String(val).replace(/,/g, '').trim());
        return isNaN(n) ? null : n;
    }

    function formatMonto(val) {
        var n = parseMonto(val);
        if (n === null) {
            return val === null || val === undefined ? '' : String(val);
        }
        var parts = n.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function isMoneyColumn(col) {
        return col && col.type === 'money';
    }

    function formatCellValue(col, val) {
        if (isMoneyColumn(col)) {
            return formatMonto(val);
        }
        return val != null ? val : '';
    }

    function readUrl() {
        var out = { reporte: '', preview: false };
        FILTER_URL_KEYS.forEach(function (key) {
            out[key] = '';
        });
        try {
            var params = new URLSearchParams(w.location.search || '');
            out.reporte = String(params.get('reporte') || '').trim();
            out.preview = params.get('preview') === '1';
            FILTER_URL_KEYS.forEach(function (key) {
                out[key] = String(params.get(key) || '').trim();
            });
        } catch (e) {
            /* ignore */
        }
        return out;
    }

    function isPrettyRouteUrl() {
        var path = String(w.location.pathname || '');
        return path.indexOf(ROUTE_SLUG) !== -1;
    }

    function writeUrl(opts) {
        if (urlSyncLock || !w.history || !w.history.replaceState) {
            return;
        }
        opts = opts || {};
        var params;
        try {
            params = new URLSearchParams(w.location.search || '');
        } catch (e) {
            return;
        }

        if (isPrettyRouteUrl()) {
            params.delete('ruta');
        } else if (!params.get('ruta')) {
            params.set('ruta', ROUTE_SLUG);
        }

        var t = tpl(state.templateId);
        if (t) {
            params.set('reporte', t.id);
            (t.filters || []).forEach(function (key) {
                var val = '';
                if (opts.filters && opts.filters[key] !== undefined) {
                    val = String(opts.filters[key] || '').trim();
                } else {
                    var $el = $('[name="' + key + '"]', '#rgv2Filters');
                    val = $el.length ? String($el.val() || '').trim() : '';
                }
                if (val) {
                    params.set(key, val);
                } else {
                    params.delete(key);
                }
            });
        } else {
            params.delete('reporte');
            FILTER_URL_KEYS.forEach(function (key) {
                params.delete(key);
            });
        }

        var keepPreview = opts.preview === true || (opts.preview !== false && state.previewOk);
        if (keepPreview) {
            params.set('preview', '1');
        } else {
            params.delete('preview');
        }

        var qs = params.toString();
        var next = w.location.pathname + (qs ? '?' + qs : '');
        var current = w.location.pathname + w.location.search;
        if (next !== current) {
            w.history.replaceState({}, '', next);
        }
    }

    var writeUrlDebounced = (function () {
        var timer = null;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                writeUrl({ preview: false });
            }, 350);
        };
    })();

    function isoDate(d) {
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + day;
    }

    function monthStartIso() {
        var d = new Date();
        return isoDate(new Date(d.getFullYear(), d.getMonth(), 1));
    }

    function todayIso() {
        return isoDate(new Date());
    }

    function applyDefaultDates(t) {
        if (!t) {
            return;
        }
        var $ini = $('[name="inicio"]', '#rgv2Filters');
        var $fin = $('[name="fin"]', '#rgv2Filters');
        if (t.id === 'saldos_fecha') {
            if ($fin.length && !$fin.val()) {
                $fin.val(todayIso());
            }
            return;
        }
        if (t.id === 'movimientos_ctacte' || t.id === 'resumen_saldos_fecha') {
            if ($ini.length && !$ini.val()) {
                $ini.val(monthStartIso());
            }
            if ($fin.length && !$fin.val()) {
                $fin.val(todayIso());
            }
            return;
        }
        if (t.id !== 'pagos' && t.id !== 'estado_cuenta') {
            return;
        }
        if ($ini.length && !$ini.val()) {
            $ini.val(monthStartIso());
        }
        if ($fin.length && !$fin.val()) {
            $fin.val(todayIso());
        }
    }

    function updateExportButtons(t) {
        var excelDisabled = true;
        var pdfDisabled = true;
        if (state.previewOk && t && t.estado === 'listo') {
            var ex = t.export || {};
            excelDisabled = ex.excel === false;
            pdfDisabled = ex.pdf === false;
        }
        $('#rgv2PdfBtn').prop('disabled', pdfDisabled);
        $('#rgv2ExcelBtn').prop('disabled', excelDisabled);
    }

    function lookupOptions(type) {
        var rows = lookups[type] || [];
        var labelKey = type === 'cli' ? 'nombre' : 'descripcion';
        var opts = ['<option value="">— Todos —</option>'];
        rows.forEach(function (row) {
            var code = row.codigo != null ? row.codigo : '';
            var label = row[labelKey] != null ? row[labelKey] : '';
            opts.push('<option value="' + escapeHtml(code) + '">' + escapeHtml(code + ' - ' + label) + '</option>');
        });
        return opts.join('');
    }

    function buildFilterField(key) {
        var def = boot.filterDefs[key];
        if (!def) return '';
        var selected = (pendingUrlFilters && pendingUrlFilters[key]) ? pendingUrlFilters[key] : '';
        var col = '<div class="col-sm-6 col-md-3 form-group rgv2-filter-field" data-filter="' + key + '">';
        col += '<label for="rgv2_' + key + '">' + escapeHtml(def.label) + '</label>';

        if (def.type === 'select') {
            var opts = (def.options || []).map(function (o) {
                var sel = selected === o.value ? ' selected' : '';
                return '<option value="' + escapeHtml(o.value) + '"' + sel + '>' + escapeHtml(o.label) + '</option>';
            }).join('');
            col += '<select class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '">' + opts + '</select>';
        } else if (def.type === 'date') {
            col += '<input type="date" class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '" value="' + escapeHtml(selected) + '">';
        } else if (['tip_doc', 'canc', 'cli', 'vend', 'banco'].indexOf(def.type) !== -1) {
            var lookupOpts = lookupOptions(def.type);
            if (selected) {
                lookupOpts = lookupOpts.replace(
                    'value="' + escapeHtml(selected) + '"',
                    'value="' + escapeHtml(selected) + '" selected'
                );
            }
            col += '<select class="form-control input-sm selectpicker" id="rgv2_' + key + '" name="' + key + '" data-live-search="true" data-size="8">' +
                lookupOpts + '</select>';
        } else {
            col += '<input type="text" class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '" value="' + escapeHtml(selected) + '">';
        }
        col += '</div>';
        return col;
    }

    function renderFilters(t) {
        var html = (t.filters || []).map(buildFilterField).join('');
        $('#rgv2FilterFields').html(html);
        if ($.fn.selectpicker) {
            $('#rgv2FilterFields .selectpicker').selectpicker();
        }
    }

    function clearPreview() {
        state.previewOk = false;
        $('#rgv2Kpis').empty();
        $('#rgv2Thead').empty();
        $('#rgv2Tbody').html('<tr><td colspan="20" class="text-muted text-center">Use Vista previa para cargar datos.</td></tr>');
        $('#rgv2Tfoot').empty();
        $('#rgv2ExcelBtn, #rgv2PdfBtn').prop('disabled', true);
    }

    function selectTemplate(id, urlFilters) {
        var t = tpl(id);
        if (!t) return;
        state.templateId = id;
        state.previewOk = false;
        renderCatalog();

        $empty.addClass('hidden');
        $panel.removeClass('hidden');

        $('#rgv2Title').text(t.title);
        $('#rgv2GroupBadge').text(groupLabel(t.group));
        $('#rgv2EstadoBadge')
            .text(estadoLabel(t.estado) + ' · Fase ' + t.fase)
            .removeClass('label-success label-warning label-default')
            .addClass(estadoClass(t.estado));
        $('#rgv2Hint').text(t.hint || '');

        pendingUrlFilters = urlFilters || null;
        renderFilters(t);
        applyDefaultDates(t);
        pendingUrlFilters = null;
        clearPreview();
        updateExportButtons(t);
        writeUrl({ preview: false });
    }

    function readFilters() {
        var t = tpl(state.templateId);
        var data = { accion: 'preview', reporte: state.templateId };
        (t && t.filters ? t.filters : []).forEach(function (key) {
            var $el = $('[name="' + key + '"]', '#rgv2Filters');
            data[key] = $el.length ? $el.val() : '';
        });
        return data;
    }

    function validateRequired() {
        var t = tpl(state.templateId);
        if (!t || !t.required) return true;
        for (var i = 0; i < t.required.length; i++) {
            var key = t.required[i];
            var val = $('[name="' + key + '"]', '#rgv2Filters').val();
            if (!val) {
                var def = boot.filterDefs[key];
                swal('Filtro requerido', 'Indique ' + (def ? def.label : key) + '.', 'warning');
                return false;
            }
        }
        return true;
    }

    function onPreview(e) {
        e.preventDefault();
        if (!state.templateId) return;
        if (!validateRequired()) return;

        var seq = ++state.fetchSeq;
        var $btn = $('#rgv2PreviewBtn').prop('disabled', true);
        clearPreview();
        $('#rgv2Tbody').html('<tr><td colspan="20" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>');

        $.ajax({
            url: 'ajax/reportes-generales-v2.ajax.php',
            method: 'POST',
            dataType: 'json',
            data: readFilters(),
        }).done(function (res) {
            if (seq !== state.fetchSeq) return;
            if (res && res.ok === true) {
                state.previewOk = true;
                renderPreview(res);
                updateExportButtons(tpl(state.templateId));
                writeUrl({ preview: true });
                return;
            }
            state.previewOk = false;
            writeUrl({ preview: false });
            $('#rgv2Tbody').html('<tr><td colspan="20" class="text-muted text-center">Sin datos de vista previa.</td></tr>');
            var msg = (res && res.error) ? res.error : 'No se pudo generar la vista previa.';
            swal('Vista previa', msg, 'info');
        }).fail(function () {
            if (seq !== state.fetchSeq) return;
            state.previewOk = false;
            $('#rgv2Tbody').html('<tr><td colspan="20" class="text-danger text-center">Error de conexión.</td></tr>');
            swal('Error', 'No se pudo contactar al servidor.', 'error');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function renderPreview(res) {
        var cols = res.columns || [];
        var rows = res.rows || [];
        if (cols.length) {
            $('#rgv2Thead').html('<tr>' + cols.map(function (c) {
                var cls = isMoneyColumn(c) ? ' class="rgv2-col-money"' : '';
                return '<th' + cls + '>' + escapeHtml(c.label || c.key || '') + '</th>';
            }).join('') + '</tr>');
        }
        if (!rows.length) {
            $('#rgv2Tbody').html('<tr><td colspan="' + Math.max(cols.length, 1) + '" class="text-muted text-center">Sin registros.</td></tr>');
            return;
        }
        var body = rows.map(function (row) {
            var rowType = row._rowType || '';
            var trClass = '';
            if (rowType === 'group1') {
                trClass = ' class="rgv2-row-group1"';
            } else if (rowType === 'group2') {
                trClass = ' class="rgv2-row-group2"';
            } else if (rowType === 'subtotal') {
                trClass = ' class="rgv2-row-subtotal"';
            }
            return '<tr' + trClass + '>' + cols.map(function (c) {
                if (c.key === '_rowType') {
                    return '';
                }
                var val = formatCellValue(c, row[c.key]);
                var cls = isMoneyColumn(c) ? ' class="rgv2-col-money"' : '';
                return '<td' + cls + '>' + escapeHtml(val) + '</td>';
            }).join('') + '</tr>';
        }).join('');
        $('#rgv2Tbody').html(body);
        if (res.kpis && res.kpis.length) {
            var kpiHtml = res.kpis.map(function (k) {
                return '<span class="rgv2-kpi"><strong>' + escapeHtml(k.label) + ':</strong> ' + escapeHtml(k.value) + '</span>';
            }).join('');
            if (res.truncated) {
                kpiHtml += '<span class="rgv2-kpi rgv2-kpi--warn"><strong>Vista previa parcial</strong> (máx. 500 filas)</span>';
            }
            $('#rgv2Kpis').html(kpiHtml);
        }
    }

    function onExport(formato) {
        if (!state.previewOk) {
            swal('Exportar', 'Genere primero una vista previa válida.', 'warning');
            return;
        }
        var data = readFilters();
        data.accion = 'export';
        data.formato = formato;
        $.ajax({
            url: 'ajax/reportes-generales-v2.ajax.php',
            method: 'POST',
            dataType: 'json',
            data: data,
        }).done(function (res) {
            if (res && res.ok && res.url) {
                w.location.href = res.url;
                return;
            }
            swal('Exportar', (res && res.error) ? res.error : 'Exportación no disponible.', 'info');
        }).fail(function () {
            swal('Error', 'No se pudo exportar.', 'error');
        });
    }

    function bindEvents() {
        $('#rgv2GroupToggle').on('click', 'a[data-group]', function (e) {
            e.preventDefault();
            var group = $(this).data('group');
            if (!group || group === state.group) return;
            state.group = group;
            renderGroupToggle();
            renderCatalog();
        });

        $('#rgv2Search').on('input', function () {
            state.search = $(this).val();
            renderCatalog();
        });

        $catalog.on('click', '.rgv2-catalog-item', function () {
            selectTemplate($(this).data('id'));
        });

        $catalog.on('keydown', '.rgv2-catalog-item', function (e) {
            if (e.keyCode === 13 || e.keyCode === 32) {
                e.preventDefault();
                selectTemplate($(this).data('id'));
            }
        });

        $('#rgv2Filters').on('submit', onPreview);
        $('#rgv2Filters').on('change', 'select, input', writeUrlDebounced);
        $('#rgv2ExcelBtn').on('click', function () { onExport('xlsx'); });
        $('#rgv2PdfBtn').on('click', function () { onExport('pdf'); });
    }

    function initFromUrl() {
        var urlState = readUrl();
        if (!urlState.reporte || !tpl(urlState.reporte)) {
            return;
        }
        var t = tpl(urlState.reporte);
        state.group = t.group;
        renderGroupToggle();
        selectTemplate(urlState.reporte, urlState);
        if (urlState.preview) {
            $('#rgv2Filters').trigger('submit');
        }
    }

    function init() {
        if (isPrettyRouteUrl()) {
            try {
                var params = new URLSearchParams(w.location.search || '');
                if (params.has('ruta')) {
                    params.delete('ruta');
                    var qs = params.toString();
                    w.history.replaceState({}, '', w.location.pathname + (qs ? '?' + qs : ''));
                }
            } catch (e) {
                /* ignore */
            }
        }
        renderGroupToggle();
        renderCatalog();
        bindEvents();
        clearPreview();
        initFromUrl();
    }

    $(init);
}(window, jQuery));
