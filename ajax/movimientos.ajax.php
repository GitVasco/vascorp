<?php

require_once '../controladores/movimientos.controlador.php';
require_once '../modelos/movimientos.modelo.php';
require_once '../helpers/jsonpe.api.php';

class AjaxMovimientos{

    /* 
    * 
    */
	public $activarId;
    public $activarTarjeta;
    
	public function ajaxActualizarMovimientos(){
		
		$valor1=$this->año;
		
		$valor2=$this->mes;


		/* var_dump($tabla,$valor1,$valor2); */


		$respuesta=ModeloMovimientos::mdlActualizarMovimientos($valor1,$valor2);

		echo $respuesta;
	}


	public $completarHastaHoy;

	private function resolverTcFecha($fecha)
	{
		$api = JsonPeApi::consultarTipoCambio($fecha);
		$ventaApi = isset($api["venta"]) ? $api["venta"] : null;
		$compraApi = isset($api["compra"]) ? $api["compra"] : 0;

		if (
			$ventaApi !== null
			&& $ventaApi !== "Fuera de plazo permitido"
			&& is_numeric($ventaApi)
			&& (float) $ventaApi > 0
		) {
			return array(
				"compra" => is_numeric($compraApi) ? $compraApi : 0,
				"venta" => $ventaApi
			);
		}

		if (!class_exists("ModeloUtilidades")) {
			require_once "../modelos/utilidades.modelo.php";
		}

		$prev = ModeloUtilidades::mdlUltimoTipCambioTotalesAntes($fecha);
		if ($prev && (float) $prev["cambio_venta"] > 0) {
			return array(
				"compra" => $prev["cambio_compra"],
				"venta" => $prev["cambio_venta"]
			);
		}

		return null;
	}

	public function ajaxActualizarTC(){

		date_default_timezone_set("America/Lima");
		$fecha = $this->fecha;
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $fecha, $m)) {
			$fecha = $m[1];
		}

		$fechas = array($fecha);
		if (!empty($this->completarHastaHoy)) {
			if (!class_exists("ModeloUtilidades")) {
				require_once "../modelos/utilidades.modelo.php";
			}
			@set_time_limit(300);
			$filas = ModeloUtilidades::mdlTotalesSinTipCambio((int) date("Y"));
			$fechas = array();
			if (is_array($filas)) {
				foreach ($filas as $f) {
					if (!empty($f["fecha"])) {
						$fechas[] = $f["fecha"];
					}
				}
			}
			if (count($fechas) < 1) {
				echo "ok";
				return;
			}
		}

		$ok = 0;
		foreach ($fechas as $f) {
			$tc = $this->resolverTcFecha($f);
			if ($tc === null) {
				continue;
			}
			$respuesta = ModeloMovimientos::mdlActualizarTipoCambio(
				$tc["compra"],
				$tc["venta"],
				$f
			);
			if ($respuesta === "ok") {
				$ok++;
			}
		}

		echo $ok > 0 ? "ok" : "no";
	}


}

if(isset($_POST["año"])){
	$actualizar=new AjaxMovimientos();
	$actualizar->año=$_POST["año"];
	$actualizar->mes=$_POST["mes"];
	$actualizar->ajaxActualizarMovimientos();
}

if(isset($_POST["fecha"])){
	$actualizar=new AjaxMovimientos();
	$actualizar->fecha=$_POST["fecha"];
	$actualizar->completarHastaHoy = !empty($_POST["completarHastaHoy"]);
	$actualizar->ajaxActualizarTC();
}