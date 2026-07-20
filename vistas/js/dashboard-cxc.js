(function () {
    'use strict';

    var chartAntiguedad = null;
    var chartVentasTendencia = null;

    function elData() {
        return document.getElementById('dashboardCxcData');
    }

    function leerEstado() {
        var nodo = elData();
        if (!nodo) {
            return {
                anio: '',
                mes: '',
                vendedor: '',
                cliente: '',
                rango: '',
                todosVendedores: false,
                pagina: 1
            };
        }

        return {
            anio: nodo.getAttribute('data-anio') || '',
            mes: nodo.getAttribute('data-mes') || '',
            vendedor: nodo.getAttribute('data-vendedor') || '',
            cliente: nodo.getAttribute('data-cliente') || '',
            rango: nodo.getAttribute('data-rango') || '',
            todosVendedores: nodo.getAttribute('data-todos-vendedores') === '1',
            pagina: parseInt(nodo.getAttribute('data-pagina') || '1', 10) || 1
        };
    }

    function escribirEstado(estado) {
        var nodo = elData();
        if (!nodo) {
            return;
        }

        nodo.setAttribute('data-anio', estado.anio || '');
        nodo.setAttribute('data-mes', estado.mes || '');
        nodo.setAttribute('data-vendedor', estado.vendedor || '');
        nodo.setAttribute('data-cliente', estado.cliente || '');
        nodo.setAttribute('data-rango', estado.rango || '');
        nodo.setAttribute('data-todos-vendedores', estado.todosVendedores ? '1' : '0');
        nodo.setAttribute('data-pagina', String(estado.pagina || 1));
    }

    function construirUrl(estado) {
        var params = [
            'ruta=dashboard-cxc',
            'año=' + encodeURIComponent(estado.anio),
            'mes=' + encodeURIComponent(estado.mes)
        ];

        if (estado.vendedor) {
            params.push('vendedor=' + encodeURIComponent(estado.vendedor));
        }
        if (estado.cliente) {
            params.push('cliente=' + encodeURIComponent(estado.cliente));
        }
        if (estado.rango) {
            params.push('rango=' + encodeURIComponent(estado.rango));
        }
        if (estado.todosVendedores) {
            params.push('todos_vendedores=1');
        }
        if (estado.pagina && Number(estado.pagina) > 1) {
            params.push('pagina=' + encodeURIComponent(estado.pagina));
        }

        return 'index.php?' + params.join('&');
    }

    function irConEstado(estado) {
        escribirEstado(estado);
        window.location.href = construirUrl(estado);
    }

    function paramsAjax(estado, extra) {
        var base = {
            anio: estado.anio,
            mes: estado.mes,
            vendedor: estado.vendedor,
            cliente: estado.cliente,
            rango: estado.rango,
            todos_vendedores: estado.todosVendedores ? '1' : '0'
        };

        if (extra) {
            for (var k in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, k)) {
                    base[k] = extra[k];
                }
            }
        }

        return base;
    }

    function formatearMonto(valor) {
        var n = Number(valor) || 0;
        return 'S/ ' + n.toLocaleString('es-PE', { maximumFractionDigits: 0 });
    }

    function formatearEntero(valor) {
        return (Number(valor) || 0).toLocaleString('es-PE', { maximumFractionDigits: 0 });
    }

    function escapeHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function esVendedorActivo(fila) {
        return fila.es_activo === true || fila.es_activo === 1 || fila.es_activo === '1';
    }

    function filtrarFilasVendedor(filas, todosVendedores) {
        if (!filas || !filas.length || todosVendedores) {
            return filas || [];
        }

        return filas.filter(esVendedorActivo);
    }

    function clasePctVencido(pct) {
        var n = Number(pct) || 0;
        if (n >= 50) {
            return 'cxc-tabla-vendedor__pct--alto';
        }
        if (n >= 25) {
            return 'cxc-tabla-vendedor__pct--medio';
        }
        return 'cxc-tabla-vendedor__pct--bajo';
    }

    function actualizarUrlSinRecarga(estado) {
        if (!window.history || !window.history.replaceState) {
            return;
        }
        window.history.replaceState(null, '', construirUrl(estado));
    }

    function marcarTablasVendedorCargando(cargando) {
        document.querySelectorAll('.cxc-panel-cartera-vendedor, .cxc-panel-ventas-unificado').forEach(function (panel) {
            panel.classList.toggle('cxc-tablas-vendedor--cargando', cargando);
        });
    }

    function renderTablaCxcVendedor(filas, todosVendedores) {
        var table = document.getElementById('tablaCxcVendedor');
        if (!table) {
            return;
        }

        var filasVisibles = filtrarFilasVendedor(filas, todosVendedores);
        var tbody = table.querySelector('tbody');
        var totales = { clientes: 0, por_vencer: 0, vencido: 0, incobrable: 0, total: 0 };
        var html = '';

        if (!filasVisibles.length) {
            html = '<tr><td colspan="8" class="text-muted">Sin datos</td></tr>';
        } else {
            filasVisibles.forEach(function (fila, i) {
                totales.clientes += Number(fila.clientes) || 0;
                totales.por_vencer += Number(fila.por_vencer) || 0;
                totales.vencido += Number(fila.vencido) || 0;
                totales.incobrable += Number(fila.incobrable) || 0;
                totales.total += Number(fila.total) || 0;

                var label = (fila.vendedor || '') + ' - ' + (fila.nom_vendedor || '');
                var pct = Number(fila.pct_vencido) || 0;

                html += '<tr class="cxc-fila-vendedor" data-vendedor="' + escapeHtml(fila.vendedor || '') + '">';
                html += '<td class="text-muted cxc-tabla-vendedor__rank">' + (i + 1) + '</td>';
                html += '<td><a href="#" class="cxc-link-vendedor" title="Filtrar por este vendedor">' + escapeHtml(label) + '</a></td>';
                html += '<td class="text-right">' + formatearEntero(fila.clientes) + '</td>';
                html += '<td class="text-right cxc-tabla-vendedor__por-vencer">' + formatearMonto(fila.por_vencer) + '</td>';
                html += '<td class="text-right cxc-tabla-vendedor__vencido">' + formatearMonto(fila.vencido) + '</td>';
                html += '<td class="text-right cxc-tabla-vendedor__incobrable">' + formatearMonto(fila.incobrable) + '</td>';
                html += '<td class="text-right cxc-tabla-vendedor__total-celda">' + formatearMonto(fila.total) + '</td>';
                html += '<td class="text-right cxc-tabla-vendedor__pct ' + clasePctVencido(pct) + '">' + pct.toFixed(1) + '%</td>';
                html += '</tr>';
            });
        }

        if (tbody) {
            tbody.innerHTML = html;
        }

        var tfoot = table.querySelector('tfoot');
        if (!filasVisibles.length) {
            if (tfoot) {
                tfoot.parentNode.removeChild(tfoot);
            }
            return;
        }

        var pctTotal = totales.total > 0 ? Math.round((totales.vencido / totales.total) * 1000) / 10 : 0;
        var footHtml = '<tr class="cxc-tabla-vendedor__total">';
        footHtml += '<td></td><td><strong>Total</strong></td>';
        footHtml += '<td class="text-right"><strong>' + formatearEntero(totales.clientes) + '</strong></td>';
        footHtml += '<td class="text-right cxc-tabla-vendedor__por-vencer"><strong>' + formatearMonto(totales.por_vencer) + '</strong></td>';
        footHtml += '<td class="text-right cxc-tabla-vendedor__vencido"><strong>' + formatearMonto(totales.vencido) + '</strong></td>';
        footHtml += '<td class="text-right cxc-tabla-vendedor__incobrable"><strong>' + formatearMonto(totales.incobrable) + '</strong></td>';
        footHtml += '<td class="text-right cxc-tabla-vendedor__total-celda"><strong>' + formatearMonto(totales.total) + '</strong></td>';
        footHtml += '<td class="text-right cxc-tabla-vendedor__pct ' + clasePctVencido(pctTotal) + '"><strong>' + pctTotal.toFixed(1) + '%</strong></td>';
        footHtml += '</tr>';

        if (!tfoot) {
            tfoot = document.createElement('tfoot');
            table.appendChild(tfoot);
        }
        tfoot.innerHTML = footHtml;
    }

    function renderTablaVentasVendedor(filas, todosVendedores) {
        var tbody = document.querySelector('#tablaVentasVendedor tbody');
        if (!tbody) {
            return;
        }

        var filasVisibles = filtrarFilasVendedor(filas, todosVendedores);

        if (!filasVisibles.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-muted">Sin ventas en el período</td></tr>';
            return;
        }

        var html = '';
        filasVisibles.forEach(function (fila) {
            var label = (fila.vendedor || '') + ' - ' + (fila.nom_vendedor || '');
            html += '<tr class="cxc-fila-venta-vendedor" data-vendedor="' + escapeHtml(fila.vendedor || '') + '">';
            html += '<td><a href="#" class="cxc-link-vendedor" title="Filtrar por vendedor">' + escapeHtml(label) + '</a></td>';
            html += '<td class="text-right">' + formatearMonto(fila.venta_mes) + '</td>';
            html += '<td class="text-right">' + formatearMonto(fila.venta_anio) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    function ajaxTablasVendedor(estado) {
        var params = paramsAjax(estado, { accion: 'tablas_vendedor' });
        var qs = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');

        return fetch('ajax/dashboard-cxc.ajax.php?' + qs, { credentials: 'same-origin' })
            .then(function (resp) {
                return resp.json();
            });
    }

    function actualizarTablasVendedorAjax() {
        var check = document.getElementById('cxcIncluirTodosVendedores');
        var estado = leerEstado();

        if (check) {
            estado.todosVendedores = check.checked;
        }

        escribirEstado(estado);
        actualizarUrlSinRecarga(estado);
        marcarTablasVendedorCargando(true);

        return ajaxTablasVendedor(estado)
            .then(function (resp) {
                if (!resp || !resp.ok || !resp.data) {
                    return;
                }

                renderTablaCxcVendedor(resp.data.cartera || [], estado.todosVendedores);
                renderTablaVentasVendedor(resp.data.ventas || [], estado.todosVendedores);
            })
            .catch(function () {
                /* sin feedback invasivo; el usuario puede reintentar */
            })
            .then(function () {
                marcarTablasVendedorCargando(false);
            });
    }

    function destruirChart(chart) {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    }

    function prepararCanvasDonut(canvas, wrap) {
        if (!canvas || !wrap) {
            return { ancho: 160, alto: 160 };
        }

        var ancho = wrap.clientWidth || wrap.offsetWidth || 168;
        if (ancho > 200) {
            ancho = 168;
        }
        if (ancho < 120) {
            ancho = 120;
        }

        var alto = ancho;
        wrap.style.height = alto + 'px';
        canvas.width = ancho;
        canvas.height = alto;
        canvas.style.width = ancho + 'px';
        canvas.style.height = alto + 'px';

        return { ancho: ancho, alto: alto };
    }

    function renderGraficoAntiguedad(data) {
        var wrap = document.getElementById('wrapGraficoAntiguedadCxc');
        var empty = document.getElementById('graficoAntiguedadCxcEmpty');

        if (!wrap || typeof Chart === 'undefined') {
            return;
        }

        var rangos = (data && data.rangos) ? data.rangos : [];
        var total = (data && data.total) ? Number(data.total) : 0;

        destruirChart(chartAntiguedad);
        chartAntiguedad = null;

        if (total <= 0 || rangos.length === 0) {
            wrap.style.display = 'none';
            if (empty) {
                empty.style.display = 'block';
            }
            return;
        }

        wrap.style.display = 'block';
        if (empty) {
            empty.style.display = 'none';
        }

        var totalNodo = document.getElementById('cxcDonutTotalVencido');
        if (totalNodo) {
            var valorNodo = totalNodo.querySelector('.cxc-cobranza-grafico__total-valor');
            if (valorNodo) {
                valorNodo.textContent = 'S/ ' + total.toLocaleString('es-PE', { maximumFractionDigits: 0 });
            }
        }

        var canvas = document.getElementById('graficoAntiguedadCxc');
        if (!canvas) {
            return;
        }

        prepararCanvasDonut(canvas, wrap);

        var segmentos = [];
        rangos.forEach(function (r) {
            if (Number(r.monto) > 0) {
                segmentos.push({
                    value: Number(r.monto),
                    color: r.color,
                    highlight: r.color,
                    label: r.label
                });
            }
        });

        if (segmentos.length === 0) {
            wrap.style.display = 'none';
            if (empty) {
                empty.style.display = 'block';
            }
            return;
        }

        chartAntiguedad = new Chart(canvas.getContext('2d')).Doughnut(segmentos, {
            responsive: false,
            maintainAspectRatio: false,
            animation: false,
            segmentShowStroke: true,
            segmentStrokeColor: '#fff',
            segmentStrokeWidth: 2,
            segmentStrokeColor: '#ffffff',
            percentageInnerCutout: 72,
            showTooltips: true,
            tooltipTemplate: '<%if (label){%><%=label%>: <%}%>S/ <%= value %>'
        });

        if (chartAntiguedad && typeof chartAntiguedad.draw === 'function') {
            chartAntiguedad.draw();
        }
    }

    function medirAltoGraficoTendencia() {
        var colTabla = document.querySelector('.cxc-seccion-ventas--media .cxc-ventas-col-tabla');
        var header = document.querySelector('.cxc-seccion-ventas--media .cxc-spark-ventas__header');
        var chartArea = document.querySelector('.cxc-seccion-ventas--media .cxc-spark-ventas__chart');
        var alto = 0;

        if (colTabla && header) {
            alto = colTabla.offsetHeight - header.offsetHeight - 12;
        }

        if (alto < 80 && chartArea) {
            alto = chartArea.clientHeight;
        }

        return alto > 80 ? alto : 200;
    }

    function medirAnchoGraficoTendencia(wrap) {
        if (!wrap) {
            return 300;
        }

        var ancho = wrap.clientWidth || wrap.offsetWidth;
        if (ancho < 50 && wrap.parentElement) {
            ancho = wrap.parentElement.clientWidth;
        }

        return ancho > 50 ? ancho : 300;
    }

    function prepararCanvasTendencia(canvas, wrap) {
        var alto = medirAltoGraficoTendencia();
        var ancho = medirAnchoGraficoTendencia(wrap);

        wrap.style.height = alto + 'px';

        canvas.width = ancho;
        canvas.height = alto;
        canvas.style.width = ancho + 'px';
        canvas.style.height = alto + 'px';

        return { ancho: ancho, alto: alto };
    }

    function forzarDibujoChart(chart) {
        if (!chart) {
            return;
        }

        if (typeof chart.draw === 'function') {
            chart.draw();
        } else if (typeof chart.update === 'function') {
            chart.update();
        }
    }

    function renderGraficoVentasTendencia(data) {
        var wrap = document.getElementById('wrapGraficoVentasTendencia');
        var empty = document.getElementById('graficoVentasTendenciaEmpty');

        if (!wrap || typeof Chart === 'undefined') {
            return;
        }

        var labels = (data && data.labels) ? data.labels : [];
        var act = (data && data.mes_actual && data.mes_actual.montos) ? data.mes_actual.montos : [];
        var ant = (data && data.mes_anterior && data.mes_anterior.montos) ? data.mes_anterior.montos : [];
        var totalAct = act.reduce(function (a, b) { return a + Number(b || 0); }, 0);
        var totalAnt = ant.reduce(function (a, b) { return a + Number(b || 0); }, 0);

        destruirChart(chartVentasTendencia);
        chartVentasTendencia = null;

        if (labels.length === 0 || (totalAct <= 0 && totalAnt <= 0)) {
            wrap.style.display = 'none';
            wrap.innerHTML = '<canvas id="graficoVentasTendencia"></canvas>';
            if (empty) {
                empty.style.display = 'block';
            }
            return;
        }

        wrap.style.display = 'block';
        if (empty) {
            empty.style.display = 'none';
        }

        wrap.innerHTML = '<canvas id="graficoVentasTendencia"></canvas>';
        var canvas = document.getElementById('graficoVentasTendencia');
        if (!canvas) {
            return;
        }

        prepararCanvasTendencia(canvas, wrap);

        var labelAct = (data.mes_actual && data.mes_actual.label) ? data.mes_actual.label : 'Mes actual';
        var labelAnt = (data.mes_anterior && data.mes_anterior.label) ? data.mes_anterior.label : 'Mes anterior';

        var chartData = {
            labels: labels,
            datasets: [
                {
                    label: labelAct,
                    fillColor: 'rgba(60, 141, 188, 0.18)',
                    strokeColor: '#3c8dbc',
                    pointColor: 'rgba(0, 0, 0, 0)',
                    pointStrokeColor: 'rgba(0, 0, 0, 0)',
                    pointHighlightFill: '#3c8dbc',
                    pointHighlightStroke: '#fff',
                    data: act
                },
                {
                    label: labelAnt,
                    fillColor: 'rgba(0, 0, 0, 0)',
                    strokeColor: '#94a3b8',
                    pointColor: 'rgba(0, 0, 0, 0)',
                    pointStrokeColor: 'rgba(0, 0, 0, 0)',
                    pointHighlightFill: '#94a3b8',
                    pointHighlightStroke: '#fff',
                    data: ant
                }
            ]
        };

        var options = {
            scaleShowGridLines: false,
            scaleShowHorizontalLines: false,
            scaleShowVerticalLines: false,
            scaleLineColor: 'rgba(0,0,0,0)',
            scaleFontSize: 0,
            scaleBeginAtZero: true,
            bezierCurve: true,
            pointDot: false,
            pointDotRadius: 0,
            datasetStroke: true,
            datasetStrokeWidth: 2,
            datasetFill: true,
            responsive: false,
            maintainAspectRatio: false,
            animation: false,
            showTooltips: true,
            tooltipTemplate: '<%if (label){%>Día <%=label%>: <%}%>S/ <%= value %>',
            multiTooltipTemplate: '<%= datasetLabel %>: S/ <%= value %>',
            onAnimationComplete: function () {
                forzarDibujoChart(this);
            }
        };

        chartVentasTendencia = new Chart(canvas.getContext('2d')).Line(chartData, options);
        forzarDibujoChart(chartVentasTendencia);
    }

    function redibujarGraficoVentasTendencia() {
        var nodo = document.getElementById('cxcVentasTendenciaData');
        if (!nodo) {
            return;
        }

        try {
            var data = JSON.parse(nodo.textContent || '{}');
            renderGraficoVentasTendencia(data);
        } catch (e) {
            renderGraficoVentasTendencia(null);
        }
    }

    function bindCheckboxTodosVendedores() {
        var check = document.getElementById('cxcIncluirTodosVendedores');
        if (!check) {
            return;
        }

        check.addEventListener('change', function () {
            actualizarTablasVendedorAjax();
        });
    }

    function bindFiltrosPrincipales() {
        var anio = document.getElementById('anioCxc');
        var mes = document.getElementById('mesCxc');
        var vendedor = document.getElementById('vendedorCxc');
        var checkTodos = document.getElementById('cxcIncluirTodosVendedores');

        function aplicar() {
            var estado = leerEstado();
            estado.anio = anio ? anio.value : estado.anio;
            estado.mes = mes ? mes.value : estado.mes;
            estado.vendedor = vendedor ? vendedor.value : estado.vendedor;
            estado.todosVendedores = checkTodos ? checkTodos.checked : estado.todosVendedores;
            estado.cliente = '';
            estado.rango = '';
            estado.pagina = 1;
            irConEstado(estado);
        }

        if (anio) {
            anio.addEventListener('change', aplicar);
        }
        if (mes) {
            mes.addEventListener('change', aplicar);
        }
        if (vendedor) {
            vendedor.addEventListener('change', aplicar);
        }
    }

    function bindEnlacesTablas() {
        var root = document.querySelector('.cxc-dashboard');
        if (root) {
            root.addEventListener('click', function (ev) {
                var objetivo = ev.target.closest('.cxc-fila-vendedor, .cxc-link-vendedor, .cxc-fila-venta-vendedor');
                if (!objetivo || !root.contains(objetivo)) {
                    return;
                }

                ev.preventDefault();
                var fila = ev.target.closest('[data-vendedor]');
                if (!fila) {
                    return;
                }

                var estado = leerEstado();
                estado.vendedor = fila.getAttribute('data-vendedor') || '';
                estado.pagina = 1;
                irConEstado(estado);
            });
        }

        document.querySelectorAll('.cxc-fila-rango, .cxc-fila-rango-detalle, .cxc-link-rango').forEach(function (nodo) {
            nodo.addEventListener('click', function (ev) {
                ev.preventDefault();
                var fila = ev.currentTarget.closest('[data-rango]');
                if (!fila) {
                    return;
                }
                var estado = leerEstado();
                estado.rango = fila.getAttribute('data-rango') || '';
                estado.pagina = 1;
                irConEstado(estado);
            });
        });

        document.querySelectorAll('.cxc-fila-cliente, .cxc-link-cliente').forEach(function (nodo) {
            nodo.addEventListener('click', function (ev) {
                ev.preventDefault();
                var fila = ev.currentTarget.closest('.cxc-fila-cliente');
                if (!fila) {
                    return;
                }
                if (fila.getAttribute('data-tipo') === 'grupo') {
                    return;
                }
                var estado = leerEstado();
                estado.cliente = fila.getAttribute('data-cliente') || '';
                estado.pagina = 1;
                irConEstado(estado);
            });
        });

        document.querySelectorAll('.cxc-limpiar-filtros-detalle').forEach(function (nodo) {
            nodo.addEventListener('click', function (ev) {
                ev.preventDefault();
                var estado = leerEstado();
                estado.cliente = '';
                estado.rango = '';
                estado.pagina = 1;
                irConEstado(estado);
            });
        });
    }

    function bindBusquedaCliente() {
        var input = document.querySelector('.cxc-buscar-cliente');
        if (!input) {
            return;
        }

        input.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Enter') {
                return;
            }
            ev.preventDefault();
            var estado = leerEstado();
            estado.cliente = input.value.trim();
            estado.pagina = 1;
            irConEstado(estado);
        });
    }

    function bindPaginacionDetalle() {
        var contenedor = document.getElementById('cxcDetallePaginacion');
        if (!contenedor) {
            return;
        }

        var prev = contenedor.querySelector('.cxc-detalle-prev');
        var next = contenedor.querySelector('.cxc-detalle-next');

        if (prev) {
            prev.addEventListener('click', function () {
                var estado = leerEstado();
                estado.pagina = Math.max(1, (estado.pagina || 1) - 1);
                irConEstado(estado);
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                var estado = leerEstado();
                var totalPaginas = parseInt(contenedor.getAttribute('data-total-paginas') || '0', 10);
                estado.pagina = Math.min(totalPaginas, (estado.pagina || 1) + 1);
                irConEstado(estado);
            });
        }
    }

    function initGraficoVentasTendencia() {
        var nodo = document.getElementById('cxcVentasTendenciaData');
        if (!nodo) {
            return;
        }

        try {
            var data = JSON.parse(nodo.textContent || '{}');
            renderGraficoVentasTendencia(data);
        } catch (e) {
            renderGraficoVentasTendencia(null);
        }
    }

    function initGraficoInicial() {
        var nodo = document.getElementById('cxcAntiguedadInitialData');
        if (!nodo) {
            return;
        }

        try {
            var data = JSON.parse(nodo.textContent || '{}');
            renderGraficoAntiguedad(data);
        } catch (e) {
            renderGraficoAntiguedad(null);
        }
    }

    var resizeVentasTimer = null;

    function programarRedibujoVentasTendencia(delay) {
        window.clearTimeout(resizeVentasTimer);
        resizeVentasTimer = window.setTimeout(function () {
            requestAnimationFrame(function () {
                redibujarGraficoVentasTendencia();
            });
        }, delay || 120);
    }

    function initGraficoVentasTendenciaDiferido() {
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                redibujarGraficoVentasTendencia();
            });
        });
    }

    function initGraficoAntiguedadDiferido() {
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                initGraficoInicial();
            });
        });
    }

    function initGraficosDashboardCxc() {
        initGraficoAntiguedadDiferido();
        initGraficoVentasTendenciaDiferido();
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindFiltrosPrincipales();
        bindCheckboxTodosVendedores();
        bindEnlacesTablas();
        bindBusquedaCliente();
        bindPaginacionDetalle();

        if (document.readyState === 'complete') {
            initGraficosDashboardCxc();
        } else {
            window.addEventListener('load', initGraficosDashboardCxc);
        }

        document.querySelectorAll('.cxc-ventas-tabs a[data-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                programarRedibujoVentasTendencia(100);
            });
        });

        window.addEventListener('resize', function () {
            programarRedibujoVentasTendencia(180);
        });
    });
})();
