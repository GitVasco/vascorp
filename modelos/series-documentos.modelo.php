<?php

require_once "conexion.php";

class ModeloSeriesDocumentos
{

	/** @return array tipo => [col_serie, col_correlativo, etiqueta] */
	static public function mdlMapaTipos()
	{
		return array(
			"01" => array("serie" => "serie_factura", "correlativo" => "facturas", "etiqueta" => "Factura"),
			"03" => array("serie" => "serie_boletas", "correlativo" => "boletas", "etiqueta" => "Boleta"),
			"07" => array("serie" => "serie_nc", "correlativo" => "nota_credito", "etiqueta" => "Nota de crédito"),
			"08" => array("serie" => "serie_nd", "correlativo" => "nota_debito", "etiqueta" => "Nota de débito"),
			"09" => array("serie" => "serie_proformas", "correlativo" => "proformas", "etiqueta" => "Proforma"),
			"90" => array("serie" => "serie_guias", "correlativo" => "guias_remision", "etiqueta" => "Guía de remisión")
		);
	}

	static public function mdlListarSeries()
	{
		$mapa = self::mdlMapaTipos();
		$partes = array();

		foreach ($mapa as $tipo => $cfg) {
			$colSerie = $cfg["serie"];
			$colCorr = $cfg["correlativo"];
			$etiqueta = $cfg["etiqueta"];
			$partes[] = "
				SELECT
					t.id AS id_talonario,
					'{$tipo}' AS tipo_documento,
					'{$etiqueta}' AS tipo_etiqueta,
					t.{$colSerie} AS serie,
					t.{$colCorr} AS correlativo,
					(
						SELECT GROUP_CONCAT(m.marca ORDER BY m.marca SEPARATOR ', ')
						FROM serie_documento_marcajf sdm
						INNER JOIN marcasjf m ON m.id = sdm.id_marca
						WHERE sdm.id_talonario = t.id AND sdm.tipo_documento = '{$tipo}'
					) AS marcas_texto,
					(
						SELECT COUNT(*)
						FROM serie_documento_marcajf sdm
						WHERE sdm.id_talonario = t.id AND sdm.tipo_documento = '{$tipo}'
					) AS total_marcas
				FROM talonariosjf t
				WHERE t.{$colSerie} IS NOT NULL AND TRIM(t.{$colSerie}) <> ''
			";
		}

		$sql = implode(" UNION ALL ", $partes) . " ORDER BY tipo_documento ASC, serie ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlDetalleSerie($idTalonario, $tipoDocumento)
	{
		$mapa = self::mdlMapaTipos();
		if (!isset($mapa[$tipoDocumento])) {
			return null;
		}
		$colSerie = $mapa[$tipoDocumento]["serie"];
		$colCorr = $mapa[$tipoDocumento]["correlativo"];
		$etiqueta = $mapa[$tipoDocumento]["etiqueta"];

		$sql = "SELECT
					t.id AS id_talonario,
					:tipo AS tipo_documento,
					:etiqueta AS tipo_etiqueta,
					t.{$colSerie} AS serie,
					t.{$colCorr} AS correlativo
				FROM talonariosjf t
				WHERE t.id = :id
				  AND t.{$colSerie} IS NOT NULL
				  AND TRIM(t.{$colSerie}) <> ''
				LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":tipo", $tipoDocumento, PDO::PARAM_STR);
		$stmt->bindParam(":etiqueta", $etiqueta, PDO::PARAM_STR);
		$stmt->bindParam(":id", $idTalonario, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}

	static public function mdlExisteSerieTipo($tipoDocumento, $serie, $excluirId = 0)
	{
		$mapa = self::mdlMapaTipos();
		if (!isset($mapa[$tipoDocumento])) {
			return true;
		}
		$colSerie = $mapa[$tipoDocumento]["serie"];
		$sql = "SELECT id FROM talonariosjf
				WHERE {$colSerie} = :serie";
		if ($excluirId > 0) {
			$sql .= " AND id <> :id";
		}
		$sql .= " LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
		if ($excluirId > 0) {
			$stmt->bindParam(":id", $excluirId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return (bool) $stmt->fetch();
	}

	static public function mdlCrearSerieRetornandoId($tipoDocumento, $serie, $correlativo)
	{
		$mapa = self::mdlMapaTipos();
		if (!isset($mapa[$tipoDocumento])) {
			return 0;
		}
		$colSerie = $mapa[$tipoDocumento]["serie"];
		$colCorr = $mapa[$tipoDocumento]["correlativo"];

		$pdo = Conexion::conectar();
		$sql = "INSERT INTO talonariosjf ({$colSerie}, {$colCorr}) VALUES (:serie, :correlativo)";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
		$stmt->bindParam(":correlativo", $correlativo, PDO::PARAM_INT);

		if ($stmt->execute()) {
			return (int) $pdo->lastInsertId();
		}
		return 0;
	}

	static public function mdlEditarSerie($idTalonario, $tipoDocumento, $serie, $correlativo)
	{
		$mapa = self::mdlMapaTipos();
		if (!isset($mapa[$tipoDocumento])) {
			return "error";
		}
		$colSerie = $mapa[$tipoDocumento]["serie"];
		$colCorr = $mapa[$tipoDocumento]["correlativo"];

		$sql = "UPDATE talonariosjf
				SET {$colSerie} = :serie, {$colCorr} = :correlativo
				WHERE id = :id";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
		$stmt->bindParam(":correlativo", $correlativo, PDO::PARAM_INT);
		$stmt->bindParam(":id", $idTalonario, PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlListarMarcasCatalogo()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id, marca FROM marcasjf ORDER BY marca ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlListarMarcasSerie($idTalonario, $tipoDocumento)
	{
		$sql = "SELECT sdm.id, sdm.id_marca, m.marca
				FROM serie_documento_marcajf sdm
				INNER JOIN marcasjf m ON m.id = sdm.id_marca
				WHERE sdm.id_talonario = :id AND sdm.tipo_documento = :tipo
				ORDER BY m.marca ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":id", $idTalonario, PDO::PARAM_INT);
		$stmt->bindParam(":tipo", $tipoDocumento, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlListarVinculosMarcas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id_talonario, tipo_documento, id_marca
			 FROM serie_documento_marcajf"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlToggleMarcaSerie($idTalonario, $tipoDocumento, $idMarca, $activo, $usureg)
	{
		$idTalonario = (int) $idTalonario;
		$idMarca = (int) $idMarca;
		$tipoDocumento = trim((string) $tipoDocumento);
		if ($idTalonario < 1 || $idMarca < 1 || $tipoDocumento === "") {
			return "error";
		}

		$pdo = Conexion::conectar();
		if ($activo) {
			$stmt = $pdo->prepare(
				"INSERT IGNORE INTO serie_documento_marcajf (id_talonario, tipo_documento, id_marca, usureg, fecreg)
				 VALUES (?, ?, ?, ?, NOW())"
			);
			return $stmt->execute(array($idTalonario, $tipoDocumento, $idMarca, $usureg)) ? "ok" : "error";
		}

		$stmt = $pdo->prepare(
			"DELETE FROM serie_documento_marcajf
			 WHERE id_talonario = ? AND tipo_documento = ? AND id_marca = ?"
		);
		return $stmt->execute(array($idTalonario, $tipoDocumento, $idMarca)) ? "ok" : "error";
	}

	static public function mdlReemplazarMarcasSerie($idTalonario, $tipoDocumento, $idsMarcas, $usureg)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();
		try {
			$stmtDel = $pdo->prepare(
				"DELETE FROM serie_documento_marcajf
				 WHERE id_talonario = :id AND tipo_documento = :tipo"
			);
			$stmtDel->execute(array(
				":id" => $idTalonario,
				":tipo" => $tipoDocumento
			));

			if (is_array($idsMarcas) && count($idsMarcas) > 0) {
				$stmtIns = $pdo->prepare(
					"INSERT INTO serie_documento_marcajf (id_talonario, tipo_documento, id_marca, usureg, fecreg)
					 VALUES (?, ?, ?, ?, NOW())"
				);
				foreach ($idsMarcas as $idMarca) {
					$idMarca = (int) $idMarca;
					if ($idMarca < 1) {
						continue;
					}
					$stmtIns->execute(array($idTalonario, $tipoDocumento, $idMarca, $usureg));
				}
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			$pdo->rollBack();
			return "error";
		}
	}
}
