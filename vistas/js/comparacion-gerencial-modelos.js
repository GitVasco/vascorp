var comparacionGraficos = [];
var comparacionColores = ["#3c8dbc", "#f39c12", "#00a65a", "#dd4b39"];
var comparacionRellenos = ["rgba(60,141,188,.10)", "rgba(243,156,18,.10)", "rgba(0,166,90,.10)", "rgba(221,75,57,.10)"];

function comparacionEscapar(valor) {
    return $("<div>").text(valor === null || valor === undefined ? "" : String(valor)).html();
}

function comparacionNumero(valor, decimales) {
    if (valor === null || valor === undefined || valor === "") {
        return "—";
    }
    var numero = Number(valor);
    if (!isFinite(numero)) {
        return "—";
    }
    return numero.toLocaleString("es-PE", {
        minimumFractionDigits: decimales || 0,
        maximumFractionDigits: decimales || 0
    });
}

function comparacionMoneda(valor) {
    return valor === null || valor === undefined ? "—" : "S/ " + comparacionNumero(valor, 2);
}

function comparacionRanking(posicion, total) {
    if (!posicion || !total) {
        return "<span class='comparacion-ranking sin-dato'><i class='fa fa-minus'></i> Sin ranking</span>";
    }
    var proporcion = Number(posicion) / Number(total);
    var clase = proporcion <= 0.2 ? "alto" : (proporcion <= 0.5 ? "medio" : "bajo");
    var icono = clase === "alto" ? "fa-check" : (clase === "medio" ? "fa-circle" : "fa-times");
    return "<span class='comparacion-ranking " + clase + "'><i class='fa " + icono + "'></i> # "
        + comparacionNumero(posicion, 0) + " de " + comparacionNumero(total, 0) + "</span>";
}

function comparacionClaseInventario(datos, indice) {
    var disponibles = datos.map(function (item, posicion) {
        return { posicion: posicion, valor: item.rotacion === null ? null : Number(item.rotacion) };
    }).filter(function (item) {
        return item.valor !== null && isFinite(item.valor);
    }).sort(function (a, b) {
        return b.valor - a.valor;
    });
    var lugar = disponibles.map(function (item) { return item.posicion; }).indexOf(indice);
    if (lugar === -1) {
        return "sin-dato";
    }
    if (lugar === 0) {
        return "alto";
    }
    return lugar === disponibles.length - 1 ? "bajo" : "medio";
}

function comparacionRenderCabeceras(datos) {
    var claseGrid = datos.length === 2 ? "modelos-2" : (datos.length === 4 ? "modelos-4" : "modelos-3");
    $(".comparacion-modelos-grid").removeClass("modelos-2 modelos-3 modelos-4").addClass(claseGrid);
    $("#comparacionModelosCabeceras").html(datos.map(function (item, indice) {
        var claseInventario = comparacionClaseInventario(datos, indice);
        var iconoInventario = claseInventario === "alto" ? "fa-check" : (claseInventario === "medio" ? "fa-circle" : (claseInventario === "bajo" ? "fa-times" : "fa-minus"));
        var enlace = "index.php?ruta=ficha-gerencial-modelos&modelo=" + encodeURIComponent(item.modelo)
            + "&anio=" + encodeURIComponent(window.comparacionModelosConfig.anio)
            + "&mes=" + encodeURIComponent(window.comparacionModelosConfig.mes);
        return "<div class='comparacion-modelo-cabecera' style='border-top-color:" + comparacionColores[indice] + "'>"
            + "<span class='comparacion-modelo-indice' style='background:" + comparacionColores[indice] + "'>" + (indice + 1) + "</span>"
            + "<h3>" + comparacionEscapar(item.modelo) + "</h3>"
            + "<strong>" + comparacionEscapar(item.nombre) + "</strong>"
            + "<small>" + comparacionEscapar(item.marca) + (item.grupo ? " · " + comparacionEscapar(item.grupo) : "") + "</small>"
            + "<div class='comparacion-cabecera-rankings'>"
            + comparacionRanking(item.ranking, item.ranking_total)
            + comparacionRanking(item.ranking_utilidad, item.ranking_utilidad_total)
            + "</div>"
            + "<div class='comparacion-inventario-estado " + claseInventario + "'><i class='fa " + iconoInventario + "'></i> Rotación relativa</div>"
            + "<a class='btn btn-xs btn-default' href='" + comparacionEscapar(enlace) + "'>Ver ficha completa</a>"
            + "</div>";
    }).join(""));
}

function comparacionRenderIndicadores(datos) {
    $("#comparacionModelosIndicadores").html(datos.map(function (item, indice) {
        var costo = item.costo_anio && item.costo_mes
            ? ("0" + item.costo_mes).slice(-2) + "/" + item.costo_anio
            : "Pendiente";
        var variacion = item.variacion_interanual_pct === null
            ? "Sin base"
            : (Number(item.variacion_interanual_pct) > 0 ? "+" : "") + comparacionNumero(item.variacion_interanual_pct, 1) + "%";
        return "<div class='comparacion-indicadores-card' style='border-top-color:" + comparacionColores[indice] + "'>"
            + "<h4>" + comparacionEscapar(item.modelo) + "</h4>"
            + "<div><span><i class='fa fa-money'></i> Ventas</span><strong>" + comparacionMoneda(item.ventas_acumuladas) + "</strong></div>"
            + "<div><span><i class='fa fa-cubes'></i> Unidades vendidas</span><strong>" + comparacionNumero(item.unidades_vendidas, 0) + "</strong></div>"
            + "<div><span><i class='fa fa-line-chart'></i> Utilidad</span><strong>" + comparacionMoneda(item.utilidad) + "</strong></div>"
            + "<div><span><i class='fa fa-percent'></i> Margen</span><strong>" + (item.margen_pct === null ? "—" : comparacionNumero(item.margen_pct, 1) + "%") + "</strong></div>"
            + "<div><span><i class='fa fa-archive'></i> Stock disponible</span><strong>" + comparacionNumero(item.stock_disponible, 0) + "</strong></div>"
            + "<div><span><i class='fa fa-refresh'></i> Rotación</span><strong>" + comparacionNumero(item.rotacion, 2) + "</strong></div>"
            + "<div><span><i class='fa fa-calendar'></i> Días inventario</span><strong>" + comparacionNumero(item.dias_inventario, 0) + "</strong></div>"
            + "<div><span><i class='fa fa-exchange'></i> Variación interanual</span><strong>" + variacion + "</strong></div>"
            + "<div><span><i class='fa fa-calculator'></i> Costo utilizado</span><strong>" + costo + "</strong></div>"
            + "</div>";
    }).join(""));
}

function comparacionCrearGrafico(id, tipo, datos, opciones) {
    var elemento = document.getElementById(id);
    if (!elemento) {
        return;
    }
    var grafico = new Chart(elemento.getContext("2d"))[tipo](datos, $.extend({
        responsive: true,
        maintainAspectRatio: false,
        datasetFill: false,
        scaleBeginAtZero: true
    }, opciones || {}));
    comparacionGraficos.push(grafico);
}

function comparacionNormalizarMetrica(datos, campo) {
    var validos = datos.map(function (item) {
        return item[campo] === null || item[campo] === undefined ? null : Number(item[campo]);
    }).filter(function (valor) {
        return valor !== null && isFinite(valor);
    });
    if (!validos.length) {
        return datos.map(function () { return 0; });
    }
    var maximo = Math.max.apply(Math, validos);
    var minimo = Math.min.apply(Math, validos);
    return datos.map(function (item) {
        if (item[campo] === null || item[campo] === undefined || !isFinite(Number(item[campo]))) {
            return 0;
        }
        if (maximo > 0) {
            return Math.max(0, Math.min(100, Math.round(Number(item[campo]) * 100 / maximo)));
        }
        if (maximo === minimo) {
            return 100;
        }
        return Math.round((Number(item[campo]) - minimo) * 100 / (maximo - minimo));
    });
}

function comparacionNormalizarTendencia(datos) {
    return datos.map(function (item) {
        var valor = Number(item.variacion_interanual_pct);
        valor = Math.max(-100, Math.min(100, valor));
        return Math.round(50 + valor / 2);
    });
}

function comparacionRenderGraficos(datos, periodo) {
    comparacionGraficos.forEach(function (grafico) {
        if (grafico && typeof grafico.destroy === "function") {
            grafico.destroy();
        }
    });
    comparacionGraficos = [];
    var etiquetas = datos.map(function (item) { return item.modelo; });
    var leyendaModelos = datos.map(function (item, indice) {
        return "<span><i style='background:" + comparacionColores[indice] + "'></i> "
            + comparacionEscapar(item.modelo) + "</span>";
    }).join("");
    $("#comparacionModelosLeyenda, #comparacionRadarLeyenda").html(leyendaModelos);
    var metricasRadar = [
        { etiqueta: "Ventas", campo: "ventas_acumuladas" },
        { etiqueta: "Unidades", campo: "unidades_vendidas" },
        { etiqueta: "Utilidad", campo: "utilidad" },
        { etiqueta: "Margen", campo: "margen_pct" },
        { etiqueta: "Rotación", campo: "rotacion" }
    ];
    var tendenciaCompleta = datos.every(function (item) {
        return item.variacion_interanual_pct !== null && item.variacion_interanual_pct !== undefined;
    });
    if (tendenciaCompleta) {
        metricasRadar.push({ etiqueta: "Tendencia", campo: "variacion_interanual_pct", tendencia: true });
    }
    var valoresRadar = {};
    metricasRadar.forEach(function (metrica) {
        valoresRadar[metrica.campo] = metrica.tendencia
            ? comparacionNormalizarTendencia(datos)
            : comparacionNormalizarMetrica(datos, metrica.campo);
    });
    $("#comparacionRadarDescripcion").text(
        "Ventas, unidades, utilidad, margen y rotación muestran qué porcentaje representa cada modelo respecto al mejor valor seleccionado."
        + (tendenciaCompleta
            ? " En tendencia, 50 representa estabilidad; menos de 50 indica caída y más de 50 crecimiento."
            : " La tendencia se omitió porque al menos un modelo no tiene base interanual comparable.")
    );
    comparacionCrearGrafico("graficoComparacionRadar", "Radar", {
        labels: metricasRadar.map(function (metrica) { return metrica.etiqueta; }),
        datasets: datos.map(function (item, indice) {
            return {
                label: item.modelo,
                fillColor: comparacionRellenos[indice],
                strokeColor: comparacionColores[indice],
                pointColor: comparacionColores[indice],
                pointStrokeColor: "#fff",
                data: metricasRadar.map(function (metrica) {
                    return valoresRadar[metrica.campo][indice];
                })
            };
        })
    }, {
        scaleOverride: true,
        scaleSteps: 5,
        scaleStepWidth: 20,
        scaleStartValue: 0,
        pointDotRadius: 3,
        angleLineColor: "rgba(120,130,140,.18)"
    });
    var meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"].slice(0, Number(periodo.mes));
    comparacionCrearGrafico("graficoComparacionEvolucion", "Line", {
        labels: meses,
        datasets: datos.map(function (item, indice) {
            return {
                label: item.modelo,
                fillColor: "rgba(255,255,255,0)",
                strokeColor: comparacionColores[indice],
                pointColor: comparacionColores[indice],
                data: (item.evolucion_unidades || []).slice(0, Number(periodo.mes)).map(Number)
            };
        })
    }, { bezierCurve: true, bezierCurveTension: 0.3, pointDotRadius: 3 });
    comparacionCrearGrafico("graficoComparacionRentabilidad", "Bar", {
        labels: etiquetas,
        datasets: [
            { label: "Ventas", fillColor: "rgba(60,141,188,.75)", data: datos.map(function (item) { return Number(item.ventas_acumuladas || 0); }) },
            { label: "Utilidad", fillColor: "rgba(0,166,90,.75)", data: datos.map(function (item) { return Number(item.utilidad || 0); }) }
        ]
    });
    comparacionCrearGrafico("graficoComparacionInventario", "Bar", {
        labels: etiquetas,
        datasets: [
            { label: "Unidades vendidas", fillColor: "rgba(243,156,18,.75)", data: datos.map(function (item) { return Number(item.unidades_vendidas || 0); }) },
            { label: "Stock disponible", fillColor: "rgba(96,92,168,.70)", data: datos.map(function (item) { return Number(item.stock_disponible || 0); }) }
        ]
    });
    comparacionCrearGrafico("graficoComparacionMargen", "Bar", {
        labels: etiquetas,
        datasets: [{ label: "Margen %", fillColor: "rgba(0,192,239,.75)", data: datos.map(function (item) { return Number(item.margen_pct || 0); }) }]
    });
    comparacionCrearGrafico("graficoComparacionRotacion", "Bar", {
        labels: etiquetas,
        datasets: [{ label: "Rotación", fillColor: "rgba(0,166,90,.70)", data: datos.map(function (item) { return Number(item.rotacion || 0); }) }]
    });
}

function comparacionMejoresIndices(datos, campo, menorEsMejor) {
    var validos = [];
    datos.forEach(function (item, indice) {
        if (item[campo] === null || item[campo] === undefined || !isFinite(Number(item[campo]))) {
            return;
        }
        validos.push({ indice: indice, valor: Number(item[campo]) });
    });
    if (validos.length < 2) {
        return [];
    }
    var mejorValor = validos[0].valor;
    validos.forEach(function (item) {
        mejorValor = menorEsMejor
            ? Math.min(mejorValor, item.valor)
            : Math.max(mejorValor, item.valor);
    });
    return validos.filter(function (item) {
        return Math.abs(item.valor - mejorValor) < 0.0001;
    }).map(function (item) {
        return item.indice;
    });
}

function comparacionRenderTabla(datos) {
    var metricas = [
        { etiqueta: "Ranking ventas", campo: "ranking", menor: true, formato: function (v, item) { return v ? "# " + v + " de " + item.ranking_total : "—"; } },
        { etiqueta: "Ranking utilidad", campo: "ranking_utilidad", menor: true, formato: function (v, item) { return v ? "# " + v + " de " + item.ranking_utilidad_total : "—"; } },
        { etiqueta: "Ventas acumuladas", campo: "ventas_acumuladas", formato: comparacionMoneda },
        { etiqueta: "Unidades vendidas", campo: "unidades_vendidas", formato: function (v) { return comparacionNumero(v, 0); } },
        { etiqueta: "Utilidad", campo: "utilidad", formato: comparacionMoneda },
        { etiqueta: "Margen", campo: "margen_pct", formato: function (v) { return v === null ? "—" : comparacionNumero(v, 1) + "%"; } },
        { etiqueta: "Stock disponible", campo: "stock_disponible", sinMejor: true, formato: function (v) { return comparacionNumero(v, 0); } },
        { etiqueta: "Rotación", campo: "rotacion", formato: function (v) { return comparacionNumero(v, 2); } },
        { etiqueta: "Días inventario", campo: "dias_inventario", sinMejor: true, formato: function (v) { return comparacionNumero(v, 0); } },
        { etiqueta: "Variación interanual", campo: "variacion_interanual_pct", formato: function (v) { return v === null ? "Sin base" : (Number(v) > 0 ? "+" : "") + comparacionNumero(v, 1) + "%"; } },
        { etiqueta: "Costo utilizado", campo: "costo_mes", sinMejor: true, formato: function (v, item) { return item.costo_anio && v ? ("0" + v).slice(-2) + "/" + item.costo_anio : "Pendiente"; } }
    ];
    var ganadores = datos.map(function () { return []; });
    var html = "<thead><tr><th>Indicador</th>" + datos.map(function (item) {
        return "<th>" + comparacionEscapar(item.modelo) + "</th>";
    }).join("") + "</tr></thead><tbody>";
    metricas.forEach(function (metrica) {
        var mejores = metrica.sinMejor ? [] : comparacionMejoresIndices(datos, metrica.campo, metrica.menor);
        mejores.forEach(function (indice) {
            ganadores[indice].push(metrica.etiqueta);
        });
        html += "<tr><th>" + metrica.etiqueta + "</th>" + datos.map(function (item, indice) {
            var ganador = mejores.indexOf(indice) !== -1;
            return "<td" + (ganador ? " class='comparacion-mejor'" : "") + ">"
                + (ganador ? "<i class='fa fa-trophy comparacion-trofeo'></i> " : "")
                + metrica.formato(item[metrica.campo], item) + "</td>";
        }).join("") + "</tr>";
    });
    $("#tablaComparacionModelos").html(html + "</tbody>");
    $("#comparacionModelosGanadores").html(datos.map(function (item, indice) {
        var indicadores = ganadores[indice];
        return "<div class='comparacion-ganador-card' style='border-top-color:" + comparacionColores[indice] + "'>"
            + "<strong>" + comparacionEscapar(item.modelo) + "</strong>"
            + "<span><i class='fa fa-trophy'></i> Ganador en " + indicadores.length
            + (indicadores.length === 1 ? " indicador" : " indicadores") + "</span>"
            + (indicadores.length
                ? "<div>" + indicadores.map(function (indicador) {
                    return "<small>" + comparacionEscapar(indicador) + "</small>";
                }).join("") + "</div>"
                : "<em>Sin indicadores ganadores</em>")
            + "</div>";
    }).join(""));
}

function comparacionCargar() {
    var config = window.comparacionModelosConfig || {};
    if (!config.modelos || config.modelos.length < 2 || config.modelos.length > 4) {
        $("#comparacionModelosEstado").removeClass("alert-info").addClass("alert-danger")
            .html("<i class='fa fa-warning'></i> Selecciona entre 2 y 4 modelos desde el resumen.");
        return;
    }
    $.ajax({
        url: "ajax/ficha-gerencial-modelos.ajax.php",
        method: "POST",
        dataType: "json",
        data: {
            accion: "comparacionModelos",
            modelos: config.modelos.join(","),
            anio: config.anio,
            mes: config.mes,
            id_grupo: config.id_grupo || ""
        }
    }).done(function (resp) {
        if (!resp || !resp.ok) {
            $("#comparacionModelosEstado").removeClass("alert-info").addClass("alert-danger")
                .html("<i class='fa fa-warning'></i> " + comparacionEscapar(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar la comparación"));
            return;
        }
        var nombresMes = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        $("#comparacionModelosPeriodo").text(nombresMes[Number(resp.periodo.mes) - 1] + " " + resp.periodo.anio);
        comparacionRenderCabeceras(resp.data || []);
        comparacionRenderIndicadores(resp.data || []);
        comparacionRenderTabla(resp.data || []);
        $("#comparacionModelosEstado").hide();
        $("#comparacionModelosContenido").show();
        window.setTimeout(function () {
            comparacionRenderGraficos(resp.data || [], resp.periodo || {});
        }, 0);
    }).fail(function () {
        $("#comparacionModelosEstado").removeClass("alert-info").addClass("alert-danger")
            .html("<i class='fa fa-warning'></i> No se pudo cargar la comparación");
    });
}

if ($("#comparacionModelosContenido").length) {
    comparacionCargar();
}
