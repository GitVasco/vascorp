var tablaZonasComerciales = null;
var tablaZonasPorRevisar = null;

var idiomaDtZonas = {
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

if ($(".tablaZonasComerciales").length) {
    tablaZonasComerciales = $(".tablaZonasComerciales").DataTable({
        ajax: "ajax/tabla-zonas-comerciales.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 20,
        language: idiomaDtZonas
    });
}

if ($(".tablaZonasPorRevisar").length) {
    tablaZonasPorRevisar = $(".tablaZonasPorRevisar").DataTable({
        ajax: "ajax/tabla-zonas-por-revisar.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[1, "asc"]],
        pageLength: 25,
        language: idiomaDtZonas
    });
}

$(document).on("click", ".btnGuardarZonaRevisar", function () {
    var codigo = $(this).attr("codigoCliente");
    var idZona = $(this).closest("td").find(".selectZonaRevisar").val() || "";
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "asignarZonaCliente",
        codigoCliente: codigo,
        idZona: idZona
    }, function (resp) {
        if (resp && resp.ok) {
            if (tablaZonasPorRevisar) {
                tablaZonasPorRevisar.ajax.reload(null, false);
            }
            alertaZona("success", resp.mensaje || "Guardado");
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo guardar");
        }
    }, "json");
});

function recargarTablaZonasComerciales() {
    if (tablaZonasComerciales) {
        tablaZonasComerciales.ajax.reload(null, false);
    }
}

function alertaZona(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

$("#formAgregarZonaComercial").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "crear" });

    $.post("ajax/zonas-comerciales.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalAgregarZonaComercial").modal("hide");
            $("#formAgregarZonaComercial")[0].reset();
            recargarTablaZonasComerciales();
            alertaZona("success", resp.mensaje || "Zona creada");
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "Error al crear");
        }
    }, "json").fail(function () {
        alertaZona("error", "Error de comunicación");
    });
});

$(document).on("click", ".btnEditarZonaComercial", function () {
    var id = $(this).attr("idZona");
    $.post("ajax/zonas-comerciales.ajax.php", { accion: "detalle", idZona: id }, function (resp) {
        if (!resp || !resp.ok || !resp.data) {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cargar");
            return;
        }
        var z = resp.data;
        $("#editarIdZona").val(z.id);
        $("#editarCodigoZona").val(z.codigo);
        $("#editarNombreZona").val(z.nombre);
        $("#editarMacrozonaZona").val(z.macrozona || "lima");
        $("#editarDescripcionZona").val(z.descripcion || "");
        $("#editarOrdenZona").val(z.orden || 0);
        $("#editarColorZona").val(z.color || "#3c8dbc");
        $("#editarEstadoZona").val(String(z.estado));
        $("#modalEditarZonaComercial").modal("show");
    }, "json");
});

$("#formEditarZonaComercial").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "editar" });

    $.post("ajax/zonas-comerciales.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalEditarZonaComercial").modal("hide");
            recargarTablaZonasComerciales();
            alertaZona("success", resp.mensaje || "Zona actualizada");
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "Error al editar");
        }
    }, "json").fail(function () {
        alertaZona("error", "Error de comunicación");
    });
});

$(document).on("click", ".btnToggleEstadoZona", function () {
    var id = $(this).attr("idZona");
    var estado = $(this).attr("nuevoEstado");
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "cambiarEstado",
        idZona: id,
        estado: estado
    }, function (resp) {
        if (resp && resp.ok) {
            recargarTablaZonasComerciales();
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cambiar");
        }
    }, "json");
});

function cargarUbigeosZona(idZona) {
    $.post("ajax/zonas-comerciales.ajax.php", { accion: "listarUbigeos", idZona: idZona }, function (resp) {
        var $tbody = $("#tablaUbigeosAsignados tbody");
        $tbody.empty();
        if (!resp || !resp.ok || !resp.data || !resp.data.length) {
            $tbody.append("<tr><td colspan='5' class='text-muted'>Sin ubigeos asignados</td></tr>");
            return;
        }
        resp.data.forEach(function (u) {
            var fila = "<tr>"
                + "<td>" + (u.cod_ubi || "") + "</td>"
                + "<td>" + (u.departamento || "") + "</td>"
                + "<td>" + (u.provincia || "") + "</td>"
                + "<td>" + (u.distrito || u.nom_ubi || "") + "</td>";
            if ($("#buscarUbigeoZona").length) {
                fila += "<td><button type='button' class='btn btn-xs btn-danger btnQuitarUbigeoZona' idRegla='"
                    + u.id + "'><i class='fa fa-trash'></i></button></td>";
            }
            fila += "</tr>";
            $tbody.append(fila);
        });
    }, "json");
}

$(document).on("click", ".btnVerUbigeosZona", function () {
    var id = $(this).attr("idZona");
    var nombre = $(this).attr("nombreZona") || "";
    $("#idZonaUbigeos").val(id);
    $("#tituloUbigeosZona").text(nombre);
    $("#resultadosBusquedaUbigeo").empty();
    $("#buscarUbigeoZona").val("");
    cargarUbigeosZona(id);
    $("#modalUbigeosZona").modal("show");
});

function buscarUbigeosParaZona() {
    var q = $.trim($("#buscarUbigeoZona").val() || "");
    if (q.length < 2) {
        $("#resultadosBusquedaUbigeo").html("<span class='text-muted'>Escribe al menos 2 caracteres</span>");
        return;
    }
    $.post("ajax/zonas-comerciales.ajax.php", { accion: "buscarUbigeos", q: q }, function (resp) {
        var $box = $("#resultadosBusquedaUbigeo");
        $box.empty();
        if (!resp || !resp.ok || !resp.data || !resp.data.length) {
            $box.html("<span class='text-muted'>Sin resultados</span>");
            return;
        }
        resp.data.forEach(function (u) {
            var ocupado = u.id_zona ? (" · ya en " + (u.zona_nombre || "otra zona")) : "";
            var btn = "<button type='button' class='btn btn-xs btn-primary btnAsignarUbigeoZona' codUbi='"
                + u.cod_ubi + "'>Asignar</button>";
            $box.append(
                "<div style='padding:4px 0;border-bottom:1px solid #eee;'>"
                + btn + " <strong>" + (u.distrito || "") + "</strong> — "
                + (u.provincia || "") + " / " + (u.departamento || "")
                + " <small>(" + (u.cod_ubi || "") + ")" + ocupado + "</small></div>"
            );
        });
    }, "json");
}

$("#btnBuscarUbigeoZona").on("click", buscarUbigeosParaZona);
$("#buscarUbigeoZona").on("keydown", function (e) {
    if (e.keyCode === 13) {
        e.preventDefault();
        buscarUbigeosParaZona();
    }
});

$(document).on("click", ".btnAsignarUbigeoZona", function () {
    var idZona = $("#idZonaUbigeos").val();
    var codUbi = $(this).attr("codUbi");
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "asignarUbigeo",
        idZona: idZona,
        codUbi: codUbi
    }, function (resp) {
        if (resp && resp.ok) {
            cargarUbigeosZona(idZona);
            recargarTablaZonasComerciales();
            buscarUbigeosParaZona();
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo asignar");
        }
    }, "json");
});

$(document).on("click", ".btnQuitarUbigeoZona", function () {
    var idRegla = $(this).attr("idRegla");
    var idZona = $("#idZonaUbigeos").val();
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "quitarUbigeo",
        idRegla: idRegla
    }, function (resp) {
        if (resp && resp.ok) {
            cargarUbigeosZona(idZona);
            recargarTablaZonasComerciales();
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo quitar");
        }
    }, "json");
});

function refrescarSelectVendedorZona($select) {
    if (!$select || !$select.length) {
        return;
    }
    if ($select.hasClass("selectpicker") || $select.data("selectpicker")) {
        $select.selectpicker("refresh");
        return;
    }
    if (typeof $select.selectpicker === "function") {
        $select.selectpicker({
            liveSearch: true,
            size: 8,
            noneSelectedText: "Seleccionar vendedor activo…"
        });
    }
}

function cargarVendedoresZona(idZona) {
    $.post("ajax/zonas-comerciales.ajax.php", { accion: "listarVendedores", idZona: idZona }, function (resp) {
        var $tbody = $("#tablaVendedoresAsignados tbody");
        var $select = $("#selectVendedorZonaDisponible");
        $tbody.empty();
        if ($select.length) {
            $select.empty().append('<option value="">Seleccionar…</option>');
        }

        if (!resp || !resp.ok) {
            $tbody.append("<tr><td colspan='3' class='text-muted'>Error al cargar</td></tr>");
            refrescarSelectVendedorZona($select);
            return;
        }

        if (!resp.data || !resp.data.length) {
            $tbody.append("<tr><td colspan='3' class='text-muted'>Sin vendedores asignados</td></tr>");
        } else {
            resp.data.forEach(function (v) {
                var fila = "<tr>"
                    + "<td>" + (v.cod_vendedor || "") + "</td>"
                    + "<td>" + (v.nombre_vendedor || "") + "</td>";
                if ($select.length) {
                    fila += "<td><button type='button' class='btn btn-xs btn-danger btnQuitarVendedorZona' idRegla='"
                        + v.id + "'><i class='fa fa-trash'></i></button></td>";
                }
                fila += "</tr>";
                $tbody.append(fila);
            });
        }

        if ($select.length && resp.disponibles && resp.disponibles.length) {
            resp.disponibles.forEach(function (v) {
                $select.append(
                    '<option value="' + v.codigo + '">'
                    + v.codigo + " - " + (v.descripcion || "")
                    + "</option>"
                );
            });
        }
        refrescarSelectVendedorZona($select);
    }, "json");
}

$(document).on("click", ".btnVerVendedoresZona", function () {
    var id = $(this).attr("idZona");
    var nombre = $(this).attr("nombreZona") || "";
    $("#idZonaVendedores").val(id);
    $("#tituloVendedoresZona").text(nombre);
    cargarVendedoresZona(id);
    $("#modalVendedoresZona").modal("show");
});

$("#btnAsignarVendedorZona").on("click", function () {
    var idZona = $("#idZonaVendedores").val();
    var $select = $("#selectVendedorZonaDisponible");
    var cod = $select.val() || "";
    if (!cod) {
        alertaZona("error", "Selecciona un vendedor");
        return;
    }
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "asignarVendedor",
        idZona: idZona,
        codVendedor: cod
    }, function (resp) {
        if (resp && resp.ok) {
            cargarVendedoresZona(idZona);
            recargarTablaZonasComerciales();
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo asignar");
        }
    }, "json");
});

$(document).on("click", ".btnQuitarVendedorZona", function () {
    var idRegla = $(this).attr("idRegla");
    var idZona = $("#idZonaVendedores").val();
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "quitarVendedor",
        idRegla: idRegla
    }, function (resp) {
        if (resp && resp.ok) {
            cargarVendedoresZona(idZona);
            recargarTablaZonasComerciales();
        } else {
            alertaZona("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo quitar");
        }
    }, "json");
});
