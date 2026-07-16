var tablaMetasRetos = null;
var mrIncentivos = [];
var mrArticuloSeleccionado = null;
var mrModelosCache = null;

function mrAlerta(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function mrConfirmar(mensaje, onYes) {
    if (typeof swal === "function") {
        swal({
            title: "Confirmar",
            text: mensaje,
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Guardar igual",
            cancelButtonText: "Revisar"
        }, function (ok) {
            if (ok && typeof onYes === "function") {
                onYes();
            }
        });
        return;
    }
    if (window.confirm(mensaje) && typeof onYes === "function") {
        onYes();
    }
}

if ($(".tablaMetasRetos").length) {
    var anio = $(".tablaMetasRetos").data("anio");
    var mes = $(".tablaMetasRetos").data("mes");

    tablaMetasRetos = $(".tablaMetasRetos").DataTable({
        ajax: {
            url: "ajax/tabla-metas-retos.ajax.php",
            type: "POST",
            data: { anio: anio, mes: mes },
            dataSrc: function (json) {
                var total = json && json.total_pagar != null ? Number(json.total_pagar) : 0;
                $("#mrTotalPagarPeriodo").text(
                    "S/ " + total.toLocaleString("es-PE", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );
                return (json && json.data) ? json.data : [];
            }
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        ordering: false,
        pageLength: 25,
        autoWidth: false,
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible",
            sInfo: "Mostrando _START_ a _END_ de _TOTAL_",
            sInfoEmpty: "Mostrando 0 a 0 de 0",
            sInfoFiltered: "(filtrado de _MAX_)",
            sSearch: "Buscar:",
            oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" }
        },
        columnDefs: [
            { targets: -1, orderable: false, className: "text-center", width: "40px" },
            { targets: -2, className: "text-right", width: "90px" }
        ]
    });
}

function mrSetValor($el, valor) {
    if (valor === null || typeof valor === "undefined") {
        $el.val("");
        return;
    }
    $el.val(valor);
}

/** Cobranza: prorrata usa %; todo_nada usa fijo. */
function mrSyncCamposCobranza() {
    var modo = $("#mrCumplimientoCobranza").val() || "prorrata";
    if (modo === "prorrata") {
        $("#mrWrapComisionCobranzaPct").show();
        $("#mrWrapComisionCobranzaFijo").hide();
        $("#mrAyudaCobranza").html(
            "Misma fuente que Resumen de gestión, <strong>sin IGV</strong>. "
            + "<strong>Prorrata:</strong> comisión = % × cobranza neta del período (sin tope por meta)."
        );
    } else {
        $("#mrWrapComisionCobranzaPct").hide();
        $("#mrWrapComisionCobranzaFijo").show();
        $("#mrAyudaCobranza").html(
            "Misma fuente que Resumen de gestión, <strong>sin IGV</strong>. "
            + "<strong>Todo o nada:</strong> comisión fija solo si la cobranza neta alcanza la meta."
        );
    }
}

$(document).on("change", "#mrCumplimientoCobranza", mrSyncCamposCobranza);

function mrBaseComisionActual() {
    return $(".tablaMetasRetos").attr("data-base-comision") || "cobranza";
}

/** Monto: prorrata usa %; todo_nada usa fijo. */
function mrSyncCamposMonto() {
    if (mrBaseComisionActual() !== "ventas") {
        return;
    }
    var modo = $("#mrCumplimientoMonto").val() || "prorrata";
    if (modo === "prorrata") {
        $("#mrWrapComisionMontoPct").show();
        $("#mrWrapComisionMontoFijo").hide();
        $("#mrAyudaMonto").html(
            "<strong>Prorrata:</strong> comisión = % × monto vendido (venta real del período)."
        );
    } else {
        $("#mrWrapComisionMontoPct").hide();
        $("#mrWrapComisionMontoFijo").show();
        $("#mrAyudaMonto").html(
            "<strong>Todo o nada:</strong> comisión fija solo si la venta real alcanza la meta."
        );
    }
}

$(document).on("change", "#mrCumplimientoMonto", mrSyncCamposMonto);

$(document).on("change", "input[name='mrBaseComision']", function () {
    var base = $(this).val();
    if (base !== "cobranza" && base !== "ventas") {
        return;
    }
    var $radios = $("input[name='mrBaseComision']");
    $radios.prop("disabled", true);
    $.post("ajax/metas-retos.ajax.php", {
        accion: "guardarBaseComision",
        base_comision: base
    }, function (resp) {
        if (resp && resp.ok) {
            window.location.reload();
            return;
        }
        $radios.prop("disabled", false);
        mrAlerta("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cambiar la base");
        // revertir radio
        $radios.filter("[value='" + mrBaseComisionActual() + "']").prop("checked", true);
    }, "json").fail(function () {
        $radios.prop("disabled", false);
        mrAlerta("error", "Error de comunicación");
        $radios.filter("[value='" + mrBaseComisionActual() + "']").prop("checked", true);
    });
});


var mrUniversoModelos = 0;

function mrSyncModoMetaModelos() {
    var modo = $("#mrMetaModelosModo").val() || "cantidad";
    if (modo === "porcentaje") {
        $("#mrWrapMetaModelosCantidad").hide();
        $("#mrWrapMetaModelosPct").show();
        $("#mrMetaModelos").prop("disabled", true);
        $("#mrMetaModelosPct").prop("disabled", false);
    } else {
        $("#mrWrapMetaModelosCantidad").show();
        $("#mrWrapMetaModelosPct").hide();
        $("#mrMetaModelos").prop("disabled", false);
        $("#mrMetaModelosPct").prop("disabled", true);
    }
    mrActualizarPreviewMetaModelos();
}

function mrActualizarPreviewMetaModelos() {
    $("#mrUniversoModelosTxt").text(mrUniversoModelos > 0 ? String(mrUniversoModelos) : "0");
    var modo = $("#mrMetaModelosModo").val() || "cantidad";
    if (modo !== "porcentaje") {
        return;
    }
    var pct = parseFloat($("#mrMetaModelosPct").val());
    if (isNaN(pct) || pct < 0) {
        $("#mrMetaModelosPctPreview").text("Equivale a — modelos");
        return;
    }
    var meta = 0;
    if (pct > 0 && mrUniversoModelos > 0) {
        meta = Math.ceil(mrUniversoModelos * (pct / 100));
        if (meta < 1) {
            meta = 1;
        }
    }
    $("#mrMetaModelosPctPreview").text("Equivale a " + meta + " modelos");
    $("#mrMetaModelos").val(meta > 0 ? meta : "");
}

$(document).on("change", "#mrMetaModelosModo", mrSyncModoMetaModelos);
$(document).on("input change", "#mrMetaModelosPct", mrActualizarPreviewMetaModelos);

/* ========== Incentivos por producto ========== */

function mrTipoLabel(tipo) {
    if (tipo === "modelo_color") {
        return "Modelo + color";
    }
    if (tipo === "articulo") {
        return "Artículo";
    }
    return "Modelo";
}

function mrObjetivoLabel(inc) {
    if (inc.tipo_objetivo === "modelo") {
        return String(inc.modelo || "");
    }
    if (inc.tipo_objetivo === "modelo_color") {
        var color = inc.nombre_color || inc.cod_color || "";
        return String(inc.modelo || "") + " · " + color;
    }
    return String(inc.articulo || "");
}

function mrSyncIncentivosJson() {
    $("#mrIncentivosJson").val(JSON.stringify(mrIncentivos));
}

function mrRenderIncentivos() {
    var $body = $("#mrIncentivosBody");
    if (!$body.length) {
        return;
    }
    $body.empty();
    if (!mrIncentivos.length) {
        $body.append('<tr class="mr-inc-empty"><td colspan="7" class="text-muted text-center">Sin incentivos</td></tr>');
        mrSyncIncentivosJson();
        return;
    }
    mrIncentivos.forEach(function (inc, idx) {
        var metaTxt = Number(inc.meta_cantidad || 0).toLocaleString("es-PE", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        var pctTxt = Number(inc.comision_pct || 0).toLocaleString("es-PE", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        var cumpl = inc.cumplimiento === "prorrata" ? "Prorrata" : "Todo o nada";
        var unidad = inc.unidad_meta === "unidades" ? "Unidades" : "Docenas";
        var tr = "<tr>"
            + "<td>" + mrTipoLabel(inc.tipo_objetivo) + "</td>"
            + "<td><strong>" + $("<div/>").text(mrObjetivoLabel(inc)).html() + "</strong></td>"
            + "<td>" + unidad + "</td>"
            + "<td>" + metaTxt + "</td>"
            + "<td>" + pctTxt + " %</td>"
            + "<td>" + cumpl + "</td>"
            + "<td class='text-center'><button type='button' class='btn btn-xs btn-danger btnMrQuitarIncentivo' data-idx='"
            + idx + "' title='Quitar'><i class='fa fa-trash'></i></button></td>"
            + "</tr>";
        $body.append(tr);
    });
    mrSyncIncentivosJson();
}

function mrCargarModelosInc(done) {
    var $sel = $("#mrIncModelo");
    if (!$sel.length) {
        if (typeof done === "function") {
            done();
        }
        return;
    }
    function pintar(lista) {
        $sel.empty().append('<option value="">— Modelo —</option>');
        (lista || []).forEach(function (m) {
            var cod = String(m.modelo || "").trim();
            if (!cod) {
                return;
            }
            var etiqueta = cod;
            if (m.nombre && String(m.nombre) !== cod) {
                etiqueta += " — " + m.nombre;
            }
            $sel.append($("<option/>").val(cod).text(etiqueta));
        });
        if (typeof $sel.selectpicker === "function") {
            $sel.selectpicker("refresh");
        }
        if (typeof done === "function") {
            done();
        }
    }
    if (mrModelosCache) {
        pintar(mrModelosCache);
        return;
    }
    $.post("ajax/metas-retos.ajax.php", { accion: "listarModelos", q: "" }, function (resp) {
        mrModelosCache = (resp && resp.data) ? resp.data : [];
        pintar(mrModelosCache);
    }, "json").fail(function () {
        pintar([]);
    });
}

var mrColoresReqId = 0;

function mrCargarColoresModelo(modelo, selected) {
    var $sel = $("#mrIncColor");
    var reqId = ++mrColoresReqId;
    $sel.empty().append('<option value="">— Color —</option>');
    modelo = String(modelo || "").trim();
    if (!modelo) {
        return;
    }
    $sel.prop("disabled", true);
    $.post("ajax/metas-retos.ajax.php", { accion: "listarColoresModelo", modelo: modelo }, function (resp) {
        if (reqId !== mrColoresReqId) {
            return;
        }
        $sel.empty().append('<option value="">— Color —</option>');
        var lista = (resp && resp.data) ? resp.data : [];
        var vistos = {};
        lista.forEach(function (c) {
            var cod = String(c.cod_color || "").trim();
            if (!cod || vistos[cod]) {
                return;
            }
            vistos[cod] = true;
            var nom = c.nombre_color ? String(c.nombre_color).trim() : cod;
            var $opt = $("<option/>").val(cod).text(cod + " — " + nom);
            if (selected && String(selected) === cod) {
                $opt.prop("selected", true);
            }
            $sel.append($opt);
        });
        $sel.prop("disabled", false);
    }, "json").fail(function () {
        if (reqId === mrColoresReqId) {
            $sel.prop("disabled", false);
        }
    });
}

function mrSyncIncFormTipo() {
    var tipo = $("#mrIncTipo").val() || "modelo";
    $("#mrIncWrapModelo").toggle(tipo !== "articulo");
    $("#mrIncWrapColor").toggle(tipo === "modelo_color");
    $("#mrIncWrapArticulo").toggle(tipo === "articulo");
    if (tipo === "articulo") {
        $("#mrIncUnidad").val("unidades");
    } else if (!$("#mrIncUnidad").val()) {
        $("#mrIncUnidad").val("docenas");
    }
}

function mrResetIncForm() {
    $("#mrIncTipo").val("modelo");
    $("#mrIncModelo").val("");
    if (typeof $("#mrIncModelo").selectpicker === "function") {
        $("#mrIncModelo").selectpicker("val", "");
        $("#mrIncModelo").selectpicker("refresh");
    }
    $("#mrIncColor").empty().append('<option value="">— Color —</option>');
    $("#mrIncArticulo").val("");
    $("#mrIncArticuloInfo").text("");
    $("#mrIncArticuloList").empty();
    mrArticuloSeleccionado = null;
    $("#mrIncUnidad").val("docenas");
    $("#mrIncMeta").val("");
    $("#mrIncPct").val("");
    $("#mrIncCumplimiento").val("todo_nada");
    mrSyncIncFormTipo();
}

function mrMostrarIncForm(show) {
    if (show) {
        mrResetIncForm();
        mrCargarModelosInc(function () {
            $("#mrIncForm").slideDown(120);
        });
    } else {
        $("#mrIncForm").slideUp(120);
    }
}

$(document).on("click", "#mrBtnAgregarIncentivo", function () {
    mrMostrarIncForm(true);
});

$(document).on("click", "#mrIncCancelar", function () {
    mrMostrarIncForm(false);
});

$(document).on("change", "#mrIncTipo", function () {
    mrSyncIncFormTipo();
    var tipo = $("#mrIncTipo").val() || "modelo";
    if (tipo === "modelo_color") {
        mrCargarColoresModelo($("#mrIncModelo").val() || "", "");
    }
});

// selectpicker dispara change + changed.bs.select: se deduplica con mrColoresReqId
var mrModeloColorTimer = null;
function mrOnModeloIncChange() {
    if ($("#mrIncTipo").val() !== "modelo_color") {
        return;
    }
    var modelo = $("#mrIncModelo").val() || "";
    clearTimeout(mrModeloColorTimer);
    mrModeloColorTimer = setTimeout(function () {
        mrCargarColoresModelo(modelo, "");
    }, 50);
}
$(document).on("changed.bs.select", "#mrIncModelo", mrOnModeloIncChange);
$(document).on("change", "#mrIncModelo", mrOnModeloIncChange);

var mrArtBuscarTimer = null;
$(document).on("input", "#mrIncArticulo", function () {
    var q = String($(this).val() || "").trim();
    mrArticuloSeleccionado = null;
    $("#mrIncArticuloInfo").text("");
    clearTimeout(mrArtBuscarTimer);
    if (q.length < 2) {
        $("#mrIncArticuloList").empty();
        return;
    }
    mrArtBuscarTimer = setTimeout(function () {
        $.post("ajax/metas-retos.ajax.php", { accion: "buscarArticulos", q: q }, function (resp) {
            var $dl = $("#mrIncArticuloList").empty();
            var lista = (resp && resp.data) ? resp.data : [];
            lista.forEach(function (a) {
                var cod = String(a.articulo || "").trim();
                if (!cod) {
                    return;
                }
                var label = cod + " · " + (a.modelo || "") + " · " + (a.nombre_color || a.cod_color || "")
                    + (a.marca ? (" · " + a.marca) : "");
                $dl.append($("<option/>").attr("value", cod).text(label));
            });
            // Si coincide exacto, fijar selección
            var exact = lista.filter(function (a) {
                return String(a.articulo || "").trim() === q;
            })[0];
            if (exact) {
                mrArticuloSeleccionado = exact;
                $("#mrIncArticuloInfo").text(
                    "Modelo " + (exact.modelo || "—")
                    + " · Color " + (exact.nombre_color || exact.cod_color || "—")
                    + (exact.marca ? (" · " + exact.marca) : "")
                );
            }
        }, "json");
    }, 280);
});

$(document).on("change blur", "#mrIncArticulo", function () {
    var q = String($(this).val() || "").trim();
    if (!q) {
        return;
    }
    $.post("ajax/metas-retos.ajax.php", { accion: "buscarArticulos", q: q }, function (resp) {
        var lista = (resp && resp.data) ? resp.data : [];
        var exact = lista.filter(function (a) {
            return String(a.articulo || "").trim() === q;
        })[0];
        if (exact) {
            mrArticuloSeleccionado = exact;
            $("#mrIncArticuloInfo").text(
                "Modelo " + (exact.modelo || "—")
                + " · Color " + (exact.nombre_color || exact.cod_color || "—")
                + (exact.marca ? (" · " + exact.marca) : "")
            );
        }
    }, "json");
});

$(document).on("click", "#mrIncConfirmar", function () {
    var tipo = $("#mrIncTipo").val() || "modelo";
    var unidad = $("#mrIncUnidad").val() === "unidades" ? "unidades" : "docenas";
    var meta = parseFloat($("#mrIncMeta").val());
    var pct = parseFloat($("#mrIncPct").val());
    var cumpl = $("#mrIncCumplimiento").val() === "prorrata" ? "prorrata" : "todo_nada";

    if (isNaN(meta) || meta <= 0) {
        mrAlerta("error", "Indicá una meta mayor a 0");
        return;
    }
    if (isNaN(pct) || pct < 0 || pct > 100) {
        mrAlerta("error", "Comisión entre 0 y 100");
        return;
    }

    var row = {
        tipo_objetivo: tipo,
        modelo: null,
        cod_color: null,
        nombre_color: null,
        articulo: null,
        unidad_meta: unidad,
        meta_cantidad: meta,
        comision_pct: pct,
        cumplimiento: cumpl
    };

    if (tipo === "modelo" || tipo === "modelo_color") {
        var modelo = String($("#mrIncModelo").val() || "").trim();
        if (!modelo) {
            mrAlerta("error", "Elegí un modelo");
            return;
        }
        row.modelo = modelo;
        if (tipo === "modelo_color") {
            var color = String($("#mrIncColor").val() || "").trim();
            if (!color) {
                mrAlerta("error", "Elegí un color");
                return;
            }
            row.cod_color = color;
            row.nombre_color = $("#mrIncColor option:selected").text().replace(/^[^—]+—\s*/, "") || color;
        }
    } else {
        var art = String($("#mrIncArticulo").val() || "").trim();
        if (!art) {
            mrAlerta("error", "Indicá el artículo");
            return;
        }
        if (!mrArticuloSeleccionado || String(mrArticuloSeleccionado.articulo) !== art) {
            mrAlerta("error", "Seleccioná un artículo válido del catálogo");
            return;
        }
        row.articulo = art;
    }

    var clave = [row.tipo_objetivo, row.modelo || "", row.cod_color || "", row.articulo || "", row.unidad_meta].join("|").toLowerCase();
    var dup = mrIncentivos.some(function (inc) {
        var k = [inc.tipo_objetivo, inc.modelo || "", inc.cod_color || "", inc.articulo || "", inc.unidad_meta].join("|").toLowerCase();
        return k === clave;
    });
    if (dup) {
        mrAlerta("error", "Ese objetivo ya está en la lista");
        return;
    }

    mrIncentivos.push(row);
    mrRenderIncentivos();
    mrMostrarIncForm(false);
});

$(document).on("click", ".btnMrQuitarIncentivo", function () {
    var idx = parseInt($(this).attr("data-idx"), 10);
    if (isNaN(idx) || idx < 0) {
        return;
    }
    mrIncentivos.splice(idx, 1);
    mrRenderIncentivos();
});

function mrEnviarGuardar(forzar) {
    $("#mrForzarSuperpuestos").val(forzar ? "1" : "0");
    mrSyncIncentivosJson();
    var data = $("#formMetasRetos").serializeArray();
    data.push({ name: "accion", value: "guardar" });

    $.post("ajax/metas-retos.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalMetasRetos").modal("hide");
            if (tablaMetasRetos) {
                tablaMetasRetos.ajax.reload(null, false);
            }
            mrAlerta("success", resp.mensaje || "Guardado");
            return;
        }
        if (resp && resp.requiere_confirmacion) {
            var detalle = (resp.superpuestos && resp.superpuestos.length)
                ? "\n\n• " + resp.superpuestos.join("\n• ")
                : "";
            mrConfirmar((resp.mensaje || "Hay objetivos superpuestos.") + detalle, function () {
                mrEnviarGuardar(true);
            });
            return;
        }
        mrAlerta("error", (resp && resp.mensaje) ? resp.mensaje : "Error al guardar");
    }, "json").fail(function () {
        mrAlerta("error", "Error de comunicación");
    });
}

$(document).on("click", ".btnEditarMetasRetos", function () {
    var cod = $(this).attr("codVendedor");
    var nombre = $(this).attr("nombreVendedor") || "";
    var anio = $("#mrAnio").val() || $(".tablaMetasRetos").data("anio");
    var mes = $("#mrMes").val() || $(".tablaMetasRetos").data("mes");

    $("#mrCodVendedor").val(cod);
    $("#mrTituloVendedor").text(cod + " — " + nombre);
    $("#mrAnio").val(anio);
    $("#mrMes").val(mes);
    $("#mrForzarSuperpuestos").val("0");
    mrMostrarIncForm(false);

    $.post("ajax/metas-retos.ajax.php", {
        accion: "detalle",
        cod_vendedor: cod,
        anio: anio,
        mes: mes
    }, function (resp) {
        var r = (resp && resp.reto) ? resp.reto : {};
        mrUniversoModelos = (resp && resp.universo_modelos != null) ? Number(resp.universo_modelos) : 0;

        mrSetValor($("#mrMetaCobranza"), r.meta_cobranza);
        mrSetValor($("#mrComisionCobranzaPct"), r.comision_cobranza_pct);
        mrSetValor($("#mrComisionCobranzaFijo"), r.comision_cobranza_fijo);
        $("#mrCumplimientoCobranza").val(r.cumplimiento_cobranza || "prorrata");
        mrSyncCamposCobranza();

        mrSetValor($("#mrMetaMonto"), r.meta_monto);
        mrSetValor($("#mrComisionMontoPct"), r.comision_monto_pct);
        mrSetValor($("#mrComisionMontoFijo"), r.comision_monto_fijo);
        $("#mrCumplimientoMonto").val(r.cumplimiento_monto || "prorrata");
        mrSyncCamposMonto();
        mrSetValor($("#mrMetaClientes"), r.meta_clientes);
        mrSetValor($("#mrComisionClientes"), r.comision_clientes_fijo);
        $("#mrCumplimientoClientes").val(r.cumplimiento_clientes || "todo_nada");
        $("#mrMetaModelosModo").val(r.meta_modelos_modo === "porcentaje" ? "porcentaje" : "cantidad");
        mrSetValor($("#mrMetaModelos"), r.meta_modelos);
        mrSetValor($("#mrMetaModelosPct"), r.meta_modelos_pct);
        mrSetValor($("#mrComisionModelos"), r.comision_modelos_fijo);
        $("#mrCumplimientoModelos").val(r.cumplimiento_modelos || "todo_nada");
        mrSyncModoMetaModelos();

        mrIncentivos = [];
        var lista = (resp && resp.incentivos) ? resp.incentivos : [];
        lista.forEach(function (inc) {
            mrIncentivos.push({
                tipo_objetivo: inc.tipo_objetivo,
                modelo: inc.modelo || null,
                cod_color: inc.cod_color || null,
                nombre_color: inc.nombre_color || null,
                articulo: inc.articulo || null,
                unidad_meta: inc.unidad_meta === "unidades" ? "unidades" : "docenas",
                meta_cantidad: parseFloat(inc.meta_cantidad) || 0,
                comision_pct: parseFloat(inc.comision_pct) || 0,
                cumplimiento: inc.cumplimiento === "prorrata" ? "prorrata" : "todo_nada"
            });
        });
        mrRenderIncentivos();
        $("#modalMetasRetos").modal("show");
    }, "json");
});

$("#formMetasRetos").on("submit", function (e) {
    e.preventDefault();
    mrEnviarGuardar(false);
});

function mrFmtSoles(n) {
    return "S/ " + Number(n || 0).toLocaleString("es-PE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

var mrCoberturaCargada = false;

function mrRenderDetalleMarcas(marcas) {
    if (!marcas || !marcas.length) {
        return "<span class='text-muted'>Sin detalle</span>";
    }
    var html = "<table class='table table-condensed mr-detalle-marca-table'><thead><tr>"
        + "<th>Marca</th><th class='text-right'>Permitida</th><th class='text-right'>Fuera</th><th class='text-right'>Sin marca</th>"
        + "</tr></thead><tbody>";
    marcas.forEach(function (m) {
        html += "<tr><td>" + (m.marca || "") + "</td>"
            + "<td class='text-right'>" + mrFmtSoles(m.permitida) + "</td>"
            + "<td class='text-right'>" + mrFmtSoles(m.fuera_cobertura) + "</td>"
            + "<td class='text-right'>" + mrFmtSoles(m.sin_marca) + "</td></tr>";
    });
    html += "</tbody></table>";
    return html;
}

function mrCargarConciliacionCobertura(force) {
    if (!$("#tablaConciliacionMarcas").length) {
        return;
    }
    if (mrCoberturaCargada && !force) {
        return;
    }
    var anio = $(".tablaMetasRetos").data("anio");
    var mes = $(".tablaMetasRetos").data("mes");
    var $tbody = $("#tablaConciliacionMarcas tbody");
    $tbody.html("<tr><td colspan='10' class='text-center text-muted'>Cargando…</td></tr>");

    $.post("ajax/metricas-comerciales.ajax.php", {
        accion: "conciliacionCobertura",
        anio: anio,
        mes: mes
    }, function (resp) {
        $tbody.empty();
        if (!resp || !resp.ok || !resp.data || !resp.data.length) {
            $tbody.append("<tr><td colspan='10' class='text-muted text-center'>Sin datos para el periodo</td></tr>");
            return;
        }
        resp.data.forEach(function (row) {
            var dif = Number(row.diferencia_oficial || 0);
            var difCls = Math.abs(dif) < 0.01 ? "mr-cob-dif-zero" : "mr-cob-dif-pos";
            var detalleId = "mrDetMarca_" + String(row.cod_vendedor).replace(/[^a-zA-Z0-9]/g, "_");
            var tr = "<tr>"
                + "<td>" + row.cod_vendedor + " — " + (row.nombre_vendedor || "") + "</td>"
                + "<td class='text-right'>" + mrFmtSoles(row.venta_cabecera) + "</td>"
                + "<td class='text-right mr-cob-permitida'>" + mrFmtSoles(row.venta_permitida) + "</td>"
                + "<td class='text-right'>" + mrFmtSoles(row.permitida_lineas) + "</td>"
                + "<td class='text-right'>" + mrFmtSoles(row.nc_descuento) + "</td>"
                + "<td class='text-right mr-cob-fuera'>" + mrFmtSoles(row.venta_fuera_cobertura) + "</td>"
                + "<td class='text-right " + difCls + "'>" + mrFmtSoles(dif) + "</td>"
                + "<td class='text-center'>" + (row.clientes_nuevos_actual || 0) + "</td>"
                + "<td class='text-center'><strong>" + (row.clientes_nuevos_permitidos || 0) + "</strong></td>"
                + "<td class='text-center'><button type='button' class='btn btn-xs btn-info btnMrVerDetalleMarca' "
                + "codVendedor='" + row.cod_vendedor + "' targetId='" + detalleId + "'><i class='fa fa-tags'></i></button></td>"
                + "</tr>"
                + "<tr class='mr-detalle-marca-row' id='" + detalleId + "' style='display:none;'>"
                + "<td colspan='10'>" + mrRenderDetalleMarcas(row.detalle_marcas) + "</td></tr>";
            $tbody.append(tr);
        });
        mrCoberturaCargada = true;
    }, "json").fail(function () {
        $tbody.html("<tr><td colspan='10' class='text-danger text-center'>Error al cargar conciliación</td></tr>");
    });
}

$(document).on("click", ".btnMrVerDetalleMarca", function () {
    var targetId = $(this).attr("targetId");
    $("#" + targetId).toggle();
});

$("#mrBoxCoberturaMarcas").on("expanded.boxwidget", function () {
    mrCargarConciliacionCobertura(false);
});

$(document).on("submit", "form[action*='metas-retos'], form[method='get']", function () {
    mrCoberturaCargada = false;
});
