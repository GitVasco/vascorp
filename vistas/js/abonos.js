$(".tablaAbonos").DataTable({
    ajax:
        "ajax/cuentas-corrientes/tabla-abonos.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "DESC"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
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
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});
/*=============================================
EDITAR ABONO
=============================================*/
$(".tablaAbonos").on("click", ".btnEditarAbono", function () {
    var idAbono = $(this).attr("idAbono");
    var datos = new FormData();
    datos.append("idAbono", idAbono);

    $.ajax({
        url: "ajax/abonos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idAbono").val(respuesta["id"]);
            $("#editarFecha").val(respuesta["fecha"]);
            $("#editarDescripcion").val(respuesta["descripcion"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarAgencia").val(respuesta["agencia"]);
            $("#editarOpe").val(respuesta["num_ope"]);
        },
    });
});

/*=============================================
ELIMINAR ABONO
=============================================*/
$(".tablaAbonos").on("click", ".btnEliminarAbono", function () {
    var idAbono = $(this).attr("idAbono");

    swal({
        title: "¿Está seguro de borrar el abono?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar abono!",
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=abonos&idAbono=" + idAbono;
        }
    });
});
//Reporte de Colores
// $(".box").on("click", ".btnReporteColor", function () {
//     window.location = "vistas/reportes_excel/rpt_color.php";

// })
/*
 * Remover localstorage con btn Actualizar
 */
$("#btnRecargar").click(function () {
    localStorage.removeItem("saldo");
    localStorage.clear();
    $(".chkAbono").prop("checked", false);
});
/*
 * validar el checkbox
 */
$(".tablaAbonosCancelar").on("click", ".chkAbono", function () {
    $(".tablaCuentasCancelar").DataTable().destroy();
    var saldo = $(this).attr("saldo");
    var idAbono = $(this).attr("idAbono");
    var fecAbono = $(this).attr("fecAbono");
    var opAbono = $(this).attr("opAbono");
    $(".btnCancelarAbono").attr("idAbono", idAbono);
    $(".btnCancelarAbono").attr("opAbono", opAbono);
    localStorage.setItem("saldo", saldo);
    localStorage.setItem("fecAbono", fecAbono);
    localStorage.setItem("opAbono", opAbono);
    cargarTablaCuentasCancelar(localStorage.getItem("saldo"));
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("saldo") != null) {
    cargarTablaCuentasCancelar(localStorage.getItem("saldo"));
    // console.log("lleno");
} else {
    cargarTablaCuentasCancelar(null);
    // console.log("vacio");
}

$(".tablaAbonosCancelar").DataTable({
    ajax:
        "ajax/cuentas-corrientes/tabla-abonos-cancelar.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "desc"]],
    pageLength: 20,
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, "Todos"],
    ],
    language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
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
            sSortAscending:
                ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
                ": Activar para ordenar la columna de manera descendente",
        },
    },
});

function cargarTablaCuentasCancelar(saldo) {
    $(".tablaCuentasCancelar").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas-cancelar.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&saldo=" +
            saldo,
        deferRender: true,
        retrieve: true,
        processing: true,
        ordering: false,
        pageLength: 20,
        lengthMenu: [
            [20, 40, 60, -1],
            [20, 40, 60, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
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
                sSortAscending:
                    ": Activar para ordenar la columna de manera ascendente",
                sSortDescending:
                    ": Activar para ordenar la columna de manera descendente",
            },
        },
    });
}
/*
 * validar el checkbox
 */
$(".tablaCuentasCancelar").on("click", ".chkCancelar", function () {
    var idCuenta = $(this).attr("idCuenta");
    $(".btnCancelarAbono").attr("idCuenta", idCuenta);
});

/*
 * CONFIRMAR CANCELACIÓN DE ABONO
 */
$(".btnCancelarAbono").click(function () {
    console.log("click");
    var idCuenta = $(this).attr("idCuenta");

    if (!idCuenta) {
        //toastr.error("No hay idCuenta para cancelar el abono");
        Command: toastr["error"]("No hay selección para cancelar el abono");
        return;
    }

    var idAbono = $(this).attr("idAbono");
    var opAbono = $(this).attr("opAbono");
    console.log("🚀 ~ opAbono:", opAbono);

    if (!idAbono) {
        console.log("🚀 ~ idAbono:", idAbono);
        //toastr.error("No hay idAbono para cancelar el abono");
        Command: toastr["error"]("No hay selección para cancelar el abono");
        return;
    }

    // Get the account information
    var datos = new FormData();
    datos.append("idCuenta", idCuenta);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // Display the account information in the edit form
            $("#idCuenta4").val(respuesta["id"]);
            $("#editarTipo").val(respuesta["tipo_doc"]);
            $("#editarCuenta").val(respuesta["num_cta"]);
            $("#editarVendedor").val(respuesta["vendedor"]);
            $("#editarCliente").val(respuesta["cliente"]);
            $("#editarSaldo").val(respuesta["saldo"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarFecha").val(localStorage.getItem("fecAbono"));
            $("#fechaVen").val(respuesta["fecha_ven"]);
            $("#editarAbono").val(localStorage.getItem("saldo"));
            $("#idAbono").val(idAbono);
            $("#opAbono").val(opAbono);
        },
    });
});
