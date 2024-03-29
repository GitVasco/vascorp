/*
* cargamos la tabla para guais de remision
*/
$(".tablaGuiasRemision").DataTable({
    ajax: "ajax/facturacion/tabla-guiasremision.ajax.php",
    deferRender: true,
    retrieve: true,
    processing: true,
    "order": [[1, "desc"]],
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
    }
});

/*
* cargamos la tabla para FACTURAS
*/
if (localStorage.getItem("capturarRango24") != null) {
	$("#daterange-btnFactura span").html(localStorage.getItem("capturarRango24"));
	cargarTablaFactura(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"));
} else {
	$("#daterange-btnFactura span").html('<i class="fa fa-calendar"></i> Rango de Fecha ');
	cargarTablaFactura(null, null);
}

function cargarTablaFactura(fechaInicial,fechaFinal) {

$(".tablaFacturas").DataTable({
    ajax: "ajax/facturacion/tabla-facturas.ajax.php?perfil="+$("#perfilOculto").val() +"&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal,
    deferRender: true,
    retrieve: true,
    processing: true,
    "order": [[1, "desc"]],
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
    }
});
}

/*
* cargamos la tabla para BOLETAS
*/

if (localStorage.getItem("capturarRango25") != null) {
	$("#daterange-btnBoleta span").html(localStorage.getItem("capturarRango25"));
	cargarTablaBoleta(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"));
} else {
	$("#daterange-btnBoleta span").html('<i class="fa fa-calendar"></i> Rango de Fecha ');
	cargarTablaBoleta(null, null);
}

function cargarTablaBoleta(fechaInicial,fechaFinal) {
$(".tablaBoletas").DataTable({
    ajax: "ajax/facturacion/tabla-boletas.ajax.php?perfil="+$("#perfilOculto").val() +"&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal,
    deferRender: true,
    retrieve: true,
    processing: true,
    "order": [[1, "desc"]],
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
    }
});
}

/*
* cargamos la tabla para PROFORMAS
*/
if (localStorage.getItem("capturarRango26") != null) {
	$("#daterange-btnProforma span").html(localStorage.getItem("capturarRango26"));
	cargarTablaProforma(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"));
} else {
	$("#daterange-btnProforma span").html('<i class="fa fa-calendar"></i> Rango de Fecha ');
	cargarTablaProforma(null, null);
}

function cargarTablaProforma(fechaInicial,fechaFinal) {
$(".tablaProformas").DataTable({
    ajax: "ajax/facturacion/tabla-proformas.ajax.php?perfil="+$("#perfilOculto").val() +"&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal,
    deferRender: true,
    retrieve: true,
    processing: true,
    "order": [[1, "desc"]],
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
    }
});
}
/*
* ACTIVAR MODAL A
*/

$(".tablaGuiasRemision tbody").on("click", "button.btnFacturarA", function(){

    var codigo = $(this).attr("documento");
    var cod_cli = $(this).attr("cod_cli");
    var nom_cli = $(this).attr("nom_cli");
    var tip_doc = $(this).attr("tip_doc");
    var nro_doc = $(this).attr("nro_doc");
    var cod_ven = $(this).attr("cod_ven");

    var serie_dest = $(this).attr("serie_dest");
    var nro_dest = $(this).attr("nro_dest");
    //console.log(dscto);

    $("#codPedido").val(codigo);
    $("#codCli").val(cod_cli);
    $("#nomCli").val(nom_cli);
    $("#tipDoc").val(tip_doc);
    $("#nroDoc").val(nro_doc);
    $("#codVen").val(cod_ven);

    $("#serieDest").val(serie_dest);
    $("#docDest").val(nro_dest);

})

/*
* ACTIVAR MODAL B
*/

$(".tablaGuiasRemision tbody").on("click", "button.btnFacturarB", function(){

    var codigo = $(this).attr("documento");
    var cod_cli = $(this).attr("cod_cli");
    var nom_cli = $(this).attr("nom_cli");
    var tip_doc = $(this).attr("tip_doc");
    var nro_doc = $(this).attr("nro_doc");
    var cod_ven = $(this).attr("cod_ven");

    //console.log(codigo);

    $("#codPedidoB").val(codigo);
    $("#codCliB").val(cod_cli);
    $("#nomCliB").val(nom_cli);
    $("#tipDocB").val(tip_doc);
    $("#nroDocB").val(nro_doc);
    $("#codVenB").val(cod_ven);

})

/*
* validar el checkbox
*/
$(".chkFacturaB").change(function(){

    var chkBox = document.getElementById('chkFacturaB');
    //console.log(chkBox);
    var documento = "01";
    //console.log(documento);
    var serieSeparadoB = $("#serieSeparadoB");
    //console.log(serieSeparadoB);

    if(chkBox.checked == true){

        document.getElementById("chkBoletaB").disabled = true;
        document.getElementById("chkBoletaB").checked = false;

        document.getElementById("serieSeparadoB").disabled = false;

    }else{

        document.getElementById("chkBoletaB").disabled = false;

        document.getElementById("serieSeparadoB").disabled = true;

    }

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({

        url:"ajax/talonarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType:"json",
        success:function(respuesta){

            //console.log(respuesta);

            // Limpiamos el select
            serieSeparadoB.find('option').remove();

            serieSeparadoB.append('<option value="">Seleccionar Serie</option>');

            for(var id of respuesta){
                serieSeparadoB.append('<option value="' + id.numero + '">' + id.numero + '</option>');
                //console.log(serieSeparadoB);
            }

        }

    })

})


$(".chkBoletaB").change(function(){

    var chkBox = document.getElementById('chkBoletaB');
    //console.log(chkBox.checked);

    var serieSeparadoB = $("#serieSeparadoB");
    serieSeparadoB.find('option').remove();
    //console.log(serieSeparadoB);


    var documento = "03";

    if(chkBox.checked == true){

        document.getElementById("chkFacturaB").disabled = true;
        document.getElementById("chkFacturaB").checked = false;

        document.getElementById("serieSeparadoB").disabled = false;

    }else{

        document.getElementById("chkFacturaB").disabled = false;

        document.getElementById("serieSeparadoB").disabled = true;

    }

    var datos = new FormData();
    datos.append("documento", documento);

    $.ajax({

        url:"ajax/talonarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType:"json",
        success:function(respuesta){

            //console.log(respuesta);

            // Limpiamos el select
            serieSeparadoB.find('option').remove();

            serieSeparadoB.append('<option value="">Seleccionar Serie</option>');

            for(var id of respuesta){
                serieSeparadoB.append('<option value="' + id.numero + '">' + id.numero + '</option>');
                //console.log(serieSeparadoB);
            }

        }

    })

})
$(".box").on("change", ".optNotas1", function () {
    var nota = $(this).val();
    
    var serie = $("#tipoNotaSerie");
    var motivo =$("#notaMotivo");
    var documento = $("#tipoNotaDocumento");
    if(nota== "credito"){
        var datos = new FormData();
        datos.append("notaCredito", nota);
    
        $.ajax({
    
            url:"ajax/talonarios.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta){
    
            //    console.log(respuesta);
                serie.find('option').remove();

                serie.append('<option value="">Seleccionar Serie</option>');
    
                for(var id of respuesta){
                    serie.append('<option value="' + id.serie_nc + '">' + id.serie_nc + '</option>');
                    //console.log(serie);
                }
                serie.selectpicker("refresh");
                documento.val("0");
                $("#radioCtaCte").prop("disabled", true);
                $("#radioCtaCte").prop("checked", false);
            }
    
        })

        var datos2 = new FormData();
        datos2.append("documentoMotivo", "TMOT");
    
        $.ajax({
    
            url:"ajax/cuentas.ajax.php",
            method: "POST",
            data: datos2,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta2){
    
            //    console.log(respuesta);
                motivo.find('option').remove();

                motivo.append('<option value="">Seleccionar motivo</option>');
    
                for(var id of respuesta2){
                    motivo.append('<option value="' + id.codigo + '">' + id.codigo + ' - '+id.descripcion+ '</option>');
                    //console.log(serie);
                }
                motivo.selectpicker("refresh");
            }
    
        })
    }else{
        var datos = new FormData();
        datos.append("notaDebito", nota);

        $.ajax({

            url:"ajax/talonarios.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta){
                // console.log(respuesta);
                serie.find('option').remove();

                serie.append('<option value="">Seleccionar Serie</option>');
    
                for(var id of respuesta){
                    serie.append('<option value="' + id.serie_nd + '">' + id.serie_nd + '</option>');
                    //console.log(serie);
                }
            
                serie.selectpicker("refresh");
                documento.val("0");
                $("#radioCtaCte").prop("disabled", false);
                $("#radioCtaCte").prop("checked", true);
                
                // document.getElementById("radioCtaCte").checked = false;
            }

        })

        var datos2 = new FormData();
        datos2.append("documentoMotivo", "TMOTD");
    
        $.ajax({
    
            url:"ajax/cuentas.ajax.php",
            method: "POST",
            data: datos2,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta2){
    
            //    console.log(respuesta);
                motivo.find('option').remove();

                motivo.append('<option value="">Seleccionar motivo</option>');
    
                for(var id of respuesta2){
                    motivo.append('<option value="' + id.codigo + '">' + id.codigo + ' - '+id.descripcion+ '</option>');
                    //console.log(serie);
                }
                motivo.selectpicker("refresh");
            }
    
        })
    }
        
  
  })

/* 
* AL CAMBIAR EL SELECT DE DOCUMENTO
*/
$("#tipoNotaSerie").change(function(){
    var nota = $('input[name=optNotas1]:checked').val();
    // console.log(nota);
    var serie = document.getElementById("tipoNotaSerie").value;
    
    var documento = $("#tipoNotaDocumento");

    if(nota == "credito"){
        var datos = new FormData();
        datos.append("serie", serie);
    
        $.ajax({
    
            url:"ajax/talonarios.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta){
                var numero = Number(respuesta["nota_credito"])+Number(1);
                documento.val(serie+("0000000"+numero).slice(-8));
    
    
            }
    
        })
    }else{
        var datos = new FormData();
        datos.append("serieDebito", serie);
    
        $.ajax({
    
            url:"ajax/talonarios.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"json",
            success:function(respuesta){
                var numero = Number(respuesta["nota_debito"])+Number(1);
                documento.val(serie+("0000000"+numero).slice(-8));
    
    
            }
    
        })
    }
	

    

})

/* 
* AL CAMBIAR EL SELECT DE DOCUMENTO
*/
$("#notaSubTotal").change(function(){
    var subtotal= $(this).val();

    var igv=subtotal*0.18;

    $("#notaIGV").val(igv.toFixed(2));

    var descuento= $("#notaDsctos").val();
    var flete= $("#notaFlete").val();
    var otros= $("#notaOtros").val();
    var noAfecto= $("#notaNoAfecto").val();
    var total = Number(subtotal)+Number(igv)+Number(descuento)+Number(flete)+Number(otros)+Number(noAfecto);
    $("#notaTotal").val(total.toFixed(2));
});

$("#notaDsctos").change(function(){
    var descuento= $(this).val();
    var subtotal= $("#notaSubTotal").val();
    var igv=subtotal*0.18;
    var flete= $("#notaFlete").val();
    var otros= $("#notaOtros").val();
    var noAfecto= $("#notaNoAfecto").val();
    var total = Number(subtotal)+Number(igv)+Number(descuento)+Number(flete)+Number(otros)+Number(noAfecto);
    $("#notaTotal").val(total.toFixed(2));
});

$("#notaFlete").change(function(){
    var flete= $(this).val();
    var subtotal= $("#notaSubTotal").val();
    var igv=subtotal*0.18;
    var descuento= $("#notaDsctos").val();
    var otros= $("#notaOtros").val();
    var noAfecto= $("#notaNoAfecto").val();
    var total = Number(subtotal)+Number(igv)+Number(descuento)+Number(flete)+Number(otros)+Number(noAfecto);
    $("#notaTotal").val(total.toFixed(2));
});

$("#notaOtros").change(function(){
    var otros= $(this).val();
    var subtotal= $("#notaSubTotal").val();
    var igv=subtotal*0.18;
    var descuento= $("#notaDsctos").val();
    var flete= $("#notaFlete").val();
    var noAfecto= $("#notaNoAfecto").val();
    var total = Number(subtotal)+Number(igv)+Number(descuento)+Number(flete)+Number(otros)+Number(noAfecto);
    $("#notaTotal").val(total.toFixed(2));
});

$("#notaNoAfecto").change(function(){
    var noAfecto= $(this).val();
    var subtotal= $("#notaSubTotal").val();
    var igv=subtotal*0.18;
    var descuento= $("#notaDsctos").val();
    var flete= $("#notaFlete").val();
    var otros= $("#notaOtros").val();
    var total = Number(subtotal)+Number(igv)+Number(descuento)+Number(flete)+Number(otros)+Number(noAfecto);
    $("#notaTotal").val(total.toFixed(2));
});

$(".btnGuardarNotaCredito").click(function(){
    
    var nota = $('input[name=optNotas1]:checked').val();
    var chkCuenta = document.getElementById("radioCtaCte");
    if(nota == 'credito'){
        var tipoImp = "E05";
        var documento =$("#tipoNotaDocumento").val();
            var existe = new FormData();
            existe.append("documentoCredito", documento);
            var cliente=$("#selectNotaCliente").val();
            var vendedor=$("#selectNotaVendedor").val();
            var neto = $("#notaSubTotal").val();
            var igv = $("#notaIGV").val();
            var monto=$("#notaTotal").val();
            var fecha=$("#notaFecha").val();
            var usuario=$("#notaUsuario").val();
            //datos de notas cd
            var origen_venta = $("#notaNroFactura").val();
            var tip_nota = $("#selectNotaDocumento").val();
            var fecha_origen=$("#notaFechaFactura").val();
            var motivo=$("#notaMotivo").val();
            var tip_cont=$("#notaTipoCont").val();
            var observacion=$("#notaTexto").val();
            
            var datos = new Array();
            
            $.ajax({
                url: "ajax/facturacion.ajax.php",
                type: "POST",
                data: existe,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta) {
                    // console.log(respuesta);
                    if (respuesta) {
                        datos.push({
                            'tipo_doc':'08',
                            'tipo_venta':'E05',
                            'num_cta' : documento,
                            'cliente':cliente,
                            'vendedor':vendedor,
                            'neto':neto,
                            'igv':igv,
                            'monto':monto,
                            'saldo':monto,
                            'fecha':fecha,
                            'estado':'PENDIENTE',
                            'notas':'Nro doc '+documento+'/'+documento,
                            'renovacion':0,
                            'protesta':0,
                            'tip_mon':'Soles',
                            'cod_pago':'08',
                            'doc_origen':documento,
                            'usuario': usuario,
                            'origen_venta':origen_venta,
                            'tip_nota':tip_nota,
                            'fecha_origen':fecha_origen,
                            'motivo':motivo,
                            'tip_cont':tip_cont,
                            'observacion':observacion
                        });
                        var cuenta = {"datosCuenta" : datos}
                        
                        var jsonCuenta2= {"jsonCuenta2":JSON.stringify(cuenta)};
                        $.ajax({
                            url:"ajax/facturacion.ajax.php",
                            method: "POST",
                            data: jsonCuenta2,
                            cache: false,
                            success:function(respuesta2){
                                
                                Command:toastr["success"]("Editado de venta exitosamente!");
                                Command:toastr["success"]("Editado  de detalle nota exitosamente!");
                            }
    
                        })
                    }else{
                        datos.push({
                            'tipo_doc':'08',
                            'tipo_venta':'E05',
                            'num_cta' : documento,
                            'cliente':cliente,
                            'vendedor':vendedor,
                            'neto':neto,
                            'igv':igv,
                            'monto':monto,
                            'saldo':monto,
                            'fecha':fecha,
                            'estado':'PENDIENTE',
                            'notas':'Nro doc '+documento+'/'+documento,
                            'renovacion':0,
                            'protesta':0,
                            'tip_mon':'Soles',
                            'cod_pago':'08',
                            'doc_origen':documento,
                            'usuario': usuario,
                            'tip_doc_venta':'NC',
                            'origen_venta':origen_venta,
                            'tip_nota':tip_nota,
                            'fecha_origen':fecha_origen,
                            'motivo':motivo,
                            'tip_cont':tip_cont,
                            'observacion':observacion
                        });
                        var cuenta = {"datosCuenta" : datos}
                        
                        var jsonCuenta= {"jsonCuenta":JSON.stringify(cuenta)};
                        $.ajax({
                            url:"ajax/facturacion.ajax.php",
                            method: "POST",
                            data: jsonCuenta,
                            cache: false,
                            success:function(respuesta2){
                                
                                Command:toastr["success"]("Registrado de venta exitosamente!");

                                Command:toastr["success"]("Registrado  de detalle nota exitosamente!");
    
                            }
    
                        })
                    }
                
                }
            });

    }else{
      var tipoImp = "S99";
        if(chkCuenta.checked == true){

            var documento =$("#tipoNotaDocumento").val();
            var existe = new FormData();
            existe.append("documento", documento);
            var cliente=$("#selectNotaCliente").val();
            var vendedor=$("#selectNotaVendedor").val();
            var neto = $("#notaSubTotal").val();
            var igv = $("#notaIGV").val();
            var monto=$("#notaTotal").val();
            var fecha=$("#notaFecha").val();
            var usuario=$("#notaUsuario").val();

            var origen_venta = $("#notaNroFactura").val();
            var tip_nota = $("#selectNotaDocumento").val();
            var fecha_origen=$("#notaFechaFactura").val();
            var motivo=$("#notaMotivo").val();
            var tip_cont=$("#notaTipoCont").val();
            var observacion=$("#notaTexto").val();
            var datos = new Array();
            
            $.ajax({
                url: "ajax/cuentas.ajax.php",
                type: "POST",
                data: existe,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta) {
                    // console.log(respuesta);
                    if (respuesta) {
                        datos.push({
                            'id':respuesta["id"],
                            'tipo_doc':'08',
                            'tipo_venta':'S99',
                            'num_cta' : documento,
                            'cliente':cliente,
                            'vendedor':vendedor,
                            'neto':neto,
                            'igv':igv,
                            'monto':monto,
                            'saldo':monto,
                            'fecha':fecha,
                            'estado':'PENDIENTE',
                            'notas':'Nro doc '+documento+'/'+documento,
                            'renovacion':0,
                            'protesta':0,
                            'tip_mon':'Soles',
                            'cod_pago':'08',
                            'doc_origen':documento,
                            'usuario': usuario,
                            'tip_doc_venta':'ND',
                            'origen_venta':origen_venta,
                            'tip_nota':tip_nota,
                            'fecha_origen':fecha_origen,
                            'motivo':motivo,
                            'tip_cont':tip_cont,
                            'observacion':observacion
                        });
                        var cuenta = {"datosCuenta" : datos}
                        
                        var jsonCuenta2= {"jsonCuenta2":JSON.stringify(cuenta)};
                        $.ajax({
                            url:"ajax/cuentas.ajax.php",
                            method: "POST",
                            data: jsonCuenta2,
                            cache: false,
                            success:function(respuesta2){
                                var existe = new FormData();
                                existe.append("documentoDebito", documento);
                                $.ajax({
                                    url: "ajax/facturacion.ajax.php",
                                    type: "POST",
                                    data: existe,
                                    cache: false,
                                    contentType: false,
                                    processData: false,
                                    dataType: "json",
                                    success: function (respuesta3) {
                                        // console.log(respuesta);
                                        if (respuesta3) {
                                            
                                            $.ajax({
                                                url:"ajax/facturacion.ajax.php",
                                                method: "POST",
                                                data: jsonCuenta2,
                                                cache: false,
                                                success:function(respuesta4){
                                                    
                                                    Command:toastr["success"]("Editado  de venta exitosamente!");

                                                    Command:toastr["success"]("Editado  de detalle nota exitosamente!");
                        
                                                }
                        
                                            })
                                        }else{
                                            
                                            var jsonCuenta= {"jsonCuenta":JSON.stringify(cuenta)};
                                            $.ajax({
                                                url:"ajax/facturacion.ajax.php",
                                                method: "POST",
                                                data: jsonCuenta,
                                                cache: false,
                                                success:function(respuesta4){
                                                    
                                                    Command:toastr["success"]("Registrado  de venta exitosamente!");

                                                    Command:toastr["success"]("Registrado  de detalle nota exitosamente!");
                        
                                                }
                        
                                            })
                                        }

                                        Command:toastr["success"]("Editado de cuenta exitosamente!");
                                    }
                                });
    
                            }
    
                        })
                    }else{
                        datos.push({
                            'tipo_doc':'08',
                            'tipo_venta':'S99',
                            'num_cta' : documento,
                            'cliente':cliente,
                            'vendedor':vendedor,
                            'neto':neto,
                            'igv':igv,
                            'monto':monto,
                            'saldo':monto,
                            'fecha':fecha,
                            'estado':'PENDIENTE',
                            'notas':'Nro doc '+documento+'/'+documento,
                            'renovacion':0,
                            'protesta':0,
                            'tip_mon':'Soles',
                            'cod_pago':'08',
                            'doc_origen':documento,
                            'usuario': usuario,
                            'tip_mov':'+',
                            'tip_doc_venta':'ND',
                            'origen_venta':origen_venta,
                            'tip_nota':tip_nota,
                            'fecha_origen':fecha_origen,
                            'motivo':motivo,
                            'tip_cont':tip_cont,
                            'observacion':observacion
                        });
                        var cuenta = {"datosCuenta" : datos}
                        
                        //CREAR CUENTA
                        var jsonCuenta= {"jsonCuenta":JSON.stringify(cuenta)};
                        $.ajax({
                            url:"ajax/cuentas.ajax.php",
                            method: "POST",
                            data: jsonCuenta,
                            cache: false,
                            success:function(respuesta2){

                                //PASAR LOS DATOS POR AJAX PARA REGISTRAR LA VENTA
                                var existe = new FormData();
                                existe.append("documentoDebito", documento);
                                $.ajax({
                                    url: "ajax/facturacion.ajax.php",
                                    type: "POST",
                                    data: existe,
                                    cache: false,
                                    contentType: false,
                                    processData: false,
                                    dataType: "json",
                                    success: function (respuesta3) {
                                        // console.log(respuesta);
                                        if (respuesta3) {
                                            var jsonCuenta2= {"jsonCuenta2":JSON.stringify(cuenta)};
                                            $.ajax({
                                                url:"ajax/facturacion.ajax.php",
                                                method: "POST",
                                                data: jsonCuenta2,
                                                cache: false,
                                                success:function(respuesta4){
                                                    
                                                    Command:toastr["success"]("Editado  de venta exitosamente!");
                                                    Command:toastr["success"]("Editado  de detalle nota exitosamente!");
                        
                                                }
                        
                                            })
                                        }else{
                                            
                                            
                                            $.ajax({
                                                url:"ajax/facturacion.ajax.php",
                                                method: "POST",
                                                data: jsonCuenta,
                                                cache: false,
                                                success:function(respuesta4){
                                                    
                                                    Command:toastr["success"]("Registrado  de venta exitosamente!");

                                                    Command:toastr["success"]("Registrado  de detalle nota exitosamente!");
                        
                                                }
                        
                                            })
                                        }

                                        Command:toastr["success"]("Registrado de cuenta exitosamente!");
                                    }
                                });
    
                            }
    
                        })
                    }
                }
                });
            
            
        }else{
            //PASAR LOS DATOS POR AJAX PARA REGISTRAR LA VENTA
            var documento =$("#tipoNotaDocumento").val();
            var cliente=$("#selectNotaCliente").val();
            var vendedor=$("#selectNotaVendedor").val();
            var neto = $("#notaSubTotal").val();
            var igv = $("#notaIGV").val();
            var monto=$("#notaTotal").val();
            var fecha=$("#notaFecha").val();
            var usuario=$("#notaUsuario").val();

            var origen_venta = $("#notaNroFactura").val();
            var tip_nota = $("#selectNotaDocumento").val();
            var fecha_origen=$("#notaFechaFactura").val();
            var motivo=$("#notaMotivo").val();
            var tip_cont=$("#notaTipoCont").val();
            var observacion=$("#notaTexto").val();

            var datos = new Array();
            datos.push({
                'tipo_doc':'08',
                'tipo_venta':'S99',
                'num_cta' : documento,
                'cliente':cliente,
                'vendedor':vendedor,
                'neto':neto,
                'igv':igv,
                'monto':monto,
                'saldo':monto,
                'fecha':fecha,
                'estado':'PENDIENTE',
                'notas':'Nro doc '+documento+'/'+documento,
                'renovacion':0,
                'protesta':0,
                'tip_mon':'Soles',
                'cod_pago':'08',
                'doc_origen':documento,
                'usuario': usuario,
                'tip_mov':'+',
                'tip_doc_venta':'ND',
                'origen_venta':origen_venta,
                'tip_nota':tip_nota,
                'fecha_origen':fecha_origen,
                'motivo':motivo,
                'tip_cont':tip_cont,
                'observacion':observacion
            });
            var cuenta = {"datosCuenta" : datos}
            
            //CREAR CUENTA
            var jsonCuenta= {"jsonCuenta":JSON.stringify(cuenta)};

            var existe = new FormData();
            existe.append("documentoDebito", documento);
            $.ajax({
                url: "ajax/facturacion.ajax.php",
                type: "POST",
                data: existe,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta3) {
                    // console.log(respuesta);
                    if (respuesta3) {
                        var jsonCuenta2= {"jsonCuenta2":JSON.stringify(cuenta)};
                        $.ajax({
                            url:"ajax/facturacion.ajax.php",
                            method: "POST",
                            data: jsonCuenta2,
                            cache: false,
                            success:function(respuesta4){
                                
                                Command:toastr["success"]("Editado  de venta exitosamente!");
    
                            }
    
                        })
                    }else{
                        
                        
                        $.ajax({
                            url:"ajax/facturacion.ajax.php",
                            method: "POST",
                            data: jsonCuenta,
                            cache: false,
                            success:function(respuesta4){
                                
                                Command:toastr["success"]("Registrado  de venta exitosamente!");

                                Command:toastr["success"]("Registrado  de detalle nota exitosamente!");
    
                            }
    
                        })
                    }
                }
            });
        }
    }
    
    $(".btnImprimirNotaCredito").prop("disabled", false);
    $(".btnImprimirNotaCredito").attr("tipo", tipoImp);
    $(".btnImprimirNotaCredito").attr("documento", documento);
    
});


if (localStorage.getItem("capturarRango23") != null) {
	$("#daterange-btnNotasCD span").html(localStorage.getItem("capturarRango23"));
	cargarTablaNotaCD(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"));
} else {
	$("#daterange-btnNotasCD span").html('<i class="fa fa-calendar"></i> Rango de Fecha ');
	cargarTablaNotaCD(null, null);
}

/* 
* TABLA PARA PRODUCCION TRUSAS
*/
function cargarTablaNotaCD(fechaInicial,fechaFinal) {
	$('.tablaNotaCredito').DataTable( {
		"ajax": "ajax/facturacion/tabla-notacreditocd.ajax.php?perfil="+$("#perfilOculto").val() +"&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal,
		"deferRender": true,
		"retrieve": true,
		"processing": true,
		"order": [[1, "desc"]],
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
	} );
}


/*=============================================
RANGO DE FECHAS
=============================================*/


$("#daterange-btnNotasCD").daterangepicker(
    {
      cancelClass: "CancelarNotasCD",
      locale:{
		"daysOfWeek": [
			"Dom",
			"Lun",
			"Mar",
			"Mie",
			"Jue",
			"Vie",
			"Sab"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
	  },
      ranges: {
        Hoy: [moment(), moment()],
        Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Últimos 7 días": [moment().subtract(6, "days"), moment()],
        "Últimos 30 días": [moment().subtract(29, "days"), moment()],
        "Este mes": [moment().startOf("month"), moment().endOf("month")],
        "Último mes": [
          moment()
            .subtract(1, "month")
            .startOf("month"),
          moment()
            .subtract(1, "month")
            .endOf("month")
        ]
      },
      
      startDate: moment(),
      endDate: moment()
    },
    function(start, end) {
      $("#daterange-btnNotasCD span").html(
        start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
      );
  
      var fechaInicial = start.format("YYYY-MM-DD");
  
      var fechaFinal = end.format("YYYY-MM-DD");
  
      var capturarRango23 = $("#daterange-btnNotasCD span").html();
  
	  localStorage.setItem("capturarRango23", capturarRango23);
      localStorage.setItem("fechaInicial", fechaInicial);
	  localStorage.setItem("fechaFinal", fechaFinal);
	  
      // Recargamos la tabla con la información para ser mostrada en la tabla
      $(".tablaNotaCredito").DataTable().destroy();
      cargarTablaNotaCD(fechaInicial, fechaFinal);
    });
  
  /*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/
  
  $(".daterangepicker.opensleft .range_inputs .CancelarNotasCD").on(
    "click",
    function() {
      localStorage.removeItem("capturarRango23");
      localStorage.removeItem("fechaInicial");
      localStorage.removeItem("fechaFinal");
      localStorage.clear();
      window.location = "ver-nota-credito";
    }
  );
  
  /*=============================================
  CAPTURAR HOY
  =============================================*/
  
  $(".daterangepicker.opensleft .ranges li").on("click", function() {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if(ruta == "ver-nota-credito"){
        if (textoHoy == "Hoy") {
        var d = new Date();
    
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        var año = d.getFullYear();
    
        dia = ("0" + dia).slice(-2);
        mes = ("0" + mes).slice(-2);
    
        var fechaInicial = año + "-" + mes + "-" + dia;
        var fechaFinal = año + "-" + mes + "-" + dia;
        localStorage.setItem("capturarRango23", "Hoy");
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaNotaCredito").DataTable().destroy();
        cargarTablaNotaCD(fechaInicial, fechaFinal);
        }
    }       
  });


$(".tablaNotaCredito").on("click", ".btnEditarNotaCD", function () {
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");

    window.location = "index.php?ruta=editar-nota-credito&tipo="+tipo+"&documento="+documento;
})

$(".tablaNotaCredito").on("click", ".btnImprimirNotaCredito", function () {
  var tipo = $(this).attr("tipo");
  var documento = $(this).attr("documento");

  window.open("extensiones/tcpdf/pdf/reporte_notascd.php?tipo="+tipo+"&documento="+documento,"_blank");
})

$(".btnImprimirNotaCredito").click(function(){
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");
    window.open("extensiones/tcpdf/pdf/reporte_notascd.php?tipo="+tipo+"&documento="+documento,"_blank");
})

$(".btnTerminarNotaCredito").click(function(){
  window.location = "ver-nota-credito";
})

$(".tablaFacturas").on("click", ".btnImprimirFactura", function () {
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");

    window.open("extensiones/tcpdf/pdf/reporte_factura.php?tipo="+tipo+"&documento="+documento,"_blank");
})

$(".tablaBoletas").on("click", ".btnImprimirBoleta", function () {
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");

    window.open("extensiones/tcpdf/pdf/reporte_boleta.php?tipo="+tipo+"&documento="+documento,"_blank");
})

$(".tablaProformas").on("click", ".btnImprimirProforma", function () {
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");

    window.open("extensiones/tcpdf/pdf/reporte_proforma.php?tipo="+tipo+"&documento="+documento,"_blank");
})

/*=============================================
RANGO DE FECHAS FACTURA
=============================================*/


$("#daterange-btnFactura").daterangepicker(
    {
      cancelClass: "CancelarFactura",
      locale:{
		"daysOfWeek": [
			"Dom",
			"Lun",
			"Mar",
			"Mie",
			"Jue",
			"Vie",
			"Sab"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
	  },
      ranges: {
        Hoy: [moment(), moment()],
        Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Últimos 7 días": [moment().subtract(6, "days"), moment()],
        "Últimos 30 días": [moment().subtract(29, "days"), moment()],
        "Este mes": [moment().startOf("month"), moment().endOf("month")],
        "Último mes": [
          moment()
            .subtract(1, "month")
            .startOf("month"),
          moment()
            .subtract(1, "month")
            .endOf("month")
        ]
      },
      
      startDate: moment(),
      endDate: moment()
    },
    function(start, end) {
      $("#daterange-btnFactura span").html(
        start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
      );
  
      var fechaInicial = start.format("YYYY-MM-DD");
  
      var fechaFinal = end.format("YYYY-MM-DD");
  
      var capturarRango24 = $("#daterange-btnFactura span").html();
  
	  localStorage.setItem("capturarRango24", capturarRango24);
      localStorage.setItem("fechaInicial", fechaInicial);
	  localStorage.setItem("fechaFinal", fechaFinal);
	  
      // Recargamos la tabla con la información para ser mostrada en la tabla
      $(".tablaFacturas").DataTable().destroy();
      cargarTablaFactura(fechaInicial, fechaFinal);
    });
  
  /*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/
  
  $(".daterangepicker.opensleft .range_inputs .CancelarFactura").on(
    "click",
    function() {
      localStorage.removeItem("capturarRango24");
      localStorage.removeItem("fechaInicial");
      localStorage.removeItem("fechaFinal");
      localStorage.clear();
      window.location = "facturas";
    }
  );
  
  /*=============================================
  CAPTURAR HOY
  =============================================*/
  
  $(".daterangepicker.opensleft .ranges li").on("click", function() {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();
    if(ruta == "facturas"){

        if (textoHoy == "Hoy") {
        var d = new Date();
    
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        var año = d.getFullYear();
    
        dia = ("0" + dia).slice(-2);
        mes = ("0" + mes).slice(-2);
    
        var fechaInicial = año + "-" + mes + "-" + dia;
        var fechaFinal = año + "-" + mes + "-" + dia;
        localStorage.setItem("capturarRango24", "Hoy");
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaFacturas").DataTable().destroy();
        cargarTablaFactura(fechaInicial, fechaFinal);
        }
    }
  });



  /*=============================================
RANGO DE FECHAS BOLETAS
=============================================*/


$("#daterange-btnBoleta").daterangepicker(
    {
      cancelClass: "CancelarBoleta",
      locale:{
		"daysOfWeek": [
			"Dom",
			"Lun",
			"Mar",
			"Mie",
			"Jue",
			"Vie",
			"Sab"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
	  },
      ranges: {
        Hoy: [moment(), moment()],
        Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Últimos 7 días": [moment().subtract(6, "days"), moment()],
        "Últimos 30 días": [moment().subtract(29, "days"), moment()],
        "Este mes": [moment().startOf("month"), moment().endOf("month")],
        "Último mes": [
          moment()
            .subtract(1, "month")
            .startOf("month"),
          moment()
            .subtract(1, "month")
            .endOf("month")
        ]
      },
      
      startDate: moment(),
      endDate: moment()
    },
    function(start, end) {
      $("#daterange-btnBoleta span").html(
        start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
      );
  
      var fechaInicial = start.format("YYYY-MM-DD");
  
      var fechaFinal = end.format("YYYY-MM-DD");
  
      var capturarRango25 = $("#daterange-btnBoleta span").html();
  
	  localStorage.setItem("capturarRango25", capturarRango25);
      localStorage.setItem("fechaInicial", fechaInicial);
	  localStorage.setItem("fechaFinal", fechaFinal);
	  
      // Recargamos la tabla con la información para ser mostrada en la tabla
      $(".tablaBoletas").DataTable().destroy();
      cargarTablaBoleta(fechaInicial, fechaFinal);
    });
  
  /*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/
  
  $(".daterangepicker.opensleft .range_inputs .CancelarBoleta").on(
    "click",
    function() {
      localStorage.removeItem("capturarRango25");
      localStorage.removeItem("fechaInicial");
      localStorage.removeItem("fechaFinal");
      localStorage.clear();
      window.location = "boletas";
    }
  );
  
  /*=============================================
  CAPTURAR HOY
  =============================================*/
  
  $(".daterangepicker.opensleft .ranges li").on("click", function() {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if(ruta == "boletas"){
        if (textoHoy == "Hoy") {
        var d = new Date();
    
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        var año = d.getFullYear();
    
        dia = ("0" + dia).slice(-2);
        mes = ("0" + mes).slice(-2);
    
        var fechaInicial = año + "-" + mes + "-" + dia;
        var fechaFinal = año + "-" + mes + "-" + dia;
        localStorage.setItem("capturarRango25", "Hoy");
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaBoletas").DataTable().destroy();
        cargarTablaBoleta(fechaInicial, fechaFinal);
        }
    }
  });


  /*=============================================
RANGO DE FECHAS PROFORMAS
=============================================*/


$("#daterange-btnProforma").daterangepicker(
    {
      cancelClass: "CancelarProforma",
      locale:{
		"daysOfWeek": [
			"Dom",
			"Lun",
			"Mar",
			"Mie",
			"Jue",
			"Vie",
			"Sab"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
	  },
      ranges: {
        Hoy: [moment(), moment()],
        Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Últimos 7 días": [moment().subtract(6, "days"), moment()],
        "Últimos 30 días": [moment().subtract(29, "days"), moment()],
        "Este mes": [moment().startOf("month"), moment().endOf("month")],
        "Último mes": [
          moment()
            .subtract(1, "month")
            .startOf("month"),
          moment()
            .subtract(1, "month")
            .endOf("month")
        ]
      },
      
      startDate: moment(),
      endDate: moment()
    },
    function(start, end) {
      $("#daterange-btnProforma span").html(
        start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
      );
  
      var fechaInicial = start.format("YYYY-MM-DD");
  
      var fechaFinal = end.format("YYYY-MM-DD");
  
      var capturarRango26 = $("#daterange-btnProforma span").html();
  
	  localStorage.setItem("capturarRango26", capturarRango26);
      localStorage.setItem("fechaInicial", fechaInicial);
	  localStorage.setItem("fechaFinal", fechaFinal);
	  
      // Recargamos la tabla con la información para ser mostrada en la tabla
      $(".tablaProformas").DataTable().destroy();
      cargarTablaProforma(fechaInicial, fechaFinal);
    });
  
  /*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/
  
  $(".daterangepicker.opensleft .range_inputs .CancelarProforma").on(
    "click",
    function() {
      localStorage.removeItem("capturarRango26");
      localStorage.removeItem("fechaInicial");
      localStorage.removeItem("fechaFinal");
      localStorage.clear();
      window.location = "proformas";
    }
  );
  
  /*=============================================
  CAPTURAR HOY
  =============================================*/
  
  $(".daterangepicker.opensleft .ranges li").on("click", function() {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if(ruta == "proformas"){
  
        if (textoHoy == "Hoy") {
        var d = new Date();
    
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        var año = d.getFullYear();
    
        dia = ("0" + dia).slice(-2);
        mes = ("0" + mes).slice(-2);
    
        var fechaInicial = año + "-" + mes + "-" + dia;
        var fechaFinal = año + "-" + mes + "-" + dia;
        localStorage.setItem("capturarRango26", "Hoy");
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaProformas").DataTable().destroy();
        cargarTablaProforma(fechaInicial, fechaFinal);
        }
    }
  });

  // GENERAR REPORTE POR RADIO BUTTON DE TIPO VENTA
  $(".box").on("change", ".radioTipoV", function () {
    var optipo = $(this).val();
    $(".btnGenerarReporteVenta").attr("optipo",optipo);
  
  })

  //GENERAR REPORTE POR RADIO BUTTON DE DOCUMENTO DE VENTA
  $(".box").on("change", ".radioDocumento", function () {
    var opdocumento = $(this).val();
    $(".btnGenerarReporteVenta").attr("opdocumento",opdocumento);
  
  })

  //GENERAR REPORTE POR RADIO BUTTON DE IMPUESTO VENTA
  $(".box").on("change", ".radioImpuesto", function () {
    if(this.checked == false){
		$(".btnGenerarReporteVenta").attr("impuesto",'0');
	}else{
		$(".btnGenerarReporteVenta").attr("impuesto",'1');
	}
    
  })

  //GENERAR REPORTE POR SELECT DE VENDEDOR VENTA
  $(".box").on("change", "#tipoVendedorReporteVenta", function () {
    var vend = $(this).val();
    $(".btnGenerarReporteVenta").attr("vend",vend);
  
  })

  //GENERAR REPORTE POR FECHA DE INICIO DE VENTA
  $(".box").on("change", "#fechaVentaInicio", function () {
    var inicio = $(this).val();
    $(".btnGenerarReporteVenta").attr("inicio",inicio);
  
  })
  
  //GENERAR REPORTE POR FECHA FINAL DE VENTA
  $(".box").on("change", "#fechaVentaFin", function () {
    var fin = $(this).val();
    $(".btnGenerarReporteVenta").attr("fin",fin);
  
  })

  //GENERAR REPORTE POR RADIO BUTTON DE IMPRESION DE VENTA
  $(".box").on("change", ".radioImpresionV", function () {
    var impresion = $(this).val();
    $(".btnGenerarReporteVenta").attr("impresion",impresion);
  
  })


  $(".btnGenerarReporteVenta").click(function(){ 
    var optipo = $(this).attr("optipo");
    var opdocumento = $(this).attr("opdocumento");
    var impuesto = $(this).attr("impuesto");
    var vend = $(this).attr("vend");
    var inicio = $(this).attr("inicio");
    var fin = $(this).attr("fin");
    var impresion = $(this).attr("impresion");
    if(impresion == "pantalla"){

       if(optipo == "resumen"){

        window.open("extensiones/tcpdf/pdf/reporte_resumen_venta.php?optipo="+optipo+"&opdocumento="+opdocumento+"&impuesto="+impuesto+"&vend="+vend+"&inicio="+inicio+"&fin="+fin,"_blank");

       }else if (optipo == "detallado"){

        window.open("extensiones/tcpdf/pdf/reporte_detalle_venta.php?optipo="+optipo+"&opdocumento="+opdocumento+"&impuesto="+impuesto+"&vend="+vend+"&inicio="+inicio+"&fin="+fin,"_blank");
       
        }else if(optipo == "postalResumen"){
        
        window.open("extensiones/tcpdf/pdf/reporte_postalrsm_venta.php?optipo="+optipo+"&opdocumento="+opdocumento+"&impuesto="+impuesto+"&vend="+vend+"&inicio="+inicio+"&fin="+fin,"_blank")

       }else if(optipo == "postalDetalle"){

        window.open("extensiones/tcpdf/pdf/reporte_postaldet_venta.php?optipo="+optipo+"&opdocumento="+opdocumento+"&impuesto="+impuesto+"&vend="+vend+"&inicio="+inicio+"&fin="+fin,"_blank");

       }
     
    }else{

        window.location = "vistas/reportes_excel/reporte_ventas.xlsx";

    }
     
})

//TABLA DE PROCESAR COMPROBANTES ELECTRONICOS

/*
* cargamos la tabla para PROFORMAS
*/
if (localStorage.getItem("capturarRango34") != null) {
	$("#daterange-btnProcesarCE span").html(localStorage.getItem("capturarRango34"));
    if(localStorage.getItem("tipoCE")  != null ){
        $("#selectDocumentoCE").val(localStorage.getItem("tipoCE"));
        $("#selectDocumentoCE").selectpicker("refresh");
        cargarTablaProcesarCE(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"),localStorage.getItem("tipoCE"));
    }else{
        $("#selectDocumentoCE").val("S03");
        $("#selectDocumentoCE").selectpicker("refresh");
        cargarTablaProcesarCE(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"),'S03');
    }
	
} else {
	$("#daterange-btnProcesarCE span").html('<i class="fa fa-calendar"></i> Rango de Fecha ');
    if(localStorage.getItem("tipoCE") != null){
        $("#selectDocumentoCE").val(localStorage.getItem("tipoCE"));
        $("#selectDocumentoCE").selectpicker("refresh");
        cargarTablaProcesarCE(null, null,localStorage.getItem("tipoCE"));
    }else{
        $("#selectDocumentoCE").val("S03");
        $("#selectDocumentoCE").selectpicker("refresh");
        cargarTablaProcesarCE(null, null, 'S03');
    }
	
}

function cargarTablaProcesarCE(fechaInicial,fechaFinal,tipo) {
$(".tablaProcesarCE").DataTable({
    ajax: "ajax/facturacion/tabla-procesarce.ajax.php?perfil="+$("#perfilOculto").val() +"&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal+"&tipo="+tipo,
    deferRender: true,
    retrieve: true,
    processing: true,
    "order": [[6, "desc"]],
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
    }
});
}

$("#selectDocumentoCE").change(function(){
	$(".tablaProcesarCE").DataTable().destroy();
	var tipoCE=$(this).val();
	localStorage.setItem("tipoCE", tipoCE);
	cargarTablaProcesarCE(localStorage.getItem("fechaInicial"), localStorage.getItem("fechaFinal"),localStorage.getItem("tipoCE"));
	
});

/*=============================================
RANGO DE FECHAS PROCESAR COMPROBANTE ELECTRONICO
=============================================*/


$("#daterange-btnProcesarCE").daterangepicker(
    {
      cancelClass: "CancelarProcesoCE",
      locale:{
		"daysOfWeek": [
			"Dom",
			"Lun",
			"Mar",
			"Mie",
			"Jue",
			"Vie",
			"Sab"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
	  },
      ranges: {
        Hoy: [moment(), moment()],
        Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Últimos 7 días": [moment().subtract(6, "days"), moment()],
        "Últimos 30 días": [moment().subtract(29, "days"), moment()],
        "Este mes": [moment().startOf("month"), moment().endOf("month")],
        "Último mes": [
          moment()
            .subtract(1, "month")
            .startOf("month"),
          moment()
            .subtract(1, "month")
            .endOf("month")
        ]
      },
      
      startDate: moment(),
      endDate: moment()
    },
    function(start, end) {
      $("#daterange-btnProcesarCE span").html(
        start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
      );
  
      var fechaInicial = start.format("YYYY-MM-DD");
  
      var fechaFinal = end.format("YYYY-MM-DD");
  
      var capturarRango34 = $("#daterange-btnProcesarCE span").html();
  
	  localStorage.setItem("capturarRango34", capturarRango34);
      localStorage.setItem("fechaInicial", fechaInicial);
	  localStorage.setItem("fechaFinal", fechaFinal);
	  
      // Recargamos la tabla con la información para ser mostrada en la tabla
      $(".tablaProcesarCE").DataTable().destroy();
      if(localStorage.getItem("tipoCE") != null){

        cargarTablaProcesarCE(fechaInicial,fechaFinal,localStorage.getItem("tipoCE"))

      }else{

        cargarTablaProcesarCE(fechaInicial, fechaFinal,'S03');

      }
      
    });
  
  /*=============================================
  CANCELAR RANGO DE FECHAS
  =============================================*/
  
  $(".daterangepicker.opensleft .range_inputs .CancelarProcesoCE").on(
    "click",
    function() {
      localStorage.removeItem("capturarRango34");
      localStorage.removeItem("fechaInicial");
      localStorage.removeItem("fechaFinal");
      localStorage.clear();
      window.location = "procesar-ce";
    }
  );
  
  /*=============================================
  CAPTURAR HOY
  =============================================*/
  
  $(".daterangepicker.opensleft .ranges li").on("click", function() {
    var textoHoy = $(this).attr("data-range-key");
    var ruta = $("#rutaAcceso").val();

    if(ruta == "procesar-ce"){
  
        if (textoHoy == "Hoy") {
        var d = new Date();
    
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        var año = d.getFullYear();
    
        dia = ("0" + dia).slice(-2);
        mes = ("0" + mes).slice(-2);
    
        var fechaInicial = año + "-" + mes + "-" + dia;
        var fechaFinal = año + "-" + mes + "-" + dia;
        localStorage.setItem("capturarRango34", "Hoy");
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);
        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaProcesarCE").DataTable().destroy();
        if(localStorage.getItem("tipoCE") != null){

            cargarTablaProcesarCE(fechaInicial,fechaFinal,localStorage.getItem("tipoCE"))

        }else{

            cargarTablaProcesarCE(fechaInicial,fechaFinal,'S03')

        }
        
        }
    }
  });

  $(".tablaProcesarCE").on("click","button.btnGenerarXMLCE",function(){
    var tipo = $(this).attr("tipo");
    var documento = $(this).attr("documento");

    // console.log(tipo);
    // console.log(documento);

    //VALIDAMOS SI ES FACTURA, BOLETA, NOTA DE CREDITO O DEBITO
    /* if(tipo == 'S03'){

        window.location = "index.php?ruta=procesar-ce&tipoFact="+tipo+"&documentoFact="+documento;

    }else if(tipo == 'S02'){

        window.location = "index.php?ruta=procesar-ce&tipoFact="+tipo+"&documentoFact="+documento;

    }else if(tipo == 'E05'){

        window.location = "index.php?ruta=procesar-ce&tipoNotaCred="+tipo+"&documentoNotaCred="+documento;

    }else{

      window.location = "index.php?ruta=procesar-ce&tipoNotaDeb="+tipo+"&documentoNotaDeb="+documento;

    } */

    //window.location.href  = "vistas/reportes_excel/fe_factura.php?tipo=" + tipo +"&documento=" + documento;
    
    var datos = new FormData();

    datos.append("tipo",tipo);
    datos.append("documento",documento);

    $.ajax({

      url:"ajax/facturacion.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType:false,
      processData:false,
      success:function(respuesta){

        //console.log(respuesta);

        if(respuesta == "ok"){
          Command: toastr["success"]("Se genero el CSV");
        }
      }
    })

    //window.location  = "index.php?ruta=procesar-ce&tipo=" + tipo +"&documento=" + documento;

  });



  $("#formularioToken").on("click","button.btnGenerarToken",function(){
    var envio = "enviando";

    var datos = new FormData();
    datos.append("envioToken",envio);

  
    $.ajax({

      url:"ajax/facturacion.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType:"json",
      success:function(respuesta){
        if(respuesta){
          var token = respuesta["access_token"];
          $("#nuevoCodigoToken").val(token);
          var inicio = $("#nuevoInicio").val();
          var fin = $("#nuevoFin").val();
          var fecha = $("#nuevaFechaToken").val();
          var datos2 = new FormData();
          // console.log(token);
          datos2.append("guardarToken",token);
          datos2.append("tiempoToken",inicio+" "+fin+" "+fecha);
          $.ajax({

            url:"ajax/facturacion.ajax.php",
            method: "POST",
            data: datos2,
            cache: false,
            contentType:false,
            processData:false,
            success:function(respuesta2){
              // console.log(respuesta2);
              if(respuesta2 == "ok"){
                Command: toastr["success"]("Token registrado exitosamente!");
                //asignados el tiempo de duracion luego de generar el token
                $("#nuevaDuracion").val(fecha +" desde "+ inicio + " hasta "+ fin);
              }
            }
          })
        }

      }
    })

  })

$("#formularioConsultaSunat").on("keyup","input#nuevaSerieConsulta",function(){
  var serie = $(this).val();
  // console.log(serie);
  var primer = serie.substring(0,1);

  // console.log(serie.length);
  if(serie.length == 0){
    $("#nuevoMontoConsulta").attr("readonly",false);
  }else{
    if(primer == 0 || primer == 1){
      $("#nuevoMontoConsulta").attr("readonly",true);
    }else {
      $("#nuevoMontoConsulta").attr("readonly",false);
    }
  }
  
})

$("#formularioConsultaSunat").on("click","button.btnConsultarSunat",function(){
  
  var tipo = $("#selectDocumentoConsulta").val();
  var ruc = $("#nuevoRucConsulta").val();
  var serie = $("#nuevaSerieConsulta").val();
  var correlativo = $("#nuevoCorrelativoConsulta").val();
  var emision = $("#nuevaEmisionConsulta").val();
  var monto = $("#nuevoMontoConsulta").val();

  var datos = new FormData();
  datos.append("tipoConsulta",tipo);
  datos.append("rucConsulta",ruc);
  datos.append("serieConsulta",serie.toUpperCase());
  datos.append("correlativoConsulta",correlativo);
  datos.append("emisionConsulta",emision);
  datos.append("montoConsulta",monto);

  $(".loadingSunat").html('<div class="alert" role="alert" style="background:#EAF2F8"><img src="vistas/img/gif/loader.gif" width="60px"/>Procesando, por favor espere...</div>');
  $.ajax({

    url:"ajax/facturacion.ajax.php",
    method: "POST",
    data: datos,
    cache: false,
    contentType: false,
    processData: false,
    dataType:"json",
    success:function(respuesta){
      $(".loadingSunat").addClass("hidden");
      if(respuesta["success"] == true){
      
          
          if(respuesta["data"]["estadoCp"] == "1"){

            var msjComp = "ACEPTADO";  

          }else if(respuesta["data"]["estadoCp"] == "3"){

            var msjComp = "AUTORIZADO";
            
          }else if(respuesta["data"]["estadoCp"] == "2"){

            var msjComp = "ANULADO";
          
          }else{
            
            var msjComp = "NO EXISTE";

          }

          if(respuesta["data"]["estadoRuc"] == "00"){

            var msjCont = "ACTIVO";

          }else{

            var msjCont = "-";

          }

          if(respuesta["data"]["condDomiRuc"] == "00"){

            var msjDomi = "HABIDO";

          }else{

            var msjDomi = "-";

          }

          $(".consultaActivo").removeClass("hidden");
          $(".estComp").html(msjComp);
          $(".estContrib").html(msjCont);
          $(".estDomicilio").html(msjDomi);
  
      }else if(respuesta["message"] == "Unauthorized"){
        Command: toastr["error"]("Por favor, generar token!");
      }else{
        Command: toastr["error"]("Error al ingresar los campos requeridos!");
      }
      
    }
  })
  $(".loadingSunat").removeClass("hidden");

  $(".consultaActivo").addClass("hidden");
  // $(".consultaError").addClass("hidden");
})

$("#formularioConsultaSunat").on("click","button.btnLimpiarConsultaSunat",function(){
  $(".consultaActivo").addClass("hidden");
  $(".consultaError").addClass("hidden");

  $("#selectDocumentoConsulta").val("");
  $("#selectDocumentoConsulta").selectpicker("refresh");

  $("#nuevoRucConsulta").val("");
  $("#nuevaSerieConsulta").val("");
  $("#nuevoCorrelativoConsulta").val("");
  $("#nuevaEmisionConsulta").val("");
  $("#nuevoMontoConsulta").val("");
})

$(".btnNuevaConsultaSunat").click(function(){
  $(".consultaActivo").addClass("hidden");
  $(".consultaError").addClass("hidden");

  $("#nuevoRucConsulta").val("");
  $("#selectDocumentoConsulta").val("");
  $("#selectDocumentoConsulta").selectpicker("refresh");

  $("#nuevaSerieConsulta").val("");
  $("#nuevoCorrelativoConsulta").val("");
  $("#nuevaEmisionConsulta").val("");
  $("#nuevoMontoConsulta").val("");
})

$(".btnVerToken").click(function(){
  var verToken = "viendo";

  var datos = new FormData();
  datos.append("verToken",verToken);
  
  $.ajax({

    url:"ajax/facturacion.ajax.php",
    method: "POST",
    data: datos,
    cache: false,
    contentType: false,
    processData: false,
    dataType:"json",
    success:function(respuesta){
      // console.log(respuesta);
      $("#nuevoCodigoToken").val(respuesta["token"]);
      var  descripcion = respuesta["descripcion"]
      var inicio = descripcion.substring(0,8);
      var fin = descripcion.substring(9,17);
      var fecha = descripcion.substring(18,28);
      $("#nuevaDuracion").val(fecha +" desde "+ inicio + " hasta "+ fin);
    }

  })

})

$(".tablaProcesarCE").on("click","button.btnConsultarEstado",function(){

  var tipo = $(this).attr("tipo");
  var ruc = "20513613939";
  var documento = $(this).attr("documento");
  var serie = documento.substring(0,4);
  var correlativo = documento.substring(4,12);
  var emision = $(this).attr("fecha");
  var monto = $(this).attr("monto");
  
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
      // console.log(respuesta);
      if(respuesta["success"] == true){
          
        if(respuesta["data"]["estadoCp"] == "1"){
				
					var msjComp = "ACEPTADO";
					var tipoMsjComp = "success";

				}else if(respuesta["data"]["estadoCp"] == "3"){

					var msjComp = "AUTORIZADO";
					var tipoMsjComp = "success";
				
				}else if(respuesta["data"]["estadoCp"] == "2"){
			
					var msjComp = "ANULADO";
					var tipoMsjComp = "error";

				}else{
					
					var msjComp = "NO EXISTE";
					var tipoMsjComp = "error";
				}

				if(respuesta["data"]["estadoRuc"] == "00"){
				
					var msjCont = "ACTIVO";
					var tipoMsjCont = "success";

				}else{
				
					var msjCont = "-";
					var tipoMsjCont = "error";

				}

				if(respuesta["data"]["condDomiRuc"] == "00"){
					
					var msjDomi = "HABIDO";
					var tipoMsjDomi = "success";
				
				}else{
					
					var msjDomi = "-";
					var tipoMsjDomi = "error";

				}

          Command: toastr[tipoMsjComp]("Estado del comprobante : "+msjComp);
          Command: toastr[tipoMsjCont]("Estado del contribuyente : "+msjCont);
          Command: toastr[tipoMsjDomi]("Condicion de domicilio : "+msjDomi);
  
      }else if(respuesta["message"] == "Unauthorized"){
        Command: toastr["error"]("Por favor, generar token!");
      }else{
        Command: toastr["error"]("Error al ingresar los campos requeridos!");
      }
      
    }
  })
});
