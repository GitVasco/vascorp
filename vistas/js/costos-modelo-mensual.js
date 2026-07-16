var tablaCostosModeloMensual = null;
var costosModeloPorCodigo = {};

function escaparCostoModelo(valor) {
    return $("<div>").text(valor === null || valor === undefined ? "" : String(valor)).html();
}

function alertaCostoModelo(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function filtrosCostosModelo() {
    return {
        accion: "listar",
        anio: $("#filtroAnioCostoModelo").val(),
        mes: $("#filtroMesCostoModelo").val(),
        id_marca: $("#filtroMarcaCostoModelo").val() || "",
        estado: $("#filtroEstadoCostoModelo").val() || ""
    };
}

function etiquetaEstadoCostoModelo(estado) {
    if (estado === "borrador") {
        return "<span class='label label-warning'>Borrador</span>";
    }
    if (estado === "aprobado") {
        return "<span class='label label-success'>Aprobado</span>";
    }
    if (estado === "anulado") {
        return "<span class='label label-danger'>Anulado</span>";
    }
    return "<span class='label label-default'>Sin costo</span>";
}

function filaCostoModelo(item) {
    var modelo = String(item.modelo || "");
    costosModeloPorCodigo[modelo] = item;
    var costo = item.costo_unitario === null
        ? "<span class='text-muted'>Pendiente</span>"
        : "S/ " + Number(item.costo_unitario).toFixed(4);
    var actualizacion = item.fecha_modificacion || item.fecha_registro || "—";
    var modeloClave = encodeURIComponent(modelo);
    var seleccion = item.id
        ? "<input type='checkbox' class='checkCostoModelo' value='" + Number(item.id) + "'>"
        : "";
    var botones = "<div class='btn-group'>";

    if (window.costosModeloPuedeEditar && (!item.estado || item.estado === "borrador")) {
        botones += "<button type='button' class='btn btn-xs btn-primary btnEditarCostoModelo' data-modelo='"
            + modeloClave + "' title='Registrar o editar'><i class='fa fa-pencil'></i></button>";
    }
    if (item.id) {
        botones += "<button type='button' class='btn btn-xs btn-info btnHistorialCostoModelo' data-modelo='"
            + modeloClave + "' title='Historial'><i class='fa fa-history'></i></button>";
        if (window.costosModeloPuedeAprobar && item.estado === "borrador") {
            botones += "<button type='button' class='btn btn-xs btn-success btnCambioEstadoCosto' data-id='"
                + Number(item.id) + "' data-accion='aprobar' title='Aprobar'><i class='fa fa-check'></i></button>";
        }
        if (window.costosModeloPuedeAprobar && (item.estado === "borrador" || item.estado === "aprobado")) {
            botones += "<button type='button' class='btn btn-xs btn-danger btnCambioEstadoCosto' data-id='"
                + Number(item.id) + "' data-accion='anular' title='Anular'><i class='fa fa-ban'></i></button>";
        }
        if (window.costosModeloPuedeAprobar && (item.estado === "aprobado" || item.estado === "anulado")) {
            botones += "<button type='button' class='btn btn-xs btn-warning btnCambioEstadoCosto' data-id='"
                + Number(item.id) + "' data-accion='reabrir' title='Reabrir'><i class='fa fa-unlock'></i></button>";
        }
    }
    botones += "</div>";

    return [
        seleccion,
        "<strong>" + escaparCostoModelo(modelo) + "</strong>",
        escaparCostoModelo(item.marca || "—"),
        escaparCostoModelo(item.nombre || modelo),
        costo,
        escaparCostoModelo(item.fuente || "—"),
        etiquetaEstadoCostoModelo(item.estado),
        escaparCostoModelo(actualizacion),
        botones
    ];
}

function actualizarResumenCostosModelo(lista) {
    var borradores = 0;
    var pendientes = 0;
    lista.forEach(function (item) {
        if (item.estado === "borrador") {
            borradores++;
        }
        if (!item.id) {
            pendientes++;
        }
    });
    $("#resumenModelosCostos").text(lista.length);
    $("#resumenBorradoresCostos").text(borradores);
    $("#resumenPendientesCostos").text(pendientes);
}

function cargarCostosModelo() {
    $.post("ajax/costos-modelo-mensual.ajax.php", filtrosCostosModelo(), function (resp) {
        if (!resp || !resp.ok) {
            alertaCostoModelo("error", resp && resp.mensaje ? resp.mensaje : "No se pudieron cargar los costos");
            return;
        }

        costosModeloPorCodigo = {};
        var filas = (resp.data || []).map(filaCostoModelo);
        actualizarResumenCostosModelo(resp.data || []);

        if (!tablaCostosModeloMensual) {
            tablaCostosModeloMensual = $("#tablaCostosModeloMensual").DataTable({
                data: filas,
                deferRender: true,
                pageLength: 25,
                order: [[1, "asc"], [2, "asc"]],
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 8] }
                ],
                language: {
                    sProcessing: "Procesando...",
                    sLengthMenu: "Mostrar _MENU_ registros",
                    sZeroRecords: "No se encontraron resultados",
                    sEmptyTable: "No hay modelos para los filtros seleccionados",
                    sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ modelos",
                    sInfoEmpty: "Mostrando 0 modelos",
                    sInfoFiltered: "(filtrado de _MAX_ modelos)",
                    sSearch: "Buscar:",
                    oPaginate: { sNext: "Siguiente", sPrevious: "Anterior" }
                }
            });
        } else {
            tablaCostosModeloMensual.clear().rows.add(filas).draw();
        }
        $("#seleccionarTodosCostosModelo").prop("checked", false);
    }, "json").fail(function (xhr) {
        var mensaje = xhr.status === 403 ? "Sin permiso para consultar costos" : "Error de comunicación";
        alertaCostoModelo("error", mensaje);
    });
}

function cargarMarcasCostosModelo() {
    $.post("ajax/costos-modelo-mensual.ajax.php", { accion: "listarMarcas" }, function (resp) {
        if (!resp || !resp.ok) {
            return;
        }
        var $select = $("#filtroMarcaCostoModelo");
        (resp.data || []).forEach(function (marca) {
            $select.append($("<option>").val(marca.id).text(marca.marca));
        });
    }, "json");
}

if ($("#tablaCostosModeloMensual").length) {
    cargarMarcasCostosModelo();
    cargarCostosModelo();
}

$(document).on("change", ".filtroCostoModelo", cargarCostosModelo);
$("#btnActualizarCostosModelo").on("click", cargarCostosModelo);

$("#seleccionarTodosCostosModelo").on("change", function () {
    var marcado = $(this).is(":checked");
    if (!tablaCostosModeloMensual) {
        return;
    }
    $(tablaCostosModeloMensual.rows({ search: "applied", page: "current" }).nodes())
        .find(".checkCostoModelo").prop("checked", marcado);
});

function idsCostosModeloSeleccionados() {
    var ids = [];
    $("#tablaCostosModeloMensual .checkCostoModelo:checked").each(function () {
        ids.push(Number($(this).val()));
    });
    return ids;
}

function abrirCambioEstadoCostos(accion, ids) {
    if (!ids.length) {
        alertaCostoModelo("warning", "Selecciona al menos un costo");
        return;
    }
    var configuracion = {
        aprobar: { titulo: "Aprobar costos", texto: "Se aprobarán " + ids.length + " costo(s).", motivo: false },
        anular: { titulo: "Anular costos", texto: "Se anularán " + ids.length + " costo(s).", motivo: true },
        reabrir: { titulo: "Reabrir costos", texto: "Se reabrirán " + ids.length + " costo(s) como borrador.", motivo: true }
    };
    var datos = configuracion[accion];
    if (!datos) {
        return;
    }
    $("#accionCambioEstadoCostoModelo").val(accion);
    $("#idsCambioEstadoCostoModelo").val(JSON.stringify(ids));
    $("#tituloCambioEstadoCostoModelo").text(datos.titulo);
    $("#textoCambioEstadoCostoModelo").text(datos.texto);
    $("#motivoCambioEstadoCostoModelo").val("").prop("required", datos.motivo);
    $("#grupoMotivoCambioEstadoCosto").toggle(datos.motivo);
    $("#modalCambioEstadoCostoModelo").modal("show");
}

$(document).on("click", ".btnCambioEstadoCosto", function () {
    abrirCambioEstadoCostos($(this).attr("data-accion"), [Number($(this).attr("data-id"))]);
});

$(document).on("click", ".btnCambioEstadoCostosSeleccionados", function () {
    abrirCambioEstadoCostos($(this).attr("data-accion"), idsCostosModeloSeleccionados());
});

$("#btnAprobarPeriodoCostos").on("click", function () {
    $("#accionCambioEstadoCostoModelo").val("aprobar");
    $("#idsCambioEstadoCostoModelo").val("periodo");
    $("#tituloCambioEstadoCostoModelo").text("Aprobar período");
    $("#textoCambioEstadoCostoModelo").text(
        "Se aprobarán todos los costos en borrador de "
        + $("#filtroMesCostoModelo option:selected").text().trim() + " "
        + $("#filtroAnioCostoModelo").val() + "."
    );
    $("#grupoMotivoCambioEstadoCosto").hide();
    $("#motivoCambioEstadoCostoModelo").val("").prop("required", false);
    $("#modalCambioEstadoCostoModelo").modal("show");
});

$("#formCambioEstadoCostoModelo").on("submit", function (e) {
    e.preventDefault();
    var $boton = $(this).find("button[type='submit']");
    $boton.prop("disabled", true);
    var ids = $("#idsCambioEstadoCostoModelo").val();
    var datosCambio = ids === "periodo"
        ? {
            accion: "aprobarPeriodo",
            anio: $("#filtroAnioCostoModelo").val(),
            mes: $("#filtroMesCostoModelo").val()
        }
        : {
            accion: "cambiarEstado",
            cambio_estado: $("#accionCambioEstadoCostoModelo").val(),
            ids: ids,
            motivo: $("#motivoCambioEstadoCostoModelo").val()
        };
    $.post("ajax/costos-modelo-mensual.ajax.php", datosCambio, function (resp) {
        if (resp && resp.ok) {
            $("#modalCambioEstadoCostoModelo").modal("hide");
            cargarCostosModelo();
            alertaCostoModelo("success", resp.mensaje || "Estado actualizado");
        } else {
            alertaCostoModelo("error", resp && resp.mensaje ? resp.mensaje : "No se pudo cambiar el estado");
        }
    }, "json").fail(function () {
        alertaCostoModelo("error", "Error de comunicación");
    }).always(function () {
        $boton.prop("disabled", false);
    });
});

$("#btnReportePendientesCostos").on("click", function () {
    var anio = encodeURIComponent($("#filtroAnioCostoModelo").val());
    var mes = encodeURIComponent($("#filtroMesCostoModelo").val());
    window.location.href = "ajax/reporte-costos-pendientes.csv.php?anio=" + anio + "&mes=" + mes;
});

$(document).on("click", ".btnEditarCostoModelo", function () {
    var item = costosModeloPorCodigo[decodeURIComponent(String($(this).attr("data-modelo")))];
    if (!item) {
        return;
    }
    $("#costoModeloCodigo").val(item.modelo);
    $("#costoModeloAnio").val($("#filtroAnioCostoModelo").val());
    $("#costoModeloMes").val($("#filtroMesCostoModelo").val());
    $("#costoModeloDescripcion").text(
        item.modelo + " — " + (item.nombre || item.modelo) + (item.marca ? " · " + item.marca : "")
    );
    $("#costoModeloImporte").val(item.costo_unitario !== null ? item.costo_unitario : "");
    $("#costoModeloFuente").val(item.fuente || "");
    $("#costoModeloObservacion").val(item.observacion || "");
    $("#modalCostoModeloMensual").modal("show");
});

$("#formCostoModeloMensual").on("submit", function (e) {
    e.preventDefault();
    var $boton = $(this).find("button[type='submit']");
    $boton.prop("disabled", true);
    var datos = $(this).serializeArray();
    datos.push({ name: "accion", value: "guardarBorrador" });

    $.post("ajax/costos-modelo-mensual.ajax.php", $.param(datos), function (resp) {
        if (resp && resp.ok) {
            $("#modalCostoModeloMensual").modal("hide");
            cargarCostosModelo();
            alertaCostoModelo("success", resp.mensaje || "Costo guardado");
        } else {
            alertaCostoModelo("error", resp && resp.mensaje ? resp.mensaje : "No se pudo guardar");
        }
    }, "json").fail(function () {
        alertaCostoModelo("error", "Error de comunicación");
    }).always(function () {
        $boton.prop("disabled", false);
    });
});

function enviarImportacionCostos(confirmar) {
    var input = $("#archivoCostosModelo")[0];
    if (!input || !input.files || !input.files.length) {
        alertaCostoModelo("warning", "Selecciona un archivo CSV");
        return;
    }

    var datos = new FormData();
    datos.append("accion", "importarCsv");
    datos.append("anio", $("#filtroAnioCostoModelo").val());
    datos.append("mes", $("#filtroMesCostoModelo").val());
    datos.append("confirmar", confirmar ? "1" : "0");
    datos.append("archivo", input.files[0]);

    var $botones = $("#btnPrevisualizarCostos, #btnConfirmarImportacionCostos");
    $botones.prop("disabled", true);
    $.ajax({
        url: "ajax/costos-modelo-mensual.ajax.php",
        method: "POST",
        data: datos,
        dataType: "json",
        processData: false,
        contentType: false
    }).done(function (resp) {
        if (!resp || !resp.ok) {
            alertaCostoModelo("error", resp && resp.mensaje ? resp.mensaje : "No se pudo procesar el archivo");
            return;
        }
        if (confirmar) {
            $("#modalImportarCostosModelo").modal("hide");
            cargarCostosModelo();
            alertaCostoModelo("success", resp.mensaje || "Costos importados");
            return;
        }

        var rechazadas = Number(resp.rechazadas || 0);
        $("#resumenImportacionCostos")
            .removeClass("alert-success alert-danger")
            .addClass(rechazadas ? "alert-danger" : "alert-success")
            .text("Total: " + resp.total + " · Válidas: " + resp.validas + " · Rechazadas: " + rechazadas)
            .show();
        var filas = (resp.data || []).map(function (fila) {
            var errores = fila.errores && fila.errores.length
                ? "<span class='text-danger'>" + escaparCostoModelo(fila.errores.join("; ")) + "</span>"
                : "<span class='text-success'>Válida</span>";
            return "<tr><td>" + escaparCostoModelo(fila.fila)
                + "</td><td>" + escaparCostoModelo(fila.modelo)
                + "</td><td>" + escaparCostoModelo(fila.costo_unitario || fila.costo_original)
                + "</td><td>" + escaparCostoModelo(fila.fuente || "—")
                + "</td><td>" + errores + "</td></tr>";
        });
        $("#previewImportacionCostosBody").html(filas.join(""));
        $("#contenedorPreviewCostos").show();
        $("#btnConfirmarImportacionCostos").prop("disabled", rechazadas > 0);
    }).fail(function () {
        alertaCostoModelo("error", "Error de comunicación durante la importación");
    }).always(function () {
        $("#btnPrevisualizarCostos").prop("disabled", false);
        if ($("#resumenImportacionCostos").hasClass("alert-success")) {
            $("#btnConfirmarImportacionCostos").prop("disabled", false);
        }
    });
}

$("#btnPrevisualizarCostos").on("click", function () {
    enviarImportacionCostos(false);
});

$("#btnConfirmarImportacionCostos").on("click", function () {
    enviarImportacionCostos(true);
});

$("#archivoCostosModelo").on("change", function () {
    $("#resumenImportacionCostos, #contenedorPreviewCostos").hide();
    $("#previewImportacionCostosBody").empty();
    $("#btnConfirmarImportacionCostos").prop("disabled", true);
});

$(document).on("click", ".btnHistorialCostoModelo", function () {
    var modelo = decodeURIComponent(String($(this).attr("data-modelo")));
    var item = costosModeloPorCodigo[modelo] || {};
    var datos = {
        accion: "historial",
        modelo: modelo,
        anio: $("#filtroAnioCostoModelo").val(),
        mes: $("#filtroMesCostoModelo").val()
    };

    $("#historialCostoModeloTitulo").text(modelo + " — " + (item.nombre || modelo));
    $("#historialCostoModeloBody").html("<tr><td colspan='7' class='text-center'>Cargando...</td></tr>");
    $("#modalHistorialCostoModelo").modal("show");

    $.post("ajax/costos-modelo-mensual.ajax.php", datos, function (resp) {
        if (!resp || !resp.ok) {
            $("#historialCostoModeloBody").html(
                "<tr><td colspan='7' class='text-center text-danger'>"
                + escaparCostoModelo(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar") + "</td></tr>"
            );
            return;
        }
        if (!resp.data || !resp.data.length) {
            $("#historialCostoModeloBody").html("<tr><td colspan='7' class='text-center'>Sin movimientos</td></tr>");
            return;
        }
        var filas = resp.data.map(function (movimiento) {
            return "<tr><td>" + escaparCostoModelo(movimiento.fecha)
                + "</td><td>" + escaparCostoModelo(movimiento.accion)
                + "</td><td>S/ " + Number(movimiento.costo_unitario).toFixed(4)
                + "</td><td>" + escaparCostoModelo(movimiento.fuente || "—")
                + "</td><td>" + escaparCostoModelo(movimiento.observacion || "—")
                + "</td><td>" + etiquetaEstadoCostoModelo(movimiento.estado)
                + "</td><td>" + escaparCostoModelo(movimiento.usuario || "—") + "</td></tr>";
        });
        $("#historialCostoModeloBody").html(filas.join(""));
    }, "json");
});
