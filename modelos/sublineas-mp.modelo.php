<?php

require_once "conexion.php";

class ModeloSublineasMp
{
	static public function mdlListarLineas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(t.Des_Corta) AS codigo,
				TRIM(t.Cod_Argumento) AS argumento,
				TRIM(t.Des_Larga) AS nombre
			FROM Tabla_M_Detalle t
			WHERE t.Cod_Tabla = 'TLIN'
			  AND t.Cod_Argumento NOT LIKE '000'
			  AND TRIM(IFNULL(t.Des_Corta, '')) <> ''
			ORDER BY t.Des_Corta ASC, t.Cod_Argumento ASC"
		);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;
		return $filas;
	}

	static public function mdlListarSublineas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(t.Cod_Argumento) AS cod_argumento,
				TRIM(t.Des_Corta) AS linea,
				TRIM(t.Valor_3) AS subcodigo,
				CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3)) AS codigo_sublinea,
				TRIM(t.Des_Larga) AS nombre,
				TRIM(IFNULL(t.Valor_1, '')) AS valor_1,
				TRIM(IFNULL(t.Valor_2, '')) AS valor_2,
				TRIM(IFNULL(t.Valor_4, '')) AS valor_4,
				TRIM(IFNULL(t.Valor_5, '')) AS valor_5,
				TRIM(IFNULL(t.Estado, '1')) AS estado,
				TRIM(IFNULL(lin.Des_Larga, '')) AS nombre_linea,
				TRIM(IFNULL(lin.Cod_Argumento, '')) AS linea_arg,
				IFNULL(p.mp_activas, 0) AS mp_activas
			FROM Tabla_M_Detalle t
			LEFT JOIN (
				SELECT
					TRIM(Des_Corta) AS Des_Corta,
					MAX(Des_Larga) AS Des_Larga,
					MAX(Cod_Argumento) AS Cod_Argumento
				FROM Tabla_M_Detalle
				WHERE Cod_Tabla = 'TLIN'
				GROUP BY TRIM(Des_Corta)
			) lin ON lin.Des_Corta = TRIM(t.Des_Corta)
			LEFT JOIN (
				SELECT
					TRIM(FamPro) AS FamPro,
					COUNT(*) AS mp_activas
				FROM Producto
				WHERE EstPro = '1'
				  AND TRIM(IFNULL(FamPro, '')) <> ''
				GROUP BY TRIM(FamPro)
			) p ON p.FamPro = CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3))
			WHERE t.Cod_Tabla = 'TSUB'
			ORDER BY t.Des_Corta ASC, t.Valor_3 ASC, t.Cod_Argumento ASC"
		);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;
		return $filas;
	}

	static public function mdlMostrar($codArgumento)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(t.Cod_Argumento) AS cod_argumento,
				TRIM(t.Des_Corta) AS linea,
				TRIM(t.Valor_3) AS subcodigo,
				CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3)) AS codigo_sublinea,
				TRIM(t.Des_Larga) AS nombre,
				TRIM(IFNULL(t.Valor_1, '')) AS valor_1,
				TRIM(IFNULL(t.Valor_2, '')) AS valor_2,
				TRIM(IFNULL(t.Valor_4, '')) AS valor_4,
				TRIM(IFNULL(t.Valor_5, '')) AS valor_5
			FROM Tabla_M_Detalle t
			WHERE t.Cod_Tabla = 'TSUB'
			  AND t.Cod_Argumento = :arg
			LIMIT 1"
		);
		$stmt->bindParam(":arg", $codArgumento, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;
		return $fila ? $fila : false;
	}

	static public function mdlMostrarPorCodigo($linea, $subcodigo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(t.Cod_Argumento) AS cod_argumento,
				TRIM(t.Des_Corta) AS linea,
				TRIM(t.Valor_3) AS subcodigo,
				CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3)) AS codigo_sublinea,
				TRIM(t.Des_Larga) AS nombre
			FROM Tabla_M_Detalle t
			WHERE t.Cod_Tabla = 'TSUB'
			  AND TRIM(t.Des_Corta) = :linea
			  AND TRIM(t.Valor_3) = :sub
			LIMIT 1"
		);
		$stmt->bindParam(":linea", $linea, PDO::PARAM_STR);
		$stmt->bindParam(":sub", $subcodigo, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;
		return $fila ? $fila : false;
	}

	static public function mdlLineaExiste($desCorta)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(Des_Corta) AS codigo, TRIM(Des_Larga) AS nombre
			FROM Tabla_M_Detalle
			WHERE Cod_Tabla = 'TLIN'
			  AND TRIM(Des_Corta) = :linea
			  AND Cod_Argumento NOT LIKE '000'
			LIMIT 1"
		);
		$stmt->bindParam(":linea", $desCorta, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;
		return $fila ? $fila : false;
	}

	static public function mdlSiguienteArgumento()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				tm.lon_campo,
				LPAD(
					MAX(CAST(td.Cod_Argumento AS SIGNED)) + 1,
					tm.lon_campo,
					'0'
				) AS correlativo
			FROM Tabla_M_Maestra tm
			LEFT JOIN Tabla_M_Detalle td
				ON tm.Cod_Tabla = td.Cod_Tabla
			WHERE tm.Cod_Tabla = 'TSUB'"
		);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;

		$lon = isset($fila["lon_campo"]) ? (int) $fila["lon_campo"] : 6;
		if ($lon < 1) {
			$lon = 6;
		}
		$corr = isset($fila["correlativo"]) ? trim((string) $fila["correlativo"]) : "";
		if ($corr === "") {
			$corr = str_pad("1", $lon, "0", STR_PAD_LEFT);
		}
		return array("correlativo" => $corr, "lon_campo" => $lon);
	}

	static public function mdlSiguienteSubcodigo($desCorta)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT LPAD(MAX(Valor_3) + 1, 3, '0') AS correlativo
			FROM Tabla_M_Detalle
			WHERE Cod_Tabla = 'TSUB'
			  AND TRIM(Des_Corta) = :linea"
		);
		$stmt->bindParam(":linea", $desCorta, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;

		$corr = ($fila && isset($fila["correlativo"])) ? trim((string) $fila["correlativo"]) : "";
		if ($corr === "") {
			$corr = "001";
		}
		return $corr;
	}

	static public function mdlExisteCodigo($desCorta, $subcodigo, $excluirArg = "")
	{
		$sql = "SELECT Cod_Argumento
			FROM Tabla_M_Detalle
			WHERE Cod_Tabla = 'TSUB'
			  AND TRIM(Des_Corta) = :linea
			  AND TRIM(Valor_3) = :sub";
		if ($excluirArg !== "") {
			$sql .= " AND Cod_Argumento <> :arg";
		}
		$sql .= " LIMIT 1";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":linea", $desCorta, PDO::PARAM_STR);
		$stmt->bindParam(":sub", $subcodigo, PDO::PARAM_STR);
		if ($excluirArg !== "") {
			$stmt->bindParam(":arg", $excluirArg, PDO::PARAM_STR);
		}
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;
		return $fila ? true : false;
	}

	static public function mdlCrear($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO tabla_m_detalle (
				cod_argumento,
				cod_local,
				cod_entidad,
				cod_tabla,
				des_larga,
				des_corta,
				valor_1,
				valor_2,
				valor_3,
				valor_4,
				valor_5,
				estado,
				fecreg,
				usureg,
				pcreg
			) VALUES (
				:cod_argumento,
				:cod_local,
				:cod_entidad,
				'TSUB',
				UPPER(:des_larga),
				UPPER(:des_corta),
				:valor_1,
				:valor_2,
				:valor_3,
				:valor_4,
				:valor_5,
				'1',
				:fecreg,
				UPPER(:usureg),
				UPPER(:pcreg)
			)"
		);

		$stmt->bindParam(":cod_argumento", $datos["cod_argumento"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":des_larga", $datos["des_larga"], PDO::PARAM_STR);
		$stmt->bindParam(":des_corta", $datos["des_corta"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_1", $datos["valor_1"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_2", $datos["valor_2"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_3", $datos["valor_3"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_4", $datos["valor_4"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_5", $datos["valor_5"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

		$ok = $stmt->execute();
		$stmt = null;
		return $ok ? "ok" : "error";
	}

	static public function mdlEditar($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE tabla_m_detalle
			SET
				des_larga = UPPER(:des_larga),
				valor_1 = :valor_1,
				valor_2 = :valor_2,
				valor_4 = :valor_4,
				valor_5 = :valor_5,
				fecmod = :fecmod,
				usumod = :usumod,
				pcmod = :pcmod
			WHERE cod_tabla = 'TSUB'
			  AND cod_argumento = :cod_argumento"
		);

		$stmt->bindParam(":cod_argumento", $datos["cod_argumento"], PDO::PARAM_STR);
		$stmt->bindParam(":des_larga", $datos["des_larga"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_1", $datos["valor_1"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_2", $datos["valor_2"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_4", $datos["valor_4"], PDO::PARAM_STR);
		$stmt->bindParam(":valor_5", $datos["valor_5"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
		$stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);

		$ok = $stmt->execute();
		$stmt = null;
		return $ok ? "ok" : "error";
	}

	static public function mdlListarMpPorSublinea($codigoSublinea)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(p.CodPro) AS codpro,
				TRIM(p.CodFab) AS codfab,
				TRIM(p.DesPro) AS despro,
				TRIM(IFNULL(col.Des_Larga, '')) AS color,
				TRIM(IFNULL(tal.Des_Larga, '')) AS talla,
				TRIM(IFNULL(und.Des_Corta, '')) AS unidad,
				IFNULL(p.CodAlm01, 0) AS stock,
				TRIM(IFNULL(p.EstPro, '')) AS estado,
				IFNULL(oc.n_oc, 0) AS n_oc,
				IFNULL(os.n_os, 0) AS n_os
			FROM Producto p
			LEFT JOIN Tabla_M_Detalle col
				ON col.Cod_Tabla = 'TCOL' AND col.Cod_Argumento = p.ColPro
			LEFT JOIN Tabla_M_Detalle tal
				ON tal.Cod_Tabla = 'TTAL' AND tal.Cod_Argumento = p.TalPro
			LEFT JOIN Tabla_M_Detalle und
				ON und.Cod_Tabla = 'TUND' AND und.Cod_Argumento = p.UndPro
			LEFT JOIN (
				SELECT ocd.codpro AS codpro, COUNT(*) AS n_oc
				FROM ocomdet ocd
				INNER JOIN ocompra oc ON oc.nro = ocd.nro
				WHERE ocd.estac IN ('ABI', 'PAR')
				  AND ocd.estoco = '03'
				GROUP BY ocd.codpro
			) oc ON oc.codpro = p.CodPro
			LEFT JOIN (
				SELECT x.codpro, COUNT(*) AS n_os
				FROM (
					SELECT osd.CodProOrigen AS codpro
					FROM oserviciodet osd
					INNER JOIN oservicio os ON os.Nro = osd.Nro
					WHERE osd.EstReg = '1' AND osd.EstOS IN ('ABI', 'PAR')
					UNION ALL
					SELECT osd.CodProDestino AS codpro
					FROM oserviciodet osd
					INNER JOIN oservicio os ON os.Nro = osd.Nro
					WHERE osd.EstReg = '1' AND osd.EstOS IN ('ABI', 'PAR')
				) x
				GROUP BY x.codpro
			) os ON os.codpro = p.CodPro
			WHERE p.EstPro = '1'
			  AND (
				TRIM(IFNULL(p.FamPro, '')) = :cod
				OR LEFT(TRIM(IFNULL(p.CodFab, '')), CHAR_LENGTH(:cod2)) = :cod3
			  )
			ORDER BY p.CodFab ASC, p.CodPro ASC"
		);
		$stmt->bindParam(":cod", $codigoSublinea, PDO::PARAM_STR);
		$stmt->bindParam(":cod2", $codigoSublinea, PDO::PARAM_STR);
		$stmt->bindParam(":cod3", $codigoSublinea, PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;
		return $filas;
	}
}
