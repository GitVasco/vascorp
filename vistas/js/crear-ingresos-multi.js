/**
 * Vista crear-ingresos-multi: tabla servida por * ajax/produccion/tabla-articulostaller-ingresos-multi.ajax.php
 */
(function () {
    if (!window.vistaCrearIngresosMulti) {
        return;
    }

    var perfilOcultoSel = "#perfilOculto";

    function quitarAgregarArticuloTMulti() {
        var filasEnFormulario = $(".formularioIngresoMulti .quitarTaller");
        var botonesTablaIngreso = $(
            ".tablaArticulosTalleresMulti tbody button.agregarArtiTaller"
        );

        for (var i = 0; i < filasEnFormulario.length; i++) {
            var filaKey = $(filasEnFormulario[i]).attr("data-quitar-fila-key");
            if (!filaKey) {
                continue;
            }
            for (var j = 0; j < botonesTablaIngreso.length; j++) {
                if (
                    $(botonesTablaIngreso[j]).attr("data-fila-ingreso-key") ===
                    filaKey
                ) {
                    $(botonesTablaIngreso[j])
                        .removeClass("btn-primary agregarArtiTaller")
                        .addClass("btn-default");
                }
            }
        }
    }

    window.cargarTablaArticulosTalleresMulti = function () {
        var $tabla = $(".tablaArticulosTalleresMulti");
        var $estado = $("#ingresosMultiTablaEstado");

        if (!$tabla.length) {
            return;
        }

        if ($.fn.DataTable.isDataTable($tabla)) {
            $tabla.DataTable().destroy();
        }

        $estado.text("Cargando datos…");

        $tabla.DataTable({
            ajax: {
                url: "ajax/produccion/tabla-articulostaller-ingresos-multi.ajax.php",
                type: "GET",
                data: function (d) {
                    d.perfil = $(perfilOcultoSel).val() || "";
                    d.alcance =
                        $("#alcanceProcesoCabeceraMulti").val() || "externos";
                },
                dataSrc: "data",
                error: function () {
                    $estado.text(
                        "Error al cargar la tabla. Revise la consola o recargue."
                    );
                },
            },
            deferRender: true,
            retrieve: true,
            processing: true,
            order: [
                [1, "asc"],
                [2, "asc"],
            ],
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
            },
            initComplete: function () {
                var n = this.api().rows().count();
                $estado.text(n + " filas cargadas");
            },
        });
    };

    function tallerRepresentanteAlcanceCodigo() {
        var alc = $("#alcanceProcesoCabeceraMulti").val() || "externos";
        if (alc === "internos") {
            return $("#ingresosMultiRep").attr("data-taller-interno") || "T1";
        }
        return $("#ingresosMultiRep").attr("data-taller-externo") || "T0";
    }

    function aplicarCabeceraProcesoMulti() {
        var cod = tallerRepresentanteAlcanceCodigo();
        $("#nuevoTalleres").val(cod);
        localStorage.setItem("sectorIngreso", cod);
        $("#nuevoTalleres").trigger("change");
        if (typeof window.cargarTablaArticulosTalleresMulti === "function") {
            window.cargarTablaArticulosTalleresMulti();
        }
    }

    $(function () {
        if (!window.vistaCrearIngresosMulti) {
            return;
        }
        if ($("#alcanceProcesoCabeceraMulti").length) {
            aplicarCabeceraProcesoMulti();
            $("#alcanceProcesoCabeceraMulti").on(
                "change",
                aplicarCabeceraProcesoMulti
            );
        }
    });

    $(".tablaArticulosTalleresMulti").on("draw.dt", function () {
        if (localStorage.getItem("quitarTallerMulti") != null) {
            var lista = JSON.parse(
                localStorage.getItem("quitarTallerMulti") || "[]"
            );
            for (var i = 0; i < lista.length; i++) {
                var fk = lista[i].filaKey;
                $(
                    "button.recuperarBoton[data-fila-ingreso-key='" + fk + "']"
                )
                    .removeClass("btn-default")
                    .addClass("btn-primary agregarArtiTaller");
            }
        }
        quitarAgregarArticuloTMulti();
    });

    $(".tablaArticulosTalleresMulti tbody").on(
        "click",
        "button.agregarArtiTaller",
        function () {
            var articuloIngreso = $(this).attr("articulo");
            var tallerQty = $(this).attr("taller");
            var idCierre = $(this).attr("idCierre") || "";
            var codSector = $(this).attr("data-sector-cod") || "";
            var sectorConsulta = $(this).attr("data-sector-consulta") || "";
            var filaKey = $(this).attr("data-fila-ingreso-key");
            var procesoCod = $(this).attr("data-proceso") || "externo";
            var procesoEtq = procesoCod === "interno" ? "Interno" : "Externo";

            $(this).removeClass("btn-primary agregarArtiTaller").addClass(
                "btn-default"
            );

            var datos = new FormData();
            datos.append("articuloT", articuloIngreso);

            $.ajax({
                url: "ajax/articulos.ajax.php",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta) {
                    var articulo = respuesta["articulo"];
                    var packing = respuesta["packingB"];
                    var taller = respuesta["taller"];
                    var sufijoSector =
                        codSector !== ""
                            ? " <small class='text-muted'>(" +
                              codSector +
                              " · " +
                              procesoEtq +
                              ")</small>"
                            : "";

                    if (idCierre == "") {
                        $(".nuevoArticuloIngreso").append(
                            `
 <div class="row munditoIngreso" style="padding:5px 15px">
                        <div class="col-xs-6" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${articuloIngreso}" data-quitar-fila-key="${filaKey}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${articuloIngreso}" name="agregarT" value="${packing}${sufijoSector}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${tallerQty}" articulo="${articulo}" nuevoTaller="${tallerQty}" data-cod-sector="${codSector}" data-sector-consulta="${sectorConsulta}" data-proceso="${procesoCod}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" value="${taller}" readonly>
                        </div>
                        <div class="col-xs-2 divCorte">
                            <input type="text" class="form-control nuevoCorteIngreso input-sm" name="nuevoCorteIngreso" value="">
                        </div>
                    </div>`
                        );
                    } else {
                        $(".nuevoArticuloIngreso").append(
                            `
                    <div class="row munditoIngreso" style="padding:5px 15px">
                        <div class="col-xs-6" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${idCierre}" data-quitar-fila-key="${filaKey}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${idCierre}" name="agregarT" value="${packing}${sufijoSector}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${tallerQty}" articulo="${articulo}" nuevoTaller="${tallerQty}" data-cod-sector="${codSector}" data-sector-consulta="${sectorConsulta}" data-proceso="${procesoCod}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" value="${tallerQty}" readonly>
                        </div>
                        <div class="col-xs-2 divCorte">
                            <input type="text" class="form-control nuevoCorteIngreso input-sm" name="nuevoCorteIngreso" value="">
                        </div>
                    </div>`
                        );
                    }

                    $(".nuevoArticuloIngreso")
                        .find(".nuevaCantidadArticuloIngreso")
                        .last()
                        .focus(function () {
                            $(this).select();
                        });

                    sumarTotalIngreso();
                    listarArticulosIngreso();
                },
            });
        }
    );

    localStorage.removeItem("quitarTallerMulti");

    $(".formularioIngresoMulti").on("click", "button.quitarTaller", function () {
        if (!$(this).attr("data-quitar-fila-key")) {
            return;
        }
        $(this).parent().parent().parent().parent().remove();
        var fk = $(this).attr("data-quitar-fila-key");

        var idQuitar = [];
        if (localStorage.getItem("quitarTallerMulti") != null) {
            idQuitar = JSON.parse(localStorage.getItem("quitarTallerMulti"));
        }
        idQuitar.push({ filaKey: fk });
        localStorage.setItem(
            "quitarTallerMulti",
            JSON.stringify(idQuitar)
        );

        $(
            "button.recuperarBoton[data-fila-ingreso-key='" + fk + "']"
        )
            .removeClass("btn-default")
            .addClass("btn-primary agregarArtiTaller");

        if ($(".nuevoArticuloIngreso").children().length == 0) {
            $("#nuevoTotalTaller").val(0);
            $("#totalTaller").val(0);
            $("#nuevoTotalTaller").attr("total", 0);
        } else {
            sumarTotalIngreso();
            listarArticulosIngreso();
        }
    });
})();
