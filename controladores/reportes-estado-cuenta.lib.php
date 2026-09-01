<?php

/**
 * Estado de cuenta — lógica compartida (Excel v1 + preview v2).
 */
class ReportesEstadoCuentaLib
{
    const MAX_DIAS = 2190;

    public static function prepararFiltros($filtros)
    {
        $f = array(
            'cli' => isset($filtros['cli']) ? trim((string) $filtros['cli']) : '',
            'vend' => isset($filtros['vend']) ? trim((string) $filtros['vend']) : '',
            'inicio' => isset($filtros['inicio']) ? trim((string) $filtros['inicio']) : '',
            'fin' => isset($filtros['fin']) ? trim((string) $filtros['fin']) : '',
        );

        if ($f['inicio'] === '' && $f['fin'] !== '') {
            $f['inicio'] = $f['fin'];
        }
        if ($f['fin'] === '') {
            $f['fin'] = date('Y-m-d');
        }
        if ($f['inicio'] !== '') {
            $f['fecha_corte'] = date('Y-m-d', strtotime($f['inicio'] . ' -1 day'));
        } else {
            $f['fecha_corte'] = '';
        }

        return $f;
    }

    public static function validar($f)
    {
        if ($f['cli'] === '') {
            return array('ok' => false, 'error' => 'Indique el cliente.');
        }
        if ($f['inicio'] === '') {
            return array('ok' => false, 'error' => 'Indique la fecha de inicio.');
        }
        if (!self::fechaValida($f['inicio'])) {
            return array('ok' => false, 'error' => 'La fecha de inicio no es válida.');
        }
        if (!self::fechaValida($f['fin'])) {
            return array('ok' => false, 'error' => 'La fecha de fin no es válida.');
        }
        if (strtotime($f['inicio']) > strtotime($f['fin'])) {
            return array('ok' => false, 'error' => 'La fecha de inicio no puede ser mayor que la de fin.');
        }

        $dias = (strtotime($f['fin']) - strtotime($f['inicio'])) / 86400;
        if ($dias > self::MAX_DIAS) {
            return array(
                'ok' => false,
                'error' => 'El rango supera el máximo de 6 años. Reduzca el periodo o genere el reporte por partes.',
            );
        }

        if ($f['cli'] !== '' && !self::codigoValido($f['cli'])) {
            return array('ok' => false, 'error' => 'El código de cliente no es válido.');
        }
        if ($f['vend'] !== '' && !self::codigoValido($f['vend'])) {
            return array('ok' => false, 'error' => 'El código de vendedor no es válido.');
        }

        return array('ok' => true);
    }

    public static function filtrosSql($pdo, $cliente, $vendedor, $alias)
    {
        $sql = '';
        if ($cliente !== '') {
            $sql .= ' AND ' . $alias . '.cliente = ' . $pdo->quote($cliente);
        }
        if ($vendedor !== '') {
            $sql .= ' AND ' . $alias . '.vendedor = ' . $pdo->quote($vendedor);
        }
        return $sql;
    }

    public static function sqlDetalle($fechaInicial, $fechaFinal, $fechaCorte, $filtrosA, $filtrosB, $filtrosPagos)
    {
        return "SELECT 
            'A' AS orden,
            '' AS tipo_doc,
            '' AS num_cta,
            '' AS cod_pago,
            '' AS doc_origen,
            '{$fechaCorte}' AS fecha,
            '{$fechaCorte}' AS fecha_ven,
            ROUND(SUM(cc.monto - IFNULL(c1.monto, 0)), 2) AS monto,
            0 AS saldo,
            '' AS tip_cambio,
            '' AS ult_pago,
            '' AS tip_mov,
            cc.cliente AS cliente,
            '' AS doc_cliente,
            '' AS nombre,
            '' AS vendedor,
            '' AS notas
          FROM cuenta_ctejf cc
          LEFT JOIN (
            SELECT
              tipo_doc,
              num_cta,
              SUM(monto) AS monto
            FROM cuenta_ctejf
            WHERE tip_mov = '-'
              AND fecha <= '{$fechaCorte}'
              {$filtrosPagos}
            GROUP BY tipo_doc, num_cta
          ) AS c1
            ON cc.tipo_doc = c1.tipo_doc
            AND cc.num_cta = c1.num_cta
          LEFT JOIN clientesjf AS c
            ON cc.cliente = c.codigo
          WHERE cc.tip_mov = '+'
            AND cc.fecha <= '{$fechaCorte}'
            {$filtrosA}
          GROUP BY cc.cliente
          UNION ALL
          SELECT
            'B' AS orden,
            cc.tipo_doc,
            cc.num_cta,
            cc.cod_pago,
            cc.doc_origen,
            cc.fecha,
            cc.fecha_ven,
            ROUND(cc.monto, 2) AS monto,
            ROUND(cc.saldo, 2) AS saldo,
            cc.tip_cambio,
            cc.ult_pago,
            cc.tip_mov,
            cc.cliente,
            c.documento AS doc_cliente,
            c.nombre,
            cc.vendedor,
            cc.notas
          FROM cuenta_ctejf cc
          LEFT JOIN clientesjf c
            ON cc.cliente = c.codigo
          WHERE cc.fecha >= '{$fechaCorte}'
            AND cc.fecha <= '{$fechaFinal}'
            {$filtrosB}
          ORDER BY cliente, orden, tipo_doc, num_cta, fecha, tip_mov";
    }

    public static function consultarDetalle($pdo, $f)
    {
        $filtrosA = self::filtrosSql($pdo, $f['cli'], $f['vend'], 'cc');
        $filtrosB = self::filtrosSql($pdo, $f['cli'], $f['vend'], 'cc');
        $filtrosPagos = '';
        if ($f['cli'] !== '') {
            $filtrosPagos .= ' AND cliente = ' . $pdo->quote($f['cli']);
        }
        if ($f['vend'] !== '') {
            $filtrosPagos .= ' AND vendedor = ' . $pdo->quote($f['vend']);
        }

        $sql = self::sqlDetalle(
            $f['inicio'],
            $f['fin'],
            $f['fecha_corte'],
            $filtrosA,
            $filtrosB,
            $filtrosPagos
        );

        $stmt = $pdo->query($sql);
        if (!$stmt) {
            return array();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : array();
    }

    public static function formatearFecha($fecha)
    {
        if ($fecha === '' || $fecha === null) {
            return '';
        }
        $ts = strtotime($fecha);
        return $ts ? date('d/m/Y', $ts) : $fecha;
    }

    private static function fechaValida($fecha)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        $partes = explode('-', $fecha);
        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]);
    }

    private static function codigoValido($valor)
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]+$/', $valor);
    }
}
