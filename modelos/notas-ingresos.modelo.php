<?php

require_once "conexion.php";

class ModeloNotasIngresos
{


    /*=============================================
	RANGO FECHAS
	=============================================*/
    static public function mdlRangoFechasNotasIngresos($fechaInicial, $fechaFinal)
    {

        if ($fechaInicial == "null") {

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
                                            DATE(nea.fecreg) AS fecreg,
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
                                            codalm,
                                            nea.usureg 
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
                                            AND YEAR(nea.fecreg) = YEAR(NOW())
                                        ORDER BY nNea DESC");

            $stmt->execute();

            return $stmt->fetchAll();
        } else if ($fechaInicial == $fechaFinal) {

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
            DATE(nea.fecreg) AS fecreg,
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
            codalm,
            nea.usureg 
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
            AND DATE(nea.fecreg) like '%$fechaFinal%'
        ORDER BY nNea DESC");

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
                DATE(nea.fecreg) AS fecreg,
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
                codalm,
                nea.usureg  
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
                AND DATE(nea.fecreg) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'
            ORDER BY nNea DESC");
            } else {


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
                DATE(nea.fecreg) AS fecreg,
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
                codalm,
                nea.usureg  
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
                AND DATE(nea.fecreg) BETWEEN '$fechaInicial' AND '$fechaFinal'
            ORDER BY nNea DESC");
            }

            $stmt->execute();

            return $stmt->fetchAll();
        }
    }

    /* 
	* MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OC
	*/
    static public function mdlMostrarMPOC($empresa, $oc)
    {

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
            pmp1.RazPro1 AS Proveedor,
            pmp1.CodRuc1 AS codruc
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
            pmp2.RazPro2 AS Proveedor,
            pmp2.CodRuc2 AS codruc
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
            pmp3.RazPro3 AS Proveedor,
            pmp3.CodRuc3 AS codruc
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
            prov.RazPro AS Proveedor,
            ocd.CodRuc AS codruc
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
                                                Stk_Act as CodAlm01,
                                                pro.Unidad,
                                                pro.Color,
                                                pro.ColPro,
                                                ocd.PrePro AS precio,
                                                '0.000000' AS preciocigv,
                                                ocd.CantNI AS CanNI,
                                                estac,
                                                ocd.Nro,
                                                prov.RazPro AS Proveedor,
                                                ocd.codruc 
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
    static public function mdlDocNI()
    {

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
    static public function mdlOCProv($valor)
    {

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

    /* 
	* MP EN OC O SUELTA
	*/
    static public function mdlTraerMpOC($codpro, $orden, $codruc)
    {

        if ($orden == "null" || $orden == "") {

            $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                                                        pro.codpro,
                                                        pro.codfab,
                                                        despro,
                                                        CONCAT(
                                                            despro,
                                                            ' - ',
                                                            Tabla_M_Detalle_4.Des_Larga,
                                                            ' / ',
                                                            Tabla_M_Detalle_2.Des_Corta
                                                        ) AS descripcion,
                                                        stk_act,
                                                        codalm01,
                                                        Tabla_M_Detalle_2.Des_Corta AS unidad,
                                                        Tabla_M_Detalle_4.Des_Larga AS color,
                                                        pro.colpro,
                                                        IFNULL(pmp1.PreProv1, 0.000000) AS precio,
                                                        IFNULL(pmp1.ConPreProv1, 0.000000) preciocigv,
                                                        '0' AS canni,
                                                        NULL AS estac,
                                                        NULL AS nro,
                                                        pmp1.RazPro1 AS proveedor,
                                                        pmp1.CodRuc1 AS codruc 
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
                                                        AND pmp1.CodRuc1 = :codruc 
                                                        AND pro.codpro = :codpro 
                                                    UNION
                                                    ALL 
                                                    SELECT DISTINCT 
                                                        pro.CodPro,
                                                        pro.CodFab,
                                                        DesPro,
                                                        CONCAT(
                                                            despro,
                                                            ' - ',
                                                            Tabla_M_Detalle_4.Des_Larga,
                                                            ' / ',
                                                            Tabla_M_Detalle_2.Des_Corta
                                                        ) AS descripcion,                                                        
                                                        Stk_Act,
                                                        CodAlm01,
                                                        Tabla_M_Detalle_2.Des_Corta AS Unidad,
                                                        Tabla_M_Detalle_4.Des_Larga AS Color,
                                                        pro.ColPro,
                                                        IFNULL(pmp2.PreProv2, 0.000000) AS precio,
                                                        IFNULL(pmp2.ConPreProv2, 0.000000) preciocigv,
                                                        '0' AS CanNI,
                                                        NULL AS estac,
                                                        NULL AS Nro,
                                                        pmp2.RazPro2 AS Proveedor,
                                                        pmp2.CodRuc2 AS codruc 
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
                                                        AND pmp2.CodRuc2 = :codruc 
                                                        AND pro.codpro = :codpro 
                                                    UNION
                                                    ALL 
                                                    SELECT DISTINCT 
                                                        pro.CodPro,
                                                        pro.CodFab,
                                                        DesPro,
                                                        CONCAT(
                                                            despro,
                                                            ' - ',
                                                            Tabla_M_Detalle_4.Des_Larga,
                                                            ' / ',
                                                            Tabla_M_Detalle_2.Des_Corta
                                                        ) AS descripcion,                                                        
                                                        Stk_Act,
                                                        CodAlm01,
                                                        Tabla_M_Detalle_2.Des_Corta AS Unidad,
                                                        Tabla_M_Detalle_4.Des_Larga AS Color,
                                                        pro.ColPro,
                                                        IFNULL(pmp3.PreProv3, 0.000000) AS precio,
                                                        IFNULL(pmp3.ConPreProv3, 0.000000) preciocigv,
                                                        '0' AS CanNI,
                                                        NULL AS estac,
                                                        NULL AS Nro,
                                                        pmp3.RazPro3 AS Proveedor,
                                                        pmp3.CodRuc3 AS codruc 
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
                                                        AND pmp3.CodRuc3 = :codruc 
                                                        AND pro.codpro = :codpro");

            $stmt->bindParam(":codruc", $codruc, PDO::PARAM_STR);
            $stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
          ocd.codpro,
          pro.codfab AS codfab,
          pro.despro AS despro,
          CONCAT(
            pro.despro,
            ' - ',
            pro.color,
            ' / ',
            pro.unidad
          ) AS descripcion,
          stk_act AS stk_act,
          pro.unidad AS unidad,
          pro.color AS color,
          pro.colpro AS colpro,
          ocd.prepro AS precio,
          '0.000000' AS preciocigv,
          ocd.CantNI AS canni,
          estac,
          ocd.nro,
          prov.RazPro AS proveedor
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
            AND ocd.CodRuc = :codruc 
            AND ocd.Nro = :orden 
            AND ocd.codpro = :codpro");

            $stmt->bindParam(":codruc", $codruc, PDO::PARAM_STR);
            $stmt->bindParam(":orden", $orden, PDO::PARAM_STR);
            $stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        }

        $stmt->close();

        $stmt = null;
    }

    /* 
    *MOSTRAR CORRELATIVO SUBLINEA
    */
    static public function mdlMostrarCorrelativoNotaIngreso()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                    LPAD(MAX(nnea) + 1, 6, '0') AS correlativo 
                  FROM
                    nea");

        $stmt->execute();

        return $stmt->Fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR NOTA DE INGRESO - CABECERA
  */
    static public function mdlGuardarNotaIngresoCab($datos)
    {

        $sql = "INSERT INTO nea (
      cod_local,
      cod_entidad,
      codruc,
      tnea,
      snea,
      nnea,
      trguia,
      serguia,
      nroguia,
      fecemi,
      mo,
      obser,
      pigv,
      subtotal,
      igv,
      total,
      trdcto,
      srdcto,
      nrdcto,
      fecven,
      tipoc,
      seroc,
      nrooc,
      codalm,
      estreg,
      fecreg,
      usureg,
      pcreg
    ) 
    VALUES
      (
        :cod_local,
        :cod_entidad,
        :codruc,
        :tnea,
        :snea,
        :nnea,
        :trguia,
        :serguia,
        :nroguia,
        :fecemi,
        :mo,
        :obser,
        :pigv,
        :subtotal,
        :igv,
        :total,
        :trdcto,
        :srdcto,
        :nrdcto,
        :fecven,
        :tipoc,
        :seroc,
        :nrooc,
        :codalm,
        :estreg,
        :fecreg,
        :usureg,
        :pcreg
      )";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
        $stmt->bindParam(":codruc", $datos["codruc"], PDO::PARAM_STR);
        $stmt->bindParam(":tnea", $datos["tnea"], PDO::PARAM_STR);
        $stmt->bindParam(":snea", $datos["snea"], PDO::PARAM_STR);
        $stmt->bindParam(":nnea", $datos["nnea"], PDO::PARAM_STR);
        $stmt->bindParam(":trguia", $datos["trguia"], PDO::PARAM_STR);
        $stmt->bindParam(":serguia", $datos["serguia"], PDO::PARAM_STR);
        $stmt->bindParam(":nroguia", $datos["nroguia"], PDO::PARAM_STR);
        $stmt->bindParam(":fecemi", $datos["fecemi"], PDO::PARAM_STR);
        $stmt->bindParam(":mo", $datos["mo"], PDO::PARAM_STR);
        $stmt->bindParam(":obser", $datos["obser"], PDO::PARAM_STR);
        $stmt->bindParam(":pigv", $datos["pigv"], PDO::PARAM_STR);
        $stmt->bindParam(":subtotal", $datos["subtotal"], PDO::PARAM_STR);
        $stmt->bindParam(":igv", $datos["igv"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":trdcto", $datos["trdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":srdcto", $datos["srdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":nrdcto", $datos["nrdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":fecven", $datos["fecven"], PDO::PARAM_STR);
        $stmt->bindParam(":tipoc", $datos["tipoc"], PDO::PARAM_STR);
        $stmt->bindParam(":seroc", $datos["seroc"], PDO::PARAM_STR);
        $stmt->bindParam(":nrooc", $datos["nrooc"], PDO::PARAM_STR);
        $stmt->bindParam(":codalm", $datos["codalm"], PDO::PARAM_STR);
        $stmt->bindParam(":estreg", $datos["estreg"], PDO::PARAM_STR);
        $stmt->bindParam(":codalm", $datos["codalm"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    /* 
  * CREAR NOTA DE INGRESO - CABECERA
  */
    static public function mdlGuardarNotaIngresoDet($datos)
    {

        $sql = "INSERT INTO neadet (
      cod_local,
      cod_entidad,
      item,
      codruc,
      tnea,
      snea,
      nnea,
      ndoc,
      cansol,
      presol,
      codpro,
      codalm,
      p_unitario,
      coscompra,
      total,
      estreg,
      fecreg,
      usureg,
      pcreg,
      salpro,
      excpro,
      cantni,
      fecemi
    ) 
    VALUES
      (
        :cod_local,
        :cod_entidad,
        :item,
        :codruc,
        :tnea,
        :snea,
        :nnea,
        :ndoc,
        :cansol,
        :presol,
        :codpro,
        :codalm,
        :p_unitario,
        :coscompra,
        :total,
        :estreg,
        :fecreg,
        :usureg,
        :pcreg,
        :salpro,
        :excpro,
        :cantni,
        :fecemi
      )";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
        $stmt->bindParam(":item", $datos["item"], PDO::PARAM_STR);
        $stmt->bindParam(":codruc", $datos["codruc"], PDO::PARAM_STR);
        $stmt->bindParam(":tnea", $datos["tnea"], PDO::PARAM_STR);
        $stmt->bindParam(":snea", $datos["snea"], PDO::PARAM_STR);
        $stmt->bindParam(":nnea", $datos["nnea"], PDO::PARAM_STR);
        $stmt->bindParam(":ndoc", $datos["ndoc"], PDO::PARAM_STR);
        $stmt->bindParam(":cansol", $datos["cansol"], PDO::PARAM_STR);
        $stmt->bindParam(":presol", $datos["presol"], PDO::PARAM_STR);
        $stmt->bindParam(":codpro", $datos["codpro"], PDO::PARAM_STR);
        $stmt->bindParam(":codalm", $datos["codalm"], PDO::PARAM_STR);
        $stmt->bindParam(":p_unitario", $datos["p_unitario"], PDO::PARAM_STR);
        $stmt->bindParam(":coscompra", $datos["coscompra"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":estreg", $datos["estreg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
        $stmt->bindParam(":salpro", $datos["salpro"], PDO::PARAM_STR);
        $stmt->bindParam(":excpro", $datos["excpro"], PDO::PARAM_STR);
        $stmt->bindParam(":cantni", $datos["cantni"], PDO::PARAM_STR);
        $stmt->bindParam(":fecemi", $datos["fecemi"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    // Método para actualizar EL STOCK DE MP
    static public function mdlActualizarStock($codpro, $stock)
    {

        $sql = "UPDATE 
            producto 
          SET
            codalm01 = :stock 
          WHERE codpro = :codpro";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);
        $stmt->bindParam(":stock", $stock, PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    // Método para actualizar EL SALDO Y ESTADO EN OC
    static public function mdlActualizarCantOc($oc, $codpro, $estado, $cantidadRe)
    {

        $sql = "UPDATE 
                ocomdet 
              SET
                cantni = 
                CASE
                  WHEN canpro = cantni 
                  THEN canpro - :cantidadRe 
                  ELSE cantni - :cantidadRe 
                END,
                ceroc = :estado,
                estac = 
                CASE
                  WHEN :estado = 'si' 
                  THEN 'CER' 
                  WHEN :estado = '' 
                  AND canpro - :cantidadRe > 0 
                  THEN 'PAR' 
                  ELSE estac 
                END 
              WHERE nro = :oc 
                AND codpro = :codpro";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":oc", $oc, PDO::PARAM_STR);
        $stmt->bindParam(":codpro", $codpro, PDO::PARAM_STR);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindParam(":cantidadRe", $cantidadRe, PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    // Método para actualizar CABECERAS DE OC
    static public function mdlActualizarEstCab($oc)
    {

        $sql = "UPDATE 
              ocompra 
            SET
              estac = 'PAR' 
            WHERE nro = :oc";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":oc", $oc, PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	*Traer cabecera nota ingreso
	*/
    static public function mdlTraerNiCab($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                      n.nnea AS ni,
                      DATE(n.fecreg) AS emi_ni,
                      n.codruc AS cod_prov,
                      p.razpro AS nom_prov,
                      n.nrooc AS oc,
                      n.trdcto AS cod_doc_pri,
                      d2.des_larga AS tip_doc_pri,
                      n.srdcto AS ser_pri,
                      n.nrdcto AS num_pri,
                      DATE(n.fecven) AS emi_pri,
                      n.trguia AS cod_doc_sec,
                      d1.des_larga AS tip_doc_sec,
                      n.serguia AS ser_sec,
                      n.nroguia AS num_sec,
                      DATE(n.fecemi) AS emi_sec,
                      n.mo AS cod_mon,
                      m.des_larga AS moneda,
                      n.obser
                    FROM
                      nea n 
                      LEFT JOIN proveedor p 
                        ON n.codruc = p.codruc 
                      LEFT JOIN 
                        (SELECT 
                          * 
                        FROM
                          tabla_m_detalle 
                        WHERE cod_tabla = 'temi') AS d1 
                        ON n.trguia = d1.cod_argumento 
                      LEFT JOIN 
                        (SELECT 
                          * 
                        FROM
                          tabla_m_detalle 
                        WHERE cod_tabla = 'temi') AS d2 
                        ON n.trdcto = d2.cod_argumento 
                      LEFT JOIN 
                        (SELECT 
                          * 
                        FROM
                          tabla_m_detalle 
                        WHERE cod_tabla = 'tmon') AS m 
                        ON n.mo = m.cod_argumento 
                    WHERE n.nnea = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /*
	*Traer cabecera nota ingreso
	*/
    static public function mdlTraerNiDet($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
    nd.item,
    nd.codpro,
    pro.despro,
    pro.codfab,
    TbCol.Des_Larga AS color,
    tbund.des_larga AS unidad,
    nd.cansol,
    nd.p_unitario,
    nd.total,
    nd.ndoc,
    nd.salpro,
    nd.excpro 
  FROM
    NeaDet nd 
    INNER JOIN Producto AS pro 
      ON nd.CodPro = pro.CodPro 
    LEFT JOIN Tabla_M_Detalle AS TbUnd 
      ON pro.UndPro = TbUnd.Cod_Argumento 
      AND TbUnd.Cod_Tabla = 'TUND' 
    LEFT JOIN Tabla_M_Detalle AS TbCol 
      ON pro.ColPro = TbCol.Cod_Argumento 
      AND TbCol.Cod_Tabla = 'TCOL' 
    LEFT JOIN ocomdet AS ocd 
      ON ocd.Nro = nd.NDoc 
      AND ocd.CodPro = pro.CodPro 
    LEFT JOIN nea ne 
      ON ne.nNea = nd.nNea 
  WHERE nd.nNea = :valor 
  ORDER BY Item ASC");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR NOTA DE INGRESO - CABECERA
  */
    static public function mdlEditarNotaIngreso($datos)
    {

        $sql = "UPDATE 
              nea 
            SET
              trdcto = :trdcto,
              srdcto = :srdcto,
              nrdcto = :nrdcto,
              fecven = :fecven,
              trguia = :trguia,
              serguia = :serguia,
              nroguia = :nroguia,
              fecemi = :fecemi,
              obser = :obser 
            WHERE nnea = :nnea";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":nnea", $datos["nnea"], PDO::PARAM_STR);
        $stmt->bindParam(":trguia", $datos["trguia"], PDO::PARAM_STR);
        $stmt->bindParam(":serguia", $datos["serguia"], PDO::PARAM_STR);
        $stmt->bindParam(":nroguia", $datos["nroguia"], PDO::PARAM_STR);
        $stmt->bindParam(":fecemi", $datos["fecemi"], PDO::PARAM_STR);
        $stmt->bindParam(":obser", $datos["obser"], PDO::PARAM_STR);
        $stmt->bindParam(":trdcto", $datos["trdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":srdcto", $datos["srdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":nrdcto", $datos["nrdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":fecven", $datos["fecven"], PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }
    }

    // Método para actualizar EL STOCK DE MP
    static public function mdlCerrarOC($datos)
    {

        $sql = "UPDATE 
              ocompra oc 
              LEFT JOIN ocomdet od 
                ON oc.nro = od.nro SET oc.estac = 'CER',
              oc.ceroc = 'SI',
              oc.feccer = :feccer,
              oc.usucer = :usucer,
              oc.pccer = :pccer,
              od.estac = 'CER',
              od.ceroc = 'SI' 
            WHERE oc.nro = :oc";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":oc", $datos["oc"], PDO::PARAM_STR);
        $stmt->bindParam(":feccer", $datos["feccer"], PDO::PARAM_STR);
        $stmt->bindParam(":usucer", $datos["usucer"], PDO::PARAM_STR);
        $stmt->bindParam(":pccer", $datos["pccer"], PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    /* 
	* AQUI INICIA NOTA DE INGRESO POR SERVICIO
	! AQUI INICIA NOTA DE INGRESO POR SERVICIO
	? AQUI INICIA NOTA DE INGRESO POR SERVICIO
	*/

    /*=============================================
	RANGO FECHAS
	=============================================*/
    static public function mdlRangoFechasNotasIngresosOS($fechaInicial, $fechaFinal)
    {

        if ($fechaInicial == "null") {

            $stmt = Conexion::conectar()->prepare("SELECT 
                                              neo.tNeaOs AS tneaos,
                                              neo.CodRuc AS codruc,
                                              prov.RazPro AS proveedor,
                                              nNeaOs AS nneaos,
                                              DATE(neo.FecEmi) AS fecemi,
                                              NroOs AS nroos,
                                              NroDcto AS nrodcto,
                                              neo.usureg  
                                            FROM
                                              nea_os neo 
                                              LEFT JOIN proveedor prov 
                                                ON prov.CodRuc = neo.CodRuc 
                                            WHERE neo.EstReg = 'P'
                                                    AND YEAR(neo.FecEmi) = YEAR(NOW())");

            $stmt->execute();

            return $stmt->fetchAll();
        } else if ($fechaInicial == $fechaFinal) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                                            neo.tNeaOs AS tneaos,
                                            neo.CodRuc AS codruc,
                                            prov.RazPro AS proveedor,
                                            nNeaOs AS nneaos,
                                            DATE(neo.FecEmi) AS fecemi,
                                            NroOs AS nroos,
                                            NroDcto AS nrodcto,
                                            neo.usureg  
                                          FROM
                                            nea_os neo 
                                            LEFT JOIN proveedor prov 
                                              ON prov.CodRuc = neo.CodRuc 
                                          WHERE neo.EstReg = 'P' 
                                            AND DATE(fecemi) LIKE '%$fechaFinal%'");

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
                                            neo.tNeaOs AS tneaos,
                                            neo.CodRuc AS codruc,
                                            prov.RazPro AS proveedor,
                                            nNeaOs AS nneaos,
                                            DATE(neo.FecEmi) AS fecemi,
                                            NroOs AS nroos,
                                            NroDcto AS nrodcto,
                                            neo.usureg  
                                          FROM
                                            nea_os neo 
                                            LEFT JOIN proveedor prov 
                                              ON prov.CodRuc = neo.CodRuc 
                                          WHERE neo.EstReg = 'P'
                                                    AND DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
            } else {


                $stmt = Conexion::conectar()->prepare("SELECT 
                                        neo.tNeaOs AS tneaos,
                                        neo.CodRuc AS codruc,
                                        prov.RazPro AS proveedor,
                                        nNeaOs AS nneaos,
                                        DATE(neo.FecEmi) AS fecemi,
                                        NroOs AS nroos,
                                        NroDcto AS nrodcto,
                                        neo.usureg  
                                      FROM
                                        nea_os neo 
                                        LEFT JOIN proveedor prov 
                                          ON prov.CodRuc = neo.CodRuc 
                                      WHERE neo.EstReg = 'P'
                AND DATE(fecemi) BETWEEN '$fechaInicial' AND '$fechaFinal'");
            }

            $stmt->execute();

            return $stmt->fetchAll();
        }
    }

    /* 
	* MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OS
	*/
    static public function mdlMostrarMPOS()
    {


        $stmt = Conexion::conectar()->prepare("SELECT 
                                            osd.Nro AS nro,
                                            CodProOrigen AS codproorigen,
                                            p1.DesPro AS desori,
                                            tcol.Des_Larga AS colorori,
                                            tund.Des_Corta AS undori,
                                            CodProDestino AS codprodestino,
                                            p2.DesPro AS desdes,
                                            tcol2.Des_Larga AS colordes,
                                            tund.Des_Corta AS unddes,
                                            Saldo AS saldo 
                                          FROM
                                            oserviciodet osd 
                                            INNER JOIN Producto p1 
                                              ON p1.CodPro = osd.CodProOrigen 
                                            INNER JOIN Producto p2 
                                              ON p2.CodPro = osd.CodProDestino 
                                            LEFT JOIN tabla_m_detalle tcol 
                                              ON tcol.Cod_Argumento = p1.ColPro 
                                            LEFT JOIN tabla_m_detalle tcol2 
                                              ON tcol2.Cod_Argumento = p2.ColPro 
                                            LEFT JOIN tabla_m_detalle tund 
                                              ON tund.Cod_Argumento = p1.UndPro 
                                            LEFT JOIN tabla_m_detalle tund2 
                                              ON tund2.Cod_Argumento = p2.UndPro 
                                          WHERE (
                                              tcol.Cod_Tabla = 'TCOL' 
                                              OR tcol.Cod_Tabla IS NULL
                                            ) 
                                            AND (
                                              tund.Cod_Tabla = 'TUND' 
                                              OR tund.Cod_Tabla IS NULL
                                            ) 
                                            AND (
                                              tcol2.Cod_Tabla = 'TCOL' 
                                              OR tcol2.Cod_Tabla IS NULL
                                            ) 
                                            AND (
                                              tund2.Cod_Tabla = 'TUND' 
                                              OR tund2.Cod_Tabla IS NULL
                                            ) 
                                            AND osd.EstReg = '1' 
                                            AND osd.EstOS IN ('ABI', 'PAR')");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
	* MOSTRAR MP PARA NOTA DE INGRESO EN OS AL HACER CLICK
	*/
    static public function mdlTraerMPOS($ordser, $codori, $coddes)
    {


        $stmt = Conexion::conectar()->prepare("SELECT 
                                              osd.Nro AS nro,
                                              CodProOrigen AS codproorigen,
                                              p1.DesPro AS desori,
                                              tcol.Des_Larga AS colorori,
                                              tund.Des_Corta AS undori,
                                              CodProDestino AS codprodestino,
                                              p2.DesPro AS desdes,
                                              tcol2.Des_Larga AS colordes,
                                              tund.Des_Corta AS unddes,
                                              Saldo AS saldo,
                                              desstk AS descontar  
                                            FROM
                                              oserviciodet osd 
                                              INNER JOIN Producto p1 
                                                ON p1.CodPro = osd.CodProOrigen 
                                              INNER JOIN Producto p2 
                                                ON p2.CodPro = osd.CodProDestino 
                                              LEFT JOIN tabla_m_detalle tcol 
                                                ON tcol.Cod_Argumento = p1.ColPro 
                                              LEFT JOIN tabla_m_detalle tcol2 
                                                ON tcol2.Cod_Argumento = p2.ColPro 
                                              LEFT JOIN tabla_m_detalle tund 
                                                ON tund.Cod_Argumento = p1.UndPro 
                                              LEFT JOIN tabla_m_detalle tund2 
                                                ON tund2.Cod_Argumento = p2.UndPro 
                                            WHERE (
                                                tcol.Cod_Tabla = 'TCOL' 
                                                OR tcol.Cod_Tabla IS NULL
                                              ) 
                                              AND (
                                                tund.Cod_Tabla = 'TUND' 
                                                OR tund.Cod_Tabla IS NULL
                                              ) 
                                              AND (
                                                tcol2.Cod_Tabla = 'TCOL' 
                                                OR tcol2.Cod_Tabla IS NULL
                                              ) 
                                              AND (
                                                tund2.Cod_Tabla = 'TUND' 
                                                OR tund2.Cod_Tabla IS NULL
                                              ) 
                                              AND osd.EstReg = '1' 
                                              AND osd.EstOS IN ('ABI', 'PAR') 
                                              AND nro = :ordser 
                                              AND codproorigen = :codori
                                              AND codprodestino = :coddes");

        $stmt->bindParam(":ordser", $ordser, PDO::PARAM_STR);
        $stmt->bindParam(":codori", $codori, PDO::PARAM_STR);
        $stmt->bindParam(":coddes", $coddes, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
    *MOSTRAR CORRELATIVO SUBLINEA
    */
    static public function mdlMostrarCorrelativoNotaIngresoServicio()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                LPAD(MAX(nneaos) + 1, 6, '0') AS correlativo 
              FROM
                nea_os");

        $stmt->execute();

        return $stmt->Fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR NOTA DE INGRESO - CABECERA OS
  */
    static public function mdlGuardarNotaIngresoCabServicio($datos)
    {

        $sql = "INSERT INTO nea_os (
      cod_local,
      cod_entidad,
      codruc,
      tneaos,
      sneaos,
      nneaos,
      fecemi,
      serdcto,
      nrodcto,
      estreg,
      fecreg,
      usureg,
      pcreg
    ) 
    VALUES
      (
        :cod_local,
        :cod_entidad,
        :codruc,
        :tneaos,
        :sneaos,
        :nneaos,
        :fecemi,
        :serdcto,
        :nrodcto,
        :estreg,
        :fecreg,
        :usureg,
        :pcreg
      )";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
        $stmt->bindParam(":codruc", $datos["codruc"], PDO::PARAM_STR);
        $stmt->bindParam(":tneaos", $datos["tneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":sneaos", $datos["sneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":nneaos", $datos["nneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":fecemi", $datos["fecemi"], PDO::PARAM_STR);
        $stmt->bindParam(":serdcto", $datos["serdcto"], PDO::PARAM_STR);
        $stmt->bindParam(":nrodcto", $datos["nrodcto"], PDO::PARAM_STR);
        $stmt->bindParam(":estreg", $datos["estreg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    /* 
  * CREAR NOTA DE INGRESO - DETALLE SERVICIO
  */
    static public function mdlGuardarNotaIngresoDetServicio($datos)
    {

        $sql = "INSERT INTO nea_os_det (
      cod_local,
      cod_entidad,
      tneaos,
      sneaos,
      nneaos,
      nroos,
      fecemi,
      item,
      codruc,
      codproorigen,
      codprodestino,
      cansol,
      estreg,
      fecreg,
      usureg,
      pcreg
    ) 
    VALUES
      (
        :cod_local,
        :cod_entidad,
        :tneaos,
        :sneaos,
        :nneaos,
        :nroos,
        :fecemi,
        :item,
        :codruc,
        :codproorigen,
        :codprodestino,
        :cansol,
        :estreg,
        :fecreg,
        :usureg,
        :pcreg
      )";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
        $stmt->bindParam(":tneaos", $datos["tneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":sneaos", $datos["sneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":nneaos", $datos["nneaos"], PDO::PARAM_STR);
        $stmt->bindParam(":nroos", $datos["nroos"], PDO::PARAM_STR);
        $stmt->bindParam(":fecemi", $datos["fecemi"], PDO::PARAM_STR);
        $stmt->bindParam(":item", $datos["item"], PDO::PARAM_STR);
        $stmt->bindParam(":codruc", $datos["codruc"], PDO::PARAM_STR);
        $stmt->bindParam(":codproorigen", $datos["codproorigen"], PDO::PARAM_STR);
        $stmt->bindParam(":codprodestino", $datos["codprodestino"], PDO::PARAM_STR);
        $stmt->bindParam(":cansol", $datos["cansol"], PDO::PARAM_STR);
        $stmt->bindParam(":estreg", $datos["estreg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt = null;
    }

    /* 
    *MOSTRAR MP EN ORDEN DE SERVICIO
    */
    static public function mdlMpServicio($nro, $codori, $coddes)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
      nro,
      fecemi,
      item,
      codproorigen,
      codprodestino,
      cantidadini,
      saldo,
      despacho,
      estos,
      desstk 
    FROM
      oserviciodet 
    WHERE nro = :nro 
      AND codproorigen = :codproorigen
      AND codprodestino = :codprodestino");

        $stmt->bindParam(":nro", $nro, PDO::PARAM_STR);
        $stmt->bindParam(":codproorigen", $codori, PDO::PARAM_STR);
        $stmt->bindParam(":codprodestino", $coddes, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    // Método para actualizar saldo, despacho y estado
    static public function mdlActualizarServicio($nro, $codori, $coddes, $saldo, $despacho, $cerrar)
    {

        $sql = "UPDATE 
                oserviciodet 
              SET
                saldo = :saldo,
                despacho = :despacho,
                estos = 
                CASE
                  WHEN :cerrar = 'SI' 
                  THEN 'CER' 
                  ELSE estos 
                END
              WHERE nro = :nro 
                AND codproorigen = :codproorigen 
                AND codprodestino = :codprodestino";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":nro", $nro, PDO::PARAM_STR);
        $stmt->bindParam(":codproorigen", $codori, PDO::PARAM_STR);
        $stmt->bindParam(":codprodestino", $coddes, PDO::PARAM_STR);
        $stmt->bindParam(":saldo", $saldo, PDO::PARAM_STR);
        $stmt->bindParam(":despacho", $despacho, PDO::PARAM_STR);
        $stmt->bindParam(":cerrar", $cerrar, PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt = null;
    }

    // Método para actualizar saldo, despacho y estado
    static public function mdlActualizarCabOrdServicio($nro)
    {

        $sql = "UPDATE 
              oservicio 
            SET
              estos = 'PAR' 
            WHERE nro = :nro ";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":nro", $nro, PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt = null;
    }

    /*
	*Traer cabecera nota ingreso
	*/
    static public function mdlTraerNiSCab($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
    neo.tNeaOs AS tneaos,
    neo.CodRuc AS codruc,
    prov.RazPro AS proveedor,
    nNeaOs AS nneaos,
    DATE(neo.FecEmi) AS fecemi,
    NroOs AS nroos,
    NroDcto AS nrodcto,
    neo.usureg 
  FROM
    nea_os neo 
    LEFT JOIN proveedor prov 
      ON prov.CodRuc = neo.CodRuc 
  WHERE neo.EstReg = 'P' 
    AND nNeaOs = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /*
	*Traer cabecera nota ingreso
	*/
    static public function mdlTraerNiSDet($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
    Item,
    CodProOrigen,
    pro.DesPro AS DesProOrigen,
    CodProDestino,
    pro1.DesPro AS Descripcion,
    CanSol,
    tb1.Des_Larga AS Color,
    tb3.Des_Larga AS Color2,
    tb2.Des_Corta AS Unidad,
    nod.nroos 
  FROM
    nea_os_det nod 
    INNER JOIN Producto pro 
      ON nod.CodProOrigen = pro.CodPro 
    INNER JOIN Producto pro1 
      ON nod.CodProDestino = pro1.CodPro 
    INNER JOIN Tabla_M_Detalle AS tb1 
      ON pro.ColPro = tb1.Cod_Argumento 
    INNER JOIN Tabla_M_Detalle AS tb3 
      ON pro1.ColPro = tb3.Cod_Argumento 
    INNER JOIN Tabla_M_Detalle AS tb2 
      ON pro.UndPro = tb2.Cod_Argumento 
  WHERE (
      tb1.Cod_Tabla = 'TCOL' 
      OR tb1.Cod_Tabla IS NULL
    ) 
    AND (
      tb2.Cod_Tabla = 'TUND' 
      OR tb2.Cod_Tabla IS NULL
    ) 
    AND (
      tb3.Cod_Tabla = 'TCOL' 
      OR tb3.Cod_Tabla IS NULL
    ) 
    AND nod.nNeaOs = :valor
  ORDER BY nod.Item ASC");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /**
     * Precio unitario válido de una línea de nota de ingreso (compra).
     */
    static private function sqlPrecioLineaNi($alias = "d")
    {
        return "CASE
            WHEN IFNULL({$alias}.coscompra, 0) > 0 THEN {$alias}.coscompra
            WHEN IFNULL({$alias}.p_unitario, 0) > 0 THEN {$alias}.p_unitario
            WHEN IFNULL({$alias}.presol, 0) > 0 THEN {$alias}.presol
            ELSE 0
        END";
    }

    static private function sqlFiltroNiCostoValida($n = "n", $d = "d")
    {
        $precio = self::sqlPrecioLineaNi($d);
        return "IFNULL({$n}.estreg, '') <> 'A'
            AND IFNULL({$d}.estreg, '') <> 'A'
            AND {$d}.cansol > 0
            AND ({$precio}) > 0";
    }

    /**
     * Último costo por MP: promedio ponderado de la última nota de ingreso
     * no anulada con precio válido (fecha de emisión, luego número).
     * Las notas de servicio no guardan precio; no entran.
     *
     * @param array|null $codpros Si viene, limita a esos códigos.
     */
    static public function mdlUltimosCostosNotaIngreso($codpros = null)
    {
        $precio = self::sqlPrecioLineaNi("d");
        $filtro = self::sqlFiltroNiCostoValida("n", "d");
        $filtro2 = self::sqlFiltroNiCostoValida("n2", "d2");

        $whereIn2 = "";
        $params = array();
        if (is_array($codpros)) {
            $limpios = array();
            foreach ($codpros as $cod) {
                $cod = trim((string) $cod);
                if ($cod !== "") {
                    $limpios[$cod] = $cod;
                }
            }
            $limpios = array_values($limpios);
            if (count($limpios) < 1) {
                return array();
            }
            $ph = array();
            foreach ($limpios as $i => $cod) {
                $key = ":c" . $i;
                $ph[] = $key;
                $params[$key] = $cod;
            }
            $whereIn2 = " AND d2.codpro IN (" . implode(",", $ph) . ")";
        }

        $sql = "SELECT
                p.codpro,
                IFNULL(p.despro, '') AS despro,
                IFNULL(p.codfab, '') AS codfab,
                IFNULL(p.cospro, 0) AS costo_actual,
                u.costo_propuesto,
                u.nnea,
                u.fecemi,
                u.cantidad
            FROM (
                SELECT
                    d.codpro,
                    n.nnea,
                    DATE(n.fecemi) AS fecemi,
                    SUM(d.cansol) AS cantidad,
                    SUM(d.cansol * ({$precio})) / SUM(d.cansol) AS costo_propuesto
                FROM neadet d
                INNER JOIN nea n
                    ON n.tnea = d.tnea
                    AND n.snea = d.snea
                    AND n.nnea = d.nnea
                INNER JOIN (
                    SELECT
                        d2.codpro,
                        MAX(CONCAT(DATE_FORMAT(n2.fecemi, '%Y%m%d'), LPAD(n2.nnea, 6, '0'))) AS k
                    FROM neadet d2
                    INNER JOIN nea n2
                        ON n2.tnea = d2.tnea
                        AND n2.snea = d2.snea
                        AND n2.nnea = d2.nnea
                    WHERE {$filtro2}
                        {$whereIn2}
                    GROUP BY d2.codpro
                ) ult
                    ON ult.codpro = d.codpro
                    AND CONCAT(DATE_FORMAT(n.fecemi, '%Y%m%d'), LPAD(n.nnea, 6, '0')) = ult.k
                WHERE {$filtro}
                GROUP BY d.codpro, n.nnea, DATE(n.fecemi)
            ) u
            INNER JOIN producto p
                ON p.codpro = u.codpro
            WHERE IFNULL(p.estpro, '1') = '1'
            ORDER BY p.codpro";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $valor) {
            $stmt->bindValue($key, $valor, PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * MPs activas cuyo costo de ficha no coincide con el de su última nota.
     */
    static public function mdlDescuadresCostoMp()
    {
        $filas = self::mdlUltimosCostosNotaIngreso(null);
        if ($filas === false) {
            return false;
        }

        $out = array();
        foreach ($filas as $f) {
            $actual = (float) $f["costo_actual"];
            $propuesto = (float) $f["costo_propuesto"];
            if (abs($actual - $propuesto) <= 0.00005) {
                continue;
            }
            $out[] = $f;
        }

        return $out;
    }
}
