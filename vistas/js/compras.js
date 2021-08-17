$(".TablaRegCompras").DataTable({

	"ajax": "ajax/centrocostos/tabla-reg_compras.ajax.php",
	"deferRender": true,
	"retrieve": true,
	"processing": true,
	"order": [[3, "asc"]],
	"pageLength": 20,
	"lengthMenu": [[20, 40, 60, -1], [20, 40, 60, 'Todos']],
	"language": {
		"sProcessing": "Procesando...",
		"sLengthMenu": "Mostrar _MENU_ registros",
		"sZeroRecords": "No se encontraron resultados",
		"sEmptyTable": "No hay datos disponibles en esta tabla",
		"sInfo": "Registros del _START_ al _END_ de un total de _TOTAL_",
		"sInfoEmpty": "Registros del 0 al 0 de un total de 0",
		"sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
		"sInfoPostFix": "",
		"sSearch": "Buscar:",
		"sUrl": "",
		"sInfoThousands": ",",
		"sLoadingRecords": "Cargando...",
		"oPaginate": {
			"sFirst": "Primero",
			"sLast": "Último",
			"sNext": "Siguiente",
			"sPrevious": "Anterior"
		},
		"oAria": {
			"sSortAscending": ": Activar para ordenar la columna de manera ascendente",
			"sSortDescending": ": Activar para ordenar la columna de manera descendente"
		}
	}

});

$(".generarTxt").click(function(){

	var estado = 'SI';
	console.log(estado);

	// Capturamos el id de la orden de compra
	swal({
        title: '¿Desea generar *.txt?',
        text: "¡Puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, generar *.txt!'
    }).then(function (result) {

	if (result.value) {

		window.location = "index.php?ruta=compras-reg&estado="+estado;

	}
	})

})


$(".TablaRegCompras").on("click","button.btnConsultarEstadoCompra",function(){

	var tipo = $(this).attr("tipo");
	var ruc = $(this).attr("ruc");
	var serie = $(this).attr("serie");
	var correlativo = $(this).attr("correlativo");
	var emision = $(this).attr("fecha");
	var monto = $(this).attr("monto");
	var tipoEmision = serie.substring(0,1);
	if(tipoEmision == "0" || tipoEmision == "1"){
		monto="";
	}
	
	var datos = new FormData();
  
	datos.append("tipoConsulta",tipo);
	datos.append("rucConsulta",ruc);
	datos.append("serieConsulta",serie);
	datos.append("correlativoConsulta",correlativo);
	datos.append("emisionConsulta",emision);
	datos.append("montoConsulta",monto);
  
	$.ajax({
  
	  url:"ajax/facturacion.ajax.php",
	  method: "POST",
	  data: datos,
	  cache: false,
	  contentType: false,
	  processData: false,
	  dataType:"json",
	  success:function(respuesta){
		console.log(respuesta);
		if(respuesta["success"] == true){
			
			if(respuesta["data"]["estadoCp"] == "1"){

				var datos2=new FormData();
				datos2.append("ruc",ruc);
				datos2.append("serie",serie);
				datos2.append("correlativo",correlativo);
				datos2.append("comprobante","2");

				if(respuesta["data"]["estadoRuc"] == "00"){
					var contribuyente = "2";
					var msjcontri = "ACTIVO";
				}else{
					var contribuyente = "1";
					var msjcontri = "-"
				}

				if(respuesta["data"]["condDomiRuc"] == "00"){
					var domicilio = "2";
					var msjdomi = "HABIDO";
				}else{
					var domicilio = "1";
					var msjdomi = "-";
				}
				datos2.append("contribuyente",contribuyente);
				datos2.append("domicilio",domicilio);
				$.ajax({
					url:"ajax/compras.ajax.php",
					type:"POST",
					data:datos2,
					cache:false,
					contentType:false,
					processData:false,
					success:function(respuesta2){
						$(".TablaRegCompras").DataTable().ajax.reload(null,false);
					}

				})
			  
			  Command: toastr["success"]("Estado del comprobante : ACEPTADO");
			  Command: toastr["success"]("Estado del contribuyente : "+msjcontri);
			  Command: toastr["success"]("Condicion de domicilio : "+msjdomi);
  
			}else if(respuesta["data"]["estadoCp"] == "3"){
				var datos2=new FormData();
				datos2.append("ruc",ruc);
				datos2.append("serie",serie);
				datos2.append("correlativo",correlativo);
				datos2.append("comprobante","2");

				if(respuesta["data"]["estadoRuc"] == "00"){
					var contribuyente = "2";
					var msjcontri = "ACTIVO";
				}else{
					var contribuyente = "1";
					var msjcontri = "-";
				}

				if(respuesta["data"]["condDomiRuc"] == "00"){
					var domicilio = "2";
					var msjdomi = "HABIDO";
				}else{
					var domicilio = "1";
					var msjdomi = "-";
				}
				datos2.append("contribuyente",contribuyente);
				datos2.append("domicilio",domicilio);
				$.ajax({
					url:"ajax/compras.ajax.php",
					type:"POST",
					data:datos2,
					cache:false,
					contentType:false,
					processData:false,
					success:function(respuesta2){
						$(".TablaRegCompras").DataTable().ajax.reload(null,false);
					}

				})
  
			  Command: toastr["success"]("Estado del comprobante : ACEPTADO");
			  Command: toastr["success"]("Estado del contribuyente : "+msjcontri);
			  Command: toastr["success"]("Condicion de domicilio : "+msjdomi);
  
			}else{

				var datos2=new FormData();
				datos2.append("ruc",ruc);
				datos2.append("serie",serie);
				datos2.append("correlativo",correlativo);
				datos2.append("comprobante","1");

				if(respuesta["data"]["estadoRuc"] == "00"){
					var contribuyente = "2";
					var msjcontri = "ACTIVO";
				}else{
					var contribuyente = "1";
					var  msjcontri = "-";
				}

				if(respuesta["data"]["condDomiRuc"] == "00"){
					var domicilio = "2";
					var msjdomi = "HABIDO";
				}else{
					var domicilio = "1";
					var msjdomi = "-";
				}

				datos2.append("contribuyente",contribuyente);
				datos2.append("domicilio",domicilio);
				$.ajax({
					url:"ajax/compras.ajax.php",
					type:"POST",
					data:datos2,
					cache:false,
					contentType:false,
					processData:false,
					success:function(respuesta2){
						$(".TablaRegCompras").DataTable().ajax.reload(null,false);
					}

				})
			  
			  Command: toastr["error"]("Estado del comprobante : NO EXISTE");
			  Command: toastr["error"]("Estado del contribuyente : "+msjcontri);
			  Command: toastr["error"]("Condicion de domicilio : "+msjdomi);
			}
	
		}else if(respuesta["message"] == "Unauthorized"){
		  Command: toastr["error"]("Por favor, generar token!");
		}else{
		  Command: toastr["error"]("Error al ingresar los campos requeridos!");
		}
		
	  }
	})
  });
  