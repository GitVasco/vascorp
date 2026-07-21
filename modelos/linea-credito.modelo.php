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

    private static function sqlDeudaVencidaSubquery($aliasCliente = "c")
    {
        return "IFNULL((
                    SELECT SUM(IFNULL(ct.saldo, 0))
                    FROM cuenta_ctejf ct
                    WHERE ct.cliente = {$aliasCliente}.codigo
                      AND ct.tip_mov = '+'
                      AND UPPER(ct.estado) = 'PENDIENTE'
                      AND IFNULL(ct.saldo, 0) > 0
                      AND ct.fecha_ven < CURDATE()
                ), 0) AS deuda_vencida";
    }

    private static function sqlDeudaActualLiveSubquery($aliasCliente = "c")
    {
        return "IFNULL((
                    SELECT SUM(IFNULL(ct.saldo, 0))
                    FROM cuenta_ctejf ct
                    WHERE ct.cliente = {$aliasCliente}.codigo
                      AND ct.tip_mov = '+'
                      AND UPPER(ct.estado) = 'PENDIENTE'
                      AND IFNULL(ct.saldo, 0) > 0
                ), 0) AS deuda_actual";
    }

    /**
     * Condición booleana de cartera activa (sin el AND inicial ni estado).
     * Usada para CASE fuera_cartera sobre miembros ya filtrados por estado=1.
     */
    private static function sqlExpresionCarteraActivaSinEstado($aliasCliente = "c")
    {
        $tipos = self::sqlTiposVentaIc();
        $meses = (int) self::MESES_COMPRA_ACTIVA;
        $excluirContado = self::sqlExcluirVendedoresContado($aliasCliente);

        return "{$aliasCliente}.fecha IS NOT NULL
            {$excluirContado}
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

    /**
     * Excluye canales de contado / showroom / digital (p. ej. 08, 08C, 08L).
     * Reutiliza la config de IC si está cargada.
     */
    private static function sqlExcluirVendedoresContado($aliasCliente = "c")
    {
        $campo = "TRIM(COALESCE({$aliasCliente}.vendedor, ''))";

        if (function_exists("icMotor2SqlExcluirVendedorPrefijosZona")) {
            $sqlPrefijos = icMotor2SqlExcluirVendedorPrefijosZona($campo);
        } else {
            $sqlPrefijos = "{$campo} NOT LIKE '08%'";
        }

        $codigosExactos = array("99", "23");
        if (function_exists("icConfigMotor2")) {
            $cfg = icConfigMotor2();
            if (!empty($cfg["ventas_excluir_vendedores"]) && is_array($cfg["ventas_excluir_vendedores"])) {
                $codigosExactos = $cfg["ventas_excluir_vendedores"];
            }
        }

        $partesExactos = array();
        foreach ($codigosExactos as $codigo) {
            $codigo = trim((string) $codigo);
            if ($codigo === "") {
                continue;
            }
            $codigoSql = str_replace(array("'", "\\"), "", $codigo);
            $partesExactos[] = "'{$codigoSql}'";
        }

        $sqlExactos = $partesExactos
            ? " AND {$campo} NOT IN (" . implode(", ", $partesExactos) . ")"
            : "";

        return " AND ({$sqlPrefijos}){$sqlExactos}";
    }

    private static function sqlFiltroCarteraActiva($aliasCliente = "c")
    {
        return "
            AND {$aliasCliente}.estado = 1
            AND " . self::sqlExpresionCarteraActivaSinEstado($aliasCliente);
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
                    c.grupo,
                    lc.linea_operativa,
                    lc.linea_recomendada,
                    lc.linea_aprobada,
                    " . self::sqlDeudaActualLiveSubquery("c") . ",
                    " . self::sqlDeudaVencidaSubquery("c") . ",
                    lc.cupo_disponible,
                    lc.utilizacion_pct,
                    lc.score_riesgo,
                    lc.score_comercial,
                    lc.score_fidelidad,
                    lc.accion_linea,
                    lc.ultimo_cierre_anio,
                    lc.ultimo_cierre_mes,
                    lc.fecha_actualizacion,
                    0 AS fuera_cartera
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

    static public function mdlListarClientesConLineaPorGrupo($codigoGrupo)
    {
        $codigoGrupo = trim((string) $codigoGrupo);

        if ($codigoGrupo === "") {
            return array();
        }

        $exprCartera = self::sqlExpresionCarteraActivaSinEstado("c");

        $stmt = Conexion::conectar()->prepare(
            "SELECT
                    c.codigo,
                    c.nombre,
                    c.grupo,
                    lc.linea_operativa,
                    lc.linea_recomendada,
                    lc.linea_aprobada,
                    " . self::sqlDeudaActualLiveSubquery("c") . ",
                    " . self::sqlDeudaVencidaSubquery("c") . ",
                    lc.cupo_disponible,
                    lc.utilizacion_pct,
                    lc.score_riesgo,
                    lc.score_comercial,
                    lc.score_fidelidad,
                    lc.accion_linea,
                    lc.fecha_actualizacion,
                    CASE WHEN ({$exprCartera}) THEN 0 ELSE 1 END AS fuera_cartera
                FROM clientesjf c
                LEFT JOIN linea_credito_clientejf lc ON lc.codigo_cliente = c.codigo
                WHERE c.grupo = :grupo
                  AND c.estado = 1
                ORDER BY
                    CASE WHEN ({$exprCartera}) THEN 0 ELSE 1 END ASC,
                    c.nombre ASC"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila["fuera_cartera"] = !empty($fila["fuera_cartera"]) ? 1 : 0;
            $fila["deuda_actual"] = round((float) $fila["deuda_actual"], 2);
            $fila["deuda_vencida"] = round((float) $fila["deuda_vencida"], 2);
        }
        unset($fila);

        return $filas;
    }

    static public function mdlCodigosMiembrosGrupo($codigoGrupo)
    {
        $codigoGrupo = trim((string) $codigoGrupo);

        if ($codigoGrupo === "") {
            return array();
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT codigo
             FROM clientesjf
             WHERE grupo = :grupo
               AND estado = 1
             ORDER BY nombre ASC"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();

        $codigos = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $codigos[] = $fila["codigo"];
        }

        return $codigos;
    }

    static public function mdlClienteLinea($codigoCliente)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT
                c.codigo,
                c.nombre,
                c.grupo,
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

    private static function normalizarCodigosLista(array $codigos)
    {
        $lista = array();

        foreach ($codigos as $codigo) {
            $codigo = trim((string) $codigo);

            if ($codigo !== '') {
                $lista[$codigo] = $codigo;
            }
        }

        return array_values($lista);
    }

    private static function resolverLineaReferenciaMontos($aprobada, $recomendada, $operativa, $prefijoEtiqueta = '')
    {
        $aprobada = (float) $aprobada;
        $recomendada = (float) $recomendada;
        $operativa = (float) $operativa;

        if ($aprobada > 0) {
            return array(
                'linea_referencia' => $aprobada,
                'etiqueta_linea' => $prefijoEtiqueta . 'Aprobada',
            );
        }

        if ($recomendada > 0) {
            if (function_exists('icRedondearLineaCredito')) {
                $recomendada = icRedondearLineaCredito($recomendada);
            } else {
                $recomendada = round($recomendada, 2);
            }

            return array(
                'linea_referencia' => $recomendada,
                'etiqueta_linea' => $prefijoEtiqueta . 'Recomendada IC',
            );
        }

        if ($operativa > 0) {
            return array(
                'linea_referencia' => $operativa,
                'etiqueta_linea' => $prefijoEtiqueta . 'Operativa',
            );
        }

        return null;
    }

    /**
     * Líneas de referencia (aprobada → recomendada → operativa) para el Top de deuda CxC.
     * Incluye estado vigente e historial reciente por código.
     */
    static public function mdlMapaLineasReferenciaDashboard(array $codigosCliente, array $codigosGrupo)
    {
        $mapa = array();
        $codigosCliente = self::normalizarCodigosLista($codigosCliente);
        $codigosGrupo = self::normalizarCodigosLista($codigosGrupo);

        if (count($codigosCliente) > 0) {
            $marcadores = array();

            foreach ($codigosCliente as $i => $codigo) {
                $marcadores[] = ':cli' . $i;
            }

            $sql = "SELECT TRIM(codigo_cliente) AS codigo,
                    linea_aprobada, linea_recomendada, linea_operativa
                FROM linea_credito_clientejf
                WHERE TRIM(codigo_cliente) IN (" . implode(', ', $marcadores) . ")";
            $stmt = Conexion::conectar()->prepare($sql);

            foreach ($codigosCliente as $i => $codigo) {
                $stmt->bindValue(':cli' . $i, $codigo, PDO::PARAM_STR);
            }

            $stmt->execute();
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filas as $fila) {
                $ref = self::resolverLineaReferenciaMontos(
                    $fila['linea_aprobada'],
                    $fila['linea_recomendada'],
                    $fila['linea_operativa']
                );

                if ($ref) {
                    $mapa['cliente|' . trim($fila['codigo'])] = $ref;
                }
            }

            $faltantes = array();

            foreach ($codigosCliente as $codigo) {
                if (!isset($mapa['cliente|' . $codigo])) {
                    $faltantes[] = $codigo;
                }
            }

            if (count($faltantes) > 0) {
                $marcadores = array();

                foreach ($faltantes as $i => $codigo) {
                    $marcadores[] = ':hcli' . $i;
                }

                $sqlHist = "SELECT TRIM(h.codigo_cliente) AS codigo,
                        h.linea_aprobada, h.linea_recomendada, h.linea_operativa
                    FROM linea_credito_historialjf h
                    INNER JOIN (
                        SELECT codigo_cliente, MAX(id) AS ultimo_id
                        FROM linea_credito_historialjf
                        WHERE TRIM(codigo_cliente) IN (" . implode(', ', $marcadores) . ")
                        GROUP BY codigo_cliente
                    ) u ON u.ultimo_id = h.id";
                $stmtHist = Conexion::conectar()->prepare($sqlHist);

                foreach ($faltantes as $i => $codigo) {
                    $stmtHist->bindValue(':hcli' . $i, $codigo, PDO::PARAM_STR);
                }

                $stmtHist->execute();
                $hist = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

                foreach ($hist as $fila) {
                    $ref = self::resolverLineaReferenciaMontos(
                        $fila['linea_aprobada'],
                        $fila['linea_recomendada'],
                        $fila['linea_operativa'],
                        'Hist. '
                    );

                    if ($ref) {
                        $mapa['cliente|' . trim($fila['codigo'])] = $ref;
                    }
                }
            }
        }

        if (count($codigosGrupo) > 0) {
            $marcadores = array();

            foreach ($codigosGrupo as $i => $codigo) {
                $marcadores[] = ':grp' . $i;
            }

            $sqlGrupo = "SELECT TRIM(codigo_grupo) AS codigo,
                    linea_aprobada, linea_recomendada, linea_operativa
                FROM linea_credito_grupojf
                WHERE TRIM(codigo_grupo) IN (" . implode(', ', $marcadores) . ")";
            $stmtGrupo = Conexion::conectar()->prepare($sqlGrupo);

            foreach ($codigosGrupo as $i => $codigo) {
                $stmtGrupo->bindValue(':grp' . $i, $codigo, PDO::PARAM_STR);
            }

            $stmtGrupo->execute();
            $filasGrupo = $stmtGrupo->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filasGrupo as $fila) {
                $ref = self::resolverLineaReferenciaMontos(
                    $fila['linea_aprobada'],
                    $fila['linea_recomendada'],
                    $fila['linea_operativa'],
                    'Grupo · '
                );

                if ($ref) {
                    $mapa['grupo|' . trim($fila['codigo'])] = $ref;
                }
            }

            $faltantesGrupo = array();

            foreach ($codigosGrupo as $codigo) {
                if (!isset($mapa['grupo|' . $codigo])) {
                    $faltantesGrupo[] = $codigo;
                }
            }

            if (count($faltantesGrupo) > 0) {
                $marcadores = array();

                foreach ($faltantesGrupo as $i => $codigo) {
                    $marcadores[] = ':hgrp' . $i;
                }

                $sqlHistGrupo = "SELECT TRIM(h.codigo_grupo) AS codigo,
                        h.linea_aprobada, h.linea_recomendada, h.linea_operativa
                    FROM linea_credito_historial_grupojf h
                    INNER JOIN (
                        SELECT codigo_grupo, MAX(id) AS ultimo_id
                        FROM linea_credito_historial_grupojf
                        WHERE TRIM(codigo_grupo) IN (" . implode(', ', $marcadores) . ")
                        GROUP BY codigo_grupo
                    ) u ON u.ultimo_id = h.id";
                $stmtHistGrupo = Conexion::conectar()->prepare($sqlHistGrupo);

                foreach ($faltantesGrupo as $i => $codigo) {
                    $stmtHistGrupo->bindValue(':hgrp' . $i, $codigo, PDO::PARAM_STR);
                }

                $stmtHistGrupo->execute();
                $histGrupo = $stmtHistGrupo->fetchAll(PDO::FETCH_ASSOC);

                foreach ($histGrupo as $fila) {
                    $ref = self::resolverLineaReferenciaMontos(
                        $fila['linea_aprobada'],
                        $fila['linea_recomendada'],
                        $fila['linea_operativa'],
                        'Grupo · Hist. '
                    );

                    if ($ref) {
                        $mapa['grupo|' . trim($fila['codigo'])] = $ref;
                    }
                }
            }
        }

        return $mapa;
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

    static public function mdlGrupoLinea($codigoGrupo)
    {
        $codigoGrupo = trim((string) $codigoGrupo);

        if ($codigoGrupo === "") {
            return null;
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT
                g.codigo,
                g.nombre,
                lg.*
             FROM grupos_empresarialesjf g
             LEFT JOIN linea_credito_grupojf lg ON lg.codigo_grupo = g.codigo
             WHERE g.codigo = :grupo
             LIMIT 1"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlHistorialGrupo($codigoGrupo, $limite = 24)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT h.*, u.nombre AS usuario_nombre
             FROM linea_credito_historial_grupojf h
             LEFT JOIN usuariosjf u ON u.id = h.usuario_id
             WHERE h.codigo_grupo = :grupo
             ORDER BY h.fecha DESC, h.id DESC
             LIMIT :limite"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlExisteCierreMensualGrupo($codigoGrupo, $anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT id
             FROM linea_credito_historial_grupojf
             WHERE codigo_grupo = :grupo
               AND anio = :anio
               AND mes = :mes
               AND tipo_evento = 'CIERRE_MENSUAL'
             LIMIT 1"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlGruposCarteraActivaPendientesCierre($anio, $mes, $limite = 15)
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $limite = max(1, min(30, (int) $limite));
        $stmt = Conexion::conectar()->prepare(
            "SELECT DISTINCT c.grupo AS codigo, g.nombre
             FROM clientesjf c
             INNER JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
             WHERE c.grupo IS NOT NULL
               AND TRIM(c.grupo) <> ''
               {$filtro}
               AND NOT EXISTS (
                   SELECT 1
                   FROM linea_credito_historial_grupojf h
                   WHERE h.codigo_grupo = c.grupo
                     AND h.anio = :anio
                     AND h.mes = :mes
                     AND h.tipo_evento = 'CIERRE_MENSUAL'
               )
             ORDER BY g.nombre ASC
             LIMIT :limite"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlContarGruposPendientesCierre($anio, $mes)
    {
        $filtro = self::sqlFiltroCarteraActiva("c");
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(DISTINCT c.grupo) AS total
             FROM clientesjf c
             INNER JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
             WHERE c.grupo IS NOT NULL
               AND TRIM(c.grupo) <> ''
               {$filtro}
               AND NOT EXISTS (
                   SELECT 1
                   FROM linea_credito_historial_grupojf h
                   WHERE h.codigo_grupo = c.grupo
                     AND h.anio = :anio
                     AND h.mes = :mes
                     AND h.tipo_evento = 'CIERRE_MENSUAL'
               )"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($fila["total"]) ? (int) $fila["total"] : 0;
    }

    static public function mdlResumenCierreGrupo($anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) AS total
             FROM linea_credito_historial_grupojf
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

    static public function mdlGuardarEstadoGrupo($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO linea_credito_grupojf
                (codigo_grupo, linea_operativa, linea_recomendada, linea_aprobada,
                 deuda_actual, cupo_disponible, utilizacion_pct,
                 score_riesgo, score_comercial, score_fidelidad, accion_linea,
                 ultimo_cierre_anio, ultimo_cierre_mes, usuario_actualiza, fecha_actualizacion)
             VALUES
                (:codigo_grupo, :linea_operativa, :linea_recomendada, :linea_aprobada,
                 :deuda_actual, :cupo_disponible, :utilizacion_pct,
                 :score_riesgo, :score_comercial, :score_fidelidad, :accion_linea,
                 :ultimo_cierre_anio, :ultimo_cierre_mes, :usuario_actualiza, NOW())
             ON DUPLICATE KEY UPDATE
                linea_operativa = VALUES(linea_operativa),
                linea_recomendada = VALUES(linea_recomendada),
                linea_aprobada = VALUES(linea_aprobada),
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

        $stmt->bindParam(":codigo_grupo", $datos["codigo_grupo"], PDO::PARAM_STR);
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

    static public function mdlRegistrarHistorialGrupo($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO linea_credito_historial_grupojf
                (codigo_grupo, anio, mes, tipo_evento,
                 linea_operativa, linea_recomendada, linea_aprobada,
                 deuda_actual, cupo_disponible, utilizacion_pct,
                 score_riesgo, score_comercial, score_fidelidad, accion_linea,
                 detalle, usuario_id)
             VALUES
                (:codigo_grupo, :anio, :mes, :tipo_evento,
                 :linea_operativa, :linea_recomendada, :linea_aprobada,
                 :deuda_actual, :cupo_disponible, :utilizacion_pct,
                 :score_riesgo, :score_comercial, :score_fidelidad, :accion_linea,
                 :detalle, :usuario_id)"
        );

        $stmt->bindParam(":codigo_grupo", $datos["codigo_grupo"], PDO::PARAM_STR);
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
        $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int) Conexion::conectar()->lastInsertId();
        }

        return 0;
    }

    static public function mdlLimpiarLineaAprobadaMiembrosGrupo($codigoGrupo)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE linea_credito_clientejf lc
             INNER JOIN clientesjf c ON c.codigo = lc.codigo_cliente
             SET lc.linea_aprobada = NULL
             WHERE c.grupo = :grupo
               AND lc.linea_aprobada IS NOT NULL"
        );
        $stmt->bindParam(":grupo", $codigoGrupo, PDO::PARAM_STR);

        return $stmt->execute() ? "ok" : "error";
    }
}
