<?php

require_once "conexion.php";

class ModeloNotasIngresos{

    
	/*=============================================
	RANGO FECHAS
	=============================================*/	

	static public function mdlRangoFechasNotasIngresos($fechaInicial, $fechaFinal){

		if($fechaInicial == "null"){

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                                            TB1.Des_Larga AS tipodoc,
                                            nrooc,
                                            nroguiaasociada,
                                            Tabla_M_Detalle.des_larga,
                                            Proveedor.razpro,
                                            Nea.cod_local,
                                            Nea.cod_entidad,
                                            Nea.codruc,
                                            tnea,
                                            snea,
                                            nnea,
                                            DATE(FecEmi) AS fecemi,
                                            tcambio,
                                            mo,
                                            obser,
                                            pigv,
                                            subtotal,
                                            igv,
                                            total,
                                            trdcto,
                                            srdcto,
                                            nrdcto,
                                            tipoc,
                                            seroc,
                                            nrooc,
                                            codalm 
                                        FROM
                                            Nea,
                                            Tabla_M_Detalle,
                                            Tabla_M_Detalle AS TB1,
                                            Proveedor 
                                        WHERE Tabla_M_Detalle.Cod_Tabla = 'TMON' 
                                            AND TB1.Cod_Tabla = 'TEMI' 
                                            AND Tabla_M_Detalle.Cod_Argumento = Nea.Mo 
                                            AND Proveedor.CodRuc = Nea.CodRuc 
                                            AND TB1.Cod_Argumento = Nea.trDcto 
                                            AND Nea.EstReg NOT LIKE 'A' 
                                            AND Nea.nNea NOT IN 
                                            (SELECT 
                                            NIGuiaAsociada 
                                            FROM
                                            Nea 
                                            WHERE Nea.EstReg = 'P' 
                                            AND Nea.`NroGuiaAsociada` != '') 
                                            AND YEAR(fecemi) IN ('2020', '2021') 
                                        ORDER BY nNea DESC");

			$stmt -> execute();

			return $stmt -> fetchAll();	


		}else if($fechaInicial == $fechaFinal){

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
            TB1.Des_Larga AS tipodoc,
            nrooc,
            nroguiaasociada,
            Tabla_M_Detalle.des_larga,
            Proveedor.razpro,
            Nea.cod_local,
            Nea.cod_entidad,
            Nea.codruc,
            tnea,
            snea,
            nnea,
            DATE(FecEmi) AS fecemi,
            tcambio,
            mo,
            obser,
            pigv,
            subtotal,
            igv,
            total,
            trdcto,
            srdcto,
            nrdcto,
            tipoc,
            seroc,
            nrooc,
            codalm 
        FROM
            Nea,
            Tabla_M_Detalle,
            Tabla_M_Detalle AS TB1,
            Proveedor 
        WHERE Tabla_M_Detalle.Cod_Tabla = 'TMON' 
            AND TB1.Cod_Tabla = 'TEMI' 
            AND Tabla_M_Detalle.Cod_Argumento = Nea.Mo 
            AND Proveedor.CodRuc = Nea.CodRuc 
            AND TB1.Cod_Argumento = Nea.trDcto 
            AND Nea.EstReg NOT LIKE 'A' 
            AND Nea.nNea NOT IN 
            (SELECT 
            NIGuiaAsociada 
            FROM
            Nea 
            WHERE Nea.EstReg = 'P' 
            AND Nea.`NroGuiaAsociada` != '') 
            AND DATE(fecemi) like '%$fechaFinal%'
        ORDER BY nNea DESC");

			$stmt -> bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetchAll();

		}else{

			$fechaActual = new DateTime();
			$fechaActual ->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2 ->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if($fechaFinalMasUno == $fechaActualMasUno){

				$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                TB1.Des_Larga AS tipodoc,
                nrooc,
                nroguiaasociada,
                Tabla_M_Detalle.des_larga,
                Proveedor.razpro,
                Nea.cod_local,
                Nea.cod_entidad,
                Nea.codruc,
                tnea,
                snea,
                nnea,
                DATE(FecEmi) AS fecemi,
                tcambio,
                mo,
                obser,
                pigv,
                subtotal,
                igv,
                total,
                trdcto,
                srdcto,
                nrdcto,
                tipoc,
                seroc,
                nrooc,
                codalm 
            FROM
                Nea,
                Tabla_M_Detalle,
                Tabla_M_Detalle AS TB1,
                Proveedor 
            WHERE Tabla_M_Detalle.Cod_Tabla = 'TMON' 
                AND TB1.Cod_Tabla = 'TEMI' 
                AND Tabla_M_Detalle.Cod_Argumento = Nea.Mo 
                AND Proveedor.CodRuc = Nea.CodRuc 
                AND TB1.Cod_Argumento = Nea.trDcto 
                AND Nea.EstReg NOT LIKE 'A' 
                AND Nea.nNea NOT IN 
                (SELECT 
                NIGuiaAsociada 
                FROM
                Nea 
                WHERE Nea.EstReg = 'P' 
                AND Nea.`NroGuiaAsociada` != '') 
                AND DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'
            ORDER BY nNea DESC");

			}else{


				$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                TB1.Des_Larga AS tipodoc,
                nrooc,
                nroguiaasociada,
                Tabla_M_Detalle.des_larga,
                Proveedor.razpro,
                Nea.cod_local,
                Nea.cod_entidad,
                Nea.codruc,
                tnea,
                snea,
                nnea,
                DATE(FecEmi) AS fecemi,
                tcambio,
                mo,
                obser,
                pigv,
                subtotal,
                igv,
                total,
                trdcto,
                srdcto,
                nrdcto,
                tipoc,
                seroc,
                nrooc,
                codalm 
            FROM
                Nea,
                Tabla_M_Detalle,
                Tabla_M_Detalle AS TB1,
                Proveedor 
            WHERE Tabla_M_Detalle.Cod_Tabla = 'TMON' 
                AND TB1.Cod_Tabla = 'TEMI' 
                AND Tabla_M_Detalle.Cod_Argumento = Nea.Mo 
                AND Proveedor.CodRuc = Nea.CodRuc 
                AND TB1.Cod_Argumento = Nea.trDcto 
                AND Nea.EstReg NOT LIKE 'A' 
                AND Nea.nNea NOT IN 
                (SELECT 
                NIGuiaAsociada 
                FROM
                Nea 
                WHERE Nea.EstReg = 'P' 
                AND Nea.`NroGuiaAsociada` != '') 
                AND DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinal'
            ORDER BY nNea DESC");

			}
		
			$stmt -> execute();

			return $stmt -> fetchAll();

		}

	}

	/* 
	* MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OC
	*/
	static public function mdlMostrarMPOC($empresa, $oc){

		if ($oc == "null" || $oc == "") {

			$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                                            pro.CodPro,
                                            pro.CodFab,
                                            DesPro,
                                            Stk_Act,
                                            CodAlm01,
                                            Tabla_M_Detalle_2.Des_Corta AS Unidad,
                                            Tabla_M_Detalle_4.Des_Larga AS Color,
                                            pro.ColPro,
                                            IFNULL(pmp1.PreProv1, 0.000000) AS precio,
                                            IFNULL(pmp1.ConPreProv1, 0.000000) preciocigv,
                                            NULL AS CanNI,
                                            NULL AS estac,
                                            NULL AS Nro,
                                            pmp1.RazPro1 AS Proveedor 
                                        FROM
                                            Producto AS pro 
                                            LEFT JOIN 
                                            (SELECT 
                                                Pro1.CodRuc AS CodRuc1,
                                                Pro1.RazPro AS RazPro1,
                                                pmp.PreProv1 AS PreProv1,
                                                (
                                                CASE
                                                    WHEN MonProv1 = 'NUEVOS SOLES' 
                                                    THEN '1' 
                                                    WHEN MonProv1 = 'DOLARES AMERICANOS' 
                                                    THEN '2' 
                                                    ELSE '' 
                                                END
                                                ) AS MonedaProv1,
                                                MonProv1,
                                                (pmp.PreProv1 + pmp.PreProv1 * 0.18) AS ConPreProv1,
                                                pmp.CodPro 
                                            FROM
                                                preciomp pmp 
                                                LEFT JOIN Proveedor AS Pro1 
                                                ON Pro1.CodRuc = pmp.CodProv1) AS pmp1 
                                            ON pmp1.CodPro = pro.CodPro 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_2 
                                            ON pro.UndPro = Tabla_M_Detalle_2.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_2.Cod_Tabla = 'TUND'
                                            ) 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_4 
                                            ON pro.ColPro = Tabla_M_Detalle_4.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_4.Cod_Tabla = 'TCOL'
                                            ) 
                                        WHERE pro.EstPro = '1' 
                                            AND pmp1.RazPro1 != '' 
                                            AND pmp1.CodRuc1 = $empresa 
                                        UNION
                                        ALL 
                                        SELECT DISTINCT 
                                            pro.CodPro,
                                            pro.CodFab,
                                            DesPro,
                                            Stk_Act,
                                            CodAlm01,
                                            Tabla_M_Detalle_2.Des_Corta AS Unidad,
                                            Tabla_M_Detalle_4.Des_Larga AS Color,
                                            pro.ColPro,
                                            IFNULL(pmp2.PreProv2, 0.000000) AS precio,
                                            IFNULL(pmp2.ConPreProv2, 0.000000) preciocigv,
                                            NULL AS CanNI,
                                            NULL AS estac,
                                            NULL AS Nro,
                                            pmp2.RazPro2 AS Proveedor 
                                        FROM
                                            Producto AS pro 
                                            LEFT JOIN 
                                            (SELECT 
                                                Pro2.CodRuc AS CodRuc2,
                                                Pro2.RazPro AS RazPro2,
                                                pmp.PreProv2 AS PreProv2,
                                                (
                                                CASE
                                                    WHEN MonProv2 = 'NUEVOS SOLES' 
                                                    THEN '1' 
                                                    WHEN MonProv2 = 'DOLARES AMERICANOS' 
                                                    THEN '2' 
                                                    ELSE '' 
                                                END
                                                ) AS MonedaProv2,
                                                MonProv2,
                                                (pmp.PreProv2 + pmp.PreProv2 * 0.18) AS ConPreProv2,
                                                pmp.CodPro 
                                            FROM
                                                preciomp pmp 
                                                LEFT JOIN Proveedor AS Pro2 
                                                ON Pro2.CodRuc = pmp.CodProv2) AS pmp2 
                                            ON pmp2.CodPro = pro.CodPro 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_2 
                                            ON pro.UndPro = Tabla_M_Detalle_2.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_2.Cod_Tabla = 'TUND'
                                            ) 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_4 
                                            ON pro.ColPro = Tabla_M_Detalle_4.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_4.Cod_Tabla = 'TCOL'
                                            ) 
                                        WHERE pro.EstPro = '1' 
                                            AND pmp2.RazPro2 != '' 
                                            AND pmp2.CodRuc2 = $empresa 
                                        UNION
                                        ALL 
                                        SELECT DISTINCT 
                                            pro.CodPro,
                                            pro.CodFab,
                                            DesPro,
                                            Stk_Act,
                                            CodAlm01,
                                            Tabla_M_Detalle_2.Des_Corta AS Unidad,
                                            Tabla_M_Detalle_4.Des_Larga AS Color,
                                            pro.ColPro,
                                            IFNULL(pmp3.PreProv3, 0.000000) AS precio,
                                            IFNULL(pmp3.ConPreProv3, 0.000000) preciocigv,
                                            NULL AS CanNI,
                                            NULL AS estac,
                                            NULL AS Nro,
                                            pmp3.RazPro3 AS Proveedor 
                                        FROM
                                            Producto AS pro 
                                            LEFT JOIN 
                                            (SELECT 
                                                Pro3.CodRuc AS CodRuc3,
                                                Pro3.RazPro AS RazPro3,
                                                pmp.PreProv3 AS PreProv3,
                                                (
                                                CASE
                                                    WHEN MonProv3 = 'NUEVOS SOLES' 
                                                    THEN '1' 
                                                    WHEN MonProv3 = 'DOLARES AMERICANOS' 
                                                    THEN '2' 
                                                    ELSE '' 
                                                END
                                                ) AS MonedaProv3,
                                                MonProv3,
                                                (pmp.PreProv3 + pmp.PreProv3 * 0.18) AS ConPreProv3,
                                                pmp.CodPro 
                                            FROM
                                                preciomp pmp 
                                                LEFT JOIN Proveedor AS Pro3 
                                                ON Pro3.CodRuc = pmp.CodProv3) AS pmp3 
                                            ON pmp3.CodPro = pro.CodPro 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_2 
                                            ON pro.UndPro = Tabla_M_Detalle_2.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_2.Cod_Tabla = 'TUND'
                                            ) 
                                            INNER JOIN Tabla_M_Detalle AS Tabla_M_Detalle_4 
                                            ON pro.ColPro = Tabla_M_Detalle_4.Cod_Argumento 
                                            AND (
                                                Tabla_M_Detalle_4.Cod_Tabla = 'TCOL'
                                            ) 
                                        WHERE pro.EstPro = '1' 
                                            AND pmp3.RazPro3 != '' 
                                            AND pmp3.CodRuc3 = $empresa 
                                        UNION
                                        ALL 
                                        SELECT 
                                            ocd.CodPro,
                                            pro.CodFab,
                                            pro.DesPro,
                                            NULL AS StkAct,
                                            NULL AS CodAlm01,
                                            pro.Unidad,
                                            pro.Color,
                                            pro.ColPro,
                                            ocd.PrePro AS precio,
                                            '0.000000' AS preciocigv,
                                            ocd.CantNI,
                                            estac,
                                            ocd.Nro,
                                            prov.RazPro AS Proveedor 
                                        FROM
                                            ocomdet ocd 
                                            LEFT JOIN 
                                            (SELECT 
                                                pro.CodPro,
                                                pro.CodFab,
                                                pro.DesPro,
                                                TbUnd.Des_Corta AS Unidad,
                                                TbCol.Des_Larga AS Color,
                                                pro.ColPro 
                                            FROM
                                                producto pro 
                                                INNER JOIN Tabla_M_Detalle AS TbUnd 
                                                ON pro.UndPro = TbUnd.Cod_Argumento 
                                                AND (TbUnd.Cod_Tabla = 'TUND') 
                                                INNER JOIN Tabla_M_Detalle AS TbCol 
                                                ON pro.ColPro = TbCol.Cod_Argumento 
                                                AND (TbCol.Cod_Tabla = 'TCOL') 
                                            WHERE pro.EstPro = '1') AS pro 
                                            ON pro.CodPro = ocd.CodPro 
                                            LEFT JOIN Proveedor AS prov 
                                            ON prov.CodRuc = ocd.CodRuc 
                                        WHERE estac IN ('ABI', 'PAR') 
                                            AND ocd.EstOco = '03' 
                                            AND ocd.CodRuc = $empresa");

			$stmt->bindParam(":empresa", $empresa, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
            ocd.CodPro,
            pro.CodFab,
            pro.DesPro,
            Stk_Act,
            pro.Unidad,
            pro.Color,
            pro.ColPro,
            ocd.PrePro AS precio,
            '0.000000' AS preciocigv,
            ocd.CantNI AS CanNI,
            estac,
            ocd.Nro,
            prov.RazPro AS Proveedor 
          FROM
            ocomdet ocd 
            LEFT JOIN 
              (SELECT 
                pro.CodPro,
                pro.CodFab,
                pro.DesPro,
                pro.codalm01 AS Stk_Act,
                TbUnd.Des_Corta AS Unidad,
                TbCol.Des_Larga AS Color,
                pro.ColPro 
              FROM
                producto pro 
                INNER JOIN Tabla_M_Detalle AS TbUnd 
                  ON pro.UndPro = TbUnd.Cod_Argumento 
                  AND (TbUnd.Cod_Tabla = 'TUND') 
                INNER JOIN Tabla_M_Detalle AS TbCol 
                  ON pro.ColPro = TbCol.Cod_Argumento 
                  AND (TbCol.Cod_Tabla = 'TCOL') 
              WHERE pro.EstPro = '1') AS pro 
              ON pro.CodPro = ocd.CodPro 
            LEFT JOIN Proveedor AS prov 
              ON prov.CodRuc = ocd.CodRuc 
          WHERE estac IN ('ABI', 'PAR') 
            AND ocd.EstOco = '03' 
                                                AND ocd.CodRuc = :empresa 
                                                AND ocd.Nro= :oc");

            $stmt->bindParam(":empresa", $empresa, PDO::PARAM_STR);
            $stmt->bindParam(":oc", $oc, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*
	* TIPOS DE DOC PARA NI
	*/
	static public function mdlDocNI(){

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                                    cod_argumento,
                                    cod_tabla,
                                    des_larga,
                                    des_corta,
                                    valor_1 
                                FROM
                                    Tabla_M_Detalle 
                                WHERE Cod_Tabla = 'TEMI' 
                                    AND Cod_Argumento IN ('12', '14', '15', '21') 
                                ORDER BY Cod_Argumento DESC ");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}  
    
	/*
	* TIPOS DE DOC PARA NI
	*/
	static public function mdlOCProv($valor){

		$stmt = Conexion::conectar()->prepare("SELECT 
                                        nro,
                                        DATE(fecemi) AS fecemi 
                                    FROM
                                        ocompra 
                                    WHERE codruc = :empresa 
                                        AND estoco = '03' 
                                        AND estac IN ('abi', 'par')");

        $stmt->bindParam(":empresa", $valor, PDO::PARAM_STR);                                        

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}     

}