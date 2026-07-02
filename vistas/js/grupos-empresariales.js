if ($(".tablaGruposEmpresariales").length) {

$(".tablaGruposEmpresariales").DataTable({
    ajax: "ajax/facturacion/tabla-grupos-empresariales.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[1, "asc"]],
    pageLength: 20,
    lengthMenu: [[20, 40, 60, -1], [20, 40, 60, "Todos"]],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sSearch: "Buscar:",
        sLoadingRecords: "Cargando...",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior"
        }
    }
});

}

$(".tablaGruposEmpresariales").on("click", ".btnEditarGrupo", function () {

    var idGrupo = $(this).attr("idGrupo");
    var datos = new FormData();
    datos.append("idGrupo", idGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idGrupo").val(respuesta.id);
            $("#editarCodigoGrupo").val(respuesta.codigo);
            $("#editarNombreGrupo").val(respuesta.nombre);
            $("#editarDescripcionGrupo").val(respuesta.descripcion || "");
            $("#editarEstadoGrupo").val(respuesta.estado);
        }
    });
});

$(".tablaGruposEmpresariales").on("click", ".btnEliminarGrupo", function () {

    var idGrupo = $(this).attr("idGrupo");

    swal({
        title: "¿Está seguro de borrar el grupo?",
        text: "Solo se puede eliminar si no tiene clientes asignados.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, borrar grupo"
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=grupos-empresariales&idGrupo=" + idGrupo;
        }
    });
});

function cargarClientesGrupo(codigoGrupo) {

    var datos = new FormData();
    datos.append("codigoGrupo", codigoGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {

            var tbody = $("#tablaClientesGrupo tbody");
            tbody.empty();

            if (respuesta.clientes.length === 0) {
                tbody.append("<tr><td colspan='5' class='text-center text-muted'>No hay clientes en este grupo</td></tr>");
            } else {
                respuesta.clientes.forEach(function (cliente) {
                    tbody.append(
                        "<tr>" +
                        "<td>" + cliente.codigo + "</td>" +
                        "<td>" + cliente.nombre + "</td>" +
                        "<td>" + (cliente.documento || "") + "</td>" +
                        "<td>" + (cliente.telefono || "") + "</td>" +
                        "<td><button class='btn btn-xs btn-danger btnQuitarClienteGrupo' codigoCliente='" + cliente.codigo + "'><i class='fa fa-times'></i></button></td>" +
                        "</tr>"
                    );
                });
            }

            var select = $("#selectClienteAsignar");
            select.empty();
            select.append("<option value=''>Seleccionar cliente sin grupo...</option>");

            respuesta.disponibles.forEach(function (cliente) {
                select.append(
                    "<option value='" + cliente.codigo + "'>" + cliente.codigo + " - " + cliente.nombre + " (" + (cliente.documento || "sin doc") + ")</option>"
                );
            });
        }
    });
}

$(".tablaGruposEmpresariales").on("click", ".btnVerClientesGrupo", function () {

    var codigoGrupo = $(this).attr("codigoGrupo");
    var nombreGrupo = $(this).attr("nombreGrupo");

    $("#codigoGrupoActivo").val(codigoGrupo);
    $("#tituloGrupoClientes").text(nombreGrupo);
    cargarClientesGrupo(codigoGrupo);
});

$("#btnAsignarClienteGrupo").on("click", function () {

    var codigoCliente = $("#selectClienteAsignar").val();
    var codigoGrupo = $("#codigoGrupoActivo").val();

    if (!codigoCliente) {
        swal({ type: "warning", title: "Seleccione un cliente", showConfirmButton: true });
        return;
    }

    var datos = new FormData();
    datos.append("accion", "asignar");
    datos.append("codigoClienteGrupo", codigoCliente);
    datos.append("codigoGrupoAsignar", codigoGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta.status === "ok") {
                cargarClientesGrupo(codigoGrupo);
                $(".tablaGruposEmpresariales").DataTable().ajax.reload(null, false);
            } else {
                swal({ type: "error", title: "No se pudo asignar el cliente", showConfirmButton: true });
            }
        }
    });
});

$("#tablaClientesGrupo").on("click", ".btnQuitarClienteGrupo", function () {

    var codigoCliente = $(this).attr("codigoCliente");
    var codigoGrupo = $("#codigoGrupoActivo").val();

    swal({
        title: "¿Quitar cliente del grupo?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, quitar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) return;

        var datos = new FormData();
        datos.append("accion", "quitar");
        datos.append("codigoClienteQuitar", codigoCliente);

        $.ajax({
            url: "ajax/grupos-empresariales.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.status === "ok") {
                    cargarClientesGrupo(codigoGrupo);
                    $(".tablaGruposEmpresariales").DataTable().ajax.reload(null, false);
                }
            }
        });
    });
});
