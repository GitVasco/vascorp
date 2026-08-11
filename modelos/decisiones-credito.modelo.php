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

            $motivoObs = isset($datos["motivo_observacion_codigo"])
                ? trim((string) $datos["motivo_observacion_codigo"])
                : "";

            $stmt = $db->prepare(
                "UPDATE decision_credito_solicitudjf
                 SET estado = :estado,
                     resolucion_codigo = :resolucion_codigo,
                     comentario_resolucion = :comentario_resolucion,
                     motivo_observacion_codigo = :motivo_observacion_codigo,
                     usuario_resuelve = :usuario_resuelve,
                     fecha_resolucion = NOW()
                 WHERE id = :id
                   AND estado IN ('PENDIENTE', 'EN_REVISION')"
            );
            $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
            $stmt->bindParam(":resolucion_codigo", $datos["resolucion_codigo"], PDO::PARAM_STR);
            $stmt->bindParam(":comentario_resolucion", $datos["comentario_resolucion"], PDO::PARAM_STR);
            if ($motivoObs !== "") {
                $stmt->bindValue(":motivo_observacion_codigo", $motivoObs, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":motivo_observacion_codigo", null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(":usuario_resuelve", $datos["usuario_resuelve"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() < 1) {
                $db->rollBack();

                return array("ok" => false, "msg" => "La solicitud no existe o ya fue resuelta.");
            }

            $detalle = $datos["estado"] . " — " . $datos["resolucion_codigo"];
            if ($motivoObs !== "" && function_exists("dcEtiquetaMotivoAprobacion")) {
                $detalle .= " — Motivo: " . dcEtiquetaMotivoAprobacion($motivoObs);
            }
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
        $solicitud["motivo_observacion_etiqueta"] = !empty($solicitud["motivo_observacion_codigo"])
            && function_exists("dcEtiquetaMotivoAprobacion")
            ? dcEtiquetaMotivoAprobacion($solicitud["motivo_observacion_codigo"])
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

        if (!$stmt->execute()) {
            return false;
        }

        $id = (int) Conexion::conectar()->lastInsertId();

        return $id > 0 ? $id : true;
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
                    COALESCE(t.total, a.pedido_total) AS pedido_total_con_igv,
                    IFNULL(u.nombre, CONCAT('Usuario #', a.usuario_id)) AS usuario_nombre,
                    IFNULL(c.nombre, '') AS cliente_nombre
                FROM decision_credito_accionjf a
                LEFT JOIN usuariosjf u ON u.id = a.usuario_id
                LEFT JOIN clientesjf c ON c.codigo = a.codigo_cliente
                LEFT JOIN temporaljf t ON t.codigo = a.codigo_pedido
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
            if (array_key_exists("pedido_total_con_igv", $fila)) {
                if ($fila["pedido_total_con_igv"] !== null && $fila["pedido_total_con_igv"] !== "") {
                    $fila["pedido_total"] = $fila["pedido_total_con_igv"];
                }
                unset($fila["pedido_total_con_igv"]);
            }
            $fila["tipo_etiqueta"] = function_exists("dcEtiquetaTipoAccion")
                ? dcEtiquetaTipoAccion($fila["tipo_accion"])
                : $fila["tipo_accion"];
            $fila["tipo_clase"] = function_exists("dcClaseTipoAccion")
                ? dcClaseTipoAccion($fila["tipo_accion"])
                : "default";
            if (!empty($fila["motivo_codigo"])) {
                $fila["motivo_etiqueta"] = function_exists("dcEtiquetaMotivoAccion")
                    ? dcEtiquetaMotivoAccion($fila["motivo_codigo"])
                    : (function_exists("dcEtiquetaMotivo")
                        ? dcEtiquetaMotivo($fila["motivo_codigo"])
                        : $fila["motivo_codigo"]);
            } else {
                $fila["motivo_etiqueta"] = null;
            }

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

    /**
     * Filtros comunes del dashboard (fechas + vendedor vía temporaljf).
     *
     * @return array{where: string[], join: string, params: array}
     */
    private static function dashFiltrosAcciones(array $filtros, $aliasAccion = "a", $aliasTemporal = "t")
    {
        $where = array("1=1");
        $params = array();
        $join = " LEFT JOIN temporaljf {$aliasTemporal} ON {$aliasTemporal}.codigo = {$aliasAccion}.codigo_pedido ";

        $fechaDesde = isset($filtros["fecha_desde"]) ? trim((string) $filtros["fecha_desde"]) : "";
        $fechaHasta = isset($filtros["fecha_hasta"]) ? trim((string) $filtros["fecha_hasta"]) : "";

        if ($fechaDesde !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $where[] = "{$aliasAccion}.fecha >= :fecha_desde";
            $params[":fecha_desde"] = $fechaDesde . " 00:00:00";
        }

        if ($fechaHasta !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $where[] = "{$aliasAccion}.fecha <= :fecha_hasta";
            $params[":fecha_hasta"] = $fechaHasta . " 23:59:59";
        }

        $vendedor = isset($filtros["vendedor"]) ? trim((string) $filtros["vendedor"]) : "";
        if ($vendedor !== "") {
            $where[] = "{$aliasTemporal}.vendedor = :dash_vendedor";
            $params[":dash_vendedor"] = $vendedor;
        }

        return array(
            "where" => $where,
            "join" => $join,
            "params" => $params,
        );
    }

    private static function dashFiltrosDecision(array $filtros, $aliasDecision = "d", $aliasTemporal = "t")
    {
        $where = array("1=1");
        $params = array();
        $join = " LEFT JOIN temporaljf {$aliasTemporal} ON {$aliasTemporal}.codigo = {$aliasDecision}.codigo_pedido ";

        $vendedor = isset($filtros["vendedor"]) ? trim((string) $filtros["vendedor"]) : "";
        if ($vendedor !== "") {
            $where[] = "{$aliasTemporal}.vendedor = :dash_vendedor";
            $params[":dash_vendedor"] = $vendedor;
        }

        return array(
            "where" => $where,
            "join" => $join,
            "params" => $params,
        );
    }

    private static function dashEjecutar($sql, array $params)
    {
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $valor) {
            $tipoParam = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $valor, $tipoParam);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function dashMontoSolesExpr($alias = "a")
    {
        return "CASE WHEN IFNULL({$alias}.pedido_lista, '') <> 'precio1'
                THEN IFNULL({$alias}.pedido_total, 0) ELSE 0 END";
    }

    private static function dashPeriodoAnterior(array $filtros)
    {
        $fechaDesde = isset($filtros["fecha_desde"]) ? trim((string) $filtros["fecha_desde"]) : "";
        $fechaHasta = isset($filtros["fecha_hasta"]) ? trim((string) $filtros["fecha_hasta"]) : "";

        if (
            $fechaDesde === "" || $fechaHasta === ""
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)
        ) {
            return null;
        }

        try {
            $desde = new DateTime($fechaDesde);
            $hasta = new DateTime($fechaHasta);
        } catch (Exception $e) {
            return null;
        }

        if ($hasta < $desde) {
            return null;
        }

        $dias = (int) $desde->diff($hasta)->days + 1;
        $prevHasta = clone $desde;
        $prevHasta->modify("-1 day");
        $prevDesde = clone $prevHasta;
        if ($dias > 1) {
            $prevDesde->modify("-" . ($dias - 1) . " days");
        }

        return array(
            "fecha_desde" => $prevDesde->format("Y-m-d"),
            "fecha_hasta" => $prevHasta->format("Y-m-d"),
            "dias" => $dias,
        );
    }

    private static function dashDeltaPct($actual, $anterior)
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;
        if ($anterior <= 0) {
            return $actual > 0 ? 100.0 : 0.0;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    private static function dashResumenDecisiones(array $filtros)
    {
        $ff = self::dashFiltrosAcciones($filtros);
        $whereSql = implode(" AND ", $ff["where"]);
        $montoSoles = self::dashMontoSolesExpr("a");

        $sql = "SELECT
                    a.tipo_accion,
                    COUNT(*) AS total,
                    SUM({$montoSoles}) AS monto_soles
                FROM decision_credito_accionjf a
                {$ff["join"]}
                WHERE {$whereSql}
                  AND a.tipo_accion IN ('APROBADO', 'OBJECION', 'OBJECION_CERRADA', 'ANULADO')
                GROUP BY a.tipo_accion";

        $embudo = array(
            "APROBADO" => 0,
            "OBJECION" => 0,
            "OBJECION_CERRADA" => 0,
            "ANULADO" => 0,
        );
        $montoAprobado = 0.0;
        $montoObjetado = 0.0;
        $totalAcciones = 0;

        foreach (self::dashEjecutar($sql, $ff["params"]) as $row) {
            $tipo = strtoupper((string) $row["tipo_accion"]);
            $n = (int) $row["total"];
            $totalAcciones += $n;
            if (isset($embudo[$tipo])) {
                $embudo[$tipo] = $n;
            }
            if ($tipo === "APROBADO") {
                $montoAprobado = (float) $row["monto_soles"];
            } elseif ($tipo === "OBJECION") {
                $montoObjetado = (float) $row["monto_soles"];
            }
        }

        $decisiones = $embudo["APROBADO"] + $embudo["OBJECION"] + $embudo["ANULADO"];

        return array(
            "embudo" => $embudo,
            "decisiones" => $decisiones,
            "total_acciones" => $totalAcciones,
            "monto_aprobado_soles" => $montoAprobado,
            "monto_objetado_soles" => $montoObjetado,
        );
    }

    static public function mdlDashboardGestionCredito(array $filtros = array())
    {
        $resumenActual = self::dashResumenDecisiones($filtros);
        $embudo = $resumenActual["embudo"];
        $montoAprobado = $resumenActual["monto_aprobado_soles"];
        $montoObjetado = $resumenActual["monto_objetado_soles"];
        $decisiones = $resumenActual["decisiones"];
        $totalAccionesPeriodo = $resumenActual["total_acciones"];

        $tasaAprobacion = $decisiones > 0 ? round(($embudo["APROBADO"] / $decisiones) * 100, 1) : 0.0;
        $tasaObjecion = $decisiones > 0 ? round(($embudo["OBJECION"] / $decisiones) * 100, 1) : 0.0;
        $tasaAnulacion = $decisiones > 0 ? round(($embudo["ANULADO"] / $decisiones) * 100, 1) : 0.0;

        $ff = self::dashFiltrosAcciones($filtros);
        $whereSql = implode(" AND ", $ff["where"]);
        $montoSoles = self::dashMontoSolesExpr("a");
        $fd = self::dashFiltrosDecision($filtros);
        $whereDec = implode(" AND ", $fd["where"]);

        $periodoPrev = self::dashPeriodoAnterior($filtros);
        $comparacion = array(
            "periodo_anterior" => null,
            "decisiones_delta_pct" => null,
            "aprobados_delta_pct" => null,
            "acciones_delta_pct" => null,
            "monto_aprobado_delta_pct" => null,
        );
        if ($periodoPrev !== null) {
            $filtrosPrev = array_merge($filtros, array(
                "fecha_desde" => $periodoPrev["fecha_desde"],
                "fecha_hasta" => $periodoPrev["fecha_hasta"],
            ));
            $resumenPrev = self::dashResumenDecisiones($filtrosPrev);
            $comparacion = array(
                "periodo_anterior" => array(
                    "desde" => $periodoPrev["fecha_desde"],
                    "hasta" => $periodoPrev["fecha_hasta"],
                    "dias" => $periodoPrev["dias"],
                ),
                "decisiones_delta_pct" => self::dashDeltaPct($decisiones, $resumenPrev["decisiones"]),
                "aprobados_delta_pct" => self::dashDeltaPct($embudo["APROBADO"], $resumenPrev["embudo"]["APROBADO"]),
                "acciones_delta_pct" => self::dashDeltaPct($totalAccionesPeriodo, $resumenPrev["total_acciones"]),
                "monto_aprobado_delta_pct" => self::dashDeltaPct($montoAprobado, $resumenPrev["monto_aprobado_soles"]),
            );
        }

        $vendedor = isset($filtros["vendedor"]) ? trim((string) $filtros["vendedor"]) : "";
        $joinVend = " LEFT JOIN temporaljf tp ON tp.codigo = a.codigo_pedido ";
        $wherePulso = array("1=1");
        $paramsPulso = array();
        if ($vendedor !== "") {
            $wherePulso[] = "tp.vendedor = :pulso_vendedor";
            $paramsPulso[":pulso_vendedor"] = $vendedor;
        }
        $wherePulsoSql = implode(" AND ", $wherePulso);

        $sqlHoy = "SELECT COUNT(*) AS total
                   FROM decision_credito_accionjf a
                   {$joinVend}
                   WHERE {$wherePulsoSql}
                     AND DATE(a.fecha) = CURDATE()";
        $rowsHoy = self::dashEjecutar($sqlHoy, $paramsPulso);
        $accionesHoy = !empty($rowsHoy[0]["total"]) ? (int) $rowsHoy[0]["total"] : 0;

        $sqlSemana = "SELECT COUNT(*) AS total
                      FROM decision_credito_accionjf a
                      {$joinVend}
                      WHERE {$wherePulsoSql}
                        AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
        $rowsSemana = self::dashEjecutar($sqlSemana, $paramsPulso);
        $accionesSemana = !empty($rowsSemana[0]["total"]) ? (int) $rowsSemana[0]["total"] : 0;

        $sqlUltima = "SELECT MAX(a.fecha) AS ultima
                      FROM decision_credito_accionjf a
                      {$joinVend}
                      WHERE {$wherePulsoSql}";
        $ultimaRow = self::dashEjecutar($sqlUltima, $paramsPulso);
        $ultimaAccion = !empty($ultimaRow[0]["ultima"]) ? (string) $ultimaRow[0]["ultima"] : null;
        $minutosUltima = null;
        if ($ultimaAccion !== null) {
            $minutosUltima = max(0, (int) round((time() - strtotime($ultimaAccion)) / 60));
        }

        $sqlAnalistasHoy = "SELECT COUNT(DISTINCT a.usuario_id) AS total
                            FROM decision_credito_accionjf a
                            {$joinVend}
                            WHERE {$wherePulsoSql}
                              AND DATE(a.fecha) = CURDATE()";
        $rowsAnalistasHoy = self::dashEjecutar($sqlAnalistasHoy, $paramsPulso);
        $analistasHoy = !empty($rowsAnalistasHoy[0]["total"]) ? (int) $rowsAnalistasHoy[0]["total"] : 0;

        $diasPeriodo = 1;
        if ($periodoPrev !== null) {
            $diasPeriodo = max(1, (int) $periodoPrev["dias"]);
        } elseif (!empty($filtros["fecha_desde"]) && !empty($filtros["fecha_hasta"])) {
            try {
                $d1 = new DateTime($filtros["fecha_desde"]);
                $d2 = new DateTime($filtros["fecha_hasta"]);
                $diasPeriodo = max(1, (int) $d1->diff($d2)->days + 1);
            } catch (Exception $e) {
                $diasPeriodo = 1;
            }
        }
        $throughputDia = round($totalAccionesPeriodo / $diasPeriodo, 1);
        $sqlRiesgo = "SELECT
                            COUNT(*) AS total,
                            SUM(CASE WHEN IFNULL(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.total, 0) ELSE 0 END) AS monto_soles
                        FROM decision_credito_pedidojf d
                        {$fd["join"]}
                        WHERE {$whereDec}
                          AND d.estado = 'VIGENTE'";
        $riesgoRow = self::dashEjecutar($sqlRiesgo, $fd["params"]);
        $montoRiesgo = !empty($riesgoRow[0]["monto_soles"]) ? (float) $riesgoRow[0]["monto_soles"] : 0.0;
        $objecionesVigentes = !empty($riesgoRow[0]["total"]) ? (int) $riesgoRow[0]["total"] : 0;

        $sqlTiempo = "SELECT
                            AVG(TIMESTAMPDIFF(HOUR, d.fecha_registro, d.fecha_resolucion)) AS horas_promedio
                        FROM decision_credito_pedidojf d
                        {$fd["join"]}
                        WHERE {$whereDec}
                          AND d.estado = 'CERRADA'
                          AND d.fecha_resolucion IS NOT NULL";
        if (!empty($ff["params"][":fecha_desde"])) {
            $sqlTiempo .= " AND d.fecha_resolucion >= :fecha_desde";
        }
        if (!empty($ff["params"][":fecha_hasta"])) {
            $sqlTiempo .= " AND d.fecha_resolucion <= :fecha_hasta";
        }
        $tiempoRow = self::dashEjecutar($sqlTiempo, array_merge($fd["params"], $ff["params"]));
        $horasResolucion = !empty($tiempoRow[0]["horas_promedio"])
            ? round((float) $tiempoRow[0]["horas_promedio"], 1)
            : null;

        $sqlSerie = "SELECT
                            DATE(a.fecha) AS dia,
                            a.tipo_accion,
                            COUNT(*) AS total,
                            SUM({$montoSoles}) AS monto_soles
                        FROM decision_credito_accionjf a
                        {$ff["join"]}
                        WHERE {$whereSql}
                          AND a.tipo_accion IN ('APROBADO', 'OBJECION', 'ANULADO')
                        GROUP BY DATE(a.fecha), a.tipo_accion
                        ORDER BY dia ASC";
        $rowsSerie = self::dashEjecutar($sqlSerie, $ff["params"]);

        $mapaSerie = array();
        foreach ($rowsSerie as $row) {
            $dia = (string) $row["dia"];
            if (!isset($mapaSerie[$dia])) {
                $mapaSerie[$dia] = array(
                    "fecha" => $dia,
                    "APROBADO" => 0,
                    "OBJECION" => 0,
                    "ANULADO" => 0,
                    "monto_aprobado" => 0.0,
                    "monto_objecion" => 0.0,
                    "monto_anulado" => 0.0,
                );
            }
            $tipo = strtoupper((string) $row["tipo_accion"]);
            if (isset($mapaSerie[$dia][$tipo])) {
                $mapaSerie[$dia][$tipo] = (int) $row["total"];
            }
            $mapaSerie[$dia]["monto_" . strtolower($tipo)] = (float) $row["monto_soles"];
        }
        $serieDiaria = array_values($mapaSerie);

        $sqlMotivos = "SELECT
                            a.motivo_codigo,
                            COUNT(*) AS total,
                            SUM({$montoSoles}) AS monto_soles
                        FROM decision_credito_accionjf a
                        {$ff["join"]}
                        WHERE {$whereSql}
                          AND a.tipo_accion = 'OBJECION'
                          AND IFNULL(a.motivo_codigo, '') <> ''
                        GROUP BY a.motivo_codigo
                        ORDER BY total DESC
                        LIMIT 12";
        $rowsMotivos = self::dashEjecutar($sqlMotivos, $ff["params"]);
        $totalMotivos = 0;
        foreach ($rowsMotivos as $row) {
            $totalMotivos += (int) $row["total"];
        }
        $motivos = array();
        foreach ($rowsMotivos as $row) {
            $codigo = (string) $row["motivo_codigo"];
            $n = (int) $row["total"];
            $motivoCfg = function_exists("dcObtenerMotivo") ? dcObtenerMotivo($codigo) : null;
            $motivos[] = array(
                "codigo" => $codigo,
                "etiqueta" => function_exists("dcEtiquetaMotivo") ? dcEtiquetaMotivo($codigo) : $codigo,
                "categoria" => $motivoCfg && isset($motivoCfg["categoria"]) ? $motivoCfg["categoria"] : "",
                "severidad" => $motivoCfg && isset($motivoCfg["severidad"]) ? $motivoCfg["severidad"] : "media",
                "total" => $n,
                "monto_soles" => round((float) $row["monto_soles"], 2),
                "pct" => $totalMotivos > 0 ? round(($n / $totalMotivos) * 100, 1) : 0.0,
            );
        }

        $sqlAnalistas = "SELECT
                            a.usuario_id,
                            IFNULL(u.nombre, CONCAT('Usuario #', a.usuario_id)) AS usuario_nombre,
                            SUM(CASE WHEN a.tipo_accion = 'APROBADO' THEN 1 ELSE 0 END) AS aprobados,
                            SUM(CASE WHEN a.tipo_accion = 'OBJECION' THEN 1 ELSE 0 END) AS objeciones,
                            SUM(CASE WHEN a.tipo_accion = 'ANULADO' THEN 1 ELSE 0 END) AS anulados,
                            SUM(CASE WHEN a.tipo_accion = 'OBJECION_CERRADA' THEN 1 ELSE 0 END) AS cerradas,
                            COUNT(*) AS total_acciones,
                            SUM(CASE WHEN a.tipo_accion = 'APROBADO' THEN {$montoSoles} ELSE 0 END) AS monto_aprobado_soles
                        FROM decision_credito_accionjf a
                        {$ff["join"]}
                        LEFT JOIN usuariosjf u ON u.id = a.usuario_id
                        WHERE {$whereSql}
                        GROUP BY a.usuario_id, u.nombre
                        HAVING total_acciones > 0
                        ORDER BY total_acciones DESC, aprobados DESC
                        LIMIT 15";
        $rowsAnalistas = self::dashEjecutar($sqlAnalistas, $ff["params"]);
        $analistas = array();
        foreach ($rowsAnalistas as $row) {
            $ap = (int) $row["aprobados"];
            $ob = (int) $row["objeciones"];
            $dec = $ap + $ob + (int) $row["anulados"];
            $analistas[] = array(
                "usuario_id" => (int) $row["usuario_id"],
                "usuario_nombre" => (string) $row["usuario_nombre"],
                "aprobados" => $ap,
                "objeciones" => $ob,
                "anulados" => (int) $row["anulados"],
                "cerradas" => (int) $row["cerradas"],
                "total_acciones" => (int) $row["total_acciones"],
                "monto_aprobado_soles" => round((float) $row["monto_aprobado_soles"], 2),
                "pct_aprobacion" => $dec > 0 ? round(($ap / $dec) * 100, 1) : 0.0,
            );
        }

        $sqlClientes = "SELECT
                            a.codigo_cliente,
                            IFNULL(c.nombre, '') AS cliente_nombre,
                            COUNT(*) AS objeciones,
                            SUM({$montoSoles}) AS monto_soles,
                            MAX(a.fecha) AS ultima_fecha,
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(a.motivo_codigo ORDER BY a.fecha DESC SEPARATOR ','),
                                ',', 1
                            ) AS ultimo_motivo_codigo
                        FROM decision_credito_accionjf a
                        {$ff["join"]}
                        LEFT JOIN clientesjf c ON c.codigo = a.codigo_cliente
                        WHERE {$whereSql}
                          AND a.tipo_accion = 'OBJECION'
                        GROUP BY a.codigo_cliente, c.nombre
                        ORDER BY objeciones DESC, monto_soles DESC
                        LIMIT 15";
        $rowsClientes = self::dashEjecutar($sqlClientes, $ff["params"]);
        $clientesObjetados = array();
        foreach ($rowsClientes as $row) {
            $ultMot = (string) $row["ultimo_motivo_codigo"];
            $clientesObjetados[] = array(
                "codigo_cliente" => (string) $row["codigo_cliente"],
                "cliente_nombre" => (string) $row["cliente_nombre"],
                "objeciones" => (int) $row["objeciones"],
                "monto_soles" => round((float) $row["monto_soles"], 2),
                "ultima_fecha" => (string) $row["ultima_fecha"],
                "ultimo_motivo" => $ultMot !== "" && function_exists("dcEtiquetaMotivo")
                    ? dcEtiquetaMotivo($ultMot)
                    : $ultMot,
            );
        }

        $diasMin = isset($filtros["dias_abiertos"]) ? max(1, (int) $filtros["dias_abiertos"]) : 3;
        $sqlAbiertas = "SELECT
                            d.id,
                            d.codigo_pedido,
                            d.codigo_cliente,
                            IFNULL(c.nombre, '') AS cliente_nombre,
                            d.motivo_codigo,
                            d.comentario,
                            d.fecha_registro,
                            DATEDIFF(CURDATE(), DATE(d.fecha_registro)) AS dias_abierta,
                            IFNULL(u.nombre, CONCAT('Usuario #', d.usuario_registro)) AS usuario_nombre,
                            CASE WHEN IFNULL(t.lista, '') <> 'precio1'
                                THEN IFNULL(t.total, 0) ELSE 0 END AS monto_soles,
                            IFNULL(t.lista, '') AS pedido_lista,
                            IFNULL(t.total, 0) AS pedido_total
                        FROM decision_credito_pedidojf d
                        {$fd["join"]}
                        LEFT JOIN clientesjf c ON c.codigo = d.codigo_cliente
                        LEFT JOIN usuariosjf u ON u.id = d.usuario_registro
                        WHERE {$whereDec}
                          AND d.estado = 'VIGENTE'
                          AND DATEDIFF(CURDATE(), DATE(d.fecha_registro)) >= :dias_min
                        ORDER BY dias_abierta DESC, d.fecha_registro ASC
                        LIMIT 30";
        $paramsAbiertas = array_merge($fd["params"], array(":dias_min" => $diasMin));
        $rowsAbiertas = self::dashEjecutar($sqlAbiertas, $paramsAbiertas);
        $objecionesAbiertas = array();
        foreach ($rowsAbiertas as $row) {
            $mot = (string) $row["motivo_codigo"];
            $objecionesAbiertas[] = array(
                "id" => (int) $row["id"],
                "codigo_pedido" => (int) $row["codigo_pedido"],
                "codigo_cliente" => (string) $row["codigo_cliente"],
                "cliente_nombre" => (string) $row["cliente_nombre"],
                "motivo_codigo" => $mot,
                "motivo_etiqueta" => $mot !== "" && function_exists("dcEtiquetaMotivo")
                    ? dcEtiquetaMotivo($mot)
                    : $mot,
                "dias_abierta" => (int) $row["dias_abierta"],
                "fecha_registro" => (string) $row["fecha_registro"],
                "usuario_nombre" => (string) $row["usuario_nombre"],
                "monto_soles" => round((float) $row["monto_soles"], 2),
                "pedido_lista" => (string) $row["pedido_lista"],
                "pedido_total" => $row["pedido_total"] !== null ? (float) $row["pedido_total"] : null,
            );
        }

        $whereCola = array("UPPER(IFNULL(t.estado, '')) = 'GENERADO'");
        $paramsCola = array();
        if ($vendedor !== "") {
            $whereCola[] = "t.vendedor = :cola_vendedor";
            $paramsCola[":cola_vendedor"] = $vendedor;
        }
        $sqlColaSalud = "SELECT
                            COUNT(*) AS generados,
                            AVG(DATEDIFF(CURDATE(), DATE(t.fecha))) AS dias_promedio,
                            MAX(DATEDIFF(CURDATE(), DATE(t.fecha))) AS dias_max,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(t.fecha)) >= 1 THEN 1 ELSE 0 END) AS sin_atender_24h,
                            SUM(CASE WHEN IFNULL(t.lista, '') <> 'precio1' THEN IFNULL(t.total, 0) ELSE 0 END) AS monto_soles
                         FROM temporaljf t
                         WHERE " . implode(" AND ", $whereCola);
        $colaRow = self::dashEjecutar($sqlColaSalud, $paramsCola);
        $colaSalud = array(
            "generados" => !empty($colaRow[0]["generados"]) ? (int) $colaRow[0]["generados"] : 0,
            "dias_promedio" => !empty($colaRow[0]["dias_promedio"]) ? round((float) $colaRow[0]["dias_promedio"], 1) : 0.0,
            "dias_max" => !empty($colaRow[0]["dias_max"]) ? (int) $colaRow[0]["dias_max"] : 0,
            "sin_atender_24h" => !empty($colaRow[0]["sin_atender_24h"]) ? (int) $colaRow[0]["sin_atender_24h"] : 0,
            "monto_soles" => !empty($colaRow[0]["monto_soles"]) ? round((float) $colaRow[0]["monto_soles"], 2) : 0.0,
        );

        $sqlSla = "SELECT
                        COUNT(*) AS total_cerradas,
                        SUM(CASE WHEN TIMESTAMPDIFF(HOUR, d.fecha_registro, d.fecha_resolucion) <= 48 THEN 1 ELSE 0 END) AS en_48h
                   FROM decision_credito_pedidojf d
                   {$fd["join"]}
                   WHERE {$whereDec}
                     AND d.estado = 'CERRADA'
                     AND d.fecha_resolucion IS NOT NULL";
        if (!empty($ff["params"][":fecha_desde"])) {
            $sqlSla .= " AND d.fecha_resolucion >= :fecha_desde";
        }
        if (!empty($ff["params"][":fecha_hasta"])) {
            $sqlSla .= " AND d.fecha_resolucion <= :fecha_hasta";
        }
        $slaRow = self::dashEjecutar($sqlSla, array_merge($fd["params"], $ff["params"]));
        $totalCerradas = !empty($slaRow[0]["total_cerradas"]) ? (int) $slaRow[0]["total_cerradas"] : 0;
        $cerradas48 = !empty($slaRow[0]["en_48h"]) ? (int) $slaRow[0]["en_48h"] : 0;
        $pctSla48 = $totalCerradas > 0 ? round(($cerradas48 / $totalCerradas) * 100, 1) : null;

        $sqlActHora = "SELECT HOUR(a.fecha) AS hora, COUNT(*) AS total
                       FROM decision_credito_accionjf a
                       {$ff["join"]}
                       WHERE {$whereSql}
                       GROUP BY HOUR(a.fecha)
                       ORDER BY hora ASC";
        $actividadHora = array();
        foreach (self::dashEjecutar($sqlActHora, $ff["params"]) as $row) {
            $actividadHora[] = array(
                "hora" => (int) $row["hora"],
                "total" => (int) $row["total"],
            );
        }

        $sqlActDow = "SELECT DAYOFWEEK(a.fecha) AS dow, COUNT(*) AS total
                      FROM decision_credito_accionjf a
                      {$ff["join"]}
                      WHERE {$whereSql}
                      GROUP BY DAYOFWEEK(a.fecha)
                      ORDER BY dow ASC";
        $nombresDow = array("", "Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb");
        $actividadDow = array();
        foreach (self::dashEjecutar($sqlActDow, $ff["params"]) as $row) {
            $dow = (int) $row["dow"];
            $actividadDow[] = array(
                "dow" => $dow,
                "label" => isset($nombresDow[$dow]) ? $nombresDow[$dow] : (string) $dow,
                "total" => (int) $row["total"],
            );
        }

        $sqlUltimas = "SELECT
                            a.fecha,
                            a.tipo_accion,
                            a.codigo_pedido,
                            a.codigo_cliente,
                            a.motivo_codigo,
                            a.pedido_total,
                            a.pedido_lista,
                            IFNULL(u.nombre, CONCAT('Usuario #', a.usuario_id)) AS usuario_nombre,
                            IFNULL(c.nombre, '') AS cliente_nombre
                       FROM decision_credito_accionjf a
                       {$ff["join"]}
                       LEFT JOIN usuariosjf u ON u.id = a.usuario_id
                       LEFT JOIN clientesjf c ON c.codigo = a.codigo_cliente
                       WHERE {$whereSql}
                       ORDER BY a.fecha DESC, a.id DESC
                       LIMIT 20";
        $ultimasGestiones = array();
        foreach (self::dashEjecutar($sqlUltimas, $ff["params"]) as $row) {
            $tipo = strtoupper((string) $row["tipo_accion"]);
            $mot = !empty($row["motivo_codigo"]) ? (string) $row["motivo_codigo"] : "";
            $ultimasGestiones[] = array(
                "fecha" => (string) $row["fecha"],
                "tipo_accion" => $tipo,
                "tipo_etiqueta" => function_exists("dcEtiquetaTipoAccion")
                    ? dcEtiquetaTipoAccion($tipo)
                    : $tipo,
                "tipo_clase" => function_exists("dcClaseTipoAccion") ? dcClaseTipoAccion($tipo) : "default",
                "codigo_pedido" => (int) $row["codigo_pedido"],
                "codigo_cliente" => (string) $row["codigo_cliente"],
                "cliente_nombre" => (string) $row["cliente_nombre"],
                "usuario_nombre" => (string) $row["usuario_nombre"],
                "motivo_etiqueta" => $mot !== "" && function_exists("dcEtiquetaMotivo")
                    ? dcEtiquetaMotivo($mot)
                    : "",
                "pedido_total" => $row["pedido_total"] !== null ? (float) $row["pedido_total"] : null,
                "pedido_lista" => (string) $row["pedido_lista"],
            );
        }

        return array(
            "kpis" => array(
                "tasa_aprobacion_pct" => $tasaAprobacion,
                "tasa_objecion_pct" => $tasaObjecion,
                "tasa_anulacion_pct" => $tasaAnulacion,
                "decisiones_total" => $decisiones,
                "conteo_aprobado" => (int) $embudo["APROBADO"],
                "conteo_objecion" => (int) $embudo["OBJECION"],
                "conteo_anulado" => (int) $embudo["ANULADO"],
                "acciones_total" => $totalAccionesPeriodo,
                "throughput_dia" => $throughputDia,
                "monto_aprobado_soles" => round($montoAprobado, 2),
                "monto_objetado_soles" => round($montoObjetado, 2),
                "monto_riesgo_soles" => round($montoRiesgo, 2),
                "objeciones_vigentes" => $objecionesVigentes,
                "tiempo_resolucion_horas" => $horasResolucion,
                "pct_sla_48h" => $pctSla48,
            ),
            "pulso" => array(
                "acciones_hoy" => $accionesHoy,
                "acciones_semana" => $accionesSemana,
                "ultima_accion" => $ultimaAccion,
                "minutos_desde_ultima" => $minutosUltima,
                "analistas_activos_hoy" => $analistasHoy,
                "equipo_activo" => ($accionesHoy > 0 || ($minutosUltima !== null && $minutosUltima <= 120)),
            ),
            "comparacion" => $comparacion,
            "cola_salud" => $colaSalud,
            "embudo" => $embudo,
            "serie_diaria" => $serieDiaria,
            "motivos" => $motivos,
            "analistas" => $analistas,
            "clientes_objetados" => $clientesObjetados,
            "objeciones_abiertas" => $objecionesAbiertas,
            "actividad_hora" => $actividadHora,
            "actividad_dow" => $actividadDow,
            "ultimas_gestiones" => $ultimasGestiones,
            "dias_abiertos_min" => $diasMin,
        );
    }

    /*=============================================
    Controles post-aprobación
    =============================================*/
    static public function mdlRegistrarControlPostAprobacion(array $datos)
    {
        $codigoPedido = isset($datos["codigo_pedido"]) ? (int) $datos["codigo_pedido"] : 0;
        $codigoCliente = isset($datos["codigo_cliente"]) ? trim((string) $datos["codigo_cliente"]) : "";
        $condicionCodigo = isset($datos["condicion_codigo"])
            ? strtoupper(trim((string) $datos["condicion_codigo"]))
            : "";
        $usuarioId = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : 0;

        if ($codigoPedido <= 0 || $codigoCliente === "" || $condicionCodigo === "" || $usuarioId <= 0) {
            return array("ok" => false, "msg" => "Datos incompletos para registrar el control.");
        }

        $pendiente = self::mdlControlPendientePorPedido($codigoPedido);
        if ($pendiente) {
            return array("ok" => false, "msg" => "El pedido ya tiene un control post-aprobación pendiente.");
        }

        $areaCodigo = isset($datos["area_autoriza_codigo"])
            ? strtoupper(trim((string) $datos["area_autoriza_codigo"]))
            : "";
        $comentario = isset($datos["comentario"]) ? trim((string) $datos["comentario"]) : "";
        $bloqueaApt = isset($datos["bloquea_apt"]) ? (int) $datos["bloquea_apt"] : 1;
        $idAccion = isset($datos["id_accion_aprobacion"]) ? (int) $datos["id_accion_aprobacion"] : 0;

        $sql = "INSERT INTO decision_credito_controljf
                    (codigo_pedido, codigo_cliente, id_accion_aprobacion,
                     condicion_codigo, area_autoriza_codigo, comentario,
                     estado, bloquea_apt, usuario_registra)
                VALUES
                    (:codigo_pedido, :codigo_cliente, :id_accion_aprobacion,
                     :condicion_codigo, :area_autoriza_codigo, :comentario,
                     'PENDIENTE', :bloquea_apt, :usuario_registra)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
        $stmt->bindValue(":codigo_cliente", $codigoCliente, PDO::PARAM_STR);
        if ($idAccion > 0) {
            $stmt->bindValue(":id_accion_aprobacion", $idAccion, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":id_accion_aprobacion", null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(":condicion_codigo", $condicionCodigo, PDO::PARAM_STR);
        if ($areaCodigo !== "") {
            $stmt->bindValue(":area_autoriza_codigo", $areaCodigo, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(":area_autoriza_codigo", null, PDO::PARAM_NULL);
        }
        if ($comentario !== "") {
            $stmt->bindValue(":comentario", $comentario, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(":comentario", null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(":bloquea_apt", $bloqueaApt ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(":usuario_registra", $usuarioId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return array("ok" => false, "msg" => "No se pudo registrar el control.");
        }

        return array(
            "ok" => true,
            "id" => (int) Conexion::conectar()->lastInsertId(),
        );
    }

    /**
     * Primera fecha de aprobación del pedido (bitácora decision_credito_accionjf).
     * Null si no hay fila APROBADO o la tabla no existe.
     */
    static public function mdlFechaAprobacionPedido($codigoPedido)
    {
        $codigoPedido = (int) $codigoPedido;
        if ($codigoPedido <= 0) {
            return null;
        }

        try {
            $sql = "SELECT fecha
                    FROM decision_credito_accionjf
                    WHERE codigo_pedido = :codigo_pedido
                      AND tipo_accion = 'APROBADO'
                    ORDER BY fecha ASC, id ASC
                    LIMIT 1";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindValue(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }

        if (!$row || empty($row["fecha"])) {
            return null;
        }

        return (string) $row["fecha"];
    }

    static public function mdlControlPendientePorPedido($codigoPedido)
    {
        $codigoPedido = (int) $codigoPedido;
        if ($codigoPedido <= 0) {
            return null;
        }

        try {
            $sql = "SELECT c.*,
                        IFNULL(ur.nombre, CONCAT('Usuario #', c.usuario_registra)) AS usuario_registra_nombre,
                        t.total AS pedido_total,
                        t.lista AS pedido_lista,
                        t.estado AS pedido_estado,
                        IFNULL(cl.nombre, c.codigo_cliente) AS cliente_nombre
                    FROM decision_credito_controljf c
                    LEFT JOIN temporaljf t ON t.codigo = c.codigo_pedido
                    LEFT JOIN clientesjf cl ON cl.codigo = c.codigo_cliente
                    LEFT JOIN usuariosjf ur ON ur.id = c.usuario_registra
                    WHERE c.codigo_pedido = :codigo_pedido
                      AND c.estado = 'PENDIENTE'
                    ORDER BY c.id DESC
                    LIMIT 1";
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindValue(":codigo_pedido", $codigoPedido, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }

        return $row ? self::mdlEnriquecerControlPostAprobacion($row) : null;
    }

    static public function mdlControlBloqueantePendiente($codigoPedido)
    {
        $control = self::mdlControlPendientePorPedido($codigoPedido);
        if (!$control || (int) $control["bloquea_apt"] !== 1) {
            return null;
        }

        $etiqueta = function_exists("dcEtiquetaControlPostAprobacion")
            ? dcEtiquetaControlPostAprobacion($control["condicion_codigo"])
            : $control["condicion_codigo"];
        $area = !empty($control["area_autoriza_codigo"]) && function_exists("dcEtiquetaAreaAutorizacion")
            ? dcEtiquetaAreaAutorizacion($control["area_autoriza_codigo"])
            : "";
        $msg = "Pedido con control pendiente: " . $etiqueta . ".";
        if ($area !== "") {
            $msg .= " Autorizado por: " . $area . ".";
        }
        if (!empty($control["comentario"])) {
            $msg .= " " . $control["comentario"];
        }
        $msg .= " Créditos debe liberarlo en Historial de crédito antes de facturar.";

        return array(
            "control" => $control,
            "mensaje" => $msg,
        );
    }

    static public function mdlMapaControlesPendientesPorPedidos(array $codigosPedido)
    {
        $codigos = array();
        foreach ($codigosPedido as $cod) {
            $n = (int) $cod;
            if ($n > 0) {
                $codigos[$n] = $n;
            }
        }

        if (empty($codigos)) {
            return array();
        }

        try {
            $placeholders = implode(",", array_fill(0, count($codigos), "?"));
            $sql = "SELECT c.*
                    FROM decision_credito_controljf c
                    WHERE c.estado = 'PENDIENTE'
                      AND c.codigo_pedido IN ($placeholders)";
            $stmt = Conexion::conectar()->prepare($sql);
            $i = 1;
            foreach ($codigos as $cod) {
                $stmt->bindValue($i++, $cod, PDO::PARAM_INT);
            }
            $stmt->execute();
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array();
        }

        $mapa = array();
        foreach ($filas as $fila) {
            $fila = self::mdlEnriquecerControlPostAprobacion($fila);
            $mapa[(int) $fila["codigo_pedido"]] = $fila;
        }

        return $mapa;
    }

    static public function mdlTablaControlesPostAprobacionDisponible()
    {
        try {
            $stmt = Conexion::conectar()->query("SELECT 1 FROM decision_credito_controljf LIMIT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    static public function mdlControlesPostAprobacionSqlWhere(array $filtros = array(), array &$params = array())
    {
        $where = array("c.estado = 'PENDIENTE'");

        $vendedor = isset($filtros["vendedor"]) ? trim((string) $filtros["vendedor"]) : "";
        if ($vendedor !== "" && empty($filtros["sin_filtro_vendedor"])) {
            $where[] = "t.vendedor = :vendedor";
            $params[":vendedor"] = $vendedor;
        }

        return implode(" AND ", $where);
    }

    static public function mdlContarControlesPostAprobacionPendientes($vendedor = null)
    {
        $filtros = array("sin_filtro_vendedor" => true);
        if ($vendedor !== null && trim((string) $vendedor) !== "") {
            unset($filtros["sin_filtro_vendedor"]);
            $filtros["vendedor"] = trim((string) $vendedor);
        }

        $params = array();
        $sqlWhere = self::mdlControlesPostAprobacionSqlWhere($filtros, $params);

        try {
            $sql = "SELECT COUNT(*) AS total
                    FROM decision_credito_controljf c
                    LEFT JOIN temporaljf t ON t.codigo = c.codigo_pedido
                    WHERE {$sqlWhere}";
            $stmt = Conexion::conectar()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return 0;
        }

        return isset($row["total"]) ? (int) $row["total"] : 0;
    }

    static public function mdlListarControlesPostAprobacion(array $filtros = array())
    {
        $params = array();
        $sqlWhere = self::mdlControlesPostAprobacionSqlWhere($filtros, $params);

        $limite = isset($filtros["limite"]) ? (int) $filtros["limite"] : 100;
        $limite = max(20, min(300, $limite));

        try {
            $sql = "SELECT c.*,
                        t.total AS pedido_total,
                        t.lista AS pedido_lista,
                        t.estado AS pedido_estado,
                        t.vendedor,
                        t.fecha AS pedido_fecha,
                        IFNULL(cl.nombre, c.codigo_cliente) AS cliente_nombre,
                        IFNULL(ur.nombre, CONCAT('Usuario #', c.usuario_registra)) AS usuario_registra_nombre,
                        IFNULL(ven.descripcion, t.vendedor) AS nom_vendedor,
                        DATEDIFF(CURDATE(), DATE(c.fecha_registro)) AS dias_pendiente
                    FROM decision_credito_controljf c
                    LEFT JOIN temporaljf t ON t.codigo = c.codigo_pedido
                    LEFT JOIN clientesjf cl ON cl.codigo = c.codigo_cliente
                    LEFT JOIN maestrajf ven
                        ON ven.codigo = t.vendedor
                       AND ven.tipo_dato = 'TVEND'
                    LEFT JOIN usuariosjf ur ON ur.id = c.usuario_registra
                    WHERE {$sqlWhere}
                    ORDER BY c.fecha_registro ASC
                    LIMIT {$limite}";
            $stmt = Conexion::conectar()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array(
                "__list_error__" => $e->getMessage(),
            );
        }

        $salida = array();
        foreach ($filas as $fila) {
            $salida[] = self::mdlEnriquecerControlPostAprobacion($fila);
        }

        return $salida;
    }

    static public function mdlLiberarControlPostAprobacion(array $datos)
    {
        $id = isset($datos["id"]) ? (int) $datos["id"] : 0;
        $usuarioId = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : 0;
        $comentario = isset($datos["comentario_liberacion"])
            ? trim((string) $datos["comentario_liberacion"])
            : "";
        $areaCodigo = isset($datos["area_autoriza_codigo"])
            ? strtoupper(trim((string) $datos["area_autoriza_codigo"]))
            : "";

        if ($id <= 0 || $usuarioId <= 0) {
            return array("ok" => false, "msg" => "Datos incompletos.");
        }

        if ($areaCodigo !== "" && function_exists("dcObtenerAreaAutorizacion") && !dcObtenerAreaAutorizacion($areaCodigo)) {
            return array("ok" => false, "msg" => "Área de autorización no válida.");
        }

        try {
            $sqlSel = "SELECT * FROM decision_credito_controljf WHERE id = :id AND estado = 'PENDIENTE' LIMIT 1";
            $stmtSel = Conexion::conectar()->prepare($sqlSel);
            $stmtSel->bindValue(":id", $id, PDO::PARAM_INT);
            $stmtSel->execute();
            $control = $stmtSel->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array("ok" => false, "msg" => "No se pudo consultar el control.");
        }

        if (!$control) {
            return array("ok" => false, "msg" => "Control no encontrado o ya fue liberado.");
        }

        $sql = "UPDATE decision_credito_controljf
                SET estado = 'LIBERADO',
                    usuario_liberacion = :usuario_liberacion,
                    comentario_liberacion = :comentario_liberacion,
                    area_autoriza_codigo = CASE
                        WHEN :area <> '' THEN :area
                        ELSE area_autoriza_codigo
                    END,
                    fecha_liberacion = NOW()
                WHERE id = :id AND estado = 'PENDIENTE'";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":usuario_liberacion", $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(":area", $areaCodigo, PDO::PARAM_STR);
        if ($comentario !== "") {
            $stmt->bindValue(":comentario_liberacion", $comentario, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(":comentario_liberacion", null, PDO::PARAM_NULL);
        }

        if (!$stmt->execute() || $stmt->rowCount() <= 0) {
            return array("ok" => false, "msg" => "No se pudo liberar el control.");
        }

        try {
            $stmtSel2 = Conexion::conectar()->prepare(
                "SELECT * FROM decision_credito_controljf WHERE id = :id LIMIT 1"
            );
            $stmtSel2->bindValue(":id", $id, PDO::PARAM_INT);
            $stmtSel2->execute();
            $control = $stmtSel2->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $control = null;
        }

        return array(
            "ok" => true,
            "control" => $control
                ? self::mdlEnriquecerControlPostAprobacion($control)
                : array(),
        );
    }

    static public function mdlEnriquecerControlPostAprobacion(array $control)
    {
        $control["condicion_etiqueta"] = function_exists("dcEtiquetaControlPostAprobacion")
            ? dcEtiquetaControlPostAprobacion($control["condicion_codigo"])
            : $control["condicion_codigo"];
        $control["area_etiqueta"] = !empty($control["area_autoriza_codigo"]) && function_exists("dcEtiquetaAreaAutorizacion")
            ? dcEtiquetaAreaAutorizacion($control["area_autoriza_codigo"])
            : null;
        $control["bloquea_apt"] = isset($control["bloquea_apt"]) ? (int) $control["bloquea_apt"] : 1;

        return $control;
    }
}
