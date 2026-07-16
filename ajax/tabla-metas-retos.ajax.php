<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.config.php";
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
function mrCelda($meta, $real, $pct, $aporte = 0, $decMeta = 0, $decReal = 0, $forzarMostrar = false, $metaExtraHtml = "")
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
		. "</span>" . $metaExtraHtml
		. "<span class='mr-sep'>/</span><span class='mr-real'>"
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

function mrEtiquetaIncentivoLista($inc)
{
	$tipo = isset($inc["tipo_objetivo"]) ? $inc["tipo_objetivo"] : "";
	if ($tipo === "modelo") {
		return "Mod " . (isset($inc["modelo"]) ? $inc["modelo"] : "");
	}
	if ($tipo === "modelo_color") {
		$nom = !empty($inc["nombre_color"]) ? $inc["nombre_color"] : (isset($inc["cod_color"]) ? $inc["cod_color"] : "");
		return "Mod " . (isset($inc["modelo"]) ? $inc["modelo"] : "") . "·" . $nom;
	}
	if ($tipo === "articulo") {
		return "Art " . (isset($inc["articulo"]) ? $inc["articulo"] : "");
	}
	return "Incentivo";
}

if (is_array($filas)) {
	foreach ($filas as $f) {
		$reto = isset($f["reto"]) && is_array($f["reto"]) ? $f["reto"] : array();
		$metaCob = isset($reto["meta_cobranza"]) ? $reto["meta_cobranza"] : null;
		$metaMonto = isset($reto["meta_monto"]) ? $reto["meta_monto"] : null;
		$metaCli = isset($reto["meta_clientes"]) ? $reto["meta_clientes"] : null;
		$metaMod = isset($reto["meta_modelos"]) ? $reto["meta_modelos"] : null;
		$extraMetaMod = "";
		if (isset($reto["meta_modelos_modo"]) && $reto["meta_modelos_modo"] === "porcentaje"
			&& isset($reto["meta_modelos_pct"]) && $reto["meta_modelos_pct"] !== null && $reto["meta_modelos_pct"] !== "") {
			$extraMetaMod = " <small class='text-muted'>("
				. htmlspecialchars(number_format((float) $reto["meta_modelos_pct"], 0, ".", ","), ENT_QUOTES, "UTF-8")
				. "%)</small>";
		}

		$cobranzaReal = isset($f["cobranza_neta_real"]) ? $f["cobranza_neta_real"] : 0;
		$pctCob = ControladorMetasRetos::ctrPctAvance($cobranzaReal, $metaCob);
		$pctMonto = ControladorMetasRetos::ctrPctAvance($f["venta_real"], $metaMonto);
		$pctCli = ControladorMetasRetos::ctrPctAvance($f["clientes_nuevos"], $metaCli);
		$pctMod = ControladorMetasRetos::ctrPctAvance($f["modelos_activos"], $metaMod);

		$comision = ControladorMetasRetos::ctrCalcularComisionEstimada($f);
		$totalPagarPeriodo += (float) $comision["total"];
		$det = $comision["detalle"];
		$aporteCob = isset($det["cobranza"]) ? (float) $det["cobranza"] : 0.0;
		$aporteMonto = isset($det["monto"]) ? (float) $det["monto"] : 0.0;
		$aporteCli = isset($det["clientes"]) ? (float) $det["clientes"] : 0.0;
		$aporteMod = isset($det["modelos"]) ? (float) $det["modelos"] : 0.0;
		$aporteInc = isset($det["incentivos_producto"]) ? (float) $det["incentivos_producto"] : 0.0;
		$detIncs = isset($det["incentivos"]) && is_array($det["incentivos"]) ? $det["incentivos"] : array();

		$titlePagar = "Cobranza: " . number_format($aporteCob, 2, ".", ",")
			. " · Cli: " . number_format($aporteCli, 2, ".", ",")
			. " · Mod: " . number_format($aporteMod, 2, ".", ",")
			. " · Inc: " . number_format($aporteInc, 2, ".", ",")
			. " · Ventas: " . number_format($aporteMonto, 2, ".", ",")
			. (mrComisionVentasHabilitada() ? "" : " (desactivada)");

		$cod = htmlspecialchars($f["cod_vendedor"], ENT_QUOTES, "UTF-8");
		$nom = htmlspecialchars($f["nombre_vendedor"], ENT_QUOTES, "UTF-8");
		$colVendedor = "<strong>{$cod}</strong> <span class='text-muted'>{$nom}</span>";

		$incs = (isset($f["incentivos"]) && is_array($f["incentivos"])) ? $f["incentivos"] : array();
		$colInc = "<div class='mr-cell'>";
		if (!empty($incs)) {
			$n = count($incs);
			$colInc .= "<div class='mr-inc-summary'>"
				. htmlspecialchars($n . ($n === 1 ? " incentivo" : " incentivos"), ENT_QUOTES, "UTF-8")
				. " · S/ " . htmlspecialchars(mrFmt($aporteInc, 2), ENT_QUOTES, "UTF-8")
				. "</div>";
			$lineas = array();
			foreach ($incs as $i => $inc) {
				$etiq = mrEtiquetaIncentivoLista($inc);
				$avance = isset($inc["avance_meta"]) ? $inc["avance_meta"] : 0;
				$metaCant = isset($inc["meta_cantidad"]) ? $inc["meta_cantidad"] : null;
				$aporteLinea = isset($detIncs[$i]["aporte"]) ? (float) $detIncs[$i]["aporte"] : 0.0;
				$uni = (isset($inc["unidad_meta"]) && $inc["unidad_meta"] === "unidades") ? "u" : "doc";
				$lineas[] = htmlspecialchars($etiq, ENT_QUOTES, "UTF-8")
					. " "
					. htmlspecialchars(mrFmt($avance, 1), ENT_QUOTES, "UTF-8")
					. "/"
					. htmlspecialchars(mrTieneValor($metaCant) ? mrFmt($metaCant, 1) : "—", ENT_QUOTES, "UTF-8")
					. " " . $uni
					. " (+" . htmlspecialchars(mrFmt($aporteLinea, 2), ENT_QUOTES, "UTF-8") . ")";
			}
			$colInc .= "<div class='mr-inc-detalle' title='"
				. htmlspecialchars(strip_tags(implode(" | ", $lineas)), ENT_QUOTES, "UTF-8")
				. "'>" . implode("<br>", array_slice($lineas, 0, 3));
			if (count($lineas) > 3) {
				$colInc .= "<br><span class='text-muted'>+" . (count($lineas) - 3) . " más</span>";
			}
			$colInc .= "</div>";
			$colInc .= mrAporteHtml($aporteInc);
		} else {
			$colInc .= "<span class='mr-empty'>—</span>";
		}
		$colInc .= "</div>";

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
			mrCelda($metaCob, $cobranzaReal, $pctCob, $aporteCob, 0, 2),
			mrCelda($metaMonto, $f["venta_real"], $pctMonto, $aporteMonto, 0, 2),
			mrCelda($metaCli, $f["clientes_nuevos"], $pctCli, $aporteCli, 0, 0),
			mrCelda($metaMod, $f["modelos_activos"], $pctMod, $aporteMod, 0, 0, false, $extraMetaMod),
			$colInc,
			$colPagar,
			$acciones
		);
	}
}

echo json_encode(array(
	"data" => $data,
	"total_pagar" => round($totalPagarPeriodo, 2),
	"comision_ventas_habilitada" => mrComisionVentasHabilitada()
));
