<?php

require_once "conexion.php";

/**
 * Persistencia del módulo de regularizaciones comerciales.
 * Solo lectura sobre cuenta_ctejf; escritura exclusiva en tablas del módulo.
 */
class ModeloRegularizacionesComerciales
{
    /* ------------------------------------------------------------------ */
    /* Lecturas de referencia a cuenta_ctejf (sin escritura)               */
    /* ------------------------------------------------------------------ */

    static public function mdlObtenerCargoOficial($cuentaCteId)
    {
        $cuentaCteId = (int) $cuentaCteId;
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                c.id,
                c.tipo_doc,
                c.num_cta,
                c.cliente,
                c.vendedor,
                c.fecha,
                c.fecha_ven,
                c.monto,
                c.saldo,
                c.estado,
                c.tip_mov,
                c.doc_origen,
                c.ult_pago,
                cli.nombre AS cliente_nombre
             FROM cuenta_ctejf c
             LEFT JOIN clientesjf cli ON cli.codigo = c.cliente
             WHERE c.id = :id
               AND c.tip_mov = '+'
             LIMIT 1"
        );
        $stmt->bindParam(":id", $cuentaCteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlBuscarCargosOficiales($filtros)
    {
        $sql = "SELECT
                    c.id,
                    c.tipo_doc,
                    c.num_cta,
                    c.cliente,
                    c.vendedor,
                    c.fecha,
                    c.fecha_ven,
                    c.monto,
                    c.saldo,
                    c.estado,
                    cli.nombre AS cliente_nombre
                FROM cuenta_ctejf c
                LEFT JOIN clientesjf cli ON cli.codigo = c.cliente
                WHERE c.tip_mov = '+'
                  AND IFNULL(c.saldo, 0) > 0";
        $params = array();

        if (!empty($filtros["cuenta_cte_id"])) {
            $sql .= " AND c.id = :cuenta_cte_id";
            $params[":cuenta_cte_id"] = (int) $filtros["cuenta_cte_id"];
        }

        if (!empty($filtros["cliente"])) {
            $sql .= " AND c.cliente = :cliente";
            $params[":cliente"] = trim((string) $filtros["cliente"]);
        }

        if (!empty($filtros["tipo_doc"])) {
            $sql .= " AND c.tipo_doc = :tipo_doc";
            $params[":tipo_doc"] = trim((string) $filtros["tipo_doc"]);
        }

        if (!empty($filtros["num_cta"])) {
            $sql .= " AND c.num_cta = :num_cta";
            $params[":num_cta"] = trim((string) $filtros["num_cta"]);
        }

        if (!empty($filtros["q"])) {
            $q = trim((string) $filtros["q"]);
            $sql .= " AND (
                c.cliente LIKE :q
                OR c.num_cta LIKE :q
                OR c.tipo_doc LIKE :q
                OR IFNULL(cli.nombre, '') LIKE :q
            )";
            $params[":q"] = "%" . $q . "%";
        }

        $sql .= " ORDER BY c.fecha DESC, c.id DESC LIMIT 50";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $tipo = ($key === ":cuenta_cte_id") ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $tipo);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Último abono oficial vinculado al cargo (tipo_doc + num_cta).
     * Misma regla que mdlMostrarCancelacionesV2.
     */
    static public function mdlUltimoAbonoOficialId($tipoDoc, $numCta, $clienteCodigo = "")
    {
        $sql = "SELECT MAX(c.id) AS max_id
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '-'
                  AND c.tipo_doc = :tipo_doc
                  AND c.num_cta = :num_cta";
        $params = array(
            ":tipo_doc" => trim((string) $tipoDoc),
            ":num_cta" => trim((string) $numCta),
        );

        if ($clienteCodigo !== "") {
            $sql .= " AND c.cliente = :cliente";
            $params[":cliente"] = trim((string) $clienteCodigo);
        }

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila && $fila["max_id"] !== null ? (int) $fila["max_id"] : null;
    }

    /**
     * Abonos oficiales posteriores al corte, vínculo inequívoco tipo_doc+num_cta.
     */
    static public function mdlAbonosOficialesPosteriores($tipoDoc, $numCta, $clienteCodigo, $corteId, array $excluirIds = array())
    {
        $sql = "SELECT
                    c.id,
                    c.tipo_doc,
                    c.num_cta,
                    c.cliente,
                    c.doc_origen,
                    c.fecha,
                    c.monto,
                    c.notas
                FROM cuenta_ctejf c
                WHERE c.tip_mov = '-'
                  AND c.tipo_doc = :tipo_doc
                  AND c.num_cta = :num_cta
                  AND c.cliente = :cliente";
        $params = array(
            ":tipo_doc" => trim((string) $tipoDoc),
            ":num_cta" => trim((string) $numCta),
            ":cliente" => trim((string) $clienteCodigo),
        );

        $corteId = (int) $corteId;
        if ($corteId > 0) {
            $sql .= " AND c.id > :corte_id";
            $params[":corte_id"] = $corteId;
        }

        $excluirIds = array_values(array_filter(array_map("intval", $excluirIds)));
        if (!empty($excluirIds)) {
            $ph = array();
            foreach ($excluirIds as $i => $idEx) {
                $key = ":ex" . $i;
                $ph[] = $key;
                $params[$key] = $idEx;
            }
            $sql .= " AND c.id NOT IN (" . implode(", ", $ph) . ")";
        }

        $sql .= " ORDER BY c.fecha ASC, c.id ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $tipo = (strpos($key, ":corte") === 0 || strpos($key, ":ex") === 0)
                ? PDO::PARAM_INT
                : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $tipo);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ------------------------------------------------------------------ */
    /* Tablas del módulo                                                   */
    /* ------------------------------------------------------------------ */

    static public function mdlObtenerPorId($id)
    {
        $id = (int) $id;
        $stmt = Conexion::conectar()->prepare(
            "SELECT r.*,
                    ur.nombre AS usuario_registro_nombre,
                    ua.nombre AS usuario_anulacion_nombre
             FROM regularizacion_comercialjf r
             LEFT JOIN usuariosjf ur ON ur.id = r.usuario_registro_id
             LEFT JOIN usuariosjf ua ON ua.id = r.usuario_anulacion_id
             WHERE r.id = :id
             LIMIT 1"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlListar($filtros = array())
    {
        $sql = "SELECT r.*,
                       ur.nombre AS usuario_registro_nombre
                FROM regularizacion_comercialjf r
                LEFT JOIN usuariosjf ur ON ur.id = r.usuario_registro_id
                WHERE 1 = 1";
        $params = array();

        if (!empty($filtros["estado"])) {
            $sql .= " AND r.estado = :estado";
            $params[":estado"] = trim((string) $filtros["estado"]);
        }

        if (!empty($filtros["cuenta_cte_id"])) {
            $sql .= " AND r.cuenta_cte_id = :cuenta_cte_id";
            $params[":cuenta_cte_id"] = (int) $filtros["cuenta_cte_id"];
        }

        if (!empty($filtros["cliente_codigo"])) {
            $sql .= " AND r.cliente_codigo = :cliente_codigo";
            $params[":cliente_codigo"] = trim((string) $filtros["cliente_codigo"]);
        }

        if (!empty($filtros["tipo_doc"]) && !empty($filtros["num_cta"])) {
            $sql .= " AND r.tipo_doc = :tipo_doc AND r.num_cta = :num_cta";
            $params[":tipo_doc"] = trim((string) $filtros["tipo_doc"]);
            $params[":num_cta"] = trim((string) $filtros["num_cta"]);
        }

        $sql .= " ORDER BY r.fecha_registro DESC, r.id DESC LIMIT 200";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $tipo = ($key === ":cuenta_cte_id") ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $tipo);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlListarActivasPorCuenta($cuentaCteId)
    {
        $cuentaCteId = (int) $cuentaCteId;
        $stmt = Conexion::conectar()->prepare(
            "SELECT *
             FROM regularizacion_comercialjf
             WHERE cuenta_cte_id = :cuenta_cte_id
               AND estado IN ('ACTIVA', 'REQUIERE_REVISION')
               AND monto_aplicable > 0
             ORDER BY id ASC"
        );
        $stmt->bindParam(":cuenta_cte_id", $cuentaCteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suma de monto_aplicable activo por cuenta_cte_id (para adaptador VascoPro).
     * @return array mapa [cuenta_cte_id => suma]
     */
    static public function mdlSumaMontoAplicableActivasPorCuentas(array $cuentaIds)
    {
        $cuentaIds = array_values(array_filter(array_map("intval", $cuentaIds)));
        if (empty($cuentaIds)) {
            return array();
        }

        $ph = implode(", ", array_fill(0, count($cuentaIds), "?"));
        $sql = "SELECT cuenta_cte_id, SUM(monto_aplicable) AS suma_aplicable
                FROM regularizacion_comercialjf
                WHERE cuenta_cte_id IN ($ph)
                  AND estado IN ('ACTIVA', 'REQUIERE_REVISION')
                  AND monto_aplicable > 0
                GROUP BY cuenta_cte_id";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($cuentaIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $mapa = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $mapa[(int) $fila["cuenta_cte_id"]] = (float) $fila["suma_aplicable"];
        }

        return $mapa;
    }

    /**
     * Suma aplicable activa agrupada como el exportador VascoPro (tipo_doc + num_cta).
     * @param array $pares listado de array('tipo_doc'=>..., 'num_cta'=>...)
     * @return array mapa ["TIPO|NUM" => suma]
     */
    static public function mdlSumaMontoAplicableActivasPorDocs(array $pares)
    {
        $claves = array();
        foreach ($pares as $par) {
            $tipo = isset($par["tipo_doc"]) ? trim((string) $par["tipo_doc"]) : "";
            $num = isset($par["num_cta"]) ? trim((string) $par["num_cta"]) : "";
            if ($tipo === "" || $num === "") {
                continue;
            }
            $claves[$tipo . "|" . $num] = array($tipo, $num);
        }

        if (empty($claves)) {
            return array();
        }

        $ors = array();
        $params = array();
        $i = 0;
        foreach ($claves as $par) {
            $ors[] = "(tipo_doc = :t" . $i . " AND num_cta = :n" . $i . ")";
            $params[":t" . $i] = $par[0];
            $params[":n" . $i] = $par[1];
            $i++;
        }

        $sql = "SELECT tipo_doc, num_cta, SUM(monto_aplicable) AS suma_aplicable
                FROM regularizacion_comercialjf
                WHERE estado IN ('ACTIVA', 'REQUIERE_REVISION')
                  AND monto_aplicable > 0
                  AND (" . implode(" OR ", $ors) . ")
                GROUP BY tipo_doc, num_cta";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $mapa = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $k = trim((string) $fila["tipo_doc"]) . "|" . trim((string) $fila["num_cta"]);
            $mapa[$k] = (float) $fila["suma_aplicable"];
        }

        return $mapa;
    }

    /**
     * ¿Hay alguna regularización con monto aplicable activo?
     */
    static public function mdlHayRegularizacionesActivas()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT 1
             FROM regularizacion_comercialjf
             WHERE estado IN ('ACTIVA', 'REQUIERE_REVISION')
               AND monto_aplicable > 0
             LIMIT 1"
        );
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Suma total de montos aplicables activos (tope de impacto comercial).
     */
    static public function mdlSumaMontoAplicableActivoTotal()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT IFNULL(SUM(monto_aplicable), 0) AS suma
             FROM regularizacion_comercialjf
             WHERE estado IN ('ACTIVA', 'REQUIERE_REVISION')
               AND monto_aplicable > 0"
        );
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? round((float) $fila["suma"], 2) : 0.0;
    }

    static public function mdlEventosPorRegularizacion($regularizacionId, $limite = 100)
    {
        $regularizacionId = (int) $regularizacionId;
        $limite = max(1, (int) $limite);
        $stmt = Conexion::conectar()->prepare(
            "SELECT e.*, u.nombre AS usuario_nombre
             FROM regularizacion_comercial_eventojf e
             LEFT JOIN usuariosjf u ON u.id = e.usuario_id
             WHERE e.regularizacion_id = :regularizacion_id
             ORDER BY e.fecha DESC, e.id DESC
             LIMIT :limite"
        );
        $stmt->bindParam(":regularizacion_id", $regularizacionId, PDO::PARAM_INT);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlMovimientosOficialesConsumidos($cuentaCteId)
    {
        $cuentaCteId = (int) $cuentaCteId;
        $stmt = Conexion::conectar()->prepare(
            "SELECT DISTINCT e.movimiento_oficial_id
             FROM regularizacion_comercial_eventojf e
             INNER JOIN regularizacion_comercialjf r ON r.id = e.regularizacion_id
             WHERE r.cuenta_cte_id = :cuenta_cte_id
               AND e.movimiento_oficial_id IS NOT NULL"
        );
        $stmt->bindParam(":cuenta_cte_id", $cuentaCteId, PDO::PARAM_INT);
        $stmt->execute();

        $ids = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $ids[] = (int) $fila["movimiento_oficial_id"];
        }

        return $ids;
    }

    static public function mdlInsertar(PDO $db, array $datos)
    {
        $stmt = $db->prepare(
            "INSERT INTO regularizacion_comercialjf (
                cuenta_cte_id, tipo_doc, num_cta, cliente_codigo,
                monto_original, monto_aplicable, fecha_pago_cliente, fecha_registro,
                saldo_oficial_al_registrar, corte_movimiento_oficial_id, estado,
                motivo, sustento_referencia, observacion, usuario_registro_id, version
             ) VALUES (
                :cuenta_cte_id, :tipo_doc, :num_cta, :cliente_codigo,
                :monto_original, :monto_aplicable, :fecha_pago_cliente, NOW(),
                :saldo_oficial_al_registrar, :corte_movimiento_oficial_id, :estado,
                :motivo, :sustento_referencia, :observacion, :usuario_registro_id, 1
             )"
        );

        $corte = isset($datos["corte_movimiento_oficial_id"]) && $datos["corte_movimiento_oficial_id"] !== null
            ? (int) $datos["corte_movimiento_oficial_id"]
            : null;

        $stmt->bindValue(":cuenta_cte_id", (int) $datos["cuenta_cte_id"], PDO::PARAM_INT);
        $stmt->bindValue(":tipo_doc", $datos["tipo_doc"], PDO::PARAM_STR);
        $stmt->bindValue(":num_cta", $datos["num_cta"], PDO::PARAM_STR);
        $stmt->bindValue(":cliente_codigo", $datos["cliente_codigo"], PDO::PARAM_STR);
        $stmt->bindValue(":monto_original", $datos["monto_original"]);
        $stmt->bindValue(":monto_aplicable", $datos["monto_aplicable"]);
        $stmt->bindValue(":fecha_pago_cliente", $datos["fecha_pago_cliente"], PDO::PARAM_STR);
        $stmt->bindValue(":saldo_oficial_al_registrar", $datos["saldo_oficial_al_registrar"]);
        if ($corte === null) {
            $stmt->bindValue(":corte_movimiento_oficial_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":corte_movimiento_oficial_id", $corte, PDO::PARAM_INT);
        }
        $stmt->bindValue(":estado", $datos["estado"], PDO::PARAM_STR);
        $stmt->bindValue(":motivo", $datos["motivo"], PDO::PARAM_STR);
        $stmt->bindValue(":sustento_referencia", $datos["sustento_referencia"], PDO::PARAM_STR);
        if (isset($datos["observacion"]) && $datos["observacion"] !== null && $datos["observacion"] !== "") {
            $stmt->bindValue(":observacion", $datos["observacion"], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(":observacion", null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(":usuario_registro_id", (int) $datos["usuario_registro_id"], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $db->lastInsertId();
    }

    static public function mdlInsertarEvento(PDO $db, array $datos)
    {
        $stmt = $db->prepare(
            "INSERT INTO regularizacion_comercial_eventojf (
                regularizacion_id, tipo_evento, estado_anterior, estado_nuevo,
                monto_delta, monto_aplicable_resultante, movimiento_oficial_id,
                detalle_json, usuario_id, fecha, origen
             ) VALUES (
                :regularizacion_id, :tipo_evento, :estado_anterior, :estado_nuevo,
                :monto_delta, :monto_aplicable_resultante, :movimiento_oficial_id,
                :detalle_json, :usuario_id, NOW(), :origen
             )"
        );

        $movId = isset($datos["movimiento_oficial_id"]) && $datos["movimiento_oficial_id"] !== null
            ? (int) $datos["movimiento_oficial_id"]
            : null;
        $usuarioId = isset($datos["usuario_id"]) && $datos["usuario_id"] !== null
            ? (int) $datos["usuario_id"]
            : null;

        $stmt->bindValue(":regularizacion_id", (int) $datos["regularizacion_id"], PDO::PARAM_INT);
        $stmt->bindValue(":tipo_evento", $datos["tipo_evento"], PDO::PARAM_STR);
        $stmt->bindValue(
            ":estado_anterior",
            isset($datos["estado_anterior"]) ? $datos["estado_anterior"] : null,
            PDO::PARAM_STR
        );
        $stmt->bindValue(
            ":estado_nuevo",
            isset($datos["estado_nuevo"]) ? $datos["estado_nuevo"] : null,
            PDO::PARAM_STR
        );
        $stmt->bindValue(
            ":monto_delta",
            array_key_exists("monto_delta", $datos) ? $datos["monto_delta"] : null
        );
        $stmt->bindValue(
            ":monto_aplicable_resultante",
            array_key_exists("monto_aplicable_resultante", $datos) ? $datos["monto_aplicable_resultante"] : null
        );
        if ($movId === null) {
            $stmt->bindValue(":movimiento_oficial_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":movimiento_oficial_id", $movId, PDO::PARAM_INT);
        }
        $stmt->bindValue(
            ":detalle_json",
            isset($datos["detalle_json"]) ? $datos["detalle_json"] : null,
            PDO::PARAM_STR
        );
        if ($usuarioId === null) {
            $stmt->bindValue(":usuario_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
        }
        $stmt->bindValue(":origen", isset($datos["origen"]) ? $datos["origen"] : "USUARIO", PDO::PARAM_STR);
        $stmt->execute();

        return (int) $db->lastInsertId();
    }

    static public function mdlAnular(PDO $db, $id, $version, $usuarioId, $motivoAnulacion)
    {
        $id = (int) $id;
        $version = (int) $version;
        $usuarioId = (int) $usuarioId;

        $stmt = $db->prepare(
            "UPDATE regularizacion_comercialjf
             SET estado = 'ANULADA',
                 monto_aplicable = 0,
                 usuario_anulacion_id = :usuario_id,
                 fecha_anulacion = NOW(),
                 motivo_anulacion = :motivo_anulacion,
                 version = version + 1
             WHERE id = :id
               AND version = :version
               AND estado IN ('ACTIVA', 'REQUIERE_REVISION')"
        );
        $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(":motivo_anulacion", $motivoAnulacion, PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":version", $version, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    static public function mdlAplicarResolucion(PDO $db, $id, $version, $nuevoMonto, $nuevoEstado)
    {
        $id = (int) $id;
        $version = (int) $version;

        $stmt = $db->prepare(
            "UPDATE regularizacion_comercialjf
             SET monto_aplicable = :monto_aplicable,
                 estado = :estado,
                 version = version + 1
             WHERE id = :id
               AND version = :version
               AND estado IN ('ACTIVA', 'REQUIERE_REVISION')"
        );
        $stmt->bindValue(":monto_aplicable", $nuevoMonto);
        $stmt->bindValue(":estado", $nuevoEstado, PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":version", $version, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    static public function mdlMarcarRequiereRevision(PDO $db, $id, $version)
    {
        $id = (int) $id;
        $version = (int) $version;

        $stmt = $db->prepare(
            "UPDATE regularizacion_comercialjf
             SET estado = 'REQUIERE_REVISION',
                 version = version + 1
             WHERE id = :id
               AND version = :version
               AND estado = 'ACTIVA'"
        );
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":version", $version, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }
}
