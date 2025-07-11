$(".tablaEquipos").DataTable({
    ajax: "ajax/mantenimiento/tabla-equipos.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[3, "asc"]],
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

$(".tablaCalendario").DataTable({
    ajax: "ajax/mantenimiento/tabla-calendario.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[2, "desc"]],
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

$(".TablaMantenimientoCabecera").DataTable({
    ajax: "ajax/mantenimiento/tabla-mante-cabecera.ajax.php",
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

//*ACTIVAR DETALLE
if (localStorage.getItem("manteCab") != null) {
    cargarTablaMantenimientoDetalle(localStorage.getItem("manteCab"));
    // console.log("lleno");
} else {
    cargarTablaMantenimientoDetalle(null);
    // console.log("vacio");
}

$(".TablaMantenimientoCabecera tbody").on(
    "click",
    "button.btnActivarDetalleMante",
    function () {
        $(".TablaMantenimientoDetalle").DataTable().destroy();

        var manteCab = $(this).attr("codigo");
        //console.log("codigo", manteCab);

        localStorage.setItem("manteCab", manteCab);
        cargarTablaMantenimientoDetalle(localStorage.getItem("manteCab"));
    }
);

function cargarTablaMantenimientoDetalle(manteCab) {
    $(".TablaMantenimientoDetalle").DataTable({
        ajax:
            "ajax/mantenimiento/tabla-mante-detalle.ajax.php?manteCab=" +
            manteCab,
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
}

$("#cargarTablaRpt").click(function () {
    $(".TablaMantenimientoRepuestos").DataTable().destroy();

    $("#divRpt").removeAttr("hidden");

    var manteDet = document.getElementById("nuevoId").value;
    console.log("hola mundo");

    localStorage.setItem("manteDet", manteDet);

    cargarTablaMantenimientoRepuesto(manteDet);
});

$("#ocultarTablaRpt").click(function () {
    $("#divRpt").attr("hidden", "");
});

$("#cargarTablaRptE").click(function () {
    $(".TablaMantenimientoRepuestos").DataTable().destroy();

    $("#divRptE").removeAttr("hidden");

    var manteDet = document.getElementById("editarId").value;
    console.log("hola mundo e");

    localStorage.setItem("manteDet", manteDet);

    cargarTablaMantenimientoRepuesto(manteDet);
});

$("#ocultarTablaRptE").click(function () {
    $("#divRptE").attr("hidden", "");
});

function cargarTablaMantenimientoRepuesto(manteDet) {
    $(".TablaMantenimientoRepuestos").DataTable({
        ajax:
            "ajax/mantenimiento/tabla-mante-repuestos.ajax.php?codInterno=" +
            manteDet,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
        pageLength: 10,
        lengthMenu: [
            [10, 20, 30, -1],
            [10, 20, 30, "Todos"],
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

//*crear cod tipo maquina
$("#nuevoTipMaq").change(function () {
    var tipoM = $(this).val();
    //console.log(tipoM);

    var datos = new FormData();
    datos.append("tipoM", tipoM);

    $.ajax({
        url: "ajax/mantenimiento.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#nuevoCodTipo").val(respuesta["nuevo"]);
        },
    });
});

//*editar maquina
$(".tablaEquipos").on("click", ".btnEditarEquipo", function () {
    var equipo = $(this).attr("idEquipo");
    //console.log(equipo);

    var datos = new FormData();
    datos.append("equipo", equipo);

    $.ajax({
        url: "ajax/mantenimiento.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);

            $("#editarTipMaq").val(respuesta["nombre_tipo_maquina"]);
            $("#editarCodTipo").val(respuesta["cod_tipo"]);
            $("#editarIdEquipo").val(respuesta["id"]);
            $("#editarDescripcion").val(respuesta["descripcion"]);
            $("#editarUbicacion").val(respuesta["cod_ubicacion"]);
            $("#editarUbicacion").selectpicker("refresh");
            $("#editarMarcaMaq").val(respuesta["cod_marca_equi"]);
            $("#editarMarcaMaq").selectpicker("refresh");
            $("#editarModeloMaq").val(respuesta["modelo_equipo"]);
            $("#editarSerieMaq").val(respuesta["serie_equipo"]);

            $("#editarTipoMotor").val(respuesta["tipo_motor"]);
            $("#editarMarcaMotor").val(respuesta["cod_marca_motor"]);
            $("#editarMarcaMotor").selectpicker("refresh");
            $("#editarModeloMotor").val(respuesta["modelo_motor"]);
            $("#editarSerieMotor").val(respuesta["serie_motor"]);

            $("#editarMarcaCaja").val(respuesta["cod_marca_caja"]);
            $("#editarMarcaCaja").selectpicker("refresh");
            $("#editarModeloCaja").val(respuesta["modelo_caja"]);
            $("#editarSerieCaja").val(respuesta["serie_caja"]);

            $("#editarDocumento").val(respuesta["documento"]);
            $("#editarRuc").val(respuesta["ruc"]);
            $("#editarFecEmision").val(respuesta["fecha_emision"]);

            $("#editarEstado").val(respuesta["estado"]);
            $("#editarEstado").selectpicker("refresh");
            $("#editarObservacion").val(respuesta["observaciones"]);

            if (
                respuesta["fec_pro_mant"] == "0000-00-00" ||
                respuesta["fec_pro_mant"] == null
            ) {
                $("#editarProgMantenimiento").val(respuesta["fec_pro_mant"]);
                document.getElementById(
                    "editarProgMantenimiento"
                ).readOnly = true;
            } else {
                $("#editarProgMantenimiento").val(respuesta["fec_pro_mant"]);
                document.getElementById(
                    "editarProgMantenimiento"
                ).readOnly = false;
            }

            $("#editarUltimoMantenimiento").val(respuesta["fec_ult_mant"]);
            document.getElementById(
                "editarUltimoMantenimiento"
            ).readOnly = true;
        },
    });
});

//*UBICACION DE MAQUINA
$("#nuevaMaquina").change(function () {
    var maq = $(this).val();
    //console.log(maq);

    var datos = new FormData();
    datos.append("maq", maq);

    $.ajax({
        url: "ajax/mantenimiento.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);
            $("#nuevaNombreUbicacion").val(respuesta["ubicacion_maquina"]);
            $("#nuevaUbicacion").val(respuesta["cod_ubicacion"]);
        },
    });
});

//*MANTENIMIENTO PREVENTIVO
$("#nuevoTipo").change(function () {
    var tipo = $(this).val();
    //console.log(tipo);

    if (tipo == "Preventivo") {
        $("#nuevoFin").val("");
        document.getElementById("nuevoFin").readOnly = true;

        $("#nuevoResponsable").val("");
        $("#nuevoResponsable").selectpicker("refresh");
        //document.getElementById("nuevoResponsable").disabled = true;

        document.getElementById("nuevoEstado").selectedIndex = 2;
        $("#nuevoEstado").val("NO HECHO");
        $("#nuevoEstado").selectpicker("refresh");
        //document.getElementById("nuevoEstado").disabled = true;

        $("#nuevoOperario").val("");
        $("#nuevoOperario").selectpicker("refresh");
        //document.getElementById("nuevoOperario").disabled = true;

        $("#nuevaObservacion").val("Mantenimiento Programado");
    } else if (tipo == "Correctivo") {
        document.getElementById("nuevoFin").readOnly = false;

        //document.getElementById("nuevoFin").disabled = false;

        //document.getElementById("nuevoResponsable").disabled = false;

        document.getElementById("nuevoEstado").selectedIndex = 1;
        $("#nuevoEstado").val("HECHO");
        //document.getElementById("nuevoEstado").disabled = false;
        $("#nuevoEstado").selectpicker("refresh");

        //document.getElementById("nuevoOperario").disabled = false;

        $("#nuevaObservacion").val("");
    }
});

var repuestosSeleccionados = [];

// Función para agregar o actualizar repuesto en el array y en el input hidden
function addOrUpdateRepuestoSeleccionado(codInterno, codpro, cospro, cantidad) {
    cantidad = parseInt(cantidad, 10);
    if (isNaN(cantidad) || cantidad < 1) {
        swal({
            title: "Cantidad inválida",
            text: "Por favor ingrese una cantidad válida.",
            type: "warning",
            confirmButtonText: "Aceptar",
        });
        return false;
    }
    // Buscar si ya existe
    var idx = repuestosSeleccionados.findIndex(function (item) {
        return item.codInterno === codInterno && item.codpro === codpro;
    });
    if (idx > -1) {
        repuestosSeleccionados[idx].cantidad = cantidad;
        $("#repuestosSeleccionados").val(
            JSON.stringify(repuestosSeleccionados)
        );
        return "update";
    } else {
        repuestosSeleccionados.push({
            codInterno: codInterno,
            codpro: codpro,
            cospro: cospro,
            cantidad: cantidad,
        });
        $("#repuestosSeleccionados").val(
            JSON.stringify(repuestosSeleccionados)
        );
        return "add";
    }
}

//*AGREGAR MANTENIMIENTO REPUESTOS
$(".TablaMantenimientoRepuestos").on("click", ".btnAddRpt", function () {
    var codInterno = $(this).attr("codInterno");
    var codpro = $(this).attr("codpro");
    var cospro = $(this).attr("cospro");
    var $fila = $(this).closest("tr");
    var cantidad = $fila.find("input.inputCantidad").val();
    var result = addOrUpdateRepuestoSeleccionado(
        codInterno,
        codpro,
        cospro,
        cantidad
    );
    if (result === "add" || result === "update") {
        // Cambiar el estado del botón y el texto
        $(this).removeClass("btn-primary btnAddRpt");
        $(this).addClass("btn-default");
        $(this).text("Actualizar");
    }
});

//*ACTUALIZAR CANTIDAD EN EL ARRAY AL CAMBIAR EL INPUT
$(".TablaMantenimientoRepuestos").on(
    "change",
    "input.inputCantidad",
    function () {
        var $fila = $(this).closest("tr");
        var cantidad = $(this).val();
        var $btn = $fila.find(".btnAddRpt, .btn-default[codInterno]");
        if ($btn.length) {
            var codInterno = $btn.attr("codInterno");
            var codpro = $btn.attr("codpro");
            var cospro = $btn.attr("cospro");
            var result = addOrUpdateRepuestoSeleccionado(
                codInterno,
                codpro,
                cospro,
                cantidad
            );
            if (result === "update") {
                $btn.text("Actualizar");
            }
        }
    }
);

//*EDITAR MANTENIMIENTO
$(".TablaMantenimientoCabecera").on(
    "click",
    ".btnEditarMantenimiento",
    function () {
        var idMantenimiento = $(this).attr("idMantenimiento");
        //console.log(idMantenimiento);

        var datos = new FormData();
        datos.append("idMantenimiento", idMantenimiento);

        $.ajax({
            url: "ajax/mantenimiento.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                //console.log(respuesta);

                $("#editarId").val(respuesta["cod_interno"]);
                $("#id").val(respuesta["id"]);
                $("#editarTipo").val(respuesta["tipo_mante"]);
                $("#editarInicio").val(respuesta["mante_inicio_a"]);
                $("#editarFin").val(respuesta["mante_fin_a"]);

                $("#editarMaquina").val(respuesta["descripcion"]);
                $("#editarMaquinaCod").val(respuesta["cod_maquina"]);

                $("#editarNombreUbicacion").val(respuesta["ubicacion_maquina"]);

                $("#editarResponsable").val(respuesta["responsable"]);
                $("#editarResponsable").selectpicker("refresh");

                $("#editarEstado").val(respuesta["estado"]);
                $("#editarEstado").selectpicker("refresh");

                $("#editarOperario").val(respuesta["operario"]);
                $("#editarOperario").selectpicker("refresh");

                $("#editarObservacion").val(respuesta["observaciones"]);

                $(".TablaMantenimientoDetalle").DataTable().destroy();
                localStorage.setItem("manteCab", respuesta["cod_interno"]);
                cargarTablaMantenimientoDetalle(
                    localStorage.getItem("manteCab")
                );
            },
        });
    }
);

//*EDITAR MANTENIMIENTO DETALLE
$(".TablaMantenimientoDetalle").on("click", ".btnEditarRepuesto", function () {
    var idDetMante = $(this).attr("idDetMante");
    //console.log(idDetMante);

    var datos = new FormData();
    datos.append("idDetMante", idDetMante);

    $.ajax({
        url: "ajax/mantenimiento.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);

            $("#editarIdD").val(respuesta["cod_interno"]);
            $("#idD").val(respuesta["id"]);
            $("#editarNombre").val(respuesta["item"]);
            $("#editarCantidadD").val(respuesta["cantidad"]);
            $("#editarPrecio").val(respuesta["precio"]);
            $("#editarObservacionD").val(respuesta["observacion"]);
        },
    });
});

//* ANULAR DETALLE
$(".TablaMantenimientoDetalle").on("click", ".btnAnularRepuestos", function () {
    var idDetMante = $(this).attr("idDetMante");
    //console.log(idDetMante);

    // Capturamos el id de la orden de compra
    swal({
        title: "¿Está seguro de anular el repuesto?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, anular repuesto!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=mantenimiento&idDetMante=" + idDetMante;
        }
    });
});

//*editar CALENDARIO
$(".tablaCalendario").on("click", ".btnEditaCalendario", function () {
    var idCalendario = $(this).attr("idCalendario");
    //console.log(calendario);

    var datos = new FormData();
    datos.append("idCalendario", idCalendario);

    $.ajax({
        url: "ajax/mantenimiento.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);
            $("#id").val(respuesta["id"]);
            $("#editarTipo").val(respuesta["tipo"]);
            $("#editarTipo").selectpicker("refresh");
            $("#editarInicio").val(respuesta["inicio_a"]);
            $("#editarFin").val(respuesta["fin_a"]);
            $("#editarTitulo").val(respuesta["titulo"]);
            $("#editarObservacion").val(respuesta["indicaciones"]);
        },
    });
});

//* ANULAR CALENDARIO
$(".tablaCalendario").on("click", ".btnAnularCalendario", function () {
    var idCalendario = $(this).attr("idCalendario");
    console.log(idCalendario);

    // Capturamos el id de la orden de compra
    swal({
        title: "¿Está seguro de anular la actividad?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, anular actividad!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=calendario&idCalendario=" + idCalendario;
        }
    });
});

//Reporte de Salidas
$(".box").on("click", ".btnReporteEquipos", function () {
    window.location = "vistas/reportes_excel/rpt_maquinas.php";
});
