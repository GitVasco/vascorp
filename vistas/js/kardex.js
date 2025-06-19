/*
 * Ver tabla de mp al hacer clic en input
 */
$("#codpro").click(function () {
    $(".tablaMpKardex").DataTable().destroy();
    $(".tablaMpKardex").DataTable({
        ajax:
            "ajax/materiaprima/tabla-ver-mp.ajax.php?perfil=" +
            $("#perfilOculto").val(),
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
 * BOTON EDITAR ORDEN DE CORTE
 */
$(".tablaMpKardex").on("click", ".btnCodpro", function () {
    var codpro = $(this).attr("codpro");
    var codfab = $(this).attr("codfab");
    var descripcion = $(this).attr("descripcion");
    var color = $(this).attr("color");
    var stock = $(this).attr("stock");

    //console.log(codigo);

    $("#codpro").val(codpro);
    $("#codfab").val(codfab);
    $("#descripcion").val(descripcion);
    $("#color").val(color);
    $("#stock").val(stock);

    $("#modalMP").modal("toggle");

    document.getElementById("filtrar").disabled = false;
    document.getElementById("exportar").disabled = false;
});

$(".btnFiltrar").click(function () {
    var codigo = document.getElementById("codpro").value;
    var ano = document.getElementById("ano").value;

    if (ano == "") {
        ano = 2024;
    }

    var ano_ant = Number(ano) - 1;
    console.log(ano_ant);

    $(".tablaKardexMateriaPrima").DataTable().destroy();

    $(".tablaKardexMateriaPrima").DataTable({
        ajax:
            "ajax/materiaprima/tabla-kardex-mp.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&codigo=" +
            codigo +
            "&ano=" +
            ano +
            "&ano_ant=" +
            ano_ant,
        deferRender: true,
        retrieve: true,
        processing: true,
        searching: false,
        lengthChange: false,
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
});

$(".btnExportar").click(function () {
    var codigo = document.getElementById("codpro").value;
    var ano = document.getElementById("ano").value;

    if (ano == "") {
        ano = 2024;
    }

    window.location =
        "vistas/reportes_excel/rpt_kardex_mp.php?codigo=" +
        codigo +
        "&ano=" +
        ano;
});

//*KARDEX DE COSTOS*************************************************************************************

$(".TablaKardexCostos").DataTable({
    ajax: "ajax/centrocostos/tabla-kardex-carga.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    searching: true,
    lengthChange: true,
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

/*
 * BOTON REXPORTAR KARDEX
 */
$(".TablaKardexCostos").on("click", ".btnExpKardex", function () {
    var codigo = $(this).attr("codigo");
    var anno = $(this).attr("anno");
    var mes = $(this).attr("mes");
    //console.log("codigo", codigo);

    window.location =
        "vistas/reportes_excel/rpt_kardex.php?codigo=" +
        codigo +
        "&anno=" +
        anno +
        "&mes=" +
        mes;
});
