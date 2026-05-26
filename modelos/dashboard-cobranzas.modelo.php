<?php

require_once "conexion.php";

class ModeloDashboardCobranzas
{
    private static function sqlIngreso($alias = "cc")
    {
        return "CASE
            WHEN {$alias}.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82') THEN 'EFECTIVO'
            WHEN {$alias}.cod_pago IN ('13', '96') THEN 'DEVOLUCION'
            WHEN {$alias}.cod_pago IN ('97', '10') THEN 'DESCUENTOS'
            ELSE 'OTROS'
        END";
    }

    private static function rangoMes($anno, $mes)
    {
        $anno = (int) $anno;
        $mes = (int) $mes;
        $inicio = sprintf("%04d-%02d-01", $anno, $mes);

        if ($mes === 12) {
            $fin = sprintf("%04d-01-01", $anno + 1);
        } else {
            $fin = sprintf("%04d-%02d-01", $anno, $mes + 1);
        }

        return array("inicio" => $inicio, "fin" => $fin);
    }

    private static function bindFiltroPeriodo($stmt, $anno, $mes, $vendedor)
    {
        $rango = self::rangoMes($anno, $mes);

        $stmt->bindParam(":fecha_ini", $rango["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $rango["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
    }

    private static function wherePeriodo($alias = "cc")
    {
        return "{$alias}.tip_mov = '-'
            AND {$alias}.fecha >= :fecha_ini
            AND {$alias}.fecha < :fecha_fin
            AND (:vendedor = '' OR {$alias}.vendedor = :vendedor)";
    }

    static public function mdlComparativoDosPeriodos($anno, $mes, $annoAnt, $mesAnt, $vendedor = "")
    {
        $sqlIngreso = self::sqlIngreso("cc");
        $rangoAct = self::rangoMes($anno, $mes);
        $rangoAnt = self::rangoMes($annoAnt, $mesAnt);

        $sql = "SELECT
            COALESCE(SUM(
                CASE WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act THEN cc.monto ELSE 0 END
            ), 0) AS cobranza_total,
            SUM(
                CASE WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act THEN 1 ELSE 0 END
            ) AS operaciones,
            COUNT(DISTINCT
                CASE WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act THEN DATE(cc.fecha) END
            ) AS dias_con_movimiento,
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_act AND cc.fecha < :fin_act
                        AND " . $sqlIngreso . " IN ('DEVOLUCION', 'DESCUENTOS')
                    THEN cc.monto ELSE 0
                END
            ), 0) AS dev_descuentos,
            COALESCE(SUM(
                CASE WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant THEN cc.monto ELSE 0 END
            ), 0) AS cobranza_total_ant,
            SUM(
                CASE WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant THEN 1 ELSE 0 END
            ) AS operaciones_ant,
            COUNT(DISTINCT
                CASE WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant THEN DATE(cc.fecha) END
            ) AS dias_con_movimiento_ant,
            COALESCE(SUM(
                CASE
                    WHEN cc.fecha >= :ini_ant AND cc.fecha < :fin_ant
                        AND " . $sqlIngreso . " IN ('DEVOLUCION', 'DESCUENTOS')
                    THEN cc.monto ELSE 0
                END
            ), 0) AS dev_descuentos_ant
        FROM cuenta_ctejf cc
        WHERE cc.tip_mov = '-'
            AND (
                (cc.fecha >= :ini_act AND cc.fecha < :fin_act)
                OR (cc.fecha >= :ini_ant AND cc.fecha < :fin_ant)
            )
            AND (:vendedor = '' OR cc.vendedor = :vendedor)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":ini_act", $rangoAct["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fin_act", $rangoAct["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":ini_ant", $rangoAnt["inicio"], PDO::PARAM_STR);
        $stmt->bindParam(":fin_ant", $rangoAnt["fin"], PDO::PARAM_STR);
        $stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlMejorDia($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY monto DESC
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array("dia" => null, "monto" => 0);
        }

        return $fila;
    }

    static public function mdlMejorVendedor($anno, $mes, $vendedor = "")
    {
        $sql = "SELECT
            cc.vendedor,
            COALESCE(SUM(cc.monto), 0) AS monto,
            COALESCE(MAX(m.descripcion), cc.vendedor) AS nombre_vendedor
        FROM cuenta_ctejf cc
        LEFT JOIN maestrajf m
            ON cc.vendedor = m.codigo
            AND m.tipo_dato = 'TVEND'
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY cc.vendedor
        ORDER BY monto DESC
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return array("vendedor" => null, "monto" => 0, "nombre_vendedor" => "—");
        }

        return $fila;
    }

    static public function mdlSerieDiariaMes($anno, $mes, $vendedor = "")
    {
        $sqlIngreso = self::sqlIngreso("cc");

        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto,
            COUNT(*) AS operaciones,
            COALESCE(SUM(
                CASE
                    WHEN {$sqlIngreso} IN ('DEVOLUCION', 'DESCUENTOS') THEN cc.monto
                    ELSE 0
                END
            ), 0) AS dev_descuentos
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSerieDiariaVendedor($anno, $mes, $codigoVendedor)
    {
        if ($codigoVendedor === null || $codigoVendedor === "") {
            return array();
        }

        $vendedor = $codigoVendedor;

        $sql = "SELECT
            DAY(cc.fecha) AS dia,
            COALESCE(SUM(cc.monto), 0) AS monto
        FROM cuenta_ctejf cc
        WHERE " . self::wherePeriodo("cc") . "
        GROUP BY DAY(cc.fecha)
        ORDER BY dia ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        self::bindFiltroPeriodo($stmt, $anno, $mes, $vendedor);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlVendedoresConCobranza($anno)
    {
        $anno = (int) $anno;
        $inicio = $anno . "-01-01";
        $fin = ($anno + 1) . "-01-01";

        $sql = "SELECT DISTINCT
            cc.vendedor AS codigo,
            COALESCE(m.descripcion, cc.vendedor) AS descripcion
        FROM cuenta_ctejf cc
        LEFT JOIN maestrajf m
            ON cc.vendedor = m.codigo
            AND m.tipo_dato = 'TVEND'
        WHERE cc.tip_mov = '-'
            AND cc.fecha >= :fecha_ini
            AND cc.fecha < :fecha_fin
            AND cc.vendedor IS NOT NULL
            AND cc.vendedor != ''
        ORDER BY descripcion ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_ini", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $fin, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
