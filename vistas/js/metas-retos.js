var tablaMetasRetos = null;

function mrAlerta(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

if ($(".tablaMetasRetos").length) {
    var anio = $(".tablaMetasRetos").data("anio");
    var mes = $(".tablaMetasRetos").data("mes");

    tablaMetasRetos = $(".tablaMetasRetos").DataTable({
        ajax: {
            url: "ajax/tabla-metas-retos.ajax.php",
            type: "POST",
            data: { anio: anio, mes: mes }
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 25,
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
        }
    });
}

function mrSetValor($el, valor) {
    if (valor === null || typeof valor === "undefined") {
        $el.val("");
        return;
    }
    $el.val(valor);
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
        $("#mrCumplimientoMonto").val(r.cumplimiento_monto || "todo_nada");
        mrSetValor($("#mrMetaClientes"), r.meta_clientes);
        mrSetValor($("#mrComisionClientes"), r.comision_clientes_fijo);
        $("#mrCumplimientoClientes").val(r.cumplimiento_clientes || "todo_nada");
        mrSetValor($("#mrMetaModelos"), r.meta_modelos);
        mrSetValor($("#mrComisionModelos"), r.comision_modelos_fijo);
        $("#mrCumplimientoModelos").val(r.cumplimiento_modelos || "todo_nada");
        $("#modalMetasRetos").modal("show");
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
