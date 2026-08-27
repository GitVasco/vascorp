(function () {
    "use strict";

    var chartVentas = null;
    var chartCobranzas = null;
    var chartCartera = null;
    var tituloOriginal = document.title;

    var pluginEtiquetas = {
        id: "isvEtiquetas",
        afterDatasetsDraw: function (chart) {
            if (!chart.canvas || (chart.canvas.id !== "isvChartVentas" && chart.canvas.id !== "isvChartCobranzas")) {
                return;
            }
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (ds, i) {
                var meta = chart.getDatasetMeta(i);
                if (meta.hidden) {
                    return;
                }
                meta.data.forEach(function (punto, idx) {
                    var val = ds.data[idx];
                    if (!val) {
                        return;
                    }
                    ctx.save();
                    ctx.font = "10px sans-serif";
                    ctx.fillStyle = ds.borderColor;
                    ctx.textAlign = "center";
                    ctx.fillText(fmtMiles(val), punto.x, punto.y - 8);
                    ctx.restore();
                });
            });
        }
    };

    function $(id) {
        return document.getElementById(id);
    }

    function fmtSoles(n) {
        return "S/ " + (Number(n) || 0).toLocaleString("es-PE", { maximumFractionDigits: 0 });
    }

    function fmtMiles(n) {
        return (Number(n) || 0).toLocaleString("es-PE", { maximumFractionDigits: 0 });
    }

    function fmtVar(p) {
        var n = Number(p) || 0;
        var cls = n >= 0 ? "isv-var-up" : "isv-var-down";
        var txt = (n >= 0 ? "▲ +" : "▼ ") + n.toFixed(1) + "%";
        return '<span class="' + cls + '">' + txt + "</span>";
    }

    function celda(fila, campo) {
        if (fila.tipo === "moneda") {
            return fmtSoles(fila[campo]);
        }
        return fmtMiles(fila[campo]);
    }

    function lis(items) {
        return (items || []).map(function (t) {
            return "<li>" + t + "</li>";
        }).join("");
    }

    function pintar(inf) {
        var k = inf.kpis;
        var p = inf.periodo;
        var v = inf.vendedor;
        var car = inf.cartera;

        $("isvMetaSemana").textContent = p.etiqueta;
        $("isvMetaEmision").textContent = p.fecha_emision;
        $("isvNomVendedor").textContent = v.nombre;
        $("isvZonaVendedor").textContent = "Zona: " + v.zona;
        $("isvKpiVenta").textContent = fmtSoles(k.venta);
        $("isvKpiPedidos").textContent = fmtMiles(k.pedidos) + " Pedidos";
        $("isvKpiClientes").textContent = fmtMiles(k.clientes_compra);
        $("isvKpiCartera").textContent = fmtMiles(k.clientes_cartera) + " en cartera";
        $("isvKpiNuevos").textContent = fmtMiles(k.nuevos);
        $("isvKpiCobranza").textContent = fmtSoles(k.cobranza);
        var pf = inf.por_facturar || {};
        $("isvKpiPorFacturar").textContent = fmtSoles(pf.total_soles);
        $("isvKpiPorFacturarN").textContent = fmtMiles(pf.total_pedidos) + " pedidos abiertos";

        var estadosPf = pf.por_estado || [];
        $("isvPfResumen").innerHTML =
            "<div>Total abierto<b>" + fmtSoles(pf.total_soles) + "</b>" + fmtMiles(pf.total_pedidos) + " ped.</div>" +
            "<div>De esta semana<b>" + fmtSoles(pf.semana_soles) + "</b>" + fmtMiles(pf.semana_pedidos) + " ped.</div>" +
            estadosPf.map(function (e) {
                return "<div>" + e.label + "<b>" + fmtSoles(e.soles) + "</b>" + fmtMiles(e.pedidos) + " ped.</div>";
            }).join("");

        $("isvResVenta").textContent = fmtSoles(k.venta);
        $("isvResProm").textContent = fmtSoles(k.promedio_4);
        $("isvResVar").innerHTML = fmtVar(k.variacion_promedio);
        $("isvCarteraTotal").textContent = fmtSoles(car.total);

        $("isvTablaComp").innerHTML = (inf.comparativo || []).map(function (fila) {
            return "<tr><td>" + fila.indicador + "</td><td class='isv-num'>" + celda(fila, "actual") +
                "</td><td class='isv-num'>" + celda(fila, "anterior") + "</td><td>" + fmtVar(fila.variacion) + "</td></tr>";
        }).join("");

        $("isvTablaCartera").innerHTML = (car.tramos || []).map(function (t) {
            return "<tr><td><span class='isv-swatch isv-swatch--" + t.id +
                "'></span></td><td>" + t.label + "</td><td class='isv-num'>" + fmtSoles(t.monto) +
                "</td><td>" + Number(t.pct).toFixed(1) + "%</td></tr>";
        }).join("");

        $("isvCarteraFoot").innerHTML =
            "<div>Cobranza realizada<b>" + fmtSoles(k.cobranza) + "</b></div>" +
            "<div>Por vencer<b>" + fmtSoles(car.monto_por_vencer) + "</b></div>" +
            "<div>Vencido 0-30d<b>" + fmtSoles(car.monto_0_30) + "</b></div>" +
            "<div>Vencido +30d<b>" + fmtSoles(car.monto_mas_30) + "</b></div>" +
            "<div>Total cartera<b>" + fmtSoles(car.total) + "</b></div>";

        var top = inf.top_clientes || [];
        $("isvTablaTop").innerHTML = top.length
            ? top.map(function (c) {
                return "<tr><td>" + c.puesto + "</td><td>" + c.nombre + "</td><td class='isv-num'>" + fmtSoles(c.venta) + "</td></tr>";
            }).join("")
            : "<tr><td colspan='3'>Sin ventas en la semana</td></tr>";

        $("isvLectura").innerHTML = lis(inf.lectura);
        $("isvPlan").innerHTML = lis(inf.plan);
        $("isvObs").textContent = inf.observaciones || "";

        pintarDias(inf.diario, "isvDiasHead", "isvDiasActual", "isvDiasAnterior");
        pintarDias(inf.diario_cobranza, "isvDiasCobHead", "isvDiasCobActual", "isvDiasCobAnterior");
        dibujarVentas(inf.diario);
        dibujarCobranzas(inf.diario_cobranza);
        dibujarCartera(car);
        aplicarTituloPdf(inf);
        setTimeout(function () {
            ajustarGraficos();
        }, 50);
    }

    function slugTitulo(txt) {
        return String(txt || "")
            .replace(/[\\/:*?"<>|]/g, "-")
            .replace(/\s+/g, " ")
            .trim();
    }

    function aplicarTituloPdf(inf) {
        var semana = (inf && inf.periodo && inf.periodo.semana_iso)
            ? inf.periodo.semana_iso
            : ($("isvSemana") ? $("isvSemana").value : "");
        var codigo = "";
        var nombre = "";
        if (inf && inf.vendedor) {
            codigo = inf.vendedor.codigo || "";
            nombre = inf.vendedor.nombre || "";
        } else if ($("isvVendedor")) {
            var sel = $("isvVendedor");
            codigo = sel.value || "";
            if (sel.selectedIndex >= 0) {
                var txt = sel.options[sel.selectedIndex].text || "";
                var sep = txt.indexOf("—");
                nombre = sep >= 0 ? txt.slice(sep + 1).replace(/^\s+/, "") : txt;
            }
        }
        var partes = ["Informe semanal"];
        if (semana) {
            partes.push(semana);
        }
        if (codigo) {
            partes.push(codigo);
        }
        if (nombre) {
            partes.push(nombre);
        }
        document.title = slugTitulo(partes.join(" "));
    }

    function restaurarTitulo() {
        document.title = tituloOriginal;
    }

    function pintarDias(diario, idHead, idActual, idAnterior) {
        var labels = (diario && diario.labels) ? diario.labels : [];
        var actual = (diario && diario.actual) ? diario.actual : [];
        var anterior = (diario && diario.anterior) ? diario.anterior : [];
        var head = "<th></th>";
        var rowA = "<th>Esta semana</th>";
        var rowB = "<th>Semana ant.</th>";
        var i;
        for (i = 0; i < labels.length; i++) {
            head += "<th>" + labels[i] + "</th>";
            rowA += "<td class='isv-num'>" + fmtSoles(actual[i] || 0) + "</td>";
            rowB += "<td class='isv-num'>" + fmtSoles(anterior[i] || 0) + "</td>";
        }
        if ($(idHead)) {
            $(idHead).innerHTML = head;
        }
        if ($(idActual)) {
            $(idActual).innerHTML = rowA;
        }
        if ($(idAnterior)) {
            $(idAnterior).innerHTML = rowB;
        }
    }

    function dibujarVentas(diario) {
        var canvas = $("isvChartVentas");
        if (!canvas || typeof Chart === "undefined") {
            return;
        }
        if (chartVentas) {
            chartVentas.destroy();
        }
        chartVentas = new Chart(canvas.getContext("2d"), {
            type: "line",
            data: {
                labels: diario.labels,
                datasets: [
                    {
                        label: "Semana actual",
                        data: diario.actual,
                        borderColor: "#7eb8d4",
                        backgroundColor: "rgba(168, 208, 230, 0.35)",
                        borderWidth: 2.5,
                        tension: 0.25,
                        pointRadius: 4,
                        pointBackgroundColor: "#7eb8d4",
                        fill: true
                    },
                    {
                        label: "Semana anterior",
                        data: diario.anterior,
                        borderColor: "#c4b5d4",
                        backgroundColor: "rgba(196, 181, 212, 0.18)",
                        borderDash: [6, 4],
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 3,
                        pointBackgroundColor: "#c4b5d4",
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: { padding: { top: 14 } },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: "12%",
                        ticks: {
                            font: { size: 10 },
                            callback: function (v) { return fmtMiles(v); }
                        }
                    },
                    x: { ticks: { font: { size: 10 } } }
                }
            },
            plugins: [pluginEtiquetas]
        });
    }

    function dibujarCobranzas(diario) {
        var canvas = $("isvChartCobranzas");
        if (!canvas || typeof Chart === "undefined" || !diario) {
            return;
        }
        if (chartCobranzas) {
            chartCobranzas.destroy();
        }
        chartCobranzas = new Chart(canvas.getContext("2d"), {
            type: "line",
            data: {
                labels: diario.labels,
                datasets: [
                    {
                        label: "Semana actual",
                        data: diario.actual,
                        borderColor: "#7ec8b8",
                        backgroundColor: "rgba(168, 216, 196, 0.35)",
                        borderWidth: 2.5,
                        tension: 0.25,
                        pointRadius: 4,
                        pointBackgroundColor: "#7ec8b8",
                        fill: true
                    },
                    {
                        label: "Semana anterior",
                        data: diario.anterior,
                        borderColor: "#c5d4a8",
                        backgroundColor: "rgba(197, 212, 168, 0.18)",
                        borderDash: [6, 4],
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 3,
                        pointBackgroundColor: "#c5d4a8",
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: { padding: { top: 14 } },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: "12%",
                        ticks: {
                            font: { size: 10 },
                            callback: function (v) { return fmtMiles(v); }
                        }
                    },
                    x: { ticks: { font: { size: 10 } } }
                }
            },
            plugins: [pluginEtiquetas]
        });
    }

    function dibujarCartera(car) {
        var canvas = $("isvChartCartera");
        if (!canvas || typeof Chart === "undefined") {
            return;
        }
        if (chartCartera) {
            chartCartera.destroy();
        }
        var tramos = car.tramos || [];
        chartCartera = new Chart(canvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: tramos.map(function (t) { return t.label; }),
                datasets: [{
                    data: tramos.map(function (t) { return t.monto; }),
                    backgroundColor: tramos.map(function (t) { return t.color; }),
                    borderWidth: 2,
                    borderColor: "#f7fafc"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                cutout: "62%",
                plugins: { legend: { display: false } }
            }
        });
    }

    function guardarFiltrosEnUrl() {
        var params = new URLSearchParams(window.location.search);
        var path = window.location.pathname || "";
        if (/index\.php$/i.test(path) || params.has("ruta")) {
            params.set("ruta", "informe-semanal-vendedor");
        }
        params.set("semana", $("isvSemana").value);
        params.set("vendedor", $("isvVendedor").value);
        var qs = params.toString();
        var next = path + (qs ? "?" + qs : "") + (window.location.hash || "");
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, "", next);
        }
    }

    function cargar() {
        var estado = $("isvEstado");
        var hoja = $("isvHoja");
        estado.hidden = false;
        estado.className = "alert alert-info isv-no-print";
        estado.textContent = "Cargando informe…";
        hoja.hidden = true;

        guardarFiltrosEnUrl();

        var url = "ajax/informe-semanal-vendedor.ajax.php?accion=informe"
            + "&semana=" + encodeURIComponent($("isvSemana").value)
            + "&vendedor=" + encodeURIComponent($("isvVendedor").value);

        fetch(url, { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.ok) {
                    estado.className = "alert alert-warning isv-no-print";
                    estado.textContent = json.msg || "No se pudo cargar el informe.";
                    return;
                }
                estado.hidden = true;
                hoja.hidden = false;
                pintar(json.informe);
            })
            .catch(function () {
                estado.className = "alert alert-danger isv-no-print";
                estado.textContent = "Error de comunicación. Intente de nuevo.";
            });
    }

    function ajustarGraficos() {
        if (chartVentas) {
            chartVentas.resize();
        }
        if (chartCobranzas) {
            chartCobranzas.resize();
        }
        if (chartCartera) {
            chartCartera.resize();
        }
    }

    $("isvFormFiltros").addEventListener("submit", function (e) {
        e.preventDefault();
        cargar();
    });
    $("isvBtnImprimir").addEventListener("click", function () {
        document.body.classList.add("isv-printing");
        aplicarTituloPdf();
        ajustarGraficos();
        setTimeout(function () {
            window.print();
        }, 200);
    });
    window.addEventListener("beforeprint", function () {
        document.body.classList.add("isv-printing");
        aplicarTituloPdf();
        ajustarGraficos();
    });
    window.addEventListener("afterprint", function () {
        document.body.classList.remove("isv-printing");
        restaurarTitulo();
        ajustarGraficos();
    });

    if ($("isvVendedor").value) {
        cargar();
    } else {
        $("isvEstado").textContent = "Elija vendedor y semana.";
    }
})();
