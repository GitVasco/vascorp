<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.controlador.php";
require_once "../modelos/metas-retos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
	echo json_encode(array("data" => array(), "total_pagar" => 0));
	return;
}

$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : (int) date("Y");
$mes = isset($_POST["mes"]) ? (int) $_POST["mes"] : (int) date("n");
$puedeEditar = ControladorMetasRetos::ctrPuedeEditar();

$filas = ControladorMetasRetos::ctrListarAvance($anio, $mes);
$data = array();
$totalPagarPeriodo = 0.0;

function mrFmt($n, $dec = 0)
{
	if ($n === null || $n === "") {
		return "—";
	}
	return number_format((float) $n, $dec, ".", ",");
}

function mrTieneValor($v)
{
	return !($v === null || $v === "" || (is_numeric($v) && (float) $v <= 0));
}

function mrAporteHtml($aporte)
{
	$aporte = (float) $aporte;
	if ($aporte > 0) {
		return "<div class='mr-aporte'>+ "
			. htmlspecialchars(mrFmt($aporte, 2), ENT_QUOTES, "UTF-8")
			. "</div>";
	}
	return "<div class='mr-aporte mr-aporte--zero'>+ 0.00</div>";
}

/** Celda compacta: meta / real + barra + aporte al estimado. */
function mrCelda($meta, $real, $pct, $aporte = 0, $decMeta = 0, $decReal = 0, $forzarMostrar = false)
{
	$tieneMeta = mrTieneValor($meta);
	$tieneReal = is_numeric($real) && (float) $real > 0;
	$aporte = (float) $aporte;

	if (!$tieneMeta && !$tieneReal && $aporte <= 0 && !$forzarMostrar) {
		return "<span class='mr-empty'>—</span>";
	}

	$metaTxt = $tieneMeta ? mrFmt($meta, $decMeta) : "—";
	$realTxt = mrFmt($real, $decReal);

	$html = "<div class='mr-cell'>"
		. "<div class='mr-line'><span class='mr-meta'>" . htmlspecialchars($metaTxt, ENT_QUOTES, "UTF-8")
		. "</span><span class='mr-sep'>/</span><span class='mr-real'>"
		. htmlspecialchars($realTxt, ENT_QUOTES, "UTF-8") . "</span></div>";

	if ($tieneMeta && $pct !== null) {
		$cls = $pct >= 100 ? "success" : ($pct >= 70 ? "warning" : "danger");
		$ancho = min(100, max(0, (float) $pct));
		$pctTxt = number_format((float) $pct, 0, ".", ",") . "%";
		$html .= "<div class='progress mr-bar'><div class='progress-bar progress-bar-{$cls}' style='width:{$ancho}%;'>"
			. htmlspecialchars($pctTxt, ENT_QUOTES, "UTF-8") . "</div></div>";
	}

	if ($tieneMeta || $tieneReal || $aporte > 0 || $forzarMostrar) {
		$html .= mrAporteHtml($aporte);
	}

	$html .= "</div>";
	return $html;
}

if (is_array($filas)) {
	foreach ($filas as $f) {
		$reto = isset($f["reto"]) && is_array($f["reto"]) ? $f["reto"] : array();
		$metaMonto = isset($reto["meta_monto"]) ? $reto["meta_monto"] : null;
		$metaCli = isset($reto["meta_clientes"]) ? $reto["meta_clientes"] : null;
		$metaMod = isset($reto["meta_modelos"]) ? $reto["meta_modelos"] : null;
		$metaDoc = isset($reto["meta_docenas_especial"]) ? $reto["meta_docenas_especial"] : null;
		$modeloEsp = isset($f["modelo_especial"]) ? trim((string) $f["modelo_especial"]) : "";
		$docenasEsp = isset($f["docenas_especial"]) ? $f["docenas_especial"] : 0;

		$pctMonto = ControladorMetasRetos::ctrPctAvance($f["venta_real"], $metaMonto);
		$pctCli = ControladorMetasRetos::ctrPctAvance($f["clientes_nuevos"], $metaCli);
		$pctMod = ControladorMetasRetos::ctrPctAvance($f["modelos_activos"], $metaMod);
		$pctEsp = ($modeloEsp !== "")
			? ControladorMetasRetos::ctrPctAvance($docenasEsp, $metaDoc)
			: null;

		$comision = ControladorMetasRetos::ctrCalcularComisionEstimada($f);
		$totalPagarPeriodo += (float) $comision["total"];
		$det = $comision["detalle"];
		$aporteMonto = isset($det["monto"]) ? (float) $det["monto"] : 0.0;
		$aporteCli = isset($det["clientes"]) ? (float) $det["clientes"] : 0.0;
		$aporteMod = isset($det["modelos"]) ? (float) $det["modelos"] : 0.0;
		$aporteEsp = isset($det["modelo_especial"]) ? (float) $det["modelo_especial"] : 0.0;

		$titlePagar = "Monto: " . number_format($aporteMonto, 2, ".", ",")
			. " · Cli: " . number_format($aporteCli, 2, ".", ",")
			. " · Mod: " . number_format($aporteMod, 2, ".", ",")
			. " · Esp: " . number_format($aporteEsp, 2, ".", ",");

		$cod = htmlspecialchars($f["cod_vendedor"], ENT_QUOTES, "UTF-8");
		$nom = htmlspecialchars($f["nombre_vendedor"], ENT_QUOTES, "UTF-8");
		$colVendedor = "<strong>{$cod}</strong> <span class='text-muted'>{$nom}</span>";

		$colEsp = "<div class='mr-cell'>";
		if ($modeloEsp !== "") {
			$colEsp .= "<div class='mr-mod'>" . htmlspecialchars($modeloEsp, ENT_QUOTES, "UTF-8") . "</div>";
			$colEsp .= "<div class='mr-line'><span class='mr-meta'>"
				. htmlspecialchars(mrTieneValor($metaDoc) ? mrFmt($metaDoc, 2) : "—", ENT_QUOTES, "UTF-8")
				. "</span><span class='mr-sep'>/</span><span class='mr-real'>"
				. htmlspecialchars(mrFmt($docenasEsp, 2), ENT_QUOTES, "UTF-8")
				. "</span></div>";
			if (mrTieneValor($metaDoc) && $pctEsp !== null) {
				$cls = $pctEsp >= 100 ? "success" : ($pctEsp >= 70 ? "warning" : "danger");
				$ancho = min(100, max(0, (float) $pctEsp));
				$pctTxt = number_format((float) $pctEsp, 0, ".", ",") . "%";
				$colEsp .= "<div class='progress mr-bar'><div class='progress-bar progress-bar-{$cls}' style='width:{$ancho}%;'>"
					. htmlspecialchars($pctTxt, ENT_QUOTES, "UTF-8") . "</div></div>";
			}
			$colEsp .= mrAporteHtml($aporteEsp);
		} else {
			$colEsp .= "<span class='mr-empty'>—</span>";
		}
		$colEsp .= "</div>";

		$colPagar = ((float) $comision["total"] > 0)
			? "<span class='mr-pay' title='" . htmlspecialchars($titlePagar, ENT_QUOTES, "UTF-8") . "'>"
				. htmlspecialchars(mrFmt($comision["total"], 2), ENT_QUOTES, "UTF-8") . "</span>"
			: "<span class='text-muted' title='" . htmlspecialchars($titlePagar, ENT_QUOTES, "UTF-8") . "'>0.00</span>";

		$acciones = "";
		if ($puedeEditar) {
			$acciones = "<button class='btn btn-xs btn-warning btnEditarMetasRetos' "
				. "codVendedor='" . htmlspecialchars($f["cod_vendedor"], ENT_QUOTES, "UTF-8") . "' "
				. "nombreVendedor='" . htmlspecialchars($f["nombre_vendedor"], ENT_QUOTES, "UTF-8") . "' "
				. "title='Configurar'><i class='fa fa-pencil'></i></button>";
		}

		$data[] = array(
			$colVendedor,
			mrCelda($metaMonto, $f["venta_real"], $pctMonto, $aporteMonto, 0, 2),
			mrCelda($metaCli, $f["clientes_nuevos"], $pctCli, $aporteCli, 0, 0),
			mrCelda($metaMod, $f["modelos_activos"], $pctMod, $aporteMod, 0, 0),
			$colEsp,
			$colPagar,
			$acciones
		);
	}
}

echo json_encode(array(
	"data" => $data,
	"total_pagar" => round($totalPagarPeriodo, 2)
));
