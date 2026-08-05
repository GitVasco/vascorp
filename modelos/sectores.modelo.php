<?php

require_once "conexion.php";

class ModeloSectores{

	/** Código legado usado en entaller_cabjf para envíos internos sin sector real. */
	const COD_INTERNO_LEGADO = "VC";

	/** Paleta pastel fija para talleres (UI). hex => etiqueta amigable */
	static public function mdlPaletaPasteles()
	{
		return array(
			"#A8D5E5" => "Azul suave",
			"#B8E0D2" => "Menta",
			"#D4C1EC" => "Lavanda",
			"#F7C5CC" => "Rosa",
			"#FFE5B4" => "Durazno",
			"#C5E1A5" => "Verde claro",
			"#FFD6A5" => "Albaricoque",
			"#B5EAD7" => "Agua",
			"#E2F0CB" => "Lima suave",
			"#FFDAC1" => "Coral claro",
			"#C7CEEA" => "Lila",
			"#F1C0E8" => "Orquídea",
			"#A0C4FF" => "Celeste",
			"#FDFFB6" => "Amarillo suave",
			"#CAFFBF" => "Verde menta",
			"#9BF6FF" => "Turquesa"
		);
	}

	static public function mdlColorPastelPorDefecto($codigo = "")
	{
		$hexes = array_keys(self::mdlPaletaPasteles());
		$codigo = strtoupper(trim((string) $codigo));
		if ($codigo === "" || empty($hexes)) {
			return "#A8D5E5";
		}
		$idx = abs(crc32($codigo)) % count($hexes);
		return $hexes[$idx];
	}

	static public function mdlNormalizarColor($color, $codigoFallback = "")
	{
		$color = strtoupper(trim((string) $color));
		if (preg_match('/^#[0-9A-F]{6}$/', $color)) {
			return $color;
		}
		return self::mdlColorPastelPorDefecto($codigoFallback);
	}

	static public function mdlNormalizarEstado($estado)
	{
		return ((int) $estado === 0) ? 0 : 1;
	}

	/*=============================================
	¿SECTOR INTERNO? (tipo = 0 o código legado VC)
	=============================================*/

	static public function mdlEsInterno($codSector){

		$cod = strtoupper(trim((string) $codSector));

		if ($cod === "") {
			return false;
		}

		if ($cod === self::COD_INTERNO_LEGADO) {
			return true;
		}

		$sector = self::mdlMostrarSectores($cod);

		if (!$sector || !is_array($sector)) {
			return false;
		}

		// Mismo criterio que la grilla del maestro: tipo == 0 (NULL cuenta como taller)
		return !isset($sector["tipo"]) || (int) $sector["tipo"] === 0 || $sector["tipo"] === "0" || $sector["tipo"] === null;

	}

	/*=============================================
	SECTORES POR TIPO (0 = interno/taller, 1 = externo/servicio)
	No incluye el código legado VC (no es fila de sectorjf).
	=============================================*/

	static public function mdlSectoresPorTipo($tipo){

		$tipo = ((int) $tipo === 0) ? 0 : 1;

		if ($tipo === 0) {
			$stmt = Conexion::conectar()->prepare(
				"SELECT * FROM sectorjf
				 WHERE tipo = 0 OR tipo IS NULL
				 ORDER BY cod_sector"
			);
		} else {
			$stmt = Conexion::conectar()->prepare(
				"SELECT * FROM sectorjf
				 WHERE tipo IS NOT NULL AND tipo <> 0
				 ORDER BY cod_sector"
			);
		}

		$stmt->execute();

		return $stmt->fetchAll();

	}

	/*=============================================
	¿IMPRIMIR TICKETS? (solo internos / VC legado)
	=============================================*/

	static public function mdlDebeImprimirTickets($codSector){

		return self::mdlEsInterno($codSector);

	}

	/*=============================================
	CREAR SECTOR
	=============================================*/

	static public function mdlIngresarSector($datos){

		$tipo = (isset($datos["tipo"]) && (int) $datos["tipo"] === 0) ? 0 : 1;
		$estado = self::mdlNormalizarEstado(isset($datos["estado"]) ? $datos["estado"] : 1);
		$color = self::mdlNormalizarColor(
			isset($datos["color"]) ? $datos["color"] : "",
			isset($datos["codigo"]) ? $datos["codigo"] : ""
		);

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO sectorjf (cod_sector, nom_sector, tipo, estado, color)
			 VALUES (:codigo, :nombre, :tipo, :estado, :color)"
		);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["sector"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_INT);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
		$stmt->bindParam(":color", $color, PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}    

	/*=============================================
	MOSTRAR SECTORES
	=============================================*/

	static public function mdlMostrarSectores($valor){

		if($valor != null){

			$stmt = Conexion::conectar()->prepare("SELECT 
                                                            *,
															CONCAT(cod_sector, ' - ', nom_sector) AS sector  
                                                        FROM
                                                            sectorjf c
                                                        WHERE c.cod_sector = :valor");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("  SELECT 
                                                            * 
                                                        FROM
                                                            sectorjf ORDER BY cod_sector");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

    }
    
	/*=============================================
	EDITAR SECTOR
	=============================================*/

	static public function mdlEditarSector($datos){

		$tipo = (isset($datos["tipo"]) && (int) $datos["tipo"] === 0) ? 0 : 1;
		$estado = self::mdlNormalizarEstado(isset($datos["estado"]) ? $datos["estado"] : 1);
		$color = self::mdlNormalizarColor(
			isset($datos["color"]) ? $datos["color"] : "",
			isset($datos["codigo"]) ? $datos["codigo"] : ""
		);

		$stmt = Conexion::conectar()->prepare(
			"UPDATE sectorjf
			 SET cod_sector = :codigo,
			     nom_sector = :sector,
			     tipo = :tipo,
			     estado = :estado,
			     color = :color
			 WHERE id = :valor"
		);

		$stmt->bindParam(":valor", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":sector", $datos["sector"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_INT);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
		$stmt->bindParam(":color", $color, PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

    }
	
	
	/*=============================================
	ELIMINAR SECTOR
	=============================================*/

	static public function mdlEliminarSector($datos){

		$stmt = Conexion::conectar()->prepare("  DELETE 
                                                        FROM
                                                        sectorjf 
                                                        WHERE cod_sector = :valor");

		$stmt -> bindParam(":valor", $datos, PDO::PARAM_INT);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}    

}