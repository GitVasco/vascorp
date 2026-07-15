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
        mrSetValor($("#mrMetaMonto"), r.meta_monto);
        mrSetValor($("#mrComisionMontoPct"), r.comision_monto_pct);
        mrSetValor($("#mrComisionMontoFijo"), r.comision_monto_fijo);
        $("#mrCumplimientoMonto").val(r.cumplimiento_monto || "prorrata");
        mrSyncCamposMonto();
        mrSetValor($("#mrMetaClientes"), r.meta_clientes);
        mrSetValor($("#mrComisionClientes"), r.comision_clientes_fijo);
        $("#mrCumplimientoClientes").val(r.cumplimiento_clientes || "todo_nada");
        mrSetValor($("#mrMetaModelos"), r.meta_modelos);
        mrSetValor($("#mrComisionModelos"), r.comision_modelos_fijo);
        $("#mrCumplimientoModelos").val(r.cumplimiento_modelos || "todo_nada");
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
