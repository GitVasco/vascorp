$('.tablaTipoPagos').DataTable({
    "ajax": "ajax/maestros/tabla-tipopagos.ajax.php?perfil="+$("#perfilOculto").val(),
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
EDITAR TIPO DE PAGO
=============================================*/
$(".tablaTipoPagos").on("click", ".btnEditarTipoPago", function () {

    var idTipoPago = $(this).attr("idTipoPago");

    var datos = new FormData();
    datos.append("idTipoPago", idTipoPago);

    $.ajax({

        url: "ajax/tipopagos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {

            $("#idTipoPago").val(respuesta["id"]);
            $("#editarCodigo").val(respuesta["codigo"]);
            $("#editarDescripcion").val(respuesta["descripcion"]);
        }

    })

})


/*=============================================
ELIMINAR TIPO DE PAGO
=============================================*/
$(".tablaTipoPagos").on("click", ".btnEliminarTipoPago", function(){

	var idTipoPago = $(this).attr("idTipoPago");
	
	swal({
        title: '¿Está seguro de borrar el tipo de pago?',
        text: "¡Si no lo está puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, borrar tipo de pago!'
      }).then(function(result){
        if (result.value) {
          
            window.location = "index.php?ruta=tipopagos&idTipoPago="+idTipoPago;
        }

  })

})
//Reporte de Colores
// $(".box").on("click", ".btnReporteColor", function () {
//     window.location = "vistas/reportes_excel/rpt_color.php";
  
// })