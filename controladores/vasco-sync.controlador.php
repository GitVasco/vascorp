<?php

class ControladorVascoSync
{
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

        $telefono = trim(isset($fila["telefono"]) ? (string) $fila["telefono"] : "");
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

        $apiKey = defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "";
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
        $global = ModeloVascoSync::mdlCuentasResumenGlobal();
        $clientesConDeuda = isset($global["clientes_con_deuda"]) ? (int) $global["clientes_con_deuda"] : 0;
        $docsPendientes = isset($global["docs_pendientes"]) ? (int) $global["docs_pendientes"] : 0;
        $deudaTotal = isset($global["deuda_total"]) ? (float) $global["deuda_total"] : 0;
        $vencidoTotal = isset($global["vencido_total"]) ? (float) $global["vencido_total"] : 0;

        $bloqueadosSinDoc = ModeloVascoSync::mdlCuentasContarBloqueadosSinDoc();
        $bloqueadosExceso = ModeloVascoSync::mdlCuentasContarBloqueadosExcesoDocs();
        $maxPorLote = self::maxClientesPorLote();
        $lotesEstimados = $clientesConDeuda > 0 ? (int) ceil($clientesConDeuda / $maxPorLote) : 0;

        $muestra = ModeloVascoSync::mdlCuentasMuestra(30);
        $muestraFormateada = array();

        foreach ($muestra as $fila) {
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

    static public function mapearDocumentoPendienteParaApi($fila)
    {
        $doc = array(
            "doc_type" => self::normalizarTipoDocComercial(isset($fila["tipo_doc"]) ? $fila["tipo_doc"] : ""),
            "doc_number" => trim(isset($fila["num_cta"]) ? (string) $fila["num_cta"] : ""),
            "amount" => round((float) (isset($fila["monto"]) ? $fila["monto"] : 0), 2),
            "balance" => round((float) (isset($fila["saldo"]) ? $fila["saldo"] : 0), 2),
        );

        $issue = self::fechaApiCuenta(isset($fila["fecha"]) ? $fila["fecha"] : "");
        if ($issue !== "") {
            $doc["issue_date"] = $issue;
        }

        $due = self::fechaApiCuenta(isset($fila["fecha_ven"]) ? $fila["fecha_ven"] : "");
        if ($due !== "") {
            $doc["due_date"] = $due;
        }

        return $doc;
    }

    static public function mapearCuentaParaApi($resumen, $documentos)
    {
        $deuda = round((float) (isset($resumen["deuda_total"]) ? $resumen["deuda_total"] : 0), 2);
        $vencido = round((float) (isset($resumen["vencido_total"]) ? $resumen["vencido_total"] : 0), 2);

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

        foreach ($documentos as $fila) {
            $cuenta["pending_documents"][] = self::mapearDocumentoPendienteParaApi($fila);
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

        $apiKey = defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "";
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

        foreach ($filas as $fila) {
            $docKey = isset($fila["doc_key"]) ? (string) $fila["doc_key"] : "";
            $pendientes = isset($docsPorDocKey[$docKey]) ? $docsPorDocKey[$docKey] : array();
            $accounts[] = self::mapearCuentaParaApi($fila, $pendientes);
            $docsEnviados += count($pendientes);
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
        $failed = isset($results["failed"]) && is_array($results["failed"]) ? $results["failed"] : array();

        if ($httpCode === 200 || $httpCode === 207) {
            $parcial = $httpCode === 207 || count($failed) > 0;
            $msg = "Lote " . $numeroLote . " — guardados en Vasco: " . $processed . " clientes, " . $documents . " docs";

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
                "processed" => $processed,
                "documents" => $documents,
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

        $apiKey = defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "";
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
}
