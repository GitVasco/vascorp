/*
 * tabla paraa cargar la lista de modelos
 */
$(".tablaModelos").DataTable({
    ajax:
        "ajax/maestros/tabla-modelos.ajax.php?perfil=" +
        $("#perfilOculto").val(),
    deferRender: true,
    retrieve: true,
    processing: true,
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

// ACTIVANDO-DESACTIVANDO ARTICULO
$(document).on("click", ".btnActivar", function () {
    // Capturamos el id del usuario y el estado
    var idModelo = $(this).attr("idModelo");
    var estadoModelo = $(this).attr("estadoModelo");
    /* 	console.log("idArticulo", idArticulo);
	console.log("estadoArticulo", estadoArticulo); */
    // Realizamos la activación-desactivación por una petición AJAX
    var datos = new FormData();
    datos.append("activarId", idModelo);
    datos.append("activarEstado", estadoModelo);
    $.ajax({
        url: "ajax/modelos.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            swal({
                type: "success",
                title: "¡Ok!",
                text: "¡La información fue actualizada con éxito!",
                showConfirmButton: true,
                confirmButtonText: "Cerrar",
                closeOnConfirm: false,
            }).then((result) => {
                if (result.value) {
                    window.location = "modelosjf";
                }
            });
        },
    });
    // Cambiamos el estado del botón físicamente
    if (estadoModelo == "Descontinuado") {
        $(this).removeClass("btn-success");
        $(this).addClass("btn-danger");
        $(this).html("Inactivo");
        $(this).attr("estadoModelo", "Activo");
    } else {
        $(this).addClass("btn-success");
        $(this).removeClass("btn-danger");
        $(this).html("Activo");
        $(this).attr("estadoModelo", "Descontinuado");
    }
});

/*=============================================
EDITAR ARTICULO
=============================================*/

$(".tablaModelos tbody").on("click", "button.btnEditarModelo", function () {
    var modelo = $(this).attr("modelo");

    var datos = new FormData();
    datos.append("modelo", modelo);

    $.ajax({
        url: "ajax/modelos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $("#editarMarca").val(respuesta["id_marca"]);
            $("#editarMarca").selectpicker("refresh");

            $("#editarModelo").val(respuesta["modelo"]);

            $("#editarDescripcion").val(respuesta["nombre"]);

            $("#editarTipo").val(respuesta["tipo"]);
            $("#editarTipo").html(respuesta["tipo"]);

            var codUnidad =
                respuesta["cod_unidad"] && String(respuesta["cod_unidad"]).trim() !== ""
                    ? String(respuesta["cod_unidad"]).trim()
                    : "C62";
            $("#editarUnidadMedida").val(codUnidad);

            if (respuesta["imagen"] != "") {
                $("#imagenActual").val(respuesta["imagen"]);

                $(".previsualizar").attr("src", respuesta["imagen"]);
            }
        },
    });
});

/*=============================================
ELIMINAR MODELO A DESCONTINUADO
=============================================*/

$(".tablaModelos tbody").on("click", "button.btnEliminarModelo", function () {
    var idModelo = $(this).attr("idModelo");
    var modelo = $(this).attr("modelo");
    var imagen = $(this).attr("imagen");

    /* console.log("idArticulo", idArticulo); */

    swal({
        title: "¿Está seguro de eliminar el modelo?",
        text: "¡Si no lo está puede cancelar la accíón!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, eliminar modelo!",
    }).then(function (result) {
        if (result.value) {
            window.location =
                "index.php?ruta=modelosjf&idModelo=" +
                idModelo +
                "&imagen=" +
                imagen +
                "&modelo=" +
                modelo;
        }
    });
});

/*=============================================
VER MODELO
=============================================*/

$(".tablaModelos tbody").on("click", "button.btnVerModelo", function () {
    var modelo2 = $(this).attr("modelo");

    var datos = new FormData();
    datos.append("modelo2", modelo2);

    $.ajax({
        url: "ajax/modelos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $(".detalleMO").remove();

            for (var id of respuesta) {
                $(".tablaDetalleModelo").append(
                    '<tr class="detalleMO">' +
                        '<td class="text-center">' +
                        id.modelo +
                        " </td>" +
                        '<td class="text-center">' +
                        id.nombre +
                        " </td>" +
                        '<td class="text-center">' +
                        id.color +
                        " </td>" +
                        '<td class="text-center">' +
                        id.talla +
                        " </td>" +
                        "</tr>"
                );
            }
        },
    });
    //VER MODELO
    var modelo = $(this).attr("modelo");
    var datos2 = new FormData();
    datos2.append("modelo", modelo);
    $.ajax({
        url: "ajax/modelos.ajax.php",
        method: "POST",
        data: datos2,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $(".titulo").html(respuesta["nombre"]);
            if (respuesta["imagen"] != "") {
                $("#imagenActual").val(respuesta["imagen"]);

                $(".previsualizar").attr("src", respuesta["imagen"]);
            } else {
                $(".previsualizar").attr(
                    "src",
                    "vistas/img/modelos/default/anonymous.png"
                );
            }
        },
    });
});

/*
 * BOTON REPORTE DE OPERACIONES X MODELO
 */
$(".tablaModelos").on("click", ".btnReporteOM", function () {
    var codigo = $(this).attr("codigo");

    window.location =
        "vistas/reportes_excel/rpt_operacionesmodelo.php?codigo=" + codigo;
});

$(".tablaModelos tbody").on("click", "button.btnGenerarArticulo", function () {
    var modelo = $(this).attr("modelo");
    window.location = "index.php?ruta=crear-articulo&modelo=" + modelo;
});

/*=============================================
  AMPLIAR MODELO: colores / tallas
=============================================*/

function caEscaparAttr(valor) {
    return String(valor == null ? "" : valor)
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

function caEscaparHtml(valor) {
    return String(valor == null ? "" : valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function caParseJson($el) {
    try {
        var raw = $el.val();
        if (!raw) {
            return [];
        }
        var data = JSON.parse(raw);
        return Array.isArray(data) ? data : [];
    } catch (e) {
        return [];
    }
}

function caCodigosExistentes(lista, campo) {
    var map = {};
    for (var i = 0; i < lista.length; i++) {
        map[String(lista[i][campo])] = lista[i];
    }
    return map;
}

function caColoresNuevosSeleccionados() {
    var lista = [];
    $(".nuevaDescripcionColor").each(function () {
        lista.push({
            id: $(this).attr("idColor"),
            codigo: $(this).attr("codigoCO"),
            descripcion: $(this).attr("descripcion"),
        });
    });
    return lista;
}

function caTallasSeleccionadas() {
    var lista = [];
    $("#caTallasGrupo .ca-talla-check:checked").each(function () {
        lista.push({
            cod_talla: $(this).val(),
            talla: $(this).attr("tallas"),
            existente: $(this).attr("data-existente") === "1",
        });
    });
    return lista;
}

function caConstruirVariantes() {
    var coloresExistentes = caParseJson($("#caColoresExistentes"));
    var coloresNuevos = caColoresNuevosSeleccionados();
    var tallas = caTallasSeleccionadas();
    var tallasNuevas = tallas.filter(function (t) {
        return !t.existente;
    });
    var variantes = [];
    var seen = {};

    function pushPar(color, talla) {
        var key = String(color.codigo) + "|" + String(talla.cod_talla);
        if (seen[key]) {
            return;
        }
        seen[key] = true;
        variantes.push({
            codigo: color.codigo,
            descripcion: color.descripcion || color.color,
            cod_talla: talla.cod_talla,
            talla: talla.talla,
        });
    }

    // Color nuevo × todas las tallas marcadas (existentes + nuevas)
    for (var i = 0; i < coloresNuevos.length; i++) {
        for (var j = 0; j < tallas.length; j++) {
            pushPar(coloresNuevos[i], tallas[j]);
        }
    }

    // Color existente × solo tallas nuevas
    for (var k = 0; k < coloresExistentes.length; k++) {
        var colorExistente = {
            codigo: coloresExistentes[k].cod_color,
            descripcion: coloresExistentes[k].color,
        };
        for (var m = 0; m < tallasNuevas.length; m++) {
            pushPar(colorExistente, tallasNuevas[m]);
        }
    }

    return variantes;
}

function caActualizarPreview() {
    if (!$("#formularioAmpliarModelo").length) {
        return;
    }

    var coloresNuevos = caColoresNuevosSeleccionados();
    var tallas = caTallasSeleccionadas();
    var tallasNuevas = tallas.filter(function (t) {
        return !t.existente;
    });
    var variantes = caConstruirVariantes();
    var $preview = $("#caPreview");
    var $btn = $("#btnGuardarVariantes");
    var texto;

    $("#listaColores").val(JSON.stringify(coloresNuevos));
    $("#listaVariantes").val(JSON.stringify(variantes));

    if (!$("#nuevoGrupoTalla").val()) {
        texto = "Elige un grupo de tallas.";
        $preview.removeClass("is-ready");
        $btn.prop("disabled", true);
    } else if (variantes.length === 0) {
        if (coloresNuevos.length === 0 && tallasNuevas.length === 0) {
            texto = "Agrega un color nuevo o marca una talla nueva.";
        } else if (coloresNuevos.length > 0 && tallas.length === 0) {
            texto = "Marca al menos una talla para el color nuevo.";
        } else {
            texto = "No hay combinaciones nuevas por crear.";
        }
        $preview.removeClass("is-ready");
        $btn.prop("disabled", true);
    } else {
        var partes = [];
        if (coloresNuevos.length > 0) {
            partes.push(coloresNuevos.length + " color(es) nuevo(s)");
        }
        if (tallasNuevas.length > 0) {
            partes.push(tallasNuevas.length + " talla(s) nueva(s)");
        }
        texto =
            "Se crearán " +
            variantes.length +
            " artículo(s)" +
            (partes.length ? " · " + partes.join(" · ") : "") +
            ".";
        $preview.addClass("is-ready");
        $btn.prop("disabled", false);
    }

    $("#caPreviewTexto").text(texto);
}

function caMarcarBotonesCatalogo() {
    if (!$("#formularioAmpliarModelo").length) {
        return;
    }

    var existentes = caCodigosExistentes(
        caParseJson($("#caColoresExistentes")),
        "cod_color"
    );
    var seleccionados = {};
    $(".quitarColor").each(function () {
        seleccionados[String($(this).attr("idColor"))] = true;
    });

    $(".tablaArticuloColores tbody button.recuperarBoton").each(function () {
        var $btn = $(this);
        var id = String($btn.attr("idColor"));

        $btn
            .removeClass("btn-primary btn-default btn-success agregarColor btn-ya-tiene")
            .prop("disabled", false);

        if (existentes[id]) {
            $btn
                .addClass("btn-success btn-ya-tiene")
                .prop("disabled", true)
                .html('<i class="fa fa-check"></i> Ya tiene');
        } else if (seleccionados[id]) {
            $btn.addClass("btn-default").html('<i class="fa fa-check"></i> Agregado');
        } else {
            $btn
                .addClass("btn-primary agregarColor")
                .html('<i class="fa fa-plus-circle"></i> Agregar');
        }
    });
}

function listarColores() {
    caActualizarPreview();
}

function quitarAgregarColores() {
    caMarcarBotonesCatalogo();
}

$(".tablaArticuloColores tbody").on("click", "button.agregarColor", function () {
    var idColor = $(this).attr("idColor");
    var $btn = $(this);

    $btn.removeClass("btn-primary agregarColor").addClass("btn-default");

    var datos = new FormData();
    datos.append("idColores", idColor);

    $.ajax({
        url: "ajax/colores.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            var codigos = respuesta["cod_color"];
            var nombres = respuesta["nom_color"];

            $("#caColoresNuevos .ca-empty-colores").remove();

            $(".nuevoColor").append(
                '<span class="ca-chip ca-chip-nuevo">' +
                    '<input type="hidden" class="nuevaDescripcionColor" idColor="' +
                    caEscaparAttr(idColor) +
                    '" codigoCO="' +
                    caEscaparAttr(codigos) +
                    '" descripcion="' +
                    caEscaparAttr(nombres) +
                    '" value="' +
                    caEscaparAttr(codigos + " - " + nombres) +
                    '">' +
                    "<strong>" +
                    caEscaparHtml(codigos) +
                    "</strong> " +
                    caEscaparHtml(nombres) +
                    '<button type="button" class="btn-quitar quitarColor" idColor="' +
                    caEscaparAttr(idColor) +
                    '" title="Quitar"><i class="fa fa-times"></i></button>' +
                    "</span>"
            );
            listarColores();
            caMarcarBotonesCatalogo();
        },
    });
});

$(".tablaArticuloColores").on("draw.dt", function () {
    caMarcarBotonesCatalogo();
});

$(".formularioArticulo").on("click", "button.quitarColor", function () {
    $(this).closest(".ca-chip-nuevo").remove();

    if ($(".nuevaDescripcionColor").length === 0) {
        $("#caColoresNuevos").append(
            '<span class="ca-empty ca-empty-colores">Agrega colores desde el catálogo →</span>'
        );
    }

    listarColores();
    caMarcarBotonesCatalogo();
});

$("#nuevoGrupoTalla").change(function () {
    var grupo = $(this).val();
    var $wrap = $("#caTallasGrupo");

    if (!grupo) {
        $wrap.html('<span class="ca-empty">Elige un grupo de tallas</span>');
        caActualizarPreview();
        return;
    }

    var existentes = caCodigosExistentes(
        caParseJson($("#caTallasExistentes")),
        "cod_talla"
    );
    var datos = new FormData();
    datos.append("grupo", grupo);

    $.ajax({
        url: "ajax/tallas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $wrap.empty();

            if (!respuesta || !respuesta.length) {
                $wrap.html('<span class="ca-empty">Sin tallas en este grupo</span>');
                caActualizarPreview();
                return;
            }

            for (var i = 0; i < respuesta.length; i++) {
                var cod = String(respuesta[i]["cod_talla"]);
                var nom = respuesta[i]["talla"];
                var yaTiene = !!existentes[cod];
                var cls = yaTiene
                    ? "ca-talla-item is-existente"
                    : "ca-talla-item is-nueva";
                var checked = yaTiene ? "checked" : "";
                var disabled = yaTiene ? "disabled" : "";
                var badge = yaTiene
                    ? '<span class="ca-talla-badge">ya tiene</span>'
                    : "";
                // Checkbox deshabilitado no se envía: hidden para chk[] + data para JS
                var hidden =
                    yaTiene
                        ? '<input type="hidden" name="chk[]" value="' +
                          cod +
                          '">'
                        : "";

                $wrap.append(
                    '<label class="' +
                        cls +
                        '">' +
                        hidden +
                        '<input type="checkbox" class="ca-talla-check" name="' +
                        (yaTiene ? "" : "chk[]") +
                        '" value="' +
                        cod +
                        '" tallas="' +
                        nom +
                        '" data-existente="' +
                        (yaTiene ? "1" : "0") +
                        '" ' +
                        checked +
                        " " +
                        disabled +
                        ">" +
                        "<span>" +
                        nom +
                        "</span>" +
                        badge +
                        "</label>"
                );
            }

            caActualizarPreview();
        },
    });
});

$(document).on("change", "#caTallasGrupo .ca-talla-check", function () {
    var $label = $(this).closest(".ca-talla-item");
    if ($(this).is(":checked")) {
        $label.addClass("is-checked");
    } else {
        $label.removeClass("is-checked");
    }
    caActualizarPreview();
});

$("#formularioAmpliarModelo").on("submit", function (e) {
    var variantes = caConstruirVariantes();
    $("#listaVariantes").val(JSON.stringify(variantes));
    $("#listaColores").val(JSON.stringify(caColoresNuevosSeleccionados()));

    if (!variantes.length) {
        e.preventDefault();
        caActualizarPreview();
        return false;
    }
});

$(function () {
    if ($("#formularioAmpliarModelo").length) {
        caActualizarPreview();
        caMarcarBotonesCatalogo();
    }
});

$("#nuevaMarca").change(function () {
    var marca = $(this).val();

    if (marca == "1") {
        $("#nuevoTipo").html(
            "<option value=''>Seleccionar Tipo</option><option value='BRASIER'>BRASIER</option><option value='TRUSA'>TRUSA</option><option value='TOP'>TOP</option><option value='FAJA'>FAJA</option><option value='SEAMLESS'>SEAMLESS</option>"
        );
    } else if (marca == "2") {
        $("#nuevoTipo").html(
            "<option value=''>Seleccionar Tipo</option><option value='TRUSA'>TRUSA</option><option value='BOXER V'>BOXER V</option><option value='MEDIAS'>MEDIAS</option>"
        );
    } else if (marca == "3") {
        $("#nuevoTipo").html(
            "<option value=''>Seleccionar Tipo</option><option value='GUAPITAS'>GUAPITAS</option>"
        );
    } else if (marca == "4") {
        $("#nuevoTipo").html(
            "<option value=''>Seleccionar Tipo</option><option value='SK'>SK</option>"
        );
    } else if (marca == "9") {
        $("#nuevoTipo").html(
            "<option value=''>Seleccionar Tipo</option><option value='SESGO'>SESGO</option>"
        );
    }
});

//Ingresar readonly para precios
for (let index = 1; index <= 11; index++) {
    $(".tablaDetallePrecio").on("click", "a.editarPrecio" + index, function () {
        if ($("#precio" + index).attr("readonly")) {
            $("#precio" + index).attr("readonly", false);
        } else {
            $("#precio" + index).attr("readonly", true);
        }
    });
}

$(".tablaModelos tbody").on("click", "button.btnVerPrecio", function () {
    var modelo = $(this).attr("modelo");
    $("#modelo").val(modelo);
    var desc = $(this).attr("descripcion");
    $("#descModelo").val(desc);
    var datos = new FormData();
    datos.append("modelo", modelo);

    $.ajax({
        url: "ajax/precios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            $(".detallePR").remove();

            for (var i = 1; i <= 11; i++) {
                $(".tablaDetallePrecio").append(
                    '<tr class="detallePR">' +
                        '<td style="background-color:#3c8dbc;border-radius:50px;width:30px;text-align:center;color:white">' +
                        i +
                        " </td>" +
                        '<td style="width:120px"><input type="number" min="0" step ="any" class="form-control input-md" name="precio' +
                        i +
                        '" id="precio' +
                        i +
                        '" value="' +
                        respuesta["precio" + i + ""] +
                        '" readonly > </td><td class="text-center"><a type="button"class="btn btn-sm btn-primary editarPrecio' +
                        i +
                        '">Editar Precio</a></td>' +
                        "</tr>"
                );
            }
        },
    });
});

//Reporte de Modelos
$(".box").on("click", ".btnReporteModelos", function () {
    window.location = "vistas/reportes_excel/rpt_modelo.php";
});
