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
			$ua = (float) $a["urg_max"];
			$ub = (float) $b["urg_max"];
			if ($ua == $ub) {
				return 0;
			}
			return ($ua < $ub) ? -1 : 1;
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

	static public function ctrMapaClavesActivas($lista)
	{
		$map = array();
		if (!is_array($lista)) {
			return $map;
		}
		foreach ($lista as $clave) {
			$map[(string) $clave] = true;
		}
		return $map;
	}

	static public function ctrClaveModeloColor($modelo, $codColor)
	{
		return trim((string) $modelo) . "|" . trim((string) $codColor);
	}

	static public function ctrListarCandidatos($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarCandidatos($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		$enPrioridad = self::ctrMapaClavesActivas(
			ModeloProgramacionTallerSemana::mdlClavesPrioridadActivas()
		);
		$enProgramado = self::ctrMapaClavesActivas(
			ModeloProgramacionTallerSemana::mdlClavesProgramadasActivas()
		);
		foreach ($lista as $fila) {
			$clave = self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]);
			if (isset($enPrioridad[$clave]) || isset($enProgramado[$clave])) {
				continue;
			}
			// Nivel lo elige quien prioriza; no se asigna automáticamente
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

	static public function ctrEnriquecerPriorizado($fila)
	{
		if (!is_array($fila)) {
			return null;
		}
		$map = self::ctrMapaNiveles();
		$nid = isset($fila["nivel"]) ? $fila["nivel"] : "";
		$fila["nivel_nombre"] = isset($map[$nid]["nombre"]) ? $map[$nid]["nombre"] : $nid;
		$fila["nivel_color"] = isset($map[$nid]["color"]) ? $map[$nid]["color"] : "#CCCCCC";
		$fila["nivel_orden"] = isset($map[$nid]["orden"]) ? (int) $map[$nid]["orden"] : 99;
		$fila["alm_corte_vivo"] = isset($fila["alm_corte_vivo"]) ? (int) $fila["alm_corte_vivo"] : 0;
		$fila["ord_corte_vivo"] = isset($fila["ord_corte_vivo"]) ? (int) $fila["ord_corte_vivo"] : 0;
		$fila["saldo_vivo"] = isset($fila["saldo_vivo"]) ? (int) $fila["saldo_vivo"] : 0;
		$fila["cod_sector_resuelto"] = isset($fila["cod_sector"]) ? $fila["cod_sector"] : "";
		return $fila;
	}

	static public function ctrFilaPriorizadoPorId($id)
	{
		$row = ModeloProgramacionTallerSemana::mdlMostrarPrioridad($id);
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
		return self::ctrEnriquecerPriorizado($row);
	}

	static public function ctrListarPriorizados($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarPriorizados($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		$enProgramado = self::ctrMapaClavesActivas(
			ModeloProgramacionTallerSemana::mdlClavesProgramadasActivas()
		);
		foreach ($lista as $fila) {
			$clave = self::ctrClaveModeloColor(
				isset($fila["modelo"]) ? $fila["modelo"] : "",
				isset($fila["cod_color"]) ? $fila["cod_color"] : ""
			);
			if (isset($enProgramado[$clave])) {
				continue;
			}
			$out[] = self::ctrEnriquecerPriorizado($fila);
		}
		usort($out, function ($a, $b) {
			$oa = isset($a["nivel_orden"]) ? (int) $a["nivel_orden"] : 99;
			$ob = isset($b["nivel_orden"]) ? (int) $b["nivel_orden"] : 99;
			if ($oa !== $ob) {
				return $oa - $ob;
			}
			$sa = self::ctrClaveOrdenSector(isset($a["cod_sector"]) ? $a["cod_sector"] : "");
			$sb = self::ctrClaveOrdenSector(isset($b["cod_sector"]) ? $b["cod_sector"] : "");
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

	static public function ctrPriorizarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$codColor = trim(isset($post["cod_color"]) ? $post["cod_color"] : "");
		$codSector = trim(isset($post["cod_sector"]) ? $post["cod_sector"] : "");
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : 0;
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : "");
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");

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
		$desdeDevolver = !empty($post["desde_devolver"]);
		if (!$desdeDevolver) {
			$enProgramado = self::ctrMapaClavesActivas(
				ModeloProgramacionTallerSemana::mdlClavesProgramadasActivas()
			);
			$clave = self::ctrClaveModeloColor($art["modelo"], $art["cod_color"]);
			if (isset($enProgramado[$clave])) {
				return array(
					"ok" => false,
					"mensaje" => "Ya está en Ya programado; quítalo de esa semana o devuélvelo desde No ejecutado"
				);
			}
		}
		$disponible = (int) $art["saldo_disponible"];
		if ($disponible < 1) {
			return array("ok" => false, "mensaje" => "Sin saldo en almacén de corte ni en órdenes de corte");
		}
		if ($cantidad > $disponible) {
			return array("ok" => false, "mensaje" => "La cantidad supera el saldo disponible ({$disponible})");
		}

		$datosBase = array(
			"cantidad" => $cantidad,
			"cod_sector" => $codSector,
			"nivel" => $nivel,
			"observacion" => $observacion !== "" ? $observacion : null,
			"saldo_alm_corte" => (int) $art["alm_corte"],
			"saldo_ord_corte" => (int) $art["ord_corte"],
			"urg_plan" => $art["urg_plan"],
			"color" => $art["color"],
			"nombre" => $art["nombre"],
			"usumod" => self::ctrUsuarioSesion()
		);

		$idExiste = ModeloProgramacionTallerSemana::mdlIdPrioridadExistente(
			$art["modelo"],
			$art["cod_color"],
			$codSector,
			false
		);
		if ($idExiste > 0) {
			$existente = ModeloProgramacionTallerSemana::mdlMostrarPrioridad($idExiste);
			$datosBase["id"] = $idExiste;
			if ($existente && (int) $existente["estado"] === 0) {
				if (ModeloProgramacionTallerSemana::mdlReactivarPrioridad($datosBase) === "ok") {
					return array(
						"ok" => true,
						"mensaje" => "Priorizado",
						"id" => $idExiste,
						"row" => self::ctrFilaPriorizadoPorId($idExiste)
					);
				}
				return array("ok" => false, "mensaje" => "No se pudo guardar la prioridad");
			}
			if (ModeloProgramacionTallerSemana::mdlEditarPrioridad($datosBase) === "ok") {
				return array(
					"ok" => true,
					"mensaje" => "Prioridad actualizada",
					"id" => $idExiste,
					"row" => self::ctrFilaPriorizadoPorId($idExiste)
				);
			}
			return array("ok" => false, "mensaje" => "No se pudo actualizar la prioridad");
		}

		$datos = array_merge($datosBase, array(
			"modelo" => $art["modelo"],
			"cod_color" => $art["cod_color"],
			"usureg" => self::ctrUsuarioSesion()
		));
		if (ModeloProgramacionTallerSemana::mdlCrearPrioridad($datos) === "ok") {
			$idNuevo = ModeloProgramacionTallerSemana::mdlIdPrioridadExistente(
				$art["modelo"],
				$art["cod_color"],
				$codSector,
				true
			);
			return array(
				"ok" => true,
				"mensaje" => "Priorizado (sin semana aún)",
				"id" => $idNuevo,
				"row" => self::ctrFilaPriorizadoPorId($idNuevo)
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo priorizar (¿tabla creada?)");
	}

	static public function ctrPriorizarLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$raw = isset($post["items"]) ? $post["items"] : "[]";
		$items = is_string($raw) ? json_decode($raw, true) : $raw;
		if (!is_array($items) || !count($items)) {
			return array("ok" => false, "mensaje" => "No hay ítems para priorizar");
		}
		$ok = 0;
		$errores = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$resp = self::ctrPriorizarAjax(array(
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
			return array("ok" => false, "mensaje" => "No se pudo priorizar ninguno", "errores" => $errores, "ok_count" => 0);
		}
		$mensaje = $ok . " priorizado(s)";
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con error";
		}
		return array("ok" => true, "mensaje" => $mensaje, "ok_count" => $ok, "errores" => $errores);
	}

	static public function ctrEditarPrioridadAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$row = ModeloProgramacionTallerSemana::mdlMostrarPrioridad($id);
		if (!$row || (int) $row["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "Prioridad no encontrada");
		}
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : $row["nivel"]);
		$map = self::ctrMapaNiveles();
		if (!isset($map[$nivel])) {
			return array("ok" => false, "mensaje" => "Nivel no válido");
		}
		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : (int) $row["cantidad"];
		if ($art) {
			$disp = (int) $art["saldo_disponible"];
			if ($disp > 0 && $cantidad > $disp) {
				$cantidad = $disp;
			}
			if ($disp < 1) {
				return array("ok" => false, "mensaje" => "Sin saldo vivo; quítalo de la prioridad");
			}
		}
		$datos = array(
			"id" => $id,
			"cantidad" => $cantidad > 0 ? $cantidad : (int) $row["cantidad"],
			"cod_sector" => $row["cod_sector"],
			"nivel" => $nivel,
			"observacion" => isset($post["observacion"]) ? trim($post["observacion"]) : $row["observacion"],
			"saldo_alm_corte" => $art ? (int) $art["alm_corte"] : (int) $row["saldo_alm_corte"],
			"saldo_ord_corte" => $art ? (int) $art["ord_corte"] : (int) $row["saldo_ord_corte"],
			"urg_plan" => $art ? $art["urg_plan"] : $row["urg_plan"],
			"usumod" => self::ctrUsuarioSesion()
		);
		if (ModeloProgramacionTallerSemana::mdlEditarPrioridad($datos) === "ok") {
			return array(
				"ok" => true,
				"mensaje" => "Nivel actualizado",
				"id" => $id,
				"row" => self::ctrFilaPriorizadoPorId($id)
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo actualizar");
	}

	static public function ctrEliminarPrioridadAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$prev = self::ctrFilaPriorizadoPorId($id);
		if ($id < 1 || !$prev) {
			return array("ok" => false, "mensaje" => "Prioridad no encontrada");
		}
		if (ModeloProgramacionTallerSemana::mdlEliminarPrioridad($id, self::ctrUsuarioSesion()) === "ok") {
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
				"mensaje" => "Quitado de prioridad",
				"id" => $id,
				"candidato" => $candidato
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo quitar");
	}

	/** Destina una prioridad a una semana (programa + saca de la bandeja). */
	static public function ctrDestinarSemanaAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$idPri = isset($post["id_prioridad"]) ? (int) $post["id_prioridad"] : 0;
		$pri = ModeloProgramacionTallerSemana::mdlMostrarPrioridad($idPri);
		if (!$pri || (int) $pri["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "Prioridad no encontrada");
		}
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($pri["modelo"], $pri["cod_color"]);
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : (int) $pri["cantidad"];
		if ($art) {
			$disp = (int) $art["saldo_disponible"];
			if ($disp < 1) {
				return array("ok" => false, "mensaje" => "Sin saldo vivo; no se puede destinar");
			}
			if ($cantidad > $disp) {
				$cantidad = $disp;
			}
		}
		$resp = self::ctrProgramarAjax(array(
			"anio" => $anio,
			"semana" => $semana,
			"modelo" => $pri["modelo"],
			"cod_color" => $pri["cod_color"],
			"cod_sector" => $pri["cod_sector"],
			"cantidad" => $cantidad,
			"nivel" => $pri["nivel"],
			"observacion" => isset($pri["observacion"]) ? $pri["observacion"] : ""
		));
		if (empty($resp["ok"])) {
			return $resp;
		}
		ModeloProgramacionTallerSemana::mdlEliminarPrioridad($idPri, self::ctrUsuarioSesion());
		$resp["mensaje"] = "Destinado a semana {$semana}";
		$resp["id_prioridad"] = $idPri;
		return $resp;
	}

	static public function ctrDestinarLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$raw = isset($post["ids"]) ? $post["ids"] : "[]";
		$ids = is_string($raw) ? json_decode($raw, true) : $raw;
		if (!is_array($ids) || !count($ids)) {
			return array("ok" => false, "mensaje" => "No hay ítems seleccionados");
		}
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$ok = 0;
		$errores = array();
		$rows = array();
		foreach ($ids as $id) {
			$resp = self::ctrDestinarSemanaAjax(array(
				"id_prioridad" => (int) $id,
				"anio" => $anio,
				"semana" => $semana
			));
			if (!empty($resp["ok"])) {
				$ok++;
				if (!empty($resp["row"])) {
					$rows[] = $resp["row"];
				}
			} else {
				$errores[] = "id " . (int) $id . ": " . (isset($resp["mensaje"]) ? $resp["mensaje"] : "Error");
			}
		}
		if ($ok < 1) {
			return array("ok" => false, "mensaje" => "No se pudo destinar ninguno", "errores" => $errores);
		}
		$mensaje = $ok . " destinado(s) a semana " . $semana;
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con error";
		}
		return array("ok" => true, "mensaje" => $mensaje, "ok_count" => $ok, "errores" => $errores, "rows" => $rows);
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
		if ($rango) {
			$rango["pasada"] = ModeloProgramacionTallerSemana::mdlSemanaYaPasada(
				(int) $rango["anio"],
				(int) $rango["semana"]
			) ? 1 : 0;
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
		if (ModeloProgramacionTallerSemana::mdlSemanaYaPasada($anio, $semana)) {
			return array("ok" => false, "mensaje" => "No se puede programar en una semana que ya pasó");
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
			"anio" => $rango["anio"],
			"semana" => $rango["semana"],
			"fecha_inicio" => $rango["fecha_inicio"],
			"fecha_fin" => $rango["fecha_fin"],
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
		$cantidad = (int) $row["cantidad"];
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : $row["nivel"]);
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");
		$anio = isset($post["anio"]) ? (int) $post["anio"] : (int) $row["anio"];
		$semana = isset($post["semana"]) ? (int) $post["semana"] : (int) $row["semana"];

		$map = self::ctrMapaNiveles();
		if ($codSector === "" || $cantidad < 1 || !isset($map[$nivel])) {
			return array("ok" => false, "mensaje" => "Datos incompletos o inválidos");
		}

		$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($anio, $semana);
		if (!$rango) {
			return array("ok" => false, "mensaje" => "Semana no válida");
		}
		$cambiaSemana = $anio !== (int) $row["anio"] || $semana !== (int) $row["semana"];
		if ($cambiaSemana && ModeloProgramacionTallerSemana::mdlSemanaYaPasada($anio, $semana)) {
			return array("ok" => false, "mensaje" => "No se puede mover a una semana que ya pasó");
		}

		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);

		if ($cambiaSemana || $codSector !== $row["cod_sector"]) {
			$otro = ModeloProgramacionTallerSemana::mdlIdExistente(
				$anio,
				$semana,
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
			"anio" => $rango["anio"],
			"semana" => $rango["semana"],
			"fecha_inicio" => $rango["fecha_inicio"],
			"fecha_fin" => $rango["fecha_fin"],
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

	static public function ctrListarNoEjecutados($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarNoEjecutados($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		foreach ($lista as $fila) {
			$out[] = self::ctrEnriquecerProgramado($fila);
		}
		return $out;
	}

	static public function ctrContarNoEjecutados()
	{
		return ModeloProgramacionTallerSemana::mdlContarNoEjecutados();
	}

	/**
	 * Mueve una programación de semana pasada (no ejecutada) a una semana actual/futura.
	 */
	static public function ctrMoverNoEjecutadoAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$row = ModeloProgramacionTallerSemana::mdlMostrar($id);
		if (!$row || (int) $row["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}
		if (!ModeloProgramacionTallerSemana::mdlSemanaYaPasada((int) $row["anio"], (int) $row["semana"])) {
			return array("ok" => false, "mensaje" => "Solo aplica a semanas ya pasadas");
		}

		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($anio, $semana);
		if (!$rango) {
			return array("ok" => false, "mensaje" => "Semana destino no válida");
		}
		if (ModeloProgramacionTallerSemana::mdlSemanaYaPasada($anio, $semana)) {
			return array("ok" => false, "mensaje" => "No se puede mover a una semana que ya pasó");
		}

		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);
		$disp = $art ? (int) $art["saldo_disponible"] : 0;
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Ya no hay saldo vivo; no se puede mover");
		}
		$cantidad = min((int) $row["cantidad"], $disp);

		// Si ya existe en destino: actualizar destino y quitar origen
		$idDest = ModeloProgramacionTallerSemana::mdlIdExistente(
			$anio,
			$semana,
			$row["modelo"],
			$row["cod_color"],
			$row["cod_sector"],
			false
		);
		$usuario = self::ctrUsuarioSesion();
		if ($idDest > 0 && $idDest !== $id) {
			$exist = ModeloProgramacionTallerSemana::mdlMostrar($idDest);
			$datosDest = array(
				"id" => $idDest,
				"anio" => $rango["anio"],
				"semana" => $rango["semana"],
				"fecha_inicio" => $rango["fecha_inicio"],
				"fecha_fin" => $rango["fecha_fin"],
				"cantidad" => $cantidad,
				"cod_sector" => $row["cod_sector"],
				"nivel" => $row["nivel"],
				"observacion" => isset($row["observacion"]) ? $row["observacion"] : null,
				"saldo_alm_corte" => $art ? (int) $art["alm_corte"] : (int) $row["saldo_alm_corte"],
				"saldo_ord_corte" => $art ? (int) $art["ord_corte"] : (int) $row["saldo_ord_corte"],
				"urg_plan" => $art ? $art["urg_plan"] : $row["urg_plan"],
				"usumod" => $usuario
			);
			if ($exist && (int) $exist["estado"] === 0) {
				$datosReac = array_merge($datosDest, array(
					"modelo" => $row["modelo"],
					"cod_color" => $row["cod_color"],
					"color" => $row["color"],
					"nombre" => $row["nombre"],
					"fecha_inicio" => $rango["fecha_inicio"],
					"fecha_fin" => $rango["fecha_fin"]
				));
				if (ModeloProgramacionTallerSemana::mdlReactivar($datosReac) !== "ok") {
					return array("ok" => false, "mensaje" => "No se pudo mover");
				}
			} elseif (ModeloProgramacionTallerSemana::mdlEditar($datosDest) !== "ok") {
				return array("ok" => false, "mensaje" => "No se pudo actualizar destino");
			}
			ModeloProgramacionTallerSemana::mdlEliminar($id, $usuario);
			return array(
				"ok" => true,
				"mensaje" => "Movido a semana {$semana} (fusionado)",
				"id" => $idDest,
				"row" => self::ctrFilaProgramadoPorId($idDest)
			);
		}

		if (ModeloProgramacionTallerSemana::mdlMoverSemana(array(
			"id" => $id,
			"anio" => $rango["anio"],
			"semana" => $rango["semana"],
			"fecha_inicio" => $rango["fecha_inicio"],
			"fecha_fin" => $rango["fecha_fin"],
			"cantidad" => $cantidad,
			"saldo_alm_corte" => $art ? (int) $art["alm_corte"] : (int) $row["saldo_alm_corte"],
			"saldo_ord_corte" => $art ? (int) $art["ord_corte"] : (int) $row["saldo_ord_corte"],
			"urg_plan" => $art ? $art["urg_plan"] : $row["urg_plan"],
			"usumod" => $usuario
		)) === "ok") {
			return array(
				"ok" => true,
				"mensaje" => "Movido a semana {$semana}",
				"id" => $id,
				"row" => self::ctrFilaProgramadoPorId($id)
			);
		}
		return array("ok" => false, "mensaje" => "No se pudo mover");
	}

	static public function ctrMoverNoEjecutadoLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$raw = isset($post["ids"]) ? $post["ids"] : "[]";
		$ids = is_string($raw) ? json_decode($raw, true) : $raw;
		if (!is_array($ids) || !count($ids)) {
			return array("ok" => false, "mensaje" => "No hay ítems seleccionados");
		}
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$semana = isset($post["semana"]) ? (int) $post["semana"] : 0;
		$ok = 0;
		$errores = array();
		foreach ($ids as $id) {
			$resp = self::ctrMoverNoEjecutadoAjax(array(
				"id" => (int) $id,
				"anio" => $anio,
				"semana" => $semana
			));
			if (!empty($resp["ok"])) {
				$ok++;
			} else {
				$errores[] = "id " . (int) $id . ": " . (isset($resp["mensaje"]) ? $resp["mensaje"] : "Error");
			}
		}
		if ($ok < 1) {
			return array("ok" => false, "mensaje" => "No se pudo mover ninguno", "errores" => $errores);
		}
		$mensaje = $ok . " movido(s) a semana " . $semana;
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con error";
		}
		return array("ok" => true, "mensaje" => $mensaje, "ok_count" => $ok, "errores" => $errores);
	}

	/** Saca de la semana pasada y vuelve a la bandeja de prioridad. */
	static public function ctrDevolverPrioridadAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$row = ModeloProgramacionTallerSemana::mdlMostrar($id);
		if (!$row || (int) $row["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}
		if (!ModeloProgramacionTallerSemana::mdlSemanaYaPasada((int) $row["anio"], (int) $row["semana"])) {
			return array("ok" => false, "mensaje" => "Solo aplica a semanas ya pasadas");
		}
		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($row["modelo"], $row["cod_color"]);
		$disp = $art ? (int) $art["saldo_disponible"] : 0;
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Sin saldo vivo; quítalo de la programación");
		}
		$cantidad = min((int) $row["cantidad"], $disp);
		$pri = self::ctrPriorizarAjax(array(
			"modelo" => $row["modelo"],
			"cod_color" => $row["cod_color"],
			"cod_sector" => $row["cod_sector"],
			"cantidad" => $cantidad,
			"nivel" => $row["nivel"],
			"observacion" => isset($row["observacion"]) ? $row["observacion"] : "",
			"desde_devolver" => 1
		));
		if (empty($pri["ok"])) {
			return $pri;
		}
		ModeloProgramacionTallerSemana::mdlEliminar($id, self::ctrUsuarioSesion());
		return array(
			"ok" => true,
			"mensaje" => "Devuelto a prioridad",
			"id" => $id,
			"prioridad" => isset($pri["row"]) ? $pri["row"] : null
		);
	}

	static public function ctrDevolverPrioridadLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}
		$raw = isset($post["ids"]) ? $post["ids"] : "[]";
		$ids = is_string($raw) ? json_decode($raw, true) : $raw;
		if (!is_array($ids) || !count($ids)) {
			return array("ok" => false, "mensaje" => "No hay ítems seleccionados");
		}
		$ok = 0;
		$errores = array();
		foreach ($ids as $id) {
			$resp = self::ctrDevolverPrioridadAjax(array("id" => (int) $id));
			if (!empty($resp["ok"])) {
				$ok++;
			} else {
				$errores[] = "id " . (int) $id . ": " . (isset($resp["mensaje"]) ? $resp["mensaje"] : "Error");
			}
		}
		if ($ok < 1) {
			return array("ok" => false, "mensaje" => "No se pudo devolver ninguno", "errores" => $errores);
		}
		$mensaje = $ok . " devuelto(s) a prioridad";
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con error";
		}
		return array("ok" => true, "mensaje" => $mensaje, "ok_count" => $ok, "errores" => $errores);
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
