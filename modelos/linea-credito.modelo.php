<?php

require_once "conexion.php";

class ModeloLineaCredito
{
    const MESES_COMPRA_ACTIVA = 24;

    private static function sqlTiposVentaIc()
    {
        if (function_exists("icVentasTiposValidosSql")) {
            return icVentasTiposValidosSql();
        }

        return "'S02', 'S03', 'S70'";
    }

    /**
     * Cartera operativa: vendedor activo (Centro de Decisiones) + compra/pedido en 24 meses.
     */
    private static function sqlFiltroCarteraActiva($aliasCliente = "c")
    {
        $tipos = self::sqlTiposVentaIc();
        $meses = (int) self::MESES_COMPRA_ACTIVA;

        return "
            AND {$aliasCliente}.estado = 1
            AND {$aliasCliente}.fecha IS NOT NULL
            AND TRIM(COALESCE({$aliasCliente}.vendedor, '')) IN (
                SELECT m.codigo
                FROM maestrajf m
                WHERE UPPER(m.tipo_dato) = 'TVEND'
                  AND m.estado_decisiones = 1
            )
            AND (
                EXISTS (
                    SELECT 1
                    FROM ventajf v
                    WHERE v.cliente = {$aliasCliente}.codigo
                      AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                      AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
                      AND UPPER(TRIM(v.tipo)) IN ({$tipos})
                )
                OR EXISTS (
                    SELECT 1
                    FROM temporaljf t
                    WHERE t.cliente = {$aliasCliente}.codigo
                      AND t.fecha >= DATE_SUB(CURDATE(), INTERVAL {$meses} MONTH)
                      AND UPPER(IFNULL(t.estado, '')) <> 'ANULADO'
                )
            )";
    }

    static public function mdlClientesCarteraActiva()
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT c.codigo, c.nombre
             FROM clientesjf c
             WHERE 1 = 1 {$filtro}
             ORDER BY c.nombre ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlContarCarteraActiva()
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) AS total
             FROM clientesjf c
             WHERE 1 = 1 {$filtro}"
        );
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    private static function sqlSinCierreMensual($anio, $mes, $aliasCliente = "c")
    {
        return "
            AND NOT EXISTS (
                SELECT 1
                FROM linea_credito_historialjf h
                WHERE h.codigo_cliente = {$aliasCliente}.codigo
                  AND h.anio = :anio
                  AND h.mes = :mes
                  AND h.tipo_evento = 'CIERRE_MENSUAL'
            )";
    }

    static public function mdlClientesCarteraActivaPendientesCierre($anio, $mes, $limite = 15)
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $sinCierre = self::sqlSinCierreMensual($anio, $mes, "c");
        $limite = max(1, min(30, (int) $limite));
        $stmt = Conexion::conectar()->prepare(
            "SELECT c.codigo, c.nombre
             FROM clientesjf c
             WHERE 1 = 1 {$filtro} {$sinCierre}
             ORDER BY c.codigo ASC
             LIMIT :limite"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlContarPendientesCierre($anio, $mes)
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $sinCierre = self::sqlSinCierreMensual($anio, $mes, "c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) AS total
             FROM clientesjf c
             WHERE 1 = 1 {$filtro} {$sinCierre}"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

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

    static public function mdlListarClientesConLinea($busqueda = "")
    {
        $busqueda = trim((string) $busqueda);
        $sql = "SELECT
                    c.codigo,
                    c.nombre,
                    lc.linea_operativa,
                    lc.linea_recomendada,
                    lc.linea_aprobada,
                    lc.deuda_actual,
                    lc.cupo_disponible,
                    lc.utilizacion_pct,
                    lc.score_riesgo,
                    lc.score_comercial,
                    lc.score_fidelidad,
                    lc.accion_linea,
                    lc.ultimo_cierre_anio,
                    lc.ultimo_cierre_mes,
                    lc.fecha_actualizacion
                FROM clientesjf c
                LEFT JOIN linea_credito_clientejf lc ON lc.codigo_cliente = c.codigo
                WHERE 1 = 1" . self::sqlFiltroCarteraActiva("c");

        if ($busqueda !== "") {
            $sql .= " AND (c.codigo LIKE :busqueda OR c.nombre LIKE :busqueda)";
        }

        $sql .= " ORDER BY c.nombre ASC";

        $stmt = Conexion::conectar()->prepare($sql);

        if ($busqueda !== "") {
            $like = "%" . $busqueda . "%";
            $stmt->bindParam(":busqueda", $like, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlClienteLinea($codigoCliente)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                c.codigo,
                c.nombre,
                lc.*
             FROM clientesjf c
             LEFT JOIN linea_credito_clientejf lc ON lc.codigo_cliente = c.codigo
             WHERE c.codigo = :cliente
             LIMIT 1"
        );
        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlHistorialCliente($codigoCliente, $limite = 24)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT h.*, u.nombre AS usuario_nombre
             FROM linea_credito_historialjf h
             LEFT JOIN usuariosjf u ON u.id = h.usuario_id
             WHERE h.codigo_cliente = :cliente
             ORDER BY h.fecha DESC, h.id DESC
             LIMIT :limite"
        );
        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSolicitudesCliente($codigoCliente, $soloPendientes = false)
    {
        $sql = "SELECT s.*,
                       us.nombre AS usuario_solicita_nombre,
                       ur.nombre AS usuario_resuelve_nombre
                FROM linea_credito_solicitudjf s
                LEFT JOIN usuariosjf us ON us.id = s.usuario_solicita
                LEFT JOIN usuariosjf ur ON ur.id = s.usuario_resuelve
                WHERE s.codigo_cliente = :cliente";

        if ($soloPendientes) {
            $sql .= " AND s.estado = 'PENDIENTE'";
        }

        $sql .= " ORDER BY s.fecha_solicitud DESC, s.id DESC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlExisteCierreMensual($codigoCliente, $anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT id
             FROM linea_credito_historialjf
             WHERE codigo_cliente = :cliente
               AND anio = :anio
               AND mes = :mes
               AND tipo_evento = 'CIERRE_MENSUAL'
             LIMIT 1"
        );
        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlGuardarEstadoCliente($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO linea_credito_clientejf
                (codigo_cliente, linea_operativa, linea_recomendada, linea_aprobada,
                 deuda_actual, cupo_disponible, utilizacion_pct,
                 score_riesgo, score_comercial, score_fidelidad, accion_linea,
                 ultimo_cierre_anio, ultimo_cierre_mes, usuario_actualiza, fecha_actualizacion)
             VALUES
                (:codigo_cliente, :linea_operativa, :linea_recomendada, :linea_aprobada,
                 :deuda_actual, :cupo_disponible, :utilizacion_pct,
                 :score_riesgo, :score_comercial, :score_fidelidad, :accion_linea,
                 :ultimo_cierre_anio, :ultimo_cierre_mes, :usuario_actualiza, NOW())
             ON DUPLICATE KEY UPDATE
                linea_operativa = VALUES(linea_operativa),
                linea_recomendada = VALUES(linea_recomendada),
                deuda_actual = VALUES(deuda_actual),
                cupo_disponible = VALUES(cupo_disponible),
                utilizacion_pct = VALUES(utilizacion_pct),
                score_riesgo = VALUES(score_riesgo),
                score_comercial = VALUES(score_comercial),
                score_fidelidad = VALUES(score_fidelidad),
                accion_linea = VALUES(accion_linea),
                ultimo_cierre_anio = VALUES(ultimo_cierre_anio),
                ultimo_cierre_mes = VALUES(ultimo_cierre_mes),
                usuario_actualiza = VALUES(usuario_actualiza),
                fecha_actualizacion = NOW()"
        );

        $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
        $stmt->bindParam(":linea_operativa", $datos["linea_operativa"]);
        $stmt->bindParam(":linea_recomendada", $datos["linea_recomendada"]);
        $stmt->bindParam(":linea_aprobada", $datos["linea_aprobada"]);
        $stmt->bindParam(":deuda_actual", $datos["deuda_actual"]);
        $stmt->bindParam(":cupo_disponible", $datos["cupo_disponible"]);
        $stmt->bindParam(":utilizacion_pct", $datos["utilizacion_pct"]);
        $stmt->bindParam(":score_riesgo", $datos["score_riesgo"]);
        $stmt->bindParam(":score_comercial", $datos["score_comercial"]);
        $stmt->bindParam(":score_fidelidad", $datos["score_fidelidad"]);
        $stmt->bindParam(":accion_linea", $datos["accion_linea"], PDO::PARAM_STR);
        $stmt->bindParam(":ultimo_cierre_anio", $datos["ultimo_cierre_anio"], PDO::PARAM_INT);
        $stmt->bindParam(":ultimo_cierre_mes", $datos["ultimo_cierre_mes"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario_actualiza", $datos["usuario_actualiza"], PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }

    static public function mdlRegistrarHistorial($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO linea_credito_historialjf
                (codigo_cliente, anio, mes, tipo_evento,
                 linea_operativa, linea_recomendada, linea_aprobada,
                 deuda_actual, cupo_disponible, utilizacion_pct,
                 score_riesgo, score_comercial, score_fidelidad, accion_linea,
                 detalle, id_solicitud, usuario_id)
             VALUES
                (:codigo_cliente, :anio, :mes, :tipo_evento,
                 :linea_operativa, :linea_recomendada, :linea_aprobada,
                 :deuda_actual, :cupo_disponible, :utilizacion_pct,
                 :score_riesgo, :score_comercial, :score_fidelidad, :accion_linea,
                 :detalle, :id_solicitud, :usuario_id)"
        );

        $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
        $stmt->bindParam(":anio", $datos["anio"], PDO::PARAM_INT);
        $stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_INT);
        $stmt->bindParam(":tipo_evento", $datos["tipo_evento"], PDO::PARAM_STR);
        $stmt->bindParam(":linea_operativa", $datos["linea_operativa"]);
        $stmt->bindParam(":linea_recomendada", $datos["linea_recomendada"]);
        $stmt->bindParam(":linea_aprobada", $datos["linea_aprobada"]);
        $stmt->bindParam(":deuda_actual", $datos["deuda_actual"]);
        $stmt->bindParam(":cupo_disponible", $datos["cupo_disponible"]);
        $stmt->bindParam(":utilizacion_pct", $datos["utilizacion_pct"]);
        $stmt->bindParam(":score_riesgo", $datos["score_riesgo"]);
        $stmt->bindParam(":score_comercial", $datos["score_comercial"]);
        $stmt->bindParam(":score_fidelidad", $datos["score_fidelidad"]);
        $stmt->bindParam(":accion_linea", $datos["accion_linea"], PDO::PARAM_STR);
        $stmt->bindParam(":detalle", $datos["detalle"], PDO::PARAM_STR);
        $stmt->bindParam(":id_solicitud", $datos["id_solicitud"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int) Conexion::conectar()->lastInsertId();
        }

        return 0;
    }

    static public function mdlActualizarLineaAprobada($codigoCliente, $lineaAprobada, $usuarioId)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE linea_credito_clientejf
             SET linea_aprobada = :linea_aprobada,
                 usuario_actualiza = :usuario_id,
                 fecha_actualizacion = NOW()
             WHERE codigo_cliente = :cliente"
        );
        $stmt->bindParam(":linea_aprobada", $lineaAprobada);
        $stmt->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
        $stmt->bindParam(":cliente", $codigoCliente, PDO::PARAM_STR);

        return $stmt->execute() ? "ok" : "error";
    }

    static public function mdlCrearSolicitud($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO linea_credito_solicitudjf
                (codigo_cliente, linea_actual, linea_solicitada, justificacion, usuario_solicita)
             VALUES
                (:codigo_cliente, :linea_actual, :linea_solicitada, :justificacion, :usuario_solicita)"
        );
        $stmt->bindParam(":codigo_cliente", $datos["codigo_cliente"], PDO::PARAM_STR);
        $stmt->bindParam(":linea_actual", $datos["linea_actual"]);
        $stmt->bindParam(":linea_solicitada", $datos["linea_solicitada"]);
        $stmt->bindParam(":justificacion", $datos["justificacion"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario_solicita", $datos["usuario_solicita"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int) Conexion::conectar()->lastInsertId();
        }

        return 0;
    }

    static public function mdlSolicitudPorId($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM linea_credito_solicitudjf WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlResolverSolicitud($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE linea_credito_solicitudjf
             SET estado = :estado,
                 linea_resuelta = :linea_resuelta,
                 comentario_resolucion = :comentario_resolucion,
                 usuario_resuelve = :usuario_resuelve,
                 fecha_resolucion = NOW()
             WHERE id = :id
               AND estado = 'PENDIENTE'"
        );
        $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $stmt->bindParam(":linea_resuelta", $datos["linea_resuelta"]);
        $stmt->bindParam(":comentario_resolucion", $datos["comentario_resolucion"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario_resuelve", $datos["usuario_resuelve"], PDO::PARAM_INT);
        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? "ok" : "error";
    }

    static public function mdlResumenCierre($anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) AS total
             FROM linea_credito_historialjf
             WHERE anio = :anio
               AND mes = :mes
               AND tipo_evento = 'CIERRE_MENSUAL'"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }
}
