<?php

require_once "conexion.php";

class ModeloMaestras
{

    /* 
    * LISTAR TABLA CABECERA
    */
    static public function mdlMostrarMaestrasCabecera()
    {

        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                                                cod_tabla,
                                                descripcion,
                                                lon_campo,
                                                tip_campo,
                                                tip_generacion 
                                            FROM
                                                Tabla_M_Maestra 
                                            WHERE Estado = '1' AND cod_tabla NOT IN ('INVI')
                                            ORDER BY Descripcion ASC");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
    * LISTAR TABLA DETALLE
    */
    static public function mdlMostrarMaestrasDetalle($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                                                    cod_tabla,
                                                    cod_argumento,
                                                    des_larga,
                                                    des_corta,
                                                    valor_1,
                                                    valor_2,
                                                    valor_3,
                                                    valor_4,
                                                    valor_5,
                                                    valor_6,
                                                    valor_7,
                                                    valor_8,
                                                    valor_9 
                                                FROM
                                                    tabla_m_detalle 
                                                WHERE cod_Tabla = :valor
                                                -- ORDER BY cod_argumento ASC
                                                -- LIMIT 0,2192
                                                ");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
    *MOSTRAR CORRELATIVO
    */
    static public function mdlMostrarCorrelativo($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
        tm.cod_tabla,
        tm.descripcion,
        tm.lon_campo,
        LPAD(
          MAX(CAST(td.cod_Argumento AS SIGNED)) + 1,
          tm.lon_campo,
          '0'
        ) AS correlativo 
      FROM
        tabla_m_maestra tm 
        LEFT JOIN tabla_m_detalle td 
          ON tm.cod_tabla = td.cod_tabla 
      WHERE tm.cod_tabla = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->Fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
    *MOSTRAR CORRELATIVO
    */
    static public function mdlMostrarSubLineas()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                    cod_tabla,
                    des_larga,
                    des_corta 
                FROM
                    tabla_m_detalle 
                WHERE cod_tabla = 'tlin'");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
    *MOSTRAR CORRELATIVO SUBLINEA
    */
    static public function mdlMostrarCorrelativoSubLinea($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                        des_corta,
                        LPAD(MAX(valor_3) + 1, 3, '0') AS correlativo 
                    FROM
                        tabla_m_detalle 
                    WHERE cod_tabla = 'tsub' 
                        AND des_corta = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->Fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
    * CREAR SUBLINEAS
    */
    static public function ctrCrearSubLinea($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO tabla_m_detalle (
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
                                                ) 
                                                VALUES
                                                    (
                                                    :cod_argumento,
                                                    :cod_local,
                                                    :cod_entidad,
                                                    :cod_tabla,
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
                                                    )");

        $stmt->bindParam(":cod_argumento", $datos["cod_argumento"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_local", $datos["cod_local"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_entidad", $datos["cod_entidad"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_tabla", $datos["cod_tabla"], PDO::PARAM_STR);
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

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    *MOSTRAR SUBLINEA
    */
    static public function mdlMostrarSubLineaEditar($valor, $valor1)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                                                cod_argumento,
                                                cod_tabla,
                                                des_larga,
                                                des_corta,
                                                valor_1,
                                                valor_2,
                                                valor_3,
                                                valor_4,
                                                valor_5 
                                            FROM
                                                tabla_m_detalle 
                                            WHERE cod_tabla = :valor
                                                AND cod_argumento = :valor1");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        $stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->Fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
    *EDITAR SUB LINEA
    */
    static public function mdlEditarSubLinea($datos)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE 
                                                    tabla_m_detalle 
                                                SET
                                                    des_larga = UPPER(:des_larga),
                                                    des_corta = UPPER(:des_corta),
                                                    valor_1 = :valor_1,
                                                    valor_2 = :valor_2,
                                                    valor_3 = :valor_3,
                                                    valor_4 = :valor_4,
                                                    valor_5 = :valor_5,
                                                    fecmod = :fecmod,
                                                    usumod = :usumod,
                                                    pcmod = :pcmod 
                                                WHERE cod_tabla = :cod_tabla 
                                                    AND cod_argumento = :cod_argumento");

        $stmt->bindParam(":cod_tabla", $datos["cod_tabla"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_argumento", $datos["cod_argumento"], PDO::PARAM_STR);

        $stmt->bindParam(":des_larga", $datos["des_larga"], PDO::PARAM_STR);
        $stmt->bindParam(":des_corta", $datos["des_corta"], PDO::PARAM_STR);
        $stmt->bindParam(":valor_1", $datos["valor_1"], PDO::PARAM_STR);
        $stmt->bindParam(":valor_2", $datos["valor_2"], PDO::PARAM_STR);
        $stmt->bindParam(":valor_3", $datos["valor_3"], PDO::PARAM_STR);
        $stmt->bindParam(":valor_4", $datos["valor_4"], PDO::PARAM_STR);
        $stmt->bindParam(":valor_5", $datos["valor_5"], PDO::PARAM_STR);
        $stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
        $stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
        $stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * LISTAR TABLA CABECERA
    */
    static public function mdlMostrarProdCabecera($tipo, $documento)
    {

        if ($documento == null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                                                c.tipo,
                                                c.documento,
                                                valor1 AS total,
                                                DATE(fecreg) AS fecha 
                                            FROM
                                                maestra_prod_cab c 
                                            ORDER BY c.documento DESC");

            $stmt->execute();

            return $stmt->fetchAll();
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                                                c.tipo,
                                                c.documento,
                                                valor1 AS total,
                                                DATE(fecreg) AS fecha 
                                            FROM
                                                maestra_prod_cab c 
                                                WHERE c.tipo = 'PCUA' AND c.documento='000007'
                                            ORDER BY c.documento DESC");

            $stmt->execute();

            return $stmt->fetch();
        }



        $stmt->close();

        $stmt = null;
    }

    /* 
    * LISTAR TABLA DETALLE
    */
    static public function mdlMostrarProdDetalle($tipo, $documento)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
        d.tipo,
        d.documento,
        d.codigo,
        p.codfab,
        p.despro,
        p.color,
        p.talla,
        p.unidad,
        d.valor1 AS cantidad
      FROM
        maestra_prod_det d 
        LEFT JOIN 
          (SELECT DISTINCT 
            CodPro AS codpro,
            CodFab AS codfab,
            DesPro AS despro,
            Stk_Act,
            CodAlm01 AS stock,
            Stk_Min,
            Stk_Max,
            CosPro,
            TbUnd.Des_Corta AS unidad,
            TbTal.Des_Larga AS talla,
            TbCol.Des_Larga AS color 
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
            AND LEFT(pro.fampro, 3) = RIGHT(:tipo ,3)) AS p 
          ON d.codigo = p.codpro 
      WHERE d.tipo = :tipo 
        AND d.documento = :documento
        AND d.condicion = '+'
        AND d.estado = 1
        AND d.visible= 1");

        $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
    * LISTAR TABLA DETALLE
    */
    static public function mdlMostrarProdDetalle2($codigo, $documento, $tipo)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
      d.tipo,
      d.documento,
      d.codigo,
      d.valor1 AS cantidad
    FROM
      maestra_prod_det d
    WHERE d.codigo = :codigo 
      AND d.documento = :documento
      AND d.tipo = :tipo");

        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * LISTAR TABLA DETALLE
  */
    static public function mdlTraerSaldos($mesG)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                  t.cod_argumento AS correlativo,
                  t.cod_tabla AS anno,
                  t.des_corta AS cod_mes,
                  t.des_larga AS nom_mes,
                  FORMAT(valor_1, 2) AS saldo_inicial,
                  FORMAT(valor_2, 2) AS ingresos,
                  FORMAT(valor_3, 2) AS egresos,
                  FORMAT(valor_4, 2) AS saldo_actual,
                  valor_4 AS actual,
                  valor_5 AS estado 
                FROM
                  tabla_m_detalle t 
                WHERE t.cod_tabla = YEAR(NOW()) 
                  AND t.des_corta = :mesG");

        $stmt->bindParam(":mesG", $mesG, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }


    /* 
    * CREAR SUBLINEAS
    */
    static public function mdlActualizarCorrelativo($codigo)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                            correlativos 
                        SET
                            actual = actual + 1 
                        WHERE codigo = '$codigo'");


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();
        $stmt = null;
    }
}
