$(document).ready(function () {

    $("#clienteInteligencia").on("changed.bs.select", function () {
        var cliente = $(this).val();

        if (cliente === "" || cliente === null) {
            window.location = "index.php?ruta=inteligencia-comercial";
            return;
        }

        window.location = "index.php?ruta=inteligencia-comercial&cliente=" + encodeURIComponent(cliente);
    });

    if (typeof Chart === "undefined") {
        return;
    }

    var factoresPorMotor = {};

    $("[id^='icMotor'][id$='Data']").each(function () {
        var motorData = JSON.parse(this.textContent);
        var motorNum = motorData.motor;
        var factoresPorClave = {};

        $.each(motorData.factores, function (clave, f) {
            factoresPorClave[f.clave || clave] = f;
        });

        factoresPorMotor[motorNum] = factoresPorClave;

        var $legend = $(".ic-chart-legend[data-motor='" + motorNum + "']");
        var chartScoreId = $legend.closest(".box-body").find(".ic-gauge-wrap canvas").attr("id");
        var chartAportId = $legend.closest(".box-body").find(".ic-aportacion-chart canvas").attr("id");

        icInitChartRiesgo(motorData.score, chartScoreId);
        icInitChartAportacion(factoresPorClave, chartAportId, $legend);
    });

    icInitModales(factoresPorMotor);

});

var IC_COLORES_PASTEL = [
    "#F4A6A6", "#FFD4A3", "#A8CCE8", "#C5B4E3",
    "#B5E0C8", "#F7C5D8", "#D5E8A4", "#E8D4B8"
];

function icColorPastel(index) {
    return IC_COLORES_PASTEL[index % IC_COLORES_PASTEL.length];
}

function icColorScore(score) {
    if (score >= 80) return "#00a65a";
    if (score >= 70) return "#3c8dbc";
    if (score >= 60) return "#f39c12";
    return "#dd4b39";
}

function icFmtScore(val) {
    return parseFloat(val).toFixed(1);
}

function icFmtAportacion(val) {
    return parseFloat(val).toFixed(2);
}

function icInitChartRiesgo(scoreFinal, canvasId) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var score = parseFloat(scoreFinal);
    var color = icColorScore(score);
    var restante = Math.max(0, 100 - score);

    new Chart(ctx.getContext("2d")).Doughnut(
        [
            { value: score, color: color, highlight: color, label: "Score " + icFmtScore(score) },
            { value: restante, color: "#ecf0f5", highlight: "#e0e5ea", label: "" }
        ],
        {
            responsive: false,
            segmentShowStroke: false,
            percentageInnerCutout: 78,
            animation: true,
            animationSteps: 60,
            showTooltips: false
        }
    );
}

function icInitChartAportacion(factoresPorClave, canvasId, $legend) {
    var segmentos = [];
    var claves = [];

    $legend.find(".ic-legend-item").each(function (i) {
        var clave = $(this).data("factor");
        var f = factoresPorClave[clave];
        if (!f) return;

        var color = $(this).data("color") || icColorPastel(i);

        claves.push(clave);
        segmentos.push({
            value: parseFloat(f.aportacion),
            color: color,
            highlight: color,
            label: f.nombre
        });
    });

    var ctxAport = document.getElementById(canvasId);
    if (!ctxAport || !segmentos.length) return;

    var chartAport = new Chart(ctxAport.getContext("2d")).Doughnut(segmentos, {
        responsive: false,
        segmentShowStroke: true,
        segmentStrokeColor: "#fff",
        segmentStrokeWidth: 2,
        percentageInnerCutout: 50,
        animation: true,
        tooltipTemplate: "<%if (label){%><%=label%>: +<%= value.toFixed(2) %> pts<%}%>"
    });

    $(ctxAport).css("cursor", "pointer").on("click", function (evt) {
        var active = chartAport.getSegmentsAtEvent(evt);
        if (!active || !active.length) return;

        var idx = typeof active[0]._index !== "undefined" ? active[0]._index : 0;
        var clave = claves[idx];

        if (clave && factoresPorClave[clave]) {
            icAbrirModalFactor(factoresPorClave[clave]);
        }
    });
}

function icAbrirModalFactor(f) {
    if (!f) return;

    var score = parseFloat(f.score);
    var color = icColorScore(score);
    var aportacion = icFmtAportacion(f.aportacion);

    $("#icModalIcon").attr("class", "fa " + f.icono);
    $("#icModalTitulo").text(f.nombre);
    $("#icModalScore").text(icFmtScore(score)).css("color", color);
    $("#icModalScoreIcon").css({ background: color + "22", color: color });
    $("#icModalScoreBar").css({ width: Math.min(100, score) + "%", background: color });
    $("#icModalPeso").text(f.peso + "%");
    $("#icModalAportacion").text("+" + aportacion);
    $("#icModalAportacionCalc").text(icFmtScore(score) + " × " + f.peso + "% = " + aportacion);
    $("#icModalDetalle").html("<i class=\"fa fa-info-circle\"></i> " + f.detalle);
    $("#icModalFormula").text(f.formula);
    $("#icModalRegla").text(f.regla);

    icRenderTablaLogica(f.tabla_logica);
    icRenderPeriodosBox(f.periodos_box);

    var htmlValores = "";
    if (f.valores && f.valores.length) {
        $.each(f.valores, function (i, v) {
            htmlValores += '<div class="col-sm-6 col-xs-6">';
            htmlValores += '<div class="ic-modal-dato">';
            htmlValores += '<span class="ic-modal-dato-val">' + v.valor + '</span>';
            htmlValores += '<span class="ic-modal-dato-lbl">' + v.etiqueta + '</span>';
            htmlValores += '</div></div>';
        });
    }
    $("#icModalValores").html(htmlValores);

    $("#modalDetalleFactor").modal("show");
}

function icRenderTablaLogica(tabla) {
    var $wrap = $("#icModalLogicaWrap");

    if (!tabla || !tabla.filas || !tabla.filas.length) {
        $wrap.hide();
        return;
    }

    $("#icModalLogicaTitulo").text(tabla.titulo || "Reglas de puntuación");

    if (tabla.intro) {
        $("#icModalLogicaIntro").text(tabla.intro).show();
    } else {
        $("#icModalLogicaIntro").hide();
    }

    var columnas = tabla.columnas || ["Situación", "Condición", "Score"];
    var headHtml = "";
    $.each(columnas, function (i, col) {
        headHtml += "<th>" + col + "</th>";
    });
    $("#icModalLogicaHead").html(headHtml);

    var bodyHtml = "";
    $.each(tabla.filas, function (i, fila) {
        var clases = [];
        if (fila.aplica) clases.push("ic-logica-aplica");
        if (fila.es_resultado) clases.push("ic-logica-resultado");

        var badge = fila.aplica
            ? '<span class="ic-modal-logica-badge">' + (fila.es_resultado ? "Su caso" : "Aplica") + "</span>"
            : "";

        bodyHtml += '<tr class="' + clases.join(" ") + '">';
        bodyHtml += "<td>" + fila.situacion + badge + "</td>";
        bodyHtml += "<td>" + fila.condicion + "</td>";
        bodyHtml += "<td>" + fila.score + "</td>";
        bodyHtml += "</tr>";
    });
    $("#icModalLogicaBody").html(bodyHtml);
    $wrap.show();
}

function icRenderPeriodosBox(periodos) {
    var $wrap = $("#icModalPeriodosWrap");

    if (!periodos || !periodos.items || !periodos.items.length) {
        $wrap.hide();
        return;
    }

    $("#icModalPeriodosTitulo").text(periodos.titulo || "Periodos de comparación");

    var html = "";
    $.each(periodos.items, function (i, item) {
        html += "<li>";
        html += '<span class="ic-periodo-etq">' + item.etiqueta + "</span>";
        html += '<span class="ic-periodo-rango">' + item.rango + "</span>";
        html += "</li>";
    });
    $("#icModalPeriodosList").html(html);
    $wrap.show();
}

function icInitModales(factoresPorMotor) {
    $(".ic-legend-item").on("click", function () {
        var motorNum = $(this).closest(".ic-chart-legend").data("motor");
        var clave = $(this).data("factor");
        var mapa = factoresPorMotor[motorNum];

        if (mapa && mapa[clave]) {
            icAbrirModalFactor(mapa[clave]);
        }
    });
}
