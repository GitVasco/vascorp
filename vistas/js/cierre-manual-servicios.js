/*=============================================
CIERRE MANUAL DE SERVICIOS (selección + motivo + historial)
=============================================*/
$(function () {
    if (!$(".tablaCierreManual").length) {
        return;
    }

    var URL = "ajax/servicios-cierre-manual.ajax.php";
    var MOTIVOS = {
        PERDIDA: "Pérdida / merma",
        DEFECTO: "Prendas con defecto",
        ERROR_REGISTRO: "Error de registro",
        ACUERDO_TALLER: "Acuerdo con el taller",
        OTRO: "Otro",
    };

    function avisarError(json) {
        if (json && json.error) {
            swal({ type: "error", title: "Error al cargar los datos", text: json.error, confirmButtonText: "Cerrar" });
        }
    }

    function esc(t) {
        return $("<div>").text(t == null ? "" : t).html();
    }

    /*---- filtros taller / modelo (se guardan en la URL) ----*/
    var params = new URLSearchParams(window.location.search);
    var filtroTaller = params.get("taller") || "";
    var filtroModelo = params.get("modelo") || "";

    // combinaciones taller/modelo conocidas (de las dos tablas) para armar los selects en cascada
    var talleres = {}; // codTaller -> nombre
    var combos = {}; // "codTaller|modelo" -> true

    function registrarCombos(filas) {
        $.each(filas, function (i, r) {
            if (r.taller) {
                talleres[r.taller] = r.taller + " - " + (r.nom_sector || "");
            }
            if (r.taller && r.modelo) {
                combos[r.taller + "|" + r.modelo] = true;
            }
        });
    }

    function modelosDeTaller(taller) {
        var lista = {};
        $.each(combos, function (k) {
            var i = k.indexOf("|");
            if (!taller || k.substring(0, i) === taller) {
                lista[k.substring(i + 1)] = true;
            }
        });
        return Object.keys(lista).sort(function (a, b) {
            return a.localeCompare(b);
        });
    }

    function llenar($sel, opciones, valorActual) {
        $sel.empty().append($("<option>").val("").text("Todos"));
        var hay = false;
        $.each(opciones, function (i, o) {
            if (o.valor === valorActual) {
                hay = true;
            }
            $sel.append($("<option>").val(o.valor).text(o.texto));
        });
        // un valor que viene de la URL se conserva aunque aún no haya datos
        if (valorActual && !hay) {
            $sel.append($("<option>").val(valorActual).text(valorActual));
        }
        $sel.val(valorActual).selectpicker("refresh");
    }

    function refrescarSelects() {
        var optsTaller = Object.keys(talleres)
            .sort(function (a, b) {
                return talleres[a].localeCompare(talleres[b]);
            })
            .map(function (k) {
                return { valor: k, texto: talleres[k] };
            });
        var optsModelo = modelosDeTaller(filtroTaller).map(function (m) {
            return { valor: m, texto: m };
        });
        llenar($("#cmFiltroTaller"), optsTaller, filtroTaller);
        llenar($("#cmFiltroModelo"), optsModelo, filtroModelo);
    }

    function poblarFiltros(filas) {
        registrarCombos(filas);
        refrescarSelects();
    }

    function guardarFiltrosEnUrl() {
        var p = new URLSearchParams(window.location.search);
        filtroTaller ? p.set("taller", filtroTaller) : p.delete("taller");
        filtroModelo ? p.set("modelo", filtroModelo) : p.delete("modelo");
        var qs = p.toString();
        window.history.replaceState(null, "", window.location.pathname + (qs ? "?" + qs : ""));
    }

    // valores iniciales que vienen de la URL (se muestran aunque aún no carguen los datos)
    refrescarSelects();

    // filtro de filas para las dos tablas del módulo
    $.fn.dataTable.ext.search.push(function (settings, data, index) {
        var cls = settings.nTable.className || "";
        if (cls.indexOf("tablaCierreManual") === -1) {
            return true;
        }
        var r = settings.aoData[index]._aData;
        if (filtroTaller && String(r.taller) !== filtroTaller) {
            return false;
        }
        if (filtroModelo && String(r.modelo) !== filtroModelo) {
            return false;
        }
        return true;
    });

    // cantidades escritas por línea: { idLinea: cantidad }
    var cant = {};

    function resumenCM() {
        var lineas = 0;
        var total = 0;
        $.each(cant, function (id, c) {
            if (c > 0) {
                lineas++;
                total += c;
            }
        });
        $("#cmResumen").text(lineas + " líneas · " + total + " prendas");
    }

    var tablaCM = $(".tablaCierreManual").DataTable({
        ajax: { url: URL, type: "POST", data: { accion: "listar" } },
        pageLength: 25,
        order: [[3, "desc"]],
        columns: [
            { data: "codigo" },
            { data: "guia" },
            {
                data: null,
                render: function (d, t, r) {
                    return esc(r.taller) + " - " + esc(r.nom_sector);
                },
            },
            { data: "fecha" },
            {
                data: null,
                render: function (d, t, r) {
                    return esc(r.modelo) + " " + esc(r.nombre) + " " + esc(r.color);
                },
            },
            { data: "cantidad" },
            { data: "saldo" },
            {
                data: null,
                orderable: false,
                className: "cmColCant",
                render: function (d, t, r) {
                    var v = cant[r.id] > 0 ? cant[r.id] : "";
                    return (
                        "<div class='input-group input-group-sm'>" +
                        "<input type='number' min='0' max='" + r.saldo + "' class='form-control cmCant' data-id='" + r.id + "' data-saldo='" + r.saldo + "' value='" + v + "'>" +
                        "<span class='input-group-btn'><button type='button' class='btn btn-default cmTodo' title='Completar con el saldo'><i class='fa fa-arrow-down'></i></button></span>" +
                        "</div>"
                    );
                },
            },
        ],
        language: $.fn.dataTable.defaults.language,
        drawCallback: resumenCM,
    }).on("xhr.dt", function (e, settings, json) {
        avisarError(json);
        if (json && json.data) {
            poblarFiltros(json.data);
        }
    });

    var tablaHist = null;
    function cargarHistorial() {
        if (tablaHist) {
            tablaHist.destroy();
        }
        tablaHist = $(".tablaCierreManualHistorial").DataTable({
            ajax: {
                url: URL,
                type: "POST",
                data: { accion: "historial", desde: $("#cmDesde").val(), hasta: $("#cmHasta").val() },
            },
            pageLength: 25,
            order: [[0, "desc"]],
            columns: [
                { data: "fecha" },
                { data: "usuario" },
                {
                    data: "motivo",
                    render: function (v) {
                        return esc(MOTIVOS[v] || v);
                    },
                },
                {
                    data: "obs",
                    render: function (v) {
                        return esc(v);
                    },
                },
                { data: "codigo" },
                { data: "guia" },
                {
                    data: null,
                    render: function (d, t, r) {
                        return esc(r.taller) + " - " + esc(r.nom_sector);
                    },
                },
                {
                    data: null,
                    render: function (d, t, r) {
                        return esc(r.modelo) + " " + esc(r.nombre) + " " + esc(r.color);
                    },
                },
                { data: "saldo_antes" },
                { data: "cantidad" },
            ],
            language: $.fn.dataTable.defaults.language,
        }).on("xhr.dt", function (e, settings, json) {
            avisarError(json);
            if (json && json.data) {
                poblarFiltros(json.data);
            }
        });
    }

    function aplicarFiltros() {
        guardarFiltrosEnUrl();
        cant = {};
        tablaCM.draw();
        if (tablaHist) {
            tablaHist.draw();
        }
    }
    // cascada: al elegir taller, los modelos se limitan a los de ese taller
    $("#cmFiltroTaller").on("change", function () {
        filtroTaller = $(this).val() || "";
        if (filtroModelo && filtroTaller && !combos[filtroTaller + "|" + filtroModelo]) {
            filtroModelo = "";
        }
        refrescarSelects();
        aplicarFiltros();
    });
    $("#cmFiltroModelo").on("change", function () {
        filtroModelo = $(this).val() || "";
        aplicarFiltros();
    });
    $("#cmLimpiarFiltros").on("click", function () {
        filtroTaller = "";
        filtroModelo = "";
        refrescarSelects();
        aplicarFiltros();
    });

    $('a[href="#cmTabCerrar"]').on("shown.bs.tab", function () {
        tablaCM.columns.adjust();
    });
    cargarHistorial();
    $("#cmBtnFiltrar").on("click", cargarHistorial);

    function fijarCantidad($inp, valor) {
        var saldo = parseInt($inp.data("saldo"), 10) || 0;
        var v = parseInt(valor, 10);
        if (isNaN(v) || v < 0) {
            v = 0;
        }
        if (v > saldo) {
            v = saldo;
        }
        var id = $inp.data("id");
        if (v > 0) {
            cant[id] = v;
        } else {
            delete cant[id];
        }
        $inp.val(v > 0 ? v : "");
        resumenCM();
    }

    $(".tablaCierreManual").on("change", ".cmCant", function () {
        fijarCantidad($(this), $(this).val());
    });
    $(".tablaCierreManual").on("click", ".cmTodo", function () {
        var $inp = $(this).closest(".input-group").find(".cmCant");
        fijarCantidad($inp, $inp.data("saldo"));
    });

    $("#btnConfirmarCierreManual").on("click", function () {
        // asegurar que el último input editado quedó registrado
        $(".tablaCierreManual .cmCant").each(function () {
            if ($(this).val() !== "") {
                fijarCantidad($(this), $(this).val());
            }
        });

        var items = [];
        var total = 0;
        $.each(cant, function (id, c) {
            if (c > 0) {
                items.push({ id: parseInt(id, 10), cantidad: c });
                total += c;
            }
        });
        var motivo = $("#cmMotivo").val();
        var obs = $("#cmObs").val();

        if (!items.length) {
            swal({ type: "warning", title: "Escriba la cantidad a cerrar en al menos una línea", confirmButtonText: "Cerrar" });
            return;
        }
        if (!motivo) {
            swal({ type: "warning", title: "Seleccione el motivo del cierre", confirmButtonText: "Cerrar" });
            return;
        }
        if (motivo === "OTRO" && !$.trim(obs)) {
            swal({ type: "warning", title: "Indique la observación para el motivo 'Otro'", confirmButtonText: "Cerrar" });
            return;
        }

        swal({
            title: "¿Cerrar " + total + " prenda(s) en " + items.length + " línea(s)?",
            text: "Se descontarán del saldo y del stock en servicio. Esta acción no se puede deshacer.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, cerrar",
            cancelButtonText: "Cancelar",
        }).then(function (r) {
            if (!r.value) {
                return;
            }
            $.post(
                URL,
                { accion: "cerrar", items: JSON.stringify(items), motivo: motivo, obs: obs },
                function (resp) {
                    if (resp && resp.ok) {
                        swal({ type: "success", title: "Se cerraron " + resp.cantidad + " prenda(s) en " + resp.cerrados + " línea(s)", confirmButtonText: "Cerrar" }).then(function () {
                            cant = {};
                            $("#cmMotivo").val("").selectpicker("refresh");
                            $("#cmObs").val("");
                            tablaCM.ajax.reload();
                            cargarHistorial();
                        });
                    } else {
                        swal({ type: "error", title: (resp && resp.error) || "No se pudo cerrar", confirmButtonText: "Cerrar" });
                    }
                },
                "json"
            );
        });
    });
});
