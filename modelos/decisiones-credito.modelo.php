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
        $sql = "SELECT d.*
                FROM decision_credito_pedidojf d
                INNER JOIN (
                    SELECT codigo_pedido, MAX(id) AS max_id
                    FROM decision_credito_pedidojf
                    WHERE estado = 'VIGENTE'
                      AND codigo_pedido IN ($placeholders)
                    GROUP BY codigo_pedido
                ) ult ON ult.max_id = d.id";

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
        $decision["usuario_registro_nombre"] = self::nombreUsuario($decision["usuario_registro"]);
        $decision["usuario_resolucion_nombre"] = !empty($decision["usuario_resolucion"])
            ? self::nombreUsuario($decision["usuario_resolucion"])
            : null;
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
}
