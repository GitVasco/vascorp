<?php

require_once "conexion.php";

class ModeloMpReprocesos
{
	const CONFIG_PATH = __DIR__ . "/../controladores/mp-reprocesos.config.json";
	const TABLA = "mp_reprocesojf";

	static public function mdlLeerConfig()
	{
		if (!is_file(self::CONFIG_PATH)) {
			return array("version" => "1.0", "procesos" => array());
		}
		$raw = file_get_contents(self::CONFIG_PATH);
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return array("version" => "1.0", "procesos" => array());
		}
		return $data;
	}

	static public function mdlListar()
	{
		$sql = "SELECT
				r.id,
				r.cod_pro_origen,
				IFNULL(po.CodFab, '') AS cod_fab_origen,
				IFNULL(po.DesPro, '') AS des_origen,
				IFNULL(co.Des_Larga, '') AS color_origen,
				r.proceso,
				r.cod_pro_destino,
				IFNULL(pd.CodFab, '') AS cod_fab_destino,
				IFNULL(pd.DesPro, '') AS des_destino,
				IFNULL(cd.Des_Larga, '') AS color_destino,
				IFNULL(r.observacion, '') AS observacion,
				r.estado AS activo,
				IFNULL(r.usureg, '') AS usu_reg,
				r.fecreg AS fec_reg,
				IFNULL(r.usumod, '') AS usu_mod,
				r.fecmod AS fec_mod
			FROM " . self::TABLA . " r
			LEFT JOIN producto po ON po.CodPro = r.cod_pro_origen
			LEFT JOIN tabla_m_detalle co
				ON po.ColPro = co.Cod_Argumento AND co.Cod_Tabla = 'TCOL'
			LEFT JOIN producto pd ON pd.CodPro = r.cod_pro_destino
			LEFT JOIN tabla_m_detalle cd
				ON pd.ColPro = cd.Cod_Argumento AND cd.Cod_Tabla = 'TCOL'
			WHERE r.estado = 1
			ORDER BY IFNULL(po.CodFab, r.cod_pro_origen) ASC, r.proceso ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlExisteDuplicado($codOrigen, $proceso, $codDestino, $excluirId = null)
	{
		$sql = "SELECT id FROM " . self::TABLA . "
			WHERE cod_pro_origen = :origen
				AND proceso = :proceso
				AND cod_pro_destino = :destino
				AND estado = 1";
		if ($excluirId !== null && $excluirId !== "") {
			$sql .= " AND id <> :id";
		}
		$sql .= " LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":origen", $codOrigen, PDO::PARAM_STR);
		$stmt->bindValue(":proceso", $proceso, PDO::PARAM_STR);
		$stmt->bindValue(":destino", $codDestino, PDO::PARAM_STR);
		if ($excluirId !== null && $excluirId !== "") {
			$stmt->bindValue(":id", (int) $excluirId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlObtenerPorId($id)
	{
		$sql = "SELECT id, cod_pro_origen, proceso, cod_pro_destino, observacion, estado
			FROM " . self::TABLA . "
			WHERE id = :id
			LIMIT 1";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row : null;
	}

	static public function mdlBuscarIncluyendoInactivo($codOrigen, $proceso, $codDestino)
	{
		$sql = "SELECT id, estado FROM " . self::TABLA . "
			WHERE cod_pro_origen = :origen
				AND proceso = :proceso
				AND cod_pro_destino = :destino
			LIMIT 1";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":origen", $codOrigen, PDO::PARAM_STR);
		$stmt->bindValue(":proceso", $proceso, PDO::PARAM_STR);
		$stmt->bindValue(":destino", $codDestino, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row : null;
	}

	static public function mdlReactivar($datos)
	{
		$sql = "UPDATE " . self::TABLA . "
			SET observacion = :obs,
				estado = 1,
				usumod = :usumod,
				fecmod = :fecmod
			WHERE id = :id";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":obs", $datos["observacion"], PDO::PARAM_STR);
		$stmt->bindValue(":usumod", $datos["usu_mod"], PDO::PARAM_STR);
		$stmt->bindValue(":fecmod", $datos["fec_mod"], PDO::PARAM_STR);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);

		return $stmt->execute();
	}

	static public function mdlInsertar($datos)
	{
		$db = Conexion::conectar();
		$sql = "INSERT INTO " . self::TABLA . "
			(cod_pro_origen, proceso, cod_pro_destino, observacion, estado, usureg, fecreg, usumod, fecmod)
			VALUES
			(:origen, :proceso, :destino, :obs, 1, :usureg, :fecreg, :usumod, :fecmod)";

		$stmt = $db->prepare($sql);
		$stmt->bindValue(":origen", $datos["cod_pro_origen"], PDO::PARAM_STR);
		$stmt->bindValue(":proceso", $datos["proceso"], PDO::PARAM_STR);
		$stmt->bindValue(":destino", $datos["cod_pro_destino"], PDO::PARAM_STR);
		$stmt->bindValue(":obs", $datos["observacion"], PDO::PARAM_STR);
		$stmt->bindValue(":usureg", $datos["usu_reg"], PDO::PARAM_STR);
		$stmt->bindValue(":fecreg", $datos["fec_reg"], PDO::PARAM_STR);
		$stmt->bindValue(":usumod", $datos["usu_mod"], PDO::PARAM_STR);
		$stmt->bindValue(":fecmod", $datos["fec_mod"], PDO::PARAM_STR);

		if (!$stmt->execute()) {
			return false;
		}
		return (int) $db->lastInsertId();
	}

	static public function mdlActualizar($datos)
	{
		$sql = "UPDATE " . self::TABLA . "
			SET cod_pro_origen = :origen,
				proceso = :proceso,
				cod_pro_destino = :destino,
				observacion = :obs,
				usumod = :usumod,
				fecmod = :fecmod
			WHERE id = :id AND estado = 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":origen", $datos["cod_pro_origen"], PDO::PARAM_STR);
		$stmt->bindValue(":proceso", $datos["proceso"], PDO::PARAM_STR);
		$stmt->bindValue(":destino", $datos["cod_pro_destino"], PDO::PARAM_STR);
		$stmt->bindValue(":obs", $datos["observacion"], PDO::PARAM_STR);
		$stmt->bindValue(":usumod", $datos["usu_mod"], PDO::PARAM_STR);
		$stmt->bindValue(":fecmod", $datos["fec_mod"], PDO::PARAM_STR);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);

		return $stmt->execute();
	}

	static public function mdlEliminar($id, $usuario, $fecmod)
	{
		$sql = "UPDATE " . self::TABLA . "
			SET estado = 0,
				usumod = :usumod,
				fecmod = :fecmod
			WHERE id = :id AND estado = 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":usumod", $usuario, PDO::PARAM_STR);
		$stmt->bindValue(":fecmod", $fecmod, PDO::PARAM_STR);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);

		return $stmt->execute() && $stmt->rowCount() > 0;
	}

	static public function mdlBuscarMp($termino)
	{
		$termino = trim((string) $termino);
		if ($termino === "") {
			return null;
		}

		$sql = "SELECT
				p.CodPro AS codpro,
				p.CodFab AS codfab,
				p.DesPro AS despro,
				IFNULL(tbcol.Des_Larga, '') AS color,
				IFNULL(tbund.Des_Corta, '') AS unidad
			FROM producto p
			LEFT JOIN tabla_m_detalle tbcol
				ON p.ColPro = tbcol.Cod_Argumento AND tbcol.Cod_Tabla = 'TCOL'
			LEFT JOIN tabla_m_detalle tbund
				ON p.UndPro = tbund.Cod_Argumento AND tbund.Cod_Tabla = 'TUND'
			WHERE p.EstPro = '1'
				AND (p.CodPro = :exacto OR p.CodFab = :exacto2)
			LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":exacto", $termino, PDO::PARAM_STR);
		$stmt->bindParam(":exacto2", $termino, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row) {
			return $row;
		}

		$like = "%" . $termino . "%";
		$sql2 = "SELECT
				p.CodPro AS codpro,
				p.CodFab AS codfab,
				p.DesPro AS despro,
				IFNULL(tbcol.Des_Larga, '') AS color,
				IFNULL(tbund.Des_Corta, '') AS unidad
			FROM producto p
			LEFT JOIN tabla_m_detalle tbcol
				ON p.ColPro = tbcol.Cod_Argumento AND tbcol.Cod_Tabla = 'TCOL'
			LEFT JOIN tabla_m_detalle tbund
				ON p.UndPro = tbund.Cod_Argumento AND tbund.Cod_Tabla = 'TUND'
			WHERE p.EstPro = '1'
				AND (p.CodPro LIKE :like1 OR p.CodFab LIKE :like2 OR p.DesPro LIKE :like3)
			ORDER BY p.CodFab
			LIMIT 15";

		$stmt2 = Conexion::conectar()->prepare($sql2);
		$stmt2->bindParam(":like1", $like, PDO::PARAM_STR);
		$stmt2->bindParam(":like2", $like, PDO::PARAM_STR);
		$stmt2->bindParam(":like3", $like, PDO::PARAM_STR);
		$stmt2->execute();
		return $stmt2->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlObtenerMpPorCodPro($codPro)
	{
		$codPro = trim((string) $codPro);
		if ($codPro === "") {
			return null;
		}

		$sql = "SELECT
				p.CodPro AS codpro,
				p.CodFab AS codfab,
				p.DesPro AS despro,
				IFNULL(tbcol.Des_Larga, '') AS color,
				IFNULL(tbund.Des_Corta, '') AS unidad
			FROM producto p
			LEFT JOIN tabla_m_detalle tbcol
				ON p.ColPro = tbcol.Cod_Argumento AND tbcol.Cod_Tabla = 'TCOL'
			LEFT JOIN tabla_m_detalle tbund
				ON p.UndPro = tbund.Cod_Argumento AND tbund.Cod_Tabla = 'TUND'
			WHERE p.EstPro = '1' AND p.CodPro = :codpro
			LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":codpro", $codPro, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row : null;
	}
}
