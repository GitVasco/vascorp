if ($(".tablaCategoriasPorRevisar").length) {

    function aplicarFiltroCategoriaBandeja(codigo) {
        var $sel = $("#filtroCategoriaBandeja");
        if (!$sel.length) {
            return;
        }
        $sel.val(codigo || "");
        if (typeof $sel.selectpicker === "function") {
            try {
                $sel.selectpicker("refresh");
            } catch (e) {}
        }
        if (typeof tablaCategoriasPorRevisar !== "undefined") {
            tablaCategoriasPorRevisar.ajax.reload();
        }
    }

    var tablaCategoriasPorRevisar = $(".tablaCategoriasPorRevisar").DataTable({
        ajax: {
            url: "ajax/facturacion/tabla-categorias-por-revisar.ajax.php",
            data: function (d) {
                d.categoria = $("#filtroCategoriaBandeja").val() || "";
            }
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[3, "asc"]],
        pageLength: 20,
        columnDefs: [
            {
                targets: [4, 5],
                className: "text-right"
            },
            {
                targets: [6],
                className: "text-center"
            }
        ],
        lengthMenu: [[20, 40, 60, -1], [20, 40, 60, "Todos"]],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún caso pendiente en la bandeja",
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

    $("#filtroCategoriaBandeja").on("changed.bs.select change", function () {
        if (typeof tablaCategoriasPorRevisar !== "undefined") {
            tablaCategoriasPorRevisar.ajax.reload();
        }
    });

    $(document).on("click", ".filtro-categoria-bandeja", function () {
        var codigo = $(this).attr("data-categoria") || "";
        var actual = $("#filtroCategoriaBandeja").val() || "";
        if (actual === codigo) {
            aplicarFiltroCategoriaBandeja("");
        } else {
            aplicarFiltroCategoriaBandeja(codigo);
        }
    });
}

function refrescarSelectRevCategoria() {
    var select = $("#revIdCategoria");
    if (!select.length || typeof select.selectpicker !== "function") {
        return;
    }
    try {
        if (select.data("selectpicker")) {
            select.selectpicker("refresh");
        } else {
            select.selectpicker({ liveSearch: true });
        }
    } catch (e) {}
}

function toggleBloqueExcepcionBandeja() {
    if ($("#revEsExcepcion").is(":checked")) {
        $("#bloqueVigenciaExcepcion").show();
    } else {
        $("#bloqueVigenciaExcepcion").hide();
    }
}

$(".tablaCategoriasPorRevisar").on("click", ".btnRevisarCategoriaBandeja", function () {
    $("#revTipoEntidad").val($(this).attr("tipoEntidad"));
    $("#revCodigoEntidad").val($(this).attr("codigoEntidad"));
    $("#revIdAsignacion").val($(this).attr("idAsignacion") || "0");
    $("#revTituloEntidad").text(
        ($(this).attr("tipoEntidad") === "grupo" ? "Grupo: " : "Cliente: ") +
        $(this).attr("nombreEntidad") +
        " (" + $(this).attr("codigoEntidad") + ")"
    );

    var idCat = $(this).attr("idCategoria") || "";
    $("#revIdCategoria").val(idCat ? String(idCat) : "");
    refrescarSelectRevCategoria();

    var cumplimiento = $(this).attr("cumplimiento") || "pendiente";
    if (cumplimiento === "sin_categoria") {
        cumplimiento = "pendiente";
    }
    $("#revCumplimiento").val(cumplimiento);

    var esExc = String($(this).attr("esExcepcion")) === "1";
    $("#revEsExcepcion").prop("checked", esExc);
    $("#revMotivo").val($(this).attr("motivo") || "");
    $("#revVigenciaHasta").val($(this).attr("vigenciaHasta") || "");
    toggleBloqueExcepcionBandeja();
});

$("#revEsExcepcion").on("change", toggleBloqueExcepcionBandeja);

$("#formRevisarCategoriaBandeja").on("submit", function (e) {
    e.preventDefault();

    var esExcepcion = $("#revEsExcepcion").is(":checked") ? 1 : 0;
    var motivo = $.trim($("#revMotivo").val() || "");
    var vigencia = $("#revVigenciaHasta").val() || "";

    if (esExcepcion === 1 && motivo === "") {
        swal({ type: "warning", title: "Indique el motivo de la excepción", showConfirmButton: true });
        return;
    }

    if (esExcepcion === 1 && vigencia === "") {
        swal({ type: "warning", title: "Indique la fecha de vencimiento", showConfirmButton: true });
        return;
    }

    var datos = new FormData();
    datos.append("accion", "resolverBandeja");
    datos.append("tipo_entidad", $("#revTipoEntidad").val());
    datos.append("codigo_entidad", $("#revCodigoEntidad").val());
    datos.append("id_asignacion", $("#revIdAsignacion").val() || "0");
    datos.append("id_categoria", $("#revIdCategoria").val() || "0");
    datos.append("cumplimiento", $("#revCumplimiento").val());
    datos.append("motivo", motivo);
    datos.append("es_excepcion", esExcepcion);
    datos.append("vigencia_hasta", vigencia);

    $("#btnGuardarRevisionBandeja").prop("disabled", true);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#btnGuardarRevisionBandeja").prop("disabled", false);

            if (respuesta && respuesta.ok) {
                $("#modalRevisarCategoriaBandeja").modal("hide");
                if (typeof tablaCategoriasPorRevisar !== "undefined") {
                    tablaCategoriasPorRevisar.ajax.reload(null, false);
                }
                swal({
                    type: "success",
                    title: "Guardado",
                    text: respuesta.mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            } else {
                swal({
                    type: "error",
                    title: "No se pudo guardar",
                    text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "Error desconocido",
                    showConfirmButton: true
                });
            }
        },
        error: function () {
            $("#btnGuardarRevisionBandeja").prop("disabled", false);
            swal({ type: "error", title: "Error de comunicación", showConfirmButton: true });
        }
    });
});
