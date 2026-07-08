$('.tablaVendedores').DataTable({
    "ajax": "ajax/maestros/tabla-vendedor.ajax.php?perfil="+$("#perfilOculto").val(),
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
$(".tablaVendedores").on("click", ".btnEditarVendedor", function () {

    var idVendedor = $(this).attr("idVendedor");

    var datos = new FormData();
    datos.append("idVendedor", idVendedor);

    $.ajax({

        url: "ajax/vendedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {

            $("#idVendedor").val(respuesta["id"]);
            $("#editarCodigo").val(respuesta["codigo"]);
            $("#editarDescripcion").val(respuesta["descripcion"]);
            $("#editarEstadoDecisiones").val(respuesta["estado_decisiones"] ? "1" : "0");
        }

    })

})

/*=============================================
TOGGLE CENTRO DE DECISIONES (CLIC EN COLUMNA)
=============================================*/
$(".tablaVendedores").on("click", ".btnToggleEstadoDecisiones", function () {

    var $badge = $(this);

    if ($badge.data("loading")) {
        return;
    }

    var idVendedor = $badge.attr("idVendedor");
    var estadoActual = $badge.attr("estadoDecisiones");
    var nuevoEstado = estadoActual === "1" ? "0" : "1";

    var datos = new FormData();
    datos.append("toggleEstadoDecisiones", "1");
    datos.append("idVendedor", idVendedor);
    datos.append("estadoDecisiones", nuevoEstado);

    $badge.data("loading", true);

    $.ajax({

        url: "ajax/vendedor.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {

            if (respuesta.status === "ok") {
                $(".tablaVendedores").DataTable().ajax.reload(null, false);
            } else {
                swal({
                    type: "error",
                    title: "No se pudo actualizar el estado",
                    showConfirmButton: true
                });
            }

        },
        error: function () {

            swal({
                type: "error",
                title: "Error de conexión",
                showConfirmButton: true
            });

        },
        complete: function () {

            $badge.data("loading", false);

        }

    });

});


/*=============================================
ELIMINAR TIPO DE PAGO
=============================================*/
$(".tablaVendedores").on("click", ".btnEliminarVendedor", function(){

	var idVendedor = $(this).attr("idVendedor");
	
	swal({
        title: '¿Está seguro de borrar el vendedor?',
        text: "¡Si no lo está puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, borrar vendedor!'
      }).then(function(result){
        if (result.value) {
          
            window.location = "index.php?ruta=vendedor&idVendedor="+idVendedor;
        }

  })

})
//Reporte de Colores
// $(".box").on("click", ".btnReporteColor", function () {
//     window.location = "vistas/reportes_excel/rpt_color.php";
  
// })