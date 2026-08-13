(function ($) {
	"use strict";

	var lineasCache = [];
	var planActual = null;

	function pad2(n) {
		n = String(n);
		return n.length < 2 ? "0" + n : n;
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

	function moneda(v) {
		if (v === null || v === undefined || v === "") {
			return "—";
		}
		return "S/ " + Number(v).toLocaleString("es-PE", {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		});
	}

	function proyAlert(mensaje, tipo) {
		tipo = tipo || "info";
		if (typeof swal === "function") {
			swal({ type: tipo, title: mensaje || "", confirmButtonText: "Cerrar" });
			return;
		}
		window.alert(mensaje || "");
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

	function llenarFiltroMeses(plan) {
		var $sel = $("#filtroMesPlan");
		var prev = $sel.val();
		$sel.find("option:not([value=''])").remove();
		if (!plan) {
			return;
		}
		var anio = Number(plan.anio_desde);
		var mes = Number(plan.mes_desde);
		var hastaClave = Number(plan.anio_hasta) * 100 + Number(plan.mes_hasta);
		while ((anio * 100 + mes) <= hastaClave) {
			var val = anio + "-" + pad2(mes);
			$sel.append($("<option>").val(val).text(val));
			mes++;
			if (mes > 12) {
				mes = 1;
				anio++;
			}
		}
		if (prev) {
			$sel.val(prev);
		}
	}

	function pintarCabecera(plan) {
		planActual = plan;
		$("#lblPlanMasivaBarra").html(
			"<strong>#" + plan.id + "</strong> " + (plan.nombre || "") +
			" · " + plan.estado +
			" · " + pad2(plan.mes_desde) + "/" + plan.anio_desde +
			" → " + pad2(plan.mes_hasta) + "/" + plan.anio_hasta
		);
		$("#btnIrPorModelo").attr(
			"href",
			"index.php?ruta=proyeccion-comercial-modelos&plan=" + encodeURIComponent(plan.id)
		);
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
			$tb.append('<tr><td colspan="13" class="text-center text-muted">Sin líneas. Usa “Generar lote”.</td></tr>');
			return;
		}

		var puedeEditar = $("#proyPuedeEditar").val() === "1";
		var idPlan = $("#proyIdPeriodo").val();
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
			var urlModelo = "index.php?ruta=proyeccion-comercial-modelos&plan=" +
				encodeURIComponent(idPlan) + "&modelo=" + encodeURIComponent(l.modelo);
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
			tr.append($("<td>").html(
				'<a class="btn btn-xs btn-primary" href="' + urlModelo + '">Modelo</a>'
			));
			$tb.append(tr);
		});
	}

	function abrirPlan(id) {
		$("#proyIdPeriodo").val(id);
		var data = { id_periodo: id, q: $("#proyQ").val() || "" };
		var mesSel = $("#filtroMesPlan").val();
		if (mesSel) {
			var p = mesSel.split("-");
			data.anio = parseInt(p[0], 10);
			data.mes = parseInt(p[1], 10);
		}
		var idMarca = parseInt($("#proyMarca").val() || "0", 10);
		var marcaTxt = $.trim($("#proyMarca option:selected").text() || "");
		return post("cargarPlan", data).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo abrir el plan", "error");
				return;
			}
			pintarCabecera(resp.plan);
			llenarFiltroMeses(resp.plan);
			if (mesSel) {
				$("#filtroMesPlan").val(mesSel);
			}
			var lineas = resp.lineas || [];
			if (idMarca > 0 && marcaTxt && marcaTxt !== "Todas") {
				lineas = lineas.filter(function (l) {
					return String(l.marca || "") === marcaTxt;
				});
			}
			renderLineas(lineas);
		}).fail(function (xhr, status) {
			proyAlert(status === "timeout" ? "La carga tardó demasiado." : "Error de red", "error");
		});
	}

	function filtroOk() {
		var idMarca = parseInt($("#proyMarca").val() || "0", 10);
		var q = $.trim($("#proyQ").val() || "");
		if (idMarca <= 0 && q.length < 1) {
			proyAlert("Elige una marca o escribe un modelo para generar el lote.", "warning");
			return false;
		}
		return true;
	}

	function generarLineas() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			proyAlert("Plan inválido", "warning");
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
				proyAlert((resp && resp.mensaje) || "No se pudo generar", "error");
				return;
			}
			proyAlert(
				"Creadas: " + (resp.creadas || 0) +
				" | Actualizadas: " + (resp.actualizadas || 0) +
				" | Omitidas: " + (resp.omitidas || 0),
				"success"
			);
			abrirPlan(id);
		}).fail(function (xhr, status) {
			proyAlert(status === "timeout" ? "Timeout al generar" : "Error de red", "error");
		}).always(function () {
			$btn.prop("disabled", false).html('<i class="fa fa-magic"></i> Generar lote');
		});
	}

	function guardarLineas() {
		var id = $("#proyIdPeriodo").val();
		if (!id) {
			return;
		}
		var cambios = [];
		$(".inpOficial").each(function () {
			var lid = parseInt($(this).data("id"), 10);
			var val = parseInt($(this).val(), 10);
			if (!isFinite(lid) || !isFinite(val) || val < 0) {
				return;
			}
			cambios.push({ id: lid, unidades_oficiales: val });
		});
		if (!cambios.length) {
			proyAlert("No hay cambios editables en la grilla", "warning");
			return;
		}
		post("guardarLineas", {
			id_periodo: id,
			cambios: JSON.stringify(cambios),
			motivo: ""
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo guardar", "error");
				return;
			}
			proyAlert("Guardadas: " + (resp.guardadas || 0), "success");
			abrirPlan(id);
		}).fail(function () {
			proyAlert("Error de red al guardar", "error");
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
			}).fail(function () {
				proyAlert("Error de red al publicar", "error");
			});
		});
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

	$(function () {
		if (!$(".proyeccion-masiva-wrap").length) {
			return;
		}
		var planIni = parseInt($("#proyPlanInicial").val() || "0", 10);
		if (planIni <= 0) {
			window.location = "index.php?ruta=proyeccion-comercial-modelos";
			return;
		}
		cargarCatalogo().always(function () {
			abrirPlan(planIni);
		});

		$("#btnGenerarLineas").on("click", generarLineas);
		$("#btnGuardarLineas").on("click", guardarLineas);
		$("#btnPublicarMes").on("click", function () { publicar(false); });
		$("#btnPublicarTodo").on("click", function () { publicar(true); });
		$("#btnRecargarPlan").on("click", function () {
			abrirPlan($("#proyIdPeriodo").val());
		});
		$("#filtroMesPlan, #proyMarca").on("change", function () {
			abrirPlan($("#proyIdPeriodo").val());
		});
		var qTimer = null;
		$("#proyQ").on("input", function () {
			clearTimeout(qTimer);
			qTimer = setTimeout(function () {
				abrirPlan($("#proyIdPeriodo").val());
			}, 400);
		});
	});
})(jQuery);
