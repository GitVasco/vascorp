/**
 * Vista crear-ingresos-multi: tabla servida por * ajax/produccion/tabla-articulostaller-ingresos-multi.ajax.php
 */
(function () {
    if (!window.vistaCrearIngresosMulti) {
        return;
    }

    var perfilOcultoSel = "#perfilOculto";

    /**
     * Desactiva el botón agregar en la tabla para cada fila ya presente en el formulario
     * (misma idea que quitarAgregarArticuloT en talleres.js). Tras paginar o buscar, DataTables
     * repinta los botones; hay que volver a aplicar según data-quitar-fila-key del formulario.
     */
    function quitarAgregarArticuloTMulti() {
        var keysEnFormulario = {};
        $(".formularioIngresoMulti .quitarTaller").each(function () {
            var fk = $(this).attr("data-quitar-fila-key");
            if (fk) {
                keysEnFormulario[fk] = true;
            }
        });

        $(".tablaArticulosTalleresMulti tbody button.recuperarBoton").each(
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

    /**
     * Ordena filas del formulario por taller (sector + proceso) y dentro por modelo;
     * inserta una línea horizontal al cambiar de grupo de taller.
     */
    function reordenarFilasIngresoMulti() {
        var $cont = $(".nuevoArticuloIngreso");
        if (!$cont.length) {
            return;
        }

        $cont.find(".separadorIngresoMultiTaller").remove();

        var $filas = $cont.children(".munditoIngreso").detach();
        var arr = $filas.toArray();

        arr.sort(function (a, b) {
            var ga = a.getAttribute("data-grupo-taller") || "";
            var gb = b.getAttribute("data-grupo-taller") || "";
            if (ga !== gb) {
                return ga.localeCompare(gb, "es", { sensitivity: "base" });
            }
            var ma = a.getAttribute("data-sort-modelo") || "";
            var mb = b.getAttribute("data-sort-modelo") || "";
            if (ma !== mb) {
                return ma.localeCompare(mb, "es", {
                    sensitivity: "base",
                    numeric: true,
                });
            }
            var sa = a.getAttribute("data-sort-articulo") || "";
            var sb = b.getAttribute("data-sort-articulo") || "";
            return sa.localeCompare(sb, "es", {
                sensitivity: "base",
                numeric: true,
            });
        });

        var prevGrupo = null;
        for (var i = 0; i < arr.length; i++) {
            var g = arr[i].getAttribute("data-grupo-taller") || "";
            if (i > 0 && g !== prevGrupo) {
                $cont.append(
                    '<div class="row separadorIngresoMultiTaller" aria-hidden="true"><div class="col-xs-12"><hr style="margin:10px 15px;border-top:1px solid #bbb"></div></div>'
                );
            }
            $cont.append(arr[i]);
            prevGrupo = g;
        }
    }

    /**
     * Vacía líneas del ingreso multi, totales y estado de “quitar”; reactiva botones agregar en la tabla actual.
     */
    function limpiarFormularioIngresoMulti() {
        var $cont = $(".nuevoArticuloIngreso");
        if ($cont.length) {
            $cont.find(".munditoIngreso").remove();
            $cont.find(".separadorIngresoMultiTaller").remove();
        }
        localStorage.removeItem("quitarTallerMulti");
        $("#listaArticulosIngreso").val("[]");
        $("#nuevoTotalTaller").val(0);
        $("#totalTaller").val(0);
        $("#nuevoTotalTaller").attr("total", 0);
        $(
            ".tablaArticulosTalleresMulti tbody button.recuperarBoton"
        )
            .removeClass("btn-default")
            .addClass("btn-primary agregarArtiTaller");
    }

    function restaurarBotonesAgregarDesdeQuitarMulti() {
        if (localStorage.getItem("quitarTallerMulti") == null) {
            return;
        }
        var lista = JSON.parse(localStorage.getItem("quitarTallerMulti"));
        for (var i = 0; i < lista.length; i++) {
            var fk = lista[i].filaKey;
            if (!fk) {
                continue;
            }
            $(
                ".tablaArticulosTalleresMulti tbody button.recuperarBoton"
            ).filter(function () {
                return $(this).attr("data-fila-ingreso-key") === fk;
            })
                .removeClass("btn-default")
                .addClass("btn-primary agregarArtiTaller");
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
            drawCallback: function () {
                restaurarBotonesAgregarDesdeQuitarMulti();
                quitarAgregarArticuloTMulti();
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
        var $alc = $("#alcanceProcesoCabeceraMulti");
        if ($alc.length) {
            if (typeof $.fn.selectpicker === "function") {
                $alc.selectpicker();
            }
            aplicarCabeceraProcesoMulti();
            $alc.on("changed.bs.select", function () {
                limpiarFormularioIngresoMulti();
                aplicarCabeceraProcesoMulti();
            });
        }

        $(".formularioIngresoMulti").on("submit", function () {
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
        ".tablaArticulosTalleresMulti button.agregarArtiTaller",
        function (e) {
            if (!window.vistaCrearIngresosMulti) {
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

                    reordenarFilasIngresoMulti();

                    sumarTotalIngreso();
                    listarArticulosIngreso();
                    quitarAgregarArticuloTMulti();
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

    localStorage.removeItem("quitarTallerMulti");

    $(".formularioIngresoMulti").on("click", "button.quitarTaller", function () {
        var fk = $(this).attr("data-quitar-fila-key");
        if (!fk) {
            return;
        }
        $(this).closest(".munditoIngreso").remove();

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
            ".tablaArticulosTalleresMulti tbody button.recuperarBoton"
        )
            .filter(function () {
                return $(this).attr("data-fila-ingreso-key") === fk;
            })
            .removeClass("btn-default")
            .addClass("btn-primary agregarArtiTaller");

        quitarAgregarArticuloTMulti();

        if ($(".nuevoArticuloIngreso .munditoIngreso").length === 0) {
            $("#nuevoTotalTaller").val(0);
            $("#totalTaller").val(0);
            $("#nuevoTotalTaller").attr("total", 0);
            $(".nuevoArticuloIngreso .separadorIngresoMultiTaller").remove();
        } else {
            reordenarFilasIngresoMulti();
            sumarTotalIngreso();
            listarArticulosIngreso();
        }
    });
})();
