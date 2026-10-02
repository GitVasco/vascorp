/*=============================================
TABLA SECTORES
=============================================*/
$('.tablaSectores').DataTable({
    "ajax": "ajax/maestros/tabla-sectores.ajax.php?perfil="+$("#perfilOculto").val(),
    "deferRender": true,
    "retrieve": true,
    "processing": true,
    "order": [[0, "asc"]],
    "pageLength": 20,
	  "lengthMenu": [[20, 40, 60, -1], [20, 40, 60, 'Todos']],
    "language": {
			"sProcessing":     "Procesando...",
			"sLengthMenu":     "Mostrar _MENU_ registros",
			"sZeroRecords":    "No se encontraron resultados",
			"sEmptyTable":     "Ningún dato disponible en esta tabla",
			"sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
			"sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
			"sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			"sInfoPostFix":    "",
			"sSearch":         "Buscar:",
			"sUrl":            "",
			"sInfoThousands":  ",",
			"sLoadingRecords": "Cargando...",
			"oPaginate": {
			"sFirst":    "Primero",
			"sLast":     "Último",
			"sNext":     "Siguiente",
			"sPrevious": "Anterior"
			},
			"oAria": {
				"sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
				"sSortDescending": ": Activar para ordenar la columna de manera descendente"
			}
    }    
  });

/*=============================================
EDITAR SECTOR
=============================================*/
function normalizarTipoSector(tipo) {
    if (tipo === 0 || tipo === "0" || Number(tipo) === 0) {
        return "0";
    }
    return "1";
}

function setEditarTipoSector(tipo) {
    var valor = normalizarTipoSector(tipo);
    var $select = $("#editarTipo");
    $select.val(valor);
    $select.find("option").prop("selected", false);
    $select.find("option[value='" + valor + "']").prop("selected", true);
}

function setColorSectorSelect($select, color, $preview) {
    color = (color || "").toUpperCase();
    if (color && $select.find("option[value='" + color + "']").length === 0) {
        $select.append($("<option>").val(color).attr("data-color", color).text(color));
    }
    if (color) {
        $select.val(color);
    }
    if ($preview && $preview.length) {
        $preview.css("background", $select.val() || "#A8D5E5");
    }
}

$(document).on("change", ".selectColorSector", function () {
    var id = $(this).attr("id");
    var color = $(this).val() || "#A8D5E5";
    if (id === "nuevoColor") {
        $("#previewNuevoColorSector").css("background", color);
    } else if (id === "editarColor") {
        $("#previewEditarColorSector").css("background", color);
    }
});

$(".tablaSectores").on("click", ".btnEditarSector", function () {

    var idSector = $(this).attr("idSector");
    var tipoSector = $(this).attr("tipoSector");
    var estadoSector = $(this).attr("estadoSector");
    var colorSector = $(this).attr("colorSector");

    // Mostrar de inmediato el tipo de la fila (sin esperar al ajax)
    if (typeof tipoSector !== "undefined") {
        setEditarTipoSector(tipoSector);
    }
    if (typeof estadoSector !== "undefined") {
        $("#editarEstado").val(String(estadoSector) === "0" ? "0" : "1");
    }
    if (typeof colorSector !== "undefined") {
        setColorSectorSelect($("#editarColor"), colorSector, $("#previewEditarColorSector"));
    }

    var datos = new FormData();
    datos.append("idSector", idSector);

    $.ajax({

        url: "ajax/sectores.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {

            $("#idSector").val(respuesta["id"]);
            $("#editarCodigo").val(respuesta["cod_sector"]);
            $("#editarSector").val(respuesta["nom_sector"]);
            if (respuesta && typeof respuesta["tipo"] !== "undefined" && respuesta["tipo"] !== null) {
                setEditarTipoSector(respuesta["tipo"]);
            }
            if (respuesta && typeof respuesta["estado"] !== "undefined" && respuesta["estado"] !== null) {
                $("#editarEstado").val(String(respuesta["estado"]) === "0" ? "0" : "1");
            }
            if (respuesta) {
                setColorSectorSelect(
                    $("#editarColor"),
                    respuesta["color"] || colorSector || "#A8D5E5",
                    $("#previewEditarColorSector")
                );
            }

        }

    })

})


/*=============================================
ELIMINAR COLOR
=============================================*/
$(".tablaSectores").on("click", ".btnEliminarSector", function(){

	var idSector = $(this).attr("idSector");
	
	swal({
        title: '¿Está seguro de borrar el sector?',
        text: "¡Si no lo está puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, borrar sector!'
      }).then(function(result){
        if (result.value) {
          
            window.location = "index.php?ruta=sectores&idSector="+idSector;
        }

  })

})
//Reporte de Sectores
$(".box").on("click", ".btnReporteSector", function () {
    window.location = "vistas/reportes_excel/rpt_sectores.php";
  
})


/*=============================================
DATOS PARA GUIA DE REMISION (solo externos)
=============================================*/
function escSectorGre(s) { return $("<div>").text(s == null ? "" : s).html(); }

function toggleBloqueGre(prefijo) {
    var externo = $("#" + prefijo + "Tipo").val() === "1";
    var $modal = $("#" + (prefijo === "nuevo" ? "modalAgregarSector" : "modalEditarSector"));
    $("#" + prefijo + "ColGre").toggle(externo);
    $("#" + prefijo + "ColGen").toggleClass("col-md-6", externo).toggleClass("col-md-12", !externo);
    $modal.find(".modal-dialog").toggleClass("modal-lg", externo);
}

function limpiarBloqueGre(prefijo) {
    $("#" + prefijo + "GreMsg").text("");
    $("#" + prefijo + "GreBloque").find("input[type=text]").val("");
    $("#" + prefijo + "GreTipoDoc").val("6");
}

function llenarBloqueGre(prefijo, d) {
    limpiarBloqueGre(prefijo);
    if (!d) return;
    $("#" + prefijo + "GreRazon").val(d.razon_social);
    $("#" + prefijo + "GreTipoDoc").val(d.tipo_doc || "6");
    $("#" + prefijo + "GreDoc").val(d.doc);
    $("#" + prefijo + "GreEmail").val(d.email);
    $("#" + prefijo + "GreDireccion").val(d.direccion);
    $("#" + prefijo + "GreUbigeo").val(d.ubigeo);
    $("#" + prefijo + "GreDpto").val(d.dpto);
    $("#" + prefijo + "GreProv").val(d.prov);
    $("#" + prefijo + "GreDist").val(d.dist);
}

$(document).on("change", "#nuevoTipo", function () { toggleBloqueGre("nuevo"); });
$(document).on("change", "#editarTipo", function () { toggleBloqueGre("editar"); });
$("#modalAgregarSector").on("show.bs.modal", function () { toggleBloqueGre("nuevo"); });

// Al abrir "Editar": pedimos de nuevo el sector para traer sus datos fiscales
$(".tablaSectores").on("click", ".btnEditarSector", function () {
    var datos = new FormData();
    datos.append("idSector", $(this).attr("idSector"));
    limpiarBloqueGre("editar");
    toggleBloqueGre("editar");
    $.ajax({
        url: "ajax/sectores.ajax.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
        success: function (r) {
            llenarBloqueGre("editar", r ? r.gre : null);
            toggleBloqueGre("editar");
        }
    });
});

// Buscador de ubigeo
(function () {
    var t = null;
    $(document).on("input", ".gre-sector-ubigeo", function () {
        var $i = $(this), $l = $i.siblings("ul"), q = $.trim($i.val());
        clearTimeout(t);
        if (q.length < 2) { $l.hide().empty(); return; }
        t = setTimeout(function () {
            $.post("ajax/sectores.ajax.php", { buscarUbigeoGre: q }, function (r) {
                $l.empty();
                $.each(r || [], function (k, d) {
                    $("<li class='list-group-item' style='cursor:pointer;padding:6px 10px'>")
                        .html("<b>" + escSectorGre(d.codigo) + "</b> " + escSectorGre(d.departamento + " / " + d.provincia + " / " + d.distrito))
                        .data("d", d).appendTo($l);
                });
                if (!(r || []).length) $("<li class='list-group-item text-muted'>").text("Sin resultados").appendTo($l);
                $l.show();
            }, "json");
        }, 250);
    });
    $(document).on("click", ".gre-sector-ubigeo + ul li", function () {
        var d = $(this).data("d"), $i = $(this).closest("ul").siblings("input"), p = $i.data("p");
        if (!d) return;
        $("#" + p + "GreUbigeo").val(d.codigo); $("#" + p + "GreDpto").val(d.departamento);
        $("#" + p + "GreProv").val(d.provincia); $("#" + p + "GreDist").val(d.distrito);
        $(this).closest("ul").hide().empty(); $i.val("");
    });
})();


// Buscar RUC / DNI en la API (SUNAT / RENIEC)
$(document).on("click", ".btnBuscarDocGre", function () {
    var p = $(this).data("p"), $b = $(this);
    var tipo = $("#" + p + "GreTipoDoc").val(), num = $.trim($("#" + p + "GreDoc").val());
    var $msg = $("#" + p + "GreMsg").removeClass("text-red text-green");
    if (tipo !== "6" && tipo !== "1") { $msg.addClass("text-red").text("La búsqueda solo existe para RUC y DNI."); return; }
    if (!num) { $msg.addClass("text-red").text("Escribe el número."); return; }
    $b.prop("disabled", true).find("i").attr("class", "fa fa-spinner fa-spin");
    $.post("ajax/sectores.ajax.php", { consultarDocGre: num, tipoDoc: tipo }, function (r) {
        if (!r || !r.ok) { $msg.addClass("text-red").text((r && r.msg) || "No se pudo consultar."); return; }
        $("#" + p + "GreRazon").val(r.razon_social);
        if (r.direccion) $("#" + p + "GreDireccion").val(r.direccion);
        if (r.ubigeo) {
            $("#" + p + "GreUbigeo").val(r.ubigeo); $("#" + p + "GreDpto").val(r.dpto);
            $("#" + p + "GreProv").val(r.prov); $("#" + p + "GreDist").val(r.dist);
        }
        $msg.addClass("text-green").text("Datos completados. Revísalos antes de guardar.");
    }, "json").fail(function () { $msg.addClass("text-red").text("Error de comunicación."); })
        .always(function () { $b.prop("disabled", false).find("i").attr("class", "fa fa-search"); });
});
