<?php

/**
 * Vasco Online — sync vascorp → API (trackeado).
 *
 * Entorno y API key de producción/pruebas: controladores/config.php (NO trackeado)
 *   define("VASCO_ONLINE_ENTORNO", "desarrollo"|"pruebas"|"produccion");
 *   define("VASCO_ONLINE_API_KEY", "...");
 *
 * Entornos:
 *   desarrollo  — vascorp en Docker Mac, API Vasco en otro Docker (puerto 8084 en host)
 *   pruebas     — vascorp en desarrollo, pero contra el API REAL en internet
 *   produccion  — vascorp en servidor, contra el API REAL en internet
 *
 * Si VASCO_ONLINE_ENTORNO no está definido, se usa "produccion" (API real) para no
 * apuntar por error a host.docker.internal en un deploy.
 */

$vasco_online_entorno = defined("VASCO_ONLINE_ENTORNO")
    ? (string) VASCO_ONLINE_ENTORNO
    : "produccion";

if (!in_array($vasco_online_entorno, array("desarrollo", "pruebas", "produccion"), true)) {
    $vasco_online_entorno = "produccion";
}

$GLOBALS["vasco_online_entorno"] = $vasco_online_entorno;

// URL pública del API real (misma para pruebas y producción).
define("VASCO_ONLINE_API_URL_REAL", "https://api.jackyform.com.pe");

if ($vasco_online_entorno === "desarrollo") {

    // Conecta al puerto publicado en el Mac; Host virtual para Apache del API local.
    define("VASCO_ONLINE_API_BASE_URL", "http://host.docker.internal:8084");
    define("VASCO_ONLINE_API_HOST", "api.vasco.io");
    // Debe coincidir con API_KEY del .env local de Vasco (NO es la key de producción).
    define("VASCO_ONLINE_API_KEY_DESARROLLO", "k6QFLuCbgpJPAXuQn2qz38sqLMrLDG");
} else {

    // pruebas y produccion usan el API real público (HTTPS, sin trucos de Host).
    define("VASCO_ONLINE_API_BASE_URL", VASCO_ONLINE_API_URL_REAL);
}

/**
 * Key efectiva según entorno.
 * desarrollo → key local; pruebas/produccion → VASCO_ONLINE_API_KEY de config.php (intacta).
 *
 * @return string
 */
function obtenerApiKeyVascoOnline()
{
    $entorno = isset($GLOBALS["vasco_online_entorno"]) ? $GLOBALS["vasco_online_entorno"] : "";

    if (
        $entorno === "desarrollo"
        && defined("VASCO_ONLINE_API_KEY_DESARROLLO")
        && VASCO_ONLINE_API_KEY_DESARROLLO !== ""
    ) {
        return VASCO_ONLINE_API_KEY_DESARROLLO;
    }

    return defined("VASCO_ONLINE_API_KEY") ? VASCO_ONLINE_API_KEY : "";
}

// Timeout y endpoints (comunes).
define("VASCO_ONLINE_SYNC_TIMEOUT", 120);
define("VASCO_ONLINE_MAX_POR_LOTE", 500);
define("VASCO_ONLINE_ENDPOINT_CLIENTES", "/v2/sync/customers-bulk");
define("VASCO_ONLINE_ENDPOINT_CUENTAS", "/v2/sync/account-statements-bulk");
define("VASCO_ONLINE_ENDPOINT_GRUPOS", "/v2/sync/business-groups-bulk");
define("VASCO_ONLINE_ENDPOINT_MIEMBROS_GRUPOS", "/v2/sync/business-group-members-bulk");
define("VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING", "/v2/sync/collections-pending-delivery");
define("VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER", "/v2/sync/collections-deliver");
define("VASCO_ONLINE_ENDPOINT_COBRANZAS_CANCEL", "/v2/sync/collections-cancel");
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
        "api_key" => obtenerApiKeyVascoOnline(),
        "timeout" => defined("VASCO_ONLINE_SYNC_TIMEOUT") ? (int) VASCO_ONLINE_SYNC_TIMEOUT : 120,
        "max_por_lote" => defined("VASCO_ONLINE_MAX_POR_LOTE") ? (int) VASCO_ONLINE_MAX_POR_LOTE : 500,
        "endpoint_clientes" => defined("VASCO_ONLINE_ENDPOINT_CLIENTES") ? VASCO_ONLINE_ENDPOINT_CLIENTES : "/v2/sync/customers-bulk",
        "endpoint_cuentas" => defined("VASCO_ONLINE_ENDPOINT_CUENTAS") ? VASCO_ONLINE_ENDPOINT_CUENTAS : "/v2/sync/account-statements-bulk",
        "endpoint_grupos" => defined("VASCO_ONLINE_ENDPOINT_GRUPOS") ? VASCO_ONLINE_ENDPOINT_GRUPOS : "/v2/sync/business-groups-bulk",
        "endpoint_miembros_grupos" => defined("VASCO_ONLINE_ENDPOINT_MIEMBROS_GRUPOS") ? VASCO_ONLINE_ENDPOINT_MIEMBROS_GRUPOS : "/v2/sync/business-group-members-bulk",
        "endpoint_cobranzas_pending" => defined("VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING") ? VASCO_ONLINE_ENDPOINT_COBRANZAS_PENDING : "/v2/sync/collections-pending-delivery",
        "endpoint_cobranzas_deliver" => defined("VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER") ? VASCO_ONLINE_ENDPOINT_COBRANZAS_DELIVER : "/v2/sync/collections-deliver",
        "endpoint_cobranzas_cancel" => defined("VASCO_ONLINE_ENDPOINT_COBRANZAS_CANCEL") ? VASCO_ONLINE_ENDPOINT_COBRANZAS_CANCEL : "/v2/sync/collections-cancel",
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
 * @return string
 */
function obtenerUrlSyncGruposVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_grupos"]) ? $config["endpoint_grupos"] : "/v2/sync/business-groups-bulk";

    return obtenerUrlVascoEndpoint($endpoint);
}

/**
 * @return string
 */
function obtenerUrlSyncMiembrosGruposVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_miembros_grupos"]) ? $config["endpoint_miembros_grupos"] : "/v2/sync/business-group-members-bulk";

    return obtenerUrlVascoEndpoint($endpoint);
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
function obtenerUrlCobranzasCancelVasco()
{
    $config = obtenerConfigVascoOnline();
    $endpoint = isset($config["endpoint_cobranzas_cancel"]) ? $config["endpoint_cobranzas_cancel"] : "/v2/sync/collections-cancel";

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
    $key = obtenerApiKeyVascoOnline();

    if ($key === "") {
        return "";
    }

    if (strlen($key) <= 8) {
        return "••••••••";
    }

    return substr($key, 0, 4) . "••••••••" . substr($key, -4);
}
