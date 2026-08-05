<?php

class ControladorModeloColorTaller
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

	static public function ctrListar($filtros = array())
	{
		return ModeloModeloColorTaller::mdlListar($filtros);
	}

	static public function ctrMostrar($id)
	{
		return ModeloModeloColorTaller::mdlMostrar($id);
	}

	static public function ctrListarColoresCatalogo()
	{
		return ModeloModeloColorTaller::mdlListarColoresCatalogo();
	}

	static public function ctrResumenArticulosPorTaller()
	{
		$lista = ModeloModeloColorTaller::mdlResumenArticulosPorTaller();
		$total = 0;
		$data = array();
		if (is_array($lista)) {
			foreach ($lista as $row) {
				$cant = isset($row["total_articulos"]) ? (int) $row["total_articulos"] : 0;
				$total += $cant;
				$data[] = array(
					"cod_sector" => isset($row["cod_sector"]) ? (string) $row["cod_sector"] : "",
					"nom_sector" => isset($row["nom_sector"]) ? (string) $row["nom_sector"] : "",
					"total_articulos" => $cant
				);
			}
		}
		return array("ok" => true, "data" => $data, "total" => $total);
	}

	/**
	 * Colores del modelo (articulojf), uno por código.
	 * Si $conAsignacion, incluye si ya tiene taller configurado.
	 */
	static public function ctrColoresModeloDetalle($modelo, $conAsignacion = false)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return array("ok" => true, "data" => array());
		}

		$lista = ControladorModelos::ctrMostrarColorModelo($modelo);
		$data = array();
		$vistos = array();
		if (is_array($lista)) {
			foreach ($lista as $row) {
				$cod = isset($row["cod_color"]) ? trim((string) $row["cod_color"]) : "";
				if ($cod === "" || isset($vistos[$cod])) {
					continue;
				}
				$vistos[$cod] = true;
				$item = array(
					"cod_color" => $cod,
					"nom_color" => isset($row["color"]) ? (string) $row["color"] : $cod,
					"asignado" => false,
					"id_asignacion" => 0,
					"cod_sector" => "",
					"nom_sector" => ""
				);
				if ($conAsignacion) {
					$id = ModeloModeloColorTaller::mdlIdPorModeloColor($modelo, $cod);
					if ($id > 0) {
						$asig = ModeloModeloColorTaller::mdlMostrar($id);
						$item["asignado"] = true;
						$item["id_asignacion"] = $id;
						$item["cod_sector"] = $asig && isset($asig["cod_sector"]) ? (string) $asig["cod_sector"] : "";
						$item["nom_sector"] = $asig && isset($asig["nom_sector"]) ? (string) $asig["nom_sector"] : "";
					}
				}
				$data[] = $item;
			}
		}

		usort($data, function ($a, $b) {
			return strcmp($a["cod_color"], $b["cod_color"]);
		});

		$reglaGeneral = null;
		if ($conAsignacion) {
			$idG = ModeloModeloColorTaller::mdlIdPorModeloColor($modelo, "");
			if ($idG > 0) {
				$asigG = ModeloModeloColorTaller::mdlMostrar($idG);
				$reglaGeneral = array(
					"asignado" => true,
					"id_asignacion" => $idG,
					"cod_sector" => $asigG && isset($asigG["cod_sector"]) ? (string) $asigG["cod_sector"] : "",
					"nom_sector" => $asigG && isset($asigG["nom_sector"]) ? (string) $asigG["nom_sector"] : ""
				);
			} else {
				$reglaGeneral = array(
					"asignado" => false,
					"id_asignacion" => 0,
					"cod_sector" => "",
					"nom_sector" => ""
				);
			}
		}

		return array("ok" => true, "data" => $data, "regla_general" => $reglaGeneral);
	}

	static public function ctrCrearMasivoAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$modelo = strtoupper(trim(isset($post["modelo"]) ? $post["modelo"] : ""));
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");
		$filasJson = isset($post["filas"]) ? $post["filas"] : "[]";
		$filas = json_decode($filasJson, true);
		if ($modelo === "") {
			return array("ok" => false, "mensaje" => "El modelo es obligatorio");
		}
		if (!ModeloModeloColorTaller::mdlExisteModelo($modelo)) {
			return array("ok" => false, "mensaje" => "El modelo no existe");
		}
		if (!is_array($filas) || empty($filas)) {
			return array("ok" => false, "mensaje" => "Indica al menos un color con taller");
		}

		$creados = 0;
		$actualizados = 0;
		$errores = array();
		$usuario = self::ctrUsuarioSesion();

		foreach ($filas as $idx => $fila) {
			$codColorRaw = isset($fila["cod_color"]) ? $fila["cod_color"] : "";
			$codSector = isset($fila["cod_sector"]) ? trim((string) $fila["cod_sector"]) : "";
			if ($codSector === "") {
				continue;
			}

			$norm = self::ctrNormalizarDatos(array(
				"modelo" => $modelo,
				"cod_color" => $codColorRaw,
				"cod_sector" => $codSector,
				"estado" => 1,
				"observacion" => $observacion
			), false);

			// Si ya existe, actualizar en vez de fallar por duplicado
			if (!$norm["ok"] && strpos($norm["mensaje"], "Duplicado:") === 0) {
				$resColor = self::ctrResolverCodColorModelo($modelo, $codColorRaw);
				if (!$resColor["ok"]) {
					$errores[] = "Fila " . ($idx + 1) . ": " . $resColor["mensaje"];
					continue;
				}
				$id = ModeloModeloColorTaller::mdlIdPorModeloColor($modelo, $resColor["cod_color"]);
				$normEdit = self::ctrNormalizarDatos(array(
					"id" => $id,
					"modelo" => $modelo,
					"cod_color" => $resColor["cod_color"],
					"cod_sector" => $codSector,
					"estado" => 1,
					"observacion" => $observacion
				), true);
				if (!$normEdit["ok"]) {
					$errores[] = "Fila " . ($idx + 1) . ": " . $normEdit["mensaje"];
					continue;
				}
				$datos = $normEdit["datos"];
				$datos["usumod"] = $usuario;
				if (ModeloModeloColorTaller::mdlEditar($datos) === "ok") {
					$actualizados++;
				} else {
					$errores[] = "Fila " . ($idx + 1) . ": no se pudo actualizar";
				}
				continue;
			}

			if (!$norm["ok"]) {
				$errores[] = "Fila " . ($idx + 1) . ": " . $norm["mensaje"];
				continue;
			}

			$datos = $norm["datos"];
			$datos["usureg"] = $usuario;
			if (ModeloModeloColorTaller::mdlCrear($datos) === "ok") {
				$creados++;
			} else {
				$errores[] = "Fila " . ($idx + 1) . ": no se pudo crear";
			}
		}

		if ($creados < 1 && $actualizados < 1) {
			return array(
				"ok" => false,
				"mensaje" => !empty($errores)
					? implode("; ", array_slice($errores, 0, 3))
					: "No hay filas con taller para guardar"
			);
		}

		$msg = "Guardado: {$creados} nuevas";
		if ($actualizados > 0) {
			$msg .= ", {$actualizados} actualizadas";
		}
		if (!empty($errores)) {
			$msg .= ". Avisos: " . implode("; ", array_slice($errores, 0, 3));
		}
		return array("ok" => true, "mensaje" => $msg, "creados" => $creados, "actualizados" => $actualizados);
	}

	static private function ctrNormalizarDatos($post, $requiereId = false)
	{
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$modelo = strtoupper(trim(isset($post["modelo"]) ? $post["modelo"] : ""));
		$codColor = trim(isset($post["cod_color"]) ? $post["cod_color"] : "");
		$codSector = trim(isset($post["cod_sector"]) ? $post["cod_sector"] : "");
		$estado = isset($post["estado"]) ? (int) $post["estado"] : 1;
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");

		if ($requiereId && $id < 1) {
			return array("ok" => false, "mensaje" => "Registro no válido");
		}
		if ($modelo === "") {
			return array("ok" => false, "mensaje" => "El modelo es obligatorio");
		}
		if ($codSector === "") {
			return array("ok" => false, "mensaje" => "El taller (sector) es obligatorio");
		}
		if (!ModeloModeloColorTaller::mdlExisteModelo($modelo)) {
			return array("ok" => false, "mensaje" => "El modelo no existe en el catálogo");
		}
		if (!ModeloModeloColorTaller::mdlExisteSector($codSector)) {
			return array("ok" => false, "mensaje" => "El sector/taller no existe");
		}

		$resColor = self::ctrResolverCodColorModelo($modelo, $codColor);
		if (!$resColor["ok"]) {
			return array("ok" => false, "mensaje" => $resColor["mensaje"]);
		}
		$codColor = $resColor["cod_color"];
		$nomColor = $resColor["nom_color"];

		if (ModeloModeloColorTaller::mdlExisteModeloColor($modelo, $codColor, $id)) {
			$detalle = $codColor === ""
				? "ya existe una regla general (sin color) para ese modelo"
				: "ya existe una regla para ese modelo y color";
			return array("ok" => false, "mensaje" => "Duplicado: " . $detalle);
		}

		return array(
			"ok" => true,
			"datos" => array(
				"id" => $id,
				"modelo" => $modelo,
				"cod_color" => $codColor,
				"nom_color" => $nomColor,
				"cod_sector" => $codSector,
				"estado" => $estado === 0 ? 0 : 1,
				"observacion" => $observacion !== "" ? $observacion : null
			)
		);
	}

	static public function ctrCrearAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$norm = self::ctrNormalizarDatos($post, false);
		if (!$norm["ok"]) {
			return $norm;
		}

		$datos = $norm["datos"];
		$datos["usureg"] = self::ctrUsuarioSesion();

		if (ModeloModeloColorTaller::mdlCrear($datos) === "ok") {
			return array("ok" => true, "mensaje" => "Asignación creada");
		}
		return array("ok" => false, "mensaje" => "No se pudo crear");
	}

	static public function ctrEditarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$norm = self::ctrNormalizarDatos($post, true);
		if (!$norm["ok"]) {
			return $norm;
		}

		if (!ModeloModeloColorTaller::mdlMostrar($norm["datos"]["id"])) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}

		$datos = $norm["datos"];
		$datos["usumod"] = self::ctrUsuarioSesion();

		if (ModeloModeloColorTaller::mdlEditar($datos) === "ok") {
			return array("ok" => true, "mensaje" => "Asignación actualizada");
		}
		return array("ok" => false, "mensaje" => "No se pudo actualizar");
	}

	static public function ctrEliminarAjax($post)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Registro no válido");
		}
		if (!ModeloModeloColorTaller::mdlMostrar($id)) {
			return array("ok" => false, "mensaje" => "Registro no encontrado");
		}
		if (ModeloModeloColorTaller::mdlEliminar($id) === "ok") {
			return array("ok" => true, "mensaje" => "Asignación eliminada");
		}
		return array("ok" => false, "mensaje" => "No se pudo eliminar");
	}

	static private function ctrDelimitadorCsv($linea)
	{
		$opciones = array(
			"," => substr_count($linea, ","),
			";" => substr_count($linea, ";"),
			"\t" => substr_count($linea, "\t")
		);
		arsort($opciones);
		$delimitador = key($opciones);
		return current($opciones) > 0 ? $delimitador : ",";
	}

	static private function ctrNormalizarEstadoImport($valor)
	{
		$v = strtolower(trim((string) $valor));
		if ($v === "" || $v === "1" || $v === "activo" || $v === "si" || $v === "sí" || $v === "true") {
			return 1;
		}
		if ($v === "0" || $v === "inactivo" || $v === "no" || $v === "false") {
			return 0;
		}
		return null;
	}

	static private function ctrCeldaTexto($valor)
	{
		if ($valor === null) {
			return "";
		}
		if (is_float($valor) || is_int($valor)) {
			// Evita "1.0" / notación científica en códigos
			if (is_float($valor) && floor($valor) == $valor) {
				return (string) (int) $valor;
			}
			return trim((string) $valor);
		}
		return trim((string) $valor);
	}

	/**
	 * Limpia artefactos típicos de Excel en códigos (apóstrofe, ="01", espacios).
	 */
	static private function ctrLimpiarCodigoExcel($valor)
	{
		$cod = self::ctrCeldaTexto($valor);
		if ($cod === "") {
			return "";
		}
		// Apóstrofe de "texto" en Excel: '01
		if (isset($cod[0]) && $cod[0] === "'") {
			$cod = substr($cod, 1);
		}
		// Fórmula CSV para forzar texto: ="01" o =01
		if (preg_match('/^=\s*"([^"]*)"\s*$/', $cod, $m)) {
			$cod = $m[1];
		} elseif (preg_match('/^=\s*(.+)\s*$/', $cod, $m)) {
			$cod = trim($m[1], " \t\"'");
		}
		return trim($cod);
	}

	/**
	 * Resuelve cod_color canónico del modelo.
	 * Tolera que Excel haya quitado ceros a la izquierda (1 → 01) si el match es único.
	 *
	 * @return array{ok:bool,cod_color?:string,nom_color?:string|null,mensaje?:string,normalizado?:bool}
	 */
	static private function ctrResolverCodColorModelo($modelo, $codColorRaw)
	{
		$codColor = self::ctrLimpiarCodigoExcel($codColorRaw);
		if ($codColor === "") {
			return array(
				"ok" => true,
				"cod_color" => "",
				"nom_color" => null,
				"normalizado" => false
			);
		}

		$coloresModelo = ControladorModelos::ctrMostrarColorModelo($modelo);
		$porCodigo = array();
		if (is_array($coloresModelo)) {
			foreach ($coloresModelo as $c) {
				$cod = isset($c["cod_color"]) ? trim((string) $c["cod_color"]) : "";
				if ($cod === "" || isset($porCodigo[$cod])) {
					continue;
				}
				$porCodigo[$cod] = isset($c["color"]) && trim((string) $c["color"]) !== ""
					? trim((string) $c["color"])
					: $cod;
			}
		}

		if (isset($porCodigo[$codColor])) {
			return array(
				"ok" => true,
				"cod_color" => $codColor,
				"nom_color" => $porCodigo[$codColor],
				"normalizado" => false
			);
		}

		// Pad a 2 dígitos (convención articulojf / colorjf)
		if (ctype_digit($codColor) && strlen($codColor) === 1) {
			$padded = str_pad($codColor, 2, "0", STR_PAD_LEFT);
			if (isset($porCodigo[$padded])) {
				return array(
					"ok" => true,
					"cod_color" => $padded,
					"nom_color" => $porCodigo[$padded],
					"normalizado" => true
				);
			}
		}

		// Match numérico único dentro del modelo (1 ≡ 01 ≡ 001 si solo uno aplica)
		if (ctype_digit($codColor)) {
			$matches = array();
			$num = (int) $codColor;
			foreach ($porCodigo as $cod => $nom) {
				if (ctype_digit($cod) && (int) $cod === $num) {
					$matches[] = $cod;
				}
			}
			if (count($matches) === 1) {
				$canon = $matches[0];
				return array(
					"ok" => true,
					"cod_color" => $canon,
					"nom_color" => $porCodigo[$canon],
					"normalizado" => $canon !== $codColor
				);
			}
			if (count($matches) > 1) {
				return array(
					"ok" => false,
					"mensaje" => "Color ambiguo ({$codColor}): coincide con "
						. implode(", ", $matches) . ". Escribe el código exacto (ej. 01)"
				);
			}
		}

		// Fallback catálogo general + pad
		$candidatos = array($codColor);
		if (ctype_digit($codColor) && strlen($codColor) === 1) {
			$candidatos[] = str_pad($codColor, 2, "0", STR_PAD_LEFT);
		}
		foreach ($candidatos as $cand) {
			$nom = ModeloModeloColorTaller::mdlNombreColor($cand);
			if ($nom !== null) {
				return array(
					"ok" => true,
					"cod_color" => $cand,
					"nom_color" => $nom,
					"normalizado" => $cand !== $codColor
				);
			}
		}

		return array(
			"ok" => false,
			"mensaje" => "Color \"{$codColor}\" no pertenece al modelo (usa 01, 02, etc.)"
		);
	}

	static private function ctrLeerFilasImportacion($archivoTmp, $extension)
	{
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
			$delimitador = self::ctrDelimitadorCsv($primeraLinea);
			rewind($manejador);
			$encabezados = fgetcsv($manejador, 0, $delimitador);
			if (!is_array($encabezados)) {
				fclose($manejador);
				return array("ok" => false, "mensaje" => "No se pudo leer el encabezado");
			}
			foreach ($encabezados as $i => $enc) {
				$enc = preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc);
				$encabezados[$i] = strtolower(trim($enc));
			}
			$pos = array_flip($encabezados);
			if (!isset($pos["modelo"]) || !isset($pos["cod_sector"])) {
				fclose($manejador);
				return array("ok" => false, "mensaje" => "El archivo debe incluir columnas: modelo, cod_sector (opcionales: cod_color, observacion, estado)");
			}
			$n = 1;
			while (($valores = fgetcsv($manejador, 0, $delimitador)) !== false) {
				$n++;
				if ($n > 5001) {
					fclose($manejador);
					return array("ok" => false, "mensaje" => "El archivo supera el máximo de 5,000 filas");
				}
				if (count($valores) === 1 && trim((string) $valores[0]) === "") {
					continue;
				}
				$filas[] = array(
					"fila" => $n,
					"modelo" => self::ctrCeldaTexto(isset($valores[$pos["modelo"]]) ? $valores[$pos["modelo"]] : ""),
					"cod_color" => self::ctrCeldaTexto(isset($pos["cod_color"], $valores[$pos["cod_color"]]) ? $valores[$pos["cod_color"]] : ""),
					"cod_sector" => self::ctrCeldaTexto(isset($valores[$pos["cod_sector"]]) ? $valores[$pos["cod_sector"]] : ""),
					"observacion" => self::ctrCeldaTexto(isset($pos["observacion"], $valores[$pos["observacion"]]) ? $valores[$pos["observacion"]] : ""),
					"estado" => self::ctrCeldaTexto(isset($pos["estado"], $valores[$pos["estado"]]) ? $valores[$pos["estado"]] : "1")
				);
			}
			fclose($manejador);
			return array("ok" => true, "filas" => $filas);
		}

		// xls / xlsx con PHPExcel
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
				$encabezados[$c] = strtolower(trim((string) $val));
			}
			$pos = array_flip($encabezados);
			if (!isset($pos["modelo"]) || !isset($pos["cod_sector"])) {
				return array("ok" => false, "mensaje" => "El Excel debe incluir columnas: modelo, cod_sector (opcionales: cod_color, observacion, estado)");
			}
			if ($highestRow > 5001) {
				return array("ok" => false, "mensaje" => "El archivo supera el máximo de 5,000 filas");
			}
			for ($r = 2; $r <= $highestRow; $r++) {
				$modelo = isset($pos["modelo"])
					? self::ctrCeldaTexto($sheet->getCellByColumnAndRow((int) $pos["modelo"], $r)->getFormattedValue())
					: "";
				$codSector = isset($pos["cod_sector"])
					? self::ctrCeldaTexto($sheet->getCellByColumnAndRow((int) $pos["cod_sector"], $r)->getFormattedValue())
					: "";
				$codColor = isset($pos["cod_color"])
					? self::ctrCeldaTexto($sheet->getCellByColumnAndRow((int) $pos["cod_color"], $r)->getFormattedValue())
					: "";
				$obs = isset($pos["observacion"])
					? self::ctrCeldaTexto($sheet->getCellByColumnAndRow((int) $pos["observacion"], $r)->getFormattedValue())
					: "";
				$estado = isset($pos["estado"])
					? self::ctrCeldaTexto($sheet->getCellByColumnAndRow((int) $pos["estado"], $r)->getFormattedValue())
					: "";
				if ($modelo === "" && $codSector === "" && $codColor === "" && $obs === "") {
					continue;
				}
				$filas[] = array(
					"fila" => $r,
					"modelo" => $modelo,
					"cod_color" => $codColor,
					"cod_sector" => $codSector,
					"observacion" => $obs,
					"estado" => $estado !== "" ? $estado : "1"
				);
			}
			return array("ok" => true, "filas" => $filas);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo leer el Excel: " . $e->getMessage());
		}
	}

	static public function ctrImportarArchivo($post, $files)
	{
		if (!self::ctrPuedeProduccion()) {
			return array("ok" => false, "mensaje" => "Sin permiso de producción");
		}

		$confirmar = isset($post["confirmar"]) && (string) $post["confirmar"] === "1";
		if (
			!isset($files["archivo"]) ||
			!is_array($files["archivo"]) ||
			(int) $files["archivo"]["error"] !== UPLOAD_ERR_OK
		) {
			return array("ok" => false, "mensaje" => "Selecciona un archivo válido");
		}

		$archivo = $files["archivo"];
		$extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
		if (!in_array($extension, array("csv", "xls", "xlsx"), true)) {
			return array("ok" => false, "mensaje" => "Formato no soportado. Usa CSV, XLS o XLSX");
		}
		if ((int) $archivo["size"] < 1 || (int) $archivo["size"] > 5 * 1024 * 1024) {
			return array("ok" => false, "mensaje" => "El archivo debe pesar como máximo 5 MB");
		}
		if (!is_uploaded_file($archivo["tmp_name"])) {
			return array("ok" => false, "mensaje" => "La carga del archivo no es válida");
		}

		$leido = self::ctrLeerFilasImportacion($archivo["tmp_name"], $extension);
		if (!$leido["ok"]) {
			return $leido;
		}
		$crudas = $leido["filas"];
		if (empty($crudas)) {
			return array("ok" => false, "mensaje" => "El archivo no contiene datos");
		}

		$vistos = array();
		$resultado = array();
		$totalErrores = 0;
		$aCrear = 0;
		$aActualizar = 0;

		foreach ($crudas as $row) {
			$errores = array();
			$modelo = strtoupper(self::ctrLimpiarCodigoExcel($row["modelo"]));
			$codColorRaw = self::ctrLimpiarCodigoExcel($row["cod_color"]);
			$codColor = $codColorRaw;
			$codSector = strtoupper(self::ctrLimpiarCodigoExcel($row["cod_sector"]));
			$observacion = trim($row["observacion"]);
			$estado = self::ctrNormalizarEstadoImport($row["estado"]);
			$accionFila = "";
			$nomColor = null;
			$colorNormalizado = false;

			$modeloOk = false;
			if ($modelo === "") {
				$errores[] = "Modelo vacío";
			} elseif (!ModeloModeloColorTaller::mdlExisteModelo($modelo)) {
				$errores[] = "Modelo inexistente";
			} else {
				$modeloOk = true;
			}
			if ($codSector === "") {
				$errores[] = "Taller vacío";
			} elseif (!ModeloModeloColorTaller::mdlExisteSector($codSector)) {
				$errores[] = "Taller/sector inexistente";
			}
			if ($estado === null) {
				$errores[] = "Estado inválido (use 1/0 o activo/inactivo)";
			}

			if ($modeloOk) {
				$resColor = self::ctrResolverCodColorModelo($modelo, $codColorRaw);
				if (!$resColor["ok"]) {
					$errores[] = $resColor["mensaje"];
				} else {
					$codColor = $resColor["cod_color"];
					$nomColor = $resColor["nom_color"];
					$colorNormalizado = !empty($resColor["normalizado"]);
				}
			}

			$clave = $modelo . "|" . $codColor;
			if ($modelo !== "" && isset($vistos[$clave])) {
				$errores[] = "Duplicado en el archivo";
			}
			$vistos[$clave] = true;

			$idExistente = 0;
			if ($modeloOk && empty($errores)) {
				$idExistente = ModeloModeloColorTaller::mdlIdPorModeloColor($modelo, $codColor);
				if ($idExistente > 0) {
					$accionFila = "actualizar";
					$aActualizar++;
				} else {
					$accionFila = "crear";
					$aCrear++;
				}
			}

			if (!empty($errores)) {
				$totalErrores++;
				$accionFila = "";
			}

			$resultado[] = array(
				"fila" => $row["fila"],
				"modelo" => $modelo,
				"cod_color" => $codColor,
				"cod_color_original" => $codColorRaw,
				"color_normalizado" => $colorNormalizado,
				"cod_sector" => $codSector,
				"observacion" => $observacion,
				"estado" => $estado === null ? $row["estado"] : $estado,
				"accion" => $accionFila,
				"id" => $idExistente,
				"nom_color" => $nomColor,
				"errores" => $errores
			);
		}

		if (!$confirmar) {
			return array(
				"ok" => true,
				"previsualizacion" => true,
				"data" => $resultado,
				"total" => count($resultado),
				"validas" => count($resultado) - $totalErrores,
				"rechazadas" => $totalErrores,
				"a_crear" => $aCrear,
				"a_actualizar" => $aActualizar
			);
		}

		if ($totalErrores > 0) {
			return array("ok" => false, "mensaje" => "Corrige las filas rechazadas antes de importar");
		}

		$usuario = self::ctrUsuarioSesion();
		$creados = 0;
		$actualizados = 0;
		foreach ($resultado as $item) {
			$datos = array(
				"modelo" => $item["modelo"],
				"cod_color" => $item["cod_color"],
				"nom_color" => $item["nom_color"],
				"cod_sector" => $item["cod_sector"],
				"estado" => (int) $item["estado"],
				"observacion" => $item["observacion"] !== "" ? $item["observacion"] : null
			);
			if ($item["accion"] === "actualizar") {
				$datos["id"] = (int) $item["id"];
				$datos["usumod"] = $usuario;
				if (ModeloModeloColorTaller::mdlEditar($datos) !== "ok") {
					return array("ok" => false, "mensaje" => "Error al actualizar fila " . $item["fila"]);
				}
				$actualizados++;
			} else {
				$datos["usureg"] = $usuario;
				if (ModeloModeloColorTaller::mdlCrear($datos) !== "ok") {
					return array("ok" => false, "mensaje" => "Error al crear fila " . $item["fila"]);
				}
				$creados++;
			}
		}

		return array(
			"ok" => true,
			"mensaje" => "Importación lista: {$creados} creadas, {$actualizados} actualizadas"
		);
	}
}
