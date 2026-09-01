<?php

/**
 * Reportes Fase 3 — agrupaciones, movimientos y resumen saldos.
 */
class ReportesGeneralesV2Fase3Lib
{
    public static function labelBanco($code)
    {
        $c = trim((string) $code);
        if ($c === '') {
            return '(sin banco)';
        }
        if ($c === '02') {
            return 'BCP';
        }
        return $c;
    }

    public static function labelEstadoDoc($code)
    {
        $c = trim((string) $code);
        if ($c === '') {
            return '(sin estado)';
        }
        if ($c === '01') {
            return 'COBRANZA';
        }
        return $c;
    }

    public static function parseNum($valor)
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }
        return (float) str_replace(',', '', (string) $valor);
    }

    public static function fmtNum($n)
    {
        return number_format($n, 2, '.', ',');
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

    public static function consultarDocumentosPendientes($pdo, $f)
    {
        $params = array();
        $sql = "SELECT
            cc.tipo_doc,
            cc.num_cta,
            cc.banco,
            cc.estado_doc,
            cc.fecha,
            cc.fecha_ven,
            cc.cliente,
            c.nombre,
            cc.vendedor,
            cc.saldo
        FROM cuenta_ctejf cc
        LEFT JOIN clientesjf c ON cc.cliente = c.codigo
        WHERE cc.tip_mov = '+'
          AND UPPER(TRIM(cc.estado)) = 'PENDIENTE'
          AND cc.saldo > 0";
        if ($f['tip_doc'] !== '') {
            $sql .= ' AND cc.tipo_doc = :tip_doc';
            $params[':tip_doc'] = $f['tip_doc'];
        }
        if ($f['banco'] !== '') {
            $sql .= ' AND cc.banco = :banco';
            $params[':banco'] = $f['banco'];
        }
        $sql .= ' ORDER BY cc.banco, cc.estado_doc, cc.tipo_doc, cc.num_cta';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function construirInformeBancoEstado($rows)
    {
        return self::construirInformeAgrupado($rows, array('banco', 'estado_doc'));
    }

    public static function construirInformeEstadoBanco($rows)
    {
        return self::construirInformeAgrupado($rows, array('estado_doc', 'banco'));
    }

    private static function construirInformeAgrupado($rows, $keys)
    {
        $out = array();
        $totalGral = 0.0;
        $groups = array();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $k1 = isset($row[$keys[0]]) ? (string) $row[$keys[0]] : '';
            $k2 = isset($row[$keys[1]]) ? (string) $row[$keys[1]] : '';
            if (!isset($groups[$k1])) {
                $groups[$k1] = array();
            }
            if (!isset($groups[$k1][$k2])) {
                $groups[$k1][$k2] = array();
            }
            $groups[$k1][$k2][] = $row;
        }

        ksort($groups);
        foreach ($groups as $g1Key => $g2Map) {
            $label1 = $keys[0] === 'banco'
                ? self::labelBanco($g1Key)
                : self::labelEstadoDoc($g1Key);
            $out[] = self::filaGrupo('group1', $keys[0] === 'banco' ? 'Banco' : 'Estado', $label1);
            $sub1 = 0.0;
            ksort($g2Map);
            foreach ($g2Map as $g2Key => $items) {
                $label2 = $keys[1] === 'banco'
                    ? self::labelBanco($g2Key)
                    : self::labelEstadoDoc($g2Key);
                $out[] = self::filaGrupo('group2', $keys[1] === 'banco' ? 'Banco' : 'Estado', $label2);
                $sub2 = 0.0;
                foreach ($items as $item) {
                    $saldo = self::parseNum(isset($item['saldo']) ? $item['saldo'] : 0);
                    $sub2 += $saldo;
                    $sub1 += $saldo;
                    $totalGral += $saldo;
                    $out[] = self::filaDetalle($item);
                }
                $out[] = self::filaSubtotal('Subtotal ' . $label2, $sub2);
            }
            $out[] = self::filaSubtotal('Subtotal ' . $label1, $sub1);
        }

        if ($totalGral > 0) {
            $out[] = self::filaSubtotal('Total general', $totalGral);
        }

        return array(
            'rows' => $out,
            'total_saldo' => $totalGral,
        );
    }

    private static function filaGrupo($tipo, $prefijo, $label)
    {
        return array(
            '_rowType' => $tipo,
            'concepto' => $prefijo . ': ' . $label,
            'tipo_doc' => '',
            'num_cta' => '',
            'fecha' => '',
            'fecha_ven' => '',
            'cliente' => '',
            'nombre' => '',
            'vendedor' => '',
            'banco' => '',
            'estado_doc' => '',
            'saldo' => '',
        );
    }

    private static function filaSubtotal($label, $monto)
    {
        return array(
            '_rowType' => 'subtotal',
            'concepto' => $label,
            'tipo_doc' => '',
            'num_cta' => '',
            'fecha' => '',
            'fecha_ven' => '',
            'cliente' => '',
            'nombre' => '',
            'vendedor' => '',
            'banco' => '',
            'estado_doc' => '',
            'saldo' => self::fmtNum($monto),
        );
    }

    private static function filaDetalle($row)
    {
        return array(
            '_rowType' => 'detail',
            'concepto' => '',
            'tipo_doc' => isset($row['tipo_doc']) ? $row['tipo_doc'] : '',
            'num_cta' => isset($row['num_cta']) ? $row['num_cta'] : '',
            'fecha' => isset($row['fecha']) ? $row['fecha'] : '',
            'fecha_ven' => isset($row['fecha_ven']) ? $row['fecha_ven'] : '',
            'cliente' => isset($row['cliente']) ? $row['cliente'] : '',
            'nombre' => isset($row['nombre']) ? $row['nombre'] : '',
            'vendedor' => isset($row['vendedor']) ? $row['vendedor'] : '',
            'banco' => self::labelBanco(isset($row['banco']) ? $row['banco'] : ''),
            'estado_doc' => self::labelEstadoDoc(isset($row['estado_doc']) ? $row['estado_doc'] : ''),
            'saldo' => isset($row['saldo']) ? $row['saldo'] : '',
        );
    }

    public static function consultarMovimientos($pdo, $f)
    {
        $params = array(
            ':inicio' => $f['inicio'],
            ':fin' => $f['fin'],
        );
        $sql = "SELECT
            cc.tipo_doc,
            cc.num_cta,
            cc.fecha,
            cc.tip_mov,
            cc.monto,
            cc.cliente,
            c.nombre,
            cc.vendedor,
            cc.cod_pago,
            cc.notas,
            cc.doc_origen
        FROM cuenta_ctejf cc
        LEFT JOIN clientesjf c ON cc.cliente = c.codigo
        WHERE cc.tip_mov IN ('+', '-')
          AND cc.fecha BETWEEN :inicio AND :fin";
        if ($f['cli'] !== '') {
            $sql .= ' AND cc.cliente = :cli';
            $params[':cli'] = $f['cli'];
        }
        if ($f['vend'] !== '') {
            $sql .= ' AND cc.vendedor = :vend';
            $params[':vend'] = $f['vend'];
        }
        $sql .= ' ORDER BY cc.fecha, cc.tip_mov, cc.tipo_doc, cc.num_cta';
        return self::ejecutar($pdo, $sql, $params);
    }

    public static function consultarResumenSaldosFecha($pdo, $f)
    {
        $inicio = $f['inicio'];
        $fin = $f['fin'];
        $params = array();
        $sql = "SELECT
            cc.cliente,
            c.nombre,
            ROUND(SUM(cc.monto - IFNULL(c1.monto, 0)), 2) AS saldo_fecha
        FROM cuenta_ctejf cc
        LEFT JOIN (
            SELECT tipo_doc, num_cta, SUM(monto) AS monto
            FROM cuenta_ctejf
            WHERE tip_mov = '-'
              AND fecha <= '" . str_replace("'", "''", $fin) . "'
            GROUP BY tipo_doc, num_cta
        ) AS c1
          ON cc.tipo_doc = c1.tipo_doc AND cc.num_cta = c1.num_cta
        LEFT JOIN clientesjf c ON cc.cliente = c.codigo
        WHERE cc.tip_mov = '+'
          AND cc.fecha BETWEEN '" . str_replace("'", "''", $inicio) . "' AND '" . str_replace("'", "''", $fin) . "'
          AND (cc.monto - IFNULL(c1.monto, 0)) > 0";
        if ($f['cli'] !== '') {
            $sql .= ' AND cc.cliente = :cli';
            $params[':cli'] = $f['cli'];
        }
        $sql .= ' GROUP BY cc.cliente, c.nombre
          HAVING saldo_fecha > 0
          ORDER BY cc.cliente';
        return self::ejecutar($pdo, $sql, $params);
    }
}
