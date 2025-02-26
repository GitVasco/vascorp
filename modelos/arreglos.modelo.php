<?php

require_once "conexion.php";

class ModeloArreglos
{
    static public function mdlActualizarCrearArreglos($taller, $articulo, $cantidad)
    {
        if ($taller == "0") {
            $sql = "UPDATE articulojf set taller = taller - {$cantidad}, arreglos = arreglos + {$cantidad} where articulo = '{$articulo}'";
        } else {
            $sql = "UPDATE articulojf set servicio = servicio - {$cantidad}, arreglos = arreglos + {$cantidad} where articulo = '{$articulo}'";
        }
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlActualizarCierre($cierre, $cantidad)
    {
        $sql = "UPDATE cierres_detallejf set cantidad = cantidad - {$cantidad} where id = '{$cierre}'";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlDescargarArreglos($articulo, $cantidad)
    {
        $sql = "UPDATE articulojf set arreglos = arreglos - {$cantidad} where articulo = '{$articulo}'";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlActualizarStock($articulo, $cantidad)
    {
        $sql = "UPDATE articulojf set stock = stock + {$cantidad} where articulo = '{$articulo}'";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlActualizarPendienteArreglos($idArreglo, $cantidad)
    {
        $sql = "UPDATE arreglos_detallejf set pendiente = pendiente - {$cantidad} where id = '{$idArreglo}'";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlCerrarDetalleArreglos()
    {
        $sql = "UPDATE arreglos_detallejf set estado = 0 where pendiente <= 0";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlCerrarCabeceraArreglos()
    {
        $sql = "UPDATE arreglos set estado = 0 where pendiente <= 0";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }

    static public function mdlActualizarPendienteCabecera()
    {
        $sql = "UPDATE
                    arreglos a
                left join
                (
                    select
                        ad.codigo ,
                        sum(ad.pendiente) pendiente
                    from
                        arreglos_detallejf ad
                    group by
                        ad.codigo) as ad
                on
                    a.codigo = ad.codigo
                set
                    a.pendiente = ad.pendiente";
        $stmt = Conexion::conectar()->prepare($sql);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return "error";
        }

        $stmt = null;
    }


    static public function mdlCrearArregloCabecera($datos)
    {
        $sql = "INSERT
            into
            arreglos (
            codigo,
            guia,
            usuario,
            taller,
            total,
            pendiente,
            fecha,
            tipo,
            estado)
        values(:codigo,
                :guia,
                :usuario,
                :taller,
                :total,
                :pendiente,
                :fecha,
                :tipo,
                :estado)";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
        $stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);
        $stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_INT);
        $stmt->bindParam(":total", $datos["total"], PDO::PARAM_INT);
        $stmt->bindParam(":pendiente", $datos["pendiente"], PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_INT);
        $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt = null;
    }

    static public function mdlCrearArreglosDetalle($datos)
    {
        $sql = "INSERT
            into
            arreglos_detallejf (
            codigo,
            articulo,
            cantidad,
            pendiente,
            id_cierre)
        values(:codigo,
                :articulo,
                :cantidad,
                :pendiente,
                :id_cierre)";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
        $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
        $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
        $stmt->bindParam(":pendiente", $datos["pendiente"], PDO::PARAM_INT);
        $stmt->bindParam(":id_cierre", $datos["id_cierre"], PDO::PARAM_INT);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return $stmt->errorInfo();
        }

        $stmt = null;
    }

    static public function mdlRangoFechasArreglos($fechaInicial, $fechaFinal)
    {

        if ($fechaInicial == "null") {

            $stmt = Conexion::conectar()->prepare("SELECT 
			se.*,
			s.nom_sector,
			u.nombre 
		  FROM
			arreglos se 
			LEFT JOIN sectorjf s 
			  ON se.taller = s.cod_sector 
			LEFT JOIN usuariosjf u 
			  ON se.usuario = u.id 
			  WHERE YEAR(se.fecha) = YEAR(NOW()) 
		  ORDER BY se.id ASC ");

            $stmt->execute();

            return $stmt->fetchAll();
        } else if ($fechaInicial == $fechaFinal) {

            $stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  arreglos se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) like '%$fechaFinal%'");

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

                $stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  arreglos se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
            } else {


                $stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  arreglos se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'");
            }

            $stmt->execute();

            return $stmt->fetchAll();
        }
    }

    static public function verArreglosReporte($id)
    {
        if ($id == null) {
            $stmt = Conexion::conectar()
                ->prepare("SELECT
                    a2.fecha ,
                    a2.codigo ,
                    a2.guia ,
                    a2.taller ,
                    s.nom_sector ,
                    ad.articulo ,
                    a.marca,
                    a.modelo,
                    a.nombre,
                    a.cod_color,
                    a.color,
                    a.cod_talla,
                    a.talla,
                    ad.cantidad ,
                    ad.pendiente
                from
                    arreglos_detallejf ad
                left join articulojf a
                    on
                    ad.articulo = a.articulo
                left join arreglos a2 
                    on
                    ad.codigo = a2.codigo
                left join sectorjf s 
                    on
                    a2.taller = s.cod_sector
                order by
                    a2.fecha desc,
                    a2.taller,
                    ad.articulo");
        } else {
            $stmt = Conexion::conectar()
                ->prepare("SELECT
                    a2.fecha ,
                    a2.codigo ,
                    a2.guia ,
                    a2.taller ,
                    s.nom_sector ,
                    ad.articulo ,
                    a.marca,
                    a.modelo,
                    a.nombre,
                    a.cod_color,
                    a.color,
                    a.cod_talla,
                    a.talla,
                    ad.cantidad ,
                    ad.pendiente
                from
                    arreglos_detallejf ad
                left join articulojf a
                    on
                    ad.articulo = a.articulo
                left join arreglos a2 
                    on
                    ad.codigo = a2.codigo
                left join sectorjf s 
                    on
                    a2.taller = s.cod_sector
                where 
                    a2.codigo = '{$id}'
                order by
                    a2.fecha desc,
                    a2.taller,
                    ad.articulo");
        }

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt = null;
    }

    static public function verArreglosDetallesAgrupados($arreglo)
    {
        if ($arreglo != null) {
            $stmt = Conexion::conectar()->prepare("SELECT
                s.cod_sector ,
                s.nom_sector ,
                a2.guia ,
                a2.fecha ,
                a2.codigo ,
                a.modelo ,
                a.nombre ,
                a.cod_color ,
                a.color ,
                sum(
                case 
                    when a.cod_talla = 1
                    then ad.cantidad 
                    else 0
                end
                ) as t1 ,
                    sum(
                case 
                    when a.cod_talla = 2
                    then ad.cantidad 
                    else 0
                end
                ) as t2 ,
                    sum(
                case 
                    when a.cod_talla = 3
                    then ad.cantidad 
                    else 0
                end
                ) as t3 ,
                    sum(
                case 
                    when a.cod_talla = 4
                    then ad.cantidad 
                    else 0
                end
                ) as t4 ,
                    sum(
                case 
                    when a.cod_talla = 5
                    then ad.cantidad 
                    else 0
                end
                ) as t5 ,
                    sum(
                case 
                    when a.cod_talla = 6
                    then ad.cantidad 
                    else 0
                end
                ) as t6 ,
                    sum(
                case 
                    when a.cod_talla = 7
                    then ad.cantidad 
                    else 0
                end
                ) as t7 ,
                    sum(
                case 
                    when a.cod_talla = 8
                    then ad.cantidad 
                    else 0
                end
                ) as t8 ,
                sum(ad.cantidad) as total
            from
                arreglos_detallejf ad
            left join articulojf a 
                on
                ad.articulo = a.articulo
            left join arreglos a2 
                on
                ad.codigo = a2.codigo
            left join sectorjf s 
                on
                a2.taller = s.cod_sector
            where
	            a2.codigo = '{$arreglo}'
            group by 
                    s.cod_sector ,
                s.nom_sector ,
                a2.guia ,
                a2.fecha ,
                a2.codigo ,
                a.modelo ,
                a.nombre ,
                a.cod_color ,
                a.color");
        } else {
            $stmt = Conexion::conectar()->prepare("");
        }

        $stmt->execute();

        return $stmt->fetchAll();

        $stmt = null;
    }
}
