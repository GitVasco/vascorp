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
	var filtroListaModelos = "pendientes";
	var select2Ready = false;
	var mesFactorLineaId = "";
	var factoresPorMesCache = [];
	var silenceMesSelect = false;

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
		var badge = "";
		if (relevante && !justificado) {
			cls += " proy-desv-warn";
			badge = ' <span class="label label-warning" title="Supera 10% sin factor: elige motivo">!</span>';
		} else if (relevante && justificado) {
			cls += " proy-desv-ok";
		} else if (d.diff === 0) {
			return '<span class="text-muted proy-desv">0</span>';
		}
		return '<span class="' + cls + '" title="Oficial − (sug. + factores)">' +
			(d.diff > 0 ? "+" : "") + num(d.diff, 0) +
			" <small>(" + (d.pct > 0 ? "+" : "") + d.pct + "%)</small>" +
			badge + "</span>";
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

	function refrescarDesvFila($tr) {
		var $ofi = $tr.find(".inpOficialModelo");
		if (!$ofi.length) {
			return;
		}
		var sug = Number($ofi.data("sug")) || 0;
		var aj = Number($ofi.data("aj")) || 0;
		var ofi = Number($ofi.val()) || 0;
		var nFact = Number($tr.data("nFactores")) || 0;
		var requiere = desviacionRelevanteUi(sug, aj, ofi) && nFact <= 0;
		$tr.find(".tdDesv").html(htmlCeldaDesv(sug, aj, ofi, nFact));
		$tr.toggleClass("proy-fila-desv", requiere);
		var $sel = $tr.find(".selMotivoDesv");
		if ($sel.length) {
			$sel.toggleClass("proy-motivo-req", requiere);
			if ($sel.data("selectpicker")) {
				$sel.selectpicker("refresh");
			}
			if (requiere && !$sel.val()) {
				$sel.find("option:first").text("Elegir motivo…");
			} else if (!requiere && !$sel.val()) {
				$sel.find("option:first").text("—");
			}
			if ($sel.data("selectpicker")) {
				$sel.selectpicker("refresh");
			}
		}
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
		$("#panelModeloVacio").show();
		$("#proyModeloActivo").val("");
		modeloSeleccionado = null;
		mesFactorLineaId = "";
		$("#proyTituloPagina").html('Proyección comercial <small>Planes de venta por modelo</small>');
		if (window.history && window.history.replaceState) {
			window.history.replaceState({}, "", urlEstado(null, null));
		}
		listarPlanes();
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
			meta = (m.marca || "Sin marca") + " · Pendiente";
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
		var pendientes = (modelosPendientesCache || []).filter(function (m) {
			return coincideBusqueda(m, q);
		});
		var proyectados = (modelosProyectadosCache || []).filter(function (m) {
			if (idMarca > 0 && parseInt(m.id_marca, 10) !== idMarca) {
				return false;
			}
			return coincideBusqueda(m, q);
		});

		$("#chipPendientes").text("Pendientes (" + pendientes.length + ")");
		$("#chipProyectados").text("Proyectados (" + proyectados.length + ")");
		$("#chipTodos").text("Todos (" + (pendientes.length + proyectados.length) + ")");

		var html = [];
		var mostrarPend = filtroListaModelos !== "proyectados";
		var mostrarProy = filtroListaModelos !== "pendientes";
		var n = 0;

		if (mostrarProy && proyectados.length && filtroListaModelos === "todos") {
			html.push('<div class="proy-lista-grupo">Proyectados</div>');
		}
		if (mostrarProy) {
			proyectados.forEach(function (m) {
				html.push(htmlItemLista(m, "proyectado"));
				n++;
			});
		}
		if (mostrarPend && pendientes.length && filtroListaModelos === "todos") {
			html.push('<div class="proy-lista-grupo">Pendientes</div>');
		}
		if (mostrarPend) {
			pendientes.forEach(function (m) {
				html.push(htmlItemLista(m, "pendiente"));
				n++;
			});
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
			renderStats(resp.stats || {});
			modelosProyectadosCache = resp.modelos_proyectados || [];
			modelosPendientesCache = resp.modelos || [];
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
			proyAlert(status === "timeout"
				? "La carga del plan tardó demasiado."
				: "Error de red al cargar el plan");
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

	function fmtDelta(actual, anterior, etiqueta) {
		if (actual == null || anterior == null || !isFinite(actual) || !isFinite(anterior)) {
			return '<span class="text-muted">' +
				(etiqueta ? '<small class="proy-delta-lbl">' + etiqueta + "</small> " : "") +
				"—</span>";
		}
		var d = Number(actual) - Number(anterior);
		var pct = Number(anterior) !== 0 ? (d / Math.abs(Number(anterior))) * 100 : null;
		var cls = d > 0 ? "proy-delta-up" : (d < 0 ? "proy-delta-down" : "proy-delta-flat");
		var arrow = d > 0 ? "▲" : (d < 0 ? "▼" : "•");
		var pctTxt = pct == null ? "" : " (" + (pct > 0 ? "+" : "") + pct.toFixed(0) + "%)";
		return '<span class="' + cls + '">' +
			(etiqueta ? '<small class="proy-delta-lbl">' + etiqueta + "</small> " : "") +
			arrow + " " + (d > 0 ? "+" : "") + num(d, 0) + pctTxt + "</span>";
	}

	function histValorNum(detalle, offset) {
		var found = null;
		(detalle || []).forEach(function (h) {
			if (Number(h.offset) === offset) {
				found = h;
			}
		});
		if (!found || found.sin_tabla || found.unidades === null || found.unidades === undefined) {
			return null;
		}
		if (found.periodo_abierto) {
			return null;
		}
		return Number(found.unidades);
	}

	/** Valor histórico + Δ vs el año anterior (mismo mes). Hace 3 no compara. */
	function histConVsAnioAnt(detalle, offset, offsetPrev) {
		var udsHtml = histUds(detalle, offset);
		if (offsetPrev == null) {
			return udsHtml;
		}
		var actual = histValorNum(detalle, offset);
		var prev = histValorNum(detalle, offsetPrev);
		if (actual == null || prev == null) {
			return '<div class="proy-hist-anio-cell">' + udsHtml +
				'<div class="proy-hist-vs text-muted">vs ant. —</div></div>';
		}
		return '<div class="proy-hist-anio-cell">' + udsHtml +
			'<div class="proy-hist-vs">' + fmtDelta(actual, prev, "vs") + "</div></div>";
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
			$tb.append('<tr><td colspan="7" class="text-muted text-center">Sin historial estacional</td></tr>');
			return;
		}
		var prevSug = null;
		var firstSug = null;
		var lastSug = null;
		var totSug = 0;
		var nSug = 0;
		var totH1 = 0;
		var nH1 = 0;
		var totH2 = 0;
		var nH2 = 0;
		var totH3 = 0;
		var nH3 = 0;
		var totProm = 0;
		var nProm = 0;

		filas.forEach(function (f) {
			var sug = f.sugerencia || {};
			var sugCalc = sug.unidades != null ? Number(sug.unidades) : null;
			var sugTxt = sugCalc != null ? "<strong>" + num(sugCalc, 0) + "</strong>" : "—";
			if (sug.sin_historia) {
				sugTxt += ' <span class="text-muted">sin hist.</span>';
			}
			var h1 = histValorNum(f.historial, 1);
			var h2 = histValorNum(f.historial, 2);
			var h3 = histValorNum(f.historial, 3);
			$tb.append(
				"<tr><td><strong>" + f.periodo + "</strong></td>" +
				"<td>" + histConVsAnioAnt(f.historial, 1, 2) + "</td>" +
				"<td>" + histConVsAnioAnt(f.historial, 2, 3) + "</td>" +
				"<td>" + histConVsAnioAnt(f.historial, 3, null) + "</td>" +
				"<td>" + (sug.promedio_simple != null ? num(sug.promedio_simple, 1) : "—") + "</td>" +
				"<td>" + sugTxt + "</td>" +
				"<td>" + fmtDelta(sugCalc, prevSug) + "</td></tr>"
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
			if (h1 != null) {
				totH1 += h1;
				nH1++;
			}
			if (h2 != null) {
				totH2 += h2;
				nH2++;
			}
			if (h3 != null) {
				totH3 += h3;
				nH3++;
			}
			if (sug.promedio_simple != null) {
				totProm += Number(sug.promedio_simple);
				nProm++;
			}
		});

		$tf.html(
			"<tr class='proy-hist-total'>" +
			"<td><strong>Total periodo</strong></td>" +
			"<td><strong>" + (nH1 ? num(totH1, 0) : "—") + "</strong>" +
				(nH1 && nH2 ? '<div class="proy-hist-vs">' + fmtDelta(totH1, totH2, "vs") + "</div>" : "") +
			"</td>" +
			"<td><strong>" + (nH2 ? num(totH2, 0) : "—") + "</strong>" +
				(nH2 && nH3 ? '<div class="proy-hist-vs">' + fmtDelta(totH2, totH3, "vs") + "</div>" : "") +
			"</td>" +
			"<td><strong>" + (nH3 ? num(totH3, 0) : "—") + "</strong></td>" +
			"<td>" + (nProm ? num(totProm, 0) : "—") + "</td>" +
			"<td><strong>" + (nSug ? num(totSug, 0) : "—") + "</strong>" +
				(nSug && nH1 ? '<div class="proy-hist-vs">' + fmtDelta(totSug, totH1, "vs año") + "</div>" : "") +
			"</td>" +
			"<td>—</td>" +
			"</tr>"
		);

		if (firstSug != null && lastSug != null && filas.length > 1) {
			var dTot = lastSug - firstSug;
			var pctTot = firstSug !== 0 ? (dTot / Math.abs(firstSug)) * 100 : null;
			var sentido = dTot > 0 ? "crece" : (dTot < 0 ? "baja" : "se mantiene");
			var pctPart = pctTot == null ? "" : " (" + (pctTot > 0 ? "+" : "") + pctTot.toFixed(0) + "%)";
			$("#lblTendenciaHist").html(
				"Tendencia mes a mes en el plan: <strong>" + sentido + "</strong> " +
				num(firstSug, 0) + " → " + num(lastSug, 0) +
				" · Δ " + (dTot > 0 ? "+" : "") + num(dTot, 0) + pctPart +
				"."
			);
		} else {
			$("#lblTendenciaHist").text("");
		}

		if (nSug) {
			var vsAnioTxt = "";
			if (nH1) {
				var dAnio = totSug - totH1;
				var pctAnio = totH1 !== 0 ? (dAnio / Math.abs(totH1)) * 100 : null;
				var sentAnio = dAnio > 0 ? "arriba" : (dAnio < 0 ? "abajo" : "igual");
				vsAnioTxt = " · sugerencia <strong>" + sentAnio + "</strong> vs hace 1 año: " +
					num(totSug, 0) + " vs " + num(totH1, 0) +
					" · Δ " + (dAnio > 0 ? "+" : "") + num(dAnio, 0) +
					(pctAnio == null ? "" : " (" + (pctAnio > 0 ? "+" : "") + pctAnio.toFixed(0) + "%)");
			}
			$("#lblGlobalHist").html(
				"Global del periodo: sugerencia <strong>" + num(totSug, 0) + "</strong> uds" +
				vsAnioTxt + "."
			);
		} else {
			$("#lblGlobalHist").text("");
		}
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
		destroyMotivoPickers();
		var $tb = $("#tablaMesesModelo tbody");
		$tb.empty();
		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		var est = mapaEstacional(ctx);
		if (!(lineas || []).length) {
			$tb.append('<tr><td colspan="9" class="text-muted text-center">Sin líneas. Pulsa “Preparar meses”.</td></tr>');
			llenarSelectMesFactores([], { skipLoad: false });
			return;
		}
		lineas.forEach(function (l) {
			var editable = puedeEditar && l.estado_linea !== "CERRADO";
			var sugMes = Number(l.unidades_sugeridas) || 0;
			if (ctx && ctx.sugerencias && ctx.sugerencias[l.periodo] && ctx.sugerencias[l.periodo].unidades != null) {
				sugMes = Number(ctx.sugerencias[l.periodo].unidades) || 0;
			}
			var aj = Number(l.unidades_ajustes) || 0;
			var base = Math.max(0, sugMes + aj);
			var ofi = Number(l.unidades_oficiales) || 0;
			var nFact = Number(l.n_factores) || 0;
			var pct = "";
			if (base > 0) {
				pct = String(Math.round(((ofi - base) / base) * 100));
			}
			var requiereMotivo = desviacionRelevanteUi(sugMes, aj, ofi) && nFact <= 0;
			var f = est[l.periodo];
			var histTxt = f ? histStack(f.historial) : "—";
			var ajHtml = '<span class="' + (aj > 0 ? "proy-num-pos" : (aj < 0 ? "proy-num-neg" : "")) + '">' +
				(aj > 0 ? "+" : "") + num(aj, 0) + "</span>";
			if (nFact) {
				ajHtml += ' <span class="label label-info">' + nFact + "</span>";
			}
			var tr = $("<tr data-id='" + l.id + "' data-n-factores='" + nFact + "'>");
			if (requiereMotivo) {
				tr.addClass("proy-fila-desv");
			}
			tr.append($("<td>").html("<strong>" + l.periodo + "</strong>"));
			tr.append($("<td>").html(histTxt));
			tr.append($("<td>").html(
				num(sugMes, 0) +
				(ctx && ctx.sugerencias && ctx.sugerencias[l.periodo] && ctx.sugerencias[l.periodo].sin_historia
					? ' <span class="text-muted">sin hist.</span>' : "")
			));
			tr.append($("<td>").html(ajHtml));
			tr.append($("<td>").html(
				editable
					? '<input type="number" step="1" class="form-control input-sm inpPctMes" data-id="' +
						l.id + '" data-base="' + base + '" value="' + pct + '" style="width:64px;" title="% entero sobre sug.+factores">'
					: (pct === "" ? "—" : pct + "%")
			));
			tr.append($("<td>").html(
				editable
					? '<input type="number" min="0" step="10" class="form-control input-sm inpOficialModelo" data-id="' +
						l.id + '" data-base="' + base + '" data-sug="' + sugMes + '" data-aj="' + aj +
						'" value="' + ofi + '" style="width:100px;">'
					: String(ofi)
			));
			tr.append($("<td class='tdDesv'>").html(htmlCeldaDesv(sugMes, aj, ofi, nFact)));
			tr.append($("<td>").html(htmlMotivoSelect(l.id, l.observacion, editable, requiereMotivo)));
			tr.append($("<td>").html('<span class="label label-default">' + l.estado_linea + "</span>"));
			$tb.append(tr);
		});
		llenarSelectMesFactores(lineas, { skipLoad: !!mesFactorLineaId });
		initMotivoPickers();
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
			$sel.selectpicker({
				liveSearch: true,
				width: "100%",
				size: 8,
				title: "Seleccionar mes"
			});
			silenceMesSelect = false;
			$("#listaCatalogoChecks").html('<p class="text-muted">Prepara meses primero.</p>');
			limpiarPreviewFactores();
			return;
		}
		lineas.forEach(function (l) {
			$sel.append($("<option>").val(String(l.id)).text(l.periodo + " · " + l.estado_linea));
		});
		if (prev && $sel.find("option[value='" + prev + "']").length) {
			$sel.val(prev);
		} else {
			$sel.val(String(lineas[0].id));
		}
		mesFactorLineaId = mesSelectVal();
		$sel.selectpicker({
			liveSearch: true,
			width: "100%",
			size: 8,
			title: "Seleccionar mes"
		});
		$sel.selectpicker("val", mesFactorLineaId);
		silenceMesSelect = false;
		if (!opts.skipLoad) {
			cargarChecksMesActual();
		}
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
		$("#resumenFactorLinea").html(
			"<strong>" + (lin.periodo || "") + "</strong> · " +
			"Si aplicas estos factores: sugerencia " + num(sug, 0) +
			" " + (suma >= 0 ? "+" : "") + num(suma, 0) +
			" = <strong>" + num(resultado, 0) + "</strong>" +
			(deltaOfi === 0
				? " (igual al oficial actual)"
				: " · vs oficial: <span class='" + (deltaOfi > 0 ? "proy-num-pos" : "proy-num-neg") + "'>" +
					(deltaOfi > 0 ? "+" : "") + num(deltaOfi, 0) + "</span>")
		);
	}

	function cargarChecksMesActual() {
		var idLinea = mesFactorLineaId || mesSelectVal();
		if (!idLinea) {
			$("#listaCatalogoChecks").html('<p class="text-muted">Elige un mes.</p>');
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

	function renderChecksCatalogo(detalle) {
		var $box = $("#listaCatalogoChecks").empty();
		var lin = detalle.linea || {};
		var sug = Number(lin.unidades_sugeridas) || 0;
		var items = detalle.items || [];
		if (!items.length) {
			$box.html('<p class="text-muted">No hay factores. Créalos en <a href="index.php?ruta=proyeccion-comercial-factores">Factores de proyección</a>.</p>');
			actualizarPreviewFactores();
			return;
		}
		var puedeEditar = $("#proyPuedeEditar").val() === "1" && lin.estado_linea !== "CERRADO";
		items.forEach(function (it) {
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
				meta += " · " + num(it.impacto_pct_default, 2) + "%";
			}
			row.append(cb);
			row.append($("<span class='proy-check-title'>").text(it.titulo));
			row.append(
				$("<span class='proy-check-ajuste'>")
					.addClass(esPos ? "proy-num-pos" : (esNeg ? "proy-num-neg" : ""))
					.text(signo + " uds")
			);
			row.append($("<span class='proy-check-meta'>").text(meta));
			$box.append(row);
		});
		actualizarPreviewFactores();
	}

	function renderFactoresPorMes(rows) {
		factoresPorMesCache = rows || [];
		var $tb = $("#tablaFactoresPorMes tbody").empty();
		if (!factoresPorMesCache.length) {
			$tb.append('<tr><td colspan="6" class="text-muted text-center">Sin meses del modelo</td></tr>');
			return;
		}
		factoresPorMesCache.forEach(function (m) {
			var facts = m.factores || [];
			var lista;
			if (!facts.length) {
				lista = '<span class="text-muted">Sin factores</span>';
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
			var tr = $("<tr>");
			tr.append($("<td>").html("<strong>" + m.periodo + "</strong>"));
			tr.append($("<td>").html(lista));
			tr.append($("<td>").html(
				'<span class="' + (aj > 0 ? "proy-num-pos" : (aj < 0 ? "proy-num-neg" : "")) + '">' +
				(aj > 0 ? "+" : "") + num(aj, 0) + "</span>"
			));
			tr.append($("<td>").text(num(res, 0)));
			tr.append($("<td>").text(num(m.unidades_oficiales, 0)));
			tr.append($("<td>").html(
				'<button type="button" class="btn btn-xs btn-primary btnEditarFactoresMes" data-id="' +
				m.id_linea + '">Editar</button>'
			));
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
			(resp.plan.anio_desde + "-" + pad2(resp.plan.mes_desde)) +
			" → " + (resp.plan.anio_hasta + "-" + pad2(resp.plan.mes_hasta))
		);
		$("#kpiLista9").text(c.sin_lista9 ? "Sin lista 9" : moneda(c.precio_lista9));
		$("#kpiStock").text(num(c.inventario.stock_disponible, 0));
		$("#kpiProceso").text(num(c.inventario.en_proceso, 0));
		renderHistEstacional(c, resp.lineas || []);
		renderMesesModelo(resp.lineas || [], c);
		renderFactoresPorMes(resp.factores_por_mes || []);
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
		var $item = $(".proy-lista-item[data-modelo='" + modelo.replace(/'/g, "") + "']");
		$item.addClass("is-loading");
		$item.find(".btnListaModelo").prop("disabled", true);
		post("espacioModelo", {
			id_periodo: id,
			modelo: modelo,
			asegurar: asegurar ? 1 : 0
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo abrir el modelo");
				return;
			}
			renderEspacio(resp);
			cargarPendientesYStats();
		}).fail(function (xhr, status) {
			proyAlert(status === "timeout" ? "Timeout al abrir el modelo" : "Error de red");
		}).always(function () {
			$(".proy-lista-item").removeClass("is-loading");
			$(".proy-lista-item .btnListaModelo").prop("disabled", false);
			renderListaModelos();
		});
	}

	function redondeo10(n) {
		return Math.max(0, Math.round(Number(n) / 10) * 10);
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
	}

	function guardarModelo() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return;
		}
		var cambios = [];
		var faltanMotivo = [];
		$(".inpOficialModelo").each(function () {
			var idLinea = parseInt($(this).data("id"), 10);
			var ofi = parseInt($(this).val(), 10) || 0;
			var sug = Number($(this).data("sug")) || 0;
			var aj = Number($(this).data("aj")) || 0;
			var $tr = $(this).closest("tr");
			var nFact = Number($tr.data("nFactores")) || 0;
			var motivo = $.trim($tr.find(".selMotivoDesv").val() || "");
			var linea = null;
			(espacioActual.lineas || []).forEach(function (l) {
				if (String(l.id) === String(idLinea)) {
					linea = l;
				}
			});
			var item = { id: idLinea, unidades_oficiales: ofi };
			if (motivo) {
				item.observacion = motivo;
			} else if (linea && linea.observacion && !desviacionRelevanteUi(sug, aj, ofi)) {
				// mantiene observación previa si ya no hay desviación
				item.observacion = linea.observacion;
			} else if (!motivo && desviacionRelevanteUi(sug, aj, ofi) && nFact <= 0) {
				item.observacion = "";
			}
			cambios.push(item);
			if (desviacionRelevanteUi(sug, aj, ofi) && nFact <= 0 && !motivo) {
				faltanMotivo.push(linea ? linea.periodo : ("#" + idLinea));
			}
		});
		if (!cambios.length) {
			proyAlert("No hay meses editables", "warning");
			return;
		}
		if (faltanMotivo.length) {
			proyAlert(
				"Hay desviación >10% en " + faltanMotivo.join(", ") +
				". Elige un motivo en la columna Motivo (o agrega un factor).",
				"warning"
			);
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
		$("#resumenFactorLinea").html(
			"<strong>" + (lin.periodo || "") + "</strong> · Sug. " + num(lin.unidades_sugeridas, 0) +
			" · Factores " + num(detalle.suma_ajustes, 0) +
			" · Sug+ajustes " + num((lin.unidades_sugeridas || 0) + (detalle.suma_ajustes || 0), 0) +
			" · Oficial " + num(lin.unidades_oficiales, 0)
		);
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
		var $sel = $("#mesFactorSelect");
		$sel.val(mesFactorLineaId);
		if ($sel.data("selectpicker")) {
			$sel.selectpicker("val", mesFactorLineaId);
		}
		cargarChecksMesActual();
		$('a[href="#tabFact"]').tab("show");
		setTimeout(function () {
			var $row = $("#tablaFactoresPorMes");
			if ($row.length) {
				$("html, body").animate({ scrollTop: $row.offset().top - 80 }, 250);
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
		initFechas();
		initSelectsModelo();
		// mesFactorSelect se (re)inicializa en llenarSelectMesFactores
		cargarCatalogo();
		cargarTiposFactor();
		listarPlanes().always(function () {
			var planIni = parseInt($("#proyPlanInicial").val() || "0", 10);
			var modeloIni = $.trim($("#proyModeloInicial").val() || "");
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


		$(document).on("input", ".inpOficialModelo", function () {
			syncPctFromOficial($(this));
		});
		$(document).on("input", ".inpPctMes", function () {
			syncOficialFromPct($(this));
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
			cargarChecksMesActual();
		});
		$(document).on("change", ".chkCatalogoLinea", function () {
			toggleCatalogoCheck($(this));
		});
		$(document).on("click", ".btnEditarFactoresMes", function () {
			abrirFactores($(this).data("id"));
			$("html, body").animate({ scrollTop: $("#listaCatalogoChecks").offset().top - 90 }, 250);
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
