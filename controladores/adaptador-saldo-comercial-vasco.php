<?php

/**
 * Adaptador de lectura: proyecta saldos oficiales a saldos comerciales
 * exclusivamente para el snapshot de VascoPro.
 *
 * No modifica SQL de otros módulos ni escribe en cuenta_ctejf.
 * No expone marcas de regularización al payload final (usar limpiarParaApi).
 */
class AdaptadorSaldoComercialVasco
{
    /**
     * Clave alineada al agrupamiento del exportador (tipo_doc + num_cta).
     */
    public static function claveDocumento($tipoDoc, $numCta)
    {
        return trim((string) $tipoDoc) . "|" . trim((string) $numCta);
    }

    public static function saldoComercial($saldoOficial, $montoAplicable)
    {
        $oficial = max(0, (float) $saldoOficial);
        $aplicable = max(0, (float) $montoAplicable);
        if ($aplicable > $oficial) {
            $aplicable = $oficial;
        }

        return round(max(0, $oficial - $aplicable), 2);
    }

    public static function esVencido($fechaVen, $hoy = null)
    {
        $fechaVen = trim((string) $fechaVen);
        if ($fechaVen === "" || $fechaVen === "0000-00-00" || strpos($fechaVen, "0000-00-00") === 0) {
            return false;
        }

        $dia = strlen($fechaVen) >= 10 ? substr($fechaVen, 0, 10) : $fechaVen;
        $hoy = $hoy !== null ? (string) $hoy : date("Y-m-d");

        return $dia < $hoy;
    }

    /**
     * Proyecta un documento oficial.
     * Devuelve null si el saldo comercial es cero (debe excluirse del payload).
     *
     * @param array $filaOficial tipo_doc, num_cta, monto, saldo, fecha, fecha_ven, ...
     * @param float $montoAplicable
     * @return array|null
     */
    public static function proyectarDocumento(array $filaOficial, $montoAplicable)
    {
        $saldoOficial = round((float) (isset($filaOficial["saldo"]) ? $filaOficial["saldo"] : 0), 2);
        $aplicable = max(0, (float) $montoAplicable);
        $saldoComercial = self::saldoComercial($saldoOficial, $aplicable);

        if ($saldoComercial <= 0) {
            return null;
        }

        $fila = $filaOficial;
        $fila["saldo"] = $saldoComercial;
        // Referencia interna; limpiarParaApi la elimina antes de VascoPro.
        $fila["_saldo_oficial"] = $saldoOficial;
        $fila["_monto_aplicable"] = round(min($aplicable, $saldoOficial), 2);

        return $fila;
    }

    /**
     * Proyecta resumen + documentos de un cliente consolidado.
     *
     * @param array $resumenOficial deuda_total, vencido_total, ...
     * @param array $documentosOficiales
     * @param array $mapaMontos clave "tipo|num" => monto_aplicable
     * @param string|null $hoy Y-m-d para tests
     * @return array{resumen: array, documentos: array, omitir: bool, sin_cambios: bool}
     */
    public static function proyectarCuenta(array $resumenOficial, array $documentosOficiales, array $mapaMontos, $hoy = null)
    {
        $docsComerciales = array();
        $deuda = 0.0;
        $vencido = 0.0;
        $huboAjuste = false;

        foreach ($documentosOficiales as $doc) {
            $clave = self::claveDocumento(
                isset($doc["tipo_doc"]) ? $doc["tipo_doc"] : "",
                isset($doc["num_cta"]) ? $doc["num_cta"] : ""
            );
            $aplicable = isset($mapaMontos[$clave]) ? (float) $mapaMontos[$clave] : 0.0;
            if ($aplicable > 0) {
                $huboAjuste = true;
            }

            $proy = self::proyectarDocumento($doc, $aplicable);
            if ($proy === null) {
                if ($aplicable > 0 || (isset($doc["saldo"]) && (float) $doc["saldo"] > 0)) {
                    $huboAjuste = true;
                }
                continue;
            }

            $docsComerciales[] = $proy;
            $deuda += (float) $proy["saldo"];
            if (self::esVencido(isset($proy["fecha_ven"]) ? $proy["fecha_ven"] : "", $hoy)) {
                $vencido += (float) $proy["saldo"];
            }
        }

        $deuda = round($deuda, 2);
        $vencido = round($vencido, 2);
        if ($vencido > $deuda) {
            $vencido = $deuda;
        }

        $resumen = $resumenOficial;
        $resumen["deuda_total"] = $deuda;
        $resumen["vencido_total"] = $vencido;
        $resumen["cant_docs"] = count($docsComerciales);

        // Identidad: sin mapa de montos y mismos docs ⇒ salida equivalente
        $sinCambios = !$huboAjuste && empty($mapaMontos);

        return array(
            "resumen" => $resumen,
            "documentos" => $docsComerciales,
            "omitir" => $deuda <= 0,
            "sin_cambios" => $sinCambios,
        );
    }

    /**
     * Quita campos internos antes de mapear al contrato VascoPro.
     */
    public static function limpiarDocumentoParaApi(array $fila)
    {
        unset($fila["_saldo_oficial"], $fila["_monto_aplicable"]);

        return $fila;
    }

    public static function limpiarDocumentosParaApi(array $documentos)
    {
        $limpios = array();
        foreach ($documentos as $doc) {
            $limpios[] = self::limpiarDocumentoParaApi($doc);
        }

        return $limpios;
    }

    /**
     * Carga montos aplicables desde BD para un listado de documentos oficiales.
     */
    public static function cargarMapaMontosAplicables(array $documentos)
    {
        if (!class_exists("ModeloRegularizacionesComerciales")) {
            return array();
        }

        if (empty($documentos)) {
            return array();
        }

        if (method_exists("ModeloRegularizacionesComerciales", "mdlHayRegularizacionesActivas")
            && !ModeloRegularizacionesComerciales::mdlHayRegularizacionesActivas()
        ) {
            return array();
        }

        return ModeloRegularizacionesComerciales::mdlSumaMontoAplicableActivasPorDocs($documentos);
    }

    /**
     * Punto de entrada para sync/auditoría: adapta un cliente del lote.
     *
     * @return array{resumen: array, documentos: array, omitir: bool}
     */
    public static function adaptarCuentaParaSync(array $resumenOficial, array $documentosOficiales, $hoy = null)
    {
        $mapa = self::cargarMapaMontosAplicables($documentosOficiales);

        if (empty($mapa)) {
            return array(
                "resumen" => $resumenOficial,
                "documentos" => $documentosOficiales,
                "omitir" => false,
                "sin_cambios" => true,
            );
        }

        $proy = self::proyectarCuenta($resumenOficial, $documentosOficiales, $mapa, $hoy);
        $proy["documentos"] = self::limpiarDocumentosParaApi($proy["documentos"]);

        return $proy;
    }

    /**
     * Adapta un lote completo doc_key => documentos y filas resumen.
     *
     * @param array $filasResumen listado de filas con doc_key, deuda_total, ...
     * @param array $docsPorDocKey doc_key => documentos
     * @return array{filas: array, docs_por_doc_key: array, omitidos: int}
     */
    public static function adaptarLote(array $filasResumen, array $docsPorDocKey, $hoy = null)
    {
        $todosDocs = array();
        foreach ($docsPorDocKey as $docs) {
            foreach ($docs as $doc) {
                $todosDocs[] = $doc;
            }
        }

        $mapaGlobal = self::cargarMapaMontosAplicables($todosDocs);

        if (empty($mapaGlobal)) {
            return array(
                "filas" => $filasResumen,
                "docs_por_doc_key" => $docsPorDocKey,
                "omitidos" => 0,
            );
        }

        $filasOut = array();
        $docsOut = array();
        $omitidos = 0;

        foreach ($filasResumen as $fila) {
            $docKey = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            $docs = ($docKey !== "" && isset($docsPorDocKey[$docKey]))
                ? $docsPorDocKey[$docKey]
                : array();

            $proy = self::proyectarCuenta($fila, $docs, $mapaGlobal, $hoy);
            if (!empty($proy["omitir"])) {
                $omitidos++;
                continue;
            }

            $filasOut[] = $proy["resumen"];
            if ($docKey !== "") {
                $docsOut[$docKey] = self::limpiarDocumentosParaApi($proy["documentos"]);
            }
        }

        return array(
            "filas" => $filasOut,
            "docs_por_doc_key" => $docsOut,
            "omitidos" => $omitidos,
        );
    }
}
