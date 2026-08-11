<?php

class ControladorRecetasModelo
{

	static private $reglasPermitidas = array("GENERAL", "COLOR", "TALLA", "COLOR_TALLA");
	static private $estadosPermitidos = array("BORRADOR", "PUBLICADA", "ARCHIVADA");
	/** Límite de filas de datos (sin contar encabezado) en import Excel/CSV */
	static private $importMaxFilas = 50000;
	/** Tamaño máximo del archivo de import (bytes) */
	static private $importMaxBytes = 26214400; // 25 MB
	/** Filas a mostrar en la previsualización (prioriza errores) */
	static private $importPreviewFilas = 400;

	static private function respuesta($ok, $mensaje = "", $data = null)
	{
		$out = array("ok" => (bool) $ok, "mensaje" => $mensaje);
		if ($data !== null) {
			$out["data"] = $data;
		}
		return $out;
	}

	static private function normalizarCodigoCorto($valor, $maxLen = 2)
	{
		$valor = trim((string) $valor);
		if ($valor === "") {
			return "";
		}
		return substr($valor, 0, $maxLen);
	}

	static private function normalizarMp($valor)
	{
		$valor = trim((string) $valor);
		if ($valor === "") {
			return "";
		}
		return substr($valor, 0, 5);
	}

	/**
	 * CodPro es char(5). Excel suele quitar ceros a la izquierda (1517 → debería ser 01517).
	 * Si el código exacto no existe y es numérico corto, prueba relleno a 5 dígitos.
	 *
	 * @return array{mp_codigo:string,normalizado:bool,original:string}
	 */
	static private function resolverMpExcel($valorCrudo, array $mpInfo)
	{
		$original = self::normalizarMp(self::limpiarCodigoExcel($valorCrudo));
		if ($original === "") {
			return array("mp_codigo" => "", "normalizado" => false, "original" => "");
		}
		if (isset($mpInfo[$original])) {
			return array("mp_codigo" => $original, "normalizado" => false, "original" => $original);
		}
		if (ctype_digit($original) && strlen($original) < 5) {
			$padded = str_pad($original, 5, "0", STR_PAD_LEFT);
			if (isset($mpInfo[$padded])) {
				return array("mp_codigo" => $padded, "normalizado" => true, "original" => $original);
			}
		}
		return array("mp_codigo" => $original, "normalizado" => false, "original" => $original);
	}

	static private function candidatosMpParaLookup($mp)
	{
		$out = array();
		$mp = self::normalizarMp($mp);
		if ($mp === "") {
			return $out;
		}
		$out[] = $mp;
		if (ctype_digit($mp) && strlen($mp) < 5) {
			$out[] = str_pad($mp, 5, "0", STR_PAD_LEFT);
		}
		return $out;
	}

	static private function normalizarDecimal($valor, $permitirNull = true)
	{
		if ($valor === null || $valor === "") {
			return $permitirNull ? null : "0";
		}
		if (!is_numeric($valor)) {
			return false;
		}
		return number_format((float) $valor, 8, ".", "");
	}

	static private function usuarioSesion()
	{
		if (!isset($_SESSION["id"])) {
			return 0;
		}
		return (int) $_SESSION["id"];
	}

	/*=============================================
	Estadísticas cabecera listado
	=============================================*/
	static public function ctrEstadisticas()
	{
		$stats = ModeloRecetasModelo::mdlEstadisticas();
		return self::respuesta(true, "", is_array($stats) ? $stats : array());
	}

	/*=============================================
	Listar cabeceras
	=============================================*/
	static public function ctrListar($modelo = "", $estado = "")
	{
		$modelo = trim((string) $modelo);
		$estado = trim((string) $estado);
		if ($estado !== "" && !in_array($estado, self::$estadosPermitidos, true)) {
			return self::respuesta(false, "Estado no válido");
		}

		$lista = ModeloRecetasModelo::mdlListar($modelo, $estado);
		if (!$lista) {
			return self::respuesta(true, "", array());
		}

		require_once dirname(__FILE__) . "/../modelos/recetas-modelo.resolucion.php";
		$cacheArts = array();
		foreach ($lista as &$row) {
			$idReceta = (int) $row["id"];
			$mod = isset($row["modelo"]) ? $row["modelo"] : "";
			if (!isset($cacheArts[$mod])) {
				$arts = ModeloRecetasModelo::mdlArticulosActivosModelo($mod);
				$cacheArts[$mod] = $arts ? $arts : array();
			}
			$estructura = self::cargarEstructuraReceta($idReceta, true);
			if (!$estructura || !count($estructura["lineas"])) {
				$row["articulos_sin_receta"] = count($cacheArts[$mod]);
				continue;
			}
			$cobertura = ServicioRecetasModeloResolucion::validarCobertura(
				$estructura["lineas"],
				$estructura["variantes_por_detalle"],
				$cacheArts[$mod],
				$estructura["mp_info"],
				true
			);
			$row["articulos_sin_receta"] = isset($cobertura["alertas"]) ? (int) $cobertura["alertas"] : 0;
		}
		unset($row);

		return self::respuesta(true, "", $lista);
	}

	/*=============================================
	Detalle completo (cabecera + líneas + variantes)
	=============================================*/
	static public function ctrDetalle($idReceta)
	{
		$idReceta = (int) $idReceta;
		if ($idReceta <= 0) {
			return self::respuesta(false, "Id de receta inválido");
		}

		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}

		$detalles = ModeloRecetasModelo::mdlListarDetalles($idReceta, false);
		$variantes = ModeloRecetasModelo::mdlListarVariantesPorReceta($idReceta);

		$porDetalle = array();
		foreach ($variantes as $v) {
			$idDet = (int) $v["id_receta_modelo_detalle"];
			if (!isset($porDetalle[$idDet])) {
				$porDetalle[$idDet] = array();
			}
			$porDetalle[$idDet][] = $v;
		}

		$lineas = array();
		foreach ($detalles as $d) {
			$idDet = (int) $d["id"];
			$d["variantes"] = isset($porDetalle[$idDet]) ? $porDetalle[$idDet] : array();
			$lineas[] = $d;
		}

		$articulos = ModeloRecetasModelo::mdlArticulosActivosModelo($cabecera["modelo"]);

		return self::respuesta(true, "", array(
			"cabecera" => $cabecera,
			"lineas" => $lineas,
			"articulos_activos" => count($articulos),
			"colores" => ModeloRecetasModelo::mdlColoresModelo($cabecera["modelo"]),
			"tallas" => ModeloRecetasModelo::mdlTallasModelo($cabecera["modelo"]),
		));
	}

	/*=============================================
	Artículos / ejes del modelo
	=============================================*/
	static public function ctrArticulosModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return self::respuesta(false, "Modelo vacío");
		}
		if (!ModeloRecetasModelo::mdlExisteModelo($modelo)) {
			return self::respuesta(false, "Modelo no encontrado");
		}

		$articulos = ModeloRecetasModelo::mdlArticulosActivosModelo($modelo);
		return self::respuesta(true, "", array(
			"modelo" => $modelo,
			"articulos" => $articulos ? $articulos : array(),
			"colores" => ModeloRecetasModelo::mdlColoresModelo($modelo),
			"tallas" => ModeloRecetasModelo::mdlTallasModelo($modelo),
			"total_activos" => $articulos ? count($articulos) : 0,
		));
	}

	/*=============================================
	Buscar materia prima
	=============================================*/
	static public function ctrBuscarMp($q = "", $codigoSublinea = "", $limit = 30)
	{
		$q = trim((string) $q);
		$codigoSublinea = trim((string) $codigoSublinea);
		if (strlen($codigoSublinea) > 6) {
			$codigoSublinea = substr($codigoSublinea, 0, 6);
		}
		$lista = ModeloRecetasModelo::mdlBuscarMp($q, $codigoSublinea, $limit);
		return self::respuesta(true, "", $lista ? $lista : array());
	}

	static public function ctrInfoMps($codigos)
	{
		if (is_string($codigos)) {
			$decoded = json_decode($codigos, true);
			if (is_array($decoded)) {
				$codigos = $decoded;
			} else {
				$codigos = array_filter(array_map("trim", explode(",", $codigos)));
			}
		}
		if (!is_array($codigos)) {
			$codigos = array();
		}
		$mapa = ModeloRecetasModelo::mdlInfoMps($codigos);
		return self::respuesta(true, "", $mapa ? $mapa : array());
	}

	static public function ctrListarSublineas($q = "", $limit = 200)
	{
		$q = trim((string) $q);
		$lista = ModeloRecetasModelo::mdlListarSublineas($q, $limit);
		return self::respuesta(true, "", $lista ? $lista : array());
	}

	static public function ctrInfoSublineas($codigos)
	{
		if (is_string($codigos)) {
			$decoded = json_decode($codigos, true);
			$codigos = is_array($decoded) ? $decoded : preg_split('/[\s,;]+/', $codigos);
		}
		if (!is_array($codigos)) {
			$codigos = array();
		}
		$mapa = ModeloRecetasModelo::mdlInfoSublineas($codigos);
		return self::respuesta(true, "", $mapa ? $mapa : array());
	}

	/*=============================================
	Crear borrador (siguiente versión del modelo)
	=============================================*/
	static public function ctrCrearBorrador($datos)
	{
		$modelo = isset($datos["modelo"]) ? trim((string) $datos["modelo"]) : "";
		if ($modelo === "" || strlen($modelo) > 10) {
			return self::respuesta(false, "Modelo inválido");
		}
		if (!ModeloRecetasModelo::mdlExisteModelo($modelo)) {
			return self::respuesta(false, "Modelo no encontrado");
		}

		$idUsuario = self::usuarioSesion();
		$version = ModeloRecetasModelo::mdlMaxVersionModelo($modelo) + 1;

		$vigenteDesde = isset($datos["vigente_desde"]) && $datos["vigente_desde"] !== ""
			? $datos["vigente_desde"]
			: null;
		$vigenteHasta = isset($datos["vigente_hasta"]) && $datos["vigente_hasta"] !== ""
			? $datos["vigente_hasta"]
			: null;

		$id = ModeloRecetasModelo::mdlCrearCabecera(array(
			"modelo" => $modelo,
			"version" => $version,
			"vigente_desde" => $vigenteDesde,
			"vigente_hasta" => $vigenteHasta,
			"id_usuario" => $idUsuario > 0 ? $idUsuario : null,
		));

		if (!$id) {
			return self::respuesta(false, "No se pudo crear el borrador");
		}

		$conTela = !isset($datos["con_tela_principal"]) || (int) $datos["con_tela_principal"] === 1;
		if ($conTela) {
			$okLineas = ModeloRecetasModelo::mdlReemplazarLineasBorrador($id, array(
				array(
					"orden" => 1,
					"nombre_rol" => "Tela principal",
					"es_tela_principal" => 1,
					"codigo_sublinea" => null,
					"regla_variante" => "COLOR_TALLA",
					"unidad" => null,
					"consumo_base" => null,
					"mp_base_codigo" => null,
					"activo" => 1,
					"variantes" => array(),
				),
			), $idUsuario);
			if (!$okLineas) {
				return self::respuesta(false, "Borrador creado pero falló la línea de tela principal", array(
					"id" => $id,
					"version" => $version,
				));
			}
		}

		return self::respuesta(true, "Borrador creado", array(
			"id" => $id,
			"modelo" => $modelo,
			"version" => $version,
			"estado" => "BORRADOR",
		));
	}

	/*=============================================
	Guardar líneas + variantes de un borrador
	=============================================*/
	static public function ctrGuardarLineas($idReceta, $lineasRaw)
	{
		$idReceta = (int) $idReceta;
		if ($idReceta <= 0) {
			return self::respuesta(false, "Id de receta inválido");
		}

		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}
		if ($cabecera["estado"] !== "BORRADOR") {
			return self::respuesta(false, "Solo se pueden editar recetas en BORRADOR");
		}

		if (is_string($lineasRaw)) {
			$lineasRaw = json_decode($lineasRaw, true);
		}
		if (!is_array($lineasRaw)) {
			return self::respuesta(false, "Líneas inválidas");
		}

		$normalizadas = self::normalizarYValidarLineas($lineasRaw);
		if (!$normalizadas["ok"]) {
			return self::respuesta(false, $normalizadas["mensaje"]);
		}

		$idUsuario = self::usuarioSesion();
		$ok = ModeloRecetasModelo::mdlReemplazarLineasBorrador(
			$idReceta,
			$normalizadas["lineas"],
			$idUsuario
		);
		if (!$ok) {
			return self::respuesta(false, "Error al guardar las líneas");
		}

		return self::ctrDetalle($idReceta);
	}

	/*=============================================
	Validación / normalización de líneas
	=============================================*/
	static private function normalizarYValidarLineas($lineasRaw)
	{
		$lineas = array();
		$telas = 0;
		$ordenAuto = 1;

		foreach ($lineasRaw as $idx => $raw) {
			if (!is_array($raw)) {
				return array("ok" => false, "mensaje" => "Línea #" . ($idx + 1) . " inválida");
			}

			$activo = isset($raw["activo"]) ? (int) $raw["activo"] : 1;
			$activo = $activo ? 1 : 0;

			$nombreRol = isset($raw["nombre_rol"]) ? trim((string) $raw["nombre_rol"]) : "";
			if ($nombreRol === "") {
				return array("ok" => false, "mensaje" => "Línea #" . ($idx + 1) . ": falta nombre_rol");
			}
			if (strlen($nombreRol) > 80) {
				$nombreRol = substr($nombreRol, 0, 80);
			}

			$regla = isset($raw["regla_variante"])
				? strtoupper(trim((string) $raw["regla_variante"]))
				: "GENERAL";
			if (!in_array($regla, self::$reglasPermitidas, true)) {
				return array("ok" => false, "mensaje" => "Línea #" . ($idx + 1) . ": regla_variante no válida");
			}

			$esTela = isset($raw["es_tela_principal"]) ? (int) $raw["es_tela_principal"] : 0;
			$esTela = $esTela ? 1 : 0;
			if ($esTela && $activo) {
				$telas++;
			}

			$sublinea = isset($raw["codigo_sublinea"]) ? trim((string) $raw["codigo_sublinea"]) : "";
			if ($sublinea === "") {
				$sublinea = null;
			} else {
				$sublinea = substr($sublinea, 0, 6);
			}

			$unidad = isset($raw["unidad"]) ? trim((string) $raw["unidad"]) : "";
			if ($unidad === "") {
				$unidad = null;
			} else {
				$unidad = substr($unidad, 0, 10);
			}

			$consumoBase = self::normalizarDecimal(
				isset($raw["consumo_base"]) ? $raw["consumo_base"] : null,
				true
			);
			if ($consumoBase === false) {
				return array("ok" => false, "mensaje" => "Línea #" . ($idx + 1) . ": consumo_base inválido");
			}

			$mpBase = self::normalizarMp(isset($raw["mp_base_codigo"]) ? $raw["mp_base_codigo"] : "");
			if ($mpBase === "") {
				$mpBase = null;
			}

			$orden = isset($raw["orden"]) ? (int) $raw["orden"] : $ordenAuto;
			if ($orden <= 0) {
				$orden = $ordenAuto;
			}
			$ordenAuto++;

			$variantesRaw = isset($raw["variantes"]) ? $raw["variantes"] : array();
			if (is_string($variantesRaw)) {
				$variantesRaw = json_decode($variantesRaw, true);
			}
			if (!is_array($variantesRaw)) {
				$variantesRaw = array();
			}

			$variantes = array();
			$claves = array();

			if ($regla === "GENERAL") {
				// Variantes opcionales; si vienen, se ignoran para no confundir
				$variantes = array();
			} else {
				foreach ($variantesRaw as $vIdx => $vRaw) {
					if (!is_array($vRaw)) {
						return array(
							"ok" => false,
							"mensaje" => "Línea #" . ($idx + 1) . " variante #" . ($vIdx + 1) . " inválida",
						);
					}

					$codColor = self::normalizarCodigoCorto(
						isset($vRaw["cod_color"]) ? $vRaw["cod_color"] : "",
						2
					);
					$codTalla = self::normalizarCodigoCorto(
						isset($vRaw["cod_talla"]) ? $vRaw["cod_talla"] : "",
						2
					);

					if ($regla === "COLOR") {
						if ($codColor === "") {
							return array(
								"ok" => false,
								"mensaje" => "Línea #" . ($idx + 1) . ": variante COLOR sin cod_color",
							);
						}
						$codTalla = "";
					} elseif ($regla === "TALLA") {
						if ($codTalla === "") {
							return array(
								"ok" => false,
								"mensaje" => "Línea #" . ($idx + 1) . ": variante TALLA sin cod_talla",
							);
						}
						$codColor = "";
					} elseif ($regla === "COLOR_TALLA") {
						if ($codColor === "" || $codTalla === "") {
							return array(
								"ok" => false,
								"mensaje" => "Línea #" . ($idx + 1) . ": COLOR_TALLA requiere color y talla",
							);
						}
					}

					$mpCodigo = self::normalizarMp(isset($vRaw["mp_codigo"]) ? $vRaw["mp_codigo"] : "");
					if ($mpCodigo === "") {
						return array(
							"ok" => false,
							"mensaje" => "Línea #" . ($idx + 1) . ": variante sin mp_codigo",
						);
					}

					$consumo = self::normalizarDecimal(
						isset($vRaw["consumo"]) ? $vRaw["consumo"] : null,
						true
					);
					if ($consumo === false) {
						return array(
							"ok" => false,
							"mensaje" => "Línea #" . ($idx + 1) . ": consumo de variante inválido",
						);
					}

					$obs = isset($vRaw["observacion"]) ? trim((string) $vRaw["observacion"]) : "";
					if ($obs === "") {
						$obs = null;
					} elseif (strlen($obs) > 255) {
						$obs = substr($obs, 0, 255);
					}

					$clave = $codColor . "|" . $codTalla;
					if (isset($claves[$clave])) {
						return array(
							"ok" => false,
							"mensaje" => "Línea #" . ($idx + 1) . ": variante duplicada ($clave)",
						);
					}
					$claves[$clave] = true;

					$variantes[] = array(
						"cod_color" => $codColor,
						"cod_talla" => $codTalla,
						"mp_codigo" => $mpCodigo,
						"consumo" => $consumo,
						"observacion" => $obs,
					);
				}
			}

			$lineas[] = array(
				"orden" => $orden,
				"nombre_rol" => $nombreRol,
				"es_tela_principal" => $esTela,
				"codigo_sublinea" => $sublinea,
				"regla_variante" => $regla,
				"unidad" => $unidad,
				"consumo_base" => $consumoBase,
				"mp_base_codigo" => $mpBase,
				"activo" => $activo,
				"variantes" => $variantes,
			);
		}

		if ($telas > 1) {
			return array("ok" => false, "mensaje" => "Solo puede haber una línea de tela principal activa");
		}

		return array("ok" => true, "lineas" => $lineas);
	}

	/*=============================================
	Carga estructura interna de una receta para resolución
	=============================================*/
	static private function cargarEstructuraReceta($idReceta, $soloActivos = true)
	{
		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return null;
		}

		$detalles = ModeloRecetasModelo::mdlListarDetalles($idReceta, $soloActivos);
		$variantes = ModeloRecetasModelo::mdlListarVariantesPorReceta($idReceta);
		$porDetalle = array();
		$mps = array();

		foreach ($variantes as $v) {
			$idDet = (int) $v["id_receta_modelo_detalle"];
			if (!isset($porDetalle[$idDet])) {
				$porDetalle[$idDet] = array();
			}
			$porDetalle[$idDet][] = $v;
			if (!empty($v["mp_codigo"])) {
				$mps[] = $v["mp_codigo"];
			}
		}

		$lineas = array();
		foreach ($detalles as $d) {
			if (!empty($d["mp_base_codigo"])) {
				$mps[] = $d["mp_base_codigo"];
			}
			$idDet = (int) $d["id"];
			$d["variantes"] = isset($porDetalle[$idDet]) ? $porDetalle[$idDet] : array();
			$lineas[] = $d;
		}

		return array(
			"cabecera" => $cabecera,
			"lineas" => $lineas,
			"variantes_por_detalle" => $porDetalle,
			"mp_info" => ModeloRecetasModelo::mdlInfoMps($mps),
		);
	}

	/*=============================================
	Validar cobertura (paso C / publicar)
	=============================================*/
	static public function ctrValidarCobertura($idReceta, $bloquearComplementarios = true)
	{
		require_once dirname(__FILE__) . "/../modelos/recetas-modelo.resolucion.php";

		$idReceta = (int) $idReceta;
		$estructura = self::cargarEstructuraReceta($idReceta, true);
		if (!$estructura) {
			return self::respuesta(false, "Receta no encontrada");
		}

		$articulos = ModeloRecetasModelo::mdlArticulosActivosModelo($estructura["cabecera"]["modelo"]);
		$cobertura = ServicioRecetasModeloResolucion::validarCobertura(
			$estructura["lineas"],
			$estructura["variantes_por_detalle"],
			$articulos ? $articulos : array(),
			$estructura["mp_info"],
			(bool) $bloquearComplementarios
		);

		$cobertura["id_receta"] = $idReceta;
		$cobertura["modelo"] = $estructura["cabecera"]["modelo"];
		$cobertura["version"] = (int) $estructura["cabecera"]["version"];
		$cobertura["estado"] = $estructura["cabecera"]["estado"];

		return self::respuesta(true, "", $cobertura);
	}

	/*=============================================
	Previsualizar explosión de un artículo
	=============================================*/
	static public function ctrPrevisualizarExplosion($datos)
	{
		require_once dirname(__FILE__) . "/../modelos/recetas-modelo.resolucion.php";

		$idReceta = isset($datos["id_receta"]) ? (int) $datos["id_receta"] : 0;
		$articuloCodigo = isset($datos["articulo"]) ? trim((string) $datos["articulo"]) : "";
		$cantidad = isset($datos["cantidad"]) ? $datos["cantidad"] : 1;
		if (!is_numeric($cantidad) || (float) $cantidad < 0) {
			return self::respuesta(false, "Cantidad inválida");
		}
		$cantidad = (float) $cantidad;

		$usarPublicada = !empty($datos["usar_publicada"]);

		if ($articuloCodigo === "") {
			return self::respuesta(false, "Artículo vacío");
		}

		$articulo = ModeloRecetasModelo::mdlArticuloPorCodigo($articuloCodigo);
		if (!$articulo) {
			return self::respuesta(false, "Artículo no encontrado");
		}

		if ($usarPublicada || $idReceta <= 0) {
			$pub = ModeloRecetasModelo::mdlRecetaPublicadaModelo($articulo["modelo"]);
			if (!$pub) {
				return self::respuesta(false, "El modelo no tiene receta PUBLICADA vigente", array(
					"modelo" => $articulo["modelo"],
					"fallback_tarjetas" => true,
				));
			}
			$idReceta = (int) $pub["id"];
		}

		$estructura = self::cargarEstructuraReceta($idReceta, true);
		if (!$estructura) {
			return self::respuesta(false, "Receta no encontrada");
		}
		if ($estructura["cabecera"]["modelo"] !== $articulo["modelo"]) {
			return self::respuesta(false, "La receta no corresponde al modelo del artículo");
		}

		$resultado = ServicioRecetasModeloResolucion::resolverArticulo(
			$estructura["lineas"],
			$estructura["variantes_por_detalle"],
			$articulo,
			$cantidad,
			$estructura["mp_info"]
		);

		$resultado["id_receta_modelo"] = $idReceta;
		$resultado["version"] = (int) $estructura["cabecera"]["version"];
		$resultado["estado_receta"] = $estructura["cabecera"]["estado"];

		if (!$resultado["ok"]) {
			return self::respuesta(false, "Explosión incompleta", $resultado);
		}

		return self::respuesta(true, "", $resultado);
	}

	/*=============================================
	Publicar borrador (bloquea si cobertura incompleta)
	=============================================*/
	static public function ctrPublicar($idReceta, $bloquearComplementarios = true)
	{
		$idReceta = (int) $idReceta;
		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}
		if ($cabecera["estado"] !== "BORRADOR") {
			return self::respuesta(false, "Solo se puede publicar un BORRADOR");
		}

		$cobertura = self::ctrValidarCobertura($idReceta, $bloquearComplementarios);
		if (!$cobertura["ok"]) {
			return $cobertura;
		}
		if (empty($cobertura["data"]["puede_publicar"])) {
			return self::respuesta(false, "Cobertura incompleta: no se puede publicar", $cobertura["data"]);
		}

		$idUsuario = self::usuarioSesion();
		$ok = ModeloRecetasModelo::mdlPublicarReceta($idReceta, $idUsuario, null);
		if (!$ok) {
			return self::respuesta(false, "Error al publicar la receta");
		}

		return self::respuesta(true, "Receta publicada", array(
			"id" => $idReceta,
			"modelo" => $cabecera["modelo"],
			"version" => (int) $cabecera["version"],
			"estado" => "PUBLICADA",
		));
	}

	/*=============================================
	Duplicar a nuevo borrador (p.ej. editar publicada)
	=============================================*/
	static public function ctrDuplicarVersion($idReceta)
	{
		$idReceta = (int) $idReceta;
		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}

		$idUsuario = self::usuarioSesion();
		$nuevo = ModeloRecetasModelo::mdlDuplicarABorrador($idReceta, $idUsuario);
		if (!$nuevo) {
			return self::respuesta(false, "No se pudo duplicar la receta");
		}

		return self::respuesta(true, "Nueva versión en borrador", $nuevo);
	}

	/*=============================================
	Eliminar una receta (cualquier estado)
	=============================================*/
	static public function ctrEliminarReceta($idReceta)
	{
		$idReceta = (int) $idReceta;
		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}

		$res = ModeloRecetasModelo::mdlEliminarReceta($idReceta);
		if (empty($res["ok"])) {
			return self::respuesta(false, isset($res["mensaje"]) ? $res["mensaje"] : "No se pudo eliminar");
		}

		return self::respuesta(true, "Receta eliminada", array(
			"id" => $idReceta,
			"modelo" => $cabecera["modelo"],
			"version" => (int) $cabecera["version"],
			"estado" => $cabecera["estado"],
		));
	}

	/*=============================================
	Eliminar borrador (compat: solo BORRADOR)
	=============================================*/
	static public function ctrEliminarBorrador($idReceta)
	{
		$idReceta = (int) $idReceta;
		$cabecera = ModeloRecetasModelo::mdlObtenerCabecera($idReceta);
		if (!$cabecera) {
			return self::respuesta(false, "Receta no encontrada");
		}
		if ($cabecera["estado"] !== "BORRADOR") {
			return self::respuesta(false, "Solo se puede eliminar un BORRADOR con esta acción. Usa eliminar receta.");
		}

		return self::ctrEliminarReceta($idReceta);
	}

	/*=============================================
	Eliminar TODAS las recetas del módulo
	=============================================*/
	static public function ctrEliminarTodas()
	{
		$res = ModeloRecetasModelo::mdlEliminarTodasRecetas();
		if (empty($res["ok"])) {
			return self::respuesta(false, isset($res["mensaje"]) ? $res["mensaje"] : "No se pudo borrar");
		}
		$n = (int) $res["recetas"];
		return self::respuesta(
			true,
			$n === 0
				? "No había recetas para borrar"
				: "Se eliminaron {$n} receta(s)",
			array(
				"recetas" => $n,
				"detalles" => (int) $res["detalles"],
				"variantes" => (int) $res["variantes"],
			)
		);
	}

	/*=============================================
	Modelos disponibles para importar (tienen tarjetas, sin receta)
	=============================================*/
	static public function ctrListarModelosImportTarjetas()
	{
		$lista = ModeloRecetasModelo::mdlListarModelosParaImportarTarjetas();
		return self::respuesta(true, "", array(
			"modelos" => is_array($lista) ? $lista : array(),
			"total" => is_array($lista) ? count($lista) : 0,
		));
	}

	/*=============================================
	Importar automáticamente desde tarjetasjf → BORRADOR
	Decisiones:
	- Agrupa por sublínea (LEFT CodFab,6); sin sublínea → por MP
	- Regla COLOR_TALLA (compatible con el editor actual)
	- Por artículo/sublínea: prioriza fila tej_princ=si
	- consumo_base = moda de consumos del grupo
	- Una sola tela principal (grupo con más tej_princ=si)
	=============================================*/
	static public function ctrImportarDesdeTarjetas($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "" || strlen($modelo) > 10) {
			return self::respuesta(false, "Modelo inválido");
		}
		if (!ModeloRecetasModelo::mdlExisteModelo($modelo)) {
			return self::respuesta(false, "Modelo no encontrado");
		}

		$filas = ModeloRecetasModelo::mdlDetallesTarjetasPorModelo($modelo);
		if (!$filas || !count($filas)) {
			return self::respuesta(false, "No hay detalles de tarjetas para artículos activos de este modelo");
		}

		$construido = self::construirLineasDesdeTarjetas($filas);
		if (!$construido["ok"]) {
			return self::respuesta(false, $construido["mensaje"]);
		}

		$lineas = $construido["lineas"];
		$avisos = $construido["avisos"];

		$creado = self::ctrCrearBorrador(array(
			"modelo" => $modelo,
			"con_tela_principal" => 0,
		));
		if (!$creado["ok"]) {
			return $creado;
		}

		$idReceta = (int) $creado["data"]["id"];
		$guardado = self::ctrGuardarLineas($idReceta, $lineas);
		if (!$guardado["ok"]) {
			return self::respuesta(false, "Borrador creado pero falló el guardado: " . $guardado["mensaje"], array(
				"id" => $idReceta,
				"avisos" => $avisos,
			));
		}

		$articulos = ModeloRecetasModelo::mdlArticulosActivosModelo($modelo);
		$totalArts = is_array($articulos) ? count($articulos) : 0;

		return self::respuesta(true, "Borrador importado desde tarjetas", array(
			"id" => $idReceta,
			"modelo" => $modelo,
			"version" => $creado["data"]["version"],
			"estado" => "BORRADOR",
			"lineas" => count($lineas),
			"articulos_activos" => $totalArts,
			"variantes_importadas" => $construido["variantes_total"],
			"avisos" => $avisos,
		));
	}

	static private function construirLineasDesdeTarjetas($filas)
	{
		$grupos = array();
		$avisos = array();
		$dupes = 0;
		$sumados = 0;

		foreach ($filas as $f) {
			$mp = self::normalizarMp(isset($f["mp_codigo"]) ? $f["mp_codigo"] : "");
			if ($mp === "") {
				continue;
			}

			$sub = trim((string) (isset($f["codigo_sublinea"]) ? $f["codigo_sublinea"] : ""));
			if ($sub === "") {
				$sub = "MP:" . $mp;
			}

			$codColor = self::normalizarCodigoCorto(isset($f["cod_color"]) ? $f["cod_color"] : "", 2);
			$codTalla = self::normalizarCodigoCorto(isset($f["cod_talla"]) ? $f["cod_talla"] : "", 2);
			if ($codColor === "" || $codTalla === "") {
				continue;
			}

			$claveVar = $codColor . "|" . $codTalla;
			$esTela = (isset($f["tej_princ"]) && $f["tej_princ"] === "si");
			$consumo = self::normalizarDecimal(isset($f["consumo"]) ? $f["consumo"] : null, false);
			if ($consumo === false) {
				$consumo = "1";
			}

			if (!isset($grupos[$sub])) {
				$grupos[$sub] = array(
					"codigo_sublinea" => (strpos($sub, "MP:") === 0) ? null : $sub,
					"nombre_sublinea" => trim((string) (isset($f["nombre_sublinea"]) ? $f["nombre_sublinea"] : "")),
					"tej_count" => 0,
					"variantes" => array(),
					"consumos" => array(),
					"unidades" => array(),
					"nombres_mp" => array(),
				);
			}

			if ($esTela) {
				$grupos[$sub]["tej_count"]++;
			}

			$candidato = array(
				"cod_color" => $codColor,
				"cod_talla" => $codTalla,
				"mp_codigo" => $mp,
				"consumo" => $consumo,
				"es_tela" => $esTela ? 1 : 0,
				"mp_descripcion" => trim((string) (isset($f["mp_descripcion"]) ? $f["mp_descripcion"] : "")),
				"unidad" => trim((string) (isset($f["unidad"]) ? $f["unidad"] : "")),
			);

			if (isset($grupos[$sub]["variantes"][$claveVar])) {
				$prev = $grupos[$sub]["variantes"][$claveVar];
				if ($prev["mp_codigo"] === $candidato["mp_codigo"]) {
					// Misma MP repetida: sumar consumos
					$prev["consumo"] = self::sumarConsumosImport($prev["consumo"], $candidato["consumo"]);
					if ($candidato["es_tela"]) {
						$prev["es_tela"] = 1;
					}
					$grupos[$sub]["variantes"][$claveVar] = $prev;
					$sumados++;
				} elseif (!$prev["es_tela"] && $candidato["es_tela"]) {
					$grupos[$sub]["variantes"][$claveVar] = $candidato;
				} else {
					$dupes++;
				}
			} else {
				$grupos[$sub]["variantes"][$claveVar] = $candidato;
			}
		}

		if ($sumados > 0) {
			$avisos[] = "Se sumaron consumos en $sumados detalle(s) con la misma MP repetida (misma sublínea/color/talla).";
		}
		if ($dupes > 0) {
			$avisos[] = "Hubo $dupes detalles con distinta MP en la misma sublínea/color/talla; se priorizó tej_princ=si.";
		}

		if (!count($grupos)) {
			return array("ok" => false, "mensaje" => "No se pudieron armar líneas desde las tarjetas");
		}

		// Elegir tela principal: grupo con más tej_princ=si
		$telaKey = null;
		$telaMax = 0;
		foreach ($grupos as $k => $g) {
			if ($g["tej_count"] > $telaMax) {
				$telaMax = $g["tej_count"];
				$telaKey = $k;
			}
		}
		if ($telaMax === 0) {
			// Heurística: sublínea BAL* o nombre que sugiera tela/jersey/rib
			foreach ($grupos as $k => $g) {
				$sub = strtoupper((string) ($g["codigo_sublinea"] ? $g["codigo_sublinea"] : $k));
				$nom = strtoupper($g["nombre_sublinea"] . " " . self::valorMasFrecuente(array_map(function ($v) {
					return isset($v["mp_descripcion"]) ? $v["mp_descripcion"] : "";
				}, $g["variantes"])));
				if (strpos($sub, "BAL") === 0
					|| strpos($nom, "TELA") !== false
					|| strpos($nom, "JERSEY") !== false
					|| strpos($nom, "RIB") !== false
				) {
					$telaKey = $k;
					$avisos[] = "Ninguna fila tenía tej_princ=si; se marcó como tela principal la sublínea «"
						. ($g["codigo_sublinea"] ? $g["codigo_sublinea"] : $k) . "» por heurística.";
					break;
				}
			}
			if ($telaKey === null) {
				$avisos[] = "Ninguna fila tenía tej_princ=si. Marca la tela principal en el editor antes de publicar.";
			}
		} else {
			$otras = 0;
			foreach ($grupos as $k => $g) {
				if ($k !== $telaKey && $g["tej_count"] > 0) {
					$otras++;
				}
			}
			if ($otras > 0) {
				$avisos[] = "Había tej_princ=si en $otras sublínea(s) adicional(es); solo se marcó una como tela principal.";
			}
		}

		$lineas = array();
		$orden = 1;
		$variantesTotal = 0;

		foreach ($grupos as $k => $g) {
			$varsOut = array();
			$consumos = array();
			$unidades = array();
			$nombresMp = array();

			foreach ($g["variantes"] as $v) {
				$varsOut[] = array(
					"cod_color" => $v["cod_color"],
					"cod_talla" => $v["cod_talla"],
					"mp_codigo" => $v["mp_codigo"],
					"consumo" => $v["consumo"],
					"observacion" => null,
				);
				$consumos[] = (string) $v["consumo"];
				if ($v["unidad"] !== "") {
					$unidades[] = $v["unidad"];
				}
				if ($v["mp_descripcion"] !== "") {
					$nombresMp[] = $v["mp_descripcion"];
				}
			}

			if (!count($varsOut)) {
				continue;
			}

			$variantesTotal += count($varsOut);
			$consumoBase = self::valorMasFrecuente($consumos);
			$unidad = self::valorMasFrecuente($unidades);
			$nombreMp = self::valorMasFrecuente($nombresMp);

			$nombreRol = $g["nombre_sublinea"];
			if ($nombreRol === "" && $nombreMp !== "") {
				$nombreRol = $nombreMp;
			}
			if ($nombreRol === "") {
				$nombreRol = $g["codigo_sublinea"] ? $g["codigo_sublinea"] : ("Insumo " . $orden);
			}
			if (strlen($nombreRol) > 80) {
				$nombreRol = substr($nombreRol, 0, 80);
			}

			$esTela = ($telaKey !== null && $k === $telaKey) ? 1 : 0;
			// No renombrar a "Tela principal": el flag es_tela_principal basta
			// (si no, al cambiar la tela el nombre viejo queda mal en la explosión).

			$lineas[] = array(
				"orden" => $orden,
				"nombre_rol" => $nombreRol,
				"es_tela_principal" => $esTela,
				"codigo_sublinea" => $g["codigo_sublinea"],
				"regla_variante" => "COLOR_TALLA",
				"unidad" => $unidad !== "" ? $unidad : null,
				"consumo_base" => $consumoBase !== "" ? $consumoBase : "1",
				"mp_base_codigo" => null,
				"activo" => 1,
				"variantes" => $varsOut,
			);
			$orden++;
		}

		if (!count($lineas)) {
			return array("ok" => false, "mensaje" => "No quedaron líneas válidas tras consolidar");
		}

		// Si hay tela, ponerla primero
		usort($lineas, function ($a, $b) {
			$ta = (int) $a["es_tela_principal"];
			$tb = (int) $b["es_tela_principal"];
			if ($ta !== $tb) {
				return $tb - $ta;
			}
			return ((int) $a["orden"]) - ((int) $b["orden"]);
		});
		foreach ($lineas as $i => &$ln) {
			$ln["orden"] = $i + 1;
		}
		unset($ln);

		return array(
			"ok" => true,
			"lineas" => $lineas,
			"avisos" => $avisos,
			"variantes_total" => $variantesTotal,
		);
	}

	static private function valorMasFrecuente($lista)
	{
		if (!is_array($lista) || !count($lista)) {
			return "";
		}
		$counts = array_count_values($lista);
		arsort($counts);
		$keys = array_keys($counts);
		return (string) $keys[0];
	}

	/*=============================================
	Importar desde Excel/CSV → BORRADOR(es)
	Plantilla plana: una fila por variante (o una fila GENERAL).
	=============================================*/
	static private function celdaTextoExcel($valor)
	{
		if ($valor === null) {
			return "";
		}
		if (is_float($valor) || is_int($valor)) {
			if (is_float($valor) && floor($valor) == $valor) {
				return (string) (int) $valor;
			}
			// Evitar notación científica en decimales chicos
			return rtrim(rtrim(number_format((float) $valor, 10, ".", ""), "0"), ".");
		}
		return trim((string) $valor);
	}

	/**
	 * Lee consumo desde celda Excel usando el valor numérico real
	 * (no el texto formateado: evita locale, % y separadores raros).
	 */
	static private function celdaNumeroExcel($cell)
	{
		if ($cell === null) {
			return "";
		}
		$raw = $cell->getValue();
		if ($raw === null || $raw === "") {
			return "";
		}
		if (is_int($raw) || is_float($raw)) {
			return number_format((float) $raw, 10, ".", "");
		}
		if (is_string($raw) && isset($raw[0]) && $raw[0] === "=") {
			try {
				$calc = $cell->getCalculatedValue();
				if (is_int($calc) || is_float($calc)) {
					return number_format((float) $calc, 10, ".", "");
				}
				if ($calc !== null && $calc !== "") {
					$raw = $calc;
				}
			} catch (Exception $e) {
				$raw = $cell->getFormattedValue();
			}
		}
		if (is_int($raw) || is_float($raw)) {
			return number_format((float) $raw, 10, ".", "");
		}
		return self::celdaTextoExcel(is_scalar($raw) ? $raw : $cell->getFormattedValue());
	}

	static private function limpiarCodigoExcel($valor)
	{
		$cod = self::celdaTextoExcel($valor);
		if ($cod === "") {
			return "";
		}
		if (isset($cod[0]) && $cod[0] === "'") {
			$cod = substr($cod, 1);
		}
		if (preg_match('/^=\s*"([^"]*)"\s*$/', $cod, $m)) {
			$cod = $m[1];
		} elseif (preg_match('/^=\s*(.+)\s*$/', $cod, $m)) {
			$cod = trim($m[1], " \t\"'");
		}
		return trim($cod);
	}

	static private function padCodigoCortoExcel($valor, $len = 2)
	{
		$cod = self::limpiarCodigoExcel($valor);
		if ($cod === "") {
			return "";
		}
		if (ctype_digit($cod) && strlen($cod) < $len) {
			$cod = str_pad($cod, $len, "0", STR_PAD_LEFT);
		}
		return substr($cod, 0, $len);
	}

	static private function normalizarDecimalImport($valor, $permitirNull = true)
	{
		if ($valor === null || $valor === "") {
			return $permitirNull ? null : "0";
		}
		if (is_int($valor) || is_float($valor)) {
			return number_format((float) $valor, 8, ".", "");
		}

		$valor = trim((string) $valor);
		$valor = str_replace(array(" ", "\xc2\xa0", "'"), "", $valor);
		if ($valor === "") {
			return $permitirNull ? null : "0";
		}

		$esPorcentaje = false;
		if (substr($valor, -1) === "%") {
			$esPorcentaje = true;
			$valor = trim(substr($valor, 0, -1));
		}

		// Miles europeos: 1.234,56
		if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $valor)) {
			$valor = str_replace(".", "", $valor);
			$valor = str_replace(",", ".", $valor);
		}
		// Miles US: 1,234.56
		elseif (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $valor)) {
			$valor = str_replace(",", "", $valor);
		}
		// Decimal europeo simple: 0,0263 / 12,5
		elseif (strpos($valor, ",") !== false && strpos($valor, ".") === false) {
			$valor = str_replace(",", ".", $valor);
		}
		// Decimal con punto (0.0263): no tocar

		if (!is_numeric($valor)) {
			return false;
		}
		$n = (float) $valor;
		if ($esPorcentaje) {
			$n = $n / 100.0;
		}
		return number_format($n, 8, ".", "");
	}

	/** Suma consumos de filas repetidas (misma MP / color+talla). */
	static private function sumarConsumosImport($a, $b)
	{
		$fa = is_numeric($a) ? (float) $a : 0.0;
		$fb = is_numeric($b) ? (float) $b : 0.0;
		return number_format($fa + $fb, 8, ".", "");
	}

	static private function normalizarFlagSiNo($valor)
	{
		$v = strtolower(trim((string) $valor));
		if ($v === "" || $v === "0" || $v === "no" || $v === "false" || $v === "n") {
			return 0;
		}
		if ($v === "1" || $v === "si" || $v === "sí" || $v === "yes" || $v === "true" || $v === "s") {
			return 1;
		}
		return null;
	}

	static private function delimitadorCsvReceta($linea)
	{
		$opciones = array(
			"," => substr_count($linea, ","),
			";" => substr_count($linea, ";"),
			"\t" => substr_count($linea, "\t"),
		);
		arsort($opciones);
		$delimitador = key($opciones);
		return current($opciones) > 0 ? $delimitador : ",";
	}

	static private function normalizarEncabezadoImport($enc)
	{
		$enc = preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc);
		$enc = strtolower(trim($enc));
		$enc = strtr($enc, array(
			"á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n",
			"ü" => "u",
		));
		$enc = preg_replace('/[\s\-]+/', "_", $enc);
		$alias = array(
			"articulo" => "articulo",
			"codigo_articulo" => "articulo",
			"cod_articulo" => "articulo",
			"sku" => "articulo",
			"mp_codigo" => "mp_codigo",
			"codpro" => "mp_codigo",
			"cod_pro" => "mp_codigo",
			"mp" => "mp_codigo",
			"materia_prima" => "mp_codigo",
			"consumo" => "consumo",
			"es_tela_principal" => "es_tela_principal",
			"tela_principal" => "es_tela_principal",
			"tej_princ" => "es_tela_principal",
			"nombre_rol" => "nombre_rol",
			"rol" => "nombre_rol",
			"observacion" => "observacion",
			"obs" => "observacion",
			"orden" => "orden",
		);
		return isset($alias[$enc]) ? $alias[$enc] : $enc;
	}

	static private function mapearFilaExcelDesdePos($get)
	{
		return array(
			"articulo" => $get("articulo"),
			"mp_codigo" => $get("mp_codigo"),
			"consumo" => $get("consumo"),
			"es_tela_principal" => $get("es_tela_principal"),
			"observacion" => $get("observacion"),
		);
	}

	static private function leerFilasExcelReceta($archivoTmp, $extension)
	{
		$columnasReq = array("articulo", "mp_codigo", "consumo");
		$filas = array();

		if ($extension === "csv") {
			$manejador = fopen($archivoTmp, "rb");
			if ($manejador === false) {
				return array("ok" => false, "mensaje" => "No se pudo leer el archivo");
			}
			$primeraLinea = fgets($manejador);
			if ($primeraLinea === false) {
				fclose($manejador);
				return array("ok" => false, "mensaje" => "El archivo está vacío");
			}
			$delimitador = self::delimitadorCsvReceta($primeraLinea);
			rewind($manejador);
			$encabezadosRaw = fgetcsv($manejador, 0, $delimitador);
			if (!is_array($encabezadosRaw)) {
				fclose($manejador);
				return array("ok" => false, "mensaje" => "No se pudo leer el encabezado");
			}
			$encabezados = array();
			foreach ($encabezadosRaw as $i => $enc) {
				$encabezados[$i] = self::normalizarEncabezadoImport($enc);
			}
			$pos = array_flip($encabezados);
			foreach ($columnasReq as $req) {
				if (!isset($pos[$req])) {
					fclose($manejador);
					return array(
						"ok" => false,
						"mensaje" => "Falta la columna obligatoria: " . $req
							. ". Plantilla: articulo, mp_codigo (CodPro), consumo.",
					);
				}
			}
			$n = 1;
			while (($valores = fgetcsv($manejador, 0, $delimitador)) !== false) {
				$n++;
				if ($n > (self::$importMaxFilas + 1)) {
					fclose($manejador);
					return array(
						"ok" => false,
						"mensaje" => "El archivo supera el máximo de "
							. number_format(self::$importMaxFilas, 0, ".", ",") . " filas",
					);
				}
				if (count($valores) === 1 && trim((string) $valores[0]) === "") {
					continue;
				}
				// Si el CSV usa coma como separador y el consumo europeo "0,0263"
				// se partió en dos columnas, reunirlas.
				if (
					$delimitador === ","
					&& isset($pos["consumo"])
					&& count($valores) === count($encabezados) + 1
				) {
					$iCons = (int) $pos["consumo"];
					if (
						isset($valores[$iCons], $valores[$iCons + 1])
						&& preg_match('/^-?\d+$/', trim((string) $valores[$iCons]))
						&& preg_match('/^\d+$/', trim((string) $valores[$iCons + 1]))
					) {
						$valores[$iCons] = trim((string) $valores[$iCons]) . "." . trim((string) $valores[$iCons + 1]);
						array_splice($valores, $iCons + 1, 1);
					}
				}
				$get = function ($col) use ($pos, $valores) {
					if (!isset($pos[$col])) {
						return "";
					}
					$i = $pos[$col];
					return self::celdaTextoExcel(isset($valores[$i]) ? $valores[$i] : "");
				};
				$row = self::mapearFilaExcelDesdePos($get);
				$row["fila"] = $n;
				$filas[] = $row;
			}
			fclose($manejador);
			return array("ok" => true, "filas" => $filas);
		}

		$phpExcelPath = dirname(__FILE__) . "/../vistas/reportes_excel/Classes/PHPExcel.php";
		if (!file_exists($phpExcelPath)) {
			return array("ok" => false, "mensaje" => "No está disponible el lector de Excel");
		}
		require_once $phpExcelPath;
		try {
			$excel = PHPExcel_IOFactory::load($archivoTmp);
			$sheet = $excel->getActiveSheet();
			$highestRow = (int) $sheet->getHighestDataRow();
			$highestCol = $sheet->getHighestDataColumn();
			$highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);
			if ($highestRow < 1) {
				return array("ok" => false, "mensaje" => "El archivo está vacío");
			}
			$encabezados = array();
			for ($c = 0; $c < $highestColIndex; $c++) {
				$val = $sheet->getCellByColumnAndRow($c, 1)->getFormattedValue();
				$encabezados[$c] = self::normalizarEncabezadoImport($val);
			}
			$pos = array_flip($encabezados);
			foreach ($columnasReq as $req) {
				if (!isset($pos[$req])) {
					return array(
						"ok" => false,
						"mensaje" => "Falta la columna obligatoria: " . $req
							. ". Plantilla: articulo, mp_codigo (CodPro), consumo.",
					);
				}
			}
			if ($highestRow > (self::$importMaxFilas + 1)) {
				return array(
					"ok" => false,
					"mensaje" => "El archivo supera el máximo de "
						. number_format(self::$importMaxFilas, 0, ".", ",") . " filas",
				);
			}
			$getCell = function ($col, $r) use ($pos, $sheet) {
				if (!isset($pos[$col])) {
					return "";
				}
				$cell = $sheet->getCellByColumnAndRow((int) $pos[$col], $r);
				if ($col === "consumo") {
					return self::celdaNumeroExcel($cell);
				}
				return self::celdaTextoExcel($cell->getFormattedValue());
			};
			for ($r = 2; $r <= $highestRow; $r++) {
				$get = function ($col) use ($getCell, $r) {
					return $getCell($col, $r);
				};
				$row = self::mapearFilaExcelDesdePos($get);
				$row["fila"] = $r;
				$vacio = true;
				foreach ($row as $k => $v) {
					if ($k === "fila") {
						continue;
					}
					if (trim((string) $v) !== "") {
						$vacio = false;
						break;
					}
				}
				if ($vacio) {
					continue;
				}
				$filas[] = $row;
			}
			return array("ok" => true, "filas" => $filas);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo leer el Excel: " . $e->getMessage());
		}
	}

	static private function construirImportExcelDesdeFilas(array $crudas)
	{
		$artsNecesarios = array();
		$mpsNecesarias = array();
		foreach ($crudas as $row) {
			$art = self::limpiarCodigoExcel($row["articulo"]);
			$mp = self::normalizarMp(self::limpiarCodigoExcel($row["mp_codigo"]));
			if ($art !== "") {
				$artsNecesarios[$art] = true;
			}
			foreach (self::candidatosMpParaLookup($mp) as $cand) {
				$mpsNecesarias[$cand] = true;
			}
		}
		$artInfo = ModeloRecetasModelo::mdlArticulosPorCodigos(array_keys($artsNecesarios));
		$mpInfo = ModeloRecetasModelo::mdlInfoMps(array_keys($mpsNecesarias));
		$subsNecesarias = array();
		foreach ($mpInfo as $info) {
			$sub = strtoupper(trim((string) (isset($info["codigo_sublinea"]) ? $info["codigo_sublinea"] : "")));
			if ($sub !== "") {
				$subsNecesarias[$sub] = true;
			}
		}
		$subInfo = ModeloRecetasModelo::mdlInfoSublineas(array_keys($subsNecesarias));

		$filasOut = array();
		$porModelo = array();

		foreach ($crudas as $row) {
			$errores = array();
			$articulo = self::limpiarCodigoExcel($row["articulo"]);
			$mpResuelto = self::resolverMpExcel(
				isset($row["mp_codigo"]) ? $row["mp_codigo"] : "",
				$mpInfo
			);
			$mpCodigo = $mpResuelto["mp_codigo"];
			$mpNormalizado = !empty($mpResuelto["normalizado"]);
			$mpOriginal = $mpResuelto["original"];
			$consumo = self::normalizarDecimalImport($row["consumo"], false);
			if ($consumo === false) {
				$errores[] = "consumo inválido";
				$consumo = null;
			}

			// Tela principal opcional: si no viene o viene vacía → 0 (se marca después en el editor).
			$flagRaw = isset($row["es_tela_principal"]) ? trim((string) $row["es_tela_principal"]) : "";
			$flagTela = 0;
			if ($flagRaw !== "") {
				$flagTela = self::normalizarFlagSiNo($flagRaw);
				if ($flagTela === null) {
					$errores[] = "es_tela_principal inválido (use 1/0 o si/no)";
					$flagTela = 0;
				}
			}

			$obs = trim((string) (isset($row["observacion"]) ? $row["observacion"] : ""));
			if (strlen($obs) > 255) {
				$obs = substr($obs, 0, 255);
			}

			$modelo = "";
			$codColor = "";
			$codTalla = "";
			$estadoArt = "";

			if ($articulo === "") {
				$errores[] = "Falta artículo";
			} elseif (!isset($artInfo[$articulo])) {
				$errores[] = "Artículo inexistente";
			} else {
				$a = $artInfo[$articulo];
				$modelo = trim((string) $a["modelo"]);
				// Usar códigos exactos del artículo (sin pad): el editor cruza por esos mismos valores
				$codColor = self::normalizarCodigoCorto(isset($a["cod_color"]) ? $a["cod_color"] : "", 2);
				$codTalla = self::normalizarCodigoCorto(isset($a["cod_talla"]) ? $a["cod_talla"] : "", 2);
				$estadoArt = trim((string) $a["estado"]);
				if ($modelo === "") {
					$errores[] = "El artículo no tiene modelo";
				}
				if ($codColor === "" || $codTalla === "") {
					$errores[] = "El artículo no tiene color/talla";
				}
				if ($estadoArt !== "" && strcasecmp($estadoArt, "Activo") !== 0) {
					$errores[] = "Artículo no activo";
				}
			}

			$sublinea = "";
			$unidad = "";
			$mpDesc = "";
			if ($mpCodigo === "") {
				$errores[] = "Falta mp_codigo (CodPro)";
			} elseif (!isset($mpInfo[$mpCodigo])) {
				$errores[] = "MP inexistente (" . $mpCodigo . ")";
			} else {
				$sublinea = strtoupper(trim((string) $mpInfo[$mpCodigo]["codigo_sublinea"]));
				if ($sublinea !== "") {
					$sublinea = substr($sublinea, 0, 6);
				}
				$unidad = trim((string) $mpInfo[$mpCodigo]["unidad"]);
				if ($unidad !== "") {
					$unidad = substr($unidad, 0, 10);
				}
				$mpDesc = trim((string) $mpInfo[$mpCodigo]["descripcion"]);
			}

			if ($consumo === null || (float) $consumo < 0) {
				if (!in_array("consumo inválido", $errores, true)) {
					$errores[] = "consumo obligatorio";
				}
			}

			// Agrupar por sublínea de la MP (si no hay, por CodPro)
			$grupoKey = "";
			$nombreRol = "";
			$nombreSublinea = "";
			if ($sublinea !== "") {
				$grupoKey = "SUB:" . $sublinea;
				$nombreSublinea = isset($subInfo[$sublinea]) ? trim((string) $subInfo[$sublinea]) : "";
				$nombreRol = $nombreSublinea !== "" ? $nombreSublinea : $sublinea;
			} elseif ($mpCodigo !== "") {
				$grupoKey = "MP:" . $mpCodigo;
				$nombreRol = $mpDesc !== "" ? $mpDesc : ("MP " . $mpCodigo);
				if (strlen($nombreRol) > 80) {
					$nombreRol = substr($nombreRol, 0, 80);
				}
			}

			$item = array(
				"fila" => (int) $row["fila"],
				"articulo" => $articulo,
				"modelo" => $modelo,
				"cod_color" => $codColor,
				"cod_talla" => $codTalla,
				"mp_codigo" => $mpCodigo,
				"mp_codigo_original" => $mpOriginal,
				"mp_normalizado" => $mpNormalizado,
				"consumo" => $consumo,
				"es_tela_principal" => (int) $flagTela,
				"nombre_rol" => $nombreRol,
				"nombre_sublinea" => $nombreSublinea,
				"grupo_key" => $grupoKey,
				"codigo_sublinea" => $sublinea,
				"unidad" => $unidad,
				"mp_descripcion" => $mpDesc,
				"orden" => 0,
				"observacion" => $obs,
				"errores" => $errores,
			);
			$filasOut[] = $item;

			if ($modelo === "" || $grupoKey === "" || !empty($errores)) {
				if ($modelo !== "") {
					if (!isset($porModelo[$modelo])) {
						$porModelo[$modelo] = array("modelo" => $modelo, "filas" => array());
					}
					$porModelo[$modelo]["filas"][] = $item;
				}
				continue;
			}

			if (!isset($porModelo[$modelo])) {
				$porModelo[$modelo] = array("modelo" => $modelo, "filas" => array());
			}
			$porModelo[$modelo]["filas"][] = $item;
		}

		$resumenModelos = array();
		$totalErrores = 0;
		foreach ($filasOut as $item) {
			if (!empty($item["errores"])) {
				$totalErrores++;
			}
		}
		$aCrear = 0;

		foreach ($porModelo as $modelo => $bloque) {
			$erroresModelo = array();
			$grupos = array();
			$variantesCount = 0;
			$filasConError = 0;
			$dupes = 0;
			$sumados = 0;

			foreach ($bloque["filas"] as $f) {
				if (!empty($f["errores"])) {
					$filasConError++;
					continue;
				}
				$gk = $f["grupo_key"];
				if (!isset($grupos[$gk])) {
					$grupos[$gk] = array(
						"nombre_rol" => $f["nombre_rol"],
						"orden" => $f["orden"] > 0 ? $f["orden"] : 9999,
						"es_tela_principal" => 0,
						"tej_count" => 0,
						"codigo_sublinea" => $f["codigo_sublinea"] !== "" ? $f["codigo_sublinea"] : null,
						"unidad" => $f["unidad"] !== "" ? $f["unidad"] : null,
						"regla_variante" => "COLOR_TALLA",
						"consumo_base" => null,
						"mp_base_codigo" => null,
						"activo" => 1,
						"variantes" => array(),
						"claves_var" => array(),
						"nombres_mp" => array(),
						"consumos" => array(),
					);
					if (!empty($f["nombre_sublinea"]) && $f["nombre_rol"] === $f["codigo_sublinea"]) {
						$grupos[$gk]["nombre_rol"] = $f["nombre_sublinea"];
					}
				}
				$g = &$grupos[$gk];
				if ((int) $f["es_tela_principal"] === 1) {
					$g["tej_count"]++;
					$g["es_tela_principal"] = 1;
				}
				if ($f["orden"] > 0 && $f["orden"] < $g["orden"]) {
					$g["orden"] = $f["orden"];
				}
				if ($g["unidad"] === null && $f["unidad"] !== "") {
					$g["unidad"] = $f["unidad"];
				}
				if (!empty($f["mp_descripcion"])) {
					$g["nombres_mp"][] = $f["mp_descripcion"];
				}

				$claveVar = $f["cod_color"] . "|" . $f["cod_talla"];
				$candidato = array(
					"cod_color" => $f["cod_color"],
					"cod_talla" => $f["cod_talla"],
					"mp_codigo" => $f["mp_codigo"],
					"consumo" => $f["consumo"],
					"observacion" => $f["observacion"] !== "" ? $f["observacion"] : null,
					"es_tela" => (int) $f["es_tela_principal"],
					"fila" => $f["fila"],
				);
				if (isset($g["claves_var"][$claveVar])) {
					$idx = $g["claves_var"][$claveVar];
					$prev = $g["variantes"][$idx];
					if ($prev["mp_codigo"] === $candidato["mp_codigo"]) {
						// Misma MP repetida: sumar consumos
						$prev["consumo"] = self::sumarConsumosImport($prev["consumo"], $candidato["consumo"]);
						if ($candidato["es_tela"]) {
							$prev["es_tela"] = 1;
						}
						if ($candidato["observacion"] && !$prev["observacion"]) {
							$prev["observacion"] = $candidato["observacion"];
						}
						$g["variantes"][$idx] = $prev;
						$sumados++;
					} elseif (!$prev["es_tela"] && $candidato["es_tela"]) {
						$g["variantes"][$idx] = $candidato;
						$dupes++;
					} else {
						$dupes++;
					}
				} else {
					$g["claves_var"][$claveVar] = count($g["variantes"]);
					$g["variantes"][] = $candidato;
					$variantesCount++;
				}
				unset($g);
			}

			// Recalcular lista de consumos desde variantes ya consolidadas (tras sumas)
			foreach ($grupos as &$gRecalc) {
				$gRecalc["consumos"] = array();
				foreach ($gRecalc["variantes"] as $vv) {
					if ($vv["consumo"] !== null && $vv["consumo"] !== "") {
						$gRecalc["consumos"][] = $vv["consumo"];
					}
				}
			}
			unset($gRecalc);

			if ($sumados > 0) {
				$erroresModelo[] = "Se sumaron consumos en $sumados fila(s) con la misma MP repetida.";
			}
			if ($dupes > 0) {
				$erroresModelo[] = "Hubo $dupes filas con distinta MP en el mismo color/talla; se conservó una.";
			}

			// Si el Excel marcó tela principal, consolidar a un solo grupo; si no, queda en 0 para configurar luego.
			$telaKey = null;
			$telaMax = 0;
			foreach ($grupos as $k => $g) {
				if ($g["tej_count"] > $telaMax) {
					$telaMax = $g["tej_count"];
					$telaKey = $k;
				}
			}
			foreach ($grupos as $k => &$g) {
				if ($telaMax > 0) {
					$g["es_tela_principal"] = ($k === $telaKey) ? 1 : 0;
					if ($g["es_tela_principal"]) {
						$g["nombre_rol"] = "Tela principal";
						$g["orden"] = 1;
					}
				} else {
					$g["es_tela_principal"] = 0;
				}
				if ($g["nombre_rol"] === "" || $g["nombre_rol"] === null) {
					$g["nombre_rol"] = "Insumo";
				}
				if (!empty($g["consumos"])) {
					$g["consumo_base"] = self::valorMasFrecuente($g["consumos"]);
				}
			}
			unset($g);

			if ($filasConError > 0) {
				// Aviso: no bloquea; esas filas se omiten y se importan las válidas
				$erroresModelo[] = "Se omitirán {$filasConError} fila(s) con error; el resto se importa.";
			}
			if (!count($grupos)) {
				$erroresModelo[] = $filasConError > 0
					? "Sin líneas válidas (todas las filas tienen error)"
					: "Sin líneas válidas";
			}

			$avisosBloqueantes = array();
			foreach ($erroresModelo as $msg) {
				if (strpos($msg, "Hubo ") === 0 && strpos($msg, "duplicadas") !== false) {
					continue;
				}
				if (strpos($msg, "Hubo ") === 0 && strpos($msg, "distinta MP") !== false) {
					continue;
				}
				if (strpos($msg, "Se omitirán ") === 0) {
					continue;
				}
				if (strpos($msg, "Se sumaron consumos") === 0) {
					continue;
				}
				$avisosBloqueantes[] = $msg;
			}
			$okModelo = empty($avisosBloqueantes) && count($grupos) > 0;
			if ($okModelo) {
				$aCrear++;
			}

			$lineas = array();
			uasort($grupos, function ($a, $b) {
				$ta = (int) $a["es_tela_principal"];
				$tb = (int) $b["es_tela_principal"];
				if ($ta !== $tb) {
					return $tb - $ta;
				}
				$oa = (int) $a["orden"];
				$ob = (int) $b["orden"];
				if ($oa === $ob) {
					return strcmp($a["nombre_rol"], $b["nombre_rol"]);
				}
				return $oa - $ob;
			});
			$ordenAuto = 1;
			foreach ($grupos as $g) {
				$varsOut = array();
				foreach ($g["variantes"] as $v) {
					$varsOut[] = array(
						"cod_color" => $v["cod_color"],
						"cod_talla" => $v["cod_talla"],
						"mp_codigo" => $v["mp_codigo"],
						"consumo" => $v["consumo"],
						"observacion" => $v["observacion"],
					);
				}
				$lineas[] = array(
					"orden" => $ordenAuto,
					"nombre_rol" => $g["nombre_rol"],
					"es_tela_principal" => (int) $g["es_tela_principal"],
					"codigo_sublinea" => $g["codigo_sublinea"],
					"regla_variante" => "COLOR_TALLA",
					"unidad" => $g["unidad"],
					"consumo_base" => $g["consumo_base"] !== null ? $g["consumo_base"] : "1",
					"mp_base_codigo" => null,
					"activo" => 1,
					"variantes" => $varsOut,
				);
				$ordenAuto++;
			}

			$resumenModelos[] = array(
				"modelo" => $modelo,
				"ok" => $okModelo,
				"lineas" => count($lineas),
				"variantes" => $variantesCount,
				"filas" => count($bloque["filas"]),
				"errores" => $okModelo
					? array_values(array_filter($erroresModelo, function ($msg) {
						return strpos($msg, "Hubo ") === 0
							|| strpos($msg, "Se omitirán ") === 0
							|| strpos($msg, "Se sumaron consumos") === 0;
					}))
					: $avisosBloqueantes,
				"accion" => $okModelo ? "crear_borrador" : "",
				"lineas_payload" => $okModelo ? $lineas : array(),
			);
		}

		return array(
			"filas" => $filasOut,
			"modelos" => $resumenModelos,
			"total" => count($filasOut),
			"validas" => count($filasOut) - $totalErrores,
			"rechazadas" => $totalErrores,
			"a_crear" => $aCrear,
			"modelos_error" => count($resumenModelos) - $aCrear,
		);
	}

	static public function ctrImportarDesdeExcel($post, $files)
	{
		@ini_set("memory_limit", "512M");
		@set_time_limit(600);

		$confirmar = isset($post["confirmar"]) && (string) $post["confirmar"] === "1";
		if (
			!isset($files["archivo"]) ||
			!is_array($files["archivo"]) ||
			(int) $files["archivo"]["error"] !== UPLOAD_ERR_OK
		) {
			return self::respuesta(false, "Selecciona un archivo válido");
		}

		$archivo = $files["archivo"];
		$extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
		if (!in_array($extension, array("csv", "xls", "xlsx"), true)) {
			return self::respuesta(false, "Formato no soportado. Usa CSV, XLS o XLSX");
		}
		$maxMb = (int) round(self::$importMaxBytes / (1024 * 1024));
		if ((int) $archivo["size"] < 1 || (int) $archivo["size"] > self::$importMaxBytes) {
			return self::respuesta(false, "El archivo debe pesar como máximo {$maxMb} MB");
		}
		if (!is_uploaded_file($archivo["tmp_name"])) {
			return self::respuesta(false, "La carga del archivo no es válida");
		}

		$leido = self::leerFilasExcelReceta($archivo["tmp_name"], $extension);
		if (!$leido["ok"]) {
			return self::respuesta(false, $leido["mensaje"]);
		}
		if (empty($leido["filas"])) {
			return self::respuesta(false, "El archivo no contiene datos");
		}

		$analisis = self::construirImportExcelDesdeFilas($leido["filas"]);

		if (!$confirmar) {
			$modelosPreview = array();
			foreach ($analisis["modelos"] as $m) {
				$modelosPreview[] = array(
					"modelo" => $m["modelo"],
					"ok" => $m["ok"],
					"lineas" => $m["lineas"],
					"variantes" => $m["variantes"],
					"filas" => $m["filas"],
					"errores" => $m["errores"],
					"accion" => $m["accion"],
				);
			}

			// Previsualización acotada: primero errores, luego OK
			$conError = array();
			$sinError = array();
			foreach ($analisis["filas"] as $f) {
				$filaPrev = array(
					"fila" => $f["fila"],
					"articulo" => $f["articulo"],
					"modelo" => $f["modelo"],
					"cod_color" => $f["cod_color"],
					"cod_talla" => $f["cod_talla"],
					"mp_codigo" => $f["mp_codigo"],
					"mp_codigo_original" => isset($f["mp_codigo_original"]) ? $f["mp_codigo_original"] : $f["mp_codigo"],
					"mp_normalizado" => !empty($f["mp_normalizado"]),
					"consumo" => $f["consumo"],
					"es_tela_principal" => $f["es_tela_principal"],
					"nombre_rol" => $f["nombre_rol"],
					"errores" => $f["errores"],
				);
				if (!empty($f["errores"])) {
					$conError[] = $filaPrev;
				} else {
					$sinError[] = $filaPrev;
				}
			}
			$limite = self::$importPreviewFilas;
			$filasPreview = array_slice($conError, 0, $limite);
			$restantes = $limite - count($filasPreview);
			if ($restantes > 0) {
				$filasPreview = array_merge($filasPreview, array_slice($sinError, 0, $restantes));
			}
			$previewLimitado = count($analisis["filas"]) > count($filasPreview);

			return array(
				"ok" => true,
				"mensaje" => "",
				"previsualizacion" => true,
				"data" => $filasPreview,
				"modelos" => $modelosPreview,
				"total" => $analisis["total"],
				"validas" => $analisis["validas"],
				"rechazadas" => $analisis["rechazadas"],
				"a_crear" => $analisis["a_crear"],
				"modelos_error" => $analisis["modelos_error"],
				"preview_mostradas" => count($filasPreview),
				"preview_limitado" => $previewLimitado,
				"max_filas" => self::$importMaxFilas,
			);
		}

		if ($analisis["a_crear"] < 1) {
			return self::respuesta(false, "No hay modelos válidos para importar. Revisa los errores del archivo.");
		}

		$creados = array();
		$omitidos = 0;
		foreach ($analisis["modelos"] as $m) {
			if (!$m["ok"] || empty($m["lineas_payload"])) {
				$omitidos++;
				continue;
			}
			$creado = self::ctrCrearBorrador(array(
				"modelo" => $m["modelo"],
				"con_tela_principal" => 0,
			));
			if (!$creado["ok"]) {
				return self::respuesta(
					false,
					"Falló al crear borrador del modelo {$m["modelo"]}: " . $creado["mensaje"],
					array("creados" => $creados)
				);
			}
			$idReceta = (int) $creado["data"]["id"];
			$guardado = self::ctrGuardarLineas($idReceta, $m["lineas_payload"]);
			if (!$guardado["ok"]) {
				return self::respuesta(
					false,
					"Borrador {$m["modelo"]} creado pero falló el guardado: " . $guardado["mensaje"],
					array(
						"creados" => $creados,
						"id_fallido" => $idReceta,
					)
				);
			}
			$creados[] = array(
				"id" => $idReceta,
				"modelo" => $m["modelo"],
				"version" => $creado["data"]["version"],
				"lineas" => $m["lineas"],
				"variantes" => $m["variantes"],
			);
		}

		$n = count($creados);
		$msg = $n === 1
			? "Se creó 1 borrador desde Excel"
			: "Se crearon {$n} borradores desde Excel";
		if ($analisis["rechazadas"] > 0) {
			$msg .= ". Se omitieron " . (int) $analisis["rechazadas"] . " fila(s) con error";
		}
		if ($omitidos > 0) {
			$msg .= ($analisis["rechazadas"] > 0 ? "" : ".")
				. " · {$omitidos} modelo(s) sin datos válidos";
		}
		return self::respuesta(
			true,
			$msg,
			array(
				"creados" => $creados,
				"filas_omitidas" => (int) $analisis["rechazadas"],
				"modelos_omitidos" => $omitidos,
			)
		);
	}
}
