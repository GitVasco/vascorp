<?php

require_once "conexion.php";

class ModeloDecisionesCredito
{
    private static function nombreUsuario($idUsuario)
    {
        $idUsuario = (int) $idUsuario;

        if ($idUsuario <= 0) {
            return "—";
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT nombre FROM usuariosjf WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila["nombre"] : ("Usuario #" . $idUsuario);
    }

    static public function mdlPedidoExiste($codigoPedido, $codigoCliente = "")
    {
        $sql = "SELECT
                    t.codigo,
                    t.cliente,
                    c.nombre AS cliente_nombre,
                    t.estado,
                    t.total,
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
                    ), 0) AS deuda_vencida,
                    IFNULL((
                        SELECT COUNT(*)
                        FROM cuenta_ctejf ct
                        WHERE ct.cliente = c.codigo
                          AND UPPER(ct.estado) = 'PENDIENTE'
                          AND IFNULL(ct.saldo, 0) > 0
                          AND ct.fecha_ven < CURDATE()
                    ), 0) AS docs_vencidos,
                    COALESCE(
                        (SELECT MAX(v.fecha)
                         FROM ventajf v
                         WHERE v.cliente = c.codigo
                           AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'),
                        c.ultima_compra,
                        (SELECT MAX(t2.fecha)
                         FROM temporaljf t2
                         WHERE t2.cliente = c.codigo
                           AND t2.estado NOT IN ('ANULADO'))
                    ) AS ultima_compra
                FROM temporaljf t
                LEFT JOIN clientesjf c ON t.cliente = c.codigo
                WHERE t.codigo = :codigo_pedido";
        $params = array(":codigo_pedido" => (int) $codigoPedido);

        if ($codigoCliente !== "") {
            $sql .= " AND t.cliente = :cliente";
            $params[":cliente"] = $codigoCliente;
        }

        $sql .= " LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);

        foreach ($params as $key => $value) {
            $tipo = ($key === ":codigo_pedido") ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $tipo);
        }

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlDecisionVigentePorPedido($codigoPedido)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT *
             FROM decision_credito_pedidojf
             WHERE codigo_pedido = :codigo_pedido
               AND estado = 'VIGENTE'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bindParam(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlDecisionesVigentesPorPedidos(array $codigosPedido)
    {
        $codigosPedido = array_values(array_filter(array_map("intval", $codigosPedido)));

        if (empty($codigosPedido)) {
            return array();
        }

        $placeholders = implode(", ", array_fill(0, count($codigosPedido), "?"));
        $sql = "SELECT
                    d.*,
                    ur.nombre AS usuario_registro_nombre,
                    uu.nombre AS usuario_resolucion_nombre
                FROM decision_credito_pedidojf d
                INNER JOIN (
                    SELECT codigo_pedido, MAX(id) AS max_id
                    FROM decision_credito_pedidojf
                    WHERE estado = 'VIGENTE'
                      AND codigo_pedido IN ($placeholders)
                    GROUP BY codigo_pedido
                ) ult ON ult.max_id = d.id
                LEFT JOIN usuariosjf ur ON ur.id = d.usuario_registro
                LEFT JOIN usuariosjf uu ON uu.id = d.usuario_resolucion";

        $stmt = Conexion::conectar()->prepare($sql);

        foreach ($codigosPedido as $idx => $codigo) {
            $stmt->bindValue($idx + 1, $codigo, PDO::PARAM_INT);
        }

        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapa = array();

        foreach ($filas as $fila) {
            $mapa[(int) $fila["codigo_pedido"]] = $fila;
        }

        return $mapa;
    }

    static public function mdlSolicitudesPorDecision($idDecision)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT *
             FROM decision_credito_solicitudjf
             WHERE id_decision = :id_decision
             ORDER BY fecha_solicitud DESC, id DESC"
        );
        $stmt->bindParam(":id_decision", $idDecision, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlEventosPorPedido($codigoPedido, $limite = 30)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT *
             FROM decision_credito_eventojf
             WHERE codigo_pedido = :codigo_pedido
             ORDER BY fecha DESC, id DESC
             LIMIT :limite"
        );
        $stmt->bindParam(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlRegistrarEvento($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO decision_credito_eventojf
                (codigo_pedido, codigo_cliente, tipo_evento, detalle, id_referencia, usuario_id)
             VALUES
                (:codigo_pedido, :codigo_cliente, :tipo_evento, :detalle, :id_referencia, :usuario_id)"
        );

        $stmt->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
        $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo_evento", $datos["tipo_evento"], PDO::PARAM_STR);
        $stmt->bindParam(":detalle", $datos["detalle"], PDO::PARAM_STR);
        $stmt->bindParam(":id_referencia", $datos["id_referencia"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    static public function mdlRegistrarNoAprobacion($datos)
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $stmtCerrar = $db->prepare(
                "UPDATE decision_credito_pedidojf
                 SET estado = 'CERRADA',
                     fecha_resolucion = NOW()
                 WHERE codigo_pedido = :codigo_pedido
                   AND estado = 'VIGENTE'"
            );
            $stmtCerrar->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmtCerrar->execute();

            $stmt = $db->prepare(
                "INSERT INTO decision_credito_pedidojf
                    (codigo_pedido, codigo_cliente, motivo_codigo, comentario, usuario_registro)
                 VALUES
                    (:codigo_pedido, :codigo_cliente, :motivo_codigo, :comentario, :usuario_registro)"
            );
            $stmt->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmt->bindParam(":motivo_codigo", $datos["motivo_codigo"], PDO::PARAM_STR);
            $stmt->bindParam(":comentario", $datos["comentario"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario_registro", $datos["usuario_registro"], PDO::PARAM_INT);
            $stmt->execute();

            $idDecision = (int) $db->lastInsertId();

            $detalle = "Motivo: " . $datos["motivo_codigo"];
            if (!empty($datos["comentario"])) {
                $detalle .= " — " . $datos["comentario"];
            }

            $stmtEvento = $db->prepare(
                "INSERT INTO decision_credito_eventojf
                    (codigo_pedido, codigo_cliente, tipo_evento, detalle, id_referencia, usuario_id)
                 VALUES
                    (:codigo_pedido, :codigo_cliente, 'DECISION_REGISTRADA', :detalle, :id_referencia, :usuario_id)"
            );
            $stmtEvento->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmtEvento->bindParam(":detalle", $detalle, PDO::PARAM_STR);
            $stmtEvento->bindParam(":id_referencia", $idDecision, PDO::PARAM_INT);
            $stmtEvento->bindParam(":usuario_id", $datos["usuario_registro"], PDO::PARAM_INT);
            $stmtEvento->execute();

            $db->commit();

            return array("ok" => true, "id" => $idDecision);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return array("ok" => false, "msg" => "No se pudo registrar la decisión.");
        }
    }

    static public function mdlCrearSolicitud($datos)
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO decision_credito_solicitudjf
                    (id_decision, codigo_pedido, codigo_cliente, tipo_solicitud, justificacion, usuario_solicita)
                 VALUES
                    (:id_decision, :codigo_pedido, :codigo_cliente, :tipo_solicitud, :justificacion, :usuario_solicita)"
            );
            $stmt->bindParam(":id_decision", $datos["id_decision"], PDO::PARAM_INT);
            $stmt->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmt->bindParam(":tipo_solicitud", $datos["tipo_solicitud"], PDO::PARAM_STR);
            $stmt->bindParam(":justificacion", $datos["justificacion"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario_solicita", $datos["usuario_solicita"], PDO::PARAM_INT);
            $stmt->execute();

            $idSolicitud = (int) $db->lastInsertId();
            $detalle = $datos["tipo_solicitud"] . " — " . $datos["justificacion"];

            $stmtEvento = $db->prepare(
                "INSERT INTO decision_credito_eventojf
                    (codigo_pedido, codigo_cliente, tipo_evento, detalle, id_referencia, usuario_id)
                 VALUES
                    (:codigo_pedido, :codigo_cliente, 'SOLICITUD_CREADA', :detalle, :id_referencia, :usuario_id)"
            );
            $stmtEvento->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmtEvento->bindParam(":detalle", $detalle, PDO::PARAM_STR);
            $stmtEvento->bindParam(":id_referencia", $idSolicitud, PDO::PARAM_INT);
            $stmtEvento->bindParam(":usuario_id", $datos["usuario_solicita"], PDO::PARAM_INT);
            $stmtEvento->execute();

            $db->commit();

            return array("ok" => true, "id" => $idSolicitud);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return array("ok" => false, "msg" => "No se pudo crear la solicitud.");
        }
    }

    static public function mdlResolverSolicitud($datos)
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "UPDATE decision_credito_solicitudjf
                 SET estado = :estado,
                     resolucion_codigo = :resolucion_codigo,
                     comentario_resolucion = :comentario_resolucion,
                     usuario_resuelve = :usuario_resuelve,
                     fecha_resolucion = NOW()
                 WHERE id = :id
                   AND estado IN ('PENDIENTE', 'EN_REVISION')"
            );
            $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
            $stmt->bindParam(":resolucion_codigo", $datos["resolucion_codigo"], PDO::PARAM_STR);
            $stmt->bindParam(":comentario_resolucion", $datos["comentario_resolucion"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario_resuelve", $datos["usuario_resuelve"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() < 1) {
                $db->rollBack();

                return array("ok" => false, "msg" => "La solicitud no existe o ya fue resuelta.");
            }

            $detalle = $datos["estado"] . " — " . $datos["resolucion_codigo"];
            if (!empty($datos["comentario_resolucion"])) {
                $detalle .= " — " . $datos["comentario_resolucion"];
            }

            $stmtEvento = $db->prepare(
                "INSERT INTO decision_credito_eventojf
                    (codigo_pedido, codigo_cliente, tipo_evento, detalle, id_referencia, usuario_id)
                 VALUES
                    (:codigo_pedido, :codigo_cliente, 'SOLICITUD_RESUELTA', :detalle, :id_referencia, :usuario_id)"
            );
            $stmtEvento->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmtEvento->bindParam(":detalle", $detalle, PDO::PARAM_STR);
            $stmtEvento->bindParam(":id_referencia", $datos["id"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":usuario_id", $datos["usuario_resuelve"], PDO::PARAM_INT);
            $stmtEvento->execute();

            $db->commit();

            return array("ok" => true);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return array("ok" => false, "msg" => "No se pudo resolver la solicitud.");
        }
    }

    static public function mdlCerrarDecision($datos)
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "UPDATE decision_credito_pedidojf
                 SET estado = 'CERRADA',
                     resolucion_codigo = :resolucion_codigo,
                     resolucion_comentario = :resolucion_comentario,
                     usuario_resolucion = :usuario_resolucion,
                     fecha_resolucion = NOW()
                 WHERE id = :id
                   AND estado = 'VIGENTE'"
            );
            $stmt->bindParam(":resolucion_codigo", $datos["resolucion_codigo"], PDO::PARAM_STR);
            $stmt->bindParam(":resolucion_comentario", $datos["resolucion_comentario"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario_resolucion", $datos["usuario_resolucion"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() < 1) {
                $db->rollBack();

                return array("ok" => false, "msg" => "La decisión no existe o ya fue cerrada.");
            }

            $detalle = "Resolución: " . $datos["resolucion_codigo"];
            if (!empty($datos["resolucion_comentario"])) {
                $detalle .= " — " . $datos["resolucion_comentario"];
            }

            $stmtEvento = $db->prepare(
                "INSERT INTO decision_credito_eventojf
                    (codigo_pedido, codigo_cliente, tipo_evento, detalle, id_referencia, usuario_id)
                 VALUES
                    (:codigo_pedido, :codigo_cliente, 'DECISION_CERRADA', :detalle, :id_referencia, :usuario_id)"
            );
            $stmtEvento->bindParam(":codigo_pedido", $datos["codigo_pedido"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
            $stmtEvento->bindParam(":detalle", $detalle, PDO::PARAM_STR);
            $stmtEvento->bindParam(":id_referencia", $datos["id"], PDO::PARAM_INT);
            $stmtEvento->bindParam(":usuario_id", $datos["usuario_resolucion"], PDO::PARAM_INT);
            $stmtEvento->execute();

            $db->commit();

            return array("ok" => true);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return array("ok" => false, "msg" => "No se pudo cerrar la decisión.");
        }
    }

    static public function mdlSolicitudPorId($idSolicitud)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM decision_credito_solicitudjf WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(":id", $idSolicitud, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlDecisionPorId($idDecision)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM decision_credito_pedidojf WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(":id", $idDecision, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlEnriquecerDecision(array $decision)
    {
        if (empty($decision)) {
            return null;
        }

        $decision["motivo_etiqueta"] = dcEtiquetaMotivo($decision["motivo_codigo"]);
        $motivo = dcObtenerMotivo($decision["motivo_codigo"]);
        $decision["motivo_severidad"] = $motivo ? $motivo["severidad"] : "media";
        $decision["motivo_categoria"] = $motivo ? $motivo["categoria"] : "";

        if (!isset($decision["usuario_registro_nombre"]) || $decision["usuario_registro_nombre"] === "" || $decision["usuario_registro_nombre"] === null) {
            $decision["usuario_registro_nombre"] = self::nombreUsuario($decision["usuario_registro"]);
        }

        if (!empty($decision["usuario_resolucion"])) {
            if (!isset($decision["usuario_resolucion_nombre"]) || $decision["usuario_resolucion_nombre"] === "" || $decision["usuario_resolucion_nombre"] === null) {
                $decision["usuario_resolucion_nombre"] = self::nombreUsuario($decision["usuario_resolucion"]);
            }
        } else {
            $decision["usuario_resolucion_nombre"] = null;
        }

        $decision["resolucion_etiqueta"] = !empty($decision["resolucion_codigo"])
            ? dcEtiquetaResolucion($decision["resolucion_codigo"])
            : null;
        $decision["solicitudes_permitidas"] = dcSolicitudesPermitidasPorMotivo($decision["motivo_codigo"]);

        return $decision;
    }

    static public function mdlEnriquecerSolicitud(array $solicitud)
    {
        if (empty($solicitud)) {
            return null;
        }

        $solicitud["tipo_etiqueta"] = dcEtiquetaSolicitud($solicitud["tipo_solicitud"]);
        $solicitud["usuario_solicita_nombre"] = self::nombreUsuario($solicitud["usuario_solicita"]);
        $solicitud["usuario_resuelve_nombre"] = !empty($solicitud["usuario_resuelve"])
            ? self::nombreUsuario($solicitud["usuario_resuelve"])
            : null;
        $solicitud["resolucion_etiqueta"] = !empty($solicitud["resolucion_codigo"])
            ? dcEtiquetaResolucion($solicitud["resolucion_codigo"])
            : null;

        return $solicitud;
    }

    static public function mdlEnriquecerEventos(array $eventos)
    {
        $salida = array();

        foreach ($eventos as $evento) {
            $evento["usuario_nombre"] = self::nombreUsuario($evento["usuario_id"]);
            $salida[] = $evento;
        }

        return $salida;
    }

    /*=============================================
    Bitácora unificada decision_credito_accionjf
    =============================================*/
    static public function mdlRegistrarAccion(array $datos)
    {
        $codigoPedido = isset($datos["codigo_pedido"]) ? (int) $datos["codigo_pedido"] : 0;
        $codigoCliente = isset($datos["codigo_cliente"]) ? trim((string) $datos["codigo_cliente"]) : "";
        $tipoAccion = isset($datos["tipo_accion"]) ? strtoupper(trim((string) $datos["tipo_accion"])) : "";
        $usuarioId = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : 0;

        if ($codigoPedido <= 0 || $codigoCliente === "" || $tipoAccion === "" || $usuarioId <= 0) {
            return false;
        }

        $sql = "INSERT INTO decision_credito_accionjf
                    (codigo_pedido, codigo_cliente, tipo_accion, origen,
                     pedido_total, pedido_lista, pedido_estado_resultado,
                     id_decision, motivo_codigo, comentario,
                     id_categoria, categoria_codigo, categoria_entidad, categoria_codigo_entidad,
                     categoria_nombre, cupo_modo, codigo_grupo, nombre_grupo,
                     linea_aprobada, linea_recomendada, linea_referencia,
                     deuda_actual, cupo_disponible, utilizacion_pct,
                     score_riesgo, score_comercial, score_fidelidad, etiqueta_linea,
                     usuario_id, detalle)
                VALUES
                    (:codigo_pedido, :codigo_cliente, :tipo_accion, :origen,
                     :pedido_total, :pedido_lista, :pedido_estado_resultado,
                     :id_decision, :motivo_codigo, :comentario,
                     :id_categoria, :categoria_codigo, :categoria_entidad, :categoria_codigo_entidad,
                     :categoria_nombre, :cupo_modo, :codigo_grupo, :nombre_grupo,
                     :linea_aprobada, :linea_recomendada, :linea_referencia,
                     :deuda_actual, :cupo_disponible, :utilizacion_pct,
                     :score_riesgo, :score_comercial, :score_fidelidad, :etiqueta_linea,
                     :usuario_id, :detalle)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
        $stmt->bindValue(":codigo_cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindValue(":tipo_accion", $tipoAccion, PDO::PARAM_STR);
        $stmt->bindValue(
            ":origen",
            isset($datos["origen"]) && $datos["origen"] !== ""
                ? trim((string) $datos["origen"])
                : "centro_decisiones",
            PDO::PARAM_STR
        );

        self::bindAccionNullable($stmt, ":pedido_total", isset($datos["pedido_total"]) ? $datos["pedido_total"] : null, "float");
        self::bindAccionNullable($stmt, ":pedido_lista", isset($datos["pedido_lista"]) ? $datos["pedido_lista"] : null, "str");
        self::bindAccionNullable($stmt, ":pedido_estado_resultado", isset($datos["pedido_estado_resultado"]) ? $datos["pedido_estado_resultado"] : null, "str");
        self::bindAccionNullable($stmt, ":id_decision", isset($datos["id_decision"]) ? $datos["id_decision"] : null, "int");
        self::bindAccionNullable($stmt, ":motivo_codigo", isset($datos["motivo_codigo"]) ? $datos["motivo_codigo"] : null, "str");
        self::bindAccionNullable($stmt, ":comentario", isset($datos["comentario"]) ? $datos["comentario"] : null, "str");
        self::bindAccionNullable($stmt, ":id_categoria", isset($datos["id_categoria"]) ? $datos["id_categoria"] : null, "int");
        self::bindAccionNullable($stmt, ":categoria_codigo", isset($datos["categoria_codigo"]) ? $datos["categoria_codigo"] : null, "str");
        self::bindAccionNullable($stmt, ":categoria_entidad", isset($datos["categoria_entidad"]) ? $datos["categoria_entidad"] : null, "str");
        self::bindAccionNullable($stmt, ":categoria_codigo_entidad", isset($datos["categoria_codigo_entidad"]) ? $datos["categoria_codigo_entidad"] : null, "str");
        self::bindAccionNullable($stmt, ":categoria_nombre", isset($datos["categoria_nombre"]) ? $datos["categoria_nombre"] : null, "str");
        self::bindAccionNullable($stmt, ":cupo_modo", isset($datos["cupo_modo"]) ? $datos["cupo_modo"] : null, "str");
        self::bindAccionNullable($stmt, ":codigo_grupo", isset($datos["codigo_grupo"]) ? $datos["codigo_grupo"] : null, "str");
        self::bindAccionNullable($stmt, ":nombre_grupo", isset($datos["nombre_grupo"]) ? $datos["nombre_grupo"] : null, "str");
        self::bindAccionNullable($stmt, ":linea_aprobada", isset($datos["linea_aprobada"]) ? $datos["linea_aprobada"] : null, "float");
        self::bindAccionNullable($stmt, ":linea_recomendada", isset($datos["linea_recomendada"]) ? $datos["linea_recomendada"] : null, "float");
        self::bindAccionNullable($stmt, ":linea_referencia", isset($datos["linea_referencia"]) ? $datos["linea_referencia"] : null, "float");
        self::bindAccionNullable($stmt, ":deuda_actual", isset($datos["deuda_actual"]) ? $datos["deuda_actual"] : null, "float");
        self::bindAccionNullable($stmt, ":cupo_disponible", isset($datos["cupo_disponible"]) ? $datos["cupo_disponible"] : null, "float");
        self::bindAccionNullable($stmt, ":utilizacion_pct", isset($datos["utilizacion_pct"]) ? $datos["utilizacion_pct"] : null, "float");
        self::bindAccionNullable($stmt, ":score_riesgo", isset($datos["score_riesgo"]) ? $datos["score_riesgo"] : null, "float");
        self::bindAccionNullable($stmt, ":score_comercial", isset($datos["score_comercial"]) ? $datos["score_comercial"] : null, "float");
        self::bindAccionNullable($stmt, ":score_fidelidad", isset($datos["score_fidelidad"]) ? $datos["score_fidelidad"] : null, "float");
        self::bindAccionNullable($stmt, ":etiqueta_linea", isset($datos["etiqueta_linea"]) ? $datos["etiqueta_linea"] : null, "str");

        $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
        self::bindAccionNullable($stmt, ":detalle", isset($datos["detalle"]) ? $datos["detalle"] : null, "str");

        return $stmt->execute();
    }

    private static function bindAccionNullable($stmt, $param, $valor, $tipo = "str")
    {
        if ($valor === null || $valor === "") {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }

        if ($tipo === "int") {
            $n = (int) $valor;
            if ($n <= 0 && $param !== ":id_decision") {
                // allow 0 for some? id_decision 0 -> null already handled by empty
            }
            if ($n <= 0) {
                $stmt->bindValue($param, null, PDO::PARAM_NULL);
                return;
            }
            $stmt->bindValue($param, $n, PDO::PARAM_INT);
            return;
        }

        if ($tipo === "float") {
            $stmt->bindValue($param, (float) $valor);
            return;
        }

        $stmt->bindValue($param, trim((string) $valor), PDO::PARAM_STR);
    }

    static public function mdlListarAcciones(array $filtros = array())
    {
        $params = array();
        $where = array("1=1");

        $fechaDesde = isset($filtros["fecha_desde"]) ? trim((string) $filtros["fecha_desde"]) : "";
        $fechaHasta = isset($filtros["fecha_hasta"]) ? trim((string) $filtros["fecha_hasta"]) : "";

        if ($fechaDesde !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $where[] = "a.fecha >= :fecha_desde";
            $params[":fecha_desde"] = $fechaDesde . " 00:00:00";
        }

        if ($fechaHasta !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $where[] = "a.fecha <= :fecha_hasta";
            $params[":fecha_hasta"] = $fechaHasta . " 23:59:59";
        }

        $tipo = isset($filtros["tipo_accion"]) ? strtoupper(trim((string) $filtros["tipo_accion"])) : "";
        if ($tipo !== "" && $tipo !== "TODOS") {
            $where[] = "a.tipo_accion = :tipo_accion";
            $params[":tipo_accion"] = $tipo;
        }

        $usuarioId = isset($filtros["usuario_id"]) ? (int) $filtros["usuario_id"] : 0;
        if ($usuarioId > 0) {
            $where[] = "a.usuario_id = :usuario_id";
            $params[":usuario_id"] = $usuarioId;
        }

        $q = isset($filtros["q"]) ? trim((string) $filtros["q"]) : "";
        if ($q !== "") {
            $where[] = "(CAST(a.codigo_pedido AS CHAR) LIKE :q
                OR a.codigo_cliente LIKE :q
                OR IFNULL(c.nombre, '') LIKE :q
                OR IFNULL(a.motivo_codigo, '') LIKE :q
                OR IFNULL(a.comentario, '') LIKE :q)";
            $params[":q"] = "%" . $q . "%";
        }

        $limite = isset($filtros["limite"]) ? (int) $filtros["limite"] : 200;
        $limite = max(20, min(500, $limite));

        $sql = "SELECT
                    a.*,
                    IFNULL(u.nombre, CONCAT('Usuario #', a.usuario_id)) AS usuario_nombre,
                    IFNULL(c.nombre, '') AS cliente_nombre
                FROM decision_credito_accionjf a
                LEFT JOIN usuariosjf u ON u.id = a.usuario_id
                LEFT JOIN clientesjf c ON c.codigo = a.codigo_cliente
                WHERE " . implode(" AND ", $where) . "
                ORDER BY a.fecha DESC, a.id DESC
                LIMIT {$limite}";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $valor) {
            $tipoParam = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $valor, $tipoParam);
        }
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $salida = array();

        foreach ($filas as $fila) {
            $fila["tipo_etiqueta"] = function_exists("dcEtiquetaTipoAccion")
                ? dcEtiquetaTipoAccion($fila["tipo_accion"])
                : $fila["tipo_accion"];
            $fila["tipo_clase"] = function_exists("dcClaseTipoAccion")
                ? dcClaseTipoAccion($fila["tipo_accion"])
                : "default";
            $fila["motivo_etiqueta"] = !empty($fila["motivo_codigo"]) && function_exists("dcEtiquetaMotivo")
                ? dcEtiquetaMotivo($fila["motivo_codigo"])
                : null;

            $codCat = !empty($fila["categoria_codigo"]) ? trim((string) $fila["categoria_codigo"]) : "";
            if ($codCat !== "") {
                $colorBd = "";
                if (class_exists("ModeloCategoriasClientes")) {
                    if (!empty($fila["id_categoria"])) {
                        $catRow = ModeloCategoriasClientes::mdlMostrarCategoria("id", (int) $fila["id_categoria"]);
                        if ($catRow && !empty($catRow["color"])) {
                            $colorBd = $catRow["color"];
                        }
                    }
                    if ($colorBd === "") {
                        $catRow = ModeloCategoriasClientes::mdlMostrarCategoria("codigo", $codCat);
                        if ($catRow && !empty($catRow["color"])) {
                            $colorBd = $catRow["color"];
                        }
                    }
                }
                $fila["categoria_color"] = class_exists("ControladorCategoriasClientes")
                    ? ControladorCategoriasClientes::ctrResolverColorCategoria($colorBd, $codCat)
                    : ($colorBd !== "" ? $colorBd : "#777777");
            } else {
                $fila["categoria_color"] = null;
            }

            $salida[] = $fila;
        }

        return $salida;
    }

    static public function mdlResumenAcciones(array $filtros = array())
    {
        $params = array();
        $where = array("1=1");

        $fechaDesde = isset($filtros["fecha_desde"]) ? trim((string) $filtros["fecha_desde"]) : "";
        $fechaHasta = isset($filtros["fecha_hasta"]) ? trim((string) $filtros["fecha_hasta"]) : "";

        if ($fechaDesde !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $where[] = "fecha >= :fecha_desde";
            $params[":fecha_desde"] = $fechaDesde . " 00:00:00";
        }

        if ($fechaHasta !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $where[] = "fecha <= :fecha_hasta";
            $params[":fecha_hasta"] = $fechaHasta . " 23:59:59";
        }

        $usuarioId = isset($filtros["usuario_id"]) ? (int) $filtros["usuario_id"] : 0;
        if ($usuarioId > 0) {
            $where[] = "usuario_id = :usuario_id";
            $params[":usuario_id"] = $usuarioId;
        }

        $sql = "SELECT
                    tipo_accion,
                    COUNT(*) AS total
                FROM decision_credito_accionjf
                WHERE " . implode(" AND ", $where) . "
                GROUP BY tipo_accion";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $valor) {
            $tipoParam = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $valor, $tipoParam);
        }
        $stmt->execute();

        $resumen = array(
            "APROBADO" => 0,
            "OBJECION" => 0,
            "OBJECION_CERRADA" => 0,
            "ANULADO" => 0,
            "total" => 0,
        );

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tipo = strtoupper((string) $row["tipo_accion"]);
            $n = (int) $row["total"];
            if (isset($resumen[$tipo])) {
                $resumen[$tipo] = $n;
            }
            $resumen["total"] += $n;
        }

        return $resumen;
    }
}
