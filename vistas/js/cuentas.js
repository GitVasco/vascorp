//cuentas  select ano
$("#selectAnoCuenta").change(function () {
    //ano de cuenta
    var ano = $(this).val();
    localStorage.setItem("ano", ano);
    $(".tablaCuentas").DataTable().destroy();
    cargarTablaCuentas(ano);
    $(".btnReporteCuentas").attr("ano", localStorage.getItem("ano"));
});

//cuentas pendientes select ano
$("#selectAnoCuentaP").change(function () {
    //ano de cuenta
    var anoP = $(this).val();
    localStorage.setItem("anoP", anoP);
    $(".tablaCuentasPendientes").DataTable().destroy();
    cargarTablaCuentasPendientes(anoP);
    $(".btnReporteCuentasPendientes").attr("ano", localStorage.getItem("anoP"));
});

//cuentas canceladas select ano
$("#selectAnoCuentaC").change(function () {
    //ano de cuenta
    var anoC = $(this).val();
    localStorage.setItem("anoC", anoC);
    $(".tablaCuentasAprobadas").DataTable().destroy();
    cargarTablaCuentasAprobadas(anoC);
    $(".btnReporteCuentasAprobadas").attr("ano", localStorage.getItem("anoC"));
});

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("ano") != null) {
    $("#selectAnoCuenta").val(localStorage.getItem("ano"));
    $("#selectAnoCuenta").selectpicker("refresh");
    cargarTablaCuentas(localStorage.getItem("ano"));
} else {
    cargarTablaCuentas(null);
}

//CUENTAS
function cargarTablaCuentas(ano) {
    $(".tablaCuentas").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&ano=" +
            ano,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[4, "desc"]],
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

//CUENTAS CON PAGINACIÓN SERVIDOR (OPTIMIZADO)
function cargarTablaCuentasPaginado(ano) {
    // Destruir tabla existente si existe
    if ($.fn.DataTable.isDataTable(".tablaCuentasPaginado")) {
        $(".tablaCuentasPaginado").DataTable().destroy();
    }

    var table = $(".tablaCuentasPaginado").DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 800, // Esperar 800ms después de que el usuario deje de escribir antes de buscar
        ajax: {
            url: "ajax/cuentas-corrientes/tabla-cuentas-paginado.ajax.php",
            type: "GET",
            data: function (d) {
                d.ano = ano;
                d.perfil = $("#perfilOculto").val();

                // Opción 1: Mínimo de caracteres para buscar (recomendado para tablas grandes)
                // Solo buscar si tiene al menos 3 caracteres o está vacío (para limpiar)
                var minChars = 3;
                if (
                    d.search.value.length > 0 &&
                    d.search.value.length < minChars
                ) {
                    // Cancelar la búsqueda si tiene menos del mínimo
                    // Esto evita búsquedas con 1 o 2 caracteres que pueden ser muy lentas
                    d.search.value = "";
                }
            },
        },
        order: [[4, "desc"]],
        pageLength: 20,
        lengthMenu: [
            [20, 40, 60, 100],
            [20, 40, 60, 100],
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

    // Implementación alternativa: Debounce manual con mínimo de caracteres
    // Descomentar este bloque si prefieres más control sobre la búsqueda
    /*
    var searchTimeout;
    var minSearchLength = 3; // Mínimo de caracteres para buscar
    
    $('.dataTables_filter input').on('keyup', function() {
        var searchValue = $(this).val();
        
        // Limpiar timeout anterior
        clearTimeout(searchTimeout);
        
        // Si está vacío o tiene mínimo de caracteres, buscar después del delay
        if (searchValue.length === 0 || searchValue.length >= minSearchLength) {
            searchTimeout = setTimeout(function() {
                table.search(searchValue).draw();
            }, 500); // Esperar 500ms después de dejar de escribir
        }
        // Si tiene menos del mínimo, no buscar (opcional: mostrar mensaje)
    });
    */
}
// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("anoP") != null) {
    $("#selectAnoCuentaP").val(localStorage.getItem("anoP"));
    $("#selectAnoCuentaP").selectpicker("refresh");
    cargarTablaCuentasPendientes(localStorage.getItem("anoP"));
} else {
    cargarTablaCuentasPendientes(null);
}

//CUENTAS PENDIENTES
function cargarTablaCuentasPendientes(ano) {
    $(".tablaCuentasPendientes").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas-pendientes.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&ano=" +
            ano,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[4, "desc"]],
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

// Validamos que venga la variable capturaRango en el localStorage
if (localStorage.getItem("anoC") != null) {
    $("#selectAnoCuentaC").val(localStorage.getItem("anoC"));
    $("#selectAnoCuentaC").selectpicker("refresh");
    cargarTablaCuentasAprobadas(localStorage.getItem("anoC"));
} else {
    cargarTablaCuentasAprobadas(null);
}

//CUENTAS
function cargarTablaCuentasAprobadas(ano) {
    $(".tablaCuentasAprobadas").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas-canceladas.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&ano=" +
            ano,
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

$("#nuevoMonto").change(function () {
    var saldo = $(this).val();
    $("#nuevoSaldo").val(saldo);
    estadoSaldo2();
});
function estadoSaldo2() {
    var saldo = $("#nuevoSaldo").val();
    if (saldo == "0") {
        $("#nuevoEstado1").val("CANCELADO");
        $("#nuevoEstado1").css("background-color", "green");
        $("#nuevoEstado1").css("color", "white");
    } else {
        $("#nuevoEstado1").val("PENDIENTE");
        $("#nuevoEstado1").css("background-color", "red");
        $("#nuevoEstado1").css("color", "white");
    }
}
/*=============================================
EDITAR TIPO DE PAGO
=============================================*/
$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnEditarCuenta",
    function () {
        var idCuenta = $(this).attr("idCuenta");

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
                $("#idCuenta").val(respuesta["id"]);
                $("#editarCodigo").val(respuesta["tipo_doc"]);
                $("#editarCodigo").selectpicker("refresh");
                $("#editarDocumento").val(respuesta["num_cta"]);
                $("#editarNota").val(respuesta["notas"]);
                $("#editarVendedor").val(respuesta["vendedor"]);
                $("#editarVendedor").selectpicker("refresh");
                if (respuesta["renovacion"] == 1) {
                    $("#editarRenovacion").prop("checked", true);
                }
                if (respuesta["protesta"] == 1) {
                    $("#editarProtestado").prop("checked", true);
                }
                $("#editarBanco").val(respuesta["banco"]);
                $("#editarBanco").selectpicker("refresh");
                $("#editarTipoDocumento").val(respuesta["cod_pago"]);
                $("#editarTipoDocumento").selectpicker("refresh");
                $("#editarFecha").val(respuesta["fecha"]);
                $("#editarFechaVenc").val(respuesta["fecha_ven"]);
                $("#editarUnico").val(respuesta["num_unico"]);
                $("#editarOrigen").val(respuesta["doc_origen"]);
                $("#editarFechaAcep").val(respuesta["fecha_cep"]);
                $("#editarFechaEnvio").val(respuesta["fecha_envio"]);
                $("#editarSaldo").val(respuesta["saldo"]);
                $("#editarFechaUltima").val(respuesta["ult_pago"]);
                $("#editarMoneda").val(respuesta["tip_mon"]);
                $("#editarMoneda").selectpicker("refresh");
                $("#editarFechaAbono").val(respuesta["fecha_abono"]);
                $("#editarEstado1").val(respuesta["estado"]);
                $("#editarMonto").val(respuesta["monto"]);
                $("#editarTipoCambio").val(respuesta["tip_cambio"]);
                $("#editarEstado").val(respuesta["estado_doc"]);
                $("#editarEstado").selectpicker("refresh");

                var clienteCuenta = "1";

                var datos = new FormData();
                datos.append("clienteCuenta", clienteCuenta);
                $.ajax({
                    url: "ajax/clientes.ajax.php",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (respuesta2) {
                        $("#editarCliente").find("option").remove();

                        for (let i = 0; i < respuesta2.length; i++) {
                            $("#editarCliente").append(
                                "<option value='" +
                                    respuesta2[i]["codigo"] +
                                    "'>" +
                                    respuesta2[i]["codigo"] +
                                    " - " +
                                    respuesta2[i]["nombre"] +
                                    "</option>",
                            );
                        }
                        $("#editarCliente").val(respuesta["cliente"]);
                        $("#editarCliente").selectpicker("refresh");
                    },
                });
            },
        });
    },
);

$(".tablaCuentasPendientes").on("click", ".btnEditarCuenta", function () {
    var idCuenta = $(this).attr("idCuenta");

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
            $("#idCuenta").val(respuesta["id"]);
            $("#editarCodigo").val(respuesta["tipo_doc"]);
            $("#editarCodigo").selectpicker("refresh");
            $("#editarDocumento").val(respuesta["num_cta"]);
            $("#editarNota").val(respuesta["notas"]);
            $("#editarVendedor").val(respuesta["vendedor"]);
            $("#editarVendedor").selectpicker("refresh");
            if (respuesta["renovacion"] == 1) {
                $("#editarRenovacion").prop("checked", true);
            }
            if (respuesta["protesta"] == 1) {
                $("#editarProtestado").prop("checked", true);
            }
            $("#editarBanco").val(respuesta["banco"]);
            $("#editarBanco").selectpicker("refresh");
            $("#editarTipoDocumento").val(respuesta["cod_pago"]);
            $("#editarTipoDocumento").selectpicker("refresh");
            $("#editarFecha").val(respuesta["fecha"]);
            $("#editarFechaVenc").val(respuesta["fecha_ven"]);
            $("#editarUnico").val(respuesta["num_unico"]);
            $("#editarOrigen").val(respuesta["doc_origen"]);
            $("#editarFechaAcep").val(respuesta["fecha_cep"]);
            $("#editarFechaEnvio").val(respuesta["fecha_envio"]);
            $("#editarSaldo").val(respuesta["saldo"]);
            $("#editarFechaUltima").val(respuesta["ult_pago"]);
            $("#editarMoneda").val(respuesta["tip_mon"]);
            $("#editarMoneda").selectpicker("refresh");
            $("#editarFechaAbono").val(respuesta["fecha_abono"]);
            $("#editarEstado1").val(respuesta["estado"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarTipoCambio").val(respuesta["tip_cambio"]);
            $("#editarEstado").val(respuesta["estado_doc"]);
            $("#editarEstado").selectpicker("refresh");

            var clienteCuenta = "1";

            var datos = new FormData();
            datos.append("clienteCuenta", clienteCuenta);
            $.ajax({
                url: "ajax/clientes.ajax.php",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta2) {
                    $("#editarCliente").find("option").remove();

                    for (let i = 0; i < respuesta2.length; i++) {
                        $("#editarCliente").append(
                            "<option value='" +
                                respuesta2[i]["codigo"] +
                                "'>" +
                                respuesta2[i]["codigo"] +
                                " - " +
                                respuesta2[i]["nombre"] +
                                "</option>",
                        );
                    }
                    $("#editarCliente").val(respuesta["cliente"]);
                    $("#editarCliente").selectpicker("refresh");
                },
            });
        },
    });
});

$(".tablaCuentasAprobadas").on("click", ".btnEditarCuenta", function () {
    var idCuenta = $(this).attr("idCuenta");

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
            $("#idCuenta").val(respuesta["id"]);
            $("#editarCodigo").val(respuesta["tipo_doc"]);
            $("#editarCodigo").selectpicker("refresh");
            $("#editarDocumento").val(respuesta["num_cta"]);
            $("#editarNota").val(respuesta["notas"]);
            $("#editarVendedor").val(respuesta["vendedor"]);
            $("#editarVendedor").selectpicker("refresh");
            if (respuesta["renovacion"] == 1) {
                $("#editarRenovacion").prop("checked", true);
            }
            if (respuesta["protesta"] == 1) {
                $("#editarProtestado").prop("checked", true);
            }
            $("#editarBanco").val(respuesta["banco"]);
            $("#editarBanco").selectpicker("refresh");
            $("#editarTipoDocumento").val(respuesta["cod_pago"]);
            $("#editarTipoDocumento").selectpicker("refresh");
            $("#editarFecha").val(respuesta["fecha"]);
            $("#editarFechaVenc").val(respuesta["fecha_ven"]);
            $("#editarUnico").val(respuesta["num_unico"]);
            $("#editarOrigen").val(respuesta["doc_origen"]);
            $("#editarFechaAcep").val(respuesta["fecha_cep"]);
            $("#editarFechaEnvio").val(respuesta["fecha_envio"]);
            $("#editarSaldo").val(respuesta["saldo"]);
            $("#editarFechaUltima").val(respuesta["ult_pago"]);
            $("#editarMoneda").val(respuesta["tip_mon"]);
            $("#editarMoneda").selectpicker("refresh");
            $("#editarFechaAbono").val(respuesta["fecha_abono"]);
            $("#editarEstado1").val(respuesta["estado"]);
            $("#editarMonto").val(respuesta["monto"]);
            $("#editarTipoCambio").val(respuesta["tip_cambio"]);
            $("#editarEstado").val(respuesta["estado_doc"]);
            $("#editarEstado").selectpicker("refresh");

            var clienteCuenta = "1";

            var datos = new FormData();
            datos.append("clienteCuenta", clienteCuenta);
            $.ajax({
                url: "ajax/clientes.ajax.php",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta2) {
                    $("#editarCliente").find("option").remove();

                    for (let i = 0; i < respuesta2.length; i++) {
                        $("#editarCliente").append(
                            "<option value='" +
                                respuesta2[i]["codigo"] +
                                "'>" +
                                respuesta2[i]["codigo"] +
                                " - " +
                                respuesta2[i]["nombre"] +
                                "</option>",
                        );
                    }
                    $("#editarCliente").val(respuesta["cliente"]);
                    $("#editarCliente").selectpicker("refresh");
                },
            });
        },
    });
});

$("#cancelarMonto").keyup(function () {
    var saldo = $(this).val();
    var saldoAntiguo = $("#cancelarSaldoAntiguo").val();
    var diferencia = saldoAntiguo - saldo;
    $("#cancelarSaldo").val(diferencia);
    if (diferencia < 0) {
        swal({
            title: "La cantidad supera el Saldo de la cuenta ",
            text: "¡Sólo hay S/. " + saldoAntiguo + " de saldo!",
            type: "error",
            confirmButtonText: "¡Cerrar!",
        });
        $(this).val("");
        $("#cancelarSaldo").val(saldoAntiguo);
        return;
    }
});

$("#editarMonto").change(function () {
    var saldo = $(this).val();
    $("#editarSaldo").val(saldo);
    estadoSaldo();
});
function estadoSaldo() {
    var saldo = $("#editarSaldo").val();
    if (saldo == "0") {
        $("#editarEstado1").val("CANCELADO");
    } else {
        $("#editarEstado1").val("PENDIENTE");
    }
}

/*=============================================
ELIMINAR TIPO DE PAGO
=============================================*/
$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnEliminarCuenta",
    function () {
        var idCuenta = $(this).attr("idCuenta");
        var rutas =
            typeof window.RUTA_CUENTAS !== "undefined"
                ? window.RUTA_CUENTAS
                : "cuentas";
        var rutaEliminar =
            typeof window.RUTA_CUENTAS !== "undefined"
                ? window.RUTA_CUENTAS
                : "cuentas";

        swal({
            title: "¿Está seguro de borrar la cuenta?",
            text: "¡Si no lo está puede cancelar la acción!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Si, borrar cuenta!",
        }).then(function (result) {
            if (result.value) {
                window.location =
                    "index.php?ruta=" +
                    rutaEliminar +
                    "&idCuenta=" +
                    idCuenta +
                    "&rutas=" +
                    rutas;
            }
        });
    },
);

$(".tablaCuentasPendientes").on("click", ".btnEliminarCuenta", function () {
    var idCuenta = $(this).attr("idCuenta");
    var rutas = "cuentas-pendientes";
    swal({
        title: "¿Está seguro de borrar la cuenta?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar cuenta!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=cuentas&idCuenta=" +
                idCuenta +
                "&rutas=" +
                rutas;
        }
    });
});

$(".tablaCuentasAprobadas").on("click", ".btnEliminarCuenta", function () {
    var idCuenta = $(this).attr("idCuenta");
    var rutas = "cuentas-canceladas";
    swal({
        title: "¿Está seguro de borrar la cuenta?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar cuenta!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=cuentas&idCuenta=" +
                idCuenta +
                "&rutas=" +
                rutas;
        }
    });
});

//Reporte de Cuentas
$(".box").on("click", ".btnReporteCuentas", function () {
    var ano = $(this).attr("ano");
    window.location = "vistas/reportes_excel/rpt_cuentas.php?ano=" + ano;
});

//Reporte de Cuentas
$(".box").on("click", ".btnReporteCuentasPendientes", function () {
    var anoP = $(this).attr("ano");
    window.location =
        "vistas/reportes_excel/rpt_cuentas_pendientes.php?anoP=" + anoP;
});

//Reporte de Cuentas
$(".box").on("click", ".btnReporteCuentasAprobadas", function () {
    var anoC = $(this).attr("ano");
    window.location =
        "vistas/reportes_excel/rpt_cuentas_aprobadas.php?anoC=" + anoC;
});

$(".box").on("click", ".btnReporteLetrasUrgentes", function () {
    window.location =
        "vistas/reportes_excel/rpt_letras_urgentes_protesto.php";
});

$(".box").on("click", ".btnCodigoCuenta", function () {
    $("#nuevaMoneda").val("Soles");
    $("#nuevaMoneda").selectpicker("refresh");

    var clienteCuenta = "1";

    var datos = new FormData();
    datos.append("clienteCuenta", clienteCuenta);
    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#nuevoClienteCuenta").find("option").remove();
            $("#nuevoClienteCuenta").append(
                '<option value="">Seleccionar cliente</option>',
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevoClienteCuenta").append(
                    "<option value='" +
                        respuesta[i]["codigo"] +
                        "'>" +
                        respuesta[i]["codigo"] +
                        " - " +
                        respuesta[i]["nombre"] +
                        "</option>",
                );
            }
            $("#nuevoClienteCuenta").selectpicker("refresh");
        },
    });
});

$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnVisualizarCuenta",
    function () {
        var codCuenta = $(this).attr("codCta");
        var numCuenta = $(this).attr("numCta");
        localStorage.setItem("numCta2", numCuenta);
        localStorage.setItem("codCta2", codCuenta);
        var rutas =
            typeof window.RUTA_CUENTAS !== "undefined"
                ? window.RUTA_CUENTAS
                : "cuentas";
        window.location =
            "index.php?ruta=ver-cuentas&numCta=" +
            numCuenta +
            "&codCuenta=" +
            codCuenta +
            "&rutas=" +
            rutas;
    },
);

$(".tablaCuentasPendientes").on("click", ".btnVisualizarCuenta", function () {
    var codCuenta = $(this).attr("codCta");
    var numCuenta = $(this).attr("numCta");
    localStorage.setItem("numCta2", numCuenta);
    localStorage.setItem("codCta2", codCuenta);
    var rutas = "cuentas-pendientes";
    window.location =
        "index.php?ruta=ver-cuentas&numCta=" +
        numCuenta +
        "&codCuenta=" +
        codCuenta +
        "&rutas=" +
        rutas;
});

$(".tablaCuentasAprobadas").on("click", ".btnVisualizarCuenta", function () {
    var numCuenta = $(this).attr("numCta");
    localStorage.setItem("numCta2", numCuenta);
    var rutas = "cuentas-canceladas";
    window.location =
        "index.php?ruta=ver-cuentas&numCta=" + numCuenta + "&rutas=" + rutas;
});

$(".tablaCuentasConsultar").on(
    "click",
    ".btnVisualizarCuentaConsultar",
    function () {
        var numCuenta = $(this).attr("numCta");
        localStorage.setItem("numCtaB", numCuenta);

        var codCuenta = $(this).attr("codCta");
        localStorage.setItem("codCtaB", codCuenta);

        window.location =
            "index.php?ruta=ver-cuentas-consultar&numCta=" +
            numCuenta +
            "&codCta=" +
            codCuenta;
    },
);

/*=============================================
ELIMINAR TIPO DE PAGO
=============================================*/
$(".tablaVerCuentas").on("click", ".btnEliminarCancelacion", function () {
    var idCancelacion = $(this).attr("idCancelacion");
    const rutas = document.getElementById("rutas").value;
    console.log("🚀 ~ rutas:", rutas);

    swal({
        title: "¿Está seguro de borrar la cancelación?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar cancelación!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=ver-cuentas&idCancelacion=" +
                idCancelacion +
                "&rutas=" +
                rutas;
        }
    });
});

$(".tablaVerCuentas").on("click", ".btnEditarCancelacion", function () {
    var idCancelacion = $(this).attr("idCancelacion");
    var datos = new FormData();
    datos.append("idCancelacion", idCancelacion);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);
            $("#idCuenta2").val(respuesta["id"]);
            $("#cancelarDocumento").val(respuesta["doc_origen"]);
            $("#docEditar").val(respuesta["num_cta"]);
            $("#cancelarNota").val(respuesta["notas"]);
            $("#cancelarCodigo").val(respuesta["cod_pago"]);
            $("#tipEditar").val(respuesta["tipo_doc"]);
            $("#cancelarCodigo").selectpicker("refresh");
            $("#cancelarVendedor").val(respuesta["vendedor"]);
            $("#cancelarCliente").val(respuesta["cliente"]);
            $("#cancelarFechaUltima").val(respuesta["fecha"]);
            $("#cancelarMonto2").val(respuesta["monto"]);
            $("#cancelarMontoAntiguo").val(respuesta["monto"]);
            $("#cliEditar").val(respuesta["cliente"]);
        },
    });
});

$("#cancelarMonto2").change(function () {
    var montoNuevo = $(this).val();
    var saldoAntiguo = $("#cancelarSaldoAntiguo").val();
    var montoAntiguo = $("#cancelarMontoAntiguo").val();

    var disponible = Number(saldoAntiguo) + Number(montoAntiguo);

    var validar =
        Number(saldoAntiguo) + Number(montoAntiguo) - Number(montoNuevo);

    //console.log(validar);

    if (validar >= 0) {
    } else {
        swal({
            title: "La cantidad supera el Saldo de la cuenta ",
            text: "¡Sólo hay S/. " + disponible + " de saldo!",
            type: "error",
            confirmButtonText: "¡Cerrar!",
        });

        return;
    }
});

$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnAgregarLetra",
    function () {
        var idCuenta = $(this).attr("idCuenta");
        var cliente = $(this).attr("cliente");
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
                $("#idCuenta3").val(respuesta["id"]);
                $("#letraCodigo").val(respuesta["tipo_doc"]);
                $("#letraDocumento").val(respuesta["num_cta"]);
                $("#letraUsuario").val(respuesta["usuario"]);
                $("#letraVendedor").val(respuesta["vendedor"]);
                $("#letraCli").val(respuesta["cliente"]);
                $("#letraFecha").val(respuesta["fecha"]);
                $("#letraMonto").val(respuesta["monto"]);
                $("#letraSaldo").val(respuesta["saldo"]);
                $("#letraMoneda").val(respuesta["tip_mon"]);
                $("#letraCliente").val(cliente);
                $(".letraCuenta").remove();
            },
        });
    },
);

$(".tablaCuentasPendientes").on("click", ".btnAgregarLetra", function () {
    var idCuenta = $(this).attr("idCuenta");
    var cliente = $(this).attr("cliente");
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
            $("#idCuenta3").val(respuesta["id"]);
            $("#letraCodigo").val(respuesta["tipo_doc"]);
            $("#letraDocumento").val(respuesta["num_cta"]);
            $("#letraUsuario").val(respuesta["usuario"]);
            $("#letraVendedor").val(respuesta["vendedor"]);
            $("#letraCli").val(respuesta["cliente"]);
            $("#letraFecha").val(respuesta["fecha"]);
            $("#letraMonto").val(respuesta["monto"]);
            $("#letraSaldo").val(respuesta["saldo"]);
            $("#letraMoneda").val(respuesta["tip_mon"]);
            $("#letraCliente").val(cliente);
            $(".letraCuenta").remove();
        },
    });
});

$(".tablaCuentasAprobadas").on("click", ".btnAgregarLetra", function () {
    var idCuenta = $(this).attr("idCuenta");
    var cliente = $(this).attr("cliente");
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
            $("#idCuenta3").val(respuesta["id"]);
            $("#letraCodigo").val(respuesta["tipo_doc"]);
            $("#letraDocumento").val(respuesta["num_cta"]);
            $("#letraUsuario").val(respuesta["usuario"]);
            $("#letraVendedor").val(respuesta["vendedor"]);
            $("#letraCli").val(respuesta["cliente"]);
            $("#letraFecha").val(respuesta["fecha"]);
            $("#letraMonto").val(respuesta["monto"]);
            $("#letraSaldo").val(respuesta["saldo"]);
            $("#letraMoneda").val(respuesta["tip_mon"]);
            $("#letraCliente").val(cliente);
            $(".letraCuenta").remove();
        },
    });
});

$(".btnGenerarLetra").click(function () {
    $(this).attr("disabled", true);
    $(this).removeClass("btn-primary");
    $(this).addClass("btn-default");

    var nroLetra = $("#nroLetra").val();
    var saldo = $("#letraSaldo").val();
    var montoLetra = Number(saldo) / Number(nroLetra);
    var fecha = new Date($("#letraFecha").val());
    var sumaDias = Number($("#sumaFecha").val()) + 1;
    var intervalo = Number($("#sumaIntervalo").val());

    fecha.setDate(fecha.getDate() + sumaDias);
    var mes = fecha.getMonth() + 1;
    var dia = fecha.getDate();

    if (mes.toString().length == 1) {
        if (dia.toString().length == 1) {
            var resultado =
                fecha.getFullYear() +
                "-0" +
                (fecha.getMonth() + 1) +
                "-" +
                "0" +
                fecha.getDate();
        } else {
            var resultado =
                fecha.getFullYear() +
                "-0" +
                (fecha.getMonth() + 1) +
                "-" +
                fecha.getDate();
        }
    } else {
        if (dia.toString().length == 1) {
            var resultado =
                fecha.getFullYear() +
                "-" +
                (fecha.getMonth() + 1) +
                "-" +
                "0" +
                fecha.getDate();
        } else {
            var resultado =
                fecha.getFullYear() +
                "-" +
                (fecha.getMonth() + 1) +
                "-" +
                fecha.getDate();
        }
    }

    //console.log(resultado);

    for (let index = 0; index < nroLetra; index++) {
        nuevoTotal = montoLetra.toFixed(2) * nroLetra;

        nuevaDif = Number(saldo) - nuevoTotal;

        nuevoMonto = Number(montoLetra.toFixed(2)) + Number(nuevaDif);

        //console.log(nuevoMonto);

        if (index == 0) {
            $(".listaLetras").append(
                '<div class="letraCuenta col-lg-12" style="padding:0px">' +
                    '<div class="col-lg-3" style="padding-top:10px">' +
                    '<input type="date" class="form-control "   name="fechaVenc[]" value="' +
                    resultado +
                    '"  required>' +
                    "</div>" +
                    '<div class="col-lg-6" style="padding-top:10px">' +
                    '<input type="text" class="form-control "   name="obs' +
                    index +
                    '" value="Letra ' +
                    (index + 1) +
                    '"  >' +
                    "</div>" +
                    '<div class="col-lg-2" style="padding-top:10px">' +
                    '<input type="text" class="form-control "   name="monto' +
                    index +
                    '" value="' +
                    nuevoMonto.toFixed(2) +
                    '" required>' +
                    "</div>" +
                    '<div class="col-lg-12"></div><br>' +
                    "</div>",
            );
        } else {
            fecha.setDate(fecha.getDate() + intervalo);
            var mes2 = fecha.getMonth() + 1;
            var dia2 = fecha.getDate();

            if (mes2.toString().length == 1) {
                if (dia2.toString().length == 1) {
                    var resultado2 =
                        fecha.getFullYear() +
                        "-0" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        "0" +
                        fecha.getDate();
                } else {
                    var resultado2 =
                        fecha.getFullYear() +
                        "-0" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        fecha.getDate();
                }
            } else {
                if (dia2.toString().length == 1) {
                    var resultado2 =
                        fecha.getFullYear() +
                        "-" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        "0" +
                        fecha.getDate();
                } else {
                    var resultado2 =
                        fecha.getFullYear() +
                        "-" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        fecha.getDate();
                }
            }

            //console.log(resultado);

            $(".listaLetras").append(
                '<div class="letraCuenta col-lg-12" style="padding:0px">' +
                    '<div class="col-lg-3" style="padding-top:10px">' +
                    '<input type="date" class="form-control "   name="fechaVenc[]" value="' +
                    resultado2 +
                    '"  required>' +
                    "</div>" +
                    '<div class="col-lg-6" style="padding-top:10px">' +
                    '<input type="text" class="form-control "   name="obs' +
                    index +
                    '"  value="Letra ' +
                    (index + 1) +
                    '"  >' +
                    "</div>" +
                    '<div class="col-lg-2" style="padding-top:10px">' +
                    '<input type="text" class="form-control "   name="monto' +
                    index +
                    '" value="' +
                    montoLetra.toFixed(2) +
                    '" required>' +
                    "</div>" +
                    '<div class="col-lg-12"></div><br>' +
                    "</div>",
            );
        }
    }
});

$(".btnLimpiarLetra").click(function () {
    $(".btnGenerarLetra").removeAttr("disabled");
    $(".btnGenerarLetra").removeClass("btn-default");
    $(".btnGenerarLetra").addClass("btn-primary");
    $(".letraCuenta").remove();
});

// $(".btnCancelarCuenta2").click(function () {
//     var numCta = $(this).attr("numCta");
//     var codCta = $(this).attr("codCta");
//     var datos = new FormData();
//     datos.append("numCta", numCta);
//     datos.append("codCta", codCta);

//     $.ajax({
//         url: "ajax/cuentas.ajax.php",
//         method: "POST",
//         data: datos,
//         cache: false,
//         contentType: false,
//         processData: false,
//         dataType: "json",
//         success: function (respuesta) {
//             // $("#idCuenta3").val(respuesta["id"]);
//             // $("#cancelarTipoDocumento2").val(respuesta["tipo_doc"]);
//             // $("#cancelarDocumento2").val(respuesta["num_cta"]);
//             // $("#cancelarDocumentoOriginal2").val(respuesta["num_cta"]);
//             //// $("#cancelarDocumento2").val(respuesta["num_cta"]);
//             // $("#cancelarVendedor2").val(respuesta["vendedor"]);
//             // $("#cancelarFechaOrigen2").val(respuesta["fecha"]);
//             // $("#cancelarVencimientoOrigen2").val(respuesta["fecha_ven"]);
//             // $("#cancelarCliente2").val(respuesta["cliente"]);
//             // $("#cancelarClienteNomOrigen2").val(respuesta["nombre"]);
//             // $("#cancelarSaldo2").val(respuesta["saldo"]);
//             // $("#cancelarSaldoAntiguo2").val(respuesta["saldo"]);
//             // $("#cancelarEstado2").val(respuesta["estado"]);
//             // $("#cancelarNumUnico2").val(respuesta["estado"]);
//             $("#cancelarTotal2").val(respuesta["saldo"]);
//         },
//     });
// });

$("#cancelarMonto3").change(function () {
    var saldo = $(this).val();
    var saldoAntiguo = $("#cancelarSaldoAntiguo2").val();
    var diferencia = saldoAntiguo - saldo;
    $("#cancelarSaldo2").val(diferencia);
    if (diferencia < 0) {
        swal({
            title: "La cantidad supera el Saldo de la cuenta ",
            text: "¡Sólo hay S/. " + saldoAntiguo + " de saldo!",
            type: "error",
            confirmButtonText: "¡Cerrar!",
        });
        $(this).val("");
        $("#cancelarSaldo2").val(saldoAntiguo);
        return;
    }
});

function refrescarCabeceraConsultarCuentas(cliente) {
    if (!cliente) {
        var elCli = document.getElementById("consultaCliente");
        if (elCli) {
            elCli.innerText = "-";
        }
        var elCred = document.getElementById("consultaCredito");
        if (elCred) {
            elCred.innerText = "0";
        }
        var elDeu = document.getElementById("consultaDeudaTot");
        if (elDeu) {
            elDeu.innerText = "0";
        }
        var elVen = document.getElementById("consultaDeudaVen");
        if (elVen) {
            elVen.innerText = "0";
        }
        return;
    }

    var datos = new FormData();
    datos.append("codigo", cliente);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            var resultCliente = document.getElementById("consultaCliente");
            if (resultCliente) {
                resultCliente.innerText =
                    respuesta["codigo"] + " - " + respuesta["nombre"];
            }
        },
    });

    var datos2 = new FormData();
    datos2.append("clienteCredito", cliente);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta2) {
            var resultTotalVenta = document.getElementById("consultaCredito");
            if (resultTotalVenta) {
                resultTotalVenta.innerText =
                    "S/ " + respuesta2["total_credito"];
            }
        },
    });

    var datos3 = new FormData();
    datos3.append("clienteDeuda", cliente);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos3,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta3) {
            var resultTotalDeuda = document.getElementById("consultaDeudaTot");
            if (!resultTotalDeuda) {
                return;
            }
            if (respuesta3 == false) {
                resultTotalDeuda.innerText = "S/ 0.00";
            } else {
                var deudaVen = parseFloat(respuesta3["total_deuda"]);
                resultTotalDeuda.innerText =
                    "S/ " +
                    new Intl.NumberFormat("de-DE").format(deudaVen.toFixed(2));
            }
        },
    });

    var datos4 = new FormData();
    datos4.append("clienteDeudaVencida", cliente);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos4,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta4) {
            var resultTotalDeudaVen =
                document.getElementById("consultaDeudaVen");
            if (!resultTotalDeudaVen) {
                return;
            }
            if (respuesta4 == false) {
                resultTotalDeudaVen.innerText = "S/ 0.00";
            } else {
                var deudaVen = parseFloat(respuesta4["total_vencido"]);
                resultTotalDeudaVen.innerText =
                    "S/ " +
                    new Intl.NumberFormat("de-DE").format(deudaVen.toFixed(2));
            }
        },
    });
}

$("#tipoCliente").change(function () {
    var cliente = $(this).val();
    var descripcion = $(this).find("option:selected").text();

    if ($.fn.DataTable.isDataTable(".tablaCuentasConsultar")) {
        $(".tablaCuentasConsultar").DataTable().destroy();
    }

    if (!cliente) {
        localStorage.removeItem("cliente");
        localStorage.removeItem("desCliente");
        sessionStorage.removeItem("desCliente");
        $("#CodCliBtn").val("");
        refrescarCabeceraConsultarCuentas(null);
        cargarTablaCuentasConsultar(null);
        return;
    }

    localStorage.setItem("cliente", cliente);
    localStorage.setItem("desCliente", descripcion);
    sessionStorage.setItem("desCliente", descripcion);

    $("#CodCliBtn").val(cliente);

    refrescarCabeceraConsultarCuentas(cliente);
    cargarTablaCuentasConsultar(cliente);
});

var clienteConsultarGuardado = localStorage.getItem("cliente");
if (clienteConsultarGuardado) {
    var desClienteGuardado =
        localStorage.getItem("desCliente") ||
        sessionStorage.getItem("desCliente") ||
        clienteConsultarGuardado;
    $("#tipoCliente").find("option").remove();
    $("<option>", {
        value: clienteConsultarGuardado,
        text: desClienteGuardado,
        selected: true,
    }).appendTo("#tipoCliente");
    $("#tipoCliente").selectpicker("refresh");
    $("#CodCliBtn").val(clienteConsultarGuardado);
    refrescarCabeceraConsultarCuentas(clienteConsultarGuardado);
    cargarTablaCuentasConsultar(clienteConsultarGuardado);
} else {
    cargarTablaCuentasConsultar(null);
}

//CUENTAS consultar
function cargarTablaCuentasConsultar(cliente) {
    $(".tablaCuentasConsultar").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-cuentas-consultar.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&cliente=" +
            cliente,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[5, "desc"]],
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

//Dividir letra

$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnDividirLetra",
    function () {
        var idCuenta = $(this).attr("idCuenta");
        var cliente = $(this).attr("cliente");

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
                console.log(
                    "🚀 ~ file: cuentas.js:1254 ~ respuesta:",
                    respuesta["num_unico"],
                );

                $("#idCuenta4").val(respuesta["id"]);
                $("#dividirDocumento").val(respuesta["tipo_doc"]);
                $("#dividirNroDocumento").val(respuesta["num_cta"]);
                $("#dividirFecha").val(respuesta["fecha"]);
                $("#dividirFechaVencimiento").val(respuesta["fecha_ven"]);
                $("#dividirSaldo").val(respuesta["saldo"]);
                $("#dividirVendedor").val(respuesta["vendedor"]);
                $("#dividirCliente").val(respuesta["cliente"]);
                $("#dividirNomCliente").val(cliente);
                $("#dividirFecha2").val(respuesta["fecha_ven"]);
                $("#dividirNroDocumento2").val(respuesta["num_cta"]);

                $("#dividirNumUnico").val(respuesta["num_unico"]);

                $("#dividirFechaCep").val(respuesta["fecha_cep"]);

                var fecha = new Date(respuesta["fecha_ven"]);
                fecha.setDate(fecha.getDate() + 31);
                var mes = fecha.getMonth() + 1;
                var dia = fecha.getDate();
                if (mes.toString().length == 1) {
                    if (dia.toString().length == 1) {
                        var resultado =
                            fecha.getFullYear() +
                            "-0" +
                            (fecha.getMonth() + 1) +
                            "-" +
                            "0" +
                            fecha.getDate();
                    } else {
                        var resultado =
                            fecha.getFullYear() +
                            "-0" +
                            (fecha.getMonth() + 1) +
                            "-" +
                            fecha.getDate();
                    }
                } else {
                    if (dia.toString().length == 1) {
                        var resultado =
                            fecha.getFullYear() +
                            "-" +
                            (fecha.getMonth() + 1) +
                            "-" +
                            "0" +
                            fecha.getDate();
                    } else {
                        var resultado =
                            fecha.getFullYear() +
                            "-" +
                            (fecha.getMonth() + 1) +
                            "-" +
                            fecha.getDate();
                    }
                }
                $("#dividirFechaVencimiento2").val(resultado);
            },
        });
    },
);

$(".tablaCuentasPendientes").on("click", ".btnDividirLetra", function () {
    var idCuenta = $(this).attr("idCuenta");
    var cliente = $(this).attr("cliente");

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
            console.log(respuesta);
            $("#idCuenta4").val(respuesta["id"]);
            $("#dividirDocumento").val(respuesta["tipo_doc"]);
            $("#dividirNroDocumento").val(respuesta["num_cta"]);
            $("#dividirFecha").val(respuesta["fecha"]);
            $("#dividirFechaVencimiento").val(respuesta["fecha_ven"]);
            $("#dividirSaldo").val(respuesta["saldo"]);
            $("#dividirVendedor").val(respuesta["vendedor"]);
            $("#dividirCliente").val(respuesta["cliente"]);
            $("#dividirNomCliente").val(cliente);
            $("#dividirFecha2").val(respuesta["fecha_ven"]);
            $("#dividirNroDocumento2").val(respuesta["num_cta"]);

            var fecha = new Date(respuesta["fecha_ven"]);
            fecha.setDate(fecha.getDate() + 31);
            var mes = fecha.getMonth() + 1;
            var dia = fecha.getDate();
            if (mes.toString().length == 1) {
                if (dia.toString().length == 1) {
                    var resultado =
                        fecha.getFullYear() +
                        "-0" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        "0" +
                        fecha.getDate();
                } else {
                    var resultado =
                        fecha.getFullYear() +
                        "-0" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        fecha.getDate();
                }
            } else {
                if (dia.toString().length == 1) {
                    var resultado =
                        fecha.getFullYear() +
                        "-" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        "0" +
                        fecha.getDate();
                } else {
                    var resultado =
                        fecha.getFullYear() +
                        "-" +
                        (fecha.getMonth() + 1) +
                        "-" +
                        fecha.getDate();
                }
            }
            $("#dividirFechaVencimiento2").val(resultado);
        },
    });
});

$(".box").on("click", "#cargaClienteCuenta", function () {
    var clienteCuenta = "1";

    var datos = new FormData();
    datos.append("clienteCuenta", clienteCuenta);
    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#tipoCliente").find("option").remove();
            $("#tipoCliente").append(
                '<option value="">Seleccionar cliente</option>',
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#tipoCliente").append(
                    "<option value='" +
                        respuesta[i]["codigo"] +
                        "'>" +
                        respuesta[i]["codigo"] +
                        " - " +
                        respuesta[i]["nombre"] +
                        " - " +
                        respuesta[i]["documento"] +
                        "</option>",
                );
            }
            $("#tipoCliente").selectpicker("refresh");
        },
    });
});

if (localStorage.getItem("numCtaB") != null) {
    cargarTablaVerCuentasConsultar(
        localStorage.getItem("numCtaB"),
        localStorage.getItem("codCtaB"),
    );
} else {
    cargarTablaVerCuentasConsultar(null, null);
}

//CUENTAS consultar
function cargarTablaVerCuentasConsultar(numCta, codCta) {
    $(".tablaVerCuentasConsultar").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-ver-cuentas-consultar.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&numCta=" +
            numCta +
            "&codCta=" +
            codCta,
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

if (localStorage.getItem("numCta2") != null) {
    cargarTablaVerCuentas(
        localStorage.getItem("numCta2"),
        localStorage.getItem("codCta2"),
    );
} else {
    cargarTablaVerCuentas(null, null);
}

//CUENTAS consultar
function cargarTablaVerCuentas(numCta, codCta) {
    $(".tablaVerCuentas").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-ver-cuentas.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&numCta=" +
            numCta +
            "&codCta=" +
            codCta,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[2, "asc"]],
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

$(".tablaEnvioLetras").DataTable({
    ajax:
        "ajax/cuentas-corrientes/tabla-envio-letras.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[1, "asc"]],
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

/*
 * AGREGANDO LOS ARTICULOS DE ORDEN DE CORTE A CORTE
 */

$(".tablaEnvioLetras tbody").on(
    "click",
    "button.agregarEnvioCuenta",
    function () {
        var idcuenta = $(this).attr("idcuenta");
        //console.log("ordcorte", ordcorte);
        //console.log("articuloAC", articuloAC);
        //console.log("idCorte", idCorte);
        //console.log("saldo", saldo);

        $(this).removeClass("btn-primary agregarEnvioCuenta");
        $(this).addClass("btn-default");

        var datos = new FormData();
        datos.append("letraCuenta", idcuenta);

        $.ajax({
            url: "ajax/cuentas.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                // console.log("respuesta", respuesta);

                var nrocta = respuesta["cuenta"];
                var cliente = respuesta["cliente"] + "-" + respuesta["nombre"];
                var monto = respuesta["saldo"];

                /* 
          todo: AGREGAR LOS CAMPOS
          */

                $(".nuevoCampoEnvio").append(
                    '<div class="row" style="padding:5px 15px">' +
                        "<!-- Numero de CUENTA Y QUITAR -->" +
                        '<div class="col-xs-3" style="padding-right:0px">' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarEnvioCuenta" idcuenta="' +
                        idcuenta +
                        '"><i class="fa fa-times"></i></button></span>' +
                        '<input type="text" class="form-control nuevoNumCuenta"   name="nrocta" value="' +
                        nrocta +
                        '" idcuenta="' +
                        idcuenta +
                        '" fecha_ven ="' +
                        respuesta["fechaVen"] +
                        '" readonly required>' +
                        "</div>" +
                        "</div>" +
                        "<!-- Descripción del CLIENTE -->" +
                        '<div class="col-xs-7" style="padding-right:0px">' +
                        '<input type="text" class="form-control nuevaDescripcionCliente" name="cliente" value="' +
                        cliente +
                        '"  nombres ="' +
                        respuesta["nombres"] +
                        '" ape_pat ="' +
                        respuesta["ape_paterno"] +
                        '" ape_mat ="' +
                        respuesta["ape_materno"] +
                        '" documento ="' +
                        respuesta["documento"] +
                        '" readonly required>' +
                        "</div>" +
                        "<!-- MONTO -->" +
                        '<div class="col-xs-2">' +
                        '<input type="number" class="form-control nuevoMonto" name="nuevoMonto"  value="' +
                        monto +
                        '" readonly required>' +
                        "</div>" +
                        "</div>",
                );

                // SUMAR TOTAL DE UNIDADES

                sumarTotalEnvio();

                // AGRUPAR PRODUCTOS EN FORMATO JSON

                listarCuentas();
            },
        });
    },
);

/*
 * CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
 */
$(".tablaEnvioLetras").on("draw.dt", function () {
    /* console.log("tabla"); */

    if (localStorage.getItem("quitarEnvioCuenta") != null) {
        var listaIdEnvioCuenta = JSON.parse(
            localStorage.getItem("quitarEnvioCuenta"),
        );
        //console.log("listaIdArticuloAC", listaIdArticuloAC);

        for (var i = 0; i < listaIdEnvioCuenta.length; i++) {
            $(
                "button.recuperarEnvioCuenta[idcuenta='" +
                    listaIdEnvioCuenta[i]["idcuenta"] +
                    "']",
            ).removeClass("btn-default");

            $(
                "button.recuperarEnvioCuenta[idcuenta='" +
                    listaIdEnvioCuenta[i]["idcuenta"] +
                    "']",
            ).addClass("btn-primary agregarEnvioCuenta");
        }
    }
});

/*
 * QUITAR ARTICULO DE CORTE Y RECUPERAR BOTÓN
 */
var idQuitarEnvioCuenta = [];

localStorage.removeItem("quitarEnvioCuenta");

$(".formularioEnvioLetra").on("click", "button.quitarEnvioCuenta", function () {
    /* console.log("boton"); */

    $(this).parent().parent().parent().parent().remove();

    var idcuenta = $(this).attr("idcuenta");

    /*=============================================
  ALMACENAR EN EL LOCALSTORAGE EL ID DEL MATERIA PRIMA A QUITAR
  =============================================*/

    if (localStorage.getItem("quitarEnvioCuenta") == null) {
        idQuitarEnvioCuenta = [];
    } else {
        idQuitarEnvioCuenta.concat(localStorage.getItem("quitarEnvioCuenta"));
    }

    idQuitarEnvioCuenta.push({
        idcuenta: idcuenta,
    });

    localStorage.setItem(
        "quitarEnvioCuenta",
        JSON.stringify(idQuitarEnvioCuenta),
    );

    $("button.recuperarEnvioCuenta[idcuenta='" + idcuenta + "']").removeClass(
        "btn-default",
    );

    $("button.recuperarEnvioCuenta[idcuenta='" + idcuenta + "']").addClass(
        "btn-primary agregarEnvioCuenta",
    );

    if ($(".nuevoCampoEnvio").children().length == 0) {
        $("#nuevoTotalCuentaEnvio").val(0);
        $("#totalEnvioCuentas").val(0);
        $("#nuevoTotalCuentaEnvio").attr("total", 0);
    } else {
        // SUMAR TOTAL DE UNIDADES

        sumarTotalEnvio();

        // AGREGAR IMPUESTO

        // AGRUPAR PRODUCTOS EN FORMATO JSON

        listarCuentas();
    }
});

/*
 * SUMAR EL TOTAL DE LOS CORTES
 */

function sumarTotalEnvio() {
    var num_cta = $(".nuevoNumCuenta");
    //console.log("cantidadAc", cantidadAc);

    var sumarTotal = num_cta.length;

    //console.log("sumarTotal", sumarTotal);

    $("#nuevoTotalCuentaEnvio").val(sumarTotal);
    $("#totalEnvioCuentas").val(sumarTotal);
    $("#nuevoTotalCuentaEnvio").attr("total", sumarTotal);
}

/*
 *formato al total
 */
$("#nuevoTotalCuentaEnvio").number(true, 0);

/*
 * LISTAR TODOS LOS ARTICULOS
 */
function listarCuentas() {
    var listaCuenta = [];

    var numcta = $(".nuevoNumCuenta");
    var cliente = $(".nuevaDescripcionCliente");
    var monto = $(".nuevoMonto");

    for (var i = 0; i < numcta.length; i++) {
        listaCuenta.push({
            idcuenta: $(numcta[i]).attr("idcuenta"),
            numcta: $(numcta[i]).val(),
            fecha: $(numcta[i]).attr("fecha_ven"),
            cliente_nom: $(cliente[i]).attr("nombres"),
            cliente_pat: $(cliente[i]).attr("ape_pat"),
            cliente_mat: $(cliente[i]).attr("ape_mat"),
            cliente_doc: $(cliente[i]).attr("documento"),
            monto: $(monto[i]).val(),
        });
    }

    // console.log("listaCuenta", JSON.stringify(listaCuenta));
    //console.log("listArticulo", listArticulo);

    $("#listaEnvioLetra").val(JSON.stringify(listaCuenta));
}

/*
 *FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL ARTICULO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
 */
function quitarAgregarEnvioCuenta() {
    //Capturamos todos los id de productos que fueron elegidos en la venta
    var idCuenta = $(".quitarEnvioCuenta");
    //console.log("articuloAC", articuloAC);

    //Capturamos todos los botones de agregar que aparecen en la tabla
    var botonesTablaEnvio = $(
        ".tablaEnvioLetras tbody button.agregarEnvioCuenta",
    );
    //console.log("botonesTablaAC", botonesTablaAC);

    //Recorremos en un ciclo para obtener los diferentes articuloAC que fueron agregados a la venta
    for (var i = 0; i < idCuenta.length; i++) {
        //Capturamos los Id de los productos agregados a la venta
        var boton = $(idCuenta[i]).attr("idCuenta");

        //Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
        for (var j = 0; j < botonesTablaEnvio.length; j++) {
            if ($(botonesTablaEnvio[j]).attr("idCuenta") == boton) {
                $(botonesTablaEnvio[j]).removeClass(
                    "btn-primary agregarEnvioCuenta",
                );
                $(botonesTablaEnvio[j]).addClass("btn-default");
            }
        }
    }
}

/*
 * CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIÓN:
 */
$(".tablaEnvioLetras").on("draw.dt", function () {
    quitarAgregarEnvioCuenta();
});

if (localStorage.getItem("capturarRango22") != null) {
    $("#daterange-btnEnvioCta span").html(
        localStorage.getItem("capturarRango22"),
    );
    cargarTablaEnvioCuentas(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal"),
    );
} else {
    $("#daterange-btnEnvioCta span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha ',
    );
    cargarTablaEnvioCuentas(null, null);
}

function cargarTablaEnvioCuentas(fechaInicial, fechaFinal) {
    $(".tablaEnvioCuentas").DataTable({
        ajax:
            "ajax/cuentas-corrientes/tabla-envio-cuentas.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
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
}

$("#daterange-btnEnvioCta").daterangepicker(
    {
        cancelClass: "CancelarEnvioCta",
        locale: {
            daysOfWeek: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
            monthNames: [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ],
        },
        ranges: {
            Hoy: [moment(), moment()],
            Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "Últimos 7 días": [moment().subtract(6, "days"), moment()],
            "Últimos 30 días": [moment().subtract(29, "days"), moment()],
            "Este mes": [moment().startOf("month"), moment().endOf("month")],
            "Último mes": [
                moment().subtract(1, "month").startOf("month"),
                moment().subtract(1, "month").endOf("month"),
            ],
        },

        startDate: moment(),
        endDate: moment(),
    },
    function (start, end) {
        $("#daterange-btnEnvioCta span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"),
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRango22 = $("#daterange-btnEnvioCta span").html();

        localStorage.setItem("capturarRango22", capturarRango22);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaEnvioCuentas").DataTable().destroy();
        cargarTablaEnvioCuentas(fechaInicial, fechaFinal);
    },
);

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .CancelarEnvioCta").on(
    "click",
    function () {
        localStorage.removeItem("capturarRango22");
        localStorage.removeItem("fechaInicial");
        localStorage.removeItem("fechaFinal");
        localStorage.clear();
        window.location = "ver-envio-letras";
    },
);

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function () {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if (ruta == "ver-envio-letras") {
        if (textoHoy == "Hoy") {
            var d = new Date();

            var dia = d.getDate();
            var mes = d.getMonth() + 1;
            var año = d.getFullYear();

            dia = ("0" + dia).slice(-2);
            mes = ("0" + mes).slice(-2);

            var fechaInicial = año + "-" + mes + "-" + dia;
            var fechaFinal = año + "-" + mes + "-" + dia;

            localStorage.setItem("capturarRango22", "Hoy");
            localStorage.setItem("fechaInicial", fechaInicial);
            localStorage.setItem("fechaFinal", fechaFinal);
            // Recargamos la tabla con la información para ser mostrada en la tabla
            $(".tablaEnvioCuentas").DataTable().destroy();
            cargarTablaEnvioCuentas(fechaInicial, fechaFinal);
        }
    }
});

// ===== Configuración centralizada de impresión de letras =====
(function () {
    function obtenerOcrear(key, valorDefault) {
        var valor = localStorage.getItem(key);
        if (valor === null || valor === undefined) {
            localStorage.setItem(key, valorDefault);
            return valorDefault;
        }
        return valor;
    }

    function inicializarModalConfigLetra() {
        var $modal = $("#modalConfigImpresionLetra");
        var $selectFondo = $("#configLetraFondo");
        var $selectSegunda = $("#configLetraSegunda");
        var $selectFormatoAntiguo = $("#configLetraFormatoAntiguo");

        if (!$modal.length) return;

        function sincronizarModal() {
            var valorFondo = obtenerOcrear("configLetraFondo", "1");
            var valorSegunda = obtenerOcrear("configLetraSegunda", "1");
            var valorFormatoAntiguo = obtenerOcrear(
                "configLetraFormatoAntiguo",
                "0",
            );
            $selectFondo.val(valorFondo);
            $selectSegunda.val(valorSegunda);
            $selectFormatoAntiguo.val(valorFormatoAntiguo);
        }

        $(".btnAbrirConfigLetra").on("click", function () {
            sincronizarModal();
            $modal.modal("show");
        });

        $(".btnGuardarConfigLetra").on("click", function () {
            var fondo = $selectFondo.val();
            var segunda = $selectSegunda.val();
            var formatoAntiguo = $selectFormatoAntiguo.val();
            localStorage.setItem("configLetraFondo", fondo);
            localStorage.setItem("configLetraSegunda", segunda);
            localStorage.setItem("configLetraFormatoAntiguo", formatoAntiguo);
            $modal.modal("hide");
            var mensaje =
                "Fondo: " +
                (fondo === "1" ? "Con fondo" : "Sin fondo") +
                "\nSegunda hoja: " +
                (segunda === "1" ? "Con 2da hoja" : "Sin 2da hoja");
            if (formatoAntiguo === "1") {
                mensaje += "\nFormato: Antiguo (temporal)";
            }
            swal({
                title: "Configuración guardada",
                text: mensaje,
                type: "success",
                timer: 1600,
                showConfirmButton: false,
            });
        });
    }

    function obtenerConfiguracionLetra() {
        return {
            fondo: localStorage.getItem("configLetraFondo") || "1",
            segundaHoja: localStorage.getItem("configLetraSegunda") || "1",
            formatoAntiguo:
                localStorage.getItem("configLetraFormatoAntiguo") || "0",
        };
    }

    function abrirLetraConfigurada(numCuenta) {
        var config = obtenerConfiguracionLetra();
        if (!numCuenta) {
            swal("Atención", "No se pudo obtener el número de cuenta.", "info");
            return;
        }

        // Si está activado el formato antiguo, usar imprimir_letra.php
        if (config.formatoAntiguo === "1") {
            window.open(
                "vistas/reportes_ticket/imprimir_letra.php?numCuenta=" +
                    encodeURIComponent(numCuenta),
                "_blank",
            );
            return;
        }

        // Formato nuevo (letra_full.php)
        var params = [
            "numCuenta=" + encodeURIComponent(numCuenta),
            "fondo=" + (config.fondo === "1" ? "1" : "0"),
            "segundaHoja=" + (config.segundaHoja === "1" ? "1" : "0"),
        ];

        window.open(
            "vistas/reportes_ticket/letra_full.php?" + params.join("&"),
            "_blank",
        );
    }

    $(document).ready(function () {
        inicializarModalConfigLetra();

        var tablasImpresion = [
            ".tablaCuentas",
            ".tablaCuentasPendientes",
            ".tablaCuentasAprobadas",
            ".tablaCuentasPaginado",
        ];

        for (var i = 0; i < tablasImpresion.length; i++) {
            $(tablasImpresion[i]).on("click", ".btnImprimirLetra", function () {
                var numCuenta = $(this).attr("numCuenta");
                abrirLetraConfigurada(numCuenta);
            });
        }

        // Imprimir solo la plantilla en blanco del formato
        $(".btnImprimirPlantillaLetra").on("click", function () {
            var config = obtenerConfiguracionLetra();
            var params = [
                "plantilla=1",
                "fondo=" + (config.fondo === "1" ? "1" : "0"),
                "segundaHoja=" + (config.segundaHoja === "1" ? "1" : "0"),
            ];
            window.open(
                "vistas/reportes_ticket/letra_full.php?" + params.join("&"),
                "_blank",
            );
        });
    });
})();

//Imprimir letra con hoja pequeña
$(".tablaCuentas, .tablaCuentasPaginado").on(
    "click",
    ".btnCargoProtesto",
    function () {
        var num_cta = $(this).attr("num_cta");
        var cliente = $(this).attr("cliente");
        //console.log(codigo);

        window.open(
            "vistas/reportes_ticket/cargo_protesto.php?num_cta=" +
                num_cta +
                "&cliente=" +
                cliente,
            "_blank",
        );
    },
);

$(".box").on("change", ".optradio", function () {
    var consulta = $(this).val();
    $(".btnGenerarReporteCuenta").attr("consulta", consulta);
    // console.log(consulta);
    if (
        consulta == "pendiente" ||
        consulta == "pendienteVencidoMenor" ||
        consulta == "pendienteVencidoMayor" ||
        consulta == "protestado" ||
        consulta == "option5" ||
        consulta == "estadoEnvioVacio" ||
        consulta == "unicoCartera" ||
        consulta == "option8" ||
        consulta == "option9" ||
        consulta == "cancelado"
    ) {
        $("#fechaCuentaInicio").prop("disabled", true);
        $("#fechaCuentaFin").prop("disabled", true);
    } else {
        $("#fechaCuentaInicio").prop("disabled", false);
        $("#fechaCuentaFin").prop("disabled", false);
    }

    if (consulta == "pagos") {
        $(".campoCancelacion").removeClass("hidden");
        $(".campoDocumento").addClass("hidden");
        $(".btnGenerarReporteCuenta").attr("tip_doc", "");
    } else {
        $(".campoDocumento").removeClass("hidden");
        $(".campoCancelacion").addClass("hidden");
        $(".btnGenerarReporteCuenta").attr("canc", "");
    }

    if (consulta == "fechaActualSaldo") {
        $(".campoCliente").removeClass("hidden");
        $(".campoVendedor").removeClass("hidden");
    }
});

$(".box").on("change", ".radioOrd1", function () {
    var orden1 = $(this).val();
    var consulta = $(".btnGenerarReporteCuenta").attr("consulta");
    console.log(orden1);
    $(".btnGenerarReporteCuenta").attr("orden1", orden1);
    if (consulta == "fechaActualSaldo") {
        return;
    }
    if (orden1 == "vendedor") {
        $(".campoVendedor").removeClass("hidden");
        $(".campoCliente").addClass("hidden");
        $(".btnGenerarReporteCuenta").attr("cli", "");
    } else if (orden1 == "cliente") {
        $(".campoCliente").removeClass("hidden");
        $(".campoVendedor").addClass("hidden");
        $(".btnGenerarReporteCuenta").attr("vend", "");
    } else if (orden1 == "fecha_ven") {
        $("#fechaCuentaInicio").prop("disabled", false);
        $("#fechaCuentaFin").prop("disabled", false);
    } else {
        $(".campoVendedor").addClass("hidden");
        $(".campoCliente").addClass("hidden");
        $(".btnGenerarReporteCuenta").attr("cli", "");
        $(".btnGenerarReporteCuenta").attr("vend", "");
    }
});

$(".box").on("change", ".radioOrd2", function () {
    var orden2 = $(this).val();
    $(".btnGenerarReporteCuenta").attr("orden2", orden2);
});

$(".box").on("change", "#tipoDocumentoReporte", function () {
    var tip_doc = $(this).val();
    $(".btnGenerarReporteCuenta").attr("tip_doc", tip_doc);
});

$(".box").on("change", "#tipoCancelacionReporte", function () {
    var canc = $(this).val();
    $(".btnGenerarReporteCuenta").attr("canc", canc);
});

$(".box").on("change", "#tipoClienteReporte", function () {
    var cliente = $(this).val();
    $(".btnGenerarReporteCuenta").attr("cli", cliente);
});

$(".box").on("change", "#tipoVendedorReporte", function () {
    var vendedor = $(this).val();
    $(".btnGenerarReporteCuenta").attr("vend", vendedor);
});

$(".box").on("change", "#tipoBancoReporte", function () {
    var banco = $(this).val();
    $(".btnGenerarReporteCuenta").attr("banco", banco);
});

$(".box").on("change", "#fechaCuentaInicio", function () {
    var inicio = $(this).val();
    $(".btnGenerarReporteCuenta").attr("inicio", inicio);
});

$(".box").on("change", "#fechaCuentaFin", function () {
    var fin = $(this).val();
    $(".btnGenerarReporteCuenta").attr("fin", fin);
});

$(".box").on("change", "#fechaCuentaFin", function () {
    var fin = $(this).val();
    $(".btnGenerarReporteCuenta").attr("fin", fin);
});

$(".box").on("change", ".radioImpresion", function () {
    var impresion = $(this).val();
    $(".btnGenerarReporteCuenta").attr("impresion", impresion);
});

$(".btnGenerarReporteCuenta").click(function () {
    var consulta = $(this).attr("consulta");
    var orden1 = $(this).attr("orden1");
    var orden2 = $(this).attr("orden2");
    var tip_doc = $(this).attr("tip_doc");
    var canc = $(this).attr("canc");
    var cli = $(this).attr("cli");
    var vend = $(this).attr("vend");
    var banco = $(this).attr("banco");
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var impresion = $(this).attr("impresion");

    // console.log("consulta", consulta);
    // console.log("orden1", orden1);
    // console.log("orden2", orden2);
    // console.log("tip_doc", tip_doc);
    // console.log("canc", canc);
    // console.log("cli", cli);
    // console.log("vend", vend);
    // console.log("banco", banco);
    // console.log("inicio", inicio);
    // console.log("fin", fin);

    if (impresion == "pantalla") {
        if (
            consulta == "pendiente" ||
            consulta == "pendienteVencidoMenor" ||
            consulta == "pendienteVencidoMayor" ||
            consulta == "protestado"
        ) {
            if (orden1 == "cliente") {
                window.open(
                    "extensiones/tcpdf/pdf/reporte_cliente_cuentas.php?consulta=" +
                        consulta +
                        "&orden1=" +
                        orden1 +
                        "&orden2=" +
                        orden2 +
                        "&cli=" +
                        cli,
                    "_blank",
                );
            } else if (orden1 == "tipo") {
                window.open(
                    "extensiones/tcpdf/pdf/reporte_general_cuentas.php?consulta=" +
                        consulta +
                        "&orden1=" +
                        orden1 +
                        "&orden2=" +
                        orden2,
                    "_blank",
                );
            } else if (orden1 == "vendedor") {
                if (vend == "") {
                    window.open(
                        "extensiones/tcpdf/pdf/reporte_general_cuentas.php?consulta=" +
                            consulta +
                            "&orden1=" +
                            orden1 +
                            "&orden2=" +
                            orden2 +
                            "&vend=" +
                            vend,
                        "_blank",
                    );
                } else {
                    window.open(
                        "extensiones/tcpdf/pdf/reporte_vendedor_cuentas.php?consulta=" +
                            consulta +
                            "&orden1=" +
                            orden1 +
                            "&orden2=" +
                            orden2 +
                            "&vend=" +
                            vend,
                        "_blank",
                    );
                }
            } else if (orden1 == "fecha_ven") {
                //console.log("aqui");

                window.open(
                    "extensiones/tcpdf/pdf/reporte_general_cuentas.php?consulta=" +
                        consulta +
                        "&orden1=" +
                        orden1 +
                        "&orden2=" +
                        orden2 +
                        "&banco=" +
                        banco +
                        "&tip_doc=" +
                        tip_doc +
                        "&fin=" +
                        fin,
                    "_blank",
                );
            }
        } else if (consulta == "pagos") {
            window.open(
                "extensiones/tcpdf/pdf/reporte_pago_cuentas.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    fin,
                "_blank",
            );
        } else if (consulta == "fechaActualSaldo") {
            window.open(
                "vistas/reportes_ticket/reporte_estado_cuentas.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    fin,
                "_blank",
            );
        } else if (consulta == "fechaSaldo") {
            window.open(
                "extensiones/tcpdf/pdf/reporte_saldo_fecha.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    fin,
                "_blank",
            );
        }
    } else {
        if (consulta == "pagos") {
            window.open(
                "vistas/reportes_excel/rpt_pagos_cta_cte.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    fin,
                "_blank",
            );
        } else if (consulta == "fechaActualSaldo") {
            var maxDiasEstadoCuenta = 2190;
            var hoyEstadoCuenta = new Date();
            var finEstadoCuenta = fin || hoyEstadoCuenta.toISOString().slice(0, 10);

            if (!inicio) {
                swal({
                    type: "error",
                    title: "Fecha requerida",
                    text: "Debe indicar la fecha de inicio para el estado de cuenta.",
                });
                return;
            }

            var diffEstadoCuenta =
                (new Date(finEstadoCuenta) - new Date(inicio)) /
                (1000 * 60 * 60 * 24);
            if (diffEstadoCuenta < 0) {
                swal({
                    type: "error",
                    title: "Rango invalido",
                    text: "La fecha de inicio no puede ser mayor que la fecha de fin.",
                });
                return;
            }
            if (diffEstadoCuenta > maxDiasEstadoCuenta) {
                swal({
                    type: "warning",
                    title: "Rango muy amplio",
                    text:
                        "El estado de cuenta en Excel admite un maximo de 6 anos. Reduzca el periodo o genere el reporte por partes.",
                });
                return;
            }

            window.open(
                "vistas/reportes_excel/rpt_estado_cuenta.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&cli=" +
                    cli +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    finEstadoCuenta,
                "_blank",
            );
        } else if (consulta == "fechaSaldo") {
            window.open(
                "vistas/reportes_excel/rpt_saldo_fecha.php?consulta=" +
                    consulta +
                    "&orden1=" +
                    orden1 +
                    "&orden2=" +
                    orden2 +
                    "&canc=" +
                    canc +
                    "&vend=" +
                    vend +
                    "&inicio=" +
                    inicio +
                    "&fin=" +
                    fin,
                "_blank",
            );
        } else if (consulta == "pendiente") {
            console.log("pendiente");
            window.open("vistas/reportes_excel/rpt_ctas_ctes.php", "_blank");
        }
    }
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaClientes").on("click", ".btnImprimirEstadoCuenta", function () {
    var cliente = $(this).attr("cliente");
    //console.log(cliente);

    // creamos un promp para que el usuario ingrese la linea a consultar 1. JackyForm 2. Rosalinda 3. Ambos, validamos que no sea vacio y sea un numero de las opciones
    var linea = prompt(
        "Ingrese la linea a consultar 1. JackyForm 2. Rosalinda 3. Ambos",
        "",
    );

    if (linea != "") {
        if (linea == "1" || linea == "2" || linea == "3") {
            window.open(
                "vistas/reportes_ticket/estado_cuenta.php?cliente=" +
                    cliente +
                    "&linea=" +
                    linea,
                "_blank",
            );
        } else {
            alert("Ingrese una opción válida");
        }
    } else {
        alert("Ingrese una opción válida");
    }
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaVtasGerenciaVdor").on("click", ".btnRptPeds", function () {
    var vendedor = $(this).attr("vendedor");
    //console.log(vendedor);

    window.open(
        "vistas/reportes_ticket/pedidos_vendedor.php?vendedor=" + vendedor,
        "_blank",
    );
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaCtasVdor").on("click", ".btnEstadoCtaVdor", function () {
    var vendedor = $(this).attr("vendedor");
    //console.log(codigo);

    window.open(
        "extensiones/tcpdf/pdf/reporte_estado_cuenta_vdor.php?vendedor=" +
            vendedor,
        "_blank",
    );
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaCtasVdor").on("click", ".btnEstadoCtaVdorNVdos", function () {
    var vendedor = $(this).attr("vendedor");
    //console.log(codigo);

    window.open(
        "extensiones/tcpdf/pdf/reporte_estado_cuenta_vdor_nvdos.php?vendedor=" +
            vendedor,
        "_blank",
    );
});

/*
 * BOTON  IMPRIMIR TICKET
 */
$(".tablaCtasVdor").on("click", ".btnEstadoCtaVdorVdos", function () {
    var vendedor = $(this).attr("vendedor");
    //console.log(codigo);

    window.open(
        "extensiones/tcpdf/pdf/reporte_estado_cuenta_vdor_vdos.php?vendedor=" +
            vendedor,
        "_blank",
    );
});

//Reporte de Cuentas
/* $(".box").on("click", ".btnReporteCrediPagos", function () {

  window.location = "vistas/reportes_excel/rpt_credipagos.php";

}) */

$(".btnReporteCrediPagos").click(function () {
    var inicio = document.getElementById("fechaInicio").value;
    var final = document.getElementById("fechaFin").value;

    var miCheckbox = document.getElementById("ctaNota");

    if (inicio == "") {
        var ini = document.getElementById("iniF").value;
    } else {
        var ini = inicio;
    }

    if (final == "") {
        var fin = document.getElementById("finF").value;
    } else {
        var fin = final;
    }

    if (miCheckbox.checked) {
        window.location =
            "vistas/reportes_excel/rpt_credipagosb.php?inicio=" +
            ini +
            "&fin=" +
            fin;
    } else {
        window.location =
            "vistas/reportes_excel/rpt_credipagos.php?inicio=" +
            ini +
            "&fin=" +
            fin;
    }
});

//Reporte de Cuentas
$(".box").on("click", ".btnDocContado", function () {
    window.open("vistas/reportes_ticket/documentos_contado.php", "_blank");
});

//Reporte de Cuentas
$(".box").on("click", ".btnPorAceptar", function () {
    var vendedor = prompt("Ingrese el codigo del vendedor", "");
    var mes = prompt("Ingrese el mes a consultar (ejemplo: 01 para Enero)", "");
    var url = "vistas/reportes_ticket/letras_aceptar.php?vendedor=" + vendedor;

    if (vendedor != "") {
        if (mes != "") {
            url += "&mes=" + mes;
        }
        window.open(url, "_blank");
    }
});

$(".box").on("click", ".btnProyeccionPagos", function () {
    window.open("vistas/reportes_ticket/proyeccion_pagos.php", "_blank");
});

function checkSubmitG() {
    document.getElementById("btnBlocClic").value = "Enviando...";
    document.getElementById("btnBlocClic").disabled = true;
    return true;
}

function checkSubmitGC() {
    document.getElementById("btnBlocClicC").value = "Enviando...";
    document.getElementById("btnBlocClicC").disabled = true;
    return true;
}

$(".btnRptPeds").click(function () {
    var vendedor = $(this).attr("vendedor");
    //console.log(vendedor);

    window.open(
        "vistas/reportes_ticket/pedidos_vendedor.php?vendedor=" + vendedor,
        "_blank",
    );
});

$(".btnRptResVtas").click(function () {
    let año = prompt("Ingrese el año a consultar (ejemplo: 2023)", "");

    if (año != "") {
        if (año.length != 4) {
            año = new Date().getFullYear();
        }

        window.open(
            "vistas/reportes_excel/rpt_resumen_vtas.php?año=" + año,
            "_blank",
        );
    } else {
        window.open(
            "vistas/reportes_excel/rpt_resumen_vtas.php?año=" + año,
            "_blank",
        );
    }
});

$(".btnRptResVtaMes").click(function () {
    let año = prompt("Ingrese el año a consultar (ejemplo: 2023)", "");
    let mes = $(this).attr("mes");

    if (año != "") {
        if (año.length != 4) {
            año = new Date().getFullYear();
        }

        window.open(
            "vistas/reportes_ticket/reporte_resumen_vtas.php?mes=" +
                mes +
                "&año=" +
                año,
            "_blank",
        );
    } else {
        window.open(
            "vistas/reportes_ticket/reporte_resumen_vtas.php?mes=" +
                mes +
                "&año=" +
                año,
            "_blank",
        );
    }
});

$(".btnRptResCobMes").click(function () {
    var mes = $(this).attr("mes");
    console.log(mes);

    window.open(
        "vistas/reportes_ticket/reporte_resumen_cobs.php?mes=" + mes,
        "_blank",
    );
});

$("#btnCargarPagos").click(function () {
    let clientePagos = document.getElementById("CodCliBtn").value;
    console.log(clientePagos);

    var datos = new FormData();
    datos.append("clientePagos", clientePagos);

    $.ajax({
        url: "ajax/cuentas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            console.table(respuesta);

            var $box = $(".nuevosPagos");
            $box.empty();

            if (!respuesta || respuesta.length === 0) {
                $box.html(
                    '<p class="text-muted text-center" style="margin:14px 10px;font-size:13px;">No hay pagos en el periodo para este cliente.</p>',
                );
                return;
            }

            var filas = "";
            for (var i = 0; i < respuesta.length; i++) {
                var r = respuesta[i];
                var periodo = (r["mes"] || "") + " " + (r["anno"] || "");
                filas +=
                    "<tr>" +
                    '<td style="white-space:nowrap;">' +
                    periodo +
                    "</td>" +
                    '<td class="text-right" style="font-variant-numeric: tabular-nums;">' +
                    (r["monto_jackyform"] || "0.00") +
                    "</td>" +
                    '<td class="text-right" style="font-variant-numeric: tabular-nums;">' +
                    (r["monto_rosalinda"] || "0.00") +
                    "</td>" +
                    '<td class="text-right" style="font-variant-numeric: tabular-nums;"><strong>' +
                    (r["monto"] || "0.00") +
                    "</strong></td>" +
                    "</tr>";
            }

            $box.html(
                '<table class="table table-condensed table-striped" style="margin:0;font-size:13px;">' +
                    "<thead>" +
                    "<tr>" +
                    "<th>Periodo</th>" +
                    '<th class="text-right" style="min-width:76px;">Jackyform</th>' +
                    '<th class="text-right" style="min-width:76px;">RosaFlor</th>' +
                    '<th class="text-right" style="min-width:76px;">Total</th>' +
                    "</tr>" +
                    "</thead>" +
                    "<tbody>" +
                    filas +
                    "</tbody>" +
                    "</table>",
            );
        },
    });
});

//
$(".tablaNotificacionesPendientes").DataTable({
    ajax: "ajax/cuentas-corrientes/tabla-notificaciones-pendientes.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[2, "asc"]],
    pageLength: 20,
    lengthMenu: [
        [30, 40, 60, -1],
        [30, 40, 60, "Todos"],
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

$(".tablaLetrasPlazoProtesto").DataTable({
    ajax: "ajax/cuentas-corrientes/tabla-letras-plazo-protesto.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [],
    pageLength: 50,
    lengthMenu: [
        [30, 50, 100, -1],
        [30, 50, 100, "Todos"],
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

const table = $(".tablaCredipagos").DataTable({
    ajax: "ajax/cuentas-corrientes/tabla-credipagos.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[2, "asc"]],
    pageLength: 100,
    lengthMenu: [
        [100, 200, 300, -1],
        [100, 200, 300, "Todos"],
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

const $eliminar = $("#eliminarSeleccionados");
const $contador = $("#contadorSeleccionados strong");

function actualizarEstado() {
    const count = $(".credipagoCheck:checked").length;
    $contador.text(count);
    $eliminar.prop("disabled", count === 0);
}

// Al hacer check/uncheck en cualquier checkbox de la tabla
$(".tablaCredipagos tbody").on("change", ".credipagoCheck", actualizarEstado);

// Botón seleccionar todos
$("#marcarTodos").on("click", function () {
    $(".credipagoCheck").prop("checked", true);
    $("#checkHeader").prop("checked", true);
    actualizarEstado();
});

// Botón desmarcar todos
$("#desmarcarTodos").on("click", function () {
    $(".credipagoCheck").prop("checked", false);
    $("#checkHeader").prop("checked", false);
    actualizarEstado();
});

// Checkbox en header para seleccionar/deseleccionar toda la página visible
$("#checkHeader").on("change", function () {
    const estado = $(this).is(":checked");
    $(".credipagoCheck").prop("checked", estado);
    actualizarEstado();
});

// Al cambiar página o filtrar, mantenemos el estado del header
table.on("draw", function () {
    const allChecked =
        $(".credipagoCheck").length === $(".credipagoCheck:checked").length &&
        $(".credipagoCheck").length > 0;
    $("#checkHeader").prop("checked", allChecked);
});

// Acción de eliminar seleccionados
document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("eliminarSeleccionados")
        .addEventListener("click", function () {
            // Recogemos los IDs seleccionados
            const checks = document.querySelectorAll(".credipagoCheck:checked");
            const ids = Array.from(checks).map((chk) =>
                chk.getAttribute("data-id-credipago"),
            );
            const count = ids.length;

            if (count === 0) {
                swal(
                    "Atención",
                    "Debes seleccionar al menos un CrediPago.",
                    "info",
                );
                return;
            }

            // SweetAlert clásico con botón Cancelar
            swal({
                title: `¿Eliminar ${count} elemento${count > 1 ? "s" : ""}?`,
                text: `Se eliminarán ${count} CrediPago${
                    count > 1 ? "s" : ""
                }.`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
            }).then(function (result) {
                if (result.value) {
                    // Confirmó: imprimimos en consola
                    console.log("IDs seleccionados:", ids);

                    let datos = new FormData();
                    datos.append("idsCredipagos", JSON.stringify(ids));

                    $.ajax({
                        url: "ajax/cuentas.ajax.php",
                        type: "POST",
                        data: datos,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (respuesta) {
                            respuesta = JSON.parse(respuesta);
                            status = respuesta.status;
                            message = respuesta.message;

                            if (status == 200) {
                                swal("Eliminados", message, "success").then(
                                    () => {
                                        window.location.reload();
                                    },
                                );
                            } else {
                                swal("Error", message, "error");
                            }
                        },
                    });
                }
            });
        });
});
