<?php

require_once __DIR__ . "/conexion.php";

class ModeloDashboardDecisiones
{
    private static $vendedorFiltro = "";

    private static function filtroVendedorSql($alias = "t")
    {
        $sql = "EXISTS (
                    SELECT 1
                    FROM maestrajf m_dd
                    WHERE UPPER(m_dd.tipo_dato) = 'TVEND'
                      AND TRIM(m_dd.codigo) = TRIM($alias.vendedor)
                      AND m_dd.estado_decisiones = 1
                )";

        if (self::$vendedorFiltro !== "") {
            $codigo = str_replace("'", "''", self::$vendedorFiltro);
            $sql .= " AND TRIM($alias.vendedor) = '" . $codigo . "'";
        }

        return $sql;
    }

    public static function setVendedorFiltro($codigo)
    {
        self::$vendedorFiltro = trim((string) $codigo);
    }

    public static function getVendedorFiltro()
    {
        return self::$vendedorFiltro;
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
        $sql = "SELECT
                    codigo,
                    descripcion
                FROM maestrajf
                WHERE UPPER(tipo_dato) = 'TVEND'
                  AND estado_decisiones = 1
                ORDER BY codigo ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    t.op_gravada AS total,
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
        $filtro = self::filtroVendedorSql("t");

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
                WHERE t.estado NOT IN ('ANULADO', 'FACTURADO')
                  AND $filtro";

        $stmt = Conexion::conectar()->prepare($sql);
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
            "soles_estancados" => 0,
            "estancados_3d" => 0,
        );
    }

    public static function mdlPedidosEstancados()
    {
        $filtro = self::filtroVendedorSql("t");

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    t.estado,
                    t.op_gravada AS total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_sin_avance,
                    u.nombre AS usuario
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                LEFT JOIN usuariosjf u ON t.usuario = u.id
                WHERE t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                  AND DATEDIFF(CURDATE(), DATE(t.fecha)) >= 3
                  AND $filtro
                ORDER BY dias_sin_avance DESC, t.op_gravada DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlAlertasDecision()
    {
        $filtro = self::filtroVendedorSql("t");

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
                WHERE t.estado = 'GENERADO'
                  AND $filtro";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $base = $stmt->fetch(PDO::FETCH_ASSOC);

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
                    INNER JOIN clientesjf c ON t.cliente = c.codigo
                    WHERE t.estado = 'GENERADO'
                      AND $filtro
                      AND EXISTS (
                          SELECT 1
                          FROM cuenta_ctejf ct
                          WHERE ct.cliente = c.codigo
                            AND UPPER(ct.estado) = 'PENDIENTE'
                            AND IFNULL(ct.saldo, 0) > 0
                            AND ct.fecha_ven < CURDATE()
                      )";

        $stmtMora = Conexion::conectar()->prepare($sqlMora);
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
        $filtro = self::filtroVendedorSql("t");
        $limite = max(1, min(30, (int) $limite));

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.op_gravada AS total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_pendiente,
                    IFNULL((
                        SELECT SUM(IFNULL(ct.saldo, 0))
                        FROM cuenta_ctejf ct
                        WHERE ct.cliente = c.codigo
                          AND UPPER(ct.estado) = 'PENDIENTE'
                          AND IFNULL(ct.saldo, 0) > 0
                          AND ct.fecha_ven < CURDATE()
                    ), 0) AS deuda_vencida_cliente
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado = 'GENERADO'
                  AND $filtro
                ORDER BY dias_pendiente DESC, t.fecha ASC, t.codigo ASC
                LIMIT $limite";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidosGenerados($limite = 30)
    {
        $filtro = self::filtroVendedorSql("t");
        $limite = max(1, min(50, (int) $limite));

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    IFNULL(ven.descripcion, t.vendedor) AS nom_vendedor,
                    t.estado,
                    t.op_gravada AS total,
                    t.lista,
                    cv.descripcion AS condicion,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_pendiente,
                    u.nombre AS usuario,
                    IFNULL((
                        SELECT SUM(IFNULL(ct.saldo, 0))
                        FROM cuenta_ctejf ct
                        WHERE ct.cliente = c.codigo
                          AND UPPER(ct.estado) = 'PENDIENTE'
                          AND IFNULL(ct.saldo, 0) > 0
                          AND ct.fecha_ven < CURDATE()
                    ), 0) AS deuda_vencida_cliente,
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM cuenta_ctejf ct
                            WHERE ct.cliente = c.codigo
                              AND UPPER(ct.estado) = 'PENDIENTE'
                              AND IFNULL(ct.saldo, 0) > 0
                              AND ct.fecha_ven < CURDATE()
                        ) THEN 1
                        ELSE 0
                    END AS cliente_en_mora
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                LEFT JOIN condiciones_ventajf cv ON t.condicion_venta = cv.id
                LEFT JOIN usuariosjf u ON t.usuario = u.id
                LEFT JOIN (
                    SELECT codigo, descripcion
                    FROM maestrajf
                    WHERE tipo_dato = 'tvend'
                ) ven ON t.vendedor = ven.codigo
                WHERE t.estado = 'GENERADO'
                  AND $filtro
                ORDER BY t.fecha DESC, t.codigo DESC
                LIMIT $limite";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidosEnProceso($limite = 12)
    {
        $filtro = self::filtroVendedorSql("t");
        $limite = max(1, min(30, (int) $limite));

        $sql = "SELECT
                    t.codigo,
                    c.codigo AS cod_cli,
                    c.nombre AS cliente,
                    t.vendedor,
                    t.estado,
                    t.op_gravada AS total,
                    t.lista,
                    DATE(t.fecha) AS fecha,
                    DATEDIFF(CURDATE(), DATE(t.fecha)) AS dias_en_estado
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                  AND $filtro
                ORDER BY t.fecha DESC
                LIMIT $limite";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClientesConAtraso($limite = 12)
    {
        $filtroPedido = self::filtroVendedorSql("t");
        $filtroCartera = self::filtroVendedorSql("ct");
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
                    IFNULL((
                        SELECT SUM(IFNULL(ct.saldo, 0))
                        FROM cuenta_ctejf ct
                        WHERE ct.cliente = c.codigo
                          AND UPPER(ct.estado) = 'PENDIENTE'
                          AND IFNULL(ct.saldo, 0) > 0
                          AND ct.fecha_ven < CURDATE()
                          AND $filtroCartera
                    ), 0) AS deuda_vencida
                FROM temporaljf t
                INNER JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.estado IN ('GENERADO', 'APROBADO', 'APT', 'CONFIRMADO')
                  AND c.estado = 1
                  AND $filtroPedido
                GROUP BY c.codigo, c.nombre
                ORDER BY pedidos_generados DESC, deuda_vencida DESC, dias_pedido DESC
                LIMIT $limite";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenCartera()
    {
        $filtro = self::filtroVendedorSql("ct");

        $sql = "SELECT
                    COUNT(DISTINCT c.codigo) AS clientes_con_deuda,
                    SUM(
                        CASE
                            WHEN UPPER(ct.estado) = 'PENDIENTE'
                                AND IFNULL(ct.saldo, 0) > 0
                                THEN IFNULL(ct.saldo, 0)
                            ELSE 0
                        END
                    ) AS deuda_total,
                    SUM(
                        CASE
                            WHEN UPPER(ct.estado) = 'PENDIENTE'
                                AND IFNULL(ct.saldo, 0) > 0
                                AND ct.fecha_ven < CURDATE()
                                THEN IFNULL(ct.saldo, 0)
                            ELSE 0
                        END
                    ) AS deuda_vencida,
                    COUNT(DISTINCT
                        CASE
                            WHEN UPPER(ct.estado) = 'PENDIENTE'
                                AND IFNULL(ct.saldo, 0) > 0
                                AND ct.fecha_ven < CURDATE()
                                THEN c.codigo
                        END
                    ) AS clientes_vencidos
                FROM clientesjf c
                INNER JOIN cuenta_ctejf ct ON ct.cliente = c.codigo
                WHERE c.estado = 1
                  AND $filtro";

        $stmt = Conexion::conectar()->prepare($sql);
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
        return "UPPER(TRIM({$alias}.tipo)) IN ('S02', 'S03', 'S70')";
    }

    public static function mdlResumenFacturadoMes()
    {
        $rango = self::rangoMesActual();
        $filtro = self::filtroVendedorSql("v");

        $sql = "SELECT
                    COUNT(*) AS docs,
                    SUM(IFNULL(v.neto, 0)) AS soles
                FROM ventajf v
                WHERE v.fecha >= :fecha_ini
                  AND v.fecha < :fecha_fin
                  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND " . self::sqlTiposVentaFacturada("v") . "
                  AND $filtro";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindValue(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return array(
            "anio" => $rango["anio"],
            "mes" => $rango["mes"],
            "docs" => isset($row["docs"]) ? (int) $row["docs"] : 0,
            "soles" => isset($row["soles"]) ? (float) $row["soles"] : 0,
        );
    }

    public static function mdlFacturadoMes($limite = 40)
    {
        $rango = self::rangoMesActual();
        $filtro = self::filtroVendedorSql("v");
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
                    TRIM(v.vendedor) AS vendedor,
                    IFNULL(ven.descripcion, v.vendedor) AS nom_vendedor,
                    cv.descripcion AS condicion
                FROM ventajf v
                LEFT JOIN clientesjf c ON v.cliente = c.codigo
                LEFT JOIN condiciones_ventajf cv ON v.condicion_venta = cv.id
                LEFT JOIN (
                    SELECT codigo, descripcion
                    FROM maestrajf
                    WHERE tipo_dato = 'tvend'
                ) ven ON TRIM(v.vendedor) = TRIM(ven.codigo)
                WHERE v.fecha >= :fecha_ini
                  AND v.fecha < :fecha_fin
                  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                  AND " . self::sqlTiposVentaFacturada("v") . "
                  AND $filtro
                ORDER BY v.fecha DESC, v.documento DESC
                LIMIT $limite";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindValue(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
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
                    c.nombre AS cliente
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
}
