<?php

/**
 * Letras y cancelados — consultas v2 (Fase 2).
 */
class ReportesGeneralesV2LetrasLib
{
    private static function baseSelect()
    {
        return "SELECT
            cc.tipo_doc,
            cc.num_cta,
            cc.fecha,
            cc.fecha_ven,
            cc.doc_origen,
            cc.cliente,
            c.nombre,
            cc.vendedor,
            cc.saldo,
            cc.monto,
            cc.num_unico,
            cc.banco,
            cc.cod_pago,
            cc.estado_doc,
            cc.estado,
            c.ubigeo,
            u.nombre AS nom_ubigeo
        FROM cuenta_ctejf cc
        LEFT JOIN clientesjf c ON cc.cliente = c.codigo
        LEFT JOIN ubigeo u ON c.ubigeo = u.codigo";
    }

    private static function appendFiltrosComunes($sql, $f, &$params)
    {
        if ($f['cli'] !== '') {
            $sql .= ' AND cc.cliente = :cli';
            $params[':cli'] = $f['cli'];
        }
        if ($f['vend'] !== '') {
            $sql .= ' AND cc.vendedor = :vend';
            $params[':vend'] = $f['vend'];
        }
        if ($f['banco'] !== '') {
            $sql .= ' AND cc.banco = :banco';
            $params[':banco'] = $f['banco'];
        }
        if ($f['inicio'] !== '' && $f['fin'] !== '') {
            $sql .= ' AND cc.fecha BETWEEN :inicio AND :fin';
            $params[':inicio'] = $f['inicio'];
            $params[':fin'] = $f['fin'];
        } elseif ($f['inicio'] !== '') {
            $sql .= ' AND cc.fecha >= :inicio';
            $params[':inicio'] = $f['inicio'];
        } elseif ($f['fin'] !== '') {
            $sql .= ' AND cc.fecha <= :fin';
            $params[':fin'] = $f['fin'];
        }
        return $sql;
    }

    private static function ejecutar($pdo, $sql, $params)
    {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : array();
    }

    public static function consultarLetrasPorImprimir($pdo, $f)
    {
        $params = array();
        $sql = self::baseSelect() . "
        WHERE cc.tip_mov = '+'
          AND UPPER(TRIM(cc.estado)) = 'PENDIENTE'
          AND cc.tipo_doc = '85'
          AND (cc.num_unico IS NULL OR TRIM(cc.num_unico) = '')
          AND cc.estado_doc = '01'
          AND cc.banco = '02'";
        $sql = self::appendFiltrosComunes($sql, $f, $params);
        $sql .= ' ORDER BY cc.vendedor, cc.cliente, cc.fecha_ven, cc.num_cta';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function consultarLetrasEnCartera($pdo, $f)
    {
        $params = array();
        $sql = self::baseSelect() . "
        WHERE cc.tip_mov = '+'
          AND UPPER(TRIM(cc.estado)) = 'PENDIENTE'
          AND cc.tipo_doc = '85'
          AND cc.saldo > 0
          AND UPPER(TRIM(IFNULL(cc.num_unico, ''))) LIKE '%CARTERA%'";
        $sql = self::appendFiltrosComunes($sql, $f, $params);
        $sql .= ' ORDER BY cc.vendedor, cc.cliente, cc.fecha_ven, cc.num_cta';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function consultarLetrasPorAceptar($pdo, $f)
    {
        $params = array();
        $sql = self::baseSelect() . "
        WHERE cc.tipo_doc = '85'
          AND UPPER(TRIM(cc.estado)) = 'PENDIENTE'
          AND cc.tip_mov = '+'
          AND (cc.banco <> '02' OR cc.banco IS NULL OR TRIM(cc.banco) = '')
          AND (cc.num_unico IS NULL OR TRIM(cc.num_unico) = '')
          AND cc.protesta <> '1'";
        $sql = self::appendFiltrosComunes($sql, $f, $params);
        $sql .= ' ORDER BY cc.vendedor, cc.cliente, cc.doc_origen, cc.fecha_ven, cc.num_cta';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function consultarDocCancelados($pdo, $f)
    {
        $params = array();
        $sql = self::baseSelect() . "
        WHERE cc.tip_mov = '+'
          AND UPPER(TRIM(cc.estado)) = 'CANCELADO'";
        $sql = self::appendFiltrosComunes($sql, $f, $params);
        $sql .= ' ORDER BY cc.tipo_doc, cc.num_cta, cc.fecha DESC';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function totalSaldo($rows)
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $val = isset($row['saldo']) ? $row['saldo'] : 0;
            $sum += (float) str_replace(',', '', (string) $val);
        }
        return $sum;
    }
}
