<?php

/**
 * Lógica de negocio de regularizaciones comerciales.
 * Sin HTTP ni HTML. No escribe en cuenta_ctejf.
 */
class ServicioRegularizacionesComerciales
{
    const ESTADO_ACTIVA = "ACTIVA";
    const ESTADO_RESUELTA = "RESUELTA_AUTOMATICA";
    const ESTADO_ANULADA = "ANULADA";
    const ESTADO_REVISION = "REQUIERE_REVISION";

    public static function calcularSaldoComercial($saldoOficial, $sumaAplicable)
    {
        $saldo = max(0, (float) $saldoOficial - max(0, (float) $sumaAplicable));

        return round($saldo, 2);
    }

    public static function buscarCargos(array $filtros)
    {
        $cargos = ModeloRegularizacionesComerciales::mdlBuscarCargosOficiales($filtros);
        $ids = array();
        foreach ($cargos as $cargo) {
            $ids[] = (int) $cargo["id"];
        }
        $sumas = ModeloRegularizacionesComerciales::mdlSumaMontoAplicableActivasPorCuentas($ids);

        foreach ($cargos as &$cargo) {
            $id = (int) $cargo["id"];
            $suma = isset($sumas[$id]) ? (float) $sumas[$id] : 0.0;
            $cargo["saldo_oficial"] = round((float) $cargo["saldo"], 2);
            $cargo["regularizacion_activa"] = $suma;
            $cargo["saldo_comercial"] = self::calcularSaldoComercial($cargo["saldo"], $suma);
        }
        unset($cargo);

        return $cargos;
    }

    public static function listar(array $filtros = array())
    {
        return ModeloRegularizacionesComerciales::mdlListar($filtros);
    }

    public static function ver($id)
    {
        $id = (int) $id;
        $reg = ModeloRegularizacionesComerciales::mdlObtenerPorId($id);
        if (!$reg) {
            return array("ok" => false, "msg" => "Regularización no encontrada.");
        }

        $cargo = ModeloRegularizacionesComerciales::mdlObtenerCargoOficial((int) $reg["cuenta_cte_id"]);
        $eventos = ModeloRegularizacionesComerciales::mdlEventosPorRegularizacion($id);
        $suma = 0.0;
        $activas = ModeloRegularizacionesComerciales::mdlListarActivasPorCuenta((int) $reg["cuenta_cte_id"]);
        foreach ($activas as $a) {
            $suma += (float) $a["monto_aplicable"];
        }

        $saldoOficial = $cargo ? (float) $cargo["saldo"] : null;

        return array(
            "ok" => true,
            "regularizacion" => $reg,
            "cargo" => $cargo,
            "eventos" => $eventos,
            "saldo_oficial" => $saldoOficial,
            "saldo_comercial" => $saldoOficial === null
                ? null
                : self::calcularSaldoComercial($saldoOficial, $suma),
        );
    }

    public static function crear(array $input, $usuarioId)
    {
        $usuarioId = (int) $usuarioId;
        $cuentaCteId = isset($input["cuenta_cte_id"]) ? (int) $input["cuenta_cte_id"] : 0;
        $monto = isset($input["monto"]) ? round((float) $input["monto"], 2) : 0.0;
        $fechaPago = isset($input["fecha_pago_cliente"]) ? trim((string) $input["fecha_pago_cliente"]) : "";
        $motivo = isset($input["motivo"]) ? trim((string) $input["motivo"]) : "";
        $sustento = isset($input["sustento_referencia"]) ? trim((string) $input["sustento_referencia"]) : "";
        $observacion = isset($input["observacion"]) ? trim((string) $input["observacion"]) : "";

        if ($cuentaCteId <= 0) {
            return array("ok" => false, "msg" => "Indique el cargo oficial.");
        }
        if ($monto <= 0) {
            return array("ok" => false, "msg" => "El monto debe ser mayor a cero.");
        }
        if ($fechaPago === "" || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
            return array("ok" => false, "msg" => "Fecha de pago inválida (YYYY-MM-DD).");
        }
        if ($motivo === "") {
            return array("ok" => false, "msg" => "Indique el motivo.");
        }
        if ($sustento === "") {
            return array("ok" => false, "msg" => "Indique el OP / nro. de recibo.");
        }
        if ($usuarioId <= 0) {
            return array("ok" => false, "msg" => "Usuario no válido.");
        }

        $cargo = ModeloRegularizacionesComerciales::mdlObtenerCargoOficial($cuentaCteId);
        if (!$cargo) {
            return array("ok" => false, "msg" => "Cargo oficial no encontrado.");
        }

        $saldoOficial = round((float) $cargo["saldo"], 2);
        if ($saldoOficial <= 0) {
            return array("ok" => false, "msg" => "El cargo no tiene saldo oficial pendiente.");
        }

        $activas = ModeloRegularizacionesComerciales::mdlListarActivasPorCuenta($cuentaCteId);
        $sumaActiva = 0.0;
        foreach ($activas as $a) {
            $sumaActiva += (float) $a["monto_aplicable"];
        }
        $disponible = round($saldoOficial - $sumaActiva, 2);
        if ($monto > $disponible + 0.001) {
            return array(
                "ok" => false,
                "msg" => "El monto supera el saldo comercial disponible (" . number_format($disponible, 2, ".", "") . ").",
            );
        }

        $corteId = ModeloRegularizacionesComerciales::mdlUltimoAbonoOficialId(
            $cargo["tipo_doc"],
            $cargo["num_cta"],
            $cargo["cliente"]
        );

        $db = Conexion::conectar();
        try {
            $db->beginTransaction();

            $id = ModeloRegularizacionesComerciales::mdlInsertar($db, array(
                "cuenta_cte_id" => $cuentaCteId,
                "tipo_doc" => $cargo["tipo_doc"],
                "num_cta" => $cargo["num_cta"],
                "cliente_codigo" => $cargo["cliente"],
                "monto_original" => $monto,
                "monto_aplicable" => $monto,
                "fecha_pago_cliente" => $fechaPago,
                "saldo_oficial_al_registrar" => $saldoOficial,
                "corte_movimiento_oficial_id" => $corteId,
                "estado" => self::ESTADO_ACTIVA,
                "motivo" => $motivo,
                "sustento_referencia" => $sustento,
                "observacion" => $observacion !== "" ? $observacion : null,
                "usuario_registro_id" => $usuarioId,
            ));

            ModeloRegularizacionesComerciales::mdlInsertarEvento($db, array(
                "regularizacion_id" => $id,
                "tipo_evento" => "ALTA",
                "estado_anterior" => null,
                "estado_nuevo" => self::ESTADO_ACTIVA,
                "monto_delta" => $monto,
                "monto_aplicable_resultante" => $monto,
                "movimiento_oficial_id" => null,
                "detalle_json" => json_encode(array(
                    "cuenta_cte_id" => $cuentaCteId,
                    "tipo_doc" => $cargo["tipo_doc"],
                    "num_cta" => $cargo["num_cta"],
                    "sustento_referencia" => $sustento,
                    "saldo_oficial" => $saldoOficial,
                )),
                "usuario_id" => $usuarioId,
                "origen" => "USUARIO",
            ));

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return array("ok" => false, "msg" => "No se pudo registrar la regularización.");
        }

        // Conciliación inmediata por si ya hay abonos posteriores al corte
        self::reconciliarPorCuenta($cuentaCteId, null, "AUTO_SYNC");

        return array(
            "ok" => true,
            "id" => $id,
            "saldo_comercial" => self::calcularSaldoComercial($saldoOficial, $sumaActiva + $monto),
        );
    }

    public static function anular($id, $motivoAnulacion, $usuarioId)
    {
        $id = (int) $id;
        $usuarioId = (int) $usuarioId;
        $motivoAnulacion = trim((string) $motivoAnulacion);

        if ($id <= 0) {
            return array("ok" => false, "msg" => "Regularización no indicada.");
        }
        if ($motivoAnulacion === "") {
            return array("ok" => false, "msg" => "Indique el motivo de anulación.");
        }
        if ($usuarioId <= 0) {
            return array("ok" => false, "msg" => "Usuario no válido.");
        }

        $reg = ModeloRegularizacionesComerciales::mdlObtenerPorId($id);
        if (!$reg) {
            return array("ok" => false, "msg" => "Regularización no encontrada.");
        }
        if (!in_array($reg["estado"], array(self::ESTADO_ACTIVA, self::ESTADO_REVISION), true)) {
            return array("ok" => false, "msg" => "Solo se pueden anular regularizaciones activas o en revisión.");
        }

        $montoAntes = (float) $reg["monto_aplicable"];
        $version = (int) $reg["version"];
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $ok = ModeloRegularizacionesComerciales::mdlAnular(
                $db,
                $id,
                $version,
                $usuarioId,
                $motivoAnulacion
            );
            if (!$ok) {
                throw new Exception("conflicto_version");
            }

            ModeloRegularizacionesComerciales::mdlInsertarEvento($db, array(
                "regularizacion_id" => $id,
                "tipo_evento" => "ANULACION",
                "estado_anterior" => $reg["estado"],
                "estado_nuevo" => self::ESTADO_ANULADA,
                "monto_delta" => -1 * $montoAntes,
                "monto_aplicable_resultante" => 0,
                "movimiento_oficial_id" => null,
                "detalle_json" => json_encode(array("motivo_anulacion" => $motivoAnulacion)),
                "usuario_id" => $usuarioId,
                "origen" => "USUARIO",
            ));

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($e->getMessage() === "conflicto_version") {
                return array("ok" => false, "msg" => "La regularización fue modificada por otro proceso. Recargue e intente de nuevo.");
            }

            return array("ok" => false, "msg" => "No se pudo anular la regularización.");
        }

        return array("ok" => true, "id" => $id);
    }

    /**
     * Conciliación FIFO por abonos oficiales vinculados (tipo_doc + num_cta + cliente).
     */
    public static function reconciliarPorCuenta($cuentaCteId, $usuarioId = null, $origen = "AUTO_SYNC")
    {
        $cuentaCteId = (int) $cuentaCteId;
        $cargo = ModeloRegularizacionesComerciales::mdlObtenerCargoOficial($cuentaCteId);
        if (!$cargo) {
            return array("ok" => false, "msg" => "Cargo no encontrado.", "cambios" => 0);
        }

        $activas = ModeloRegularizacionesComerciales::mdlListarActivasPorCuenta($cuentaCteId);
        if (empty($activas)) {
            return array("ok" => true, "msg" => "Sin regularizaciones activas.", "cambios" => 0);
        }

        $consumidos = ModeloRegularizacionesComerciales::mdlMovimientosOficialesConsumidos($cuentaCteId);
        $corteMin = null;
        foreach ($activas as $a) {
            $c = isset($a["corte_movimiento_oficial_id"]) ? (int) $a["corte_movimiento_oficial_id"] : 0;
            if ($corteMin === null || ($c > 0 && $c < $corteMin)) {
                $corteMin = $c > 0 ? $c : $corteMin;
            }
            if ($corteMin === null && $c <= 0) {
                $corteMin = 0;
            }
        }
        if ($corteMin === null) {
            $corteMin = 0;
        }

        $abonos = ModeloRegularizacionesComerciales::mdlAbonosOficialesPosteriores(
            $cargo["tipo_doc"],
            $cargo["num_cta"],
            $cargo["cliente"],
            $corteMin,
            $consumidos
        );

        $cambios = 0;
        foreach ($abonos as $abono) {
            $montoAbonoRestante = round((float) $abono["monto"], 2);
            if ($montoAbonoRestante <= 0) {
                continue;
            }

            // Releer activas en cada abono (FIFO por id de regularización)
            $activas = ModeloRegularizacionesComerciales::mdlListarActivasPorCuenta($cuentaCteId);
            foreach ($activas as $reg) {
                if ($montoAbonoRestante <= 0) {
                    break;
                }
                if ($reg["estado"] === self::ESTADO_REVISION) {
                    continue;
                }

                $pendiente = round((float) $reg["monto_aplicable"], 2);
                if ($pendiente <= 0) {
                    continue;
                }

                $corteReg = isset($reg["corte_movimiento_oficial_id"])
                    ? (int) $reg["corte_movimiento_oficial_id"]
                    : 0;
                if ($corteReg > 0 && (int) $abono["id"] <= $corteReg) {
                    continue;
                }

                $aplicar = min($pendiente, $montoAbonoRestante);
                $nuevoMonto = round($pendiente - $aplicar, 2);
                $nuevoEstado = $nuevoMonto <= 0.001
                    ? self::ESTADO_RESUELTA
                    : self::ESTADO_ACTIVA;
                if ($nuevoMonto <= 0.001) {
                    $nuevoMonto = 0;
                }

                $db = Conexion::conectar();
                try {
                    $db->beginTransaction();
                    $ok = ModeloRegularizacionesComerciales::mdlAplicarResolucion(
                        $db,
                        (int) $reg["id"],
                        (int) $reg["version"],
                        $nuevoMonto,
                        $nuevoEstado
                    );
                    if (!$ok) {
                        throw new Exception("conflicto_version");
                    }

                    $tipoEvento = $nuevoEstado === self::ESTADO_RESUELTA
                        ? "RESOLUCION_TOTAL"
                        : "RESOLUCION_PARCIAL";

                    ModeloRegularizacionesComerciales::mdlInsertarEvento($db, array(
                        "regularizacion_id" => (int) $reg["id"],
                        "tipo_evento" => $tipoEvento,
                        "estado_anterior" => $reg["estado"],
                        "estado_nuevo" => $nuevoEstado,
                        "monto_delta" => -1 * $aplicar,
                        "monto_aplicable_resultante" => $nuevoMonto,
                        "movimiento_oficial_id" => (int) $abono["id"],
                        "detalle_json" => json_encode(array(
                            "abono_monto" => (float) $abono["monto"],
                            "abono_fecha" => $abono["fecha"],
                            "aplicado" => $aplicar,
                        )),
                        "usuario_id" => $usuarioId,
                        "origen" => $origen,
                    ));

                    $db->commit();
                    $cambios++;
                    $montoAbonoRestante = round($montoAbonoRestante - $aplicar, 2);
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    // Continuar con siguiente; conflicto se reintenta en otra pasada
                }
            }
        }

        return array("ok" => true, "cambios" => $cambios);
    }

    /**
     * Revisión manual de un caso REQUIERE_REVISION o forzar re-conciliación.
     * No modifica contabilidad.
     */
    public static function reconciliarManual($id, $usuarioId, $accion = "reintentar")
    {
        $id = (int) $id;
        $usuarioId = (int) $usuarioId;
        $reg = ModeloRegularizacionesComerciales::mdlObtenerPorId($id);
        if (!$reg) {
            return array("ok" => false, "msg" => "Regularización no encontrada.");
        }
        if (!in_array($reg["estado"], array(self::ESTADO_ACTIVA, self::ESTADO_REVISION), true)) {
            return array("ok" => false, "msg" => "La regularización no admite conciliación.");
        }

        if ($accion === "marcar_revision") {
            $db = Conexion::conectar();
            try {
                $db->beginTransaction();
                $ok = ModeloRegularizacionesComerciales::mdlMarcarRequiereRevision(
                    $db,
                    $id,
                    (int) $reg["version"]
                );
                if (!$ok) {
                    throw new Exception("conflicto");
                }
                ModeloRegularizacionesComerciales::mdlInsertarEvento($db, array(
                    "regularizacion_id" => $id,
                    "tipo_evento" => "REQUIERE_REVISION",
                    "estado_anterior" => $reg["estado"],
                    "estado_nuevo" => self::ESTADO_REVISION,
                    "monto_delta" => 0,
                    "monto_aplicable_resultante" => (float) $reg["monto_aplicable"],
                    "movimiento_oficial_id" => null,
                    "detalle_json" => json_encode(array("motivo" => "Marcado manualmente")),
                    "usuario_id" => $usuarioId,
                    "origen" => "USUARIO",
                ));
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                return array("ok" => false, "msg" => "No se pudo marcar para revisión.");
            }

            return array("ok" => true, "id" => $id, "estado" => self::ESTADO_REVISION);
        }

        // Reactivar desde revisión y reintentar FIFO
        if ($reg["estado"] === self::ESTADO_REVISION && $accion === "reintentar") {
            $db = Conexion::conectar();
            try {
                $db->beginTransaction();
                $ok = ModeloRegularizacionesComerciales::mdlAplicarResolucion(
                    $db,
                    $id,
                    (int) $reg["version"],
                    (float) $reg["monto_aplicable"],
                    self::ESTADO_ACTIVA
                );
                if (!$ok) {
                    throw new Exception("conflicto");
                }
                ModeloRegularizacionesComerciales::mdlInsertarEvento($db, array(
                    "regularizacion_id" => $id,
                    "tipo_evento" => "REACTIVACION",
                    "estado_anterior" => self::ESTADO_REVISION,
                    "estado_nuevo" => self::ESTADO_ACTIVA,
                    "monto_delta" => 0,
                    "monto_aplicable_resultante" => (float) $reg["monto_aplicable"],
                    "movimiento_oficial_id" => null,
                    "detalle_json" => json_encode(array("accion" => "reintentar")),
                    "usuario_id" => $usuarioId,
                    "origen" => "USUARIO",
                ));
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                return array("ok" => false, "msg" => "No se pudo reactivar para conciliar.");
            }
        }

        $resultado = self::reconciliarPorCuenta((int) $reg["cuenta_cte_id"], $usuarioId, "USUARIO");
        $resultado["id"] = $id;

        return $resultado;
    }
}
