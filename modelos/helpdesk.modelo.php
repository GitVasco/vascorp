<?php

require_once "conexion.php";

class ModeloHelpdesk
{
    public static function mdlListar($filtros)
    {
        $sql = "SELECT
                    t.id,
                    t.titulo,
                    t.tipo,
                    t.prioridad,
                    t.estado,
                    t.modulo,
                    t.sistema,
                    t.area,
                    t.solicitante_id,
                    t.asignado_id,
                    t.creado_por_id,
                    t.creado_en,
                    t.actualizado_en,
                    t.cerrado_en,
                    t.fecha_estimada,
                    t.sla_exento,
                    t.sla_exento_motivo,
                    sol.nombre AS solicitante_nombre,
                    asi.nombre AS asignado_nombre
                FROM helpdesk_ticketjf t
                LEFT JOIN usuariosjf sol ON sol.id = t.solicitante_id
                LEFT JOIN usuariosjf asi ON asi.id = t.asignado_id
                WHERE 1 = 1";
        $params = array();

        if (!empty($filtros["estado"])) {
            $sql .= " AND t.estado = :estado";
            $params[":estado"] = $filtros["estado"];
        }

        if (!empty($filtros["tipo"])) {
            $sql .= " AND t.tipo = :tipo";
            $params[":tipo"] = $filtros["tipo"];
        }

        if (!empty($filtros["sistema"])) {
            $sql .= " AND t.sistema = :sistema";
            $params[":sistema"] = $filtros["sistema"];
        }

        if (!empty($filtros["prioridad"])) {
            $sql .= " AND t.prioridad = :prioridad";
            $params[":prioridad"] = $filtros["prioridad"];
        }

        if (!empty($filtros["area"])) {
            $sql .= " AND t.area = :area";
            $params[":area"] = $filtros["area"];
        }

        if (!empty($filtros["asignado_id"])) {
            $sql .= " AND t.asignado_id = :asignado_id";
            $params[":asignado_id"] = (int) $filtros["asignado_id"];
        }

        if (!empty($filtros["solicitante_id"])) {
            $sql .= " AND t.solicitante_id = :solicitante_id";
            $params[":solicitante_id"] = (int) $filtros["solicitante_id"];
        }

        if (!empty($filtros["asignado_mio_o_libre"])) {
            $sql .= " AND (t.asignado_id = :amid_libre OR t.asignado_id IS NULL)";
            $params[":amid_libre"] = (int) $filtros["asignado_mio_o_libre"];
        }

        if (!empty($filtros["q"])) {
            $sql .= " AND (
                t.titulo LIKE :q
                OR t.descripcion LIKE :q
                OR IFNULL(t.modulo, '') LIKE :q
                OR IFNULL(t.area, '') LIKE :q
                OR CAST(t.id AS CHAR) = :q_exact
            )";
            $params[":q"] = "%" . $filtros["q"] . "%";
            $params[":q_exact"] = $filtros["q"];
        }

        if (!empty($filtros["solo_abiertos"])) {
            $sql .= " AND t.estado <> 'CERRADO'";
        }

        $sql .= " ORDER BY t.id DESC
                  LIMIT 200";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteos por estado (sin filtrar por estado/solo_abiertos).
     */
    public static function mdlResumen($filtros)
    {
        $sql = "SELECT t.estado, COUNT(*) AS total
                FROM helpdesk_ticketjf t
                WHERE 1 = 1";
        $params = array();

        if (!empty($filtros["tipo"])) {
            $sql .= " AND t.tipo = :tipo";
            $params[":tipo"] = $filtros["tipo"];
        }
        if (!empty($filtros["sistema"])) {
            $sql .= " AND t.sistema = :sistema";
            $params[":sistema"] = $filtros["sistema"];
        }
        if (!empty($filtros["prioridad"])) {
            $sql .= " AND t.prioridad = :prioridad";
            $params[":prioridad"] = $filtros["prioridad"];
        }
        if (!empty($filtros["area"])) {
            $sql .= " AND t.area = :area";
            $params[":area"] = $filtros["area"];
        }
        if (!empty($filtros["asignado_id"])) {
            $sql .= " AND t.asignado_id = :asignado_id";
            $params[":asignado_id"] = (int) $filtros["asignado_id"];
        }
        if (!empty($filtros["solicitante_id"])) {
            $sql .= " AND t.solicitante_id = :solicitante_id";
            $params[":solicitante_id"] = (int) $filtros["solicitante_id"];
        }
        if (!empty($filtros["asignado_mio_o_libre"])) {
            $sql .= " AND (t.asignado_id = :amid_libre OR t.asignado_id IS NULL)";
            $params[":amid_libre"] = (int) $filtros["asignado_mio_o_libre"];
        }
        if (!empty($filtros["q"])) {
            $sql .= " AND (
                t.titulo LIKE :q
                OR t.descripcion LIKE :q
                OR IFNULL(t.modulo, '') LIKE :q
                OR IFNULL(t.area, '') LIKE :q
                OR CAST(t.id AS CHAR) = :q_exact
            )";
            $params[":q"] = "%" . $filtros["q"] . "%";
            $params[":q_exact"] = $filtros["q"];
        }

        $sql .= " GROUP BY t.estado";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = array(
            "total" => 0,
            "activos" => 0,
            "ABIERTO" => 0,
            "EN_PROGRESO" => 0,
            "ESPERANDO_USUARIO" => 0,
            "CERRADO" => 0,
        );
        foreach ($rows as $r) {
            $est = isset($r["estado"]) ? $r["estado"] : "";
            $n = (int) $r["total"];
            $out["total"] += $n;
            if (isset($out[$est])) {
                $out[$est] = $n;
            }
            if ($est !== "CERRADO") {
                $out["activos"] += $n;
            }
        }

        return $out;
    }

    /**
     * Tickets abiertos sujetos a SLA (excluye desarrollo y SLA cancelado).
     * El vencimiento se evalúa en el controlador con horas laborales.
     */
    public static function mdlCandidatosSlaAbiertos($filtros)
    {
        $sql = "SELECT
                    t.id,
                    t.tipo,
                    t.prioridad,
                    t.estado,
                    t.creado_en,
                    t.cerrado_en,
                    t.sla_exento,
                    t.sla_exento_motivo
                FROM helpdesk_ticketjf t
                WHERE t.estado <> 'CERRADO'
                  AND t.tipo NOT IN ('DESARROLLO')
                  AND IFNULL(t.sla_exento, 0) = 0";
        $params = array();

        if (!empty($filtros["tipo"])) {
            $sql .= " AND t.tipo = :tipo";
            $params[":tipo"] = $filtros["tipo"];
        }
        if (!empty($filtros["sistema"])) {
            $sql .= " AND t.sistema = :sistema";
            $params[":sistema"] = $filtros["sistema"];
        }
        if (!empty($filtros["prioridad"])) {
            $sql .= " AND t.prioridad = :prioridad";
            $params[":prioridad"] = $filtros["prioridad"];
        }
        if (!empty($filtros["area"])) {
            $sql .= " AND t.area = :area";
            $params[":area"] = $filtros["area"];
        }
        if (!empty($filtros["asignado_id"])) {
            $sql .= " AND t.asignado_id = :asignado_id";
            $params[":asignado_id"] = (int) $filtros["asignado_id"];
        }
        if (!empty($filtros["solicitante_id"])) {
            $sql .= " AND t.solicitante_id = :solicitante_id";
            $params[":solicitante_id"] = (int) $filtros["solicitante_id"];
        }
        if (!empty($filtros["asignado_mio_o_libre"])) {
            $sql .= " AND (t.asignado_id = :amid_libre OR t.asignado_id IS NULL)";
            $params[":amid_libre"] = (int) $filtros["asignado_mio_o_libre"];
        }
        if (!empty($filtros["q"])) {
            $sql .= " AND (
                t.titulo LIKE :q
                OR t.descripcion LIKE :q
                OR IFNULL(t.modulo, '') LIKE :q
                OR IFNULL(t.area, '') LIKE :q
                OR CAST(t.id AS CHAR) = :q_exact
            )";
            $params[":q"] = "%" . $filtros["q"] . "%";
            $params[":q_exact"] = $filtros["q"];
        }

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : array();
    }

    /**
     * Tickets relevantes para indicadores (creados/cerrados en rango + abiertos actuales).
     */
    public static function mdlListarParaIndicadores($desde, $hasta, $filtros = array())
    {
        $sql = "SELECT
                    t.id,
                    t.titulo,
                    t.tipo,
                    t.prioridad,
                    t.estado,
                    t.modulo,
                    t.sistema,
                    t.area,
                    t.solicitante_id,
                    t.asignado_id,
                    t.creado_en,
                    t.cerrado_en,
                    t.sla_exento,
                    t.sla_exento_motivo,
                    sol.nombre AS solicitante_nombre,
                    asi.nombre AS asignado_nombre
                FROM helpdesk_ticketjf t
                LEFT JOIN usuariosjf sol ON sol.id = t.solicitante_id
                LEFT JOIN usuariosjf asi ON asi.id = t.asignado_id
                WHERE (
                    (t.creado_en >= :desde AND t.creado_en < :hasta)
                    OR (t.cerrado_en IS NOT NULL AND t.cerrado_en >= :desde2 AND t.cerrado_en < :hasta2)
                    OR t.estado <> 'CERRADO'
                )";
        $params = array(
            ":desde" => $desde,
            ":hasta" => $hasta,
            ":desde2" => $desde,
            ":hasta2" => $hasta,
        );

        if (!empty($filtros["solicitante_id"])) {
            $sql .= " AND t.solicitante_id = :solicitante_id";
            $params[":solicitante_id"] = (int) $filtros["solicitante_id"];
        }
        if (!empty($filtros["asignado_id"])) {
            $sql .= " AND t.asignado_id = :asignado_id";
            $params[":asignado_id"] = (int) $filtros["asignado_id"];
        }

        $sql .= " ORDER BY t.creado_en DESC LIMIT 5000";

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlActividadReciente($limite = 12, $filtros = array())
    {
        $limite = (int) $limite;
        if ($limite < 1) {
            $limite = 12;
        }
        $sql = "SELECT
                    c.id,
                    c.ticket_id,
                    c.tipo_evento,
                    c.mensaje,
                    c.creado_en,
                    u.nombre AS usuario_nombre,
                    t.titulo AS ticket_titulo
                FROM helpdesk_comentariojf c
                LEFT JOIN usuariosjf u ON u.id = c.usuario_id
                LEFT JOIN helpdesk_ticketjf t ON t.id = c.ticket_id
                WHERE 1 = 1";
        $params = array();
        if (!empty($filtros["solicitante_id"])) {
            $sql .= " AND t.solicitante_id = :solicitante_id";
            $params[":solicitante_id"] = (int) $filtros["solicitante_id"];
        }
        $sql .= " ORDER BY c.creado_en DESC LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtener($id)
    {
        $id = (int) $id;
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                t.*,
                sol.nombre AS solicitante_nombre,
                sol.correo AS solicitante_correo,
                sol.usuario AS solicitante_usuario,
                asi.nombre AS asignado_nombre,
                cre.nombre AS creado_por_nombre
             FROM helpdesk_ticketjf t
             LEFT JOIN usuariosjf sol ON sol.id = t.solicitante_id
             LEFT JOIN usuariosjf asi ON asi.id = t.asignado_id
             LEFT JOIN usuariosjf cre ON cre.id = t.creado_por_id
             WHERE t.id = :id
             LIMIT 1"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlCrear($datos)
    {
        $db = Conexion::conectar();
        $stmt = $db->prepare(
            "INSERT INTO helpdesk_ticketjf
                (titulo, descripcion, pasos_reproducir, tipo, prioridad, estado, modulo, sistema, area,
                 correo_contacto, telefono_contacto, canal_preferido,
                 solicitante_id, asignado_id, creado_por_id, fecha_estimada)
             VALUES
                (:titulo, :descripcion, :pasos_reproducir, :tipo, :prioridad, 'ABIERTO', :modulo, :sistema, :area,
                 :correo_contacto, :telefono_contacto, :canal_preferido,
                 :solicitante_id, :asignado_id, :creado_por_id, :fecha_estimada)"
        );
        $stmt->bindValue(":titulo", $datos["titulo"], PDO::PARAM_STR);
        $stmt->bindValue(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        self::bindNullable($stmt, ":pasos_reproducir", $datos["pasos_reproducir"]);
        $stmt->bindValue(":tipo", $datos["tipo"], PDO::PARAM_STR);
        $stmt->bindValue(":prioridad", $datos["prioridad"], PDO::PARAM_STR);
        self::bindNullable($stmt, ":modulo", $datos["modulo"]);
        self::bindNullable($stmt, ":sistema", isset($datos["sistema"]) ? $datos["sistema"] : null);
        self::bindNullable($stmt, ":area", $datos["area"]);
        self::bindNullable($stmt, ":correo_contacto", $datos["correo_contacto"]);
        self::bindNullable($stmt, ":telefono_contacto", $datos["telefono_contacto"]);
        self::bindNullable($stmt, ":canal_preferido", $datos["canal_preferido"]);
        $stmt->bindValue(":solicitante_id", (int) $datos["solicitante_id"], PDO::PARAM_INT);
        if ($datos["asignado_id"] === null) {
            $stmt->bindValue(":asignado_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":asignado_id", (int) $datos["asignado_id"], PDO::PARAM_INT);
        }
        $stmt->bindValue(":creado_por_id", (int) $datos["creado_por_id"], PDO::PARAM_INT);
        self::bindNullable(
            $stmt,
            ":fecha_estimada",
            isset($datos["fecha_estimada"]) ? $datos["fecha_estimada"] : null
        );

        if (!$stmt->execute()) {
            return 0;
        }

        return (int) $db->lastInsertId();
    }

    private static function bindNullable($stmt, $param, $valor)
    {
        if ($valor === null || $valor === "") {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, $valor, PDO::PARAM_STR);
        }
    }

    public static function mdlActualizar($id, $campos)
    {
        $id = (int) $id;
        if ($id < 1 || empty($campos)) {
            return false;
        }

        $permitidos = array(
            "titulo", "descripcion", "pasos_reproducir", "tipo", "prioridad", "estado",
            "modulo", "area", "correo_contacto", "telefono_contacto", "canal_preferido",
            "solicitante_id", "asignado_id", "cerrado_en", "fecha_estimada",
            "sla_exento", "sla_exento_motivo", "sla_exento_en", "sla_exento_por",
        );
        $sets = array();
        $params = array(":id" => $id);

        foreach ($campos as $campo => $valor) {
            if (!in_array($campo, $permitidos, true)) {
                continue;
            }
            $sets[] = $campo . " = :" . $campo;
            $params[":" . $campo] = $valor;
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE helpdesk_ticketjf SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);

        foreach ($params as $key => $valor) {
            if ($valor === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($valor)) {
                $stmt->bindValue($key, $valor, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $valor, PDO::PARAM_STR);
            }
        }

        return $stmt->execute();
    }

    public static function mdlListarComentarios($ticketId)
    {
        $ticketId = (int) $ticketId;
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                c.id,
                c.ticket_id,
                c.usuario_id,
                c.tipo_evento,
                c.mensaje,
                c.estado_anterior,
                c.estado_nuevo,
                c.creado_en,
                u.nombre AS usuario_nombre
             FROM helpdesk_comentariojf c
             LEFT JOIN usuariosjf u ON u.id = c.usuario_id
             WHERE c.ticket_id = :ticket_id
             ORDER BY c.creado_en ASC, c.id ASC"
        );
        $stmt->bindParam(":ticket_id", $ticketId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlAgregarComentario($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO helpdesk_comentariojf
                (ticket_id, usuario_id, tipo_evento, mensaje, estado_anterior, estado_nuevo)
             VALUES
                (:ticket_id, :usuario_id, :tipo_evento, :mensaje, :estado_anterior, :estado_nuevo)"
        );
        $stmt->bindValue(":ticket_id", (int) $datos["ticket_id"], PDO::PARAM_INT);
        $stmt->bindValue(":usuario_id", (int) $datos["usuario_id"], PDO::PARAM_INT);
        $stmt->bindValue(":tipo_evento", $datos["tipo_evento"], PDO::PARAM_STR);
        $stmt->bindValue(":mensaje", $datos["mensaje"], PDO::PARAM_STR);
        if ($datos["estado_anterior"] === null) {
            $stmt->bindValue(":estado_anterior", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":estado_anterior", $datos["estado_anterior"], PDO::PARAM_STR);
        }
        if ($datos["estado_nuevo"] === null) {
            $stmt->bindValue(":estado_nuevo", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":estado_nuevo", $datos["estado_nuevo"], PDO::PARAM_STR);
        }

        return $stmt->execute();
    }

    public static function mdlListarAdjuntos($ticketId)
    {
        $ticketId = (int) $ticketId;
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                a.id,
                a.ticket_id,
                a.nombre_original,
                a.nombre_guardado,
                a.mime,
                a.tamanio,
                a.usuario_id,
                a.creado_en,
                u.nombre AS usuario_nombre
             FROM helpdesk_adjuntojf a
             LEFT JOIN usuariosjf u ON u.id = a.usuario_id
             WHERE a.ticket_id = :ticket_id
             ORDER BY a.creado_en ASC, a.id ASC"
        );
        $stmt->bindParam(":ticket_id", $ticketId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerAdjunto($id)
    {
        $id = (int) $id;
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM helpdesk_adjuntojf WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlAgregarAdjunto($datos)
    {
        $db = Conexion::conectar();
        $stmt = $db->prepare(
            "INSERT INTO helpdesk_adjuntojf
                (ticket_id, nombre_original, nombre_guardado, mime, tamanio, usuario_id)
             VALUES
                (:ticket_id, :nombre_original, :nombre_guardado, :mime, :tamanio, :usuario_id)"
        );
        $stmt->bindValue(":ticket_id", (int) $datos["ticket_id"], PDO::PARAM_INT);
        $stmt->bindValue(":nombre_original", $datos["nombre_original"], PDO::PARAM_STR);
        $stmt->bindValue(":nombre_guardado", $datos["nombre_guardado"], PDO::PARAM_STR);
        self::bindNullable($stmt, ":mime", isset($datos["mime"]) ? $datos["mime"] : null);
        $stmt->bindValue(":tamanio", (int) $datos["tamanio"], PDO::PARAM_INT);
        $stmt->bindValue(":usuario_id", (int) $datos["usuario_id"], PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return 0;
        }

        return (int) $db->lastInsertId();
    }

    public static function mdlUsuariosPorIds($ids)
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (empty($ids)) {
            return array();
        }

        $placeholders = array();
        $params = array();
        foreach ($ids as $i => $id) {
            $key = ":id" . $i;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $sql = "SELECT id, nombre, usuario
                FROM usuariosjf
                WHERE id IN (" . implode(",", $placeholders) . ")
                  AND IFNULL(estado, 0) = 1
                ORDER BY nombre ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $key => $valor) {
            $stmt->bindValue($key, $valor, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListarUsuariosActivos($limite = 200)
    {
        $limite = max(1, min(500, (int) $limite));
        $stmt = Conexion::conectar()->prepare(
            "SELECT id, nombre, usuario
             FROM usuariosjf
             WHERE IFNULL(estado, 0) = 1
             ORDER BY nombre ASC
             LIMIT " . $limite
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
