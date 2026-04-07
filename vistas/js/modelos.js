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

// ELIMINAR OPERACIÓN
$(".tablaModelos tbody").on("click", "button.btnGenerarArticulo", function () {
    var modelo = $(this).attr("modelo");
    window.location = "index.php?ruta=crear-articulo&modelo=" + modelo;
});

//AGREGAR COLOR X ARTICULO
$(".tablaArticuloColores tbody").on(
    "click",
    "button.agregarColor",
    function () {
        var idColor = $(this).attr("idColor");

        $(this).removeClass("btn-primary agregarColor");

        $(this).addClass("btn-default");

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

                $(".nuevoColor").append(
                    '<div class="row" style="padding:5px 15px">' +
                        "<!-- Descripción del color -->" +
                        '<div class="col-xs-12" style="padding-right:0px">' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarColor" idColor="' +
                        idColor +
                        '"><i class="fa fa-times"></i></button></span>' +
                        '<input type="text" class="form-control nuevaDescripcionColor" idColor="' +
                        idColor +
                        '" name="agregarColor" value="' +
                        codigos +
                        " - " +
                        nombres +
                        '"  codigoCO= "' +
                        codigos +
                        '" descripcion= "' +
                        nombres +
                        '" readonly>' +
                        "</div>" +
                        "</div>" +
                        "</div>"
                );
                listarColores();
            },
        });
    }
);

/*=============================================
CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
=============================================*/

$(".tablaArticuloColores").on("draw.dt", function () {
    //console.log("tabla");

    if (localStorage.getItem("quitarColor") != null) {
        var listaidColores = JSON.parse(localStorage.getItem("quitarColor"));

        for (var i = 0; i < listaidColores.length; i++) {
            $(
                "button.recuperarBoton[idColor='" +
                    listaidColores[i]["idColor"] +
                    "']"
            ).removeClass("btn-default");
            $(
                "button.recuperarBoton[idColor='" +
                    listaidColores[i]["idColor"] +
                    "']"
            ).addClass("btn-primary agregarColor");
        }
    }
});

/*=============================================
  QUITAR OPERACIONES POR ARTICULO Y RECUPERAR BOTÓN
  =============================================*/

var idQuitarColor = [];

localStorage.removeItem("quitarColor");

$(".formularioArticulo").on("click", "button.quitarColor", function () {
    $(this).parent().parent().parent().parent().remove();

    var idColor = $(this).attr("idColor");

    /*=============================================
	ALMACENAR EN EL LOCALSTORAGE EL ID DE OPERACION A QUITAR
	=============================================*/

    if (localStorage.getItem("quitarColor") == null) {
        idQuitarColor = [];
    } else {
        idQuitarColor.concat(localStorage.getItem("quitarColor"));
    }

    idQuitarColor.push({
        idColor: idColor,
    });

    localStorage.setItem("quitarColor", JSON.stringify(idQuitarColor));

    $("button.recuperarBoton[idColor='" + idColor + "']").removeClass(
        "btn-default"
    );

    $("button.recuperarBoton[idColor='" + idColor + "']").addClass(
        "btn-primary agregarColor"
    );

    listarColores();
});

/*=============================================
FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
=============================================*/

function quitarAgregarColores() {
    //Capturamos todos los id de productos que fueron elegidos en la venta
    var idColores = $(".quitarColor");

    //Capturamos todos los botones de agregar que aparecen en la tabla
    var botonesTabla = $(".tablaArticuloColores tbody button.agregarColor");

    //Recorremos en un ciclo para obtener los diferentes idProductos que fueron agregados a la venta
    for (var i = 0; i < idColores.length; i++) {
        //Capturamos los Id de los productos agregados a la venta
        var boton = $(idColores[i]).attr("idColor");
        //   console.log(boton);

        //Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
        for (var j = 0; j < botonesTabla.length; j++) {
            if ($(botonesTabla[j]).attr("idColor") == boton) {
                $(botonesTabla[j]).removeClass("btn-primary agregarColor");
                $(botonesTabla[j]).addClass("btn-default");
            }
        }
    }
}

$(".tablaArticuloColores").on("draw.dt", function () {
    quitarAgregarColores();
});

/*=============================================
LISTAR TODOS LOS COLORES
=============================================*/

function listarColores() {
    var listaColores = [];

    var descripcion = $(".nuevaDescripcionColor");

    for (var i = 0; i < descripcion.length; i++) {
        listaColores.push({
            id: $(descripcion[i]).attr("idColor"),
            codigo: $(descripcion[i]).attr("codigoCO"),
            descripcion: $(descripcion[i]).attr("descripcion"),
        });
    }
    $("#listaColores").val(JSON.stringify(listaColores));
    console.log(listaColores);
}

$("#nuevoGrupoTalla").change(function () {
    var grupo = $(this).val();
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
            $(".detalle").remove();
            for (var i = 0; i < respuesta.length; i += 1) {
                $(".nuevaTalla").append(
                    '<ul class="detalle" style="display:inline"><input type="checkbox" name = "chk[]" tallas="' +
                        respuesta[i]["talla"] +
                        '" value="' +
                        respuesta[i]["cod_talla"] +
                        '"><label  for="' +
                        respuesta[i]["talla"] +
                        '">' +
                        respuesta[i]["talla"] +
                        ' </label> <span  style="padding:10px"></ul>'
                );
            }
        },
    });
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
