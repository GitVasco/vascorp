if ($(".tablaCategoriasComerciales").length) {

    var tablaCategoriasComerciales = $(".tablaCategoriasComerciales").DataTable({
        ajax: "ajax/facturacion/tabla-categorias-comerciales.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
        pageLength: 20,
        lengthMenu: [[20, 40, 60, -1], [20, 40, 60, "Todos"]],
        language: {
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
        }
    });

}

function recargarTablaCategoriasComerciales() {
    if (typeof tablaCategoriasComerciales !== "undefined" && tablaCategoriasComerciales) {
        tablaCategoriasComerciales.ajax.reload(null, false);
    }
}

function valorOVacio(valor) {
    if (valor === null || typeof valor === "undefined" || valor === "") {
        return "";
    }
    return valor;
}

var coloresCategoriaPorCodigo = {
    DIST: "#dd4b39",
    MAYO: "#00a65a",
    MINO: "#f39c12",
    CATA: "#00c0ef",
    UFIN: "#605ca8"
};

function colorDefaultCategoria(codigo) {
    var key = String(codigo || "").toUpperCase().trim();
    return coloresCategoriaPorCodigo[key] || "#777777";
}

function normalizarColorHex(color, codigo) {
    var hex = String(color || "").trim();
    if (/^#[0-9A-Fa-f]{3,8}$/.test(hex)) {
        return hex.length === 4
            ? "#" + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]
            : hex.substring(0, 7);
    }
    return colorDefaultCategoria(codigo);
}

$("#nuevoCodigoCategoria").on("blur", function () {
    var codigo = $(this).val();
    var actual = $("#nuevoColorCategoria").val();
    if (!actual || actual === "#3c8dbc" || actual === "#777777") {
        $("#nuevoColorCategoria").val(colorDefaultCategoria(codigo));
    }
});

$("#formAgregarCategoriaComercial").on("submit", function (e) {
    e.preventDefault();

    var datos = new FormData();
    datos.append("accion", "crear");
    datos.append("codigo", $("#nuevoCodigoCategoria").val());
    datos.append("nombre", $("#nuevoNombreCategoria").val());
    datos.append("descripcion", $("#nuevaDescripcionCategoria").val());
    datos.append("orden", $("#nuevoOrdenCategoria").val());
    datos.append("color", $("#nuevoColorCategoria").val());
    datos.append("estado", $("#nuevoEstadoCategoria").val());
    datos.append("monto_compras_anual", $("#nuevoMontoAnualCategoria").val());
    datos.append("descuento_venta_pct", $("#nuevoDtoVentaCategoria").val());
    datos.append("descuento_pronto_pago_pct", $("#nuevoDtoProntoCategoria").val());

    $("#btnGuardarNuevaCategoria").prop("disabled", true);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#btnGuardarNuevaCategoria").prop("disabled", false);

            if (respuesta && respuesta.ok) {
                $("#modalAgregarCategoriaComercial").modal("hide");
                $("#formAgregarCategoriaComercial")[0].reset();
                $("#nuevoEstadoCategoria").val("1");
                recargarTablaCategoriasComerciales();
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
                    text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "Error desconocido"
                });
            }
        },
        error: function () {
            $("#btnGuardarNuevaCategoria").prop("disabled", false);
            swal({ type: "error", title: "Error", text: "No se pudo comunicar con el servidor" });
        }
    });
});

$(".tablaCategoriasComerciales").on("click", ".btnEditarCategoriaComercial", function () {
    var idCategoria = $(this).attr("idCategoria");
    var datos = new FormData();
    datos.append("accion", "detalle");
    datos.append("idCategoria", idCategoria);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (!respuesta || !respuesta.ok) {
                swal({
                    type: "error",
                    title: "Error",
                    text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "No se pudo cargar la categoría"
                });
                return;
            }

            var cat = respuesta.data.categoria;
            var req = respuesta.data.requisito;
            var ben = respuesta.data.beneficio;

            $("#idCategoriaComercial").val(cat.id);
            $("#editarCodigoCategoria").val(cat.codigo);
            $("#editarNombreCategoria").val(cat.nombre);
            $("#editarDescripcionCategoria").val(cat.descripcion || "");
            $("#editarOrdenCategoria").val(cat.orden);
            $("#editarEstadoCategoria").val(String(cat.estado));
            $("#editarColorCategoria").val(normalizarColorHex(cat.color, cat.codigo));

            $("#editarMontoAnualCategoria").val(req ? valorOVacio(req.valor_numerico) : "");
            $("#editarDtoVentaCategoria").val(ben ? valorOVacio(ben.descuento_venta_pct) : "");
            $("#editarDtoProntoCategoria").val(ben ? valorOVacio(ben.descuento_pronto_pago_pct) : "");
        },
        error: function () {
            swal({ type: "error", title: "Error", text: "No se pudo comunicar con el servidor" });
        }
    });
});

$("#formEditarCategoriaComercial").on("submit", function (e) {
    e.preventDefault();

    var datos = new FormData();
    datos.append("accion", "editar");
    datos.append("id", $("#idCategoriaComercial").val());
    datos.append("nombre", $("#editarNombreCategoria").val());
    datos.append("descripcion", $("#editarDescripcionCategoria").val());
    datos.append("orden", $("#editarOrdenCategoria").val());
    datos.append("color", $("#editarColorCategoria").val());
    datos.append("estado", $("#editarEstadoCategoria").val());
    datos.append("monto_compras_anual", $("#editarMontoAnualCategoria").val());
    datos.append("descuento_venta_pct", $("#editarDtoVentaCategoria").val());
    datos.append("descuento_pronto_pago_pct", $("#editarDtoProntoCategoria").val());

    $("#btnGuardarEditarCategoria").prop("disabled", true);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#btnGuardarEditarCategoria").prop("disabled", false);

            if (respuesta && respuesta.ok) {
                $("#modalEditarCategoriaComercial").modal("hide");
                recargarTablaCategoriasComerciales();
                swal({
                    type: "success",
                    title: "Actualizado",
                    text: respuesta.mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            } else {
                swal({
                    type: "error",
                    title: "No se pudo actualizar",
                    text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "Error desconocido"
                });
            }
        },
        error: function () {
            $("#btnGuardarEditarCategoria").prop("disabled", false);
            swal({ type: "error", title: "Error", text: "No se pudo comunicar con el servidor" });
        }
    });
});

$(".tablaCategoriasComerciales").on("click", ".btnToggleEstadoCategoria", function () {
    var idCategoria = $(this).attr("idCategoria");
    var nuevoEstado = $(this).attr("nuevoEstado");
    var texto = String(nuevoEstado) === "1" ? "activar" : "desactivar";

    swal({
        title: "¿" + texto.charAt(0).toUpperCase() + texto.slice(1) + " categoría?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, " + texto
    }).then(function (result) {
        if (!result.value) {
            return;
        }

        var datos = new FormData();
        datos.append("accion", "cambiarEstado");
        datos.append("idCategoria", idCategoria);
        datos.append("estado", nuevoEstado);

        $.ajax({
            url: "ajax/categorias-clientes.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta && respuesta.ok) {
                    recargarTablaCategoriasComerciales();
                    swal({
                        type: "success",
                        title: "Listo",
                        text: respuesta.mensaje,
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    });
                } else {
                    swal({
                        type: "error",
                        title: "Error",
                        text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "No se pudo cambiar el estado"
                    });
                }
            },
            error: function () {
                swal({ type: "error", title: "Error", text: "No se pudo comunicar con el servidor" });
            }
        });
    });
});
