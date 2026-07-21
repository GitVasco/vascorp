<?php

class ControladorRecetasModelo
{

	static private $reglasPermitidas = array("GENERAL", "COLOR", "TALLA", "COLOR_TALLA");
	static private $estadosPermitidos = array("BORRADOR", "PUBLICADA", "ARCHIVADA");

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
		return self::respuesta(true, "", $lista ? $lista : array());
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
				// Preferir tela principal; si no, conservar el primero
				if (!$prev["es_tela"] && $candidato["es_tela"]) {
					$grupos[$sub]["variantes"][$claveVar] = $candidato;
				} else {
					$dupes++;
				}
			} else {
				$grupos[$sub]["variantes"][$claveVar] = $candidato;
			}
		}

		if ($dupes > 0) {
			$avisos[] = "Hubo $dupes detalles duplicados en la misma sublínea/color/talla; se priorizó tej_princ=si.";
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
			if ($esTela) {
				$nombreRol = "Tela principal";
			}

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
}
