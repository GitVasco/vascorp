<?php

require_once "conexion.php";
require_once dirname(__FILE__) . "/../controladores/dashboard-cxc.config.php";

class ModeloDashboardCxc
{
    private static function diasDelMes($anio, $mes)
    {
        $mes = (int) $mes;
        $anio = (int) $anio;

        if (function_exists('cal_days_in_month')) {
            return (int) cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        }

        $diasPorMes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

        if ($mes === 2) {
            $bisiesto = ($anio % 400 === 0) || ($anio % 4 === 0 && $anio % 100 !== 0);
            return $bisiesto ? 29 : 28;
        }

        return isset($diasPorMes[$mes - 1]) ? $diasPorMes[$mes - 1] : 31;
    }

    public static function mdlFechaCorteDesdePeriodo($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;

        if ($mes === 0) {
            $mes = 12;
        }

        $ultimoDia = self::diasDelMes($anio, $mes);
        $fechaCorte = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);
        $hoy = date('Y-m-d');

        return $fechaCorte > $hoy ? $hoy : $fechaCorte;
    }

    private static function esPeriodoAnual(array $filtros)
    {
        return !empty($filtros['periodo_anual']) || (int) $filtros['mes'] === 0;
    }

    private static function fechaCorteSql(array $filtros)
    {
        $fecha = isset($filtros['fecha_corte']) ? (string) $filtros['fecha_corte'] : date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        return "'" . $fecha . "'";
    }

    private static function sqlDiasVencido($alias = 'cc', $fechaCorteSql = null)
    {
        $fecha = $fechaCorteSql !== null ? $fechaCorteSql : "'" . date('Y-m-d') . "'";

        return 'GREATEST(0, DATEDIFF(' . $fecha . ', ' . $alias . '.fecha_ven))';
    }

    private static function sqlEsVencido($alias = 'cc', $fechaCorteSql = null)
    {
        $fecha = $fechaCorteSql !== null ? $fechaCorteSql : "'" . date('Y-m-d') . "'";

        return $alias . '.fecha_ven < ' . $fecha;
    }

    private static function sqlEsIncobrable($alias = 'cc')
    {
        return dashboardCxcSqlInIncobrablesLiterals($alias);
    }

    private static function incluirTodosVendedores(array $filtros)
    {
        return !empty($filtros['todos_vendedores']);
    }

    /** INNER JOIN si solo activos; LEFT JOIN si se incluyen todos. */
    private static function sqlJoinMaestraVendedor($codigoExpr, array $filtros, $alias = 'mv')
    {
        $base = "TRIM({$alias}.codigo) = TRIM({$codigoExpr})
               AND UPPER({$alias}.tipo_dato) = 'TVEND'";

        if (self::incluirTodosVendedores($filtros)) {
            return "LEFT JOIN maestrajf {$alias} ON {$base}";
        }

        return "INNER JOIN maestrajf {$alias} ON {$base} AND {$alias}.estado_decisiones = 1";
    }

    private static function filtrarFilasSoloVendedoresActivos(array $filas, array $filtros)
    {
        if (self::incluirTodosVendedores($filtros)) {
            return $filas;
        }

        return array_values(array_filter($filas, function ($fila) {
            return !empty($fila['es_activo']);
        }));
    }

    private static function sqlFromBase()
    {
        return 'FROM cuenta_ctejf cc
            LEFT JOIN clientesjf cl ON cc.cliente = cl.codigo';
    }

    private static function sqlJoinGrupoEmpresarial($aliasCliente = 'cl', $aliasGrupo = 'ge')
    {
        return "LEFT JOIN grupos_empresarialesjf {$aliasGrupo}
            ON TRIM({$aliasGrupo}.codigo) = TRIM({$aliasCliente}.grupo)
            AND {$aliasGrupo}.estado = 1";
    }

    /** Clave de agrupación: grupo empresarial activo o cliente suelto. */
    private static function sqlExprEntidadTopDeuda($aliasGrupo = 'ge', $aliasCliente = 'cc')
    {
        return "CASE
            WHEN {$aliasGrupo}.codigo IS NOT NULL AND TRIM({$aliasGrupo}.codigo) <> ''
                THEN CONCAT('G|', TRIM({$aliasGrupo}.codigo))
            ELSE CONCAT('C|', {$aliasCliente}.cliente)
        END";
    }

    private static function clasificarRiesgoTopDeuda($antiguedad, $esIncobrable)
    {
        if ($esIncobrable) {
            return 'incobrable';
        }
        if ($antiguedad > 180) {
            return '180+';
        }
        if ($antiguedad > 60) {
            return 'alto';
        }
        if ($antiguedad > 0) {
            return 'medio';
        }

        return 'bajo';
    }

    /** Tablas por vendedor + detalle de documentos. El resto del dashboard usa sqlFromBase(). */
    private static function sqlFromBaseTablaVendedor(array $filtros)
    {
        $joinVendedor = self::sqlJoinMaestraVendedor('cc.vendedor', $filtros);

        return 'FROM cuenta_ctejf cc
            LEFT JOIN clientesjf cl ON cc.cliente = cl.codigo
            ' . $joinVendedor;
    }

    private static function sqlCaseFacturasGuias($alias = 'cc')
    {
        return "CASE WHEN {$alias}.tipo_doc IN ('01', '03', '07', '08', '09') THEN {$alias}.saldo ELSE 0 END";
    }

    private static function sqlCaseLetrasProyeccion($alias = 'cc')
    {
        return "CASE WHEN {$alias}.tipo_doc IN ('85') AND {$alias}.banco = '02' THEN {$alias}.saldo ELSE 0 END";
    }

    private static function sqlCaseOtrosProyeccion($alias = 'cc')
    {
        $fact = "{$alias}.tipo_doc IN ('01', '03', '07', '08', '09')";
        $letras = "{$alias}.tipo_doc IN ('85') AND {$alias}.banco = '02'";

        return "CASE WHEN NOT ({$fact}) AND NOT ({$letras}) THEN {$alias}.saldo ELSE 0 END";
    }

    private static function normalizarFilaProyeccionTipos(array $fila)
    {
        return array(
            'documentos' => (int) $fila['documentos'],
            'clientes' => (int) $fila['clientes'],
            'facturas_guias' => (float) $fila['facturas_guias'],
            'letras' => (float) $fila['letras'],
            'otros' => (float) $fila['otros'],
            'total' => (float) $fila['total'],
        );
    }

    private static function sqlWhereBase()
    {
        return "cc.tip_mov = '+'
            AND cc.estado = 'PENDIENTE'
            AND cc.saldo > 0
            AND (:vendedor_filtro = '' OR cc.vendedor = :vendedor_valor)
            AND (
                :cliente_filtro = ''
                OR cc.cliente LIKE :cliente_like_codigo
                OR cl.nombre LIKE :cliente_like_nombre
            )
            AND (:zona_filtro = 0 OR cl.id_zona = :zona_valor)";
    }

    private static function bindFiltrosComunes(PDOStatement $stmt, array $filtros)
    {
        $busquedaCliente = '%' . $filtros['cliente'] . '%';

        $stmt->bindValue(':vendedor_filtro', $filtros['vendedor'], PDO::PARAM_STR);
        $stmt->bindValue(':vendedor_valor', $filtros['vendedor'], PDO::PARAM_STR);
        $stmt->bindValue(':cliente_filtro', $filtros['cliente'], PDO::PARAM_STR);
        $stmt->bindValue(':cliente_like_codigo', $busquedaCliente, PDO::PARAM_STR);
        $stmt->bindValue(':cliente_like_nombre', $busquedaCliente, PDO::PARAM_STR);
        $stmt->bindValue(':zona_filtro', (int) $filtros['zona'], PDO::PARAM_INT);
        $stmt->bindValue(':zona_valor', (int) $filtros['zona'], PDO::PARAM_INT);
    }

    private static function sqlFiltroRango($rango, array $filtros, $alias = 'cc')
    {
        $rango = trim((string) $rango);

        if ($rango === '') {
            return '';
        }

        $fechaSql = self::fechaCorteSql($filtros);
        $dias = self::sqlDiasVencido($alias, $fechaSql);
        $vencido = self::sqlEsVencido($alias, $fechaSql);
        $incobrable = self::sqlEsIncobrable($alias);

        switch ($rango) {
            case 'incobrable':
                return ' AND ' . $incobrable;
            case 'por-vencer':
                return ' AND NOT (' . $incobrable . ') AND NOT ' . $vencido;
            case '180+':
                return ' AND NOT (' . $incobrable . ') AND ' . $vencido . ' AND ' . $dias . ' > 180';
            case '91-180':
                return ' AND NOT (' . $incobrable . ') AND ' . $vencido . ' AND ' . $dias . ' BETWEEN 91 AND 180';
            case '61-90':
                return ' AND NOT (' . $incobrable . ') AND ' . $vencido . ' AND ' . $dias . ' BETWEEN 61 AND 90';
            case '31-60':
                return ' AND NOT (' . $incobrable . ') AND ' . $vencido . ' AND ' . $dias . ' BETWEEN 31 AND 60';
            case '0-30':
                return ' AND NOT (' . $incobrable . ') AND ' . $vencido . ' AND ' . $dias . ' <= 30';
            default:
                return '';
        }
    }

    /**
     * Detalle operativo: por defecto sin Incobrables; el resto de rangos sí.
     * Solo incluye Incobrables si el filtro de rango es explícitamente "incobrable".
     */
    private static function sqlFiltroDetalleDocumentos(array $filtros, $alias = 'cc')
    {
        $rango = isset($filtros['rango']) ? trim((string) $filtros['rango']) : '';
        $sqlExtra = self::sqlFiltroRango($rango, $filtros, $alias);

        if ($rango === '') {
            $sqlExtra .= ' AND NOT (' . self::sqlEsIncobrable($alias) . ')';
        }

        return $sqlExtra;
    }

    public static function mdlVendedoresFiltro()
    {
        $sql = "SELECT DISTINCT
                cc.vendedor AS codigo,
                COALESCE(
                    (SELECT m.descripcion
                     FROM maestrajf m
                     WHERE m.codigo = cc.vendedor
                       AND m.tipo_dato = 'TVEND'
                     LIMIT 1),
                    cc.vendedor
                ) AS descripcion
            FROM cuenta_ctejf cc
            WHERE cc.tip_mov = '+'
              AND cc.estado = 'PENDIENTE'
              AND cc.saldo > 0
              AND cc.vendedor IS NOT NULL
              AND cc.vendedor <> ''
            ORDER BY cc.vendedor ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlKpis(array $filtros, $scopeVendedor = false)
    {
        $fechaSql = self::fechaCorteSql($filtros);
        $dias = self::sqlDiasVencido('cc', $fechaSql);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $from = $scopeVendedor ? self::sqlFromBaseTablaVendedor($filtros) : self::sqlFromBase();

        $sql = "SELECT
                COALESCE(SUM(cc.saldo), 0) AS total_por_cobrar,
                COALESCE(SUM(CASE WHEN {$vencido} AND NOT ({$incobrable}) THEN cc.saldo ELSE 0 END), 0) AS monto_vencido,
                COALESCE(SUM(CASE WHEN NOT {$vencido} AND NOT ({$incobrable}) THEN cc.saldo ELSE 0 END), 0) AS monto_por_vencer,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$dias} > 180 THEN cc.saldo ELSE 0 END), 0) AS monto_vencido_180,
                COALESCE(SUM(CASE WHEN {$incobrable} THEN cc.saldo ELSE 0 END), 0) AS monto_incobrable,
                COALESCE(SUM(CASE WHEN cc.fecha_ven >= {$fechaSql} AND cc.fecha_ven <= DATE_ADD({$fechaSql}, INTERVAL 30 DAY) THEN cc.saldo ELSE 0 END), 0) AS cobranza_proyectada_30,
                COALESCE(SUM(CASE WHEN cc.fecha_ven >= DATE_SUB({$fechaSql}, INTERVAL 30 DAY) AND cc.fecha_ven < {$fechaSql} THEN cc.saldo ELSE 0 END), 0) AS cobranza_proyectada_30_ant,
                COUNT(DISTINCT cc.cliente) AS clientes_con_deuda,
                COUNT(DISTINCT CASE WHEN {$vencido} AND NOT ({$incobrable}) THEN cc.cliente END) AS clientes_morosos,
                COUNT(DISTINCT CASE WHEN {$incobrable} THEN cc.cliente END) AS clientes_incobrable
            " . $from . "
            WHERE " . self::sqlWhereBase();

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array(
                'total_por_cobrar' => 0,
                'monto_vencido' => 0,
                'monto_por_vencer' => 0,
                'monto_vencido_180' => 0,
                'monto_incobrable' => 0,
                'cobranza_proyectada_30' => 0,
                'cobranza_proyectada_30_ant' => 0,
                'clientes_con_deuda' => 0,
                'clientes_morosos' => 0,
                'clientes_incobrable' => 0,
            );
        }

        return array(
            'total_por_cobrar' => (float) $fila['total_por_cobrar'],
            'monto_vencido' => (float) $fila['monto_vencido'],
            'monto_por_vencer' => (float) $fila['monto_por_vencer'],
            'monto_vencido_180' => (float) $fila['monto_vencido_180'],
            'monto_incobrable' => (float) $fila['monto_incobrable'],
            'cobranza_proyectada_30' => (float) $fila['cobranza_proyectada_30'],
            'cobranza_proyectada_30_ant' => (float) $fila['cobranza_proyectada_30_ant'],
            'clientes_con_deuda' => (int) $fila['clientes_con_deuda'],
            'clientes_morosos' => (int) $fila['clientes_morosos'],
            'clientes_incobrable' => (int) $fila['clientes_incobrable'],
        );
    }

    public static function mdlAntiguedad(array $filtros, $scopeVendedor = false)
    {
        $fechaSql = self::fechaCorteSql($filtros);
        $dias = self::sqlDiasVencido('cc', $fechaSql);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $from = $scopeVendedor ? self::sqlFromBaseTablaVendedor($filtros) : self::sqlFromBase();

        $sql = "SELECT
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND NOT {$vencido} THEN cc.saldo ELSE 0 END), 0) AS por_vencer,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} <= 30 THEN cc.saldo ELSE 0 END), 0) AS rango_0_30,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 31 AND 60 THEN cc.saldo ELSE 0 END), 0) AS rango_31_60,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 61 AND 90 THEN cc.saldo ELSE 0 END), 0) AS rango_61_90,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 91 AND 180 THEN cc.saldo ELSE 0 END), 0) AS rango_91_180,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} > 180 THEN cc.saldo ELSE 0 END), 0) AS rango_180_mas,
                COALESCE(SUM(CASE WHEN {$incobrable} THEN cc.saldo ELSE 0 END), 0) AS incobrables,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND NOT {$vencido} THEN cc.cliente END) AS clientes_por_vencer,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} <= 30 THEN cc.cliente END) AS clientes_0_30,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 31 AND 60 THEN cc.cliente END) AS clientes_31_60,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 61 AND 90 THEN cc.cliente END) AS clientes_61_90,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} BETWEEN 91 AND 180 THEN cc.cliente END) AS clientes_91_180,
                COUNT(DISTINCT CASE WHEN NOT ({$incobrable}) AND {$vencido} AND {$dias} > 180 THEN cc.cliente END) AS clientes_180_mas,
                COUNT(DISTINCT CASE WHEN {$incobrable} THEN cc.cliente END) AS clientes_incobrables,
                COUNT(DISTINCT cc.cliente) AS clientes_total
            " . $from . "
            WHERE " . self::sqlWhereBase();

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlPorVendedor(array $filtros)
    {
        $fechaSql = self::fechaCorteSql($filtros);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $porVencer = 'NOT (' . $incobrable . ') AND NOT ' . $vencido;

        $sql = "SELECT
                cc.vendedor,
                MAX(COALESCE(mv.descripcion, cc.vendedor)) AS nom_vendedor,
                MAX(COALESCE(mv.estado_decisiones, 0)) AS estado_vendedor,
                COUNT(DISTINCT cc.cliente) AS clientes,
                COALESCE(SUM(CASE WHEN {$porVencer} THEN cc.saldo ELSE 0 END), 0) AS por_vencer,
                COALESCE(SUM(CASE WHEN NOT ({$incobrable}) AND {$vencido} THEN cc.saldo ELSE 0 END), 0) AS vencido,
                COALESCE(SUM(CASE WHEN {$incobrable} THEN cc.saldo ELSE 0 END), 0) AS incobrable,
                COALESCE(SUM(cc.saldo), 0) AS total
            " . self::sqlFromBaseTablaVendedor($filtros) . "
            WHERE " . self::sqlWhereBase() . "
            GROUP BY cc.vendedor
            ORDER BY total DESC, nom_vendedor ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $total = (float) $fila['total'];
            $vencido = (float) $fila['vencido'];
            $fila['pct_vencido'] = $total > 0 ? round(($vencido / $total) * 100, 1) : 0;
            $fila['es_activo'] = (int) $fila['estado_vendedor'] === 1;
        }
        unset($fila);

        return self::filtrarFilasSoloVendedoresActivos($filas, $filtros);
    }

    public static function mdlPorRango(array $filtros)
    {
        $antiguedad = self::mdlAntiguedad($filtros);

        if (!$antiguedad) {
            return array();
        }

        $totalVencido = (float) $antiguedad['rango_0_30']
            + (float) $antiguedad['rango_31_60']
            + (float) $antiguedad['rango_61_90']
            + (float) $antiguedad['rango_91_180']
            + (float) $antiguedad['rango_180_mas'];

        $total = $totalVencido + (float) $antiguedad['incobrables'];

        $rangos = array(
            array('rango' => '0-30', 'monto' => (float) $antiguedad['rango_0_30'], 'clientes' => (int) $antiguedad['clientes_0_30']),
            array('rango' => '31-60', 'monto' => (float) $antiguedad['rango_31_60'], 'clientes' => (int) $antiguedad['clientes_31_60']),
            array('rango' => '61-90', 'monto' => (float) $antiguedad['rango_61_90'], 'clientes' => (int) $antiguedad['clientes_61_90']),
            array('rango' => '91-180', 'monto' => (float) $antiguedad['rango_91_180'], 'clientes' => (int) $antiguedad['clientes_91_180']),
            array('rango' => '180+', 'monto' => (float) $antiguedad['rango_180_mas'], 'clientes' => (int) $antiguedad['clientes_180_mas']),
            array('rango' => 'incobrable', 'monto' => (float) $antiguedad['incobrables'], 'clientes' => (int) $antiguedad['clientes_incobrables']),
        );

        foreach ($rangos as &$rango) {
            if ($rango['rango'] === 'incobrable') {
                $rango['porcentaje'] = $total > 0 ? round(($rango['monto'] / $total) * 100, 1) : 0;
            } else {
                $rango['porcentaje'] = $totalVencido > 0 ? round(($rango['monto'] / $totalVencido) * 100, 1) : 0;
            }
        }
        unset($rango);

        return $rangos;
    }

    public static function mdlTopClientes(array $filtros, $limite = 10, $scopeVendedor = false)
    {
        $limite = max(1, min(50, (int) $limite));
        $fechaSql = self::fechaCorteSql($filtros);
        $dias = self::sqlDiasVencido('cc', $fechaSql);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $entidadKey = self::sqlExprEntidadTopDeuda('ge', 'cc');
        $from = $scopeVendedor ? self::sqlFromBaseTablaVendedor($filtros) : self::sqlFromBase();

        $sql = "SELECT
                CASE
                    WHEN ge.codigo IS NOT NULL AND TRIM(ge.codigo) <> '' THEN 'grupo'
                    ELSE 'cliente'
                END AS tipo_entidad,
                CASE
                    WHEN ge.codigo IS NOT NULL AND TRIM(ge.codigo) <> '' THEN TRIM(ge.codigo)
                    ELSE cc.cliente
                END AS codigo,
                CASE
                    WHEN ge.codigo IS NOT NULL AND TRIM(ge.codigo) <> '' THEN ge.nombre
                    ELSE COALESCE(cl.nombre, cc.cliente)
                END AS nombre_cliente,
                CASE
                    WHEN COUNT(DISTINCT cc.vendedor) > 1 THEN 'Varios'
                    ELSE MAX(cc.vendedor)
                END AS vendedor,
                COALESCE(SUM(cc.saldo), 0) AS saldo,
                COALESCE(SUM(CASE WHEN {$vencido} THEN cc.saldo ELSE 0 END), 0) AS vencido,
                MAX({$dias}) AS antiguedad_max,
                MAX(CASE WHEN {$incobrable} THEN 1 ELSE 0 END) AS es_incobrable,
                COUNT(DISTINCT cc.cliente) AS num_clientes
            " . $from . "
            " . self::sqlJoinGrupoEmpresarial('cl', 'ge') . "
            WHERE " . self::sqlWhereBase() . "
            GROUP BY {$entidadKey}
            ORDER BY vencido DESC, antiguedad_max DESC, saldo DESC
            LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $tipoEntidad = isset($fila['tipo_entidad']) ? (string) $fila['tipo_entidad'] : 'cliente';
            $codigo = isset($fila['codigo']) ? (string) $fila['codigo'] : '';
            $antiguedad = (int) $fila['antiguedad_max'];
            $esIncobrable = (int) $fila['es_incobrable'] === 1;

            $fila['tipo_entidad'] = $tipoEntidad;
            $fila['codigo_grupo'] = $tipoEntidad === 'grupo' ? $codigo : '';
            $fila['cliente'] = $tipoEntidad === 'cliente' ? $codigo : '';
            $fila['num_clientes'] = (int) $fila['num_clientes'];
            $fila['linea_credito'] = null;
            $fila['linea_credito_etiqueta'] = '';
            $fila['riesgo'] = self::clasificarRiesgoTopDeuda($antiguedad, $esIncobrable);
        }
        unset($fila);

        return $filas;
    }

    public static function mdlConteoDetalle(array $filtros)
    {
        $sqlExtra = self::sqlFiltroDetalleDocumentos($filtros);

        $sql = "SELECT COUNT(*) AS total
            " . self::sqlFromBaseTablaVendedor($filtros) . "
            WHERE " . self::sqlWhereBase() . $sqlExtra;

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? (int) $fila['total'] : 0;
    }

    public static function mdlDetalleDocumentos(array $filtros, $pagina = 1, $porPagina = 25, $orden = 'vencido_desc')
    {
        $pagina = max(1, (int) $pagina);
        $porPagina = max(10, min(100, (int) $porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $fechaSql = self::fechaCorteSql($filtros);
        $dias = self::sqlDiasVencido('cc', $fechaSql);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $sqlExtra = self::sqlFiltroDetalleDocumentos($filtros);

        switch ($orden) {
            case 'cliente_asc':
                $orderBy = 'cl.nombre ASC, cc.fecha_ven ASC';
                break;
            case 'saldo_desc':
                $orderBy = 'cc.saldo DESC';
                break;
            case 'antiguedad_desc':
                $orderBy = $dias . ' DESC, cc.saldo DESC';
                break;
            case 'vencido_desc':
            default:
                $orderBy = 'CASE WHEN ' . $vencido . ' THEN 1 ELSE 0 END DESC, ' . $dias . ' DESC, cc.saldo DESC';
                break;
        }

        $sql = "SELECT
                cc.cliente,
                COALESCE(cl.nombre, cc.cliente) AS nombre_cliente,
                cc.vendedor,
                cc.tipo_doc,
                cc.num_cta,
                cc.fecha,
                cc.fecha_ven,
                cc.saldo,
                {$dias} AS dias_antiguedad,
                CASE
                    WHEN {$incobrable} THEN 'incobrable'
                    WHEN {$dias} > 180 THEN '180+'
                    WHEN {$vencido} THEN 'vencido'
                    ELSE 'regular'
                END AS clasificacion,
                CASE
                    WHEN {$incobrable} THEN 'incobrable'
                    WHEN NOT {$vencido} THEN 'por-vencer'
                    WHEN {$dias} > 180 THEN '180+'
                    WHEN {$dias} BETWEEN 91 AND 180 THEN '91-180'
                    WHEN {$dias} BETWEEN 61 AND 90 THEN '61-90'
                    WHEN {$dias} BETWEEN 31 AND 60 THEN '31-60'
                    ELSE '0-30'
                END AS rango
            " . self::sqlFromBaseTablaVendedor($filtros) . "
            WHERE " . self::sqlWhereBase() . $sqlExtra . "
            ORDER BY {$orderBy}
            LIMIT {$porPagina} OFFSET {$offset}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlTotalesDetalleFiltrado(array $filtros)
    {
        $sqlExtra = self::sqlFiltroDetalleDocumentos($filtros);

        $sql = "SELECT COALESCE(SUM(cc.saldo), 0) AS saldo_total
            " . self::sqlFromBaseTablaVendedor($filtros) . "
            WHERE " . self::sqlWhereBase() . $sqlExtra;

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? (float) $fila['saldo_total'] : 0;
    }

    /* ========== Ventas (ventajf) ========== */

    private static function rangoMesVentas($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;

        if ($mes === 0) {
            $inicio = sprintf('%04d-01-01', $anio);
            $fin = sprintf('%04d-01-01', $anio + 1);

            if ($anio === (int) date('Y')) {
                $fin = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
            }

            return array('inicio' => $inicio, 'fin' => $fin);
        }

        $inicio = sprintf('%04d-%02d-01', $anio, $mes);

        if ($mes === 12) {
            $fin = sprintf('%04d-01-01', $anio + 1);
        } else {
            $fin = sprintf('%04d-%02d-01', $anio, $mes + 1);
        }

        return array('inicio' => $inicio, 'fin' => $fin);
    }

    private static function rangosComparativoVentas(array $filtros)
    {
        if (self::esPeriodoAnual($filtros)) {
            $act = self::rangoMesVentas($filtros['anio'], 0);
            $anioAnt = (int) $filtros['anio'] - 1;
            $ant = self::rangoMesVentas($anioAnt, 0);

            return array(
                'actual' => $act,
                'anterior' => $ant,
                'anio_anterior' => $anioAnt,
                'mes_anterior' => 0,
            );
        }

        $act = self::rangoMesVentas($filtros['anio'], $filtros['mes']);
        $mesAnt = (int) $filtros['mes'] - 1;
        $anioAnt = (int) $filtros['anio'];

        if ($mesAnt < 1) {
            $mesAnt = 12;
            $anioAnt--;
        }

        $ant = self::rangoMesVentas($anioAnt, $mesAnt);

        return array(
            'actual' => $act,
            'anterior' => $ant,
            'anio_anterior' => $anioAnt,
            'mes_anterior' => $mesAnt,
        );
    }

    private static function rangoAnioVentasHastaMes($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;

        if ($mes === 0) {
            return self::rangoMesVentas($anio, 0);
        }

        $finMes = self::rangoMesVentas($anio, $mes);

        return array(
            'inicio' => sprintf('%04d-01-01', $anio),
            'fin' => $finMes['fin'],
        );
    }

    private static function sqlZonaEfectivaExpr()
    {
        return "CASE
            WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
            WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
            ELSE r.id_zona
        END";
    }

    private static function sqlFromVentas()
    {
        return "FROM ventajf v
            INNER JOIN clientesjf c ON c.codigo = v.cliente
            LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
            LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = c.ubigeo";
    }

    private static function sqlWhereVentasFiltros()
    {
        $zona = self::sqlZonaEfectivaExpr();

        return "UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
            AND TRIM(IFNULL(v.vendedor, '')) <> ''
            AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
            AND (:vendedor_filtro = '' OR TRIM(v.vendedor) = :vendedor_valor)
            AND (:zona_filtro = 0 OR {$zona} = :zona_valor)";
    }

    private static function bindVentasFiltros(PDOStatement $stmt, array $filtros)
    {
        $stmt->bindValue(':vendedor_filtro', $filtros['vendedor'], PDO::PARAM_STR);
        $stmt->bindValue(':vendedor_valor', $filtros['vendedor'], PDO::PARAM_STR);
        $stmt->bindValue(':zona_filtro', (int) $filtros['zona'], PDO::PARAM_INT);
        $stmt->bindValue(':zona_valor', (int) $filtros['zona'], PDO::PARAM_INT);
    }

    public static function mdlVentasPorVendedor(array $filtros)
    {
        $rangoMes = self::rangoMesVentas($filtros['anio'], $filtros['mes']);

        if (self::esPeriodoAnual($filtros)) {
            $anioAnt = (int) $filtros['anio'] - 1;
            if ((int) $filtros['anio'] === (int) date('Y')) {
                $rangoAnio = array(
                    'inicio' => sprintf('%04d-01-01', $anioAnt),
                    'fin' => date('Y-m-d', strtotime(date('Y-m-d') . ' -1 year +1 day')),
                );
            } else {
                $rangoAnio = self::rangoMesVentas($anioAnt, 0);
            }
        } else {
            $rangoAnio = self::rangoAnioVentasHastaMes($filtros['anio'], $filtros['mes']);
        }

        $joinVendedor = self::sqlJoinMaestraVendedor('v.vendedor', $filtros);

        $sql = "SELECT
                TRIM(v.vendedor) AS vendedor,
                MAX(IFNULL(mv.descripcion, TRIM(v.vendedor))) AS nom_vendedor,
                MAX(COALESCE(mv.estado_decisiones, 0)) AS estado_vendedor,
                COALESCE(SUM(CASE WHEN v.fecha >= :ini_mes AND v.fecha < :fin_mes THEN v.neto ELSE 0 END), 0) AS venta_mes,
                COALESCE(SUM(CASE WHEN v.fecha >= :ini_anio AND v.fecha < :fin_anio THEN v.neto ELSE 0 END), 0) AS venta_anio
            " . self::sqlFromVentas() . "
            {$joinVendedor}
            WHERE (
                    (v.fecha >= :ini_mes_w AND v.fecha < :fin_mes_w)
                 OR (v.fecha >= :ini_anio_w AND v.fecha < :fin_anio_w)
                )
              AND " . self::sqlWhereVentasFiltros() . "
            GROUP BY TRIM(v.vendedor)
            HAVING venta_mes > 0 OR venta_anio > 0
            ORDER BY venta_mes DESC, venta_anio DESC, nom_vendedor ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindVentasFiltros($stmt, $filtros);
        $stmt->bindValue(':ini_mes', $rangoMes['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_mes', $rangoMes['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_anio', $rangoAnio['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_anio', $rangoAnio['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_mes_w', $rangoMes['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_mes_w', $rangoMes['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_anio_w', $rangoAnio['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_anio_w', $rangoAnio['fin'], PDO::PARAM_STR);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['es_activo'] = (int) $fila['estado_vendedor'] === 1;
        }
        unset($fila);

        return self::filtrarFilasSoloVendedoresActivos($filas, $filtros);
    }

    public static function mdlVentasPorTipoDocumento(array $filtros)
    {
        $rango = self::rangoMesVentas($filtros['anio'], $filtros['mes']);

        $sql = "SELECT
                COALESCE(NULLIF(TRIM(v.tipo_documento), ''), v.tipo, 'Sin tipo') AS tipo_documento,
                COALESCE(SUM(v.neto), 0) AS venta,
                COUNT(DISTINCT v.documento) AS documentos
            " . self::sqlFromVentas() . "
            WHERE v.fecha >= :ini_act AND v.fecha < :fin_act
              AND " . self::sqlWhereVentasFiltros() . "
            GROUP BY COALESCE(NULLIF(TRIM(v.tipo_documento), ''), v.tipo, 'Sin tipo')
            ORDER BY venta DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindVentasFiltros($stmt, $filtros);
        $stmt->bindValue(':ini_act', $rango['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act', $rango['fin'], PDO::PARAM_STR);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;

        foreach ($filas as $fila) {
            $total += (float) $fila['venta'];
        }

        foreach ($filas as &$fila) {
            $venta = (float) $fila['venta'];
            $fila['porcentaje'] = $total > 0 ? round(($venta / $total) * 100, 1) : 0;
        }
        unset($fila);

        return array(
            'filas' => $filas,
            'total' => $total,
        );
    }

    public static function mdlVentasPorZona(array $filtros)
    {
        $rango = self::rangoMesVentas($filtros['anio'], $filtros['mes']);
        $zonaExpr = self::sqlZonaEfectivaExpr();

        $sql = "SELECT
                {$zonaExpr} AS id_zona,
                MAX(COALESCE(z.nombre, CONCAT('Zona ', {$zonaExpr}))) AS nombre_zona,
                MAX(COALESCE(NULLIF(TRIM(z.color), ''), '#777777')) AS color_zona,
                COALESCE(SUM(v.neto), 0) AS venta,
                COUNT(DISTINCT v.cliente) AS clientes,
                COUNT(DISTINCT v.documento) AS documentos
            " . self::sqlFromVentas() . "
            LEFT JOIN zonas_comercialesjf z ON z.id = {$zonaExpr}
            WHERE v.fecha >= :ini_act AND v.fecha < :fin_act
              AND " . self::sqlWhereVentasFiltros() . "
            GROUP BY {$zonaExpr}
            HAVING id_zona IS NOT NULL AND id_zona > 0 AND venta > 0
            ORDER BY venta DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindVentasFiltros($stmt, $filtros);
        $stmt->bindValue(':ini_act', $rango['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act', $rango['fin'], PDO::PARAM_STR);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;

        foreach ($filas as $fila) {
            $total += (float) $fila['venta'];
        }

        foreach ($filas as &$fila) {
            $venta = (float) $fila['venta'];
            $fila['porcentaje'] = $total > 0 ? round(($venta / $total) * 100, 1) : 0;
            $color = trim((string) $fila['color_zona']);
            if ($color !== '' && $color[0] !== '#') {
                $color = '#' . $color;
            }
            if (!preg_match('/^#[0-9A-Fa-f]{3,8}$/', $color)) {
                $color = '#777777';
            }
            $fila['color_zona'] = $color;
        }
        unset($fila);

        return array(
            'filas' => $filas,
            'total' => $total,
        );
    }

    public static function mdlVentasTendenciaDiaria(array $filtros)
    {
        $rangos = self::rangosComparativoVentas($filtros);

        if (self::esPeriodoAnual($filtros)) {
            return self::mdlVentasTendenciaMensualAnio($filtros, $rangos);
        }

        $diasAct = self::diasDelMes($filtros['anio'], $filtros['mes']);
        $diasAnt = self::diasDelMes($rangos['anio_anterior'], $rangos['mes_anterior']);
        $maxDias = max($diasAct, $diasAnt);

        $sql = "SELECT
                CASE
                    WHEN v.fecha >= :ini_act AND v.fecha < :fin_act THEN 'act'
                    WHEN v.fecha >= :ini_ant AND v.fecha < :fin_ant THEN 'ant'
                    ELSE 'otro'
                END AS periodo,
                DAY(v.fecha) AS dia,
                COALESCE(SUM(v.neto), 0) AS venta
            " . self::sqlFromVentas() . "
            WHERE (
                    (v.fecha >= :ini_act_w AND v.fecha < :fin_act_w)
                 OR (v.fecha >= :ini_ant_w AND v.fecha < :fin_ant_w)
                )
              AND " . self::sqlWhereVentasFiltros() . "
            GROUP BY periodo, DAY(v.fecha)
            HAVING periodo IN ('act', 'ant')
            ORDER BY periodo ASC, dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindVentasFiltros($stmt, $filtros);
        $stmt->bindValue(':ini_act', $rangos['actual']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act', $rangos['actual']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_ant', $rangos['anterior']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_ant', $rangos['anterior']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_act_w', $rangos['actual']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act_w', $rangos['actual']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_ant_w', $rangos['anterior']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_ant_w', $rangos['anterior']['fin'], PDO::PARAM_STR);
        $stmt->execute();

        $porDia = array('act' => array(), 'ant' => array());
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porDia[$fila['periodo']][(int) $fila['dia']] = (float) $fila['venta'];
        }

        $labels = array();
        $actual = array();
        $anterior = array();

        for ($dia = 1; $dia <= $maxDias; $dia++) {
            $labels[] = (string) $dia;
            $actual[] = isset($porDia['act'][$dia]) ? $porDia['act'][$dia] : 0;
            $anterior[] = ($dia <= $diasAnt && isset($porDia['ant'][$dia])) ? $porDia['ant'][$dia] : 0;
        }

        return array(
            'labels' => $labels,
            'granularidad' => 'dia',
            'mes_actual' => array(
                'anio' => (int) $filtros['anio'],
                'mes' => (int) $filtros['mes'],
                'montos' => $actual,
                'total' => array_sum($actual),
            ),
            'mes_anterior' => array(
                'anio' => (int) $rangos['anio_anterior'],
                'mes' => (int) $rangos['mes_anterior'],
                'montos' => $anterior,
                'total' => array_sum($anterior),
            ),
        );
    }

    private static function mdlVentasTendenciaMensualAnio(array $filtros, array $rangos)
    {
        $sql = "SELECT
                CASE
                    WHEN v.fecha >= :ini_act AND v.fecha < :fin_act THEN 'act'
                    WHEN v.fecha >= :ini_ant AND v.fecha < :fin_ant THEN 'ant'
                    ELSE 'otro'
                END AS periodo,
                MONTH(v.fecha) AS mes,
                COALESCE(SUM(v.neto), 0) AS venta
            " . self::sqlFromVentas() . "
            WHERE (
                    (v.fecha >= :ini_act_w AND v.fecha < :fin_act_w)
                 OR (v.fecha >= :ini_ant_w AND v.fecha < :fin_ant_w)
                )
              AND " . self::sqlWhereVentasFiltros() . "
            GROUP BY periodo, MONTH(v.fecha)
            HAVING periodo IN ('act', 'ant')
            ORDER BY periodo ASC, mes ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindVentasFiltros($stmt, $filtros);
        $stmt->bindValue(':ini_act', $rangos['actual']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act', $rangos['actual']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_ant', $rangos['anterior']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_ant', $rangos['anterior']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_act_w', $rangos['actual']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_act_w', $rangos['actual']['fin'], PDO::PARAM_STR);
        $stmt->bindValue(':ini_ant_w', $rangos['anterior']['inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fin_ant_w', $rangos['anterior']['fin'], PDO::PARAM_STR);
        $stmt->execute();

        $porMes = array('act' => array(), 'ant' => array());
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porMes[$fila['periodo']][(int) $fila['mes']] = (float) $fila['venta'];
        }

        $labelsCortos = array(
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        );

        $mesHasta = 12;
        if ((int) $filtros['anio'] === (int) date('Y')) {
            $mesHasta = (int) date('n');
        }

        $labels = array();
        $actual = array();
        $anterior = array();

        for ($mes = 1; $mes <= $mesHasta; $mes++) {
            $labels[] = $labelsCortos[$mes];
            $actual[] = isset($porMes['act'][$mes]) ? $porMes['act'][$mes] : 0;
            $anterior[] = isset($porMes['ant'][$mes]) ? $porMes['ant'][$mes] : 0;
        }

        return array(
            'labels' => $labels,
            'granularidad' => 'mes',
            'mes_actual' => array(
                'anio' => (int) $filtros['anio'],
                'mes' => 0,
                'montos' => $actual,
                'total' => array_sum($actual),
            ),
            'mes_anterior' => array(
                'anio' => (int) $rangos['anio_anterior'],
                'mes' => 0,
                'montos' => $anterior,
                'total' => array_sum($anterior),
            ),
        );
    }

    public static function mdlProyeccionPagosResumenVencido(array $filtros)
    {
        $fechaSql = self::fechaCorteSql($filtros);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $facturasGuias = self::sqlCaseFacturasGuias('cc');
        $letras = self::sqlCaseLetrasProyeccion('cc');
        $otros = self::sqlCaseOtrosProyeccion('cc');

        $sql = "SELECT
                COUNT(*) AS documentos,
                COUNT(DISTINCT cc.cliente) AS clientes,
                COALESCE(SUM({$facturasGuias}), 0) AS facturas_guias,
                COALESCE(SUM({$letras}), 0) AS letras,
                COALESCE(SUM({$otros}), 0) AS otros,
                COALESCE(SUM(cc.saldo), 0) AS total
            " . self::sqlFromBase() . "
            WHERE " . self::sqlWhereBase() . "
              AND NOT ({$incobrable})
              AND {$vencido}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array(
                'documentos' => 0,
                'clientes' => 0,
                'facturas_guias' => 0,
                'letras' => 0,
                'otros' => 0,
                'total' => 0,
            );
        }

        return self::normalizarFilaProyeccionTipos($fila);
    }

    public static function mdlProyeccionPagosResumenIncobrable(array $filtros)
    {
        $incobrable = self::sqlEsIncobrable();
        $facturasGuias = self::sqlCaseFacturasGuias('cc');
        $letras = self::sqlCaseLetrasProyeccion('cc');
        $otros = self::sqlCaseOtrosProyeccion('cc');

        $sql = "SELECT
                COUNT(*) AS documentos,
                COUNT(DISTINCT cc.cliente) AS clientes,
                COALESCE(SUM({$facturasGuias}), 0) AS facturas_guias,
                COALESCE(SUM({$letras}), 0) AS letras,
                COALESCE(SUM({$otros}), 0) AS otros,
                COALESCE(SUM(cc.saldo), 0) AS total
            " . self::sqlFromBase() . "
            WHERE " . self::sqlWhereBase() . "
              AND {$incobrable}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array(
                'documentos' => 0,
                'clientes' => 0,
                'facturas_guias' => 0,
                'letras' => 0,
                'otros' => 0,
                'total' => 0,
            );
        }

        return self::normalizarFilaProyeccionTipos($fila);
    }

    public static function mdlProyeccionPagosMensual(array $filtros, $limiteMeses = 6)
    {
        $fechaSql = self::fechaCorteSql($filtros);
        $vencido = self::sqlEsVencido('cc', $fechaSql);
        $incobrable = self::sqlEsIncobrable();
        $limiteMeses = max(1, min(12, (int) $limiteMeses));
        $facturasGuias = self::sqlCaseFacturasGuias('cc');
        $letras = self::sqlCaseLetrasProyeccion('cc');
        $otros = self::sqlCaseOtrosProyeccion('cc');

        $sql = "SELECT
                YEAR(cc.fecha_ven) AS anio,
                MONTH(cc.fecha_ven) AS mes,
                DATE_FORMAT(cc.fecha_ven, '%Y-%m') AS periodo,
                MIN(cc.fecha_ven) AS fecha_min,
                MAX(cc.fecha_ven) AS fecha_max,
                COUNT(*) AS documentos,
                COUNT(DISTINCT cc.cliente) AS clientes,
                COALESCE(SUM({$facturasGuias}), 0) AS facturas_guias,
                COALESCE(SUM({$letras}), 0) AS letras,
                COALESCE(SUM({$otros}), 0) AS otros,
                COALESCE(SUM(cc.saldo), 0) AS total
            " . self::sqlFromBase() . "
            WHERE " . self::sqlWhereBase() . "
              AND NOT ({$incobrable})
              AND NOT {$vencido}
              AND cc.fecha_ven >= {$fechaSql}
              AND cc.fecha_ven <= LAST_DAY(DATE_ADD({$fechaSql}, INTERVAL " . ($limiteMeses - 1) . " MONTH))
            GROUP BY YEAR(cc.fecha_ven), MONTH(cc.fecha_ven)
            ORDER BY anio ASC, mes ASC
            LIMIT {$limiteMeses}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltrosComunes($stmt, $filtros);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila = array_merge($fila, self::normalizarFilaProyeccionTipos($fila));
        }
        unset($fila);

        return $filas;
    }
}
