/* MP usadas en recetas */

var mprRecTodos = [];
var mprRecTabla = null;
var mprRecSilencio = false;

function mprRecFmtNum(n, dec) {
    var x = Number(n);
    if (isNaN(x)) {
        return "0";
    }
    return x.toLocaleString("es-PE", {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
    });
}

function mprRecEscape(s) {
    return String(s == null ? "" : s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function mprRecEstadoLabel(estado) {
    if (estado === "PUBLICADA") {
        return "<span class='label label-success'>Publicada</span>";
    }
    if (estado === "BORRADOR") {
        return "<span class='label label-warning'>Borrador</span>";
    }
    return "<span class='label label-default'>" + mprRecEscape(estado) + "</span>";
}

function mprRecVal($el) {
    return String($el.val() || "").trim();
}

function mprRecRefreshSelect($el) {
    if ($el.hasClass("selectpicker") && $.fn.selectpicker) {
        $el.selectpicker("refresh");
    }
}

function mprRecSetDisabled($el, disabled) {
    $el.prop("disabled", !!disabled);
    mprRecRefreshSelect($el);
}

function mprRecSetVal($el, valor) {
    $el.val(valor || "");
    if ($el.hasClass("selectpicker") && $.fn.selectpicker) {
        $el.selectpicker("val", valor || "");
        $el.selectpicker("refresh");
    }
}

function mprRecLeerUrl() {
    var q = {};
    var search = window.location.search || "";
    if (search.charAt(0) === "?") {
        search = search.substring(1);
    }
    if (!search) {
        return { linea: "", sublinea: "", mp: "", costo: "" };
    }
    var parts = search.split("&");
    for (var i = 0; i < parts.length; i++) {
        if (!parts[i]) {
            continue;
        }
        var kv = parts[i].split("=");
        var k = decodeURIComponent(kv[0] || "");
        var v = decodeURIComponent((kv[1] || "").replace(/\+/g, " "));
        q[k] = v;
    }
    return {
        linea: String(q.linea || "").trim(),
        sublinea: String(q.sublinea || "").trim(),
        mp: String(q.mp || "").trim(),
        costo: String(q.costo || "").trim()
    };
}

function mprRecEscribirUrl() {
    if (mprRecSilencio) {
        return;
    }
    var actuales = mprRecLeerUrl();
    var next = {
        linea: mprRecVal($("#mprRecLinea")),
        sublinea: mprRecVal($("#mprRecSublinea")),
        mp: mprRecVal($("#mprRecMp")),
        costo: mprRecVal($("#mprRecCosto"))
    };
    var params = [];
    var search = window.location.search || "";
    if (search.charAt(0) === "?") {
        search = search.substring(1);
    }
    if (search) {
        var parts = search.split("&");
        for (var i = 0; i < parts.length; i++) {
            if (!parts[i]) {
                continue;
            }
            var kv = parts[i].split("=");
            var k = decodeURIComponent(kv[0] || "");
            if (k === "linea" || k === "sublinea" || k === "mp" || k === "costo") {
                continue;
            }
            params.push(parts[i]);
        }
    }
    if (next.linea) {
        params.push("linea=" + encodeURIComponent(next.linea));
    }
    if (next.sublinea) {
        params.push("sublinea=" + encodeURIComponent(next.sublinea));
    }
    if (next.mp) {
        params.push("mp=" + encodeURIComponent(next.mp));
    }
    if (next.costo) {
        params.push("costo=" + encodeURIComponent(next.costo));
    }
    var qs = params.join("&");
    var dest = window.location.pathname + (qs ? ("?" + qs) : "");
    if (window.location.hash) {
        dest += window.location.hash;
    }
    if (
        next.linea === actuales.linea
        && next.sublinea === actuales.sublinea
        && next.mp === actuales.mp
        && next.costo === actuales.costo
        && (window.location.search || "") === (qs ? ("?" + qs) : "")
    ) {
        return;
    }
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, "", dest);
    }
}

function mprRecFilasFiltradas() {
    var linea = mprRecVal($("#mprRecLinea"));
    var sub = mprRecVal($("#mprRecSublinea"));
    var mp = mprRecVal($("#mprRecMp"));
    var costo = mprRecVal($("#mprRecCosto"));
    var out = [];
    for (var i = 0; i < mprRecTodos.length; i++) {
        var r = mprRecTodos[i];
        if (linea && String(r.linea || "") !== linea) {
            continue;
        }
        if (sub && String(r.codigo_sublinea || "") !== sub) {
            continue;
        }
        if (mp && String(r.codpro || "") !== mp) {
            continue;
        }
        var tieneCosto = Number(r.costo) > 0;
        if (costo === "con" && !tieneCosto) {
            continue;
        }
        if (costo === "sin" && tieneCosto) {
            continue;
        }
        out.push(r);
    }
    return out;
}

function mprRecPintarTabla() {
    var rows = mprRecFilasFiltradas();
    if (!mprRecTabla) {
        return;
    }
    mprRecTabla.clear();
    mprRecTabla.rows.add(rows);
    mprRecTabla.draw();
}

function mprRecResetMp(placeholder) {
    $("#mprRecMp").html("<option value=\"\">" + mprRecEscape(placeholder) + "</option>");
    $("#mprRecMp").val("");
    mprRecSetDisabled($("#mprRecMp"), true);
}

function mprRecResetSublinea(placeholder) {
    $("#mprRecSublinea").html("<option value=\"\">" + mprRecEscape(placeholder) + "</option>");
    $("#mprRecSublinea").val("");
    mprRecSetDisabled($("#mprRecSublinea"), true);
    mprRecResetMp("Elegí línea y sublínea");
}

function mprRecCargarLineas() {
    return $.post("ajax/materiaprima/mp-recetas.ajax.php", { accion: "lineas" }, function (resp) {
        var html = "<option value=\"\">Todas las líneas</option>";
        var rows = (resp && resp.ok && resp.data) ? resp.data : [];
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var etq = r.codigo + (r.nombre ? (" - " + r.nombre) : "");
            html += "<option value=\"" + mprRecEscape(r.codigo) + "\">" + mprRecEscape(etq) + "</option>";
        }
        $("#mprRecLinea").html(html);
        mprRecRefreshSelect($("#mprRecLinea"));
    }, "json");
}

function mprRecCargarSublineas(linea) {
    if (!linea) {
        mprRecResetSublinea("Elegí una línea");
        return $.Deferred().resolve().promise();
    }
    return $.post("ajax/materiaprima/mp-recetas.ajax.php", {
        accion: "sublineas",
        linea: linea
    }, function (resp) {
        var html = "<option value=\"\">Todas las sublíneas</option>";
        var rows = (resp && resp.ok && resp.data) ? resp.data : [];
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var etq = r.codigo + (r.nombre ? (" - " + r.nombre) : "");
            html += "<option value=\"" + mprRecEscape(r.codigo) + "\">" + mprRecEscape(etq) + "</option>";
        }
        $("#mprRecSublinea").html(html);
        mprRecSetDisabled($("#mprRecSublinea"), false);
        if (!mprRecSilencio) {
            mprRecResetMp("Elegí línea y sublínea");
        }
    }, "json");
}

function mprRecCargarMps(linea, sub) {
    if (!linea || !sub) {
        mprRecResetMp("Elegí línea y sublínea");
        return;
    }
    var html = "<option value=\"\">Todas las MP</option>";
    var vistos = {};
    var items = [];
    for (var i = 0; i < mprRecTodos.length; i++) {
        var r = mprRecTodos[i];
        if (String(r.linea || "") !== linea || String(r.codigo_sublinea || "") !== sub) {
            continue;
        }
        if (vistos[r.codpro]) {
            continue;
        }
        vistos[r.codpro] = true;
        items.push(r);
    }
    items.sort(function (a, b) {
        return String(a.codpro).localeCompare(String(b.codpro), "es", { numeric: true });
    });
    for (var j = 0; j < items.length; j++) {
        var m = items[j];
        var etq = m.codpro + " - " + (m.despro || "") + (m.color ? (" - " + m.color) : "");
        html += "<option value=\"" + mprRecEscape(m.codpro) + "\">" + mprRecEscape(etq) + "</option>";
    }
    $("#mprRecMp").html(html);
    mprRecSetDisabled($("#mprRecMp"), false);
    if (!mprRecSilencio) {
        mprRecSetVal($("#mprRecMp"), "");
    }
}

$(function () {
    mprRecResetSublinea("Elegí una línea");
    mprRecResetMp("Elegí línea y sublínea");

    mprRecTabla = $("#tablaMpRecetas").DataTable({
        data: [],
        deferRender: true,
        autoWidth: false,
        scrollX: false,
        pageLength: 25,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Todos"]],
        order: [[1, "asc"]],
        columns: [
            { data: "codpro", width: "70px" },
            { data: "codfab", width: "120px" },
            {
                data: "despro",
                render: function (v) {
                    var t = v || "";
                    return "<span title=\"" + mprRecEscape(t) + "\">" + mprRecEscape(t) + "</span>";
                }
            },
            { data: "color", width: "110px" },
            { data: "unidad", width: "70px" },
            {
                data: "stock",
                className: "text-right",
                width: "90px",
                render: function (v) { return mprRecFmtNum(v, 2); }
            },
            {
                data: "costo",
                className: "text-right",
                width: "90px",
                render: function (v) { return mprRecFmtNum(v, 4); }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center nowrap",
                width: "70px",
                render: function (row) {
                    return "<div class='mpr-rec-acc'>"
                        + "<button type='button' class='btn btn-xs btn-primary btnMpRecetasCosto' data-codpro='"
                        + mprRecEscape(row.codpro) + "' title='Editar costo'><i class='fa fa-money'></i></button>"
                        + "<button type='button' class='btn btn-xs btn-info btnMpRecetasVer' data-codpro='"
                        + mprRecEscape(row.codpro) + "' data-despro='"
                        + mprRecEscape(row.despro) + "' title='Ver recetas'><i class='fa fa-eye'></i></button>"
                        + "</div>";
                }
            }
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
            sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sSearch: "Buscar:",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            }
        }
    });

    function mprRecCargarLista() {
        return $.post("ajax/materiaprima/mp-recetas.ajax.php", {
            accion: "listar"
        }, function (resp) {
            mprRecTodos = (resp && resp.ok && resp.data) ? resp.data : [];
            if (!mprRecSilencio) {
                var linea = mprRecVal($("#mprRecLinea"));
                var sub = mprRecVal($("#mprRecSublinea"));
                if (linea && sub) {
                    var mpSel = mprRecVal($("#mprRecMp"));
                    mprRecCargarMps(linea, sub);
                    if (mpSel) {
                        mprRecSetVal($("#mprRecMp"), mpSel);
                    }
                } else if (!linea) {
                    mprRecResetSublinea("Elegí una línea");
                } else if (!sub) {
                    mprRecResetMp("Elegí línea y sublínea");
                }
                mprRecPintarTabla();
            }
        }, "json");
    }

    function mprRecRestaurarFiltros() {
        var ini = mprRecLeerUrl();
        mprRecSilencio = true;
        if (ini.costo === "con" || ini.costo === "sin") {
            $("#mprRecCosto").val(ini.costo);
        }
        return $.when(mprRecCargarLineas(), mprRecCargarLista()).always(function () {
            if (!ini.linea) {
                mprRecSilencio = false;
                mprRecPintarTabla();
                return;
            }
            mprRecSetVal($("#mprRecLinea"), ini.linea);
            mprRecCargarSublineas(ini.linea).always(function () {
                if (ini.sublinea) {
                    mprRecSetVal($("#mprRecSublinea"), ini.sublinea);
                    mprRecCargarMps(ini.linea, ini.sublinea);
                    if (ini.mp) {
                        mprRecSetVal($("#mprRecMp"), ini.mp);
                    }
                } else {
                    mprRecResetMp("Elegí línea y sublínea");
                }
                mprRecSilencio = false;
                mprRecPintarTabla();
                mprRecEscribirUrl();
            });
        });
    }

    mprRecRestaurarFiltros();

    $("#mprRecLinea").on("changed.bs.select change", function () {
        if (mprRecSilencio) {
            return;
        }
        var linea = mprRecVal($("#mprRecLinea"));
        mprRecCargarSublineas(linea).always(function () {
            mprRecPintarTabla();
            mprRecEscribirUrl();
        });
    });

    $("#mprRecSublinea").on("changed.bs.select change", function () {
        if (mprRecSilencio) {
            return;
        }
        var linea = mprRecVal($("#mprRecLinea"));
        var sub = mprRecVal($("#mprRecSublinea"));
        mprRecCargarMps(linea, sub);
        mprRecPintarTabla();
        mprRecEscribirUrl();
    });

    $("#mprRecMp").on("changed.bs.select change", function () {
        if (mprRecSilencio) {
            return;
        }
        mprRecPintarTabla();
        mprRecEscribirUrl();
    });

    $("#mprRecCosto").on("change", function () {
        if (mprRecSilencio) {
            return;
        }
        mprRecPintarTabla();
        mprRecEscribirUrl();
    });

    $("#mprRecBtnPlantilla").on("click", function () {
        var qs = $.param({
            linea: mprRecVal($("#mprRecLinea")),
            sublinea: mprRecVal($("#mprRecSublinea")),
            mp: mprRecVal($("#mprRecMp")),
            costo: mprRecVal($("#mprRecCosto"))
        });
        window.location = "ajax/materiaprima/mp-recetas-plantilla.php?" + qs;
    });

    $("#modalMpRecetasImportar").on("hidden.bs.modal", function () {
        $("#mprRecArchivo").val("");
        $("#mprRecImportMsg").hide().removeClass("alert-success alert-danger alert-warning").text("");
    });

    $("#mprRecBtnImportar").on("click", function () {
        var input = document.getElementById("mprRecArchivo");
        if (!input || !input.files || !input.files[0]) {
            swal({
                type: "warning",
                title: "Falta el archivo",
                text: "Elegí el Excel o CSV con los costos.",
                confirmButtonText: "Cerrar"
            });
            return;
        }
        var $btn = $("#mprRecBtnImportar");
        var fd = new FormData();
        fd.append("accion", "importarCostos");
        fd.append("archivo", input.files[0]);
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo…');
        $.ajax({
            url: "ajax/materiaprima/mp-recetas.ajax.php",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (resp) {
            var $msg = $("#mprRecImportMsg");
            if (!resp || !resp.ok) {
                $msg.removeClass("alert-success").addClass("alert-danger")
                    .text((resp && resp.mensaje) ? resp.mensaje : "No se pudo importar")
                    .show();
                return;
            }
            var extra = "";
            if (resp.errores && resp.errores.length) {
                extra = "<br>" + resp.errores.slice(0, 8).map(mprRecEscape).join("<br>");
                if (resp.errores.length > 8) {
                    extra += "<br>…";
                }
            }
            $msg.removeClass("alert-danger").addClass(resp.errores && resp.errores.length ? "alert-warning" : "alert-success")
                .html(mprRecEscape(resp.mensaje || "Listo") + extra)
                .show();
            mprRecCargarLista();
        }).fail(function () {
            $("#mprRecImportMsg").removeClass("alert-success").addClass("alert-danger")
                .text("No se pudo comunicar con el servidor").show();
        }).always(function () {
            $btn.prop("disabled", false).html('<i class="fa fa-check"></i> Actualizar costos');
        });
    });

    $("#tablaMpRecetas").on("click", ".btnMpRecetasCosto", function () {
        var cod = $(this).attr("data-codpro") || "";
        var row = null;
        for (var i = 0; i < mprRecTodos.length; i++) {
            if (String(mprRecTodos[i].codpro) === String(cod)) {
                row = mprRecTodos[i];
                break;
            }
        }
        if (!row) {
            return;
        }
        $("#mprRecCostoCodpro").val(row.codpro);
        $("#mprRecCostoCodigo").val(row.codpro);
        $("#mprRecCostoDespro").val(row.despro || "");
        $("#mprRecCostoColor").val(row.color || "");
        $("#mprRecCostoActual").val(mprRecFmtNum(row.costo, 4));
        $("#mprRecCostoNuevo").val(Number(row.costo) > 0 ? Number(row.costo) : "");
        $("#modalMpRecetasCosto").modal("show");
        setTimeout(function () {
            $("#mprRecCostoNuevo").focus().select();
        }, 300);
    });

    $("#mprRecBtnGuardarCosto").on("click", function () {
        var codpro = mprRecVal($("#mprRecCostoCodpro"));
        var costo = mprRecVal($("#mprRecCostoNuevo"));
        if (!codpro) {
            return;
        }
        var $btn = $("#mprRecBtnGuardarCosto");
        $btn.prop("disabled", true);
        $.post("ajax/materiaprima/mp-recetas.ajax.php", {
            accion: "guardarCosto",
            codpro: codpro,
            costo: costo
        }, function (resp) {
            $btn.prop("disabled", false);
            if (!resp || !resp.ok) {
                swal({
                    type: "error",
                    title: "No se guardó",
                    text: (resp && resp.mensaje) ? resp.mensaje : "Revisá el costo",
                    confirmButtonText: "Cerrar"
                });
                return;
            }
            for (var i = 0; i < mprRecTodos.length; i++) {
                if (String(mprRecTodos[i].codpro) === String(codpro)) {
                    mprRecTodos[i].costo = Number(resp.costo);
                    break;
                }
            }
            $("#modalMpRecetasCosto").modal("hide");
            mprRecPintarTabla();
        }, "json").fail(function () {
            $btn.prop("disabled", false);
            swal({
                type: "error",
                title: "Error",
                text: "No se pudo comunicar con el servidor",
                confirmButtonText: "Cerrar"
            });
        });
    });

    $("#mprRecCostoNuevo").on("keydown", function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            $("#mprRecBtnGuardarCosto").click();
        }
    });

    $("#tablaMpRecetas").on("click", ".btnMpRecetasVer", function () {
        var cod = $(this).attr("data-codpro") || "";
        var des = $(this).attr("data-despro") || "";
        $("#mprRecDetCodigo").text(cod);
        $("#mprRecDetNombre").text(des);
        $("#mprRecDetLoading").show();
        $("#mprRecDetEmpty").hide();
        $("#mprRecDetWrap").hide();
        $("#mprRecDetBody").empty();
        $("#modalMpRecetasDetalle").modal("show");

        $.post("ajax/materiaprima/mp-recetas.ajax.php", { accion: "recetas", codpro: cod }, function (resp) {
            $("#mprRecDetLoading").hide();
            var rows = (resp && resp.ok && resp.data) ? resp.data : [];
            if (!rows.length) {
                $("#mprRecDetEmpty").show();
                return;
            }
            var puede = $("#mprRecPuedeVerReceta").val() === "1";
            var html = "";
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var modelo = mprRecEscape(r.modelo || "");
                var modeloHtml = puede && r.id
                    ? "<a href='index.php?ruta=editar-receta-modelo&idReceta=" + r.id + "'>" + modelo + "</a>"
                    : modelo;
                html += "<tr>";
                html += "<td>" + modeloHtml + "</td>";
                html += "<td>" + mprRecEscape(r.nombre_modelo || "") + "</td>";
                html += "<td>v" + mprRecEscape(r.version) + "</td>";
                html += "<td>" + mprRecEstadoLabel(r.estado) + "</td>";
                html += "</tr>";
            }
            $("#mprRecDetBody").html(html);
            $("#mprRecDetWrap").show();
        }, "json").fail(function () {
            $("#mprRecDetLoading").hide();
            $("#mprRecDetEmpty").text("No se pudo cargar el detalle.").show();
        });
    });
});
