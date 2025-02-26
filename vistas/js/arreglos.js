if (localStorage.getItem("capturarRangoArreglo") != null) {
    $("#daterange-btnArreglos span").html(
        localStorage.getItem("capturarRangoArreglo")
    );
    cargarTablaArreglos(
        localStorage.getItem("fechaInicial"),
        localStorage.getItem("fechaFinal")
    );
} else {
    $("#daterange-btnArreglos span").html(
        '<i class="fa fa-calendar"></i> Rango de Fecha '
    );
    cargarTablaArreglos(null, null);
}

function cargarTablaArreglos(fechaInicial, fechaFinal) {
    $(".tablaArreglos").DataTable({
        ajax:
            "ajax/produccion/tabla-arreglos.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&fechaInicial=" +
            fechaInicial +
            "&fechaFinal=" +
            fechaFinal,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[8, "desc"]],
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
}

$("#daterange-btnArreglos").daterangepicker(
    {
        cancelClass: "CancelarArreglos",
        locale: {
            daysOfWeek: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
            monthNames: [
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
                "Diciembre",
            ],
        },
        ranges: {
            Hoy: [moment(), moment()],
            Ayer: [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "Últimos 7 días": [moment().subtract(6, "days"), moment()],
            "Últimos 30 días": [moment().subtract(29, "days"), moment()],
            "Este mes": [moment().startOf("month"), moment().endOf("month")],
            "Último mes": [
                moment().subtract(1, "month").startOf("month"),
                moment().subtract(1, "month").endOf("month"),
            ],
        },

        startDate: moment(),
        endDate: moment(),
    },
    function (start, end) {
        $("#daterange-btnArreglos span").html(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
        );

        var fechaInicial = start.format("YYYY-MM-DD");

        var fechaFinal = end.format("YYYY-MM-DD");

        var capturarRangoArreglo = $("#daterange-btnArreglos span").html();

        localStorage.setItem("capturarRangoArreglo", capturarRangoArreglo);
        localStorage.setItem("fechaInicial", fechaInicial);
        localStorage.setItem("fechaFinal", fechaFinal);

        // Recargamos la tabla con la información para ser mostrada en la tabla
        $(".tablaArreglos").DataTable().destroy();
        cargarTablaArreglos(fechaInicial, fechaFinal);
    }
);

$("#nuevoTalleresCrearArreglos").change(function () {
    var ingreso = $(this).val();
    $(".tablaArticulosTalleres").DataTable().destroy();
    localStorage.setItem("sectorIngreso", ingreso);
    cargarTablaArticuloTalleres(localStorage.getItem("sectorIngreso"));
    var datos = new FormData();
    datos.append("idSector", ingreso);

    $.ajax({
        url: "ajax/sectores.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            //console.log("respuesta", respuesta);

            $("#nuevoTipoSector").val(respuesta["tipo"]);
        },
    });

    var random = Math.floor(Math.random() * 90000) + 10000;

    $("#nuevoCodigo").val("A" + ingreso + random);
});

// Tabla Arreglos pendientes
// if (localStorage.getItem("sectorArreglos") != null) {
//     cargarTablaArticuloArreglos(localStorage.getItem("sectorArreglos"));
// } else {
//     cargarTablaArticuloArreglos(null);
// }

function cargarTablaArticuloArreglos(sectorArreglos) {
    $(".tablaArticulosArreglos").DataTable({
        ajax:
            "ajax/produccion/tabla-articulosarreglos.ajax.php?perfil=" +
            $("#perfilOculto").val() +
            "&sectorArreglos=" +
            sectorArreglos,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[2, "asc"]],
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
            sInfo: "Mostrando del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Mostrando del 0 al 0 de un total de 0",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sInfoPostFix: "",
            sSearch: "Buscar:",
            sUrl: "",
            sInfoThousands: ",",
            sLoadingRecords: "Cargando...",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: ">>>",
                sPrevious: "<<<",
            },
            oAria: {
                sSortAscending:
                    ": Activar para ordenar la columna de manera ascendente",
                sSortDescending:
                    ": Activar para ordenar la columna de manera descendente",
            },
        },
    });
}

$("#nuevoTalleresA").change(function () {
    var arreglos = $(this).val();

    $(".tablaArticulosArreglos").DataTable().destroy();
    // localStorage.setItem("sectorArreglos", arreglos);
    cargarTablaArticuloArreglos(arreglos);

    var random = Math.floor(Math.random() * 90000) + 10000;

    $("#nuevoCodigoCe").val("A" + arreglos + random);
});

$(".tablaArticulosArreglos").on("click", ".agregarArtiArreglo", function () {
    var idArreglo = $(this).attr("idArreglo");

    $(this).removeClass("btn-primary agregarArtiArreglo");
    $(this).addClass("btn-default");

    var datos = new FormData();
    datos.append("articuloArreglo", idArreglo);

    $.ajax({
        url: "ajax/articulos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
            console.log("respuesta", respuesta);

            var articulo = respuesta["articulo"];
            var packing =
                respuesta["modelo"] +
                " - " +
                respuesta["color"] +
                " - " +
                respuesta["talla"];
            var taller = respuesta["pendiente"];

            // todo: AGREGAR LOS CAMPOS

            $(".nuevoArticuloArreglos").append(`
                    <div class="row" style="padding:5px 15px">
                        <!-- Descripción del Articulo -->
                        <div class="col-xs-6" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarArreglo" articuloArreglo="${idArreglo}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloArreglo="${idArreglo}" name="agregarT" value="${packing}" articulo="${articulo}" idArreglo="${idArreglo}" readonly required>
                            </div>
                        </div>
                        <!-- Cantidad de la Orden de Corte -->
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloArreglo input-sm" name="nuevaCantidadArticuloArreglo" id="nuevaCantidadArticuloArreglo" min="1" value="0" taller="${taller}" articulo="${articulo}" nuevoTaller="${taller}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <!-- saldo de la Orden de Corte -->
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" id="nuevoSaldoIngreso" value="${taller}" readonly>
                        </div>
                    </div>`);

            sumarTotalArreglosCierre();

            listarArticulosArreglos();
        },
    });
});

$(".tablaArticulosArreglos").on("draw.dt", function () {
    if (localStorage.getItem("quitarArreglo") != null) {
        var listaIdArticuloT = JSON.parse(
            localStorage.getItem("quitarArreglo")
        );

        for (var i = 0; i < listaIdArticuloT.length; i++) {
            $(
                "button.recuperarBoton[articuloArreglo='" +
                    listaIdArticuloT[i]["articuloArreglo"] +
                    "']"
            ).removeClass("btn-default");

            $(
                "button.recuperarBoton[articuloArreglo='" +
                    listaIdArticuloT[i]["articuloArreglo"] +
                    "']"
            ).addClass("btn-primary agregarArtiArreglo");
        }
    }
});

var idQuitarArticuloA = [];

localStorage.removeItem("quitarArreglo");

$(".formularioArreglos").on("click", "button.quitarArreglo", function () {
    $(this).parent().parent().parent().parent().remove();
    var articuloArreglo = $(this).attr("articuloArreglo");

    /*=============================================
    ALMACENAR EN EL LOCALSTORAGE EL ID DEL MATERIA PRIMA A QUITAR
    =============================================*/

    if (localStorage.getItem("quitarArreglo") == null) {
        idQuitarArticuloA = [];
    } else {
        idQuitarArticuloA.concat(localStorage.getItem("quitarArreglo"));
    }

    idQuitarArticuloA.push({
        articuloArreglo: articuloArreglo,
    });

    localStorage.setItem("quitarArreglo", JSON.stringify(idQuitarArticuloA));

    $(
        "button.recuperarBoton[articuloArreglo='" + articuloArreglo + "']"
    ).removeClass("btn-default");

    $(
        "button.recuperarBoton[articuloArreglo='" + articuloArreglo + "']"
    ).addClass("btn-primary agregarArtiArreglo");

    if ($(".nuevoArticuloArreglos").children().length == 0) {
        $("#nuevoTotalArreglosCierre").val(0);
        $("#totalArreglosCierre").val(0);
        $("#nuevoTotalArreglosCierre").attr("total", 0);
    } else {
        // SUMAR TOTAL DE UNIDADES
        // sumarTotalIngreso();
        // // AGREGAR IMPUESTO
        // // AGRUPAR PRODUCTOS EN FORMATO JSON
        // listarArticulosIngreso();
    }
});

$(".formularioArreglos").on(
    "change",
    "input.nuevaCantidadArticuloArreglo",
    function () {
        // necesito actualizar la cantidad de nuevoSaldoIngreso que es la difrerencia con nuevaCantidadArticuloArreglo
        var cantidad = $(this).val();
        var nuevoTaller = $(this).attr("nuevoTaller");
        var saldo = $(this).attr("taller");
        var articulo = $(this).attr("articulo");
        var nuevoSaldo = saldo - cantidad;

        //$(this).attr("nuevaCantidad", cantidad);

        $(this).parent().parent().find(".nuevoSaldoIngreso").val(nuevoSaldo);

        sumarTotalArreglosCierre();

        listarArticulosArreglos();
    }
);

function sumarTotalArreglosCierre() {
    var cantidadArreglo = $(".nuevaCantidadArticuloArreglo");

    var arraySumarCantidades = [];

    for (var i = 0; i < cantidadArreglo.length; i++) {
        arraySumarCantidades.push(Number($(cantidadArreglo[i]).val()));
    }
    /* console.log("arraySumarCantidades", arraySumarCantidades); */

    function sumaArrayCantidades(total, numero) {
        return total + numero;
    }

    var sumarTotal = arraySumarCantidades.reduce(sumaArrayCantidades);

    $("#nuevoTotalArreglosCierre").val(sumarTotal);
    $("#totalArreglosCierre").val(sumarTotal);
    $("#nuevoTotalArreglosCierre").attr("total", sumarTotal);
}

function listarArticulosArreglos() {
    var listaArticulos = [];

    var descripcion = $(".nuevaDescripcionProducto");

    var cantidad = $(".nuevaCantidadArticuloArreglo");

    let saldo = $(".nuevoSaldoIngreso");

    for (var i = 0; i < descripcion.length; i++) {
        listaArticulos.push({
            id: $(descripcion[i]).attr("articuloArreglo"),
            articulo: $(descripcion[i]).attr("articulo"),
            cantidad: $(cantidad[i]).val(),
            saldo: $(saldo[i]).val(),
        });
    }

    $("#listaArticulosArreglos").val(JSON.stringify(listaArticulos));
}

//#region Detalles
$(".tablaArreglos").on("click", ".btnVisualizarArreglos", function () {
    let codigoArreglo = $(this).attr("codigoArreglo");
    let guiaArreglo = $(this).attr("guiaArreglo");
    let fecha = $(this).attr("fecha");
    let total = $(this).attr("total");

    $("#arreglos").val(codigoArreglo);
    $("#guia").val(guiaArreglo);
    $("#fecha").val(fecha);
    $("#total").val(total);

    let datos = new FormData();
    datos.append("codigoArreglo", codigoArreglo);

    $.ajax({
        url: "ajax/arreglos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuestaDetalle) {
            console.log("🚀 ~ respuestaDetalle:", respuestaDetalle);

            // Limpiar el contenido del tbody antes de agregar nuevas filas
            $(".tablaDetalleArre tbody").empty();

            let previousModelo = "";
            respuestaDetalle.forEach((id) => {
                if (id.modelo !== previousModelo) {
                    $(".tablaDetalleArre tbody").append(`
                        <tr>
                            <td colspan="13" style="border-top:1px solid #000; font-weight:bold;"></td>
                        </tr>
                    `);
                    previousModelo = id.modelo;
                }

                const getValue = (val) => (val > 0 ? val : "");

                const t1 = getValue(id.t1);
                const t2 = getValue(id.t2);
                const t3 = getValue(id.t3);
                const t4 = getValue(id.t4);
                const t5 = getValue(id.t5);
                const t6 = getValue(id.t6);
                const t7 = getValue(id.t7);
                const t8 = getValue(id.t8);

                $(".tablaDetalleArre tbody").append(`
                    <tr class="detalleMP" style="border-bottom:1px solid #000;">
                        <td>${id.cod_sector} - ${id.nom_sector}</td>
                        <td>${id.codigo}</td>
                        <td><b>${id.modelo}</b></td>
                        <td>${id.nombre}</td>
                        <td>${id.color}</td>
                        <td><b>${t1}</b></td>
                        <td><b>${t2}</b></td>
                        <td><b>${t3}</b></td>
                        <td><b>${t4}</b></td>
                        <td><b>${t5}</b></td>
                        <td><b>${t6}</b></td>
                        <td><b>${t7}</b></td>
                        <td><b>${t8}</b></td>
                        <td><b>${id.total}</b></td>
                    </tr>
                `);
            });
        },
    });
});

// modificar el saldo
