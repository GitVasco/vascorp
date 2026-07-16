<?php

require_once "conexion.php";
require_once dirname(__FILE__) . "/../controladores/metas-retos.config.php";

class ModeloMetasVendedor
{
    private static function tiposVentaReal()
    {
        return array("S02", "S03", "S70", "E05", "S05");
    }

    private static function sqlTiposVentaReal($alias = "v")
    {
        $tipos = array();
        foreach (self::tiposVentaReal() as $tipo) {
            $tipos[] = "'" . $tipo . "'";
        }

        return "{$alias}.tipo IN (" . implode(", ", $tipos) . ")";
    }

    private static function rangoMes($anio, $mes)
    {
        $anio = (int) $anio;
        $mes = (int) $mes;
        $inicio = sprintf("%04d-%02d-01", $anio, $mes);

        if ($mes === 12) {
            $fin = sprintf("%04d-01-01", $anio + 1);
        } else {
            $fin = sprintf("%04d-%02d-01", $anio, $mes + 1);
        }

        return array("inicio" => $inicio, "fin" => $fin);
    }

    private static function sqlCobranzaEfectivo($alias = "cc")
    {
        return "CASE
            WHEN " . mrSqlInCodigosCobranzaEfectiva($alias) . " THEN 'EFECTIVO'
            WHEN {$alias}.cod_pago IN ('13', '96') THEN 'DEVOLUCION'
            WHEN {$alias}.cod_pago IN ('97', '10') THEN 'DESCUENTOS'
            ELSE 'OTROS'
        END";
    }

    static public function mdlVendedoresActivos()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT codigo, descripcion
             FROM maestrajf
             WHERE UPPER(tipo_dato) = 'TVEND'
             ORDER BY codigo ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlExisteMetaPeriodo($codVendedor, $anio, $mes, $excluirId = null)
    {
        $sql = "SELECT id
                FROM metas_vendedorjf
                WHERE cod_vendedor = :cod_vendedor
                  AND anio = :anio
                  AND mes = :mes";

        if ($excluirId !== null) {
            $sql .= " AND id <> :excluir_id";
        }

        $sql .= " LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);

        if ($excluirId !== null) {
            $stmt->bindParam(":excluir_id", $excluirId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlIngresarMeta($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO metas_vendedorjf
                (cod_vendedor, anio, mes, meta_venta, meta_cobranza, usuario)
             VALUES
                (:cod_vendedor, :anio, :mes, :meta_venta, :meta_cobranza, :usuario)"
        );

        $stmt->bindParam(":cod_vendedor", $datos["cod_vendedor"], PDO::PARAM_STR);
        $stmt->bindParam(":anio", $datos["anio"], PDO::PARAM_INT);
        $stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_INT);
        $stmt->bindParam(":meta_venta", $datos["meta_venta"], PDO::PARAM_STR);
        $stmt->bindParam(":meta_cobranza", $datos["meta_cobranza"], $datos["meta_cobranza"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    static public function mdlMostrarMeta($item, $valor)
    {
        if ($item !== null) {
            $stmt = Conexion::conectar()->prepare(
                "SELECT m.*, mv.descripcion AS nombre_vendedor
                 FROM metas_vendedorjf m
                 LEFT JOIN maestrajf mv
                    ON mv.codigo = m.cod_vendedor
                   AND UPPER(mv.tipo_dato) = 'TVEND'
                 WHERE m.$item = :$item
                 LIMIT 1"
            );
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->prepare(
            "SELECT m.*, mv.descripcion AS nombre_vendedor
             FROM metas_vendedorjf m
             LEFT JOIN maestrajf mv
                ON mv.codigo = m.cod_vendedor
               AND UPPER(mv.tipo_dato) = 'TVEND'
             ORDER BY m.anio DESC, m.mes DESC, m.cod_vendedor ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlListarMetasPeriodo($anio, $mes)
    {
        $rango = self::rangoMes($anio, $mes);
        $sqlCobranza = self::sqlCobranzaEfectivo("cc");

        $sql = "SELECT
                    m.id,
                    m.cod_vendedor,
                    mv.descripcion AS nombre_vendedor,
                    m.anio,
                    m.mes,
                    m.meta_venta,
                    m.meta_cobranza,
                    COALESCE(v.venta_real, 0) AS venta_real,
                    COALESCE(c.cobranza_real, 0) AS cobranza_real
                FROM metas_vendedorjf m
                LEFT JOIN maestrajf mv
                    ON mv.codigo = m.cod_vendedor
                   AND UPPER(mv.tipo_dato) = 'TVEND'
                LEFT JOIN (
                    SELECT
                        TRIM(v.vendedor) AS vendedor,
                        SUM(v.neto) AS venta_real
                    FROM ventajf v
                    WHERE v.fecha >= :fecha_ini
                      AND v.fecha < :fecha_fin
                      AND " . self::sqlTiposVentaReal("v") . "
                    GROUP BY TRIM(v.vendedor)
                ) v ON v.vendedor = TRIM(m.cod_vendedor)
                LEFT JOIN (
                    SELECT
                        TRIM(cc.vendedor) AS vendedor,
                        SUM(cc.monto) AS cobranza_real
                    FROM cuenta_ctejf cc
                    WHERE cc.tip_mov = '-'
                      AND cc.fecha >= :fecha_ini_c
                      AND cc.fecha < :fecha_fin_c
                      AND {$sqlCobranza} = 'EFECTIVO'
                    GROUP BY TRIM(cc.vendedor)
                ) c ON c.vendedor = TRIM(m.cod_vendedor)
                WHERE m.anio = :anio
                  AND m.mes = :mes
                ORDER BY m.cod_vendedor ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_ini_c", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin_c", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();

        return self::aplicarVentaPermitida($stmt->fetchAll(PDO::FETCH_ASSOC), $anio, $mes);
    }

    static public function mdlAvanceVentasDashboard($anio, $mes, $codVendedor = "")
    {
        $rango = self::rangoMes($anio, $mes);
        $codVendedor = trim((string) $codVendedor);
        $filtroVendedor = "";

        if ($codVendedor !== "") {
            $filtroVendedor = " AND mv.codigo = :cod_vendedor";
        }

        $sql = "SELECT
                    mv.codigo AS cod_vendedor,
                    mv.descripcion AS nombre_vendedor,
                    meta.meta_venta,
                    COALESCE(v.venta_real, 0) AS venta_real,
                    COALESCE(p.soles_generados, 0) AS soles_generados,
                    COALESCE(p.soles_aprobados, 0) AS soles_aprobados,
                    COALESCE(p.soles_apt, 0) AS soles_apt,
                    COALESCE(p.soles_confirmados, 0) AS soles_confirmados
                FROM metas_vendedorjf meta
                INNER JOIN maestrajf mv
                    ON mv.codigo = meta.cod_vendedor
                   AND mv.tipo_dato = 'TVEND'
                   AND mv.estado_decisiones = 1
                LEFT JOIN (
                    SELECT
                        v.vendedor AS vendedor,
                        SUM(v.neto) AS venta_real
                    FROM ventajf v
                    WHERE v.fecha >= :fecha_ini
                      AND v.fecha < :fecha_fin
                      AND " . self::sqlTiposVentaReal("v") . "
                    GROUP BY v.vendedor
                ) v ON v.vendedor = meta.cod_vendedor
                LEFT JOIN (
                    SELECT
                        t.vendedor AS vendedor,
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
                        ) AS soles_confirmados
                    FROM temporaljf t
                    INNER JOIN maestrajf m_dd
                        ON m_dd.codigo = t.vendedor
                       AND m_dd.tipo_dato = 'TVEND'
                       AND m_dd.estado_decisiones = 1
                    WHERE t.estado NOT IN ('ANULADO', 'FACTURADO')
                    GROUP BY t.vendedor
                ) p ON p.vendedor = meta.cod_vendedor
                WHERE meta.anio = :anio
                  AND meta.mes = :mes
                  AND meta.meta_venta > 0
                  {$filtroVendedor}
                ORDER BY (
                    (COALESCE(v.venta_real, 0)
                     + COALESCE(p.soles_generados, 0)
                     + COALESCE(p.soles_aprobados, 0)
                     + COALESCE(p.soles_apt, 0)
                     + COALESCE(p.soles_confirmados, 0)) / meta.meta_venta
                ) ASC, mv.codigo ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        if ($codVendedor !== "") {
            $stmt->bindValue(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
        }
        $stmt->execute();

        $filas = self::aplicarVentaPermitida($stmt->fetchAll(PDO::FETCH_ASSOC), $anio, $mes);
        usort($filas, function ($a, $b) {
            $metaA = (float) $a["meta_venta"];
            $metaB = (float) $b["meta_venta"];
            $projA = (float) $a["venta_real"]
                + (float) $a["soles_generados"]
                + (float) $a["soles_aprobados"]
                + (float) $a["soles_apt"]
                + (float) $a["soles_confirmados"];
            $projB = (float) $b["venta_real"]
                + (float) $b["soles_generados"]
                + (float) $b["soles_aprobados"]
                + (float) $b["soles_apt"]
                + (float) $b["soles_confirmados"];
            $pctA = $metaA > 0 ? $projA / $metaA : 0;
            $pctB = $metaB > 0 ? $projB / $metaB : 0;
            if ($pctA == $pctB) {
                return strcmp($a["cod_vendedor"], $b["cod_vendedor"]);
            }
            return ($pctA < $pctB) ? -1 : 1;
        });
        return $filas;
    }
    static private function aplicarVentaPermitida($filas, $anio, $mes)
    {
        if (!is_array($filas) || !count($filas)) {
            return $filas;
        }
        require_once "metricas-comerciales.modelo.php";
        $mapa = ModeloMetricasComerciales::mdlVentaPermitidaPorVendedor($anio, $mes, false);
        foreach ($filas as &$fila) {
            $cod = isset($fila["cod_vendedor"]) ? trim((string) $fila["cod_vendedor"]) : "";
            if ($cod === "" && isset($fila["vendedor"])) {
                $cod = trim((string) $fila["vendedor"]);
            }
            $fila["venta_real"] = isset($mapa[$cod]) ? (float) $mapa[$cod] : 0.0;
            $fila["venta_fuera_cobertura"] = null;
        }
        unset($fila);
        return $filas;
    }

    static public function mdlEditarMeta($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE metas_vendedorjf
             SET meta_venta = :meta_venta,
                 meta_cobranza = :meta_cobranza,
                 usuario = :usuario
             WHERE id = :id"
        );

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
        $stmt->bindParam(":meta_venta", $datos["meta_venta"], PDO::PARAM_STR);
        $stmt->bindParam(":meta_cobranza", $datos["meta_cobranza"], $datos["meta_cobranza"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    static public function mdlEliminarMeta($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM metas_vendedorjf WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }
}
