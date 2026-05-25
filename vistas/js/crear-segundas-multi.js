/**
 * Vista crear-segundas-multi: tabla servida por ajax/produccion/tabla-articulostaller-ingresos-multi.ajax.php
 */
(function () {
    if (!window.vistaCrearSegundasMulti) {
        return;
    }

    var perfilOcultoSel = "#perfilOculto";

    function quitarAgregarArticuloTSegundaMulti() {
        var keysEnFormulario = {};
        $(".formularioSegundaMulti .quitarTaller").each(function () {
            var fk = $(this).attr("data-quitar-fila-key");
            if (fk) {
                keysEnFormulario[fk] = true;
            }
        });

        $(".tablaArticulosTalleresSegundaMulti tbody button.recuperarBoton").each(
            function () {
                var $b = $(this);
                var fk = $b.attr("data-fila-ingreso-key");
                if (fk && keysEnFormulario[fk]) {
                    $b.removeClass("btn-primary agregarArtiTaller").addClass(
                        "btn-default"
                    );
                }
            }
        );
    }

    function limpiarFormularioSegundaMulti() {
        var $cont = $(".nuevoArticuloIngreso");
        if ($cont.length) {
            $cont.find(".munditoIngreso").remove();
            $cont.find(".separadorIngresoMultiTaller").remove();
        }
        localStorage.removeItem("quitarTallerSegundaMulti");
        $("#listaArticulosIngreso").val("[]");
        $("#nuevoTotalTaller").val(0);
        $("#totalTaller").val(0);
        $("#nuevoTotalTaller").attr("total", 0);
        $(
            ".tablaArticulosTalleresSegundaMulti tbody button.recuperarBoton"
        )
            .removeClass("btn-default")
            .addClass("btn-primary agregarArtiTaller");
    }

    function restaurarBotonesAgregarDesdeQuitarSegundaMulti() {
        if (localStorage.getItem("quitarTallerSegundaMulti") == null) {
            return;
        }
        var lista = JSON.parse(localStorage.getItem("quitarTallerSegundaMulti"));
        for (var i = 0; i < lista.length; i++) {
            var fk = lista[i].filaKey;
            if (!fk) {
                continue;
            }
            $(
                ".tablaArticulosTalleresSegundaMulti tbody button.recuperarBoton"
            ).filter(function () {
                return $(this).attr("data-fila-ingreso-key") === fk;
            })
                .removeClass("btn-default")
                .addClass("btn-primary agregarArtiTaller");
        }
    }

    window.cargarTablaArticulosTalleresSegundaMulti = function () {
        var $tabla = $(".tablaArticulosTalleresSegundaMulti");
        var $estado = $("#segundasMultiTablaEstado");

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
                        $("#alcanceProcesoCabeceraSegundaMulti").val() || "externos";
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
            drawCallback: function () {
                restaurarBotonesAgregarDesdeQuitarSegundaMulti();
                quitarAgregarArticuloTSegundaMulti();
            },
        });
    };

    function tallerRepresentanteAlcanceCodigo() {
        var alc = $("#alcanceProcesoCabeceraSegundaMulti").val() || "externos";
        if (alc === "internos") {
            return $("#segundasMultiRep").attr("data-taller-interno") || "T1";
        }
        return $("#segundasMultiRep").attr("data-taller-externo") || "T0";
    }

    function aplicarCabeceraProcesoSegundaMulti() {
        var cod = tallerRepresentanteAlcanceCodigo();
        $("#nuevoTalleres").val(cod);
        localStorage.setItem("sectorIngreso", cod);
        $("#nuevoTalleres").trigger("change");
        if (typeof window.cargarTablaArticulosTalleresSegundaMulti === "function") {
            window.cargarTablaArticulosTalleresSegundaMulti();
        }
    }

    $(function () {
        if (!window.vistaCrearSegundasMulti) {
            return;
        }
        var $alc = $("#alcanceProcesoCabeceraSegundaMulti");
        if ($alc.length) {
            if (typeof $.fn.selectpicker === "function") {
                $alc.selectpicker();
            }
            aplicarCabeceraProcesoSegundaMulti();
            $alc.on("changed.bs.select", function () {
                limpiarFormularioSegundaMulti();
                aplicarCabeceraProcesoSegundaMulti();
            });
        }

        $(".formularioSegundaMulti").on("submit", function () {
            if (typeof listarArticulosIngreso === "function") {
                listarArticulosIngreso();
            }
        });
    });

    function leerAttrBotonIngreso(el) {
        if (!el || !el.getAttribute) {
            return {};
        }
        var g = function (nombre) {
            var v = el.getAttribute(nombre);
            return v === null || v === undefined ? "" : String(v).trim();
        };
        return {
            articuloSku: g("articulo"),
            articuloIngresoId: g("articuloIngreso") || g("articuloingreso"),
            idCierre: g("idCierre") || g("idcierre"),
            tallerQty: g("taller"),
            codSector: g("data-sector-cod"),
            sectorConsulta: g("data-sector-consulta"),
            filaKey: g("data-fila-ingreso-key"),
            procesoCod: g("data-proceso") || "externo",
        };
    }

    $(document).on(
        "click",
        ".tablaArticulosTalleresSegundaMulti button.agregarArtiTaller",
        function () {
            if (!window.vistaCrearSegundasMulti) {
                return;
            }
            var $btn = $(this);
            if (!$btn.hasClass("agregarArtiTaller")) {
                return;
            }
            var el = this;
            var a = leerAttrBotonIngreso(el);
            var articuloT = a.articuloSku;
            if (!articuloT) {
                if (typeof swal === "function") {
                    swal({
                        type: "error",
                        title: "No se pudo leer el artículo",
                        text: "Faltan datos en el botón (articulo / articuloIngreso).",
                        confirmButtonText: "Cerrar",
                    });
                }
                return;
            }

            var idCierre = a.idCierre;
            var tallerQty = a.tallerQty;
            var codSector = a.codSector;
            var sectorConsulta = a.sectorConsulta;
            var filaKey = a.filaKey;
            var procesoCod = a.procesoCod || "externo";
            var procesoEtq = procesoCod === "interno" ? "Interno" : "Externo";
            var claveFormSinCierre = a.articuloIngresoId || articuloT;

            $btn.removeClass("btn-primary agregarArtiTaller").addClass(
                "btn-default"
            );

            var datos = new FormData();
            datos.append("articuloT", articuloT);

            $.ajax({
                url: "ajax/articulos.ajax.php",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta) {
                    if (!respuesta || respuesta.articulo === undefined) {
                        if (typeof swal === "function") {
                            swal({
                                type: "error",
                                title: "Artículo",
                                text: "Respuesta inválida del servidor.",
                                confirmButtonText: "Cerrar",
                            });
                        }
                        $btn.addClass("btn-primary agregarArtiTaller").removeClass(
                            "btn-default"
                        );
                        return;
                    }
                    var articulo = respuesta["articulo"];
                    var packing = respuesta["packingB"];
                    var taller = respuesta["taller"];
                    var modeloVal =
                        respuesta["modelo"] !== undefined &&
                        respuesta["modelo"] !== null
                            ? respuesta["modelo"]
                            : respuesta["Modelo"];
                    var modeloRaw =
                        modeloVal !== undefined && modeloVal !== null
                            ? String(modeloVal)
                            : "";
                    var modeloEscAttr = modeloRaw
                        .replace(/&/g, "&amp;")
                        .replace(/"/g, "&quot;");
                    var grupoTaller =
                        codSector + "|" + (procesoCod || "externo");
                    var textoTallerFila =
                        codSector !== ""
                            ? codSector + " · " + procesoEtq
                            : "—";
                    var packingEsc = String(packing)
                        .replace(/&/g, "&amp;")
                        .replace(/"/g, "&quot;");
                    var textoTallerEsc = String(textoTallerFila)
                        .replace(/&/g, "&amp;")
                        .replace(/"/g, "&quot;");

                    if (idCierre === "") {
                        $(".nuevoArticuloIngreso").append(
                            `
 <div class="row munditoIngreso" style="padding:5px 15px" data-grupo-taller="${grupoTaller}" data-sort-modelo="${modeloEscAttr}" data-sort-articulo="${articulo}">
                        <div class="col-xs-5" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${claveFormSinCierre}" data-quitar-fila-key="${filaKey}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${claveFormSinCierre}" name="agregarT" value="${packingEsc}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <input type="text" class="form-control input-sm textoTallerIngreso" value="${textoTallerEsc}" data-cod-sector="${codSector}" readonly tabindex="-1">
                        </div>
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${tallerQty}" articulo="${articulo}" nuevoTaller="${tallerQty}" data-cod-sector="${codSector}" data-sector-consulta="${sectorConsulta}" data-proceso="${procesoCod}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" value="${taller}" readonly>
                        </div>
                        <div class="col-xs-1 divCorte">
                            <input type="text" class="form-control nuevoCorteIngreso input-sm" name="nuevoCorteIngreso" value="">
                        </div>
                    </div>`
                        );
                    } else {
                        $(".nuevoArticuloIngreso").append(
                            `
                    <div class="row munditoIngreso" style="padding:5px 15px" data-grupo-taller="${grupoTaller}" data-sort-modelo="${modeloEscAttr}" data-sort-articulo="${articulo}">
                        <div class="col-xs-5" style="padding-right:0px">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <button type="button" class="btn btn-danger btn-xs quitarTaller" articuloIngreso="${idCierre}" data-quitar-fila-key="${filaKey}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloIngreso="${idCierre}" name="agregarT" value="${packingEsc}" codigoAC="${articulo}" idCierre="${idCierre}" readonly required>
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <input type="text" class="form-control input-sm textoTallerIngreso" value="${textoTallerEsc}" data-cod-sector="${codSector}" readonly tabindex="-1">
                        </div>
                        <div class="col-xs-2">
                            <input type="number" class="form-control nuevaCantidadArticuloIngreso input-sm" name="nuevaCantidadArticuloIngreso" min="1" value="0" taller="${tallerQty}" articulo="${articulo}" nuevoTaller="${tallerQty}" data-cod-sector="${codSector}" data-sector-consulta="${sectorConsulta}" data-proceso="${procesoCod}" cantidad="" nuevaCantidad="0" required>
                        </div>
                        <div class="col-xs-2 divSaldoIngreso">
                            <input type="number" class="form-control nuevoSaldoIngreso input-sm" name="nuevoSaldoIngreso" value="${tallerQty}" readonly>
                        </div>
                        <div class="col-xs-1 divCorte">
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
                    quitarAgregarArticuloTSegundaMulti();
                },
                error: function (xhr, status, err) {
                    $btn.addClass("btn-primary agregarArtiTaller").removeClass(
                        "btn-default"
                    );
                    if (typeof swal === "function") {
                        swal({
                            type: "error",
                            title: "Error al cargar artículo",
                            text:
                                status === "parsererror"
                                    ? "La respuesta no es JSON válido."
                                    : (err || status || "Error de red"),
                            confirmButtonText: "Cerrar",
                        });
                    }
                },
            });
        }
    );

    localStorage.removeItem("quitarTallerSegundaMulti");

    $(".formularioSegundaMulti").on("click", "button.quitarTaller", function () {
        var fk = $(this).attr("data-quitar-fila-key");
        if (!fk) {
            return;
        }
        $(this).closest(".munditoIngreso").remove();

        var idQuitar = [];
        if (localStorage.getItem("quitarTallerSegundaMulti") != null) {
            idQuitar = JSON.parse(localStorage.getItem("quitarTallerSegundaMulti"));
        }
        idQuitar.push({ filaKey: fk });
        localStorage.setItem(
            "quitarTallerSegundaMulti",
            JSON.stringify(idQuitar)
        );

        $(
            ".tablaArticulosTalleresSegundaMulti tbody button.recuperarBoton"
        )
            .filter(function () {
                return $(this).attr("data-fila-ingreso-key") === fk;
            })
            .removeClass("btn-default")
            .addClass("btn-primary agregarArtiTaller");

        quitarAgregarArticuloTSegundaMulti();

        if ($(".nuevoArticuloIngreso .munditoIngreso").length === 0) {
            $("#nuevoTotalTaller").val(0);
            $("#totalTaller").val(0);
            $("#nuevoTotalTaller").attr("total", 0);
            $(".nuevoArticuloIngreso .separadorIngresoMultiTaller").remove();
        } else {
            sumarTotalIngreso();
            listarArticulosIngreso();
        }
    });
})();
