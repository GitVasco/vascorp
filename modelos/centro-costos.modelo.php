<?php

require_once "conexion.php";

class ModeloCentroCostos{

	/*
	* Mostrar Areas
	*/
	static public function mdlMostrarAreas($valor){

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

        $stmt -> execute();

        return $stmt -> fetchAll();

		$stmt -> close();

		$stmt = null;

	}

	/*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarCentroCostos($valor){

    if($valor != null){

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

      $stmt -> execute();

      return $stmt -> fetch();

    }else{

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
          ORDER BY cc.key_gasto,
          cc.cod_area,
          cc.cod_caja ");

      $stmt -> execute();

      return $stmt -> fetchAll();

    }



    $stmt -> close();

    $stmt = null;

  }  

	/*
	* Mostrar Areas
	*/
	static public function mdlMostrarCorrelativo($tipoGasto, $area){

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

    $stmt -> execute();

    return $stmt -> fetch();

    $stmt -> close();

    $stmt = null;

  }  

    /* 
    * CREAR CENTRO COSTOS
    */
    static public function mdlCrearCentroCostos($datos){

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
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return "error";
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }    
  
    /* 
    * ACTUALIZAR VACIOS
    */
    static public function mdlActualizarACentroCostos($datos){

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
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }    

	/*
	* PONER NOMBRES
	*/
	static public function mdlPonerNombres(){

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

    $stmt -> execute();

    $stmt = null;

  }  

  /*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarGastosCaja($mes){

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
                    g.rubro_cancelacion 
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
                      and g.estado=1 AND g.visible=1");

    $stmt -> execute();

    return $stmt -> fetchAll();

    $stmt -> close();

    $stmt = null;

  }  

  /*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarGastosCajaId($id){

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

    $stmt -> execute();

    return $stmt -> fetch();

    $stmt -> close();

    $stmt = null;

  }   

  /*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarCentroCostosCaja(){

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

    $stmt -> execute();

    return $stmt -> fetchAll();

    $stmt -> close();

    $stmt = null;

  }  

  /*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarTipoDoc(){

    $stmt = Conexion::conectar()->prepare("SELECT 
    codigo,
    descripcion 
  FROM
    maestrajf 
  WHERE tipo_dato = 'tdoc' 
    AND codigo IN ('01', '03', '09', '99')");

    $stmt -> execute();

    return $stmt -> fetchAll();

    $stmt -> close();

    $stmt = null;

  }   

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlCrearGastosCaja($datos){

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
      $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
      $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
      $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return "error";
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }
    
    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlEditarGastosCaja($datos){

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
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }      

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlAnularGastosCaja($datos){

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
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }  
    
    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlActualizarEgresosA($datos){

      $stmt = Conexion::conectar()->prepare("UPDATE 
                                    tabla_m_detalle t 
                                  SET
                                    t.valor_3 = t.valor_3 + :egreso,
                                    t.valor_4 = t.valor_4 - :egreso 
                                  WHERE t.cod_tabla = YEAR(NOW()) 
                                    AND t.des_corta = MONTH(:mes)");
  
      $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);  
      $stmt->bindParam(":egreso", $datos["egreso"], PDO::PARAM_STR);
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }  
    
    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlActualizarEgresosB($datos){

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
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }    

    /* 
    * CREAR GASTOS DE CAJA
    */
    static public function mdlActualizarEgresosC($datos){

      $stmt = Conexion::conectar()->prepare("UPDATE 
                                    tabla_m_detalle t 
                                  SET
                                    t.valor_3 = t.valor_3 - :egreso,
                                    t.valor_4 = t.valor_4 + :egreso 
                                  WHERE t.cod_tabla = YEAR(NOW()) 
                                    AND t.des_corta = MONTH(:mes)");
  
      $stmt->bindParam(":mes", $datos["fecha"], PDO::PARAM_STR);  
      $stmt->bindParam(":egreso", $datos["egreso"], PDO::PARAM_STR);
  
      if($stmt->execute()){
  
        return "ok";
  
      }else{
  
        return $stmt->errorInfo();
      
      }
  
      $stmt->close();
      $stmt = null;
  
    }    

  /*
	* Mostrar Centro de Costos
	*/
	static public function mdlMostrarMeses(){

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

    $stmt -> execute();

    return $stmt -> fetchAll();

    $stmt -> close();

    $stmt = null;

  }
  
  /* 
  * CERRAR MES
  */
  static public function mdlCerrarMes($mes){

    $stmt = Conexion::conectar()->prepare("UPDATE 
                      tabla_m_detalle 
                    SET
                      valor_5 = 'CER' 
                    WHERE cod_tabla = YEAR(NOW()) 
                      AND des_corta = :mes");

    $stmt->bindParam(":mes", $mes, PDO::PARAM_STR);  

    if($stmt->execute()){

      return "ok";

    }else{

      return $stmt->errorInfo();
    
    }

    $stmt->close();
    $stmt = null;

  }  

  /* 
  * CERRAR MES
  */
  static public function mdlAbrirMes($mes, $saldo_actual){

    $stmt = Conexion::conectar()->prepare("UPDATE 
                      tabla_m_detalle 
                    SET
                      valor_1 = :saldo_actual,
                      valor_5 = 'ABI' 
                    WHERE cod_tabla = YEAR(NOW()) 
                      AND des_corta = :mes+1");

    $stmt->bindParam(":mes", $mes, PDO::PARAM_STR); 
    $stmt->bindParam(":saldo_actual", $saldo_actual, PDO::PARAM_STR);  

    if($stmt->execute()){

      return "ok";

    }else{

      return $stmt->errorInfo();
    
    }

    $stmt->close();
    $stmt = null;

  }   

}