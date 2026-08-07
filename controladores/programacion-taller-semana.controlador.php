<?php

class ControladorProgramacionTallerSemana
{

	static private function ctrUsuarioSesion()
	{
		if (isset($_SESSION["usuario"]) && $_SESSION["usuario"] !== "") {
			return (string) $_SESSION["usuario"];
		}
		if (isset($_SESSION["id"])) {
			return (string) $_SESSION["id"];
		}
		return "sistema";
	}

	static public function ctrPuedeProduccion()
	{
		return isset($_SESSION["produccion"]) && (int) $_SESSION["produccion"] === 1;
	}

	static public function ctrNiveles()
	{
		return ModeloProgramacionTallerSemana::mdlCargarNiveles();
	}

	static public function ctrMapaNiveles()
	{
		$cfg = self::ctrNiveles();
		$map = array();
		foreach ($cfg["niveles"] as $n) {
			$map[$n["id"]] = $n;
		}
		return $map;
	}

	/**
	 * Clasifica nivel según JSON + urg_plan + estado artículo.
	 */
	static public function ctrClasificarNivel($urgPlan, $estadoArticulo = "")
	{
		$cfg = self::ctrNiveles();
		$default = isset($cfg["nivel_default"]) ? $cfg["nivel_default"] : "prioridad";
		$estadoNorm = strtoupper(trim((string) $estadoArticulo));
		$estadoNorm = str_replace(array("Ñ", "Á", "É", "Í", "Ó", "Ú"), array("N", "A", "E", "I", "O", "U"), $estadoNorm);

		foreach ($cfg["niveles"] as $n) {
			if (empty($n["por_estado_articulo"]) || !is_array($n["por_estado_articulo"])) {
				continue;
			}
			foreach ($n["por_estado_articulo"] as $alias) {
				$aliasN = strtoupper(trim((string) $alias));
				$aliasN = str_replace(array("Ñ", "Á", "É", "Í", "Ó", "Ú"), array("N", "A", "E", "I", "O", "U"), $aliasN);
				if ($aliasN !== "" && (strpos($estadoNorm, $aliasN) !== false || $estadoNorm === $aliasN)) {
					return $n["id"];
				}
			}
		}

		if ($urgPlan === null || $urgPlan === "" || !is_numeric($urgPlan)) {
			return "critico";
		}
		$urg = (float) $urgPlan;

		$porUmbral = array();
		foreach ($cfg["niveles"] as $n) {
			if (!isset($n["urg_max"]) || $n["urg_max"] === null || $n["urg_max"] === "") {
				continue;
			}
			$porUmbral[] = $n;
		}
		usort($porUmbral, function ($a, $b) {
			return ((int) $a["orden"]) - ((int) $b["orden"]);
		});

		foreach ($porUmbral as $n) {
			if ($urg <= (float) $n["urg_max"]) {
				return $n["id"];
			}
		}
		return $default;
	}

	static public function ctrEnriquecerConNivel($fila)
	{
		$urg = isset($fila["urg_plan"]) ? $fila["urg_plan"] : null;
		$est = isset($fila["estado_articulo"]) ? $fila["estado_articulo"] : "";
		$nivelId = self::ctrClasificarNivel($urg, $est);
		$map = self::ctrMapaNiveles();
		$fila["nivel"] = $nivelId;
		$fila["nivel_nombre"] = isset($map[$nivelId]["nombre"]) ? $map[$nivelId]["nombre"] : $nivelId;
		$fila["nivel_color"] = isset($map[$nivelId]["color"]) ? $map[$nivelId]["color"] : "#CCCCCC";
		$fila["nivel_orden"] = isset($map[$nivelId]["orden"]) ? (int) $map[$nivelId]["orden"] : 99;
		return $fila;
	}

	static public function ctrListarCandidatos($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarCandidatos($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		foreach ($lista as $fila) {
			// Nivel lo elige quien programa; no se asigna automáticamente
			$fila["nivel"] = "";
			$fila["nivel_nombre"] = "";
			$fila["nivel_color"] = "";
			$out[] = $fila;
		}
		usort($out, function ($a, $b) {
			$cmp = strcmp((string) $a["modelo"], (string) $b["modelo"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			$cmpColor = strcmp((string) $a["cod_color"], (string) $b["cod_color"]);
			if ($cmpColor !== 0) {
				return $cmpColor;
			}
			$ua = $a["urg_plan"] === null ? 999 : (float) $a["urg_plan"];
			$ub = $b["urg_plan"] === null ? 999 : (float) $b["urg_plan"];
			return ($ua < $ub) ? -1 : (($ua > $ub) ? 1 : 0);
		});
		return $out;
	}

	/** Enriquece una fila de programación para la UI (nivel, saldos, consumido). */
	static public function ctrEnriquecerProgramado($fila)
	{
		if (!is_array($fila)) {
			return null;
		}
		$map = self::ctrMapaNiveles();
		$nid = isset($fila["nivel"]) ? $fila["nivel"] : "";
		$fila["nivel_nombre"] = isset($map[$nid]["nombre"]) ? $map[$nid]["nombre"] : $nid;
		$fila["nivel_color"] = isset($map[$nid]["color"]) ? $map[$nid]["color"] : "#CCCCCC";
		$fila["nivel_orden"] = isset($map[$nid]["orden"]) ? (int) $map[$nid]["orden"] : 99;
		$saldoVivo = isset($fila["saldo_vivo"]) ? (int) $fila["saldo_vivo"] : 0;
		$fila["alm_corte_vivo"] = isset($fila["alm_corte_vivo"]) ? (int) $fila["alm_corte_vivo"] : 0;
		$fila["ord_corte_vivo"] = isset($fila["ord_corte_vivo"]) ? (int) $fila["ord_corte_vivo"] : 0;
		$fila["saldo_vivo"] = $saldoVivo;
		$fila["consumido"] = $saldoVivo <= 0 ? 1 : 0;
		return $fila;
	}

	/** Fila lista para UI a partir del id (sin barrer todos los programados). */
	static public function ctrFilaProgramadoPorId($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$row = ModeloProgramacionTallerSemana::mdlMostrar($id);
		if (!$row || (int) $row["estado"] !== 1) {
			return null;
		}
		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);
		$row["alm_corte_vivo"] = $art ? (int) $art["alm_corte"] : 0;
		$row["ord_corte_vivo"] = $art ? (int) $art["ord_corte"] : 0;
		$row["saldo_vivo"] = $art ? (int) $art["saldo_disponible"] : 0;
		if (empty($row["nom_sector"]) && !empty($row["cod_sector"]) && class_exists("ControladorSectores")) {
			$sec = ControladorSectores::ctrMostrarSectores($row["cod_sector"]);
			if (is_array($sec)) {
				$row["nom_sector"] = isset($sec["nom_sector"]) ? $sec["nom_sector"] : "";
				$row["color_taller"] = isset($sec["color"]) ? $sec["color"] : null;
			}
		}
		return self::ctrEnriquecerProgramado($row);
	}

	static public function ctrListarProgramados($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarProgramados($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		foreach ($lista as $fila) {
			$out[] = self::ctrEnriquecerProgramado($fila);
		}
		usort($out, function ($a, $b) {
			$sa = self::ctrClaveOrdenSector($a["cod_sector"]);
			$sb = self::ctrClaveOrdenSector($b["cod_sector"]);
			$cmp = strnatcasecmp($sa, $sb);
			if ($cmp !== 0) {
				return $cmp;
			}
			$cmp = strcmp((string) $a["modelo"], (string) $b["modelo"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			return strcmp((string) $a["cod_color"], (string) $b["cod_color"]);
		});
		return $out;
	}

	static public function ctrEstadisticasSemana($anio, $semana)
	{
		$stats = ModeloProgramacionTallerSemana::mdlEstadisticasSemana((int) $anio, (int) $semana);
		if (!empty($stats["por_taller"]) && is_array($stats["por_taller"])) {
			usort($stats["por_taller"], function ($a, $b) {
				return strnatcasecmp(
					self::ctrClaveOrdenSector(isset($a["cod_sector"]) ? $a["cod_sector"] : ""),
					self::ctrClaveOrdenSector(isset($b["cod_sector"]) ? $b["cod_sector"] : "")
				);
			});
		}
		if (!empty($stats["por_programar"]["por_taller"]) && is_array($stats["por_programar"]["por_taller"])) {
			usort($stats["por_programar"]["por_taller"], function ($a, $b) {
				return strnatcasecmp(
					self::ctrClaveOrdenSector(isset($a["cod_sector"]) ? $a["cod_sector"] : ""),
					self::ctrClaveOrdenSector(isset($b["cod_sector"]) ? $b["cod_sector"] : "")
				);
			});
		}
		return $stats;
	}

	/** Orden de taller ignorando la T inicial del código (T1, T2, T10…). */
	static public function ctrClaveOrdenSector($codSector)
	{
		$c = strtoupper(trim((string) $codSector));
		if ($c !== "" && ($c[0] === "T" || $c[0] === "t")) {
			$c = substr($c, 1);
		}
		return $c === false ? "" : $c;
	}

	static public function ctrInfoSemana($anio, $semana)
	{
		$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($anio, $semana);
		if (!$rango) {
			$act = ModeloProgramacionTallerSemana::mdlSemanaActual();
			$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($act["anio"], $act["semana"]);
		}
		return $rango;
	}

	static public function ctrProgramarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$codColor = trim(isset($post["cod_color"]) ? $post["cod_color"] : "");
		$codSector = trim(isset($post["cod_sector"]) ? $post["cod_sector"] : "");
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : 0;
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : "");
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");

		$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($anio, $semana);
		if (!$rango) {
			return array("ok" => false, "mensaje" => "Semana no válida");
		}
		if ($modelo === "") {
			return array("ok" => false, "mensaje" => "Modelo obligatorio");
		}
		if ($codSector === "") {
			return array("ok" => false, "mensaje" => "Taller obligatorio");
		}
		if ($cantidad < 1) {
			return array("ok" => false, "mensaje" => "La cantidad debe ser mayor a 0");
		}

		$map = self::ctrMapaNiveles();
		if ($nivel === "" || !isset($map[$nivel])) {
			return array("ok" => false, "mensaje" => "Nivel de urgencia no válido");
		}

		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($modelo, $codColor);
		if (!$art) {
			return array("ok" => false, "mensaje" => "Modelo/color no encontrado");
		}

		$disponible = (int) $art["saldo_disponible"];
		if ($disponible < 1) {
			return array("ok" => false, "mensaje" => "Sin saldo en almacén de corte ni en órdenes de corte");
		}
		if ($cantidad > $disponible) {
			return array("ok" => false, "mensaje" => "La cantidad supera el saldo disponible ({$disponible})");
		}

		$idExiste = ModeloProgramacionTallerSemana::mdlIdExistente(
			$anio,
			$semana,
			$art["modelo"],
			$art["cod_color"],
			$codSector,
			false
		);
		$datosBase = array(
			"cantidad" => $cantidad,
			"cod_sector" => $codSector,
			"nivel" => $nivel,
			"observacion" => $observacion !== "" ? $observacion : null,
			"saldo_alm_corte" => (int) $art["alm_corte"],
			"saldo_ord_corte" => (int) $art["ord_corte"],
			"urg_plan" => $art["urg_plan"]
		);

		if ($idExiste > 0) {
			$existente = ModeloProgramacionTallerSemana::mdlMostrar($idExiste);
			$datosBase["id"] = $idExiste;
			$datosBase["usumod"] = self::ctrUsuarioSesion();
			if ($existente && (int) $existente["estado"] === 0) {
				$datosReac = array_merge($datosBase, array(
					"modelo" => $art["modelo"],
					"cod_color" => $art["cod_color"],
					"color" => $art["color"],
					"nombre" => $art["nombre"],
					"fecha_inicio" => $rango["fecha_inicio"],
					"fecha_fin" => $rango["fecha_fin"]
				));
				if (ModeloProgramacionTallerSemana::mdlReactivar($datosReac) === "ok") {
					return array(
						"ok" => true,
						"mensaje" => "Modelo/color programado en la semana {$semana}",
						"id" => $idExiste,
						"row" => self::ctrFilaProgramadoPorId($idExiste)
					);
				}
				return array("ok" => false, "mensaje" => "No se pudo guardar");
			}
			if (ModeloProgramacionTallerSemana::mdlEditar($datosBase) === "ok") {
				return array(
					"ok" => true,
					"mensaje" => "Programación actualizada",
					"id" => $idExiste,
					"row" => self::ctrFilaProgramadoPorId($idExiste)
				);
			}
			return array("ok" => false, "mensaje" => "No se pudo actualizar");
		}

		$datos = array_merge($datosBase, array(
			"anio" => $rango["anio"],
			"semana" => $rango["semana"],
			"fecha_inicio" => $rango["fecha_inicio"],
			"fecha_fin" => $rango["fecha_fin"],
			"modelo" => $art["modelo"],
			"cod_color" => $art["cod_color"],
			"color" => $art["color"],
			"nombre" => $art["nombre"],
			"usureg" => self::ctrUsuarioSesion()
		));

		if (ModeloProgramacionTallerSemana::mdlCrear($datos) === "ok") {
			$idNuevo = ModeloProgramacionTallerSemana::mdlIdExistente(
				$anio,
				$semana,
				$art["modelo"],
				$art["cod_color"],
				$codSector,
				true
			);
			return array(
				"ok" => true,
				"mensaje" => "Modelo/color programado en la semana {$semana}",
				"id" => $idNuevo,
				"row" => self::ctrFilaProgramadoPorId($idNuevo)
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo guardar");
	}

	static public function ctrEditarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$row = ModeloProgramacionTallerSemana::mdlMostrar($id);
		if (!$row || (int) $row["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}

		$codSector = trim(isset($post["cod_sector"]) ? $post["cod_sector"] : $row["cod_sector"]);
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : (int) $row["cantidad"];
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : $row["nivel"]);
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");

		$map = self::ctrMapaNiveles();
		if ($codSector === "" || $cantidad < 1 || !isset($map[$nivel])) {
			return array("ok" => false, "mensaje" => "Datos incompletos o inválidos");
		}

		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);
		$disponible = $art ? (int) $art["saldo_disponible"] : 0;
		// Si ya está consumido (sin saldo), solo permitir mantener o bajar la cantidad programada
		if ($disponible < 1) {
			if ($cantidad > (int) $row["cantidad"]) {
				return array("ok" => false, "mensaje" => "Consumido: sin saldo vivo; no se puede aumentar la cantidad");
			}
		} elseif ($cantidad > $disponible) {
			return array("ok" => false, "mensaje" => "La cantidad supera el saldo disponible ({$disponible})");
		}

		// Si cambian taller, validar unicidad
		if ($codSector !== $row["cod_sector"]) {
			$otro = ModeloProgramacionTallerSemana::mdlIdExistente(
				(int) $row["anio"],
				(int) $row["semana"],
				$row["modelo"],
				$row["cod_color"],
				$codSector
			);
			if ($otro > 0 && $otro !== $id) {
				return array("ok" => false, "mensaje" => "Ya existe ese modelo/color en ese taller para la semana");
			}
		}

		$datos = array(
			"id" => $id,
			"cantidad" => $cantidad,
			"cod_sector" => $codSector,
			"nivel" => $nivel,
			"observacion" => $observacion !== "" ? $observacion : null,
			"saldo_alm_corte" => $art ? (int) $art["alm_corte"] : (int) $row["saldo_alm_corte"],
			"saldo_ord_corte" => $art ? (int) $art["ord_corte"] : (int) $row["saldo_ord_corte"],
			"urg_plan" => $art ? $art["urg_plan"] : $row["urg_plan"],
			"usumod" => self::ctrUsuarioSesion()
		);

		if (ModeloProgramacionTallerSemana::mdlEditar($datos) === "ok") {
			return array(
				"ok" => true,
				"mensaje" => "Programación actualizada",
				"id" => $id,
				"row" => self::ctrFilaProgramadoPorId($id)
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo actualizar");
	}

	static public function ctrEliminarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$prev = self::ctrFilaProgramadoPorId($id);
		if ($id < 1 || !$prev) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}
		if (ModeloProgramacionTallerSemana::mdlEliminar($id, self::ctrUsuarioSesion()) === "ok") {
			$candidato = ModeloProgramacionTallerSemana::mdlColorParaProgramar($prev["modelo"], $prev["cod_color"]);
			if (is_array($candidato)) {
				$candidato["nivel"] = "";
				$candidato["nivel_nombre"] = "";
				$candidato["nivel_color"] = "";
				$candidato["cod_sector_resuelto"] = isset($candidato["cod_sector_resuelto"])
					? $candidato["cod_sector_resuelto"]
					: (isset($prev["cod_sector"]) ? $prev["cod_sector"] : "");
				if (empty($candidato["nom_sector"]) && !empty($prev["nom_sector"])) {
					$candidato["nom_sector"] = $prev["nom_sector"];
				}
				if (empty($candidato["color_taller"]) && !empty($prev["color_taller"])) {
					$candidato["color_taller"] = $prev["color_taller"];
				}
			}
			return array(
				"ok" => true,
				"mensaje" => "Programación eliminada",
				"id" => $id,
				"row" => $prev,
				"candidato" => $candidato
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo eliminar");
	}

	/**
	 * Programación en lote: items = [{modelo, cod_color, cod_sector, cantidad, nivel}, ...]
	 */
	static public function ctrProgramarLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$raw = isset($post["items"]) ? $post["items"] : "[]";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}
		if (!is_array($items) || !count($items)) {
			return array("ok" => false, "mensaje" => "No hay ítems para programar");
		}

		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$ok = 0;
		$errores = array();

		foreach ($items as $i => $item) {
			if (!is_array($item)) {
				continue;
			}
			$resp = self::ctrProgramarAjax(array(
				"anio" => $anio,
				"semana" => $semana,
				"modelo" => isset($item["modelo"]) ? $item["modelo"] : "",
				"cod_color" => isset($item["cod_color"]) ? $item["cod_color"] : "",
				"cod_sector" => isset($item["cod_sector"]) ? $item["cod_sector"] : "",
				"cantidad" => isset($item["cantidad"]) ? $item["cantidad"] : 0,
				"nivel" => isset($item["nivel"]) ? $item["nivel"] : "",
				"observacion" => isset($item["observacion"]) ? $item["observacion"] : ""
			));
			if (!empty($resp["ok"])) {
				$ok++;
			} else {
				$modelo = isset($item["modelo"]) ? $item["modelo"] : "?";
				$color = isset($item["cod_color"]) ? $item["cod_color"] : "";
				$msg = isset($resp["mensaje"]) ? $resp["mensaje"] : "Error";
				$errores[] = $modelo . "/" . $color . ": " . $msg;
			}
		}

		if ($ok < 1) {
			return array(
				"ok" => false,
				"mensaje" => "No se pudo programar ninguno",
				"errores" => $errores,
				"ok_count" => 0
			);
		}

		$mensaje = $ok . " programado(s)";
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con error";
		}
		return array(
			"ok" => true,
			"mensaje" => $mensaje,
			"ok_count" => $ok,
			"errores" => $errores
		);
	}
}
