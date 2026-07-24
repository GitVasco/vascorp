var sdMatriz = {
    series: [],
    marcas: [],
    vinculos: {},
    tipo: window.seriesDocumentosTipoActivo || "01",
    filtroMarca: ""
};

function sdToast(mensaje, esError) {
    var $t = $("#sdToastMsg");
    if (!$t.length) {
        $t = $("<div id='sdToastMsg' class='sd-toast'></div>").appendTo("body");
    }
    $t.text(mensaje).toggleClass("is-error", !!esError).addClass("is-on");
    clearTimeout(sdToast._timer);
    sdToast._timer = setTimeout(function () {
        $t.removeClass("is-on");
    }, 1800);
}

function sdEscape(texto) {
    return String(texto == null ? "" : texto)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function sdPadProximo(tipo, correlativo) {
    var pad = tipo === "09" ? 7 : 8;
    var n = Math.max(0, parseInt(correlativo, 10) || 0) + 1;
    var s = String(n);
    while (s.length < pad) {
        s = "0" + s;
    }
    return s;
}

function sdActualizarPreview(prefijo) {
    var serie = String($("#" + prefijo + "SerieDocumento").val() || "").trim().toUpperCase();
    var tipo = prefijo === "agregar"
        ? ($("#agregarTipoDocumentoSerie").val() || "")
        : ($("#editarTipoDocumentoSerie").val() || "");
    var $preview = $("#" + prefijo + "PreviewProximo");
    if (!serie) {
        $preview.text("—");
        return;
    }
    $preview.text(serie + "-" + sdPadProximo(tipo, $("#" + prefijo + "CorrelativoSerie").val()));
}

function sdKeyVinculo(idTalonario, tipo, idMarca) {
    return String(idTalonario) + "|" + String(tipo) + "|" + String(idMarca);
}

function sdSeriesDelTipo() {
    return (sdMatriz.series || []).filter(function (s) {
        return String(s.tipo_documento) === String(sdMatriz.tipo);
    });
}

function sdActualizarKpis() {
    var rows = sdSeriesDelTipo();
    var amarres = 0;
    var sin = 0;
    rows.forEach(function (s) {
        var count = 0;
        (sdMatriz.marcas || []).forEach(function (m) {
            if (sdMatriz.vinculos[sdKeyVinculo(s.id_talonario, s.tipo_documento, m.id)]) {
                count += 1;
            }
        });
        amarres += count;
        if (count === 0) {
            sin += 1;
        }
    });
    $("#sdKpiTotal").text(rows.length);
    $("#sdKpiAmarres").text(amarres);
    $("#sdKpiSinMarcas").text(sin);
}

function sdMarcaVisible(marca) {
    var q = String(sdMatriz.filtroMarca || "").toLowerCase().trim();
    if (!q) {
        return true;
    }
    return String(marca.marca || "").toLowerCase().indexOf(q) !== -1;
}

function sdRenderMatriz() {
    var rows = sdSeriesDelTipo();
    var marcas = sdMatriz.marcas || [];
    var $loading = $("#sdMatrixLoading").hide();
    var $empty = $("#sdMatrixEmpty");
    var $scroll = $("#sdMatrixScroll");
    var $table = $("#sdMatrixTable");
    var puede = !!window.seriesDocumentosPuedeEditar;

    sdActualizarKpis();

    if (!rows.length) {
        $scroll.hide();
        $empty.show();
        return;
    }

    $empty.hide();
    $scroll.show();

    var head = "<tr>"
        + "<th class='sd-sticky sd-col-serie'>Serie</th>"
        + "<th class='sd-sticky sd-col-corr'>Correlativo</th>"
        + "<th class='sd-sticky sd-col-prox'>Próximo</th>"
        + "<th class='sd-sticky sd-col-acc'></th>";

    marcas.forEach(function (m) {
        var hidden = sdMarcaVisible(m) ? "" : " is-hidden-col";
        head += "<th class='sd-th-marca" + hidden + "' title='" + sdEscape(m.marca) + "' data-marca-id='" + m.id + "'>"
            + sdEscape(m.marca) + "</th>";
    });
    head += "</tr>";
    $table.find("thead").html(head);

    var body = "";
    rows.forEach(function (s) {
        body += "<tr data-id='" + s.id_talonario + "' data-tipo='" + sdEscape(s.tipo_documento) + "'>"
            + "<td class='sd-sticky sd-col-serie'><span class='sd-cell-serie'>" + sdEscape(s.serie) + "</span></td>"
            + "<td class='sd-sticky sd-col-corr'><span class='sd-cell-corr'>" + sdEscape(s.correlativo) + "</span></td>"
            + "<td class='sd-sticky sd-col-prox'><span class='sd-cell-prox'>" + sdEscape(s.proximo) + "</span></td>"
            + "<td class='sd-sticky sd-col-acc sd-cell-acc'>"
            + "<button type='button' class='btn btn-xs btn-default sd-btn-num btnEditarSerieDocumento'"
            + " idTalonario='" + s.id_talonario + "' tipoDocumento='" + sdEscape(s.tipo_documento) + "'"
            + " title='Numeración'><i class='fa fa-pencil'></i></button>"
            + "</td>";

        marcas.forEach(function (m) {
            var key = sdKeyVinculo(s.id_talonario, s.tipo_documento, m.id);
            var on = !!sdMatriz.vinculos[key];
            var hidden = sdMarcaVisible(m) ? "" : " is-hidden-col";
            var disabled = puede ? "" : " disabled";
            body += "<td class='sd-check-cell" + (on ? " is-on" : "") + hidden + "' data-marca-id='" + m.id + "'>"
                + "<input type='checkbox' class='sd-check'"
                + " data-id='" + s.id_talonario + "'"
                + " data-tipo='" + sdEscape(s.tipo_documento) + "'"
                + " data-marca='" + m.id + "'"
                + (on ? " checked" : "") + disabled + ">"
                + "</td>";
        });
        body += "</tr>";
    });
    $table.find("tbody").html(body);
}

function sdCargarMatriz(callback) {
    $("#sdMatrixLoading").show();
    $("#sdMatrixScroll").hide();
    $("#sdMatrixEmpty").hide();
    $.post("ajax/series-documentos.ajax.php", { accion: "matriz" }, function (resp) {
        if (!resp || !resp.ok) {
            $("#sdMatrixLoading").text("No se pudo cargar la matriz");
            return;
        }
        sdMatriz.series = resp.series || [];
        sdMatriz.marcas = resp.marcas || [];
        sdMatriz.vinculos = resp.vinculos || {};
        sdRenderMatriz();
        if (typeof callback === "function") {
            callback();
        }
    }, "json").fail(function () {
        $("#sdMatrixLoading").text("Error de comunicación");
    });
}

if ($(".sd-page").length) {
    sdCargarMatriz();
}

$("#sdTabsTipo").on("click", ".sd-tab", function () {
    $("#sdTabsTipo .sd-tab").removeClass("is-active");
    $(this).addClass("is-active");
    sdMatriz.tipo = $(this).attr("data-tipo") || "";
    window.seriesDocumentosTipoActivo = sdMatriz.tipo;
    sdRenderMatriz();
});

$("#sdFiltrarMarcaCol").on("keyup", function () {
    sdMatriz.filtroMarca = this.value || "";
    sdRenderMatriz();
});

$("#btnRefrescarMatrizSerie").on("click", function () {
    sdCargarMatriz();
});

$(document).on("change", ".sd-check", function () {
    if (!window.seriesDocumentosPuedeEditar) {
        return;
    }
    var $chk = $(this);
    var $td = $chk.closest("td");
    var activo = $chk.is(":checked") ? 1 : 0;
    var id = $chk.attr("data-id");
    var tipo = $chk.attr("data-tipo");
    var idMarca = $chk.attr("data-marca");
    var key = sdKeyVinculo(id, tipo, idMarca);

    $td.addClass("is-saving");
    $chk.prop("disabled", true);

    $.post("ajax/series-documentos.ajax.php", {
        accion: "toggleMarca",
        id_talonario: id,
        tipo_documento: tipo,
        id_marca: idMarca,
        activo: activo
    }, function (resp) {
        $td.removeClass("is-saving");
        $chk.prop("disabled", false);
        if (!resp || !resp.ok) {
            $chk.prop("checked", !activo);
            sdToast((resp && resp.mensaje) ? resp.mensaje : "No se pudo guardar", true);
            return;
        }
        if (activo) {
            sdMatriz.vinculos[key] = true;
            $td.addClass("is-on");
        } else {
            delete sdMatriz.vinculos[key];
            $td.removeClass("is-on");
        }
        sdActualizarKpis();
        sdToast(resp.mensaje || "Guardado");
    }, "json").fail(function () {
        $td.removeClass("is-saving");
        $chk.prop("disabled", false);
        $chk.prop("checked", !activo);
        sdToast("Error de comunicación", true);
    });
});

$("#btnNuevaSerieDocumento").on("click", function () {
    $("#formAgregarSerieDocumento")[0].reset();
    $("#agregarTipoDocumentoSerie").val(sdMatriz.tipo || "01");
    sdActualizarPreview("agregar");
    $("#modalAgregarSerieDocumento").modal("show");
});

$(document).on("input change", "#agregarSerieDocumento, #agregarCorrelativoSerie, #agregarTipoDocumentoSerie", function () {
    sdActualizarPreview("agregar");
});

$(document).on("input change", "#editarSerieDocumento, #editarCorrelativoSerie", function () {
    sdActualizarPreview("editar");
});

$("#formAgregarSerieDocumento").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "crear" });
    var $btn = $(this).find("button[type='submit']").prop("disabled", true);
    $.post("ajax/series-documentos.ajax.php", $.param(data), function (resp) {
        $btn.prop("disabled", false);
        if (resp && resp.ok) {
            $("#modalAgregarSerieDocumento").modal("hide");
            var tipoNuevo = $("#agregarTipoDocumentoSerie").val();
            if (tipoNuevo) {
                sdMatriz.tipo = tipoNuevo;
                $("#sdTabsTipo .sd-tab").removeClass("is-active");
                $("#sdTabsTipo .sd-tab[data-tipo='" + tipoNuevo + "']").addClass("is-active");
            }
            sdCargarMatriz();
            sdToast(resp.mensaje || "Serie creada");
        } else {
            sdToast((resp && resp.mensaje) ? resp.mensaje : "Error al crear", true);
        }
    }, "json").fail(function () {
        $btn.prop("disabled", false);
        sdToast("Error de comunicación", true);
    });
});

$(document).on("click", ".btnEditarSerieDocumento", function () {
    var id = $(this).attr("idTalonario");
    var tipo = $(this).attr("tipoDocumento");
    $.post("ajax/series-documentos.ajax.php", {
        accion: "detalle",
        id_talonario: id,
        tipo_documento: tipo
    }, function (resp) {
        if (!resp || !resp.ok || !resp.data) {
            sdToast((resp && resp.mensaje) ? resp.mensaje : "No se pudo cargar", true);
            return;
        }
        var s = resp.data;
        $("#editarIdTalonarioSerie").val(s.id_talonario);
        $("#editarTipoDocumentoSerie").val(s.tipo_documento);
        $("#editarTipoEtiquetaSerie").val(s.tipo_etiqueta || s.tipo_documento);
        $("#editarSerieDocumento").val(s.serie || "");
        $("#editarCorrelativoSerie").val(s.correlativo != null ? s.correlativo : 0);
        sdActualizarPreview("editar");
        $("#modalEditarSerieDocumento").modal("show");
    }, "json");
});

$("#formEditarSerieDocumento").on("submit", function (e) {
    e.preventDefault();
    if (!window.seriesDocumentosPuedeEditar) {
        return;
    }
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "editar" });
    var $btn = $(this).find("button[type='submit']").prop("disabled", true);
    $.post("ajax/series-documentos.ajax.php", $.param(data), function (resp) {
        $btn.prop("disabled", false);
        if (resp && resp.ok) {
            $("#modalEditarSerieDocumento").modal("hide");
            sdCargarMatriz();
            sdToast(resp.mensaje || "Numeración actualizada");
        } else {
            sdToast((resp && resp.mensaje) ? resp.mensaje : "Error al editar", true);
        }
    }, "json").fail(function () {
        $btn.prop("disabled", false);
        sdToast("Error de comunicación", true);
    });
});
