<?php

/**
 * Resolución única de recetas por modelo.
 * Reutilizar desde preview, publicación y futura explosión.
 * No duplicar esta lógica en JS ni reportes.
 */
class ServicioRecetasModeloResolucion
{

	/**
	 * Resuelve una línea para un color/talla.
	 *
	 * Precedencia:
	 * 1) variante COLOR_TALLA exacta
	 * 2) variante COLOR o TALLA según regla (y como fallback en COLOR_TALLA)
	 * 3) mp_base / consumo_base (GENERAL de la línea)
	 * 4) incompleto
	 *
	 * @param array $linea        Detalle de receta (con o sin variantes embebidas)
	 * @param array $variantes    Lista de variantes de esa línea
	 * @param string $codColor
	 * @param string $codTalla
	 * @return array
	 */
	static public function resolverLinea($linea, $variantes, $codColor, $codTalla)
	{
		$codColor = trim((string) $codColor);
		$codTalla = trim((string) $codTalla);
		$regla = strtoupper(trim(isset($linea["regla_variante"]) ? $linea["regla_variante"] : "GENERAL"));
		$nombreRol = isset($linea["nombre_rol"]) ? $linea["nombre_rol"] : "";
		$esTela = !empty($linea["es_tela_principal"]) ? 1 : 0;
		$sublineaEsperada = isset($linea["codigo_sublinea"]) ? trim((string) $linea["codigo_sublinea"]) : "";
		$unidad = isset($linea["unidad"]) ? $linea["unidad"] : null;
		$idDetalle = isset($linea["id"]) ? (int) $linea["id"] : 0;
		$orden = isset($linea["orden"]) ? (int) $linea["orden"] : 0;

		$base = array(
			"id_detalle" => $idDetalle,
			"orden" => $orden,
			"nombre_rol" => $nombreRol,
			"es_tela_principal" => $esTela,
			"regla_variante" => $regla,
			"codigo_sublinea_esperada" => $sublineaEsperada !== "" ? $sublineaEsperada : null,
			"unidad" => $unidad,
			"mp_codigo" => null,
			"consumo" => null,
			"origen" => null,
			"completo" => false,
			"motivo_incompleto" => null,
		);

		$mapa = self::indexarVariantes(is_array($variantes) ? $variantes : array());

		$match = null;
		$origen = null;

		if ($regla === "GENERAL") {
			$match = null;
		} else {
			// 1) COLOR_TALLA
			$claveCT = $codColor . "|" . $codTalla;
			if ($codColor !== "" && $codTalla !== "" && isset($mapa[$claveCT])) {
				$match = $mapa[$claveCT];
				$origen = "COLOR_TALLA";
			}

			// 2) COLOR o TALLA según regla / fallback
			if ($match === null) {
				if ($regla === "COLOR" || $regla === "COLOR_TALLA") {
					$claveC = $codColor . "|";
					if ($codColor !== "" && isset($mapa[$claveC])) {
						$match = $mapa[$claveC];
						$origen = "COLOR";
					}
				}
			}
			if ($match === null) {
				if ($regla === "TALLA" || $regla === "COLOR_TALLA") {
					$claveT = "|" . $codTalla;
					if ($codTalla !== "" && isset($mapa[$claveT])) {
						$match = $mapa[$claveT];
						$origen = "TALLA";
					}
				}
			}
		}

		// 3) GENERAL de la línea
		if ($match === null) {
			$mpBase = isset($linea["mp_base_codigo"]) ? trim((string) $linea["mp_base_codigo"]) : "";
			$consumoBase = self::aFloatONull(isset($linea["consumo_base"]) ? $linea["consumo_base"] : null);
			if ($mpBase !== "" && $consumoBase !== null) {
				$base["mp_codigo"] = substr($mpBase, 0, 5);
				$base["consumo"] = $consumoBase;
				$base["origen"] = "GENERAL";
				$base["completo"] = true;
				return $base;
			}

			$faltantes = array();
			if ($mpBase === "") {
				$faltantes[] = "MP";
			}
			if ($consumoBase === null) {
				$faltantes[] = "consumo";
			}
			$base["motivo_incompleto"] = "Sin resolución para color=$codColor talla=$codTalla"
				. (empty($faltantes) ? "" : " (base sin " . implode("/", $faltantes) . ")");
			return $base;
		}

		$mp = isset($match["mp_codigo"]) ? trim((string) $match["mp_codigo"]) : "";
		$consumoVar = self::aFloatONull(isset($match["consumo"]) ? $match["consumo"] : null);
		$consumoBase = self::aFloatONull(isset($linea["consumo_base"]) ? $linea["consumo_base"] : null);
		$consumo = $consumoVar !== null ? $consumoVar : $consumoBase;

		if ($mp === "" || $consumo === null) {
			$base["motivo_incompleto"] = "Variante $origen incompleta (MP/consumo) para color=$codColor talla=$codTalla";
			$base["origen"] = $origen;
			return $base;
		}

		$base["mp_codigo"] = substr($mp, 0, 5);
		$base["consumo"] = $consumo;
		$base["origen"] = $origen;
		$base["completo"] = true;
		return $base;
	}

	/**
	 * Resuelve todas las líneas activas para un artículo.
	 *
	 * @param array $lineasActivas  Cada una puede traer "variantes" o usarse $variantesPorDetalle
	 * @param array $variantesPorDetalle  id_detalle => [variantes]
	 * @param array $articulo  articulo, cod_color, cod_talla, modelo, color, talla
	 * @param float $cantidad
	 * @param array $mpInfoPorCodigo  opcional: mp_codigo => [codigo_sublinea, descripcion, unidad, color]
	 * @return array
	 */
	static public function resolverArticulo(
		$lineasActivas,
		$variantesPorDetalle,
		$articulo,
		$cantidad = 1.0,
		$mpInfoPorCodigo = array()
	) {
		$cantidad = (float) $cantidad;
		if ($cantidad < 0) {
			$cantidad = 0;
		}

		$codColor = isset($articulo["cod_color"]) ? trim((string) $articulo["cod_color"]) : "";
		$codTalla = isset($articulo["cod_talla"]) ? trim((string) $articulo["cod_talla"]) : "";
		$codigoArticulo = isset($articulo["articulo"]) ? $articulo["articulo"] : "";

		$insumos = array();
		$errores = array();
		$telasResueltas = 0;
		$telaPrincipal = null;
		$lineasCompletas = 0;
		$lineasTotales = 0;

		$conteoSub = array();
		foreach ($lineasActivas as $lineaCnt) {
			if (isset($lineaCnt["activo"]) && (int) $lineaCnt["activo"] !== 1) {
				continue;
			}
			$s = isset($lineaCnt["codigo_sublinea"])
				? strtoupper(trim((string) $lineaCnt["codigo_sublinea"]))
				: "";
			if ($s === "") {
				continue;
			}
			if (!isset($conteoSub[$s])) {
				$conteoSub[$s] = 0;
			}
			$conteoSub[$s]++;
		}

		foreach ($lineasActivas as $linea) {
			if (isset($linea["activo"]) && (int) $linea["activo"] !== 1) {
				continue;
			}
			$idDet = isset($linea["id"]) ? (int) $linea["id"] : 0;
			$vars = array();
			if (isset($linea["variantes"]) && is_array($linea["variantes"])) {
				$vars = $linea["variantes"];
			} elseif ($idDet > 0 && isset($variantesPorDetalle[$idDet])) {
				$vars = $variantesPorDetalle[$idDet];
			}

			$res = self::resolverLinea($linea, $vars, $codColor, $codTalla);
			$res["articulo"] = $codigoArticulo;
			$res["cod_color"] = $codColor;
			$res["cod_talla"] = $codTalla;
			$esTelaLinea = !empty($linea["es_tela_principal"]);
			$subKey = !empty($res["codigo_sublinea_esperada"])
				? strtoupper(trim((string) $res["codigo_sublinea_esperada"]))
				: "";
			$capaOpcional = $esTelaLinea
				|| ($subKey !== "" && isset($conteoSub[$subKey]) && $conteoSub[$subKey] > 1);

			// Tela principal u otra capa de la misma tela: este color no la usa.
			if ($capaOpcional && empty($res["completo"])) {
				continue;
			}

			$lineasTotales++;
			$res["consumo_total"] = $res["completo"] ? round($res["consumo"] * $cantidad, 8) : null;

			$mpInfo = null;
			if ($res["completo"] && isset($mpInfoPorCodigo[$res["mp_codigo"]])) {
				$mpInfo = $mpInfoPorCodigo[$res["mp_codigo"]];
			}
			$res["mp_descripcion"] = $mpInfo && isset($mpInfo["descripcion"]) ? $mpInfo["descripcion"] : null;
			$res["mp_color"] = $mpInfo && isset($mpInfo["color"]) ? $mpInfo["color"] : null;
			$res["mp_unidad"] = $mpInfo && isset($mpInfo["unidad"]) ? $mpInfo["unidad"] : $res["unidad"];
			$res["mp_codigo_sublinea"] = $mpInfo && isset($mpInfo["codigo_sublinea"])
				? $mpInfo["codigo_sublinea"]
				: null;

			// Validación sublínea (si la línea la define y hay dato de MP)
			$res["sublinea_ok"] = true;
			$res["sublinea_error"] = null;
			if (
				$res["completo"]
				&& $res["codigo_sublinea_esperada"]
				&& $res["mp_codigo_sublinea"]
				&& strtoupper($res["codigo_sublinea_esperada"]) !== strtoupper($res["mp_codigo_sublinea"])
			) {
				$res["sublinea_ok"] = false;
				$res["sublinea_error"] = "MP {$res["mp_codigo"]} es sublínea {$res["mp_codigo_sublinea"]}, "
					. "se esperaba {$res["codigo_sublinea_esperada"]}";
				$errores[] = array(
					"articulo" => $codigoArticulo,
					"rol" => $res["nombre_rol"],
					"tipo" => "sublinea",
					"mensaje" => $res["sublinea_error"],
				);
			}

			if ($res["completo"] && $res["sublinea_ok"]) {
				$lineasCompletas++;
			} elseif (!$res["completo"]) {
				$errores[] = array(
					"articulo" => $codigoArticulo,
					"rol" => $res["nombre_rol"],
					"tipo" => "sin_resolucion",
					"mensaje" => $res["motivo_incompleto"],
					"cod_color" => $codColor,
					"cod_talla" => $codTalla,
				);
			}

			if (!empty($res["es_tela_principal"])) {
				if ($res["completo"] && $res["sublinea_ok"]) {
					$telasResueltas++;
					$telaPrincipal = $res;
				}
			}

			$insumos[] = $res;
		}

		$okTela = ($telasResueltas === 1);
		$completo = $okTela && ($lineasTotales > 0) && ($lineasCompletas === $lineasTotales)
			&& empty(array_filter($errores, function ($e) {
				return $e["tipo"] === "sublinea";
			}));

		// Si no hay línea de tela principal en la receta
		$tieneLineaTela = false;
		foreach ($lineasActivas as $linea) {
			if (isset($linea["activo"]) && (int) $linea["activo"] !== 1) {
				continue;
			}
			if (!empty($linea["es_tela_principal"])) {
				$tieneLineaTela = true;
				break;
			}
		}
		if (!$tieneLineaTela) {
			$okTela = false;
			$completo = false;
			$errores[] = array(
				"articulo" => $codigoArticulo,
				"rol" => "Tela principal",
				"tipo" => "tela_principal",
				"mensaje" => "La receta no tiene línea de tela principal activa",
			);
		} elseif ($telasResueltas === 0) {
			$okTela = false;
			$completo = false;
			$errores[] = array(
				"articulo" => $codigoArticulo,
				"rol" => "Tela principal",
				"tipo" => "tela_principal",
				"mensaje" => "Este color no tiene tela principal",
				"cod_color" => $codColor,
				"cod_talla" => $codTalla,
			);
		} elseif ($telasResueltas > 1) {
			$okTela = false;
			$completo = false;
			$errores[] = array(
				"articulo" => $codigoArticulo,
				"rol" => "Tela principal",
				"tipo" => "tela_principal",
				"mensaje" => "Este color tiene más de una tela principal",
				"cod_color" => $codColor,
				"cod_talla" => $codTalla,
			);
		}

		$consolidados = self::consolidarPorMp($insumos);

		return array(
			"ok" => $completo,
			"completo" => $completo,
			"tela_principal_ok" => $okTela,
			"articulo" => $codigoArticulo,
			"modelo" => isset($articulo["modelo"]) ? $articulo["modelo"] : null,
			"cod_color" => $codColor,
			"color" => isset($articulo["color"]) ? $articulo["color"] : null,
			"cod_talla" => $codTalla,
			"talla" => isset($articulo["talla"]) ? $articulo["talla"] : null,
			"cantidad" => $cantidad,
			"tela_principal" => $telaPrincipal,
			"insumos" => $insumos,
			"consolidados" => $consolidados,
			"errores" => $errores,
			"lineas_totales" => $lineasTotales,
			"lineas_completas" => $lineasCompletas,
		);
	}

	/**
	 * Cobertura de todos los artículos activos del modelo.
	 */
	static public function validarCobertura(
		$lineasActivas,
		$variantesPorDetalle,
		$articulos,
		$mpInfoPorCodigo = array(),
		$bloquearComplementarios = true
	) {
		$filas = array();
		$alertas = 0;
		$sinTela = 0;
		$incompletos = 0;
		$sublineaMal = 0;

		foreach ($articulos as $art) {
			$res = self::resolverArticulo(
				$lineasActivas,
				$variantesPorDetalle,
				$art,
				1.0,
				$mpInfoPorCodigo
			);

			$estado = "OK";
			if (!$res["tela_principal_ok"]) {
				$estado = "SIN_TELA";
				$sinTela++;
				$alertas++;
			} elseif (!$res["completo"]) {
				$estado = "INCOMPLETO";
				$incompletos++;
				$alertas++;
			}

			foreach ($res["errores"] as $err) {
				if ($err["tipo"] === "sublinea") {
					$sublineaMal++;
					if ($estado === "OK") {
						$estado = "SUBLINEA";
						$alertas++;
					}
				}
			}

			$telaMp = $res["tela_principal"] && isset($res["tela_principal"]["mp_codigo"])
				? $res["tela_principal"]["mp_codigo"]
				: null;

			$filas[] = array(
				"articulo" => $res["articulo"],
				"cod_color" => $res["cod_color"],
				"color" => $res["color"],
				"cod_talla" => $res["cod_talla"],
				"talla" => $res["talla"],
				"tela_principal_mp" => $telaMp,
				"insumos_completos" => $res["completo"],
				"tela_principal_ok" => $res["tela_principal_ok"],
				"estado" => $estado,
				"errores" => $res["errores"],
			);
		}

		$puedePublicar = ($sinTela === 0) && ($sublineaMal === 0);
		if ($bloquearComplementarios) {
			$puedePublicar = $puedePublicar && ($incompletos === 0);
		}

		$tieneTelaEnReceta = false;
		foreach ($lineasActivas as $linea) {
			if (isset($linea["activo"]) && (int) $linea["activo"] !== 1) {
				continue;
			}
			if (!empty($linea["es_tela_principal"])) {
				$tieneTelaEnReceta = true;
				break;
			}
		}
		if (!$tieneTelaEnReceta) {
			$puedePublicar = false;
		}

		return array(
			"puede_publicar" => $puedePublicar,
			"bloquear_complementarios" => (bool) $bloquearComplementarios,
			"total_articulos" => count($articulos),
			"ok" => count($articulos) - $alertas,
			"alertas" => $alertas,
			"sin_tela" => $sinTela,
			"incompletos" => $incompletos,
			"sublinea_incompatible" => $sublineaMal,
			"filas" => $filas,
		);
	}

	/**
	 * Consolida por MP conservando si alguna fila es tela principal.
	 */
	static public function consolidarPorMp($insumos)
	{
		$mapa = array();
		foreach ($insumos as $row) {
			if (empty($row["completo"]) || empty($row["mp_codigo"])) {
				continue;
			}
			$mp = $row["mp_codigo"];
			if (!isset($mapa[$mp])) {
				$mapa[$mp] = array(
					"mp_codigo" => $mp,
					"mp_descripcion" => isset($row["mp_descripcion"]) ? $row["mp_descripcion"] : null,
					"mp_color" => isset($row["mp_color"]) ? $row["mp_color"] : null,
					"unidad" => isset($row["mp_unidad"]) ? $row["mp_unidad"] : $row["unidad"],
					"consumo_unitario" => 0.0,
					"consumo_total" => 0.0,
					"es_tela_principal" => 0,
					"roles" => array(),
				);
			}
			$mapa[$mp]["consumo_unitario"] = round(
				$mapa[$mp]["consumo_unitario"] + (float) $row["consumo"],
				8
			);
			$mapa[$mp]["consumo_total"] = round(
				$mapa[$mp]["consumo_total"] + (float) $row["consumo_total"],
				8
			);
			if (!empty($row["es_tela_principal"])) {
				$mapa[$mp]["es_tela_principal"] = 1;
			}
			$rol = isset($row["nombre_rol"]) ? $row["nombre_rol"] : "";
			if ($rol !== "" && !in_array($rol, $mapa[$mp]["roles"], true)) {
				$mapa[$mp]["roles"][] = $rol;
			}
		}
		return array_values($mapa);
	}

	static private function indexarVariantes($variantes)
	{
		$mapa = array();
		foreach ($variantes as $v) {
			$c = isset($v["cod_color"]) ? trim((string) $v["cod_color"]) : "";
			$t = isset($v["cod_talla"]) ? trim((string) $v["cod_talla"]) : "";
			$mapa[$c . "|" . $t] = $v;
		}
		return $mapa;
	}

	static private function aFloatONull($valor)
	{
		if ($valor === null || $valor === "") {
			return null;
		}
		if (!is_numeric($valor)) {
			return null;
		}
		return (float) $valor;
	}
}
