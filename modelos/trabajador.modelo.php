<?php

require_once "conexion.php";

class ModeloTrabajador{
/*=============================================
	MOSTRAR TRABAJADOR
	=============================================*/

	static public function mdlMostrarTrabajador($tabla,$item,$valor){

		if($valor != null){

			$stmt = Conexion::conectar()->prepare("SELECT 
																*,
																CONCAT(
																t.nom_tra,
																' ',
																t.ape_pat_tra,
																' ',
																t.ape_mat_tra
																) AS nombre ,
																t.sector
															FROM
																trabajadorjf t 
															WHERE t.cod_tra = $valor");

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT 
                                                            t.cod_tra,
                                                            d.tipo_doc,
                                                            t.nro_doc_tra,
                                                            t.nom_tra,
                                                            t.ape_pat_tra,
                                                            t.ape_mat_tra,
                                                            tt.nom_tip_trabajador,
															t.estado,
                                                            t.sueldo_total,
															t.sector,
															s.nom_sector
															
                                                        FROM
                                                            $tabla t
															LEFT JOIN tipo_documentojf d
															ON d.cod_doc = t.cod_doc
															LEFT JOIN tipo_trabajadorjf tt
															ON tt.cod_tip_tra = t.cod_tip_tra
															LEFT JOIN sectorjf s
															ON s.cod_sector = t.sector");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

    }

    /*=============================================
	REGISTRO DE PRODUCTO
	=============================================*/
	static public function mdlIngresarTrabajador($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla( cod_doc, nro_doc_tra, nom_tra, ape_pat_tra, ape_mat_tra, cod_tip_tra, sueldo_total,sector) VALUES ( :cod_doc, :nro_doc_tra, :nom_tra, :ape_pat_tra, :ape_mat_tra, :cod_tip_tra, :sueldo_total,:sector)");

		//$stmt->bindParam(":cod_tra", $datos["cod_tra"], PDO::PARAM_INT);
		$stmt->bindParam(":cod_doc", $datos["cod_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":nro_doc_tra", $datos["nro_doc_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":nom_tra", $datos["nom_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_pat_tra", $datos["ape_pat_tra"], PDO::PARAM_STR);
        $stmt->bindParam(":ape_mat_tra", $datos["ape_mat_tra"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_tip_tra", $datos["cod_tip_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":sueldo_total", $datos["sueldo_total"], PDO::PARAM_STR);
		$stmt->bindParam(":sector", $datos["sector"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

    /*=============================================
	EDITAR PRODUCTO
	=============================================*/
	static public function mdlEditarTrabajador($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET  cod_doc = :cod_doc , nro_doc_tra = :nro_doc_tra, nom_tra = :nom_tra, ape_pat_tra = :ape_pat_tra, ape_mat_tra = :ape_mat_tra, cod_tip_tra = :cod_tip_tra, sueldo_total = :sueldo_total , sector = :sector WHERE cod_tra = :cod_tra");


		
        $stmt->bindParam(":cod_doc", $datos["cod_doc"], PDO::PARAM_INT);
        $stmt->bindParam(":nro_doc_tra", $datos["nro_doc_tra"], PDO::PARAM_INT);
		$stmt->bindParam(":nom_tra", $datos["nom_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_pat_tra", $datos["ape_pat_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_mat_tra", $datos["ape_mat_tra"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_tip_tra", $datos["cod_tip_tra"], PDO::PARAM_INT);
		$stmt->bindParam(":sueldo_total", $datos["sueldo_total"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_tra", $datos["cod_tra"], PDO::PARAM_INT);
        $stmt->bindParam(":sector", $datos["sector"], PDO::PARAM_STR);
       

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}	


    /*=============================================
	ELIMINAR OPERACION
	=============================================*/

	static public function mdlEliminarTrabajador($tabla,$datos){

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE cod_tra = :cod_tra");

		$stmt -> bindParam(":cod_tra", $datos, PDO::PARAM_INT);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	} 
	
	
	/*
	* MOSTRAR TRABAJADORES ACTIVOS
	*/

	static public function mdlMostrarTrabajadorActivo(){

		$stmt = Conexion::conectar()->prepare("SELECT 
		cod_tra,
		d.tipo_doc,
		nro_doc_tra,
		CONCAT(
			nom_tra,
			', ',
			ape_pat_tra,
			' ',
			ape_mat_tra
		) AS trabajador,
		nom_tra,
		ape_pat_tra,
		ape_mat_tra,
		tt.nom_tip_trabajador,
		estado,
		sueldo_total
	FROM
		trabajadorjf t,
		tipo_documentojf d,
		tipo_trabajadorjf tt
	WHERE
		d.cod_doc = t.cod_doc
			AND tt.cod_tip_tra = t.cod_tip_tra
			AND t.estado = 'activo'
			ORDER BY t.nom_tra");

		$stmt -> execute();

		return $stmt -> fetchAll();

		$stmt -> close();

		$stmt = null;

    }

	/* 
	* Método para activar y desactivar un Trabajador
	*/
	static public function mdlActualizarTrabajador($tabla,$valor1, $valor2){

		$sql = "UPDATE $tabla SET estado=:estado WHERE cod_tra=:valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor", $valor2, PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}
	
	/* 
	* Método para poner a todos en CERO
	*/
	static public function mdlTrabajadorSet($usuario,$sector){

		$sql = "UPDATE 
					trabajadorjf 
				SET
					configuracion = 0,
					usuario = 0
					where usuario = $usuario OR sector=:sector";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":sector", $sector, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para configurar trabajador
	*/
	static public function ctrConfigurarTrabajador($cod_tra, $usuario){

		$sql = "UPDATE 
					trabajadorjf 
				SET
					configuracion = 1,
					usuario = :usuario
				WHERE cod_tra = :cod_tra";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":cod_tra", $cod_tra,PDO::PARAM_INT);
		$stmt->bindParam(":usuario", $usuario,PDO::PARAM_INT);

		$stmt->execute();

		$stmt = null;
	}
	
	/*
	* MOSTRAR TRABAJADOR CONFIGURADO
	*/

	static public function mdlMostrarTrabajadorConfigurado($usuario){

		$stmt = Conexion::conectar()->prepare("SELECT 
									t.cod_tra,
									CONCAT(
									t.cod_tra,
									' - ',
									t.nom_tra,
									' ',
									t.ape_pat_tra,
									' ',
									t.ape_mat_tra
									) AS trabajador 
								FROM
									trabajadorjf t 
								WHERE t.configuracion = '1' and usuario = $usuario ");

		$stmt -> execute();

		return $stmt -> fetch();

		$stmt = null;

	}
	
	/*=============================================
	MOSTRAR TRABAJADOR
	=============================================*/

	static public function mdlMostrarTrabajador2($valor){

		if($valor != null){

			$stmt = Conexion::conectar()->prepare("SELECT t.* FROM trabajadores_graljf t WHERE t.id = $valor");

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT  t.* FROM trabajadores_graljf t ORDER BY t.sector ASC");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

	}

	static public function mdlMostrarTrabajador2Activo($valor){

		

		$stmt = Conexion::conectar()->prepare("SELECT  t.* FROM trabajadores_graljf t WHERE t.estado='1' ORDER BY t.sector ASC");

		$stmt -> execute();

		return $stmt -> fetchAll();

		
		$stmt -> close();

		$stmt = null;

	}

	static public function mdlMostrarTrabajador2Inactivo($valor){

		

		$stmt = Conexion::conectar()->prepare("SELECT  t.* FROM trabajadores_graljf t WHERE t.estado='0' ORDER BY t.sector ASC");

		$stmt -> execute();

		return $stmt -> fetchAll();

		
		$stmt -> close();

		$stmt = null;

	}
	
	/*
	* Quincena del módulo Quincenas que cerró antes que "esta".
	* "Esta" = la que contiene la fecha; si no hay, la última con fin < fecha.
	*/
	static public function mdlQuincenaAnterior($fecha)
	{
		$pdo = Conexion::conectar();

		$stmtActual = $pdo->prepare(
			"SELECT id, inicio, fin, ano, mes, quincena
			 FROM quincenasjf
			 WHERE :fecha BETWEEN inicio AND fin
			 ORDER BY fin DESC
			 LIMIT 1"
		);
		$stmtActual->bindParam(":fecha", $fecha, PDO::PARAM_STR);
		$stmtActual->execute();
		$actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

		if ($actual && !empty($actual["inicio"])) {
			$stmtPrev = $pdo->prepare(
				"SELECT id, inicio, fin, ano, mes, quincena
				 FROM quincenasjf
				 WHERE fin < :inicioActual
				 ORDER BY fin DESC
				 LIMIT 1"
			);
			$stmtPrev->bindParam(":inicioActual", $actual["inicio"], PDO::PARAM_STR);
			$stmtPrev->execute();
			$prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
			return $prev ? $prev : null;
		}

		$stmt = $pdo->prepare(
			"SELECT id, inicio, fin, ano, mes, quincena
			 FROM quincenasjf
			 WHERE fin < :fecha
			 ORDER BY fin DESC
			 LIMIT 1"
		);
		$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row : null;
	}

	/*
	* Total S/ de quincena (columna Total de /pagos, sin bono).
	* Retorna mapa [cod_tra => total].
	*/
	static public function mdlPromedioDiarioProduccion($inicio, $fin, $sector = null)
	{
		if (!class_exists("ModeloProduccion")) {
			require_once __DIR__ . "/produccion.modelo.php";
		}

		return ModeloProduccion::mdlPromedioDiarioPagoQuincena($inicio, $fin, $sector);
	}

	/*
	* Trabajadores activos por sector (compensación día).
	* $tipo: dia_sueldo | medio_sueldo | dia_promedio | medio_promedio
	* $diasTrabajados: divisor del Total para promedio (lo indica el usuario).
	*/
	static public function mdlTrabajadoresActivosPorSector($sector, $tipo = "dia_sueldo", $fecha = null, $diasTrabajados = 15)
	{
		$tiposValidos = array("dia_sueldo", "medio_sueldo", "dia_promedio", "medio_promedio");
		if (!in_array($tipo, $tiposValidos, true)) {
			$tipo = "dia_sueldo";
		}

		$diasTrabajados = (float) $diasTrabajados;
		if ($diasTrabajados <= 0) {
			$diasTrabajados = 15;
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT
				t.cod_tra,
				CONCAT(TRIM(t.nom_tra), ' ', TRIM(t.ape_pat_tra), ' ', TRIM(t.ape_mat_tra)) AS nombre,
				t.sueldo_total,
				t.sector,
				s.nom_sector
			FROM trabajadorjf t
			LEFT JOIN sectorjf s ON s.cod_sector = t.sector
			WHERE t.estado = 'Activo'
			  AND t.sector = :sector
			ORDER BY
				TRIM(t.nom_tra),
				TRIM(t.ape_pat_tra),
				TRIM(t.ape_mat_tra)"
		);

		$stmt->bindParam(":sector", $sector, PDO::PARAM_STR);
		$stmt->execute();
		$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$lista) {
			return array();
		}

		$totales = array();
		$quincena = null;
		$usaPromedio = ($tipo === "dia_promedio" || $tipo === "medio_promedio");

		if ($usaPromedio) {
			if ($fecha === null || $fecha === "") {
				$fecha = date("Y-m-d");
			}
			$quincena = self::mdlQuincenaAnterior($fecha);
			if ($quincena) {
				$totales = self::mdlPromedioDiarioProduccion(
					$quincena["inicio"],
					$quincena["fin"],
					$sector
				);
			}
		}

		foreach ($lista as &$t) {
			$cod = (int) $t["cod_tra"];
			$sueldo = isset($t["sueldo_total"]) ? (float) $t["sueldo_total"] : 0;
			$diaSueldo = $sueldo > 0 ? round($sueldo / 30, 2) : 0;
			$total = isset($totales[$cod]) ? round((float) $totales[$cod], 2) : 0;
			$diaPromedio = ($total > 0 && $diasTrabajados > 0)
				? round($total / $diasTrabajados, 2)
				: 0;

			if ($tipo === "dia_sueldo") {
				$base = $sueldo;
				$monto = $diaSueldo;
			} elseif ($tipo === "medio_sueldo") {
				$base = $sueldo;
				$monto = round($diaSueldo / 2, 2);
			} elseif ($tipo === "dia_promedio") {
				$base = $total;
				$monto = $diaPromedio;
			} else {
				$base = $total;
				$monto = round($diaPromedio / 2, 2);
			}

			$t["base_calculo"] = $base;
			$t["total_quincena"] = $total;
			$t["promedio_diario"] = $diaPromedio;
			$t["dias_trabajados"] = $diasTrabajados;
			$t["monto_feriado"] = $monto;
			$t["cantidad"] = round($monto * 12, 2);
			$t["tipo"] = $tipo;
			if ($quincena) {
				$t["quincena_inicio"] = $quincena["inicio"];
				$t["quincena_fin"] = $quincena["fin"];
			}
		}
		unset($t);

		return $lista;
	}

	/* 
	* Método para activar y desactivar un Trabajador
	*/
	static public function mdlActualizarTrabajador2($tabla,$valor1, $valor2){

		$sql = "UPDATE $tabla SET estado=:estado WHERE id=:valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor", $valor2, PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

}