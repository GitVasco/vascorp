var tablaGruposMarcas = null;
var catalogoMarcasGrupo = [];

var idiomaDtGruposMarcas = {
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

function alertaGrupoMarcas(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function recargarTablaGruposMarcas() {
    if (tablaGruposMarcas) {
        tablaGruposMarcas.ajax.reload(null, false);
    }
}

function cargarCatalogoMarcasGrupo(callback) {
    $.post("ajax/grupos-marcas-comercial.ajax.php", { accion: "catalogoMarcas" }, function (resp) {
        catalogoMarcasGrupo = (resp && resp.ok && resp.data) ? resp.data : [];
        if (typeof callback === "function") {
            callback();
        }
    }, "json");
}

function poblarSelectMarcasGrupo() {
    var $sel = $("#selectMarcaAgregarGrupo");
    if (!$sel.length) {
        return;
    }
    $sel.find("option:not(:first)").remove();
    catalogoMarcasGrupo.forEach(function (m) {
        $sel.append($("<option>").val(m.id).text(m.marca));
    });
}

function cargarMarcasEnModal(idGrupo) {
    $.post("ajax/grupos-marcas-comercial.ajax.php", { accion: "listarMarcas", idGrupo: idGrupo }, function (resp) {
        var $tbody = $("#tablaMarcasGrupo tbody");
        $tbody.empty();
        if (!resp || !resp.ok || !resp.data || !resp.data.length) {
            var cols = window.gruposMarcasPuedeEditar ? 3 : 2;
            $tbody.append("<tr><td colspan='" + cols + "' class='text-muted'>Sin marcas en el grupo</td></tr>");
            return;
        }
        resp.data.forEach(function (m) {
            var fila = "<tr><td>" + (m.marca || "") + "</td><td>" + (m.fecreg || "—") + "</td>";
            if (window.gruposMarcasPuedeEditar) {
                fila += "<td><button type='button' class='btn btn-xs btn-danger btnQuitarMarcaGrupo' idDetalle='"
                    + m.id + "'><i class='fa fa-trash'></i></button></td>";
            }
            fila += "</tr>";
            $tbody.append(fila);
        });
    }, "json");
}

if ($(".tablaGruposMarcas").length) {
    tablaGruposMarcas = $(".tablaGruposMarcas").DataTable({
        ajax: "ajax/tabla-grupos-marcas.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 20,
        language: idiomaDtGruposMarcas
    });

    if (window.gruposMarcasPuedeEditar) {
        cargarCatalogoMarcasGrupo(poblarSelectMarcasGrupo);
    }
}

$("#formAgregarGrupoMarcas").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "crear" });
    $.post("ajax/grupos-marcas-comercial.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalAgregarGrupoMarcas").modal("hide");
            $("#formAgregarGrupoMarcas")[0].reset();
            recargarTablaGruposMarcas();
            alertaGrupoMarcas("success", resp.mensaje || "Grupo creado");
        } else {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "Error al crear");
        }
    }, "json").fail(function () {
        alertaGrupoMarcas("error", "Error de comunicación");
    });
});

$(document).on("click", ".btnEditarGrupoMarcas", function () {
    var id = $(this).attr("idGrupo");
    $.post("ajax/grupos-marcas-comercial.ajax.php", { accion: "detalle", idGrupo: id }, function (resp) {
        if (!resp || !resp.ok || !resp.data) {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cargar");
            return;
        }
        var g = resp.data;
        $("#editarIdGrupoMarcas").val(g.id);
        $("#editarCodigoGrupoMarcas").val(g.codigo);
        $("#editarNombreGrupoMarcas").val(g.nombre);
        $("#editarDescripcionGrupoMarcas").val(g.descripcion || "");
        $("#editarEstadoGrupoMarcas").val(String(g.estado));
        $("#modalEditarGrupoMarcas").modal("show");
    }, "json");
});

$("#formEditarGrupoMarcas").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "editar" });
    $.post("ajax/grupos-marcas-comercial.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalEditarGrupoMarcas").modal("hide");
            recargarTablaGruposMarcas();
            alertaGrupoMarcas("success", resp.mensaje || "Grupo actualizado");
        } else {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "Error al editar");
        }
    }, "json");
});

$(document).on("click", ".btnToggleEstadoGrupoMarcas", function () {
    var id = $(this).attr("idGrupo");
    var estado = $(this).attr("nuevoEstado");
    $.post("ajax/grupos-marcas-comercial.ajax.php", {
        accion: "cambiarEstado",
        idGrupo: id,
        estado: estado
    }, function (resp) {
        if (resp && resp.ok) {
            recargarTablaGruposMarcas();
        } else {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cambiar");
        }
    }, "json");
});

$(document).on("click", ".btnVerMarcasGrupo", function () {
    var id = $(this).attr("idGrupo");
    var codigo = $(this).attr("codigoGrupo") || "";
    var nombre = $(this).attr("nombreGrupo") || "";
    $("#idGrupoMarcasModal").val(id);
    $("#tituloMarcasGrupo").text(codigo + " — " + nombre);
    cargarMarcasEnModal(id);
    $("#modalMarcasGrupo").modal("show");
});

$("#btnAgregarMarcaGrupo").on("click", function () {
    var idGrupo = $("#idGrupoMarcasModal").val();
    var idMarca = $("#selectMarcaAgregarGrupo").val();
    if (!idMarca) {
        alertaGrupoMarcas("warning", "Selecciona una marca");
        return;
    }
    $.post("ajax/grupos-marcas-comercial.ajax.php", {
        accion: "agregarMarca",
        idGrupo: idGrupo,
        idMarca: idMarca
    }, function (resp) {
        if (resp && resp.ok) {
            cargarMarcasEnModal(idGrupo);
            recargarTablaGruposMarcas();
            alertaGrupoMarcas("success", resp.mensaje || "Marca agregada");
        } else {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo agregar");
        }
    }, "json");
});

$(document).on("click", ".btnQuitarMarcaGrupo", function () {
    var idDetalle = $(this).attr("idDetalle");
    var idGrupo = $("#idGrupoMarcasModal").val();
    if (!confirm("¿Quitar esta marca del grupo?")) {
        return;
    }
    $.post("ajax/grupos-marcas-comercial.ajax.php", {
        accion: "quitarMarca",
        idDetalle: idDetalle
    }, function (resp) {
        if (resp && resp.ok) {
            cargarMarcasEnModal(idGrupo);
            recargarTablaGruposMarcas();
        } else {
            alertaGrupoMarcas("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo quitar");
        }
    }, "json");
});
