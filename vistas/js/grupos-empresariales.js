if ($(".tablaGruposEmpresariales").length) {

$(".tablaGruposEmpresariales").DataTable({
    ajax: "ajax/facturacion/tabla-grupos-empresariales.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[1, "asc"]],
    pageLength: 20,
    lengthMenu: [[20, 40, 60, -1], [20, 40, 60, "Todos"]],
    columnDefs: [
        { targets: [4, 5, 6], className: "text-right" },
        { targets: -1, orderable: false, searchable: false }
    ],
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

function escapeHtmlGrupo(texto) {
    return String(texto == null ? "" : texto)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function textoOpcionCliente(cliente) {
    var documento = cliente.documento || "sin doc";
    return cliente.codigo + " - " + cliente.nombre + " (" + documento + ")";
}

function actualizarContadorMiembros(total) {
    $("#contadorMiembrosGrupo").text(total);
    $("#contadorAfectadosCategoriaGrupo").text(total);
}

function actualizarContadorGrupoEnTabla(codigoGrupo, total) {
    var tabla = $(".tablaGruposEmpresariales").DataTable();

    tabla.rows().every(function () {
        var data = this.data();
        if (data[0] === codigoGrupo) {
            data[2] = String(total);
            this.data(data);
        }
    });
}

function actualizarCategoriaGrupoEnTabla(codigoGrupo, nombreCategoria, codigoCategoria, colorCategoria) {
    var tabla = $(".tablaGruposEmpresariales").DataTable();
    var html;
    var hex;
    var mapa = {
        DIST: "#dd4b39",
        MAYO: "#00a65a",
        MINO: "#f39c12",
        CATA: "#00c0ef",
        UFIN: "#605ca8"
    };

    if (!nombreCategoria || nombreCategoria === "Sin categoría / pendiente") {
        html = "<span class='label label-default'>Sin categoría</span>";
    } else {
        hex = String(colorCategoria || "").trim();
        if (!/^#[0-9A-Fa-f]{3,8}$/.test(hex)) {
            hex = mapa[String(codigoCategoria || "").toUpperCase()] || "#777777";
        }
        html = "<span class='label' style='background-color:" + hex + ";'>" +
            escapeHtmlGrupo(nombreCategoria) + "</span>";
    }

    tabla.rows().every(function () {
        var data = this.data();
        if (data[0] === codigoGrupo) {
            data[3] = html;
            this.data(data);
        }
    });
}

function refrescarSelectCategoriaGrupo() {
    var select = $("#categoriaComercialGrupo");
    if (!select.length || typeof select.selectpicker !== "function") {
        return;
    }
    try {
        if (select.data("selectpicker")) {
            select.selectpicker("refresh");
        } else {
            select.selectpicker({
                liveSearch: true,
                size: 8
            });
        }
    } catch (e) {}
}

function setCategoriaGrupoUI(categoria) {
    var select = $("#categoriaComercialGrupo");

    if (categoria && categoria.tiene_categoria && categoria.categoria) {
        select.val(String(categoria.categoria.id));
    } else {
        select.val("");
    }

    refrescarSelectCategoriaGrupo();
}

function listarNombresMiembrosGrupo() {
    var nombres = [];
    $("#tablaClientesGrupo tbody tr").each(function () {
        if ($(this).hasClass("fila-vacia-grupo")) {
            return;
        }
        var nombre = $(this).find("td").eq(1).text();
        if (nombre) {
            nombres.push(nombre);
        }
    });
    return nombres;
}

function filaClienteGrupo(cliente) {
    return (
        "<tr data-codigo='" + escapeHtmlGrupo(cliente.codigo) + "'>" +
        "<td>" + escapeHtmlGrupo(cliente.codigo) + "</td>" +
        "<td>" + escapeHtmlGrupo(cliente.nombre) + "</td>" +
        "<td>" + escapeHtmlGrupo(cliente.documento || "") + "</td>" +
        "<td>" + escapeHtmlGrupo(cliente.telefono || "") + "</td>" +
        "<td><button type='button' class='btn btn-xs btn-danger btnQuitarClienteGrupo' codigoCliente='" + escapeHtmlGrupo(cliente.codigo) + "'><i class='fa fa-times'></i></button></td>" +
        "</tr>"
    );
}

function renderTablaClientesGrupo(clientes) {
    var tbody = $("#tablaClientesGrupo tbody");
    tbody.empty();

    if (!clientes || clientes.length === 0) {
        tbody.append("<tr class='fila-vacia-grupo'><td colspan='5' class='text-center text-muted'>No hay clientes en este grupo</td></tr>");
        return;
    }

    clientes.forEach(function (cliente) {
        tbody.append(filaClienteGrupo(cliente));
    });
}

function agregarFilaClienteGrupo(cliente) {
    var tbody = $("#tablaClientesGrupo tbody");
    tbody.find("tr.fila-vacia-grupo").remove();
    tbody.prepend(filaClienteGrupo(cliente));
}

function quitarFilaClienteGrupo(codigoCliente) {
    var tbody = $("#tablaClientesGrupo tbody");
    tbody.find("tr[data-codigo='" + codigoCliente + "']").remove();

    if (tbody.find("tr").length === 0) {
        tbody.append("<tr class='fila-vacia-grupo'><td colspan='5' class='text-center text-muted'>No hay clientes en este grupo</td></tr>");
    }
}

function refrescarSelectClienteAsignar() {
    var select = $("#selectClienteAsignar");

    if (!select.length || typeof select.selectpicker !== "function") {
        return;
    }

    try {
        if (select.data("selectpicker")) {
            select.selectpicker("refresh");
        } else {
            select.selectpicker({
                liveSearch: true,
                size: 8,
                container: "body",
                noneResultsText: "Sin coincidencias",
                title: "Buscar por código, nombre o documento..."
            });
        }
    } catch (e) {
        // Evitar que un fallo del plugin bloquee la carga AJAX
    }
}

function setEstadoSelectClientes(estado, mensajeAyuda) {
    try {
        var select = $("#selectClienteAsignar");
        var btn = $("#btnAsignarClienteGrupo");
        var ayuda = $("#ayudaSelectClienteAsignar");

        if (mensajeAyuda) {
            ayuda.text(mensajeAyuda);
        }

        if (estado === "cargando") {
            select.prop("disabled", true);
            btn.prop("disabled", true);
            select.html("<option value=''>Cargando clientes...</option>");
            refrescarSelectClienteAsignar();
            return;
        }

        if (estado === "vacio") {
            select.prop("disabled", true);
            btn.prop("disabled", true);
            select.html("<option value=''>No hay clientes disponibles para asignar</option>");
            refrescarSelectClienteAsignar();
            ayuda.text("No hay clientes disponibles para asignar.");
            return;
        }

        select.prop("disabled", false);
        btn.prop("disabled", false);
        ayuda.text(mensajeAyuda || "Busque por código, razón social o documento.");
        refrescarSelectClienteAsignar();
    } catch (e) {
        // No bloquear la carga de clientes si falla el plugin visual
    }
}

function cargarOpcionesClientesDisponibles(disponibles) {
    var select = $("#selectClienteAsignar");
    var html = "<option value=''></option>";

    if (!disponibles || disponibles.length === 0) {
        setEstadoSelectClientes("vacio");
        return;
    }

    disponibles.forEach(function (cliente) {
        var tokens = [cliente.codigo, cliente.nombre, cliente.documento || ""].join(" ");
        html +=
            "<option value='" + escapeHtmlGrupo(cliente.codigo) + "' data-tokens='" + escapeHtmlGrupo(tokens) + "'>" +
            escapeHtmlGrupo(textoOpcionCliente(cliente)) +
            "</option>";
    });

    select.html(html);
    select.val("");
    setEstadoSelectClientes("listo");
}

function quitarOpcionClienteSelect(codigoCliente) {
    var select = $("#selectClienteAsignar");
    select.find("option[value='" + codigoCliente.replace(/'/g, "\\'") + "']").remove();

    if (select.find("option").filter(function () {
        return $(this).val() !== "";
    }).length === 0) {
        setEstadoSelectClientes("vacio");
        return;
    }

    select.val("");
    refrescarSelectClienteAsignar();
}

function agregarOpcionClienteSelect(cliente) {
    var select = $("#selectClienteAsignar");
    var codigo = String(cliente.codigo);

    if (select.find("option").filter(function () {
        return $(this).val() === codigo;
    }).length) {
        return;
    }

    if (select.prop("disabled")) {
        select.html("<option value=''></option>");
    }

    var tokens = [cliente.codigo, cliente.nombre, cliente.documento || ""].join(" ");
    select.append(
        "<option value='" + escapeHtmlGrupo(cliente.codigo) + "' data-tokens='" + escapeHtmlGrupo(tokens) + "'>" +
        escapeHtmlGrupo(textoOpcionCliente(cliente)) +
        "</option>"
    );

    select.val("");
    setEstadoSelectClientes("listo");
}

function enfocarSelectClienteAsignar() {
    var select = $("#selectClienteAsignar");
    if (select.prop("disabled") || typeof select.selectpicker !== "function") {
        return;
    }

    setTimeout(function () {
        try {
            select.selectpicker("toggle");
        } catch (e) {}
    }, 150);
}

function setBotonAsignarCargando(cargando) {
    var btn = $("#btnAsignarClienteGrupo");

    if (cargando) {
        btn.data("html-original", btn.html());
        btn.prop("disabled", true).html("<i class='fa fa-spinner fa-spin'></i> Agregando...");
        return;
    }

    btn.prop("disabled", false).html(btn.data("html-original") || "<i class='fa fa-plus'></i> Agregar al grupo");
}

function cargarClientesGrupo(codigoGrupo) {

    actualizarContadorMiembros(0);
    $("#tablaClientesGrupo tbody").html(
        "<tr><td colspan='5' class='text-center text-muted'><i class='fa fa-spinner fa-spin'></i> Cargando...</td></tr>"
    );
    setEstadoSelectClientes("cargando", "Cargando clientes...");

    var datos = new FormData();
    datos.append("codigoGrupo", codigoGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            renderTablaClientesGrupo(respuesta.clientes || []);
            cargarOpcionesClientesDisponibles(respuesta.disponibles || []);
            actualizarContadorMiembros(respuesta.total_miembros || 0);
            setCategoriaGrupoUI(respuesta.categoria || null);
            refrescarSelectCategoriaGrupo();
        },
        error: function (xhr) {
            setEstadoSelectClientes("vacio", "No se pudieron cargar los clientes.");
            $("#tablaClientesGrupo tbody").html(
                "<tr><td colspan='5' class='text-center text-danger'>Error al cargar clientes del grupo</td></tr>"
            );
            swal({
                type: "error",
                title: "No se pudieron cargar los clientes",
                text: xhr && xhr.status ? ("HTTP " + xhr.status) : "",
                showConfirmButton: true
            });
        }
    });
}

$(".tablaGruposEmpresariales").on("click", ".btnEditarGrupo", function () {

    var idGrupo = $(this).attr("idGrupo");
    var datos = new FormData();
    datos.append("idGrupo", idGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#idGrupo").val(respuesta.id);
            $("#editarCodigoGrupo").val(respuesta.codigo);
            $("#editarNombreGrupo").val(respuesta.nombre);
            $("#editarDescripcionGrupo").val(respuesta.descripcion || "");
            $("#editarIdZonaGrupo").val(respuesta.id_zona ? String(respuesta.id_zona) : "");
            $("#editarEstadoGrupo").val(respuesta.estado);
        }
    });
});

$(".tablaGruposEmpresariales").on("click", ".btnEliminarGrupo", function () {

    var idGrupo = $(this).attr("idGrupo");

    swal({
        title: "¿Está seguro de borrar el grupo?",
        text: "Solo se puede eliminar si no tiene clientes asignados.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, borrar grupo"
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=grupos-empresariales&idGrupo=" + idGrupo;
        }
    });
});

$(".tablaGruposEmpresariales").on("click", ".btnVerClientesGrupo", function () {

    var codigoGrupo = $(this).attr("codigoGrupo");
    var nombreGrupo = $(this).attr("nombreGrupo");

    $("#codigoGrupoActivo").val(codigoGrupo);
    $("#tituloGrupoClientes").text(nombreGrupo);
    cargarClientesGrupo(codigoGrupo);
});

$("#modalClientesGrupo").on("shown.bs.modal", function () {
    refrescarSelectClienteAsignar();
    refrescarSelectCategoriaGrupo();

    // Asegurar que el menú del select quede por encima del modal
    $(".bootstrap-select .dropdown-menu").css("z-index", 2060);
});

$("#modalClientesGrupo").on("hidden.bs.modal", function () {
    var select = $("#selectClienteAsignar");
    select.val("");
    refrescarSelectClienteAsignar();
});

// bootstrap-select con container=body puede quedar detrás del modal
$(document).on("shown.bs.select", "#selectClienteAsignar", function () {
    $(".bootstrap-select.open .dropdown-menu, .bootstrap-select .dropdown-menu").css("z-index", 2060);
});

$("#btnAsignarClienteGrupo").on("click", function () {

    var codigoCliente = $("#selectClienteAsignar").val();
    var codigoGrupo = $("#codigoGrupoActivo").val();

    if (!codigoCliente) {
        swal({ type: "warning", title: "Seleccione un cliente", showConfirmButton: true });
        return;
    }

    setBotonAsignarCargando(true);

    var datos = new FormData();
    datos.append("accion", "asignar");
    datos.append("codigoClienteGrupo", codigoCliente);
    datos.append("codigoGrupoAsignar", codigoGrupo);

    $.ajax({
        url: "ajax/grupos-empresariales.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            setBotonAsignarCargando(false);

            if (respuesta.status === "ok") {
                agregarFilaClienteGrupo(respuesta.cliente);
                quitarOpcionClienteSelect(respuesta.cliente.codigo);
                actualizarContadorMiembros(respuesta.total_miembros);
                actualizarContadorGrupoEnTabla(codigoGrupo, respuesta.total_miembros);
                enfocarSelectClienteAsignar();
                if (respuesta.mensaje) {
                    toastr["info"](respuesta.mensaje);
                }
            } else {
                swal({
                    type: "error",
                    title: respuesta.mensaje || "No se pudo asignar el cliente",
                    showConfirmButton: true
                });
            }
        },
        error: function () {
            setBotonAsignarCargando(false);
            swal({ type: "error", title: "Error de comunicación al asignar", showConfirmButton: true });
        }
    });
});

$("#tablaClientesGrupo").on("click", ".btnQuitarClienteGrupo", function () {

    var boton = $(this);
    var codigoCliente = boton.attr("codigoCliente");
    var codigoGrupo = $("#codigoGrupoActivo").val();

    swal({
        title: "¿Quitar cliente del grupo?",
        text: "Quedará sin categoría comercial hasta que se le asigne una individual.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, quitar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) return;

        boton.prop("disabled", true).html("<i class='fa fa-spinner fa-spin'></i>");

        var datos = new FormData();
        datos.append("accion", "quitar");
        datos.append("codigoClienteQuitar", codigoCliente);
        datos.append("codigoGrupoQuitar", codigoGrupo);

        $.ajax({
            url: "ajax/grupos-empresariales.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.status === "ok") {
                    quitarFilaClienteGrupo(codigoCliente);
                    agregarOpcionClienteSelect(respuesta.cliente);
                    actualizarContadorMiembros(respuesta.total_miembros);
                    actualizarContadorGrupoEnTabla(codigoGrupo, respuesta.total_miembros);
                    if (respuesta.mensaje) {
                        toastr["info"](respuesta.mensaje);
                    }
                } else {
                    boton.prop("disabled", false).html("<i class='fa fa-times'></i>");
                    swal({
                        type: "error",
                        title: respuesta.mensaje || "No se pudo quitar el cliente",
                        showConfirmButton: true
                    });
                }
            },
            error: function () {
                boton.prop("disabled", false).html("<i class='fa fa-times'></i>");
                swal({ type: "error", title: "Error de comunicación al quitar", showConfirmButton: true });
            }
        });
    });
});

$("#btnAplicarCategoriaGrupo").on("click", function () {
    var codigoGrupo = $("#codigoGrupoActivo").val();
    var idCategoria = $("#categoriaComercialGrupo").val() || "";
    var total = parseInt($("#contadorMiembrosGrupo").text(), 10) || 0;
    var textoCat = idCategoria
        ? $("#categoriaComercialGrupo option:selected").text()
        : "Sin categoría / pendiente";
    var nombres = listarNombresMiembrosGrupo();
    var previewMiembros = "";

    if (nombres.length > 0) {
        var mostrados = nombres.slice(0, 8);
        previewMiembros = "Miembros: " + mostrados.join(", ");
        if (nombres.length > 8) {
            previewMiembros += " y " + (nombres.length - 8) + " más";
        }
    } else {
        previewMiembros = "El grupo no tiene miembros aún; la categoría quedará lista para futuros integrantes.";
    }

    swal({
        title: "¿Aplicar categoría al grupo?",
        text: textoCat + " — afecta a " + total + " miembro(s). " + previewMiembros,
        type: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, aplicar",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.value) {
            return;
        }

        var btn = $("#btnAplicarCategoriaGrupo");
        btn.prop("disabled", true);

        var datos = new FormData();
        datos.append("accion", "asignarGrupo");
        datos.append("codigoGrupo", codigoGrupo);
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
                btn.prop("disabled", false);

                if (respuesta && respuesta.ok) {
                    var nombreFinal = (respuesta.categoria && respuesta.categoria.nombre)
                        ? respuesta.categoria.nombre
                        : "Sin categoría / pendiente";
                    var codigoFinal = (respuesta.categoria && respuesta.categoria.codigo)
                        ? respuesta.categoria.codigo
                        : "";
                    var colorFinal = (respuesta.categoria && respuesta.categoria.color)
                        ? respuesta.categoria.color
                        : "";
                    actualizarCategoriaGrupoEnTabla(codigoGrupo, nombreFinal, codigoFinal, colorFinal);
                    $(".tablaGruposEmpresariales").DataTable().ajax.reload(null, false);
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
                        title: "No se pudo aplicar",
                        text: (respuesta && respuesta.mensaje) ? respuesta.mensaje : "Error desconocido",
                        showConfirmButton: true
                    });
                }
            },
            error: function () {
                btn.prop("disabled", false);
                swal({ type: "error", title: "Error de comunicación", showConfirmButton: true });
            }
        });
    });
});
