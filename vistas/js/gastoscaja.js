$(".btnEne").click(function(){
	var mesG = document.getElementById("btnEne").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnFeb").click(function(){
	var mesG = document.getElementById("btnFeb").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnMar").click(function(){
	var mesG = document.getElementById("btnMar").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnAbr").click(function(){
	var mesG = document.getElementById("btnAbr").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnMay").click(function(){
	var mesG = document.getElementById("btnMay").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnJun").click(function(){
	var mesG = document.getElementById("btnJun").value;
	//console.log(mesG);

	  var datos = new FormData();
	  datos.append("mesG", mesG);

	  	$.ajax({
	
			url:"ajax/tablamaestra.ajax.php",
			method: "POST",
			data: datos,
			cache: false,
			contentType: false,
			processData: false,
			dataType:"json",
			success:function(respuesta){
		  	
				//console.log(respuesta);
				document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
				document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
				document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
				document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

			}
	  
		})

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnJul").click(function(){
	var mesG = document.getElementById("btnJul").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnAgo").click(function(){
	var mesG = document.getElementById("btnAgo").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnSep").click(function(){
	var mesG = document.getElementById("btnSep").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnOct").click(function(){
	var mesG = document.getElementById("btnOct").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnNov").click(function(){
	var mesG = document.getElementById("btnNov").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	$(".btnDic").removeClass("btn-info");
	$(".btnDic").addClass("btn-default");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})
$(".btnDic").click(function(){
	var mesG = document.getElementById("btnDic").value;
	//console.log(mesG);

	var datos = new FormData();
	datos.append("mesG", mesG);

		$.ajax({
  
		  url:"ajax/tablamaestra.ajax.php",
		  method: "POST",
		  data: datos,
		  cache: false,
		  contentType: false,
		  processData: false,
		  dataType:"json",
		  success:function(respuesta){
			
			  //console.log(respuesta);
			  document.getElementById("saldoInicial").innerHTML=respuesta["saldo_inicial"];
			  document.getElementById("saldoIngreso").innerHTML=respuesta["ingresos"];
			  document.getElementById("saldoEgreso").innerHTML=respuesta["egresos"];
			  document.getElementById("saldoActual").innerHTML=respuesta["saldo_actual"];

		  }
	
	  })	

	$(".btnEne").removeClass("btn-info");
	$(".btnEne").addClass("btn-default");

	$(".btnFeb").removeClass("btn-info");
	$(".btnFeb").addClass("btn-default");

	$(".btnMar").removeClass("btn-info");
	$(".btnMar").addClass("btn-default");

	$(".btnAbr").removeClass("btn-info");
	$(".btnAbr").addClass("btn-default");

	$(".btnMay").removeClass("btn-info");
	$(".btnMay").addClass("btn-default");

	$(".btnJun").removeClass("btn-info");
	$(".btnJun").addClass("btn-default");

	$(".btnJul").removeClass("btn-info");
	$(".btnJul").addClass("btn-default");

	$(".btnAgo").removeClass("btn-info");
	$(".btnAgo").addClass("btn-default");

	$(".btnSep").removeClass("btn-info");
	$(".btnSep").addClass("btn-default");

	$(".btnOct").removeClass("btn-info");
	$(".btnOct").addClass("btn-default");

	$(".btnNov").removeClass("btn-info");
	$(".btnNov").addClass("btn-default");

	$(this).removeClass("btn-default");
	$(this).addClass("btn-info");

	localStorage.setItem("mesG", mesG);
	$(".TablaGastosCaja").DataTable().destroy();
	cargarTablaGastosCaja(localStorage.getItem("mesG"));
})

 //OBTENER DATOS POR RUC MEDIANTE LA API 
function ObtenerDatosRuc3(ruc){

	if(ruc == "nvo1"){

		var nuevoRuc = $("#nuevoRucProC").val();

	}else if(ruc == "nvo2"){

		var nuevoRuc = $("#editarRucProC").val();

	}else if(ruc == "nvo3"){

		var nuevoRuc = $("#nuevoRucProSol").val();

	}else if(ruc == "nvo4"){

		var nuevoRuc = $("#editarRucProSol").val();

	}

	//console.log(ruc);

	//var nuevoRuc = $("#nuevoRucProC, #editarRucProC, #nuevoRucProSol, #editarRucProSol").val();

	var tamano = nuevoRuc.length;
	//console.log(nuevoRuc);
	if(tamano == 8){
		var datos = new FormData();
		datos.append("nuevoDni",nuevoRuc);
		$.ajax({
			type: "POST",
			url: 'ajax/clientes.ajax.php',
			data: datos,
			cache: false,
			contentType: false,
			processData: false,
			dataType: "json",
			success:function( jsonx ) {
				//console.log(jsonx);
				if(jsonx["success"]==false){

					$('#nuevaRazPro').attr('readonly',false);
					$('#nuevaRazPro').val("");

					$('#editarRazPro').attr('readonly',false);
					$('#editarRazPro').val("");

					
				}else{
					var datos = jsonx["data"];

					$('#nuevaRazPro').val(datos["apellido_paterno"]+" "+datos["apellido_materno"]+" "+datos["nombres"] );

					$('#editarRazPro').val(datos["apellido_paterno"]+" "+datos["apellido_materno"]+" "+datos["nombres"] );
					
				}
			  
			}
		})
	}else if(tamano == 11){
		var datos = new FormData();
		datos.append("nuevoRuc",nuevoRuc);
		$.ajax({
			type: "POST",
			url: 'ajax/proveedor.ajax.php',
			data: datos,
			cache: false,
			contentType: false,
			processData: false,
			dataType: "json",
			success:function( jsonx ) {
				//console.log(jsonx);
				if(jsonx["success"]==false){

					$('#nuevaRazPro').attr('readonly',false);
					$('#nuevaRazPro').val("");

					$('#editarRazPro').attr('readonly',false);
					$('#editarRazPro').val("");
					
				}else{
					var data = jsonx["data"];

					$('#nuevaRazPro').val(data["nombre_o_razon_social"]);

					$('#editarRazPro').val(data["nombre_o_razon_social"]);
				}
			  
			}
		})
	}else{
		
		$('#nuevaRazPro').attr('readonly',false);
		$('#nuevaRazPro').val("");

		$('#editarRazPro').attr('readonly',false);
		$('#editarRazPro').val("");

		Command: toastr["warning"]("DNI 8 digitos!");		
		Command: toastr["warning"]("RUC 11 digitos!");
	}
	
}

$("#nuevoCodCaja, #editarCodCaja").change(function(){

	var cod_caja = $(this).val();

	var datos = new FormData();

	datos.append("cod_caja", cod_caja);

	$.ajax({

		url:"ajax/centro-costos.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		dataType:"json",
		success:function(respuesta){

			$("#editarGasto").val(respuesta["nombre_gasto"]);
			$("#editarArea").val(respuesta["nombre_area"]);
			$("#editarCaja").val(respuesta["descripcion"]);

			$("#gasto").val(respuesta["nombre_gasto"]);
			$("#area").val(respuesta["nombre_area"]);
			$("#caja").val(respuesta["descripcion"]);

			if(respuesta["tipo_gasto"] == "94"){

				document.getElementById("gasto").style.background = "#52BE80";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#52BE80";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#52BE80";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#52BE80";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#52BE80";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#52BE80";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "95"){

				document.getElementById("gasto").style.background = "#52BEB4";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#52BEB4";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#52BEB4";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#52BEB4";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#52BEB4";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#52BEB4";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "92"){

				document.getElementById("gasto").style.background = "#FF6868";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#FF6868";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#FF6868";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#FF6868";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#FF6868";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#FF6868";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "97"){

				document.getElementById("gasto").style.background = "#7C9EFF";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#7C9EFF";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#7C9EFF";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#7C9EFF";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#7C9EFF";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#7C9EFF";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "60"){

				document.getElementById("gasto").style.background = "#CCF459";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#CCF459";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#CCF459";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#CCF459";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#CCF459";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#CCF459";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "10"){

				document.getElementById("gasto").style.background = "#AAE1FF";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#AAE1FF";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#AAE1FF";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#AAE1FF";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#AAE1FF";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#AAE1FF";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "11"){

				document.getElementById("gasto").style.background = "#DDDAD6";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#DDDAD6";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#DDDAD6";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#DDDAD6";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#DDDAD6";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#DDDAD6";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "12"){

				document.getElementById("gasto").style.background = "#FFCFE8";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#FFCFE8";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#FFCFE8";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#FFCFE8";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#FFCFE8";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#FFCFE8";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "13"){

				document.getElementById("gasto").style.background = "#F5FAA5";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#F5FAA5";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#F5FAA5";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#F5FAA5";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#F5FAA5";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#F5FAA5";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}else if(respuesta["tipo_gasto"] == "14"){

				document.getElementById("gasto").style.background = "#DFB6F9";
				document.getElementById("gasto").style.color = "black";
				$("#gasto").css("font-weight","bold");

				document.getElementById("area").style.background = "#DFB6F9";
				document.getElementById("area").style.color = "black";
				$("#area").css("font-weight","bold");

				document.getElementById("caja").style.background = "#DFB6F9";
				document.getElementById("caja").style.color = "black";
				$("#caja").css("font-weight","bold");

				document.getElementById("editarGasto").style.background = "#DFB6F9";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#DFB6F9";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#DFB6F9";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");				

			}

		}
  
	}) 

})

/* 
*EDITAR GASTO
*/
$(".TablaGastosCaja tbody").on("click", "button.btnEditarGasto", function(){

	var idGasto = $(this).attr("idGasto");
	//console.log(idGasto);

	var datos = new FormData();
	datos.append("idGasto", idGasto);

	$.ajax({

		url:"ajax/centro-costos.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		dataType:"json",
		success:function(respuesta){
			//console.log(respuesta);

			$("#id").val(respuesta["id"]);

			$("#editarFechaGasto").val(respuesta["fecha"]);
			$("#editarRecibo").val(respuesta["recibo"]);
			$("#editarSucursal").val(respuesta["sucursal"]);
			$("#editarSucursal").selectpicker("refresh");
			$("#editarRucProC").val(respuesta["ruc_proveedor"]);
			$("#editarRazPro").val(respuesta["proveedor"]);
			$("#editarTipo").val(respuesta["tipo_documento"]);
			$("#editarTipo").selectpicker("refresh");
			$("#editarDocumentoG").val(respuesta["documento"]);
			$("#editarCodCaja").val(respuesta["cod_caja"]);
			$("#editarCodCaja").selectpicker("refresh");
			$("#editarTotal").val(respuesta["total"]);
			$("#totalAntiguo").val(respuesta["total"]);

			$("#editarGasto").val(respuesta["nombre_gasto"]);
			$("#editarArea").val(respuesta["nombre_area"]);
			$("#editarCaja").val(respuesta["nom_caja"]);

			if(respuesta["tipo_gasto"] == "94"){

				document.getElementById("editarGasto").style.background = "#52BE80";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#52BE80";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#52BE80";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "95"){

				document.getElementById("editarGasto").style.background = "#52BEB4";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#52BEB4";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#52BEB4";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "92"){

				document.getElementById("editarGasto").style.background = "#FF6868";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#FF6868";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#FF6868";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "97"){

				document.getElementById("editarGasto").style.background = "#7C9EFF";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#7C9EFF";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#7C9EFF";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "60"){

				document.getElementById("editarGasto").style.background = "#CCF459";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#CCF459";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#CCF459";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "10"){

				document.getElementById("editarGasto").style.background = "#AAE1FF";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#AAE1FF";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#AAE1FF";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "11"){

				document.getElementById("editarGasto").style.background = "#DDDAD6";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#DDDAD6";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#DDDAD6";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "12"){

				document.getElementById("editarGasto").style.background = "#FFCFE8";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#FFCFE8";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#FFCFE8";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "13"){

				document.getElementById("editarGasto").style.background = "#F5FAA5";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#F5FAA5";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#F5FAA5";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "14"){

				document.getElementById("editarGasto").style.background = "#DFB6F9";
				document.getElementById("editarGasto").style.color = "black";
				$("#editarGasto").css("font-weight","bold");

				document.getElementById("editarArea").style.background = "#DFB6F9";
				document.getElementById("editarArea").style.color = "black";
				$("#editarArea").css("font-weight","bold");

				document.getElementById("editarCaja").style.background = "#DFB6F9";
				document.getElementById("editarCaja").style.color = "black";
				$("#editarCaja").css("font-weight","bold");

			}
			
			$("#editarSolicitante").val(respuesta["solicitante"]);
			$("#editarDescripcion").val(respuesta["desc_salida"]);
			$("#editarRubro").val(respuesta["rubro_cancelacion"]);
			$("#editarObservacion").val(respuesta["observacion"]);



		}
  
	})	
})

$(".TablaGastosCaja").on("click",".btnAnularGasto",function(){
	var idGasto = $(this).attr("idGasto");
 
	// Capturamos el id de la orden de compra
	swal({
        title: '¿Está seguro de anular el gasto?',
        text: "¡Si no lo está puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, anular gasto!'
    }).then(function (result) {

	if (result.value) {

		window.location = "index.php?ruta=gastos-caja&idGasto="+idGasto;

	}
	})

});

$(".TablaSolicitud").DataTable({
    ajax: "ajax/centrocostos/tabla-solicitud-gastos.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    order: [[0, "desc"]],
    "pageLength": 20,
    "lengthMenu": [[20, 40, 60, -1], [20, 40, 60, 'Todos']],
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
        sPrevious: "Anterior"
      },
      oAria: {
        sSortAscending: ": Activar para ordenar la columna de manera ascendente",
        sSortDescending: ": Activar para ordenar la columna de manera descendente"
      }
    },
    "createdRow":function(row,data,index){
        if(data[4] == "94"){
            $('td',row).eq(4).css({
                'background-color':'#52BE80',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#52BE80',
                'color':'black'
            })
        }else if (data[4] == "95"){
            $('td',row).eq(4).css({
                'background-color':'#52BEB4',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#52BEB4',
                'color':'black'
            })
        }else if(data[4] == "92"){
            $('td',row).eq(4).css({
                'background-color':'#FF6868',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#FF6868',
                'color':'black'
            })
        }else if(data[4] == "97"){
            $('td',row).eq(4).css({
                'background-color':'#7C9EFF',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#7C9EFF',
                'color':'black'
            })
        }else if(data[4] == "60"){
            $('td',row).eq(4).css({
                'background-color':'#CCF459',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#CCF459',
                'color':'black'
            })
        }else if(data[4] == "10"){
            $('td',row).eq(4).css({
                'background-color':'#AAE1FF',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#AAE1FF',
                'color':'black'
            })
        }else if(data[4] == "11"){
            $('td',row).eq(4).css({
                'background-color':'#DDDAD6',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#DDDAD6',
                'color':'black'
            })
        }else if(data[4] == "12"){
            $('td',row).eq(4).css({
                'background-color':'#FFCFE8',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#FFCFE8',
                'color':'black'
            })
        }else if(data[4] == "13"){
            $('td',row).eq(4).css({
                'background-color':'#F5FAA5',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#F5FAA5',
                'color':'black'
            })
        }else if(data[4] == "14"){
            $('td',row).eq(4).css({
                'background-color':'#DFB6F9',
                'color':'black'
            })
            $('td',row).eq(5).css({
                'background-color':'#DFB6F9',
                'color':'black'
            })
        }

        if(data[8] == "POR RENDIR"){
            $('td',row).eq(8).css({
              'background-color':'#F5F106',
              'color':'black'
            })
        }

        if(data[0].substr(-2,2) == "31"){
            $('td',row).eq(0).css({
              'background-color':'#C2E4FF',
              'color':'black'
            })
        }else if(data[0].substr(-2,2) == "30"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "29"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "28"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "27"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "26"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "25"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "26"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "25"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "24"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "23"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "22"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "21"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "20"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "19"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "18"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "17"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "16"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "15"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "14"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "13"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "12"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "11"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "10"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "09"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "08"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "07"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "06"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "05"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "04"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "03"){
            $('td',row).eq(0).css({
                'background-color':'#C2FFD2',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "02"){
            $('td',row).eq(0).css({
                'background-color':'#E8C2FF',
                'color':'black'
            })
        }else if(data[0].substr(-2,2) == "01"){
            $('td',row).eq(0).css({
                'background-color':'#C2E4FF',
                'color':'black'
            })
        }


      }
});

/* 
*AGREGANDO COPAS
*/
$(".TablaSolicitud, .TablaGastosCaja").on("click", ".btnAprobarSol", function() {

	var idSolicitud = $(this).attr("idSolicitud");
	var total = $(this).attr("total");
	var estadoSol = $(this).attr("estadoSol");
	var fecha = $(this).attr("fecha");
	//console.log(estadoSol);

	var datos=new FormData();
	datos.append("idSolicitud",idSolicitud);
	datos.append("estadoSol",estadoSol);
	datos.append("fecha",fecha);
	datos.append("total",total);

	$.ajax({
		url:"ajax/centro-costos.ajax.php",
		type:"POST",
		data:datos,
		cache:false,
		contentType:false,
		processData:false,
		success:function(respuesta){

			//console.log(respuesta);

			if(respuesta == '"ok"' && estadoSol == "3"){

				Command: toastr["info"]("Entregar el dinero");

			}else if(respuesta == '"ok"' && estadoSol == "4"){

				Command: toastr["info"]("Por Aceptar");

			}else if(respuesta == '"ok"' && estadoSol == "1"){

				Command: toastr["success"]("Aprobado");

			}


		}

	});

	if(estadoSol == "3"){

		$(this).removeClass("btn-warning");
		$(this).addClass("btn-info");
		$(this).html("Por Rendir");
		$(this).attr("estadoSol","4");
		$(this).attr("total","0");

		var btnSol = "btnSol"+idSolicitud;
		//console.log(btnSol);
	
		document.getElementById(btnSol).disabled=true;

	}else if(estadoSol == "4"){

		$(this).removeClass("btn-info");
		$(this).addClass("btn-primary");
		$(this).html("Por Aceptar");
		

	}else if(estadoSol == "1"){

		$(this).removeClass("btn-primary");
		$(this).addClass("btn-success");
		$(this).html("Aprobado");
		

	}

})

$(".TablaSolicitud tbody").on("click", "button.btnEditarSolicitud", function(){

	var idGasto = $(this).attr("idGasto");
	//console.log(idGasto);
	var estado = $(this).attr("estado");
	//console.log(estado);

	var datos = new FormData();
	datos.append("idGasto", idGasto);

	$.ajax({

		url:"ajax/centro-costos.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		dataType:"json",
		success:function(respuesta){
			//console.log(respuesta);

			if(estado == "3" || estado == "4"){

				$("#editarTotalS").val(respuesta["total"]);
				$('#editarTotalS').attr('readonly',true);

			}else{

				$("#editarTotalS").val(respuesta["total"]);
				$('#editarTotalS').attr('readonly',false);

			}


			$("#id").val(respuesta["id"]);

			$("#editarFechaSol").val(respuesta["fecha"]);
			$("#editarRecibo").val(respuesta["recibo"]);
			$("#editarSucursalSol").val(respuesta["sucursal"]);
			$("#editarSucursalSol").selectpicker("refresh");
			$("#editarRucProSol").val(respuesta["ruc_proveedor"]);
			$("#editarRazProSol").val(respuesta["proveedor"]);
			$("#editarTipoSol").val(respuesta["tipo_documento"]);
			$("#editarTipoSol").selectpicker("refresh");
			$("#editarDocumentoS").val(respuesta["documento"]);
			$("#editarCodCajaSol").val(respuesta["cod_caja"]);
			$("#editarCodCajaSol").selectpicker("refresh");
			
			$("#estado").val(estado);
			$("#totalAntiguo").val(respuesta["total"]);

			$("#editarGastoSol").val(respuesta["nombre_gasto"]);
			$("#editarAreaSol").val(respuesta["nombre_area"]);
			$("#editarCajaSol").val(respuesta["nom_caja"]);

			if(respuesta["tipo_gasto"] == "94"){

				document.getElementById("editarGastoSol").style.background = "#52BE80";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#52BE80";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#52BE80";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "95"){

				document.getElementById("editarGastoSol").style.background = "#52BEB4";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#52BEB4";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#52BEB4";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "92"){

				document.getElementById("editarGastoSol").style.background = "#FF6868";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#FF6868";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#FF6868";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "97"){

				document.getElementById("editarGastoSol").style.background = "#7C9EFF";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#7C9EFF";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#7C9EFF";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "60"){

				document.getElementById("editarGastoSol").style.background = "#CCF459";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#CCF459";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#CCF459";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "10"){

				document.getElementById("editarGastoSol").style.background = "#AAE1FF";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#AAE1FF";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#AAE1FF";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "11"){

				document.getElementById("editarGastoSol").style.background = "#DDDAD6";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#DDDAD6";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#DDDAD6";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "12"){

				document.getElementById("editarGastoSol").style.background = "#FFCFE8";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#FFCFE8";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#FFCFE8";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "13"){

				document.getElementById("editarGastoSol").style.background = "#F5FAA5";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#F5FAA5";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#F5FAA5";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}else if(respuesta["tipo_gasto"] == "14"){

				document.getElementById("editarGastoSol").style.background = "#DFB6F9";
				document.getElementById("editarGastoSol").style.color = "black";
				$("#editarGastoSol").css("font-weight","bold");

				document.getElementById("editarAreaSol").style.background = "#DFB6F9";
				document.getElementById("editarAreaSol").style.color = "black";
				$("#editarAreaSol").css("font-weight","bold");

				document.getElementById("editarCajaSol").style.background = "#DFB6F9";
				document.getElementById("editarCajaSol").style.color = "black";
				$("#editarCajaSol").css("font-weight","bold");

			}
			
			$("#editarSolicitante").val(respuesta["solicitante"]);
			$("#editarDescripcion").val(respuesta["desc_salida"]);
			$("#editarRubro").val(respuesta["rubro_cancelacion"]);
			$("#editarObservacion").val(respuesta["observacion"]);



		}
  
	})	

})

$(".TablaSolicitud").on("click",".btnAnularGasto",function(){
	var idGasto = $(this).attr("idGasto");
 
	// Capturamos el id de la orden de compra
	swal({
        title: '¿Está seguro de anular la solicitud?',
        text: "¡Si no lo está puede cancelar la acción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, anular gasto!'
    }).then(function (result) {

	if (result.value) {

		window.location = "index.php?ruta=solicitud-caja&idGasto="+idGasto;

	}
	})

});