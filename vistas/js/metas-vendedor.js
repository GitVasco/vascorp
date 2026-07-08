$(".tablaMetasVendedor").DataTable({
    ajax: {
        url: "ajax/metas-vendedor/tabla-metas-vendedor.ajax.php",
        data: function () {
            return {
                anio: $(".tablaMetasVendedor").data("anio"),
                mes: $(".tablaMetasVendedor").data("mes"),
            };
        },
    },
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "asc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No hay metas registradas para este período",
        sEmptyTable: "No hay metas registradas para este período",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        oAria: {
            sSortAscending: ": Activar para ordenar la columna de manera ascendente",
            sSortDescending: ": Activar para ordenar la columna de manera descendente",
        },
    },
});

$(".tablaMetasVendedor").on("click", ".btnEditarMeta", function () {
    var idMeta = $(this).attr("idMeta");
    var datos = new FormData();
    datos.append("idMeta", idMeta);

    $.ajax({
        url: "ajax/metas-vendedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idMeta").val(respuesta.id);
            $("#editarVendedorLabel").val(
                respuesta.cod_vendedor + " - " + (respuesta.nombre_vendedor || "")
            );
            $("#editarPeriodoLabel").val(respuesta.mes + "/" + respuesta.anio);
            $("#editarMetaVenta").val(parseFloat(respuesta.meta_venta || 0).toFixed(2));
            $("#editarMetaCobranza").val(
                respuesta.meta_cobranza === null || respuesta.meta_cobranza === ""
                    ? ""
                    : parseFloat(respuesta.meta_cobranza).toFixed(2)
            );
        },
    });
});

$(".tablaMetasVendedor").on("click", ".btnEliminarMeta", function () {
    var idMeta = $(this).attr("idMeta");
    var anio = $(".tablaMetasVendedor").data("anio");
    var mes = $(".tablaMetasVendedor").data("mes");

    swal({
        title: "¿Eliminar esta meta?",
        text: "Esta acción no se puede deshacer",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, eliminar",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=metas-vendedor&anio=" +
                anio +
                "&mes=" +
                mes +
                "&idMeta=" +
                idMeta;
        }
    });
});
