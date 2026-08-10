<?php

require_once "conexion.php";

class ModeloAbonos{

	/*=============================================
	CREAR ABONO
	=============================================*/

	static public function mdlIngresarAbono($tabla,$datos){

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(fecha,descripcion,monto,agencia,num_ope) VALUES (:fecha,:descripcion,:monto,:agencia,:num_ope)");

		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_INT);
        $stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
        $stmt->bindParam(":num_ope", $datos["num_ope"], PDO::PARAM_STR);


		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}    

	/*=============================================
	MOSTRAR ABONOS
	=============================================*/

	static public function mdlMostrarAbonos($tabla, $item, $valor, $filtroMotivo = null, $anio = null, $mes = null, $anioDesde = 2026)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}

		$sql = "SELECT * FROM $tabla";
		$condiciones = array();
		$params = array();

		if ($anioDesde !== null && (int) $anioDesde > 0) {
			$condiciones[] = "fecha >= :fecha_desde";
			$params[":fecha_desde"] = sprintf("%04d-01-01", (int) $anioDesde);
		}

		if ($anio !== null && $anio !== "") {
			$condiciones[] = "YEAR(fecha) = :anio";
			$params[":anio"] = (int) $anio;
		}

		if ($mes !== null && $mes !== "" && (int) $mes >= 1 && (int) $mes <= 12) {
			$condiciones[] = "MONTH(fecha) = :mes";
			$params[":mes"] = (int) $mes;
		}

		if ($filtroMotivo === "sin") {
			$condiciones[] = "(motivo_pendiente IS NULL OR motivo_pendiente = '')";
		} elseif ($filtroMotivo !== null && $filtroMotivo !== "") {
			$condiciones[] = "motivo_pendiente = :motivo_pendiente";
			$params[":motivo_pendiente"] = $filtroMotivo;
		}

		if (!empty($condiciones)) {
			$sql .= " WHERE " . implode(" AND ", $condiciones);
		}

		$sql .= " ORDER BY id DESC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $clave => $valorParam) {
			$tipo = is_int($valorParam) ? PDO::PARAM_INT : PDO::PARAM_STR;
			$stmt->bindValue($clave, $valorParam, $tipo);
		}
		$stmt->execute();

		return $stmt->fetchAll();
	}
    
	/*=============================================
	EDITAR ABONO
	=============================================*/

	static public function mdlEditarAbono($tabla,$datos){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET fecha = :fecha, descripcion = :descripcion, monto = :monto, agencia = :agencia,num_ope = :num_ope WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_INT);
        $stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
        $stmt->bindParam(":num_ope", $datos["num_ope"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

    }

	/*=============================================
	EDITAR MOTIVO / OBSERVACIÓN PENDIENTE
	=============================================*/

	static public function mdlEditarMotivoPendiente($tabla, $datos){

		$stmt = Conexion::conectar()->prepare(
			"UPDATE $tabla SET
				motivo_pendiente = :motivo_pendiente,
				observacion_pendiente = :observacion_pendiente,
				motivo_usuario = :motivo_usuario,
				motivo_fecha = :motivo_fecha
			 WHERE id = :id"
		);

		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);

		if ($datos["motivo_pendiente"] === null || $datos["motivo_pendiente"] === "") {
			$stmt->bindValue(":motivo_pendiente", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":motivo_pendiente", $datos["motivo_pendiente"], PDO::PARAM_STR);
		}

		if ($datos["observacion_pendiente"] === null || $datos["observacion_pendiente"] === "") {
			$stmt->bindValue(":observacion_pendiente", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":observacion_pendiente", $datos["observacion_pendiente"], PDO::PARAM_STR);
		}

		if ($datos["motivo_usuario"] === null || $datos["motivo_usuario"] === "") {
			$stmt->bindValue(":motivo_usuario", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":motivo_usuario", $datos["motivo_usuario"], PDO::PARAM_STR);
		}

		$stmt->bindValue(":motivo_fecha", $datos["motivo_fecha"], PDO::PARAM_STR);

		if ($stmt->execute()) {
			return "ok";
		}

		return "error";

	}
	
	
	/*=============================================
	ELIMINAR TIPO DE PAGO
	=============================================*/

	static public function mdlEliminarAbono($tabla,$datos){

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");

		$stmt -> bindParam(":id", $datos, PDO::PARAM_INT);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}

	/*=============================================
	PENDIENTES AGRUPADOS POR MES (abonosjf)
	=============================================*/

	static public function mdlPendientesPorMes($anio)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				YEAR(fecha) AS anio,
				MONTH(fecha) AS mes,
				COUNT(*) AS cantidad,
				IFNULL(SUM(monto), 0) AS monto
			 FROM abonosjf
			 WHERE YEAR(fecha) = :anio
			 GROUP BY YEAR(fecha), MONTH(fecha)
			 ORDER BY mes ASC"
		);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	APLICADOS DESDE CTA CTE (05 / 15 + OP-)
	=============================================*/

	static public function mdlAplicadosPorMes($anio)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				YEAR(fecha) AS anio,
				MONTH(fecha) AS mes,
				COUNT(*) AS cantidad,
				IFNULL(SUM(monto), 0) AS monto
			 FROM cuenta_ctejf
			 WHERE tip_mov = '-'
			   AND cod_pago IN ('05', '15')
			   AND notas LIKE 'OP-%'
			   AND YEAR(fecha) = :anio
			 GROUP BY YEAR(fecha), MONTH(fecha)
			 ORDER BY mes ASC"
		);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

}
