/*=============================================
DESCUENTOS COMPUESTOS ESSO
=============================================*/

var montoActualModal = 0;

(function () {
    var $tabla = $(".tablaDescuentosCompuestos");

    if ($tabla.length === 0) {
        return;
    }

    var origen = $("#origenFiltroDescuento").val() || "";
    var cliente = $("#clienteFiltroDescuento").val() || "";

    $tabla.DataTable({
        "ajax": "ajax/descuentos-compuestos/tabla-descuentos-compuestos.ajax.php?origen=" + encodeURIComponent(origen) + "&cliente=" + encodeURIComponent(cliente),
        "deferRender": true,
        "retrieve": true,
        "processing": true,
        "order": [[1, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "Todos"]],
        "columnDefs": [
            { "orderable": false, "targets": [10] }
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });
})();

/*=============================================
FILTRO POR CLIENTE (recarga preservando el origen)
=============================================*/
$(document).on("change", "#clienteDescuento", function () {
    var cliente = $(this).val() || "";
    var origen = $("#origenFiltroDescuento").val() || "";

    var url = "index.php?ruta=descuentos-compuestos";

    if (origen !== "") {
        url += "&origen=" + encodeURIComponent(origen);
    }

    if (cliente !== "") {
        url += "&cliente=" + encodeURIComponent(cliente);
    }

    window.location = url;
});

/*=============================================
DESCOMPOSICIÓN EN CASCADA (1er % al total, 2do % a la diferencia)
=============================================*/
function descomponerDescuento(monto, pct1, pct2) {
    var p1 = pct1 / 100;
    var p2 = pct2 / 100;
    var factor = p1 + p2 * (1 - p1);

    if (factor <= 0 || monto <= 0) {
        return null;
    }

    var base = monto / factor;

    return {
        montoP1: base * p1,
        montoP2: base * p2 * (1 - p1)
    };
}

function parsearNotaEstandar(nota) {
    var match = /^DSCTO_([0-9]+(?:\.[0-9]+)?)_([0-9]+(?:\.[0-9]+)?)$/i.exec((nota || "").trim());

    if (!match) {
        return null;
    }

    var p1 = parseFloat(match[1]);
    var p2 = parseFloat(match[2]);

    if (p1 < 0 || p2 < 0 || p1 > 100 || p2 > 100) {
        return null;
    }

    return { pct1: p1, pct2: p2 };
}

function formatearMoneda(valor) {
    return valor.toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/*=============================================
PREVIEW EN EL MODAL
=============================================*/
$(document).on("input", "#correccionNotaEstandar", function () {
    var nota = $(this).val().toUpperCase();
    var $preview = $("#correccionPreview");
    var pct = parsearNotaEstandar(nota);

    if (pct === null) {
        $preview.removeClass("text-success").addClass("text-muted")
            .text("Formato inválido. Usa DSCTO_p1_p2 (ej. DSCTO_7_2).");
        return;
    }

    var desc = descomponerDescuento(montoActualModal, pct.pct1, pct.pct2);

    if (desc === null) {
        $preview.removeClass("text-success").addClass("text-muted")
            .text("No se puede calcular con este monto.");
        return;
    }

    $preview.removeClass("text-muted").addClass("text-success").html(
        "<b>" + pct.pct1 + "%</b> → S/ " + formatearMoneda(desc.montoP1) +
        " &nbsp;|&nbsp; <b>" + pct.pct2 + "%</b> → S/ " + formatearMoneda(desc.montoP2)
    );
});

/*=============================================
CONFIRMAR SUGERENCIA AUTOMÁTICA
=============================================*/
$(".tablaDescuentosCompuestos").on("click", ".btnConfirmarDescuento", function () {
    var id = $(this).attr("idDescuento");
    var notaPropuesta = $(this).attr("notaPropuesta");

    swal({
        title: "¿Confirmar sugerencia?",
        text: "Se guardará la nota " + notaPropuesta + " como oficial para el registro " + id + ".",
        type: "question",
        showCancelButton: true,
        confirmButtonColor: "#00a65a",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, confirmar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }

        var datos = new FormData();
        datos.append("accion", "confirmar");
        datos.append("id", id);

        $.ajax({
            url: "ajax/descuentos-compuestos/descuentos-compuestos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.ok) {
                    swal({
                        type: "success",
                        title: "Confirmado",
                        text: respuesta.mensaje,
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    swal({ type: "error", title: "Error", text: respuesta.mensaje });
                }
            }
        });
    });
});

/*=============================================
DESCARTAR (no requiere corrección, deja de listarse)
=============================================*/
$(".tablaDescuentosCompuestos").on("click", ".btnDescartarDescuento", function () {
    var id = $(this).attr("idDescuento");

    swal({
        title: "¿Descartar este registro?",
        text: "Dejará de aparecer en la lista. Podrás restaurarlo desde el filtro 'Descartados'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#777",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, descartar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }

        var datos = new FormData();
        datos.append("accion", "descartar");
        datos.append("id", id);

        $.ajax({
            url: "ajax/descuentos-compuestos/descuentos-compuestos.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.ok) {
                    swal({
                        type: "success",
                        title: "Descartado",
                        text: respuesta.mensaje,
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    swal({ type: "error", title: "Error", text: respuesta.mensaje });
                }
            }
        });
    });
});

/*=============================================
RESTAURAR UN REGISTRO DESCARTADO
=============================================*/
$(".tablaDescuentosCompuestos").on("click", ".btnRestaurarDescuento", function () {
    var id = $(this).attr("idDescuento");

    var datos = new FormData();
    datos.append("accion", "restaurar");
    datos.append("id", id);

    $.ajax({
        url: "ajax/descuentos-compuestos/descuentos-compuestos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta.ok) {
                swal({
                    type: "success",
                    title: "Restaurado",
                    text: respuesta.mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function () {
                    window.location.reload();
                });
            } else {
                swal({ type: "error", title: "Error", text: respuesta.mensaje });
            }
        }
    });
});

/*=============================================
ABRIR MODAL DE CORRECCIÓN
=============================================*/
$(".tablaDescuentosCompuestos").on("click", ".btnEditarDescuento", function () {
    var id = $(this).attr("idDescuento");

    var datos = new FormData();
    datos.append("accion", "obtener");
    datos.append("id", id);

    $.ajax({
        url: "ajax/descuentos-compuestos/descuentos-compuestos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (!respuesta.ok) {
                swal({ type: "error", title: "Error", text: respuesta.mensaje });
                return;
            }

            var r = respuesta.registro;
            montoActualModal = parseFloat(r.monto) || 0;

            $("#correccionId").val(r.id);
            $("#correccionNotaOriginal").text(r.notas_original);
            $("#correccionMonto").text(formatearMoneda(montoActualModal));
            $("#correccionSugerencia").text(r.nota_estandar_propuesta || "— (no se pudo leer)");

            var notaInicial = r.nota_estandar_manual || r.nota_estandar_propuesta || "";
            $("#correccionNotaEstandar").val(notaInicial);
            $("#correccionObservacion").val(r.observacion_manual || "");
            $("#correccionEstado").val(r.estado_correccion || "CONFIRMADO");

            $("#correccionNotaEstandar").trigger("input");
        }
    });
});

/*=============================================
GUARDAR CORRECCIÓN
=============================================*/
$("#formCorregirDescuento").on("submit", function (e) {
    e.preventDefault();

    var nota = $("#correccionNotaEstandar").val().toUpperCase().trim();

    if (parsearNotaEstandar(nota) === null) {
        swal({ type: "warning", title: "Formato inválido", text: "Usa DSCTO_p1_p2 (ej. DSCTO_7_2)." });
        return;
    }

    var datos = new FormData();
    datos.append("accion", "guardar");
    datos.append("id", $("#correccionId").val());
    datos.append("nota_estandar", nota);
    datos.append("observacion", $("#correccionObservacion").val());
    datos.append("estado", $("#correccionEstado").val());

    $.ajax({
        url: "ajax/descuentos-compuestos/descuentos-compuestos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta.ok) {
                $("#modalCorregirDescuento").modal("hide");
                swal({
                    type: "success",
                    title: "Guardado",
                    text: respuesta.mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function () {
                    window.location.reload();
                });
            } else {
                swal({ type: "error", title: "Error", text: respuesta.mensaje });
            }
        }
    });
});
