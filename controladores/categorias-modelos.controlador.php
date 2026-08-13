<?php

class ControladorCategoriasModelos
{
	static private function ctrPuedeVer()
	{
		return isset($_SESSION["maestros"]) && (int) $_SESSION["maestros"] === 1;
	}

	static private function ctrPuedeEditar()
	{
		return self::ctrPuedeVer();
	}

	static private function ctrUsuarioId()
	{
		return isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
	}

	static public function ctrCatalogo()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$filas = ModeloCategoriasModelos::mdlCatalogoActivo();
		$categorias = array();
		foreach ($filas as $f) {
			$idCat = (int) $f["id_categoria"];
			if (!isset($categorias[$idCat])) {
				$categorias[$idCat] = array(
					"id" => $idCat,
					"codigo" => $f["codigo_categoria"],
					"nombre" => $f["nombre_categoria"],
					"modelos_categoria" => isset($f["modelos_categoria"]) ? (int) $f["modelos_categoria"] : 0,
					"modelos_solo_categoria" => isset($f["modelos_solo_categoria"]) ? (int) $f["modelos_solo_categoria"] : 0,
					"subcategorias" => array()
				);
			}
			if ($f["id_subcategoria"] === null || (int) $f["id_subcategoria"] < 1) {
				continue;
			}
			$categorias[$idCat]["subcategorias"][] = array(
				"id" => (int) $f["id_subcategoria"],
				"codigo" => $f["codigo_subcategoria"],
				"nombre" => $f["nombre_subcategoria"],
				"modelos_activos" => isset($f["modelos_activos"]) ? (int) $f["modelos_activos"] : 0
			);
		}
		$conteos = ModeloCategoriasModelos::mdlConteos(0, "");
		return array(
			"ok" => true,
			"categorias" => array_values($categorias),
			"marcas" => ModeloCategoriasModelos::mdlListarMarcasActivas(),
			"conteos" => $conteos
		);
	}

	static private function ctrSugerenciaCodigo($tipo, $linea, $nombre = "")
	{
		$tipo = strtoupper(trim((string) $tipo));
		$linea = strtoupper(preg_replace('/\s+/', ' ', trim((string) $linea)));
		$nombre = strtoupper(preg_replace('/\s+/', ' ', trim((string) $nombre)));

		$mapaLinea = array(
			"TANGA" => "TRUSA_HILO",
			"SEMI HILO" => "TRUSA_HILO",
			"HILO" => "TRUSA_HILO",
			"HILO CONTROL" => "TRUSA_HILO",
			"BIKINI" => "TRUSA_BIKINI",
			"TRUSA CLASICA" => "TRUSA_BIKINI",
			"CACHETERO" => "TRUSA_CACHETERO",
			"BOXER" => "TRUSA_CULOTTE",
			"TRUSA CONTROL" => "TRUSA_ALTA_CINTURA",
			"BRASIER BASICO" => "BRASIER_BASICO",
			"BRASIER SENORIAL SIN COPA" => "BRASIER_BASICO",
			"BRASIER SEÑORIAL SIN COPA" => "BRASIER_BASICO",
			"BRASIER ENCAJE" => "BRASIER_BASICO",
			"BRALLETTE" => "BRASIER_BASICO",
			"BRASIER CON REALCE" => "BRASIER_PUSHUP",
			"ESTRAPLE BASICO" => "BRASIER_STRAPLESS",
			"ESTRAPLE FAJA ANCHA" => "BRASIER_STRAPLESS",
			"BRASIER MATERNAL" => "BRASIER_LACTANCIA",
			"BRASIER FAJA ANCHA" => "BRASIER_BASICO",
			"FORMADOR CON COPA" => "BRASIER_PREFORMADA",
			"BODY FAJA" => "FAJA_BODY",
			"SHORT CONTROL" => "FAJA_SHORT",
			"CINTURILLA" => "FAJA_CHALECO"
		);

		if ($tipo === "FAJA" && $linea === "TRUSA CONTROL") {
			return "FAJA_PANTY";
		}
		if ($tipo === "FAJA" && $linea === "HILO CONTROL") {
			return "FAJA_PANTY";
		}
		if ($tipo === "BRASIER" && $linea === "BODY FAJA") {
			return null;
		}
		if ($linea !== "" && $linea !== "PENDIENTE" && isset($mapaLinea[$linea])) {
			return $mapaLinea[$linea];
		}

		// Pistas por nombre solo si la línea no aporta (orientativo; no autoasigna).
		if ($linea === "" || $linea === "PENDIENTE") {
			if ($tipo === "TRUSA" || $tipo === "BOXER V" || strpos($nombre, "TANGA") !== false || strpos($nombre, "TRUSA") !== false || strpos($nombre, "BIKINI") !== false || strpos($nombre, "HILO") !== false) {
				if (strpos($nombre, "CACHETERO") !== false) {
					return "TRUSA_CACHETERO";
				}
				if (strpos($nombre, "TANGA") !== false || strpos($nombre, "HILO") !== false || strpos($nombre, "SEMI HILO") !== false) {
					return "TRUSA_HILO";
				}
				if (strpos($nombre, "BIKINI") !== false) {
					return "TRUSA_BIKINI";
				}
				if (strpos($nombre, "BOXER") !== false || strpos($nombre, "CULOTTE") !== false) {
					return "TRUSA_CULOTTE";
				}
				if (strpos($nombre, "CONTROL") !== false || strpos($nombre, "ALTA") !== false) {
					return "TRUSA_ALTA_CINTURA";
				}
				if ($tipo === "TRUSA" || strpos($nombre, "TRUSA") !== false) {
					return "TRUSA_BIKINI";
				}
			}
			if ($tipo === "BRASIER" || strpos($nombre, "BRASIER") !== false || strpos($nombre, "STRAPLE") !== false || strpos($nombre, "ESTRAPLE") !== false || strpos($nombre, "BRALETTE") !== false || strpos($nombre, "BUSTIER") !== false) {
				if (strpos($nombre, "PUSH") !== false || strpos($nombre, "REALCE") !== false) {
					return "BRASIER_PUSHUP";
				}
				if (strpos($nombre, "STRAPLE") !== false || strpos($nombre, "ESTRAPLE") !== false || strpos($nombre, "STRAPLESS") !== false) {
					return "BRASIER_STRAPLESS";
				}
				if (strpos($nombre, "DEPORT") !== false || strpos($nombre, "SPORT") !== false) {
					return "BRASIER_DEPORTIVO";
				}
				if (strpos($nombre, "MATERNAL") !== false || strpos($nombre, "LACTANCIA") !== false) {
					return "BRASIER_LACTANCIA";
				}
				if (strpos($nombre, "BRALETTE") !== false || strpos($nombre, "BRALLETTE") !== false || strpos($nombre, "TRIANGULAR") !== false) {
					return "BRASIER_BASICO";
				}
				return "BRASIER_BASICO";
			}
			if ($tipo === "FAJA" || strpos($nombre, "FAJA") !== false || strpos($nombre, "BODY") !== false || strpos($nombre, "CINTURILLA") !== false) {
				if (strpos($nombre, "SHORT") !== false || strpos($nombre, "BERMUDA") !== false) {
					return "FAJA_SHORT";
				}
				if (strpos($nombre, "BODY") !== false) {
					return "FAJA_BODY";
				}
				if (strpos($nombre, "CINTURILLA") !== false || strpos($nombre, "CHALECO") !== false) {
					return "FAJA_CHALECO";
				}
				if (strpos($nombre, "POSTPARTO") !== false) {
					return "FAJA_POSTPARTO";
				}
				return "FAJA_PANTY";
			}
		}

		return null;
	}

	static public function ctrListar($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}

		$estadoLista = isset($post["estado_lista"]) ? trim((string) $post["estado_lista"]) : "pendientes";
		if (!in_array($estadoLista, array("pendientes", "en_categoria", "clasificados", "pendientes_o_categoria", "todos"), true)) {
			$estadoLista = "pendientes";
		}

		$filtros = array(
			"q" => isset($post["q"]) ? trim((string) $post["q"]) : "",
			"id_marca" => isset($post["id_marca"]) ? (int) $post["id_marca"] : 0,
			"id_categoria" => isset($post["id_categoria"]) ? (int) $post["id_categoria"] : 0,
			"id_categoria_pool" => isset($post["id_categoria_pool"]) ? (int) $post["id_categoria_pool"] : 0,
			"id_subcategoria" => isset($post["id_subcategoria"]) ? (int) $post["id_subcategoria"] : 0,
			"estado_lista" => $estadoLista,
			"pagina" => isset($post["pagina"]) ? (int) $post["pagina"] : 1,
			"limite" => isset($post["limite"]) ? (int) $post["limite"] : 50
		);

		if (mb_strlen($filtros["q"]) > 80) {
			$filtros["q"] = mb_substr($filtros["q"], 0, 80);
		}

		$lista = ModeloCategoriasModelos::mdlListar($filtros);
		$conteos = ModeloCategoriasModelos::mdlConteos($filtros["id_marca"], $filtros["q"]);

		$codigoAId = array();
		foreach (ModeloCategoriasModelos::mdlCatalogoActivo() as $c) {
			$codigoAId[$c["codigo_subcategoria"]] = (int) $c["id_subcategoria"];
		}

		$filas = array();
		foreach ($lista["filas"] as $f) {
			$sugCodigo = self::ctrSugerenciaCodigo($f["tipo"], $f["linea"], $f["nombre"]);
			$sugId = ($sugCodigo && isset($codigoAId[$sugCodigo])) ? $codigoAId[$sugCodigo] : null;
			$filas[] = array(
				"modelo" => $f["modelo"],
				"nombre" => $f["nombre"],
				"id_marca" => (int) $f["id_marca"],
				"marca" => $f["marca"],
				"tipo" => $f["tipo"],
				"linea" => $f["linea"],
				"imagen" => $f["imagen"],
				"id_subcategoria" => $f["id_subcategoria"] !== null ? (int) $f["id_subcategoria"] : null,
				"nombre_subcategoria" => $f["nombre_subcategoria"],
				"id_categoria" => $f["id_categoria"] !== null ? (int) $f["id_categoria"] : null,
				"nombre_categoria" => $f["nombre_categoria"],
				"sugerencia_codigo" => $sugCodigo,
				"sugerencia_id" => $sugId,
				"fecha_asignacion" => $f["fecha_asignacion"],
				"actualizado_en" => $f["actualizado_en"] ? $f["actualizado_en"] : $f["fecha_asignacion"],
				"usuario_asignacion" => $f["usuario_asignacion"] !== null ? (int) $f["usuario_asignacion"] : null,
				"nombre_usuario" => $f["nombre_usuario_actualizacion"] !== ""
					? $f["nombre_usuario_actualizacion"]
					: $f["nombre_usuario_asignacion"]
			);
		}

		return array(
			"ok" => true,
			"filas" => $filas,
			"total" => $lista["total"],
			"pagina" => $lista["pagina"],
			"limite" => $lista["limite"],
			"conteos" => $conteos,
			"puede_editar" => self::ctrPuedeEditar()
		);
	}

	static public function ctrAsignar($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		$idSubcategoria = isset($post["id_subcategoria"]) ? (int) $post["id_subcategoria"] : 0;
		$observacion = isset($post["observacion"]) ? $post["observacion"] : null;

		if ($modelo === "" || !preg_match('/^[A-Za-z0-9_\-]{1,10}$/', $modelo)) {
			return array("ok" => false, "mensaje" => "Código de modelo inválido");
		}
		if ($idSubcategoria < 1) {
			return array("ok" => false, "mensaje" => "Subcategoría requerida");
		}

		$resultado = ModeloCategoriasModelos::mdlAsignar(
			$modelo,
			$idSubcategoria,
			self::ctrUsuarioId(),
			$observacion,
			"pantalla"
		);
		if (!$resultado["ok"]) {
			return $resultado;
		}
		$conteos = ModeloCategoriasModelos::mdlConteos(0, "");
		$resultado["conteos"] = $conteos;
		return $resultado;
	}

	static public function ctrAsignarLote($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$idSubcategoria = isset($post["id_subcategoria"]) ? (int) $post["id_subcategoria"] : 0;
		$idCategoria = isset($post["id_categoria"]) ? (int) $post["id_categoria"] : 0;
		$destinoCategoria = $idSubcategoria < 1 && $idCategoria > 0;

		if ($idSubcategoria < 1 && !$destinoCategoria) {
			return array("ok" => false, "mensaje" => "Elija una categoría o subcategoría destino");
		}

		$sub = null;
		$cat = null;
		if ($destinoCategoria) {
			$cat = ModeloCategoriasModelos::mdlCategoriaActiva($idCategoria);
			if (!$cat) {
				return array("ok" => false, "mensaje" => "Categoría inválida o inactiva");
			}
		} else {
			$sub = ModeloCategoriasModelos::mdlSubcategoriaActiva($idSubcategoria);
			if (!$sub) {
				return array("ok" => false, "mensaje" => "Subcategoría inválida o inactiva");
			}
		}

		$modelosRaw = array();
		if (isset($post["modelos"]) && is_array($post["modelos"])) {
			$modelosRaw = $post["modelos"];
		} elseif (isset($post["modelos"]) && is_string($post["modelos"])) {
			$decoded = json_decode($post["modelos"], true);
			if (is_array($decoded)) {
				$modelosRaw = $decoded;
			} else {
				$modelosRaw = preg_split('/\s*,\s*/', $post["modelos"]);
			}
		}

		$modelos = array();
		foreach ($modelosRaw as $m) {
			$m = trim((string) $m);
			if ($m === "" || !preg_match('/^[A-Za-z0-9_\-]{1,10}$/', $m)) {
				continue;
			}
			$modelos[$m] = $m;
		}
		$modelos = array_values($modelos);

		if (empty($modelos)) {
			return array("ok" => false, "mensaje" => "Seleccione al menos un modelo válido");
		}
		if (count($modelos) > 100) {
			return array("ok" => false, "mensaje" => "Máximo 100 modelos por lote");
		}

		$usuarioId = self::ctrUsuarioId();
		$asignados = 0;
		$omitidos = 0;
		$errores = array();

		foreach ($modelos as $modelo) {
			if ($destinoCategoria) {
				$resultado = ModeloCategoriasModelos::mdlAsignarCategoria(
					$modelo,
					$idCategoria,
					$usuarioId,
					null,
					"lote",
					true
				);
			} else {
				$resultado = ModeloCategoriasModelos::mdlAsignar(
					$modelo,
					$idSubcategoria,
					$usuarioId,
					null,
					"lote"
				);
			}
			if (!$resultado["ok"]) {
				$errores[] = array(
					"modelo" => $modelo,
					"mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "Error"
				);
				continue;
			}
			if (!empty($resultado["idempotente"])) {
				$omitidos++;
			} else {
				$asignados++;
			}
		}

		return array(
			"ok" => true,
			"asignados" => $asignados,
			"omitidos" => $omitidos,
			"errores" => $errores,
			"total_enviados" => count($modelos),
			"destino" => $destinoCategoria
				? array(
					"id_subcategoria" => null,
					"nombre_subcategoria" => null,
					"id_categoria" => (int) $cat["id_categoria"],
					"nombre_categoria" => $cat["nombre_categoria"]
				)
				: array(
					"id_subcategoria" => (int) $sub["id_subcategoria"],
					"nombre_subcategoria" => $sub["nombre_subcategoria"],
					"id_categoria" => (int) $sub["id_categoria"],
					"nombre_categoria" => $sub["nombre_categoria"]
				),
			"conteos" => ModeloCategoriasModelos::mdlConteos(0, ""),
			"mensaje" => $asignados . " asignado(s)"
				. ($omitidos ? ", " . $omitidos . " sin cambio" : "")
				. (count($errores) ? ", " . count($errores) . " con error" : "")
		);
	}

	static public function ctrQuitarLote($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$modelosRaw = array();
		if (isset($post["modelos"]) && is_array($post["modelos"])) {
			$modelosRaw = $post["modelos"];
		} elseif (isset($post["modelos"]) && is_string($post["modelos"])) {
			$decoded = json_decode($post["modelos"], true);
			if (is_array($decoded)) {
				$modelosRaw = $decoded;
			} else {
				$modelosRaw = preg_split('/\s*,\s*/', $post["modelos"]);
			}
		}

		$modelos = array();
		foreach ($modelosRaw as $m) {
			$m = trim((string) $m);
			if ($m === "" || !preg_match('/^[A-Za-z0-9_\-]{1,10}$/', $m)) {
				continue;
			}
			$modelos[$m] = $m;
		}
		$modelos = array_values($modelos);

		if (empty($modelos)) {
			return array("ok" => false, "mensaje" => "Seleccione al menos un modelo");
		}
		if (count($modelos) > 100) {
			return array("ok" => false, "mensaje" => "Máximo 100 modelos por lote");
		}

		$usuarioId = self::ctrUsuarioId();
		$quitados = 0;
		$omitidos = 0;
		$errores = array();

		foreach ($modelos as $modelo) {
			$resultado = ModeloCategoriasModelos::mdlQuitar($modelo, $usuarioId, "lote");
			if (!$resultado["ok"]) {
				$errores[] = array(
					"modelo" => $modelo,
					"mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "Error"
				);
				continue;
			}
			if (!empty($resultado["idempotente"])) {
				$omitidos++;
			} else {
				$quitados++;
			}
		}

		return array(
			"ok" => true,
			"quitados" => $quitados,
			"omitidos" => $omitidos,
			"errores" => $errores,
			"conteos" => ModeloCategoriasModelos::mdlConteos(0, ""),
			"mensaje" => $quitados . " quitado(s)"
				. ($omitidos ? ", " . $omitidos . " ya sin asignar" : "")
				. (count($errores) ? ", " . count($errores) . " con error" : "")
		);
	}

	static public function ctrHistorial($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		if ($modelo === "" || !preg_match('/^[A-Za-z0-9_\-]{1,10}$/', $modelo)) {
			return array("ok" => false, "mensaje" => "Código de modelo inválido");
		}
		$filas = ModeloCategoriasModelos::mdlHistorial($modelo);
		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"id" => (int) $f["id"],
				"accion" => $f["accion"],
				"fecha" => $f["fecha"],
				"usuario_nombre" => $f["usuario_nombre"],
				"desde" => self::ctrEtiquetaHistorial($f, "anterior"),
				"hasta" => $f["accion"] === "BAJA"
					? "Sin asignar"
					: self::ctrEtiquetaHistorial($f, "nueva"),
				"observacion" => $f["observacion"]
			);
		}
		return array("ok" => true, "modelo" => $modelo, "historial" => $data);
	}

	static private function ctrEtiquetaHistorial($f, $lado)
	{
		$idSub = $lado === "anterior" ? $f["id_subcategoria_anterior"] : $f["id_subcategoria_nueva"];
		$idCat = $lado === "anterior"
			? (isset($f["id_categoria_anterior"]) ? $f["id_categoria_anterior"] : null)
			: (isset($f["id_categoria_nueva"]) ? $f["id_categoria_nueva"] : null);
		$nomSub = $lado === "anterior" ? $f["nombre_sub_anterior"] : $f["nombre_sub_nueva"];
		$nomCat = $lado === "anterior" ? $f["nombre_cat_anterior"] : $f["nombre_cat_nueva"];
		if ($idSub) {
			return ($nomCat ? $nomCat . " › " : "") . $nomSub;
		}
		if ($idCat || $nomCat) {
			return $nomCat ? $nomCat : "Categoría";
		}
		return "—";
	}

	static public function ctrHistorialReciente($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idSub = isset($post["id_subcategoria"]) ? (int) $post["id_subcategoria"] : 0;
		$limite = isset($post["limite"]) ? (int) $post["limite"] : 40;
		$filas = ModeloCategoriasModelos::mdlHistorialReciente($limite, $idSub);
		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"id" => (int) $f["id"],
				"modelo" => $f["modelo"],
				"nombre_modelo" => $f["nombre_modelo"],
				"accion" => $f["accion"],
				"fecha" => $f["fecha"],
				"origen" => $f["origen"],
				"usuario_nombre" => $f["usuario_nombre"],
				"desde" => self::ctrEtiquetaHistorial($f, "anterior"),
				"hasta" => $f["accion"] === "BAJA"
					? "Sin asignar"
					: self::ctrEtiquetaHistorial($f, "nueva")
			);
		}
		return array("ok" => true, "historial" => $data);
	}

	static private function ctrSlugBase($texto, $maxLen = 40)
	{
		$texto = trim((string) $texto);
		if ($texto === "") {
			return "ITEM";
		}
		$map = array(
			"Á" => "A", "À" => "A", "Ä" => "A", "Â" => "A", "Ã" => "A",
			"É" => "E", "È" => "E", "Ë" => "E", "Ê" => "E",
			"Í" => "I", "Ì" => "I", "Ï" => "I", "Î" => "I",
			"Ó" => "O", "Ò" => "O", "Ö" => "O", "Ô" => "O", "Õ" => "O",
			"Ú" => "U", "Ù" => "U", "Ü" => "U", "Û" => "U",
			"Ñ" => "N", "Ç" => "C",
			"á" => "A", "à" => "A", "ä" => "A", "â" => "A", "ã" => "A",
			"é" => "E", "è" => "E", "ë" => "E", "ê" => "E",
			"í" => "I", "ì" => "I", "ï" => "I", "î" => "I",
			"ó" => "O", "ò" => "O", "ö" => "O", "ô" => "O", "õ" => "O",
			"ú" => "U", "ù" => "U", "ü" => "U", "û" => "U",
			"ñ" => "N", "ç" => "C"
		);
		$texto = strtr($texto, $map);
		$texto = strtoupper($texto);
		$texto = preg_replace('/[^A-Z0-9]+/', "_", $texto);
		$texto = trim($texto, "_");
		$texto = preg_replace('/_+/', "_", $texto);
		if ($texto === "") {
			$texto = "ITEM";
		}
		if (strlen($texto) > $maxLen) {
			$texto = rtrim(substr($texto, 0, $maxLen), "_");
		}
		return $texto !== "" ? $texto : "ITEM";
	}

	static private function ctrGenerarCodigoCategoria($nombre)
	{
		$base = self::ctrSlugBase($nombre, 40);
		$codigo = $base;
		$i = 2;
		while (ModeloCategoriasModelos::mdlCategoriaPorCodigo($codigo)) {
			$sufijo = "_" . $i;
			$codigo = substr($base, 0, max(1, 50 - strlen($sufijo))) . $sufijo;
			$i++;
			if ($i > 99) {
				$codigo = "CAT_" . date("YmdHis");
				break;
			}
		}
		return $codigo;
	}

	static private function ctrGenerarCodigoSubcategoria($nombre, $idCategoria)
	{
		$cat = ModeloCategoriasModelos::mdlCategoriaPorId($idCategoria);
		$prefijo = $cat ? self::ctrSlugBase($cat["codigo"], 20) : "SUB";
		$slug = self::ctrSlugBase($nombre, 40);
		$base = $prefijo . "_" . $slug;
		if (strlen($base) > 70) {
			$base = substr($base, 0, 70);
			$base = rtrim($base, "_");
		}
		$codigo = $base;
		$i = 2;
		while (ModeloCategoriasModelos::mdlSubcategoriaPorCodigo($codigo)) {
			$sufijo = "_" . $i;
			$codigo = substr($base, 0, max(1, 70 - strlen($sufijo))) . $sufijo;
			$i++;
			if ($i > 99) {
				$codigo = "SUB_" . date("YmdHis");
				break;
			}
		}
		return $codigo;
	}

	static public function ctrListarAdmin()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		return array(
			"ok" => true,
			"puede_editar" => self::ctrPuedeEditar(),
			"categorias" => ModeloCategoriasModelos::mdlListarCategoriasAdmin(),
			"subcategorias" => ModeloCategoriasModelos::mdlListarSubcategoriasAdmin(0),
			"conteos" => ModeloCategoriasModelos::mdlConteos(0, "")
		);
	}

	static public function ctrListarModelosCategoria($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$id = isset($post["id_categoria"]) ? (int) $post["id_categoria"] : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Categoría inválida");
		}
		$cat = ModeloCategoriasModelos::mdlCategoriaPorId($id);
		if (!$cat) {
			return array("ok" => false, "mensaje" => "Categoría no encontrada");
		}
		$filas = ModeloCategoriasModelos::mdlListarModelosPorCategoria($id, false);
		$modelos = array();
		$activos = 0;
		foreach ($filas as $f) {
			$esActivo = $f["estado"] === "ACTIVO";
			if ($esActivo) {
				$activos++;
			}
			$modelos[] = array(
				"modelo" => $f["modelo"],
				"nombre" => $f["nombre"],
				"marca" => $f["marca"],
				"tipo" => $f["tipo"],
				"linea" => $f["linea"],
				"imagen" => $f["imagen"],
				"estado" => $f["estado"],
				"id_subcategoria" => $f["id_subcategoria"] !== null ? (int) $f["id_subcategoria"] : null,
				"nombre_subcategoria" => $f["nombre_subcategoria"],
				"fecha" => $f["actualizado_en"] ? $f["actualizado_en"] : $f["fecha_asignacion"],
				"usuario_nombre" => $f["usuario_nombre"]
			);
		}
		return array(
			"ok" => true,
			"categoria" => array(
				"id" => (int) $cat["id"],
				"codigo" => $cat["codigo"],
				"nombre" => $cat["nombre"]
			),
			"total" => count($modelos),
			"activos" => $activos,
			"modelos" => $modelos
		);
	}

	static public function ctrListarModelosSubcategoria($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$id = isset($post["id_subcategoria"]) ? (int) $post["id_subcategoria"] : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Subcategoría inválida");
		}
		$sub = ModeloCategoriasModelos::mdlSubcategoriaPorId($id);
		if (!$sub) {
			return array("ok" => false, "mensaje" => "Subcategoría no encontrada");
		}
		$cat = ModeloCategoriasModelos::mdlCategoriaPorId((int) $sub["id_categoria"]);
		$filas = ModeloCategoriasModelos::mdlListarModelosPorSubcategoria($id);
		$modelos = array();
		$activos = 0;
		foreach ($filas as $f) {
			$esActivo = $f["estado"] === "ACTIVO";
			if ($esActivo) {
				$activos++;
			}
			$modelos[] = array(
				"modelo" => $f["modelo"],
				"nombre" => $f["nombre"],
				"marca" => $f["marca"],
				"tipo" => $f["tipo"],
				"linea" => $f["linea"],
				"imagen" => $f["imagen"],
				"estado" => $f["estado"],
				"fecha" => $f["actualizado_en"] ? $f["actualizado_en"] : $f["fecha_asignacion"],
				"usuario_nombre" => $f["usuario_nombre"]
			);
		}
		return array(
			"ok" => true,
			"subcategoria" => array(
				"id" => (int) $sub["id"],
				"codigo" => $sub["codigo"],
				"nombre" => $sub["nombre"],
				"categoria" => $cat ? $cat["nombre"] : ""
			),
			"total" => count($modelos),
			"activos" => $activos,
			"modelos" => $modelos
		);
	}

	static public function ctrGuardarCategoria($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$nombre = trim(isset($post["nombre"]) ? (string) $post["nombre"] : "");
		$orden = isset($post["orden"]) ? (int) $post["orden"] : 0;
		$estado = isset($post["estado"]) ? ((int) $post["estado"] === 0 ? 0 : 1) : 1;

		if ($nombre === "" || mb_strlen($nombre) > 100) {
			return array("ok" => false, "mensaje" => "Nombre inválido");
		}
		if ($orden < 0 || $orden > 9999) {
			$orden = 0;
		}

		if ($id > 0) {
			$actual = ModeloCategoriasModelos::mdlCategoriaPorId($id);
			if (!$actual) {
				return array("ok" => false, "mensaje" => "Categoría no encontrada");
			}
			if ($estado === 0) {
				$n = ModeloCategoriasModelos::mdlContarModelosActivosCategoria($id);
				if ($n > 0) {
					return array(
						"ok" => false,
						"mensaje" => "No se puede desactivar: hay $n modelo(s) activo(s) asignados. Reasigne antes."
					);
				}
			}
			$ok = ModeloCategoriasModelos::mdlGuardarCategoria(array(
				"id" => $id,
				"nombre" => $nombre,
				"orden" => $orden,
				"estado" => $estado,
				"usuario_id" => self::ctrUsuarioId()
			));
			return $ok === "ok"
				? array("ok" => true, "mensaje" => "Categoría actualizada")
				: array("ok" => false, "mensaje" => "No se pudo guardar");
		}

		$codigo = self::ctrGenerarCodigoCategoria($nombre);
		$ok = ModeloCategoriasModelos::mdlGuardarCategoria(array(
			"codigo" => $codigo,
			"nombre" => $nombre,
			"orden" => $orden,
			"estado" => $estado,
			"usuario_id" => self::ctrUsuarioId()
		));
		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Categoría creada", "codigo" => $codigo)
			: array("ok" => false, "mensaje" => "No se pudo crear (¿nombre duplicado?)");
	}

	static public function ctrGuardarSubcategoria($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$idCategoria = isset($post["id_categoria"]) ? (int) $post["id_categoria"] : 0;
		$nombre = trim(isset($post["nombre"]) ? (string) $post["nombre"] : "");
		$orden = isset($post["orden"]) ? (int) $post["orden"] : 0;
		$estado = isset($post["estado"]) ? ((int) $post["estado"] === 0 ? 0 : 1) : 1;

		if ($idCategoria < 1 || !ModeloCategoriasModelos::mdlCategoriaPorId($idCategoria)) {
			return array("ok" => false, "mensaje" => "Categoría inválida");
		}
		if ($nombre === "" || mb_strlen($nombre) > 100) {
			return array("ok" => false, "mensaje" => "Nombre inválido");
		}
		if ($orden < 0 || $orden > 9999) {
			$orden = 0;
		}

		if ($id > 0) {
			$actual = ModeloCategoriasModelos::mdlSubcategoriaPorId($id);
			if (!$actual) {
				return array("ok" => false, "mensaje" => "Subcategoría no encontrada");
			}
			if ($estado === 0) {
				$n = ModeloCategoriasModelos::mdlContarModelosActivosSubcategoria($id);
				if ($n > 0) {
					return array(
						"ok" => false,
						"mensaje" => "No se puede desactivar: hay $n modelo(s) activo(s) asignados. Reasigne antes."
					);
				}
			}
			$ok = ModeloCategoriasModelos::mdlGuardarSubcategoria(array(
				"id" => $id,
				"id_categoria" => $idCategoria,
				"nombre" => $nombre,
				"orden" => $orden,
				"estado" => $estado,
				"usuario_id" => self::ctrUsuarioId()
			));
			return $ok === "ok"
				? array("ok" => true, "mensaje" => "Subcategoría actualizada")
				: array("ok" => false, "mensaje" => "No se pudo guardar");
		}

		$codigo = self::ctrGenerarCodigoSubcategoria($nombre, $idCategoria);
		$ok = ModeloCategoriasModelos::mdlGuardarSubcategoria(array(
			"id_categoria" => $idCategoria,
			"codigo" => $codigo,
			"nombre" => $nombre,
			"orden" => $orden,
			"estado" => $estado,
			"usuario_id" => self::ctrUsuarioId()
		));
		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Subcategoría creada", "codigo" => $codigo)
			: array("ok" => false, "mensaje" => "No se pudo crear (¿nombre duplicado en la categoría?)");
	}

	static public function ctrEliminarSubcategoria($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Id inválido");
		}

		$actual = ModeloCategoriasModelos::mdlSubcategoriaPorId($id);
		if (!$actual) {
			return array("ok" => false, "mensaje" => "Subcategoría no encontrada");
		}

		$asignaciones = ModeloCategoriasModelos::mdlContarAsignacionesSubcategoria($id);
		if ($asignaciones > 0) {
			return array(
				"ok" => false,
				"mensaje" => "No se puede eliminar: hay $asignaciones modelo(s) asignado(s). Reasigne antes."
			);
		}

		$historial = ModeloCategoriasModelos::mdlContarHistorialSubcategoria($id);
		if ($historial > 0) {
			return array(
				"ok" => false,
				"mensaje" => "No se puede eliminar: tiene historial de asignaciones. Desactívela en su lugar."
			);
		}

		$ok = ModeloCategoriasModelos::mdlEliminarSubcategoria($id);
		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Subcategoría eliminada")
			: array("ok" => false, "mensaje" => "No se pudo eliminar");
	}
}
