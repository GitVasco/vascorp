<?php

class ControladorEstadoCuenta
{
    private static function fmtMonto($valor)
    {
        return round((float) $valor, 2);
    }


    private static function mapDocumento($f)
    {
        $estadoRaw = isset($f["estado"]) ? trim((string) $f["estado"]) : "";
        $esPendiente = (strtolower($estadoRaw) === "pendiente");
        $fechaSort = !empty($f["fecha"]) ? substr((string) $f["fecha"], 0, 10) : "";
        $fechaVenSort = !empty($f["fecha_ven"]) ? substr((string) $f["fecha_ven"], 0, 10) : "";
        $ultPagoSort = !empty($f["ult_pago"]) ? substr((string) $f["ult_pago"], 0, 10) : "";
        return array(
            "id" => (int) $f["id"],
            "tipo_doc" => $f["tipo_doc"],
            "num_cta" => $f["num_cta"],
            "cod_pago" => $f["cod_pago"],
            "doc_origen" => $f["doc_origen"],
            "fecha" => $f["fecha_fmt"],
            "fecha_ven" => $f["fecha_ven_fmt"],
            "fecha_sort" => $fechaSort,
            "fecha_ven_sort" => $fechaVenSort,
            "ult_pago_sort" => $ultPagoSort,
            "monto" => self::fmtMonto($f["monto"]),
            "saldo" => self::fmtMonto($f["saldo"]),
            "ult_pago" => $f["ult_pago"] ? $f["ult_pago_fmt"] : "",
            "diferencia" => (int) $f["diferencia"],
            "banco" => $f["banco"],
            "num_unico" => $f["num_unico"],
            "vendedor" => $f["vendedor"],
            "renovacion" => ((int) $f["renovacion"] === 1),
            "protesta" => ((int) $f["protesta"] === 1),
            "estado" => $esPendiente ? "PENDIENTE" : "CANCELADO",
            "es_vencido" => ((int) $f["es_vencido"] === 1),
        );
    }

    private static function normalizarResumen($row)
    {
        if (!$row || !is_array($row)) {
            $row = array();
        }

        $deuda = self::fmtMonto(isset($row["total_deuda"]) ? $row["total_deuda"] : 0);
        $vencido = self::fmtMonto(isset($row["total_vencido"]) ? $row["total_vencido"] : 0);
        $pctMora = ($deuda > 0) ? round(($vencido / $deuda) * 100, 1) : 0.0;

        return array(
            "total_venta" => self::fmtMonto(isset($row["total_venta"]) ? $row["total_venta"] : 0),
            "total_deuda" => $deuda,
            "total_vencido" => $vencido,
            "pct_mora" => $pctMora,
            "docs_pendientes" => (int) (isset($row["docs_pendientes"]) ? $row["docs_pendientes"] : 0),
            "docs_vencidos" => (int) (isset($row["docs_vencidos"]) ? $row["docs_vencidos"] : 0),
            "docs_protestados" => (int) (isset($row["docs_protestados"]) ? $row["docs_protestados"] : 0),
            "total_locales" => (int) (isset($row["total_locales"]) ? $row["total_locales"] : 0),
        );
    }

    private static function creditoEntidad($modo, $codigo)
    {
        $codigo = trim((string) $codigo);
        $vacio = array(
            "linea_aprobada" => null,
            "linea_recomendada" => 0,
            "linea_referencia" => 0,
            "cupo_disponible" => 0,
            "utilizacion_pct" => 0,
            "excedido" => 0,
            "cupo_agotado" => false,
            "etiqueta_linea" => "Sin línea",
            "riesgo" => "sin_dato",
        );

        if ($codigo === "" || !class_exists("ControladorLineaCredito")) {
            return $vacio;
        }

        try {
            if ($modo === "grupo") {
                $ref = ControladorLineaCredito::ctrReferenciaCupoGrupo($codigo);
            } else {
                $ref = ControladorLineaCredito::ctrReferenciaCupoPedido($codigo);
            }
        } catch (Exception $e) {
            return $vacio;
        }

        if (!$ref || !is_array($ref)) {
            return $vacio;
        }

        $util = isset($ref["utilizacion_pct"]) ? (float) $ref["utilizacion_pct"] : 0;
        $excedido = isset($ref["excedido_sobre_recomendada"]) ? (float) $ref["excedido_sobre_recomendada"] : 0;
        $agotado = !empty($ref["cupo_agotado"]);

        if ($agotado || $excedido > 0 || $util >= 100) {
            $riesgo = "critico";
        } elseif ($util >= 80) {
            $riesgo = "alto";
        } elseif ($util >= 50) {
            $riesgo = "medio";
        } elseif ($util > 0) {
            $riesgo = "bajo";
        } else {
            $riesgo = "ok";
        }

        return array(
            "linea_aprobada" => isset($ref["linea_aprobada"]) && $ref["linea_aprobada"] !== null
                ? self::fmtMonto($ref["linea_aprobada"]) : null,
            "linea_recomendada" => self::fmtMonto(isset($ref["linea_recomendada"]) ? $ref["linea_recomendada"] : 0),
            "linea_referencia" => self::fmtMonto(isset($ref["linea_referencia"]) ? $ref["linea_referencia"] : 0),
            "cupo_disponible" => self::fmtMonto(isset($ref["cupo_disponible"]) ? $ref["cupo_disponible"] : 0),
            "utilizacion_pct" => round($util, 1),
            "excedido" => self::fmtMonto($excedido),
            "cupo_agotado" => $agotado,
            "etiqueta_linea" => isset($ref["etiqueta_linea"]) ? $ref["etiqueta_linea"] : "Línea",
            "riesgo" => $riesgo,
        );
    }

    public static function ctrBuscarClientes($q)
    {
        $filas = ModeloEstadoCuenta::mdlBuscarClientes($q, 80);
        $out = array();

        foreach ($filas as $f) {
            $grupo = isset($f["grupo"]) ? trim((string) $f["grupo"]) : "";
            $out[] = array(
                "codigo" => $f["codigo"],
                "nombre" => $f["nombre"],
                "documento" => isset($f["documento"]) ? $f["documento"] : "",
                "grupo" => $grupo,
                "grupo_nombre" => ($grupo !== "" && !empty($f["grupo_nombre"])) ? $f["grupo_nombre"] : "",
            );
        }

        return array("ok" => true, "clientes" => $out);
    }

    public static function ctrResumenCliente($codigo)
    {
        $codigo = trim((string) $codigo);
        if ($codigo === "") {
            return array("ok" => false, "msg" => "Seleccione un cliente.");
        }

        $cliente = ModeloEstadoCuenta::mdlClienteCabecera($codigo);
        if (!$cliente) {
            return array("ok" => false, "msg" => "Cliente no encontrado.");
        }

        $resumen = self::normalizarResumen(ModeloEstadoCuenta::mdlResumenCliente($codigo));
        $grupo = isset($cliente["grupo"]) ? trim((string) $cliente["grupo"]) : "";
        $credito = self::creditoEntidad($grupo !== "" ? "grupo" : "cliente", $grupo !== "" ? $grupo : $codigo);

        return array(
            "ok" => true,
            "modo" => "cliente",
            "cliente" => array(
                "codigo" => $cliente["codigo"],
                "nombre" => $cliente["nombre"],
                "documento" => isset($cliente["documento"]) ? $cliente["documento"] : "",
                "telefono" => isset($cliente["telefono"]) ? $cliente["telefono"] : "",
                "grupo" => $grupo,
                "grupo_nombre" => ($grupo !== "" && !empty($cliente["grupo_nombre"])) ? $cliente["grupo_nombre"] : "",
            ),
            "resumen" => $resumen,
            "credito" => $credito,
        );
    }

    public static function ctrResumenGrupo($codigoGrupo)
    {
        $codigoGrupo = trim((string) $codigoGrupo);
        if ($codigoGrupo === "") {
            return array("ok" => false, "msg" => "Seleccione un grupo empresarial.");
        }

        $grupo = ModeloGruposEmpresariales::mdlMostrarGrupos("codigo", $codigoGrupo);
        if (!$grupo || (int) $grupo["estado"] !== 1) {
            return array("ok" => false, "msg" => "Grupo empresarial no encontrado.");
        }

        $resumen = self::normalizarResumen(ModeloEstadoCuenta::mdlResumenGrupo($codigoGrupo));
        $localesRaw = ModeloEstadoCuenta::mdlLocalesGrupo($codigoGrupo);
        $deudaGrupo = $resumen["total_deuda"];
        $locales = array();

        foreach ($localesRaw as $loc) {
            $deuda = self::fmtMonto($loc["deuda"]);
            $pct = ($deudaGrupo > 0) ? round(($deuda / $deudaGrupo) * 100, 1) : 0;
            $locales[] = array(
                "codigo" => $loc["codigo"],
                "nombre" => $loc["nombre"],
                "documento" => isset($loc["documento"]) ? $loc["documento"] : "",
                "deuda" => $deuda,
                "vencido" => self::fmtMonto($loc["vencido"]),
                "docs_pendientes" => (int) $loc["docs_pendientes"],
                "pct_grupo" => $pct,
            );
        }

        $credito = self::creditoEntidad("grupo", $codigoGrupo);

        return array(
            "ok" => true,
            "modo" => "grupo",
            "grupo" => array(
                "codigo" => $grupo["codigo"],
                "nombre" => $grupo["nombre"],
            ),
            "resumen" => $resumen,
            "credito" => $credito,
            "locales" => $locales,
        );
    }

    public static function ctrDocumentos($codigo, $estado = "", $soloVencidos = false)
    {
        $codigo = trim((string) $codigo);
        if ($codigo === "") {
            return array("ok" => false, "msg" => "Seleccione un cliente.", "documentos" => array());
        }

        $filas = ModeloEstadoCuenta::mdlDocumentosCliente($codigo, $estado, $soloVencidos);
        $docs = array();
        foreach ($filas as $f) {
            $docs[] = self::mapDocumento($f);
        }

        return array("ok" => true, "documentos" => $docs);
    }

    public static function ctrDesgloseGrupo($codigoGrupo, $estado = "", $soloVencidos = false)
    {
        $base = self::ctrResumenGrupo($codigoGrupo);
        if (empty($base["ok"])) {
            return $base;
        }

        $locales = array();
        foreach ($base["locales"] as $loc) {
            $filas = ModeloEstadoCuenta::mdlDocumentosCliente($loc["codigo"], $estado, $soloVencidos);
            $docs = array();
            foreach ($filas as $f) {
                $docs[] = self::mapDocumento($f);
            }
            $loc["documentos"] = $docs;
            $loc["total_docs"] = count($docs);
            $locales[] = $loc;
        }

        $base["locales"] = $locales;
        return $base;
    }

    public static function ctrCancelaciones($tipoDoc, $numCta)
    {
        $tipoDoc = trim((string) $tipoDoc);
        $numCta = trim((string) $numCta);
        if ($tipoDoc === "" || $numCta === "") {
            return array("ok" => false, "msg" => "Documento incompleto.", "cancelaciones" => array());
        }

        $filas = ModeloEstadoCuenta::mdlCancelaciones($tipoDoc, $numCta);
        $out = array();
        foreach ($filas as $f) {
            $out[] = array(
                "cod_pago" => $f["cod_pago"],
                "doc_origen" => $f["doc_origen"],
                "fecha" => $f["fecha_fmt"],
                "notas" => $f["notas"],
                "monto" => self::fmtMonto($f["monto"]),
            );
        }

        return array("ok" => true, "cancelaciones" => $out);
    }

    public static function ctrPagos($tipo, $codigo)
    {
        $tipo = strtolower(trim((string) $tipo));
        $codigo = trim((string) $codigo);
        if ($codigo === "") {
            return array("ok" => false, "msg" => "Código vacío.", "pagos" => array());
        }

        if ($tipo === "grupo") {
            $filas = ModeloEstadoCuenta::mdlUltPagosGrupo($codigo);
        } else {
            $filas = ModeloEstadoCuenta::mdlUltPagosCliente($codigo);
        }

        $pagos = array();
        foreach ($filas as $f) {
            $pagos[] = array(
                "anno" => (int) $f["anno"],
                "mes" => $f["mes"],
                "monto" => self::fmtMonto($f["monto"]),
                "monto_jackyform" => self::fmtMonto($f["monto_jackyform"]),
                "monto_rosalinda" => self::fmtMonto($f["monto_rosalinda"]),
            );
        }

        return array("ok" => true, "pagos" => $pagos);
    }
}
