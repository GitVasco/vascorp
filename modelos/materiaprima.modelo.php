<?php

require_once "conexion.php";

class ModeloMateriaPrima
{

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	* Query directo (reemplaza sp_1028_consulta_mp_p): línea TLIN por prefijo más largo.
	*/
	static public function mdlMostrarMateriaPrima($valor)
	{

		if ($valor != null) {

			$sql = "SELECT DISTINCT
				p.codpro,
				tblin.des_corta AS CodLin,
				tblin.des_larga AS linea,
				p.FamPro AS codlinea,
				p.codfab,
				p.despro AS despro,
				p.UndPro,
				p.PesPro,
				p.CodAlt,
				p.FecReg,
				p.UsuReg,
				p.ColPro,
				p.TalPro,
				p.CodAlm01,
				p.Stk_Act,
				p.Stk_Min,
				p.Stk_Max,
				p.Por_Seg,
				p.Por_Adval,
				p.FamPro,
				pmp.CodProv1,
				pmp.PreProv1,
				pmp.MonProv1,
				pmp.ObsProv1,
				pmp.CodProv2,
				pmp.PreProv2,
				pmp.MonProv2,
				pmp.ObsProv2,
				pmp.CodProv3,
				pmp.PreProv3,
				pmp.MonProv3,
				pmp.ObsProv3,
				CONCAT(
					p.FamPro,
					' - ',
					p.DesPro,
					' - ',
					tbcol.des_larga,
					' - ',
					tbund.des_corta
				) AS descripcion,
				p.codalm01 AS stock,
				tbund.des_corta AS unidad,
				tbcol.des_larga AS color,
				p.cospro,
				IFNULL(pmp.proveedores, 'Pendiente') AS proveedor,
				IFNULL(pmp.precio, 0.000000) AS precio
			FROM producto p
			LEFT JOIN (
				SELECT
					codpro,
					CONCAT_WS('   -   ', prov1.razpro) AS proveedores,
					pmp.CodProv1,
					pmp.PreProv1,
					pmp.MonProv1,
					pmp.ObsProv1,
					pmp.CodProv2,
					pmp.PreProv2,
					pmp.MonProv2,
					pmp.ObsProv2,
					pmp.CodProv3,
					pmp.PreProv3,
					pmp.MonProv3,
					pmp.ObsProv3,
					GREATEST(PreProv1, PreProv2, PreProv3) AS precio
				FROM preciomp pmp
				LEFT JOIN proveedor prov1 ON pmp.codprov1 = prov1.codruc
				GROUP BY pmp.codpro
			) AS pmp ON pmp.codpro = p.codpro
			INNER JOIN tabla_m_detalle AS tbund
				ON p.undpro = tbund.cod_argumento
				AND tbund.Cod_Tabla = 'TUND'
			INNER JOIN tabla_m_detalle AS tbcol
				ON p.ColPro = tbcol.cod_argumento
				AND tbcol.Cod_Tabla = 'TCOL'
			LEFT JOIN tabla_m_detalle AS tblin
				ON tblin.Cod_Tabla = 'TLIN'
				AND CHAR_LENGTH(TRIM(tblin.des_corta)) > 0
				AND LEFT(p.codfab, CHAR_LENGTH(TRIM(tblin.des_corta))) = TRIM(tblin.des_corta)
				AND CHAR_LENGTH(TRIM(tblin.des_corta)) = (
					SELECT MAX(CHAR_LENGTH(TRIM(t2.des_corta)))
					FROM tabla_m_detalle t2
					WHERE t2.Cod_Tabla = 'TLIN'
						AND CHAR_LENGTH(TRIM(t2.des_corta)) > 0
						AND LEFT(p.codfab, CHAR_LENGTH(TRIM(t2.des_corta))) = TRIM(t2.des_corta)
				)
			WHERE p.codpro = :valor
				AND p.estpro = '1'";

			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetch(PDO::FETCH_ASSOC);
		} else {

			$stmt = Conexion::conectar()->prepare("CALL sp_1029_consulta_mp()");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlNormalizarSublineaFiltro($valor)
	{
		$valor = strtoupper(trim((string) $valor));
		$valor = preg_replace('/[^A-Z0-9]/', '', $valor);
		if (!is_string($valor) || $valor === "") {
			return "";
		}
		return substr($valor, 0, 6);
	}

	static public function mdlMostrarMateriaPrima2($valor, $sublinea = "")
	{

		if ($valor != null) {

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
			p.codpro,
			SUBSTRING(p.codfab, 1, 6) AS codlinea,
			p.codfab,
			p.ColPro,
			p.despro AS despro,
			CONCAT(
			  (SUBSTRING(p.CodFab, 1, 6)),
			  ' - ',
			  p.DesPro,
			  ' - ',
			  tbcol.des_larga,
			  ' - ',
			  tbund.des_corta
			) AS descripcion,
			p.codalm01 AS stock,
			tbund.des_corta AS unidad,
			tbcol.des_larga AS color,
			p.cospro,
			IFNULL(pmp.proveedores, 'Pendiente') AS proveedor,
			IFNULL( pmp.precio , 0.000000) AS precio  
		  FROM
			producto p 
			LEFT JOIN 
			  (SELECT 
				codpro,
				CONCAT_WS('   -   ', prov1.razpro) AS proveedores,
				GREATEST(PreProv1, PreProv2, PreProv3) AS precio  
			  FROM
				preciomp pmp 
				LEFT JOIN proveedor prov1 
				  ON pmp.codprov1 = prov1.codruc 
			  GROUP BY pmp.codpro) AS pmp 
			  ON pmp.codpro = p.codpro 
			INNER JOIN tabla_m_detalle AS tbund 
			  ON p.undpro = tbund.cod_argumento 
			  AND (tbund.Cod_Tabla = 'TUND') 
			INNER JOIN tabla_m_detalle AS tbcol 
			  ON p.ColPro = tbcol.cod_argumento 
			  AND (tbcol.Cod_Tabla = 'TCOL') 
		  WHERE p.estpro = '1' 
		  AND p.CodPro = :codpro");
			$stmt->bindParam(":codpro", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$sublinea = self::mdlNormalizarSublineaFiltro($sublinea);
			$filtroSub = "";
			if ($sublinea !== "") {
				$filtroSub = " WHERE (TRIM(IFNULL(pro.FamPro, '')) = :sublinea OR LEFT(TRIM(pro.CodFab), 6) = :sublinea) ";
			}

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
					'MP' AS Mp,
					pro.CodAlt,
					pro.CodFab,
					pro.DesPro,
					pro.CodPro,
					pro.CodAlm01,
					pro.Stk_Min,
					pro.Stk_Max,
					TbUnd.Des_Corta AS Unidad,
					TbCol.Cod_Argumento AS CodigoColor,
					TbCol.Des_Larga AS Color,
					pro.TalPro,
					TbTal.Des_larga AS Talla,
					pmp.Proveedores,
					IFNULL(pmp.precio, 0.000000) AS precio,
					CASE
					WHEN pro.estpro = 1 THEN 'Activo'
					ELSE 'Inactivo'END AS estpro
				FROM
					Producto pro 
					LEFT JOIN 
					(SELECT DISTINCT 
						p.codpro,
						CONCAT_WS(
						' - ',
						IFNULL(p1.razpro, ''),
						IFNULL(p2.razpro, ''),
						IFNULL(p3.razpro, '')
						) AS proveedores,
						GREATEST(
						p.preprov1,
						p.preprov2,
						p.preprov2
						) AS precio 
					FROM
						preciomp p 
						LEFT JOIN 
						(SELECT DISTINCT 
							codruc,
							razpro 
						FROM
							proveedor prov) AS p1 
						ON p.codprov1 = p1.codruc 
						LEFT JOIN 
						(SELECT DISTINCT 
							codruc,
							razpro 
						FROM
							proveedor prov) AS p2 
						ON p.codprov2 = p2.codruc 
						LEFT JOIN 
						(SELECT DISTINCT 
							codruc,
							razpro 
						FROM
							proveedor prov) AS p3 
						ON p.codprov3 = p3.codruc) AS pmp 
					ON pmp.CodPro = pro.CodPro 
					INNER JOIN Tabla_M_Detalle AS TbUnd 
					ON pro.UndPro = TbUnd.Cod_Argumento 
					AND (TbUnd.Cod_Tabla = 'TUND') 
					INNER JOIN Tabla_M_Detalle AS TbCol 
					ON pro.ColPro = TbCol.Cod_Argumento 
					AND (TbCol.Cod_Tabla = 'TCOL') 
					INNER JOIN Tabla_M_Detalle AS TbTal 
					ON pro.TalPro = TbTal.Cod_Argumento 
					AND (TbTal.Cod_Tabla = 'TTAL') 
				$filtroSub
				GROUP BY pro.CodPro 
				ORDER BY pro.CodPro DESC
				");

			if ($sublinea !== "") {
				$stmt->bindValue(":sublinea", $sublinea, PDO::PARAM_STR);
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlMostrarMateriaPrima3()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
		CodPro AS codpro,
		CodFab AS codfab,
		DesPro AS despro,
		Stk_Act,
		CodAlm01 AS stock,
		Stk_Min,
		Stk_Max,
		CosPro,
		TbUnd.Des_Corta AS unidad,
		TbCol.Des_Larga AS color 
	  FROM
		Producto AS Pro 
		INNER JOIN Tabla_M_Detalle AS TbUnd 
		  ON Pro.UndPro = TbUnd.Cod_Argumento 
		  AND (TbUnd.Cod_Tabla = 'TUND') 
		INNER JOIN Tabla_M_Detalle AS TbCol 
		  ON Pro.ColPro = TbCol.Cod_Argumento 
		  AND (TbCol.Cod_Tabla = 'TCOL') 
	  WHERE Pro.EstPro = '1'
	  ORDER BY pro.codfab ");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/* 
	* Método para vizualizar detalle de la materia prima
	*/
	static public function mdlVisualizarMateriaPrimaDetalle($valor)
	{

		$sql = "CALL sp_1031_articulos_x_mp_p($valor)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/* 
	* EDITAR COSTO DE LA MATERIA PRIMA
	*/
	static public function mdlEditarMateriaPrimaCosto($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1032_update_mp_costo_p(:cospro,:valor)");

		$stmt->bindParam(":cospro", $datos["cospro"], PDO::PARAM_STR);
		$stmt->bindParam(":valor", $datos["codpro"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR MATERIA PRIMA PARA LA TABLA URGENCIA
	*/
	static public function mdlMostrarUrgenciaAMP($valor)
	{

		if ($valor == null) {

			$stmt = Conexion::conectar()->prepare("CALL sp_1033_mp_urgencias()");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			return self::mdlMostrarMateriaPrima($valor);
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR EL DETALLE DE LAS URGENCIAS TABLA ORDEN DE COMPRA
	*/
	static public function mdlVisualizarUrgenciasAMPDetalleOC($valor)
	{

		$sql = "CALL sp_1034_mp_en_oc_p($valor)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/* 
	* MOSTRAR EL DETALLE DE LAS URGENCIAS TABLA ORDEN DE COMPRA
	*/
	static public function mdlVisualizarUrgenciasAMPDetalleART($valor)
	{

		$sql = "CALL sp_1035_art_mp_urg_p($valor)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	// Método para mostrar el Rango de Fechas de Ventas
	static public function mdlProyMp($mp)
	{

		if ($mp == "null") {

			$sql = "SELECT 
						mp.linea,
						mp.codsublinea,
						mp.codpro,
						mp.codfab,
						mp.descripcion,
						mp.color,
						mp.unidad,
						mp.stock,
						SUM(doc.saldo * dt.consumo) AS requerimiento,
						IFNULL(oc.saldo, 0) AS saldo_oc,
						IFNULL(os.saldo, 0) AS saldo_os,
						IFNULL(pr.cons_total, 0) AS cons_total,
						IFNULL(i.ing,0) AS ingresos,
						IFNULL(
						(
						IFNULL(i.ing, 0) / IFNULL(pr.cons_total, 0)
						) * 100,
						0
						) AS avance  
					FROM
						ordencortejf o 
						LEFT JOIN detalles_ordencortejf doc 
						ON o.codigo = doc.ordencorte 
						LEFT JOIN detalles_tarjetajf dt 
						ON doc.articulo = dt.articulo 
						LEFT JOIN 
						(SELECT DISTINCT 
							p.Codpro AS codpro,
							SUBSTRING(p.CodFab, 1, 3) AS codlinea,
							Tb4.Des_larga AS linea,
							SUBSTRING(p.CodFab, 1, 6) AS codsublinea,
							Tb1.Des_larga AS sublinea,
							p.CodFab AS codfab,
							p.DesPro AS descripcion,
							p.CodAlm01 AS stock,
							Tabla_M_Detalle.Des_Larga AS color,
							Tb2.Des_Corta AS unidad 
						FROM
							producto p,
							Tabla_M_Detalle,
							Tabla_M_Detalle AS Tb1,
							Tabla_M_Detalle AS Tb2,
							Tabla_M_Detalle AS Tb4 
						WHERE Tabla_M_Detalle.Cod_Tabla IN ('TCOL') 
							AND Tb2.Cod_Tabla IN ('TUND') 
							AND tB4.Cod_Tabla IN ('TLIN') 
							AND Tb1.Cod_Tabla IN ('TSUB') 
							AND Tabla_M_Detalle.Cod_Argumento = p.ColPro 
							AND Tb2.Cod_Argumento = p.UndPro 
							AND LEFT(p.CodFab, 3) = Tb4.Des_Corta 
							AND SUBSTRING(p.CodFab, 4, 3) = Tb1.Valor_3 
							AND Tb4.Des_Corta = Tb1.Des_Corta 
						ORDER BY p.CodPro ASC) AS mp 
						ON dt.mat_pri = mp.codpro 
						LEFT JOIN 
						(SELECT 
							ocd.codpro,
							ocd.nro,
							DATE(oc.fecemi) AS emision,
							DATE(oc.fecllegada) AS llegada,
							p.razpro,
							ocd.canpro AS cantidad_pedida,
							ocd.cantni AS saldo,
							oc.estac 
						FROM
							ocomdet ocd 
							LEFT JOIN ocompra oc 
							ON ocd.nro = oc.nro 
							LEFT JOIN proveedor p 
							ON oc.codruc = p.codruc 
						WHERE oc.estac IN ('ABI', 'PAR') 
							AND ocd.estac IN ('ABI', 'PAR') 
							AND oc.estoco = '03' 
							AND ocd.estoco = '03' 
							AND YEAR(oc.fecemi) = YEAR(NOW())) AS oc 
						ON dt.mat_pri = oc.codpro 
						LEFT JOIN 
						(SELECT 
							osd.CodProOrigen,
							osd.CodProDestino AS codpro,
							osd.Saldo 
						FROM
							oserviciodet osd 
							LEFT JOIN oservicio os 
							ON os.Nro = osd.Nro 
						WHERE osd.EstReg = '1' 
							AND osd.EstOS IN ('ABI', 'PAR') 
							AND YEAR(os.fecent) = YEAR(NOW())) AS os 
						ON dt.mat_pri = os.codpro 
						LEFT JOIN 
						(SELECT 
							dt.mat_pri,
							dt.consumo,
							a.proyeccion,
							SUM(dt.consumo * a.proyeccion) AS cons_total 
						FROM
							detalles_tarjetajf dt 
							LEFT JOIN articulojf a 
							ON dt.articulo = a.articulo 
						WHERE a.proyeccion > 0 
						GROUP BY dt.mat_pri) AS pr 
						ON dt.mat_pri = pr.mat_pri 
						LEFT JOIN 
						(SELECT 
							nd.codpro,
							SUM(nd.cansol) AS ing 
						FROM
							neadet nd 
						WHERE nd.fecemi > '2020-07-31' 
						GROUP BY nd.codpro) AS i 
						ON dt.mat_pri = i.codpro 
					WHERE o.estado NOT IN ('Cerrado') 
					GROUP BY mp.codpro 
					ORDER BY mp.linea";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			# Retornamos un fetchAll por ser más de una línea la que necesitamos devolver
			return $stmt->fetchAll();
		} else {

			$sql = "SELECT 
			mp.linea,
			mp.codsublinea,
			mp.codpro,
			mp.codfab,
			mp.descripcion,
			mp.color,
			mp.unidad,
			mp.stock,
			SUM(doc.saldo * dt.consumo) AS requerimiento,
			IFNULL(oc.saldo, 0) AS saldo_oc,
			IFNULL(os.saldo, 0) AS saldo_os,
			IFNULL(pr.cons_total, 0) AS cons_total,
			IFNULL(i.ing, 0) AS ingresos,
			IFNULL(
			  (
				IFNULL(i.ing, 0) / IFNULL(pr.cons_total, 0)
			  ) * 100,
			  0
			) AS avance 
		  FROM
			ordencortejf o 
			LEFT JOIN detalles_ordencortejf doc 
			  ON o.codigo = doc.ordencorte 
			LEFT JOIN detalles_tarjetajf dt 
			  ON doc.articulo = dt.articulo 
			LEFT JOIN 
			  (SELECT DISTINCT 
				p.Codpro AS codpro,
				SUBSTRING(p.CodFab, 1, 3) AS codlinea,
				Tb4.Des_larga AS linea,
				SUBSTRING(p.CodFab, 1, 6) AS codsublinea,
				Tb1.Des_larga AS sublinea,
				p.CodFab AS codfab,
				p.DesPro AS descripcion,
				p.CodAlm01 AS stock,
				Tabla_M_Detalle.Des_Larga AS color,
				Tb2.Des_Corta AS unidad 
			  FROM
				producto p,
				Tabla_M_Detalle,
				Tabla_M_Detalle AS Tb1,
				Tabla_M_Detalle AS Tb2,
				Tabla_M_Detalle AS Tb4 
			  WHERE Tabla_M_Detalle.Cod_Tabla IN ('TCOL') 
				AND Tb2.Cod_Tabla IN ('TUND') 
				AND tB4.Cod_Tabla IN ('TLIN') 
				AND Tb1.Cod_Tabla IN ('TSUB') 
				AND Tabla_M_Detalle.Cod_Argumento = p.ColPro 
				AND Tb2.Cod_Argumento = p.UndPro 
				AND LEFT(p.CodFab, 3) = Tb4.Des_Corta 
				AND SUBSTRING(p.CodFab, 4, 3) = Tb1.Valor_3 
				AND Tb4.Des_Corta = Tb1.Des_Corta 
			  ORDER BY p.CodPro ASC) AS mp 
			  ON dt.mat_pri = mp.codpro 
			LEFT JOIN 
			  (SELECT 
				ocd.codpro,
				ocd.nro,
				DATE(oc.fecemi) AS emision,
				DATE(oc.fecllegada) AS llegada,
				p.razpro,
				ocd.canpro AS cantidad_pedida,
				ocd.cantni AS saldo,
				oc.estac 
			  FROM
				ocomdet ocd 
				LEFT JOIN ocompra oc 
				  ON ocd.nro = oc.nro 
				LEFT JOIN proveedor p 
				  ON oc.codruc = p.codruc 
			  WHERE oc.estac IN ('ABI', 'PAR') 
				AND ocd.estac IN ('ABI', 'PAR') 
				AND oc.estoco = '03' 
				AND ocd.estoco = '03' 
				AND YEAR(oc.fecemi) = YEAR(NOW())) AS oc 
			  ON dt.mat_pri = oc.codpro 
			LEFT JOIN 
			  (SELECT 
				osd.CodProOrigen,
				osd.CodProDestino AS codpro,
				osd.Saldo 
			  FROM
				oserviciodet osd 
				LEFT JOIN oservicio os 
				  ON os.Nro = osd.Nro 
			  WHERE osd.EstReg = '1' 
				AND osd.EstOS IN ('ABI', 'PAR') 
				AND YEAR(os.fecent) = YEAR(NOW())) AS os 
			  ON dt.mat_pri = os.codpro 
			LEFT JOIN 
			  (SELECT 
				dt.mat_pri,
				dt.consumo,
				a.proyeccion,
				SUM(dt.consumo * a.proyeccion) AS cons_total 
			  FROM
				detalles_tarjetajf dt 
				LEFT JOIN articulojf a 
				  ON dt.articulo = a.articulo 
			  WHERE a.proyeccion > 0 
			  GROUP BY dt.mat_pri) AS pr 
			  ON dt.mat_pri = pr.mat_pri 
			LEFT JOIN 
			  (SELECT 
				nd.codpro,
				SUM(nd.cansol) AS ing 
			  FROM
				neadet nd 
			  WHERE nd.fecemi > '2020-07-31' 
			  GROUP BY nd.codpro) AS i 
			  ON dt.mat_pri = i.codpro 
		  WHERE o.estado NOT IN ('Cerrado') 
			AND o.codigo = :mp
		  GROUP BY mp.codpro 
		  ORDER BY mp.linea";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->bindParam(":mp", $mp, PDO::PARAM_STR);

			$stmt->execute();

			# Retornamos un fetchAll por ser más de una línea la que necesitamos devolver
			return $stmt->fetchAll();
		}

		$stmt = null;
	}

	/* 
	* MOSTRAR MATERIA PRIMA POR ARTICULO
	*/
	static public function mdlMostrarMateriaArticulo($valor)
	{

		$sql = "SELECT DISTINCT 
		dt.mat_pri,
		mp.descripcion,
		mp.unidad,
		ROUND(dt.consumo, 6) AS consumo,
		CASE
		  WHEN dt.tej_princ = 'no' 
		  THEN '' 
		  ELSE 'SI' 
		END AS tej_princ,
		ROUND(dt.precio_mp, 6) AS precio_mp,
		ROUND(dt.total_detalle, 6) AS total_detalle 
	  FROM
		detalles_tarjetajf dt 
		LEFT JOIN 
		  (SELECT DISTINCT 
			p.codpro,
			tblin.des_larga AS linea,
			SUBSTRING(p.codfab, 1, 6) AS codlinea,
			p.codfab,
			p.despro AS despro,
			CONCAT(
			  (SUBSTRING(p.CodFab, 1, 6)),
			  ' - ',
			  p.DesPro,
			  ' - ',
			  tbcol.des_larga
			) AS descripcion,
			p.codalm01 AS stock,
			tbund.des_corta AS unidad,
			tbcol.des_larga AS color,
			p.cospro 
		  FROM
			producto p 
			INNER JOIN tabla_m_detalle AS tbund 
			  ON p.undpro = tbund.cod_argumento 
			  AND (tbund.Cod_Tabla = 'TUND') 
			INNER JOIN tabla_m_detalle AS tbcol 
			  ON p.ColPro = tbcol.cod_argumento 
			  AND (tbcol.Cod_Tabla = 'TCOL') 
			INNER JOIN tabla_m_detalle AS tblin 
			  ON LEFT(p.codfab, 3) = tblin.des_corta 
			  AND (tblin.cod_tabla = 'Tlin') 
		  WHERE p.estpro = '1' 
			AND tblin.des_larga IN ('BLONDA', 'ELASTICO', 'TELA')) AS mp 
		  ON dt.mat_pri = mp.codpro 
	  WHERE dt.articulo LIKE '%" . $valor . "%' 
		AND mp.descripcion IS NOT NULL ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":articulo", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR LINEAS
	=============================================*/

	static public function mdlMostrarLineas()
	{

		$stmt = Conexion::conectar()->prepare("SELECT distinct * FROM Tabla_M_Detalle WHERE  Cod_Tabla = 'TLIN' and Cod_Argumento not like '000'");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR SUBLINEAS
	=============================================*/

	static public function mdlMostrarSubLineas($valor)
	{
		if ($valor == null) {

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT * FROM Tabla_M_Detalle WHERE Cod_Tabla = 'TSUB' ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT * FROM Tabla_M_Detalle WHERE Cod_Tabla = 'TSUB' AND Des_Corta = '" . $valor . "' ORDER BY Valor_3 ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}




		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR SUBLINEAS 2
	=============================================*/

	static public function mdlMostrarSubLineas2($valor, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("SELECT  Valor_3, Des_Larga FROM Tabla_M_Detalle WHERE Cod_Tabla = 'TSUB' AND Des_Corta = '" . $valor . "' AND Valor_3 = '" . $valor2 . "'  ");

		$stmt->execute();

		return $stmt->fetch();




		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR TALLAS
	=============================================*/

	static public function mdlMostrarTallas()
	{

		$stmt = Conexion::conectar()->prepare("SELECT  distinct * FROM Tabla_M_Detalle WHERE  Cod_Tabla = 'TTAL'");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR COLORES
	=============================================*/

	static public function mdlMostrarColores()
	{

		$stmt = Conexion::conectar()->prepare("SELECT  distinct * FROM Tabla_M_Detalle WHERE  Cod_Tabla = 'TCOL' and Cod_Argumento not like '0000' ");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR COLORES
	=============================================*/

	static public function mdlMostrarUndMedida()
	{

		$stmt = Conexion::conectar()->prepare("SELECT distinct * FROM Tabla_M_Detalle WHERE  Cod_Tabla = 'TUND' and Cod_Argumento not like '000'  ");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	CREAR PRECIO DE MATERIA PRIMA
	=============================================*/

	static public function mdlIngresarPrecioMP($tabla, $datos)
	{

		$datos = self::mdlNormalizarDatosPrecio($datos);

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(Cod_Local,Cod_Entidad,CodPro, CodProv1, PreProv1,MonProv1,ObsProv1,CodProv2, PreProv2,MonProv2,ObsProv2,CodProv3,PreProv3,MonProv3,ObsProv3,FecReg,UsuReg,PcReg) VALUES (:Cod_Local,:Cod_Entidad,:CodPro,:CodProv1, :PreProv1,:MonProv1,UPPER(:ObsProv1),:CodProv2,:PreProv2,:MonProv2,UPPER(:ObsProv2),:CodProv3,:PreProv3,:MonProv3,UPPER(:ObsProv3),:FecReg,UPPER(:UsuReg),UPPER(:PcReg))");

		$stmt->bindParam(":Cod_Local", $datos["Cod_Local"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Entidad", $datos["Cod_Entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv1", $datos["CodProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv1", $datos["PreProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv1", $datos["MonProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv1", $datos["ObsProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv2", $datos["CodProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv2", $datos["PreProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv2", $datos["MonProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv2", $datos["ObsProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv3", $datos["CodProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv3", $datos["PreProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv3", $datos["MonProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv3", $datos["ObsProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":FecReg", $datos["FecReg"], PDO::PARAM_STR);
		$stmt->bindParam(":PcReg", $datos["PcReg"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuReg", $datos["UsuReg"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	CREAR MATERIA PRIMA
	=============================================*/

	static public function mdlIngresarMateriaPrima($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(CodAlt,Cod_Local,Cod_Entidad,CodPro,CodFab,DesPro,ColPro,UndPro,Mo,PaiPro,PrePro,PreFob,CosPro,Por_AdVal,Por_Seg,PesPro,Stk_Act,Stk_Min,Stk_Max,EstPro,TalPro,FamPro, Proveedor, CodAlm01, FecReg, UsuReg, PcReg) VALUES (:CodAlt,:Cod_Local,:Cod_Entidad,:CodPro,:CodFab,UPPER(:DesPro),:ColPro,:UndPro,:Mo,:PaiPro,:PrePro,:PreFob,:CosPro,:Por_AdVal,:Por_Seg,:PesPro,:Stk_Act,:Stk_Min,:Stk_Max,:EstPro,:TalPro,:FamPro,:Proveedor,:CodAlm01,:FecReg,UPPER(:UsuReg),UPPER(:PcReg))");

		$stmt->bindParam(":CodAlt", $datos["CodAlt"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Local", $datos["Cod_Local"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Entidad", $datos["Cod_Entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodFab", $datos["CodFab"], PDO::PARAM_STR);
		$stmt->bindParam(":DesPro", $datos["DesPro"], PDO::PARAM_STR);
		$stmt->bindParam(":ColPro", $datos["ColPro"], PDO::PARAM_STR);
		$stmt->bindParam(":UndPro", $datos["UndPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Mo", $datos["Mo"], PDO::PARAM_STR);
		$stmt->bindParam(":PaiPro", $datos["PaiPro"], PDO::PARAM_STR);
		$stmt->bindParam(":PrePro", $datos["PrePro"], PDO::PARAM_STR);
		$stmt->bindParam(":PreFob", $datos["PreFob"], PDO::PARAM_STR);
		$stmt->bindParam(":CosPro", $datos["CosPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Por_AdVal", $datos["Por_AdVal"], PDO::PARAM_STR);
		$stmt->bindParam(":Por_Seg", $datos["Por_Seg"], PDO::PARAM_STR);
		$stmt->bindParam(":PesPro", $datos["PesPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Stk_Act", $datos["Stk_Act"], PDO::PARAM_STR);
		$stmt->bindParam(":Stk_Min", $datos["Stk_Min"], PDO::PARAM_STR);
		$stmt->bindParam(":Stk_Max", $datos["Stk_Max"], PDO::PARAM_STR);
		$stmt->bindParam(":EstPro", $datos["EstPro"], PDO::PARAM_STR);
		$stmt->bindParam(":TalPro", $datos["TalPro"], PDO::PARAM_STR);
		$stmt->bindParam(":FamPro", $datos["FamPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Proveedor", $datos["Proveedor"], PDO::PARAM_STR);
		$stmt->bindParam(":CodAlm01", $datos["CodAlm01"], PDO::PARAM_STR);
		$stmt->bindParam(":FecReg", $datos["FecReg"], PDO::PARAM_STR);
		$stmt->bindParam(":PcReg", $datos["PcReg"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuReg", $datos["UsuReg"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	VALIDAR CODIGO DE FABRICA MATERIA PRIMA
	=============================================*/

	static public function mdlMostrarMateriaFabrica($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT  * FROM producto WHERE  CodFab = '" . $valor . "'");

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR ULTIMO CODIGOPRO DE MATERIA PRIMA
	=============================================*/

	static public function mdlMostrarUltimoCodPro()
	{


		$stmt = Conexion::conectar()->prepare("SELECT MAX(CodPro) AS CodPro FROM producto");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*
	* Siguiente CodPro libre.
	* Mantiene el mismo ancho de dígitos del MAX actual (no alarga el código:
	* si la columna es fija, un código más largo se trunca y genera duplicados).
	*/
	static public function mdlSiguienteCodProLibre()
	{

		$ultimoCod = self::mdlMostrarUltimoCodPro();

		if (!$ultimoCod || $ultimoCod["CodPro"] === null || $ultimoCod["CodPro"] === "") {
			return "000001";
		}

		$codigoActual = (string) $ultimoCod["CodPro"];
		$len = strlen($codigoActual);
		$siguiente = $codigoActual + 1;

		$maxIntentos = 5000;
		for ($i = 0; $i < $maxIntentos; $i++) {

			// No generar CodPro con más dígitos que los actuales
			if (strlen((string) $siguiente) > $len) {
				return false;
			}

			$codigoPro = str_pad($siguiente, $len, '0', STR_PAD_LEFT);

			$stmt = Conexion::conectar()->prepare("SELECT CodPro FROM producto WHERE CodPro = :CodPro LIMIT 1");
			$stmt->bindParam(":CodPro", $codigoPro, PDO::PARAM_STR);
			$stmt->execute();

			if (!$stmt->fetch()) {
				// Limpia precio huérfano del mismo código para no chocar el INSERT
				self::mdlEliminarPrecioMP($codigoPro);
				return $codigoPro;
			}

			$siguiente++;
		}

		return false;
	}

	/*
	* Normaliza precios/códigos vacíos para evitar fallo de INSERT en preciomp
	*/
	static public function mdlNormalizarDatosPrecio($datos)
	{

		foreach (array("PreProv1", "PreProv2", "PreProv3") as $campo) {
			if (!isset($datos[$campo]) || $datos[$campo] === "" || $datos[$campo] === null) {
				$datos[$campo] = "0";
			}
		}

		foreach (array("CodProv1", "CodProv2", "CodProv3", "MonProv1", "MonProv2", "MonProv3", "ObsProv1", "ObsProv2", "ObsProv3") as $campo) {
			if (!isset($datos[$campo]) || $datos[$campo] === null) {
				$datos[$campo] = "";
			}
		}

		return $datos;
	}

	/*
	* Elimina un registro de preciomp (rollback / limpieza de huérfanos)
	*/
	static public function mdlEliminarPrecioMP($codpro)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM preciomp WHERE CodPro = :CodPro");
		$stmt->bindParam(":CodPro", $codpro, PDO::PARAM_STR);

		if ($stmt->execute()) {
			return "ok";
		}

		return "error";
	}


	/*=============================================
	EDITAR PRECIO DE MATERIA PRIMA (UPSERT)
	=============================================*/

	static public function mdlEditarPrecioMP($tabla, $datos)
	{

		$datos = self::mdlNormalizarDatosPrecio($datos);

		$stmtExiste = Conexion::conectar()->prepare("SELECT CodPro FROM $tabla WHERE CodPro = :CodPro LIMIT 1");
		$stmtExiste->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmtExiste->execute();
		$existe = $stmtExiste->fetch();

		if (!$existe) {

			$datosInsert = $datos;
			$datosInsert["Cod_Local"] = isset($datos["Cod_Local"]) ? $datos["Cod_Local"] : "01";
			$datosInsert["Cod_Entidad"] = isset($datos["Cod_Entidad"]) ? $datos["Cod_Entidad"] : "01";
			$datosInsert["FecReg"] = isset($datos["FecMod"]) ? $datos["FecMod"] : (isset($datos["FecReg"]) ? $datos["FecReg"] : date("Y-m-d H:i:s"));
			$datosInsert["UsuReg"] = isset($datos["UsuMod"]) ? $datos["UsuMod"] : (isset($datos["UsuReg"]) ? $datos["UsuReg"] : "");
			$datosInsert["PcReg"] = isset($datos["PcMod"]) ? $datos["PcMod"] : (isset($datos["PcReg"]) ? $datos["PcReg"] : "");

			return self::mdlIngresarPrecioMP($tabla, $datosInsert);
		}

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET CodProv1=:CodProv1,MonProv1=:MonProv1,PreProv1=:PreProv1, ObsProv1=UPPER(:ObsProv1),CodProv2=:CodProv2,MonProv2=:MonProv2,PreProv2=:PreProv2,ObsProv2=UPPER(:ObsProv2),CodProv3=:CodProv3,MonProv3=:MonProv3,PreProv3=:PreProv3,ObsProv3=UPPER(:ObsProv3),UsuMod=UPPER(:UsuMod),FecMod=:FecMod,PcMod=UPPER(:PcMod) WHERE CodPro = :CodPro");

		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv1", $datos["CodProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv1", $datos["PreProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv1", $datos["MonProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv1", $datos["ObsProv1"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv2", $datos["CodProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv2", $datos["PreProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv2", $datos["MonProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv2", $datos["ObsProv2"], PDO::PARAM_STR);
		$stmt->bindParam(":CodProv3", $datos["CodProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":PreProv3", $datos["PreProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":MonProv3", $datos["MonProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsProv3", $datos["ObsProv3"], PDO::PARAM_STR);
		$stmt->bindParam(":FecMod", $datos["FecMod"], PDO::PARAM_STR);
		$stmt->bindParam(":PcMod", $datos["PcMod"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuMod", $datos["UsuMod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	EDITAR MATERIA PRIMA
	=============================================*/
	static public function mdlEditarMateriaPrima($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE producto SET CodAlt=UPPER(:CodAlt),DesPro=UPPER(:DesPro),UndPro=UPPER(:UndPro),Por_AdVal=:Por_AdVal,Por_Seg=:Por_Seg,PesPro=:PesPro,Stk_Min=:Stk_Min,Stk_Max=:Stk_Max,UsuMod=UPPER(:UsuMod),FecMod=:FecMod,PcMod=UPPER(:PcMod) WHERE CodPro = :CodPro");

		$stmt->bindParam(":CodAlt", $datos["CodAlt"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":DesPro", $datos["DesPro"], PDO::PARAM_STR);
		$stmt->bindParam(":UndPro", $datos["UndPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Por_AdVal", $datos["Por_AdVal"], PDO::PARAM_STR);
		$stmt->bindParam(":Por_Seg", $datos["Por_Seg"], PDO::PARAM_STR);
		$stmt->bindParam(":PesPro", $datos["PesPro"], PDO::PARAM_STR);
		$stmt->bindParam(":Stk_Min", $datos["Stk_Min"], PDO::PARAM_STR);
		$stmt->bindParam(":Stk_Max", $datos["Stk_Max"], PDO::PARAM_STR);
		$stmt->bindParam(":FecMod", $datos["FecMod"], PDO::PARAM_STR);
		$stmt->bindParam(":PcMod", $datos["PcMod"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuMod", $datos["UsuMod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	MOSTRAR ULTIMO CODIGOPRO DE MATERIA PRIMA
	=============================================*/

	static public function mdlMostrarExisteMateria($valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT CodPro FROM producto WHERE CodFab = '" . $valor . "'");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ANULAR MATERIA PRIMA
	=============================================*/

	static public function mdlAnularMateriaPrima($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE producto SET EstPro=:EstPro,UsuAnu=UPPER(:UsuAnu),FecAnu=:FecAnu,PcAnu=UPPER(:PcAnu) WHERE CodPro = :CodPro");

		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":EstPro", $datos["EstPro"], PDO::PARAM_STR);
		$stmt->bindParam(":FecAnu", $datos["FecAnu"], PDO::PARAM_STR);
		$stmt->bindParam(":PcAnu", $datos["PcAnu"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuAnu", $datos["UsuAnu"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlMostrarKardexMP($codigo, $ano, $ano_ant)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
		DATE(FecReg) AS FecEmi,
		DATE_FORMAT(FecReg, '%m') AS Fecha,
		NULL AS nDoc,
		NULL AS Razon,
		Valor_1 AS StkInicial,
		NULL AS CanIng,
		NULL AS CanSal 
	  FROM
		Tabla_M_Detalle 
	  WHERE Cod_Tabla = 'INVI' 
		AND Des_Corta = $ano_ant 
		AND Cod_Argumento = $codigo 
	  UNION
	  ALL 
	  SELECT 
		DATE(vc.FecEmi) AS FecEmi,
		DATE_FORMAT(vc.FecEmi, '%m') AS Fecha,
		CONCAT(vc.Tip, '-', vc.Ser, '-', vc.Nro) AS nDoc,
		Clientes.RazCli AS Razon,
		NULL AS StkInicial,
		'0.00' AS CanIng,
		vd.CanVta AS CanSal 
	  FROM
		Ventas_Cab vc,
		Venta_Det vd,
		Clientes 
	  WHERE vc.Nro = vd.Nro 
		AND vd.CodPro = $codigo 
		AND Clientes.Ruc = vc.Ruc 
		AND vd.FecEmi LIKE '%$ano%' 
		AND vc.EstVta = 'P' 
	  UNION
	  ALL 
	  SELECT 
		DATE(os.FecEmi) AS FecEmi,
		DATE_FORMAT(os.FecEmi, '%m') AS Fecha,
		CONCAT(os.Tip, '-', os.Ser, '-', os.Nro) AS nDoc,
		pro.RazPro AS Razon,
		NULL AS StkInicial,
		'0.00' AS CanIng,
		od.CantidadIni AS CanSal 
	  FROM
		oservicio os,
		oserviciodet od,
		Proveedor pro 
	  WHERE os.Nro = od.Nro 
		AND od.CodProOrigen = $codigo 
		AND pro.CodRuc = os.CodRuc 
		AND od.FecEmi LIKE '%$ano%' 
		AND od.DesStk = 'SI' 
		AND os.EstReg = '1' 
	  UNION
	  ALL 
	  SELECT 
		DATE(nod.FecEmi) AS FecEmi,
		DATE_FORMAT(nod.FecEmi, '%m') AS Fecha,
		CONCAT(
		  'NExOS',
		  '-',
		  nod.sNeaOs,
		  '-',
		  nod.NroOs
		) AS nDoc,
		'ELASTICOS VASCO' AS Razon,
		NULL AS StkInicial,
		nod.CanSol AS CanIng,
		'0.00' AS CanSal 
	  FROM
		nea_os_det nod 
	  WHERE nod.estReg = 'P' 
		AND nod.FecEmi LIKE '%$ano%' 
		AND nod.CodProDestino = $codigo 
		AND nod.CanSol > 0 
	  UNION
	  ALL 
	  SELECT 
		DATE(Nea.FecEmi) AS FecEmi,
		DATE_FORMAT(Nea.FecEmi, '%m') AS Fecha,
		CONCAT(
		  Nea.tNea,
		  '-',
		  Nea.sNea,
		  '-',
		  Nea.nNea
		) AS nDoc,
		Proveedor.RazPro AS Razon,
		NULL AS StkInicial,
		NeaDet.CanSol AS CanIng,
		'0.00' AS CanSal 
	  FROM
		Nea,
		NeaDet,
		Proveedor 
	  WHERE Nea.nNea = NeaDet.nNea 
		AND NeaDet.CodPro = $codigo 
		AND Proveedor.CodRuc = Nea.CodRuc 
		AND Nea.FecEmi LIKE '%$ano%' 
		AND NeaDet.CanSol > 0 
		AND Nea.EstReg = 'P' 
		AND Nea.nNea NOT IN 
		(SELECT 
		  NIGuiaAsociada 
		FROM
		  Nea 
		WHERE Nea.EstReg = 'P' 
		  AND Nea.`NroGuiaAsociada` != '') 
		UNION
		ALL 
		SELECT DISTINCT 
		  NULL AS FecEmi,
		  NULL AS Fecha,
		  NULL AS nDoc,
		  NULL AS Razon,
		  'TOTALES:' AS StkInicial,
		  IFNULL(
			(SELECT 
			  SUM(NeaDet.CanSol) 
			FROM
			  Nea,
			  NeaDet 
			WHERE Nea.nNea = NeaDet.nNea 
			  AND NeaDet.CodPro = $codigo 
			  AND Nea.FecEmi LIKE '%$ano%' 
			  AND Nea.EstReg = 'P'),
			0
		  ) + IFNULL(
			(SELECT 
			  SUM(nod.CanSol) 
			FROM
			  nea_os_det nod 
			WHERE nod.estReg = 'P' 
			  AND nod.FecEmi LIKE '%$ano%' 
			  AND nod.CodProDestino = $codigo 
			  AND nod.CanSol > 0),
			0
		  ) AS CanIng,
		  IFNULL(
			(SELECT 
			  SUM(vd.CanVta) 
			FROM
			  Ventas_Cab vc,
			  Venta_Det vd 
			WHERE vc.Nro = vd.Nro 
			  AND vd.CodPro = $codigo 
			  AND vc.FecEmi LIKE '%$ano%' 
			  AND vc.EstVta = 'P' 
			  AND vd.CanVta > 0),
			0
		  ) + IFNULL(
			(SELECT 
			  SUM(od.CantidadIni) 
			FROM
			  oserviciodet od,
			  oservicio os 
			WHERE os.Nro = od.Nro 
			  AND od.CodProOrigen = $codigo 
			  AND os.FecEmi LIKE '%$ano%' 
			  AND os.EstReg = '1' 
			  AND od.DesStk = 'SI'),
			0
		  ) AS CanSal 
		FROM
		  Nea,
		  NeaDet,
		  Ventas_Cab vc,
		  Venta_Det vd,
		  oserviciodet od,
		  oservicio os 
		WHERE Nea.nNea = NeaDet.nNea 
		  AND Nea.nNea NOT IN 
		  (SELECT 
			NIGuiaAsociada 
		  FROM
			Nea 
		  WHERE Nea.EstReg = 'P' 
			AND Nea.`NroGuiaAsociada` != '') 
		  AND NeaDet.CodPro = $codigo 
		  AND vc.Nro = vd.Nro 
		  AND vd.CodPro = $codigo 
		  AND os.Nro = od.Nro 
		  AND od.CodProOrigen = $codigo 
		  AND Nea.FecEmi LIKE '%$ano%' 
		  AND Nea.EstReg = 'P' 
		  AND vc.FecEmi LIKE '%$ano%' 
		  AND vc.EstVta = 'P' 
		  AND os.FecEmi LIKE '%$ano%' 
		  AND os.EstReg = '1' 
		UNION
		ALL
		SELECT 
		DATE(md.fecreg) AS FecEmi,
		DATE_FORMAT(md.fecreg, '%m') AS Fecha,
		CONCAT(md.tipo, '-', md.documento) AS nDoc,
		'Corporacion Vasco SAC' AS Razon,
		'' AS StkInicial,
		CASE
			WHEN md.condicion = '+' 
			THEN md.valor1 
			ELSE 0 
		END AS CanIng,
		CASE
			WHEN md.condicion = '-' 
			THEN md.valor1 
			ELSE 0 
		END AS CanSal 
		FROM
		maestra_prod_det md 
		WHERE md.fecreg LIKE '%$ano%' 
		AND md.codigo = $codigo
		ORDER BY Fecha DESC ");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA ALMACEN 01
	*/
	static public function mdlMostrarAlmacen01($tipo)
	{

		if ($tipo == "CUA" || $tipo == "COP") {

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
			pro.codpro,
			pro.codfab,
			pro.despro,
			pro.CodAlm01 AS stock,
			TbUnd.Des_Corta AS unidad,
			pro.colpro,
			TbCol.Des_Larga AS color,
			pro.talpro,
			TbTal.Des_larga AS talla,
			pro.cuadro,
			(SELECT 
				despro 
			FROM
				producto 
			WHERE codpro = pro.cuadro) AS cuadro_nom,
			pro.usureg,
			pro.fampro,
			LEFT(pro.fampro, 3) AS fam 
		FROM
			Producto pro 
			INNER JOIN Tabla_M_Detalle AS TbUnd 
			ON pro.UndPro = TbUnd.Cod_Argumento 
			AND (TbUnd.Cod_Tabla = 'TUND') 
			INNER JOIN Tabla_M_Detalle AS TbCol 
			ON pro.ColPro = TbCol.Cod_Argumento 
			AND (TbCol.Cod_Tabla = 'TCOL') 
			INNER JOIN Tabla_M_Detalle AS TbTal 
			ON pro.TalPro = TbTal.Cod_Argumento 
			AND (TbTal.Cod_Tabla = 'TTAL') 
		WHERE pro.estpro = '1' 
			AND LEFT(pro.fampro, 3) = :tipo
		ORDER BY pro.codfab ASC");

			$stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
			pro.codpro,
			pro.codfab,
			pro.despro,
			pro.CodAlm01 AS stock,
			TbUnd.Des_Corta AS unidad,
			pro.colpro,
			TbCol.Des_Larga AS color,
			pro.talpro,
			TbTal.Des_larga AS talla,
			pro.cuadro,
			(SELECT 
				despro 
			FROM
				producto 
			WHERE codpro = pro.cuadro) AS cuadro_nom,
			pro.usureg,
			pro.fampro,
			LEFT(pro.fampro, 3) AS fam 
			FROM
				Producto pro 
				INNER JOIN Tabla_M_Detalle AS TbUnd 
				ON pro.UndPro = TbUnd.Cod_Argumento 
				AND (TbUnd.Cod_Tabla = 'TUND') 
				INNER JOIN Tabla_M_Detalle AS TbCol 
				ON pro.ColPro = TbCol.Cod_Argumento 
				AND (TbCol.Cod_Tabla = 'TCOL') 
				INNER JOIN Tabla_M_Detalle AS TbTal 
				ON pro.TalPro = TbTal.Cod_Argumento 
				AND (TbTal.Cod_Tabla = 'TTAL') 
			WHERE pro.estpro = '1' 
				AND LEFT(pro.fampro, 3) IN ('CUA','COP')
			ORDER BY pro.codfab ASC");


			$stmt->execute();

			return $stmt->fetchAll();
		}



		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlAlmacen01Agregar($codpro)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
							CodPro AS codpro,
							CodFab AS codfab,
							DesPro AS despro,
							TbCol.Des_Larga AS color,
							TbTal.Des_Larga AS talla,
							TbUnd.Des_Corta AS unidad,
							CodAlm01 AS stock,
							pro.cuadro 
						FROM
							Producto AS Pro 
							INNER JOIN Tabla_M_Detalle AS TbUnd 
							ON Pro.UndPro = TbUnd.Cod_Argumento 
							AND (TbUnd.Cod_Tabla = 'TUND') 
							INNER JOIN Tabla_M_Detalle AS TbCol 
							ON Pro.ColPro = TbCol.Cod_Argumento 
							AND (TbCol.Cod_Tabla = 'TCOL') 
							INNER JOIN Tabla_M_Detalle AS TbTal 
							ON Pro.TalPro = TbTal.Cod_Argumento 
							AND (TbTal.Cod_Tabla = 'TTAL') 
						WHERE Pro.EstPro = '1' 
						AND LEFT(pro.fampro, 3) = 'COP' 
						AND (
							pro.cuadro = '' 
							OR pro.cuadro IS NULL
						)");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ANULAR MATERIA PRIMA
	=============================================*/

	static public function mdlAgregarCuadro($cuadro, $codpro)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
							producto 
						SET
							cuadro = :cuadro 
						WHERE codpro = :codpro ");

		$stmt->bindParam(":cuadro", $cuadro, PDO::PARAM_STR);
		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlAlmacen01Quitar($codpro)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
							CodPro AS codpro,
							CodFab AS codfab,
							DesPro AS despro,
							TbCol.Des_Larga AS color,
							TbTal.Des_Larga AS talla,
							TbUnd.Des_Corta AS unidad,
							CodAlm01 AS stock,
							pro.cuadro 
						FROM
							Producto AS Pro 
							INNER JOIN Tabla_M_Detalle AS TbUnd 
							ON Pro.UndPro = TbUnd.Cod_Argumento 
							AND (TbUnd.Cod_Tabla = 'TUND') 
							INNER JOIN Tabla_M_Detalle AS TbCol 
							ON Pro.ColPro = TbCol.Cod_Argumento 
							AND (TbCol.Cod_Tabla = 'TCOL') 
							INNER JOIN Tabla_M_Detalle AS TbTal 
							ON Pro.TalPro = TbTal.Cod_Argumento 
							AND (TbTal.Cod_Tabla = 'TTAL') 
						WHERE Pro.EstPro = '1' 
						AND LEFT(pro.fampro, 3) = 'COP' 
						AND pro.cuadro=:codpro");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ANULAR MATERIA PRIMA
	=============================================*/

	static public function mdlQuitarCuadro($codpro)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
							producto 
						SET
							cuadro = '' 
						WHERE codpro = :codpro ");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlCorrelativoNuevo($tipo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
							LPAD(IFNULL(MAX(documento), 0) + 1, 6, '0') AS correlativo 
						FROM
							maestra_prod_cab 
						WHERE tipo = :tipo");

		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTAR MP DE ALMACEN01 
	*/
	static public function mdlSelectAlmacen01($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
		pro.codpro,
		pro.codfab,
		pro.despro,
		pro.CodAlm01 AS stock,
		TbUnd.Des_Corta AS unidad,
		pro.colpro,
		TbCol.Des_Larga AS color,
		pro.talpro,
		TbTal.Des_larga AS talla,
		pro.cuadro,
		(SELECT 
		  despro 
		FROM
		  producto 
		WHERE codpro = pro.cuadro) AS cuadro_nom,
		pro.usureg,
		LEFT(pro.fampro, 3) AS fam 
	  FROM
		Producto pro 
		INNER JOIN Tabla_M_Detalle AS TbUnd 
		  ON pro.UndPro = TbUnd.Cod_Argumento 
		  AND (TbUnd.Cod_Tabla = 'TUND') 
		INNER JOIN Tabla_M_Detalle AS TbCol 
		  ON pro.ColPro = TbCol.Cod_Argumento 
		  AND (TbCol.Cod_Tabla = 'TCOL') 
		INNER JOIN Tabla_M_Detalle AS TbTal 
		  ON pro.TalPro = TbTal.Cod_Argumento 
		  AND (TbTal.Cod_Tabla = 'TTAL') 
	  WHERE pro.estpro = '1' 
		AND pro.codpro = :codpro 
	  ORDER BY pro.codfab ASC");

		$stmt->bindParam(":codpro", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*
	* GUARDAR PRODUCCION EN MAESTRA
	*/
	static public function mdlGuardarProduccionCab($datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO maestra_prod_cab (
			tipo,
			documento,
			valor1,
			valor2,
			valor3,
			valor4,
			valor5,
			usureg,
			fecreg,
			pcreg
		  ) 
		  VALUES
			(
			  :tipo,
			  :documento,
			  :valor1,
			  :valor2,
			  :valor3,
			  :valor4,
			  :valor5,
			  :usureg,
			  :fecreg,
			  :pcreg
			)");

		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $datos["valor1"], PDO::PARAM_STR);
		$stmt->bindParam(":valor2", $datos["valor2"], PDO::PARAM_STR);
		$stmt->bindParam(":valor3", $datos["valor3"], PDO::PARAM_STR);
		$stmt->bindParam(":valor4", $datos["valor4"], PDO::PARAM_STR);
		$stmt->bindParam(":valor5", $datos["valor5"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* GUARDAR PRODUCCION EN MAESTRA
	*/
	static public function mdlGuardarProduccionDet($datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO maestra_prod_det (
						tipo,
						documento,
						codigo,
						valor1,
						valor2,
						valor3,
						valor4,
						valor5,
						usureg,
						fecreg,
						pcreg,
						condicion
					) 
					VALUES
						(
						:tipo,
						:documento,
						:codigo,
						:valor1,
						:valor2,
						:valor3,
						:valor4,
						:valor5,
						:usureg,
						:fecreg,
						:pcreg,
						:condicion
						)");

		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $datos["valor1"], PDO::PARAM_STR);
		$stmt->bindParam(":valor2", $datos["valor2"], PDO::PARAM_STR);
		$stmt->bindParam(":valor3", $datos["valor3"], PDO::PARAM_STR);
		$stmt->bindParam(":valor4", $datos["valor4"], PDO::PARAM_STR);
		$stmt->bindParam(":valor5", $datos["valor5"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion", $datos["condicion"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* GUARDAR PRODUCCION EN MAESTRA
	*/
	static public function mdlActualizarStockMP($codpro, $cantidad)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
		producto 
	  SET
		codalm01 = codalm01 + :cantidad 
	  WHERE codpro = :codpro");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* GUARDAR PRODUCCION EN MAESTRA
	*/
	static public function mdlDescontarStockMP($codpro, $cantidad)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
		producto 
	  SET
		CodAlm01 = CodAlm01 - :cantidad  
	  WHERE codpro = :codpro");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlMostrarMateriaOrdenCompra($valor1, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
		p.codpro,
		SUBSTRING(p.codfab, 1, 6) AS codlinea,
		p.codfab,
		p.ColPro,
		p.despro AS despro,
		CONCAT(
		  (SUBSTRING(p.CodFab, 1, 6)),
		  ' - ',
		  p.DesPro,
		  ' - ',
		  tbcol.des_larga,
		  ' - ',
		  tbund.des_corta
		) AS descripcion,
		p.codalm01 AS stock,
		tbund.des_corta AS unidad,
		tbcol.des_larga AS color,
		p.cospro,
		IFNULL(pmp.precio, 0.000000) AS precio 
	  FROM
		producto p 
		LEFT JOIN 
		  (SELECT 
			codpro,
			CASE
			  WHEN codprov1 = :codruc
			  THEN preprov1 
			  WHEN codprov2 = :codruc
			  THEN preprov2 
			  WHEN codprov3 = :codruc 
			  THEN preprov3 
			  ELSE 0 
			END AS precio 
		  FROM
			preciomp 
		  WHERE codpro = :codpro) AS pmp 
		  ON pmp.codpro = p.codpro 
		INNER JOIN tabla_m_detalle AS tbund 
		  ON p.undpro = tbund.cod_argumento 
		  AND (tbund.Cod_Tabla = 'TUND') 
		INNER JOIN tabla_m_detalle AS tbcol 
		  ON p.ColPro = tbcol.cod_argumento 
		  AND (tbcol.Cod_Tabla = 'TCOL') 
	  WHERE p.estpro = '1' 
		AND p.CodPro = :codpro ");

		$stmt->bindParam(":codpro", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":codruc", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	EDITAR DETALLE DE MAESTRA MP
	=============================================*/

	static public function mdlEditarDetalleMP($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE maestra_prod_det SET valor1=:cantidad,usumod=:usumod,fecmod=:fecmod,pcmod=:pcmod WHERE codigo = :codigo and documento = :documento and tipo=:tipo");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
		$stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	ANULAR DETALLE DE MAESTRA MP
	=============================================*/

	static public function mdlAnularDetalleMP($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE maestra_prod_det SET estado=:estado,visible=:visible,usumod=:usumod,fecmod=:fecmod,pcmod=:pcmod WHERE codigo = :codigo and documento = :documento and tipo=:tipo");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
		$stmt->bindParam(":visible", $datos["visible"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
		$stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/* 
	* SELECT PARA AGREGAR ITEM DE DETALLE MP
	*/
	static public function mdlSelectMateriaTipo($tipo, $documento)
	{


		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
			pro.codpro,
			pro.codfab,
			pro.despro,
			pro.CodAlm01 AS stock,
			TbUnd.Des_Corta AS unidad,
			pro.colpro,
			TbCol.Des_Larga AS color,
			pro.talpro,
			TbTal.Des_larga AS talla,
			pro.cuadro,
			(SELECT 
			  despro 
			FROM
			  producto 
			WHERE codpro = pro.cuadro) AS cuadro_nom,
			pro.usureg,
			LEFT(pro.fampro, 3) AS fam 
		  FROM
			Producto pro 
			INNER JOIN Tabla_M_Detalle AS TbUnd 
			  ON pro.UndPro = TbUnd.Cod_Argumento 
			  AND (TbUnd.Cod_Tabla = 'TUND') 
			INNER JOIN Tabla_M_Detalle AS TbCol 
			  ON pro.ColPro = TbCol.Cod_Argumento 
			  AND (TbCol.Cod_Tabla = 'TCOL') 
			INNER JOIN Tabla_M_Detalle AS TbTal 
			  ON pro.TalPro = TbTal.Cod_Argumento 
			  AND (TbTal.Cod_Tabla = 'TTAL') 
		  WHERE pro.estpro = '1' 
			AND LEFT(pro.fampro, 3) = RIGHT(:tipo,3)
			AND pro.codpro NOT IN 
			(SELECT 
			  codigo 
			FROM
			  maestra_prod_det mt 
			WHERE mt.tipo = :tipo
			  AND mt.documento = :documento)
		  ORDER BY pro.codfab ASC");

		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
		$stmt->bindParam(":documento", $documento, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();




		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	EDITAR COPA MP
	=============================================*/

	static public function mdlEditarCopaMP($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE producto SET cuadro=:cuadro,UsuMod=:UsuMod,FecMod=:FecMod,PcMod=:PcMod WHERE CodPro = :codigo ");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cuadro", $datos["cuadro"], PDO::PARAM_STR);
		$stmt->bindParam(":FecMod", $datos["FecMod"], PDO::PARAM_STR);
		$stmt->bindParam(":PcMod", $datos["PcMod"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuMod", $datos["UsuMod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function mdlGlobalMaestra($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
						t.cod_argumento,
						t.cod_tabla,
						t.des_larga,
						t.des_corta 
					FROM
						tabla_m_detalle t 
					WHERE t.cod_tabla = :valor");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	#region Saldos
	static public function mdlMPSaldos()
	{
		$stmt = Conexion::conectar()->prepare("SELECT
			distinct 
					pro.codpro,
					pro.codfab,
					pro.despro,
					pro.CodAlm01 as stock,
					pro.undpro,
					TbUnd.Des_Corta as unidad,
					pro.colpro,
					TbCol.Des_Larga as color,
					pro.talpro,
					TbTal.Des_larga as talla,
					left(pro.fampro,
			3) as fam,
			tblin.Des_Larga as linea
		from
					Producto pro
		inner join Tabla_M_Detalle as TbUnd 
					on
			pro.UndPro = TbUnd.Cod_Argumento
			and (TbUnd.Cod_Tabla = 'TUND')
		inner join Tabla_M_Detalle as TbCol 
					on
			pro.ColPro = TbCol.Cod_Argumento
			and (TbCol.Cod_Tabla = 'TCOL')
		inner join Tabla_M_Detalle as TbTal 
					on
			pro.TalPro = TbTal.Cod_Argumento
			and (TbTal.Cod_Tabla = 'TTAL')
		inner join tabla_m_detalle as tblin
			on
			left(pro.fampro,
			3) = tblin.Des_Corta
			and tblin.Cod_Tabla = 'TLIN'
		order by
			pro.codfab");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMPStockInicial()
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				Cod_Argumento as codpro,
				Valor_1 as cantidad
			from
				tabla_m_detalle
			where
				cod_tabla = 'INVI'
				and des_corta = '2023'");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMPIngresos($anio, $fecha)
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				codpro,
				sum(cansol) as cantidad 
			from
				neadet
			where
				year(fecemi)= '$anio'
				and fecemi <= '$fecha'
			group by codpro");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMPIngresosOS($anio, $fecha)
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				codprodestino as codpro,
				sum(cansol) as cantidad
			from
				nea_os_det
			where
				year(fecemi)= '$anio'
				and fecemi <= '$fecha'
			group by codprodestino");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMPSalidas($anio, $fecha)
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				codpro,
				sum(CanVta) as cantidad
			from
				venta_det
			where
				year(fecemi)= '$anio'
				and fecemi <= '$fecha'
			group by codpro");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMPSalidasOS($anio, $fecha)
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				codproorigen as codpro,
				sum(cantidadini) as cantidad
			from
				oserviciodet
			where
				year(fecemi)= '$anio'
				and fecemi <= '$fecha'
			group by codproorigen");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR MATERIA PRIMA CON PAGINACIÓN SERVIDOR
	=============================================*/

	static public function mdlMostrarMateriaPrimaPaginado($start, $length, $search, $orderColumn, $orderDir, $sublinea = "")
	{
		$sublinea = self::mdlNormalizarSublineaFiltro($sublinea);

		// Construir WHERE clause base
		$where = "1=1";
		if ($sublinea !== "") {
			$where .= " AND (TRIM(IFNULL(pro.FamPro, '')) = :sublinea OR LEFT(TRIM(pro.CodFab), 6) = :sublinea)";
		}

		// Construir búsqueda
		$searchWhere = "";
		if (!empty($search)) {
			$searchWhere = " AND (
				pro.CodPro LIKE :search1 OR 
				pro.CodFab LIKE :search2 OR 
				pro.DesPro LIKE :search3 OR 
				TbCol.Des_Larga LIKE :search4 OR 
				TbTal.Des_larga LIKE :search5 OR
				TbUnd.Des_Corta LIKE :search6 OR
				pmp.Proveedores LIKE :search7
			)";
		}

		// Mapeo de columnas para ordenamiento
		$columns = [
			0 => "pro.CodPro",
			1 => "pro.CodFab",
			2 => "pro.DesPro",
			3 => "TbCol.Des_Larga",
			4 => "TbTal.Des_larga",
			5 => "TbUnd.Des_Corta",
			6 => "pmp.Proveedores",
			7 => "pro.CodAlm01",
			8 => "pro.estpro"
		];

		$orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : "pro.CodPro";
		$orderDir = strtoupper($orderDir) == "ASC" ? "ASC" : "DESC";

		// Consulta para obtener total de registros
		$sqlTotal = "SELECT COUNT(DISTINCT pro.CodPro) as total 
			FROM Producto pro 
			LEFT JOIN 
			(SELECT DISTINCT 
				p.codpro,
				CONCAT_WS(
				' - ',
				IFNULL(p1.razpro, ''),
				IFNULL(p2.razpro, ''),
				IFNULL(p3.razpro, '')
				) AS proveedores,
				GREATEST(
				p.preprov1,
				p.preprov2,
				p.preprov2
				) AS precio 
			FROM
				preciomp p 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p1 
				ON p.codprov1 = p1.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p2 
				ON p.codprov2 = p2.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p3 
				ON p.codprov3 = p3.codruc) AS pmp 
			ON pmp.CodPro = pro.CodPro 
			INNER JOIN Tabla_M_Detalle AS TbUnd 
			ON pro.UndPro = TbUnd.Cod_Argumento 
			AND (TbUnd.Cod_Tabla = 'TUND') 
			INNER JOIN Tabla_M_Detalle AS TbCol 
			ON pro.ColPro = TbCol.Cod_Argumento 
			AND (TbCol.Cod_Tabla = 'TCOL') 
			INNER JOIN Tabla_M_Detalle AS TbTal 
			ON pro.TalPro = TbTal.Cod_Argumento 
			AND (TbTal.Cod_Tabla = 'TTAL') 
			WHERE $where";

		$stmtTotal = Conexion::conectar()->prepare($sqlTotal);
		if ($sublinea !== "") {
			$stmtTotal->bindValue(":sublinea", $sublinea, PDO::PARAM_STR);
		}
		$stmtTotal->execute();
		$totalRecords = $stmtTotal->fetch(PDO::FETCH_ASSOC)["total"];

		// Consulta para obtener total filtrado
		$sqlFiltered = "SELECT COUNT(DISTINCT pro.CodPro) as total 
			FROM Producto pro 
			LEFT JOIN 
			(SELECT DISTINCT 
				p.codpro,
				CONCAT_WS(
				' - ',
				IFNULL(p1.razpro, ''),
				IFNULL(p2.razpro, ''),
				IFNULL(p3.razpro, '')
				) AS proveedores,
				GREATEST(
				p.preprov1,
				p.preprov2,
				p.preprov2
				) AS precio 
			FROM
				preciomp p 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p1 
				ON p.codprov1 = p1.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p2 
				ON p.codprov2 = p2.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p3 
				ON p.codprov3 = p3.codruc) AS pmp 
			ON pmp.CodPro = pro.CodPro 
			INNER JOIN Tabla_M_Detalle AS TbUnd 
			ON pro.UndPro = TbUnd.Cod_Argumento 
			AND (TbUnd.Cod_Tabla = 'TUND') 
			INNER JOIN Tabla_M_Detalle AS TbCol 
			ON pro.ColPro = TbCol.Cod_Argumento 
			AND (TbCol.Cod_Tabla = 'TCOL') 
			INNER JOIN Tabla_M_Detalle AS TbTal 
			ON pro.TalPro = TbTal.Cod_Argumento 
			AND (TbTal.Cod_Tabla = 'TTAL') 
			WHERE $where $searchWhere";

		$stmtFiltered = Conexion::conectar()->prepare($sqlFiltered);
		if ($sublinea !== "") {
			$stmtFiltered->bindValue(":sublinea", $sublinea, PDO::PARAM_STR);
		}
		if (!empty($search)) {
			$searchParam = "%$search%";
			$stmtFiltered->bindParam(":search1", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search2", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search3", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search4", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search5", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search6", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search7", $searchParam, PDO::PARAM_STR);
		}
		$stmtFiltered->execute();
		$filteredRecords = $stmtFiltered->fetch(PDO::FETCH_ASSOC)["total"];

		// Consulta principal con paginación
		$sql = "SELECT DISTINCT 
			'MP' AS Mp,
			pro.CodAlt,
			pro.CodFab,
			pro.DesPro,
			pro.CodPro,
			pro.CodAlm01,
			pro.Stk_Min,
			pro.Stk_Max,
			TbUnd.Des_Corta AS Unidad,
			TbCol.Cod_Argumento AS CodigoColor,
			TbCol.Des_Larga AS Color,
			pro.TalPro,
			TbTal.Des_larga AS Talla,
			pmp.Proveedores,
			IFNULL(pmp.precio, 0.000000) AS precio,
			CASE
			WHEN pro.estpro = 1 THEN 'Activo'
			ELSE 'Inactivo'END AS estpro
		FROM
			Producto pro 
			LEFT JOIN 
			(SELECT DISTINCT 
				p.codpro,
				CONCAT_WS(
				' - ',
				IFNULL(p1.razpro, ''),
				IFNULL(p2.razpro, ''),
				IFNULL(p3.razpro, '')
				) AS proveedores,
				GREATEST(
				p.preprov1,
				p.preprov2,
				p.preprov2
				) AS precio 
			FROM
				preciomp p 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p1 
				ON p.codprov1 = p1.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p2 
				ON p.codprov2 = p2.codruc 
				LEFT JOIN 
				(SELECT DISTINCT 
					codruc,
					razpro 
				FROM
					proveedor prov) AS p3 
				ON p.codprov3 = p3.codruc) AS pmp 
			ON pmp.CodPro = pro.CodPro 
			INNER JOIN Tabla_M_Detalle AS TbUnd 
			ON pro.UndPro = TbUnd.Cod_Argumento 
			AND (TbUnd.Cod_Tabla = 'TUND') 
			INNER JOIN Tabla_M_Detalle AS TbCol 
			ON pro.ColPro = TbCol.Cod_Argumento 
			AND (TbCol.Cod_Tabla = 'TCOL') 
			INNER JOIN Tabla_M_Detalle AS TbTal 
			ON pro.TalPro = TbTal.Cod_Argumento 
			AND (TbTal.Cod_Tabla = 'TTAL') 
			WHERE $where $searchWhere
			GROUP BY pro.CodPro 
			ORDER BY $orderBy $orderDir
			LIMIT :start, :length";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($sublinea !== "") {
			$stmt->bindValue(":sublinea", $sublinea, PDO::PARAM_STR);
		}

		if (!empty($search)) {
			$searchParam = "%$search%";
			$stmt->bindParam(":search1", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search2", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search3", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search4", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search5", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search6", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search7", $searchParam, PDO::PARAM_STR);
		}

		$stmt->bindParam(":start", $start, PDO::PARAM_INT);
		$stmt->bindParam(":length", $length, PDO::PARAM_INT);

		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return [
			"data" => $data,
			"recordsTotal" => $totalRecords,
			"recordsFiltered" => $filteredRecords
		];

		$stmt->close();
		$stmt = null;
	}

	/*
	* ÓRDENES DE COMPRA PENDIENTES POR MATERIA PRIMA
	*/
	static public function mdlOrdenesCompraPorMp($codpro)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
			ocd.nro,
			DATE(oc.FecEmi) AS fecemi,
			DATE(oc.Fecllegada) AS fecllegada,
			IFNULL(prov.RazPro, '') AS proveedor,
			ocd.canpro AS cantidad,
			ocd.cantni AS saldo,
			ocd.estac,
			ocd.PrePro AS precio
		FROM ocompra oc
		INNER JOIN ocomdet ocd
			ON oc.nro = ocd.nro
		LEFT JOIN proveedor AS prov
			ON prov.codruc = ocd.codruc
		WHERE ocd.codpro = :codpro
			AND ocd.estac IN ('ABI', 'PAR')
			AND ocd.estoco = '03'
		ORDER BY oc.FecEmi DESC, ocd.nro DESC");

		$stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
	* ÓRDENES DE SERVICIO PENDIENTES POR MATERIA PRIMA (origen o destino)
	*/
	static public function mdlOrdenesServicioPorMp($codpro)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
			osd.Nro AS nro,
			DATE_FORMAT(os.FecEmi, '%Y-%m-%d') AS fecemi,
			DATE_FORMAT(os.FecEnt, '%Y-%m-%d') AS fecent,
			osd.CodProOrigen AS codpro_origen,
			p1.DesPro AS des_origen,
			osd.CodProDestino AS codpro_destino,
			p2.DesPro AS des_destino,
			osd.CantidadIni AS cantidad,
			osd.Saldo AS saldo,
			osd.EstOS AS estos,
			CASE
				WHEN osd.CodProOrigen = :codpro1 THEN 'ORIGEN'
				WHEN osd.CodProDestino = :codpro2 THEN 'DESTINO'
				ELSE ''
			END AS rol
		FROM oserviciodet osd
		INNER JOIN oservicio os
			ON os.Nro = osd.Nro
		INNER JOIN Producto p1
			ON p1.CodPro = osd.CodProOrigen
		INNER JOIN Producto p2
			ON p2.CodPro = osd.CodProDestino
		WHERE (osd.CodProOrigen = :codpro3 OR osd.CodProDestino = :codpro4)
			AND osd.EstReg = '1'
			AND osd.EstOS IN ('ABI', 'PAR')
		ORDER BY os.FecEmi DESC, osd.Nro DESC");

		$stmt->bindParam(":codpro1", $codpro, PDO::PARAM_STR);
		$stmt->bindParam(":codpro2", $codpro, PDO::PARAM_STR);
		$stmt->bindParam(":codpro3", $codpro, PDO::PARAM_STR);
		$stmt->bindParam(":codpro4", $codpro, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
