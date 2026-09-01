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
        var html = boot.groups.map(function (g) {
            var active = g.id === state.group ? ' active' : '';
            var label = groupShortLabel(g.id) || g.label;
            return '<button type="button" class="rgv2-group-btn' + active + '" data-group="' + g.id + '" role="tab"' +
                (active ? ' aria-selected="true"' : ' aria-selected="false"') + '>' +
                escapeHtml(label) + '</button>';
        }).join('');
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
                metaText = 'OK';
            } else if (t.estado === 'fuera_alcance') {
                metaClass = 'rgv2-meta--off';
                metaText = 'N/A';
            }
            return '<div class="rgv2-catalog-item' + active + '" data-id="' + t.id + '" role="button" tabindex="0">' +
                '<span class="rgv2-catalog-item__icon"><i class="fa ' + (t.icon || 'fa-file-o') + '"></i></span>' +
                '<span class="rgv2-catalog-item__title">' + escapeHtml(t.title) + '</span>' +
                '<span class="rgv2-catalog-item__meta ' + metaClass + '">' + escapeHtml(metaText) + '</span>' +
                '</div>';
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
        var col = '<div class="col-sm-6 col-md-4 form-group" data-filter="' + key + '">';
        col += '<label for="rgv2_' + key + '">' + escapeHtml(def.label) + '</label>';

        if (def.type === 'select') {
            var opts = (def.options || []).map(function (o) {
                return '<option value="' + escapeHtml(o.value) + '">' + escapeHtml(o.label) + '</option>';
            }).join('');
            col += '<select class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '">' + opts + '</select>';
        } else if (def.type === 'date') {
            col += '<input type="date" class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '">';
        } else if (['tip_doc', 'canc', 'cli', 'vend', 'banco'].indexOf(def.type) !== -1) {
            col += '<select class="form-control input-sm selectpicker" id="rgv2_' + key + '" name="' + key + '" data-live-search="true" data-size="8">' +
                lookupOptions(def.type) + '</select>';
        } else {
            col += '<input type="text" class="form-control input-sm" id="rgv2_' + key + '" name="' + key + '">';
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

    function selectTemplate(id) {
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

        renderFilters(t);
        clearPreview();
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
                $('#rgv2ExcelBtn, #rgv2PdfBtn').prop('disabled', false);
                return;
            }
            state.previewOk = false;
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
                return '<th>' + escapeHtml(c.label || c.key || '') + '</th>';
            }).join('') + '</tr>');
        }
        if (!rows.length) {
            $('#rgv2Tbody').html('<tr><td colspan="' + Math.max(cols.length, 1) + '" class="text-muted text-center">Sin registros.</td></tr>');
            return;
        }
        var body = rows.map(function (row) {
            return '<tr>' + cols.map(function (c) {
                var val = row[c.key] != null ? row[c.key] : '';
                return '<td>' + escapeHtml(val) + '</td>';
            }).join('') + '</tr>';
        }).join('');
        $('#rgv2Tbody').html(body);
        if (res.kpis && res.kpis.length) {
            $('#rgv2Kpis').html(res.kpis.map(function (k) {
                return '<span class="rgv2-kpi"><strong>' + escapeHtml(k.label) + ':</strong> ' + escapeHtml(k.value) + '</span>';
            }).join(''));
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
        $('#rgv2GroupToggle').on('click', '.rgv2-group-btn', function () {
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
        $('#rgv2ExcelBtn').on('click', function () { onExport('xlsx'); });
        $('#rgv2PdfBtn').on('click', function () { onExport('pdf'); });
    }

    function init() {
        renderGroupToggle();
        renderCatalog();
        bindEvents();
        clearPreview();
    }

    $(init);
}(window, jQuery));
