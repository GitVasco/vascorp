(function ($) {
	"use strict";

	var tiposCache = [];
	var catalogoCache = [];

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
			timeout: 60000,
			data: data
		});
	}

	function tipoNombre(codigo) {
		var n = codigo;
		tiposCache.forEach(function (t) {
			if (t.codigo === codigo) {
				n = t.nombre;
			}
		});
		return n;
	}

	function llenarTipos(tipos) {
		tiposCache = tipos || [];
		var $sel = $("#catTipo").empty();
		tiposCache.forEach(function (t) {
			$sel.append($("<option>").val(t.codigo).text(t.nombre));
		});
	}

	function limpiarForm() {
		$("#catId").val("");
		$("#catTitulo").val("");
		$("#catAjuste").val("0");
		$("#catPct").val("");
		$("#catDesc").val("");
		$("#tituloFormFactor").text("Nuevo factor");
		if (tiposCache.length) {
			$("#catTipo").val(tiposCache[0].codigo);
		}
	}

	function renderTabla(rows) {
		var $tb = $("#tablaCatalogoFactores tbody").empty();
		var activos = (rows || []).filter(function (r) {
			return String(r.activo) === "1" || r.activo === 1 || r.activo === true;
		});
		if (!activos.length) {
			$tb.append('<tr><td colspan="6" class="text-muted text-center">Sin factores. Crea el primero o ejecuta el SQL de ejemplos.</td></tr>');
			return;
		}
		var puedeEditar = $("#factPuedeEditar").val() === "1";
		activos.forEach(function (c) {
			var tr = $("<tr>");
			tr.append($("<td>").text(tipoNombre(c.tipo)));
			tr.append($("<td>").html("<strong>" + $("<div>").text(c.titulo).html() + "</strong>"));
			tr.append($("<td>").text((Number(c.ajuste_unidades_default) >= 0 ? "+" : "") + num(c.ajuste_unidades_default, 0)));
			tr.append($("<td>").text(c.impacto_pct_default == null ? "—" : num(c.impacto_pct_default, 2) + "%"));
			tr.append($("<td>").text(c.descripcion || "—"));
			var acc = "—";
			if (puedeEditar) {
				acc =
					'<button type="button" class="btn btn-xs btn-default btnEditarFact" data-id="' + c.id + '">Editar</button> ' +
					'<button type="button" class="btn btn-xs btn-danger btnQuitarFact" data-id="' + c.id + '">Quitar</button>';
			}
			tr.append($("<td>").html(acc));
			$tb.append(tr);
		});
	}

	function cargar() {
		return post("listarCatalogo", { todos: 1 }).done(function (resp) {
			if (!resp || !resp.ok) {
				$("#tablaCatalogoFactores tbody").html(
					'<tr><td colspan="6" class="text-danger text-center">' +
					((resp && resp.mensaje) || "¿Ejecutaste el SQL del catálogo?") + "</td></tr>"
				);
				return;
			}
			llenarTipos(resp.tipos || []);
			catalogoCache = resp.catalogo || [];
			renderTabla(catalogoCache);
		}).fail(function () {
			$("#tablaCatalogoFactores tbody").html(
				'<tr><td colspan="6" class="text-danger text-center">Error de red / SQL pendiente</td></tr>'
			);
		});
	}

	function guardar() {
		post("guardarCatalogo", {
			id: $("#catId").val() || 0,
			tipo: $("#catTipo").val(),
			titulo: $("#catTitulo").val(),
			descripcion: $("#catDesc").val(),
			ajuste_unidades_default: $("#catAjuste").val(),
			impacto_pct_default: $("#catPct").val()
		}).done(function (resp) {
			if (!resp || !resp.ok) {
				proyAlert((resp && resp.mensaje) || "No se pudo guardar");
				return;
			}
			catalogoCache = resp.catalogo || [];
			renderTabla(catalogoCache);
			limpiarForm();
		});
	}

	function quitar(id) {
		proyConfirm("Quitar factor", "¿Quitar este factor del catálogo?").then(function (ok) {
			if (!ok) {
				return;
			}
			post("desactivarCatalogo", { id: id }).done(function (resp) {
				if (!resp || !resp.ok) {
					proyAlert((resp && resp.mensaje) || "No se pudo quitar", "error");
					return;
				}
				catalogoCache = resp.catalogo || [];
				renderTabla(catalogoCache);
				limpiarForm();
			});
		});
	}

	$(function () {
		if (!$(".proyeccion-factores-wrap").length) {
			return;
		}
		cargar();
		$("#btnGuardarCatalogoFactor").on("click", guardar);
		$("#btnLimpiarCatalogoFactor").on("click", limpiarForm);
		$(document).on("click", ".btnEditarFact", function () {
			var id = $(this).data("id");
			var row = null;
			catalogoCache.forEach(function (c) {
				if (String(c.id) === String(id)) {
					row = c;
				}
			});
			if (!row) {
				return;
			}
			$("#catId").val(row.id);
			$("#catTipo").val(row.tipo);
			$("#catTitulo").val(row.titulo);
			$("#catAjuste").val(row.ajuste_unidades_default);
			$("#catPct").val(row.impacto_pct_default != null ? row.impacto_pct_default : "");
			$("#catDesc").val(row.descripcion || "");
			$("#tituloFormFactor").text("Editar factor");
			$("html, body").animate({ scrollTop: $("#formCatalogoFactor").offset().top - 80 }, 250);
		});
		$(document).on("click", ".btnQuitarFact", function () {
			quitar($(this).data("id"));
		});
	});
})(jQuery);
