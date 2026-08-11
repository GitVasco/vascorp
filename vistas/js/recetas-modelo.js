/* global swal */

var RM = {
	url: "ajax/recetas-modelo.ajax.php",
	estado: null,
	articulos: [],
	lineaIdx: null,
	lineaAnteriorIdx: null,
	mpActiva: null,
	mpActivaUnd: "",
	mpActivaColor: "",
	mpCache: {},
	sublineaCache: {},
	dirty: false,
	_ctxFlashTimer: null
};

function rmEsc(v) {
	return $("<div>").text(v === null || v === undefined ? "" : String(v)).html();
}

function rmAlerta(tipo, titulo, texto) {
	if (typeof swal === "function") {
		swal({ type: tipo, title: titulo || "", text: texto || "", confirmButtonText: "Cerrar" });
		return;
	}
	alert((titulo ? titulo + ": " : "") + (texto || ""));
}

function rmPost(data) {
	return $.ajax({
		url: RM.url,
		method: "POST",
		dataType: "json",
		data: data
	});
}

function rmBadgeEstado(estado) {
	if (estado === "PUBLICADA") return "<span class='label label-success'>PUBLICADA</span>";
	if (estado === "BORRADOR") return "<span class='label label-warning'>BORRADOR</span>";
	if (estado === "ARCHIVADA") return "<span class='label label-default'>ARCHIVADA</span>";
	return "<span class='label label-default'>" + rmEsc(estado) + "</span>";
}

function rmEsBorradorEditable() {
	return RM.estado && RM.estado.cabecera && RM.estado.cabecera.estado === "BORRADOR";
}

function rmLineaActual() {
	if (RM.lineaIdx === null || !RM.estado || !RM.estado.lineas) return null;
	return RM.estado.lineas[RM.lineaIdx] || null;
}

function rmClaveArt(art) {
	return String(art.cod_color || "") + "|" + String(art.cod_talla || "");
}

/** Normaliza códigos cortos para cruzar variantes importadas vs artículos (01 ≈ 1). */
function rmNormCodigoCorto(v) {
	var s = String(v == null ? "" : v).trim();
	if (s === "") return "";
	if (/^\d+$/.test(s)) {
		s = String(parseInt(s, 10));
		if (s === "NaN") return String(v).trim();
	}
	return s;
}

function rmClavesVarianteCandidatas(codColor, codTalla) {
	var c = String(codColor || "");
	var t = String(codTalla || "");
	var cn = rmNormCodigoCorto(c);
	var tn = rmNormCodigoCorto(t);
	var keys = [c + "|" + t];
	if (cn !== c || tn !== t) keys.push(cn + "|" + tn);
	if (/^\d+$/.test(c) && c.length < 2) keys.push(("0" + c).slice(-2) + "|" + t);
	if (/^\d+$/.test(t) && t.length < 2) keys.push(c + "|" + ("0" + t).slice(-2));
	if (/^\d+$/.test(c) && c.length < 2 && /^\d+$/.test(t) && t.length < 2) {
		keys.push(("0" + c).slice(-2) + "|" + ("0" + t).slice(-2));
	}
	keys.push(c + "|");
	keys.push("|" + t);
	keys.push(cn + "|");
	keys.push("|" + tn);
	var seen = {};
	var out = [];
	keys.forEach(function (k) {
		if (seen[k]) return;
		seen[k] = true;
		out.push(k);
	});
	return out;
}

function rmMapaVariantes(linea) {
	var mapa = {};
	(linea.variantes || []).forEach(function (v) {
		var c = String(v.cod_color || "");
		var t = String(v.cod_talla || "");
		mapa[c + "|" + t] = v;
		// Índice alterno normalizado para imports con ceros a la izquierda
		var alt = rmNormCodigoCorto(c) + "|" + rmNormCodigoCorto(t);
		if (!mapa[alt]) mapa[alt] = v;
	});
	return mapa;
}

function rmBuscarVarianteEnMapa(mapa, art) {
	var keys = rmClavesVarianteCandidatas(art.cod_color, art.cod_talla);
	for (var i = 0; i < keys.length; i++) {
		if (mapa[keys[i]]) return mapa[keys[i]];
	}
	return {};
}

function rmConsumoLinea(linea) {
	if (linea && linea.consumo_base != null && linea.consumo_base !== "") {
		return linea.consumo_base;
	}
	return $("#rmConsumoLinea").val() || "1";
}

function rmAsegurarVariantesArticulos(linea) {
	var mapa = rmMapaVariantes(linea);
	var consumoDefault = rmConsumoLinea(linea);
	var nuevas = [];
	(RM.articulos || []).forEach(function (art) {
		var prev = rmBuscarVarianteEnMapa(mapa, art);
		var consumoPrev = prev.consumo != null && prev.consumo !== "" ? prev.consumo : consumoDefault;
		nuevas.push({
			cod_color: art.cod_color || "",
			cod_talla: art.cod_talla || "",
			color: art.color || "",
			talla: art.talla || "",
			articulo: art.articulo,
			mp_codigo: prev.mp_codigo || "",
			consumo: consumoPrev,
			observacion: prev.observacion || ""
		});
	});
	linea.variantes = nuevas;
	linea.regla_variante = "COLOR_TALLA";
	linea.activo = 1;
	linea.consumo_base = consumoDefault;
}

function rmContarOk(linea) {
	rmAsegurarVariantesArticulos(linea);
	var ok = 0;
	(linea.variantes || []).forEach(function (v) {
		if (v.mp_codigo) ok++;
	});
	return { ok: ok, total: (linea.variantes || []).length };
}

function rmColoresOrdenados() {
	var mapa = {};
	(RM.articulos || []).forEach(function (a) {
		var c = String(a.cod_color || "");
		if (!mapa[c]) {
			mapa[c] = { cod_color: c, color: a.color || c };
		}
	});
	return Object.keys(mapa).sort().map(function (k) { return mapa[k]; });
}

function rmTallasOrdenadas() {
	var mapa = {};
	(RM.articulos || []).forEach(function (a) {
		var t = String(a.cod_talla || "");
		if (!mapa[t]) {
			mapa[t] = { cod_talla: t, talla: a.talla || t };
		}
	});
	return Object.keys(mapa).sort(function (a, b) {
		return (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0) || String(a).localeCompare(String(b));
	}).map(function (k) { return mapa[k]; });
}

function rmArtPorColorTalla(codColor, codTalla) {
	var found = null;
	(RM.articulos || []).forEach(function (a) {
		if (String(a.cod_color || "") === String(codColor) && String(a.cod_talla || "") === String(codTalla)) {
			found = a;
		}
	});
	return found;
}

/* ===================== LISTADO ===================== */

var RM_DT_LANG = {
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
		sPrevious: "Anterior"
	},
	oAria: {
		sSortAscending: ": Activar para ordenar la columna de manera ascendente",
		sSortDescending: ": Activar para ordenar la columna de manera descendente"
	}
};

function rmDestroyListadoDt() {
	var $t = $("#tablaRecetasModelo");
	if ($t.length && $.fn.DataTable && $.fn.DataTable.isDataTable($t)) {
		$t.DataTable().clear().destroy();
	}
}

function rmMarcarKpiActivo(estado) {
	estado = (estado == null) ? "" : String(estado);
	$("#rmStatsCabecera .rm-kpi").removeClass("active");
	var $btn = $('#rmStatsCabecera .rm-kpi[data-estado="' + estado + '"]');
	if ($btn.length) $btn.addClass("active");
	else $("#rmStatsCabecera .rm-kpi-total").addClass("active");
}

function rmCargarEstadisticas() {
	if (!$("#rmStatsCabecera").length) return;
	rmPost({ accion: "estadisticas" }).done(function (resp) {
		var d = (resp && resp.ok && resp.data) ? resp.data : {};
		$("#rmStatTotal").text(Number(d.total || 0));
		$("#rmStatBorrador").text(Number(d.borrador || 0));
		$("#rmStatPublicada").text(Number(d.publicada || 0));
		$("#rmStatArchivada").text(Number(d.archivada || 0));
		$("#rmStatSinTela").text(Number(d.sin_tela_principal || 0));
		var sinReceta = Number(d.modelos_sin_receta || 0);
		$("#rmStatSinReceta").text(sinReceta);
		var $badge = $("#badgeModelosSinReceta");
		if (sinReceta > 0) $badge.text(sinReceta).show();
		else $badge.hide();
		rmMarcarKpiActivo($("#filtroEstadoReceta").val() || "");
	});
}

function rmActualizarAlertaSinReceta() {
	rmCargarEstadisticas();
}

function rmCargarListado() {
	var $t = $("#tablaRecetasModelo");
	var $tb = $t.find("tbody");
	if (!$tb.length) return;
	rmDestroyListadoDt();
	$tb.html("<tr><td colspan='10' class='text-center text-muted'>Cargando…</td></tr>");
	rmPost({
		accion: "listar",
		modelo: $("#filtroModeloReceta").val() || "",
		estado: $("#filtroEstadoReceta").val() || ""
	}).done(function (resp) {
		rmDestroyListadoDt();
		if (!resp || !resp.ok) {
			$tb.html("<tr><td colspan='10' class='text-danger'>" + rmEsc((resp && resp.mensaje) || "Error") + "</td></tr>");
			return;
		}
		var lista = resp.data || [];
		var html = "";
		lista.forEach(function (r) {
			var id = Number(r.id);
			var botones = "<div class='btn-group' style='display:inline-flex; flex-wrap:nowrap; white-space:nowrap;'>"
				+ "<a class='btn btn-xs btn-primary' href='index.php?ruta=editar-receta-modelo&idReceta=" + id + "' title='Editar'><i class='fa fa-pencil'></i></a>"
				+ "<button type='button' class='btn btn-xs btn-info btnPreviewRecetaLista' data-id='" + id + "' data-modelo='" + rmEsc(r.modelo) + "' title='Previsualizar'><i class='fa fa-eye'></i></button>"
				+ "<button type='button' class='btn btn-xs btn-warning btnDuplicarRecetaLista' data-id='" + id + "' title='Duplicar'><i class='fa fa-copy'></i></button>";
			if (r.estado === "BORRADOR") {
				botones += "<button type='button' class='btn btn-xs btn-success btnPublicarRecetaLista' data-id='" + id + "' title='Publicar'><i class='fa fa-check'></i></button>";
			}
			botones += "<button type='button' class='btn btn-xs btn-danger btnEliminarRecetaLista' data-id='" + id + "' data-modelo='" + rmEsc(r.modelo) + "' data-version='" + rmEsc(r.version) + "' data-estado='" + rmEsc(r.estado) + "' title='Eliminar'><i class='fa fa-trash'></i></button>";
			botones += "</div>";

			var alertas = [];
			if (!r.tela_principal_rol) {
				alertas.push("<span class='label label-danger' title='Falta marcar tela principal'>Sin tela</span>");
			}
			if (!Number(r.lineas_activas)) {
				alertas.push("<span class='label label-danger'>Sin líneas</span>");
			}
			var sinReceta = Number(r.articulos_sin_receta);
			if (!isFinite(sinReceta)) sinReceta = 0;
			if (sinReceta > 0) {
				alertas.push(
					"<span class='label label-warning' title='Artículos activos con cobertura incompleta en esta versión'>"
					+ sinReceta + " sin receta</span>"
				);
			} else if (Number(r.lineas_activas) > 0 && r.tela_principal_rol) {
				alertas.push("<span class='label label-success' title='Todos los artículos activos tienen cobertura'>0 sin receta</span>");
			}
			var alertaHtml = alertas.length ? alertas.join(" ") : "<span class='text-muted'>—</span>";

			html += "<tr>"
				+ "<td><strong>" + rmEsc(r.modelo) + "</strong></td>"
				+ "<td>" + rmEsc(r.nombre_modelo || "") + "</td>"
				+ "<td data-order='" + rmEsc(r.version) + "'>" + rmEsc(r.version) + "</td>"
				+ "<td data-order='" + rmEsc(r.estado) + "'>" + rmBadgeEstado(r.estado) + "</td>"
				+ "<td data-order='" + Number(r.articulos_activos || 0) + "'>" + rmEsc(r.articulos_activos) + "</td>"
				+ "<td data-order='" + Number(r.lineas_activas || 0) + "'>" + rmEsc(r.lineas_activas) + "</td>"
				+ "<td>" + rmEsc(r.tela_principal_rol || "—") + "</td>"
				+ "<td data-order='" + sinReceta + "'>" + alertaHtml + "</td>"
				+ "<td>" + rmEsc(r.updated_at || r.created_at || "") + "</td>"
				+ "<td style='white-space:nowrap; width:1%;'>" + botones + "</td></tr>";
		});
		$tb.html(html);
		if ($.fn.DataTable) {
			$t.DataTable({
				pageLength: 25,
				lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
				order: [[0, "asc"]],
				columnDefs: [
					{ orderable: false, targets: -1 }
				],
				language: RM_DT_LANG,
				autoWidth: false
			});
		}
	}).fail(function () {
		rmDestroyListadoDt();
		$tb.html("<tr><td colspan='10' class='text-danger'>Error al cargar</td></tr>");
	});
}

function rmCargarTablaModelosSinReceta() {
	var $tb = $("#tablaModelosSinReceta tbody").html("<tr><td colspan='4' class='text-muted'>Cargando…</td></tr>");
	rmPost({ accion: "listarModelosImportTarjetas" }).done(function (resp) {
		var lista = (resp && resp.ok && resp.data && resp.data.modelos) ? resp.data.modelos : [];
		if (!lista.length) {
			$tb.html("<tr><td colspan='4' class='text-center text-muted'>Todos los modelos con tarjetas ya tienen receta</td></tr>");
			return;
		}
		var html = "";
		lista.forEach(function (m) {
			html += "<tr>"
				+ "<td><strong>" + rmEsc(m.modelo) + "</strong></td>"
				+ "<td>" + rmEsc(m.nombre_modelo || "") + "</td>"
				+ "<td>" + rmEsc(m.articulos_con_tarjeta || 0) + "</td>"
				+ "<td><button type='button' class='btn btn-xs btn-success btnImportarModeloLista' data-modelo='" + rmEsc(m.modelo) + "'><i class='fa fa-download'></i> Importar</button></td>"
				+ "</tr>";
		});
		$tb.html(html);
	}).fail(function () {
		$tb.html("<tr><td colspan='4' class='text-danger'>Error al cargar</td></tr>");
	});
}

function rmCargarSelectPreviewArticulos(modelo) {
	var $sel = $("#previewArticulo");
	$sel.prop("disabled", true);
	$sel.html("");
	if (typeof $sel.selectpicker === "function") {
		$sel.selectpicker("refresh");
	}
	if (!modelo) {
		$sel.html("<option value=''>Sin modelo</option>");
		$sel.prop("disabled", false);
		if (typeof $sel.selectpicker === "function") $sel.selectpicker("refresh");
		return;
	}
	rmPost({ accion: "articulosModelo", modelo: modelo }).done(function (resp) {
		$sel.empty();
		var arts = (resp && resp.ok && resp.data && resp.data.articulos) ? resp.data.articulos : [];
		if (!arts.length) {
			$sel.append("<option value=''>Sin artículos activos</option>");
		} else {
			arts.forEach(function (a) {
				var label = (a.articulo || "")
					+ " · " + (a.color || a.cod_color || "")
					+ " · " + (a.talla || a.cod_talla || "");
				$sel.append("<option value='" + rmEsc(a.articulo) + "'>" + rmEsc(label) + "</option>");
			});
		}
	}).fail(function () {
		$sel.html("<option value=''>Error al cargar</option>");
	}).always(function () {
		$sel.prop("disabled", false);
		if (typeof $sel.selectpicker === "function") {
			$sel.selectpicker("refresh");
		}
	});
}

function rmEjecutarImportModelo(modelo, $btn) {
	modelo = $.trim(modelo || "");
	if (!modelo) return;
	var $b = $btn || $("#btnEjecutarImportTarjetas");
	var htmlPrev = $b.html();
	$b.prop("disabled", true).html("<i class='fa fa-spinner fa-spin'></i>");
	rmPost({ accion: "importarDesdeTarjetas", modelo: modelo }).done(function (resp) {
		if (!resp || !resp.ok) {
			rmAlerta("error", "No se importó", (resp && resp.mensaje) || "Error");
			return;
		}
		var avisos = (resp.data && resp.data.avisos) ? resp.data.avisos : [];
		var txt = "Líneas: " + (resp.data.lineas || 0)
			+ " · Variantes: " + (resp.data.variantes_importadas || 0)
			+ " · Arts: " + (resp.data.articulos_activos || 0);
		if (avisos.length) {
			txt += "\n\nAvisos:\n- " + avisos.join("\n- ");
		}
		$("#modalImportarTarjetasReceta, #modalModelosSinReceta").modal("hide");
		rmActualizarAlertaSinReceta();
		rmCargarListado();
		if (typeof swal === "function") {
			swal({
				type: "success",
				title: "Borrador importado",
				text: txt,
				confirmButtonText: "Abrir editor"
			}).then(function () {
				window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + resp.data.id;
			});
		} else {
			alert(txt);
			window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + resp.data.id;
		}
	}).fail(function () {
		rmAlerta("error", "Error", "No se pudo comunicar con el servidor");
	}).always(function () {
		$b.prop("disabled", false).html(htmlPrev);
	});
}

/* ===================== EDITOR ===================== */

function rmRenderCabecera() {
	var c = RM.estado.cabecera;
	$("#rmModelo").text(c.modelo || "—");
	$("#rmNombreModelo").text(c.nombre_modelo || c.modelo || "—");
	$("#rmVersion").text(c.version || "—");
	$("#rmEstado").html(rmBadgeEstado(c.estado));
	$("#rmArtActivos").text(RM.articulos.length);
	$("#rmTituloCabecera").text(c.modelo + " v" + c.version);
	var editable = rmEsBorradorEditable();
	$("#rmBtnGuardar, #rmBtnPublicar, #rmBtnAgregarSublinea").prop("disabled", !editable);
	$("#rmConsumoLinea").prop("disabled", !editable);
	$("#rmMsgEstado").text(editable ? "" : "Solo lectura — crea «Nueva versión»");
}

function rmRenderChips() {
	var $box = $("#rmChipsInsumos").empty();
	var lineasActivas = (RM.estado.lineas || []).filter(function (l) {
		return Number(l.activo) !== 0;
	});
	if (!lineasActivas.length) {
		$box.removeClass("has-chips").hide();
		return;
	}
	$box.addClass("has-chips").show();
	RM.estado.lineas.forEach(function (linea, idx) {
		if (Number(linea.activo) === 0) return;
		rmAsegurarVariantesArticulos(linea);
		var cont = rmContarOk(linea);
		var active = RM.lineaIdx === idx;
		var cons = linea.consumo_base != null ? linea.consumo_base : "";
		// Si es la línea activa, preferir el valor del input (aún no sincronizado)
		if (active) {
			var consInput = $("#rmConsumoLinea").val();
			if (consInput !== undefined && consInput !== null && consInput !== "") cons = consInput;
		}
		var esTela = Number(linea.es_tela_principal) === 1;
		var html = "<div class='rm2-chip" + (active ? " active" : "") + "' data-idx='" + idx + "'>"
			+ "<strong>" + rmEsc(rmEtiquetaLinea(linea)) + "</strong>";
		if (active) {
			html += "<span class='rm2-chip-editando'>EDITANDO</span>";
		}
		if (cons !== "") {
			html += "<span class='label label-default'>" + rmEsc(rmFmtNum(cons)) + (linea.unidad ? " " + rmEsc(linea.unidad) : "") + "</span>";
		}
		html += "<span class='label " + (cont.ok === cont.total && cont.total ? "label-success" : "label-warning") + "'>"
			+ cont.ok + "/" + cont.total + "</span>";
		if (rmEsBorradorEditable()) {
			html += "<button type='button' class='rm2-btn-tela rmToggleTela" + (esTela ? " on" : "") + "' data-idx='" + idx + "'"
				+ " title='" + (esTela ? "Es la tela principal (clic para quitar)" : "Marcar esta sublínea como tela principal") + "'>"
				+ (esTela ? "★ Tela principal" : "Hacer tela") + "</button>";
			html += "<a href='javascript:;' class='rm2-chip-x rmQuitarChip' data-idx='" + idx + "' title='Quitar'>&times;</a>";
		} else if (esTela) {
			html += "<span class='label label-danger'>Tela principal</span>";
		}
		html += "</div>";
		$box.append(html);
	});
}

function rmLimpiarPickSublinea() {
	$("#rmNuevaSublinea").val("");
	$("#rmNuevaSublineaCod").text("Buscar y agregar otra sublínea…").addClass("empty");
	$("#rmNuevaSublineaNom").text("Solo agrega; la edición es del chip seleccionado abajo");
}

function rmSetPickSublinea(cod, nom) {
	$("#rmNuevaSublinea").val(cod || "");
	if (cod) {
		$("#rmNuevaSublineaCod").text(cod).removeClass("empty");
		$("#rmNuevaSublineaNom").text(nom || "");
	} else {
		rmLimpiarPickSublinea();
	}
}

function rmNombreLinea(linea) {
	var cod = linea && linea.codigo_sublinea ? String(linea.codigo_sublinea) : "";
	var catalogo = "";
	if (cod && RM.sublineaCache[cod]) catalogo = RM.sublineaCache[cod];
	else if (linea && linea.nombre_sublinea) catalogo = String(linea.nombre_sublinea);

	var nom = linea && linea.nombre_rol != null ? String(linea.nombre_rol) : "";
	// Nombre fantasma / genérico: preferir nombre de catálogo de la sublínea
	if (nom === "Tela principal" || nom === "" || nom === cod) {
		nom = catalogo || (nom === "Tela principal" ? "" : nom);
	}
	if ((!nom || nom === cod) && catalogo) {
		nom = catalogo;
	}
	return nom || cod || "Insumo";
}

function rmEtiquetaLinea(linea) {
	if (!linea) return "—";
	var cod = linea.codigo_sublinea || "";
	var nom = rmNombreLinea(linea);
	if (cod && nom && nom !== cod) return cod + " — " + nom;
	return nom || cod || "Insumo";
}

function rmEnriquecerNombresSublinea(done) {
	var cods = [];
	var seen = {};
	(RM.estado && RM.estado.lineas || []).forEach(function (l) {
		var c = l.codigo_sublinea ? String(l.codigo_sublinea).toUpperCase() : "";
		if (!c || seen[c] || RM.sublineaCache[c]) return;
		seen[c] = true;
		cods.push(c);
	});
	if (!cods.length) {
		if (typeof done === "function") done();
		return;
	}
	rmPost({ accion: "infoSublineas", codigos: JSON.stringify(cods) }).done(function (resp) {
		if (resp && resp.ok && resp.data) {
			Object.keys(resp.data).forEach(function (cod) {
				var nom = resp.data[cod];
				if (nom) RM.sublineaCache[cod] = String(nom);
			});
			// Si nombre_rol era solo el código, completar con el nombre de catálogo
			(RM.estado.lineas || []).forEach(function (l) {
				var c = l.codigo_sublinea ? String(l.codigo_sublinea).toUpperCase() : "";
				if (!c || !RM.sublineaCache[c]) return;
				l.nombre_sublinea = RM.sublineaCache[c];
				if (!l.nombre_rol || String(l.nombre_rol) === c || String(l.nombre_rol) === "Tela principal") {
					if (Number(l.es_tela_principal) === 1 && String(l.nombre_rol) === "Tela principal") {
						// conservar "Tela principal" como rol, el label usará cache
					} else if (!l.nombre_rol || String(l.nombre_rol) === c) {
						l.nombre_rol = RM.sublineaCache[c];
					}
				}
			});
		}
	}).always(function () {
		if (typeof done === "function") done();
	});
}

/**
 * Encabezado persistente de la sublínea activa + franja temporal opcional.
 * @param {string} [mensajeTemporal]
 * @param {{mostrarVolver?: boolean}} [opts]
 */
function rmActualizarContextoLineaActiva(mensajeTemporal, opts) {
	opts = opts || {};
	var linea = rmLineaActual();
	var $hint = $("#rmSublineaSeleccionadaHint");
	var $ctx = $("#rmLineaActivaContexto");

	if (!linea) {
		$ctx.hide();
		$hint.hide().empty();
		$("#rmCtxFlash").removeClass("visible").hide();
		$("#rmBtnVolverLineaAnterior").hide();
		$("#rmConsumoLineaLabel").text("Consumo");
		$("#rmUnidadLineaAddon").text("—");
		$("#rmMatrizContexto").text("Asignar · color artículo × talla");
		$("#rmTituloArticulos").text("2. Asignar materia prima");
		return;
	}

	var etiqueta = rmEtiquetaLinea(linea);
	var nArts = (RM.articulos || []).length;
	var und = linea.unidad ? String(linea.unidad) : "—";

	$ctx.show();
	// El nombre completo solo aquí (evitar repetirlo en título / consumo / matriz)
	$("#rmCtxNombre").text(etiqueta);
	$("#rmCtxMeta").text(
		"Consumo único para esta sublínea · se aplica a " + nArts
		+ " combinación" + (nArts === 1 ? "" : "es") + " color × talla"
	);
	$("#rmConsumoLineaLabel").text("Consumo");
	$("#rmUnidadLineaAddon").text(und);
	$("#rmMatrizContexto").text("Asignar MP · color × talla");
	$("#rmTituloArticulos").text("2. Asignar materia prima");
	$hint.hide().empty();

	if (mensajeTemporal) {
		$("#rmCtxFlashTxt").text(mensajeTemporal);
		var $flash = $("#rmCtxFlash").addClass("visible").show();
		var ant = (RM.lineaAnteriorIdx !== null && RM.estado && RM.estado.lineas)
			? RM.estado.lineas[RM.lineaAnteriorIdx]
			: null;
		if (opts.mostrarVolver && ant && Number(ant.activo) !== 0) {
			$("#rmBtnVolverLineaAnterior")
				.text("Volver a «" + rmEtiquetaLinea(ant) + "»")
				.show();
		} else {
			$("#rmBtnVolverLineaAnterior").hide();
		}
		if (RM._ctxFlashTimer) clearTimeout(RM._ctxFlashTimer);
		RM._ctxFlashTimer = setTimeout(function () {
			$flash.removeClass("visible").fadeOut(180);
			$("#rmBtnVolverLineaAnterior").hide();
		}, 7000);
	}
}

/** Corrige nombres "Tela principal" huérfanos al cambiar el flag. */
function rmNormalizarNombresTela() {
	var changed = false;
	(RM.estado && RM.estado.lineas || []).forEach(function (l) {
		if (Number(l.es_tela_principal) !== 1 && String(l.nombre_rol || "") === "Tela principal") {
			l.nombre_rol = l.codigo_sublinea || "Insumo";
			changed = true;
		}
	});
	return changed;
}

function rmCacheMp(mp) {
	if (!mp || !mp.mp_codigo) return;
	RM.mpCache[mp.mp_codigo] = {
		descripcion: mp.descripcion || "",
		color: mp.color || "",
		unidad: mp.unidad || ""
	};
}

function rmColorMp(codigo) {
	if (!codigo) return "";
	var info = RM.mpCache[codigo];
	if (info && info.color) return info.color;
	return "";
}

function rmEtiquetaMp(codigo) {
	var color = rmColorMp(codigo);
	if (color) return color;
	return codigo || "—";
}

function rmNombreMp(codigo) {
	if (!codigo) return "";
	var info = RM.mpCache[codigo];
	if (info && info.descripcion) return info.descripcion;
	return "";
}

function rmEnriquecerMpsAsignadas(done) {
	var codigos = [];
	var seen = {};
	(RM.estado && RM.estado.lineas || []).forEach(function (linea) {
		(linea.variantes || []).forEach(function (v) {
			if (v.mp_codigo && !seen[v.mp_codigo] && !RM.mpCache[v.mp_codigo]) {
				seen[v.mp_codigo] = true;
				codigos.push(v.mp_codigo);
			}
		});
	});
	if (!codigos.length) {
		if (done) done();
		return;
	}
	rmPost({ accion: "infoMps", codigos: JSON.stringify(codigos) }).done(function (resp) {
		if (resp && resp.ok && resp.data) {
			Object.keys(resp.data).forEach(function (cod) {
				var row = resp.data[cod];
				RM.mpCache[cod] = {
					descripcion: row.descripcion || "",
					color: row.color || "",
					unidad: row.unidad || ""
				};
			});
		}
		if (done) done();
	}).fail(function () {
		if (done) done();
	});
}

function rmRenderMpsAsignadas() {
	var linea = rmLineaActual();
	var $box = $("#rmMpsAsignadas").empty();
	if (!linea) {
		$box.html("<span class='text-muted'>Ninguna aún</span>");
		return;
	}
	var mapa = {};
	(linea.variantes || []).forEach(function (v) {
		if (!v.mp_codigo) return;
		if (!mapa[v.mp_codigo]) mapa[v.mp_codigo] = 0;
		mapa[v.mp_codigo]++;
	});
	var keys = Object.keys(mapa);
	if (!keys.length) {
		$box.html("<span class='text-muted'>Ninguna aún</span>");
		return;
	}
	keys.sort(function (a, b) {
		return rmEtiquetaMp(a).localeCompare(rmEtiquetaMp(b));
	});
	keys.forEach(function (cod) {
		var info = RM.mpCache[cod] || {};
		var activa = String(RM.mpActiva || "") === String(cod) ? " activa" : "";
		$box.append(
			"<button type='button' class='rm2-asig-chip" + activa + "' data-mp='" + rmEsc(cod) + "'"
			+ " data-und='" + rmEsc(info.unidad || "") + "'"
			+ " data-color='" + rmEsc(info.color || "") + "'"
			+ " data-desc='" + rmEsc(info.descripcion || "") + "'"
			+ " title='Clic: poner en mano · " + rmEsc(cod) + "'>"
			+ "<strong>" + rmEsc(rmEtiquetaMp(cod)) + "</strong>"
			+ "<span class='n'>" + mapa[cod] + "</span>"
			+ "</button>"
		);
	});
}

function rmMarcarDirty(on) {
	RM.dirty = !!on;
	if (RM.dirty) $("#rmDirtyFlag").show();
	else $("#rmDirtyFlag").hide();
}

function rmHeredarUnidadDeMp(und) {
	if (!und) return;
	var linea = rmLineaActual();
	if (!linea) return;
	var next = String(und);
	if (String(linea.unidad || "") !== next) rmMarcarDirty(true);
	linea.unidad = next;
	$("#rmUnidadLineaAddon").text(next || "—");
	$("#rmMpActivaUndTxt").text(next ? (" · " + next) : "");
}

function rmSetMpActiva(mp, und, color, desc) {
	RM.mpActiva = mp || null;
	RM.mpActivaUnd = und || "";
	RM.mpActivaColor = color || rmColorMp(mp) || "";
	if (mp) {
		if (und) rmHeredarUnidadDeMp(und);
		$("#rmMpActivaBox").addClass("visible");
		$("#rmMpActivaTxt").html(
			"<strong>" + rmEsc(RM.mpActivaColor || mp) + "</strong>"
			+ (desc ? " <span class='text-muted'>· " + rmEsc(desc) + "</span>" : "")
			+ " <span class='text-muted'>(" + rmEsc(mp) + ")</span>"
		);
		$("#rmMpActivaUndTxt").text(und ? (" · " + und) : "");
		$("#rmMpActivaAcciones").show();
	} else {
		$("#rmMpActivaBox").removeClass("visible");
		$("#rmMpActivaTxt").text("—");
		$("#rmMpActivaUndTxt").text("");
		$("#rmMpActivaAcciones").hide();
	}
	$("#rmTablaMp tr").removeClass("activa");
	if (mp) {
		$("#rmTablaMp tr[data-mp='" + String(mp).replace(/'/g, "\\'") + "']").addClass("activa");
	}
	$("#rmMpsAsignadas .rm2-asig-chip").removeClass("activa");
	if (mp) {
		$("#rmMpsAsignadas .rm2-asig-chip[data-mp='" + String(mp).replace(/'/g, "\\'") + "']").addClass("activa");
	}
}

function rmFmtNum(v) {
	if (v === null || v === undefined || v === "") return "";
	var n = Number(v);
	if (!isFinite(n)) return String(v);
	var s = n.toFixed(6).replace(/\.?0+$/, "");
	return s === "-0" ? "0" : s;
}

function rmResolverMpLinea(linea, art) {
	rmAsegurarVariantesArticulos(linea);
	var mapa = rmMapaVariantes(linea);
	var v = rmBuscarVarianteEnMapa(mapa, art);
	var consumo = v.consumo != null && v.consumo !== ""
		? v.consumo
		: (linea.consumo_base != null ? linea.consumo_base : "");
	return {
		mp_codigo: v.mp_codigo || "",
		consumo: consumo,
		unidad: linea.unidad || (v.mp_codigo && RM.mpCache[v.mp_codigo] ? RM.mpCache[v.mp_codigo].unidad : "") || ""
	};
}

function rmRenderTarjetasPorArticulo() {
	var $tb = $("#rmTablaTarjetasArticulo tbody");
	if (!$tb.length) return;
	$tb.empty();

	var lineas = (RM.estado && RM.estado.lineas || []).filter(function (l) {
		return Number(l.activo) !== 0;
	});
	var arts = RM.articulos || [];

	if (!arts.length) {
		$tb.html("<tr><td colspan='10' class='text-muted'>Sin artículos activos</td></tr>");
		return;
	}
	if (!lineas.length) {
		$tb.html("<tr><td colspan='10' class='text-muted'>Agrega sublíneas para ver las tarjetas</td></tr>");
		return;
	}

	lineas.forEach(rmAsegurarVariantesArticulos);

	arts.forEach(function (art, artIdx) {
		var n = lineas.length;
		var prev = artIdx > 0 ? arts[artIdx - 1] : null;
		var sepClass = "";
		if (prev) {
			var colorPrev = String(prev.cod_color || "");
			var colorCur = String(art.cod_color || "");
			var tallaPrev = String(prev.cod_talla || "");
			var tallaCur = String(art.cod_talla || "");
			if (colorPrev !== colorCur) {
				sepClass = " rm2-sep-color";
			} else if (tallaPrev !== tallaCur) {
				sepClass = " rm2-sep-talla";
			}
		}
		lineas.forEach(function (linea, i) {
			var res = rmResolverMpLinea(linea, art);
			var ok = !!res.mp_codigo;
			var esTela = Number(linea.es_tela_principal) === 1;
			var sep = i === 0 ? sepClass : "";
			var row = "<tr class='" + (ok ? "ok" : "falta") + sep + "'>";
			if (i === 0) {
				row += "<td class='rm2-art-cell' rowspan='" + n + "'>" + rmEsc(art.articulo) + "</td>"
					+ "<td class='rm2-meta-cell' rowspan='" + n + "'>" + rmEsc(art.color || art.cod_color) + "</td>"
					+ "<td class='rm2-meta-cell' rowspan='" + n + "'>" + rmEsc(art.talla || art.cod_talla) + "</td>";
			}
			var nombreMp = ok ? (rmNombreMp(res.mp_codigo) || res.mp_codigo) : "—";
			row += "<td title='" + rmEsc(ok ? res.mp_codigo : "") + "'>" + rmEsc(nombreMp)
				+ (esTela ? " <span class='label label-danger'>Tela</span>" : "")
				+ "</td>"
				+ "<td>" + rmEsc(linea.codigo_sublinea || "—") + "</td>"
				+ "<td>" + rmEsc(ok ? res.mp_codigo : "—") + "</td>"
				+ "<td>" + rmEsc(ok ? rmEtiquetaMp(res.mp_codigo) : "—") + "</td>"
				+ "<td>" + rmEsc(ok ? rmFmtNum(res.consumo) : "—") + "</td>"
				+ "<td>" + rmEsc(ok ? (res.unidad || "") : "") + "</td>"
				+ "<td>" + (ok
					? "<span class='label label-success'>OK</span>"
					: "<span class='label label-warning'>Falta</span>")
				+ "</td></tr>";
			$tb.append(row);
		});
	});
}

function rmRenderPorArticulo() {
	rmRenderTarjetasPorArticulo();
}

function rmRenderMatriz() {
	var linea = rmLineaActual();
	if (!linea) {
		$("#rmAyudaPaso2").show();
		$("#rmPanelPaso2").hide();
		$("#rmTituloArticulos").text("2. Asignar materia prima");
		rmActualizarContextoLineaActiva();
		return;
	}
	$("#rmAyudaPaso2").hide();
	$("#rmPanelPaso2").show();

	$("#rmConsumoLinea").val(linea.consumo_base != null && linea.consumo_base !== "" ? linea.consumo_base : "1");
	rmActualizarContextoLineaActiva();

	rmAsegurarVariantesArticulos(linea);
	var mapa = rmMapaVariantes(linea);
	var colores = rmColoresOrdenados();
	var tallas = rmTallasOrdenadas();
	var editable = rmEsBorradorEditable();

	// Fila 1: nombres de talla
	// Fila 2: atajo "todos los colores" por columna
	var $thead = $("#rmMatriz thead").empty();
	var head1 = "<tr><th class='rm2-color-th' rowspan='2'>"
		+ "Color artículo"
		+ (editable
			? "<div class='rm2-atajos' style='margin-top:6px;'>"
				+ "<button type='button' class='btn btn-xs btn-success rmAplicarTodos' title='Asignar MP en mano a todas las celdas'><i class='fa fa-th'></i> Todos</button> "
				+ "<button type='button' class='btn btn-xs btn-danger rmQuitarTodos' title='Quitar MP de todas las celdas'><i class='fa fa-eraser'></i></button>"
				+ "</div>"
			: "")
		+ "</th>";
	tallas.forEach(function (t) {
		head1 += "<th>" + rmEsc(t.talla || t.cod_talla) + "</th>";
	});
	head1 += "<th rowspan='2' style='min-width:140px;'>Atajos</th></tr>";

	var head2 = "<tr>";
	tallas.forEach(function (t) {
		head2 += "<th style='background:#e8f4fc; padding:4px;'>"
			+ (editable
				? "<div class='btn-group btn-group-xs'>"
					+ "<button type='button' class='btn btn-info rmAplicarTalla' data-talla='" + rmEsc(t.cod_talla) + "' title='MP en mano → todos los colores de esta talla'><i class='fa fa-arrows-v'></i></button>"
					+ "<button type='button' class='btn btn-default rmQuitarTalla' data-talla='" + rmEsc(t.cod_talla) + "' title='Quitar MP en todos los colores de esta talla'><i class='fa fa-times'></i></button>"
					+ "</div>"
				: "")
			+ "</th>";
	});
	head2 += "</tr>";
	$thead.html(head1 + head2);

	var $tbody = $("#rmMatriz tbody").empty();
	colores.forEach(function (col) {
		var row = "<tr><th class='rm2-color-th'>"
			+ "<strong>" + rmEsc(col.color || col.cod_color) + "</strong>"
			+ "</th>";
		tallas.forEach(function (tal) {
			var art = rmArtPorColorTalla(col.cod_color, tal.cod_talla);
			if (!art) {
				row += "<td class='text-muted' style='background:#fafafa;'>—</td>";
				return;
			}
			var key = rmClaveArt(art);
			var v = mapa[key] || {};
			var ok = !!v.mp_codigo;
			var etiqueta = ok ? rmEtiquetaMp(v.mp_codigo) : "—";
			row += "<td class='rm2-celda " + (ok ? "ok" : "falta") + "' data-articulo='" + rmEsc(art.articulo)
				+ "' data-color='" + rmEsc(col.cod_color) + "' data-talla='" + rmEsc(tal.cod_talla) + "' data-key='" + rmEsc(key) + "'"
				+ " title='" + rmEsc(art.articulo + (ok ? " · MP " + v.mp_codigo : "") + " — clic: asignar · Alt+clic: quitar") + "'"
				+ (editable ? "" : " style='cursor:default;'") + ">"
				+ "<div class='rm2-celda-color'>" + rmEsc(etiqueta) + "</div>"
				+ "<div class='rm2-celda-art'>" + rmEsc(art.articulo) + "</div>"
				+ "</td>";
		});
		row += "<td>"
			+ (editable
				? "<div class='btn-group btn-group-xs'>"
					+ "<button type='button' class='btn btn-primary rmAplicarColor' data-color='" + rmEsc(col.cod_color) + "' title='MP en mano → todas las tallas de este color'><i class='fa fa-arrows-h'></i> Tallas</button>"
					+ "<button type='button' class='btn btn-default rmQuitarColor' data-color='" + rmEsc(col.cod_color) + "' title='Quitar MP en todas las tallas de este color'><i class='fa fa-times'></i></button>"
					+ "</div>"
				: "")
			+ "</td></tr>";
		$tbody.append(row);
	});

	rmRenderMpsAsignadas();
	rmRenderTarjetasPorArticulo();
	rmSetMpActiva(RM.mpActiva, RM.mpActivaUnd, RM.mpActivaColor, RM.mpActiva ? (RM.mpCache[RM.mpActiva] || {}).descripcion : "");
}

function rmRefrescarPaso2(reloadCatalogo) {
	rmRenderChips();
	rmRenderMatriz();
	if (reloadCatalogo) rmCargarTablaMp();
}

function rmAplicarMpAKey(key, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var consumo = $("#rmConsumoLinea").val() || linea.consumo_base || "1";
	linea.consumo_base = consumo;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") + "|" + String(v.cod_talla || "") === String(key)) {
			v.mp_codigo = String(mp);
			v.consumo = consumo;
		}
	});
	rmMarcarDirty(true);
}

function rmAplicarMpAColor(codColor, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var consumo = $("#rmConsumoLinea").val() || linea.consumo_base || "1";
	linea.consumo_base = consumo;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") === String(codColor)) {
			v.mp_codigo = String(mp);
			v.consumo = consumo;
		}
	});
	rmMarcarDirty(true);
}

function rmAplicarMpATalla(codTalla, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var consumo = $("#rmConsumoLinea").val() || linea.consumo_base || "1";
	linea.consumo_base = consumo;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_talla || "") === String(codTalla)) {
			v.mp_codigo = String(mp);
			v.consumo = consumo;
		}
	});
	rmMarcarDirty(true);
}

function rmAplicarMpTodos(mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var consumo = $("#rmConsumoLinea").val() || linea.consumo_base || "1";
	linea.consumo_base = consumo;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		v.mp_codigo = String(mp);
		v.consumo = consumo;
	});
	rmMarcarDirty(true);
}

function rmContarVariantesConMp(filtroFn) {
	var linea = rmLineaActual();
	if (!linea) return 0;
	rmAsegurarVariantesArticulos(linea);
	var n = 0;
	(linea.variantes || []).forEach(function (v) {
		if (filtroFn && !filtroFn(v)) return;
		if (v.mp_codigo) n++;
	});
	return n;
}

function rmQuitarMpAKey(key) {
	var linea = rmLineaActual();
	if (!linea) return;
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") + "|" + String(v.cod_talla || "") === String(key)) {
			v.mp_codigo = "";
		}
	});
	rmMarcarDirty(true);
}

function rmQuitarMpAColor(codColor) {
	var linea = rmLineaActual();
	if (!linea) return;
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") === String(codColor)) {
			v.mp_codigo = "";
		}
	});
	rmMarcarDirty(true);
}

function rmQuitarMpATalla(codTalla) {
	var linea = rmLineaActual();
	if (!linea) return;
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_talla || "") === String(codTalla)) {
			v.mp_codigo = "";
		}
	});
	rmMarcarDirty(true);
}

function rmQuitarMpTodos() {
	var linea = rmLineaActual();
	if (!linea) return;
	(linea.variantes || []).forEach(function (v) {
		v.mp_codigo = "";
	});
	rmMarcarDirty(true);
}

/** Confirma acciones masivas. cb() si acepta. Compatible con SweetAlert2. */
function rmConfirmarMasivo(titulo, texto, cb) {
	if (typeof swal === "function") {
		swal({
			title: titulo || "Confirmar",
			text: texto || "",
			type: "warning",
			showCancelButton: true,
			confirmButtonText: "Sí, continuar",
			cancelButtonText: "Cancelar",
			confirmButtonColor: "#dd4b39",
			cancelButtonColor: "#6c757d"
		}).then(function (result) {
			// SweetAlert2: result.value === true al confirmar
			if (result && (result.value === true || result === true)) {
				cb();
			}
		});
		return;
	}
	if (window.confirm((titulo ? titulo + "\n" : "") + (texto || ""))) cb();
}

function rmExigirMpEnMano() {
	if (RM.mpActiva) return true;
	rmAlerta("info", "MP", "Primero elige una MP en el catálogo o en «MPs ya usadas»");
	return false;
}

function rmSincronizarConsumoEnVariantes() {
	var linea = rmLineaActual();
	if (!linea) return;
	var consumo = $("#rmConsumoLinea").val() || "1";
	linea.consumo_base = consumo;
	(linea.variantes || []).forEach(function (v) {
		v.consumo = consumo;
	});
	rmRenderChips();
	rmRenderTarjetasPorArticulo();
}

function rmCargarTablaMp() {
	var linea = rmLineaActual();
	var $tb = $("#rmTablaMp tbody");
	if (!linea || !linea.codigo_sublinea) {
		$tb.html("<tr><td colspan='4' class='text-muted'>Sin sublínea</td></tr>");
		return;
	}
	$tb.html("<tr><td colspan='4' class='text-muted'>Cargando MP…</td></tr>");
	rmPost({
		accion: "buscarMp",
		q: $("#rmFiltroMp").val() || "",
		codigo_sublinea: linea.codigo_sublinea,
		limit: 100
	}).done(function (resp) {
		$tb.empty();
		if (!resp || !resp.ok || !(resp.data || []).length) {
			$tb.html("<tr><td colspan='4' class='text-muted'>No hay MP para esta sublínea</td></tr>");
			return;
		}
		resp.data.forEach(function (mp) {
			rmCacheMp(mp);
			var activa = String(RM.mpActiva || "") === String(mp.mp_codigo) ? " activa" : "";
			$tb.append("<tr class='" + activa + "' data-mp='" + rmEsc(mp.mp_codigo) + "'>"
				+ "<td><div class='rm2-mp-color'>" + rmEsc(mp.color || "—") + "</div>"
				+ "<div class='rm2-mp-cod'>" + rmEsc(mp.mp_codigo) + "</div></td>"
				+ "<td><span class='rm2-mp-und'>" + rmEsc(mp.unidad || "—") + "</span></td>"
				+ "<td><div class='rm2-mp-desc' title='" + rmEsc(mp.descripcion) + "'>" + rmEsc(mp.descripcion) + "</div></td>"
				+ "<td><button type='button' class='btn btn-xs btn-primary rmElegirMpActiva'"
				+ " data-mp='" + rmEsc(mp.mp_codigo) + "'"
				+ " data-und='" + rmEsc(mp.unidad || "") + "'"
				+ " data-color='" + rmEsc(mp.color || "") + "'"
				+ " data-desc='" + rmEsc(mp.descripcion) + "'>Elegir</button></td>"
				+ "</tr>");
		});
	});
}

function rmPayloadLineas() {
	rmNormalizarNombresTela();
	return (RM.estado.lineas || []).map(function (linea, idx) {
		var consumo = (RM.lineaIdx === idx)
			? ($("#rmConsumoLinea").val() || linea.consumo_base || "1")
			: (linea.consumo_base || "1");
		linea.consumo_base = consumo;
		rmAsegurarVariantesArticulos(linea);
		var variantes = [];
		(linea.variantes || []).forEach(function (v) {
			if (!v.mp_codigo) return;
			variantes.push({
				cod_color: v.cod_color || "",
				cod_talla: v.cod_talla || "",
				mp_codigo: v.mp_codigo,
				consumo: consumo,
				observacion: null
			});
		});
		return {
			orden: idx + 1,
			nombre_rol: (function () {
				var n = linea.nombre_rol || ("Insumo " + (idx + 1));
				if (n === "Tela principal" && !Number(linea.es_tela_principal)) {
					n = linea.codigo_sublinea || n;
				}
				return n;
			})(),
			es_tela_principal: Number(linea.es_tela_principal) ? 1 : 0,
			codigo_sublinea: linea.codigo_sublinea || null,
			regla_variante: "COLOR_TALLA",
			unidad: linea.unidad || null,
			consumo_base: consumo,
			mp_base_codigo: null,
			activo: 1,
			variantes: variantes
		};
	});
}

function rmCargarEditor() {
	var id = Number($("#rmIdReceta").val() || 0);
	if (!id) return;
	rmPost({ accion: "detalle", id_receta: id }).done(function (resp) {
		if (!resp || !resp.ok) {
			rmAlerta("error", "Error", (resp && resp.mensaje) || "No se pudo cargar");
			return;
		}
		RM.estado = resp.data;
		(RM.estado.lineas || []).forEach(function (l) {
			if (!Array.isArray(l.variantes)) l.variantes = [];
		});
		rmNormalizarNombresTela();
		rmPost({ accion: "articulosModelo", modelo: RM.estado.cabecera.modelo }).done(function (artResp) {
			RM.articulos = (artResp && artResp.ok && artResp.data && artResp.data.articulos)
				? artResp.data.articulos
				: [];
			RM.articulos.sort(function (a, b) {
				var c = String(a.cod_color || "").localeCompare(String(b.cod_color || ""));
				if (c !== 0) return c;
				return (parseInt(a.cod_talla, 10) || 0) - (parseInt(b.cod_talla, 10) || 0);
			});
			if (RM.lineaIdx === null && RM.estado.lineas.length) RM.lineaIdx = 0;
			var artKeysExactos = {};
			(RM.articulos || []).forEach(function (a) {
				artKeysExactos[rmClaveArt(a)] = true;
			});
			var needsRemap = false;
			(RM.estado.lineas || []).forEach(function (l) {
				(l.variantes || []).forEach(function (v) {
					if (!v.mp_codigo) return;
					var k = String(v.cod_color || "") + "|" + String(v.cod_talla || "");
					if (!artKeysExactos[k]) needsRemap = true;
				});
			});
			RM.estado.lineas.forEach(rmAsegurarVariantesArticulos);
			if (rmNormalizarNombresTela() || needsRemap) rmMarcarDirty(true);
			else rmMarcarDirty(false);
			rmEnriquecerNombresSublinea(function () {
				rmRenderCabecera();
				rmRenderChips();
				rmActualizarContextoLineaActiva();
				rmEnriquecerMpsAsignadas(function () {
					rmRenderMatriz();
					rmCargarTablaMp();
				});
				rmCargarCobertura();
			});
		});
	});
}

function rmGuardar(silent) {
	if (!rmEsBorradorEditable()) return $.Deferred().reject().promise();
	rmSincronizarConsumoEnVariantes();
	var keep = RM.lineaIdx;
	$("#rmBtnGuardar").prop("disabled", true).html("<i class='fa fa-spinner fa-spin'></i>");
	return rmPost({
		accion: "guardarLineas",
		id_receta: RM.estado.cabecera.id,
		lineas: JSON.stringify(rmPayloadLineas())
	}).done(function (resp) {
		if (!resp || !resp.ok) {
			rmAlerta("error", "No se guardó", (resp && resp.mensaje) || "Error");
			return;
		}
		RM.estado = resp.data;
		(RM.estado.lineas || []).forEach(function (l) {
			if (!Array.isArray(l.variantes)) l.variantes = [];
			rmAsegurarVariantesArticulos(l);
		});
		rmNormalizarNombresTela();
		RM.lineaIdx = keep !== null && RM.estado.lineas[keep] ? keep : (RM.estado.lineas.length ? 0 : null);
		rmMarcarDirty(false);
		rmEnriquecerNombresSublinea(function () {
			rmRenderCabecera();
			rmRenderChips();
			rmActualizarContextoLineaActiva();
			rmEnriquecerMpsAsignadas(function () {
				rmRenderMatriz();
				rmCargarTablaMp();
			});
			rmCargarCobertura();
			if (!silent) rmAlerta("success", "Guardado", "Cambios guardados");
		});
	}).always(function () {
		$("#rmBtnGuardar").prop("disabled", !rmEsBorradorEditable()).html("<i class='fa fa-save'></i> Guardar");
	});
}

function rmCargarCobertura() {
	var id = Number($("#rmIdReceta").val() || 0);
	if (!id) return;
	rmPost({ accion: "validarCobertura", id_receta: id, bloquear_complementarios: 1 }).done(function (resp) {
		if (!resp || !resp.ok) return;
		var d = resp.data;
		$("#rmResumenCobertura").html(
			"<div class='col-sm-3'><div class='info-box bg-aqua'><span class='info-box-icon'><i class='fa fa-cubes'></i></span><div class='info-box-content'><span class='info-box-text'>Artículos</span><span class='info-box-number'>" + d.total_articulos + "</span></div></div></div>"
			+ "<div class='col-sm-3'><div class='info-box bg-green'><span class='info-box-icon'><i class='fa fa-check'></i></span><div class='info-box-content'><span class='info-box-text'>Completos</span><span class='info-box-number'>" + d.ok + "</span></div></div></div>"
			+ "<div class='col-sm-3'><div class='info-box bg-yellow'><span class='info-box-icon'><i class='fa fa-warning'></i></span><div class='info-box-content'><span class='info-box-text'>Alertas</span><span class='info-box-number'>" + d.alertas + "</span></div></div></div>"
			+ "<div class='col-sm-3'><div class='info-box " + (d.puede_publicar ? "bg-green" : "bg-red") + "'><span class='info-box-icon'><i class='fa fa-flag'></i></span><div class='info-box-content'><span class='info-box-text'>¿Publicar?</span><span class='info-box-number'>" + (d.puede_publicar ? "Sí" : "No") + "</span></div></div></div>"
		);
	});
}

function rmBuscarSublineas() {
	rmPost({
		accion: "listarSublineas",
		q: $("#rmBuscarSublineaQ").val() || "",
		limit: 200
	}).done(function (resp) {
		var $tb = $("#rmTablaSublineas tbody").empty();
		(resp && resp.data || []).forEach(function (s) {
			$tb.append("<tr>"
				+ "<td><strong>" + rmEsc(s.codigo_sublinea) + "</strong></td>"
				+ "<td>" + rmEsc(s.linea) + "</td>"
				+ "<td>" + rmEsc(s.nombre) + "</td>"
				+ "<td><button type='button' class='btn btn-xs btn-primary rmPickSub' data-cod='" + rmEsc(s.codigo_sublinea) + "' data-nom='" + rmEsc(s.nombre) + "'>Elegir</button></td>"
				+ "</tr>");
		});
	});
}

/* ===================== EVENTOS ===================== */

$(document).ready(function () {
	if ($("#tablaRecetasModelo").length) {
		rmCargarListado();
		rmCargarEstadisticas();
		$("#btnFiltrarRecetasModelo").on("click", function () {
			rmMarcarKpiActivo($("#filtroEstadoReceta").val() || "");
			rmCargarListado();
		});
		$("#filtroModeloReceta").on("keydown", function (e) {
			if (e.which === 13) {
				e.preventDefault();
				rmMarcarKpiActivo($("#filtroEstadoReceta").val() || "");
				rmCargarListado();
			}
		});
		$("#filtroEstadoReceta").on("change", function () {
			rmMarcarKpiActivo($(this).val() || "");
		});
		$("#rmStatsCabecera").on("click", ".rm-kpi", function () {
			var $btn = $(this);
			if ($btn.data("filtro") === "sin_receta") {
				$("#rmStatsCabecera .rm-kpi").removeClass("active");
				$btn.addClass("active");
				return;
			}
			if ($btn.data("filtro") === "sin_tela") {
				$("#rmStatsCabecera .rm-kpi").removeClass("active");
				$btn.addClass("active");
				return;
			}
			var est = $btn.attr("data-estado");
			if (typeof est === "undefined") est = "";
			$("#filtroEstadoReceta").val(est);
			rmMarcarKpiActivo(est);
			rmCargarListado();
		});
		$("#btnCrearRecetaModelo").on("click", function () {
			var modelo = $.trim($("#nuevoModeloReceta").val() || "");
			if (!modelo) {
				rmAlerta("warning", "Modelo", "Ingresa el código de modelo");
				return;
			}
			rmPost({ accion: "crearBorrador", modelo: modelo, con_tela_principal: 0 }).done(function (resp) {
				if (!resp || !resp.ok) {
					rmAlerta("error", "Error", (resp && resp.mensaje) || "No se creó");
					return;
				}
				window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + resp.data.id;
			});
		});

		function rmCargarSelectImportTarjetas() {
			var $sel = $("#importModeloReceta");
			$sel.prop("disabled", true).empty();
			if (typeof $sel.selectpicker === "function") {
				$sel.selectpicker("refresh");
			}
			rmPost({ accion: "listarModelosImportTarjetas" }).done(function (resp) {
				$sel.empty();
				var lista = (resp && resp.ok && resp.data && resp.data.modelos) ? resp.data.modelos : [];
				if (!lista.length) {
					$sel.append("<option value=''>No hay modelos pendientes</option>");
					return;
				}
				lista.forEach(function (m) {
					var label = (m.modelo || "")
						+ (m.nombre_modelo && m.nombre_modelo !== m.modelo ? " — " + m.nombre_modelo : "")
						+ " (" + (m.articulos_con_tarjeta || 0) + " arts)";
					$sel.append("<option value='" + rmEsc(m.modelo) + "'>" + rmEsc(label) + "</option>");
				});
			}).fail(function () {
				$sel.html("<option value=''>Error al cargar</option>");
			}).always(function () {
				$sel.prop("disabled", false);
				if (typeof $sel.selectpicker === "function") {
					$sel.selectpicker("refresh");
				}
			});
		}

		$("#modalImportarTarjetasReceta").on("show.bs.modal", rmCargarSelectImportTarjetas);
		$("#modalImportarTarjetasReceta").on("shown.bs.modal", function () {
			var $sel = $("#importModeloReceta");
			if (typeof $sel.selectpicker === "function") {
				$sel.selectpicker("refresh");
			}
		});
		$("#modalModelosSinReceta").on("show.bs.modal", rmCargarTablaModelosSinReceta);

		$("#btnEjecutarImportTarjetas").on("click", function () {
			var modelo = $.trim($("#importModeloReceta").val() || "");
			if (!modelo) {
				rmAlerta("warning", "Modelo", "Selecciona un modelo de la lista");
				return;
			}
			rmEjecutarImportModelo(modelo, $(this));
		});

		function rmLimpiarModalExcelReceta() {
			$("#archivoExcelReceta").val("");
			$("#resumenImportacionExcelReceta").hide().removeClass("alert-success alert-danger").text("");
			$("#contenedorPreviewExcelReceta, #contenedorPreviewModelosExcelReceta").hide();
			$("#previewImportacionExcelRecetaBody, #previewModelosExcelRecetaBody").empty();
			$("#btnConfirmarExcelReceta").prop("disabled", true);
		}

		function rmEnviarImportExcelReceta(confirmar) {
			var input = $("#archivoExcelReceta")[0];
			if (!input || !input.files || !input.files.length) {
				rmAlerta("warning", "Archivo", "Selecciona un archivo Excel o CSV");
				return;
			}
			var datos = new FormData();
			datos.append("accion", "importarDesdeExcel");
			datos.append("confirmar", confirmar ? "1" : "0");
			datos.append("archivo", input.files[0]);

			var $botones = $("#btnPrevisualizarExcelReceta, #btnConfirmarExcelReceta");
			$botones.prop("disabled", true);

			$.ajax({
				url: RM.url,
				method: "POST",
				data: datos,
				dataType: "json",
				processData: false,
				contentType: false
			}).done(function (resp) {
				if (!resp || !resp.ok) {
					rmAlerta("error", "Importación", (resp && resp.mensaje) || "No se pudo procesar el archivo");
					return;
				}
				if (confirmar) {
					$("#modalImportarExcelReceta").modal("hide");
					rmLimpiarModalExcelReceta();
					rmActualizarAlertaSinReceta();
					rmCargarListado();
					var creados = (resp.data && resp.data.creados) ? resp.data.creados : [];
					var detalle = creados.map(function (c) {
						return c.modelo + " v" + c.version;
					}).join(", ");
					rmAlerta(
						"success",
						"Importado",
						(resp.mensaje || "Listo") + (detalle ? "\n" + detalle : "")
					);
					if (creados.length === 1 && creados[0].id) {
						window.setTimeout(function () {
							window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + creados[0].id;
						}, 800);
					}
					return;
				}

				var rechazadas = Number(resp.rechazadas || 0);
				var modelosError = Number(resp.modelos_error || 0);
				var aCrear = Number(resp.a_crear || 0);
				var puedeImportar = aCrear >= 1;
				var parcial = puedeImportar && (rechazadas > 0 || modelosError > 0);
				var resumenTxt = "Filas: " + (resp.total || 0)
					+ " · Válidas: " + (resp.validas || 0)
					+ " · Rechazadas: " + rechazadas
					+ " · Modelos a crear: " + aCrear
					+ (modelosError ? " · Modelos omitidos: " + modelosError : "");
				if (parcial) {
					resumenTxt += " · Se importarán solo los válidos";
				}
				if (resp.preview_limitado) {
					resumenTxt += " · Mostrando " + (resp.preview_mostradas || 0)
						+ " filas en pantalla (prioriza errores)";
				}
				$("#resumenImportacionExcelReceta")
					.removeClass("alert-success alert-danger alert-warning")
					.addClass(!puedeImportar ? "alert-danger" : (parcial ? "alert-warning" : "alert-success"))
					.text(resumenTxt)
					.show();

				var modelosHtml = (resp.modelos || []).map(function (m) {
					var err = (m.errores && m.errores.length)
						? "<span class='text-danger'>" + rmEsc(m.errores.join("; ")) + "</span>"
						: "<span class='text-success'>OK</span>";
					var accion = m.accion === "crear_borrador"
						? "<span class='label label-primary'>Crear borrador</span>"
						: "<span class='label label-default'>Omitir</span>";
					return "<tr><td>" + rmEsc(m.modelo)
						+ "</td><td>" + rmEsc(m.lineas)
						+ "</td><td>" + rmEsc(m.variantes)
						+ "</td><td>" + accion
						+ "</td><td>" + err + "</td></tr>";
				});
				$("#previewModelosExcelRecetaBody").html(modelosHtml.join(""));
				$("#contenedorPreviewModelosExcelReceta").toggle(modelosHtml.length > 0);

				var filasHtml = (resp.data || []).map(function (fila) {
					var errores = (fila.errores && fila.errores.length)
						? "<span class='text-danger'>" + rmEsc(fila.errores.join("; ")) + "</span>"
						: "<span class='text-success'>OK</span>";
					var mpTxt = fila.mp_codigo || "";
					if (fila.mp_normalizado && fila.mp_codigo_original) {
						mpTxt = fila.mp_codigo_original + " → " + fila.mp_codigo;
					}
					return "<tr><td>" + rmEsc(fila.fila)
						+ "</td><td>" + rmEsc(fila.articulo)
						+ "</td><td>" + rmEsc(fila.modelo)
						+ "</td><td>" + rmEsc(fila.cod_color)
						+ "</td><td>" + rmEsc(fila.cod_talla)
						+ "</td><td>" + rmEsc(mpTxt)
						+ "</td><td>" + rmEsc(fila.consumo)
						+ "</td><td>" + errores + "</td></tr>";
				});
				$("#previewImportacionExcelRecetaBody").html(filasHtml.join(""));
				$("#contenedorPreviewExcelReceta").show();
				$("#btnConfirmarExcelReceta")
					.prop("disabled", !puedeImportar)
					.html(parcial
						? "<i class='fa fa-check'></i> Importar " + aCrear + " modelo(s) válido(s)"
						: "<i class='fa fa-check'></i> Confirmar importación");
				window._rmImportExcelParcial = parcial;
				window._rmImportExcelACrear = aCrear;
				window._rmImportExcelRechazadas = rechazadas;
			}).fail(function () {
				rmAlerta("error", "Error", "No se pudo comunicar con el servidor");
			}).always(function () {
				$("#btnPrevisualizarExcelReceta").prop("disabled", false);
				if (Number(window._rmImportExcelACrear || 0) >= 1) {
					$("#btnConfirmarExcelReceta").prop("disabled", false);
				}
			});
		}

		$("#btnPrevisualizarExcelReceta").on("click", function () {
			rmEnviarImportExcelReceta(false);
		});
		$("#btnConfirmarExcelReceta").on("click", function () {
			var aCrear = Number(window._rmImportExcelACrear || 0);
			if (aCrear < 1) {
				rmAlerta("warning", "Importación", "No hay modelos válidos para importar");
				return;
			}
			if (window._rmImportExcelParcial) {
				rmConfirmarMasivo(
					"Importar solo válidos",
					"Hay " + (window._rmImportExcelRechazadas || 0)
						+ " fila(s) con error. Se importarán " + aCrear
						+ " modelo(s) con las filas correctas y se omitirán las fallidas.",
					function () {
						rmEnviarImportExcelReceta(true);
					}
				);
				return;
			}
			rmEnviarImportExcelReceta(true);
		});
		$("#modalImportarExcelReceta").on("hidden.bs.modal", function () {
			rmLimpiarModalExcelReceta();
			window._rmImportExcelParcial = false;
			window._rmImportExcelACrear = 0;
			window._rmImportExcelRechazadas = 0;
			$("#btnConfirmarExcelReceta").html("<i class='fa fa-check'></i> Confirmar importación");
		});
		$("#archivoExcelReceta").on("change", function () {
			$("#btnConfirmarExcelReceta").prop("disabled", true)
				.html("<i class='fa fa-check'></i> Confirmar importación");
			window._rmImportExcelParcial = false;
			window._rmImportExcelACrear = 0;
			window._rmImportExcelRechazadas = 0;
			$("#resumenImportacionExcelReceta").hide();
			$("#contenedorPreviewExcelReceta, #contenedorPreviewModelosExcelReceta").hide();
		});

		$(document).on("click", ".btnImportarModeloLista", function () {
			rmEjecutarImportModelo(String($(this).attr("data-modelo") || ""), $(this));
		});

		$(document).on("click", ".btnDuplicarRecetaLista", function () {
			rmPost({ accion: "duplicarVersion", id_receta: $(this).data("id") }).done(function (resp) {
				if (resp && resp.ok) window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + resp.data.id;
				else rmAlerta("error", "Error", (resp && resp.mensaje) || "");
			});
		});
		$(document).on("click", ".btnPublicarRecetaLista", function () {
			rmPost({ accion: "publicar", id_receta: $(this).data("id"), bloquear_complementarios: 1 }).done(function (resp) {
				if (!resp || !resp.ok) rmAlerta("error", "No se publicó", (resp && resp.mensaje) || "");
				else {
					rmAlerta("success", "Publicada", "");
					rmCargarEstadisticas();
					rmCargarListado();
				}
			});
		});
		$(document).on("click", ".btnEliminarRecetaLista", function () {
			var id = $(this).data("id");
			var modelo = String($(this).attr("data-modelo") || $(this).data("modelo") || "");
			var version = String($(this).attr("data-version") || $(this).data("version") || "");
			var estado = String($(this).attr("data-estado") || $(this).data("estado") || "");
			rmConfirmarMasivo(
				"Eliminar receta",
				"Se borrará la receta del modelo " + modelo + " (v" + version + ", " + estado + "). Esta acción no se puede deshacer.",
				function () {
					rmPost({ accion: "eliminarReceta", id_receta: id }).done(function (resp) {
						if (!resp || !resp.ok) {
							rmAlerta("error", "No se eliminó", (resp && resp.mensaje) || "");
							return;
						}
						rmAlerta("success", "Eliminado", "Receta borrada.");
						rmActualizarAlertaSinReceta();
						rmCargarListado();
					}).fail(function () {
						rmAlerta("error", "Error", "No se pudo comunicar con el servidor");
					});
				}
			);
		});

		$("#btnBorrarTodasRecetasModelo").on("click", function () {
			rmConfirmarMasivo(
				"Borrar TODAS las recetas",
				"Se eliminarán todas las recetas por modelo (borrador, publicadas y archivadas). No afecta las tarjetas antiguas. ¿Continuar?",
				function () {
					rmConfirmarMasivo(
						"Confirmación final",
						"Última confirmación: se borrará TODO el catálogo de recetas por modelo.",
						function () {
							var $btn = $("#btnBorrarTodasRecetasModelo").prop("disabled", true);
							rmPost({ accion: "eliminarTodas" }).done(function (resp) {
								if (!resp || !resp.ok) {
									rmAlerta("error", "No se borró", (resp && resp.mensaje) || "");
									return;
								}
								rmAlerta("success", "Listo", resp.mensaje || "Recetas eliminadas");
								rmActualizarAlertaSinReceta();
								rmCargarListado();
							}).fail(function () {
								rmAlerta("error", "Error", "No se pudo comunicar con el servidor");
							}).always(function () {
								$btn.prop("disabled", false);
							});
						}
					);
				}
			);
		});

		$(document).on("click", ".btnPreviewRecetaLista", function () {
			var id = $(this).attr("data-id") || $(this).data("id");
			var modelo = String($(this).attr("data-modelo") || "");
			$("#previewIdReceta").val(id);
			$("#previewModeloReceta").val(modelo);
			$("#previewExplosionResultado").empty();
			$("#previewCantidad").val("1");
			$("#modalPreviewExplosionReceta").modal("show");
			rmCargarSelectPreviewArticulos(modelo);
		});
		$("#modalPreviewExplosionReceta").on("shown.bs.modal", function () {
			var $sel = $("#previewArticulo");
			if (typeof $sel.selectpicker === "function") {
				$sel.selectpicker("refresh");
			}
		});
		$("#btnEjecutarPreviewReceta").on("click", function () {
			var articulo = $.trim($("#previewArticulo").val() || "");
			if (!articulo) {
				rmAlerta("warning", "Artículo", "Selecciona un artículo del modelo");
				return;
			}
			rmPost({
				accion: "previsualizarExplosion",
				id_receta: $("#previewIdReceta").val(),
				articulo: articulo,
				cantidad: $("#previewCantidad").val() || 1
			}).done(function (resp) {
				var $out = $("#previewExplosionResultado");
				var data = resp && resp.data ? resp.data : null;
				var consolidados = data && data.consolidados ? data.consolidados : null;
				if (!resp || (!resp.ok && !consolidados)) {
					$out.html("<div class='alert alert-danger'>" + rmEsc((resp && resp.mensaje) || "Error") + "</div>");
					return;
				}
				var html = "<table class='table table-bordered table-condensed'><thead><tr>"
					+ "<th>MP</th><th>Descripción</th><th>Color</th><th>Und</th><th>Total</th><th>Tela</th>"
					+ "</tr></thead><tbody>";
				(consolidados || []).forEach(function (c) {
					html += "<tr>"
						+ "<td>" + rmEsc(c.mp_codigo) + "</td>"
						+ "<td>" + rmEsc(c.mp_descripcion || "") + "</td>"
						+ "<td>" + rmEsc(c.mp_color || "") + "</td>"
						+ "<td>" + rmEsc(c.unidad || "") + "</td>"
						+ "<td>" + rmEsc(c.consumo_total) + "</td>"
						+ "<td>" + (c.es_tela_principal ? "SI" : "") + "</td>"
						+ "</tr>";
				});
				$out.html(html + "</tbody></table>");
				if (!resp.ok || (data.errores && data.errores.length)) {
					var avisos = (data.errores || []).map(function (e) {
						return rmEsc((e.mensaje || e.tipo || "") + (e.rol ? " (" + e.rol + ")" : ""));
					}).join("<br>");
					$out.prepend(
						"<div class='alert alert-warning'>"
						+ rmEsc(resp.mensaje || "Explosión incompleta")
						+ (avisos ? "<br>" + avisos : "")
						+ "</div>"
					);
				}
			});
		});
	}

	if (!$("#rmIdReceta").length) return;

	rmCargarEditor();

	$("#rmBtnBuscarSublineaTop, #rmBtnBuscarSublineaIcon").on("click", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$("#rmBuscarSublineaQ").val("");
		$("#modalSublineasReceta").modal("show");
		rmBuscarSublineas();
	});
	$("#rmBtnBuscarSublinea").on("click", rmBuscarSublineas);
	$("#rmBuscarSublineaQ").on("keydown", function (e) {
		if (e.which === 13) rmBuscarSublineas();
	});
	$(document).on("click", ".rmPickSub", function () {
		var cod = String($(this).attr("data-cod") || $(this).data("cod") || "");
		var nom = String($(this).attr("data-nom") || $(this).data("nom") || "");
		rmSetPickSublinea(cod, nom);
		$("#modalSublineasReceta").modal("hide");
	});

	$("#rmBtnAgregarSublinea").on("click", function () {
		if (!rmEsBorradorEditable()) return;
		var sub = $.trim($("#rmNuevaSublinea").val() || "");
		var nom = $.trim($("#rmNuevaSublineaNom").text() || "");
		if (!sub) {
			rmAlerta("warning", "Sublínea", "Elige una sublínea");
			return;
		}
		if (!nom || nom === "\u00a0" || nom.indexOf("Solo agrega") === 0 || nom.indexOf("Luego marca") === 0) {
			nom = sub;
		}
		if (sub && nom && nom !== sub) {
			RM.sublineaCache[String(sub).toUpperCase()] = nom;
		}

		rmSincronizarConsumoEnVariantes();
		var lineaAnteriorIdx = RM.lineaIdx;

		var linea = {
			orden: RM.estado.lineas.length + 1,
			nombre_rol: nom,
			es_tela_principal: 0,
			codigo_sublinea: sub,
			regla_variante: "COLOR_TALLA",
			unidad: "",
			consumo_base: "1",
			mp_base_codigo: "",
			activo: 1,
			variantes: []
		};
		rmAsegurarVariantesArticulos(linea);
		RM.estado.lineas.push(linea);
		RM.lineaAnteriorIdx = (lineaAnteriorIdx !== null && RM.estado.lineas[lineaAnteriorIdx])
			? lineaAnteriorIdx
			: null;
		RM.lineaIdx = RM.estado.lineas.length - 1;
		RM.mpActiva = null;
		RM.mpActivaUnd = "";
		RM.mpActivaColor = "";

		rmLimpiarPickSublinea();

		rmMarcarDirty(true);
		rmRefrescarPaso2(true);
		rmActualizarContextoLineaActiva(
			"Ahora estás configurando: " + rmEtiquetaLinea(linea),
			{ mostrarVolver: RM.lineaAnteriorIdx !== null }
		);
		var $ctx = $("#rmLineaActivaContexto");
		if ($ctx.length && $ctx[0].scrollIntoView) {
			$ctx[0].scrollIntoView({ behavior: "smooth", block: "start" });
		}
		setTimeout(function () {
			$("#rmConsumoLinea").focus().select();
		}, 120);
	});

	$(document).on("click", ".rm2-chip", function (e) {
		if ($(e.target).closest(".rmQuitarChip, .rmToggleTela").length) return;
		rmSincronizarConsumoEnVariantes();
		var nuevoIdx = Number($(this).attr("data-idx"));
		if (nuevoIdx === RM.lineaIdx) return;
		RM.lineaAnteriorIdx = null;
		RM.lineaIdx = nuevoIdx;
		RM.mpActiva = null;
		RM.mpActivaUnd = "";
		RM.mpActivaColor = "";
		$("#rmCtxFlash").removeClass("visible").hide();
		$("#rmBtnVolverLineaAnterior").hide();
		if (RM._ctxFlashTimer) {
			clearTimeout(RM._ctxFlashTimer);
			RM._ctxFlashTimer = null;
		}
		rmRenderChips();
		rmEnriquecerMpsAsignadas(function () {
			rmRenderMatriz();
			rmCargarTablaMp();
		});
	});

	$("#rmBtnVolverLineaAnterior").on("click", function () {
		if (RM.lineaAnteriorIdx === null || !RM.estado || !RM.estado.lineas[RM.lineaAnteriorIdx]) {
			return;
		}
		rmSincronizarConsumoEnVariantes();
		RM.lineaIdx = RM.lineaAnteriorIdx;
		RM.lineaAnteriorIdx = null;
		RM.mpActiva = null;
		RM.mpActivaUnd = "";
		RM.mpActivaColor = "";
		$("#rmCtxFlash").removeClass("visible").hide();
		$("#rmBtnVolverLineaAnterior").hide();
		if (RM._ctxFlashTimer) {
			clearTimeout(RM._ctxFlashTimer);
			RM._ctxFlashTimer = null;
		}
		rmRefrescarPaso2(true);
	});

	$(document).on("click", ".rmToggleTela", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable()) return;
		var idx = Number($(this).attr("data-idx"));
		var linea = RM.estado.lineas[idx];
		if (!linea) return;
		var activar = Number(linea.es_tela_principal) !== 1;
		RM.estado.lineas.forEach(function (l) { l.es_tela_principal = 0; });
		if (activar) linea.es_tela_principal = 1;
		rmNormalizarNombresTela();
		rmMarcarDirty(true);
		rmRenderChips();
	});

	$(document).on("click", ".rmQuitarChip", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable()) return;
		var idx = Number($(this).attr("data-idx"));
		RM.estado.lineas.splice(idx, 1);
		RM.lineaIdx = RM.estado.lineas.length ? Math.min(idx, RM.estado.lineas.length - 1) : null;
		rmMarcarDirty(true);
		rmRefrescarPaso2(true);
	});

	$("#rmConsumoLinea").on("change", function () {
		rmSincronizarConsumoEnVariantes();
		rmMarcarDirty(true);
		rmRenderChips();
		rmActualizarContextoLineaActiva();
	});

	$("#rmBtnFiltroMp").on("click", rmCargarTablaMp);
	$("#rmFiltroMp").on("keydown", function (e) {
		if (e.which === 13) rmCargarTablaMp();
	});

	$(document).on("click", ".rmElegirMpActiva", function () {
		var $btn = $(this);
		rmSetMpActiva(
			String($btn.attr("data-mp") || ""),
			String($btn.attr("data-und") || ""),
			String($btn.attr("data-color") || ""),
			String($btn.attr("data-desc") || "")
		);
	});

	$(document).on("click", ".rm2-asig-chip", function () {
		if (!rmEsBorradorEditable()) return;
		var $btn = $(this);
		rmSetMpActiva(
			String($btn.attr("data-mp") || ""),
			String($btn.attr("data-und") || ""),
			String($btn.attr("data-color") || ""),
			String($btn.attr("data-desc") || "")
		);
	});

	$("#rmBtnLimpiarMpActiva").on("click", function () {
		rmSetMpActiva(null, "", "");
	});

	$(document).on("click", ".rmAplicarTalla", function () {
		if (!rmEsBorradorEditable() || !rmExigirMpEnMano()) return;
		rmAplicarMpATalla(String($(this).attr("data-talla") || ""), RM.mpActiva);
		rmRefrescarPaso2(false);
	});

	$(document).on("click", ".rmQuitarTalla", function () {
		if (!rmEsBorradorEditable()) return;
		rmQuitarMpATalla(String($(this).attr("data-talla") || ""));
		rmRefrescarPaso2(false);
	});

	$(document).on("click", ".rmAplicarTodos", function () {
		if (!rmEsBorradorEditable() || !rmExigirMpEnMano()) return;
		var total = (rmLineaActual() && rmLineaActual().variantes) ? rmLineaActual().variantes.length : 0;
		var etiqueta = rmEtiquetaMp(RM.mpActiva) || RM.mpActiva;
		rmConfirmarMasivo(
			"Aplicar a todos",
			"Se asignará «" + etiqueta + "» a " + total + " celdas (color×talla). ¿Continuar?",
			function () {
				rmAplicarMpTodos(RM.mpActiva);
				rmRefrescarPaso2(false);
			}
		);
	});

	$(document).on("click", ".rmQuitarTodos", function () {
		if (!rmEsBorradorEditable()) return;
		var n = rmContarVariantesConMp();
		if (!n) {
			rmAlerta("info", "Sin asignaciones", "No hay MP que quitar en esta sublínea");
			return;
		}
		rmConfirmarMasivo(
			"Quitar de todos",
			"Se quitará la MP de " + n + " celdas. Quedarán incompletas hasta reasignar. ¿Continuar?",
			function () {
				rmQuitarMpTodos();
				rmRefrescarPaso2(false);
			}
		);
	});

	$(document).on("click", ".rmAplicarColor", function () {
		if (!rmEsBorradorEditable() || !rmExigirMpEnMano()) return;
		rmAplicarMpAColor(String($(this).attr("data-color") || ""), RM.mpActiva);
		rmRefrescarPaso2(false);
	});

	$(document).on("click", ".rmQuitarColor", function () {
		if (!rmEsBorradorEditable()) return;
		rmQuitarMpAColor(String($(this).attr("data-color") || ""));
		rmRefrescarPaso2(false);
	});

	$(document).on("click", ".rm2-celda", function (e) {
		if (!rmEsBorradorEditable()) return;
		var key = String($(this).attr("data-key") || "");
		if (e.altKey) {
			rmQuitarMpAKey(key);
			rmRefrescarPaso2(false);
			return;
		}
		if (!rmExigirMpEnMano()) return;
		rmAplicarMpAKey(key, RM.mpActiva);
		rmRefrescarPaso2(false);
	});

	$("#rmBtnGuardar").on("click", function () { rmGuardar(false); });

	$("#rmBtnPublicar").on("click", function () {
		if (RM.dirty) {
			rmGuardar(true).done(function (resp) {
				if (!resp || !resp.ok) return;
				rmPublicarActual();
			});
			return;
		}
		rmPublicarActual();
	});

	$("#rmBtnDuplicar").on("click", function () {
		rmPost({ accion: "duplicarVersion", id_receta: $("#rmIdReceta").val() }).done(function (resp) {
			if (resp && resp.ok) window.location = "index.php?ruta=editar-receta-modelo&idReceta=" + resp.data.id;
			else rmAlerta("error", "Error", (resp && resp.mensaje) || "");
		});
	});

	// Revisar = solo cobertura de lo ya guardado (no autoguarda ni limpia el editor)
	$("#rmBtnRefrescarCobertura").on("click", function () {
		if (RM.dirty) {
			$("#rmMsgEstado").text("Hay cambios sin guardar — Revisar usa lo último guardado");
		}
		rmCargarCobertura();
	});
});

function rmPublicarActual() {
	rmPost({
		accion: "publicar",
		id_receta: RM.estado.cabecera.id,
		bloquear_complementarios: 1
	}).done(function (r) {
		if (!r || !r.ok) rmAlerta("error", "No se publicó", (r && r.mensaje) || "");
		else {
			rmAlerta("success", "Publicada", "");
			rmCargarEditor();
		}
	});
}