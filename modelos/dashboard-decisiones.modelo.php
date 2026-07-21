<?php

require_once __DIR__ . "/conexion.php";

class ModeloDashboardDecisiones
{
    private static $vendedorFiltro = "";
    private static $vendedoresCache = null;
    private static $bindSeq = 0;

    public static function setVendedorFiltro($codigo)
    {
        self::$vendedorFiltro = trim((string) $codigo);
    }

    public static function getVendedorFiltro()
    {
        return self::$vendedorFiltro;
    }

    /**
     * JOIN a vendedores activos del Centro de Decisiones.
     * Solo cuando no hay vendedor seleccionado (el filtro puntual va en WHERE).
     */
    private static function sqlJoinVendedoresActivos($aliasTabla, $aliasMaestra)
    {
        if (self::$vendedorFiltro !== "") {
            return "";
        }

        return " INNER JOIN maestrajf {$aliasMaestra}
                    ON {$aliasMaestra}.codigo = {$aliasTabla}.vendedor
                   AND {$aliasMaestra}.tipo_dato = 'TVEND'
                   AND {$aliasMaestra}.estado_decisiones = 1 ";
    }

    /**
     * Condición indexable de vendedor seleccionado (parámetro preparado).
     */
    private static function sqlCondicionVendedor($aliasTabla, array &$params)
    {
        if (self::$vendedorFiltro === "") {
            return "1 = 1";
        }

        self::$bindSeq++;
        $key = ":dd_vendedor_" . self::$bindSeq;
        $params[$key] = self::$vendedorFiltro;

        return "{$aliasTabla}.vendedor = {$key}";
    }

    private static function bindParams($stmt, array $params)
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    /**
     * Subconsulta de deuda vencida por cliente, con el mismo filtro de vendedor del dashboard.
     */
    private static function sqlSubqueryDeudaVencida($exprCliente, array &$params, $aliasMv)
    {
        $join = self::sqlJoinVendedoresActivos("ct", $aliasMv);
        $whereVend = self::sqlCondicionVendedor("ct", $params);

        return "IFNULL((
                    SELECT SUM(ct.saldo)
                    FROM cuenta_ctejf ct
                    {$join}
                    WHERE ct.cliente = {$exprCliente}
                      AND ct.estado = 'PENDIENTE'
                      AND ct.saldo > 0
                      AND ct.fecha_ven < CURDATE()
                      AND {$whereVend}
                ), 0)";
    }

    public static function normalizarVendedorFiltro($codigo)
    {
        $codigo = trim((string) $codigo);

        if ($codigo === "") {
            return "";
        }

        foreach (self::mdlVendedoresPermitidos() as $vendedor) {
            if ((string) $vendedor["codigo"] === $codigo) {
                return $codigo;
            }
        }

        return "";
    }

    public static function mdlVendedoresPermitidos()
    {
        if (self::$vendedoresCache !== null) {
            return self::$vendedoresCache;
        }

        $sql = "SELECT
                    codigo,
                    descripcion
                FROM maestrajf
                WHERE tipo_dato = 'TVEND'
                  AND estado_decisiones = 1
                ORDER BY codigo ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        self::$vendedoresCache = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return self::$vendedoresCache;
    }

    public static function mdlPedidoMini($codigoPedido, $codigoCliente = "")
    {
        $codigoPedido = trim((string) $codigoPedido);

        if ($codigoPedido === "") {
            return null;
        }

        $sql = "SELECT
                    t.codigo,
                    t.estado,
                    t.total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_pendiente
                FROM temporaljf t
                WHERE t.codigo = :codigo";

        if ($codigoCliente !== "") {
            $sql .= " AND t.cliente = :cliente";
        }

        $sql .= " LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":codigo", $codigoPedido, PDO::PARAM_STR);

        if ($codigoCliente !== "") {
            $stmt->bindValue(":cliente", trim((string) $codigoCliente), PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $lista = isset($row["lista"]) ? (string) $row["lista"] : "";
        $row["simbolo"] = ($lista === "precio1") ? "$ " : "S/ ";
        $row["total"] = round((float) $row["total"], 2);

        return $row;
    }

    public static function mdlResumenPedidos()
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_ped");
        $whereVend = self::sqlCondicionVendedor("t", $params);

        $sql = "SELECT
                    COUNT(*) AS total_activos,
                    SUM(CASE WHEN t.estado = 'GENERADO' THEN 1 ELSE 0 END) AS generados,
                    SUM(CASE WHEN t.estado = 'APROBADO' THEN 1 ELSE 0 END) AS aprobados,
                    SUM(CASE WHEN t.estado = 'APT' THEN 1 ELSE 0 END) AS apt,
                    SUM(CASE WHEN t.estado = 'CONFIRMADO' THEN 1 ELSE 0 END) AS confirmados,
                    SUM(
                        CASE
                            WHEN t.estado = 'GENERADO' AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_generados,
                    SUM(
                        CASE
                            WHEN t.estado = 'APROBADO' AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_aprobados,
                    SUM(
                        CASE
                            WHEN t.estado = 'APT' AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_apt,
                    SUM(
                        CASE
                            WHEN t.estado = 'CONFIRMADO' AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_confirmados,
                    SUM(
                        CASE
                            WHEN COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_pipeline,
                    SUM(
                        CASE
                            WHEN t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                                AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_post_aprobacion,
                    SUM(
                        CASE
                            WHEN t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                                THEN 1
                            ELSE 0
                        END
                    ) AS post_aprobacion_total,
                    SUM(
                        CASE
                            WHEN t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                                AND DATEDIFF(CURDATE(), DATE(t.fecha)) >= 3
                                AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_estancados,
                    SUM(
                        CASE
                            WHEN t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                                AND DATEDIFF(CURDATE(), DATE(t.fecha)) >= 3
                                THEN 1
                            ELSE 0
                        END
                    ) AS estancados_3d
                FROM temporaljf t
                {$join}
                WHERE t.estado NOT IN ('ANULADO', 'FACTURADO')
                  AND {$whereVend}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: array(
            "total_activos" => 0,
            "generados" => 0,
            "aprobados" => 0,
            "apt" => 0,
            "confirmados" => 0,
            "soles_generados" => 0,
            "soles_aprobados" => 0,
            "soles_apt" => 0,
            "soles_confirmados" => 0,
            "soles_pipeline" => 0,
            "soles_post_aprobacion" => 0,
            "post_aprobacion_total" => 0,
            "soles_estancados" => 0,
            "estancados_3d" => 0,
        );
    }

    public static function mdlPedidosEstancados()
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_est");
        $whereVend = self::sqlCondicionVendedor("t", $params);

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    t.estado,
                    t.total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_sin_avance,
                    u.nombre AS usuario,
                    IFNULL(cov.pct_completo, 0) AS pct_completo
                FROM temporaljf t
                {$join}
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                LEFT JOIN usuariosjf u ON t.usuario = u.id
                LEFT JOIN (
                    SELECT
                        dt.codigo,
                        ROUND(
                            SUM(
                                LEAST(
                                    IFNULL(dt.cantidad, 0),
                                    GREATEST(IFNULL(a.stock, 0), 0)
                                )
                            ) / NULLIF(SUM(IFNULL(dt.cantidad, 0)), 0) * 100,
                            0
                        ) AS pct_completo
                    FROM detalle_temporal dt
                    INNER JOIN articulojf a ON a.articulo = dt.articulo
                    GROUP BY dt.codigo
                ) cov ON cov.codigo = t.codigo
                WHERE t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                  AND {$whereVend}
                ORDER BY dias_sin_avance ASC, t.op_gravada DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlAlertasDecision()
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_al");
        $whereVend = self::sqlCondicionVendedor("t", $params);

        $sql = "SELECT
                    COUNT(*) AS generados_total,
                    SUM(
                        CASE
                            WHEN COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS generados_soles,
                    SUM(
                        CASE
                            WHEN DATEDIFF(CURDATE(), DATE(t.fecha)) >= 2 THEN 1
                            ELSE 0
                        END
                    ) AS generados_antiguos,
                    SUM(
                        CASE
                            WHEN DATEDIFF(CURDATE(), DATE(t.fecha)) >= 2
                                AND COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS generados_antiguos_soles
                FROM temporaljf t
                {$join}
                WHERE t.estado = 'GENERADO'
                  AND {$whereVend}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();
        $base = $stmt->fetch(PDO::FETCH_ASSOC);

        $paramsMora = array();
        $joinMora = self::sqlJoinVendedoresActivos("t", "mv_am");
        $whereVendMora = self::sqlCondicionVendedor("t", $paramsMora);

        $sqlMora = "SELECT
                        COUNT(*) AS generados_mora,
                        SUM(
                            CASE
                                WHEN COALESCE(t.lista, '') <> 'precio1'
                                    THEN IFNULL(t.op_gravada, 0)
                                ELSE 0
                            END
                        ) AS generados_mora_soles
                    FROM temporaljf t
                    {$joinMora}
                    INNER JOIN clientesjf c ON t.cliente = c.codigo
                    WHERE t.estado = 'GENERADO'
                      AND {$whereVendMora}
                      AND EXISTS (
                          SELECT 1
                          FROM cuenta_ctejf ct
                          WHERE ct.cliente = c.codigo
                            AND ct.estado = 'PENDIENTE'
                            AND ct.saldo > 0
                            AND ct.fecha_ven < CURDATE()
                      )";

        $stmtMora = Conexion::conectar()->prepare($sqlMora);
        self::bindParams($stmtMora, $paramsMora);
        $stmtMora->execute();
        $mora = $stmtMora->fetch(PDO::FETCH_ASSOC);

        return array(
            "generados_total" => isset($base["generados_total"]) ? (int) $base["generados_total"] : 0,
            "generados_soles" => isset($base["generados_soles"]) ? (float) $base["generados_soles"] : 0,
            "generados_antiguos" => isset($base["generados_antiguos"]) ? (int) $base["generados_antiguos"] : 0,
            "generados_antiguos_soles" => isset($base["generados_antiguos_soles"]) ? (float) $base["generados_antiguos_soles"] : 0,
            "generados_mora" => isset($mora["generados_mora"]) ? (int) $mora["generados_mora"] : 0,
            "generados_mora_soles" => isset($mora["generados_mora_soles"]) ? (float) $mora["generados_mora_soles"] : 0,
        );
    }

    public static function mdlTopGeneradosPendientes($limite = 20)
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_top");
        $whereVend = self::sqlCondicionVendedor("t", $params);
        $deudaSql = self::sqlSubqueryDeudaVencida("c.codigo", $params, "mv_topd");
        $limite = max(1, min(30, (int) $limite));

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_pendiente,
                    {$deudaSql} AS deuda_vencida_cliente
                FROM temporaljf t
                {$join}
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado = 'GENERADO'
                  AND {$whereVend}
                ORDER BY dias_pendiente DESC, t.fecha ASC, t.codigo ASC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidosGenerados($limite = 30)
    {
        return self::mdlPedidosPorEstados(array("GENERADO"), $limite);
    }

    /**
     * Pedidos del pipeline de crédito por uno o más estados (GENERADO, APROBADO, …).
     */
    public static function mdlPedidosPorEstados(array $estados, $limite = 50)
    {
        $estadosLimpios = array();
        foreach ($estados as $estado) {
            $estado = strtoupper(trim((string) $estado));
            if ($estado !== "" && preg_match('/^[A-Z_]+$/', $estado)) {
                $estadosLimpios[$estado] = true;
            }
        }
        $estadosLimpios = array_keys($estadosLimpios);

        if (empty($estadosLimpios)) {
            return array();
        }

        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_gen");
        $whereVend = self::sqlCondicionVendedor("t", $params);
        $deudaSql = self::sqlSubqueryDeudaVencida("c.codigo", $params, "mv_gend");
        $limite = max(1, min(100, (int) $limite));

        $placeholders = array();
        foreach ($estadosLimpios as $i => $estado) {
            $key = ":estado_cola_" . $i;
            $placeholders[] = $key;
            $params[$key] = $estado;
        }

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    IFNULL(ven.descripcion, t.vendedor) AS nom_vendedor,
                    t.estado,
                    t.total,
                    t.lista,
                    t.condicion_venta,
                    cv.codigo AS condicion_codigo,
                    cv.descripcion AS condicion,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_pendiente,
                    u.nombre AS usuario,
                    {$deudaSql} AS deuda_vencida_cliente,
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM cuenta_ctejf ct
                            WHERE ct.cliente = c.codigo
                              AND ct.estado = 'PENDIENTE'
                              AND ct.saldo > 0
                              AND ct.fecha_ven < CURDATE()
                        ) THEN 1
                        ELSE 0
                    END AS cliente_en_mora
                FROM temporaljf t
                {$join}
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                LEFT JOIN condiciones_ventajf cv ON t.condicion_venta = cv.id
                LEFT JOIN usuariosjf u ON t.usuario = u.id
                LEFT JOIN maestrajf ven
                    ON ven.codigo = t.vendedor
                   AND ven.tipo_dato = 'TVEND'
                WHERE t.estado IN (" . implode(", ", $placeholders) . ")
                  AND {$whereVend}
                ORDER BY
                    CASE t.estado
                        WHEN 'GENERADO' THEN 0
                        WHEN 'APROBADO' THEN 1
                        ELSE 2
                    END,
                    t.fecha DESC,
                    t.codigo DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidosEnProceso($limite = 12)
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_pro");
        $whereVend = self::sqlCondicionVendedor("t", $params);
        $limite = max(1, min(30, (int) $limite));

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    t.estado,
                    t.total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_en_estado
                FROM temporaljf t
                {$join}
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                  AND {$whereVend}
                ORDER BY t.fecha DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClientesConAtraso($limite = 12)
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_atr");
        $whereVend = self::sqlCondicionVendedor("t", $params);
        $deudaSql = self::sqlSubqueryDeudaVencida("c.codigo", $params, "mv_atrd");
        $limite = max(1, min(30, (int) $limite));

        $sql = "SELECT
                    c.codigo,
                    c.nombre,
                    COUNT(DISTINCT t.codigo) AS pedidos_activos,
                    SUM(CASE WHEN t.estado = 'GENERADO' THEN 1 ELSE 0 END) AS pedidos_generados,
                    MAX(DATEDIFF(CURDATE(), DATE(t.fecha))) AS dias_pedido,
                    SUM(
                        CASE
                            WHEN COALESCE(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.op_gravada, 0)
                            ELSE 0
                        END
                    ) AS soles_pipeline,
                    {$deudaSql} AS deuda_vencida
                FROM temporaljf t
                {$join}
                INNER JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado IN ('GENERADO', 'APROBADO', 'APT', 'CONFIRMADO')
                  AND c.estado = 1
                  AND {$whereVend}
                GROUP BY c.codigo, c.nombre
                ORDER BY pedidos_generados DESC, deuda_vencida DESC, dias_pedido DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen de cartera optimizado:
     * - Parte desde cuenta_ctejf filtrando PENDIENTE + saldo > 0.
     * - JOIN a vendedores activos (o igualdad por vendedor seleccionado).
     * - Conserva deuda_total, deuda_vencida y clientes_vencidos.
     */
    public static function mdlResumenCartera()
    {
        $params = array();
        $joinVend = self::sqlJoinVendedoresActivos("ct", "mv_car");
        $whereVend = self::sqlCondicionVendedor("ct", $params);

        $sql = "SELECT
                    COUNT(DISTINCT ct.cliente) AS clientes_con_deuda,
                    SUM(ct.saldo) AS deuda_total,
                    SUM(
                        CASE
                            WHEN ct.fecha_ven < CURDATE() THEN ct.saldo
                            ELSE 0
                        END
                    ) AS deuda_vencida,
                    COUNT(DISTINCT
                        CASE
                            WHEN ct.fecha_ven < CURDATE() THEN ct.cliente
                        END
                    ) AS clientes_vencidos
                FROM cuenta_ctejf ct
                {$joinVend}
                INNER JOIN clientesjf c
                    ON c.codigo = ct.cliente
                   AND c.estado = 1
                WHERE ct.estado = 'PENDIENTE'
                  AND ct.saldo > 0
                  AND {$whereVend}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: array(
            "clientes_con_deuda" => 0,
            "deuda_total" => 0,
            "deuda_vencida" => 0,
            "clientes_vencidos" => 0,
        );
    }

    public static function mdlPedidosRecientes($limite = 8)
    {
        return self::mdlPedidosEnProceso($limite);
    }

    private static function rangoMesActual()
    {
        date_default_timezone_set("America/Lima");

        $anio = (int) date("Y");
        $mes = (int) date("n");
        $inicio = sprintf("%04d-%02d-01", $anio, $mes);

        if ($mes === 12) {
            $fin = sprintf("%04d-01-01", $anio + 1);
        } else {
            $fin = sprintf("%04d-%02d-01", $anio, $mes + 1);
        }

        return array(
            "anio" => $anio,
            "mes" => $mes,
            "inicio" => $inicio,
            "fin" => $fin,
        );
    }

    private static function sqlTiposVentaFacturada($alias = "v")
    {
        return "{$alias}.tipo IN ('S02', 'S03', 'S70')";
    }

    public static function mdlResumenFacturadoMes()
    {
        $rango = self::rangoMesActual();
        $params = array(
            ":fecha_ini" => $rango["inicio"],
            ":fecha_fin" => $rango["fin"],
        );
        $join = self::sqlJoinVendedoresActivos("v", "mv_fac");
        $whereVend = self::sqlCondicionVendedor("v", $params);

        $sql = "SELECT
                    COUNT(*) AS docs,
                    SUM(IFNULL(v.neto, 0)) AS soles,
                    SUM(
                        CASE
                            WHEN v.tipo = 'S70' THEN IFNULL(v.neto, 0)
                            ELSE 0
                        END
                    ) AS soles_proformas,
                    SUM(
                        CASE
                            WHEN v.tipo = 'S70' THEN 1
                            ELSE 0
                        END
                    ) AS docs_proformas
                FROM ventajf v
                {$join}
                WHERE v.fecha >= :fecha_ini
                  AND v.fecha < :fecha_fin
                  AND (v.estado IS NULL OR v.estado <> 'ANULADO')
                  AND " . self::sqlTiposVentaFacturada("v") . "
                  AND {$whereVend}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $soles = isset($row["soles"]) ? (float) $row["soles"] : 0;
        $solesProformas = isset($row["soles_proformas"]) ? (float) $row["soles_proformas"] : 0;
        $pctProformas = ($soles > 0) ? round(($solesProformas / $soles) * 100, 1) : 0.0;

        return array(
            "anio" => $rango["anio"],
            "mes" => $rango["mes"],
            "docs" => isset($row["docs"]) ? (int) $row["docs"] : 0,
            "soles" => $soles,
            "soles_proformas" => round($solesProformas, 2),
            "docs_proformas" => isset($row["docs_proformas"]) ? (int) $row["docs_proformas"] : 0,
            "pct_proformas" => $pctProformas,
        );
    }

    public static function mdlFacturadoMes($limite = 40)
    {
        $rango = self::rangoMesActual();
        $params = array(
            ":fecha_ini" => $rango["inicio"],
            ":fecha_fin" => $rango["fin"],
        );
        $join = self::sqlJoinVendedoresActivos("v", "mv_fcl");
        $whereVend = self::sqlCondicionVendedor("v", $params);
        $limite = max(1, min(100, (int) $limite));

        $sql = "SELECT
                    v.tipo,
                    v.documento,
                    v.tipo_documento,
                    IFNULL(v.neto, 0) AS neto,
                    v.lista_precios AS lista,
                    DATE(v.fecha) AS fecha,
                    v.cliente AS cod_cli,
                    c.nombre AS cliente,
                    v.vendedor AS vendedor,
                    IFNULL(ven.descripcion, v.vendedor) AS nom_vendedor,
                    cv.descripcion AS condicion
                FROM ventajf v
                {$join}
                LEFT JOIN clientesjf c ON v.cliente = c.codigo
                LEFT JOIN condiciones_ventajf cv ON v.condicion_venta = cv.id
                LEFT JOIN maestrajf ven
                    ON ven.codigo = v.vendedor
                   AND ven.tipo_dato = 'TVEND'
                WHERE v.fecha >= :fecha_ini
                  AND v.fecha < :fecha_fin
                  AND (v.estado IS NULL OR v.estado <> 'ANULADO')
                  AND " . self::sqlTiposVentaFacturada("v") . "
                  AND {$whereVend}
                ORDER BY v.fecha DESC, v.documento DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidoParaAnular($codigoPedido)
    {
        $codigoPedido = trim((string) $codigoPedido);

        if ($codigoPedido === "") {
            return null;
        }

        $sql = "SELECT
                    t.codigo,
                    t.estado,
                    t.cliente AS cod_cli,
                    c.nombre AS cliente,
                    IFNULL(t.total, t.op_gravada) AS total,
                    t.lista
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.codigo = :codigo
                LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":codigo", $codigoPedido, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function mdlAnularPedidoGenerado($codigoPedido, $usuarioId)
    {
        $codigoPedido = trim((string) $codigoPedido);
        $usuarioId = (int) $usuarioId;

        if ($codigoPedido === "" || $usuarioId <= 0) {
            return false;
        }

        $sql = "UPDATE temporaljf
                SET estado = 'ANULADO',
                    usuario_estado = :usuario
                WHERE codigo = :codigo
                  AND estado = 'GENERADO'";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":usuario", $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(":codigo", $codigoPedido, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    public static function mdlAprobarPedidoGenerado($codigoPedido, $usuarioId)
    {
        $codigoPedido = trim((string) $codigoPedido);
        $usuarioId = (int) $usuarioId;

        if ($codigoPedido === "" || $usuarioId <= 0) {
            return false;
        }

        $sql = "UPDATE temporaljf
                SET estado = 'APROBADO',
                    usuario_estado = :usuario
                WHERE codigo = :codigo
                  AND estado = 'GENERADO'";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":usuario", $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(":codigo", $codigoPedido, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Artículos en pedidos post-aprobación sin stock suficiente o descontinuados.
     * Excluye GENERADO y CONFIRMADO (pedido cerrado). Solo vendedores activos del Centro.
     */
    public static function mdlArticulosEnRiesgo($limite = 50)
    {
        $params = array();
        $join = self::sqlJoinVendedoresActivos("t", "mv_art");
        $whereVend = self::sqlCondicionVendedor("t", $params);
        $limite = max(1, min(100, (int) $limite));

        $sql = "SELECT
                    a.articulo,
                    IFNULL(a.nombre, '') AS nombre,
                    IFNULL(a.modelo, '') AS modelo,
                    IFNULL(a.color, '') AS color,
                    IFNULL(a.talla, '') AS talla,
                    GREATEST(IFNULL(a.stock, 0), 0) AS stock,
                    SUM(IFNULL(dt.cantidad, 0)) AS cant_pedida,
                    GREATEST(
                        SUM(IFNULL(dt.cantidad, 0)) - GREATEST(IFNULL(a.stock, 0), 0),
                        0
                    ) AS faltante,
                    IFNULL(a.estado, '') AS estado,
                    CASE
                        WHEN LOWER(IFNULL(a.estado, '')) = 'descontinuado'
                             AND SUM(IFNULL(dt.cantidad, 0)) > GREATEST(IFNULL(a.stock, 0), 0)
                            THEN 'ambos'
                        WHEN LOWER(IFNULL(a.estado, '')) = 'descontinuado'
                            THEN 'descontinuado'
                        ELSE 'sin_stock'
                    END AS alerta,
                    COUNT(DISTINCT t.codigo) AS n_pedidos,
                    GROUP_CONCAT(DISTINCT t.estado ORDER BY t.estado SEPARATOR ', ') AS estados,
                    GROUP_CONCAT(DISTINCT t.codigo ORDER BY t.codigo SEPARATOR ', ') AS pedidos
                FROM detalle_temporal dt
                INNER JOIN temporaljf t ON t.codigo = dt.codigo
                INNER JOIN articulojf a ON a.articulo = dt.articulo
                {$join}
                WHERE t.estado IN ('APROBADO', 'APT')
                  AND {$whereVend}
                GROUP BY
                    a.articulo,
                    a.nombre,
                    a.modelo,
                    a.color,
                    a.talla,
                    a.stock,
                    a.estado
                HAVING LOWER(IFNULL(a.estado, '')) = 'descontinuado'
                    OR SUM(IFNULL(dt.cantidad, 0)) > GREATEST(IFNULL(a.stock, 0), 0)
                ORDER BY
                    CASE
                        WHEN LOWER(IFNULL(a.estado, '')) = 'descontinuado'
                             AND SUM(IFNULL(dt.cantidad, 0)) > GREATEST(IFNULL(a.stock, 0), 0)
                            THEN 1
                        WHEN LOWER(IFNULL(a.estado, '')) = 'descontinuado'
                            THEN 2
                        ELSE 3
                    END,
                    faltante DESC,
                    cant_pedida DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
