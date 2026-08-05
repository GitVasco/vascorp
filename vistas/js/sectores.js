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

$(".tablaSectores").on("click", ".btnEditarSector", function () {

    var idSector = $(this).attr("idSector");
    var tipoSector = $(this).attr("tipoSector");

    // Mostrar de inmediato el tipo de la fila (sin esperar al ajax)
    if (typeof tipoSector !== "undefined") {
        setEditarTipoSector(tipoSector);
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
