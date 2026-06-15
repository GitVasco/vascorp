<?php

require_once "conexion.php";

class ModeloVascoSync
{
    static $TABLA_CLIENTES = "clientesjf";

    static $TABLA_CUENTAS = "cuenta_ctejf";

    static $MAX_DOCS_PENDIENTES_CLIENTE = 500;

    static $DOC_TYPES_VALIDOS = array("0", "1", "4", "6", "7", "A", "B");

    public static function normalizarDocumento($documento)
    {
        $valor = strtoupper(trim((string) $documento));
        $valor = preg_replace('/\s+/', '', $valor);

        return $valor !== null ? $valor : "";
    }

    public static function mdlResumenClientes()
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 1 AND fecha IS NOT NULL THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN NOT (estado = 1 AND fecha IS NOT NULL) THEN 1 ELSE 0 END) AS inactivos,
                    SUM(CASE WHEN TRIM(IFNULL(documento, '')) = '' THEN 1 ELSE 0 END) AS sin_documento,
                    SUM(CASE WHEN TRIM(IFNULL(documento, '')) <> '' AND TRIM(IFNULL(tipo_documento, '')) = '' THEN 1 ELSE 0 END) AS sin_tipo_documento
                FROM " . self::$TABLA_CLIENTES;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : array();
    }

    public static function mdlClientesTipoDocumentoInvalido($limite = 100)
    {
        $placeholders = array();
        foreach (self::$DOC_TYPES_VALIDOS as $tipo) {
            $placeholders[] = "'" . $tipo . "'";
        }

        $sql = "SELECT
                    id,
                    codigo,
                    nombre,
                    tipo_documento,
                    documento,
                    estado,
                    vendedor,
                    fecreg
                FROM " . self::$TABLA_CLIENTES . "
                WHERE TRIM(IFNULL(documento, '')) <> ''
                  AND UPPER(TRIM(IFNULL(tipo_documento, ''))) NOT IN (" . implode(", ", $placeholders) . ")
                ORDER BY codigo ASC
                LIMIT " . intval($limite);

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClientesSinDocumento($limite = 100)
    {
        $sql = "SELECT
                    id,
                    codigo,
                    nombre,
                    tipo_documento,
                    documento,
                    estado,
                    vendedor,
                    fecreg
                FROM " . self::$TABLA_CLIENTES . "
                WHERE TRIM(IFNULL(documento, '')) = ''
                ORDER BY codigo ASC
                LIMIT " . intval($limite);

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClientesDuplicadosDocumento()
    {
        $sql = "SELECT
                    c.id,
                    c.codigo,
                    c.nombre,
                    c.tipo_documento,
                    c.documento,
                    c.estado,
                    c.vendedor,
                    c.grupo,
                    c.fecreg,
                    c.fecha,
                    g.doc_key,
                    g.cantidad
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (
                    SELECT
                        CONCAT(
                            UPPER(TRIM(IFNULL(tipo_documento, ''))),
                            ':',
                            UPPER(REPLACE(REPLACE(TRIM(IFNULL(documento, '')), ' ', ''), '-', ''))
                        ) AS doc_key,
                        COUNT(*) AS cantidad
                    FROM " . self::$TABLA_CLIENTES . "
                    WHERE TRIM(IFNULL(documento, '')) <> ''
                      AND TRIM(IFNULL(tipo_documento, '')) <> ''
                    GROUP BY doc_key
                    HAVING COUNT(*) > 1
                ) g ON CONCAT(
                    UPPER(TRIM(IFNULL(c.tipo_documento, ''))),
                    ':',
                    UPPER(REPLACE(REPLACE(TRIM(IFNULL(c.documento, '')), ' ', ''), '-', ''))
                ) = g.doc_key
                ORDER BY g.doc_key ASC, c.estado DESC, c.fecreg DESC, c.id ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentos únicos con tipo y número válidos (activos e inactivos).
     */
    public static function mdlContarDocumentosUnicosListos()
    {
        $sql = "SELECT COUNT(*) AS listos
                FROM (
                    SELECT 1
                    FROM " . self::$TABLA_CLIENTES . "
                    WHERE " . self::sqlFiltroDocValido() . "
                    GROUP BY " . self::sqlDocKey() . "
                ) AS docs_unicos";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["listos"]) ? (int) $fila["listos"] : 0;
    }

    /**
     * Clientes que no pueden enviarse (sin documento o tipo inválido).
     */
    public static function mdlContarBloqueadosEnvio()
    {
        $placeholders = array();
        foreach (self::$DOC_TYPES_VALIDOS as $tipo) {
            $placeholders[] = "'" . $tipo . "'";
        }

        $sql = "SELECT COUNT(*) AS bloqueados
                FROM " . self::$TABLA_CLIENTES . "
                WHERE TRIM(IFNULL(documento, '')) = ''
                   OR UPPER(TRIM(IFNULL(tipo_documento, ''))) NOT IN (" . implode(", ", $placeholders) . ")";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["bloqueados"]) ? (int) $fila["bloqueados"] : 0;
    }

    public static function mdlContarTipoDocumentoInvalido()
    {
        $placeholders = array();
        foreach (self::$DOC_TYPES_VALIDOS as $tipo) {
            $placeholders[] = "'" . $tipo . "'";
        }

        $sql = "SELECT COUNT(*) AS total
                FROM " . self::$TABLA_CLIENTES . "
                WHERE TRIM(IFNULL(documento, '')) <> ''
                  AND UPPER(TRIM(IFNULL(tipo_documento, ''))) NOT IN (" . implode(", ", $placeholders) . ")";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    /**
     * Un registro por documento (consolidado). Orden estable por id.
     *
     * @param int $offset
     * @param int $limite
     * @return array
     */
    public static function mdlClientesParaSyncLote($offset, $limite)
    {
        $offset = (int) $offset;
        $limite = (int) $limite;

        if ($limite <= 0) {
            return array();
        }

        $sql = "SELECT
                    c.id,
                    c.codigo,
                    c.nombre,
                    c.tipo_documento,
                    c.documento,
                    c.direccion,
                    c.ubigeo,
                    c.telefono,
                    c.email,
                    c.estado,
                    c.fecha
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (
                    SELECT
                        " . self::sqlDocKey() . " AS doc_key,
                        CAST(SUBSTRING_INDEX(GROUP_CONCAT(
                            id ORDER BY
                                CASE WHEN estado = 1 AND fecha IS NOT NULL THEN 0 ELSE 1 END ASC,
                                IFNULL(fecreg, '1970-01-01') DESC,
                                id ASC
                        ), ',', 1) AS UNSIGNED) AS id_elegido
                    FROM " . self::$TABLA_CLIENTES . "
                    WHERE " . self::sqlFiltroDocValido() . "
                    GROUP BY doc_key
                ) pick ON c.id = pick.id_elegido
                ORDER BY c.id ASC
                LIMIT " . $offset . ", " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function sqlDocKey($alias = "")
    {
        $p = $alias !== "" ? $alias . "." : "";

        return "CONCAT(
            UPPER(TRIM(IFNULL(" . $p . "tipo_documento, ''))),
            ':',
            UPPER(REPLACE(REPLACE(TRIM(IFNULL(" . $p . "documento, '')), ' ', ''), '-', ''))
        )";
    }

    private static function sqlFiltroDocValido($alias = "")
    {
        $p = $alias !== "" ? $alias . "." : "";
        $placeholders = array();

        foreach (self::$DOC_TYPES_VALIDOS as $tipo) {
            $placeholders[] = "'" . $tipo . "'";
        }

        return "TRIM(IFNULL(" . $p . "documento, '')) <> ''
            AND UPPER(TRIM(IFNULL(" . $p . "tipo_documento, ''))) IN (" . implode(", ", $placeholders) . ")";
    }

    /**
     * @deprecated Usar mdlContarDocumentosUnicosListos
     */
    public static function mdlContarDocumentosUnicosActivos()
    {
        return self::mdlContarDocumentosUnicosListos();
    }

    // ============================================
    // CUENTAS — cuenta_ctejf → account-statements-bulk
    // ============================================

    /**
     * Resumen global de cuentas listas para sync (con deuda y doc válido).
     *
     * @return array
     */
    public static function mdlCuentasResumenGlobal()
    {
        $sql = "SELECT
                    COUNT(*) AS clientes_con_deuda,
                    IFNULL(SUM(cant_docs), 0) AS docs_pendientes,
                    IFNULL(SUM(deuda_total), 0) AS deuda_total,
                    IFNULL(SUM(vencido_total), 0) AS vencido_total
                FROM (" . self::sqlSubqueryCuentasPorDocKey() . ") AS cuentas_listas";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : array();
    }

    public static function mdlCuentasContarConsolidados()
    {
        $sql = "SELECT COUNT(*) AS total
                FROM (" . self::sqlSubqueryCuentasPorDocKey() . ") AS t
                WHERE t.cant_codigos > 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    public static function mdlCuentasContarBloqueadosSinDoc()
    {
        $sql = "SELECT COUNT(DISTINCT cc.cliente) AS total
                FROM " . self::$TABLA_CUENTAS . " cc
                LEFT JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                  AND (
                    c.codigo IS NULL
                    OR TRIM(IFNULL(c.documento, '')) = ''
                    OR UPPER(TRIM(IFNULL(c.tipo_documento, ''))) NOT IN (" . self::sqlPlaceholdersDocTypes() . ")
                  )";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    public static function mdlCuentasContarBloqueadosExcesoDocs()
    {
        $sql = "SELECT COUNT(*) AS total
                FROM (
                    SELECT " . self::sqlDocKey("c") . " AS doc_key
                    FROM " . self::$TABLA_CUENTAS . " cc
                    INNER JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                    WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                      AND " . self::sqlFiltroDocValido("c") . "
                    GROUP BY doc_key
                    HAVING COUNT(*) > " . (int) self::$MAX_DOCS_PENDIENTES_CLIENTE . "
                ) AS exceso";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    public static function mdlCuentasBloqueadosSinDoc($limite = 50)
    {
        $limite = (int) $limite;

        $sql = "SELECT
                    cc.cliente AS codigo,
                    IFNULL(c.nombre, '') AS nombre,
                    IFNULL(c.tipo_documento, '') AS tipo_documento,
                    IFNULL(c.documento, '') AS documento,
                    COUNT(*) AS cant_docs,
                    SUM(cc.saldo) AS deuda
                FROM " . self::$TABLA_CUENTAS . " cc
                LEFT JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                  AND (
                    c.codigo IS NULL
                    OR TRIM(IFNULL(c.documento, '')) = ''
                    OR UPPER(TRIM(IFNULL(c.tipo_documento, ''))) NOT IN (" . self::sqlPlaceholdersDocTypes() . ")
                  )
                GROUP BY cc.cliente, c.nombre, c.tipo_documento, c.documento
                ORDER BY deuda DESC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCuentasBloqueadosExcesoDocs($limite = 50)
    {
        $limite = (int) $limite;

        $sql = "SELECT
                    t.doc_key,
                    t.tipo_documento,
                    t.documento,
                    t.nombre,
                    t.cant_docs,
                    t.deuda_total
                FROM (" . self::sqlSubqueryCuentasPorDocKey(false) . ") AS t
                WHERE t.cant_docs > " . (int) self::$MAX_DOCS_PENDIENTES_CLIENTE . "
                ORDER BY t.cant_docs DESC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCuentasMuestra($limite = 30)
    {
        $limite = (int) $limite;

        $sql = "SELECT
                    t.doc_key,
                    t.tipo_documento,
                    t.documento,
                    t.nombre,
                    t.deuda_total,
                    t.vencido_total,
                    t.cant_docs,
                    t.cant_codigos
                FROM (" . self::sqlSubqueryCuentasPorDocKey() . ") AS t
                ORDER BY t.deuda_total DESC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clientes con deuda consolidados por documento (listos para sync).
     *
     * @param int $offset
     * @param int $limite
     * @return array
     */
    public static function mdlCuentasParaSyncLote($offset, $limite)
    {
        $offset = (int) $offset;
        $limite = (int) $limite;

        if ($limite <= 0) {
            return array();
        }

        $sql = "SELECT
                    t.doc_key,
                    t.tipo_documento,
                    t.documento,
                    t.nombre,
                    t.deuda_total,
                    t.vencido_total,
                    t.cant_docs,
                    t.cant_codigos
                FROM (" . self::sqlSubqueryCuentasPorDocKey() . ") AS t
                ORDER BY t.doc_key ASC
                LIMIT " . $offset . ", " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentos pendientes de un cliente (por doc_key consolidado).
     *
     * @param string $docKey
     * @return array
     */
    public static function mdlCuentasDocsPendientesPorDocKey($docKey)
    {
        $docKey = trim((string) $docKey);

        if ($docKey === "") {
            return array();
        }

        $sql = "SELECT
                    cc.tipo_doc,
                    cc.num_cta,
                    MIN(cc.fecha) AS fecha,
                    MIN(cc.fecha_ven) AS fecha_ven,
                    MAX(cc.monto) AS monto,
                    MAX(cc.saldo) AS saldo
                FROM " . self::$TABLA_CUENTAS . " cc
                INNER JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                  AND " . self::sqlFiltroDocValido("c") . "
                  AND " . self::sqlDocKey("c") . " = :doc_key
                GROUP BY cc.tipo_doc, cc.num_cta
                ORDER BY cc.fecha ASC, cc.num_cta ASC
                LIMIT " . (int) self::$MAX_DOCS_PENDIENTES_CLIENTE;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":doc_key", $docKey, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentos pendientes de varios clientes en una sola consulta (por lote).
     *
     * @param array $docKeys
     * @return array doc_key => listado de filas
     */
    public static function mdlCuentasDocsPendientesPorDocKeys($docKeys)
    {
        $docKeys = array_values(array_filter(array_map("trim", $docKeys)));

        if (empty($docKeys)) {
            return array();
        }

        $placeholders = array();
        foreach ($docKeys as $i => $key) {
            $placeholders[] = ":dk" . $i;
        }

        $sql = "SELECT
                    " . self::sqlDocKey("c") . " AS doc_key,
                    cc.tipo_doc,
                    cc.num_cta,
                    MIN(cc.fecha) AS fecha,
                    MIN(cc.fecha_ven) AS fecha_ven,
                    MAX(cc.monto) AS monto,
                    MAX(cc.saldo) AS saldo
                FROM " . self::$TABLA_CUENTAS . " cc
                INNER JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                  AND " . self::sqlFiltroDocValido("c") . "
                  AND " . self::sqlDocKey("c") . " IN (" . implode(", ", $placeholders) . ")
                GROUP BY doc_key, cc.tipo_doc, cc.num_cta
                ORDER BY doc_key ASC, cc.num_cta ASC";

        $stmt = Conexion::conectar()->prepare($sql);

        foreach ($docKeys as $i => $key) {
            $stmt->bindValue(":dk" . $i, $key, PDO::PARAM_STR);
        }

        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $agrupado = array();

        foreach ($filas as $fila) {
            $dk = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            if ($dk === "") {
                continue;
            }
            if (!isset($agrupado[$dk])) {
                $agrupado[$dk] = array();
            }
            if (count($agrupado[$dk]) >= self::$MAX_DOCS_PENDIENTES_CLIENTE) {
                continue;
            }
            $agrupado[$dk][] = $fila;
        }

        return $agrupado;
    }

    private static function sqlSubqueryCuentasPorDocKey($soloListos = true)
    {
        $having = "HAVING deuda_total > 0";

        if ($soloListos) {
            $having .= " AND cant_docs <= " . (int) self::$MAX_DOCS_PENDIENTES_CLIENTE;
        }

        return "SELECT
                    " . self::sqlDocKey("c") . " AS doc_key,
                    UPPER(TRIM(MIN(c.tipo_documento))) AS tipo_documento,
                    UPPER(REPLACE(REPLACE(TRIM(MIN(c.documento)), ' ', ''), '-', '')) AS documento,
                    SUBSTRING_INDEX(GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR '||'), '||', 1) AS nombre,
                    SUM(cc.saldo) AS deuda_total,
                    SUM(
                        CASE
                            WHEN cc.fecha_ven IS NOT NULL
                             AND cc.fecha_ven <> '0000-00-00'
                             AND cc.fecha_ven < CURDATE()
                            THEN cc.saldo
                            ELSE 0
                        END
                    ) AS vencido_total,
                    COUNT(*) AS cant_docs,
                    COUNT(DISTINCT cc.cliente) AS cant_codigos
                FROM " . self::$TABLA_CUENTAS . " cc
                INNER JOIN " . self::$TABLA_CLIENTES . " c ON cc.cliente = c.codigo
                WHERE " . self::sqlFiltroCuentaPendiente("cc") . "
                  AND " . self::sqlFiltroDocValido("c") . "
                GROUP BY doc_key
                " . $having;
    }

    private static function sqlFiltroCuentaPendiente($alias = "cc")
    {
        $p = $alias !== "" ? $alias . "." : "";

        return $p . "tip_mov = '+'
            AND UPPER(TRIM(" . $p . "estado)) = 'PENDIENTE'
            AND " . $p . "saldo > 0";
    }

    private static function sqlPlaceholdersDocTypes()
    {
        $placeholders = array();

        foreach (self::$DOC_TYPES_VALIDOS as $tipo) {
            $placeholders[] = "'" . $tipo . "'";
        }

        return implode(", ", $placeholders);
    }
}
