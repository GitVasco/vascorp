<?php

require_once "conexion.php";

class ModeloVascoSync
{
    static $TABLA_CLIENTES = "clientesjf";

    static $TABLA_CUENTAS = "cuenta_ctejf";

    static $TABLA_GRUPOS = "grupos_empresarialesjf";

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
                    c.telefono2,
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

    /**
     * Busca cliente ERP para gestión Vasco (external_id o documento).
     *
     * @param string|int|null $externalId
     * @param string $docType
     * @param string $docNumber
     * @return array|null
     */
    public static function mdlClienteParaGestionVasco($externalId, $docType, $docNumber, $codigo = "")
    {
        $tabla = self::$TABLA_CLIENTES;

        if ($externalId !== null && trim((string) $externalId) !== "") {
            $externalRaw = trim((string) $externalId);
            if (ctype_digit($externalRaw)) {
                $id = (int) $externalRaw;
                if ($id > 0) {
                    $stmt = Conexion::conectar()->prepare(
                        "SELECT id, codigo, nombre, telefono, telefono2, documento, tipo_documento, vendedor
                         FROM $tabla WHERE id = :id LIMIT 1"
                    );
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($fila) {
                        return $fila;
                    }
                }
            }
        }

        $codigo = trim((string) $codigo);
        if ($codigo !== "") {
            $stmt = Conexion::conectar()->prepare(
                "SELECT id, codigo, nombre, telefono, telefono2, documento, tipo_documento, vendedor
                 FROM $tabla WHERE codigo = :codigo LIMIT 1"
            );
            $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
            $stmt->execute();
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fila) {
                return $fila;
            }
        }

        $docType = strtoupper(trim((string) $docType));
        $docNumber = self::normalizarDocumento($docNumber);
        if ($docType === "" || $docNumber === "") {
            if ($docNumber !== "") {
                $stmt = Conexion::conectar()->prepare(
                    "SELECT id, codigo, nombre, telefono, telefono2, documento, tipo_documento, vendedor
                     FROM $tabla
                     WHERE UPPER(REPLACE(TRIM(IFNULL(documento, '')), ' ', '')) = :documento
                     ORDER BY CASE WHEN estado = 1 AND fecha IS NOT NULL THEN 0 ELSE 1 END, id DESC
                     LIMIT 1"
                );
                $stmt->bindParam(":documento", $docNumber, PDO::PARAM_STR);
                $stmt->execute();
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($fila) {
                    return $fila;
                }
            }

            return null;
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT id, codigo, nombre, telefono, telefono2, documento, tipo_documento, vendedor
             FROM $tabla
             WHERE UPPER(TRIM(IFNULL(tipo_documento, ''))) = :tipo_documento
               AND UPPER(REPLACE(TRIM(IFNULL(documento, '')), ' ', '')) = :documento
             ORDER BY CASE WHEN estado = 1 AND fecha IS NOT NULL THEN 0 ELSE 1 END, id DESC
             LIMIT 1"
        );
        $stmt->bindParam(":tipo_documento", $docType, PDO::PARAM_STR);
        $stmt->bindParam(":documento", $docNumber, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : null;
    }

    /**
     * @param string $telefono 9 dígitos
     * @param int $excluirId
     * @return array|null
     */
    public static function mdlClienteConTelefonoDuplicado($telefono, $excluirId)
    {
        $telefono = preg_replace('/\D/', '', (string) $telefono);
        if ($telefono === "") {
            return null;
        }

        $tabla = self::$TABLA_CLIENTES;
        $excluirId = (int) $excluirId;

        $stmt = Conexion::conectar()->prepare(
            "SELECT id, codigo, nombre, telefono
             FROM $tabla
             WHERE id <> :excluir_id
               AND (
                    REPLACE(TRIM(IFNULL(telefono, '')), ' ', '') = :telefono
                    OR REPLACE(TRIM(IFNULL(telefono2, '')), ' ', '') = :telefono
               )
             LIMIT 1"
        );
        $stmt->bindParam(":excluir_id", $excluirId, PDO::PARAM_INT);
        $stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : null;
    }

    /**
     * @param int $idCliente
     * @param string $telefono
     * @return string ok|error
     */
    public static function mdlActualizarTelefonoCliente($idCliente, $telefono)
    {
        $idCliente = (int) $idCliente;
        $telefono = trim((string) $telefono);
        if ($idCliente < 1 || $telefono === "") {
            return "error";
        }

        $tabla = self::$TABLA_CLIENTES;
        $stmt = Conexion::conectar()->prepare(
            "UPDATE $tabla SET telefono = :telefono WHERE id = :id"
        );
        $stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $stmt->bindParam(":id", $idCliente, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    // ============================================
    // GRUPOS EMPRESARIALES → business-groups-bulk
    // + miembros → business-group-members-bulk
    // ============================================

    /**
     * Subquery: un cliente consolidado por documento (mismo criterio que sync de clientes).
     *
     * @return string
     */
    private static function sqlSubqueryClientesConsolidados()
    {
        return "SELECT
                    " . self::sqlDocKey() . " AS doc_key,
                    CAST(SUBSTRING_INDEX(GROUP_CONCAT(
                        id ORDER BY
                            CASE WHEN estado = 1 AND fecha IS NOT NULL THEN 0 ELSE 1 END ASC,
                            IFNULL(fecreg, '1970-01-01') DESC,
                            id ASC
                    ), ',', 1) AS UNSIGNED) AS id_elegido
                FROM " . self::$TABLA_CLIENTES . "
                WHERE " . self::sqlFiltroDocValido() . "
                GROUP BY doc_key";
    }

    public static function mdlResumenGrupos()
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado <> 1 THEN 1 ELSE 0 END) AS inactivos,
                    SUM(CASE WHEN TRIM(IFNULL(codigo, '')) = '' THEN 1 ELSE 0 END) AS sin_codigo,
                    SUM(CASE WHEN TRIM(IFNULL(nombre, '')) = '' THEN 1 ELSE 0 END) AS sin_nombre
                FROM " . self::$TABLA_GRUPOS;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : array();
    }

    /**
     * Grupos con código y nombre (listos para enviar).
     */
    public static function mdlContarGruposListos()
    {
        $sql = "SELECT COUNT(*) AS listos
                FROM " . self::$TABLA_GRUPOS . "
                WHERE TRIM(IFNULL(codigo, '')) <> ''
                  AND TRIM(IFNULL(nombre, '')) <> ''";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["listos"]) ? (int) $fila["listos"] : 0;
    }

    /**
     * @param int $offset
     * @param int $limite
     * @return array
     */
    public static function mdlGruposParaSyncLote($offset, $limite)
    {
        $offset = (int) $offset;
        $limite = (int) $limite;

        if ($limite <= 0) {
            return array();
        }

        $sql = "SELECT
                    id,
                    codigo,
                    nombre,
                    descripcion,
                    estado
                FROM " . self::$TABLA_GRUPOS . "
                WHERE TRIM(IFNULL(codigo, '')) <> ''
                  AND TRIM(IFNULL(nombre, '')) <> ''
                ORDER BY id ASC
                LIMIT " . $offset . ", " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Muestra de grupos para auditoría UI.
     *
     * @param int $limite
     * @return array
     */
    public static function mdlMuestraGrupos($limite = 30)
    {
        $limite = (int) $limite;
        if ($limite <= 0) {
            return array();
        }

        $sql = "SELECT
                    g.id,
                    g.codigo,
                    g.nombre,
                    g.estado,
                    (SELECT COUNT(*)
                     FROM " . self::$TABLA_CLIENTES . " c
                     WHERE c.grupo = g.codigo AND c.estado = 1) AS total_clientes
                FROM " . self::$TABLA_GRUPOS . " g
                WHERE TRIM(IFNULL(g.codigo, '')) <> ''
                  AND TRIM(IFNULL(g.nombre, '')) <> ''
                ORDER BY g.nombre ASC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clientes consolidados (doc válido) con grupo existente en maestro.
     */
    public static function mdlContarMiembrosGrupoListos()
    {
        $sql = "SELECT COUNT(*) AS listos
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (" . self::sqlSubqueryClientesConsolidados() . ") pick ON c.id = pick.id_elegido
                INNER JOIN " . self::$TABLA_GRUPOS . " g ON g.codigo = c.grupo
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["listos"]) ? (int) $fila["listos"] : 0;
    }

    /**
     * @param int $offset
     * @param int $limite
     * @return array
     */
    public static function mdlMiembrosGrupoParaSyncLote($offset, $limite)
    {
        $offset = (int) $offset;
        $limite = (int) $limite;

        if ($limite <= 0) {
            return array();
        }

        $sql = "SELECT
                    c.id AS customer_id,
                    c.codigo AS customer_codigo,
                    c.nombre AS customer_nombre,
                    c.tipo_documento,
                    c.documento,
                    c.grupo AS grupo_codigo,
                    g.id AS grupo_id,
                    g.nombre AS grupo_nombre
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (" . self::sqlSubqueryClientesConsolidados() . ") pick ON c.id = pick.id_elegido
                INNER JOIN " . self::$TABLA_GRUPOS . " g ON g.codigo = c.grupo
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''
                ORDER BY c.id ASC
                LIMIT " . $offset . ", " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cliente consolidado con grupo apuntando a código inexistente.
     *
     * @param int $limite
     * @return array
     */
    public static function mdlMiembrosGrupoCodigoInexistente($limite = 50)
    {
        $limite = (int) $limite;

        $sql = "SELECT
                    c.id,
                    c.codigo,
                    c.nombre,
                    c.grupo
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (" . self::sqlSubqueryClientesConsolidados() . ") pick ON c.id = pick.id_elegido
                LEFT JOIN " . self::$TABLA_GRUPOS . " g ON g.codigo = c.grupo
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''
                  AND g.id IS NULL
                ORDER BY c.codigo ASC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlContarMiembrosGrupoCodigoInexistente()
    {
        $sql = "SELECT COUNT(*) AS total
                FROM " . self::$TABLA_CLIENTES . " c
                INNER JOIN (" . self::sqlSubqueryClientesConsolidados() . ") pick ON c.id = pick.id_elegido
                LEFT JOIN " . self::$TABLA_GRUPOS . " g ON g.codigo = c.grupo
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''
                  AND g.id IS NULL";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    /**
     * Clientes con grupo pero sin documento válido (no se envían como miembros).
     */
    public static function mdlContarClientesConGrupoSinDocValido()
    {
        $sql = "SELECT COUNT(*) AS total
                FROM " . self::$TABLA_CLIENTES . " c
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''
                  AND NOT (" . self::sqlFiltroDocValido("c") . ")";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    /**
     * @param int $limite
     * @return array
     */
    public static function mdlClientesConGrupoSinDocValido($limite = 50)
    {
        $limite = (int) $limite;

        $sql = "SELECT
                    c.id,
                    c.codigo,
                    c.nombre,
                    c.tipo_documento,
                    c.documento,
                    c.grupo
                FROM " . self::$TABLA_CLIENTES . " c
                WHERE TRIM(IFNULL(c.grupo, '')) <> ''
                  AND NOT (" . self::sqlFiltroDocValido("c") . ")
                ORDER BY c.codigo ASC
                LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Muestra de asignaciones listas (cliente consolidado → grupo).
     *
     * @param int $limite
     * @return array
     */
    public static function mdlMuestraMiembrosGrupo($limite = 30)
    {
        return self::mdlMiembrosGrupoParaSyncLote(0, (int) $limite);
    }
}

