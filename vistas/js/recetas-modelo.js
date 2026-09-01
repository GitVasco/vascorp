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
	mpCatalogo: [],
	mpCatalogoSub: "",
	sublineaCache: {},
	dirty: false,
	_ctxFlashTimer: null,
	_mpReqSeq: 0,
	_mpFiltroTimer: null,
	_enRender: false,
	consumoMpPendiente: {}
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

function rmLineaTieneMp(linea) {
	if (!linea) return false;
	if (linea.mp_base_codigo) return true;
	var vars = linea.variantes || [];
	for (var i = 0; i < vars.length; i++) {
		if (vars[i] && vars[i].mp_codigo) return true;
	}
	return false;
}

/** La sublínea activa existe y todavía no tiene MP: se puede reemplazar sin perder asignaciones. */
function rmLineaActualReemplazable() {
	var linea = rmLineaActual();
	if (!linea || Number(linea.activo) === 0) return false;
	return !rmLineaTieneMp(linea);
}

function rmNormSublinea(cod) {
	return String(cod || "").toUpperCase().replace(/[\s\u00A0\u2000-\u200B\uFEFF\u0000]+/g, "");
}

function rmMpDominanteLinea(linea) {
	var counts = {};
	(linea && linea.variantes || []).forEach(function (v) {
		var m = String(v && v.mp_codigo ? v.mp_codigo : "").replace(/\s+/g, "");
		if (!m) return;
		counts[m] = (counts[m] || 0) + 1;
	});
	var best = "";
	var n = 0;
	Object.keys(counts).forEach(function (m) {
		if (counts[m] > n) {
			n = counts[m];
			best = m;
		}
	});
	return best;
}

function rmIdxLineaPorSublinea(cod) {
	var needle = rmNormSublinea(cod);
	if (!needle || !RM.estado) return -1;
	var found = -1;
	(RM.estado.lineas || []).forEach(function (l, i) {
		if (Number(l.activo) === 0) return;
		if (rmNormSublinea(l.codigo_sublinea) === needle) found = i;
	});
	return found;
}

function rmConteoSublinea(cod) {
	var needle = rmNormSublinea(cod);
	if (!needle || !RM.estado) return 0;
	var n = 0;
	(RM.estado.lineas || []).forEach(function (l) {
		if (Number(l.activo) === 0) return;
		if (rmNormSublinea(l.codigo_sublinea) === needle) n++;
	});
	return n;
}

/** Número de capa (1, 2…) si la misma sublínea está más de una vez. */
function rmCapaDeLinea(linea, idx) {
	var needle = rmNormSublinea(linea && linea.codigo_sublinea);
	var total = 0;
	var nro = 0;
	if (!needle) return { total: 1, nro: 1 };
	(RM.estado && RM.estado.lineas || []).forEach(function (l, i) {
		if (Number(l.activo) === 0) return;
		if (rmNormSublinea(l.codigo_sublinea) !== needle) return;
		total++;
		if (idx != null && i === idx) nro = total;
		else if ((idx == null || idx === undefined) && l === linea) nro = total;
	});
	return { total: total, nro: nro || 1 };
}

function rmEtiquetaLineaConCapa(linea, idx) {
	var base = rmEtiquetaLinea(linea);
	var capa = rmCapaDeLinea(linea, idx);
	if (capa.total < 2) return base;
	return base + " · " + capa.nro;
}

/** Tela principal u otra capa de la misma tela: vacío = ese color no la usa. */
function rmLineaEsCapaOpcional(linea) {
	if (!linea) return false;
	if (Number(linea.es_tela_principal) === 1) return true;
	return rmConteoSublinea(linea.codigo_sublinea) > 1;
}

function rmLineasMismaSublinea(linea) {
	var sub = rmNormSublinea(linea && linea.codigo_sublinea);
	var out = [];
	(RM.estado && RM.estado.lineas || []).forEach(function (l, i) {
		if (Number(l.activo) === 0) return;
		if (sub && rmNormSublinea(l.codigo_sublinea) !== sub) return;
		if (!sub && l !== linea) return;
		out.push({ linea: l, idx: i });
	});
	return out;
}

function rmConsumoVariante(v, linea, codColor) {
	var vc = rmNormCons(v && v.consumo != null && v.consumo !== "" ? v.consumo : "");
	if (vc) return vc;
	return rmConsumoDeColor(linea, codColor);
}

function rmMpsEnCelda(codColor, codTalla, lineaBase) {
	var mps = [];
	var seen = {};
	rmLineasMismaSublinea(lineaBase || rmLineaActual()).forEach(function (item) {
		rmAsegurarVariantesArticulos(item.linea);
		(item.linea.variantes || []).forEach(function (v) {
			if (String(v.cod_color || "") !== String(codColor)) return;
			if (String(v.cod_talla || "") !== String(codTalla)) return;
			var mp = String(v.mp_codigo || "").replace(/\s+/g, "");
			if (!mp || seen[mp]) return;
			seen[mp] = true;
			mps.push({
				mp: mp,
				idx: item.idx,
				consumo: rmConsumoVariante(v, item.linea, codColor)
			});
		});
	});
	return mps;
}

function rmIndicesCapasDeMp(mp, lineaBase) {
	if (!mp) return [];
	var indices = [];
	rmLineasMismaSublinea(lineaBase || rmLineaActual()).forEach(function (item) {
		var tiene = false;
		(item.linea.variantes || []).forEach(function (v) {
			if (tiene) return;
			if (String(v.mp_codigo || "").replace(/\s+/g, "") === String(mp)) tiene = true;
		});
		if (tiene) indices.push(item.idx);
	});
	return indices;
}

/** Capa que usa esta MP en el color producto indicado (p. ej. FLAMENGO solo en BLANCO vs en PALO ROSA). */
function rmIdxCapaDeMpColor(mp, codColor, lineaBase) {
	if (!mp) return -1;
	var found = -1;
	rmLineasMismaSublinea(lineaBase || rmLineaActual()).forEach(function (item) {
		if (found >= 0) return;
		(item.linea.variantes || []).forEach(function (v) {
			if (found >= 0) return;
			if (String(v.cod_color || "") !== String(codColor || "")) return;
			if (String(v.mp_codigo || "").replace(/\s+/g, "") === String(mp)) found = item.idx;
		});
	});
	return found;
}

function rmIdxCapaDeMp(mp, lineaBase) {
	var indices = rmIndicesCapasDeMp(mp, lineaBase);
	if (!indices.length) return -1;
	if (indices.length === 1) return indices[0];
	if (RM.lineaIdx != null && indices.indexOf(RM.lineaIdx) >= 0) return RM.lineaIdx;
	return indices[0];
}

function rmInputConsumoColor(codColor) {
	return $(".rmConsumoColor").filter(function () {
		return String($(this).attr("data-color") || "") === String(codColor || "");
	});
}

function rmGuardarConsumoPendiente(mp, codColor, valor) {
	if (!mp) return;
	var k = String(mp);
	if (!RM.consumoMpPendiente[k]) RM.consumoMpPendiente[k] = {};
	RM.consumoMpPendiente[k][String(codColor || "")] = rmNormCons(valor);
}

function rmConsumoPendienteDe(mp, codColor) {
	var map = RM.consumoMpPendiente[String(mp || "")];
	if (!map) return "";
	return map[String(codColor || "")] || "";
}

function rmLimpiarConsumoPendiente(mp, codColor) {
	var map = RM.consumoMpPendiente[String(mp || "")];
	if (!map) return;
	delete map[String(codColor || "")];
}

function rmConsumoDesdeUi(mp, codColor, lineaDestino) {
	if (String(RM.mpActiva || "") === String(mp || "")) {
		var vivo = rmNormCons(rmInputConsumoColor(codColor).val());
		if (vivo) return vivo;
	}
	var pend = rmConsumoPendienteDe(mp, codColor);
	if (pend) return pend;
	if (lineaDestino) return rmNormCons(rmConsumoDeColor(lineaDestino, codColor));
	return "";
}

function rmLineaDeMp(mp) {
	var idx = rmIdxCapaDeMp(mp);
	if (idx < 0 || !RM.estado || !RM.estado.lineas) return null;
	return RM.estado.lineas[idx] || null;
}

function rmPintarInputConsumoColor($inp, valor, especial) {
	if (!$inp || !$inp.length) return;
	$inp.val(rmFmtNum(valor));
	var $wrap = $inp.closest(".rm2-cons-color");
	var $th = $inp.closest("th");
	$wrap.toggleClass("especial", !!especial);
	$th.toggleClass("especial", !!especial);
	$wrap.find(".rm2-esp-tag, .rmQuitarConsumoColor").toggle(!!especial);
}

function rmRefrescarInputsConsumoSegunMp() {
	var mp = RM.mpActiva;
	var linea = mp ? rmLineaDeMp(mp) : rmLineaActual();
	$(".rmConsumoColor").each(function () {
		var $inp = $(this);
		var c = String($inp.attr("data-color") || "");
		if (linea) {
			rmPintarInputConsumoColor($inp, rmConsumoDeColor(linea, c), rmColorTieneConsumoEspecial(linea, c));
			return;
		}
		var pend = rmConsumoPendienteDe(mp, c);
		var val = pend || "1";
		rmPintarInputConsumoColor($inp, val, !!(pend && !rmMismoConsumo(pend, "1")));
	});
}

function rmCrearCapaExtra(origen, consumoInicial) {
	var cons = rmNormCons(consumoInicial);
	if (!cons) cons = "1";
	var linea = {
		orden: rmSiguienteOrdenLinea(),
		nombre_rol: (origen && origen.nombre_rol) || "",
		es_tela_principal: 0,
		codigo_sublinea: origen && origen.codigo_sublinea,
		nombre_sublinea: origen && origen.nombre_sublinea,
		regla_variante: "COLOR_TALLA",
		unidad: (origen && origen.unidad) || "",
		consumo_base: cons,
		consumo_color: {},
		mp_base_codigo: "",
		activo: 1,
		variantes: []
	};
	rmAsegurarVariantesArticulos(linea);
	RM.estado.lineas.push(linea);
	return RM.estado.lineas.length - 1;
}

function rmIdxCapaLibreParaCelda(codColor, codTalla, mp) {
	var capas = rmLineasMismaSublinea(rmLineaActual());
	var i;
	for (i = 0; i < capas.length; i++) {
		var item = capas[i];
		rmAsegurarVariantesArticulos(item.linea);
		var tiene = "";
		(item.linea.variantes || []).forEach(function (v) {
			if (String(v.cod_color || "") !== String(codColor)) return;
			if (String(v.cod_talla || "") !== String(codTalla)) return;
			tiene = String(v.mp_codigo || "").replace(/\s+/g, "");
		});
		if (tiene && tiene === String(mp || "")) return -2;
		if (!tiene) return item.idx;
	}
	return -1;
}

function rmAsignarMpEnLineaIdx(idx, key, mp) {
	var linea = RM.estado && RM.estado.lineas ? RM.estado.lineas[idx] : null;
	if (!linea || !mp) return;
	rmAsegurarVariantesArticulos(linea);
	var parts = String(key).split("|");
	var cons = rmConsumoDesdeUi(mp, parts[0] || "", linea);
	if (cons) rmSetConsumoColor(linea, parts[0] || "", cons);
	if (idx === RM.lineaIdx) {
		if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	} else if (RM.mpActivaUnd && !linea.unidad) {
		linea.unidad = RM.mpActivaUnd;
	}
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") + "|" + String(v.cod_talla || "") === String(key)) {
			v.mp_codigo = String(mp);
			v.consumo = rmConsumoDeColor(linea, v.cod_color);
		}
	});
	rmLimpiarConsumoPendiente(mp, parts[0] || "");
	rmMarcarDirty(true);
}

function rmCeldaYaTieneMp(codColor, codTalla, mp, lineaBase) {
	var ya = rmMpsEnCelda(codColor, codTalla, lineaBase);
	var i;
	for (i = 0; i < ya.length; i++) {
		if (String(ya[i].mp) === String(mp)) return true;
	}
	return false;
}

function rmAgregarMpExtraAKey(key, mp, silencioso) {
	var linea = rmLineaActual();
	if (!linea || !mp || !key) return false;
	var parts = String(key).split("|");
	var codColor = parts[0] || "";
	var codTalla = parts[1] || "";
	if (rmCeldaYaTieneMp(codColor, codTalla, mp, linea)) {
		if (!silencioso) rmAlerta("info", "MP", "Esa tela ya está en esta celda");
		return false;
	}
	var idx = rmIdxCapaLibreParaCelda(codColor, codTalla, mp);
	if (idx === -2) {
		if (!silencioso) rmAlerta("info", "MP", "Esa tela ya está en esta celda");
		return false;
	}
	var cons = rmConsumoDesdeUi(mp, codColor, idx >= 0 ? RM.estado.lineas[idx] : null);
	if (idx < 0) idx = rmCrearCapaExtra(linea, cons);
	else if (cons) rmSetConsumoColor(RM.estado.lineas[idx], codColor, cons);
	rmAsignarMpEnLineaIdx(idx, key, mp);
	return idx;
}

function rmAgregarMpExtraAColor(codColor, mp) {
	var n = 0;
	var lastIdx = -1;
	(RM.articulos || []).forEach(function (art) {
		if (String(art.cod_color || "") !== String(codColor)) return;
		var idx = rmAgregarMpExtraAKey(rmClaveArt(art), mp, true);
		if (idx === false || idx < 0) return;
		n++;
		lastIdx = idx;
	});
	if (!n) rmAlerta("info", "MP", "Esa tela ya está en todas las tallas de este color");
	else if (lastIdx >= 0) RM.lineaIdx = lastIdx;
	return n;
}

function rmQuitarMpDeCapa(idx, key) {
	var linea = RM.estado && RM.estado.lineas ? RM.estado.lineas[idx] : null;
	if (!linea) return;
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") + "|" + String(v.cod_talla || "") === String(key)) {
			v.mp_codigo = "";
		}
	});
	rmMarcarDirty(true);
	rmLimpiarCapasExtraVacias();
}

function rmLimpiarCapasExtraVacias() {
	rmLimpiarLineasSinMp({ keepActual: true });
}

/** Quita sublíneas sin MP (consultadas y no puestas en ninguna tarjeta). */
function rmLimpiarLineasSinMp(opts) {
	opts = opts || {};
	var keepActual = opts.keepActual !== false;
	if (!RM.estado || !Array.isArray(RM.estado.lineas)) return 0;
	var actual = rmLineaActual();
	var kept = [];
	var removed = 0;
	RM.estado.lineas.forEach(function (l, i) {
		if (Number(l.activo) === 0) {
			kept.push(l);
			return;
		}
		if (keepActual && i === RM.lineaIdx) {
			kept.push(l);
			return;
		}
		if (!rmLineaTieneMp(l)) {
			removed++;
			return;
		}
		kept.push(l);
	});
	if (!removed) return 0;
	RM.estado.lineas = kept;
	if (actual && kept.indexOf(actual) >= 0) {
		RM.lineaIdx = kept.indexOf(actual);
	} else {
		RM.lineaIdx = kept.length ? 0 : null;
	}
	RM.lineaAnteriorIdx = null;
	if (!opts.silent) rmMarcarDirty(true);
	return removed;
}

function rmNombrePickSublinea(sub, nom) {
	nom = $.trim(nom || "");
	if (!nom || nom === "\u00a0"
		|| nom.indexOf("Solo agrega") === 0
		|| nom.indexOf("Luego marca") === 0
		|| nom.indexOf("No era esta") === 0
		|| nom.indexOf("Cambiar reemplaza") === 0) {
		nom = sub;
	}
	if (sub && nom && nom !== sub) {
		RM.sublineaCache[String(sub).toUpperCase()] = nom;
	}
	return nom;
}

function rmLimpiarMpDeLinea(linea) {
	if (!linea) return;
	linea.mp_base_codigo = "";
	linea.unidad = "";
	(linea.variantes || []).forEach(function (v) {
		v.mp_codigo = "";
	});
}

function rmResetMpEnMano() {
	RM.mpActiva = null;
	RM.mpActivaUnd = "";
	RM.mpActivaColor = "";
}

function rmEnfocarPanelLinea() {
	var $ctx = $("#rmLineaActivaContexto");
	if ($ctx.length && $ctx[0].scrollIntoView) {
		$ctx[0].scrollIntoView({ behavior: "smooth", block: "start" });
	}
	setTimeout(function () {
		var $c = $(".rmConsumoColor").first();
		if ($c.length) $c.focus().select();
	}, 120);
}

function rmActualizarAccionesSublinea() {
	var $btnCambiar = $("#rmBtnCambiarSublinea");
	var $hint = $("#rmHintCambiarSublinea");
	var editable = rmEsBorradorEditable();
	var reemplazable = rmLineaActualReemplazable();
	var hayPick = $.trim($("#rmNuevaSublinea").val() || "") !== "";
	$btnCambiar.toggle(!!(editable && reemplazable && hayPick));
	if ($hint.length) $hint.toggle(!!(editable && reemplazable));
	if (!$("#rmNuevaSublinea").val()) {
		if (reemplazable) {
			$("#rmNuevaSublineaCod").text("Buscar para cambiar o agregar otra…").addClass("empty");
			$("#rmNuevaSublineaNom").text("Cambiar reemplaza la vacía; Agregar suma otra (misma tela = capa extra)");
		} else {
			$("#rmNuevaSublineaCod").text("Buscar y agregar otra sublínea…").addClass("empty");
			$("#rmNuevaSublineaNom").text("Agregar la misma tela otra vez suma una capa (otro color de copa)");
		}
	}
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
	return "1";
}

function rmNormCons(v) {
	return rmFmtNum(v);
}

function rmMismoConsumo(a, b) {
	return rmNormCons(a) === rmNormCons(b);
}

function rmInferirConsumoColor(linea) {
	var map = {};
	var general = rmNormCons(rmConsumoLinea(linea));
	var porColor = {};
	(linea && linea.variantes || []).forEach(function (v) {
		var c = String(v.cod_color || "");
		if (!c || v.consumo == null || v.consumo === "") return;
		if (!porColor[c]) porColor[c] = [];
		porColor[c].push(rmNormCons(v.consumo));
	});
	Object.keys(porColor).forEach(function (c) {
		var vals = porColor[c];
		var first = vals[0];
		var i;
		for (i = 1; i < vals.length; i++) {
			if (vals[i] !== first) return;
		}
		if (first !== "" && first !== general) map[c] = first;
	});
	return map;
}

function rmAsegurarConsumoColor(linea) {
	if (!linea) return;
	if (!linea.consumo_color || typeof linea.consumo_color !== "object" || Array.isArray(linea.consumo_color)) {
		linea.consumo_color = rmInferirConsumoColor(linea);
	}
}

function rmColorTieneConsumoEspecial(linea, codColor) {
	rmAsegurarConsumoColor(linea);
	var v = linea && linea.consumo_color ? linea.consumo_color[String(codColor || "")] : null;
	return v != null && v !== "";
}

function rmConsumoDeColor(linea, codColor) {
	rmAsegurarConsumoColor(linea);
	if (rmColorTieneConsumoEspecial(linea, codColor)) {
		return linea.consumo_color[String(codColor || "")];
	}
	return rmConsumoLinea(linea);
}

function rmConteoConsumoEspecial(linea) {
	rmAsegurarConsumoColor(linea);
	return Object.keys(linea && linea.consumo_color ? linea.consumo_color : {}).length;
}

function rmConsumosAsignadosLinea(linea) {
	var vals = [];
	(linea && linea.variantes || []).forEach(function (v) {
		if (!v.mp_codigo) return;
		var c = rmNormCons(v.consumo != null && v.consumo !== "" ? v.consumo : rmConsumoDeColor(linea, v.cod_color));
		if (c) vals.push(c);
	});
	return vals;
}

/** Consumo que se ve en el chip: el que más se usa en esta capa, sin desglosar colores. */
function rmConsumoResumenLinea(linea) {
	var vals = rmConsumosAsignadosLinea(linea);
	if (!vals.length) return rmNormCons(rmConsumoLinea(linea));
	var counts = {};
	var best = vals[0];
	var bestN = 0;
	vals.forEach(function (c) {
		counts[c] = (counts[c] || 0) + 1;
		if (counts[c] > bestN) {
			bestN = counts[c];
			best = c;
		}
	});
	return best;
}

function rmHayConsumosMixtosLinea(linea) {
	var seen = {};
	rmConsumosAsignadosLinea(linea).forEach(function (c) {
		seen[c] = true;
	});
	return Object.keys(seen).length > 1;
}

function rmSetConsumoColor(linea, codColor, valor) {
	if (!linea) return;
	rmAsegurarConsumoColor(linea);
	var c = String(codColor || "");
	if (!c) return;
	var general = rmConsumoLinea(linea);
	var n = rmNormCons(valor);
	if (n === "" || rmMismoConsumo(n, general)) {
		delete linea.consumo_color[c];
		n = rmNormCons(general) || general;
	} else {
		linea.consumo_color[c] = n;
	}
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") === c) v.consumo = n;
	});
}

function rmAplicarConsumosLinea(linea) {
	if (!linea) return;
	rmAsegurarConsumoColor(linea);
	var general = rmNormCons(rmConsumoLinea(linea));
	Object.keys(linea.consumo_color || {}).forEach(function (c) {
		if (rmMismoConsumo(linea.consumo_color[c], general)) delete linea.consumo_color[c];
	});
	(linea.variantes || []).forEach(function (v) {
		v.consumo = rmConsumoDeColor(linea, v.cod_color);
	});
}

function rmClaveColorTallaNorm(art) {
	return rmNormCodigoCorto(art.cod_color) + "|" + rmNormCodigoCorto(art.cod_talla);
}

function rmClaveLineaTarjeta(linea) {
	var sub = rmNormSublinea(linea && linea.codigo_sublinea);
	if (sub) return "S:" + sub;
	var mp = linea && linea.mp_base_codigo ? String(linea.mp_base_codigo).replace(/\s+/g, "") : "";
	if (!mp) mp = rmMpDominanteLinea(linea);
	if (mp) return "M:" + mp;
	return "R:" + String(linea && linea.nombre_rol ? linea.nombre_rol : "") + "|O:" + String(linea && linea.orden != null ? linea.orden : "");
}

function rmArticulosUnicos() {
	var seen = {};
	var out = [];
	(RM.articulos || []).forEach(function (a) {
		var k = String(a.articulo || "").trim();
		if (!k) k = rmClaveArt(a);
		if (!k || seen[k]) return;
		seen[k] = true;
		out.push(a);
	});
	return out;
}

function rmFusionarVariantesLinea(destino, origen) {
	if (!destino || !origen) return;
	var mapaOrig = rmMapaVariantes(origen);
	rmAsegurarVariantesArticulos(destino);
	(destino.variantes || []).forEach(function (v) {
		if (v.mp_codigo) return;
		var o = rmBuscarVarianteEnMapa(mapaOrig, v);
		if (o && o.mp_codigo) {
			v.mp_codigo = o.mp_codigo;
			if (o.consumo != null && o.consumo !== "") v.consumo = o.consumo;
		}
	});
	if (!Number(destino.es_tela_principal) && Number(origen.es_tela_principal)) {
		destino.es_tela_principal = 1;
	}
	if (!destino.unidad && origen.unidad) destino.unidad = origen.unidad;
}

/** Normaliza sublíneas; no fusiona capas (varias líneas con la misma MP son válidas). */
function rmDeduplicarLineasEstado() {
	if (!RM.estado || !Array.isArray(RM.estado.lineas)) return 0;
	RM.estado.lineas.forEach(function (l) {
		if (l.codigo_sublinea) l.codigo_sublinea = String(l.codigo_sublinea).trim();
	});
	return 0;
}

function rmAsegurarVariantesArticulos(linea) {
	var mapa = rmMapaVariantes(linea);
	rmAsegurarConsumoColor(linea);
	var consumoDefault = rmConsumoLinea(linea);
	var nuevas = [];
	var seenArt = {};
	var seenCT = {};
	(RM.articulos || []).forEach(function (art) {
		var ak = String(art.articulo || "").trim();
		if (ak) {
			if (seenArt[ak]) return;
			seenArt[ak] = true;
		}
		var ct = String(art.cod_color || "") + "|" + String(art.cod_talla || "");
		if (seenCT[ct]) return;
		seenCT[ct] = true;
		var prev = rmBuscarVarianteEnMapa(mapa, art);
		var consumo = rmColorTieneConsumoEspecial(linea, art.cod_color)
			? rmConsumoDeColor(linea, art.cod_color)
			: (prev.consumo != null && prev.consumo !== "" ? prev.consumo : consumoDefault);
		nuevas.push({
			cod_color: art.cod_color || "",
			cod_talla: art.cod_talla || "",
			color: art.color || "",
			talla: art.talla || "",
			articulo: art.articulo,
			mp_codigo: prev.mp_codigo || "",
			consumo: consumo,
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

function rmLineaCoberturaParcial(linea) {
	var con = {};
	var sin = {};
	(linea && linea.variantes || []).forEach(function (v) {
		var c = String(v.cod_color || "");
		if (!c) return;
		if (v.mp_codigo) con[c] = true;
		else sin[c] = true;
	});
	return Object.keys(con).length > 0 && Object.keys(sin).length > 0;
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
				+ "<a class='btn btn-xs btn-success' href='ajax/recetas-modelo-excel.php?id_receta=" + id + "' title='Descargar tarjetas en Excel'><i class='fa fa-file-excel-o'></i></a>"
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
	$("#rmBtnGuardar, #rmBtnPublicar, #rmBtnAgregarSublinea, #rmBtnCambiarSublinea").prop("disabled", !editable);
	$("#rmMsgEstado").text(editable ? "" : "Solo lectura — crea «Nueva versión»");
}

function rmSiguienteOrdenLinea() {
	var max = 0;
	(RM.estado && RM.estado.lineas || []).forEach(function (l) {
		var o = parseInt(l.orden, 10);
		if (isFinite(o) && o > max) max = o;
	});
	return max + 1;
}

/** Sublíneas activas en orden de receta (incluye capas extra de la misma tela). */
function rmLineasActivasPorOrden() {
	var out = [];
	(RM.estado && RM.estado.lineas || []).forEach(function (linea, idx) {
		if (Number(linea.activo) === 0) return;
		out.push({ linea: linea, idx: idx });
	});
	out.sort(function (a, b) {
		var oa = parseInt(a.linea.orden, 10);
		var ob = parseInt(b.linea.orden, 10);
		if (!isFinite(oa)) oa = a.idx + 1;
		if (!isFinite(ob)) ob = b.idx + 1;
		if (oa !== ob) return oa - ob;
		return a.idx - b.idx;
	});
	return out;
}

function rmRenumerarOrdenLineas() {
	var n = 0;
	(RM.estado && RM.estado.lineas || []).forEach(function (l) {
		if (Number(l.activo) === 0) return;
		n++;
		l.orden = n;
	});
}

function rmMoverLinea(idx, dir) {
	dir = dir < 0 ? -1 : 1;
	if (!RM.estado || !Array.isArray(RM.estado.lineas)) return false;
	var vis = rmLineasActivasPorOrden();
	var pos = -1;
	vis.forEach(function (it, i) {
		if (it.idx === idx) pos = i;
	});
	var dest = pos + dir;
	if (pos < 0 || dest < 0 || dest >= vis.length) return false;
	var actual = rmLineaActual();
	var ordenadas = vis.map(function (it) { return it.linea; });
	var tmp = ordenadas[pos];
	ordenadas[pos] = ordenadas[dest];
	ordenadas[dest] = tmp;
	(RM.estado.lineas || []).forEach(function (l) {
		if (Number(l.activo) === 0) ordenadas.push(l);
	});
	RM.estado.lineas = ordenadas;
	rmRenumerarOrdenLineas();
	RM.lineaIdx = actual ? RM.estado.lineas.indexOf(actual) : RM.lineaIdx;
	rmMarcarDirty(true);
	rmRenderChips();
	rmRenderTarjetasPorArticulo();
	return true;
}

function rmRenderChips() {
	var $box = $("#rmChipsInsumos").empty();
	var lineasActivas = rmLineasActivasPorOrden();
	if (!lineasActivas.length) {
		$box.removeClass("has-chips");
		return;
	}
	$box.addClass("has-chips");
	lineasActivas.forEach(function (item, pos) {
		var linea = item.linea;
		var idx = item.idx;
		rmAsegurarVariantesArticulos(linea);
		var cont = rmContarOk(linea);
		var active = RM.lineaIdx === idx;
		var cons = rmConsumoResumenLinea(linea);
		var esTela = Number(linea.es_tela_principal) === 1;
		var editable = rmEsBorradorEditable();
		var html = "<div class='rm2-chip" + (active ? " active" : "") + "' data-idx='" + idx + "'>";
		if (editable && lineasActivas.length > 1) {
			html += "<span class='rm2-chip-ord'>"
				+ "<button type='button' class='rmMoverLinea' data-idx='" + idx + "' data-dir='-1'"
				+ (pos === 0 ? " disabled" : "") + " title='Subir'><i class='fa fa-caret-up'></i></button>"
				+ "<button type='button' class='rmMoverLinea' data-idx='" + idx + "' data-dir='1'"
				+ (pos === lineasActivas.length - 1 ? " disabled" : "") + " title='Bajar'><i class='fa fa-caret-down'></i></button>"
				+ "</span>";
		}
		html += "<strong>" + rmEsc(rmEtiquetaLineaConCapa(linea, idx)) + "</strong>";
		if (active) {
			html += "<span class='rm2-chip-editando'>EDITANDO</span>";
		}
		if (cons !== "") {
			html += "<span class='label label-default'>" + rmEsc(rmFmtNum(cons)) + (linea.unidad ? " " + rmEsc(linea.unidad) : "") + "</span>";
		}
		if (rmHayConsumosMixtosLinea(linea)) {
			html += "<span class='label label-warning' title='Hay colores con consumo distinto en esta capa'>mixto</span>";
		}
		html += "<span class='label " + (esTela
			? (cont.ok ? "label-primary" : "label-warning")
			: (cont.ok === cont.total && cont.total ? "label-success" : "label-warning")) + "'>"
			+ cont.ok + "/" + cont.total + "</span>";
		if (editable) {
			html += "<button type='button' class='rm2-btn-tela rmToggleTela" + (esTela ? " on" : "") + "' data-idx='" + idx + "'"
				+ " title='" + (esTela ? "Es tela principal (clic para quitar). Puede haber varias; cada color usa una sola." : "Marcar como tela principal (puede haber más de una)") + "'>"
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
	rmActualizarAccionesSublinea();
}

function rmSetPickSublinea(cod, nom) {
	$("#rmNuevaSublinea").val(cod || "");
	if (cod) {
		$("#rmNuevaSublineaCod").text(cod).removeClass("empty");
		$("#rmNuevaSublineaNom").text(nom || "");
		rmActualizarAccionesSublinea();
	} else {
		rmLimpiarPickSublinea();
	}
}

function rmReemplazarLineaActual(sub, nom) {
	sub = $.trim(sub || "");
	if (!sub) return false;
	nom = rmNombrePickSublinea(sub, nom);
	var linea = rmLineaActual();
	if (!linea) return false;
	if (rmLineaTieneMp(linea)) {
		rmAlerta(
			"warning",
			"Sublínea",
			"Esta sublínea ya tiene materia prima. Quítala con × si quieres reemplazarla, o usa Agregar para sumar otra."
		);
		return false;
	}

	var idxExistente = rmIdxLineaPorSublinea(sub);
	if (idxExistente === RM.lineaIdx) {
		rmLimpiarPickSublinea();
		return true;
	}
	if (idxExistente >= 0) {
		var quitarIdx = RM.lineaIdx;
		RM.estado.lineas.splice(quitarIdx, 1);
		if (idxExistente > quitarIdx) idxExistente--;
		RM.lineaIdx = idxExistente;
		RM.lineaAnteriorIdx = null;
		rmResetMpEnMano();
		rmLimpiarPickSublinea();
		rmMarcarDirty(true);
		rmRefrescarPaso2(true);
		rmActualizarContextoLineaActiva(
			"Esa sublínea ya estaba en la receta; se seleccionó y se quitó la que no tenía MP"
		);
		rmEnfocarPanelLinea();
		return true;
	}

	linea.codigo_sublinea = sub;
	linea.nombre_rol = nom;
	linea.nombre_sublinea = nom;
	rmLimpiarMpDeLinea(linea);
	rmAsegurarVariantesArticulos(linea);
	rmResetMpEnMano();
	rmLimpiarPickSublinea();
	rmMarcarDirty(true);
	rmRefrescarPaso2(true);
	rmActualizarContextoLineaActiva("Se cambió la sublínea a: " + rmEtiquetaLinea(linea));
	rmEnfocarPanelLinea();
	return true;
}

function rmAgregarLineaSublinea(sub, nom) {
	sub = $.trim(sub || "");
	if (!sub) {
		rmAlerta("warning", "Sublínea", "Elige una sublínea");
		return false;
	}
	nom = rmNombrePickSublinea(sub, nom);

	var idxExistente = rmIdxLineaPorSublinea(sub);
	var esCapaExtra = idxExistente >= 0;

	rmSincronizarConsumoEnVariantes();
	var lineaAnteriorIdx = RM.lineaIdx;
	var linea = {
		orden: rmSiguienteOrdenLinea(),
		nombre_rol: nom,
		es_tela_principal: 0,
		codigo_sublinea: sub,
		nombre_sublinea: nom,
		regla_variante: "COLOR_TALLA",
		unidad: "",
		consumo_base: "1",
		consumo_color: {},
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
	rmLimpiarLineasSinMp({ keepActual: true });
	rmResetMpEnMano();
	rmLimpiarPickSublinea();
	rmMarcarDirty(true);
	rmRefrescarPaso2(true);
	rmActualizarContextoLineaActiva(
		esCapaExtra
			? "Capa extra de la misma tela. Asigna el otro color. Vacío = ese color no usa esta capa. No la marques tela principal."
			: ("Ahora estás configurando: " + rmEtiquetaLinea(linea)),
		{ mostrarVolver: RM.lineaAnteriorIdx !== null }
	);
	rmEnfocarPanelLinea();
	return true;
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
		$("#rmUnidadLineaAddon").text("");
		$("#rmMatrizContexto").text("Asignar · color artículo × talla");
		$("#rmTituloArticulos").text("2. Asignar materia prima");
		return;
	}

	var etiqueta = rmEtiquetaLineaConCapa(linea, RM.lineaIdx);
	var nArts = (RM.articulos || []).length;
	var und = linea.unidad ? String(linea.unidad) : "—";
	var capa = rmCapaDeLinea(linea, RM.lineaIdx);
	var opcional = rmLineaEsCapaOpcional(linea);

	var nEsp = rmConteoConsumoEspecial(linea);
	var txtEsp = nEsp
		? (" · " + nEsp + " color" + (nEsp === 1 ? "" : "es") + " con consumo distinto")
		: "";
	$ctx.show();
	// El nombre completo solo aquí (evitar repetirlo en título / consumo / matriz)
	$("#rmCtxNombre").text(etiqueta);
	$("#rmCtxMeta").text(
		capa.total > 1
			? ("Capa " + capa.nro + " de " + capa.total + " · mismo insumo, otro color si hace falta · consumo por color" + (und !== "—" ? " · " + und : "") + txtEsp)
			: ("Consumo por color en la matriz · " + nArts
				+ " combinación" + (nArts === 1 ? "" : "es") + " color × talla"
				+ (und !== "—" ? " · " + und : "") + txtEsp)
	);
	$("#rmUnidadLineaAddon").text(und !== "—" ? und + " · " : "");
	$("#rmMatrizContexto").text(
		(opcional || rmLineaCoberturaParcial(linea))
			? "Asignar MP · color × talla · vacío = este color no usa esta tela · consumo en la fila del color"
			: "Asignar MP · color × talla · consumo distinto en la fila del color"
	);
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
	rmLineasMismaSublinea(linea).forEach(function (item) {
		(item.linea.variantes || []).forEach(function (v) {
			if (!v.mp_codigo) return;
			if (!mapa[v.mp_codigo]) mapa[v.mp_codigo] = 0;
			mapa[v.mp_codigo]++;
		});
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
			+ " title='Clic: ver y editar el consumo de este color · " + rmEsc(cod) + "'>"
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
	$("#rmUnidadLineaAddon").text(next ? next + " · " : "");
	$("#rmMpActivaUndTxt").text(next ? (" · " + next) : "");
}

function rmSetMpActiva(mp, und, color, desc, lineaIdxForzado) {
	RM.mpActiva = mp || null;
	RM.mpActivaUnd = und || "";
	RM.mpActivaColor = color || rmColorMp(mp) || "";
	if (mp) {
		$("#rmMpActivaBox").addClass("visible");
		$("#rmMpActivaTxt").html(
			"<strong>" + rmEsc(RM.mpActivaColor || mp) + "</strong>"
			+ (desc ? " <span class='text-muted'>· " + rmEsc(desc) + "</span>" : "")
			+ " <span class='text-muted'>(" + rmEsc(mp) + ")</span>"
		);
		$("#rmMpActivaUndTxt").text(und ? (" · " + und) : "");
	} else {
		$("#rmMpActivaBox").removeClass("visible");
		$("#rmMpActivaTxt").text("—");
		$("#rmMpActivaUndTxt").text("");
	}
	$("#rmTablaMp tr").removeClass("activa");
	if (mp) {
		$("#rmTablaMp tr[data-mp='" + String(mp).replace(/'/g, "\\'") + "']").addClass("activa");
	}
	$("#rmMpsAsignadas .rm2-asig-chip").removeClass("activa");
	if (mp) {
		$("#rmMpsAsignadas .rm2-asig-chip[data-mp='" + String(mp).replace(/'/g, "\\'") + "']").addClass("activa");
	}
	$("#rmMatriz .rm2-celda-mp").removeClass("activa");
	if (mp) {
		$("#rmMatriz .rm2-celda-mp[data-mp='" + String(mp).replace(/'/g, "\\'") + "']").addClass("activa");
	}
	if (RM._enRender) return;
	if (mp) {
		var idx = (lineaIdxForzado != null && lineaIdxForzado >= 0)
			? lineaIdxForzado
			: rmIdxCapaDeMp(mp);
		if (idx >= 0 && idx !== RM.lineaIdx) {
			rmSincronizarConsumoEnVariantes();
			RM.lineaIdx = idx;
			if (und) rmHeredarUnidadDeMp(und);
			rmRenderChips();
			rmActualizarAccionesSublinea();
			rmActualizarContextoLineaActiva("Consumo de «" + (RM.mpActivaColor || rmEtiquetaMp(mp) || mp) + "»");
			rmRenderMatriz();
			return;
		}
		if (und) rmHeredarUnidadDeMp(und);
	}
	if (mp && rmIdxCapaDeMp(mp) < 0) {
		rmRefrescarInputsConsumoSegunMp();
		rmActualizarContextoLineaActiva("Consumo para «" + (RM.mpActivaColor || rmEtiquetaMp(mp) || mp) + "» al asignar");
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

function rmLineasParaTarjetas() {
	var lineas = [];
	rmLineasActivasPorOrden().forEach(function (item) {
		lineas.push(item.linea);
	});
	return lineas;
}

function rmArticulosParaTarjetas() {
	return rmArticulosUnicos().slice().sort(function (a, b) {
		var c = String(a.cod_color || "").localeCompare(String(b.cod_color || ""));
		if (c !== 0) return c;
		var ta = String(a.cod_talla || "");
		var tb = String(b.cod_talla || "");
		var na = parseInt(ta, 10);
		var nb = parseInt(tb, 10);
		if (isFinite(na) && isFinite(nb) && na !== nb) return na - nb;
		return ta.localeCompare(tb);
	});
}

function rmFilasTarjetaDeArticulo(art, lineas) {
	var seenMp = {};
	var filas = [];
	(lineas || []).forEach(function (linea) {
		var res = rmResolverMpLinea(linea, art);
		var mp = String(res.mp_codigo || "").replace(/\s+/g, "");
		if (!mp) return;
		if (seenMp[mp]) return;
		seenMp[mp] = true;
		filas.push({ linea: linea, res: res });
	});
	return filas;
}

function rmFilasPlanasTarjetas() {
	var lineas = rmLineasParaTarjetas();
	var arts = rmArticulosParaTarjetas();
	lineas.forEach(rmAsegurarVariantesArticulos);
	var rows = [];
	arts.forEach(function (art) {
		rmFilasTarjetaDeArticulo(art, lineas).forEach(function (item) {
			var res = item.res;
			var ok = !!res.mp_codigo;
			rows.push({
				articulo: art.articulo || "",
				color: art.color || art.cod_color || "",
				talla: art.talla || art.cod_talla || "",
				mp_nombre: ok ? (rmNombreMp(res.mp_codigo) || res.mp_codigo) : "",
				es_tela: Number(item.linea.es_tela_principal) === 1 ? "SI" : "",
				sublinea: item.linea.codigo_sublinea || "",
				mp_codigo: ok ? res.mp_codigo : "",
				color_mp: ok ? rmEtiquetaMp(res.mp_codigo) : "",
				consumo: ok ? res.consumo : "",
				unidad: ok ? (res.unidad || "") : "",
				estado: ok ? "OK" : "Falta"
			});
		});
	});
	return rows;
}

function rmDescargarBlob(contenido, nombre, mime) {
	var blob = contenido instanceof Blob
		? contenido
		: new Blob([contenido], { type: mime || "application/octet-stream" });
	var url = URL.createObjectURL(blob);
	var a = document.createElement("a");
	a.href = url;
	a.download = nombre;
	document.body.appendChild(a);
	a.click();
	setTimeout(function () {
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	}, 400);
}

function rmExportarExcelTarjetas() {
	var rows = rmFilasPlanasTarjetas();
	if (!rows.length) {
		rmAlerta("warning", "Sin tarjetas", "No hay artículos o sublíneas para exportar");
		return;
	}
	var cab = (RM.estado && RM.estado.cabecera) ? RM.estado.cabecera : {};
	var modelo = String(cab.modelo || "modelo");
	var version = cab.version != null ? String(cab.version) : "";
	var estado = String(cab.estado || "");
	var nombreModelo = String(cab.nombre_modelo || $("#rmNombreModelo").text() || "");
	var $btn = $("#rmBtnExcelTarjetas").prop("disabled", true);
	$.ajax({
		url: "ajax/recetas-modelo-excel.php",
		method: "POST",
		data: {
			modelo: modelo,
			version: version,
			estado: estado,
			nombre: nombreModelo,
			filas: JSON.stringify(rows)
		},
		xhrFields: { responseType: "blob" }
	}).done(function (data, _st, xhr) {
		var ctype = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
		if (ctype.indexOf("json") !== -1) {
			var reader = new FileReader();
			reader.onload = function () {
				var resp = {};
				try { resp = JSON.parse(reader.result); } catch (e) {}
				rmAlerta("error", "Excel", (resp && resp.mensaje) || "No se pudo generar el archivo");
			};
			reader.readAsText(data);
			return;
		}
		var archivo = "tarjetas-" + modelo.replace(/[^\w.-]+/g, "_")
			+ (version ? "-v" + version : "") + ".xls";
		rmDescargarBlob(data, archivo, "application/vnd.ms-excel");
	}).fail(function () {
		rmAlerta("error", "Excel", "No se pudo generar el archivo");
	}).always(function () {
		$btn.prop("disabled", false);
	});
}

function rmRenderTarjetasPorArticulo() {
	var $tb = $("#rmTablaTarjetasArticulo tbody");
	if (!$tb.length) return;

	var lineas = rmLineasParaTarjetas();
	var arts = rmArticulosParaTarjetas();

	if (!arts.length) {
		$tb.html("<tr><td colspan='10' class='text-muted'>Sin artículos activos</td></tr>");
		return;
	}
	if (!lineas.length) {
		$tb.html("<tr><td colspan='10' class='text-muted'>Agrega sublíneas para ver las tarjetas</td></tr>");
		return;
	}

	lineas.forEach(rmAsegurarVariantesArticulos);

	var html = [];
	arts.forEach(function (art, artIdx) {
		var prev = artIdx > 0 ? arts[artIdx - 1] : null;
		var sepClass = "";
		if (prev) {
			if (String(prev.cod_color || "") !== String(art.cod_color || "")) {
				sepClass = " rm2-sep-color";
			} else if (String(prev.cod_talla || "") !== String(art.cod_talla || "")) {
				sepClass = " rm2-sep-talla";
			}
		}
		var filas = rmFilasTarjetaDeArticulo(art, lineas);
		var n = filas.length;
		filas.forEach(function (item, i) {
			var linea = item.linea;
			var res = item.res;
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
			row += "<td>" + rmEsc(linea.codigo_sublinea || "—") + "</td>"
				+ "<td>" + rmEsc(ok ? res.mp_codigo : "—") + "</td>"
				+ "<td title='" + rmEsc(ok ? res.mp_codigo : "") + "'>" + rmEsc(nombreMp)
				+ (esTela ? " <span class='label label-danger'>Tela</span>" : "")
				+ "</td>"
				+ "<td>" + rmEsc(ok ? rmEtiquetaMp(res.mp_codigo) : "—") + "</td>"
				+ "<td>" + rmEsc(ok ? rmFmtNum(res.consumo) : "—") + "</td>"
				+ "<td>" + rmEsc(ok ? (res.unidad || "") : "") + "</td>"
				+ "<td>" + (ok
					? "<span class='label label-success'>OK</span>"
					: "<span class='label label-warning'>Falta</span>")
				+ "</td></tr>";
			html.push(row);
		});
	});
	$tb.html(html.join(""));
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
	RM._enRender = true;

	rmActualizarContextoLineaActiva();

	rmAsegurarVariantesArticulos(linea);
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
		head1 += "<th class='rm2-talla-th'>" + rmEsc(t.talla || t.cod_talla) + "</th>";
	});
	head1 += "</tr>";

	var head2 = "<tr>";
	tallas.forEach(function (t) {
		head2 += "<th class='rm2-talla-th' style='background:#e8f4fc; padding:4px;'>"
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
		var especial = rmColorTieneConsumoEspecial(linea, col.cod_color);
		var consColor = rmConsumoDeColor(linea, col.cod_color);
		var row = "<tr><th class='rm2-color-th" + (especial ? " especial" : "") + "'>"
			+ "<strong>" + rmEsc(col.color || col.cod_color) + "</strong>"
			+ "<div class='rm2-cons-color" + (especial ? " especial" : "") + "'>"
			+ (editable
				? "<input type='number' step='any' min='0' class='form-control rmConsumoColor' data-color='"
					+ rmEsc(col.cod_color) + "' value='" + rmEsc(rmFmtNum(consColor)) + "'"
					+ " title='" + (especial
						? "Consumo distinto de este color. No se pisa al cambiar el general."
						: "Consumo de este color. Si lo cambiás, queda como especial.") + "'>"
					+ "<span class='rm2-esp-tag'" + (especial ? "" : " style='display:none;'") + ">especial</span>"
					+ "<button type='button' class='rmQuitarConsumoColor' data-color='" + rmEsc(col.cod_color) + "'"
					+ (especial ? "" : " style='display:none;'")
					+ " title='Volver al consumo general'>&times;</button>"
				: "<span class='rm2-cons-txt'>" + rmEsc(rmFmtNum(consColor))
					+ (especial ? " <span class='rm2-esp-tag'>especial</span>" : "")
					+ "</span>")
			+ "</div>"
			+ (editable
				? "<div class='rm2-atajos-fila'>"
					+ "<button type='button' class='btn btn-xs btn-primary rmAplicarColor' data-color='" + rmEsc(col.cod_color) + "' title='MP en mano → todas las tallas de este color'><i class='fa fa-arrows-h'></i> Tallas</button>"
					+ "<button type='button' class='btn btn-xs btn-success rmAgregarMpColor' data-color='" + rmEsc(col.cod_color) + "' title='Suma la MP en mano a este color, sin pisar'><i class='fa fa-plus'></i> Otra</button>"
					+ "<button type='button' class='btn btn-xs btn-default rmQuitarColor' data-color='" + rmEsc(col.cod_color) + "' title='Quitar MP en todas las tallas de este color'><i class='fa fa-times'></i></button>"
					+ "</div>"
				: "")
			+ "</th>";
		tallas.forEach(function (tal) {
			var art = rmArtPorColorTalla(col.cod_color, tal.cod_talla);
			if (!art) {
				row += "<td class='text-muted' style='background:#fafafa;'>—</td>";
				return;
			}
			var key = rmClaveArt(art);
			var mps = rmMpsEnCelda(col.cod_color, tal.cod_talla, linea);
			var ok = mps.length > 0;
			var vacioOk = !ok && (rmLineaEsCapaOpcional(linea) || rmLineaCoberturaParcial(linea));
			var etiqueta = ok ? rmEtiquetaMp(mps[0].mp) : (vacioOk ? "No usa" : "—");
			var celdaClase = ok ? "ok" : (vacioOk ? "na" : "falta");
			var inner = "";
			if (ok) {
				inner += "<div class='rm2-celda-mps'>";
				mps.forEach(function (item) {
					var esActiva = String(item.mp) === String(RM.mpActiva || "");
					inner += "<span class='rm2-celda-mp" + (esActiva ? " activa" : "") + "' data-mp='" + rmEsc(item.mp) + "' data-idx='" + item.idx + "'"
						+ " title='" + rmEsc(rmEtiquetaMp(item.mp) + " · consumo " + rmFmtNum(item.consumo) + " · clic para editar esta capa") + "'>"
						+ rmEsc(rmEtiquetaMp(item.mp))
						+ "<span class='rm2-celda-cons'>" + rmEsc(rmFmtNum(item.consumo)) + "</span>";
					if (editable) {
						inner += "<a href='javascript:;' class='rmQuitarMpCapa' data-idx='" + item.idx
							+ "' data-key='" + rmEsc(key) + "' title='Quitar esta MP'>&times;</a>";
					}
					inner += "</span>";
				});
				inner += "</div>";
			} else {
				inner += "<div class='rm2-celda-color'>" + rmEsc(etiqueta) + "</div>";
			}
			if (editable) {
				inner += "<button type='button' class='rm2-celda-add rmAgregarMpCelda' data-key='" + rmEsc(key)
					+ "' title='Sumar otra MP sin reemplazar la que ya está'>+</button>";
			}
			inner += "<div class='rm2-celda-art'>" + rmEsc(art.articulo) + "</div>";
			row += "<td class='rm2-celda " + celdaClase + "' data-articulo='" + rmEsc(art.articulo)
				+ "' data-color='" + rmEsc(col.cod_color) + "' data-talla='" + rmEsc(tal.cod_talla) + "' data-key='" + rmEsc(key) + "'"
				+ " title='" + rmEsc(art.articulo + (ok ? " · " + mps.map(function (x) { return rmEtiquetaMp(x.mp); }).join(" + ") : (vacioOk ? " · no usa esta tela" : "")) + " — clic: reemplazar esta capa · +: sumar otra") + "'"
				+ (editable ? "" : " style='cursor:default;'") + ">"
				+ inner
				+ "</td>";
		});
		row += "</tr>";
		$tbody.append(row);
	});

	rmRenderMpsAsignadas();
	rmRenderTarjetasPorArticulo();
	rmSetMpActiva(RM.mpActiva, RM.mpActivaUnd, RM.mpActivaColor, RM.mpActiva ? (RM.mpCache[RM.mpActiva] || {}).descripcion : "");
	RM._enRender = false;
}

function rmRefrescarPaso2(reloadCatalogo) {
	rmRenderChips();
	rmRenderMatriz();
	if (reloadCatalogo) rmCargarTablaMp();
	rmActualizarAccionesSublinea();
}

function rmAplicarMpAKey(key, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var parts = String(key).split("|");
	var cons = rmConsumoDesdeUi(mp, parts[0] || "", linea);
	if (cons) rmSetConsumoColor(linea, parts[0] || "", cons);
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") + "|" + String(v.cod_talla || "") === String(key)) {
			v.mp_codigo = String(mp);
			v.consumo = rmConsumoDeColor(linea, v.cod_color);
		}
	});
	rmLimpiarConsumoPendiente(mp, parts[0] || "");
	rmMarcarDirty(true);
}

function rmAplicarMpAColor(codColor, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	var cons = rmConsumoDesdeUi(mp, codColor, linea);
	if (cons) rmSetConsumoColor(linea, codColor, cons);
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_color || "") === String(codColor)) {
			v.mp_codigo = String(mp);
			v.consumo = rmConsumoDeColor(linea, v.cod_color);
		}
	});
	rmLimpiarConsumoPendiente(mp, codColor);
	rmMarcarDirty(true);
}

function rmAplicarMpATalla(codTalla, mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		if (String(v.cod_talla || "") !== String(codTalla)) return;
		var cons = rmConsumoDesdeUi(mp, v.cod_color, linea);
		if (cons) rmSetConsumoColor(linea, v.cod_color, cons);
		v.mp_codigo = String(mp);
		v.consumo = rmConsumoDeColor(linea, v.cod_color);
	});
	rmMarcarDirty(true);
}

function rmAplicarMpTodos(mp) {
	var linea = rmLineaActual();
	if (!linea || !mp) return;
	if (RM.mpActivaUnd) rmHeredarUnidadDeMp(RM.mpActivaUnd);
	(linea.variantes || []).forEach(function (v) {
		var cons = rmConsumoDesdeUi(mp, v.cod_color, linea);
		if (cons) rmSetConsumoColor(linea, v.cod_color, cons);
		v.mp_codigo = String(mp);
		v.consumo = rmConsumoDeColor(linea, v.cod_color);
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
	rmAplicarConsumosLinea(linea);
	rmRenderChips();
	rmRenderTarjetasPorArticulo();
	rmRenderMatriz();
}

function rmCargarTablaMp() {
	var linea = rmLineaActual();
	var $tb = $("#rmTablaMp tbody");
	if (!linea || !linea.codigo_sublinea) {
		RM.mpCatalogo = [];
		RM.mpCatalogoSub = "";
		$tb.html("<tr><td colspan='4' class='text-muted'>Sin sublínea</td></tr>");
		return;
	}
	var sub = String(linea.codigo_sublinea);
	$tb.html("<tr><td colspan='4' class='text-muted'>Cargando MP…</td></tr>");
	var seq = ++RM._mpReqSeq;
	rmPost({
		accion: "buscarMp",
		q: "",
		codigo_sublinea: sub,
		limit: 400
	}).done(function (resp) {
		if (seq !== RM._mpReqSeq) return;
		var data = (resp && resp.ok && resp.data) ? resp.data : [];
		RM.mpCatalogo = data;
		RM.mpCatalogoSub = sub;
		data.forEach(rmCacheMp);
		rmAplicarFiltroMp();
	}).fail(function () {
		if (seq !== RM._mpReqSeq) return;
		$tb.html("<tr><td colspan='4' class='text-muted'>No se pudo cargar el catálogo</td></tr>");
	});
}

function rmHaystackMp(mp) {
	return [
		mp.mp_codigo || "",
		mp.codfab || "",
		mp.descripcion || "",
		mp.color || "",
		mp.unidad || "",
		mp.colpro || "",
		mp.codigo_sublinea || ""
	].join(" ").toLowerCase();
}

function rmFiltrarMpLocal(q) {
	q = $.trim(String(q || "")).toLowerCase();
	if (!q) return RM.mpCatalogo.slice();
	return (RM.mpCatalogo || []).filter(function (mp) {
		return rmHaystackMp(mp).indexOf(q) >= 0;
	});
}

function rmPintarTablaMp(lista, q) {
	var $tb = $("#rmTablaMp tbody").empty();
	if (!(lista || []).length) {
		if (q) {
			$tb.html("<tr><td colspan='4' class='text-muted'>Ninguna MP coincide con «" + rmEsc(q) + "»</td></tr>");
		} else {
			$tb.html("<tr><td colspan='4' class='text-muted'>No hay MP para esta sublínea</td></tr>");
		}
		return;
	}
	lista.forEach(function (mp) {
		rmCacheMp(mp);
		var activa = String(RM.mpActiva || "") === String(mp.mp_codigo) ? " activa" : "";
		$tb.append("<tr class='" + activa + "' data-mp='" + rmEsc(mp.mp_codigo) + "'>"
			+ "<td><div class='rm2-mp-color'>" + rmEsc(mp.color || "—") + "</div></td>"
			+ "<td><span class='rm2-mp-und'>" + rmEsc(mp.unidad || "—") + "</span></td>"
			+ "<td><div class='rm2-mp-desc' title='" + rmEsc((mp.mp_codigo || "") + " — " + (mp.descripcion || "") + (mp.codfab ? " · " + mp.codfab : "")) + "'>"
			+ "<strong>" + rmEsc(mp.mp_codigo || "") + "</strong>"
			+ (mp.descripcion ? " — " + rmEsc(mp.descripcion) : "")
			+ (mp.codfab ? " · " + rmEsc(mp.codfab) : "")
			+ "</div></td>"
			+ "<td><button type='button' class='btn btn-xs btn-primary rmElegirMpActiva'"
			+ " data-mp='" + rmEsc(mp.mp_codigo) + "'"
			+ " data-und='" + rmEsc(mp.unidad || "") + "'"
			+ " data-color='" + rmEsc(mp.color || "") + "'"
			+ " data-desc='" + rmEsc(mp.descripcion) + "'>Elegir</button></td>"
			+ "</tr>");
	});
}

function rmAplicarFiltroMp() {
	var linea = rmLineaActual();
	var $tb = $("#rmTablaMp tbody");
	if (!linea || !linea.codigo_sublinea) {
		$tb.html("<tr><td colspan='4' class='text-muted'>Sin sublínea</td></tr>");
		return;
	}
	var q = $.trim($("#rmFiltroMp").val() || "");
	var sub = String(linea.codigo_sublinea);
	if (RM.mpCatalogoSub !== sub) {
		rmCargarTablaMp();
		return;
	}
	var local = rmFiltrarMpLocal(q);
	if (local.length || q.length < 2) {
		rmPintarTablaMp(local, q);
		return;
	}
	var seq = ++RM._mpReqSeq;
	$tb.html("<tr><td colspan='4' class='text-muted'>Buscando MP…</td></tr>");
	rmPost({
		accion: "buscarMp",
		q: q,
		codigo_sublinea: sub,
		limit: 400
	}).done(function (resp) {
		if (seq !== RM._mpReqSeq) return;
		var data = (resp && resp.ok && resp.data) ? resp.data : [];
		data.forEach(rmCacheMp);
		rmPintarTablaMp(data, q);
	}).fail(function () {
		if (seq !== RM._mpReqSeq) return;
		rmPintarTablaMp([], q);
	});
}

function rmPayloadLineas() {
	rmNormalizarNombresTela();
	rmDeduplicarLineasEstado();
	rmLimpiarLineasSinMp({ keepActual: false });
	return (RM.estado.lineas || []).filter(function (linea) {
		return rmLineaTieneMp(linea);
	}).map(function (linea, idx) {
		var consumo = linea.consumo_base || "1";
		linea.consumo_base = consumo;
		rmAsegurarVariantesArticulos(linea);
		rmAplicarConsumosLinea(linea);
		var variantes = [];
		(linea.variantes || []).forEach(function (v) {
			if (!v.mp_codigo) return;
			variantes.push({
				cod_color: v.cod_color || "",
				cod_talla: v.cod_talla || "",
				mp_codigo: v.mp_codigo,
				consumo: (v.consumo != null && v.consumo !== "") ? v.consumo : consumo,
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
			RM.articulos = rmArticulosUnicos();
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
			var dupsLineas = rmDeduplicarLineasEstado();
			RM.estado.lineas.forEach(rmAsegurarVariantesArticulos);
			var vacias = rmLimpiarLineasSinMp({ keepActual: false, silent: true });
			if (rmNormalizarNombresTela() || needsRemap || dupsLineas || vacias) rmMarcarDirty(true);
			else rmMarcarDirty(false);
			rmEnriquecerNombresSublinea(function () {
				rmRenderCabecera();
				rmRenderChips();
				rmActualizarContextoLineaActiva();
				rmActualizarAccionesSublinea();
				rmEnriquecerMpsAsignadas(function () {
					rmRenderMatriz();
					rmCargarTablaMp();
				});
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
			rmActualizarAccionesSublinea();
			rmEnriquecerMpsAsignadas(function () {
				rmRenderMatriz();
				rmCargarTablaMp();
			});
		});
		if (!silent) rmAlerta("success", "Guardado", "Cambios guardados");
	}).always(function () {
		$("#rmBtnGuardar").prop("disabled", !rmEsBorradorEditable()).html("<i class='fa fa-save'></i> Guardar");
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
			var btns = "<button type='button' class='btn btn-xs btn-primary rmPickSub' data-cod='"
				+ rmEsc(s.codigo_sublinea) + "' data-nom='" + rmEsc(s.nombre) + "'>Elegir</button>";
			if (rmEsBorradorEditable() && rmLineaActualReemplazable()) {
				btns = "<button type='button' class='btn btn-xs btn-warning rmReemplazarSub' data-cod='"
					+ rmEsc(s.codigo_sublinea) + "' data-nom='" + rmEsc(s.nombre)
					+ "' title='Reemplazar la sublínea actual en chips y tarjetas'>Cambiar</button> "
					+ btns;
			}
			$tb.append("<tr>"
				+ "<td><strong>" + rmEsc(s.codigo_sublinea) + "</strong></td>"
				+ "<td>" + rmEsc(s.linea) + "</td>"
				+ "<td>" + rmEsc(s.nombre) + "</td>"
				+ "<td style='white-space:nowrap;'>" + btns + "</td>"
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
		rmActualizarAccionesSublinea();
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
	$(document).on("click", ".rmReemplazarSub", function () {
		if (!rmEsBorradorEditable()) return;
		var cod = String($(this).attr("data-cod") || $(this).data("cod") || "");
		var nom = String($(this).attr("data-nom") || $(this).data("nom") || "");
		$("#modalSublineasReceta").modal("hide");
		rmReemplazarLineaActual(cod, nom);
	});

	$("#rmBtnCambiarSublinea").on("click", function () {
		if (!rmEsBorradorEditable()) return;
		var sub = $.trim($("#rmNuevaSublinea").val() || "");
		var nom = $.trim($("#rmNuevaSublineaNom").text() || "");
		if (!sub) {
			rmAlerta("warning", "Sublínea", "Elige una sublínea");
			return;
		}
		rmReemplazarLineaActual(sub, nom);
	});

	$("#rmBtnAgregarSublinea").on("click", function () {
		if (!rmEsBorradorEditable()) return;
		var sub = $.trim($("#rmNuevaSublinea").val() || "");
		var nom = $.trim($("#rmNuevaSublineaNom").text() || "");
		rmAgregarLineaSublinea(sub, nom);
	});

	$(document).on("click", ".rm2-chip", function (e) {
		if ($(e.target).closest(".rmQuitarChip, .rmToggleTela, .rmMoverLinea, .rm2-chip-ord").length) return;
		rmSincronizarConsumoEnVariantes();
		var nuevoIdx = Number($(this).attr("data-idx"));
		if (nuevoIdx === RM.lineaIdx) return;
		RM.lineaAnteriorIdx = null;
		RM.lineaIdx = nuevoIdx;
		rmLimpiarLineasSinMp({ keepActual: true });
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
		rmActualizarAccionesSublinea();
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
		linea.es_tela_principal = activar ? 1 : 0;
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

	$(document).on("click", ".rmMoverLinea", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable()) return;
		if ($(this).prop("disabled")) return;
		rmMoverLinea(Number($(this).attr("data-idx")), Number($(this).attr("data-dir")));
	});

	$(document).on("click", ".rmConsumoColor", function (e) {
		e.stopPropagation();
	});
	$(document).on("keydown", ".rmConsumoColor", function (e) {
		if (e.which === 13) {
			e.preventDefault();
			$(this).blur();
		}
	});
	$(document).on("change", ".rmConsumoColor", function () {
		if (!rmEsBorradorEditable()) return;
		var $inp = $(this);
		var codColor = String($inp.attr("data-color") || "");
		var valor = $inp.val();
		var mp = RM.mpActiva;
		var idx = mp ? rmIdxCapaDeMpColor(mp, codColor) : RM.lineaIdx;
		if (mp && idx < 0) idx = RM.lineaIdx;
		if (mp && idx < 0) {
			rmGuardarConsumoPendiente(mp, codColor, valor);
			rmMarcarDirty(true);
			rmPintarInputConsumoColor($inp, valor, !rmMismoConsumo(valor, "1"));
			return;
		}
		var linea = (idx != null && idx >= 0 && RM.estado && RM.estado.lineas[idx])
			? RM.estado.lineas[idx]
			: rmLineaActual();
		if (!linea) return;
		rmSetConsumoColor(linea, codColor, valor);
		rmMarcarDirty(true);
		var especial = rmColorTieneConsumoEspecial(linea, codColor);
		rmPintarInputConsumoColor($inp, especial ? rmConsumoDeColor(linea, codColor) : rmConsumoLinea(linea), especial);
		rmRenderChips();
		rmRenderMatriz();
		rmRenderTarjetasPorArticulo();
		rmActualizarContextoLineaActiva();
	});
	$(document).on("click", ".rmQuitarConsumoColor", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable()) return;
		var codColor = String($(this).attr("data-color") || "");
		var mp = RM.mpActiva;
		var idx = mp ? rmIdxCapaDeMpColor(mp, codColor) : RM.lineaIdx;
		if (mp && idx < 0) idx = RM.lineaIdx;
		if (mp && idx < 0) {
			rmLimpiarConsumoPendiente(mp, codColor);
			rmPintarInputConsumoColor(rmInputConsumoColor(codColor), "1", false);
			rmMarcarDirty(true);
			return;
		}
		var linea = (idx != null && idx >= 0 && RM.estado && RM.estado.lineas[idx])
			? RM.estado.lineas[idx]
			: rmLineaActual();
		if (!linea) return;
		rmSetConsumoColor(linea, codColor, "");
		rmMarcarDirty(true);
		rmRenderChips();
		rmRenderMatriz();
	});

	$("#rmBtnFiltroMp").on("click", function () {
		if (RM._mpFiltroTimer) clearTimeout(RM._mpFiltroTimer);
		rmAplicarFiltroMp();
	});
	$("#rmFiltroMp").on("input", function () {
		if (RM._mpFiltroTimer) clearTimeout(RM._mpFiltroTimer);
		RM._mpFiltroTimer = setTimeout(rmAplicarFiltroMp, 120);
	});
	$("#rmFiltroMp").on("keydown", function (e) {
		if (e.which === 13) {
			e.preventDefault();
			if (RM._mpFiltroTimer) clearTimeout(RM._mpFiltroTimer);
			rmAplicarFiltroMp();
		}
	});

	$(document).on("click", ".rm2-celda-mp", function (e) {
		e.preventDefault();
		e.stopPropagation();
		var mp = String($(this).attr("data-mp") || "");
		if (!mp) return;
		var info = RM.mpCache[mp] || {};
		var idxCapa = Number($(this).attr("data-idx"));
		rmSetMpActiva(
			mp,
			info.unidad || "",
			info.color || "",
			info.descripcion || "",
			isFinite(idxCapa) && idxCapa >= 0 ? idxCapa : null
		);
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

	$(document).on("click", ".rmAgregarMpColor", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable() || !rmExigirMpEnMano()) return;
		rmAgregarMpExtraAColor(String($(this).attr("data-color") || ""), RM.mpActiva);
		rmRefrescarPaso2(true);
	});

	$(document).on("click", ".rmAgregarMpCelda", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable() || !rmExigirMpEnMano()) return;
		var idx = rmAgregarMpExtraAKey(String($(this).attr("data-key") || ""), RM.mpActiva);
		if (idx !== false && idx >= 0) RM.lineaIdx = idx;
		rmRefrescarPaso2(true);
	});

	$(document).on("click", ".rmQuitarMpCapa", function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (!rmEsBorradorEditable()) return;
		rmQuitarMpDeCapa(Number($(this).attr("data-idx")), String($(this).attr("data-key") || ""));
		rmRefrescarPaso2(true);
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

	$("#rmBtnExcelTarjetas").on("click", function () {
		rmExportarExcelTarjetas();
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