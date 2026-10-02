<?php

require_once "../controladores/sectores.controlador.php";
require_once "../modelos/sectores.modelo.php";
require_once "../modelos/gre-manual.modelo.php";
require_once "../controladores/gre-manual.controlador.php";

class AjaxSectores{

	/*=============================================
	EDITAR SECTOR
	=============================================*/	

	public $id;

	public function ajaxEditarSector(){

		$valor = $this->id;

		$respuesta = ControladorSectores::ctrMostrarSectores($valor);

		// Datos fiscales para guías (solo externos); null si no hay o falta la tabla
		if (is_array($respuesta) && isset($respuesta["cod_sector"])) {
			$respuesta["gre"] = ModeloGreManual::mdlDatosTaller($respuesta["cod_sector"]);
		}

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
BUSCAR UBIGEO (datos fiscales del taller)
=============================================*/

if(isset($_POST["consultarDocGre"])){

	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	header("Content-Type: application/json; charset=utf-8");
	if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
		echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
		exit;
	}
	echo json_encode(ControladorGreManual::ctrConsultarDocumento(
		isset($_POST["tipoDoc"]) ? $_POST["tipoDoc"] : "",
		$_POST["consultarDocGre"]
	));
	exit;

}

if(isset($_POST["buscarUbigeoGre"])){

	$q = trim((string) $_POST["buscarUbigeoGre"]);
	echo json_encode(strlen($q) >= 2 ? ModeloGreManual::mdlBuscarUbigeos($q) : array());
	exit;

}

/*=============================================
PROBE HELPERS FASE 0
=============================================*/

if(isset($_POST["probeSectores"])){

	$sector = new AjaxSectores();
	$sector -> ajaxProbeHelpers();

}