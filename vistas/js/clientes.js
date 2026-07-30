/*
 * CARGAR TABLA CLIENTES
 */
var tablaClientesDt = $(".tablaClientes").DataTable({
    ajax: {
        url: "ajax/facturacion/tabla-clientes.ajax.php",
        data: function (d) {
            d.perfil = $("#perfilOculto").val();
            d.categoria = $("#filtroCategoriaCliente").val() || "";
        },
    },
    deferRender: true,
    retrieve: true,
    processing: true,
    autoWidth: false,
    order: [[0, "asc"]],
    pageLength: 20,
    columnDefs: [
        {
            targets: -1,
            orderable: false,
            searchable: false,
            className: "text-center clientes-col-acciones",
            width: "120px",
        },
    ],
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

function refrescarFiltroCategoriaCliente() {
    var $select = $("#filtroCategoriaCliente");
    if (!$select.length) {
        return;
    }
    try {
        if ($select.data("selectpicker")) {
            $select.selectpicker("refresh");
        } else if (typeof $select.selectpicker === "function") {
            $select.selectpicker({
                liveSearch: true,
                size: 8,
            });
        }
    } catch (e) {}
}

function aplicarFiltroCategoriaCliente(codigoCategoria) {
    var valor = typeof codigoCategoria === "undefined" || codigoCategoria === null
        ? ""
        : String(codigoCategoria);
    $("#filtroCategoriaCliente").val(valor);
    refrescarFiltroCategoriaCliente();
    if (tablaClientesDt) {
        tablaClientesDt.ajax.reload();
    }
}

$(function () {
    refrescarFiltroCategoriaCliente();

    $("#filtroCategoriaCliente").on("changed.bs.select change", function () {
        if (tablaClientesDt) {
            tablaClientesDt.ajax.reload();
        }
    });

    $(document).on("click", ".filtro-categoria-clientes", function () {
        var codigo = $(this).attr("data-categoria") || "";
        var actual = $("#filtroCategoriaCliente").val() || "";
        // Segundo clic en la misma caja limpia el filtro
        if (actual === codigo) {
            aplicarFiltroCategoriaCliente("");
        } else {
            aplicarFiltroCategoriaCliente(codigo);
        }
    });
});
// VALIDACIÓN DE UN DOCUMENTO EXISTENTE EN LA BD AL REGISTRAR
function validarDocumento(documento, tipo) {
    if (tipo === "1" && documento.length === 8) {
        return true;
    } else if (tipo === "6" && documento.length === 11) {
        return true;
    } else {
        return false;
    }
}

function verificarDocumentoExistente(documento, tipo) {
    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta) {
                toastr["error"](
                    "El documento ya existe!, por favor ingresar otro"
                );
                $("#documentoCliente").val("").focus();
                $("#tipo_persona").val("");
                $("#ape_paterno").val("");
                $("#ape_materno").val("");
                $("#nombres").val("");
                $("#nuevaDireccion").val("");

                $("#nuevoUbiPro").val("");
                $("#nuevoUbiPro").selectpicker("refresh");
            }
        },
    });
}

$("#documentoCliente").change(function () {
    var documento = $(this).val();
    var tipo = $("#tipo_documento").val();

    if (validarDocumento(documento, tipo)) {
        verificarDocumentoExistente(documento, tipo);
    } else {
        //toastr["error"]("Revisar la cantidad de dígitos del documento");
        $("#documentoCliente").focus();
        $("#tipo_persona").val("");
        $("#ape_paterno").val("");
        $("#ape_materno").val("");
        $("#nombres").val("");
        $("#nuevaDireccion").val("");

        $("#nuevoUbiPro").val("");
        $("#nuevoUbiPro").selectpicker("refresh");
    }
});

// VALIDACIÓN DE select tipo cliente AL REGISTRAR
$("#documentoCliente").keyup(function () {
    var documento = $(this).val();
    if (documento.length == 8) {
        inicio = documento.substring(0, 2);
        if (inicio != "10" && inicio != "20") {
            // console.log(inicio);
            $("#tipo_persona").val("1");
        }
    } else if (documento.length == 11) {
        inicio = documento.substring(0, 2);
        if (inicio == 20) {
            $("#tipo_persona").val("2");
        } else {
            $("#tipo_persona").val("1");
        }
    }
});

// VALIDACIÓN DE UN CODIGO DE CLIENTE EXISTENTE EN LA BD AL REGISTRAR
$("#codigoCliente").change(function () {
    var codigo = $(this).val();
    var datos = new FormData();
    datos.append("codigo", codigo);
    $.ajax({
        url: "ajax/clientes.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta) {
                Command: toastr["error"](
                    "El Codigo ya existe!, por favor ingresar otro"
                );
                $("#codigoCliente").val("");
                $("#codigoCliente").focus();

                /* $("#documentoCliente").val("").focus();
                $("#tipo_persona").val("");
                $("#ape_paterno").val("");
                $("#ape_materno").val("");
                $("#nombres").val("");
                $("#nuevaDireccion").val("");

                $("#nuevoUbiPro").val("");
                $("#nuevoUbiPro").selectpicker("refresh"); */
            } else {
                $(".msgError").remove();
            }
        },
    });
});

// VALIDACIÓN DE UN DOCUMENTO EXISTENTE EN LA BD AL EDITAR
$("#editarDocumento").keyup(function () {
    var documento = $(this).val();
    var datos = new FormData();
    datos.append("documento", documento);
    $.ajax({
        url: "ajax/clientes.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta) {
                Command: toastr["error"](
                    "El DNI ya existe!, por favor ingresar otro"
                );
                $("#editarDocumento").val("");
                $("#editarDocumento").focus();
            } else {
                $(".msgError").remove();
            }
        },
    });
});
// VALIDACIÓN DE tipo de cliente AL EDITAR
$("#editarDocumento").keyup(function () {
    var documento = $(this).val();
    if (documento.length == 8) {
        inicio = documento.substring(0, 2);
        if (inicio != 20 && inicio != 10) {
            $("#editarTipo_persona").val("1");
        }
    } else if (documento.length == 11) {
        inicio = documento.substring(0, 2);

        if (inicio == 20) {
            $("#editarTipo_persona").val("2");
        } else {
            $("#editarTipo_persona").val("1");
        }
    }
});

// VALIDACIÓN DE UN DOCUMENTO EXISTENTE EN LA BD AL EDITAR
$("#editarCodigoCliente").change(function () {
    var codigo = $(this).val();
    var datos = new FormData();
    datos.append("codigo", codigo);
    $.ajax({
        url: "ajax/clientes.ajax.php",
        type: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            if (respuesta) {
                Command: toastr["error"](
                    "El Codigo ya existe!, por favor ingresar otro"
                );
                $("#editarCodigoCliente").val("");
                $("#editarCodigoCliente").focus();
            } else {
                $(".msgError").remove();
            }
        },
    });
});

/*=============================================
EDITAR CLIENTE
=============================================*/
$(".tablaClientes").on("click", ".btnEditarCliente", function () {
    var codigo = $(this).attr("codigo");
    /* console.log("codigo", codigo); */

    var datos = new FormData();
    datos.append("codigo", codigo);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log(respuesta);

            $("#editarCodigoCliente").val(respuesta["codigo"]);
            $("#editarNombre").val(respuesta["nombre"]);
            $("#editarTipo_documento").val(respuesta["tipo_documento"]);
            $("#editarDocumento").val(respuesta["documento"]);
            $("#editarTipo_persona").val(respuesta["tipo_persona"]);
            $("#editarApe_paterno").val(respuesta["ape_paterno"]);
            $("#editarApe_materno").val(respuesta["ape_materno"]);
            $("#editarNombres").val(respuesta["nombres"]);
            $("#editarDireccion").val(respuesta["direccion"]);

            $("#editarUbigeo").val(respuesta["ubigeo"]);
            $("#editarUbigeo").selectpicker("refresh");

            $("#editarDireccionDespacho").val(respuesta["direccion_despacho"]);

            $("#editarUbigeoDespacho").val(respuesta["ubigeo_despacho"]);
            $("#editarUbigeoDespacho").selectpicker("refresh");

            $("#editarAgencia").val(respuesta["agencia"]);
            $("#editarAgencia").selectpicker("refresh");
            var agenteRet = respuesta["agente_retencion"];
            $("#editarAgente_retencion").val(
                (agenteRet == 1 || agenteRet === "1" || agenteRet === true) ? "1" : "0"
            );

            $("#editarTelefono").val(respuesta["telefono"]);
            $("#editarTelefono2").val(respuesta["telefono2"]);
            $("#editarEmail").val(respuesta["email"]);
            $("#editarContacto").val(respuesta["contacto"]);
            $("#editarVendedor").val(respuesta["vendedor"]);
            $("#editarVendedor").selectpicker("refresh");
            $("#editarGrupo").val(respuesta["grupo"]);

            if ($("#editar_id_zona").length) {
                var idZonaCli = respuesta["id_zona"] ? String(respuesta["id_zona"]) : "";
                $("#editar_id_zona").val(idZonaCli);
            }

            $("#editarLista_precios").val(respuesta["lista_precios"]);
            $("#editarLista_precios").selectpicker("refresh");

            if (typeof cargarCategoriaComercialCliente === "function") {
                cargarCategoriaComercialCliente(respuesta["codigo"], respuesta["grupo"]);
            }

            if (typeof mostrarZonaEfectivaCliente === "function") {
                mostrarZonaEfectivaCliente(respuesta["codigo"]);
            }
        },
    });
});

function mostrarZonaEfectivaCliente(codigoCliente) {
    var $hint = $("#hintZonaEfectivaCliente");
    if (!$hint.length || !codigoCliente) {
        return;
    }
    $.post("ajax/zonas-comerciales.ajax.php", {
        accion: "resolverCliente",
        codigoCliente: codigoCliente
    }, function (resp) {
        if (!resp || !resp.ok) {
            $hint.text("");
            return;
        }
        if (resp.zona && resp.zona.nombre) {
            $hint.text("Efectiva: " + resp.zona.nombre + " (" + (resp.origen_etiqueta || resp.origen || "") + ")");
        } else {
            $hint.text("Efectiva: sin zona — conviene asignar o revisar ubigeo");
        }
    }, "json");
}

/*=============================================
ELIMINAR CLIENTE
=============================================*/
$(".tablas").on("click", ".btnEliminarCliente", function () {
    var idCliente = $(this).attr("idCliente");

    swal({
        title: "¿Está seguro de borrar el cliente?",
        text: "¡Si no lo está puede cancelar la acción!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Si, borrar cliente!",
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=clientes&idCliente=" + idCliente;
        }
    });
});

/*=============================================
EDITAR AVAL
=============================================*/
$(".tablaClientes").on("click", ".btnEditarAval", function () {
    var codigo = $(this).attr("codigo");
    /* console.log("codigo", codigo); */

    var datos = new FormData();
    datos.append("codigo", codigo);

    $.ajax({
        url: "ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            // console.log(respuesta);
            $("#avalCliente").val(respuesta["codigo"]);
            $("#editarAvalNombre").val(respuesta["aval_nombre"]);
            $("#editarAvalDir").val(respuesta["aval_dir"]);
            if (respuesta["aval_postal"] != null) {
                $("#editarAvalPostal").val(respuesta["aval_postal"]);
                $("#editarAvalPostal").selectpicker("refresh");
            } else {
                $("#editarAvalPostal").val("");
                $("#editarAvalPostal").selectpicker("refresh");
            }

            $("#editarAvalTelf").val(respuesta["aval_telf"]);
            $("#editarAvalRuc").val(respuesta["aval_ruc"]);

            $("#editarAvalLibreta").val(respuesta["aval_libreta"]);
        },
    });
});

//VALIDA SI ES RUC O DNI
function ObtenerDatosCliente() {
    tipodoc = $("#tipo_documento").find("option:selected").text();
    // console.log(tipodoc);
    if (tipodoc == "DNI") {
        ObtenerDatosDni();
    } else if (tipodoc == "RUC") {
        ObtenerDatosRuc2();
    }
}

// OBTENER DATOS DE DNI POR API
function ObtenerDatosDni() {
    var nuevoDni = $("#documentoCliente").val();
    var datos = new FormData();
    datos.append("nuevoDni", nuevoDni);
    $.ajax({
        type: "POST",
        url: "ajax/clientes.ajax.php",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (jsonx) {
            //console.log(jsonx["data"]);

            if (jsonx["success"] == false) {
                $("#nuevaRazPro").val("");
                $("#ape_paterno").val("");
                $("#ape_materno").val("");
                $("#nombres").val("");
                $("#nuevaDireccion").val("");
            } else {
                const datos = jsonx["data"];

                $("#nuevaRazPro").val(
                    datos["apellido_paterno"] +
                        " " +
                        datos["apellido_materno"] +
                        " " +
                        datos["nombres"]
                );
                $("#ape_paterno").val(datos["apellido_paterno"]);
                $("#ape_materno").val(datos["apellido_materno"]);
                $("#nombres").val(datos["nombres"]);
                $("#nuevaDireccion").val(datos["direccion"]);

                $("#nuevoUbiPro").val(datos["ubigeo"][2]);
                $("#nuevoUbiPro").selectpicker("refresh");
            }
        },
    });
}

//OBTENER DATOS POR RUC MEDIANTE LA API
function ObtenerDatosRuc2() {
    var nuevoRuc = $("#documentoCliente").val();
    var datos = new FormData();
    datos.append("nuevoRuc", nuevoRuc);
    $.ajax({
        type: "POST",
        url: "ajax/proveedor.ajax.php",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (jsonx) {
            //console.log(jsonx);
            if (jsonx["success"] == false) {
                $("#nuevaRazPro").attr("readonly", false);
                $("#nuevaRazPro").val("");
                $("#nuevaDireccion").val("");
                $("#nuevoUbiPro").val("");
                $("#nuevoUbiPro").selectpicker("refresh");
            } else {
                var data = jsonx["data"];

                $("#nuevaRazPro").val(data["nombre_o_razon_social"]);
                $("#nuevaDireccion").val(data["direccion"]);
                $("#nuevoUbiPro").val(data["ubigeo"][2]);
                $("#nuevoUbiPro").selectpicker("refresh");
            }
        },
    });
}

/*=============================================
CATEGORÍA COMERCIAL EN CLIENTES (selector simple)
=============================================*/
function refrescarSelectCategoria($select) {
    if (!$select.length) {
        return;
    }
    try {
        if ($select.data("selectpicker")) {
            $select.selectpicker("refresh");
        } else if (typeof $select.selectpicker === "function") {
            $select.selectpicker();
        }
    } catch (e) {}
}

function setSelectCategoriaModoGrupo(contexto, dataCategoriaGrupo) {
    var $select = contexto === "nuevo" ? $("#categoriaComercialNueva") : $("#categoriaComercialEditar");

    $select.prop("disabled", true);

    if (dataCategoriaGrupo && dataCategoriaGrupo.tiene_categoria && dataCategoriaGrupo.categoria) {
        $select.val(String(dataCategoriaGrupo.categoria.id));
    } else {
        $select.val("");
    }

    refrescarSelectCategoria($select);
}

function setSelectCategoriaModoIndividual(contexto, dataEfectiva) {
    var $select = contexto === "nuevo" ? $("#categoriaComercialNueva") : $("#categoriaComercialEditar");

    $select.prop("disabled", false);

    if (dataEfectiva && dataEfectiva.tiene_categoria && dataEfectiva.categoria) {
        $select.val(String(dataEfectiva.categoria.id));
    } else {
        $select.val("");
    }

    refrescarSelectCategoria($select);
}

function cargarCategoriaComercialCliente(codigoCliente, codigoGrupo) {
    var grupo = (codigoGrupo || "").toString().trim();

    if (grupo !== "") {
        var datosGrupo = new FormData();
        datosGrupo.append("accion", "categoriaGrupo");
        datosGrupo.append("codigoGrupo", grupo);

        $.ajax({
            url: "ajax/categorias-clientes.ajax.php",
            method: "POST",
            data: datosGrupo,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                setSelectCategoriaModoGrupo("editar", respuesta);
            },
            error: function () {
                setSelectCategoriaModoGrupo("editar", null);
            }
        });
        return;
    }

    var datos = new FormData();
    datos.append("accion", "efectivaCliente");
    datos.append("codigoCliente", codigoCliente);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            setSelectCategoriaModoIndividual("editar", respuesta);
        },
        error: function () {
            setSelectCategoriaModoIndividual("editar", null);
        }
    });
}

function sincronizarCategoriaPorGrupoSelect(contexto) {
    var esNuevo = contexto === "nuevo";
    var $grupo = esNuevo ? $("#grupo") : $("#editarGrupo");
    var codigoGrupo = ($grupo.val() || "").toString().trim();

    if (codigoGrupo === "") {
        if (esNuevo) {
            setSelectCategoriaModoIndividual("nuevo", null);
        } else {
            cargarCategoriaComercialCliente($("#editarCodigoCliente").val(), "");
        }
        return;
    }

    var datos = new FormData();
    datos.append("accion", "categoriaGrupo");
    datos.append("codigoGrupo", codigoGrupo);

    $.ajax({
        url: "ajax/categorias-clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            setSelectCategoriaModoGrupo(contexto, respuesta);
        },
        error: function () {
            setSelectCategoriaModoGrupo(contexto, null);
        }
    });
}

$("#grupo").on("change", function () {
    sincronizarCategoriaPorGrupoSelect("nuevo");
});

$("#editarGrupo").on("change", function () {
    sincronizarCategoriaPorGrupoSelect("editar");
});

$("#modalAgregarCliente").on("shown.bs.modal", function () {
    sincronizarCategoriaPorGrupoSelect("nuevo");
    refrescarSelectCategoria($("#categoriaComercialNueva"));
});

// Si el select está deshabilitado (cliente en grupo), habilitarlo al enviar para no perder el POST,
// pero el backend ignora categoría individual cuando hay grupo.
$("#modalEditarCliente form").on("submit", function () {
    var $select = $("#categoriaComercialEditar");
    if ($select.prop("disabled")) {
        $select.prop("disabled", false);
        refrescarSelectCategoria($select);
    }
});
