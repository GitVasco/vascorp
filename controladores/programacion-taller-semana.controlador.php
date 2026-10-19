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

	static public function ctrClaveModeloColor($modelo, $codColor)
	{
		return trim((string) $modelo) . "|" . trim((string) $codColor);
	}

	static private $cacheSaldosOc = null;

	/** Saldo por OC de todos los modelo/color (una sola vez por request). */
	static public function ctrSaldosOc()
	{
		if (self::$cacheSaldosOc === null) {
			self::$cacheSaldosOc = ModeloProgramacionTallerSemana::mdlSaldosPorOc();
		}
		return self::$cacheSaldosOc;
	}

	/** null / "" = fila legado sin OC. */
	static private function ctrNormalizarOc($valor)
	{
		if ($valor === null || $valor === "") {
			return null;
		}
		return (int) $valor;
	}

	static private function ctrSaldoDeMapa($mapa, $clave, $ordencorte)
	{
		$vacio = array(
			"ordencorte" => $ordencorte,
			"alm_corte" => 0,
			"ord_corte" => 0,
			"saldo" => 0,
			"fecha_oc" => null,
			"tallas" => array()
		);
		if (!isset($mapa[$clave])) {
			return $vacio;
		}
		if ($ordencorte === null) {
			// Legado sin OC: total del modelo/color
			foreach ($mapa[$clave] as $s) {
				$vacio["alm_corte"] += $s["alm_corte"];
				$vacio["ord_corte"] += $s["ord_corte"];
				$vacio["saldo"] += $s["saldo"];
				foreach ($s["tallas"] as $art => $t) {
					if (!isset($vacio["tallas"][$art])) {
						$vacio["tallas"][$art] = array("alm_corte" => 0, "ord_corte" => 0);
					}
					$vacio["tallas"][$art]["alm_corte"] += $t["alm_corte"];
					$vacio["tallas"][$art]["ord_corte"] += $t["ord_corte"];
				}
			}
			return $vacio;
		}
		return isset($mapa[$clave][$ordencorte]) ? $mapa[$clave][$ordencorte] : $vacio;
	}

	/** Saldo vivo de un modelo/color en una OC (consulta acotada a ese modelo/color). */
	static public function ctrSaldoOc($modelo, $codColor, $ordencorte)
	{
		$mapa = ModeloProgramacionTallerSemana::mdlSaldosPorOc($modelo, $codColor);
		return self::ctrSaldoDeMapa(
			$mapa,
			self::ctrClaveModeloColor($modelo, $codColor),
			self::ctrNormalizarOc($ordencorte)
		);
	}

	/** Pone en la fila el saldo vivo de su propia OC (no el total del modelo/color). */
	static private function ctrAplicarSaldoOc($fila, $mapa)
	{
		$oc = self::ctrNormalizarOc(isset($fila["ordencorte"]) ? $fila["ordencorte"] : null);
		$s = self::ctrSaldoDeMapa($mapa, self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]), $oc);
		$fila["alm_corte_vivo"] = (int) $s["alm_corte"];
		$fila["ord_corte_vivo"] = (int) $s["ord_corte"];
		$fila["saldo_vivo"] = (int) $s["saldo"];
		$fila["fecha_oc"] = $s["fecha_oc"];
		return $fila;
	}

	/** Fecha de OC para filas cuya OC ya no tiene saldo (no vienen en el mapa de saldos). */
	static private function ctrCompletarFechasOc($filas)
	{
		$faltan = array();
		foreach ($filas as $f) {
			if (empty($f["fecha_oc"]) && isset($f["ordencorte"]) && (int) $f["ordencorte"] > 0) {
				$faltan[] = (int) $f["ordencorte"];
			}
		}
		if (!count($faltan)) {
			return $filas;
		}
		$fechas = ModeloProgramacionTallerSemana::mdlFechasOc($faltan);
		foreach ($filas as $i => $f) {
			if (empty($f["fecha_oc"]) && isset($f["ordencorte"]) && isset($fechas[(int) $f["ordencorte"]])) {
				$filas[$i]["fecha_oc"] = $fechas[(int) $f["ordencorte"]];
			}
		}
		return $filas;
	}

	/** Candidato con saldo en una OC concreta a partir de la fila agregada del modelo/color. */
	static private function ctrFilaCandidatoOc($fila, $saldoOc)
	{
		$fila["saldo_total"] = (int) $fila["saldo_disponible"];
		$fila["ordencorte"] = (int) $saldoOc["ordencorte"];
		$fila["fecha_oc"] = $saldoOc["fecha_oc"];
		$fila["alm_corte"] = (int) $saldoOc["alm_corte"];
		$fila["ord_corte"] = (int) $saldoOc["ord_corte"];
		$fila["saldo_disponible"] = (int) $saldoOc["saldo"];
		// Nivel lo elige quien prioriza; no se asigna automáticamente
		$fila["nivel"] = "";
		$fila["nivel_nombre"] = "";
		$fila["nivel_color"] = "";
		return $fila;
	}

	/**
	 * Candidatos: una fila por modelo/color/OC con saldo vivo que no esté ya priorizada ni programada.
	 * Una OC nueva siempre aparece por separado, aunque el modelo/color tenga otra OC programada.
	 */
	static public function ctrListarCandidatos($filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarCandidatos($filtros);
		$out = array();
		if (!is_array($lista)) {
			return $out;
		}
		$mapa = self::ctrSaldosOc();
		$ocupadas = ModeloProgramacionTallerSemana::mdlClavesOcOcupadas();
		foreach ($lista as $fila) {
			$clave = self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]);
			if (!isset($mapa[$clave])) {
				continue;
			}
			foreach ($mapa[$clave] as $oc => $s) {
				if ((int) $s["saldo"] < 1 || isset($ocupadas[$clave . "|" . $oc])) {
					continue;
				}
				$out[] = self::ctrFilaCandidatoOc($fila, $s);
			}
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
			return (int) $a["ordencorte"] - (int) $b["ordencorte"];
		});
		return $out;
	}

	/** Candidato de un modelo/color/OC tras quitarlo de prioridad o de la semana (null si ya no aplica). */
	static private function ctrCandidatoOc($prev)
	{
		$oc = self::ctrNormalizarOc(isset($prev["ordencorte"]) ? $prev["ordencorte"] : null);
		if ($oc === null) {
			return null;
		}
		$art = ModeloProgramacionTallerSemana::mdlColorParaProgramar($prev["modelo"], $prev["cod_color"]);
		if (!is_array($art)) {
			return null;
		}
		$clave = self::ctrClaveModeloColor($art["modelo"], $art["cod_color"]);
		$ocupadas = ModeloProgramacionTallerSemana::mdlClavesOcOcupadas();
		if (isset($ocupadas[$clave . "|" . $oc])) {
			return null;
		}
		$s = self::ctrSaldoOc($art["modelo"], $art["cod_color"], $oc);
		if ((int) $s["saldo"] < 1) {
			return null;
		}
		$candidato = self::ctrFilaCandidatoOc($art, $s);
		if (empty($candidato["cod_sector_resuelto"]) && !empty($prev["cod_sector"])) {
			$candidato["cod_sector_resuelto"] = $prev["cod_sector"];
		}
		if (empty($candidato["nom_sector"]) && !empty($prev["nom_sector"])) {
			$candidato["nom_sector"] = $prev["nom_sector"];
		}
		if (empty($candidato["color_taller"]) && !empty($prev["color_taller"])) {
			$candidato["color_taller"] = $prev["color_taller"];
		}
		return $candidato;
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
		$row = self::ctrAplicarSaldoOc($row, ModeloProgramacionTallerSemana::mdlSaldosPorOc($row["modelo"], $row["cod_color"]));
		$filas = self::ctrCompletarFechasOc(array($row));
		$row = $filas[0];
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
		$mapa = self::ctrSaldosOc();
		foreach (self::ctrCompletarFechasOc(array_map(function ($f) use ($mapa) {
			return self::ctrAplicarSaldoOc($f, $mapa);
		}, $lista)) as $fila) {
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
		$ordencorte = self::ctrNormalizarOc(isset($post["ordencorte"]) ? $post["ordencorte"] : null);
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : 0;
		$nivel = trim(isset($post["nivel"]) ? $post["nivel"] : "");
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");

		if ($modelo === "") {
			return array("ok" => false, "mensaje" => "Modelo obligatorio");
		}
		if ($ordencorte === null) {
			return array("ok" => false, "mensaje" => "Falta la orden de corte; recarga la página");
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
		$saldo = self::ctrSaldoOc($art["modelo"], $art["cod_color"], $ordencorte);
		$disponible = (int) $saldo["saldo"];
		if ($disponible < 1) {
			return array("ok" => false, "mensaje" => "La OC {$ordencorte} ya no tiene saldo en corte ni en orden de corte");
		}

		$idExiste = ModeloProgramacionTallerSemana::mdlIdPrioridadExistente(
			$art["modelo"],
			$art["cod_color"],
			$codSector,
			false,
			$ordencorte
		);
		$desdeDevolver = !empty($post["desde_devolver"]);
		$ocupadas = ModeloProgramacionTallerSemana::mdlClavesOcOcupadas();
		$claveOc = self::ctrClaveModeloColor($art["modelo"], $art["cod_color"]) . "|" . $ordencorte;
		if (isset($ocupadas[$claveOc])) {
			$ocup = $ocupadas[$claveOc];
			if ($ocup["tipo"] === "programado" && !$desdeDevolver) {
				return array(
					"ok" => false,
					"mensaje" => "La OC {$ordencorte} ya está programada en la semana {$ocup["semana"]}"
				);
			}
			if ($ocup["tipo"] === "prioridad" && $ocup["id"] !== $idExiste) {
				return array("ok" => false, "mensaje" => "La OC {$ordencorte} ya está en la bandeja de prioridad");
			}
		}
		if ($cantidad > $disponible) {
			$cantidad = $disponible;
		}

		$datosBase = array(
			"cantidad" => $cantidad,
			"cod_sector" => $codSector,
			"nivel" => $nivel,
			"observacion" => $observacion !== "" ? $observacion : null,
			"saldo_alm_corte" => (int) $saldo["alm_corte"],
			"saldo_ord_corte" => (int) $saldo["ord_corte"],
			"urg_plan" => $art["urg_plan"],
			"color" => $art["color"],
			"nombre" => $art["nombre"],
			"usumod" => self::ctrUsuarioSesion()
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
			"ordencorte" => $ordencorte,
			"usureg" => self::ctrUsuarioSesion()
		));
		if (ModeloProgramacionTallerSemana::mdlCrearPrioridad($datos) === "ok") {
			$idNuevo = ModeloProgramacionTallerSemana::mdlIdPrioridadExistente(
				$art["modelo"],
				$art["cod_color"],
				$codSector,
				true,
				$ordencorte
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
				"ordencorte" => isset($item["ordencorte"]) ? $item["ordencorte"] : null,
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
		$saldo = self::ctrSaldoOc($row["modelo"], $row["cod_color"], $row["ordencorte"]);
		$cantidad = isset($post["cantidad"]) ? (int) $post["cantidad"] : (int) $row["cantidad"];
		$disp = (int) $saldo["saldo"];
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Su OC ya no tiene saldo; quítalo de la prioridad");
		}
		if ($cantidad > $disp) {
			$cantidad = $disp;
		}
		$datos = array(
			"id" => $id,
			"cantidad" => $cantidad > 0 ? $cantidad : (int) $row["cantidad"],
			"cod_sector" => $row["cod_sector"],
			"nivel" => $nivel,
			"observacion" => isset($post["observacion"]) ? trim($post["observacion"]) : $row["observacion"],
			"saldo_alm_corte" => (int) $saldo["alm_corte"],
			"saldo_ord_corte" => (int) $saldo["ord_corte"],
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
			$candidato = self::ctrCandidatoOc($prev);
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
		$saldo = self::ctrSaldoOc($pri["modelo"], $pri["cod_color"], $pri["ordencorte"]);
		$disp = (int) $saldo["saldo"];
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Su OC ya no tiene saldo; no se puede destinar");
		}
		// La cantidad de la prioridad es una foto del día que se priorizó; se destina el saldo vivo de su OC
		$cantidad = isset($post["cantidad"]) ? min((int) $post["cantidad"], $disp) : $disp;
		$resp = self::ctrProgramarAjax(array(
			"anio" => $anio,
			"semana" => $semana,
			"modelo" => $pri["modelo"],
			"cod_color" => $pri["cod_color"],
			"ordencorte" => $pri["ordencorte"],
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
		$row = self::ctrAplicarSaldoOc($row, ModeloProgramacionTallerSemana::mdlSaldosPorOc($row["modelo"], $row["cod_color"]));
		$filas = self::ctrCompletarFechasOc(array($row));
		$row = $filas[0];
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
		$mapa = self::ctrSaldosOc();
		foreach (self::ctrCompletarFechasOc(array_map(function ($f) use ($mapa) {
			return self::ctrAplicarSaldoOc($f, $mapa);
		}, $lista)) as $fila) {
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

	/** $filas: programados ya enriquecidos de esa semana (para no volver a consultarlos). */
	static public function ctrEstadisticasSemana($anio, $semana, $filas = null)
	{
		$stats = ModeloProgramacionTallerSemana::mdlEstadisticasSemana((int) $anio, (int) $semana);
		if ($filas === null) {
			$filas = self::ctrListarProgramados(array("anio" => (int) $anio, "semana" => (int) $semana));
		}
		// Pendiente / consumido según el saldo vivo de la OC de cada línea
		$tot = array("pendientes_lineas" => 0, "consumidos_lineas" => 0, "pendientes_cantidad" => 0, "consumidos_cantidad" => 0);
		foreach ($filas as $f) {
			$suf = !empty($f["consumido"]) ? "consumidos" : "pendientes";
			$tot[$suf . "_lineas"] += 1;
			$tot[$suf . "_cantidad"] += (int) $f["cantidad"];
		}
		$stats["totales"] = array_merge(is_array($stats["totales"]) ? $stats["totales"] : array(), $tot);
		$stats["por_programar"] = self::ctrPendienteProgramarSemana((int) $anio, (int) $semana);
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

	/**
	 * OC con saldo vivo que no tienen línea activa en la semana, agrupadas por taller sugerido.
	 * @return array{totales:array,por_taller:array}
	 */
	static private function ctrPendienteProgramarSemana($anio, $semana)
	{
		$lista = ModeloProgramacionTallerSemana::mdlListarCandidatos(array());
		$mapa = self::ctrSaldosOc();
		$enSemana = ModeloProgramacionTallerSemana::mdlClavesOcSemana($anio, $semana);
		$colores = array();
		$modelos = array();
		$cantidad = 0;
		$porTaller = array();
		foreach ((array) $lista as $fila) {
			$clave = self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]);
			if (!isset($mapa[$clave])) {
				continue;
			}
			foreach ($mapa[$clave] as $oc => $sal) {
				if ((int) $sal["saldo"] < 1 || isset($enSemana[$clave . "|" . $oc])) {
					continue;
				}
				$cod = trim((string) $fila["cod_sector_resuelto"]);
				$k = $cod !== "" ? $cod : "—";
				if (!isset($porTaller[$k])) {
					$porTaller[$k] = array(
						"cod_sector" => $k,
						"nom_sector" => $cod === "" ? "Sin taller" : ($fila["nom_sector"] ? $fila["nom_sector"] : $cod),
						"color_taller" => $fila["color_taller"] ? $fila["color_taller"] : "#f0ad4e",
						"total_colores" => 0,
						"total_cantidad" => 0,
						"_colores" => array()
					);
				}
				$porTaller[$k]["_colores"][$clave] = true;
				$porTaller[$k]["total_cantidad"] += (int) $sal["saldo"];
				$colores[$clave] = true;
				$modelos[$fila["modelo"]] = true;
				$cantidad += (int) $sal["saldo"];
			}
		}
		foreach ($porTaller as $k => $t) {
			$porTaller[$k]["total_colores"] = count($t["_colores"]);
			unset($porTaller[$k]["_colores"]);
		}
		$porTaller = array_values($porTaller);
		usort($porTaller, function ($a, $b) {
			return $b["total_cantidad"] - $a["total_cantidad"];
		});
		return array(
			"totales" => array(
				"total_colores" => count($colores),
				"total_modelos" => count($modelos),
				"total_cantidad" => $cantidad
			),
			"por_taller" => $porTaller
		);
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

	/** Observación resultante al fusionar dos programaciones (sin perder la existente). */
	static private function ctrUnirObservacion($actual, $nueva)
	{
		$actual = trim((string) $actual);
		$nueva = trim((string) $nueva);
		if ($nueva === "" || $nueva === $actual) {
			return $actual !== "" ? $actual : null;
		}
		return $actual !== "" ? $actual . " | " . $nueva : $nueva;
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
		$ordencorte = self::ctrNormalizarOc(isset($post["ordencorte"]) ? $post["ordencorte"] : null);
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
		if ($ordencorte === null) {
			return array("ok" => false, "mensaje" => "Falta la orden de corte; recarga la página");
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

		$saldo = self::ctrSaldoOc($art["modelo"], $art["cod_color"], $ordencorte);
		$disponible = (int) $saldo["saldo"];
		if ($disponible < 1) {
			return array("ok" => false, "mensaje" => "La OC {$ordencorte} ya no tiene saldo en corte ni en orden de corte");
		}

		$idExiste = ModeloProgramacionTallerSemana::mdlIdExistente(
			$anio,
			$semana,
			$art["modelo"],
			$art["cod_color"],
			$codSector,
			false,
			$ordencorte
		);
		// Una OC se programa una sola vez; otra OC del mismo modelo/color es una línea aparte
		$ocupadas = ModeloProgramacionTallerSemana::mdlClavesOcOcupadas();
		$claveOc = self::ctrClaveModeloColor($art["modelo"], $art["cod_color"]) . "|" . $ordencorte;
		if (isset($ocupadas[$claveOc]) && $ocupadas[$claveOc]["tipo"] === "programado" && $ocupadas[$claveOc]["id"] !== $idExiste) {
			return array(
				"ok" => false,
				"mensaje" => "La OC {$ordencorte} ya está programada en la semana {$ocupadas[$claveOc]["semana"]}"
			);
		}
		if ($cantidad > $disponible) {
			$cantidad = $disponible;
		}

		$datosBase = array(
			"anio" => $rango["anio"],
			"semana" => $rango["semana"],
			"fecha_inicio" => $rango["fecha_inicio"],
			"fecha_fin" => $rango["fecha_fin"],
			"cantidad" => $cantidad,
			"cod_sector" => $codSector,
			"nivel" => $nivel,
			"observacion" => $observacion !== "" ? $observacion : null,
			"saldo_alm_corte" => (int) $saldo["alm_corte"],
			"saldo_ord_corte" => (int) $saldo["ord_corte"],
			"urg_plan" => $art["urg_plan"]
		);

		if ($idExiste > 0) {
			$existente = ModeloProgramacionTallerSemana::mdlMostrar($idExiste);
			$datosBase["id"] = $idExiste;
			$datosBase["usumod"] = self::ctrUsuarioSesion();
			if ($existente && (int) $existente["estado"] === 1) {
				// Misma OC/semana/taller ya programada: se suma (sin pasar el saldo de la OC) y se conserva lo configurado
				$datosBase["cantidad"] = min($disponible, (int) $existente["cantidad"] + $cantidad);
				$datosBase["nivel"] = $existente["nivel"] !== "" && $existente["nivel"] !== null ? $existente["nivel"] : $nivel;
				$datosBase["observacion"] = self::ctrUnirObservacion($existente["observacion"], $observacion);
			}
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
			"modelo" => $art["modelo"],
			"cod_color" => $art["cod_color"],
			"ordencorte" => $ordencorte,
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
				true,
				$ordencorte
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
		$saldo = self::ctrSaldoOc($row["modelo"], $row["cod_color"], $row["ordencorte"]);

		if ($cambiaSemana || $codSector !== $row["cod_sector"]) {
			$otro = ModeloProgramacionTallerSemana::mdlIdExistente(
				$anio,
				$semana,
				$row["modelo"],
				$row["cod_color"],
				$codSector,
				false,
				self::ctrNormalizarOc($row["ordencorte"])
			);
			if ($otro > 0 && $otro !== $id) {
				// Ya existe en destino: se acumula la cantidad y se quita el origen
				$exist = ModeloProgramacionTallerSemana::mdlMostrar($otro);
				$activo = $exist && (int) $exist["estado"] === 1;
				$usuario = self::ctrUsuarioSesion();
				$datosDest = array(
					"id" => $otro,
					"anio" => $rango["anio"],
					"semana" => $rango["semana"],
					"fecha_inicio" => $rango["fecha_inicio"],
					"fecha_fin" => $rango["fecha_fin"],
					"cantidad" => $activo ? min(max((int) $saldo["saldo"], $cantidad), (int) $exist["cantidad"] + $cantidad) : $cantidad,
					"cod_sector" => $codSector,
					"nivel" => $activo && $exist["nivel"] !== "" && $exist["nivel"] !== null ? $exist["nivel"] : $nivel,
					"observacion" => $activo
						? self::ctrUnirObservacion($exist["observacion"], $observacion)
						: ($observacion !== "" ? $observacion : null),
					"saldo_alm_corte" => (int) $saldo["alm_corte"],
					"saldo_ord_corte" => (int) $saldo["ord_corte"],
					"urg_plan" => $art ? $art["urg_plan"] : $row["urg_plan"],
					"usumod" => $usuario
				);
				if ($activo) {
					$res = ModeloProgramacionTallerSemana::mdlEditar($datosDest);
				} else {
					$res = ModeloProgramacionTallerSemana::mdlReactivar(array_merge($datosDest, array(
						"modelo" => $row["modelo"],
						"cod_color" => $row["cod_color"],
						"color" => $row["color"],
						"nombre" => $row["nombre"]
					)));
				}
				if ($res !== "ok") {
					return array("ok" => false, "mensaje" => "No se pudo actualizar destino");
				}
				ModeloProgramacionTallerSemana::mdlEliminar($id, $usuario);
				return array(
					"ok" => true,
					"mensaje" => "Acumulado en la semana {$semana}",
					"id" => $otro,
					"id_origen" => $id,
					"row" => self::ctrFilaProgramadoPorId($otro)
				);
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
			"saldo_alm_corte" => (int) $saldo["alm_corte"],
			"saldo_ord_corte" => (int) $saldo["ord_corte"],
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
			$candidato = self::ctrCandidatoOc($prev);
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
		// Solo las que su OC aún tiene saldo; si ya se cortó y envió, la semana sí se ejecutó
		$mapa = self::ctrSaldosOc();
		foreach ($lista as $fila) {
			$fila = self::ctrAplicarSaldoOc($fila, $mapa);
			if ($fila["saldo_vivo"] > 0) {
				$out[] = self::ctrEnriquecerProgramado($fila);
			}
		}
		return $out;
	}

	static public function ctrContarNoEjecutados()
	{
		return count(self::ctrListarNoEjecutados());
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
		$saldo = self::ctrSaldoOc($row["modelo"], $row["cod_color"], $row["ordencorte"]);
		$disp = (int) $saldo["saldo"];
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Su OC ya no tiene saldo; no se puede mover");
		}
		$cantidad = min((int) $row["cantidad"], $disp);

		// Si ya existe en destino: actualizar destino y quitar origen
		$idDest = ModeloProgramacionTallerSemana::mdlIdExistente(
			$anio,
			$semana,
			$row["modelo"],
			$row["cod_color"],
			$row["cod_sector"],
			false,
			self::ctrNormalizarOc($row["ordencorte"])
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
				"cantidad" => $exist && (int) $exist["estado"] === 1 ? min($disp, (int) $exist["cantidad"] + $cantidad) : $cantidad,
				"cod_sector" => $row["cod_sector"],
				"nivel" => $exist && (int) $exist["estado"] === 1 && $exist["nivel"] !== "" ? $exist["nivel"] : $row["nivel"],
				"observacion" => $exist && (int) $exist["estado"] === 1
					? self::ctrUnirObservacion($exist["observacion"], isset($row["observacion"]) ? $row["observacion"] : "")
					: (isset($row["observacion"]) ? $row["observacion"] : null),
				"saldo_alm_corte" => (int) $saldo["alm_corte"],
				"saldo_ord_corte" => (int) $saldo["ord_corte"],
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
			"saldo_alm_corte" => (int) $saldo["alm_corte"],
			"saldo_ord_corte" => (int) $saldo["ord_corte"],
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
		$saldo = self::ctrSaldoOc($row["modelo"], $row["cod_color"], $row["ordencorte"]);
		$disp = (int) $saldo["saldo"];
		if ($disp < 1) {
			return array("ok" => false, "mensaje" => "Su OC ya no tiene saldo; quítalo de la programación");
		}
		$cantidad = min((int) $row["cantidad"], $disp);
		$pri = self::ctrPriorizarAjax(array(
			"modelo" => $row["modelo"],
			"cod_color" => $row["cod_color"],
			"ordencorte" => $row["ordencorte"],
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
				"ordencorte" => isset($item["ordencorte"]) ? $item["ordencorte"] : null,
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

	static public function ctrListarArticulosEnviar($filtros = array())
	{
		$anio = isset($filtros["anio"]) ? (int) $filtros["anio"] : 0;
		$semana = isset($filtros["semana"]) ? (int) $filtros["semana"] : 0;
		$anioHasta = isset($filtros["anio_hasta"]) ? (int) $filtros["anio_hasta"] : $anio;
		$semanaHasta = isset($filtros["semana_hasta"]) ? (int) $filtros["semana_hasta"] : $semana;
		if ($anio < 1 || $semana < 1) {
			return array();
		}
		if ($anioHasta < 1 || $semanaHasta < 1) {
			$anioHasta = $anio;
			$semanaHasta = $semana;
		}
		$filtros["anio_hasta"] = $anioHasta;
		$filtros["semana_hasta"] = $semanaHasta;
		$lista = ModeloProgramacionTallerSemana::mdlListarArticulosEnviarSemana($anio, $semana, $filtros);
		if (!is_array($lista)) {
			return array();
		}
		$map = self::ctrMapaNiveles();
		$mapa = self::ctrSaldosOc();
		$out = array();
		foreach ($lista as $fila) {
			// Alm. corte de la talla en la OC de esta línea, no el total del artículo
			$s = self::ctrSaldoDeMapa(
				$mapa,
				self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]),
				self::ctrNormalizarOc($fila["ordencorte"])
			);
			$art = trim((string) $fila["articulo"]);
			$fila["alm_corte"] = isset($s["tallas"][$art]) ? (int) $s["tallas"][$art]["alm_corte"] : 0;
			$fila["ord_corte"] = isset($s["tallas"][$art]) ? (int) $s["tallas"][$art]["ord_corte"] : 0;
			$fila["fecha_oc"] = $s["fecha_oc"];
			if ($fila["alm_corte"] < 1) {
				continue;
			}
			$nid = isset($fila["nivel"]) ? $fila["nivel"] : "";
			$fila["nivel_nombre"] = isset($map[$nid]["nombre"]) ? $map[$nid]["nombre"] : $nid;
			$fila["nivel_color"] = isset($map[$nid]["color"]) ? $map[$nid]["color"] : "#CCCCCC";
			$tipo = isset($fila["tipo_sector"]) ? (int) $fila["tipo_sector"] : 0;
			$fila["es_interno"] = ($tipo === 0) ? 1 : 0;
			$out[] = $fila;
		}
		return $out;
	}

	/** Detalle por talla de la semana (Excel) con el saldo de la OC de cada línea. */
	static public function ctrExportDetalleSemana($anio, $semana, $filtros = array())
	{
		$lista = ModeloProgramacionTallerSemana::mdlExportDetalleSemana($anio, $semana, $filtros);
		$mapa = self::ctrSaldosOc();
		$out = array();
		foreach ((array) $lista as $fila) {
			$s = self::ctrSaldoDeMapa(
				$mapa,
				self::ctrClaveModeloColor($fila["modelo"], $fila["cod_color"]),
				self::ctrNormalizarOc($fila["ordencorte"])
			);
			$art = trim((string) $fila["articulo"]);
			$alm = isset($s["tallas"][$art]) ? (int) $s["tallas"][$art]["alm_corte"] : 0;
			$ord = isset($s["tallas"][$art]) ? (int) $s["tallas"][$art]["ord_corte"] : 0;
			if ($alm + $ord < 1) {
				continue;
			}
			$fila["alm_corte"] = $alm;
			$fila["ord_corte"] = $ord;
			$fila["suma_orden"] = $alm + $ord;
			$out[] = $fila;
		}
		return $out;
	}

	static private function ctrAsegurarDepsMandarTaller()
	{
		$base = dirname(__FILE__) . "/..";
		$autoload = $base . "/extensiones/vendor/autoload.php";
		if (is_file($autoload)) {
			require_once $autoload;
		}
		if (!class_exists("ModeloCortes")) {
			require_once $base . "/modelos/cortes.modelo.php";
		}
		if (!class_exists("ModeloArticulos")) {
			require_once $base . "/modelos/articulos.modelo.php";
		}
		if (!class_exists("ModeloServicios")) {
			require_once $base . "/modelos/servicio.modelo.php";
		}
		if (!class_exists("ControladorCortes")) {
			require_once dirname(__FILE__) . "/cortes.controlador.php";
		}
	}

	/**
	 * Envío a taller desde programación semanal (artículos con check).
	 * Reutiliza el mismo pipeline de stock que en-cortes, sin tocar esa pantalla.
	 */
	static public function ctrMandarTallerLoteAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$anio = isset($post["anio_desde"]) ? (int) $post["anio_desde"] : (isset($post["anio"]) ? (int) $post["anio"] : 0);
		$semana = isset($post["semana_desde"]) ? (int) $post["semana_desde"] : (isset($post["semana"]) ? (int) $post["semana"] : 0);
		$anioHasta = isset($post["anio_hasta"]) ? (int) $post["anio_hasta"] : $anio;
		$semanaHasta = isset($post["semana_hasta"]) ? (int) $post["semana_hasta"] : $semana;
		$guia = isset($post["guia"]) ? trim((string) $post["guia"]) : "";
		$itemsRaw = isset($post["items"]) ? $post["items"] : "[]";
		$items = is_array($itemsRaw) ? $itemsRaw : json_decode($itemsRaw, true);

		if ($anio < 1 || $semana < 1) {
			return array("ok" => false, "mensaje" => "Rango de semanas no válido");
		}
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay artículos seleccionados");
		}

		$permitidos = self::ctrListarArticulosEnviar(array(
			"anio" => $anio,
			"semana" => $semana,
			"anio_hasta" => $anioHasta,
			"semana_hasta" => $semanaHasta
		));
		$porArticulo = array();
		foreach ($permitidos as $fila) {
			$art = trim((string) $fila["articulo"]);
			if ($art === "") {
				continue;
			}
			$idProg = isset($fila["id_programacion"]) ? (int) $fila["id_programacion"] : 0;
			$clave = $art . "|" . $idProg;
			$porArticulo[$clave] = $fila;
			if (!isset($porArticulo[$art])) {
				$porArticulo[$art] = $fila;
			}
		}

		$usuario = isset($_SESSION["id"]) ? (string) $_SESSION["id"] : self::ctrUsuarioSesion();
		$procesados = 0;
		$errores = array();
		$necesitaGuia = false;

		$pendientes = array();
		foreach ($items as $item) {
			$articulo = trim(isset($item["articulo"]) ? (string) $item["articulo"] : "");
			$cantidad = isset($item["cantidad"]) ? (int) $item["cantidad"] : 0;
			$idProg = isset($item["id_programacion"]) ? (int) $item["id_programacion"] : 0;
			if ($articulo === "" || $cantidad < 1) {
				continue;
			}
			$clave = $idProg > 0 ? ($articulo . "|" . $idProg) : $articulo;
			$fila = isset($porArticulo[$clave]) ? $porArticulo[$clave] : (isset($porArticulo[$articulo]) ? $porArticulo[$articulo] : null);
			if (!$fila) {
				$errores[] = $articulo . ": no está programado en el rango o no tiene saldo en corte";
				continue;
			}
			$taller = trim((string) $fila["cod_sector"]);
			if ($taller === "") {
				$errores[] = $articulo . ": sin taller programado";
				continue;
			}
			if ($cantidad > (int) $fila["alm_corte"]) {
				$cantidad = (int) $fila["alm_corte"];
			}
			if (!empty($fila["es_interno"])) {
				$necesitaGuia = true;
			}
			$pendientes[] = array(
				"articulo" => $articulo,
				"cantidad" => $cantidad,
				"fila" => $fila,
				"taller" => $taller
			);
		}

		if (!count($pendientes)) {
			return array(
				"ok" => false,
				"mensaje" => count($errores) ? implode("; ", $errores) : "No hay artículos válidos para enviar",
				"errores" => $errores
			);
		}
		if ($necesitaGuia && $guia === "") {
			return array("ok" => false, "mensaje" => "La guía es obligatoria para talleres internos");
		}

		self::ctrAsegurarDepsMandarTaller();

		foreach ($pendientes as $pend) {
			$resp = self::ctrMandarUnArticuloPts(
				$pend["articulo"],
				$pend["cantidad"],
				$pend["taller"],
				$guia,
				$usuario,
				isset($pend["fila"]["ordencorte"]) ? (int) $pend["fila"]["ordencorte"] : 0
			);
			if (!empty($resp["ok"])) {
				$procesados++;
				if (!empty($resp["aviso"])) {
					$errores[] = $pend["articulo"] . ": " . $resp["aviso"];
				}
			} else {
				$msg = isset($resp["mensaje"]) ? $resp["mensaje"] : "no se pudo enviar";
				$errores[] = $pend["articulo"] . ": " . $msg;
			}
		}

		if ($procesados < 1) {
			return array(
				"ok" => false,
				"mensaje" => count($errores) ? implode("; ", $errores) : "No se pudo enviar ningún artículo",
				"errores" => $errores,
				"ok_count" => 0
			);
		}

		$mensaje = $procesados . " talla(s) enviada(s) a taller";
		if (count($errores)) {
			$mensaje .= "; " . count($errores) . " con aviso";
		}
		return array(
			"ok" => true,
			"mensaje" => $mensaje,
			"ok_count" => $procesados,
			"errores" => $errores
		);
	}

	/** $ordencorte: OC de la programación; sus detalles de almacén de corte se liquidan primero. */
	static private function ctrMandarUnArticuloPts($articulo, $cantidadPedida, $taller, $guia, $usuario, $ordencorte = 0)
	{
		$esExterno = !ControladorSectores::ctrEsInterno($taller);
		$imprimirTickets = ControladorSectores::ctrDebeImprimirTickets($taller);
		$cantidadPedida = (int) $cantidadPedida;

		$datosCab = array(
			"articulo" => $articulo,
			"usuario" => $usuario,
			"cantidad" => $cantidadPedida,
			"saldo" => $cantidadPedida,
			"estado" => "0",
			"guia" => $guia,
			"taller" => $taller,
			"es_servicio_externo" => $esExterno,
			"ordencorte" => (int) $ordencorte
		);

		$respuestaCab = ModeloCortes::mdlMandarTallerCabV2($datosCab);
		$ok = (is_array($respuestaCab) && isset($respuestaCab["status"]) && $respuestaCab["status"] === "ok");
		$cantidadUsada = $ok ? (int) $respuestaCab["cantidad_usada"] : 0;
		if (!$ok || $cantidadUsada <= 0) {
			$msg = is_string($respuestaCab) ? $respuestaCab : "sin saldo disponible";
			return array("ok" => false, "mensaje" => $msg);
		}

		$ult = ModeloCortes::mdlUltCodigo();
		$cod = isset($ult["ult_codigo"]) ? $ult["ult_codigo"] : null;
		if (!$cod) {
			return array("ok" => false, "mensaje" => "no se obtuvo la cabecera de taller");
		}

		$respuesta = ModeloCortes::mdlMandarTaller(array(
			"usuario" => $usuario,
			"articulo" => $articulo,
			"cantidad" => $cantidadUsada,
			"codigo" => $cod
		));
		if ($respuesta != "ok") {
			return array("ok" => false, "mensaje" => "error al registrar en taller");
		}

		$aviso = "";
		if ($cantidadUsada < $cantidadPedida) {
			$aviso = "se enviaron $cantidadUsada de $cantidadPedida (saldo insuficiente)";
		}

		if ($imprimirTickets) {
			try {
				ControladorCortes::ctrImprimirTicketsPorCabecera($cod);
			} catch (Exception $e) {
				$aviso = trim($aviso . " error al imprimir tickets");
			}
		} else {
			ModeloArticulos::mdlActualizarServicioCorte($articulo, $cantidadUsada, false);
			$codigoServicio = ModeloServicios::mdlObtenerOCrearServicioDelDia($taller, $usuario);
			if ($codigoServicio) {
				$detalle = ModeloServicios::mdlGuardarDetallesServicios("servicios_detallejf", array(
					"articulo" => $articulo,
					"cantidad" => $cantidadUsada,
					"codigo" => $codigoServicio,
					"saldo" => $cantidadUsada,
					"cabecera_taller" => $cod
				));
				if ($detalle == "ok") {
					ModeloServicios::mdlSumarTotalServicio($codigoServicio, $cantidadUsada);
				} else {
					$aviso = trim($aviso . " no se pudo guardar el detalle de servicio");
				}
			} else {
				$aviso = trim($aviso . " no se pudo crear el servicio del día");
			}
		}

		return array(
			"ok" => true,
			"cantidad_usada" => $cantidadUsada,
			"aviso" => $aviso
		);
	}
}
