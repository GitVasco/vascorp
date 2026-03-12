<?php
require_once "conexion.php";

class ModeloCortes
{

    /*
	* Método para mostrar los cortes
	*/
    static public function mdlMostrarCortes($valor1)
    {

        if ($valor1 == null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                    a.articulo,
                    a.marca,
                    a.modelo,
                    a.nombre,
                    a.color,
                    a.talla,
                    a.alm_corte 
                FROM
                    articulojf a 
                WHERE a.alm_corte > 0");

            $stmt->execute();

            return $stmt->fetchAll();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
                        a.articulo,
                        a.marca,
                        a.modelo,
                        a.nombre,
                        a.color,
                        a.talla,
                        a.alm_corte 
                    FROM
                        articulojf a 
                    WHERE  a.articulo = :valor1");

            $stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        }

        $stmt->close();

        $stmt = null;
    }

    /*
	* MOSTRAR TALLERES - VERSION 3
	* MODIFICADO: Usa directamente alm_corte de articulojf (ya sincronizado)
	* Más simple y confiable que calcular dinámicamente restando entaller_cabjf
	* NOTA: Ejecutar actualizar_alm_corte.php periódicamente para mantener alm_corte sincronizado
	*/
    static public function mdlMostrarCortesV($modeloCorte)
    {
        if ($modeloCorte != "null") {
            $stmt = Conexion::conectar()->prepare("SELECT
                                                    a.modelo,
                                                    a.nombre,
                                                    a.cod_color,
                                                    a.color,
                                                    a.estado,
                                                    SUM(CASE WHEN a.cod_talla = 1 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '1',
                                                    SUM(CASE WHEN a.cod_talla = 2 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '2',
                                                    SUM(CASE WHEN a.cod_talla = 3 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '3',
                                                    SUM(CASE WHEN a.cod_talla = 4 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '4',
                                                    SUM(CASE WHEN a.cod_talla = 5 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '5',
                                                    SUM(CASE WHEN a.cod_talla = 6 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '6',
                                                    SUM(CASE WHEN a.cod_talla = 7 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '7',
                                                    SUM(CASE WHEN a.cod_talla = 8 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '8',
                                                    SUM(GREATEST(a.alm_corte, 0)) AS total
                                                FROM articulojf a
                                                WHERE a.modelo = :modelo
                                                  AND a.alm_corte > 0
                                                GROUP BY a.modelo, a.cod_color, a.color, a.estado");

            $stmt->bindParam(":modelo", $modeloCorte, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetchAll();
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                                            a.marca,
                                            a.modelo,
                                            a.nombre,
                                            a.cod_color,
                                            a.color,
                                            m.tipo,
                                            a.estado,
                                            SUM(CASE WHEN a.cod_talla = 1 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '1',
                                            SUM(CASE WHEN a.cod_talla = 2 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '2',
                                            SUM(CASE WHEN a.cod_talla = 3 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '3',
                                            SUM(CASE WHEN a.cod_talla = 4 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '4',
                                            SUM(CASE WHEN a.cod_talla = 5 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '5',
                                            SUM(CASE WHEN a.cod_talla = 6 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '6',
                                            SUM(CASE WHEN a.cod_talla = 7 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '7',
                                            SUM(CASE WHEN a.cod_talla = 8 THEN GREATEST(a.alm_corte, 0) ELSE 0 END) AS '8',
                                            SUM(GREATEST(a.alm_corte, 0)) AS total 
                                        FROM articulojf a 
                                        LEFT JOIN modelojf m ON a.modelo = m.modelo
                                        WHERE a.alm_corte > 0
                                        GROUP BY a.marca, a.modelo, a.cod_color, a.color, m.tipo, a.estado");

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }

    //*enviados a taller
    static public function mdlMostrarEnviadosTaller($modeloTaller)
    {

        if ($modeloTaller != "null") {
            $stmt = Conexion::conectar()->prepare("SELECT 
                                                DATE(e.fecha) AS fecha,
                                                e.taller,
                                                CASE
                                                WHEN e.taller IS NULL 
                                                THEN '' 
                                                WHEN e.taller = 'VC' 
                                                THEN 'VASCO' 
                                                ELSE s.nom_sector 
                                                END AS nombre_taller,
                                                a.modelo,
                                                a.nombre,
                                                a.cod_color,
                                                a.color,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '1' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t1,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '2' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t2,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '3' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t3,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '4' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t4,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '5' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t5,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '6' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t6,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '7' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t7,
                                                SUM(
                                                CASE
                                                    WHEN a.cod_talla = '8' 
                                                    THEN e.cantidad 
                                                    ELSE 0 
                                                END
                                                ) AS t8,
                                                SUM(e.cantidad) AS total 
                                            FROM
                                                entaller_cabjf e 
                                                LEFT JOIN articulojf a 
                                                ON e.articulo = a.articulo 
                                                LEFT JOIN sectorjf s 
                                                ON e.taller = s.cod_sector 
                                        WHERE a.modelo = '" . $modeloTaller . "' 
                                            AND YEAR(e.fecha) = YEAR(NOW())
                                            AND (e.taller IS NULL OR e.taller != 'TX')
                                        GROUP BY DATE(e.fecha),
                                                    e.taller,
                                            a.modelo,
                                            a.cod_color
                                        ORDER BY e.fecha DESC,
                                            e.taller,
                                            a.modelo,
                                            a.cod_color");

            $stmt->execute();

            return $stmt->fetchAll();
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                                        DATE(e.fecha) AS fecha,
                                        e.taller,
                                        CASE
                                        WHEN e.taller IS NULL 
                                        THEN '' 
                                        WHEN e.taller = 'VC' 
                                        THEN 'VASCO' 
                                        ELSE s.nom_sector 
                                        END AS nombre_taller,
                                        a.modelo,
                                        a.nombre,
                                        a.cod_color,
                                        a.color,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '1' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t1,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '2' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t2,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '3' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t3,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '4' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t4,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '5' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t5,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '6' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t6,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '7' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t7,
                                        SUM(
                                        CASE
                                            WHEN a.cod_talla = '8' 
                                            THEN e.cantidad 
                                            ELSE 0 
                                        END
                                        ) AS t8,
                                        SUM(e.cantidad) AS total 
                                    FROM
                                        entaller_cabjf e 
                                        LEFT JOIN articulojf a 
                                        ON e.articulo = a.articulo 
                                        LEFT JOIN sectorjf s 
                                        ON e.taller = s.cod_sector 
                                    WHERE YEAR(e.fecha) = YEAR(NOW())
                                        AND (e.taller IS NULL OR e.taller != 'TX')
                                    GROUP BY DATE(e.fecha),
                                        e.taller,
                                        a.modelo,
                                        a.cod_color
                                    ORDER BY e.fecha DESC,
                                        e.taller,
                                        a.modelo,
                                        a.cod_color");

            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt->close();

        $stmt = null;
    }


    /*
	* MOSTRAR TALLERES
	*/
    static public function mdlMostrarTallerA()
    {

        $stmt = Conexion::conectar()->prepare("SELECT * FROM tallerjf");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
    *ACTUALIZAR LA NUEVA CANTIDAD EN LA TABLA CORTE
    */
    static public function mdlActualizarCorte($articulo, $operacion, $cantidad)
    {

        $stmt = Conexion::conectar()->prepare("UPDATE encortejf
                                                SET
                                                    cantidad = :cantidad,
                                                    total_precio = (precio_doc / 12) * cantidad,
                                                    total_tiempo = (tiempo_stand / 60) * cantidad
                                                WHERE
                                                    articulo = :articulo
                                                        AND cod_operacion = :operacion");

        $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
        $stmt->bindParam(":operacion", $operacion, PDO::PARAM_STR);
        $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_STR);

        $stmt->execute();

        $stmt = null;
    }

    /*
	* REGISTRAR LO QUE SE MANDA A TALLER
	*/
    static public function mdlMandarTaller($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO entallerjf (
                                                id_cabecera,
                                                articulo,
                                                cod_operacion,
                                                cantidad,
                                                usuario,
                                                total_precio,
                                                total_tiempo,
                                                codigo
                                            ) 
                                            (SELECT 
                                                :codigo,
                                                a.articulo,
                                                od.cod_operacion,
                                                :cantidad,
                                                :usuario,
                                                ((od.precio_doc) / 12) * :cantidad,
                                                ((od.tiempo_stand) / 60) * :cantidad,
                                                CONCAT(:codigo,od.cod_operacion) 
                                            FROM
                                                articulojf a 
                                                LEFT JOIN operaciones_detallejf od 
                                                ON a.modelo = od.modelo 
                                            WHERE articulo = :articulo)");

        $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
        $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* ULTIMO CODIGO
	*/
    static public function mdlUltCodigo()
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                                                IFNULL(MAX(id), 1000000) AS ult_codigo 
                                            FROM
                                                entaller_cabjf en");

        $stmt->execute();

        return $stmt->fetch();

        $stmt->close();

        $stmt = null;
    }

    /*
	* SIGUIENTE CÓDIGO (sin insertar) - para ticket original
	*/
    static public function mdlSiguienteCodigoTaller()
    {
        $stmt = Conexion::conectar()->prepare("SELECT IFNULL(MAX(id), 1000000) + 1 AS ult_codigo FROM entaller_cabjf");
        $stmt->execute();
        return $stmt->fetch();
        $stmt->close();
        $stmt = null;
    }

    /*
	* REGISTRAR LO QUE SE MANDA A TALLER CABECERA
	*/
    static public function mdlMandarTallerCab($datos)
    {

        $stmt = Conexion::conectar()->prepare("INSERT INTO entaller_cabjf (articulo, usuario, cantidad, estado, taller) 
        VALUES
          (:articulo, :usuario, :cantidad, :estado, :taller) ");

        $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
        $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);


        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /*
	* REGISTRAR LO QUE SE MANDA A TALLER CABECERA V2
	* Liquida por FIFO sobre todos los detalles con saldo hasta cubrir la cantidad.
	* Devuelve ["status"=>"ok", "cantidad_usada"=>N] o string "error...".
	*/
    static public function mdlMandarTallerCabV2($datos)
    {
        try {
            $pdo = Conexion::conectar();
            $pdo->beginTransaction();

            $articulo = $datos["articulo"];
            $cantidadPedida = (int) $datos["cantidad"];
            $cantidadRestante = $cantidadPedida;
            $cantidadTotalUsada = 0;
            $primerDetalleId = null;

            // Traer todos los detalles con saldo del año, orden FIFO (más antiguo primero)
            $stmt = $pdo->prepare("SELECT acd.id, COALESCE(acd.saldo_taller, acd.cantidad) AS saldo_disponible
                FROM almacencorte_detallejf acd
                INNER JOIN almacencortejf ac ON acd.almacencorte = ac.codigo
                WHERE acd.articulo = :articulo
                  AND COALESCE(acd.saldo_taller, acd.cantidad) > 0
                  AND YEAR(DATE(ac.fecha)) = YEAR(NOW())
                ORDER BY acd.id ASC");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->execute();
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($detalles)) {
                $pdo->rollBack();
                return "error_saldo_insuficiente";
            }

            // Liquidar de varios grupos (FIFO) hasta cubrir lo pedido
            foreach ($detalles as $detalle) {
                if ($cantidadRestante <= 0) break;
                $detalleId = (int) $detalle['id'];
                $saldoDisponible = (int) $detalle['saldo_disponible'];
                if ($saldoDisponible <= 0) continue;

                $cantidadAUsar = min($saldoDisponible, $cantidadRestante);
                if ($cantidadAUsar <= 0) continue;

                if ($primerDetalleId === null) $primerDetalleId = $detalleId;

                $nuevoSaldo = max(0, $saldoDisponible - $cantidadAUsar);
                $stmt = $pdo->prepare("UPDATE almacencorte_detallejf SET saldo_taller = :nuevo_saldo WHERE id = :detalle_id");
                $stmt->bindParam(":nuevo_saldo", $nuevoSaldo, PDO::PARAM_INT);
                $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();

                $cantidadTotalUsada += $cantidadAUsar;
                $cantidadRestante -= $cantidadAUsar;
            }

            if ($cantidadTotalUsada <= 0) {
                $pdo->rollBack();
                return "error_saldo_insuficiente";
            }

            // Una sola cabecera con la cantidad realmente liquidada (referencia al primer detalle usado)
            $stmt = $pdo->prepare("INSERT INTO entaller_cabjf
                (articulo, usuario, cantidad, saldo, estado, guia, taller, almacencorte_detalle_id)
                VALUES
                (:articulo, :usuario, :cantidad, :saldo, :estado, :guia, :taller, :almacencorte_detalle_id)");
            $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidadTotalUsada, PDO::PARAM_INT);
            $stmt->bindParam(":saldo", $cantidadTotalUsada, PDO::PARAM_INT);
            $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
            $stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
            $stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);
            $stmt->bindParam(":almacencorte_detalle_id", $primerDetalleId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $pdo->rollBack();
                return "error";
            }
            $stmt->closeCursor();

            // Articulojf: descontar solo lo realmente liquidado (evita duplicar alm_corte/servicio)
            $es_servicio_externo = isset($datos["es_servicio_externo"]) ? $datos["es_servicio_externo"] : false;
            if (!$es_servicio_externo) {
                $stmt = $pdo->prepare("UPDATE articulojf SET taller = taller + :cantidad, alm_corte = GREATEST(alm_corte - :cantidad, 0) WHERE articulo = :articulo");
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                $stmt->bindParam(":cantidad", $cantidadTotalUsada, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();
            } else {
                $stmt = $pdo->prepare("UPDATE articulojf SET alm_corte = GREATEST(alm_corte - :cantidad, 0) WHERE articulo = :articulo");
                $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
                $stmt->bindParam(":cantidad", $cantidadTotalUsada, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();
            }

            $pdo->commit();
            return ["status" => "ok", "cantidad_usada" => $cantidadTotalUsada];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return "error: " . $e->getMessage();
        }

        $stmt = null;
    }

    /*
	* MOSTRAR EN TALLERES
	*/
    static public function mdlMostrarEnTalleres($articulo)
    {

        $stmt = Conexion::conectar()->prepare("SELECT 
                                            a.modelo,
                                            a.nombre,
                                            a.color,
                                            a.talla,
                                            td.cantidad,
                                            td.cod_operacion,
                                            o.nombre AS operacion,
                                            td.codigo 
                                        FROM
                                            entallerjf td 
                                            LEFT JOIN operacionesjf o 
                                            ON td.cod_operacion = o.codigo 
                                            LEFT JOIN articulojf a 
                                            ON td.articulo = a.articulo 
                                            LEFT JOIN operaciones_detallejf od 
                                            ON od.modelo = a.modelo 
                                            AND td.cod_operacion = od.cod_operacion 
                                        WHERE td.id_cabecera = :articulo 
                                            AND (
                                            o.nombre NOT LIKE '%HABI%' 
                                            AND o.nombre NOT LIKE '%Limpi%' 
                                            AND o.nombre NOT LIKE '%inspe%'
                                            ) 
                                            AND od.precio_doc > 0");

        $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    /*
	* Método para mostrar los cortes DEL SUBLIMADO POR ARTICULO
	*/
    static public function mdlMostrarCorteSublimado($valor1, $valor2)
    {


        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
        IFNULL(ac.guia, '') AS guia,
        DATE_FORMAT(ac.fecha, '%d/%m/%Y') AS fecha
      FROM
        almacencortejf ac 
        LEFT JOIN almacencorte_detallejf ad 
          ON ac.codigo = ad.almacencorte 
        LEFT JOIN articulojf a 
          ON ad.articulo = a.articulo 
      WHERE a.modelo = '" . $valor1 . "' 
      AND a.cod_color = '" . $valor2 . "'");

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt->close();

        $stmt = null;
    }

    //* mostrar todos los cortes registrados del año
    static public function mdlMostrarCortesLista()
    {
        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
                    al.guia,
                    DATE(al.fecha) AS fecha 
                FROM
                    almacencortejf al 
                    LEFT JOIN almacencorte_detallejf ac 
                    ON al.guia = ac.almacencorte 
                    LEFT JOIN articulojf a 
                    ON ac.articulo = a.articulo 
                WHERE YEAR(al.fecha) = YEAR(NOW()) 
                    AND a.estampado = 1 
                    AND ac.estampado = 0 
                ORDER BY al.id DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll();

        $stmt = null;
    }

    static public function ctrMostrarCortesArticulos($corte, $articulo)
    {
        if ($articulo == null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                    ac.id,
                    ac.almacencorte,
                    ac.articulo,
                    ac.cantidad,
                    ac.estampado,
                    a.modelo,
                    a.nombre,
                    a.cod_color,
                    a.color,
                    a.cod_talla,
                    a.talla 
                FROM
                    almacencorte_detallejf ac 
                    LEFT JOIN articulojf a 
                    ON ac.articulo = a.articulo 
                WHERE ac.almacencorte = '$corte' 
                    AND a.estampado = 1 
                    AND ac.estampado = 0
            ");

            $stmt->execute();
            return $stmt->fetchAll();

            $stmt = null;
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                        ac.id,
                        ac.almacencorte,
                        ac.articulo,
                        ac.cantidad,
                        ac.estampado,
                        a.modelo,
                        a.nombre,
                        a.cod_color,
                        a.color,
                        a.cod_talla,
                        a.talla 
                    FROM
                        almacencorte_detallejf ac 
                        LEFT JOIN articulojf a 
                        ON ac.articulo = a.articulo 
                    WHERE ac.id = '$articulo' 
            ");

            $stmt->execute();
            return $stmt->fetch();

            $stmt = null;
        }
    }

    static public function mdlArticulosEstampado($articulo)
    {
        if ($articulo == null) {
            $stmt = Conexion::conectar()->prepare("SELECT 
                    * 
                FROM
                    articulojf a 
                WHERE a.estampado = 1
            ");

            $stmt->execute();
            return $stmt->fetchAll();

            $stmt = null;
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                    * 
                FROM
                    articulojf a 
                WHERE a.estampado = 1 
                    AND a.articulo = '$articulo'
            ");

            $stmt->execute();
            return $stmt->fetch();

            $stmt = null;
        }
    }

    static public function mdlArticulosTampografia($articulo)
    {
        if ($articulo == null) {
            $stmt = Conexion::conectar()->prepare("SELECT 
                    * 
                FROM
                    articulojf a 
                WHERE a.tampografia = 1
            ");

            $stmt->execute();
            return $stmt->fetchAll();

            $stmt = null;
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT 
                    * 
                FROM
                    articulojf a 
                WHERE a.tampografia = 1 
                    AND a.articulo = '$articulo'
            ");

            $stmt->execute();
            return $stmt->fetch();

            $stmt = null;
        }
    }

    static public function mdlRegistrarEstampado($datos)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO estampado (
            corte,
            almacencorte,
            articulo,
            cantorigen,
            cantestampado,
            cantmerma,
            cantsaldo,
            fecha,
            operario,
            cerrar,
            iniprep,
            finprep,
            iniprod,
            finprod,
            usuario,
            pcreg,
            fecreg
        ) VALUES (
            :corte,
            :almacencorte,
            :articulo,
            :cantorigen,
            :cantestampado,
            :cantmerma,
            :cantsaldo,
            :fecha,
            :operario,
            :cerrar,
            :iniprep,
            :finprep,
            :iniprod,
            :finprod,
            :usuario,
            :pcreg,
            :fecreg
        )");

        $stmt->bindParam(":corte", $datos["corte"], PDO::PARAM_STR);
        $stmt->bindParam(":almacencorte", $datos["almacencorte"], PDO::PARAM_STR);
        $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
        $stmt->bindParam(":cantorigen", $datos["cantorigen"], PDO::PARAM_STR);
        $stmt->bindParam(":cantestampado", $datos["cantestampado"], PDO::PARAM_STR);
        $stmt->bindParam(":cantmerma", $datos["cantmerma"], PDO::PARAM_STR);
        $stmt->bindParam(":cantsaldo", $datos["cantsaldo"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":operario", $datos["operario"], PDO::PARAM_STR);
        $stmt->bindParam(":cerrar", $datos["cerrar"], PDO::PARAM_STR);
        $stmt->bindParam(":iniprep", $datos["iniprep"], PDO::PARAM_STR);
        $stmt->bindParam(":finprep", $datos["finprep"], PDO::PARAM_STR);
        $stmt->bindParam(":iniprod", $datos["iniprod"], PDO::PARAM_STR);
        $stmt->bindParam(":finprod", $datos["finprod"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
        $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return $stmt->errorInfo();
        }
    }

    static public function mdlActualizarAlmacenCorte($datos)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE almacencorte_detallejf SET estampado = :estampado, saldo = :saldo WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
        $stmt->bindParam(":estampado", $datos["estampado"], PDO::PARAM_STR);
        $stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return $stmt->errorInfo();
        }
    }

    static public function mdlMostrarEstampados($estampado)
    {
        if ($estampado == null) {

            $stmt = Conexion::conectar()->prepare("SELECT 
                        e.id,
                        e.fecha,
                        a.modelo,
                        a.nombre,
                        a.color,
                        a.talla,
                        e.operario,
                        e.cantestampado 
                    FROM
                        estampado e 
                        LEFT JOIN articulojf a 
                        ON e.articulo = a.articulo");

            $stmt->execute();
            return $stmt->fetchAll();

            $stmt = null;
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT 
                        * 
                    FROM
                        estampado e 
                    WHERE id = '$estampado'");

            $stmt->execute();
            return $stmt->fetch();

            $stmt = null;
        }
    }

    static public function mdlActualizarEstampado($id, $corte, $articulo, $id_articulo, $cantidadOrigen, $cantidadEstampado, $cantidadMerma, $cantidadSaldo, $fecha, $operario, $cerrar, $inicioPreparacion, $finPreparacion, $inicioProduccion, $finProduccion)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE estampado set corte= '$corte', almacencorte = '$id_articulo', articulo = '$articulo', cantorigen = '$cantidadOrigen', cantestampado = '$cantidadEstampado', cantmerma = '$cantidadMerma', cantsaldo = '$cantidadSaldo', fecha = '$fecha', operario = '$operario', cerrar = '$cerrar', iniprep = '$inicioPreparacion', finprep = '$finPreparacion', iniprod = '$inicioProduccion', finprod = '$finProduccion' WHERE id = '$id'");
        if ($stmt->execute()) {
            return "ok";
        } else {
            return $stmt->errorInfo();
        }
    }

    static public function mdlEliminarEstampado($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM estampado WHERE id = '$id'");
        if ($stmt->execute()) {
            return "ok";
        } else {
            return $stmt->errorInfo();
        }
    }

    /*
	* ELIMINAR BLOQUE TALLER Y REVERTIR ALMACEN CORTE
	* Revierte el saldo en almacencorte_detallejf cuando se elimina un bloque (lógica en PHP)
	*/
    static public function mdlEliminarBloqueTaller($id_cabecera)
    {
        try {
            $pdo = Conexion::conectar();
            $pdo->beginTransaction();

            // Obtener datos del movimiento antes de eliminarlo
            $stmt = $pdo->prepare("SELECT 
                id, articulo, cantidad, almacencorte_detalle_id, estado 
                FROM entaller_cabjf 
                WHERE id = :id_cabecera");
            $stmt->bindParam(":id_cabecera", $id_cabecera, PDO::PARAM_INT);
            $stmt->execute();
            $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$movimiento) {
                $pdo->rollBack();
                return "error_movimiento_no_encontrado";
            }

            $articulo = $movimiento['articulo'];
            $cantidad = $movimiento['cantidad'];
            $detalleId = $movimiento['almacencorte_detalle_id'];

            // Revertir saldo_taller en almacencorte_detallejf si tiene referencia (lógica en PHP)
            if ($detalleId) {
                // Obtener cantidad original del detalle
                $stmt = $pdo->prepare("SELECT cantidad, saldo_taller FROM almacencorte_detallejf WHERE id = :detalle_id");
                $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
                $stmt->execute();
                $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($detalle) {
                    // Calcular nuevo saldo_taller (no puede exceder la cantidad original)
                    $nuevoSaldo = min($detalle['cantidad'], ($detalle['saldo_taller'] + $cantidad));

                    $stmt = $pdo->prepare("UPDATE almacencorte_detallejf
                        SET saldo_taller = :nuevo_saldo
                        WHERE id = :detalle_id");
                    $stmt->bindParam(":nuevo_saldo", $nuevoSaldo, PDO::PARAM_INT);
                    $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
                    $stmt->execute();
                    $stmt->closeCursor();
                }
            }

            $pdo->commit();
            return "ok";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return "error: " . $e->getMessage();
        }

        $stmt = null;
    }

    /*
	* CANCELAR MOVIMIENTO A TALLER
	* Revierte el saldo en almacencorte_detallejf y actualiza estado (lógica en PHP)
	*/
    static public function mdlCancelarMovimientoTaller($id_cabecera)
    {
        try {
            $pdo = Conexion::conectar();
            $pdo->beginTransaction();

            // Obtener datos del movimiento
            $stmt = $pdo->prepare("SELECT 
                id, articulo, cantidad, almacencorte_detalle_id, estado 
                FROM entaller_cabjf 
                WHERE id = :id_cabecera");
            $stmt->bindParam(":id_cabecera", $id_cabecera, PDO::PARAM_INT);
            $stmt->execute();
            $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$movimiento) {
                $pdo->rollBack();
                return "error_movimiento_no_encontrado";
            }

            if ($movimiento['estado'] != '0') {
                $pdo->rollBack();
                return "error_movimiento_ya_procesado";
            }

            $articulo = $movimiento['articulo'];
            $cantidad = $movimiento['cantidad'];
            $detalleId = $movimiento['almacencorte_detalle_id'];

            // Revertir saldo_taller en almacencorte_detallejf si tiene referencia (lógica en PHP)
            if ($detalleId) {
                // Obtener cantidad original del detalle
                $stmt = $pdo->prepare("SELECT cantidad, saldo_taller FROM almacencorte_detallejf WHERE id = :detalle_id");
                $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
                $stmt->execute();
                $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($detalle) {
                    // Calcular nuevo saldo_taller (no puede exceder la cantidad original)
                    $nuevoSaldo = min($detalle['cantidad'], ($detalle['saldo_taller'] + $cantidad));

                    $stmt = $pdo->prepare("UPDATE almacencorte_detallejf
                        SET saldo_taller = :nuevo_saldo
                        WHERE id = :detalle_id");
                    $stmt->bindParam(":nuevo_saldo", $nuevoSaldo, PDO::PARAM_INT);
                    $stmt->bindParam(":detalle_id", $detalleId, PDO::PARAM_INT);
                    $stmt->execute();
                    $stmt->closeCursor();
                }
            }

            // Revertir en articulosjf (lógica en PHP)
            $stmt = $pdo->prepare("UPDATE articulojf 
                SET alm_corte = alm_corte + :cantidad,
                    taller = GREATEST(taller - :cantidad, 0)
                WHERE articulo = :articulo");
            $stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
            $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            // Actualizar estado del movimiento
            $stmt = $pdo->prepare("UPDATE entaller_cabjf 
                SET estado = 'CANCELADO' 
                WHERE id = :id_cabecera");
            $stmt->bindParam(":id_cabecera", $id_cabecera, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $pdo->commit();
            return "ok";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return "error: " . $e->getMessage();
        }

        $stmt = null;
    }
}
