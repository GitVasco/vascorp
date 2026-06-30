<?php

require_once "conexion.php";

class ModeloDescuentosCompuestos
{
    /**
     * Lista descuentos compuestos ESSO desde la vista consolidada.
     *
     * @param string $origenNota AUTO|MANUAL|REVISAR|'' (todos)
     * @param string $cliente    Código de cliente o '' (todos)
     */
    public static function mdlListarDescuentosCompuestos($origenNota = "", $limite = 500, $cliente = "")
    {
        $limite = (int) $limite;

        if ($limite < 1) {
            $limite = 500;
        }

        $origenNota = trim((string) $origenNota);
        $cliente = trim((string) $cliente);
        $filtroOrigen = "";
        $filtroCliente = "";

        if ($origenNota !== "") {
            $filtroOrigen = " AND v.origen_nota = :origen_nota";
        } else {
            $filtroOrigen = " AND v.origen_nota <> 'DESCARTADO'";
        }

        if ($cliente !== "") {
            $filtroCliente = " AND v.cliente = :cliente";
        }

        $sql = "SELECT
            v.id,
            v.tipo_doc,
            v.num_cta,
            v.cod_pago,
            v.doc_origen,
            v.fecha,
            v.monto,
            v.cliente,
            v.nombre_cliente,
            v.vendedor,
            v.notas_original,
            v.formato_notas,
            v.pct1_parseo,
            v.pct2_parseo,
            v.estado_parseo,
            v.nota_estandar_propuesta,
            v.nota_estandar_manual,
            v.observacion_manual,
            v.estado_correccion,
            v.nota_estandar_final,
            v.pct1_final,
            v.pct2_final,
            v.origen_nota,
            v.monto_base_final,
            v.monto_pct1_final,
            v.monto_pct2_final
        FROM v_descuento_compuesto_esso v
        WHERE 1 = 1" . $filtroOrigen . $filtroCliente . "
        ORDER BY v.fecha DESC, v.id DESC
        LIMIT " . $limite;

        $stmt = Conexion::conectar()->prepare($sql);

        if ($origenNota !== "") {
            $stmt->bindParam(":origen_nota", $origenNota, PDO::PARAM_STR);
        }

        if ($cliente !== "") {
            $stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen agregado por cliente (excluye descartados).
     */
    public static function mdlResumenPorCliente()
    {
        $sql = "SELECT
            v.cliente AS codigo,
            COALESCE(MAX(v.nombre_cliente), v.cliente) AS nombre,
            COUNT(*) AS total,
            SUM(CASE WHEN v.origen_nota = 'AUTO' THEN 1 ELSE 0 END) AS sugeridos,
            SUM(CASE WHEN v.origen_nota = 'MANUAL' THEN 1 ELSE 0 END) AS confirmados,
            SUM(CASE WHEN v.origen_nota = 'REVISAR' THEN 1 ELSE 0 END) AS por_revisar,
            COALESCE(SUM(v.monto), 0) AS monto_total,
            COALESCE(SUM(v.monto_pct1_final), 0) AS monto_base,
            COALESCE(SUM(v.monto_pct2_final), 0) AS monto_adicional
        FROM v_descuento_compuesto_esso v
        WHERE v.origen_nota <> 'DESCARTADO'
            AND v.cliente IS NOT NULL AND v.cliente != ''
        GROUP BY v.cliente
        ORDER BY nombre ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista de clientes con descuentos compuestos (para el filtro).
     */
    public static function mdlClientesConDescuentos()
    {
        $sql = "SELECT
            v.cliente AS codigo,
            COALESCE(MAX(v.nombre_cliente), v.cliente) AS nombre,
            COUNT(*) AS total
        FROM v_descuento_compuesto_esso v
        WHERE v.cliente IS NOT NULL AND v.cliente != ''
        GROUP BY v.cliente
        ORDER BY nombre ASC";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro individual de la vista consolidada (para el modal).
     */
    public static function mdlObtenerDescuentoCompuesto($id)
    {
        $id = (int) $id;

        if ($id < 1) {
            return null;
        }

        $sql = "SELECT
            v.id,
            v.fecha,
            v.cliente,
            v.nombre_cliente,
            v.monto,
            v.notas_original,
            v.formato_notas,
            v.pct1_parseo,
            v.pct2_parseo,
            v.estado_parseo,
            v.nota_estandar_propuesta,
            v.nota_estandar_manual,
            v.observacion_manual,
            v.estado_correccion,
            v.nota_estandar_final,
            v.pct1_final,
            v.pct2_final,
            v.origen_nota,
            v.monto_pct1_final,
            v.monto_pct2_final
        FROM v_descuento_compuesto_esso v
        WHERE v.id = :id
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $fila : null;
    }

    /**
     * Resumen de conteos por origen (AUTO / MANUAL / REVISAR).
     *
     * @param string $cliente Código de cliente o '' (todos)
     */
    public static function mdlResumenDescuentosCompuestos($cliente = "")
    {
        $cliente = trim((string) $cliente);
        $filtroCliente = "";

        if ($cliente !== "") {
            $filtroCliente = " AND v.cliente = :cliente";
        }

        $sql = "SELECT
            v.origen_nota,
            COUNT(*) AS total,
            COALESCE(SUM(v.monto), 0) AS monto_total,
            COALESCE(SUM(v.monto_pct1_final), 0) AS monto_pct1_total,
            COALESCE(SUM(v.monto_pct2_final), 0) AS monto_pct2_total
        FROM v_descuento_compuesto_esso v
        WHERE 1 = 1" . $filtroCliente . "
        GROUP BY v.origen_nota
        ORDER BY v.origen_nota ASC";

        $stmt = Conexion::conectar()->prepare($sql);

        if ($cliente !== "") {
            $stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Parsea nota estándar DSCTO_p1_p2 y devuelve porcentajes.
     *
     * @return array|null ['pct1' => float, 'pct2' => float]
     */
    public static function parsearNotaEstandar($notaEstandar)
    {
        $notaEstandar = trim((string) $notaEstandar);

        if (!preg_match('/^DSCTO_([0-9]+(?:\.[0-9]+)?)_([0-9]+(?:\.[0-9]+)?)$/i', $notaEstandar, $coincidencias)) {
            return null;
        }

        $pct1 = (float) $coincidencias[1];
        $pct2 = (float) $coincidencias[2];

        if ($pct1 < 0 || $pct2 < 0 || $pct1 > 100 || $pct2 > 100) {
            return null;
        }

        return array(
            "pct1" => $pct1,
            "pct2" => $pct2,
        );
    }

    /**
     * Guarda o actualiza corrección manual de un descuento compuesto.
     */
    public static function mdlGuardarCorreccion($datos)
    {
        $id = (int) $datos["id"];
        $notaEstandar = strtoupper(trim((string) $datos["nota_estandar"]));
        $porcentajes = self::parsearNotaEstandar($notaEstandar);

        if ($id < 1 || $porcentajes === null) {
            return "Formato inválido. Use DSCTO_p1_p2 (ej. DSCTO_7_2).";
        }

        $observacion = isset($datos["observacion"]) ? trim((string) $datos["observacion"]) : "";
        $estado = isset($datos["estado"]) ? trim((string) $datos["estado"]) : "CONFIRMADO";
        $usureg = isset($datos["usureg"]) ? trim((string) $datos["usureg"]) : null;
        $pcreg = isset($datos["pcreg"]) ? trim((string) $datos["pcreg"]) : null;

        if (!in_array($estado, array("PENDIENTE", "CONFIRMADO", "RECHAZADO"), true)) {
            $estado = "CONFIRMADO";
        }

        $sql = "INSERT INTO descuento_correccionjf
            (id, nota_estandar, pct1, pct2, observacion, estado, usureg, pcreg)
        VALUES
            (:id, :nota_estandar, :pct1, :pct2, :observacion, :estado, :usureg, :pcreg)
        ON DUPLICATE KEY UPDATE
            nota_estandar = VALUES(nota_estandar),
            pct1 = VALUES(pct1),
            pct2 = VALUES(pct2),
            observacion = VALUES(observacion),
            estado = VALUES(estado),
            usureg = VALUES(usureg),
            pcreg = VALUES(pcreg)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":nota_estandar", $notaEstandar, PDO::PARAM_STR);
        $stmt->bindParam(":pct1", $porcentajes["pct1"], PDO::PARAM_STR);
        $stmt->bindParam(":pct2", $porcentajes["pct2"], PDO::PARAM_STR);
        $stmt->bindParam(":observacion", $observacion, PDO::PARAM_STR);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindParam(":usureg", $usureg, PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $pcreg, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    /**
     * Confirma la propuesta automática (copia nota_estandar_propuesta a corrección).
     */
    public static function mdlConfirmarPropuesta($id, $usureg = null, $pcreg = null)
    {
        $id = (int) $id;

        if ($id < 1) {
            return "ID inválido.";
        }

        $sql = "SELECT
            id,
            nota_estandar_propuesta,
            pct1,
            pct2,
            estado_parseo
        FROM v_descuento_compuesto_parseo
        WHERE id = :id
        LIMIT 1";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila || $fila["estado_parseo"] !== "OK" || empty($fila["nota_estandar_propuesta"])) {
            return "No hay propuesta automática válida para confirmar.";
        }

        return self::mdlGuardarCorreccion(array(
            "id" => $id,
            "nota_estandar" => $fila["nota_estandar_propuesta"],
            "observacion" => "Confirmación automática",
            "estado" => "CONFIRMADO",
            "usureg" => $usureg,
            "pcreg" => $pcreg,
        ));
    }

    /**
     * Marca un registro como DESCARTADO (no requiere corrección, deja de listarse).
     */
    public static function mdlDescartar($id, $usureg = null, $pcreg = null)
    {
        $id = (int) $id;

        if ($id < 1) {
            return "ID inválido.";
        }

        $sql = "INSERT INTO descuento_correccionjf
            (id, nota_estandar, pct1, pct2, observacion, estado, usureg, pcreg)
        VALUES
            (:id, '', NULL, NULL, 'Descartado: no requiere corrección', 'DESCARTADO', :usureg, :pcreg)
        ON DUPLICATE KEY UPDATE
            estado = 'DESCARTADO',
            observacion = 'Descartado: no requiere corrección',
            usureg = VALUES(usureg),
            pcreg = VALUES(pcreg)";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":usureg", $usureg, PDO::PARAM_STR);
        $stmt->bindParam(":pcreg", $pcreg, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }

    /**
     * Restaura un registro descartado (elimina la marca, vuelve a la lista).
     */
    public static function mdlRestaurar($id)
    {
        $id = (int) $id;

        if ($id < 1) {
            return "ID inválido.";
        }

        $sql = "DELETE FROM descuento_correccionjf
        WHERE id = :id AND estado = 'DESCARTADO'";

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        }

        return "error";
    }
}
