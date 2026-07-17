var resumenModelosSolicitud = null;
var resumenModelosGeneracion = 0;
var resumenModelosRespuesta = null;

function resumenModelosEscapar(valor) {
    return $("<div>").text(valor === null || valor === undefined ? "" : String(valor)).html();
}

function resumenModelosNumero(valor, decimales) {
    if (valor === null || valor === undefined || valor === "") {
        return "—";
    }
    var numero = Number(valor);
    if (!isFinite(numero)) {
        return "—";
    }
    return numero.toLocaleString("es-PE", {
        minimumFractionDigits: decimales || 0,
        maximumFractionDigits: decimales || 0
    });
}

function resumenModelosMoneda(valor) {
    return valor === null || valor === undefined ? "—" : "S/ " + resumenModelosNumero(valor, 2);
}

function resumenModelosParametros() {
    return {
        id_grupo: $("#resumenModelosGrupo").val() || "",
        anio: $("#resumenModelosAnio").val(),
        mes: $("#resumenModelosMes").val(),
        orden: $("input[name='resumenModelosOrden']:checked").val() || "ventas"
    };
}

function resumenModelosActualizarUrl(parametros) {
    if (!window.history || !window.history.replaceState) {
        return;
    }
    var url = "index.php?ruta=resumen-gerencial-modelos"
        + "&id_grupo=" + encodeURIComponent(parametros.id_grupo)
        + "&anio=" + encodeURIComponent(parametros.anio)
        + "&mes=" + encodeURIComponent(parametros.mes)
        + "&orden=" + encodeURIComponent(parametros.orden);
    window.history.replaceState({}, "", url);
}

function resumenModelosMostrarError(mensaje) {
    $("#resumenModelosError").text(mensaje || "No se pudo cargar el resumen").show();
}

function resumenModelosRankingSemaforo(posicion, total) {
    if (!posicion || !total) {
        return "<span class='resumen-ranking-semaforo sin-dato'><i class='fa fa-minus'></i> Sin ranking</span>";
    }
    var proporcion = Number(posicion) / Number(total);
    var clase = proporcion <= 0.2 ? "alto" : (proporcion <= 0.5 ? "medio" : "bajo");
    var icono = clase === "alto" ? "fa-check" : (clase === "medio" ? "fa-circle" : "fa-times");
    return "<span class='resumen-ranking-semaforo " + clase + "'><i class='fa " + icono + "'></i> # "
        + resumenModelosNumero(posicion, 0) + " de " + resumenModelosNumero(total, 0) + "</span>";
}

function resumenModelosRender(resp) {
    var periodo = resp.periodo || {};
    var orden = $("input[name='resumenModelosOrden']:checked").val() || "ventas";
    var campoOrden = orden === "utilidad" ? "utilidad" : "unidades_vendidas";
    var datosOrdenados = (resp.data || []).slice().sort(function (a, b) {
        var aNulo = a[campoOrden] === null || a[campoOrden] === undefined;
        var bNulo = b[campoOrden] === null || b[campoOrden] === undefined;
        if (aNulo !== bNulo) {
            return aNulo ? 1 : -1;
        }
        var diferencia = Number(b[campoOrden] || 0) - Number(a[campoOrden] || 0);
        return diferencia !== 0
            ? diferencia
            : String(a.modelo || "").localeCompare(String(b.modelo || ""));
    });
    var filas = datosOrdenados.map(function (item) {
        var modelo = String(item.modelo || "");
        var enlace = "index.php?ruta=ficha-gerencial-modelos"
            + "&modelo=" + encodeURIComponent(modelo)
            + "&anio=" + encodeURIComponent(periodo.anio)
            + "&mes=" + encodeURIComponent(periodo.mes);
        var ranking = resumenModelosRankingSemaforo(item.ranking, item.ranking_total);
        var rankingUtilidad = resumenModelosRankingSemaforo(item.ranking_utilidad, item.ranking_utilidad_total);
        var variacionNumero = Number(item.variacion_interanual_pct);
        var variacion = item.variacion_interanual_pct === null
            ? "<span class='text-muted'>Sin base</span>"
            : "<span class='" + (variacionNumero > 0
                ? "resumen-modelos-alza"
                : (variacionNumero < 0 ? "resumen-modelos-baja" : "resumen-modelos-estable"))
                + "'><i class='fa " + (variacionNumero > 0
                    ? "fa-arrow-up"
                    : (variacionNumero < 0 ? "fa-arrow-down" : "fa-minus"))
                + "'></i> " + (variacionNumero > 0 ? "+" : "")
                + resumenModelosNumero(variacionNumero, 1) + "%</span>";
        var costo = item.costo_anio && item.costo_mes
            ? ("0" + item.costo_mes).slice(-2) + "/" + item.costo_anio
            : "<span class='text-muted'>Pendiente</span>";
        if (item.costo_anio && item.costo_mes
            && (Number(item.costo_anio) !== Number(periodo.anio) || Number(item.costo_mes) !== Number(periodo.mes))) {
            costo += "<small>Último anterior</small>";
        }

        return "<tr>"
            + "<td><a href='" + resumenModelosEscapar(enlace) + "' title='Abrir ficha gerencial'><strong>"
            + resumenModelosEscapar(modelo) + "</strong> <i class='fa fa-external-link'></i></a></td>"
            + "<td>" + resumenModelosEscapar(item.nombre || modelo) + "</td>"
            + "<td>" + resumenModelosEscapar(item.marca || "Sin marca")
            + (item.grupo ? "<small>" + resumenModelosEscapar(item.grupo) + "</small>" : "") + "</td>"
            + "<td>" + ranking + "</td>"
            + "<td>" + rankingUtilidad + "</td>"
            + "<td>" + resumenModelosMoneda(item.ventas_acumuladas) + "</td>"
            + "<td>" + resumenModelosNumero(item.unidades_vendidas, 0) + "</td>"
            + "<td>" + resumenModelosMoneda(item.utilidad) + "</td>"
            + "<td>" + (item.margen_pct === null ? "—" : resumenModelosNumero(item.margen_pct, 1) + "%") + "</td>"
            + "<td>" + resumenModelosNumero(item.stock_disponible, 0) + "</td>"
            + "<td>" + resumenModelosNumero(item.rotacion, 2) + "</td>"
            + "<td>" + resumenModelosNumero(item.dias_inventario, 0) + "</td>"
            + "<td>" + variacion + "</td>"
            + "<td>" + costo + "</td>"
            + "</tr>";
    }).join("");

    $("#tablaResumenModelos").html(
        filas || "<tr><td colspan='14' class='text-muted text-center'>No hay modelos para comparar</td></tr>"
    );
}

function resumenModelosCargar() {
    var parametros = resumenModelosParametros();
    resumenModelosGeneracion++;
    var generacion = resumenModelosGeneracion;
    if (resumenModelosSolicitud && resumenModelosSolicitud.readyState !== 4) {
        resumenModelosSolicitud.abort();
    }
    $("#resumenModelosError").hide();
    $(".resumen-modelos-cargando").html("<i class='fa fa-spinner fa-spin'></i> Cargando");
    resumenModelosActualizarUrl(parametros);

    resumenModelosSolicitud = $.ajax({
        url: "ajax/ficha-gerencial-modelos.ajax.php",
        method: "POST",
        dataType: "json",
        data: $.extend({ accion: "resumenComparativo" }, parametros)
    }).done(function (resp) {
        if (generacion !== resumenModelosGeneracion) {
            return;
        }
        if (!resp || !resp.ok) {
            resumenModelosMostrarError(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar el resumen");
            return;
        }
        resumenModelosRespuesta = resp;
        resumenModelosRender(resp);
    }).fail(function (_, estado) {
        if (generacion === resumenModelosGeneracion && estado !== "abort") {
            resumenModelosMostrarError("No se pudo cargar el resumen");
        }
    }).always(function () {
        if (generacion === resumenModelosGeneracion) {
            $(".resumen-modelos-cargando").text("");
        }
    });
}

function resumenModelosCargarGrupos() {
    $.ajax({
        url: "ajax/ficha-gerencial-modelos.ajax.php",
        method: "POST",
        dataType: "json",
        data: { accion: "catalogo" }
    }).done(function (resp) {
        if (!resp || !resp.ok) {
            resumenModelosMostrarError(resp && resp.mensaje ? resp.mensaje : "No se pudieron cargar los grupos");
            return;
        }
        var $grupo = $("#resumenModelosGrupo");
        (resp.grupos || []).forEach(function (item) {
            $grupo.append($("<option>").val(item.id).text(item.nombre));
        });
        $grupo.val(String($grupo.data("inicial") || ""));
        resumenModelosCargar();
    }).fail(function () {
        resumenModelosMostrarError("No se pudieron cargar los grupos");
    });
}

$("#btnCargarResumenModelos").on("click", function (evento) {
    evento.preventDefault();
    resumenModelosCargar();
});
$("#btnLimpiarResumenModelos").on("click", function (evento) {
    evento.preventDefault();
    var hoy = new Date();
    $("#resumenModelosGrupo").val("");
    $("#resumenModelosAnio").val(String(hoy.getFullYear()));
    $("#resumenModelosMes").val(String(hoy.getMonth() + 1));
    $("input[name='resumenModelosOrden'][value='ventas']").prop("checked", true);
    resumenModelosCargar();
});
$("#resumenModelosGrupo, #resumenModelosAnio, #resumenModelosMes").on("change", resumenModelosCargar);
$("input[name='resumenModelosOrden']").on("change", function () {
    resumenModelosActualizarUrl(resumenModelosParametros());
    if (resumenModelosRespuesta) {
        resumenModelosRender(resumenModelosRespuesta);
    }
});

if ($("#zonaResumenModelos").length) {
    resumenModelosCargarGrupos();
}
