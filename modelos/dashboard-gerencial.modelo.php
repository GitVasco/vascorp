<?php

require_once "conexion.php";
require_once dirname(__FILE__) . "/../controladores/metas-retos.config.php";

class ModeloDashboardGerencial
{
    /** Factor IGV 18%: cobranzas se muestran sin IGV para comparar con ventas netas. */
    const FACTOR_IGV = 1.18;

    /**
     * Convierte un monto de cobranza (con IGV) a base imponible.
     */
    public static function sinIgv($monto)
    {
        return round((float) $monto / self::FACTOR_IGV, 2);
    }

    private static function sqlCobranzaEfectivo($alias = "cc")
    {
        return mrSqlInCodigosCobranzaEfectiva($alias);
    }

    private static function sqlFechaOrigenDoc($aliasPago = 'pago')
    {
        return "COALESCE(
            NULLIF(IF({$aliasPago}.fecha_ori IS NOT NULL AND {$aliasPago}.fecha_ori > '1900-01-01', {$aliasPago}.fecha_ori, NULL), '0000-00-00'),
            doc.fecha
        )";
    }

    private static function sqlJoinDocCargo()
    {
        return "LEFT JOIN (
                SELECT
                    d.tipo_doc,
                    d.num_cta,
                    d.cliente,
                    MIN(d.fecha) AS fecha
                FROM cuenta_ctejf d
                WHERE d.tip_mov = '+'
                GROUP BY d.tipo_doc, d.num_cta, d.cliente
            ) doc
                ON doc.tipo_doc = pago.tipo_doc
               AND doc.num_cta = pago.num_cta
               AND doc.cliente = pago.cliente";
    }

    /**
     * Opciones del filtro vendedor (lista fija + nombres TVEND).
     * Reutiliza la misma fuente que dashboard-cobranzas.
     */
    public static function mdlVendedoresFiltro($anio = null)
    {
        return ModeloDashboardCobranzas::mdlVendedoresConCobranza($anio !== null ? (int) $anio : (int) date('Y'));
    }

    /**
     * Ventas por mes del año (totalesjf) — global, misma fuente que KPIs gerencia.
     * @return array<int,float> mes => monto
     */
    public static function mdlVentasMensualGlobal($anio)
    {
        $anio = (int) $anio;
        $sql = "SELECT
                t.mes AS mes,
                COALESCE(SUM(t.total_ventas_soles), 0) AS venta
            FROM totalesjf t
            WHERE t.año = :anio
            GROUP BY t.mes
            ORDER BY t.mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        $porMes = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[(int) $fila['mes']] = (float) $fila['venta'];
        }

        return $porMes;
    }

    /**
     * Ventas por mes del año (ventajf) — un vendedor.
     * Tipos alineados con ModeloMetasVendedor (venta_real).
     * @return array<int,float> mes => monto
     */
    public static function mdlVentasMensualVendedor($anio, $vendedor)
    {
        $anio = (int) $anio;
        $vendedor = trim((string) $vendedor);
        $inicio = sprintf('%04d-01-01', $anio);
        $fin = sprintf('%04d-01-01', $anio + 1);

        $sql = "SELECT
                MONTH(v.fecha) AS mes,
                COALESCE(SUM(v.neto), 0) AS venta
            FROM ventajf v
            WHERE v.fecha >= :fecha_ini
              AND v.fecha < :fecha_fin
              AND v.vendedor = :vendedor
              AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
            GROUP BY MONTH(v.fecha)
            ORDER BY mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fin, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[(int) $fila['mes']] = (float) $fila['venta'];
        }

        return $porMes;
    }

    /**
     * Ventas en un rango de fechas (ventajf), total y por año-mes.
     * $hastaInclusive es Y-m-d inclusive; se consulta con fin exclusivo (+1 día).
     *
     * @return array{total: float, por_mes: array<string, float>}
     */
    public static function mdlVentasRango($desde, $hastaInclusive, $vendedor = '')
    {
        $desde = (string) $desde;
        $hastaInclusive = (string) $hastaInclusive;
        $vendedor = trim((string) $vendedor);

        $finExclusivo = date('Y-m-d', strtotime($hastaInclusive . ' +1 day'));

        $sql = "SELECT
                YEAR(v.fecha) AS anio,
                MONTH(v.fecha) AS mes,
                COALESCE(SUM(v.neto), 0) AS venta
            FROM ventajf v
            WHERE v.fecha >= :fecha_ini
              AND v.fecha < :fecha_fin
              AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
              AND (:vendedor = '' OR v.vendedor = :vendedor)
            GROUP BY YEAR(v.fecha), MONTH(v.fecha)
            ORDER BY anio ASC, mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $finExclusivo, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array();
        $total = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $anio = (int) $fila['anio'];
            $mes = (int) $fila['mes'];
            $venta = (float) $fila['venta'];
            $clave = sprintf('%04d-%02d', $anio, $mes);
            $porMes[$clave] = $venta;
            $total += $venta;
        }

        return array(
            'total' => $total,
            'por_mes' => $porMes,
        );
    }

    /**
     * Cobranzas por mes del año (totalesjf) — global, misma fuente que KPI gerencia.
     * @return array<int,float>
     */
    public static function mdlCobranzasMensualGlobal($anio)
    {
        $anio = (int) $anio;
        $sql = "SELECT
                t.mes AS mes,
                COALESCE(SUM(t.total_pagos_soles), 0) AS cobranza
            FROM totalesjf t
            WHERE t.año = :anio
            GROUP BY t.mes
            ORDER BY t.mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        $porMes = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[(int) $fila['mes']] = self::sinIgv($fila['cobranza']);
        }

        return $porMes;
    }

    /**
     * Cobranzas por mes del año (cuenta_ctejf efectivo).
     * @return array<int,float>
     */
    public static function mdlCobranzasMensualVendedor($anio, $vendedor)
    {
        $anio = (int) $anio;
        $vendedor = trim((string) $vendedor);
        $inicio = sprintf('%04d-01-01', $anio);
        $fin = sprintf('%04d-01-01', $anio + 1);

        $sql = "SELECT
                MONTH(cc.fecha) AS mes,
                COALESCE(SUM(cc.monto), 0) AS cobranza
            FROM cuenta_ctejf cc
            WHERE cc.tip_mov = '-'
              AND cc.fecha >= :fecha_ini
              AND cc.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo('cc') . "
              AND cc.vendedor = :vendedor
            GROUP BY MONTH(cc.fecha)
            ORDER BY mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fin, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[(int) $fila['mes']] = self::sinIgv($fila['cobranza']);
        }

        return $porMes;
    }

    /**
     * Cobranzas en rango de fechas (cuenta_ctejf efectivo), total y por año-mes.
     *
     * @return array{total: float, por_mes: array<string, float>}
     */
    public static function mdlCobranzasRango($desde, $hastaInclusive, $vendedor = '')
    {
        $desde = (string) $desde;
        $hastaInclusive = (string) $hastaInclusive;
        $vendedor = trim((string) $vendedor);
        $finExclusivo = date('Y-m-d', strtotime($hastaInclusive . ' +1 day'));

        $sql = "SELECT
                YEAR(cc.fecha) AS anio,
                MONTH(cc.fecha) AS mes,
                COALESCE(SUM(cc.monto), 0) AS cobranza
            FROM cuenta_ctejf cc
            WHERE cc.tip_mov = '-'
              AND cc.fecha >= :fecha_ini
              AND cc.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo('cc') . "
              AND (:vendedor = '' OR cc.vendedor = :vendedor)
            GROUP BY YEAR(cc.fecha), MONTH(cc.fecha)
            ORDER BY anio ASC, mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $finExclusivo, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array();
        $total = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $clave = sprintf('%04d-%02d', (int) $fila['anio'], (int) $fila['mes']);
            $monto = self::sinIgv($fila['cobranza']);
            $porMes[$clave] = $monto;
            $total += $monto;
        }

        return array(
            'total' => $total,
            'por_mes' => $porMes,
        );
    }

    /**
     * Cobranza efectiva del período de cobro, agrupada por mes/año de origen del documento.
     * Origen: COALESCE(fecha_ori válida del abono, MIN(fecha) del cargo tip_mov='+').
     *
     * @return array{filas: array<int,array>, sin_origen: float, total: float}
     */
    public static function mdlOrigenCobranza($desde, $hastaInclusive, $vendedor = '')
    {
        $desde = (string) $desde;
        $hastaInclusive = (string) $hastaInclusive;
        $vendedor = trim((string) $vendedor);
        $finExclusivo = date('Y-m-d', strtotime($hastaInclusive . ' +1 day'));

        $sqlFechaOrigen = "COALESCE(
            NULLIF(IF(pago.fecha_ori IS NOT NULL AND pago.fecha_ori > '1900-01-01', pago.fecha_ori, NULL), '0000-00-00'),
            doc.fecha
        )";

        $sql = "SELECT
                YEAR({$sqlFechaOrigen}) AS anio_origen,
                MONTH({$sqlFechaOrigen}) AS mes_origen,
                COALESCE(SUM(pago.monto), 0) AS monto,
                COALESCE(SUM(
                    CASE
                        WHEN {$sqlFechaOrigen} IS NOT NULL
                         AND YEAR({$sqlFechaOrigen}) = YEAR(pago.fecha)
                         AND MONTH({$sqlFechaOrigen}) = MONTH(pago.fecha)
                        THEN pago.monto
                        ELSE 0
                    END
                ), 0) AS mismo_mes
            FROM cuenta_ctejf pago
            LEFT JOIN (
                SELECT
                    d.tipo_doc,
                    d.num_cta,
                    d.cliente,
                    MIN(d.fecha) AS fecha
                FROM cuenta_ctejf d
                WHERE d.tip_mov = '+'
                GROUP BY d.tipo_doc, d.num_cta, d.cliente
            ) doc
                ON doc.tipo_doc = pago.tipo_doc
               AND doc.num_cta = pago.num_cta
               AND doc.cliente = pago.cliente
            WHERE pago.tip_mov = '-'
              AND pago.fecha >= :fecha_ini
              AND pago.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND (:vendedor = '' OR pago.vendedor = :vendedor)
            GROUP BY YEAR({$sqlFechaOrigen}), MONTH({$sqlFechaOrigen})
            ORDER BY anio_origen DESC, mes_origen DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $finExclusivo, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $filas = array();
        $sinOrigen = 0.0;
        $total = 0.0;
        $mismoMes = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $monto = self::sinIgv($fila['monto']);
            $mismoMes += self::sinIgv($fila['mismo_mes']);
            $total += $monto;
            $anio = $fila['anio_origen'] !== null ? (int) $fila['anio_origen'] : null;
            $mes = $fila['mes_origen'] !== null ? (int) $fila['mes_origen'] : null;

            if ($anio === null || $mes === null || $anio < 1990 || $mes < 1 || $mes > 12) {
                $sinOrigen += $monto;
                continue;
            }

            $filas[] = array(
                'anio' => $anio,
                'mes' => $mes,
                'monto' => $monto,
            );
        }

        return array(
            'filas' => $filas,
            'sin_origen' => $sinOrigen,
            'mismo_mes' => $mismoMes,
            'total' => $total,
        );
    }

    /**
     * Origen de cobranza por vendedor (para pivote / resumen Bloque 7).
     *
     * @return array<int,array{vendedor:string,nombre:string,total:float,mismo_mes:float}>
     */
    public static function mdlOrigenCobranzaPorVendedor($desde, $hastaInclusive, $limite = 15)
    {
        $desde = (string) $desde;
        $hastaInclusive = (string) $hastaInclusive;
        $finExclusivo = date('Y-m-d', strtotime($hastaInclusive . ' +1 day'));
        $limite = max(1, min(50, (int) $limite));

        $sqlFechaOrigen = "COALESCE(
            NULLIF(IF(pago.fecha_ori IS NOT NULL AND pago.fecha_ori > '1900-01-01', pago.fecha_ori, NULL), '0000-00-00'),
            doc.fecha
        )";

        $sql = "SELECT
                pago.vendedor AS vendedor,
                COALESCE(m.descripcion, pago.vendedor) AS nombre,
                COALESCE(SUM(pago.monto), 0) AS total,
                COALESCE(SUM(
                    CASE
                        WHEN {$sqlFechaOrigen} IS NOT NULL
                         AND YEAR({$sqlFechaOrigen}) = YEAR(pago.fecha)
                         AND MONTH({$sqlFechaOrigen}) = MONTH(pago.fecha)
                        THEN pago.monto
                        ELSE 0
                    END
                ), 0) AS mismo_mes
            FROM cuenta_ctejf pago
            LEFT JOIN (
                SELECT
                    d.tipo_doc,
                    d.num_cta,
                    d.cliente,
                    MIN(d.fecha) AS fecha
                FROM cuenta_ctejf d
                WHERE d.tip_mov = '+'
                GROUP BY d.tipo_doc, d.num_cta, d.cliente
            ) doc
                ON doc.tipo_doc = pago.tipo_doc
               AND doc.num_cta = pago.num_cta
               AND doc.cliente = pago.cliente
            LEFT JOIN maestrajf m
                ON m.codigo = pago.vendedor
               AND m.tipo_dato = 'TVEND'
            WHERE pago.tip_mov = '-'
              AND pago.fecha >= :fecha_ini
              AND pago.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND pago.vendedor IS NOT NULL
              AND pago.vendedor <> ''
            GROUP BY pago.vendedor, m.descripcion
            HAVING total > 0
            ORDER BY total DESC
            LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $finExclusivo, PDO::PARAM_STR);
        $stmt->execute();

        $out = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $total = self::sinIgv($fila['total']);
            $mismo = self::sinIgv($fila['mismo_mes']);
            $out[] = array(
                'vendedor' => (string) $fila['vendedor'],
                'nombre' => (string) $fila['nombre'],
                'total' => $total,
                'mismo_mes' => $mismo,
                'pct_mismo_mes' => $total > 0 ? round(($mismo / $total) * 100, 1) : 0.0,
            );
        }

        return $out;
    }

    /**
     * Cobranza efectiva recuperada hasta hoy (o hasta $hastaPago) de documentos
     * cuya fecha de origen cae en [$desdeOrigen, $hastaOrigenInclusive].
     */
    public static function mdlRecuperadoDeOrigenHasta($desdeOrigen, $hastaOrigenInclusive, $vendedor = '', $hastaPagoInclusive = null)
    {
        $desdeOrigen = (string) $desdeOrigen;
        $hastaOrigenInclusive = (string) $hastaOrigenInclusive;
        $vendedor = trim((string) $vendedor);
        $finOrigen = date('Y-m-d', strtotime($hastaOrigenInclusive . ' +1 day'));
        $hastaPagoInclusive = $hastaPagoInclusive ? (string) $hastaPagoInclusive : date('Y-m-d');
        $finPago = date('Y-m-d', strtotime($hastaPagoInclusive . ' +1 day'));
        $fechaOri = self::sqlFechaOrigenDoc('pago');

        $sql = "SELECT COALESCE(SUM(pago.monto), 0) AS total
            FROM cuenta_ctejf pago
            " . self::sqlJoinDocCargo() . "
            WHERE pago.tip_mov = '-'
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND pago.fecha < :fin_pago
              AND {$fechaOri} IS NOT NULL
              AND {$fechaOri} >= :ini_origen
              AND {$fechaOri} < :fin_origen
              AND (:vendedor = '' OR pago.vendedor = :vendedor)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fin_pago', $finPago, PDO::PARAM_STR);
        $stmt->bindValue(':ini_origen', $desdeOrigen, PDO::PARAM_STR);
        $stmt->bindValue(':fin_origen', $finOrigen, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? self::sinIgv($fila['total']) : 0.0;
    }

    /**
     * Reverso de origen: de docs con origen en el período, cómo se reparte
     * lo recuperado por mes de pago (hasta hoy).
     *
     * @return array{filas: array<int,array{anio:int,mes:int,monto:float}>, total: float}
     */
    public static function mdlRecuperacionPorMesPago($desdeOrigen, $hastaOrigenInclusive, $vendedor = '', $hastaPagoInclusive = null)
    {
        $desdeOrigen = (string) $desdeOrigen;
        $hastaOrigenInclusive = (string) $hastaOrigenInclusive;
        $vendedor = trim((string) $vendedor);
        $finOrigen = date('Y-m-d', strtotime($hastaOrigenInclusive . ' +1 day'));
        $hastaPagoInclusive = $hastaPagoInclusive ? (string) $hastaPagoInclusive : date('Y-m-d');
        $finPago = date('Y-m-d', strtotime($hastaPagoInclusive . ' +1 day'));
        $fechaOri = self::sqlFechaOrigenDoc('pago');

        $sql = "SELECT
                YEAR(pago.fecha) AS anio_pago,
                MONTH(pago.fecha) AS mes_pago,
                COALESCE(SUM(pago.monto), 0) AS monto
            FROM cuenta_ctejf pago
            " . self::sqlJoinDocCargo() . "
            WHERE pago.tip_mov = '-'
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND pago.fecha < :fin_pago
              AND {$fechaOri} IS NOT NULL
              AND {$fechaOri} >= :ini_origen
              AND {$fechaOri} < :fin_origen
              AND (:vendedor = '' OR pago.vendedor = :vendedor)
            GROUP BY YEAR(pago.fecha), MONTH(pago.fecha)
            ORDER BY anio_pago DESC, mes_pago DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fin_pago', $finPago, PDO::PARAM_STR);
        $stmt->bindValue(':ini_origen', $desdeOrigen, PDO::PARAM_STR);
        $stmt->bindValue(':fin_origen', $finOrigen, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $filas = array();
        $total = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $monto = self::sinIgv($fila['monto']);
            $total += $monto;
            $filas[] = array(
                'anio' => (int) $fila['anio_pago'],
                'mes' => (int) $fila['mes_pago'],
                'monto' => $monto,
            );
        }

        return array(
            'filas' => $filas,
            'total' => $total,
        );
    }

    /**
     * Por mes del año:
     * - venta
     * - recuperado hasta hoy (docs con origen en ese mes)
     * - cobrado (pagos del mes) y mismo_mes (pagos del mes con origen = mes del pago)
     *   → misma lógica que KPI % cobro mismo mes
     *
     * @return array<int,array{mes:int,venta:float,recuperado:float,cobrado:float,mismo_mes:float}>
     */
    public static function mdlVentasRecuperacionMensualAnio($anio, $vendedor = '')
    {
        $anio = (int) $anio;
        $vendedor = trim((string) $vendedor);
        $iniAnio = sprintf('%04d-01-01', $anio);
        $finAnio = sprintf('%04d-01-01', $anio + 1);
        $finPago = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
        $fechaOri = self::sqlFechaOrigenDoc('pago');

        if ($vendedor === '') {
            $ventas = self::mdlVentasMensualGlobal($anio);
        } else {
            $ventas = self::mdlVentasMensualVendedor($anio, $vendedor);
        }

        // Recuperado de docs con origen en cada mes (hasta hoy).
        $sqlRecup = "SELECT
                MONTH({$fechaOri}) AS mes,
                COALESCE(SUM(pago.monto), 0) AS recuperado
            FROM cuenta_ctejf pago
            " . self::sqlJoinDocCargo() . "
            WHERE pago.tip_mov = '-'
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND pago.fecha < :fin_pago
              AND {$fechaOri} >= :ini_anio
              AND {$fechaOri} < :fin_anio
              AND (:vendedor = '' OR pago.vendedor = :vendedor)
            GROUP BY MONTH({$fechaOri})
            ORDER BY mes ASC";

        $stmt = Conexion::conectar()->prepare($sqlRecup);
        $stmt->bindValue(':fin_pago', $finPago, PDO::PARAM_STR);
        $stmt->bindValue(':ini_anio', $iniAnio, PDO::PARAM_STR);
        $stmt->bindValue(':fin_anio', $finAnio, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $recup = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $recup[(int) $fila['mes']] = self::sinIgv($fila['recuperado']);
        }

        // Cobrado del mes + mismo mes (agrupa por mes de PAGO = misma lógica del KPI).
        $sqlCobro = "SELECT
                MONTH(pago.fecha) AS mes,
                COALESCE(SUM(pago.monto), 0) AS cobrado,
                COALESCE(SUM(
                    CASE
                        WHEN {$fechaOri} IS NOT NULL
                         AND YEAR({$fechaOri}) = YEAR(pago.fecha)
                         AND MONTH({$fechaOri}) = MONTH(pago.fecha)
                        THEN pago.monto ELSE 0
                    END
                ), 0) AS mismo_mes
            FROM cuenta_ctejf pago
            " . self::sqlJoinDocCargo() . "
            WHERE pago.tip_mov = '-'
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND pago.fecha >= :ini_anio
              AND pago.fecha < :fin_anio
              AND (:vendedor = '' OR pago.vendedor = :vendedor)
            GROUP BY MONTH(pago.fecha)
            ORDER BY mes ASC";

        $stmt2 = Conexion::conectar()->prepare($sqlCobro);
        $stmt2->bindValue(':ini_anio', $iniAnio, PDO::PARAM_STR);
        $stmt2->bindValue(':fin_anio', $finAnio, PDO::PARAM_STR);
        $stmt2->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt2->execute();

        $cobroPorMes = array();
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $cobroPorMes[(int) $fila['mes']] = array(
                'cobrado' => self::sinIgv($fila['cobrado']),
                'mismo_mes' => self::sinIgv($fila['mismo_mes']),
            );
        }

        $mesHasta = ($anio === (int) date('Y')) ? (int) date('n') : 12;
        $out = array();

        for ($mes = 1; $mes <= $mesHasta; $mes++) {
            $venta = isset($ventas[$mes]) ? (float) $ventas[$mes] : 0.0;
            $c = isset($cobroPorMes[$mes])
                ? $cobroPorMes[$mes]
                : array('cobrado' => 0.0, 'mismo_mes' => 0.0);
            $out[] = array(
                'mes' => $mes,
                'venta' => $venta,
                'recuperado' => isset($recup[$mes]) ? (float) $recup[$mes] : 0.0,
                'cobrado' => (float) $c['cobrado'],
                'mismo_mes' => (float) $c['mismo_mes'],
            );
        }

        return $out;
    }

    /**
     * Antigüedad del cobro en el período: días entre origen y fecha de pago.
     *
     * @return array<string,float> id rango => monto
     */
    public static function mdlOrigenCobranzaAging($desde, $hastaInclusive, $vendedor = '')
    {
        $desde = (string) $desde;
        $hastaInclusive = (string) $hastaInclusive;
        $vendedor = trim((string) $vendedor);
        $finExclusivo = date('Y-m-d', strtotime($hastaInclusive . ' +1 day'));
        $fechaOri = self::sqlFechaOrigenDoc('pago');

        $sql = "SELECT
                CASE
                    WHEN {$fechaOri} IS NULL THEN 'sin_origen'
                    WHEN DATEDIFF(pago.fecha, {$fechaOri}) <= 30 THEN '0-30'
                    WHEN DATEDIFF(pago.fecha, {$fechaOri}) <= 60 THEN '31-60'
                    WHEN DATEDIFF(pago.fecha, {$fechaOri}) <= 90 THEN '61-90'
                    WHEN DATEDIFF(pago.fecha, {$fechaOri}) <= 180 THEN '91-180'
                    ELSE '180+'
                END AS rango,
                COALESCE(SUM(pago.monto), 0) AS monto
            FROM cuenta_ctejf pago
            " . self::sqlJoinDocCargo() . "
            WHERE pago.tip_mov = '-'
              AND pago.fecha >= :fecha_ini
              AND pago.fecha < :fecha_fin
              AND " . self::sqlCobranzaEfectivo('pago') . "
              AND (:vendedor = '' OR pago.vendedor = :vendedor)
            GROUP BY rango";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':fecha_ini', $desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $finExclusivo, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $orden = array('0-30', '31-60', '61-90', '91-180', '180+', 'sin_origen');
        $out = array();
        foreach ($orden as $id) {
            $out[$id] = 0.0;
        }

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $id = (string) $fila['rango'];
            if (isset($out[$id])) {
                $out[$id] = self::sinIgv($fila['monto']);
            }
        }

        return $out;
    }

    /**
     * Cumplimiento de vencimientos: cargos tip_mov='+' con fecha_ven en el rango,
     * clasificados por ult_pago (o MAX fecha de abonos) vs fecha_ven.
     *
     * - a_tiempo: sin saldo y pago ≤ vencimiento
     * - atrasado: sin saldo y pago > vencimiento
     * - pendiente: saldo > 0 (o sin fecha de pago)
     *
     * @return array{
     *   total:float,a_tiempo:float,atrasado:float,pendiente:float,
     *   docs_total:int,docs_a_tiempo:int,docs_atrasado:int,docs_pendiente:int
     * }
     */
    public static function mdlPuntualidadVencimiento($desdeVen, $hastaVenInclusive, $vendedor = '')
    {
        $desdeVen = (string) $desdeVen;
        $hastaVenInclusive = (string) $hastaVenInclusive;
        $vendedor = trim((string) $vendedor);
        $finVen = date('Y-m-d', strtotime($hastaVenInclusive . ' +1 day'));

        $sqlFechaPago = "COALESCE(
            NULLIF(IF(cc.ult_pago IS NOT NULL AND cc.ult_pago > '1900-01-01', cc.ult_pago, NULL), '0000-00-00'),
            p.ult_pago_calc
        )";

        $sql = "SELECT
                COALESCE(SUM(base.monto), 0) AS total,
                COALESCE(SUM(CASE WHEN base.clase = 'a_tiempo' THEN base.monto ELSE 0 END), 0) AS a_tiempo,
                COALESCE(SUM(CASE WHEN base.clase = 'atrasado' THEN base.monto ELSE 0 END), 0) AS atrasado,
                COALESCE(SUM(CASE WHEN base.clase = 'pendiente' THEN base.monto ELSE 0 END), 0) AS pendiente,
                COUNT(*) AS docs_total,
                COALESCE(SUM(CASE WHEN base.clase = 'a_tiempo' THEN 1 ELSE 0 END), 0) AS docs_a_tiempo,
                COALESCE(SUM(CASE WHEN base.clase = 'atrasado' THEN 1 ELSE 0 END), 0) AS docs_atrasado,
                COALESCE(SUM(CASE WHEN base.clase = 'pendiente' THEN 1 ELSE 0 END), 0) AS docs_pendiente
            FROM (
                SELECT
                    cc.monto,
                    CASE
                        WHEN cc.saldo > 0.009 THEN 'pendiente'
                        WHEN {$sqlFechaPago} IS NOT NULL
                         AND {$sqlFechaPago} <= cc.fecha_ven THEN 'a_tiempo'
                        WHEN {$sqlFechaPago} IS NOT NULL
                         AND {$sqlFechaPago} > cc.fecha_ven THEN 'atrasado'
                        ELSE 'pendiente'
                    END AS clase
                FROM cuenta_ctejf cc
                LEFT JOIN (
                    SELECT
                        d.tipo_doc,
                        d.num_cta,
                        d.cliente,
                        MAX(d.fecha) AS ult_pago_calc
                    FROM cuenta_ctejf d
                    WHERE d.tip_mov = '-'
                    GROUP BY d.tipo_doc, d.num_cta, d.cliente
                ) p
                    ON p.tipo_doc = cc.tipo_doc
                   AND p.num_cta = cc.num_cta
                   AND p.cliente = cc.cliente
                WHERE cc.tip_mov = '+'
                  AND cc.fecha_ven >= :ini_ven
                  AND cc.fecha_ven < :fin_ven
                  AND cc.fecha_ven IS NOT NULL
                  AND cc.fecha_ven > '1900-01-01'
                  AND (:vendedor = '' OR cc.vendedor = :vendedor)
            ) base";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':ini_ven', $desdeVen, PDO::PARAM_STR);
        $stmt->bindValue(':fin_ven', $finVen, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array(
                'total' => 0.0,
                'a_tiempo' => 0.0,
                'atrasado' => 0.0,
                'pendiente' => 0.0,
                'docs_total' => 0,
                'docs_a_tiempo' => 0,
                'docs_atrasado' => 0,
                'docs_pendiente' => 0,
            );
        }

        return array(
            'total' => self::sinIgv($fila['total']),
            'a_tiempo' => self::sinIgv($fila['a_tiempo']),
            'atrasado' => self::sinIgv($fila['atrasado']),
            'pendiente' => self::sinIgv($fila['pendiente']),
            'docs_total' => (int) $fila['docs_total'],
            'docs_a_tiempo' => (int) $fila['docs_a_tiempo'],
            'docs_atrasado' => (int) $fila['docs_atrasado'],
            'docs_pendiente' => (int) $fila['docs_pendiente'],
        );
    }

    /**
     * Puntualidad mes a mes del año (por fecha_ven).
     *
     * @return array<int,array{mes:int,total:float,a_tiempo:float,atrasado:float,pendiente:float}>
     */
    public static function mdlPuntualidadVencimientoMensualAnio($anio, $vendedor = '')
    {
        $anio = (int) $anio;
        $vendedor = trim((string) $vendedor);
        $iniAnio = sprintf('%04d-01-01', $anio);
        $finAnio = sprintf('%04d-01-01', $anio + 1);

        $sqlFechaPago = "COALESCE(
            NULLIF(IF(cc.ult_pago IS NOT NULL AND cc.ult_pago > '1900-01-01', cc.ult_pago, NULL), '0000-00-00'),
            p.ult_pago_calc
        )";

        $sql = "SELECT
                MONTH(base.fecha_ven) AS mes,
                COALESCE(SUM(base.monto), 0) AS total,
                COALESCE(SUM(CASE WHEN base.clase = 'a_tiempo' THEN base.monto ELSE 0 END), 0) AS a_tiempo,
                COALESCE(SUM(CASE WHEN base.clase = 'atrasado' THEN base.monto ELSE 0 END), 0) AS atrasado,
                COALESCE(SUM(CASE WHEN base.clase = 'pendiente' THEN base.monto ELSE 0 END), 0) AS pendiente
            FROM (
                SELECT
                    cc.monto,
                    cc.fecha_ven,
                    CASE
                        WHEN cc.saldo > 0.009 THEN 'pendiente'
                        WHEN {$sqlFechaPago} IS NOT NULL
                         AND {$sqlFechaPago} <= cc.fecha_ven THEN 'a_tiempo'
                        WHEN {$sqlFechaPago} IS NOT NULL
                         AND {$sqlFechaPago} > cc.fecha_ven THEN 'atrasado'
                        ELSE 'pendiente'
                    END AS clase
                FROM cuenta_ctejf cc
                LEFT JOIN (
                    SELECT
                        d.tipo_doc,
                        d.num_cta,
                        d.cliente,
                        MAX(d.fecha) AS ult_pago_calc
                    FROM cuenta_ctejf d
                    WHERE d.tip_mov = '-'
                    GROUP BY d.tipo_doc, d.num_cta, d.cliente
                ) p
                    ON p.tipo_doc = cc.tipo_doc
                   AND p.num_cta = cc.num_cta
                   AND p.cliente = cc.cliente
                WHERE cc.tip_mov = '+'
                  AND cc.fecha_ven >= :ini_anio
                  AND cc.fecha_ven < :fin_anio
                  AND cc.fecha_ven IS NOT NULL
                  AND cc.fecha_ven > '1900-01-01'
                  AND (:vendedor = '' OR cc.vendedor = :vendedor)
            ) base
            GROUP BY MONTH(base.fecha_ven)
            ORDER BY mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(':ini_anio', $iniAnio, PDO::PARAM_STR);
        $stmt->bindValue(':fin_anio', $finAnio, PDO::PARAM_STR);
        $stmt->bindValue(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[(int) $fila['mes']] = array(
                'total' => self::sinIgv($fila['total']),
                'a_tiempo' => self::sinIgv($fila['a_tiempo']),
                'atrasado' => self::sinIgv($fila['atrasado']),
                'pendiente' => self::sinIgv($fila['pendiente']),
            );
        }

        $mesHasta = ($anio === (int) date('Y')) ? (int) date('n') : 12;
        $out = array();
        for ($mes = 1; $mes <= $mesHasta; $mes++) {
            $r = isset($porMes[$mes])
                ? $porMes[$mes]
                : array('total' => 0.0, 'a_tiempo' => 0.0, 'atrasado' => 0.0, 'pendiente' => 0.0);
            $out[] = array(
                'mes' => $mes,
                'total' => (float) $r['total'],
                'a_tiempo' => (float) $r['a_tiempo'],
                'atrasado' => (float) $r['atrasado'],
                'pendiente' => (float) $r['pendiente'],
            );
        }

        return $out;
    }
}
