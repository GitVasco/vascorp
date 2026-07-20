//MATERIA PRIMA CON PAGINACIÓN CLIENTE (ORIGINAL)
function cargarTablaMateriaPrima() {
    $(".tablaMateriaPrima").DataTable({
        ajax: "ajax/materiaprima/tabla-materiaprima.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
        pageLength: 20,
        lengthMenu: [
            [20, 40, 60, -1],
            [20, 40, 60, "Todos"],
        ],
        columnDefs: [
            {
                targets: -1,
                orderable: false,
                searchable: false,
                className: "text-center",
                width: "170px",
            },
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

//MATERIA PRIMA CON PAGINACIÓN SERVIDOR (OPTIMIZADO)
function cargarTablaMateriaPrimaPaginado() {
    // Destruir tabla existente si existe
    if ($.fn.DataTable.isDataTable(".tablaMateriaPrimaPaginado")) {
        $(".tablaMateriaPrimaPaginado").DataTable().destroy();
    }

    var table = $(".tablaMateriaPrimaPaginado").DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 800, // Esperar 800ms después de que el usuario deje de escribir antes de buscar
        ajax: {
            url: "ajax/materiaprima/tabla-materiaprima-paginado.ajax.php",
            type: "GET",
            data: function (d) {
                // Opción 1: Mínimo de caracteres para buscar (recomendado para tablas grandes)
                // Solo buscar si tiene al menos 3 caracteres o está vacío (para limpiar)
                var minChars = 3;
                if (d.search.value.length > 0 && d.search.value.length < minChars) {
                    // Cancelar la búsqueda si tiene menos del mínimo
                    // Esto evita búsquedas con 1 o 2 caracteres que pueden ser muy lentas
                    d.search.value = "";
                }
            }
        },
        order: [[0, "desc"]],
        pageLength: 20,
        lengthMenu: [
            [20, 40, 60, 100],
            [20, 40, 60, 100],
        ],
        columnDefs: [
            {
                targets: -1,
                orderable: false,
                searchable: false,
                className: "text-center",
                width: "170px",
            },
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
 * tabla paraa cargar la lista de materia - URGENCIA
 */
$(".tablaUrgenciasAMP").DataTable({
    ajax:
        "ajax/maestros/tabla-urgenciasamp.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[6, "desc"]],
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
 * EDITAR NOMBRE MATERIA PRIMA
 */
// Función común para manejar la edición
function editarMateriaPrima(idMateriaPrima) {
    var datos = new FormData();
    datos.append("idMateriaPrima", idMateriaPrima);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            var linea = respuesta["FamPro"].substr(0, 3);
            var sublinea = respuesta["FamPro"].substr(3, 6);
            var fecha = respuesta["FecReg"].substr(0, 10);
            // console.log(linea);
            // console.log(sublinea);

            var datos2 = new FormData();
            datos2.append("linea2", linea);
            datos2.append("sublinea", sublinea);

            $.ajax({
                url: "ajax/materiaprima.ajax.php",
                method: "POST",
                data: datos2,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta2) {
                    $("#editarSubLinea2").val(
                        respuesta2["Valor_3"] +
                            " - " +
                            respuesta2["Des_Larga"]
                    );
                    $("#editarSubLinea").val(respuesta2["Valor_3"]);
                },
            });
            $("#editarCodigoPro").val(respuesta["codpro"]);
            $("#editarCodigoFab").val(respuesta["codfab"]);
            $("#editarCodigoAlt").val(respuesta["CodAlt"]);
            $("#editarFechaEmision").val(fecha);
            $("#editarLinea").val(linea);
            $("#editarUsuarioReg").val(respuesta["UsuReg"]);
            $("#editarLinea").selectpicker("refresh");
            $("#editarColorMateria").val(respuesta["ColPro"]);
            $("#editarColorMateria").selectpicker("refresh");
            $("#editarTallaMateria").val(respuesta["TalPro"]);
            $("#editarTallaMateria").selectpicker("refresh");
            $("#editarUnidadMedida").val(respuesta["UndPro"]);
            $("#editarUnidadMedida").selectpicker("refresh");
            $("#editarPeso").val(respuesta["PesPro"]);
            $("#editarAdVal").val(respuesta["Por_Adval"]);
            $("#editarSeguro").val(respuesta["Por_Seg"]);
            $("#editarStockActual").val(respuesta["Stk_Act"]);
            $("#editarStockMinimo").val(respuesta["Stk_Min"]);
            $("#editarStockMaximo").val(respuesta["Stk_Max"]);
            $("#editarDescripcion").val(respuesta["despro"]);
            $("#editarProveedor").val(respuesta["CodProv1"]);
            $("#editarProveedor").selectpicker("refresh");
            $("#editarMoneda").val(respuesta["MonProv1"]);
            $("#editarMoneda").selectpicker("refresh");
            $("#editarPrecioSIGV").val(respuesta["PreProv1"] || "0");
            $("#editarProveedor1").val(respuesta["CodProv2"]);
            $("#editarProveedor1").selectpicker("refresh");
            $("#editarMoneda1").val(respuesta["MonProv2"]);
            $("#editarMoneda1").selectpicker("refresh");
            $("#editarPrecioSIGV1").val(respuesta["PreProv2"] || "0");
            $("#editarProveedor2").val(respuesta["CodProv3"]);
            $("#editarProveedor2").selectpicker("refresh");
            $("#editarMoneda2").val(respuesta["MonProv3"]);
            $("#editarMoneda2").selectpicker("refresh");
            $("#editarPrecioSIGV2").val(respuesta["PreProv3"] || "0");
            $("#editarObservacion1").val(respuesta["ObsProv1"]);
            $("#editarObservacion2").val(respuesta["ObsProv2"]);
            $("#editarObservacion3").val(respuesta["ObsProv3"]);
        },
    });
}

// Evento para tabla cliente
$(".tablaMateriaPrima tbody").on(
    "click",
    "button.btnEditarMateriaPrima",
    function () {
        var idMateriaPrima = $(this).attr("idMateriaPrima");
        editarMateriaPrima(idMateriaPrima);
    }
);

// Evento para tabla servidor
$(".tablaMateriaPrimaPaginado tbody").on(
    "click",
    "button.btnEditarMateriaPrima",
    function () {
        var idMateriaPrima = $(this).attr("idMateriaPrima");
        editarMateriaPrima(idMateriaPrima);
    }
);

/*
 * VISUALIZAR DETALLE DE ARTICULOS QUE LLEVAN ESA MATERIA PRIMA
 */
// Función común para visualizar artículos
function visualizarArticulos(articuloMP) {
    var datos = new FormData();
    datos.append("articuloMP", articuloMP);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            /* console.log("respuesta", respuesta); */

            $("#codpro").val(respuesta["codpro"]);

            $("#codLinea").val(respuesta["codlinea"]);

            $("#linea").val(respuesta["linea"]);

            $("#codfab").val(respuesta["codfab"]);

            $("#descripcion").val(respuesta["descripcion"]);

            $("#unidad").val(respuesta["unidad"]);

            $("#color").val(respuesta["color"]);

            $("#salidasT").val(respuesta["canvta"]);

            $("#prom").val(respuesta["prom"]);

            $("#stock").val(respuesta["stock"]);

            $("#proveedor").val(respuesta["proveedor"]);
        },
    });

    var articuloMPDetalle = articuloMP;

    /* console.log("articuloMPDetalle", articuloMPDetalle); */

    var datosDetalle = new FormData();
    datosDetalle.append("articuloMPDetalle", articuloMPDetalle);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datosDetalle,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            /* console.log("respuestaDetalle", respuestaDetalle); */

            $(".detalleMP").remove();

            for (var id of respuestaDetalle) {
                $(".tablaDetalleArticulo").append(
                    '<tr class="detalleMP">' +
                        "<td>" +
                        id.articulo +
                        " </td>" +
                        "<td>" +
                        id.modelo +
                        " </td>" +
                        "<td>" +
                        id.nombre +
                        " </td>" +
                        "<td>" +
                        id.color +
                        " </td>" +
                        "<td>" +
                        id.talla +
                        " </td>" +
                        "<td>" +
                        id.estado +
                        " </td>" +
                        "<td>" +
                        id.consumo +
                        " </td>" +
                        "<td>" +
                        id.tej_princ +
                        " </td>" +
                        "</tr>"
                );
            }
        },
    });
}

// Evento para tabla cliente
$(".tablaMateriaPrima").on("click", ".btnVisualizarArticulos", function () {
    var articuloMP = $(this).attr("articuloMP");
    visualizarArticulos(articuloMP);
});

// Evento para tabla servidor
$(".tablaMateriaPrimaPaginado").on("click", ".btnVisualizarArticulos", function () {
    var articuloMP = $(this).attr("articuloMP");
    visualizarArticulos(articuloMP);
});

/*
 * VISUALIZAR ÓRDENES DE COMPRA / SERVICIO DE LA MP
 */
function visualizarOrdenesMp(codpro) {
    var datos = new FormData();
    datos.append("ordenesMp", codpro);

    $("#ocos_codpro").val("");
    $("#ocos_codfab").val("");
    $("#ocos_descripcion").val("");
    $("#ocos_color").val("");
    $("#ocos_stock").val("");
    $("#ocos_oc_count").text("0");
    $("#ocos_os_count").text("0");
    $("#ocos_oc_body").html(
        '<tr class="ocos-empty"><td colspan="8" class="text-center text-muted">Cargando...</td></tr>'
    );
    $("#ocos_os_body").html(
        '<tr class="ocos-empty"><td colspan="9" class="text-center text-muted">Cargando...</td></tr>'
    );

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            var mp = respuesta.mp || {};
            $("#ocos_codpro").val(mp.codpro || mp.CodPro || "");
            $("#ocos_codfab").val(mp.codfab || mp.CodFab || "");
            $("#ocos_descripcion").val(mp.descripcion || mp.despro || mp.DesPro || "");
            $("#ocos_color").val(mp.color || "");
            $("#ocos_stock").val(mp.stock || mp.CodAlm01 || "");

            var oc = respuesta.oc || [];
            $("#ocos_oc_count").text(oc.length);
            if (oc.length === 0) {
                $("#ocos_oc_body").html(
                    '<tr class="ocos-empty"><td colspan="8" class="text-center text-muted">Sin órdenes de compra pendientes</td></tr>'
                );
            } else {
                var htmlOc = "";
                for (var i = 0; i < oc.length; i++) {
                    var estadoOc =
                        oc[i].estac === "ABI"
                            ? "<span class='label label-success'>ABI</span>"
                            : "<span class='label label-warning'>PAR</span>";
                    htmlOc +=
                        "<tr>" +
                        "<td>" + (oc[i].nro || "") + "</td>" +
                        "<td>" + (oc[i].fecemi || "") + "</td>" +
                        "<td>" + (oc[i].fecllegada || "") + "</td>" +
                        "<td>" + (oc[i].proveedor || "") + "</td>" +
                        "<td>" + (oc[i].cantidad || "") + "</td>" +
                        "<td>" + (oc[i].saldo || "") + "</td>" +
                        "<td>" + estadoOc + "</td>" +
                        "<td>" + (oc[i].precio || "") + "</td>" +
                        "</tr>";
                }
                $("#ocos_oc_body").html(htmlOc);
            }

            var os = respuesta.os || [];
            $("#ocos_os_count").text(os.length);
            if (os.length === 0) {
                $("#ocos_os_body").html(
                    '<tr class="ocos-empty"><td colspan="9" class="text-center text-muted">Sin órdenes de servicio pendientes</td></tr>'
                );
            } else {
                var htmlOs = "";
                for (var j = 0; j < os.length; j++) {
                    var estadoOs =
                        os[j].estos === "ABI"
                            ? "<span class='label label-success'>ABI</span>"
                            : "<span class='label label-warning'>PAR</span>";
                    var rolLabel =
                        os[j].rol === "ORIGEN"
                            ? "<span class='label label-info'>ORIGEN</span>"
                            : "<span class='label label-primary'>DESTINO</span>";
                    htmlOs +=
                        "<tr>" +
                        "<td>" + (os[j].nro || "") + "</td>" +
                        "<td>" + (os[j].fecemi || "") + "</td>" +
                        "<td>" + (os[j].fecent || "") + "</td>" +
                        "<td>" + rolLabel + "</td>" +
                        "<td>" + (os[j].codpro_origen || "") + " - " + (os[j].des_origen || "") + "</td>" +
                        "<td>" + (os[j].codpro_destino || "") + " - " + (os[j].des_destino || "") + "</td>" +
                        "<td>" + (os[j].cantidad || "") + "</td>" +
                        "<td>" + (os[j].saldo || "") + "</td>" +
                        "<td>" + estadoOs + "</td>" +
                        "</tr>";
                }
                $("#ocos_os_body").html(htmlOs);
            }
        },
        error: function () {
            $("#ocos_oc_body").html(
                '<tr class="ocos-empty"><td colspan="8" class="text-center text-danger">Error al cargar órdenes de compra</td></tr>'
            );
            $("#ocos_os_body").html(
                '<tr class="ocos-empty"><td colspan="9" class="text-center text-danger">Error al cargar órdenes de servicio</td></tr>'
            );
        },
    });
}

$(".tablaMateriaPrima").on("click", ".btnOrdenesMp", function () {
    visualizarOrdenesMp($(this).attr("codpro"));
});

$(".tablaMateriaPrimaPaginado").on("click", ".btnOrdenesMp", function () {
    visualizarOrdenesMp($(this).attr("codpro"));
});

/*
 * EDITAR COSTO MATERIA PRIMA
 */
// Función común para editar costo
function editarCostoMateriaPrima(materiaPrima) {
    var datosCosto = new FormData();
    datosCosto.append("materiaPrima", materiaPrima);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datosCosto,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaCostos) {
            /* console.log("codpro", respuestaCostos["Codpro"]); */

            /* console.log("respuestaCostos", respuestaCostos); */

            $("#codigo").val(respuestaCostos["codpro"]);

            $("#descripcionMP").val(respuestaCostos["descripcion"]);

            $("#colorMP").val(respuestaCostos["color"]);

            $("#costo").val(respuestaCostos["cospro"]);
        },
    });
}

// Evento para tabla cliente
$(".tablaMateriaPrima tbody").on("click", "button.btnEditarCosto", function () {
    var materiaPrima = $(this).attr("materiaPrima");
    editarCostoMateriaPrima(materiaPrima);
});

// Evento para tabla servidor
$(".tablaMateriaPrimaPaginado tbody").on("click", "button.btnEditarCosto", function () {
    var materiaPrima = $(this).attr("materiaPrima");
    editarCostoMateriaPrima(materiaPrima);
});

/* 
! PROYECCION DE ORDEN DE CORTE
*/

/*
 * BOTON ACEPTAR
 */
$("#proyMp").change(function () {
    $(".tablaProyMp").DataTable().destroy();

    var proyMp = $(this).val();
    //console.log(lineaMp);
    $(".btnReporteProyeccion").attr("corte", proyMp);
    localStorage.setItem("proyMp", proyMp);

    cargarTablaProyMp(localStorage.getItem("proyMp"));
});

/*
 * BOTON LIMPIAR
 */
$(".box").on("click", ".btnLimpiarProyMp", function () {
    localStorage.removeItem("proyMp");
    localStorage.clear();
    $(".btnReporteProyeccion").attr("corte", "");
    window.location = "proyeccion-mp";
});

/*
 * VEMOS SI LOCAL STORAGE TRAE ALGO
 */
if (localStorage.getItem("proyMp") != null) {
    $("#proyMp").val(localStorage.getItem("proyMp"));
    $("#proyMp").selectpicker("refresh");

    cargarTablaProyMp(localStorage.getItem("proyMp"));
    //console.log("lleno");
} else {
    cargarTablaProyMp(null);
    //console.log("vacio");
}

/*
 * TABLA MOVIMIENTOS
 */
function cargarTablaProyMp(proyMp) {
    $(".tablaProyMp").DataTable({
        ajax:
            "ajax/maestros/tabla-proymp.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&proyMp=" +
            proyMp,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 30,
        lengthMenu: [
            [30, 60, 90, -1],
            [30, 60, 90, "Todos"],
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "No hay datos disponibles en esta tabla",
            sInfo: "Registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Registros del 0 al 0 de un total de 0",
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

//Reporte de Salidas
$(".box").on("click", ".btnReporteProyeccion", function () {
    var corte = $(this).attr("corte");
    window.location =
        "vistas/reportes_excel/rpt_proyeccion_mp.php?corte=" + corte;
});

//Reporte de Marca
$(".box").on("click", ".btnReporteMateria", function () {
    var linea = prompt("Elegir la Linea a exportar", "TODOS");
    console.log("🚀 ~ file: materiaprima.js:413 ~ linea", linea);

    window.location =
        "vistas/reportes_excel/rpt_materiaprima.php?linea=" + linea;
});

// GENERAR CODIGO AL CREAR MATERIA PRIMA  Y CARGAMOS EL SELECT DE SUBLINEA

$("#nuevaLinea").change(function () {
    var linea = $(this).val();
    var talla = $("#nuevaTallaMateria").val();
    var color = $("#nuevoColorMateria").val();
    $("#nuevoCodigoFab").val(linea + color + talla);

    var datos = new FormData();

    datos.append("linea", linea);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#nuevaSubLinea").find("option").remove();
            $("#nuevaSubLinea").append(
                "<option value='' > SELECCIONAR SUBLINEA </option>"
            );
            for (let i = 0; i < respuesta.length; i++) {
                $("#nuevaSubLinea").append(
                    "<option value='" +
                        respuesta[i]["Valor_3"] +
                        "'>" +
                        respuesta[i]["Valor_3"] +
                        " - " +
                        respuesta[i]["Des_Larga"] +
                        "</option>"
                );
            }
            $("#nuevaSubLinea").selectpicker("refresh");
        },
    });
});

// GENERAR CODIGO AL CREAR MATERIA PRIMA

$("#nuevaSubLinea").change(function () {
    var sublinea = $(this).val();
    var linea = $("#nuevaLinea").val();
    var talla = $("#nuevaTallaMateria").val();
    var color = $("#nuevoColorMateria").val();
    $("#nuevoCodigoFab").val(linea + sublinea + color + talla);
    var texto = $(this).find("option:selected").text();
    var descripcion = texto.substr(6);
    $("#nuevaDescripcionMat").val(descripcion);
});

// GENERAR CODIGO AL CREAR MATERIA PRIMA
$("#nuevoColorMateria").change(function () {
    var color = $(this).val();
    var linea = $("#nuevaLinea").val();
    var sublinea = $("#nuevaSubLinea").val();
    var talla = $("#nuevaTallaMateria").val();
    $("#nuevoCodigoFab").val(linea + sublinea + color + talla);
});

// GENERAR CODIGO AL CREAR MATERIA PRIMA  Y VALIDAMOS SI EXISTE EN LA TABLA PRODUCTO

$("#nuevaTallaMateria").change(function () {
    var talla = $(this).val();
    var color = $("#nuevoColorMateria").val();
    var linea = $("#nuevaLinea").val();
    var sublinea = $("#nuevaSubLinea").val();
    $("#nuevoCodigoFab").val(linea + sublinea + color + talla);

    var CodigoFab = linea + sublinea + color + talla;
    var datos = new FormData();
    datos.append("CodigoFab", CodigoFab);
    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            console.log(respuesta.length);
            if (respuesta) {
                if ($(".msgError").length == 0) {
                    $("#alertaCodigoFab")
                        .parent()
                        .after(
                            '<div class="alert alert-danger alert-dismissable msgError" id="mensajeError">' +
                                '<a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>' +
                                "<strong>Error!</strong> El Cod. Fab. ya existe en la Base de Datos, por favor verifique." +
                                "</div>"
                        );
                    $("#nuevoCodigoFab").val(linea + sublinea + color);
                    $("#nuevaTallaMateria").val("");
                    $("#nuevaTallaMateria").focus("");
                    $("#nuevaTallaMateria").selectpicker("refresh");
                }
            } else {
                $(".msgError").remove();
            }
        },
    });
});

//SELECT PARA CARGAR MONEDA DEL PROVEEDOR 1 AL CREAR MATERIA PRIMA
$("#nuevoProveedor").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#nuevaMoneda").val(respuesta["Des_Larga"]);
        },
    });
});

//SELECT PARA CARGAR MONEDA DEL PROVEEDOR 2 AL CREAR MATERIA PRIMA
$("#nuevoProveedor1").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#nuevaMoneda1").val(respuesta["Des_Larga"]);
        },
    });
});

//SELECT PARA CARGAR MONEDA DEL PROVEEDOR 3 AL CREAR MATERIA PRIMA
$("#nuevoProveedor2").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#nuevaMoneda2").val(respuesta["Des_Larga"]);
        },
    });
});

//EDITAR MATERIA PRIMA PROVEEDOR CON SELECT DE MONEDA
$("#editarProveedor").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#editarMoneda").val(respuesta["Des_Larga"]);
            $("#editarMoneda").selectpicker("refresh");
        },
    });
});

//EDITAR MATERIA PRIMA PROVEEDOR CON SELECT DE MONEDA
$("#editarProveedor1").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#editarMoneda1").val(respuesta["Des_Larga"]);
            $("#editarMoneda1").selectpicker("refresh");
        },
    });
});

//EDITAR MATERIA PRIMA PROVEEDOR CON SELECT DE MONEDA
$("#editarProveedor2").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#editarMoneda2").val(respuesta["Des_Larga"]);
            $("#editarMoneda2").selectpicker("refresh");
        },
    });
});

//Botones para limpiar la fila de proveedores 1 al editar materia prima
$("#btnLimpiarProveedor1").click(function () {
    $("#editarProveedor").val("");
    $("#editarProveedor").selectpicker("refresh");
    $("#editarMoneda").val("");
    $("#editarMoneda").selectpicker("refresh");
    $("#editarPrecioSIGV").val("0.00");
});
//Botones para limpiar la fila de proveedores 2 al editar materia prima
$("#btnLimpiarProveedor2").click(function () {
    $("#editarProveedor1").val("");
    $("#editarProveedor1").selectpicker("refresh");
    $("#editarMoneda1").val("");
    $("#editarMoneda1").selectpicker("refresh");
    $("#editarPrecioSIGV1").val("0.00");
});
//Botones para limpiar la fila de proveedores 3 al editar materia prima
$("#btnLimpiarProveedor3").click(function () {
    $("#editarProveedor2").val("");
    $("#editarProveedor2").selectpicker("refresh");
    $("#editarMoneda2").val("");
    $("#editarMoneda2").selectpicker("refresh");
    $("#editarPrecioSIGV2").val("0.00");
});

/*
 * DUPLICAR MATERIA PRIMA
 */
// Función común para duplicar materia prima
function duplicarMateriaPrima(idMateriaPrima) {
    var datos = new FormData();
    datos.append("idMateriaPrima", idMateriaPrima);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            var linea = respuesta["FamPro"].substr(0, 3);
            var sublinea = respuesta["FamPro"].substr(3, 6);
            var datos2 = new FormData();
            datos2.append("linea2", linea);
            datos2.append("sublinea", sublinea);

            $.ajax({
                url: "ajax/materiaprima.ajax.php",
                method: "POST",
                data: datos2,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta2) {
                    $("#duplicarSubLinea2").val(
                        respuesta2["Valor_3"] +
                            " - " +
                            respuesta2["Des_Larga"]
                    );
                    $("#duplicarSubLinea").val(respuesta2["Valor_3"]);
                },
            });
            $("#duplicarCodigoPro").val(respuesta["codpro"]);
            $("#duplicarCodigoFab").val(respuesta["codfab"]);
            $("#duplicarCodigoAlt").val(respuesta["CodAlt"]);
            $("#duplicarLinea").val(linea);
            $("#duplicarLinea").selectpicker("refresh");
            $("#duplicarColorMateria").val(respuesta["ColPro"]);
            $("#duplicarFamPro").val(respuesta["FamPro"]);
            $("#duplicarColorMateria").selectpicker("refresh");
            $("#duplicarTallaMateria").val(respuesta["TalPro"]);
            $("#duplicarTallaMateria").selectpicker("refresh");
            $("#duplicarUnidadMedida").val(respuesta["UndPro"]);
            $("#duplicarUnidadMedida").selectpicker("refresh");
            $("#duplicarPeso").val(respuesta["PesPro"]);
            $("#duplicarAdVal").val(respuesta["Por_Adval"]);
            $("#duplicarSeguro").val(respuesta["Por_Seg"]);
            $("#duplicarStockActual").val(respuesta["Stk_Act"]);
            $("#duplicarStockMinimo").val(respuesta["Stk_Min"]);
            $("#duplicarStockMaximo").val(respuesta["Stk_Max"]);
            $("#duplicarDescripcion").val(respuesta["despro"]);
            $("#duplicarProveedor").val(respuesta["CodProv1"]);
            $("#duplicarProveedor").selectpicker("refresh");
            $("#duplicarMoneda").val(respuesta["MonProv1"]);
            $("#duplicarMoneda").selectpicker("refresh");
            $("#duplicarPrecioSIGV").val(respuesta["PreProv1"] || "0");
            $("#duplicarProveedor1").val(respuesta["CodProv2"]);
            $("#duplicarProveedor1").selectpicker("refresh");
            $("#duplicarMoneda1").val(respuesta["MonProv2"]);
            $("#duplicarMoneda1").selectpicker("refresh");
            $("#duplicarPrecioSIGV1").val(respuesta["PreProv2"] || "0");
            $("#duplicarProveedor2").val(respuesta["CodProv3"]);
            $("#duplicarProveedor2").selectpicker("refresh");
            $("#duplicarMoneda2").val(respuesta["MonProv3"]);
            $("#duplicarMoneda2").selectpicker("refresh");
            $("#duplicarPrecioSIGV2").val(respuesta["PreProv3"] || "0");
            $("#duplicarObservacion1").val(respuesta["ObsProv1"]);
            $("#duplicarObservacion2").val(respuesta["ObsProv2"]);
            $("#duplicarObservacion3").val(respuesta["ObsProv3"]);
        },
    });
}

// Evento para tabla cliente
$(".tablaMateriaPrima tbody").on(
    "click",
    "button.btnDuplicarMateriaPrima",
    function () {
        var idMateriaPrima = $(this).attr("idMateriaPrima");
        duplicarMateriaPrima(idMateriaPrima);
    }
);

// Evento para tabla servidor
$(".tablaMateriaPrimaPaginado tbody").on(
    "click",
    "button.btnDuplicarMateriaPrima",
    function () {
        var idMateriaPrima = $(this).attr("idMateriaPrima");
        duplicarMateriaPrima(idMateriaPrima);
    }
);

// GENERAR CODIGO AL CREAR NUEVO COLOR MEDIANTE EL BOTON

$("#duplicarColorMateria").change(function () {
    var color = $(this).val();
    var linea = $("#duplicarLinea").val();
    var sublinea = $("#duplicarSubLinea").val();
    var talla = $("#duplicarTallaMateria").val();
    $("#duplicarCodigoFab").val(linea + sublinea + color + talla);
});

// GENERAR CODIGO AL CREAR NUEVO COLOR MEDIANTE EL BOTON Y VALIDAR SI EXISTE EL CODIGO DE FABRICA EN LA TABLA PRODUCTO

$("#duplicarTallaMateria").change(function () {
    var talla = $(this).val();
    var color = $("#duplicarColorMateria").val();
    var linea = $("#duplicarLinea").val();
    var sublinea = $("#duplicarSubLinea").val();
    $("#duplicarCodigoFab").val(linea + sublinea + color + talla);

    var CodigoFab = linea + sublinea + color + talla;
    var datos = new FormData();
    datos.append("CodigoFab", CodigoFab);
    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            if (respuesta) {
                if ($(".msgError").length == 0) {
                    $("#alertaCodigoFab2")
                        .parent()
                        .after(
                            '<div class="alert alert-danger alert-dismissable msgError" id="mensajeError">' +
                                '<a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>' +
                                "<strong>Error!</strong> El Cod. Fab. ya existe en la Base de Datos, por favor verifique." +
                                "</div>"
                        );

                    $("#duplicarCodigoFab").val(linea + sublinea + color);
                    $("#duplicarTallaMateria").val("");
                    $("#duplicarTallaMateria").focus("");
                    $("#duplicarTallaMateria").selectpicker("refresh");
                }
            } else {
                $(".msgError").remove();
            }
        },
    });
});

//DUPLICAR MATERIA PRIMA PROVEEDOR 1 CON SELECT DE MONEDA
$("#duplicarProveedor").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#duplicarMoneda").val(respuesta["Des_Larga"]);
            $("#duplicarMoneda").selectpicker("refresh");
        },
    });
});

//DUPLICAR MATERIA PRIMA PROVEEDOR  2 CON SELECT DE MONEDA
$("#duplicarProveedor1").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#duplicarMoneda1").val(respuesta["Des_Larga"]);
            $("#duplicarMoneda1").selectpicker("refresh");
        },
    });
});

//DUPLICAR MATERIA PRIMA PROVEEDOR 3 CON SELECT DE MONEDA
$("#editarProveedor2").change(function () {
    var CodRuc = $(this).val();
    var datos = new FormData();
    datos.append("CodRuc", CodRuc);

    $.ajax({
        url: "ajax/proveedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#duplicarMoneda2").val(respuesta["Des_Larga"]);
            $("#duplicarMoneda2").selectpicker("refresh");
        },
    });
});

/*=============================================
ANULAR MATERIA PRIMA
=============================================*/
// Función común para anular materia prima
function anularMateriaPrima(idMateriaPrima) {
    swal({
        title: "¿Está seguro de anular la materia prima?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, anular materia prima!",
    }).then(function (result) {
        if (result.value) {
            // Determinar la ruta según la configuración
            var ruta = window.RUTA_MATERIAPRIMA || "materiaprima";
            window.location =
                "index.php?ruta=" + ruta + "&idMateriaPrima=" + idMateriaPrima;
        }
    });
}

// Evento para tabla cliente
$(".tablaMateriaPrima").on("click", ".btnAnularMateriaPrima", function () {
    var idMateriaPrima = $(this).attr("idMateriaPrima");
    anularMateriaPrima(idMateriaPrima);
});

// Evento para tabla servidor
$(".tablaMateriaPrimaPaginado").on("click", ".btnAnularMateriaPrima", function () {
    var idMateriaPrima = $(this).attr("idMateriaPrima");
    anularMateriaPrima(idMateriaPrima);
});

/*=============================================
GUARDAR CAMBIOS DE MATERIA PRIMA
=============================================*/
$("#formularioEditarMateria").on(
    "click",
    ".btnEditarCambiosMateria",
    function () {
        //datos de materia prima
        var codpro = $("#editarCodigoPro").val();
        var codalt = $("#editarCodigoAlt").val();
        var despro = $("#editarDescripcion").val();
        var undpro = $("#editarUnidadMedida").val();
        var padval = $("#editarAdVal").val();
        var pseg = $("#editarSeguro").val();
        var pespro = $("#editarPeso").val();
        var stkmin = $("#editarStockMinimo").val();
        var stkmax = $("#editarStockMaximo").val();

        //datos de precio mp
        var codprov1 = $("#editarProveedor").val() || "";
        var preprov1 = $("#editarPrecioSIGV").val() || "0";
        var monprov1 = $("#editarMoneda").val() || "";
        var obsprov1 = $("#editarObservacion1").val() || "";
        var codprov2 = $("#editarProveedor1").val() || "";
        var preprov2 = $("#editarPrecioSIGV1").val() || "0";
        var monprov2 = $("#editarMoneda1").val() || "";
        var obsprov2 = $("#editarObservacion2").val() || "";
        var codprov3 = $("#editarProveedor2").val() || "";
        var preprov3 = $("#editarPrecioSIGV2").val() || "0";
        var monprov3 = $("#editarMoneda2").val() || "";
        var obsprov3 = $("#editarObservacion3").val() || "";

        var datos = new Array();

        datos.push({
            codpro: codpro,
            codalt: codalt,
            despro: despro,
            undpro: undpro,
            padval: padval,
            pseg: pseg,
            pespro: pespro,
            stkmin: stkmin,
            stkmax: stkmax,
            codprov1: codprov1,
            preprov1: preprov1,
            monprov1: monprov1,
            obsprov1: obsprov1,
            codprov2: codprov2,
            preprov2: preprov2,
            monprov2: monprov2,
            obsprov2: obsprov2,
            codprov3: codprov3,
            preprov3: preprov3,
            monprov3: monprov3,
            obsprov3: obsprov3,
        });
        var materiaprima = { datosMateria: datos };

        var jsonMateria = { jsonMateria: JSON.stringify(materiaprima) };

        $.ajax({
            url: "ajax/materiaprima.ajax.php",
            method: "POST",
            data: jsonMateria,
            cache: false,
            success: function (respuesta) {
                // console.log(respuesta);
                if (respuesta == "ok") {
                    // Recargar tabla según la configuración (RUTA_MATERIAPRIMA)
                    var ruta = window.RUTA_MATERIAPRIMA || "materiaprima";
                    if (ruta === "materiaprima-test") {
                        // Si estamos en modo servidor, recargar tabla paginada
                        if ($.fn.DataTable.isDataTable(".tablaMateriaPrimaPaginado")) {
                            $(".tablaMateriaPrimaPaginado").DataTable().ajax.reload(null, false);
                        }
                    } else {
                        // Si estamos en modo cliente, recargar tabla cliente
                        if ($.fn.DataTable.isDataTable(".tablaMateriaPrima")) {
                            $(".tablaMateriaPrima").DataTable().ajax.reload(null, false);
                        }
                    }
                    $("#modalEditarMateriaPrima").modal("hide");
                    Command: toastr["success"](
                        "Editado de materia prima exitosamente!"
                    );
                }
            },
        });
    }
);

$("#formDuplicarMateriaPrima").submit(function (e) {
    e.preventDefault();
    //datos de materia prima
    var codfab = $("#duplicarCodigoFab").val();
    var codalt = $("#duplicarCodigoAlt").val();
    var fampro = $("#duplicarFamPro").val();
    var color = $("#duplicarColorMateria").val();
    var talla = $("#duplicarTallaMateria").val();
    var despro = $("#duplicarDescripcion").val();
    var undpro = $("#duplicarUnidadMedida").val();
    var pespro = $("#duplicarPeso").val();
    var padval = $("#duplicarAdVal").val();
    var pseg = $("#duplicarSeguro").val();
    var stkactual = $("#duplicarStockActual").val();
    var stkmin = $("#duplicarStockMinimo").val();
    var stkmax = $("#duplicarStockMaximo").val();

    //datos de precio mp
    var codprov1 = $("#duplicarProveedor").val() || "";
    var preprov1 = $("#duplicarPrecioSIGV").val() || "0";
    var monprov1 = $("#duplicarMoneda").val() || "";
    var obsprov1 = $("#duplicarObservacion1").val() || "";
    var codprov2 = $("#duplicarProveedor1").val() || "";
    var preprov2 = $("#duplicarPrecioSIGV1").val() || "0";
    var monprov2 = $("#duplicarMoneda1").val() || "";
    var obsprov2 = $("#duplicarObservacion2").val() || "";
    var codprov3 = $("#duplicarProveedor2").val() || "";
    var preprov3 = $("#duplicarPrecioSIGV2").val() || "0";
    var monprov3 = $("#duplicarMoneda2").val() || "";
    var obsprov3 = $("#duplicarObservacion3").val() || "";

    var datos = new Array();

    datos.push({
        codfab: codfab,
        codalt: codalt,
        fampro: fampro,
        color: color,
        talla: talla,
        despro: despro,
        undpro: undpro,
        padval: padval,
        pseg: pseg,
        pespro: pespro,
        stkactual: stkactual,
        stkmin: stkmin,
        stkmax: stkmax,
        codprov1: codprov1,
        preprov1: preprov1,
        monprov1: monprov1,
        obsprov1: obsprov1,
        codprov2: codprov2,
        preprov2: preprov2,
        monprov2: monprov2,
        obsprov2: obsprov2,
        codprov3: codprov3,
        preprov3: preprov3,
        monprov3: monprov3,
        obsprov3: obsprov3,
    });
    var materiaprima = { datosMateria: datos };

    var jsonMateriaDuplicar = {
        jsonMateriaDuplicar: JSON.stringify(materiaprima),
    };

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: jsonMateriaDuplicar,
        cache: false,
        success: function (respuesta) {
            // console.log(respuesta);
            if (respuesta == "ok") {
                //recargamos la pagina
                location.reload();

                // $(".tablaMateriaPrima").DataTable().ajax.reload(null, false);
                // $("#modalDuplicarMateriaPrima").modal("hide");
                // Command: toastr["success"](
                //     "Se creo nuevo color de materia prima exitosamente!"
                // );
            } else {
                Command: toastr["error"]("No se realizo ningun cambio!");
                $("#modalDuplicarMateriaPrima").modal("hide");
            }
        },
    });
});

/* 
* ALMACEN 01 - CUADROS Y COPAS
! ALMACEN 01 - CUADROS Y COPAS
? ALMACEN 01 - CUADROS Y COPAS
*/
/*
 *CARGAR LA TABLA DINÁMICA DE almacen01
 **/
if (localStorage.getItem("tipo") != null) {
    $("#selectAlmacen01").val(localStorage.getItem("tipo"));
    cargarAlmacen01Tipo(localStorage.getItem("tipo"));
} else {
    cargarAlmacen01Tipo(null);
}

function cargarAlmacen01Tipo(tipo) {
    $(".tablaAlmacen01").DataTable({
        ajax: "ajax/materiaprima/tabla-almacen01.ajax.php?tipo=" + tipo,
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

/*
 *AGREGAR COPA
 */
$(".tablaAlmacen01 tbody").on("click", "button.btnAgregarCopas", function () {
    var codpro = $(this).attr("codpro");
    var codfab = $(this).attr("codfab");
    var despro = $(this).attr("despro");
    var color = $(this).attr("color");
    var unidad = $(this).attr("unidad");
    var stock = $(this).attr("stock");

    $("#codpro").val(codpro);
    $("#codfab").val(codfab);
    $("#despro").val(despro);
    $("#color").val(color);
    $("#unidad").val(unidad);
    $("#stock").val(stock);

    $(".tablaAlm01Add").DataTable().destroy();
    $(".tablaAlm01Add").DataTable({
        ajax: "ajax/materiaprima/tabla-almacen01-add.ajax.php?codpro=" + codpro,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[1, "asc"]],
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
});

/*
 *AGREGANDO COPAS
 */
$(".tablaAlm01Add").on("click", ".btnAddAlm01", function () {
    var cuadro = $(this).attr("cuadro");
    var codpro = $(this).attr("codpro");
    //console.log(cuadro,codpro);

    var datos = new FormData();
    datos.append("cuadro", cuadro);
    datos.append("codpro", codpro);
    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            //console.log(respuesta);

            if (respuesta == '"ok"') {
                Command: toastr["info"]("Copa agregada correctamente!");
            } else {
                Command: toastr["error"]("No se pudo agregar");
            }
        },
    });

    $(this).removeClass("btn-primary");
    $(this).removeClass("btnAddAlm01");
});

$("#selectAlmacen01").change(function () {
    var tipo = $(this).val();

    if (tipo == "") {
        localStorage.setItem("tipo", null);
    } else {
        localStorage.setItem("tipo", tipo);
    }

    //console.log(tipo);
    $(".tablaAlmacen01").DataTable().destroy();
    cargarAlmacen01Tipo(localStorage.getItem("tipo"));
});

/*
 *QUITAR COPAS
 */
$(".tablaAlmacen01 tbody").on("click", "button.btnQuitarCopas", function () {
    var codpro = $(this).attr("codpro");
    var codfab = $(this).attr("codfab");
    var despro = $(this).attr("despro");
    var color = $(this).attr("color");
    var unidad = $(this).attr("unidad");
    var stock = $(this).attr("stock");
    //console.log(codpro);

    $("#codproQ").val(codpro);
    $("#codfabQ").val(codfab);
    $("#desproQ").val(despro);
    $("#colorQ").val(color);
    $("#unidadQ").val(unidad);
    $("#stockQ").val(stock);

    $(".tablaAlm01Off").DataTable().destroy();
    $(".tablaAlm01Off").DataTable({
        ajax: "ajax/materiaprima/tabla-almacen01-off.ajax.php?codpro=" + codpro,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[1, "asc"]],
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
});

/*
 *AGREGANDO COPAS
 */
$(".tablaAlm01Off").on("click", ".btnOffAlm01", function () {
    var codproQ = $(this).attr("codpro");
    console.log(codproQ);

    var datos = new FormData();
    datos.append("codproQ", codproQ);
    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            //console.log(respuesta);

            if (respuesta == '"ok"') {
                Command: toastr["error"]("¡Se quito correctamente!");
            } else {
                Command: toastr["info"]("No se pudo quitar");
            }
        },
    });

    $(this).removeClass("btn-danger");
    $(this).removeClass("btnOffAlm01");
});

/*
 * BOTON AGREGAR PRODUCCION CUADROS
 */
$(".tablaAlmacen01").on("click", ".btnagregarCuadrosProd", function () {
    var codpro = $(this).attr("codpro");
    //console.log(codpro);

    //window.location = "index.php?ruta=editar-tarjeta&idTarjeta=" + idTarjeta;
});

$(".tablaAlmacen01CUA").DataTable({
    ajax: "ajax/materiaprima/tabla-almacen01-cua-prod.ajax.php",
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
 *AGREGANDO MATERIA PRIMA - CUADROS
 */
$(".tablaAlmacen01CUA").on("click", ".agregarCuadros", function () {
    var idBoton = $(this).attr("idBoton");
    var codpro = $(this).attr("codpro");
    //console.log(codpro);

    $(this).removeClass("btn-primary agregarCuadros");
    $(this).addClass("btn-default");

    var datos = new FormData();
    datos.append("codproCua", codpro);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);
            var descripcion = respuesta["despro"];
            var color = respuesta["color"];

            $(".nuevoCuadro").append(
                '<div class="row" style="padding:1px 15px">' +
                    "<!-- CODPRO -->" +
                    '<div class="col-xs-2">' +
                    '<div class="input-group">' +
                    '<span class="input-group-addon" style="padding: 3px 6px"><button type="button" class="btn btn-danger btn-xs quitarCuadro" idBoton="' +
                    idBoton +
                    '"><i class="fa fa-times"></i></button></span>' +
                    '<input type="text" class="form-control nuevoCodPro" codpro="' +
                    codpro +
                    '" id="codpro" name="codpro" value="' +
                    codpro +
                    '" codigoP="' +
                    codpro +
                    '" readonly required>' +
                    "</div>" +
                    "</div>" +
                    "<!-- DESCRIPCION -->" +
                    '<div class="col-xs-4" >' +
                    '<input type="text" class="form-control input-sm nuevaDescripcion" name="descripcion" id ="descripcion" value="' +
                    descripcion +
                    '"  readonly>' +
                    "</div>" +
                    "<!-- COLOR -->" +
                    '<div class="col-xs-4" >' +
                    '<input type="text" class="form-control input-sm nuevColor" name="color" id ="color" value="' +
                    color +
                    '"  readonly>' +
                    "</div>" +
                    "<!-- CANTIDAD RECIBIDA -->" +
                    '<div class="col-xs-2 ingresoCantidad">' +
                    '<input type="number" step="any" class="form-control input-sm nuevaCantidadRecibida"  name="cantidadRecibida" id="cantidadRecibida" cantidadReal="0" value="1" min="1">' +
                    "</div>" +
                    "</div>"
            );

            sumarTotalCantidadCua();
            listarCuadros();
        },
    });
});

/*=============================================
CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
=============================================*/
$(".tablaAlmacen01CUA").on("draw.dt", function () {
    /* console.log("tabla"); */

    if (localStorage.getItem("quitarCuadro") != null) {
        var listaCuadros = JSON.parse(localStorage.getItem("quitarCuadro"));

        //console.log(listaCuadros);

        for (var i = 0; i < listaCuadros.length; i++) {
            $(
                "button.recuperarBoton[idBoton='" +
                    listaCuadros[i]["idBoton"] +
                    "']"
            ).removeClass("btn-default");
            $(
                "button.recuperarBoton[idBoton='" +
                    listaCuadros[i]["idBoton"] +
                    "']"
            ).addClass("btn-primary agregarCuadros");
        }
    }
});

/*=============================================
  QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
  =============================================*/
var idQuitarCuadro = [];

localStorage.removeItem("quitarCuadro");

$(".formularioCuadros").on("click", "button.quitarCuadro", function () {
    /* console.log("boton"); */

    $(this).parent().parent().parent().parent().remove();

    var idBoton = $(this).attr("idBoton");
    //console.log(idBoton);

    /*=============================================
	ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
	=============================================*/

    if (localStorage.getItem("quitarCuadro") == null) {
        idQuitarCuadro = [];
    } else {
        idQuitarCuadro.concat(localStorage.getItem("quitarCuadro"));
    }

    idQuitarCuadro.push({
        idBoton: idBoton,
    });

    //console.log(idQuitarCuadro);

    localStorage.setItem("quitarCuadro", JSON.stringify(idQuitarCuadro));

    $(".recuperarBoton[idBoton='" + idBoton + "']").removeClass("btn-default");
    console.log(".recuperarBoton[idBoton='" + idBoton + "']");

    $(".recuperarBoton[idBoton='" + idBoton + "']").addClass(
        "btn-primary agregarCuadros"
    );

    if ($(".nuevoCuadro").children().length == 0) {
        $("#nuevoTotal").val(0);

        $("#totalCua").val(0);
        $("#totalCua").attr("total", 0);

        $("#listaCuaMp").val("");
    } else {
        sumarTotalCantidadCua();
        listarCuadros();
    }
});

/*=============================================
MODIFICAR EL TOTAL AL CAMBIAR LA CANTIDAD
=============================================*/
$(".formularioCuadros").on("keyup", "input.nuevaCantidadRecibida", function () {
    sumarTotalCantidadCua();
    listarCuadros();
});

function sumarTotalCantidadCua() {
    var cantidad = $(".nuevaCantidadRecibida");
    //console.log("cantidad", cantidad);

    var arraySumaCantidad = [];

    for (var i = 0; i < cantidad.length; i++) {
        arraySumaCantidad.push(Number($(cantidad[i]).val()));
    }
    //console.log("arraySumaCantidad", arraySumaCantidad);

    function sumaArrayCantidad(total, numero) {
        return total + numero;
    }

    var sumaTotalCantidad = arraySumaCantidad.reduce(sumaArrayCantidad);
    //console.log("sumaTotalCantidad", sumaTotalCantidad);

    $("#nuevoTotal").val(sumaTotalCantidad.toFixed(0));
    $("#totalCua").val(sumaTotalCantidad);
}

function listarCuadros() {
    listaCuadros = [];

    var codpro = $(".nuevoCodPro");
    var descripcion = $(".nuevaDescripcion");
    var cantidadRe = $(".nuevaCantidadRecibida");

    for (var i = 0; i < descripcion.length; i++) {
        listaCuadros.push({
            codpro: $(codpro[i]).val(),
            cantidadRe: $(cantidadRe[i]).val(),
        });
    }

    //console.log("listaCuadros", JSON.stringify(listaCuadros));

    $("#listaCuaMp").val(JSON.stringify(listaCuadros));
}

//COPAS

$(".tablaAlmacen01COP").DataTable({
    ajax: "ajax/materiaprima/tabla-almacen01-cop-prod.ajax.php",
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
 *AGREGANDO MATERIA PRIMA - COPAS
 */
$(".tablaAlmacen01COP").on("click", "button.agregarCopas", function () {
    var idBoton = $(this).attr("idBoton");
    var codpro = $(this).attr("codpro");
    //console.log(codpro);

    $(this).removeClass("btn-primary agregarCopas");
    $(this).addClass("btn-default");

    var datos = new FormData();
    datos.append("codproCua", codpro);

    $.ajax({
        url: "ajax/materiaprima.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            var descripcion = respuesta["despro"];
            var color = respuesta["color"];
            var talla = respuesta["talla"];
            var unidad = respuesta["unidad"];
            var cuadro = respuesta["cuadro"];

            $(".nuevaCopa").append(
                '<div class="row" style="padding:1px 15px">' +
                    "<!-- CODPRO -->" +
                    '<div class="col-xs-2">' +
                    '<div class="input-group">' +
                    '<span class="input-group-addon" style="padding: 3px 6px"><button type="button" class="btn btn-danger btn-xs quitarCopa" idBoton="' +
                    idBoton +
                    '"><i class="fa fa-times"></i></button></span>' +
                    '<input type="text" class="form-control nuevoCodPro" codpro="' +
                    codpro +
                    '" id="codpro" name="codpro" value="' +
                    codpro +
                    '" codigoP="' +
                    codpro +
                    '" cuadro = "' +
                    cuadro +
                    '" readonly required>' +
                    "</div>" +
                    "</div>" +
                    "<!-- DESCRIPCION -->" +
                    '<div class="col-xs-5" >' +
                    '<input type="text" class="form-control input-sm nuevaDescripcion" name="descripcion" id ="descripcion" value="' +
                    descripcion +
                    " - " +
                    unidad +
                    " - T" +
                    talla +
                    '"  readonly>' +
                    "</div>" +
                    "<!-- COLOR -->" +
                    '<div class="col-xs-3" >' +
                    '<input type="text" class="form-control input-sm nuevColor" name="color" id ="color" value="' +
                    color +
                    '"  readonly>' +
                    "</div>" +
                    "<!-- CANTIDAD RECIBIDA -->" +
                    '<div class="col-xs-2 ingresoCantidad">' +
                    '<input type="number" step="any" class="form-control input-sm nuevaCantidadRecibida"  name="cantidadRecibida" id="cantidadRecibida" cantidadReal="0" value="1" min="1">' +
                    "</div>" +
                    "</div>"
            );

            sumarTotalCantidadCopa();
            listarCopas();
        },
    });
});

/*=============================================
CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
=============================================*/
$(".tablaAlmacen01COP").on("draw.dt", function () {
    //  console.log("tabla");

    if (localStorage.getItem("quitarCopa") != null) {
        var listaCopas = JSON.parse(localStorage.getItem("quitarCopa"));

        //console.log(listaCuadros);

        for (var i = 0; i < listaCopas.length; i++) {
            $(
                "button.recuperarBoton[idBoton='" +
                    listaCopas[i]["idBoton"] +
                    "']"
            ).removeClass("btn-default");
            $(
                "button.recuperarBoton[idBoton='" +
                    listaCopas[i]["idBoton"] +
                    "']"
            ).addClass("btn-primary agregarCopas");
        }
    }
});

/*=============================================
  QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
  =============================================*/
var idQuitarCopa = [];

localStorage.removeItem("quitarCopa");

$(".formularioCopas").on("click", "button.quitarCopa", function () {
    /* console.log("boton"); */

    $(this).parent().parent().parent().parent().remove();

    var idBoton = $(this).attr("idBoton");
    //console.log(idBoton);

    /*=============================================
	ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
	=============================================*/

    if (localStorage.getItem("quitarCopa") == null) {
        idQuitarCopa = [];
    } else {
        idQuitarCopa.concat(localStorage.getItem("quitarCopa"));
    }

    idQuitarCopa.push({
        idBoton: idBoton,
    });

    //console.log(idQuitarCuadro);

    localStorage.setItem("quitarCopa", JSON.stringify(idQuitarCopa));

    $(".recuperarBoton[idBoton='" + idBoton + "']").removeClass("btn-default");
    // console.log(".recuperarBoton[idBoton='" + idBoton + "']");

    $(".recuperarBoton[idBoton='" + idBoton + "']").addClass(
        "btn-primary agregarCopas"
    );

    if ($(".nuevaCopa").children().length == 0) {
        $("#nuevoTotal").val(0);

        $("#totalCopa").val(0);
        $("#totalCopa").attr("total", 0);

        $("#listaCopaMp").val("");
    } else {
        sumarTotalCantidadCopa();
        listarCopas();
    }
});

/*=============================================
MODIFICAR EL TOTAL AL CAMBIAR LA CANTIDAD
=============================================*/
$(".formularioCopas").on("keyup", "input.nuevaCantidadRecibida", function () {
    sumarTotalCantidadCopa();
    listarCopas();
});

function sumarTotalCantidadCopa() {
    var cantidad = $(".nuevaCantidadRecibida");
    //console.log("cantidad", cantidad);

    var arraySumaCantidad = [];

    for (var i = 0; i < cantidad.length; i++) {
        arraySumaCantidad.push(Number($(cantidad[i]).val()));
    }
    //console.log("arraySumaCantidad", arraySumaCantidad);

    function sumaArrayCantidad(total, numero) {
        return total + numero;
    }

    var sumaTotalCantidad = arraySumaCantidad.reduce(sumaArrayCantidad);
    //console.log("sumaTotalCantidad", sumaTotalCantidad);

    $("#nuevoTotal").val(sumaTotalCantidad.toFixed(0));
    $("#totalCopa").val(sumaTotalCantidad);
}

function listarCopas() {
    listaCopas = [];

    var codpro = $(".nuevoCodPro");
    var descripcion = $(".nuevaDescripcion");
    var cantidadRe = $(".nuevaCantidadRecibida");

    for (var i = 0; i < descripcion.length; i++) {
        listaCopas.push({
            codpro: $(codpro[i]).val(),
            cuadro: $(codpro[i]).attr("cuadro"),
            cantidadRe: $(cantidadRe[i]).val(),
        });
    }

    //   console.log("listaCopas", JSON.stringify(listaCopas));

    $("#listaCopaMp").val(JSON.stringify(listaCopas));
}

$("#formularioSaldosMatPri").on(
    "click",
    "button.btnGenerarSaldoMatPri",
    function () {
        console.log("Generar Saldo");

        let fin = $("#fFin").val();
        let guias = $("#conGuias").is(":checked") ? "1" : "0";

        window.location = `vistas/reportes_excel/saldos_materiaprima.php?fin=${fin}`;
    }
);

