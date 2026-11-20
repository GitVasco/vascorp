<?php

/**
 * Planilla de Cobranza Libre de Letras (formato BCP).
 * - excel: .xlsx OOXML liviano (sin depender de extensión zip / plantilla pesada)
 * - txt: ancho fijo histórico (formato vigente hasta confirmación del banco)
 */
class EnvioLetrasExcelLib
{
	const PLANTILLA_XLSX = 'vistas/letras/plantilla/Formato_Planilla_de_Letras.xlsx';

	/** @var string */
	public static $ultimoError = '';

	/**
	 * @param string $formato txt|excel
	 * @return string
	 */
	public static function normalizarFormato($formato)
	{
		$formato = strtolower(trim((string) $formato));
		if ($formato === 'excel' || $formato === 'xls' || $formato === 'xlsx') {
			return 'excel';
		}
		return 'txt';
	}

	/**
	 * @param string $formato
	 * @return string extensión sin punto
	 */
	public static function extension($formato)
	{
		return self::normalizarFormato($formato) === 'excel' ? 'xlsx' : 'txt';
	}

	/**
	 * Detecta formato a partir de la ruta del archivo guardado.
	 */
	public static function formatoDesdeArchivo($ruta)
	{
		$ext = strtolower(pathinfo((string) $ruta, PATHINFO_EXTENSION));
		if ($ext === 'xls' || $ext === 'xlsx') {
			return 'excel';
		}
		return 'txt';
	}

	/**
	 * Solo RUC 20… (empresa): razón social completa en la 1ª columna; apellidos vacíos.
	 * DNI y RUC 10… (persona): nombres / ape. paterno / ape. materno.
	 *
	 * @return array{nombre:string,ape_paterno:string,ape_materno:string}
	 */
	public static function partesNombreCliente($nombres, $apePaterno, $apeMaterno, $razonSocial = '', $numDoc = '', $tipoDoc = null)
	{
		$docDigitos = preg_replace('/\D+/', '', (string) $numDoc);
		$esEmpresaRuc20 = (strlen($docDigitos) === 11 && strpos($docDigitos, '20') === 0);

		if ($esEmpresaRuc20) {
			$razon = trim((string) $razonSocial);
			if ($razon === '') {
				$razon = trim((string) $nombres);
			}
			if ($razon === '') {
				$partes = array_filter(array(
					trim((string) $nombres),
					trim((string) $apePaterno),
					trim((string) $apeMaterno),
				), 'strlen');
				$razon = implode(' ', $partes);
			}
			return array(
				'nombre' => $razon,
				'ape_paterno' => '',
				'ape_materno' => '',
			);
		}

		return array(
			'nombre' => trim((string) $nombres),
			'ape_paterno' => trim((string) $apePaterno),
			'ape_materno' => trim((string) $apeMaterno),
		);
	}

	/**
	 * Genera el archivo según formato (txt o excel).
	 */
	public static function generarArchivo($rutaDestino, array $filas, $formato = 'txt')
	{
		$formato = self::normalizarFormato($formato);
		if ($formato === 'excel') {
			return self::generar($rutaDestino, $filas);
		}
		return self::generarTxt($rutaDestino, $filas);
	}

	/**
	 * TXT de ancho fijo (formato histórico BCP).
	 */
	public static function generarTxt($rutaDestino, array $filas)
	{
		$dir = dirname($rutaDestino);
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			return false;
		}

		$file = @fopen($rutaDestino, 'w');
		if ($file === false) {
			return false;
		}

		foreach ($filas as $fila) {
			$nombre = self::texto($fila, 'nombre');
			$apePat = self::texto($fila, 'ape_paterno');
			$apeMat = self::texto($fila, 'ape_materno');
			$tipoDoc = isset($fila['tipo_doc']) ? (int) $fila['tipo_doc'] : 1;
			$numDoc = self::texto($fila, 'num_doc');
			$numLetra = self::texto($fila, 'num_letra');
			$fechaTxt = self::formatearFechaTxt(isset($fila['fecha_ven']) ? $fila['fecha_ven'] : '');
			$importe = isset($fila['importe']) ? $fila['importe'] : 0;
			if (!is_numeric($importe)) {
				$importe = 0;
			}
			$monto = number_format((float) $importe, 2, '.', '');

			$clienteDoc = $tipoDoc . $numDoc;
			$salto6 = (strlen($monto) == 7) ? 13 : 14;

			$linea = str_pad($nombre, 72) .
				str_pad($apePat, 24) .
				str_pad($apeMat, 24) .
				str_pad($clienteDoc, 16) .
				str_pad($numLetra, 12) .
				str_pad($fechaTxt, $salto6) .
				$monto .
				PHP_EOL;
			fwrite($file, $linea);
		}

		fclose($file);
		return is_file($rutaDestino);
	}

	/**
	 * Genera .xlsx OOXML (mismas columnas que la plantilla BCP).
	 * No requiere extensión zip del servidor: arma el ZIP en PHP puro.
	 *
	 * @param string $rutaDestino Ruta del .xlsx a crear
	 * @param array  $filas
	 * @return bool
	 */
	public static function generar($rutaDestino, array $filas)
	{
		self::$ultimoError = '';

		$dir = dirname($rutaDestino);
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			self::$ultimoError = 'No se pudo crear la carpeta de letras.';
			return false;
		}

		$headers = array(
			'NOMBRE O RAZÓN SOCIAL',
			'APELLIDO PATERNO',
			'APELLIDO MATERNO',
			'TIPO DE DOCUMENTO DE IDENTIDAD',
			'NÚMERO DE DOCUMENTO DE IDENTIDAD',
			'NÚMERO LETRA O FACTURA',
			'FECHA VENCIMIENTO',
			'IMPORTE',
			'DIRECCION',
			'DEPARTAMENTO',
			'PROVINCIA',
			'DISTRITO'
		);

		$ultimaFila = 1 + count($filas);
		$sheetData = '<sheetData>';
		$sheetData .= '<row r="1">';
		$col = 0;
		foreach ($headers as $h) {
			$sheetData .= self::celdaInlineStr(self::colLetter($col) . '1', $h, null);
			$col++;
		}
		$sheetData .= '</row>';

		$rowNum = 2;
		foreach ($filas as $fila) {
			$importe = isset($fila['importe']) ? $fila['importe'] : 0;
			if (!is_numeric($importe)) {
				$importe = 0;
			}
			$importe = round((float) $importe, 2);
			$tipoDoc = isset($fila['tipo_doc']) ? (int) $fila['tipo_doc'] : 0;

			$sheetData .= '<row r="' . $rowNum . '">';
			$sheetData .= self::celdaInlineStr('A' . $rowNum, self::texto($fila, 'nombre'), null);
			$sheetData .= self::celdaInlineStr('B' . $rowNum, self::texto($fila, 'ape_paterno'), null);
			$sheetData .= self::celdaInlineStr('C' . $rowNum, self::texto($fila, 'ape_materno'), null);
			$sheetData .= self::celdaNumeroXlsx('D' . $rowNum, $tipoDoc, null);
			$sheetData .= self::celdaInlineStr('E' . $rowNum, self::texto($fila, 'num_doc'), null);
			$sheetData .= self::celdaInlineStr('F' . $rowNum, self::texto($fila, 'num_letra'), null);
			$sheetData .= self::celdaInlineStr('G' . $rowNum, self::texto($fila, 'fecha_ven'), null);
			$sheetData .= self::celdaNumeroXlsx('H' . $rowNum, $importe, null);
			$sheetData .= self::celdaInlineStr('I' . $rowNum, self::texto($fila, 'direccion'), null);
			$sheetData .= self::celdaInlineStr('J' . $rowNum, self::texto($fila, 'departamento'), null);
			$sheetData .= self::celdaInlineStr('K' . $rowNum, self::texto($fila, 'provincia'), null);
			$sheetData .= self::celdaInlineStr('L' . $rowNum, self::texto($fila, 'distrito'), null);
			$sheetData .= '</row>';
			$rowNum++;
		}
		$sheetData .= '</sheetData>';

		$sheet1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<dimension ref="A1:L' . max(1, $ultimaFila) . '"/>'
			. $sheetData
			. '</worksheet>';

		$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
			. '</Types>';

		$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';

		$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
			. ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheets><sheet name="Hoja1" sheetId="1" r:id="rId1"/></sheets>'
			. '</workbook>';

		$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
			. '</Relationships>';

		$archivos = array(
			'[Content_Types].xml' => $contentTypes,
			'_rels/.rels' => $rels,
			'xl/workbook.xml' => $workbook,
			'xl/_rels/workbook.xml.rels' => $workbookRels,
			'xl/worksheets/sheet1.xml' => $sheet1
		);

		if (is_file($rutaDestino)) {
			@unlink($rutaDestino);
		}

		if (!self::escribirZipStore($rutaDestino, $archivos)) {
			if (self::$ultimoError === '') {
				self::$ultimoError = 'No se pudo escribir el archivo XLSX.';
			}
			@unlink($rutaDestino);
			return false;
		}

		return is_file($rutaDestino) && filesize($rutaDestino) > 0;
	}

	/**
	 * ZIP método store (sin compresión) en PHP puro — funciona sin extensión zip.
	 */
	private static function escribirZipStore($rutaDestino, array $archivos)
	{
		$fh = @fopen($rutaDestino, 'wb');
		if ($fh === false) {
			self::$ultimoError = 'Sin permiso de escritura en vistas/letras.';
			return false;
		}

		$offset = 0;
		$central = '';
		$count = 0;

		foreach ($archivos as $nombre => $contenido) {
			$nombre = str_replace('\\', '/', $nombre);
			$contenido = (string) $contenido;
			$size = strlen($contenido);
			$crcBin = pack('V', hexdec(hash('crc32b', $contenido)));

			$local = pack('Vvvvvv', 0x04034b50, 20, 0, 0, 0, 0)
				. $crcBin
				. pack('VV', $size, $size)
				. pack('vv', strlen($nombre), 0)
				. $nombre
				. $contenido;

			if (fwrite($fh, $local) === false) {
				fclose($fh);
				self::$ultimoError = 'Error al escribir datos ZIP.';
				return false;
			}

			$central .= pack('Vvvvvvv', 0x02014b50, 20, 20, 0, 0, 0, 0)
				. $crcBin
				. pack('VV', $size, $size)
				. pack('vvvvvVV', strlen($nombre), 0, 0, 0, 0, 0, $offset)
				. $nombre;

			$offset += strlen($local);
			$count++;
		}

		$centralOffset = $offset;
		$centralSize = strlen($central);
		if (fwrite($fh, $central) === false) {
			fclose($fh);
			self::$ultimoError = 'Error al escribir directorio ZIP.';
			return false;
		}

		$end = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);
		if (fwrite($fh, $end) === false) {
			fclose($fh);
			self::$ultimoError = 'Error al cerrar ZIP.';
			return false;
		}

		fclose($fh);
		return true;
	}

	private static function colLetter($index)
	{
		$letters = 'ABCDEFGHIJKL';
		return $letters[$index];
	}

	private static function celdaInlineStr($ref, $valor, $style)
	{
		$sAttr = ($style !== null && $style !== '') ? ' s="' . $style . '"' : '';
		if ($valor === '') {
			return '<c r="' . $ref . '"' . $sAttr . '/>';
		}
		$space = preg_match('/^\s|\s$/', $valor) ? ' xml:space="preserve"' : '';
		return '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t' . $space . '>'
			. self::xmlEscape($valor) . '</t></is></c>';
	}

	private static function celdaNumeroXlsx($ref, $valor, $style)
	{
		$sAttr = ($style !== null && $style !== '') ? ' s="' . $style . '"' : '';
		if (is_float($valor) || (is_string($valor) && strpos((string) $valor, '.') !== false)) {
			$valor = rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
			if ($valor === '') {
				$valor = '0';
			}
		}
		return '<c r="' . $ref . '"' . $sAttr . '><v>' . $valor . '</v></c>';
	}

	private static function texto(array $fila, $clave)
	{
		if (!isset($fila[$clave]) || $fila[$clave] === null) {
			return '';
		}
		return trim((string) $fila[$clave]);
	}

	private static function xmlEscape($valor)
	{
		return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Normaliza fecha a dd/mm/yyyy (acepta DDMMAA o YYYY-MM-DD).
	 */
	public static function formatearFecha($fecha)
	{
		$fecha = trim((string) $fecha);
		if ($fecha === '') {
			return '';
		}
		if (preg_match('/^(\\d{2})(\\d{2})(\\d{2})$/', $fecha, $m)) {
			return $m[1] . '/' . $m[2] . '/20' . $m[3];
		}
		if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})/', $fecha, $m)) {
			return $m[3] . '/' . $m[2] . '/' . $m[1];
		}
		if (preg_match('/^(\\d{2})\\/(\\d{2})\\/(\\d{4})$/', $fecha)) {
			return $fecha;
		}
		return $fecha;
	}

	/**
	 * Fecha DDMMAA para el TXT histórico.
	 */
	public static function formatearFechaTxt($fecha)
	{
		$fecha = trim((string) $fecha);
		if ($fecha === '') {
			return '';
		}
		if (preg_match('/^(\\d{2})(\\d{2})(\\d{2})$/', $fecha)) {
			return $fecha;
		}
		if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})/', $fecha, $m)) {
			return $m[3] . $m[2] . substr($m[1], 2);
		}
		if (preg_match('/^(\\d{2})\\/(\\d{2})\\/(\\d{4})$/', $fecha, $m)) {
			return $m[1] . $m[2] . substr($m[3], 2);
		}
		if (preg_match('/^(\\d{2})\\/(\\d{2})\\/(\\d{2})$/', $fecha, $m)) {
			return $m[1] . $m[2] . $m[3];
		}
		return preg_replace('/\\D+/', '', $fecha);
	}
}
