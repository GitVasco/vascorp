<?php

/**
 * Vasco Online — sync vascorp → API en internet.
 *
 * Ajusta SOLO este archivo al cambiar de entorno.
 * La API key va en controladores/config.php (junto a TOKEN_WHATSAPP, etc.).
 *
 * Entornos:
 *   desarrollo  — vascorp en Docker Mac, API Vasco en otro Docker (puerto 8084 en host)
 *   pruebas     — vascorp en desarrollo, pero contra el API REAL en internet
 *                 (https://api.jackyform.com.pe) para validar antes de producción
 *   produccion  — vascorp en servidor, contra el API REAL en internet
 *
 * Para pasar de pruebas a producción: solo cambia $vasco_online_entorno a "produccion".
 * La URL del API real es la misma; cambia solo el origen desde donde se ejecuta vascorp.
 */

$vasco_online_entorno = "pruebas";

// URL pública del API real (misma para pruebas y producción).
define("VASCO_ONLINE_API_URL_REAL", "https://api.jackyform.com.pe");

if ($vasco_online_entorno === "desarrollo") {

    // Conecta al puerto publicado en el Mac; Host virtual para Apache del API local.
    define("VASCO_ONLINE_API_BASE_URL", "http://host.docker.internal:8084");
    define("VASCO_ONLINE_API_HOST", "api.vasco.io");

} else {

    // pruebas y produccion usan el API real público (HTTPS, sin trucos de Host).
    define("VASCO_ONLINE_API_BASE_URL", VASCO_ONLINE_API_URL_REAL);

}

// API key en controladores/config.php (junto a otros tokens).
// Aquí solo URLs, timeout y endpoints según entorno Docker/XAMPP.
define("VASCO_ONLINE_SYNC_TIMEOUT", 120);
define("VASCO_ONLINE_MAX_POR_LOTE", 500);
define("VASCO_ONLINE_ENDPOINT_CLIENTES", "/v2/sync/customers-bulk");
define("VASCO_ONLINE_ENDPOINT_CUENTAS", "/v2/sync/account-statements-bulk");
define("VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING", "/v2/sync/collections-pending-delivery");
define("VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER", "/v2/sync/collections-deliver");
define("VASCO_ONLINE_ENDPOINT_FIELD_UPDATES", "/v2/sync/customer-field-updates");
define("VASCO_ONLINE_ENDPOINT_FIELD_UPDATES_ACK", "/v2/sync/customer-field-updates/ack");
define("VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS", "/v2/sync/portal-visit-requests");
define("VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS_ACK", "/v2/sync/portal-visit-requests/ack");

/**
 * @return array
 */
function obtenerConfigVascoOnline()
{
    return array(
        "entorno" => isset($GLOBALS["vasco_online_entorno"]) ? $GLOBALS["vasco_online_entorno"] : "",
        "base_url" => defined("VASCO_ONLINE_API_BASE_URL") ? VASCO_ONLINE_API_BASE_URL : "",
        "api_host" => defined("VASCO_ONLINE_API_HOST") ? VASCO_ONLINE_API_HOST : "",
        "api_key" => defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "",
        "timeout" => defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120,
        "max_por_lote" => defined("VASCO_ONLINE_MAX_POR_LOTE") ? (int) VASCO_ONLINE_MAX_POR_LOTE : 500,
        "endpoint_clientes" => defined("VASCO_ONLINE_ENDPOINT_CLIENTES") ? VASCO_ONLINE_ENDPOINT_CLIENTES : "/v2/sync/customers-bulk",
        "endpoint_cuentas" => defined("VASCO_ONLINE_ENDPOINT_CUENTAS") ? VASCO_ONLINE_ENDPOINT_CUENTAS : "/v2/sync/account-statements-bulk",
        "endpoint_cobranzas_pending" => defined("VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING") ? VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING : "/v2/sync/collections-pending-delivery",
        "endpoint_cobranzas_deliver" => defined("VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER") ? VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER : "/v2/sync/collections-deliver",
        "endpoint_field_updates" => defined("VASCO_ONLINE_ENDPOINT_FIELD_UPDATES") ? VASCO_ONLINE_ENDPOINT_FIELD_UPDATES : "/v2/sync/customer-field-updates",
        "endpoint_field_updates_ack" => defined("VASCO_ONLINE_ENDPOINT_FIELD_UPDATES_ACK") ? VASCO_ONLINE_ENDPOINT_FIELD_UPDATES_ACK : "/v2/sync/customer-field-updates/ack",
        "endpoint_portal_visit_requests" => defined("VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS") ? VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS : "/v2/sync/portal-visit-requests",
        "endpoint_portal_visit_requests_ack" => defined("VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS_ACK") ? VASCO_ONLINE_ENDPOINT_PORTAL_VISIT_REQUESTS_ACK : "/v2/sync/portal-visit-requests/ack",
    );
}

/**
 * @return string
 */
function obtenerUrlSyncClientesVasco()
{
    $config = obtenerConfigVascoOnline();
    $base = rtrim($config["base_url"], "/");
    $endpoint = $config["endpoint_clientes"];

    if ($endpoint === "" || $endpoint[0] !== "/") {
        $endpoint = "/" . $endpoint;
    }

    return $base . $endpoint;
}

/**
 * @return string
 */
function obtenerUrlSyncCuentasVasco()
{
    $config = obtenerConfigVascoOnline();
    $base = rtrim($config["base_url"], "/");
    $endpoint = isset($config["endpoint_cuentas"]) ? $config["endpoint_cuentas"] : "/v2/sync/account-statements-bulk";

    if ($endpoint === "" || $endpoint[0] !== "/") {
        $endpoint = "/" . $endpoint;
    }

    return $base . $endpoint;
}

/**
 * @param string $endpoint
 * @return string
 */
function obtenerUrlVascoEndpoint($endpoint)
{
    $config = obtenerConfigVascoOnline();
    $base = rtrim($config["base_url"], "/");

    if ($endpoint === "" || $endpoint[0] !== "/") {
        $endpoint = "/" . $endpoint;
    }

    return $base !== "" ? $base . $endpoint : "";
}

/**
 * @return string
 */
function obtenerUrlCobranzasPendientesVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_cobranzas_pending"]) ? $config["endpoint_cobranzas_pending"] : "/v2/sync/collections-pending-delivery";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlCobranzasDeliverVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_cobranzas_deliver"]) ? $config["endpoint_cobranzas_deliver"] : "/v2/sync/collections-deliver";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlGestionClienteVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_field_updates"]) ? $config["endpoint_field_updates"] : "/v2/sync/customer-field-updates";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlGestionClienteAckVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_field_updates_ack"]) ? $config["endpoint_field_updates_ack"] : "/v2/sync/customer-field-updates/ack";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlSolicitudesAtencionVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_portal_visit_requests"]) ? $config["endpoint_portal_visit_requests"] : "/v2/sync/portal-visit-requests";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlSolicitudesAtencionAckVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_portal_visit_requests_ack"]) ? $config["endpoint_portal_visit_requests_ack"] : "/v2/sync/portal-visit-requests/ack";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlHealthVasco()
{
    $config = obtenerConfigVascoOnline();
    $base = rtrim($config["base_url"], "/");

    return $base !== "" ? $base . "/health" : "";
}

/**
 * Host virtual para cURL (solo desarrollo Docker).
 *
 * @param string $url
 * @return array
 */
function obtenerHeadersCurlVascoOnline($url)
{
    $headers = array("Accept: application/json");

    if (!defined("VASCO_ONLINE_API_HOST") || VASCO_ONLINE_API_HOST === "") {
        return $headers;
    }

    $parsed = parse_url($url);
    $connectHost = isset($parsed["host"]) ? $parsed["host"] : "";

    if ($connectHost !== "" && $connectHost !== VASCO_ONLINE_API_HOST) {
        $headers[] = "Host: " . VASCO_ONLINE_API_HOST;
    }

    return $headers;
}

/**
 * @return string
 */
function vascoOnlineApiKeyEnmascarada()
{
    $key = defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "";

    if ($key === "") {
        return "";
    }

    if (strlen($key) <= 8) {
        return "••••••••";
    }

    return substr($key, 0, 4) . "••••••••" . substr($key, -4);
}
