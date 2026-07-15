var tablaMetasRetos = null;
var mrModeloPendiente = "";

function mrAlerta(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function mrAplicarModeloSelect(valor) {
    var $sel = $("#mrModeloEspecial");
    if (!$sel.length) {
        return;
    }
    var actual = valor == null ? "" : String(valor).trim();
    mrModeloPendiente = actual;
    $sel.val(actual);
    $sel.find("option").prop("selected", false);
    if (actual === "") {
        $sel.find('option[value=""]').prop("selected", true);
    } else {
        var $opt = $sel.find("option").filter(function () {
            return String($(this).val()).trim() === actual;
        });
        if ($opt.length) {
            $opt.prop("selected", true);
        }
    }
    if (typeof $sel.selectpicker === "function") {
        $sel.selectpicker("refresh");
        $sel.selectpicker("val", actual);
    }
}

function mrCargarModelos(selected, done) {
    var $sel = $("#mrModeloEspecial");
    if (!$sel.length) {
        if (typeof done === "function") {
            done();
        }
        return;
    }

    var actual = selected == null ? "" : String(selected).trim();
    mrModeloPendiente = actual;

    $.post("ajax/metas-retos.ajax.php", { accion: "listarModelos", q: "" }, function (resp) {
        $sel.empty().append('<option value="">— Sin modelo —</option>');
        var lista = (resp && resp.data) ? resp.data : [];
        var encontrado = false;
        lista.forEach(function (m) {
            var cod = String(m.modelo || "").trim();
            if (!cod) {
                return;
            }
            if (cod === actual) {
                encontrado = true;
            }
            var etiqueta = cod;
            if (m.nombre && String(m.nombre) !== cod) {
                etiqueta += " — " + m.nombre;
            }
            $sel.append($("<option/>").val(cod).text(etiqueta));
        });
        if (actual && !encontrado) {
            $sel.append($("<option/>").val(actual).text(actual + " (configurado)"));
        }
        mrAplicarModeloSelect(actual);
        if (typeof done === "function") {
            done();
        }
    }, "json").fail(function () {
        if (typeof done === "function") {
            done();
        }
    });
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

/** Monto: prorrata usa %; todo_nada usa fijo. */
function mrSyncCamposMonto() {
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

$(document).on("click", ".btnEditarMetasRetos", function () {
    var cod = $(this).attr("codVendedor");
    var nombre = $(this).attr("nombreVendedor") || "";
    var anio = $("#mrAnio").val() || $(".tablaMetasRetos").data("anio");
    var mes = $("#mrMes").val() || $(".tablaMetasRetos").data("mes");

    $("#mrCodVendedor").val(cod);
    $("#mrTituloVendedor").text(cod + " — " + nombre);
    $("#mrAnio").val(anio);
    $("#mrMes").val(mes);

    $.post("ajax/metas-retos.ajax.php", {
        accion: "detalle",
        cod_vendedor: cod,
        anio: anio,
        mes: mes
    }, function (resp) {
        var r = (resp && resp.reto) ? resp.reto : {};
        mrUniversoModelos = (resp && resp.universo_modelos != null) ? Number(resp.universo_modelos) : 0;
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
        mrSetValor($("#mrMetaDocenasEsp"), r.meta_docenas_especial);
        mrSetValor($("#mrComisionModeloEspPct"), r.comision_modelo_esp_pct);
        $("#mrCumplimientoModeloEsp").val(r.cumplimiento_modelo_esp || "todo_nada");

        mrCargarModelos(r.modelo_especial || "", function () {
            $("#modalMetasRetos").modal("show");
        });
    }, "json");
});

$("#formMetasRetos").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "guardar" });

    $.post("ajax/metas-retos.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalMetasRetos").modal("hide");
            if (tablaMetasRetos) {
                tablaMetasRetos.ajax.reload(null, false);
            }
            mrAlerta("success", resp.mensaje || "Guardado");
        } else {
            mrAlerta("error", (resp && resp.mensaje) ? resp.mensaje : "Error al guardar");
        }
    }, "json").fail(function () {
        mrAlerta("error", "Error de comunicación");
    });
});

$("#modalMetasRetos").on("shown.bs.modal", function () {
    // selectpicker a veces resetea al pintar el modal: reaplicar elección guardada
    mrAplicarModeloSelect(mrModeloPendiente);
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
