<?php

require_once "conexion.php";

class ModeloSectores{

	/** Código legado usado en entaller_cabjf para envíos internos sin sector real. */
	const COD_INTERNO_LEGADO = "VC";

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

		$stmt = Conexion::conectar()->prepare("INSERT INTO sectorjf (cod_sector, nom_sector, tipo) 
        VALUES
          (:codigo, :nombre, :tipo) ;");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["sector"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_INT);

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

		$stmt = Conexion::conectar()->prepare("UPDATE 
                                                        sectorjf 
                                                    SET
                                                        cod_sector = :codigo,
                                                        nom_sector = :sector,
                                                        tipo = :tipo
                                                    WHERE id = :valor");

		$stmt->bindParam(":valor", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":sector", $datos["sector"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_INT);

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