<?php

class ControladorVascoSync
{
    /** @var array|null mapa "tipo|serie" => "JF"|"RF" */
    private static $mapaBrandCodeSerie = null;

    /** @var array num_cta => fila cargo (cadena doc_origen) */
    private static $cacheCargosPorNumCta = array();

    private static function requireAdaptadorSaldoComercial()
    {
        if (!class_exists("AdaptadorSaldoComercialVasco")) {
            require_once __DIR__ . "/adaptador-saldo-comercial-vasco.php";
        }
        if (!class_exists("ModeloRegularizacionesComerciales")) {
            require_once dirname(__DIR__) . "/modelos/regularizaciones-comerciales.modelo.php";
        }
    }

    static public function maxClientesPorLote()
    {
        if (defined("VASCO_ONLINE_MAX_POR_LOTE")) {
            return (int) VASCO_ONLINE_MAX_POR_LOTE;
        }

        return 500;
    }

    static $DOC_TYPES_VALIDOS = array("0", "1", "4", "6", "7", "A", "B");

    static public function ctrAuditarClientes()
    {
        $resumenDb = ModeloVascoSync::mdlResumenClientes();
        $duplicadosFilas = ModeloVascoSync::mdlClientesDuplicadosDocumento();
        $sinDocumento = ModeloVascoSync::mdlClientesSinDocumento();
        $tipoInvalido = ModeloVascoSync::mdlClientesTipoDocumentoInvalido();

        $idsEnDuplicado = array();
        $gruposDuplicados = array();

        foreach ($duplicadosFilas as $fila) {
            $docKey = (string) $fila["doc_key"];
            $id = (int) $fila["id"];
            $idsEnDuplicado[$id] = true;

            if (!isset($gruposDuplicados[$docKey])) {
                $partes = explode(":", $docKey, 2);
                $gruposDuplicados[$docKey] = array(
                    "doc_key" => $docKey,
                    "tipo_documento" => isset($partes[0]) ? $partes[0] : "",
                    "documento" => isset($partes[1]) ? $partes[1] : "",
                    "cantidad" => (int) $fila["cantidad"],
                    "clientes" => array(),
                    "sugerido_id" => null,
                );
            }

            $cliente = self::formatearClienteAuditoria($fila);
            $gruposDuplicados[$docKey]["clientes"][] = $cliente;

            if ($gruposDuplicados[$docKey]["sugerido_id"] === null && self::esClienteActivo($fila)) {
                $gruposDuplicados[$docKey]["sugerido_id"] = $id;
            }
        }

        foreach ($gruposDuplicados as $docKey => $grupo) {
            if ($grupo["sugerido_id"] === null && !empty($grupo["clientes"])) {
                $gruposDuplicados[$docKey]["sugerido_id"] = (int) $grupo["clientes"][0]["id"];
            }
        }

        $total = isset($resumenDb["total"]) ? (int) $resumenDb["total"] : 0;
        $activos = isset($resumenDb["activos"]) ? (int) $resumenDb["activos"] : 0;
        $registrosEnDuplicado = count($duplicadosFilas);
        $gruposDuplicadosLista = array_values($gruposDuplicados);
        $cantidadGruposDuplicados = count($gruposDuplicadosLista);

        $bloqueadosEnvio = ModeloVascoSync::mdlContarBloqueadosEnvio();
        $listosSync = ModeloVascoSync::mdlContarDocumentosUnicosListos();
        $codigosExtraMismoDoc = max(0, $registrosEnDuplicado - $cantidadGruposDuplicados);

        $lotesEstimados = $listosSync > 0
            ? (int) ceil($listosSync / self::maxClientesPorLote())
            : 0;

        $sinDocumentoFormateado = array();
        foreach ($sinDocumento as $fila) {
            $sinDocumentoFormateado[] = self::formatearClienteAuditoria($fila);
        }

        $tipoInvalidoFormateado = array();
        foreach ($tipoInvalido as $fila) {
            $tipoInvalidoFormateado[] = self::formatearClienteAuditoria($fila);
        }

        return array(
            "ok" => true,
            "resumen" => array(
                "total" => $total,
                "activos" => $activos,
                "inactivos" => isset($resumenDb["inactivos"]) ? (int) $resumenDb["inactivos"] : 0,
                "sin_documento" => isset($resumenDb["sin_documento"]) ? (int) $resumenDb["sin_documento"] : 0,
                "sin_tipo_documento" => isset($resumenDb["sin_tipo_documento"]) ? (int) $resumenDb["sin_tipo_documento"] : 0,
                "tipo_documento_invalido" => ModeloVascoSync::mdlContarTipoDocumentoInvalido(),
                "grupos_duplicados" => $cantidadGruposDuplicados,
                "registros_en_duplicados" => $registrosEnDuplicado,
                "codigos_extra_mismo_doc" => $codigosExtraMismoDoc,
                "listos_sync" => $listosSync,
                "bloqueados_duplicado" => 0,
                "bloqueados_datos" => $bloqueadosEnvio,
                "bloqueados_envio" => $bloqueadosEnvio,
                "lotes_estimados" => $lotesEstimados,
                "max_por_lote" => self::maxClientesPorLote(),
            ),
            "duplicados" => $gruposDuplicadosLista,
            "advertencias" => array(
                "sin_documento" => $sinDocumentoFormateado,
                "tipo_documento_invalido" => $tipoInvalidoFormateado,
            ),
            "doc_types_validos" => self::$DOC_TYPES_VALIDOS,
            "external_id_campo" => "id",
        );
    }

    private static function formatearClienteAuditoria($fila)
    {
        return array(
            "id" => (int) $fila["id"],
            "codigo" => isset($fila["codigo"]) ? (string) $fila["codigo"] : "",
            "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
            "tipo_documento" => strtoupper(trim(isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "")),
            "documento" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
            "estado" => isset($fila["estado"]) ? (int) $fila["estado"] : 0,
            "activo" => self::esClienteActivo($fila),
            "vendedor" => isset($fila["vendedor"]) ? (string) $fila["vendedor"] : "",
            "grupo" => isset($fila["grupo"]) ? (string) $fila["grupo"] : "",
            "fecreg" => isset($fila["fecreg"]) ? (string) $fila["fecreg"] : "",
        );
    }

    private static function esClienteActivo($fila)
    {
        $estado = isset($fila["estado"]) ? (int) $fila["estado"] : 0;

        return $estado === 1 && !empty($fila["fecha"]);
    }

    static public function generarTraceId()
    {
        $sufijo = substr(md5(uniqid("vasco", true)), 0, 6);

        return "vascorp-sync-" . date("Ymd-His") . "-" . $sufijo;
    }

    static public function estadoApiCliente($fila)
    {
        if (self::esClienteActivo($fila)) {
            return 1;
        }

        return 2;
    }

    /**
     * Devuelve un celular peruano válido (9 dígitos, empieza en 9) o "".
     * Acepta formatos con espacios, guiones o prefijo 51; descarta fijos.
     *
     * @param string|null $raw
     * @return string
     */
    static public function telefonoMovilPeParaApi($raw)
    {
        $soloDigitos = preg_replace("/\D/", "", (string) $raw);

        if ($soloDigitos === "" || $soloDigitos === null) {
            return "";
        }

        if (strlen($soloDigitos) === 11 && substr($soloDigitos, 0, 2) === "51") {
            $soloDigitos = substr($soloDigitos, 2);
        }

        if (strlen($soloDigitos) === 9 && $soloDigitos[0] === "9") {
            return $soloDigitos;
        }

        return "";
    }

    /**
     * Cruza el failed[] que devuelve Vasco con los clientes enviados,
     * para que cada rechazo tenga código, documento y nombre legibles.
     *
     * @param array $failed
     * @param array $customers Clientes enviados en el lote (en orden)
     * @return array
     */
    static public function enriquecerFailedClientes($failed, $customers)
    {
        if (!is_array($failed) || count($failed) === 0) {
            return array();
        }

        $porExternalId = array();
        $porDoc = array();
        foreach ($customers as $pos => $cli) {
            if (!is_array($cli)) {
                continue;
            }
            if (isset($cli["external_id"]) && $cli["external_id"] !== "") {
                $porExternalId[(string) $cli["external_id"]] = $cli;
            }
            $docKey = (isset($cli["doc_type"]) ? $cli["doc_type"] : "") . "|" . (isset($cli["doc_number"]) ? $cli["doc_number"] : "");
            $porDoc[$docKey] = $cli;
        }

        $out = array();
        foreach ($failed as $idx => $f) {
            if (!is_array($f)) {
                $out[] = array("message" => (string) $f);
                continue;
            }

            $base = null;
            if (isset($f["index"]) && isset($customers[(int) $f["index"]]) && is_array($customers[(int) $f["index"]])) {
                $base = $customers[(int) $f["index"]];
            } elseif (isset($f["external_id"]) && isset($porExternalId[(string) $f["external_id"]])) {
                $base = $porExternalId[(string) $f["external_id"]];
            } else {
                $docKey = (isset($f["doc_type"]) ? $f["doc_type"] : "") . "|" . (isset($f["doc_number"]) ? $f["doc_number"] : "");
                if ($docKey !== "|" && isset($porDoc[$docKey])) {
                    $base = $porDoc[$docKey];
                } elseif (isset($customers[$idx]) && is_array($customers[$idx]) && count($failed) === count($customers)) {
                    $base = $customers[$idx];
                }
            }

            $message = "";
            if (isset($f["message"])) {
                $message = (string) $f["message"];
            } elseif (isset($f["error"])) {
                $message = (string) $f["error"];
            } elseif (isset($f["errors"])) {
                $message = is_array($f["errors"]) ? implode("; ", $f["errors"]) : (string) $f["errors"];
            }

            $out[] = array(
                "external_id" => isset($f["external_id"]) ? (string) $f["external_id"] : ($base && isset($base["external_id"]) ? (string) $base["external_id"] : ""),
                "code" => isset($f["code"]) ? (string) $f["code"] : ($base && isset($base["code"]) ? (string) $base["code"] : ""),
                "doc_type" => isset($f["doc_type"]) ? (string) $f["doc_type"] : ($base && isset($base["doc_type"]) ? (string) $base["doc_type"] : ""),
                "doc_number" => isset($f["doc_number"]) ? (string) $f["doc_number"] : ($base && isset($base["doc_number"]) ? (string) $base["doc_number"] : ""),
                "legal_name" => $base && isset($base["legal_name"]) ? (string) $base["legal_name"] : "",
                "message" => $message !== "" ? $message : "Rechazado por Vasco",
            );
        }

        return $out;
    }

    static public function mapearClienteParaApi($fila)
    {
        $nombre = trim(isset($fila["nombre"]) ? (string) $fila["nombre"] : "");
        if ($nombre === "") {
            $codigo = trim(isset($fila["codigo"]) ? (string) $fila["codigo"] : "");
            $nombre = $codigo !== "" ? $codigo : "CLIENTE " . (int) $fila["id"];
        }

        $cliente = array(
            "external_id" => (string) (int) $fila["id"],
            "doc_type" => strtoupper(trim(isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "")),
            "doc_number" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
            "legal_name" => $nombre,
            "state" => self::estadoApiCliente($fila),
        );

        $codigo = trim(isset($fila["codigo"]) ? (string) $fila["codigo"] : "");
        if ($codigo !== "") {
            $cliente["code"] = $codigo;
        }

        $direccion = trim(isset($fila["direccion"]) ? (string) $fila["direccion"] : "");
        if ($direccion !== "") {
            $cliente["address"] = $direccion;
        }

        $ubigeo = trim(isset($fila["ubigeo"]) ? (string) $fila["ubigeo"] : "");
        if ($ubigeo !== "") {
            $cliente["ubigeo"] = $ubigeo;
        }

        $telefono = self::telefonoMovilPeParaApi(isset($fila["telefono"]) ? $fila["telefono"] : "");
        if ($telefono === "") {
            $telefono = self::telefonoMovilPeParaApi(isset($fila["telefono2"]) ? $fila["telefono2"] : "");
        }
        if ($telefono !== "") {
            $cliente["phone"] = $telefono;
        }

        $email = trim(isset($fila["email"]) ? (string) $fila["email"] : "");
        if ($email !== "") {
            $cliente["email"] = $email;
        }

        return $cliente;
    }

    /**
     * POST /v2/sync/customers-bulk — un lote.
     *
     * @param int $numeroLote 1-based
     * @param string $traceId
     * @return array
     */
    static public function ctrSincronizarLoteClientes($numeroLote, $traceId)
    {
        if (!function_exists("obtenerUrlSyncClientesVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $numeroLote = (int) $numeroLote;
        $traceId = trim((string) $traceId);
        $maxPorLote = self::maxClientesPorLote();

        if ($numeroLote < 1) {
            return array("ok" => false, "msg" => "Número de lote inválido");
        }

        if ($traceId === "") {
            $traceId = self::generarTraceId();
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $url = obtenerUrlSyncClientesVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de sync no configurada");
        }

        $offset = ($numeroLote - 1) * $maxPorLote;
        $filas = ModeloVascoSync::mdlClientesParaSyncLote($offset, $maxPorLote);

        if (empty($filas)) {
            return array(
                "ok" => true,
                "msg" => "Lote vacío (sin más registros)",
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "customers_sent" => 0,
                "skipped" => true,
            );
        }

        $customers = array();
        foreach ($filas as $fila) {
            $customers[] = self::mapearClienteParaApi($fila);
        }

        $payload = array(
            "trace_id" => $traceId,
            "batch" => $numeroLote,
            "customers" => $customers,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el lote a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $apiKey;

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : array();

        $processed = isset($results["processed"]) ? (int) $results["processed"] : count($customers);
        $inserted = isset($results["inserted"]) ? (int) $results["inserted"] : 0;
        $updated = isset($results["updated"]) ? (int) $results["updated"] : 0;
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $failed = self::enriquecerFailedClientes($failed, $customers);

        if ($httpCode === 200 || $httpCode === 207) {
            $parcial = $httpCode === 207 || count($failed) > 0;

            return array(
                "ok" => true,
                "partial" => $parcial,
                "msg" => $parcial
                    ? "Lote " . $numeroLote . " con advertencias (" . count($failed) . " fallidos)"
                    : "Lote " . $numeroLote . " enviado correctamente",
                "batch" => $numeroLote,
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "customers_sent" => count($customers),
                "processed" => $processed,
                "inserted" => $inserted,
                "updated" => $updated,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "batch" => $numeroLote,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "customers_sent" => count($customers),
            "body" => $body,
            "url" => $url,
        );
    }

    static public function maxDocsPendientesPorCliente()
    {
        return 500;
    }

    static public function generarTraceIdCuentas()
    {
        $sufijo = substr(md5(uniqid("ec", true)), 0, 6);

        return "vascorp-ec-" . date("Ymd-His") . "-" . $sufijo;
    }

    static public function ctrAuditarCuentas()
    {
        self::requireAdaptadorSaldoComercial();

        $global = ModeloVascoSync::mdlCuentasResumenGlobal();
        $clientesConDeuda = isset($global["clientes_con_deuda"]) ? (int) $global["clientes_con_deuda"] : 0;
        $docsPendientes = isset($global["docs_pendientes"]) ? (int) $global["docs_pendientes"] : 0;
        $deudaTotal = isset($global["deuda_total"]) ? (float) $global["deuda_total"] : 0;
        $vencidoTotal = isset($global["vencido_total"]) ? (float) $global["vencido_total"] : 0;

        $impactoComercial = 0.0;
        if (ModeloRegularizacionesComerciales::mdlHayRegularizacionesActivas()) {
            $impactoComercial = ModeloRegularizacionesComerciales::mdlSumaMontoAplicableActivoTotal();
            $deudaTotal = max(0, round($deudaTotal - $impactoComercial, 2));
            if ($vencidoTotal > $deudaTotal) {
                $vencidoTotal = $deudaTotal;
            }
        }

        $bloqueadosSinDoc = ModeloVascoSync::mdlCuentasContarBloqueadosSinDoc();
        $bloqueadosExceso = ModeloVascoSync::mdlCuentasContarBloqueadosExcesoDocs();
        $maxPorLote = self::maxClientesPorLote();
        $lotesEstimados = $clientesConDeuda > 0 ? (int) ceil($clientesConDeuda / $maxPorLote) : 0;

        $muestra = ModeloVascoSync::mdlCuentasMuestra(30);
        $muestraFormateada = array();
        $docKeysMuestra = array();
        foreach ($muestra as $fila) {
            $dk = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            if ($dk !== "") {
                $docKeysMuestra[] = $dk;
            }
        }
        $docsMuestra = !empty($docKeysMuestra)
            ? ModeloVascoSync::mdlCuentasDocsPendientesPorDocKeys($docKeysMuestra)
            : array();
        $loteMuestra = AdaptadorSaldoComercialVasco::adaptarLote($muestra, $docsMuestra);
        foreach ($loteMuestra["filas"] as $fila) {
            $muestraFormateada[] = self::formatearCuentaAuditoria($fila);
        }

        $bloqueosSinDoc = ModeloVascoSync::mdlCuentasBloqueadosSinDoc(50);
        $bloqueosExceso = ModeloVascoSync::mdlCuentasBloqueadosExcesoDocs(50);

        $bloqueosSinDocFmt = array();
        foreach ($bloqueosSinDoc as $fila) {
            $bloqueosSinDocFmt[] = array(
                "codigo" => isset($fila["codigo"]) ? (string) $fila["codigo"] : "",
                "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
                "tipo_documento" => strtoupper(trim(isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "")),
                "documento" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
                "cant_docs" => isset($fila["cant_docs"]) ? (int) $fila["cant_docs"] : 0,
                "deuda" => isset($fila["deuda"]) ? (float) $fila["deuda"] : 0,
                "motivo" => "sin_documento_valido",
            );
        }

        $bloqueosExcesoFmt = array();
        foreach ($bloqueosExceso as $fila) {
            $item = self::formatearCuentaAuditoria($fila);
            $item["motivo"] = "exceso_documentos";
            $bloqueosExcesoFmt[] = $item;
        }

        return array(
            "ok" => true,
            "resumen" => array(
                "clientes_con_deuda" => $clientesConDeuda,
                "docs_pendientes" => $docsPendientes,
                "deuda_total" => round($deudaTotal, 2),
                "vencido_total" => round($vencidoTotal, 2),
                "impacto_regularizaciones" => $impactoComercial,
                "lotes_estimados" => $lotesEstimados,
                "max_por_lote" => $maxPorLote,
                "max_docs_cliente" => self::maxDocsPendientesPorCliente(),
                "sin_documento" => $bloqueadosSinDoc,
                "exceso_documentos" => $bloqueadosExceso,
                "consolidados" => ModeloVascoSync::mdlCuentasContarConsolidados(),
                "bloqueados_envio" => $bloqueadosSinDoc + $bloqueadosExceso,
                "listos_sync" => $clientesConDeuda,
            ),
            "muestra" => $muestraFormateada,
            "bloqueos" => array(
                "sin_documento" => $bloqueosSinDocFmt,
                "exceso_documentos" => $bloqueosExcesoFmt,
            ),
        );
    }

    private static function formatearCuentaAuditoria($fila)
    {
        return array(
            "doc_key" => isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "",
            "tipo_documento" => strtoupper(trim(isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "")),
            "documento" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
            "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
            "deuda_total" => isset($fila["deuda_total"]) ? round((float) $fila["deuda_total"], 2) : 0,
            "vencido_total" => isset($fila["vencido_total"]) ? round((float) $fila["vencido_total"], 2) : 0,
            "cant_docs" => isset($fila["cant_docs"]) ? (int) $fila["cant_docs"] : 0,
            "cant_codigos" => isset($fila["cant_codigos"]) ? (int) $fila["cant_codigos"] : 0,
        );
    }

    private static function fechaApiCuenta($fecha)
    {
        $valor = trim((string) $fecha);

        if ($valor === "" || $valor === "0000-00-00" || $valor === "0000-00-00 00:00:00") {
            return "";
        }

        if (strlen($valor) >= 10) {
            return substr($valor, 0, 10);
        }

        return $valor;
    }

    /**
     * Monto en soles con 2 decimales estables para JSON.
     */
    private static function montoApi($valor)
    {
        return (float) number_format((float) $valor, 2, ".", "");
    }

    static public function normalizarTipoDocComercial($tipoDoc)
    {
        $tipo = strtoupper(trim((string) $tipoDoc));

        if ($tipo === "") {
            return "";
        }

        if (strlen($tipo) < 2) {
            return str_pad($tipo, 2, "0", STR_PAD_LEFT);
        }

        return $tipo;
    }

    /**
     * Primer texto no vacío (trim) entre candidatos; string vacío si todos vacíos.
     */
    static public function primerTextoNoVacio()
    {
        $args = func_get_args();
        foreach ($args as $valor) {
            $texto = trim((string) $valor);
            if ($texto !== "") {
                return $texto;
            }
        }

        return "";
    }

    /**
     * Mapea una fila tip_mov='-' a payments[] del contrato.
     * Devuelve null si faltan fecha o monto > 0.
     *
     * @param array $fila
     * @return array|null
     */
    static public function mapearAbonoParaApi($fila)
    {
        $paymentDate = self::fechaApiCuenta(isset($fila["fecha"]) ? $fila["fecha"] : "");
        $amount = self::montoApi(isset($fila["monto"]) ? $fila["monto"] : 0);

        if ($paymentDate === "" || $amount <= 0) {
            return null;
        }

        $paymentCode = trim(isset($fila["cod_pago"]) ? (string) $fila["cod_pago"] : "");
        $methodLabel = trim(isset($fila["descripcion"]) ? (string) $fila["descripcion"] : "");
        if ($methodLabel === "" && $paymentCode !== "") {
            $methodLabel = $paymentCode;
        }

        $abono = array(
            "payment_date" => $paymentDate,
            "amount" => $amount,
        );

        if ($paymentCode !== "") {
            $abono["payment_code"] = $paymentCode;
        }

        if ($methodLabel !== "") {
            $abono["method_label"] = $methodLabel;
        }

        $reference = self::primerTextoNoVacio(
            isset($fila["notas"]) ? $fila["notas"] : "",
            isset($fila["doc_origen"]) ? $fila["doc_origen"] : "",
            isset($fila["banco"]) ? $fila["banco"] : ""
        );
        if ($reference !== "") {
            $abono["reference"] = $reference;
        }

        if (isset($fila["id"]) && $fila["id"] !== "" && $fila["id"] !== null) {
            $abono["external_id"] = (string) $fila["id"];
        }

        return $abono;
    }

    /**
     * Longitud de serie en num_cta: proformas (09) = 3; resto fiscal = 4.
     */
    static public function longitudSerieDocumento($tipoDoc)
    {
        $tipo = self::normalizarTipoDocComercial($tipoDoc);

        return ($tipo === "09") ? 3 : 4;
    }

    /**
     * Extrae la serie fiscal desde num_cta (o doc_origen).
     * Si hay guion, usa la parte izquierda (F001-000123 → F001; letras no deben usar este camino).
     */
    static public function extraerSerieDocumento($tipoDoc, $numCta)
    {
        $num = strtoupper(trim((string) $numCta));
        $num = preg_replace('/\s+/', '', $num);
        if ($num === null || $num === "") {
            return "";
        }

        $guion = strpos($num, "-");
        if ($guion !== false) {
            $num = substr($num, 0, $guion);
        }

        $len = self::longitudSerieDocumento($tipoDoc);
        if (strlen($num) < $len) {
            return "";
        }

        return substr($num, 0, $len);
    }

    /**
     * Grupo X → JF, Y → RF. Solo si el conjunto de grupos es unívoco.
     *
     * @param array $grupos
     * @return string|null
     */
    static public function brandCodeDesdeGrupos(array $grupos)
    {
        $codigos = array();
        foreach ($grupos as $g) {
            $g = strtoupper(trim((string) $g));
            if ($g === "X") {
                $codigos["JF"] = true;
            } elseif ($g === "Y") {
                $codigos["RF"] = true;
            }
        }

        if (count($codigos) === 1) {
            $keys = array_keys($codigos);
            return $keys[0];
        }

        return null;
    }

    /**
     * Carga (lazy) mapa tipo|serie → JF|RF desde amarre serie↔marcas↔grupos.
     */
    static public function asegurarMapaBrandCodeSerie()
    {
        if (self::$mapaBrandCodeSerie !== null) {
            return;
        }

        $gruposPorSerie = ModeloVascoSync::mdlGruposPorSerieDocumento();
        $mapa = array();

        foreach ($gruposPorSerie as $clave => $grupos) {
            $brand = self::brandCodeDesdeGrupos(is_array($grupos) ? $grupos : array());
            if ($brand !== null) {
                $mapa[$clave] = $brand;
            }
        }

        self::$mapaBrandCodeSerie = $mapa;
    }

    /**
     * Precarga cargos por num_cta para resolver cadenas doc_origen de letras del lote.
     *
     * @param array $documentos
     */
    static public function prepararBrandCodeParaLote(array $documentos)
    {
        self::asegurarMapaBrandCodeSerie();

        $pendientes = array();
        foreach ($documentos as $doc) {
            if (!is_array($doc)) {
                continue;
            }
            $tipo = self::normalizarTipoDocComercial(isset($doc["tipo_doc"]) ? $doc["tipo_doc"] : "");
            if ($tipo !== "85") {
                continue;
            }
            $origen = trim(isset($doc["doc_origen"]) ? (string) $doc["doc_origen"] : "");
            if ($origen !== "" && !isset(self::$cacheCargosPorNumCta[$origen])) {
                $pendientes[$origen] = true;
            }
        }

        $ronda = 0;
        while (!empty($pendientes) && $ronda < 10) {
            $ronda++;
            $lista = array_keys($pendientes);
            $pendientes = array();
            $cargos = ModeloVascoSync::mdlCuentasCargoPorNumCtas($lista);

            foreach ($lista as $num) {
                if (!isset(self::$cacheCargosPorNumCta[$num])) {
                    self::$cacheCargosPorNumCta[$num] = isset($cargos[$num]) ? $cargos[$num] : null;
                }
            }

            foreach ($cargos as $cargo) {
                $tipo = self::normalizarTipoDocComercial(isset($cargo["tipo_doc"]) ? $cargo["tipo_doc"] : "");
                if ($tipo !== "85") {
                    continue;
                }
                $origen = trim(isset($cargo["doc_origen"]) ? (string) $cargo["doc_origen"] : "");
                if ($origen !== "" && !array_key_exists($origen, self::$cacheCargosPorNumCta)) {
                    $pendientes[$origen] = true;
                }
            }
        }
    }

    /**
     * @param string $tipoDoc
     * @param string $serie
     * @return string|null
     */
    static public function brandCodePorTipoSerie($tipoDoc, $serie)
    {
        self::asegurarMapaBrandCodeSerie();
        $tipo = self::normalizarTipoDocComercial($tipoDoc);
        $serie = strtoupper(trim((string) $serie));
        if ($tipo === "" || $serie === "") {
            return null;
        }

        $clave = $tipo . "|" . $serie;
        return isset(self::$mapaBrandCodeSerie[$clave]) ? self::$mapaBrandCodeSerie[$clave] : null;
    }

    /**
     * Cuando solo hay número (cargo fiscal borrado al pasar a letras): probar tipos probables.
     *
     * @param string $numCta
     * @return string|null
     */
    static public function brandCodeDesdeNumeroSinTipo($numCta)
    {
        $num = strtoupper(trim((string) $numCta));
        $num = preg_replace('/\s+/', '', $num);
        if ($num === null || $num === "") {
            return null;
        }

        $candidatos = array();
        $inicial = substr($num, 0, 1);

        $probar = array();
        if ($inicial === "F") {
            $probar[] = "01";
            $probar[] = "07";
            $probar[] = "08";
        } elseif ($inicial === "B") {
            $probar[] = "03";
            $probar[] = "07";
            $probar[] = "08";
        } else {
            $probar[] = "09";
            $probar[] = "01";
            $probar[] = "03";
            $probar[] = "07";
            $probar[] = "08";
            $probar[] = "90";
        }

        foreach ($probar as $tipo) {
            $serie = self::extraerSerieDocumento($tipo, $num);
            $brand = self::brandCodePorTipoSerie($tipo, $serie);
            if ($brand !== null) {
                $candidatos[$brand] = true;
            }
        }

        if (count($candidatos) === 1) {
            $keys = array_keys($candidatos);
            return $keys[0];
        }

        return null;
    }

    /**
     * Resuelve JF/RF. Letras: sube por doc_origen. Sin clasificación → JF.
     *
     * @param array $fila
     * @return string
     */
    static public function mapBrandCode(array $fila)
    {
        $tipo = self::normalizarTipoDocComercial(isset($fila["tipo_doc"]) ? $fila["tipo_doc"] : "");
        $num = trim(isset($fila["num_cta"]) ? (string) $fila["num_cta"] : "");
        $origen = trim(isset($fila["doc_origen"]) ? (string) $fila["doc_origen"] : "");
        $brand = self::resolverBrandCodeRecursivo($tipo, $num, $origen, array());

        return ($brand === "RF") ? "RF" : "JF";
    }

    /**
     * @param string $tipoDoc
     * @param string $numCta
     * @param string $docOrigen
     * @param array $visitados
     * @return string|null
     */
    static public function resolverBrandCodeRecursivo($tipoDoc, $numCta, $docOrigen, array $visitados)
    {
        $tipo = self::normalizarTipoDocComercial($tipoDoc);
        $num = trim((string) $numCta);
        $origen = trim((string) $docOrigen);
        $claveVisita = $tipo . "|" . $num;

        if ($claveVisita !== "|" && isset($visitados[$claveVisita])) {
            return null;
        }
        if (count($visitados) >= 10) {
            return null;
        }
        if ($claveVisita !== "|") {
            $visitados[$claveVisita] = true;
        }

        if ($tipo === "85") {
            if ($origen === "") {
                return null;
            }

            if (!array_key_exists($origen, self::$cacheCargosPorNumCta)) {
                $cargos = ModeloVascoSync::mdlCuentasCargoPorNumCtas(array($origen));
                self::$cacheCargosPorNumCta[$origen] = isset($cargos[$origen]) ? $cargos[$origen] : null;
            }

            $padre = self::$cacheCargosPorNumCta[$origen];
            if (is_array($padre)) {
                return self::resolverBrandCodeRecursivo(
                    isset($padre["tipo_doc"]) ? $padre["tipo_doc"] : "",
                    isset($padre["num_cta"]) ? $padre["num_cta"] : $origen,
                    isset($padre["doc_origen"]) ? $padre["doc_origen"] : "",
                    $visitados
                );
            }

            return self::brandCodeDesdeNumeroSinTipo($origen);
        }

        if ($tipo === "" || $num === "") {
            if ($origen !== "") {
                return self::brandCodeDesdeNumeroSinTipo($origen);
            }
            return null;
        }

        $serie = self::extraerSerieDocumento($tipo, $num);
        $brand = self::brandCodePorTipoSerie($tipo, $serie);
        if ($brand !== null) {
            return $brand;
        }

        if ($origen !== "") {
            return self::brandCodeDesdeNumeroSinTipo($origen);
        }

        return null;
    }

    static public function mapearDocumentoPendienteParaApi($fila, array $abonosFilas = null)
    {
        $doc = array(
            "doc_type" => self::normalizarTipoDocComercial(isset($fila["tipo_doc"]) ? $fila["tipo_doc"] : ""),
            "doc_number" => trim(isset($fila["num_cta"]) ? (string) $fila["num_cta"] : ""),
            "brand_code" => self::mapBrandCode(is_array($fila) ? $fila : array()),
            "amount" => self::montoApi(isset($fila["monto"]) ? $fila["monto"] : 0),
            "balance" => self::montoApi(isset($fila["saldo"]) ? $fila["saldo"] : 0),
        );

        if (isset($fila["id"]) && $fila["id"] !== "" && $fila["id"] !== null) {
            $doc["external_id"] = (string) $fila["id"];
        }

        $uniqueNumber = trim(isset($fila["num_unico"]) ? (string) $fila["num_unico"] : "");
        if ($uniqueNumber !== "") {
            $doc["unique_number"] = $uniqueNumber;
        }

        $protesta = isset($fila["protesta"]) ? $fila["protesta"] : 0;
        $doc["protested"] = ((string) $protesta === "1" || (int) $protesta === 1);

        $issue = self::fechaApiCuenta(isset($fila["fecha"]) ? $fila["fecha"] : "");
        if ($issue !== "") {
            $doc["issue_date"] = $issue;
        }

        $due = self::fechaApiCuenta(isset($fila["fecha_ven"]) ? $fila["fecha_ven"] : "");
        if ($due !== "") {
            $doc["due_date"] = $due;
        }

        if ($abonosFilas !== null && !empty($abonosFilas)) {
            $payments = array();
            $maxPorDoc = (int) ModeloVascoSync::$MAX_ABONOS_POR_DOCUMENTO;
            foreach ($abonosFilas as $abonoFila) {
                if (count($payments) >= $maxPorDoc) {
                    break;
                }
                $mapped = self::mapearAbonoParaApi($abonoFila);
                if ($mapped !== null) {
                    $payments[] = $mapped;
                }
            }
            if (!empty($payments)) {
                $doc["payments"] = $payments;
            }
        }

        return $doc;
    }

    /**
     * @param array $resumen
     * @param array $documentos
     * @param array $abonosPorDoc clave "tipo_doc|num_cta" => filas ERP
     * @return array
     */
    static public function mapearCuentaParaApi($resumen, $documentos, array $abonosPorDoc = array())
    {
        $deuda = self::montoApi(isset($resumen["deuda_total"]) ? $resumen["deuda_total"] : 0);
        $vencido = self::montoApi(isset($resumen["vencido_total"]) ? $resumen["vencido_total"] : 0);

        if ($vencido > $deuda) {
            $vencido = $deuda;
        }

        $cuenta = array(
            "doc_type" => strtoupper(trim(isset($resumen["tipo_documento"]) ? (string) $resumen["tipo_documento"] : "")),
            "doc_number" => ModeloVascoSync::normalizarDocumento(isset($resumen["documento"]) ? $resumen["documento"] : ""),
            "deuda_total" => $deuda,
            "vencido_total" => $vencido,
            "pending_documents" => array(),
        );

        $abonosCliente = 0;
        $maxAbonosCliente = (int) ModeloVascoSync::$MAX_ABONOS_POR_CLIENTE;

        foreach ($documentos as $fila) {
            $clave = ModeloVascoSync::claveDocumentoCuenta(
                isset($fila["tipo_doc"]) ? $fila["tipo_doc"] : "",
                isset($fila["num_cta"]) ? $fila["num_cta"] : ""
            );
            $abonosFilas = isset($abonosPorDoc[$clave]) ? $abonosPorDoc[$clave] : array();

            if ($abonosCliente >= $maxAbonosCliente) {
                $abonosFilas = array();
            } elseif (!empty($abonosFilas) && ($abonosCliente + count($abonosFilas)) > $maxAbonosCliente) {
                $abonosFilas = array_slice($abonosFilas, 0, $maxAbonosCliente - $abonosCliente);
            }

            $doc = self::mapearDocumentoPendienteParaApi($fila, $abonosFilas);
            if (isset($doc["payments"])) {
                $abonosCliente += count($doc["payments"]);
            }
            $cuenta["pending_documents"][] = $doc;
        }

        return $cuenta;
    }

    /**
     * POST /v2/sync/account-statements-bulk — un lote.
     *
     * @param int $numeroLote
     * @param string $traceId
     * @return array
     */
    static public function ctrSincronizarLoteCuentas($numeroLote, $traceId)
    {
        if (!function_exists("obtenerUrlSyncCuentasVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $numeroLote = (int) $numeroLote;
        $traceId = trim((string) $traceId);
        $maxPorLote = self::maxClientesPorLote();

        if ($numeroLote < 1) {
            return array("ok" => false, "msg" => "Número de lote inválido");
        }

        if ($traceId === "") {
            $traceId = self::generarTraceIdCuentas();
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $url = obtenerUrlSyncCuentasVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de sync de cuentas no configurada");
        }

        $offset = ($numeroLote - 1) * $maxPorLote;
        $filas = ModeloVascoSync::mdlCuentasParaSyncLote($offset, $maxPorLote);

        if (empty($filas)) {
            return array(
                "ok" => true,
                "msg" => "Lote vacío (sin más registros)",
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "accounts_sent" => 0,
                "skipped" => true,
            );
        }

        $accounts = array();
        $docsEnviados = 0;
        $docKeys = array();

        foreach ($filas as $fila) {
            $docKey = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            if ($docKey !== "") {
                $docKeys[] = $docKey;
            }
        }

        $docsPorDocKey = ModeloVascoSync::mdlCuentasDocsPendientesPorDocKeys($docKeys);

        self::requireAdaptadorSaldoComercial();
        $loteComercial = AdaptadorSaldoComercialVasco::adaptarLote($filas, $docsPorDocKey);
        $filas = $loteComercial["filas"];
        $docsPorDocKey = $loteComercial["docs_por_doc_key"];

        $todosDocs = array();
        foreach ($docsPorDocKey as $pendientesLote) {
            foreach ($pendientesLote as $docPendiente) {
                $todosDocs[] = $docPendiente;
            }
        }
        self::prepararBrandCodeParaLote($todosDocs);
        $abonosPorDoc = ModeloVascoSync::mdlCuentasAbonosPorDocs($todosDocs);

        $paymentsEnviados = 0;
        foreach ($filas as $fila) {
            $docKey = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            $pendientes = isset($docsPorDocKey[$docKey]) ? $docsPorDocKey[$docKey] : array();
            $cuenta = self::mapearCuentaParaApi($fila, $pendientes, $abonosPorDoc);
            $accounts[] = $cuenta;
            $docsEnviados += count($pendientes);
            foreach ($cuenta["pending_documents"] as $docApi) {
                if (isset($docApi["payments"]) && is_array($docApi["payments"])) {
                    $paymentsEnviados += count($docApi["payments"]);
                }
            }
        }

        $payload = array(
            "trace_id" => $traceId,
            "batch" => $numeroLote,
            "accounts" => $accounts,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el lote a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $apiKey;

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $processed = isset($results["processed"]) ? (int) $results["processed"] : 0;
        $documents = isset($results["documents"]) ? (int) $results["documents"] : 0;
        $payments = isset($results["payments"]) ? (int) $results["payments"] : 0;
        $paymentWarnings = isset($results["payment_warnings"]) ? (int) $results["payment_warnings"] : 0;
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $parcial = $httpCode === 207 || count($failed) > 0;
            $msg = "Lote " . $numeroLote . " — guardados en Vasco: " . $processed . " clientes, " . $documents . " docs";
            if ($payments > 0 || $paymentsEnviados > 0) {
                $msg .= ", " . ($payments > 0 ? $payments : $paymentsEnviados) . " abonos";
            }
            if ($paymentWarnings > 0) {
                $msg .= " (" . $paymentWarnings . " payment_warnings)";
            }

            if ($processed === 0 && count($accounts) > 0) {
                $msg = "Lote " . $numeroLote . " — enviado pero 0 guardados en Vasco (revisar failed)";
                $parcial = true;
            } elseif ($parcial) {
                $msg .= " (" . count($failed) . " fallidos)";
            }

            return array(
                "ok" => $processed > 0 || ($processed === 0 && count($accounts) === 0),
                "partial" => $parcial,
                "msg" => $msg,
                "batch" => $numeroLote,
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "accounts_sent" => count($accounts),
                "documents_sent" => $docsEnviados,
                "payments_sent" => $paymentsEnviados,
                "processed" => $processed,
                "documents" => $documents,
                "payments" => $payments,
                "payment_warnings" => $paymentWarnings,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "batch" => $numeroLote,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "accounts_sent" => count($accounts),
            "processed" => $processed,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/account-statements-bulk — cierre de corrida (finalize: true).
     * Purga en Vasco clientes que no vinieron en esta corrida (ya no deben).
     *
     * @param string $traceId Mismo trace_id de los batches previos
     * @param int $numeroLote Número de lote de cierre (típicamente último + 1)
     * @return array
     */
    static public function ctrFinalizarSyncCuentas($traceId, $numeroLote)
    {
        if (!function_exists("obtenerUrlSyncCuentasVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $traceId = trim((string) $traceId);
        $numeroLote = (int) $numeroLote;

        if ($traceId === "") {
            return array("ok" => false, "msg" => "trace_id requerido para finalize");
        }

        if ($numeroLote < 1) {
            return array("ok" => false, "msg" => "Número de lote inválido");
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $url = obtenerUrlSyncCuentasVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de sync de cuentas no configurada");
        }

        $payload = array(
            "trace_id" => $traceId,
            "batch" => $numeroLote,
            "finalize" => true,
            "accounts" => array(),
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el cierre a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $apiKey;

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "finalize" => true,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $purged = isset($results["purged"]) ? (int) $results["purged"] : 0;
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $ok = count($failed) === 0;

            return array(
                "ok" => $ok,
                "partial" => !$ok,
                "msg" => $ok
                    ? "Finalize OK — purgados en Vasco: " . $purged
                    : "Finalize con errores (" . count($failed) . " fallidos)",
                "batch" => $numeroLote,
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "finalize" => true,
                "purged" => $purged,
                "processed" => isset($results["processed"]) ? (int) $results["processed"] : 0,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "batch" => $numeroLote,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "finalize" => true,
            "purged" => $purged,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * GET /health — sin API key.
     *
     * @return array
     */
    static public function ctrProbarConexion()
    {
        if (!function_exists("obtenerUrlHealthVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $url = obtenerUrlHealthVasco();

        if ($url === "") {
            return array(
                "ok" => false,
                "msg" => "URL base no configurada en config.php",
            );
        }

        if (!function_exists("curl_init")) {
            return array(
                "ok" => false,
                "msg" => "cURL no está disponible en este servidor",
                "url" => $url,
            );
        }

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;
        $timeoutHealth = $timeout > 30 ? 30 : $timeout;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutHealth,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => function_exists("obtenerHeadersCurlVascoOnline")
                ? obtenerHeadersCurlVascoOnline($url)
                : array("Accept: application/json"),
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            $msg = "No se pudo conectar: " . $err;
            if (strpos($err, "Connection refused") !== false || strpos($err, "Could not resolve host") !== false) {
                $msg .= ". Si vascorp corre en Docker y Vasco API en otro contenedor, usa host.docker.internal:8084 en config.php (no api.vasco.io desde dentro del contenedor).";
            }

            return array(
                "ok" => false,
                "msg" => $msg,
                "url" => $url,
            );
        }

        if ($httpCode !== 200) {
            $msg = "Respuesta HTTP " . $httpCode;
            if ($httpCode === 404 && strpos($body, "Vasco Admin") !== false) {
                $msg .= ". Apache devolvió el admin (virtual host). En Docker local usa Host: api.vasco.io (ya configurado en desarrollo).";
            }

            return array(
                "ok" => false,
                "msg" => $msg,
                "url" => $url,
                "http_code" => $httpCode,
                "body" => $body,
            );
        }

        $json = json_decode($body, true);
        $detalle = "";

        if (is_array($json)) {
            if (isset($json["status"])) {
                $detalle = (string) $json["status"];
            } elseif (isset($json["ok"])) {
                $detalle = $json["ok"] ? "ok" : "error";
            }
        }

        return array(
            "ok" => true,
            "msg" => $detalle !== "" ? "API disponible (" . $detalle . ")" : "API disponible",
            "url" => $url,
            "http_code" => $httpCode,
            "health" => is_array($json) ? $json : $body,
        );
    }

    static public function generarTraceIdCobranzas()
    {
        $sufijo = substr(md5(uniqid("cob", true)), 0, 6);

        return "vascorp-deliver-" . date("Ymd-His") . "-" . $sufijo;
    }

    static public function generarTraceIdCobranzasCancel()
    {
        $sufijo = substr(md5(uniqid("cobc", true)), 0, 6);

        return "vascorp-cancel-" . date("Ymd-His") . "-" . $sufijo;
    }

    /**
     * @return array{ok:bool,msg?:string,api_key?:string,timeout?:int}
     */
    private static function prepararClienteVascoApi()
    {
        if (!function_exists("obtenerConfigVascoOnline")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        return array(
            "ok" => true,
            "api_key" => $apiKey,
            "timeout" => $timeout,
        );
    }

    /**
     * GET /v2/sync/collections-pending-delivery
     *
     * @param array $filtros
     * @return array
     */
    static public function ctrListarCobranzasPendientes($filtros = array())
    {
        if (!function_exists("obtenerUrlCobranzasPendientesVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $urlBase = obtenerUrlCobranzasPendientesVasco();
        if ($urlBase === "") {
            return array("ok" => false, "msg" => "URL de cobranzas pendientes no configurada");
        }

        $status = isset($filtros["status"]) ? trim((string) $filtros["status"]) : "pending_delivery";
        if ($status === "") {
            $status = "pending_delivery";
        }

        $limit = isset($filtros["limit"]) ? (int) $filtros["limit"] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $traceId = isset($filtros["trace_id"]) ? trim((string) $filtros["trace_id"]) : "";
        if ($traceId === "") {
            $traceId = self::generarTraceIdCobranzas();
        }

        $query = array(
            "status" => $status,
            "limit" => $limit,
            "trace_id" => $traceId,
        );

        $sellerUsername = isset($filtros["seller_username"]) ? trim((string) $filtros["seller_username"]) : "";
        if ($sellerUsername !== "") {
            $query["seller_username"] = $sellerUsername;
        }

        $since = isset($filtros["since"]) ? trim((string) $filtros["since"]) : "";
        if ($since !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $since)) {
            $query["since"] = $since;
        }

        $url = $urlBase . "?" . http_build_query($query);

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        if ($httpCode === 200) {
            $items = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();

            return array(
                "ok" => true,
                "msg" => "Consulta OK",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "count" => isset($results["count"]) ? (int) $results["count"] : count($items),
                "total_amount" => isset($results["total_amount"]) ? (float) $results["total_amount"] : 0,
                "status" => isset($results["status"]) ? (string) $results["status"] : $status,
                "items" => $items,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/collections-deliver
     *
     * @param array $items
     * @param string $deliveredBy
     * @param string $traceId
     * @return array
     */
    static public function ctrEntregarCobranzas($items, $deliveredBy, $traceId = "")
    {
        if (!function_exists("obtenerUrlCobranzasDeliverVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $url = obtenerUrlCobranzasDeliverVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de entrega de cobranzas no configurada");
        }

        if (!is_array($items) || count($items) === 0) {
            return array("ok" => false, "msg" => "Seleccione al menos una cobranza");
        }

        if (count($items) > 500) {
            return array("ok" => false, "msg" => "Máximo 500 cobranzas por confirmación");
        }

        $itemsApi = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $row = array();
            $code = isset($item["code"]) ? trim((string) $item["code"]) : "";
            $id = isset($item["id"]) ? (int) $item["id"] : 0;

            if ($code === "" && $id < 1) {
                continue;
            }

            if ($code !== "") {
                $row["code"] = $code;
            }
            if ($id > 0) {
                $row["id"] = $id;
            }

            $externalRef = isset($item["external_reference"]) ? trim((string) $item["external_reference"]) : "";
            if ($externalRef !== "") {
                $row["external_reference"] = substr($externalRef, 0, 64);
            }

            $itemsApi[] = $row;
        }

        if (count($itemsApi) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para confirmar");
        }

        $traceId = trim((string) $traceId);
        if ($traceId === "") {
            $traceId = self::generarTraceIdCobranzas();
        }

        $deliveredBy = trim((string) $deliveredBy);
        if ($deliveredBy === "") {
            $deliveredBy = "caja.vascorp";
        }
        $deliveredBy = substr($deliveredBy, 0, 80);

        $payload = array(
            "trace_id" => $traceId,
            "delivered_by" => $deliveredBy,
            "items" => $itemsApi,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar la confirmación a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $itemsResult = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $ok = count($failed) === 0;

            return array(
                "ok" => $ok,
                "partial" => !$ok,
                "msg" => $ok
                    ? "Rendición confirmada en Vasco"
                    : "Confirmación parcial (" . count($failed) . " fallidos)",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "processed" => isset($results["processed"]) ? (int) $results["processed"] : count($itemsResult),
                "delivered" => isset($results["delivered"]) ? (int) $results["delivered"] : 0,
                "already_delivered" => isset($results["already_delivered"]) ? (int) $results["already_delivered"] : 0,
                "items" => $itemsResult,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/collections-cancel
     *
     * @param array $items
     * @param string $cancelledBy
     * @param string $traceId
     * @return array
     */
    static public function ctrAnularCobranzas($items, $cancelledBy, $traceId = "")
    {
        if (!function_exists("obtenerUrlCobranzasCancelVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $url = obtenerUrlCobranzasCancelVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de anulación de cobranzas no configurada");
        }

        if (!is_array($items) || count($items) === 0) {
            return array("ok" => false, "msg" => "Seleccione al menos una cobranza");
        }

        if (count($items) > 500) {
            return array("ok" => false, "msg" => "Máximo 500 cobranzas por anulación");
        }

        $itemsApi = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $row = array();
            $code = isset($item["code"]) ? trim((string) $item["code"]) : "";
            $id = isset($item["id"]) ? (int) $item["id"] : 0;
            $reason = isset($item["reason"]) ? trim((string) $item["reason"]) : "";

            if ($code === "" && $id < 1) {
                continue;
            }

            if ($reason === "") {
                return array("ok" => false, "msg" => "El motivo de anulación es obligatorio");
            }

            if ($code !== "") {
                $row["code"] = $code;
            }
            if ($id > 0) {
                $row["id"] = $id;
            }
            $row["reason"] = substr($reason, 0, 255);

            $itemsApi[] = $row;
        }

        if (count($itemsApi) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para anular");
        }

        $traceId = trim((string) $traceId);
        if ($traceId === "") {
            $traceId = self::generarTraceIdCobranzasCancel();
        }

        $cancelledBy = trim((string) $cancelledBy);
        if ($cancelledBy === "") {
            $cancelledBy = "caja.vascorp";
        }
        $cancelledBy = substr($cancelledBy, 0, 80);

        $payload = array(
            "trace_id" => $traceId,
            "cancelled_by" => $cancelledBy,
            "items" => $itemsApi,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar la anulación a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $itemsResult = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $ok = count($failed) === 0;

            return array(
                "ok" => $ok,
                "partial" => !$ok,
                "msg" => $ok
                    ? "Cobranza(s) anulada(s) en Vasco"
                    : "Anulación parcial (" . count($failed) . " fallidos)",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "processed" => isset($results["processed"]) ? (int) $results["processed"] : count($itemsResult),
                "cancelled" => isset($results["cancelled"]) ? (int) $results["cancelled"] : 0,
                "already_cancelled" => isset($results["already_cancelled"]) ? (int) $results["already_cancelled"] : 0,
                "items" => $itemsResult,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    static public function generarTraceIdGestionCliente()
    {
        $sufijo = substr(md5(uniqid("gest", true)), 0, 6);

        return "vascorp-gestion-" . date("Ymd-His") . "-" . $sufijo;
    }

    /**
     * Convierte E.164 Perú (51987654321) a 9 dígitos locales.
     *
     * @param string|null $phoneE164
     * @return string
     */
    static public function e164ATelefonoLocal($phoneE164)
    {
        $phone = preg_replace("/\D/", "", (string) $phoneE164);

        if (strlen($phone) === 11 && substr($phone, 0, 2) === "51") {
            return substr($phone, 2);
        }

        if (strlen($phone) === 9 && $phone[0] === "9") {
            return $phone;
        }

        return "";
    }

    /**
     * @param string $telefono
     * @return bool
     */
    static public function validarTelefonoLocalPe($telefono)
    {
        return preg_match("/^9\d{8}$/", (string) $telefono) === 1;
    }

    /**
     * @param array $customer
     * @return array|null
     */
    private static function resolverClienteErpDesdeVasco($customer)
    {
        if (!is_array($customer)) {
            $customer = array();
        }

        $externalId = isset($customer["external_id"]) ? $customer["external_id"] : null;
        $docType = isset($customer["doc_type"]) ? (string) $customer["doc_type"] : "";
        $docNumber = isset($customer["doc_number"]) ? (string) $customer["doc_number"] : "";
        $codigo = isset($customer["code"]) ? (string) $customer["code"] : "";

        return ModeloVascoSync::mdlClienteParaGestionVasco($externalId, $docType, $docNumber, $codigo);
    }

    /**
     * @param array $item
     * @return array
     */
    static public function previewGestionItemErp($item)
    {
        if (!is_array($item)) {
            $item = array();
        }

        $customer = isset($item["customer"]) && is_array($item["customer"]) ? $item["customer"] : array();
        $erp = self::resolverClienteErpDesdeVasco($customer);

        $preview = array(
            "encontrado" => $erp !== null,
            "id" => $erp ? (int) $erp["id"] : 0,
            "codigo" => $erp ? (string) $erp["codigo"] : "",
            "nombre" => $erp ? (string) $erp["nombre"] : "",
            "telefono_actual" => $erp ? trim((string) $erp["telefono"]) : "",
            "telefono_nuevo" => "",
            "puede_aplicar" => false,
            "motivo" => "",
        );

        if ($erp === null) {
            $preview["motivo"] = "Cliente no encontrado en ERP";

            return $preview;
        }

        $phoneE164 = isset($item["phone_e164"]) ? $item["phone_e164"] : null;
        $consent = !empty($item["whatsapp_consent"]);
        $portalOnly = !empty($item["portal_account_requested"]) && !$consent && ($phoneE164 === null || trim((string) $phoneE164) === "");

        if ($consent && $phoneE164 !== null && trim((string) $phoneE164) !== "") {
            $telefonoLocal = self::e164ATelefonoLocal($phoneE164);
            $preview["telefono_nuevo"] = $telefonoLocal;

            if (!self::validarTelefonoLocalPe($telefonoLocal)) {
                $preview["motivo"] = "Celular inválido (" . (string) $phoneE164 . ")";

                return $preview;
            }

            $dup = ModeloVascoSync::mdlClienteConTelefonoDuplicado($telefonoLocal, (int) $erp["id"]);
            if ($dup) {
                $preview["motivo"] = "Celular duplicado en ERP (código " . $dup["codigo"] . ")";

                return $preview;
            }

            $preview["puede_aplicar"] = true;
            if ($telefonoLocal === $preview["telefono_actual"]) {
                $preview["motivo"] = "Teléfono ya coincide en ERP";
            } else {
                $preview["motivo"] = "Actualizará teléfono en ERP";
            }

            return $preview;
        }

        if ($portalOnly) {
            $preview["puede_aplicar"] = true;
            $preview["motivo"] = "Solo portal (sin cambio de teléfono)";

            return $preview;
        }

        $preview["puede_aplicar"] = true;
        $preview["motivo"] = "Sin celular/consentimiento que aplicar";

        return $preview;
    }

    /**
     * @param array $items
     * @return array
     */
    static public function enriquecerGestionItemsConErp($items)
    {
        if (!is_array($items)) {
            return array();
        }

        $enriquecidos = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $item["erp_preview"] = self::previewGestionItemErp($item);
            $enriquecidos[] = $item;
        }

        return $enriquecidos;
    }

    /**
     * @param array $item
     * @return array{ok:bool,msg:string,telefono_anterior?:string,telefono_nuevo?:string,codigo?:string}
     */
    static public function aplicarGestionItemEnErp($item)
    {
        if (!is_array($item)) {
            return array("ok" => false, "msg" => "Ítem inválido");
        }

        $customer = isset($item["customer"]) && is_array($item["customer"]) ? $item["customer"] : array();
        $erp = self::resolverClienteErpDesdeVasco($customer);

        if ($erp === null) {
            return array("ok" => false, "msg" => "Cliente no encontrado en ERP");
        }

        $phoneE164 = isset($item["phone_e164"]) ? $item["phone_e164"] : null;
        $consent = !empty($item["whatsapp_consent"]);
        $portalOnly = !empty($item["portal_account_requested"]) && !$consent && ($phoneE164 === null || trim((string) $phoneE164) === "");

        if ($portalOnly) {
            return array(
                "ok" => true,
                "msg" => "Sin cambios en ERP (solo portal)",
                "codigo" => (string) $erp["codigo"],
            );
        }

        if ($consent && $phoneE164 !== null && trim((string) $phoneE164) !== "") {
            $telefonoLocal = self::e164ATelefonoLocal($phoneE164);
            if (!self::validarTelefonoLocalPe($telefonoLocal)) {
                return array("ok" => false, "msg" => "Celular inválido: " . (string) $phoneE164);
            }

            $dup = ModeloVascoSync::mdlClienteConTelefonoDuplicado($telefonoLocal, (int) $erp["id"]);
            if ($dup) {
                return array(
                    "ok" => false,
                    "msg" => "Celular duplicado en ERP (código " . $dup["codigo"] . ")",
                );
            }

            $telefonoAnterior = trim((string) $erp["telefono"]);
            if ($telefonoLocal === $telefonoAnterior) {
                return array(
                    "ok" => true,
                    "msg" => "Teléfono ya actualizado en ERP",
                    "codigo" => (string) $erp["codigo"],
                    "telefono_anterior" => $telefonoAnterior,
                    "telefono_nuevo" => $telefonoLocal,
                );
            }

            $resultado = ModeloVascoSync::mdlActualizarTelefonoCliente((int) $erp["id"], $telefonoLocal);
            if ($resultado !== "ok") {
                return array("ok" => false, "msg" => "No se pudo actualizar teléfono en ERP");
            }

            return array(
                "ok" => true,
                "msg" => "Teléfono actualizado en ERP",
                "codigo" => (string) $erp["codigo"],
                "telefono_anterior" => $telefonoAnterior,
                "telefono_nuevo" => $telefonoLocal,
            );
        }

        return array(
            "ok" => true,
            "msg" => "Sin cambios que aplicar",
            "codigo" => (string) $erp["codigo"],
        );
    }

    /**
     * GET /v2/sync/customer-field-updates
     *
     * @param array $filtros
     * @return array
     */
    static public function ctrListarGestionClientePendiente($filtros = array())
    {
        if (!function_exists("obtenerUrlGestionClienteVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $urlBase = obtenerUrlGestionClienteVasco();
        if ($urlBase === "") {
            return array("ok" => false, "msg" => "URL de gestión de cliente no configurada");
        }

        $status = isset($filtros["status"]) ? trim((string) $filtros["status"]) : "pending";
        if ($status === "") {
            $status = "pending";
        }

        $limit = isset($filtros["limit"]) ? (int) $filtros["limit"] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $traceId = isset($filtros["trace_id"]) ? trim((string) $filtros["trace_id"]) : "";
        if ($traceId === "") {
            $traceId = self::generarTraceIdGestionCliente();
        }

        $query = array(
            "status" => $status,
            "limit" => $limit,
            "trace_id" => $traceId,
        );

        $since = isset($filtros["since"]) ? trim((string) $filtros["since"]) : "";
        if ($since !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $since)) {
            $query["since"] = $since;
        }

        $url = $urlBase . "?" . http_build_query($query);

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        if ($httpCode === 200) {
            $items = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();
            $items = self::enriquecerGestionItemsConErp($items);

            return array(
                "ok" => true,
                "msg" => "Consulta OK",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "count" => isset($results["count"]) ? (int) $results["count"] : count($items),
                "status" => isset($results["status"]) ? (string) $results["status"] : $status,
                "items" => $items,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/customer-field-updates/ack
     *
     * @param array $ackItems
     * @param string $ackBy
     * @param string $traceId
     * @return array
     */
    static public function ctrAckGestionCliente($ackItems, $ackBy, $traceId = "")
    {
        if (!function_exists("obtenerUrlGestionClienteAckVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $url = obtenerUrlGestionClienteAckVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de ack de gestión no configurada");
        }

        if (!is_array($ackItems) || count($ackItems) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem para confirmar en Vasco");
        }

        if (count($ackItems) > 500) {
            return array("ok" => false, "msg" => "Máximo 500 ítems por confirmación");
        }

        $itemsApi = array();
        foreach ($ackItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = isset($item["id"]) ? (int) $item["id"] : 0;
            $result = isset($item["result"]) ? trim((string) $item["result"]) : "";
            if ($id < 1 || ($result !== "synced" && $result !== "rejected")) {
                continue;
            }

            $row = array(
                "id" => $id,
                "result" => $result,
            );

            if ($result === "rejected") {
                $reason = isset($item["rejection_reason"]) ? trim((string) $item["rejection_reason"]) : "";
                if ($reason === "") {
                    $reason = "Rechazado en vascorp";
                }
                $row["rejection_reason"] = substr($reason, 0, 255);
            }

            $itemsApi[] = $row;
        }

        if (count($itemsApi) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para ack");
        }

        $traceId = trim((string) $traceId);
        if ($traceId === "") {
            $traceId = self::generarTraceIdGestionCliente();
        }

        $ackBy = trim((string) $ackBy);
        if ($ackBy === "") {
            $ackBy = "vascorp";
        }
        $ackBy = substr($ackBy, 0, 80);

        $payload = array(
            "trace_id" => $traceId,
            "ack_by" => $ackBy,
            "items" => $itemsApi,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el ack a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $itemsResult = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $ok = count($failed) === 0;

            return array(
                "ok" => $ok,
                "partial" => !$ok,
                "msg" => $ok
                    ? "Gestión confirmada en Vasco"
                    : "Confirmación parcial (" . count($failed) . " fallidos)",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "processed" => isset($results["processed"]) ? (int) $results["processed"] : count($itemsResult),
                "synced" => isset($results["synced"]) ? (int) $results["synced"] : 0,
                "rejected" => isset($results["rejected"]) ? (int) $results["rejected"] : 0,
                "already_processed" => isset($results["already_processed"]) ? (int) $results["already_processed"] : 0,
                "items" => $itemsResult,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * Aplica cambios en ERP y confirma en Vasco.
     *
     * @param array $items [{id, action, rejection_reason?, vasco_item?}]
     * @param string $ackBy
     * @param string $traceId
     * @return array
     */
    static public function ctrProcesarGestionCliente($items, $ackBy, $traceId = "")
    {
        if (!is_array($items) || count($items) === 0) {
            return array("ok" => false, "msg" => "Seleccione al menos una gestión");
        }

        $ackItems = array();
        $detalleErp = array();

        foreach ($items as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = isset($entry["id"]) ? (int) $entry["id"] : 0;
            if ($id < 1) {
                continue;
            }

            $action = isset($entry["action"]) ? trim((string) $entry["action"]) : "synced";

            if ($action === "rejected") {
                $reason = isset($entry["rejection_reason"]) ? trim((string) $entry["rejection_reason"]) : "";
                if ($reason === "") {
                    $reason = "Rechazado en vascorp";
                }

                $ackItems[] = array(
                    "id" => $id,
                    "result" => "rejected",
                    "rejection_reason" => $reason,
                );
                $detalleErp[] = array(
                    "id" => $id,
                    "action" => "rejected",
                    "ok" => true,
                    "msg" => $reason,
                );
                continue;
            }

            $vascoItem = isset($entry["vasco_item"]) && is_array($entry["vasco_item"])
                ? $entry["vasco_item"]
                : array();

            $erpResult = self::aplicarGestionItemEnErp($vascoItem);
            $detalleErp[] = array_merge(
                array("id" => $id, "action" => "synced"),
                $erpResult
            );

            if ($erpResult["ok"]) {
                $ackItems[] = array("id" => $id, "result" => "synced");
            } else {
                $ackItems[] = array(
                    "id" => $id,
                    "result" => "rejected",
                    "rejection_reason" => $erpResult["msg"],
                );
            }
        }

        if (count($ackItems) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para procesar");
        }

        $respuesta = self::ctrAckGestionCliente($ackItems, $ackBy, $traceId);
        $respuesta["erp"] = $detalleErp;

        return $respuesta;
    }

    static public function generarTraceIdSolicitudAtencion()
    {
        $sufijo = substr(md5(uniqid("atencion", true)), 0, 6);

        return "vascorp-atencion-" . date("Ymd-His") . "-" . $sufijo;
    }

    /**
     * @param array $item
     * @return array
     */
    static public function previewSolicitudAtencionItemErp($item)
    {
        if (!is_array($item)) {
            $item = array();
        }

        $customer = isset($item["customer"]) && is_array($item["customer"]) ? $item["customer"] : array();
        $erp = self::resolverClienteErpDesdeVasco($customer);

        $preview = array(
            "encontrado" => $erp !== null,
            "id" => $erp ? (int) $erp["id"] : 0,
            "codigo" => $erp ? (string) $erp["codigo"] : "",
            "nombre" => $erp ? (string) $erp["nombre"] : "",
            "vendedor_erp" => $erp ? trim((string) $erp["vendedor"]) : "",
            "telefono" => $erp ? trim((string) $erp["telefono"]) : "",
            "puede_tomar" => false,
            "motivo" => "",
        );

        if ($erp === null) {
            $preview["motivo"] = "Cliente no encontrado en ERP";

            return $preview;
        }

        $preview["puede_tomar"] = true;
        $preview["motivo"] = "Cliente listo para tomar solicitud";

        return $preview;
    }

    /**
     * @param array $items
     * @return array
     */
    static public function enriquecerSolicitudesAtencionConErp($items)
    {
        if (!is_array($items)) {
            return array();
        }

        $enriquecidos = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $item["erp_preview"] = self::previewSolicitudAtencionItemErp($item);
            $enriquecidos[] = $item;
        }

        return $enriquecidos;
    }

    /**
     * GET /v2/sync/portal-visit-requests
     *
     * @param array $filtros
     * @return array
     */
    static public function ctrListarSolicitudesAtencion($filtros = array())
    {
        if (!function_exists("obtenerUrlSolicitudesAtencionVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $urlBase = obtenerUrlSolicitudesAtencionVasco();
        if ($urlBase === "") {
            return array("ok" => false, "msg" => "URL de solicitudes de atención no configurada");
        }

        $status = isset($filtros["status"]) ? trim((string) $filtros["status"]) : "pending";
        if ($status === "") {
            $status = "pending";
        }

        $limit = isset($filtros["limit"]) ? (int) $filtros["limit"] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $traceId = isset($filtros["trace_id"]) ? trim((string) $filtros["trace_id"]) : "";
        if ($traceId === "") {
            $traceId = self::generarTraceIdSolicitudAtencion();
        }

        $query = array(
            "status" => $status,
            "limit" => $limit,
            "trace_id" => $traceId,
        );

        $since = isset($filtros["since"]) ? trim((string) $filtros["since"]) : "";
        if ($since !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $since)) {
            $query["since"] = $since;
        }

        $url = $urlBase . "?" . http_build_query($query);

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        if ($httpCode === 200) {
            $items = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();
            $items = self::enriquecerSolicitudesAtencionConErp($items);

            return array(
                "ok" => true,
                "msg" => "Consulta OK",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "count" => isset($results["count"]) ? (int) $results["count"] : count($items),
                "status" => isset($results["status"]) ? (string) $results["status"] : $status,
                "items" => $items,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/portal-visit-requests/ack
     *
     * @param array $ackItems
     * @param string $ackBy
     * @param string $traceId
     * @return array
     */
    static public function ctrAckSolicitudesAtencion($ackItems, $ackBy, $traceId = "")
    {
        if (!function_exists("obtenerUrlSolicitudesAtencionAckVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $prep = self::prepararClienteVascoApi();
        if (!$prep["ok"]) {
            return $prep;
        }

        $url = obtenerUrlSolicitudesAtencionAckVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de ack de solicitudes no configurada");
        }

        if (!is_array($ackItems) || count($ackItems) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem para confirmar en Vasco");
        }

        if (count($ackItems) > 500) {
            return array("ok" => false, "msg" => "Máximo 500 ítems por confirmación");
        }

        $resultadosValidos = array("acknowledged", "rejected", "completed");
        $itemsApi = array();

        foreach ($ackItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = isset($item["id"]) ? (int) $item["id"] : 0;
            $result = isset($item["result"]) ? trim((string) $item["result"]) : "";
            if ($id < 1 || !in_array($result, $resultadosValidos, true)) {
                continue;
            }

            $row = array(
                "id" => $id,
                "result" => $result,
            );

            if ($result === "rejected") {
                $reason = isset($item["rejection_reason"]) ? trim((string) $item["rejection_reason"]) : "";
                if ($reason === "") {
                    $reason = "Rechazado en vascorp";
                }
                $row["rejection_reason"] = substr($reason, 0, 255);
            }

            $itemsApi[] = $row;
        }

        if (count($itemsApi) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para ack");
        }

        $traceId = trim((string) $traceId);
        if ($traceId === "") {
            $traceId = self::generarTraceIdSolicitudAtencion();
        }

        $ackBy = trim((string) $ackBy);
        if ($ackBy === "") {
            $ackBy = "vascorp";
        }
        $ackBy = substr($ackBy, 0, 80);

        $payload = array(
            "trace_id" => $traceId,
            "ack_by" => $ackBy,
            "items" => $itemsApi,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el ack a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $prep["api_key"];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $prep["timeout"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : (is_array($json) ? $json : array());

        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $itemsResult = isset($results["items"]) && is_array($results["items"]) ? $results["items"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $ok = count($failed) === 0;

            return array(
                "ok" => $ok,
                "partial" => !$ok,
                "msg" => $ok
                    ? "Solicitud confirmada en Vasco"
                    : "Confirmación parcial (" . count($failed) . " fallidos)",
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "processed" => isset($results["processed"]) ? (int) $results["processed"] : count($itemsResult),
                "acknowledged" => isset($results["acknowledged"]) ? (int) $results["acknowledged"] : 0,
                "completed" => isset($results["completed"]) ? (int) $results["completed"] : 0,
                "rejected" => isset($results["rejected"]) ? (int) $results["rejected"] : 0,
                "already_processed" => isset($results["already_processed"]) ? (int) $results["already_processed"] : 0,
                "items" => $itemsResult,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        } elseif (isset($results["error"])) {
            $msg .= ": " . $results["error"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "failed" => $failed,
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * Valida en ERP y confirma solicitudes de atención en Vasco.
     *
     * @param array $items [{id, action, rejection_reason?, vasco_item?}]
     * @param string $ackBy
     * @param string $traceId
     * @return array
     */
    static public function ctrProcesarSolicitudesAtencion($items, $ackBy, $traceId = "")
    {
        if (!is_array($items) || count($items) === 0) {
            return array("ok" => false, "msg" => "Seleccione al menos una solicitud");
        }

        $ackItems = array();
        $detalleErp = array();

        foreach ($items as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = isset($entry["id"]) ? (int) $entry["id"] : 0;
            if ($id < 1) {
                continue;
            }

            $action = isset($entry["action"]) ? trim((string) $entry["action"]) : "acknowledged";
            $vascoItem = isset($entry["vasco_item"]) && is_array($entry["vasco_item"])
                ? $entry["vasco_item"]
                : array();

            if ($action === "rejected") {
                $reason = isset($entry["rejection_reason"]) ? trim((string) $entry["rejection_reason"]) : "";
                if ($reason === "") {
                    $reason = "Rechazado en vascorp";
                }

                $ackItems[] = array(
                    "id" => $id,
                    "result" => "rejected",
                    "rejection_reason" => $reason,
                );
                $detalleErp[] = array(
                    "id" => $id,
                    "action" => "rejected",
                    "ok" => true,
                    "msg" => $reason,
                );
                continue;
            }

            if ($action === "completed") {
                $ackItems[] = array("id" => $id, "result" => "completed");
                $detalleErp[] = array(
                    "id" => $id,
                    "action" => "completed",
                    "ok" => true,
                    "msg" => "Marcada como atendida en Vasco",
                );
                continue;
            }

            $preview = self::previewSolicitudAtencionItemErp($vascoItem);
            if (!$preview["puede_tomar"]) {
                $ackItems[] = array(
                    "id" => $id,
                    "result" => "rejected",
                    "rejection_reason" => $preview["motivo"],
                );
                $detalleErp[] = array(
                    "id" => $id,
                    "action" => "acknowledged",
                    "ok" => false,
                    "msg" => $preview["motivo"],
                );
                continue;
            }

            $ackItems[] = array("id" => $id, "result" => "acknowledged");
            $detalleErp[] = array(
                "id" => $id,
                "action" => "acknowledged",
                "ok" => true,
                "msg" => "Solicitud tomada — vendedor ERP " . ($preview["vendedor_erp"] !== "" ? $preview["vendedor_erp"] : "sin asignar"),
                "codigo" => $preview["codigo"],
            );
        }

        if (count($ackItems) === 0) {
            return array("ok" => false, "msg" => "Ningún ítem válido para procesar");
        }

        $respuesta = self::ctrAckSolicitudesAtencion($ackItems, $ackBy, $traceId);
        $respuesta["erp"] = $detalleErp;

        return $respuesta;
    }

    // ============================================
    // GRUPOS EMPRESARIALES
    // ============================================

    static public function generarTraceIdGrupos()
    {
        $sufijo = substr(md5(uniqid("grp", true)), 0, 6);

        return "vascorp-groups-" . date("Ymd-His") . "-" . $sufijo;
    }

    static public function generarTraceIdMiembrosGrupos()
    {
        $sufijo = substr(md5(uniqid("gmem", true)), 0, 6);

        return "vascorp-group-members-" . date("Ymd-His") . "-" . $sufijo;
    }

    static public function ctrAuditarGrupos()
    {
        $resumenDb = ModeloVascoSync::mdlResumenGrupos();
        $listosGrupos = ModeloVascoSync::mdlContarGruposListos();
        $listosMiembros = ModeloVascoSync::mdlContarMiembrosGrupoListos();
        $maxPorLote = self::maxClientesPorLote();

        $sinCodigo = isset($resumenDb["sin_codigo"]) ? (int) $resumenDb["sin_codigo"] : 0;
        $sinNombre = isset($resumenDb["sin_nombre"]) ? (int) $resumenDb["sin_nombre"] : 0;
        $grupoInexistente = ModeloVascoSync::mdlContarMiembrosGrupoCodigoInexistente();
        $sinDocValido = ModeloVascoSync::mdlContarClientesConGrupoSinDocValido();

        $lotesGrupos = $listosGrupos > 0 ? (int) ceil($listosGrupos / $maxPorLote) : 0;
        $lotesMiembros = $listosMiembros > 0 ? (int) ceil($listosMiembros / $maxPorLote) : 0;

        $muestraGrupos = array();
        foreach (ModeloVascoSync::mdlMuestraGrupos(30) as $fila) {
            $muestraGrupos[] = array(
                "id" => (int) $fila["id"],
                "codigo" => isset($fila["codigo"]) ? (string) $fila["codigo"] : "",
                "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
                "estado" => isset($fila["estado"]) ? (int) $fila["estado"] : 0,
                "activo" => isset($fila["estado"]) && (int) $fila["estado"] === 1,
                "total_clientes" => isset($fila["total_clientes"]) ? (int) $fila["total_clientes"] : 0,
            );
        }

        $muestraMiembros = array();
        foreach (ModeloVascoSync::mdlMuestraMiembrosGrupo(30) as $fila) {
            $muestraMiembros[] = array(
                "customer_id" => (int) $fila["customer_id"],
                "customer_codigo" => isset($fila["customer_codigo"]) ? (string) $fila["customer_codigo"] : "",
                "customer_nombre" => isset($fila["customer_nombre"]) ? (string) $fila["customer_nombre"] : "",
                "tipo_documento" => isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "",
                "documento" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
                "grupo_id" => (int) $fila["grupo_id"],
                "grupo_codigo" => isset($fila["grupo_codigo"]) ? (string) $fila["grupo_codigo"] : "",
                "grupo_nombre" => isset($fila["grupo_nombre"]) ? (string) $fila["grupo_nombre"] : "",
            );
        }

        $grupoInexistenteFilas = array();
        foreach (ModeloVascoSync::mdlMiembrosGrupoCodigoInexistente(50) as $fila) {
            $grupoInexistenteFilas[] = array(
                "id" => (int) $fila["id"],
                "codigo" => isset($fila["codigo"]) ? (string) $fila["codigo"] : "",
                "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
                "grupo" => isset($fila["grupo"]) ? (string) $fila["grupo"] : "",
            );
        }

        $sinDocFilas = array();
        foreach (ModeloVascoSync::mdlClientesConGrupoSinDocValido(50) as $fila) {
            $sinDocFilas[] = array(
                "id" => (int) $fila["id"],
                "codigo" => isset($fila["codigo"]) ? (string) $fila["codigo"] : "",
                "nombre" => isset($fila["nombre"]) ? (string) $fila["nombre"] : "",
                "tipo_documento" => isset($fila["tipo_documento"]) ? (string) $fila["tipo_documento"] : "",
                "documento" => ModeloVascoSync::normalizarDocumento(isset($fila["documento"]) ? $fila["documento"] : ""),
                "grupo" => isset($fila["grupo"]) ? (string) $fila["grupo"] : "",
            );
        }

        return array(
            "ok" => true,
            "resumen" => array(
                "total_grupos" => isset($resumenDb["total"]) ? (int) $resumenDb["total"] : 0,
                "activos" => isset($resumenDb["activos"]) ? (int) $resumenDb["activos"] : 0,
                "inactivos" => isset($resumenDb["inactivos"]) ? (int) $resumenDb["inactivos"] : 0,
                "listos_grupos" => $listosGrupos,
                "listos_miembros" => $listosMiembros,
                "lotes_grupos" => $lotesGrupos,
                "lotes_miembros" => $lotesMiembros,
                "lotes_estimados" => $lotesGrupos + $lotesMiembros,
                "max_por_lote" => $maxPorLote,
                "sin_codigo" => $sinCodigo,
                "sin_nombre" => $sinNombre,
                "grupo_inexistente" => $grupoInexistente,
                "con_grupo_sin_doc" => $sinDocValido,
                "bloqueados_envio" => $sinCodigo + $sinNombre + $grupoInexistente + $sinDocValido,
            ),
            "muestra_grupos" => $muestraGrupos,
            "muestra_miembros" => $muestraMiembros,
            "bloqueos" => array(
                "grupo_inexistente" => $grupoInexistenteFilas,
                "con_grupo_sin_doc" => $sinDocFilas,
            ),
            "external_id_campo" => "id",
        );
    }

    static public function mapearGrupoParaApi($fila)
    {
        $nombre = trim(isset($fila["nombre"]) ? (string) $fila["nombre"] : "");
        $codigo = trim(isset($fila["codigo"]) ? (string) $fila["codigo"] : "");
        if ($nombre === "") {
            $nombre = $codigo !== "" ? $codigo : "GRUPO " . (int) $fila["id"];
        }

        $estado = isset($fila["estado"]) ? (int) $fila["estado"] : 0;
        $grupo = array(
            "external_id" => (string) (int) $fila["id"],
            "code" => $codigo,
            "name" => $nombre,
            "state" => $estado === 1 ? 1 : 2,
        );

        $notas = trim(isset($fila["descripcion"]) ? (string) $fila["descripcion"] : "");
        if ($notas !== "") {
            $grupo["notes"] = $notas;
        }

        return $grupo;
    }

    static public function mapearMiembroGrupoParaApi($fila)
    {
        return array(
            "customer_external_id" => (string) (int) $fila["customer_id"],
            "business_group_external_id" => (string) (int) $fila["grupo_id"],
        );
    }

    /**
     * @param array $failed
     * @param array $businessGroups
     * @return array
     */
    static public function enriquecerFailedGrupos($failed, $businessGroups)
    {
        if (!is_array($failed) || count($failed) === 0) {
            return array();
        }

        $porExternalId = array();
        foreach ($businessGroups as $pos => $g) {
            if (!is_array($g)) {
                continue;
            }
            if (isset($g["external_id"]) && $g["external_id"] !== "") {
                $porExternalId[(string) $g["external_id"]] = $g;
            }
        }

        $out = array();
        foreach ($failed as $idx => $f) {
            if (!is_array($f)) {
                $out[] = array("message" => (string) $f);
                continue;
            }

            $base = null;
            if (isset($f["index"]) && isset($businessGroups[(int) $f["index"]]) && is_array($businessGroups[(int) $f["index"]])) {
                $base = $businessGroups[(int) $f["index"]];
            } elseif (isset($f["external_id"]) && isset($porExternalId[(string) $f["external_id"]])) {
                $base = $porExternalId[(string) $f["external_id"]];
            } elseif (isset($businessGroups[$idx]) && is_array($businessGroups[$idx])) {
                $base = $businessGroups[$idx];
            }

            $message = "";
            if (isset($f["message"])) {
                $message = (string) $f["message"];
            } elseif (isset($f["error"])) {
                $message = (string) $f["error"];
            }

            $out[] = array(
                "external_id" => isset($f["external_id"]) ? (string) $f["external_id"] : ($base && isset($base["external_id"]) ? (string) $base["external_id"] : ""),
                "code" => isset($f["code"]) ? (string) $f["code"] : ($base && isset($base["code"]) ? (string) $base["code"] : ""),
                "name" => $base && isset($base["name"]) ? (string) $base["name"] : "",
                "message" => $message !== "" ? $message : "Rechazado por Vasco",
            );
        }

        return $out;
    }

    /**
     * @param array $failed
     * @param array $members
     * @param array $filasOriginales
     * @return array
     */
    static public function enriquecerFailedMiembros($failed, $members, $filasOriginales = array())
    {
        if (!is_array($failed) || count($failed) === 0) {
            return array();
        }

        $porCustomerId = array();
        foreach ($filasOriginales as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $cid = isset($fila["customer_id"]) ? (string) (int) $fila["customer_id"] : "";
            if ($cid !== "") {
                $porCustomerId[$cid] = $fila;
            }
        }

        $out = array();
        foreach ($failed as $idx => $f) {
            if (!is_array($f)) {
                $out[] = array("message" => (string) $f);
                continue;
            }

            $customerId = isset($f["customer_external_id"]) ? (string) $f["customer_external_id"] : "";
            if ($customerId === "" && isset($members[(int) (isset($f["index"]) ? $f["index"] : $idx)]["customer_external_id"])) {
                $customerId = (string) $members[(int) (isset($f["index"]) ? $f["index"] : $idx)]["customer_external_id"];
            }

            $base = isset($porCustomerId[$customerId]) ? $porCustomerId[$customerId] : null;

            $message = "";
            if (isset($f["message"])) {
                $message = (string) $f["message"];
            } elseif (isset($f["error"])) {
                $message = (string) $f["error"];
            }

            $out[] = array(
                "customer_external_id" => $customerId,
                "customer_codigo" => $base && isset($base["customer_codigo"]) ? (string) $base["customer_codigo"] : "",
                "customer_nombre" => $base && isset($base["customer_nombre"]) ? (string) $base["customer_nombre"] : "",
                "business_group_external_id" => isset($f["business_group_external_id"])
                    ? (string) $f["business_group_external_id"]
                    : ($base && isset($base["grupo_id"]) ? (string) (int) $base["grupo_id"] : ""),
                "grupo_codigo" => $base && isset($base["grupo_codigo"]) ? (string) $base["grupo_codigo"] : "",
                "message" => $message !== "" ? $message : "Rechazado por Vasco",
            );
        }

        return $out;
    }

    /**
     * POST /v2/sync/business-groups-bulk — un lote.
     *
     * @param int $numeroLote
     * @param string $traceId
     * @return array
     */
    static public function ctrSincronizarLoteGrupos($numeroLote, $traceId)
    {
        if (!function_exists("obtenerUrlSyncGruposVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $numeroLote = (int) $numeroLote;
        $traceId = trim((string) $traceId);
        $maxPorLote = self::maxClientesPorLote();

        if ($numeroLote < 1) {
            return array("ok" => false, "msg" => "Número de lote inválido");
        }

        if ($traceId === "") {
            $traceId = self::generarTraceIdGrupos();
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $url = obtenerUrlSyncGruposVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de sync de grupos no configurada");
        }

        $offset = ($numeroLote - 1) * $maxPorLote;
        $filas = ModeloVascoSync::mdlGruposParaSyncLote($offset, $maxPorLote);

        if (empty($filas)) {
            return array(
                "ok" => true,
                "msg" => "Lote vacío (sin más registros)",
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "groups_sent" => 0,
                "skipped" => true,
            );
        }

        $businessGroups = array();
        foreach ($filas as $fila) {
            $businessGroups[] = self::mapearGrupoParaApi($fila);
        }

        $payload = array(
            "trace_id" => $traceId,
            "batch" => $numeroLote,
            "business_groups" => $businessGroups,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el lote a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $apiKey;

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : array();

        $processed = isset($results["processed"]) ? (int) $results["processed"] : count($businessGroups);
        $inserted = isset($results["inserted"]) ? (int) $results["inserted"] : 0;
        $updated = isset($results["updated"]) ? (int) $results["updated"] : 0;
        $adopted = isset($results["adopted"]) ? (int) $results["adopted"] : 0;
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $failed = self::enriquecerFailedGrupos($failed, $businessGroups);

        if ($httpCode === 200 || $httpCode === 207) {
            $parcial = $httpCode === 207 || count($failed) > 0;

            return array(
                "ok" => true,
                "partial" => $parcial,
                "msg" => $parcial
                    ? "Lote grupos " . $numeroLote . " con advertencias (" . count($failed) . " fallidos)"
                    : "Lote grupos " . $numeroLote . " enviado correctamente",
                "batch" => $numeroLote,
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "groups_sent" => count($businessGroups),
                "processed" => $processed,
                "inserted" => $inserted,
                "updated" => $updated,
                "adopted" => $adopted,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "batch" => $numeroLote,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "groups_sent" => count($businessGroups),
            "body" => $body,
            "url" => $url,
        );
    }

    /**
     * POST /v2/sync/business-group-members-bulk — un lote.
     *
     * @param int $numeroLote
     * @param string $traceId
     * @return array
     */
    static public function ctrSincronizarLoteMiembrosGrupos($numeroLote, $traceId)
    {
        if (!function_exists("obtenerUrlSyncMiembrosGruposVasco")) {
            require_once __DIR__ . "/config.php";
            require_once __DIR__ . "/vasco-online.config.php";
        }

        $numeroLote = (int) $numeroLote;
        $traceId = trim((string) $traceId);
        $maxPorLote = self::maxClientesPorLote();

        if ($numeroLote < 1) {
            return array("ok" => false, "msg" => "Número de lote inválido");
        }

        if ($traceId === "") {
            $traceId = self::generarTraceIdMiembrosGrupos();
        }

        $apiKey = function_exists("obtenerApiKeyVascoOnline") ? obtenerApiKeyVascoOnline() : (defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "");
        if ($apiKey === "") {
            return array("ok" => false, "msg" => "API key no configurada en config.php");
        }

        if (!function_exists("curl_init")) {
            return array("ok" => false, "msg" => "cURL no está disponible en este servidor");
        }

        $url = obtenerUrlSyncMiembrosGruposVasco();
        if ($url === "") {
            return array("ok" => false, "msg" => "URL de sync de miembros no configurada");
        }

        $offset = ($numeroLote - 1) * $maxPorLote;
        $filas = ModeloVascoSync::mdlMiembrosGrupoParaSyncLote($offset, $maxPorLote);

        if (empty($filas)) {
            return array(
                "ok" => true,
                "msg" => "Lote vacío (sin más registros)",
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "members_sent" => 0,
                "skipped" => true,
            );
        }

        $members = array();
        foreach ($filas as $fila) {
            $members[] = self::mapearMiembroGrupoParaApi($fila);
        }

        $payload = array(
            "trace_id" => $traceId,
            "batch" => $numeroLote,
            "members" => $members,
        );

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return array("ok" => false, "msg" => "No se pudo serializar el lote a JSON");
        }

        $headers = function_exists("obtenerHeadersCurlVascoOnline")
            ? obtenerHeadersCurlVascoOnline($url)
            : array("Accept: application/json");
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: " . $apiKey;

        $timeout = defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err !== "") {
            return array(
                "ok" => false,
                "msg" => "No se pudo conectar: " . $err,
                "batch" => $numeroLote,
                "trace_id" => $traceId,
                "url" => $url,
            );
        }

        $json = json_decode($body, true);
        $results = is_array($json) && isset($json["results"]) && is_array($json["results"])
            ? $json["results"]
            : array();

        $processed = isset($results["processed"]) ? (int) $results["processed"] : count($members);
        $updated = isset($results["updated"]) ? (int) $results["updated"] : 0;
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();
        $failed = self::enriquecerFailedMiembros($failed, $members, $filas);

        if ($httpCode === 200 || $httpCode === 207) {
            $parcial = $httpCode === 207 || count($failed) > 0;

            return array(
                "ok" => true,
                "partial" => $parcial,
                "msg" => $parcial
                    ? "Lote miembros " . $numeroLote . " con advertencias (" . count($failed) . " fallidos)"
                    : "Lote miembros " . $numeroLote . " enviado correctamente",
                "batch" => $numeroLote,
                "trace_id" => isset($results["trace_id"]) ? (string) $results["trace_id"] : $traceId,
                "http_code" => $httpCode,
                "members_sent" => count($members),
                "processed" => $processed,
                "updated" => $updated,
                "failed" => $failed,
                "url" => $url,
            );
        }

        $msg = "Respuesta HTTP " . $httpCode;
        if (is_array($json) && isset($json["message"])) {
            $msg .= ": " . $json["message"];
        }

        return array(
            "ok" => false,
            "msg" => $msg,
            "batch" => $numeroLote,
            "trace_id" => $traceId,
            "http_code" => $httpCode,
            "members_sent" => count($members),
            "body" => $body,
            "url" => $url,
        );
    }
}

