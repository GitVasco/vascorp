<?php

require_once "conexion.php";

class ModeloEstadoCuenta
{
    /** Condición de documento pendiente (case-insensitive). */
    private static function sqlPendiente($alias = "c")
    {
        return "LOWER(TRIM($alias.estado)) = 'pendiente'";
    }

    public static function mdlBuscarClientes($q, $limite = 80)
    {
        $limite = max(1, min(200, (int) $limite));
        $q = trim((string) $q);

        if ($q === "") {
            $stmt = Conexion::conectar()->prepare(
                "SELECT c.codigo, c.nombre, c.documento, c.grupo,
                        ge.nombre AS grupo_nombre
                 FROM clientesjf c
                 LEFT JOIN grupos_empresarialesjf ge
                   ON TRIM(ge.codigo) = TRIM(c.grupo) AND ge.estado = 1
                 WHERE c.estado = 1
                 ORDER BY c.nombre ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $like = "%" . $q . "%";
        $stmt = Conexion::conectar()->prepare(
            "SELECT c.codigo, c.nombre, c.documento, c.grupo,
                    ge.nombre AS grupo_nombre
             FROM clientesjf c
             LEFT JOIN grupos_empresarialesjf ge
               ON TRIM(ge.codigo) = TRIM(c.grupo) AND ge.estado = 1
             WHERE c.estado = 1
               AND (
                    c.codigo LIKE :q
                 OR c.nombre LIKE :q2
                 OR c.documento LIKE :q3
               )
             ORDER BY
               CASE WHEN c.codigo = :qExact THEN 0
                    WHEN c.codigo LIKE :qPref THEN 1
                    ELSE 2 END,
               c.nombre ASC
             LIMIT $limite"
        );
        $stmt->bindValue(":q", $like, PDO::PARAM_STR);
        $stmt->bindValue(":q2", $like, PDO::PARAM_STR);
        $stmt->bindValue(":q3", $like, PDO::PARAM_STR);
        $stmt->bindValue(":qExact", $q, PDO::PARAM_STR);
        $stmt->bindValue(":qPref", $q . "%", PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClienteCabecera($codigo)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT c.codigo, c.nombre, c.documento, c.grupo, c.telefono,
                    ge.nombre AS grupo_nombre, ge.codigo AS grupo_codigo
             FROM clientesjf c
             LEFT JOIN grupos_empresarialesjf ge
               ON TRIM(ge.codigo) = TRIM(c.grupo) AND ge.estado = 1
             WHERE c.codigo = :codigo
             LIMIT 1"
        );
        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenCliente($codigo)
    {
        $pend = self::sqlPendiente("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' THEN c.monto ELSE 0 END), 0) AS total_venta,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN c.saldo ELSE 0 END), 0) AS total_deuda,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND c.fecha_ven < NOW() THEN c.saldo ELSE 0 END), 0) AS total_vencido,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN 1 ELSE 0 END), 0) AS docs_pendientes,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND c.fecha_ven < NOW() THEN 1 ELSE 0 END), 0) AS docs_vencidos,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND IFNULL(c.protesta,0) = 1 THEN 1 ELSE 0 END), 0) AS docs_protestados
             FROM cuenta_ctejf c
             WHERE c.cliente = :cliente"
        );
        $stmt->bindParam(":cliente", $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenGrupo($codigoGrupo)
    {
        $pend = self::sqlPendiente("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' THEN c.monto ELSE 0 END), 0) AS total_venta,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN c.saldo ELSE 0 END), 0) AS total_deuda,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND c.fecha_ven < NOW() THEN c.saldo ELSE 0 END), 0) AS total_vencido,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN 1 ELSE 0 END), 0) AS docs_pendientes,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND c.fecha_ven < NOW() THEN 1 ELSE 0 END), 0) AS docs_vencidos,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND IFNULL(c.protesta,0) = 1 THEN 1 ELSE 0 END), 0) AS docs_protestados,
                COUNT(DISTINCT cli.codigo) AS total_locales
             FROM clientesjf cli
             LEFT JOIN cuenta_ctejf c ON c.cliente = cli.codigo
             WHERE TRIM(cli.grupo) = TRIM(:grupo)
               AND cli.estado = 1"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlLocalesGrupo($codigoGrupo)
    {
        $pend = self::sqlPendiente("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                cli.codigo,
                cli.nombre,
                cli.documento,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN c.saldo ELSE 0 END), 0) AS deuda,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend AND c.fecha_ven < NOW() THEN c.saldo ELSE 0 END), 0) AS vencido,
                IFNULL(SUM(CASE WHEN c.tip_mov = '+' AND $pend THEN 1 ELSE 0 END), 0) AS docs_pendientes
             FROM clientesjf cli
             LEFT JOIN cuenta_ctejf c ON c.cliente = cli.codigo
             WHERE TRIM(cli.grupo) = TRIM(:grupo)
               AND cli.estado = 1
             GROUP BY cli.codigo, cli.nombre, cli.documento
             ORDER BY vencido DESC, deuda DESC, cli.nombre ASC"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlDocumentosCliente($codigo, $estado = "", $soloVencidos = false)
    {
        $pend = self::sqlPendiente("c");
        $sql = "SELECT
                    c.id,
                    c.tipo_doc,
                    c.num_cta,
                    c.cod_pago,
                    c.doc_origen,
                    c.fecha,
                    c.fecha_ven,
                    c.monto,
                    c.saldo,
                    c.ult_pago,
                    IFNULL(DATEDIFF(c.ult_pago, c.fecha_ven), 0) AS diferencia,
                    c.banco,
                    c.num_unico,
                    c.vendedor,
                    c.renovacion,
                    c.protesta,
                    c.estado,
                    DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha_fmt,
                    DATE_FORMAT(c.fecha_ven, '%d/%m/%Y') AS fecha_ven_fmt,
                    DATE_FORMAT(c.ult_pago, '%d/%m/%Y') AS ult_pago_fmt,
                    CASE WHEN $pend AND c.fecha_ven < NOW() THEN 1 ELSE 0 END AS es_vencido
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '+'
                  AND c.cliente = :cliente";

        $estado = strtolower(trim((string) $estado));
        if ($estado === "pendiente") {
            $sql .= " AND $pend";
        } elseif ($estado === "cancelado") {
            $sql .= " AND LOWER(TRIM(c.estado)) <> 'pendiente'";
        }

        if ($soloVencidos) {
            $sql .= " AND $pend AND c.fecha_ven < NOW()";
        }

        $sql .= " ORDER BY c.fecha_ven DESC, c.id DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":cliente", $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCancelaciones($tipoDoc, $numCta)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                c.id,
                c.cod_pago,
                c.doc_origen,
                c.fecha,
                DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha_fmt,
                c.notas,
                c.monto
             FROM cuenta_ctejf c
             WHERE c.tip_mov = '-'
               AND c.tipo_doc = :tipoDoc
               AND c.num_cta = :numCta
             ORDER BY c.fecha DESC, c.id DESC"
        );
        $stmt->bindParam(":tipoDoc", $tipoDoc, PDO::PARAM_STR);
        $stmt->bindParam(":numCta", $numCta, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUltPagosCliente($cliente)
    {
        return self::mdlUltPagosPorClientes(array($cliente));
    }

    public static function mdlUltPagosGrupo($codigoGrupo)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT codigo FROM clientesjf
             WHERE TRIM(grupo) = TRIM(:grupo) AND estado = 1"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();
        $codigos = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $loc) {
            if (!empty($loc["codigo"])) {
                $codigos[] = $loc["codigo"];
            }
        }
        return self::mdlUltPagosPorClientes($codigos);
    }

    private static function mdlUltPagosPorClientes(array $codigos)
    {
        $codigos = array_values(array_filter(array_map("trim", $codigos)));
        if (count($codigos) === 0) {
            return array();
        }

        $placeholders = array();
        foreach ($codigos as $i => $codigo) {
            $placeholders[] = ":c$i";
        }
        $in = implode(",", $placeholders);

        $sql = "SELECT
                  YEAR(c.fecha) AS anno,
                  CASE MONTH(c.fecha)
                    WHEN 1 THEN 'ENERO' WHEN 2 THEN 'FEBRERO' WHEN 3 THEN 'MARZO'
                    WHEN 4 THEN 'ABRIL' WHEN 5 THEN 'MAYO' WHEN 6 THEN 'JUNIO'
                    WHEN 7 THEN 'JULIO' WHEN 8 THEN 'AGOSTO' WHEN 9 THEN 'SEPTIEMBRE'
                    WHEN 10 THEN 'OCTUBRE' WHEN 11 THEN 'NOVIEMBRE' ELSE 'DICIEMBRE'
                  END AS mes,
                  MONTH(c.fecha) AS mes_num,
                  ROUND(SUM(c.monto), 2) AS monto,
                  ROUND(SUM(CASE WHEN TRIM(c.vendedor) IN ('00','00B','00b','04','05','19','22','27') THEN c.monto ELSE 0 END), 2) AS monto_jackyform,
                  ROUND(SUM(CASE WHEN TRIM(c.vendedor) IN ('24','26','28') THEN c.monto ELSE 0 END), 2) AS monto_rosalinda
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '-'
                  AND c.fecha BETWEEN DATE_SUB(NOW(), INTERVAL 7 MONTH) AND NOW()
                  AND c.cliente IN ($in)
                  AND c.cod_pago IN ('00','05','06','14','80','82')
                GROUP BY YEAR(c.fecha), MONTH(c.fecha)
                ORDER BY YEAR(c.fecha) DESC, MONTH(c.fecha) DESC
                LIMIT 6";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($codigos as $i => $codigo) {
            $stmt->bindValue(":c$i", $codigo, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
