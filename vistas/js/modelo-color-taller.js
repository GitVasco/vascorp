var tablaModeloColorTaller = null;
var sectoresCatalogoMct = [];
var cargandoEdicionMct = false;

var idiomaDtMct = {
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
};

function alertaMct(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function refrescarSelectMct($select) {
    if (!$select || !$select.length || typeof $select.selectpicker !== "function") {
        return;
    }
    try {
        if ($select.data("selectpicker")) {
            $select.selectpicker("refresh");
        } else {
            $select.selectpicker({
                liveSearch: true,
                size: 10,
                container: $select.closest(".modal").length ? "body" : false
            });
        }
    } catch (e) {}
}

function setSelectMct($select, valor) {
    if (!$select || !$select.length) {
        return;
    }
    valor = valor == null ? "" : String(valor);
    $select.val(valor);
    if (typeof $select.selectpicker === "function" && $select.data("selectpicker")) {
        try {
            $select.selectpicker("val", valor);
            $select.selectpicker("refresh");
        } catch (e) {
            refrescarSelectMct($select);
        }
        return;
    }
    refrescarSelectMct($select);
}

function filtrosMctPost() {
    return {
        modelo: $("#filtroModeloMct").val() || "",
        cod_color: $("#filtroColorMct").val() || "",
        cod_sector: $("#filtroSectorMct").val() || "",
        estado: $("#filtroEstadoMct").val() || ""
    };
}

function recargarTablaMct() {
    if (tablaModeloColorTaller) {
        tablaModeloColorTaller.ajax.reload(null, false);
    }
    cargarResumenArticulosPorTallerMct();
}

function formatearNumeroMct(n) {
    n = Number(n) || 0;
    try {
        return n.toLocaleString("es-PE");
    } catch (e) {
        return String(n);
    }
}

function cargarResumenArticulosPorTallerMct() {
    var $box = $("#resumenArticulosPorTallerMct");
    if (!$box.length) {
        return;
    }
    $.post("ajax/modelo-color-taller.ajax.php", { accion: "resumenArticulosPorTaller" }, function (resp) {
        if (!resp || !resp.ok) {
            $box.html('<span class="text-muted">Sin datos</span>');
            return;
        }
        var data = resp.data || [];
        if (!data.length) {
            $box.html('<span class="text-muted">Aún no hay artículos cubiertos por la configuración</span>');
            return;
        }
        var chips = data.map(function (item) {
            var titulo = item.cod_sector + (item.nom_sector ? " — " + item.nom_sector : "");
            return '<span class="label label-primary" style="display:inline-block;margin:0;padding:6px 10px;font-size:12px;font-weight:600;" title="'
                + escaparAttrMct(titulo) + '">'
                + escaparMct(item.cod_sector)
                + ': <strong>' + formatearNumeroMct(item.total_articulos) + '</strong>'
                + "</span>";
        });
        chips.push(
            '<span class="label label-default" style="display:inline-block;margin:0;padding:6px 10px;font-size:12px;">'
            + "Total: <strong>" + formatearNumeroMct(resp.total) + "</strong></span>"
        );
        $box.html(chips.join(" "));
    }, "json").fail(function () {
        $box.html('<span class="text-danger">No se pudo cargar el resumen</span>');
    });
}

function resetSelectColorMct($select, deshabilitar) {
    $select.empty().append($("<option>").val("").text("— Todo el modelo —"));
    if (deshabilitar) {
        $select.prop("disabled", true);
    } else {
        $select.prop("disabled", false);
    }
    refrescarSelectMct($select);
}

function cargarColoresModeloMct($select, modelo, colorSeleccionado) {
    colorSeleccionado = colorSeleccionado == null ? "" : String(colorSeleccionado);
    if (!modelo) {
        resetSelectColorMct($select, true);
        return $.Deferred().resolve().promise();
    }

    $select.prop("disabled", true);
    refrescarSelectMct($select);

    return $.post("ajax/modelo-color-taller.ajax.php", {
        accion: "coloresModelo",
        modelo: modelo
    }, function (resp) {
        $select.empty().append($("<option>").val("").text("— Todo el modelo —"));
        if (resp && resp.ok && resp.data && resp.data.length) {
            resp.data.forEach(function (item) {
                var txt = item.cod_color + " — " + (item.nom_color || "");
                $select.append($("<option>").val(String(item.cod_color)).text(txt));
            });
        }
        if (colorSeleccionado !== "" && $select.find("option").filter(function () {
            return String($(this).val()) === colorSeleccionado;
        }).length === 0) {
            $select.append(
                $("<option>").val(colorSeleccionado).text(colorSeleccionado + " (histórico)")
            );
        }
        $select.prop("disabled", false);
        setSelectMct($select, colorSeleccionado);
    }, "json").fail(function () {
        resetSelectColorMct($select, false);
    });
}

function llenarSelectModelos($selects) {
    return $.post("ajax/modelo-color-taller.ajax.php", { accion: "listarModelos" }, function (resp) {
        $selects.each(function () {
            var $s = $(this);
            $s.find("option:not(:first)").remove();
            if (resp && resp.ok && resp.data) {
                resp.data.forEach(function (item) {
                    $s.append($("<option>").val(item.modelo).text(item.nombre || item.modelo));
                });
            }
            refrescarSelectMct($s);
        });
    }, "json");
}

function llenarSelectColoresFiltro($select, modelo) {
    var $s = $select;
    var keepSinColor = true;

    function baseOptions() {
        $s.empty();
        $s.append($("<option>").val("").text("Todos"));
        if (keepSinColor) {
            $s.append($("<option>").val("__SIN_COLOR__").text("Solo generales (sin color)"));
        }
    }

    if (!modelo) {
        return $.post("ajax/modelo-color-taller.ajax.php", { accion: "listarColores" }, function (resp) {
            baseOptions();
            if (resp && resp.ok && resp.data) {
                resp.data.forEach(function (item) {
                    var txt = item.cod_color + " — " + (item.nom_color || "");
                    $s.append($("<option>").val(item.cod_color).text(txt));
                });
            }
            refrescarSelectMct($s);
        }, "json");
    }

    return $.post("ajax/modelo-color-taller.ajax.php", {
        accion: "coloresModelo",
        modelo: modelo
    }, function (resp) {
        baseOptions();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (item) {
                var txt = item.cod_color + " — " + (item.nom_color || "");
                $s.append($("<option>").val(item.cod_color).text(txt));
            });
        }
        refrescarSelectMct($s);
    }, "json");
}

function llenarSelectSectores($selects) {
    return $.post("ajax/modelo-color-taller.ajax.php", { accion: "listarSectores" }, function (resp) {
        sectoresCatalogoMct = (resp && resp.ok && resp.data) ? resp.data : [];
        $selects.each(function () {
            var $s = $(this);
            $s.find("option:not(:first)").remove();
            sectoresCatalogoMct.forEach(function (item) {
                var txt = item.cod_sector + " — " + (item.nom_sector || "");
                $s.append($("<option>").val(item.cod_sector).text(txt));
            });
            refrescarSelectMct($s);
        });
    }, "json");
}

function htmlOpcionesTallerMct(valorSeleccionado) {
    var html = '<option value="">— Sin asignar —</option>';
    sectoresCatalogoMct.forEach(function (item) {
        var sel = valorSeleccionado && valorSeleccionado === item.cod_sector ? " selected" : "";
        html += '<option value="' + escaparAttrMct(item.cod_sector) + '"' + sel + ">"
            + escaparMct(item.cod_sector + " — " + (item.nom_sector || ""))
            + "</option>";
    });
    return html;
}

function escaparAttrMct(texto) {
    return String(texto == null ? "" : texto)
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;");
}

function limpiarDetalleColoresNuevoMct() {
    $("#bloqueReglaGeneralMct").hide();
    $("#nuevoSectorGeneralMct").val("");
    refrescarSelectMct($("#nuevoSectorGeneralMct"));
    $("#estadoReglaGeneralMct").text("—");
    $("#ayudaColoresModeloMct").text("Elige un modelo para ver sus colores uno por uno y asignar taller a cada color.");
    $("#bodyColoresNuevoMct").html(
        '<tr class="text-muted"><td colspan="4" class="text-center">Selecciona un modelo…</td></tr>'
    );
    $("#btnGuardarNuevoMct").prop("disabled", true);
}

function cargarDetalleColoresNuevoMct(modelo) {
    if (!modelo) {
        limpiarDetalleColoresNuevoMct();
        return $.Deferred().resolve().promise();
    }

    $("#bodyColoresNuevoMct").html(
        '<tr class="text-muted"><td colspan="4" class="text-center">Cargando colores del modelo…</td></tr>'
    );
    $("#btnGuardarNuevoMct").prop("disabled", true);

    return $.post("ajax/modelo-color-taller.ajax.php", {
        accion: "coloresModelo",
        modelo: modelo,
        con_asignacion: 1
    }, function (resp) {
        if (!resp || !resp.ok) {
            $("#bodyColoresNuevoMct").html(
                '<tr class="text-danger"><td colspan="4" class="text-center">No se pudieron cargar los colores</td></tr>'
            );
            return;
        }

        var colores = resp.data || [];
        $("#bloqueReglaGeneralMct").show();
        var rg = resp.regla_general || {};
        $("#nuevoSectorGeneralMct").val(rg.cod_sector || "");
        refrescarSelectMct($("#nuevoSectorGeneralMct"));
        if (rg.asignado) {
            $("#estadoReglaGeneralMct").html(
                '<span class="label label-warning">Ya tiene: '
                + escaparMct(rg.cod_sector + (rg.nom_sector ? " — " + rg.nom_sector : ""))
                + "</span>"
            );
        } else {
            $("#estadoReglaGeneralMct").html('<span class="label label-default">Sin regla general</span>');
        }

        if (!colores.length) {
            $("#ayudaColoresModeloMct").text("Este modelo aún no tiene colores registrados.");
            $("#bodyColoresNuevoMct").html(
                '<tr class="text-muted"><td colspan="4" class="text-center">Sin colores para este modelo</td></tr>'
            );
            actualizarEstadoBotonNuevoMct();
            return;
        }

        $("#ayudaColoresModeloMct").text(
            "Asigna taller color por color (" + colores.length + "). Solo se guardan las filas con taller."
        );

        var filas = colores.map(function (item) {
            var estado = item.asignado
                ? '<span class="label label-warning">Ya: ' + escaparMct(item.cod_sector || "") + "</span>"
                : '<span class="label label-default">Pendiente</span>';
            return "<tr data-cod-color=\"" + escaparAttrMct(item.cod_color) + "\">"
                + "<td><strong>" + escaparMct(item.cod_color) + "</strong></td>"
                + "<td>" + escaparMct(item.nom_color || "") + "</td>"
                + '<td><select class="form-control input-sm selectTallerColorMct">'
                + htmlOpcionesTallerMct(item.cod_sector || "")
                + "</select></td>"
                + "<td>" + estado + "</td>"
                + "</tr>";
        });
        $("#bodyColoresNuevoMct").html(filas.join(""));
        actualizarEstadoBotonNuevoMct();
    }, "json").fail(function () {
        $("#bodyColoresNuevoMct").html(
            '<tr class="text-danger"><td colspan="4" class="text-center">Error de comunicación</td></tr>'
        );
    });
}

function filasNuevoMctParaGuardar() {
    var filas = [];
    var sectorGeneral = $("#nuevoSectorGeneralMct").val() || "";
    if (sectorGeneral) {
        filas.push({ cod_color: "", cod_sector: sectorGeneral });
    }
    $("#bodyColoresNuevoMct tr[data-cod-color]").each(function () {
        var cod = $(this).attr("data-cod-color") || "";
        var sector = $(this).find(".selectTallerColorMct").val() || "";
        if (sector) {
            filas.push({ cod_color: cod, cod_sector: sector });
        }
    });
    return filas;
}

function actualizarEstadoBotonNuevoMct() {
    $("#btnGuardarNuevoMct").prop("disabled", filasNuevoMctParaGuardar().length < 1);
}

function cargarCatalogosMct() {
    return $.when(
        llenarSelectModelos($("#filtroModeloMct, #nuevoModeloMct, #editarModeloMct")),
        llenarSelectColoresFiltro($("#filtroColorMct"), ""),
        llenarSelectSectores($(
            "#filtroSectorMct, #editarSectorMct, #nuevoSectorDefaultMct, #nuevoSectorGeneralMct"
        ))
    ).always(function () {
        resetSelectColorMct($("#editarColorMct"), true);
        limpiarDetalleColoresNuevoMct();
        refrescarSelectMct($("#filtroEstadoMct"));
        refrescarSelectMct($("#editarEstadoMct"));
    });
}

if ($(".tablaModeloColorTaller").length) {
    tablaModeloColorTaller = $(".tablaModeloColorTaller").DataTable({
        ajax: {
            url: "ajax/tabla-modelo-color-taller.ajax.php",
            type: "POST",
            data: function () {
                return filtrosMctPost();
            }
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 25,
        language: idiomaDtMct
    });

    cargarCatalogosMct();
    cargarResumenArticulosPorTallerMct();
}

$(document).on("changed.bs.select change", ".filtroModeloColorTaller", function () {
    if ($(this).is("#filtroModeloMct")) {
        var modelo = $(this).val() || "";
        llenarSelectColoresFiltro($("#filtroColorMct"), modelo).always(function () {
            recargarTablaMct();
        });
        return;
    }
    recargarTablaMct();
});

$("#btnRefrescarMct").on("click", function () {
    recargarTablaMct();
});

$(document).on("changed.bs.select change", "#nuevoModeloMct", function () {
    cargarDetalleColoresNuevoMct($(this).val() || "");
});

$(document).on("changed.bs.select change", "#editarModeloMct", function () {
    // Evita que al abrir edición se limpie el color al setear el modelo
    if (cargandoEdicionMct) {
        return;
    }
    cargarColoresModeloMct($("#editarColorMct"), $(this).val() || "", "");
});

$(document).on("changed.bs.select change", "#nuevoSectorGeneralMct", function () {
    actualizarEstadoBotonNuevoMct();
});

$(document).on("change", ".selectTallerColorMct", function () {
    actualizarEstadoBotonNuevoMct();
});

$("#btnAplicarTallerDefaultMct").on("click", function () {
    var sector = $("#nuevoSectorDefaultMct").val() || "";
    if (!sector) {
        alertaMct("warning", "Elige primero un taller por defecto");
        return;
    }
    $("#bodyColoresNuevoMct .selectTallerColorMct").each(function () {
        if (!$(this).val()) {
            $(this).val(sector);
        }
    });
    actualizarEstadoBotonNuevoMct();
});

$("#modalAgregarModeloColorTaller").on("shown.bs.modal", function () {
    refrescarSelectMct($("#nuevoModeloMct"));
    refrescarSelectMct($("#nuevoSectorDefaultMct"));
    refrescarSelectMct($("#nuevoSectorGeneralMct"));
});

$("#modalAgregarModeloColorTaller").on("hidden.bs.modal", function () {
    $("#nuevoModeloMct").val("");
    $("#nuevoSectorDefaultMct").val("");
    $("#nuevaObsMct").val("");
    refrescarSelectMct($("#nuevoModeloMct"));
    refrescarSelectMct($("#nuevoSectorDefaultMct"));
    limpiarDetalleColoresNuevoMct();
});

$("#modalEditarModeloColorTaller").on("shown.bs.modal", function () {
    refrescarSelectMct($("#editarModeloMct"));
    refrescarSelectMct($("#editarColorMct"));
    refrescarSelectMct($("#editarSectorMct"));
    refrescarSelectMct($("#editarEstadoMct"));
    // Reafirmar color seleccionado después del refresh del selectpicker
    var colorActual = $("#editarColorMct").val();
    if (colorActual !== null && colorActual !== undefined) {
        setSelectMct($("#editarColorMct"), colorActual);
    }
});

$("#formAgregarModeloColorTaller").on("submit", function (e) {
    e.preventDefault();
    var modelo = $("#nuevoModeloMct").val() || "";
    var filas = filasNuevoMctParaGuardar();
    if (!modelo) {
        alertaMct("warning", "Selecciona un modelo");
        return;
    }
    if (!filas.length) {
        alertaMct("warning", "Asigna taller al menos a un color (o a la regla general)");
        return;
    }

    var $btn = $("#btnGuardarNuevoMct").prop("disabled", true);
    $.post("ajax/modelo-color-taller.ajax.php", {
        accion: "crearMasivo",
        modelo: modelo,
        observacion: $("#nuevaObsMct").val() || "",
        filas: JSON.stringify(filas)
    }, function (resp) {
        if (resp && resp.ok) {
            $("#modalAgregarModeloColorTaller").modal("hide");
            recargarTablaMct();
            alertaMct("success", resp.mensaje || "Guardado");
        } else {
            alertaMct("error", (resp && resp.mensaje) ? resp.mensaje : "Error al guardar");
            $btn.prop("disabled", false);
        }
    }, "json").fail(function () {
        alertaMct("error", "Error de comunicación");
        $btn.prop("disabled", false);
    });
});

$(document).on("click", ".btnEditarModeloColorTaller", function () {
    var id = $(this).attr("data-id");
    $.post("ajax/modelo-color-taller.ajax.php", { accion: "mostrar", id: id }, function (resp) {
        if (!resp || !resp.ok || !resp.data) {
            alertaMct("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cargar");
            return;
        }
        var d = resp.data;
        var colorEditar = d.cod_color == null ? "" : String(d.cod_color);
        cargandoEdicionMct = true;

        $("#editarIdMct").val(d.id);
        if (d.modelo && $("#editarModeloMct option").filter(function () {
            return $(this).val() === d.modelo;
        }).length === 0) {
            var txtModelo = d.modelo + (d.nombre_modelo ? " - " + d.nombre_modelo : "");
            $("#editarModeloMct").append($("<option>").val(d.modelo).text(txtModelo));
        }
        setSelectMct($("#editarModeloMct"), d.modelo || "");
        setSelectMct($("#editarSectorMct"), d.cod_sector || "");
        setSelectMct($("#editarEstadoMct"), String(parseInt(d.estado, 10) === 0 ? 0 : 1));
        $("#editarObsMct").val(d.observacion || "");

        var textoColor = colorEditar === ""
            ? "Todo el modelo (sin color específico)"
            : colorEditar + (d.nom_color ? " — " + d.nom_color : "");
        $("#editarColorActualMct").text(textoColor);

        cargarColoresModeloMct($("#editarColorMct"), d.modelo || "", colorEditar).always(function () {
            setSelectMct($("#editarColorMct"), colorEditar);
            $("#modalEditarModeloColorTaller").modal("show");
            // Tras mostrar el modal, reaplicar por si selectpicker redibuja
            setTimeout(function () {
                setSelectMct($("#editarColorMct"), colorEditar);
                cargandoEdicionMct = false;
            }, 150);
        });
    }, "json").fail(function () {
        cargandoEdicionMct = false;
        alertaMct("error", "Error de comunicación");
    });
});

$("#formEditarModeloColorTaller").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "editar" });
    $.post("ajax/modelo-color-taller.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalEditarModeloColorTaller").modal("hide");
            recargarTablaMct();
            alertaMct("success", resp.mensaje || "Actualizado");
        } else {
            alertaMct("error", (resp && resp.mensaje) ? resp.mensaje : "Error al editar");
        }
    }, "json").fail(function () {
        alertaMct("error", "Error de comunicación");
    });
});

function eliminarModeloColorTaller(id) {
    $.post("ajax/modelo-color-taller.ajax.php", { accion: "eliminar", id: id }, function (resp) {
        if (resp && resp.ok) {
            recargarTablaMct();
            alertaMct("success", resp.mensaje || "Eliminado");
        } else {
            alertaMct("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo eliminar");
        }
    }, "json").fail(function () {
        alertaMct("error", "Error de comunicación");
    });
}

$(document).on("click", ".btnEliminarModeloColorTaller", function () {
    var id = $(this).attr("data-id");
    var modelo = $(this).attr("data-modelo") || "";
    var color = $(this).attr("data-color") || "";
    var detalle = modelo + (color ? " / color " + color : " (todo el modelo)");

    if (typeof swal === "function") {
        swal({
            title: "¿Eliminar asignación?",
            text: detalle,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd4b39",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result && result.value) {
                eliminarModeloColorTaller(id);
            }
        });
        return;
    }
    if (window.confirm("¿Eliminar asignación " + detalle + "?")) {
        eliminarModeloColorTaller(id);
    }
});

function escaparMct(texto) {
    return $("<div>").text(texto == null ? "" : String(texto)).html();
}

function enviarImportacionMct(confirmar) {
    var input = $("#archivoModeloColorTaller")[0];
    if (!input || !input.files || !input.files.length) {
        alertaMct("warning", "Selecciona un archivo Excel o CSV");
        return;
    }

    var datos = new FormData();
    datos.append("accion", "importar");
    datos.append("confirmar", confirmar ? "1" : "0");
    datos.append("archivo", input.files[0]);

    var $botones = $("#btnPrevisualizarMct, #btnConfirmarImportacionMct");
    $botones.prop("disabled", true);

    $.ajax({
        url: "ajax/modelo-color-taller.ajax.php",
        method: "POST",
        data: datos,
        dataType: "json",
        processData: false,
        contentType: false
    }).done(function (resp) {
        if (!resp || !resp.ok) {
            alertaMct("error", resp && resp.mensaje ? resp.mensaje : "No se pudo procesar el archivo");
            return;
        }
        if (confirmar) {
            $("#modalImportarModeloColorTaller").modal("hide");
            $("#archivoModeloColorTaller").val("");
            $("#resumenImportacionMct").hide().text("");
            $("#contenedorPreviewMct").hide();
            $("#previewImportacionMctBody").empty();
            recargarTablaMct();
            alertaMct("success", resp.mensaje || "Importación completada");
            return;
        }

        var rechazadas = Number(resp.rechazadas || 0);
        $("#resumenImportacionMct")
            .removeClass("alert-success alert-danger")
            .addClass(rechazadas ? "alert-danger" : "alert-success")
            .text(
                "Total: " + resp.total
                + " · Válidas: " + resp.validas
                + " · Rechazadas: " + rechazadas
                + " · A crear: " + (resp.a_crear || 0)
                + " · A actualizar: " + (resp.a_actualizar || 0)
            )
            .show();

        var filas = (resp.data || []).map(function (fila) {
            var errores = fila.errores && fila.errores.length
                ? "<span class='text-danger'>" + escaparMct(fila.errores.join("; ")) + "</span>"
                : "<span class='text-success'>OK</span>";
            var colorTxt = fila.cod_color ? fila.cod_color : "(todo el modelo)";
            if (fila.color_normalizado && fila.cod_color_original) {
                colorTxt = fila.cod_color_original + " → " + fila.cod_color;
            }
            var accionTxt = fila.accion === "actualizar"
                ? "<span class='label label-warning'>Actualizar</span>"
                : (fila.accion === "crear"
                    ? "<span class='label label-primary'>Crear</span>"
                    : "—");
            return "<tr><td>" + escaparMct(fila.fila)
                + "</td><td>" + escaparMct(fila.modelo)
                + "</td><td>" + escaparMct(colorTxt)
                + "</td><td>" + escaparMct(fila.cod_sector)
                + "</td><td>" + accionTxt
                + "</td><td>" + errores + "</td></tr>";
        });
        $("#previewImportacionMctBody").html(filas.join(""));
        $("#contenedorPreviewMct").show();
        $("#btnConfirmarImportacionMct").prop("disabled", rechazadas > 0 || Number(resp.validas || 0) < 1);
    }).fail(function () {
        alertaMct("error", "Error de comunicación durante la importación");
    }).always(function () {
        $("#btnPrevisualizarMct").prop("disabled", false);
        if ($("#resumenImportacionMct").hasClass("alert-success") && Number($("#previewImportacionMctBody tr").length) > 0) {
            $("#btnConfirmarImportacionMct").prop("disabled", false);
        }
    });
}

$("#btnPrevisualizarMct").on("click", function () {
    enviarImportacionMct(false);
});

$("#btnConfirmarImportacionMct").on("click", function () {
    enviarImportacionMct(true);
});

$("#modalImportarModeloColorTaller").on("hidden.bs.modal", function () {
    $("#archivoModeloColorTaller").val("");
    $("#resumenImportacionMct").hide().removeClass("alert-success alert-danger").text("");
    $("#contenedorPreviewMct").hide();
    $("#previewImportacionMctBody").empty();
    $("#btnConfirmarImportacionMct").prop("disabled", true);
});
