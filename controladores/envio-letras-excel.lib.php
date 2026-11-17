<?php

/**
 * Planilla de Cobranza Libre de Letras (formato BCP).
 * - excel: SpreadsheetML (.xls) con dirección (formato nuevo del banco)
 * - txt: ancho fijo histórico (formato vigente hasta confirmación del banco)
 */
class EnvioLetrasExcelLib
{
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
		return self::normalizarFormato($formato) === 'excel' ? 'xls' : 'txt';
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
	 * @param string $rutaDestino Ruta del .xls a crear
	 * @param array  $filas       nombre, ape_paterno, ape_materno, tipo_doc, num_doc,
	 *                            num_letra, fecha_ven, importe, direccion, departamento,
	 *                            provincia, distrito
	 * @return bool
	 */
	public static function generar($rutaDestino, array $filas)
	{
		$dir = dirname($rutaDestino);
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
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

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
		$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
		$xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
		$xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
		$xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
		$xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
		$xml .= '<Styles>' . "\n";
		$xml .= '<Style ss:ID="header"><Font ss:Bold="1"/></Style>' . "\n";
		$xml .= '<Style ss:ID="number"><NumberFormat ss:Format="0.00"/></Style>' . "\n";
		$xml .= '</Styles>' . "\n";

		$xml .= '<Worksheet ss:Name="Hoja1"><Table>' . "\n";
		$xml .= '<Row>';
		foreach ($headers as $h) {
			$xml .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . self::xmlEscape($h) . '</Data></Cell>';
		}
		$xml .= '</Row>' . "\n";

		foreach ($filas as $fila) {
			$xml .= '<Row>';
			$xml .= self::celdaTexto(self::texto($fila, 'nombre'));
			$xml .= self::celdaTexto(self::texto($fila, 'ape_paterno'));
			$xml .= self::celdaTexto(self::texto($fila, 'ape_materno'));
			$xml .= self::celdaNumero(isset($fila['tipo_doc']) ? (int) $fila['tipo_doc'] : 0, false);
			$xml .= self::celdaTexto(self::texto($fila, 'num_doc'));
			$xml .= self::celdaTexto(self::texto($fila, 'num_letra'));
			$xml .= self::celdaTexto(self::texto($fila, 'fecha_ven'));
			$importe = isset($fila['importe']) ? $fila['importe'] : 0;
			if (!is_numeric($importe)) {
				$importe = 0;
			}
			$xml .= self::celdaNumero(round((float) $importe, 2), true);
			$xml .= self::celdaTexto(self::texto($fila, 'direccion'));
			$xml .= self::celdaTexto(self::texto($fila, 'departamento'));
			$xml .= self::celdaTexto(self::texto($fila, 'provincia'));
			$xml .= self::celdaTexto(self::texto($fila, 'distrito'));
			$xml .= '</Row>' . "\n";
		}

		$xml .= '</Table></Worksheet>' . "\n";
		$xml .= '</Workbook>';

		return @file_put_contents($rutaDestino, $xml) !== false;
	}

	private static function celdaTexto($valor)
	{
		if ($valor === '') {
			return '<Cell><Data ss:Type="String"></Data></Cell>';
		}
		return '<Cell><Data ss:Type="String">' . self::xmlEscape($valor) . '</Data></Cell>';
	}

	private static function celdaNumero($valor, $conEstilo)
	{
		$style = $conEstilo ? ' ss:StyleID="number"' : '';
		return '<Cell' . $style . '><Data ss:Type="Number">' . $valor . '</Data></Cell>';
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
