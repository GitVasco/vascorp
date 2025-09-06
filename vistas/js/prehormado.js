// tabla prehormado
function cargarTablaPrehormado() {
    $(".tablaPrehormado").DataTable({
        ajax: "ajax/produccion/tabla-prehormado.ajax.php",
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "desc"]],
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

// cargar tabla prehormado
cargarTablaPrehormado();

// tabla para Articulos en prehormado
function cargarTablaArticulosPrehormado(seleccionado) {
    $(".tablaArticulosPrehormado").DataTable({
        ajax:
            "ajax/produccion/tabla-articulos-prehormado.ajax.php?tipoPrehormado=" +
            seleccionado,
        deferRender: true,
        retrieve: true,
        processing: true,
        order: [[0, "asc"]],
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
// Evento change para el select de tipo de prehormado
$("#tipoPrehormadoPS").change(function () {
    var seleccionado = $(this).val();
    console.log("🚀 ~ seleccionado:", seleccionado);
    $(".tablaArticulosPrehormado").DataTable().destroy();
    cargarTablaArticulosPrehormado(seleccionado);
});

// Al cargar la página, si hay producto por defecto
$(document).ready(function () {
    var seleccionado = $("#tipoPrehormadoPS").val();
    if (seleccionado) {
        cargarTablaArticulosPrehormado(seleccionado);
    }
});

(function ($) {
    "use strict";

    // ======= Estado en memoria =======
    const seleccion = new Map(); // key = idArticulo
    const seleccionIds = new Set();
    let lastTipo = null; // último valor válido del select

    // ======= Nodos clave =======
    const $tabla = $(".tablaArticulosPrehormado");
    const $hidden = $("#listaArticulosPrehormado");
    const $totalVis = $("#nuevoTotalTaller");
    const $totalHid = $("#totalTaller");
    const $tipoSel = $("#tipoPrehormadoPS");
    const listaSelector = ".listaSeleccionPrehormado";

    // ======= Utilitarios =======
    function esc(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    function toInt(v, d = 0) {
        var n = parseInt(v, 10);
        return isFinite(n) ? n : d;
    }

    // ======= Espejo hidden del select cuando está disabled =======
    function updateTipoMirror() {
        const $form = $tipoSel.closest("form");
        let $mirror = $("#tipoPrehormadoPS_mirror");
        if ($tipoSel.is(":disabled")) {
            if (!$mirror.length) {
                $mirror = $("<input>", {
                    type: "hidden",
                    id: "tipoPrehormadoPS_mirror",
                    name: "tipoPrehormadoPS",
                    value: $tipoSel.val(),
                }).appendTo($form);
            } else {
                $mirror.val($tipoSel.val());
            }
        } else {
            if ($mirror.length) $mirror.remove();
        }
    }

    // ======= Bloqueo de tipo =======
    function lockTipo(lock) {
        const $grp = $tipoSel.closest(".form-group");
        if (lock) {
            $tipoSel.prop("disabled", true);
            updateTipoMirror();
            if (!$grp.find(".lock-msg").length) {
                $grp.append(
                    '<p class="help-block lock-msg">Bloqueado porque hay artículos en la lista.</p>'
                );
            }
        } else {
            $tipoSel.prop("disabled", false);
            updateTipoMirror();
            $grp.find(".lock-msg").remove();
        }
    }
    function updateTipoLock() {
        if (seleccion.size > 0) lockTipo(true);
        else lockTipo(false);
    }

    // ======= Serialización + Totales =======
    function actualizarTotalesYHidden() {
        let total = 0;
        const arr = [];
        seleccion.forEach((it) => {
            const qty = toInt(it.cantidad, 0);
            total += qty;
            arr.push({
                id: it.id,
                codigo: it.codigo,
                nombre: it.nombre,
                color: it.color,
                talla: it.talla,
                cantidad: qty,
            });
        });
        $totalVis.val(total);
        $totalHid.val(total);
        $hidden.val(JSON.stringify(arr));
    }

    // ======= Render de la lista =======
    function ensureListaContainer() {
        const $listaPanel = $(".panel.panel-primary .panel-body"); // cuerpo de "Lista por agregar"
        if (!$listaPanel.find(listaSelector).length) {
            $listaPanel
                .find(".well")
                .after('<div class="listaSeleccionPrehormado"></div>');
        }
    }
    function renderSeleccion() {
        ensureListaContainer();
        const $lista = $(listaSelector).empty();

        if (seleccion.size === 0) {
            $lista.append(
                '<div class="text-center text-muted">' +
                    '<p><i class="fa fa-inbox"></i></p>' +
                    "<p>Aún no has agregado artículos.<br><small>Usa la tabla de la derecha para seleccionarlos.</small></p>" +
                    "</div>"
            );
            actualizarTotalesYHidden();
            updateTipoLock();
            return;
        }

        const out = [];
        seleccion.forEach((it) => {
            out.push(
                '<div class="row" data-id="' +
                    esc(it.id) +
                    '">' +
                    '<div class="col-xs-10">' +
                    "<div><strong>" +
                    esc(it.codigo) +
                    "</strong> — " +
                    esc(it.nombre) +
                    "</div>" +
                    '<div class="text-muted small">Color: ' +
                    esc(it.color) +
                    " · Talla: " +
                    esc(it.talla) +
                    "</div>" +
                    "</div>" +
                    '<div class="col-xs-2">' +
                    '<div class="input-group input-group-sm">' +
                    '<input type="number" class="form-control inpCant" min="1" value="' +
                    (it.cantidad || 1) +
                    '">' +
                    '<span class="input-group-btn">' +
                    '<button class="btn btn-default btnQuitar" type="button" title="Quitar"><i class="fa fa-times"></i></button>' +
                    "</span>" +
                    "</div>" +
                    "</div>" +
                    "</div>"
            );
        });
        $(listaSelector).html(out.join(""));
        actualizarTotalesYHidden();
        updateTipoLock();
    }

    // ======= Botones de la tabla (sync estado) =======
    function syncBotonesTabla() {
        if (!$tabla.length || !$tabla.data("DataTable")) return;
        $tabla
            .DataTable()
            .rows({ page: "current" })
            .nodes()
            .to$()
            .find("button.btnAgregarArticuloPrehormado")
            .each(function () {
                const $btn = $(this);
                const id = $btn.attr("idArticulo");
                if (seleccionIds.has(id)) {
                    $btn.removeClass("btn-primary")
                        .addClass("btn-default")
                        .prop("disabled", true);
                } else {
                    $btn.removeClass("btn-default")
                        .addClass("btn-primary")
                        .prop("disabled", false);
                }
            });
    }

    // ======= Agregar desde la tabla =======
    $tabla.on(
        "click",
        "tbody button.btnAgregarArticuloPrehormado",
        function () {
            const $btn = $(this);
            const id = $btn.attr("idArticulo");
            if (!id) return;

            const item = {
                id: id,
                codigo: id, // si tu "código" es el mismo id
                nombre: $btn.attr("nombreArticulo") || "",
                color: $btn.attr("colorArticulo") || "",
                talla: $btn.attr("tallaArticulo") || "",
                cantidad: 1,
            };

            if (!seleccion.has(id)) {
                seleccion.set(id, item);
                seleccionIds.add(id);
            } else {
                const it = seleccion.get(id);
                it.cantidad = toInt(it.cantidad, 1) + 1;
                seleccion.set(id, it);
            }

            renderSeleccion();
            $btn.removeClass("btn-primary")
                .addClass("btn-default")
                .prop("disabled", true);
        }
    );

    // ======= Quitar / Cambiar cantidad en la lista =======
    $(document).on("click", listaSelector + " .btnQuitar", function () {
        const id = String($(this).closest("[data-id]").data("id"));
        if (!id) return;
        seleccion.delete(id);
        seleccionIds.delete(id);
        renderSeleccion();
        $tabla
            .find(
                "button.btnAgregarArticuloPrehormado[idArticulo='" + id + "']"
            )
            .removeClass("btn-default")
            .addClass("btn-primary")
            .prop("disabled", false);
    });

    $(document).on("change input", listaSelector + " .inpCant", function () {
        const id = String($(this).closest("[data-id]").data("id"));
        const it = seleccion.get(id);
        if (!it) return;
        it.cantidad = Math.max(1, toInt($(this).val(), 1));
        seleccion.set(id, it);
        actualizarTotalesYHidden();
    });

    // ======= Cambio de tipo (bloqueado si hay selección) =======
    $tipoSel.on("focus", function () {
        lastTipo = $(this).val();
    });

    $tipoSel.on("change", function () {
        if (seleccion.size > 0) {
            $(this).val(lastTipo);
            alert(
                "No puedes cambiar el tipo mientras haya artículos seleccionados. Quita los artículos primero."
            );
            updateTipoMirror();
            return;
        }
        lastTipo = $(this).val();
        if ($tabla.data("DataTable")) {
            $tabla.DataTable().destroy();
        }
        cargarTablaArticulosPrehormado(lastTipo); // tu función existente
        updateTipoMirror();
    });

    // ======= DataTable draw: sincronizar botones =======
    $tabla.on("draw.dt", function () {
        syncBotonesTabla();
    });

    // ======= Init =======
    $(document).ready(function () {
        ensureListaContainer();
        lastTipo = $tipoSel.val();
        updateTipoLock();
        updateTipoMirror(); // asegura que el tipo viaje en POST si quedó bloqueado
    });
})(jQuery);
