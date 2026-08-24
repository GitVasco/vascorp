<?php

require_once "conexion.php";

/**
 * Persistencia del cuadre de ventas.
 * Paso 1: solo comprueba que existan las tablas. No toca cuenta_ctejf.
 */
class ModeloCuadreVentas
{
    public static function mdlTablasListas()
    {
        $pdo = Conexion::conectar();
        $faltan = array();
        $nombres = array("cuadre_ventasjf", "cuadre_ventas_docjf", "cuadre_ventas_medjf");

        foreach ($nombres as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }

        $tieneReserva = false;
        $stmtCol = $pdo->query("SHOW COLUMNS FROM abonosjf LIKE 'id_cuadre'");
        if ($stmtCol && $stmtCol->fetch()) {
            $tieneReserva = true;
        }

        return array(
            "ok" => count($faltan) === 0 && $tieneReserva,
            "faltan" => $faltan,
            "reserva_abono" => $tieneReserva,
        );
    }

    /**
     * Cargos del día de este usuario. Solo lectura. No escribe en cuenta_ctejf.
     * El usuario siempre viene del controlador (sesión), no de la pantalla.
     */
    public static function mdlListarVentasDia($usuario, $fecha, $verTodas = false)
    {
        $sql = "SELECT
                    c.id,
                    c.tipo_doc,
                    c.num_cta,
                    c.cliente,
                    cli.nombre AS cliente_nombre,
                    c.usuario,
                    u.usuario AS usuario_nombre,
                    c.vendedor,
                    c.monto,
                    c.saldo,
                    c.estado
                FROM cuenta_ctejf c
                LEFT JOIN clientesjf cli ON cli.codigo = c.cliente
                LEFT JOIN usuariosjf u ON u.id = CAST(TRIM(c.usuario) AS UNSIGNED)
                WHERE LEFT(c.fecha, 10) = :fecha
                  AND TRIM(c.tipo_doc) IN ('01', '03')
                  AND TRIM(c.vendedor) LIKE '08%'
                  AND UPPER(TRIM(c.estado)) = 'PENDIENTE'
                  AND (c.tip_mov = '+' OR c.tip_mov IS NULL OR c.tip_mov = '')";

        if (!$verTodas) {
            $sql .= " AND TRIM(c.usuario) = :usuario";
        }

        $sql .= " ORDER BY c.usuario ASC, c.cliente ASC, c.tipo_doc ASC, c.num_cta ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        if (!$verTodas) {
            $stmt->bindValue(":usuario", (string) $usuario, PDO::PARAM_STR);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlCargosPorIds($ids)
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (empty($ids)) {
            return array();
        }

        $ph = array();
        foreach ($ids as $i => $id) {
            $ph[] = ":id" . $i;
        }

        $sql = "SELECT
                    c.id,
                    c.tipo_doc,
                    c.num_cta,
                    c.cliente,
                    c.usuario,
                    c.vendedor,
                    c.monto,
                    c.saldo,
                    c.estado,
                    LEFT(c.fecha, 10) AS fecha
                FROM cuenta_ctejf c
                WHERE c.id IN (" . implode(",", $ph) . ")
                  AND TRIM(c.tipo_doc) IN ('01', '03')
                  AND TRIM(c.vendedor) LIKE '08%'
                  AND UPPER(TRIM(c.estado)) = 'PENDIENTE'
                  AND (c.tip_mov = '+' OR c.tip_mov IS NULL OR c.tip_mov = '')";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue(":id" . $i, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlBuscarBorrador($usuarioRegistro, $fecha, $cliente)
    {
        $sql = "SELECT * FROM cuadre_ventasjf
                WHERE usuario_registro = :usuario
                  AND fecha_ventas = :fecha
                  AND cliente = :cliente
                  AND estado = 'BORRADOR'
                ORDER BY id DESC
                LIMIT 1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":usuario", (int) $usuarioRegistro, PDO::PARAM_INT);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        $stmt->bindValue(":cliente", (string) $cliente, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        return $fila ? $fila : null;
    }

    public static function mdlListarBorradoresDia($usuarioRegistro, $fecha, $estado = "BORRADOR")
    {
        $sql = "SELECT
                    q.id,
                    q.cliente,
                    q.estado,
                    q.total_docs,
                    q.total_pagos,
                    q.usuario_ventas,
                    cli.nombre AS cliente_nombre,
                    COUNT(d.id) AS n_docs
                FROM cuadre_ventasjf q
                LEFT JOIN cuadre_ventas_docjf d ON d.id_cuadre = q.id
                LEFT JOIN clientesjf cli ON cli.codigo = q.cliente
                WHERE q.usuario_registro = :usuario
                  AND q.fecha_ventas = :fecha
                  AND q.estado = :estado
                GROUP BY q.id, q.cliente, q.estado, q.total_docs, q.total_pagos, q.usuario_ventas, cli.nombre
                ORDER BY q.id DESC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":usuario", (int) $usuarioRegistro, PDO::PARAM_INT);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        $stmt->bindValue(":estado", (string) $estado, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlDocsDeCuadre($idCuadre)
    {
        $sql = "SELECT id_cuenta, tipo_doc, num_cta, cliente, monto_doc, monto_aplicar
                FROM cuadre_ventas_docjf
                WHERE id_cuadre = :id
                ORDER BY id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":id", (int) $idCuadre, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlLoteAbiertoDeCuenta($idCuenta, $exceptoCuadre = 0)
    {
        $sql = "SELECT q.id, q.estado, q.cliente
                FROM cuadre_ventas_docjf d
                INNER JOIN cuadre_ventasjf q ON q.id = d.id_cuadre
                WHERE d.id_cuenta = :cuenta
                  AND q.estado IN ('BORRADOR', 'REGISTRADO', 'VALIDADO')";
        if ((int) $exceptoCuadre > 0) {
            $sql .= " AND q.id <> :excepto";
        }
        $sql .= " LIMIT 1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":cuenta", (int) $idCuenta, PDO::PARAM_INT);
        if ((int) $exceptoCuadre > 0) {
            $stmt->bindValue(":excepto", (int) $exceptoCuadre, PDO::PARAM_INT);
        }
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        return $fila ? $fila : null;
    }

    public static function mdlGuardarBorrador($cabecera, $docs)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();

        try {
            $idCuadre = isset($cabecera["id"]) ? (int) $cabecera["id"] : 0;
            if ($idCuadre > 0) {
                $sql = "UPDATE cuadre_ventasjf
                        SET usuario_ventas = :usuario_ventas,
                            total_docs = :total_docs,
                            observacion = :observacion,
                            actualizado_en = NOW()
                        WHERE id = :id AND estado = 'BORRADOR'";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(":usuario_ventas", $cabecera["usuario_ventas"], PDO::PARAM_STR);
                $stmt->bindValue(":total_docs", $cabecera["total_docs"]);
                $stmt->bindValue(":observacion", isset($cabecera["observacion"]) ? $cabecera["observacion"] : null, PDO::PARAM_STR);
                $stmt->bindValue(":id", $idCuadre, PDO::PARAM_INT);
                $stmt->execute();

                $stmtDel = $pdo->prepare("DELETE FROM cuadre_ventas_docjf WHERE id_cuadre = :id");
                $stmtDel->bindValue(":id", $idCuadre, PDO::PARAM_INT);
                $stmtDel->execute();
            } else {
                $sql = "INSERT INTO cuadre_ventasjf
                            (fecha_ventas, usuario_ventas, cliente, estado, total_docs, total_pagos,
                             usuario_registro, fecha_registro)
                        VALUES
                            (:fecha_ventas, :usuario_ventas, :cliente, 'BORRADOR', :total_docs, 0,
                             :usuario_registro, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(":fecha_ventas", $cabecera["fecha_ventas"], PDO::PARAM_STR);
                $stmt->bindValue(":usuario_ventas", $cabecera["usuario_ventas"], PDO::PARAM_STR);
                $stmt->bindValue(":cliente", $cabecera["cliente"], PDO::PARAM_STR);
                $stmt->bindValue(":total_docs", $cabecera["total_docs"]);
                $stmt->bindValue(":usuario_registro", (int) $cabecera["usuario_registro"], PDO::PARAM_INT);
                $stmt->execute();
                $idCuadre = (int) $pdo->lastInsertId();
            }

            $sqlDoc = "INSERT INTO cuadre_ventas_docjf
                            (id_cuadre, id_cuenta, tipo_doc, num_cta, cliente, monto_doc, monto_aplicar)
                       VALUES
                            (:id_cuadre, :id_cuenta, :tipo_doc, :num_cta, :cliente, :monto_doc, :monto_aplicar)";
            $stmtDoc = $pdo->prepare($sqlDoc);
            foreach ($docs as $doc) {
                $stmtDoc->bindValue(":id_cuadre", $idCuadre, PDO::PARAM_INT);
                $stmtDoc->bindValue(":id_cuenta", (int) $doc["id_cuenta"], PDO::PARAM_INT);
                $stmtDoc->bindValue(":tipo_doc", $doc["tipo_doc"], PDO::PARAM_STR);
                $stmtDoc->bindValue(":num_cta", $doc["num_cta"], PDO::PARAM_STR);
                $stmtDoc->bindValue(":cliente", $doc["cliente"], PDO::PARAM_STR);
                $stmtDoc->bindValue(":monto_doc", $doc["monto_doc"]);
                $stmtDoc->bindValue(":monto_aplicar", $doc["monto_aplicar"]);
                $stmtDoc->execute();
            }

            $pdo->commit();
            return $idCuadre;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 0;
        }
    }

    public static function mdlBuscarAbonosPorOpe($numOpe)
    {
        $ope = trim((string) $numOpe);
        if ($ope === "") {
            return array();
        }

        $sql = "SELECT id, fecha, descripcion, monto, agencia, num_ope, id_cuadre
                FROM abonosjf
                WHERE TRIM(num_ope) = :ope";
        $params = array(":ope" => $ope);

        if (strlen($ope) >= 4) {
            $like = str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $ope);
            $sql .= " OR TRIM(num_ope) LIKE CONCAT('%', :like) ESCAPE '\\\\'";
            $params[":like"] = $like;
        }

        $sql .= " ORDER BY CASE WHEN TRIM(num_ope) = :opeOrd THEN 0 ELSE 1 END,
                         CHAR_LENGTH(TRIM(num_ope)) ASC,
                         id DESC
                  LIMIT 20";
        $params[":opeOrd"] = $ope;

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlAbonoPorId($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT id, fecha, descripcion, monto, agencia, num_ope, id_cuadre FROM abonosjf WHERE id = :id LIMIT 1");
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        return $fila ? $fila : null;
    }

    public static function mdlIdsCuentasEnLotes($estados)
    {
        if (!is_array($estados) || empty($estados)) {
            return array();
        }
        $ph = array();
        foreach ($estados as $i => $est) {
            $ph[] = ":e" . $i;
        }
        $sql = "SELECT d.id_cuenta
                FROM cuadre_ventas_docjf d
                INNER JOIN cuadre_ventasjf q ON q.id = d.id_cuadre
                WHERE q.estado IN (" . implode(",", $ph) . ")";
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($estados as $i => $est) {
            $stmt->bindValue(":e" . $i, $est, PDO::PARAM_STR);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $ids = array();
        foreach ($filas as $fila) {
            $ids[(int) $fila["id_cuenta"]] = true;
        }
        return $ids;
    }

    public static function mdlRegistrarPagos($idCuadre, $totalPagos, $medios)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();

        try {
            $idCuadre = (int) $idCuadre;
            $stmtDel = $pdo->prepare("DELETE FROM cuadre_ventas_medjf WHERE id_cuadre = :id");
            $stmtDel->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtDel->execute();

            $sqlMed = "INSERT INTO cuadre_ventas_medjf
                            (id_cuadre, tipo_medio, id_abono, num_ope, monto)
                       VALUES
                            (:id_cuadre, :tipo_medio, :id_abono, :num_ope, :monto)";
            $stmtMed = $pdo->prepare($sqlMed);
            $idsAbono = array();
            foreach ($medios as $med) {
                $idAbono = isset($med["id_abono"]) ? (int) $med["id_abono"] : 0;
                $stmtMed->bindValue(":id_cuadre", $idCuadre, PDO::PARAM_INT);
                $stmtMed->bindValue(":tipo_medio", $med["tipo_medio"], PDO::PARAM_STR);
                if ($idAbono > 0) {
                    $stmtMed->bindValue(":id_abono", $idAbono, PDO::PARAM_INT);
                    $idsAbono[] = $idAbono;
                } else {
                    $stmtMed->bindValue(":id_abono", null, PDO::PARAM_NULL);
                }
                $ope = isset($med["num_ope"]) ? $med["num_ope"] : null;
                $stmtMed->bindValue(":num_ope", $ope, $ope === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmtMed->bindValue(":monto", number_format((float) $med["monto"], 2, ".", ""), PDO::PARAM_STR);
                $stmtMed->execute();
            }

            foreach ($idsAbono as $idAbono) {
                $stmtRes = $pdo->prepare(
                    "UPDATE abonosjf
                     SET id_cuadre = :id
                     WHERE id = :abono
                       AND (id_cuadre IS NULL OR id_cuadre = 0 OR id_cuadre = :id2)"
                );
                $stmtRes->bindValue(":id", $idCuadre, PDO::PARAM_INT);
                $stmtRes->bindValue(":id2", $idCuadre, PDO::PARAM_INT);
                $stmtRes->bindValue(":abono", (int) $idAbono, PDO::PARAM_INT);
                $stmtRes->execute();
                if ($stmtRes->rowCount() < 1) {
                    throw new Exception("OP ya reservada");
                }
            }

            $stmtCab = $pdo->prepare(
                "UPDATE cuadre_ventasjf
                 SET estado = 'REGISTRADO',
                     total_pagos = :total_pagos,
                     fecha_registro = NOW(),
                     actualizado_en = NOW()
                 WHERE id = :id AND estado = 'BORRADOR'"
            );
            $stmtCab->bindValue(":total_pagos", number_format((float) $totalPagos, 2, ".", ""), PDO::PARAM_STR);
            $stmtCab->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtCab->execute();
            if ($stmtCab->rowCount() < 1) {
                throw new Exception("El lote ya no está en borrador");
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $e->getMessage();
        }
    }

    public static function mdlListarLotesFecha($fecha, $estados)
    {
        if (!is_array($estados)) {
            $estados = array($estados);
        }
        $permitidos = array(
            "BORRADOR" => true,
            "REGISTRADO" => true,
            "VALIDADO" => true,
            "PROCESADO" => true,
            "ANULADO" => true,
            "RECHAZADO" => true,
        );
        $limpios = array();
        foreach ($estados as $e) {
            $e = strtoupper(trim((string) $e));
            if (isset($permitidos[$e])) {
                $limpios[] = $e;
            }
        }
        if (empty($limpios)) {
            return array();
        }

        $ph = array();
        foreach ($limpios as $i => $e) {
            $ph[] = ":e" . $i;
        }
        $sql = "SELECT
                    q.id,
                    q.fecha_ventas,
                    q.cliente,
                    q.estado,
                    q.total_docs,
                    q.total_pagos,
                    q.usuario_ventas,
                    q.usuario_registro,
                    q.fecha_registro,
                    q.observacion,
                    cli.nombre AS cliente_nombre,
                    ur.usuario AS usuario_registro_nombre,
                    COUNT(d.id) AS n_docs
                FROM cuadre_ventasjf q
                LEFT JOIN cuadre_ventas_docjf d ON d.id_cuadre = q.id
                LEFT JOIN clientesjf cli ON cli.codigo = q.cliente
                LEFT JOIN usuariosjf ur ON ur.id = q.usuario_registro
                WHERE q.fecha_ventas = :fecha
                  AND q.estado IN (" . implode(",", $ph) . ")
                GROUP BY q.id, q.fecha_ventas, q.cliente, q.estado, q.total_docs, q.total_pagos,
                         q.usuario_ventas, q.usuario_registro, q.fecha_registro, q.observacion,
                         cli.nombre, ur.usuario
                ORDER BY FIELD(q.estado, 'REGISTRADO', 'VALIDADO', 'PROCESADO', 'RECHAZADO', 'ANULADO'),
                         q.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        foreach ($limpios as $i => $e) {
            $stmt->bindValue(":e" . $i, $e, PDO::PARAM_STR);
        }
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlMediosDeCuadre($idCuadre)
    {
        $sql = "SELECT tipo_medio, id_abono, num_ope, monto
                FROM cuadre_ventas_medjf
                WHERE id_cuadre = :id
                ORDER BY id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":id", (int) $idCuadre, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlMapaMarcasPorSerie()
    {
        $sql = "SELECT
                    sdm.tipo_documento AS tipo,
                    TRIM(CASE sdm.tipo_documento
                        WHEN '01' THEN t.serie_factura
                        WHEN '03' THEN t.serie_boletas
                        ELSE ''
                    END) AS serie,
                    GROUP_CONCAT(DISTINCT m.marca ORDER BY m.marca SEPARATOR ', ') AS marcas
                FROM serie_documento_marcajf sdm
                INNER JOIN talonariosjf t ON t.id = sdm.id_talonario
                INNER JOIN marcasjf m ON m.id = sdm.id_marca
                WHERE sdm.tipo_documento IN ('01', '03')
                GROUP BY sdm.tipo_documento,
                         TRIM(CASE sdm.tipo_documento
                             WHEN '01' THEN t.serie_factura
                             WHEN '03' THEN t.serie_boletas
                             ELSE ''
                         END)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlFilasExcelFecha($fecha)
    {
        $sql = "SELECT
                    q.id AS id_cuadre,
                    q.fecha_ventas,
                    LEFT(q.fecha_registro, 10) AS fecha_pago,
                    q.estado,
                    IFNULL(ur.nombre, ur.usuario) AS responsable,
                    d.tipo_doc,
                    d.num_cta,
                    d.cliente,
                    d.id_cuenta,
                    d.monto_doc,
                    d.monto_aplicar,
                    LEFT(c.fecha, 10) AS fecha_emision,
                    TRIM(c.vendedor) AS vendedor,
                    mv.descripcion AS vendedor_nombre,
                    cli.nombre AS cliente_nombre,
                    cli.documento AS cliente_documento,
                    ub.departamento,
                    ub.distrito
                FROM cuadre_ventas_docjf d
                INNER JOIN cuadre_ventasjf q ON q.id = d.id_cuadre
                LEFT JOIN cuenta_ctejf c ON c.id = d.id_cuenta
                LEFT JOIN clientesjf cli ON cli.codigo = d.cliente
                LEFT JOIN ubigeo ub ON ub.codigo = cli.ubigeo
                LEFT JOIN usuariosjf ur ON ur.id = q.usuario_registro
                LEFT JOIN maestrajf mv
                    ON mv.codigo = TRIM(c.vendedor)
                   AND mv.tipo_dato = 'TVEND'
                WHERE q.fecha_ventas = :fecha
                  AND q.estado IN ('REGISTRADO', 'VALIDADO', 'PROCESADO')
                ORDER BY q.id ASC, d.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlMediosExcelFecha($fecha)
    {
        $sql = "SELECT
                    m.id_cuadre,
                    m.tipo_medio,
                    m.id_abono,
                    m.num_ope,
                    m.monto
                FROM cuadre_ventas_medjf m
                INNER JOIN cuadre_ventasjf q ON q.id = m.id_cuadre
                WHERE q.fecha_ventas = :fecha
                  AND q.estado IN ('REGISTRADO', 'VALIDADO', 'PROCESADO')
                ORDER BY m.id_cuadre ASC, m.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha", (string) $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        return is_array($filas) ? $filas : array();
    }

    public static function mdlCortesPagosDocs($docs, $medios)
    {
        return self::cvRepartirPagosEnDocs($docs, $medios);
    }

    public static function mdlGrupoMarcaDeDocumento($numCta)
    {
        $n = strtoupper(trim((string) $numCta));
        if (strpos($n, "FR") === 0 || strpos($n, "BR") === 0) {
            return "Rosaflor";
        }
        return "Jackyform";
    }

    public static function mdlMarcaDeDocumento($tipo, $numCta, $mapa)
    {
        $numCta = trim((string) $numCta);
        $tipo = trim((string) $tipo);
        $best = "";
        $bestLen = 0;
        foreach ($mapa as $row) {
            if (trim((string) $row["tipo"]) !== $tipo) {
                continue;
            }
            $serie = trim((string) $row["serie"]);
            $len = strlen($serie);
            if ($len < 1 || $len < $bestLen) {
                continue;
            }
            if (substr($numCta, 0, $len) === $serie) {
                $best = isset($row["marcas"]) ? trim((string) $row["marcas"]) : "";
                $bestLen = $len;
            }
        }
        return $best;
    }

    public static function mdlCuadrePorId($id)
    {
        $sql = "SELECT
                    q.*,
                    cli.nombre AS cliente_nombre,
                    ur.usuario AS usuario_registro_nombre
                FROM cuadre_ventasjf q
                LEFT JOIN clientesjf cli ON cli.codigo = q.cliente
                LEFT JOIN usuariosjf ur ON ur.id = q.usuario_registro
                WHERE q.id = :id
                LIMIT 1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        return $fila ? $fila : null;
    }

    public static function mdlValidarCuadre($idCuadre, $usuarioValidacion)
    {
        $sql = "UPDATE cuadre_ventasjf
                SET estado = 'VALIDADO',
                    usuario_validacion = :usuario,
                    fecha_validacion = NOW(),
                    actualizado_en = NOW()
                WHERE id = :id AND estado = 'REGISTRADO'";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":usuario", (int) $usuarioValidacion, PDO::PARAM_INT);
        $stmt->bindValue(":id", (int) $idCuadre, PDO::PARAM_INT);
        $stmt->execute();
        $ok = $stmt->rowCount() > 0;
        $stmt = null;

        return $ok;
    }

    public static function mdlRechazarCuadre($idCuadre, $usuarioValidacion, $motivo)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();

        try {
            $idCuadre = (int) $idCuadre;
            $stmt = $pdo->prepare(
                "UPDATE cuadre_ventasjf
                 SET estado = 'RECHAZADO',
                     usuario_validacion = :usuario,
                     fecha_validacion = NOW(),
                     observacion = :motivo,
                     actualizado_en = NOW()
                 WHERE id = :id AND estado = 'REGISTRADO'"
            );
            $stmt->bindValue(":usuario", (int) $usuarioValidacion, PDO::PARAM_INT);
            $stmt->bindValue(":motivo", $motivo, PDO::PARAM_STR);
            $stmt->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() < 1) {
                throw new Exception("El lote ya no está pendiente de validar");
            }

            $stmtLib = $pdo->prepare(
                "UPDATE abonosjf
                 SET id_cuadre = NULL
                 WHERE id_cuadre = :id"
            );
            $stmtLib->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtLib->execute();

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $e->getMessage();
        }
    }

    public static function mdlAnularCuadre($idCuadre, $observacion)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();

        try {
            $idCuadre = (int) $idCuadre;
            $stmt = $pdo->prepare(
                "UPDATE cuadre_ventasjf
                 SET estado = 'ANULADO',
                     observacion = :motivo,
                     actualizado_en = NOW()
                 WHERE id = :id AND estado = 'REGISTRADO'"
            );
            $stmt->bindValue(":motivo", $observacion, PDO::PARAM_STR);
            $stmt->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() < 1) {
                throw new Exception("El lote ya no está pendiente de validar");
            }

            $stmtLib = $pdo->prepare(
                "UPDATE abonosjf
                 SET id_cuadre = NULL
                 WHERE id_cuadre = :id"
            );
            $stmtLib->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtLib->execute();

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $e->getMessage();
        }
    }

    /**
     * Paso 7: pasa un lote VALIDADO a cte.
     * INSERT tip_mov='-' (cod_pago del medio, notas OP- si hay), baja saldo del cargo,
     * consume abonosjf y deja PROCESADO. Idempotente si ya está procesado.
     *
     * $ctx: usureg, pcreg, usuario_proceso
     */
    public static function mdlProcesarACte($idCuadre, $ctx)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();

        try {
            $idCuadre = (int) $idCuadre;
            $stmtLote = $pdo->prepare("SELECT * FROM cuadre_ventasjf WHERE id = :id FOR UPDATE");
            $stmtLote->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtLote->execute();
            $lote = $stmtLote->fetch(PDO::FETCH_ASSOC);
            if (!$lote) {
                throw new Exception("No se encontró el cuadre.");
            }

            $estado = strtoupper(trim((string) $lote["estado"]));
            if ($estado === "PROCESADO") {
                $pdo->commit();
                return array("ok" => true, "ya" => true);
            }
            if ($estado !== "VALIDADO") {
                throw new Exception("Solo se procesan cuadres ya confirmados.");
            }

            $stmtDocs = $pdo->prepare(
                "SELECT id_cuenta, tipo_doc, num_cta, cliente, monto_doc, monto_aplicar
                 FROM cuadre_ventas_docjf
                 WHERE id_cuadre = :id
                 ORDER BY id ASC"
            );
            $stmtDocs->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtDocs->execute();
            $docs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
            if (empty($docs)) {
                throw new Exception("El cuadre no tiene documentos.");
            }

            $stmtMed = $pdo->prepare(
                "SELECT tipo_medio, id_abono, num_ope, monto
                 FROM cuadre_ventas_medjf
                 WHERE id_cuadre = :id
                 ORDER BY id ASC"
            );
            $stmtMed->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtMed->execute();
            $medios = $stmtMed->fetchAll(PDO::FETCH_ASSOC);
            if (empty($medios)) {
                throw new Exception("El cuadre no tiene pagos.");
            }

            $ids = array();
            foreach ($docs as $d) {
                $ids[] = (int) $d["id_cuenta"];
            }
            $ph = array();
            foreach ($ids as $i => $id) {
                $ph[] = ":c" . $i;
            }
            $sqlCar = "SELECT
                            id, tipo_doc, num_cta, cliente, usuario, vendedor,
                            monto, saldo, estado,
                            LEFT(fecha, 10) AS fecha,
                            LEFT(fecha_ven, 10) AS fecha_ven
                       FROM cuenta_ctejf
                       WHERE id IN (" . implode(",", $ph) . ")
                       FOR UPDATE";
            $stmtCar = $pdo->prepare($sqlCar);
            foreach ($ids as $i => $id) {
                $stmtCar->bindValue(":c" . $i, $id, PDO::PARAM_INT);
            }
            $stmtCar->execute();
            $cargos = array();
            foreach ($stmtCar->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $cargos[(int) $c["id"]] = $c;
            }

            foreach ($docs as $d) {
                $idCta = (int) $d["id_cuenta"];
                if (!isset($cargos[$idCta])) {
                    throw new Exception("No se encontró el documento " . $d["num_cta"] . " en cuentas.");
                }
                $car = $cargos[$idCta];
                if (strtoupper(trim((string) $car["estado"])) !== "PENDIENTE") {
                    throw new Exception("El documento " . $d["num_cta"] . " ya no está pendiente.");
                }
                $aplicar = round((float) $d["monto_aplicar"], 2);
                $saldo = round((float) $car["saldo"], 2);
                if ($aplicar <= 0) {
                    throw new Exception("Hay un monto a aplicar inválido en " . $d["num_cta"] . ".");
                }
                if ($aplicar - $saldo > 0.009) {
                    throw new Exception(
                        "El saldo de " . $d["num_cta"] . " no alcanza. Saldo: "
                        . number_format($saldo, 2, ".", ",") . "."
                    );
                }
            }

            $cortes = self::cvRepartirPagosEnDocs($docs, $medios);
            $fechaLote = substr((string) $lote["fecha_ventas"], 0, 10);
            $usureg = isset($ctx["usureg"]) ? $ctx["usureg"] : "";
            $pcreg = isset($ctx["pcreg"]) ? $ctx["pcreg"] : "";
            $usuarioProc = isset($ctx["usuario_proceso"]) ? (int) $ctx["usuario_proceso"] : 0;

            $sqlIns = "INSERT INTO cuenta_ctejf (
                            tipo_doc, num_cta, cliente, vendedor, fecha, fecha_ven,
                            monto, notas, estado, cod_pago, doc_origen,
                            renovacion, protesta, usuario, saldo, tip_mov,
                            usureg, pcreg, fecha_ori
                       ) VALUES (
                            :tipo_doc, :num_cta, :cliente, :vendedor, :fecha, :fecha_ven,
                            :monto, :notas, 'PENDIENTE', :cod_pago, :doc_origen,
                            0, 0, :usuario, 0, '-',
                            :usureg, :pcreg, :fecha_ori
                       )";
            $stmtIns = $pdo->prepare($sqlIns);
            $docsUlt = array();

            foreach ($cortes as $corte) {
                $doc = $corte["doc"];
                $idCta = (int) $doc["id_cuenta"];
                $car = $cargos[$idCta];
                $cod = $corte["cod_pago"];
                $ope = $corte["num_ope"];
                $notas = $ope !== "" ? ("OP-" . $ope) : ("CUADRE-" . $idCuadre);
                $fechaVen = isset($car["fecha_ven"]) && $car["fecha_ven"] ? $car["fecha_ven"] : $fechaLote;
                $fechaMov = $fechaLote !== "" ? $fechaLote : date("Y-m-d");

                $stmtIns->bindValue(":tipo_doc", trim((string) $car["tipo_doc"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":num_cta", trim((string) $car["num_cta"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":cliente", trim((string) $car["cliente"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":vendedor", trim((string) $car["vendedor"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":fecha", $fechaMov, PDO::PARAM_STR);
                $stmtIns->bindValue(":fecha_ven", $fechaVen, PDO::PARAM_STR);
                $stmtIns->bindValue(":monto", number_format((float) $corte["monto"], 2, ".", ""), PDO::PARAM_STR);
                $stmtIns->bindValue(":notas", $notas, PDO::PARAM_STR);
                $stmtIns->bindValue(":cod_pago", $cod, PDO::PARAM_STR);
                $stmtIns->bindValue(":doc_origen", trim((string) $car["num_cta"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":usuario", trim((string) $car["usuario"]), PDO::PARAM_STR);
                $stmtIns->bindValue(":usureg", $usureg, PDO::PARAM_STR);
                $stmtIns->bindValue(":pcreg", $pcreg, PDO::PARAM_STR);
                $stmtIns->bindValue(":fecha_ori", $fechaMov, PDO::PARAM_STR);
                $stmtIns->execute();

                $claveUlt = trim((string) $car["tipo_doc"]) . "|" . trim((string) $car["num_cta"]);
                $docsUlt[$claveUlt] = array(
                    "tipo_doc" => trim((string) $car["tipo_doc"]),
                    "num_cta" => trim((string) $car["num_cta"]),
                );
            }

            $stmtSal = $pdo->prepare(
                "UPDATE cuenta_ctejf
                 SET saldo = :saldo,
                     estado = :estado
                 WHERE id = :id
                   AND (tip_mov = '+' OR tip_mov IS NULL OR tip_mov = '')"
            );
            foreach ($docs as $d) {
                $idCta = (int) $d["id_cuenta"];
                $car = $cargos[$idCta];
                $nuevo = round((float) $car["saldo"] - (float) $d["monto_aplicar"], 2);
                if ($nuevo < 0.01) {
                    $nuevo = 0.0;
                    $est = "CANCELADO";
                } else {
                    $est = "PENDIENTE";
                }
                $stmtSal->bindValue(":saldo", number_format($nuevo, 2, ".", ""), PDO::PARAM_STR);
                $stmtSal->bindValue(":estado", $est, PDO::PARAM_STR);
                $stmtSal->bindValue(":id", $idCta, PDO::PARAM_INT);
                $stmtSal->execute();
                if ($stmtSal->rowCount() < 1) {
                    throw new Exception("No se pudo actualizar el saldo de " . $d["num_cta"] . ".");
                }
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $idsAbonos = self::cvIdsAbonosDelLote($pdo, $idCuadre, $medios);
            $stmtAbMonto = $pdo->prepare(
                "SELECT id, monto, num_ope FROM abonosjf WHERE id = :id_abono FOR UPDATE"
            );
            foreach ($medios as $m) {
                $idA = isset($m["id_abono"]) ? (int) $m["id_abono"] : 0;
                if ($idA < 1) {
                    continue;
                }
                $stmtAbMonto->bindValue(":id_abono", $idA, PDO::PARAM_INT);
                $stmtAbMonto->execute();
                $ab = $stmtAbMonto->fetch(PDO::FETCH_ASSOC);
                if (method_exists($stmtAbMonto, "closeCursor")) {
                    $stmtAbMonto->closeCursor();
                }
                if (!$ab) {
                    throw new Exception("El abono de la OP " . trim((string) $m["num_ope"]) . " ya no existe.");
                }
                $disp = round((float) $ab["monto"], 2);
                $usado = round((float) $m["monto"], 2);
                if (abs($disp - $usado) > 0.009) {
                    throw new Exception(
                        "La OP " . $ab["num_ope"] . " es de "
                        . number_format($disp, 2, ".", ",")
                        . " y no cuadra con las boletas ("
                        . number_format($usado, 2, ".", ",")
                        . "). Anula el cuadre y armá de nuevo."
                    );
                }
            }
            $stmtDelAb = $pdo->prepare("DELETE FROM abonosjf WHERE id = :id_abono");
            foreach ($idsAbonos as $idA) {
                $stmtDelAb->bindValue(":id_abono", (int) $idA, PDO::PARAM_INT);
                $stmtDelAb->execute();
            }

            $stmtUlt = $pdo->prepare(
                "UPDATE cuenta_ctejf c1
                 LEFT JOIN (
                    SELECT num_cta, tipo_doc, MAX(fecha) AS ult_pago
                    FROM cuenta_ctejf
                    WHERE num_cta = :num_cta2
                      AND tipo_doc = :tipo_doc2
                      AND tip_mov = '-'
                 ) c2
                   ON c1.num_cta = c2.num_cta AND c1.tipo_doc = c2.tipo_doc
                 SET c1.ult_pago = c2.ult_pago
                 WHERE c1.num_cta = :num_cta
                   AND c1.tipo_doc = :tipo_doc
                   AND c1.tip_mov = '+'"
            );
            foreach ($docsUlt as $u) {
                $stmtUlt->bindValue(":num_cta", $u["num_cta"], PDO::PARAM_STR);
                $stmtUlt->bindValue(":tipo_doc", $u["tipo_doc"], PDO::PARAM_STR);
                $stmtUlt->bindValue(":num_cta2", $u["num_cta"], PDO::PARAM_STR);
                $stmtUlt->bindValue(":tipo_doc2", $u["tipo_doc"], PDO::PARAM_STR);
                $stmtUlt->execute();
            }

            $stmtFin = $pdo->prepare(
                "UPDATE cuadre_ventasjf
                 SET estado = 'PROCESADO',
                     usuario_proceso = :usuario,
                     fecha_proceso = NOW(),
                     actualizado_en = NOW()
                 WHERE id = :id AND estado = 'VALIDADO'"
            );
            $stmtFin->bindValue(":usuario", $usuarioProc, PDO::PARAM_INT);
            $stmtFin->bindValue(":id", $idCuadre, PDO::PARAM_INT);
            $stmtFin->execute();
            if ($stmtFin->rowCount() < 1) {
                throw new Exception("El lote ya no está validado.");
            }

            $pdo->commit();
            return array("ok" => true, "ya" => false);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return array("ok" => false, "msg" => $e->getMessage());
        }
    }

    private static function cvRepartirPagosEnDocs($docs, $medios)
    {
        $items = array();
        foreach ($docs as $i => $d) {
            $cents = (int) round(((float) $d["monto_aplicar"]) * 100);
            $items[] = array(
                "idx" => $i,
                "doc" => $d,
                "cents" => $cents,
                "restante" => $cents,
            );
        }
        $pays = array();
        foreach ($medios as $m) {
            $cents = (int) round(((float) $m["monto"]) * 100);
            $ope = isset($m["num_ope"]) ? trim((string) $m["num_ope"]) : "";
            $idAbono = isset($m["id_abono"]) ? (int) $m["id_abono"] : 0;
            $pays[] = array(
                "cod_pago" => self::cvCodPago(isset($m["tipo_medio"]) ? $m["tipo_medio"] : ""),
                "num_ope" => $ope,
                "con_op" => ($ope !== "" || $idAbono > 0) ? 1 : 0,
                "cents" => $cents,
                "restante" => $cents,
            );
        }
        usort($pays, array("ModeloCuadreVentas", "cvOrdenarPagos"));

        $cortes = array();
        $usados = array();
        foreach ($pays as $p => $pay) {
            if ($pays[$p]["restante"] <= 0) {
                continue;
            }
            $disponibles = array();
            foreach ($items as $i => $it) {
                if (!isset($usados[$it["idx"]]) && $items[$i]["restante"] === $items[$i]["cents"]) {
                    $disponibles[] = $i;
                }
            }
            $pool = array();
            foreach ($disponibles as $di) {
                $pool[] = $items[$di];
            }
            $sub = self::cvHallarSubconjunto($pool, $pays[$p]["restante"]);
            if ($sub === null) {
                continue;
            }
            foreach ($sub as $si) {
                $it = $pool[$si];
                $idx = $it["idx"];
                $usados[$idx] = true;
                $items[$idx]["restante"] = 0;
                $cortes[] = array(
                    "doc" => $it["doc"],
                    "cod_pago" => $pay["cod_pago"],
                    "num_ope" => $pay["num_ope"],
                    "monto" => round($it["cents"] / 100, 2),
                );
            }
            $pays[$p]["restante"] = 0;
        }

        foreach ($items as $i => $it) {
            if ($items[$i]["restante"] <= 0) {
                continue;
            }
            foreach ($pays as $p => $pay) {
                if ($items[$i]["restante"] <= 0) {
                    break;
                }
                if ($pays[$p]["restante"] <= 0) {
                    continue;
                }
                $use = $items[$i]["restante"] < $pays[$p]["restante"]
                    ? $items[$i]["restante"]
                    : $pays[$p]["restante"];
                $cortes[] = array(
                    "doc" => $it["doc"],
                    "cod_pago" => $pay["cod_pago"],
                    "num_ope" => $pay["num_ope"],
                    "monto" => round($use / 100, 2),
                );
                $items[$i]["restante"] -= $use;
                $pays[$p]["restante"] -= $use;
            }
            if ($items[$i]["restante"] > 0) {
                throw new Exception("Los pagos no cubren el documento " . $it["doc"]["num_cta"] . ".");
            }
        }

        return $cortes;
    }

    public static function cvOrdenarPagos($a, $b)
    {
        if ($a["con_op"] !== $b["con_op"]) {
            return $b["con_op"] - $a["con_op"];
        }
        if ($a["cents"] === $b["cents"]) {
            return 0;
        }
        return ($a["cents"] > $b["cents"]) ? -1 : 1;
    }

    private static function cvHallarSubconjunto($items, $targetCents)
    {
        $targetCents = (int) $targetCents;
        if ($targetCents < 1 || empty($items)) {
            return null;
        }
        $best = array(0 => array());
        foreach ($items as $k => $it) {
            $c = (int) $it["cents"];
            $snapshot = array_keys($best);
            foreach ($snapshot as $prev) {
                $prev = (int) $prev;
                $next = $prev + $c;
                if ($next > $targetCents) {
                    continue;
                }
                $cand = $best[$prev];
                $cand[] = $k;
                if (!isset($best[$next]) || count($cand) < count($best[$next])) {
                    $best[$next] = $cand;
                }
            }
        }
        return isset($best[$targetCents]) ? $best[$targetCents] : null;
    }

    private static function cvOpeIgual($a, $b)
    {
        $a = trim((string) $a);
        $b = trim((string) $b);
        if ($a === "" || $b === "") {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        if (strlen($a) >= 4 && strlen($a) < strlen($b) && substr($b, -strlen($a)) === $a) {
            return true;
        }
        if (strlen($b) >= 4 && strlen($b) < strlen($a) && substr($a, -strlen($b)) === $b) {
            return true;
        }
        return false;
    }

    private static function cvIdsAbonosDelLote($pdo, $idCuadre, $medios)
    {
        $ids = array();
        $idCuadre = (int) $idCuadre;

        foreach ($medios as $m) {
            $idA = isset($m["id_abono"]) ? (int) $m["id_abono"] : 0;
            if ($idA > 0) {
                $ids[$idA] = true;
            }
        }

        $stmtRes = $pdo->prepare(
            "SELECT id, num_ope FROM abonosjf WHERE id_cuadre = :id_cuadre FOR UPDATE"
        );
        $stmtRes->bindValue(":id_cuadre", $idCuadre, PDO::PARAM_INT);
        $stmtRes->execute();
        $reservados = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
        if (method_exists($stmtRes, "closeCursor")) {
            $stmtRes->closeCursor();
        }
        foreach ($reservados as $ab) {
            $ids[(int) $ab["id"]] = true;
        }

        foreach ($medios as $m) {
            $ope = isset($m["num_ope"]) ? trim((string) $m["num_ope"]) : "";
            if ($ope === "") {
                continue;
            }
            $idEnc = 0;
            foreach ($reservados as $ab) {
                if (self::cvOpeIgual($ope, $ab["num_ope"])) {
                    $idEnc = (int) $ab["id"];
                    break;
                }
            }
            if ($idEnc < 1) {
                $stmtOpe = $pdo->prepare(
                    "SELECT id, id_cuadre, num_ope
                     FROM abonosjf
                     WHERE TRIM(num_ope) = :ope
                        OR (CHAR_LENGTH(:ope_len) >= 4 AND TRIM(num_ope) LIKE CONCAT('%', :ope_like))
                     ORDER BY CASE WHEN TRIM(num_ope) = :ope_eq THEN 0 ELSE 1 END,
                              CASE WHEN id_cuadre = :id_cuadre THEN 0 ELSE 1 END,
                              id DESC
                     LIMIT 8
                     FOR UPDATE"
                );
                $stmtOpe->bindValue(":ope", $ope, PDO::PARAM_STR);
                $stmtOpe->bindValue(":ope_len", $ope, PDO::PARAM_STR);
                $stmtOpe->bindValue(":ope_like", $ope, PDO::PARAM_STR);
                $stmtOpe->bindValue(":ope_eq", $ope, PDO::PARAM_STR);
                $stmtOpe->bindValue(":id_cuadre", $idCuadre, PDO::PARAM_INT);
                $stmtOpe->execute();
                $cands = $stmtOpe->fetchAll(PDO::FETCH_ASSOC);
                if (method_exists($stmtOpe, "closeCursor")) {
                    $stmtOpe->closeCursor();
                }
                foreach ($cands as $ab) {
                    $idCand = (int) $ab["id"];
                    if (isset($ids[$idCand])) {
                        continue;
                    }
                    $res = isset($ab["id_cuadre"]) ? (int) $ab["id_cuadre"] : 0;
                    if ($res > 0 && $res !== $idCuadre) {
                        continue;
                    }
                    if (!self::cvOpeIgual($ope, $ab["num_ope"]) && trim((string) $ab["num_ope"]) !== $ope) {
                        continue;
                    }
                    $idEnc = $idCand;
                    break;
                }
            }
            if ($idEnc > 0) {
                $ids[$idEnc] = true;
            }
        }

        return array_keys($ids);
    }

    private static function cvCodPago($tipo)
    {
        $tipo = strtoupper(trim((string) $tipo));
        $legado = array(
            "EFECTIVO" => "80",
            "YAPE" => "15",
            "ABONO_OP" => "05",
            "DEPOSITO" => "05",
            "TARJETA" => "17",
            "LINK" => "16",
            "CULQI" => "14",
            "CULQUI" => "14",
        );
        if (isset($legado[$tipo])) {
            return $legado[$tipo];
        }
        $ok = array("80" => true, "15" => true, "05" => true, "17" => true, "16" => true, "14" => true);
        return isset($ok[$tipo]) ? $tipo : "80";
    }
}
