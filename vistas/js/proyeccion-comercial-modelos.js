(function ($) {
	"use strict";

	var lineasCache = [];
	var planActual = null;
	var puedeEditarPlanes = false;
	var tiposFactorCache = [];
	var factorLineaActual = null;
	var factoresById = {};
	var espacioActual = null;
	var modeloSeleccionado = null;
	var buscarTimer = null;
	var catalogoFactoresCache = [];
	var catalogoLineaCache = null;
	var modelosProyectadosCache = [];
	var modelosPendientesCache = [];
	var dashboardCache = null;
	var filtroListaModelos = "pendientes";
	var select2Ready = false;
	var mesFactorLineaId = "";
	var factoresPorMesCache = [];
	var silenceMesSelect = false;
	var decMorrisLine = null;
	var decMorrisBar = null;
	var decChartTimer = null;
	var decMesFoco = null;
	var matrizColorPref = {};
	var DEC_COL = {
		h3: "#8e44ad",
		h2: "#16a085",
		h1: "#2980b9",
		curso: "#7fb3d5",
		sug: "#e67e22",
		ofi: "#27ae60"
	};

	function proyAlert(mensaje, tipo) {
		tipo = tipo || "info";
		if (typeof swal === "function") {
			swal({
				type: tipo,
				title: mensaje || "",
				confirmButtonText: "Cerrar"
			});
			return;
		}
		window.proyAlert(mensaje || "");
	}

	function proyConfirm(titulo, texto) {
		if (typeof swal !== "function") {
			return $.Deferred().resolve(window.confirm((titulo || "") + (texto ? "\n" + texto : ""))).promise();
		}
		return swal({
			title: titulo || "Confirmar",
			text: texto || "",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Sí",
			cancelButtonText: "Cancelar"
		}).then(function (result) {
			return !!(result && result.value);
		});
	}

	function proyPrompt(titulo, texto, valorInicial) {
		if (typeof swal !== "function") {
			var v = window.prompt(texto || titulo, valorInicial || "");
			return $.Deferred().resolve(v).promise();
		}
		return swal({
			title: titulo || "",
			text: texto || "",
			input: "text",
			inputValue: valorInicial || "",
			showCancelButton: true,
			confirmButtonText: "Aceptar",
			cancelButtonText: "Cancelar"
		}).then(function (result) {
			if (!result || result.dismiss) {
				return null;
			}
			return result.value == null ? "" : String(result.value);
		});
	}

	function desviacionRelevanteUi(sug, aj, ofi) {
		var base = (Number(sug) || 0) + (Number(aj) || 0);
		var diff = Math.abs((Number(ofi) || 0) - base);
		if (diff <= 5) {
			return false;
		}
		var umbral = (espacioActual && espacioActual.umbral_desviacion_pct != null)
			? Number(espacioActual.umbral_desviacion_pct)
			: 10;
		return (diff / Math.max(1, Math.abs(base))) * 100 > umbral;
	}

	function listaMotivosDesviacion() {
		var list = (espacioActual && espacioActual.motivos_desviacion) || [];
		if (list.length) {
			return list;
		}
		return [
			"Campaña / promoción comercial",
			"Estacionalidad distinta al histórico",
			"Lanzamiento o retiro del modelo",
			"Cambio de precio o lista",
			"Demanda de clientes clave",
			"Ajuste por meta comercial",
			"Abastecimiento / disponibilidad esperada",
			"Corrección por dato histórico atípico"
		];
	}

	function calcDesv(base, ofi) {
		var b = Number(base) || 0;
		var o = Number(ofi) || 0;
		var diff = o - b;
		var pct = b > 0 ? Math.round((diff / b) * 100) : 0;
		return { diff: diff, pct: pct };
	}

	function htmlCeldaDesv(sug, aj, ofi, nFactores) {
		var base = Math.max(0, (Number(sug) || 0) + (Number(aj) || 0));
		var d = calcDesv(base, ofi);
		var relevante = desviacionRelevanteUi(sug, aj, ofi);
		var justificado = (Number(nFactores) || 0) > 0;
		var cls = "proy-desv";
		if (d.diff === 0) {
			return '<span class="text-muted proy-desv">0</span>';
		}
		if (relevante && justificado) {
			cls += " proy-desv-ok";
		} else if (relevante) {
			cls += " proy-desv-warn";
		}
		return '<span class="' + cls + '" title="Oficial − (sug. + factores)">' +
			(d.diff > 0 ? "+" : "") + num(d.diff, 0) +
			" <small>(" + (d.pct > 0 ? "+" : "") + d.pct + "%)</small></span>";
	}

	function htmlMotivoSelect(idLinea, observacion, editable, requiere) {
		var actual = observacion ? String(observacion) : "";
		if (!editable) {
			return actual ? $("<div>").text(actual).html() : "—";
		}
		var opts = '<option value="">' + (requiere ? "Elegir motivo…" : "—") + "</option>";
		var vistos = {};
		listaMotivosDesviacion().forEach(function (m) {
			vistos[m] = true;
			opts += '<option value="' + $("<div>").text(m).html() + '"' +
				(actual === m ? " selected" : "") + ">" + $("<div>").text(m).html() + "</option>";
		});
		if (actual && !vistos[actual]) {
			opts += '<option value="' + $("<div>").text(actual).html() + '" selected>' +
				$("<div>").text(actual).html() + "</option>";
		}
		return '<select class="form-control input-sm selectpicker selMotivoDesv' +
			(requiere ? " proy-motivo-req" : "") +
			'" data-id="' + idLinea + '" data-width="190px" data-container="body" title="Motivo">' +
			opts + "</select>";
	}

	function histRefCerrado(detalle) {
		var v1 = histValorNum(detalle, 1);
		if (v1 != null) {
			return { uds: v1, anio: histAnio(detalle, 1) };
		}
		var v2 = histValorNum(detalle, 2);
		if (v2 != null) {
			return { uds: v2, anio: histAnio(detalle, 2) };
		}
		return null;
	}

	function htmlVsAnio(ofi, ref) {
		if (!ref || ref.uds == null) {
			return '<span class="text-muted">—</span>';
		}
		return '<div class="proy-hist-cell">' +
			'<span class="proy-hist-num">' + num(ref.uds, 0) + "</span>" +
			htmlPctLine(ofi, ref.uds, ref.anio ? String(ref.anio) : "") +
			"</div>";
	}

	function htmlSolesFila(ofi, precio) {
		if (precio == null || !isFinite(precio)) {
			return '<span class="text-muted">—</span>';
		}
		return moneda(Number(ofi) * Number(precio));
	}

	function refrescarDesvFila($tr) {
		var $ofi = $tr.find(".inpOficialModelo");
		if (!$ofi.length) {
			return;
		}
		var sug = Number($ofi.data("sug")) || 0;
		var aj = Number($ofi.data("aj")) || 0;
		var ofi = Number($ofi.val()) || 0;
		var nFact = Number($tr.data("nFactores")) || 0;
		var refUds = $tr.attr("data-hist-ref");
		var refAnio = $tr.attr("data-hist-anio");
		var precio = $tr.attr("data-precio");
		$tr.find(".tdDesv").html(htmlCeldaDesv(sug, aj, ofi, nFact));
		$tr.find(".tdVsAnio").html(htmlVsAnio(ofi, refUds != null && refUds !== ""
			? { uds: Number(refUds), anio: refAnio } : null));
		$tr.find(".tdSoles").html(htmlSolesFila(ofi, precio != null && precio !== "" ? Number(precio) : null));
		pintarResumenCantidades();
	}

	function urlEstado(idPlan, modelo) {
		var base = "index.php?ruta=proyeccion-comercial-modelos";
		var partes = [];
		if (idPlan) {
			partes.push("plan=" + encodeURIComponent(idPlan));
		}
		modelo = $.trim(modelo || "");
		if (modelo) {
			partes.push("modelo=" + encodeURIComponent(modelo));
		}
		return partes.length ? base + "&" + partes.join("&") : base;
	}

	function syncUrlEstado() {
		if (!(window.history && window.history.replaceState)) {
			return;
		}
		var idPlan = $("#proyIdPeriodo").val() || (planActual && planActual.id) || "";
		var modelo = $("#proyModeloActivo").val() || modeloSeleccionado || "";
		window.history.replaceState({}, "", urlEstado(idPlan, modelo));
	}

	function mostrarListado() {
		$("#pantallaListado").show();
		$("#pantallaPlan").hide();
		$("#proyIdPeriodo").val("");
		planActual = null;
		espacioActual = null;
		$("#panelModeloActivo").hide();
		$("#panelModeloCarga").hide();
		$("#decCarga").hide();
		$("#panelModeloVacio").show();
		ocultarCargaBarra();
		$("#proyModeloActivo").val("");
		modeloSeleccionado = null;
		mesFactorLineaId = "";
		resetDecisionModelo();
		$("#proyTituloPagina").html('Proyección comercial <small>Planes de venta por modelo</small>');
		if (window.history && window.history.replaceState) {
			window.history.replaceState({}, "", urlEstado(null, null));
		}
		listarPlanes();
	}

	function mostrarCargaBarra(texto) {
		$("#proyCargaBarraTxt").text(texto || "Cargando…");
		$("#proyCargaBarra").show();
	}

	function ocultarCargaBarra() {
		$("#proyCargaBarra").hide();
	}

	function mostrarCargaModelo(modelo) {
		var nom = $.trim(modelo || modeloSeleccionado || "");
		$("#panelModeloVacio").hide();
		$("#panelModeloActivo").hide();
		$("#proyCargaTitulo").text(nom ? ("Cargando " + nom + "…") : "Cargando modelo…");
		$("#panelModeloCarga").show();
		$("#decVacio").hide();
		$("#decActivo").hide();
		$("#decCarga").show();
		$("#decTitulo").text(nom || "Panorama");
		$("#decSub").text("Armando historial y receta…");
	}

	function ocultarCargaModelo() {
		$("#panelModeloCarga").hide();
		$("#decCarga").hide();
	}

	function mostrarPlan(plan) {
		$("#pantallaListado").hide();
		$("#pantallaPlan").show();
		$("#proyTituloPagina").html(
			'Proyección <small>#' + plan.id + " · " + (plan.nombre || "sin nombre") + "</small>"
		);
		$("#lblPlanActivoBarra").html(
			"<strong>#" + plan.id + "</strong> " + (plan.nombre || "") +
			" · " + plan.estado +
			" · " + pad2(plan.mes_desde) + "/" + plan.anio_desde +
			" → " + pad2(plan.mes_hasta) + "/" + plan.anio_hasta
		);
		$("#btnIrMasiva").attr(
			"href",
			"index.php?ruta=proyeccion-comercial-masiva&plan=" + encodeURIComponent(plan.id)
		);
		syncUrlEstado();
	}

	function pad2(n) {
		n = String(n);
		return n.length < 2 ? "0" + n : n;
	}

	function ymActual() {
		var d = new Date();
		return d.getFullYear() + "-" + pad2(d.getMonth() + 1);
	}

	function ymMasMeses(ym, delta) {
		var partes = String(ym).split("-");
		var anio = parseInt(partes[0], 10);
		var mes = parseInt(partes[1], 10);
		var idx = anio * 12 + (mes - 1) + delta;
		var a = Math.floor(idx / 12);
		var m = (idx % 12) + 1;
		return a + "-" + pad2(m);
	}

	function moneda(v) {
		if (v === null || v === undefined || v === "") {
			return "—";
		}
		return "S/ " + Number(v).toLocaleString("es-PE", {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		});
	}

	function num(v, dec) {
		if (v === null || v === undefined || v === "") {
			return "—";
		}
		return Number(v).toLocaleString("es-PE", {
			minimumFractionDigits: dec || 0,
			maximumFractionDigits: dec || 0
		});
	}

	function post(accion, data) {
		data = data || {};
		data.accion = accion;
		return $.ajax({
			url: "ajax/proyeccion-comercial-modelos.ajax.php",
			method: "POST",
			dataType: "json",
			timeout: 90000,
			data: data
		});
	}

	function filtroOk() {
		var idMarca = parseInt($("#proyMarca").val() || "0", 10);
		var q = $.trim($("#proyQ").val() || "");
		if (idMarca <= 0 && q.length < 1) {
			proyAlert("Elige una marca o un modelo pendiente.");
			return false;
		}
		return true;
	}

	function initSelectsModelo() {
		if (!$.fn.select2 || !$("#selModeloPendiente").length) {
			return;
		}
		select2Ready = true;
		$("#selModeloPendiente").select2({
			placeholder: "Buscar modelo pendiente…",
			allowClear: true,
			width: "100%"
		});
		$("#selModeloProyectado").select2({
			placeholder: "Reabrir modelo proyectado…",
			allowClear: true,
			width: "100%"
		});
	}

	function escHtml(s) {
		return $("<div>").text(s == null ? "" : String(s)).html();
	}

	function coincideBusqueda(m, q) {
		if (!q) {
			return true;
		}
		q = q.toLowerCase();
		return String(m.modelo || "").toLowerCase().indexOf(q) >= 0
			|| String(m.nombre || "").toLowerCase().indexOf(q) >= 0
			|| String(m.marca || "").toLowerCase().indexOf(q) >= 0;
	}

	function ordenarPorCodigo(rows) {
		return (rows || []).slice().sort(function (a, b) {
			return String(a.modelo || "").localeCompare(String(b.modelo || ""), "es", {
				numeric: true,
				sensitivity: "base"
			});
		});
	}

	function htmlItemLista(m, tipo) {
		var activo = modeloSeleccionado && String(modeloSeleccionado) === String(m.modelo);
		var meta;
		if (tipo === "proyectado") {
			meta = (m.marca || "Sin marca") + " · " + num(m.meses, 0) + " " +
				plural(Number(m.meses) || 0, "mes", "meses");
			if (Number(m.unidades_oficiales) > 0) {
				meta += " · " + num(m.unidades_oficiales, 0) + " uds";
			}
		} else {
			meta = m.marca || "Sin marca";
		}
		var btnLabel = tipo === "proyectado" ? "Reabrir" : "Abrir";
		var btnClass = tipo === "proyectado" ? "btn-default" : "btn-primary";
		return '<div class="proy-lista-item' +
			(tipo === "proyectado" ? " is-proyectado" : " is-pendiente") +
			(activo ? " is-active" : "") +
			'" data-modelo="' + escHtml(m.modelo) + '" data-tipo="' + tipo + '">' +
			'<div class="proy-lista-item-main">' +
			'<div class="proy-lista-item-title"><strong>' + escHtml(m.modelo) + "</strong> " +
			escHtml(m.nombre || "") + "</div>" +
			'<div class="proy-lista-item-meta">' + escHtml(meta) + "</div>" +
			"</div>" +
			'<button type="button" class="btn btn-xs ' + btnClass + ' btnListaModelo">' +
			btnLabel + "</button>" +
			"</div>";
	}

	function renderListaModelos() {
		var $lista = $("#listaModelos");
		if (!$lista.length) {
			return;
		}
		var idMarca = parseInt($("#proyMarca").val() || "0", 10);
		var q = $.trim($("#proyQ").val() || "").toLowerCase();
		var pendientes = ordenarPorCodigo((modelosPendientesCache || []).filter(function (m) {
			return coincideBusqueda(m, q);
		}));
		var proyectados = ordenarPorCodigo((modelosProyectadosCache || []).filter(function (m) {
			if (idMarca > 0 && parseInt(m.id_marca, 10) !== idMarca) {
				return false;
			}
			return coincideBusqueda(m, q);
		}));

		$("#chipPendientes").text("Pendientes (" + pendientes.length + ")");
		$("#chipProyectados").text("Proyectados (" + proyectados.length + ")");
		$("#chipTodos").text("Todos (" + (pendientes.length + proyectados.length) + ")");

		var html = [];
		var mostrarPend = filtroListaModelos !== "proyectados";
		var mostrarProy = filtroListaModelos !== "pendientes";
		var n = 0;

		if (filtroListaModelos === "todos") {
			var mezcla = pendientes.map(function (m) {
				return { m: m, tipo: "pendiente" };
			}).concat(proyectados.map(function (m) {
				return { m: m, tipo: "proyectado" };
			}));
			mezcla.sort(function (a, b) {
				return String(a.m.modelo || "").localeCompare(String(b.m.modelo || ""), "es", {
					numeric: true,
					sensitivity: "base"
				});
			});
			mezcla.forEach(function (item) {
				html.push(htmlItemLista(item.m, item.tipo));
				n++;
			});
		} else {
			if (mostrarProy) {
				proyectados.forEach(function (m) {
					html.push(htmlItemLista(m, "proyectado"));
					n++;
				});
			}
			if (mostrarPend) {
				pendientes.forEach(function (m) {
					html.push(htmlItemLista(m, "pendiente"));
					n++;
				});
			}
		}

		if (!n) {
			html.push('<div class="proy-lista-vacio">No hay modelos en este filtro.</div>');
		}
		$lista.html(html.join(""));
		$("#lblListaCount").text(n + " " + plural(n, "modelo", "modelos"));
	}

	function setStatTone($el, tone) {
		$el.removeClass("proy-stat--primary proy-stat--ok proy-stat--warn proy-stat--danger proy-stat--neutral");
		if (tone) {
			$el.addClass("proy-stat--" + tone);
		}
	}

	function plural(n, uno, muchos) {
		return n === 1 ? uno : muchos;
	}

	function renderStats(stats) {
		stats = stats || {};
		var avance = Number(stats.avance_pct) || 0;
		var activos = Number(stats.modelos_activos) || 0;
		var proyectados = Number(stats.modelos_proyectados) || 0;
		var pendientes = Number(stats.modelos_pendientes) || 0;
		var udsOfi = Number(stats.unidades_oficiales) || 0;
		var udsSug = Number(stats.unidades_sugeridas) || 0;
		var sinLista9 = Number(stats.lineas_sin_lista9) || 0;
		var borrador = Number(stats.lineas_borrador) || 0;
		var publicadas = Number(stats.lineas_publicadas) || 0;
		var cerradas = Number(stats.lineas_cerradas) || 0;
		var totalLineas = Number(stats.total_lineas) || 0;

		$("#stAvance").text(avance.toFixed(1) + "%");
		$("#stAvanceMeta").text(num(proyectados, 0) + " de " + num(activos, 0) + " modelos");
		$("#stBarra").css("width", Math.min(100, avance) + "%");
		if (avance >= 80) {
			setStatTone($("#cardAvance"), "ok");
		} else if (avance >= 40) {
			setStatTone($("#cardAvance"), "primary");
		} else {
			setStatTone($("#cardAvance"), "warn");
		}

		$("#stProyectados").text(num(proyectados, 0));
		$("#stProyectadosMeta").text(
			num(totalLineas, 0) + " " + plural(totalLineas, "línea en el plan", "líneas en el plan")
		);
		setStatTone($("#cardProyectados"), proyectados > 0 ? "ok" : "neutral");

		$("#stPendientes").text(num(pendientes, 0));
		$("#stPendientesMeta").text(
			pendientes === 0 ? "Todos los activos cubiertos" : "modelos activos sin proyectar"
		);
		setStatTone($("#cardPendientes"), pendientes > 0 ? "warn" : "ok");

		$("#stUds").text(num(udsOfi, 0));
		var metaUds = "de " + num(proyectados, 0) + " " +
			plural(proyectados, "modelo ya proyectado", "modelos ya proyectados");
		if (udsSug > 0) {
			metaUds += " · sug. " + num(udsSug, 0);
		}
		if (sinLista9 > 0) {
			metaUds += " · " + num(sinLista9, 0) + " sin lista 9";
		}
		$("#stUdsMeta").text(metaUds);
		setStatTone($("#cardUds"), sinLista9 > 0 ? "warn" : "neutral");

		$("#stBorrador").text(num(borrador, 0));
		$("#stBorradorMeta").text(
			plural(borrador, "línea aún no publicada", "líneas aún no publicadas")
		);
		setStatTone($("#cardBorrador"), borrador > 0 ? "warn" : "neutral");

		$("#stPublicadas").text(num(publicadas, 0));
		var metaPub = plural(publicadas, "línea publicada", "líneas publicadas");
		if (cerradas > 0) {
			metaPub += " · " + num(cerradas, 0) + " " + plural(cerradas, "cerrada", "cerradas");
		}
		$("#stPublicadasMeta").text(metaPub);
		setStatTone($("#cardPublicadas"), publicadas > 0 ? "ok" : "neutral");
		renderDashboard(stats, dashboardCache, planActual);
	}

	function nombreMesCorto(mes) {
		var n = ["", "Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
		mes = parseInt(mes, 10);
		return n[mes] || String(mes);
	}

	function nombreMesLargo(mes) {
		var n = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
			"Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
		mes = parseInt(mes, 10);
		return n[mes] || String(mes);
	}

	function etiquetaMesDePeriodo(periodo, largo) {
		var mes = mesDesdePeriodo(periodo);
		var anio = anioDesdePeriodo(periodo);
		var nom = largo ? nombreMesLargo(mes) : nombreMesCorto(mes);
		return nom + (anio ? " " + anio : "");
	}

	function etiquetaEstadoLinea(estado) {
		var e = String(estado || "").toLowerCase();
		if (e === "borrador") {
			return "Borrador";
		}
		if (e === "publicado") {
			return "Publicado";
		}
		if (e === "cerrado") {
			return "Cerrado";
		}
		return estado || "";
	}

	function deltaTxt(ofi, sug) {
		var d = (Number(ofi) || 0) - (Number(sug) || 0);
		if (!d) {
			return "igual a sug.";
		}
		var cls = d > 0 ? "proy-num-pos" : "proy-num-neg";
		return '<span class="' + cls + '">' + (d > 0 ? "+" : "") + num(d, 0) + "</span>";
	}

	function chipAlerta(cls, icono, texto) {
		return '<span class="proy-dash-chip ' + cls + '"><i class="fa ' + icono + '"></i> ' +
			texto + "</span>";
	}

	function mostrarResumenPlan() {
		modeloSeleccionado = null;
		$("#proyModeloActivo").val("");
		$("#panelModeloActivo").hide();
		$("#panelModeloVacio").show();
		$(".proy-lista-item").removeClass("is-active");
		resetDecisionModelo();
		syncUrlEstado();
	}

	function htmlDecKpi(label, value, cls, meta) {
		return '<div class="proy-dec-kpi' + (cls ? " " + cls : "") + '"><em>' +
			label + "</em><b>" + value + "</b>" +
			(meta ? '<span class="proy-dec-kpi-meta">' + meta + "</span>" : "") +
			"</div>";
	}

	function flechaPct(pct) {
		if (pct == null || !isFinite(pct)) {
			return "—";
		}
		if (pct > 5) {
			return "▲ " + fmtPct(pct, 0);
		}
		if (pct < -5) {
			return "▼ " + fmtPct(pct, 0);
		}
		return "• " + fmtPct(pct, 0);
	}

	function clsPct(pct) {
		if (pct == null || !isFinite(pct)) {
			return "";
		}
		if (pct > 5) {
			return "is-pos";
		}
		if (pct < -5) {
			return "is-neg";
		}
		return "";
	}

	function bandaSugMes(sug, a, b) {
		var vals = [];
		if (a != null && isFinite(a)) {
			vals.push(Number(a));
		}
		if (b != null && isFinite(b)) {
			vals.push(Number(b));
		}
		if (!vals.length || sug == null || !isFinite(sug)) {
			return "nd";
		}
		sug = Number(sug);
		var lo = Math.min.apply(null, vals);
		var hi = Math.max.apply(null, vals);
		if (sug > hi * 1.1) {
			return "alta";
		}
		if (sug < lo * 0.9) {
			return "baja";
		}
		return "rango";
	}

	function pintarRecomendacion(opts) {
		opts = opts || {};
		var altas = opts.altas || [];
		var bajas = opts.bajas || [];
		var nEval = Number(opts.nEval) || 0;
		var lblRef = opts.lblRef || "";
		var lblPrev = opts.lblPrev || "";
		var titulo = "Usá la sugerencia";
		var detalle = "";
		var cls = "is-ok";
		var chips = [];

		if (!nEval) {
			titulo = "Sin año cerrado";
			detalle = "Cuando cierre el periodo comparable, acá va a aparecer si conviene subir o bajar.";
			cls = "";
		} else if (altas.length && bajas.length) {
			titulo = "Revisá meses";
			detalle = "Hay meses altos y bajos vs " + lblPrev + "–" + lblRef + ".";
			cls = "is-mix";
		} else if (altas.length) {
			titulo = "Revisá bajar · " + altas.join(", ");
			detalle = "Por encima de " + lblPrev + " y " + lblRef + ".";
			cls = "is-down";
		} else if (bajas.length) {
			titulo = "Revisá subir · " + bajas.join(", ");
			detalle = "Por debajo de " + lblRef + " y " + lblPrev + ".";
			cls = "is-up";
		} else if (opts.rango && lblRef && lblPrev) {
			detalle = opts.rango + " entre " + lblPrev + " y " + lblRef + ".";
		} else if (lblRef) {
			detalle = "En línea con " + lblRef + ".";
		} else {
			detalle = "En línea con el último periodo cerrado.";
		}

		var recPct = opts.recientePct;
		if (recPct != null && isFinite(recPct) && nEval) {
			if (recPct < -10) {
				if (cls === "is-ok") {
					titulo = "Usá la sugerencia · no subas";
				}
				chips.push('<span class="proy-dec-chip is-alta">A la baja</span>');
			} else if (recPct > 10) {
				chips.push('<span class="proy-dec-chip is-ok">Al alza</span>');
			}
		}

		altas.forEach(function (m) {
			chips.push('<span class="proy-dec-chip is-alta">' + m + " bajá</span>");
		});
		bajas.forEach(function (m) {
			chips.push('<span class="proy-dec-chip is-baja">' + m + " subí</span>");
		});

		$("#decRecoBox").html(
			'<div class="proy-dec-reco ' + cls + '"><b>' + titulo + "</b>" +
			(detalle ? "<span>" + detalle + "</span>" : "") +
			(chips.length ? '<div class="proy-dec-chips">' + chips.join("") + "</div>" : "") +
			"</div>"
		);
	}

	function pctCambio(actual, anterior) {
		if (actual == null || anterior == null || !isFinite(actual) || !isFinite(anterior)) {
			return null;
		}
		if (Number(anterior) === 0) {
			return null;
		}
		return ((Number(actual) - Number(anterior)) / Math.abs(Number(anterior))) * 100;
	}

	function sentidoPct(pct, umbral) {
		umbral = umbral == null ? 5 : umbral;
		if (pct == null) {
			return null;
		}
		if (pct > umbral) {
			return "crece";
		}
		if (pct < -umbral) {
			return "baja";
		}
		return "se mantiene";
	}

	function fmtPct(pct, dec) {
		if (pct == null || !isFinite(pct)) {
			return "—";
		}
		dec = dec == null ? 0 : dec;
		return (pct > 0 ? "+" : "") + pct.toFixed(dec) + "%";
	}

	function mesDesdePeriodo(periodo) {
		var p = String(periodo || "");
		var parts = p.split("-");
		return parseInt(parts[1], 10) || 0;
	}

	function anioDesdePeriodo(periodo) {
		var p = String(periodo || "");
		var parts = p.split("-");
		return parseInt(parts[0], 10) || 0;
	}

	function etiquetaMesPlan(m) {
		var mes = m.mes != null ? m.mes : mesDesdePeriodo(m.periodo);
		var anio = m.anio != null ? m.anio : anioDesdePeriodo(m.periodo);
		var yy = anio ? String(anio).slice(-2) : "";
		return nombreMesCorto(mes) + (yy ? " " + yy : "");
	}

	function oficialVivo(linea) {
		if (linea && linea.id) {
			var $inp = $(".inpOficialModelo[data-id='" + linea.id + "']");
			if ($inp.length) {
				return Number($inp.val()) || 0;
			}
		}
		return Number(linea && linea.unidades_oficiales) || 0;
	}

	function sugDeLinea(linea, ctx) {
		var sug = Number(linea && linea.unidades_sugeridas) || 0;
		if (ctx && ctx.sugerencias && linea && ctx.sugerencias[linea.periodo] &&
			ctx.sugerencias[linea.periodo].unidades != null) {
			sug = Number(ctx.sugerencias[linea.periodo].unidades) || 0;
		}
		return sug;
	}

	function mixSugColor(bloque, color, tallas) {
		var s = 0;
		(tallas || []).forEach(function (t) {
			var cel = ((bloque && bloque.celdas) || {})[claveVariante(color.cod, t.cod)];
			s += cel ? Number(cel.sug) || 0 : 0;
		});
		return s;
	}

	function mixOffBloque(mz, bloque) {
		var prefs = matrizPrefs();
		var off = 0;
		(mz.colores || []).forEach(function (c) {
			if (prefs.off[c.cod]) {
				off += mixSugColor(bloque, c, mz.tallas);
			}
		});
		return off;
	}

	function poolSugBloque(mz, bloque) {
		var orig = Number(bloque && bloque.sug) || 0;
		var pool = orig - mixOffBloque(mz, bloque);
		return pool < 0 ? 0 : pool;
	}

	function ajustarSugPorColores(sug, periodo, ctx) {
		var mz = ctx && ctx.matriz_articulos;
		if (!mz || sug == null || !isFinite(Number(sug))) {
			return sug;
		}
		var bloque = (periodo && mz.por_mes && mz.por_mes[periodo]) ? mz.por_mes[periodo] : null;
		if (!bloque || !(Number(bloque.sug) > 0)) {
			bloque = mz.plan;
		}
		if (!bloque || !(Number(bloque.sug) > 0)) {
			return sug;
		}
		var orig = Number(bloque.sug);
		var pool = poolSugBloque(mz, bloque);
		if (pool === orig) {
			return sug;
		}
		return Math.round(Number(sug) * (pool / orig));
	}

	function destroyDecisionCharts() {
		if (decChartTimer) {
			clearTimeout(decChartTimer);
			decChartTimer = null;
		}
		$("#decChartComp").empty().removeClass("proy-dec-chart--empty").addClass("proy-dec-chart");
		decMorrisLine = null;
		decMorrisBar = null;
	}

	function resetDecisionModelo() {
		destroyDecisionCharts();
		decMesFoco = null;
		$("#decVerPlan").hide();
		$("#tablaHistEstacional tbody tr").removeClass("is-foco");
		$("#decActivo").hide();
		$("#decVacio").show();
		$("#decTitulo").text("Panorama");
		$("#decSub").text("Elige un modelo para ver tendencia y comparación.");
		$("#decCompLine, #decTendKpis, #decLegendComp, #decRecoBox, #decVieneKpis").empty();
		$("#decTendLabel").text("Tendencia");
		$("#decVieneLabel").text("Cómo viene");
	}

	function mesesDecision(ctx, lineas) {
		var meses = (ctx && ctx.periodo_plan && ctx.periodo_plan.meses_lista) || [];
		if (meses.length) {
			return meses;
		}
		var filas = (ctx && ctx.historial_estacional && ctx.historial_estacional.filas) || [];
		if (filas.length) {
			return filas.map(function (f) {
				return {
					periodo: f.periodo,
					mes: mesDesdePeriodo(f.periodo),
					anio: anioDesdePeriodo(f.periodo)
				};
			});
		}
		return (lineas || []).map(function (l) {
			return {
				periodo: l.periodo,
				mes: l.mes != null ? l.mes : mesDesdePeriodo(l.periodo),
				anio: l.anio != null ? l.anio : anioDesdePeriodo(l.periodo)
			};
		});
	}

	function pintarDecisionCharts(lineData, aniosLinea) {
		if (typeof Morris === "undefined") {
			$("#decChartComp").empty().addClass("proy-dec-chart--empty").removeClass("proy-dec-chart")
				.html("Sin gráfico.");
			decMorrisLine = null;
			decMorrisBar = null;
			return;
		}
		aniosLinea = aniosLinea || {};
		var incluirH1 = !!aniosLinea.incluirH1;
		$("#decChartComp").empty().removeClass("proy-dec-chart--empty").addClass("proy-dec-chart");
		decMorrisLine = null;
		decMorrisBar = null;
		var hayCurso = incluirH1 && lineData.some(function (r) {
			return r.h1curso != null;
		});
		var hayOfiDistinto = lineData.some(function (r) {
			return r.ofi != null && Number(r.ofi) !== Number(r.sug || 0);
		});
		if (!lineData.length) {
			$("#decChartComp").addClass("proy-dec-chart--empty").removeClass("proy-dec-chart")
				.html("Sin meses para graficar.");
			return;
		}
		var ykeys = ["h3", "h2"];
		var lineLabels = [
			etiquetaAnio(3, aniosLinea.h3),
			etiquetaAnio(2, aniosLinea.h2)
		];
		var lineColors = [DEC_COL.h3, DEC_COL.h2];
		if (incluirH1) {
			ykeys.push("h1");
			lineLabels.push(etiquetaAnio(1, aniosLinea.h1));
			lineColors.push(DEC_COL.h1);
		}
		if (hayCurso) {
			ykeys.push("h1curso");
			lineLabels.push("En curso");
			lineColors.push(DEC_COL.curso);
		}
		ykeys.push("sug");
		lineLabels.push("Sug.");
		lineColors.push(DEC_COL.sug);
		if (hayOfiDistinto) {
			ykeys.push("ofi");
			lineLabels.push("Oficial");
			lineColors.push(DEC_COL.ofi);
		}
		decMorrisLine = Morris.Line({
			element: "decChartComp",
			resize: true,
			parseTime: false,
			smooth: false,
			data: lineData,
			xkey: "mes",
			ykeys: ykeys,
			labels: lineLabels,
			lineColors: lineColors,
			pointSize: 2,
			lineWidth: 2,
			hideHover: "auto",
			gridTextColor: "#8898aa",
			gridTextSize: 9,
			ymin: 0,
			numLines: 4,
			xLabelAngle: lineData.length > 10 ? 45 : 0,
			yLabelFormat: function (y) {
				return num(y, 0);
			}
		});
	}

	function renderDecisionModelo(resp) {
		if (!$("#proyDecisionCol").length) {
			return;
		}
		var ctx = resp && resp.contexto;
		var lineas = (resp && resp.lineas) || [];
		if (!ctx) {
			resetDecisionModelo();
			return;
		}
		$("#decVacio").hide();
		$("#decActivo").show();
		var cab = ctx.cabecera || {};
		$("#decTitulo").text(cab.modelo || "Panorama");

		var meses = mesesDecision(ctx, lineas);
		var byPeriodo = {};
		lineas.forEach(function (l) {
			byPeriodo[l.periodo] = l;
		});
		var est = mapaEstacional(ctx);

		var nBorr = 0;
		var nPub = 0;
		var nCerr = 0;
		var nFalta = 0;
		meses.forEach(function (m) {
			var l = byPeriodo[m.periodo];
			if (l) {
				if (l.estado_linea === "PUBLICADO") {
					nPub++;
				} else if (l.estado_linea === "CERRADO") {
					nCerr++;
				} else {
					nBorr++;
				}
			} else {
				nFalta++;
			}
		});
		var nCarg = nBorr + nPub + nCerr;
		var subParts = [];
		if (cab.nombre || cab.marca) {
			subParts.push(cab.nombre || cab.marca);
		}
		if (meses.length) {
			subParts.push(nCarg + "/" + meses.length);
		}
		if (nBorr) {
			subParts.push(nBorr + " borrador");
		} else if (nPub + nCerr) {
			subParts.push((nPub + nCerr) + " publicados");
		}
		if (nFalta) {
			subParts.push("faltan " + nFalta);
		}
		$("#decSub").text(subParts.join(" · ") || "");

		var totOfi = 0;
		var totSug = 0;
		var nDesv = 0;
		var desvMeses = [];
		lineas.forEach(function (l) {
			var sug = sugDeLinea(l, ctx);
			var aj = Number(l.unidades_ajustes) || 0;
			var ofi = oficialVivo(l);
			totOfi += ofi;
			totSug += sug;
			if (desviacionRelevanteUi(sug, aj, ofi)) {
				nDesv++;
				desvMeses.push(nombreMesCorto(l.mes != null ? l.mes : mesDesdePeriodo(l.periodo)));
			}
		});
		var dOfi = totOfi - totSug;
		var pctOfi = pctCambio(totOfi, totSug);
		var clsOfi = dOfi > 0 ? "is-pos" : (dOfi < 0 ? "is-neg" : "");
		var lineHtml;
		if (!dOfi) {
			lineHtml = "<span>Oficial = sugerencia <b>" + num(totOfi, 0) + "</b></span>";
		} else {
			lineHtml = "<span>Oficial <b>" + num(totOfi, 0) + "</b></span>" +
				"<span>Sug. <b>" + num(totSug, 0) + "</b></span>" +
				'<span class="' + clsOfi + '">Δ <b>' + (dOfi > 0 ? "+" : "") + num(dOfi, 0) +
				"</b> " + fmtPct(pctOfi, 0) + "</span>";
		}
		if (nDesv) {
			lineHtml += '<span class="is-warn">' + nDesv +
				(nDesv === 1 ? " mes >10%" : " meses >10%") +
				(desvMeses.length ? " · " + desvMeses.join(", ") : "") +
				"</span>";
		}
		$("#decCompLine").html(lineHtml);

		var totH1Per = 0;
		var totH2Per = 0;
		var totH3Per = 0;
		var lineData = [];
		var recAltas = [];
		var recBajas = [];
		var recNEval = 0;
		var anioH1 = null;
		var anioH2 = null;
		var anioH3 = null;
		var h1Completo = offsetPeriodoCompleto(meses, est, 1);
		var h2Completo = offsetPeriodoCompleto(meses, est, 2);
		var h3Completo = offsetPeriodoCompleto(meses, est, 3);
		if (h1Completo) {
			totH1Per = totOffsetPeriodo(meses, est, 1);
		}
		if (h2Completo) {
			totH2Per = totOffsetPeriodo(meses, est, 2);
		}
		if (h3Completo) {
			totH3Per = totOffsetPeriodo(meses, est, 3);
		}

		meses.forEach(function (m) {
			var f = est[m.periodo];
			var l = byPeriodo[m.periodo];
			var hist = f ? f.historial : [];
			var h1 = h1Completo ? histValorCerrado(hist, 1) : null;
			var h2 = histValorCerrado(hist, 2);
			var h3 = histValorCerrado(hist, 3);
			var h1curso = h1Completo ? histValorEnCurso(hist, 1) : null;
			var sug = 0;
			var ofi = null;
			if (l) {
				sug = sugDeLinea(l, ctx);
				ofi = oficialVivo(l);
			} else if (f && f.sugerencia && f.sugerencia.unidades != null) {
				sug = Number(f.sugerencia.unidades) || 0;
			}
			if (!anioH1) {
				anioH1 = histAnio(hist, 1);
			}
			if (!anioH2) {
				anioH2 = histAnio(hist, 2);
			}
			if (!anioH3) {
				anioH3 = histAnio(hist, 3);
			}
			var nomMes = nombreMesCorto(m.mes != null ? m.mes : mesDesdePeriodo(m.periodo));
			var refMes = h1Completo ? h1 : h2;
			var prevMes = h1Completo ? h2 : h3;
			var band = bandaSugMes(sug, refMes, prevMes);
			if (band === "alta") {
				recAltas.push(nomMes);
			} else if (band === "baja") {
				recBajas.push(nomMes);
			}
			if (band !== "nd") {
				recNEval++;
			}
			lineData.push({
				mes: etiquetaMesPlan(m),
				h3: h3,
				h2: h2,
				h1: h1,
				h1curso: h1curso,
				sug: sug,
				ofi: ofi
			});
		});

		var lblH1 = etiquetaAnio(1, anioH1);
		var lblH2 = etiquetaAnio(2, anioH2);
		var lblH3 = etiquetaAnio(3, anioH3);
		var rangoPlan = rangoMesesPlan(meses);
		var tendTitulo = rangoPlan ? rangoPlan : "Tendencia";
		if (!h1Completo && anioH1) {
			tendTitulo += " · sin " + lblH1;
		}
		$("#decTendLabel").text(tendTitulo);

		var totRef = h1Completo ? totH1Per : (h2Completo ? totH2Per : null);
		var lblRef = h1Completo ? lblH1 : (h2Completo ? lblH2 : null);
		var totPrev = h1Completo ? (h2Completo ? totH2Per : null) : (h3Completo ? totH3Per : null);
		var lblPrev = h1Completo ? lblH2 : lblH3;
		var tendHtml = "";
		if (totRef != null && totPrev != null) {
			var pctHist = pctCambio(totRef, totPrev);
			tendHtml += htmlDecKpi(
				lblRef + " vs " + lblPrev,
				flechaPct(pctHist),
				clsPct(pctHist),
				num(totRef, 0) + " vs " + num(totPrev, 0)
			);
		}
		if (totRef != null) {
			var pctSugRef = pctCambio(totSug, totRef);
			tendHtml += htmlDecKpi(
				"Sug. vs " + lblRef,
				flechaPct(pctSugRef),
				clsPct(pctSugRef),
				num(totSug, 0) + " vs " + num(totRef, 0)
			);
			if (dOfi) {
				var pctOfiRef = pctCambio(totOfi, totRef);
				tendHtml += htmlDecKpi(
					"Oficial vs " + lblRef,
					flechaPct(pctOfiRef),
					clsPct(pctOfiRef),
					num(totOfi, 0) + " vs " + num(totRef, 0)
				);
			} else if (totPrev != null) {
				var pctSugPrev = pctCambio(totSug, totPrev);
				tendHtml += htmlDecKpi(
					"Sug. vs " + lblPrev,
					flechaPct(pctSugPrev),
					clsPct(pctSugPrev),
					num(totSug, 0) + " vs " + num(totPrev, 0)
				);
			}
		}
		if (!tendHtml) {
			tendHtml = htmlDecKpi("Periodo", "—", "", "Sin años cerrados");
		}
		$("#decTendKpis").toggleClass("proy-dec-kpis--3", true).html(tendHtml);

		var recient = (ctx && ctx.tendencia_reciente) || {};
		pintarRecomendacion({
			altas: recAltas,
			bajas: recBajas,
			nEval: recNEval,
			lblRef: lblRef || "",
			lblPrev: totPrev != null ? lblPrev : "",
			rango: rangoPlan,
			recientePct: recient.pct,
			recienteRango: recient.rango || ""
		});

		if (recient.n_meses) {
			$("#decVieneLabel").text("Cómo viene · " + (recient.rango || ""));
			var htmlViene = htmlDecKpi(
				(recient.anio || "") + " vs " + (recient.anio_ant || ""),
				flechaPct(recient.pct),
				clsPct(recient.pct),
				num(recient.uds, 0) + " vs " + num(recient.uds_anio_ant, 0)
			);
			if (recient.pct_3 != null) {
				htmlViene += htmlDecKpi(
					"3 m · " + (recient.rango_3 || ""),
					flechaPct(recient.pct_3),
					clsPct(recient.pct_3),
					num(recient.uds_3, 0) + " vs " + num(recient.uds_3_anio_ant, 0)
				);
			}
			$("#decVieneKpis").html(htmlViene);
		} else {
			$("#decVieneLabel").text("Cómo viene");
			$("#decVieneKpis").html(htmlDecKpi("Año en curso", "—", "", "Sin meses cerrados"));
		}

		var legend = '<span><i style="background:' + DEC_COL.h3 + '"></i>' + lblH3 + "</span>" +
			'<span><i style="background:' + DEC_COL.h2 + '"></i>' + lblH2 + "</span>";
		if (h1Completo) {
			legend += '<span><i style="background:' + DEC_COL.h1 + '"></i>' + lblH1 + "</span>";
		}
		legend += '<span><i style="background:' + DEC_COL.sug + '"></i>Sug.</span>';
		if (dOfi) {
			legend += '<span><i style="background:' + DEC_COL.ofi + '"></i>Oficial</span>';
		}
		$("#decLegendComp").html(legend);

		if (decChartTimer) {
			clearTimeout(decChartTimer);
		}
		decChartTimer = setTimeout(function () {
			pintarDecisionCharts(lineData, {
				h1: anioH1,
				h2: anioH2,
				h3: anioH3,
				incluirH1: h1Completo
			});
		}, 40);

		if (decMesFoco) {
			aplicarPanoramaMes(ctx, lineas, byPeriodo, est, decMesFoco);
		} else {
			$("#decVerPlan").hide();
		}
	}

	function pintarLineaOficialSug(totOfi, totSug, nDesv, desvMeses) {
		var dOfi = totOfi - totSug;
		var pctOfi = pctCambio(totOfi, totSug);
		var clsOfi = dOfi > 0 ? "is-pos" : (dOfi < 0 ? "is-neg" : "");
		var lineHtml;
		if (!dOfi) {
			lineHtml = "<span>Oficial = sugerencia <b>" + num(totOfi, 0) + "</b></span>";
		} else {
			lineHtml = "<span>Oficial <b>" + num(totOfi, 0) + "</b></span>" +
				"<span>Sug. <b>" + num(totSug, 0) + "</b></span>" +
				'<span class="' + clsOfi + '">Δ <b>' + (dOfi > 0 ? "+" : "") + num(dOfi, 0) +
				"</b> " + fmtPct(pctOfi, 0) + "</span>";
		}
		if (nDesv) {
			lineHtml += '<span class="is-warn">' + nDesv +
				(nDesv === 1 ? " mes >10%" : " meses >10%") +
				(desvMeses && desvMeses.length ? " · " + desvMeses.join(", ") : "") +
				"</span>";
		}
		$("#decCompLine").html(lineHtml);
		return dOfi;
	}

	function aplicarPanoramaMes(ctx, lineas, byPeriodo, est, periodo) {
		var f = est[periodo] || {};
		var hist = f.historial || [];
		var l = byPeriodo[periodo];
		var nom = nombreMesCorto(mesDesdePeriodo(periodo));
		var cab = ctx.cabecera || {};
		var sug = 0;
		var ofi = 0;
		var nDesv = 0;
		if (l) {
			sug = sugDeLinea(l, ctx);
			ofi = oficialVivo(l);
			if (desviacionRelevanteUi(sug, Number(l.unidades_ajustes) || 0, ofi)) {
				nDesv = 1;
			}
		} else if (f.sugerencia && f.sugerencia.unidades != null) {
			sug = Number(f.sugerencia.unidades) || 0;
			ofi = sug;
		}
		var h1 = histValorCerrado(hist, 1);
		var h1c = histValorEnCurso(hist, 1);
		var h2 = histValorCerrado(hist, 2);
		var h3 = histValorCerrado(hist, 3);
		var a1 = histAnio(hist, 1);
		var a2 = histAnio(hist, 2);
		var a3 = histAnio(hist, 3);
		var lblH1 = etiquetaAnio(1, a1);
		var lblH2 = etiquetaAnio(2, a2);
		var lblH3 = etiquetaAnio(3, a3);
		var sub = cab.nombre || cab.marca || "";
		$("#decSub").text((sub ? sub + " · " : "") + nom);
		$("#decVerPlan").show();

		pintarLineaOficialSug(ofi, sug, nDesv, nDesv ? [nom] : []);

		var ref = h1 != null ? h1 : h2;
		var prev = h1 != null ? h2 : h3;
		var lblRef = h1 != null ? lblH1 : lblH2;
		var lblPrev = h1 != null ? lblH2 : lblH3;
		var band = bandaSugMes(sug, ref, prev);
		var recient = (ctx && ctx.tendencia_reciente) || {};
		pintarRecomendacion({
			altas: band === "alta" ? [nom] : [],
			bajas: band === "baja" ? [nom] : [],
			nEval: band !== "nd" ? 1 : 0,
			lblRef: ref != null ? lblRef : "",
			lblPrev: prev != null ? lblPrev : "",
			rango: nom,
			recientePct: recient.pct,
			recienteRango: recient.rango || ""
		});

		$("#decVieneLabel").text("Este mes · " + nom);
		var htmlViene = "";
		if (h1 != null && h2 != null) {
			htmlViene += htmlDecKpi(
				lblH1 + " vs " + lblH2,
				flechaPct(pctCambio(h1, h2)),
				clsPct(pctCambio(h1, h2)),
				num(h1, 0) + " vs " + num(h2, 0)
			);
		} else if (h1c != null && h2 != null) {
			htmlViene += htmlDecKpi(
				lblH1 + " en curso vs " + lblH2,
				flechaPct(pctCambio(h1c, h2)),
				clsPct(pctCambio(h1c, h2)),
				num(h1c, 0) + " vs " + num(h2, 0)
			);
		}
		if (h2 != null && h3 != null) {
			htmlViene += htmlDecKpi(
				lblH2 + " vs " + lblH3,
				flechaPct(pctCambio(h2, h3)),
				clsPct(pctCambio(h2, h3)),
				num(h2, 0) + " vs " + num(h3, 0)
			);
		}
		$("#decVieneKpis").html(htmlViene || htmlDecKpi("Histórico", "—", "", "Sin años cerrados"));

		var totRef = h1 != null ? h1 : h2;
		$("#decTendLabel").text("Sug. · " + nom);
		var tendHtml = "";
		if (totRef != null) {
			tendHtml += htmlDecKpi(
				"Sug. vs " + lblRef,
				flechaPct(pctCambio(sug, totRef)),
				clsPct(pctCambio(sug, totRef)),
				num(sug, 0) + " vs " + num(totRef, 0)
			);
		}
		if (prev != null && prev !== totRef) {
			tendHtml += htmlDecKpi(
				"Sug. vs " + lblPrev,
				flechaPct(pctCambio(sug, prev)),
				clsPct(pctCambio(sug, prev)),
				num(sug, 0) + " vs " + num(prev, 0)
			);
		}
		$("#decTendKpis").toggleClass("proy-dec-kpis--3", false).html(
			tendHtml || htmlDecKpi("Mes", "—", "", "Sin años cerrados")
		);
	}

	function setMesFoco(periodo) {
		if (periodo && decMesFoco === periodo) {
			decMesFoco = null;
		} else {
			decMesFoco = periodo || null;
		}
		$("#tablaHistEstacional tbody tr").removeClass("is-foco");
		if (decMesFoco) {
			$("#tablaHistEstacional tbody tr[data-periodo='" + decMesFoco + "']").addClass("is-foco");
		}
		if (espacioActual) {
			renderDecisionModelo(espacioActual);
			renderMatrizSugerencia(espacioActual.contexto);
		}
	}

	function proyEsc(valor) {
		return $("<div>").text(valor == null ? "" : String(valor)).html();
	}

	function proyColorVisual(nombre) {
		var normalizar = function (valor) {
			return String(valor || "").toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
		};
		var texto = normalizar(nombre);
		var paleta = window.proyPaletaColores || {};
		var claves = Object.keys(paleta).sort(function (a, b) {
			return normalizar(b).length - normalizar(a).length;
		});
		var validar = function (color) {
			return /^#[0-9a-f]{6}$/i.test(String(color || "")) ? color : null;
		};
		var i;
		for (i = 0; i < claves.length; i++) {
			if (normalizar(claves[i]) === texto) {
				return validar(paleta[claves[i]]) || "#d9dee3";
			}
		}
		for (i = 0; i < claves.length; i++) {
			if (texto.indexOf(normalizar(claves[i])) !== -1) {
				return validar(paleta[claves[i]]) || "#d9dee3";
			}
		}
		return "#d9dee3";
	}

	function matrizPrefKey() {
		return String(modeloSeleccionado || "") + "|" + String($("#proyIdPeriodo").val() || "");
	}

	function matrizPrefs() {
		var k = matrizPrefKey();
		if (!matrizColorPref[k]) {
			matrizColorPref[k] = { off: {}, uds: {} };
		}
		return matrizColorPref[k];
	}

	function matrizPrefsLoad() {
		try {
			matrizColorPref = JSON.parse(localStorage.getItem("proyMatrizColores") || "{}") || {};
		} catch (e) {
			matrizColorPref = {};
		}
	}

	function matrizPrefsSave() {
		try {
			localStorage.setItem("proyMatrizColores", JSON.stringify(matrizColorPref));
		} catch (e) { /* noop */ }
	}

	function claveVariante(codColor, codTalla) {
		return String(codColor || "") + "|" + String(codTalla || "");
	}

	function repartirEnterosJs(total, pesos) {
		var out = {};
		var suma = 0;
		var k;
		for (k in pesos) {
			if (!pesos.hasOwnProperty(k)) {
				continue;
			}
			out[k] = 0;
			suma += Number(pesos[k]) || 0;
		}
		total = Math.round(Number(total) || 0);
		if (total <= 0 || suma <= 0) {
			return out;
		}
		var fracs = [];
		var asignado = 0;
		for (k in pesos) {
			if (!pesos.hasOwnProperty(k)) {
				continue;
			}
			var v = total * ((Number(pesos[k]) || 0) / suma);
			var ent = Math.floor(v);
			out[k] = ent;
			asignado += ent;
			fracs.push({ k: k, f: v - ent });
		}
		fracs.sort(function (a, b) { return b.f - a.f; });
		var resto = total - asignado;
		var i;
		for (i = 0; i < fracs.length && resto > 0; i++) {
			out[fracs[i].k]++;
			resto--;
		}
		return out;
	}

	function armarCeldasMatriz(mz, bloque) {
		var prefs = matrizPrefs();
		var colores = mz.colores || [];
		var tallas = mz.tallas || [];
		var celdasIn = (bloque && bloque.celdas) || {};
		var baseOrig = Number(bloque && bloque.sug) || 0;
		var base = poolSugBloque(mz, bloque);
		var incOld = [];
		var incNew = [];
		colores.forEach(function (c) {
			if (prefs.off[c.cod]) {
				return;
			}
			if (c.nuevo) {
				incNew.push(c);
			} else {
				incOld.push(c);
			}
		});
		var manual = 0;
		incNew.forEach(function (c) {
			manual += udsColorNuevo(c, celdasIn, tallas, prefs);
		});
		var resto = base - manual;
		if (resto < 0) {
			resto = 0;
		}
		var pesosOld = {};
		incOld.forEach(function (c) {
			tallas.forEach(function (t) {
				var k = claveVariante(c.cod, t.cod);
				var cel = celdasIn[k];
				pesosOld[k] = cel ? Number(cel.hist) || 0 : 0;
			});
		});
		var repartoOld = repartirEnterosJs(resto, pesosOld);
		var pesoTalla = {};
		var sumaT = 0;
		tallas.forEach(function (t) {
			pesoTalla[t.cod] = 0;
			incOld.forEach(function (c) {
				pesoTalla[t.cod] += pesosOld[claveVariante(c.cod, t.cod)] || 0;
			});
			sumaT += pesoTalla[t.cod];
		});
		if (sumaT <= 0) {
			tallas.forEach(function (t) {
				pesoTalla[t.cod] = 1;
			});
		}
		var out = {};
		incOld.forEach(function (c) {
			tallas.forEach(function (t) {
				var k = claveVariante(c.cod, t.cod);
				var cel = celdasIn[k];
				out[k] = {
					hist: cel ? Number(cel.hist) || 0 : 0,
					sug: repartoOld[k] || 0
				};
			});
		});
		incNew.forEach(function (c) {
			var uds = udsColorNuevo(c, celdasIn, tallas, prefs);
			var pesosN = {};
			tallas.forEach(function (t) {
				pesosN[t.cod] = pesoTalla[t.cod];
			});
			var split = repartirEnterosJs(uds, pesosN);
			tallas.forEach(function (t) {
				out[claveVariante(c.cod, t.cod)] = {
					hist: 0,
					sug: split[t.cod] || 0
				};
			});
		});
		return { celdas: out, manual: manual, resto: resto, base: base, baseOrig: baseOrig };
	}

	function udsColorNuevo(c, celdasIn, tallas, prefs) {
		if (Object.prototype.hasOwnProperty.call(prefs.uds, c.cod)) {
			return parseInt(prefs.uds[c.cod], 10) || 0;
		}
		var s = 0;
		(tallas || []).forEach(function (t) {
			var cel = celdasIn[claveVariante(c.cod, t.cod)];
			s += cel ? Number(cel.sug) || 0 : 0;
		});
		return s;
	}

	function renderMatrizSugerencia(ctx) {
		var $tb = $("#tablaMatrizSug");
		if (!$tb.length) {
			return;
		}
		var mz = ctx && ctx.matriz_articulos;
		if (!mz || !(mz.colores || []).length || !(mz.tallas || []).length) {
			$("#proyMatrizSugLabel").text("Sugerencia por color × talla");
			$("#proyMatrizSugHint").text("");
			$tb.html('<tbody><tr><td class="text-muted">Sin artículos color/talla para este modelo.</td></tr></tbody>');
			return;
		}
		var bloque = null;
		var titulo = "Sugerencia por color × talla";
		if (decMesFoco && mz.por_mes && mz.por_mes[decMesFoco]) {
			bloque = mz.por_mes[decMesFoco];
			titulo = "Sugerencia · " + decMesFoco;
		} else if (mz.plan) {
			bloque = mz.plan;
			titulo = "Sugerencia · " + (mz.plan.mix_label || "plan");
		}
		$("#proyMatrizSugLabel").text(titulo);
		if (!bloque) {
			$("#proyMatrizSugHint").text("Sin mix histórico para repartir la sugerencia.");
			$tb.html('<tbody><tr><td class="text-muted">No hay ventas cerradas de ese mes para armar la curva.</td></tr></tbody>');
			return;
		}
		var prefs = matrizPrefs();
		var armado = armarCeldasMatriz(mz, bloque);
		var celdas = armado.celdas;
		var colores = (mz.colores || []).slice();
		var tallas = mz.tallas || [];
		colores.sort(function (a, b) {
			var ta = 0;
			var tbTot = 0;
			tallas.forEach(function (t) {
				var ca = celdas[claveVariante(a.cod, t.cod)];
				var cb = celdas[claveVariante(b.cod, t.cod)];
				ta += ca ? Number(ca.sug) || 0 : 0;
				tbTot += cb ? Number(cb.sug) || 0 : 0;
			});
			if (a.nuevo !== b.nuevo) {
				return a.nuevo ? 1 : -1;
			}
			return tbTot - ta;
		});
		var totTalla = {};
		var totColor = {};
		var tot = 0;
		colores.forEach(function (c) {
			totColor[c.cod] = 0;
			tallas.forEach(function (t) {
				if (totTalla[t.cod] == null) {
					totTalla[t.cod] = 0;
				}
				if (prefs.off[c.cod]) {
					return;
				}
				var cel = celdas[claveVariante(c.cod, t.cod)];
				var v = cel ? Number(cel.sug) || 0 : 0;
				totColor[c.cod] += v;
				totTalla[t.cod] += v;
				tot += v;
			});
		});
		var html = "<thead><tr><th>Color</th>";
		tallas.forEach(function (t) {
			html += "<th>" + proyEsc(t.nombre) + "</th>";
		});
		html += "<th>Total</th></tr></thead><tbody>";
		colores.forEach(function (c) {
			var on = !prefs.off[c.cod];
			var trCls = "proy-mx-row" + (on ? "" : " is-off") + (c.nuevo ? " is-nuevo" : "");
			html += '<tr class="' + trCls + '" data-cod="' + proyEsc(c.cod) + '">';
			html += '<th class="proy-mx-color">';
			html += '<label class="proy-mx-lab">';
			html += '<input type="checkbox" class="proy-mx-on" data-cod="' +
				proyEsc(c.cod) + '"' + (on ? " checked" : "") + ">";
			html += '<i style="background:' + proyColorVisual(c.nombre) + '"></i>';
			html += '<span>' + proyEsc(c.nombre) + "</span>";
			if (c.nuevo) {
				html += ' <span class="proy-mx-badge" title="Sin venta o muy poca vs el resto (hace 2–3 años)">nuevo</span>';
			}
			html += "</label></th>";
			tallas.forEach(function (t) {
				if (!on) {
					html += '<td class="text-muted">·</td>';
					return;
				}
				var cel = celdas[claveVariante(c.cod, t.cod)];
				var v = cel ? Number(cel.sug) || 0 : 0;
				html += '<td class="' + (v ? "proy-mx-num" : "text-muted") + '">' +
					(v ? num(v, 0) : "·") + "</td>";
			});
			if (on && c.nuevo) {
				html += '<td class="proy-mx-tot proy-mx-tot-edit">';
				html += '<input type="number" min="0" step="1" class="form-control input-sm proy-mx-uds" data-cod="' +
					proyEsc(c.cod) + '" value="' + udsColorNuevo(c, bloque.celdas || {}, tallas, prefs) + '">';
				html += "</td>";
			} else {
				html += '<td class="proy-mx-tot">' + (on ? num(totColor[c.cod], 0) : "·") + "</td>";
			}
			html += "</tr>";
		});
		html += '</tbody><tfoot><tr class="proy-tfoot"><td>Total</td>';
		tallas.forEach(function (t) {
			html += "<td>" + (totTalla[t.cod] ? num(totTalla[t.cod], 0) : "·") + "</td>";
		});
		html += "<td>" + num(tot, 0) + "</td></tr></tfoot>";
		$tb.html(html);
		var hint = "Reparto de " + num(armado.base, 0) + " uds entre colores tildados";
		if (armado.baseOrig && armado.base !== armado.baseOrig) {
			hint = "Sug. " + num(armado.base, 0) + " uds (original " + num(armado.baseOrig, 0) + ")";
			var offNombres = [];
			colores.forEach(function (c) {
				if (prefs.off[c.cod]) {
					offNombres.push(c.nombre);
				}
			});
			if (offNombres.length && offNombres.length <= 4) {
				hint += " · sin " + offNombres.join(", ");
			} else if (offNombres.length) {
				hint += " · sin " + offNombres.length + " colores";
			}
		}
		if (bloque.mix_label) {
			hint += " · mix " + bloque.mix_label;
		}
		hint += ". Nuevo = sin historia o muy poca venta vs el resto: editá el total. Clic en el color para tildar.";
		if (armado.manual) {
			hint += " Nuevos: " + num(armado.manual, 0) + " uds.";
		}
		$("#proyMatrizSugHint").text(hint);
	}

	function mixUdsPorColor(ctx) {
		var mz = ctx && ctx.matriz_articulos;
		var out = {};
		if (!mz) {
			return out;
		}
		var bloque = null;
		if (decMesFoco && mz.por_mes && mz.por_mes[decMesFoco]) {
			bloque = mz.por_mes[decMesFoco];
		} else {
			bloque = mz.plan;
		}
		var celdas = (bloque && bloque.celdas) || {};
		(mz.colores || []).forEach(function (c) {
			var s = 0;
			(mz.tallas || []).forEach(function (t) {
				var cel = celdas[claveVariante(c.cod, t.cod)];
				s += cel ? Number(cel.sug) || 0 : 0;
			});
			out[c.cod] = s;
		});
		return out;
	}

	function normColorTxt(valor) {
		return String(valor || "").toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
	}

	function colorMatrizOff(clave, prefs, coloresMz) {
		if (prefs.off[clave]) {
			return true;
		}
		var n = normColorTxt(clave);
		var i;
		for (i = 0; i < (coloresMz || []).length; i++) {
			var c = coloresMz[i];
			if (normColorTxt(c.cod) === n || normColorTxt(c.nombre) === n) {
				return !!prefs.off[c.cod];
			}
		}
		return false;
	}

	function mpTextoEsColorOff(it, prefs, coloresMz) {
		var textos = [normColorTxt(it.color), normColorTxt(it.descripcion)].join(" ");
		var i;
		for (i = 0; i < (coloresMz || []).length; i++) {
			var c = coloresMz[i];
			if (!prefs.off[c.cod]) {
				continue;
			}
			var nom = normColorTxt(c.nombre);
			if (nom.length >= 4 && textos.indexOf(nom) !== -1) {
				return true;
			}
		}
		return false;
	}

	function itemsMpSegunColores(ctx) {
		var prefs = matrizPrefs();
		var mix = mixUdsPorColor(ctx);
		var items = ((ctx && ctx.mp_riesgo) || {}).items || [];
		var coloresMz = ((ctx && ctx.matriz_articulos) || {}).colores || [];
		var visibles = [];
		if (!Object.keys(mix).length && !coloresMz.length) {
			return items;
		}
		items.forEach(function (it) {
			var cols = it.colores || [];
			var uds = 0;
			if (cols.length) {
				var alguno = false;
				cols.forEach(function (cod) {
					if (!colorMatrizOff(cod, prefs, coloresMz)) {
						alguno = true;
						uds += Number(mix[cod]) || 0;
					}
				});
				if (!alguno) {
					return;
				}
			} else if (mpTextoEsColorOff(it, prefs, coloresMz)) {
				return;
			} else {
				var algunoGen = false;
				Object.keys(mix).forEach(function (cod) {
					if (!prefs.off[cod]) {
						algunoGen = true;
						uds += Number(mix[cod]) || 0;
					}
				});
				if (!algunoGen) {
					return;
				}
			}
			var cons = Number(it.consumo) || 0;
			var req = cons > 0 ? cons * uds : 0;
			visibles.push($.extend({}, it, { requerido: Math.round(req * 10) / 10 }));
		});
		return visibles;
	}

	function renderMpRiesgo(ctx) {
		var $body = $("#proyMpRiesgoBody");
		var $hint = $("#proyMpRiesgoHint");
		if (!$body.length) {
			return;
		}
		var data = (ctx && ctx.mp_riesgo) || {};
		var items = itemsMpSegunColores(ctx).filter(function (it) {
			return it.estado === "unico" || it.estado === "poco";
		});
		var labels = {
			tela: "Telas",
			blonda: "Blondas",
			elastico: "Elásticos",
			sesgo: "Sesgos",
			tirante: "Tirantes"
		};
		var estLbl = {
			unico: "1 artículo",
			poco: "poca",
			ok: "compartida"
		};
		if (!items.length) {
			$hint.text(data.fuente
				? "Ninguna MP de la receta está en alerta: todas están compartidas en varios artículos."
				: "Este modelo no tiene receta publicada ni tarjeta con esas materias primas.");
			$body.empty();
			return;
		}
		var hint = "Solo MP en alerta (1 artículo o poca compartición)";
		if (data.fuente === "tarjeta") {
			hint += " · según la tarjeta";
		} else if (data.version) {
			hint += " · receta v" + data.version;
		}
		$hint.text(hint);
		var html = '<table class="table table-condensed proy-table" id="tablaMpRiesgo">';
		html += "<thead><tr><th>Tipo</th><th>Materia prima</th><th>Pide el plan</th><th>Artículos</th><th>Modelos</th><th></th></tr></thead><tbody>";
		items.forEach(function (it) {
			var und = it.unidad ? " " + it.unidad : "";
			var nom = (it.descripcion || it.mp) + (it.color ? " · " + it.color : "");
			html += '<tr class="proy-mp-row is-' + proyEsc(it.estado) + '">';
			html += "<td>" + proyEsc(labels[it.tipo] || it.tipo);
			if (it.tela_principal) {
				html += ' <span class="proy-mp-prin" title="Tela que direcciona el corte">principal</span>';
			}
			html += "</td>";
			html += "<td><strong>" + proyEsc(nom) + "</strong> <span class='text-muted'>" +
				proyEsc(it.mp) + "</span></td>";
			html += "<td>" + (it.requerido ? num(it.requerido, 1) + und : "·") + "</td>";
			html += "<td>" + num(it.articulos || 0, 0) + "</td>";
			html += "<td>" + num(it.modelos || 0, 0) + "</td>";
			html += '<td><span class="proy-mp-est">' + (estLbl[it.estado] || it.estado) + "</span></td>";
			html += "</tr>";
		});
		html += "</tbody></table>";
		$body.html(html);
	}

	function renderDashboard(stats, dash, plan) {
		if (!$("#proyDashCol").length) {
			return;
		}
		stats = stats || {};
		dash = dash || {};
		plan = plan || planActual || {};

		var proyectados = Number(stats.modelos_proyectados) || 0;
		var pendientes = Number(stats.modelos_pendientes) || 0;
		var activos = Number(stats.modelos_activos) || 0;
		var avance = Number(stats.avance_pct) || 0;
		var udsOfi = Number(stats.unidades_oficiales) || 0;
		var udsSug = Number(stats.unidades_sugeridas) || 0;
		var udsAj = Number(stats.unidades_ajustes) || 0;
		var borrador = Number(stats.lineas_borrador) || 0;
		var publicadas = Number(stats.lineas_publicadas) || 0;
		var cerradas = Number(stats.lineas_cerradas) || 0;
		var sin9 = Number(stats.lineas_sin_lista9) || 0;
		var desvio = Number(dash.lineas_desvio) || 0;
		var cero = Number(dash.modelos_cero) || 0;
		var umbral = Number(dash.umbral_pct) || 10;
		var fact = dash.factores || {};
		var factN = Number(fact.aplicados) || 0;
		var factMod = Number(fact.modelos) || 0;
		var factAj = Number(fact.ajuste) || 0;
		var catalogo = Number(dash.catalogo_activos) || 0;

		if (plan && plan.id) {
			$("#dashPlanTitulo").text((plan.nombre || ("Plan #" + plan.id)));
			$("#dashPlanMeta").text(
				nombreMesCorto(plan.mes_desde) + " " + plan.anio_desde +
				" → " + nombreMesCorto(plan.mes_hasta) + " " + plan.anio_hasta +
				" · " + (plan.estado || "")
			);
		}

		var alertas = [];
		if (pendientes > 0) {
			alertas.push(chipAlerta("is-warn", "fa-clock-o", num(pendientes, 0) + " modelos sin proyectar"));
		} else if (activos > 0) {
			alertas.push(chipAlerta("is-ok", "fa-check", "Todos los modelos activos tienen proyección"));
		}
		if (borrador > 0) {
			alertas.push(chipAlerta("is-warn", "fa-pencil", num(borrador, 0) + " líneas aún en borrador"));
		}
		if (sin9 > 0) {
			alertas.push(chipAlerta("is-danger", "fa-tag", num(sin9, 0) + " líneas sin lista 9"));
		}
		if (cero > 0) {
			alertas.push(chipAlerta("is-warn", "fa-ban", num(cero, 0) + " modelos en 0 unidades"));
		}
		if (desvio > 0) {
			alertas.push(chipAlerta(
				"is-warn",
				"fa-balance-scale",
				num(desvio, 0) + " líneas con desvío &gt; " + umbral + "% vs sug.+factores"
			));
		}
		if (factN > 0) {
			alertas.push(chipAlerta(
				"is-ok",
				"fa-sliders",
				num(factN, 0) + " factores en " + num(factMod, 0) + " modelos (" +
				(factAj > 0 ? "+" : "") + num(factAj, 0) + " uds)"
			));
		} else {
			alertas.push(chipAlerta("is-neutral", "fa-sliders", "Ningún factor aplicado todavía"));
		}
		$("#dashAlertas").html(alertas.join(""));

		var $tbMes = $("#dashTablaMeses tbody");
		$tbMes.empty();
		var meses = dash.meses || [];
		if (!meses.length) {
			$tbMes.append('<tr><td colspan="3" class="text-muted text-center">Todavía no hay meses cargados.</td></tr>');
		} else {
			meses.forEach(function (m) {
				var ofi = Number(m.unidades_oficiales) || 0;
				var sug = (Number(m.unidades_sugeridas) || 0) + (Number(m.unidades_ajustes) || 0);
				var tr = $("<tr>");
				tr.append($("<td>").text(nombreMesCorto(m.mes) + " " + m.anio));
				tr.append($("<td class='text-right'>").text(num(ofi, 0)));
				tr.append($("<td class='text-right'>").html(deltaTxt(ofi, sug)));
				$tbMes.append(tr);
			});
		}

		var marcas = {};
		(modelosProyectadosCache || []).forEach(function (m) {
			var k = m.marca || "Sin marca";
			if (!marcas[k]) {
				marcas[k] = { marca: k, proy: 0, pend: 0, uds: 0 };
			}
			marcas[k].proy += 1;
			marcas[k].uds += Number(m.unidades_oficiales) || 0;
		});
		(modelosPendientesCache || []).forEach(function (m) {
			var k = m.marca || "Sin marca";
			if (!marcas[k]) {
				marcas[k] = { marca: k, proy: 0, pend: 0, uds: 0 };
			}
			marcas[k].pend += 1;
		});
		var marcasArr = Object.keys(marcas).map(function (k) { return marcas[k]; });
		marcasArr.sort(function (a, b) {
			return (b.uds - a.uds) || (b.proy - a.proy) || a.marca.localeCompare(b.marca, "es");
		});
		var $tbMar = $("#dashTablaMarcas tbody");
		$tbMar.empty();
		if (!marcasArr.length) {
			$tbMar.append('<tr><td colspan="4" class="text-muted text-center">—</td></tr>');
		} else {
			marcasArr.slice(0, 12).forEach(function (m) {
				var tr = $("<tr>");
				tr.append($("<td>").text(m.marca));
				tr.append($("<td class='text-right'>").text(num(m.proy, 0)));
				tr.append($("<td class='text-right'>").text(num(m.pend, 0)));
				tr.append($("<td class='text-right'>").text(num(m.uds, 0)));
				$tbMar.append(tr);
			});
		}

		var cfg = [];
		cfg.push("<li><em>Sugerencia</em> promedio del mismo mes en los 3 años previos, redondeado a 10.</li>");
		cfg.push("<li><em>Oficial</em> es la cifra que confirman. Si se desvía más de " + umbral + "% vs sug.+factores, pide motivo.</li>");
		cfg.push("<li><em>Factores</em> " + num(catalogo, 0) + " activos en el catálogo" +
			(factN ? (" · " + num(factN, 0) + " aplicados en este plan") : "") +
			'. <a href="index.php?ruta=proyeccion-comercial-factores">Configurar</a></li>');
		cfg.push("<li><em>Publicar</em> congela lista 9 y deja la cifra lista para producción/logística.</li>");
		$("#dashConfig").html(cfg.join(""));
	}

	function renderSelectProyectados(rows) {
		modelosProyectadosCache = rows || [];
		renderListaModelos();
	}

	function renderSelectPendientes(rows) {
		modelosPendientesCache = rows || [];
		renderListaModelos();
	}

	function cargarPendientesYStats() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return $.Deferred().resolve().promise();
		}
		return post("modelosPendientes", {
			id_periodo: id,
			id_marca: $("#proyMarca").val() || 0,
			q: $.trim($("#proyQ").val() || "")
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				return;
			}
			dashboardCache = resp.dashboard || null;
			modelosProyectadosCache = resp.modelos_proyectados || [];
			modelosPendientesCache = resp.modelos || [];
			renderStats(resp.stats || {});
			renderListaModelos();
		});
	}

	function initFechas() {
		var desde = ymActual();
		$("#planDesde").val(desde);
		$("#planHasta").val(ymMasMeses(desde, 5));
		var ahora = new Date();
		$("#proyConcAnio").val(ahora.getFullYear());
		$("#proyConcMes").val(Math.max(1, ahora.getMonth()));
	}

	function cargarCatalogo() {
		return post("catalogo", {}).done(function (resp) {
			if (!resp || !resp.ok) {
				return;
			}
			var $marca = $("#proyMarca");
			$marca.find("option:not([value='0'])").remove();
			(resp.marcas || []).forEach(function (m) {
				$marca.append($("<option>").val(m.id).text(m.marca));
			});
		});
	}

	function cargarTiposFactor() {
		return post("tiposFactor", {}).done(function (resp) {
			if (!resp || !resp.ok) {
				return;
			}
			tiposFactorCache = resp.tipos || [];
			["#factorTipo", "#catTipo"].forEach(function (sel) {
				var $sel = $(sel);
				if (!$sel.length) {
					return;
				}
				$sel.empty();
				tiposFactorCache.forEach(function (t) {
					$sel.append($("<option>").val(t.codigo).text(t.nombre));
				});
			});
		});
	}

	function tipoNombre(codigo) {
		var n = codigo;
		tiposFactorCache.forEach(function (t) {
			if (t.codigo === codigo) {
				n = t.nombre;
			}
		});
		return n;
	}

	function cargarCatalogoFactores() {
		return post("listarCatalogo", { todos: 1 }).done(function (resp) {
			if (!resp || !resp.ok) {
				$("#tablaCatalogo tbody").html(
					'<tr><td colspan="6" class="text-danger text-center">' +
					((resp && resp.mensaje) || "¿Ejecutaste el SQL del catálogo?") + "</td></tr>"
				);
				return;
			}
			if (resp.tipos && resp.tipos.length) {
				tiposFactorCache = resp.tipos;
				["#factorTipo", "#catTipo"].forEach(function (sel) {
					var $sel = $(sel);
					if (!$sel.length) {
						return;
					}
					$sel.empty();
					tiposFactorCache.forEach(function (t) {
						$sel.append($("<option>").val(t.codigo).text(t.nombre));
					});
				});
			}
			catalogoFactoresCache = resp.catalogo || [];
			renderTablaCatalogo(catalogoFactoresCache);
		}).fail(function () {
			$("#tablaCatalogo tbody").html(
				'<tr><td colspan="6" class="text-danger text-center">Error de red / SQL catálogo pendiente</td></tr>'
			);
		});
	}

	function renderTablaCatalogo(rows) {
		var $tb = $("#tablaCatalogo tbody");
		$tb.empty();
		var activos = (rows || []).filter(function (r) {
			return String(r.activo) === "1" || r.activo === 1 || r.activo === true;
		});
		if (!activos.length) {
			$tb.append('<tr><td colspan="6" class="text-muted text-center">Sin factores. Crea el primero abajo.</td></tr>');
			return;
		}
		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		activos.forEach(function (c) {
			var tr = $("<tr>");
			tr.append($("<td>").text(tipoNombre(c.tipo)));
			tr.append($("<td>").text(c.titulo));
			tr.append($("<td>").text(num(c.ajuste_unidades_default, 0)));
			tr.append($("<td>").text(c.impacto_pct_default == null ? "—" : num(c.impacto_pct_default, 2) + "%"));
			tr.append($("<td>").text(c.descripcion || "—"));
			var acc = "—";
			if (puedeEditar) {
				acc =
					'<button type="button" class="btn btn-xs btn-default btnEditarCatalogo" data-id="' + c.id + '">Editar</button> ' +
					'<button type="button" class="btn btn-xs btn-danger btnDesactivarCatalogo" data-id="' + c.id + '">Quitar</button>';
			}
			tr.append($("<td>").html(acc));
			$tb.append(tr);
		});
	}

	function limpiarFormCatalogo() {
		$("#catId").val("");
		$("#catTitulo").val("");
		$("#catAjuste").val("0");
		$("#catPct").val("");
		$("#catDesc").val("");
		if (tiposFactorCache.length) {
			$("#catTipo").val(tiposFactorCache[0].codigo);
		}
	}

	function guardarCatalogoFactor() {
		var data = {
			id: $("#catId").val() || 0,
			tipo: $("#catTipo").val(),
			titulo: $("#catTitulo").val(),
			descripcion: $("#catDesc").val(),
			ajuste_unidades_default: $("#catAjuste").val(),
			impacto_pct_default: $("#catPct").val()
		};
		post("guardarCatalogo", data).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo guardar el factor");
				return;
			}
			catalogoFactoresCache = resp.catalogo || [];
			renderTablaCatalogo(catalogoFactoresCache);
			limpiarFormCatalogo();
			if (espacioActual) {
				espacioActual.catalogo_factores = (catalogoFactoresCache || []).filter(function (c) {
					return String(c.activo) === "1" || c.activo === 1 || c.activo === true;
				});
				cargarChecksMesActual();
			}
		});
	}

	function desactivarCatalogoFactor(id) {
		proyConfirm("Quitar factor", "¿Quitar este factor del catálogo?").then(function (ok) {
			if (!ok) {
				return;
			}
			post("desactivarCatalogo", { id: id }).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo quitar", "error");
					return;
				}
				catalogoFactoresCache = resp.catalogo || [];
				renderTablaCatalogo(catalogoFactoresCache);
				cargarChecksMesActual();
			});
		});
	}

	function renderPlanes(planes) {
		var $tb = $("#tablaPlanes tbody");
		$tb.empty();
		if (!planes || !planes.length) {
			$tb.append('<tr><td colspan="6" class="text-center text-muted">Aún no hay planes. Crea uno.</td></tr>');
			return;
		}
		planes.forEach(function (p) {
			var rango = pad2(p.mes_desde) + "/" + p.anio_desde + " → " + pad2(p.mes_hasta) + "/" + p.anio_hasta;
			var tr = $("<tr>");
			if (planActual && String(planActual.id) === String(p.id)) {
				tr.addClass("proy-plan-activo");
			}
			tr.append($("<td>").text(p.id));
			tr.append($("<td>").text(p.nombre || "—"));
			tr.append($("<td>").text(rango));
			tr.append($("<td>").html('<span class="label label-default">' + p.estado + "</span>"));
			tr.append($("<td>").text((p.lineas_borrador || 0) + " borr. / " + (p.lineas_publicadas || 0) + " pub. / " + (p.total_lineas || 0)));
			tr.append(
				$("<td>").html(
					'<button type="button" class="btn btn-xs btn-primary btnAbrirPlan" data-id="' + p.id + '">Ingresar</button> ' +
					'<a class="btn btn-xs btn-default" href="index.php?ruta=proyeccion-comercial-masiva&plan=' +
						encodeURIComponent(p.id) + '" title="Grilla de todos los modelos">Masiva</a> ' +
					(puedeEditarPlanes
						? '<button type="button" class="btn btn-xs btn-danger btnEliminarPlan" data-id="' + p.id + '" data-nombre="' +
							$("<div>").text(p.nombre || ("#" + p.id)).html() + '">Eliminar</button>'
						: "")
				)
			);
			$tb.append(tr);
		});
	}

	function listarPlanes() {
		return post("listarPlanes", {}).done(function (resp) {
			if (!resp || !resp.ok) {
				$("#tablaPlanes tbody").html(
					'<tr><td colspan="6" class="text-danger text-center">' +
					((resp && resp.mensaje) || "Error") + "</td></tr>"
				);
				return;
			}
			renderPlanes(resp.planes);
		}).fail(function () {
			$("#tablaPlanes tbody").html(
				'<tr><td colspan="6" class="text-danger text-center">Error de red al listar planes</td></tr>'
			);
		});
	}

	function llenarFiltroMeses(plan) {
		var $sel = $("#filtroMesPlan");
		$sel.find("option:not([value=''])").remove();
		if (!plan) {
			return;
		}
		var anio = parseInt(plan.anio_desde, 10);
		var mes = parseInt(plan.mes_desde, 10);
		var hastaClave = parseInt(plan.anio_hasta, 10) * 100 + parseInt(plan.mes_hasta, 10);
		while ((anio * 100 + mes) <= hastaClave) {
			var val = anio + "-" + pad2(mes);
			$sel.append($("<option>").val(val).text(val));
			mes++;
			if (mes > 12) {
				mes = 1;
				anio++;
			}
		}
	}

	function renderLineas(lineas) {
		lineasCache = lineas || [];
		var $tb = $("#tablaLineasPlan tbody");
		$tb.empty();
		var borr = 0;
		var pub = 0;
		var sin9 = 0;
		lineasCache.forEach(function (l) {
			if (l.estado_linea === "BORRADOR") {
				borr++;
			}
			if (l.estado_linea === "PUBLICADO" || l.estado_linea === "CERRADO") {
				pub++;
			}
			if (l.sin_lista9) {
				sin9++;
			}
		});
		$("#proyKpiLineas").text(lineasCache.length);
		$("#proyKpiBorrador").text(borr);
		$("#proyKpiPublicado").text(pub);
		$("#proyKpiSinLista9").text(sin9);

		if (!lineasCache.length) {
			$tb.append('<tr><td colspan="13" class="text-center text-muted">Sin líneas en la grilla.</td></tr>');
			return;
		}

		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		lineasCache.forEach(function (l) {
			var editable = puedeEditar && l.estado_linea !== "CERRADO";
			var precioTxt = l.precio_lista9_snapshot !== null && l.precio_lista9_snapshot !== undefined
				? moneda(l.precio_lista9_snapshot)
				: (l.sin_lista9
					? '<span class="label label-danger">Sin lista 9</span>'
					: moneda(l.precio_lista9_vigente) + " <small>(vigente)</small>");
			var input = editable
				? '<input type="number" min="0" step="1" class="form-control input-sm inpOficial" data-id="' +
					l.id + '" value="' + l.unidades_oficiales + '" style="width:90px;">'
				: String(l.unidades_oficiales);
			var ajustesTxt = num(l.unidades_ajustes, 0);
			if (l.n_factores) {
				ajustesTxt += ' <span class="label label-info">' + l.n_factores + "</span>";
			}
			var tr = $("<tr>");
			tr.append($("<td>").text(l.periodo));
			tr.append($("<td>").text(l.modelo + (l.nombre ? " — " + l.nombre : "")));
			tr.append($("<td>").text(l.marca || "—"));
			tr.append($("<td>").text(num(l.unidades_sugeridas, 0)));
			tr.append($("<td>").html(ajustesTxt));
			tr.append($("<td>").html(input));
			tr.append($("<td>").html(precioTxt));
			tr.append($("<td>").text(l.importe_lista9_proyectado === null ? "—" : moneda(l.importe_lista9_proyectado)));
			tr.append($("<td>").text(num(l.inventario && l.inventario.stock_disponible, 0)));
			tr.append($("<td>").text(num(l.inventario && l.inventario.en_proceso, 0)));
			tr.append($("<td>").text(num(l.brecha_referencial, 0)));
			tr.append($("<td>").html('<span class="label label-default">' + l.estado_linea + "</span>"));
			tr.append(
				$("<td>").html(
					'<button type="button" class="btn btn-xs btn-success btnFactores" data-id="' + l.id + '">Factores</button> ' +
					'<button type="button" class="btn btn-xs btn-primary btnIrModelo" data-modelo="' +
					$("<div>").text(l.modelo).html() + '">Modelo</button>'
				)
			);
			$tb.append(tr);
		});
	}

	function abrirPlan(id, opts) {
		opts = opts || {};
		$("#proyIdPeriodo").val(id);
		mostrarCargaBarra(opts.modelo ? "Abriendo el plan y el modelo…" : "Cargando el plan…");
		if (opts.modelo) {
			modeloSeleccionado = opts.modelo;
			mostrarCargaModelo(opts.modelo);
		}
		var data = { id_periodo: id, q: $("#proyQ").val() || "" };
		var mesSel = $("#filtroMesPlan").val();
		if (mesSel) {
			var p = mesSel.split("-");
			data.anio = parseInt(p[0], 10);
			data.mes = parseInt(p[1], 10);
		}
		return post("cargarPlan", data).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo abrir el plan");
				return;
			}
			planActual = resp.plan;
			mostrarPlan(planActual);
			llenarFiltroMeses(planActual);
			if (mesSel) {
				$("#filtroMesPlan").val(mesSel);
			}
			dashboardCache = resp.dashboard || null;
			modelosProyectadosCache = resp.modelos_proyectados || [];
			renderStats(resp.stats || {});
			renderSelectProyectados(resp.modelos_proyectados || []);
			cargarPendientesYStats();
			if ($("#tablaLineasPlan").length) {
				renderLineas(resp.lineas);
			}
			if (opts.modelo) {
				modeloSeleccionado = opts.modelo;
				cargarEspacioModelo(!!opts.asegurar);
			}
		}).fail(function (xhr, status) {
			ocultarCargaModelo();
			$("#panelModeloVacio").show();
			proyAlert(status === "timeout"
				? "La carga del plan tardó demasiado."
				: "Error de red al cargar el plan");
		}).always(function () {
			if (!opts.modelo) {
				ocultarCargaBarra();
			}
		});
	}

	function crearPlan() {
		post("crearPlan", {
			desde: $("#planDesde").val(),
			hasta: $("#planHasta").val(),
			nombre: $("#planNombre").val()
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo crear");
				return;
			}
			listarPlanes().done(function () {
				abrirPlan(resp.id_periodo);
			});
		}).fail(function () {
			proyAlert("Error de red al crear el plan");
		});
	}

	function eliminarPlan(id, nombre) {
		proyConfirm(
			"Eliminar plan",
			'¿Eliminar el plan "' + nombre + '"? Solo se permite si no tiene líneas publicadas/cerradas.'
		).then(function (ok) {
			if (!ok) {
				return;
			}
			post("eliminarPlan", { id_periodo: id }).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo eliminar", "error");
					return;
				}
				if (String($("#proyIdPeriodo").val()) === String(id)) {
					mostrarListado();
				}
				proyAlert(resp.mensaje || "Plan eliminado", "success");
				listarPlanes();
			}).fail(function () {
				proyAlert("Error de red al eliminar", "error");
			});
		});
	}

	function buscarModelosLive() {
		var q = $.trim($("#modeloBuscar").val() || "");
		var idMarca = $("#proyMarca").val() || 0;
		if (q.length < 1 && parseInt(idMarca, 10) <= 0) {
			$("#modeloSugerencias").hide().empty();
			return;
		}
		post("buscarModelos", { q: q, id_marca: idMarca }).done(function (resp) {
			var $box = $("#modeloSugerencias").empty();
			if (!resp || !resp.ok || !(resp.modelos || []).length) {
				$box.hide();
				return;
			}
			(resp.modelos || []).forEach(function (m) {
				var item = $("<a href='#' class='list-group-item proy-sug-item'></a>");
				item.text(m.modelo + " — " + (m.nombre || "") + (m.marca ? " (" + m.marca + ")" : ""));
				item.data("modelo", m.modelo);
				$box.append(item);
			});
			$box.show();
		});
	}

	function histUds(detalle, offset) {
		var found = null;
		(detalle || []).forEach(function (h) {
			if (Number(h.offset) === offset) {
				found = h;
			}
		});
		if (!found) {
			return "—";
		}
		if (found.sin_tabla) {
			return '<span class="text-muted">n/d</span>';
		}
		if (found.periodo_abierto) {
			if (found.unidades === null || found.unidades === undefined) {
				return '<span class="proy-hist-parcial" title="Mes abierto · no entra al cálculo">—</span>';
			}
			return '<span class="proy-hist-parcial" title="Mes abierto · no entra al cálculo">' +
				num(found.unidades, 0) + "</span>";
		}
		if (found.unidades === null || found.unidades === undefined) {
			return "—";
		}
		return num(found.unidades, 0);
	}

	/** Historial legible: una línea por año (año · unidades). */
	function histStack(detalle) {
		var rows = (detalle || []).slice().sort(function (a, b) {
			return Number(a.offset) - Number(b.offset);
		});
		if (!rows.length) {
			return "—";
		}
		return '<div class="proy-hist-stack">' + rows.map(function (h) {
			var uds;
			var cls = "proy-hist-row";
			var title = h.periodo || "";
			if (h.sin_tabla) {
				uds = "n/d";
				cls += " proy-hist-parcial";
				title = "Sin tabla de movimientos";
			} else if (h.periodo_abierto) {
				uds = (h.unidades == null ? "—" : num(h.unidades, 0));
				cls += " proy-hist-parcial";
				title = "Mes abierto · no entra al cálculo";
			} else if (h.unidades == null) {
				uds = "—";
			} else {
				uds = num(h.unidades, 0);
			}
			return '<div class="' + cls + '" title="' + title + '">' +
				'<span class="proy-hist-anio">' + h.anio + "</span>" +
				'<span class="proy-hist-uds">' + uds + "</span></div>";
		}).join("") + "</div>";
	}

	function fmtDelta(actual, anterior, etiqueta, compacto) {
		if (actual == null || anterior == null || !isFinite(actual) || !isFinite(anterior)) {
			return compacto ? "" : ('<span class="text-muted">' +
				(etiqueta ? '<small class="proy-delta-lbl">' + etiqueta + "</small> " : "") +
				"—</span>");
		}
		var d = Number(actual) - Number(anterior);
		var pct = Number(anterior) !== 0 ? (d / Math.abs(Number(anterior))) * 100 : null;
		var cls = d > 0 ? "proy-delta-up" : (d < 0 ? "proy-delta-down" : "proy-delta-flat");
		var arrow = d > 0 ? "▲" : (d < 0 ? "▼" : "•");
		if (compacto) {
			if (pct == null) {
				return '<span class="' + cls + '">' + arrow + "</span>";
			}
			return '<span class="' + cls + '">' + arrow + " " +
				(pct > 0 ? "+" : "") + pct.toFixed(0) + "%</span>";
		}
		var pctTxt = pct == null ? "" : " (" + (pct > 0 ? "+" : "") + pct.toFixed(0) + "%)";
		return '<span class="' + cls + '">' +
			(etiqueta ? '<small class="proy-delta-lbl">' + etiqueta + "</small> " : "") +
			arrow + " " + (d > 0 ? "+" : "") + num(d, 0) + pctTxt + "</span>";
	}

	function histValorNum(detalle, offset) {
		var found = histItem(detalle, offset);
		if (!found || found.sin_tabla || found.unidades === null || found.unidades === undefined) {
			return null;
		}
		if (found.periodo_abierto) {
			return null;
		}
		return Number(found.unidades);
	}

	function histItem(detalle, offset) {
		var found = null;
		(detalle || []).forEach(function (h) {
			if (Number(h.offset) === offset) {
				found = h;
			}
		});
		return found;
	}

	function histEsCerrado(detalle, offset) {
		var h = histItem(detalle, offset);
		return !!(h && !h.sin_tabla && !h.periodo_abierto);
	}

	function offsetPeriodoCompleto(meses, est, offset) {
		if (!meses || !meses.length) {
			return false;
		}
		var i;
		for (i = 0; i < meses.length; i++) {
			var f = est[meses[i].periodo];
			if (!histEsCerrado(f ? f.historial : [], offset)) {
				return false;
			}
		}
		return true;
	}

	function totOffsetPeriodo(meses, est, offset) {
		var tot = 0;
		(meses || []).forEach(function (m) {
			var f = est[m.periodo];
			var v = histValorCerrado(f ? f.historial : [], offset);
			tot += v == null ? 0 : v;
		});
		return tot;
	}

	function rangoMesesPlan(meses, largo) {
		return txtRangoMeses((meses || []).map(function (m) {
			var mes = m.mes != null ? m.mes : mesDesdePeriodo(m.periodo);
			return largo ? nombreMesLargo(mes) : nombreMesCorto(mes);
		}));
	}

	function histValorCerrado(detalle, offset) {
		var h = histItem(detalle, offset);
		if (!h || h.sin_tabla || h.periodo_abierto) {
			return null;
		}
		if (h.unidades === null || h.unidades === undefined) {
			return 0;
		}
		return Number(h.unidades);
	}

	function histValorEnCurso(detalle, offset) {
		var h = histItem(detalle, offset);
		if (!h || h.sin_tabla || !h.periodo_abierto) {
			return null;
		}
		if (h.unidades === null || h.unidades === undefined) {
			return null;
		}
		return Number(h.unidades);
	}

	function histEstadoH1(detalle) {
		var h = histItem(detalle, 1);
		if (!h || h.sin_tabla) {
			return "nd";
		}
		if (!h.periodo_abierto) {
			return "cerrado";
		}
		if (h.unidades !== null && h.unidades !== undefined) {
			return "en_curso";
		}
		return "pendiente";
	}

	function histAnio(detalle, offset) {
		var h = histItem(detalle, offset);
		return h && h.anio != null ? Number(h.anio) : null;
	}

	function txtRangoMeses(nombres) {
		if (!nombres || !nombres.length) {
			return "";
		}
		var first = String(nombres[0]).toLowerCase();
		var last = String(nombres[nombres.length - 1]).toLowerCase();
		return nombres.length === 1 ? first : first + "–" + last;
	}

	function htmlPctLine(actual, anterior, vsLabel) {
		if (actual == null || anterior == null || !isFinite(actual) || !isFinite(anterior)) {
			return "";
		}
		var pct = pctCambio(actual, anterior);
		if (pct == null) {
			return "";
		}
		var cls = "proy-hist-pct";
		if (pct > 0.5) {
			cls += " proy-delta-up";
		} else if (pct < -0.5) {
			cls += " proy-delta-down";
		} else {
			cls += " proy-delta-flat";
		}
		var arrow = pct > 0.5 ? "▲" : (pct < -0.5 ? "▼" : "•");
		var vs = vsLabel ? " vs " + vsLabel : "";
		return '<span class="' + cls + '">' + arrow + " " +
			(pct > 0 ? "+" : "") + pct.toFixed(0) + "%" + vs + "</span>";
	}

	function htmlThAnio(anio, nota) {
		if (!anio) {
			return nota || "—";
		}
		return String(anio) + (nota ? "<small>" + nota + "</small>" : "");
	}

	function anioDesdePlan(offset, anioPlan) {
		var base = Number(anioPlan) || 0;
		if (!base && planActual) {
			base = Number(planActual.anio_desde) || 0;
		}
		if (!base) {
			return null;
		}
		return base - Number(offset);
	}

	function etiquetaAnio(offset, anioHist, anioPlan) {
		var a = anioHist || anioDesdePlan(offset, anioPlan);
		return a ? String(a) : "—";
	}

	function pintarCabeceraHist(anioPlan, h1Completo) {
		var a1 = anioDesdePlan(1, anioPlan);
		var a2 = anioDesdePlan(2, anioPlan);
		var a3 = anioDesdePlan(3, anioPlan);
		$("#thHist1").html(htmlThAnio(a1, h1Completo === false ? "aún no cierra" : ""));
		$("#thHist2").html(htmlThAnio(a2, ""));
		$("#thHist3").html(htmlThAnio(a3, ""));
		$("#thHistSug").html("Sugerencia" + (anioPlan ? "<small>" + anioPlan + "</small>" : ""));
	}

	/** Unidades arriba; el % vs el año de al lado, debajo y en chico. */
	function histCellVs(detalle, offset, offsetPrev) {
		var udsHtml = histUds(detalle, offset);
		if (offsetPrev == null) {
			return udsHtml;
		}
		var actual = histValorNum(detalle, offset);
		var prev = histValorNum(detalle, offsetPrev);
		var anioPrev = histAnio(detalle, offsetPrev) || anioDesdePlan(offsetPrev);
		var pctHtml = htmlPctLine(actual, prev, anioPrev ? String(anioPrev) : "");
		if (!pctHtml) {
			return udsHtml;
		}
		return '<div class="proy-hist-cell">' +
			'<span class="proy-hist-num">' + udsHtml + "</span>" +
			pctHtml +
			"</div>";
	}

	function mapaEstacional(ctx) {
		var map = {};
		var filas = (ctx && ctx.historial_estacional && ctx.historial_estacional.filas) || [];
		filas.forEach(function (f) {
			map[f.periodo] = f;
		});
		return map;
	}

	function renderHistEstacional(ctx, lineas) {
		var $tb = $("#tablaHistEstacional tbody");
		var $tf = $("#tablaHistEstacionalFoot");
		$tb.empty();
		$tf.empty();
		var filas = (ctx && ctx.historial_estacional && ctx.historial_estacional.filas) || [];
		if (!filas.length) {
			$("#lblTendenciaHist").text("");
			$("#lblGlobalHist").text("");
			pintarCabeceraHist(planActual && planActual.anio_desde, null);
			$tb.append('<tr><td colspan="7" class="text-muted text-center">Sin historial estacional</td></tr>');
			renderMatrizSugerencia(ctx);
			renderMpRiesgo(ctx);
			return;
		}
		var prevSug = null;
		var firstSug = null;
		var lastSug = null;
		var totSug = 0;
		var nSug = 0;
		var totProm = 0;
		var nProm = 0;
		var anioH1 = null;
		var anioH2 = null;
		var anioH3 = null;
		var mesesHist = filas.map(function (f) {
			return {
				periodo: f.periodo,
				mes: f.mes != null ? f.mes : mesDesdePeriodo(f.periodo),
				anio: f.anio
			};
		});
		var estHist = mapaEstacional(ctx);
		var h1Ok = offsetPeriodoCompleto(mesesHist, estHist, 1);
		var h2Ok = offsetPeriodoCompleto(mesesHist, estHist, 2);
		var h3Ok = offsetPeriodoCompleto(mesesHist, estHist, 3);
		var totH1Per = h1Ok ? totOffsetPeriodo(mesesHist, estHist, 1) : null;
		var totH2Per = h2Ok ? totOffsetPeriodo(mesesHist, estHist, 2) : null;
		var totH3Per = h3Ok ? totOffsetPeriodo(mesesHist, estHist, 3) : null;
		var anioPlan = filas[0].anio != null ? Number(filas[0].anio) : anioDesdePeriodo(filas[0].periodo);
		if (!anioPlan && planActual) {
			anioPlan = Number(planActual.anio_desde) || 0;
		}
		anioH1 = histAnio(filas[0].historial, 1) || anioDesdePlan(1, anioPlan);
		anioH2 = histAnio(filas[0].historial, 2) || anioDesdePlan(2, anioPlan);
		anioH3 = histAnio(filas[0].historial, 3) || anioDesdePlan(3, anioPlan);
		var rangoLargo = rangoMesesPlan(mesesHist, true) || "el periodo";
		pintarCabeceraHist(anioPlan, h1Ok);

		filas.forEach(function (f) {
			var sug = f.sugerencia || {};
			var sugCalc = sug.unidades != null ? Number(sug.unidades) : null;
			var mesNom = nombreMesLargo(f.mes != null ? f.mes : mesDesdePeriodo(f.periodo));
			var sugInner = sugCalc != null
				? '<strong class="proy-hist-sug">' + num(sugCalc, 0) + "</strong>"
				: "—";
			if (sug.sin_historia) {
				sugInner += '<span class="text-muted proy-hist-pct">sin historial</span>';
			}
			var sugTxt = sugCalc != null
				? '<div class="proy-hist-cell">' + sugInner + "</div>"
				: sugInner;
			var c1 = histCellVs(f.historial, 1, 2);
			var c2 = histCellVs(f.historial, 2, 3);
			var c3 = histCellVs(f.historial, 3, null);
			var vsMes = htmlPctLine(sugCalc, prevSug, "");
			$tb.append(
				'<tr class="proy-hist-mes' + (decMesFoco === f.periodo ? " is-foco" : "") +
				'" data-periodo="' + f.periodo + '" title="Clic para ver este mes a la derecha">' +
				'<td class="proy-hist-mes-nom"><strong>' + mesNom + "</strong></td>" +
				"<td>" + c1 + "</td>" +
				"<td>" + c2 + "</td>" +
				"<td>" + c3 + "</td>" +
				"<td>" + (sug.promedio_simple != null ? num(sug.promedio_simple, 0) : "—") + "</td>" +
				'<td class="proy-td-sug">' + sugTxt + "</td>" +
				"<td>" + (vsMes || "—") + "</td></tr>"
			);
			if (sugCalc != null) {
				if (firstSug == null) {
					firstSug = sugCalc;
				}
				lastSug = sugCalc;
				prevSug = sugCalc;
				totSug += sugCalc;
				nSug++;
			}
			if (sug.promedio_simple != null) {
				totProm += Number(sug.promedio_simple);
				nProm++;
			}
		});

		var totRef = h1Ok ? totH1Per : totH2Per;
		var anioRef = h1Ok ? anioH1 : anioH2;
		var pieH1 = h1Ok
			? "<strong>" + num(totH1Per, 0) + "</strong>"
			: '<span class="text-muted">aún no cierra</span>';
		var pieH2 = h2Ok
			? '<div class="proy-hist-cell"><strong>' + num(totH2Per, 0) + "</strong>" +
				(h3Ok ? htmlPctLine(totH2Per, totH3Per, anioH3 ? String(anioH3) : "") : "") +
				"</div>"
			: "—";
		var pieSug = nSug
			? '<div class="proy-hist-cell"><strong class="proy-hist-sug">' + num(totSug, 0) + "</strong>" +
				(totRef != null ? htmlPctLine(totSug, totRef, anioRef ? String(anioRef) : "") : "") +
				"</div>"
			: "—";
		$tf.html(
			"<tr class='proy-tfoot'>" +
			"<td><strong>Total " + rangoLargo + "</strong></td>" +
			"<td>" + pieH1 + "</td>" +
			"<td>" + pieH2 + "</td>" +
			"<td><strong>" + (h3Ok ? num(totH3Per, 0) : "—") + "</strong></td>" +
			"<td>" + (nProm ? num(totProm, 0) : "—") + "</td>" +
			'<td class="proy-td-sug">' + pieSug + "</td>" +
			"<td></td>" +
			"</tr>"
		);

		if (firstSug != null && lastSug != null && filas.length > 1) {
			var dTot = lastSug - firstSug;
			var pctTot = firstSug !== 0 ? (dTot / Math.abs(firstSug)) * 100 : null;
			var primerMes = nombreMesLargo(mesesHist[0].mes);
			var ultimoMes = nombreMesLargo(mesesHist[mesesHist.length - 1].mes);
			var sentido = dTot > 0 ? "sube" : (dTot < 0 ? "baja" : "se mantiene");
			var pctPart = pctTot == null
				? ""
				: " (" + Math.abs(pctTot).toFixed(0) + "%)";
			$("#lblTendenciaHist").html(
				"De " + primerMes.toLowerCase() + " a " + ultimoMes.toLowerCase() +
				" la sugerencia <strong>" + sentido + "</strong> de " +
				num(firstSug, 0) + " a " + num(lastSug, 0) + " unidades" + pctPart + "."
			);
		} else {
			$("#lblTendenciaHist").text("");
		}

		if (nSug) {
			var vsAnioTxt = "";
			if (totRef != null) {
				var dAnio = totSug - totRef;
				var pctAnio = totRef !== 0 ? (dAnio / Math.abs(totRef)) * 100 : null;
				var anioTxt = anioRef ? String(anioRef) : "el último año cerrado";
				if (pctAnio == null || Math.abs(dAnio) < 0.5) {
					vsAnioTxt = ", casi igual que en " + rangoLargo + " " + anioTxt;
				} else {
					vsAnioTxt = ", un <strong>" + Math.abs(pctAnio).toFixed(0) + "% " +
						(dAnio > 0 ? "más" : "menos") + "</strong> que en " +
						rangoLargo + " " + anioTxt;
				}
			}
			var abiertoTxt = h1Ok
				? ""
				: " " + (anioH1 ? anioH1 : "El año anterior") +
					" no se compara porque todavía no cerró.";
			$("#lblGlobalHist").html(
				"En total se sugieren <strong>" + num(totSug, 0) + "</strong> unidades" +
				vsAnioTxt + "." + abiertoTxt
			);
		} else {
			$("#lblGlobalHist").text("");
		}
		renderMatrizSugerencia(ctx);
		renderMpRiesgo(ctx);
	}

	function destroyMotivoPickers() {
		$("#tablaMesesModelo .selMotivoDesv").each(function () {
			var $s = $(this);
			if ($s.data("selectpicker")) {
				try {
					$s.selectpicker("destroy");
				} catch (e) {}
			}
		});
	}

	function initMotivoPickers() {
		if (!$.fn.selectpicker) {
			return;
		}
		var $sels = $("#tablaMesesModelo .selMotivoDesv");
		if (!$sels.length) {
			return;
		}
		$sels.selectpicker({
			style: "btn-default btn-sm",
			width: "190px",
			size: 8,
			container: "body",
			noneSelectedText: "—"
		});
	}

	function renderMesesModelo(lineas, ctx) {
		var $tb = $("#tablaMesesModelo tbody");
		var $tf = $("#tablaMesesModeloFoot");
		$tb.empty();
		$tf.empty();
		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		var est = mapaEstacional(ctx);
		var precio = ctx && ctx.precio_lista9 != null ? Number(ctx.precio_lista9) : null;
		if (!(lineas || []).length) {
			$tb.append('<tr><td colspan="9" class="text-muted text-center">Sin líneas. Pulsa “Preparar meses”.</td></tr>');
			$("#proyCantResumen").empty();
			llenarSelectMesFactores([], { skipLoad: false });
			return;
		}
		var totOfi = 0;
		var totSug = 0;
		var totAj = 0;
		var totRef = 0;
		var nRef = 0;
		var anioRefHead = null;
		lineas.forEach(function (l) {
			var editable = puedeEditar && l.estado_linea !== "CERRADO";
			var sugMes = sugDeLinea(l, ctx);
			var aj = Number(l.unidades_ajustes) || 0;
			var base = Math.max(0, sugMes + aj);
			var ofi = Number(l.unidades_oficiales) || 0;
			var nFact = Number(l.n_factores) || 0;
			var pct = "";
			if (base > 0) {
				pct = String(Math.round(((ofi - base) / base) * 100));
			}
			var f = est[l.periodo];
			var histTxt = f ? histStack(f.historial) : "—";
			var ref = f ? histRefCerrado(f.historial) : null;
			if (ref && !anioRefHead) {
				anioRefHead = ref.anio;
			}
			var ajHtml = '<span class="' + (aj > 0 ? "proy-num-pos" : (aj < 0 ? "proy-num-neg" : "")) + '">' +
				(aj > 0 ? "+" : "") + num(aj, 0) + "</span>";
			if (nFact) {
				ajHtml += ' <span class="label label-info">' + nFact + "</span>";
			}
			var ofiHtml = editable
				? '<input type="number" min="0" step="10" class="form-control input-sm inpOficialModelo" data-id="' +
					l.id + '" data-base="' + base + '" data-sug="' + sugMes + '" data-aj="' + aj +
					'" value="' + ofi + '">' +
					'<small class="tdDesv">' + htmlCeldaDesv(sugMes, aj, ofi, nFact) + "</small>"
				: '<strong>' + num(ofi, 0) + "</strong>" +
					'<small class="tdDesv">' + htmlCeldaDesv(sugMes, aj, ofi, nFact) + "</small>";
			var tr = $("<tr data-id='" + l.id + "' data-n-factores='" + nFact + "'>");
			if (ref) {
				tr.attr("data-hist-ref", ref.uds);
				tr.attr("data-hist-anio", ref.anio || "");
			}
			if (precio != null) {
				tr.attr("data-precio", precio);
			}
			tr.append($("<td>").html("<strong>" + etiquetaMesDePeriodo(l.periodo, true) + "</strong>"));
			tr.append($("<td>").html(histTxt));
			tr.append($("<td>").html(
				num(sugMes, 0) +
				(ctx && ctx.sugerencias && ctx.sugerencias[l.periodo] && ctx.sugerencias[l.periodo].sin_historia
					? ' <span class="text-muted">sin historial</span>' : "")
			));
			tr.append($("<td>").html(ajHtml));
			tr.append($("<td>").html(
				editable
					? '<input type="number" step="1" class="form-control input-sm inpPctMes" data-id="' +
						l.id + '" data-base="' + base + '" value="' + pct + '" title="% sobre sug. + factores">'
					: (pct === "" ? "—" : pct + "%")
			));
			tr.append($("<td class='tdOficial'>").html(ofiHtml));
			tr.append($("<td class='tdVsAnio'>").html(htmlVsAnio(ofi, ref)));
			tr.append($("<td class='tdSoles'>").html(htmlSolesFila(ofi, precio)));
			tr.append($("<td>").html(
				'<span class="label label-default">' + etiquetaEstadoLinea(l.estado_linea) + "</span>"
			));
			$tb.append(tr);
			totOfi += ofi;
			totSug += sugMes;
			totAj += aj;
			if (ref) {
				totRef += ref.uds;
				nRef++;
			}
		});
		if (anioRefHead) {
			$("#thCantVs").text("vs " + anioRefHead);
		} else {
			$("#thCantVs").text("vs año");
		}
		$tf.html(
			"<tr class='proy-tfoot'>" +
			"<td><strong>Total</strong></td>" +
			"<td></td>" +
			"<td><strong>" + num(totSug, 0) + "</strong></td>" +
			"<td><strong>" + (totAj > 0 ? "+" : "") + num(totAj, 0) + "</strong></td>" +
			"<td></td>" +
			"<td><strong>" + num(totOfi, 0) + "</strong></td>" +
			"<td>" + (nRef ? htmlVsAnio(totOfi, { uds: totRef, anio: anioRefHead }) : "") + "</td>" +
			"<td><strong>" + htmlSolesFila(totOfi, precio) + "</strong></td>" +
			"<td></td>" +
			"</tr>"
		);
		pintarResumenCantidades();
		llenarSelectMesFactores(lineas, { skipLoad: !!mesFactorLineaId });
	}

	function pintarResumenCantidades() {
		var $box = $("#proyCantResumen");
		if (!$box.length || !espacioActual) {
			return;
		}
		var ctx = espacioActual.contexto || {};
		var totOfi = 0;
		$(".inpOficialModelo").each(function () {
			totOfi += Number($(this).val()) || 0;
		});
		if (!totOfi && espacioActual.lineas) {
			espacioActual.lineas.forEach(function (l) {
				totOfi += Number(l.unidades_oficiales) || 0;
			});
		}
		var inv = ctx.inventario || {};
		var stock = Number(inv.stock_disponible) || 0;
		var proc = Number(inv.en_proceso) || 0;
		var cubierto = stock + proc;
		var gap = totOfi - cubierto;
		var precio = ctx.precio_lista9 != null ? Number(ctx.precio_lista9) : null;
		var totRef = 0;
		var nRef = 0;
		var anioRef = null;
		$("#tablaMesesModelo tbody tr").each(function () {
			var ref = $(this).attr("data-hist-ref");
			if (ref != null && ref !== "") {
				totRef += Number(ref) || 0;
				nRef++;
				if (!anioRef) {
					anioRef = $(this).attr("data-hist-anio");
				}
			}
		});
		var vsHtml = "—";
		if (nRef && totRef) {
			var d = totOfi - totRef;
			var pct = totRef ? Math.round((d / Math.abs(totRef)) * 100) : 0;
			var cls = d > 0 ? "proy-num-pos" : (d < 0 ? "proy-num-neg" : "");
			vsHtml = '<span class="' + cls + '">' + (d > 0 ? "+" : "") + pct + "%</span>" +
				'<span class="proy-stat-meta">vs ' + (anioRef || "año cerrado") + "</span>";
		}
		var cubHtml;
		if (gap > 0) {
			cubHtml = '<span class="proy-num-neg">faltan ' + num(gap, 0) + "</span>" +
				'<span class="proy-stat-meta">stock ' + num(stock, 0) + " + proceso " + num(proc, 0) + "</span>";
		} else {
			cubHtml = '<span class="proy-num-pos">cubierto</span>' +
				'<span class="proy-stat-meta">stock ' + num(stock, 0) + " + proceso " + num(proc, 0) + "</span>";
		}
		$box.html(
			'<div class="proy-fp-item"><em>Proyectás</em><b>' + num(totOfi, 0) + " uds</b></div>" +
			'<div class="proy-fp-item"><em>Contra el histórico</em><b>' + vsHtml + "</b></div>" +
			'<div class="proy-fp-item proy-fp-result"><em>A lista 9</em><b>' +
				(precio != null ? moneda(totOfi * precio) : "—") + "</b></div>" +
			'<div class="proy-fp-item"><em>Abastecimiento</em><b>' + cubHtml + "</b></div>"
		);
	}

	function mesSelectVal() {
		var v = $("#mesFactorSelect").val();
		if ($.isArray(v)) {
			v = v.length ? v[0] : "";
		}
		return v ? String(v) : "";
	}

	function llenarSelectMesFactores(lineas, opts) {
		opts = opts || {};
		var $sel = $("#mesFactorSelect");
		var prev = mesFactorLineaId || mesSelectVal();
		silenceMesSelect = true;
		if ($sel.data("selectpicker")) {
			try {
				$sel.selectpicker("destroy");
			} catch (e) {}
		}
		$sel.empty();
		if (!(lineas || []).length) {
			$sel.append($("<option>").val("").text("Sin meses"));
			mesFactorLineaId = "";
			silenceMesSelect = false;
			pintarChipsMesFactores([]);
			$("#proyFactMesTitulo").text("Elegí un mes");
			$("#proyFactMesEstado").text("");
			$("#listaCatalogoChecks").html('<p class="text-muted">Prepara meses primero.</p>');
			limpiarPreviewFactores();
			return;
		}
		lineas.forEach(function (l) {
			$sel.append($("<option>").val(String(l.id)).text(etiquetaMesDePeriodo(l.periodo, true)));
		});
		if (prev && $sel.find("option[value='" + prev + "']").length) {
			$sel.val(prev);
		} else {
			$sel.val(String(lineas[0].id));
		}
		mesFactorLineaId = mesSelectVal();
		silenceMesSelect = false;
		pintarChipsMesFactores(lineas);
		if (!opts.skipLoad) {
			cargarChecksMesActual();
		}
	}

	function pintarChipsMesFactores(lineas) {
		var $box = $("#proyFactMeses").empty();
		(lineas || []).forEach(function (l) {
			var nFact = Number(l.n_factores) || 0;
			var btn = $('<button type="button" class="proy-fact-chip-mes"></button>');
			btn.attr("data-id", l.id);
			btn.toggleClass("is-active", String(l.id) === String(mesFactorLineaId));
			btn.toggleClass("has-facts", nFact > 0);
			btn.html(
				"<strong>" + etiquetaMesDePeriodo(l.periodo, false) + "</strong>" +
				(nFact ? "<em>" + nFact + "</em>" : "")
			);
			$box.append(btn);
		});
	}

	function limpiarPreviewFactores() {
		$("#fpSug, #fpAj, #fpRes, #fpOfi").text("—").removeClass("proy-num-pos proy-num-neg");
		$("#resumenFactorLinea").empty();
	}

	function ajusteItemEstimado(it, sug) {
		if (it.aplicado && it.ajuste_aplicado != null) {
			return Number(it.ajuste_aplicado) || 0;
		}
		var aj = Number(it.ajuste_unidades_default) || 0;
		if (aj === 0 && it.impacto_pct_default != null && Number(sug) > 0) {
			aj = Math.round(Number(sug) * (Number(it.impacto_pct_default) / 100));
		}
		return aj;
	}

	function pintarNum($el, valor, conSigno) {
		var v = Number(valor) || 0;
		$el.removeClass("proy-num-pos proy-num-neg");
		if (v > 0) {
			$el.addClass("proy-num-pos");
		} else if (v < 0) {
			$el.addClass("proy-num-neg");
		}
		if (conSigno) {
			$el.text((v > 0 ? "+" : "") + num(v, 0));
		} else {
			$el.text(num(v, 0));
		}
	}

	function actualizarPreviewFactores() {
		var lin = catalogoLineaCache && catalogoLineaCache.linea;
		if (!lin) {
			limpiarPreviewFactores();
			return;
		}
		var sug = Number(lin.unidades_sugeridas) || 0;
		var ofi = Number(lin.unidades_oficiales) || 0;
		var suma = 0;
		$(".chkCatalogoLinea:checked").each(function () {
			suma += Number($(this).data("ajusteEst")) || 0;
		});
		var resultado = Math.max(0, sug + suma);
		pintarNum($("#fpSug"), sug, false);
		pintarNum($("#fpAj"), suma, true);
		pintarNum($("#fpRes"), resultado, false);
		pintarNum($("#fpOfi"), ofi, false);
		var deltaOfi = resultado - ofi;
		var mesNom = etiquetaMesDePeriodo(lin.periodo, true).toLowerCase();
		var txt;
		if (deltaOfi === 0) {
			txt = "En " + mesNom + ", con lo marcado, quedaría en <strong>" +
				num(resultado, 0) + "</strong> unidades. Es igual al oficial.";
		} else {
			txt = "En " + mesNom + ", con lo marcado, quedaría en <strong>" +
				num(resultado, 0) + "</strong> unidades: <span class='" +
				(deltaOfi > 0 ? "proy-num-pos" : "proy-num-neg") + "'>" +
				num(Math.abs(deltaOfi), 0) + " " + (deltaOfi > 0 ? "más" : "menos") +
				"</span> que el oficial.";
		}
		$("#resumenFactorLinea").html(txt);
	}

	function cargarChecksMesActual() {
		var idLinea = mesFactorLineaId || mesSelectVal();
		if (!idLinea) {
			$("#listaCatalogoChecks").html('<p class="text-muted">Elegí un mes arriba.</p>');
			$("#proyFactMesTitulo").text("Elegí un mes");
			$("#proyFactMesEstado").text("");
			limpiarPreviewFactores();
			return;
		}
		mesFactorLineaId = String(idLinea);
		post("catalogoLinea", { id_linea: idLinea }).done(function (resp) {
			if (!resp || !resp.ok) {
				$("#listaCatalogoChecks").html(
					'<p class="text-danger">' + ((resp && resp.mensaje) || "¿Ejecutaste el SQL del catálogo?") +
					' <a href="index.php?ruta=proyeccion-comercial-factores">Ir a factores</a></p>'
				);
				limpiarPreviewFactores();
				return;
			}
			catalogoLineaCache = resp;
			renderChecksCatalogo(resp);
		}).fail(function () {
			$("#listaCatalogoChecks").html('<p class="text-danger">Error de red al cargar checks</p>');
			limpiarPreviewFactores();
		});
	}

	function htmlCheckFactor(it, lin, sug, puedeEditar) {
		var id = "chkCat_" + it.id;
		var ajEst = ajusteItemEstimado(it, sug);
		var esPos = ajEst > 0;
		var esNeg = ajEst < 0;
		var row = $('<label class="proy-check-item"></label>');
		if (esPos) {
			row.addClass("proy-check-pos");
		} else if (esNeg) {
			row.addClass("proy-check-neg");
		}
		if (it.aplicado) {
			row.addClass("proy-check-on");
		}
		if (!puedeEditar) {
			row.addClass("is-disabled");
		}
		var cb = $('<input type="checkbox" class="chkCatalogoLinea">');
		cb.attr("id", id);
		cb.prop("checked", !!it.aplicado);
		cb.prop("disabled", !puedeEditar);
		cb.data("idCatalogo", it.id);
		cb.data("idLinea", lin.id);
		cb.data("ajusteEst", ajEst);
		var signo = (ajEst > 0 ? "+" : "") + num(ajEst, 0);
		var meta = tipoNombre(it.tipo);
		if (it.impacto_pct_default != null) {
			meta += " · " + num(it.impacto_pct_default, 0) + "%";
		}
		row.append(cb);
		row.append($("<span class='proy-check-flag'>").text(it.aplicado ? "Marcado" : "Marcar"));
		row.append($("<span class='proy-check-title'>").text(it.titulo));
		row.append(
			$("<span class='proy-check-ajuste'>")
				.addClass(esPos ? "proy-num-pos" : (esNeg ? "proy-num-neg" : ""))
				.text(signo + " uds")
		);
		row.append($("<span class='proy-check-meta'>").text(meta));
		return row;
	}

	function renderGrupoChecks($box, titulo, items, lin, sug, puedeEditar) {
		if (!items.length) {
			return;
		}
		var nOn = items.filter(function (it) { return !!it.aplicado; }).length;
		var $g = $('<div class="proy-check-grupo"></div>');
		$g.append(
			$('<div class="proy-check-grupo-tit"></div>').text(
				titulo + (nOn ? " · " + nOn + " marcado" + (nOn === 1 ? "" : "s") : "")
			)
		);
		var $grid = $('<div class="proy-catalogo-checks"></div>');
		items.forEach(function (it) {
			$grid.append(htmlCheckFactor(it, lin, sug, puedeEditar));
		});
		$g.append($grid);
		$box.append($g);
	}

	function renderChecksCatalogo(detalle) {
		var $box = $("#listaCatalogoChecks").empty();
		var lin = detalle.linea || {};
		var sug = Number(lin.unidades_sugeridas) || 0;
		var items = detalle.items || [];
		$("#proyFactMesTitulo").text(lin.periodo ? etiquetaMesDePeriodo(lin.periodo, true) : "Elegí un mes");
		$("#proyFactMesEstado").text(etiquetaEstadoLinea(lin.estado_linea));
		$("#proyFactMesEstado").toggleClass("is-pub", String(lin.estado_linea) === "PUBLICADO");
		$("#proyFactMesEstado").toggleClass("is-cerrado", String(lin.estado_linea) === "CERRADO");
		if (!items.length) {
			$box.html('<p class="text-muted">No hay factores. Créalos en <a href="index.php?ruta=proyeccion-comercial-factores">Factores de proyección</a>.</p>');
			actualizarPreviewFactores();
			return;
		}
		var puedeEditar = $("#proyPuedeEditar").val() === "1" && lin.estado_linea !== "CERRADO";
		var pos = [];
		var neg = [];
		items.forEach(function (it) {
			if (ajusteItemEstimado(it, sug) < 0) {
				neg.push(it);
			} else {
				pos.push(it);
			}
		});
		renderGrupoChecks($box, "Suma unidades", pos, lin, sug, puedeEditar);
		renderGrupoChecks($box, "Resta unidades", neg, lin, sug, puedeEditar);
		actualizarPreviewFactores();
		$("#tablaFactoresPorMes tbody tr").removeClass("is-active");
		$("#tablaFactoresPorMes tbody tr[data-id='" + lin.id + "']").addClass("is-active");
		$(".proy-fact-chip-mes").removeClass("is-active");
		$(".proy-fact-chip-mes[data-id='" + lin.id + "']").addClass("is-active");
	}

	function renderFactoresPorMes(rows) {
		factoresPorMesCache = rows || [];
		var $tb = $("#tablaFactoresPorMes tbody").empty();
		if (!factoresPorMesCache.length) {
			$tb.append('<tr><td colspan="5" class="text-muted text-center">Sin meses del modelo</td></tr>');
			return;
		}
		factoresPorMesCache.forEach(function (m) {
			var facts = m.factores || [];
			var lista;
			if (!facts.length) {
				lista = '<span class="text-muted">Ninguno</span>';
			} else {
				lista = facts.map(function (f) {
					var cls = f.ajuste_unidades > 0 ? "proy-num-pos" : (f.ajuste_unidades < 0 ? "proy-num-neg" : "");
					var sig = (f.ajuste_unidades > 0 ? "+" : "") + num(f.ajuste_unidades, 0);
					return '<span class="proy-fact-chip">' + $("<div>").text(f.titulo).html() +
						' <b class="' + cls + '">' + sig + "</b></span>";
				}).join(" ");
			}
			var aj = Number(m.unidades_ajustes) || 0;
			var sug = Number(m.unidades_sugeridas) || 0;
			var res = Math.max(0, sug + aj);
			var tr = $("<tr class='proy-fact-mes-row'>");
			tr.attr("data-id", m.id_linea);
			tr.toggleClass("is-active", String(m.id_linea) === String(mesFactorLineaId));
			tr.append($("<td>").html("<strong>" + etiquetaMesDePeriodo(m.periodo, true) + "</strong>"));
			tr.append($("<td>").html(lista));
			tr.append($("<td>").html(
				'<span class="' + (aj > 0 ? "proy-num-pos" : (aj < 0 ? "proy-num-neg" : "")) + '">' +
				(aj > 0 ? "+" : "") + num(aj, 0) + "</span>"
			));
			tr.append($("<td>").text(num(res, 0)));
			tr.append($("<td>").text(num(m.unidades_oficiales, 0)));
			$tb.append(tr);
		});
	}

	function refrescarResumenFactoresModelo() {
		var id = $("#proyIdPeriodo").val();
		var modelo = $("#proyModeloActivo").val() || modeloSeleccionado;
		if (!id || !modelo) {
			return;
		}
		post("resumenFactoresModelo", { id_periodo: id, modelo: modelo }).done(function (resp) {
			if (!resp || !resp.ok) {
				return;
			}
			if (espacioActual) {
				espacioActual.factores_por_mes = resp.factores_por_mes || [];
			}
			renderFactoresPorMes(resp.factores_por_mes || []);
		});
	}

	function toggleCatalogoCheck($cb) {
		var aplicar = $cb.is(":checked");
		var idLinea = $cb.data("idLinea");
		var idCatalogo = $cb.data("idCatalogo");
		var lin = catalogoLineaCache && catalogoLineaCache.linea;

		function ejecutar(motivo) {
			$cb.closest(".proy-check-item").toggleClass("proy-check-on", aplicar);
			actualizarPreviewFactores();
			$cb.prop("disabled", true);
			post("toggleCatalogoLinea", {
				id_linea: idLinea,
				id_catalogo: idCatalogo,
				aplicar: aplicar ? 1 : 0,
				motivo: motivo || ""
			}).done(function (resp) {
				if (!resp || !resp.ok) {
					$cb.prop("checked", !aplicar);
					$cb.closest(".proy-check-item").toggleClass("proy-check-on", !aplicar);
					actualizarPreviewFactores();
					proyAlert((resp && resp.mensaje) || "No se pudo aplicar", "error");
					return;
				}
				catalogoLineaCache = resp;
				if (resp.unidades_ajustes != null && catalogoLineaCache.linea) {
					catalogoLineaCache.linea.unidades_ajustes = resp.unidades_ajustes;
				}
				if (resp.linea && espacioActual && espacioActual.lineas) {
					espacioActual.lineas.forEach(function (l) {
						if (String(l.id) === String(resp.linea.id)) {
							l.unidades_ajustes = resp.linea.unidades_ajustes;
							l.unidades_oficiales = resp.linea.unidades_oficiales;
							l.n_factores = (resp.items || []).filter(function (it) {
								return !!it.aplicado;
							}).length;
						}
					});
					renderMesesModelo(espacioActual.lineas, espacioActual.contexto);
					renderDecisionModelo(espacioActual);
				}
				renderChecksCatalogo(resp);
				refrescarResumenFactoresModelo();
			}).fail(function () {
				$cb.prop("checked", !aplicar);
				$cb.closest(".proy-check-item").toggleClass("proy-check-on", !aplicar);
				actualizarPreviewFactores();
				proyAlert("Error de red", "error");
			}).always(function () {
				$cb.prop("disabled", false);
			});
		}

		if (lin && lin.estado_linea === "PUBLICADO") {
			proyPrompt("Motivo obligatorio", "La línea está publicada. Indica el motivo del cambio:", "").then(function (motivo) {
				if (motivo === null || $.trim(motivo) === "") {
					$cb.prop("checked", !aplicar);
					proyAlert("Cancelado", "info");
					return;
				}
				ejecutar($.trim(motivo));
			});
			return;
		}
		ejecutar("");
	}

	function renderEspacio(resp) {
		espacioActual = resp;
		var c = resp.contexto;
		var cab = c.cabecera;
		if (String($("#proyModeloActivo").val() || "") !== String(cab.modelo || "")) {
			mesFactorLineaId = "";
		}
		ocultarCargaModelo();
		ocultarCargaBarra();
		$("#panelModeloVacio").hide();
		$("#panelModeloActivo").show();
		$("#proyModeloActivo").val(cab.modelo);
		modeloSeleccionado = cab.modelo;
		syncUrlEstado();
		$("#mdlTitulo").text(cab.modelo + " — " + (cab.nombre || ""));
		$("#mdlMeta").text(
			(cab.marca || "Sin marca") + " · " + (cab.estado || "") +
			" · fórmula " + (c.formula_version || "")
		);
		$("#mdlRangoPlan").text(
			nombreMesCorto(resp.plan.mes_desde) + " " + resp.plan.anio_desde +
			" → " + nombreMesCorto(resp.plan.mes_hasta) + " " + resp.plan.anio_hasta
		);
		$("#kpiLista9").text(c.sin_lista9 ? "Sin lista 9" : moneda(c.precio_lista9));
		$("#kpiStock").text(num(c.inventario.stock_disponible, 0));
		$("#kpiProceso").text(num(c.inventario.en_proceso, 0));
		renderHistEstacional(c, resp.lineas || []);
		renderMesesModelo(resp.lineas || [], c);
		if (hayColoresOff()) {
			aplicarSugColoresAOficial();
		}
		renderFactoresPorMes(resp.factores_por_mes || []);
		renderDecisionModelo(resp);
		$("#proyConcModelo").val(cab.modelo);
		if (resp.catalogo_factores) {
			// mantener tabla catálogo si ya cargamos todos; solo refrescar checks
		}
	}

	function cargarEspacioModelo(asegurar) {
		var id = $("#proyIdPeriodo").val();
		var modelo = modeloSeleccionado;
		if (!id) {
			proyAlert("Abre un plan primero");
			return;
		}
		if (!modelo) {
			proyAlert("Elige un modelo en la lista");
			return;
		}
		modeloSeleccionado = modelo;
		mostrarCargaBarra("Cargando " + modelo + "…");
		mostrarCargaModelo(modelo);
		var $item = $(".proy-lista-item[data-modelo='" + modelo.replace(/'/g, "") + "']");
		$item.addClass("is-loading");
		$item.find(".btnListaModelo").prop("disabled", true);
		post("espacioModelo", {
			id_periodo: id,
			modelo: modelo,
			asegurar: asegurar ? 1 : 0
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				ocultarCargaModelo();
				$("#panelModeloVacio").show();
				$("#decVacio").show();
				proyAlert((resp && resp.mensaje) || "No se pudo abrir el modelo");
				return;
			}
			renderEspacio(resp);
			cargarPendientesYStats();
		}).fail(function (xhr, status) {
			ocultarCargaModelo();
			$("#panelModeloVacio").show();
			$("#decVacio").show();
			proyAlert(status === "timeout" ? "Timeout al abrir el modelo" : "Error de red");
		}).always(function () {
			ocultarCargaBarra();
			$(".proy-lista-item").removeClass("is-loading");
			$(".proy-lista-item .btnListaModelo").prop("disabled", false);
			renderListaModelos();
		});
	}

	function redondeo10(n) {
		return Math.max(0, Math.round(Number(n) / 10) * 10);
	}

	function hayColoresOff() {
		var off = (matrizPrefs().off) || {};
		var k;
		for (k in off) {
			if (off.hasOwnProperty(k) && off[k]) {
				return true;
			}
		}
		return false;
	}

	function aplicarSugColoresAOficial() {
		if ($("#proyPuedeEditar").val() !== "1" || !espacioActual) {
			return;
		}
		var ctx = espacioActual.contexto;
		(espacioActual.lineas || []).forEach(function (l) {
			if (l.estado_linea === "CERRADO") {
				return;
			}
			var $inp = $(".inpOficialModelo[data-id='" + l.id + "']");
			if (!$inp.length) {
				return;
			}
			var sugAdj = ajustarSugPorColores(sugDeLinea(l, ctx), l.periodo, ctx);
			var aj = Number(l.unidades_ajustes) || 0;
			var base = Math.max(0, sugAdj + aj);
			var ofi = redondeo10(base);
			$inp.data("sug", sugAdj);
			$inp.data("aj", aj);
			$inp.data("base", base);
			$inp.val(ofi);
			$inp.closest("tr").find(".inpPctMes").data("base", base);
			l.unidades_oficiales = ofi;
			syncPctFromOficial($inp);
		});
	}

	function syncPctFromOficial($input) {
		var base = Number($input.data("base")) || 0;
		var ofi = Number($input.val()) || 0;
		var $pct = $input.closest("tr").find(".inpPctMes");
		if (base > 0) {
			$pct.val(Math.round(((ofi - base) / base) * 100));
		} else {
			$pct.val("");
		}
		refrescarDesvFila($input.closest("tr"));
	}

	function syncOficialFromPct($input) {
		var base = Number($input.data("base")) || 0;
		var pct = Math.round(Number($input.val()));
		if (!isFinite(pct)) {
			return;
		}
		$input.val(pct);
		$input.closest("tr").find(".inpOficialModelo").val(redondeo10(base * (1 + pct / 100)));
		refrescarDesvFila($input.closest("tr"));
	}

	function aplicarBaseFactoresTodas() {
		$(".inpOficialModelo").each(function () {
			var base = Number($(this).data("base")) || 0;
			var ofi = redondeo10(base);
			$(this).val(ofi);
			syncPctFromOficial($(this));
		});
		if (espacioActual) {
			renderDecisionModelo(espacioActual);
		}
	}

	function guardarModelo() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return;
		}
		var cambios = [];
		$(".inpOficialModelo").each(function () {
			var idLinea = parseInt($(this).data("id"), 10);
			var ofi = parseInt($(this).val(), 10) || 0;
			cambios.push({ id: idLinea, unidades_oficiales: ofi });
		});
		if (!cambios.length) {
			proyAlert("No hay meses editables", "warning");
			return;
		}
		var hayPub = false;
		(espacioActual.lineas || []).forEach(function (l) {
			if (l.estado_linea === "PUBLICADO") {
				cambios.forEach(function (c) {
					if (c.id === l.id && c.unidades_oficiales !== l.unidades_oficiales) {
						hayPub = true;
					}
				});
			}
		});

		function enviar(motivo) {
			post("guardarLineas", {
				id_periodo: id,
				cambios: JSON.stringify(cambios),
				motivo: motivo || ""
			}).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo guardar", "error");
					return;
				}
				proyAlert("Guardadas: " + (resp.guardadas || 0), "success");
				cargarEspacioModelo(false);
			}).fail(function () {
				proyAlert("Error de red al guardar", "error");
			});
		}

		if (hayPub) {
			proyPrompt(
				"Motivo obligatorio",
				"Hay meses publicados. Indica el motivo de la corrección:",
				""
			).then(function (motivo) {
				if (motivo === null || $.trim(motivo) === "") {
					proyAlert("Cancelado", "info");
					return;
				}
				enviar($.trim(motivo));
			});
			return;
		}
		enviar("");
	}

	function publicarModelo() {
		var id = $("#proyIdPeriodo").val();
		var modelo = $("#proyModeloActivo").val();
		if (!id || !modelo) {
			return;
		}
		proyConfirm(
			"Publicar modelo",
			"¿Publicar solo los borradores de " + modelo + " en este plan?"
		).then(function (ok) {
			if (!ok) {
				return;
			}
			post("publicar", {
				id_periodo: id,
				anio: 0,
				mes: 0,
				modelo: modelo
			}).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo publicar", "error");
					return;
				}
				proyAlert(resp.mensaje || ("Publicadas: " + resp.publicadas), "success");
				cargarEspacioModelo(false);
				listarPlanes();
			}).fail(function () {
				proyAlert("Error de red al publicar", "error");
			});
		});
	}

	function generarLineas() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			proyAlert("Abre un plan primero");
			return;
		}
		if (!filtroOk()) {
			return;
		}
		var $btn = $("#btnGenerarLineas");
		$btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Generando…');
		post("generarLineas", {
			id_periodo: id,
			id_marca: $("#proyMarca").val() || 0,
			q: $("#proyQ").val() || ""
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo generar");
				return;
			}
			proyAlert("Creadas: " + (resp.creadas || 0) + " | Actualizadas: " + (resp.actualizadas || 0) + " | Omitidas: " + (resp.omitidas || 0));
			abrirPlan(id);
		}).fail(function (xhr, status) {
			proyAlert(status === "timeout" ? "Timeout al generar" : "Error de red");
		}).always(function () {
			$btn.prop("disabled", false).html('<i class="fa fa-magic"></i> Generar lote (marca/búsqueda)');
		});
	}

	function cambiosDesdeInputs() {
		var cambios = [];
		$(".inpOficial").each(function () {
			var id = parseInt($(this).data("id"), 10);
			var val = parseInt($(this).val(), 10);
			if (!isFinite(id) || !isFinite(val) || val < 0) {
				return;
			}
			cambios.push({ id: id, unidades_oficiales: val });
		});
		return cambios;
	}

	function guardarLineas() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return;
		}
		var cambios = cambiosDesdeInputs();
		if (!cambios.length) {
			proyAlert("No hay cambios editables en la grilla");
			return;
		}
		post("guardarLineas", {
			id_periodo: id,
			cambios: JSON.stringify(cambios),
			motivo: ""
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo guardar");
				return;
			}
			proyAlert("Guardadas: " + (resp.guardadas || 0));
			abrirPlan(id);
		}).fail(function () {
			proyAlert("Error de red al guardar");
		});
	}

	function publicar(todo) {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return;
		}
		var data = { id_periodo: id, anio: 0, mes: 0 };
		if (!todo) {
			var mesSel = $("#filtroMesPlan").val();
			if (!mesSel) {
				proyAlert("Elige un mes en el filtro.", "warning");
				return;
			}
			var p = mesSel.split("-");
			data.anio = parseInt(p[0], 10);
			data.mes = parseInt(p[1], 10);
		}
		proyConfirm(
			"Publicar",
			todo ? "¿Publicar todos los borradores del plan?" : "¿Publicar el mes filtrado?"
		).then(function (ok) {
			if (!ok) {
				return;
			}
			post("publicar", data).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo publicar", "error");
					return;
				}
				proyAlert(resp.mensaje || ("Publicadas: " + resp.publicadas), "success");
				abrirPlan(id);
				if ($("#proyModeloActivo").val()) {
					cargarEspacioModelo(false);
				}
			}).fail(function () {
				proyAlert("Error de red al publicar", "error");
			});
		});
	}

	function limpiarFormFactor() {
		$("#factorId").val("");
		$("#factorTitulo").val("");
		$("#factorAjuste").val("");
		$("#factorPct").val("");
		$("#factorAplicarPct").prop("checked", false);
		$("#factorDesde").val("");
		$("#factorHasta").val("");
		$("#factorCanal").val("");
		$("#factorInversion").val("");
		$("#factorPrecioAnt").val("");
		$("#factorPrecioNuevo").val("");
		$("#factorEvidencia").val("");
		$("#factorDescripcion").val("");
		if (tiposFactorCache.length) {
			$("#factorTipo").val(tiposFactorCache[0].codigo);
		}
	}

	function renderFactores(detalle) {
		factorLineaActual = detalle.linea || null;
		var $tb = $("#tablaFactores tbody");
		$tb.empty();
		var lin = detalle.linea || {};
		$("#lblFactorLinea").text((lin.modelo || "") + " · " + (lin.periodo || ""));
		$("#factorIdLinea").val(lin.id || "");
		var factores = detalle.factores || [];
		factoresById = {};
		if (!factores.length) {
			$tb.append('<tr><td colspan="6" class="text-muted text-center">Sin factores activos</td></tr>');
			return;
		}
		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		factores.forEach(function (f) {
			factoresById[f.id] = f;
			var tipoNombre = f.tipo;
			tiposFactorCache.forEach(function (t) {
				if (t.codigo === f.tipo) {
					tipoNombre = t.nombre;
				}
			});
			var tr = $("<tr>");
			tr.append($("<td>").text(tipoNombre));
			tr.append($("<td>").text(f.titulo));
			tr.append($("<td>").text(num(f.ajuste_unidades, 0)));
			tr.append($("<td>").text(f.impacto_pct == null ? "—" : num(f.impacto_pct, 2) + "%"));
			tr.append($("<td>").text(f.descripcion || "—"));
			var acciones = "—";
			if (puedeEditar && lin.estado_linea !== "CERRADO") {
				acciones =
					'<button type="button" class="btn btn-xs btn-default btnEditarFactor" data-id="' + f.id + '">Editar</button> ' +
					'<button type="button" class="btn btn-xs btn-danger btnBorrarFactor" data-id="' + f.id + '">Quitar</button>';
			}
			tr.append($("<td>").html(acciones));
			$tb.append(tr);
		});
	}

	function abrirFactores(idLinea) {
		mesFactorLineaId = String(idLinea);
		$("#mesFactorSelect").val(mesFactorLineaId);
		cargarChecksMesActual();
		$(".proy-fact-chip-mes").removeClass("is-active");
		$(".proy-fact-chip-mes[data-id='" + mesFactorLineaId + "']").addClass("is-active");
		$('a[href="#tabFact"]').tab("show");
		setTimeout(function () {
			var $cat = $(".proy-fact-catalogo");
			if ($cat.length) {
				$("html, body").animate({ scrollTop: $cat.offset().top - 80 }, 250);
			}
		}, 50);
	}

	function guardarFactor() {
		var idLinea = $("#factorIdLinea").val();
		if (!idLinea) {
			return;
		}
		var data = {
			id_linea: idLinea,
			id: $("#factorId").val() || 0,
			tipo: $("#factorTipo").val(),
			titulo: $("#factorTitulo").val(),
			descripcion: $("#factorDescripcion").val(),
			ajuste_unidades: $("#factorAjuste").val(),
			impacto_pct: $("#factorPct").val(),
			aplicar_pct: $("#factorAplicarPct").is(":checked") ? 1 : 0,
			fecha_desde: $("#factorDesde").val(),
			fecha_hasta: $("#factorHasta").val(),
			canal_publicidad: $("#factorCanal").val(),
			inversion_publicidad: $("#factorInversion").val(),
			precio_anterior: $("#factorPrecioAnt").val(),
			precio_nuevo: $("#factorPrecioNuevo").val(),
			referencia_evidencia: $("#factorEvidencia").val(),
			motivo: ""
		};

		function enviar() {
			post("guardarFactor", data).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo guardar", "error");
					return;
				}
				renderFactores(resp.detalle);
				limpiarFormFactor();
				if ($("#proyModeloActivo").val()) {
					cargarEspacioModelo(false);
				} else {
					abrirPlan($("#proyIdPeriodo").val());
				}
			});
		}

		if (factorLineaActual && factorLineaActual.estado_linea === "PUBLICADO") {
			proyPrompt("Motivo obligatorio", "La línea está publicada. Indica el motivo:", "").then(function (motivo) {
				if (motivo === null || $.trim(motivo) === "") {
					proyAlert("Cancelado", "info");
					return;
				}
				data.motivo = $.trim(motivo);
				enviar();
			});
			return;
		}
		enviar();
	}

	function borrarFactor(idFactor) {
		proyConfirm("Quitar factor", "¿Quitar este factor?").then(function (ok) {
			if (!ok) {
				return;
			}
			var data = { id_factor: idFactor, motivo: "" };
			function enviar() {
				post("eliminarFactor", data).done(function (resp) {
					if (!resp || !resp.ok) {
						proyAlert((resp && resp.mensaje) || "No se pudo quitar", "error");
						return;
					}
					renderFactores(resp.detalle);
					if ($("#proyModeloActivo").val()) {
						cargarEspacioModelo(false);
					}
				});
			}
			if (factorLineaActual && factorLineaActual.estado_linea === "PUBLICADO") {
				proyPrompt("Motivo obligatorio", "La línea está publicada. Indica el motivo:", "").then(function (motivo) {
					if (motivo === null || $.trim(motivo) === "") {
						return;
					}
					data.motivo = $.trim(motivo);
					enviar();
				});
				return;
			}
			enviar();
		});
	}

	function aplicarSugAjustes() {
		var idLinea = $("#factorIdLinea").val();
		if (!idLinea) {
			return;
		}
		var data = { id_linea: idLinea, motivo: "" };
		function enviar() {
			post("aplicarSugMasAjustes", data).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo aplicar", "error");
					return;
				}
				abrirFactores(idLinea);
				cargarEspacioModelo(false);
			});
		}
		if (factorLineaActual && factorLineaActual.estado_linea === "PUBLICADO") {
			proyPrompt("Motivo", "Indica el motivo:", "Aplicar sugerencia + factores").then(function (motivo) {
				if (motivo === null || $.trim(motivo) === "") {
					return;
				}
				data.motivo = $.trim(motivo);
				enviar();
			});
			return;
		}
		enviar();
	}

	function conciliar() {
		$("#btnProyConciliar").prop("disabled", true);
		post("conciliar", {
			modelo: $("#proyConcModelo").val(),
			anio: $("#proyConcAnio").val(),
			mes: $("#proyConcMes").val()
		}).done(function (resp) {
			$("#proyConcResultado").show().text(JSON.stringify(resp, null, 2));
		}).fail(function () {
			$("#proyConcResultado").show().text("Error de red al conciliar");
		}).always(function () {
			$("#btnProyConciliar").prop("disabled", false);
		});
	}

	$(function () {
		if (!$(".proyeccion-comercial-wrap").length) {
			return;
		}
		puedeEditarPlanes = $("#proyPuedeEditar").val() === "1";
		matrizPrefsLoad();
		initFechas();
		initSelectsModelo();
		// mesFactorSelect se (re)inicializa en llenarSelectMesFactores
		cargarCatalogo();
		cargarTiposFactor();
		var planIni = parseInt($("#proyPlanInicial").val() || "0", 10);
		var modeloIni = $.trim($("#proyModeloInicial").val() || "");
		if (planIni > 0) {
			$("#pantallaListado").hide();
			$("#pantallaPlan").show();
			mostrarCargaBarra(modeloIni ? "Recargando el modelo…" : "Abriendo el plan…");
			if (modeloIni) {
				modeloSeleccionado = modeloIni;
				mostrarCargaModelo(modeloIni);
			}
		}
		listarPlanes().always(function () {
			if (planIni > 0) {
				abrirPlan(planIni, modeloIni ? { modelo: modeloIni, asegurar: true } : {});
			} else {
				mostrarListado();
			}
		});

		$("#btnVolverListado").on("click", function () {
			mostrarListado();
		});
		$("#btnCrearPlan").on("click", crearPlan);
		$(document).on("click", ".proy-lista-item", function (e) {
			var $item = $(this);
			var modelo = $.trim($item.attr("data-modelo") || "");
			var tipo = $item.attr("data-tipo") || "";
			if (!modelo || $item.hasClass("is-loading")) {
				return;
			}
			e.preventDefault();
			modeloSeleccionado = modelo;
			$(".proy-lista-item").removeClass("is-active");
			$item.addClass("is-active");
			cargarEspacioModelo(tipo === "pendiente");
		});
		$(document).on("click", ".proy-lista-chip", function () {
			filtroListaModelos = $(this).data("filtro") || "pendientes";
			$(".proy-lista-chip").removeClass("is-active");
			$(this).addClass("is-active");
			renderListaModelos();
		});
		$("#btnAsegurarLineas").on("click", function () {
			cargarEspacioModelo(true);
		});
		$("#proyMarca").on("change", function () {
			cargarPendientesYStats();
		});
		$("#proyQ").on("input", function () {
			renderListaModelos();
			clearTimeout(buscarTimer);
			buscarTimer = setTimeout(function () {
				cargarPendientesYStats();
			}, 300);
		});
		$("#btnGuardarModelo").on("click", guardarModelo);
		$("#btnPublicarModelo").on("click", publicarModelo);
		$("#btnUsarBaseFactores").on("click", aplicarBaseFactoresTodas);
		$("#btnGenerarLineas").on("click", generarLineas);
		$("#btnGuardarLineas").on("click", guardarLineas);
		$("#btnPublicarMes").on("click", function () { publicar(false); });
		$("#btnPublicarTodo").on("click", function () { publicar(true); });
		$("#btnRecargarPlan").on("click", function () {
			var id = $("#proyIdPeriodo").val();
			if (id) {
				abrirPlan(id);
			}
		});
		$("#filtroMesPlan").on("change", function () {
			var id = $("#proyIdPeriodo").val();
			if (id) {
				abrirPlan(id);
			}
		});
		$(document).on("click", "#tablaHistEstacional tbody tr.proy-hist-mes", function () {
			setMesFoco($(this).attr("data-periodo") || "");
		});
		$(document).on("click", "#tablaHistEstacionalFoot tr", function () {
			if (decMesFoco) {
				setMesFoco(null);
			}
		});
		$("#decVerPlan").on("click", function (e) {
			e.preventDefault();
			setMesFoco(null);
		});
		$(document).on("change", "#tablaMatrizSug .proy-mx-on", function () {
			var prefs = matrizPrefs();
			var cod = String($(this).attr("data-cod") || "");
			if ($(this).is(":checked")) {
				delete prefs.off[cod];
			} else {
				prefs.off[cod] = 1;
			}
			matrizPrefsSave();
			if (espacioActual) {
				aplicarSugColoresAOficial();
				renderHistEstacional(espacioActual.contexto, espacioActual.lineas);
				renderDecisionModelo(espacioActual);
			}
		});
		$(document).on("change", "#tablaMatrizSug .proy-mx-uds", function () {
			var prefs = matrizPrefs();
			var cod = String($(this).attr("data-cod") || "");
			var raw = String($(this).val() || "").trim();
			var v = parseInt(raw, 10);
			if (raw === "" || isNaN(v) || v < 0) {
				delete prefs.uds[cod];
			} else {
				prefs.uds[cod] = v;
			}
			matrizPrefsSave();
			if (espacioActual) {
				renderMatrizSugerencia(espacioActual.contexto);
			}
		});
		$(document).on("click", "#tablaMatrizSug .proy-mx-on, #tablaMatrizSug .proy-mx-uds", function (e) {
			e.stopPropagation();
		});
		$(document).on("focus", "#tablaMatrizSug .proy-mx-uds", function () {
			this.select();
		});


		$(document).on("input", ".inpOficialModelo", function () {
			syncPctFromOficial($(this));
			if (espacioActual) {
				if (decChartTimer) {
					clearTimeout(decChartTimer);
				}
				decChartTimer = setTimeout(function () {
					renderDecisionModelo(espacioActual);
				}, 280);
			}
		});
		$(document).on("input", ".inpPctMes", function () {
			syncOficialFromPct($(this));
			if (espacioActual) {
				if (decChartTimer) {
					clearTimeout(decChartTimer);
				}
				decChartTimer = setTimeout(function () {
					renderDecisionModelo(espacioActual);
				}, 280);
			}
		});
		$(document).on("changed.bs.select change", ".selMotivoDesv", function () {
			var $tr = $(this).closest("tr");
			if ($.trim($(this).val() || "")) {
				$(this).removeClass("proy-motivo-req");
				if ($(this).data("selectpicker")) {
					$(this).selectpicker("setStyle", "proy-motivo-req", "remove");
				}
			} else {
				refrescarDesvFila($tr);
			}
		});
		$("#mesFactorSelect").on("changed.bs.select change", function () {
			if (silenceMesSelect) {
				return;
			}
			var v = mesSelectVal();
			if (!v) {
				return;
			}
			mesFactorLineaId = v;
			$(".proy-fact-chip-mes").removeClass("is-active");
			$(".proy-fact-chip-mes[data-id='" + v + "']").addClass("is-active");
			cargarChecksMesActual();
		});
		$(document).on("click", ".proy-fact-chip-mes", function () {
			var id = String($(this).data("id") || "");
			if (!id || id === String(mesFactorLineaId)) {
				return;
			}
			mesFactorLineaId = id;
			silenceMesSelect = true;
			$("#mesFactorSelect").val(id);
			silenceMesSelect = false;
			$(".proy-fact-chip-mes").removeClass("is-active");
			$(this).addClass("is-active");
			cargarChecksMesActual();
		});
		$(document).on("change", ".chkCatalogoLinea", function () {
			toggleCatalogoCheck($(this));
		});
		$(document).on("click", "#tablaFactoresPorMes tbody tr.proy-fact-mes-row", function () {
			var id = $(this).data("id");
			if (id) {
				abrirFactores(id);
			}
		});
		$(document).on("click", ".btnEditarFactoresMes", function () {
			abrirFactores($(this).data("id"));
		});

		$(document).on("click", ".btnAbrirPlan", function () {
			abrirPlan($(this).data("id"));
		});
		$(document).on("click", ".btnEliminarPlan", function () {
			eliminarPlan($(this).data("id"), $(this).data("nombre"));
		});
		$(document).on("click", ".btnFactores", function () {
			abrirFactores($(this).data("id"));
		});
		$(document).on("click", ".btnIrModelo", function () {
			modeloSeleccionado = $(this).data("modelo");
			cargarEspacioModelo(false);
		});
		$(document).on("click", ".btnEditarFactor", function () {
			var f = factoresById[$(this).data("id")];
			if (!f) {
				return;
			}
			$("#factorId").val(f.id);
			$("#factorTipo").val(f.tipo);
			$("#factorTitulo").val(f.titulo);
			$("#factorAjuste").val(f.ajuste_unidades);
			$("#factorPct").val(f.impacto_pct != null ? f.impacto_pct : "");
			$("#factorDesde").val(f.fecha_desde || "");
			$("#factorHasta").val(f.fecha_hasta || "");
			$("#factorCanal").val(f.canal_publicidad || "");
			$("#factorInversion").val(f.inversion_publicidad != null ? f.inversion_publicidad : "");
			$("#factorPrecioAnt").val(f.precio_anterior != null ? f.precio_anterior : "");
			$("#factorPrecioNuevo").val(f.precio_nuevo != null ? f.precio_nuevo : "");
			$("#factorEvidencia").val(f.referencia_evidencia || "");
			$("#factorDescripcion").val(f.descripcion || "");
		});
		$(document).on("click", ".btnBorrarFactor", function () {
			borrarFactor($(this).data("id"));
		});
		$("#btnGuardarFactor").on("click", guardarFactor);
		$("#btnLimpiarFactor").on("click", limpiarFormFactor);
		$("#btnAplicarSugAjustes").on("click", aplicarSugAjustes);
		$("#btnProyConciliar").on("click", conciliar);
	});
})(jQuery);
