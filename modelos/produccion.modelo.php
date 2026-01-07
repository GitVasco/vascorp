<?php

require_once "conexion.php";

class ModeloProduccion
{

  /* 
	* MOSTRAR PRODUCCION
	*/
  static public function mdlMostrarQuincenas($valor)
  {

    if ($valor != null) {

      $stmt = Conexion::conectar()->prepare("SELECT 
                                                            q.id,
                                                            q.ano,
                                                            m.mes,
                                                            q.mes AS nmes,
                                                            q.quincena AS nquincena,
                                                            CASE
                                                            WHEN q.quincena = '1' 
                                                            THEN '1ra Quincena' 
                                                            ELSE '2da Quincena' 
                                                            END AS quincena,
                                                            q.inicio,
                                                            q.fin,
                                                            u.nombre,
                                                            q.fecha_creacion 
                                                        FROM
                                                            quincenasjf q 
                                                            LEFT JOIN usuariosjf u 
                                                            ON q.usuario = u.id 
                                                            LEFT JOIN 
                                                            (SELECT DISTINCT 
                                                                codigo,
                                                                descripcion AS mes 
                                                            FROM
                                                                meses) AS m 
                                                            ON q.mes = m.codigo 
                                                        WHERE q.id = :valor
                                                        AND YEAR(q.fecha_creacion) = YEAR(NOW()) ");

      $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

      $stmt->execute();

      return $stmt->fetch();
    } else {

      $stmt = Conexion::conectar()->prepare("SELECT 
                                            q.id,
                                            q.ano,
                                            CASE
                                              WHEN q.mes = '1' 
                                              THEN 'Enero' 
                                              WHEN q.mes = '2' 
                                              THEN 'Febrero' 
                                              WHEN q.mes = '3' 
                                              THEN 'Marzo' 
                                              WHEN q.mes = '4' 
                                              THEN 'Abril' 
                                              WHEN q.mes = '5' 
                                              THEN 'Mayo' 
                                              WHEN q.mes = '6' 
                                              THEN 'Junio' 
                                              WHEN q.mes = '7' 
                                              THEN 'Julio' 
                                              WHEN q.mes = '8' 
                                              THEN 'Agosto' 
                                              WHEN q.mes = '9' 
                                              THEN 'Septiembre' 
                                              WHEN q.mes = '10' 
                                              THEN 'Octubre' 
                                              WHEN q.mes = '11' 
                                              THEN 'Noviembre' 
                                              ELSE 'Diciembre' 
                                            END AS mes,
                                            q.mes AS nmes,
                                            q.quincena AS nquincena,
                                            CASE
                                              WHEN q.quincena = '1' 
                                              THEN '1ra Quincena' 
                                              ELSE '2da Quincena' 
                                            END AS quincena,
                                            q.inicio,
                                            q.fin,
                                            u.nombre,
                                            q.fecha_creacion 
                                          FROM
                                            quincenasjf q 
                                            LEFT JOIN usuariosjf u 
                                              ON q.usuario = u.id
                                              where q.ano  = YEAR(NOW()) ");

      $stmt->execute();

      return $stmt->fetchAll();
    }

    $stmt->close();

    $stmt = null;
  }

  /*
	* CREAR QUINCENA
	*/
  static public function mdlCrearQuincenas($datos)
  {

    $stmt = Conexion::conectar()->prepare("INSERT INTO quincenasjf (
                                                ano,
                                                mes,
                                                quincena,
                                                inicio,
                                                fin,
                                                usuario
                                            ) 
                                            VALUES
                                                (
                                                :ano,
                                                :mes,
                                                :quincena,
                                                :inicio,
                                                :fin,
                                                :usuario
                                                )");

    $stmt->bindParam(":ano", $datos["ano"], PDO::PARAM_STR);
    $stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_STR);
    $stmt->bindParam(":quincena", $datos["quincena"], PDO::PARAM_STR);
    $stmt->bindParam(":inicio", $datos["inicio"], PDO::PARAM_STR);
    $stmt->bindParam(":fin", $datos["fin"], PDO::PARAM_STR);
    $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

    if ($stmt->execute()) {

      return "ok";
    } else {

      return "error";
    }

    $stmt->close();
    $stmt = null;
  }

  static public function mdlEditarQuincenas($datos)
  {

    $stmt = Conexion::conectar()->prepare("UPDATE 
                                                    quincenasjf 
                                                SET
                                                    ano = :ano,
                                                    mes = :mes,
                                                    quincena = :quincena,
                                                    inicio = :inicio,
                                                    fin = :fin,
                                                    usuario = :usuario 
                                                WHERE id = :id");

    $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
    $stmt->bindParam(":ano", $datos["ano"], PDO::PARAM_STR);
    $stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_STR);
    $stmt->bindParam(":quincena", $datos["quincena"], PDO::PARAM_STR);
    $stmt->bindParam(":inicio", $datos["inicio"], PDO::PARAM_STR);
    $stmt->bindParam(":fin", $datos["fin"], PDO::PARAM_STR);
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
	* Método para la eficiencia por mes
	*/
  static public function mdlMostrarEficiencia($inicio, $fin, $nquincena, $id, $sector)
  {
    if ($sector != "null") {

      if ($nquincena == "1") {

        $sql = "SELECT 
          et.trabajador,
          CONCAT(t.nom_tra,' ', t.ape_pat_tra) AS nom_tra,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '1' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d1,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '2' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d2,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '3' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d3,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '4' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d4,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '5' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d5,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '6' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d6,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '7' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d7,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '8' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d8,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '9' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d9,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '10' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d10,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '11' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d11,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '12' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d12,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '13' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d13,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '14' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d14,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '15' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d15,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '16' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d16,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '27' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d27,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '28' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d28,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '29' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d29,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '30' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d30,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '31' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d31 
        FROM
          entallerjf et 
          LEFT JOIN 
            (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
            ON et.trabajador = asi.trabajador 
            AND DATE(fecha_terminado) = asi.fecha 
          LEFT JOIN trabajadorjf t 
            ON et.trabajador = t.cod_tra,
          (SELECT 
            inicio,
            fin 
          FROM
            quincenasjf q 
          WHERE q.id = :id) AS q 
        WHERE DATE(et.fecha_terminado) BETWEEN :inicio
          AND :fin
        AND t.sector = '" . $sector . "'
        GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      } else {

        $sql = "SELECT 
          et.trabajador,
          CONCAT(t.nom_tra,' ', t.ape_pat_tra) AS nom_tra,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '1' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d1,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '12' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d12,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '13' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d13,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '14' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d14,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '15' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d15,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '16' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d16,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '17' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d17,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '18' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d18,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '19' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d19,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '20' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d20,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '21' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d21,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '22' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d22,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '23' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d23,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '24' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d24,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '25' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d25,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '26' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d26,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '27' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d27,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '28' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d28,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '29' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d29,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '30' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d30,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '31' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d31 
        FROM
          entallerjf et 
          LEFT JOIN 
            (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
            ON et.trabajador = asi.trabajador 
            AND DATE(fecha_terminado) = asi.fecha 
          LEFT JOIN trabajadorjf t 
            ON et.trabajador = t.cod_tra,
          (SELECT 
            inicio,
            fin 
          FROM
            quincenasjf q 
          WHERE q.id = :id) AS q 
        WHERE DATE(et.fecha_terminado) BETWEEN :inicio 
          AND :fin 
        AND t.sector = '" . $sector . "'
        GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      }
    } else {

      if ($nquincena == "1") {

        $sql = "SELECT 
          et.trabajador,
          CONCAT(t.nom_tra,' ', t.ape_pat_tra) AS nom_tra,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '1' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d1,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '2' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d2,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '3' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d3,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '4' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d4,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '5' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d5,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '6' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d6,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '7' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d7,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '8' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d8,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '9' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d9,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '10' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d10,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '11' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d11,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '12' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d12,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '13' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d13,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '14' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d14,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '15' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d15,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '16' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d16,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '27' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d27,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '28' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d28,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '29' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d29,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '30' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d30,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '31' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d31 
        FROM
          entallerjf et 
          LEFT JOIN 
            (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
            ON et.trabajador = asi.trabajador 
            AND DATE(fecha_terminado) = asi.fecha 
          LEFT JOIN trabajadorjf t 
            ON et.trabajador = t.cod_tra,
          (SELECT 
            inicio,
            fin 
          FROM
            quincenasjf q 
          WHERE q.id = :id) AS q 
        WHERE DATE(et.fecha_terminado) BETWEEN :inicio
          AND :fin
        GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      } else {

        $sql = "SELECT 
          et.trabajador,
          CONCAT(t.nom_tra,' ', t.ape_pat_tra) AS nom_tra,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '1' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d1,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '12' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d12,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '13' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d13,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '14' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d14,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '15' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d15,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '16' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d16,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '17' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d17,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '18' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d18,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '19' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d19,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '20' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d20,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '21' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d21,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '22' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d22,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '23' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d23,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '24' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d24,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '25' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d25,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '26' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d26,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '27' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d27,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '28' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d28,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '29' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d29,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '30' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d30,
          SUM(
            CASE
              WHEN DAY(fecha_terminado) = '31' 
              THEN et.total_tiempo / asi.minutos * 100 
              ELSE 0 
            END
          ) AS d31 
        FROM
          entallerjf et 
          LEFT JOIN 
            (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
            ON et.trabajador = asi.trabajador 
            AND DATE(fecha_terminado) = asi.fecha 
          LEFT JOIN trabajadorjf t 
            ON et.trabajador = t.cod_tra,
          (SELECT 
            inicio,
            fin 
          FROM
            quincenasjf q 
          WHERE q.id = :id) AS q 
        WHERE DATE(et.fecha_terminado) BETWEEN :inicio 
          AND :fin 
        GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      }
    }

    $stmt = null;
  }

  /*
	* Método para los pagos por mes
	*/
  static public function mdlMostrarPagos($inicio, $fin, $nquincena, $id, $sector)
  {
    if ($sector != "null") {
      if ($nquincena == "1") {

        $sql = "SELECT 
              et.trabajador,
              CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '1' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d1,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '2' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d2,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '3' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d3,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '4' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d4,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '5' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d5,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '6' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d6,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '7' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d7,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '8' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d8,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '9' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d9,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '10' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d10,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '11' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d11,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '12' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d12,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '13' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d13,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '14' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d14,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '15' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d15,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '16' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d16,
              SUM(
              CASE
                WHEN DAY(fecha_terminado) = '27' 
                THEN et.total_precio 
                ELSE 0 
              END
              ) AS d27,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '28' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d28,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '29' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d29,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '30' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d30,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '31' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d31,
              SUM(et.total_precio) AS total,
              (t.sueldo_total/2) as sueldo_total
            FROM
              entallerjf et 
              LEFT JOIN 
                (SELECT DISTINCT 
                  et.trabajador,
                  DATE(a.fecha) AS fecha,
                  a.minutos 
                FROM
                  asistenciasjf a 
                  LEFT JOIN entallerjf et 
                    ON a.id_trabajador = et.trabajador 
                    AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                WHERE et.trabajador IS NOT NULL 
                AND (a.fecha BETWEEN :inicio
                  AND :fin
                )) AS asi 
                ON et.trabajador = asi.trabajador 
                AND DATE(fecha_terminado) = asi.fecha 
              LEFT JOIN trabajadorjf t 
                ON et.trabajador = t.cod_tra,
              (SELECT 
                inicio,
                fin 
              FROM
                quincenasjf q 
              WHERE q.id = :id) AS q 
            WHERE DATE(et.fecha_terminado) BETWEEN :inicio
              AND :fin
            AND t.sector = '" . $sector . "'
            GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      } else {

        $sql = "SELECT 
                et.trabajador,
                CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '1' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d1,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '12' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d12,                
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '13' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d13,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '14' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d14,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '15' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d15,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '16' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d16,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '17' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d17,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '18' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d18,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '19' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d19,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '20' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d20,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '21' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d21,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '22' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d22,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '23' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d23,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '24' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d24,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '25' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d25,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '26' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d26,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '27' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d27,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '28' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d28,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '29' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d29,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '30' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d30,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '31' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d31,
                SUM(et.total_precio) AS total,
                (t.sueldo_total/2) as sueldo_total
              FROM
                entallerjf et 
                LEFT JOIN 
                  (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
                  ON et.trabajador = asi.trabajador 
                  AND DATE(fecha_terminado) = asi.fecha 
                LEFT JOIN trabajadorjf t 
                  ON et.trabajador = t.cod_tra,
                (SELECT 
                  inicio,
                  fin 
                FROM
                  quincenasjf q 
                WHERE q.id = :id) AS q 
              WHERE DATE(et.fecha_terminado) BETWEEN :inicio 
                AND :fin 
              AND t.sector = '" . $sector . "'
              GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      }
    } else {
      if ($nquincena == "1") {

        $sql = "SELECT 
              et.trabajador,
              CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '1' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d1,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '2' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d2,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '3' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d3,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '4' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d4,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '5' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d5,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '6' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d6,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '7' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d7,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '8' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d8,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '9' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d9,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '10' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d10,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '11' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d11,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '12' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d12,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '13' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d13,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '14' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d14,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '15' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d15,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '16' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d16,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '27' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d27,              
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '28' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d28,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '29' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d29,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '30' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d30,
              SUM(
                CASE
                  WHEN DAY(fecha_terminado) = '31' 
                  THEN et.total_precio 
                  ELSE 0 
                END
              ) AS d31,
              SUM(et.total_precio) AS total,
              (t.sueldo_total/2) as sueldo_total
            FROM
              entallerjf et 
              LEFT JOIN 
                (SELECT DISTINCT 
                  et.trabajador,
                  DATE(a.fecha) AS fecha,
                  a.minutos 
                FROM
                  asistenciasjf a 
                  LEFT JOIN entallerjf et 
                    ON a.id_trabajador = et.trabajador 
                    AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                WHERE et.trabajador IS NOT NULL
                AND (a.fecha BETWEEN :inicio
                  AND :fin
                )) AS asi 
                ON et.trabajador = asi.trabajador 
                AND DATE(fecha_terminado) = asi.fecha 
              LEFT JOIN trabajadorjf t 
                ON et.trabajador = t.cod_tra,
              (SELECT 
                inicio,
                fin 
              FROM
                quincenasjf q 
              WHERE q.id = :id) AS q 
            WHERE DATE(et.fecha_terminado) BETWEEN :inicio
              AND :fin
            GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      } else {

        $sql = "SELECT 
                et.trabajador,
                CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '1' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d1,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '12' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d12,                
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '13' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d13,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '14' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d14,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '15' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d15,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '16' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d16,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '17' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d17,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '18' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d18,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '19' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d19,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '20' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d20,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '21' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d21,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '22' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d22,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '23' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d23,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '24' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d24,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '25' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d25,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '26' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d26,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '27' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d27,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '28' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d28,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '29' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d29,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '30' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d30,
                SUM(
                  CASE
                    WHEN DAY(fecha_terminado) = '31' 
                    THEN et.total_precio 
                    ELSE 0 
                  END
                ) AS d31,
                SUM(et.total_precio) AS total,
                (t.sueldo_total/2) as sueldo_total
              FROM
                entallerjf et 
                LEFT JOIN 
                  (SELECT DISTINCT 
                    et.trabajador,
                    DATE(a.fecha) AS fecha,
                    a.minutos 
                  FROM
                    asistenciasjf a 
                    LEFT JOIN entallerjf et 
                      ON a.id_trabajador = et.trabajador 
                      AND DATE(a.fecha) = DATE(et.fecha_terminado) 
                  WHERE et.trabajador IS NOT NULL
                  AND (a.fecha BETWEEN :inicio
                  AND :fin
                  )) AS asi 
                  ON et.trabajador = asi.trabajador 
                  AND DATE(fecha_terminado) = asi.fecha 
                LEFT JOIN trabajadorjf t 
                  ON et.trabajador = t.cod_tra,
                (SELECT 
                  inicio,
                  fin 
                FROM
                  quincenasjf q 
                WHERE q.id = :id) AS q 
              WHERE DATE(et.fecha_terminado) BETWEEN :inicio 
                AND :fin 
              GROUP BY et.trabajador";

        $stmt = Conexion::conectar()->prepare($sql);

        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll();
      }
    }


    $stmt = null;
  }

  /* 
	* BORRAR QUINCENA
	*/
  static public function mdlEliminarQuincena($id)
  {

    $stmt = Conexion::conectar()->prepare("DELETE FROM quincenasjf WHERE id = :id ");

    $stmt->bindParam(":id", $id, PDO::PARAM_STR);

    if ($stmt->execute()) {

      return "ok";
    } else {

      return "error";
    }

    $stmt->close();

    $stmt = null;
  }

  /* 
	* ACTUALIZAR QUINCENA
	*/
  static public function mdlActualizarPrecioTiempo($inicio, $fin)
  {

    $stmt = Conexion::conectar()->prepare("UPDATE 
    entallerjf e 
    LEFT JOIN articulojf a 
      ON e.articulo = a.articulo 
    LEFT JOIN operaciones_detallejf od 
      ON e.cod_operacion = od.cod_operacion 
      AND a.modelo = od.modelo SET e.total_precio = (e.cantidad / 12) * precio_doc 
  WHERE DATE(e.fecha_terminado) BETWEEN :inicio
    AND :fin ");

    $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
    $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);

    if ($stmt->execute()) {

      return "ok";
    } else {

      return "error";
    }

    $stmt->close();

    $stmt = null;
  }


  /* 
	* MOSTRAR PRODUCCION
	*/
  static public function mdlMostrarAvances($inicio, $fin)
  {

    $stmt = Conexion::conectar()->prepare("SELECT 
    a.id_trabajador,
    CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nombre,
    FORMAT(ROUND(et.produccion, 2), 2) AS produccion,
    FORMAT(ROUND(et.sueldo_quincena, 2), 2) AS sueldo,
    FORMAT(ROUND(et.diferencia, 2), 2) AS diferencia,
    FORMAT(ROUND(et.incentivo, 2), 2) AS incentivo,
    FORMAT(
      CASE
        WHEN et.produccion > et.sueldo_quincena 
        THEN ROUND(et.produccion + et.incentivo, 2) 
        ELSE ROUND(et.sueldo_quincena, 2) 
      END,
      2
    ) AS total 
  FROM
    asistenciasjf a 
    LEFT JOIN 
      (SELECT 
        et.trabajador,
        SUM(total_precio) AS produccion,
        (t.sueldo_total / 2) AS sueldo_quincena,
        CASE
          WHEN SUM(total_precio) >= 600 
          THEN 'A' 
          WHEN SUM(total_precio) >= 550 
          AND SUM(total_precio) < 600 
          THEN 'B' 
          WHEN SUM(total_precio) >= 501 
          AND SUM(total_precio) < 550 
          THEN 'C' 
          WHEN SUM(total_precio) >= 475 
          AND SUM(total_precio) <= 500 
          THEN 'D' 
          WHEN SUM(total_precio) >= 450 
          AND SUM(total_precio) < 475 
          THEN 'E' 
          WHEN SUM(total_precio) >= 0 
          AND SUM(total_precio) < 450 
          THEN 'F' 
        END AS categoria,
        CASE
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          THEN 0 
          WHEN SUM(total_precio) < (t.sueldo_total / 2) 
          THEN ROUND(
            SUM(total_precio) - (t.sueldo_total / 2),
            2
          ) 
        END AS diferencia,
        CASE
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (SUM(total_precio) >= 600) 
          THEN 110 
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (
            SUM(total_precio) >= 550 
            AND SUM(total_precio) < 600
          ) 
          THEN 110 
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (
            SUM(total_precio) >= 500 
            AND SUM(total_precio) < 550
          ) 
          THEN 100 
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (
            SUM(total_precio) >= 475 
            AND SUM(total_precio) < 500
          ) 
          THEN 85 
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (
            SUM(total_precio) >= 450 
            AND SUM(total_precio) < 475
          ) 
          THEN 70 
          WHEN SUM(total_precio) > (t.sueldo_total / 2) 
          AND (
            SUM(total_precio) >= 0 
            AND SUM(total_precio) < 450
          ) 
          THEN 55 
          ELSE 0 
        END AS incentivo 
      FROM
        entallerjf et 
        LEFT JOIN trabajadorjf t 
          ON et.trabajador = t.cod_tra 
        LEFT JOIN articulojf a 
          ON et.articulo = a.articulo 
        LEFT JOIN modelojf m 
          ON a.modelo = m.modelo 
      WHERE (
          DATE(et.fecha_terminado) BETWEEN '" . $inicio . "' 
      AND '" . $fin . "'
        ) 
        AND m.tipo NOT IN ('BRASIER') 
      GROUP BY et.trabajador) AS et 
      ON a.id_trabajador = et.trabajador 
    LEFT JOIN trabajadorjf t 
      ON a.id_trabajador = t.cod_tra 
    LEFT JOIN tipo_trabajadorjf tt 
      ON t.cod_tip_tra = tt.cod_tip_tra 
  WHERE (
      DATE(a.fecha) BETWEEN '" . $inicio . "' 
      AND '" . $fin . "'
    ) 
    AND tt.cod_tip_tra = '1' 
    AND et.produccion > 0 
    AND a.estado = 'asistio' 
  GROUP BY a.id_trabajador 
  ORDER BY 
    CASE
      WHEN et.produccion > et.sueldo_quincena 
      THEN ROUND(et.produccion + et.incentivo, 2) 
      ELSE ROUND(et.sueldo_quincena, 2) 
    END DESC");


    $stmt->execute();

    return $stmt->fetchAll();


    $stmt->close();

    $stmt = null;
  }

  /* 
	* MOSTRAR PRODUCCION
	*/
  static public function mdlMostrarTrabTaller($taller)
  {

    $stmt = Conexion::conectar()->prepare("SELECT 
              t.cod_tra,
              CONCAT(
                t.nom_tra,
                ' ',
                ape_pat_tra,
                ' ',
                ape_mat_tra
              ) AS trabajador,
              t.configuracion,
              t.usuario 
            FROM
              trabajadorjf t 
            WHERE t.sector = :taller
              AND t.estado = 'Activo'");

    $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);

    $stmt->execute();

    return $stmt->fetchAll();

    $stmt->close();

    $stmt = null;
  }

  /* 
	* MOSTRAR PRODUCCION
	*/
  static public function mdlTablaEficienciaGlobal($taller)
  {

    if ($taller == "null") {

      $stmt = Conexion::conectar()->prepare("SELECT 
                                        t.sector,
                                        t.cod_tra,
                                        CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
                                        SUM(
                                            CASE
                                            WHEN q.id = '27' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q27,
                                        SUM(
                                            CASE
                                            WHEN q.id = '28' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q28,
                                        SUM(
                                            CASE
                                            WHEN q.id = '29' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q29,
                                        SUM(
                                            CASE
                                            WHEN q.id = '30' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q30,
                                        SUM(
                                            CASE
                                            WHEN q.id = '31' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q31,
                                        SUM(
                                            CASE
                                            WHEN q.id = '32' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q32,
                                        SUM(
                                            CASE
                                            WHEN q.id = '33' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q33,
                                        SUM(
                                            CASE
                                            WHEN q.id = '34' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q34,
                                        SUM(
                                            CASE
                                            WHEN q.id = '35' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q35,
                                        SUM(
                                            CASE
                                            WHEN q.id = '36' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q36,
                                        SUM(
                                            CASE
                                            WHEN q.id = '37' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q37,
                                        SUM(
                                            CASE
                                            WHEN q.id = '38' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q38,
                                        SUM(
                                            CASE
                                            WHEN q.id = '39' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q39,
                                        SUM(
                                            CASE
                                            WHEN q.id = '40' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q40,
                                        SUM(
                                            CASE
                                            WHEN q.id = '41' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q41,
                                        SUM(
                                            CASE
                                            WHEN q.id = '42' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q42,
                                        SUM(
                                            CASE
                                            WHEN q.id = '43' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q43,
                                        SUM(
                                            CASE
                                            WHEN q.id = '44' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q44,
                                        SUM(
                                            CASE
                                            WHEN q.id = '45' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q45,
                                        SUM(
                                            CASE
                                            WHEN q.id = '46' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q46,
                                        SUM(
                                            CASE
                                            WHEN q.id = '47' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q47,
                                        SUM(
                                            CASE
                                            WHEN q.id = '38' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q38,
                                        SUM(
                                            CASE
                                            WHEN q.id = '48' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q48,
                                        SUM(
                                            CASE
                                            WHEN q.id = '49' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q49,
                                        SUM(
                                            CASE
                                            WHEN q.id = '50' 
                                            THEN q.total_tiempo / q.minutos 
                                            ELSE 0 
                                            END
                                        ) AS q50 
                                        FROM
                                        trabajadorjf t 
                                        LEFT JOIN 
                                            (SELECT 
                                            q.id,
                                            e.trabajador,
                                            SUM(e.total_tiempo) AS total_tiempo,
                                            a.minutos 
                                            FROM
                                            entallerjf e 
                                            LEFT JOIN quincenasjf q 
                                                ON DATE(e.fecha_terminado) BETWEEN q.inicio 
                                                AND q.fin 
                                            LEFT JOIN 
                                                (SELECT 
                                                q.id,
                                                a.id_trabajador,
                                                SUM(a.minutos) AS minutos 
                                                FROM
                                                asistenciasjf a 
                                                LEFT JOIN quincenasjf q 
                                                    ON DATE(a.fecha) BETWEEN q.inicio 
                                                    AND q.fin 
                                                WHERE q.id IN (
                                                    '27',
                                                    '28',
                                                    '29',
                                                    '30',
                                                    '31',
                                                    '32',
                                                    '33',
                                                    '34',
                                                    '35',
                                                    '36',
                                                    '37',
                                                    '38',
                                                    '39',
                                                    '40',
                                                    '41',
                                                    '42',
                                                    '43',
                                                    '44',
                                                    '45',
                                                    '46',
                                                    '47',
                                                    '48',
                                                    '49',
                                                    '50'
                                                ) 
                                                GROUP BY q.id,
                                                a.id_trabajador) AS a 
                                                ON q.id = a.id 
                                                AND e.trabajador = a.id_trabajador 
                                            WHERE e.estado = 3 
                                            AND q.id IN (
                                                '27',
                                                '28',
                                                '29',
                                                '30',
                                                '31',
                                                '32',
                                                '33',
                                                '34',
                                                '35',
                                                '36',
                                                '37',
                                                '38',
                                                '39',
                                                '40',
                                                '41',
                                                '42',
                                                '43',
                                                '44',
                                                '45',
                                                '46',
                                                '47',
                                                '48',
                                                '49',
                                                '50'
                                            ) 
                                            GROUP BY q.id,
                                            e.trabajador) AS q 
                                            ON t.cod_tra = q.trabajador 
                                        WHERE t.estado = 'activo' 
                                        AND t.cod_tra NOT IN ('24', '79') 
                                        GROUP BY q.trabajador");

      $stmt->execute();

      return $stmt->fetchAll();
    } else {

      $stmt = Conexion::conectar()->prepare("SELECT 
      t.sector,
      t.cod_tra,
      CONCAT(t.nom_tra, ' ', t.ape_pat_tra) AS nom_tra,
      SUM(
          CASE
          WHEN q.id = '27' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q27,
      SUM(
          CASE
          WHEN q.id = '28' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q28,
      SUM(
          CASE
          WHEN q.id = '29' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q29,
      SUM(
          CASE
          WHEN q.id = '30' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q30,
      SUM(
          CASE
          WHEN q.id = '31' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q31,
      SUM(
          CASE
          WHEN q.id = '32' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q32,
      SUM(
          CASE
          WHEN q.id = '33' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q33,
      SUM(
          CASE
          WHEN q.id = '34' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q34,
      SUM(
          CASE
          WHEN q.id = '35' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q35,
      SUM(
          CASE
          WHEN q.id = '36' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q36,
      SUM(
          CASE
          WHEN q.id = '37' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q37,
      SUM(
          CASE
          WHEN q.id = '38' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q38,
      SUM(
          CASE
          WHEN q.id = '39' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q39,
      SUM(
          CASE
          WHEN q.id = '40' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q40,
      SUM(
          CASE
          WHEN q.id = '41' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q41,
      SUM(
          CASE
          WHEN q.id = '42' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q42,
      SUM(
          CASE
          WHEN q.id = '43' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q43,
      SUM(
          CASE
          WHEN q.id = '44' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q44,
      SUM(
          CASE
          WHEN q.id = '45' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q45,
      SUM(
          CASE
          WHEN q.id = '46' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q46,
      SUM(
          CASE
          WHEN q.id = '47' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q47,
      SUM(
          CASE
          WHEN q.id = '38' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q38,
      SUM(
          CASE
          WHEN q.id = '48' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q48,
      SUM(
          CASE
          WHEN q.id = '49' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q49,
      SUM(
          CASE
          WHEN q.id = '50' 
          THEN q.total_tiempo / q.minutos 
          ELSE 0 
          END
      ) AS q50 
      FROM
      trabajadorjf t 
      LEFT JOIN 
          (SELECT 
          q.id,
          e.trabajador,
          SUM(e.total_tiempo) AS total_tiempo,
          a.minutos 
          FROM
          entallerjf e 
          LEFT JOIN quincenasjf q 
              ON DATE(e.fecha_terminado) BETWEEN q.inicio 
              AND q.fin 
          LEFT JOIN 
              (SELECT 
              q.id,
              a.id_trabajador,
              SUM(a.minutos) AS minutos 
              FROM
              asistenciasjf a 
              LEFT JOIN quincenasjf q 
                  ON DATE(a.fecha) BETWEEN q.inicio 
                  AND q.fin 
              WHERE q.id IN (
                  '27',
                  '28',
                  '29',
                  '30',
                  '31',
                  '32',
                  '33',
                  '34',
                  '35',
                  '36',
                  '37',
                  '38',
                  '39',
                  '40',
                  '41',
                  '42',
                  '43',
                  '44',
                  '45',
                  '46',
                  '47',
                  '48',
                  '49',
                  '50'
              ) 
              GROUP BY q.id,
              a.id_trabajador) AS a 
              ON q.id = a.id 
              AND e.trabajador = a.id_trabajador 
          WHERE e.estado = 3 
          AND q.id IN (
              '27',
              '28',
              '29',
              '30',
              '31',
              '32',
              '33',
              '34',
              '35',
              '36',
              '37',
              '38',
              '39',
              '40',
              '41',
              '42',
              '43',
              '44',
              '45',
              '46',
              '47',
              '48',
              '49',
              '50'
          ) 
          GROUP BY q.id,
          e.trabajador) AS q 
          ON t.cod_tra = q.trabajador 
      WHERE t.estado = 'activo' 
      AND t.cod_tra NOT IN ('24', '79') 
      AND t.sector = :taller
      GROUP BY q.trabajador");

      $stmt->bindParam(":taller", $taller, PDO::PARAM_STR);

      $stmt->execute();

      return $stmt->fetchAll();
    }

    $stmt->close();

    $stmt = null;
  }

  static public function mdlVerIngresos($documento, $articulo)
  {

    $stmt = Conexion::conectar()->prepare("SELECT 
                                    * 
                                FROM
                                    movimientosjf_2026 
                                WHERE documento = '$documento' 
                                    AND articulo = '$articulo'");

    $stmt->execute();

    return $stmt->fetch();

    $stmt->close();

    $stmt = null;
  }

  // traemos los datos de los articulos segun el tipo de prehormado
  static public function mdlMostrarArticulosBrasier()
  {

    $stmt = Conexion::conectar()->prepare("SELECT
                    a.articulo ,
                    a.marca ,
                    a.modelo ,
                    a.nombre ,
                    a.cod_color ,
                    a.color ,
                    a.cod_talla ,
                    a.talla 
                FROM
                    articulojf a
                WHERE
                    a.estado = 'Activo'
                    and a.linea = 'Brasier'");



    $stmt->execute();

    return $stmt->fetchAll();

    $stmt = null;
  }

  static public function mdlCrearPrehormado($datos)
  {

    $stmt = Conexion::conectar()->prepare("INSERT INTO 
                prehormado
                (tipo, 
                fecha_registro, 
                articulo, 
                cantidad, 
                usureg,
                fecreg,
                pcreg) 
                VALUES 
                (:tipo, 
                :fecha_registro, 
                :articulo, 
                :cantidad, 
                :usureg,
                :fecreg,
                :pcreg)");

    $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
    $stmt->bindParam(":fecha_registro", $datos["fecha_registro"], PDO::PARAM_STR);
    $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
    $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
    $stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
    $stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
    $stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

    if ($stmt->execute()) {

      return "ok";
    } else {

      return $stmt;
    }

    $stmt = null;
  }

  public static function mdlCrearPrehormadoMasivo($lista)
  {
    if (empty($lista)) return "vacio";

    $pdo = null;
    $stmt = null;
    try {
      $pdo = Conexion::conectar();
      $pdo->beginTransaction();

      $sql = "INSERT INTO prehormado
                (tipo, fecha_registro, articulo, cantidad, usureg, fecreg, pcreg)
                VALUES
                (:tipo, :fecha_registro, :articulo, :cantidad, :usureg, :fecreg, :pcreg)";
      $stmt = $pdo->prepare($sql);

      foreach ($lista as $datos) {
        $stmt->bindValue(":tipo",           $datos["tipo"],           PDO::PARAM_STR);
        $stmt->bindValue(":fecha_registro", $datos["fecha_registro"], PDO::PARAM_STR);
        $stmt->bindValue(":articulo",       $datos["articulo"],       PDO::PARAM_STR);
        $stmt->bindValue(":cantidad",       (int)$datos["cantidad"],  PDO::PARAM_INT);
        $stmt->bindValue(":usureg",         $datos["usureg"],         PDO::PARAM_STR);
        $stmt->bindValue(":fecreg",         $datos["fecreg"],         PDO::PARAM_STR);
        $stmt->bindValue(":pcreg",          $datos["pcreg"],          PDO::PARAM_STR);

        if (!$stmt->execute()) {
          $pdo->rollBack();
          $stmt = null;
          $pdo = null;
          return "error";
        }
      }

      $pdo->commit();
      $stmt = null;
      $pdo = null;
      return "ok";
    } catch (Exception $e) {
      if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $stmt = null;
      $pdo = null;
      return "error";
    }
  }


  static public function mdlMostrarPrehormados()
  {

    $stmt = Conexion::conectar()->prepare("SELECT
                    p.id,
                    p.tipo,
                    a.articulo,
                    concat(a.modelo, ' - ', a.nombre) as producto,
                    a.color,
                    a.talla,
                    p.cantidad,
                    p.fecha_registro
                from
                    prehormado p
                join articulojf a on
                    p.articulo = a.articulo
                where
                    p.tipo = 'producto'
                union all
                select
                    p.id,
                    p.tipo,
                    p1.codpro,
                    concat(p1.codpro, ' - ', p1.codfab, ' - ', p1.despro) as producto,
                    p1.color,
                    p1.talla,
                    p.cantidad,
                    p.fecha_registro
                from
                    prehormado p
                left join (
                    select
                        distinct 
                            pro.codpro,
                            pro.codfab,
                            pro.despro,
                            pro.CodAlm01 as stock,
                            TbUnd.Des_Corta as unidad,
                            pro.colpro,
                            TbCol.Des_Larga as color,
                            pro.talpro,
                            TbTal.Des_larga as talla,
                            pro.cuadro,
                            (
                        select 
                                despro
                        from
                                producto
                        where
                            codpro = pro.cuadro) as cuadro_nom,
                            pro.usureg,
                            left(pro.fampro,
                        3) as fam
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
                            where
                                pro.estpro = '1'
                                and left(pro.fampro,
                                3) = 'cop'
                            order by
                                pro.codfab asc) as p1
                    on
                    p.articulo = p1.codpro
                where
                    p.tipo <> 'producto'
                order by
                    fecha_registro desc,
                    tipo,
                    articulo");

    $stmt->execute();

    return $stmt->fetchAll();

    $stmt = null;
  }

  static public function verPrehormado($id)
  {
    $stmt = Conexion::conectar()->prepare("SELECT * FROM prehormado p WHERE p.id=$id");

    $stmt->execute();

    return $stmt->fetch();

    $stmt = null;
  }

  static public function mdlEditarPrehormado($datos)
  {
    $stmt = Conexion::conectar()->prepare("UPDATE
                prehormado
            set
                tipo =:tipo,
                fecha_registro =:fecha_registro ,
                articulo =:articulo ,
                cantidad =:cantidad
            where
                id = :id ");

    $stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);
    $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
    $stmt->bindParam(":fecha_registro", $datos["fecha_registro"], PDO::PARAM_STR);
    $stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
    $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);

    if ($stmt->execute()) {

      return "ok";
    } else {

      return $stmt;
    }

    $stmt = null;
  }

  static public function mdlEliminarPrehormado($id)
  {
    $stmt = Conexion::conectar()->prepare("DELETE FROM prehormado WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
      return "ok";
    } else {
      return $stmt->errorInfo();
    }
    $stmt = null;
  }
}
