<?php

require_once "../controladores/sectores.controlador.php";
require_once "../modelos/sectores.modelo.php";

class AjaxSectores{

	/*=============================================
	EDITAR SECTOR
	=============================================*/	

	public $id;

	public function ajaxEditarSector(){

		$valor = $this->id;

		$respuesta = ControladorSectores::ctrMostrarSectores($valor);

		echo json_encode($respuesta);


	}

	/*=============================================
	DIAGNÓSTICO HELPERS (Fase 0 — solo lectura)
	POST: probeSectores=1, opcional codSector (default prueba VC/T5)
	=============================================*/

	public function ajaxProbeHelpers(){

		$cod = isset($_POST["codSector"]) ? trim((string) $_POST["codSector"]) : "";
		$codigosPrueba = $cod !== "" ? array($cod) : array("VC", "T5", "T1", "T3", "T0");

		$detalle = array();
		foreach ($codigosPrueba as $c) {
			$detalle[] = array(
				"cod" => $c,
				"esInterno" => ControladorSectores::ctrEsInterno($c) ? 1 : 0,
				"debeImprimirTickets" => ControladorSectores::ctrDebeImprimirTickets($c) ? 1 : 0
			);
		}

		$internos = ControladorSectores::ctrSectoresPorTipo(0);
		$externos = ControladorSectores::ctrSectoresPorTipo(1);

		echo json_encode(array(
			"ok" => 1,
			"detalle" => $detalle,
			"internos" => array_map(function ($r) {
				return isset($r["cod_sector"]) ? $r["cod_sector"] : null;
			}, $internos),
			"externos" => array_map(function ($r) {
				return isset($r["cod_sector"]) ? $r["cod_sector"] : null;
			}, $externos)
		));

	}

}

/*=============================================
EDITAR SECTOR
=============================================*/	

if(isset($_POST["idSector"])){

	$sector = new AjaxSectores();
	$sector -> id = $_POST["idSector"];
	$sector -> ajaxEditarSector();

}

/*=============================================
PROBE HELPERS FASE 0
=============================================*/

if(isset($_POST["probeSectores"])){

	$sector = new AjaxSectores();
	$sector -> ajaxProbeHelpers();

}