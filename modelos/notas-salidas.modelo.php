<?php

require_once "conexion.php";

class ModeloNotasSalidas
{


	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasNotasSalidas($fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			vc.tip,
			vc.ser,
			vc.nro,
			vc.EstNota,
			vc.UsuReg,
			DATE(fecemi) AS fecemi,
			c.razcli,
			a.des_larga AS almacen 
		  FROM
			ventas_cab vc 
			LEFT JOIN clientes c 
			  ON vc.ruc = c.ruc 
			LEFT JOIN tabla_m_detalle a 
			  ON vc.codalm = a.cod_argumento 
		  WHERE YEAR(fecemi) = YEAR(NOW())
			AND vc.EstVta NOT LIKE 'A' 
			AND cod_tabla = 'talm' 
		  ORDER BY Nro DESC ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			vc.tip,
			vc.ser,
			vc.nro,
			vc.EstNota,
			vc.UsuReg,
			DATE(fecemi) AS fecemi,
			c.razcli,
			a.des_larga AS almacen 
		  FROM
			ventas_cab vc 
			LEFT JOIN clientes c 
			  ON vc.ruc = c.ruc 
			LEFT JOIN 
			  (SELECT 
				cod_argumento,
				cod_tabla,
				des_larga
			  FROM
				tabla_m_detalle 
			  WHERE cod_tabla = 'talm') a 
			  ON vc.codalm = a.cod_argumento 
		  WHERE DATE(fecemi) like '%$fechaFinal%' AND vc.EstVta NOT LIKE 'A' ORDER BY Nro DESC");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$fechaActual = new DateTime();
			$fechaActual->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if ($fechaFinalMasUno == $fechaActualMasUno) {

				$stmt = Conexion::conectar()->prepare("SELECT 
				vc.tip,
				vc.ser,
				vc.nro,
				vc.EstNota,
				vs.UsuReg,
				DATE(fecemi) AS fecemi,
				c.razcli,
				a.des_larga AS almacen 
			  FROM
				ventas_cab vc 
				LEFT JOIN clientes c 
				  ON vc.ruc = c.ruc 
				LEFT JOIN 
				  (SELECT 
					cod_argumento,
					cod_tabla,
					des_larga
				  FROM
					tabla_m_detalle 
				  WHERE cod_tabla = 'talm') a 
				  ON vc.codalm = a.cod_argumento 
			  WHERE DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'   
			  AND vc.EstVta NOT LIKE 'A' ORDER BY Nro DESC");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				vc.tip,
				vc.ser,
				vc.nro,
				vc.EstNota,
				vc.UsuReg,
				DATE(fecemi) AS fecemi,
				c.razcli,
				a.des_larga AS almacen 
			  FROM
				ventas_cab vc 
				LEFT JOIN clientes c 
				  ON vc.ruc = c.ruc 
				LEFT JOIN 
				  (SELECT 
					cod_argumento,
					cod_tabla,
					des_larga
				  FROM
					tabla_m_detalle 
				  WHERE cod_tabla = 'talm') a 
				  ON vc.codalm = a.cod_argumento 
			  WHERE DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinal'
			  AND vc.EstVta NOT LIKE 'A' ORDER BY Nro DESC");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*=============================================
	MOSTRAR CLIENTES PARA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarClientesNotas()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
													CodCli,
													Ruc,
													RazCli,
													DirCli 
												FROM
													Clientes 
												WHERE CodCli NOT LIKE '000000' ");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR TIPO DE ALMACENES PARA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarTipoAlmacen()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
													Cod_Argumento AS id_almacen,
													Des_Larga AS almacen,
													Des_Corta 
												FROM
													Tabla_m_detalle 
												WHERE cod_tabla = 'TALM' 
													AND Cod_Argumento IN ('01', '02')");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR TIPO DE MOTIVO PARA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarMotivoNota()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
													Cod_Argumento,
													Des_Larga 
												FROM
													Tabla_M_Detalle 
												WHERE Cod_Tabla = 'TEMI' 
													AND Cod_Argumento NOT LIKE ('17') 
													AND Cod_Argumento NOT LIKE ('09') 
													AND Cod_Argumento NOT LIKE ('10') 
													AND Cod_Argumento NOT LIKE ('06') 
													AND Cod_Argumento NOT LIKE ('00') 
												ORDER BY Des_Larga DESC ");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR DESTINO PARA LA MATERIA EN LA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarDestinoNota()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
													Cod_Argumento,
													Des_Larga,
													Des_Corta 
												FROM
													Tabla_m_detalle 
												WHERE Cod_Tabla = 'TAMP' ");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR DESTINO PARA LA MATERIA EN LA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarDestinoNotaB()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT
		cc.cod_area,
		cc.nombre_area
	   FROM
		 centro_costos cc 
	   WHERE cc.cod_area IN (
		   '005',
		   '014',
		   '015',
		   '016',
		   '017',
		   '100',
		   '101',
		   '102',
		   '103',
		   '104',
		   '105',
		   '106',
		   '200',
		   '201',
		   '202',
		   '203',
		   '204',
		   '205',
		   '206',
		   '207',
		   '208',
		   '209',
		   '210',
		   '211',
		   '212',
		   '213',
		   '214',
		   '215',
		   '216',
		   '217',
		   '218',
		   '219',
		   '220',
		   '221',
		   '222',
		   '223',
		   '224',
		   '225',
		   '226',
		   '227',
		   '228',
		   '229',
		   '230',
		   '231',
		   '232',
		   '233',
		   '234',
		   '235',
		   '236',
		   '237',
		   '238',
		   '239',
		   '240',
		   '241','242','243','244','245','246','247','248'
		 )");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR DESTINO PARA LA MATERIA EN LA NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarMateriasNotas()
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
												'MP' AS Mp,
												pro.CodAlt,
												pro.CodFab,
												pro.DesPro,
												pro.CodPro,
												pro.CodAlm01,
												pro.Stk_Min,
												TbUnd.Des_Corta AS Unidad,
												TbCol.Cod_Argumento AS CodigoColor,
												TbCol.Des_Larga AS Color,
												pmp.Proveedores,
												IFNULL( pmp.precio , 0.000000) AS precio  
											FROM
												Producto pro 
												LEFT JOIN 
												(SELECT 
													CodPro,
													CONCAT_WS(
													'   -   ',
													prov1.RazPro,
													prov2.RazPro,
													prov3.RazPro
													) AS Proveedores,
													GREATEST(PreProv1, PreProv2, PreProv3) AS precio  
												FROM
													preciomp pmp 
													LEFT JOIN Proveedor prov1 
													ON pmp.CodProv1 = prov1.CodRuc 
													LEFT JOIN Proveedor prov2 
													ON pmp.CodProv2 = prov2.CodRuc 
													LEFT JOIN Proveedor prov3 
													ON pmp.CodProv3 = prov3.CodRuc 
												GROUP BY pmp.CodPro) AS pmp 
												ON pmp.CodPro = pro.CodPro 
												INNER JOIN Tabla_M_Detalle AS TbUnd 
												ON pro.UndPro = TbUnd.Cod_Argumento 
												AND (TbUnd.Cod_Tabla = 'TUND') 
												INNER JOIN Tabla_M_Detalle AS TbCol 
												ON pro.ColPro = TbCol.Cod_Argumento 
												AND (TbCol.Cod_Tabla = 'TCOL') 
											WHERE pro.estpro = '1' 
											AND CodAlm01 > 0
											GROUP BY pro.CodPro 
											ORDER BY pro.CodPro DESC ;");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}


	// Método para guardar las ventas
	static public function mdlGuardarNotaSalida($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla(Tip,Ser,Nro,Cod_Local,Cod_Entidad,Ruc,FecEmi,FecVto,MdaVta,ForVta,TcVta,ImpVta,brutot,totdct,SubVta,IgvVta,TotVta,EstVta,Abono,ValVta,ObsGer,EstExp,EstGuia,CodAlm,AlmDes,CodCli,EstAte,Dia,ObsCre,EstPri,DocSal,DetDocSal,FecReg,PcReg,UsuReg,observacion) VALUES (:Tip,:Ser,:Nro,:Cod_Local,:Cod_Entidad,:Ruc,:FecEmi,:FecVto,:MdaVta,:ForVta,:TcVta,:ImpVta,:brutot,:totdct,:SubVta,:IgvVta,:TotVta,:EstVta,:Abono,:ValVta,:ObsGer,:EstExp,:EstGuia,:CodAlm,:AlmDes,:CodCli,:EstAte,:Dia,:ObsCre,:EstPri,:DocSal,:DetDocSal,:FecReg,:PcReg,:UsuReg,UPPER(:observacion))";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":Tip", $datos["Tip"], PDO::PARAM_STR);
		$stmt->bindParam(":Ser", $datos["Ser"], PDO::PARAM_STR);
		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Local", $datos["Cod_Local"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Entidad", $datos["Cod_Entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":Ruc", $datos["Ruc"], PDO::PARAM_STR);
		$stmt->bindParam(":FecEmi", $datos["FecEmi"], PDO::PARAM_STR);
		$stmt->bindParam(":FecVto", $datos["FecVto"], PDO::PARAM_STR);
		$stmt->bindParam(":MdaVta", $datos["MdaVta"], PDO::PARAM_STR);
		$stmt->bindParam(":ForVta", $datos["ForVta"], PDO::PARAM_STR);
		$stmt->bindParam(":TcVta", $datos["TcVta"], PDO::PARAM_STR);
		$stmt->bindParam(":ImpVta", $datos["ImpVta"], PDO::PARAM_STR);
		$stmt->bindParam(":brutot", $datos["brutot"], PDO::PARAM_STR);
		$stmt->bindParam(":totdct", $datos["totdct"], PDO::PARAM_STR);
		$stmt->bindParam(":SubVta", $datos["SubVta"], PDO::PARAM_STR);
		$stmt->bindParam(":IgvVta", $datos["IgvVta"], PDO::PARAM_STR);
		$stmt->bindParam(":TotVta", $datos["TotVta"], PDO::PARAM_STR);
		$stmt->bindParam(":EstVta", $datos["EstVta"], PDO::PARAM_STR);
		$stmt->bindParam(":Abono", $datos["Abono"], PDO::PARAM_STR);
		$stmt->bindParam(":ValVta", $datos["ValVta"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsGer", $datos["ObsGer"], PDO::PARAM_STR);
		$stmt->bindParam(":EstExp", $datos["EstExp"], PDO::PARAM_STR);
		$stmt->bindParam(":EstGuia", $datos["EstGuia"], PDO::PARAM_STR);
		$stmt->bindParam(":CodAlm", $datos["CodAlm"], PDO::PARAM_STR);
		$stmt->bindParam(":AlmDes", $datos["AlmDes"], PDO::PARAM_STR);
		$stmt->bindParam(":CodCli", $datos["CodCli"], PDO::PARAM_STR);
		$stmt->bindParam(":EstAte", $datos["EstAte"], PDO::PARAM_STR);
		$stmt->bindParam(":Dia", $datos["Dia"], PDO::PARAM_STR);
		$stmt->bindParam(":ObsCre", $datos["ObsCre"], PDO::PARAM_STR);
		$stmt->bindParam(":EstPri", $datos["EstPri"], PDO::PARAM_STR);
		$stmt->bindParam(":DocSal", $datos["DocSal"], PDO::PARAM_STR);
		$stmt->bindParam(":DetDocSal", $datos["DetDocSal"], PDO::PARAM_STR);
		$stmt->bindParam(":FecReg", $datos["FecReg"], PDO::PARAM_STR);
		$stmt->bindParam(":PcReg", $datos["PcReg"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuReg", $datos["UsuReg"], PDO::PARAM_STR);
		$stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	// Método para guardar las ventas
	static public function mdlGuardarDetalleNotaSalida($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla(Item,CanVta,PreVta,FecEmi,DscVta,Tip,Ser,Nro,Cod_Local,Cod_Entidad,Ruc,CodPro,SugVta,EstVta,pcosto,CenCosto,FecReg,PcReg,UsuReg,SalVta) VALUES (:Item,:CanVta,:PreVta,:FecEmi,:DscVta,:Tip,:Ser,:Nro,:Cod_Local,:Cod_Entidad,:Ruc,:CodPro,:SugVta,:EstVta,:pcosto,:CenCosto,:FecReg,:PcReg,:UsuReg,:CanVta)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":Item", $datos["Item"], PDO::PARAM_STR);
		$stmt->bindParam(":CanVta", $datos["CanVta"], PDO::PARAM_STR);
		$stmt->bindParam(":PreVta", $datos["PreVta"], PDO::PARAM_STR);
		$stmt->bindParam(":FecEmi", $datos["FecEmi"], PDO::PARAM_STR);
		$stmt->bindParam(":DscVta", $datos["DscVta"], PDO::PARAM_STR);
		$stmt->bindParam(":Tip", $datos["Tip"], PDO::PARAM_STR);
		$stmt->bindParam(":Ser", $datos["Ser"], PDO::PARAM_STR);
		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Local", $datos["Cod_Local"], PDO::PARAM_STR);
		$stmt->bindParam(":Cod_Entidad", $datos["Cod_Entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":Ruc", $datos["Ruc"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":SugVta", $datos["SugVta"], PDO::PARAM_STR);
		$stmt->bindParam(":EstVta", $datos["EstVta"], PDO::PARAM_STR);
		$stmt->bindParam(":pcosto", $datos["pcosto"], PDO::PARAM_STR);
		$stmt->bindParam(":CenCosto", $datos["CenCosto"], PDO::PARAM_STR);
		$stmt->bindParam(":FecReg", $datos["FecReg"], PDO::PARAM_STR);
		$stmt->bindParam(":PcReg", $datos["PcReg"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuReg", $datos["UsuReg"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	// Método para actualizar un dato CON EL ID
	static public function mdlActualizarUnDatoMateria($tabla, $item1, $valor1, $valor2)
	{

		$sql = "UPDATE $tabla SET $item1=:$item1 WHERE CodPro=:CodPro";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR ULTIMO NRO DE NOTA DE SALIDA
	=============================================*/

	static public function mdlMostrarUltimoNro()
	{


		$stmt = Conexion::conectar()->prepare("SELECT MAX(Nro) AS Nro FROM ventas_cab");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}


	/*=============================================
	MOSTRAR NOTAS DE SALIDA CABECERA
	=============================================*/

	static public function mdlMostrarNotaSalida($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM  $tabla  WHERE $item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM  $tabla WHERE EstVta not like 'A' ORDER BY Nro DESC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR NOTAS DE SALIDA DETALLE
	=============================================*/
	static public function mdlMostrarDetalleNotaSalida($tabla, $item, $valor)
	{

		$sql = "SELECT 
		det.*,
		p.CodFab,
		p.ColPro,
		p.DesPro,
		tbcol.Des_Larga 
	  FROM
		$tabla det 
		LEFT JOIN producto p 
		  ON p.CodPro = det.CodPro 
		INNER JOIN tabla_m_detalle AS tbcol 
		  ON p.ColPro = tbcol.cod_argumento 
		  AND (tbcol.Cod_Tabla = 'TCOL') 
	  WHERE det.$item = :$item 
	  ORDER BY item ASC ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}


	/*=============================================
	ANULA NOTA DE SALIDA
	=============================================*/

	static public function mdlAnularNotaSalida($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET UsuAnu = UPPER(:UsuAnu), PcAnu = UPPER(:PcAnu) ,FecAnu = :FecAnu,EstVta='A' WHERE Nro = :Nro");

		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuAnu", $datos["UsuAnu"], PDO::PARAM_STR);
		$stmt->bindParam(":PcAnu", $datos["PcAnu"], PDO::PARAM_STR);
		$stmt->bindParam(":FecAnu", $datos["FecAnu"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}


	/*=============================================
	MOSTRAR NOTAS DE SALIDA CABECERA
	=============================================*/

	static public function mdlMostrarDetalleNotaSalida2($tabla, $item, $valor, $item2, $valor2)
	{


		$stmt = Conexion::conectar()->prepare("SELECT SalVta FROM  $tabla  WHERE $item = :$item AND $item2 = :$item2");

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
		$stmt->bindParam(":" . $item2, $valor2, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();



		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ACTUALIZAR SALDO DE NOTA DE SALIDA
	=============================================*/

	static public function mdlActualizarSaldoNotaSalida($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET SalVta = SalVta - :SalVta WHERE Nro = :Nro AND CodPro = :CodPro");

		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":SalVta", $datos["SalVta"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	APROBAR NOTA DE SALIDA
	=============================================*/

	static public function mdlAprobarNotaSalida($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE ventas_cab SET EstNota = :EstNota , UsuMod = :UsuMod, PcMod = :PcMod, FecMod = :FecMod WHERE Nro = :Nro");

		$stmt->bindParam(":EstNota", $datos["EstNota"], PDO::PARAM_STR);
		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":UsuMod", $datos["UsuMod"], PDO::PARAM_STR);
		$stmt->bindParam(":PcMod", $datos["PcMod"], PDO::PARAM_STR);
		$stmt->bindParam(":FecMod", $datos["FecMod"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}


	/*=============================================
	ACTUALIZAR CANTIDAD SALDO
	=============================================*/

	static public function mdlActualizarCantidadSaldo($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE venta_det SET SalVta = CanVta WHERE Nro = :Nro and CodPro = :CodPro");

		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR SELECT DE NOTA DE SALIDA
	=============================================*/
	static public function mdlSelectNotaSalida($tabla, $item, $valor)
	{

		$sql = "SELECT 
		Nro,
		DATE(FecEmi) AS fecha
	  FROM
		ventas_cab 
	  WHERE $item = :$item
		AND fecemi BETWEEN CURDATE() - INTERVAL 30 DAY 
		AND CURDATE() 
	  ORDER BY Nro ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}


	/*=============================================
	MOSTRAR SELECT DEPENDIENTE DE NOTA DE SALIDA
	=============================================*/
	static public function mdlSelectDependienteNotaSalida($valor1, $valor2)
	{

		$sql = "SELECT 
		Nro,
		DATE(FecEmi) as fecha
	  FROM
		venta_det
	  WHERE CodPro = :CodPro
	  AND Nro NOT IN (:Nro) 
	  AND FecEmi BETWEEN CURDATE() - INTERVAL 30 DAY 
	  AND CURDATE()
	  AND SalVta >0
	  AND union_ns IS NULL
	  ORDER BY Nro ASC ";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":Nro", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*=============================================
	AUMENTAR CANTIDAD SALDO
	=============================================*/

	static public function mdlAumentarCantidadSaldo($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE venta_det SET SalVta = SalVta + :cantidad , union_ns = :union_ns WHERE Nro = :Nro AND CodPro = :CodPro");

		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":union_ns", $datos["union_ns"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}


	/*=============================================
	RESTAR CANTIDAD SALDO
	=============================================*/

	static public function mdlRestarCantidadSaldo($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE venta_det SET SalVta = SalVta - :cantidad, union_ns = :union_ns WHERE Nro = :Nro AND CodPro = :CodPro");

		$stmt->bindParam(":Nro", $datos["Nro"], PDO::PARAM_STR);
		$stmt->bindParam(":CodPro", $datos["CodPro"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":union_ns", $datos["union_ns"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}
}
