var tablaAsignacionGruposMarcas = null;

var idiomaDtAsignacionGrupos = {
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

function alertaAsignacionGrupo(tipo, mensaje) {
    if (typeof swal === "function") {
        swal({ type: tipo, title: mensaje, confirmButtonText: "Cerrar" });
        return;
    }
    alert(mensaje);
}

function filtrosAsignacionGrupoPost() {
    return {
        cod_vendedor: $("#filtroVendedorAsignacion").val() || "",
        id_grupo: $("#filtroGrupoAsignacion").val() || "",
        id_marca: $("#filtroMarcaAsignacion").val() || "",
        fecha_ref: $("#filtroFechaAsignacion").val() || "",
        vigente: $("#filtroVigenteAsignacion").val() || ""
    };
}

function recargarTablaAsignacionGrupo() {
    if (tablaAsignacionGruposMarcas) {
        tablaAsignacionGruposMarcas.ajax.reload(null, false);
    }
}

function cargarFiltrosAsignacionGrupo() {
    $.post("ajax/asignacion-grupos-marcas.ajax.php", { accion: "listarVendedores" }, function (resp) {
        var $v = $("#filtroVendedorAsignacion, #nuevoVendedorAsignacion");
        $v.filter("#filtroVendedorAsignacion").find("option:not(:first)").remove();
        $v.filter("#nuevoVendedorAsignacion").find("option:not(:first)").remove();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (item) {
                var txt = item.codigo + " — " + (item.nombre || "");
                $("#filtroVendedorAsignacion").append($("<option>").val(item.codigo).text(txt));
                $("#nuevoVendedorAsignacion").append($("<option>").val(item.codigo).text(txt));
            });
        }
    }, "json");

    $.post("ajax/asignacion-grupos-marcas.ajax.php", { accion: "listarGruposActivos" }, function (resp) {
        var $g = $("#filtroGrupoAsignacion, #nuevoGruposAsignacion");
        $g.filter("#filtroGrupoAsignacion").find("option:not(:first)").remove();
        $("#nuevoGruposAsignacion").empty();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (item) {
                var txt = item.codigo + " — " + (item.nombre || "");
                $("#filtroGrupoAsignacion").append($("<option>").val(item.id).text(txt));
                $("#nuevoGruposAsignacion").append($("<option>").val(item.id).text(txt));
            });
        }
    }, "json");

    $.post("ajax/asignacion-grupos-marcas.ajax.php", { accion: "catalogoMarcas" }, function (resp) {
        $("#filtroMarcaAsignacion").find("option:not(:first)").remove();
        if (resp && resp.ok && resp.data) {
            resp.data.forEach(function (item) {
                $("#filtroMarcaAsignacion").append($("<option>").val(item.id).text(item.marca));
            });
        }
    }, "json");
}

if ($(".tablaAsignacionGruposMarcas").length) {
    tablaAsignacionGruposMarcas = $(".tablaAsignacionGruposMarcas").DataTable({
        ajax: {
            url: "ajax/tabla-asignacion-grupos-marcas.ajax.php",
            type: "POST",
            data: function () {
                return filtrosAsignacionGrupoPost();
            }
        },
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[3, "desc"]],
        pageLength: 25,
        language: idiomaDtAsignacionGrupos
    });

    cargarFiltrosAsignacionGrupo();
}

$(document).on("change", ".filtroAsignacionGrupo", function () {
    recargarTablaAsignacionGrupo();
});

$("#btnRefrescarAsignacionGrupo").on("click", function () {
    recargarTablaAsignacionGrupo();
});

$("#formAgregarAsignacionGrupo").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({ name: "accion", value: "crear" });
    $.post("ajax/asignacion-grupos-marcas.ajax.php", $.param(data), function (resp) {
        if (resp && resp.ok) {
            $("#modalAgregarAsignacionGrupo").modal("hide");
            $("#formAgregarAsignacionGrupo")[0].reset();
            var hoy = new Date();
            var primerDia = hoy.getFullYear() + "-" + String(hoy.getMonth() + 1).padStart(2, "0") + "-01";
            $("#nuevaFechaInicioAsignacion").val(primerDia);
            recargarTablaAsignacionGrupo();
            alertaAsignacionGrupo("success", resp.mensaje || "Asignaciones creadas");
        } else {
            alertaAsignacionGrupo("error", (resp && resp.mensaje) ? resp.mensaje : "Error al crear");
        }
    }, "json").fail(function () {
        alertaAsignacionGrupo("error", "Error de comunicación");
    });
});

$(document).on("click", ".btnCerrarAsignacionGrupo", function () {
    var id = $(this).attr("idAsignacion");
    var texto = $(this).attr("vendedor") || "";
    $("#cerrarIdAsignacion").val(id);
    $("#cerrarTextoAsignacion").text(texto);
    $("#modalCerrarAsignacionGrupo").modal("show");
});

$("#formCerrarAsignacionGrupo").on("submit", function (e) {
    e.preventDefault();
    var id = $("#cerrarIdAsignacion").val();
    var fechaFin = $("#cerrarFechaFinAsignacion").val();
    $.post("ajax/asignacion-grupos-marcas.ajax.php", {
        accion: "cerrar",
        idAsignacion: id,
        fecha_fin: fechaFin
    }, function (resp) {
        if (resp && resp.ok) {
            $("#modalCerrarAsignacionGrupo").modal("hide");
            recargarTablaAsignacionGrupo();
            alertaAsignacionGrupo("success", resp.mensaje || "Asignación cerrada");
        } else {
            alertaAsignacionGrupo("error", (resp && resp.mensaje) ? resp.mensaje : "No se pudo cerrar");
        }
    }, "json");
});
