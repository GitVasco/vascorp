<?php

require_once "conexion.php";

class ModeloCentroCostos
{

    /*
	* Mostrar Areas
	*/
    static public function mdlMostrarAreas($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
        cod_argumento,
        cod_tabla,
        des_larga,
        des_corta 
      FROM
        tabla_m_detalle 
      WHERE cod_tabla = 'AREA' 
        AND des_Corta = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarCentroCostos($valor)
    {

        if ($valor != null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
      cc.id,
      cc.tipo_gasto,
      cc.nombre_gasto,
      cc.key_gasto,
      cc.cod_area,
      cc.nombre_area,
      IFNULL(cc.cod_caja, ' - ') AS cod_caja,
      IFNULL(cc.descripcion, ' - ') AS descripcion,
      cc.estado,
      cc.visible,
      cc.usureg,
      cc.fecreg,
      cc.pcreg,
      cc.usumod,
      cc.fecmod,
      cc.pcmod 
    FROM
      centro_costos cc 
    WHERE cc.cod_caja = :valor");

            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
          cc.id,
          cc.tipo_gasto,
          cc.nombre_gasto,
          cc.key_gasto,
          cc.cod_area,
          cc.nombre_area,
          IFNULL(cc.cod_caja, ' - ') AS cod_caja,
          IFNULL(cc.descripcion, ' - ') AS descripcion,
          cc.estado,
          cc.visible,
          cc.cc1,
          cc.cc2,
          cc.usureg,
          cc.fecreg,
          cc.pcreg,
          cc.usumod,
          cc.fecmod,
          cc.pcmod 
        FROM
          centro_costos cc 
          ORDER BY cc.key_gasto,
          cc.cod_area,
          cc.cod_caja ");

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Areas
	*/
    static public function mdlMostrarCorrelativo($tipoGasto, $area)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
        CONCAT(
          cc.cod_area,
          LPAD(
            IFNULL(MAX(RIGHT(cc.cod_caja, 2)), 0) + 1,
            2,
            '0'
          )
        ) AS correlativo 
      FROM
        centro_costos cc 
      WHERE cc.tipo_gasto = :tipoGasto
        AND CC.cod_area = :area");

        $stmt->bindParam(":tipoGasto", $tipoGasto, PDO::PARAM_STR);
        $stmt->bindParam(":area", $area, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Areas
	*/
    static public function mdlMostrarCorrelativoRecibo()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
    LPAD(MAX(cod_argumento) + 1, 6, '0') AS recibo 
  FROM
    tabla_m_detalle 
  WHERE cod_tabla = 'TREC'");

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
    * CREAR CENTRO COSTOS
    */
    static public function mdlActualizarCorrelativo()
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
      tabla_m_detalle 
    SET
      cod_argumento = LPAD(cod_argumento + 1, 6, '0') 
    WHERE cod_tabla = 'TREC'");

        $stmt->execute();
        $stmt = null;
    }

    /* 
    * CREAR CENTRO COSTOS
    */
    static public function mdlCrearCentroCostos($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO centro_costos (
                                  tipo_gasto,
                                  cod_area,
                                  cod_caja,
                                  descripcion,
                                  usureg,
                                  fecreg,
                                  pcreg
                                ) 
                                VALUES
                                  (
                                    :tipo_gasto,
                                    :cod_area,
                                    :cod_caja,
                                    UPPER(:descripcion),
                                    :usureg,
                                    :fecreg,
                                    :pcreg
                                  )");

        $stmt->bindParam(":tipo_gasto", $datos["tipo_gasto"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_area", $datos["cod_area"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_caja", $datos["cod_caja"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
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
    * ACTUALIZAR VACIOS
    */
    static public function mdlActualizarACentroCostos($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                              centro_costos 
                            SET
                              cod_caja = :cod_caja,
                              descripcion = UPPER(:descripcion),
                              usumod = :usumod,
                              fecmod = :fecmod,
                              pcmod = :pcmod 
                            WHERE tipo_gasto = :tipo_gasto 
                              AND cod_area = :cod_area 
                              AND (
                                cod_caja IS NULL 
                                OR cod_caja = ''
                              )");

        $stmt->bindParam(":tipo_gasto", $datos["tipo_gasto"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_area", $datos["cod_area"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_caja", $datos["cod_caja"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
        $stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
        $stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* PONER NOMBRES
	*/
    static public function mdlPonerNombres()
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
    centro_costos cc 
    LEFT JOIN 
      (SELECT 
        t1.cod_argumento,
        t1.cod_tabla,
        t1.des_larga,
        t1.des_corta 
      FROM
        tabla_m_detalle t1 
      WHERE t1.cod_tabla = 'tgas') t1 
      ON cc.tipo_gasto = t1.cod_argumento 
    LEFT JOIN 
      (SELECT 
        t2.cod_argumento,
        t2.cod_tabla,
        t2.des_larga,
        t2.des_corta 
      FROM
        tabla_m_detalle t2 
      WHERE t2.cod_tabla = 'area') AS t2 
      ON cc.tipo_gasto = t2.des_corta 
      AND cc.cod_area = t2.cod_argumento SET cc.nombre_gasto = t1.des_larga,
    cc.nombre_area = t2.des_larga,
    cc.key_gasto = t1.des_corta");

        $stmt->bindParam(":tipoGasto", $tipoGasto, PDO::PARAM_STR);
        $stmt->bindParam(":area", $area, PDO::PARAM_STR);

        $stmt->execute();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarGastosCaja($mes)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                    g.id,
                    DATE(g.fecha) AS fecha,
                    g.recibo,
                    g.ruc_proveedor,
                    g.proveedor,
                    g.sucursal,
                    s.des_larga AS nom_sucursal,
                    g.cod_caja,
                    cc.descripcion AS nom_caja,
                    cc.tipo_gasto,
                    cc.nombre_gasto,
                    cc.cod_area,
                    cc.nombre_area,
                    g.total,
                    g.tipo_documento,
                    d.descripcion AS nombre_documento,
                    g.documento,
                    g.solicitante,
                    g.descripcion AS desc_salida,
                    g.rubro_cancelacion,
                    g.estado 
                  FROM
                    gastos_caja g 
                    LEFT JOIN 
                      (SELECT 
                        cod_argumento,
                        des_larga 
                      FROM
                        tabla_m_detalle 
                      WHERE cod_tabla = 'tsuc') s 
                      ON g.sucursal = s.cod_argumento 
                    LEFT JOIN centro_costos cc 
                      ON g.cod_caja = cc.cod_caja 
                    LEFT JOIN 
                      (SELECT 
                        codigo,
                        descripcion 
                      FROM
                        maestrajf 
                      WHERE tipo_dato = 'tdoc' 
                        AND codigo IN ('01', '03', '09', '99')) AS d 
                      ON g.tipo_documento = d.codigo
                      WHERE YEAR(g.fecha) = YEAR(NOW()) 
                      AND MONTH(g.fecha) = $mes
                      and g.estado IN('1','3','4') AND g.visible=1");


        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarGastosCajaUsuario($usuario)
    {

        if ($usuario == "Joel Medrano" || $usuario == "Johana Requelme" || $usuario == "Yudy Rosales" || $usuario == "Judith Condor") {

            $stmt = Conexion::conectar()->prepare("SELECT 
      g.id,
      DATE(g.fecha) AS fecha,
      g.recibo,
      g.ruc_proveedor,
      g.proveedor,
      g.sucursal,
      s.des_larga AS nom_sucursal,
      g.cod_caja,
      cc.descripcion AS nom_caja,
      cc.tipo_gasto,
      cc.nombre_gasto,
      cc.cod_area,
      cc.nombre_area,
      g.total,
      g.tipo_documento,
      d.descripcion AS nombre_documento,
      g.documento,
      g.solicitante,
      g.descripcion AS desc_salida,
      g.rubro_cancelacion,
      g.usureg,
      g.estado,
      g.visible,
      g.estado
    FROM
      gastos_caja g 
      LEFT JOIN 
        (SELECT 
          cod_argumento,
          des_larga 
        FROM
          tabla_m_detalle 
        WHERE cod_tabla = 'tsuc') s 
        ON g.sucursal = s.cod_argumento 
      LEFT JOIN centro_costos cc 
        ON g.cod_caja = cc.cod_caja 
      LEFT JOIN 
        (SELECT 
          codigo,
          descripcion 
        FROM
          maestrajf 
        WHERE tipo_dato = 'tdoc' 
          AND codigo IN ('01', '03', '09', '99')) AS d 
        ON g.tipo_documento = d.codigo 
    WHERE YEAR(g.fecha) = YEAR(NOW()) 
      AND g.estado IN ('2','3','4')  
      AND g.visible = 1");

            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetchAll();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
            g.id,
            DATE(g.fecha) AS fecha,
            g.recibo,
            g.ruc_proveedor,
            g.proveedor,
            g.sucursal,
            s.des_larga AS nom_sucursal,
            g.cod_caja,
            cc.descripcion AS nom_caja,
            cc.tipo_gasto,
            cc.nombre_gasto,
            cc.cod_area,
            cc.nombre_area,
            g.total,
            g.tipo_documento,
            d.descripcion AS nombre_documento,
            g.documento,
            g.solicitante,
            g.descripcion AS desc_salida,
            g.rubro_cancelacion,
            g.estado,
            g.usureg
          FROM
            gastos_caja g 
            LEFT JOIN 
              (SELECT 
                cod_argumento,
                des_larga 
              FROM
                tabla_m_detalle 
              WHERE cod_tabla = 'tsuc') s 
              ON g.sucursal = s.cod_argumento 
            LEFT JOIN centro_costos cc 
              ON g.cod_caja = cc.cod_caja 
            LEFT JOIN 
              (SELECT 
                codigo,
                descripcion 
              FROM
                maestrajf 
              WHERE tipo_dato = 'tdoc' 
                AND codigo IN ('01', '03', '09', '99')) AS d 
              ON g.tipo_documento = d.codigo 
          WHERE YEAR(g.fecha) = YEAR(NOW()) 
            AND g.estado IN ('2','3') 
            AND g.visible = 1 
            AND g.usureg = :usuario");

            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarGastosCajaId($id)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                    g.id,
                    DATE(g.fecha) AS fecha,
                    g.recibo,
                    g.ruc_proveedor,
                    g.proveedor,
                    g.sucursal,
                    s.des_larga AS nom_sucursal,
                    g.cod_caja,
                    cc.descripcion AS nom_caja,
                    cc.tipo_gasto,
                    cc.nombre_gasto,
                    cc.cod_area,
                    cc.nombre_area,
                    g.total,
                    g.tipo_documento,
                    d.descripcion AS nombre_documento,
                    g.documento,
                    g.solicitante,
                    g.descripcion AS desc_salida,
                    g.rubro_cancelacion,
                    g.observacion 
                  FROM
                    gastos_caja g 
                    LEFT JOIN 
                      (SELECT 
                        cod_argumento,
                        des_larga 
                      FROM
                        tabla_m_detalle 
                      WHERE cod_tabla = 'tsuc') s 
                      ON g.sucursal = s.cod_argumento 
                    LEFT JOIN centro_costos cc 
                      ON g.cod_caja = cc.cod_caja 
                    LEFT JOIN 
                      (SELECT 
                        codigo,
                        descripcion 
                      FROM
                        maestrajf 
                      WHERE tipo_dato = 'tdoc' 
                        AND codigo IN ('01', '03', '09', '99')) AS d 
                      ON g.tipo_documento = d.codigo
                      WHERE g.id = $id");

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarCentroCostosCaja()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
            cc.id,
            cc.tipo_gasto,
            cc.nombre_gasto,
            cc.key_gasto,
            cc.cod_area,
            cc.nombre_area,
            IFNULL(cc.cod_caja, ' - ') AS cod_caja,
            IFNULL(cc.descripcion, ' - ') AS descripcion,
            cc.estado,
            cc.visible,
            cc.usureg,
            cc.fecreg,
            cc.pcreg,
            cc.usumod,
            cc.fecmod,
            cc.pcmod 
          FROM
            centro_costos cc 
          WHERE cc.cod_caja IS NOT NULL 
          ORDER BY cc.key_gasto,
            cc.cod_area,
            cc.cod_caja");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarTipoDoc()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
    codigo,
    descripcion 
  FROM
    maestrajf 
  WHERE tipo_dato = 'tdoc' 
    AND codigo IN ('01', '03', '09', '99')");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlCrearGastosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO gastos_caja (
                          fecha,
                          recibo,
                          ruc_proveedor,
                          proveedor,
                          sucursal,
                          cod_caja,
                          total,
                          tipo_documento,
                          documento,
                          solicitante,
                          descripcion,
                          rubro_cancelacion,
                          observacion,
                          estado,
                          usureg,
                          fecreg,
                          pcreg
                        ) 
                        VALUES
                          (
                            :fecha,
                            :recibo,
                            :ruc_proveedor,
                            UPPER(:proveedor),
                            :sucursal,
                            :cod_caja,
                            :total,
                            :tipo_documento,
                            UPPER(:documento),
                            UPPER(:solicitante),
                            UPPER(:descripcion),
                            UPPER(:rubro_cancelacion),
                            UPPER(:observacion),
                            :estado,
                            :usureg,
                            :fecreg,
                            :pcreg
                          )");

        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":recibo", $datos["recibo"], PDO::PARAM_STR);
        $stmt->bindParam(":ruc_proveedor", $datos["ruc_proveedor"], PDO::PARAM_STR);
        $stmt->bindParam(":proveedor", $datos["proveedor"], PDO::PARAM_STR);
        $stmt->bindParam(":sucursal", $datos["sucursal"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_caja", $datos["cod_caja"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        $stmt->bindParam(":solicitante", $datos["solicitante"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":rubro_cancelacion", $datos["rubro_cancelacion"], PDO::PARAM_STR);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
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
  * CREAR GASTOS DE CAJA
  */
    static public function mdlEditarGastosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                    gastos_caja 
                                  SET
                                    fecha = :fecha,
                                    recibo = :recibo,
                                    ruc_proveedor = :ruc_proveedor,
                                    proveedor = UPPER(:proveedor),
                                    sucursal = :sucursal,
                                    cod_caja = :cod_caja,
                                    total = :total,
                                    tipo_documento = :tipo_documento,
                                    documento = UPPER(:documento),
                                    solicitante = UPPER(:solicitante),
                                    descripcion = UPPER(:descripcion),
                                    rubro_cancelacion = UPPER(:rubro_cancelacion),
                                    observacion = UPPER(:observacion),
                                    usumod = :usumod,
                                    fecmod = :fecmod,
                                    pcmod = :pcmod 
                                  WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":recibo", $datos["recibo"], PDO::PARAM_STR);
        $stmt->bindParam(":ruc_proveedor", $datos["ruc_proveedor"], PDO::PARAM_STR);
        $stmt->bindParam(":proveedor", $datos["proveedor"], PDO::PARAM_STR);
        $stmt->bindParam(":sucursal", $datos["sucursal"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_caja", $datos["cod_caja"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        $stmt->bindParam(":solicitante", $datos["solicitante"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":rubro_cancelacion", $datos["rubro_cancelacion"], PDO::PARAM_STR);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        $stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
        $stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
        $stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlAnularGastosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                gastos_caja 
              SET
                estado = 0,
                visible = 0,
                usuanu = :usuanu,
                fecanu = :fecanu,
                pcanu = :pcanu 
              WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
        $stmt->bindParam(":usuanu", $datos["usuanu"], PDO::PARAM_STR);
        $stmt->bindParam(":fecanu", $datos["fecanu"], PDO::PARAM_STR);
        $stmt->bindParam(":pcanu", $datos["pcanu"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlActualizarEgresosA($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                    tabla_m_detalle t 
                                  SET
                                    t.valor_3 = t.valor_3 + :egreso,
                                    t.valor_4 = t.valor_4 - :egreso 
                                  WHERE t.cod_tabla = YEAR(NOW()) 
                                    AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":egreso", $datos["egreso"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlActualizarAlertaD($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                              diario 
                                            SET
                                              alerta = :alerta 
                                            WHERE ruc = :ruc 
                                              AND documento = :documento");

        $stmt->bindParam(":alerta", $datos["alerta"], PDO::PARAM_STR);
        $stmt->bindParam(":ruc", $datos["ruc"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * Cambiar estado
    */
    static public function mdlCambiarEstado($datosE)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                    gastos_caja 
                                  SET
                                    estado = :estado 
                                  WHERE id = :id");

        $stmt->bindParam(":id", $datosE["id"], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datosE["estado"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlActualizarEgresosB($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                    tabla_m_detalle t 
                                  SET
                                    t.valor_3 = t.valor_3 - :antiguo + :nuevo,
                                    t.valor_4 = t.valor_4 + :antiguo - :nuevo 
                                  WHERE t.cod_tabla = YEAR(NOW()) 
                                    AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":nuevo", $datos["nuevo"], PDO::PARAM_STR);
        $stmt->bindParam(":antiguo", $datos["antiguo"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlActualizarEgresosC($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                  tabla_m_detalle t 
                                SET
                                  t.valor_3 = t.valor_3 - :egreso,
                                  t.valor_4 = t.valor_4 + :egreso 
                                WHERE t.cod_tabla = YEAR(NOW()) 
                                  AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":egreso", $datos["egreso"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarMeses()
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
          valor_5 AS estado 
        FROM
          tabla_m_detalle t 
        WHERE t.cod_tabla = YEAR(NOW()) 
          AND t.valor_5 = 'ABI'");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CERRAR MES
  */
    static public function mdlCerrarMes($mes)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                      tabla_m_detalle 
                    SET
                      valor_5 = 'CER' 
                    WHERE cod_tabla = YEAR(NOW()) 
                      AND des_corta = :mes");

        $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CERRAR MES
  */
    static public function mdlAbrirMes($mes, $saldo_actual)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                      tabla_m_detalle 
                    SET
                      valor_1 = :saldo_actual,
                      valor_4 = :saldo_actual,
                      valor_5 = 'ABI' 
                    WHERE cod_tabla = YEAR(NOW()) 
                      AND des_corta = :mes+1");

        $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);
        $stmt->bindParam(":saldo_actual", $saldo_actual, PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarIngresosCaja($mes)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                            i.id,
                            DATE(i.fecha) AS fecha,
                            i.cod_ingreso,
                            (SELECT 
                              t.des_larga 
                            FROM
                              tabla_m_detalle t 
                            WHERE t.cod_tabla = 'TING' 
                              AND i.cod_ingreso = t.cod_argumento) AS nom_ingreso,
                            i.cod_responsable,
                            (SELECT 
                              t.des_larga 
                            FROM
                              tabla_m_detalle t 
                            WHERE t.cod_tabla = 'COBR' 
                              AND i.cod_responsable = t.cod_argumento) AS nom_responsable,
                            i.tipo_documento,
                            (SELECT 
                              descripcion 
                            FROM
                              maestrajf m 
                            WHERE m.tipo_dato = 'tdoc' 
                              AND i.tipo_documento = m.codigo) AS nom_documento,
                            i.documento,
                            i.total AS total,
                            i.observacion,
                            i.estado,
                            i.visible 
                          FROM
                            ingresos_caja i 
                          WHERE YEAR(i.fecha) = YEAR(NOW()) 
                            AND MONTH(i.fecha)= :mes
                            AND i.estado = 1 
                            AND visible = 1");

        $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarIngresosVendedor($mes)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
            i.cod_responsable,
            c.des_larga AS nom_responsable,
            SUM(i.total) AS total 
          FROM
            ingresos_caja i 
            LEFT JOIN 
              (SELECT 
                t.cod_argumento,
                t.cod_tabla,
                t.des_larga,
                t.des_corta 
              FROM
                tabla_m_detalle t 
              WHERE t.cod_tabla = 'COBR') c 
              ON i.cod_responsable = c.cod_argumento 
          WHERE YEAR(i.fecha) = YEAR(NOW()) 
          AND MONTH(i.fecha)= :mes
          AND i.estado = 1 
          AND i.visible = 1 
          GROUP BY i.cod_responsable");

        $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlCrearIngresosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO ingresos_caja (
                  fecha,
                  cod_ingreso,
                  cod_responsable,
                  tipo_documento,
                  documento,
                  total,
                  observacion,
                  usureg,
                  fecreg,
                  pcreg
                ) 
                VALUES
                  (
                    :fecha,
                    :cod_ingreso,
                    :cod_responsable,
                    :tipo_documento,
                    :documento,
                    :total,
                    :observacion,
                    :usureg,
                    :fecreg,
                    :pcreg
                  )");

        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_ingreso", $datos["cod_ingreso"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_responsable", $datos["cod_responsable"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlActualizarIngresosA($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                  tabla_m_detalle t 
                                SET
                                  t.valor_2 = t.valor_2 + :ingreso,
                                  t.valor_4 = t.valor_4 + :ingreso 
                                WHERE t.cod_tabla = YEAR(NOW()) 
                                  AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":ingreso", $datos["ingreso"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarIngresosCajaId($id)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                              i.id,
                              DATE(i.fecha) AS fecha,
                              i.cod_ingreso,
                              (SELECT 
                                t.des_larga 
                              FROM
                                tabla_m_detalle t 
                              WHERE t.cod_tabla = 'TING' 
                                AND i.cod_ingreso = t.cod_argumento) AS nom_ingreso,
                              i.cod_responsable,
                              (SELECT 
                                t.des_larga 
                              FROM
                                tabla_m_detalle t 
                              WHERE t.cod_tabla = 'COBR' 
                                AND i.cod_responsable = t.cod_argumento) AS nom_responsable,
                              i.tipo_documento,
                              (SELECT 
                                descripcion 
                              FROM
                                maestrajf m 
                              WHERE m.tipo_dato = 'tdoc' 
                                AND i.tipo_documento = m.codigo) AS nom_documento,
                              i.documento,
                              i.total AS total,
                              i.observacion,
                              i.estado,
                              i.visible 
                            FROM
                              ingresos_caja i 
                            WHERE i.id=$id");

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlEditarIngresosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                ingresos_caja 
                              SET
                                fecha = :fecha,
                                cod_ingreso = :cod_ingreso,
                                cod_responsable = :cod_responsable,
                                tipo_documento = :tipo_documento,
                                documento = :documento,
                                total = :total,
                                observacion = :observacion,
                                usumod = :usumod,
                                fecmod = :fecmod,
                                pcmod = :pcmod 
                              WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_ingreso", $datos["cod_ingreso"], PDO::PARAM_STR);
        $stmt->bindParam(":cod_responsable", $datos["cod_responsable"], PDO::PARAM_STR);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        $stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
        $stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
        $stmt->bindParam(":pcmod", $datos["pcmod"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlActualizarIngresosB($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                    tabla_m_detalle t 
                                  SET
                                    t.valor_2 = t.valor_2 - :antiguo + :nuevo,
                                    t.valor_4 = t.valor_4 - :antiguo + :nuevo 
                                  WHERE t.cod_tabla = YEAR(NOW()) 
                                    AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":nuevo", $datos["nuevo"], PDO::PARAM_STR);
        $stmt->bindParam(":antiguo", $datos["antiguo"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlAnularIngresosCaja($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                ingresos_caja 
              SET
                estado = 0,
                visible = 0,
                usuanu = :usuanu,
                fecanu = :fecanu,
                pcanu = :pcanu 
              WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
        $stmt->bindParam(":usuanu", $datos["usuanu"], PDO::PARAM_STR);
        $stmt->bindParam(":fecanu", $datos["fecanu"], PDO::PARAM_STR);
        $stmt->bindParam(":pcanu", $datos["pcanu"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /* 
  * CREAR GASTOS DE CAJA
  */
    static public function mdlActualizarIngresosC($datos)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE 
                                  tabla_m_detalle t 
                                SET
                                  t.valor_2 = t.valor_2 - :ingreso,
                                  t.valor_4 = t.valor_4 - :ingreso 
                                WHERE t.cod_tabla = YEAR(NOW()) 
                                  AND t.des_corta = MONTH(:mes)");

        $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":ingreso", $datos["ingreso"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarCentroCostosResumen($valor)
    {

        if ($valor != null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
      cc.id,
      cc.tipo_gasto,
      cc.nombre_gasto,
      cc.key_gasto,
      cc.cod_area,
      cc.nombre_area,
      IFNULL(cc.cod_caja, ' - ') AS cod_caja,
      IFNULL(cc.descripcion, ' - ') AS descripcion,
      cc.estado,
      cc.visible,
      cc.usureg,
      cc.fecreg,
      cc.pcreg,
      cc.usumod,
      cc.fecmod,
      cc.pcmod 
    FROM
      centro_costos cc 
    WHERE cc.cod_caja = :valor");

            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
      cc.key_gasto,
      cc.tipo_gasto,
      cc.nombre_gasto,
      cc.cod_area,
      cc.nombre_area,
      cc.cod_caja,
      cc.descripcion,
      IFNULL(g.m1, 0) AS m1,
      IFNULL(g.m2, 0) AS m2,
      IFNULL(g.m3, 0) AS m3,
      IFNULL(g.m4, 0) AS m4,
      IFNULL(g.m5, 0) AS m5,
      IFNULL(g.m6, 0) AS m6,
      IFNULL(g.m7, 0) AS m7,
      IFNULL(g.m8, 0) AS m8,
      IFNULL(g.m9, 0) AS m9,
      IFNULL(g.m10, 0) AS m10,
      IFNULL(g.m11, 0) AS m11,
      IFNULL(g.m12, 0) AS m12,
      IFNULL(g.total, 0) AS total
    FROM
      centro_costos cc 
      LEFT JOIN 
        (SELECT 
          g.cod_caja,
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '1' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm1',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '2' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm2',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '3' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm3',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '4' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm4',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '5' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm5',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '6' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm6',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '7' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm7',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '8' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm8',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '9' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm9',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '10' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm10',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '11' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm11',
          SUM(
            CASE
              WHEN MONTH(g.fecha) = '12' 
              THEN g.total 
              ELSE 0 
            END
          ) AS 'm12',
          SUM(g.total) AS total  
        FROM
          gastos_caja g 
        WHERE g.estado = 1 
          AND g.visible = 1 
          AND YEAR(g.fecha) = YEAR(NOW()) 
        GROUP BY g.cod_caja) g 
        ON cc.cod_caja = g.cod_caja 
    WHERE cc.cod_caja IS NOT NULL");

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarDiario($valor)
    {

        if ($valor != null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                  d.id,
                  d.tipo_gasto,
                  d.origen,
                  d.voucher,
                  d.cuenta,
                  d.descripcion,
                  d.debito,
                  d.credito,
                  d.moneda,
                  d.tipo_cambio,
                  d.fecha,
                  d.concepto,
                  d.ruc,
                  d.razon_social,
                  d.tipo_documento,
                  d.documento,
                  d.serie_doc,
                  d.num_doc,
                  d.fecha_emision,
                  d.fecha_vencimiento,
                  d.sucursal,
                  d.comprobante,
                  d.contribuyente,
                  d.condicion,
                  d.alerta 
                FROM
                  diario d 
                WHERE YEAR(d.fecha) = YEAR(NOW()) 
                  AND MONTH(d.fecha) = :valor
                  AND d.tipo_gasto IN ('60','92','94','95','97')
                ORDER BY d.origen,
                  d.voucher ");

            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetchAll();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
                                              d.id,
                                              d.tipo_gasto,
                                              d.origen,
                                              d.voucher,
                                              d.cuenta,
                                              d.descripcion,
                                              d.debito,
                                              d.credito,
                                              d.moneda,
                                              d.tipo_cambio,
                                              d.fecha,
                                              d.concepto,
                                              d.ruc,
                                              d.razon_social,
                                              d.tipo_documento,
                                              d.documento,
                                              d.serie_doc,
                                              d.num_doc,
                                              d.fecha_emision,
                                              d.fecha_vencimiento,
                                              d.sucursal,
                                              d.comprobante,
                                              d.contribuyente,
                                              d.condicion,
                                              d.alerta 
                                            FROM
                                              diario d
                                              where d.tipo_gasto IN ('60','92','94','95','97')
                                            ORDER BY d.origen,
                                              d.voucher");

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarDiarioAlerta()
    {


        $stmt = Conexion::conectar()->prepare("SELECT 
                                                  CASE
                                                    WHEN MONTH(d.fecha) = '1' 
                                                    THEN 'Ene' 
                                                    WHEN MONTH(d.fecha) = '2' 
                                                    THEN 'Feb' 
                                                    WHEN MONTH(d.fecha) = '3' 
                                                    THEN 'Mar' 
                                                    WHEN MONTH(d.fecha) = '4' 
                                                    THEN 'Abr' 
                                                    WHEN MONTH(d.fecha) = '5' 
                                                    THEN 'May' 
                                                    WHEN MONTH(d.fecha) = '6' 
                                                    THEN 'Jun' 
                                                    WHEN MONTH(d.fecha) = '7' 
                                                    THEN 'Jul' 
                                                    WHEN MONTH(d.fecha) = '8' 
                                                    THEN 'Ago' 
                                                    WHEN MONTH(d.fecha) = '9' 
                                                    THEN 'Sep' 
                                                    WHEN MONTH(d.fecha) = '10' 
                                                    THEN 'Oct' 
                                                    WHEN MONTH(d.fecha) = '11' 
                                                    THEN 'Nov' 
                                                    ELSE 'Dic' 
                                                  END AS mes,
                                                  d.id,
                                                  d.tipo_gasto,
                                                  d.origen,
                                                  d.voucher,
                                                  d.cuenta,
                                                  d.descripcion,
                                                  d.debito,
                                                  d.credito,
                                                  (d.debito - d.credito) AS total,
                                                  d.moneda,
                                                  d.tipo_cambio,
                                                  d.fecha,
                                                  d.concepto,
                                                  d.ruc,
                                                  d.razon_social,
                                                  d.tipo_documento,
                                                  d.documento,
                                                  d.serie_doc,
                                                  d.num_doc,
                                                  d.fecha_emision,
                                                  d.fecha_vencimiento,
                                                  d.sucursal,
                                                  d.comprobante,
                                                  d.contribuyente,
                                                  d.condicion,
                                                  d.alerta 
                                                FROM
                                                  diario d 
                                                WHERE d.tipo_gasto IN ('60', '92', '94', '95', '97') 
                                                  AND (
                                                    d.alerta = '1' 
                                                  OR d.comprobante IN ('NO EXISTE', 'ANULADO') 
                                                  OR d.contribuyente IN ('-') 
                                                  OR condicion IN ('-')
                                                  ) 
                                                ORDER BY d.origen,
                                                  d.voucher");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarRegCompras()
    {


        $stmt = Conexion::conectar()->prepare("SELECT 
                                                CASE
                                                  WHEN MONTH(r.fecha_emision) = '1' 
                                                  THEN 'Ene' 
                                                  WHEN MONTH(r.fecha_emision) = '2' 
                                                  THEN 'Feb' 
                                                  WHEN MONTH(r.fecha_emision) = '3' 
                                                  THEN 'Mar' 
                                                  WHEN MONTH(r.fecha_emision) = '4' 
                                                  THEN 'Abr' 
                                                  WHEN MONTH(r.fecha_emision) = '5' 
                                                  THEN 'May' 
                                                  WHEN MONTH(r.fecha_emision) = '6' 
                                                  THEN 'Jun' 
                                                  WHEN MONTH(r.fecha_emision) = '7' 
                                                  THEN 'Jul' 
                                                  WHEN MONTH(r.fecha_emision) = '8' 
                                                  THEN 'Ago' 
                                                  WHEN MONTH(r.fecha_emision) = '9' 
                                                  THEN 'Sep' 
                                                  WHEN MONTH(r.fecha_emision) = '10' 
                                                  THEN 'Oct' 
                                                  WHEN MONTH(r.fecha_emision) = '11' 
                                                  THEN 'Nov' 
                                                  ELSE 'Dic' 
                                                END AS mes,
                                                r.origen,
                                                r.voucher,
                                                r.fecha_emision,
                                                r.fecha_vencimiento,
                                                r.tipo_documento,
                                                r.serie_doc,
                                                r.num_doc,
                                                r.ruc,
                                                r.razon_social,
                                                CASE
                                                  WHEN r.tipo_documento = '07' 
                                                  THEN (
                                                    CASE
                                                      WHEN r.moneda = 'D' 
                                                      THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                                      ELSE ROUND(r.total * - 1, 2) 
                                                    END
                                                  ) 
                                                  ELSE (
                                                    CASE
                                                      WHEN r.moneda = 'D' 
                                                      THEN ROUND(r.total / r.tipo_cambio, 2) 
                                                      ELSE ROUND(r.total, 2) 
                                                    END
                                                  ) 
                                                END AS total,
                                                r.moneda,
                                                r.tipo_cambio,
                                                r.comprobante,
                                                r.contribuyente,
                                                r.condicion,
                                                r.alerta,
                                                r.estado 
                                              FROM
                                                reg_compras r WHERE r.tipo_documento IN ('01', '03', '07', '08') ");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }


    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlMostrarKardex($valor, $tipo = "", $anno = "", $mes = "")
    {

        if ($valor != null) {

            $stmt = Conexion::conectar()->prepare("");

            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        } else {

            $where = [];
            if ($tipo !== "") {
                $where[] = "tipo = :tipo";
            }
            if ($anno !== "") {
                $where[] = "anno = :anno";
            }
            if ($mes !== "") {
                $where[] = "LOWER(mes) = LOWER(:mes)";
            }

            $sql = "SELECT * FROM kardex_cabjf" . ($where ? " WHERE " . implode(" AND ", $where) : "");

            $stmt = Conexion::conectar()->prepare($sql);

            if ($tipo !== "") {
                $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            }
            if ($anno !== "") {
                $stmt->bindParam(":anno", $anno, PDO::PARAM_STR);
            }
            if ($mes !== "") {
                $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);
            }

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlIngresarKardexCab($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO kardex_cabjf (
                                                        tipo,
                                                        codigo,
                                                        anno,
                                                        mes,
                                                        saldo_inicial,
                                                        ingreso,
                                                        salida,
                                                        saldo_final,
                                                        usureg,
                                                        pcreg,
                                                        fecreg
                                                    ) 
                                                    VALUES
                                                        (
                                                        :tipo,
                                                        UPPER(:codigo),
                                                        :anno,
                                                        :mes,
                                                        :saldo_inicial,
                                                        :ingreso,
                                                        :salida,
                                                        :saldo_final,
                                                        :usureg,
                                                        :pcreg,
                                                        :fecreg
                                                        )");

        $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
        $stmt->bindParam(":anno", $datos["anno"], PDO::PARAM_STR);
        $stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_STR);
        $stmt->bindParam(":saldo_inicial", $datos["saldo_inicial"], PDO::PARAM_STR);
        $stmt->bindParam(":ingreso", $datos["ingreso"], PDO::PARAM_STR);
        $stmt->bindParam(":salida", $datos["salida"], PDO::PARAM_STR);
        $stmt->bindParam(":saldo_final", $datos["saldo_final"], PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* REGISTAR DETALLEES DEL KARDEX
	*/
    static public function mdlIngresarKardexDet($detalle)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO kardex_detjf (
                                                            cod_cabecera,
                                                            fecha,
                                                            t_tabla,
                                                            establecimiento,
                                                            num_documento,
                                                            item,
                                                            descripcion,
                                                            cod_unidad,
                                                            unidad,
                                                            cliente,
                                                            ctm,
                                                            tipo_movimiento,
                                                            cantidad_a,
                                                            pu_a,
                                                            total_a,
                                                            cantidad_b,
                                                            pu_b,
                                                            total_b,
                                                            cantidad_c,
                                                            pu_c,
                                                            total_c
                                                )
                                                VALUES
                                                    $detalle");
        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlVerItems($valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                        k.item,
                        k.descripcion,
                        k.cod_unidad  
                    FROM
                        kardex_detjf k 
                    WHERE k.item NOT LIKE '%Total%' 
                        AND k.cod_cabecera = :valor");

        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Mostrar Centro de Costos
	*/
    static public function mdlVerItemsDet($codigo, $item)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                            k.cod_cabecera,
                            k.fecha,
                            k.t_tabla,
                            k.establecimiento,
                            k.num_documento,
                            k.item,
                            REPLACE(k.item, 'Total ', '') AS buscador,
                            k.descripcion,
                            CASE
                              WHEN k.ctm <> '' 
                              THEN k.ctm 
                              ELSE 'TOTAL' 
                            END AS ctm,
                            k.cantidad_a,
                            k.pu_a,
                            k.total_a,
                            k.cantidad_b,
                            k.pu_b,
                            k.total_b,
                            k.cantidad_c,
                            k.pu_c,
                            k.total_c 
                        FROM
                            kardex_detjf k 
                        WHERE k.cod_cabecera = :codigo 
                            AND REPLACE(k.item, 'Total ', '') = :item");

        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
        $stmt->bindParam(":item", $item, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }
}
