var fichaGeneracion = 0;
var fichaSolicitudes = [];
var fichaGraficoUnidades = null;
var fichaGraficosRanking = {
    grupo: null,
    categoria: null,
    subcategoria: null
};
var fichaCatalogoGeneracion = 0;
var fichaResumenActual = null;
var fichaRankingPendiente = null;
var fichaLideresPendiente = null;
var fichaRankingsVariantesPendiente = null;

function fichaEscapar(valor) {
    return $("<div>").text(valor === null || valor === undefined ? "" : String(valor)).html();
}

function fichaNumero(valor, decimales) {
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

function fichaMoneda(valor) {
    return valor === null || valor === undefined ? "—" : "S/ " + fichaNumero(valor, 2);
}

function fichaColorVisual(nombre) {
    var normalizar = function (valor) {
        return String(valor || "").toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
    };
    var texto = normalizar(nombre);
    var paleta = window.fichaModelosConfig && window.fichaModelosConfig.paletaColores
        ? window.fichaModelosConfig.paletaColores
        : {};
    var claves = Object.keys(paleta).sort(function (a, b) {
        return normalizar(b).length - normalizar(a).length;
    });
    var validar = function (color) {
        return /^#[0-9a-f]{6}$/i.test(String(color || "")) ? color : null;
    };
    for (var i = 0; i < claves.length; i++) {
        if (normalizar(claves[i]) === texto) {
            return validar(paleta[claves[i]]) || "#d9dee3";
        }
    }
    for (var j = 0; j < claves.length; j++) {
        if (texto.indexOf(normalizar(claves[j])) !== -1) {
            return validar(paleta[claves[j]]) || "#d9dee3";
        }
    }
    return "#d9dee3";
}

function fichaMetaTexto(meta) {
    if (!meta) {
        return "";
    }
    return "Fuente: " + (meta.fuente || "—") + " · " + (meta.formula || "") + " · Consultado: " + (meta.consultado_en || "—");
}

function fichaYmAClave(ym) {
    var partes = String(ym || "").split("-");
    if (partes.length !== 2) {
        return null;
    }
    return Number(partes[0]) * 100 + Number(partes[1]);
}

function fichaSumarMesesYm(ym, delta) {
    var partes = String(ym || "").split("-");
    if (partes.length !== 2) {
        return "";
    }
    var anio = Number(partes[0]);
    var mes = Number(partes[1]) + Number(delta || 0);
    while (mes > 12) {
        mes -= 12;
        anio += 1;
    }
    while (mes < 1) {
        mes += 12;
        anio -= 1;
    }
    return anio + "-" + ("0" + mes).slice(-2);
}

function fichaMesActualYm() {
    var hoy = new Date();
    return hoy.getFullYear() + "-" + ("0" + (hoy.getMonth() + 1)).slice(-2);
}

function fichaEtiquetaYm(ym) {
    var meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
    var partes = String(ym || "").split("-");
    if (partes.length !== 2) {
        return String(ym || "—");
    }
    return meses[Number(partes[1]) - 1] + " " + partes[0];
}

function fichaEtiquetaPeriodo(desde, hasta) {
    if (!desde || !hasta) {
        return "—";
    }
    return desde === hasta
        ? fichaEtiquetaYm(desde)
        : fichaEtiquetaYm(desde) + " – " + fichaEtiquetaYm(hasta);
}

function fichaMesesEnRango(desde, hasta) {
    var inicio = fichaYmAClave(desde);
    var fin = fichaYmAClave(hasta);
    if (inicio === null || fin === null || inicio > fin) {
        return 0;
    }
    return (Number(String(hasta).slice(0, 4)) - Number(String(desde).slice(0, 4))) * 12
        + (Number(String(hasta).slice(5, 7)) - Number(String(desde).slice(5, 7)))
        + 1;
}

function fichaValidarPeriodo(desde, hasta) {
    if (!desde || !hasta) {
        return "Selecciona el período";
    }
    if (fichaYmAClave(desde) > fichaYmAClave(hasta)) {
        return "El inicio del período no puede ser posterior al fin";
    }
    var meses = fichaMesesEnRango(desde, hasta);
    if (meses < 1 || meses > 12) {
        return "El período máximo permitido es de 12 meses";
    }
    if (fichaYmAClave(hasta) > fichaYmAClave(fichaMesActualYm())) {
        return "No se pueden consultar meses futuros";
    }
    return "";
}

function fichaActualizarPeriodoUi(desde, hasta) {
    $("#fichaFiltroDesde").val(desde);
    $("#fichaFiltroHasta").val(hasta);
    $("#fichaFiltroPeriodoTexto").text(fichaEtiquetaPeriodo(desde, hasta));
    $("#fichaPeriodoDesdeSelect").val(desde);
    $("#fichaPeriodoHastaSelect").val(hasta);
}

function fichaOpcionesMesesDisponibles() {
    var opciones = [];
    var hoy = new Date();
    var anioMax = hoy.getFullYear();
    var mesMax = hoy.getMonth() + 1;
    for (var anio = anioMax; anio >= 2021; anio--) {
        var limiteMes = anio === anioMax ? mesMax : 12;
        for (var mes = limiteMes; mes >= 1; mes--) {
            opciones.push(anio + "-" + ("0" + mes).slice(-2));
        }
    }
    return opciones;
}

function fichaRellenarSelectoresPeriodo() {
    var opciones = fichaOpcionesMesesDisponibles();
    var html = opciones.map(function (ym) {
        return "<option value='" + ym + "'>" + fichaEtiquetaYm(ym) + "</option>";
    }).join("");
    $("#fichaPeriodoDesdeSelect, #fichaPeriodoHastaSelect").html(html);
}

function fichaPresetPeriodo(preset) {
    var actual = fichaMesActualYm();
    var desde = actual;
    var hasta = actual;
    if (preset === "ultimos_3") {
        desde = fichaSumarMesesYm(actual, -2);
    } else if (preset === "ultimos_6") {
        desde = fichaSumarMesesYm(actual, -5);
    } else if (preset === "ultimos_12") {
        desde = fichaSumarMesesYm(actual, -11);
    } else if (preset === "anio_actual") {
        desde = String(new Date().getFullYear()) + "-01";
        hasta = actual;
    } else if (preset === "anio_anterior") {
        var anioAnterior = new Date().getFullYear() - 1;
        desde = anioAnterior + "-01";
        hasta = anioAnterior + "-12";
    }
    if (fichaYmAClave(desde) < 202101) {
        desde = "2021-01";
    }
    return { desde: desde, hasta: hasta };
}

function fichaCerrarPeriodoPanel() {
    $("#fichaPeriodoPanel").hide();
}

function fichaAbrirPeriodoPanel() {
    var desde = $("#fichaFiltroDesde").val() || fichaMesActualYm();
    var hasta = $("#fichaFiltroHasta").val() || desde;
    $("#fichaPeriodoDesdeSelect").val(desde);
    $("#fichaPeriodoHastaSelect").val(hasta);
    $("#fichaPeriodoPanel").show();
}

function fichaAplicarPeriodoSeleccion(recargar) {
    var desde = $("#fichaPeriodoDesdeSelect").val();
    var hasta = $("#fichaPeriodoHastaSelect").val();
    var errorPeriodo = fichaValidarPeriodo(desde, hasta);
    if (errorPeriodo) {
        alert(errorPeriodo);
        return false;
    }
    fichaActualizarPeriodoUi(desde, hasta);
    fichaCerrarPeriodoPanel();
    if (recargar && $("#fichaFiltroModelo").val()) {
        fichaCargarTodo();
    }
    return true;
}

function fichaInicializarPeriodoPicker() {
    if (!$("#fichaFiltroPeriodo").length) {
        return;
    }
    fichaRellenarSelectoresPeriodo();
    var desde = $("#fichaFiltroDesde").val() || fichaMesActualYm();
    var hasta = $("#fichaFiltroHasta").val() || desde;
    fichaActualizarPeriodoUi(desde, hasta);

    $("#fichaFiltroPeriodo").on("click", function (evento) {
        evento.stopPropagation();
        if ($("#fichaPeriodoPanel").is(":visible")) {
            fichaCerrarPeriodoPanel();
        } else {
            fichaAbrirPeriodoPanel();
        }
    });
    $("#fichaPeriodoPanel").on("click", function (evento) {
        evento.stopPropagation();
    });
    $(document).on("click.fichaPeriodo", function () {
        fichaCerrarPeriodoPanel();
    });
    $(".ficha-periodo-preset").on("click", function () {
        var preset = fichaPresetPeriodo($(this).data("preset"));
        fichaActualizarPeriodoUi(preset.desde, preset.hasta);
        fichaAplicarPeriodoSeleccion(true);
    });
    $("#fichaPeriodoAplicar").on("click", function () {
        fichaAplicarPeriodoSeleccion(true);
    });
    $("#fichaPeriodoCancelar").on("click", function () {
        fichaCerrarPeriodoPanel();
    });
}

function fichaParametros() {
    return {
        modelo: $("#fichaFiltroModelo").val() || "",
        desde: $("#fichaFiltroDesde").val() || "",
        hasta: $("#fichaFiltroHasta").val() || ""
    };
}

function fichaValidarParametros(parametros) {
    if (!parametros.modelo) {
        return "Selecciona un modelo";
    }
    return fichaValidarPeriodo(parametros.desde, parametros.hasta);
}

function fichaAbortarSolicitudes() {
    fichaSolicitudes.forEach(function (xhr) {
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
        }
    });
    fichaSolicitudes = [];
}

function fichaPost(accion, extras, generacion) {
    var datos = $.extend({ accion: accion }, extras || {});
    var xhr = $.ajax({
        url: "ajax/ficha-gerencial-modelos.ajax.php",
        method: "POST",
        data: datos,
        dataType: "json"
    });
    fichaSolicitudes.push(xhr);
    return xhr.then(function (resp) {
        if (generacion !== undefined && generacion !== fichaGeneracion) {
            return $.Deferred().reject({ obsoleta: true }).promise();
        }
        return resp;
    });
}

function fichaEstadoZona(id, estado, mensaje) {
    var $zona = $("#" + id);
    $zona.removeClass("ficha-error");
    if (estado === "cargando") {
        $zona.find(".ficha-cargando").html("<i class='fa fa-spinner fa-spin'></i> Cargando");
    } else if (estado === "error") {
        $zona.addClass("ficha-error");
        $zona.find(".ficha-cargando").text(mensaje || "Error");
    } else {
        $zona.find(".ficha-cargando").text("");
    }
}

function fichaMostrarErrorGlobal(mensaje) {
    $("#fichaContenido").hide();
    $("#fichaMensajeGlobal").removeClass("alert-info alert-success").addClass("alert-danger").text(mensaje).show();
}

function fichaActualizarUrl(parametros) {
    if (!window.history || !window.history.replaceState) {
        return;
    }
    var url = "index.php?ruta=ficha-gerencial-modelos&modelo=" + encodeURIComponent(parametros.modelo)
        + "&desde=" + encodeURIComponent(parametros.desde)
        + "&hasta=" + encodeURIComponent(parametros.hasta);
    window.history.replaceState({}, "", url);
}

function fichaBindModeloChange() {
    var $modelo = $("#fichaFiltroModelo");
    $modelo.off("change.fichaAuto changed.bs.select.fichaAuto");
    // selectpicker dispara changed.bs.select; el change nativo a veces no basta
    if (typeof $modelo.selectpicker === "function") {
        $modelo.on("changed.bs.select.fichaAuto", fichaCargarTodo);
    } else {
        $modelo.on("change.fichaAuto", fichaCargarTodo);
    }
}

function fichaLimpiarCajasLideres() {
    $("#fichaColorLiderNombre, #fichaColorLiderParticipacion, #fichaColorLiderVentas, #fichaColorLiderUtilidad").text("—");
    $("#fichaTallaLiderNombre, #fichaTallaLiderParticipacion, #fichaTallaLiderRotacion, #fichaTallaLiderMargen").text("—");
    $("#fichaZonaLiderNombre, #fichaZonaLiderParticipacion, #fichaZonaLiderVentas, #fichaZonaLiderUnidades").text("—");
    $("#fichaVendedorLiderNombre, #fichaVendedorLiderParticipacion, #fichaVendedorLiderVentas").text("—");
    $("#fichaVendedorLiderUtilidad").text("—");
    $("#fichaClienteLiderNombre, #fichaClienteLiderVentas, #fichaClienteLiderUnidades, #fichaClienteLiderUltimaCompra").text("—");
}

function fichaCargarCatalogo(seleccionarModelo, cargarDespues) {
    fichaCatalogoGeneracion++;
    var generacionCatalogo = fichaCatalogoGeneracion;
    var marca = $("#fichaFiltroMarca").val() || "";
    fichaPost("catalogo", { id_marca: marca }).done(function (resp) {
        if (generacionCatalogo !== fichaCatalogoGeneracion) {
            return;
        }
        if (!resp || !resp.ok) {
            fichaMostrarErrorGlobal(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar el catálogo");
            return;
        }
        if ($("#fichaFiltroMarca option").length <= 1) {
            (resp.marcas || []).forEach(function (item) {
                $("#fichaFiltroMarca").append($("<option>").val(item.id).text(item.marca));
            });
        }
        var $modelos = $("#fichaFiltroModelo").empty();
        (resp.modelos || []).forEach(function (item) {
            $modelos.append($("<option>").val(item.modelo).text(item.modelo + " — " + item.nombre));
        });
        if (seleccionarModelo) {
            $modelos.val(String(seleccionarModelo));
        }
        if (!$modelos.val() && $modelos.find("option").length) {
            $modelos.prop("selectedIndex", 0);
        }
        if (typeof $modelos.selectpicker === "function") {
            $modelos.selectpicker("refresh");
        }
        fichaBindModeloChange();
        if (cargarDespues && $modelos.val()) {
            fichaCargarTodo();
        }
    });
}

function fichaRenderResumen(resp) {
    fichaResumenActual = resp;
    var cabecera = resp.cabecera || {};
    var ventas = resp.ventas || {};
    var inventario = resp.inventario || {};
    var renta = resp.rentabilidad || {};

    $("#fichaModeloImagen").attr("src", cabecera.imagen || "vistas/img/modelos/default/anonymous.png");
    $("#fichaModeloNombre").text(cabecera.nombre || cabecera.modelo || "—");
    $("#fichaModeloCodigo").text(cabecera.modelo || "—");
    $("#fichaModeloMarca").text(cabecera.marca || "—");
    $("#fichaModeloEstado").text(cabecera.estado || "Activo");
    var periodo = resp.periodo || {};
    $("#fichaModeloPeriodo").text(fichaEtiquetaPeriodo(periodo.desde, periodo.hasta)
        + (periodo.parcial ? " · parcial" : ""));
    $("#fichaMetaCabecera").text(fichaMetaTexto(resp.meta && resp.meta.ventas));

    $("#fichaModeloCategoria").text("—");
    $("#fichaModeloSubcategoria").text("—");
    fichaPintarRank("#fichaRankGeneral", null);
    fichaPintarRank("#fichaRankCategoria", null);
    fichaPintarRank("#fichaRankSubcategoria", null);
    $("#fichaClasificarLink").hide();
    $("#kpiVentasAcumuladas").text(resp.ventas_acumuladas === null ? "Sin precio" : fichaMoneda(resp.ventas_acumuladas));
    $("#kpiPrecioLista9").text(resp.precio_lista9 === null ? "Lista 9 no registrada" : "Lista 9: " + fichaMoneda(resp.precio_lista9));
    $("#kpiUnidades").text(fichaNumero(ventas.unidades_vendidas, 0));
    $("#kpiStockDisponible").text(fichaNumero(inventario.stock_disponible, 0));
    $("#kpiRotacionPromedio").text(resp.rotacion_promedio === null ? "—" : fichaNumero(resp.rotacion_promedio, 2));
    $("#kpiDiasInventario").text(resp.dias_inventario === null ? "—" : fichaNumero(resp.dias_inventario, 0) + " días");

    if (resp.precio_lista9 === null || resp.precio_lista9 === undefined) {
        $("#kpiUtilidad, #kpiMargen").text("Pendiente");
        $("#kpiCostoVenta").text("Falta precio de lista 9");
        $("#kpiCostoEstado").text("No se puede valorizar la venta");
    } else if (renta.costo_unitario === null || renta.costo_unitario === undefined) {
        $("#kpiUtilidad, #kpiMargen").text("Pendiente");
        $("#kpiCostoVenta").text("Costo pendiente de aprobación");
        $("#kpiCostoEstado").text("No se muestran valores ficticios");
    } else {
        $("#kpiUtilidad").text(fichaMoneda(renta.utilidad));
        $("#kpiMargen").text(fichaNumero(renta.margen_pct, 2) + "%");
        $("#kpiCostoVenta").text("Costo de venta: " + fichaMoneda(renta.costo_venta));
        var origenCosto = renta.costo_arrastrado
            ? " · costo de " + ("0" + renta.costo_mes).slice(-2) + "/" + renta.costo_anio
            : "";
        $("#kpiCostoEstado").text("Costo unitario: " + fichaMoneda(renta.costo_unitario) + origenCosto);
    }
    $("#preguntaMargenPromedio").text(renta.margen_pct !== null && renta.margen_pct !== undefined
        ? fichaNumero(renta.margen_pct, 1) + "%"
        : "");

    var tela = resp.tela_principal || null;
    $("#preguntaTelaPrincipal").text(tela && tela.etiqueta ? tela.etiqueta : "Sin dato");

    // Reaplicar bloques que dependen del resumen (pueden haber llegado antes)
    if (fichaRankingPendiente) {
        fichaAplicarRanking(fichaRankingPendiente);
    }
    if (fichaLideresPendiente) {
        fichaRenderLideresComerciales(fichaLideresPendiente);
    }
    if (fichaRankingsVariantesPendiente) {
        fichaRenderLideres(fichaRankingsVariantesPendiente);
    }
}

function fichaClaseRanking(bloque) {
    if (!bloque || bloque.posicion == null || !bloque.total) {
        return "rank-na";
    }
    var ratio = Number(bloque.posicion) / Number(bloque.total);
    if (ratio <= 0.25) {
        return "rank-alto";
    }
    if (ratio <= 0.5) {
        return "rank-medio-alto";
    }
    if (ratio <= 0.75) {
        return "rank-medio";
    }
    return "rank-bajo";
}

function fichaPintarRank(selector, bloque) {
    var $el = $(selector);
    var $item = $el.closest(".ficha-rank-item");
    $item.removeClass("rank-oro rank-alto rank-medio-alto rank-medio rank-bajo rank-na");
    if (!bloque) {
        $el.text("…");
        $item.addClass("rank-na");
        return;
    }
    $el.text(
        bloque.posicion
            ? ("# " + bloque.posicion + (bloque.total ? " / " + bloque.total : ""))
            : "—"
    );
    $item.addClass(fichaClaseRanking(bloque));
}

function fichaAplicarRanking(resp) {
    if (!fichaResumenActual || !resp) {
        return;
    }
    fichaResumenActual.ranking_general = resp.ranking_general || {};
    var ranking = fichaResumenActual.ranking_general || {};
    var ranks = ranking.ranking || {};
    var gen = ranks.general || {};
    var catRank = ranks.categoria || {};
    var subRank = ranks.subcategoria || {};
    var cat = ranking.categoria && ranking.categoria.nombre ? ranking.categoria.nombre : "";
    var sub = ranking.subcategoria && ranking.subcategoria.nombre ? ranking.subcategoria.nombre : "";

    $("#fichaModeloCategoria").text(cat || (ranking.estado === "sin_clasificacion" ? "Sin clasificar" : "—"));
    $("#fichaModeloSubcategoria").text(
        sub || (ranking.estado === "parcial" ? "Pendiente" : "—")
    );

    fichaPintarRank("#fichaRankGeneral", gen);
    fichaPintarRank("#fichaRankCategoria", catRank);
    fichaPintarRank("#fichaRankSubcategoria", subRank);

    if (ranking.estado === "sin_grupo") {
        $("#fichaClasificarLink").hide();
        $("#preguntaRankingModelo").text("");
        return;
    }

    if (ranking.estado === "sin_clasificacion" || ranking.estado === "parcial") {
        var textoParcial = ranking.estado === "parcial" && catRank.posicion
            ? ("Cat # " + catRank.posicion + (catRank.total ? " de " + catRank.total : ""))
            : (gen.posicion ? ("Grupo # " + gen.posicion + (gen.total ? " de " + gen.total : "")) : "");
        $("#preguntaRankingModelo").text(textoParcial);
        if (window.fichaModelosConfig && window.fichaModelosConfig.puedeEditarCategoriasModelos) {
            var codigoModelo = $("#fichaModeloCodigo").text() || "";
            $("#fichaClasificarHref").attr(
                "href",
                "index.php?ruta=categorias-modelos&modelo=" + encodeURIComponent(codigoModelo)
            );
            $("#fichaClasificarLink").show();
        } else {
            $("#fichaClasificarLink").hide();
        }
        return;
    }

    $("#fichaClasificarLink").hide();
    var partes = [];
    if (gen.posicion) {
        partes.push("Grupo #" + gen.posicion);
    }
    if (catRank.posicion) {
        partes.push("Cat #" + catRank.posicion);
    }
    if (subRank.posicion) {
        partes.push("Sub #" + subRank.posicion);
    }
    $("#preguntaRankingModelo").text(partes.join(" · "));
}

function fichaRenderRanking(resp) {
    fichaRankingPendiente = resp || null;
    if (!fichaResumenActual) {
        // El resumen aún no llegó; se aplicará al terminar fichaRenderResumen
        return;
    }
    fichaAplicarRanking(resp);
}

function fichaRenderLideresComerciales(resp) {
    fichaLideresPendiente = resp || null;
    var base = fichaResumenActual || resp;
    var lideres = resp.lideres_comerciales || {};
    var zona = lideres.zona || null;
    var vendedor = lideres.vendedor || null;
    var cliente = lideres.cliente || null;
    var ventaTotal = Number(base.ventas && base.ventas.venta_neta || 0);
    var costoUnitario = base.rentabilidad
        && base.rentabilidad.costo_unitario !== null
        && base.rentabilidad.costo_unitario !== undefined
        ? Number(base.rentabilidad.costo_unitario)
        : null;
    var participacion = function (item) {
        return item && ventaTotal > 0
            ? fichaNumero(Number(item.venta_neta || 0) * 100 / ventaTotal, 0) + "%"
            : "—";
    };
    var fechaCorta = function (fecha) {
        var partes = String(fecha || "").substring(0, 10).split("-");
        return partes.length === 3 ? partes[2] + "/" + partes[1] + "/" + partes[0] : "—";
    };

    $("#fichaZonaLiderNombre").text(zona ? zona.nombre : "—");
    $("#fichaZonaLiderParticipacion").text(participacion(zona));
    $("#fichaZonaLiderVentas").text(zona ? fichaMoneda(zona.venta_neta) : "—");
    $("#fichaZonaLiderUnidades").text(zona ? fichaNumero(zona.unidades_vendidas, 0) : "—");

    $("#fichaVendedorLiderNombre").text(vendedor ? vendedor.nombre : "—");
    $("#fichaVendedorLiderParticipacion").text(participacion(vendedor));
    $("#fichaVendedorLiderVentas").text(vendedor ? fichaMoneda(vendedor.venta_neta) : "—");
    $("#fichaVendedorLiderUtilidad").text(vendedor && costoUnitario !== null
        ? fichaMoneda(Number(vendedor.venta_neta || 0) - Number(vendedor.unidades_vendidas || 0) * costoUnitario)
        : "Pendiente");

    $("#fichaClienteLiderNombre").text(cliente ? cliente.nombre : "—");
    $("#fichaClienteLiderVentas").text(cliente ? fichaMoneda(cliente.venta_neta) : "—");
    $("#fichaClienteLiderUnidades").text(cliente ? fichaNumero(cliente.unidades_vendidas, 0) : "—");
    $("#fichaClienteLiderUltimaCompra").text(cliente ? fechaCorta(cliente.ultima_compra) : "—");

    var zonas = lideres.zonas || [];
    var totalVentasZonas = zonas.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    var totalUnidadesZonas = zonas.reduce(function (total, item) {
        return total + Number(item.unidades_vendidas || 0);
    }, 0);
    var filasZonas = zonas.map(function (item) {
        var participacionZona = totalVentasZonas !== 0
            ? Number(item.venta_neta || 0) * 100 / totalVentasZonas
            : 0;
        return "<tr><td>" + fichaEscapar(item.nombre || "Sin zona") + "</td>"
            + "<td>" + fichaMoneda(item.venta_neta) + "</td>"
            + "<td>" + fichaNumero(participacionZona, 1) + "%</td>"
            + "<td>" + fichaNumero(item.unidades_vendidas, 0) + "</td></tr>";
    }).join("");
    $("#tablaVentasZonaFicha").html(filasZonas || "<tr><td colspan='4' class='text-muted text-center'>Sin ventas por zona</td></tr>");
    $("#totalVentasZonaFicha").text(fichaMoneda(totalVentasZonas));
    $("#totalUnidadesZonaFicha").text(fichaNumero(totalUnidadesZonas, 0));

    var vendedores = lideres.vendedores || [];
    var totalVentasVendedores = vendedores.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    var totalCantidadVendedores = vendedores.reduce(function (total, item) {
        return total + Number(item.unidades_vendidas || 0);
    }, 0);
    var vendedoresTabla = vendedores.slice(0, 5);
    if (vendedores.length > 5) {
        vendedoresTabla.push(vendedores.slice(5).reduce(function (otros, item) {
            otros.venta_neta += Number(item.venta_neta || 0);
            otros.unidades_vendidas += Number(item.unidades_vendidas || 0);
            return otros;
        }, { nombre: "Otros vendedores", venta_neta: 0, unidades_vendidas: 0, es_otros: true }));
    }
    var filasVendedores = vendedoresTabla.map(function (item, indice) {
        var participacionVendedor = totalVentasVendedores !== 0
            ? Number(item.venta_neta || 0) * 100 / totalVentasVendedores
            : 0;
        var nombreVendedor = item.es_otros
            ? item.nombre
            : (indice + 1) + ". " + (item.nombre || item.codigo || "Sin vendedor");
        return "<tr" + (item.es_otros ? " class='ficha-fila-otros'" : "") + "><td>"
            + fichaEscapar(nombreVendedor) + "</td>"
            + "<td>" + fichaMoneda(item.venta_neta) + "</td>"
            + "<td>" + fichaNumero(item.unidades_vendidas, 0) + "</td>"
            + "<td>" + fichaNumero(participacionVendedor, 1) + "%</td></tr>";
    }).join("");
    $("#tablaTopVendedoresFicha").html(filasVendedores || "<tr><td colspan='4' class='text-muted text-center'>Sin ventas por vendedor</td></tr>");
    $("#totalVentasVendedoresFicha").text(fichaMoneda(totalVentasVendedores));
    $("#totalCantidadVendedoresFicha").text(fichaNumero(totalCantidadVendedores, 0));

    var clientes = lideres.clientes || [];
    var totalVentasClientes = clientes.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    var totalUnidadesClientes = clientes.reduce(function (total, item) {
        return total + Number(item.unidades_vendidas || 0);
    }, 0);
    var clientesTabla = clientes.slice(0, 5);
    if (clientes.length > 5) {
        clientesTabla.push(clientes.slice(5).reduce(function (otros, item) {
            otros.venta_neta += Number(item.venta_neta || 0);
            otros.unidades_vendidas += Number(item.unidades_vendidas || 0);
            return otros;
        }, { nombre: "Otros clientes", venta_neta: 0, unidades_vendidas: 0, es_otros: true }));
    }
    var filasClientes = clientesTabla.map(function (item, indice) {
        var participacionCliente = totalVentasClientes !== 0
            ? Number(item.venta_neta || 0) * 100 / totalVentasClientes
            : 0;
        var nombreCliente = item.es_otros
            ? item.nombre
            : (indice + 1) + ". " + (item.nombre || item.codigo || "Sin cliente");
        return "<tr" + (item.es_otros ? " class='ficha-fila-otros'" : "") + "><td>"
            + fichaEscapar(nombreCliente) + "</td>"
            + "<td>" + fichaMoneda(item.venta_neta) + "</td>"
            + "<td>" + fichaNumero(item.unidades_vendidas, 0) + "</td>"
            + "<td>" + fichaNumero(participacionCliente, 1) + "%</td></tr>";
    }).join("");
    $("#tablaTopClientesFicha").html(filasClientes || "<tr><td colspan='4' class='text-muted text-center'>Sin ventas por cliente</td></tr>");
    $("#totalVentasClientesFicha").text(fichaMoneda(totalVentasClientes));
    $("#totalUnidadesClientesFicha").text(fichaNumero(totalUnidadesClientes, 0));

    $("#preguntaZonaMayorVenta").text(zona ? zona.nombre : "");
    $("#preguntaVendedorMayorVenta").text(vendedor ? vendedor.nombre : "");
}

function fichaRenderVariantes(resp) {
    var filas = resp.data || [];
    var colores = [];
    var tallas = [];
    var colorVisto = {};
    var tallaVista = {};
    var mapa = {};
    filas.forEach(function (fila) {
        var activos = Number(fila.articulos_activos || 0);
        if (!colorVisto[fila.cod_color]) {
            colorVisto[fila.cod_color] = {
                codigo: fila.cod_color,
                nombre: fila.color,
                articulos_activos: 0
            };
            colores.push(colorVisto[fila.cod_color]);
        }
        colorVisto[fila.cod_color].articulos_activos += activos;
        if (!tallaVista[fila.cod_talla]) {
            tallaVista[fila.cod_talla] = true;
            tallas.push({ codigo: fila.cod_talla, nombre: fila.talla });
        }
        mapa[fila.cod_color + "|" + fila.cod_talla] = fila;
    });
    $("#fichaColoresDisponibles").html(colores.map(function (item) {
        var inactivo = Number(item.articulos_activos || 0) <= 0;
        return "<span class='ficha-color-item" + (inactivo ? " ficha-color-item--inactivo" : "") + "'"
            + (inactivo ? " title='Color sin artículos activos'" : "")
            + "><i style='background-color:"
            + fichaColorVisual(item.nombre) + "'></i><span>" + fichaEscapar(item.nombre) + "</span></span>";
    }).join("") || "Sin colores");
    $("#fichaTallasDisponibles").html(tallas.map(function (item) {
        return "<span class='ficha-talla-item'>" + fichaEscapar(item.nombre) + "</span>";
    }).join("") || "Sin tallas");

    var resumenPorColor = {};
    var totalVentasMatriz = 0;
    var totalPromedioMensualMatriz = 0;
    var totalStockMatriz = 0;
    var totalUnidadesMatriz = 0;
    var totalesPorTalla = {};
    var ventaMaximaCombinacion = 0;
    filas.forEach(function (fila) {
        var venta = Number(fila.venta_neta || 0);
        var unidades = Number(fila.unidades_vendidas || 0);
        var stock = Number(fila.stock_disponible || 0);
        var promedio = Number(fila.promedio_mensual_unidades || 0);
        if (!totalesPorTalla[fila.cod_talla]) {
            totalesPorTalla[fila.cod_talla] = { unidades: 0, venta: 0 };
        }
        totalesPorTalla[fila.cod_talla].unidades += unidades;
        totalesPorTalla[fila.cod_talla].venta += venta;
        if (!resumenPorColor[fila.cod_color]) {
            resumenPorColor[fila.cod_color] = {
                venta_neta: 0,
                promedio_mensual_unidades: 0,
                stock_disponible: 0,
                unidades_vendidas: 0
            };
        }
        resumenPorColor[fila.cod_color].venta_neta += venta;
        resumenPorColor[fila.cod_color].promedio_mensual_unidades += promedio;
        resumenPorColor[fila.cod_color].stock_disponible += stock;
        resumenPorColor[fila.cod_color].unidades_vendidas += unidades;
        totalVentasMatriz += venta;
        totalPromedioMensualMatriz += promedio;
        totalStockMatriz += stock;
        totalUnidadesMatriz += unidades;
        ventaMaximaCombinacion = Math.max(ventaMaximaCombinacion, venta);
    });
    var nivelSemaforo = function (fila) {
        var venta = Math.max(0, Number(fila.venta_neta || 0));
        var porcentaje = ventaMaximaCombinacion > 0 ? venta * 100 / ventaMaximaCombinacion : 0;
        if (venta <= 0) {
            return { clase: "sin-movimiento", porcentaje: 0 };
        }
        if (porcentaje >= 80) {
            return { clase: "excelente", porcentaje: porcentaje };
        }
        if (porcentaje >= 60) {
            return { clase: "bueno", porcentaje: porcentaje };
        }
        if (porcentaje >= 40) {
            return { clase: "regular", porcentaje: porcentaje };
        }
        if (porcentaje >= 20) {
            return { clase: "bajo", porcentaje: porcentaje };
        }
        return { clase: "muy-bajo", porcentaje: porcentaje };
    };
    var html = "<thead><tr><th rowspan='2'>Color / Talla</th>";
    if (tallas.length) {
        html += "<th colspan='" + tallas.length + "' class='ficha-matriz-grupo'>Combinaciones color × talla</th>";
    }
    html += "<th colspan='5' class='ficha-matriz-resumen ficha-matriz-grupo'>Resumen agrupado por color</th></tr><tr>";
    tallas.forEach(function (talla) {
        html += "<th>" + fichaEscapar(talla.nombre) + "</th>";
    });
    html += "<th class='ficha-matriz-resumen'>Ventas (S/)</th>"
        + "<th class='ficha-matriz-resumen'>Prom. und./mes</th>"
        + "<th class='ficha-matriz-resumen'>Stock</th>"
        + "<th class='ficha-matriz-resumen'>Rotación</th>"
        + "<th class='ficha-matriz-resumen'>Participación</th>";
    html += "</tr></thead><tbody>";
    colores.forEach(function (color) {
        html += "<tr><td class='ficha-matriz-color'><i style='background-color:"
            + fichaColorVisual(color.nombre) + "'></i>" + fichaEscapar(color.nombre) + "</td>";
        tallas.forEach(function (talla) {
            var fila = mapa[color.codigo + "|" + talla.codigo];
            if (fila) {
                var nivel = nivelSemaforo(fila);
                var iconoNivel = nivel.clase === "sin-movimiento"
                    ? "fa-minus"
                    : (nivel.clase === "muy-bajo"
                        ? "fa-times"
                        : (nivel.clase === "regular" || nivel.clase === "bajo" ? "fa-circle" : "fa-check"));
                html += "<td class='ficha-matriz-td'><span class='ficha-semaforo-celda " + nivel.clase
                    + "' title='Ventas frente a la combinación líder: " + fichaNumero(nivel.porcentaje, 1)
                    + "% · Ventas: " + fichaMoneda(fila.venta_neta)
                    + " · Vendidas: " + fichaNumero(fila.unidades_vendidas, 0)
                    + " · Disponibles: " + fichaNumero(fila.stock_disponible, 0) + "'>"
                    + "<i class='fa " + iconoNivel + "'></i></span></td>";
            } else {
                html += "<td class='text-muted'>—</td>";
            }
        });
        var resumenColor = resumenPorColor[color.codigo] || {};
        var ventaColor = Number(resumenColor.venta_neta || 0);
        var participacionColor = totalVentasMatriz !== 0 ? ventaColor * 100 / totalVentasMatriz : 0;
        var rotacionColor = Number(resumenColor.stock_disponible || 0) > 0
            ? fichaNumero(Number(resumenColor.unidades_vendidas || 0) / Number(resumenColor.stock_disponible), 2)
            : "—";
        html += "<td class='ficha-matriz-venta'>" + fichaMoneda(ventaColor) + "</td>"
            + "<td class='ficha-matriz-resumen-valor'>" + fichaNumero(resumenColor.promedio_mensual_unidades, 1) + "</td>"
            + "<td class='ficha-matriz-resumen-valor'>" + fichaNumero(resumenColor.stock_disponible, 0) + "</td>"
            + "<td class='ficha-matriz-resumen-valor'>" + rotacionColor + "</td>"
            + "<td class='ficha-matriz-participacion'>" + fichaNumero(participacionColor, 0) + "%</td>";
        html += "</tr>";
    });
    if (colores.length) {
        var rotacionTotal = totalStockMatriz > 0
            ? fichaNumero(totalUnidadesMatriz / totalStockMatriz, 2)
            : "—";
        html += "</tbody><tfoot><tr><td class='ficha-matriz-total-label'>Total</td>";
        tallas.forEach(function (talla) {
            var totalTalla = totalesPorTalla[talla.codigo] || { unidades: 0, venta: 0 };
            html += "<td class='ficha-matriz-total-celda' title='Unidades: "
                + fichaNumero(totalTalla.unidades, 0)
                + " · Ventas: " + fichaMoneda(totalTalla.venta) + "'>"
                + fichaNumero(totalTalla.unidades, 0) + "</td>";
        });
        html += "<td class='ficha-matriz-venta ficha-matriz-total-valor'>" + fichaMoneda(totalVentasMatriz) + "</td>"
            + "<td class='ficha-matriz-resumen-valor ficha-matriz-total-valor'>" + fichaNumero(totalPromedioMensualMatriz, 1) + "</td>"
            + "<td class='ficha-matriz-resumen-valor ficha-matriz-total-valor'>" + fichaNumero(totalStockMatriz, 0) + "</td>"
            + "<td class='ficha-matriz-resumen-valor ficha-matriz-total-valor'>" + rotacionTotal + "</td>"
            + "<td class='ficha-matriz-participacion ficha-matriz-total-valor'>100%</td>"
            + "</tr></tfoot>";
    } else {
        html += "</tbody>";
    }
    $("#fichaMatrizVariantes").html(html);
    $("#zonaVariantes .ficha-fuente").text(fichaMetaTexto(resp.meta));
    var rankings = fichaRankingsDesdeVariantes(filas, resp.meta);
    fichaRenderLideres(rankings);
    fichaRenderPreguntasVariantes(rankings);
    fichaRenderRankings(rankings);
    fichaEstadoZona("zonaRankings", "ok");
}

function fichaRankingsDesdeVariantes(filas, meta) {
    var colores = {};
    var tallas = {};
    var combinaciones = [];
    (filas || []).forEach(function (fila) {
        if (!colores[fila.cod_color]) {
            colores[fila.cod_color] = { nombre: fila.color, venta_neta: 0, unidades: 0, stock: 0 };
        }
        if (!tallas[fila.cod_talla]) {
            tallas[fila.cod_talla] = { nombre: fila.talla, venta_neta: 0, unidades: 0, stock: 0 };
        }
        colores[fila.cod_color].venta_neta += Number(fila.venta_neta || 0);
        colores[fila.cod_color].unidades += Number(fila.unidades_vendidas || 0);
        colores[fila.cod_color].stock += Number(fila.stock_disponible || 0);
        tallas[fila.cod_talla].venta_neta += Number(fila.venta_neta || 0);
        tallas[fila.cod_talla].unidades += Number(fila.unidades_vendidas || 0);
        tallas[fila.cod_talla].stock += Number(fila.stock_disponible || 0);
        combinaciones.push({
            cod_color: fila.cod_color, cod_talla: fila.cod_talla,
            color: fila.color, talla: fila.talla, venta_neta: Number(fila.venta_neta || 0),
            unidades: Number(fila.unidades_vendidas || 0), stock: Number(fila.stock_disponible || 0),
            pedidos: Number(fila.pedidos || 0),
            promedio_mensual_unidades: Number(fila.promedio_mensual_unidades || 0)
        });
    });
    var ordenar = function (a, b) { return b.venta_neta - a.venta_neta; };
    var listaColores = Object.keys(colores).map(function (clave) { return colores[clave]; }).sort(ordenar);
    var listaTallas = Object.keys(tallas).map(function (clave) { return tallas[clave]; }).sort(ordenar);
    combinaciones.sort(ordenar);
    var totalVentas = listaColores.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    return {
        colores: listaColores.slice(0, 10),
        tallas: listaTallas.slice(0, 10),
        todos_colores: listaColores,
        todas_tallas: listaTallas,
        combinaciones: combinaciones.slice(0, 10),
        todas_combinaciones: combinaciones,
        total_ventas: totalVentas,
        meta: meta
    };
}

function fichaRenderPreguntasVariantes(resp) {
    var colorLider = resp.colores && resp.colores.length ? resp.colores[0] : null;
    $("#preguntaColorMasVendido").text(colorLider ? colorLider.nombre : "");

    var tallasRotacion = (resp.todas_tallas || []).filter(function (item) {
        return Number(item.stock || 0) > 0 && Number(item.unidades || 0) > 0;
    }).sort(function (a, b) {
        return Number(b.unidades) / Number(b.stock) - Number(a.unidades) / Number(a.stock);
    });
    $("#preguntaTallaRotaMas").text(tallasRotacion.length ? tallasRotacion[0].nombre : "");

    var costoUnitario = fichaResumenActual && fichaResumenActual.rentabilidad
        && fichaResumenActual.rentabilidad.costo_unitario !== null
        ? Number(fichaResumenActual.rentabilidad.costo_unitario)
        : null;
    if (costoUnitario !== null) {
        var coloresUtilidad = (resp.todos_colores || []).slice().sort(function (a, b) {
            var utilidadA = Number(a.venta_neta || 0) - Number(a.unidades || 0) * costoUnitario;
            var utilidadB = Number(b.venta_neta || 0) - Number(b.unidades || 0) * costoUnitario;
            return utilidadB - utilidadA;
        });
        $("#preguntaColorMayorUtilidad").text(coloresUtilidad.length ? coloresUtilidad[0].nombre : "");
    }

    var parametros = fichaParametros();
    var mesesRango = Math.max(1, fichaMesesEnRango(parametros.desde, parametros.hasta));
    var diasPeriodo = Math.max(1, mesesRango * 30);
    if (fichaResumenActual && fichaResumenActual.periodo) {
        var periodoResumen = fichaResumenActual.periodo;
        if (periodoResumen.inicio && periodoResumen.fin_exclusivo) {
            var inicioMs = Date.parse(periodoResumen.inicio);
            var finMs = Date.parse(periodoResumen.fin_exclusivo);
            var hoyMs = Date.now();
            if (isFinite(inicioMs) && isFinite(finMs)) {
                var finEfectivo = Math.min(finMs, hoyMs + 24 * 60 * 60 * 1000);
                diasPeriodo = Math.max(1, Math.round((finEfectivo - inicioMs) / (24 * 60 * 60 * 1000)));
            }
        }
    }
    var combinaciones = resp.todas_combinaciones || [];
    var agotarse = combinaciones.filter(function (item) {
        return Number(item.stock || 0) > 0 && Number(item.unidades || 0) > 0;
    }).map(function (item) {
        item.dias_cobertura = Number(item.stock) / (Number(item.unidades) / diasPeriodo);
        return item;
    }).sort(function (a, b) {
        return a.dias_cobertura - b.dias_cobertura;
    });
    $("#preguntaCombinacionAgotarse").text(agotarse.length
        ? agotarse[0].color + " / " + agotarse[0].talla + " (" + fichaNumero(agotarse[0].dias_cobertura, 0) + " días)"
        : "");

    var noVende = combinaciones.filter(function (item) {
        return Number(item.stock || 0) > 0 && Number(item.unidades || 0) <= 0;
    }).sort(function (a, b) {
        return Number(b.stock || 0) - Number(a.stock || 0);
    });
    $("#preguntaCombinacionNoVende").text(noVende.length
        ? noVende[0].color + " / " + noVende[0].talla
        : "");
}

function fichaRenderLideres(resp) {
    fichaRankingsVariantesPendiente = resp || null;
    var color = resp.colores && resp.colores.length ? resp.colores[0] : null;
    var talla = resp.tallas && resp.tallas.length ? resp.tallas[0] : null;
    var totalVentas = Number(resp.total_ventas || 0);
    var renta = fichaResumenActual && fichaResumenActual.rentabilidad
        ? fichaResumenActual.rentabilidad
        : {};
    var costoUnitario = renta.costo_unitario === null || renta.costo_unitario === undefined
        ? null
        : Number(renta.costo_unitario);

    $("#fichaColorLiderNombre").text(color ? color.nombre : "—");
    $("#fichaColorLiderParticipacion").text(color && totalVentas
        ? fichaNumero(Number(color.venta_neta || 0) * 100 / totalVentas, 0) + "%"
        : "—");
    $("#fichaColorLiderVentas").text(color ? fichaMoneda(color.venta_neta) : "—");
    $("#fichaColorLiderUtilidad").text(color && costoUnitario !== null
        ? fichaMoneda(Number(color.venta_neta || 0) - Number(color.unidades || 0) * costoUnitario)
        : "Pendiente");

    $("#fichaTallaLiderNombre").text(talla ? talla.nombre : "—");
    $("#fichaTallaLiderParticipacion").text(talla && totalVentas
        ? fichaNumero(Number(talla.venta_neta || 0) * 100 / totalVentas, 0) + "%"
        : "—");
    $("#fichaTallaLiderRotacion").text(talla && Number(talla.stock || 0) > 0
        ? fichaNumero(Number(talla.unidades || 0) / Number(talla.stock), 2)
        : "—");
    if (talla && costoUnitario !== null && Number(talla.venta_neta || 0) !== 0) {
        var utilidadTalla = Number(talla.venta_neta) - Number(talla.unidades || 0) * costoUnitario;
        $("#fichaTallaLiderMargen").text(fichaNumero(utilidadTalla * 100 / Number(talla.venta_neta), 1) + "%");
    } else {
        $("#fichaTallaLiderMargen").text("Pendiente");
    }
}

function fichaTablaRanking(items, nombre) {
    if (!items || !items.length) {
        return "<p class='text-muted'>Sin ventas en el período.</p>";
    }
    var html = "<table class='ficha-ranking-tabla'>";
    items.slice(0, 5).forEach(function (item, indice) {
        var etiqueta = nombre === "combinacion" ? item.color + " / " + item.talla : item.nombre;
        html += "<tr><td>" + (indice + 1) + ". " + fichaEscapar(etiqueta)
            + "</td><td>" + fichaMoneda(item.venta_neta) + "</td></tr>";
    });
    return html + "</table>";
}

function fichaRenderRankings(resp) {
    var comparador = new Intl.Collator("es", { numeric: true, sensitivity: "base" });
    var combinaciones = (resp.todas_combinaciones || []).slice().sort(function (a, b) {
        var porColor = comparador.compare(String(a.cod_color || ""), String(b.cod_color || ""));
        return porColor !== 0
            ? porColor
            : comparador.compare(String(a.cod_talla || ""), String(b.cod_talla || ""));
    });
    var html = "";
    var colorAnterior = null;
    combinaciones.forEach(function (item) {
        var rotacion = Number(item.stock || 0) > 0
            ? fichaNumero(Number(item.unidades || 0) / Number(item.stock), 2)
            : "—";
        var iniciaColor = colorAnterior !== null && String(colorAnterior) !== String(item.cod_color || "");
        html += "<tr" + (iniciaColor ? " class='ficha-combinacion-inicio-color'" : "") + "><td>"
            + fichaEscapar(item.color + " / " + item.talla) + "</td>"
            + "<td>" + fichaMoneda(item.venta_neta) + "</td>"
            + "<td>" + fichaNumero(item.promedio_mensual_unidades, 1) + "</td>"
            + "<td>" + fichaNumero(item.stock, 0) + "</td>"
            + "<td>" + fichaNumero(item.pedidos, 0) + "</td>"
            + "<td>" + rotacion + "</td></tr>";
        colorAnterior = item.cod_color;
    });
    $("#tablaCombinacionesFicha").html(html || "<tr><td colspan='6' class='text-muted text-center'>Sin combinaciones</td></tr>");
}

function fichaRecrearCanvasGraficoUnidades() {
    var $contenedor = $(".ficha-grafico-compacto");
    if (!$contenedor.length) {
        return null;
    }
    $contenedor.empty().append("<canvas id='graficoUnidadesFicha' height='105'></canvas>");
    return document.getElementById("graficoUnidadesFicha");
}

function fichaSeriePuestoGrafico(serie) {
    return (serie || []).map(function (valor) {
        if (valor === null || valor === undefined || valor === "") {
            return null;
        }
        var numero = Number(valor);
        return isFinite(numero) && numero > 0 ? -numero : null;
    });
}

function fichaRecrearCanvasRanking(canvasId) {
    var $contenedor = $('.ficha-grafico-ranking[data-canvas="' + canvasId + '"]');
    if (!$contenedor.length) {
        return null;
    }
    $contenedor.empty().append("<canvas id='" + canvasId + "' height='120'></canvas>");
    return document.getElementById(canvasId);
}

function fichaDibujarGraficoPuesto(canvasId, claveGrafico, labels, serie, color, titulo) {
    if (typeof Chart !== "function") {
        return false;
    }
    var canvas = fichaRecrearCanvasRanking(canvasId);
    if (!canvas || !canvas.getContext) {
        return false;
    }
    var etiquetas = labels.length ? labels.slice() : ["Sin datos"];
    var datos = fichaSeriePuestoGrafico(serie);
    if (!datos.length) {
        datos = [null];
    }
    while (datos.length < etiquetas.length) {
        datos.push(null);
    }
    datos = datos.slice(0, etiquetas.length);
    fichaGraficosRanking[claveGrafico] = new Chart(canvas.getContext("2d")).Line({
        labels: etiquetas,
        datasets: [
            {
                label: titulo || "Puesto",
                fillColor: "rgba(60,141,188,0)",
                strokeColor: color || "#3c8dbc",
                pointColor: color || "#3c8dbc",
                pointStrokeColor: "#fff",
                data: datos
            }
        ]
    }, {
        datasetFill: false,
        bezierCurve: true,
        bezierCurveTension: 0.35,
        pointDotRadius: 3,
        scaleBeginAtZero: false,
        scaleLabel: "<%if (value){%>#<%= Math.abs(value) %><%}%>",
        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%if (value === null || typeof value === 'undefined'){%>Sin ventas<%}else{%>#<%= Math.abs(value) %><%}%>"
    });
    return true;
}

function fichaRenderEvolucionRanking(resp) {
    var periodos = resp.periodos || resp.etiquetas || [];
    var labels = periodos.map(function (periodo) {
        return fichaEtiquetaYm(periodo);
    });
    var mensaje = resp.mensaje
        || "Puesto mensual por ventas netas del mes (grupo, categoría y subcategoría).";
    if (resp.grupo && resp.grupo.nombre) {
        mensaje = "Grupo: " + resp.grupo.nombre + ". " + mensaje;
    }
    $("#fichaEvolucionRankingMensaje").text(mensaje);

    var sinClasificacion = resp.estado !== "ok";
    $("#fichaRankCatVacio").toggle(sinClasificacion).text(
        resp.estado === "sin_grupo" ? "Sin grupo comercial" : "Sin clasificación"
    );
    $("#fichaRankSubVacio").toggle(sinClasificacion).text(
        resp.estado === "sin_grupo" ? "Sin grupo comercial" : "Sin clasificación"
    );

    window.requestAnimationFrame(function () {
        fichaDibujarGraficoPuesto(
            "graficoRankGrupoFicha",
            "grupo",
            labels,
            resp.grupo_serie || [],
            "#3c8dbc",
            "Grupo"
        );
        if (resp.estado === "ok") {
            $("#fichaRankCatVacio").hide();
            $("#fichaRankSubVacio").hide();
            fichaDibujarGraficoPuesto(
                "graficoRankCategoriaFicha",
                "categoria",
                labels,
                resp.categoria_serie || [],
                "#00a65a",
                "Categoría"
            );
            fichaDibujarGraficoPuesto(
                "graficoRankSubcategoriaFicha",
                "subcategoria",
                labels,
                resp.subcategoria_serie || [],
                "#605ca8",
                "Subcategoría"
            );
        } else {
            $('.ficha-grafico-ranking[data-canvas="graficoRankCategoriaFicha"]').empty();
            $('.ficha-grafico-ranking[data-canvas="graficoRankSubcategoriaFicha"]').empty();
            fichaGraficosRanking.categoria = null;
            fichaGraficosRanking.subcategoria = null;
            if (resp.estado === "sin_grupo") {
                $('.ficha-grafico-ranking[data-canvas="graficoRankGrupoFicha"]').html(
                    "<p class='text-muted text-center' style='margin:40px 0 0;'>Sin grupo comercial</p>"
                );
                fichaGraficosRanking.grupo = null;
            }
        }
    });

    if ($("#zonaEvolucionRanking .ficha-fuente").length) {
        $("#zonaEvolucionRanking .ficha-fuente").text(fichaMetaTexto(resp.meta));
    }
}

function fichaDibujarGraficoUnidades(labels, unidadesAnterior, unidades, resp) {
    if (typeof Chart !== "function") {
        return false;
    }
    var canvas = fichaRecrearCanvasGraficoUnidades();
    if (!canvas || !canvas.getContext) {
        return false;
    }
    var etiquetas = labels.length ? labels.slice() : ["Sin datos"];
    var serieAnterior = unidadesAnterior.length ? unidadesAnterior.slice() : [0];
    var serieActual = unidades.length ? unidades.slice() : [0];
    while (serieAnterior.length < etiquetas.length) {
        serieAnterior.push(0);
    }
    while (serieActual.length < etiquetas.length) {
        serieActual.push(0);
    }
    serieAnterior = serieAnterior.slice(0, etiquetas.length);
    serieActual = serieActual.slice(0, etiquetas.length);
    fichaGraficoUnidades = new Chart(canvas.getContext("2d")).Line({
        labels: etiquetas,
        datasets: [
            {
                label: String(resp.anio_anterior || "Período homólogo"),
                fillColor: "rgba(160,174,192,0)",
                strokeColor: "#a0aec0",
                pointColor: "#a0aec0",
                data: serieAnterior
            },
            {
                label: String(resp.anio || "Período actual"),
                fillColor: "rgba(60,141,188,0)",
                strokeColor: "#3c8dbc",
                pointColor: "#3c8dbc",
                data: serieActual
            }
        ]
    }, {
        datasetFill: false,
        bezierCurve: true,
        bezierCurveTension: 0.35,
        pointDotRadius: 3,
        scaleBeginAtZero: true
    });
    return true;
}

function fichaRenderEvolucion(resp) {
    var filasPeriodo = resp.data || [];
    var filasPeriodoAnterior = resp.data_anterior || [];
    var totalVentasPeriodo = filasPeriodo.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    var totalUnidadesPeriodo = filasPeriodo.reduce(function (total, item) {
        return total + Number(item.unidades_vendidas || 0);
    }, 0);
    var totalVentasPeriodoAnterior = filasPeriodoAnterior.reduce(function (total, item) {
        return total + Number(item.venta_neta || 0);
    }, 0);
    var totalUnidadesPeriodoAnterior = filasPeriodoAnterior.reduce(function (total, item) {
        return total + Number(item.unidades_vendidas || 0);
    }, 0);
    $("#comparativoAnioAnteriorFicha").text(resp.anio_anterior);
    $("#comparativoAnioActualFicha").text(resp.anio);
    $("#comparativoVentasAnteriorFicha").text(fichaMoneda(totalVentasPeriodoAnterior));
    $("#comparativoVentasActualFicha").text(fichaMoneda(totalVentasPeriodo));
    $("#comparativoUnidadesAnteriorFicha").text(fichaNumero(totalUnidadesPeriodoAnterior, 0));
    $("#comparativoUnidadesActualFicha").text(fichaNumero(totalUnidadesPeriodo, 0));
    var $tendenciaInteranual = $("#comparativoTendenciaFicha").removeClass("alza baja estable");
    if (totalUnidadesPeriodoAnterior !== 0) {
        var variacionInteranual = (totalUnidadesPeriodo - totalUnidadesPeriodoAnterior)
            * 100 / Math.abs(totalUnidadesPeriodoAnterior);
        var direccionInteranual = variacionInteranual > 0 ? "Al alza" : (variacionInteranual < 0 ? "A la baja" : "Estable");
        $tendenciaInteranual.addClass(variacionInteranual > 0 ? "alza" : (variacionInteranual < 0 ? "baja" : "estable"));
        $("#comparativoIconoTendenciaFicha").removeClass("fa-exchange fa-arrow-up fa-arrow-down fa-minus")
            .addClass(variacionInteranual > 0 ? "fa-arrow-up" : (variacionInteranual < 0 ? "fa-arrow-down" : "fa-minus"));
        $("#comparativoDireccionFicha").text(direccionInteranual);
        $("#comparativoVariacionFicha").text((variacionInteranual > 0 ? "+" : "") + fichaNumero(variacionInteranual, 1) + "% vs período homólogo");
    } else {
        $tendenciaInteranual.addClass("estable");
        $("#comparativoIconoTendenciaFicha").removeClass("fa-exchange fa-arrow-up fa-arrow-down").addClass("fa-minus");
        $("#comparativoDireccionFicha").text("Sin base comparable");
        $("#comparativoVariacionFicha").text("");
    }
    var mesesRango = Math.max(1, filasPeriodo.length || fichaMesesEnRango(
        resp.periodo && resp.periodo.desde,
        resp.periodo && resp.periodo.hasta
    ));
    $("#preguntaPromedioMensual").text(fichaNumero(totalUnidadesPeriodo / mesesRango, 0) + " unidades");
    var mejorMes = filasPeriodo.slice().sort(function (a, b) {
        return Number(b.unidades_vendidas || 0) - Number(a.unidades_vendidas || 0);
    })[0];
    $("#preguntaMejorMes").text(mejorMes
        ? fichaEtiquetaYm(mejorMes.periodo || (mejorMes.anio + "-" + ("0" + mejorMes.mes).slice(-2)))
        : "");
    var unidades = filasPeriodo.map(function (item) { return Number(item.unidades_vendidas || 0); });
    var unidadesAnterior = filasPeriodoAnterior.map(function (item) { return Number(item.unidades_vendidas || 0); });
    var mesesPeriodo = filasPeriodo.map(function (item) {
        return fichaEtiquetaYm(item.periodo || (item.anio + "-" + ("0" + item.mes).slice(-2)));
    });
    if (!mesesPeriodo.length && resp.periodo && resp.periodo.desde && resp.periodo.hasta) {
        var cursor = resp.periodo.desde;
        var limite = resp.periodo.hasta;
        while (cursor && fichaYmAClave(cursor) <= fichaYmAClave(limite)) {
            mesesPeriodo.push(fichaEtiquetaYm(cursor));
            unidades.push(0);
            cursor = fichaSumarMesesYm(cursor, 1);
        }
    }
    $("#leyendaAnioAnteriorFicha").text(resp.anio_anterior || "Período homólogo");
    $("#leyendaAnioActualFicha").text(resp.anio || "Período actual");
    $("#tituloGraficoUnidadesFicha").text("Unidades netas · " + (resp.anio || "Período") + " vs homólogo");
    window.requestAnimationFrame(function () {
        if (!fichaDibujarGraficoUnidades(mesesPeriodo, unidadesAnterior, unidades, resp)) {
            $(".ficha-grafico-compacto").html(
                "<p class='text-muted text-center' style='margin:24px 0 0;'>No se pudo dibujar el gráfico</p>"
            );
        }
    });
    if ($("#zonaEvolucion .ficha-fuente").length) {
        $("#zonaEvolucion .ficha-fuente").text(fichaMetaTexto(resp.meta));
    }
}

function fichaRenderConciliacion(resp) {
    var data = resp.data || {};
    var totales = data.totales || {};
    var nc = data.nc_no_atribuibles_periodo || {};
    var auditoria = resp.auditoria || {};
    $("#fichaConciliacionContenido").html(
        "<div class='row'>"
        + "<div class='col-sm-3'><strong>Motor líneas</strong><br>" + fichaMoneda(totales.motor_lineas) + "</div>"
        + "<div class='col-sm-3'><strong>Ficha validada</strong><br>" + fichaMoneda(totales.ficha_lineas) + "</div>"
        + "<div class='col-sm-3'><strong>Líneas excluidas</strong><br>" + fichaNumero(totales.lineas_excluidas, 0) + "</div>"
        + "<div class='col-sm-3'><strong>NC no atribuibles</strong><br>" + fichaMoneda(nc.neto) + " (" + fichaNumero(nc.documentos, 0) + ")</div>"
        + "</div><hr><p>Artículos activos: " + fichaNumero(auditoria.articulos_activos, 0)
        + " · Sin color: " + fichaNumero(auditoria.sin_color, 0)
        + " · Sin talla: " + fichaNumero(auditoria.sin_talla, 0)
        + " · Stock negativo: " + fichaNumero(auditoria.stock_negativo, 0)
        + " · Disponible negativo: " + fichaNumero(auditoria.disponible_negativo, 0) + "</p>"
    );
    $("#zonaConciliacion .ficha-fuente").text(fichaMetaTexto(resp.meta));
}

function fichaCargarZona(accion, zona, render, parametros, generacion) {
    if (zona) {
        fichaEstadoZona(zona, "cargando");
    }
    fichaPost(accion, parametros, generacion).done(function (resp) {
        if (!resp || !resp.ok) {
            if (zona) {
                fichaEstadoZona(zona, "error", resp && resp.mensaje ? resp.mensaje : "Error");
            }
            if (zona === "zonaVariantes") {
                fichaEstadoZona("zonaRankings", "error", "No disponible");
            }
            return;
        }
        try {
            render(resp);
            if (zona) {
                fichaEstadoZona(zona, "ok");
            }
        } catch (errorRender) {
            if (zona) {
                fichaEstadoZona(zona, "error", "No se pudo mostrar la información");
            }
        }
    }).fail(function (error) {
        if (generacion === fichaGeneracion && (!error || !error.obsoleta)) {
            if (zona) {
                fichaEstadoZona(zona, "error", "No disponible");
            }
            if (zona === "zonaVariantes") {
                fichaEstadoZona("zonaRankings", "error", "No disponible");
            }
        }
    });
}

function fichaLimpiarPreguntasRapidas() {
    $(".ficha-preguntas-lista strong").text("");
}

function fichaCargarTodo() {
    var parametros = fichaParametros();
    var errorValidacion = fichaValidarParametros(parametros);
    if (errorValidacion) {
        fichaMostrarErrorGlobal(errorValidacion);
        return;
    }
    fichaLimpiarPreguntasRapidas();
    fichaLimpiarCajasLideres();
    fichaGeneracion++;
    var generacion = fichaGeneracion;
    fichaResumenActual = null;
    fichaRankingPendiente = null;
    fichaLideresPendiente = null;
    fichaRankingsVariantesPendiente = null;
    fichaAbortarSolicitudes();
    fichaActualizarUrl(parametros);
    $("#fichaMensajeGlobal").removeClass("alert-danger").addClass("alert-info").text("Cargando ficha...").show();
    $("#fichaContenido").show();
    fichaEstadoZona("zonaRankings", "cargando");
    fichaEstadoZona("zonaVariantes", "cargando");
    fichaEstadoZona("zonaEvolucion", "cargando");
    fichaEstadoZona("zonaEvolucionRanking", "cargando");

    fichaPost("resumen", parametros, generacion).done(function (resp) {
        if (!resp || !resp.ok) {
            fichaMostrarErrorGlobal(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar el resumen");
            return;
        }
        fichaRenderResumen(resp);
        $("#fichaMensajeGlobal").hide();
    }).fail(function (error) {
        if (generacion === fichaGeneracion && (!error || !error.obsoleta)) {
            fichaMostrarErrorGlobal("No se pudo cargar la ficha");
        }
    });

    fichaCargarZona("ranking", null, fichaRenderRanking, parametros, generacion);
    fichaCargarZona("lideres", null, fichaRenderLideresComerciales, parametros, generacion);
    fichaCargarZona("variantes", "zonaVariantes", fichaRenderVariantes, parametros, generacion);
    fichaCargarZona("evolucion", "zonaEvolucion", fichaRenderEvolucion, parametros, generacion);
    fichaCargarZona("evolucion_ranking", "zonaEvolucionRanking", fichaRenderEvolucionRanking, parametros, generacion);
    if (window.fichaModelosConfig && window.fichaModelosConfig.puedeConciliar) {
        fichaCargarZona("conciliacion", "zonaConciliacion", fichaRenderConciliacion, parametros, generacion);
    }
}

$("#btnCargarFichaModelo").on("click", fichaCargarTodo);
fichaBindModeloChange();
$("#fichaFiltroMarca").on("change", function () {
    fichaCargarCatalogo("", true);
});

if ($("#fichaFiltroModelo").length) {
    var configInicial = window.fichaModelosConfig || {};
    if (configInicial.desdeInicial && configInicial.hastaInicial) {
        fichaActualizarPeriodoUi(configInicial.desdeInicial, configInicial.hastaInicial);
    }
    fichaInicializarPeriodoPicker();
    fichaCargarCatalogo(configInicial.modeloInicial || "", true);
}
