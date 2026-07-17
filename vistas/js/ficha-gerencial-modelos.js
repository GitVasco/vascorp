var fichaGeneracion = 0;
var fichaSolicitudes = [];
var fichaGraficoUnidades = null;
var fichaCatalogoGeneracion = 0;
var fichaResumenActual = null;

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

function fichaParametros() {
    return {
        modelo: $("#fichaFiltroModelo").val() || "",
        anio: $("#fichaFiltroAnio").val(),
        mes: $("#fichaFiltroMes").val()
    };
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
        + "&anio=" + encodeURIComponent(parametros.anio)
        + "&mes=" + encodeURIComponent(parametros.mes);
    window.history.replaceState({}, "", url);
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
    $("#fichaModeloTipo").text(cabecera.tipo || "—");
    $("#fichaModeloLinea").text(cabecera.linea || "—");
    $("#fichaModeloEstado").text(cabecera.estado || "Activo");
    $("#fichaModeloPeriodo").text($("#fichaFiltroMes option:selected").text() + " " + resp.periodo.anio);
    $("#fichaMetaCabecera").text(fichaMetaTexto(resp.meta && resp.meta.ventas));

    var ranking = resp.ranking_general || {};
    var grupoRanking = ranking.grupo && ranking.grupo.nombre ? ranking.grupo.nombre : "";
    $("#kpiRankingGeneral").text(ranking.posicion ? "# " + ranking.posicion : "—");
    $("#kpiRankingTotal").text(ranking.total_modelos_con_venta
        ? (grupoRanking ? grupoRanking + " · " : "") + "de " + ranking.total_modelos_con_venta + " modelos"
        : "Sin grupo o ventas");
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
    fichaRenderLideresComerciales(resp);
}

function fichaRenderLideresComerciales(resp) {
    var lideres = resp.lideres_comerciales || {};
    var zona = lideres.zona || null;
    var vendedor = lideres.vendedor || null;
    var cliente = lideres.cliente || null;
    var ventaTotal = Number(resp.ventas && resp.ventas.venta_neta || 0);
    var costoUnitario = resp.rentabilidad
        && resp.rentabilidad.costo_unitario !== null
        && resp.rentabilidad.costo_unitario !== undefined
        ? Number(resp.rentabilidad.costo_unitario)
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
    $("#preguntaMargenPromedio").text(resp.rentabilidad && resp.rentabilidad.margen_pct !== null
        ? fichaNumero(resp.rentabilidad.margen_pct, 1) + "%"
        : "");
    var ranking = resp.ranking_general || {};
    $("#preguntaRankingModelo").text(ranking.posicion
        ? "# " + ranking.posicion + (ranking.total_modelos_con_venta ? " de " + ranking.total_modelos_con_venta : "")
        : "");
}

function fichaRenderVariantes(resp) {
    var filas = resp.data || [];
    var colores = [];
    var tallas = [];
    var colorVisto = {};
    var tallaVista = {};
    var mapa = {};
    filas.forEach(function (fila) {
        if (!colorVisto[fila.cod_color]) {
            colorVisto[fila.cod_color] = true;
            colores.push({ codigo: fila.cod_color, nombre: fila.color });
        }
        if (!tallaVista[fila.cod_talla]) {
            tallaVista[fila.cod_talla] = true;
            tallas.push({ codigo: fila.cod_talla, nombre: fila.talla });
        }
        mapa[fila.cod_color + "|" + fila.cod_talla] = fila;
    });
    $("#fichaColoresDisponibles").html(colores.map(function (item) {
        return "<span class='ficha-color-item'><i style='background-color:"
            + fichaColorVisual(item.nombre) + "'></i><span>" + fichaEscapar(item.nombre) + "</span></span>";
    }).join("") || "Sin colores");
    $("#fichaTallasDisponibles").html(tallas.map(function (item) {
        return "<span class='ficha-talla-item'>" + fichaEscapar(item.nombre) + "</span>";
    }).join("") || "Sin tallas");

    var ventasPorColor = {};
    var totalVentasMatriz = 0;
    var ventaMaximaCombinacion = 0;
    filas.forEach(function (fila) {
        var venta = Number(fila.venta_neta || 0);
        ventasPorColor[fila.cod_color] = Number(ventasPorColor[fila.cod_color] || 0) + venta;
        totalVentasMatriz += venta;
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
    var html = "<thead><tr><th>Color / Talla</th>";
    tallas.forEach(function (talla) {
        html += "<th>" + fichaEscapar(talla.nombre) + "</th>";
    });
    html += "<th class='ficha-matriz-resumen'>Ventas (S/)</th><th class='ficha-matriz-resumen'>Participación</th>";
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
        var ventaColor = Number(ventasPorColor[color.codigo] || 0);
        var participacionColor = totalVentasMatriz !== 0 ? ventaColor * 100 / totalVentasMatriz : 0;
        html += "<td class='ficha-matriz-venta'>" + fichaMoneda(ventaColor) + "</td>"
            + "<td class='ficha-matriz-participacion'>" + fichaNumero(participacionColor, 0) + "%</td>";
        html += "</tr>";
    });
    html += "</tbody>";
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
    var hoy = new Date();
    var anio = Number(parametros.anio);
    var mes = Number(parametros.mes);
    var diasPeriodo = hoy.getFullYear() === anio && hoy.getMonth() + 1 === mes
        ? hoy.getDate()
        : new Date(anio, mes, 0).getDate();
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

function fichaRenderEvolucion(resp) {
    var meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
    var mesesCompletos = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    var mesSeleccionado = Number($("#fichaFiltroMes").val() || 0);
    var filasPeriodo = (resp.data || []).filter(function (item) {
        return Number(item.mes || 0) <= mesSeleccionado;
    });
    var filasPeriodoAnterior = (resp.data_anterior || []).filter(function (item) {
        return Number(item.mes || 0) <= mesSeleccionado;
    });
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
        $("#comparativoVariacionFicha").text((variacionInteranual > 0 ? "+" : "") + fichaNumero(variacionInteranual, 1) + "% vs " + resp.anio_anterior);
    } else {
        $tendenciaInteranual.addClass("estable");
        $("#comparativoIconoTendenciaFicha").removeClass("fa-exchange fa-arrow-up fa-arrow-down").addClass("fa-minus");
        $("#comparativoDireccionFicha").text("Sin base comparable");
        $("#comparativoVariacionFicha").text("");
    }
    $("#preguntaPromedioMensual").text(mesSeleccionado > 0
        ? fichaNumero(totalUnidadesPeriodo / mesSeleccionado, 0) + " unidades"
        : "");
    var mejorMes = filasPeriodo.slice().sort(function (a, b) {
        return Number(b.unidades_vendidas || 0) - Number(a.unidades_vendidas || 0);
    })[0];
    $("#preguntaMejorMes").text(mejorMes
        ? mesesCompletos[Number(mejorMes.mes) - 1] + " " + $("#fichaFiltroAnio").val()
        : "");
    var unidades = (resp.data || []).map(function (item) { return Number(item.unidades_vendidas || 0); });
    var unidadesAnterior = (resp.data_anterior || []).map(function (item) { return Number(item.unidades_vendidas || 0); });
    var limitarPeriodo = function (datos) {
        return datos.slice(0, mesSeleccionado);
    };
    unidades = limitarPeriodo(unidades);
    unidadesAnterior = limitarPeriodo(unidadesAnterior);
    var mesesPeriodo = meses.slice(0, mesSeleccionado);
    $("#leyendaAnioAnteriorFicha").text(resp.anio_anterior);
    $("#leyendaAnioActualFicha").text(resp.anio);
    $("#tituloGraficoUnidadesFicha").text("Unidades netas · " + resp.anio + " vs " + resp.anio_anterior);
    if (fichaGraficoUnidades && typeof fichaGraficoUnidades.destroy === "function") {
        fichaGraficoUnidades.destroy();
    }
    fichaGraficoUnidades = new Chart(document.getElementById("graficoUnidadesFicha").getContext("2d")).Line({
        labels: mesesPeriodo,
        datasets: [
            { label: String(resp.anio_anterior), fillColor: "rgba(160,174,192,0)", strokeColor: "#a0aec0", pointColor: "#a0aec0", data: unidadesAnterior },
            { label: String(resp.anio), fillColor: "rgba(60,141,188,0)", strokeColor: "#3c8dbc", pointColor: "#3c8dbc", data: unidades }
        ]
    }, {
        responsive: true,
        maintainAspectRatio: false,
        datasetFill: false,
        bezierCurve: true,
        bezierCurveTension: 0.35,
        pointDotRadius: 3,
        scaleBeginAtZero: true
    });
    $("#zonaEvolucion .ficha-fuente").text(fichaMetaTexto(resp.meta));
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
    fichaEstadoZona(zona, "cargando");
    fichaPost(accion, parametros, generacion).done(function (resp) {
        if (!resp || !resp.ok) {
            fichaEstadoZona(zona, "error", resp && resp.mensaje ? resp.mensaje : "Error");
            if (zona === "zonaVariantes") {
                fichaEstadoZona("zonaRankings", "error", "No disponible");
            }
            return;
        }
        render(resp);
        fichaEstadoZona(zona, "ok");
    }).fail(function (error) {
        if (generacion === fichaGeneracion && (!error || !error.obsoleta)) {
            fichaEstadoZona(zona, "error", "No disponible");
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
    if (!parametros.modelo) {
        fichaMostrarErrorGlobal("Selecciona un modelo");
        return;
    }
    fichaLimpiarPreguntasRapidas();
    fichaGeneracion++;
    var generacion = fichaGeneracion;
    fichaAbortarSolicitudes();
    fichaActualizarUrl(parametros);
    $("#fichaMensajeGlobal").removeClass("alert-danger").addClass("alert-info").text("Cargando ficha...").show();

    fichaPost("resumen", parametros, generacion).done(function (resp) {
        if (!resp || !resp.ok) {
            fichaMostrarErrorGlobal(resp && resp.mensaje ? resp.mensaje : "No se pudo cargar el resumen");
            return;
        }
        fichaRenderResumen(resp);
        $("#fichaMensajeGlobal").hide();
        $("#fichaContenido").show();
        fichaEstadoZona("zonaRankings", "cargando");
        fichaCargarZona("variantes", "zonaVariantes", fichaRenderVariantes, parametros, generacion);
        fichaCargarZona("evolucion", "zonaEvolucion", fichaRenderEvolucion, parametros, generacion);
        if (window.fichaModelosConfig && window.fichaModelosConfig.puedeConciliar) {
            fichaCargarZona("conciliacion", "zonaConciliacion", fichaRenderConciliacion, parametros, generacion);
        }
    }).fail(function (error) {
        if (generacion === fichaGeneracion && (!error || !error.obsoleta)) {
            fichaMostrarErrorGlobal("No se pudo cargar la ficha");
        }
    });
}

$("#btnCargarFichaModelo").on("click", fichaCargarTodo);
$("#fichaFiltroModelo, #fichaFiltroAnio, #fichaFiltroMes").on("change", fichaCargarTodo);
$("#fichaFiltroMarca").on("change", function () {
    fichaCargarCatalogo("", true);
});

if ($("#fichaFiltroModelo").length) {
    fichaCargarCatalogo(window.fichaModelosConfig ? window.fichaModelosConfig.modeloInicial : "", true);
}
