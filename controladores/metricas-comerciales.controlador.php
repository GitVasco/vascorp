<?php

class ControladorMetricasComerciales
{

	static public function ctrConciliacionCobertura($anio, $mes)
	{
		$p = ControladorMetasRetos::ctrNormalizarPeriodo($anio, $mes);
		return ModeloMetricasComerciales::mdlConciliacionCoberturaPeriodo($p["anio"], $p["mes"]);
	}

	static public function ctrDetalleMarcaVendedor($codVendedor, $anio, $mes)
	{
		$p = ControladorMetasRetos::ctrNormalizarPeriodo($anio, $mes);
		$mapa = ModeloMetricasComerciales::mdlVentasCoberturaPorMarca(
			$p["anio"],
			$p["mes"],
			trim((string) $codVendedor)
		);
		$cod = trim((string) $codVendedor);
		return isset($mapa[$cod]) ? $mapa[$cod] : array();
	}
}
