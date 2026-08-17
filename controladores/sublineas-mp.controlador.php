<?php

class ControladorSublineasMp
{
	static private function asegurarTimezone()
	{
		date_default_timezone_set("America/Lima");
	}

	static private function usuarioActual()
	{
		if (isset($_SESSION["nombre"]) && trim((string) $_SESSION["nombre"]) !== "") {
			return (string) $_SESSION["nombre"];
		}
		if (isset($_SESSION["usuario"]) && trim((string) $_SESSION["usuario"]) !== "") {
			return (string) $_SESSION["usuario"];
		}
		return "";
	}

	static private function pcActual()
	{
		$ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "";
		$pc = $ip !== "" ? gethostbyaddr($ip) : "";
		return $pc ? $pc : "";
	}

	static private function texto($post, $clave)
	{
		if (!isset($post[$clave])) {
			return "";
		}
		return trim((string) $post[$clave]);
	}

	static public function ctrLineas()
	{
		$filas = ModeloSublineasMp::mdlListarLineas();
		$lineas = array();
		foreach ($filas as $row) {
			$codigo = isset($row["codigo"]) ? trim((string) $row["codigo"]) : "";
			if ($codigo === "") {
				continue;
			}
			$lineas[] = array(
				"codigo" => $codigo,
				"argumento" => isset($row["argumento"]) ? trim((string) $row["argumento"]) : "",
				"nombre" => isset($row["nombre"]) ? trim((string) $row["nombre"]) : ""
			);
		}
		return array("ok" => true, "lineas" => $lineas);
	}

	static public function ctrListar()
	{
		$filas = ModeloSublineasMp::mdlListarSublineas();
		$gruposMap = array();

		foreach ($filas as $row) {
			$linea = isset($row["linea"]) ? strtoupper(trim((string) $row["linea"])) : "";
			if ($linea === "") {
				$linea = "(SIN LÍNEA)";
			}
			if (!isset($gruposMap[$linea])) {
				$gruposMap[$linea] = array(
					"linea" => $linea,
					"nombre_linea" => isset($row["nombre_linea"]) ? trim((string) $row["nombre_linea"]) : "",
					"linea_arg" => isset($row["linea_arg"]) ? trim((string) $row["linea_arg"]) : "",
					"items" => array()
				);
			}
			$gruposMap[$linea]["items"][] = array(
				"cod_argumento" => isset($row["cod_argumento"]) ? trim((string) $row["cod_argumento"]) : "",
				"linea" => $linea === "(SIN LÍNEA)" ? "" : $linea,
				"subcodigo" => isset($row["subcodigo"]) ? trim((string) $row["subcodigo"]) : "",
				"codigo_sublinea" => isset($row["codigo_sublinea"]) ? strtoupper(trim((string) $row["codigo_sublinea"])) : "",
				"nombre" => isset($row["nombre"]) ? trim((string) $row["nombre"]) : "",
				"valor_1" => isset($row["valor_1"]) ? trim((string) $row["valor_1"]) : "",
				"valor_2" => isset($row["valor_2"]) ? trim((string) $row["valor_2"]) : "",
				"valor_4" => isset($row["valor_4"]) ? trim((string) $row["valor_4"]) : "",
				"valor_5" => isset($row["valor_5"]) ? trim((string) $row["valor_5"]) : "",
				"mp_activas" => isset($row["mp_activas"]) ? (int) $row["mp_activas"] : 0
			);
		}

		$grupos = array_values($gruposMap);
		$total = 0;
		foreach ($grupos as $g) {
			$total += count($g["items"]);
		}

		return array(
			"ok" => true,
			"total" => $total,
			"grupos" => $grupos
		);
	}

	static public function ctrPreview($post)
	{
		$linea = strtoupper(self::texto($post, "linea"));
		if ($linea === "") {
			return array("ok" => false, "mensaje" => "Selecciona una línea");
		}

		$tlin = ModeloSublineasMp::mdlLineaExiste($linea);
		if (!$tlin) {
			return array("ok" => false, "mensaje" => "La línea no existe en TLIN");
		}

		$arg = ModeloSublineasMp::mdlSiguienteArgumento();
		$subcodigo = ModeloSublineasMp::mdlSiguienteSubcodigo($linea);

		return array(
			"ok" => true,
			"linea" => $linea,
			"nombre_linea" => isset($tlin["nombre"]) ? trim((string) $tlin["nombre"]) : "",
			"cod_argumento" => $arg["correlativo"],
			"subcodigo" => $subcodigo,
			"codigo_sublinea" => $linea . $subcodigo
		);
	}

	static public function ctrCrear($post)
	{
		self::asegurarTimezone();

		$linea = strtoupper(self::texto($post, "linea"));
		$nombre = self::texto($post, "nombre");
		$valor1 = self::texto($post, "valor_1");
		$valor2 = self::texto($post, "valor_2");
		$valor4 = self::texto($post, "valor_4");
		$valor5 = self::texto($post, "valor_5");

		if ($linea === "") {
			return array("ok" => false, "mensaje" => "Selecciona una línea");
		}
		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "Ingresa el nombre de la sublínea");
		}

		$tlin = ModeloSublineasMp::mdlLineaExiste($linea);
		if (!$tlin) {
			return array("ok" => false, "mensaje" => "La línea no existe en TLIN");
		}

		$arg = ModeloSublineasMp::mdlSiguienteArgumento();
		$subcodigo = ModeloSublineasMp::mdlSiguienteSubcodigo($linea);

		if (ModeloSublineasMp::mdlExisteCodigo($linea, $subcodigo)) {
			return array("ok" => false, "mensaje" => "Ya existe una sublínea " . $linea . $subcodigo . ". Recarga e inténtalo de nuevo.");
		}

		$fecha = date("Y-m-d H:i:s");
		$datos = array(
			"cod_argumento" => $arg["correlativo"],
			"cod_local" => "01",
			"cod_entidad" => "01",
			"des_larga" => $nombre,
			"des_corta" => $linea,
			"valor_1" => $valor1,
			"valor_2" => $valor2,
			"valor_3" => $subcodigo,
			"valor_4" => $valor4,
			"valor_5" => $valor5,
			"fecreg" => $fecha,
			"usureg" => self::usuarioActual(),
			"pcreg" => self::pcActual()
		);

		$respuesta = ModeloSublineasMp::mdlCrear($datos);
		if ($respuesta !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo guardar la sublínea");
		}

		return array(
			"ok" => true,
			"mensaje" => "Sublínea creada",
			"cod_argumento" => $arg["correlativo"],
			"codigo_sublinea" => $linea . $subcodigo
		);
	}

	static public function ctrEditar($post)
	{
		self::asegurarTimezone();

		$codArgumento = self::texto($post, "cod_argumento");
		$nombre = self::texto($post, "nombre");
		$valor1 = self::texto($post, "valor_1");
		$valor2 = self::texto($post, "valor_2");
		$valor4 = self::texto($post, "valor_4");
		$valor5 = self::texto($post, "valor_5");

		if ($codArgumento === "") {
			return array("ok" => false, "mensaje" => "Falta el código interno");
		}
		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "Ingresa el nombre de la sublínea");
		}

		$actual = ModeloSublineasMp::mdlMostrar($codArgumento);
		if (!$actual) {
			return array("ok" => false, "mensaje" => "No se encontró la sublínea");
		}

		$fecha = date("Y-m-d H:i:s");
		$datos = array(
			"cod_argumento" => $codArgumento,
			"des_larga" => $nombre,
			"valor_1" => $valor1,
			"valor_2" => $valor2,
			"valor_4" => $valor4,
			"valor_5" => $valor5,
			"fecmod" => $fecha,
			"usumod" => self::usuarioActual(),
			"pcmod" => self::pcActual()
		);

		$respuesta = ModeloSublineasMp::mdlEditar($datos);
		if ($respuesta !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo editar la sublínea");
		}

		return array(
			"ok" => true,
			"mensaje" => "Sublínea actualizada",
			"codigo_sublinea" => isset($actual["codigo_sublinea"]) ? $actual["codigo_sublinea"] : ""
		);
	}

	static public function ctrListarMp($post)
	{
		$codigo = strtoupper(self::texto($post, "codigo_sublinea"));
		if ($codigo === "") {
			return array("ok" => false, "mensaje" => "Falta la sublínea");
		}

		$filas = ModeloSublineasMp::mdlListarMpPorSublinea($codigo);
		$items = array();
		foreach ($filas as $row) {
			$items[] = array(
				"codpro" => isset($row["codpro"]) ? trim((string) $row["codpro"]) : "",
				"codfab" => isset($row["codfab"]) ? trim((string) $row["codfab"]) : "",
				"despro" => isset($row["despro"]) ? trim((string) $row["despro"]) : "",
				"color" => isset($row["color"]) ? trim((string) $row["color"]) : "",
				"talla" => isset($row["talla"]) ? trim((string) $row["talla"]) : "",
				"unidad" => isset($row["unidad"]) ? trim((string) $row["unidad"]) : "",
				"stock" => isset($row["stock"]) ? (float) $row["stock"] : 0,
				"n_oc" => isset($row["n_oc"]) ? (int) $row["n_oc"] : 0,
				"n_os" => isset($row["n_os"]) ? (int) $row["n_os"] : 0
			);
		}

		return array(
			"ok" => true,
			"codigo_sublinea" => $codigo,
			"total" => count($items),
			"items" => $items
		);
	}

	static private function campoFila($row, $claves)
	{
		foreach ($claves as $k) {
			if (isset($row[$k]) && trim((string) $row[$k]) !== "") {
				return trim((string) $row[$k]);
			}
		}
		return "";
	}

	static private function mapCatalogo($filas)
	{
		$out = array();
		foreach ($filas as $row) {
			$codigo = self::campoFila($row, array("Cod_Argumento", "cod_argumento"));
			if ($codigo === "") {
				continue;
			}
			$out[] = array(
				"codigo" => $codigo,
				"nombre" => self::campoFila($row, array("Des_Larga", "des_larga")),
				"corta" => self::campoFila($row, array("Des_Corta", "des_corta"))
			);
		}
		return $out;
	}

	static public function ctrCatalogos()
	{
		return array(
			"ok" => true,
			"colores" => self::mapCatalogo(ModeloMateriaPrima::mdlMostrarColores()),
			"tallas" => self::mapCatalogo(ModeloMateriaPrima::mdlMostrarTallas()),
			"unidades" => self::mapCatalogo(ModeloMateriaPrima::mdlMostrarUndMedida())
		);
	}

	static private function catalogoTiene($lista, $codigo)
	{
		$needle = strtoupper(trim((string) $codigo));
		foreach ($lista as $item) {
			if (strtoupper($item["codigo"]) === $needle) {
				return $item;
			}
		}
		return false;
	}

	static public function ctrValidarCodFab($post)
	{
		$codFab = strtoupper(self::texto($post, "codfab"));
		if ($codFab === "") {
			return array("ok" => false, "mensaje" => "Falta el código de fábrica");
		}
		$existe = ModeloMateriaPrima::mdlMostrarMateriaFabrica($codFab);
		return array(
			"ok" => true,
			"existe" => $existe ? true : false
		);
	}

	static public function ctrCrearMp($post)
	{
		self::asegurarTimezone();

		$linea = strtoupper(self::texto($post, "linea"));
		$subcodigo = self::texto($post, "subcodigo");
		$color = self::texto($post, "color");
		$talla = self::texto($post, "talla");
		$unidad = self::texto($post, "unidad");
		$nombre = self::texto($post, "nombre");
		$codAlt = strtoupper(self::texto($post, "codalt"));
		$peso = self::texto($post, "peso");
		$adval = self::texto($post, "adval");
		$seguro = self::texto($post, "seguro");
		$stkMin = self::texto($post, "stk_min");
		$stkMax = self::texto($post, "stk_max");

		if ($linea === "" || $subcodigo === "") {
			return array("ok" => false, "mensaje" => "Elige una sublínea");
		}
		if ($color === "" || $talla === "" || $unidad === "") {
			return array("ok" => false, "mensaje" => "Completa color, talla y unidad");
		}
		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "Ingresa la descripción");
		}

		$tlin = ModeloSublineasMp::mdlLineaExiste($linea);
		if (!$tlin) {
			return array("ok" => false, "mensaje" => "La línea no existe");
		}

		$sublinea = ModeloSublineasMp::mdlMostrarPorCodigo($linea, $subcodigo);
		if (!$sublinea) {
			return array("ok" => false, "mensaje" => "La sublínea no existe");
		}

		$colores = self::mapCatalogo(ModeloMateriaPrima::mdlMostrarColores());
		$tallas = self::mapCatalogo(ModeloMateriaPrima::mdlMostrarTallas());
		$unidades = self::mapCatalogo(ModeloMateriaPrima::mdlMostrarUndMedida());
		if (!self::catalogoTiene($colores, $color)) {
			return array("ok" => false, "mensaje" => "El color no es válido");
		}
		if (!self::catalogoTiene($tallas, $talla)) {
			return array("ok" => false, "mensaje" => "La talla no es válida");
		}
		if (!self::catalogoTiene($unidades, $unidad)) {
			return array("ok" => false, "mensaje" => "La unidad no es válida");
		}

		$codFab = $linea . $subcodigo . $color . $talla;
		if (ModeloMateriaPrima::mdlMostrarMateriaFabrica($codFab)) {
			return array("ok" => false, "mensaje" => "Ya existe una materia prima con el código " . $codFab);
		}

		$codigoPro = ModeloMateriaPrima::mdlSiguienteCodProLibre();
		if ($codigoPro === false || $codigoPro === null || $codigoPro === "") {
			return array("ok" => false, "mensaje" => "No hay código interno disponible");
		}

		$nombre = str_replace(array("'", '"', ",", "."), "", $nombre);
		if ($peso === "") {
			$peso = "0";
		}
		if ($adval === "") {
			$adval = "0";
		}
		if ($seguro === "") {
			$seguro = "0";
		}
		if ($stkMin === "") {
			$stkMin = "0";
		}
		if ($stkMax === "") {
			$stkMax = "0";
		}

		$fecha = date("Y-m-d H:i:s");
		$datos = array(
			"CodAlt" => $codAlt,
			"Cod_Local" => "01",
			"Cod_Entidad" => "01",
			"CodPro" => $codigoPro,
			"CodFab" => $codFab,
			"DesPro" => $nombre,
			"ColPro" => $color,
			"UndPro" => $unidad,
			"Mo" => "",
			"PaiPro" => "",
			"PrePro" => "",
			"PreFob" => "",
			"CosPro" => "",
			"Por_AdVal" => $adval,
			"Por_Seg" => $seguro,
			"PesPro" => $peso,
			"Stk_Act" => "0",
			"Stk_Min" => $stkMin,
			"Stk_Max" => $stkMax,
			"EstPro" => "1",
			"TalPro" => $talla,
			"FamPro" => $linea . $subcodigo,
			"Proveedor" => "",
			"CodAlm01" => "0",
			"FecReg" => $fecha,
			"PcReg" => self::pcActual(),
			"UsuReg" => self::usuarioActual()
		);

		$alta = ModeloMateriaPrima::mdlIngresarMateriaPrima("producto", $datos);
		if ($alta !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo crear la materia prima");
		}

		$precio = array(
			"Cod_Local" => "01",
			"Cod_Entidad" => "01",
			"CodPro" => $codigoPro,
			"CodProv1" => "",
			"PreProv1" => "0",
			"MonProv1" => "",
			"ObsProv1" => "",
			"CodProv2" => "",
			"PreProv2" => "0",
			"MonProv2" => "",
			"ObsProv2" => "",
			"CodProv3" => "",
			"PreProv3" => "0",
			"MonProv3" => "",
			"ObsProv3" => "",
			"FecReg" => $fecha,
			"PcReg" => self::pcActual(),
			"UsuReg" => self::usuarioActual()
		);

		$origenCod = self::texto($post, "origen_codpro");
		if ($origenCod !== "") {
			$origen = ModeloMateriaPrima::mdlMostrarMateriaPrima($origenCod);
			if ($origen && is_array($origen)) {
				$precio["CodProv1"] = self::campoFila($origen, array("CodProv1", "codprov1"));
				$precio["PreProv1"] = self::campoFila($origen, array("PreProv1", "preprov1"));
				$precio["MonProv1"] = self::campoFila($origen, array("MonProv1", "monprov1"));
				$precio["ObsProv1"] = self::campoFila($origen, array("ObsProv1", "obsprov1"));
				$precio["CodProv2"] = self::campoFila($origen, array("CodProv2", "codprov2"));
				$precio["PreProv2"] = self::campoFila($origen, array("PreProv2", "preprov2"));
				$precio["MonProv2"] = self::campoFila($origen, array("MonProv2", "monprov2"));
				$precio["ObsProv2"] = self::campoFila($origen, array("ObsProv2", "obsprov2"));
				$precio["CodProv3"] = self::campoFila($origen, array("CodProv3", "codprov3"));
				$precio["PreProv3"] = self::campoFila($origen, array("PreProv3", "preprov3"));
				$precio["MonProv3"] = self::campoFila($origen, array("MonProv3", "monprov3"));
				$precio["ObsProv3"] = self::campoFila($origen, array("ObsProv3", "obsprov3"));
			}
		}

		ModeloMateriaPrima::mdlIngresarPrecioMP("preciomp", $precio);

		return array(
			"ok" => true,
			"mensaje" => $origenCod !== "" ? "Materia prima duplicada" : "Materia prima creada",
			"codpro" => $codigoPro,
			"codfab" => $codFab,
			"codigo_sublinea" => $linea . $subcodigo
		);
	}

	static public function ctrOrdenes($post)
	{
		$codpro = self::texto($post, "codpro");
		if ($codpro === "") {
			return array("ok" => false, "mensaje" => "Falta la materia prima");
		}

		$mpRow = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
		$ocRaw = ModeloMateriaPrima::mdlOrdenesCompraPorMp($codpro);
		$osRaw = ModeloMateriaPrima::mdlOrdenesServicioPorMp($codpro);
		$oc = array();
		$os = array();
		$mp = array(
			"codpro" => $codpro,
			"codfab" => "",
			"despro" => "",
			"color" => "",
			"stock" => ""
		);
		if ($mpRow && is_array($mpRow)) {
			$mp["codpro"] = self::campoFila($mpRow, array("codpro", "CodPro"));
			$mp["codfab"] = self::campoFila($mpRow, array("codfab", "CodFab"));
			$mp["despro"] = self::campoFila($mpRow, array("despro", "DesPro", "descripcion"));
			$mp["color"] = self::campoFila($mpRow, array("color"));
			$mp["stock"] = self::campoFila($mpRow, array("stock", "CodAlm01", "codalm01"));
		}

		foreach ($ocRaw as $row) {
			$oc[] = array(
				"nro" => self::campoFila($row, array("nro", "Nro")),
				"fecemi" => self::campoFila($row, array("fecemi", "FecEmi")),
				"fecllegada" => self::campoFila($row, array("fecllegada", "Fecllegada")),
				"proveedor" => self::campoFila($row, array("proveedor", "RazPro")),
				"cantidad" => self::campoFila($row, array("cantidad", "canpro")),
				"saldo" => self::campoFila($row, array("saldo", "cantni")),
				"estado" => self::campoFila($row, array("estac")),
				"precio" => self::campoFila($row, array("precio", "PrePro"))
			);
		}

		foreach ($osRaw as $row) {
			$os[] = array(
				"nro" => self::campoFila($row, array("nro", "Nro")),
				"fecemi" => self::campoFila($row, array("fecemi")),
				"fecent" => self::campoFila($row, array("fecent")),
				"rol" => self::campoFila($row, array("rol")),
				"codpro_origen" => self::campoFila($row, array("codpro_origen")),
				"des_origen" => self::campoFila($row, array("des_origen")),
				"codpro_destino" => self::campoFila($row, array("codpro_destino")),
				"des_destino" => self::campoFila($row, array("des_destino")),
				"cantidad" => self::campoFila($row, array("cantidad")),
				"saldo" => self::campoFila($row, array("saldo")),
				"estado" => self::campoFila($row, array("estos", "EstOS"))
			);
		}

		return array("ok" => true, "mp" => $mp, "oc" => $oc, "os" => $os);
	}

	static public function ctrDetalleMp($post)
	{
		$codpro = self::texto($post, "codpro");
		if ($codpro === "") {
			return array("ok" => false, "mensaje" => "Falta la materia prima");
		}
		$row = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
		if (!$row || !is_array($row)) {
			return array("ok" => false, "mensaje" => "No se encontró la materia prima");
		}

		return array(
			"ok" => true,
			"mp" => array(
				"codpro" => self::campoFila($row, array("codpro", "CodPro")),
				"codfab" => self::campoFila($row, array("codfab", "CodFab")),
				"codalt" => self::campoFila($row, array("CodAlt", "codalt")),
				"despro" => self::campoFila($row, array("despro", "DesPro")),
				"color" => self::campoFila($row, array("ColPro", "colpro")),
				"talla" => self::campoFila($row, array("TalPro", "talpro")),
				"unidad" => self::campoFila($row, array("UndPro", "undpro")),
				"peso" => self::campoFila($row, array("PesPro", "pespro")),
				"adval" => self::campoFila($row, array("Por_Adval", "Por_AdVal", "por_adval")),
				"seguro" => self::campoFila($row, array("Por_Seg", "por_seg")),
				"stk_min" => self::campoFila($row, array("Stk_Min", "stk_min")),
				"stk_max" => self::campoFila($row, array("Stk_Max", "stk_max"))
			)
		);
	}
}
